<?php

if (!defined('ABSPATH')) {
  exit;
} // Exit if accessed directly

/**
 * Client side throttle for the Linet api.
 *
 * Linet answers at most 60 requests a minute per account and replies 429 once
 * that budget is used up. A full sync, an admin ajax pulse, a checkout and
 * wp-cron can all be talking to Linet from different php processes at the same
 * time, so the counter cannot live in a static. It is a sliding window of
 * request times kept in an option and guarded by a mysql named lock.
 *
 * The window covers this WordPress install only. Two sites sharing one Linet
 * account each need their own (lower) limit.
 */
class WC_LI_Rate_Limiter
{

  /** Sliding window of request times, one float per request. */
  const WINDOW_OPTION = 'wc_linet_api_window';

  /** Timestamp every process waits for after Linet answered 429. */
  const BACKOFF_OPTION = 'wc_linet_api_backoff';

  /** Length of the window Linet counts, in seconds. */
  const WINDOW = 60;

  /** Requests Linet allows per window. */
  const DEFAULT_LIMIT = 60;

  /** Longest a single call will block waiting for a free slot. */
  const MAX_WAIT = 120;

  /** Longest a single usleep() is, so a slot freed elsewhere is noticed. */
  const POLL = 5;

  /** First wait when Linet refuses without saying for how long. */
  const BLIND_BACKOFF = 5;

  /** Seconds to wait for the named lock before giving up on it. */
  const LOCK_TIMEOUT = 10;

  /** Calls in a row that went unanswered, counted across processes. */
  const TIMEOUT_OPTION = 'wc_linet_api_timeouts';

  /** Unanswered calls in a row before a run is given up on. */
  const TIMEOUT_LIMIT = 3;

  /** How long every process is held back after one call went unanswered. */
  const TIMEOUT_BACKOFF = 10;

  /** Seconds this request has spent waiting, for the admin screen to show. */
  private static $waited = 0.0;

  /**
   * How long this request has been held back by the throttle, in seconds.
   *
   * @return float
   */
  public static function waited()
  {
    return round(self::$waited, 1);
  }

  /**
   * Requests allowed per minute.
   *
   * The option is there for accounts on a different plan and for sites that
   * have to leave head room for a second install; 0 turns the throttle off.
   *
   * @return int
   */
  public static function limit()
  {
    $limit = get_option('wc_linet_rate_limit', self::DEFAULT_LIMIT);

    if (!is_numeric($limit)) {
      $limit = self::DEFAULT_LIMIT;
    }

    return (int) apply_filters('woocommerce_linet_rate_limit', (int) $limit);
  }

  /**
   * Block until Linet will accept another request, then count it.
   *
   * Gives up after MAX_WAIT seconds and lets the request through: a 429 is a
   * better outcome than a php process parked for the rest of its time limit,
   * and sendAPI() retries on it anyway.
   *
   * @param WC_LI_Logger|false $logger
   */
  public static function reserve($logger = false)
  {
    $limit = self::limit();

    if ($limit <= 0) {
      return;
    }

    $max_wait = (float) apply_filters('woocommerce_linet_rate_max_wait', self::MAX_WAIT);
    $deadline = microtime(true) + $max_wait;
    $waited = 0.0;

    while (true) {
      $wait = self::attempt($limit, false);

      if ($wait <= 0) {
        break;
      }

      $now = microtime(true);

      if ($now >= $deadline) {
        // Count it anyway, so the window keeps matching what was sent.
        self::attempt($limit, true);
        self::$waited += $waited;
        if ($logger) {
          $logger->write(sprintf("LINET RATE LIMIT: no free slot after %.1fs, sending anyway\n", $waited));
        }
        return;
      }

      $wait = min($wait, $deadline - $now, (float) self::POLL);
      usleep((int) round($wait * 1000000));
      $waited += $wait;
    }

    if ($waited > 0) {
      self::$waited += $waited;

      if ($logger) {
        $logger->write(sprintf("LINET RATE LIMIT: held request %.1fs to stay under %d/min\n", $waited, $limit));
      }
    }
  }

  /**
   * Take a slot if one is free.
   *
   * @param int  $limit
   * @param bool $force Record the request even when the window is full.
   *
   * @return float Seconds to wait before trying again, 0 when the slot is ours.
   */
  private static function attempt($limit, $force)
  {
    $lock = self::lock();

    $now = microtime(true);
    $window = self::read_window($now);
    $backoff = self::read_backoff($now);

    $wait = 0.0;

    if (!$force) {
      if ($backoff > $now) {
        $wait = $backoff - $now;
      } elseif (count($window) >= $limit) {
        // The oldest call leaves the window in this many seconds.
        $wait = ($window[0] + self::WINDOW) - $now;

        if ($wait <= 0) {
          $wait = 0.001;
        }
      }
    }

    if ($wait <= 0) {
      $window[] = $now;
      self::write_window($window);
    }

    self::unlock($lock);

    return $wait;
  }

