<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 2.1.6
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
        $products = WC_LI_Settings::sendAPI(WC_LI_Inventory::syncStockURL(), array('id'=>$item_id));
        foreach($products->body as $item){
          WC_LI_Inventory::singleProdSync( $item,$logger);
        }

       $products = WC_LI_Settings::sendAPI(WC_LI_Inventory::syncStockURL(), array('parent_item_id'=>$item_id));
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

    public static function parsekMsg($msg,$logger){
      if($msg['Type']=='SubscriptionConfirmation' && isset($msg['SubscribeURL'])){
        return self::authMsg($msg,$logger);
      }
      if(isset($msg['Message'])){
        $data=explode("-",$msg['Message']);
        if(count($data)==3){
          update_option('wc_linet_last_sns', date('Y-m-d H:i:s'));

          if($data[1]=='\\app\models\Item')
            return self::updateItem((int)$data[2],$logger);
          if($data[1]=='\\app\\models\\Itemcategory')
            return self::updateCat((int)$data[2],$logger);
        }
      }

      return false;
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


    public function setup_hooks() {
      $autoSync= get_option('wc_linet_sync_items');
      if($autoSync=="sns"){
        add_action( 'rest_api_init', function () {
          register_rest_route( 'linet-fast-sync/v1', '/item', array(
            'methods' => 'POST',
            'callback' => 'WC_LI_Sns::sync_item_by_linet_id',
          ) );
        } );
      }
    }

}
