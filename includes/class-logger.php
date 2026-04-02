<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BGAI_Logger {

    const TABLE = 'bgai_logs';

    public static function create_table() {
        global $wpdb;
        $t   = $wpdb->prefix . self::TABLE;
        $sql = "CREATE TABLE IF NOT EXISTS {$t} (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            level      VARCHAR(20) NOT NULL DEFAULT 'info',
            message    TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) " . $wpdb->get_charset_collate() . ";";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function log( $level, $message ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . self::TABLE, array(
            'level'   => sanitize_text_field( $level ),
            'message' => sanitize_text_field( $message ),
        ) );
    }

    public static function get_logs( $limit = 100 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}" . self::TABLE . " ORDER BY created_at DESC LIMIT %d", $limit
        ) );
    }

    public static function get_stats() {
        global $wpdb;
        $t = $wpdb->prefix . self::TABLE;
        return array(
            'published_month' => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$t} WHERE level='success' AND created_at >= %s", date( 'Y-m-01 00:00:00' )
            ) ),
            'errors_week' => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$t} WHERE level='error' AND created_at >= %s", date( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
            ) ),
        );
    }

    public static function clear() {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE " . $wpdb->prefix . self::TABLE );
    }
}
