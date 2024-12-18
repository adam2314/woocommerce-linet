<?php
/*
Plugin Name: WooCommerce Linet Integration
Plugin URI: https://github.com/adam2314/woocommerce-linet
Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
Author: Speedcomp
Author URI: http://www.linet.org.il
Version: 3.5.5
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

class WC_LI_Order_Actions
{

  /**
   * @var WC_LI_Settings
   */
  private $settings;

  /**
   * WC_LI_Order_Actions constructor.
   *
   * @param WC_LI_Settings $settings
   */
  public function __construct(WC_LI_Settings $settings)
  {
    $this->settings = $settings;
  }

  /**
   * Setup the required WooCommerce hooks
   */
  public function setup_hooks()
  {
    // Add order actions

    add_action('woocommerce_order_actions', 'WC_LI_Order_Actions::add_order_actions');

    //if $order->hasLinetDocId()
    // Catch order actions
    add_action('woocommerce_order_action_linet_manual_invoice', array($this, 'manual_invoice'));
  }

  /**
   * Add order actions
   *
   * @param array $actions
   *
   * @return array
   */
  public static function add_order_actions($actions)
  {

    // This should never happen but yeah let's check it anyway
    if (!is_array($actions)) {
      $actions = array();
    }

    $doctype = get_option('wc_linet_manual_linet_doc');

    if ((int)$doctype != 0)
      $actions['linet_manual_invoice'] = __('Send Doc. to Linet', 'wc-linet');

    return $actions;
  }

  /**
   * Handle the order actions callback for creating a manual invoice
   *
   * @param WC_Order $order
   *
   * @return boolean
   */
  public function manual_invoice($order)
  {

    // Invoice Manager
    $invoice_manager = new WC_LI_Invoice_Manager($this->settings);


    $doctype = get_option('wc_linet_manual_linet_doc');

    // Send Invoice
    $invoice_manager->send_invoice($order->get_id(), $doctype);

    return true;
  }
}