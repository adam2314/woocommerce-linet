<?php
/*
Plugin Name: WooCommerce Linet Integration
Plugin URI: https://github.com/adam2314/woocommerce-linet
Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
Author: Speedcomp
Author URI: http://www.linet.org.il
Version: 3.6.1
Text Domain: linet-erp-woocommerce-integration
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

class WC_LI_Settings_Yith_Map
{

  // Settings defaults
  public $settings = array();

  public function __construct($override = null)
  {
    //get all settings
    global $wpdb;

    $items = $wpdb->get_results($wpdb->prepare("SELECT label,id FROM {$wpdb->prefix}yith_wapo_types WHERE 1"));

    foreach ($items as $itm) {
      //$decoded=json_decode($itm->meta_value);
      //if(count($decoded)>=1){
      //  foreach($decoded as $setting)
      $this->settings[] = array(
        'label' => $itm->label,
        //'id'=>$itm->ID,
        'name' => $itm->name,
        'elementId' => $itm->id
      );

    }
  }

  static public function metaMap($detail, $item)
  {
    $metas = $item->get_data()["meta_data"];
    foreach ($metas as $meta) {
      $metaData = $meta->get_data();
      if ($metaData['key'] == "_ywapo_meta_data") {
        foreach ($metaData['value'] as $prop) {
          $eav = get_option('wc_linet_ywapo' . $prop['type_id']);
          $detail[$eav] = $prop['value'];
        }
      }
    }

    return $detail;
  }

}