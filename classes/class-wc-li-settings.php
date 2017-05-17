<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 0.93
  Text Domain: wc-linet
  Domain Path: /languages/
  Requires WooCommerce: 2.2

  Copyright 2016  Adam Ben Hour

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

    // Settings defaults
    private $settings = array();
    private $override = array();

    public function __construct($override = null) {



        if (!wp_next_scheduled('linetItemSync')) {
            wp_schedule_event(time(), 'hourly', 'linetItemSync');
        }

        add_action('init', 'WC_LI_Settings::StartSession', 1);
        add_action('wp_logout', 'WC_LI_Settings::EndSession');
        add_action('wp_login', 'WC_LI_Settings::EndSession');
        add_action('linetItemSync', 'WC_LI_Settings::catSync');

        add_action('wp_ajax_LinetItemSync', 'WC_LI_Settings::catSyncAjax');
        add_action('wp_ajax_LinetTest', 'WC_LI_Settings::TestAjax');
        add_action('wp_ajax_CatList', 'WC_LI_Settings::CatListAjax');
        add_action('wp_ajax_CatDet', 'WC_LI_Settings::CatDetAjax');

        if ($override !== null) {
            $this->override = $override;
        }
        $supported_gateways = array();




        // Set the settings
        $this->settings = array(
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
            'genral_acc' => array(
                'title' => __('General Custemer Account', 'wc-linet'),
                'default' => '0',
                'type' => 'text',
                'description' => __('Enter 0 for auto create account', 'wc-linet'),
            ),
            'genral_item' => array(
                'title' => __('General Item', 'wc-linet'),
                'default' => '1',
                'type' => 'text',
                'description' => __('Code for Linet general Item ', 'wc-linet'),
            ),
            'warehouse_id' => array(
                'title' => __('Warehouse', 'wc-linet'),
                'default' => '115',
                'type' => 'text',
                'description' => __('Warehouse ', 'wc-linet'),
            ),
            // Misc settings
            /*
              'send_invoices'      => array(
              'title'       => __( 'Send Invoices', 'wc-linet' ),
              'default'     => 'manual',
              'type'        => 'select',
              'description' => __(  'Send Invoices manually (from the order\'s action menu), on creation (when the order is created), or on completion (when order status is changed to completed).', 'wc-linet' ),
              'options'     => array(
              'manual'   => __( 'Manually', 'wc-linet' ),
              'creation' => __( 'On Order Creation', 'wc-linet' ),
              'on'       => __( 'On Order Completion', 'wc-linet' ),
              ),
              ),
             */
            /*
              'send_payments'      => array(
              'title'       => __( 'Send Payments', 'wc-linet' ),
              'default'     => 'off',
              'type'        => 'select',
              'description' => __(  'Send Payments manually or automatically when order is completed. This may need to be turned off if you sync via a separate integration such as PayPal.', 'wc-linet' ),
              'options'     => array(
              'off' => __( 'Manually', 'wc-linet' ),
              'on'  => __( 'On Order Completion', 'wc-linet' ),
              ),
              ),

              'export_zero_amount' => array(
              'title' => __('Orders with zero total', 'wc-linet'),
              'default' => 'off',
              'type' => 'checkbox',
              'description' => __('Export orders with zero total.', 'wc-linet'),
              ),
             *
             */
            'sync_items' => array(
                'title' => __('Sync Items', 'wc-linet'),
                'default' => 'on',
                'type' => 'checkbox',
                'description' => __('Use Linet to sync items', 'wc-linet') .
                ' <a href="#target" onclick="linet.trySend();">Manual Items Sync</a>' .
                "<div id='mItems' class='hidden'>" .
                '
                <div id="target"></div>
                <progress id="targetBar" max="100" value="0"></progress>
                <div id="subTarget"></div>
                <progress id="subTargetBar" max="100" value="0"></progress>' .
                "</div>"
            ,
            ),
            /*
            'update_cat' => array(
                'title' => __('Update Catagories', 'wc-linet'),
                'default' => '',
                'type' => 'text',
                'description' => __('Use Linet to sync items', 'wc-linet') .
                ' <a href="#getList" onclick="linet.getList();">Get List</a>' .
                "<ul id='catList' class=''>" .
                ' ' .
                "</ul>"
            ),*/
            'last_update' => array(
                'title' => __('Last Update Time', 'wc-linet'),
                'default' => '',
                'type' => 'text',
                'description' => __('Last Update Time ', 'wc-linet'),
                'options' => array(
                    'readonly' => true,
                )
            ),
            /*
              'cat_select' => array(
              'title' => __('Categroy Select', 'wc-linet'),
              'default' => 'off',
              'type' => 'checkbox',
              'description' => __('Find Linet items by SKU and not there Item ID', 'wc-linet'),
              ), */
            'sku_find' => array(
                'title' => __('SKU Find', 'wc-linet'),
                'default' => 'off',
                'type' => 'checkbox',
                'description' => __('Find Linet items by SKU and not there Item ID', 'wc-linet'),
            ),
            'sync_orders' => array(
                'title' => __('Sync Orders', 'wc-linet'),
                'default' => 'none',
                //type' => 'checkbox',
                'type' => 'select',
                'options' => array(
                    'none' => __('Manually', 'wc-linet'),
                    'processing' => __('On Creation', 'wc-linet'),
                    'completed' => __('On Completion', 'wc-linet'),
                ),
                'description' => __('Auto Genrate Invoice Recipet in Linet', 'wc-linet'),
            ),
            'linet_doc' => array(
                'title' => __('Linet Document Type', 'wc-linet'),
                'default' => '9',
                //type' => 'checkbox',
                'type' => 'select',
                'options' => array(
                    '1' => __('Performa', 'wc-linet'),
                     '2' => __('Delivery Doc.', 'wc-linet'),
                    '3' => __('Invoice', 'wc-linet'),

                    '7' => __('Sales Order', 'wc-linet'),
                    '8' => __('Receipt', 'wc-linet'),
                    '9' => __('Invoice Receipt', 'wc-linet'),
                    '17' => __('Stock Exist Doc.', 'wc-linet'),

                ),

                //'options' => $supported_gateways,
                'description' => __('Select linet document type', 'wc-linet'),
            ),

            'supported_gateways' => array(
                'title' => __('Supported Gateways', 'wc-linet'),
                'default' => '',
                //type' => 'checkbox',
                'type' => 'pay_list',
                //'options' => $supported_gateways,
                'description' => __('Select Gateways to invoice', 'wc-linet'),
            ),
            'stock_manage' => array(
                'title' => __('Stock Manage', 'wc-linet'),
                'default' => 'on',
                'type' => 'checkbox',
                'description' => __('Use Linet to sync the stock level of items', 'wc-linet'),
            ),
            'debug' => array(
                'title' => __('Debug', 'wc-linet'),
                'default' => 'off',
                'type' => 'checkbox',
                'description' => __('Enable logging.  Log file is located at: /wc-logs/', 'wc-linet'),
            ),
            'dev' => array(
                'title' => __('Dev Mode', 'wc-linet'),
                'default' => 'off',
                'type' => 'checkbox',
                'description' => __('Will work aginst the dev server', 'wc-linet'),
            ),
        );
    }

    function StartSession() {
        if (!session_id()) {
            session_start();
        }
    }

    function EndSession() {
        session_destroy();
    }

    /**
     * Setup the required settings hooks
     */
    public function setup_hooks() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_menu_item'));
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

        return get_option(self::OPTION_PREFIX . $key, $this->settings[$key]['default']);
    }

    /**
     * settings_init()
     *
     * @access public
     * @return void
     */
    public function register_settings() {

        //self::catSync();
        // Add section
        add_settings_section('wc_linet_settings', __('Linet Settings', 'wc-linet'), array(
            $this,
            'settings_intro'
                ), 'woocommerce_linet');

        // Add setting fields
        foreach ($this->settings as $key => $option) {

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
        ?>
        <div class="wrap woocommerce">
            <form method="post" id="mainform" action="options.php">
                <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br/></div>
                <h2><?php _e('Linet for WooCommerce', 'wc-linet'); ?></h2>

                <?php
                if (isset($_GET['settings-updated']) && ( $_GET['settings-updated'] == 'true' )) {
                    echo '<div id="message" class="updated fade"><p><strong>' . __('Your settings have been saved.', 'wc-linet') . '</strong></p></div>';
                } else if (isset($_GET['settings-updated']) && ( $_GET['settings-updated'] == 'false' )) {
                    echo '<div id="message" class="error fade"><p><strong>' . __('There was an error saving your settings.', 'wc-linet') . '</strong></p></div>';
                }
                ?>


                <a href="#target1" onclick="linet.doTest();">Test Connection</a> (You can Check The Connection Only After Saving)
                <?php settings_fields('woocommerce_linet'); ?>
        <?php do_settings_sections('woocommerce_linet'); ?>



                <p class="submit"><input type="submit" class="button-primary" value="Save"/></p>
                <script>
                    linet = {
                        catDet: function (response) {
                            jQuery('#catValue' + response.id).html(response.wc_count + "/" + response.linet_count);


                        },
                        getList: function () {
                            var data = {
                                'action': 'CatList',
                                //'mode': 1
                            };


                            jQuery.post(ajaxurl, data, function (response) {
                                //console.log(response);
                                jQuery('#catList').html("");
                                //console.log(response.body.length);
                                for (i = 0; i < response.body.length; i++) {
                                    //console.log(response.body[i].name);
                                    jQuery('#catList').append("<li>" + response.body[i].name + " <span id='catValue" + response.body[i].id + "'></span></li>");

                                    jQuery.post(ajaxurl, {
                                        'action': 'CatDet',
                                        'id': response.body[i].id,
                                        'catName': response.body[i].name
                                    }, function (response) {
                                        linet.catDet(response);

                                    }, 'json');

                                }


                                //console.log(response);
                                //alert(response.text);
                                //count

                            }, 'json');


                        },
                        doTest: function () {
                            var data = {
                                'action': 'LinetTest',
                                //'mode': 1
                            };


                            jQuery.post(ajaxurl, data, function (response) {
                                //console.log(response);
                                console.log(response);
                                alert(response.text);
                                //count

                            }, 'json');


                        },
                        doCall: function (num) {
                            var data = {
                                'action': 'LinetItemSync',
                                'mode': 1
                            };
                            max = jQuery('#targetBar').attr("max");
                            if (num)
                                jQuery.post(ajaxurl, data, function (response) {
                                    //console.log(response);

                                    bar = max - num;

                                    jQuery('#target').html("Categories Processed:  " + bar + "/" + max + "");
                                    jQuery('#targetBar').val(bar);



                                    jQuery('#subTarget').html("Item Processed: 0/" + response + "");
                                    jQuery('#subTargetBar').attr("max", 1 * response);

                                    linet.subCall(num - 1, 0);
                                    //count

                                });


                        },
                        trySend: function () {
                            var data = {
                                'action': 'LinetItemSync',
                                'mode': 0
                            };
                            jQuery('#mItems').removeClass('hidden');

                            jQuery.post(ajaxurl, data, function (response) {
                                jQuery('#target').html("Categories Processed:  0/" + response + "");
                                jQuery('#targetBar').attr("max", response);

                                linet.doCall(response);
                            });
                            return false
                        },
                        doCall: function (num) {
                            var data = {
                                'action': 'LinetItemSync',
                                'mode': 1
                            };
                            max = jQuery('#targetBar').attr("max");
                            if (num)
                                jQuery.post(ajaxurl, data, function (response) {
                                    //console.log(response);

                                    bar = max - num;

                                    jQuery('#target').html("Categories Processed:  " + bar + "/" + max + "");
                                    jQuery('#targetBar').val(bar);



                                    jQuery('#subTarget').html("Item Processed: 0/" + response + "");
                                    jQuery('#subTargetBar').attr("max", 1 * response);

                                    linet.subCall(num - 1, 0);
                                    //count

                                });


                        },
                                subCall: function (catnum, barnum) {
                                    var data = {
                                        'action': 'LinetItemSync',
                                        'mode': 2
                                    };

                                    submax = 1 * jQuery('#subTargetBar').attr("max");

                                    //console.log("Catagory:" + catnum + " Items:" + barnum + " Max:" + submax);
                                    if (barnum >= submax) {

                                        linet.doCall(catnum);
                                    } else {
                                        jQuery.post(ajaxurl, data, function (response) {
                                            //console.log("Items Ammount:"+response);

                                            barnum += (1) * (response);

                                            jQuery('#subTarget').html("Item Processed: " + barnum + "/" + submax + "");
                                            jQuery('#subTargetBar').val(barnum);
                                            //console.log("Item Processed:" + barnum + "/" + submax);
                                            linet.subCall(catnum, barnum);
                                        });


                                    }
                                    //next cat

                                }


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
        echo '<p>' . __('Settings for your Linet account including security keys and default account numbers.<br/> <strong>All</strong> text fields are required for the integration to work properly.', 'wc-linet') . '</p>';
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
        echo '<p class="description">' . esc_html($args['option']['description']) . '</p>';
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
            if(in_array($key, $option)){
                $selected='selected="selected"';
            }
            //$selected = selected($option, $key, false);
            $text = esc_html($value);
            $val = esc_attr($key);
            echo "<option value='$val' $selected>$text</option>";
        }

        echo '</select>';
        echo '<p class="description">' . esc_html($args['option']['description']) . '</p>';
    }

    public static function sendAPI($req, $body = []) {


        $server = self::SERVER;
        $dev = get_option('wc_linet_dev');
        if ($dev == 'on') {
            $server = self::DEV_SERVER;
        }

        //$dev = get_option('wc_linet_dev');
        //echo json_encode($server);
        //wp_die();

        $login_id = get_option('wc_linet_consumer_id');
        $hash = get_option('wc_linet_consumer_key');
        $company = get_option('wc_linet_company');

        $body['login_id'] = $login_id;
        $body['login_hash'] = $hash;
        $body['login_company'] = $company;


        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $server . "/api/" . $req,
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($body)
        ));

        $response = curl_exec($ch);
        return json_decode($response);
    }

    public static function TestAjax() {

        $genral_item = get_option('wc_linet_genral_item');
        $res = self::sendAPI('view/item?id=' . $genral_item);
        echo json_encode($res);
        wp_die();
    }

    public static function CatListAjax() {
        //$genral_item = get_option('wc_linet_genral_item');
        $res = self::sendAPI('search/itemcategory');
        echo json_encode($res);
        wp_die();
    }

    public static function CatDetAjax() {
        //$genral_item = get_option('wc_linet_genral_item');
        //$res = self::sendAPI('search/itemcategory');
        $id = intval($_POST['id']);

        $products = self::sendAPI('search/item', ['category_id' => $id]);




        global $wpdb;
        $prefix = mysql_real_escape_string($wpdb->prefix);
        $catName = mysql_real_escape_string($_POST['catName']);
        $catName = $_POST['catName'];


        $query = "SELECT * FROM `" . $prefix . "term_taxonomy` " .
                " LEFT JOIN `" . $prefix . "postmeta` ON `" . $prefix . "postmeta`.post_id=`" . $prefix .
                "posts`.ID where `" . $prefix . "posts`.post_type='product' and `" . $prefix . "posts`.post_status = 'publish' and `" . $prefix .
                "postmeta`.meta_key='_linet_id' and `" . $prefix . "postmeta`.meta_value='" . $itemId . "';" .
                "LEFT JOIN `" . $prefix . "term_taxonomy` ON `" . $prefix . "term_taxonomy`.term_id=`" . $prefix . "terms`.term_id " .
                "where `" .
                //"postmeta`.meta_key='_linet_id' and `" . $prefix . "postmeta`.meta_value='" . $itemId . "';";
                $prefix . "term_taxonomy`.taxonomy='product_cat' and `" . $prefix . "terms`.name='" . $catName . "';";
        $product_id = $wpdb->get_results($query);

        //echo $query;

        $arr = array(
            'id' => $id,
            'linet_count' => count($products->body),
            'wc_count' => 'na'
        );


        if (count($product_id) != 0) {
            $term_id = $product_id[0]->term_id;


            $arr['wc_count'] = get_term_meta($term_id, 'product_count_product_cat');
        }//else{
        //    $arr['wc_count']='NA';
        //}















        echo json_encode($arr);
        wp_die();
    }

    public static function catSyncAjax() {

        // this is how you get access to the database

        $mode = intval($_POST['mode']);
        //$logger = new WC_LI_Logger($this->settings);

        if ($mode == 0) {
            update_option('wc_linet_last_update', date('d/m/Y H:m:i'));
            $cats = self::sendAPI('search/itemcategory');
            //$_SESSION['body'] = $cats->body;
            $_SESSION['body'] = $cats->body;

            echo count($cats->body);
            wp_die();
        }


        $cats = $_SESSION['body'];
        if ($mode == 1) {
            //var_dump($cats);wp_die();
            $cat = array_pop($cats);
            $_SESSION['body'] = $cats;

            $wp_cat_id = self::singleCatSync($cat);

            $warehouse = get_option('wc_linet_warehouse_id');


            $products = self::sendAPI('stockall/item?warehouse_id=' . $warehouse, ['category_id' => $cat->id]);
            $_SESSION['wp_cat_id'] = $wp_cat_id;
            $_SESSION['products'] = $products->body;


            echo count($products->body);
            wp_die();
        }

        if ($mode == 2) {
            $wp_cat_id = $_SESSION['wp_cat_id'];
            $products = $_SESSION['products'];
            $limit = 30;
            $count = count($products) - 1;

            for ($i = 0; $i <= $count; $i++) {
                if ($i >= $limit) {
                    break;
                }


                $prod = array_pop($products);
                self::singleProdSync($wp_cat_id, $prod);
            }


            $_SESSION['products'] = $products;
            echo $i;

            wp_die();
        }









        //}
        //end loop
    }

    public static function singleCatSync($cat) {
        global $wpdb;

        $prefix = mysql_real_escape_string($wpdb->prefix);
        $catName = mysql_real_escape_string($cat->name);


        $query = "SELECT * FROM `" . $prefix . "terms` LEFT JOIN `" . $prefix . "term_taxonomy` ON `" . $prefix .
                "term_taxonomy`.term_id=`" . $prefix . "terms`.term_id where `" . $prefix .
                "term_taxonomy`.taxonomy='product_cat' and `" . $prefix . "terms`.name='" . $catName . "';";
        $product_id = $wpdb->get_results($query);

        if (count($product_id) != 0) {
            $term_id = $product_id[0]->term_id;
        } else {
            $term_id = wp_insert_term($cat->name, 'product_cat', array(
                'slug' => $cat->name,
                'name' => $cat->name,
            ));

            update_term_meta($term_id, 'order', '');
            update_term_meta($term_id, 'display_type', '');
            update_term_meta($term_id, 'thumbnail_id', '');
            update_term_meta($term_id, 'product_count_product_cat', '');
        }



        wp_update_term($term_id, 'product_cat', [

            'name' => $cat->name,
            'slug' => $cat->name
        ]);


        update_term_meta($term_id, '_linet_cat', $cat->id);


        return $term_id;
    }

    public static function singleProdSync($wp_cat_id, $item) {
        $user_id = 1;
        $stockManage = get_option('wc_linet_stock_manage');

        global $wpdb;
        $prefix = $wpdb->_real_escape($wpdb->prefix);
        $itemId = $wpdb->_real_escape($item->item->id);


        $query = "SELECT * FROM `" . $prefix . "posts` LEFT JOIN `" . $prefix . "postmeta` ON `" . $prefix . "postmeta`.post_id=`" . $prefix .
                "posts`.ID where `" . $prefix . "posts`.post_type='product' and `" . $prefix . "posts`.post_status = 'publish' and `" . $prefix .
                "postmeta`.meta_key='_linet_id' and `" . $prefix . "postmeta`.meta_value='" . $itemId . "';";

        $product_id = $wpdb->get_results($query);

        if (count($product_id) == 0) {

            $post_id = wp_insert_post(array(
                'post_author' => $user_id,
                'post_title' => $item->item->name,
                'post_content' => $item->item->description,
                'post_status' => 'publish',
                'post_type' => "product",
            ));

            update_post_meta($post_id, 'total_sales', '0');
            update_post_meta($post_id, '_visibility', 'visible');
            update_post_meta($post_id, '_stock_status', 'instock');

            update_post_meta($post_id, '_downloadable', 'no');
            update_post_meta($post_id, '_virtual', 'no');

            update_post_meta($post_id, '_purchase_note', '');
            update_post_meta($post_id, '_featured', 'no');
            update_post_meta($post_id, '_weight', '');
            update_post_meta($post_id, '_length', '');
            update_post_meta($post_id, '_width', '');
            update_post_meta($post_id, '_height', '');

            update_post_meta($post_id, '_product_attributes', array());
            update_post_meta($post_id, '_sale_price_dates_from', '');
            update_post_meta($post_id, '_sale_price_dates_to', '');

            update_post_meta($post_id, '_sold_individually', '');
            update_post_meta($post_id, '_backorders', 'no');
        } else {
            $post_id = $product_id[0]->ID;
            wp_update_post(['ID' => $post_id, 'post_title' => $item->item->name, 'post_content' => $item->item->description]);
        }


        wp_set_object_terms($post_id, 'simple', 'product_type');

        update_post_meta($post_id, '_sku', $item->item->sku);
        update_post_meta($post_id, '_linet_id', $item->item->id);

        update_post_meta($post_id, '_price', $item->item->saleprice);
        update_post_meta($post_id, '_regular_price', $item->item->saleprice);
        update_post_meta($post_id, '_sale_price', $item->item->saleprice);


        if ($stockManage == 'on') {
            update_post_meta($post_id, '_manage_stock', 'yes');

            update_post_meta($post_id, '_stock', $item->qty);
        } else {
            update_post_meta($post_id, '_manage_stock', 'no');

            update_post_meta($post_id, '_stock', '');
        }

        $res = wp_set_post_terms($post_id, [$wp_cat_id], 'product_cat');
    }

    public static function prodSync($linet_cat_id, $wp_cat_id, $logger) {

        $user_id = 1;
        $stockManage = get_option('wc_linet_stock_manage');
        $warehouse = get_option('wc_linet_warehouse_id');


        $products = self::sendAPI('stockall/item?warehouse_id=' . $warehouse, ['category_id' => $linet_cat_id]);
        $products = $products->body;


        foreach ($products as $item) {
            self::singleProdSync($wp_cat_id, $item);


            $logger->write("Linet Item Id: " . $item->id . " was synced");
        }//end each
    }

    public static function catSync() {
        update_option('wc_linet_last_update', date('d/m/Y H:m:i'));
        $cats = self::sendAPI('search/itemcategory');
        $cats = $cats->body;


        $logger = new WC_LI_Logger($this->settings);
        $logger->write("Start Linet Cat Sync");
        foreach ($cats as $cat) {



            global $wpdb;

            $prefix = mysql_real_escape_string($wpdb->prefix);
            $catName = mysql_real_escape_string($cat->name);


            $query = "SELECT * FROM `" . $prefix . "terms` LEFT JOIN `" . $prefix . "term_taxonomy` ON `" . $prefix .
                    "term_taxonomy`.term_id=`" . $prefix . "terms`.term_id where `" . $prefix .
                    "term_taxonomy`.taxonomy='product_cat' and `" . $prefix . "terms`.name='" . $catName . "';";
            //echo $query;exit;

            $product_id = $wpdb->get_results($query);

            if (count($product_id) != 0) {
                //echo "aa";

                $term_id = $product_id[0]->term_id;
            } else {
                //echo "jj";
                $term_id = wp_insert_term($cat->name, 'product_cat', array(
                    //'post_author' => $user_id,
                    'slug' => $cat->name,
                    'name' => $cat->name,
                        //'post_content' => $item->description,
                        //'post_status' => 'publish',
                        //'post_type' => "product",
                ));
            }



            wp_update_term($term_id, 'product_cat', [

                'name' => $cat->name,
                'slug' => $cat->name
            ]);


            update_term_meta($term_id, '_linet_cat', $cat->id);
            update_term_meta($term_id, 'order', '');
            update_term_meta($term_id, 'display_type', '');
            update_term_meta($term_id, 'thumbnail_id', '');
            update_term_meta($term_id, 'product_count_product_cat', '');
            $logger->write("Linet Cat ID:" . $cat->id);
            self::prodSync($cat->id, $term_id, $logger);
            /*
              order
              display_type
              thumbnail_id

             */
        }


        $logger->write("End Linet Cat Sync");
    }

//end func
}

//end class
