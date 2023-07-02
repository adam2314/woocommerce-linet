<?php
/**
 * @package WC_Linet_Custom
 * @version 1.0.0
 */
/*
Plugin Name: WC Linet Custom
Plugin URI: http://
Description: Linet Custom Hooks Implmetion
Author: ABHW
Version: 1.0.0
Author URI: http://
*/

class WC_Linet_Custom
{
  const VERSION = '1.0.0';

  const MAP = [
    //5 => 'icons',??
    7 => '_soldby_unit_price',
    //yes,no
    8 => '_unit_price',
    9 => '_unit_sale_price',
    10 => '_soldby_weight_price',
    //yes,no
    //10 => '_weight_price',
    //11 => '_weight_sale_price',
    11 => 'e_d_dual_pricing', //yes,no





  ];

  public function __construct()
  {
    //add_action('wp_ajax_WpSingleProdSync', 'WC_Linet_Custom::singleProdAjax');
    //add_action('post_submitbox_start', 'WC_Linet_Custom::custom_button');

    add_filter('woocommerce_linet_item', 'WC_Linet_Custom::sync_cf_callback'); //item
    add_filter('woocommerce_linet_item_back', 'WC_Linet_Custom::linet_item_back'); //item

    add_filter('woocommerce_linet_set_order_line', 'WC_Linet_Custom::order_line_callback'); //docdets
    add_filter('woocommerce_linet_to_array', 'WC_Linet_Custom::body_callback'); //item

  }

  public static function body_callback($obj)
  {

    //var_dump($obj['doc']);exit;
    return $obj;
  }


  public static function order_line_callback($obj)
  {
    $metas = $obj['item']->get_meta_data();
    $name = $obj['detail']['name'];
    $post_id = $obj["item_id"];

    $qty = false;
    $unit = false;
    $addon_name = false;

    //var_dump($metas);
    //var_dump($obj['item']);

    foreach ($metas as $meta) {
      //if ($meta->key === "") {
      //  $qty = $meta->value;
      //}
      if ($meta->key === "יחידה") {
        $unit = $meta->value;
      }
    }

    //var_dump($obj['detail']['name']);
    //  var_dump($obj['item']->get_id());

    if ($unit == 'יחידות') {
      $addon_name = $obj['detail']['qty'] . "Xunit @" . ($obj['detail']['qty'] * $obj['detail']['iItem'] . "₪");
      $obj['detail']['qty'] = 1;

      $prod = $obj['item']->get_product();
      $weight_price = $prod->get_meta('_weight_price', true);
      $obj['detail']['iItem'] = $weight_price;
    }

    if ($addon_name)
      $obj['detail']['name'] .= " ($addon_name)";

    return $obj;
  }

  public static function linet_item_back($obj)
  {
    $post_id = $obj['wc_product']->get_id();
    $obj['body'] = [];

    foreach (self::MAP as $eav => $field) {
      $obj['body']['eav' . $eav] = $obj['wc_product']->get_meta($field, true);

    }

    $saleprice = $obj['wc_product']->get_meta('_weight_price', true);
    if ($saleprice) {
      $obj['body']['saleprice'] = $saleprice;
    }


    return $obj;
  }

  public static function sync_cf_callback($obj)
  {

    $post_id = $obj["item_id"];
    $item = $obj["linet_item"];
    $product = $obj["wc_product"];


    //$metas = $obj['wc_product']->get_meta_data();




    //var_dump($obj["linet_item"]->item->saleprice);exit;

    foreach (self::MAP as $eav => $field) {
      $perp = isset($item->properties->{$eav}) ? $item->properties->{$eav} : '';

      update_post_meta($post_id, $field, $perp);

    }

    update_post_meta($post_id, '_weight_price', $item->saleprice);



    return $obj;
  }

}

function __woocommerce_linet_custom()
{
  new WC_Linet_Custom();
}

// Initialize plugin when plugins are loaded
add_action('plugins_loaded', '__woocommerce_linet_custom');