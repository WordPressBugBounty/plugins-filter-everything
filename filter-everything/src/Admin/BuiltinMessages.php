<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Messages that ship INSIDE a release — for admins who declined «Plugin
 * notifications» (or have not answered yet) and therefore never download the
 * feed. Same schema, same validation (MessageFeed::validate()), same display
 * rules (Messages: plugin screens only, per-user dismiss, cooldowns, the
 * quiet day after an update, `ends` mandatory) — the only difference is that
 * nothing is requested from anywhere.
 *
 * A feed entry with the same `id` overrides the built-in copy on sites that
 * allowed notifications: that is how a date, a price or the text is changed
 * remotely, and an `ends` in the past pulls the message back for them. Sites
 * that declined keep the built-in copy until its own `ends` — choose that date
 * deliberately; it cannot be moved without another release.
 *
 * Housekeeping: remove an entry with the next release after it has ended.
 * Keep the list short — one or two entries at most.
 */
class BuiltinMessages
{
    /** @var array|null validated entries, keyed by id (resolved once per request) */
    private static $validated = null;

    /**
     * Raw entries in feed schema. UTC timestamps, like messages.json.
     */
    public static function raw()
    {
        return array(
            // Autumn sale 2026, extended to 2026-09-30 23:59:59 Europe/Kyiv.
            array(
                'id'        => '2026-09-autumn-sale',
                'type'      => 'promo',
                'layout'    => 'bar',
                'theme'     => 'sale',
                'countdown' => true,
                'ends'      => '2026-09-30T20:59:59Z',
                'audience'  => array( 'build' => array( 'free' ) ),
                'content'   => array(
                    'en' => array(
                        'badge' => 'Autumn Sale',
                        'text'  => 'Up to <span class="wpc-msg__pill">29%</span> off any PRO license — ends <strong>September 30</strong>',
                        'cta'   => array(
                            'label' => 'See prices',
                            'url'   => 'https://filtereverything.pro/pricing/?utm_source=free_plugin&utm_medium=internal&utm_campaign=autumn_sale_2026',
                        ),
                    ),
                    'uk' => array(
                        'badge' => 'Осінній розпродаж',
                        'text'  => 'Знижка до <span class="wpc-msg__pill">29%</span> на будь-яку PRO-ліцензію — до <strong>30 вересня</strong>',
                        'cta'   => array(
                            'label' => 'Дивитись ціни',
                            'url'   => 'https://filtereverything.pro/pricing/?utm_source=free_plugin&utm_medium=internal&utm_campaign=autumn_sale_2026',
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Built-in `pricing` block for the «Upgrade to PRO» popup (same schema as
     * the feed's top-level `pricing`; MessageFeed::validatePricing()). Used only
     * while the feed has no pricing block of its own — a feed block, even an
     * expired one, wins on sites that allowed notifications. Null = none.
     */
    public static function rawPricing()
    {
        return array(
            'price' => '$49',
            'was'   => '$69',
            'off'   => '−29%',
            'ends'  => '2026-09-30T20:59:59Z',
        );
    }

    /** @return array|null */
    public static function pricing()
    {
        $raw = self::rawPricing();

        return $raw ? MessageFeed::validatePricing( $raw ) : null;
    }

    /**
     * Entries after the feed's validation, keyed by id and flagged `builtin`
     * so that Messages can tell them from feed messages (no badge for them).
     *
     * @return array
     */
    public static function validated()
    {
        if ( null === self::$validated ) {
            self::$validated = array();
            foreach ( MessageFeed::validate( self::raw() ) as $id => $m ) {
                $m['builtin']           = true;
                self::$validated[ $id ] = $m;
            }
        }

        return self::$validated;
    }
}
