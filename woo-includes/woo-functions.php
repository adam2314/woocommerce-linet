<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 0.7
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
/**
 * Functions used by plugins
 */
if (!class_exists('WC_Dependencies'))
    require_once 'class-wc-dependencies.php';

/**
 * WC Detection
 */
if (!function_exists('is_woocommerce_active')) {

    function is_woocommerce_active() {
        return WC_Dependencies::woocommerce_active_check();
    }

}
