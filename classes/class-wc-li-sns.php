<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 2.6.9
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

class WC_LI_Sns {

    /**
     * @var WC_LI_Sns
     */

    public static function authMsg($msg,$logger){
      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL => $msg['SubscribeURL'],
        CURLOPT_RETURNTRANSFER => TRUE,
      ));

      $response = curl_exec($ch);
      curl_close($ch);
      return $response;

    }

    public static function updateItem($item_id,$logger){
        $params=WC_LI_Inventory::syncParams();
        unset($params['limit']);

        $params['id']=$item_id;

        $products = WC_LI_Settings::sendAPI(WC_LI_Inventory::syncStockURL(), $params);
        foreach($products->body as $item){
          WC_LI_Inventory::singleProdSync( $item,$logger);
        }
        unset($params['id']);
        $params['parent_item_id']=$item_id;
       $products = WC_LI_Settings::sendAPI(WC_LI_Inventory::syncStockURL(), $params);
       foreach($products->body as $item){
         WC_LI_Inventory::singleProdSync( $item,$logger);
       }
    }

    public static function updateCat($cat_id,$logger){

      $cats = WC_LI_Settings::sendAPI('search/itemcategory', array('id'=>$cat_id));
      foreach($cats->body as $cat){
        WC_LI_Inventory::singleCatSync($cat,$logger);
      }
    }


    public static function updateOrder($doc,$logger){
      $logger->write("updateOrder ". $doc['refstatus']);

      if($doc['refstatus']=="1"){

        $number=str_replace (__('Online Order', 'wc-linet')." #","",$doc['refnum_ext']);

        $order = new WC_Order($number);

        $sync_back_status= get_option('wc_linet_sync_back_status');
        $logger->write("sync_back_status: ". $sync_back_status);

        if($sync_back_status!='none'&&$sync_back_status!=''){

          $order->set_status($sync_back_status);

          $logger->write("save: ". $order->save());

        }

      }



    }


    public static function parsekMsg($msg,$logger){
      if($msg['Type']=='SubscriptionConfirmation' && isset($msg['SubscribeURL'])){
        return self::authMsg($msg,$logger);
      }


      if(isset($msg['Message'])){
        $data=explode("-",$msg['Message']);
        if(count($data)==3){
          update_option('wc_linet_last_sns', date('Y-m-d H:i:s'));

          if($data[1]=='\\app\\models\\Item')
            return self::updateItem((int)$data[2],$logger);
          if($data[1]=='\\app\\models\\Itemcategory')
            return self::updateCat((int)$data[2],$logger);
          if($data[1]=='\\app\\models\\Docs')
            return self::updateOrder((int)$data[2],$logger);
        }
      }

      return false;
    }



    public static function parseNextMsg($msg,$logger){


      if(isset($msg['MessageNext'])&&isset($msg['MessageNext']['title'])){
        $data=explode("-",$msg['MessageNext']['title']);
        if(count($data)==3){
          update_option('wc_linet_last_sns', date('Y-m-d H:i:s'));

          if($data[1]=='\\app\\models\\Item')
            return self::updateItem((int)$data[2],$logger);
          if($data[1]=='\\app\\models\\Itemcategory')
            return self::updateCat((int)$data[2],$logger);
          if($data[1]=='\\app\\models\\Docs')
            return self::updateOrder($msg['MessageNext']['model'],$logger);
        }
      }

      if(isset($msg['Message'])&&isset($msg['Message']['title'])){
        $data=explode("-",$msg['Message']['title']);
        if(count($data)==3){
          update_option('wc_linet_last_sns', date('Y-m-d H:i:s'));

          if($data[1]=='\\app\\models\\Item')
            return self::updateItem((int)$data[2],$logger);
          if($data[1]=='\\app\\models\\Itemcategory')
            return self::updateCat((int)$data[2],$logger);
          if($data[1]=='\\app\\models\\Docs')
            return self::updateOrder($msg['Message']['model'],$logger);
        }
      }


      return self::parsekMsg($msg,$logger);
    }

    public static function sync_item_by_linet_id( $data ) {
      $logger = new WC_LI_Logger(get_option('wc_linet_debug'));
      $logger->write("sns msg: " .file_get_contents("php://input"));

      $msg=json_decode(file_get_contents("php://input"),true);
      if(!is_null($msg)&&isset($msg['Type'])){
        self::parsekMsg($msg,$logger);
      }
      return 200;
    }

    public static function sync_by_linet( $data ) {
      $logger = new WC_LI_Logger(get_option('wc_linet_debug'));
      $logger->write("sns msg: " .file_get_contents("php://input"));

      $msg=json_decode(file_get_contents("php://input"),true);
      if(!is_null($msg)&&isset($msg['Type'])){
        self::parseNextMsg($msg,$logger);
      }
      return 200;
    }


    public function setup_hooks() {
      $autoSync= get_option('wc_linet_sync_items');
      if($autoSync=="sns"){
        add_action( 'rest_api_init', function () {
          register_rest_route( 'linet-fast-sync/v1', '/item', array(
            'methods' => 'POST',
            'callback' => 'WC_LI_Sns::sync_item_by_linet_id',
            'permission_callback' => '__return_true',
          ) );
        } );



        add_action( 'rest_api_init', function () {
          register_rest_route( 'linet-fast-sync/v2', '/sync', array(
            'methods' => 'POST',
            'callback' => 'WC_LI_Sns::sync_by_linet',
            'permission_callback' => '__return_true',
          ) );
        } );
      }
    }

}
