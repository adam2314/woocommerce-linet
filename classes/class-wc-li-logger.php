<?php

if (!defined('ABSPATH')) {
  exit;
} // Exit if accessed directly

class WC_LI_Logger
{

  /**
   * @var WC_LI_Settings
   */
  private $enabled;

  /**
   * WC_LI_Logger constructor.
   *
   * @param WC_LI_Settings $settings
   */
  public function __construct($enabled)
  {
    $this->enabled = $enabled;
  }

  /**
   * Check if logging is enabled
   *
   * @return bool
   */
  public function is_enabled()
  {

    // Check if debug is on
    if ('on' === $this->enabled) {
      return true;
    }

    return false;
  }

  /**
   * Write the message to log
   *
   * @param String $message
   */
  public function write($message)
  {

    // Check if enabled
    if ($this->is_enabled()) {

      // Logger object
      $wc_logger = new WC_Logger();

      // Add to logger
      $wc_logger->add('linet', self::readable((string) $message));
    }
  }

  /**
   * Make a message readable before it goes into the log file.
   *
   * Attribute values, term slugs and taxonomy names all pass through
   * sanitize_title(), which percent-encodes everything that is not plain
   * ascii. That is the form Linet has to receive, but it turns a Hebrew log
   * into lines like "WpItemSync %d7%9e%d7%99%d7%93%d7%94", so the escapes are
   * decoded on the way out.
   *
   * Only runs of escapes that decode to non-ascii text are touched, so a %20
   * or %2F inside a logged url stays exactly as it was sent.
   *
   * @param String $message
   *
   * @return String
   */
  public static function readable($message)
  {
    $message = html_entity_decode((string) $message, ENT_QUOTES, 'UTF-8');

    if (false === strpos($message, '%')) {
      return $message;
    }

    return preg_replace_callback(
      '/(?:%[0-9a-fA-F]{2})+/',
      function ($match) {
        $decoded = rawurldecode($match[0]);

        // Valid utf-8, and nothing that was readable to begin with.
        if (preg_match('//u', $decoded) && !preg_match('/[\x00-\x7F]/', $decoded)) {
          return $decoded;
        }

        return $match[0];
      },
      $message
    );
  }

}