<?php
/**
 * robots.txt helper — keeps well-behaved crawlers away from filtering result pages.
 *
 * Why this exists: filter combinations produce a practically unlimited number of
 * URLs, and bots (search engines, AI crawlers, scrapers) that once discover them
 * keep requesting them for months — even after the filter links are hidden or the
 * plugin is removed. Hiding the links (see «Disable filter links for crawlers»)
 * stops new discovery; the only signal that also stops compliant crawlers from
 * re-fetching URLs they already know is a robots.txt Disallow. That is Google's own
 * recommendation for faceted navigation: disallow the URL patterns rather than rely
 * on noindex (a noindex page still costs a full crawl request).
 *
 * Two URL schemes, two rule sets:
 *
 *  QUERY MODE (free build, or PRO with pretty permalinks off) — every filter URL is
 *  fully described by its parameter names (?color=red&min_price=10), so robots.txt
 *  can express "any URL carrying a filter parameter" precisely:
 *      Disallow: /*?color=
 *      Disallow: /*&color=
 *  (two patterns per parameter: first in the query string or after another one —
 *  deliberately NOT the looser /*?*color= form, which would also match unrelated
 *  parameters that merely end in the same word, e.g. ?product_color=).
 *
 *  PRETTY MODE (PRO) — filters travel as path segments (/shop/color-red/size-xl/)
 *  and SEO Rules may make some of those pages indexable, so the rules must only
 *  name the classes of URL the plugin never indexes (SeoFrontend::isNoindex()):
 *      - numeric/date ranges and the in-filter search term (still query params);
 *      - several values of one filter (…/color-red-or-blue/);
 *      - more filters in one URL than the highest Indexing Depth allows;
 *      - and, when no post type has an Indexing Depth at all (nothing is
 *        indexable), every filter segment outright.
 *  Single-filter pages stay crawlable otherwise (commented suggestions show how
 *  to block them too). Path patterns match a segment that STARTS with a filter
 *  prefix, so a product/category slug beginning with the same word would match
 *  as well — the Settings box shows the rules for review before anything is
 *  added.
 *
 * The generated block is
 *   - appended to WordPress' virtual /robots.txt while the option is on (the filter
 *     has no effect when a physical robots.txt file exists — WordPress never serves
 *     the virtual one then);
 *   - shown on Settings → General so it can be copied into a physical file.
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

if ( ! function_exists( 'flrt_robots_helper_available' ) ) {
    /**
     * Whether the robots.txt helper (option, rules box, robots_txt hook) is active
     * in this build. Both builds since 1.9.6; kept as a single switch/filter.
     *
     * @return bool
     */
    function flrt_robots_helper_available()
    {
        return (bool) apply_filters( 'wpc_robots_helper_available', true );
    }
}

if ( ! function_exists( 'flrt_robots_pretty_mode' ) ) {
    /**
     * True when filters travel as path segments (PRO with pretty permalinks).
     *
     * @return bool
     */
    function flrt_robots_pretty_mode()
    {
        return defined( 'FLRT_PERMALINKS_ENABLED' ) && FLRT_PERMALINKS_ENABLED;
    }
}

if ( ! function_exists( 'flrt_robots_registry' ) ) {
    /**
     * The URL-prefix registry (option wpc_filter_permalinks, 'entity#ename' => slug)
     * in URL order, as [ ['entity' => …, 'slug' => …], … ] with empty slugs dropped.
     *
     * @return array[]
     */
    function flrt_robots_registry()
    {
        $registry = get_option( 'wpc_filter_permalinks', [] );

        if ( ! is_array( $registry ) ) {
            $registry = maybe_unserialize( $registry );
        }
        if ( ! is_array( $registry ) ) {
            $registry = [];
        }

        $entries = [];

        foreach ( $registry as $entityAndEname => $slug ) {
            // Same normalisation as UrlManager::getParamName()
            $slug = sanitize_title( (string) $slug );
            if ( $slug === '' ) {
                continue;
            }
            $parts     = explode( '#', (string) $entityAndEname, 2 );
            $entries[] = [ 'entity' => $parts[0], 'slug' => $slug ];
        }

        return $entries;
    }
}

