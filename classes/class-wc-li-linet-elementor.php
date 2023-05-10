<?php
/**
 * Class Linet_Elementor
 * @see https://developers.elementor.com/custom-form-action/
 * Custom elementor form action after submit to add a subsciber to
 * Sendy list via API
 */

use ElementorPro\Modules\Forms\Controls\Fields_Map;

class WC_LI_Linet_Elementor extends \ElementorPro\Modules\Forms\Classes\Action_Base
{
  /**
   * Get Name
   *
   * Return the action name
   *
   * @access public
   * @return string
   */
  public function get_name()
  {
    return 'linet';
  }

  /**
   * Get Label
   *
   * Returns the action label
   *
   * @access public
   * @return string
   */
  public function get_label()
  {
    return __('Linet', 'wc-linet');
  }

  /**
   * Run
   *
   * Runs the action after submit
   *
   * @access public
   * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
   * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
   */
  public function run($record, $ajax_handler)
  {
    $settings = $record->get('form_settings');

    // Get sumitetd Form data
    $raw_fields = $record->get('fields');

    // Normalize the Form Data
    $fields = [];

    $elementor_form_map = get_option('wc_linet_elementor_form');

    $place = array_search($settings['form_name'], $elementor_form_map['form_name']);
    $map_linet = array();
    $map_elm = array();


    if ($place !== false) {
      $map_linet = $elementor_form_map['li_field'][$place];
      $map_elm = $elementor_form_map['el_field'][$place];
    }

    foreach ($raw_fields as $id => $field) {
      $has_map = array_search($id, $map_elm);

      if ($has_map !== false) {
        $fields[$map_linet[$has_map]] = $field['value'];
      } else {
        $fields[$id] = $field['value'];
      }
    }

    //var_dump($fields);
    //exit;
    $obj = array(
      'body' => $fields,
      'raw_fields' => $raw_fields,
      'map_linet' => $map_linet,
      'map_elm' => $map_elm
    );

    $obj = apply_filters('woocommerce_linet_elmentor_create_acc', $obj);
    if (isset($obj["fields"]))
      $fields = $obj["fields"];

    $newLinItem = WC_LI_Settings::sendAPI('create/account', $fields);

    return true;
  }

  /**
   * Register Settings Section
   *
   * Registers the Action controls
   *
   * @access public
   * @param \Elementor\Widget_Base $widget
   */
  public function register_settings_section($widget)
  {
    $widget->start_controls_section(
      'section_linet',
      [
        'label' => __('linet', 'wc-linet'),
        'condition' => [
          'submit_actions' => $this->get_name(),
        ],
      ]
    );


    /*$widget->add_control(
    'linet_list',
    [
    'label' => __( 'Active', 'linet' ),
    'type' => \Elementor\Controls_Manager::SELECT,
    'options' => array("0"=>"inactive","1"=>"active"),
    ]
    );*/

    $html = sprintf(__('Please make sure the form fields (inputs) are with the correct name on your linet account <a href="%1$s" target="_blank">Full Guide</a>.', 'wc-linet'), get_admin_url() . "admin.php?page=flashy");

    $content_classes = 'elementor-panel-alert elementor-panel-alert-warning';
    $widget->add_control(
      '_linet_api_msg',
      [
        'type' => \Elementor\Controls_Manager::RAW_HTML,
        'raw' => $html,
        'content_classes' => $content_classes,
      ]
    );

    /*
    if(isset($formdata["settings"])&&is_array($formdata["settings"]["form_fields"])&& false)
    foreach ($formdata["settings"]["form_fields"] as $nextfield) {
    $widget->add_control(
    $nextfield['custom_id'],
    [
    'label' => $nextfield['field_label'],
    'type' => \Elementor\Controls_Manager::SELECT,
    'type' => \Elementor\Controls_Manager::RAW_HTML,
    'options' => $lists,
    ]
    );
    // code...
    }
    //*/


    //var_dump($formdata["settings"]["form_fields"]) ;exit;



    $widget->end_controls_section();
  }

  public function on_export($element)
  {
    return $element;
  }

}