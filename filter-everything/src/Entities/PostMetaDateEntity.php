<?php

namespace FilterEverything\Filter;

if (!defined('ABSPATH')) {
    exit;
}

use DateTime;
use DateTimeImmutable;
use DateTimeZone;

class PostMetaDateEntity extends PostDateEntity
{
    use PostMetaTrait;

    private $new_meta_query = [];

    private $date_format;

    private $meta_date_format;

    private $meta_date_type;

    private $doRecalculate = '';

    private $post_and_types = [];

    public function __construct($name, $postType)
    {

        /**
         * @feature clean code from unused methods
         */
        // This object is being created two times
        // One time without post type when RequestParser select all filter terms
        // Second time with post type when Filters widget requires filter terms to display them
        $this->entityName = $name;
        $this->setPostTypes(array($postType));
        $this->setFromAndTo();
        if (empty($this->meta_date_type)) {
            $this->meta_date_type = $this->detectMetaTypeForKey($this->entityName, $postType);
        }
        $this->getAllExistingTerms();
    }

    protected function queryTerms($alreadyFilteredPosts = [])
    {
        global $wpdb;
        $IN = false;
        $translatable_post_type_exists = false;
        $lang = '';
        $key_in = '';

        /**
         * Check if any post type is translatable
         */

        if (flrt_wpml_active() && defined('ICL_LANGUAGE_CODE')) {
            $lang = ICL_LANGUAGE_CODE;
            $wpml_settings = get_option('icl_sitepress_settings');

            foreach ($this->postTypes as $type) {
                if (isset($wpml_settings['custom_posts_sync_option'][$type])) {
                    if ($wpml_settings['custom_posts_sync_option'][$type] === '1') {
                        $translatable_post_type_exists = true;
                        break;
                    }
                }
            }
        }

        /**
         * Set Post types
         */
        if (!empty($this->postTypes) && isset($this->postTypes[0]) && $this->postTypes[0]) {
            foreach ($this->postTypes as $postType) {
                $key_in .= '_' . $postType;
                $pieces[] = $wpdb->prepare("%s", $postType);
            }

            $IN = implode(", ", $pieces);
        }

        /**
         * Set transient key
         */
        $e_name = wp_unslash($this->entityName);
        $transient_key = flrt_get_terms_transient_key( 'post_meta_date_'. $this->getName() . $key_in );

        if (false === ($result = flrt_get_transient($transient_key))) {
            // Get all post meta values

            $sql[] = "SELECT {$wpdb->posts}.ID,{$wpdb->postmeta}.meta_value as post_date, {$wpdb->posts}.post_type";
            $sql[] = "FROM {$wpdb->posts}";
            $sql[] = "LEFT JOIN {$wpdb->postmeta} ON ({$wpdb->postmeta}.post_id = {$wpdb->posts}.ID)";

            /**
             * If post type is translatable with WPML, get post meta values only with current language
             */
            if (flrt_wpml_active() && $lang && $translatable_post_type_exists) {
                $sql[] = "LEFT JOIN {$wpdb->prefix}icl_translations AS wpml_translations";
                $sql[] = "ON {$wpdb->posts}.ID = wpml_translations.element_id";

                if (!empty($this->postTypes)) {

                    $sql[] = "AND wpml_translations.element_type IN(";

                    foreach ($this->postTypes as $type) {
                        $LANG_IN[] = $wpdb->prepare("CONCAT('post_', '%s')", $type);
                    }
                    $sql[] = implode(",", $LANG_IN);

                    $sql[] = ")";
                }
            }

            $e_name     = wp_unslash( $this->entityName );
            $sql[] = $wpdb->prepare("WHERE {$wpdb->postmeta}.meta_key = %s", $e_name);
            $sql[] = "AND {$wpdb->postmeta}.meta_value IS NOT NULL";
            $sql[] = "AND {$wpdb->postmeta}.meta_value <> ''";

            if (!empty($alreadyFilteredPosts)) {
                //$sql[] = "AND {$wpdb->postmeta}.post_id IN( ".implode(",",$alreadyFilteredPosts).")";
            }

            if ($IN) {
                $sql[] = "AND {$wpdb->posts}.post_type IN( {$IN} )";
            }

            if (flrt_wpml_active() && $lang && $translatable_post_type_exists) {
                $sql[] = $wpdb->prepare("AND wpml_translations.language_code = %s", $lang);
            }

            /**
             * Filters terms SQL-query and allows to modify it
             */
            $sql = apply_filters('wpc_filter_get_meta_date_terms_sql', $sql, $this->postTypes);

            $sql = implode(' ', $sql);

            $result = $wpdb->get_results($sql, ARRAY_A);
            if ($this->date_type !== $this->meta_date_type) {
                if (!in_array($this->meta_date_type, ['TIME', 'DATETIME', 'DATE'])) {
                    foreach ($result as $key => $value) {
                        if (in_array($this->meta_date_format, ['U', 'Ums'])) {
                            $val = $value['post_date'];
                            if ($this->meta_date_format === 'Ums') {
                                $val = (int)floor((int)$value['post_date'] / 1000);
                            }
                            $temp_date = (new \DateTime())->setTimestamp($val);
                            $temp_date = $temp_date->format('Y-m-d H:i:s');
                        } else {
                            $temp_date = \DateTime::createFromFormat($this->meta_date_format, $value['post_date']);
                            if ($temp_date !== false) {
                                $temp_date = $temp_date->format('Y-m-d H:i:s');
                            }
                        }

                        if ($temp_date != false) {
                            $result[$key]['post_date'] = $temp_date;
                        } else {
                            unset($result[$key]);
                        }

                    }
                }
                if ($this->meta_date_type === 'TIME') {
                    foreach ($result as $key => $value) {
                        $result[$key]['post_date'] = $this->timeToTodayDateTime($value['post_date']);
                    }
                }
            }

            flrt_set_transient($transient_key, $result, FLRT_TRANSIENT_PERIOD_HOURS * HOUR_IN_SECONDS);
        }

        return $result;
    }

