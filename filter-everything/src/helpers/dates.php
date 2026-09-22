<?php

if (!defined('ABSPATH')) {
    exit;
}

use \FilterEverything\Filter\Container;
use \FilterEverything\Filter\FilterSet;
use \FilterEverything\Filter\FilterFields;
use \FilterEverything\Filter\PostMetaNumEntity;
use \FilterEverything\Filter\PostDateEntity;
use \FilterEverything\Filter\PostMetaDateEntity;

/**
 * Date & time helpers: detecting date/time formats, parsing, formatting and
 * PHP→jQuery UI format conversion for date filters.
 *
 * Split out of src/wpc-helpers.php on 2026-09-14; function names and bodies are
 * unchanged. Loaded from filter-everything.php right after wpc-helpers.php.
 */

/**
 * Checks and returns date format.
 * Does not check if date is valid
 * @param $date
 * @return string|false date, time format or false
 */
function flrt_detect_date_type($date_or_time)
{
    if (!$date_or_time) {
        return false;
    }
    $format = false;
    $date = false;
    $time = false;

    $date_or_time = str_replace(FLRT_DATE_TIME_SEPARATOR, ' ', $date_or_time);

    $pcs = date_parse($date_or_time);
    if ($pcs['year'] !== false && $pcs['month'] !== false && $pcs['day'] !== false) {
        $date = true;
    }

    if ($pcs['hour'] !== false && $pcs['minute'] !== false && $pcs['second'] !== false) {
        $time = true;
    }

    if ($date && $time) {
        $format = 'datetime';
    } else {
        if ($date) {
            $format = 'date';
        }
        if ($time) {
            $format = 'time';
        }
    }

    return $format;
}

/**
 * Modifies datetime to the human format
 * @param $datetime
 * @param $date_type
 * @param string $sep
 * @return mixed|string
 */
function flrt_clean_date_time($datetime, $date_type, $sep = " ")
{
    if ($date_type === 'date' || $date_type === 'DATE') {
        $pieces = explode($sep, $datetime);
        return $pieces[0]; //date e.g. 2021-05-14
    } else if ($date_type === 'time' || $date_type === 'TIME') {
        $pieces = explode($sep, $datetime);
        if (isset($pieces[1])) {
            return $pieces[1]; //time e.g. 14:15:47
        }
    } else {
        return $datetime; // str_replace( $sep, ' ', $datetime ); //datetime e.g. 2021-05-14 14:15:47
    }
}

function flrt_apply_date_format($income_date, $format = "Y-m-d H:i:s")
{
    $timestamp = strtotime($income_date);
    return flrt_date($format, $timestamp);
}

function flrt_date($format, $timestamp = null)
{
    global $wp_locale;

    if (null === $timestamp) {
        $timestamp = time();
    } elseif (!is_numeric($timestamp)) {
        return false;
    }

    $datetime = date_create('@' . $timestamp);

    if (empty($wp_locale->month) || empty($wp_locale->weekday)) {
        $date = $datetime->format($format);
    } else {
        // We need to unpack shorthand `r` format because it has parts that might be localized.
        $format = preg_replace('/(?<!\\\\)r/', DATE_RFC2822, $format);

        $new_format = '';
        $format_length = strlen($format);
        $month = $wp_locale->get_month($datetime->format('m'));
        $weekday = $wp_locale->get_weekday($datetime->format('w'));

        for ($i = 0; $i < $format_length; $i++) {
            switch ($format[$i]) {
                case 'D':
                    $new_format .= addcslashes($wp_locale->get_weekday_abbrev($weekday), '\\A..Za..z');
                    break;
                case 'F':
                    $new_format .= addcslashes($month, '\\A..Za..z');
                    break;
                case 'l':
                    $new_format .= addcslashes($weekday, '\\A..Za..z');
                    break;
                case 'M':
                    $new_format .= addcslashes($wp_locale->get_month_abbrev($month), '\\A..Za..z');
                    break;
                case 'a':
                    $new_format .= addcslashes($wp_locale->get_meridiem($datetime->format('a')), '\\A..Za..z');
                    break;
                case 'A':
                    $new_format .= addcslashes($wp_locale->get_meridiem($datetime->format('A')), '\\A..Za..z');
                    break;
                case '\\':
                    $new_format .= $format[$i];

                    // If character follows a slash, we add it without translating.
                    if ($i < $format_length) {
                        $new_format .= $format[++$i];
                    }
                    break;
                default:
                    $new_format .= $format[$i];
                    break;
            }
        }

        $date = date_format($datetime, $new_format);
    }

    return $date;
}

function flrt_default_date_format($date_type = 'date')
{
    /**
     * @todo date format depend from localization and geo settings
     * we have to relate them here
     */
    $date_format = __('F j, Y');

    switch ($date_type) {
        case 'date':
            $date_format = __('F j, Y'); //'d-m-Y';
            break;
        case 'datetime':
            $date_format = __('F j, Y g:i a'); //'d-m-Y H:i:s';
            break;
        case 'time':
            $date_format = __('g:i a'); // 'H:i:s';
            break;
    }

    return $date_format;
}

function flrt_convert_date_to_js($date_or_time)
{
    $date_php_to_js = Container::instance()->getParam('php_to_js_date_formats');
    return flrt_str_replace($date_or_time, $date_php_to_js);
}

function flrt_convert_time_to_js($date_or_time)
{
    $time_php_to_js = Container::instance()->getParam('php_to_js_time_formats');
    return flrt_str_replace($date_or_time, $time_php_to_js);
}

function flrt_split_date_time($date_time = '')
{
    $php_date = Container::instance()->getParam('php_to_js_date_formats');
    $php_time = Container::instance()->getParam('php_to_js_time_formats');
    $chars = str_split($date_time);
    $type = 'date';

    $data = array(
            'date' => '',
            'time' => '',
    );

    foreach ($chars as $i => $c) {
        if (isset($php_date[$c])) {
            $type = 'date';
        } elseif (isset($php_time[$c])) {
            $type = 'time';
        }
        $data[$type] .= $c;
    }

    $data['date'] = trim($data['date']);
    $data['time'] = trim($data['time']);

    return $data;
}
function flrtDetectTimeComponents(string $format): array
{
    $hasHours   = false;
    $hasMinutes = false;
    $hasSeconds = false;

    $len = strlen($format);
    for ($i = 0; $i < $len; $i++) {
        $char = $format[$i];

        if ($char === '\\') {
            $i++;
            continue;
        }

        if (in_array($char, ['g', 'G', 'h', 'H'], true)) {
            $hasHours = true;
        } elseif ($char === 'i') {
            $hasMinutes = true;
        } elseif ($char === 's') {
            $hasSeconds = true;
        }
    }

    return [
            'hours'   => $hasHours,
            'minutes' => $hasMinutes,
            'seconds' => $hasSeconds,
    ];
}

function flrtBuildDateTimeString(string $value, string $format): ?string
{
    $date = DateTime::createFromFormat($format, $value);
    if ($date === false) {
        return null;
    }

    $components = flrtDetectTimeComponents($format);

    $hours   = $components['hours']   ? $date->format('H') : '00';
    $minutes = $components['minutes'] ? $date->format('i') : '00';
    $seconds = $components['seconds'] ? $date->format('s') : '00';

    return $date->format('Y-m-d') . " {$hours}:{$minutes}:{$seconds}";
}
