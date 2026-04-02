<?php
/**
 * Plugin Name:  BlogGenie AI
 * Plugin URI:   https://yourdomain.com/bloggenie-ai
 * Description:  Automatically generates and publishes 2 SEO-optimised blog posts daily using ChatGPT GPT-4o. Includes smart topic generation, DALL-E images, Yoast SEO integration and internal linking.
 * Version:      2.0.0
 * Author:       Your Name
 * License:      GPL-2.0+
 * Text Domain:  bloggenie-ai
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BGAI_VERSION', '2.0.0' );
define( 'BGAI_PATH',    plugin_dir_path( __FILE__ ) );
define( 'BGAI_URL',     plugin_dir_url( __FILE__ ) );

require_once BGAI_PATH . 'includes/class-logger.php';
require_once BGAI_PATH . 'includes/class-topics.php';
require_once BGAI_PATH . 'includes/class-content-generator.php';
require_once BGAI_PATH . 'includes/class-image-generator.php';
require_once BGAI_PATH . 'includes/class-seo-builder.php';
require_once BGAI_PATH . 'includes/class-internal-linker.php';
require_once BGAI_PATH . 'includes/class-publisher.php';
require_once BGAI_PATH . 'includes/class-scheduler.php';

register_activation_hook(   __FILE__, array( 'BGAI_Scheduler', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BGAI_Scheduler', 'deactivate' ) );

add_action( 'plugins_loaded', 'bgai_init' );

function bgai_init() {
    new BGAI_Scheduler();
    if ( is_admin() ) {
        require_once BGAI_PATH . 'admin/class-admin.php';
        new BGAI_Admin();
    }
}

add_action( 'bgai_run_pipeline_1', 'bgai_run_pipeline' );
add_action( 'bgai_run_pipeline_2', 'bgai_run_pipeline' );

function bgai_run_pipeline() {
    $api_key = bgai_get_api_key();
    if ( empty( $api_key ) ) {
        BGAI_Logger::log( 'error', 'Pipeline aborted — OpenAI API key not configured. Go to Settings to add your key.' );
        return;
    }

    $keywords = get_option( 'bgai_keywords', array() );
    if ( empty( $keywords ) ) {
        BGAI_Logger::log( 'error', 'Pipeline aborted — no keywords configured. Go to Settings > Keywords.' );
        return;
    }

    BGAI_Logger::log( 'info', 'Pipeline started.' );

    // Get topic
    $topic = BGAI_Topics::get_topic( $api_key, $keywords );
    if ( ! $topic ) {
        BGAI_Logger::log( 'error', 'Could not select a topic. Pipeline stopped.' );
        return;
    }

    BGAI_Logger::log( 'info', 'Topic selected: ' . $topic );

    // Generate content
    $content = BGAI_Content_Generator::generate( $api_key, $topic );
    if ( ! $content ) {
        BGAI_Logger::log( 'error', 'Content generation failed for topic: ' . $topic );
        return;
    }

    // Generate image
    $image_id = null;
    if ( get_option( 'bgai_enable_images', '1' ) === '1' ) {
        $image_id = BGAI_Image_Generator::generate( $api_key, $content['title'] );
        if ( ! $image_id ) {
            BGAI_Logger::log( 'warning', 'Image generation failed — publishing without featured image.' );
        }
    }

    // Internal linking
    if ( get_option( 'bgai_enable_linking', '1' ) === '1' ) {
        $content['body'] = BGAI_Internal_Linker::inject( $api_key, $content['body'] );
    }

    // Publish post
    $post_id = BGAI_Publisher::publish( $content, $image_id );
    if ( ! $post_id ) {
        BGAI_Logger::log( 'error', 'Publishing to WordPress failed.' );
        return;
    }

    // SEO meta
    if ( get_option( 'bgai_enable_yoast', '1' ) === '1' ) {
        BGAI_SEO_Builder::write_yoast( $post_id, $content );
    }
    BGAI_SEO_Builder::write_schema( $post_id, $content );

    BGAI_Logger::log( 'success', 'Post published: "' . $content['title'] . '" (ID ' . $post_id . ')' );
}

// Encrypt/decrypt API key
function bgai_get_api_key() {
    $enc  = get_option( 'bgai_openai_key_enc', '' );
    if ( empty( $enc ) ) return '';
    $salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'bgai-fallback-salt-32chars-padded';
    return openssl_decrypt( $enc, 'AES-128-ECB', substr( $salt, 0, 16 ) );
}

function bgai_save_api_key( $key ) {
    $salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'bgai-fallback-salt-32chars-padded';
    update_option( 'bgai_openai_key_enc', openssl_encrypt( $key, 'AES-128-ECB', substr( $salt, 0, 16 ) ) );
}
