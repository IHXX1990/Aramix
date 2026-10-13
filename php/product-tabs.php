<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

add_filter( 'woocommerce_product_tabs', 'amcf_modify_product_tabs', 999 );

function amcf_modify_product_tabs( $tabs ) {
    if ( isset( $tabs['description'] ) ) {
        $tabs['description']['title'] = __( 'معرفی', 'aramcoffee' );
        $tabs['description']['priority'] = 10;
    }

    $tabs['amcf_specs'] = array(
        'title'    => __( 'مشخصات', 'aramcoffee' ),
        'priority' => 20,
        'callback' => 'amcf_product_specs_tab'
    );

    $tabs['amcf_brewing'] = array(
        'title'    => __( 'روش دم‌آوری', 'aramcoffee' ),
        'priority' => 30,
        'callback' => 'amcf_product_brewing_tab'
    );

    $tabs['amcf_storage'] = array(
        'title'    => __( 'نگهداری', 'aramcoffee' ),
        'priority' => 40,
        'callback' => 'amcf_product_storage_tab'
    );

    if ( isset( $tabs['reviews'] ) ) {
        $tabs['reviews']['priority'] = 100;
    }

    return $tabs;
}

function amcf_product_specs_tab() {
    global $product;
    if ( ! $product ) return;

    $attributes = $product->get_attributes();
    if ( empty( $attributes ) ) {
        echo '<p>' . esc_html__( 'این محصول مشخصات ثابتی ندارد.', 'aramcoffee' ) . '</p>';
        return;
    }

    echo '<div class="amcf-specs-tab">';
    echo '<table class="amcf-specs-table">';
    foreach ( $attributes as $attribute ) {
        if ( $attribute->is_taxonomy() ) {
            $terms = wp_get_post_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
            $value = implode( ', ', $terms );
        } else {
            $value = $attribute->get_options();
            if ( is_array( $value ) ) $value = implode( ', ', $value );
        }

        $label = wc_attribute_label( $attribute->get_name() );
        echo '<tr><th>' . esc_html( $label ) . '</th><td>' . wp_kses_post( $value ) . '</td></tr>';
    }
    echo '</table>';
    echo '</div>';
}

function amcf_product_brewing_tab() {
    global $post;
    $content = get_post_meta( $post->ID, '_amcf_brewing_method', true );
    if ( empty( $content ) ) {
        echo '<p>' . esc_html__( 'روش دم‌آوری برای این محصول تعریف نشده است.', 'aramcoffee' ) . '</p>';
        return;
    }
    echo apply_filters( 'the_content', $content );
}

function amcf_product_storage_tab() {
    global $post;
    $content = get_post_meta( $post->ID, '_amcf_storage', true );
    if ( empty( $content ) ) {
        echo '<p>' . esc_html__( 'اطلاعات نگهداری برای این محصول تعریف نشده است.', 'aramcoffee' ) . '</p>';
        return;
    }
    echo apply_filters( 'the_content', $content );
}

add_action( 'wp_enqueue_scripts', 'amcf_enqueue_tabs_assets' );
function amcf_enqueue_tabs_assets() {
    if ( ! is_product() ) return;
    if ( file_exists( AMCF_PRO . 'assets/css/tabs.css' ) ) {
        wp_enqueue_style( 'amcf-tabs-css', AMCF_URL . 'assets/css/tabs.css', array(), '1.0' );
    }
    if ( file_exists( AMCF_PRO . 'assets/js/tabs.js' ) ) {
        wp_enqueue_script( 'amcf-tabs-js', AMCF_URL . 'assets/js/tabs.js', array('jquery'), '1.0', true );
    }
}

add_filter( 'woocommerce_product_tabs', 'amcf_force_tab_order', 10000 );
function amcf_force_tab_order( $tabs ) {
    $order = array( 'description', 'amcf_specs', 'amcf_brewing', 'amcf_storage', 'reviews' );
    $new = array();

    foreach ( $order as $key ) {
        if ( isset( $tabs[ $key ] ) ) {
            $new[ $key ] = $tabs[ $key ];
        }
    }

    foreach ( $tabs as $k => $v ) {
        if ( ! isset( $new[ $k ] ) ) {
            $new[ $k ] = $v;
        }
    }

    return $new;
}
