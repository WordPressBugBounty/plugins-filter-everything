<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Review request popup (free build only).
 *
 * Asks a long-term user to rate the plugin on wordpress.org. Shown on the
 * plugin's own admin pages to users with manage_options, at most once per page
 * load, when ALL of the following hold:
 *   - at least self::FIRST_DELAY has passed since the install timestamp
 *     (stamped on the first admin_init after install or after updating to the
 *     version that introduced it);
 *   - the site has at least one published Filter Set (the user actually uses
 *     the plugin — we only ask people the plugin has worked for);
 *   - the current admin has not dismissed it, has seen it fewer than
 *     self::MAX_SHOWS times, and is not within a snooze window.
 *
 * Every render auto-snoozes the popup for self::SNOOZE, so ignoring it equals
 * "Maybe later". "Don't show this again" and clicking through to the review
 * dismiss it permanently. State is kept per admin user (the ask is personal),
 * the install timestamp per site.
 */
class ReviewRequest
{
    const INSTALL_OPTION     = 'flrt_free_installed_at';
    const USER_META          = 'flrt_review_request';
    const NONCE_ACTION       = 'flrt_review_popup';
    const INSTALLS_TRANSIENT = 'flrt_free_active_installs';

    const REVIEW_URL  = 'https://wordpress.org/support/plugin/filter-everything/reviews/#new-post';
    const SUPPORT_URL = 'https://wordpress.org/support/plugin/filter-everything/';

    const FIRST_DELAY = 14 * DAY_IN_SECONDS;
    const SNOOZE      = 21 * DAY_IN_SECONDS;
    const MAX_SHOWS   = 3;

    // Used when the wordpress.org API is unreachable; matches the listing at
    // the time this feature shipped.
    const INSTALLS_FALLBACK = 50000;

    public function __construct()
    {
        // The review ask targets the wordpress.org listing — free build only.
        if ( defined('FLRT_FILTERS_PRO') ) {
            return;
        }

        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_init', [ $this, 'stampInstallTime' ] );
        add_action( 'admin_footer', [ $this, 'maybeRender' ] );
        add_action( 'wp_ajax_flrt_review_popup', [ $this, 'ajaxDismiss' ] );
    }

    /**
     * Existing installs never recorded an install time — the first admin_init
     * after the update becomes their reference point, so everybody waits the
     * same FIRST_DELAY from a known moment.
     */
    public function stampInstallTime()
    {
        if ( ! get_option( self::INSTALL_OPTION ) ) {
            add_option( self::INSTALL_OPTION, time() );
        }
    }

    public function maybeRender()
    {
        if ( ! $this->shouldShow() ) {
            return;
        }

        // Count the show and auto-snooze right away: a user who ignores the
        // popup (navigates on without clicking anything) is treated exactly
        // like "Maybe later", and reloading pages cannot burn through the
        // MAX_SHOWS budget within one session.
        $state                  = $this->getState();
        $state['shows']         = (int) $state['shows'] + 1;
        $state['snoozed_until'] = time() + self::SNOOZE;
        $this->saveState( $state );

        flrt_include_admin_view( 'review-popup', [
            'installs_label' => $this->getActiveInstallsLabel(),
            'review_nonce'   => wp_create_nonce( self::NONCE_ACTION ),
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

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        // The plugin's own admin pages only — same gate as the PRO modal.
        if ( ! $screen || $screen->post_type !== FLRT_FILTERS_SET_POST_TYPE ) {
            return false;
        }

        $installed_at = (int) get_option( self::INSTALL_OPTION );

        if ( ! $installed_at || ( time() - $installed_at ) < self::FIRST_DELAY ) {
            return false;
        }

        $state = $this->getState();

        if ( $state['dismissed']
            || (int) $state['shows'] >= self::MAX_SHOWS
            || (int) $state['snoozed_until'] > time()
        ) {
            return false;
        }

        // Only ask users the plugin has actually worked for: at least one
        // published Filter Set.
        $sets = get_posts( [
            'post_type'   => FLRT_FILTERS_SET_POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 1,
            'fields'      => 'ids',
        ] );

        return ! empty( $sets );
    }

    private function getState()
    {
        $state = get_user_meta( get_current_user_id(), self::USER_META, true );

        if ( ! is_array( $state ) ) {
            $state = [];
        }

        return array_merge(
            [ 'shows' => 0, 'snoozed_until' => 0, 'dismissed' => false ],
            $state
        );
    }

    private function saveState( array $state )
    {
        update_user_meta( get_current_user_id(), self::USER_META, $state );
    }

    /**
     * "50,000+" — the real active-installs count from the wordpress.org
     * listing, refreshed weekly. On API failure the last known/fallback count
     * is cached for a day so the admin never waits on a dead request twice.
     *
     * @return string
     */
    private function getActiveInstallsLabel()
    {
        $installs = get_transient( self::INSTALLS_TRANSIENT );

        if ( false === $installs ) {
            $installs = 0;
            $response = wp_remote_get(
                'https://api.wordpress.org/plugins/info/1.0/filter-everything.json?fields=active_installs',
                [ 'timeout' => 5 ]
            );

            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $data     = json_decode( wp_remote_retrieve_body( $response ), true );
                $installs = isset( $data['active_installs'] ) ? (int) $data['active_installs'] : 0;
            }

            if ( $installs > 0 ) {
                set_transient( self::INSTALLS_TRANSIENT, $installs, WEEK_IN_SECONDS );
            } else {
                $installs = self::INSTALLS_FALLBACK;
                set_transient( self::INSTALLS_TRANSIENT, $installs, DAY_IN_SECONDS );
            }
        }

        return number_format_i18n( (int) $installs ) . '+';
    }
}

new ReviewRequest();
