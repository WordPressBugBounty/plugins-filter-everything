<?php

namespace FilterEverything\Filter;

if (!defined('ABSPATH')) {
    exit;
}

final class DuplicateFilterSet
{

    public $post_id;

    public $new_post_id;

    public function __construct($post_id)
    {
        $this->post_id = $post_id;
        $this->duplicatePost();
    }

    private function duplicatePost()
    {
        $post = get_post($this->post_id);

        if (!$post || $post->post_type !== FLRT_FILTERS_SET_POST_TYPE) {
            wp_die(esc_html__('The post was not found or is not a Filter Set.', 'filter-everything'));
        }

        $post_content = maybe_unserialize($post->post_content);
        if(isset($post_content['wp_filter_query']))
            unset($post_content['wp_filter_query']);

        if (!is_serialized($post_content)) {
            $post_content = maybe_serialize($post_content);
        }

        $this->new_post_id = wp_insert_post(
            $this->preparePost($post,
                [
                    'post_title'   => $this->generate_unique_copy_title($post->post_title),
                    'post_content' => $post_content,
                    'post_status'  => 'draft'
                ]
            ));
        $this->copyPostMeta($this->post_id, $this->new_post_id);
        $this->copyFilterFields();
    }

    private function preparePost($post, $options = [])
    {
        $new_post = [
            'post_author'           => wp_get_current_user()->ID,
            'post_content'          => $post->post_content,
            'post_title'            => $post->post_title,
            'post_excerpt'          => $post->post_excerpt,
            'post_status'           => 'publish',
            'comment_status'        => $post->comment_status,
            'ping_status'           => $post->ping_status,
            'post_password'         => $post->post_password,
            'post_name'             => $post->post_name,
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

    private function copyFilterFields()
    {
        $fields = get_children([
            'post_parent' => $this->post_id,
            'post_type'   => 'filter-field',
            'numberposts' => -1,
            'post_status' => 'any'
        ]);

        if ($fields) {
            $parent_filters = [];
            foreach ($fields as $field) {
                $new_field_post_id = wp_insert_post($this->preparePost($field,
                    ['post_parent' => $this->new_post_id]
                ));
                $post_content = maybe_unserialize($field->post_content);
                if (isset($post_content['parent_filter']) && !empty($post_content['parent_filter']) && $post_content['parent_filter'] > 0) {
                    $parent_filters[$new_field_post_id]['post_name'] = $field->post_name;
                    $parent_filters[$new_field_post_id]['has_parent_filter_post_id'] = $new_field_post_id;
                }
                $this->copyPostMeta($field->ID, $new_field_post_id);
            }
            if (!empty($parent_filters)) {

                foreach ($parent_filters as $parent_filter) {
                    if (!empty($parent_filter['post_name']) && !empty($parent_filter['has_parent_filter_post_id'])) {
                        $query = new \WP_Query([
                            'post_type'   => 'filter-field',
                            'post_parent' => $this->post_id,
                            'post_status' => 'any',
                            'name'        => $parent_filter['post_name'],
                            'numberposts' => 1
                        ]);

                        if ($query->have_posts()) {
                            $parent_filter_posts = $query->posts[0];
                            $filter_parent_id = maybe_unserialize($parent_filter_posts->post_content)['parent_filter'];
                            $parent_filter_name = get_post($filter_parent_id);
                            if (!empty($parent_filter_name)) {
                                $new_query = new \WP_Query([
                                    'post_type'   => 'filter-field',
                                    'post_parent' => $this->new_post_id,
                                    'post_status' => 'any',
                                    'name'        => $parent_filter_name->post_name,
                                    'numberposts' => 1
                                ]);
                                if ($new_query->have_posts()) {
                                    $parent_filter_info = $new_query->posts[0];
                                    $post_new = get_post($parent_filter['has_parent_filter_post_id']);
                                    $post_new->post_content = maybe_unserialize($post_new->post_content);
                                    $post_new->post_content['parent_filter'] = (string) $parent_filter_info->ID;
                                    $post_new->post_content = maybe_serialize($post_new->post_content);
                                    wp_update_post(wp_slash($post_new));
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function copyPostMeta($old_post_id, $new_post_id)
    {
        $meta = get_post_meta($old_post_id);
        foreach ($meta as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }
    }

    private function generate_unique_copy_title($original_title, $post_type = 'filter-set')
    {
        global $wpdb;

        $copy_text = esc_html__('copy', 'filter-everything');

        if (preg_match('/^(.*) – ' . $copy_text . ' \d+$/', $original_title, $matches)) {
            $base_title = $matches[1];
        } else {
            $base_title = $original_title;
        }

        $copy_pattern = $wpdb->esc_like($base_title) . ' – ' . $copy_text . '%';

        $titles = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_title FROM $wpdb->posts
             WHERE (post_title = %s OR post_title LIKE %s)
             AND post_type = %s",
                $base_title,
                $copy_pattern,
                $post_type
            )
        );

        $max_copy_number = 0;

        foreach ($titles as $title) {
            if (preg_match('/^' . preg_quote($base_title, '/') . ' – ' . $copy_text . ' (\d+)$/', $title, $m)) {
                $num = intval($m[1]);
                if ($num > $max_copy_number) {
                    $max_copy_number = $num;
                }
            }
        }

        $new_number = $max_copy_number + 1;
        return $base_title . ' – ' . $copy_text . ' ' . $new_number;
    }
}