/*!
 * Filter Everything 1.9.3
 */
(function ($) {
    "use strict";
    let wpcAjax                     = wpcFilterFront.wpcAjaxEnabled;
    let wpcStatusCookieName         = wpcFilterFront.wpcStatusCookieName;
    let wpcMoreLessCookieName       = wpcFilterFront.wpcMoreLessCookieName;
    let wpcWidgetStatusCookieName   = wpcFilterFront.wpcWidgetStatusCookieName;
    let wpcHierachyListCookieName   = wpcFilterFront.wpcHierarchyListCookieName;
    let wpcMobileWidth              = wpcFilterFront.wpcMobileWidth;
    let wpcPostContainers           = wpcFilterFront.wpcPostContainers;
    let wpcAutoScroll               = wpcFilterFront.wpcAutoScroll;
    let wpcAutoScrollOffset         = wpcFilterFront.wpcAutoScrollOffset;
    let wpcWaitCursor               = wpcFilterFront.wpcWaitCursor;
    let wpcPostsPerPage             = wpcFilterFront.wpcPostsPerPage;
    let wpcUseSelect2               = wpcFilterFront.wpcUseSelect2;
    let wpcDateFilters              = wpcFilterFront.wpcDateFilters;
    let wpcDateFiltersLocale        = wpcFilterFront.wpcDateFiltersLocale;
    let wpcDateFiltersL10n          = wpcFilterFront.wpcDateFiltersL10n;
    let wpcPopupCompatMode          = wpcFilterFront.wpcPopupCompatMode;
    let wpcApplyButtonSets          = wpcFilterFront.wpcApplyButtonSets;
    let wpcQueryOnThePageSets       = wpcFilterFront.wpcQueryOnThePageSets;
    let noPostsContainerMsg         = wpcFilterFront.wpcNoPostsContainerMsg;
    let wpcIsPro            = Boolean(wpcFilterFront.wpcIsPro);
    let permalinksEnabled   = Boolean(wpcFilterFront.permalinksEnabled);
    let wpcMoreLessCount            = wpcFilterFront.wpcMoreLessCount;
    let wpcSearchChipsText          = wpcFilterFront.wpcSearchChipsText;
    let chipsTitle                  = wpcFilterFront.chipsTitle;
    let chipsReset                  = wpcFilterFront.chipsReset;
    // Instant (client-side) Apply-button recount is active when the server printed
    // window.wpcFilterJsonData for this page — it does so only when a Set with
    // use_apply_button=yes is present AND the "Instant recount" option is enabled.
    // With static-file delivery the data arrives asynchronously: the bootstrap
    // exposes wpcFilterJsonDataPromise until the blob is fetched and merged.
    // Without either, Apply-button Sets fall back to the legacy per-click AJAX recount.
    let wpcInstantRecount           = ( typeof window.wpcFilterJsonData !== 'undefined' || typeof window.wpcFilterJsonDataPromise !== 'undefined' );
    let wpcWidgetContainer   = '.wpc-filters-main-wrap';
    let wpcIsMobile         = false;
    let toReplaceSEO        = true;
    let prevState           = false; // Contains SEO Rule availability on a page
    let currentState        = false; // Contains SEO Rule availability on a page

    let seoRuleId = $('#wpc-seo-rule-id').data( 'seoruleid' );
    if ( seoRuleId > 0 ) {
        prevState = true;
    }

    function removeElement($el)
    {
        $el.fadeTo(100, 0, function() {
            $el.slideUp(100, function() {
                $el.remove();
            });
        });
    }

    $(document).on('click', '.wpc-filter-content input[type="radio"], .wpc-filter-content input[type="checkbox"]', function (e) {
        let wpcLink = $(this).data('wpc-link');
        let $el     = $(this).parents(wpcWidgetContainer);
        let setId   = $el.data('set');
        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
            applyButtonMode = true;
        }

        if( applyButtonMode && !wpcInstantRecount ){
            // Legacy Apply-button mode: recount counters on the server per click
            e.preventDefault();
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
        }else if( applyButtonMode ){
            // The recount collector selects radios by the [data-wpc-was-checked=true]
            // ATTRIBUTE — always clear/set BOTH the attribute and jQuery data on the
            // whole radio group, otherwise the previously selected term (incl. the
            // server-rendered one) keeps counting as selected alongside the new one
            const isRadio = $(this).is('input[type="radio"]');
            if (isRadio && !$(this).hasClass( 'flrt-star-input' )) {
                if ($(this).data('wpc-was-checked')) {
                    $(this).prop('checked', false).attr('data-wpc-was-checked', false).data('wpc-was-checked', false);
                } else {
                    $('input[type="radio"][name="' + $(this).attr('name') + '"]').attr('data-wpc-was-checked', false).data('wpc-was-checked', false);
                    $(this).attr('data-wpc-was-checked', true).data('wpc-was-checked', true);
                }
            }
            const isRatingStar = $(this).hasClass('flrt-star-input');
            if (isRatingStar && isRadio) {
                const $starContent = $(this).closest('.flrt-stars-wpc-filter-content');
                $starContent.find('input.flrt-star-input').removeClass('wpc-checked-for-apply-button');
                if ($(this).data('wpc-was-checked')) {
                    $(this).prop('checked', false).attr('data-wpc-was-checked', false).data('wpc-was-checked', false);
                } else if($(this).is(':checked')) {
                    $('input[type="radio"][name="' + $(this).attr('name') + '"]').not(this).attr('data-wpc-was-checked', false).data('wpc-was-checked', false);
                    $(this).data('wpc-was-checked', true).attr('data-wpc-was-checked', true);
                }
                // The star fill has TWO writers whose ORDER depends on the click
                // target: a real click lands on the <a> inside the label, so the
                // '.wpc-filter-content a' handler triggers this input synchronously
                // and the label click handler repaints AFTERWARDS from its own
                // stale data (a plain label click runs them in the opposite order).
                // Repaint once more after the whole click dispatch settles, from
                // the input state — the single source of truth.
                const selectedAndAbove = $starContent.data('selectedAndAbove');
                setTimeout(function () {
                    const $checkedInput = $starContent.find('input.flrt-star-input[data-wpc-was-checked="true"]');
                    const n = $checkedInput.length ? Number($checkedInput.data('ratingNum')) : 0;
                    $starContent.find('label.flrt-star-label').each(function () {
                        const k = Number($(this).data('ratingNum'));
                        const on = n > 0 && (selectedAndAbove ? k >= n : k <= n);
                        $(this).toggleClass('flrt-star-label-hover', on)
                               .toggleClass('flrt-star-label-checked', on && !selectedAndAbove);
                        if (selectedAndAbove) {
                            $(this).toggleClass('flrt-star-label-not-checked', n > 0 && on);
                        } else {
                            $(this).removeClass('flrt-star-label-not-checked');
                        }
                        $(this).data('wpc-was-checked', n > 0 && k === n);
                    });
                }, 0);
            }

            if ($(this).hasClass('wpc-range-list-item')) {
                $('.wpc-range-list-item', $(this).parents('.wpc-filters-range-inputs')).removeClass('wpc-range-list-item-checked');

                if(!applyButtonMode) return;

                const elementData = $(this).data();
                let wpcEName = (typeof elementData.wpcEName !== 'undefined');
                let wpcMin = (typeof elementData.min !== 'undefined');
                let wpcMax = (typeof elementData.max !== 'undefined');
                let wpcSlugMin= (typeof elementData.wpcSlugMin !== 'undefined');
                let wpcSlugMax= (typeof elementData.wpcSlugMax !== 'undefined');

                $(this).addClass('wpc-range-list-item-checked');

                let isChecked = $(this).data('wpc-was-checked');

                if(!wpcEName) return;


                if(wpcMin){
                    if(!wpcSlugMin) return;
                    let inputNameMin = elementData.wpcSlugMin + elementData.wpcEName;
                    let minVal = elementData.min;
                    let $inputElementMin = $("input[name=" + inputNameMin + "].wpc-filters-range-min", $el)
                    if(minVal !== $inputElementMin.data().min){
                        $inputElementMin.parent().find('.wpc-range-clear').show();
                    }
                    /*if(!minVal || minVal === 0){
                        minVal = $inputElementMin.data().min
                    }*/

                    if(!isChecked){
                        minVal = $inputElementMin.data().min;
                    }

                    $inputElementMin.attr('value', minVal)
                    $inputElementMin.val(minVal)

                }

                if(wpcMax){
                    if(!wpcSlugMax) return;
                    let inputNameMax = elementData.wpcSlugMax +  elementData.wpcEName ;
                    let maxVal = elementData.max;
                    let $inputElementMax = $("input[name=" + inputNameMax + "].wpc-filters-range-max", $el)
                    if(maxVal !== $inputElementMax.data().max){
                        $inputElementMax.parent().find('.wpc-range-clear').show();
                    }
                   /* if(!maxVal || maxVal === 0){
                        maxVal = $inputElementMax.data().max
                    }*/

                    if(!isChecked){
                        maxVal = $inputElementMax.data().max
                    }

                    $inputElementMax.attr('value', maxVal)
                    $inputElementMax.val(maxVal)
                }


                let form = $(this).parents('.wpc-filters-range-inputs').find('form');
                if(form.length){
                    $.fn.wpcInitSlider( form );
                }
            }
            wpcApplyEngine.applyJsMode($el, setId)
        }else if(wpcAjax){
            e.preventDefault();
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
        }else{
            location.href = wpcLink;
        }
    });

    $(document).on('change', '.wpc-orderby-select', function (){
        let wpcSortingForm = $(this).parents('form.wpc-sorting-form');
        // let wpcSortingVal  = $(this).val();
        let search = '';
        //@todo bug on mobile force AJAX
        search = '?' + wpcSortingForm.serialize();

        let wpcLink = wpcSortingForm.attr('action') + search;

        if( wpcFilterFront.wpcAjaxEnabled ) {
            $('.wpc-filters-main-wrap').each(function (index, element) {
                let $el = $(element);
                wpcSendFilterRequest(wpcLink, $el, false);
            });
        }else{
            wpcSortingForm.attr('action', wpcLink);
            // window.location.href = wpcLink;
            wpcSortingForm.submit();
        }
    });

    $(document).on('change', '.wpc-filter-content select', function (e) {

        var wpcLink = $(this).find('option:selected').data('wpc-link');
        let $el     = $(this).parents(wpcWidgetContainer);
        let setId   = $el.data('set');
        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
            applyButtonMode = true;
        }

        if( applyButtonMode && !wpcInstantRecount ){
            e.preventDefault();
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
        }else if( applyButtonMode ){
            wpcApplyEngine.applyJsMode($el, setId)
        }else if(wpcAjax){
            e.preventDefault();
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
        }else{
            location.href = wpcLink;
        }
    });

    // Smart spans render non-indexable chips as <span data-wpc-span-link>
    $(document).on('click', '.wpc-filter-chip a, .wpc-filter-chip span[data-wpc-span-link], .wpc-filter-chip span.wpc-apply-button-chip', function (e){
        let wpcLink = $(this).attr('href') || $(this).attr('data-wpc-span-link');
        let setId   = $(this).parents('.wpc-filter-chips-list').data('set');
        let $el     = $('.wpc-filter-set-'+setId);
        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
            // Legacy mode counts only chips INSIDE the filters widget as Apply-button
            // clicks (pre-1.9.3 behaviour) — standalone chips above the posts navigate
            // like in non-apply mode. Instant mode handles all chips client-side.
            if( wpcInstantRecount ? $el.length > 0 : $(this).parents('.wpc-filter-set-'+setId).length > 0 ){
                applyButtonMode = true;
            }
        }

        if( applyButtonMode && !wpcInstantRecount ){
            e.preventDefault();
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
        }else if( applyButtonMode ){
            if($(this).hasClass('wpc-apply-button-chips-reset')){
                e.preventDefault();
                $(`a.wpc-filters-reset-button.wpc-filters-reset-button-${setId}`).first().click();
            }

            if($(this).closest('li').hasClass('wpc-chip-search')){
                e.preventDefault();
                wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
            }

            if($(this).hasClass('wpc-apply-button-chip') && !$(this).hasClass('wpc-apply-button-chips-reset') && !$(this).closest('li').hasClass('wpc-chip-search')){
                e.preventDefault();
                // Removing the LAST chip equals resetting all filters —
                // behave exactly like the Reset all button
                const $otherChips = $(this).closest('.wpc-filter-chips-list')
                    .find('li.wpc-filter-chip')
                    .not('.wpc-chip-reset-all')
                    .not($(this).closest('li'));
                const $resetButton = $(`a.wpc-filters-reset-button.wpc-filters-reset-button-${setId}`).first();

                if( $otherChips.length === 0 && $resetButton.length > 0 ){
                    $resetButton.click();
                }else{
                    wpcApplyEngine.unsetChip($(this));
                }
            }
            //applyJsMode($el, setId)
        }else if(wpcAjax) {
            e.preventDefault();
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
        }else{
            // A span chip has no native navigation — follow the link manually
            if( ! $(this).is('a') && wpcLink ){
                window.location.href = wpcLink;
                return false;
            }
            return true;
        }
    });

    $(document).on('click', 'a.wpc-filters-submit-button', function (e){

        if( $(this).hasClass('on-hold') ){
            if( $(this).data('last') !== 'wpc-search-field' ){
                e.preventDefault();
                return false;
            }
        }

        let wpcLink = $(this).attr('href');
        let setId   = $(this).parents('.wpc-filters-main-wrap').data('set');
        let $el     = $('.wpc-filter-set-'+setId);

        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
            applyButtonMode = true;
            // return false;
        }

        if( wpcAjax && wpcQueryOnThePageSets.includes( setId ) ) {
            e.preventDefault();
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode && wpcInstantRecount );
        }else{
            return true;
        }
    });

    $(document).on('click', 'a.wpc-search-clear-icon', function (e){
        let wpcLink = $(this).attr('href');
        let setId   = $(this).parents('.wpc-filters-main-wrap').data('set');
        let $el     = $('.wpc-filter-set-'+setId);
        let applyButtonMode = false;

        if( wpcAjax ) {
            e.preventDefault();
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
            return false;
        }else{
            return true;
        }
    });

    $(document).on( 'change', '.wpc-search-field', function (e) {
        let form = $(this).parents(".wpc-filter-search-form");

        let $el = form.parents(wpcWidgetContainer);
        let setId = $el.data('set');
        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
            applyButtonMode = true;
            // return false;
        }

        if( wpcAjax || applyButtonMode ){
            let search  = form.serialize();
            let wpcLink = form.attr('action') + '?' + search;
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
            return false;
        } else {
            form.submit();
        }
    });

    $(document).on('submit', '.wpc-filter-search-form', function (e) {
        let form = $(this);

        let $el = form.parents(wpcWidgetContainer);
        let setId = $el.data('set');
        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
            applyButtonMode = true;
        }

        if( wpcAjax || applyButtonMode ){
            let search  = form.serialize();
            let wpcLink = form.attr('action') + '?' + search;
            wpcSendFilterRequest( wpcLink, $el, applyButtonMode );
            return false;
        } else {
            return true;
            // form.submit();
        }

    });

    $(document).on('click', 'a.wpc-filters-reset-button', function (e){

        if( $(this).hasClass('on-hold') ){
            e.preventDefault();
            return false;
        }

        let wpcLink = $(this).attr('href');
        let setId   = $(this).parents('.wpc-filters-main-wrap').data('set');
        let $el     = $('.wpc-filter-set-'+setId);
        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
            if( $(this).parents('.wpc-filter-set-'+setId).length > 0 ){
                applyButtonMode = true;
            }
        }

        if (applyButtonMode && wpcInstantRecount){
            e.preventDefault();
            // With "AJAX for Filters" disabled, Reset all must be a plain page
            // reload to the clean URL — same as the Apply button navigates to
            // the built URL. Explicit assign() (not `return true`): the Reset
            // chip and the last-chip removal trigger this handler with a
            // programmatic jQuery .click(), which never follows the href.
            if( ! wpcAjax ){
                window.location.assign(wpcLink);
                return;
            }
            wpcSendFilterRequest(wpcLink, $el, applyButtonMode);
        }else if (wpcAjax) {
            e.preventDefault();
            if (wpcQueryOnThePageSets.includes(setId)) {
                wpcSendFilterRequest(wpcLink, $el, false);
            } else {
                wpcSendFilterRequest(wpcLink, $el, true);
            }

        } else {
            return true;
            // wpcSendFilterRequest( wpcLink, $el, true );
        }
    });

    $(document).on('click', 'i.wpc-toggle-children-list', function (){
        let tid = $(this).data('tid');
        let $targetLi = $(this).parent(".wpc-term-item-content-wrapper").parent('li');
        let $targetFilter = $(this).parents('.wpc-filters-section');

        if ( $targetLi.hasClass( 'wpc-opened' ) ) {
            $targetLi.removeClass( 'wpc-opened' )
                .addClass( 'wpc-closed' );
            setStatusCookie( -tid, wpcHierachyListCookieName );
        } else if ( $targetLi.hasClass( 'wpc-closed' ) ) {
            $targetLi.removeClass( 'wpc-closed' )
                .addClass( 'wpc-opened' );
            setStatusCookie( tid, wpcHierachyListCookieName );
        } else {
            if ( $targetFilter.hasClass( 'wpc-filter-hierarchy-reverse' ) ) {
                $targetLi.removeClass( 'wpc-opened' ) // For any case
                    .addClass( 'wpc-closed' );
                setStatusCookie( -tid, wpcHierachyListCookieName );
            } else {
                $targetLi.removeClass( 'wpc-closed' ) // For any case
                    .addClass( 'wpc-opened' );
                setStatusCookie( tid, wpcHierachyListCookieName );
            }
        }
    });

    $(document).on('click', '.wpc-filters-overlay', function (){
        let setId = $('body').data('set');
        wpcCloseFiltersContainer(setId);
    })

    $(document).on('change', '.wpc-filter-range-form input[type="number"]', function (event) {

        let form = $(this).parents('.wpc-filter-range-form');
        processRangeForm( event, form );
    });

    $(document).on( 'click','.wpc-open-close-filters-button', function (e){
        e.preventDefault();

        let openCloseButton = $(this);
        let wpcSetId        = openCloseButton.data('wid');
        let wpcButtonFilterSetError        = openCloseButton.data('wpcButtonFilterSetError');
        let wpcButtonWidgetError        = openCloseButton.data('wpcButtonWidgetError');
        let widgetContent   = $('.wpc-filter-set-'+wpcSetId+' .wpc-filters-widget-content');

        if (typeof wpcButtonFilterSetError !== 'undefined') {
            alert(wpcButtonFilterSetError);
        }

        if (typeof wpcButtonWidgetError !== 'undefined') {
            if(typeof window.wpcFilterWidgetActive === 'undefined'){
                alert(wpcButtonWidgetError);
            }
        }

        if( widgetContent.is(':visible') ){
            widgetContent.slideUp({
                duration: 100,
                complete: function (){
                    $(this).addClass('wpc-closed')
                        .removeClass('wpc-opened');
                    openCloseButton.removeClass('wpc-opened');
                    wpcSetCookie(wpcWidgetStatusCookieName, null, {path: '/', 'max-age': 2592000});
                }
            });
        }else{
            widgetContent.slideDown({
                duration: 100,
                complete: function (){
                    $(this).addClass('wpc-opened')
                        .removeClass('wpc-closed');
                    openCloseButton.addClass('wpc-opened');
                    wpcSetCookie(wpcWidgetStatusCookieName, wpcSetId, {path: '/', 'max-age': 2592000});
                }
            });
        }
    });

    $(document).on('click', '.wpc-widget-close-icon', function (e){
        e.preventDefault();
        let $wrapper    = $( this ).parents( wpcWidgetContainer );
        let setId       = $wrapper.data( 'set' );
        wpcCloseFiltersContainer(setId);
    });

    $(document).on('click', '.wpc-filters-apply-button', function (e){
        e.preventDefault();
        let $wrapper    = $( this ).parents( wpcWidgetContainer );
        let setId       = $wrapper.data( 'set' );
        let $content    = $( '.wpc-filter-set-'+setId+' .wpc-filters-widget-content' );
        let href        = $(this).attr( 'href' );
        let wpcReload   = ! $(this).hasClass('wpc-posts-loaded');
        let wpcZindex   = '';
        let $currentTag = false;
        let $el     = $('.wpc-filter-set-'+setId);

        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
            applyButtonMode = true;
        }

        if(applyButtonMode && wpcInstantRecount){
            if((wpcFilterFront.wpcAjaxEnabled && wpcAjax) && wpcQueryOnThePageSets.includes( setId ) ) {
                wpcSendFilterRequest( href, $el, applyButtonMode );
            }else{
                location.href = href;
            }
        }


        $wrapper.removeClass('wpc-container-opened');
        $('html').removeClass('wpc-overlay-visible');
        $content.removeClass('wpc-filters-widget-opened');
        $('.wpc-open-button-'+setId+' .wpc-filters-open-widget').removeClass('wpc-opened');

        if( wpcPopupCompatMode ) {
            setTimeout(() => {
                $content.parents().each(function (index, tag) {
                    $currentTag = $(tag);
                    wpcZindex = $currentTag.data('wpczindex');

                    // Saved z-index for
                    if (wpcZindex !== 'undefined') {
                        $currentTag.css('z-index', wpcZindex);
                    }

                    if ($currentTag.hasClass('wpc-force-visibility')) {
                        $currentTag.removeClass('wpc-force-visibility');
                    }
                });

                setTimeout(() => {
                    $(".wpc-was-invisible").css('opacity', '1')
                        .removeClass('wpc-was-invisible');
                }, 300);

            }, 260);
        }

        if( wpcReload ) {
            location.href = href;
        }
    });

    $(document).on('submit', '.wpc-filter-range-form', function (e) {
        submitSliderForm(e, $(this));
    });

    $(document).on('keydown', '.wpc-filters-range-from,.wpc-filters-range-to', function (event){
        if ( event.which == 13 ) {
            let fid = $(this).data('fid');
            processRangeForm( event, $("#wpc-filter-date-range-form-"+ fid ) );
        }
    });

    $(document).on('click', '.wpc-filter-content a', function (e) {
        e.preventDefault();
        let wpcInputId = $(this).closest('label').attr('for');
        $(this).closest('label').parent('.wpc-term-item-content-wrapper').parent('.wpc-term-item').find('#'+wpcInputId).trigger('click');
    });

    $(document).on('click', '.wpc-filters-open-widget', function (e) {
        e.preventDefault();
        let setId = $(this).data('wid');
        wpcOpenContainer( setId );
    });

    $(document).on('click', '.wpc-filters-close-button', function (e) {
        e.preventDefault();
        let wrapper = $(this).parents(wpcWidgetContainer);
        let setId   = wrapper.data('set');

        if( wpcAjax && wpcFilterFront.wpcAjaxEnabled ){
            let cancelLink      = $(this).attr('href');
            let applyLink       = $('.wpc-filter-set-'+setId+' .wpc-filters-apply-button').attr('href');

            if( cancelLink !== applyLink ){
                wpcSendFilterRequest( cancelLink, wrapper, false,'wpcCloseFiltersContainer' );
                return;
            }
        }

        wpcCloseFiltersContainer(setId);
    });

    $(document).on('click', 'a.wpc-toggle-a', function (e){
        e.preventDefault();
        let fid            = $(this).data('fid');
        let $filterSection = $( ".wpc-filters-section-" + fid );
        //$( ".wpc-filters-section-" + fid ).toggleClass( 'wpc-show-more' );

        if ( $filterSection.hasClass('wpc-show-more' ) ) {
            $filterSection.removeClass( 'wpc-show-more' )
                .addClass( 'wpc-show-less' );
            setStatusCookie( -fid, wpcMoreLessCookieName );
        } else if ( $filterSection.hasClass('wpc-show-less' ) ) {
            $filterSection.removeClass( 'wpc-show-less' )
                .addClass( 'wpc-show-more' );
            setStatusCookie( fid, wpcMoreLessCookieName );
        } else {
            // No status class detected
            if( $filterSection.hasClass( 'wpc-filter-has-selected' ) || $filterSection.hasClass( 'wpc-show-more-reverse' ) ) {
                $filterSection.removeClass( 'wpc-show-more' ) // For any case
                    .addClass( 'wpc-show-less' );
                setStatusCookie( -fid, wpcMoreLessCookieName );
            } else {
                $filterSection.removeClass( 'wpc-show-less' ) // For any case
                    .addClass( 'wpc-show-more' );
                setStatusCookie( fid, wpcMoreLessCookieName );
            }
        }
    });

    $(document).on('click', '.wpc-filters-main-wrap input', function (e) {
        let lastInputClass = $(this).attr('class');
        if ( typeof lastInputClass !== 'undefined' ){
            $('.wpc-filters-submit-button').data('last', lastInputClass);
        }
    });

    $(document).on('click', '.wpc-filter-title button', function (e) {
        e.preventDefault();
        let $filterSection = $(this).parents('.wpc-filters-section');
        let filterId       = $filterSection.data( 'fid' );

        if ( $filterSection.hasClass( 'wpc-opened' ) ) {
            $filterSection.removeClass( 'wpc-opened' )
                .addClass( 'wpc-closed' );
            setStatusCookie( -filterId, wpcStatusCookieName );
        } else if ( $filterSection.hasClass( 'wpc-closed' ) ) {
            $filterSection.removeClass( 'wpc-closed' )
                .addClass( 'wpc-opened' );
            setStatusCookie( filterId, wpcStatusCookieName );
        } else {
            if( $filterSection.hasClass( 'wpc-filter-has-selected' ) || $filterSection.hasClass( 'wpc-filter-collapsible-reverse' ) ) {
                $filterSection.removeClass( 'wpc-opened' )
                    .addClass( 'wpc-closed' );
                setStatusCookie( -filterId, wpcStatusCookieName );
            } else {
                $filterSection.removeClass( 'wpc-closed' )
                    .addClass( 'wpc-opened' );
                setStatusCookie( filterId, wpcStatusCookieName );
            }
        }
    });

    $( window ).resize(function() {
        if( window.innerWidth <= wpcMobileWidth ){
            wpcIsMobile = true;
            if( wpcFilterFront.showBottomWidget === 'yes' ) {
                wpcAjax = true;
            }
        }else{
            wpcAjax     = wpcFilterFront.wpcAjaxEnabled;
            wpcIsMobile = false;
        }

        if ( ! wpcSsMobileBrowser() ){
            if( wpcUseSelect2 === 'yes' ){
                $(wpcWidgetContainer).each( function ( index, widget ){
                    let widgetSet = $(widget).data('set');
                    let widgetClass = 'wpc-filter-set-'+widgetSet;
                    wpcInitSelect2(widgetClass);
                });
            }
        }

    });

    if ($.support.pjax) {
        $(document).on('pjax:end', function() {
            setTimeout(() => {
                wpcInitiateAll();
            }, 300);
        });
    }

    $(document).ready(function (){
        wpcInitiateAll();
    });

    $(document).on('input', '.wpc-search-field',function (e){
        let $section    = $(this).parents('.wpc-filters-section');
        let searchOrig  = $(this).val();
        let $search     = searchOrig.toLowerCase();
        let $submitBtn = $(".wpc-filters-submit-button");
        let theHref    = $submitBtn.attr('href');

        if ( typeof theHref !== 'undefined' ){
            let url     = new URL(theHref);
            url.searchParams.set( 'srch', searchOrig);
            $submitBtn.attr( 'href', url.href );

            if( $search !== '' ){
                $section.addClass('wpc-search-active');
            }else{
                $section.removeClass('wpc-search-active');
            }
        }

    });

    $(document).on('input', '.wpc-filter-search-field',function (e){
        let $search  = $(this).val().toString().toLowerCase();
        let $section = $(this).parents('.wpc-filters-section');
        let fid      = $section.data('fid');

        if( $search !== '' ){
            $(".wpc-filter-search-wrapper-"+fid+" .wpc-search-clear").show();
            $section.addClass('wpc-search-active');
        }else{
            $(".wpc-filter-search-wrapper-"+fid+" .wpc-search-clear").hide();
            $section.removeClass('wpc-search-active');
        }

        $(".wpc-filters-list-"+fid+" li").each(function( index, value ) {
            let $li = $(value);
            let $termName = $(value).find('label a').text().toLowerCase();
            if($termName === undefined) {
                let $termName = $(value).find('label span').text().toLowerCase();
            }
            if ($termName.indexOf($search) > -1) {
                $li.addClass('showli');
            } else {
                $li.removeClass('showli');
            }
        });
    });

    $(document).on( 'click', '.wpc-search-clear', function (e){
        e.preventDefault();
        let $searchField = $(this).parent(".wpc-filter-search-wrapper").find(".wpc-filter-search-field");
        $searchField.val('')
            .trigger('input');
    })


    function isDonePressed( inst ) {
        return ( ( $('#ui-datepicker-div .ui-datepicker-close.ui-state-hover').length > 0 ) && !inst._keyEvent );
    }

    function wpcInitiateAll(){
        $('.wpc-filter-range-form').each( function ( index, form ){
            $.fn.wpcInitSlider( $(form) );
        });

        if (window.innerWidth <= wpcMobileWidth) {
            wpcIsMobile = true;
            if( wpcFilterFront.showBottomWidget === 'yes' ) {
                wpcAjax = true;
            }
        }

        if( wpcUseSelect2 === 'yes' ){
            $(wpcWidgetContainer).each( function ( index, widget ){
                let widgetSet = $(widget).data('set');
                let widgetClass = 'wpc-filter-set-'+widgetSet;
                wpcInitSelect2(widgetClass);
            });
        }

        if ( wpcDateFilters !== '' ) {

            $.datepicker.regional[wpcDateFiltersLocale] = wpcDateFiltersL10n;
            $.datepicker.setDefaults(wpcDateFiltersL10n);

            const updatedProperties = {
                _selectDate : function( id, dateStr ) {
                    var onSelect,
                        target = $( id ),
                        inst = this._getInst( target[ 0 ] );

                    dateStr = ( dateStr != null ? dateStr : this._formatDate( inst ) );
                    if ( inst.input ) {
                        inst.input.val( dateStr );
                    }
                    this._updateAlternate( inst );

                    onSelect = this._get( inst, "onSelect" );
                    if ( onSelect ) {
                        onSelect.apply( ( inst.input ? inst.input[ 0 ] : null ), [ dateStr, inst ] );  // trigger custom callback
                    } else if ( inst.input ) {
                        inst.input.trigger( "change" ); // fire the change event
                    }

                    if ( inst.inline || this._curInst.id.includes( 'wpc-filters-alt-date' ) ) {
                        this._updateDatepicker( inst );
                    } else {
                        this._hideDatepicker();
                        this._lastInput = inst.input[ 0 ];
                        if ( typeof( inst.input[ 0 ] ) !== "object" ) {
                            inst.input.trigger( "focus" ); // restore focus
                        }
                        this._lastInput = null;
                    }
                }
            };

            Object.assign( $.datepicker, updatedProperties );

            $.each( wpcDateFilters, function ( fid, dateFilter ) {

                if ( $("#wpc-filters-date-from-"+ fid).length < 1 ) {
                    return true;
                }
                let setId = $("#wpc-filters-date-from-"+ fid).data('set');

                let applyButtonMode = false;

                if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
                    applyButtonMode = true;
                }
                if(applyButtonMode && wpcInstantRecount){
                    wpcDateFiltersL10n.closeText = wpcDateFiltersL10n.applyText
                }

                let pickerOptions = {};
                let timeFormat = dateFilter['time_format'].includes('s') ? 'HH.mm.ss' : 'HH.mm.00';
                if ( dateFilter['date_type'] === 'date' ) {
                    let yearMin = $("#wpc-filters-date-from-"+ fid).data('from').slice(0,4);
                    let yearMax = $("#wpc-filters-date-to-"+ fid).data('to').slice(0,4);

                    pickerOptions = {
                        dateFormat: dateFilter['date_format'], // will be shown in visible field
                        altFieldTimeOnly: false,
                        altField: '#wpc-filters-date-from-' + fid,
                        altFormat: 'yy-mm-dd',
                        changeYear: true,
                        yearRange: yearMin+':'+yearMax,
                        changeMonth: true,
                        showButtonPanel: true,
                        onClose: function( dateText, inst ){
                            if( isDonePressed( inst ) ) {
                                if(applyButtonMode && wpcInstantRecount){
                                    updateInputDateData(dateText, inst.input)
                                }
                                processRangeForm( event, $("#wpc-filter-date-range-form-"+ fid ) );
                            }
                        },
                        beforeShow: function(input, inst) {
                            $('#ui-datepicker-div').addClass('wpc-filter-datepicker wpc-filter-datepicker-'+ fid);
                        },
                        onUpdateDatepicker: function( inst ) {
                            let $inp = $(inst.input);
                            let w = $inp.outerWidth();
                            $(".wpc-filter-datepicker-"+ fid).css('width', w + 'px');
                        }
                    };

                    $( "#wpc-filters-alt-date-from-" + fid  ).datepicker( pickerOptions );
                    pickerOptions.altField = '#wpc-filters-date-to-' + fid;
                    $( "#wpc-filters-alt-date-to-" + fid ).datepicker( pickerOptions );

                } else if ( dateFilter['date_type'] === 'datetime' ) {
                    $.timepicker.regional[wpcDateFiltersLocale] = wpcDateFiltersL10n;
                    $.timepicker.setDefaults(wpcDateFiltersL10n);
                    let yearMin = $("#wpc-filters-date-from-"+ fid).data('from').slice(0,4);
                    let yearMax = $("#wpc-filters-date-to-"+ fid).data('to').slice(0,4);

                    pickerOptions = {
                        dateFormat: dateFilter['date_format'],
                        timeFormat: dateFilter['time_format'], // Depends from localization
                        altFieldTimeOnly: false,
                        altField: '#wpc-filters-date-from-' + fid,
                        altFormat: 'yy-mm-dd',
                        altTimeFormat: timeFormat, // Depends from format HH.mm.ss or HH.mm.00
                        altSeparator: 't',
                        changeYear: true,
                        yearRange: yearMin+':'+yearMax,
                        changeMonth: true,
                        showButtonPanel: true,
                        controlType: 'select',
                        oneLine: true,
                        onClose: function( dateText, inst ){
                            if( isDonePressed( inst )) {
                                if(applyButtonMode && wpcInstantRecount){
                                    updateInputDateData(dateText, inst.input)
                                }
                                processRangeForm( event, $("#wpc-filter-date-range-form-"+ fid ) );
                            }
                        },
                        beforeShow: function(input, inst) {
                            $('#ui-datepicker-div').addClass('wpc-filter-datepicker wpc-filter-datepicker-'+ fid);
                        },
                        onUpdateDatepicker: function( inst ) {
                            let $inp = $(inst.input);
                            let w = $inp.outerWidth();
                            $(".wpc-filter-datepicker-"+ fid).css('width', w + 'px');
                        }
                    };

                    $( "#wpc-filters-alt-date-from-" + fid ).datetimepicker( pickerOptions );
                    pickerOptions.altField = '#wpc-filters-date-to-' + fid;
                    $( "#wpc-filters-alt-date-to-" + fid ).datetimepicker( pickerOptions );

                } else if ( dateFilter['date_type'] === 'time' ) {
                    $.timepicker.regional[wpcDateFiltersLocale] = wpcDateFiltersL10n;
                    $.timepicker.setDefaults(wpcDateFiltersL10n);

                    pickerOptions = {
                        timeFormat: dateFilter['time_format'],
                        altField: '#wpc-filters-date-from-' + fid,
                        altFieldTimeOnly: false,
                        altTimeFormat: timeFormat,
                        controlType: 'select',
                        oneLine: true,
                        onClose: function( dateText, inst ){
                            if( isDonePressed( inst ) ) {
                                if(applyButtonMode && wpcInstantRecount){
                                    updateInputDateData(dateText, inst.input)
                                }
                                processRangeForm( event, $("#wpc-filter-date-range-form-"+ fid ) );
                            }
                        },
                        beforeShow: function(input, inst) {
                            $('#ui-datepicker-div').addClass('wpc-filter-datepicker wpc-filter-datepicker-'+ fid);
                        },
                        onUpdateDatepicker: function( inst ) {
                            let $inp = $(inst.input);
                            let w = $inp.outerWidth();
                            $(".wpc-filter-datepicker-"+ fid).css('width', w + 'px');
                        }
                    };

                    $( "#wpc-filters-alt-date-from-" + fid  ).timepicker( pickerOptions );
                    pickerOptions.altField = '#wpc-filters-date-to-' + fid;
                    $( "#wpc-filters-alt-date-to-" + fid ).timepicker( pickerOptions );
                }
            });
        }

        $('.wpc-help-tip').tipTip({
            'activation': 'hover',
            'attribute': 'data-tip',
            'fadeIn':    50,
            'fadeOut':   50,
            'delay':     200,
            'keepAlive': true,
            'maxWidth': "220px",
        });
    }

    function updateInputDateData(dateText, $input){
        if(typeof $input.data('wpcTempFrom') !== 'undefined'){
            $input.data('wpcTempFrom', dateText)
            $input.attr('data-wpc-temp-from', dateText)
        }
        if(typeof $input.data('wpcTempTo') !== 'undefined'){
            $input.data('wpcTempTo', dateText)
            $input.attr('data-wpc-temp-to', dateText)
        }
    }

    function wpcSsMobileBrowser() {
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        const isMobile = /Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent);

        return isMobile;
    }

    function wpcIsDesktopSafari() {
        const userAgent = navigator.userAgent;
        const isSafari = /^((?!chrome|android|crios|fxios).)*safari/i.test(userAgent);
        const isNotMobile = !/Mobile|iPhone|iPad|iPod/i.test(userAgent);

        return isSafari && isNotMobile;
    }

    function wpcInitSelect2( widgetClass,  ) {
        if( typeof $.fn.select2 === 'undefined'){
            return;
        }

        // Destroy existing select2 instances before re-initializing
        // to prevent duplicate event listeners that block mobile scroll
        $('.wpc-filters-widget-select').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });
        $('.wpc-orderby-select').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });

        let wpcUserAgent = navigator.userAgent.toLowerCase();
        let wpcIsAndroid = wpcUserAgent.indexOf("android") > -1;

        let wpcAllowSearchField = 0;
        if( wpcIsAndroid ) {
            wpcAllowSearchField = Infinity;
        }

        if( wpcIsDesktopSafari() ){
            wpcAllowSearchField = 10;
        }

        const wpcHideEmpty = $(`.${widgetClass}`).data('wpc-hide-empty');
        const useApplyButton = $(`.${widgetClass}`).data('wpc-use-apply-button');

        const isHideEmpty = wpcHideEmpty === 'yes';



        $('.wpc-filters-widget-select').select2({
            dropdownCssClass: 'wpc-filter-everything-dropdown',
            dropdownParent: $('.'+widgetClass+' .wpc-filters-widget-content'),
            templateResult: function( data, container ) {
                let postsCount = $(data.element).data('count');
                let wpcSlug = $(data.element).data('wpcSlug');
                let wpcEName = $(data.element).data('wpcEName');
                if($(data.element).hasClass('wpc-dropdown-default')){
                    data.text = $(data.element).text();
                }
                if(wpcSlug !== undefined && wpcEName !== undefined){
                    $(container).addClass(`select2-${wpcEName}-${wpcSlug}`);
                }
                $(container).addClass('wpc-select2-term-id-' + data.id);
                if($(data.element).hasClass('wpc-show-with-parent-false')){
                    $(container).addClass('wpc-show-with-parent-false');
                }
                if($(data.element).hasClass('wpc-ask-to-parent-display')){
                    $(container).addClass('wpc-ask-to-parent-display');
                }
                if ( isHideEmpty && useApplyButton && postsCount <= 0 ) {
                    $(container).addClass('wpc-term-count-hidden-0');
                }
                return wpcSelect2Template( data );
            },
            templateSelection: function( data, container ) {
                let postsCount = $(data.element).data('count');
                let wpcSlug = $(data.element).data('wpcSlug');
                let wpcEName = $(data.element).data('wpcEName');;
                if($(data.element).hasClass('wpc-dropdown-default')){
                    data.text = $(data.element).text();
                }
                if(wpcSlug !== undefined && wpcEName !== undefined){
                    $(container).addClass(`select2-${wpcEName}-${wpcSlug}`);
                }
                $(container).addClass('wpc-select2-term-id-' + data.id);
                if($(data.element).hasClass('wpc-show-with-parent-false')){
                    $(container).addClass('wpc-show-with-parent-false');
                }
                if($(data.element).hasClass('wpc-ask-to-parent-display')){
                    $(container).addClass('wpc-ask-to-parent-display');
                }
                if ( isHideEmpty && useApplyButton && postsCount <= 0 ) {
                    $(container).addClass('wpc-term-count-hidden-0');
                }
                return wpcSelect2Template( data );
            },
            minimumResultsForSearch: wpcAllowSearchField,
        });

        $('.wpc-orderby-select').select2({
            dropdownCssClass: 'wpc-filter-everything-dropdown',
            dropdownParent: $('.wpc-after-sorting-form'),
            templateResult: function(data) {
                // We only really care if there is an element to pull classes from
                if (!data.element) {
                    return data.text;
                }
                let $dr_element = $(data.element);
                let $dr_wrapper = $('<span></span>');
                $dr_wrapper.addClass($dr_element[0].className);
                $dr_wrapper.text(data.text);

                return $dr_wrapper;
            },
            minimumResultsForSearch: Infinity
        });
    }

    function wpcSelect2Template( data ) {
        // We only really care if there is an element to pull classes from
        if ( ! data.element ) {
            return data.text;
        }

        let theImageSrc = $(data.element).data('image');
        let brandImageSrc = $(data.element).data('brand');
        let theColor = $(data.element).data('color');
        let starRating = $(data.element).data('starRating');
        let innerHtml = data.text;
        let postsCount = $(data.element).data('count');
        let additionalClass = '';

        if ( typeof theImageSrc !== 'undefined' ) {

            additionalClass = 'wpc-item-has-swatch';
            innerHtml = $('<span data-label="' + data.text +'" class="wpc-term-swatch-wrapper wpc-term-swatch-image"><img src="'+theImageSrc+'" class="wpc-term-image" /></span><span class="wpc-term-name">'+data.text+'</span>');

        } else if ( typeof theColor !== 'undefined'  ) {

            additionalClass = 'wpc-item-has-swatch';

            let swatch = '<span data-label="' + data.text +'" class="wpc-term-swatch-wrapper">';
            if ( theColor === 'none' ){
                swatch += '<span class="wpc-term-swatch wpc-no-swatch-yet">';
            } else {
                swatch += '<span class="wpc-term-swatch" style="background-color:'+theColor+'">';
            }
            swatch += '</span></span><span class="wpc-term-name">'+data.text+'</span>';

            innerHtml = $( swatch );

        } else if ( typeof brandImageSrc !== 'undefined' ) {

            additionalClass = 'wpc-item-has-brand';
            innerHtml = $('<span data-label="' + data.text +'" class="wpc-term-image-wrapper"><img src="'+brandImageSrc+'"/></span><span class="wpc-term-name">'+data.text+'</span>');

        } else if ( typeof starRating !== 'undefined' &&  starRating > 0) {
            additionalClass = 'wpc-item-has-star-rating';
            innerHtml = $(data.element.innerHTML);
        }

        let $dr_element = $(data.element);
        let $dr_wrapper = $('<span></span>');
        $dr_wrapper.addClass($dr_element[0].className);
        if ( additionalClass !== '' ){
            $dr_wrapper.addClass( additionalClass );
        }
        $dr_wrapper.html( innerHtml );
        if ( typeof postsCount !== 'undefined' ){
            $dr_wrapper.append( '<span class="wpc-term-count"><span class="wpc-term-count-brackets-open">(</span><span class="wpc-term-count-value">'+postsCount+'</span><span class="wpc-term-count-brackets-close">)</span></span>' );
        }

        return $dr_wrapper;
    }

    function wpcGetCookie(name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ))
        return matches ? decodeURIComponent(matches[1]) : undefined
    }

    //Example: wpcSetCookie('user', 'John', {secure: true, 'max-age': 3600});
    function wpcSetCookie(name, value, props) {
        props = props || {}
        let exp = props.expires
        if (typeof exp == "number" && exp) {
            let d = new Date()
            d.setTime(d.getTime() + exp*1000)
            exp = props.expires = d
        }

        if(exp && exp.toUTCString) { props.expires = exp.toUTCString() }
        value = encodeURIComponent(value)

        let updatedCookie = name + "=" + value
        for(let propName in props){
            updatedCookie += "; " + propName
            let propValue = props[propName]
            if(propValue !== true){ updatedCookie += "=" + propValue }
        }
        document.cookie = updatedCookie
    }

    function setStatusCookie( fid, wpcListCookieName )
    {
        let status = wpcGetCookie(wpcListCookieName);
        let _fids  = new Array();

        fid = fid.toString();

        // In case there is no Cookies yet
        if( typeof status === 'undefined' ){
            status = '';
        }else{
            status = status.trim();
            _fids = status.split(',');
        }

        // Filter from empty elements
        _fids = _fids.filter(function (el) {
            return el != '';
        });

        // Remove possible existing closed/opened to avoid double commands e.g. 151 and -151
        let reversal = -fid;
        let pos = _fids.indexOf( reversal.toString() );

        if ( pos !== -1 ) {
            _fids.splice(pos, 1);
        }

        if( _fids.indexOf(fid) === -1 ){
            _fids.push(fid);

            let newStatus = '';

            if( _fids.length === 0 ){
                newStatus = fid;
            }else{
                newStatus = _fids.join();
            }

            wpcSetCookie( wpcListCookieName, newStatus, {path: '/', 'max-age': 2592000} )
        }

    }

    function wpcCloseFiltersContainer(setId)
    {
        let $wrapper = $('.wpc-filter-set-'+setId);
        let $content = $('.wpc-filter-set-'+setId+' .wpc-filters-widget-content');
        $('.wpc-open-button-'+setId+' .wpc-filters-open-widget').removeClass('wpc-opened');
        $('html').removeClass('wpc-overlay-visible');
        $content.removeClass('wpc-filters-widget-opened');

        if( wpcPopupCompatMode ) {
            setTimeout(() => {

                let wpcZindex = '';
                let $currentTag = false;

                $content.parents().each(function (index, tag) {
                    $currentTag = $(tag);
                    wpcZindex = $currentTag.data('wpczindex');
                    // Saved z-index for
                    if (wpcZindex !== 'undefined') {
                        $currentTag.css('z-index', wpcZindex);
                    }

                    if ($currentTag.hasClass('wpc-force-visibility')) {
                        $currentTag.removeClass('wpc-force-visibility');
                    }
                });

                setTimeout(() => {
                    $(".wpc-was-invisible").css('opacity', '1')
                        .removeClass('wpc-was-invisible');
                }, 300);

            }, 260);
        }

        $wrapper.removeClass('wpc-container-opened');
    }

    function wpcOpenFiltersContainer(setId)
    {
        let $wrapper    = $('.wpc-filter-set-'+setId);
        let $content    = $('.wpc-filter-set-'+setId+' .wpc-filters-widget-content');
        let wpcZindex   = '';
        let wpcVisibility = '';
        let wpcTransform = '';
        let $currentTag = false;

        if( $content.length < 1 ){
            return true;
        }

        if( wpcPopupCompatMode ) {
            $content.parents().each(function (index, tag) {
                $currentTag     = $(tag);
                wpcZindex       = $currentTag.css('z-index');
                wpcVisibility   = $currentTag.is(":visible");
                wpcTransform    = $currentTag.css('transform');

                // Save current z-index for future
                if (wpcZindex !== 'auto') {
                    $currentTag.data('wpczindex', wpcZindex);
                }

                $currentTag.css('z-index', 'auto');

                // Save current display, opacity and visibility values
                if (!wpcVisibility || wpcTransform !== 'none') {
                    if (!$currentTag.hasClass('widget_wpc_filters_widget')
                        &&
                        !$currentTag.hasClass('wpc-filters-main-wrap')
                    ) {
                        $currentTag.css('opacity', '0');
                        $currentTag.addClass('wpc-force-visibility wpc-was-invisible');
                    }
                }
            });

            if( wpcUseSelect2 === 'yes' ){
                wpcInitSelect2( 'wpc-filter-set-'+setId );
            }
        }

        $('.wpc-open-button-'+setId+' .wpc-filters-open-widget').addClass('wpc-opened');
        $('html').addClass('wpc-overlay-visible');
        $('body').data('set', setId);

        $content.addClass('wpc-filters-widget-opened');
        $wrapper.addClass('wpc-container-opened');
        $('.wpc-filter-set-'+setId+' .wpc-filters-close-button').attr('href', window.location.href);

    }

    function wpcOpenContainer( setId ) {
        let $wrapper = $( '.wpc-filter-set-'+setId );

        if( $wrapper.length < 1 ){
            alert('There is no filter widget with ID '+setId+' on this page');
            return;
        }

        if( $wrapper.hasClass('wpc-container-opened') ){
            wpcCloseFiltersContainer(setId);
        }else{
            wpcOpenFiltersContainer(setId);
        }
    }

    function wpcLockApplyButton( setId )
    {
        $(".wpc-filter-set-"+setId).addClass('is-active');
        // We have only to check what the element was last focused
        // if( $('.wpc-search-field').length < 1 ){
        $(".wpc-filter-set-"+setId+" .wpc-filters-submit-button").addClass('on-hold');
        $(".wpc-filter-set-"+setId+" .wpc-filters-reset-button").addClass('on-hold');
        // }
    }

    function wpcUnlockApplyButton( setId )
    {
        $(".wpc-filter-set-"+setId).removeClass('is-active');
        $(".wpc-filter-set-"+setId+" .wpc-filters-submit-button").removeClass('on-hold');
        $(".wpc-filter-set-"+setId+" .wpc-filters-reset-button").removeClass('on-hold');
    }

    function wpcShowSpinner()
    {
        $('.wpc-spinner, html').addClass('is-active');
    }

    function wpcHideSpinner()
    {
        $('.wpc-spinner, html').removeClass('is-active');
    }

    $.fn.wpcInitSlider = function ( form ) {

        let $el = form.parents(wpcWidgetContainer);
        let setId = $el.data('set');
        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes( setId ) ){
            applyButtonMode = true;
        }
        // Default valued at start
        let $min = form.find('.wpc-filters-range-min');
        let $max = form.find('.wpc-filters-range-max');
        let $slider = form.find('.wpc-filters-range-slider-control');
        let step = parseFloat( $min.attr('step') );

        let initialMinVal = parseFloat( $min.data('min') );
        let initialMaxVal = parseFloat( $max.data('max') );

        // Values after applying filter
        let curMinVal = parseFloat( $min.val() );
        let curMaxVal = parseFloat( $max.val() );

        if( curMaxVal !== initialMaxVal ){
            $max.parent().find('.wpc-range-clear').show();
        }else{
            $max.parent().find('.wpc-range-clear').hide();
        }

        if( curMinVal !== initialMinVal ){
            $min.parent().find('.wpc-range-clear').show();
        }else{
            $min.parent().find('.wpc-range-clear').hide();
        }


        // Setting value into form inputs when slider is moving
        $slider.slider({
            min: initialMinVal,
            max: initialMaxVal,
            values: [curMinVal, curMaxVal],
            range: true,
            step: step,
            slide: function (event, elem) {
                let instantMinVal = elem.values[0];
                let instantMaxVal = elem.values[1];

                $min.val(instantMinVal);
                $max.val(instantMaxVal);
            },
            change: function (event) {
                // It is better always to submit slider automatically to avoid empty intersection occurrence
                submitSliderForm(event, form);
            }
        });

        form.submit(function (e) {
            //Remove ? sign if form is empty
            if (($(this).serialize().length === 0)) {
                e.preventDefault();
                window.location.assign(window.location.pathname);
            }
        });
    }

    function submitSliderForm(event, form) {
        if (event.originalEvent) {
            processRangeForm( event, form );
        }
    }

    $(document).on('click', '.wpc-filters-range-min-column .wpc-range-clear', function(event) {
        let rangeInput = $(this).parent().find('input');
        let minVal = rangeInput.data('min');
        $(this).hide();
        rangeInput.val(minVal).change();
    });

    $(document).on('click', '.wpc-filters-range-max-column .wpc-range-clear', function(event) {
        let rangeInput = $(this).parent().find('input');
        let maxVal = rangeInput.data('max');
        $(this).hide();
        rangeInput.val(maxVal).change();
    });

    function processRangeForm( event, form ){
        let $el = form.parents(wpcWidgetContainer);
        let setId = $el.data('set');
        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes( setId ) ){
            applyButtonMode = true;
        }
        let low_suffix  = 'min';
        let high_suffix = 'max';

        if ( form.hasClass('wpc-filter-date-range-form') ) {
            low_suffix  = 'from';
            high_suffix = 'to';
        }

        let $min = form.find( '.wpc-filters-range-' + low_suffix );
        let $max = form.find( '.wpc-filters-range-' + high_suffix );

        if ( low_suffix === 'min' && high_suffix ===  'max' ) {
            var curMinVal = parseFloat( $min.val() );
            var curMaxVal = parseFloat( $max.val() );
        } else {
            var curMinVal = $min.val().toString();
            var curMaxVal = $max.val().toString();
        }

        var initialMin = $min.data( low_suffix );
        var initialMax = $max.data( high_suffix );

        // The form has slider
        if( form.hasClass('wpc-form-has-slider') ){
            let $slider = form.find('.wpc-filters-range-slider-control');
            // in Case of e.type === 'change' we have to set slider values
            if ( event.type === 'change' ){
                $slider.slider("option", "values", [curMinVal, curMaxVal]);
            }
        }

        if(!applyButtonMode || !wpcInstantRecount){
            if (curMinVal === initialMin) {
                $min.attr('disabled', true);
            }

            if (curMaxVal === initialMax) {
                $max.attr('disabled', true);
            }
        }

        if (applyButtonMode && wpcInstantRecount) {
            wpcApplyEngine.compareInputWithRangeList(form, applyButtonMode);
            wpcApplyEngine.applyJsMode($el, setId)
        } else if (wpcAjax || applyButtonMode) {
            event.preventDefault();
            let search = form.serialize();
            let questionParam = '?'
            if (!search) {
                questionParam = '';
            }
            let wpcLink = form.attr('action') + questionParam + search;

            wpcSendFilterRequest(wpcLink, $el, applyButtonMode);

            $min.attr('disabled', true);
            $max.attr('disabled', true);

        } else {
            form.trigger('submit');
        }
    }

    function wpcSendFilterRequest( link, widget, applyButtonMode, onComplete ){

        onComplete = (typeof onComplete !== 'undefined') ? onComplete : false;
        removeElement($('.wpc-front-error'));

        let requestParams               = {};
        requestParams.flrt_ajax_link    = link;
        requestParams.wpcAjaxAction     = 'filter';
        let setId                       = widget.data('set');
        let widgetClass                 = 'wpc-filter-set-'+setId;
        let targetPostsContainer        = wpcPostContainers['default'];
        let wpcUsedRouter = false;

        if( typeof wpcPostContainers[setId] !== "undefined" ){
            targetPostsContainer = wpcPostContainers[setId];
        }

        // Disable Apply button for Pop-up widget as its behavior is the same
        if( applyButtonMode ){
            if( $("body").hasClass("wpc_show_bottom_widget") ){
                if( window.innerWidth <= wpcMobileWidth ){
                    applyButtonMode = false;
                }
            }
        }

        $.ajax({
            'method': 'POST',
            'data': requestParams,
            'url': link,
            'dataType': 'html',
            beforeSend: function () {
                if( wpcWaitCursor ){
                    $('html, body').css("cursor", "wait");
                }

                let $a_el = $(widget).find('.wpc-filters-apply-button');

                $a_el.removeClass('wpc-posts-loaded');

                let oldLink = $a_el.attr('href');

                $a_el.attr('href', link);
                $a_el.data('href', oldLink);

                // $(".wpc-filters-section-"+setId).find(".wpc-filters-submit-button").attr('href', link);

                if( applyButtonMode ){
                    wpcLockApplyButton( setId );
                    // Legacy Apply-button mode only locks the button (pre-1.9.3 behaviour)
                    if( wpcInstantRecount ){
                        wpcShowSpinner();
                    }
                }else{
                    wpcShowSpinner();
                }
            },
            complete: function () {
                if(onComplete !== false){
                    eval(onComplete+'(setId)');
                }
                if( wpcWaitCursor ) {
                    $('html, body').css("cursor", "auto");
                }

                wpcInitiateAll();

                if( applyButtonMode ){
                    wpcUnlockApplyButton(setId);
                    if( wpcInstantRecount ){
                        wpcHideSpinner();
                    }
                } else if( !wpcUsedRouter ) {
                    wpcHideSpinner();
                }
            },
            success: function ( response ) {
                if ( typeof response !== 'undefined' ) {
                    // Products
                    // Wrap response to allow .find method search inner elements.
                    response                    = '<div class="responseWrapper">'+response+'</div>';
                    let $response               = $(response);
                    let $responsePostsContainer = $response.find(targetPostsContainer);
                    let currentSeoRuleId        = $response.find('#wpc-seo-rule-id').data('seoruleid');
                    let isFilterRequest         = $response.find('.wpc-filters-main-wrap').hasClass('wpc-filter-request');

                    if($('#wpc-filter-everything-js-before').length > 0){
                        wpcApplyEngine.updateWpcFilterJsonData(response)
                    }


                    if ( currentSeoRuleId > 0 ) {
                        currentState = true;
                    } else {
                        currentState = false;
                    }

                    if ( ! currentState && ! prevState ) {
                        toReplaceSEO = false;
                    } else {
                        toReplaceSEO = true;
                    }

                    if( applyButtonMode ){
                        // Filters Widget
                        wpcReloadFiltersWidget( $response, widgetClass );
                        if(window.innerWidth > wpcMobileWidth ){
                            if(isFilterRequest){
                                wpcEnableStickyButtons(true);
                                wpcUpdateStickyButtons();
                            }

                            if(!isFilterRequest){
                                wpcEnableStickyButtons(false);
                                wpcUpdateStickyButtons();
                            }
                        }
                        if( !wpcInstantRecount ){
                            // Legacy Apply-button recount: a per-click request refreshes
                            // only the widget and counters; posts wait for the Apply click
                            return;
                        }
                    }

                    if(!applyButtonMode && !isFilterRequest && window.innerWidth > wpcMobileWidth ){
                        wpcEnableStickyButtons(false);
                        wpcUpdateStickyButtons();
                    }

                    if( ( $responsePostsContainer.length > 0 ) && wpcFilterFront.wpcAjaxEnabled && wpcQueryOnThePageSets.includes( setId ) ){
                        if( isFilterRequest ) {
                            $("body").addClass('wpc_is_filter_request');
                        } else {
                            $("body").removeClass('wpc_is_filter_request');
                        }
                        // But this works on TV also
                        //Try reinitializing the WooCommerce product-collection block via Interactivity Router
                        wpcUsedRouter = false;
                        const wpcImportmap = document.querySelector('script[type="importmap"]');
                        if( wpcImportmap ){
                            try {
                                const wpcMap = JSON.parse(wpcImportmap.textContent);
                                const wpcRouterUrl = wpcMap.imports && wpcMap.imports['@wordpress/interactivity-router'];

                                if( wpcRouterUrl && $(targetPostsContainer).find('[data-wp-interactive]').length > 0 ){
                                    wpcUsedRouter = true;
                                    import(wpcRouterUrl).then(function(module){
                                        if( module.actions && typeof module.actions.navigate === 'function' ){
                                            module.actions.navigate(link, { force: true }).then(function(){
                                                wpcHideSpinner();
                                            }).catch(function(){
                                                $(targetPostsContainer).html( $responsePostsContainer.html() );
                                                wpcHideSpinner();
                                            });
                                        }
                                    }).catch(function(){
                                        $(targetPostsContainer).html( $responsePostsContainer.html() );
                                        wpcHideSpinner();
                                    });
                                }
                            } catch(e) {}
                        }

                        if( !wpcUsedRouter ){
                            $(targetPostsContainer).html( $responsePostsContainer.html() );
                        }

                        // Re-init Divi modules
                        const isDiviTheme = document.body.classList.contains('wp-theme-Divi') ||
                            document.body.classList.contains('theme-Divi');
                        if( isDiviTheme && typeof window.et_pb_init_modules === 'function' ){
                            window.et_pb_init_modules();
                        }
                        // wpcPostsWereLoaded = true;

                        // Mark the "Show" button to not reload content
                        $(widget).find('.wpc-filters-apply-button').addClass('wpc-posts-loaded');

                        //@todo update selected terms if them outside of posts container

                        if ( toReplaceSEO ) {
                            let responseTitle     = $response.find('title').text();
                            let responseCanonical = $response.find('link[rel="canonical"]').attr('href');

                            // If h1 outside of posts container
                            if( $responsePostsContainer.find('h1').length < 1 ){
                                if( $response.find('h1').length > 0){
                                    $('h1')[0].replaceWith( $response.find('h1')[0] );
                                }
                            }

                            // If seoText container is outside from posts container
                            if( $responsePostsContainer.find('.wpc-page-seo-description').length < 1 ){
                                let wpcSeoTextContainer = $response.find('.wpc-page-seo-description');
                                let originalSeoTextContainer = $('.wpc-page-seo-description');
                                if( wpcSeoTextContainer.length > 0 && originalSeoTextContainer.length > 0){
                                    $('.wpc-page-seo-description')[0].replaceWith( wpcSeoTextContainer[0] );
                                }
                            }

                            // Replace title
                            if( typeof responseTitle !== 'undefined' && responseTitle !== '' ){
                                $(document).attr( 'title', responseTitle );
                            }

                            // Handle <meta name="description" /> tag
                            handleMetaTag('description', response);

                            // Handle <meta name="robots" /> tag
                            handleMetaTag('robots', response);

                            // Handle Canonical
                            if( typeof responseCanonical !== 'undefined' && responseCanonical !== '' ){
                                // Replace content if tag exists
                                if( $('link[rel="canonical"]').length > 0 ){
                                    $('link[rel="canonical"]').attr('href', responseCanonical );
                                } else {
                                    // Append meta tag
                                    $('head').append('<link rel="canonical" href="'+responseCanonical+'" />');
                                }
                            }else{
                                if( $('link[rel="canonical"]').length > 0 ){
                                    $('link[rel="canonical"]').remove();
                                }
                            }
                        }

                        // If Filters open button outside of posts container
                        if( $responsePostsContainer.find('.wpc-open-button-'+setId).length < 1 ) {
                            let wpcButtonInnerContent = $response.find('.wpc-open-button-'+setId+' .wpc-button-inner');

                            if( wpcButtonInnerContent.length > 0 ) {
                                $('.wpc-open-button-'+setId).each( function ( bIndex, bUtton ) {
                                    if ( $(this).parent('div').hasClass('wpc-filters-main-wrap') ){
                                        return true;
                                    }
                                    $(this).find(".wpc-button-inner").replaceWith( wpcButtonInnerContent[0] );
                                } );
                            }
                        }

                        window.history.pushState({wpcHandler: 'wpcFilterEverything'}, null, link);

                        prevState = currentState;
                    } else {
                        if ( $(targetPostsContainer).length === 0 && wpcFilterFront.wpcAjaxEnabled ) {
                            alert( noPostsContainerMsg );
                        }
                    }

                    let wpcPostsFound   = $response.find('.'+widgetClass).find('.wpc-posts-found').data('found');
                    wpcPostsFound       = parseFloat( wpcPostsFound );

                    // Chips
                    wpcReloadChips( $response );

                    // Sorting widget
                    wpcReloadSorting( $response );

                    // Filters Widget. It modifies $response so it is better to fire it in the end
                    wpcReloadFiltersWidget( $response, widgetClass );


                    //trigger events
                    $(document).trigger( 'ready' );
                    $(window).trigger( 'scroll' );
                    $(window).trigger( 'resize' );

                    // a3 Lazy Load support
                    $(window).trigger( 'lazyshow' );

                    wpcFixWoocommerceOrder();

                    //check rating stars
                    flrtStarCheck();

                    let applyButtonFilterSet = false;
                    if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes( setId ) ){
                        applyButtonFilterSet = true;
                    }

                    if( ! wpcIsMobile && wpcAutoScroll && ( wpcPostsFound < wpcPostsPerPage[setId] || applyButtonFilterSet ) ){
                        if( $(targetPostsContainer).length > 0 ){
                            $('body, html').animate({ scrollTop:$(targetPostsContainer).offset().top - wpcAutoScrollOffset });
                        }
                    }

                    // Re-init Elementor actions
                    if( typeof( elementorFrontend ) !== 'undefined' ){
                        if( $responsePostsContainer.hasClass('elementor-element') ){
                            $(targetPostsContainer+'.elementor-element').each(
                                function() {
                                    elementorFrontend.elementsHandler.runReadyTrigger($(this));
                                }
                            );
                        } else {
                            $(targetPostsContainer+' .elementor-element').each(
                                function() {
                                    elementorFrontend.elementsHandler.runReadyTrigger($(this));
                                }
                            );
                        }
                    }
                }
            },

            error: function (response) {
                wpcHideSpinner();
                let $a_el = $(widget).find('.wpc-filters-apply-button');
                let oldLink = $a_el.data('href');
                $a_el.attr('href', oldLink );
            }
        });

    }

    function handleMetaTag( tagName, response )
    {

        let tagContent = $(response).find('meta[name="'+tagName+'"]').attr('content');
        if( typeof tagContent !== 'undefined' ){
            // Replace content if tag exists
            if( $('meta[name="'+tagName+'"]').length > 0 ){
                $('meta[name="'+tagName+'"]').attr('content', tagContent );
            } else {
                // Append meta tag
                $('head').append('<meta name="'+tagName+'" content="'+tagContent+'" />');
            }
        }else{
            if( $('meta[name="'+tagName+'"]').length > 0 ){
                $('meta[name="'+tagName+'"]').remove();
            }
        }
    }

    function wpcFixWoocommerceOrder() {
        $('.woocommerce-ordering').on('change', 'select.orderby', function () {
            $(this).closest('form').submit();
        });
    }

    function wpcReloadFiltersWidget( $response, widgetClass ){
        // Replace parts
        // let targetWidget = '.'+widgetClass;
        // let $response    = $response;
        // It seems we need to reload all widgets available on the page
        if( wpcIsMobile === true && ( wpcFilterFront.showBottomWidget === 'yes' ) ){

            $(wpcWidgetContainer).each( function ( index, widget ){
                let widgetSet = $(widget).data('set');
                let widgetClass = '.wpc-filter-set-'+widgetSet;

                // .wpc-filters-scroll-container
                // .wpc-filters-widget-containers-wrapper
                let newWidget       = $response.find(widgetClass+' .wpc-filters-scroll-container');
                let newPostsFound   = $response.find(widgetClass+' .wpc-filters-found-posts');

                // Replace all filters and chips
                if( newWidget.length > 0 ){
                    $(widgetClass).find('.wpc-filters-scroll-container').replaceWith( newWidget );
                }
                // Replace found posts number
                if( newPostsFound.length > 0  ){
                    $(widgetClass).find('.wpc-filters-found-posts').html( newPostsFound.html() );
                }

                if( wpcApplyButtonSets.includes( widgetSet ) ){
                    let applyLink = $(widgetClass+" .wpc-filters-submit-button").attr('href');
                    if( applyLink !== '' ){
                        $(".wpc-filters-widget-controls-container .wpc-filters-submit-button").attr('href', applyLink);
                    }
                }
            });

        } else {
            $(wpcWidgetContainer).each( function ( index, widget ) {
                let widgetSet = $(widget).data('set');
                let widgetClass = '.wpc-filter-set-'+widgetSet;

                let newWidget = $response.find(widgetClass);
                if (newWidget.length > 0) {
                    $(widgetClass).replaceWith(newWidget);
                }
            });
        }
    }

    function wpcReloadSorting( $response ){
        let wpcSortingForms   = $response.find('.wpc-sorting-form');
        if ( wpcSortingForms.length < 1 ) {
            return;
        }
        let originalSortingForms = $(".wpc-sorting-form");

        if( wpcSortingForms.length > 0 ){
            wpcSortingForms.each( function ( index, elem ){
                originalSortingForms[index].replaceWith(elem);
            });
        }
    }

    function wpcReloadChips( $response ){
        let $chips = $(".wpc-filter-chips-list");
        if ( $chips.length < 1 ) {
            return;
        }

        $chips.each( function ( index, chipsWidget ) {

            if( ( wpcIsMobile === true && ( wpcFilterFront.showBottomWidget !== 'yes' ) ) || wpcIsMobile === false ){
                // Do not replace Chips inside Filters widget
                if ( $(this).parent('div').hasClass('wpc-inner-widget-chips-wrapper') ){
                    return true;
                }
            }

            let chipsSetCount       = $(chipsWidget).data('setcount');
            let chipsWidgetClass    = '.wpc-filter-chips-'+chipsSetCount;
            let newChipsInstance    = $response.find(chipsWidgetClass);

            if ( newChipsInstance.length > 0 ) {
                // Do not use $(this) because reloaded widget kills it
                $(chipsWidgetClass).replaceWith( newChipsInstance );
            }
        });
    }

    window.addEventListener( 'popstate', function ( e ) {
        // @todo the last history step sometimes doesn't reload
        if( e.state !== null && e.state.hasOwnProperty('wpcHandler') ){
            if( e.state.wpcHandler === 'wpcFilterEverything' ){
                window.location.reload(true);
            }
        }
    });

    //check rating stars after upload
    function flrtStarCheck(){
        if($('label.flrt-star-label-checked').length > 0){
            $('#flrt-wpc-term-count').removeClass('flrt-change-blocked');
            let ratingNumChecked =  $('label.flrt-star-label-checked').data('ratingNum');
            let selectedAndAbove =  $('.flrt-stars-wpc-filter-content').data('selectedAndAbove');
            flrtGetRatingTermCount($('label.flrt-star-label-checked'))
            $('label.flrt-star-label').each(function( index ) {
                index += 1;
                if(!selectedAndAbove && index <= ratingNumChecked){
                    $('label.flrt-rating-numb-' + index).addClass('flrt-star-label-hover');
                }else if(selectedAndAbove && index >= ratingNumChecked) {
                    $('label.flrt-rating-numb-' + index).addClass('flrt-star-label-hover');
                }
            });
        }
    }
    flrtStarCheck();

    $(document).on('mouseenter', 'label.flrt-star-label', function() {
        if(!$('#flrt-wpc-term-count').hasClass('flrt-change-blocked')) {
            let ratingNum = $(this).data('ratingNum');
            let selectedAndAbove = $('.flrt-stars-wpc-filter-content').data('selectedAndAbove');
            $('label.flrt-remove-star-check').removeClass('flrt-star-label-not-checked');
            flrtGetRatingTermCount($(this));
            for (let i = 0; i <= 5; i++) {
                if (!selectedAndAbove && i <= ratingNum) {
                    $('label.flrt-rating-numb-' + i).addClass('flrt-star-label-hover');
                } else if (selectedAndAbove && i >= ratingNum) {
                    $('label.flrt-rating-numb-' + i).addClass('flrt-star-label-hover');
                } else {
                    $('label.flrt-rating-numb-' + i).removeClass('flrt-star-label-hover');
                }
            }
        }
    });

    $(document).on('click', 'label.flrt-star-label', function() {

        let $el = $(this).parents(wpcWidgetContainer);
        let setId = $el.data('set');
        let applyButtonMode = false;

        if( setId > 0 && wpcApplyButtonSets.length > 0 && wpcApplyButtonSets.includes(setId) ){
            applyButtonMode = true;
        }

        // The blocked flag freezes hover until the widget HTML is reloaded.
        // In instant Apply mode no reload follows the click, so the flag
        // would never be cleared and hover would stay dead until a full
        // page refresh
        if( !( applyButtonMode && wpcInstantRecount ) ){
            $('#flrt-wpc-term-count').addClass('flrt-change-blocked');
        }

        let ratingNum =  $(this).data('ratingNum');
        let selectedAndAbove =  $('.flrt-stars-wpc-filter-content').data('selectedAndAbove');
        $('label.flrt-remove-star-check').removeClass('flrt-star-label-not-checked');


        flrtGetRatingTermCount($(this));
        for (let i = 0; i <= 5; i++) {
            if(!selectedAndAbove && i <= ratingNum){
                $('label.flrt-rating-numb-' + i).addClass('flrt-star-label-hover');
            }if(selectedAndAbove && i >= ratingNum){
                $('label.flrt-rating-numb-' + i).addClass('flrt-star-label-hover');
            }
        }

        if(applyButtonMode && wpcInstantRecount) {
            const inputId = $(this).attr('for')
            const input = $('#'+inputId);
            // Clear state classes from the previous selection before painting the
            // new one, otherwise -checked/-not-checked leak when switching ratings
            $(this).parents('.flrt-stars-wpc-filter-content').find('label.flrt-star-label')
                .removeClass('flrt-star-label-hover flrt-star-label-checked flrt-star-label-not-checked')
            for (let i = 0; i <= 5; i++) {
                if(!selectedAndAbove && i <= ratingNum){
                    $('label.flrt-rating-numb-' + i).addClass('flrt-star-label-hover flrt-star-label-checked');
                }if(selectedAndAbove && i >= ratingNum){
                    $('label.flrt-rating-numb-' + i).addClass('flrt-star-label-hover flrt-star-label-not-checked');
                }
            }
            for (let i = 0; i <= 5; i++) {
                if(ratingNum != i){
                    $('label.flrt-rating-numb-' + i).data('wpc-was-checked', false);
                }
            }
            if ($(this).data('wpc-was-checked')) {
                // Deselect: clear ALL state classes at once — the same result the
                // input-state repaint produces for "nothing selected". Removing them
                // one-by-one by re-querying '.flrt-star-label-hover' left the
                // -checked/-not-checked classes behind after the hover class was gone
                $(this).parents('.flrt-stars-wpc-filter-content').find('label.flrt-star-label')
                    .removeClass('flrt-star-label-hover flrt-star-label-checked flrt-star-label-not-checked')
                $(this).data('wpc-was-checked', false)
            } else {
                $(this).data('wpc-was-checked', true);
            }
            flrtGetRatingTermCount($(this), applyButtonMode);
            /*$(this).parents('.flrt-stars-wpc-filter-content')
                .find('.flrt-star-label-hover')
                .removeClass('flrt-star-label-hover');*/
        }

    });

    $(document).on('mouseleave', '.flrt-stars-filter', function() {
        if(!$('#flrt-wpc-term-count').hasClass('flrt-change-blocked')){
            // The checked input is the single source of truth for the selected
            // rating: in instant Apply mode the client repaint puts
            // .flrt-star-label-checked on EVERY label up to the selected one,
            // so reading ratingNum from the first such label restores a wrong
            // (always one-star) state
            const $checkedInput = $(this).find('input.flrt-star-input:checked');
            if($checkedInput.length > 0){
                let ratingNumChecked =  Number($checkedInput.data('ratingNum'));
                let selectedAndAbove =  $('.flrt-stars-wpc-filter-content').data('selectedAndAbove');
                flrtGetRatingTermCount($('label.flrt-rating-numb-' + ratingNumChecked))
                $('label.flrt-remove-star-check').addClass('flrt-star-label-not-checked');
                for (let i = 0; i <= 5; i++) {
                    if(!selectedAndAbove && i <= ratingNumChecked){
                        $('label.flrt-rating-numb-' + i).addClass('flrt-star-label-hover');
                    }else if(selectedAndAbove && i >= ratingNumChecked){
                        $('label.flrt-rating-numb-' + i).addClass('flrt-star-label-hover');
                    }else{
                        $('label.flrt-rating-numb-' + i).removeClass('flrt-star-label-hover');
                    }
                }
            }else{
                $('#flrt-wpc-term-count').text('');
                for (let i = 1; i <= 5; i++) {
                    $('label.flrt-rating-numb-' + i).removeClass('flrt-star-label-hover');
                }
            }
        }
    });


    function flrtGetRatingTermCount(el, applyButtonMode = false){

        if(!$('#flrt-wpc-term-count').hasClass('flrt-change-blocked') || applyButtonMode){
            let wpcTermCount = el.data('wpcTermCount');
            let selectedAndAbove =  $('.flrt-stars-wpc-filter-content').data('selectedAndAbove');
            let showTermCount =  $('.flrt-stars-wpc-filter-content').data('showTermCount');
            if(showTermCount) {
                if(selectedAndAbove){
                    let ratingNum =  el.data('ratingNum');
                    let totalTerms = 0;
                    for (let i = ratingNum; i <= 5; i++) {
                        let showTermCount = $('label.flrt-rating-numb-' + i).data('wpcTermCount');
                        totalTerms += showTermCount;
                    }
                    $('#flrt-wpc-term-count').text(totalTerms);
                }else{
                    $('#flrt-wpc-term-count').text(wpcTermCount);
                }
                if(applyButtonMode){
                    const checked = $('input:checked', el.parents('.flrt-stars-filter'));
                    if(!checked.length){
                        $('#flrt-wpc-term-count').text('');
                    }
                }
            }
        }
    }


    // Sticky buttons: enabled lazily after first user interaction with filters
    let wpcStickyButtonsActivated = false;
    let wpcStickyForceOnActivate = false; // If true, fix immediately after first click even before chips are rendered

    // Checks if any filter is selected (chips present, inputs checked or a
    // dropdown holds a non-default option — incl. its select2 rendering)
    function wpcHasAnyFilterSelected() {
        const $wrapper = $('.wpc-filters-widget-wrapper');
        if (!$wrapper.length) return false;

        const hasChecks  = $wrapper.find('input[type=checkbox]:checked, input[type=radio]:checked').length > 0;
        // Smart spans render non-indexable chips as spans — they count too
        const hasChips   = $wrapper.find('.wpc-filter-chip a, .wpc-filter-chip [data-wpc-span-link], .wpc-filter-chip span.wpc-apply-button-chip').length > 0;
        const hasSelects = $wrapper.find('.wpc-filters-widget-select option:selected')
            .not('.wpc-dropdown-default').length > 0;

        if (hasChecks || hasChips || hasSelects) {
            return true;
        }

        // Numeric range and date inputs come pre-filled with their (possibly
        // narrowed) bounds — only a value differing from them is a selection.
        // Same rules as the Apply-URL builder in processElements: equality
        // with any bound attribute, or an applied date equal to the absolute
        // bound, means "not filtering".
        let hasRanges = false;
        $wrapper.find('form.wpc-filter-range-form input, form.wpc-filter-date-range-form-visible input')
            .not('.wpc-range-list-item').each(function () {
                const data = $(this).data();
                let val = $(this).val();
                if (typeof data.wpcEName === 'undefined' || val === '') return;
                if (typeof data.wpcTempFrom !== 'undefined' && typeof data.wpcAbsFrom !== 'undefined' && data.wpcTempFrom === data.wpcAbsFrom) return;
                if (typeof data.wpcTempTo !== 'undefined' && typeof data.wpcAbsTo !== 'undefined' && data.wpcTempTo === data.wpcAbsTo) return;
                if (typeof data.wpcAbsFrom !== 'undefined' && data.wpcAbsFrom === val) return;
                if (typeof data.wpcAbsTo !== 'undefined' && data.wpcAbsTo === val) return;
                if (typeof data.min !== 'undefined' || typeof data.max !== 'undefined') {
                    val = Number(val);
                }
                if (typeof data.absMin !== 'undefined' && data.absMin === val) return;
                if (typeof data.min !== 'undefined' && data.min === val) return;
                if (typeof data.absMax !== 'undefined' && data.absMax === val) return;
                if (typeof data.max !== 'undefined' && data.max === val) return;
                hasRanges = true;
                return false;
            });

        if (hasRanges) {
            return true;
        }

        const $search = $wrapper.find('.wpc-filter-search-form input.wpc-search-field');
        return $search.length > 0 && String($search.val() || '') !== '';
    }

    // Enable sticky buttons after user interaction; attach handlers once
    function wpcEnableStickyButtons(force = false) {
        if (!wpcStickyButtonsActivated) {
            const handler = wpcDebounce(wpcUpdateStickyButtons, 0);
            $(window).on('scroll.wpcStickyButtons', handler);
            wpcStickyButtonsActivated = true;
        }

        // Force immediate fixation on first click (no need to scroll)
        if (force) {
            wpcStickyForceOnActivate = true;
        }else{
            wpcStickyForceOnActivate = false
        }

        // Perform initial calculation right away so buttons become fixed immediately after click
        wpcUpdateStickyButtons();
    }



    // Updates fixed positioning of sticky buttons based on scroll position
    function wpcUpdateStickyButtons() {
        const $allButtons = $('.wpc-sticky-buttons');

        // If not activated yet, keep buttons unfixed
        if (!wpcStickyButtonsActivated) {
            return;
        }

        $allButtons.each(function () {
            const $buttons = $(this);
            const el = $buttons[0];
            if (!el) return;
            const stickyButtonsWidth = el.getBoundingClientRect().width;

            const rect = el.getBoundingClientRect();
            const styles = window.getComputedStyle(el);
            const marginTop = parseFloat(styles.marginTop) || 0;
            const marginBottom = parseFloat(styles.marginBottom) || 0;
            const stickyButtonsHeight = rect.height;

            if (stickyButtonsWidth === 0 || stickyButtonsHeight === 0) {
                return;
            }



            const stickyButtonsHeightWithMargin = rect.height + marginTop + marginBottom;
            const $wrapper = $buttons.closest('.wpc-filters-scroll-container');

            if (!wpcStickyForceOnActivate && !wpcHasAnyFilterSelected()) {
                if ($buttons.hasClass('wpc-is-fixed-apply-button')) {
                    $buttons.removeClass('wpc-is-fixed-apply-button').attr('style', '');
                }
                const prevPos = $wrapper.data('wpc-prev-position');
                if (typeof prevPos !== 'undefined') {
                    $wrapper.css('position', prevPos);
                    $wrapper.removeData('wpc-prev-position');
                }

                const $ph = $buttons.prev('.wpc-sticky-placeholder');
                if ($ph.length) $ph.remove();
                return;
            }


            if (!$wrapper.length) {
                return;
            }

            const winTop = $(window).scrollTop();
            const winH = $(window).height();
            const winBottom = winTop + winH;

            const wrOffset = $wrapper.offset();
            if (!wrOffset) {
                return;
            }

            const wrTop = wrOffset.top;
            const wrHeight = $wrapper.outerHeight();
            const wrBottom = wrTop + wrHeight;


            const ensurePlaceholder = (h) => {
                let $ph = $buttons.prev('.wpc-sticky-placeholder');
                if (!$ph.length) {
                    $ph = $('<div class="wpc-sticky-placeholder" aria-hidden="true"></div>');
                    $buttons.before($ph);
                }
                $ph.css('height', stickyButtonsHeightWithMargin + 'px');
                return $ph;
            };



            const btnH = $buttons.outerHeight();

            const wrLeft = wrOffset.left;

            const buttonsStyles =  window.getComputedStyle(el);
            const buttonsTop = ($('#wpadminbar').length > 0 ? $('#wpadminbar').outerHeight() : 0) + (parseFloat(buttonsStyles.getPropertyValue('--sticky-top')) || parseFloat(el.dataset.stickyTop) || 16);
            parseFloat(el.dataset.stickyTop) || 16;
            const buttonsBottom = parseFloat(buttonsStyles.getPropertyValue('--sticky-bottom')) ||
                parseFloat(el.dataset.stickyBottom) || 16;

            let currentPosition = $buttons.css('position');
            let currentTop = currentPosition === 'fixed' ? parseFloat($buttons.css('top')) : null;
            let currentBottom = currentPosition === 'fixed' ? parseFloat($buttons.css('bottom')) : null;

            let css = {
                position: 'fixed',
                left: wrLeft + 'px',
                bottom: 0,
                top: 'auto',
                width: stickyButtonsWidth + 'px',
                zIndex: 9999,
            }

            if (currentPosition === 'fixed') {
                if (currentTop !== null) {
                    css.top = currentTop + 'px';
                }
                if (currentBottom !== null) {
                    css.bottom = currentBottom + 'px';
                }
            }

            const $ph = ensurePlaceholder(btnH);

            if ($ph.length) {
                let buttonsPlaceholderOffset = $('.wpc-sticky-placeholder').offset();
                buttonsPlaceholderOffset.bottom = buttonsPlaceholderOffset.top + $('.wpc-sticky-placeholder').outerHeight();

                if (winTop > buttonsPlaceholderOffset.top) {
                    $buttons.addClass('wpc-is-fixed-apply-button');
                    css.top = buttonsTop + 'px';
                    css.left = wrLeft + 'px';
                    css.bottom = 'auto';
                    $buttons.css(css);
                } else if (winBottom < buttonsPlaceholderOffset.bottom) {
                    $buttons.addClass('wpc-is-fixed-apply-button');
                    css.top = 'auto';
                    css.bottom = buttonsBottom + 'px';
                    $buttons.css(css);
                }else{
                    $('.wpc-sticky-placeholder').remove();
                    css.position = 'relative';
                    css.top = 0;
                    css.left = 0;
                    $buttons.css(css);
                    $buttons.removeClass('wpc-is-fixed-apply-button');
                }
            }
        });
    }


    function wpcDebounce(fn, wait) {
        let t;
        return function() {
            clearTimeout(t);
            const args = arguments;
            const ctx = this;
            t = setTimeout(function() { fn.apply(ctx, args); }, wait);
        }
    }

    $(function() {
        if (wpcHasAnyFilterSelected() && window.innerWidth > wpcMobileWidth ) {
            wpcEnableStickyButtons(true);
        }
    });



    $.fn.tipTip = function(options) {
        var defaults = {
            activation: "hover",
            keepAlive: false,
            maxWidth: "200px",
            edgeOffset: 3,
            defaultPosition: "bottom",
            delay: 400,
            fadeIn: 200,
            fadeOut: 200,
            attribute: "title",
            content: false, // HTML or String to fill TipTIp with
            enter: function(){},
            exit: function(){}
        };
        var opts = $.extend(defaults, options);

        // Setup tip tip elements and render them to the DOM
        if($("#tiptip_holder").length <= 0){
            var tiptip_holder = $('<div id="tiptip_holder" style="max-width:'+ opts.maxWidth +';"></div>');
            var tiptip_content = $('<div id="tiptip_content"></div>');
            var tiptip_arrow = $('<div id="tiptip_arrow"></div>');
            $("body").append(tiptip_holder.html(tiptip_content).prepend(tiptip_arrow.html('<div id="tiptip_arrow_inner"></div>')));
        } else {
            var tiptip_holder = $("#tiptip_holder");
            var tiptip_content = $("#tiptip_content");
            var tiptip_arrow = $("#tiptip_arrow");
        }

        return this.each(function(){
            var org_elem = $(this);
            if(opts.content){
                var org_title = opts.content;
            } else {
                var org_title = org_elem.attr(opts.attribute);
            }
            if(org_title != ""){
                if(!opts.content){
                    org_elem.removeAttr(opts.attribute); //remove original Attribute
                }
                var timeout = false;

                if(opts.activation == "hover"){
                    org_elem.hover(function(){
                        active_tiptip();
                    }, function(){
                        if(!opts.keepAlive || !tiptip_holder.is(':hover')){
                            deactive_tiptip();
                        }
                    });
                    if(opts.keepAlive){
                        tiptip_holder.hover(function(){}, function(){
                            deactive_tiptip();
                        });
                    }
                } else if(opts.activation == "focus"){
                    org_elem.focus(function(){
                        active_tiptip();
                    }).blur(function(){
                        deactive_tiptip();
                    });
                } else if(opts.activation == "click"){
                    org_elem.click(function(){
                        active_tiptip();
                        return false;
                    }).hover(function(){},function(){
                        if(!opts.keepAlive){
                            deactive_tiptip();
                        }
                    });
                    if(opts.keepAlive){
                        tiptip_holder.hover(function(){}, function(){
                            deactive_tiptip();
                        });
                    }
                }

                function active_tiptip(){
                    opts.enter.call(this);
                    tiptip_content.html(org_title);
                    tiptip_holder.hide().removeAttr("class").css("margin","0");
                    tiptip_arrow.removeAttr("style");

                    var top = parseInt(org_elem.offset()['top']);
                    var left = parseInt(org_elem.offset()['left']);
                    var org_width = parseInt(org_elem.outerWidth());
                    var org_height = parseInt(org_elem.outerHeight());
                    var tip_w = tiptip_holder.outerWidth();
                    var tip_h = tiptip_holder.outerHeight();
                    var w_compare = Math.round((org_width - tip_w) / 2);
                    var h_compare = Math.round((org_height - tip_h) / 2);
                    var marg_left = Math.round(left + w_compare);
                    var marg_top = Math.round(top + org_height + opts.edgeOffset);
                    var t_class = "";
                    var arrow_top = "";
                    var arrow_left = Math.round(tip_w - 12) / 2;

                    if(opts.defaultPosition == "bottom"){
                        t_class = "_bottom";
                    } else if(opts.defaultPosition == "top"){
                        t_class = "_top";
                    } else if(opts.defaultPosition == "left"){
                        t_class = "_left";
                    } else if(opts.defaultPosition == "right"){
                        t_class = "_right";
                    }

                    var right_compare = (w_compare + left) < parseInt($(window).scrollLeft());
                    var left_compare = (tip_w + left) > parseInt($(window).width());

                    if((right_compare && w_compare < 0) || (t_class == "_right" && !left_compare) || (t_class == "_left" && left < (tip_w + opts.edgeOffset + 5))){
                        t_class = "_right";
                        arrow_top = Math.round(tip_h - 13) / 2;
                        arrow_left = -12;
                        marg_left = Math.round(left + org_width + opts.edgeOffset);
                        marg_top = Math.round(top + h_compare);
                    } else if((left_compare && w_compare < 0) || (t_class == "_left" && !right_compare)){
                        t_class = "_left";
                        arrow_top = Math.round(tip_h - 13) / 2;
                        arrow_left =  Math.round(tip_w);
                        marg_left = Math.round(left - (tip_w + opts.edgeOffset + 5));
                        marg_top = Math.round(top + h_compare);
                    }

                    var top_compare = (top + org_height + opts.edgeOffset + tip_h + 8) > parseInt($(window).height() + $(window).scrollTop());
                    var bottom_compare = ((top + org_height) - (opts.edgeOffset + tip_h + 8)) < 0;

                    if(top_compare || (t_class == "_bottom" && top_compare) || (t_class == "_top" && !bottom_compare)){
                        if(t_class == "_top" || t_class == "_bottom"){
                            t_class = "_top";
                        } else {
                            t_class = t_class+"_top";
                        }
                        arrow_top = tip_h;
                        marg_top = Math.round(top - (tip_h + 5 + opts.edgeOffset));
                    } else if(bottom_compare | (t_class == "_top" && bottom_compare) || (t_class == "_bottom" && !top_compare)){
                        if(t_class == "_top" || t_class == "_bottom"){
                            t_class = "_bottom";
                        } else {
                            t_class = t_class+"_bottom";
                        }
                        arrow_top = -12;
                        marg_top = Math.round(top + org_height + opts.edgeOffset);
                    }

                    if(t_class == "_right_top" || t_class == "_left_top"){
                        marg_top = marg_top + 5;
                    } else if(t_class == "_right_bottom" || t_class == "_left_bottom"){
                        marg_top = marg_top - 5;
                    }
                    if(t_class == "_left_top" || t_class == "_left_bottom"){
                        marg_left = marg_left + 5;
                    }
                    tiptip_arrow.css({"margin-left": arrow_left+"px", "margin-top": arrow_top+"px"});
                    tiptip_holder.css({"margin-left": marg_left+"px", "margin-top": marg_top+"px"}).attr("class","tip"+t_class);

                    if (timeout){ clearTimeout(timeout); }
                    timeout = setTimeout(function(){ tiptip_holder.stop(true,true).fadeIn(opts.fadeIn); }, opts.delay);
                }

                function deactive_tiptip(){
                    opts.exit.call(this);
                    if (timeout){ clearTimeout(timeout); }
                    tiptip_holder.fadeOut(opts.fadeOut);
                }
            }
        });
    }

    /* =========================================================================
     * Apply-button recount engine.
     *
     * Client-side counters recount, chips and apply-URL building for Filter
     * Sets with use_apply_button = 'yes'. On such pages the server prints
     * window.wpcFilterJsonData BEFORE this script (Plugin::inlineScriptJsonData,
     * wp_add_inline_script 'before'). On every other page the guard below
     * replaces the whole engine with no-ops, so none of its code can run.
     * ========================================================================= */
    const wpcApplyEngine = (function () {

    if (typeof window.wpcFilterJsonData === 'undefined' && typeof window.wpcFilterJsonDataPromise === 'undefined') {
        const noop = function () {};
        return {
            applyJsMode: noop,
            compareInputWithRangeList: noop,
            unsetChip: noop,
            updateWpcFilterJsonData: noop
        };
    }

    // Static-file delivery resolves the data asynchronously; if a user
    // interaction beats the blob fetch, park it on the promise (all engine
    // entry points are fire-and-forget, so deferral is transparent and the
    // registration order of .then callbacks preserves the call order)
    function wpcWhenDataReady(fn) {
        if (typeof window.wpcFilterJsonData !== 'undefined') {
            fn();
            return;
        }
        if (window.wpcFilterJsonDataPromise) {
            wpcShowSpinner();
            window.wpcFilterJsonDataPromise.then(function () {
                wpcHideSpinner();
                if (typeof window.wpcFilterJsonData !== 'undefined') fn();
            });
        }
    }

    // Array.prototype.push(...arr) / Math.min(...arr) put every element on the
    // CALL STACK — at 100k-product scale the term post lists exceed V8's
    // argument limit and throw "Maximum call stack size exceeded"; loop instead.
    // Min/max coerce like Math.min does: meta_values may carry numeric strings.
    function wpcPushAll(target, items) {
        for (let i = 0; i < items.length; i++) target.push(items[i]);
        return target;
    }
    function wpcArrayMin(items) {
        let min = Infinity;
        for (let i = 0; i < items.length; i++) {
            const v = Number(items[i]);
            if (v < min) min = v;
        }
        return min;
    }
    function wpcArrayMax(items) {
        let max = -Infinity;
        for (let i = 0; i < items.length; i++) {
            const v = Number(items[i]);
            if (v > max) max = v;
        }
        return max;
    }

    function updateCounters($el, setId) {

        const filterSetData = wpcFilterJsonData[setId];
        filterSetData.chips = [];
        filterSetData.tempFilteredAllPostsIds = {};
        filterSetData.tempFilteredTerms = {};
        delete filterSetData.filteredPostsIds;

        // Needed by the collectors below: filtering parent-keyed dates against the
        // expanded universe alone loses every variable product from a date selection
        const { inSetUniverse } = wpcUniverse(setId);


        const $all = $('input:checked:not(.wpc-range-list-item), option:selected:not(.wpc-range-list-item), form.wpc-filter-range-form input:not(.wpc-range-list-item), form.wpc-filter-date-range-form-visible input, input[data-wpc-was-checked=true]', $el);

        //const allPostsSet = new Set(getAllPostsIdsInArray(setId));


        const getEntityItems = (data) => {
            let wpcSlug = wpcTermSlug(data.wpcSlug);

            if (typeof data.min         !== 'undefined') wpcSlug = 'min';
            if (typeof data.max         !== 'undefined') wpcSlug = 'max';
            if (typeof data.wpcTempFrom !== 'undefined') wpcSlug = 'from';
            if (typeof data.wpcTempTo   !== 'undefined') wpcSlug = 'to';

            const allEntityItems = filterSetData.allEntities[data.wpcEName].items;
            for (const item of Object.values(allEntityItems)) {
                if (item.slug == wpcSlug) return item.posts;
            }
            return [];
        };

        const getAllEntityItemsForRange = (data) => {
            let wpcSlug = wpcTermSlug(data.wpcSlug);
            if(data.min === undefined && data.max === undefined){
                return;
            }
            if (typeof data.min         !== 'undefined') wpcSlug = 'min';
            if (typeof data.max         !== 'undefined') wpcSlug = 'max';

            const allEntityItems = filterSetData.allEntities[data.wpcEName].items;
            for (const [key, item] of Object.entries(allEntityItems)) {
                if (item.slug === wpcSlug){
                    wpcFilterJsonData[setId].allEntities[data.wpcEName].items[key].posts = Object.keys(item.meta_values).map(Number);
                    return Object.keys(item.meta_values).map(Number);
                }
            }
            return [];
        };

        const filterRangeItems = ($currentEl, postsIds, setId) => {
            const data = $currentEl.data();
            const postsArray = Array.isArray(postsIds) ? postsIds : Object.values(postsIds);
            const filteredPostsIds = [];

            if (typeof data.min !== 'undefined' || typeof data.max !== 'undefined') {
                const $form   = $currentEl.parents('.wpc-filter-range-form');
                const $minEl  = $('.wpc-filters-range-min', $form);
                const $maxEl  = $('.wpc-filters-range-max', $form);
                const minCurVal = +$minEl.val();
                const maxCurVal = +$maxEl.val();

                // An untouched side means NO bound: the server renders the input
                // VALUE and data-min/max as placeholders scoped to the CURRENT
                // result set, so using them as limits kept phantom constraints
                // after another selection changed (numeric twin of the untouched-
                // date fix). Untouched = value equals the Abs bound OR the current
                // placeholder — exactly the checks buildUrlForApplyButton uses
                // before omitting the query param.
                const minUntouched = ($minEl.data().absMin === minCurVal) || (Number($minEl.data().min) === minCurVal);
                const maxUntouched = ($maxEl.data().absMax === maxCurVal) || (Number($maxEl.data().max) === maxCurVal);

                if (minUntouched && maxUntouched) {
                    return postsIds;
                }

                const minBound = minUntouched ? -Infinity : minCurVal;
                const maxBound = maxUntouched ? Infinity : maxCurVal;

                let filterPostsIds = [];
                for (const postId of postsArray) {
                    if(inSetUniverse(postId)){
                        filterPostsIds.push(+postId);
                    }
                }
                const entityNumberValues = filterSetData.allEntities[data.wpcEName].items.min.meta_values;
                for (const postId of filterPostsIds) {
                    const postValues = entityNumberValues[postId];
                    if (postValues !== undefined) {
                        for (let postVal of postValues) {
                            if (postVal >= minBound && postVal <= maxBound) {
                                filteredPostsIds.push(postId);
                            }
                        }
                    }
                }
                return filteredPostsIds;
            }


            if (typeof data.wpcTempFrom !== 'undefined' || typeof data.wpcTempTo !== 'undefined') {
                const $form   = $currentEl.parents('.wpc-filters-range-inputs form');
                const $fromEl = $('.wpc-filters-range-from.hasDatepicker', $form);
                const $toEl   = $('.wpc-filters-range-to.hasDatepicker', $form);
                const from    = $fromEl.data('wpcTempFrom');
                const to      = $toEl.data('wpcTempTo');

                if ($fromEl.data('wpcAbsFrom') === from && $toEl.data('wpcAbsTo') === to) {
                    return postsIds;
                }

                const entityDateValues = filterSetData.allEntities[data.wpcEName].items.from.meta_values;
                const dateType = $fromEl.data('wpcDateType');
                let dateFrom = from ? $fromEl[wpcPickerFn()]('getDate') : null;
                let dateTo   = to   ? $toEl[wpcPickerFn()]('getDate')   : null;

                // An untouched field means NO bound, not "clamp to the displayed
                // Abs value": on an applied page the server renders Abs bounds
                // scoped to the CURRENT result set (they are placeholders), so
                // clamping cut off products outside the previous selection when
                // another filter changed. Mirrors buildUrlForApplyButton, which
                // omits the date param for untouched fields.
                if($fromEl.val() === $fromEl.data('wpcAbsFrom')){
                    dateFrom = null;
                }

                if($toEl.val() === $toEl.data('wpcAbsTo')){
                    dateTo = null;
                }

                // meta_values dates are minute-precision (seconds zeroed) — keep
                // picked boundaries in the same precision
                if (dateFrom) dateFrom.setSeconds(0, 0);
                if (dateTo) dateTo.setSeconds(0, 0);


                let todayStr = null;
                if (dateType === 'time') {
                    const t = new Date();
                    todayStr = `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(t.getDate()).padStart(2, '0')}`;
                }

                //const variationMap = wpcFilterJsonData.product_variations_map;


                // Candidates come from the meta_values KEYS, not from item.posts:
                // the server intersects item.posts with the variation-expanded
                // universe (variable parents replaced by variation ids there),
                // while post dates are keyed by PARENT ids — using item.posts
                // dropped every variable product from a date selection
                let filterPostsIds = [];
                for (const postId of Object.keys(entityDateValues).map(Number)) {
                    if(inSetUniverse(postId)){
                        filterPostsIds.push(postId);
                    }
                }

                for (const postId of filterPostsIds) {
                    const dateValStr = entityDateValues[postId];
                    if (!dateValStr) continue;

                    let dateVal;
                    if (dateType === 'time') {
                        const timePart = dateValStr.substring(dateValStr.indexOf(' ') + 1);
                        if (!timePart) continue;
                        dateVal = new Date(`${todayStr} ${timePart}`);
                    } else {
                        dateVal = new Date(dateValStr);
                    }

                    const isAfterFrom = !dateFrom || dateVal >= dateFrom;
                    const isBeforeTo  = !dateTo   || dateVal <= dateTo;
                    if (isAfterFrom && isBeforeTo){
                        filteredPostsIds.push(postId);
                    }
                }
            }

            return filteredPostsIds;
        };


        const filterLogic = (entityName, postIds, logic) => {
            let posts;

            if (logic === 'and' && postIds.length > 1) {

                let set = new Set(postIds[0]);
                for (let i = 1; i < postIds.length; i++) {
                    set = new Set(postIds[i].filter(id => set.has(id)));
                }
                posts = [...set];
            } else {
                posts = [...new Set(postIds.flat())];
            }

            return posts;
        };



        const countTerms = ($elements, setId) => {
            const tempFilteredAllPostsIds = {};
            const tempFilteredTerms = {};
            const setData = wpcFilterJsonData[setId];


            $elements.each((index, el) => {
                const $currentEl = $(el);
                const data = $currentEl.data();
                if (typeof data.wpcEName === 'undefined' || typeof data.wpcSlug === 'undefined') return;

                let currentPostsIds = getEntityItems(data);
                if (!tempFilteredTerms[data.wpcEName]) {
                    tempFilteredTerms[data.wpcEName] = [];
                }
                tempFilteredTerms[data.wpcEName].push(wpcTermSlug(data.wpcSlug));

                const isRange = typeof data.min         !== 'undefined' || typeof data.max !== 'undefined';
                const isDate  = typeof data.wpcTempFrom !== 'undefined' || typeof data.wpcTempTo !== 'undefined';
                const { used_for_variations: usedForVariations } = setData.allEntities[data.wpcEName].filter;
                const isUsedForVariations = usedForVariations === 'yes' || usedForVariations === true;

                if (isRange || isDate) {
                    if (isRange) {
                        currentPostsIds = getAllEntityItemsForRange(data);
                        const $form   = $currentEl.parents('.wpc-filter-range-form');
                        const $minEl  = $('.wpc-filters-range-min', $form);
                        const $maxEl  = $('.wpc-filters-range-max', $form);
                        const min     = $minEl.data().min;
                        const max     = $maxEl.data().max;

                        if (min !== +$minEl.val() || max !== +$maxEl.val()) {
                            currentPostsIds = filterRangeItems($currentEl, currentPostsIds, setId);
                            if (currentPostsIds.length) {
                                if (!tempFilteredAllPostsIds[data.wpcEName]) tempFilteredAllPostsIds[data.wpcEName] = [];
                                tempFilteredAllPostsIds[data.wpcEName][index] = currentPostsIds;
                            }
                            collectChips($currentEl, setId);
                        }
                    }


                    if (isDate) {
                        const $form   = $currentEl.parents('.wpc-filters-range-inputs');
                        const $fromEl = $('.wpc-filters-range-from', $form);
                        const $toEl   = $('.wpc-filters-range-to', $form);

                        if ($fromEl.data('wpcTempFrom') !== $fromEl.data('wpcAbsFrom') ||
                            $toEl.data('wpcTempTo')     !== $toEl.data('wpcAbsTo')) {

                            currentPostsIds = filterRangeItems($currentEl, currentPostsIds, setId);
                            if (currentPostsIds.length) {
                                if (!tempFilteredAllPostsIds[data.wpcEName]) tempFilteredAllPostsIds[data.wpcEName] = [];
                                tempFilteredAllPostsIds[data.wpcEName][index] = currentPostsIds;
                            }
                            collectChips($currentEl, setId);
                        }
                    }

                } else {
                    if (!tempFilteredAllPostsIds[data.wpcEName]) tempFilteredAllPostsIds[data.wpcEName] = [];
                    tempFilteredAllPostsIds[data.wpcEName][index] = currentPostsIds;
                    collectChips($currentEl, setId);
                }
            });
            filterSetData.tempFilteredTerms = tempFilteredTerms;
            for (const [entityName, postIds] of Object.entries(tempFilteredAllPostsIds)) {
                filterSetData.tempFilteredAllPostsIds[entityName] = filterLogic(entityName, postIds, setId);
            }
        };



        function getAllPostsIdsInArray(setId) {
            const ids = wpcFilterJsonData[setId].allPostsIds;
            if (Array.isArray(ids)) return wpcFilterJsonData.allPostsIds.map(Number);
            return Object.keys(ids).map(Number);
        }
        const changeFiltersWithParents = ($el, setId) => {
            const $filtersWithParent = $('.wpc-has-parent-filter', $el);
            const hideEmpty = (filterSetData.settings.hide_empty !== undefined) ? wpcFilterJsonData[setId].settings.hide_empty : false;
            const isHideEmpty = hideEmpty === 'yes';
            let $is_parent_unchecked = false;
            $filtersWithParent.each((index, filter) => {
                const $filter = $(filter);
                const parentFilterId = $filter.data('parentFilterId');
                if(parentFilterId !== undefined){
                    const hideUntilParent = ($filter.data('hideUntilParent') === 1);
                    const filterEName = $filter.data('filterEName');
                    const $parentFilter = $('.wpc-filters-section-' + parentFilterId, $el);
                    const parentFilterEName = $parentFilter.data('filterEName');
                    const $select  = $filter.find('select');
                    const $wpcDropdownDefault = $select.find('option.wpc-dropdown-default');
                    const wpcDropdownDefaultData = $wpcDropdownDefault.data();
                    const isSelect2 = $select.hasClass('select2-hidden-accessible');
                    const moreLess = filterSetData.allEntities[filterEName].filter.more_less;
                    const entity = filterSetData.allEntities[filterEName].filter.entity;
                    const isMoreLess = moreLess === 'yes';
                    const hideEmptyFilter = (filterSetData.settings.hide_empty_filter !== undefined) ? wpcFilterJsonData[setId].settings.hide_empty_filter : false;
                    const isHideEmptyFilter = hideEmptyFilter === 'yes';
                    if(
                        filterSetData.tempFilteredAllPostsIds[parentFilterEName] !== undefined
                        &&
                        filterSetData.tempFilteredAllPostsIds[parentFilterEName].length > 0
                    ){
                        const allParentFilterPosts = filterSetData.tempFilteredAllPostsIds[parentFilterEName];
                        if ($select.length) {
                            $filter.find('option.wpc-term-item').each((index, elem) => {
                                const data = $(elem).data();
                                const entityItems = getEntityItems(data);
                                const hasMatch = entityItems && entityItems.length > 0 &&
                                    entityItems.some(id => allParentFilterPosts.includes(Number(id)));
                                if(hasMatch){
                                    $(elem).addClass('wpc-show-with-parent-true')
                                    if ($(elem).hasClass('wpc-has-terms')) {
                                        if(!$(elem).is(':selected')){
                                            $(elem).removeClass('wpc-show-with-parent-false');
                                        }

                                    } else {
                                        if(isHideEmpty){
                                            $(elem).addClass('wpc-show-with-parent-false');
                                        }else{
                                            $(elem).removeClass('wpc-show-with-parent-false');
                                        }
                                    }
                                }else{
                                    if(!$(elem).is(':selected')) {
                                        $(elem).addClass('wpc-show-with-parent-false').removeClass('wpc-show-with-parent-true');
                                    }
                                }
                            });
                            $('.wpc-dropdown-default-' + filterEName, $el).text(wpcDropdownDefaultData.wpcDefaultOptionText)
                        } else {
                            $filter.find('li.wpc-term-item').each((index, elem) => {
                                const data = $(elem).find('input').data();
                                const entityItems = getEntityItems(data);
                                const hasMatch = entityItems && entityItems.length > 0 &&
                                    entityItems.some(id => allParentFilterPosts.includes(Number(id)));
                                if(hasMatch){
                                    $(elem).addClass('wpc-show-with-parent-true')
                                    if ($(elem).hasClass('wpc-has-terms')) {
                                        $(elem).removeClass('wpc-show-with-parent-false');
                                    } else {
                                        if(isHideEmpty){
                                            if(!$(elem).find('input').is(':checked')){
                                                $(elem).addClass('wpc-show-with-parent-false');
                                            }
                                        }else{
                                            $(elem).removeClass('wpc-show-with-parent-false');
                                        }
                                    }
                                }else{
                                    if(!$(elem).find('input').is(':checked')) {
                                        $(elem).addClass('wpc-show-with-parent-false').removeClass('wpc-show-with-parent-true');
                                    }
                                }
                            });



                            let $wpcTermsItems = $filter.find(`.wpc-filters-ul-list li.wpc-show-with-parent-true`);
                            $wpcTermsItems.removeClass('wpc-not-hidden-term');
                            const $wpcHasTermsItems = $filter.find(`.wpc-filters-ul-list li.wpc-has-terms.wpc-show-with-parent-true`)
                            const $wpcHasCheckedTermsItems = $filter.find(`.wpc-filters-ul-list input:checked, .wpc-filters-ul-list option:selected`)
                            let wpcHasTerms = $wpcHasTermsItems.length || $wpcHasCheckedTermsItems.length
                            const isHideFilterElement = (entity === 'post_meta_num' || entity === 'post_date' || entity === 'tax_numeric' || entity === 'post_meta_num');

                            if (isHideFilterElement && wpcFilterJsonData[setId].entityPostsCount[data.wpcEName] !== undefined) {
                                wpcHasTerms = wpcFilterJsonData[setId].entityPostsCount[data.wpcEName] === true ? 1 : 0;
                            }
                            if(wpcHasTerms <= 0 && isHideEmptyFilter){
                                $filter.addClass('wpc-filters-section-0');
                            }else{
                                $filter.removeClass('wpc-filters-section-0');
                            }

                            if(isMoreLess){
                                if(wpcHasTerms <= +wpcMoreLessCount){
                                    $filter.addClass('wpc-filter-few-terms');
                                }else{
                                    $filter.removeClass('wpc-filter-few-terms');
                                }
                                if(isHideEmpty){
                                    $wpcTermsItems.filter('li.wpc-has-terms.wpc-show-with-parent-true').slice(0, +wpcMoreLessCount).each(function() {
                                        $(this).addClass('wpc-not-hidden-term');
                                    });
                                }else{
                                    $wpcTermsItems.filter('li.wpc-show-with-parent-true').slice(0, +wpcMoreLessCount).each(function() {
                                        $(this).addClass('wpc-not-hidden-term');
                                    });
                                }
                            }

                        }

                        if($filter.find('.wpc-has-terms').length > 0 || $filter.hasClass('wpc-range-filter')){
                            $filter.find('.wpc-ask-to-parent-display').addClass('wpc-ask-to-parent-display-none');
                            $filter.removeClass('wpc-parent-filter-terms-unselected').addClass('wpc-parent-filter-terms-selected')
                            if(hideUntilParent){
                                $filter.removeClass('wpc-hide-terms-until-parent-unselected')
                            }
                        }else{
                            if(isHideEmpty){
                                $filter.find('.wpc-ask-to-parent-display').removeClass('wpc-ask-to-parent-display-none');
                                $filter.removeClass('wpc-parent-filter-terms-selected').addClass('wpc-parent-filter-terms-unselected')
                                if(hideUntilParent){
                                    $filter.addClass('wpc-hide-terms-until-parent-unselected')
                                }
                            }else{
                                $filter.find('wpc-ask-to-parent-display-none').removeClass('.wpc-ask-to-parent-display');
                                $filter.removeClass('wpc-parent-filter-terms-unselected').addClass('wpc-parent-filter-terms-selected')
                                if(hideUntilParent){
                                    $filter.removeClass('wpc-hide-terms-until-parent-unselected')
                                }
                            }
                        }
                        if($filter.hasClass('wpc-range-from-elem')){
                            $filter.find('.wpc-ask-to-parent-display').addClass('wpc-ask-to-parent-display-none');
                            $filter.removeClass('wpc-show-range-with-parent-false')
                        }
                        if($filter.hasClass('wpc-stars-rating-block')){
                            $filter.find('.wpc-ask-to-parent-display').addClass('wpc-ask-to-parent-display-none');
                            $filter.removeClass('wpc-parent-filter-terms-unselected');
                        }
                    }else{
                        if(filterSetData.tempFilteredAllPostsIds[filterEName] !== undefined
                            &&
                            filterSetData.tempFilteredAllPostsIds[filterEName].length > 0){
                            $filter.addClass('wpc-child-selected-no-parent')
                            $filter.find('input:checked').prop('checked', false);
                            $filter.find('option:selected').prop('selected', false);
                            delete filterSetData.tempFilteredAllPostsIds[filterEName];
                            $is_parent_unchecked = true;
                        }else{
                            $filter.removeClass('wpc-child-selected-no-parent')
                        }

                        if(hideUntilParent){
                            $filter.addClass('wpc-hide-terms-until-parent-unselected')
                        }

                        $filter.find('.wpc-ask-to-parent-display').removeClass('wpc-ask-to-parent-display-none');
                        $filter.removeClass('wpc-parent-filter-terms-selected').addClass('wpc-parent-filter-terms-unselected')
                        if($select.length) {
                            $filter.find('option.wpc-term-item').each((index, elem) => {
                                if(!$(elem).is(':selected')){
                                    $(elem).addClass('wpc-show-with-parent-false');
                                }
                            });
                            $('.wpc-dropdown-default-' + filterEName, $el).text(wpcDropdownDefaultData.wpcSelectParentText)
                        }else{
                            $filter.find('li.wpc-term-item').each((index, elem) => {
                                if(!$(elem).is(':checked')){
                                    $(elem).addClass('wpc-show-with-parent-false');
                                }
                            });
                        }
                        if($filter.hasClass('wpc-range-from-elem')){
                            $filter.addClass('wpc-show-range-with-parent-false')
                            $filter.find('.wpc-ask-to-parent-display').removeClass('wpc-ask-to-parent-display-none');
                        }
                        if($filter.hasClass('wpc-stars-rating-block')){
                            $filter.find('.wpc-ask-to-parent-display').removeClass('wpc-ask-to-parent-display-none');
                            $filter.addClass('wpc-parent-filter-terms-unselected');
                        }
                    }
                }

            });
            if($is_parent_unchecked){
                applyJsMode($el, setId);
            }
        }

        countTerms($all, setId);
        updateChipsList(setId);
        changeCounters($el, setId);
        changeFiltersWithParents($el, setId)
    }

    function changeCounters($el, setId){

        const $otherFilterSetsOnPage = $(wpcWidgetContainer);
        const hasMultipleWidgets = $otherFilterSetsOnPage.length > 1;

        if (hasMultipleWidgets) {
            let tempPostIds = [];
            let relatedSetsIds = [];
            $otherFilterSetsOnPage.each((index, filterSetWidget) => {
                const widgetSetId = $(filterSetWidget).data('set');

                const relatedSets = wpcFilterJsonData[widgetSetId].relatedSets;

                if(relatedSetsIds[relatedSets] === undefined){
                    relatedSetsIds[relatedSets] = [];
                }
                if(relatedSetsIds[relatedSets][widgetSetId] === undefined){
                    relatedSetsIds[relatedSets][widgetSetId] = [];
                }
                relatedSetsIds[relatedSets][widgetSetId] = wpcFilterJsonData[widgetSetId].filteredPostsIds


            });

            if (Object.keys(relatedSetsIds).length > 0) {
                let setParams = [];
                Object.entries(relatedSetsIds).forEach(([relatedSets, widgetSetIds]) => {
                    const intersectionPosts = Object.values(widgetSetIds).reduce((acc, arr) => {
                        const set = new Set(arr);
                        return acc.filter(id => set.has(id));
                    }, []);
                    Object.entries(widgetSetIds).forEach(([relatedSetId, widgetPostIds]) => {
                        wpcFilterJsonData[relatedSetId].filteredPostsIds = intersectionPosts;
                        Object.entries(widgetSetIds).forEach(([relatedSetIdNext, widgetPostIdsNext]) => {
                            if(relatedSetIdNext !== relatedSetId){
                                const filteredPostsIds = wpcFilterJsonData[relatedSetId].tempFilteredAllPostsIds;
                                if (filteredPostsIds && typeof filteredPostsIds === 'object') {
                                    Object.entries(filteredPostsIds).forEach(([eName, entityPostIds]) => {
                                        if(wpcFilterJsonData[relatedSetIdNext].tempFilteredAllPostsIds === undefined){
                                            wpcFilterJsonData[relatedSetIdNext].tempFilteredAllPostsIds = [];
                                        }
                                        if(typeof wpcFilterJsonData[relatedSetIdNext].tempFilteredAllPostsIds[eName] === 'undefined'){
                                            wpcFilterJsonData[relatedSetIdNext].tempFilteredAllPostsIds[eName] = entityPostIds;
                                        }
                                    });
                                }
                            }
                        });
                    });
                });
            }
        }

        const setData = wpcFilterJsonData[setId];
        let allEntities = JSON.parse(JSON.stringify(wpcFilterJsonData[setId].allEntities));
        const tempFiltered = setData.tempFilteredAllPostsIds;
        const hasTempFilters = Object.keys(tempFiltered).length > 0;
        let entityPostsCount = [];
        let allPostsIds = getVariation(Object.keys(setData.allPostsIds), false);
        const { allPostsUniverseSet, allPostsExpandedSet, inSetUniverse } = wpcUniverse(setId);

        // Final displayed count for a candidate id list. On variations-as-products
        // shops item.posts carry ALL variations of the matched parents (the server
        // clamps its final counts against the set universe in calcTermCount) —
        // mirror that clamp here, in variation space. On regular shops the
        // parent-space collapse compensates already (parents are universe-bound),
        // so the behavior there is unchanged.
        const wpcDisplayCount = (ids) => (wpcFilterJsonData.variationsAsProducts
            ? wpcCountSpace(ids.filter(inSetUniverse))
            : wpcCountSpace(ids)).length;

        if (hasTempFilters) {
            for (const [tempEntityName, postIds] of Object.entries(tempFiltered)) {
                // Cross-entity intersections run in PARENT (product-level) space,
                // mirroring the server SQL where every filter is an independent
                // product-level clause: a product with a blue variation at 65 and a
                // non-blue variation at 72 DOES match color-blue + price 70..100,
                // even though no single variation satisfies both conditions
                const parentIdSet = new Set(postIds.map(wpcParentOf));

                for (const [entityName, entity] of Object.entries(allEntities)) {
                    const { logic, range_list_input: rangeList, used_for_variations: usedForVariations } = entity.filter;
                    const isAndLogic = logic === 'and';
                    const isUsedForVariations = usedForVariations === 'yes' || usedForVariations === true;
                    const hasOnlyOneElement = Object.keys(tempFiltered).length === 1 && tempFiltered[entityName] !== undefined;

                    for (const [index, item] of Object.entries(entity.items)) {
                        let tempPostIdsArray;
                        if (tempEntityName === entityName) {
                            tempPostIdsArray = isAndLogic
                                ? item.posts.filter(x => parentIdSet.has(wpcParentOf(x)) && inSetUniverse(x))
                                : item.posts.filter(x => inSetUniverse(x));
                        } else {
                            tempPostIdsArray = item.posts.filter(x => parentIdSet.has(wpcParentOf(x)));
                        }

                        allEntities[entityName].items[index].posts = tempPostIdsArray;
                        allEntities[entityName].items[index].count = tempPostIdsArray.length;

                        // On variations-as-products shops the DISPLAY count must be
                        // taken from the pre-normalization list: getVariation() below
                        // collapses non-ufv lists to parent ids for the cross-entity
                        // intersections, but the visible catalog unit is a variation
                        // (e.g. Variable: 467 raw -> universe-clamped 141, while the
                        // collapsed list would count 40 parents).
                        const wpcDisplayIds = tempPostIdsArray;

                        tempPostIdsArray = getVariation(tempPostIdsArray, isUsedForVariations);

                        // Displayed counters are distinct products (parent space), as
                        // the server counts them — non-ufv lists also carry variation
                        // ids alongside parents, so a plain .length over-counts. On
                        // variations-as-products shops counts stay in variation space,
                        // clamped to the set universe.
                        allEntities[entityName].items[index].cross_count = wpcDisplayCount(
                            wpcFilterJsonData.variationsAsProducts ? wpcDisplayIds : tempPostIdsArray
                        );
                        if(entityPostsCount[entityName] === undefined){
                            entityPostsCount[entityName] = [];
                        }
                        wpcPushAll(entityPostsCount[entityName], tempPostIdsArray);

                        if (typeof rangeList === 'object') {
                            if (hasOnlyOneElement) {
                                tempPostIdsArray = setData.allEntities[entityName].items[index].posts;
                                tempPostIdsArray = getVariation(tempPostIdsArray, isUsedForVariations);
                            }else{
                                tempPostIdsArray = setData.allEntities[entityName].items[index].posts;
                                const rangeTempFiltered = {...tempFiltered};

                                delete rangeTempFiltered[entityName];
                                for (const [rangeTempEntityName, rangePostIds] of Object.entries(rangeTempFiltered)) {
                                    const rangeParentSet = new Set(rangePostIds.map(wpcParentOf));
                                    tempPostIdsArray = tempPostIdsArray.filter(x => rangeParentSet.has(wpcParentOf(x)));
                                }

                                tempPostIdsArray = getVariation(tempPostIdsArray, isUsedForVariations);
                            }

                            for (const [indexRange, range] of Object.entries(rangeList)) {
                                const rangeListMinVal = Number(range.range_list_min_val);
                                const rangeListMaxVal = Number(range.range_list_max_val);
                                let rangePostIds = [];

                                for (const postId of tempPostIdsArray) {
                                    const postValues = allEntities[entityName].items[index].meta_values[postId];
                                    if (postValues !== undefined) {
                                        for (let postVal of postValues) {
                                            postVal = Number(postVal);
                                            // inSetUniverse, not the parent-only set: with used_for_variations
                                            // the candidate ids (and meta_values keys) are VARIATION ids,
                                            // which live only in the expanded universe
                                            if (inSetUniverse(postId) &&
                                                ((rangeListMaxVal === 0 && rangeListMinVal <= postVal) ||
                                                (rangeListMinVal <= postVal && rangeListMaxVal >= postVal))) {
                                                rangePostIds.push(postId);
                                            }
                                        }
                                    }
                                }

                                rangePostIds = getVariation(rangePostIds, isUsedForVariations);

                                allEntities[entityName].items[index].range_list_input[indexRange] = wpcDisplayCount(rangePostIds);
                            }
                        }
                    }
                }
            }
        }

        setData.filteredPostsIds = [];

        if (hasTempFilters) {
            for (const postIds of Object.values(tempFiltered)) {
                wpcPushAll(setData.filteredPostsIds, postIds);
            }
        } else {
            for (const [indexEntity, entity] of Object.entries(allEntities)) {
                const { logic, range_list_input: rangeList, used_for_variations: usedForVariations } = entity.filter;
                const isUsedForVariations = usedForVariations === 'yes' || usedForVariations === true;
                for (const [indexItem, item] of Object.entries(entity.items)) {
                    let postIds = item.posts;
                    wpcPushAll(setData.filteredPostsIds, item.posts);
                    postIds = getVariation(postIds, isUsedForVariations);
                    // Same pre-normalization display source as the hasTempFilters
                    // branch: non-ufv lists get parent-collapsed by getVariation()
                    // right above, which under-counts on variations-as-products shops.
                    allEntities[indexEntity].items[indexItem].cross_count = wpcDisplayCount(
                        wpcFilterJsonData.variationsAsProducts ? item.posts : postIds
                    );


                    if(entityPostsCount[indexEntity] === undefined){
                        entityPostsCount[indexEntity] = [];
                    }
                    wpcPushAll(entityPostsCount[indexEntity], postIds);

                    if (typeof rangeList === 'object') {

                        postIds = getVariation(postIds, isUsedForVariations);

                        for (const [indexRange, range] of Object.entries(rangeList)) {
                            const rangeListMinVal = Number(range.range_list_min_val);
                            const rangeListMaxVal = Number(range.range_list_max_val);
                            let rangePostIds = [];

                            for (const postId of postIds) {
                                const postValues = allEntities[indexEntity].items[indexItem].meta_values[postId];
                                if (postValues !== undefined) {
                                    for (let postVal of postValues) {
                                        postVal = Number(postVal);
                                        // inSetUniverse — variation ids of ufv filters live only in the
                                        // expanded universe (see the twin scan above)
                                        if (inSetUniverse(postId) &&
                                            ((rangeListMaxVal === 0 && rangeListMinVal <= postVal) ||
                                            (rangeListMinVal <= postVal && rangeListMaxVal >= postVal))) {
                                            rangePostIds.push(postId);
                                        }
                                    }
                                }
                            }

                            rangePostIds = getVariation(rangePostIds, isUsedForVariations);

                            allEntities[indexEntity].items[indexItem].range_list_input[indexRange] = rangePostIds.length;
                        }
                    }
                }
            }
        }

        setData.countFilteredPostsIds = [];
        let isFiltered = false;
        if (hasTempFilters) {
            for (const [entityName, postsIds] of Object.entries(tempFiltered)) {

                // Product-level (parent-space) intersection: every filter is an
                // independent product-level clause in the server query, so a product
                // matches when ANY of its variations satisfies each filter — not
                // necessarily the same variation for all of them. On
                // variations-as-products shops the intersection (and the Apply
                // total derived from it) stays in variation space instead,
                // matching the server's ungated getBetweenFiltersIntersect.
                // Never count posts outside the set universe (see inSetUniverse above)
                const parentIds = new Set(wpcCountSpace(postsIds.filter(inSetUniverse)));

                if(setData.countFilteredPostsIds.length === 0 && !isFiltered){
                    setData.countFilteredPostsIds = [...parentIds];
                }

                setData.countFilteredPostsIds = setData.countFilteredPostsIds.filter(x => parentIds.has(x));
                isFiltered = true;
            }
        }

        setData.filteredPostsIds = [...new Set(setData.filteredPostsIds)];

        for (const [entityName, postIds] of Object.entries(entityPostsCount)) {
            const uniquePostIds = [...new Set(postIds)];
            entityPostsCount[entityName] = uniquePostIds.some(id => allPostsUniverseSet.has(Number(id)));
        }
        setData.entityPostsCount = entityPostsCount;

        updateCountersHtml(allEntities, setId);
        updateRangeInput(allEntities, setId);
    }

    const getVariation = (tempPostIdsArray, isUsedForVariations) => {
        if(!wpcIsPro) return tempPostIdsArray;
        let postIdsWithoutVariation = [];

        tempPostIdsArray.forEach(function(postId, indexPostId) {
            let isVariation = wpcFilterJsonData.product_variations_map[postId] !== undefined;
            if(!isUsedForVariations && isVariation){
                // Map the variation to its parent product (the Set dedups).
                // Dropping variations here made numeric-range counts lose every
                // variable product: their _price data is represented by
                // variation IDs, while the server counts parent products whose
                // variation price matches the range.
                postIdsWithoutVariation.push(Number(wpcFilterJsonData.product_variations_map[postId]));
            }else{
                postIdsWithoutVariation.push(postId);
            }
        });

        return [...new Set(postIdsWithoutVariation)]
    }

    // Final DISPLAYED-count space. On shops that list variations as standalone
    // catalog items (XStore's variable_products_detach — the server sets
    // wpcFilterJsonData.variationsAsProducts) the visible item unit IS a
    // variation, so term counters and the Apply total must stay in variation
    // space; everywhere else counts collapse to distinct parent products.
    // Mirrors the server-side wpc_from_variations_to_products counting gate.
    // Space normalization for intersections / universe membership keeps using
    // getVariation() directly and is deliberately NOT affected by the flag.
    const wpcCountSpace = (postIdsArray) =>
        wpcFilterJsonData.variationsAsProducts ? [...new Set(postIdsArray)] : getVariation(postIdsArray, false);



    // The set universe in both spaces: parent products and the variation-expanded
    // one (variable parents replaced by their variations there). meta_values can
    // be keyed by EITHER space — PARENT ids for post dates and product-level
    // values, VARIATION ids for used-for-variations data — so every universe
    // membership check must accept both spaces.
    const wpcUniverse = (setId) => {
        const expandedKeys = Object.keys(wpcFilterJsonData[setId].allPostsIds);
        const allPostsExpandedSet = new Set(expandedKeys.map(Number));
        const allPostsUniverseSet = new Set(getVariation(expandedKeys, false).map(Number));
        const inSetUniverse = (id) => allPostsUniverseSet.has(+id) || allPostsExpandedSet.has(+id);
        return { allPostsExpandedSet, allPostsUniverseSet, inSetUniverse };
    };

    // jQuery .data() type-casts attribute values ("0" -> number 0,
    // "false" -> boolean false), while slugs in the inline JSON are always
    // strings — normalize every slug read from .data() before comparing or
    // collecting, or terms named "0"/"false" silently stop matching.
    const wpcTermSlug = (slug) => (slug === undefined ? undefined : String(slug));

    // The timepicker addon is enqueued only when a date+time filter exists on
    // the page; date-only pages have just the stock jQuery UI datepicker (with
    // the addon present, datetimepicker get/setDate works on both kinds).
    const wpcPickerFn = () => ($.fn.datetimepicker ? 'datetimepicker' : 'datepicker');

    // Terminate a built URL's path exactly like PHP's user_trailingslashit()
    // does for the server-rendered links (wpcFilterJsonData.trailingSlash
    // carries the site convention) — a mismatched slash costs a 301 redirect
    // on every Apply click and breaks the newUrl/applyDataUrl comparisons
    const wpcNormalizeTrailingSlash = (url) => {
        if (typeof wpcFilterJsonData.trailingSlash === 'undefined' || url.pathname === '/') {
            return url;
        }
        if (wpcFilterJsonData.trailingSlash) {
            if (!url.pathname.endsWith('/')) {
                url.pathname += '/';
            }
        } else {
            url.pathname = url.pathname.replace(/\/+$/, '');
        }
        return url;
    };

    // Maps a variation id to its parent product id (identity for non-variations).
    // Cross-entity logic must compare PRODUCTS: the server treats every filter as
    // an independent product-level clause, so intersecting raw variation ids would
    // require one variation to satisfy all filters at once and lose products.
    const wpcParentOf = (postId) => {
        if (!wpcIsPro || !wpcFilterJsonData.product_variations_map) return Number(postId);
        const parent = wpcFilterJsonData.product_variations_map[postId];
        return parent !== undefined ? Number(parent) : Number(postId);
    };

    // Range bounds must reflect the INTERSECTION of the other selected filters
    // (filteredPostsIds is a UNION across entities). The intersection is taken in
    // parent space and then expanded back to the whole universe families, because
    // the bounds cover ALL price rows of the surviving products — like the server
    const intersectTempFiltered = (jsonData, excludeEname) => {
        let parentInter = null;
        for (const [eName, ids] of Object.entries(jsonData.tempFilteredAllPostsIds)) {
            if (eName === excludeEname) continue;
            const parents = new Set(getVariation(ids, false));
            if (parentInter === null) {
                parentInter = parents;
            } else {
                const prev = parentInter;
                parentInter = new Set();
                for (const p of parents) {
                    if (prev.has(p)) {
                        parentInter.add(p);
                    }
                }
            }
        }
        if (parentInter === null) {
            return Object.values(jsonData.filteredPostsIds);
        }
        // Parent ids first: post dates and product-level meta rows are keyed by
        // them, and the expanded universe below only carries variation ids for
        // variable products. Duplicated simple ids are harmless for min/max.
        const familyIds = [...parentInter];
        for (const key of Object.keys(jsonData.allPostsIds)) {
            const id = Number(key);
            if (parentInter.has(wpcParentOf(id))) {
                familyIds.push(id);
            }
        }
        return familyIds;
    };

    const updateRangeInput = (allEntities, setId) => {
        const $filterSetEl = $('.wpc-filter-set-' + setId);
        const $rangeInputs = $('.wpc-filters-range-inputs', $filterSetEl);
        const setData = wpcFilterJsonData[setId];
        const isSearchUrl = wpcIsSearchUrl()

        if(isSearchUrl && !setData.allPostsIds.length){
            return;
        }

        $rangeInputs.each(function() {
            const $container = $(this);
            const $forms = $('form', $container);
            const $rangeListInputs = $('.wpc-range-list-item:checked', $container);
            const rangeListData = $rangeListInputs.length ? $rangeListInputs.data() : null;
            const rangeListMin = rangeListData ? rangeListData.min : '';
            const rangeListMax = rangeListData ? rangeListData.max : '';

            $forms.each(function() {
                const $form = $(this);

                // Number range
                const $min = $('.wpc-filters-range-min', $form);
                const $max = $('.wpc-filters-range-max', $form);

                if ($min.length && $max.length) {
                    const minData = $min.data();
                    const maxData = $max.data();
                    const min = Number(minData.min);
                    const max = Number(maxData.max);
                    const minCurVal = Number($min.val());
                    const maxCurVal = Number($max.val());

                    if (minCurVal == min && max == maxCurVal) {
                        const $inputs = $('input', $form);
                        const jsonData = wpcFilterJsonData[setId];
                        const tempFilteredLength = Object.keys(jsonData.tempFilteredAllPostsIds).length;

                        $inputs.each(function() {
                            const $input = $(this);
                            const data = $input.data();
                            const wpcEname = data.wpcEName;

                            if (typeof data.min !== "undefined") {
                                const hasOnlyOneElement = tempFilteredLength === 1 &&
                                    typeof jsonData.tempFilteredAllPostsIds[wpcEname] !== 'undefined';

                                if (rangeListMin !== '') {
                                    $input.val(rangeListMin);
                                } else {
                                    let dataMin = data.absMin;
                                    let minVal = data.absMin;

                                    if (!hasOnlyOneElement && jsonData.filteredPostsIds.length) {
                                        const minValuePostIds = [];
                                        // items may arrive without the 'min' key (e.g. reindexed to a plain array)
                                        const minItem = jsonData.allEntities[wpcEname] ? jsonData.allEntities[wpcEname].items['min'] : undefined;
                                        const metaValues = minItem ? minItem.meta_values : {};

                                        for (const postId of intersectTempFiltered(jsonData, wpcEname)) {
                                            if (setData.allPostsIds[postId] === undefined) continue;
                                            const values = metaValues[postId];
                                            if (typeof values !== 'undefined') {
                                                for (const val of values) {
                                                    minValuePostIds.push(val);
                                                }
                                            }
                                        }


                                        if (minValuePostIds.length) {
                                            minVal = wpcArrayMin(minValuePostIds);
                                            dataMin = minVal;
                                        }
                                    } else if (hasOnlyOneElement) {
                                        minVal = $input.val();
                                    }

                                    $input
                                        .data('min', dataMin)
                                        .attr('data-min', dataMin)
                                        .val(minVal);
                                }
                            }

                            if (typeof data.max !== "undefined") {
                                const hasOnlyOneElement = tempFilteredLength === 1 &&
                                    typeof jsonData.tempFilteredAllPostsIds[wpcEname] !== 'undefined';

                                if (rangeListMax !== '') {
                                    $input.val(rangeListMax);
                                } else {
                                    let dataMax = data.absMax;
                                    let maxVal = data.absMax;

                                    if (!hasOnlyOneElement && jsonData.filteredPostsIds.length) {
                                        const maxValuePostIds = [];
                                        // items may arrive without the 'max' key (e.g. reindexed to a plain array)
                                        const maxItem = jsonData.allEntities[wpcEname] ? jsonData.allEntities[wpcEname].items['max'] : undefined;
                                        const metaValues = maxItem ? maxItem.meta_values : {};

                                        for (const postId of intersectTempFiltered(jsonData, wpcEname)) {
                                            if (setData.allPostsIds[postId] === undefined) continue;
                                            const values = metaValues[postId];
                                            if (typeof values !== 'undefined') {
                                                for (const val of values) {
                                                    maxValuePostIds.push(val);
                                                }
                                            }
                                        }

                                        if (maxValuePostIds.length) {
                                            maxVal = wpcArrayMax(maxValuePostIds);
                                            dataMax = maxVal;
                                        }else{
                                            maxVal = dataMax = 0;
                                        }
                                    } else if (hasOnlyOneElement) {
                                        maxVal = $input.val();
                                    }

                                    $input
                                        .data('max', dataMax)
                                        .attr('data-max', dataMax)
                                        .val(maxVal);
                                }
                            }
                        });

                        $.fn.wpcInitSlider($form);
                    }
                }

                // Date range
                const $fromEl = $('.wpc-filters-range-from.hasDatepicker', $container);
                const $toEl = $('.wpc-filters-range-to.hasDatepicker', $container);

                if ($fromEl.length && $toEl.length) {
                    const fromData = $fromEl.data();
                    const toData = $toEl.data();

                    if (fromData.wpcTempFrom == fromData.wpcAbsFrom && toData.wpcAbsTo == toData.wpcTempTo) {
                        const jsonData = wpcFilterJsonData[setId];
                        const filteredPostIds = Object.values(jsonData.filteredPostsIds);
                        const hasFilteredPosts = filteredPostIds.length > 0;
                        const tempFilteredLength = Object.keys(jsonData.tempFilteredAllPostsIds).length;

                        $('input', $form).each(function() {
                            const data = $(this).data();
                            const wpcEname = data.wpcEName;
                            const hasOnlyOneElement = tempFilteredLength === 1 &&
                                wpcEname in jsonData.tempFilteredAllPostsIds;

                            const shouldFilter = !hasOnlyOneElement && hasFilteredPosts;
                            const boundPostIds = shouldFilter ? intersectTempFiltered(jsonData, wpcEname) : filteredPostIds;

                            const findDateBound = (metaValues, findMin) => {
                                let bound = findMin ? Infinity : -Infinity;
                                for (let i = 0; i < boundPostIds.length; i++) {
                                    const val = metaValues[boundPostIds[i]];
                                    if (val !== undefined) {
                                        const ts = Date.parse(val);
                                        if (findMin ? ts < bound : ts > bound) bound = ts;
                                    }
                                }
                                return isFinite(bound) ? new Date(bound) : null;
                            };

                            const pickerFn = wpcPickerFn();

                            if (data.wpcTempFrom !== undefined) {
                                let setDate = new Date(data.wpcAbsFromRaw);
                                if (shouldFilter) {
                                    const fromItem = jsonData.allEntities[wpcEname] ? jsonData.allEntities[wpcEname].items['from'] : undefined;
                                    const metaValues = fromItem ? fromItem.meta_values : {};
                                    setDate = findDateBound(metaValues, true) ?? setDate;
                                }
                                $(this)[pickerFn]('setDate', setDate);
                            }

                            if (data.wpcTempTo !== undefined) {
                                let setDate = new Date(data.wpcAbsToRaw);
                                if (shouldFilter) {
                                    const toItem = jsonData.allEntities[wpcEname] ? jsonData.allEntities[wpcEname].items['to'] : undefined;
                                    const metaValues = toItem ? toItem.meta_values : {};
                                    setDate = findDateBound(metaValues, false) ?? setDate;
                                }
                                $(this)[pickerFn]('setDate', setDate);
                            }
                        });
                    }
                }
            });
        });
    }

    const updateCountersHtml = (allEntities, setId) => {
        const $filterSetEl = $('.wpc-filter-set-' + setId);
        const hideEmpty = (wpcFilterJsonData[setId].settings.hide_empty !== undefined) ? wpcFilterJsonData[setId].settings.hide_empty : false;
        const isHideEmpty = hideEmpty === 'yes';
        const hideEmptyFilter = (wpcFilterJsonData[setId].settings.hide_empty_filter !== undefined) ? wpcFilterJsonData[setId].settings.hide_empty_filter : false;
        const isHideEmptyFilter = hideEmptyFilter === 'yes';

        const selectorsCache = {};

        Object.entries(allEntities).forEach(([entityName, entityData]) => {
            let rangeList = entityData.filter.range_list_input;
            const entity = entityData.filter.entity;
            const entitySelector = '[data-wpc-e-name="' + entityName + '"]';
            const moreLess = entityData.filter.more_less;
            const isMoreLess = moreLess === 'yes';

            Object.entries(entityData.items).forEach(([index, item]) => {
                const itemSlug = item.slug;
                const selectorKey = entityName + '|' + itemSlug;

                if (!selectorsCache[selectorKey]) {
                    selectorsCache[selectorKey] = {
                        $el: $(entitySelector + '[data-wpc-slug="' + itemSlug + '"]', $filterSetEl).not('.wpc-apply-button-chip'),
                        $parent: null
                    };
                }

                const cacheItem = selectorsCache[selectorKey];
                const $el = cacheItem.$el;

                if($el.length){
                    if (!cacheItem.$parent) {
                        cacheItem.$parent = $el.parent();
                    }
                    const $parent = cacheItem.$parent;

                    let $counter = $('.wpc-term-count-value', $parent);
                    // closest(), NOT parents(): in a hierarchy parents() climbs to
                    // ancestor term-items too, so a zero-count child poisoned its
                    // parents with the count-0/hidden-0 classes.
                    // With "Show counters" disabled the counter spans do not exist,
                    // but the empty-term state classes must still be maintained —
                    // derive the li from the input itself then. Options and stars
                    // keep the counter-based (empty) path: their own branches below
                    // manage their state.
                    const isGenericInput = !$el.is('option') && !$el.hasClass('flrt-star-input');
                    let $parentItem = isGenericInput ? $el.closest('.wpc-term-item') : $counter.closest('.wpc-term-item');
                    if($counter.length){
                        $counter.text(item.cross_count);
                    }
                    if($counter.length || isGenericInput){
                        if(item.cross_count === 0){
                            $parentItem.addClass('wpc-term-count-0')
                        } else {
                            $parentItem.removeClass('wpc-term-count-0')
                        }
                    }

                    if(isHideEmpty){
                        if(item.cross_count <= 0){
                            $parentItem.addClass('wpc-term-count-hidden-0').removeClass('wpc-has-terms');
                            if($el.is(':checked')){
                                $parentItem.addClass('wpc-term-count-hidden-checked-0')
                            }else{
                                $parentItem.removeClass('wpc-term-count-hidden-checked-0')
                            }
                        } else {
                            $parentItem.removeClass('wpc-term-count-hidden-0 wpc-term-count-hidden-checked-0').addClass('wpc-has-terms');
                        }
                    }else{
                        if(item.cross_count <= 0){
                            if(!$el.is(':checked')){
                                $parentItem.removeClass('wpc-has-terms');
                            }
                        } else {
                            $parentItem.addClass('wpc-has-terms');
                        }
                    }



                    let $starLabel = $('.flrt-star-label', $parent);
                    if($starLabel.length && typeof $starLabel.data().wpcTermCount !== 'undefined'){
                        $starLabel.attr('data-wpc-term-count', item.cross_count);
                        $starLabel.data('wpcTermCount', item.cross_count);
                        // Stars have no counter spans, so the generic branch above
                        // never toggles the zero-class that drives the disabled look
                        const $starItem = $starLabel.closest('.wpc-term-item');
                        if(item.cross_count === 0){
                            $starItem.addClass('wpc-term-count-0');
                        }else{
                            $starItem.removeClass('wpc-term-count-0');
                        }
                        // The visible number next to the stars is written only on
                        // hover/click and then frozen via .flrt-change-blocked, so a
                        // recount would otherwise leave a stale value there
                        if($el.is(':checked')){
                            const showTermCount = $starLabel.closest('.flrt-stars-wpc-filter-content').data('showTermCount');
                            if(showTermCount){
                                $('#flrt-wpc-term-count', $filterSetEl).text(item.cross_count);
                            }
                        }
                    }

                    if($el.is('option')){
                        const isSelect2 = $el.closest('select').hasClass('select2-hidden-accessible');
                        const $select = $($el.closest('select'), $filterSetEl);
                        const $childOption = $select.find(`.wpc-term-id-${$el.val()}`);
                        if (isHideEmpty) {
                            if ($childOption.length) {
                                if (item.cross_count <= 0) {
                                    $childOption.addClass('wpc-term-count-hidden-0').removeClass('wpc-has-terms');
                                    if($el.is(':selected')){
                                        $parentItem.addClass('wpc-term-count-hidden-checked-0')
                                    }else{
                                        $parentItem.removeClass('wpc-term-count-hidden-checked-0')
                                    }
                                } else {
                                    $childOption.removeClass('wpc-term-count-hidden-0').addClass('wpc-has-terms');
                                }
                            }
                        } else {
                            if(item.cross_count <= 0){
                                if(!$el.is(':selected')){
                                    $childOption.removeClass('wpc-has-terms');
                                }
                            } else {
                                $childOption.addClass('wpc-has-terms');
                            }
                        }
                        if(!isSelect2){
                            $el[0].textContent = $el[0].textContent.replace(/\(\d+\)/, '(' + item.cross_count + ')');
                        }
                        if(isSelect2){
                            // Non-ASCII term slugs are percent-encoded ("%" breaks Sizzle selectors)
                            const $select2El = $('.' + $.escapeSelector(`select2-${entityName}-${itemSlug}`))
                            if($select2El.length){
                                $select2El.find('.wpc-term-count-value').text(item.cross_count);
                            }
                            //wpcInitSelect2( 'wpc-filter-set-'+setId );
                        }
                    }

                    if(typeof $el.data().count !== 'undefined'){
                        $el.attr('data-count', item.cross_count);
                        $el.data('count', item.cross_count);
                    }
                }

                if (typeof rangeList === 'object') {
                    Object.entries(entityData.items[index].range_list_input).forEach(([indexRange, count]) => {
                        const rangeId = 'wpc-radio-' + entity + '-' + entityName + '-' + indexRange;
                        const $rangeEl = $('#' + rangeId + '.wpc-range-list-item', $filterSetEl);
                        if ($rangeEl.length) {
                            let $counter = $('.wpc-term-count-value', $rangeEl.parent());
                            // The li must not depend on the counter span existing —
                            // with "Show counters" off the state classes still apply
                            let $parentItem = $rangeEl.closest('.wpc-term-item');
                            if ($counter.length) {
                                $counter.text(count);
                            }
                            if (count === 0) {
                                $parentItem.addClass('wpc-term-count-0')
                            } else {
                                $parentItem.removeClass('wpc-term-count-0')
                            }
                            if(isHideEmpty){
                                if(count === 0){
                                    $parentItem.addClass('wpc-term-count-hidden-0')
                                    if($rangeEl.is(':checked')){
                                        $parentItem.addClass('wpc-term-count-hidden-checked-0')
                                    }else{
                                        $parentItem.removeClass('wpc-term-count-hidden-checked-0')
                                    }
                                } else {
                                    $parentItem.removeClass('wpc-term-count-hidden-0 wpc-term-count-hidden-checked-0')
                                }
                            }
                        }
                    });
                }
            });

            const $section = $(`.wpc-filters-section.wpc-filter-${entityName}`, $filterSetEl)
            let $wpcTermsItems = $section.find(`.wpc-filters-ul-list li, .wpc-filters-widget-select option`);
            let $wpcHasTermsItems = $section.find(`.wpc-filters-ul-list li.wpc-has-terms, .wpc-filters-widget-select option.wpc-has-terms`);
            const $wpcHasCheckedTermsItems = $section.find(`.wpc-filters-ul-list input:checked, .wpc-filters-ul-list option:selected`)
            $wpcTermsItems.removeClass('wpc-not-hidden-term');
            // Views without per-term counter spans (e.g. the stars rating) have no
            // li.wpc-has-terms bookkeeping, so a section hidden by hide_empty_filter
            // could never reappear — decide from the fresh recount data as well.
            // Skip the empty-slug pseudo-term: meta entities carry a ''-item for
            // products WITHOUT the field — it never renders as a selectable term,
            // but its cross_count kept whole custom-field sections visible
            const hasAnyTermPosts = Object.values(entityData.items).some(item => (item.slug ?? '') !== '' && (item.cross_count || 0) > 0);
            let wpcHasTerms = $wpcHasTermsItems.length || $wpcHasCheckedTermsItems.length || (hasAnyTermPosts ? 1 : 0)
            const isHideFilterElement = (entity === 'post_meta_num' || entity === 'post_date' || entity === 'tax_numeric' || entity === 'post_meta_num');

            if (isHideFilterElement && wpcFilterJsonData[setId].entityPostsCount[entityName] !== undefined) {
                wpcHasTerms = wpcFilterJsonData[setId].entityPostsCount[entityName] === true ? 1 : 0;
            }

            if(wpcHasTerms <= 0 && isHideEmptyFilter){
                $section.addClass('wpc-filters-section-0');
            }else{
                $section.removeClass('wpc-filters-section-0');
            }

            if (isMoreLess) {
                if(!isHideEmpty){
                    wpcHasTerms = $wpcTermsItems.length;
                }

                if (wpcHasTerms <= +wpcMoreLessCount) {
                    $section.addClass('wpc-filter-few-terms');
                } else {
                    $section.removeClass('wpc-filter-few-terms');
                }

                if (isHideEmpty) {
                    $wpcTermsItems.filter('li.wpc-has-terms').slice(0, +wpcMoreLessCount).each(function () {
                        $(this).addClass('wpc-not-hidden-term');
                    });
                } else {
                    $wpcTermsItems.filter('li').slice(0, +wpcMoreLessCount).each(function () {
                        $(this).addClass('wpc-not-hidden-term');
                    });
                }
            }
        });

        // The parents' disabled look is gated by :not(.wpc-has-not-empty-children)
        // (see the dynamic CSS in Plugin.php) — recompute the class from the fresh
        // counts, or a hierarchy parent whose children all dropped to 0 keeps the
        // stale server-rendered class and never greys out
        $('li.wpc-has-children', $filterSetEl).each(function () {
            const hasNotEmpty = $(this).children('ul.children')
                .find('li.wpc-term-item:not(.wpc-term-count-0)').length > 0;
            $(this).toggleClass('wpc-has-not-empty-children', hasNotEmpty);
        });

        const $otherFilterSetsOnPage = $(wpcWidgetContainer);
        const hasMultipleWidgets = $otherFilterSetsOnPage.length > 1;

        if (hasMultipleWidgets) {
            let tempPostIds = [];
            $otherFilterSetsOnPage.each((index, filterSetWidget) => {
                const widgetSetId = $(filterSetWidget).data('set');
                if (wpcFilterJsonData[widgetSetId] && wpcFilterJsonData[widgetSetId].countFilteredPostsIds) {

                    const currentPostIds = wpcFilterJsonData[widgetSetId].countFilteredPostsIds;

                    if (index === 0) {
                        tempPostIds = new Set(currentPostIds);

                    } else {
                        tempPostIds = new Set(currentPostIds.filter(x => tempPostIds.has(x)));

                    }
                } else {
                    if (index === 0) {
                        tempPostIds = new Set();
                    } else {
                        tempPostIds = new Set();
                    }
                }
                wpcFilterJsonData[widgetSetId].countFilteredPostsIds = [...tempPostIds];
                updateApplyButtonData(widgetSetId);
            });
        }else{
            updateApplyButtonData(setId);
        }

        function updateApplyButtonData(setId){
            let filteredPostsCount = wpcFilterJsonData[setId].countFilteredPostsIds.length;
            const $applyButton = $('.wpc-filter-set-' + setId + ' .wpc-pc-apply-button');
            const $mobileApplyButton = $('.wpc-filter-set-' + setId + ' .wpc-filters-apply-button');
            if (filteredPostsCount <= 0){
                $applyButton.text('');
                $mobileApplyButton.find('.wpc-filters-found-posts').text(filteredPostsCount);
                wpcEnableStickyButtons(false);
            }else{
                $applyButton.text('(' + filteredPostsCount + ')');
                $mobileApplyButton.find('.wpc-filters-found-posts').text(filteredPostsCount);
            }

            if(filteredPostsCount){
                $applyButton.removeClass('wpc-hidden-term-count');
            }else{
                $applyButton.addClass('wpc-hidden-term-count');
            }

            let foundKey = null, foundIndex = null;
            let chips = [];

            if(wpcFilterJsonData[setId]['chips'] !== undefined){
                Object.assign(chips, wpcFilterJsonData[setId]['chips']);

                Object.keys(chips).forEach(key => {
                    if (Array.isArray(chips[key])) {
                        const idx = chips[key].findIndex(item => {
                            return typeof item === 'object' &&
                                item !== null &&
                                item.entityClass === 'wpc-chip-search';
                        });
                        if (idx !== -1) {
                            foundKey = key;
                            foundIndex = idx;
                        }
                    }
                });
                if (foundKey !== null) {
                    delete chips[foundKey];
                }
            }

            const isAnyFilter = chips !== undefined && Object.keys(chips).length > 0;

            if(!isAnyFilter){
                filteredPostsCount =  wpcFilterJsonData[setId].totalAllPostsIds;
                $mobileApplyButton.find('.wpc-filters-found-posts').text(filteredPostsCount);
            }
        }
    }

    const changeSearchUrl = (url, $el, setId) => {
        url = new URL(url);
        const $searchInput = $('.wpc-filter-search-form input.wpc-search-field', $el);
        if ($searchInput === undefined) return;


        const searchSlug = $searchInput.attr('name');
        const searchVal = $searchInput.val();

        if (searchVal !== undefined) {
            url.searchParams.delete(searchSlug);
            let newUrl = url.toString();
            $searchInput.closest('.wpc-filter-search-form').find('a.wpc-search-clear-icon').attr('href', newUrl);
            const chips = $('.wpc-filter-chips-' + setId + ' .wpc-chip-search a');

            if(chips === undefined || chips.length === 0) return;

            chips.each(function() {
                $(this).attr('href', newUrl);
            });
        }
    }

    function buildUrlForApplyButton($el, setId) {
        const $inputs = $('input:checked', '.wpc-filters-main-wrap').not('.wpc-range-list-item');
        const $options = $('option:selected', '.wpc-filters-main-wrap').not('.wpc-range-list-item');
        const $rangeForms = $('form.wpc-filter-range-form input', '.wpc-filters-main-wrap').not('.wpc-range-list-item');
        const $dateForms = $('form.wpc-filter-date-range-form-visible input', '.wpc-filters-main-wrap');
        let baseUrl    = wpcFilterJsonData.domain;
        const $submitButton = $('.wpc-filters-submit-button');
        const applyUrl = $submitButton.data('wpcApplyUrl');
        const applyButtonPage = $submitButton.data('applyButtonPage');
        if(applyUrl !== undefined && applyButtonPage){
            // Alternative Location: filter segments are appended to this base
            // via RELATIVE URL resolution below, which drops the last path
            // segment of a base without a trailing slash ("/shop" -> "/") —
            // the PHP-rendered location permalink is not slash-terminated
            const applyUrlObj = new URL(applyUrl, window.location.href);
            if (!applyUrlObj.pathname.endsWith('/')) {
                applyUrlObj.pathname += '/';
            }
            baseUrl = applyUrlObj.href;
        }

        let filterSetData = wpcFilterJsonData[setId];

        let urlParams = {};
        let urlParamsWithoutSlug = {};
        let urlParamsWithoutPermalinks = {};


        const processElements = ($elements, setId) => {
            $elements.each(function(index, el) {
                const $currentEl =  $(el);
                const data = $currentEl.data();

                if (typeof data.wpcEName === "undefined") {
                    return;
                }

                let entity = filterSetData.allEntities[data.wpcEName]['filter']['entity'];
                if(entity!== undefined && entity === 'taxonomy'){
                    if ($currentEl.is(':disabled:checked')) {
                        return;
                    }
                }

                if(typeof wpcFilterJsonData.wpcFilterEntitiesWithoutSlug[data.wpcEName] !== "undefined"){

                    let val = $currentEl.val();

                    if(typeof data.wpcTempFrom !== 'undefined' || typeof data.wpcTempTo !== 'undefined'){
                        let wpcTempFrom = (typeof data.wpcTempFrom !== 'undefined');
                        let wpcTempTo= (typeof data.wpcTempTo !== 'undefined');
                        let wpcAbsFrom= (typeof data.wpcAbsFrom !== 'undefined');
                        let wpcAbsTo= (typeof data.wpcAbsTo !== 'undefined');
                        let dateTimeStr = false;

                        if(wpcAbsFrom && data.wpcAbsFrom === val){
                            return;
                        }

                        if(wpcTempFrom && wpcAbsFrom && data.wpcTempFrom === data.wpcAbsFrom){
                            return;
                        }

                        if(wpcAbsTo && data.wpcAbsTo === val){
                            return;
                        }


                        if(wpcTempTo && wpcAbsTo && data.wpcTempTo === data.wpcAbsTo){
                            return;
                        }

                        if(wpcTempFrom){
                            dateTimeStr = $("#wpc-filters-alt-date-from-"+ data.fid)[wpcPickerFn()]('getDate');
                        }

                        if(wpcTempTo){
                            dateTimeStr = $("#wpc-filters-alt-date-to-"+ data.fid)[wpcPickerFn()]('getDate');
                        }

                        if(dateTimeStr !== false){
                            val = formatDate(dateTimeStr, data.wpcDateType);
                        }
                    }

                    if(typeof data.min !== 'undefined' || typeof data.max !== 'undefined'){
                        val = Number($currentEl.val());
                    }

                    if(typeof data.absMin !== 'undefined' && data.absMin === val){
                        return;
                    }
                    if(typeof data.min !== 'undefined' && data.min === val){
                        return;
                    }
                    if(typeof data.absMax !== 'undefined' && data.absMax === val){
                        return;
                    }
                    if(typeof data.max !== 'undefined' && data.max === val){
                        return;
                    }
                    const sortKey = Object.keys(wpcFilterJsonData.wpcFilterPermalinksNum)
                        .find(k => wpcFilterJsonData.wpcFilterPermalinksNum[k] === data.wpcEName);

                    if (!sortKey) return;

                    urlParamsWithoutSlug[sortKey] ??= [];
                    const wpcEName = wpcFilterJsonData.wpcFilterPermalinks[data.wpcEName];
                    urlParamsWithoutSlug[sortKey][wpcEName] ??= [];
                    urlParamsWithoutSlug[sortKey][wpcEName][data.wpcSlug] = val;
                    return;
                }

                const sortKey = Object.keys(wpcFilterJsonData.wpcFilterPermalinksNum)
                    .find(k => wpcFilterJsonData.wpcFilterPermalinksNum[k] === data.wpcEName);

                if (!sortKey) return;

                urlParams[sortKey] ??= [];
                const wpcEName = wpcFilterJsonData.wpcFilterPermalinks[data.wpcEName];
                urlParams[sortKey][wpcEName] ??= [];
                if (!urlParams[sortKey][wpcEName].includes(wpcTermSlug(data.wpcSlug))) {
                    urlParams[sortKey][wpcEName].push(wpcTermSlug(data.wpcSlug));
                }
            });
        };

        processElements($inputs,  setId);
        processElements($options, setId);
        processElements($rangeForms, setId);
        processElements($dateForms, setId);

        const $searchInput = $('.wpc-filter-search-form input.wpc-search-field', $el);
        if($searchInput !== undefined && $searchInput.length){

            let sortKey = Object.values(wpcFilterJsonData.wpcFilterPermalinksNum).length + 1;
            const searchSlug = $searchInput.attr('name');
            const searchVal = $searchInput.val();

            if (searchVal !== undefined && searchVal !== '') {
                if(wpcFilterJsonData.wpcFilterEntitiesWithoutSlug === undefined){
                    wpcFilterJsonData.wpcFilterEntitiesWithoutSlug = {}
                }
                wpcFilterJsonData.wpcFilterEntitiesWithoutSlug.search = 'search';

                if (urlParamsWithoutSlug[sortKey]=== undefined) {
                    urlParamsWithoutSlug[sortKey] = {};
                }
                if (urlParamsWithoutSlug[sortKey]['search'] === undefined) {
                    urlParamsWithoutSlug[sortKey]['search'] = {};
                }
                urlParamsWithoutSlug[sortKey]['search'][searchSlug] = searchVal;
            }
        }

        let urlArray = [];

        let wpcFilterPermalinksKeys = {};
        for (const [key, value] of Object.entries(wpcFilterJsonData.wpcFilterPermalinks)) {
            wpcFilterPermalinksKeys[value] = key;
        }

        if(wpcIsPro) {
            let url = new URL(baseUrl);
            if(permalinksEnabled){
                if (Object.keys(urlParams).length > 0) {
                    Object.entries(urlParams).forEach(([key, value]) => {
                        Object.entries(value).forEach(([filterName, arr]) => {
                            let urlKey = wpcFilterPermalinksKeys[filterName]
                            const sorted = arr.sort((a, b) =>
                                Object.values(filterSetData['allEntities'][urlKey].items_sort).indexOf(a) - Object.values(filterSetData['allEntities'][urlKey].items_sort).indexOf(b));
                            let logic = filterSetData['allEntities'][urlKey]['filter']['logic'];
                            let tempUrlString = filterName + "-" + sorted.join('-' + logic + '-');
                            urlArray.push(tempUrlString)
                        });
                    });
                }
            }
            if(!permalinksEnabled){
                if (Object.keys(urlParams).length > 0) {
                    if (Object.keys(urlParams).length > 0) {
                        Object.entries(urlParams).forEach(([key, value]) => {
                            Object.entries(value).forEach(([filterName, arr]) => {
                                let urlKey = wpcFilterPermalinksKeys[filterName]
                                const sorted = arr.sort((a, b) =>
                                    Object.values(filterSetData['allEntities'][urlKey].items_sort).indexOf(a) - Object.values(filterSetData['allEntities'][urlKey].items_sort).indexOf(b));
                                url.searchParams.set(filterName, sorted.join(';'));
                            });
                        });
                    }
                }
            }

            if(permalinksEnabled){
                if (urlArray.length) {
                    // Relative resolution against baseUrl (the clean page URL
                    // without filter segments, always slash-terminated) appends
                    // the filter path: /shop/ + color-blue -> /shop/color-blue
                    url = new URL(urlArray.join('/'), baseUrl);
                } else {
                    // No path filters selected (e.g. only a numeric range, which
                    // travels as a GET param): keep the page base URL as is.
                    // new URL("/", baseUrl) wiped the path and produced links
                    // like http://site.test/?max_price=84 instead of
                    // http://site.test/shop/?max_price=84
                    url = new URL(baseUrl);
                }
            }


            if (Object.keys(urlParamsWithoutSlug).length > 0) {
                Object.entries(urlParamsWithoutSlug).forEach(([key, value]) => {
                    Object.entries(value).forEach(([filterName, arr]) => {
                        Object.entries(arr).forEach(([item_key, item]) => {
                            url.searchParams.set(item_key, item);
                        });
                    });
                });
            }
            if(url.pathname === '/' && url.search === '' && applyButtonPage) {
                return baseUrl;
            }
            wpcNormalizeTrailingSlash(url);
            changeSearchUrl(url, $el, setId);
            return url.toString();
        }


        if(!wpcIsPro){
            if (Object.keys(urlParams).length > 0) {
                Object.entries(urlParams).forEach(([key, value]) => {
                    Object.entries(value).forEach(([filterName, arr]) => {
                        let urlKey = wpcFilterPermalinksKeys[filterName]
                        const sorted = arr.sort((a, b) =>
                            Object.values(filterSetData['allEntities'][urlKey].items_sort).indexOf(a) - Object.values(filterSetData['allEntities'][urlKey].items_sort).indexOf(b));
                        let filterPositon = Number(filterSetData['allEntities'][urlKey]['filter']['menu_order']);
                        urlParamsWithoutPermalinks[filterPositon] = {[filterName]: sorted.join(';')}

                    });
                });
            }

            if (Object.keys(urlParamsWithoutSlug).length > 0) {
                Object.entries(urlParamsWithoutSlug).forEach(([key, value]) => {
                    Object.entries(value).forEach(([filterName, arr]) => {
                        let urlKey = wpcFilterPermalinksKeys[filterName]
                        let filterPositon = Number(filterSetData['allEntities'][urlKey]['filter']['menu_order']);
                        urlParamsWithoutPermalinks[filterPositon] = arr;
                    });
                });
            }
            let url = new URL(baseUrl);
            if (Object.keys(urlParamsWithoutPermalinks).length > 0) {
                Object.values(urlParamsWithoutPermalinks).forEach((item) => {
                    Object.entries(item).forEach(([item_key, item]) => {
                        url.searchParams.set(item_key, item);
                    });
                });
            }
            if(url.pathname === '/' && url.search === '' && applyButtonPage) {
                return baseUrl;
            }
            wpcNormalizeTrailingSlash(url);
            changeSearchUrl(url, $el, setId);
            return url.toString();
        }
    }

    function formatDate(value, type) {
        const d = new Date(value);

        const pad = n => String(n).padStart(2, '0');

        const date = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
        const time = `${pad(d.getHours())}.${pad(d.getMinutes())}.${pad(d.getSeconds())}`;
        const timeColon = `${pad(d.getHours())}.${pad(d.getMinutes())}.${pad(d.getSeconds())}`;

        switch (type) {
            case 'date':     return date;
            case 'datetime': return `${date}t${time}`;
            case 'time':     return timeColon;
            default:         return date;
        }
    }

    function compareInputWithRangeList(form, applyButtonMode){
        if(!applyButtonMode) return;
        let inputDataMin = $('input.wpc-filters-range-min', form);

        if(!inputDataMin.length) return;

        let inputDataMax = $('input.wpc-filters-range-max', form);
        let inputMinVal = Number(inputDataMin.val());
        let inputMaxVal = Number(inputDataMax.val());
        let $rangeList = form.parents().find('.wpc-filter-' + inputDataMin.data('wpcEName') + ' .wpc-filters-range-inputs .wpc-filters-radio input');

        if (!$rangeList.length) return;
        $rangeList.each(function (i, radioEl){
            let data = $(radioEl).data();
            let elMin = Number(data.min)
            let elMax = Number(data.max)


            if((elMin !== inputMinVal || elMax !== inputMaxVal)){
                $(radioEl).prop('checked', false).data('wpc-was-checked', false);
            }

            if((elMin === inputMinVal && elMax === inputMaxVal)){
                $(radioEl).prop('checked', true).data('wpc-was-checked', true);
            }
        });
    }

    function compareWithOtherFilterSets($el, setId){
        let $otherFilterSetsOnPage;
        $otherFilterSetsOnPage = $(wpcWidgetContainer).not(`.wpc-filter-set-${setId}`);
        let changedFilterSets = new Set();
        if ($otherFilterSetsOnPage) {
            $otherFilterSetsOnPage.each((index, filterSetWidget) => {
                const $filterSetWidget = $(filterSetWidget);
                const changedSetId = $filterSetWidget.data('set');
                const $allInputData = $('input, option', $filterSetWidget);
                const $allInputDataThisWidget = $('input, option', `.wpc-filter-set-${setId}`);

                $allInputData.each((index, inputEl) => {
                    const $inputEl = $(inputEl);
                    const data = $inputEl.data();
                    const wpcEName = data.wpcEName;
                    const wpcSlug = data.wpcSlug;

                    if(wpcEName !== undefined && wpcSlug !== undefined){
                        const $input  = $allInputDataThisWidget.filter(function () {
                                return $(this).data('wpc-e-name') === data.wpcEName &&
                                    $(this).data('wpc-slug') === data.wpcSlug
                        });

                        if($input.length){
                            const isChecked = $input.is(':checked') || $input.is(':selected')
                            const $inputForChange = $filterSetWidget.find($inputEl);
                            const isRatingStar = $inputForChange.hasClass('flrt-star-input');
                            if ($inputEl.prop('tagName') === 'OPTION') {
                                if($input.prop('selected') !== $inputEl.prop('selected')){
                                     $inputForChange.prop('selected', isChecked);
                                }
                            }

                            if ($inputEl.prop('tagName') === 'INPUT') {
                                if ($inputEl.attr('type') === 'checkbox' || $inputEl.attr('type') === 'radio') {
                                    if($input.prop('checked') !== $inputEl.prop('checked')){
                                        $inputForChange.prop('checked', isChecked);
                                    }
                                }
                                if ($inputEl.attr('type') === 'number' || $inputEl.attr('type') === 'text') {
                                    const isRange = $input.hasClass('wpc-filters-range-min') && $inputEl.hasClass('wpc-filters-range-min') || $input.hasClass('wpc-filters-range-max') && $inputEl.hasClass('wpc-filters-range-max')
                                    if(isRange){
                                        if ($input.val() !== $inputEl.val()) {
                                            $inputForChange.val($input.val());
                                            if(data.min !== undefined){
                                                $inputForChange.data('min', $input.data('min')).attr('data-min', $input.data('min'))
                                            }
                                            if(data.max !== undefined){
                                                $inputForChange.data('max', $input.data('max')).attr('data-max', $input.data('max'));
                                            }
                                        }
                                    }
                                    const isDate = ($input.hasClass('wpc-filters-range-from') && $inputEl.hasClass('wpc-filters-range-from') || $input.hasClass('wpc-filters-range-to') && $inputEl.hasClass('wpc-filters-range-to'))
                                    && $input.data('wpc-date-type') === data.wpcDateType;
                                    if(isDate){
                                        if ($input.val() !== $inputEl.val()) {
                                            const newDate = $input[wpcPickerFn()]('getDate');
                                            $inputForChange[wpcPickerFn()]('setDate', newDate);
                                        }
                                    }
                                }
                            }
                            changedFilterSets.add(changedSetId)
                        }
                    }

                    if(wpcEName !== undefined && data.wpcSlugMin !== undefined && $inputEl.hasClass('wpc-range-list-item')){
                        const $input  = $allInputDataThisWidget.filter(function () {
                            return $(this).data('wpc-slug-min') !== undefined &&
                                $(this).prop('id') === $inputEl.prop('id') &&
                                $(this).data('wpc-e-name') === data.wpcEName &&
                                $(this).data('termId') === data.termId
                        });

                        if($input.length) {
                            const isChecked = $input.is(':checked') || $input.is(':selected')
                            const $inputForChange = $filterSetWidget.find($inputEl);
                            if ($inputEl.prop('tagName') === 'INPUT') {
                                if ($inputEl.attr('type') === 'checkbox' || $inputEl.attr('type') === 'radio') {
                                    if (isChecked) {
                                        $inputForChange.prop('checked', isChecked).data('wpc-was-checked', true).addClass('wpc-range-list-item-checked');
                                    }else{
                                        $inputForChange.prop('checked', isChecked).data('wpc-was-checked', false).removeClass('wpc-range-list-item-checked');
                                    }
                                }
                            }
                            changedFilterSets.add(changedSetId)
                        }
                    }
                });
            });
        }
        return changedFilterSets;
    }

    function applyJsMode($el, setId){
        updateCounters($el, setId)
        const hasMultipleWidgets = $(wpcWidgetContainer).length > 1;

        if (hasMultipleWidgets) {
            const changedFilterSets = compareWithOtherFilterSets($el, setId);
            if (changedFilterSets.size > 0) {
                for (const elementSetId of changedFilterSets) {
                    updateCounters($('.wpc-filter-set-' + elementSetId), elementSetId)
                }
            }
        }

        const newUrl = buildUrlForApplyButton($el, setId);
        if( wpcUseSelect2 === 'yes' && typeof $.fn.select2 !== 'undefined' ){
            $('.wpc-filters-widget-select, .wpc-orderby-select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('close');
                }
            });
        }
        wpcInitiateAll();
        const resetButton = $('.wpc-sticky-buttons .wpc-filters-reset-button', $el).attr('href');
        const applyDataUrl = $('.wpc-sticky-buttons .wpc-filters-submit-button', $el).data('wpcApplyUrl');
        const applyButtonUrl = $('.wpc-sticky-buttons .wpc-filters-submit-button', $el).attr('href');
        let isSticky = false;
        const isSameUrl = (newUrl + '/' === resetButton || newUrl  === resetButton + '/');
        const urlNow = window.location.href;
        if(applyButtonUrl !== newUrl){
            isSticky = true;
            if(hasMultipleWidgets){
                let $otherFilterSetsOnPage;
                $otherFilterSetsOnPage = $(wpcWidgetContainer).not(`.wpc-filter-set-${setId}`);
                if($otherFilterSetsOnPage){
                    $otherFilterSetsOnPage.each((index, filterSetWidget) => {
                        const $filterSetWidget = $(filterSetWidget);
                        $('.wpc-sticky-buttons .wpc-filters-submit-button', $filterSetWidget).attr('href', newUrl)
                        $('.wpc-filters-widget-controls-item .wpc-filters-apply-button', $filterSetWidget).attr('href', newUrl)
                    });
                }
            }
            $('.wpc-sticky-buttons .wpc-filters-submit-button', $el).attr('href', newUrl)
            $('.wpc-filters-widget-controls-item .wpc-filters-apply-button', $el).attr('href', newUrl)
        }
        if (applyDataUrl === newUrl){
            isSticky = false;
        }
        wpcEnableStickyButtons(isSticky);
        wpcUpdateStickyButtons();
    }

    // Mirrors flrt_chips_labels() in PHP: "Labels for Chips" filter option,
    // {value} replaced with the input value, or the value appended.
    function flrtChipLabelFromTemplate(template, value) {
        template = String(template);
        if (template.indexOf('{value}') !== -1) {
            return template.split('{value}').join(value);
        }
        return template + ' ' + value;
    }

    function collectChips($currentEl, setId) {
        const data = $currentEl.data();
        const chips = wpcFilterJsonData[setId]['chips'];


        const sortData = {};
        $('.wpc-filters-section').each((index, el) => {
            const fid = $(el).data('fid');
            if (fid !== undefined) sortData[fid] = index;
        });


        const pushChip = (slotIndex, chip, isSearch = false) => {
            chips[slotIndex] = chips[slotIndex] || [];

            if (!chips[slotIndex].some(c => c.entityClass === chip.entityClass)) {
                chips[slotIndex].push(chip);
            }

            if (isSearch) {
                const idx = chips[slotIndex].findIndex(c => c.entityClass === chip.entityClass);
                if (idx !== -1 && idx !== chips[slotIndex].length - 1) {
                    chips[slotIndex].push(chips[slotIndex].splice(idx, 1)[0]);
                }
            }
        };


        const makeChip = (entityClass, chipLabel, isSearch = false) => ({
            entityClass,
            chipLabel,
            wpcEName: (isSearch) ? '' : data.wpcEName,
            wpcSlug: (isSearch) ?  '' : data.wpcSlug,
        });

        const slotIndex = sortData[data.fid];

        if (data.min !== undefined || data.max !== undefined) {

            if (slotIndex === undefined) return;

            const rangeName = $currentEl
                .closest('.wpc-filters-section-' + data.fid)
                .find('.wpc-filter-title')
                .text();
            const chipLabel   = data.wpcChipLabel
                ? flrtChipLabelFromTemplate(data.wpcChipLabel, $currentEl.val())
                : `${data.wpcChipsText} ${rangeName} ${$currentEl.val()}`;
            const entityClass = `wpc-chip-${data.wpcEName}-${data.wpcSlug}`;
            const chip        = makeChip(entityClass, chipLabel);

            // A chip only for a bound the user actually set: a value equal to
            // the catalog-wide bound (data.absMin/absMax) OR to the current
            // intersection placeholder (data.min/max, refreshed by
            // updateRangeInput) is untouched — the same criterion
            // buildUrlForApplyButton uses, so chips always match the built URL
            const numVal = Number($currentEl.val());
            if (data.absMin !== undefined && numVal !== data.absMin && numVal !== data.min) pushChip(slotIndex, chip);
            if (data.absMax !== undefined && numVal !== data.absMax && numVal !== data.max) pushChip(slotIndex, chip);

        } else if (data.wpcTempFrom !== undefined || data.wpcTempTo !== undefined) {

            if (slotIndex === undefined) return;

            const chipLabel   = data.wpcChipLabel
                ? flrtChipLabelFromTemplate(data.wpcChipLabel, $currentEl.val())
                : `${data.wpcChipsText} ${$currentEl.val()}`;
            const entityClass = `wpc-chip-${data.wpcEName}-${data.wpcSlug}`;
            const chip        = makeChip(entityClass, chipLabel);

            // Untouched date fields keep data-wpc-temp-* equal to the abs bound
            // (updateInputDateData rewrites it only on a real picker pick) —
            // mirror buildUrlForApplyButton so no chip appears for a field the
            // user never opened, even after updateRangeInput narrowed its value
            if (data.wpcAbsFrom !== undefined && $currentEl.val() != data.wpcAbsFrom && data.wpcTempFrom !== data.wpcAbsFrom) pushChip(slotIndex, chip);
            if (data.wpcAbsTo   !== undefined && $currentEl.val() != data.wpcAbsTo   && data.wpcTempTo   !== data.wpcAbsTo)   pushChip(slotIndex, chip);

        } else {

            const $wpcSection  = $currentEl.closest(`.wpc-filters-section.wpc-filter-${data.wpcEName}`);
            const fid          = $wpcSection.data('fid');
            if (sortData[fid] === undefined) return;

            const sectionSlot  = sortData[fid];
            const entityClass  = `wpc-chip-${data.wpcEName}-${data.termId}`;

            let chipLabel = $(`label .wpc-filter-link`, $currentEl.closest(`.wpc-term-id-${data.termId}`)).text();

            const $select = $('select', $wpcSection);
            if ($select.length) {
                const isSelect2 = $select.hasClass('select2-hidden-accessible') && wpcUseSelect2 === 'yes';
                const selector  = isSelect2 ? 'select.select2-hidden-accessible option:selected' : 'select option:selected';
                chipLabel = $(selector, $wpcSection).data('wpcChip');
            }

            chips[sectionSlot] = chips[sectionSlot] || [];
            if (!chips[sectionSlot].some(c => c.entityClass === entityClass)) {
                chips[sectionSlot][data.termId] = makeChip(entityClass, chipLabel);
            }
        }

        const searchInput = $('.wpc-filter-set-' + setId + ' form.wpc-filter-search-form input');
        if(searchInput !== undefined){
            let searchVal = searchInput.val();
            if(searchVal !== undefined && searchVal !== ''){
                const chipLabel   = wpcSearchChipsText.replace("%s", searchVal);
                const entityClass = `wpc-chip-search`;
                const chip        = makeChip(entityClass, chipLabel, true);
                pushChip(Object.values(sortData).length + 1, chip, true);
            }

        }
    }
    function updateChipsList(setId) {
        const chips = wpcFilterJsonData[setId]['chips'];
        flrtClearChips(setId);

        const entries = Object.entries(chips);
        if (entries.length === 0) return;


        const $container = $('.wpc-filter-chips-' + setId);
        $container.find('li').remove();
        $container.removeClass('wpc-filter-chips-empty');

        const fragment = [chipsResetButtonTemplate()];

        entries.forEach(([, chipGroup]) => {
            Object.values(chipGroup).forEach(chip => {
                fragment.push(
                    chip.wpcEName === 'product_visibility'
                        ? chipsStarsTemplate(chip, setId)
                        : chipsTemplate(chip)
                );
            });
        });

        $container.append(fragment.join(''));
    }

    function chipsResetButtonTemplate(){
        // Client-rebuilt chips describe the PENDING selection and never navigate —
        // render them as spans so no crawlable link appears from client state
        return `<li class="wpc-filter-chip wpc-chip-reset-all"><span class="wpc-apply-button-chip wpc-apply-button-chips-reset" title=""><span class="wpc-chip-content"><span class="wpc-filter-chip-name">${chipsReset}</span><span class="wpc-chip-remove-icon">×</span></span></span></li>`;
    }

    function resetButtonInApplyButtonMode($this, e){

        const $el   = $this.closest(wpcWidgetContainer);
        const setId = $el.data('set');


        if (!setId || !wpcApplyButtonSets.length || !wpcApplyButtonSets.includes(setId)) return;

        e.preventDefault();
        $('html, body').css("cursor", "wait");
        wpcShowSpinner();
        setTimeout(function() {
            $el.find('.wpc-filters-widget-select').each(function () {
                const sel = this;
                const opts = sel.options;
                if (opts.length && sel.selectedIndex !== -1) {
                    sel.value = opts[0].value;
                }
            });

            $('input:checked', $el).each(function () {
                this.checked = false;
                $(this).data('wpc-was-checked', false);
            });

            $('input[type=text], input[type=number]', $el).each(function () {
                resetInputElement($(this));
            });

            applyJsMode($el, setId);

            delete wpcFilterJsonData[setId]['chips'];
            delete wpcFilterJsonData[setId]['tempFilteredAllPostsIds'];
            flrtClearChips(setId);
            wpcHideSpinner();
            $('html, body').css("cursor", "auto");
        }, 0);
    }

    function flrt_rating_star()
    {
        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25">
            <polygon class="cls-1" points="19.89 24.5 12.48 19.8 5.06 24.48 7.03 15.62 0.5 9.64 9.12 8.87 12.51 0.5 15.88 8.88 24.5 9.68 17.96 15.63 19.89 24.5"/>
        </svg>`;
    }

    function chipsTemplate(chip){
        let title = wpcSprintf(
            chipsTitle, chip.chipLabel
        )
        return `<li class="wpc-filter-chip ${chip.entityClass}"><span data-wpc-e-name="${chip.wpcEName}" data-wpc-slug="${chip.wpcSlug}" class="wpc-apply-button-chip" title="${title}"><span class="wpc-chip-content"><span class="wpc-filter-chip-name">${chip.chipLabel} </span><span class="wpc-chip-remove-icon">×</span></span></span></li>`;
    }

    function chipsStarsTemplate(chip, setId) {
        let title = wpcSprintf(
            chipsTitle, chip.chipLabel
        )
        let $starsNumber = $(`input[data-wpc-e-name="${chip.wpcEName}"][data-wpc-slug="${chip.wpcSlug}"]:checked`, $(`.wpc-filter-set-${setId}`));
        if(!$starsNumber.length){
            $starsNumber = $(`option[data-wpc-e-name="${chip.wpcEName}"][data-wpc-slug="${chip.wpcSlug}"]:selected`, $(`.wpc-filter-set-${setId}`));
        }


        let ratingNum = $starsNumber.data('ratingNum');
        let tempHtml = '';
        for (let i = 1; i <= ratingNum; i++) {
            tempHtml += `<span>${flrt_rating_star()}</span>`;
        }
        return `<li class="wpc-filter-chip ${chip.entityClass}"><span data-wpc-e-name="${chip.wpcEName}" data-wpc-slug="${chip.wpcSlug}" class="wpc-apply-button-chip" title="${title}"><span class="wpc-chip-content"><span class="wpc-chip-stars">${tempHtml} </span><span class="wpc-chip-remove-icon">×</span></span></span></li>`;

    }

    function flrtClearChips(setId){
        $('.wpc-filter-chips-'+setId + ' li').remove().addClass('wpc-empty-chips-container');
    }

    function wpcSprintf(format, ...args) {
        let index = 0;
        return format.replace(/%(\.\d+)?([sdf%])/g, (match, precision, type) => {
            if (type === '%') return '%';
            const arg = args[index++];
            switch (type) {
                case 's': return String(arg ?? '');
                case 'd': return parseInt(arg ?? 0, 10);
                case 'f': return precision
                    ? parseFloat(arg ?? 0).toFixed(parseInt(precision.slice(1)))
                    : parseFloat(arg ?? 0).toString();
                default: return match;
            }
        });
    }

    function unsetChip($chipElement){
        const wpcEName = $chipElement.data('wpcEName');
        const wpcSlug = $chipElement.data('wpcSlug');
        const setId = $chipElement.closest('.wpc-filter-chips-list').data('set')
        const $el = $(`.widget_wpc_filters_widget .wpc-filter-set-${setId}`);
        if(typeof wpcEName !== 'undefined' && typeof wpcSlug !== 'undefined'){
            const $inputElement = $el.find('[data-wpc-e-name="' + wpcEName + '"][data-wpc-slug="' + wpcSlug + '"]').not('.wpc-apply-button-chip');
            if($inputElement.length > 0){
                resetInputElement($inputElement)
                applyJsMode($el, setId)
            }
        }
    }
    function resetInputElement($inputElement) {
        const inputData = $inputElement.data();
        const type = $inputElement.prop('type');
        const isOption = ($inputElement.prop('tagName') === 'OPTION');

        const { absMin, absMax, wpcAbsFromRaw, wpcAbsToRaw, wpcAbsFrom, wpcAbsTo } = inputData;

        if (absMin !== undefined) {
            $inputElement.val(absMin).data('min', absMin).change();
        } else if (absMax !== undefined) {
            $inputElement.val(absMax).data('max', absMax).change();
        } else if (wpcAbsFromRaw !== undefined) {
            $inputElement.val(wpcAbsFrom)[wpcPickerFn()]('setDate', new Date(wpcAbsFromRaw));
        } else if (wpcAbsToRaw !== undefined) {
            $inputElement.val(wpcAbsTo)[wpcPickerFn()]('setDate', new Date(wpcAbsToRaw));
        } else if (type === 'checkbox' || type === 'radio') {
            // Clear BOTH the attribute and jQuery data: updateCounters selects
            // radios by the [data-wpc-was-checked=true] ATTRIBUTE, and .data()
            // alone does not update it — the term would stay "selected"
            $inputElement.prop('checked', false)
                .attr('data-wpc-was-checked', false)
                .data('wpc-was-checked', false);
            // The old guard compared type to 'react-radio', which never occurs —
            // dead code — and the fill classes span the WHOLE star group, not one li
            if ($inputElement.hasClass('flrt-star-input')) {
                $inputElement
                    .closest('.flrt-stars-wpc-filter-content')
                    .find('label.flrt-star-label')
                    .removeClass('flrt-star-label-hover flrt-star-label-checked flrt-star-label-not-checked')
                    .data('wpc-was-checked', false);
            }
        }else if (isOption) {
            const select = $inputElement.closest('select');
            const defaultValue = select.find('.wpc-dropdown-default').val();
            select.val(defaultValue).trigger('change')
            $inputElement.prop('selected', false);

        }
    }

    // Merges the inline page part (filteredAllPostsIds, domain, permalinks…)
    // into a blob: per-set keys are object-assigned, root keys replaced
    function wpcMergePagePart(target, pagePart) {
        for (const k in pagePart) {
            if (k === 'blobUrl') continue;
            if (target[k] && typeof target[k] === 'object' && !Array.isArray(target[k]) &&
                pagePart[k] && typeof pagePart[k] === 'object' && !Array.isArray(pagePart[k])) {
                Object.assign(target[k], pagePart[k]);
            } else {
                target[k] = pagePart[k];
            }
        }
        return target;
    }

    function updateWpcFilterJsonData(responseHtml) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(responseHtml, 'text/html');

        // Inert JSON block (Plugin::inlineScriptJsonData): JSON.parse is
        // orders of magnitude faster than eval'ing a JS object literal
        const jsonEl = doc.querySelector('#wpc-filter-json-data');
        if (jsonEl) {
            try {
                const pagePart = JSON.parse(jsonEl.textContent);

                if (pagePart.blobUrl) {
                    if (pagePart.blobUrl === window.wpcFilterJsonBlobUrl && typeof window.wpcFilterJsonData !== 'undefined') {
                        // Same blob — refresh only the page-scoped part
                        wpcMergePagePart(window.wpcFilterJsonData, pagePart);
                    } else {
                        window.wpcFilterJsonBlobUrl = pagePart.blobUrl;
                        delete window.wpcFilterJsonData;
                        window.wpcFilterJsonDataPromise = fetch(pagePart.blobUrl, { credentials: 'same-origin' })
                            .then((r) => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                            .then((blob) => {
                                window.wpcFilterJsonData = wpcMergePagePart(blob, pagePart);
                                return blob;
                            })
                            .catch((e) => { console.error('Filter Everything: filter data fetch failed', e); });
                    }
                } else {
                    window.wpcFilterJsonData = pagePart;
                }
            } catch (e) {
                console.error('Filter Everything: filter data JSON parse failed', e);
            }
            return;
        }

        // Legacy responses carry the data as an executable literal
        const scriptEl = doc.querySelector('#wpc-filter-everything-js-before');

        if (scriptEl) {
            let code = scriptEl.textContent;
            (0, eval)(code);
        }
    }

    function wpcIsSearchUrl(){
        const url = new URL(window.location.href);
        return url.searchParams.has('srch');
    }

    return {
        applyJsMode: function ($el, setId) { wpcWhenDataReady(function () { applyJsMode($el, setId); }); },
        compareInputWithRangeList: function (form, applyButtonMode) { wpcWhenDataReady(function () { compareInputWithRangeList(form, applyButtonMode); }); },
        unsetChip: function ($chipElement) { wpcWhenDataReady(function () { unsetChip($chipElement); }); },
        updateWpcFilterJsonData: updateWpcFilterJsonData
    };

    })(); // end of the Apply-button recount engine (wpcApplyEngine)

})(jQuery);