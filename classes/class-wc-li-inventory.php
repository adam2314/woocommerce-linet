<?php
/*
  Plugin Name: WooCommerce Linet Integration
  Plugin URI: https://github.com/adam2314/woocommerce-linet
  Description: Integrates <a href="http://www.woothemes.com/woocommerce" target="_blank" >WooCommerce</a> with the <a href="http://www.linet.org.il" target="_blank">Linet</a> accounting software.
  Author: Speedcomp
  Author URI: http://www.linet.org.il
  Version: 2.6.8
  Text Domain: wc-linet
  Domain Path: /languages/
  WC requires at least: 2.2
  WC tested up to: 4.2.2

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

class WC_LI_Inventory {
  const IMAGE_DIR='images';


/**
* Setup the required settings hooks
*/
public function setup_hooks() {//out

  add_action('admin_init', array($this, 'register_settings'));

  add_action('admin_menu', array($this, 'add_menu_item'));
}



public static function DeleteAjax() {
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


public static function CleanOrphAjax() {
  global $wpdb;
$wpdb->query();
/*
DELETE pm
FROM wp_postmeta pm
LEFT JOIN wp_posts wp ON wp.ID = pm.post_id
WHERE wp.ID IS NULL
*/
}


public static function CatListAjax() {
  //$genral_item = get_option('wc_linet_genral_item');
  $res = WC_LI_Settings::sendAPI('search/itemcategory');
  echo json_encode($res);
  wp_die();
}

public static function WpCatSyncAjax() {
  //$genral_item = get_option('wc_linet_genral_item');
  //$res = WC_LI_Settings::sendAPI('search/itemcategory');
  $cat_id = intval($_POST['id']);

  $products = WC_LI_Settings::sendAPI('search/item', array('category_id' => $cat_id));

  global $wpdb;
  $catName = $_POST['catName'];

  $query = "SELECT * FROM $wpdb->term_taxonomy " .
  " LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=" .
  "$wpdb->posts.ID WHERE $wpdb->posts.post_type='product' AND $wpdb->posts.post_status = 'publish' AND "  .
  "$wpdb->postmeta.meta_key='_linet_id' and $wpdb->postmeta.meta_value='" . $cat_id . "';" .
  "LEFT JOIN $wpdb->term_taxonomy ON $wpdb->term_taxonomy.term_id=$wpdb->terms.term_id " .
  "WHERE " .
  "$wpdb->term_taxonomy.taxonomy='product_cat' AND $wpdb->terms.name=%s;";
  $product_id = $wpdb->get_col($wpdb->prepare($query,$catName));

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

public static function WpItemsSyncAjax(){
  global $wpdb;
  $mode = intval($_POST['mode']);
  //$logger = new WC_LI_Logger(get_option('wc_linet_debug'));
  $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

  if ($mode == 0) {
    //count items to sync
    $query = "SELECT count(ID) FROM $wpdb->posts ".
    "WHERE ".
    "$wpdb->posts.post_type='product' AND $wpdb->posts.post_status = 'publish' AND %d";//or variation

    $counts = $wpdb->get_col($wpdb->prepare($query,array(1)));
    //var_dump($counts);
    $count=0;

    //get all cats and sync

    if (count($counts) != 0) {
      $count=$counts[0]*1;
    }

    $logger->write("Start WP->Linet Sync:$count");

    echo json_encode($count);

  }else{
    $offset = intval($_POST['offset']);
    $logger->write("WP->Linet Sync Pulse:$offset");
    echo json_encode(self::WpSmallItemsSyncAjax($offset));
  }
  wp_die();
}

public static function WpCatSync($item){
  //$cat_id=0;

  $terms=wp_get_post_terms($item->ID,'product_cat');
  $cats=[];
  if(is_array($terms)&&count($terms)>0){

    foreach( $terms as $term){

      $termsMeta = get_term_meta($term->term_id);

      if(isset($termsMeta['_linet_cat'])&&isset($termsMeta['_linet_cat'][0])){
        $linCat = WC_LI_Settings::sendAPI('view/itemcategory?id='.$termsMeta['_linet_cat'][0]);//
        //var_dump("_linet_cat search");
        //var_dump($linCat);exit;
        if($linCat->errorCode==0 && $linCat->status==200)
          $cats[] = (int)$termsMeta['_linet_cat'][0];
      }

      $linCat = WC_LI_Settings::sendAPI('search/itemcategory', ['name' => $term->name]);//
      $catBody=array(
        'name'=>$term->name,
        'profit'=>1,
      );
      if($linCat->errorCode==1000){
        //create body pic?
        $linCat = WC_LI_Settings::sendAPI('create/itemcategory',$catBody);
        if($linCat->errorCode==0 && $linCat->status==200 ){
          update_term_meta($term->term_id, '_linet_cat',$linCat->body->id);
          $cats[] = (int)$linCat->body->id;
        }
      }else{
          $cat_id=$linCat->body[0]->id;
          //update body pic?
          //$linItem = WC_LI_Settings::sendAPI('update/itemcategory?id='.$id, $catBody);
          update_term_meta($term->term_id, '_linet_cat',$cat_id);
          $cats[] = (int)$cat_id;
        }
    }
  }
  return array_unique($cats);
}

public static function WpSmallItemsSyncAjax($offset){
  global $wpdb;
  $query = "SELECT * FROM $wpdb->posts ".
  //"INNER JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=" . "$wpdb->posts.ID ".
  "WHERE ".
  "$wpdb->posts.post_type='product' AND $wpdb->posts.post_status = 'publish' ".
  "LIMIT ".WC_LI_Settings::STOCK_LIMIT." OFFSET %d;";
  //$parent_id=$item->item->parent_item_id;
  $items = $wpdb->get_results($wpdb->prepare($query,$offset));
  foreach($items as $item){
    $metas = get_post_meta($item->ID);

    $cats_id = self::WpCatSync($item);
    //get term meta?


    $itemSku = $item->ID;
    if(isset($metas['_sku']) &&
       isset($metas['_sku'][0]) &&
       $metas['_sku'][0]!='')
      $itemSku=$metas['_sku'][0];

    $stockType = 0;
    $ammount = 0;
    $saleprice = 0;

    if(isset($metas['_manage_stock']) &&
       isset($metas['_manage_stock'][0]))
      $stockType=($metas['_manage_stock'][0]=='yes')?1:0;

    if(isset($metas['_stock']) &&
       isset($metas['_stock'][0]))
      $ammount=$metas['_stock'][0];

    if(isset($metas['_price']) &&
       isset($metas['_price'][0]))
      $saleprice=$metas['_price'][0];


    $body=array(
      'category_id' => count($cats_id)>0?$cats_id[0]:0,

      'categories_ids'=>$cats_id,

      'name' => $item->post_title,
      'description' => $item->post_content,

      'sku' => $itemSku,
      'stockType' => $stockType,
      'ammount' => $ammount,
      'saleprice' => $saleprice,
      'vatIn' => 1,

      'parent_item_id' => 0,

      'currency_id' => 'ILS',
      'active' => 1,
      'unit_id' => 0,
      'isProduct'=>1,
      'itemVatCat_id'=>1,

      //_price
      //_linet_id
      //_manage_stock=yes
      //_stock
    );

    $obj=array(
      'body'=>$body,
      'wc_product'=>wc_get_product( $item->ID),
    );


    $obj= apply_filters( 'woocommerce_linet_item_back',   $obj  );
    if(isset($obj["body"]))
      $body=$obj["body"];

    $linItem = WC_LI_Settings::sendAPI('search/item', array('sku' => $itemSku));//
    $item_id=false;
    if($linItem->errorCode==1000){
      //create body pic?
      $newLinItem = WC_LI_Settings::sendAPI('create/item',$body);
      if($newLinItem->errorCode==0){
        $item_id = $newLinItem->body->id;
        self::smart_update_post_meta($item->ID, '_linet_id',$item_id);

      }

    }else{
      $item_id=$linItem->body[0]->id;
      //update body pic?
      $linItem = WC_LI_Settings::sendAPI('update/item?id='.$item_id, $body);

      self::smart_update_post_meta($item->ID, '_linet_id',$item_id);
    }

    //sync images?
    if($item_id){
      if(
        isset($metas['_thumbnail_id']) &&
        $metas['_thumbnail_id'][0] &&
        $metas['_thumbnail_id'][0] != ""
      ){
        self::savePicToLinet($item_id,$metas['_thumbnail_id'][0],true);
      }

      if(
        isset($metas['_product_image_gallery']) &&
        $metas['_product_image_gallery'][0] &&
        $metas['_product_image_gallery'][0] != ""
      ){
        $images_id = explode(",",$metas['_product_image_gallery'][0]);
        self::savePicToLinet($item_id,$images_id);
      }
    }
  }
  //foreach
  //sleep(1);
  return count($items);

}


public static function savePicToLinet($linet_item_id,$post_id,$thumb=false){
  $metas=get_post_meta($post_id);

  if(
    isset($metas['_wp_attached_file']) &&
    $metas['_wp_attached_file'][0] &&
    $metas['_wp_attached_file'][0] != ""
  ){

    $basePath = wp_upload_dir()['basedir'].'/';
    $wp_attached_file = $metas['_wp_attached_file'][0];
    $filename = basename($wp_attached_file);

    if(!file_exists ( $basePath.$wp_attached_file))
    	return false;
    $body = [
      "name" => $filename,
      "path" => "pics/",
      "public" => 1,
      "filetype" => $thumb?5:15,
      "parent_id" => $linet_item_id,
      "nparent_type" => 5,
    ];
    $fileExsits = WC_LI_Settings::sendAPI('search/file', $body);

    //var_dump($fileExsits);exit;
    if($fileExsits->status == 200 &&
      $fileExsits->errorCode == 1000
    ){
      $pic = base64_encode(file_get_contents( $basePath.$wp_attached_file));

      $body["parent_type"] = "app\models\Item";
      $body["base64content"] = $pic;

      $file = WC_LI_Settings::sendAPI('create/file', $body);
    }else{
      $file = $fileExsits;
      $file->body = $fileExsits->body[0];
    }

    if(
      $thumb &&
      $file->status == 200 &&
      $file->errorCode == 0

    ){
      $body=[
        'pic' => $file->body->hash
      ];
      $linItem = WC_LI_Settings::sendAPI('update/item?id='.$linet_item_id, $body);
      //var_dump($linItem);exit;
      //update item image
    }
  }
}

  public static function syncStockURL(){

      $warehouse_stock_count=get_option('wc_linet_warehouse_stock_count');
      if($warehouse_stock_count=='off'){
        $warehouse_id=-1;
      }else{
        $warehouse_id = get_option('wc_linet_warehouse_id');
      }
      $pricelist_account=get_option('wc_linet_pricelist_account');
      $account_id="";
      if($pricelist_account)
        $account_id="&account_id=$pricelist_account";

    return "stockall/item?warehouse_id=" . $warehouse_id.$account_id;
  }

public static function syncParams(){
  $arr=array(
    'active'=>1,
    'limit'=> WC_LI_Settings::STOCK_LIMIT,
  );
  $syncField=get_option('wc_linet_syncField');
  $syncValue=get_option('wc_linet_syncValue');
  if($syncField!=''&&$syncValue!=''){
    $arr[$syncField]=$syncValue;
  }

  $warehouse_exclude=get_option('wc_linet_warehouse_exclude');
  if((string)$warehouse_exclude!=''){
    if(substr_count ($warehouse_exclude,",")==0){
      $arr['exclude'] = [$warehouse_exclude];

    }else{
      $arr['exclude'] = explode(",",$warehouse_exclude);

    }
  }


  return $arr;
}


public static function catSyncAjax() {
  $mode = $_POST['mode'];
  $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

  if ($mode === "CatSync") {
    $cats = WC_LI_Settings::sendAPI('search/itemcategory');
    foreach($cats->body as $cat){
      self::singleCatSync($cat,$logger);
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

    foreach($products as $prod){
      $result = self::singleProdSync( $prod,$logger);
      $prod = null;
      $result = null;

      unset($prod);
      unset($result);
    }

    echo json_encode(
      array(
        'status' => 'Success',
        'items' => count($products)
      )
    );

    wp_die();
  }

  if ($mode == 3) {//doUpdateCall
    update_option('wc_linet_last_update', date('Y-m-d')." 00:00:00");//date('Y-m-d H:i:s')

    echo json_encode("done");

    wp_die();
  }



  echo json_encode( array( 'status'=>'nothing' ) );

  wp_die();
}

public static function singleCatSync($cat,$logger) {
  global $wpdb;

  $term = self::findTermByCatId($cat->id);
  $catParams = array( 'name' => $cat->name );

  if($cat->parent_id != 0){
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

    $term_id = wp_insert_term($cat->name, 'product_cat',$catParams );
    if(is_wp_error( $term_id )){
      $logger->write("Term Insret error: (cat_name)$cat->name " . $term_id->get_error_message());

      $query = "SELECT * FROM $wpdb->terms
      LEFT JOIN $wpdb->term_taxonomy ON $wpdb->term_taxonomy.term_id=$wpdb->terms.term_id

      WHERE
      $wpdb->terms.name=%s AND
      $wpdb->term_taxonomy.taxonomy='product_cat'
       LIMIT 1;
      ";//$catParams['parent']
      //_linet_cat
      $term_id = $wpdb->get_col($wpdb->prepare($query,$cat->name));
      //$logger->write("Term found " . $term_id->get_error_message());



      //$term_id=$term_id['term_id'];
      // echo $term_id->get_error_message();
      $term_id=$term_id[0];

    }else{
      $term_id=$term_id['term_id'];

    }



    update_term_meta($term_id, 'order', '');
    update_term_meta($term_id, 'display_type', '');
    update_term_meta($term_id, 'thumbnail_id', '');
    update_term_meta($term_id, 'product_count_product_cat', '');
  }
  $logger->write("update term: ".json_encode($catParams));

  $update_term = wp_update_term($term_id, 'product_cat', $catParams);
  if(is_wp_error( $update_term )){
    $logger->write("Term update error: (term_id)$term_id " . $update_term->get_error_message());

  }

  update_term_meta($term_id, '_linet_cat', $cat->id);


  $picsync = get_option('wc_linet_picsync');
  if($picsync == 'on'){
    $thumbed = self::getImage($cat->pic,$logger);
    if($thumbed){
      update_term_meta($term_id, 'thumbnail_id', $thumbed);
    }
  }

  update_term_meta($term_id, '_linet_last_update',date('Y-m-d H:i:s'));
  $logger->write("Term done: (term_id)$term_id ");

  return $term_id;
}

public static function getImage($pic,$logger=false) {//unused ,$parent_id=''
  $server = WC_LI_Settings::SERVER;

  $dev = get_option('wc_linet_dev');
  if ($dev == 'on') {
    $server = WC_LI_Settings::DEV_SERVER;
  }

  $rect_img = get_option('wc_linet_rect_img');

  $basePath = wp_upload_dir()['basedir'].'/';
  $realtivePath = self::IMAGE_DIR."/".$pic;
  $filePath = $basePath.$realtivePath;


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


  if(!is_dir($basePath)) {
    mkdir($basePath);
  }

  if(!is_dir($basePath.self::IMAGE_DIR)) {
    mkdir($basePath.self::IMAGE_DIR);
  }

  if($pic!='' ){
    if(!is_file($filePath) || filesize ($filePath)==0){
      $url=$server . "/site/largethumbnail/" . $pic.(($rect_img == 'on')?"?rect=true":"");
      $logger->write("get img: ".$url);

      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
      ));
      $response = curl_exec($ch);
      $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      $content_type = explode("; ",$content_type);
      if(
        !isset($content_type[0]) ||
        (substr($content_type[0], 0, 6) !== "image/")
      ){

        return false;
      }

      $logger->write("mimetype img: ".$content_type[0]);


      $ext=substr($content_type[0], 6);

      if( isset($ext) && !empty($ext) ) {
					$filePath .= '.'. $ext;
			}

      file_put_contents($filePath,$response);
    }

    global $wpdb;
    $query = "SELECT ID FROM $wpdb->posts WHERE post_name = '%s' AND post_type = 'attachment' LIMIT 1";
    $image_id = $wpdb->get_col($wpdb->prepare($query,$pic));

    if (count($image_id) == 0) {

      $mime_type='';
      if(function_exists ('mime_content_type')){
        $mime_type=mime_content_type($filePath);
      }else{
         $finfo = new finfo(FILEINFO_MIME); //<5.3
         $mime_type=$finfo->file($filePath);
      }

      if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {//rest api!
        include( ABSPATH . 'wp-admin/includes/image.php' );
      }

      $attachment = array(
					'post_mime_type' => $mime_type,
					'post_title' => sanitize_file_name( $pic ),
					'post_content' => '',
					'post_status' => 'inherit'
				);

				$post_id = wp_insert_attachment( $attachment, $filePath );

        wp_update_attachment_metadata($post_id,wp_generate_attachment_metadata($post_id,$filePath));

    }else{
      $post_id = $image_id[0];
    }
    //*   //save new post
    return $post_id;//*/
  }

  return false;
}

public static function findByCatId($cat_id){
  $term =self::findTermByCatId($cat_id);
  if( $term ) {
     return $term->term_id;
  }
  return false;
}

public static function findTermByCatId($cat_id){

  $args = array(
     'hide_empty' => false,
     'meta_query' => array(
        array(
           'key' => '_linet_cat',
           'value' => $cat_id,
        )
     ),
     'taxonomy'  => 'product_cat',
  );

  $terms = get_terms( $args );
  if( !empty($terms) && !is_wp_error($terms) ) {
     return $terms[0];
  }

  return false;


}


public static function findByProdId($item_id){
  global $wpdb;
  $query = "SELECT * FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=$wpdb->posts.ID ".
   "WHERE ".
   //"($wpdb->posts.post_type='product' OR $wpdb->posts.post_type='product_variation') AND " .
  "$wpdb->postmeta.meta_key='_linet_id' AND $wpdb->postmeta.meta_value=%s LIMIT 1;";
  $post= $wpdb->get_col($wpdb->prepare($query,array($item_id)));

  if (count($post) == 1) {
    return $post[0];
  }
  return false;
}

  public static function findByProdSku($item_sku){
    global $wpdb;

    $query = "SELECT ID FROM $wpdb->posts ".
            "LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=$wpdb->posts.ID AND $wpdb->postmeta.meta_key='_sku'".
            " WHERE ($wpdb->posts.post_type='product' OR $wpdb->posts.post_type='product_variation') AND ".
            "$wpdb->postmeta.meta_value=%s LIMIT 1;";
    $post = $wpdb->get_col($wpdb->prepare($query,array($item_sku)));

    if (count($post) == 1) {
      return $post[0];
    }
    return false;
  }

  public static function findParentBySku($item){
    if( $item->isProduct == 0 && $item->parent_item_id != 0){
      $calc_sku = explode('-',$item->sku);
      if(is_array($calc_sku) && count($calc_sku)>=1){
        return self::findByProdSku($calc_sku[0]);
      }
    }
    return false;
  }

  public static function updateTaxonomy($item,$post_id){
    $terms = array(self::findByCatId($item->item->category_id));
    foreach($item->categories_ids as $cat){
      $terms[] = self::findByCatId($cat);
    }
    $res = wp_set_post_terms($post_id, $terms, 'product_cat');
  }


  public static function singleSyncAjax(){
        $post_id = intval($_POST['post_id']);
        $result = self::singleSync($post_id);
        if($result)
          echo json_encode(
            array(
              'status'=>'Success',
              'result'=>$result
            )
          );
        else
          echo json_encode(
            array(
              'status'=>'empty',
              'result'=>$result
            )
          );
        wp_die();
  }



  public static function singleSync($post_id){
        $metas = get_post_meta($post_id);
        $item = null;
        $found = false;
        $params = self::syncParams();
        $params['limit']=1;

        if(isset($metas['_linet_id']) && isset($metas['_linet_id'][0])){
          $params['id']=$metas['_linet_id'][0];
          $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
          if(is_array($products->body) && count($products->body)>=1){
            $item = $products->body[0];
            $found = true;
          }
        }else{
          if(isset($metas['_sku']) && isset($metas['_sku'][0])){
            $params['sku'] = $metas['_sku'][0];
            $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
            if(is_array($products->body) && count($products->body)>=1){
              $item = $products->body[0];
              self::smart_update_post_meta($post_id, '_linet_id',$products->body[0]->item->id);
            }
          }
        }

        if(!is_null($item) ){
          $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

          $result = self::singleProdSync( $item,$logger);

          $params = self::syncParams();
          $params['parent_item_id']=$products->body[0]->item->id;
          $params['limit']=50;



          $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
          foreach($products->body as $item){
            $result = self::singleProdSync( $item,$logger);
          }

          return true;
        }
        return false;
  }




  public static function saveRuler($name,$slug){
    global $wpdb;

    $query = "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies ".
            //"LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=$wpdb->posts.ID AND $wpdb->postmeta.meta_key='_sku'".
            " WHERE attribute_name=%s ".
            " LIMIT 1;";
    $post = $wpdb->get_col($wpdb->prepare($query,array($slug)));


    $attribute=array(
      'attribute_name' => $slug,
      'attribute_label' => $name,
      'attribute_type' => 'select',
      'attribute_orderby' => 'id',
      'attribute_public' => 0
    );

    if (count($post) == 1) {
      $ruler_id = $post[0];
      $wpdb->update($wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute , array('attribute_id'=>$ruler_id));
    }else{
      $ruler_id=$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
    }
    return $ruler_id;
  }

    //


public static function syncRuler($ruler,$logger){
  global $wpdb;

  $rulerslug=$ruler->slug;


  $ruler_id=self::saveRuler($ruler->name,$ruler->slug);

  foreach($ruler->units as $unit){

    $query = "SELECT term_id FROM {$wpdb->prefix}terms  WHERE name=%s  LIMIT 1;";
    $post = $wpdb->get_col($wpdb->prepare($query,array($unit->name)));
    $attr =	array("name" => $unit->name, "slug"=>strtolower(urlencode($unit->slug)), "term_group" =>0);
    if (count($post) == 1) {
      $term_id=$post[0];
      $wpdb->update($wpdb->prefix . 'terms', $attr , array('term_id'=>$term_id));
    }else{
      $term_id=$wpdb->insert( $wpdb->prefix . 'terms', $attr );
    }


    $query = "SELECT term_id FROM {$wpdb->prefix}term_taxonomy  WHERE term_id=%d AND taxonomy=%s LIMIT 1;";
    $texonomy="pa_".str_replace(" ","-",$rulerslug);
    $post = $wpdb->get_col($wpdb->prepare($query,array($term_id,$texonomy)));
    $attr =	array("term_id"=>$term_id,	"taxonomy"=>$texonomy,	"description"=>'',	"parent"=>0,	"count"=>0);
    if (count($post) == 1) {
      //$term_id=$post[0];
      //$wpdb->update($wpdb->prefix . 'terms', $attr , array('term_id'=>$term_id));
    }else{
      $term_id=$wpdb->insert( $wpdb->prefix . 'term_taxonomy', $attr );
    }
  }
}







public static function singleProdSync( $item,$logger ) {
  $user_id = 1;
  $onlyStockManage = get_option('wc_linet_only_stock_manage');

  $global_attr = get_option('wc_linet_global_attr') == 'on';

  $parent_id = false;

  if($onlyStockManage == 'on'){
    $post_id = self::findByProdSku($item->item->sku);
    $product = wc_get_product( $post_id);

    if($post_id && $product){
      //date('Y-m-d H:i:s')
      $product->update_meta_data('_linet_last_update',date('Y-m-d H:i:s'));
      $product = self::updateStock($product,$item,$logger);
    }

    $logger->write("singleProdSync only stock: (post_id,linet_id)$post_id," . $item->item->id);

    return 0;
  }

  $post_id = self::findByProdId($item->item->id);

  $product_type = "product";
  if($item->item->isProduct==0 && $item->item->parent_item_id!=0)
    $product_type = "product_variation";
  if($item->item->isProduct==3)
    $product_type = "variable";

  $logger->write("singleProdSync: $product_type(post_id,linet_id)$post_id," . $item->item->id);
  $product=false;
  if($post_id){
    $logger->write("singleProdSync update");
    $update_product_type=$product_type;

    if($update_product_type=="variable"){
      wp_set_object_terms($post_id, 'variable', 'product_type');
      $update_product_type = "product";
    }else{
      wp_set_object_terms($post_id, 'simple', 'product_type');
    }

    wp_update_post(array("ID"=>$post_id,"post_type"=>$update_product_type));

    $logger->write("singleProdSync: $post_id");

    $product = wc_get_product($post_id);

  }

  if (!$post_id || !$product){
    $logger->write("singleProdSync create");

    //$classname = WC_Product_Factory::get_product_classname( $post_id, $product_type );
    //$product = new $classname();

    if($product_type == 'product_variation'){
      $product = new WC_Product_Variation( );
    }elseif($product_type == "variable"){
      $product = new WC_Product_Variable();
    }else{
      $product = new WC_Product();
    }

    $product->set_name((string)$item->item->name);
    $product->set_description((string)$item->item->description);
    $product->set_sku($item->item->sku);

    $product->update_meta_data('_linet_id',$item->item->id);

    $logger->write("singleProdSync product save: ".$product->save());

    $post_id=$product->get_id();

  } else {

    //$classname = WC_Product_Factory::get_product_classname( $post_id, $product_type );
    //$product = new $classname($post_id);

    $product->set_name((string)$item->item->name);
    $product->set_description((string)$item->item->description);
  }




  if($item->item->isProduct==3 ){

    self::updateTaxonomy($item,$post_id);

    $not_product_attributes = get_option('wc_linet_not_product_attributes');

    if($not_product_attributes!="on"){
      $bla=array();
      //var_dump($item);exit;
      foreach($item->mutex as $in=>$prop){
        if($global_attr && isset($item->slugmutex[$in]) ){
          $perp=$item->slugmutex[$in];
          $bla["pa_".urlencode($perp->rulerSlug)]=array(
            "name"=>"pa_".$perp->rulerSlug,
            "value"=>"",

            "position"=>0,
            "is_visible"=>1,
            "is_variation"=>1,
            "is_taxonomy"=>1,
          );
            $tmparray=array();
            foreach($item->slugmutex[$in]->units as $mutexvalue){
              $tmparray[]=$mutexvalue->slug;
            }

            wp_set_object_terms($post_id,$tmparray,"pa_".$perp->rulerSlug);

        }else{
          $bla[$prop->name]=array(
            "name"=>$prop->name,
            "value"=>implode(" | ",$prop->unitnames),
            "position"=>0,
            "is_visible"=>1,
            "is_variation"=>1,
            "is_taxonomy"=>0,
          );
        }
      }

        $obj=array(
          'item_id'=>$post_id,
          'linet_item'=>$item,
          'wc_product'=>$product,
          'product_attributes'=>$bla
        );

        $obj= apply_filters( 'woocommerce_linet_product_attributes',   $obj  );
        if(isset($obj["product_attributes"]))
          $bla=$obj["product_attributes"];

      self::smart_update_post_meta($post_id,'_product_attributes', $bla, $metas);
      wc_delete_product_transients( $post_id );

      //delete_transient()
    }
  }else{
    if( $item->item->isProduct==0 && $item->item->parent_item_id!=0){

      $parent_id = self::findByProdId($item->item->parent_item_id);

      $product->set_name($item->item->sku);
      $product->set_parent_id($parent_id);

      if(is_null($item->mutex))      //we need to get attrbuts..
        $item->mutex=array();


          foreach($item->mutex as $type=>$attr){
            if($type!='SKU'){
              if ($global_attr){
                $attry=strtolower(urlencode(str_replace(" ","-",$attr->rulerslug)));
                $slug=strtolower(urlencode(str_replace(" ","-",$attr->slug)));

                self::smart_update_post_meta($post_id,'attribute_pa_'.$attry, $slug, array());
              }else{
                $attry=strtolower(urlencode(str_replace(" ","-",$type)));
                self::smart_update_post_meta($post_id,'attribute_'.$attry, $attr->name, array());

              }


            }
          }



    }else{
      self::updateTaxonomy($item,$post_id);
    }
  }

  $product->update_meta_data('_linet_last_update',date('Y-m-d H:i:s'));

  $product->set_regular_price( $item->item->saleprice );
  $product->set_price( $item->item->saleprice );
  if($item->item->discount!=0){  //discount for all
    $product->set_sale_price($item->item->saleprice-$item->item->discount);
  }else{
    $product->set_sale_price("");
  }

  $product->set_tax_status($item->item->itemVatCat_id==1? 'taxable':'none');
  try{
    $product->set_sku($item->item->sku);

  }catch(Exception $e){
    $product->set_sku($item->item->sku."--".$product->get_id());

    $logger->write("singleProdSync: double sku-".$item->item->sku);
  }

  $product->update_meta_data('_linet_id',$item->item->id);

  $product=self::updateStock($product,$item,$logger);//by parent_item_id



  $picsync = get_option('wc_linet_picsync');
  //echo $picsync;exit;
  if($picsync == 'on' && ($item->has_pictures != "0" ||$item->item->pic!="")){
    $thumbed = self::getImage($item->item->pic,$logger);
    $logger->write("Linet before thumbed Img:".$thumbed);

    if($thumbed){
      $product->set_image_id($thumbed);
      $logger->write("Linet thumbed Img: " . $thumbed);

    }

    //$imgs//get files concted to item with type
    $params=array(
      'nparent_type' => 5,
      'filetype' => 15,

      'parent_id' => $item->item->id
    );

    $galleryImgs = WC_LI_Settings::sendAPI('search/file', $params);
    $imgs=[];
    if(is_array($galleryImgs->body)){
      foreach($galleryImgs->body as $img){
        $newImg=self::getImage($img->hash,$logger);
        if($newImg)
          $imgs[]=$newImg;
      }
    }
    $logger->write("Linet GalleryImgs: " . implode(",",$imgs));
    $product->set_gallery_image_ids($imgs);
  }

  $itemFields = get_option('wc_linet_itemFields');

  if(is_array($itemFields) && isset($itemFields["linet_field"])){
    foreach ($itemFields["linet_field"] as $index_key => $key_field_linet) {
        //mybe if number add eav
        $linet_field='eav'.$key_field_linet;
        $wc_field=$itemFields["wc_field"][$index_key];

        $post_fields=array(  );

        if(isset($item->item->$linet_field)){
          $fieldValue=$item->item->$linet_field;


          if(in_array($wc_field,$post_fields)){
            $update=array('ID'=>$post_id);
            $update[$wc_field]=$fieldValue;
            wp_update_post($update);
          }elseif($wc_field=='post_date'){
            $product->set_date_created($fieldValue);
          }elseif($wc_field=='post_excerpt'){
            $product->set_short_description($fieldValue);
          }elseif($wc_field=='post_status'){
            $product->set_status($fieldValue);
          }elseif($wc_field=='post_name'){//is url name //post_title is prod name
            $product->set_slug($fieldValue);
          }else{
            //self::smart_update_post_meta($post_id,$wc_field, $fieldValue, array());
            $product->update_meta_data($wc_field,$fieldValue);

          }

        }

      // code...
    }
  }
  $product->update_meta_data('_linet_last_update',date('Y-m-d H:i:s'));


  $obj=array(
    'item_id'=>$post_id,
    'linet_item'=>$item,
    'wc_product'=>$product,
  );

  $obj= apply_filters( 'woocommerce_linet_item',   $obj  );
  if(isset($obj["wc_product"]))
    $product=$obj["wc_product"];
  $logger->write("singleProdSync product save: ".$product->save());

  $logger->write("singleProdSync: done");


  return 0;

}


public static function updateStock($product,$item,$logger){

  $stockManage = get_option('wc_linet_stock_manage');
  $qty = str_replace(",","",$item->qty);

  //if ($stockManage == 'on') {
  if ($item->item->stockType && $item->item->isProduct!=3) {
    $product->set_manage_stock('yes');
    //$product->set_stock_status(($qty<=0)?'outofstock':'instock');
    $product->set_stock_quantity($qty);
  } else {
    $product->set_manage_stock('no');
    //$product->set_stock_status('instock');
    //$product->set_stock_quantity(null);
  }
  $logger->write("updateStock product save: ".$product->save());


  return $product;
}

public static function smart_update_post_meta($post_id,$attr, $value, $metas=array()) {
  //if(isset($metas[$attr])&& $metas[$attr]!= $value){
  //  return false;
  //}
  $obj=array(
    'post_id'=>$post_id,
    'attr'=>$attr,
    'value'=>$value,
    'metas'=>$metas,
    'update'=>true
  );

  $obj= apply_filters( 'woocommerce_linet_update_post_meta',   $obj  );
  if(
    isset($obj['post_id']) &&
    isset($obj['attr']) &&
    isset($obj['value']) &&
    isset($obj['update']) &&
    $obj['update']
  )
    return update_post_meta($obj['post_id'], $obj['attr'],$obj['value']);
  return false;
}

public static function prodSync( $logger,$status) {
  $logger->write("prodSync");

  $user_id = 1;

  $params=self::syncParams();
  //$params['category_id'] = $linet_cat_id;
  $params['offset'] = 0;
  $params['since']=get_option('wc_linet_last_update');

  $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
  $products = $products->body;
  while(count($products)){
    foreach ($products as $item) {
      $logger->write("Linet Item Id: " . $item->item->id . " start sync");
      $result=self::singleProdSync( $item,$logger);

      $logger->write("Linet Item Id: " . $item->item->id . " was synced");
      unset($result);
      unset($item);
    }//end each
    $status['offset']+=count($products);
    $params['offset']+=count($products);

    wp_cache_set( 'linet_fullSync_status', $status );

    $products = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);
    //$logger->write(json_encode($products));
    if(isset($products->body))
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

  public static function fullSync() {//shuld use single?
    $status = array(
        'running' => true,
        'start' =>date('Y-m-d'),
        'offset' => 0
      );
    wp_cache_set( 'linet_fullSync_status', $status );

    $cats = WC_LI_Settings::sendAPI('search/itemcategory');
    $cats = $cats->body;

    $logger = new WC_LI_Logger(get_option('wc_linet_debug'));

    $logger->write("Start Linet Cat Sync:");
    $logger->write("max_execution_time:".ini_get('max_execution_time'));
    $logger->write("WP_CRON_LOCK_TIMEOUT:".WP_CRON_LOCK_TIMEOUT);

    foreach ($cats as $cat) {
      $wp_cat_id = self::singleCatSync($cat,$logger);
      $logger->write("Linet Cat ID:" . $cat->id);

    }

    $status=self::prodSync($logger,$status);


    $status['running']=false;
    wp_cache_set( 'linet_fullSync_status', $status );

    //update_option('wc_linet_last_update', "2018-06-01 00:00:00");
    update_option('wc_linet_last_update', date('Y-m-d')." 00:00:00");//date('Y-m-d H:m:i')
    $logger->write("End Linet Cat Sync");
  }//end func
}

  //end class