if ( ! function_exists( 'flrt_robots_filter_params' ) ) {
    /**
     * Query-string parameter names the plugin's filter URLs can carry.
     *
     * Built the same way UrlManager/RequestParser build and read them: numeric
     * entities travel as min_{slug}/max_{slug}, date entities as {slug}_from/{slug}_to
     * and — in query mode only — everything else as the bare slug. The in-filter
     * search term («srch») always travels with filter URLs too.
     *
     * @param bool|null $pretty_mode null = detect; true = only the parameters that
     *                               stay in the query string with pretty permalinks
     * @return string[] sorted, unique parameter names
     */
    function flrt_robots_filter_params( $pretty_mode = null )
    {
        if ( $pretty_mode === null ) {
            $pretty_mode = flrt_robots_pretty_mode();
        }

        $params = [];

        foreach ( flrt_robots_registry() as $entry ) {
            $slug = $entry['slug'];

            switch ( $entry['entity'] ) {
                case 'post_meta_num':
                case 'tax_numeric':
                    $params[] = 'min_' . $slug;
                    $params[] = 'max_' . $slug;
                    break;
                case 'post_date':
                case 'post_meta_date':
                    $params[] = $slug . '_from';
                    $params[] = $slug . '_to';
                    break;
                default:
                    if ( ! $pretty_mode ) {
                        $params[] = $slug;
                    }
            }
        }

        if ( ! empty( flrt_robots_registry() ) ) {
            $params[] = 'srch';
        }

        $params = array_values( array_unique( $params ) );
        sort( $params, SORT_STRING );

        return apply_filters( 'wpc_robots_filter_params', $params );
    }
}

if ( ! function_exists( 'flrt_robots_path_prefixes' ) ) {
    /**
     * Prefixes of the filters that travel as path segments, in URL order (the
     * registry order IS the canonical segment order — any other order 404s).
     *
     * @return string[]
     */
    function flrt_robots_path_prefixes()
    {
        $prefixes = [];

        foreach ( flrt_robots_registry() as $entry ) {
            if ( in_array( $entry['entity'], [ 'post_meta_num', 'tax_numeric', 'post_date', 'post_meta_date' ], true ) ) {
                continue;
            }
            $prefixes[] = $entry['slug'];
        }

        return array_values( array_unique( $prefixes ) );
    }
}

if ( ! function_exists( 'flrt_robots_indexing_depth' ) ) {
    /**
     * The highest Indexing Depth configured for any post type (PRO option
     * wpc_indexing_deep_settings), i.e. the most filters a single indexable URL
     * may carry anywhere on the site. 0 = no filter page is indexable.
     *
     * @return int
     */
    function flrt_robots_indexing_depth()
    {
        $depth   = 0;
        $options = get_option( 'wpc_indexing_deep_settings', [] );

        if ( is_array( $options ) ) {
            foreach ( $options as $value ) {
                $depth = max( $depth, (int) $value );
            }
        }

        return (int) apply_filters( 'wpc_robots_indexing_depth', $depth );
    }
}

if ( ! function_exists( 'flrt_robots_combinations' ) ) {
    /**
     * All ordered k-combinations of $items (order preserved), or null when there
     * would be more than $limit of them.
     *
     * @return array[]|null
     */
    function flrt_robots_combinations( array $items, $k, $limit )
    {
        $n = count( $items );
        if ( $k < 1 || $k > $n ) {
            return [];
        }

        // C(n, k) without overflow: stop counting once past the limit
        $count = 1;
        for ( $i = 1; $i <= $k; $i++ ) {
            $count = $count * ( $n - $k + $i ) / $i;
            if ( $count > $limit ) {
                return null;
            }
        }

        $result  = [];
        $indexes = range( 0, $k - 1 );

        while ( true ) {
            $combo = [];
            foreach ( $indexes as $i ) {
                $combo[] = $items[ $i ];
            }
            $result[] = $combo;

            // advance
            $i = $k - 1;
            while ( $i >= 0 && $indexes[ $i ] === $n - $k + $i ) {
                $i--;
            }
            if ( $i < 0 ) {
                break;
            }
            $indexes[ $i ]++;
            for ( $j = $i + 1; $j < $k; $j++ ) {
                $indexes[ $j ] = $indexes[ $j - 1 ] + 1;
            }
        }

        return $result;
    }
}

