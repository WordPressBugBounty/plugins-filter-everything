/*!
 * Filter Everything common admin 1.9.2
 */
(function($) {
    "use strict";

    $(document).ready(function (){
        let wpcUserAgent = navigator.userAgent.toLowerCase();
        let wpcIsAndroid = wpcUserAgent.indexOf("android") > -1;
        let wpcAllowSearchField = 0;
        if(wpcIsAndroid) {
            wpcAllowSearchField = Infinity;
        }

        // Common JS code
        $(document).on('change', '#mobile_filter_settings', function (e){
            if($(this).val() === 'show_bottom_widget'){
                $('#show_open_close_button').parent('label').addClass('wpc-inactive-settings-field');
                $('.wpc-bottom-widget-compatibility').addClass('wpc-opened');
            }else{
                $('#show_open_close_button').parent('label').removeClass('wpc-inactive-settings-field');
                $('.wpc-bottom-widget-compatibility').removeClass('wpc-opened');
            }
        });

        $(document).on('click', '#use_color_swatches', function (e){
            if ( $(this).is(':checked') ) {
                $('.wpc-color-swatches-taxonomies').addClass('wpc-opened');
            } else {
                $('.wpc-color-swatches-taxonomies').removeClass('wpc-opened');
            }
        });

        $('#wpc_primary_color').wpColorPicker({
            defaultColor: '',
            palettes: [ '#0570e2', '#f44336', '#E91E63', '#007cba', '#65BC7B', '#FFEB3B', '#FFC107', '#FF9800', '#607D8B'],
        });

        $('#wpc_term_color').wpColorPicker({
            defaultColor: '',
            palettes: [ '#0000FF', '#808080', '#008000', '#FF0000', '#FFFF00', '#FFA500', '#00bfff', '#7F00FF', '#FFFFFF'],
        });

        $('.wpc-help-tip, .wpc-icon-help-tip').tipTip({
            'attribute': 'data-tip',
            'fadeIn':    50,
            'fadeOut':   50,
            'delay':     200,
            'keepAlive': true,
            'maxWidth': "220px",
        });

        $( '.wpc-sortable-table' ).sortable({
            items: "tr.pro-version.wpc-sortable-row",
            delay: 150,
            placeholder: "wpc-filter-field-shadow",
            refreshPositions: true,
            cursor: 'move',
            handle: ".wpc-order-sortable-handle-icon",
            axis: 'y',
            update: function( event, ui ) {
                renderTableOrder();
            },

        });

        $("#show_terms_in_content").select2({
            width: '80%',
            placeholder: wpcFiltersAdminCommon.chipsPlaceholder,
            minimumResultsForSearch: wpcAllowSearchField,
            tags: true
        });

        $("#color_swatches_taxonomies").select2({
            width: '80%',
            placeholder: wpcFiltersAdminCommon.colorSwatchesPlaceholder,
            minimumResultsForSearch: wpcAllowSearchField,
            tags: false,
        });

        $('body').on('click', '.wpc-notice-dismiss', function (e){
            e.preventDefault();

            let requestParams      = {};
            requestParams._wpnonce = $(this).data('nonce');
            let dismissAction      = $(this).data('action');

            wp.ajax.post( dismissAction, requestParams )
                .always( function( response ) {
                    // $spinner.removeClass( 'is-active' );
                    var $el = $( '.license-notice' );
                    $el.fadeTo( 100, 0, function() {
                        $el.slideUp( 100, function() {
                            $el.remove();
                        });
                    });
                })
        });

        // on upload button click
        $( document ).on( 'click', '.wpc-upload', function( event ){

            event.preventDefault(); // prevent default link click and page refresh

            const button = $(this)
            const imageId = button.next().next().val();

            const customUploader = wp.media({
                title: 'Insert image', // modal window title
                library : {
                    // uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
                    type : 'image'
                },
                button: {
                    text: 'Use this image' // button label text
                },
                multiple: false
            }).on( 'select', function() { // it also has "open" and "close" events
                const attachment = customUploader.state().get( 'selection' ).first().toJSON();
                button.removeClass( 'button' ).html( '<img src="' + attachment.url + '">'); // add image instead of "Upload Image"
                button.next().show(); // show "Remove image" link
                button.next().next().val( attachment.id ); // Populate the hidden field with image ID
            })

            // already selected images
            customUploader.on( 'open', function() {

                if( imageId ) {
                    const selection = customUploader.state().get( 'selection' )
                    let attachment = wp.media.attachment( imageId );
                    attachment.fetch();
                    selection.add( attachment ? [attachment] : [] );
                }

            })

            customUploader.open()
        });

        // on remove button click
        $( document ).on( 'click', '.wpc-remove', function( event ){
            wpcHideTheRemoveButton( $(this) );
        });

        $( document ).ajaxSuccess( function(e, request, settings) {
            let params = new URLSearchParams( settings.data );
            let action = params.get('action');

            if( action === 'add-tag' ){
                // clear form
                $("#wpc_term_img, #wpc_term_color").val('');
                $(".wpc-remove").hide();
                wpcHideTheRemoveButton( $("a.wpc-remove") );

                $(".wpc-color-picker .wp-picker-clear").trigger('click');
            }
            // else {
            //     console.log( settings.data );
            // }
        });


    }); // End $(document).ready();

    $(document).ready(function($) {
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get('post_type') === 'filter-set' &&
            urlParams.get('page') === 'filters-settings' &&
            urlParams.get('tab') === 'prefixes') {

            const hash = window.location.hash.slice(1);
            if (hash && hash.trim() !== '') {
                let tab = $('a[href="#' + hash + '"]');
                console.log(tab);

                if (tab.length) {
                    tab.click();
                } else {
                    console.warn('Tab not found', hash);
                }
            }
        }
    });

    $(document).on('click', '.wpc-error.is-dismissible > .notice-dismiss', function (e){
            e.preventDefault();

            let $button = $( this );
            let $el = $button.parent('.wpc-error');

            $el.fadeTo( 100, 0, function() {
                $el.slideUp( 100, function() {
                    $el.remove();
                });
            });
            $el.append( $button );
    });

    function renderTableOrder()
    {
        let num = 0;
        $("tr.wpc-sortable-row").each( function ( index, element ) {
            num = (index + 1);
            $(element).find('.wpc-order-td').text(num);
        });
    }

    function wpcHideTheRemoveButton( element ){
        // event.preventDefault();
        const button = element; //$(this);
        button.next().val( '' ); // emptying the hidden field
        button.hide().prev().addClass( 'button' ).html( 'Upload image' ); // replace the image with text
    }

    $(document).on('click', '#wpc-create-xml', function (e){
        e.preventDefault();
        let $btn = $('#wpc-create-xml');
        let xmlCreateLink = $btn.attr('href');
        let errorCreateXmlText = $btn.data('errorCreateXmlText');
        let successCreateXmlText = $btn.data('successCreateXmlText');
        let progressNoticeText = $btn.data('progressNoticeText');
        let dismissNoticeText = $btn.data('dismissNoticeText');

        let errTexts = {
            500: $btn.attr('data-err-500'),
            503: $btn.attr('data-err-503'),
            0:   $btn.attr('data-err-0'),
            404: $btn.attr('data-err-404'),
            genericPrefix: $btn.attr('data-err-generic-prefix') || 'Error'
        };

        let progressInterval = null;

        $('.flrt-notice').remove();
        $.ajax({
            'method': 'GET',
            'url': xmlCreateLink,
            'dataType': 'html',
            beforeSend: function () {
                $btn.addClass('wpc-xml-loader').removeAttr('href');
                $('#wpc-xml-link').addClass('disabled').removeAttr('href');
                progressInterval = wpcXmlLoadProgress();
                setTimeout(() => {
                    wpcAdminNotificationForXMLProgress(progressNoticeText, dismissNoticeText);
                }, 10000);
            },
            complete: function () {},
            success: function (response) {
                clearInterval(progressInterval);
                let response_data = JSON.parse(response);
                $btn.addClass('fill-complete');
                if(response_data.success){
                    if(response_data.url != ''){
                        $btn.text(successCreateXmlText);
                        $('#wpc-xml-link').removeClass('disabled hidden-xml-button').prop('href', response_data.url);
                        $('.flrt-xml-progress-notice').remove();
                    }
                    setTimeout(() => {
                        $btn.removeClass('fill-complete wpc-xml-loader');
                    }, 200);
                    setTimeout(() => {
                        $btn.text(response_data.update_text);
                    }, 2000);
                } else {
                    $.each(response_data.data, function (index, error) {
                        $('.wp-header-end').after(error.message);
                    });
                    $btn.text(errorCreateXmlText).removeClass('fill-complete wpc-xml-loader');
                }
                $btn.prop('href', xmlCreateLink);
            },
            error: function (xhr, status, error) {
                clearInterval(progressInterval);
                $btn.removeClass('fill-complete wpc-xml-loader').text(errorCreateXmlText).prop('href', xmlCreateLink);
                $('.flrt-xml-progress-notice').remove();

                let errorMessage = '';
                if (xhr.status === 500 && errTexts[500]) {
                    errorMessage = errTexts[500];
                } else if (xhr.status === 503 && errTexts[503]) {
                    errorMessage = errTexts[503];
                } else if (xhr.status === 0 && errTexts[0]) {
                    errorMessage = errTexts[0];
                } else if (xhr.status === 404 && errTexts[404]) {
                    errorMessage = errTexts[404];
                } else {
                    const prefix = errTexts.genericPrefix || 'Error';
                    errorMessage = `${prefix} ${xhr.status}: ${error}`;
                }

                wpcAdminNotificationTemplate(errorMessage, dismissNoticeText, 'notice-error');
            }
        });
    });

    window.addEventListener("beforeunload", function (event) {
        if($('#wpc-create-xml').hasClass('wpc-xml-loader')) {
            event.preventDefault();
        }
    });

    function wpcXmlAnimation(keyframe){
        const style = document.createElement('style');
        style.textContent = `.button.wpc-xml-loader{animation: ${keyframe} 2.2s ease-in-out forwards;}`;
        document.head.appendChild(style);
    }

    function wpcXmlLoadProgress(){
        let xmlLoadProgressLink = $('#wpc-create-xml').data('xmlProgressUrl');
        const pollIntervalMs = 5000;
        wpcXmlAnimation('fillTo10');
        setInterval(() => {
            if($('#wpc-create-xml').hasClass('wpc-xml-loader')){
                $.ajax({
                    'method': 'GET',
                    'url': xmlLoadProgressLink,
                    beforeSend: function () {
                    },
                    complete: function () {},
                    success: function (response) {
                        if(response.success){
                            wpcXmlAnimation('fillTo' + response.progress);
                        }
                    },
                })
            }
        }, pollIntervalMs);
    }

    function wpcAdminNotificationForXMLProgress(progressNoticeText, dismissNoticeText){
        if($('#wpc-create-xml').hasClass('wpc-xml-loader')){
            wpcAdminNotificationTemplate(progressNoticeText, dismissNoticeText, 'notice-warning flrt-notice flrt-xml-progress-notice');
        }
    }
    function wpcAdminNotificationTemplate(message, closeText = 'Dismiss this notice.', className = ''){
        const errorMessage = `<div class="notice is-dismissible flrt-notice ${className}"><p>${message}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">${closeText}</span></button></div>`;
        $('.wp-header-end').after(errorMessage);
    }


    $(document).on('click', '#export_all', function (e){
        if($('#export_all').prop('checked')){
            $('form.form_wpc_export_settings input[type=checkbox]').prop('checked', 'checked');
        }else{
            $('form.form_wpc_export_settings input[type=checkbox]').prop('checked', '');
        }
    });

    $(document).on('click', 'form.form_wpc_export_settings input[type=checkbox]', function (e){
        let checkboxes = $('form.form_wpc_export_settings input[type=checkbox]');
        let checkedCheckboxes = $('form.form_wpc_export_settings input[type=checkbox]:checked');
        let newCheckboxes = checkboxes.filter(function( index ) {
            return $( this ).attr( "id" ) !== "export_all";
        });
        checkedCheckboxes = checkedCheckboxes.filter(function( index ) {
            return $( this ).attr( "id" ) !== "export_all";
        });
        if(newCheckboxes.length === checkedCheckboxes.length){
            $('#export_all').prop('checked', 'checked');
        }else{
            $('#export_all').prop('checked', '');
        }
    });

    $(document).on('click', '#import_all', function (e){
        if($('#import_all').prop('checked')){
            $('form.form_wpc_import_settings input[type=checkbox]').prop('checked', 'checked');
        }else{
            $('form.form_wpc_import_settings input[type=checkbox]').prop('checked', '');
        }
    });

    $(document).on('click', 'form.form_wpc_import_settings input[type=checkbox]', function (e){
        let checkboxes = $('form.form_wpc_import_settings input[type=checkbox]');
        let checkedCheckboxes = $('form.form_wpc_import_settings input[type=checkbox]:checked');
        let newCheckboxes = checkboxes.filter(function( index ) {
            return $( this ).attr( "id" ) !== "import_all";
        });
        checkedCheckboxes = checkedCheckboxes.filter(function( index ) {
            return $( this ).attr( "id" ) !== "import_all";
        });
        if(newCheckboxes.length === checkedCheckboxes.length){
            $('#import_all').prop('checked', 'checked');
        }else{
            $('#import_all').prop('checked', '');
        }
    });

    $(document).on('change', 'input[name=wpc_filter_import_export\\[import_file\\]]', function (e){
        const file = e.target.files[0];
        if (!file) return;

        const fileName = file.name;
        let import_all_checkbox = true;

        if (/filters/i.test(fileName)) {
            $('#import_filter_set').prop('checked', 'checked');
        }else{
            import_all_checkbox = false;
            $('#import_filter_set').prop('checked', '');
        }

        if (/settings/i.test(fileName)) {
            $('#import_options').prop('checked', 'checked');
        }else{
            import_all_checkbox = false;
            $('#import_options').prop('checked', '');
        }

        if (/seo/i.test(fileName)) {
            $('#import_filter_seo_rule').prop('checked', 'checked');
        }else{
            import_all_checkbox = false;
            $('#import_filter_seo_rule').prop('checked', '');
        }

        if(import_all_checkbox){
            $('#import_all').prop('checked', 'checked');
        }else {
            $('#import_all').prop('checked', '');
        }
    });

    $(document).on('submit', 'form.form_wpc_import_settings', function(event) {
        $('.nav-tab-wrapper').before('<div id="wpc-loader"></div>');
    });

    $(document).on('click', 'a', function (e) {
        const href = $(this).attr('href');
        if (!href) return;

        try {
            const url = new URL(href, window.location.origin);
            const params = url.searchParams;
            if (params.get('post_type') === 'filter-set' && params.get('page') === 'flrt-pro') {
                e.preventDefault();
                wpcGetProVersionPopup();
            }
        } catch (err) {

        }
    });

    $(document).on( 'click', '.free-version .wpc-field-sortable-handle', function (){
        wpcGetProVersionPopup();
    });
    $(document).on('click', '#flrt-close-modal-btn', function (){
        closeProVersionPopup();
    });

    $(document).on('click', '#flrt-pro-modal-overlay', function (e) {
        if (e.target === this) {
            closeProVersionPopup();
        }
    });

    function closeProVersionPopup(){
        $('#flrt-pro-modal-overlay').css('display', 'none');
        
    }

    window.wpcGetProVersionPopup = function () {
        $('#flrt-pro-modal-overlay').css('display', 'flex');
    };

    $(document).on('click', '.wpc-create-auto-filter', function (e) {
        if(!$(this).hasClass('disabled')){
            $(this).addClass('wpc-button-animation-loader');
        }
    });

    $(document).on('click', '.wpc-seo-setting-head', function (el) {
        if($(this).hasClass('wpc-opened')){
            $(this).removeClass('wpc-opened');
            $(this).next('.wpc-filter-body').removeClass('wpc-opened');
        }else{
            $(this).addClass('wpc-opened');
            $(this).next('.wpc-filter-body').addClass('wpc-opened');
        }
    });

    $('#wpc-seo-settings .nav-tab').on('click', function(e){
        e.preventDefault();
        if(!$(this).hasClass('disabled')){
            $('#wpc-seo-settings .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('#wpc-seo-settings .tab-content').hide();
            $($(this).attr('href')).show();
        }
    });

    const activeTabHref = $('#wpc-seo-settings .nav-tab-active').attr('href');
    if(activeTabHref) {
        $(activeTabHref).show();
    }


})(jQuery);