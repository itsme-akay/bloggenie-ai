<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BGAI_Internal_Linker {

    const API_URL = 'https://api.openai.com/v1/chat/completions';

    public static function inject( $api_key, $body ) {
        $posts = self::get_posts();
        if ( empty( $posts ) ) return $body;

        $list = '';
        foreach ( $posts as $p ) {
            $list .= '- "' . $p['title'] . '" : ' . $p['url'] . "\n";
        }

        $prompt = "You are an SEO expert. Given the article HTML and a list of existing posts, find 3-5 places to naturally insert internal links.\n\n"
            . "Return ONLY a JSON array: [{\"anchor\": \"exact text from article\", \"url\": \"https://...\"}, ...]\n"
            . "Only use anchor text that exists word-for-word in the article.\n\n"
            . "Existing posts:\n" . $list . "\n\nArticle HTML:\n" . $body;

        $response = wp_remote_post( self::API_URL, array(
            'timeout' => 45,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'      => 'gpt-4o',
                'max_tokens' => 600,
                'messages'   => array(
                    array( 'role' => 'system', 'content' => 'Return only valid JSON arrays.' ),
                    array( 'role' => 'user',   'content' => $prompt ),
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) return $body;

        $data  = json_decode( wp_remote_retrieve_body( $response ), true );
        $raw   = $data['choices'][0]['message']['content'] ?? '';
        $raw   = preg_replace( '/^```json\s*/i', '', trim( $raw ) );
        $raw   = preg_replace( '/\s*```$/', '', $raw );
        $links = json_decode( trim( $raw ), true );

        if ( ! is_array( $links ) ) return $body;

        $count = 0;
        foreach ( $links as $link ) {
            if ( empty( $link['anchor'] ) || empty( $link['url'] ) ) continue;
            $anchor   = esc_html( $link['anchor'] );
            $tag      = '<a href="' . esc_url( $link['url'] ) . '">' . $anchor . '</a>';
            $new_body = str_replace( $anchor, $tag, $body, $n );
            if ( $n > 0 ) { $body = $new_body; $count++; }
        }

        BGAI_Logger::log( 'info', 'Internal links injected: ' . $count );
        return $body;
    }

    private static function get_posts() {
        $result = array();
        foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 100 ) ) as $p ) {
            $result[] = array( 'title' => get_the_title( $p ), 'url' => get_permalink( $p ) );
        }
        return $result;
    }
}
