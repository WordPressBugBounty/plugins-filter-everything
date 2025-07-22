<?php

namespace FilterEverything\Filter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use FilterEverything\Filter\DefaultSettings;

class ImportSettings
{
    public $import_params;

    public $redirect_url;

    public $files;

    public $file_field_name = 'wpc_filter_import_export';

    public $file_name = 'import_file';

    public $file_data = [];

    public $paramsToImport =
        [
            'filter_set',
            'filter_seo_rule',
            'options'
        ];

    public function __construct($import_params, $files, $redirect_url)
    {
        $this->import_params = $import_params;
        $this->files = $files;
        $this->redirect_url = $redirect_url;
        $this->validate_data();
        $this->validate_params();
        $this->insertImportData();
        $url = add_query_arg('flrt_import_success', 'import_success', $this->redirect_url);
        wp_redirect(esc_url_raw($url));
        exit;
    }

    public function validate_data()
    {
        $this->validate_uploaded_json_file();
    }

    public function query_arg($arg)
    {
        return add_query_arg('flrt_import_error', $arg, $this->redirect_url);
    }

    public function validate_uploaded_json_file()
    {
        $file_field_name = $this->file_field_name;
        $file_name = $this->file_name;

        if (
            !isset($this->files[$file_field_name]) ||
            $this->files[$file_field_name]['error'][$file_name] !== UPLOAD_ERR_OK
        ) {
            wp_redirect(esc_url_raw($this->query_arg('upload_file_error')));
            exit;
        }

        $file = $this->files[$file_field_name];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name'][$file_name]);
        finfo_close($finfo);

        if ($mime_type !== 'application/json') {
            wp_redirect(esc_url_raw($this->query_arg('not_json_format')));
            exit;
        }

        $ext = pathinfo($file['name'][$file_name], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'json') {
            wp_redirect(esc_url_raw($this->query_arg('invalid_extension')));
            exit;
        }

        $content = file_get_contents($file['tmp_name'][$file_name]);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_redirect(esc_url_raw($this->query_arg('invalid_json')));
            exit;
        }
        $this->file_data = $data;
        return true;
    }


    public function validate_params()
    {
        $import_params = [];
        foreach ($this->import_params as $param_name => $param) {
            if ($param === 'on') {
                $import_param_name = explode('import_', $param_name)[1];
                if (!empty($import_param_name)) {
                    if (in_array($import_param_name, $this->paramsToImport)) {
                        $import_params[] = $import_param_name;
                    }
                }
            }
        }
        if (empty($import_params)) {
            wp_redirect(esc_url_raw($this->query_arg('empty_params')));
        }

        if (!empty($import_params)) {
            foreach ($import_params as $param) {
                if (!isset($this->file_data[$param]) || empty($this->file_data[$param])) {
                    wp_redirect(esc_url_raw($this->query_arg('empty_in_import_' . $param)));
                }
            }
            $this->paramsToImport = $import_params;
        }
    }

    public function insertImportData()
    {
        foreach ($this->paramsToImport as $item) {
            $function_name = 'import_' . $item;
            if (method_exists($this, $function_name)) {
                $this->$function_name($item);
            }
        }
    }

    public function import_filter_seo_rule($item)
    {
        foreach ($this->file_data[$item] as $seo_rule_post) {
            $post_id = wp_insert_post($this->prepareImportPost($seo_rule_post));

            if (isset($seo_rule_post['post_meta']) && !empty($seo_rule_post['post_meta'])) {
                foreach ($seo_rule_post['post_meta'] as $post_meta) {
                    add_post_meta($post_id, $post_meta['meta_key'], maybe_serialize($post_meta['meta_value']), true);
                }
            }
        }
    }

    public function import_options($item)
    {
        $defaultSettings = new DefaultSettings();
        foreach ($this->file_data[$item] as $option_name => $option) {
            if (method_exists($defaultSettings, $option_name)) {
                foreach ($defaultSettings->$option_name() as $def_option_name => $def_option) {
                    if (is_array($def_option)) {
                        if (!isset($option[$def_option_name])) {
                            $option[$def_option_name] = $def_option;
                        }
                        if (isset($option[$def_option_name])) {
                            foreach ($def_option as $key => $def_op){
                                if(!in_array($def_op, $option[$def_option_name])){
                                    array_push($option[$def_option_name], $def_op);
                                }
                            }
                        }
                    } else {
                        if (!isset($option[$def_option_name])) {
                            $option[$def_option_name] = $def_option;
                        }
                    }
                }
            }
            if (get_option($option_name)){
                update_option($option_name, $option);
            }else{
                add_option($option_name, $option);
            }
        }
    }

    public function import_filter_set($item)
    {
        foreach ($this->file_data[$item] as $filter_set) {
            $post_id = wp_insert_post(
                $this->prepareImportPost(
                    $filter_set,
                    [
                        'post_content' => maybe_serialize($filter_set['post_content']),
                    ]
                )
            );
            if (isset($filter_set['post_meta']) && !empty($filter_set['post_meta'])) {
                foreach ($filter_set['post_meta'] as $post_meta) {
                    add_post_meta($post_id, $post_meta['meta_key'], maybe_serialize($post_meta['meta_value']), true);
                }
            }

            if (isset($filter_set['filter_field']) && !empty($filter_set['filter_field'])) {
                foreach ($filter_set['filter_field'] as $filter_field) {
                    $filter_field_post_id = wp_insert_post(
                        $this->prepareImportPost(
                            $filter_field,
                            [
                                'post_parent'  => $post_id,
                                'post_content' => maybe_serialize($filter_field['post_content'])
                            ]
                        ));
                }
            }
        }
    }

    private function prepareImportPost($post, $options = [])
    {
        if (is_array($post)) {
            $post = (object)$post;
        }
        $new_post = [
            'post_author'           => wp_get_current_user()->ID,
            'post_content'          => $post->post_content,
            'post_title'            => $post->post_title,
            'post_excerpt'          => $post->post_excerpt,
            'post_status'           => $post->post_status,
            'comment_status'        => $post->comment_status,
            'ping_status'           => $post->ping_status,
            'post_password'         => $post->post_password,
            'post_name'             => $post->post_name,
            'to_ping'               => $post->to_ping,
            'pinged'                => $post->pinged,
            'post_parent'           => $post->post_parent,
            'post_content_filtered' => $post->post_content_filtered,
            'post_type'             => $post->post_type,
            'menu_order'            => $post->menu_order,
            'post_mime_type'        => $post->post_mime_type,
        ];

        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $new_post[$key] = $value;
            }
        }
        return $new_post;
    }
}