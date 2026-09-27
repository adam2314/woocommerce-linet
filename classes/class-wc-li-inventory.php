<?php

if (!defined('ABSPATH')) {
  exit;
} // Exit if accessed directly

class WC_LI_Inventory
{
  /** Pictures already pushed to Linet, kept on the attachment. */
  const PIC_SYNC_META = '_linet_pic_sync';

  /** How long a recorded push is trusted, in seconds (30 days). */
  const PIC_SYNC_TTL = 2592000;

  /** Versions of one attachment remembered before the oldest are dropped. */
  const PIC_SYNC_KEEP = 20;

  /**
   * Most bytes one picture may weigh before it is sent to Linet (2 MB).
   *
   * The file goes up base64 encoded inside the create/file body, which is a
   * third larger again, so a heavy original is what turns one push into a
   * request Linet refuses or times out on. Over the limit the smaller
   * versions WordPress already made are used instead.
   */
  const PIC_MAX_BYTES = 2097152;

  const IMAGE_DIR = 'images';

  const SKU_PREFIX = 'SKU';

  /**
   * What Linet calls one cell of a matrix. Cells pushed by older versions of
   * this plugin, and ones made by hand, are 0 instead, so both are read back
   * as the same thing.
   */
  const ITEM_MUTEX_CHILD = 4;

  /** Term meta holding the Linet itemcategory id of a product_cat term */
  const CAT_META = '_linet_cat';

  /**
   * Setup the required settings hooks
   */
  public function setup_hooks()
  { //out

    //add_action('admin_init', array($this, 'register_settings'));
    //add_action('admin_menu', array($this, 'add_menu_item'));

    // Write down what this request learned about Linet, once, at the end.
    add_action('shutdown', array('WC_LI_Sync_Cache', 'flush'));

    add_filter('manage_edit-product_cat_columns', array($this, 'category_columns_head'));
    add_filter('manage_product_cat_custom_column', array($this, 'category_columns'), 10, 3);
    add_filter('manage_edit-product_cat_sortable_columns', array($this, 'category_columns_sort'));





    add_action('manage_product_posts_custom_column', array($this, 'product_columns'), 10, 2);
    add_filter('manage_edit-product_columns', array($this, 'product_columns_head'));
    add_filter('manage_edit-product_sortable_columns', array($this, 'product_columns_sort'));
    add_action('pre_get_posts', array($this, 'linet_posts_orderby'));




    //chat
    add_action('admin_footer-edit.php', array($this, 'jqurey'));

    add_action('admin_footer-edit-tags.php', array($this, 'jqurey'));
    add_action('edited_product_cat', function ($term_id) {
      if (isset($_POST['linet_id'])) {
        update_term_meta($term_id, self::CAT_META, sanitize_text_field($_POST['linet_id']));
      }
    });

    add_action('save_post_product', function ($post_id) {
      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
      if (isset($_POST['linet_id'])) {
        update_post_meta($post_id, '_linet_id', sanitize_text_field($_POST['linet_id']));
      }
    });


    add_action('quick_edit_custom_box', function ($column_name, $screen, $taxonomy) {
      if ($screen === 'edit-tags' && $taxonomy === 'product_cat' && $column_name === 'linet_id') {
        ?>
        <fieldset>
          <div class="inline-edit-col">
            <label>
              <span class="title"><?php esc_html_e('Linet ID', 'linet-erp-woocommerce-integration'); ?></span>
              <span class="input-text-wrap">
                <input type="text" name="linet_id" class="ptitle" value="">
              </span>
            </label>
          </div>
        </fieldset>
        <?php
      }


    }, 10, 3);

    add_action('quick_edit_custom_box', function ($column_name, $obj) {
      if ($obj === 'product' && $column_name === 'linet_id') {
        ?>
        <fieldset>
          <div class="inline-edit-col">
            <label>
              <span class="title"><?php esc_html_e('Linet ID', 'linet-erp-woocommerce-integration'); ?></span>
              <span class="input-text-wrap">
                <input type="text" name="linet_id" class="ptitle" value="">
              </span>
            </label>
          </div>
        </fieldset>
        <?php
      }


    }, 10, 3);




  }






  public function jqurey()
  {

    global $typenow;

    if ($typenow === 'product') {
      ?>
      <script>
        jQuery(function ($) {
          //if (typeof inlineEditPost === 'undefined') {
          //  return; // safeguard: don't run if Quick Edit not available
          //}

          var wp_inline_edit = inlineEditPost.edit;
          inlineEditPost.edit = function (id) {
            wp_inline_edit.apply(this, arguments);
            var postId = 0;
            if (typeof (id) == 'object') {
              postId = parseInt(this.getId(id));
            }
            if (postId > 0) {
              var $row = $('#post-' + postId);
              var linetId = $row.find('td.column-linet_id').text().trim();
              if (linetId === '–') linetId = '';
              $(':input[name="linet_id"]').val(linetId);
            }
          };
        });
      </script>

      <?php
    }

    $screen = get_current_screen();
    if ($screen->id === 'edit-product_cat') {
      ?>
      <script>
        jQuery(function ($) {
          //if (typeof inlineEditPost === 'undefined') {
          //  return; // safeguard: don't run if Quick Edit not available
          //}
          var wp_inline_edit_function = inlineEditTax.edit;
          inlineEditTax.edit = function (id) {
            wp_inline_edit_function.apply(this, arguments);
            var termId = 0;
            if (typeof (id) == 'object') {
              termId = parseInt(this.getId(id));
            }
            if (termId > 0) {
              var $row = $('#tag-' + termId);
              var linetId = $row.find('td.column-linet_id').text().trim();
              if (linetId === '–') linetId = '';
              $(':input[name="linet_id"]').val(linetId);
            }
          };
        });
      </script>
      <?php
    }
  }


