<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 0.93
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
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class WC_LI_Invoice_Manager {

    /**
     * @var WC_LI_Settings
     */
    private $settings;

    /**
     * WC_LI_Invoice_Manager constructor.
     *
     * @param WC_LI_Settings $settings
     */
    public function __construct(WC_LI_Settings $settings) {
        $this->settings = $settings;
    }

    /**
     * Method to setup the hooks
     */
    public function setup_hooks() {

        // Check if we need to send invoices when they're completed automatically
        $sendInv = get_option('wc_linet_sync_orders');
        //$option = $this->settings->get_option('send_invoices');
        if ('completed' === $sendInv) {
            //add_action('woocommerce_order_status_processing', array($this, 'send_invoice'));
        //} elseif ('completion' === $sendPayments || 'on' === $sendPayments) {
            add_action('woocommerce_order_status_completed', array($this, 'send_invoice'));
        }elseif('processing' === $sendInv) {
            add_action('woocommerce_order_status_processing', array($this, 'send_invoice'));
        }else{//none...


        }
    }

    /**
     * Send invoice to LINET API
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function send_invoice($order_id) {

        // Get the order
        $order = wc_get_order($order_id);
        $supported_gateways=$this->settings->get_option('supported_gateways');
        if(!in_array($order->payment_method, $supported_gateways)){
            $order->add_order_note(__("LINET: Will not create doc. unsupported gateway", 'wc-linet'));
            return false;
            //echo $order->payment_method;exit;
        }
        //print_r();

        //$order->payment_method//if type==



        // Get the invoice
        $invoice = $this->get_invoice_by_order($order);

        // Write exception message to log
        $logger = new WC_LI_Logger($this->settings);



        //var_dump($order);
        //var_dump($invoice->to_array());
        //exit;
        // Check if the order total is 0 and if we need to send 0 total invoices to Linet
        if (0 == $invoice->get_total() && 'on' !== $this->settings->get_option('export_zero_amount')) {
            //if ( 0 == $invoice->get_total() && 'on' !== $this->settings->get_option( 'export_zero_amount' ) ) {
            $logger->write('INVOICE HAS TOTAL OF 0, NOT SENDING ORDER WITH ID ' . $order->id);

            $order->add_order_note(__("LINET: Didn't create doc. because total is 0 and send order with zero total is set to off.", 'wc-linet'));

            return false;
        }

        // Invoice Request
        //$invoice_request = new WC_LI_Request_Invoice( $this->settings, $invoice );
        // Logging
        $logger->write('START LINET NEW doc. order_id=' . $order->id);

        // Try to do the request
        try {
            // Do the request
            $json_response = $invoice->do_request();

            //var_dump($json_response);
            //exit;

            // Check response status
            if ('200' == $json_response->status) {

                // Add order meta data
                update_post_meta($order->id, '_linet_invoice_id', (string) $json_response->body->id);
                update_post_meta($order->id, '_linet_currency_rate', (string) $json_response->body->currency_rate);

                // Log response
                $logger->write('LINET RESPONSE:' . "\n" .print_r($json_response,true));

                // Add Order Note
                $order->add_order_note(__('Linet Doc. created.  ', 'wc-linet') . ' Doc. num: ' . (string) $json_response->body->docnum);
            } else { // XML reponse is not OK
                // Log reponse
                $logger->write('LINET ERROR RESPONSE:' . "\n" . print_r($json_response,true));

                // Format error message
                //$error_message = $xml_response->Elements->DataContractBase->ValidationErrors->ValidationError->Message ? $xml_response->Elements->DataContractBase->ValidationErrors->ValidationError->Message : __('None', 'wc-linet');

                // Add order note
                $order->add_order_note(__('ERROR creating Linet doc: ', 'wc-linet') .
                        __(' ErrorNumber: ', 'wc-linet') . $json_response->status .
                        __(' ErrorType: ', 'wc-linet') . $json_response->errorCode .
                        __(' Message: ', 'wc-linet') . $json_response->text .

                        __(' Detail: ', 'wc-linet') . $json_response->body);
            }
        } catch (Exception $e) {
            // Add Exception as order note
            $order->add_order_note($e->getMessage());

            $logger->write($e->getMessage());

            return false;
        }

        $logger->write('END LINET NEW doc.');

        return true;
    }

    /**
     * Get invoice by order
     *
     * @param WC_Order $order
     *
     * @return WC_LI_Invoice
     */
    public function get_invoice_by_order($order) {

        // Date time object of order data
        $order_dt = new DateTime($order->order_date);

        // Line Item manager
        //$line_item_manager = new WC_LI_Line_Item_Manager( $this->settings );
        // Contact Manager
        //$contact_manager = new WC_LI_Contact_Manager( $this->settings );
        // Create invoice
        $invoice = new WC_LI_Invoice(
                //$this->settings,
                //'',//$contact_manager->get_contact_by_order( $order ),
                //$order_dt->format( 'Y-m-d' ),
                //$order_dt->format( 'Y-m-d' ),
                //'',//ltrim( $order->get_order_number(), '#' ),
                //'',//$line_item_manager->build_line_items( $order ),
                //'',//$order->get_order_currency(),
                //'',//round( ( floatval( $order->order_tax ) + floatval( $order->order_shipping_tax ) ), 2 ),
                //$order->order_total
                );

        $invoice->set_order($order);

        // Return invoice
        return $invoice;
    }

}
