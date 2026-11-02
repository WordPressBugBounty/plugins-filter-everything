<?php

namespace FilterEverything\Filter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Woo Discount Rules integration.
 *
 * With that plugin active, the Price filter puts products into the price ranges
 * by their discounted price and the «On sale» filter (PRO) treats products that
 * a rule discounts as being on sale. The only way to learn a discounted price
 * is to ask Woo Discount Rules for every product object, which costs seconds on
 * a catalog of a few thousand products — so the answers are cached here.
 *
 * The cache is a map product_id => [ signature, discounted price ] kept in a
 * transient per rule state and visitor kind. A product is recalculated only
 * when its signature (post_modified + its _price rows) changes, a change of the
 * active rules (edit, enable, schedule start/end) gives a new key, and the whole
 * map expires after a week. A stock update made by an order does not touch the
 * signature, so a busy shop pays the full calculation only after a rule or
 * price change — not on every page view as before 1.9.8.
 *
 * Rules can target user roles, logged-in visitors or a list of customers, so a
 * logged-in visitor gets a map of their own role set (and of their own user id
 * when a rule names specific customers). Guests share one map.
 */
class WooDiscountRules
{
    const TRANSIENT_PREFIX = 'wpc_wdr_prices_';

    /** Products hydrated per batch while calculating — keeps memory flat on large catalogs */
    const CHUNK_SIZE = 200;

    private static $enabled    = null;
    private static $rules_hash = null;
    private static $calculator = null;

    /** cache key => map, so the two filters of one page share a map without a second transient read */
    private static $maps = [];

    /**
     * The Woo Discount Rules plugin (v2 core) is active.
     *
     * @return bool
     */
    public static function isPluginActive()
    {
        return flrt_is_woocommerce()
            && class_exists( 'Wdr\App\Helpers\Woocommerce' )
            && class_exists( 'Wdr\App\Controllers\Base' )
            && class_exists( 'Wdr\App\Helpers\Rule' )
            && class_exists( 'Wdr\App\Controllers\ManageDiscount' )
            && class_exists( 'Wdr\App\Controllers\DiscountCalculator' )
            && class_exists( 'Wdr\App\Models\DBTable' );
    }

    /**
     * The integration is on: the plugin is active, the «Woo Discount Rules»
     * option is not turned off, nothing disabled it via the filter and at least
     * one active rule is of a type that changes a product price.
     *
     * @return bool
     */
    public static function isEnabled()
    {
        if ( null !== self::$enabled ) {
            return self::$enabled;
        }

        self::$enabled = false;

        if ( ! apply_filters( 'flrt_woo_discount_rules_enabled', true ) ) {
            return false;
        }

        // On by default: a missing option means the site owner never turned it off
        if ( 'off' === flrt_get_option( 'woo_discount_rules' ) ) {
            return false;
        }

        if ( ! self::isPluginActive() ) {
            return false;
        }

        $types = [];
        foreach ( self::getRules() as $rule ) {
            if ( isset( $rule->discount_type ) ) {
                $types[] = $rule->discount_type;
            }
        }

        $price_rule_types = [ 'wdr_simple_discount', 'wdr_bulk_discount', 'wdr_set_discount' ];
        self::$enabled    = ! empty( array_intersect( $price_rule_types, $types ) );

        return self::$enabled;
    }

    /**
     * Discounted prices of the given products.
     *
     * @param int[] $product_ids
     * @return array product_id => float — only the products that a rule discounts
     */
    public static function getDiscountedPrices( array $product_ids )
    {
        if ( empty( $product_ids ) || ! self::isEnabled() ) {
            return [];
        }

        $product_ids = array_values( array_unique( array_map( 'intval', $product_ids ) ) );
        $key         = self::cacheKey();

        if ( isset( self::$maps[ $key ] ) ) {
            $map = self::$maps[ $key ];
        } else {
            $map = flrt_get_transient( $key );
            if ( ! is_array( $map ) ) {
                $map = [];
            }
        }

        $signatures = self::productSignatures( $product_ids );
        $stale      = [];

        foreach ( $product_ids as $id ) {
            $signature = isset( $signatures[ $id ] ) ? $signatures[ $id ] : '';
            if ( ! isset( $map[ $id ]['s'] ) || $map[ $id ]['s'] !== $signature ) {
                $stale[ $id ] = $signature;
            }
        }

        if ( ! empty( $stale ) ) {
            $prices = self::calculate( array_keys( $stale ) );
            foreach ( $stale as $id => $signature ) {
                $map[ $id ] = [
                    's' => $signature,
                    'p' => isset( $prices[ $id ] ) ? $prices[ $id ] : null,
                ];
            }
            flrt_set_transient( $key, $map, self::cacheLifetime() );
        }

        self::$maps[ $key ] = $map;

        $result = [];
        foreach ( $product_ids as $id ) {
            if ( isset( $map[ $id ]['p'] ) ) {
                $result[ $id ] = (float) $map[ $id ]['p'];
            }
        }

        return $result;
    }

    /**
     * How long a price map lives without being touched.
     *
     * @return int seconds
     */
    public static function cacheLifetime()
    {
        return (int) apply_filters( 'wpc_woo_discount_rules_cache_lifetime', WEEK_IN_SECONDS );
    }

