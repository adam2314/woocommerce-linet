<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 0.96
  Text Domain: wc-linet
  Domain Path: /languages/
  Requires WooCommerce: 2.2

  Copyright 2016  Adam Ben Hour

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
 * Required functions
 */
if (!function_exists('woothemes_queue_update')) {
    require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
//woothemes_queue_update(plugin_basename(__FILE__), 'f0dd29d338d3c67cf6cee88eddf6869b', '18733');

/**
 * Class WC_Linet
 * Main plugin class
 */
class WC_Linet {

    const VERSION = '0.96';

    /**
     * The constructor
     */
    public function __construct() {
        if (is_woocommerce_active() && version_compare(WC()->version, '2.2.0', '>=')) {
            $this->setup();
        } //else {
        //	add_action( 'admin_notices', array( $this, 'notice_wc_required' ) );
        //}
    }

    /**
     * Setup the class
     */
    public function setup() {

        // Setup the autoloader
        $this->setup_autoloader();

        // Load textdomain
        load_plugin_textdomain('wc-linet', false, dirname(plugin_basename(self::get_plugin_file())) . '/languages');

        // Setup Settings
        $settings = new WC_LI_Settings();
        $settings->setup_hooks();

        // Setup order actions
        $order_actions = new WC_LI_Order_Actions($settings);
        $order_actions->setup_hooks();

        // Setup Invoice hooks
        $invoice_manager = new WC_LI_Invoice_Manager($settings);
        $invoice_manager->setup_hooks();

        // Setup Payment hooks
        $payment_manager = new WC_LI_Payment_Manager($settings);
        $payment_manager->setup_hooks();





        // Plugins Links

        add_filter('plugin_action_links_' . plugin_basename(self::get_plugin_file()), array(
            $this,
            'plugin_links'
        ));
    }

    /**
     * Get the plugin file
     *
     * @static
     * @since  1.0.0
     * @access public
     *
     * @return String
     */
    public static function get_plugin_file() {
        return __FILE__;
    }

    /**
     * A static method that will setup the autoloader
     *
     * @static
     * @since  1.0.0
     * @access private
     */
    private function setup_autoloader() {
        require_once( plugin_dir_path(self::get_plugin_file()) . '/classes/class-wc-li-autoloader.php' );

        // Core loader
        $autoloader = new WC_LI_Autoloader(plugin_dir_path(self::get_plugin_file()) . 'classes/');
        spl_autoload_register(array($autoloader, 'load'));
    }

    /**
     * Admin error notifying user that WC is required
     */
    public function notice_wc_required() {
        ?>
        <div class="error">
            <p><?php _e('WooCommerce Linet Integration requires WooCommerce 2.2.0 or higher to be installed and activated!', 'wc-linet'); ?></p>
        </div>
        <?php
    }

    /**
     * Plugin page links
     *
     * @param array $links
     *
     * @return array
     */
    public function plugin_links($links) {

        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=woocommerce_linet') . '">' . __('Settings', 'wc-linet') . '</a>',
            '<a href="http://www.linet.org.il/support/">' . __('Support', 'wc-linet') . '</a>',
            //'<a href="http://docs.linet.org.il/document/linet/">' . __('Documentation', 'wc-linet') . '</a>',
        );

        return array_merge($plugin_links, $links);
    }

}

/**
 * Extension main function
 */
function __woocommerce_linet_main() {
    new WC_Linet();
}

// Initialize plugin when plugins are loaded
add_action('plugins_loaded', '__woocommerce_linet_main');
