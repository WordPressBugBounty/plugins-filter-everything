<?php
namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}
class WP_Query_Source_Detector
{
    public static string $builder_key = '_free_builder_source';
    public static $builders = [
        'elementor'      => [
            'classes'    => ['elementor-widget', 'elementor-element', 'elementor-posts', 'elementor-loop'],
            'data_attrs' => ['data-elementor-type', 'data-elementor-id'],
            'functions'  => ['elementor_pro_load_plugin'],
            'meta_keys'  => ['_elementor_edit_mode', '_elementor_data']
        ],
        'divi'           => [
            'classes'    => ['et_pb_module', 'et_pb_post', 'et_pb_blog_grid', 'et_pb_posts'],
            'data_attrs' => ['data-et-multi-view'],
            'functions'  => ['et_divi_fonts_url'],
            'meta_keys'  => ['_et_pb_use_builder']
        ],
        'avada'          => [
            'classes'       => ['fusion-posts-container', 'fusion-blog-layout', 'fusion-post-cards'],
            'data_attrs'    => ['data-fusion-query'],
            'classes_check' => ['Avada'],
            'meta_keys'     => ['_avada_builder']
        ],
        'bricks'         => [
            'classes'    => ['brxe-post', 'brxe-posts', 'bricks-posts-wrapper', 'brx-posts'],
            'data_attrs' => ['data-query-id'],
            'constants'  => ['BRICKS_VERSION'],
            'meta_keys'  => ['_bricks_page_content_2']
        ],
        'beaver_builder' => [
            'classes'       => ['fl-post-grid', 'fl-post-carousel', 'fl-module-post-grid', 'fl-post-feed'],
            'data_attrs'    => ['data-settings'],
            'classes_check' => ['FLBuilder'],
            'meta_keys'     => ['_fl_builder_enabled', '_fl_builder_data']
        ],
        'breakdance'     => [
            'classes'    => ['breakdance-post-loop', 'bde-post-loop', 'breakdance-posts'],
            'data_attrs' => ['data-breakdance-query'],
            'constants'  => ['BREAKDANCE_VERSION'],
            'meta_keys'  => ['_breakdance_data']
        ],
        'brizy'          => [
            'classes'       => ['brz-posts', 'brz-posts-container', 'brz-wp-shortcode__posts', 'brz-posts__wrapper'],
            'data_attrs'    => ['data-brz-query', 'data-brz'],
            'constants'     => ['BRIZY_VERSION', 'BRIZY_EDITOR_VERSION'],
            'classes_check' => ['Brizy_Editor'],
            'meta_keys'     => ['brizy-post']
        ]
    ];

    /**
     * Identifies the source of the query
     *
     * @param WP_Query|null $query
     * @param string $content
     * @return string
     */
    public static function identify_query_source($query = null, $content = '')
    {
        global $wp_query;
        global $post;



        if (!$query) {
            $query = $wp_query;
        }

        if(empty($content) && isset($post->post_content)){
            $content = $post->post_content;
        }

        if ($query && $query->is_main_query() && $query === $wp_query) {
            if (is_singular()) {
                $page_builder = self::detect_page_builder_for_singular($query, $content);
                if ($page_builder) {
                    return $page_builder;
                }
            }
            return 'main_query';
        }

        if (self::is_gutenberg_query($query, $content)) {
            return 'gutenberg';
        }

        if (!empty($content)) {
            $builder_from_html = self::detect_builder_from_html($content);
            if ($builder_from_html) {
                return $builder_from_html;
            }
        }

        $is_shortcode = self::is_shortcode_query();
        if ($is_shortcode) {
            $builder_from_trace = self::detect_builder_from_backtrace();
            if ($builder_from_trace) {
                return $builder_from_trace;
            }
            return 'shortcode';
        }



        $builder_from_trace = self::detect_builder_from_backtrace();
        if ($builder_from_trace) {
            return $builder_from_trace;
        }


        return 'custom';
    }

