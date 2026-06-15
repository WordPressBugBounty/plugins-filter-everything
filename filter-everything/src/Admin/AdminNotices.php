<?php


namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Lightweight, reusable admin-notice manager.
 *
 * To add or change a notice, edit self::notices() only — each entry fully
 * describes its text, type and the event it is tied to. The rest (free/PRO
 * gating, capability, rendering, persistent dismissal) is handled generically.
 *
 * Notice fields:
 *   id          unique slug, chars [a-z0-9_-] (dismissal key + CSS class + AJAX scope)
 *   type        info | success | warning | error
 *   message     string (pre-escaped) OR a callable returning the escaped HTML
 *   trigger     'always' | 'update' | callable returning bool
 *   free_only   bool — hide in the PRO build (default false)
 *   capability  capability required to see/dismiss it (default flrt_plugin_user_caps())
 *   dismissible bool — show the "X" and remember the dismissal permanently, per id
 *
 * The 'update' trigger fires only after an existing install is updated (never
 * on a fresh install): Plugin::activate() stamps the version on fresh installs,
 * so a missing/older stamp means an in-place update happened.
 */
class AdminNotices
{
    /** Last-seen plugin version on this site. */
    const VERSION_OPTION = 'flrt_version';

    /** Version the site was most recently updated to (drives the 'update' trigger). */
    const UPDATED_OPTION = 'flrt_updated_to';

    /** Array of permanently dismissed notice ids. */
    const DISMISSED_OPTION = 'flrt_dismissed_notices';

    /** Map of notice id => unix time when first shown (drives 'expires_after'). */
    const STARTED_OPTION = 'flrt_notice_started';

    /** Shared AJAX action / nonce for dismissing any notice. */
    const DISMISS_ACTION = 'flrt_dismiss_notice';

    /**
     * Preview mode — for fine-tuning notice text and appearance during development.
     *
     * When true: every notice is shown on every admin page (trigger, expiry and
     * dismissal state are ignored) and the "X" only hides it client-side, so it
     * reappears on reload. Keep false in production.
     */
    const PREVIEW_MODE = false;

    public function __construct()
    {
        add_action( 'admin_init', [ $this, 'detectUpdate' ] );
        add_action( 'admin_init', [ $this, 'maybeAutoDismiss' ] );
        add_action( 'admin_notices', [ $this, 'renderAll' ] );
        add_action( 'wp_ajax_' . self::DISMISS_ACTION, [ $this, 'ajaxDismiss' ] );
    }

    /**
     * Single source of truth for admin notices. Add one array entry per notice.
     *
     * @return array[]
     */
    protected function notices()
    {
        return [
            [
                'id'            => 'security-1922',
                'type'          => 'warning',
                'free_only'     => false, // true = Free only; false = show in both Free and PRO
                'trigger'       => 'update',
                'expires_after' => DAY_IN_SECONDS,
                'auto_dismiss'  => function () {
                    // Auto-hide once the user opens the Color Swatches (Experimental) settings tab.
                    return isset( $_GET['page'], $_GET['tab'] )
                        && sanitize_key( wp_unslash( $_GET['page'] ) ) === 'filters-settings'
                        && sanitize_key( wp_unslash( $_GET['tab'] ) ) === 'experimental';
                },
                'message'       => function () {
                    $settings_url = admin_url( 'edit.php?post_type=' . FLRT_FILTERS_SET_POST_TYPE . '&page=filters-settings&tab=experimental' );

                    return sprintf(
                        /* translators: 1: opening <a> tag to the plugin settings page, 2: closing </a> tag. */
                        wp_kses(
                            __( 'Thank you for updating Filter Everything! This release includes a security update related to how <strong>Color swatches</strong> are rendered. Everything should work fine, but just in case, please check how your Color swatches look in the filters on your site\'s pages. You can see the list of Color swatches you use on %1$s<strong>this settings page</strong>%2$s.', 'filter-everything' ),
                            [ 'strong' => [], 'a' => [ 'href' => [] ] ]
                        ),
                        '<a href="' . esc_url( $settings_url ) . '">',
                        '</a>'
                    );
                },
            ],
        ];
    }

    /**
     * Records that an existing install was updated, so 'update' notices fire.
     * Fresh installs are pre-stamped in Plugin::activate() and match here.
     */
    public function detectUpdate()
    {
        if ( self::PREVIEW_MODE ) {
            return;
        }

        $stored = get_option( self::VERSION_OPTION, false );

        if ( $stored === FLRT_PLUGIN_VER ) {
            return;
        }

        update_option( self::UPDATED_OPTION, FLRT_PLUGIN_VER );
        update_option( self::VERSION_OPTION, FLRT_PLUGIN_VER );
    }

    /**
     * Runs each notice's optional 'auto_dismiss' condition (e.g. "the user opened
     * the relevant settings page") and dismisses it permanently when it matches.
     */
    public function maybeAutoDismiss()
    {
        if ( self::PREVIEW_MODE ) {
            return;
        }

        foreach ( $this->notices() as $notice ) {
            if ( empty( $notice['id'] ) || empty( $notice['auto_dismiss'] ) || ! is_callable( $notice['auto_dismiss'] ) ) {
                continue;
            }
            if ( $this->isDismissed( $notice['id'] ) ) {
                continue;
            }
            if ( call_user_func( $notice['auto_dismiss'] ) ) {
                $this->markDismissed( $notice['id'] );
            }
        }
    }

    public function renderAll()
    {
        foreach ( $this->notices() as $notice ) {
            $this->maybeRender( $notice );
        }
    }