  /**
   * Park every process until $seconds from now, after Linet said 429.
   *
   * @param float              $seconds 0 to work the delay out from the window.
   * @param WC_LI_Logger|false $logger
   * @param int                $attempt Retries already made, to escalate on.
   *
   * @return float The delay that was applied.
   */
  public static function back_off($seconds, $logger = false, $attempt = 0)
  {
    $seconds = (float) $seconds;

    if ($seconds <= 0) {
      $seconds = self::default_backoff($attempt);
    }

    // Nothing is gained by parking longer than the whole window.
    $seconds = min($seconds, (float) self::WINDOW + 5);

    $until = microtime(true) + $seconds;
    $current = self::read_backoff(microtime(true));

    if ($until > $current) {
      update_option(self::BACKOFF_OPTION, $until, false);
    }

    if ($logger) {
      $logger->write(sprintf("LINET RATE LIMIT: backing off %.1fs\n", $seconds));
    }

    return $seconds;
    // The wait itself is counted by reserve(), which is what does the waiting.
  }

  /**
   * What a call that never came back looks like, in the transport's own words.
   *
   * Two families, and the same thing follows from both - there is no answer to
   * read a status off, and asking again straight away will be told the same:
   *
   * - out of time: curl says "Operation timed out after 15002 milliseconds",
   *   the socket transport says it in its own words.
   * - the connection died or was never made: a server that fell over mid
   *   request answers with nothing at all (curl 52 "Empty reply from server"),
   *   or drops the socket (curl 56 "Recv failure: Connection reset by peer",
   *   "OpenSSL SSL_read: Connection reset by peer"), or is not listening at
   *   all (curl 7 "Failed to connect to ...: Connection refused"). A name that
   *   will not resolve (curl 6) is the same answer from further away.
   *
   * Uploading a picture is what usually finds this: a create/file that Linet
   * crashes on comes back as an empty reply, and without this the run reads it
   * as an ordinary failure and walks the whole catalogue asking a server that
   * is plainly down.
   */
  const UNANSWERED_ERRORS = array(
    'timed out',
    'timeout',
    'operation aborted',
    'empty reply',
    'connection reset',
    'recv failure',
    'send failure',
    'broken pipe',
    'connection refused',
    'failed to connect',
    'could not connect',
    'could not resolve host',
    'name or service not known',
  );