    /**
     * Detects which page builder is used for a singular page
     *
     * @param int|null $post_id
     * @return string|false
     */
    public static function detect_page_builder_for_singular($query,  $content = '', $post_id = null)
    {
        global $post;

        // If no ID is passed - we take the current post
        if (!$post_id && isset($post->ID)) {
            $post_id = $post->ID;
        }

        if (!$post_id) {
            return false;
        }

        // Only for singular pages
        if (!is_singular()) {
            return false;
        }

        if(self::is_gutenberg_query($query, $content)){
            return 'gutenberg';
        }

        // Method 1: Check via post meta
        foreach (self::$builders as $builder => $signatures) {
            if (isset($signatures['meta_keys'])) {
                foreach ($signatures['meta_keys'] as $meta_key) {
                    $meta_value = get_post_meta($post_id, $meta_key, true);

                    if (!empty($meta_value)) {
                        // Additional validation for specific builders
                        if ($builder === 'elementor' && $meta_value === 'builder') {
                            return 'elementor';
                        } elseif ($builder === 'divi' && $meta_value === 'on') {
                            return 'divi';
                        } elseif ($builder === 'beaver_builder' && $meta_value === '1') {
                            return 'beaver_builder';
                        } elseif (in_array($builder, ['bricks', 'breakdance', 'brizy', 'avada'])) {
                            return $builder;
                        }
                    }
                }
            }
        }

        // Method 2: Check via content
        $content = get_post_field('post_content', $post_id);
        if (!empty($content)) {
            $builder_from_html = self::detect_builder_from_html($content);
            if ($builder_from_html) {
                return $builder_from_html;
            }
        }

        $is_shortcode = self::is_shortcode_query();
        if ($is_shortcode) {
            $builder_from_trace = self::detect_builder_from_backtrace();
            if ($builder_from_trace) {
                return $builder_from_trace;
            }
            return 'shortcode';
        }

        return false;
    }

