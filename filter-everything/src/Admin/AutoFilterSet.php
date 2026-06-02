<?php

namespace FilterEverything\Filter;

if (!defined('ABSPATH')) {
    exit;
}

class AutoFilterSet extends FilterSet
{
    public $post_types;

    public $publicTaxonomies = [];

    public $postTaxonomies = [];

    public $limit = 5;

    public $redirect_url = '';

    public $fse;


    public function __construct($redirect_url = '')
    {
        add_action('save_post', array($this, 'saveSet'), 10, 2);
        $this->redirect_url = $redirect_url;
        $this->fse = Container::instance()->getFilterSetService();
        $this->post_types = $this->fse->getPostTypes();

        if (empty($this->post_types)) {
            flrt_refresh_temp_transient('wpc_auto_filters_error', esc_html__('Error: No post types found. Please add your first post and try again, or create a Filter Set manually.', 'filter-everything'));
            wp_redirect(esc_url_raw($redirect_url));
            exit();
        }

        if (!empty($this->post_types['page'])) {
            unset($this->post_types['page']);
        }

        $this->publicTaxonomies = get_taxonomies(['public' => true], 'objects');

        if (flrt_is_woocommerce()) {
            $attributeTaxonomies = wc_get_attribute_taxonomies();

            if (!empty($attributeTaxonomies)) {
                foreach ($attributeTaxonomies as $attr) {
                    $taxonomy = wc_attribute_taxonomy_name($attr->attribute_name);
                    $taxonomy_obj = get_taxonomy($taxonomy);
                    if ($taxonomy_obj) {
                        $this->publicTaxonomies[$taxonomy] = $taxonomy_obj;
                    }
                }
            }
        }

        foreach ($this->post_types as $post_type => $post_name) {
            $this->postTaxonomies[$post_type] = $this->getPostTaxonomies($post_type);
        }

        $result = $this->addAutoFilters();
        if (!$result) {
            flrt_refresh_temp_transient('wpc_auto_filters_error', esc_html__("Error: Automatic filters were not created. Please try again, or set them up manually.", 'filter-everything'));
            wp_redirect(esc_url_raw($redirect_url));
            exit();
        }

        wp_redirect(esc_url_raw($redirect_url));
        exit();
    }

    private function filterSetDefaultSettings($post)
    {
        return array(
            'post_author'  => wp_get_current_user()->ID,
            'post_type'    => FLRT_FILTERS_SET_POST_TYPE,
            'post_status'  => 'inherit',
            'post_title'   => $post['post_title'],
            'post_excerpt' => $post['post_excerpt'],
            'post_name'    => '1'
        );
    }

    private function filterSetFields($post)
    {
        return array(
            'post_type'                => (!empty($post['post_type'])) ? $post['post_type'] : 'post',
            'search_field_menu_order'  => $post['search_field_menu_order'],
            'apply_button_menu_order'  => $post['apply_button_menu_order'],
            'wp_page_type'             => 'common___common',
            'wp_filter_query'          => '-1',
            'hide_empty'               => 'no',
            'show_count'               => 'yes',
            'search_field_label'       => ' ' . esc_html__('Search', 'filter-everything'),
            'search_field_placeholder' => '',
            'apply_button_text'        => ' ' . esc_html__('Apply', 'filter-everything'),
            'reset_button_text'        => ' ' . esc_html__('Reset', 'filter-everything'),
            'horizontal_view_priority' => 'filter_set',
        );
    }

    private function filterFieldDefaultSettings($post)
    {

        return array(
            'ID'              => 'wpc_new_id',
            'entity'          => $post['entity'],
            'e_name'          => (!empty($post['e_name'])) ? $post['e_name'] : '',
            'view'            => (!empty($post['view'])) ? $post['view'] : 'checkboxes',
            'date_type'       => 'date',
            'show_term_names' => 'yes',
            'dropdown_label'  => '',
            'date_format'     => 'F j, Y',
            'logic'           => (!empty($post['logic'])) ? $post['logic'] : 'or',
            'label'           => (!empty($post['label'])) ? $post['label'] : '',
            'orderby'         => 'default',
            'in_path'         => (!empty($post['in_path'])) ? $post['in_path'] : 'yes',
            'range_slider'    => 'yes',
            'step'            => '1',
            'parent_filter'   => '-1',
            'parent'          => '',
            'min_num_label'   => '',
            'max_num_label'   => '',
            'tooltip'         => '',
            'show_chips'      => 'yes',
            'more_less'       => 'yes',
            'slug'            => $post['post_name'],
        );
    }

