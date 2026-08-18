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
          //'description' => esc_html_e('Enter the linet ID', 'linet-erp-woocommerce-integration'),
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
      'ignore_supported_gateways' => array(
        'title' => __('Ignore Supported Gateways', 'linet-erp-woocommerce-integration'),
        'default' => 'off',
        'type' => 'select',
        'options' => array(
          'off' => __('Off', 'linet-erp-woocommerce-integration'),
          'on' => __('On', 'linet-erp-woocommerce-integration'),
        ),
        'description' => __('When enabled, invoices will be created for all gateways regardless of the Supported Gateways selection', 'linet-erp-woocommerce-integration'),
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


  /**
   * The maintenance tab deletes products and files, so make sure the request
   * really comes from somebody who is allowed to manage the shop.
   *
   * @return bool
   */
  private static function canMaintain()
  {
    if (current_user_can('manage_woocommerce')) {
      return true;
    }

    wp_send_json_error(
      array('message' => __('You are not allowed to run maintenance actions.', 'linet-erp-woocommerce-integration')),
      403
    );

    return false;
  }

  public static function LinetGetFile()
  {
    if (!current_user_can('manage_woocommerce')) {
      wp_die(esc_html__('You are not allowed to read the log files.', 'linet-erp-woocommerce-integration'), '', array('response' => 403));
    }

    $filtered = preg_replace('/[^A-Za-z0-9.-]/', '', $_POST['name']);
    $filtered = preg_replace('/\.+/', '.', $filtered);

    echo esc_html(file_get_contents(WC_LOG_DIR . $filtered));
    wp_die();
  }


  public static function LinetDeleteFile()
  {
    self::canMaintain();

    $filtered = preg_replace('/[^A-Za-z0-9.-]/', '', $_POST['name']);
    $filtered = preg_replace('/\.+/', '.', $filtered);

    wp_delete_file(WC_LOG_DIR . $filtered);

    wp_send_json_success(
      array(
        /* translators: %s: log file name */
        'message' => sprintf(__('%s was deleted.', 'linet-erp-woocommerce-integration'), $filtered),
      )
    );
  }

  /**
   * Maintenance actions on products.
   *
   * key=id      delete one product or variation
   * key=ids     delete a list of products or variations
   * key=unlink  keep the products, only drop their _linet_id
   * key=_sku    legacy: keep the oldest product with that sku, delete the rest
   */
  public static function LinetDeleteProd()
  {
    self::canMaintain();

    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

    $key = isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '';
    $value = isset($_POST['value']) ? sanitize_text_field(wp_unslash($_POST['value'])) : '';

    $logger->write("admin maintenance action $key: $value");

    if ('' === $value) {
      wp_send_json_error(array('message' => __('Nothing to do, no product was given.', 'linet-erp-woocommerce-integration')));
    }

    if ('unlink' === $key) {
      $cleared = 0;

      foreach (self::idList($value) as $post_id) {
        if (delete_post_meta($post_id, '_linet_id')) {
          $cleared++;
          wc_delete_product_transients($post_id);
        }
      }

      wp_send_json_success(
        array(
          /* translators: %d: number of products */
          'message' => sprintf(_n('Linet ID cleared from %d product.', 'Linet ID cleared from %d products.', $cleared, 'linet-erp-woocommerce-integration'), $cleared),
        )
      );
    }

    if ('id' === $key || 'ids' === $key) {
      $ids = self::idList($value);
      $deleted = array();
      $failed = array();

      foreach ($ids as $post_id) {
        if (self::DeleteProd(wc_get_product($post_id), $logger)) {
          $deleted[] = $post_id;
        } else {
          $failed[] = $post_id;
        }
      }

      if (empty($deleted)) {
        wp_send_json_error(
          array(
            /* translators: %s: list of post IDs */
            'message' => sprintf(__('Nothing was deleted (%s).', 'linet-erp-woocommerce-integration'), implode(', ', $ids)),
          )
        );
      }

      /* translators: %d: number of products */
      $message = sprintf(_n('%d product deleted.', '%d products deleted.', count($deleted), 'linet-erp-woocommerce-integration'), count($deleted));

      if ($failed) {
        /* translators: %s: list of post IDs */
        $message .= ' ' . sprintf(__('Could not delete: %s.', 'linet-erp-woocommerce-integration'), implode(', ', $failed));
      }

      wp_send_json_success(array('message' => $message));
    }

    // Legacy: a meta key and its value. Keep the oldest match, delete the rest.
    $products = wc_get_products(
      array(
        'limit' => 50,
        'orderby' => 'ID',
        'order' => 'ASC',
        'meta_key' => $key,
        'meta_value' => $value,
      )
    );

    $deleted = 0;
    $first = true;

    foreach ($products as $product) {
      if ($first) {
        $first = false;
        continue;
      }

      if (self::DeleteProd($product, $logger)) {
        $deleted++;
      }
    }

    wp_send_json_success(
      array(
        /* translators: %d: number of products */
        'message' => sprintf(_n('%d duplicate deleted.', '%d duplicates deleted.', $deleted, 'linet-erp-woocommerce-integration'), $deleted),
      )
    );
  }

  /**
   * Turn a comma separated list of post IDs into a clean array.
   *
   * @param string $value
   *
   * @return array
   */
  private static function idList($value)
  {
    $ids = array_map('intval', explode(',', $value));
    $ids = array_filter(
      $ids,
      function ($id) {
        return $id > 0;
      }
    );

    return array_values(array_unique($ids));
  }

  /**
   * @param WC_Product|null $product
   * @param WC_LI_Logger    $logger
   *
   * @return bool
   */
  public static function DeleteProd($product, $logger)
  {
    if (empty($product)) {
      $logger->write("not found prod");

      return false;
    }

    $post_id = $product->get_id();

    $logger->write("found prod $post_id");

    $deleted = $product->delete(true);

    wc_delete_product_transients($post_id);

    return (bool) $deleted;
  }



  public static function LinetDeleteAttachment($id = 0)
  {
    self::canMaintain();

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if (!$id || !wp_delete_attachment($id)) {
      wp_send_json_error(array('message' => __('The image could not be deleted.', 'linet-erp-woocommerce-integration')));
    }

    wp_send_json_success(
      array(
        /* translators: %d: attachment ID */
        'message' => sprintf(__('Image #%d was deleted.', 'linet-erp-woocommerce-integration'), $id),
      )
    );
  }

  public static function LinetCalcAttachment($id = 0)
  {
    self::canMaintain();

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $pic = isset($_POST['file']) ? (int) $_POST['file'] : 0;

    $basePath = wp_upload_dir()['basedir'] . '/';
    $realtivePath = WC_LI_Inventory::IMAGE_DIR . "/" . $pic;
    $filePath = $basePath . $realtivePath;

    // Prefer the file WordPress has on record, the path above is only the
    // guess for images that came in from Linet.
    $attached = $id ? get_attached_file($id) : '';

    if ($attached && file_exists($attached)) {
      $filePath = $attached;
      $realtivePath = str_replace($basePath, '', $attached);
    }

    if (!$id || !file_exists($filePath)) {
      wp_send_json_error(
        array(
          /* translators: %s: path of the missing file */
          'message' => sprintf(__('The file is missing on the server (%s), so the image sizes cannot be rebuilt.', 'linet-erp-woocommerce-integration'), $realtivePath),
        )
      );
    }

    $meta = wp_generate_attachment_metadata($id, $filePath);

    if (empty($meta)) {
      wp_send_json_error(array('message' => __('WordPress could not read the image.', 'linet-erp-woocommerce-integration')));
    }

    wp_update_attachment_metadata($id, $meta);

    wp_send_json_success(
      array(
        /* translators: %d: attachment ID */
        'message' => sprintf(__('The image sizes of #%d were rebuilt.', 'linet-erp-woocommerce-integration'), $id),
      )
    );
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




  /**
   * How many rows each maintenance section lists before it is truncated.
   */
  const MAINTENANCE_LIMIT = 100;

  /**
   * Markup allowed inside a maintenance row.
   */
  const MAINTENANCE_TAGS = array(
    'a' => array(
      'href' => true,
      'class' => true,
      'target' => true,
      'rel' => true,
      'onclick' => true,
      'title' => true,
      'data-key' => true,
      'data-value' => true,
      'data-label' => true,
      'data-id' => true,
      'data-name' => true,
      'data-file' => true,
    ),
    'p' => array('class' => true),
    'div' => array('class' => true),
    'span' => array('class' => true),
    'ul' => array('class' => true),
    'li' => array('class' => true),
    'code' => array(),
    'strong' => array(),
    'em' => array(),
    'br' => array(),
  );

  /**
   * The "Maintenance" tab.
   *
   * Every problem is listed together with links to the WooCommerce products it
   * belongs to, so a product can be inspected before anything is deleted.
   */
  public function maintenance()
  {
    $arr = array();

    $arr = array_merge($arr, $this->maintenanceDuplicateMeta('_sku', __('Duplicate SKU', 'linet-erp-woocommerce-integration'), __('SKU', 'linet-erp-woocommerce-integration')));
    $arr = array_merge($arr, $this->maintenanceDuplicateMeta('_linet_id', __('Duplicate Linet ID', 'linet-erp-woocommerce-integration'), __('Linet ID', 'linet-erp-woocommerce-integration')));
    $arr = array_merge($arr, $this->maintenanceDuplicateVariations());
    $arr = array_merge($arr, $this->maintenanceAttachments());

    if (empty($arr)) {
      $arr['maint_clean'] = array(
        'title' => esc_html__('Products', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'maint',
        'html' => '<p class="description">' . esc_html__('No duplicate SKUs, Linet IDs, variations or broken images were found.', 'linet-erp-woocommerce-integration') . '</p>',
      );
    }

    $arr = array_merge($arr, $this->maintenanceLogFiles());

    return $arr;
  }

  /**
   * Products that share the same value in a meta field that has to be unique.
   *
   * @param string $meta_key  Meta field to group by, e.g. _sku.
   * @param string $label     Row title.
   * @param string $friendly  Name of the field as shown to the user.
   *
   * @return array
   */
  private function maintenanceDuplicateMeta($meta_key, $label, $friendly)
  {
    $arr = array();
    $groups = $this->findDuplicateMeta($meta_key);

    $truncated = count($groups) > self::MAINTENANCE_LIMIT;
    $groups = array_slice($groups, 0, self::MAINTENANCE_LIMIT);

    $prefix = 'dup' . str_replace('_', '', $meta_key);

    foreach ($groups as $index => $group) {
      $ids = $group->ids;

      if (count($ids) < 2) {
        continue;
      }

      $keep = array_shift($ids);

      $html = '<p class="description">' . sprintf(
        /* translators: 1: number of products, 2: name of the field, e.g. SKU */
        esc_html(_n('%1$d product uses this %2$s.', '%1$d products use this %2$s.', $group->num, 'linet-erp-woocommerce-integration')),
        (int) $group->num,
        esc_html($friendly)
      ) . ' ' . esc_html__('It has to be unique, so only the oldest product should keep it.', 'linet-erp-woocommerce-integration') . '</p>';

      $html .= $this->postListHtml($keep, $ids);

      $html .= '<p>' . $this->maintenanceAction(
        'ids',
        implode(',', $ids),
        sprintf(
          /* translators: 1: number of products, 2: post ID that is kept */
          _n('Delete %1$d duplicate product (keep #%2$d)', 'Delete %1$d duplicate products (keep #%2$d)', count($ids), 'linet-erp-woocommerce-integration'),
          count($ids),
          $keep
        ),
        'button'
      );

      if ('_linet_id' === $meta_key) {
        $html .= ' ' . $this->maintenanceAction(
          'unlink',
          implode(',', $ids),
          __('Keep the products, only clear their Linet ID', 'linet-erp-woocommerce-integration'),
          'button'
        );
      }

      $html .= '</p>';

      $arr[$prefix . $index] = array(
        'title' => esc_html($label) . '<br /><code>' . esc_html($group->value) . '</code>',
        'default' => '',
        'type' => 'maint',
        'html' => $html,
      );
    }

    if ($truncated) {
      $arr[$prefix . 'more'] = array(
        'title' => esc_html($label),
        'default' => '',
        'type' => 'maint',
        'html' => '<p class="description">' . sprintf(
          /* translators: %d: number of rows shown */
          esc_html__('Only the first %d groups are listed. Handle them and reload this page to see the rest.', 'linet-erp-woocommerce-integration'),
          self::MAINTENANCE_LIMIT
        ) . '</p>',
      );
    }

    return $arr;
  }

  /**
   * Variations of the same product that carry the same set of attributes.
   *
   * @return array
   */
  private function maintenanceDuplicateVariations()
  {
    $arr = array();
    $groups = $this->findDuplicateVariations();

    $truncated = count($groups) > self::MAINTENANCE_LIMIT;
    $groups = array_slice($groups, 0, self::MAINTENANCE_LIMIT);

    foreach ($groups as $index => $group) {
      $ids = $group->ids;

      if (count($ids) < 2) {
        continue;
      }

      $keep = array_shift($ids);
      $parent = get_post((int) $group->parent);
      $parent_title = ($parent && '' !== $parent->post_title) ? $parent->post_title : __('(no title)', 'linet-erp-woocommerce-integration');

      $html = '<p class="description">' . sprintf(
        /* translators: 1: number of variations, 2: parent product title */
        esc_html(_n('%1$d variation of %2$s has the same attributes.', '%1$d variations of %2$s have the same attributes.', $group->num, 'linet-erp-woocommerce-integration')),
        (int) $group->num,
        '<strong>' . esc_html($parent_title) . '</strong>'
      ) . ' ' . esc_html__('WooCommerce only ever sells the first one, the others just sit in the database.', 'linet-erp-woocommerce-integration') . '</p>';

      $html .= '<p>' . esc_html__('Parent product:', 'linet-erp-woocommerce-integration') . ' ' . $this->postSummary((int) $group->parent, false) . '</p>';

      if ('' !== trim((string) $group->variation)) {
        $html .= '<p class="description">' . esc_html__('Attributes:', 'linet-erp-woocommerce-integration') . ' <code>' . esc_html($group->variation) . '</code></p>';
      }

      $html .= $this->postListHtml($keep, $ids);

      $html .= '<p>' . $this->maintenanceAction(
        'ids',
        implode(',', $ids),
        sprintf(
          /* translators: 1: number of variations, 2: post ID that is kept */
          _n('Delete %1$d duplicate variation (keep #%2$d)', 'Delete %1$d duplicate variations (keep #%2$d)', count($ids), 'linet-erp-woocommerce-integration'),
          count($ids),
          $keep
        ),
        'button'
      ) . '</p>';

      $arr['vari' . $index] = array(
        'title' => esc_html__('Duplicate variation', 'linet-erp-woocommerce-integration') . '<br /><code>#' . (int) $group->parent . '</code>',
        'default' => '',
        'type' => 'maint',
        'html' => $html,
      );
    }

    if ($truncated) {
      $arr['varimore'] = array(
        'title' => esc_html__('Duplicate variation', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'maint',
        'html' => '<p class="description">' . sprintf(
          /* translators: %d: number of rows shown */
          esc_html__('Only the first %d groups are listed. Handle them and reload this page to see the rest.', 'linet-erp-woocommerce-integration'),
          self::MAINTENANCE_LIMIT
        ) . '</p>',
      );
    }

    return $arr;
  }

  /**
   * Images that were never processed by WordPress, so no thumbnails exist.
   *
   * @return array
   */
  private function maintenanceAttachments()
  {
    global $wpdb;

    $arr = array();

    $attachments = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT p.ID, p.post_title
           FROM {$wpdb->posts} p
      LEFT JOIN {$wpdb->postmeta} pm
             ON pm.post_id = p.ID AND pm.meta_key = '_wp_attachment_metadata'
          WHERE p.post_type = 'attachment'
            AND pm.meta_value IS NULL
       ORDER BY p.ID ASC
          LIMIT %d",
        self::MAINTENANCE_LIMIT + 1
      )
    );

    $truncated = count($attachments) > self::MAINTENANCE_LIMIT;
    $attachments = array_slice($attachments, 0, self::MAINTENANCE_LIMIT);

    foreach ($attachments as $index => $attachment) {
      $id = (int) $attachment->ID;
      $used_by = $this->productsUsingAttachment($id);

      $html = '<p class="description">' . esc_html__('WordPress has no image sizes for this file, so it shows up broken or full size in the shop. Rebuild it if the file is still on disk, delete it if it is not.', 'linet-erp-woocommerce-integration') . '</p>';

      $html .= '<p>' . $this->postSummary($id, false) . '</p>';

      if ($used_by) {
        $html .= '<p class="description">' . esc_html__('Used by:', 'linet-erp-woocommerce-integration') . '</p><ul class="linet-maint-list">';
        foreach ($used_by as $product_id) {
          $html .= '<li>' . $this->postSummary($product_id, false) . '</li>';
        }
        $html .= '</ul>';
      } else {
        $html .= '<p class="description">' . esc_html__('No product uses this image.', 'linet-erp-woocommerce-integration') . '</p>';
      }

      $html .= '<p>' . sprintf(
        '<a class="button" href="#" data-id="%1$d" data-file="%2$s" data-label="%3$s" onclick="linet.calcAttachment(event,this);">%4$s</a> <a class="button" href="#" data-id="%1$d" data-label="%5$s" onclick="linet.deleteAttachment(event,this);">%6$s</a>',
        $id,
        esc_attr($attachment->post_title),
        esc_attr__('Rebuild the image sizes', 'linet-erp-woocommerce-integration'),
        esc_html__('Rebuild the image sizes', 'linet-erp-woocommerce-integration'),
        esc_attr__('Delete this image', 'linet-erp-woocommerce-integration'),
        esc_html__('Delete this image', 'linet-erp-woocommerce-integration')
      ) . '</p>';

      $arr['attachment' . $index] = array(
        'title' => esc_html__('Image without sizes', 'linet-erp-woocommerce-integration') . '<br /><code>' . esc_html($attachment->post_title) . '</code>',
        'default' => '',
        'type' => 'maint',
        'html' => $html,
      );
    }

    if ($truncated) {
      $arr['attachmentmore'] = array(
        'title' => esc_html__('Image without sizes', 'linet-erp-woocommerce-integration'),
        'default' => '',
        'type' => 'maint',
        'html' => '<p class="description">' . sprintf(
          /* translators: %d: number of rows shown */
          esc_html__('Only the first %d images are listed. Handle them and reload this page to see the rest.', 'linet-erp-woocommerce-integration'),
          self::MAINTENANCE_LIMIT
        ) . '</p>',
      );
    }

    return $arr;
  }

  /**
   * Linet and fatal error log files.
   *
   * @return array
   */
  private function maintenanceLogFiles()
  {
    $arr = array();

    if (!is_dir(WC_LOG_DIR)) {
      return $arr;
    }

    $files = array_diff(scandir(WC_LOG_DIR), array('..', '.'));

    foreach ($files as $index => $file) {
      if (0 !== strpos($file, 'linet') && 0 !== strpos($file, 'fatal-errors')) {
        continue;
      }

      $path = WC_LOG_DIR . $file;
      $meta = array();

      if (is_readable($path)) {
        $meta[] = size_format(filesize($path));
        $meta[] = sprintf(
          /* translators: %s: date the log file was last written to */
          __('last written %s', 'linet-erp-woocommerce-integration'),
          date_i18n(get_option('date_format') . ' H:i', filemtime($path))
        );
      }

      $html = '<p>' . sprintf(
        '<a href="#" onclick="linet.getFile(\'%1$s\'); return false;">%2$s</a>',
        esc_js($file),
        esc_html__('Download', 'linet-erp-woocommerce-integration')
      );

      if ($meta) {
        $html .= ' <span class="description">(' . esc_html(implode(', ', $meta)) . ')</span>';
      }

      $html .= '</p><p>' . sprintf(
        '<a class="button" href="#" data-name="%1$s" data-label="%2$s" onclick="linet.deleteFile(event,this);">%3$s</a>',
        esc_attr($file),
        esc_attr__('Delete this log file', 'linet-erp-woocommerce-integration'),
        esc_html__('Delete this log file', 'linet-erp-woocommerce-integration')
      ) . '</p>';

      $arr['file' . $index] = array(
        'title' => esc_html__('Log file', 'linet-erp-woocommerce-integration') . '<br /><code>' . esc_html($file) . '</code>',
        'default' => '',
        'type' => 'maint',
        'html' => $html,
      );
    }

    return $arr;
  }

  /**
   * Group products by a meta value that should be unique.
   *
   * @param string $meta_key
   *
   * @return array Objects with value, num and ids.
   */
  private function findDuplicateMeta($meta_key)
  {
    global $wpdb;

    $groups = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT pm.meta_value AS value, COUNT(*) AS num
           FROM {$wpdb->postmeta} pm
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
          WHERE pm.meta_key = %s
            AND pm.meta_value <> ''
            AND p.post_type IN ('product', 'product_variation')
            AND p.post_status NOT IN ('trash', 'auto-draft')
       GROUP BY pm.meta_value
         HAVING num > 1
       ORDER BY num DESC, pm.meta_value ASC
          LIMIT %d",
        $meta_key,
        self::MAINTENANCE_LIMIT + 1
      )
    );

    if (empty($groups)) {
      return array();
    }

    $values = wp_list_pluck($groups, 'value');
    $placeholders = implode(',', array_fill(0, count($values), '%s'));

    $rows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT pm.post_id, pm.meta_value AS value
           FROM {$wpdb->postmeta} pm
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
          WHERE pm.meta_key = %s
            AND pm.meta_value IN ($placeholders)
            AND p.post_type IN ('product', 'product_variation')
            AND p.post_status NOT IN ('trash', 'auto-draft')
       ORDER BY pm.post_id ASC",
        array_merge(array($meta_key), $values)
      )
    );

    $ids = array();
    foreach ($rows as $row) {
      $ids[$row->value][] = (int) $row->post_id;
    }

    foreach ($groups as $group) {
      $group->num = (int) $group->num;
      $group->ids = isset($ids[$group->value]) ? $ids[$group->value] : array();
    }

    return $groups;
  }

  /**
   * Variations of the same parent that share the same attribute combination.
   *
   * @return array Objects with parent, variation, num and ids.
   */
  private function findDuplicateVariations()
  {
    global $wpdb;

    $groups = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT p.post_parent AS parent,
                MIN(p.post_excerpt) AS variation,
                COUNT(*) AS num,
                GROUP_CONCAT(p.ID ORDER BY p.ID ASC) AS ids
           FROM {$wpdb->posts} p
     INNER JOIN (
                SELECT pm.post_id,
                       GROUP_CONCAT(CONCAT(pm.meta_key, '=', pm.meta_value) ORDER BY pm.meta_key SEPARATOR '|') AS attributes
                  FROM {$wpdb->postmeta} pm
                 WHERE pm.meta_key LIKE 'attribute\_%%'
              GROUP BY pm.post_id
                ) a ON a.post_id = p.ID
          WHERE p.post_type = 'product_variation'
            AND p.post_parent <> 0
            AND p.post_status NOT IN ('trash', 'auto-draft')
       GROUP BY p.post_parent, a.attributes
         HAVING num > 1
       ORDER BY p.post_parent ASC
          LIMIT %d",
        self::MAINTENANCE_LIMIT + 1
      )
    );

    foreach ($groups as $group) {
      $group->parent = (int) $group->parent;
      $group->num = (int) $group->num;
      $group->ids = $this->validVariationIds($group->ids, $group->parent);
    }

    return $groups;
  }

  /**
   * Keep only the IDs that really are variations of the given parent.
   *
   * GROUP_CONCAT is capped by group_concat_max_len, so the last ID of a very
   * long list can come back truncated. Never hand such an ID to a delete link.
   *
   * @param string $ids
   * @param int    $parent
   *
   * @return array
   */
  private function validVariationIds($ids, $parent)
  {
    $valid = array();

    foreach (explode(',', (string) $ids) as $id) {
      $id = (int) $id;
      $post = $id ? get_post($id) : null;

      if ($post && 'product_variation' === $post->post_type && (int) $post->post_parent === $parent) {
        $valid[] = $id;
      }
    }

    sort($valid);

    return $valid;
  }

  /**
   * Products that show the given attachment as their image.
   *
   * @param int $attachment_id
   *
   * @return array
   */
  private function productsUsingAttachment($attachment_id)
  {
    global $wpdb;

    $ids = $wpdb->get_col(
      $wpdb->prepare(
        "SELECT DISTINCT pm.post_id
           FROM {$wpdb->postmeta} pm
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
          WHERE p.post_type IN ('product', 'product_variation')
            AND (
                 (pm.meta_key = '_thumbnail_id' AND pm.meta_value = %s)
                 OR (pm.meta_key = '_product_image_gallery' AND (
                      pm.meta_value = %s
                      OR pm.meta_value LIKE %s
                      OR pm.meta_value LIKE %s
                      OR pm.meta_value LIKE %s
                 ))
            )
          LIMIT 10",
        $attachment_id,
        $attachment_id,
        $wpdb->esc_like($attachment_id . ',') . '%',
        '%' . $wpdb->esc_like(',' . $attachment_id . ',') . '%',
        '%' . $wpdb->esc_like(',' . $attachment_id)
      )
    );

    return array_map('intval', $ids);
  }

  /**
   * A "kept" product followed by the duplicates that can be removed.
   *
   * @param int   $keep
   * @param array $duplicates
   *
   * @return string
   */
  private function postListHtml($keep, $duplicates)
  {
    $html = '<ul class="linet-maint-list">';

    $html .= '<li>' . $this->postSummary($keep, false)
      . ' <span class="linet-maint-keep">' . esc_html__('kept', 'linet-erp-woocommerce-integration') . '</span></li>';

    foreach ($duplicates as $id) {
      $html .= '<li>' . $this->postSummary($id, false) . ' '
        . $this->maintenanceAction('id', $id, __('Delete', 'linet-erp-woocommerce-integration'))
        . '</li>';
    }

    return $html . '</ul>';
  }

  /**
   * One line describing a post, linked to its edit screen and to the shop.
   *
   * @param int  $post_id
   * @param bool $short
   *
   * @return string
   */
  private function postSummary($post_id, $short = true)
  {
    $post_id = (int) $post_id;
    $post = get_post($post_id);

    if (!$post) {
      return '<code>#' . $post_id . '</code> <em>' . esc_html__('this post no longer exists', 'linet-erp-woocommerce-integration') . '</em>';
    }

    $title = ('' !== $post->post_title) ? $post->post_title : __('(no title)', 'linet-erp-woocommerce-integration');
    $is_variation = ('product_variation' === $post->post_type);
    $link_id = ($is_variation && $post->post_parent) ? (int) $post->post_parent : $post_id;
    $edit_link = get_edit_post_link($link_id, '');
    $view_link = get_permalink($link_id);

    $html = '<code>#' . $post_id . '</code> ';

    if ($edit_link) {
      $html .= '<a href="' . esc_url($edit_link) . '" target="_blank" rel="noopener">' . esc_html($title) . '</a>';
    } else {
      $html .= esc_html($title);
    }

    if ($short) {
      return $html;
    }

    $meta = array();

    if ($is_variation) {
      /* translators: %d: ID of the parent product */
      $meta[] = sprintf(__('variation of #%d', 'linet-erp-woocommerce-integration'), (int) $post->post_parent);
    } else {
      $meta[] = $post->post_type;
    }

    $meta[] = $post->post_status;

    $sku = get_post_meta($post_id, '_sku', true);
    if ('' !== $sku) {
      /* translators: %s: product SKU */
      $meta[] = sprintf(__('SKU %s', 'linet-erp-woocommerce-integration'), $sku);
    }

    $linet_id = get_post_meta($post_id, '_linet_id', true);
    if ('' !== $linet_id) {
      /* translators: %s: item ID in Linet */
      $meta[] = sprintf(__('Linet ID %s', 'linet-erp-woocommerce-integration'), $linet_id);
    }

    $html .= ' <span class="description">(' . implode(' &middot; ', array_map('esc_html', $meta)) . ')</span>';

    if ($view_link && 'attachment' !== $post->post_type) {
      $html .= ' <a href="' . esc_url($view_link) . '" target="_blank" rel="noopener">' . esc_html__('View in shop', 'linet-erp-woocommerce-integration') . '</a>';
    }

    return $html;
  }

  /**
   * A link that asks the browser to run one of the maintenance actions.
   *
   * @param string $key    Action the ajax handler understands.
   * @param string $value  Payload, usually a list of post IDs.
   * @param string $text   Link text, also used in the confirmation.
   * @param string $class  Extra css class.
   *
   * @return string
   */
  private function maintenanceAction($key, $value, $text, $class = '')
  {
    return sprintf(
      '<a class="%1$s" href="#" data-key="%2$s" data-value="%3$s" data-label="%4$s" onclick="linet.deleteProd(event,this);">%5$s</a>',
      esc_attr(trim('linet-maint-action ' . $class)),
      esc_attr($key),
      esc_attr($value),
      esc_attr($text),
      esc_html($text)
    );
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
      <?php $cat_meta = WC_LI_Inventory::CAT_META; ?>
      <?php echo esc_html(isset($metas[$cat_meta]) && $metas[$cat_meta]['0'] ? $metas[$cat_meta]["0"] : "No Linet ID") ?><br />
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
        wp_enqueue_script('wp-api');
        wp_localize_script('wp-api', 'wpApiSettings', array(
          'root' => esc_url_raw(rest_url()),
          'nonce' => wp_create_nonce('wp_rest')
        ));


      }



      $metas = get_post_meta($post->ID);
      ?>
      
      <div class="linetDataAndAction">
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
      </div>
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

      // Maintenance rows are reports, they have nothing to store.
      if ('maint' === $option['type']) {
        continue;
      }

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
          wp_enqueue_script('wp-api');
          wp_localize_script('wp-api', 'wpApiSettings', array(
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
          ));


        }



        ?>


        <p class="submit"><input type="submit" class="button-primary" value="Save" /></p>

        <style>
          .linet-maint p {
            margin: 0 0 6px;
          }

          .linet-maint code {
            background: #f0f0f1;
          }

          .linet-maint-list {
            margin: 0 0 10px;
            padding: 0;
            list-style: none;
          }

          .linet-maint-list li {
            margin: 0;
            padding: 3px 0;
            border-bottom: 1px solid #f0f0f1;
          }

          .linet-maint-list li:last-child {
            border-bottom: 0;
          }

          .linet-maint-keep {
            color: #007017;
            font-weight: 600;
          }

          .linet-maint-list .linet-maint-action {
            color: #b32d2e;
          }

          .linet-maint-busy {
            pointer-events: none;
            opacity: 0.6;
          }

          .linet-maint-done {
            color: #007017;
          }

          .linet-maint-error {
            color: #b32d2e;
          }
        </style>

        <script>
          linet = {
            catDet: function (response) {
              jQuery('#catValue' + response.id).html(response.wc_count + "/" + response.linet_count);
            },


            maintText: {
              confirm: '<?php echo esc_js(__('%s — are you sure? This cannot be undone.', 'linet-erp-woocommerce-integration')); ?>',
              working: '<?php echo esc_js(__('Working…', 'linet-erp-woocommerce-integration')); ?>',
              done: '<?php echo esc_js(__('Done.', 'linet-erp-woocommerce-integration')); ?>',
              failed: '<?php echo esc_js(__('The request failed, nothing was changed.', 'linet-erp-woocommerce-integration')); ?>'
            },

            // Every maintenance call goes through here, so the nonce and the
            // feedback are handled in one place.
            maintPost: function (data) {
              return jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                <?php if ($nonce): ?>
                beforeSend: function (xhr) {
                  xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                <?php endif; ?>
                data: data
              });
            },

            // Ask before deleting anything, using the label on the link.
            maintConfirm: function (obj) {
              var label = jQuery(obj).data('label') || jQuery.trim(jQuery(obj).text());

              return window.confirm(linet.maintText.confirm.replace('%s', label));
            },

            // Replace the clicked link with the result, and fade the row when
            // the thing it pointed at is gone.
            maintResult: function (obj, message, ok) {
              var link = jQuery(obj);
              var row = link.closest('li');

              if (!row.length) {
                row = link.closest('tr');
              }

              link.replaceWith(
                jQuery('<span/>')
                  .addClass(ok ? 'linet-maint-done' : 'linet-maint-error')
                  .text(message)
              );

              if (ok) {
                row.css('opacity', 0.5);
              }
            },

            maintRun: function (e, obj, data, skipConfirm) {
              if (e) {
                e.preventDefault();
              }

              if (!skipConfirm && !linet.maintConfirm(obj)) {
                return false;
              }

              jQuery(obj).addClass('linet-maint-busy').text(linet.maintText.working);

              linet.maintPost(data).done(function (response) {
                var ok = !(response && response.success === false);
                var message = (response && response.data && response.data.message)
                  ? response.data.message
                  : linet.maintText.done;

                linet.maintResult(obj, message, ok);
              }).fail(function () {
                linet.maintResult(obj, linet.maintText.failed, false);
              });

              return false;
            },

            deleteAttachment: function (e, obj) {
              return linet.maintRun(e, obj, {
                'action': 'LinetDeleteAttachment',
                'id': jQuery(obj).data('id')
              });
            },

            // Rebuilding image sizes changes nothing that cannot be redone,
            // so it does not ask for a confirmation.
            calcAttachment: function (e, obj) {
              return linet.maintRun(e, obj, {
                'action': 'LinetCalcAttachment',
                'file': jQuery(obj).data('file'),
                'id': jQuery(obj).data('id')
              }, true);
            },

            deleteProd: function (e, obj) {
              return linet.maintRun(e, obj, {
                'action': 'LinetDeleteProd',
                'key': jQuery(obj).data('key'),
                'value': String(jQuery(obj).data('value'))
              });
            },

            deleteFile: function (e, obj) {
              return linet.maintRun(e, obj, {
                'action': 'LinetDeleteFile',
                'name': jQuery(obj).data('name')
              });
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
              jQuery('#mItems').removeClass('hidden');
              linet.timeoutErrorCount = 0;

              //phase 1: all categories, then the items
              linet.wpCatSync(0);

              return false
            },

            wpCatSync: function (offset) {
              var data = {
                'action': 'WpItemSync',
                'mode': 2,
                'offset': offset
              };

              clearTimeout(linet.resumeTimeOut);

              linet.resumeTimeOut = setTimeout(
                () => {
                  linet.wpCatSync(offset);
                  linet.timeoutErrorCount++
                }, 1000 * 60
              )

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
                jQuery('#target').html("Categories:  " + response.offset + "/" + response.total);
                jQuery('#targetBar').prop('max', response.total || 1);
                jQuery('#targetBar').val(response.offset);

                if (!response.done && response.synced > 0) {
                  linet.wpCatSync(response.offset);
                } else {
                  clearTimeout(linet.resumeTimeOut);
                  linet.timeoutErrorCount = 0;
                  linet.prodSyncStart();
                }
              });
            },

            prodSyncStart: function () {
              var data = {
                'action': 'WpItemSync',
                'mode': 0
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
                jQuery('#target').html("Items:  0/" + response);
                jQuery('#targetBar').prop('max', response);
                jQuery('#targetBar').val(0);
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
   * One row of the maintenance tab. The html is built by maintenance() and is
   * already escaped, wp_kses only keeps the markup down to what is expected.
   *
   * @param array $args
   */
  public function input_maint($args)
  {
    $html = isset($args['option']['html']) ? $args['option']['html'] : '';

    echo '<div class="linet-maint">' . wp_kses($html, self::MAINTENANCE_TAGS) . '</div>';
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

    //$pay = new \WC_Payment_Gateways;
    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();


    foreach ($payment_gateways as $gateway_id => $gateway) {
      $opt['options'][$gateway_id] = $gateway->title;
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

    //$body['login_id'] = $login_id;
    //$body['login_hash'] = $hash;
    //$body['login_company'] = $company;

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
        'login-id'=> $login_id,
        'login-hash'=> $hash,
        'login-company'=> $company,

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