    /**
     * Forgets every cached price map — the «Reset cache» link.
     */
    public static function resetCache()
    {
        global $wpdb;

        self::$maps = [];

        $like  = $wpdb->esc_like( '_transient_' . self::TRANSIENT_PREFIX ) . '%';
        $names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $like ) );

        foreach ( (array) $names as $name ) {
            delete_transient( str_replace( '_transient_', '', $name ) );
        }

        // With an external object cache the transients are not in the options
        // table — delete at least the map of the current visitor
        if ( self::isPluginActive() ) {
            delete_transient( self::cacheKey() );
        }
    }

    /**
     * Asks Woo Discount Rules for the discounted price of every product.
     *
     * @param int[] $ids
     * @return array product_id => float for the products that a rule discounts
     */
    private static function calculate( array $ids )
    {
        $prices     = [];
        $calculator = self::calculator();

        if ( ! $calculator ) {
            return $prices;
        }

        foreach ( array_chunk( $ids, self::CHUNK_SIZE ) as $chunk ) {
            // One query each for the posts, their meta and their terms instead
            // of a query storm while the product objects are built
            _prime_post_caches( $chunk, true, true );

            foreach ( $chunk as $id ) {
                $product = wc_get_product( $id );
                if ( ! $product ) {
                    continue;
                }

                $sale = $calculator->getProductPriceToDisplay( $product );
                if ( ! empty( $sale ) && isset( $sale['discounted_price'] ) ) {
                    $prices[ $id ] = (float) $sale['discounted_price'];
                }
            }
        }

        return $prices;
    }

    /**
     * What a cached price is valid for: the product's last modification and
     * its _price rows (a variable product keeps one row per distinct child
     * price). Both change when the product or a variation is edited; neither
     * changes when an order only updates the stock.
     *
     * @param int[] $ids
     * @return array product_id => string
     */
    private static function productSignatures( array $ids )
    {
        global $wpdb;

        $signatures = [];

        foreach ( array_chunk( $ids, 2000 ) as $chunk ) {
            $in   = implode( ',', array_map( 'intval', $chunk ) );
            $rows = $wpdb->get_results(
                "SELECT p.ID, p.post_modified_gmt, GROUP_CONCAT( pm.meta_value ORDER BY pm.meta_value SEPARATOR ',' ) AS prices
                FROM {$wpdb->posts} AS p
                LEFT JOIN {$wpdb->postmeta} AS pm ON ( pm.post_id = p.ID AND pm.meta_key = '_price' )
                WHERE p.ID IN ( {$in} )
                GROUP BY p.ID, p.post_modified_gmt",
                ARRAY_A
            );

            if ( ! is_array( $rows ) ) {
                continue;
            }

            foreach ( $rows as $row ) {
                $signatures[ (int) $row['ID'] ] = $row['post_modified_gmt'] . '|' . $row['prices'];
            }
        }

        return $signatures;
    }

    /**
     * The transient name of the current visitor's map under the current rules.
     *
     * @return string
     */
    private static function cacheKey()
    {
        return self::TRANSIENT_PREFIX . md5( self::rulesHash() . '|' . self::visitorSignature() );
    }

    /**
     * Fingerprint of the rules that apply right now. Woo Discount Rules returns
     * only the enabled rules inside their schedule, so a rule being switched on
     * or off, edited, starting or ending changes the hash. The usage counters
     * are left out — they change with every order.
     *
     * @return string
     */
    private static function rulesHash()
    {
        if ( null !== self::$rules_hash ) {
            return self::$rules_hash;
        }

        $rows = [];
        foreach ( self::getRules() as $rule ) {
            $row = (array) $rule;
            unset( $row['used_limits'], $row['used_coupons'], $row['created_by'], $row['created_on'], $row['modified_by'] );
            $rows[] = $row;
        }

        self::$rules_hash = md5( serialize( $rows ) );

        return self::$rules_hash;
    }

    /**
     * Whom the prices are calculated for. Rules can be limited to user roles,
     * to logged-in visitors or to a list of customers.
     *
     * @return string
     */
    private static function visitorSignature()
    {
        if ( ! is_user_logged_in() ) {
            return 'guest';
        }

        $user  = wp_get_current_user();
        $roles = (array) $user->roles;
        sort( $roles );

        $signature = 'roles:' . implode( ',', $roles );

        foreach ( self::getRules() as $rule ) {
            $targets = ( isset( $rule->filters ) ? $rule->filters : '' ) . ( isset( $rule->conditions ) ? $rule->conditions : '' );
            if ( false !== strpos( $targets, 'user_list' ) ) {
                $signature .= ';user:' . $user->ID;
                break;
            }
        }

        return $signature;
    }

    /**
     * @return object[] the rules Woo Discount Rules applies in this request
     */
    private static function getRules()
    {
        if ( ! class_exists( 'Wdr\App\Models\DBTable' ) ) {
            return [];
        }

        $rules = \Wdr\App\Models\DBTable::getRules();

        return is_array( $rules ) ? $rules : [];
    }

    /**
     * @return \FlrtWooDiscountRules|false
     */
    private static function calculator()
    {
        if ( null === self::$calculator ) {
            self::$calculator = function_exists( 'flrt_woo_discount_rules_class' ) ? flrt_woo_discount_rules_class() : false;
        }

        return self::$calculator;
    }
}
