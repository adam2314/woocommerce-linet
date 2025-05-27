<?php

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
