<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 2.6.5
  Text Domain: wc-linet
  Domain Path: /languages/
  WC requires at least: 2.2
  WC tested up to: 4.2.2

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
        //var_dump($this->to_array());exit;
        return WC_LI_Settings::sendAPI('create/doc', $this->to_array());
    }

    public function set_order($order) {
        $total = 0;

        $genItm = get_option('wc_linet_genral_item');
        $warehouse = get_option('wc_linet_warehouse_id');

        $one_item_order = get_option('wc_linet_one_item_order');

        $income_acc = get_option('wc_linet_income_acc');
        $income_acc_novat = get_option('wc_linet_income_acc_novat');
        $j5Token = get_option('wc_linet_j5Token');
        $j5Number = get_option('wc_linet_j5Number');

        $custom_product_addons=WC_Dependencies::check_custom_product_addons();

        $yith_woocommerce_product_add_ons=WC_Dependencies::check_yith_woocommerce_product_add_ons();


        if(!$income_acc)
          $income_acc = 100;
        if(!$income_acc_novat)
          $income_acc_novat = 102;

        $country_id = $order->get_billing_country();
        $currency_id = $order->get_currency();
        if($country_id==""){
          $country_id = "IL";
        }
        //exit;
        //var_dump($order);
        foreach ($order->get_items() as $item) {

          $product = wc_get_product( $item['product_id']);
          //var_dump($item);
          $one_item=(double)$item["total"]+(double)$item["total_tax"];
          $discount=(double)$item["subtotal"]-(double)$item["total"];


          if($item['qty']!=0){
            $one_item=round($one_item/$item['qty'],2);
            $discount=round($discount/$item['qty'],2);
          }

          if(isset($item['variation_id'])&&$item['variation_id']!=0){
            $item_id = self::getLinetItemId($item['variation_id']);
            $product = wc_get_product( $item['variation_id']);
          }  else{
            $item_id = self::getLinetItemId($item['product_id']);
          }


          $vat_cat=($country_id=="IL") ? 1 : 2;
          if($vat_cat===1 && $product && $product->get_tax_status()==='none'){
            $vat_cat=2;
          }

          //var_dump($vat_cat);
          //exit;

         $detail = array(
            "item_id" => $item_id, //getLinetId $item['product_id']
            "name" => html_entity_decode($item['name']),
            "description" => "",
            "qty" => $item['qty'],
            "currency_id" => $currency_id,
            //i need to get the rate from before!
            //"currency_rate" => "1",
            "vat_cat_id" => $vat_cat,
            "account_id" => ($vat_cat===1) ? $income_acc : $income_acc_novat,
            "unit_id" => 0,
            //"iTotalVat" => $item['line_total'],
            "iItem" => $one_item,
            "iItemWithVat" => 1,
            "warehouse_id" => $warehouse,
          );

          //if($custom_product_addons){
            $detail=WC_LI_Settings_Map::metaMap($detail,$item,"syncFields");
          //}


          $obj = [
            'detail' => $detail,
            'item' => $item,
            'order' => $order
          ];

          $obj = apply_filters( 'woocommerce_linet_set_order_line',   $obj  		);


          $this->doc['docDet'][] = $obj['detail'];

          $total += $one_item * $item['qty'];
        }


        //*
        if($one_item_order=='on'){
          $this->doc['docDet']=[
            [
              "item_id" => $genItm, //getLinetId $item['product_id']
              "name" =>  __('Online Order', 'wc-linet')." #" . $order->get_id(),
              "description" => "",
              "qty" => 1,
              "currency_id" => $currency_id,
              //"currency_rate" => "1",
              "vat_cat_id" => ($country_id=="IL") ? 1 : 2,
              "account_id" => ($country_id=="IL") ? $income_acc : $income_acc_novat,
              "unit_id" => 0,
              "iItem" => $total,
              "iItemWithVat" => 1
            ]
          ];
        }//*/

        foreach ($order->get_shipping_methods() as $method) {
          $shiping_price = (double)$method->get_total()+(double)$method->get_total_tax();

          $this->doc['docDet'][] = [
            "item_id" => $genItm, //getLinetId $item['product_id']
            "name" => html_entity_decode($method->get_method_title()),
            "description" => "",
            "qty" => ($shiping_price<0)?-1:1,
            "currency_id" => $currency_id,
            //"currency_rate" => "1",
            "vat_cat_id" => ($country_id=="IL") ? 1 : 2,
            "account_id" => ($country_id=="IL") ? $income_acc : $income_acc_novat,
            "unit_id" => 0,
            "iItem" => $shiping_price,
            "iItemWithVat" => 1
          ];
          $total += $shiping_price;
        }

        foreach ( $order->get_fees() as $fee) {
        //foreach ( WC_Cart::get_fees() as $fee) {
          $this->doc['docDet'][] = [
            "item_id" => $genItm, //getLinetId $item['product_id']
            "name" => html_entity_decode($fee['name']),
            "description" => "",
            "qty" => ($fee['total']<0)?-1:1,
            "currency_id" => $currency_id,
            //"currency_rate" => "1",
            "vat_cat_id" => ($country_id=="IL") ? 1 : 2,
            "account_id" => ($country_id=="IL") ? $income_acc : $income_acc_novat,
            "unit_id" => 0,
            "iItem" => abs($fee['total']),
            "iItemWithVat" => 1
          ];
          $total += $fee['total'];
        }


        //_payment_method_title
        //_payment_method
        //$Payment = new WC_LI_Payment(array($order,$currency_id,$total));
        //$this->doc["docCheq"] =$Payment->process($currency_id,$total);

        $rcpt = [
            "type" => 3,
            "currency_id" => $currency_id,
            //"currency_rate" => "1",
            "sum" => $total,
            "doc_sum" => $total,
            "line" => 1
          ];

        switch ($order->get_payment_method()) {
          case 'cod':
            $rcpt["type"]=1;

            break;

          case 'zcredit_payment':
          case 'zcredit_checkout_payment':

              $zc_response = get_post_meta( $order->get_id(), 'zc_response', true );
              $zc_response = $zc_response ? json_decode(unserialize(base64_decode($zc_response)),true) : array();

              if(isset($zc_response['Token'])){
                $rcpt['card_no']['value']=$zc_response['Token'];
                if($j5Token)
                  $this->doc[$j5Token]=$zc_response['Token'];
                if($j5Number)
                  $this->doc[$j5Number]=$zc_response['ReferenceNumber'];
              }
              if(isset($zc_response['ID'])){
                $rcpt['auth_number']['value']=$zc_response['ID'];
              }


            break;

          case 'creditguard':
            $metas = get_post_meta( $order->get_id());

            if(isset($metas['_cardMask'])&&isset($metas['_cardMask'][0])){
              $rcpt['last_4_digits']['value']=$metas['_cardMask'][0];
            }
            if(isset($metas['_authNumber'])&&isset($metas['_authNumber'][0])){
              $rcpt['auth_number']['value']=$metas['_authNumber'][0];
            }
            if(
              isset($metas['_numberOfPayments']) && isset($metas['_numberOfPayments'][0])
              //&& isset($metas['_firstPayment']) && isset($metas['_firstPayment'][0])
              //&& isset($metas['_periodicalPayment']) && isset($metas['_periodicalPayment'][0])
            ){
              $rcpt["type"]=1;
              $rcpt['paymentsNo']['value']=$metas['_numberOfPayments'][0];
            }
            break;
          case 'ppec_paypal':
          case 'paypal':
            $rcpt["type"]=8;
            break;
          case 'gotopay':
            break;
          case 'pelacard':
            break;
          default:
            break;
        }

        $this->doc["docCheq"] = [$rcpt];


        $doctype = get_option('wc_linet_linet_doc');



        if(in_array($doctype,[8,18])){
          unset($this->doc['docDet']);
        }
        if(!in_array($doctype,[8,9,18])){
          unset($this->doc['docCheq']);
        }

        $this->total = $total;
        $this->order = $order;

        $obj=[
          'doc' => $this->doc,
          'order' => $order
        ];

        $obj = apply_filters( 'woocommerce_linet_set_order',   $obj  		);


        $this->doc = $obj['doc'];
        //var_dump($this->doc);/**/
        //exit;
        return true;
    }

    public function get_total() {
        return $this->total;
    }

    private function getLinetItemId($id) {

        global $wpdb;

        $itemId = $wpdb->_real_escape($id);
        $linetSkuFind = get_option('wc_linet_sku_find');

        if ($linetSkuFind == 'on') {
            // " WHERE ($wpdb->posts.post_type='product' OR $wpdb->posts.post_type='product_variation') AND ".


            $query = "SELECT $wpdb->postmeta.meta_value FROM $wpdb->posts ".//bad
                  "LEFT JOIN $wpdb->postmeta ON  $wpdb->postmeta.post_id=$wpdb->posts.ID AND $wpdb->postmeta.meta_key='_sku'".
                  "WHERE ($wpdb->posts.post_type='product' OR $wpdb->posts.post_type='product_variation') ".
                  "AND $wpdb->posts.id=%s LIMIT 1;";

            $product_id= $wpdb->get_col($wpdb->prepare($query,$id));



            if (count($product_id) == 1) {
              $res = WC_LI_Settings::sendAPI('search/item', ['sku' => $product_id[0]]);
              if (is_array($res->body)) {
                //echo "id(by sku):" . $res->body[0]->id;exit;
                return $res->body[0]->id;
              }
            }
        } else {

          $query = "SELECT $wpdb->postmeta.meta_value FROM $wpdb->posts ".
                "LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=$wpdb->posts.ID AND $wpdb->postmeta.meta_key='_linet_id' ".
                "WHERE $wpdb->posts.id=%s LIMIT 1;";

          $product_id = $wpdb->get_col($wpdb->prepare($query,$id));

          if (count($product_id) == 1) {
            return $product_id[0];
          }
        }
        //echo "id not found:". $itemId;  exit;
        $genItm = get_option('wc_linet_genral_item');
        return $genItm; //genrel item
    }

    public function getAcc($email) {
        $res = WC_LI_Settings::sendAPI('search/account', ['email' => $email, 'type' => 0]);
        //var_dump($res);exit;
        if (is_array($res->body)) {
            return $res->body[0]->id;
        }
        return false;
    }

    public function updateAcc($id, $body) {

        $res = WC_LI_Settings::sendAPI('update/account?id=' . $id, $body);
        return ($res->status==200);
    }

    public function createAcc($body) {
        $body['type'] = 0;
        $res = WC_LI_Settings::sendAPI('create/account', $body);

        if($res->status==200){
          return $res->body->id;
        }
        return false;
    }

    /**
     * Format the invoice to XML and return the XML string
     *
     * @return string
     */
    public function to_array() {

        if (strlen($this->order->get_billing_company()) > 0) {
            $invoice_name = $this->order->get_billing_company();
        } else {
            $invoice_name = $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name();
        }


        //search by mail
        $accId = get_option('wc_linet_genral_acc');
        $country_id = $this->order->get_billing_country();
        $currency_id = $this->order->get_currency();

        if($country_id===""){
          $country_id = "IL";
        }



        if ($accId == 0) {
          $body = [
            'name' => html_entity_decode($invoice_name),
            "phone" => $this->order->get_billing_phone(),
            "address" => html_entity_decode($this->order->get_billing_address_1()),
            "city" => html_entity_decode($this->order->get_billing_city()),
            "country_id" => $country_id,
            "currency_id" => $currency_id,
            "email" => $this->order->get_billing_email(),
          ];

          $obj=[
            'acc' => $body,
            'order' => $this->order
          ];
          $obj = apply_filters( 'woocommerce_linet_account_dets',   $obj);
	        $body = $obj['acc'];


          $accId = $this->getAcc($this->order->get_billing_email());
          if ($accId === false) {//create new acc
            $accId = $this->createAcc($body);
          } else {//update acc
            $this->updateAcc($accId, $body);
          }
        }//*/


        $doctype = get_option('wc_linet_linet_doc');

        //if((9!=$doctype)&&(8!=$doctype)){
        //    unset($this->doc["docCheq"]);
        //}

        $status = (int)get_option('wc_linet_status');
        if($status == 0)
          $status = 2;
        //if ($doctype==2)
          //$status=1;

        $this->doc["doctype"] = $doctype;
        $this->doc["status"] = $status;
        $this->doc["account_id"] = $accId;
        //billing_country
        $this->doc["refnum_ext"] = __('Online Order', 'wc-linet')." #" . $this->order->get_id();
        $this->doc["phone"] = $this->order->get_billing_phone();
        $this->doc["address"] = html_entity_decode($this->order->get_billing_address_1());
        $this->doc["city"] = html_entity_decode($this->order->get_billing_city());
        $this->doc["email"] = $this->order->get_billing_email();
        $this->doc["company"] = html_entity_decode($invoice_name);
        $this->doc["currency_id"] = $currency_id;
        $this->doc["country_id"] = $country_id;
        $this->doc["language"] = ($country_id=='IL') ? "he_il" : "en_us";
        $this->doc["autoRound"] = false;

        $this->doc["comments"] = $this->order->get_customer_note();



        $printview = get_option('wc_linet_printview');
        $status = get_option('wc_linet_status');


        if($printview != '')
          $this->doc["view"] = $printview;
        //if($status!='')
        //  $this->doc["status"] = $status;

        //maybe will get rate...
        //$this->doc["currency_rate"] = "1";

        $this->doc["sendmail"] = (get_option('wc_linet_autosend')=='off')?0:1;

        $this->doc=WC_LI_Settings_Map::metaMapOrder($this->doc,$this->order,"orderFields");

        //var_dump($this->doc);
        //exit;
        return $this->doc;
    }

}
