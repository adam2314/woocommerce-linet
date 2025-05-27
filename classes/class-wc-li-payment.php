<?php

if (!defined('ABSPATH')) {
  exit;
} // Exit if accessed directly

class WC_LI_Payment
{


  public function process()
  {
    switch ($this->order->payment_method) {
      case 'cod':
        $docCheq = [
          [
            "type" => 1,
            "currency_id" => $currency_id,
            //"currency_rate" => "1",
            "sum" => $total,
            "doc_sum" => $total,
            "line" => 1
          ]
        ];
        break;

      case 'ppec_paypal':
      case 'paypal':
        $docCheq = [
          [
            "type" => 8, //paypal
            "currency_id" => $currency_id,
            //"currency_rate" => "1",
            //add credit card
            //add auth number
            "sum" => $total,
            "doc_sum" => $total,
            "line" => 1
          ]
        ];
        break;
      case 'gotopay':
        $docCheq = [
          [
            "type" => 3,
            "currency_id" => $currency_id,
            //"currency_rate" => "1",
            //add credit card
            //add auth number
            "sum" => $total,
            "doc_sum" => $total,
            "line" => 1
          ]
        ];
        break;
      case 'pelacard':
        $docCheq = [
          [
            "type" => 3,
            "currency_id" => $currency_id,
            //"currency_rate" => "1",
            //add credit card
            //add auth number
            "sum" => $total,
            "doc_sum" => $total,
            "line" => 1
          ]
        ];
        break;
      default:
        $docCheq = [
          [
            "type" => 3,
            "currency_id" => $currency_id,
            //"currency_rate" => "1",
            "sum" => $total,
            "doc_sum" => $total,
            "line" => 1
          ]
        ];
        break;
    }

    return $docCheq;

  }





  private $invoice_id = '';
  private $code = '';
  private $date = '';
  private $currency_rate = '';
  private $amount = 0;
  private $order = null;

  /**
   * @return string
   */
  public function get_invoice_id()
  {
    return apply_filters('woocommerce_linet_payment_invoice_id', $this->invoice_id, $this);
  }

  /**
   * @param string $invoice_id
   */
  public function set_invoice_id($invoice_id)
  {
    $this->invoice_id = $invoice_id;
  }

  /**
   * @return string
   */
  public function get_code()
  {
    return apply_filters('woocommerce_linet_payment_code', $this->code, $this);
  }

  /**
   * @param string $code
   */
  public function set_code($code)
  {
    $this->code = $code;
  }

  /**
   * @return string
   */
  public function get_date()
  {
    return apply_filters('woocommerce_linet_payment_date', $this->date, $this);
  }

  /**
   * @param string $date
   */
  public function set_date($date)
  {
    $this->date = $date;
  }

  /**
   * @return string
   */
  public function get_currency_rate()
  {
    return apply_filters('woocommerce_linet_payment_currency_rate', $this->currency_rate, $this);
  }

  /**
   * @param string $currency_rate
   */
  public function set_currency_rate($currency_rate)
  {
    $this->currency_rate = $currency_rate;
  }

  /**
   * @return int
   */
  public function get_amount()
  {
    return apply_filters('woocommerce_linet_payment_amount', $this->amount, $this);
  }

  /**
   * @param int $amount
   */
  public function set_amount($amount)
  {
    $this->amount = floatval($amount);
  }

  /**
   * @return WC_Order
   */
  public function get_order()
  {
    return $this->order;
  }

  /**
   * @param WC_Order $order
   */
  public function set_order($order)
  {
    $this->order = $order;
  }


}