    protected function setFromAndTo()
    {
        $wpManager = Container::instance()->getWpManager();
        $queried_values = $wpManager->getQueryVar('queried_values', []);
        $filter_slug = false;

        /**
         * Check if this filter was queried
         */
        foreach ($queried_values as $slug => $filter) {
            /**
             * At this point we do not know what exactly filter was queired
             * because filters with the same slug can be in multiple Filter Sets with
             * different date_type values.
             */
            if ($filter['e_name'] === $this->getName()) {
                $filter_slug = $slug;
                break;
            }
        }

        /**
         * If this filter was queried we have to receive its $from and $to values
         */
        if ($filter_slug) {
            if (isset($queried_values[$filter_slug]['values']['to']) && $this->to === false) {
                $to = str_replace('.', ':', $queried_values[$filter_slug]['values']['to']);

                $this->date_type = $this->guessMetaTypeFromValue($to, false, true);

                $this->to = $this->time_to = apply_filters('wpc_unset_date_shift', $to, $this->getName(), $this->date_type);
                if ($this->date_type === 'TIME') {
                    $this->time_to = $this->to;
                }
            }

            if (isset($queried_values[$filter_slug]['values']['from']) && $this->from === false) {
                $from = str_replace('.', ':', $queried_values[$filter_slug]['values']['from']);
                $this->date_type = $this->guessMetaTypeFromValue($from, false, true);
                $this->from = apply_filters('wpc_unset_date_shift', $from, $this->getName(), $this->date_type);
                if ($this->date_type === 'TIME') {
                    $this->time_from = $this->from;
                }
            }
        }
    }

