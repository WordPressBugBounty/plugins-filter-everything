<?php

namespace FilterEverything\Filter;
trait PluginHelpers
{
    public function sendSetLocationTerms()
    {
        $postData   = Container::instance()->getThePost();
        $filterSet  = Container::instance()->getFilterSetService();

        $full_label = true;
        $post_type  = isset( $postData['postType'] ) ? $postData['postType'] : 'post';
        $wpPageType = isset( $postData['wpPageType'] ) ? $postData['wpPageType'] : false;
        $post_id    = isset( $postData['postId'] ) ? $postData['postId'] : '';
        $nonce      = isset( $postData['_wpnonce'] ) ? $postData['_wpnonce'] : false;
        $fieldkey   = isset( $postData['fieldKey'] ) ? $postData['fieldKey'] : 'post_name';

        $errorResponse  = array(
            'postId' => $post_id,
            'message' => esc_html__('An error occurred. Please, refresh the page and try again.', 'filter-everything')
        );

        if( ! wp_verify_nonce( $nonce, FilterSet::NONCE_ACTION ) ){
            wp_send_json_error($errorResponse);
        }

        $set = $filterSet->getSet( $post_id );

        // Get prepared field with populated saved values
        if( ! empty( $set ) && $set['post_type']['value'] == $post_type ){
            $location   = $set[$fieldkey];
        }else{
            // Or create new one, if it is new set
            $fields     = $filterSet->getFieldsMapping();
            $location   = $fields[$fieldkey];
        }

        if( $fieldkey === 'apply_button_post_name' ){
            $full_label = false;
        }

        $location['options'] = flrt_get_set_location_terms( $wpPageType, $post_type, $full_label );

        $response = [];

        ob_start();

        echo flrt_render_input($location);

        $response['isLimitFilterSet'] = $filterSet->under_limit_filter_set($post_id, $post_type);
        $response['html'] = ob_get_clean();

        wp_send_json_success($response);
        die();
    }

    public function showLocationFields( &$set_settings_fields )
    {
        $location_fields = flrt_extract_vars( $set_settings_fields, array('wp_page_type', 'post_name') );
        ?>
        <tr class="wpc-filter-tr <?php echo esc_attr( $location_fields['wp_page_type']['class'] ); ?>-tr"<?php flrt_maybe_hide_row( $location_fields['wp_page_type'] ); ?>><?php

            flrt_include_admin_view('filter-field-label', array(
                    'field_key'  => 'wp_page_type',
                    'attributes' =>  $location_fields['wp_page_type']
                )
            );
            ?>
            <td class="wpc-filter-field-td wpc-filter-field-location-td">
                <div class="wpc-field-wrap <?php echo esc_attr( $location_fields['wp_page_type']['id'] ); ?>-wrap">
                    <?php echo flrt_render_input( $location_fields['wp_page_type'] ); // Already escaped in function ?>
                </div>
                <div class="wpc-field-wrap <?php echo esc_attr( $location_fields['post_name']['id'] ); ?>-wrap">
                    <?php echo flrt_render_input( $location_fields['post_name'] ); // Already escaped in function ?>
                </div>
            </td>
        </tr>
        <?php
    }
    public function isFilteredQuery( $result, $query_to_check )
    {
        $wpManager  = Container::instance()->getWpManager();
        $sets       = $wpManager->getQueryVar('wpc_page_related_set_ids');

        if( empty( $sets ) ){
            return false;
        }

        $filterSet = Container::instance()->getFilterSetService();
        remove_filter('wpc_prepare_filter_set_parameters', [$this, 'prepareSetParameters'], 10, 2);

        foreach ( $sets as $set ){

            $theSet = $filterSet->getSet( $set['ID'] );

            if( isset( $theSet['wp_filter_query']['value'] ) && $theSet['wp_filter_query']['value'] ){
                $savedValue = $theSet['wp_filter_query']['value'];
                // We have to avoid recognize similar queries that have the same hash
                if(!empty($query_to_check->get('flrt_query_hash')) && $savedValue === $query_to_check->get('flrt_query_hash') /*&& isset($set['query_on_the_page']) &&
                    $set['query_on_the_page'] === true*/
                ){
                    $result[] = $set['ID'];
                }
            }
        }

        if( empty( $result ) ){
            // Let's do it again.
            foreach ( $sets as $set ) {

                $theSet = $filterSet->getSet($set['ID']);

                if( isset( $theSet['wp_filter_query']['value'] ) && $theSet['wp_filter_query']['value'] ) {
                    $savedValue = $theSet['wp_filter_query']['value'];

                    // For backward compatibility, when savedValue isn't specified and is default -1
                    if ($query_to_check->is_main_query() && $savedValue === '-1') {
                        $result[] = $set['ID'];

                        break;
                    }

                    // For All Post type archive pages
                    if (isset($theSet['post_name']['value']) && $theSet['post_name']['value'] === '1' && $query_to_check->is_main_query()) {
                        if( isset( $theSet['use_apply_button']['value'] ) && $theSet['use_apply_button']['value'] === 'yes' ){
                            continue;
                        }else{
                            $result[] = $set['ID'];
                            break;
                        }
                    }
                }
            }
        }

        add_filter('wpc_prepare_filter_set_parameters', [$this, 'prepareSetParameters'], 10, 2);

        unset($filterSet, $wpManager);

        return $result;
    }

