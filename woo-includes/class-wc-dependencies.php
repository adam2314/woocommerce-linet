<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 3.6.3
  Text Domain: linet-erp-woocommerce-integration
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
/**
 * WC Dependency Checker
 *
 * Checks if WooCommerce is enabled
 */
class LI_WC_Dependencies {

  private static $active_plugins;

  public static function init() {

    self::$active_plugins = (array) get_option('active_plugins', array());

    if (is_multisite())
      self::$active_plugins = array_merge(self::$active_plugins, get_site_option('active_sitewide_plugins', array()));
  }

  public static function woocommerce_active_check() {

    if (!self::$active_plugins)
      self::init();

    return in_array('woocommerce/woocommerce.php', self::$active_plugins) || array_key_exists('woocommerce/woocommerce.php', self::$active_plugins);
  }

  public static function check_custom_product_addons()  {

    if (!self::$active_plugins)
      self::init();

    if (in_array('woo-custom-product-addons/start.php', apply_filters('active_plugins', get_option('active_plugins')))) {
      return true;
    }
    if (is_multisite()) {
      $plugins = get_site_option('active_sitewide_plugins');
      if (isset($plugins['woo-custom-product-addons/start.php']))
        return true;
    }
    return false;
  }

  public static function check_yith_woocommerce_product_add_ons()  {

    if (!self::$active_plugins)
      self::init();

    if (in_array('yith-woocommerce-product-add-ons/init.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        return true;
    }
    if (is_multisite()) {
        $plugins = get_site_option('active_sitewide_plugins');
        if (isset($plugins['yith-woocommerce-product-add-ons/init.php']))
            return true;
    }
    return false;
  }


}
