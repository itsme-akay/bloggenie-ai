<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BGAI_SEO_Builder {

    public static function write_yoast( $post_id, $content ) {
        $fields = array(
            '_yoast_wpseo_title'                  => $content['meta_title']       ?? '',
            '_yoast_wpseo_metadesc'               => $content['meta_description'] ?? '',
            '_yoast_wpseo_focuskw'                => $content['focus_keyword']    ?? '',
            '_yoast_wpseo_opengraph-title'        => $content['meta_title']       ?? '',
            '_yoast_wpseo_opengraph-description'  => $content['meta_description'] ?? '',
            '_yoast_wpseo_twitter-title'          => $content['meta_title']       ?? '',
            '_yoast_wpseo_twitter-description'    => $content['meta_description'] ?? '',
        );
        foreach ( $fields as $key => $val ) {
            if ( ! empty( $val ) ) update_post_meta( $post_id, $key, sanitize_text_field( $val ) );
        }
    }

    public static function write_schema( $post_id, $content ) {
        $schema = array(
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => sanitize_text_field( $content['title'] ?? '' ),
            'description'   => sanitize_text_field( $content['meta_description'] ?? '' ),
            'datePublished' => get_the_date( 'c', $post_id ),
            'dateModified'  => get_the_modified_date( 'c', $post_id ),
            'url'           => get_permalink( $post_id ),
            'publisher'     => array(
                '@type' => 'Organization',
                'name'  => get_bloginfo( 'name' ),
                'url'   => get_site_url(),
            ),
        );
        update_post_meta( $post_id, '_bgai_schema_json', wp_json_encode( $schema ) );

        add_action( 'wp_head', function() use ( $post_id ) {
            if ( is_single() && get_the_ID() === $post_id ) {
                $s = get_post_meta( $post_id, '_bgai_schema_json', true );
                if ( $s ) echo '<script type="application/ld+json">' . $s . '</script>' . "\n";
            }
        });
    }
}