if ( ! function_exists( 'flrt_robots_txt_rules' ) ) {
    /**
     * The robots.txt block for the current filters, or '' when there are no
     * filters yet. A repeated "User-agent: *" group is fine: crawlers merge groups
     * that address the same user agent.
     *
     * @return string
     */
    function flrt_robots_txt_rules()
    {
        $pretty   = flrt_robots_pretty_mode();
        $params   = flrt_robots_filter_params( $pretty );
        $prefixes = $pretty ? flrt_robots_path_prefixes() : [];

        if ( empty( $params ) && empty( $prefixes ) ) {
            return '';
        }

        $lines   = [];
        $lines[] = '# Filter Everything: do not crawl filtering result pages';
        $lines[] = 'User-agent: *';

        // 1) Query-string parameters — in query mode that is every filter; with
        //    pretty permalinks only the ranges/dates/search that never index.
        foreach ( $params as $param ) {
            $lines[] = 'Disallow: /*?' . $param . '=';
            $lines[] = 'Disallow: /*&' . $param . '=';
        }

        // 2) Path segments (pretty permalinks)
        if ( $pretty && ! empty( $prefixes ) ) {
            $depth = flrt_robots_indexing_depth();

            if ( $depth < 1 ) {
                // Nothing on this site indexes filter pages: block every filter
                // segment. (A product or category slug that starts with one of
                // these prefixes would match the same pattern — review the list.)
                $lines[] = '# No Indexing Depth is set, so no filtering result page is indexable: block every filter segment';
                foreach ( $prefixes as $prefix ) {
                    $lines[] = 'Disallow: /*/' . $prefix . '-';
                }
            } else {
                // 2a) several values of one filter — never indexable
                $lines[] = '# Several values of one filter (…/color-red-or-blue/) are never indexable';
                foreach ( $prefixes as $prefix ) {
                    foreach ( [ '-or-', '-and-' ] as $separator ) {
                        $lines[] = 'Disallow: /*/' . $prefix . '-*' . $separator;
                    }
                }

                // 2b) more filters in one URL than the Indexing Depth allows
                $size   = $depth + 1;
                $combos = flrt_robots_combinations( $prefixes, $size, 300 );

                if ( $combos === null ) {
                    $lines[] = sprintf( '# URLs with more than %d filters are never indexable, but there are too many prefix combinations to list them here', $depth );
                } elseif ( ! empty( $combos ) ) {
                    $lines[] = sprintf( '# URLs with more than %d filters (your highest Indexing Depth) are never indexable', $depth );
                    foreach ( $combos as $combo ) {
                        $pattern = '';
                        foreach ( $combo as $prefix ) {
                            $pattern .= '/*/' . $prefix . '-';
                        }
                        // "/*/a-/*/b-" → "/*/a-*/b-": the wildcard between segments
                        $lines[] = 'Disallow: ' . str_replace( '-/*/', '-*/', $pattern );
                    }
                }

                // 2c) single-filter pages stay crawlable — they are the ones SEO
                //     Rules index; leave the opt-in as commented lines
                $lines[] = '# Single-filter pages stay crawlable because your SEO Rules may index them; to block them too, remove the # below';
                foreach ( $prefixes as $prefix ) {
                    $lines[] = '# Disallow: /*/' . $prefix . '-';
                }
            }
        }

        $rules = implode( "\n", $lines );

        return (string) apply_filters( 'wpc_robots_txt_rules', $rules, $params, $prefixes );
    }
}

if ( ! function_exists( 'flrt_robots_txt_is_physical' ) ) {
    /**
     * True when a physical robots.txt file exists in the WordPress root. WordPress
     * serves the virtual robots.txt (and runs the robots_txt filter) only when it
     * does not.
     *
     * @return bool
     */
    function flrt_robots_txt_is_physical()
    {
        return file_exists( ABSPATH . 'robots.txt' );
    }
}

if ( ! function_exists( 'flrt_robots_txt_append_rules' ) ) {
    /**
     * robots_txt filter callback: appends the filter rules to WordPress' virtual
     * robots.txt while the option is on.
     *
     * @param string $output robots.txt content so far
     * @param bool   $public blog_public option; when false WordPress already emits
     *                       "Disallow: /" for everyone and our rules are redundant
     * @return string
     */
    function flrt_robots_txt_append_rules( $output, $public )
    {
        if ( ! $public || ! flrt_robots_helper_available() ) {
            return $output;
        }

        if ( flrt_get_option( 'robots_txt_block_filters' ) !== 'on' ) {
            return $output;
        }

        $rules = flrt_robots_txt_rules();
        if ( $rules === '' ) {
            return $output;
        }

        return rtrim( (string) $output ) . "\n\n" . $rules . "\n";
    }
}

add_filter( 'robots_txt', 'flrt_robots_txt_append_rules', 20, 2 );
