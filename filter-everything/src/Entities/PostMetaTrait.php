<?php

namespace FilterEverything\Filter;

trait PostMetaTrait
{
    private function findNestedIndexForQuery( $meta_query_array )
    {
        $meta_key = $meta_query_array['key'];

        if( empty( $this->new_meta_query ) ){
            return 0;
        }

        foreach ( $this->new_meta_query as $i_level_1 => $maybe_meta_query ){
            // This subquery already exists
            if( isset( $maybe_meta_query[0]['key'] ) && $maybe_meta_query[0]['key'] === $meta_key ){
                return $i_level_1;
            }
        }

        return count( $this->new_meta_query );
    }

    private function hasNestedQueries( $meta_query )
    {
        if( isset( $meta_query[0]['key'] ) ){
            return true;
        }

        return false;
    }

    private function isTheSameMetaQuery( $meta_query_1, $meta_query_2 )
    {
        $meta_query_1 = $this->normalizeMetaQueryArray( $meta_query_1 );
        $meta_query_2 = $this->normalizeMetaQueryArray( $meta_query_2 );

        $diff = array_diff( $meta_query_1, $meta_query_2 );

        if ( empty( $diff ) ){
            return true;
        }

        return false;
    }

    private function normalizeMetaQueryArray( $meta_query )
    {
        $normalized_meta_query = [];

        if( ! is_array( $meta_query ) || ! isset( $meta_query['key'] ) ){
            return false;
        }
        if( isset( $meta_query['value'] ) ){
            if( is_array( $meta_query['value'] ) ){
                sort( $meta_query['value'] );
                $meta_query['value'] = implode( '-', $meta_query['value'] );
                $normalized_meta_query['value']     = $meta_query['value'];
            }else{
                $normalized_meta_query['value'] = $meta_query['value'];
            }
        }

        $normalized_meta_query['key']       = $meta_query['key'];
        if( isset( $meta_query['compare'] ) ){
            $normalized_meta_query['compare']   = isset( $meta_query['compare'] ) ? $meta_query['compare'] : '';
        }

        return $normalized_meta_query;
    }

    public function importExistingMetaQuery( $wp_query )
    {
        // Try to check if there is meta_key, meta_value and meta_compare
        if( $wp_query->get('meta_key') ){
            $this->addMetaKeyToQuery( $wp_query );
        }

        $already_existing_meta_query = $wp_query->get('meta_query');

        if( is_array( $already_existing_meta_query ) ){
            foreach( $already_existing_meta_query as $top_index => $value ){

                if( $this->hasNestedQueries( $value ) ){
                    foreach( $value as $n => $nested_meta_query ){
                        $this->addMetaQueryArray( $nested_meta_query, $value['relation'], $top_index );
                    }
                }else{
                    $this->addMetaQueryArray( $value );
                }

            }
        }
    }

    public function addMetaKeyToQuery( $wp_query )
    {
        $args = [];

        $args['key']   = $wp_query->get( 'meta_key' );
        if( $wp_query->get( 'meta_value'  ) ){
            $args['value'] = $wp_query->get( 'meta_value' );
            $args['compare'] = ( $compare = $wp_query->get( 'meta_compare' ) ) ? $compare : 'IN';
        }

        $wp_query->set( 'meta_key', '' );
        $wp_query->set( 'meta_value', '' );

        $this->addMetaQueryArray( $args );
    }

    public function isTermAlreadyInQuery( $queried_value, $wp_query )
    {
        // Is term in Key
        if( $duplicate = $this->isTermInMetaKey( $queried_value, $wp_query ) ) {
            return $duplicate;
        }
        // Is term in Query
        if( $duplicate = $this->isTermInMetaQuery( $queried_value, $wp_query ) ) {
            return $duplicate;
        }

        return false;
    }

    private function isTermInMetaKey( $queried_value, $wp_query ){
        $duplicate  = [];
        $terms      = $queried_value['values'];
        $meta_key   = $wp_query->get('meta_key');
        $meta_value = $wp_query->get('meta_value');

        foreach ( $terms as $term ) {
            if( $queried_value['e_name'] === $meta_key ){
                if( $meta_value === $term ){
                    $duplicate['post_meta'] = $queried_value['e_name'];
                    $duplicate['term']      = $term;
                    return $duplicate;
                }
            }
        }

        return false;
    }

    protected function addMetaQueryArray( $meta_query_array, $relation = false, $top_index = false )
    {
        if( ! isset( $meta_query_array['key'] ) ){
            return false;
        }

        $existing_meta_query = $this->new_meta_query;

        foreach ($existing_meta_query as $index => $present_query) {

            if ($this->hasNestedQueries($present_query)) {
                foreach ($present_query as $k => $nested_present_query) {

                    if (!isset($nested_present_query['key'])) {
                        // relation arg
                        continue;
                    }
                    if ($this->isTheSameMetaQuery($nested_present_query, $meta_query_array)) {
                        return false;
                    }
                }
            } else {
                if ($this->isTheSameMetaQuery($present_query, $meta_query_array)) {
                    return false;
                }
            }

        }

        if ( $relation && in_array( $relation, array( 'AND', 'OR' ) ) ) {
            if( $top_index !== false ) {
                $nested_index = $top_index;
            }else{
                $nested_index = $this->findNestedIndexForQuery( $meta_query_array );
            }

            $this->new_meta_query[$nested_index][] = $meta_query_array;
            $this->new_meta_query[$nested_index]['relation'] = $relation;
        } else {
            $this->new_meta_query[] = $meta_query_array;
        }

    }
}