    public function addTermsToWpQuery($queried_value, $wp_query)
    {
        $meta_query = [];
        $key = $queried_value['e_name'];
        $possible_date_query = $wp_query->get( 'date_query' );

        if( ! empty( $possible_date_query ) ) {
            $this->new_date_query = $possible_date_query;
        }

        // Add existing Meta Query if present
        $this->importExistingMetaQuery( $wp_query );

        if (strtolower((string)$this->date_type) !== 'time' && $this->date_type !== $this->meta_date_type) {
            $tz = wp_timezone();
            if ($this->meta_date_format === 'U' || $this->meta_date_format === 'Ums') {
                if ($this->from !== false) {
                    $dt = \DateTimeImmutable::createFromFormat($this->date_format, $this->from, $tz);
                    $this->from = $dt ? $dt->getTimestamp() : false;
                }
                if ($this->to !== false) {
                    $dt = \DateTimeImmutable::createFromFormat($this->date_format, $this->to, $tz);
                    $this->to = $dt ? $dt->getTimestamp() : false;
                }
                if ($this->from !== false || $this->to !== false) {
                    $this->date_type = $this->meta_date_type;
                }
            }
        }

        if (strtolower((string)$this->date_type) === 'time' && in_array($this->meta_date_format, ['U', 'Ums'], true)) {

            $normalizeTime = function ($t) {
                if ($t === false || $t === null || $t === '') return false;
                $t = str_replace('.', ':', $t);
                if (preg_match('/^\d{1,2}$/', $t)) $t .= ':00:00';
                elseif (preg_match('/^\d{1,2}:\d{2}$/', $t)) $t .= ':00';
                return $t;
            };
            $tFrom = $normalizeTime($this->from);
            $tTo = $normalizeTime($this->to);

            if ($tFrom !== false || $tTo !== false) {
                if ($tFrom === false) {
                    $tFrom = '00:00:00';
                }
                if ($tTo === false) {
                    $tTo = '24:00:00';
                }

                $wp_query->set('time_meta_key', $key);
                $wp_query->set('time_between', [$tFrom, $tTo]);
                $wp_query->set('time_is_ms', $this->meta_date_format === 'Ums');

                static $wpc_time_where_added = false;
                if (!$wpc_time_where_added) {
                    add_filter('posts_where', function ($where, $q) {
                        global $wpdb;
                        $timeBetween = $q->get('time_between');
                        $metaKey = $q->get('time_meta_key');

                        if (!$timeBetween || !$metaKey) {
                            return $where;
                        }

                        [$tFromL, $tToL] = $timeBetween;
                        $tzString = wp_timezone_string() ?: 'UTC';
                        $isMs = (bool)$q->get('time_is_ms');

                        $fieldExpr = $isMs
                            ? 'CAST(pm.meta_value AS UNSIGNED) / 1000'
                            : 'CAST(pm.meta_value AS UNSIGNED)';

                        $where .= $wpdb->prepare(
                            " AND EXISTS (
                                SELECT 1
                                FROM {$wpdb->postmeta} pm
                                WHERE pm.post_id = {$wpdb->posts}.ID
                                  AND pm.meta_key = %s
                                  AND TIME(CONVERT_TZ(FROM_UNIXTIME($fieldExpr), 'UTC', %s)) >= %s
                                  AND TIME(CONVERT_TZ(FROM_UNIXTIME($fieldExpr), 'UTC', %s)) < %s
                            )",
                            $metaKey, $tzString, $tFromL, $tzString, $tToL
                        );

                        return $where;
                    }, 10, 2);
                    $wpc_time_where_added = true;
                }

                $wp_query->set('meta_query', $this->new_meta_query);
                $this->new_meta_query = [];
                return $wp_query;
            }
        }

        if ($this->from !== false && $this->to === false) {

            $meta_query = array(
                'key'     => $key,
                'value'   => $this->from,
                'compare' => '>=',
                'type'    => $this->date_type
            );
            $this->addMetaQueryArray($meta_query);
        }

        if ($this->to !== false && $this->from === false) {

            $meta_query = array(
                'key'     => $key,
                'value'   => $this->to,
                'compare' => '<=',
                'type'    => $this->date_type
            );
            $this->addMetaQueryArray($meta_query);
        }

        if ($this->to !== false && $this->from !== false) {

            $meta_query = array(
                'key'     => $key,
                'value'   => [$this->from, $this->to],
                'compare' => 'BETWEEN',
                'type'    => $this->date_type
            );
            $this->addMetaQueryArray($meta_query);
        }

        $this->addMetaQueryArray($meta_query);

        if (count($this->new_meta_query) > 1) {
            $this->new_meta_query['relation'] = 'AND';
        }

        $wp_query->set('meta_query', $this->new_meta_query);
        $this->new_meta_query = [];

