<?php
if ( ! defined('WP_UNINSTALL_PLUGIN') ) exit;
global $wpdb;
$options = array('bgai_openai_key_enc','bgai_keywords','bgai_blacklist','bgai_time1','bgai_time2','bgai_tone','bgai_category','bgai_enable_images','bgai_enable_linking','bgai_enable_yoast','bgai_enable_faq','bgai_author_id');
foreach ( $options as $o ) delete_option($o);
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bgai_logs");
wp_clear_scheduled_hook('bgai_run_pipeline_1');
wp_clear_scheduled_hook('bgai_run_pipeline_2');
delete_transient('bgai_trends_cache');
