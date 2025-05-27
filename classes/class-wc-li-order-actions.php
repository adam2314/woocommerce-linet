<?php

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
      $actions['linet_manual_invoice'] = __('Send Doc. to Linet', 'linet-erp-woocommerce-integration');

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