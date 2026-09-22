<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Review request (free build only).
 *
 * Asks an admin to rate the plugin on wordpress.org — as a dismissible notice
 * on every admin screen, not a modal on the plugin's own pages: people set
 * their filters up once and rarely come back to those pages, so the modal
 * reached almost nobody (1.9.4–1.9.6).
 *
 * WHEN — the ask follows proof that the plugin works for this site, not a
 * calendar:
 *   - at least one published Filter Set, published ≥ self::SET_AGE_MIN ago;
 *   - visitors have actually filtered: self::USAGE_MIN filtered page loads
 *     counted on the frontend (logged-in users with plugin capabilities are
 *     not counted — that is the admin testing). Low-traffic sites that never
 *     reach the count are asked once the first set is self::SET_AGE_MAX old;
 *   - not in the quiet day after an update or install (Messages::inQuietPeriod());
 *   - nothing else is asking: not on a request that draws a message or the
 *     consent question, and not within Messages::SPACING_DAYS after one —
 *     the review request yields to every other ask (and, in return, promos
 *     and tips wait as long after it; see Messages::pick());
 *   - the current admin has not dismissed it and is not snoozed.
 *
 * HOW OFTEN — self::MAX_SHOWS times, self::SNOOZE apart (every render
 * auto-snoozes: ignoring it equals «Maybe later»), then one final ask
 * self::FINAL_AFTER after the last one. «Don't show this again», the ✕ and
 * clicking through to the review end it for good. State is per admin user
 * (the ask is personal); the usage counter and set date are per site.
 */
class ReviewRequest
{
    const USER_META        = 'flrt_review_request';
    const USAGE_OPTION     = 'flrt_filter_usage';
    const FIRST_SET_OPTION = 'flrt_first_set_published_at';
    const NONCE_ACTION     = 'flrt_review_popup';

    const REVIEW_URL  = 'https://wordpress.org/support/plugin/filter-everything/reviews/#new-post';
    const SUPPORT_URL = 'https://wordpress.org/support/plugin/filter-everything/';

    const USAGE_MIN   = 20;
    const SET_AGE_MIN = 7 * DAY_IN_SECONDS;
    const SET_AGE_MAX = 30 * DAY_IN_SECONDS;
    const SNOOZE      = 21 * DAY_IN_SECONDS;
    const MAX_SHOWS   = 3;
    const FINAL_AFTER = 180 * DAY_IN_SECONDS;

    public function __construct()
    {
        // The review ask targets the wordpress.org listing — free build only.
        if ( defined('FLRT_FILTERS_PRO') ) {
            return;
        }

        if ( is_admin() ) {
            add_action( 'admin_notices', [ $this, 'maybeRender' ] );
            add_action( 'wp_ajax_flrt_review_popup', [ $this, 'ajaxDismiss' ] );
        } else {
            add_action( 'wp', [ $this, 'countUsage' ] );
        }
    }

    /* ---------------------------------------------------------------------
     * Proof of use (frontend)
     * ------------------------------------------------------------------- */

    /**
     * One filtered page load = one tick, until the threshold is reached — a
     * single autoloaded option read per request afterwards, no more writes.
     */
    public function countUsage()
    {
        if ( ! function_exists( 'flrt_is_filter_request' ) || ! flrt_is_filter_request() ) {
            return;
        }
        if ( is_user_logged_in() && current_user_can( flrt_plugin_user_caps() ) ) {
            return; // the site's own admin trying the filters
        }

        $usage = (int) get_option( self::USAGE_OPTION, 0 );
        if ( $usage >= self::USAGE_MIN ) {
            return;
        }

        update_option( self::USAGE_OPTION, $usage + 1 );
    }

    /**
     * Unix time the site's oldest published Filter Set was published, cached
     * once found; 0 while there is none.
     */
    public static function firstSetPublishedAt()
    {
        $cached = (int) get_option( self::FIRST_SET_OPTION, 0 );
        if ( $cached ) {
            return $cached;
        }

        $sets = get_posts( [
            'post_type'   => FLRT_FILTERS_SET_POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 1,
            'orderby'     => 'date',
            'order'       => 'ASC',
        ] );
        if ( empty( $sets ) ) {
            return 0;
        }

        $at = (int) get_post_time( 'U', true, $sets[0] );
        update_option( self::FIRST_SET_OPTION, $at );

        return $at;
    }

