<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BGAI_Scheduler {

    public function __construct() {
        add_action( 'init', array( $this, 'schedule' ) );
    }

    public function schedule() {
        if ( ! wp_next_scheduled( 'bgai_run_pipeline_1' ) ) {
            wp_schedule_event( self::next_ts( get_option( 'bgai_time1', '08:00' ) ), 'daily', 'bgai_run_pipeline_1' );
        }
        if ( ! wp_next_scheduled( 'bgai_run_pipeline_2' ) ) {
            wp_schedule_event( self::next_ts( get_option( 'bgai_time2', '15:00' ) ), 'daily', 'bgai_run_pipeline_2' );
        }
    }

    public static function activate() {
        BGAI_Logger::create_table();
        if ( ! wp_next_scheduled( 'bgai_run_pipeline_1' ) )
            wp_schedule_event( self::next_ts( get_option( 'bgai_time1', '08:00' ) ), 'daily', 'bgai_run_pipeline_1' );
        if ( ! wp_next_scheduled( 'bgai_run_pipeline_2' ) )
            wp_schedule_event( self::next_ts( get_option( 'bgai_time2', '15:00' ) ), 'daily', 'bgai_run_pipeline_2' );
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'bgai_run_pipeline_1' );
        wp_clear_scheduled_hook( 'bgai_run_pipeline_2' );
    }

    public static function reschedule() {
        self::deactivate();
        wp_schedule_event( self::next_ts( get_option( 'bgai_time1', '08:00' ) ), 'daily', 'bgai_run_pipeline_1' );
        wp_schedule_event( self::next_ts( get_option( 'bgai_time2', '15:00' ) ), 'daily', 'bgai_run_pipeline_2' );
    }

    public static function next_ts( $time_str ) {
        $tz     = get_option( 'timezone_string' ) ?: 'UTC';
        $now    = new DateTime( 'now', new DateTimeZone( $tz ) );
        $target = DateTime::createFromFormat( 'H:i', $time_str, new DateTimeZone( $tz ) );
        if ( ! $target || $target <= $now ) {
            if ( $target ) $target->modify( '+1 day' );
            else return time() + 86400;
        }
        return $target->getTimestamp();
    }
}
