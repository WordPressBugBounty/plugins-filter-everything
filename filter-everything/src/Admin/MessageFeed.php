<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * «Plugin notifications» feed — the transport half of the message channel (roadmap 2.19).
 *
 * One static JSON file on filtereverything.pro lists every message that is
 * currently live, together with the audience each one is meant for. The plugin
 * downloads the file and decides LOCALLY which message, if any, applies to this
 * site (see Messages). Consequences that matter:
 *
 *   - the request carries nothing about the site: no parameters, no cookies and
 *     a fixed User-Agent (WordPress' default one contains the site URL — that is
 *     why it is overridden here);
 *   - it only ever happens in WP-Cron, once a day, and only while the channel
 *     is enabled (opt-in in the free build) — never on a frontend or admin page
 *     load, so a slow or dead server is invisible to the site;
 *   - the file is data, never code: everything is validated here, length-capped,
 *     and text is sanitised again at render time (Messages::html()).
 *
 * A failed or invalid fetch changes nothing: the previous copy stays, and its
 * entries still expire on their own `ends` dates.
 */
class MessageFeed
{
    const URL          = 'https://filtereverything.pro/plugin-feed/messages.json';
    const CACHE_OPTION = 'flrt_messages_cache';
    const CRON_HOOK    = 'flrt_messages_fetch';
    const MAX_BYTES    = 32768;
    const SCHEMA       = 1;

    const TYPES   = array( 'critical', 'important', 'promo', 'info', 'tip' );
    const THEMES  = array( 'neutral', 'sale', 'brand', 'dark', 'danger' );
    /** Audience keys this version understands. A message using any other key is skipped. */
    const AUDIENCE_KEYS = array( 'build', 'license', 'min_ver', 'max_ver', 'woo', 'locale' );

    /** Hosts a message may link to (subdomains included). */
    const LINK_HOSTS = array( 'filtereverything.pro', 'wordpress.org' );

    public static function url()
    {
        $url = defined( 'FLRT_MESSAGES_URL' ) ? FLRT_MESSAGES_URL : self::URL;

        return (string) apply_filters( 'wpc_messages_feed_url', $url );
    }

