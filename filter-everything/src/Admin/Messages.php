<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * «Plugin notifications» — the display half of the message channel (roadmap 2.19).
 *
 * MessageFeed brings a list of messages, BuiltinMessages a few that ship with
 * the release itself; this class decides which ONE of them, if any, this admin
 * sees right now, and draws it with a layout that ships in the plugin. The feed
 * chooses a layout and fills its slots — the design itself never comes from
 * the server. A feed entry overrides a built-in one with the same id.
 *
 * Consent. Free build: off until the admin says yes (wordpress.org guideline 7
 * forbids contacting an external server without consent) — a one-off quiet
 * question on the plugin's Settings screen once a Filter Set is published
 * (see isConsentMoment()), plus a checkbox in Settings. PRO build:
 * on by default (the site is knowingly connected to our license server), same
 * checkbox to turn it off. FLRT_DISABLE_MESSAGES or the wpc_messages_enabled
 * filter switch the whole thing off in code.
 *
 * Where. Only `critical` messages may appear outside the plugin's screens
 * (guideline 11); everything else stays on Filter Sets / SEO Rules / Settings
 * (What's new included). One message at a time, by priority.
 *
 * Fatigue. Dismissal is per user and permanent for that message id. Dismissing
 * a promo silences all promos for 14 days, dismissing a tip silences tips for
 * 30 days. Every message has a mandatory end date (MessageFeed::validate()).
 * The day after an update or a fresh install is quiet for everything but
 * critical/important — the admin has just met the What's new badge and, in
 * the free build, the consent question; a sale bar on top of that is noise.
 * Promos also wait for the first published Filter Set, like the consent
 * question does.
 *
 * Trust boundary. Every text slot goes through self::html(): a short wp_kses
 * tag list, our own class names only, links only to MessageFeed::LINK_HOSTS or
 * to this site's admin. Plain slots are escaped. No images, styles or scripts.
 */
class Messages
{
    const CONSENT_OPTION = 'flrt_messages_consent';
    const SETTING_KEY    = 'plugin_news';
    const DISMISSED_META = 'flrt_msg_dismissed';
    /** Per user: ids of messages already shown once — drives the What's new badge. */
    const SEEN_META      = 'flrt_msg_seen';
    const NONCE          = 'flrt_messages';
    /** Stamped by Plugin::activate() on fresh installs (updates stamp flrt_updated_at). */
    const INSTALLED_AT_OPTION = 'flrt_installed_at';
    /** No promo/info/tip within this many hours after an update or install. */
    const QUIET_AFTER_UPDATE_HOURS = 24;
    /**
     * Asks keep their distance: after the review request was shown, no
     * promo/info/tip for this many days; the review request itself waits
     * as long after any message or the consent question (ReviewRequest).
     */
    const SPACING_DAYS = 7;
    /** Per user: when this admin last saw a message or the consent question. */
    const LAST_SHOWN_META = 'flrt_msg_last_shown';

    /** Most important first. */
    const PRIORITY = array( 'critical' => 0, 'important' => 1, 'promo' => 2, 'info' => 3, 'tip' => 4 );

    /** After dismissing a message of this type, stay quiet about the type for N days. */
    const COOLDOWN_DAYS = array( 'promo' => 14, 'tip' => 30 );

    const ALLOWED_CLASSES = array( 'wpc-msg__pill', 'wpc-msg__muted', 'wpc-msg__nowrap' );

    /** @var array|null|false message to render on this request (false = not resolved yet) */
    private $current = false;

    public function __construct()
    {
        add_action( MessageFeed::CRON_HOOK, array( __CLASS__, 'cron' ) );
        add_action( 'admin_init', array( $this, 'syncSchedule' ) );

        add_filter( 'wpc_general_filters_settings', array( $this, 'settingsField' ) );
        add_filter( 'option_wpc_filter_settings', array( $this, 'mirrorSetting' ) );
        add_filter( 'pre_update_option_wpc_filter_settings', array( $this, 'saveSetting' ), 20, 2 );

        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_notices', array( $this, 'render' ), 1 );

        add_action( 'wp_ajax_flrt_message_dismiss', array( $this, 'ajaxDismiss' ) );
        add_action( 'wp_ajax_flrt_messages_consent', array( $this, 'ajaxConsent' ) );
    }

    /* ---------------------------------------------------------------------
     * Consent and scheduling
     * ------------------------------------------------------------------- */

    public static function isPro()
    {
        return defined( 'FLRT_FILTERS_PRO' ) && FLRT_FILTERS_PRO;
    }

    /** 'yes' | 'no' | '' (never answered) */
    public static function consent()
    {
        $c = get_option( self::CONSENT_OPTION, '' );

        return in_array( $c, array( 'yes', 'no' ), true ) ? $c : '';
    }

    public static function enabled()
    {
        if ( defined( 'FLRT_DISABLE_MESSAGES' ) && FLRT_DISABLE_MESSAGES ) {
            return false;
        }

        $consent = self::consent();
        $enabled = ( 'yes' === $consent ) || ( '' === $consent && self::isPro() );

        return (bool) apply_filters( 'wpc_messages_enabled', $enabled );
    }

    public static function setConsent( $yes )
    {
        update_option( self::CONSENT_OPTION, $yes ? 'yes' : 'no', false );

        if ( $yes && self::enabled() ) {
            MessageFeed::schedule();
        } else {
            MessageFeed::unschedule();
            delete_option( MessageFeed::CACHE_OPTION );
        }
    }

    public static function cron()
    {
        if ( self::enabled() ) {
            MessageFeed::fetch();
        } else {
            MessageFeed::unschedule();
        }
    }

    /** Keeps the cron event in line with the switch, whatever changed it. */
    public function syncSchedule()
    {
        $scheduled = (bool) wp_next_scheduled( MessageFeed::CRON_HOOK );

        if ( self::enabled() && ! $scheduled ) {
            MessageFeed::schedule();
        } elseif ( ! self::enabled() && $scheduled ) {
            MessageFeed::unschedule();
        }
    }

    /** Plugin::deactivate() */
    public static function teardown()
    {
        MessageFeed::unschedule();
    }

    /** Plugin::uninstall() */
    public static function uninstall()
    {
        MessageFeed::unschedule();
        delete_option( MessageFeed::CACHE_OPTION );
        delete_option( self::CONSENT_OPTION );
        delete_metadata( 'user', 0, self::DISMISSED_META, '', true );
        delete_metadata( 'user', 0, self::SEEN_META, '', true );
        delete_metadata( 'user', 0, self::LAST_SHOWN_META, '', true );
        delete_option( self::INSTALLED_AT_OPTION );
    }

    /* ---------------------------------------------------------------------
     * Settings → General → Other
     * ------------------------------------------------------------------- */

    public function settingsField( $settings )
    {
        if ( isset( $settings['common_settings']['fields'] ) ) {
            $settings['common_settings']['fields'][ self::SETTING_KEY ] = array(
                'type'        => 'checkbox',
                'title'       => esc_html__( 'Plugin notifications', 'filter-everything' ),
                'id'          => self::SETTING_KEY,
                'label'       => esc_html__( 'Receive security alerts, update warnings, usage tips and occasional offers', 'filter-everything' ),
                'description' => esc_html__( 'Once a day the plugin downloads a small public file from filtereverything.pro. Nothing about your site is sent. Messages appear only on the plugin\'s own screens, except urgent security alerts.', 'filter-everything' ),
            );
        }

        return $settings;
    }

    /** The checkbox always shows the real state, including the PRO default. */
    public function mirrorSetting( $value )
    {
        if ( is_array( $value ) ) {
            if ( self::enabled() ) {
                $value[ self::SETTING_KEY ] = 'on';
            } else {
                unset( $value[ self::SETTING_KEY ] );
            }
        }

        return $value;
    }

    public function saveSetting( $value, $old_value )
    {
        // Only a real submission of the Settings form may change consent — the
        // option is also rewritten programmatically, without this key.
        if ( ! isset( $_POST['option_page'] ) || 'wpc_filter' !== $_POST['option_page'] || ! current_user_can( flrt_plugin_user_caps() ) ) {
            return $value;
        }

        $posted = is_array( $value ) && ! empty( $value[ self::SETTING_KEY ] );

        if ( $posted && 'yes' !== self::consent() ) {
            self::setConsent( true );
        } elseif ( ! $posted && self::enabled() ) {
            self::setConsent( false );
        }

        return $value;
    }

    /* ---------------------------------------------------------------------
     * Who is looking: the local facts a message audience is matched against
     * ------------------------------------------------------------------- */

    public static function context()
    {
        $license = 'none';
        if ( self::isPro() && function_exists( 'flrt_get_license_key' ) ) {
            $key = flrt_get_license_key();
            if ( $key ) {
                $decoded = base64_decode( (string) $key, true );
                $source  = $decoded ? strtok( $decoded, '|' ) : '';
                $license = 'codecanyon' === $source ? 'codecanyon' : ( 'filtereverything' === $source ? 'shop' : 'other' );
            }
        }

        return array(
            'build'   => self::isPro() ? 'pro' : 'free',
            'license' => $license,
            'version' => FLRT_PLUGIN_VER,
            'woo'     => function_exists( 'flrt_is_woocommerce' ) && flrt_is_woocommerce(),
            'locale'  => function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(),
        );
    }

    public static function matches( array $audience, array $ctx )
    {
        foreach ( $audience as $key => $want ) {
            switch ( $key ) {
                case 'build':
                case 'license':
                    if ( ! in_array( $ctx[ $key ], (array) $want, true ) ) {
                        return false;
                    }
                    break;
                case 'locale':
                    $lang = substr( $ctx['locale'], 0, 2 );
                    if ( ! in_array( $ctx['locale'], (array) $want, true ) && ! in_array( $lang, (array) $want, true ) ) {
                        return false;
                    }
                    break;
                case 'min_ver':
                    if ( '' !== (string) $want && version_compare( $ctx['version'], (string) $want, '<' ) ) {
                        return false;
                    }
                    break;
                case 'max_ver':
                    if ( '' !== (string) $want && version_compare( $ctx['version'], (string) $want, '>' ) ) {
                        return false;
                    }
                    break;
                case 'woo':
                    if ( null !== $want && (bool) $want !== $ctx['woo'] ) {
                        return false;
                    }
                    break;
                default:
                    return false; // MessageFeed drops these already; never guess
            }
        }

        return true;
    }

    public static function isPluginScreen()
    {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen ) {
            return false;
        }

        $types = array( FLRT_FILTERS_SET_POST_TYPE );
        if ( defined( 'FLRT_SEO_RULES_POST_TYPE' ) ) {
            $types[] = FLRT_SEO_RULES_POST_TYPE;
        }

        return in_array( (string) $screen->post_type, $types, true )
            || false !== strpos( (string) $screen->id, 'filters-settings' );
    }

    /**
     * Where and when the free build asks for consent. Always only once the site
     * has a published Filter Set — a newcomer is busy building the first
     * filters and would see the question as noise. Then on the Settings page,
     * any tab: where someone exploring the plugin further ends up, and where an
     * existing user lands after an update, drawn by the red «1» badge to the
     * What's new tab. They rarely open Settings once their filters work, so
     * without that badge most of them would never be asked.
     */
    public static function isConsentMoment()
    {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $id     = $screen ? (string) $screen->id : '';
        if ( false === strpos( $id, 'filters-settings' ) ) {
            return false;
        }

        return self::hasPublishedSet();
    }

    private static function dismissed( $user_id )
    {
        $d = get_user_meta( $user_id, self::DISMISSED_META, true );

        return is_array( $d ) ? $d : array();
    }

    /**
     * Everything that may be shown: the release's built-in messages, overridden
     * by feed entries with the same id — but the feed only counts while the
     * admin allows it (its cache is dropped on «no» anyway).
     *
     * @return array keyed by id
     */
    public static function messages()
    {
        $list = BuiltinMessages::validated();

        if ( self::enabled() ) {
            $cache = MessageFeed::cached();
            foreach ( $cache['messages'] as $id => $m ) {
                $list[ $id ] = $m;
            }
        }

        return $list;
    }

    /**
     * The first day after an update or a fresh install: nothing but
     * critical/important. flrt_updated_at is stamped by AdminNotices on
     * updates, flrt_installed_at by Plugin::activate() on installs.
     */
    public static function inQuietPeriod( $now = 0 )
    {
        $now   = $now ? $now : time();
        $since = max( (int) get_option( 'flrt_updated_at', 0 ), (int) get_option( self::INSTALLED_AT_OPTION, 0 ) );

        return $since > 0 && $now < $since + self::QUIET_AFTER_UPDATE_HOURS * HOUR_IN_SECONDS;
    }

    /** True within SPACING_DAYS after the review request was shown to this admin. */
    public static function reviewAskedRecently( $user_id, $now = 0 )
    {
        if ( ! class_exists( __NAMESPACE__ . '\\ReviewRequest' ) ) {
            return false;
        }
        $now  = $now ? $now : time();
        $last = ReviewRequest::lastShownAt( $user_id );

        return $last > 0 && $now - $last < self::SPACING_DAYS * DAY_IN_SECONDS;
    }

    /** When this admin last saw a message or the consent question (0 = never). */
    public static function lastShownAt( $user_id = 0 )
    {
        $user_id = $user_id ? $user_id : get_current_user_id();

        return (int) get_user_meta( $user_id, self::LAST_SHOWN_META, true );
    }

    /** What render() put on this request: 'consent' | 'message' | ''. */
    private static $rendered_kind = '';

    public static function renderedKind()
    {
        return self::$rendered_kind;
    }

    public static function hasPublishedSet()
    {
        $counts = wp_count_posts( FLRT_FILTERS_SET_POST_TYPE );

        return isset( $counts->publish ) && (int) $counts->publish > 0;
    }

    /**
     * The one message this user sees on this screen, or null.
     */
    public static function pick( $on_plugin_screen, $user_id = 0, $now = 0 )
    {
        $user_id   = $user_id ? $user_id : get_current_user_id();
        $now       = $now ? $now : time();
        $ctx       = self::context();
        $dismissed = self::dismissed( $user_id );
        $quiet_day = self::inQuietPeriod( $now ) || self::reviewAskedRecently( $user_id, $now );
        $has_set   = null; // resolved lazily: one query, only when a promo is in play

        $quiet = array();
        foreach ( $dismissed as $d ) {
            $type = isset( $d['type'] ) ? $d['type'] : '';
            if ( isset( self::COOLDOWN_DAYS[ $type ] ) && $d['t'] > $now - self::COOLDOWN_DAYS[ $type ] * DAY_IN_SECONDS ) {
                $quiet[ $type ] = true;
            }
        }

        $candidates = array();
        foreach ( self::messages() as $m ) {
            if ( $m['starts'] > $now || $m['ends'] <= $now ) {
                continue;
            }
            if ( isset( $dismissed[ $m['id'] ] ) || isset( $quiet[ $m['type'] ] ) ) {
                continue;
            }
            if ( 'critical' !== $m['type'] && ! $on_plugin_screen ) {
                continue;
            }
            if ( $quiet_day && ! in_array( $m['type'], array( 'critical', 'important' ), true ) ) {
                continue;
            }
            if ( 'promo' === $m['type'] ) {
                if ( null === $has_set ) {
                    $has_set = self::hasPublishedSet();
                }
                if ( ! $has_set ) {
                    continue;
                }
            }
            if ( ! self::matches( $m['audience'], $ctx ) ) {
                continue;
            }
            $candidates[] = $m;
        }

        if ( ! $candidates ) {
            return null;
        }

        usort( $candidates, function ( $a, $b ) {
            $pa = self::PRIORITY[ $a['type'] ];
            $pb = self::PRIORITY[ $b['type'] ];
            if ( $pa !== $pb ) {
                return $pa - $pb;
            }
            if ( $a['priority'] !== $b['priority'] ) {
                return $b['priority'] - $a['priority'];
            }
            return $b['starts'] - $a['starts'];
        } );

        return $candidates[0];
    }

    /* ---------------------------------------------------------------------
     * Rendering
     * ------------------------------------------------------------------- */

    /** @return array|null  ['kind' => 'consent'] | ['kind' => 'message', 'message' => …] */
    private function current()
    {
        if ( false !== $this->current ) {
            return $this->current;
        }
        $this->current = null;

        if ( ! current_user_can( flrt_plugin_user_caps() ) ) {
            return null;
        }

        $on_plugin_screen = self::isPluginScreen();

        if ( ! self::enabled() ) {
            $never_asked = '' === self::consent() && ! ( defined( 'FLRT_DISABLE_MESSAGES' ) && FLRT_DISABLE_MESSAGES );
            // Would a "yes" actually turn it on? A developer who switched the
            // channel off with the wpc_messages_enabled filter must not be asked.
            $can_enable  = (bool) apply_filters( 'wpc_messages_enabled', true );
            if ( $never_asked && $can_enable && ! self::isPro() && self::isConsentMoment() ) {
                $this->current = array( 'kind' => 'consent' );
                return $this->current;
            }
            // No feed here — pick() sees the built-in messages only.
        }

        $m = self::pick( $on_plugin_screen );
        if ( $m ) {
            $this->current = array( 'kind' => 'message', 'message' => $m );
        }

        return $this->current;
    }

    public function assets()
    {
        // The sale strip on the PRO benefits tab needs the countdown script and
        // its styles even when no bar is drawn on this request.
        if ( ! $this->current() && ! self::stripScreen() ) {
            return;
        }

        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'wpc-messages', FLRT_PLUGIN_DIR_URL . 'assets/css/wpc-messages' . $suffix . '.css', array(), FLRT_PLUGIN_VER );
        wp_enqueue_script( 'wpc-messages', FLRT_PLUGIN_DIR_URL . 'assets/js/wpc-messages' . $suffix . '.js', array(), FLRT_PLUGIN_VER, true );
        wp_localize_script( 'wpc-messages', 'wpcMessages', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( self::NONCE ),
        ) );
    }

    public function render()
    {
        $current = $this->current();
        if ( ! $current ) {
            return;
        }

        echo '<div class="wpc-msg-area">';
        if ( 'consent' === $current['kind'] ) {
            $this->renderConsent();
        } else {
            $this->renderMessage( $current['message'] );
            self::markSeen( $current['message']['id'] );
            self::$rendered_id = $current['message']['id'];
        }
        echo '</div>';

        self::$rendered_kind = $current['kind'];
        update_user_meta( get_current_user_id(), self::LAST_SHOWN_META, time() );
    }

    /* ---------------------------------------------------------------------
     * Feed-driven PRO price for the «Upgrade to PRO» popup
     * ------------------------------------------------------------------- */

    /**
     * The price block from the feed while it is in force, or null — then the
     * popup falls back to FLRT_PRO_PRICE. Like everything from the feed it is
     * only available once the admin allowed notifications: a site that declined
     * never contacts filtereverything.pro, so it keeps the price it shipped with.
     *
     * @return array{price:string, was:string, off:string}|null
     */
    public static function pricing()
    {
        $p = null;

        if ( self::enabled() ) {
            $cache = MessageFeed::cached();
            $p     = $cache['pricing']; // a feed block, even an expired one, overrides the built-in
        }
        if ( ! $p ) {
            $p = BuiltinMessages::pricing();
        }

        $now = time();
        if ( ! $p || $p['starts'] > $now || $p['ends'] <= $now ) {
            return null;
        }

        return apply_filters( 'wpc_pro_pricing', array(
            'price' => $p['price'],
            'was'   => $p['was'],
            'off'   => $p['off'],
            'ends'  => $p['ends'],
        ) );
    }

    /**
     * A message that is «standing» right now — active, aimed at this site —
     * regardless of whether this admin dismissed it, of cooldowns or of the
     * quiet day. For places that restate an offer in their own words, e.g. the
     * PRO benefits tab: the top bar may be gone, the sale is not.
     *
     * @return array|null
     */
    public static function standing( $id, $now = 0 )
    {
        $now = $now ? $now : time();
        $all = self::messages();
        if ( empty( $all[ $id ] ) ) {
            return null;
        }
        $m = $all[ $id ];
        if ( $m['starts'] > $now || $m['ends'] <= $now || ! self::matches( $m['audience'], self::context() ) ) {
            return null;
        }

        return $m;
    }

    /**
     * The sale promo in force right now (feed copy first, built-in otherwise),
     * dismissed or not — what the PRO benefits tab restates at its top.
     *
     * @return array|null
     */
    public static function standingSale()
    {
        foreach ( self::messages() as $id => $m ) {
            if ( 'promo' === $m['type'] && 'sale' === $m['theme'] ) {
                $standing = self::standing( $id );
                if ( $standing ) {
                    return $standing;
                }
            }
        }

        return null;
    }

    /** True on the PRO benefits tab while a sale strip will be drawn there. */
    private static function stripScreen()
    {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        return $screen && false !== strpos( (string) $screen->id, 'filters-settings' )
            && isset( $_GET['tab'] ) && 'aboutpro' === $_GET['tab']
            && current_user_can( flrt_plugin_user_caps() )
            && self::standingSale();
    }

    /** Id of the message drawn as the bar on this request, or '' — set by render(). */
    private static $rendered_id = '';

    public static function renderedId()
    {
        return self::$rendered_id;
    }

    /* ---------------------------------------------------------------------
     * «Unread» state for the What's new badge
     * ------------------------------------------------------------------- */

    /**
     * True when this admin has a message waiting on the plugin's screens that
     * they have not been shown yet. WhatsNew lights its red «1» on the Filters
     * menu for it — the menu is visible from every admin screen, so an admin
     * who only updates plugins (or lets a tool do it) still gets a nudge.
     */
    public static function hasUnseen( $user_id = 0 )
    {
        if ( ! self::enabled() ) {
            return false;
        }
        $user_id = $user_id ? $user_id : get_current_user_id();
        $m       = self::pick( true, $user_id );

        // A built-in message rides on the release: the What's new badge for
        // that release already brings the admin in, no second nudge for it.
        return $m && empty( $m['builtin'] ) && ! in_array( $m['id'], self::seen( $user_id ), true );
    }

    private static function seen( $user_id )
    {
        $s = get_user_meta( $user_id, self::SEEN_META, true );

        return is_array( $s ) ? $s : array();
    }

    public static function markSeen( $id, $user_id = 0 )
    {
        $user_id = $user_id ? $user_id : get_current_user_id();
        $seen    = self::seen( $user_id );
        if ( in_array( $id, $seen, true ) ) {
            return;
        }
        $seen[] = $id;
        update_user_meta( $user_id, self::SEEN_META, array_slice( $seen, -60 ) );
    }

    private function renderConsent()
    {
        ?>
        <div class="wpc-msg wpc-msg--tip wpc-msg--neutral wpc-msg--consent">
            <span class="dashicons dashicons-megaphone wpc-msg__icon" aria-hidden="true"></span>
            <div class="wpc-msg__main">
                <strong class="wpc-msg__title"><?php esc_html_e( 'Do you agree to receive helpful notifications?', 'filter-everything' ); ?></strong>
                <div class="wpc-msg__html"><?php echo wp_kses( __( 'Filter Everything can alert you to <strong>security issues and important updates</strong>, and share usage tips and occasional offers (we won\'t spam you). Once a day the plugin downloads a small public file from filtereverything.pro. Nothing about your site is sent. Messages appear only on the plugin\'s own screens, except urgent security alerts. You can change this any time in Settings.', 'filter-everything' ), array( 'strong' => array() ) ); ?></div>
                <p class="wpc-msg__actions">
                    <button type="button" class="button button-primary" data-wpc-consent="yes"><?php esc_html_e( 'Yes, I agree', 'filter-everything' ); ?></button>
                    <button type="button" class="button-link" data-wpc-consent="no"><?php esc_html_e( 'No, I don\'t agree', 'filter-everything' ); ?></button>
                </p>
            </div>
        </div>
        <?php
    }

    public static function content( array $m )
    {
        $locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();

        foreach ( array( $locale, substr( $locale, 0, 2 ), 'en' ) as $lang ) {
            if ( isset( $m['content'][ $lang ] ) ) {
                return $m['content'][ $lang ];
            }
        }

        return reset( $m['content'] );
    }

    private function renderMessage( array $m )
    {
        $c      = self::content( $m );
        $layout = in_array( $m['layout'], array( 'bar', 'card', 'notice', 'tip' ), true ) ? $m['layout'] : 'bar';
        $theme  = $m['theme'];
        if ( '' === $theme ) {
            $defaults = array( 'bar' => 'brand', 'card' => 'sale', 'notice' => 'critical' === $m['type'] ? 'danger' : 'neutral', 'tip' => 'neutral' );
            $theme    = $defaults[ $layout ];
        }

        $close = '<button type="button" class="wpc-msg__close" data-wpc-dismiss="' . esc_attr( $m['id'] ) . '"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Dismiss this message', 'filter-everything' ) . '</span></button>';
        $count = $m['countdown'] ? self::countdown( $m['ends'] ) : '';
        $text  = isset( $c['text'] ) ? self::html( $c['text'] ) : '';
        $title = isset( $c['title'] ) ? esc_html( $c['title'] ) : '';
        $badge = isset( $c['badge'] ) ? '<span class="wpc-msg__badge">' . esc_html( $c['badge'] ) . '</span>' : '';

        echo '<div class="' . esc_attr( "wpc-msg wpc-msg--$layout wpc-msg--$theme wpc-msg--type-{$m['type']}" ) . '" data-wpc-msg="' . esc_attr( $m['id'] ) . '">';

        switch ( $layout ) {
            case 'card':
                echo '<div class="wpc-msg__strip">' . $badge;
                if ( $count ) {
                    echo '<span class="wpc-msg__strip-text">' . esc_html__( 'Ends in', 'filter-everything' ) . '</span>' . $count;
                }
                echo $close . '</div><div class="wpc-msg__body"><div class="wpc-msg__main">';
                echo $title ? '<h3>' . $title . '</h3>' : '';
                echo $text ? '<div class="wpc-msg__html">' . $text . '</div>' : '';
                if ( ! empty( $c['list'] ) ) {
                    echo '<ul class="wpc-msg__list">';
                    foreach ( $c['list'] as $li ) {
                        echo '<li><span class="dashicons dashicons-yes" aria-hidden="true"></span>' . esc_html( $li ) . '</li>';
                    }
                    echo '</ul>';
                }
                echo '</div>';
                if ( ! empty( $c['price'] ) || ! empty( $c['cta'] ) ) {
                    $p = isset( $c['price'] ) ? $c['price'] : array( 'was' => '', 'now' => '', 'off' => '', 'term' => '' );
                    echo '<div class="wpc-msg__offer">';
                    if ( '' !== $p['was'] || '' !== $p['off'] ) {
                        echo '<div class="wpc-msg__was"><s>' . esc_html( $p['was'] ) . '</s>' . ( '' !== $p['off'] ? '<span class="wpc-msg__pill">' . esc_html( $p['off'] ) . '</span>' : '' ) . '</div>';
                    }
                    echo '' !== $p['now'] ? '<div class="wpc-msg__now">' . esc_html( $p['now'] ) . '</div>' : '';
                    echo '' !== $p['term'] ? '<div class="wpc-msg__term">' . esc_html( $p['term'] ) . '</div>' : '';
                    echo self::cta( $c, 'button' ) . '</div>';
                }
                echo '</div>';
                break;

            case 'tip':
                echo '<span class="dashicons dashicons-lightbulb wpc-msg__icon" aria-hidden="true"></span><div class="wpc-msg__main">';
                echo isset( $c['label'] ) ? '<span class="wpc-msg__label">' . esc_html( $c['label'] ) . '</span>' : '';
                echo $title ? '<strong class="wpc-msg__title">' . $title . '</strong>' : '';
                echo '<div class="wpc-msg__html">' . $text . '</div>';
                echo isset( $c['cta'] ) ? '<p class="wpc-msg__actions">' . self::cta( $c, 'link' ) . '</p>' : '';
                echo '</div>' . $close;
                break;

            case 'notice':
                $icon = 'danger' === $theme ? 'shield-alt' : 'info';
                echo '<span class="dashicons dashicons-' . esc_attr( $icon ) . ' wpc-msg__icon" aria-hidden="true"></span><div class="wpc-msg__main">';
                echo $title ? '<strong class="wpc-msg__title">' . $title . '</strong>' : '';
                echo '<div class="wpc-msg__html">' . $text . '</div></div>' . self::cta( $c, 'link' ) . $close;
                break;

            default: // bar
                echo $badge . '<div class="wpc-msg__text">' . ( $title ? '<strong>' . $title . '</strong> ' : '' ) . $text . '</div>' . $count;
                // A button, except on the dark bar where a link reads better on the dark ground.
                echo self::cta( $c, 'dark' === $theme ? 'link' : 'button' ) . $close;
        }

        echo '</div>';
    }

    private static function cta( array $c, $style )
    {
        if ( empty( $c['cta'] ) ) {
            return '';
        }
        $link = MessageFeed::resolveUrl( $c['cta']['url'] );
        if ( ! $link ) {
            return '';
        }

        $class = 'wpc-msg__cta wpc-msg__cta--' . $style;
        $attrs = '';
        $hint  = '';
        if ( $link['external'] ) {
            $attrs = ' target="_blank" rel="noopener"';
            $hint  = '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'filter-everything' ) . '</span>';
            if ( 'link' === $style ) {
                $class .= ' wpc-external-link';
            }
        }

        return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $link['url'] ) . '"' . $attrs . '>' . esc_html( $c['cta']['label'] ) . $hint . '</a>';
    }

    public static function countdown( $ends )
    {
        // With a context: a bare "min" already exists in the plugin and means "minimum" (numeric range).
        $labels = array(
            'd' => _x( 'days', 'countdown unit', 'filter-everything' ),
            'h' => _x( 'hrs', 'countdown unit', 'filter-everything' ),
            'm' => _x( 'min', 'countdown unit', 'filter-everything' ),
            's' => _x( 'sec', 'countdown unit', 'filter-everything' ),
        );
        $left  = max( 0, $ends - time() );
        $parts = array(
            'd' => (string) floor( $left / DAY_IN_SECONDS ),
            'h' => sprintf( '%02d', floor( $left % DAY_IN_SECONDS / HOUR_IN_SECONDS ) ),
            'm' => sprintf( '%02d', floor( $left % HOUR_IN_SECONDS / MINUTE_IN_SECONDS ) ),
            's' => sprintf( '%02d', $left % MINUTE_IN_SECONDS ),
        );

        $html = '<span class="wpc-msg__count" data-wpc-end="' . (int) $ends . '">';
        foreach ( $parts as $u => $v ) {
            $html .= '<span><b data-u="' . $u . '">' . esc_html( $v ) . '</b><i>' . esc_html( $labels[ $u ] ) . '</i></span>';
        }

        return $html . '</span>';
    }

    /**
     * Feed HTML → safe HTML. The whole trust boundary of the channel.
     */
    public static function html( $html )
    {
        $allowed = array(
            'h3' => array(), 'h4' => array(), 'p' => array(), 'br' => array(),
            'strong' => array(), 'em' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
            'a' => array( 'href' => true ), 'span' => array( 'class' => true ),
        );

        // `admin:` must survive wp_kses' protocol check to reach resolveUrl().
        $out = wp_kses( (string) $html, $allowed, array( 'https', 'admin' ) );

        $out = preg_replace_callback( '/<span class="([^"]*)">/', function ( $m ) {
            $keep = array_intersect( preg_split( '/\s+/', trim( $m[1] ) ), self::ALLOWED_CLASSES );
            return $keep ? '<span class="' . esc_attr( implode( ' ', $keep ) ) . '">' : '<span>';
        }, $out );

        $out = preg_replace_callback( '/<a href="([^"]*)">(.*?)<\/a>/s', function ( $m ) {
            $link = MessageFeed::resolveUrl( html_entity_decode( $m[1], ENT_QUOTES ) );
            if ( ! $link ) {
                return $m[2]; // a foreign link keeps its text and loses the <a>
            }
            if ( ! $link['external'] ) {
                return '<a href="' . esc_url( $link['url'] ) . '">' . $m[2] . '</a>';
            }
            return '<a href="' . esc_url( $link['url'] ) . '" class="wpc-external-link" target="_blank" rel="noopener">' . $m[2]
                . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'filter-everything' ) . '</span></a>';
        }, $out );

        // Any <a> the pattern above did not rewrite (odd attribute order, etc.) is not trusted.
        return preg_replace( '/<a (?!href="[^"]*"( class="wpc-external-link" target="_blank" rel="noopener")?>)[^>]*>/', '', $out );
    }

    /* ---------------------------------------------------------------------
     * AJAX
     * ------------------------------------------------------------------- */

    private static function guardAjax()
    {
        check_ajax_referer( self::NONCE, 'nonce' );

        if ( ! current_user_can( flrt_plugin_user_caps() ) ) {
            wp_send_json_error( null, 403 );
        }
    }

    public function ajaxDismiss()
    {
        self::guardAjax();

        $id = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
        if ( ! preg_match( '/^[a-z0-9][a-z0-9_-]{2,63}$/', $id ) ) {
            wp_send_json_error( null, 400 );
        }

        $all  = self::messages();
        $type = isset( $all[ $id ] ) ? $all[ $id ]['type'] : '';

        $user_id   = get_current_user_id();
        $dismissed = self::dismissed( $user_id );
        $dismissed[ $id ] = array( 't' => time(), 'type' => $type );

        // Bounded: ids of long-gone messages are of no use.
        if ( count( $dismissed ) > 60 ) {
            uasort( $dismissed, function ( $a, $b ) {
                return $b['t'] - $a['t'];
            } );
            $dismissed = array_slice( $dismissed, 0, 60, true );
        }

        update_user_meta( $user_id, self::DISMISSED_META, $dismissed );
        wp_send_json_success();
    }

    public function ajaxConsent()
    {
        self::guardAjax();

        $answer = isset( $_POST['answer'] ) ? sanitize_key( wp_unslash( $_POST['answer'] ) ) : '';
        if ( ! in_array( $answer, array( 'yes', 'no' ), true ) ) {
            wp_send_json_error( null, 400 );
        }

        self::setConsent( 'yes' === $answer );
        wp_send_json_success();
    }
}
