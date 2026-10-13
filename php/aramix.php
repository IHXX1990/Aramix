<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function aramix_bootstrap() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    add_action( 'admin_menu', 'aramix_register_admin_menu' );
    add_action( 'admin_init', 'aramix_register_settings' );
    add_action( 'admin_enqueue_scripts', 'aramix_enqueue_admin_assets' );
    add_action( 'wp_enqueue_scripts', 'aramix_enqueue_frontend_assets' );
    add_shortcode( 'aramix_blender', 'aramix_blender_shortcode' );
}
add_action( 'plugins_loaded', 'aramix_bootstrap' );

function aramix_register_admin_menu() {
    add_menu_page(
        'آرامیکس',
        'آرامیکس',
        'manage_woocommerce',
        'aramix',
        'aramix_admin_page',
        'dashicons-coffee',
        26
    );
}

function aramix_register_settings() {
    register_setting(
        'aramix_settings_group',
        'aramix_products',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'aramix_sanitize_product_list',
            'default'           => '[]',
        )
    );
}

function aramix_sanitize_product_list( $value ) {
    $decoded = json_decode( stripslashes( (string) $value ), true );
    if ( ! is_array( $decoded ) ) {
        return array();
    }

    $clean = array();
    foreach ( $decoded as $item ) {
        $id = 0;

        if ( is_array( $item ) ) {
            $id = absint( $item['product_id'] ?? $item['id'] ?? 0 );
        } else {
            $id = absint( $item );
        }

        if ( $id > 0 ) {
            $clean[] = $id;
        }
    }

    return array_values( array_unique( $clean ) );
}

function aramix_get_selected_product_ids() {
    $ids = get_option( 'aramix_products', array() );

    if ( is_string( $ids ) ) {
        $ids = json_decode( stripslashes( $ids ), true );
    }

    if ( ! is_array( $ids ) ) {
        $ids = array();
    }

    $clean = array();
    foreach ( $ids as $id ) {
        $clean[] = absint( $id );
    }

    return array_values( array_unique( array_filter( $clean ) ) );
}

function aramix_get_product_options() {
    $products = array();
    $query    = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    $post_list = get_posts( $query );
    foreach ( $post_list as $post ) {
        $product = wc_get_product( $post->ID );
        if ( ! $product ) {
            continue;
        }

        $products[] = array(
            'id'    => $product->get_id(),
            'title' => $product->get_name(),
            'price' => (float) $product->get_price(),
            'sku'   => $product->get_sku() ?: '',
        );
    }

    return $products;
}

function aramix_get_selected_products() {
    $ids = aramix_get_selected_product_ids();
    $data = array();

    foreach ( $ids as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            continue;
        }

        $data[] = array(
            'id'    => $product->get_id(),
            'name'  => $product->get_name(),
            'price' => (float) $product->get_price(),
            'link'  => get_permalink( $product->get_id() ),
        );
    }

    return $data;
}

function aramix_enqueue_admin_assets( $hook_suffix ) {
    if ( 'toplevel_page_aramix' !== $hook_suffix ) {
        return;
    }

    wp_enqueue_style( 'aramix-admin-css', AMCF_URL . 'assets/css/aramix-admin.css', array(), '1.0' );
    wp_enqueue_script( 'aramix-admin-js', AMCF_URL . 'assets/js/aramix-admin.js', array( 'jquery' ), '1.0', true );

    $products = aramix_get_product_options();
    wp_localize_script(
        'aramix-admin-js',
        'ARAMIX_ADMIN_PRODUCTS',
        $products
    );
}

function aramix_enqueue_frontend_assets() {
    wp_enqueue_style( 'aramix-frontend-css', AMCF_URL . 'assets/css/aramix-frontend.css', array(), '1.0' );
    wp_enqueue_script( 'aramix-frontend-js', AMCF_URL . 'assets/js/aramix-frontend.js', array( 'jquery' ), '1.0', true );
}

