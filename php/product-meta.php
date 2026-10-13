<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add meta boxes for product brewing method and storage content.
 */
add_action( 'add_meta_boxes', 'amcf_add_product_meta_boxes' );

function amcf_add_product_meta_boxes() {
    add_meta_box(
        'amcf_brewing_method',
        __( 'روش دم‌آوری', 'aramcoffee' ),
        'amcf_brewing_method_meta_box_callback',
        'product',
        'normal',
        'default'
    );

    add_meta_box(
        'amcf_storage',
        __( 'نگهداری', 'aramcoffee' ),
        'amcf_storage_meta_box_callback',
        'product',
        'normal',
        'default'
    );
}

function amcf_brewing_method_meta_box_callback( $post ) {
    wp_nonce_field( 'amcf_save_product_meta', 'amcf_product_meta_nonce' );
    $content = get_post_meta( $post->ID, '_amcf_brewing_method', true );
    $editor_id = 'amcf_brewing_method_editor';
    wp_editor( $content, $editor_id, array( 'textarea_name' => '_amcf_brewing_method', 'media_buttons' => true ) );
}

function amcf_storage_meta_box_callback( $post ) {
    wp_nonce_field( 'amcf_save_product_meta', 'amcf_product_meta_nonce' );
    $content = get_post_meta( $post->ID, '_amcf_storage', true );
    $editor_id = 'amcf_storage_editor';
    wp_editor( $content, $editor_id, array( 'textarea_name' => '_amcf_storage', 'media_buttons' => true ) );
}

add_action( 'save_post_product', 'amcf_save_product_meta', 10, 2 );

function amcf_save_product_meta( $post_id, $post ) {
    // Check nonce
    if ( ! isset( $_POST['amcf_product_meta_nonce'] ) || ! wp_verify_nonce( $_POST['amcf_product_meta_nonce'], 'amcf_save_product_meta' ) ) {
        return;
    }

    // Check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['_amcf_brewing_method'] ) ) {
        // Save raw content, allow HTML
        update_post_meta( $post_id, '_amcf_brewing_method', wp_kses_post( $_POST['_amcf_brewing_method'] ) );
    } else {
        delete_post_meta( $post_id, '_amcf_brewing_method' );
    }

    if ( isset( $_POST['_amcf_storage'] ) ) {
        update_post_meta( $post_id, '_amcf_storage', wp_kses_post( $_POST['_amcf_storage'] ) );
    } else {
        delete_post_meta( $post_id, '_amcf_storage' );
    }
}
