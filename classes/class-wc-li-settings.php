<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 3.1.0
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

class WC_LI_Settings {

  const OPTION_PREFIX = 'wc_linet_';
  const SERVER = "https://app.linet.org.il";
  const DEV_SERVER = "https://dev.linet.org.il";
  //const DEV_SERVER = "http://10.8.0.6:8123";

  const STOCK_LIMIT = 50;
  const RUNTIME_LIMIT = 21;

  // Settings defaults
  private $settings = array();
  private $override = array();





  public function __construct($override = null) {

    //add_action('init', 'WC_LI_Settings::StartSession', 1);
    //add_action('wp_logout', 'WC_LI_Settings::EndSession');
    //add_action('wp_login', 'WC_LI_Settings::EndSession');



    add_action('linetItemSync', 'WC_LI_Inventory::fullSync');

    add_action('wp_ajax_LinetGetFile', 'WC_LI_Settings::LinetGetFile');
    add_action('wp_ajax_LinetDeleteFile', 'WC_LI_Settings::LinetDeleteFile');
    add_action('wp_ajax_LinetDeleteProd', 'WC_LI_Settings::LinetDeleteProd');

    add_action('wp_ajax_LinetDeleteAttachment', 'WC_LI_Settings::LinetDeleteAttachment');
    add_action('wp_ajax_LinetCalcAttachment', 'WC_LI_Settings::LinetCalcAttachment');


    add_action('wp_ajax_LinetTest', 'WC_LI_Settings::TestAjax');

    add_action('wp_ajax_RulerAjax', 'WC_LI_Settings::RulerAjax');



    add_action('wp_ajax_LinetItemSync', 'WC_LI_Inventory::catSyncAjax');//linet to wp all prod

    add_action('wp_ajax_LinetSingleItemSync', 'WC_LI_Inventory::singleSyncAjax');//linet to wp
    add_action('wp_ajax_LinetSingleProdSync', 'WC_LI_Inventory::singleProdAjax');//wp to linet


    add_action('wp_ajax_LinetCatList', 'WC_LI_Inventory::CatListAjax');

    add_action('wp_ajax_WpItemSync', 'WC_LI_Inventory::WpItemsSyncAjax');
    add_action('wp_ajax_WpCatSync', 'WC_LI_Inventory::WpCatSyncAjax');

    //add_filter('woocommerce_get_settings_pages',array($this,'add_woocomerce_settings_tab'))
    if (!is_null($override)) {
      $this->override = $override;
    }
}



public function orderOptions(){
  return array(

    'one_item_order' => array(
      'title' => __('One Item Order', 'wc-linet'),
      'default' => 'off',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Remove all items from linet doc and make one item only', 'wc-linet'),
    ),


    'autosend' => array(
      'title' => __('Mail Document', 'wc-linet'),
      'default' => 'on',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Autosend document in mail', 'wc-linet'),
    ),

    'genral_acc' => array(
      'title' => __('General Custemer Account', 'wc-linet'),
      'default' => '0',
      'type' => 'text',
      'description' => __('Enter 0 for auto create account', 'wc-linet'),
    ),

    'j5Token' => array(
      'title' => __('J5 Token EAV field', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('J5 Token field sync {eavX}', 'wc-linet'),
    ),
    'j5Number' => array(
      'title' => __('J5 Reference Number EAV field', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('J5 Reference Number field sync {eavX}', 'wc-linet'),
    ),

    'genral_item' => array(
      'title' => __('General Item', 'wc-linet'),
      'default' => '1',
      'type' => 'text',
      'description' => __('Code for Linet general Item ', 'wc-linet'),
    ),

    'income_acc' => array(
      'title' => __('Income Account', 'wc-linet'),
      'default' => '100',
      'type' => 'text',
      'description' => __('Income Account', 'wc-linet'),
    ),

    'income_acc_novat' => array(
      'title' => __('Income Account No VAT', 'wc-linet'),
      'default' => '102',
      'type' => 'text',
      'description' => __('Income Account No VAT', 'wc-linet'),
    ),

    'printview' => array(
      'title' => __('Document Print View', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('Document Print View.', 'wc-linet'),
    ),

    'status' => array(
      'title' => __('Document status', 'wc-linet'),
      'default' => '2',
      'type' => 'text',
      'description' => __('Document status.', 'wc-linet'),
    ),

    'orderFields' => array(
      'title' => __('Custom Order Fields', 'wc-linet'),
      'default' => '',
      'type' => 'repeater_text',
      'description' => __('Linet Custom Field ID (eav{N}) for auto syncd products.', 'wc-linet'),
    ),
  );
}
public function lineOptions(){
  return array(
    'syncFields' => array(
      'title' => __('Custom Field ID (TEST)', 'wc-linet'),
      'default' => '',
      'type' => 'repeater_text',
      'description' => __('Linet Custom Field ID (eav{N}) for auto syncd products.', 'wc-linet'),
    ),
  );
}

public function syncOptions(){

  $statuses = array('none' => __('Manually', 'wc-linet'));
  foreach(wc_get_order_statuses() as $key=>$name){
    $statuses[str_replace("wc-","",$key)]=__($name, 'wc-linet');
  }

  $array = array(

    'sku_find' => array(
      'title' => __('SKU Find', 'wc-linet'),
      'default' => 'off',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Find Linet items by SKU and not there Item ID', 'wc-linet'),
    ),

    'global_attr' => array(
      'title' => __('Global attributes', 'wc-linet'),
      'default' => 'off',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('use global attributes for variable products', 'wc-linet')
      .'<a style="" href="#target1" class="button-primary" onclick="linet.doRuler();">Write Global Rulers</a> '
      ,
    )
    );

    foreach(wc_get_order_statuses() as $key=>$name){
      $skey = str_replace("wc-","",$key);
      $statuses[$skey] = __($name, 'wc-linet');

      $array["sync_orders_$key"]= array(
        'title' => __('Sync Orders On'.' '.$name, 'wc-linet'),
        'default' => 'none',
        //type' => 'checkbox',
        'type' => 'select',
        'options' => array(
          '' => __('None', 'wc-linet'),
          '1' => __('Performa', 'wc-linet'),
          '2' => __('Delivery Doc.', 'wc-linet'),
          '3' => __('Invoice', 'wc-linet'),

          '7' => __('Sales Order', 'wc-linet'),
          '8' => __('Receipt', 'wc-linet'),
          '9' => __('Invoice Receipt', 'wc-linet'),
          '17' => __('Stock Exist Doc.', 'wc-linet'),
          '18' => __('Donation Receipt', 'wc-linet'),
        ),
        'description' => __('Auto Genrate Doc in Linet', 'wc-linet'),
      );


    }

  return $array+array(

    'manual_linet_doc'=>array(
        'title' => __('Sync Orders Manual', 'wc-linet'),
        'default' => 'none',
        //type' => 'checkbox',
        'type' => 'select',
        'options' => array(
  	'' => __('None', 'wc-linet'),
        	'1' => __('Performa', 'wc-linet'),
          '2' => __('Delivery Doc.', 'wc-linet'),
          '3' => __('Invoice', 'wc-linet'),

          '7' => __('Sales Order', 'wc-linet'),
          '8' => __('Receipt', 'wc-linet'),
          '9' => __('Invoice Receipt', 'wc-linet'),
          '17' => __('Stock Exist Doc.', 'wc-linet'),
          '18' => __('Donation Receipt', 'wc-linet'),

        ),
        'description' => __('Auto Genrate Doc in Linet', 'wc-linet'),
      ),

    'sync_back_status' => array(
      'title' => __('sync back order status', 'wc-linet'),
      'default' => 'none',
      //type' => 'checkbox',
      'type' => 'select',
      'options' => $statuses,
      'description' => __('will change order stauts after action in linet', 'wc-linet'),
    ),

    'supported_gateways' => array(
      'title' => __('Supported Gateways', 'wc-linet'),
      'default' => '',
      //type' => 'checkbox',
      'type' => 'pay_list',
      'description' => __('Select Gateways to invoice', 'wc-linet'),
    ),
    'stock_manage' => array(//out
      'title' => __('Stock Manage', 'wc-linet'),
      'default' => 'on',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Use Linet to sync the stock level of items', 'wc-linet'),
    ),
    'only_stock_manage' => array(
      'title' => __('Only Stock Manage', 'wc-linet'),
      'default' => 'off',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Will update only stock levels and not other details', 'wc-linet'),
    ),

    'pricelist_account' => array(
      'title' => __('Pricelist Custemer ID', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('custemer id for a spical pricelist for the site', 'wc-linet'),
    ),


    'sync_items' => array(
      'title' => __('Sync Items', 'wc-linet'),
      'default' => 'on',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
        'sns'  => __( 'SNS - Select Only With Linet Support!', 'wc-linet' ),
      ),

      'description' => __('Manual Items Sync:', 'wc-linet') .
      ' <br /><button type="button" id="linwc-btn" class="button-primary" onclick="linet.fullItemsSync();">Linet->WC</button>' .
      ' <br /><button type="button" id="wclin-btn" class="button" onclick="linet.fullProdSync();">WC->Linet</button>' .
      "<div id='mItems' class='hidden'>" .
      '
      <div id="target"></div>
      <progress id="targetBar" max="100" value="0"></progress>
      <div id="subTarget"></div>
      <input text="hidden" id="subTargetBar" value="0" />' .
      "</div>"
      ,
    ),
    'syncField' => array(
      'title' => __('Custom Field ID (Product)', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('Linet Custom Field ID (eav{N}) for auto syncd products', 'wc-linet'),
    ),
    'syncValue' => array(
      'title' => __('Custom Field Value (Product)', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('Linet Custom Field Value for auto syncd products', 'wc-linet'),
    ),
    'syncCatField' => array(
      'title' => __('Custom Field ID (Category)', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('Linet Custom Field ID (eav{N}) for auto syncd categories', 'wc-linet'),
    ),
    'syncCatValue' => array(
      'title' => __('Custom Field Value (Category)', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('Linet Custom Field Value for auto syncd categories', 'wc-linet'),
    ),
    'picsync' => array(
      'title' => __('Picture Sync', 'wc-linet'),
      'default' => 'on',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Will sync Pictures', 'wc-linet'),
    ),
    'rect_img' => array(
      'title' => __('Rectangular Picture', 'wc-linet'),
      'default' => 'off',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Will force Rectangular Pictures', 'wc-linet'),
    ),


    'not_product_attributes' => array(
      'title' => __('No Product Attributes', 'wc-linet'),
      'default' => 'off',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Do not write product_attributes meta data', 'wc-linet'),
    ),


    'warehouse_id' => array(
      'title' => __('Warehouse', 'wc-linet'),
      'default' => '115',
      'type' => 'text',
      'description' => __('Warehouse ID from Linet', 'wc-linet'),
    ),

    'warehouse_exclude' => array(
      'title' => __('Warehouse exclude', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('Warehouse ID from Linet you can write a list with commas(,)', 'wc-linet'),
    ),

    'warehouse_stock_count' => array(
      'title' => __('Stock Count Warehouse', 'wc-linet'),
      'default' => 'on',
      'type' => 'select',
      'options' => array(
        'off' => __( 'All company Warehouses', 'wc-linet' ),
        'on'  => __( 'The same warehouse', 'wc-linet' ),
      ),
      'description' => __('Stock Count Warehouse', 'wc-linet'),
    ),

    'itemFields' => array(
      'title' => __('Custom Item Fields', 'wc-linet'),
      'default' => '',
      'type' => 'repeater_text',
      'description' => __('Linet Custom Field ID (eav{N}) for auto syncd products.', 'wc-linet'),
    ),
  );
}


public static function LinetGetFile($name){
  $name = str_replace("/","",str_replace("..","",$_POST['name']));
  echo file_get_contents(WC_LOG_DIR.$name);
  wp_die();
}


public static function LinetDeleteFile($name){
  $name = str_replace("/","",str_replace("..","",$_POST['name']));
  echo unlink(WC_LOG_DIR.$name);
  wp_die();
}

public static function LinetDeleteProd($id){
  global $wpdb;

  $key = $_POST['key'];
  $value = $_POST['value'];

  $query = "SELECT post_id FROM $wpdb->postmeta where meta_key=%s AND meta_value=%s";
  $posts = $wpdb->get_col($wpdb->prepare($query,$key,$value));

  $first=true;
  foreach ($posts as $key => $post_id) {
    if($first){
      $first=false;
    }else{
      $product=wc_get_product($post_id);
      if(!empty($product))
          echo $product->delete(true);

      echo delete_post_meta($post_id,"_linet_id");
      echo delete_post_meta($post_id,"_sku");
      echo wc_delete_product_transients($post_id);
    }

  }

  wp_die();
}



public static function LinetDeleteAttachment($id){
  $id =(int)$_POST['id'];

  wp_delete_attachment($id);
}

public static function LinetCalcAttachment($id){
  $id =(int)$_POST['id'];
  $pic =(int)$_POST['file'];

  $basePath = wp_upload_dir()['basedir'].'/';
  $realtivePath = WC_LI_Inventory::IMAGE_DIR."/".$pic;
  $filePath = $basePath.$realtivePath;

  wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$filePath));
}



public function form(){
  global $wpdb;

  $arr=array();

  $args = array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1);

	if( $data = get_posts($args) ){
    foreach($data as $form) {
      $arr["cf7".$form->ID]= array(
        'title' => __('CF7:', 'wc-linet')." ".$form->post_title,
        'default' => '',
        'type' => 'cf7_text',
        'payload' => array('form_id'=>$form->ID )
        //'description' => __('Login ID  retrieved from <a href="http://app.linet.org.il" target="_blank">Linet</a>.', 'wc-linet'),
      );
    }
  }

  $arr["elementor_form"]= array(
    'title' => __('elementor form map', 'wc-linet'),
    'default' => '',
    'type' => 'elementor_text',
    //'payload' => array('form_id'=>1),
    'description' => __('map form by name and field id', 'wc-linet'),
  );



/*
  */


  return $arr;
}




public function maintenance(){
  global $wpdb;

  $arr = array();

  $query = "SELECT post_id,meta_value ,count(meta_value) as num FROM $wpdb->postmeta where meta_key='_sku' GROUP by meta_value HAVING num>1";
  $products = $wpdb->get_results($query);
  foreach($products as $index=>$product){
    $arr['sku'.$index]= array(
      'title' => __('duplicate sku', 'wc-linet')." <br /><a data-key='_sku' data-value='$product->meta_value' onclick=\"linet.deleteProd(this);\" href='#'>Delete</a>",
      'default' => '',
      'type' => 'none',
      'description' => $product->post_id." ".$product->meta_value." ".$product->num,
    );
  }

  $query = "SELECT post_id,meta_value ,count(meta_value) as num FROM $wpdb->postmeta where meta_key='_linet_id' GROUP by meta_value HAVING num>1";
  $products = $wpdb->get_results($query);
  foreach($products as $index => $product){
    $arr['linet_id'.$index] = array(
      'title' => __('duplicate linet_id', 'wc-linet')." <br /><a data-key='_linet_id' data-value='$product->meta_value' onclick=\"linet.deleteProd(this);\" href='#'>Delete</a>",
      'default' => '',
      'type' => 'none',
      'description' => $product->post_id." ".$product->meta_value." ".$product->num,
    );
  }

  $query = "SELECT ID,post_title,meta_value FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON post_id=ID AND meta_key = '_wp_attachment_metadata' where post_type='attachment' AND meta_value is null";
  $attachments = $wpdb->get_results($query);
  foreach($attachments as $index=>$attachment){
    $arr['attachment'.$index]= array(
      'title' => __('attachment metadata missing', 'wc-linet')." <br /><a data-id='$attachment->ID' onclick=\"linet.deleteAttachment(this);\" href='#'>Delete</a>",
      'default' => '',
      'type' => 'none',
      'description' => "<a data-id='$attachment->ID' data-file='$attachment->post_title' onclick=\"linet.calcAttachment(this);\" href='#'>$attachment->post_title</a>",
    );
  }
  $scanned_directory = array();
  if(is_dir(WC_LOG_DIR)){
    $scanned_directory = array_diff(scandir(WC_LOG_DIR), array('..', '.'));

  }

  $text=array();
  foreach($scanned_directory as $index=>$file){
    if(strpos($file,'linet')===0 ||strpos($file,'fatal-errors')===0 )
      $arr['file'.$index]= array(
        'title' => __('Log File', 'wc-linet')."<br /><a data-name='$file'  onclick=\"linet.deleteFile(this);\" href='#'>Delete</a>",
        'default' => '',
        'type' => 'none',
        'description' => "<a  onclick=\"linet.getFile('$file');\" href='#'>$file</a>",
      );
  }

  return $arr;
}


public function connectionOptions(){


  return array(
    'consumer_id' => array(
      'title' => __('ID', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('Login ID  retrieved from <a href="http://app.linet.org.il" target="_blank">Linet</a>.', 'wc-linet'),
    ),
    'consumer_key' => array(
      'title' => __('Key', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('Key retrieved from <a href="http://app.linet.org.il" target="_blank">Linet</a>.', 'wc-linet'),
    ),
    'company' => array(
      'title' => __('Company', 'wc-linet'),
      'default' => '1',
      'type' => 'text',
      'description' => __('Company id', 'wc-linet'),
    ),
    'last_update' => array(
      'title' => __('Last Update Time', 'wc-linet'),
      'default' => '',
      'type' => 'text',
      'description' => __('Last Update Time ', 'wc-linet'),
      'options' => array(
        'readonly' => true,
      )
    ),
    'last_sns' => array(
      'title' => __('Last Message Time', 'wc-linet'),
      'default' => '',
      'type' => 'none',
      'description' => self::get_option("last_sns"),

    ),
    'debug' => array(
      'title' => __('Debug', 'wc-linet'),
      'default' => 'off',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Enable logging.  Log file is located at:', 'wc-linet')." ".WC_LOG_DIR,
    ),
    'dev' => array(
      'title' => __('Dev Mode', 'wc-linet'),
      'default' => 'off',
      'type' => 'select',
      'options' => array(
        'off' => __( 'Off', 'wc-linet' ),
        'on'  => __( 'On', 'wc-linet' ),
      ),
      'description' => __('Will work aginst the dev server', 'wc-linet'),
    ),
  );
}



public static function StartSession() {
  if (!session_id()) {
    session_start();
  }
}

public static function EndSession() {
  session_destroy();
}

/**
* Setup the required settings hooks
*/
public function setup_hooks() {
  add_action('admin_init', array($this, 'register_settings'));

  add_action('admin_menu', array($this, 'add_menu_item'));

  add_action('post_submitbox_start', array($this, 'custom_button'));
  add_action('post_submitbox_start', array($this, 'custom_button_wp'));


  add_action('product_cat_edit_form_fields',  array($this, 'custom_term_button'));

}


function custom_term_button($term) {
  $taxonomy=$term->taxonomy;
    $types=['product_cat'];
    if(in_array($taxonomy,$types)){
      $metas=get_term_meta($term->term_id);
       ?>
       Linet Cat ID: <?=isset($metas['_linet_cat'])&&$metas['_linet_cat']['0']?$metas['_linet_cat']["0"]:"No Linet ID"?><br />
       Linet Last Upate: <?=isset($metas['_linet_last_update'])&&$metas['_linet_last_update']['0']?$metas['_linet_last_update']["0"]:"unkown"?><br />

      <?php
    }
}

function custom_button($post) {
    $types=['product'];
    if(in_array(get_post_type($post),$types)){
      $metas=get_post_meta($post->ID);
       ?>
      <script>
        var linet = {
          singleSync:function(post_id){
            jQuery.post(ajaxurl, {
              'action': 'LinetSingleItemSync',
              'post_id': post_id
              //'post_id': jQuery(this).data("post_id")
            }, function (response) {
              alert(response.status);
              location.reload();
            }, 'json');
          }
        }
      </script>
      Linet ID: <?=isset($metas['_linet_id'])&&$metas['_linet_id']['0']?$metas['_linet_id']["0"]:"No Linet ID"?><br />
      Linet Last Upate: <?=isset($metas['_linet_last_update'])&&$metas['_linet_last_update']['0']?$metas['_linet_last_update']["0"]:"unkown"?><br />

<a class="button" data-post_id="<?=$post->ID;?>" onclick="linet.singleSync(<?=$post->ID;?>);">Sync Item From Linet</a>
      <?php
    }
}

function custom_button_wp($post) {
  $types = ['product'];
  if(in_array(get_post_type($post),$types)){
    //$metas = get_post_meta($post->ID);
    ?>
    <script>
      var wlinet={
        singleProdSync:function(post_id){
          jQuery.post(ajaxurl, {
            'action': 'LinetSingleProdSync',
            'post_id': post_id
            //'post_id': jQuery(this).data("post_id")
          }, function (response) {
            alert(response.status);
            location.reload();
          }, 'json');
        }
      }
    </script>
    <a class="button" data-post_id="<?=$post->ID;?>" onclick="wlinet.singleProdSync(<?=$post->ID;?>);">
      Sync Item To Linet 
    </a>
    <?php
  }
}

/**
* Get an option
*
* @param $key
*
* @return mixed
*/
public function get_option($key) {

  if (isset($this->override[$key])) {
    return $this->override[$key];
  }

  $default='';
  if(isset($this->settings[$key]) && isset($this->settings[$key]['default']))
    $default=$this->settings[$key]['default'];
  return get_option(self::OPTION_PREFIX . $key, $default);

}

/**
* settings_init()
*
* @access public
* @return void
*/
public function register_settings() {

  //self::fullItemsSync();
  // Add section
  add_settings_section('wc_linet_settings',
  __('Linet Settings', 'wc-linet'),
   array(
    $this,
    'settings_intro'
  ), 'woocommerce_linet');
  $this->settings=array_merge(
    $this->orderOptions(),
    $this->lineOptions(),
    $this->syncOptions(),
    $this->connectionOptions(),
    //$this->maintenance(),
    $this->form()
  );


  //here we display the sections and options in the settings page based on the active tab
   if(isset($_GET["tab"]))  {
       if($_GET["tab"] == "order-options")     {
         $this->renderOptTab($this->orderOptions());
       }
       if($_GET["tab"] == "line-options")     {
         $this->renderOptTab($this->lineOptions());

         /*
         if(LI_WC_Dependencies::check_custom_product_addons()){
           $settingsMap = new WC_LI_Settings_Map();
           foreach($settingsMap->settings as $key => $setting){
             add_settings_field(self::OPTION_PREFIX . $setting['name']. $key, $setting['label'], array(
               $this,
               'input_text'
             ), 'woocommerce_linet', 'wc_linet_settings', array('key' => $setting['name'], 'option' => $setting));
             register_setting('woocommerce_linet', self::OPTION_PREFIX . $setting['name']);
           }
         }

         if(LI_WC_Dependencies::check_yith_woocommerce_product_add_ons()){
           $settingsMap = new WC_LI_Settings_Yith_Map();
           foreach($settingsMap->settings as $key => $setting){
             add_settings_field(self::OPTION_PREFIX .'ywapo'. $setting['elementId']. $key, $setting['label'], array(
               $this,
               'input_text'
             ), 'woocommerce_linet', 'wc_linet_settings', array('key' =>'ywapo'. $setting['elementId'], 'option' => $setting));
             register_setting('woocommerce_linet', self::OPTION_PREFIX .'ywapo'. $setting['elementId']);
           }
         }

         */

       }
       if($_GET["tab"] == "sync-options")    {
         $this->renderOptTab($this->syncOptions());
       }
       if($_GET["tab"] == "connection-options")    {
         $this->renderOptTab($this->connectionOptions());
       }

       if($_GET["tab"] == "maintenance")    {
         $this->renderOptTab($this->maintenance());
       }

       if($_GET["tab"] == "form")    {
         $this->renderOptTab($this->form());
       }

   } else  {
     $this->renderOptTab($this->connectionOptions());
   }


   //$this->renderOptTab($this->settings);


}

public function renderOptTab($settings){
  // Add setting fields
  foreach ($settings as $key => $option) {

    // Add setting fields
    add_settings_field(self::OPTION_PREFIX . $key, $option['title'], array(
      $this,
      'input_' . $option['type']
    ), 'woocommerce_linet', 'wc_linet_settings', array('key' => $key, 'option' => $option));

    // Register setting
    register_setting('woocommerce_linet', self::OPTION_PREFIX . $key);
  }
}

/**
* Add menu item
*
* @return void
*/
public function add_menu_item() {
  $sub_menu_page = add_submenu_page('woocommerce', __('Linet', 'wc-linet'), __('Linet', 'wc-linet'), 'manage_woocommerce', 'woocommerce_linet', array(
    $this,
    'options_page'
  ));

  add_action('load-' . $sub_menu_page, array($this, 'enqueue_style'));


}

public function enqueue_style() {
  global $woocommerce;
  wp_enqueue_style('woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css');
}

/**
* The options page
*/
public function options_page() {


  $autoSync= get_option('wc_linet_sync_items');

  $login_id = get_option('wc_linet_consumer_id');
  $hash = get_option('wc_linet_consumer_key');
  $company = get_option('wc_linet_company');


  if($autoSync=='on' && $login_id!=''&& $hash!=''&& $company!=''){
    if (!wp_next_scheduled('linetItemSync')) {
      wp_schedule_event(time(), 'hourly', 'linetItemSync');
    }
  }else{
    wp_clear_scheduled_hook( 'linetItemSync' );
  }
  $status=wp_cache_get( 'linet_fullSync_status', 'linet' );
  //var_dump($status);exit;
  //adam:sync
  //wp_clear_scheduled_hook( 'linetItemSync' );
  //wp_schedule_event(time(), 'hourly', 'linetItemSync');
  $income_acc_novat = get_option('wc_linet_income_acc_novat');

  $active_tab = "connection-options";
  if(isset($_GET["tab"])) {
     if($_GET["tab"] == "order-options")
       $active_tab = "order-options";
     if($_GET["tab"] == "line-options")
       $active_tab = "line-options";
     if($_GET["tab"] == "sync-options")
       $active_tab = "sync-options";
     if($_GET["tab"] == "maintenance")
       $active_tab = "maintenance";
     if($_GET["tab"] == "form")
       $active_tab = "form";

 }

  ?>
  <div class="wrap woocommerce">
    <form method="post" id="mainform" action="options.php?tab=<?= $active_tab;?>">
      <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br/></div>
      <h2><?php _e('Linet for WooCommerce', 'wc-linet'); ?></h2>

      <?php
      if (isset($_GET['settings-updated']) && ( $_GET['settings-updated'] == 'true' )) {
        echo '<div id="message" class="updated fade"><p><strong>' . __('Your settings have been saved.', 'wc-linet') . '</strong></p></div>';
      } else if (isset($_GET['settings-updated']) && ( $_GET['settings-updated'] == 'false' )) {
        echo '<div id="message" class="error fade"><p><strong>' . __('There was an error saving your settings.', 'wc-linet') . '</strong></p></div>';
      }
      ?>

      <?php
      if ($status &&
        isset($status['running'])&&
        $status['running'] &&
        isset($status['start'])&&
        isset($status['offset'])

      ) {

        echo '<div id="backgroundSync" class="error fade"><p><strong>' . __('background sync is rununing started/syncd', 'wc-linet').$status['start']."/" .$status['offset']. '</strong></p></div>';
      }
      ?>


      <a href="#target1" class="button-primary" onclick="linet.doTest();">Test Connection</a> (You can Check The Connection Only After Saving)


      <h2 class="nav-tab-wrapper">
          <!-- when tab buttons are clicked we jump back to the same page but with a new parameter that represents the clicked tab. accordingly we make it active -->
          <a href="?page=woocommerce_linet&tab=connection-options" class="nav-tab <?php if($active_tab == 'connection-options'){echo 'nav-tab-active';} ?> "><?php _e('Connection Options', 'sandbox'); ?></a>
          <a href="?page=woocommerce_linet&tab=order-options" class="nav-tab <?php if($active_tab == 'order-options'){echo 'nav-tab-active';} ?>"><?php _e('Order Options', 'sandbox'); ?></a>
          <a href="?page=woocommerce_linet&tab=line-options" class="nav-tab <?php if($active_tab == 'line-options'){echo 'nav-tab-active';} ?>"><?php _e('Line Options', 'sandbox'); ?></a>
          <a href="?page=woocommerce_linet&tab=sync-options" class="nav-tab <?php if($active_tab == 'sync-options'){echo 'nav-tab-active';} ?>"><?php _e('Sync Options', 'sandbox'); ?></a>
          <a href="?page=woocommerce_linet&tab=maintenance" class="nav-tab <?php if($active_tab == 'maintenance'){echo 'nav-tab-active';} ?>"><?php _e('Maintenance', 'sandbox'); ?></a>
          <a href="?page=woocommerce_linet&tab=form" class="nav-tab <?php if($active_tab == 'form'){echo 'nav-tab-active';} ?>"><?php _e('Form', 'sandbox'); ?></a>


      </h2>

      <?php settings_fields('woocommerce_linet'); ?>
      <?php do_settings_sections('woocommerce_linet'); ?>

      <p class="submit"><input type="submit" class="button-primary" value="Save"/></p>
      <script>
      linet = {
        catDet: function (response) {
          jQuery('#catValue' + response.id).html(response.wc_count + "/" + response.linet_count);
        },


        deleteAttachment: function (obj) {
          var id = jQuery(obj).data('id');
          jQuery(obj).parent().parent().hide();

          var data = {
            'action': 'LinetDeleteAttachment',
            'id': id
          };
          jQuery.post(ajaxurl, data, function (response) {

          });
          return false;
        },
        calcAttachment: function (obj) {
          var id = jQuery(obj).data('id');
          var file = jQuery(obj).data('file');

          jQuery(obj).parent().parent().hide();

          var data = {
            'action': 'LinetCalcAttachment',
            'file': file,
            'id': id
          };
          jQuery.post(ajaxurl, data, function (response) {

          });
          return false;
        },

        deleteProd: function (obj) {
          var key = jQuery(obj).data('key');
          var value = jQuery(obj).data('value');
          jQuery(obj).parent().parent().hide();

          var data = {
            'action': 'LinetDeleteProd',
            'key': key,
            'value': value
          };

          jQuery.post(ajaxurl, data, function (response) {

          });
          return false;
        },
        deleteFile: function (obj) {
          var name = jQuery(obj).data('name');
          jQuery(obj).parent().parent().hide();
          var data = {
            'action': 'LinetDeleteFile',
            'name': name
          };
          jQuery.post(ajaxurl, data, function (response) {

          });
          return false;
        },
        getFile: function (name) {
          var data = {
            'action': 'LinetGetFile',
            'name': name
          };
          jQuery.post(ajaxurl, data, function (response) {
            var blob=new Blob([response]);
               var link=document.createElement('a');
               link.href=window.URL.createObjectURL(blob);
               link.download=name;
               link.click();
          });
          return false;
        },


        doTest: function () {
          var data = {
            'action': 'LinetTest',
            //'mode': 1
          };


          jQuery.post(ajaxurl, data, function (response) {
            //console.log(response);
            //console.log(response);
            alert(response.text);
            //count

          }, 'json');


        },


        doRuler: function () {
          var data = {
            'action': 'RulerAjax',
            //'mode': 1
          };


          jQuery.post(ajaxurl, data, function (response) {
            //console.log(response);
            //console.log(response);
            alert(response);
            //count

          }, 'json');


        },

        fullProdSync: function () {
          //event.preventDefault();
          var data = {
            'action': 'WpItemSync',
            'mode': 0
          };
          jQuery('#mItems').removeClass('hidden');
          jQuery.post(ajaxurl, data, function (response) {
            jQuery('#target').html("Items:  0/" + response );
            jQuery('#targetBar').prop('max',response);
            linet.timeoutErrorCount = 0;
            if (response){
              linet.prodSync(0);

            }
          });
          return false
        },


        prodSync: function (offset) {
          var data = {
            'action': 'WpItemSync',
            'offset': offset,
            'mode': 1
          };
          //max = jQuery('#targetBar').attr("max");

          clearTimeout(linet.resumeTimeOut);

          linet.resumeTimeOut = setTimeout(
            ()=>{
              linet.prodSync(offset);
              linet.timeoutErrorCount++
            },1000*60
          )

          num = jQuery('#targetBar').prop('max');


         
          jQuery.post(ajaxurl, data, function (response) {
            //console.log(response);
            bar = offset + response*1;

            jQuery('#target').html("Items:  " + bar + "/" + num );
            jQuery('#targetBar').val(bar);
            //jQuery('#subTarget').html("Items: 0" );

            if (num-bar > 0)
              linet.prodSync(bar);
            //jQuery('#subTargetBar').attr("max", 1 * response);
            //linet.subCall(num - 1, 1);
            //count
          });
        },






        getList: function () {
          var data = {
            'action': 'LinetCatList',
            //'mode': 1
          };

          jQuery.post(ajaxurl, data, function (response) {
            jQuery('#catList').html("");
            for (i = 0; i < response.body.length; i++) {
              jQuery('#catList').append("<li>" + response.body[i].name + " <span id='catValue" + response.body[i].id + "'></span></li>");

              jQuery.post(ajaxurl, {
                'action': 'WpCatSync',
                'id': response.body[i].id,
                'catName': response.body[i].name
              }, function (response) {
                linet.catDet(response);

              }, 'json');

            }

          }, 'json');

        },

        fullItemsSync: function () {
          //event.preventDefault();

          var data = {
            'action': 'LinetItemSync',
            'mode': 'CatSync'
          };

          jQuery('#wclin-btn').prop('disabled',true);
          jQuery('#linwc-btn').prop('disabled',true);

          jQuery('#mItems').removeClass('hidden');
          jQuery.post(ajaxurl, data, function (response) {
            //console.log(response);
            jQuery('#target').html("Categories:  " + response.cats + "");
            //jQuery('#targetBar').attr("max", response);
            linet.timeoutErrorCount = 0;

            linet.itemSync(0);
          }, 'json');
          return false
        },


        itemSync: function (offset) {
          //console.log('subCall',catnum, lastRun);
          var data = {
            'action': 'LinetItemSync',
            'mode': 'ItemSync',
            'offset': offset
          };


          clearTimeout(linet.resumeTimeOut);

          linet.resumeTimeOut = setTimeout(
            ()=>{
              linet.itemSync(offset);
              linet.timeoutErrorCount++
            },1000*60
          )

          var items = jQuery('#subTargetBar').val()*1;
          jQuery.post(ajaxurl, data, function (response) {

            jQuery('#subTarget').html("Items: " + (offset+response.items) );
            jQuery('#subTargetBar').val(offset+response.items);

            if (response.items) {
              linet.itemSync(offset+response.items);

            } else{
              linet.lastCall();

            }
          }, 'json');

          //next cat
        },
        lastCall: function () {

          var data = {
            'action': 'LinetItemSync',
            'mode': 3
          };
          jQuery.post(ajaxurl, data, function (response) {
            //done!
            jQuery('#wclin-btn').prop('disabled',false);
            jQuery('#linwc-btn').prop('disabled',false);
          })

        },


      };


      </script>

    </form>
  </div>
  <?php
}

/**
* Settings intro
*/
public function settings_intro() {
  //echo '<p>' . __('Settings for your Linet account including security keys and default account numbers.<br/> <strong>All</strong> text fields are required for the integration to work properly.', 'wc-linet') . '</p>';
}


public function input_repeater_text($args) {
  $options = $this->get_option($args['key']);
  include( plugin_dir_path( __FILE__ ) . '../templates/field-repeater.php');
}



public function input_cf7_text($args) {
  $options = $this->get_option($args['key']);
  include( plugin_dir_path( __FILE__ ) . '../templates/field-cf7.php');
}

public function input_elementor_text($args) {
  $options = $this->get_option($args['key']);
  include( plugin_dir_path( __FILE__ ) . '../templates/field-elementor.php');
}


/**
* Text setting field
*
* @param array $args
*/
public function input_text($args) {
  echo '<input type="text" name="' . self::OPTION_PREFIX . $args['key'] . '" id="' . self::OPTION_PREFIX . $args['key'] . '" value="' . $this->get_option($args['key']) . '" />';
  echo '<p class="description">' . $args['option']['description'] . '</p>';
}

public function input_none($args) {
  //echo '';
  echo '<h3 class="description">' . $args['option']['description'] . '</h3>';
}

/**
* Checkbox setting field
*
* @param array $args
*/
public function input_checkbox($args) {
  echo '<input type="checkbox" name="' . self::OPTION_PREFIX . $args['key'] . '" id="' . self::OPTION_PREFIX . $args['key'] . '" ' . checked('on', $this->get_option($args['key']), false) . ' /> ';
  echo '<p class="description">' . $args['option']['description'] . '</p>';
}

public function input_select($args) {
  $option = $this->get_option($args['key']);

  $name = esc_attr(self::OPTION_PREFIX . $args['key']);
  $id = esc_attr(self::OPTION_PREFIX . $args['key']);
  echo "<select name='$name' id='$id'>";

  foreach ($args['option']['options'] as $key => $value) {
    $selected = selected($option, $key, false);
    $text = esc_html($value);
    $val = esc_attr($key);
    echo "<option value='$val' $selected>$text</option>";
  }

  echo '</select>';
  echo '<p class="description">' . $args['option']['description'] . '</p>';
  //echo '<p class="description">' . esc_html($args['option']['description']) . '</p>';
}

public function input_pay_list($args) {
  $option = $this->get_option($args['key']);

  $name = esc_attr(self::OPTION_PREFIX . $args['key']);
  $id = esc_attr(self::OPTION_PREFIX . $args['key']);
  //echo $option;
  echo "<select name='{$name}[]' id='$id' multiple='true'>";

  $pay = new \WC_Payment_Gateways;

  foreach ($pay->get_available_payment_gateways() as $id => $small) {
    $args['option']['options'][$id] = $small->title;
  }

  foreach ($args['option']['options'] as $key => $value) {
    $selected='';
    if(is_array($option) && in_array($key, $option)){
      $selected='selected';
    }
    //$selected = selected($option, $key, false);
    $text = esc_html($value);
    $val = esc_attr($key);
    echo "<option value='$val' $selected>$text</option>";
  }

  echo '</select>';
  echo '<p class="description">' . esc_html($args['option']['description']) . '</p>';
}

public static function sendAPI($req, $body = array()) {

  $server = self::SERVER;
  $dev = get_option('wc_linet_dev') == 'on';
  if ($dev) {
    $server = self::DEV_SERVER;
  }

  //var_dump($dev);exit;

  $login_id = get_option('wc_linet_consumer_id');
  $hash = get_option('wc_linet_consumer_key');
  $company = get_option('wc_linet_company');

  $body['login_id'] = $login_id;
  $body['login_hash'] = $hash;
  $body['login_company'] = $company;

  if($login_id=='' || $hash=='' || $company==''){
    return false;
  }

  $logger = new WC_LI_Logger(get_option('wc_linet_debug'));
  $ch = curl_init();
  $logger->write('OWER REQUEST(' .$server . "/api/" . $req. ")\n" .json_encode($body));
  curl_setopt_array($ch, array(
    CURLOPT_URL => $server . "/api/" . $req,
    CURLOPT_POST => TRUE,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_SSL_VERIFYHOST => $dev?0:2,
    CURLOPT_SSL_VERIFYPEER => !$dev,
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      'Wordpress-Site: '.str_replace("http://","",str_replace("https://","",get_site_url())),
      'Wordpress-Plugin: '. WC_Linet::VERSION
    ),
    CURLOPT_POSTFIELDS => json_encode($body)
  ));

  $response = curl_exec($ch);
  curl_close($ch);
  //var_dump($server . "/api/" . $req);
  //var_dump($response);exit;
  $logger->write('LINET RESPONSE:' . "\n" .json_encode($response));

  unset($body);
  unset($login_id);
  unset($hash);
  unset($company);
  unset($ch);
  unset($server);
  unset($req);
  return json_decode($response);
}

public static function TestAjax() {
  $genral_item = (string)get_option('wc_linet_genral_item');

  $genral_item = ($genral_item=="")?"1":$genral_item;
  $res = self::sendAPI('view/item?id=' . $genral_item);

  echo json_encode($res);
  wp_die();

}




public static function RulerAjax() {
  $res = self::sendAPI('rulers');

  if(
    $res &&
    isset($res->body) &&
    is_array($res->body)
  ){
    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

    foreach($res->body as $ruler){
      WC_LI_Inventory::syncRuler($ruler,$logger);
    }
  }

  delete_option("_transient_wc_attribute_taxonomies");
  echo json_encode('ok');

  wp_die();

}



}

  //end class
