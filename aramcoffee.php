<?php
/**
 * Plugin Name: AramCoffee
 * Plugin URI:        https://aramcoffee.com
 * Description:       پلاگین اختصاصی سایت آرام
 * Version:           1.2
 * Requires at least: 5.3
 * Requires PHP:      5.6
 * Author:            ایمان حسنی
 * Author URI:        https://t.me/+989361785477
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aramcoffee
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

define('AMCF_PRO', plugin_dir_path(__FILE__));
define('AMCF_URL', plugin_dir_url(__FILE__));

// Load modular files
require_once AMCF_PRO . 'php/add-hone.php';
require_once AMCF_PRO . 'php/checkout-fixes.php';
include_once AMCF_PRO . 'php/share-button.php';
include_once AMCF_PRO . 'php/single-post.php';


function armcff_enqueue_frontend_assets() {
    wp_enqueue_style('armcff-frontend-css', AMCF_URL . 'assets/css/frontend.css', array(), '1.2');
    wp_enqueue_script('armcff-frontend-js', AMCF_URL . 'assets/js/frontend.js', array('jquery'), '1.2', true);
}
add_action('wp_enqueue_scripts', 'armcff_enqueue_frontend_assets');

function armcff_enqueue_admin_assets() {
    wp_enqueue_media();
    wp_enqueue_style('armcff-admin-css', AMCF_URL . 'assets/css/admin.css', array(), '1.2');
    wp_enqueue_style('armcff-admin-product-meta-css', AMCF_URL . 'assets/css/admin-product-meta.css', array(), '1.2');
    wp_enqueue_script('armcff-admin-js', AMCF_URL . 'assets/js/admin.js', array('jquery'), '1.2', true);
}
add_action('admin_enqueue_scripts', 'armcff_enqueue_admin_assets');

if ( file_exists( AMCF_PRO . 'php/product-tabs.php' ) ) {
    require_once AMCF_PRO . 'php/product-tabs.php';
}

if ( file_exists( AMCF_PRO . 'php/product-meta.php' ) ) {
    require_once AMCF_PRO . 'php/product-meta.php';
}

if ( file_exists( AMCF_PRO . 'php/product-cat-subcats.php' ) ) {
    require_once AMCF_PRO . 'php/product-cat-subcats.php';
}

if ( file_exists( AMCF_PRO . 'php/aramix.php' ) ) {
    require_once AMCF_PRO . 'php/aramix.php';
}