    public function burpOutAllWpQueries( $wp_query )
    {
        $postData = Container::instance()->getThePost();
        if( isset( $postData['action'] ) && $postData['action'] === 'wpc_get_wp_queries' ){

            $do_security_check = true;

            if( flrt_wpml_active() ){
                $wpml_settings = get_option( 'icl_sitepress_settings' );
                if ( isset( $wpml_settings['language_negotiation_type'] ) && $wpml_settings['language_negotiation_type'] === '2' ) {
                    $do_security_check = false;
                }
            }

            if ( $do_security_check ) {
                if( ! isset( $postData['_wpnonce'] ) || ! wp_verify_nonce( $postData['_wpnonce'], FilterSet::NONCE_ACTION ) ){
                    return $wp_query;
                }

                if( ! current_user_can( flrt_plugin_user_caps() ) ) {
                    return $wp_query;
                }
            }

            add_action( 'wp_footer', [$this, 'showCollectedWpQueries'] );
        }

        return $wp_query;
    }

    /**
     * Checks if provided WP_Query is filtered query
     * @param $result
     * @param $query_to_check
     * @return array with filter set IDs related to the Query
     */


    public function showCollectedWpQueries()
    {
        global $flrt_queries;
        $filterSet = Container::instance()->getFilterSetService();
        $postData = Container::instance()->getThePost();
        $postType = isset($postData['postType']) ? $postData['postType'] : false;
        $postId = isset($postData['postId']) ? $postData['postId'] : false;
        $flatten_queries = $this->flatAllWpQueriesList($flrt_queries, $postType);
        $fieldName = 'wp_filter_query';
        // For Any case if the 'flrt_render_input()' return false;
        $postTypeObject = get_post_type_object($postType);
        $postNameLabel = isset($postTypeObject->labels->name) ? $postTypeObject->labels->name : $postType;

        $theSet = $filterSet->getSet($postId);
        // Set includes field configuration arrays together with saved values
        $select_atts = isset($theSet[$fieldName]) ? $theSet[$fieldName] : false;
        if ($select_atts) {
            $select_atts['options'] = $flatten_queries['options'];
        }

        // Remove all additional HTML from the 'wp_filter_query' Select field
        remove_all_filters('wpc_input_type_select');
        if (!empty($flatten_queries['disabled'])) {
            $select_atts['disabled'] = [];
            foreach ($flatten_queries['disabled'] as $hash => $val) {
               $select_atts['disabled'][] = $hash;
            }
        }
        if (!empty($select_atts['disabled'])) {
            if (count($select_atts['options']) === count($select_atts['disabled'])) {
                $select_atts['options']['-1'] = sprintf(esc_html__('No %s enabled lists found on the page', 'filter-everything'), $postTypeObject->labels->name);
                $select_atts['selected'] = '-1';
            }
        }

        $selectField = flrt_render_input($select_atts);

        if( ! $selectField ) {

            $selectField  = '<div><select class="wpc-field-wp-filter-query" id="wpc_set_fields-wp_filter_query" name="wpc_set_fields[wp_filter_query]">'."\n";
            $selectField .= '<option value="-1" >'.sprintf( esc_html__('No list of %s found on this page', 'filter-everything' ), flrt_lcfirst($postNameLabel) ).'</option>'."\n";
            $selectField .= '</select></div>'."\n";
        }

        echo '<div>'.$selectField.'</div>';

        echo '<div><div id="wpc_query_vars">';
        if( isset( $flatten_queries['query_vars'] ) && ! empty( $flatten_queries['query_vars'] ) ){
            foreach ( $flatten_queries['query_vars'] as $hash => $vars ){
                $hiddenFieldName = esc_attr( $filterSet::FIELD_NAME_PREFIX . '[wp_filter_query_vars]['.$hash.']' );
                echo '<input type="hidden" name="'.$hiddenFieldName.'" value="'.esc_attr( $vars ).'"/>'."\n";
            }
        }
        echo '</div></div>';

    }

