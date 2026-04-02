<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BGAI_Image_Generator {

    const API_URL = 'https://api.openai.com/v1/images/generations';

    public static function generate( $api_key, $title ) {
        $prompt = 'Professional blog featured image for: ' . $title . '. Clean flat design, modern digital marketing style, no text, business blog quality.';

        $payload = array(
            'model'   => 'dall-e-3',
            'prompt'  => $prompt,
            'n'       => 1,
            'size'    => '1792x1024',
            'quality' => 'standard',
        );

        $response = wp_remote_post( self::API_URL, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( $payload ),
        ) );

        if ( is_wp_error( $response ) ) {
            BGAI_Logger::log( 'error', 'DALL-E error: ' . $response->get_error_message() );
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $body['data'][0]['url'] ) ) {
            $err = $body['error']['message'] ?? 'HTTP ' . $code;
            BGAI_Logger::log( 'warning', 'DALL-E failed: ' . $err . ' — trying Unsplash fallback.' );
            return self::unsplash_fallback( $title );
        }

        $img = wp_remote_get( $body['data'][0]['url'], array( 'timeout' => 30 ) );
        if ( is_wp_error( $img ) ) return self::unsplash_fallback( $title );

        BGAI_Logger::log( 'info', 'Featured image generated via DALL-E 3.' );
        return self::upload( wp_remote_retrieve_body( $img ), $title );
    }

    private static function unsplash_fallback( $title ) {
        $q   = urlencode( sanitize_title( $title ) );
        $res = wp_remote_get( 'https://source.unsplash.com/1600x900/?' . $q, array( 'timeout' => 20 ) );
        if ( is_wp_error( $res ) ) return null;
        $data = wp_remote_retrieve_body( $res );
        if ( empty( $data ) ) return null;
        BGAI_Logger::log( 'info', 'Featured image from Unsplash fallback.' );
        return self::upload( $data, $title );
    }

    private static function upload( $image_data, $title ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $filename = sanitize_title( $title ) . '-' . time() . '.jpg';
        $upload   = wp_upload_bits( $filename, null, $image_data );

        if ( ! empty( $upload['error'] ) ) {
            BGAI_Logger::log( 'error', 'Image upload error: ' . $upload['error'] );
            return null;
        }

        $id = wp_insert_attachment( array(
            'post_mime_type' => 'image/jpeg',
            'post_title'     => sanitize_text_field( $title ),
            'post_status'    => 'inherit',
        ), $upload['file'] );

        wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
        update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );

        BGAI_Logger::log( 'info', 'Featured image uploaded. ID: ' . $id );
        return $id;
    }
}