    private function getPostTaxonomies($post_type)
    {
        global $wpdb;
        $sql = "SELECT tt.taxonomy, MIN(tr.term_taxonomy_id) AS term_taxonomy_id
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE p.post_type = '%s'
                GROUP BY tt.taxonomy
                ORDER BY term_taxonomy_id ASC;";

        $excludeTaxonomies = flrt_excluded_taxonomies();
        $query = $wpdb->prepare($sql, $post_type);
        $taxonomies = $wpdb->get_results($query, ARRAY_A);
        if (!empty($taxonomies)) {
            foreach ($taxonomies as $key => $taxonomy) {
                if (empty($this->publicTaxonomies[$taxonomy['taxonomy']])) {
                    unset($taxonomies[$key]);
                }
                if (in_array($taxonomy['taxonomy'], $excludeTaxonomies)) {
                    unset($taxonomies[$key]);
                }
            }
            return $taxonomies;
        }
        return false;
    }

    public function standardPostFilters()
    {
        $entities = array(
            'post' => array(
                'taxonomy' => ['category', 'post_tag']
            )
        );


        if (flrt_is_woocommerce()) {
            $entities['product'] = array(
                'taxonomy'      => ['product_cat', 'product_tag'],
                'post_meta_num' => ['_price'],
            );
        }

        foreach ($entities as $post_type => $entity) {
            foreach ($entity['taxonomy'] as $taxonomy) {
                if (empty($this->checkTaxonomyTerms($taxonomy))) {
                    $key = array_search($taxonomy, $entities[$post_type]['taxonomy']);
                    if ($key !== false) {
                        unset($entities[$post_type]['taxonomy']);
                    }
                }
            }
        }


        foreach ($this->postTaxonomies as $post_type => $taxonomies) {
            $i = 1;
            foreach ($taxonomies as $taxonomy) {
                if ($this->limit < $i) continue;
                if (!empty($entities[$post_type]) &&
                    !empty($entities[$post_type]['taxonomy'])
                    && in_array($taxonomy['taxonomy'], $entities[$post_type]['taxonomy'])) {
                    $i++;
                    continue;
                }
                if (empty($this->checkTaxonomyTerms($taxonomy))) {
                    continue;
                }
                $entities[$post_type]['taxonomy'][] = $taxonomy['taxonomy'];
                $i++;
            }
        }
        return $entities;
    }

