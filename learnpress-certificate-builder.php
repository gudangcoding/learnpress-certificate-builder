<?php
/*
Plugin Name: LearnPress Certificate Builder
Plugin URI: http://lkpnaura.com/
Description: Certificate Builder for LearnPress.
Version: 1.0.0
Author: Nevh Dev
Author URI: http://lkpnaura.com/
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Load necessary files
require_once 'includes/class-lp-certificate-builder.php';

// Initialize the plugin
add_action('plugins_loaded', 'lp_certificate_builder_init');
function lp_certificate_builder_init()
{
    LP_Certificate_Builder::instance();
}