    protected function maybeRender( array $notice )
    {
        $notice = array_merge(
            [
                'id'          => '',
                'type'        => 'info',
                'message'     => '',
                'trigger'     => 'always',
                'free_only'   => false,
                'capability'  => flrt_plugin_user_caps(),
                'dismissible' => true,
            ],
            $notice
        );

        if ( $notice['id'] === '' ) {
            return;
        }

        if ( $notice['free_only'] && defined( 'FLRT_FILTERS_PRO' ) && FLRT_FILTERS_PRO ) {
            return;
        }

        if ( $notice['capability'] && ! current_user_can( $notice['capability'] ) ) {
            return;
        }

        if ( ! self::PREVIEW_MODE ) {
            if ( $this->isDismissed( $notice['id'] ) ) {
                return;
            }
            if ( ! $this->triggerPasses( $notice['trigger'] ) ) {
                return;
            }
            if ( $this->hasExpired( $notice ) ) {
                return;
            }
        }

        $message = is_callable( $notice['message'] ) ? call_user_func( $notice['message'] ) : $notice['message'];
        if ( $message === '' ) {
            return;
        }

        $notice_class = 'flrt-notice-' . sanitize_html_class( $notice['id'] );

        if ( function_exists( 'wp_admin_notice' ) ) {
            // Modern WordPress notice API (WP 6.4+).
            wp_admin_notice(
                $message,
                [
                    'type'               => $notice['type'],
                    'dismissible'        => (bool) $notice['dismissible'],
                    'additional_classes' => [ 'flrt-admin-notice', $notice_class ],
                ]
            );
        } else {
            // Fallback for WordPress < 6.4 ($message is already escaped above).
            printf(
                '<div class="notice notice-%1$s%2$s flrt-admin-notice %3$s"><p>%4$s</p></div>',
                esc_attr( $notice['type'] ),
                $notice['dismissible'] ? ' is-dismissible' : '',
                esc_attr( $notice_class ),
                $message // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with wp_kses()/esc_url() above
            );
        }

        // Persist the dismissal only in live mode; in preview the "X" is client-side only.
        if ( ! self::PREVIEW_MODE && $notice['dismissible'] ) {
            $this->printDismissScript( $notice['id'], $notice_class );
        }
    }

    protected function triggerPasses( $trigger )
    {
        if ( $trigger === 'always' ) {
            return true;
        }

        if ( $trigger === 'update' ) {
            return get_option( self::UPDATED_OPTION ) === FLRT_PLUGIN_VER;
        }

        if ( is_callable( $trigger ) ) {
            return (bool) call_user_func( $trigger );
        }

        return false;
    }

    protected function isDismissed( $id )
    {
        return in_array( $id, (array) get_option( self::DISMISSED_OPTION, [] ), true );
    }

    protected function markDismissed( $id )
    {
        $dismissed = (array) get_option( self::DISMISSED_OPTION, [] );
        if ( ! in_array( $id, $dismissed, true ) ) {
            $dismissed[] = $id;
            update_option( self::DISMISSED_OPTION, $dismissed );
        }
    }

    /**
     * True once a notice with an 'expires_after' (seconds) has been visible that
     * long. The first-shown time is stamped per id on the first eligible render.
     */
    protected function hasExpired( array $notice )
    {
        if ( empty( $notice['expires_after'] ) ) {
            return false;
        }

        $started = (array) get_option( self::STARTED_OPTION, [] );
        if ( ! isset( $started[ $notice['id'] ] ) ) {
            $started[ $notice['id'] ] = time();
            update_option( self::STARTED_OPTION, $started );
        }

        return ( time() - (int) $started[ $notice['id'] ] ) >= (int) $notice['expires_after'];
    }

    /**
     * Persists the dismissal of a specific notice when its "X" is clicked.
     */
    protected function printDismissScript( $id, $notice_class )
    {
        ?>
        <script>
        ( function () {
            var notice = document.querySelector( <?php echo wp_json_encode( '.' . $notice_class ); ?> );
            if ( ! notice ) {
                return;
            }
            notice.addEventListener( 'click', function ( e ) {
                if ( ! e.target.closest( '.notice-dismiss' ) ) {
                    return;
                }
                var body = new URLSearchParams();
                body.append( 'action', <?php echo wp_json_encode( self::DISMISS_ACTION ); ?> );
                body.append( 'id', <?php echo wp_json_encode( $id ); ?> );
                body.append( 'nonce', <?php echo wp_json_encode( wp_create_nonce( self::DISMISS_ACTION ) ); ?> );
                fetch( <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                } );
            } );
        } )();
        </script>
        <?php
    }

    /**
     * AJAX handler — permanently marks a notice as dismissed.
     */
    public function ajaxDismiss()
    {
        check_ajax_referer( self::DISMISS_ACTION, 'nonce' );

        $id     = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
        $notice = $this->findNotice( $id );

        if ( ! $notice ) {
            wp_send_json_error( null, 400 );
        }

        $capability = ! empty( $notice['capability'] ) ? $notice['capability'] : flrt_plugin_user_caps();
        if ( ! current_user_can( $capability ) ) {
            wp_send_json_error( null, 403 );
        }

        $this->markDismissed( $id );

        wp_send_json_success();
    }

    protected function findNotice( $id )
    {
        if ( $id === '' ) {
            return null;
        }

        foreach ( $this->notices() as $notice ) {
            if ( isset( $notice['id'] ) && $notice['id'] === $id ) {
                return $notice;
            }
        }

        return null;
    }
}

new AdminNotices();
