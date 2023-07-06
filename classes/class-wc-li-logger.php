<?php
/*
Plugin Name: WooCommerce Linet Integration
Plugin URI: https://github.com/adam2314/woocommerce-linet
Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
Author: Speedcomp
Author URI: http://www.linet.org.il
Version: 3.3.4
Text Domain: wc-linet
Domain Path: /languages/
WC requires at least: 2.2
WC tested up to: 6.0
Copyright 2020  Adam Ben Hour
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
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
      $wc_logger->add('linet', $message);
    }
  }

}