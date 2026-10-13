<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure WooCommerce active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

add_action( 'woocommerce_before_main_content', 'amcf_show_category_subcats', 20 );

add_action( 'wp_enqueue_scripts', 'amcf_enqueue_subcats_assets' );
function amcf_enqueue_subcats_assets() {
    if ( ! is_product_category() ) return;
    if ( function_exists( 'is_shop' ) && is_shop() ) return;

    $ver = '1.0.0';
    wp_enqueue_style( 'amcf-subcats-css', AMCF_URL . 'assets/css/subcats.css', array(), $ver );
    wp_enqueue_script( 'amcf-subcats-js', AMCF_URL . 'assets/js/subcats.js', array( 'jquery' ), $ver, true );
    wp_localize_script( 'amcf-subcats-js', 'amcfSubcats', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'amcf_subcats_nonce' ),
    ) );
}

function amcf_show_category_subcats() {
    if ( ! is_product_category() ) return;
    if ( function_exists( 'is_shop' ) && is_shop() ) return;

    $term = get_queried_object();
    if ( ! $term || ! isset( $term->term_id ) || ( isset( $term->taxonomy ) && $term->taxonomy !== 'product_cat' ) ) return;

    $subcats = get_terms( array(
        'taxonomy'   => 'product_cat',
        'parent'     => $term->term_id,
        'hide_empty' => false,
    ) );

    echo '<div class="amcf-subcats-wrap">';

    if ( empty( $subcats ) || is_wp_error( $subcats ) ) {
        echo '<div class="amcf-subcats-list amcf-subcats-none"></div>';
        echo '</div>';
        return;
    }

    $count = count( $subcats );
    $classes = $count > 4 ? 'amcf-subcats-slider' : 'amcf-subcats-grid';

    echo '<div class="amcf-subcats-list ' . esc_attr( $classes ) . '">';

    foreach ( $subcats as $sc ) {
        $image = '';
        $icon_data = get_term_meta( $sc->term_id, 'category_icon', true );

        if ( $icon_data ) {
            if ( is_array( $icon_data ) && ! empty( $icon_data['id'] ) ) {
                $icon_id = $icon_data['id'];
                if ( ! empty( $icon_data['url'] ) && function_exists( 'woodmart_is_svg' ) && woodmart_is_svg( $icon_data['url'] ) && function_exists( 'woodmart_get_svg_html' ) ) {
                    $image .= woodmart_get_svg_html( $icon_id, apply_filters( 'woodmart_cat_menu_icon_size_svg', '40x40' ), array( 'class' => 'wd-nav-img' ) );
                } else {
                    $image .= wp_get_attachment_image( $icon_id, apply_filters( 'woodmart_cat_menu_icon_size', 'thumbnail' ), false, array( 'class' => 'wd-nav-img' ) );
                }
            } else {
                $icon_url = '';
                if ( is_array( $icon_data ) && isset( $icon_data['url'] ) ) {
                    $icon_url = $icon_data['url'];
                } elseif ( is_string( $icon_data ) ) {
                    $icon_url = $icon_data;
                }

                if ( $icon_url ) {
                    $extra_classes = '';
                    if ( function_exists( 'woodmart_get_old_classes' ) ) {
                        $extra_classes = woodmart_get_old_classes( ' category-icon' );
                    }
                    $image .= '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $sc->name ) . '" class="wd-nav-img' . $extra_classes . '" />';
                }
            }
        }

        if ( empty( $image ) ) {
            $thumb_id = get_term_meta( $sc->term_id, 'thumbnail_id', true );
            if ( $thumb_id ) {
                $image = wp_get_attachment_image( $thumb_id, 'medium', false, array( 'class' => 'wd-nav-img' ) );
            } else {
                $image = '<div class="amcf-subcat-no-image"></div>';
            }
        }

        echo '<div class="amcf-subcat-item">';
        echo '<a href="' . esc_url( get_term_link( $sc ) ) . '">';
        echo '<div class="xts-preview-shown amcf-subcat-preview">';
        echo $image;
        $term_count = isset( $sc->count ) ? intval( $sc->count ) : 0;
        echo '<h3 class="amcf-subcat-title">' . esc_html( $sc->name ) . ' <span class="amcf-subcat-count">(' . esc_html( $term_count ) . ')</span></h3>';
        echo '</div>';
        echo '</a>';


        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}