    /**
     * Converts queries array from multidimensional to simple
     * Optionally removes queries with unnecessary post type
     * @param array $queries
     * @param false|string $postType
     */
    public function flatAllWpQueriesList( $queries, $postType = false )
    {
        $flatten = [];

        $postTypeObject = get_post_type_object( $postType );
        $postNameLabel = isset( $postTypeObject->labels->name) ? $postTypeObject->labels->name :  $postType;

        if( empty( $queries ) ){
            $flatten['options']['-1'] = sprintf( esc_html__('No list of %s found on this page', 'filter-everything' ), flrt_lcfirst($postNameLabel) );
            return $flatten;
        }

        foreach ( $queries as $hash => $single_query ){
            foreach ($single_query as $index => $values ) {
                if( $postType ){
                    if( ! in_array( $postType, $values['post_types'], true ) ){
                        continue;
                    }
                }

                // We should use another label numeration logic
                $new_hash = md5( $hash . $index );
                $flatten['options'][ $new_hash ]    = $values['label'];
                if( ! empty( $values['disabled'] ) && $values['disabled'] === true ){
                    $flatten['disabled'][ $new_hash ]    = $values['disabled'];
                }
                $flatten['query_vars'][ $new_hash ] = $values['query_vars'];
            }
        }

        // Add numeration for equal labels
        if( ! empty( $flatten['options'] ) ){
            $copy_flatten = $flatten['options'];
            $count_labels = array_count_values($copy_flatten);
            $i = [];

            foreach ( $copy_flatten as $hash => $label ){
                if( $count_labels[$label] > 1 ){
                    if( ! isset( $i[$label] ) ){
                        $i[$label] = 0;
                    }
                    $i[$label]++;
                    if(str_contains( $label, '»' ) ){
                        $changed_label = str_replace("»", " #%s»", $label);
                        $new_label = sprintf( $changed_label, $i[$label] );
                    }

                    $flatten['options'][ $hash ] = $new_label;
                }
            }
        }else{
            $flatten['options']['-1'] = sprintf( esc_html__('No list of %s found on this page', 'filter-everything' ), flrt_lcfirst($postNameLabel) );
            return $flatten;
        }

        return $flatten;
    }

    public function validationLocationEntities( $possibleEntities, $setFields )
    {
        $possibleEntities = flrt_get_set_location_terms( $setFields['wp_page_type'], $setFields['post_type'] );
        return array_keys( $possibleEntities );
    }

    public function validationWpPageTypeEntities( $possibleWpPageTypes )
    {
        $possibleWpPageTypes = $this->flattenValues( flrt_get_set_location_groups() );
        return array_keys( $possibleWpPageTypes );
    }

