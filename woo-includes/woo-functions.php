<?php

/**
 * Functions used by plugins
 */
require_once 'class-wc-dependencies.php';

/**
 * WC Detection
 */
if (!function_exists('is_woocommerce_active')) {

    function is_woocommerce_active() {
        return LI_WC_Dependencies::woocommerce_active_check();
    }

}
