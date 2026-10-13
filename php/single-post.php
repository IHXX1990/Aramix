<?php
add_action( 'wp_head', function () {
    if ( ! is_singular( 'post' ) ) return;

    $thumb_id = get_post_thumbnail_id();
    if ( ! $thumb_id ) return;

    $img_url = wp_get_attachment_image_url( $thumb_id, 'full' );
    if ( ! $img_url ) return;

    echo '<script>window.postFeaturedImage = ' . wp_json_encode( $img_url ) . ';</script>';
});


function wpkb_get_clock_svg() {
    return '<svg class="rt-icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <path d="M23 12C23 12.3545 22.9832 12.7051 22.9504 13.051C22.3838 12.4841 21.7204 12.014 20.9871 11.6675C20.8122 6.85477 16.8555 3.00683 12 3.00683C7.03321 3.00683 3.00683 7.03321 3.00683 12C3.00683 16.8555 6.85477 20.8122 11.6675 20.9871C12.014 21.7204 12.4841 22.3838 13.051 22.9504C12.7051 22.9832 12.3545 23 12 23C5.92487 23 1 18.0751 1 12C1 5.92487 5.92487 1 12 1C18.0751 1 23 5.92487 23 12Z" fill="currentColor"/>
        <path d="M13 11.8812L13.8426 12.3677C13.2847 12.7802 12.7902 13.2737 12.3766 13.8307L11.5174 13.3346C11.3437 13.2343 11.2115 13.0898 11.1267 12.9235C11 12.7274 11 12.4667 11 12.4667V6C11 5.44771 11.4477 5 12 5C12.5523 5 13 5.44772 13 6V11.8812Z" fill="currentColor"/>
        <path d="M15.2929 17.7071C15.6834 17.3166 16.3166 17.3166 16.7071 17.7071L17.3482 18.3482L19.2473 16.4491C19.6379 16.0586 20.271 16.0586 20.6615 16.4491C21.0521 16.8397 21.0521 17.4728 20.6615 17.8634L18.1213 20.4036C18.0349 20.49 17.9367 20.5573 17.8318 20.6054C17.4488 20.8294 16.9487 20.7772 16.6203 20.4487L15.2929 19.1213C14.9024 18.7308 14.9024 18.0976 15.2929 17.7071Z" fill="currentColor"/>
        <path fill-rule="evenodd" clip-rule="evenodd" d="M18 24C21.3137 24 24 21.3137 24 18C24 14.6863 21.3137 12 18 12C14.6863 12 12 14.6863 12 18C12 21.3137 14.6863 24 18 24ZM18 22.0181C15.7809 22.0181 13.9819 20.2191 13.9819 18C13.9819 15.7809 15.7809 13.9819 18 13.9819C20.2191 13.9819 22.0181 15.7809 22.0181 18C22.0181 20.2191 20.2191 22.0181 18 22.0181Z" fill="currentColor"/>
    </svg>';
}


add_shortcode( 'reading_time', 'wpkb_reading_time_shortcode' );
function wpkb_reading_time_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'label'     => 'زمان مطالعه:',
        'suffix'    => 'دقیقه',
        'wpm'       => 150,
        'post_id'   => null,
        'show_icon' => 'yes',
        'icon_type' => 'svg',
        'icon'      => '⏱',
    ), $atts, 'reading_time' );

    $post_id = $atts['post_id'] ? intval( $atts['post_id'] ) : get_the_ID();
    if ( ! $post_id ) return '';

    $content = get_post_field( 'post_content', $post_id );
    if ( ! $content ) return '';

    $content = strip_shortcodes( $content );
    $content = wp_strip_all_tags( $content );

    $word_count = wpkb_count_words( $content );
    $wpm        = max( 1, intval( $atts['wpm'] ) );
    $minutes    = max( 1, (int) ceil( $word_count / $wpm ) );

    $icon_html = '';
    if ( $atts['show_icon'] === 'yes' ) {
        if ( $atts['icon_type'] === 'svg' ) {
            $icon_html = wpkb_get_clock_svg() . ' ';
        } else {
            $icon_html = '<span class="rt-icon" aria-hidden="true">' . esc_html( $atts['icon'] ) . '</span> ';
        }
    }

    return sprintf(
        '<span class="reading-time" data-words="%1$d" data-wpm="%2$d" style="display: flex;gap: 8px;justify-content: right;align-items: center;">%3$s%4$s %5$s %6$s</span>',
        $word_count,
        $wpm,
        $icon_html,
        esc_html( $atts['label'] ),
        esc_html( $minutes ),
        esc_html( $atts['suffix'] )
    );
}

function wpkb_count_words( $text ) {
    $text = preg_replace( '/\s+/u', ' ', trim( $text ) );
    preg_match_all( '/[\p{L}\p{N}]+/u', $text, $matches );
    return count( $matches[0] );
}