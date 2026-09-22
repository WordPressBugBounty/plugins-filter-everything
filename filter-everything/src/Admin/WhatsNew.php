<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * «What's new» — release notes inside the admin, plus the badge that points to them.
 *
 * The problem it solves: after an update (increasingly an automatic one) nobody
 * opens the plugin settings to discover what changed, yet a redirect or a modal
 * would be obnoxious. So:
 *
 *   - a «What's new» tab on the Settings page (the last one) renders the
 *     release notes of the INSTALLED version straight from the bundled
 *     readme.txt changelog — zero extra maintenance, no remote calls, always in
 *     sync with what actually shipped; earlier releases are listed below,
 *     collapsed. It used to be a submenu entry (1.9.6); it moved into Settings
 *     so that the Filters menu stays short with Import/Export back in it;
 *   - after an in-place update the WP-native red «1» badge sits on the Filters
 *     menu item (collapsed menu), on «Settings» once the menu is open, and on
 *     the «What's new» tab inside Settings, until THIS admin opens the tab — it
 *     is visible from every admin screen but demands nothing, and it stops on
 *     its own after self::BADGE_TTL. Fresh installs never see it: there is
 *     nothing to catch up on. An unread plugin notification lights the same
 *     badge (Messages::hasUnseen()).
 *
 * "Seen" is per user (user meta — each admin gets one quiet nudge), "updated"
 * is per site (AdminNotices' update stamp). Both builds, free and PRO.
 *
 * Complements, not replaces, the one-off AdminNotices entries: those are for
 * releases that change behaviour; the badge is the baseline for every release.
 */
class WhatsNew implements TabInterface
{
    /** Settings tab name (?page=filters-settings&tab=…). */
    const TAB = 'whats_new';

    /** Former submenu slug (?page=…), kept so old links land on the tab. */
    const PAGE_SLUG = 'filters-whats-new';

    /** Per-user: the last version whose release notes this admin has opened. */
    const USER_META = 'flrt_whats_new_seen';

    /** The badge gives up on its own after this long since the update. */
    const BADGE_TTL = 30 * DAY_IN_SECONDS;

    /** The complete, always-current changelog lives on the site. */
    const CHANGELOG_URL = 'https://filtereverything.pro/changelog/';

    public function __construct()
    {
        if ( ! is_admin() ) {
            return;
        }

        // Last Settings tab, after License (PRO) / PRO benefits (free)
        add_action( 'wpc_settings_tabs_registered', [ $this, 'registerTab' ] );
        // Runs after every submenu exists
        add_action( 'admin_menu', [ $this, 'decorateMenu' ], 99 );
        // Bookmarked / old links to the former submenu page
        // The old page is no longer registered, so WordPress denies it in
        // wp-admin/menu.php before admin_init ever runs; this hook fires right
        // before that 403 and is the only place a redirect can still happen.
        add_action( 'admin_page_access_denied', [ $this, 'redirectOldPage' ] );
    }

    /* ----------------------------------------------------------------- TabInterface */

    public function registerTab( TabRenderer $renderer )
    {
        $renderer->register( $this );
    }

    public function init()
    {
    }

    public function getName()
    {
        return self::TAB;
    }

    public function getLabel()
    {
        return esc_html__( "What's new", 'filter-everything' );
    }

    public function valid()
    {
        return true;
    }

    /** The unread badge on the tab itself (options.php prints it after the label): same count as the menu. */
    public function labelBadge()
    {
        if ( self::isWhatsNewPage() ) {
            return '';
        }
        list( $count, $label ) = $this->unread();

        return $count ? self::badgeHtml( $count, $label ) : '';
    }

    /**
     * What this admin has not seen yet: release notes and/or a plugin
     * notification (Messages::hasUnseen()).
     *
     * @return array{0:int,1:string} count and screen-reader label
     */
    public function unread()
    {
        $notes   = $this->badgeDue();
        $message = class_exists( __NAMESPACE__ . '\\Messages' ) && Messages::hasUnseen();
        $label   = $notes && $message
            ? esc_html__( 'New release notes and a new notification', 'filter-everything' )
            : ( $notes ? esc_html__( 'New release notes available', 'filter-everything' ) : esc_html__( 'New notification', 'filter-everything' ) );

        return array( (int) $notes + (int) $message, $label );
    }

    public function redirectOldPage()
    {
        if ( isset( $_GET['page'] ) && sanitize_key( wp_unslash( $_GET['page'] ) ) === self::PAGE_SLUG ) {
            wp_safe_redirect( self::pageUrl() );
            exit;
        }
    }

    public static function parentSlug()
    {
        return 'edit.php?post_type=' . FLRT_FILTERS_SET_POST_TYPE;
    }

    public static function pageUrl()
    {
        return admin_url( self::parentSlug() . '&page=filters-settings&tab=' . self::TAB );
    }

    /** Version as it appears in the changelog: 1.9.6-dev → 1.9.6 */
    public static function baseVersion()
    {
        return preg_replace( '/[-+].*$/', '', FLRT_PLUGIN_VER );
    }

    public static function isWhatsNewPage()
    {
        return isset( $_GET['page'], $_GET['tab'] )
            && sanitize_key( wp_unslash( $_GET['page'] ) ) === 'filters-settings'
            && sanitize_key( wp_unslash( $_GET['tab'] ) ) === self::TAB;
    }

    public static function badgeHtml( $count, $label )
    {
        return ' <span class="update-plugins count-' . (int) $count . ' flrt-whats-new-badge"><span class="plugin-count" aria-hidden="true">' . (int) $count . '</span>'
             . '<span class="screen-reader-text">' . $label . '</span></span>';
    }

    /**
     * Appends the badge to the Filters menu item and to the «Settings» entry
     * while this admin has unread release notes and/or an unread plugin
     * notification (Messages::hasUnseen()). Inside Settings the same count
     * sits on the «What's new» tab (labelBadge()). Opening the tab marks the
     * notes read; the notification counts as read once it has been shown on
     * any of the plugin's screens — the tab included, so one click clears both.
     */
    public function decorateMenu()
    {
        if ( ! current_user_can( flrt_plugin_user_caps() ) ) {
            return;
        }

        if ( self::isWhatsNewPage() ) {
            $this->markSeen();
            return;
        }

        list( $count, $label ) = $this->unread();

        if ( ! $count ) {
            return;
        }

        global $menu, $submenu;

        $parent = self::parentSlug();
        $badge  = self::badgeHtml( $count, $label );

        foreach ( (array) $menu as $i => $item ) {
            if ( isset( $item[2] ) && $item[2] === $parent ) {
                $menu[ $i ][0] .= $badge;
                break;
            }
        }

        if ( isset( $submenu[ $parent ] ) ) {
            foreach ( $submenu[ $parent ] as $i => $item ) {
                if ( isset( $item[2] ) && $item[2] === 'filters-settings' ) {
                    $submenu[ $parent ][ $i ][0] .= $badge;
                    break;
                }
            }
        }
    }

    /**
     * Unread notes exist when the site was updated in place to the running
     * version (never on a fresh install), the update is recent enough, and this
     * admin has not opened the page for this version yet.
     */
    public function badgeDue()
    {
        if ( get_option( AdminNotices::UPDATED_OPTION ) !== FLRT_PLUGIN_VER ) {
            return false;
        }

        $since = (int) get_option( AdminNotices::UPDATED_AT_OPTION, 0 );
        if ( $since && ( time() - $since ) > self::BADGE_TTL ) {
            return false;
        }

        return get_user_meta( get_current_user_id(), self::USER_META, true ) !== FLRT_PLUGIN_VER;
    }

    public function markSeen()
    {
        if ( get_user_meta( get_current_user_id(), self::USER_META, true ) !== FLRT_PLUGIN_VER ) {
            update_user_meta( get_current_user_id(), self::USER_META, FLRT_PLUGIN_VER );
        }
    }

    public function render()
    {
        if ( ! current_user_can( flrt_plugin_user_caps() ) ) {
            return '';
        }

        $releases = self::changelog();
        $version  = self::baseVersion();
        $current  = null;

        if ( isset( $releases[ $version ] ) ) {
            $current = $releases[ $version ];
        } elseif ( ! empty( $releases ) ) {
            // A dev/hotfix build without its own block yet: show the newest notes
            $version = (string) array_key_first( $releases );
            $current = $releases[ $version ];
        }

        $earlier = $releases;
        unset( $earlier[ $version ] );

        ob_start();
        flrt_include_admin_view( 'whats-new', [
            'version'       => $version,
            'current'       => $current,
            'earlier'       => $earlier,
            'changelog_url' => self::CHANGELOG_URL,
        ] );

        return ob_get_clean();
    }

    /**
     * Parses the bundled readme.txt changelog.
     *
     * Understands the format the project has used for years:
     *   = 1.9.6 =
     *   *Release Date - 5 August 2026*
     *   * Fix   - Fixed …
     *   * Dev   - NEW: Added …
     *
     * @return array version => ['date' => string, 'items' => [ ['label' => 'Fix', 'new' => bool, 'text' => string], … ]]
     *               in readme order (newest first)
     */
    public static function changelog()
    {
        $file = FLRT_PLUGIN_DIR_PATH . 'readme.txt';
        if ( ! is_readable( $file ) ) {
            return [];
        }

        $text = (string) file_get_contents( $file );
        $pos  = stripos( $text, '== Changelog ==' );
        if ( $pos === false ) {
            return [];
        }
        $text = substr( $text, $pos + strlen( '== Changelog ==' ) );

        // Stop at the next top-level section, e.g. "== Upgrade Notice =="
        if ( preg_match( '/^==\s*[^=\r\n]+\s*==\s*$/m', $text, $m, PREG_OFFSET_CAPTURE ) ) {
            $text = substr( $text, 0, $m[0][1] );
        }

        $releases = [];
        $version  = null;

        foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $line ) {
            $line = trim( $line );
            if ( $line === '' ) {
                continue;
            }

            if ( preg_match( '/^=\s*([0-9][0-9A-Za-z.\-]*)\s*=$/', $line, $m ) ) {
                $version              = $m[1];
                $releases[ $version ] = [ 'date' => '', 'items' => [] ];
                continue;
            }

            if ( $version === null ) {
                continue;
            }

            if ( preg_match( '/^\*\s*Release Date\s*[-–—:]\s*(.+?)\s*\*$/i', $line, $m ) ) {
                $releases[ $version ]['date'] = $m[1];
                continue;
            }

            if ( preg_match( '/^[\*\-]\s*(?:([A-Za-z]+)\s+-\s+)?(.+)$/', $line, $m ) ) {
                $label  = $m[1];
                $body   = trim( $m[2] );
                $is_new = false;

                if ( preg_match( '/^NEW:\s*/i', $body ) ) {
                    $is_new = true;
                    $body   = preg_replace( '/^NEW:\s*/i', '', $body );
                }

                $releases[ $version ]['items'][] = [
                    'label' => $label,
                    'new'   => $is_new,
                    'text'  => $body,
                ];
            }
        }

        return $releases;
    }
}

new WhatsNew();
