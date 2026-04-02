<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BGAI_Content_Generator {

    const API_URL = 'https://api.openai.com/v1/chat/completions';

    public static function generate( $api_key, $topic ) {
        $tone    = get_option( 'bgai_tone', 'professional and informative' );
        $add_faq = get_option( 'bgai_enable_faq', '1' ) === '1';
        $year    = date( 'Y' );

        $faq = $add_faq
            ? 'Include a FAQ section at the end with 5 questions and answers.'
            : '';

        $prompt = <<<PROMPT
You are an expert digital marketing and SEO content writer.

Write a complete, 100% unique, human-written style blog post on: "{$topic}"

Requirements:
- Tone: {$tone}
- Length: exactly 1500 words
- Structure: H2 and H3 subheadings, strong intro, detailed body, conclusion
- Year context: {$year}
- Do NOT open with "In today's digital landscape" or similar clichés
- {$faq}

Return ONLY a valid JSON object with these exact keys — no markdown, no code fences:
{
  "title": "SEO post title under 60 chars",
  "meta_title": "Meta title under 60 chars",
  "meta_description": "Meta description under 155 chars",
  "focus_keyword": "main focus keyword",
  "slug": "hyphenated-url-slug",
  "tags": ["tag1","tag2","tag3","tag4","tag5"],
  "body": "Full HTML using <h2><h3><p><ul><li> — no html/body wrapper"
}
PROMPT;

        $payload = array(
            'model'       => 'gpt-4o',
            'temperature' => 0.7,
            'max_tokens'  => 3500,
            'messages'    => array(
                array( 'role' => 'system', 'content' => 'You are a professional SEO content writer. Always return valid JSON only. No markdown fences.' ),
                array( 'role' => 'user',   'content' => $prompt ),
            ),
        );

        $response = wp_remote_post( self::API_URL, array(
            'timeout' => 90,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( $payload ),
        ) );

        if ( is_wp_error( $response ) ) {
            BGAI_Logger::log( 'error', 'OpenAI API error: ' . $response->get_error_message() );
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $err = $data['error']['message'] ?? 'HTTP ' . $code;
            BGAI_Logger::log( 'error', 'OpenAI error: ' . $err );
            return false;
        }

        $raw = $data['choices'][0]['message']['content'] ?? '';
        $raw = preg_replace( '/^```json\s*/i', '', trim( $raw ) );
        $raw = preg_replace( '/\s*```$/', '', $raw );
        $content = json_decode( trim( $raw ), true );

        if ( ! is_array( $content ) || empty( $content['title'] ) || empty( $content['body'] ) ) {
            BGAI_Logger::log( 'error', 'Failed to parse OpenAI JSON response.' );
            return false;
        }

        $tokens = $data['usage']['total_tokens'] ?? 0;
        BGAI_Logger::log( 'info', 'Content generated — ' . $tokens . ' tokens used.' );

        return $content;
    }
}
