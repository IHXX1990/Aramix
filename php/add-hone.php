<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AramCoffee_H1_Settings {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_body_open', [ $this, 'output_homepage_h1' ], 10 );
    }

    public function add_menu() {
        add_submenu_page( 'options-general.php', __( 'تنظیمات تگ H1', 'aramcoffee' ), __( 'تنظیمات H1', 'aramcoffee' ), 'manage_options', 'aramcoffee-h1-settings', [ $this, 'render_page' ] );
    }

    public function register_settings() {
        register_setting( 'aramcoffee_h1_group', 'aramcoffee_enable_h1' );
        add_settings_section( 'aramcoffee_h1_section', __( 'پیکربندی تگ H1', 'aramcoffee' ), function() { echo '<p>' . esc_html__( 'فعال کردن تگ H1 در صفحه اصلی سایت.', 'aramcoffee' ) . '</p>'; }, 'aramcoffee-h1-settings' );
        add_settings_field( 'aramcoffee_enable_h1_field', __( 'فعال‌سازی تگ H1', 'aramcoffee' ), function() {
            $val = get_option( 'aramcoffee_enable_h1', '0' );
            echo '<input type="hidden" name="aramcoffee_enable_h1" value="0">';
            echo '<label><input type="checkbox" name="aramcoffee_enable_h1" value="1" ' . checked( '1', $val, false ) . '> ' . esc_html__( 'فعال در صفحه اصلی', 'aramcoffee' ) . '</label>';
        }, 'aramcoffee-h1-settings', 'aramcoffee_h1_section' );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'تنظیمات H1 آرام کافی', 'aramcoffee' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'aramcoffee_h1_group' ); do_settings_sections( 'aramcoffee-h1-settings' ); submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function output_homepage_h1() {
        if ( '1' !== get_option( 'aramcoffee_enable_h1', '0' ) ) return;
        
        if ( is_front_page() || is_home() ) {
            $site_name = get_bloginfo( 'name' );
            echo '<h1 class="aramcoffee-homepage-h1" style="position: absolute;opacity: 0;">' . esc_html( $site_name ) . '</h1>';
        }
    }
}
new AramCoffee_H1_Settings();