        return $wp_query;
    }

    private function detectMetaTypeForKey($meta_key, $post_type = 'any', $sampleSize = 10)
    {
        global $wpdb;

        $post_type_sql = '';
        $params = [$meta_key];

        if ($post_type !== 'any') {
            if (is_array($post_type)) {
                $placeholders = implode(',', array_fill(0, count($post_type), '%s'));
                $post_type_sql = " AND p.post_type IN ($placeholders) ";
                $params = array_merge($params, $post_type);
            } else {
                $post_type_sql = " AND p.post_type = %s ";
                $params[] = $post_type;
            }
        }

        $sql = "
        SELECT pm.meta_value
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s
          AND pm.meta_value IS NOT NULL
          AND pm.meta_value <> ''
          {$post_type_sql}
        LIMIT %d";
        $params[] = $sampleSize;

        $values = $wpdb->get_col($wpdb->prepare($sql, $params));

        if (empty($values)) {
            return 'CHAR';
        }

        $counts = [
            'DATETIME' => 0,
            'DATE'     => 0,
            'NUMERIC'  => 0,
            'CHAR'     => 0,
        ];

        foreach ($values as $v) {
            $type = $this->guessMetaTypeFromValue($v, true);
            if (!isset($counts[$type])) {
                $counts[$type] = 0;
            }
            $counts[$type]++;
        }

        arsort($counts);
        $best = key($counts);

        return $best ?: 'CHAR';
    }

    private function guessMetaTypeFromValue($value, $change_meta_date_format = false, $change_date_format = false)
    {
        // Normalize input
        $trimmed = trim((string)$value);
        $apply_meta_date_format = $change_meta_date_format;
        $apply_date_format = $change_date_format;

        // Local setter to avoid repeating the same condition
        $setFormat = function ($fmt) use ($apply_meta_date_format, $apply_date_format) {
            if ($apply_meta_date_format) {
                $this->meta_date_format = $fmt;
            }
            if ($apply_date_format) {
                $this->date_format = $fmt;
            }
        };

        // Empty value -> treat as CHAR
        if ($trimmed === '') {
            $setFormat(null);
            return 'CHAR';
        }

        // Predefined patterns for readability
        $TIME_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d(?:[:][0-5]\d)?$/';
        $DATETIME_PATTERN = '/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/';
        $DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}$/';
        $ISO8601_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+\-]\d{2}:\d{2})?$/';

        // TIME: HH:MM or HH:MM:SS
        if (preg_match($TIME_PATTERN, $trimmed)) {
            $setFormat('H:i:s');
            return 'TIME';
        }

        // Digits only
        if (ctype_digit($trimmed)) {
            $len = strlen($trimmed);

            switch ($len) {
                // 13 digits — likely UNIX timestamp in milliseconds
                case 13:
                    if ((int)$trimmed > 999999999999) {
                        $setFormat('Ums');
                        return 'NUMERIC';
                    }
                    break;

                // 10 digits — UNIX timestamp in seconds
                case 10:
                    $setFormat('U');
                    return 'NUMERIC';

                // 8 digits — Ymd format (e.g., 20250831)
                case 8:
                    $setFormat('Ymd');
                    return 'NUMERIC';

                default:
                    // Any other all-digit string is still NUMERIC
                    $setFormat(null);
                    return 'NUMERIC';
            }
        }

        // MySQL DATETIME: Y-m-d H:i:s
        if (preg_match($DATETIME_PATTERN, $trimmed)) {
            $setFormat('Y-m-d H:i:s');
            return 'DATETIME';
        }

        // MySQL DATE: Y-m-d
        if (preg_match($DATE_PATTERN, $trimmed)) {
            $setFormat('Y-m-d');
            return 'DATE';
        }

        // ISO 8601: 2025-08-31T15:30:45Z or with an offset
        if (preg_match($ISO8601_PATTERN, $trimmed)) {
            $setFormat('Y-m-d\TH:i:sP');
            return 'CHAR';
        }

        // Fallback
        $setFormat(null);
        return 'CHAR';
    }

    private function timeToTodayDateTime( $value, ?\DateTimeZone $tz = null): ?string
    {
        $value = trim($value);

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(?:[:][0-5]\d)?$/', $value)) {
            return null;
        }

        if (strlen($value) === 5) { // HH:MM
            $value .= ':00';
        }

        if (!$tz) {
            if (function_exists('wp_timezone')) {
                $tz = wp_timezone(); // \DateTimeZone
            } else {
                $tzString = function_exists('wp_timezone_string') ? wp_timezone_string() : '';
                $tz = $tzString ? new \DateTimeZone($tzString) : new \DateTimeZone(date_default_timezone_get());
            }
        }

        $now = new DateTime('now', $tz);
        $now->modify('-1 day');
        $datePart = $now->format('Y-m-d');

        return $datePart . ' ' . $value; // Y-m-d H:i:s
    }

}