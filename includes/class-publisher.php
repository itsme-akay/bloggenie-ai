<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BGAI_Publisher {

    public static function publish( $content, $image_id = null ) {
        $cat_id = (int) get_option( 'bgai_category', 0 );
        $tags   = is_array( $content['tags'] ?? null ) ? $content['tags'] : array();
        $slug   = sanitize_title( $content['slug'] ?? $content['title'] );

        $data = array(
            'post_title'    => sanitize_text_field( $content['title'] ),
            'post_content'  => wp_kses_post( $content['body'] ),
            'post_status'   => 'publish',
            'post_author'   => self::author_id(),
            'post_name'     => $slug,
            'post_type'     => 'post',
        );
        if ( $cat_id > 0 ) $data['post_category'] = array( $cat_id );

        $post_id = wp_insert_post( $data, true );
        if ( is_wp_error( $post_id ) ) {
            BGAI_Logger::log( 'error', 'wp_insert_post failed: ' . $post_id->get_error_message() );
            return false;
        }

        if ( ! empty( $tags ) )  wp_set_post_tags( $post_id, $tags, false );
        if ( $image_id )         set_post_thumbnail( $post_id, $image_id );

        update_post_meta( $post_id, '_bgai_generated',    '1' );
        update_post_meta( $post_id, '_bgai_generated_at', current_time( 'mysql' ) );
        update_post_meta( $post_id, '_bgai_focus_kw',     sanitize_text_field( $content['focus_keyword'] ?? '' ) );

        return $post_id;
    }

    private static function author_id() {
        $id = (int) get_option( 'bgai_author_id', 0 );
        if ( $id > 0 ) return $id;
        $admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
        return ! empty( $admins ) ? $admins[0]->ID : 1;
    }
}
