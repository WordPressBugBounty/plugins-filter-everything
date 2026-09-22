<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Per-request filter state.
 *
 * One instance per HTTP request (see Container::getFilterContext()). It owns the
 * state that the request pipeline steps (WpManager → EntityManager → widgets)
 * pass to each other: the parsed filter request, the sets relevant for the
 * page, the JSON payload for the frontend script and a few one-shot flags.
 *
 * Until 1.9.7 this state lived in WpManager::$filterQueryVars and in the PHP
 * globals $flrt_sets, $flrt_json_data, $wpc_not_fired and $chips_count.
 *
 * @since 1.9.7
 */
class FilterContext
{
    /**
     * Write-once request variables (queried_values, wpc_page_related_set_ids, …).
     * @var array
     */
    private $vars = [];

    /** @var bool */
    private $filterRequest = false;

    /**
     * Queue of the filter sets relevant for the current page.
     * flrt_the_set() consumes it while widgets render.
     * @var array
     */
    private $sets = [];

    /**
     * Payload for the frontend script (instant recount, permalinks map …).
     * @var array
     */
    private $jsonData = [];

    /** @var bool */
    private $mainQueryHandled = false;

    /** @var int */
    private $chipsCount = 0;

    /* ---------------------------------------------------------------------
     * Request variables (write-once, same contract as WpManager::setQueryVar)
     * ------------------------------------------------------------------- */

    /**
     * Stores a variable only if it is not set yet.
     *
     * @return bool true when stored, false when the key already existed.
     */
    public function set( $key, $value )
    {
        if ( ! isset( $this->vars[ $key ] ) ) {
            $this->vars[ $key ] = $value;
            return true;
        }
        return false;
    }

    /**
     * Deliberate overwrite of an existing variable. Used once, by
     * WpManager::populateQueriedValuesWithAdditionalParams() when the logic
     * separators become known after the relevant set is resolved.
     */
    public function replace( $key, $value )
    {
        $this->vars[ $key ] = $value;
    }

    public function get( $key, $default = false )
    {
        if ( isset( $this->vars[ $key ] ) ) {
            return $this->vars[ $key ];
        }
        return $default;
    }

    public function has( $key )
    {
        return isset( $this->vars[ $key ] );
    }

    /* ---------------------------------------------------------------------
     * Filter request flag
     * ------------------------------------------------------------------- */

    public function isFilterRequest()
    {
        return $this->filterRequest;
    }

    public function markFilterRequest()
    {
        $this->filterRequest = true;
    }

    /* ---------------------------------------------------------------------
     * Relevant sets queue
     * ------------------------------------------------------------------- */

    public function setSets( array $sets )
    {
        $this->sets = $sets;
    }

    public function getSets()
    {
        return $this->sets;
    }

    /**
     * Removes and returns one set from the queue: the one with the given ID,
     * or the first one when $set_id is 0. Returns null when nothing is left.
     */
    public function takeSet( $set_id = 0 )
    {
        if ( $set_id ) {
            foreach ( $this->sets as $k => $set ) {
                if ( $set['ID'] === $set_id ) {
                    unset( $this->sets[ $k ] );
                    return $set;
                }
            }
        }

        return array_shift( $this->sets );
    }

    /* ---------------------------------------------------------------------
     * Frontend JSON payload
     * ------------------------------------------------------------------- */

    /**
     * Returned by reference so producers can write into it in place:
     *   $json = &$ctx->jsonData();
     *   $json[ $setId ]['allEntities'] = …;
     *
     * @return array
     */
    public function &jsonData()
    {
        return $this->jsonData;
    }

    public function hasJsonData()
    {
        return ! empty( $this->jsonData );
    }

    /* ---------------------------------------------------------------------
     * One-shot flags
     * ------------------------------------------------------------------- */

    /**
     * True until markMainQueryHandled() is called. Guards the section of
     * WpManager::addFilterQueryToWpQuery() that must run once per request.
     */
    public function isMainQueryPending()
    {
        return ! $this->mainQueryHandled;
    }

    public function markMainQueryHandled()
    {
        $this->mainQueryHandled = true;
    }

    /**
     * Next 1-based index for a rendered chips list (unique CSS class per list).
     */
    public function nextChipsIndex()
    {
        return ++$this->chipsCount;
    }
}
