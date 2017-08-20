<?php

/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 0.95
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
    public function __construct(
        $settings=null,
        $contact=null,
        $date=null,
        $due_date=null,
        $invoice_number=null,
        $line_items=null,
        $currency_code=null,
        $total_tax=null,
        $total=null) {
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

        $genItm = get_option('wc_linet_genral_item');
        $warehouse = get_option('wc_linet_warehouse_id');
        //exit;

        foreach ($items as $item) {

            $this->doc['docDet'][] = [
                "item_id" => $this->getLinetItemId($item['product_id']), //getLinetId $item['product_id']
                "name" => html_entity_decode($item['name']),
                "description" => "",
                "qty" => $item['qty'],
                "currency_id" => "ILS",
                "currency_rate" => "1",
                "unit_id" => 0,
                "iTotalVat" => $item['line_total'],
                "warehouse_id"=>$warehouse,
            ];
            $total+=$item['line_total'];
        }

        $shipping_methods = [];
        foreach ($order->get_shipping_methods() as $method) {
            $shipping_methods[] = $method['method_id'];

            //var_dump(  $method['item_meta']['cost'][0]);


            $this->doc['docDet'][] = [
                "item_id" => $genItm, //getLinetId $item['product_id']
                "name" => html_entity_decode($order->get_shipping_method()),
                "description" => "",
                "qty" => 1,
                "currency_id" => "ILS",
                "currency_rate" => "1",
                "unit_id" => 0,
                "iTotalVat" => $order->order_shipping,


            ];

            $total+=  $order->order_shipping;
        }


        //_payment_method_title
        //_payment_method

        $this->doc["docCheq"] = [
            [
                "type" => 3,
                "currency_id" => "ILS",
                "currency_rate" => "1",
                "sum" => $total,
                "doc_sum" => $total,
                "line" => 1
            ]
        ];

        $doctype = get_option('wc_linet_linet_doc');

        if($doctype<>8 && $doctype<>9){
            unset($this->doc['docCheq']);
        }
        if($doctype==8  || $doctype==18){

          unset($this->doc['docDet']);
        }

        $this->total = $total;
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
        $linetSkuFind = get_option('wc_linet_sku_find');

        if ($linetSkuFind == 'on') {
            //

            $query = "SELECT `" . $prefix . "postmeta`.meta_value FROM `" . $prefix . "posts` LEFT JOIN `" . $prefix . "postmeta` ON `" . $prefix . "postmeta`.post_id=`" . $prefix .
                    "posts`.ID where `" . $prefix . "posts`.post_type='product' and `" . $prefix . "posts`.post_status = 'publish' and `" . $prefix .
                    "postmeta`.meta_key='_sku' and `" . $prefix . "posts`.id='" . $itemId . "';";

            $product_id = $wpdb->get_results($query);

            //echo "sku:" . $product_id[0]->meta_value;exit;
            if (count($product_id) != 0) {
                $res = WC_LI_Settings::sendAPI('search/item', ['sku' => $product_id[0]->meta_value]);
                if ($res->body != "No items where found for model") {
                    //echo "id(by sku):" . $res->body[0]->id;exit;
                    return $res->body[0]->id;
                }
            }
        } else {

            $query = "SELECT `" . $prefix . "postmeta`.meta_value FROM `" . $prefix . "posts` LEFT JOIN `" . $prefix . "postmeta` ON `" . $prefix . "postmeta`.post_id=`" . $prefix .
                    "posts`.ID where `" . $prefix . "posts`.post_type='product' and `" . $prefix . "posts`.post_status = 'publish' and `" . $prefix .
                    "postmeta`.meta_key='_linet_id' and `" . $prefix . "posts`.id='" . $itemId . "';";

            $product_id = $wpdb->get_results($query);

            //echo "id:". $product_id[0]->meta_value;exit;

            if (count($product_id) != 0) {
                return $product_id[0]->meta_value;
            }
        }
        //echo "id not found:". $itemId;  exit;
        $genItm = get_option('wc_linet_genral_item');
        return $genItm; //genrel item
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



//search by mail
        $accId = get_option('wc_linet_genral_acc');

        if ($accId == 0) {
            $accId = $this->getAcc($this->order->billing_email);
            if ($accId === false) {//create new acc
                $body = [
                    'name' => html_entity_decode($invoice_name),
                    "phone" => $this->order->billing_phone,
                    "address" => html_entity_decode($this->order->billing_address_1),
                    "city" => html_entity_decode($this->order->billing_city),
                    "email" => $this->order->billing_email,
                ];
                $accId = $this->createAcc($body);
            } else {//update acc
                $body = [
                    'name' => html_entity_decode($invoice_name),
                    "phone" => $this->order->billing_phone,
                    "address" => html_entity_decode($this->order->billing_address_1),
                    "city" => html_entity_decode($this->order->billing_city),
                    "email" => $this->order->billing_email,
                ];
                $this->updateAcc($accId, $body);
            }
        }//*/


        $doctype = get_option('wc_linet_linet_doc');

        //if((9!=$doctype)&&(8!=$doctype)){
        //    unset($this->doc["docCheq"]);
        //}


        $this->doc["doctype"] = $doctype;
        $this->doc["status"] = 2;
        $this->doc["account_id"] = $accId;
        //billing_country
        $this->doc["refnum_ext"] = 'Woocomerce Order #' . $this->order->id;
        $this->doc["phone"] = $this->order->billing_phone;
        $this->doc["address"] = html_entity_decode($this->order->billing_address_1);
        $this->doc["city"] = html_entity_decode($this->order->billing_city);
        $this->doc["email"] = $this->order->billing_email;
        $this->doc["company"] = html_entity_decode($invoice_name);
        $this->doc["currency_id"] = "ILS";
        $this->doc["currency_rate"] = "1";

        $this->doc["sendmail"] = 1;



        return $this->doc;
    }

}