    private function checkTaxonomyTerms($taxonomy)
    {
        return get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ]);
    }

    private function prepareFilterEntities()
    {
        $filterSets = [];
        $entities = $this->standardPostFilters();
        $empty_all_entities = true;
        foreach ($entities as $entity) {
            if (!empty($entity)) {
                $empty_all_entities = false;
            }
        }
        if ($empty_all_entities) {
            flrt_refresh_temp_transient('wpc_auto_filters_error', esc_html__("Error: No taxonomies found. Please create your first taxonomy and then try again.", 'filter-everything'));
            wp_redirect(esc_url_raw($this->redirect_url));
            exit();
        }

        if (!empty($entities)) {
            foreach ($entities as $post_type => $data) {
                $post = [];
                $title_base = (string)$post_type;
                $title_base = ltrim($title_base);
                if ($title_base !== '') {
                    $firstChar = mb_substr($title_base, 0, 1, 'UTF-8');
                    $rest = mb_substr($title_base, 1, null, 'UTF-8');
                    $title_base = mb_strtoupper($firstChar, 'UTF-8') . $rest;
                }
                $post_title = $title_base . " " . esc_html__('filters', 'filter-everything');
                $post['post_title'] = flrt_generate_unique_copy_title($post_title, FLRT_FILTERS_SET_POST_TYPE, esc_html__('(auto-generated)', 'filter-everything'), false);
                $post['post_excerpt'] = $post_type;

                $filterSets[$post_type]['filter_set'] = $this->filterSetDefaultSettings($post);
                $post = [];
                $post['post_type'] = $post_type;
                $post['search_field_menu_order'] = count($data['taxonomy']) + 1;
                $post['apply_button_menu_order'] = count($data['taxonomy']) + 2;
                $filterSets[$post_type][self::FIELD_NAME_PREFIX] = $this->filterSetFields($post);

                $standard_slugs = [
                    'category'    => 'pcat',
                    'post_tag'    => 'ptag',
                    'product_cat' => 'category',
                    'product_tag' => 'tag'
                ];

                foreach ($data as $entity => $taxonomies) {
                    foreach ($taxonomies as $taxonomy) {
                        // $post_filter_field['post_title'] = '';
                        $post_filter_field = [];
                        if ($entity === 'taxonomy') {
                            $post_filter_field['in_path'] = 'yes';
                            $post_filter_field['entity'] = $entity . "_" . $taxonomy;
                            $post_filter_field['label'] = $this->publicTaxonomies[$taxonomy]->label;
                            $post_filter_field['post_name'] = $taxonomy;
                        }


                        if (flrt_is_woocommerce() && $post_type === 'product') {
                            if (isset($post_filter_field['post_name']) && isset($standard_slugs[$post_filter_field['post_name']])) {
                                $post_filter_field['post_name'] = $standard_slugs[$post_filter_field['post_name']];
                            } elseif (strpos($post_filter_field['post_name'], 'pa_') === 0) {
                                $post_filter_field['post_name'] = substr($post_filter_field['post_name'], 3);
                            }
                        }

                        if ($post_type === 'post' && isset($post_filter_field['post_name']) && isset($standard_slugs[$post_filter_field['post_name']])) {
                            $post_filter_field['post_name'] = $standard_slugs[$post_filter_field['post_name']];
                        }


                        if (defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO) {
                            $post_filter_field['post_name'] = ltrim($post_filter_field['post_name'], "_");
                            $post_filter_field['post_name'] = str_replace("_", "-", $post_filter_field['post_name']);
                        } else {
                            $post_filter_field['post_name'] = '_' . $post_filter_field['post_name'];
                        }

                        if ($entity === 'post_meta_num') {
                            $post_filter_field['label'] = esc_html__('Price', 'filter-everything');
                            $post_filter_field['view'] = 'range';
                            $post_filter_field['logic'] = 'and';
                            $post_filter_field['post_name'] = 'price';
                            $post_filter_field['entity'] = $entity;
                            $post_filter_field['e_name'] = $taxonomy;
                        }

                        $filter_field = $this->filterFieldDefaultSettings($post_filter_field);
                        if ($entity === 'post_meta_num') {
                            unset($filter_field['in_path']);
                        }
                        $filterSets[$post_type]['wpc_filter_fields'][] = $filter_field;
                    }
                }
            }

            return apply_filters('wpc_default_filters_config', $filterSets);
        }
        return false;
    }

    private function addAutoFilters()
    {
        $filterLinks = get_option('wpc_filter_permalinks', []);
        $filterSets = $this->prepareFilterEntities();
        $filterFields = parent::getFilterFieldService();

        if ($filterSets !== false) {
            foreach ($filterSets as $filterSet) {
                $filterSetFields = $filterSet[self::FIELD_NAME_PREFIX];
                $filterSetFields['post_name'] = $filterSet['filter_set']['post_name'];
                $is_valid = $this->validateSetFields($filterSetFields);
                if(!$is_valid){
                    $errors = $this->getErrors();
                    if(in_array(92, $errors)){
                        flrt_refresh_temp_transient(
                            'wpc_auto_filters_error',
                            $filterFields->getErrorMessage(92)
                        );
                    }
                    continue;
                }
                $filterSet = (array)$filterSet['filter_set'];
                if (!empty($filterSet['post_content'])) {
                    $filterSet['post_content'] = maybe_serialize($filterSet['post_content']);
                }
                add_filter('pre_wp_unique_post_slug', 'flrt_force_non_unique_slug', 10, 2);

                $filterSet_post_id = wp_insert_post($filterSet);

                remove_filter('pre_wp_unique_post_slug', 'flrt_force_non_unique_slug', 10);
            }
            return true;
        }
        return false;
    }

    public function saveSet($post_id, $post)
    {
        $filterSets = $this->prepareFilterEntities();

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if (wp_is_post_revision($post_id)) {
            return $post_id;
        }

        if ($post->post_type !== FLRT_FILTERS_SET_POST_TYPE) {
            return $post_id;
        }

        $nonce = filter_input(INPUT_GET, '_flrt_nonce');

        if (!parent::verifyNonce($nonce)) {
            return $post_id;
        }

        if (!current_user_can(flrt_plugin_user_caps())) {
            return $post_id;
        }

        remove_action('save_post', array($this, 'saveSet'), 10, 2);

        $filterFields = parent::getFilterFieldService();
        $saveFiltersTrigger = true;
        $allFiltersValid = true;
        $filterLinks = get_option('wpc_filter_permalinks', []);

        // Save filter fields
        if (!empty($filterSets[$post->post_excerpt]['wpc_filter_fields'])) {
            $postData['wpc_filter_fields'] = $filterSets[$post->post_excerpt]['wpc_filter_fields'];
            // Validate filters
            if (!$filterFields->validateFilters($postData['wpc_filter_fields'])) {
                $saveFiltersTrigger = false;
            }

            if ($saveFiltersTrigger) {
                $filtersToSave = [];

                // loop
                $filterConfiguredFields = $filterFields->getFieldsMapping();
                foreach ($postData['wpc_filter_fields'] as $filterId => $filter) {

                    // Set up checkbox fields if they are empty
                    $filter = $filterFields->prepareFilterCheckboxFields($filter, $filterFields->getFieldsByType('checkbox', $filterConfiguredFields));

                    // set parent
                    if (!$filter['parent']) {
                        $filter['parent'] = $post_id;
                    }

                    $filter = $filterFields->sanitizeFilterFields($filter);
                    $filtersToSave[$filterId] = $filter;

                    if (!$filterFields->validateTheFilter($filter, $filterId)) {
                        unset($postData['wpc_filter_fields'][$filterId]);
                        unset($filtersToSave[$filterId]);
                    }
                }
                if (empty($filtersToSave)) {
                    $allFiltersValid = false;
                }

                // Loop to save
                if ($allFiltersValid) {

                    $update_after_save = [];
                    $old_new_ids = [];

                    foreach ($filtersToSave as $filterId => $filter) {
                        // save filter
                        $saved_filter = $filterFields->saveFilter($filter);
                        $link_filter = $saved_filter;
                        $link_filter['post_content'] = maybe_serialize($link_filter['post_content']);
                        $entity = $link_filter['post_content']['entity'];
                        $taxonomy = $link_filter['post_content']['e_name'];
                        if (!empty($filterLinks[$entity . "#" . $taxonomy])) {
                            $filterField['post_name'] = $filterLinks[$entity . "#" . $taxonomy];
                        } else {
                            $filterLinks[$entity . "#" . $taxonomy] = $filterField['post_name'];
                        }

                        if (isset($saved_filter['parent_filter']) && isset($saved_filter['ID'])) {
                            if (strpos($saved_filter['parent_filter'], 'filter_', 0) !== false) {
                                $update_after_save[$saved_filter['ID']][] = [
                                    'key'   => 'parent_filter',
                                    'value' => $saved_filter['parent_filter']
                                ];
                            }
                            $old_new_ids[$filterId] = $saved_filter['ID'];
                        }
                    }

                    update_option('wpc_filter_permalinks', $filterLinks);
                    // Update data after saving Filters and getting IDs of new ones.
                    if (!empty($update_after_save)) {

                        foreach ($update_after_save as $filter_post_id => $fields_to_update) {
                            $filter_post_data = get_post($filter_post_id);
                            $filter_data = maybe_unserialize($filter_post_data->post_content);

                            if (!$filter_data) {
                                continue;
                            }

                            foreach ($fields_to_update as $field_attr) {
                                if ($field_attr['key'] === 'parent_filter') {
                                    if (isset($filter_data['parent_filter'])) {
                                        if (isset($old_new_ids[$field_attr['value']])) {
                                            $filter_data[$field_attr['key']] = $old_new_ids[$field_attr['value']];
                                        }
                                    }
                                }
                            }

                            $to_update = array(
                                'ID'           => $filter_post_id,
                                'post_content' => maybe_serialize($filter_data)
                            );

                            // Unhook wp_targeted_link_rel() filter from WP 5.1 corrupting serialized data.
                            remove_filter('content_save_pre', 'wp_targeted_link_rel');
                            add_filter('pre_wp_unique_post_slug', 'flrt_force_non_unique_slug', 10, 2);

                            // Slash data.
                            // WP expects all data to be slashed and will unslash it (fixes '\' character issues)
                            $to_update = wp_slash($to_update);

                            wp_update_post($to_update);

                            remove_filter('pre_wp_unique_post_slug', 'flrt_force_non_unique_slug', 10);
                        }
                    }
                }
            }
        }

        // Save Set fields
        $set_fields_key = self::FIELD_NAME_PREFIX;

        if (!empty($filterSets[$post->post_excerpt][$set_fields_key]) && $saveFiltersTrigger && $allFiltersValid) {
            $setFields = $filterSets[$post->post_excerpt][$set_fields_key];
            $setFields['ID'] = $post_id;
            $setFields['title'] = isset($post->post_title) ? $post->post_title : '';

            parent::saveSetFields($setFields);
        }

        if (!$saveFiltersTrigger || !$allFiltersValid) {
            wp_delete_post($post_id, true);
        }

        add_action('save_post', array($this, 'saveSet'), 10, 2);

        return $post;
    }
}