  public function category_columns_head($columns)
  {
    $columns['linet_id'] = __('Linet ID', 'linet-erp-woocommerce-integration');
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

      $linet_id = get_term_meta($term_id, self::CAT_META, true);

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
      echo "<a target='_blank' href='https://app.linet.org.il/item/update?id=" . esc_attr($linet_id) . "'>" . esc_html($linet_id) . "</a>";
    }
    if ($column == 'linet_last_update') {
      echo esc_html(get_post_meta($post_id, '_linet_last_update', true));
    }

  }

  function product_columns_head($clmns)
  {
    $clmns['linet_id'] = __('Linet ID', 'linet-erp-woocommerce-integration');
    $clmns['linet_last_update'] = __('Linet Update', 'linet-erp-woocommerce-integration');
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
    //global $wpdb;
    //$wpdb->query();
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
    //echo json_encode("Success");
    //wp_die();

  }


  public static function CleanOrphAjax()
  {
    //global $wpdb;
    //$wpdb->query();
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

  public static function WpCatSyncAjax()//adam 2025 needs fix
  {
    //$genral_item = get_option('wc_linet_genral_item');
    $cat_id = intval($_POST['id']);
    $catName = sanitize_text_field($_POST['catName']);

    // No limit here, unlike the lookups by sku and by id: the only thing this
    // answer is used for is how many rows came back, so asking for one row
    // would make every category read as holding a single item. If Linet ever
    // carries a total in the envelope, that is what this should ask for with
    // limit 1 instead of reading the whole category to count it.
    $products = WC_LI_Settings::sendAPI('newsearch/item', array(
      'query' => array('category_id' => $cat_id),
    ));

    global $wpdb;
    $term_ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT t.term_id
    FROM {$wpdb->posts} AS p
    INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
    INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id
    INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
    WHERE p.post_type = 'product'
      AND p.post_status = 'publish'
      AND pm.meta_key = '_linet_id'
      AND pm.meta_value = %d
      AND tt.taxonomy = 'product_cat'
      AND t.name = %s
    ", $cat_id, $catName));

    $arr = array(
      'id' => $cat_id,
      'linet_count' => count(WC_LI_Settings::apiRows($products)),
      'wc_count' => 'na'
    );

    if (count($term_ids) != 0) {
      $term_id = $term_ids[0]->term_id;
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

    if ($mode == 2) {
      //phase 1: push every product_cat to Linet, before any item is touched
      $offset = intval($_POST['offset']);

      // A fresh run is never refused on the strength of one that stalled some
      // time ago.
      if (0 === $offset) {
        WC_LI_Rate_Limiter::start_run();
      }
      $logger->write("WP->Linet Cat Sync Pulse:$offset");
      echo json_encode(self::WpSmallCatsSyncAjax($offset, $logger));

    } elseif ($mode == 0) {
      //phase 2: count items to sync
      $count = self::publishedProductCount();

      $logger->write("Start WP->Linet Sync:$count");

      echo json_encode($count);

    } else {
      $offset = intval($_POST['offset']);
      $logger->write("WP->Linet Sync Pulse:$offset");
      echo json_encode(self::WpSmallItemsSyncAjax($offset, $logger));
    }
    wp_die();
  }

  /**
   * All product_cat term ids, parents before children, so a category is
   * always pushed to Linet after its parent already has a Linet id.
   */
  public static function wpCatSyncOrder()
  {
    $terms = get_terms(array(
      'taxonomy' => 'product_cat',
      'hide_empty' => false,
    ));

    if (is_wp_error($terms) || !is_array($terms)) {
      return array();
    }

    $depths = array();
    foreach ($terms as $term) {
      $depths[$term->term_id] = count(get_ancestors($term->term_id, 'product_cat', 'taxonomy'));
    }
    asort($depths);

    return array_keys($depths);
  }

  /**
   * One pulse of the category phase: sync categories from $offset until the
   * runtime budget runs out.
   */
  public static function WpSmallCatsSyncAjax($offset, $logger)
  {
    // The same lock as the item push: the two are phases of one run, both
    // write to Linet, and a pulse the page gave up on is still working.
    if (!WC_LI_Settings::lock('wp_push', self::PUSH_LOCK_TTL)) {
      $logger->write("WpSmallCatsSyncAjax: another push is running, offset $offset left alone");

      return array(
        'status' => 'Success',
        'busy' => true,
        'synced' => 0,
        'offset' => $offset,
        'total' => count(self::wpCatSyncOrder()),
        'done' => false,
        'waited' => WC_LI_Rate_Limiter::waited(),
      );
    }

    try {
      return self::WpSmallCatsSyncRun($offset, $logger);
    } finally {
      WC_LI_Settings::unlock('wp_push');
    }
  }

  /**
   * One pulse of the category push, under the lock WpSmallCatsSyncAjax() holds.
   */
  private static function WpSmallCatsSyncRun($offset, $logger)
  {
    $term_ids = self::wpCatSyncOrder();
    $total = count($term_ids);

    $synced = 0;
    $skipped = 0;
    $skip_linked = self::pushSkipsLinked();
    $runtime = microtime(true);

    for ($i = $offset; $i < $total; $i++) {
      // Linet has stopped answering. Walking the rest of the run would cost a
      // timeout a call and knock at a door that is plainly shut, so it is
      // handed back to the page with the reason instead.
      if (WC_LI_Rate_Limiter::stalled()) {
        $logger->write("WpSmallCatsSyncAjax: Linet is not answering, the push stops at offset " . ($offset + $synced));

        return array(
          'status' => 'Error',
          'error' => __('Linet stopped answering', 'linet-erp-woocommerce-integration'),
          'synced' => $synced,
          'offset' => $offset + $synced,
          'total' => $total,
          'done' => false,
          'waited' => WC_LI_Rate_Limiter::waited(),
        );
      }

      if (microtime(true) - $runtime >= WC_LI_Settings::RUNTIME_LIMIT) {
        break;
      }
      // Mapped to a Linet category already, and the run was told to leave
      // those alone. Counted like any other, both because the page follows
      // the offset and because the phase stops on a pulse that did nothing.
      if ($skip_linked && (int) get_term_meta($term_ids[$i], self::CAT_META, true)) {
        $synced++;
        $skipped++;
        continue;
      }

      self::WpSingleCatSync($term_ids[$i], $logger);
      $synced++;
    }

    if ($skipped) {
      $logger->write("WpSmallCatsSyncAjax: $skipped categories at offset $offset are already mapped, left alone");
    }

    return array(
      'status' => 'Success',
      'total' => $total,
      'synced' => $synced,
      'offset' => $offset + $synced,
      'done' => ($offset + $synced) >= $total,
      'waited' => WC_LI_Rate_Limiter::waited(),
    );
  }

  /**
   * Push a single product_cat term to Linet and return its Linet category id.
   */
  public static function WpSingleCatSync($term, $logger = null, $seen = array())
  { //wp->linet
    if (is_numeric($term)) {
      $term = get_term((int) $term, 'product_cat');
    }

    if (!$term || is_wp_error($term)) {
      return 0;
    }

    if (in_array($term->term_id, $seen)) {
      return 0; //broken hierarchy, don't loop
    }
    $seen[] = $term->term_id;

    $catBody = array(
      'name' => $term->name,
      'profit' => 1,
      'parent_id' => 0, //0 = top level, same as the Linet->WP side reads it
    );

    if ($term->parent) {
      //parent_id holds the parent's Linet category id, not the wp term id
      $parent_cat_id = (int) get_term_meta($term->parent, self::CAT_META, true);
      if (!$parent_cat_id) {
        $parent_cat_id = (int) self::WpSingleCatSync($term->parent, $logger, $seen);
      }
      if ($parent_cat_id) {
        $catBody['parent_id'] = $parent_cat_id;
      }
    }

    //already mapped? push the current name/parent onto that Linet category
    $linet_cat_id = (int) get_term_meta($term->term_id, self::CAT_META, true);

    if ($linet_cat_id) {
      $linCat = WC_LI_Settings::sendAPI('search/itemcategory', array('id' => $linet_cat_id));

      if ($linCat->errorCode == 1000) {
        //gone from Linet - drop the mapping and fall through to search by name
        if ($logger)
          $logger->write("WpSingleCatSync stale mapping: $term->name ($linet_cat_id)");
        $linet_cat_id = 0;
      } else {
        //update body pic?
        $linCat = WC_LI_Settings::sendAPI('update/itemcategory?id=' . $linet_cat_id, $catBody);
        if ($logger)
          $logger->write("WpSingleCatSync updated: $term->name ($linet_cat_id) errorCode $linCat->errorCode");

        return $linet_cat_id;
      }
    }

    $linCat = WC_LI_Settings::sendAPI('search/itemcategory', array('name' => $term->name));

    if ($linCat->errorCode == 1000) {
      //create body pic?
      $linCat = WC_LI_Settings::sendAPI('create/itemcategory', $catBody);
      if ($linCat->errorCode == 0 && $linCat->status == 200) {
        $linet_cat_id = (int) $linCat->body->id;
        update_term_meta($term->term_id, self::CAT_META, $linet_cat_id);
        if ($logger)
          $logger->write("WpSingleCatSync created: $term->name ($linet_cat_id)");
        return $linet_cat_id;
      }
      if ($logger)
        $logger->write("WpSingleCatSync create failed: $term->name (errorCode $linCat->errorCode)");
      return 0;
    }

    //found by name - adopt it, push our values, and remember the mapping
    $linet_cat_id = (int) $linCat->body[0]->id;
    update_term_meta($term->term_id, self::CAT_META, $linet_cat_id);
    //update body pic?
    $linCat = WC_LI_Settings::sendAPI('update/itemcategory?id=' . $linet_cat_id, $catBody);
    if ($logger)
      $logger->write("WpSingleCatSync matched: $term->name ($linet_cat_id) errorCode $linCat->errorCode");

    return $linet_cat_id;
  }

  public static function WpCatSync($product, $logger = null)
  { //wp->linet
    $terms = get_the_terms($product->get_id(), 'product_cat');

    $cats = array();
    if (is_array($terms) && count($terms) > 0) {

      foreach ($terms as $term) {
        //the category phase has already mapped these, so this is a meta read
        $linet_cat_id = (int) get_term_meta($term->term_id, self::CAT_META, true);

        if (!$linet_cat_id) {
          //term added after the category phase ran - push it now
          $linet_cat_id = (int) self::WpSingleCatSync($term, $logger);
        }

        if ($linet_cat_id) {
          $cats[] = $linet_cat_id;
        }
      }
    }

    if ($logger)
      $logger->write("WpCatSync (post_id): " . $product->get_id() . " cats: " . implode(',', $cats));

    return array_unique($cats);
  }

  /**
   * How many products and variations a push has to get through.
   */
  public static function publishedProductCount()
  {
    global $wpdb;

    return (int) $wpdb->get_var("SELECT count(ID) FROM {$wpdb->posts} as p " .
      "WHERE " .
      "(p.post_type='product' OR p.post_type='product_variation') AND " .
      "p.post_status = 'publish'");
  }

  /**
   * How long a push pulse may hold the lock before the next one takes it over.
   * A pulse gives up its own work after RUNTIME_LIMIT, the rest is the room
   * that waiting out Linet's rate limit needs.
   */
  const PUSH_LOCK_TTL = 300;

  /**
   * Most files to read off one item when building its gallery.
   *
   * A product gallery is a handful of pictures; this is only here so that an
   * item which has gathered a great many files in Linet cannot turn one
   * product into a very large answer.
   */
  const GALLERY_LIMIT = 50;

  /** The lock one pull holds, so a second does not read Linet alongside it. */
  const PULL_LOCK = 'linet_pull';

  /** How long a pull pulse may hold the lock before the next one takes over. */
  const PULL_LOCK_TTL = 300;

  /**
   * Does the WC->Linet run leave out what is already linked?
   *
   * A catalogue that has been pushed once is mostly products Linet already
   * knows, and every one of them still costs the run a search and an update.
   * With this on, anything carrying a Linet id is passed over and the run is
   * only about what has never been sent. It is the whole-catalogue run this
   * speaks for: a product pushed by hand, or on save, is meant to go up
   * whatever its id says, and is sent either way.
   *
   * @return bool
   */
  public static function pushSkipsLinked()
  {
    return get_option('wc_linet_push_skip_linked') === 'on';
  }

  public static function WpSmallItemsSyncAjax($offset, $logger)
  {
    // Only one pulse pushes at a time. Two at once look up the same sku, both
    // find nothing, and both create it, which is the duplicate sku the items
    // table refuses.
    if (!WC_LI_Settings::lock('wp_push', self::PUSH_LOCK_TTL)) {
      $logger->write("WpSmallItemsSyncAjax: another push is running, offset $offset left alone");

      return array(
        'status' => 'Success',
        'busy' => true,
        'synced' => 0,
        'offset' => $offset,
        'total' => self::publishedProductCount(),
        'done' => false,
        'waited' => WC_LI_Rate_Limiter::waited(),
      );
    }

    try {
      return self::WpSmallItemsSyncRun($offset, $logger);
    } finally {
      WC_LI_Settings::unlock('wp_push');
    }
  }

  /**
   * One pulse of the item push, under the lock WpSmallItemsSyncAjax() holds.
   */
  private static function WpSmallItemsSyncRun($offset, $logger)
  {
    global $wpdb;
    //$parent_id=$item->item->parent_item_id;
    // Ordered, because the browser walks this with an offset: without an order
    // by, the same offset is not promised to mean the same row twice, and a
    // product can be pushed twice or missed altogether.
    $product_ids = $wpdb->get_col($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} as p " .
      //"INNER JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=" . "$wpdb->posts.ID ".
      "WHERE " .
      "(p.post_type='product' OR p.post_type='product_variation') AND " .
      "p.post_status = 'publish' " .
      "ORDER BY p.ID ASC " .
      "LIMIT %d OFFSET %d;", WC_LI_Settings::STOCK_LIMIT, $offset));

    // Nothing at this offset: the run is over. Said plainly, because the total
    // was counted before the run started and anything unpublished since would
    // otherwise leave the browser asking for the same empty page for ever.
    if (!count($product_ids)) {
      $logger->write("WpSmallItemsSyncAjax: nothing left at offset $offset");

      return array(
        'status' => 'Success',
        'synced' => 0,
        'offset' => $offset,
        'total' => self::publishedProductCount(),
        'done' => true,
        'waited' => WC_LI_Rate_Limiter::waited(),
      );
    }

    $sync_count = 0;
    $skipped = 0;
    $skip_linked = self::pushSkipsLinked();
    $runtime = microtime(true);

    foreach ($product_ids as $product_id) {
      // Linet has stopped answering. Walking the rest of the run would cost a
      // timeout a call and knock at a door that is plainly shut, so it is
      // handed back to the page with the reason instead.
      if (WC_LI_Rate_Limiter::stalled()) {
        $logger->write("WpSmallItemsSyncAjax: Linet is not answering, the push stops at offset " . ($offset + $sync_count));

        return array(
          'status' => 'Error',
          'error' => __('Linet stopped answering', 'linet-erp-woocommerce-integration'),
          'synced' => $sync_count,
          'offset' => $offset + $sync_count,
          'total' => self::publishedProductCount(),
          'done' => false,
          'waited' => WC_LI_Rate_Limiter::waited(),
        );
      }

      // Out of time: stop, and let the next pulse pick these up. The browser
      // moves on by however many were done, so nothing is skipped.
      if (microtime(true) - $runtime >= WC_LI_Settings::RUNTIME_LIMIT) {
        break;
      }

      // Already in Linet, and the run was told to leave those alone. It still
      // counts towards the offset: the page walks the catalogue by that, so a
      // product passed over has to move the run along like any other.
      if ($skip_linked && self::getLinetIdFromPost($product_id)) {
        $sync_count++;
        $skipped++;
        continue;
      }

      self::wpItemSync($product_id, $logger);
      $sync_count++;
    }
    //foreach
    // One line for the pulse, rather than one per product: a catalogue that is
    // almost all linked would otherwise write thousands of them.
    if ($skipped) {
      $logger->write("WpSmallItemsSyncAjax: $skipped products at offset $offset already have a Linet id, left alone");
    }
    //sleep(1);
    return array(
      'status' => 'Success',
      'synced' => $sync_count,
      'offset' => $offset + $sync_count,
      // Counted fresh every pulse, so the bar follows a catalogue that changed
      // while the run was going.
      'total' => self::publishedProductCount(),
      'done' => false,
      'waited' => WC_LI_Rate_Limiter::waited(),
    );

  }

  /**
   * Make sure one unit of a ruler is in Linet, and say which one it is.
   *
   * The ruler and its units are the same for every product that uses the
   * attribute, so once a unit is known to be there the lookup is skipped: a
   * twelve size ruler was costing twelve calls per product, on every push.
   *
   * What is noted down is the unit's id, not merely that the unit is there:
   * the id is how a variation says which cell of the matrix it is, so a note
   * without one is no use. Notes left by an older version say only "it is
   * there", and are looked up again.
   *
   * @return int|false False when Linet did not answer, so nothing is noted down.
   */
  private static function linetSaveRulerUnit($rulerId, $name, $slug, $value, $order, $logger)
  {
    $signature = md5(implode('|', array($rulerId, $name, $slug, $value, $order)));

    $unitId = WC_LI_Sync_Cache::get('rulerunit', $signature);

    if ($unitId && is_numeric($unitId)) {
      return (int) $unitId;
    }

    $rulerUnitBody = array(
      'ruler_id' => $rulerId,
      'name' => $name,
      'value' => $value,
      'uValue' => $order,
      'slug' => $slug
    );

    $linItem = WC_LI_Settings::sendAPI('search/MutexRulerUnit', $rulerUnitBody);

    if (!WC_LI_Settings::apiOk($linItem)) {
      $logger->write("linetSaveRuler: no answer from search/MutexRulerUnit for $name");

      return false;
    }

    $unitId = false;

    if ($linItem->errorCode == 1000) {
      $newLinItem = WC_LI_Settings::sendAPI('create/MutexRulerUnit', $rulerUnitBody);

      if (!WC_LI_Settings::apiOk($newLinItem)) {
        $logger->write("linetSaveRuler: no answer from create/MutexRulerUnit for $name");

        return false;
      }

      if (isset($newLinItem->body->id)) {
        $unitId = (int) $newLinItem->body->id;
      }
    } elseif (isset($linItem->body[0]->id)) {
      $unitId = (int) $linItem->body[0]->id;
    }

    if (!$unitId) {
      $logger->write("linetSaveRuler: no unit id for $name on ruler $rulerId");

      return false;
    }

    WC_LI_Sync_Cache::remember('rulerunit', $signature, $unitId);

    return $unitId;
  }

  /**
   * The name an attribute's ruler goes under in Linet.
   *
   * @return string
   */
  private static function rulerName($attr)
  {
    $name = str_replace("pa_", "", $attr->get_taxonomy());

    if ($name == "") {
      $attribute_data = $attr->get_data();
      $name = $attribute_data['name'];
    }

    return $name;
  }

  /**
   * The id of the ruler an attribute maps to, made if it is not there yet.
   *
   * Rulers are global in Linet, so the id found for "Size" once holds for
   * every product that has a size.
   *
   * @return int|false
   */
  private static function rulerId($name, $logger)
  {
    $rulerId = WC_LI_Sync_Cache::get('ruler', md5($name));

    if ($rulerId) {
      return (int) $rulerId;
    }

    $rulerBody = array('name' => $name, 'slug' => $name); //name

    $linItem = WC_LI_Settings::sendAPI('search/MutexRuler', $rulerBody);

    if (!WC_LI_Settings::apiOk($linItem)) {
      $logger->write("linetSaveRuler: no answer from search/MutexRuler for $name");

      return false;
    }

    $rulerId = false;

    if ($linItem->errorCode == 1000) {
      $newLinItem = WC_LI_Settings::sendAPI('create/MutexRuler', $rulerBody);

      if (WC_LI_Settings::apiOk($newLinItem) && $newLinItem->errorCode == 0 && isset($newLinItem->body->id)) {
        $rulerId = (int) $newLinItem->body->id;
      }
    } elseif (isset($linItem->body[0]->id)) {
      $rulerId = (int) $linItem->body[0]->id;
    }

    if (!$rulerId) {
      $logger->write("linetSaveRuler: no ruler id for $name, attribute skipped");

      return false;
    }

    WC_LI_Sync_Cache::remember('ruler', md5($name), $rulerId);

    return $rulerId;
  }

  /**
   * How a ruler unit's value is spelled in Linet.
   *
   * @return string
   */
  private static function rulerUnitSlug($value)
  {
    $slug = str_replace(" ", "", urldecode($value));
    $slug = str_replace("-", "", $slug);
    $slug = str_replace("(", "", $slug);
    $slug = str_replace(")", "", $slug);

    return $slug;
  }

  /**
   * The code Linet files a ruler unit under.
   *
   * Linet will only take Code 39 here - digits, capitals, space and - . $ / +
   * % - so a Hebrew value is refused outright ("Only Code39 characters are
   * allowed"), and until it is given something it accepts the unit is never
   * made at all. A value that is already Code 39 once shouted is kept, so a
   * Latin shop still reads as itself; anything else falls back to a code that
   * is merely stable and unique. The unit's name and slug are untouched, so
   * what is read in Linet is still the word itself.
   *
   * @param string $slug     The unit's value as WooCommerce spells it.
   * @param string $fallback Used when that spelling is not Code 39.
   *
   * @return string
   */
  private static function rulerUnitValue($slug, $fallback)
  {
    $value = strtoupper($slug);

    if ($value !== '' && !preg_match('/[^0-9A-Z \-.$\/+%]/', $value)) {
      return $value;
    }

    return $fallback;
  }

  /**
   * A Code 39 code for an option that has no term id to fall back on.
   *
   * Local attributes are not terms, so there is no id to lean on. The code has
   * to be the same every run or the unit is made again beside itself, and it
   * has to tell two options of one ruler apart, which a digest of both does.
   *
   * @return string
   */
  private static function rulerUnitCode($rulerName, $option)
  {
    return 'U' . strtoupper(base_convert((string) crc32($rulerName . '|' . $option), 10, 36));
  }

  /**
   * One attribute's options, in the order its ruler units are numbered from.
   *
   * The units are made from this list and a variation is placed against it,
   * so both have to walk the very same list in the very same order, or a
   * variation ends up on a unit that means something else.
   *
   * Each option carries the name and value Linet holds the unit under, its
   * 1-based position, and the spellings the option can be written in - what a
   * variation holds is the term slug, which is not what Linet is given.
   *
   * @return array
   */
  private static function rulerOptions($attr)
  {
    $options = array();
    $terms = $attr->get_terms();
    $order = 0;

    if (is_null($terms)) {
      $attribute_data = $attr->get_data();
      $rulerName = self::rulerName($attr);

      foreach ($attribute_data["options"] as $term) {
        $order++;

        $slug = self::rulerUnitSlug($term);

        $options[] = array(
          'name' => $term,
          'slug' => $slug,
          'value' => self::rulerUnitValue($slug, self::rulerUnitCode($rulerName, $term)),
          'order' => $order,
          'match' => array($term),
        );
      }

      return $options;
    }

    foreach ($terms as $term) {
      $order++;

      $slug = self::rulerUnitSlug($term->slug);

      $options[] = array(
        'name' => $term->name,
        'slug' => $slug,
        'value' => self::rulerUnitValue($slug, (string) $term->term_id),
        'order' => $order,
        'match' => array($term->slug, $term->name),
      );
    }

    return $options;
  }

  public static function linetSaveRuler($attr, $item_id, $line, $logger = null)
  {
    if (!$logger) {
      $logger = new WC_LI_Logger(get_option('wc_linet_debug'));
    }

    $name = self::rulerName($attr);

    $rulerId = self::rulerId($name, $logger);

    if (!$rulerId) {
      return false;
    }

    // This one really is per item, but a push repeats it for every product on
    // every run, so it is worth noting down too.
    $typeMapBody = array(
      'item_id' => $item_id,
      'ruler_id' => $rulerId,
      'line' => $line
    );

    $typeMapSig = md5(implode('|', array($item_id, $rulerId, $line)));

    if (!WC_LI_Sync_Cache::known('typemap', $typeMapSig)) {
      $linItem = WC_LI_Settings::sendAPI('search/MutexTypeMap', $typeMapBody);

      if (WC_LI_Settings::apiOk($linItem)) {
        $mapped = true;

        if ($linItem->errorCode == 1000) {
          $newLinItem = WC_LI_Settings::sendAPI('create/MutexTypeMap', $typeMapBody);
          $mapped = WC_LI_Settings::apiOk($newLinItem) && $newLinItem->errorCode == 0;
        }

        if ($mapped) {
          WC_LI_Sync_Cache::remember('typemap', $typeMapSig);
        }
      } else {
        $logger->write("linetSaveRuler: no answer from search/MutexTypeMap for $name");
      }
    }

    foreach (self::rulerOptions($attr) as $option) {
      self::linetSaveRulerUnit($rulerId, $option['name'], $option['slug'], $option['value'], $option['order'], $logger);
    }

    //var_dump();exit;


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

    return self::SKU_PREFIX . $post_id;
  }


  /**
   * Where a variation's value sits in the parent's list of options.
   *
   * The position is what tells one variation's sku from its siblings', so a
   * value that cannot be placed has to stay unplaced: answering with a
   * position anyway gives every variation of the product the same sku, and
   * the sku is what the push looks items up by, so the whole set ends up on
   * one Linet item.
   *
   * @param int    $product_id     Parent product.
   * @param string $attribute_name Attribute name, without the attribute_ prefix.
   * @param string $value          Value as the variation holds it.
   * @param object $logger
   *
   * @return int|false 1-based position, or false if the value is not an option.
   */
  public static function get_product_attribute_index( $product_id, $attribute_name, $value,$logger ) {
    $product = wc_get_product( $product_id );

    if ( ! $product ) {
      return false;
    }

    $attributes = $product->get_attributes();
    //$logger->write(print_r($attributes,true));

    if ( ! isset( $attributes[$attribute_name] ) ) {
        $logger->write("get_product_attribute_index: $attribute_name is not an attribute of product $product_id");

        return false;
    }

    $attribute = $attributes[$attribute_name];

    // Get full option list. A variation holds the slug of the term, but a
    // term whose slug was not made by WordPress itself does not always spell
    // it the way sanitizing the name would - Hebrew terms in particular are
    // sometimes kept as they were typed - so the name counts as a spelling
    // of the option too.
    // The position has to be counted off the very list that linetSaveRuler()
    // numbers the ruler units from, which is the attribute's own option order
    // as the product saved it. wc_get_product_terms() answers in alphabetical
    // order instead, so counting from it put the variation on a different unit
    // of the ruler than the one it means whenever the two orders disagreed.
    $options = array();

    foreach ( self::rulerOptions( $attribute ) as $option ) {
      $options[] = $option['match'];
    }

    $logger->write("get_product_attribute_index " . urldecode( $value ));
    //$logger->write(print_r($options,true));

    $index = self::attributeOptionIndex( $value, $options );

    if ( false === $index ) {
      $logger->write("get_product_attribute_index: " . urldecode( $value ) . " is not one of the options of $attribute_name on product $product_id");

      return false;
    }

    return $index + 1;
}

  /**
   * Position of a value among a parent's options, counting from zero.
   *
   * @param string $value
   * @param array  $options One entry per option, each the spellings it has.
   *
   * @return int|false
   */
  private static function attributeOptionIndex( $value, $options )
  {
    $wanted = self::attributeValueForms( $value );

    if ( empty( $wanted ) ) {
      // An attribute left on "any" has no value to place.
      return false;
    }

    foreach ( $options as $index => $spellings ) {
      foreach ( $spellings as $spelling ) {
        if ( array_intersect( $wanted, self::attributeValueForms( $spelling ) ) ) {
          return $index;
        }
      }
    }

    return false;
  }

  /**
   * The spellings one attribute value can be written in.
   *
   * What a variation holds and what the attribute offers are both written by
   * hand often enough that one of them may be percent-encoded, or cased,
   * differently from the other, so each is compared in every form it can take
   * rather than in one chosen form.
   *
   * @param string $value
   *
   * @return array
   */
  private static function attributeValueForms( $value )
  {
    $value = (string) $value;

    $forms = array(
      $value,
      urldecode( $value ),
      sanitize_title( $value ),
      sanitize_title( urldecode( $value ) ),
    );

    $forms = array_filter( $forms, 'strlen' );

    foreach ( $forms as $key => $form ) {
      $forms[$key] = function_exists( 'mb_strtolower' ) ? mb_strtolower( $form, 'UTF-8' ) : strtolower( $form );
    }

    return array_values( array_unique( $forms ) );
  }

  /**
   * Where a variation sits on its parent's rulers, as Linet wants it told.
   *
   * The old way of saying this was the sku: the parent's sku and then one
   * number per ruler, the position of the value among the parent's options.
   * Linet reads the cell off the item itself now - eavMTR{ruler_id} holding
   * the id of the MutexRulerUnit - which is what leaves the sku free.
   *
   * An attribute that cannot be placed is left out rather than guessed at: a
   * wrong cell puts the variation somewhere in the matrix it does not belong,
   * which is worse than its not being placed at all.
   *
   * @param object $product Variation.
   * @param object $logger
   *
   * @return array eavMTR{ruler_id} => unit id. Empty when nothing was placed.
   */
  private static function mutexCells($product, $logger)
  {
    $cells = array();

    $parent = wc_get_product($product->get_parent_id());

    if (!$parent) {
      $logger->write("mutexCells: variation " . $product->get_id() . " has no parent product");

      return $cells;
    }

    $attributes = $parent->get_attributes();

    foreach (wc_get_product_variation_attributes($product->get_id()) as $attr_name => $value) {
      $attr_name = str_replace("attribute_", "", $attr_name);

      $logger->write("mutexCells " . urldecode($attr_name) . ": " . urldecode($value));

      if (!isset($attributes[$attr_name])) {
        $logger->write("mutexCells: $attr_name is not an attribute of product " . $parent->get_id());

        continue;
      }

      $attr = $attributes[$attr_name];

      if (!$attr->get_variation()) {
        continue;
      }

      $options = self::rulerOptions($attr);
      $spellings = array();

      foreach ($options as $option) {
        $spellings[] = $option['match'];
      }

      $index = self::attributeOptionIndex($value, $spellings);

      if (false === $index) {
        // An attribute left on "any" lands here too, and rightly: there is no
        // one unit of the ruler for it to sit on.
        $logger->write("mutexCells: " . urldecode($value) . " is not one of the options of $attr_name, no cell for it");

        continue;
      }

      $rulerId = self::rulerId(self::rulerName($attr), $logger);

      if (!$rulerId) {
        continue;
      }

      $option = $options[$index];

      $unitId = self::linetSaveRulerUnit($rulerId, $option['name'], $option['slug'], $option['value'], $option['order'], $logger);

      if (!$unitId) {
        continue;
      }

      $cells['eavMTR' . $rulerId] = $unitId;
    }

    return $cells;
  }

  /**
   * The sku a variation was pushed under before the rulers carried the cell.
   *
   * Worth knowing only so that an item that went up under it is found and
   * renamed, rather than created a second time beside itself.
   *
   * @return string
   */
  private static function legacyVariationSku($product, $logger)
  {
    $parent_sku = self::getProdSku($product->get_parent_id());
    $sku = array($parent_sku);
    $placed = false;

    foreach (wc_get_product_variation_attributes($product->get_id()) as $attr_name => $value) {
      $index = self::get_product_attribute_index($product->get_parent_id(), str_replace("attribute_", "", $attr_name), $value, $logger);

      if (false === $index) {
        $placed = false;
        break;
      }

      $sku[] = $index;
      $placed = true;
    }

    if (!$placed) {
      $sku = array($parent_sku, $product->get_id());
    }

    return implode("-", $sku);
  }

  /**
   * Did Linet answer with "there is no such thing"?
   *
   * Everything comes back wrapped in {status, text, body, errorCode}, and 1000
   * is the code for a search that matched nothing. A call that did not come
   * back at all is not that, which matters: nothing may be created on it.
   *
   * @param mixed $res
   *
   * @return bool
   */
  private static function apiMissing($res)
  {
    return is_object($res) && isset($res->errorCode) && (int) $res->errorCode === 1000;
  }

  /**
   * Did a create/ call come back with an item?
   *
   * @param mixed $res
   *
   * @return bool
   */
  private static function apiCreated($res)
  {
    return WC_LI_Settings::apiOk($res) &&
      isset($res->errorCode) &&
      (int) $res->errorCode === 0 &&
      isset($res->body->id) &&
      $res->body->id;
  }

  /**
   * Whatever Linet said went wrong, short enough for one log line.
   *
   * A refused call can arrive as the usual envelope, or, when the items table
   * itself refused, as a bare yii exception with name and message.
   *
   * @param mixed $res
   *
   * @return string
   */
  private static function apiReason($res)
  {
    if (!is_object($res)) {
      return 'no answer';
    }

    foreach (array('text', 'message', 'name') as $field) {
      if (isset($res->$field) && is_string($res->$field) && $res->$field !== '') {
        return substr($res->$field, 0, 300);
      }
    }

    return substr((string) json_encode($res, JSON_UNESCAPED_UNICODE), 0, 300);
  }

  /**
   * The id of the Linet item that carries this sku.
   *
   * Told apart on purpose: false is Linet saying there is no such item, and
   * null is Linet not saying anything. Only the first of those is a reason to
   * create, because the sku column is unique in the items table and an insert
   * over a sku that is already there comes back as a database error, not as a
   * refusal the sync can read.
   *
   * @param string        $itemSku
   * @param WC_LI_Logger  $logger
   * @param bool          $inactive_too Look for switched off items as well.
   *                                    active 0 means hidden from every
   *                                    listing, the searches included, while
   *                                    the sku still holds the unique key, and
   *                                    the only way to see those is to ask for
   *                                    active 0 on its own: that query leaves
   *                                    the live items out in return.
   *
   * @return int|false|null
   */
  private static function linetItemIdBySku($itemSku, $logger, $inactive_too = false)
  {
    // newsearch is where Linet documents filtering: the fields go under query,
    // and limit/offset page the answer. A sku is unique in the items table, so
    // one row is all there is to find and asking for more only makes Linet
    // build a page that is thrown away.
    $queries = array(array('newsearch/item', array(
      'limit' => 1,
      'query' => array('sku' => $itemSku),
    )));

    if ($inactive_too) {
      $queries[] = array('newsearch/item', array(
        'limit' => 1,
        'query' => array('sku' => $itemSku, 'active' => 0),
      ));
    }

    $answered = false;
    $first_row = false;

    foreach ($queries as $query) {
      $res = WC_LI_Settings::sendAPI($query[0], $query[1]);

      if (self::apiMissing($res)) {
        $answered = true;
        continue;
      }

      if (!WC_LI_Settings::apiOk($res)) {
        continue;
      }

      $answered = true;

      foreach (WC_LI_Settings::apiRows($res) as $row) {
        if (!isset($row->id) || !$row->id) {
          continue;
        }

        // The sku that was asked for wins, so a search that answers more
        // widely than it was asked cannot hand the product the wrong item.
        if (isset($row->sku) && 0 === strcasecmp((string) $row->sku, (string) $itemSku)) {
          return (int) $row->id;
        }

        if (false === $first_row) {
          $first_row = (int) $row->id;
        }
      }
    }

    if (false !== $first_row) {
      return $first_row;
    }

    return $answered ? false : null;
  }

  public static function WpItemSync($post_id, $logger)
  { //wp->linet
    //$pf = new WC_Product_Factory();
    //$product = $pf->get_product($item->ID);

    $product = wc_get_product($post_id);

    $logger->write("WpItemSync (post_id): $post_id");

    $metas = get_post_meta($product->get_id());

    $cats_id = self::WpCatSync($product, $logger);
    //get term meta?

    $itemSku = $product->get_sku();
    if ($itemSku == '') {
      $itemSku = self::SKU_PREFIX . $post_id;
    }

    $stockType = 0;
    $ammount = 0;
    $saleprice = 0;


    $stockType = $product->get_manage_stock() == 'yes' ? 1 : 0;
    $ammount = $product->get_stock_quantity();

    $saleprice = $product->get_price();
    $saleprice = $product->get_regular_price();
    $ssprice = $product->get_sale_price();

    $isProduct = 1;

    $terms = wp_get_object_terms($product->get_id(), 'product_type');

    $product_type = $product->get_type();
    $logger->write("WpItemSync get_type: $product_type");


    if (isset($terms[0])) {
      if ($terms[0]->name == 'variable') {
        $isProduct = 3;
        $logger->write("WpItemSync sku(variable): " . $itemSku);
      }
    }

    $is_variation = false;
    $mutex_cells = array();

    if ($product_type == 'product_variation' || $product_type == 'variation') {
      $isProduct = self::ITEM_MUTEX_CHILD;
      $is_variation = true;

      // Which cell of the parent's matrix this is is said by the ruler unit
      // ids below, not by numbers packed into the sku the way it used to be,
      // so the sku is free to be the variation's own.
      //
      // get_sku() on a variation that has none of its own answers with the
      // parent's. The push looks items up by sku, so taking that would hand
      // the parent's Linet item to the child: what the variation actually
      // holds is what counts, and a variation holding nothing is told apart
      // by its id.
      $parent_sku = self::getProdSku($product->get_parent_id());

      $itemSku = $product->get_sku('edit');

      if ($itemSku == '' || $itemSku == $parent_sku) {
        $itemSku = self::SKU_PREFIX . $post_id;
      }

      $mutex_cells = self::mutexCells($product, $logger);

      if (!count($mutex_cells)) {
        $logger->write("WpItemSync: variation " . $product->get_id() . " sits on no ruler, pushed without a cell");
      }

      $logger->write("WpItemSync sku($product_type): $itemSku " . json_encode($mutex_cells));

    }

    // An int, because a parent that has not been pushed yet has no _linet_id
    // and get_post_meta() answers with '', which went up to Linet as "".
    $parent_item_id = 0;
    if ($product->get_parent_id()) {
      $parent_item_id = (int) self::getLinetIdFromPost($product->get_parent_id());
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
    // Where the variation sits on each of the parent's rulers: the id of the
    // ruler unit, under the id of the ruler it belongs to. Empty for anything
    // that is not a variation.
    foreach ($mutex_cells as $mutex_field => $mutex_unit_id) {
      $body[$mutex_field] = $mutex_unit_id;
    }

    $sale_pricelist_id = get_option('wc_linet_sale_pricelist_id');

    if ($ssprice && $sale_pricelist_id) {
      $body['price' . $sale_pricelist_id] = $ssprice;
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
      // This only asks whether the id noted on the product is still in Linet,
      // so one row is all it wants.
      $linItem = WC_LI_Settings::sendAPI('newsearch/item', array(
        'limit' => 1,
        'query' => array('id' => $item_id),
      ));

      // Nothing came back for the id. Taken two ways, because search/item says
      // so with errorCode 1000 while a row count of nought is the other way an
      // answer has of saying it, and newsearch is not documented either way.
      // An answer that never arrived is neither: that falls through to the
      // update, as it did before, rather than unpicking the product's id on
      // the strength of a call that failed.
      $nothing = self::apiMissing($linItem) ||
        (WC_LI_Settings::apiOk($linItem) && !WC_LI_Settings::apiRows($linItem));

      if ($nothing) {
        // The id noted on the product is not in Linet any more, so the sku is
        // what decides below, rather than updating something that is gone.
        $item_id = false;
      } else {
        $linItem = WC_LI_Settings::sendAPI('update/item?id=' . $item_id, $body);
      }

    }

    if (!$item_id) {
      $found = self::linetItemIdBySku($itemSku, $logger);

      // Linet did not answer the search at all (timed out, or the rate limit
      // ran out): creating now would be creating on a guess, and the guess
      // that is wrong is exactly the duplicate sku. Left for the next run.
      if (null === $found) {
        $logger->write("WpItemSync: sku $itemSku could not be looked up, not pushed");

        return false;
      }

      // A variation pushed by an older version went up under a sku built of
      // the parent's sku and one number per ruler. Now that it goes up under
      // its own, that item has to be found and renamed, or the push makes a
      // second item beside it.
      if (false === $found && $is_variation) {
        $legacy_sku = self::legacyVariationSku($product, $logger);

        if ($legacy_sku != '' && $legacy_sku != $itemSku) {
          $legacy_id = self::linetItemIdBySku($legacy_sku, $logger);

          if (null === $legacy_id) {
            $logger->write("WpItemSync: sku $legacy_sku could not be looked up, not pushed");

            return false;
          }

          if ($legacy_id) {
            $logger->write("WpItemSync: variation $post_id is item $legacy_id under its old sku $legacy_sku, renaming it to $itemSku");

            $found = $legacy_id;
          }
        }
      }

      if (false === $found) {
        //create body pic?
        $newLinItem = WC_LI_Settings::sendAPI('create/item', $body);

        if (self::apiCreated($newLinItem)) {
          $found = (int) $newLinItem->body->id;
        } else {
          // Linet refused the item. The usual reason is that the sku is
          // already on an item the plain search does not hand back: one that
          // was switched off in Linet, or one another pulse of this same run
          // made a moment ago. Either way the items table answers with a raw
          // "Duplicate entry ... for key 'sku'", and adopting whatever holds
          // the sku is what stops the product failing the same way for ever.
          $logger->write("WpItemSync: create refused for sku $itemSku: " . self::apiReason($newLinItem));

          $found = self::linetItemIdBySku($itemSku, $logger, true);

          if (!$found) {
            $logger->write("WpItemSync: sku $itemSku was neither created nor found, nothing pushed");

            return false;
          }

          $logger->write("WpItemSync: sku $itemSku is item $found in Linet, updating it instead");

          WC_LI_Settings::sendAPI('update/item?id=' . $found, $body);
        }
      } else {
        //update body pic?
        WC_LI_Settings::sendAPI('update/item?id=' . $found, $body);
      }

      $item_id = $found;
      self::smart_update_post_meta($product->get_id(), '_linet_id', $item_id);
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
            $typeId = self::linetSaveRuler($attr, $item_id, $line, $logger);

            // Only a ruler that was actually mapped takes up an axis. Counting
            // on regardless left a gap - 1, then 3 - and the axis a ruler sits
            // on is what orders the matrix, so the gap was not harmless.
            if ($typeId) {
              $line++;
            }
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
        self::savePicToLinet($item_id, $metas['_thumbnail_id'][0], true, $logger);
      }

      if (
        isset($metas['_product_image_gallery']) &&
        $metas['_product_image_gallery'][0] &&
        $metas['_product_image_gallery'][0] != ""
      ) {
        $images_id = explode(",", $metas['_product_image_gallery'][0]);
        foreach ($images_id as $img_id)
          self::savePicToLinet($item_id, $img_id, false, $logger);
      }
    }

    return true;
  }


  /**
   * Fingerprint of one picture as it was last pushed to Linet.
   *
   * Everything that would make the push different is in here: which Linet item
   * it hangs off, which file it is, whether the bytes changed, and whether it
   * went up as the item thumbnail (filetype 10) or as a gallery image (15).
   *
   * @return string
   */
  private static function picSignature($linet_item_id, $wp_attached_file, $path, $thumb)
  {
    return md5(implode('|', array(
      $linet_item_id,
      $wp_attached_file,
      (string) @filesize($path),
      (string) @filemtime($path),
      $thumb ? 'thumb' : 'gallery',
    )));
  }

  /**
   * How long a recorded push is trusted before the picture is looked up in
   * Linet again. Re-checking is what heals a file deleted at the Linet end, so
   * the filter is the way to make that quicker (or 0 to always ask).
   *
   * @return int
   */
  private static function picSyncTtl()
  {
    return (int) apply_filters('woocommerce_linet_pic_sync_ttl', self::PIC_SYNC_TTL);
  }

  /**
   * Has this exact picture already been pushed, recently enough to trust?
   *
   * @return bool
   */
  private static function picAlreadySynced($post_id, $signature)
  {
    $ttl = self::picSyncTtl();

    if ($ttl <= 0) {
      return false;
    }

    $seen = get_post_meta($post_id, self::PIC_SYNC_META, true);

    if (!is_array($seen) || !isset($seen[$signature])) {
      return false;
    }

    return (time() - (int) $seen[$signature]) < $ttl;
  }

  /**
   * Record a picture that is now known to be in Linet.
   */
  private static function rememberPicSynced($post_id, $signature)
  {
    $seen = get_post_meta($post_id, self::PIC_SYNC_META, true);

    if (!is_array($seen)) {
      $seen = array();
    }

    // Drop what has aged out, so an attachment that keeps being re-cropped
    // does not collect a row per version for ever.
    $cut = time() - max(self::picSyncTtl(), 0);

    foreach ($seen as $key => $when) {
      if ((int) $when < $cut) {
        unset($seen[$key]);
      }
    }

    $seen[$signature] = time();

    if (count($seen) > self::PIC_SYNC_KEEP) {
      asort($seen);
      $seen = array_slice($seen, -self::PIC_SYNC_KEEP, null, true);
    }

    update_post_meta($post_id, self::PIC_SYNC_META, $seen);
  }

  /**
   * Most bytes one picture may weigh, filter included.
   *
   * 0 or less turns the limit off and sends the original whatever its size.
   *
   * @return int
   */
  private static function picMaxBytes()
  {
    return (int) apply_filters('woocommerce_linet_pic_max_bytes', self::PIC_MAX_BYTES);
  }

  /**
   * The largest version of an attachment that is still under the size limit.
   *
   * The original is used when it fits. When it does not, the sizes WordPress
   * generated on upload sit next to it on disk, so the widest of those that
   * fits goes up instead of the original - a smaller picture in Linet being
   * better than none. An attachment with no version small enough (a huge file
   * uploaded before its sizes were made, say) has nothing to send.
   *
   * @return string|false absolute path of the file to send
   */
  private static function picSourceFile($post_id, $path, $logger)
  {
    $max = self::picMaxBytes();

    if ($max <= 0) {
      return $path;
    }

    $size = @filesize($path);

    // An unreadable size is not a reason to drop the picture; the original is
    // what would have been sent before this limit existed.
    if ($size === false || $size <= $max) {
      return $path;
    }

    $meta = wp_get_attachment_metadata($post_id);
    $sizes = (isset($meta['sizes']) && is_array($meta['sizes'])) ? $meta['sizes'] : array();

    // Widest first, so the best picture that fits is the one that is taken.
    uasort($sizes, function ($a, $b) {
      $aw = isset($a['width']) ? (int) $a['width'] : 0;
      $bw = isset($b['width']) ? (int) $b['width'] : 0;

      return $bw <=> $aw;
    });

    $dir = trailingslashit(dirname($path));

    foreach ($sizes as $name => $size_meta) {
      if (empty($size_meta['file'])) {
        continue;
      }

      $candidate = $dir . $size_meta['file'];

      if (!file_exists($candidate)) {
        continue;
      }

      $candidate_size = @filesize($candidate);

      if ($candidate_size === false || $candidate_size > $max) {
        continue;
      }

      $logger->write(sprintf(
        'savePicToLinet: %s is %s, over the %s limit, sending the %s version instead',
        basename($path),
        size_format($size),
        size_format($max),
        $name
      ));

      return $candidate;
    }

    $logger->write(sprintf(
      'savePicToLinet: %s is %s, over the %s limit and no smaller version fits, not sent',
      basename($path),
      size_format($size),
      size_format($max)
    ));

    return false;
  }

  public static function savePicToLinet($linet_item_id, $post_id, $thumb = false, $logger = null)
  {
    if (!$logger) {
      $logger = new WC_LI_Logger(get_option('wc_linet_debug'));
    }

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

      // Whatever is actually sent: the original when it is light enough, one
      // of its generated sizes when it is not, nothing when none of them is.
      $sourceFile = self::picSourceFile($post_id, $basePath . $wp_attached_file, $logger);

      if (!$sourceFile) {
        return false;
      }

      // A push costs a search/file, often a create/file and, for the
      // thumbnail, an update/item on top. That is most of the 60 calls a
      // minute Linet allows, spent again on every run on pictures that have
      // not changed since the last one, so a picture already known to be in
      // Linet is left alone until its fingerprint or the ttl says otherwise.
      $signature = self::picSignature($linet_item_id, $wp_attached_file, $sourceFile, $thumb);

      if (self::picAlreadySynced($post_id, $signature)) {
        $logger->write("savePicToLinet($linet_item_id/$post_id): $filename unchanged, not sent again");

        return true;
      }

      $body = [
        "name" => $filename,
        "path" => "pics/",
        "public" => 1,
        "filetype" => $thumb ? 10 : 15,
        "parent_id" => $linet_item_id,
        "nparent_type" => 5,
      ];
      // The body doubles as the search: the same fields go to create/file
      // below, so the criteria go under query and $body is left as it is.
      // One row is all this asks for, because all it wants to know is whether
      // the picture is there.
      $fileExsits = WC_LI_Settings::sendAPI('newsearch/file', array(
        'limit' => 1,
        'query' => $body,
      ));

      //var_dump($fileExsits);exit;
      // A timeout, a gateway error or missing credentials all come back as
      // false or null, so there is nothing to look at.
      if (!is_object($fileExsits)) {
        $logger->write("savePicToLinet($linet_item_id/$post_id): no answer from search/file for $filename");

        return false;
      }

      // The API answers with an object, but not always the one expected here.
      $searchStatus = isset($fileExsits->status) ? $fileExsits->status : 0;
      $searchError = isset($fileExsits->errorCode) ? $fileExsits->errorCode : -1;

      // The picture is not in Linet yet. search/file said so with errorCode
      // 1000; an answer carrying no rows is the other way of saying it, and
      // newsearch is not documented either way. Both have to mean "create it",
      // or a picture that is not there is never sent.
      $missing = ($searchStatus == 200 && $searchError == 1000) ||
        (WC_LI_Settings::apiOk($fileExsits) && !WC_LI_Settings::apiRows($fileExsits));

      if ($missing) {
        $pic = base64_encode(file_get_contents($sourceFile));

        $body["parent_type"] = "app\models\Item";
        $body["base64content"] = $pic;

        $file = WC_LI_Settings::sendAPI('create/file', $body);

        if (!is_object($file)) {
          $logger->write("savePicToLinet($linet_item_id/$post_id): no answer from create/file for $filename");

          return false;
        }
      } else {
        if (empty($fileExsits->body) || !is_array($fileExsits->body)) {
          $logger->write("savePicToLinet($linet_item_id/$post_id): newsearch/file answered status $searchStatus errorCode $searchError with no file for $filename");

          return false;
        }

        // Keep the search result intact, only the first match is used here.
        $file = clone $fileExsits;
        $file->body = $fileExsits->body[0];
      }

      if (
        $thumb &&
        isset($file->status) && $file->status == 200 &&
        isset($file->errorCode) && $file->errorCode == 0

      ) {
        if (!isset($file->body) || !is_object($file->body) || empty($file->body->hash)) {
          $logger->write("savePicToLinet($linet_item_id/$post_id): $filename has no hash, item image not updated");

          return false;
        }

        $body = array(
          'pic' => $file->body->hash
        );
        $linItem = WC_LI_Settings::sendAPI('update/item?id=' . $linet_item_id, $body);
        //var_dump($linItem);exit;
        //update item image
      }

      self::rememberPicSynced($post_id, $signature);

      return true;
    }

    return false;
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
      // Phase one of a pull, so the count starts clean.
      WC_LI_Rate_Limiter::start_run();

      if (!WC_LI_Settings::lock(self::PULL_LOCK, self::PULL_LOCK_TTL)) {
        $logger->write("catSyncAjax: another pull is running, the categories are left alone");
        echo json_encode(self::pullBusy());
        wp_die();
      }

      // wp_die() is kept outside, because it exits and a finally does not run
      // on the way out.
      try {
        $payload = self::linetCatSyncRun($logger);
      } finally {
        WC_LI_Settings::unlock(self::PULL_LOCK);
      }

      echo json_encode($payload);
      wp_die();
    }


    if ($mode === "ItemSync") {
      $offset = intval($_POST['offset']);

      if (!WC_LI_Settings::lock(self::PULL_LOCK, self::PULL_LOCK_TTL)) {
        $logger->write("catSyncAjax: another pull is running, offset $offset left alone");
        echo json_encode(self::pullBusy($offset));
        wp_die();
      }

      try {
        $payload = self::linetItemSyncRun($offset, $logger);
      } finally {
        WC_LI_Settings::unlock(self::PULL_LOCK);
      }

      echo json_encode($payload);
      wp_die();
    }

    if ($mode == 3) { //doUpdateCall
      update_option('wc_linet_last_update', gmdate('Y-m-d') . " 00:00:00"); //date('Y-m-d H:i:s')

      echo json_encode("done");

      wp_die();
    }

    echo json_encode(array('status' => 'nothing'));

    wp_die();
  }

  /**
   * Pull every category from Linet, under the lock catSyncAjax() holds.
   */
  private static function linetCatSyncRun($logger)
  {
    $res = WC_LI_Settings::sendAPI('newsearch/itemcategory', self::syncCatParams());

    if (self::pullFailed($res)) {
      $logger->write("LinetItemSync: no answer for the categories, the pull is stopped\n");

      return self::pullError();
    }

    $cats = self::linetCatSyncOrder(WC_LI_Settings::apiRows($res));

    foreach ($cats as $cat) {
      self::singleCatSync($cat, $logger);
    }

    return array(
      'status' => 'Success',
      'cats' => count($cats),
      'waited' => WC_LI_Rate_Limiter::waited(),
    );
  }

  /**
   * One pulse of the item pull, under the lock catSyncAjax() holds.
   */
  private static function linetItemSyncRun($offset, $logger)
  {
    $params = self::syncParams();
    $params['offset'] = $offset;
    $params['since'] = get_option('wc_linet_last_update');

    $res = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);

    if (self::pullFailed($res)) {
      $logger->write("LinetItemSync: no answer for offset $offset, the pull is stopped\n");

      return self::pullError($offset);
    }

    $products = WC_LI_Settings::apiRows($res);

    $runtime = microtime(true);
    $sync_count = 0;

    if (is_array($products)) {
      foreach ($products as $prod) {
      // Linet has stopped answering. Walking the rest of the run would cost a
      // timeout a call and knock at a door that is plainly shut, so it is
      // handed back to the page with the reason instead.
      if (WC_LI_Rate_Limiter::stalled()) {
          $logger->write("LinetItemSync: Linet is not answering, the pull stops at offset " . ($offset + $sync_count));

          return self::pullError($offset + $sync_count, $sync_count);
        }

        if (microtime(true) - $runtime < WC_LI_Settings::RUNTIME_LIMIT) {
          self::singleProdSync($prod, $logger);
          $sync_count++;
        }
      }
    }

    return array(
      'status' => 'Success',
      'items' => $sync_count,
      'offset' => $offset + $sync_count,
      'waited' => WC_LI_Rate_Limiter::waited(),
    );
  }

  /**
   * Did a pull call come back with something that can be walked?
   *
   * An answer that is not there is not the same as an answer with no rows in
   * it. sendAPI() returns null on a network error, on a rate limit that
   * outlived its retries, and on an html error page where json was meant to
   * be; all three reach apiRows() as an empty list, which the page reads as
   * "there is nothing left in Linet" and ends the run calling it a success.
   *
   * @return bool True when the call did not come back, so the run has to stop.
   */
  private static function pullFailed($res)
  {
    // An endpoint that answers with a bare list instead of Linet's envelope.
    if (is_array($res)) {
      return false;
    }

    return !WC_LI_Settings::apiOk($res);
  }

  /**
   * The answer a pulse gets when another one is already talking to Linet.
   */
  private static function pullBusy($offset = 0)
  {
    return array(
      'status' => 'Success',
      'busy' => true,
      'items' => 0,
      'cats' => 0,
      'offset' => $offset,
      'waited' => WC_LI_Rate_Limiter::waited(),
    );
  }

  /**
   * The answer a pulse gets when Linet did not answer it.
   */
  private static function pullError($offset = 0, $items = 0)
  {
    return array(
      'status' => 'Error',
      'error' => __('Linet did not answer', 'linet-erp-woocommerce-integration'),
      'items' => $items,
      'cats' => 0,
      'offset' => $offset,
      'waited' => WC_LI_Rate_Limiter::waited(),
    );
  }

  /**
   * Linet categories sorted parents before children, so findByCatId() can
   * always resolve a parent that arrived in the same batch.
   */
  public static function linetCatSyncOrder($cats)
  {
    if (!is_array($cats)) {
      return array();
    }

    $by_id = array();
    foreach ($cats as $cat) {
      if (isset($cat->id)) {
        $by_id[$cat->id] = $cat;
      }
    }

    $depths = array();
    foreach ($cats as $index => $cat) {
      $depth = 0;
      $parent_id = isset($cat->parent_id) ? (int) $cat->parent_id : 0;
      $walked = array();

      //walk up while the parent is in this batch; anything above that is
      //either already in wp or outside the filter, so it counts as a root
      while ($parent_id && isset($by_id[$parent_id]) && !in_array($parent_id, $walked)) {
        $walked[] = $parent_id;
        $depth++;
        $parent_id = (int) $by_id[$parent_id]->parent_id;
      }

      $depths[$index] = $depth;
    }

    asort($depths);

    $ordered = array();
    foreach (array_keys($depths) as $index) {
      $ordered[] = $cats[$index];
    }

    return $ordered;
  }

  public static function singleCatSync($cat, $logger)
  {
    global $wpdb;

    $term = self::findTermByCatId($cat->id);
    //parent always goes in, so a category flattened in Linet gets flattened here
    $catParams = array('name' => $cat->name, 'parent' => 0);

    if ($cat->parent_id != 0) {
      $parent_term_id = self::findByCatId($cat->parent_id);
      if ($parent_term_id) {
        $catParams['parent'] = $parent_term_id;
      } else {
        $logger->write("Parent cat not in wp: (parent_id)$cat->parent_id for $cat->name");
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


        $term_id = $wpdb->get_col($wpdb->prepare("SELECT * FROM {$wpdb->terms} as t
          LEFT JOIN {$wpdb->term_taxonomy} as tt ON tt.term_id = t.term_id
          WHERE
          t.name=%s AND
          tt.taxonomy = 'product_cat'
          LIMIT 1;
          ", $cat->name));
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
      self::CAT_META,

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

    update_term_meta($term_id, self::CAT_META, $cat->id);
    $picsync = get_option('wc_linet_picsync');
    if ($picsync == 'on') {
      $thumbed = self::getImage($cat->pic, $logger);
      if ($thumbed) {
        update_term_meta($term_id, 'thumbnail_id', $thumbed);
      }
    }

    update_term_meta($term_id, '_linet_last_update', gmdate('Y-m-d H:i:s'));
    $logger->write("Term done: (term_id)$term_id ");

    return $term_id;
  }




  public static function get_image_extension_from_mime($mime_type)
  {
    $map = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/gif' => 'gif',
      'image/webp' => 'webp',
      'image/bmp' => 'bmp',
      'image/svg+xml' => 'svg',
      'image/tiff' => 'tiff',
      'image/x-icon' => 'ico',
    ];

    return isset($map[$mime_type]) ? $map[$mime_type] : false;
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



    $filePath = wp_normalize_path($basePath . $realtivePath);

    $basePath = wp_normalize_path($basePath);
    $imageDir = wp_normalize_path($basePath . self::IMAGE_DIR);

    // Ensure base directory exists
    if (!is_dir($basePath)) {
      wp_mkdir_p($basePath);
    }

    // Ensure image directory exists
    if (!is_dir($imageDir)) {
      wp_mkdir_p($imageDir);
    }

    if ($pic != '') {
      if (!is_file($filePath) || filesize($filePath) == 0) {



        $logger->write("get img: " . $url);



        $args = array(
          'method' => 'POST',
          'sslverify' => !$dev,
          'headers' => array(
            'Content-Type' => 'application/json',
            'Wordpress-Site' => str_replace("http://", "", str_replace("https://", "", get_site_url())),
            'Wordpress-Plugin' => WC_Linet::VERSION,
          ),
          //'body' => json_encode($body),
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
          $error_message = $response->get_error_message();
          $logger->write('Request failed:' . " $error_message\n");
        }

        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);

        $content_type = isset($headers['content-type']) ? $headers['content-type'] : 'application/octet-stream';







        //var_dump($url);
        //var_dump($content_type);exit;


        $logger->write("mimetype img: " . $content_type);
        $ext = self::get_image_extension_from_mime($content_type);
        if (!$ext) {

          return false;
        }

        $filePath .= '.' . $ext;

        file_put_contents($filePath, $body);
      }

      global $wpdb;
      $image_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'attachment' LIMIT 1", $pic));

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
          'key' => self::CAT_META,
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
    $post = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} as p LEFT JOIN {$wpdb->postmeta} as pm ON pm.post_id = p.ID WHERE " .
      "p.post_type in ('product','product_variation') AND " .
      "pm.meta_key = %s AND pm.meta_value = %s LIMIT 1;", $meta, $value));

    if (count($post) == 1) {
      return wc_get_product($post[0]);
    }
    return false;
  }





  /**
   * Is this Linet item one cell of a matrix, rather than a product of its own?
   *
   * @param object $item The item as Linet hands it back.
   *
   * @return bool
   */
  public static function isMutexChild($item)
  {
    if (!isset($item->parent_item_id) || !$item->parent_item_id) {
      return false;
    }

    $type = (int) $item->isProduct;

    return 0 === $type || self::ITEM_MUTEX_CHILD === $type;
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
    WC_LI_Settings::verify_ajax_request();

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


    global $wpdb;

    $products = $wpdb->get_col($wpdb->prepare(
      "SELECT ID FROM {$wpdb->posts} p WHERE " .
      " p.post_status = 'publish' AND " .
      "(p.post_type='product' OR p.post_type='product_variation') AND " .
      " (p.ID=%d OR p.post_parent=%d)"

      ,
      $post_id,
      $post_id
    ));


    foreach ($products as $product)
      $result = self::WpItemSync($product, $logger);
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
    WC_LI_Settings::verify_ajax_request();

    if (!WC_LI_Settings::items_sync_enabled()) {
      wp_send_json(array('status' => 'items sync is off'), 403);
    }

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

    $post = $wpdb->get_col($wpdb->prepare("SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies " .
      //"LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id=$wpdb->posts.ID AND $wpdb->postmeta.meta_key='_sku'".
      " WHERE attribute_name=%s AND attribute_label=%s" .
      " LIMIT 1;", $slug, $name));

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

    $old_attr = get_option('wc_linet_old_attr') == 'on';


    $logger->write("singleProdSync start: " . $item->item->id);


    $parent_id = false;
    $post_id = false;

    $product = self::findByProdId($item->item->id);

    if ($onlyStockManage == 'on') {

      if (!$product)
        $product = self::findByProdSku($item->item->sku);

      if ($product) {
        //date('Y-m-d H:i:s')
        $product->update_meta_data('_linet_last_update', gmdate('Y-m-d H:i:s'));
        $product = self::updateStock($product, $item, $logger);
        $logger->write("singleProdSync only stock: (post_id,linet_id){$product->get_id()}," . $item->item->id);

      }


      return 0;
    }

    //$post_id = self::findByProdId($item->item->id);
    $product_type = "product";
    $product_fc_type = "product";


    if ($product)
      $post_id = $product->get_id();

    if (self::isMutexChild($item->item)) {
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
      if ($product) {
        $post_id = $product->get_id();
      }
      $logger->write("singleProdSync by sku: $product_type(linet_id)," . $item->item->id);

    }



    if ($product && is_object($product) && $product_type != $product->get_type()) {
      $post_id = $product->get_id();
      $logger->write("singleProdSyncType  $product_type(linet_id)," . $product->get_type());

      $logger->write("singleProdSyncType update");
      $update_product_type = $product_type;

      if ($update_product_type == "variable") {
        $update_product_type = "product";
      }

      wp_update_post(array("ID" => $post_id, "post_type" => $update_product_type));

      // Set WC product type term AFTER wp_update_post so save_post hooks
      // (WooCommerce or third-party) cannot reset it back to 'simple'.
      if ($product_type == "variable") {
        wp_set_object_terms($post_id, 'variable', 'product_type');
      } else if ($product_type == "product_variation") {
        wp_set_object_terms($post_id, '', 'product_type');
      } else {
        wp_set_object_terms($post_id, 'simple', 'product_type');
      }

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

      //$product->set_name((string) $item->item->name);
      $product->set_name(wp_slash((string) $item->item->name));                                                                  

      if (!$no_description)
        $product->set_description((string) $item->item->description);
      $product->set_sku($item->item->sku);

      $product->update_meta_data('_linet_id', $item->item->id);

      $logger->write("singleProdSync new product save: " . $product->save());

      $post_id = $product->get_id();

    } else {

      //$classname = WC_Product_Factory::get_product_classname( $post_id, $product_type );
      //$product = new $classname($post_id);

      //$product->set_name((string) $item->item->name);
      $product->set_name(wp_slash((string) $item->item->name));                                                                  
      if (!$no_description)
        $product->set_description((string) $item->item->description);
    }


    if ($item->item->isProduct == 3) {

      self::updateTaxonomy($item, $product);

      $logger->write("singleProdSync updateTaxonomy mutex parent");


      $not_product_attributes = get_option('wc_linet_not_product_attributes');

      if ($not_product_attributes != "on") {
        $attributes = $old_attr ? $product->get_attributes() : array();


        //var_dump($item);exit;
        foreach ($item->mutex as $mutexIndex => $fullRuler) {

          $attribute = new WC_Product_Attribute();
          $attribute->set_position(0);
          $attribute->set_visible(1);
          $attribute->set_variation(1);

          // What this ruler's attribute is filed under. Each branch below
          // sets its own: keying them all off one variable left every ruler
          // after the first writing into the slot of the one before it, so a
          // parent with three rulers came back carrying one attribute.
          $key = '';

          if ($global_attr ) {
            if(isset($item->slugmutex[$mutexIndex])){
              $cutRoler = $item->slugmutex[$mutexIndex];

              $taxonomy = wc_attribute_taxonomy_name($cutRoler->rulerSlug);
              $key = $taxonomy;
  
              $tmparray = array();
  
              foreach ($cutRoler->units as $rolerUnit) {
                $term_name = $rolerUnit->name;
                $term_slug = sanitize_title(strtolower($rolerUnit->slug));
  
                if (!$term = get_term_by('slug', $term_slug, $taxonomy)) {
                  wp_insert_term($term_name, $taxonomy, array('slug' => $term_slug));
                  $term = get_term_by('slug', $term_slug, $taxonomy);
                }

                $logger->write("singleProdSync term lookup taxonomy:$taxonomy slug:$term_slug -> term_id:{$term->term_id} name:" . $term->name);

                $tmparray[] = (int) $term->term_id;
                $logger->write("singleProdSync tmparray " . $term->term_id);
  
              }
  
              $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
              $attribute->set_name($taxonomy);
              $attribute->set_options($tmparray);
  
              //Save main product to get its id
  
              $logger->write("singleProdSync mutex $taxonomy $post_id " . json_encode($tmparray));
  
            }

          } else {
            $attribute->set_id(0);
            $attribute->set_name($fullRuler->name);
            $attribute->set_options($fullRuler->unitnames);
            $key = sanitize_title($fullRuler->name);

          }

          // Nothing was found to build this ruler's attribute from, so there
          // is nothing to file: better one attribute short than one standing
          // in another's place.
          if ($key === '') {
            $logger->write("singleProdSync mutex: ruler $mutexIndex gave no attribute, left out");

            continue;
          }

          $attributes[$key] = $attribute;

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
      if (self::isMutexChild($item->item)) {

        $parent_product = self::findByProdId($item->item->parent_item_id);

        $product->set_name($item->item->sku);
        if ($parent_product) {
          $logger->write("parent_id wp,linet: " . $parent_product->get_id() . "," . $item->item->parent_item_id);
          $product->set_parent_id($parent_product->get_id());
        }


        $attributes = $old_attr ? $product->get_attributes() : array();



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

              $logger->write("singleProdSync term lookup taxonomy:$taxonomy slug:$slug -> term_id:{$term->term_id} name:" . $term->name);

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

    $product->update_meta_data('_linet_last_update', gmdate('Y-m-d H:i:s'));

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

    $product->set_tax_status( $item->item->itemVatCat_id == 1 ? 'taxable' : 'none');
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

    $product = self::updateStock($product, $item, $logger, false); //by parent_item_id, saved once at the end



    $picsync = get_option('wc_linet_picsync');
    //echo $picsync;exit;
    if ($picsync == 'on' && ($item->has_pictures != "0" || $item->item->pic != "")) {
      $thumbed = self::getImage($item->item->pic, $logger);
      $logger->write("Linet before thumbed Img:" . $thumbed);

      if ($thumbed) {
        $product->set_image_id($thumbed);
        $logger->write("Linet thumbed Img: " . $thumbed);

      }

      // has_pictures is Linet's own count of files on the item. When it is 0
      // there is nothing for newsearch/file to find, and that call was being made
      // for every product on every run: on a 1000 product catalogue it is the
      // whole minute's budget, sixteen times over.
      if ($item->has_pictures != "0") {
        //$imgs//get files concted to item with type
        $params = array(
          'nparent_type' => 5,
          'filetype' => 15,
          'parent_id' => $item->item->id
        );

        // A gallery, so this one is not after a single row. It is bounded all
        // the same, rather than reading however many files an item has
        // gathered in Linet to build a gallery out of the first handful.
        $galleryImgs = WC_LI_Settings::sendAPI('newsearch/file', array(
          'limit' => (int) apply_filters('woocommerce_linet_gallery_limit', self::GALLERY_LIMIT),
          'query' => $params,
        ));

        // Only touch the gallery when Linet actually answered. A refused or
        // failed call used to come back as nothing to show, which emptied the
        // product's gallery - across the catalogue, once the minute's budget
        // was spent.
        if (WC_LI_Settings::apiOk($galleryImgs)) {
          $imgs = array();

          foreach (WC_LI_Settings::apiRows($galleryImgs) as $img) {
            $newImg = self::getImage($img->hash, $logger);
            if ($newImg)
              $imgs[] = $newImg;
          }

          $logger->write("Linet GalleryImgs: " . implode(",", $imgs));
          $product->set_gallery_image_ids($imgs);
        } else {
          $logger->write("Linet GalleryImgs: no answer from newsearch/file, gallery left as it is");
        }
      } else {
        $product->set_gallery_image_ids(array());
      }
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
    $product->update_meta_data('_linet_last_update', gmdate('Y-m-d H:i:s'));

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

    // The stock update used to do this halfway through, before the rest of the
    // item had even been written.
    wc_delete_product_transients($product->get_id());
    clean_post_cache($product->get_id());

    $logger->write("singleProdSync: done");

    return 0;

  }

  /**
   * @param bool $save Write the product out here. singleProdSync passes false
   *                   because it carries on editing and saves once at the end;
   *                   saving twice doubled the db writes of a whole sync.
   */
  public static function updateStock($product, $item, $logger, $save = true)
  {

    $stockManage = get_option('wc_linet_stock_manage');
    $qty = str_replace(",", "", $item->qty);

    if ($stockManage == 'on' && $item->item->stockType && $item->item->isProduct != 3) {
      $product->set_manage_stock('yes');
      $product->set_stock_quantity($qty);
    } else {
      $product->set_manage_stock('no');
    }

    if ($save) {
      $logger->write("updateStock product save: $qty " . $product->save());

      wc_delete_product_transients($product->get_id());
      clean_post_cache($product->get_id());
    } else {
      $logger->write("updateStock qty: $qty");
    }

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

    $status['failed'] = false;

    while (true) {
      $res = WC_LI_Settings::sendAPI(self::syncStockURL(), $params);

      // A page that never came back is not the same as the last page. A 429
      // that outlived its retries used to end the loop quietly, and fullSync
      // then moved wc_linet_last_update forward as if everything had been
      // seen, so the items behind the failed page were skipped for good.
      // Linet answers a refusal with a whole envelope, so this has to look at
      // the envelope status, not just at whether anything was decoded.
      if (!WC_LI_Settings::apiOk($res)) {
        $status['failed'] = true;
        $logger->write("prodSync: no answer at offset " . $params['offset'] . ", stopping short");
        break;
      }

      $products = WC_LI_Settings::apiRows($res);

      if (!count($products)) {
        break;
      }

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
    }
    unset($user_id);
    unset($products);
    $logger->write("prodSync end");
    return $status;
  }

  public static function fullSync()
  { //shuld use single?
    $status = array(
      'running' => true,
      'start' => gmdate('Y-m-d'),
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


    $cats = self::linetCatSyncOrder(WC_LI_Settings::apiRows($cats));

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
    if (empty($status['failed'])) {
      update_option('wc_linet_last_update', gmdate('Y-m-d') . " 00:00:00"); //date('Y-m-d H:m:i')
    } else {
      // Leave the mark where it was so the next run picks the items up again.
      $logger->write("Sync stopped short, wc_linet_last_update stays at " . get_option('wc_linet_last_update'));
    }
    $logger->write("End Linet Cat Sync");
  } //end func
}

//end class