<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 3.1.1
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

class WC_LI_Payment_Manager {

    /**
     * @var WC_LI_Settings
     */
    private $settings;

    /**
     * WC_LI_Payment_Manager constructor.
     *
     * @param WC_LI_Settings $settings
     */
    public function __construct(WC_LI_Settings $settings) {
        $this->settings = $settings;
    }

    public function setup_hooks() {
        // Check if we need to send payments when they're completed automatically
        /*
        $sendPayments = get_option('wc_linet_send_payments');
        if ('on' === $sendPayments) {
            add_action('woocommerce_order_status_completed', array($this, 'send_payment'));
        }

        add_filter('woocommerce_linet_order_payment_date', array($this, 'cod_payment_set_payment_date_as_current_date'), 10, 2);
         *
         *
         */
    }

    /**
     * Send the payment to the LINET API
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function send_payment($order_id) {

        // Get the order
        $order = wc_get_order($order_id);

        if (!get_post_meta($order->get_id(), '_linet_invoice_id', true)) {
            $order->add_order_note(__('Linet Payment not created: Invoice has not been sent.', 'wc-linet'));
            return false;
        }

        // Payment Request
        $payment_request = new WC_LI_Request_Payment($this->settings, $this->get_payment_by_order($order));

        // Write exception message to log
        $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

        // Logging start
        $logger->write('START LINET NEW PAYMENT. order_id=' . $order->get_id());

        // Try to do the request
        try {
            // Do the request
            $payment_request->do_request();

            // Parse XML Response
            $xml_response = $payment_request->get_response_body_xml();

            // Check response status
            if ('OK' == $xml_response->Status) {

                // Add post meta
                update_post_meta($order->id, '_linet_payment_id', (string) $xml_response->Payments->Payment[0]->PaymentID);

                // Write logger
                $logger->write('LINET RESPONSE:' . "\n" . $payment_request->get_response_body());

                // Add order note
                $order->add_order_note(__('Linet Payment created.  ', 'wc-linet') .
                        ' Payment ID: ' . (string) $xml_response->Payments->Payment[0]->PaymentID);
            } else { // XML reponse is not OK
                // Logger write
                $logger->write('LINET ERROR RESPONSE:' . "\n" . $payment_request->get_response_body());

                // Error order note
                $error_num = (string) $xml_response->ErrorNumber;
                $error_msg = (string) $xml_response->Elements->DataContractBase->ValidationErrors->ValidationError->Message;
                $order->add_order_note(__('ERROR creating Linet payment. ErrorNumber:' . $error_num . '| Error Message:' . $error_msg, 'wc-linet'));
            }
        } catch (Exception $e) {
            // Add Exception as order note
            $order->add_order_note($e->getMessage());

            $logger->write($e->getMessage());

            return false;
        }

        // Logging end
        $logger->write('END LINET NEW PAYMENT');

        return true;
    }

    /**
     * Get payment by order
     *
     * @param WC_Order $order
     *
     * @return WC_LI_Payment
     */
    public function get_payment_by_order($order) {

        // Get the LINET invoice ID
        $invoice_id = get_post_meta($order->get_id(), '_linet_invoice_id', true);

        // Get the LINET currency rate
        $currency_rate = get_post_meta($order->get_id(), '_linet_currencyrate', true);

        // Date time object of order data
        $order_dt = new DateTime($order->order_date);

        // The Payment object
        $payment = new WC_LI_Payment();

        $payment->set_order($order);

        // Set the invoice ID
        $payment->set_invoice_id($invoice_id);

        // Set the Payment Account code
        //no need
        //$payment->set_code( $this->settings->get_option( 'payment_account' ) );
        // Set the payment date
        $payment->set_date(apply_filters('woocommerce_linet_order_payment_date', $order_dt->format('Y-m-d'), $order));

        // Set the currency rate
        $payment->set_currency_rate($currency_rate);

        // Set the amount
        $payment->set_amount($order->order_total);

        return $payment;
    }

    /**
     * If the payment gateway is set to COD, set the payment date as the current date instead of the order date.
     */
    public function cod_payment_set_payment_date_as_current_date($order_date, $order) {
        $payment_method = !empty($order->payment_method) ? $order->payment_method : '';
        if ('cod' !== $payment_method) {
            return $order_date;
        }
        return date('Y-m-d', time());
    }

}
