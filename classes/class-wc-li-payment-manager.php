<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class WC_LI_Payment_Manager {

    /**
     * @var WC_LI_Settings
     */
    private $settings;

    /**
     * WC_LI_Payment_Manager constructor.
     *
     * @param WC_LI_Settings $settings
     */
    public function __construct(WC_LI_Settings $settings) {
        $this->settings = $settings;
    }


}
