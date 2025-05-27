<?php

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