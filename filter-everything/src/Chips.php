<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Chips
{
    public $chips = [];
    
    private $queried;

    private $showReset;

    private $use_apply_button;

    private $counter = 1;

    private $setFilterKeys = [];

    private $chipKeys = [];

    public function __construct( $showReset = false, $setIds = [] )
    {
        $em                 = Container::instance()->getEntityManager();
        $wpManager          = Container::instance()->getWpManager();

        $this->queried      = $wpManager->getQueryVar('queried_values');
        $sets               = $wpManager->getQueryVar('wpc_page_related_set_ids');

        $this->showReset    = $showReset;

        if( ! $setIds || empty( $setIds ) ){
            if(!empty($sets) && is_array( $sets ) ) {
                foreach ($sets as $set) {
                    $setIds[] = $set['ID'];
                }
            }
        }

        if( $setIds ){
            $this->setFilterKeys = $em->getSetFilterKeys( $setIds );
            if(!empty($sets) && is_array( $sets ) ) {
                foreach ($sets as $set) {
                    if (in_array($set['ID'], $setIds)) {
                        $postType = $set['filtered_post_type'];
                        $this->fillChips($set['ID'], $postType );
                    }
                }
            }
        }

        unset( $em );

    }
    
    /**
     * Chips are collected across all page-related Sets; the same queried
     * value must produce ONE chip even when the Sets disagree on
     * mode-dependent attributes (e.g. Apply-button link classes),
     * so deduplication goes by identity key, not by the whole array.
     */
    private function addChip( $key, $toAdd )
    {
        if ( ! isset( $this->chipKeys[ $key ] ) ) {
            $this->chips[ $this->counter ] = $toAdd;
            $this->chipKeys[ $key ] = true;
            $this->counter++;
        }
    }

    private function fillChips( $setId, $postType = '' )
    {
        if( $this->queried || ( isset( $_GET['srch'] ) && $_GET['srch'] ) ) {
            $em = Container::instance()->getEntityManager();
            $urlManager = Container::instance()->getUrlManager();
            $filterSet  = Container::instance()->getFilterSetService();
            $filter_set_params = $filterSet->getSet($setId);
            $use_apply_button = (isset($filter_set_params['use_apply_button'] )  && $filter_set_params['use_apply_button']['value'] === 'yes') && flrt_instant_recount();

            // Smart spans: with the crawler-links option on, a term chip keeps
            // its real <a> only when its removal target is indexable (PRO)
            $judge_chip_links = defined('FLRT_FILTERS_PRO')
                && flrt_get_option('disable_filter_links_for_bots') === 'on'
                && function_exists('flrt_indexable_link_target');

            if ($this->showReset) {
                $reset_button_class = 'wpc-chip-reset-all';
                if ($use_apply_button) {
                    $reset_button_class .= ' wpc-apply-button-chip wpc-apply-button-chips-reset';
                }
                $toAdd = array(
                    'link' => $urlManager->getResetUrl(),
                    'name' => esc_html__('Reset all', 'filter-everything'),
                    'class' => $reset_button_class,
                    'link_class' => $reset_button_class
                );

                $this->addChip( 'reset-all', $toAdd );

            }

            if ( $this->queried ) {
                foreach ($this->queried as $slug => $filter) {

                    if (isset($filter['show_chips']) && ($filter['show_chips'] !== 'yes')) {
                        continue;
                    }

                    if (!empty($this->setFilterKeys)) {
                        $queried_value_key = $filter['entity'] . '#' . $filter['e_name'];
                        if (!in_array($queried_value_key, $this->setFilterKeys)) {
                            continue;
                        }
                    }

                    $entityObj = $em->getEntityByFilter($filter, $postType);

                    foreach ($filter['values'] as $key => $termSlug) {
                        $tempSlug = $termSlug;
                        if ( in_array($filter['entity'], ['post_meta_num', 'tax_numeric'] ) ) {
                            $termSlug = $key;
                            $tempSlug = $key . $filter['e_name'];
                        }

                        if ( in_array($filter['entity'], ['post_date', 'post_meta_date'] ) ) {
                            $termSlug = $key;
                            $tempSlug = flrt_range_input_name( $filter['slug'], $key, 'date' );
                        }


                        $termId = $entityObj->getTermId( $termSlug );
                        $term = $entityObj->getTerm( $termId );

                        // In case if we have no terms for this post type
                        if (!$term) {
                            continue;
                        }

                        $chips_button_class = '';
                        if ($use_apply_button) {
                            $chips_button_class .= 'wpc-apply-button-chip';
                        }

                        $toAdd = array(
                            'link'       => $urlManager->getTermUrl($termSlug, $filter['e_name'], $filter['entity']),
                            'link_class' => $chips_button_class,
                            'name'       => apply_filters('wpc_chips_term_name', $term->name, $term, $filter),
                            'class'      => 'wpc-chip-' . $filter['e_name'] . '-' . $termId,
                            'e_name'     => $filter['e_name'],
                            'slug'       => $tempSlug,
                            'label'      => $filter['label']
                        );

                        if ( $judge_chip_links ) {
                            $toAdd['crawlable'] = flrt_indexable_link_target( $term, $filter );

                            // Removing one value of an OR multi-selection NARROWS the
                            // results to the remaining values — a zero-result target
                            // gets noindexed at runtime (SeoFrontend::countTerms). The
                            // remaining term's cross_count is exactly that target count.
                            if ( $toAdd['crawlable']
                                && isset( $filter['logic'] ) && $filter['logic'] === 'or'
                                && count( $filter['values'] ) > 1 ) {

                                $remaining_alive = false;
                                foreach ( $filter['values'] as $otherSlug ) {
                                    if ( $otherSlug === $termSlug ) {
                                        continue;
                                    }
                                    $otherTerm = $entityObj->getTerm( $entityObj->getTermId( $otherSlug ) );
                                    if ( $otherTerm && ( ! isset( $otherTerm->cross_count ) || (int) $otherTerm->cross_count > 0 ) ) {
                                        $remaining_alive = true;
                                        break;
                                    }
                                }

                                if ( ! $remaining_alive ) {
                                    $toAdd['crawlable'] = false;
                                }
                            }
                        }

                        if ( $filter['e_name'] === 'product_visibility') {
                            $rating_slugs = array(
                                'rated-1',
                                'rated-2',
                                'rated-3',
                                'rated-4',
                                'rated-5'
                            );

                            if(in_array($termSlug, $rating_slugs)){
                                $pieces = explode("-", $termSlug);
                                $rating = isset( $pieces[1] ) ? $pieces[1] : 0;
                                if ($rating){
                                    $toAdd['link'] = $urlManager->getTermUrl( $termSlug, $filter['e_name'], $filter['entity'], '', ['rating_slug' => $termSlug]);
                                    $toAdd['rating'] = $rating;
                                }
                            }
                        }

                        $this->addChip( 'term:' . $filter['e_name'] . ':' . $tempSlug, $toAdd );
                    }
                }
            }

            $srch = isset( $_GET['srch'] ) ? filter_input( INPUT_GET, 'srch', FILTER_SANITIZE_SPECIAL_CHARS ) : '';
            if ( $srch ){

                $clear_search_url = $urlManager->getTermUrl( '', '', '', '', [ 'srch'=> true ] );

                $toAdd = array(
                    'link'  => $clear_search_url,
                    'name'  => sprintf( __( 'search: %s', 'filter-everything' ), $srch ),
                    'class' => 'wpc-chip-search',
                    'label' => $srch,
                );

                $this->addChip( 'search:' . $srch, $toAdd );
            }

            if( count( $this->chips ) === 1 ){
                $singleChip = reset( $this->chips );
                // strpos: the Apply-button variant carries extra classes after 'wpc-chip-reset-all'
                if( strpos( $singleChip['class'], 'wpc-chip-reset-all' ) === 0 ){
                    $this->chips    = [];
                    $this->chipKeys = [];
                }
            }

            unset( $em, $urlManager );

        }
    }

    public function getChips()
    {
        if( ! empty( $this->chips ) ){
            return $this->chips;
        }
        return false;
    }

}