    public function prepareSetParameters( $defaults, $set_post  )
    {
        // Set location dropdown fields related to saved post_type and wp_page_type
        $postType = $set_post->post_excerpt ? $set_post->post_excerpt : 'post';
        $unserialized = maybe_unserialize( $set_post->post_content );

        // For backward compatibility. From v.1.1.24
        if( isset( $unserialized['wp_page_type'] ) ){
            $unserialized['wp_page_type'] = str_replace(":", "___", $unserialized['wp_page_type']);
        }

        $wpPageType = isset( $unserialized['wp_page_type'] ) ? $unserialized['wp_page_type'] : $this->detectWpPageTypeByLocation( $set_post->post_name );
        $applyButtonPageType = isset( $unserialized['apply_button_page_type'] ) ? $unserialized['apply_button_page_type'] : 'no_page___no_page';

        $defaults['post_name']['options'] = flrt_get_set_location_terms( $wpPageType, $postType );
        $defaults['apply_button_post_name']['options'] = flrt_get_set_location_terms( $applyButtonPageType, $postType, false );

        return $defaults;
    }

    public function detectWpPageTypeByLocation( $locationValue )
    {
        $wpPostType = 'common___common';


        if( $locationValue == '1' ){
            $wpPostType = 'common___common';
        }else if( mb_strpos( $locationValue, 'author' ) !== false ){
            $wpPostType = 'author___author';

        }else if( mb_strpos( $locationValue, 'post_type' ) !== false ){
            $postTypeParts = explode("___", $locationValue);
            $postTypeName  = $postTypeParts[0];
            $wpPostType    = 'post_type___'.$postTypeName;
        }else if( $locationValue ){
            $taxonomyParts = explode("___", $locationValue);
            $taxName       = $taxonomyParts[0];
            $wpPostType = 'taxonomy___'.$taxName;
        }
        ;
        return $wpPostType;
    }

    public function flattenValues( $entities )
    {
        if( empty( $entities ) ){
            return $entities;
        }
        $flat_entities = [];

        array_walk_recursive( $entities, function ( $value, $key ) use ( &$flat_entities ) {
            if( $key !== 'group_label' /*&& isset( $value['label'] ) && $value['label'] */){
                $flat_entities[ $key ] = $value;
            }
        }, $flat_entities );

        return $flat_entities;
    }

    public function legacyPrepareWpPageTypeValue( $prepared )
    {
        if( isset($prepared['post_name']['value']) && $prepared['post_name']['value'] ){
            if( ! isset( $prepared['wp_page_type']['value'] ) ){
                $prepared['wp_page_type']['value'] = $this->detectWpPageTypeByLocation( $prepared['post_name']['value']);
            }
        }
        return $prepared;
    }

    public function filterSetPostTypeCol( $columns )
    {
        $newColumns = [];

        foreach ( $columns as $columnId => $columnName ) {

            $newColumns[$columnId] = $columnName;
            if( $columnId === 'title' ){
                $newColumns['location'] = esc_html__( 'Available on', 'filter-everything' );
            }
        }

        return $newColumns;
    }

    // Show selected location in the Available on column of admin Filter Sets list
    public function filterSetPostTypeColContent( $column_name, $post_id )
    {
        if ( 'location' == $column_name ){
            $fss        = Container::instance()->getFilterSetService();
            $theSet     = $fss->getSet( $post_id );

            $wpPageType = isset( $theSet['wp_page_type'] ) ? $theSet['wp_page_type'] : '';
            $location   = isset( $theSet['post_name'] ) ? $theSet['post_name'] : '';
            $post_type  = isset( $theSet['post_type']['value'] ) ? $theSet['post_type']['value'] : 'post';

            if( $label = $this->getSetLocationLabel( $wpPageType['options'], $location['value'] , $post_type) ){
                echo esc_html( $label );
            }

            unset($fss);
        }
    }