    /** The site-level half of shouldShow(): does the plugin demonstrably work here? */
    public static function siteQualifies( $now = 0 )
    {
        $now      = $now ? $now : time();
        $first_at = self::firstSetPublishedAt();
        if ( ! $first_at || $now - $first_at < self::SET_AGE_MIN ) {
            return false;
        }

        $usage = (int) get_option( self::USAGE_OPTION, 0 );

        return $usage >= self::USAGE_MIN || $now - $first_at >= self::SET_AGE_MAX;
    }

    /* ---------------------------------------------------------------------
     * The notice (admin)
     * ------------------------------------------------------------------- */

    public function maybeRender()
    {
        if ( ! $this->shouldShow() ) {
            return;
        }

        // Count the show and auto-snooze right away: ignoring the notice equals
        // «Maybe later», and reloads cannot burn through the budget.
        $state                  = $this->getState();
        $state['shows']         = (int) $state['shows'] + 1;
        $state['last_shown']    = time();
        $state['snoozed_until'] = time() + self::SNOOZE;
        $this->saveState( $state );

        flrt_include_admin_view( 'review-notice', [
            'review_nonce' => wp_create_nonce( self::NONCE_ACTION ),
        ] );
    }

    public function ajaxDismiss()
    {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! current_user_can( flrt_plugin_user_caps() ) ) {
            wp_send_json_error();
        }

        $mode  = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : '';
        $state = $this->getState();

        if ( $mode === 'later' ) {
            $state['snoozed_until'] = time() + self::SNOOZE;
        } elseif ( $mode === 'never' || $mode === 'rated' ) {
            $state['dismissed'] = true;
        } else {
            wp_send_json_error();
        }

        $this->saveState( $state );
        wp_send_json_success();
    }

    private function shouldShow()
    {
        if ( ! current_user_can( flrt_plugin_user_caps() ) ) {
            return false;
        }
        if ( wp_doing_ajax() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
            return false;
        }

        $now   = time();
        $state = $this->getState();

        if ( $state['dismissed'] || (int) $state['snoozed_until'] > $now ) {
            return false;
        }

        // The regular budget, plus one final ask half a year after the last one.
        $shows = (int) $state['shows'];
        if ( $shows >= self::MAX_SHOWS ) {
            if ( $shows > self::MAX_SHOWS || $now - (int) $state['last_shown'] < self::FINAL_AFTER ) {
                return false;
            }
        }

        if ( class_exists( __NAMESPACE__ . '\\Messages' ) ) {
            if ( Messages::inQuietPeriod( $now ) || '' !== Messages::renderedKind() ) {
                return false;
            }
            $last = Messages::lastShownAt();
            if ( $last > 0 && $now - $last < Messages::SPACING_DAYS * DAY_IN_SECONDS ) {
                return false;
            }
        }

        return self::siteQualifies( $now );
    }

    /** When the review request was last shown to this admin (0 = never); used by Messages. */
    public static function lastShownAt( $user_id = 0 )
    {
        $user_id = $user_id ? $user_id : get_current_user_id();
        $state   = get_user_meta( $user_id, self::USER_META, true );

        return is_array( $state ) && ! empty( $state['last_shown'] ) ? (int) $state['last_shown'] : 0;
    }

    private function getState()
    {
        $state = get_user_meta( get_current_user_id(), self::USER_META, true );

        if ( ! is_array( $state ) ) {
            $state = [];
        }

        return array_merge(
            [ 'shows' => 0, 'last_shown' => 0, 'snoozed_until' => 0, 'dismissed' => false ],
            $state
        );
    }

    private function saveState( array $state )
    {
        update_user_meta( get_current_user_id(), self::USER_META, $state );
    }

    /** Uninstall hook: the site-level options (user meta is removed with the rest). */
    public static function uninstall()
    {
        delete_option( self::USAGE_OPTION );
        delete_option( self::FIRST_SET_OPTION );
        delete_option( 'flrt_free_installed_at' ); // the 1.9.4–1.9.6 timer
        delete_metadata( 'user', 0, self::USER_META, '', true );
    }
}

new ReviewRequest();
