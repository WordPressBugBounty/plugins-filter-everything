<?php

namespace FilterEverything\Filter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ExportSettings
{

    protected $params;

    protected $exportOptions = [
        'wpc_filter_settings',
        'wpc_indexing_deep_settings',
        'wpc_filter_permalinks',
        'wpc_seo_rules_settings',
        'wpc_filter_experimental',
        'wpc_indexing_deep_settings'
    ];

    protected $settings = [];

    public function __construct($export_params)
    {
        $this->params = $export_params;
    }

    protected function export()
    {
        if (isset($this->params['export_filter_set']) && $this->params['export_filter_set'] === 'on') {
            $this->exportFilterSets();
        }
        if (isset($this->params['export_options']) && $this->params['export_options'] === 'on') {
            $this->exportSettings();
        }
        if (isset($this->params['export_seo_rule']) && $this->params['export_seo_rule'] === 'on') {
            $this->exportSeoRules();
        }
    }

    protected function exportSettings()
    {
        foreach ($this->exportOptions as $option_name) {
            $option = get_option($option_name, false);
            if ($option !== false ) {
                $this->settings['options'][$option_name] = $option;
            }
        }
    }

    protected function exportFilterSets()
    {
        $sets = get_posts(array(
            'post_type'      => FLRT_FILTERS_SET_POST_TYPE,
            'posts_per_page' => -1
        ));

        $post_filter_set = flrt_post_type_underline_transform(FLRT_FILTERS_SET_POST_TYPE);
        $post_filter_field = flrt_post_type_underline_transform(FLRT_FILTERS_POST_TYPE);
        if ($sets) {
            foreach ($sets as $set) {
                $set->post_content = maybe_unserialize($set->post_content);
                $this->settings[$post_filter_set][$set->ID] = (array)$set;
                $this->settings[$post_filter_set][$set->ID]['post_meta'] = $this->exportPostMeta($set->ID, 'wpc_filter');
                $fields = get_posts(array(
                    'post_type'   => FLRT_FILTERS_POST_TYPE,
                    'posts_per_page' => -1,
                    'post_parent' => $set->ID
                ));
                foreach ($fields as $field) {
                    $field->post_content = maybe_unserialize($field->post_content);
                    $this->settings[$post_filter_set][$set->ID][$post_filter_field][$field->ID] = (array)$field;
                }
            }
        }
    }

    protected function exportSeoRules()
    {
        $seo_rules = get_posts(array(
            'post_type'      => FLRT_SEO_RULES_POST_TYPE,
            'posts_per_page' => -1
        ));

        $post_filter_seo_rules = flrt_post_type_underline_transform(FLRT_SEO_RULES_POST_TYPE);
        if ($seo_rules) {
            foreach ($seo_rules as $rule) {
                $this->settings[$post_filter_seo_rules][$rule->ID] = (array)$rule;
                $this->settings[$post_filter_seo_rules][$rule->ID]['post_meta'] = $this->exportPostMeta($rule->ID, 'wpc_seo_rule');
            }
        }
    }

    protected function exportPostMeta($post_id, $prefix)
    {
        global $wpdb;

        $sql = "SELECT meta_key, meta_value 
                    FROM {$wpdb->postmeta}
                    WHERE post_id = %d AND meta_key LIKE %s";

        $results = $wpdb->get_results($wpdb->prepare($sql, $post_id, $wpdb->esc_like($prefix) . '%'), ARRAY_A);


        if(!empty($results)){
            foreach ($results as $key => $value){
                $results[$key]['meta_value'] = maybe_unserialize($value['meta_value']);
            }
            return $results;
        }
        return [];
    }

    public function submit_download()
    {

        $this->export();

        $json = $this->settings;

        if (empty($json)) {
            return false;
        }

        // headers
        $file_name = 'flrt-export-' . date('Y-m-d') . '.json';
        header('Content-Description: File Transfer');
        header("Content-Disposition: attachment; filename={$file_name}");
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\r\n";
        die;
    }
}