    private function getSetLocationLabel( $options, $value, $post_type = 'post' )
    {
        $entityLabel = $entityGroup = $entity = '';

        if( ! isset($options['common']['entities']) ){
            return false;
        }

        $parts          = explode("___", $value);
        $selectedEntity = $parts[0];
        $selectedValue  = isset($parts[1]) ? $parts[1] : $parts[0];

        unset($parts);

        if( $selectedValue == '1' && $selectedEntity == '1' ){
            $entityGroup = 'common';
            $entity      = 'common';
            $entityLabel = esc_html__('All archive pages for this Post Type', 'filter-everything');
        }else{
            foreach( $options as $section ){
                foreach( $section['entities'] as $groupAndEntity => $label ){
                    $parts = explode("___", $groupAndEntity);
                    $entityGroup = $parts[0];
                    $entity = $parts[1];

                    unset($parts);

                    if( $entity === $selectedEntity ){
                        $entityLabel = $label;
                        break;
                    }

                    $entityGroup = $entity = '';

                }

                if( $entityGroup && $entity ){
                    break;
                }
            }
        }

        if( $entityGroup && $entity && $entityLabel ) {

            switch ( $entityGroup ){
                case 'common':

                    $commonPages = flrt_get_common_location_terms( $post_type );

                    if( isset( $commonPages[ $selectedEntity .'___'. $selectedValue ]['label'] ) ){
                        $toShow = $commonPages[ $selectedEntity .'___'. $selectedValue ]['label'];
                    }else{
                        $toShow = $entityLabel;
                    }

                    break;
                case 'taxonomy':
                    // could be -1
                    if( $selectedValue == '-1' ){
                        $toShow = sprintf(esc_html__('Any %s', 'filter-everything'), $entityLabel );
                    }else{
                        $term   = get_term( $selectedValue, $selectedEntity );
                        $name   = ( is_wp_error( $term ) || is_null( $term ) ) ? '' : $term->name;
                        $toShow = sprintf(esc_html__('%s: %s', 'filter-everything'), $entityLabel, $name);
                    }

                    break;

                case 'post_type':
                    // could be -1
                    if( $selectedValue == '-1' ){
                        $toShow = sprintf(esc_html__('Any %s', 'filter-everything'), $entityLabel );
                    }else{
                        $name = get_the_title($selectedValue);
                        $toShow = sprintf(esc_html__('%s: %s', 'filter-everything'), $entityLabel, $name);
                    }
                    break;
                case 'author':
                    // could be -1
                    if( $selectedValue == '-1' ){
                        $toShow = sprintf(esc_html__('Any %s', 'filter-everything'), $entityLabel );
                    }else{
                        $author = get_userdata($selectedValue);
                        $name   = ( $author ) ? $author->data->display_name : '';
                        $toShow = sprintf(esc_html__('%s: %s', 'filter-everything'), $entityLabel, $name);
                    }
                    break;

            }

            return apply_filters( 'wpc_set_location_label', $toShow, $selectedValue, $entityGroup, $entity, $entityLabel );
        }

        return false;
    }

    /**
     * @param $filterSet array
     * @param $queriedObject array
     * @return array
     */
    public function findRelevantSets( $filterSet, $queriedObject )
    {
        // Singular page
        if( isset( $queriedObject[ 'post_id' ] ) ){
            $sets = $this->getSetIdForSingular( $queriedObject[ 'post_types' ], $queriedObject[ 'post_id' ] );
            if( $sets !== false ){
                return $sets;
            }

            //@todo Try to find common set for all pages this post type
            $sets = $this->getSetIdForSingular( $queriedObject[ 'post_types' ], '-1' );
            if( $sets !== false ){
                return $sets;
            }
        }

        // We need to process Common WordPress pages first as more prioritized
        // Than archive pages
        if( isset( $queriedObject[ 'common' ] ) ){
            $sets = $this->getSetIdForCommon( $queriedObject[ 'common' ] );
            if( ! empty( $sets )){
                return $sets;
            }
        }

        // Get filter set specified for term (if exists)
        if( isset( $queriedObject[ 'taxonomy' ] ) ){
            // get term related Filter Set
            $sets = $this->getSetIdForTerm( $queriedObject[ 'taxonomy' ], $queriedObject[ 'term_id' ] );

            if( $sets !== false ){
                return $sets;
            }
            // Try to find common set for taxonomy archive
            $sets = $this->getSetIdForTerm( $queriedObject[ 'taxonomy' ], '-1' );

            if( $sets !== false ){
                return $sets;
            }
        }

        if( isset( $queriedObject[ 'author' ] ) ){
            $sets = $this->getSetIdForAuthor( $queriedObject[ 'author' ] );
            if( $sets !== false ){
                return $sets;
            }
        }

        return apply_filters( 'wpc_pro_relevant_set_ids', $filterSet, $queriedObject, $this );
    }