    /**
     * Checks if this is a Gutenberg Query Loop
     *
     * @param WP_Query|null $query
     * @param string $content
     * @return bool
     */
    private static function is_gutenberg_query($query, $content = '')
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 25);

        foreach ($backtrace as $trace) {
            $file = isset($trace['file']) ? $trace['file'] : '';
            $function = isset($trace['function']) ? $trace['function'] : '';

            if (strpos($file, 'wp-includes/blocks/query.php') !== false ||
                strpos($file, 'wp-includes/blocks/post-template.php') !== false ||
                strpos($file, 'wp-includes/blocks/query-pagination.php') !== false ||
                strpos($file, 'wp-includes/blocks/query-pagination-next.php') !== false ||
                strpos($file, 'wp-includes/blocks/query-pagination-previous.php') !== false ||
                strpos($file, 'wp-includes/blocks/query-pagination-numbers.php') !== false) {
                return true;
            }

            if ($function === 'render_block_core_query' ||
                $function === 'render_block_core_post_template' ||
                $function === 'render_block_core_query_pagination' ||
                $function === 'render_block_core_query_pagination_next' ||
                $function === 'render_block_core_query_pagination_previous' ||
                $function === 'render_block_core_query_pagination_numbers') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if this query is triggered by a shortcode
     *
     * @return bool
     */
    private static function is_shortcode_query(): bool
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);

        $has_shortcode_in_trace = false;
        $has_wp_query_in_trace = false;
        $shortcode_depth = null;
        $wp_query_depth = null;

        foreach ($backtrace as $index => $trace) {
            $function = isset($trace['function']) ? $trace['function'] : '';
            $file = isset($trace['file']) ? strtolower($trace['file']) : '';

            // Check for shortcode functions
            if (in_array($function, ['do_shortcode', 'do_shortcode_tag', 'apply_shortcodes'], true)) {
                $has_shortcode_in_trace = true;
                if ($shortcode_depth === null) {
                    $shortcode_depth = $index;
                }
            }

            // Check for shortcode file
            if (strpos($file, 'wp-includes/shortcodes.php') !== false) {
                $has_shortcode_in_trace = true;
                if ($shortcode_depth === null) {
                    $shortcode_depth = $index;
                }
            }

            // Check for WP_Query
            if ($function === 'get_posts' || strpos($function, 'wp_query') !== false) {
                $has_wp_query_in_trace = true;
                if ($wp_query_depth === null) {
                    $wp_query_depth = $index;
                }
            }

            // Check for WP_Query class
            if (isset($trace['class']) && $trace['class'] === 'WP_Query') {
                $has_wp_query_in_trace = true;
                if ($wp_query_depth === null) {
                    $wp_query_depth = $index;
                }
            }
        }

        // Shortcode must be higher in the stack (lower index) than WP_Query
        // This ensures WP_Query is called FROM WITHIN the shortcode
        return $has_shortcode_in_trace &&
            $has_wp_query_in_trace &&
            $shortcode_depth !== null &&
            $wp_query_depth !== null &&
            $shortcode_depth < $wp_query_depth;
    }

    /**
     * Detects builder via backtrace analysis
     *
     * @return string|null
     */
    private static function detect_builder_from_backtrace()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        foreach ($backtrace as $trace) {
            $file = isset($trace['file']) ? strtolower($trace['file']) : '';

            if (empty($file)) {
                continue;
            }

            // Check for Elementor
            if (strpos($file, 'elementor') !== false) {
                return 'elementor';
            }

            // Check for Divi
            if (strpos($file, 'divi') !== false) {
                return 'divi';
            }

            // Check for Bricks
            if (strpos($file, 'bricks') !== false) {
                return 'bricks';
            }

            // Check for Beaver Builder
            if (strpos($file, 'beaver-builder') !== false || strpos($file, 'fl-builder') !== false) {
                return 'beaver_builder';
            }

            // Check for Breakdance
            if (strpos($file, 'breakdance') !== false) {
                return 'breakdance';
            }

            // Check for Avada
            if (strpos($file, 'avada') !== false || strpos($file, 'fusion-') !== false) {
                return 'avada';
            }

            // Check for Brizy
            if (strpos($file, 'brizy') !== false) {
                return 'brizy';
            }
        }

        return null;
    }

    /**
     * Detects builder through HTML markup analysis
     *
     * @param string $html
     * @return string|null
     */
    private static function detect_builder_from_html($html)
    {
        foreach (self::$builders as $builder => $signatures) {
            // Check for builder-specific classes
            if (isset($signatures['classes'])) {
                foreach ($signatures['classes'] as $class) {
                    if (strpos($html, $class) !== false) {
                        return $builder;
                    }
                }
            }

            // Check for builder-specific data attributes
            if (isset($signatures['data_attrs'])) {
                foreach ($signatures['data_attrs'] as $attr) {
                    if (strpos($html, $attr) !== false) {
                        return $attr;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Gets information about active page builders
     *
     * @return array
     */
    public static function get_active_builder_info()
    {
        $active = [];

        foreach (self::$builders as $builder => $signatures) {
            // Check for builder constants
            if (isset($signatures['constants'])) {
                foreach ($signatures['constants'] as $constant) {
                    if (defined($constant)) {
                        $active[$builder] = constant($constant);
                    }
                }
            }

            // Check for builder functions
            if (isset($signatures['functions'])) {
                foreach ($signatures['functions'] as $function) {
                    if (function_exists($function)) {
                        $active[$builder] = true;
                    }
                }
            }

            // Check for builder classes
            if (isset($signatures['classes_check'])) {
                foreach ($signatures['classes_check'] as $class) {
                    if (class_exists($class)) {
                        $active[$builder] = true;
                    }
                }
            }
        }
        return $active;
    }

    /**
     * Gets human-readable name for a builder
     *
     * @param string $builder_name
     * @return string
     */
    public static function get_query_builder_name($builder_name) : string
    {
        if (empty($builder_name)){
            return '';
        }
        $available_in_pro = '';
        if( ! defined('FLRT_FILTERS_PRO') ) {
            $available_in_pro = " (" . esc_html__('Available in PRO', 'filter-everything') . ")";
        }
        $query_string = ' - ';
        switch ($builder_name) {
            case 'main_query':
                $query_string = '';
                break;
            case 'gutenberg':
                $query_string .= 'Gutenberg';
                break;
            case 'elementor':
                $query_string .= 'Elementor' . $available_in_pro;
                break;
            case 'divi':
                $query_string .= 'Divi Builder' . $available_in_pro;
                break;
            case 'avada':
                $query_string .= 'Avada' . $available_in_pro;
                break;
            case 'bricks':
                $query_string .= 'Bricks' . $available_in_pro;
                break;
            case 'beaver_builder':
                $query_string .= 'Beaver builder' . $available_in_pro;
                break;
            case 'breakdance':
                $query_string .= 'Breakdance' . $available_in_pro;
                break;
            case 'brizy':
                $query_string .= 'Brizy' . $available_in_pro;
                break;
            default:
                $query_string .= 'Custom query' . $available_in_pro;
                break;
        }

        return $query_string;
    }

    /**
     * Checks if a builder is allowed
     *
     * @param string $builder
     * @return bool
     */
    public static function is_allowed($builder): bool
    {
        $allowed = apply_filters('wpc_get_broken_builders', []);

        if(in_array($builder, $allowed)){
            return true;
        }

        return false;
    }
}