    public static function schedule()
    {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            // First run right away, so a fresh opt-in does not wait a day.
            wp_schedule_event( time() + 30, 'daily', self::CRON_HOOK );
        }
    }

    public static function unschedule()
    {
        $ts = wp_next_scheduled( self::CRON_HOOK );
        while ( $ts ) {
            wp_unschedule_event( $ts, self::CRON_HOOK );
            $ts = wp_next_scheduled( self::CRON_HOOK );
        }
    }

    /**
     * @return array{fetched:int, messages:array, pricing:array|null}
     */
    public static function cached()
    {
        $cache = get_option( self::CACHE_OPTION );

        if ( ! is_array( $cache ) || ! isset( $cache['messages'] ) || ! is_array( $cache['messages'] ) ) {
            return array( 'fetched' => 0, 'messages' => array(), 'pricing' => null );
        }
        if ( ! isset( $cache['pricing'] ) || ! is_array( $cache['pricing'] ) ) {
            $cache['pricing'] = null;
        }

        return $cache;
    }

    /**
     * Downloads, validates and stores the feed.
     *
     * @return true|\WP_Error
     */
    public static function fetch()
    {
        $response = wp_remote_get( self::url(), array(
            'timeout'             => 5,
            'redirection'         => 2,
            'user-agent'          => 'FilterEverything',
            'cookies'             => array(),
            'headers'             => array( 'Accept' => 'application/json' ),
            'limit_response_size' => self::MAX_BYTES + 1,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }
        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return new \WP_Error( 'flrt_feed_http', 'Unexpected response code' );
        }

        $body = (string) wp_remote_retrieve_body( $response );
        if ( strlen( $body ) > self::MAX_BYTES ) {
            return new \WP_Error( 'flrt_feed_size', 'Feed is too large' );
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) || ! isset( $data['schema'], $data['messages'] ) || ! is_array( $data['messages'] ) ) {
            return new \WP_Error( 'flrt_feed_format', 'Feed is not valid' );
        }
        if ( (int) $data['schema'] !== self::SCHEMA ) {
            // A future format this version cannot read: keep quiet rather than guess.
            return new \WP_Error( 'flrt_feed_schema', 'Unknown feed schema' );
        }

        update_option( self::CACHE_OPTION, array(
            'fetched'  => time(),
            'messages' => self::validate( $data['messages'] ),
            'pricing'  => isset( $data['pricing'] ) && is_array( $data['pricing'] ) ? self::validatePricing( $data['pricing'] ) : null,
        ), false );

        return true;
    }

    /**
     * Optional top-level `pricing` block: the PRO price the free build's
     * «Upgrade to PRO» popup shows in place of the built-in FLRT_PRO_PRICE
     * while the block is in force. With `was` the popup draws a classic
     * discount (old price struck out, new price emphasised); without it the
     * price is simply replaced. `ends` is mandatory, like for messages.
     *
     *   "pricing": {"price": "$49", "was": "$69", "off": "−29%", "starts": "…", "ends": "…"}
     *
     * @return array{price:string, was:string, off:string, starts:int, ends:int}|null
     */
    public static function validatePricing( array $p )
    {
        $ends   = isset( $p['ends'] ) ? strtotime( (string) $p['ends'] ) : false;
        $starts = isset( $p['starts'] ) ? strtotime( (string) $p['starts'] ) : 0;
        if ( ! $ends || false === $starts ) {
            return null;
        }

        $money = '/^[\p{Sc}\p{Lu}0-9.,\s-]{1,12}$/u'; // "$49", "€45", "49 USD"
        $price = isset( $p['price'] ) ? trim( (string) $p['price'] ) : '';
        $was   = isset( $p['was'] ) ? trim( (string) $p['was'] ) : '';
        $off   = isset( $p['off'] ) ? trim( (string) $p['off'] ) : '';

        if ( ! preg_match( $money, $price ) || ( '' !== $was && ! preg_match( $money, $was ) ) ) {
            return null;
        }
        if ( '' !== $off && ! preg_match( '/^[\p{L}\p{N}%+\x{2212}\s-]{1,8}$/u', $off ) ) {
            $off = '';
        }

        return array(
            'price'  => $price,
            'was'    => $was,
            'off'    => $off,
            'starts' => (int) $starts,
            'ends'   => (int) $ends,
        );
    }

    /**
     * Keeps only well-formed messages and normalises them. Never trusts a field:
     * unknown audience keys, missing dates, foreign link hosts or oversized text
     * drop the whole message.
     */
    public static function validate( array $messages )
    {
        $out = array();

        foreach ( array_slice( $messages, 0, 30 ) as $m ) {
            if ( ! is_array( $m ) ) {
                continue;
            }

            $id   = isset( $m['id'] ) ? (string) $m['id'] : '';
            $type = isset( $m['type'] ) ? (string) $m['type'] : '';
            if ( ! preg_match( '/^[a-z0-9][a-z0-9_-]{2,63}$/', $id ) || ! in_array( $type, self::TYPES, true ) ) {
                continue;
            }

            $ends   = isset( $m['ends'] ) ? strtotime( (string) $m['ends'] ) : false;
            $starts = isset( $m['starts'] ) ? strtotime( (string) $m['starts'] ) : 0;
            if ( ! $ends || false === $starts ) {
                continue; // an end date is mandatory: nothing may live forever
            }

            $audience = isset( $m['audience'] ) && is_array( $m['audience'] ) ? $m['audience'] : array();
            if ( array_diff( array_keys( $audience ), self::AUDIENCE_KEYS ) ) {
                continue; // written for a newer version of the plugin
            }

            $content = array();
            foreach ( ( isset( $m['content'] ) && is_array( $m['content'] ) ? $m['content'] : array() ) as $lang => $c ) {
                $clean = is_array( $c ) ? self::content( $c ) : false;
                if ( $clean && preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', (string) $lang ) ) {
                    $content[ $lang ] = $clean;
                }
            }
            if ( empty( $content['en'] ) ) {
                continue;
            }

            $theme = isset( $m['theme'] ) ? (string) $m['theme'] : '';

            $out[ $id ] = array(
                'id'        => $id,
                'type'      => $type,
                'layout'    => isset( $m['layout'] ) ? preg_replace( '/[^a-z-]/', '', (string) $m['layout'] ) : 'bar',
                'theme'     => in_array( $theme, self::THEMES, true ) ? $theme : '',
                'starts'    => (int) $starts,
                'ends'      => (int) $ends,
                'priority'  => isset( $m['priority'] ) ? (int) $m['priority'] : 0,
                'countdown' => ! empty( $m['countdown'] ),
                'audience'  => $audience,
                'content'   => $content,
            );
        }

        return $out;
    }

    /**
     * @return array|false
     */
    private static function content( array $c )
    {
        $out = array();

        foreach ( array( 'badge' => 40, 'label' => 40, 'title' => 120, 'text' => 1500 ) as $key => $max ) {
            if ( isset( $c[ $key ] ) && is_string( $c[ $key ] ) && '' !== trim( $c[ $key ] ) ) {
                if ( strlen( $c[ $key ] ) > $max * 4 || mb_strlen( wp_strip_all_tags( $c[ $key ] ) ) > $max ) {
                    return false;
                }
                $out[ $key ] = $c[ $key ];
            }
        }
        if ( empty( $out['text'] ) && empty( $out['title'] ) ) {
            return false;
        }

        if ( isset( $c['list'] ) && is_array( $c['list'] ) ) {
            $out['list'] = array();
            foreach ( array_slice( $c['list'], 0, 6 ) as $li ) {
                if ( is_string( $li ) && '' !== trim( $li ) && mb_strlen( $li ) <= 120 ) {
                    $out['list'][] = $li;
                }
            }
        }

        if ( isset( $c['price'] ) && is_array( $c['price'] ) ) {
            $out['price'] = array();
            foreach ( array( 'was', 'now', 'off', 'term' ) as $key ) {
                $v = isset( $c['price'][ $key ] ) && is_string( $c['price'][ $key ] ) ? $c['price'][ $key ] : '';
                $out['price'][ $key ] = mb_substr( $v, 0, 60 );
            }
        }

        if ( isset( $c['cta'] ) && is_array( $c['cta'] ) ) {
            $label = isset( $c['cta']['label'] ) && is_string( $c['cta']['label'] ) ? trim( $c['cta']['label'] ) : '';
            $url   = isset( $c['cta']['url'] ) && is_string( $c['cta']['url'] ) ? trim( $c['cta']['url'] ) : '';
            if ( '' === $label || mb_strlen( $label ) > 40 || false === self::resolveUrl( $url ) ) {
                return false; // a button that cannot be trusted disqualifies the message
            }
            $out['cta'] = array( 'label' => $label, 'url' => $url );
        }

        return $out;
    }

    /**
     * The only two kinds of links a message may carry:
     *   https://… on one of LINK_HOSTS (or a subdomain)  → external
     *   admin:plugins.php                                → this site's own admin
     *
     * @return array{url:string, external:bool}|false
     */
    public static function resolveUrl( $url )
    {
        $url = trim( (string) $url );

        if ( preg_match( '/^admin:([a-z0-9-]+\.php(\?[a-zA-Z0-9_=&%.-]*)?)$/', $url, $m ) ) {
            return array( 'url' => admin_url( $m[1] ), 'external' => false );
        }

        if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
            return false;
        }
        $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
        foreach ( self::LINK_HOSTS as $allowed ) {
            if ( $host === $allowed || substr( $host, -strlen( '.' . $allowed ) ) === '.' . $allowed ) {
                return array( 'url' => $url, 'external' => true );
            }
        }

        return false;
    }
}
