<?php

if (!defined('ABSPATH')) {
  exit;
} // Exit if accessed directly

/**
 * Notes on what has already been pushed to Linet, so a sync does not spend its
 * 60 calls a minute asking the same questions again.
 *
 * Only for things that are the same for every product: an attribute ruler and
 * its units are global in Linet, but linetSaveRuler() runs once per product, so
 * a push of a catalogue looks up the same "Size" ruler and the same twelve
 * sizes for every single item.
 *
 * Entries carry a timestamp and are re-checked once the ttl runs out, which is
 * what heals anything deleted at the Linet end. Concurrent pulses merge on
 * write, so two ajax pulses cannot wipe each other's notes.
 */
class WC_LI_Sync_Cache
{

  const OPTION = 'wc_linet_sync_cache';

  /** How long a note is trusted, in seconds (30 days). */
  const TTL = 2592000;

  /** Entries kept before the oldest are dropped. */
  const KEEP = 2000;

  private static $entries = null;

  private static $dirty = false;

  /**
   * @return int Seconds, 0 to turn the cache off.
   */
  public static function ttl()
  {
    return (int) apply_filters('woocommerce_linet_sync_cache_ttl', self::TTL);
  }

  /**
   * Is this already known, and recent enough to trust?
   *
   * @param string $group
   * @param string $signature
   *
   * @return bool
   */
  public static function known($group, $signature)
  {
    return null !== self::get($group, $signature);
  }

  /**
   * The value stored for a note, or null when there is none to trust.
   *
   * @param string $group
   * @param string $signature
   *
   * @return mixed|null
   */
  public static function get($group, $signature)
  {
    $ttl = self::ttl();

    if ($ttl <= 0) {
      return null;
    }

    $entries = self::load();
    $key = self::key($group, $signature);

    if (!isset($entries[$key]) || !is_array($entries[$key])) {
      return null;
    }

    $entry = $entries[$key];
    $when = isset($entry['t']) ? (int) $entry['t'] : 0;

    if ((time() - $when) >= $ttl) {
      return null;
    }

    return isset($entry['v']) ? $entry['v'] : true;
  }

  /**
   * Note something that is now known to be in Linet. Held in memory until
   * flush(), so a run that touches a hundred units writes the option once.
   *
   * @param string $group
   * @param string $signature
   * @param mixed  $value
   */
  public static function remember($group, $signature, $value = true)
  {
    self::load();

    self::$entries[self::key($group, $signature)] = array('v' => $value, 't' => time());
    self::$dirty = true;
  }

  /**
   * Write the notes back, merged with whatever another process stored while
   * this one was working.
   */
  public static function flush()
  {
    if (!self::$dirty) {
      return;
    }

    wp_cache_delete(self::OPTION, 'options');

    $stored = get_option(self::OPTION, array());

    if (!is_array($stored)) {
      $stored = array();
    }

    // This process has the fresher answer for anything it looked up itself.
    $entries = array_merge($stored, self::$entries);

    $ttl = self::ttl();
    $cut = time() - max($ttl, 0);

    foreach ($entries as $key => $entry) {
      if (!is_array($entry) || !isset($entry['t']) || (int) $entry['t'] < $cut) {
        unset($entries[$key]);
      }
    }

    if (count($entries) > self::KEEP) {
      uasort($entries, array(__CLASS__, 'byAge'));
      $entries = array_slice($entries, -self::KEEP, null, true);
    }

    update_option(self::OPTION, $entries, false);

    self::$entries = $entries;
    self::$dirty = false;
  }

  /**
   * @return array
   */
  private static function load()
  {
    if (null === self::$entries) {
      $entries = get_option(self::OPTION, array());
      self::$entries = is_array($entries) ? $entries : array();
    }

    return self::$entries;
  }

  /**
   * @return int
   */
  public static function byAge($a, $b)
  {
    $at = is_array($a) && isset($a['t']) ? (int) $a['t'] : 0;
    $bt = is_array($b) && isset($b['t']) ? (int) $b['t'] : 0;

    return $at - $bt;
  }

  /**
   * @return string
   */
  private static function key($group, $signature)
  {
    return $group . ':' . $signature;
  }

}
