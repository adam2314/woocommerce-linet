<?php
/*
Plugin Name: WooCommerce Linet Integration
Plugin URI: https://github.com/adam2314/woocommerce-linet
Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
Author: Speedcomp
Author URI: http://www.linet.org.il
Version: 3.5.2
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

class WC_LI_Inventory
{
  const IMAGE_DIR = 'images';


  /**
   * Setup the required settings hooks
   */
  public function setup_hooks()
  { //out

    //add_action('admin_init', array($this, 'register_settings'));
    //add_action('admin_menu', array($this, 'add_menu_item'));

    add_filter('manage_edit-product_cat_columns', array($this, 'category_columns_head'));
    add_filter('manage_product_cat_custom_column', array($this, 'category_columns'), 10, 3);
    add_filter('manage_edit-product_cat_sortable_columns', array($this, 'category_columns_sort'));

    add_action('manage_product_posts_custom_column', array($this, 'product_columns'), 10, 2);
    add_filter('manage_product_posts_columns', array($this, 'product_columns_head'));
    add_filter('manage_edit-product_sortable_columns', array($this, 'product_columns_sort'));
    add_action('pre_get_posts', array($this, 'linet_posts_orderby'));

  }


  public function category_columns_head($columns)
  {
    $columns['linet_id'] = __('Linet ID', 'wc-linet');
    //$columns['linet_actions'] = 'Linet Actions';
    return $columns;
  }

  public function category_columns_sort($columns)
  {
    $columns['linet_id'] = 'linet_id';
    return $columns;
  }

  public function category_columns($content, $column_name, $term_id)
  {
    if ($column_name == 'linet_id') {

      $linet_id = get_term_meta($term_id, '_linet_cat', true);

      return isset($linet_id) && !empty($linet_id) ?
        "<a target='_blank' href='https://app.linet.org.il/itemcategory/update?id=$linet_id'>$linet_id</a>"
        :
        $content;

    }
    return $content;
  }


  function product_columns($column, $post_id)
  {

    if ($column == 'linet_id') {
      $linet_id = get_post_meta($post_id, '_linet_id', true);
      echo "<a target='_blank' href='https://app.linet.org.il/item/update?id=$linet_id'>$linet_id</a>";
    }
    if ($column == 'linet_last_update') {
      echo get_post_meta($post_id, '_linet_last_update', true);
    }

  }

  function product_columns_head($clmns)
  {
    $clmns['linet_id'] = __('Linet ID', 'wc-linet');
    $clmns['linet_last_update'] = __('Linet Update', 'wc-linet');
    return $clmns;
  }

  function product_columns_sort($clmns)
  {
    $clmns['linet_id'] = '_linet_id';
    $clmns['linet_last_update'] = '_linet_last_update';
    return $clmns;
  }

  function linet_posts_orderby($query)
  {
    if (!is_admin() || !$query->is_main_query()) {
      return;
    }

    if ('_linet_id' === $query->get('orderby')) {
      $query->set('orderby', 'meta_value');
      $query->set('meta_key', '_linet_id');
      $query->set('meta_type', 'numeric');
    }
    if ('_linet_last_update' === $query->get('orderby')) {
      $query->set('orderby', 'meta_value');
      $query->set('meta_key', '_linet_last_update');
      $query->set('meta_type', 'DATE');
    }
  }




  public static function DeleteAjax()
  {
    global $wpdb;
    $wpdb->query();
    /*
    DELETE relations.*, taxes.*, terms.*
    FROM wp_term_relationships AS relations
    INNER JOIN wp_term_taxonomy AS taxes
    ON relations.term_taxonomy_id=taxes.term_taxonomy_id
    INNER JOIN wp_terms AS terms
    ON taxes.term_id=terms.term_id
    WHERE object_id IN (SELECT ID FROM wp_posts WHERE post_type='product');
    DELETE FROM wp_postmeta WHERE post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'product');
    DELETE FROM wp_posts WHERE post_type = 'product';
    DELETE FROM wp_postmeta WHERE post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'product_variation');
    DELETE FROM wp_posts WHERE post_type = 'product_variation';
    DELETE pm FROM wp_postmeta pm LEFT JOIN wp_posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL;
    DELETE a,c FROM wp_terms AS a
    LEFT JOIN wp_term_taxonomy AS c ON a.term_id = c.term_id
    LEFT JOIN wp_term_relationships AS b ON b.term_taxonomy_id = c.term_taxonomy_id
    WHERE c.taxonomy = 'product_tag';
    DELETE a,c FROM wp_terms AS a
    LEFT JOIN wp_term_taxonomy AS c ON a.term_id = c.term_id
    LEFT JOIN wp_term_relationships AS b ON b.term_taxonomy_id = c.term_taxonomy_id
    WHERE c.taxonomy = 'product_cat';
    */
    echo json_encode("Success");
    wp_die();

  }


  public static function CleanOrphAjax()
  {
    global $wpdb;
    $wpdb->query();
    /*
    DELETE pm
    FROM wp_postmeta pm
    LEFT JOIN wp_posts wp ON wp.ID = pm.post_id
    WHERE wp.ID IS NULL
    */
  }

  public static function CatListAjax()
  {
    //$genral_item = get_option('wc_linet_genral_item');
    $catFilter = self::syncCatParams();

    $res = WC_LI_Settings::sendAPI('newsearch/itemcategory', $catFilter);
    echo json_encode($res);
    wp_die();
  }

  public static function WpCatSyncAjax()
  {
    //$genral_item = get_option('wc_linet_genral_item');
    $cat_id = intval($_POST['id']);

    $products = WC_LI_Settings::sendAPI('search/item', array('category_id' => $cat_id));

    global $wpdb;
    $catName = $_POST['catName'];

    $query = "SELECT * FROM $wpdb->term_taxonomy " .
      " LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=" .
      "$wpdb->posts.ID WHERE $wpdb->posts.post_type='product' AND $wpdb->posts.post_status = 'publish' AND " .
      "$wpdb->postmeta.meta_key='_linet_id' and $wpdb->postmeta.meta_value='" . $cat_id . "';" .
      "LEFT JOIN $wpdb->term_taxonomy ON $wpdb->term_taxonomy.term_id=$wpdb->terms.term_id " .
      "WHERE " .
      "$wpdb->term_taxonomy.taxonomy='product_cat' AND $wpdb->terms.name=%s;";
    $product_id = $wpdb->get_col($wpdb->prepare($query, $catName));

    $arr = array(
      'id' => $cat_id,
      'linet_count' => count($products->body),
      'wc_count' => 'na'
    );

    if (count($product_id) != 0) {
      $term_id = $product_id[0]->term_id;
      $arr['wc_count'] = get_term_meta($term_id, 'product_count_product_cat');
    }

    echo json_encode($arr);
    wp_die();
  }

  public static function WpItemsSyncAjax()
  {
    global $wpdb;
    $mode = intval($_POST['mode']);
    //$logger = new WC_LI_Logger(get_option('wc_linet_debug'));
    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

    if ($mode == 0) {
      //count items to sync
      $query = "SELECT count(ID) FROM $wpdb->posts " .
        "WHERE " .
        "($wpdb->posts.post_type='product' OR $wpdb->posts.post_type='product_variation') AND " .

        "$wpdb->posts.post_status = 'publish' AND %d"; //or variation

      $counts = $wpdb->get_col($wpdb->prepare($query, array(1)));
      //var_dump($counts);
      $count = 0;

      //get all cats and sync

      if (count($counts) != 0) {
        $count = $counts[0] * 1;
      }

      $logger->write("Start WP->Linet Sync:$count");

      echo json_encode($count);

    } else {
      $offset = intval($_POST['offset']);
      $logger->write("WP->Linet Sync Pulse:$offset");
      echo json_encode(self::WpSmallItemsSyncAjax($offset, $logger));
    }
    wp_die();
  }

  public static function WpCatSync($item)
  { //wp->linet
    //$cat_id=0;

    $terms = wp_get_post_terms($item->ID, 'product_cat');
    $cats = array();
    if (is_array($terms) && count($terms) > 0) {

      foreach ($terms as $term) {

        $termsMeta = get_term_meta($term->term_id);

        if (isset($termsMeta['_linet_cat']) && isset($termsMeta['_linet_cat'][0])) {
          $linCat = WC_LI_Settings::sendAPI('view/itemcategory?id=' . $termsMeta['_linet_cat'][0]);
          //var_dump("_linet_cat search");
          //var_dump($linCat);exit;
          if ($linCat->errorCode == 0 && $linCat->status == 200)
            $cats[] = (int) $termsMeta['_linet_cat'][0];
        }

        $linCat = WC_LI_Settings::sendAPI('search/itemcategory', array('name' => $term->name));
        $catBody = array(
          'name' => $term->name,
          'profit' => 1,
        );
        if ($linCat->errorCode == 1000) {
          //create body pic?
          $linCat = WC_LI_Settings::sendAPI('create/itemcategory', $catBody);
          if ($linCat->errorCode == 0 && $linCat->status == 200) {
            update_term_meta($term->term_id, '_linet_cat', $linCat->body->id);
            $cats[] = (int) $linCat->body->id;
          }
        } else {
          $cat_id = $linCat->body[0]->id;
          //update body pic?
          update_term_meta($term->term_id, '_linet_cat', $cat_id);
          $cats[] = (int) $cat_id;
        }
      }
    }
    return array_unique($cats);
  }

  public static function WpSmallItemsSyncAjax($offset, $logger)
  {
    global $wpdb;
    $query = "SELECT * FROM $wpdb->posts " .
      //"INNER JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=" . "$wpdb->posts.ID ".
      "WHERE " .
      "($wpdb->posts.post_type='product' OR $wpdb->posts.post_type='product_variation') AND " .
      "$wpdb->posts.post_status = 'publish' " .
      "LIMIT " . WC_LI_Settings::STOCK_LIMIT . " OFFSET %d;";
    //$parent_id=$item->item->parent_item_id;
    $products = $wpdb->get_results($wpdb->prepare($query, $offset));
    $sync_count = 0;
    $runtime = microtime(true);

    foreach ($products as $product) {
      if (microtime(true) - $runtime < WC_LI_Settings::RUNTIME_LIMIT) {
        self::wpItemSync($product->ID, $logger);
        $sync_count++;

      }
    }
    //foreach
    //sleep(1);
    return $sync_count;

  }

  public static function linetSaveRuler($attr, $item_id, $line)
  {
    $typeBody = array('name' => str_replace("pa_", "", $attr->get_taxonomy())); //name

    $rulerBody = array('name' => $typeBody['name'], 'slug' => $typeBody['name']); //name

    $linItem = WC_LI_Settings::sendAPI('search/MutexRuler', $rulerBody);
    $rulerId = false;
    if ($linItem->errorCode == 1000) {
      //create body pic?
      $newLinItem = WC_LI_Settings::sendAPI('create/MutexRuler', $rulerBody);
      if ($newLinItem->errorCode == 0) {
        $rulerId = $newLinItem->body->id;
      }
    } else {
      $rulerId = $linItem->body[0]->id;
    }

    $typeMapBody = array(
      'item_id' => $item_id,
      'ruler_id' => $rulerId,
      'line' => $line
    );

    $linItem = WC_LI_Settings::sendAPI('search/MutexTypeMap', $typeMapBody);
    $typeMapId = false;
    if ($linItem->errorCode == 1000) {
      $newLinItem = WC_LI_Settings::sendAPI('create/MutexTypeMap', $typeMapBody);
      if ($newLinItem->errorCode == 0) {
        //$typeMapId = $newLinItem->body->id;
      }
    } else {
      //$typeMapId = $linItem->body[0]->id;

    }

    foreach ($attr->get_terms() as $term) {

      $slug = str_replace(" ", "", urldecode($term->slug));
      $slug = str_replace("-", "", $slug);
      $slug = str_replace("(", "", $slug);
      $slug = str_replace(")", "", $slug);

      $rulerUnitBody = array(
        'ruler_id' => $rulerId,
        'name' => $term->name,
        'value' => $slug,
        'uValue' => $slug,
        'slug' => $slug
      );

      $linItem = WC_LI_Settings::sendAPI('search/MutexRulerUnit', $rulerUnitBody);
      $rulerUnitId = false;
      if ($linItem->errorCode == 1000) {
        //create body pic?
        $newLinItem = WC_LI_Settings::sendAPI('create/MutexRulerUnit', $rulerUnitBody);
        if ($newLinItem->errorCode == 0) {
          $rulerUnitId = $newLinItem->body->id;
        }
      } else {
        $rulerUnitId = $linItem->body[0]->id;
      }
    }

    return $rulerId;
  }

  public static function getProdSku($post_id)
  {

    $metas = get_post_meta($post_id);
    if (
      isset($metas['_sku']) &&
      isset($metas['_sku'][0]) &&
      $metas['_sku'][0] != ''
    )
      return $metas['_sku'][0];

    return $post_id;
  }

  public static function WpItemSync($post_id, $logger)
  { //wp->linet
    //$pf = new WC_Product_Factory();
    //$product = $pf->get_product($item->ID);

    $product = wc_get_product($post_id);

    $logger->write("WpItemSync (post_id): $post_id");

    $metas = get_post_meta($product->get_id());

    $cats_id = self::WpCatSync($product);
    //get term meta?

    $itemSku = $product->get_sku();

    $stockType = 0;
    $ammount = 0;
    $saleprice = 0;


    $stockType = $product->get_manage_stock() == 'yes' ? 1 : 0;
    $ammount = $product->get_stock_quantity();

    $saleprice = $product->get_price();
    $saleprice = $product->get_regular_price();
    $price11 = $product->get_sale_price();

    $isProduct = 1;

    $terms = wp_get_object_terms($product->get_id(), 'product_type');

    if (isset($terms[0])) {
      if ($terms[0]->name == 'variable') {
        $isProduct = 3;
        $logger->write("WpItemSync sku(variable): " . $itemSku);

        if (strpos($itemSku, '-') !== false) {
          $itemSku = str_replace("-", "", $itemSku);
          $product->set_sku($itemSku);
          $product->save();

        }
      }
    }

    if ($product->get_type() == 'product_variation') {
      $isProduct = 0;
      $sku = array(self::getProdSku($product->get_parent_id()));
      foreach (wc_get_product_variation_attributes($product->get_id()) as $val) {
        $tval = str_replace(" ", "", urldecode($val));
        $tval = str_replace("-", "", $tval);
        $tval = str_replace("(", "", $tval);
        $tval = str_replace(")", "", $tval);
        $sku[] = $tval;
        //$sku[] = $val;
      }
      $itemSku = implode("-", $sku);
      $logger->write("WpItemSync sku(product_variation): " . $itemSku);

    }

    $parent_item_id = 0;
    if ($product->get_parent_id()) {
      $parent_item_id = self::getLinetIdFromPost($product->get_parent_id());
    }


    $cat_id = 0;
    if (count($cats_id) > 0)
      $cat_id = array_shift($cats_id);


    $body = array(
      'category_id' => $cat_id,
      'categories_ids' => $cats_id,
      //shoud be without main
      'name' => $product->get_name(),
      'description' => $product->get_description(),

      'sku' => $itemSku,
      'stockType' => $stockType,
      'saleprice' => $saleprice,
      'vatIn' => 1,

      'parent_item_id' => $parent_item_id,

      'currency_id' => get_woocommerce_currency(),
      //'ILS'
      'active' => 1,
      'unit_id' => 0,
      'isProduct' => $isProduct,
      'itemVatCat_id' => 1,



      'qty' => $ammount,
      'warehouse' => get_option('wc_linet_warehouse_id'),
      'stockSet' => true

      //_price
      //_linet_id
      //_manage_stock=yes
      //_stock
    );
    if ($price11) {
      $body['price11'] = $price11;
    }

    $obj = array(
      'body' => $body,
      'wc_product' => $product,
    );

    $obj = apply_filters('woocommerce_linet_item_back', $obj);
    if (isset($obj["body"]))
      $body = $obj["body"];

    $item_id = self::getLinetIdFromPost($product->get_id());

    if ($item_id) {
      $linItem = WC_LI_Settings::sendAPI('search/item', array('id' => $item_id));
      if ($linItem->errorCode == 1000) {
        $item_id = false;
      } else {
        $linItem = WC_LI_Settings::sendAPI('update/item?id=' . $item_id, $body);
        //no needself::smart_update_post_meta($item->ID, '_linet_id', $item_id);
      }

    }

    if (!$item_id) {
      $linItem = WC_LI_Settings::sendAPI('search/item', array('sku' => $itemSku));

      if ($linItem->errorCode == 1000) {
        //create body pic?
        $newLinItem = WC_LI_Settings::sendAPI('create/item', $body);
        if ($newLinItem->errorCode == 0) {
          $item_id = $newLinItem->body->id;
          self::smart_update_post_meta($product->get_id(), '_linet_id', $item_id);
        }
      } else {
        $item_id = $linItem->body[0]->id;
        //update body pic?
        $linItem = WC_LI_Settings::sendAPI('update/item?id=' . $item_id, $body);

        self::smart_update_post_meta($product->get_id(), '_linet_id', $item_id);
      }
    }

    if ($item_id) {
      if ($isProduct == 3) {

        $attrs = $product->get_attributes();
        $maps = array();
        //$template = array('{{SKU}}');
        //$fields = array();
        $line = 1;
        foreach ($attrs as $attr) {
          if ($attr->get_variation()) {
            $typeId = self::linetSaveRuler($attr, $item_id, $line);
            $line++;
            //$fields[] = $typeId;
            //$template[] = "{{".$typeId."}}";
          }
        }

        /**********************************************************************************************/
        //$catName
        //WpCatSync cat name?

      }
    }

    //var_dump($metas);exit;


    //sync images?
    if ($item_id) {
      if (
        isset($metas['_thumbnail_id']) &&
        $metas['_thumbnail_id'][0] &&
        $metas['_thumbnail_id'][0] != ""
      ) {
        self::savePicToLinet($item_id, $metas['_thumbnail_id'][0], true);
      }

      if (
        isset($metas['_product_image_gallery']) &&
        $metas['_product_image_gallery'][0] &&
        $metas['_product_image_gallery'][0] != ""
      ) {
        $images_id = explode(",", $metas['_product_image_gallery'][0]);
        foreach ($images_id as $img_id)
          self::savePicToLinet($item_id, $img_id, false);
      }
    }

    return true;
  }


  public static function savePicToLinet($linet_item_id, $post_id, $thumb = false)
  {
    $metas = get_post_meta($post_id);

    if (
      isset($metas['_wp_attached_file']) &&
      $metas['_wp_attached_file'][0] &&
      $metas['_wp_attached_file'][0] != ""
    ) {

      $basePath = wp_upload_dir()['basedir'] . '/';
      $wp_attached_file = $metas['_wp_attached_file'][0];
      $filename = basename($wp_attached_file);

      if (!file_exists($basePath . $wp_attached_file))
        return false;
      $body = [
        "name" => $filename,
        "path" => "pics/",
        "public" => 1,
        "filetype" => $thumb ? 10 : 15,
        "parent_id" => $linet_item_id,
        "nparent_type" => 5,
      ];
      $fileExsits = WC_LI_Settings::sendAPI('search/file', $body);

      //var_dump($fileExsits);exit;
      if (
        $fileExsits->status == 200 &&
        $fileExsits->errorCode == 1000
      ) {
        $pic = base64_encode(file_get_contents($basePath . $wp_attached_file));

        $body["parent_type"] = "app\models\Item";
        $body["base64content"] = $pic;

        $file = WC_LI_Settings::sendAPI('create/file', $body);
      } else {
        $file = $fileExsits;
        $file->body = $fileExsits->body[0];
      }

      if (
        $thumb &&
        $file->status == 200 &&
        $file->errorCode == 0

      ) {
        $body = array(
          'pic' => $file->body->hash
        );
        $linItem = WC_LI_Settings::sendAPI('update/item?id=' . $linet_item_id, $body);
        //var_dump($linItem);exit;
        //update item image
      }
    }
  }

  public static function syncStockURL()
  {

    $warehouse_stock_count = get_option('wc_linet_warehouse_stock_count');
    if ($warehouse_stock_count == 'off') {
      $warehouse_id = -1;
    } else {
      $warehouse_id = get_option('wc_linet_warehouse_id');
    }
    $pricelist_account = get_option('wc_linet_pricelist_account');
    $account_id = "";
    if ($pricelist_account)
      $account_id = "&account_id=$pricelist_account";

    return "stockall/item?warehouse_id=" . $warehouse_id . $account_id;
  }

  public static function syncParams()
  {
    $arr = array(
      'active' => 1,
      'limit' => WC_LI_Settings::STOCK_LIMIT,
    );
    $syncField = get_option('wc_linet_syncField');
    $syncValue = get_option('wc_linet_syncValue');
    if ($syncField != '' && $syncValue != '') {
      $arr[$syncField] = $syncValue;
    }

    $warehouse_exclude = get_option('wc_linet_warehouse_exclude');
    if ((string) $warehouse_exclude != '') {
      if (substr_count($warehouse_exclude, ",") == 0) {
        $arr['exclude'] = [$warehouse_exclude];

      } else {
        $arr['exclude'] = explode(",", $warehouse_exclude);

      }
    }

    return $arr;
  }


  public static function syncCatParams()
  {
    $arr = array(
      //'limit' => WC_LI_Settings::STOCK_LIMIT,
    );
    $syncField = get_option('wc_linet_syncCatField');
    $syncValue = get_option('wc_linet_syncCatValue');
    if ($syncField != '' && $syncValue != '') {
      $arr[$syncField] = $syncValue;
    }

    return array('query' => $arr);
  }


  public static function catSyncAjax()
  {
    if (!is_admin()) {
      echo "Go Away!";
      wp_die();

    }
    $mode = $_POST['mode'];
    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

    if ($mode === "CatSync") {
      $catFilter = self::syncCatParams();

      $cats = WC_LI_Settings::sendAPI('newsearch/itemcategory', $catFilter);
      foreach ($cats->body as $cat) {
        self::singleCatSync($cat, $logger);
      }

      echo json_encode(
        array(
          'status' => 'Success',
          'cats' => count($cats->body)
        )
      );
      wp_die();
    }


    if ($mode === "ItemSync") {
      $offset = intval($_POST['offset']);

      $params = self::syncParams();
      $params['offset'] = $offset;
      $params['since'] = get_option('wc_linet_last_update');

      $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
      //if isset..
      $products = $products->body;

      $runtime = microtime(true);
      $sync_count = 0;
      if (is_array($products))
        foreach ($products as $prod) {
          if (microtime(true) - $runtime < WC_LI_Settings::RUNTIME_LIMIT) {
            self::singleProdSync($prod, $logger);
            $sync_count++;
          }
        }

      echo json_encode(
        array(
          'status' => 'Success',
          'items' => $sync_count
        )
      );

      wp_die();
    }

    if ($mode == 3) { //doUpdateCall
      update_option('wc_linet_last_update', date('Y-m-d') . " 00:00:00"); //date('Y-m-d H:i:s')

      echo json_encode("done");

      wp_die();
    }

    echo json_encode(array('status' => 'nothing'));

    wp_die();
  }

  public static function singleCatSync($cat, $logger)
  {
    global $wpdb;

    $term = self::findTermByCatId($cat->id);
    $catParams = array('name' => $cat->name);

    if ($cat->parent_id != 0) {
      $parent_term_id = self::findByCatId($cat->parent_id);
      if ($parent_term_id) {
        $catParams['parent'] = $parent_term_id;
      }
    }

    if ($term) {
      $logger->write("Term Found: (term_id)$term->term_id ");

      $term_id = $term->term_id;

      $catParams['slug'] = $term->slug;
    } else {
      $catParams['slug'] = $cat->name;
      $logger->write("Term Insret: (cat_name)$cat->name ");

      $term_id = wp_insert_term($cat->name, 'product_cat', $catParams);
      if (is_wp_error($term_id)) {
        $logger->write("Term Insret error: (cat_name)$cat->name " . $term_id->get_error_message());

        $query = "SELECT * FROM $wpdb->terms
          LEFT JOIN $wpdb->term_taxonomy ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id
          WHERE
          $wpdb->terms.name=%s AND
          $wpdb->term_taxonomy.taxonomy = 'product_cat'
          LIMIT 1;
          ";
        //$catParams['parent']
        //_linet_cat
        $term_id = $wpdb->get_col($wpdb->prepare($query, $cat->name));
        //$logger->write("Term found " . $term_id->get_error_message());

        //$term_id=$term_id['term_id'];
        // echo $term_id->get_error_message();
        $term_id = $term_id[0];

      } else {
        $term_id = $term_id['term_id'];
      }

      update_term_meta($term_id, 'order', '');
      update_term_meta($term_id, 'display_type', '');
      update_term_meta($term_id, 'thumbnail_id', '');
      update_term_meta($term_id, 'product_count_product_cat', '');
    }
    $logger->write("update term: " . json_encode($catParams));

    $prev_metas = get_term_meta($term_id);

    $update_term = wp_update_term($term_id, 'product_cat', $catParams);
    if (is_wp_error($update_term)) {
      $logger->write("Term update error: (term_id)$term_id " . $update_term->get_error_message());
    }

    $exclude_metas = array(
      'product_count_product_cat',
      'thumbnail_id',
      'display_type',
      'order',
      'jet_woo_builder_template',

      '_linet_last_update',
      '_linet_cat',

    );

    $exclude_metas = apply_filters('woocommerce_linet_exclude_meta_save_on_sync', $exclude_metas);
    foreach ($prev_metas as $meta_key => $meta_value) {
      if (substr($meta_key, 0, 1) !== "_" && !in_array($meta_key, $exclude_metas)) {
        if (is_array($meta_value) && isset($meta_value[0])) {
          update_term_meta($term_id, $meta_key, $meta_value[0]);
        } else {
          update_term_meta($term_id, $meta_key, $meta_value);
        }
      }
    }

    update_term_meta($term_id, '_linet_cat', $cat->id);

    $picsync = get_option('wc_linet_picsync');
    if ($picsync == 'on') {
      $thumbed = self::getImage($cat->pic, $logger);
      if ($thumbed) {
        update_term_meta($term_id, 'thumbnail_id', $thumbed);
      }
    }

    update_term_meta($term_id, '_linet_last_update', date('Y-m-d H:i:s'));
    $logger->write("Term done: (term_id)$term_id ");

    return $term_id;
  }

  public static function getImage($pic, $logger = false)
  { //unused ,$parent_id=''
    $server = WC_LI_Settings::SERVER;

    $dev = get_option('wc_linet_dev') == 'on';
    if ($dev) {
      $server = WC_LI_Settings::DEV_SERVER;
    }

    $img_opt = get_option('wc_linet_rect_img');

    $basePath = wp_upload_dir()['basedir'] . '/';


    if (strpos($pic, 'http') === 0) {
      $url = $pic;
      $realtivePath = self::IMAGE_DIR . "/" . sha1($pic);
    } else {
      $realtivePath = self::IMAGE_DIR . "/" . $pic;
      $url = $server . "/site/largethumbnail/" . $pic;

      if ($img_opt == 'on')
        $url .= "?rect=true";

      if ($img_opt == 'nothumb')
        $url = $server . "/site/download/" . $pic;

    }

    $filePath = $basePath . $realtivePath;

    /*only in admin not in cron!!!
    WP_Filesystem();
    global $wp_filesystem;
    if(!$wp_filesystem->is_dir($basePath)) {
    $wp_filesystem->mkdir($basePath);
    }
    if($pic!='' ){
    if(!$wp_filesystem->is_file($filePath)){
    $ch = curl_init();
    curl_setopt_array($ch, array(
    CURLOPT_URL => $server . "/site/download/" . $pic,CURLOPT_RETURNTRANSFER => TRUE,CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    ));
    $response = curl_exec($ch);
    $wp_filesystem->put_contents($filePath,$response,FS_CHMOD_FILE);
    }*/


    if (!is_dir($basePath)) {
      mkdir($basePath);
    }

    if (!is_dir($basePath . self::IMAGE_DIR)) {
      mkdir($basePath . self::IMAGE_DIR);
    }

    if ($pic != '') {
      if (!is_file($filePath) || filesize($filePath) == 0) {



        $logger->write("get img: " . $url);

        $ch = curl_init();
        curl_setopt_array(
          $ch,
          array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => TRUE,
            //CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_SSL_VERIFYHOST => !$dev,
            CURLOPT_SSL_VERIFYPEER => !$dev,
          )
        );
        $response = curl_exec($ch);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        //var_dump($url);
        //var_dump($content_type);exit;
        //$logger->write("curl_getinfo: ".$content_type]);

        $content_type = explode("; ", $content_type);
        $logger->write("mimetype img: " . $content_type[0]);

        if (
          !isset($content_type[0]) ||
          (substr($content_type[0], 0, 6) !== "image/")
        ) {

          return false;
        }



        $ext = substr($content_type[0], 6);

        if (isset($ext) && !empty($ext)) {
          $filePath .= '.' . $ext;
        }

        file_put_contents($filePath, $response);
      }

      global $wpdb;
      $query = "SELECT ID FROM $wpdb->posts WHERE post_name = '%s' AND post_type = 'attachment' LIMIT 1";
      $image_id = $wpdb->get_col($wpdb->prepare($query, $pic));

      if (count($image_id) == 0) {

        $mime_type = $content_type[0];
        if (function_exists('mime_content_type')) {
          $mime_type = mime_content_type($filePath);
        } else {
          if (class_exists("finfo")) {
            $finfo = new finfo(FILEINFO_MIME); //<5.3
            $mime_type = $finfo->file($filePath);
          }
        }

        if (!function_exists('wp_generate_attachment_metadata')) { //rest api!
          include(ABSPATH . 'wp-admin/includes/image.php');
        }

        $attachment = array(
          'post_mime_type' => $mime_type,
          'post_title' => sanitize_file_name($pic),
          'post_content' => '',
          'post_status' => 'inherit'
        );

        $post_id = wp_insert_attachment($attachment, $filePath);

        wp_update_attachment_metadata($post_id, wp_generate_attachment_metadata($post_id, $filePath));

      } else {
        $post_id = $image_id[0];

        //wp_update_attachment_metadata($post_id, wp_generate_attachment_metadata($post_id, $filePath));

      }
      //*   //save new post
      return $post_id; //*/
    }

    return false;
  }

  public static function findByCatId($cat_id)
  {
    $term = self::findTermByCatId($cat_id);
    if ($term) {
      return $term->term_id;
    }
    return false;
  }

  public static function findTermByCatId($cat_id)
  {

    $args = array(
      'hide_empty' => false,
      'meta_query' => array(
        array(
          'key' => '_linet_cat',
          'value' => $cat_id,
        )
      ),
      'taxonomy' => 'product_cat',
    );

    $terms = get_terms($args);
    if (!empty($terms) && !is_wp_error($terms)) {
      return $terms[0];
    }

    return false;
  }



  public static function getLinetIdFromPost($post_id)
  {
    return get_post_meta($post_id, '_linet_id', true);

    global $wpdb;
    $query = "SELECT meta_value FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=$wpdb->posts.ID " .
      "WHERE " .
      "($wpdb->posts.post_type='product' OR $wpdb->posts.post_type='product_variation') AND " .
      "$wpdb->postmeta.meta_key='_linet_id' AND $wpdb->posts.ID=%d LIMIT 1;";
    $post = $wpdb->get_col($wpdb->prepare($query, array($post_id)));

    if (count($post) == 1) {
      return $post[0];
    }
    return 0;
  }


  public static function findByProdId($item_id)
  {
    return static::findByMeta("_linet_id", $item_id);
  }

  public static function findByProdSku($item_sku)
  {
    return static::findByMeta("_sku", $item_sku);
  }

  public static function findByMeta($meta, $value)
  {
    /*$products = wc_get_products(
      [
        'limit' => 1,
        'meta_key' => $meta,
        'meta_value' => $value,
        //'type' => array('variation', 'simple', 'variable'),
      ]
    );

    if (count($products) == 1) {
      return current($products);
    }
    return false;*/




    global $wpdb;
    $query = "SELECT ID FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID " .
      "WHERE " .
      "$wpdb->posts.post_type in ('product','product_variation') AND " .
      "$wpdb->postmeta.meta_key = '$meta' AND $wpdb->postmeta.meta_value = %s LIMIT 1;";
    $post = $wpdb->get_col($wpdb->prepare($query, array($value)));

    if (count($post) == 1) {
      return wc_get_product($post[0]);
    }
    return false;
  }





  public static function findParentBySku($item)
  {
    if ($item->isProduct == 0 && $item->parent_item_id != 0) {
      $calc_sku = explode('-', $item->sku);
      if (is_array($calc_sku) && count($calc_sku) >= 1) {
        return self::findByProdSku($calc_sku[0]);
      }
    }
    return false;
  }

  public static function updateTaxonomy($item, $product)
  {
    $terms = array(self::findByCatId($item->item->category_id));
    foreach ($item->categories_ids as $cat) {
      $terms[] = self::findByCatId($cat);
    }

    $product->set_category_ids($terms);

    //$res = wp_set_post_terms($post_id, $terms, 'product_cat');


  }


  public static function singleProdAjax()
  { //wp to linet
    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

    $post_id = intval($_POST['post_id']);
    $logger->write("WpItemSync post_id:$post_id");


    /*$product = wc_get_products(
      array(
        'limit' => 1,
        'include' => array($post_id),
      )
    );*/
    //$product = wc_get_product( $post_id   );
    //$product = wc_get_product($post_id);

    $result = self::WpItemSync($post_id, $logger);
    if ($result)
      echo json_encode(
        array(
          'status' => 'Success',
          'result' => $result
        )
      );
    else
      echo json_encode(
        array(
          'status' => 'empty',
          'result' => $result
        )
      );
    wp_die();
  }


  public static function singleSyncAjax()
  { //linet to wp
    $post_id = intval($_POST['post_id']);
    $result = self::singleSync($post_id);
    if ($result)
      echo json_encode(
        array(
          'status' => 'Success',
          'result' => $result
        )
      );
    else
      echo json_encode(
        array(
          'status' => 'empty',
          'result' => $result
        )
      );
    wp_die();
  }


  public static function singleSync($post_id)
  {
    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));
    $logger->write("singleSync: " . $post_id);

    $metas = get_post_meta($post_id);
    $item = null;
    $found = false;
    $params = self::syncParams();
    $params['limit'] = 1;

    if (isset($metas['_linet_id']) && isset($metas['_linet_id'][0])) {
      $params['id'] = $metas['_linet_id'][0];
      $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
      if (is_array($products->body) && count($products->body) >= 1) {
        $item = $products->body[0];
        $found = true;
      }
    } else {
      if (isset($metas['_sku']) && isset($metas['_sku'][0])) {
        $params['sku'] = $metas['_sku'][0];
        $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
        if (is_array($products->body) && count($products->body) >= 1) {
          $item = $products->body[0];
          self::smart_update_post_meta($post_id, '_linet_id', $products->body[0]->item->id);
        }
      }
    }

    if (!is_null($item)) {

      $result = self::singleProdSync($item, $logger);

      $params = self::syncParams();
      $params['parent_item_id'] = $products->body[0]->item->id;
      $params['limit'] = 70;

      $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
      foreach ($products->body as $item) {
        $result = self::singleProdSync($item, $logger);
      }

      return true;
    }
    return false;
  }

  public static function saveRuler($name, $slug, $logger)
  {
    global $wpdb;

    $ruler_wp_id = 0;

    $query = "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies " .
      //"LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=$wpdb->posts.ID AND $wpdb->postmeta.meta_key='_sku'".
      " WHERE attribute_name=%s AND attribute_label=%s" .
      " LIMIT 1;";
    $post = $wpdb->get_col($wpdb->prepare($query, array($slug, $name)));

    if (count($post) == 1) {

      $ruler_wp_id = $post[0];
      $logger->write("found ruler update $ruler_wp_id");

      wc_update_attribute(
        $ruler_wp_id,
        array(
          'name' => $name,
          'slug' => $slug,
          //'order_by' => 'name'
        )
      );
    } else {
      $ruler_wp_id = wc_create_attribute([
        'name' => $name,
        'slug' => $slug,
        //'order_by' => 'name'
      ]);


      if (is_wp_error($ruler_wp_id)) {
        $logger->write("saveRuler create error " . $ruler_wp_id->get_error_message());
        $ruler_wp_id = 0;
      }
      $logger->write("saveRuler create $ruler_wp_id");


    }

    return $ruler_wp_id;
  }

  public static function syncRuler($ruler, $logger)
  {
    global $wpdb;

    $rulerslug = $ruler->slug;

    $ruler_wp_id = self::saveRuler($ruler->name, $rulerslug, $logger);

    $taxonomy = "pa_" . sanitize_title($rulerslug);
    $taxonomy = "pa_" . $rulerslug;


    $logger->write("syncRuler taxonomy: $taxonomy, slug: $ruler_wp_id (name,linet_id)  (" . $ruler->name . "," . $ruler->id);

    foreach ($ruler->units as $unit) {

      $unitslug = strtolower($unit->slug);

      if (!$term = get_term_by('slug', $unitslug, $taxonomy)) {
        $insert = wp_insert_term($unit->name, $taxonomy, array('slug' => $unitslug));
        $logger->write("syncRuler slug insert" . json_encode($insert));

        $term = get_term_by('slug', $unitslug, $taxonomy);

      } else {
        wp_update_term($term->term_id, $taxonomy, array('name' => $unit->name));
      }

      $term_id = 0;

      if ($term) {
        $term_id = $term->term_id;

        update_term_meta($term_id, 'order', $unit->uValue);

      } else {
        $logger->write("syncRuler bed term " . json_encode($term));

      }

      $logger->write("syncRuler slug $unit->name $unitslug $term_id");

    }
  }







  public static function singleProdSync($item, $logger)
  {
    $user_id = 1;
    $onlyStockManage = get_option('wc_linet_only_stock_manage');

    $global_attr = get_option('wc_linet_global_attr') == 'on';

    $no_description = get_option('wc_linet_no_description') == 'on';
    $logger->write("singleProdSync start: " . $item->item->id);


    $parent_id = false;
    $post_id = false;

    $product = self::findByProdId($item->item->id);

    if ($onlyStockManage == 'on') {

      if (!$product)
        $product = self::findByProdSku($item->item->sku);

      if ($product) {
        //date('Y-m-d H:i:s')
        $product->update_meta_data('_linet_last_update', date('Y-m-d H:i:s'));
        $product = self::updateStock($product, $item, $logger);
        $logger->write("singleProdSync only stock: (post_id,linet_id){$product->get_id()}," . $item->item->id);

      }


      return 0;
    }

    //$post_id = self::findByProdId($item->item->id);
    $product_type = "product";
    $product_update_type = "product";


    if ($product)
      $post_id = $product->get_id();

    if ($item->item->isProduct == 0 && $item->item->parent_item_id != 0) {
      $product_type = "product_variation";
      $product_fc_type = "variation";


    }
    if ($item->item->isProduct == 3) {
      $product_type = "variable";
      $product_fc_type = "variable";


    }

    $logger->write("singleProdSync: $product_type(post_id,linet_id)$post_id," . $item->item->id);

    //$product = false;

    if (!$post_id) {

      $product = self::findByProdSku($item->item->sku);
      $logger->write("singleProdSync by sku: $product_type(linet_id)," . $item->item->id);

    }



    if ($product && is_object($product) && $product_type != $product->get_type()) {
      $post_id = $product->get_id();
      $logger->write("singleProdSyncType  $product_type(linet_id)," . $product->get_type());

      $logger->write("singleProdSyncType update");
      $update_product_type = $product_type;

      if ($update_product_type == "variable") {
        wp_set_object_terms($post_id, 'variable', 'product_type');
        $update_product_type = "product";

      } else if ($update_product_type == "product_variation") {
        wp_set_object_terms($post_id, '', 'product_type');

      } else {
        wp_set_object_terms($post_id, 'simple', 'product_type');

      }

      wp_update_post(array("ID" => $post_id, "post_type" => $update_product_type));

      $logger->write("singleProdSyncType: $post_id");


      $classname = WC_Product_Factory::get_product_classname($post_id, $product_fc_type);
      $product = new $classname($post_id);
      //$product = wc_get_product($post_id);

    }

    if (!$post_id || !$product) {
      $logger->write("singleProdSync create");

      //$classname = WC_Product_Factory::get_product_classname( $post_id, $product_type );
      //$product = new $classname();


      if ($product_type == 'product_variation') {//do not use product factory
        $product = new WC_Product_Variation();
      } elseif ($product_type == "variable") {
        $product = new WC_Product_Variable();
      } else {
        $product = new WC_Product();
      }

      $product->set_name((string) $item->item->name);
      if (!$no_description)
        $product->set_description((string) $item->item->description);
      $product->set_sku($item->item->sku);

      $product->update_meta_data('_linet_id', $item->item->id);

      $logger->write("singleProdSync new product save: " . $product->save());

      $post_id = $product->get_id();

    } else {

      //$classname = WC_Product_Factory::get_product_classname( $post_id, $product_type );
      //$product = new $classname($post_id);

      $product->set_name((string) $item->item->name);
      if (!$no_description)
        $product->set_description((string) $item->item->description);
    }


    if ($item->item->isProduct == 3) {

      self::updateTaxonomy($item, $product);

      $logger->write("singleProdSync updateTaxonomy mutex parent");


      $not_product_attributes = get_option('wc_linet_not_product_attributes');

      if ($not_product_attributes != "on") {
        $attributes = array();
        //var_dump($item);exit;
        foreach ($item->mutex as $mutexIndex => $fullRuler) {

          $attribute = new WC_Product_Attribute();
          $attribute->set_position(0);
          $attribute->set_visible(1);
          $attribute->set_variation(1);

          if ($global_attr && isset($item->slugmutex[$mutexIndex])) {
            $cutRoler = $item->slugmutex[$mutexIndex];

            $taxonomy = wc_attribute_taxonomy_name($cutRoler->rulerSlug);

            $tmparray = array();

            foreach ($cutRoler->units as $rolerUnit) {
              $term_name = $rolerUnit->name;
              $term_slug = sanitize_title(strtolower($rolerUnit->slug));

              if (!$term = get_term_by('slug', $term_slug, $taxonomy)) {
                wp_insert_term($term_name, $taxonomy, array('slug' => $term_slug));
                $term = get_term_by('slug', $term_slug, $taxonomy);
              }



              $tmparray[] = (int) $term->term_id;
              $logger->write("singleProdSync tmparray " . $term->term_id);

            }

            $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
            $attribute->set_name($taxonomy);
            $attribute->set_options($tmparray);

            //Save main product to get its id

            $logger->write("singleProdSync mutex $taxonomy $post_id " . json_encode($tmparray));

          } else {
            $attribute->set_id(0);
            $attribute->set_name($fullRuler->name);
            $attribute->set_options($fullRuler->unitnames);

          }

          $attributes[$taxonomy] = $attribute;

        }

        $obj = array(
          'item_id' => $post_id,
          'linet_item' => $item,
          'wc_product' => $product,
          'product_attributes' => $attributes
        );

        $obj = apply_filters('woocommerce_linet_product_attributes', $obj);
        if (isset($obj["product_attributes"]))
          $attributes = $obj["product_attributes"];

        $product->set_attributes($attributes);
        $logger->write("singleProdSync mutex _product_attributes " . json_encode($attributes));

        wc_delete_product_transients($post_id); //needed?

        //delete_transient()
      }
    } else {
      if ($item->item->isProduct == 0 && $item->item->parent_item_id != 0) {

        $parent_product = self::findByProdId($item->item->parent_item_id);

        $product->set_name($item->item->sku);
        if ($parent_product) {
          $logger->write("parent_id wp,linet: " . $parent_product->get_id() . "," . $item->item->parent_item_id);
          $product->set_parent_id($parent_product->get_id());
        }


        $attributes = array();

        if (is_null($item->mutex)) //we need to get attrbuts..
          $item->mutex = array();

        foreach ($item->mutex as $type => $attr) {
          if ($type != 'SKU') {
            if ($global_attr) {
              $slug = sanitize_title(strtolower($attr->slug));

              $taxonomy = wc_attribute_taxonomy_name($attr->rulerslug);

              $tax = strtolower(urlencode($taxonomy));

              //$attributes[$taxonomy] = strtolower(urlencode($attr->slug));

              if (!$term = get_term_by('slug', $slug, $taxonomy)) {
                wp_insert_term($attr->name, $taxonomy, array('slug' => $slug));
                $term = get_term_by('slug', $slug, $taxonomy);
              }



              $logger->write("singleProdSync mutex global " . $tax . " " . $term->term_id);


              $attributes[$tax] = $slug;
              //bad!! $attributes[$taxonomy] = $term->term_id;

            } else {
              $attry = strtolower(urlencode(str_replace(" ", "-", $type)));
              $attributes[$attry] = $attr->name;
              $logger->write("singleProdSync mutex simple " . 'attribute_' . $attry . " " . $attr->name);

            }
          }
        }

        $product->set_attributes($attributes);
        //maybe? WC_Product_Variable::sync( $parent_id );

      } else {
        self::updateTaxonomy($item, $product);
        $logger->write("singleProdSync updateTaxonomy simple");
      }
    }

    $product->update_meta_data('_linet_last_update', date('Y-m-d H:i:s'));

    $saleprice = $item->item->saleprice;
    if (!$item->item->vatIn) {
      $saleprice *= 1 + $item->vat_rate / 100;
      $saleprice = round($saleprice, 2);
    }

    $product->set_regular_price($saleprice);
    $product->set_price($saleprice);
    if ($item->item->discount != 0) { //discount for all
      //if (!$item->item->vatIn) {
      //  $discount = $saleprice - $item->item->discount * (1 + $item->vat_rate / 100);
      //} else {
      $discount = $saleprice - $item->item->discount;
      //}
      $discount = round($discount, 2);

      $product->set_sale_price($discount);
    } else {
      $product->set_sale_price("");
    }

    $product->set_tax_status($item->item->itemVatCat_id == 1 ? 'taxable' : 'none');
    try {
      $product->set_sku($item->item->sku);

    } catch (Exception $e) {

      $product = self::findByProdId($item->item->id);

      if ($product !== false) {
        $logger->write("singleProdSync: found linet id assuming double fast call, cancel update");
        $product->delete(true);
        return 0;
      }

      $product->set_sku($item->item->sku . "--" . $product->get_id());

      $logger->write("singleProdSync: double sku-" . $item->item->sku);
    }

    $product->update_meta_data('_linet_id', $item->item->id);

    $product = self::updateStock($product, $item, $logger); //by parent_item_id



    $picsync = get_option('wc_linet_picsync');
    //echo $picsync;exit;
    if ($picsync == 'on' && ($item->has_pictures != "0" || $item->item->pic != "")) {
      $thumbed = self::getImage($item->item->pic, $logger);
      $logger->write("Linet before thumbed Img:" . $thumbed);

      if ($thumbed) {
        $product->set_image_id($thumbed);
        $logger->write("Linet thumbed Img: " . $thumbed);

      }

      //$imgs//get files concted to item with type
      $params = array(
        'nparent_type' => 5,
        'filetype' => 15,
        'parent_id' => $item->item->id
      );

      $galleryImgs = WC_LI_Settings::sendAPI('search/file', $params);
      $imgs = array();
      if (is_array($galleryImgs->body)) {
        foreach ($galleryImgs->body as $img) {
          $newImg = self::getImage($img->hash, $logger);
          if ($newImg)
            $imgs[] = $newImg;
        }
      }
      $logger->write("Linet GalleryImgs: " . implode(",", $imgs));
      $product->set_gallery_image_ids($imgs);
    }

    $itemFields = get_option('wc_linet_itemFields');

    if (is_array($itemFields) && isset($itemFields["linet_field"])) {
      foreach ($itemFields["linet_field"] as $index_key => $key_field_linet) {
        $linet_field = $key_field_linet;
        if (is_numeric($key_field_linet))
          $linet_field = 'eav' . $key_field_linet;

        $wc_field = $itemFields["wc_field"][$index_key];

        $post_fields = array();

        if (isset($item->item->$linet_field)) {
          $fieldValue = $item->item->$linet_field;


          if (in_array($wc_field, $post_fields)) {
            $update = array('ID' => $post_id);
            $update[$wc_field] = $fieldValue;
            wp_update_post($update);
          } elseif ($wc_field == 'post_date') {
            $product->set_date_created($fieldValue);
          } elseif ($wc_field == 'post_excerpt') {
            //$product->set_short_description($fieldValue);
            $fieldValue = str_replace("\r\n", '', $fieldValue);

            $fieldValue = str_replace("\r", '', str_replace("\n", '', $fieldValue));
            $product->set_short_description($fieldValue);

          } elseif ($wc_field == 'post_status') {
            $product->set_status($fieldValue);
          } elseif ($wc_field == 'backorders') {
            $product->set_backorders($fieldValue);
          } elseif ($wc_field == 'post_name') { //is url name //post_title is prod name
            $product->set_slug($fieldValue);
          } else {
            //self::smart_update_post_meta($post_id,$wc_field, $fieldValue, array());
            $product->update_meta_data($wc_field, $fieldValue);

          }

        }

        // code...
      }
    }
    $product->update_meta_data('_linet_last_update', date('Y-m-d H:i:s'));

    $obj = array(
      'item_id' => $post_id,
      'linet_item' => $item,
      'wc_product' => $product,
    );

    $obj = apply_filters('woocommerce_linet_item', $obj);
    if (isset($obj["wc_product"]))
      $product = $obj["wc_product"];
    $logger->write("singleProdSync product save: " . $product->save());
    $logger->write("singleProdSync status: " . $product->get_status());

    $logger->write("singleProdSync: done");

    return 0;

  }

  public static function updateStock($product, $item, $logger)
  {

    $stockManage = get_option('wc_linet_stock_manage');
    $qty = str_replace(",", "", $item->qty);

    if ($stockManage == 'on' && $item->item->stockType && $item->item->isProduct != 3) {
      $product->set_manage_stock('yes');
      $product->set_stock_quantity($qty);
    } else {
      $product->set_manage_stock('no');
    }
    $logger->write("updateStock product save: $qty " . $product->save());

    return $product;
  }

  public static function smart_update_post_meta($post_id, $attr, $value, $metas = array())
  {
    //if(isset($metas[$attr])&& $metas[$attr]!= $value){
    //  return false;
    //}
    $obj = array(
      'post_id' => $post_id,
      'attr' => $attr,
      'value' => $value,
      'metas' => $metas,
      'update' => true
    );

    $obj = apply_filters('woocommerce_linet_update_post_meta', $obj);
    if (
      isset($obj['post_id']) &&
      isset($obj['attr']) &&
      isset($obj['value']) &&
      isset($obj['update']) &&
      $obj['update']
    )
      return update_post_meta($obj['post_id'], $obj['attr'], $obj['value']);
    return false;
  }

  public static function prodSync($logger, $status)
  {
    $logger->write("prodSync");

    $user_id = 1;

    $params = self::syncParams();
    //$params['category_id'] = $linet_cat_id;
    $params['offset'] = 0;
    $params['since'] = get_option('wc_linet_last_update');

    $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
    $products = $products->body;
    while (count($products)) {
      foreach ($products as $item) {
        $logger->write("Linet Item Id: " . $item->item->id . " start sync");
        $result = self::singleProdSync($item, $logger);

        $logger->write("Linet Item Id: " . $item->item->id . " was synced");
        unset($result);
        unset($item);
      } //end each
      $status['offset'] += count($products);
      $params['offset'] += count($products);

      wp_cache_set('linet_fullSync_status', $status);

      $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
      //$logger->write(json_encode($products));
      if (isset($products->body))
        $products = $products->body;
      else
        $products = array();
    }
    unset($user_id);
    unset($offset);
    unset($products);
    $logger->write("prodSync end");
    return $status;
  }

  public static function fullSync()
  { //shuld use single?
    $status = array(
      'running' => true,
      'start' => date('Y-m-d'),
      'offset' => 0
    );
    $login_id = get_option('wc_linet_consumer_id');
    $hash = get_option('wc_linet_consumer_key');
    $company = get_option('wc_linet_company');

    if ($login_id == '' || $hash == '' || $company == '') {
      return false;
    }
    wp_cache_set('linet_fullSync_status', $status);

    $catFilter = self::syncCatParams();

    $cats = WC_LI_Settings::sendAPI('newsearch/itemcategory', $catFilter);


    $cats = $cats->body;

    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

    $logger->write("Start Linet Cat Sync:");
    $logger->write("max_execution_time:" . ini_get('max_execution_time'));
    $logger->write("WP_CRON_LOCK_TIMEOUT:" . WP_CRON_LOCK_TIMEOUT);

    foreach ($cats as $cat) {
      $wp_cat_id = self::singleCatSync($cat, $logger);
      $logger->write("Linet Cat ID:" . $cat->id);

    }

    $status = self::prodSync($logger, $status);


    $status['running'] = false;
    wp_cache_set('linet_fullSync_status', $status);

    //update_option('wc_linet_last_update', "2018-06-01 00:00:00");
    update_option('wc_linet_last_update', date('Y-m-d') . " 00:00:00"); //date('Y-m-d H:m:i')
    $logger->write("End Linet Cat Sync");
  } //end func
}

//end class