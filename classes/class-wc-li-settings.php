<?php

if (!defined('ABSPATH')) {
  exit;
} // Exit if accessed directly

class WC_LI_Settings
{

  const OPTION_PREFIX = 'wc_linet_';
  const SERVER = "https://app.linet.org.il";
  const DEV_SERVER = "https://dev.linet.org.il";

  const STOCK_LIMIT = 5;
  const RUNTIME_LIMIT = 21;

  // Settings defaults
  private $settings = array();
  private $override = array();

  public function __construct($override = null)
  {

    //add_action('init', 'WC_LI_Settings::StartSession', 1);
    //add_action('wp_logout', 'WC_LI_Settings::EndSession');
    //add_action('wp_login', 'WC_LI_Settings::EndSession');

    add_action('linetItemSync', 'WC_LI_Inventory::fullSync');

    //if (is_user_logged_in() && current_user_can('administrator') && wp_verify_nonce(get_header('x-wp-nonce'), 'action')) {
    //


    $headers = self::getRequestHeaders();



    //var_dump($headers);
    if (is_user_logged_in() && current_user_can('administrator')) {

      $no_nonce = get_option('wc_linet_nonce') === 'off';
      if (
        $no_nonce ||
        (isset($headers['X-Wp-Nonce']) && wp_verify_nonce($headers['X-Wp-Nonce'], 'wp_rest'))

      ) {

        add_action('wp_ajax_LinetGetFile', 'WC_LI_Settings::LinetGetFile');
        add_action('wp_ajax_LinetDeleteFile', 'WC_LI_Settings::LinetDeleteFile');
        add_action('wp_ajax_LinetDeleteProd', 'WC_LI_Settings::LinetDeleteProd');

        add_action('wp_ajax_LinetDeleteAttachment', 'WC_LI_Settings::LinetDeleteAttachment');
        add_action('wp_ajax_LinetCalcAttachment', 'WC_LI_Settings::LinetCalcAttachment');



        add_action('wp_ajax_LinetTest', 'WC_LI_Settings::TestAjax');

        add_action('wp_ajax_RulerAjax', 'WC_LI_Settings::RulerAjax');



        add_action('wp_ajax_LinetItemSync', 'WC_LI_Inventory::catSyncAjax'); //linet to wp all prod


        add_action('wp_ajax_LinetCatList', 'WC_LI_Inventory::CatListAjax');

        add_action('wp_ajax_WpItemSync', 'WC_LI_Inventory::WpItemsSyncAjax');
        add_action('wp_ajax_WpCatSync', 'WC_LI_Inventory::WpCatSyncAjax');





      }


      add_action('wp_ajax_LinetSingleItemSync', 'WC_LI_Inventory::singleSyncAjax'); //linet to wp
      add_action('wp_ajax_LinetSingleProdSync', 'WC_LI_Inventory::singleProdAjax'); //wp to linet
      add_action('woocommerce_product_after_variable_attributes', 'WC_LI_Settings::add_variation_custom_sku_input_field', 300, 3);
    }

    //add_filter('woocommerce_get_settings_pages',array($this,'add_woocomerce_settings_tab'))
    if (!is_null($override)) {
      $this->override = $override;
    }
  }


  public static function getRequestHeaders()
  {
    $headers = array();
    foreach ($_SERVER as $key => $value) {
      if (substr($key, 0, 5) <> 'HTTP_') {
        continue;
      }
      $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
      $headers[$header] = $value;
    }
    return $headers;
  }

  public static function add_variation_custom_sku_input_field($loop, $variation_data, $post)
  {
    //$variation = wc_get_product($post->ID);
    $field_key = '_linet_id';
    $value = false;
    if (isset($variation_data['_linet_id']) && isset($variation_data['_linet_id'][0])) {
      $value = $variation_data['_linet_id'][0];

      woocommerce_wp_text_input(
        array(
          'id' => "{$field_key}-{$loop}",
          'name' => "{$field_key}[{$loop}]",
          'value' => $value,
          'custom_attributes' => array('readonly' => 'readonly'),
          'label' => esc_html_e('Linet ID', 'linet-erp-woocommerce-integration'),
          'desc_tip' => true,
          'description' => esc_html_e('Enter the linet ID', 'linet-erp-woocommerce-integration'),
          'wrapper_class' => 'form-row form-row-last',
        )
      );
    }
  }