function aramix_admin_page() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        echo '<div class="wrap"><h1>آرامیکس</h1><p>برای استفاده از آرامیکس، افزونه WooCommerce باید فعال باشد.</p></div>';
        return;
    }

    $selected_ids = aramix_get_selected_product_ids();
    $product_data = aramix_get_product_options();
    ?>
    <div class="wrap aramix-admin-wrap">
        <h1>آرامیکس</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'aramix_settings_group' ); ?>
            <?php do_settings_sections( 'aramix' ); ?>

            <div class="aramix-settings-box">
                <h2>محصولات قابل استفاده در چرخش قهوه</h2>
                <p>حداقل یک محصول را انتخاب کنید. در فرم جانبی و ترکیب‌ساز، این محصولات به‌صورت جستجوپذیر قابل استفاده خواهند بود.</p>
                <input type="hidden" id="aramix-products-field" name="aramix_products" value='<?php echo esc_attr( wp_json_encode( $selected_ids ) ); ?>'>
                <div id="aramix-repeater" class="aramix-repeater"></div>
                <button type="button" class="button button-primary" id="aramix-add-row">+ افزودن محصول</button>
            </div>

            <?php submit_button( 'ذخیره تنظیمات' ); ?>
        </form>
    </div>
    <?php
}

function aramix_blender_shortcode() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return '<p>برای نمایش آرامیکس، ووکامرس باید فعال باشد.</p>';
    }

    $selected_ids = aramix_get_selected_product_ids();
    if ( empty( $selected_ids ) ) {
        return '<p>در صفحه آرامیکس، حداقل یک محصول انتخاب کنید تا سازنده‌ی ترکیب قهوه فعال شود.</p>';
    }

    $product_data = array();
    foreach ( $selected_ids as $id ) {
        $product = wc_get_product( $id );
        if ( ! $product ) {
            continue;
        }

        $product_data[] = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => (float) $product->get_price(),
            'url' => get_permalink( $product->get_id() ),
        );
    }

    if ( empty( $product_data ) ) {
        return '<p>هیچ محصول معتبری برای آرامیکس پیدا نشد.</p>';
    }

    $current_user = wp_get_current_user();
    $customer_name = $current_user->exists() ? $current_user->display_name : '';
    $customer_phone = $current_user->exists() ? get_user_meta( $current_user->ID, 'billing_phone', true ) : '';

    wp_localize_script(
        'aramix-frontend-js',
        'ARAMIX_FRONTEND',
        array(
            'products' => $product_data,
            'weights'  => array( 250, 500, 750, 1000, 1500, 2000 ),
            'presets'  => array(
                array(
                    'id'    => 'balanced',
                    'title' => 'متعادل',
                    'desc'  => 'شکلاتی و آجیل',
                ),
                array(
                    'id'    => 'mild',
                    'title' => 'ملایم',
                    'desc'  => 'تلخ و دودی',
                ),
                array(
                    'id'    => 'fruity',
                    'title' => 'میوه‌ای و مرکباتی',
                    'desc'  => 'روشن و ترش',
                ),
            ),
            'grindOptions' => array(
                'دان' => 'دان باشد',
                'home' => 'آسیاب خانگی',
                'frenchpress' => 'آسیاب فرنچ پرس',
                'moka' => 'آسیاب موکاپات',
                'paper' => 'آسیاب فیلتر کاغذی (فرانسه)',
                'metal' => 'آسیاب فیلتر ثابت (فرانسه)',
                'portable' => 'آسیاب دستگاه پمپی (مسافرتی)',
                'industrial' => 'آسیاب دستگاه صنعتی',
                'semiindustrial' => 'آسیاب دستگاه نیمه صنعتی',
                'capsule' => 'آسیاب برای کپسول شارژی',
            ),
            'customer' => array(
                'name'  => $customer_name,
                'phone' => $customer_phone,
            ),
        )
    );

    ob_start();
    ?>
    <div class="aramix-blender" id="aramix-blender">
        <div class="brand">☕ <b>قهوه آرام</b> · قهوه‌یاب</div>
        <div class="card" id="aramix-card">
            <div class="topbar" id="aramix-topbar">
                <button class="back-btn" type="button" data-back="true"><span>→</span><span>بازگشت</span></button>
                <span class="step-label" id="aramix-step-label"></span>
            </div>
            <div class="progress-track" id="aramix-progress-track">
                <div class="progress-fill" id="aramix-progress-fill"></div>
            </div>

            <div class="aramix-screen active" data-screen="landing">
                <h1>قهوه‌ات رو چطور پیدا کنیم؟</h1>
                <p class="sub">هر مسیری که به شما نزدیک‌تره رو انتخاب کنید.</p>
                <div class="path-options">
                    <button class="path-card" type="button" data-flow="builder">
                        <span class="path-icon">🎯</span>
                        <span>
                            <span class="path-title">می‌دونم چی می‌خوام</span>
                            <span class="path-desc">ترکیب دلخواه خودم رو بسازم</span>
                        </span>
                    </button>
                    <button class="path-card" type="button" data-flow="taste">
                        <span class="path-icon">🧭</span>
                        <span>
                            <span class="path-title">نمی‌دونم، ذائقه‌مو پیدا کنید</span>
                            <span class="path-desc">چند سوال کوتاه بپرسید</span>
                        </span>
                    </button>
                    <button class="path-card" type="button" data-flow="help">
                        <span class="path-icon">📞</span>
                        <span>
                            <span class="path-title">راهنمایی می‌خوام</span>
                            <span class="path-desc">همکارهای ما باهام تماس بگیرن</span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="aramix-screen" data-screen="builder">
                <h1>ترکیب خودتو بساز</h1>
                <p class="sub">مبدأها رو انتخاب کن و سهم هرکدام را تنظیم کن تا جمع ترکیب ۱۰۰٪ شود.</p>

                <div class="preset-title">از یک پیشنهاد شروع کن</div>
                <div class="preset-row" id="aramix-preset-row"></div>

                <div class="preset-title">یا محصول‌های موجود را انتخاب کن</div>
                <div class="origin-grid" id="aramix-origin-grid"></div>

                <div class="blend-panel">
                    <div class="blend-panel-title">ترکیب شما</div>
                    <div id="aramix-blend-rows"></div>
                    <div class="blend-status" id="aramix-blend-status"></div>
                </div>

                <div class="price-banner" id="aramix-price-banner" style="display:none;">
                    <div class="label">قیمت تقریبی هر کیلوگرم</div>
                    <div class="value" id="aramix-price-value"></div>
                </div>

                <button type="button" class="btn btn-primary" id="aramix-next-builder">ادامه</button>
            </div>

            <div class="aramix-screen" data-screen="grind">
                <div class="q-title">برای قسمت آسیاب</div>
                <div class="choice-grid" id="aramix-grind-grid"></div>
                <button type="button" class="btn btn-ghost" data-back="true">بازگشت</button>
                <button type="button" class="btn btn-primary" id="aramix-next-grind">ادامه</button>
            </div>

            <div class="aramix-screen" data-screen="weight">
                <div class="q-title">وزن دلخواه</div>
                <div class="field">
                    <label>وزن به گرم</label>
                    <div class="aramix-weight-input-wrap">
                        <input type="number" id="aramix-weight-input" min="250" max="2000" step="50" value="250" />
                        <span>گرم</span>
                    </div>
                </div>
                <div class="aramix-range-block">
                    <input type="range" id="aramix-weight-range" min="250" max="2000" step="50" value="250" />
                    <div class="aramix-scale" id="aramix-scale"></div>
                </div>
                <div class="summary-block" id="aramix-weight-summary"></div>
                <button type="button" class="btn btn-ghost" data-back="true">بازگشت</button>
                <button type="button" class="btn btn-primary" id="aramix-next-weight">ادامه</button>
            </div>

            <div class="aramix-screen" data-screen="checkout">
                <h1>ثبت سفارش</h1>
                <p class="sub">اطلاعات شما برای هماهنگی سفارش و ارسال لازم است.</p>
                <div class="field"><label>نام</label><input type="text" id="aramix-name" placeholder="نام شما"></div>
                <div class="field"><label>شماره موبایل</label><input type="tel" id="aramix-phone" placeholder="09xxxxxxxxx" maxlength="11"></div>
                <div class="summary-block" id="aramix-summary-box"></div>
                <button type="button" class="btn btn-ghost" data-back="true">بازگشت</button>
                <button type="button" class="btn btn-primary" id="aramix-submit-order">ثبت سفارش</button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
