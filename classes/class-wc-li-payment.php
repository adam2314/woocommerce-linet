<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 0.94
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

class WC_LI_Payment {

    private $invoice_id = '';
    private $code = '';
    private $date = '';
    private $currency_rate = '';
    private $amount = 0;
    private $order = null;

    /**
     * @return string
     */
    public function get_invoice_id() {
        return apply_filters('woocommerce_linet_payment_invoice_id', $this->invoice_id, $this);
    }

    /**
     * @param string $invoice_id
     */
    public function set_invoice_id($invoice_id) {
        $this->invoice_id = $invoice_id;
    }

    /**
     * @return string
     */
    public function get_code() {
        return apply_filters('woocommerce_linet_payment_code', $this->code, $this);
    }

    /**
     * @param string $code
     */
    public function set_code($code) {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function get_date() {
        return apply_filters('woocommerce_linet_payment_date', $this->date, $this);
    }

    /**
     * @param string $date
     */
    public function set_date($date) {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function get_currency_rate() {
        return apply_filters('woocommerce_linet_payment_currency_rate', $this->currency_rate, $this);
    }

    /**
     * @param string $currency_rate
     */
    public function set_currency_rate($currency_rate) {
        $this->currency_rate = $currency_rate;
    }

    /**
     * @return int
     */
    public function get_amount() {
        return apply_filters('woocommerce_linet_payment_amount', $this->amount, $this);
    }

    /**
     * @param int $amount
     */
    public function set_amount($amount) {
        $this->amount = floatval($amount);
    }

    /**
     * @return WC_Order
     */
    public function get_order() {
        return $this->order;
    }

    /**
     * @param WC_Order $order
     */
    public function set_order($order) {
        $this->order = $order;
    }


}
