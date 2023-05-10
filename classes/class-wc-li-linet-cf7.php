<?php
/**
 * Class Linet_Cf7
 * @see https://developers.elementor.com/custom-form-action/
 * Custom cf7 form action after submit to add a subsciber to
 */

if (!defined('ABSPATH')) {
  exit;
} // Exit if accessed directly

class WC_LI_Linet_Cf7
{

  public function setup_hooks()
  {
    //if( is_plugin_active('contact-form-7/wp-contact-form-7.php') )
    add_action('wpcf7_before_send_mail', array($this, 'handle_cf7_forms'));
  }

  public function handle_cf7_forms($post)
  {

    $form_id = $post->id();

    $submission = WPCF7_Submission::get_instance();

    $posted_data = $submission->get_posted_data();

    $form = WPCF7_ContactForm::get_instance($form_id);

    $map = get_option('wc_linet_cf7' . $form_id);

    if (isset($map["sync"]) && $map["sync"] == "on") {

      $contact = [];

      foreach ($post->scan_form_tags() as $field) {
        if (isset($posted_data[$field->name]) && $map[$field->name]["value_type"] == "map") {
          if (gettype($posted_data[$field->name]) !== "array")
            $contact[$map[$field->name]["linet_value"]] = $posted_data[$field->name];
          else
            $contact[$map[$field->name]["linet_value"]] = implode(",", $posted_data[$field->name]);
        }
      }
      try {
        $newLinItem = WC_LI_Settings::sendAPI('create/account', $contact);
        //var_dump($newLinItem);
      } catch (\Exception $e) {
        //flashy_log("There was an error from CF7 event list.");
        //flashy_log($e->getTraceAsString());
      }
    }
    //exit;
  }
}