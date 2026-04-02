<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BGAI_Admin {

    public function __construct() {
        add_action( 'admin_menu',            array( $this, 'menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_post_bgai_save',       array( $this, 'save' ) );
        add_action( 'admin_post_bgai_run',        array( $this, 'run' ) );
        add_action( 'admin_post_bgai_clear_logs', array( $this, 'clear_logs' ) );
        add_action( 'admin_post_bgai_reset_key',  array( $this, 'reset_key' ) );
    }

    public function menus() {
        add_menu_page( 'BlogGenie AI', 'BlogGenie AI', 'manage_options', 'bloggenie-ai',
            array( $this, 'dashboard' ), 'dashicons-edit-large', 30 );
        add_submenu_page( 'bloggenie-ai', 'Dashboard',    'Dashboard',    'manage_options', 'bloggenie-ai',          array( $this, 'dashboard' ) );
        add_submenu_page( 'bloggenie-ai', 'Settings',     'Settings',     'manage_options', 'bloggenie-ai-settings', array( $this, 'settings' ) );
        add_submenu_page( 'bloggenie-ai', 'Activity Log', 'Activity Log', 'manage_options', 'bloggenie-ai-logs',     array( $this, 'logs' ) );
    }

    public function assets( $hook ) {
        if ( strpos( $hook, 'bloggenie-ai' ) === false ) return;
        wp_enqueue_style(  'bgai', BGAI_URL . 'assets/css/admin.css', array(), BGAI_VERSION );
        wp_enqueue_script( 'bgai', BGAI_URL . 'assets/js/admin.js', array( 'jquery' ), BGAI_VERSION, true );
    }

    public function dashboard() { require BGAI_PATH . 'admin/page-dashboard.php'; }
    public function settings()  { require BGAI_PATH . 'admin/page-settings.php'; }
    public function logs()      { require BGAI_PATH . 'admin/page-logs.php'; }

    public function save() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorised' );
        check_admin_referer( 'bgai_save' );

        // API key — only save if new value entered (not masked)
        $new_key = trim( $_POST['bgai_api_key'] ?? '' );
        if ( ! empty( $new_key ) && strpos( $new_key, '•' ) === false ) {
            bgai_save_api_key( sanitize_text_field( $new_key ) );
        }

        // Keywords
        $raw = sanitize_text_field( $_POST['bgai_keywords_raw'] ?? '' );
        update_option( 'bgai_keywords', array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) ) );

        // Blacklist
        update_option( 'bgai_blacklist', sanitize_text_field( $_POST['bgai_blacklist'] ?? '' ) );

        // Schedule
        update_option( 'bgai_time1', sanitize_text_field( $_POST['bgai_time1'] ?? '08:00' ) );
        update_option( 'bgai_time2', sanitize_text_field( $_POST['bgai_time2'] ?? '15:00' ) );

        // General
        update_option( 'bgai_tone',     sanitize_text_field( $_POST['bgai_tone']     ?? 'professional and informative' ) );
        update_option( 'bgai_category', (int) ( $_POST['bgai_category'] ?? 0 ) );

        // Toggles
        update_option( 'bgai_enable_images',  isset( $_POST['bgai_enable_images'] )  ? '1' : '0' );
        update_option( 'bgai_enable_linking', isset( $_POST['bgai_enable_linking'] ) ? '1' : '0' );
        update_option( 'bgai_enable_yoast',   isset( $_POST['bgai_enable_yoast'] )   ? '1' : '0' );
        update_option( 'bgai_enable_faq',     isset( $_POST['bgai_enable_faq'] )     ? '1' : '0' );

        BGAI_Scheduler::reschedule();

        wp_redirect( admin_url( 'admin.php?page=bloggenie-ai-settings&saved=1' ) );
        exit;
    }

    public function run() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorised' );
        check_admin_referer( 'bgai_run' );
        bgai_run_pipeline();
        wp_redirect( admin_url( 'admin.php?page=bloggenie-ai&ran=1' ) );
        exit;
    }

    public function clear_logs() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorised' );
        check_admin_referer( 'bgai_clear_logs' );
        BGAI_Logger::clear();
        wp_redirect( admin_url( 'admin.php?page=bloggenie-ai-logs&cleared=1' ) );
        exit;
    }

    public function reset_key() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorised' );
        check_admin_referer( 'bgai_reset_key' );
        delete_option( 'bgai_openai_key_enc' );
        wp_redirect( admin_url( 'admin.php?page=bloggenie-ai-settings&tab=general&reset=1' ) );
        exit;
    }
}
