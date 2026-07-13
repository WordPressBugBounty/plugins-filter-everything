<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class PostMetaNumEntity implements Entity
{
    use PostMetaTrait;
    public $items           = [];
    /**
     * Declared explicitly: assigned from the outside in
     * EntityManager::prepareEntitiesToDisplay(); dynamic properties are
     * deprecated since PHP 8.2.
     */
    public $items_sort = [];

    public $filter = [];

    protected $product_cache  = [];

    public $entityName      = '';

    public $excludedTerms   = [];

    public $isInclude       = false;

    public $new_meta_query  = [];

    public $postTypes       = [];

    public $wdr_product_ids = [];

    public $is_woo_discount_rules = false;

    public function __construct( $postMetaName, $postType ){
        /**
         * @feature clean code from unused methods
         */
        $this->entityName = $postMetaName;
        if(flrt_is_woo_discount_rules()){
            $this->is_woo_discount_rules = true;
        }
        $this->setPostTypes( array($postType) );
    }

    public function setPostTypes( $postTypes = false )
    {
        $wpManager          = Container::instance()->getWpManager();
        $wpQueriedObject    = $wpManager->getQueryVar('wp_queried_object');

        if ($postTypes) {
            $this->postTypes = $postTypes;
        }elseif( ! empty( $wpQueriedObject['post_types'] ) ){
            $this->postTypes = $wpQueriedObject['post_types'];
        }else{
            $this->postTypes = array('post');
        }

        if( flrt_is_woocommerce()
            && in_array( 'product', $this->postTypes )
            && ! in_array( 'product_variation', $this->postTypes ) ){
            $this->postTypes[] = 'product_variation';
        }

        $this->getAllExistingTerms();
    }

    public static function inputName( $slug, $edge = 'min' )
    {
        return $edge . '_' . $slug;
    }

    public function setExcludedTerms( $excludedTerms, $isInclude )
    {
        $this->excludedTerms = $excludedTerms;
        $this->isInclude     = $isInclude;
    }

    public function getName()
    {
        return $this->entityName;
    }

    function excludeTerms( $terms )
    {
        return $terms;
    }

    function getTerms()
    {
        return $this->excludeTerms( $this->getAllExistingTerms() );
    }

    /**
     * @param int $id term id
     * @return false|object term object of false
     */
    public function getTerm( $termId ){
        if( ! $termId ){
            return false;
        }

        foreach ( $this->getTerms() as $term ){
            if( $termId == $term->term_id ){
                return $term;
            }
        }

        return false;
    }

    public function getTermId( $slug )
    {
        /**
         * Post meta num value has no typical ID, so slug will be instead
         */
        return $slug . '_' . $this->getName();
    }

    /**
     * @return array list of term_id and names useful to create Select dropdown
     */
    public function getTermsForSelect()
    {
        $toSelect = [];
        foreach ( $this->getTerms() as $term ) {
            $toSelect[$term->slug] = $term->name;
        }

        return $toSelect;
    }

    public function getTermsForSelect2()
    {
        $toSelect = [];
        foreach ( $this->getTerms() as $term ) {
            $toSelect[] = array( 'id' => $term->slug, 'text' =>$term->name );
        }

        return $toSelect;
    }

    function getAllExistingTerms( $force = false )
    {
        if( empty( $this->items ) || $force ) {
            $this->items = $this->selectTerms();
        }
        return $this->items;
    }

    public function isDecimal( $step = 0, $value = 0 )
    {
        if( strpos( $step, '.') !== false ){
            return true;
        }

        if( strpos( $value, '.') !== false ){
            return true;
        }

        return false;
    }

    function populateTermsWithPostIds( $setId, $post_type )
    {
        // Does nothing. It was already done before.
    }

    /**
     * @param array $alreadyFilteredPosts
     * @return array
     */
    public function selectTerms( $alreadyFilteredPosts = [] ) {
        global $wpdb;

        $IN             = false;
        $key_in         = '';
        $new_result     = [];
        $min_and_max    = [
            'min' => 0,
            'max' => 0
        ];
        $post_and_types = [];
        $post_and_meta_values = [];
        $translatable_post_type_exists = false;

        /**
         * Set Post types
         */
        if( ! empty( $this->postTypes ) && isset($this->postTypes[0]) && $this->postTypes[0] ){
            foreach ( $this->postTypes as $postType ){
                $key_in .= '_' . $postType;
                $pieces[] = $wpdb->prepare( "%s", $postType );
            }

            $IN = implode(", ", $pieces );
        }

        /**
         * Set transient key
         */
        $transient_key = flrt_get_terms_transient_key( 'post_meta_num_'. $this->getName() . $key_in );

        $result = flrt_get_transient( $transient_key );

        // Cache format v2: ['vals' => [post_id => float|float[]], 'types' => [post_id => post_type]].
        // Legacy format cached raw SQL rows — detect and rebuild those.
        if ( ! is_array( $result ) || ! isset( $result['vals'], $result['types'] ) ) {
            // Get all post meta values
            $sql[] = "SELECT {$wpdb->postmeta}.post_id,{$wpdb->postmeta}.meta_value,{$wpdb->posts}.post_type";
            $sql[] = "FROM {$wpdb->postmeta}";
            $sql[] = "LEFT JOIN {$wpdb->posts} ON ({$wpdb->postmeta}.post_id = {$wpdb->posts}.ID)";

            /**
             * If post type is translatable with WPML, get post meta values only with current language
             */
            if( flrt_wpml_active() && defined( 'ICL_LANGUAGE_CODE' ) ){

                $wpml_settings = get_option( 'icl_sitepress_settings' );

                foreach ( $this->postTypes as $type ) {
                    if( isset( $wpml_settings['custom_posts_sync_option'][$type] ) ){
                        if( $wpml_settings['custom_posts_sync_option'][$type] === '1' ){
                            $translatable_post_type_exists = true;
                            break;
                        }
                    }
                }

                if ( $translatable_post_type_exists ) {
                    $sql[] = "LEFT JOIN {$wpdb->prefix}icl_translations AS wpml_translations";
                    $sql[] = "ON {$wpdb->postmeta}.post_id = wpml_translations.element_id";

                    if (!empty($this->postTypes)) {

                        $sql[] = "AND wpml_translations.element_type IN(";

                        foreach ($this->postTypes as $type) {
                            $LANG_IN[] = $wpdb->prepare("CONCAT('post_', '%s')", $type);
                        }
                        $sql[] = implode(",", $LANG_IN);

                        $sql[] = ")";
                    }
                }
            }
            /**
             * There is NULL problem because posts with meta_value = '' are also included in the list
             * And condition (NULL <= 0) is true
             * */
            $sql[] = "WHERE {$wpdb->postmeta}.meta_key = %s";
            $sql[] = "AND {$wpdb->postmeta}.meta_value IS NOT NULL";

            if( $IN ){
                $sql[] = "AND {$wpdb->posts}.post_type IN( {$IN} )";
            }

            if( flrt_wpml_active() && defined( 'ICL_LANGUAGE_CODE' ) && $translatable_post_type_exists ){

                $sql[] = $wpdb->prepare("AND wpml_translations.language_code = '%s'", ICL_LANGUAGE_CODE);
            }

            $sql = implode(' ', $sql);

            $e_name     = wp_unslash( $this->entityName );
            $sql        = $wpdb->prepare( $sql, $e_name );

            /**
             * Filters terms SQL-query and allows to modify it
             */
            $sql        = apply_filters( 'wpc_filter_get_post_meta_num_terms_sql', $sql, $e_name );

            $rows = $wpdb->get_results( $sql, ARRAY_A );

            // Aggregate raw rows into the compact v2 maps. A post can have
            // several meta rows with different values (e.g. WC variable
            // products keep one _price row per distinct child price) — such
            // posts get an array of floats instead of a single float.
            $vals  = [];
            $types = [];
            foreach ( $rows as $single_post ) {
                if ( preg_match( '/[^\d\.\-]+/', $single_post['meta_value'] ) ) {
                    continue;
                }

                $post_id    = (int) $single_post['post_id'];
                $meta_value = (float) $single_post['meta_value'];

                if ( isset( $vals[ $post_id ] ) ) {
                    if ( ! is_array( $vals[ $post_id ] ) ) {
                        $vals[ $post_id ] = array( $vals[ $post_id ] );
                    }
                    $vals[ $post_id ][] = $meta_value;
                } else {
                    $vals[ $post_id ] = $meta_value;
                }

                $types[ $post_id ] = $single_post['post_type'];
            }
            unset( $rows );

            $result = array( 'vals' => $vals, 'types' => $types );
            unset( $vals, $types );

            flrt_set_transient( $transient_key, $result, FLRT_TRANSIENT_PERIOD_HOURS * HOUR_IN_SECONDS );
        }

        $meta_vals  = isset( $result['vals'] )  ? $result['vals']  : [];
        $meta_types = isset( $result['types'] ) ? $result['types'] : [];

        if( ! empty( $meta_vals ) ) {

            $postsIn_flipped = array_flip( $alreadyFilteredPosts );
            $wpManager      = Container::instance()->getWpManager();
            $queried_values = $wpManager->getQueryVar( 'queried_values', [] );
            $fss        = Container::instance()->getFilterSetService();
            $filter_slug    = false;
            $setId = false;

            /**
             * Check if this filter was queried
             */
            foreach ( $queried_values as $slug => $filter ) {
                if ( $filter[ 'e_name' ] === $this->getName() ) {
                    $filter_slug = $slug;
                    $setId = $filter['parent'];
                    break;
                }
            }

            $max = false;
            $min = false;

            /**
             * If this filter was queried we have to receive its $max and $min values
             */
            if ( $filter_slug ) {
                if ( isset( $queried_values[ $filter_slug ][ 'values' ][ 'max' ] ) ) {
                    $max  = (float) $queried_values[ $filter_slug ][ 'values' ][ 'max' ];
                    $max  = apply_filters( 'wpc_unset_num_shift', $max, $this->getName() );
                }

                if ( isset( $queried_values[ $filter_slug ][ 'values' ][ 'min' ] ) ) {
                    $min  = (float) $queried_values[ $filter_slug ][ 'values' ][ 'min' ];
                    $min  = apply_filters( 'wpc_unset_num_shift', $min, $this->getName() );
                }
            }

            if($this->is_woo_discount_rules && (strpos($this->entityName, '_price') !== false)){
                $wdr_woo_discount_rules = $this->getWooDiscountRulesClass();
                $this->preloadProducts( array_keys( $meta_vals ) );
            }

            foreach ( $meta_vals as $post_id => $post_values ) {
                /**
                 * If there are already filtered posts, we have to skip posts
                 * that are out of the queried list
                 */
                if( ! empty( $alreadyFilteredPosts ) ) {
                    if( ! isset( $postsIn_flipped[ $post_id ] ) ) {
                        continue;
                    }
                }

                if ($this->is_woo_discount_rules) {
                    if (strpos($this->entityName, '_price') !== false) {
                        $product = $this->getProductCached($post_id);
                        if ($product) {
                            $wdr_product_has_sale = $wdr_woo_discount_rules->getProductPriceToDisplay($product);
                            if ($wdr_product_has_sale) {
                                $post_values = (float) $wdr_product_has_sale['discounted_price'];
                            }
                        }
                    }
                }

                /**
                 * We have to generate and fill two arrays
                 * First to detect $min and $max values
                 * Second to map post_types with post IDs
                 */
                foreach ( (array) $post_values as $meta_value ) {
                    $post_and_meta_values[ $post_id ][] = $meta_value;
                    $new_result[] = $meta_value;

                    if ( $min !== false && $meta_value < $min ){
                        continue;
                    }

                    if ( $max !== false && $meta_value > $max ){
                        continue;
                    }

                    if($this->is_woo_discount_rules){
                        $this->wdr_product_ids[] = $post_id;
                    }

                    $post_and_types[ $post_id ] = $meta_types[ $post_id ];
                }
            }

        }
        $abs_min_and_max = [
            'abs_min' =>  0,
            'abs_max' =>  0,
        ];
        if( ! empty( $new_result ) ){
            $min_and_max = [
                'min' => apply_filters( 'wpc_set_num_shift', min( $new_result ), $this->getName(), 'min' ),
                'max' => apply_filters( 'wpc_set_num_shift', max( $new_result ), $this->getName(), 'max' ),
            ];

            $flat = [];
            foreach ($post_and_meta_values as $post_id => $values) {
                foreach ($values as $value) {
                    $flat[$post_id][] = $value;
                }
            }

            $all_values = array_merge(...array_values($post_and_meta_values));

            $abs_min = min($all_values);
            $abs_max = max($all_values);

            $min_post_id = null;
            $max_post_id = null;

            foreach ($post_and_meta_values as $post_id => $values) {
                if (in_array($abs_min, $values)) {
                    $min_post_id = $post_id;
                }
                if (in_array($abs_max, $values)) {
                    $max_post_id = $post_id;
                }
            }

            $abs_min_and_max = [
                'abs_min' => $abs_min,
                'abs_max' => $abs_max,
            ];

            $post_and_meta_values[$min_post_id] = [$min_and_max['min']];
            $post_and_meta_values[$max_post_id] = [$min_and_max['max']];
        }

        $min_and_max = apply_filters( 'wpc_set_min_max', $min_and_max, $this->getName() );
        return $this->convertSelectResult( $min_and_max, $post_and_types, $post_and_meta_values, $abs_min_and_max);
    }

    public function updateMinAndMaxValues( $postsIn )
    {
        if( ! empty( $this->items ) && ! empty( $postsIn ) ){
            $newItems = $this->selectTerms( $postsIn );
            foreach ( $this->items as $index => $term ) {
                if( isset( $this->items[$index]->$index ) ){
                    $this->items[$index]->$index = $newItems[$index]->$index;
                }
            }
        }
    }

    private function createTermName( $edge, $value, $queried_values )
    {
        $name = $edge;
        $queriedFilter = false;

        if( $queried_values ){
            foreach ( $queried_values as $slug => $filter ){
                if( $filter['e_name'] === $this->getName() ){
                    $queriedFilter = $filter;
                    break;
                }
            }

            if ( $queriedFilter ) {
                $name = $name .' '. $slug;
            }
        }

        $name = ($name === 'min') ? esc_html__('min', 'filter-everything') : $name;
        $name = ($name === 'max') ? esc_html__('max', 'filter-everything') : $name;

        if( isset( $queriedFilter['values'][$edge] ) ) {
            $name = $name .' '. $queriedFilter['values'][$edge];
        }else{
            $name = $name .' '. $value;
        }

        return apply_filters( 'wpc_filter_post_meta_num_term_name', $name, $this->getName() );
    }

    public function convertSelectResult( $result, $post_and_types = [], $post_and_meta_values = [], $abs_min_and_max = [] ){
        $return = [];

        if( ! is_array( $result ) ){
            return $return;
        }

        // To make standard format for terms array;
        $i = 1;
        $wpManager      = Container::instance()->getWpManager();
        $queried_values = $wpManager->getQueryVar( 'queried_values' );

        foreach ( $result as $edge => $value ){

            $termObject = new \stdClass();
            $termObject->slug = $edge;
            $termObject->name = $this->createTermName( $edge, $value, $queried_values );
            $termObject->term_id = $edge . '_' . $this->getName();
            $termObject->posts = array_keys( $post_and_types );
            $termObject->meta_values =  $post_and_meta_values;
            $termObject->count = 0;
            $termObject->cross_count = 0;
            $termObject->post_types = $post_and_types; //[];
            $termObject->$edge = $value;
            $termObject->wp_queried  = false;
            $termObject->abs_values  = $abs_min_and_max;

            $return[ $edge ] = $termObject;

            $i++;
        }

        return $return;
    }

    /**
     * @return object WP_Query
     */
    public function addTermsToWpQuery( $queried_value, $wp_query )
    {
        $meta_query = [];
        $key        = $queried_value['e_name'];
        // Add existing Meta Query if present
        $this->importExistingMetaQuery( $wp_query );

        /**
         * @bug for Woo Products if we don't specify Max value it makes it 0.0000
         */
        $min = isset( $queried_value['values']['min'] ) ? $queried_value['values']['min'] : false;
        $max = isset( $queried_value['values']['max'] ) ? $queried_value['values']['max'] : false;

        // Compare with false because $min can be 0
        if ((strpos($key, '_price') !== false) && flrt_is_woocommerce() && $this->is_woo_discount_rules) {
            $query_post_in = $this->wdrAddProductsRangeToFilterQuery($wp_query);
            $wp_query->set('post__in', $query_post_in);
            if (empty($query_post_in)){
                $wp_query->set('posts_per_page', $query_post_in);
            }
        } else {
            if ($min !== false && $max !== false) {
                $min = apply_filters('wpc_unset_num_shift', $min, $this->getName());
                $max = apply_filters('wpc_unset_num_shift', $max, $this->getName());

                $type = ( $this->isDecimal($queried_value['step'], $min) || $this->isDecimal($queried_value['step'], $max) ) ? 'DECIMAL(15,6)' : 'NUMERIC';
                /**
                 * Both bounds must live in ONE clause. WP_Meta_Query gives every
                 * clause its own postmeta JOIN, so separate >= and <= clauses can
                 * match two DIFFERENT meta rows: a post with several values of the
                 * key (e.g. variable product _price rows) passed the range even
                 * when no single value was inside it, and the results disagreed
                 * with the term counters. BETWEEN checks the same row.
                 */
                $meta_query = array(
                    'key'     => $key,
                    'value'   => array( $min, $max ),
                    'compare' => 'BETWEEN',
                    'type'    => $type
                );
                $this->addMetaQueryArray($meta_query);

            } elseif ($min !== false) {
                $min = apply_filters('wpc_unset_num_shift', $min, $this->getName());

                $type = $this->isDecimal($queried_value['step'], $min) ? 'DECIMAL(15,6)' : 'NUMERIC';
                $meta_query = array(
                    'key'     => $key,
                    'value'   => $min,
                    'compare' => '>=',
                    'type'    => $type
                );
                $this->addMetaQueryArray($meta_query);

            } elseif ($max !== false) {
                $max = apply_filters('wpc_unset_num_shift', $max, $this->getName());

                $type = $this->isDecimal($queried_value['step'], $max) ? 'DECIMAL(15,6)' : 'NUMERIC';
                $meta_query = array(
                    'key'     => $key,
                    'value'   => $max,
                    'compare' => '<=',
                    'type'    => $type
                );
                $this->addMetaQueryArray($meta_query);
            }

            if (count($this->new_meta_query) > 1) {
                $this->new_meta_query['relation'] = 'AND';
            }
        }

        $wp_query->set('meta_query', $this->new_meta_query);

        $this->new_meta_query = [];

        return $wp_query;
    }

    public function wdrAddProductsRangeToFilterQuery($wp_query)
    {
        $args = array(
            'status'    => 'publish',
            'limit'     => -1,
            'return'    => 'ids'
        );

        //Added for work woo_discount_rules and query hash
        $args['flrt_wdr_pagination'] = true;

        if (isset($wp_query->queried_object->taxonomy) && isset($wp_query->queried_object->taxonomy)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $wp_query->queried_object->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => array($wp_query->queried_object->term_id),
                )
            );
        }

        $products = wc_get_products($args);
        $query_post_in = array_intersect($products, $this->wdr_product_ids);
        if(isset($wp_query->query_vars['post__in']) && !empty($wp_query->query_vars['post__in'])){
            $query_post_in = array_intersect($query_post_in, $wp_query->query_vars['post__in']);
        }
        if(empty($query_post_in)){
            return [0];
        }
        return $query_post_in;
    }

    public function getWooDiscountRulesClass(){
        return flrt_woo_discount_rules_class();
    }

    protected function preloadProducts($product_ids) {
        if (empty($product_ids)) {
            return;
        }

        $products = wc_get_products([
            'include' => $product_ids,
            'limit'   => -1,
        ]);

        foreach ($products as $product) {
            $this->product_cache[$product->get_id()] = $product;
        }
    }

    protected function getProductCached($product_id) {
        if (!isset($this->product_cache[$product_id])) {
            $this->product_cache[$product_id] = wc_get_product($product_id);
        }
        return $this->product_cache[$product_id];
    }
}