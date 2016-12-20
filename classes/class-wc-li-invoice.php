<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 0.7
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

class WC_LI_Invoice {

    /**
     * @var WC_LI_Settings
     */
    public $settings;
    public $contact;
    public $date;
    public $due_date;
    public $invoice_number;
    public $line_items;
    public $currency_code;
    public $total_tax;
    public $total;
    //public $settings;
    private $order;
    private $doc = [];

    /**
     * Construct
     *
     * @param WC_LI_Settings $settings
     * @param WC_LI_Contact $contact
     * @param string $date
     * @param string $due_date
     * @param string $invoice_number
     * @param array $line_items
     * @param string $currency_code
     * @param float $total_tax
     * @param float $total
     */
    public function __construct($settings, $contact, $date, $due_date, $invoice_number, $line_items, $currency_code, $total_tax, $total) {
        //var_dump($settings);exit;
        $this->settings = $settings;
        $this->contact = $contact;
        $this->date = $date;
        $this->due_date = $due_date;
        $this->invoice_number = $invoice_number;
        $this->line_items = $line_items;
        $this->currency_code = $currency_code;
        $this->total_tax = $total_tax;
        $this->total = $total;

        //add_filter('woocommerce_linet_invoice_due_date', array($this, 'set_org_default_due_date'), 10, 2);
    }

    public function do_request() {
        //update linetDocId
        
        //var_dump('aa');exit;
        return WC_LI_Settings::sendAPI('create/doc', $this->to_array());
    }

    public function set_order($order) {
        $items = $order->get_items();
        //var_dump($items);
        $total = 0;

        foreach ($items as $item) {

            $this->doc['docDet'][] = [
                "item_id" => $this->getLinetItemId($item['product_id']), //getLinetId $item['product_id']
                "name" => $item['name'],
                "description" => "",
                "qty" => $item['qty'],
                "currency_id" => "ILS",
                "unit_id" => 0,
                "iTotalVat" => $item['line_total'],
            ];
            $total+=$item['line_total'];
        }
        //_payment_method_title
        //_payment_method

        $this->doc["docCheq"] = [
            [
                "type" => 1,
                "currency_id" => "ILS",
                "sum" => $total,
                "doc_sum" => $total,
                "line" => 1
            ]
        ];


        $this->order = $order;
        return true;
    }

    public function get_total() {

        return $this->total;
    }

    private function getLinetItemId($id) {

        global $wpdb;
        $prefix = $wpdb->_real_escape($wpdb->prefix);

        $itemId = $wpdb->_real_escape($id);


        $query = "SELECT * FROM `" . $prefix . "posts` LEFT JOIN `" . $prefix . "postmeta` ON `" . $prefix . "postmeta`.post_id=`" . $prefix .
                "posts`.ID where `" . $prefix . "posts`.post_type='product' and `" . $prefix . "posts`.post_status = 'publish' and `" . $prefix .
                "postmeta`.meta_key='_linet_id' and `" . $prefix . "postmeta`.meta_value='" . $itemId . "';";
        //}
        $product_id = $wpdb->get_results($query);

        if (count($product_id) != 0) {
            return $product_id[0]->ID;
        }

        return 1; //genrel item
    }

    public function getAcc($email) {

        $res = WC_LI_Settings::sendAPI('search/account', ['email' => $email, 'type' => 0]);

        //var_dump($res);exit;
        if ($res->body != "No items where found for model") {
            return $res->body[0]->id;
        }

        return false;
    }

    public function updateAcc($id, $body) {

        $res = WC_LI_Settings::sendAPI('update/account?id=' . $id, $body);
        //if(count($res->body)!=0){
        //	return $res->body[0]->id;
        //}
        //var_dump($res);exit;
        return true;
    }

    public function createAcc($body) {
        $body['type'] = 0;
        $res = WC_LI_Settings::sendAPI('create/account', $body);
        //if(count($res->body)!=0){
        //	return $res->body[0]->id;
        //}
        //var_dump($res);exit;
        return $res->body->id;
    }

    /**
     * Format the invoice to XML and return the XML string
     *
     * @return string
     */
    public function to_array() {

        if (strlen($this->order->billing_company) > 0) {
            $invoice_name = $this->order->billing_company;
        } else {
            $invoice_name = $this->order->billing_first_name . ' ' . $this->order->billing_last_name;
        }

        $body = [
            "doctype" => 3,
            "status" => 2,
            "account_id" => 113,
            "company" => "Just a Test",
            "currency_id" => "ILS",
        ];


//search by mail
        $accId = get_option('wc_linet_genral_acc');

        if ($accId == 0) {
            $accId = $this->getAcc($this->order->billing_email);
            if ($accId === false) {//create new acc
                $body = [
                    'name' => $invoice_name,
                    "phone" => $this->order->billing_phone,
                    "address" => $this->order->billing_address_1,
                    "city" => $this->order->billing_city,
                    "email" => $this->order->billing_email,
                ];
                $accId = $this->createAcc($body);
            } else {//update acc
                $body = [
                    'name' => $invoice_name,
                    "phone" => $this->order->billing_phone,
                    "address" => $this->order->billing_address_1,
                    "city" => $this->order->billing_city,
                    "email" => $this->order->billing_email,
                ];
                $this->updateAcc($accId, $body);
            }
        }//*/




        $this->doc["doctype"] = 9;
        $this->doc["status"] = 2;
        $this->doc["account_id"] = $accId;
        //billing_country
        $this->doc["refnum_ext"] = 'Woocomerce Order #' . $this->order->id;
        $this->doc["phone"] = $this->order->billing_phone;
        $this->doc["address"] = $this->order->billing_address_1;
        $this->doc["city"] = $this->order->billing_city;
        $this->doc["email"] = $this->order->billing_email;
        $this->doc["company"] = $invoice_name;
        $this->doc["currency_id"] = "ILS";


        $this->doc["sendmail"] = 1;



        return $this->doc;
    }

}