  public function orderOptions()
  {
    return array(

      'one_item_order' => array(
        'title' => __('One Item Order', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Remove all items from linet doc and make one item only', 'linet-erp-woocommerce-integration'),
      ),


      'autosend' => array(
        'title' => __('Mail Document', 'linet-erp-woocommerce-integration'),
        'default' => 'on',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Autosend document in mail', 'linet-erp-woocommerce-integration'),
      ),
      'autosendsms' => array(
        'title' => __('SMS Document', 'linet-erp-woocommerce-integration'),
        'default' => 'on',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Autosend document in sms', 'linet-erp-woocommerce-integration'),
      ),

      'genral_acc' => array(
        'title' => __('General Custemer Account', 'linet-erp-woocommerce-integration'),
        'default' => '0',
        'type' => 'text',
        'description' => __('Enter 0 for auto create account', 'linet-erp-woocommerce-integration'),
      ),

      'j5Token' => array(
        'title' => __('J5 Token EAV field', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('J5 Token field sync {eavX}', 'linet-erp-woocommerce-integration'),
      ),
      'j5Number' => array(
        'title' => __('J5 Reference Number EAV field', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('J5 Reference Number field sync {eavX}', 'linet-erp-woocommerce-integration'),
      ),

      'genral_item' => array(
        'title' => __('General Item', 'linet-erp-woocommerce-integration'),
        'default' => '1',
        'type' => 'text',
        'description' => __('Code for Linet general Item ', 'linet-erp-woocommerce-integration'),
      ),

      'income_acc' => array(
        'title' => __('Income Account', 'linet-erp-woocommerce-integration'),
        'default' => '100',
        'type' => 'text',
        'description' => __('Income Account', 'linet-erp-woocommerce-integration'),
      ),

      'income_acc_novat' => array(
        'title' => __('Income Account No VAT', 'linet-erp-woocommerce-integration'),
        'default' => '102',
        'type' => 'text',
        'description' => __('Income Account No VAT', 'linet-erp-woocommerce-integration'),
      ),

      'printview' => array(
        'title' => __('Document Print View', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Document Print View.', 'linet-erp-woocommerce-integration'),
      ),

      'status' => array(
        'title' => __('Document status', 'linet-erp-woocommerce-integration'),
        'default' => '2',
        'type' => 'text',
        'description' => __('Document status.', 'linet-erp-woocommerce-integration'),
      ),

      'orderFields' => array(
        'title' => __('Custom Order Fields', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'repeater_text',
        'description' => __('Linet Custom Field ID (eav{N}) for auto syncd products.', 'linet-erp-woocommerce-integration'),
      ),
    );
  }
  public function lineOptions()
  {
    return array(
      'syncFields' => array(
        'title' => __('Custom Field ID (TEST)', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'repeater_text',
        'description' => __('Linet Custom Field ID (eav{N}) for auto syncd products.', 'linet-erp-woocommerce-integration'),
      ),
    );
  }

  public function syncOptions()
  {

    $statuses = array('none' => __('Manually', 'linet-erp-woocommerce-integration'));
    foreach (wc_get_order_statuses() as $key => $name) {
      $statuses[str_replace("wc-", "", $key)] = $name;
    }

    $array = array(

      'sku_find' => array(
        'title' => __('SKU Find', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Find Linet items by SKU and not there Item ID', 'linet-erp-woocommerce-integration'),
      ),

      'global_attr' => array(
        'title' => __('Global attributes', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('use global attributes for variable products', 'linet-erp-woocommerce-integration')
          . '<a style="" href="#target1" class="button-primary" onclick="linet.doRuler();">Write Global Rulers</a> '
        ,
      ),


      'old_attr' => array(
        'title' => __('Preserve Old attributes', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('preserve old attributes for variable products', 'linet-erp-woocommerce-integration'),
      ),
    );

    foreach (wc_get_order_statuses() as $key => $name) {
      $str_name = (string) $name;
      $skey = str_replace("wc-", "", $key);
      $statuses[$skey] = $str_name;

      $array["sync_orders_$key"] = array(
        'title' => 'Sync Orders On' . ' ' . $str_name,
        'default' => 'none',
        //type' => 'checkbox',
        'type' => 'select',
        'options' => array(
          '' => __('None', 'linet-erp-woocommerce-integration'),
          '1' => __('Proforma', 'linet-erp-woocommerce-integration'),
          '2' => __('Delivery Doc.', 'linet-erp-woocommerce-integration'),
          '3' => __('Invoice', 'linet-erp-woocommerce-integration'),
          '6' => __('Quote', 'linet-erp-woocommerce-integration'),

          '7' => __('Sales Order', 'linet-erp-woocommerce-integration'),
          '8' => __('Receipt', 'linet-erp-woocommerce-integration'),
          '9' => __('Invoice Receipt', 'linet-erp-woocommerce-integration'),
          '17' => __('Stock Exit Doc.', 'linet-erp-woocommerce-integration'),
          '18' => __('Donation Receipt', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Auto Genrate Doc in Linet', 'linet-erp-woocommerce-integration'),
      );


    }

    return $array + array(

      'manual_linet_doc' => array(
        'title' => __('Sync Orders Manual', 'linet-erp-woocommerce-integration'),
        'default' => 'none',
        //type' => 'checkbox',
        'type' => 'select',
        'options' => array(
          '' => __('None', 'linet-erp-woocommerce-integration'),
          '1' => __('Performa', 'linet-erp-woocommerce-integration'),
          '2' => __('Delivery Doc.', 'linet-erp-woocommerce-integration'),
          '3' => __('Invoice', 'linet-erp-woocommerce-integration'),

          '7' => __('Sales Order', 'linet-erp-woocommerce-integration'),
          '8' => __('Receipt', 'linet-erp-woocommerce-integration'),
          '9' => __('Invoice Receipt', 'linet-erp-woocommerce-integration'),
          '17' => __('Stock Exist Doc.', 'linet-erp-woocommerce-integration'),
          '18' => __('Donation Receipt', 'linet-erp-woocommerce-integration'),

        ),
        'description' => __('Auto Genrate Doc in Linet', 'linet-erp-woocommerce-integration'),
      ),

      'sync_back_status' => array(
        'title' => __('sync back order status', 'linet-erp-woocommerce-integration'),
        'default' => 'none',
        //type' => 'checkbox',
        'type' => 'select',
        'options' => $statuses,
        'description' => __('will change order stauts after action in linet', 'linet-erp-woocommerce-integration'),
      ),

      'supported_gateways' => array(
        'title' => __('Supported Gateways', 'linet-erp-woocommerce-integration'),
        'default' => '',
        //type' => 'checkbox',
        'type' => 'pay_list',
        'description' => __('Select Gateways to invoice', 'linet-erp-woocommerce-integration'),
      ),
      'stock_manage' => array(
        //out
        'title' => __('Stock Manage', 'linet-erp-woocommerce-integration'),
        'default' => 'on',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Use Linet to sync the stock level of items', 'linet-erp-woocommerce-integration'),
      ),
      'only_stock_manage' => array(
        'title' => __('Only Stock Manage', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Will update only stock levels and not other details', 'linet-erp-woocommerce-integration'),
      ),

      'no_description' => array(
        'title' => __('No Description', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Will block updates  for description from linet', 'linet-erp-woocommerce-integration'),
      ),

      'pricelist_account' => array(
        'title' => __('Pricelist Custemer ID', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('custemer id for a spical pricelist for the site', 'linet-erp-woocommerce-integration'),
      ),
      'sale_pricelist_id' => array(
        'title' => __('Sale Pricelist ID', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Pricelist id for a sale', 'linet-erp-woocommerce-integration'),
      ),



      'sync_items' => array(
        'title' => __('Sync Items', 'linet-erp-woocommerce-integration'),
        'default' => 'on',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
          'sns' => __('SNS - Select Only With Linet Support!', 'linet-erp-woocommerce-integration'),
        ),

        'description' => __('Manual Items Sync:', 'linet-erp-woocommerce-integration') .
          ' <br /><button type="button" id="linwc-btn" class="button-primary" onclick="linet.fullItemsSync();">Linet->WC</button>' .
          ' <br /><button type="button" id="wclin-btn" class="button hidden" onclick="linet.fullProdSync();">WC->Linet</button>' .
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
        'title' => __('Custom Field ID (Product)', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Linet Custom Field ID (eav{N}) for auto syncd products', 'linet-erp-woocommerce-integration'),
      ),
      'syncValue' => array(
        'title' => __('Custom Field Value (Product)', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Linet Custom Field Value for auto syncd products', 'linet-erp-woocommerce-integration'),
      ),
      'syncCatField' => array(
        'title' => __('Custom Field ID (Category)', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Linet Custom Field ID (eav{N}) for auto syncd categories', 'linet-erp-woocommerce-integration'),
      ),
      'syncCatValue' => array(
        'title' => __('Custom Field Value (Category)', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Linet Custom Field Value for auto syncd categories', 'linet-erp-woocommerce-integration'),
      ),
      'picsync' => array(
        'title' => __('Picture Sync', 'linet-erp-woocommerce-integration'),
        'default' => 'on',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Will sync Pictures', 'linet-erp-woocommerce-integration'),
      ),
      'rect_img' => array(
        'title' => __('Picture Options', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'none' => __('None', 'linet-erp-woocommerce-integration'),
          'on' => __('Force Rect. Picture', 'linet-erp-woocommerce-integration'),
          'nothumb' => __('Original File', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Will force Rectangular Pictures', 'linet-erp-woocommerce-integration'),
      ),


      'not_product_attributes' => array(
        'title' => __('No Product Attributes', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Do not write product_attributes meta data', 'linet-erp-woocommerce-integration'),
      ),


      'warehouse_id' => array(
        'title' => __('Warehouse', 'linet-erp-woocommerce-integration'),
        'default' => '115',
        'type' => 'text',
        'description' => __('Warehouse ID from Linet', 'linet-erp-woocommerce-integration'),
      ),

      'warehouse_exclude' => array(
        'title' => __('Warehouse exclude', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Warehouse ID from Linet you can write a list with commas(,)', 'linet-erp-woocommerce-integration'),
      ),

      'warehouse_stock_count' => array(
        'title' => __('Stock Count Warehouse', 'linet-erp-woocommerce-integration'),
        'default' => 'on',
        'type' => 'select',
        'options' => array(
          'off' => __('All company Warehouses', 'linet-erp-woocommerce-integration'),
          'on' => __('The same warehouse', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Stock Count Warehouse', 'linet-erp-woocommerce-integration'),
      ),

      'itemFields' => array(
        'title' => __('Custom Item Fields', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'repeater_text',
        'description' => __('Linet Custom Field ID (eav{N}) for auto syncd products.', 'linet-erp-woocommerce-integration'),
      ),
    );
  }


  public static function LinetGetFile()
  {
    $filtered = preg_replace('/[^A-Za-z0-9.-]/', '', $_POST['name']);
    $filtered = preg_replace('/\.+/', '.', $filtered);

    $name = str_replace("/", "", str_replace("..", "", $_POST['name']));
    echo esc_html(file_get_contents(WC_LOG_DIR . $filtered));
    wp_die();
  }


  public static function LinetDeleteFile()
  {
    $filtered = preg_replace('/[^A-Za-z0-9.-]/', '', $_POST['name']);
    $filtered = preg_replace('/\.+/', '.', $filtered);

    //$name = str_replace("/", "", str_replace("..", "",$_POST['name'] ));
    wp_delete_file(WC_LOG_DIR . $filtered);
    //echo esc_html(unlink(WC_LOG_DIR . $filtered));
    wp_die();
  }

  public static function LinetDeleteProd()
  {

    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

    $key = $_POST['key'];
    $value = $_POST['value'];
    $logger->write("admin delete by $key: $value");

    if ($key === "id") {
      $post_id = (int) $value;
      return self::DeleteProd(wc_get_product($post_id), $logger);
    }


    if ($key === "_linet_id") {
      global $wpdb;

      return $wpdb->query(
          $wpdb->prepare(
              "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
              $key,
              $value
          )
      );

    }

    $products = wc_get_products(
      [
        'limit' => 10,

        //'type' => array('simple', 'variable'),

        //'post_type' => 'product',
        'meta_key' => $key,
        'meta_value' => $value, //'meta_value' => array('yes'),
        //'meta_compare' => '=' //'meta_compare' => 'NOT IN'
      ]

    );


    $first = true;
    foreach ($products as $product) {
      if ($first) {
        $first = false;
      } else {
        self::DeleteProd($product, $logger);
      }

    }

    wp_die();
  }

  public static function DeleteProd($product, $logger)
  {

    if (!empty($product)) {
      $post_id = $product->get_id();

      $logger->write("found prod $post_id");

      echo esc_html($product->delete(true));


      echo esc_html(wc_delete_product_transients($post_id));

    } else {
      $logger->write("not found prod");

    }

  }



  public static function LinetDeleteAttachment($id)
  {
    $id = (int) $_POST['id'];

    wp_delete_attachment($id);
  }

  public static function LinetCalcAttachment($id)
  {
    $id = (int) $_POST['id'];
    $pic = (int) $_POST['file'];

    $basePath = wp_upload_dir()['basedir'] . '/';
    $realtivePath = WC_LI_Inventory::IMAGE_DIR . "/" . $pic;
    $filePath = $basePath . $realtivePath;

    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $filePath));
  }



  public function form()
  {
    $arr = array();

    $args = array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1);

    if ($data = get_posts($args)) {
      foreach ($data as $form) {
        $arr["cf7" . $form->ID] = array(
          'title' => __('CF7:', 'linet-erp-woocommerce-integration') . " " . $form->post_title,
          'default' => '',
          'type' => 'cf7_text',
          'payload' => array('form_id' => $form->ID)
          //'description' => __('Login ID  retrieved from <a href="http://app.linet.org.il" target="_blank">Linet</a>.', 'linet-erp-woocommerce-integration'),
        );
      }
    }

    $arr["elementor_form"] = array(
      'title' => __('elementor form map', 'linet-erp-woocommerce-integration'),
      'default' => '',
      'type' => 'elementor_text',
      //'payload' => array('form_id'=>1),
      'description' => __('map form by name and field id', 'linet-erp-woocommerce-integration'),
    );



    /*
     */


    return $arr;
  }




  public function maintenance()
  {
    global $wpdb;

    $arr = array();

    $products = $wpdb->get_results("SELECT post_id,meta_value ,count(meta_value) as num FROM {$wpdb->postmeta} where meta_key='_sku' GROUP by meta_value HAVING num>1");

    foreach ($products as $index => $product) {
      $arr['sku' . $index] = array(
        'title' => __('duplicate sku', 'linet-erp-woocommerce-integration') . " <br /><a data-key='_sku' data-value='$product->meta_value' onclick=\"linet.deleteProd(event,this);\" href=''>Delete</a>",
        'default' => '',
        'type' => 'none',
        'description' => $product->post_id . " " . $product->meta_value . " " . $product->num,
      );
    }

    $products = $wpdb->get_results("SELECT post_id,meta_value ,count(meta_value) as num FROM {$wpdb->postmeta} WHERE meta_key='_linet_id' GROUP by meta_value HAVING num>1");


    foreach ($products as $index => $product) {
      $arr['linet_id' . $index] = array(
        'title' => __('duplicate linet_id', 'linet-erp-woocommerce-integration') . " <br /><a data-key='_linet_id' data-value='$product->meta_value' onclick=\"linet.deleteProd(event,this);\" href=''>Delete</a>",
        'default' => '',
        'type' => 'none',
        'description' => $product->post_id . " " . $product->meta_value . " " . $product->num,
      );
    }


    $attachments = $wpdb->get_results("SELECT ID,post_title,meta_value FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON post_id=ID AND meta_key = '_wp_attachment_metadata' where post_type='attachment' AND meta_value is null");
    foreach ($attachments as $index => $attachment) {
      $arr['attachment' . $index] = array(
        'title' => __('attachment metadata missing', 'linet-erp-woocommerce-integration') . " <br /><a data-id='$attachment->ID' onclick=\"linet.deleteAttachment(this);\" href=''>Delete</a>",
        'default' => '',
        'type' => 'none',
        'description' => "<a data-id='$attachment->ID' data-file='$attachment->post_title' onclick=\"linet.calcAttachment(this);\" href=''>$attachment->post_title</a>",
      );
    }


    $products = $wpdb->get_results("
SELECT a.*, meta_id.meta_value AS meta_id, meta_sku.meta_value AS meta_sku
FROM (
    SELECT 
        COUNT(p.ID) AS inst,
        MAX(p.ID) AS lasty,
        p.post_type,
        p.post_title,
        p.post_excerpt,
        p.post_parent
    FROM {$wpdb->posts} p
    WHERE 
        p.post_parent IN (
            SELECT DISTINCT post_parent 
            FROM {$wpdb->posts} 
            WHERE post_type = 'product_variation'
        ) 
        AND p.post_parent != 0 
        AND p.post_type = 'product_variation'
    GROUP BY p.post_parent, p.post_excerpt
    HAVING inst > 1
) a
LEFT JOIN {$wpdb->postmeta} meta_sku 
    ON meta_sku.meta_key = '_sku' AND meta_sku.post_id = a.lasty
LEFT JOIN {$wpdb->postmeta} meta_id 
    ON meta_id.meta_key = '_linet_id' AND meta_id.post_id = a.lasty
ORDER BY a.post_parent ASC
");
    foreach ($products as $index => $product) {
      $arr['vari' . $index] = array(
        'title' => __('duplicate product_variation', 'linet-erp-woocommerce-integration') . " <br /><a class='duplidel' data-key='id' data-value='$product->lasty' onclick=\"linet.deleteProd(event,this);\" href=''>Delete</a>",
        'default' => '',
        'type' => 'none',
        'description' => "post_id: " . $product->lasty . " post_parent: " . $product->post_parent . " linet_id:" . $product->meta_id . " sku:" . $product->meta_sku . " count: " . $product->inst,
      );
    }



    $scanned_directory = array();
    if (is_dir(WC_LOG_DIR)) {
      $scanned_directory = array_diff(scandir(WC_LOG_DIR), array('..', '.'));

    }

    $text = array();
    foreach ($scanned_directory as $index => $file) {
      if (strpos($file, 'linet') === 0 || strpos($file, 'fatal-errors') === 0)
        $arr['file' . $index] = array(
          'title' => __('Log File', 'linet-erp-woocommerce-integration') . "<br /><a data-name='$file'  onclick=\"linet.deleteFile(event,this);\" href='#'>Delete</a>",
          'default' => '',
          'type' => 'href',
          "onclick" => "linet.getFile('$file')",
          "href" => '#',
          'text' => $file,
          //'description' => $file,

        );
    }

    return $arr;
  }


  public function connectionOptions()
  {


    return array(
      'consumer_id' => array(
        'title' => __('ID', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Login ID  retrieved from <a href="http://app.linet.org.il" target="_blank">Linet</a>.', 'linet-erp-woocommerce-integration'),
      ),
      'consumer_key' => array(
        'title' => __('Key', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Key retrieved from <a href="http://app.linet.org.il" target="_blank">Linet</a>.', 'linet-erp-woocommerce-integration'),
      ),
      'company' => array(
        'title' => __('Company', 'linet-erp-woocommerce-integration'),
        'default' => '1',
        'type' => 'text',
        'description' => __('Company id', 'linet-erp-woocommerce-integration'),
      ),
      'last_update' => array(
        'title' => __('Last Update Time', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'text',
        'description' => __('Last Update Time ', 'linet-erp-woocommerce-integration'),
        'options' => array(
          'readonly' => true,
        )
      ),
      'last_sns' => array(
        'title' => __('Last Message Time', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'none',
        'description' => self::get_option("last_sns"),

      ),
      'debug' => array(
        'title' => __('Debug', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Enable logging.  Log file is located at:', 'linet-erp-woocommerce-integration') . " " . WC_LOG_DIR,
      ),
      'dev' => array(
        'title' => __('Dev Mode', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('Will work aginst the dev server', 'linet-erp-woocommerce-integration'),
      ),
      'nonce' => array(
        'title' => __('Safe Ajax Mode', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'on' => __('On', 'linet-erp-woocommerce-integration'),
          'off' => __('Off', 'linet-erp-woocommerce-integration'),

        ),
        'description' => __('Will disable security nonce', 'linet-erp-woocommerce-integration'),
      ),
    );
  }



  public static function StartSession()
  {
    if (!session_id()) {
      session_start();
    }
  }

  public static function EndSession()
  {
    session_destroy();
  }

  /**
   * Setup the required settings hooks
   */
  public function setup_hooks()
  {
    add_action('admin_init', array($this, 'register_settings'));

    add_action('admin_menu', array($this, 'add_menu_item'));

    add_action('post_submitbox_start', array($this, 'custom_button'));


    add_action('product_cat_edit_form_fields', array($this, 'custom_term_button'));

  }


  function custom_term_button($term)
  {
    $taxonomy = $term->taxonomy;
    $types = ['product_cat'];
    if (in_array($taxonomy, $types)) {
      $metas = get_term_meta($term->term_id);
      ?>
      Linet Cat ID:
      <?php echo esc_html(isset($metas['_linet_cat']) && $metas['_linet_cat']['0'] ? $metas['_linet_cat']["0"] : "No Linet ID") ?><br />
      Linet Last Upate:
      <?php echo esc_html(isset($metas['_linet_last_update']) && $metas['_linet_last_update']['0'] ? $metas['_linet_last_update']["0"] : "unkown") ?><br />

      <?php
    }
  }

  function custom_button($post)
  {

    $types = ['product'];
    if (in_array(get_post_type($post), $types)) {



      $nonce = get_option('wc_linet_nonce') !== 'off';

      if ($nonce) {
        wp_localize_script('wp-api', 'wpApiSettings', array(
          'root' => esc_url_raw(rest_url()),
          'nonce' => wp_create_nonce('wp_rest')
        ));


      }



      $metas = get_post_meta($post->ID);
      ?>
      <script>
        var linet = {
          singleSync: function (post_id) {
            var data = {
              'action': 'LinetSingleItemSync',
              'post_id': post_id
            };
            jQuery.ajax({
              url: ajaxurl,
              method: 'POST',
              dataType: "json",

              <?php if ($nonce): ?>
                                                                                                    beforeSend: function (xhr) {
                  xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
              <?php endif; ?>
                                                                                data: data
            }).done(function (response) {
              alert(response.status);
              location.reload();
            });


          },
          singleToSync: function (post_id) {
            var data = {
              'action': 'LinetSingleProdSync',
              'post_id': post_id
            };
            jQuery.ajax({
              url: ajaxurl,
              method: 'POST',
              dataType: "json",

              <?php if ($nonce): ?>
                                                                                                    beforeSend: function (xhr) {
                  xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
              <?php endif; ?>
                                                                                  data: data
            }).done(function (response) {
              alert(response.status);
              location.reload();
            });
          }
        }
      </script>
      Linet ID:
      <?php echo esc_html(isset($metas['_linet_id']) && $metas['_linet_id']['0'] ? $metas['_linet_id']["0"] : "No Linet ID") ?><br />
      Linet Last Upate:
      <?php echo esc_html(isset($metas['_linet_last_update']) && $metas['_linet_last_update']['0'] ? $metas['_linet_last_update']["0"] : "unkown") ?><br />
      <a class="button" data-post_id="<?php echo esc_attr($post->ID); ?>"
        onclick="linet.singleSync(<?php echo esc_attr($post->ID); ?>);">Sync
        Item From
        Linet</a>
      <a class="button hidden" data-post_id="<?php echo esc_attr($post->ID); ?>"
        onclick="linet.singleToSync(<?php echo esc_attr($post->ID); ?>);">Sync Item To
        Linet</a>
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
  public function get_option($key)
  {

    if (isset($this->override[$key])) {
      return $this->override[$key];
    }

    $default = '';
    if (isset($this->settings[$key]) && isset($this->settings[$key]['default']))
      $default = $this->settings[$key]['default'];
    return get_option(self::OPTION_PREFIX . $key, $default);

  }

  /**
   * settings_init()
   *
   * @access public
   * @return void
   */
  public function register_settings()
  {

    //self::fullItemsSync();
    // Add section
    add_settings_section(
      'wc_linet_settings',
      __('Linet Settings', 'linet-erp-woocommerce-integration'),
      array(
        $this,
        'settings_intro'
      ),
      'woocommerce_linet'
    );
    $this->settings = array_merge(
      $this->orderOptions(),
      $this->lineOptions(),
      $this->syncOptions(),
      $this->connectionOptions(),
      //$this->maintenance(),
      $this->form()
    );

    $selectedTab = isset($_GET["tab"]) ? $_GET["tab"] : "";


    switch ($selectedTab) {
      case "order-options":
        $this->renderOptTab($this->orderOptions());
        break;

      case 'line-options':

        $this->renderOptTab($this->lineOptions());
        break;

      case 'sync-options':
        $this->renderOptTab($this->syncOptions());
        break;

      case 'maintenance':
        $this->renderOptTab($this->maintenance());
        break;

      case 'connection-options':
        $this->renderOptTab($this->connectionOptions());
        break;
      case 'form':
        $this->renderOptTab($this->form());
        break;

      default:
        $this->renderOptTab($this->connectionOptions());
        break;
    }
    //here we display the sections and options in the settings page based on the active tab


    //$this->renderOptTab($this->settings);


  }

  public function renderOptTab($settings)
  {
    // Add setting fields
    foreach ($settings as $key => $option) {

      // Add setting fields
      add_settings_field(self::OPTION_PREFIX . $key, $option['title'], array(
        $this,
        'input_' . $option['type']
      ), 'woocommerce_linet', 'wc_linet_settings', array('key' => $key, 'option' => $option));

      // Register setting
      register_setting('woocommerce_linet', self::OPTION_PREFIX . $key, [
        'sanitize_callback' => ['WC_LI_Settings', 'sanitize_input'] // Add appropriate sanitization function
      ]);
    }
  }



  public static function sanitize_input($input) {
    if (!is_array($input)) {
        return sanitize_text_field($input);
    }

    foreach ($input as $key => &$value) {
        if (is_array($value)) {
            $value = self::sanitize_input($value);
        } else {
            $value = sanitize_text_field($value);
        }
    }

    return $input;
}

  public function input_href($settings)
  {

    //print_r($settings);
    return printf("<a onclick='%s' href='%s'>%s</a>", esc_attr($settings['option']['onclick']), esc_attr($settings['option']['href']), esc_html($settings['option']['text']));
  }

  /**
   * Add menu item
   *
   * @return void
   */
  public function add_menu_item()
  {
    $sub_menu_page = add_submenu_page(
      'woocommerce',
      __('Linet', 'linet-erp-woocommerce-integration'),
      __('Linet', 'linet-erp-woocommerce-integration'),
      'manage_woocommerce',
      'woocommerce_linet',
      array(
        $this,
        'options_page'
      )
    );

    add_action('load-' . $sub_menu_page, array($this, 'enqueue_style'));


  }

  public function enqueue_style()
  {
    global $woocommerce;
    wp_enqueue_style('woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css');
  }

  /**
   * The options page
   */
  public function options_page()
  {


    $autoSync = get_option('wc_linet_sync_items');

    $login_id = get_option('wc_linet_consumer_id');
    $hash = get_option('wc_linet_consumer_key');
    $company = get_option('wc_linet_company');


    if ($autoSync == 'on' && $login_id != '' && $hash != '' && $company != '') {
      if (!wp_next_scheduled('linetItemSync')) {
        wp_schedule_event(time(), 'hourly', 'linetItemSync');
      }
    } else {
      wp_clear_scheduled_hook('linetItemSync');
    }
    $status = wp_cache_get('linet_fullSync_status', 'linet');
    //var_dump($status);exit;
    //adam:sync
    //wp_clear_scheduled_hook( 'linetItemSync' );
    //wp_schedule_event(time(), 'hourly', 'linetItemSync');

    $active_tab = "connection-options";
    if (isset($_GET["tab"])) {
      if ($_GET["tab"] == "order-options")
        $active_tab = "order-options";
      if ($_GET["tab"] == "line-options")
        $active_tab = "line-options";
      if ($_GET["tab"] == "sync-options")
        $active_tab = "sync-options";
      if ($_GET["tab"] == "maintenance")
        $active_tab = "maintenance";
      if ($_GET["tab"] == "form")
        $active_tab = "form";

    }

    ?>
    <div class="wrap woocommerce">
      <form method="post" id="mainform" action="options.php?tab=<?php echo esc_attr($active_tab); ?>">
        <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
        <h2>
          <?php esc_html_e('Linet for WooCommerce', 'linet-erp-woocommerce-integration'); ?>
        </h2>

        <?php
        if (isset($_GET['settings-updated']) && ($_GET['settings-updated'] == 'true')) {
          echo '<div id="message" class="updated fade"><p><strong>' . esc_html_e('Your settings have been saved.', 'linet-erp-woocommerce-integration') . '</strong></p></div>';
        } else if (isset($_GET['settings-updated']) && ($_GET['settings-updated'] == 'false')) {
          echo '<div id="message" class="error fade"><p><strong>' . esc_html_e('There was an error saving your settings.', 'linet-erp-woocommerce-integration') . '</strong></p></div>';
        }
        ?>

        <?php
        if (
          $status &&
          isset($status['running']) &&
          $status['running'] &&
          isset($status['start']) &&
          isset($status['offset'])

        ) {

          echo '<div id="backgroundSync" class="error fade"><p><strong>' . esc_html_e('background sync is rununing started/syncd', 'linet-erp-woocommerce-integration') . esc_html($status['start']) . "/" . esc_html($status['offset']) . '</strong></p></div>';
        }
        ?>


        <a href="#target1" class="button-primary" onclick="linet.doTest();">Test Connection</a> (You can Check The
        Connection Only After Saving)


        <h2 class="nav-tab-wrapper">
          <!-- when tab buttons are clicked we jump back to the same page but with a new parameter that represents the clicked tab. accordingly we make it active -->
          <a href="?page=woocommerce_linet&tab=connection-options" class="nav-tab <?php if ($active_tab == 'connection-options') {
            echo 'nav-tab-active';
          } ?> "><?php esc_html_e('Connection Options', 'linet-erp-woocommerce-integration'); ?></a>
          <a href="?page=woocommerce_linet&tab=order-options" class="nav-tab <?php if ($active_tab == 'order-options') {
            echo 'nav-tab-active';
          } ?>"><?php esc_html_e('Order Options', 'linet-erp-woocommerce-integration'); ?></a>
          <a href="?page=woocommerce_linet&tab=line-options" class="nav-tab <?php if ($active_tab == 'line-options') {
            echo 'nav-tab-active';
          } ?>"><?php esc_html_e('Line Options', 'linet-erp-woocommerce-integration'); ?></a>
          <a href="?page=woocommerce_linet&tab=sync-options" class="nav-tab <?php if ($active_tab == 'sync-options') {
            echo 'nav-tab-active';
          } ?>"><?php esc_html_e('Sync Options', 'linet-erp-woocommerce-integration'); ?></a>
          <a href="?page=woocommerce_linet&tab=maintenance" class="nav-tab <?php if ($active_tab == 'maintenance') {
            echo 'nav-tab-active';
          } ?>"><?php esc_html_e('Maintenance', 'linet-erp-woocommerce-integration'); ?></a>
          <a href="?page=woocommerce_linet&tab=form" class="nav-tab <?php if ($active_tab == 'form') {
            echo 'nav-tab-active';
          } ?>"><?php esc_html_e('Form', 'linet-erp-woocommerce-integration'); ?></a>


        </h2>

        <?php settings_fields('woocommerce_linet'); ?>
        <?php do_settings_sections('woocommerce_linet');



        $nonce = get_option('wc_linet_nonce') !== 'off';

        if ($nonce) {
          wp_localize_script('wp-api', 'wpApiSettings', array(
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
          ));


        }



        ?>


        <p class="submit"><input type="submit" class="button-primary" value="Save" /></p>
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

              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                <?php if ($nonce): ?>
                                                                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                                         data: data
              }).done(function (response) {
                console.log(response);
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



              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',

                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                          data: data
              }).done(function (response) {
                console.log(response);
              });



              return false;
            },

            deleteProd: function (e, obj) {

              e.preventDefault();


              var key = jQuery(obj).data('key');
              var value = jQuery(obj).data('value');
              jQuery(obj).parent().parent().hide();

              var data = {
                'action': 'LinetDeleteProd',
                'key': key,
                'value': value
              };

              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',

                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                          data: data
              }).done(function (response) {
                console.log(response);
              });

              return false;
            },
            deleteFile: function (e, obj) {
              e.preventDefault();

              var name = jQuery(obj).data('name');
              jQuery(obj).parent().parent().hide();
              var data = {
                'action': 'LinetDeleteFile',
                'name': name
              };

              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',

                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                        data: data
              }).done(function (response) {
                console.log(response);
              });

              return false;
            },
            getFile: function (name) {
              var data = {
                'action': 'LinetGetFile',
                'name': name
              };
              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',

                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                        data: data
              }).done(function (response) {
                console.log(response);
                var blob = new Blob([response]);
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = name;
                link.click();
              });

              return false;
            },


            doTest: function () {
              var data = {
                'action': 'LinetTest',
                //'mode': 1
              };


              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: "json",

                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                          data: data
              }).done(function (response) {
                alert(response.text);
              });



            },


            doRuler: function () {
              var data = {
                'action': 'RulerAjax',
                //'mode': 1
              };


              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',

                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                        data: data
              }).done(function (response) {
                alert(response);
              });




            },

            fullProdSync: function () {
              //event.preventDefault();
              var data = {
                'action': 'WpItemSync',
                'mode': 0
              };
              jQuery('#mItems').removeClass('hidden');

              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',

                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                        data: data
              }).done(function (response) {
                jQuery('#target').html("Items:  0/" + response);
                jQuery('#targetBar').prop('max', response);
                linet.timeoutErrorCount = 0;
                if (response) {
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

              clearTimeout(linet.resumeTimeOut);

              linet.resumeTimeOut = setTimeout(
                () => {
                  linet.prodSync(offset);
                  linet.timeoutErrorCount++
                }, 1000 * 60
              )

              num = jQuery('#targetBar').prop('max');

              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',

                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                      data: data
              }).done(function (response) {

                //console.log(response);
                bar = offset + response * 1;

                jQuery('#target').html("Items:  " + bar + "/" + num);
                jQuery('#targetBar').val(bar);

                if (num - bar > 0)
                  linet.prodSync(bar);
                //linet.subCall(num - 1, 1);
                //count
              });

            },

            getList: function () {
              var data = {
                'action': 'LinetCatList',
                //'mode': 1
              };

              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: "json",


                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                        data: data
              }).done(function (response) {
                jQuery('#catList').html("");
                for (i = 0; i < response.body.length; i++) {
                  jQuery('#catList').append("<li>" + response.body[i].name + " <span id='catValue" + response.body[i].id + "'></span></li>");
                  var data = {
                    'action': 'WpCatSync',
                    'id': response.body[i].id,
                    'catName': response.body[i].name
                  };

                  jQuery.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                      },
                    <?php endif; ?>
                                            data: data
                  }).done(function (response) {
                    linet.catDet(response);

                  });

                }
              });

            },

            fullItemsSync: function () {
              //event.preventDefault();
              jQuery('#mItems').show();

              var data = {
                'action': 'LinetItemSync',
                'mode': 'CatSync'
              };

              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: "json",


                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                        data: data
              }).done(function (response) {
                console.log(response)
                jQuery('#target').html("Categories:  " + response.cats + "");
                linet.timeoutErrorCount = 0;

                linet.itemSync(0);
              });
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
                () => {
                  linet.itemSync(offset);
                  linet.timeoutErrorCount++
                }, 1000 * 60
              )

              var items = jQuery('#subTargetBar').val() * 1;
              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: "json",


                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                              data: data
              }).done(function (response) {

                jQuery('#subTarget').html("Items: " + (offset + response.items));
                jQuery('#subTargetBar').val(offset + response.items);

                if (response.items) {
                  linet.itemSync(offset + response.items);

                } else {
                  linet.lastCall();

                }
              });

              //next cat
            },
            lastCall: function () {

              var data = {
                'action': 'LinetItemSync',
                'mode': 3
              };
              jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                //dataType: "json",

                <?php if ($nonce): ?>
                                                          beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                  },
                <?php endif; ?>
                                          data: data
              }).done(function (response) {                //done!
                jQuery('#wclin-btn').prop('disabled', false);
                jQuery('#linwc-btn').prop('disabled', false);
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
  public function settings_intro()
  {
    //echo '<p>' . __('Settings for your Linet account including security keys and default account numbers.<br/> <strong>All</strong> text fields are required for the integration to work properly.', 'linet-erp-woocommerce-integration') . '</p>';
  }


  public function input_repeater_text($args)
  {
    $options = $this->get_option($args['key']);
    include(plugin_dir_path(__FILE__) . '../templates/field-repeater.php');
  }



  public function input_cf7_text($args)
  {
    $options = $this->get_option($args['key']);
    include(plugin_dir_path(__FILE__) . '../templates/field-cf7.php');
  }

  public function input_elementor_text($args)
  {
    $options = $this->get_option($args['key']);
    include(plugin_dir_path(__FILE__) . '../templates/field-elementor.php');
  }


  /**
   * Text setting field
   *
   * @param array $args
   */
  public function input_text($args)
  {
    echo '<input type="text" name="' . esc_attr(self::OPTION_PREFIX . $args['key']) . '" id="' . esc_attr(self::OPTION_PREFIX . $args['key']) . '" value="' . esc_attr($this->get_option($args['key'])) . '" />';
    echo '<p class="description">' . wp_kses($args['option']['description'], WC_Linet::ALLOWD_TAGS) . '</p>';
  }

  public function input_none($args)
  {
    //echo '';
    echo '<h3 class="description">' . wp_kses($args['option']['description'], WC_Linet::ALLOWD_TAGS) . '</h3>';
  }

  /**
   * Checkbox setting field
   *
   * @param array $args
   */
  public function input_checkbox($args)
  {
    echo '<input type="checkbox" name="' . esc_attr(self::OPTION_PREFIX . $args['key']) . '" id="' . esc_attr(self::OPTION_PREFIX . $args['key']) . '" ' . esc_attr(checked('on', $this->get_option($args['key']), false)) . ' /> ';
    echo '<p class="description">' . wp_kses($args['option']['description'], WC_Linet::ALLOWD_TAGS) . '</p>';
  }

  public function input_select($args)
  {
    $option = $this->get_option($args['key']);

    $name = self::OPTION_PREFIX . $args['key'];
    $id = esc_attr(self::OPTION_PREFIX . $args['key']);
    echo "<select name='" . esc_attr($name) . "' id='" . esc_attr($id) . "'>";

    foreach ($args['option']['options'] as $key => $value) {
      $selected = selected($option, $key, false);
      $text = esc_html($value);
      $val = esc_attr($key);
      echo "<option value='" . esc_attr($val) . "' " . esc_attr($selected) . ">" . esc_html($text) . "</option>";
    }

    echo '</select>';
    echo '<p class="description">' . wp_kses($args['option']['description'], WC_Linet::ALLOWD_TAGS) . '</p>';
  }

  public function input_pay_list($args)
  {
    $saved_value = $this->get_option($args['key']);
    $opt = $args['option'];
    //var_dump($option);exit;

    $name = self::OPTION_PREFIX . $args['key'] . "[]";
    $id = self::OPTION_PREFIX . $args['key'];
    //echo $option;
    printf(
      "<select name='%s' id='%s' multiple='true'>",
      esc_attr($name),
      esc_attr($name)
    );

    $pay = new \WC_Payment_Gateways;

    foreach ($pay->get_available_payment_gateways() as $id => $small) {
      $opt['options'][$id] = $small->title;
    }

    foreach ($opt['options'] as $key => $value) {
      $selected = '';
      if (is_array($saved_value) && in_array($key, $saved_value)) {
        $selected = 'selected';
      }
      //$selected = selected($option, $key, false);
      printf(
        "<option value='%s' %s>%s</option>",
        esc_attr($key),
        esc_attr($selected),
        esc_attr($value)
      );
    }
    printf(
      "</select><p class='description'>%s</p>",
      esc_html($opt['description'])
    );
  }

  public static function sendAPI($req, $body = array())
  {

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

    if ($login_id == '' || $hash == '' || $company == '') {
      return false;
    }

    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));


    $url = $server . "/api/" . $req;
    $logger->write('OWER REQUEST(' . $url . ")\n" . json_encode($body));

    $args = array(
      'method' => 'POST',
      'sslverify' => !$dev,
      'timeout' => 30,
      'headers' => array(
        'Content-Type' => 'application/json',
        'Wordpress-Site' => str_replace("http://", "", str_replace("https://", "", get_site_url())),
        'Wordpress-Plugin' => WC_Linet::VERSION,
      ),
      'body' => json_encode($body),
    );

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      $logger->write('Request failed:' . " $error_message\n");
    }

    $body = wp_remote_retrieve_body($response);


    $logger->write('LINET RESPONSE:' . $body . "\n");

    //unset($body);
    unset($login_id);
    unset($hash);
    unset($company);
    unset($ch);
    unset($server);
    unset($req);
    return json_decode($body);
  }

  public static function TestAjax()
  {
    $genral_item = (string) get_option('wc_linet_genral_item');

    $genral_item = ($genral_item == "") ? "1" : $genral_item;
    $res = self::sendAPI('view/item?id=' . $genral_item);

    echo json_encode($res);
    wp_die();

  }




  public static function RulerAjax()
  {
    $res = self::sendAPI('rulers');

    if (
      $res &&
      isset($res->body) &&
      is_array($res->body)
    ) {
      $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

      foreach ($res->body as $ruler) {
        WC_LI_Inventory::syncRuler($ruler, $logger);
      }
    }

    delete_option("_transient_wc_attribute_taxonomies");
    echo json_encode('ok');

    wp_die();
  }


  public static function income_acc()
  {
    $income_acc = get_option('wc_linet_income_acc');

    if (!$income_acc)
      return 100;
    return $income_acc;
  }

  public static function income_acc_novat()
  {
    $income_acc_novat = get_option('wc_linet_income_acc_novat');

    if (!$income_acc_novat)
      return 102;
    return $income_acc_novat;
  }

  public static function genral_item()
  {
    $genral_item = (string) get_option('wc_linet_genral_item');

    if (!$genral_item)
      return 1;
    return $genral_item;
  }


}

//end class