    /**
     * @param $common array
     * @return false|mixed
     */
    private function getSetIdForCommon( $common )
    {
        $storeKey   = 'set_common';
        $searchKey  = [];

        foreach( $common as $value ){
            if( ! in_array( $value, array(
                'page_on_front',
                'page_for_posts',
                'search_results',
                'shop_page' ) ) ){
                return false;
            }

            $storeKey .= '_'.$value;
            $searchKey[] = "common___".$value;
        }

        return $this->querySets( $storeKey, $searchKey );

    }

    private function getSetIdForSingular( $postTypes, $postId )
    {
        if( ! $postTypes || ! $postId ){
            return false;
        }

        $postType = reset($postTypes);

        $storeKey   = 'set_' . $postType . '_' .$postId;
        $searchKey  = $postType.'___'.$postId;

        return $this->querySets( $storeKey, $searchKey );

    }

    /**
     * @return int|false
     */
    private function getSetIdForTerm( $taxonomy, $termId )
    {
        if( ! $taxonomy || ! $termId ){
            return false;
        }

        $storeKey   = 'set_' . $taxonomy . '_' .$termId;
        $searchKey  = $taxonomy.'___'.$termId;

        $sets = $this->querySets( $storeKey, $searchKey );

        if( ! $sets ){
            $parentTermId = wp_get_term_taxonomy_parent_id( $termId, $taxonomy );

            if($parentTermId){
                return $this->getSetIdForTerm( $taxonomy, $parentTermId );
            }
        }

        return $sets;
    }

