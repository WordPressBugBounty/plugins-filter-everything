<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class TaxonomyEntity implements Entity
{
    public $items           = [];
    /**
     * Declared explicitly: assigned from the outside in
     * EntityManager::prepareEntitiesToDisplay(); dynamic properties are
     * deprecated since PHP 8.2.
     */
    public $items_sort = [];

    public $filter = [];

    public $excludedTerms   = [];

    public $isInclude       = false;

    public $descendants     = [];

    private $entityName     = '';

    private $new_tax_query  = [];

    private $postTypes      = [];

    public function __construct( $taxName ){
        $this->entityName = $taxName;
        $this->getAllExistingTerms();
        $this->passTermNames();
    }

    public function setPostTypes( $postTypes )
    {
        $this->postTypes = $postTypes;
    }

    public function setExcludedTerms( $excludedTerms, $isInclude )
    {
        $this->excludedTerms = (array) $excludedTerms;
        $this->isInclude     = $isInclude;
    }

    public function getName()
    {
        return $this->entityName;
    }

    function excludeTerms( $terms )
    {
        $exclude = [];

        if( ! empty( $this->excludedTerms ) ){
            $exclude = $this->excludedTerms;
        }

        $exclude_flipped = array_flip( $exclude );
        if( $this->isInclude ){
            $included_terms = [];
            foreach( $terms as $index => $term ){
                if( isset( $exclude_flipped[$term->term_id] ) ){
                    $included_terms[$index] = $term;
                }
            }
            $terms = $included_terms;
        }else{
            foreach( $terms as $index => $term ){
                if(  isset( $exclude_flipped[$term->term_id] ) ){
                    unset( $terms[$index] );
                }
            }
        }

        return $terms;
    }

    public function getTermTaxonomyPostsIds( $termTaxonomyIds, $termIds, $filter )
    {
        global $wpdb;
        $include_variation_atts = false;
        $ids = [];

        if(! isset( $filter['slug'] ) ){
            return $ids;
        }

        // Check if it is already stored
        $transient_key = flrt_get_post_ids_transient_key( $filter['slug'] );

        $ids = flrt_get_transient( $transient_key );

        // Cache format v2: aggregated [term_id => [object_id, ...]] int map.
        // Legacy format cached raw SQL rows (each an assoc array with a 'term_id'
        // key) — detect and rebuild those. The compact map is 15-30x smaller in
        // memory and in wp_options.
        $is_v2 = is_array( $ids );
        if ( $is_v2 && ! empty( $ids ) ) {
            $first_item = reset( $ids );
            if ( is_array( $first_item ) && isset( $first_item['term_id'] ) ) {
                $is_v2 = false;
            }
        }

        if ( ! $is_v2 ) {

            // It wasn't there, so regenerate the data and save the transient
            if( defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO ) {
                if( strpos( $this->getName(), 'pa_' ) === 0 ) {
                    $include_variation_atts = true;
                }
            }

            $query[] = "SELECT DISTINCT {$wpdb->term_relationships}.term_taxonomy_id";
            $query[] = ", {$wpdb->term_relationships}.object_id";
            $query[] = ", tt.term_id";

            if( $include_variation_atts ){
                $query[] = ", tm.slug";
            }

            $query[] = "FROM {$wpdb->term_relationships}";
            $query[] = "LEFT JOIN {$wpdb->term_taxonomy} AS tt";
            $query[] = "ON ( {$wpdb->term_relationships}.term_taxonomy_id = tt.term_taxonomy_id )";

            if( $include_variation_atts ){
                $query[] = "LEFT JOIN {$wpdb->terms} AS tm";
                $query[] = "ON ( tt.term_id = tm.term_id )";
            }

            $query[] = "WHERE {$wpdb->term_relationships}.term_taxonomy_id IN ('" . implode("','", $termTaxonomyIds) . "')";

            $query = implode(' ', $query);

            $results = $wpdb->get_results($query, ARRAY_A);

            // Note: with the v2 cache this filter runs on the raw rows only when
            // the cache is being (re)built, not on every request as before.
            $taxonomy_terms = apply_filters( 'wpc_term_taxonomy_terms', $results, $this );
            unset( $results );

            $ids = [];
            foreach ($taxonomy_terms as $key => $result) {
                $ids[ (int) $result['term_id'] ][] = (int) $result['object_id'];
            }
            unset( $taxonomy_terms );

            flrt_set_transient( $transient_key, $ids, FLRT_TRANSIENT_PERIOD_HOURS * HOUR_IN_SECONDS );
        }

        // Add possible empty terms without posts
        foreach( $termIds as $term_id ){
            if( ! isset( $ids[$term_id] ) ){
                $ids[$term_id] = [];
            }
        }

        // Fix for counts for parent, when 'include_children=true'
        // Add posts from children terms to their parents
        // To make correct counts for their parents
        if( ! empty( $this->descendants ) && $filter['logic'] === 'or' ){
            // array of parents term_ids, that have children
            foreach ( $ids as $term_id => $post_ids_array ){
                if( isset( $this->descendants[$term_id] ) ){
                    foreach ( $this->descendants[$term_id] as $child_term_id ){
                        if( isset( $ids[$child_term_id] ) ){
                            $ids[$term_id] = array_unique( array_merge( $ids[$term_id], $ids[$child_term_id] ) );
                        }
                    }
                }
            }
        }

        return $ids;
    }

    public function populateTermsWithPostIds( $setId, $post_type )
    {
        $termTaxonomyIds     = [];
        $termIds             = [];
        $termPosts           = [];
        $the_filter          = [];
        $allWpQueriedPostIds = [];
        $em                  = Container::instance()->getEntityManager();
        $allWpQueriedPostIds = $em->getAllSetWpQueriedPostIds( $setId );

        $relatedFilters      = $em->getSetsRelatedFilters( array( array( 'ID' => $setId) ) );

        foreach ( $relatedFilters as $filter ){
            if( isset( $filter['e_name'] ) && $filter['e_name'] === $this->getName() ){
                $the_filter = $filter;
                break;
            }
        }

        foreach ( $this->getAllExistingTerms() as $term ) {
            $termTaxonomyIds[] = $term->term_taxonomy_id;
            $termIds[]         = $term->term_id;
        }


        if( ! empty( $the_filter ) ){
            $termPosts = $this->getTermTaxonomyPostsIds( $termTaxonomyIds, $termIds, $the_filter );
        }

        if( $this->getName() === 'product_shipping_class' ) {
            foreach ( $termPosts as $term_id => $the_posts ) {
                $termPosts[$term_id] = apply_filters( 'wpc_from_variations_to_products', $the_posts );
            }
        }

        $wpManager          = Container::instance()->getWpManager();
        $wp_queried_object  = $wpManager->getQueryVar( 'wp_queried_object' );
        $wp_queried_term_id = ( isset( $wp_queried_object['term_id'] ) && $wp_queried_object['term_id'] > 0 ) ? $wp_queried_object['term_id'] : 0;

        // On shops that list variations as standalone products (e.g. XStore's
        // "variable_products_detach") the set universe contains VARIATIONS while
        // the variable parents are excluded via post__not_in. Term relationships,
        // however, live on the parents — a plain intersection below would drop
        // them and zero out every taxonomy counter. Treat a parent as
        // "in universe" when any of its variations is: map the in-universe
        // variations back to their parents (PRO hooks mapVariationIdsToParentIds
        // here; in the free build the filter is a no-op) and union both lists.
        // NOTE: this deliberately uses the UNGATED mapping filter — the parent
        // identity is needed here regardless of how the catalog lists its items,
        // while wpc_from_variations_to_products is a counting-time collapse that
        // stays off on variations-as-products shops.
        // The wpc_items_before_calc_term_count replacement later turns parents
        // back into their variations, and calcTermCount() intersects against the
        // real universe again, so the final counts stay exact. On sites whose
        // universe holds no variation IDs the union changes nothing.
        $universeParents = apply_filters( 'wpc_variations_to_parents_always', $allWpQueriedPostIds );
        if ( is_array( $universeParents ) && $universeParents !== $allWpQueriedPostIds ) {
            $allWpQueriedPostIds = array_merge( $allWpQueriedPostIds, $universeParents );
        }

        $allWpQueriedPostIds = array_flip( $allWpQueriedPostIds );

        foreach( $this->items as $index => $term ) {
            if( isset( $termPosts[$term->term_id] ) ) {
                $intersected_posts = [];
                foreach ( $termPosts[$term->term_id] as $post_id ){
                    if( isset( $allWpQueriedPostIds[$post_id] ) ){
                        $intersected_posts[] = $post_id;
                    }
                }
                $this->items[$index]->posts = $intersected_posts;
            }else{
                // Here could be items that have no posts, but their descendants have
                $this->items[$index]->posts = [];
            }

            if( $wp_queried_term_id === $this->items[$index]->term_id ){
                $this->items[$index]->wp_queried = true;
            }

        }
    }

    public function getTerms()
    {
        return $this->excludeTerms( $this->getAllExistingTerms() );
    }

    /**
     * @param int $id term id
     * @return false|object term object of false
     */
    public function getTerm( $id ){
        if( ! $id ){
            return false;
        }

        foreach ( $this->getAllExistingTerms() as $term ){
            if( $id == $term->term_id ){
                return $term;
            }
        }

        return false;
    }

    public function getTermId( $termSlug )
    {
        foreach ( $this->getAllExistingTerms() as $term ){
            if( $termSlug == $term->slug ){
                return $term->term_id;
            }
        }

        return false;
    }

    /**
     * @return array list of term_id and names useful to create Select dropdown
     */
    public function getTermsForSelect( $optionGroup = false )
    {
        $toSelect = [];
        foreach ( $this->getTerms() as $term ) {
            if( $optionGroup ){
                $key = $term->taxonomy.":".$term->term_id;
                $toSelect[$key] = $term->name;
            }else{
                $toSelect[$term->term_id] = $term->name;
            }

        }

        return $toSelect;
    }

    public function getTermsForSelect2()
    {
        $toSelect = [];
        foreach ( $this->getTerms() as $term ) {
            $toSelect[] = array( 'id' => $term->term_id, 'text' =>$term->name );
        }
        return $toSelect;
    }

    public function passTermNames()
    {
        foreach ($this->getAllExistingTerms() as $index => $term ) {
            $this->items[$index]->name = apply_filters( 'wpc_filter_taxonomy_term_name', $term->name, $this->getName() );
        }
    }

    function getAllExistingTerms( $force = false )
    {

        if( empty( $this->items ) || $force ){

            $args = array(
                'taxonomy'   => $this->getName(),
                'hide_empty' => false,
                'orderby'    => 'none',
                'order'      => 'ASC'
            );

            /**
             * Filter terms query $args to allow handle cases with some specific taxonomies
             */
            $args   = apply_filters( 'wpc_filter_term_query_args', $args, 'taxonomy', $this->getName() );
            $result = apply_filters( 'wpc_filter_get_taxonomy_terms', get_terms( $args ), $this->getName() );

            $termsUpdated    = [];
            $children_terms  = [];

            if( ! empty( $result ) && ! is_wp_error( $result ) ) {

                foreach ($result as $i => $termObject) {
                    $termObject->name = apply_filters( 'wpc_filter_' . $this->getName() . '_term_name', $termObject->name, $termObject );
                    $termObject->cross_count = 0;
                    $termObject->posts = [];
                    $termObject->wp_queried  = false;

                    $termsUpdated[$i] = $termObject;

                    if( ! empty( $termObject->parent ) ) {
                        $children_terms[$termObject->parent][] = $termObject->term_id;
                    }
                }
                // Solution for 'include_children=true' problem and parent counts
                $this->descendants = flrt_find_all_descendants( $children_terms );
                $this->items = $termsUpdated;
            }

        }

        return $this->items;
    }

    private function getSqlLogicOperator( $filter ){
        if( $filter['logic'] === 'and' ){
            return 'AND';
        } else {
            return 'IN';
        }
    }

    public function isTermAlreadyInQuery( $queried_value, $wp_query ){
        $duplicate = [];

        if ( ! empty( $wp_query->tax_query->queried_terms ) ) {
            $native_query_terms = $wp_query->tax_query->queried_terms;
            $queried_taxonomies = array_keys( $native_query_terms );

            foreach ( $queried_taxonomies as $q_taxonomy ) {
                if( $q_taxonomy === $queried_value['e_name'] ){
                    $query = $native_query_terms[$q_taxonomy];

                    if ( ! empty( $query['terms'] ) ) {
                        if ( 'term_id' == $query['field'] ) {
                            $term = get_term( reset( $query['terms'] ), $q_taxonomy );
                        } else {
                            $term = get_term_by( $query['field'], reset( $query['terms'] ), $q_taxonomy );
                        }

                        if( ! $term || is_wp_error( $term ) ){
                            return false;
                        }

                        foreach( $queried_value['values'] as $filter_slug ){
                            if(  $filter_slug === $term->slug ){
                                $duplicate['taxonomy'] = $q_taxonomy;
                                $duplicate['term']     = $term->slug;
                                return $duplicate;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    private function isTheSameTaxQuery( $tax_query_1, $tax_query_2 ){
        $tax_query_1 = $this->normalizeTaxQueryArray( $tax_query_1 );
        $tax_query_2 = $this->normalizeTaxQueryArray( $tax_query_2 );

        $diff = array_diff( $tax_query_1, $tax_query_2 );

        if ( empty( $diff ) ){
            return true;
        }

        return false;
    }

    private function normalizeTaxQueryArray( $tax_query ){
        $normalized_tax_query = [];

        if( ! is_array( $tax_query ) || ! isset( $tax_query['taxonomy'] ) ) {
            return false;
        }

        if( is_array( $tax_query['terms'] ) ){
            sort( $tax_query['terms'] );
        }

        $normalized_tax_query['taxonomy'] = $tax_query['taxonomy'];
        if( isset( $tax_query['field'] ) ){
            $normalized_tax_query['field']    = $tax_query['field'];
        }

        if( is_array($tax_query['terms']) ){
            $normalized_tax_query['terms']    = implode( '-', $tax_query['terms'] );
        }else{
            $normalized_tax_query['terms']    = $tax_query['terms'];
        }

        return $normalized_tax_query;
    }

    private function hasNestedQueries( $tax_query )
    {
        if( isset( $tax_query[0]['taxonomy'] ) ){
            return true;
        }

        return false;
    }

    private function addTaxQueryArray( $tax_query_array ){
        if( ! isset( $tax_query_array['taxonomy'] ) ){
            return false;
        }

        $existing_tax_query = $this->new_tax_query;
        foreach( $existing_tax_query as $index => $present_query ){
            if( $this->isTheSameTaxQuery( $present_query, $tax_query_array ) ){
                return false;
            }
        }

        $this->new_tax_query[] = $tax_query_array;
    }

    /**
     * @return mixed object WP_Query|string;
    */
    public function addTermsToWpQuery($queried_value, $wp_query ){
        /**
         * @feature Include children should be optionally configured in Settings. Maybe.
        */
        //@bug WordPress bug if slug is '0' SQL query is wrong
        $args = array(
            'taxonomy'          => $queried_value['e_name'],
            'field'             => 'slug',
            'terms'             => $queried_value['values'],
        );

        /**
         * On multilingual sites, resolve slugs to term IDs of the current language
         * before querying.
         *
         * With Polylang / WPML a single slug can belong to several terms in
         * different languages (e.g. an English "leather" term and a Latvian one
         * sharing the slug "leather"). Querying by slug then matches the
         * wrong-language term, which makes Polylang fire a canonical 301 redirect.
         * getTermId() resolves against getAllExistingTerms(), which is already
         * scoped to the active language, so the query targets the right term.
         * Falls back to slug matching if any value can't be resolved.
         *
         * Gated to multilingual setups so single-language sites keep the original
         * slug-based path untouched (no extra term lookups).
         */
        if ( function_exists( 'pll_current_language' ) || flrt_wpml_active() ) {
            $term_ids = array();
            foreach ( (array) $queried_value['values'] as $term_slug ) {
                $term_id = $this->getTermId( $term_slug );
                if ( ! $term_id ) {
                    $term_ids = array();
                    break;
                }
                $term_ids[] = $term_id;
            }

            if ( ! empty( $term_ids ) ) {
                $args['field'] = 'term_id';
                $args['terms'] = $term_ids;
            }
        }

        if( isset( $queried_value['logic'] ) && $queried_value['logic'] === 'and' ){
            $args['include_children'] = false;
        }

        $args['operator'] = $this->getSqlLogicOperator( $queried_value );

        if( isset( $wp_query->tax_query->queries ) && count( $wp_query->tax_query->queries ) ){
            foreach($wp_query->tax_query->queries as $single_tax_query ){
                $this->addTaxQueryArray( $single_tax_query );
            }
        }

        $this->addTaxQueryArray( $args );

        $already_existing_tax_query = $wp_query->get('tax_query');

        if( is_array( $already_existing_tax_query ) ){
            foreach( $already_existing_tax_query as $value ){
                if( $this->hasNestedQueries( $value ) ){
                    foreach( $value as $n => $nested_tax_query ){
                        $this->addTaxQueryArray( $nested_tax_query );
                    }
                }
                $this->addTaxQueryArray( $value );
            }
        }

        if( count($this->new_tax_query) > 1 ){
            $this->new_tax_query['relation'] = 'AND';
        }

        $wp_query->set('tax_query', $this->new_tax_query );
        $this->new_tax_query = [];

        return $wp_query;
    }
}