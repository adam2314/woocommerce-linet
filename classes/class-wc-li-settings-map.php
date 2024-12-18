<?php
/*
Plugin Name: WooCommerce Linet Integration
Plugin URI: https://github.com/adam2314/woocommerce-linet
Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
Author: Speedcomp
Author URI: http://www.linet.org.il
Version: 3.5.5
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

class WC_LI_Settings_Map
{

  // Settings defaults

  /* @var \WC_Order_Item $wpObj */

  static public function metaMap($linetObj, $wpObj, $optKey)
  {


    $meta_data = $wpObj->get_formatted_meta_data('_');


    if (!$meta_data)
      return $linetObj;

    $hidden_order_item_meta = apply_filters(
      'woocommerce_hidden_order_itemmeta',
      array(
        '_qty',
        '_tax_class',
        '_product_id',
        '_variation_id',
        '_line_subtotal',
        '_line_subtotal_tax',
        '_line_total',
        '_line_tax',
        'method_id',
        'cost',
        '_reduced_stock',
      )
    );
    foreach ($meta_data as $meta_id => $meta) {

      if (in_array($meta->key, $hidden_order_item_meta, true)) {
        continue;
      }

      $key = $meta->display_key;
      $value = force_balance_tags($meta->display_value);

      // 'is_sync_field' return linet field or false if not find
      if ($linet_field = WC_LI_Settings_Map::is_sync_field($key, $optKey)) {
        if (isset($linetObj[$linet_field]) && !empty($linetObj[$linet_field])) {
          $linetObj[$linet_field] .= ', ' . sanitize_text_field($value);
        } else {
          $linetObj[$linet_field] = sanitize_text_field($value);
        }
      }
    }

    return $linetObj;

  }


  static public function metaMapOrder($linetObj, $wpObj, $optKey)
  {

    $meta_data = get_post_meta($wpObj->get_id());

    if (!$meta_data)
      return $linetObj;

    //var_dump($wpObj);

    foreach ($meta_data as $meta_id => $meta) {
      //echo "$meta_id, $meta[0] \n";
      $key = $meta_id;
      $value = force_balance_tags($meta[0]);

      // 'is_sync_field' return linet field or false if not find
      if ($linet_field = WC_LI_Settings_Map::is_sync_field($key, $optKey)) {
        if (isset($linetObj[$linet_field]) && !empty($linetObj[$linet_field])) {
          $linetObj[$linet_field] .= ', ' . sanitize_text_field($value);
        } else {
          $linetObj[$linet_field] = sanitize_text_field($value);
        }
      }
    }

    return $linetObj;
  }

  static public function is_sync_field($name, $optKey)
  {

    $syncFields = get_option('wc_linet_' . $optKey); //not very efficent shuld be outside
    $obj = [
      'key' => $optKey,
      'fields' => $syncFields
    ];

    $obj = apply_filters('woocommerce_linet_sync_cf', $obj);
    $syncFields = $obj['fields'];

    if (isset($syncFields['wc_field']) && isset($syncFields['linet_field'])) {
      $syncWcFields = $syncFields['wc_field'];
      $syncLinetFields = $syncFields['linet_field'];

      if (count($syncWcFields) > 0 && in_array($name, $syncWcFields)) {
        return 'eav' . $syncLinetFields[array_search($name, $syncWcFields)];
      }
    }


    return false;

  }

}