    /**
     * @param $author user_id|user slug
     * @return int|false
     */
    private function getSetIdForAuthor( $authorSlug )
    {
        $user_id = false;

        if( ! $authorSlug ){
            return false;
        }

        if( $user = get_user_by( 'slug', $authorSlug ) ){
            $user_id = $user->ID;
        }

        $storeKey   = 'set_author_' . $user_id;
        $searchKey  = 'author___'.$user_id;

        $sets = $this->querySets( $storeKey, $searchKey );

        // Try to find common author page sets
        if( ! $sets ){
            $user_id    = '-1';
            $storeKey   = 'set_author_' . $user_id;
            $searchKey  = 'author___'.$user_id;

            $sets = $this->querySets( $storeKey, $searchKey );
        }

        return $sets;
    }
    public function querySets( $storeKey, $searchKey )
    {
        $container = Container::instance();

        if( ! $sets = $container->getParam( $storeKey ) ){
            global $wpdb;
            $sql        = [];
            $is_common  = false;
            $IN         = false;
            $pll_lang_id = false;
            $is_fitler_set_translatable = false;

            if( ! is_array( $searchKey ) ){
                $searchKey = array( $searchKey );
            }

            foreach( $searchKey as $set_key ){
                $set_key_parts = explode("___", $set_key);

                if ( isset( $set_key_parts[1] ) && $set_key_parts[1] === '-1' ) {
                    $is_common = true;
                }

                if ( isset( $set_key_parts[0] ) && $set_key_parts[0] === 'common' ) {
                    $is_common = true;
                }

                $pieces[] = $wpdb->prepare( "%s", $set_key );
            }

            if( flrt_wpml_active() ){
                $wpml_settings = get_option( 'icl_sitepress_settings' );
                if( isset( $wpml_settings['custom_posts_sync_option'][FLRT_FILTERS_SET_POST_TYPE] ) ){
                    if( $wpml_settings['custom_posts_sync_option'][FLRT_FILTERS_SET_POST_TYPE] === '1' ){
                        $is_fitler_set_translatable = true;
                    }
                }
            }

            $IN = implode(", ", $pieces );

            $sql[] = "SELECT {$wpdb->posts}.ID,{$wpdb->posts}.post_content,{$wpdb->posts}.post_excerpt,{$wpdb->posts}.post_name";
            $sql[] = "FROM {$wpdb->posts}";

            // We check if it is common because other thing have their own ID
            // and do not require separate language versions
            if ( flrt_wpml_active() && defined('ICL_LANGUAGE_CODE') && $is_common && $is_fitler_set_translatable ) {
                $sql[] = "LEFT JOIN {$wpdb->prefix}icl_translations AS wpml_translations";
                $sql[] = "ON {$wpdb->posts}.ID = wpml_translations.element_id";
                $sql[] = "AND wpml_translations.element_type IN(";
                $sql[] = $wpdb->prepare( "CONCAT('post_', '%s')", FLRT_FILTERS_SET_POST_TYPE );
                $sql[] = ")";
            }

            // Check common if Polylang PRO is active and Filter Set is translatable post type
            if( flrt_pll_pro_active() && defined('FLRT_ALLOW_PLL_TRANSLATIONS') && FLRT_ALLOW_PLL_TRANSLATIONS && $is_common ){
                if( function_exists('pll_current_language') && function_exists('pll_the_languages') ) {
                    $pll_current_language   = pll_current_language();
                    $pll_languages          = pll_the_languages(array('raw' => 1));
                    if ( $pll_current_language && isset( $pll_languages[$pll_current_language]['id'] ) ) {
                        $pll_lang_id = $pll_languages[$pll_current_language]['id'];

                        $sql[] = "LEFT JOIN {$wpdb->term_relationships}";
                        $sql[] = "ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
                    }
                }
            }

            $sql[] = "WHERE 1=1";

            $sql[] = "AND ( ";
            $sql[] = "{$wpdb->posts}.post_name IN ( {$IN} )";
            $sql[] = "OR {$wpdb->posts}.ID IN ( ";
            $sql[] = "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}";
            $sql[] = "LEFT JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )";
            $sql[] = "WHERE 1=1";
            $sql[] = $wpdb->prepare( "AND  {$wpdb->postmeta}.meta_key = %s", FLRT_APPLY_BUTTON_META_KEY);
            $sql[] = "AND {$wpdb->postmeta}.meta_value IN ( {$IN} )";
            $sql[] = ")";
            $sql[] = ")";

            $sql[] = $wpdb->prepare("AND {$wpdb->posts}.post_type = '%s'", FLRT_FILTERS_SET_POST_TYPE );
            $sql[] = "AND ( ({$wpdb->posts}.post_status = 'publish') )";

            if( flrt_wpml_active() && defined( 'ICL_LANGUAGE_CODE' ) && $is_common && $is_fitler_set_translatable ){
                $sql[] = $wpdb->prepare("AND wpml_translations.language_code = '%s'", ICL_LANGUAGE_CODE );
            }

            if( flrt_pll_pro_active() && defined('FLRT_ALLOW_PLL_TRANSLATIONS') && FLRT_ALLOW_PLL_TRANSLATIONS && $is_common ){
                if( $pll_lang_id ){
                    $sql[] = $wpdb->prepare("AND {$wpdb->term_relationships}.term_taxonomy_id IN (%d)", $pll_lang_id );
                }
            }

            // First Set is that has larger value Menu order or was created first.
            $sql[] = "ORDER BY {$wpdb->posts}.menu_order DESC, {$wpdb->posts}.ID ASC";

            $sql = implode(' ', $sql );
            $setPosts = $wpdb->get_results( $sql, OBJECT );

            if( ! empty( $setPosts ) ){
                $sets = flrt_is_query_on_page( $setPosts, $searchKey );
            }else{
                return false;
            }

            $container->storeParam( $storeKey, $sets );
        }

        unset( $container );

        return $sets;
    }
}