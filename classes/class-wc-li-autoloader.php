<?php
/*
Plugin Name: WooCommerce Linet Integration
Plugin URI: https://github.com/adam2314/woocommerce-linet
Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
Author: Speedcomp
Author URI: http://www.linet.org.il
Version: 3.6.0
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

class WC_LI_Autoloader
{

  private $path;

  /**
   * The Constructor, sets the path of the class directory.
   *
   * @param $path
   */
  public function __construct($path)
  {
    $this->path = $path;
  }

  /**
   * Autoloader load method. Load the class.
   *
   * @param $class_name
   */
  public function load($class_name)
  {

    // Only autoload WooCommerce Sales Report Email classes
    if (0 === strpos($class_name, 'WC_LI_')) {

      // String to lower
      $class_name = strtolower($class_name);

      // Format file name
      $file_name = 'class-wc-li-' . str_ireplace('_', '-', str_ireplace('WC_LI_', '', $class_name)) . '.php';

      // Setup the file path
      $file_path = $this->path;

      // Check if we need to extend the class path
      if (strpos($class_name, 'wc_li_request') === 0) {
        $file_path .= 'requests/';
      }

      // Append file name to clas path
      $file_path .= $file_name;

      // Check & load file
      if (file_exists($file_path)) {
        require_once($file_path);
      }
    }
  }

}