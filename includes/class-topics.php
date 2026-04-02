<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BGAI_Topics {

    const TRENDS_URL = 'https://trends.google.com/trends/trendingsearches/daily/rss?geo=IN';

    public static function get_topic( $api_key, $keywords ) {
        $blacklist = self::get_blacklist();

        // Step 1: try Google Trends RSS
        $trends = self::fetch_trends();
        if ( ! empty( $trends ) ) {
            $match = self::match_keywords( $trends, $keywords, $blacklist );
            if ( $match ) {
                BGAI_Logger::log( 'info', 'Topic from Google Trends: ' . $match );
                return $match;
            }
        }

        // Step 2: ChatGPT generates 10 fresh topic ideas
        BGAI_Logger::log( 'info', 'Google Trends unavailable — asking ChatGPT to generate topic ideas.' );
        $topic = self::chatgpt_topics( $api_key, $keywords, $blacklist );
        if ( $topic ) {
            BGAI_Logger::log( 'info', 'Topic from ChatGPT generator: ' . $topic );
            return $topic;
        }

        // Step 3: plain keyword fallback
        $filtered = self::filter_blacklist( $keywords, $blacklist );
        if ( ! empty( $filtered ) ) {
            $kw    = $filtered[ array_rand( $filtered ) ];
            $topic = $kw . ' guide ' . date( 'Y' );
            BGAI_Logger::log( 'info', 'Topic from keyword fallback: ' . $topic );
            return $topic;
        }

        return false;
    }

    private static function fetch_trends() {
        $cached = get_transient( 'bgai_trends_cache' );
        if ( $cached ) return $cached;

        $response = wp_remote_get( self::TRENDS_URL, array(
            'timeout'    => 10,
            'user-agent' => 'Mozilla/5.0',
        ) );

        if ( is_wp_error( $response ) ) return array();

        $body   = wp_remote_retrieve_body( $response );
        $topics = array();

        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        if ( $xml ) {
            foreach ( $xml->channel->item as $item ) {
                $title = (string) $item->title;
                if ( ! empty( $title ) ) $topics[] = $title;
                if ( count( $topics ) >= 20 ) break;
            }
        }

        if ( ! empty( $topics ) ) {
            set_transient( 'bgai_trends_cache', $topics, 3 * HOUR_IN_SECONDS );
        }

        return $topics;
    }

    private static function match_keywords( $trends, $keywords, $blacklist ) {
        $kws_lower = array_map( 'strtolower', $keywords );
        foreach ( $trends as $trend ) {
            if ( self::is_blacklisted( $trend, $blacklist ) ) continue;
            $trend_lower = strtolower( $trend );
            foreach ( $kws_lower as $kw ) {
                foreach ( explode( ' ', $kw ) as $word ) {
                    if ( strlen( $word ) > 3 && strpos( $trend_lower, $word ) !== false ) {
                        return $trend;
                    }
                }
            }
        }
        return false;
    }

    private static function chatgpt_topics( $api_key, $keywords, $blacklist ) {
        $kw_str     = implode( ', ', array_slice( $keywords, 0, 20 ) );
        $bl_str     = ! empty( $blacklist ) ? 'Never suggest topics related to: ' . implode( ', ', $blacklist ) . '.' : '';
        $year       = date( 'Y' );

        $prompt = "You are an SEO content strategist. Generate exactly 10 trending blog post topic ideas for a Digital Marketing and SEO blog.\n\n"
            . "Base the topics on these seed keywords: {$kw_str}\n"
            . "Topics must be relevant to digital marketing, SEO, content marketing, or related technology.\n"
            . "{$bl_str}\n"
            . "Each topic should be timely for {$year} and have good search potential.\n\n"
            . "Return ONLY a JSON array of 10 topic strings. Example: [\"topic one\", \"topic two\", ...]\n"
            . "No explanation. No markdown. Just the JSON array.";

        $payload = array(
            'model'       => 'gpt-4o',
            'temperature' => 0.8,
            'max_tokens'  => 500,
            'messages'    => array(
                array( 'role' => 'system', 'content' => 'You are an SEO strategist. Return only valid JSON arrays.' ),
                array( 'role' => 'user',   'content' => $prompt ),
            ),
        );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( $payload ),
        ) );

        if ( is_wp_error( $response ) ) return false;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $raw  = $data['choices'][0]['message']['content'] ?? '';
        $raw  = preg_replace( '/^```json\s*/i', '', trim( $raw ) );
        $raw  = preg_replace( '/\s*```$/', '', $raw );
        $list = json_decode( trim( $raw ), true );

        if ( ! is_array( $list ) || empty( $list ) ) return false;

        // Filter blacklist and pick first valid one
        foreach ( $list as $t ) {
            if ( ! empty( $t ) && ! self::is_blacklisted( $t, $blacklist ) ) {
                return sanitize_text_field( $t );
            }
        }

        return false;
    }

    private static function get_blacklist() {
        $raw = get_option( 'bgai_blacklist', '' );
        if ( empty( $raw ) ) return array();
        return array_filter( array_map( 'trim', explode( ',', strtolower( $raw ) ) ) );
    }

    private static function is_blacklisted( $topic, $blacklist ) {
        if ( empty( $blacklist ) ) return false;
        $lower = strtolower( $topic );
        foreach ( $blacklist as $bl ) {
            if ( ! empty( $bl ) && strpos( $lower, $bl ) !== false ) return true;
        }
        return false;
    }

    private static function filter_blacklist( $keywords, $blacklist ) {
        if ( empty( $blacklist ) ) return $keywords;
        return array_filter( $keywords, function( $kw ) use ( $blacklist ) {
            return ! self::is_blacklisted( $kw, $blacklist );
        });
    }
}