  /**
   * Did the call never come back?
   *
   * @param WP_Error|array $response Raw wp_remote_* answer.
   *
   * @return bool
   */
  public static function is_timeout($response)
  {
    if (!is_wp_error($response)) {
      return false;
    }

    foreach ($response->get_error_messages() as $message) {
      foreach (self::UNANSWERED_ERRORS as $needle) {
        if (false !== stripos($message, $needle)) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Calls in a row that have gone unanswered.
   *
   * @return int
   */
  public static function unanswered()
  {
    wp_cache_delete(self::TIMEOUT_OPTION, 'options');

    return (int) get_option(self::TIMEOUT_OPTION, 0);
  }

  /**
   * Note that a call went unanswered, and hold every process back for a while.
   *
   * The wait grows with the count, because the first unanswered call may be
   * one slow request and the third is Linet saying it cannot keep up.
   *
   * @param WC_LI_Logger|false $logger
   *
   * @return int The number of unanswered calls in a row, this one included.
   */
  public static function timed_out($logger = false)
  {
    $count = self::unanswered() + 1;

    update_option(self::TIMEOUT_OPTION, $count, false);

    if ($logger) {
      $logger->write(sprintf("LINET: %d call(s) in a row went unanswered\n", $count));
    }

    self::back_off(self::TIMEOUT_BACKOFF * $count, $logger);

    return $count;
  }

  /**
   * Note that Linet answered, whatever it had to say.
   */
  public static function answered()
  {
    if (0 !== self::unanswered()) {
      update_option(self::TIMEOUT_OPTION, 0, false);
    }
  }

  /**
   * Has Linet stopped answering altogether?
   *
   * @return bool
   */
  public static function stalled()
  {
    if (self::unanswered() < self::TIMEOUT_LIMIT) {
      return false;
    }

    // The count on its own would shut Linet out for good if nothing ever got
    // through again - an order placed an hour later would have its document
    // refused without Linet ever being asked. So it holds only as long as the
    // wait the last unanswered call set, and after that one call is let
    // through to see whether Linet is back. That call either answers, which
    // clears the count, or does not, which sets a longer wait.
    $now = microtime(true);

    return self::read_backoff($now) > $now;
  }

  /**
   * Start a run with a clean count, so a run is never refused on the strength
   * of one that ended some time ago.
   */
  public static function start_run()
  {
    update_option(self::TIMEOUT_OPTION, 0, false);
  }

  /**
   * Is this the answer Linet sends when the minute's budget is spent?
   *
   * Linet reports it in its own envelope, {"status":429,"text":"",
   * "body":"Rate limit exceeded","errorCode":0}, so the envelope is what is
   * really checked here; the http status is checked too in case the transport
   * carries it as well.
   *
   * 503 only counts when it carries a Retry-After, otherwise it is an ordinary
   * outage and retrying straight away will not help.
   *
   * @param int         $code     Http status.
   * @param array       $response Raw wp_remote_* response.
   * @param object|null $decoded  The decoded envelope, when there was one.
   *
   * @return bool
   */
  public static function is_throttled($code, $response, $decoded = null)
  {
    if (429 === (int) $code) {
      return true;
    }

    if (503 === (int) $code && '' !== self::header($response, 'retry-after')) {
      return true;
    }

    if (!is_object($decoded)) {
      return false;
    }

    if (isset($decoded->status) && 429 === (int) $decoded->status) {
      return true;
    }

    // Belt and braces, in case the envelope ever carries the refusal without
    // the 429. A body that holds rows is an array, never a string, so there is
    // nothing here to collide with a real answer.
    return isset($decoded->body) &&
      is_string($decoded->body) &&
      false !== stripos($decoded->body, 'rate limit');
  }

  /**
   * Seconds asked for by Retry-After, which is either a delay or a http date.
   *
   * @param array $response
   *
   * @return float 0 when the header is missing or unreadable.
   */
  public static function retry_after($response)
  {
    $value = self::header($response, 'retry-after');

    if ('' === $value) {
      return 0.0;
    }

    if (is_numeric($value)) {
      return max(0.0, (float) $value);
    }

    $date = strtotime($value);

    if (false === $date) {
      return 0.0;
    }

    return max(0.0, (float) $date - time());
  }

  /**
   * Read a header off a wp_remote_* response.
   *
   * @param array  $response
   * @param string $name
   *
   * @return string
   */
  private static function header($response, $name)
  {
    if (is_wp_error($response) || !is_array($response)) {
      return '';
    }

    $value = wp_remote_retrieve_header($response, $name);

    if (is_array($value)) {
      $value = reset($value);
    }

    return is_string($value) ? trim($value) : '';
  }

  /**
   * How long to wait when Linet refused without a Retry-After header, which is
   * what it sends today.
   *
   * @param int $attempt Retries already made.
   *
   * @return float
   */
  private static function default_backoff($attempt)
  {
    $now = microtime(true);
    $window = self::read_window($now);
    $limit = self::limit();

    // This site's own count agrees that the budget is spent, so the honest
    // answer is the moment the oldest call ages out of the window.
    if ($limit > 0 && count($window) >= $limit) {
      return (float) min(max(($window[0] + self::WINDOW) - $now, self::BLIND_BACKOFF), self::WINDOW);
    }

    // Linet refused while this site still had budget left, so the two counts
    // disagree: another install on the same account, or requests this window
    // never saw. There is nothing to work the delay out from, so probe back
    // with a short escalating wait (5s, 10s, 20s) rather than parking every
    // process for a whole minute on a guess.
    $wait = self::BLIND_BACKOFF * pow(2, max(0, (int) $attempt));

    return (float) min($wait, self::WINDOW);
  }

  /**
   * The request times of the last minute, oldest first.
   *
   * @param float $now
   *
   * @return array
   */
  private static function read_window($now)
  {
    // Another process may have written since this one cached the option.
    wp_cache_delete(self::WINDOW_OPTION, 'options');

    $window = get_option(self::WINDOW_OPTION, array());

    if (!is_array($window)) {
      return array();
    }

    $oldest = $now - self::WINDOW;
    $fresh = array();

    foreach ($window as $time) {
      $time = (float) $time;

      // Drop anything expired, and anything from the future: a clock that has
      // been put back would otherwise block the site until it caught up.
      if ($time > $oldest && $time <= $now) {
        $fresh[] = $time;
      }
    }

    sort($fresh);

    return $fresh;
  }

  /**
   * @param array $window
   */
  private static function write_window($window)
  {
    update_option(self::WINDOW_OPTION, array_values($window), false);
  }

  /**
   * @param float $now
   *
   * @return float
   */
  private static function read_backoff($now)
  {
    wp_cache_delete(self::BACKOFF_OPTION, 'options');

    $until = (float) get_option(self::BACKOFF_OPTION, 0);

    // Again, ignore a value a clock change left far in the future.
    if ($until > $now + self::WINDOW + 60) {
      return 0.0;
    }

    return $until;
  }

  /**
   * Take a mysql named lock so the read/modify/write of the window is atomic
   * across php processes. Best effort: if the lock cannot be had the caller
   * still goes ahead, and the 429 handling catches the overshoot.
   *
   * @return string|false The lock name, for unlock().
   */
  private static function lock()
  {
    global $wpdb;

    if (!isset($wpdb)) {
      return false;
    }

    // Named locks are server wide, so key it to this install.
    $name = 'wc_linet_rate_' . substr(md5(DB_NAME . '|' . $wpdb->prefix), 0, 20);

    $suppress = $wpdb->suppress_errors(true);
    $got = $wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, %d)", $name, self::LOCK_TIMEOUT));
    $wpdb->suppress_errors($suppress);

    return ('1' === (string) $got) ? $name : false;
  }

  /**
   * @param string|false $name
   */
  private static function unlock($name)
  {
    if (!$name) {
      return;
    }

    global $wpdb;

    $suppress = $wpdb->suppress_errors(true);
    $wpdb->get_var($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $name));
    $wpdb->suppress_errors($suppress);
  }

}
