<?php
/*
Plugin Name: WooCommerce Linet Integration
Plugin URI: https://github.com/adam2314/woocommerce-linet
Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
Author: Speedcomp
Author URI: http://www.linet.org.il
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

class WC_LI_Invoice_Manager
{

  /**
   * @var WC_LI_Settings
   */
  private $settings;

  /**
   * WC_LI_Invoice_Manager constructor.
   *
   * @param WC_LI_Settings $settings
   */
  public function __construct(WC_LI_Settings $settings)
  {
    $this->settings = $settings;
  }

  /**
   * Method to setup the hooks
   */
  public function setup_hooks()
  {
    // Check if we need to send invoices when they're completed automatically
    $do_doc = get_option('wc_linet_sync_orders');

    $doctype = get_option('wc_linet_linet_doc');

    if ('none' !== $do_doc && '' !== $do_doc) {
      update_option("wc_linet_sync_orders_wc-$do_doc", $doctype);

      delete_option('wc_linet_sync_orders');
      delete_option('wc_linet_linet_doc');

    }

    foreach (wc_get_order_statuses() as $key => $name) {

      $do_doc = get_option("wc_linet_sync_orders_$key");
      if ('none' !== $do_doc && '' !== $do_doc) {

        $skey = str_replace("wc-", "", $key);
        //var_dump($do_doc);
        //var_dump("woocommerce_order_status_$skey");
        add_action("woocommerce_order_status_$skey", array($this, 'auto_send_invoice'));
      }
    }

    //add_action("woocommerce_order_status_wc-on-hold", array($this, 'auto_send_invoice'));
    //exit;

    add_action('manage_edit-shop_order_columns', array($this, 'order_download_column_header'), 20);
    add_filter('manage_shop_order_posts_custom_column', array($this, 'order_pdf_column_content'), 10, 3);

  }



  public static function order_download_column_header($clmns)
  {

    foreach ($clmns as $name => $info) {
      $next_clmns[$name] = $info;
      if ('wc_actions' === $name) {
        $next_clmns['linet_link_column'] = __('Linet Document', 'wc-linet');
      }
    }
    //var_dump($clmns);exit;
    return $next_clmns;

  }

  public static function order_pdf_column_content($column, $post_id)
  {
    if ('linet_link_column' === $column) {

      //global $post;
      //$post_id = $post->ID;

      if (!$post_id)
        return;

      $order = wc_get_order($post_id);


      $doc_id = $order->get_meta("_linet_invoice_id", true);


      if (!$doc_id)
        return;
      $doc_url = $order->get_meta("_linet_doc_url", true);

      $docnum = $order->get_meta("_linet_docnum", true);


      if ($doc_url) {
        return self::create_pdf_link_html_tag($doc_url, $docnum);
      }

      $doc_url = self::get_doc_url($doc_id, $order);
      if (!$doc_url)
        return;

      return self::create_pdf_link_html_tag($doc_url, $docnum);
    }
  }

  public static function get_doc_url($doc_id, $order)
  {
    $doc_url = false;

    if (!$doc_id)
      return;

    $res = WC_LI_Settings::sendAPI("print/doc/$doc_id", ['href' => 1]);

    if ($res->status == 200 && $res->text == 'OK' && $res->errorCode == 0) {
      $doc_url = (string) $res->body;
      $order->update_meta_data( '_linet_doc_url',  (string) $doc_url );
      $order->save();

    }
    return $doc_url;

  }

  public static function create_pdf_link_html_tag($doc_url = "#hash", $docnum = '')
  {
    $alt = __('Download Document', 'wc-linet');

    $base = plugin_dir_url("") . "linet-erp-woocommerce-integration";
    echo "<a href='$doc_url' title='$alt ($docnum)' target='_blank'>
    	       <img src='$base/assets/pdf.png' alt='$alt' style='width: 30px; height: 30px;'>
            </a>
            ";
  }

  /**
   * Send invoice to LINET API
   *
   * @param int $order_id
   *
   * @return bool
   */
  public function send_invoice($order_id, $doctype = null)
  {
    //var_dump('send_invoice');
    //exit;
    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

    // Get the order
    $order = wc_get_order($order_id);


    if (is_null($doctype)) {
      $doctype = (int) get_option("wc_linet_sync_orders_wc-" . $order->get_status());
      if ($doctype === 0) {

        $order->add_order_note(
          __('wont create Linet doc: ', 'wc-linet') .
          __(' status: ', 'wc-linet') . $order->get_status() .
          __(' not mapped to doctype', 'wc-linet')

        );

        return false;
      }
    }




    try {
      // Do the request

      //$logger->write('OWER REQUEST:' . "\n" .print_r($invoice->to_array(),true));

      // Get the invoice
      $invoice = $this->get_invoice_by_order($order, $doctype);




      // Check if the order total is 0 and if we need to send 0 total invoices to Linet
      /*
      if (0 == $invoice->get_total() && 'on' !== $this->settings->get_option('export_zero_amount')) {
      //if ( 0 == $invoice->get_total() && 'on' !== $this->settings->get_option( 'export_zero_amount' ) ) {
      $logger->write('INVOICE HAS TOTAL OF 0, NOT SENDING ORDER WITH ID ' . $order->get_id());
      $order->add_order_note(__("LINET: Didn't create doc. because total is 0 and send order with zero total is set to off.", 'wc-linet'));
      return false;
      }
      */

      // Invoice Request
      //$invoice_request = new WC_LI_Request_Invoice( $this->settings, $invoice );
      // Logging
      $logger->write('START LINET NEW doc. order_id=' . $order->get_id() . " To " . $doctype);


      // Try to do the request

      $json_response = $invoice->do_request($doctype);


      if ($json_response === false) {
        return false;
      }

      // Check response status
      if ('200' == $json_response->status && '0' == $json_response->errorCode) {

        // Add order meta data

        $order->update_meta_data( '_linet_doc_id',  (string)  $json_response->body->id );
        $order->update_meta_data( '_linet_docnum',  (string) $json_response->body->docnum );

        $order->update_meta_data( '_linet_invoice_id',  (string) $json_response->body->id );
        $order->update_meta_data( '_linet_currency_rate',  (string) $json_response->body->currency_rate );
        $order->save();

        // Log response
        //$logger->write('LINET RESPONSE:' . "\n" .print_r($json_response,true));

        // Add Order Note
        $order->add_order_note(__('Linet Doc. created.  ', 'wc-linet') . ' Doc. num: ' . (string) $json_response->body->docnum);
      } else { // XML reponse is not OK
        // Log reponse
        $logger->write('LINET ERROR RESPONSE:' . "\n" . print_r($json_response, true));

        $to = get_option('admin_email');

        $subject = __('ERROR creating Linet doc: ', 'wc-linet') .
          __(' ErrorNumber: ', 'wc-linet') . $json_response->status .
          __(' ErrorType: ', 'wc-linet') . $json_response->errorCode .
          __(' Message: ', 'wc-linet') . $json_response->text;

        $message = __(' Order: ', 'wc-linet') . $order->get_id() . __(' Detail: ', 'wc-linet') . json_encode($json_response->body);

        wp_mail($to, $subject, $message);
        wp_mail('ops@linet.org.il', get_site_url() . " - " . $subject, $message);


        // Format error message
        //$error_message = $xml_response->Elements->DataContractBase->ValidationErrors->ValidationError->Message ? $xml_response->Elements->DataContractBase->ValidationErrors->ValidationError->Message : __('None', 'wc-linet');

        // Add order note
        $order->add_order_note(__('ERROR creating Linet doc: ', 'wc-linet') .
          __(' ErrorNumber: ', 'wc-linet') . $json_response->status .
          __(' ErrorType: ', 'wc-linet') . $json_response->errorCode .
          __(' Message: ', 'wc-linet') . $json_response->text .
          __(' Detail: ', 'wc-linet') . json_encode($json_response->body));
      }
    } catch (Exception $e) {
      // Add Exception as order note
      $order->add_order_note($e->getMessage());

      $logger->write($e->getMessage());

      return false;
    }

    $logger->write('END LINET NEW doc.');

    return true;
  }

  public function auto_send_invoice($order_id, $doctype = null)
  {

    // Get the order
    $order = wc_get_order($order_id);
    $supported_gateways = $this->settings->get_option('supported_gateways');
    if (!in_array($order->get_payment_method(), $supported_gateways)) {
      $order->add_order_note(__("LINET: Will not create doc. unsupported gateway", 'wc-linet'));
      return false;
    }


    return self::send_invoice($order_id, $doctype);

  }

  /**
   * Get invoice by order
   *
   * @param WC_Order $order
   *
   * @return WC_LI_Invoice
   */
  public function get_invoice_by_order($order, $doctype)
  {

    // Line Item manager
    // Contact Manager
    //$contact_manager = new WC_LI_Contact_Manager( $this->settings );
    // Create invoice
    $invoice = new WC_LI_Invoice();

    $invoice->set_order($order, $doctype);

    // Return invoice
    return $invoice;
  }

}