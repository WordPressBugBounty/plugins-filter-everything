<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Frontend CSS that depends on plugin settings (colors, container height,
 * swatch sizes, experimental options, Divi tweaks, custom CSS).
 *
 * Extracted from Plugin::inlineFrontCss() / Plugin::dynamicFrontCss() on
 * 2026-09-14; the CSS output is unchanged. Plugin keeps those two methods as
 * delegates so existing add_action/remove_action callbacks still work.
 *
 * @since 1.9.7
 */
class DynamicCss
{
    /**
     * Prints the small always-on inline CSS block (wp_head).
     */
    public function printInlineCss()
    {
        $inline = '<style type="text/css" id="filter-everything-inline-css">.wpc-orderby-select{width:100%}.wpc-filters-open-button-container{display:none}.wpc-debug-message{padding:16px;font-size:14px;border:1px dashed #ccc;margin-bottom:20px}.wpc-debug-title{visibility:hidden}.wpc-button-inner,.wpc-chip-content{display:flex;align-items:center}.wpc-icon-html-wrapper{position:relative;margin-right:10px;top:2px}.wpc-icon-html-wrapper span{display:block;height:1px;width:18px;border-radius:3px;background:#2c2d33;margin-bottom:4px;position:relative}span.wpc-icon-line-1:after,span.wpc-icon-line-2:after,span.wpc-icon-line-3:after{content:"";display:block;width:3px;height:3px;border:1px solid #2c2d33;background-color:#fff;position:absolute;top:-2px;box-sizing:content-box}span.wpc-icon-line-3:after{border-radius:50%;left:2px}span.wpc-icon-line-1:after{border-radius:50%;left:5px}span.wpc-icon-line-2:after{border-radius:50%;left:12px}body .wpc-filters-open-button-container a.wpc-filters-open-widget,body .wpc-filters-open-button-container a.wpc-open-close-filters-button{display:inline-block;text-align:left;border:1px solid #2c2d33;border-radius:2px;line-height:1.5;padding:7px 12px;background-color:transparent;color:#2c2d33;box-sizing:border-box;text-decoration:none!important;font-weight:400;transition:none;position:relative}@media screen and (max-width:768px){.wpc_show_bottom_widget .wpc-filters-open-button-container,.wpc_show_open_close_button .wpc-filters-open-button-container{display:block}.wpc_show_bottom_widget .wpc-filters-open-button-container{margin-top:1em;margin-bottom:1em}}</style>'."\r\n";
        echo $inline;
    }

    /**
     * Builds the settings-dependent CSS, caches it as
     * uploads/cache/filter-everything/<md5>.css and enqueues it (wp_print_styles).
     * Does nothing when the page has no related Filter Set.
     */
    public function enqueueDynamicCss()
    {
        // Do not include plugin CSS if there are no Filter Sets on the page
        $sets = Container::instance()->getFilterContext()->get( 'wpc_page_related_set_ids', [] );
        if( empty( $sets ) ) {
            return false;
        }

        $maxHeight        = flrt_get_option( 'container_height' );
        $color            = flrt_get_option( 'primary_color', flrt_default_theme_color() );
        $move_to_top      = flrt_get_option( 'try_move_to_top_sidebar' );
        $wpc_mobile_width = flrt_get_mobile_width();

        // Experimental Options
        $custom_css     = flrt_get_experimental_option('custom_css');
        $use_loader     = flrt_get_experimental_option('use_loader');
        $dark_overlay   = flrt_get_experimental_option('dark_overlay');
        $styled_inputs  = flrt_get_experimental_option('styled_inputs');
        $use_select2    = flrt_get_experimental_option('select2_dropdowns');
        $rounded_swatch = flrt_get_experimental_option('rounded_swatches');
        $contrastColor  = false;

        $swatches_measurements = [ 'width' => 32, 'height' => 32 ];
        if( $rounded_swatch ) {
            $swatches_measurements = [ 'width' => 32, 'height' => 32 ];
        }

        $swatches       = apply_filters( 'wpc_swatches_width_height', $swatches_measurements );
        $brands         = apply_filters( 'wpc_brands_width_height', [ 'width' => 70, 'height' => 40 ] );

        $css = '';
        $css .= '@media screen and (min-width:'.($wpc_mobile_width+1).'px){.wpc_show_bottom_widget .wpc-filters-widget-content{height:auto!important}body.wpc_show_open_close_button .wpc-filters-widget-content.wpc-closed,body.wpc_show_open_close_button .wpc-filters-widget-content.wpc-opened,body.wpc_show_open_close_button .wpc-filters-widget-content:not(.wpc-opened){display:block!important}}@media screen and (min-width:'.$wpc_mobile_width.'px){.wpc-custom-selected-terms{clear:both;width:100%}.wpc-custom-selected-terms ul.wpc-filter-chips-list{display:flex;overflow-x:auto;overflow-y:clip;padding-left:0}.wpc-filters-main-wrap .wpc-custom-selected-terms ul.wpc-filter-chips-list{display:block;overflow:visible}html.is-active .wpc-filters-overlay{top:0;opacity:.3;background:#fff}.wpc-filters-main-wrap input.wpc-label-input+label:hover{border:1px solid rgba(0,0,0,.25);border-radius:5px}.wpc-filters-main-wrap input.wpc-label-input+label:hover span.wpc-filter-label-wrapper{color:#333;background-color:rgba(0,0,0,.25)}.wpc-filters-main-wrap .wpc-filters-labels li.wpc-term-item input+label:hover a{color:#333}.theme-storefront #primary .storefront-sorting .wpc-custom-selected-terms{font-size:inherit}.theme-storefront #primary .wpc-custom-selected-terms{font-size:.875em}}@media screen and (max-width:'.$wpc_mobile_width.'px){.wpc-filters-labels li.wpc-term-item label:hover .wpc-term-swatch-wrapper:after,.wpc-filters-labels li.wpc-term-item label:hover .wpc-term-swatch-wrapper:before{display:none;}.wpc_show_bottom_widget .wpc-filters-widget-top-container,.wpc_show_open_close_button .wpc-filters-widget-top-container{text-align:center}.wpc_show_bottom_widget .wpc-filters-widget-top-container{position:sticky;top:0;z-index:99999;border-bottom:1px solid #f7f7f7}.wpc-custom-selected-terms:not(.wpc-show-on-mobile),.wpc-edit-filter-set,.wpc_show_bottom_widget .widget_wpc_selected_filters_widget,.wpc_show_bottom_widget .wpc-filters-widget-content .wpc-filter-set-widget-title,.wpc_show_bottom_widget .wpc-filters-main-wrap .widget-title,.wpc_show_bottom_widget .wpc-filters-widget-wrapper .wpc-filter-layout-submit-button,.wpc_show_bottom_widget .wpc-posts-found,body.wpc_show_bottom_widget .wpc-open-close-filters-button,body.wpc_show_open_close_button .wpc-filters-widget-content:not(.wpc-opened),.widget_wpc_chips_widget .widget-title:not(.wpc-show-on-mobile-widget-title), .widget_wpc_chips_widget .widgettitle:not(.wpc-show-on-mobile-widget-title) {display:none}.wpc_show_bottom_widget .wpc-filters-widget-top-container:not(.wpc-show-on-desktop),.wpc_show_bottom_widget .wpc-spinner.is-active,.wpc_show_bottom_widget .wpc-widget-close-container,html.is-active body:not(.wpc_show_bottom_widget) .wpc-spinner{display:block}body .wpc-filters-main-wrap li.wpc-term-item{padding:2px 0}.wpc-chip-empty{width:0;display:list-item;visibility:hidden;margin-right:0!important}.wpc-overlay-visible #secondary{z-index:auto}html.is-active:not(.wpc-overlay-visible) .wpc-filters-overlay{top:0;opacity:.2;background:#fff}.wpc-custom-selected-terms.wpc-show-on-mobile ul.wpc-filter-chips-list{display:flex;overflow-x:auto;padding-left:0}html.is-active body:not(.wpc_show_bottom_widget) .wpc-filters-overlay{top:0;opacity:.3;background:#fff}body.wpc_show_bottom_widget .wpc-filters-widget-content.wpc-closed,body.wpc_show_bottom_widget .wpc-filters-widget-content.wpc-opened,body.wpc_show_bottom_widget .wpc-filters-widget-content:not(.wpc-opened){display:block!important}.wpc-open-close-filters-button{display:block;margin-bottom:20px}.wpc-overlay-visible body,html.wpc-overlay-visible{overflow:hidden!important}.wpc_show_bottom_widget .widget_wpc_filters_widget,.wpc_show_bottom_widget .wpc-filters-main-wrap{padding:0!important;margin:0!important}.wpc_show_bottom_widget .wpc-filters-range-column{width:48%;max-width:none}.wpc_show_bottom_widget .wpc-filters-toolbar{display:flex;margin:1em 0}.wpc_show_bottom_widget .wpc-inner-widget-chips-wrapper{display:block;padding-left:20px;padding-right:20px}.wpc_show_bottom_widget .wpc-filters-main-wrap .widget-title.wpc-filter-title{display:flex}.wpc_show_bottom_widget .wpc-inner-widget-chips-wrapper .wpc-filter-chips-list,.wpc_show_open_close_button .wpc-inner-widget-chips-wrapper .wpc-filter-chips-list{display:flex;-webkit-box-pack:start;place-content:center flex-start;overflow-x:auto;padding-top:5px;padding-bottom:5px;margin-left:0;padding-left:0}.wpc-overlay-visible .wpc_show_bottom_widget .wpc-filters-overlay{top:0;opacity:.4}.wpc_show_bottom_widget .wpc-filters-main-wrap .wpc-spinner.is-active+.wpc-filters-widget-content .wpc-filters-scroll-container .wpc-filters-widget-wrapper{opacity:.6;pointer-events:none}.wpc_show_bottom_widget .wpc-filters-open-button-container{margin-top:1em;margin-bottom:1em}.wpc_show_bottom_widget .wpc-filters-widget-content{position:fixed;bottom:0;right:0;left:0;top:5%;z-index:999999;padding:0;background-color:#fff;margin:0;box-sizing:border-box;border-radius:7px 7px 0 0;transition:transform .25s;transform:translate3d(0,120%,0);-webkit-overflow-scrolling:touch;height:auto}.wpc_show_bottom_widget .wpc-filters-widget-containers-wrapper{padding:0;margin:0;overflow-y:scroll;box-sizing:border-box;position:fixed;top:56px;left:0;right:0;bottom:0}.wpc_show_bottom_widget .wpc-filters-widget-content.wpc-filters-widget-opened{transform:translate3d(0,0,0)}.theme-twentyfourteen .wpc_show_bottom_widget .wpc-filters-widget-content,.theme-twentyfourteen.wpc_show_bottom_widget .wpc-filters-scroll-container{background-color:#000}.wpc_show_bottom_widget .wpc-filters-section:not(.wpc-filter-post_meta_num):not(.wpc-filter-tax_numeric) .wpc-filter-content ul.wpc-filters-ul-list,.wpc_show_open_close_button .wpc-filters-section:not(.wpc-filter-post_meta_num):not(.wpc-filter-tax_numeric) .wpc-filter-content ul.wpc-filters-ul-list{max-height:none}.wpc_show_bottom_widget .wpc-filters-scroll-container{background:#fff;min-height:100%}.wpc_show_bottom_widget .wpc-filters-widget-wrapper{padding:20px 20px 15px}.wpc-filter-everything-dropdown .select2-search--dropdown .select2-search__field,.wpc-sorting-form select,.wpc_show_bottom_widget .wpc-filters-main-wrap input[type=number],.wpc_show_bottom_widget .wpc-filters-main-wrap input[type=text],.wpc_show_bottom_widget .wpc-filters-main-wrap select,.wpc_show_bottom_widget .wpc-filters-main-wrap textarea,.wpc_show_bottom_widget .wpc-search-field,.wpc_show_open_close_button .wpc-search-field,.wpc_show_open_close_button .wpc-filter-search-field{font-size:16px}.wpc-filter-layout-dropdown .select2-container .select2-selection--single,.wpc-sorting-form .select2-container .select2-selection--single{height:auto;padding:6px}.wpc_show_bottom_widget .wpc-filters-section:not(.wpc-filter-post_meta_num):not(.wpc-filter-tax_numeric) .wpc-filter-content ul.wpc-filters-ul-list{overflow-y:visible}.theme-twentyeleven #primary,.theme-twentyeleven #secondary{margin-left:0;margin-right:0;clear:both;float:none}#main>.fusion-row{max-width:100%}.wpc_show_bottom_widget .wpc-filters-open-button-container,.wpc_show_bottom_widget .wpc-filters-widget-controls-container,.wpc_show_bottom_widget .wpc-filters-widget-top-container,.wpc_show_open_close_button .wpc-filters-open-button-container{display:block}}'."\r\n";
        $css .= '.wpc-preload-img{display:none;}';
        // Number of items visible by default in More/Less filters

        $css .= 'li.wpc-term-item label span.wpc-term-swatch,.wpc-term-swatch-wrapper{width:'.$swatches['width'].'px;min-width:'.$swatches['width'].'px;';

        if ( $rounded_swatch ) {
            $css .= 'border-radius:'.$swatches['height'].'px;';
        }

        $css .= 'height:'.$swatches['height'].'px;}'."\r\n";
        $css .= '.wpc-term-swatch-wrapper:after{width:'.($swatches['width']/2.5).'px;height:'.($swatches['width']/5).'px;left:'.($swatches['width']/3.5).'px;top:'.($swatches['height']/3.5).'px;}';
        $css .= '.wpc-term-image-wrapper{width:'.$brands['width'].'px;min-width:'.$brands['width'].'px;height:'.$brands['height'].'px;}';

        if( $maxHeight ){
            $css .= '.wpc-filters-section:not(.wpc-filter-more-less):not(.wpc-filter-post_meta_num):not(.wpc-filter-tax_numeric):not(.wpc-filter-layout-dropdown):not(.wpc-filter-terms-count-0) .wpc-filter-content:not(.wpc-filter-has-hierarchy) ul.wpc-filters-ul-list{
                        max-height: '.$maxHeight.'px;
                        overflow-y: auto;
                }'."\r\n";
        }

        if( $color ){
            $contrastColor = flrt_get_contrast_color($color);
            $css .= '.wpc-filters-range-inputs .ui-slider-horizontal .ui-slider-range{
                        background-color: '.$color.';
                    }
                '."\r\n";

            $css .= '.wpc-spinner:after {
                        border-top-color: '.$color.';
                    }'."\r\n";

            $css .= '.theme-Avada .wpc-filter-product_visibility .star-rating:before,
                .wpc-filter-product_visibility .star-rating span:before{
                    color: '.$color.';
                }'."\r\n";

            $css .= 'body .wpc-filters-main-wrap input.wpc-label-input:checked+label span.wpc-filter-label-wrapper{
                        background-color: '.$color.';
                }'."\r\n";

            $css .= 'body .wpc-filters-main-wrap input.wpc-label-input:checked+label{
                        border-color: '.$color.';
                }'."\r\n";

            // Disabled label
            $css .= 'body .wpc-filters-main-wrap .wpc-term-disabled input.wpc-label-input:checked+label span.wpc-filter-label-wrapper{
                        background-color: #d8d8d8;
                }'."\r\n";

            $css .= 'body .wpc-filters-main-wrap .wpc-term-disabled input.wpc-label-input:checked+label{
                        border-color: #d8d8d8;
                }'."\r\n";

            $css .= 'body .wpc-filters-main-wrap .wpc-term-disabled input.wpc-label-input+label:hover{
                        border-color: #d8d8d8;
                }'."\r\n";

            $css .= 'body .wpc-filters-main-wrap .wpc-term-disabled input.wpc-label-input:checked+label span.wpc-filter-label-wrapper,
                body .wpc-filters-main-wrap .wpc-filters-labels li.wpc-term-item.wpc-term-disabled input:checked+label a,
                body .wpc-filters-main-wrap .wpc-filters-labels li.wpc-term-item.wpc-term-disabled input:checked+label span
                {
                        color: #333333;
                }'."\r\n";
            // End of disabled label

            $css .= 'body .wpc-filters-main-wrap input.wpc-label-input:checked+label span.wpc-filter-label-wrapper,
                body .wpc-filters-main-wrap .wpc-filters-labels li.wpc-term-item input:checked+label a{
                        color: '.$contrastColor.';
                }'."\r\n";

            $css .= 'body .wpc-filter-chips-list li.wpc-filter-chip:not(.wpc-chip-reset-all) a,
                body .wpc-filter-chips-list li.wpc-filter-chip:not(.wpc-chip-reset-all) span[data-wpc-span-link],
                body .wpc-filter-chips-list li.wpc-filter-chip:not(.wpc-chip-reset-all) span.wpc-apply-button-chip{
                    border-color: '.$color.';
                }'."\r\n";

            $css .= 'body .wpc-filters-main-wrap .wpc-filters-widget-controls-container a.wpc-filters-apply-button,
                body .wpc-filters-main-wrap a.wpc-filters-submit-button{
                    border-color: '.$color.';
                    background-color: '.$color.';
                    color: '.$contrastColor.';
                }'."\r\n";

            $css .= 'body .wpc-filter-chips-list li.wpc-filter-chip a:hover,
                body .wpc-filter-chips-list li.wpc-filter-chip span[data-wpc-span-link]:hover,
                body .wpc-filter-chips-list li.wpc-filter-chip span.wpc-apply-button-chip:hover{
                    opacity: 0.9;
                }'."\r\n";

            $css .= 'body .wpc-filter-chips-list li.wpc-filter-chip a:active,
                body .wpc-filter-chips-list li.wpc-filter-chip span[data-wpc-span-link]:active,
                body .wpc-filter-chips-list li.wpc-filter-chip span.wpc-apply-button-chip:active{
                    opacity: 0.75;
                }'."\r\n";

            $css .= '.star-rating span,
                .star-rating span:before{
                    color: '.$color.';
                }'."\r\n";

            $css .= 'body a.wpc-filters-open-widget:active, a.wpc-filters-open-widget:active, 
                .wpc-filters-open-widget:active{
                    border-color: '.$color.';
                    background-color: '.$color.';
                    color: '.$contrastColor.';
                }'."\r\n";

            $css .= 'a.wpc-filters-open-widget:active span.wpc-icon-line-1:after,
                a.wpc-filters-open-widget:active span.wpc-icon-line-2:after,
                a.wpc-filters-open-widget:active span.wpc-icon-line-3:after{
                    background-color: '.$color.';
                    border-color: '.$contrastColor.';
                }'."\r\n";

            $css .= 'a.wpc-filters-open-widget:active .wpc-icon-html-wrapper span{
                    background-color: '.$contrastColor.';
                }'."\r\n";

            //$css .= '@media screen and (min-width: '.$wpc_mobile_width.'px) {'."\r\n";
            $css .= 'body .wpc-filters-main-wrap input.wpc-label-input+label:hover span.wpc-filter-label-wrapper{
                        color: '.$contrastColor.';
                        background-color: '.$color.';
                    }'."\r\n";

            $css .= 'body .wpc-filters-main-wrap .wpc-filters-labels li.wpc-term-item input+label:hover a{
                        color: '.$contrastColor.';
                    }'."\r\n";
            $css .= 'body .wpc-filters-main-wrap input.wpc-label-input+label:hover{
                        border-color: '.$color.';
                    }'."\r\n";


            $css .= '#ui-datepicker-div.wpc-filter-datepicker .ui-state-active, 
            #ui-datepicker-div.ui-widget-content.wpc-filter-datepicker .ui-state-active, 
            #ui-datepicker-div.wpc-filter-datepicker .ui-widget-header .ui-state-active{
                    border-color: '.$color.';
                    background: '.$color.';
                    opacity: 0.95;
            }'."\r\n";

            $css .= '#ui-datepicker-div.wpc-filter-datepicker .ui-state-hover, 
            #ui-datepicker-div.ui-widget-content.wpc-filter-datepicker .ui-state-hover, 
            #ui-datepicker-div.wpc-filter-datepicker .ui-widget-header .ui-state-hover, 
            #ui-datepicker-div.wpc-filter-datepicker .ui-state-focus, 
            #ui-datepicker-div.ui-widget-content.wpc-filter-datepicker .ui-state-focus, 
            #ui-datepicker-div.wpc-filter-datepicker .ui-widget-header .ui-state-focus{
                border-color: '.$color.';
                background: '.$color.';
                opacity: 0.6;
            }';

            $css .= '#ui-datepicker-div.wpc-filter-datepicker .ui-datepicker-close.ui-state-default{
                background: '.$color.';
                color: '.$contrastColor.';
            }'."\r\n";

            //$css .= '}'."\r\n";
            $css .= '.flrt-star-label svg{
                    stroke: '.$color.';
            }'."\r\n";

            $css .= '.flrt-star-label-hover svg, .wpc-chip-stars svg{
                    fill: '.$color.';
            }'."\r\n";

            $css .= '.wpc-filter-label-stars-wrapper{
                   padding: 4px 5px !important;
            }'."\r\n";

            $css .= '.wpc-filter-label-stars-wrapper .flrt-star-label svg{
                    height: 17px;
                    width: 17px;
            }'."\r\n";

            $css .= 'body .wpc-filters-main-wrap input.wpc-label-input:checked+label span.wpc-filter-label-stars-wrapper .flrt-star-label svg, 
            span.wpc-filter-label-stars-wrapper:hover .flrt-star-label svg{
                        fill: ' . flrt_get_contrast_color($color) . ';
                }'."\r\n";
        }
        if( $styled_inputs ){
            $styled_color   = $color ? $color : '#0570e2';
            $contrastColor  = $contrastColor ? $contrastColor : flrt_get_contrast_color($styled_color);
            $hoverColor     = flrt_add_color_opacity($color);
            $no_hex_color   = substr( $color, 1, 6 );
            $css .= '.wpc-filters-main-wrap input[type=checkbox],
                        .wpc-filters-main-wrap input[type=radio]{
                            -webkit-appearance: none;
                            -moz-appearance: none;
                            position: relative;
                            width: 20px;
                            height: 20px;
                            border: 1px solid #c9d1e0;
                            background: #ffffff;
                            border-radius: 5px;
                            min-width: 20px;
                        }
                        i.wpc-toggle-children-list:after,
                        i.wpc-toggle-children-list:before{
                            background-color: #b8bcc8;
                        }
                        i.wpc-toggle-children-list:hover:after,
                        i.wpc-toggle-children-list:hover:before{
                            background-color: '.$color.';
                        }
                        .wpc-filters-widget-content input[type=email], 
                        .wpc-filters-widget-content input[type=number], 
                        .wpc-filters-widget-content input[type=password], 
                        .wpc-filters-widget-content input[type=search], 
                        .wpc-filters-widget-content input[type=tel], 
                        .wpc-filters-widget-content input[type=text], 
                        .wpc-filters-widget-content input[type=url]{
                            height: 44px;
                        }
                        .wpc-filters-widget-content .wpc-filters-section input[type="number"],
                        .wpc-filters-widget-content .wpc-filters-section input[type="text"]{
                            border: 1px solid #ccd0dc;
                            border-radius: 6px;
                            background: transparent;
                            box-shadow: none; 
                            padding: 8px 16px;
                        }
                        .wpc-filters-main-wrap input[type=checkbox]:after {
							content: "";
							opacity: 0;
							display: block;
							left: 3px;
							top: 3px;
							position: absolute;
							width: 12px;
							height: 12px;
							border: navajowhite;
							box-sizing: content-box;
							background-color:  '.$styled_color.';
							border-radius: 2px;
                        }
                        .wpc-filters-main-wrap input[type=radio]:after {
                            content: "";
                            opacity: 0;
                            display: block;
                            left: 3px;
                            top: 3px;
                            position: absolute;
                            width: 12px;
                            height: 12px;
                            border-radius: 50%;
                            background: '.$styled_color.';
                            box-sizing: content-box;
                        }
                        .wpc-filters-main-wrap input[type=radio]:checked,
                        .wpc-filters-main-wrap input[type=checkbox]:checked {
                            border-color: '.$styled_color.';
                        }
                        .wpc-filters-main-wrap .wpc-radio-item.wpc-term-disabled input[type=radio],
                        .wpc-filters-main-wrap .wpc-checkbox-item.wpc-term-disabled > div > input[type=checkbox],
                        .wpc-filters-main-wrap .wpc-checkbox-item.wpc-term-disabled > div > input[type=checkbox]:after,
                        .wpc-filters-main-wrap .wpc-term-count-0:not(.wpc-has-not-empty-children) input[type=checkbox]:after,
                        .wpc-filters-main-wrap .wpc-term-count-0:not(.wpc-has-not-empty-children) input[type=checkbox],
                        .wpc-filters-main-wrap .wpc-term-count-0:not(.wpc-has-not-empty-children) input[type=radio],
                        .wpc-term-swatch-no-image{
                            border-color: #d8d8d8;
                        }
						.wpc-filters-main-wrap .wpc-term-count-0:not(.wpc-has-not-empty-children) input[type=checkbox]:after,
						.wpc-filters-main-wrap .wpc-checkbox-item.wpc-term-disabled > div > input[type=checkbox]:after {
							background-color: #d8d8d8;
						}
                        .wpc-filters-main-wrap .wpc-radio-item.wpc-term-disabled input[type=radio]:after,
                        .wpc-filters-main-wrap .wpc-term-count-0:not(.wpc-has-not-empty-children) input[type=radio]:after{
                            background-color: #d8d8d8;
                        }
                        .wpc-filters-main-wrap input[type=radio]:checked:after,
                        .wpc-filters-main-wrap input[type=checkbox]:checked:after {
                            opacity: 1;
                        }
                        .wpc-filters-main-wrap input[type=radio] {
                            border-radius: 50%;
                        }
                        .wpc-filters-widget-content .wpc-filters-section .wpc-filter-search-wrapper .wpc-filter-search-field {
                            padding: 8px 16px 8px 48px;
                        }
                        .wpc-filters-widget-content .wpc-filters-date-range-wrapper input[type="text"]{
                            padding-right: 48px;
                            background-image: url("data:image/svg+xml,%3Csvg width=\'24\' height=\'24\' viewBox=\'0 0 16 16\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Crect x=\'2\' y=\'4\' width=\'12\' height=\'10\' rx=\'1.33333\' stroke=\'%23b8bcc8\' stroke-width=\'1.33333\'/%3E%3Cpath d=\'M2.66699 7.3335H13.3337\' stroke=\'%23b8bcc8\' stroke-width=\'1.33333\' stroke-linecap=\'round\'/%3E%3Cpath d=\'M6 10.6667H10\' stroke=\'%23b8bcc8\' stroke-width=\'1.33333\' stroke-linecap=\'round\'/%3E%3Cpath d=\'M5.33301 2L5.33301 4.66667\' stroke=\'%23b8bcc8\' stroke-width=\'1.33333\' stroke-linecap=\'round\'/%3E%3Cpath d=\'M10.667 2L10.667 4.66667\' stroke=\'%23b8bcc8\' stroke-width=\'1.33333\' stroke-linecap=\'round\'/%3E%3C/svg%3E%0A");
                            background-repeat: no-repeat;
                            background-position: right 16px bottom 50%;
                            background-size: 16px;
                        }     
                        .wpc-filters-widget-content .wpc-filters-date-range-wrapper input[type="text"]:focus,
                        .wpc-filters-widget-content .wpc-filters-date-range-wrapper input[type="text"]:hover{
                            background-image: url("data:image/svg+xml,%3Csvg width=\'24\' height=\'24\' viewBox=\'0 0 16 16\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Crect x=\'2\' y=\'4\' width=\'12\' height=\'10\' rx=\'1.33333\' stroke=\'%23'.$no_hex_color.'\' stroke-width=\'1.33333\'/%3E%3Cpath d=\'M2.66699 7.3335H13.3337\' stroke=\'%23'.$no_hex_color.'\' stroke-width=\'1.33333\' stroke-linecap=\'round\'/%3E%3Cpath d=\'M6 10.6667H10\' stroke=\'%23b8bcc8\' stroke-width=\'1.33333\' stroke-linecap=\'round\'/%3E%3Cpath d=\'M5.33301 2L5.33301 4.66667\' stroke=\'%23'.$no_hex_color.'\' stroke-width=\'1.33333\' stroke-linecap=\'round\'/%3E%3Cpath d=\'M10.667 2L10.667 4.66667\' stroke=\'%23'.$no_hex_color.'\' stroke-width=\'1.33333\' stroke-linecap=\'round\'/%3E%3C/svg%3E%0A");
                        }
                        .wpc-filter-layout-dropdown .select2-container--default .select2-selection--single .select2-selection__arrow b, 
                        .wpc-sorting-form .select2-container--default .select2-selection--single .select2-selection__arrow b{
                            border-left: 1px solid #b8bcc8;
                            border-top: 1px solid #b8bcc8;
                        }
                        .wpc-filter-layout-dropdown .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b, 
                        .wpc-sorting-form .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b{
                            border-left: 1px solid #b8bcc8;
                            border-top: 1px solid #b8bcc8;
                        }
                        .wpc-filter-collapsible .wpc-filter-title .wpc-open-icon, 
                        .wpc-filter-collapsible-reverse.wpc-filter-collapsible.wpc-closed .wpc-filter-title .wpc-open-icon, 
                        .wpc-filter-collapsible.wpc-closed .wpc-filter-title .wpc-open-icon, 
                        .wpc-filter-has-selected.wpc-closed .wpc-filter-title .wpc-open-icon{
                            border-left: 1px solid #b8bcc8;
                            border-top: 1px solid #b8bcc8;
                        }
                        .wpc-sorting-form .select2-container--default .select2-selection--single:hover,
                        .wpc-filters-widget-content input[type=email]:hover, 
                        .wpc-filters-widget-content input[type=number]:hover, 
                        .wpc-filters-widget-content input[type=password]:hover, 
                        .wpc-filters-widget-content input[type=search]:hover, 
                        .wpc-filters-widget-content input[type=tel]:hover, 
                        .wpc-filters-widget-content input[type=text]:hover, 
                        .wpc-filters-widget-content input[type=url]:hover{
                            border-color: '. $hoverColor.';
                        }
                        .wpc-filter-layout-dropdown .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b, 
                        .wpc-sorting-form .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b,
                        .widget_wpc_sorting_widget .select2-container--default .select2-selection--single:hover .select2-selection__arrow b,
                        .wpc-filter-layout-dropdown .select2-container--default .select2-selection--single:hover .select2-selection__arrow b,
                        .wpc-filter-layout-dropdown .select2-container--default .select2-selection--single:hover, 
                        .select2-container--default.select2-container--open .wpc-filter-everything-dropdown.select2-dropdown,
                        .wpc-sorting-form .select2-container--open .select2-selection--single:hover,
                        .widget_wpc_sorting_widget .select2-container--open .select2-selection--single,
                        .wpc-filter-layout-dropdown .select2-container--open .select2-selection--single, 
                        .wpc-filters-widget-content input[type=email]:focus, 
                        .wpc-filters-widget-content input[type=number]:focus, 
                        .wpc-filters-widget-content input[type=password]:focus, 
                        .wpc-filters-widget-content input[type=search]:focus, 
                        .wpc-filters-widget-content input[type=tel]:focus, 
                        .wpc-filters-widget-content input[type=text]:focus, 
                        .wpc-filters-widget-content input[type=url]:focus,
                        .wpc-filters-widget-content input[type=email]:active, 
                        .wpc-filters-widget-content input[type=number]:active, 
                        .wpc-filters-widget-content input[type=password]:active, 
                        .wpc-filters-widget-content input[type=search]:active, 
                        .wpc-filters-widget-content input[type=tel]:active, 
                        .wpc-filters-widget-content input[type=text]:active, 
                        .wpc-filters-widget-content input[type=url]:active,
                        .wpc-filter-collapsible .wpc-filter-title button:hover .wpc-open-icon, 
                        .wpc-filter-collapsible-reverse.wpc-filter-collapsible.wpc-closed .wpc-filter-title button:hover .wpc-open-icon, 
                        .wpc-filter-collapsible.wpc-closed .wpc-filter-title button:hover .wpc-open-icon, 
                        .wpc-filter-has-selected.wpc-closed .wpc-filter-title button:hover .wpc-open-icon{
                            border-color: '.$color.';
                        }
                       
                        .wpc-filters-main-wrap a.wpc-toggle-a:hover {
                            color: '.$color.';
                        }
                        .wpc-sorting-form .select2-container--default.select2-container--open.select2-container--above .select2-selection--multiple, 
                        .wpc-filter-layout-dropdown .select2-container--default.select2-container--open.select2-container--above .select2-selection--single,
                        .wpc-sorting-form .select2-container--default.select2-container--open.select2-container--above .select2-selection--multiple, 
                        .wpc-filter-layout-dropdown .select2-container--default.select2-container--open.select2-container--above .select2-selection--single{
                            border-top: 1px solid transparent;
                        }
                        .wpc-search-field-wrapper .wpc-search-clear-icon-wrapper, 
                        .wpc-filter-search-wrapper button.wpc-search-clear{
                            color: #b5bed2;
                        }
                        .wpc-filters-main-wrap .wpc-filters-labels li.wpc-term-item label {
                            border-color: #ccd0dc;
                        }
                        .wpc-filters-main-wrap .wpc-filters-labels li.wpc-term-item label span.wpc-filter-label-wrapper{
                            padding: 8px 7px;
                        }
                        .wpc-filters-labels li.wpc-term-has-image label:hover .wpc-term-image-wrapper, 
                        .wpc-filters-labels li.wpc-term-has-image input[type=checkbox]:checked + label .wpc-term-image-wrapper{
                            border-color: '.$color.';
                        }
                        @media screen and (min-width: '.$wpc_mobile_width.'px) {
                            .wpc-filters-main-wrap input[type=radio]:hover,
                            .wpc-filters-main-wrap input[type=checkbox]:hover{
                                border-color: '.$styled_color.';
                            }
                            .wpc-filter-label-wrapper .wpc-term-swatch-no-image:hover,
                            .wpc-filters-main-wrap .wpc-term-count-0:not(.wpc-has-not-empty-children) input[type=radio]:hover,
                            .wpc-filters-main-wrap .wpc-term-count-0:not(.wpc-has-not-empty-children) input[type=checkbox]:hover{
                                border-color: #c3c3c3;
                            }
                        }';
        }

        if( $use_select2 ){
            $styled_color   = $color ? $color : '#0570e2';
            $contrastColor  = $contrastColor ? $contrastColor : flrt_get_contrast_color($styled_color);

            $css .= '.wpc-sorting-form select,
                        .wpc-filter-content select{
                            padding: 2px 8px 2px 10px;
                            border-color: #c9d1e0;
                            border-radius: 3px;
                            color: inherit;
                            -webkit-appearance: none;
                        }
						.select2-container--default .wpc-filter-everything-dropdown .select2-results__option--highlighted::after,
						.select2-container--default .wpc-filter-everything-dropdown .select2-results__option[aria-selected="true"]::after,
						.select2-container--default .wpc-filter-everything-dropdown .select2-results__option[data-selected="true"]::after {
							border: 2px solid '.$color.';
							content: "";
							position: absolute !important;
							right: 13px !important;
							width: 18px;
							height: 10px;
							top: 30% !important;
							font-size: 16px !important;
							color: #000 !important;
							transform: rotate(137deg) !important;
							border-bottom: none;
							border-left: none;
                            box-sizing: border-box;
						}
						.select2-container--default .wpc-filter-everything-dropdown .select2-results__option--highlighted::after {
							border-top: 2px solid #C7D1E2;
							border-right: 2px solid #C7D1E2;
						}
                        .select2-container--default .wpc-filter-everything-dropdown .select2-results__option--highlighted[aria-selected],
                        .select2-container--default .wpc-filter-everything-dropdown .select2-results__option--highlighted[data-selected]{
                            background-color: rgba(0,0,0,0.05); 
                            color: inherit;
                        }
                        ';
            $css .= '@media screen and (max-width: '.$wpc_mobile_width.'px) {'."\r\n";
            $css .=  '.wpc-sorting-form select,
                        .wpc-filter-content select{
                            padding: 6px 12px 6px 14px;
                        }';

            $css .= '}'."\r\n";
        }

        if( $move_to_top ){
            $css .= '@media screen and (max-width: '.$wpc_mobile_width.'px) {'."\r\n";

            $css .= 'body #main,
                        body #content .col-full,
                        .woocommerce-page .content-has-sidebar,
                        .woocommerce-page .has-one-sidebar,
                        .woocommerce-page #main-sidebar-container,
                        .woocommerce-page .theme-page-wrapper,
                        .woocommerce-page #content-area,
                        .theme-jevelin.woocommerce-page .woocomerce-styling,
                        .woocommerce-page .content_wrapper,
                        .woocommerce-page #col-mask,
                        body #main-content .content-area {
                            -js-display: flex;
                            display: -webkit-box;
                            display: -webkit-flex;
                            display: -moz-box;
                            display: -ms-flexbox;
                            display: flex;
                            -webkit-box-orient: vertical;
                            -moz-box-orient: vertical;
                            -webkit-flex-direction: column;
                            -ms-flex-direction: column;
                            flex-direction: column;
                        }
                        body #primary,
                        .woocommerce-page .has-one-sidebar > section,
                        .woocommerce-page .theme-content,
                        .woocommerce-page #left-area,
                        .woocommerce-page #content,
                        .woocommerce-page .sections_group,
                        .woocommerce-page .content-box,
                        body #main-sidebar-container #main {
                            -webkit-box-ordinal-group: 2;
                            -moz-box-ordinal-group: 2;
                            -ms-flex-order: 2;
                            -webkit-order: 2;
                            order: 2;
                        }
                        body #secondary,
                        .woocommerce-page .has-one-sidebar > aside,
                        body aside#mk-sidebar,
                        .woocommerce-page #sidebar,
                        .woocommerce-page .sidebar,
                        body #main-sidebar-container #sidebar {
                            -webkit-box-ordinal-group: 1;
                            -moz-box-ordinal-group: 1;
                            -ms-flex-order: 1;
                            -webkit-order: 1;
                            order: 1;
                        }
                    
                        /*second method first method solve issue theme specific*/
                        .woocommerce-page:not(.single,.page) .btWithSidebar.btSidebarLeft .btContentHolder,
                        body .theme-generatepress.woocommerce #content {
                            display: table;
                        }
                        body .btContent,
                        body .theme-generatepress.woocommerce #primary {
                            display: table-footer-group;
                        }
                        body .btSidebar,
                        body .theme-generatepress.woocommerce #left-sidebar {
                            display: table-header-group;
                        }'."\r\n";
                        $css .= '#ui-datepicker-div.wpc-filter-datepicker .ui-datepicker-close.ui-state-default{
                            background: '.$color.';
                            color: '.$contrastColor.';
                        }'."\r\n";
                        $css .= '.wpc-filters-date-range-column{
                            justify-content: left;
                        }'."\r\n";
            $css .= '}'."\r\n";
        }

        if( $use_loader ){
            $css .= '@media screen and (min-width: '.$wpc_mobile_width.'px) {'."\r\n";
            $css .= 'html.is-active .wpc-spinner{
                                display: block;
                            }';
            $css .= '}'."\r\n";
        }

        if( $dark_overlay ){
            $css .= '@media screen and (min-width: '.$wpc_mobile_width.'px) {'."\r\n";
            $css .= 'html.is-active .wpc-filters-overlay{
                            opacity: .15;
                            background: #000000;
                        }';
            $css .= '}'."\r\n";
        }

        if(flrt_is_divi_theme()){
                $css .= <<<PHP_CSS
                .wp-theme-Divi .et_builder_inner_content .widget_wpc_sorting_widget,  
                .wp-theme-Divi .et_builder_inner_content .widget_wpc_filters_widget,
                .wp-theme-Divi .et_builder_inner_content .widget_wpc_chips_widget
                {
                    margin: 0 0 3.7em;
                }
                .wp-theme-Divi .et_builder_inner_content .widget_wpc_chips_widget ul.wpc-filter-chips-list{
                    padding: 0;
                }
                .wpc-divi-filter-wrap .wpc-filters-open-button-container{
                    display: block !important;
                }
                @media screen and (max-width: 768px) {
                    .wpc-filters-open-button-container.et_before_main_content{
                        display: block !important;
                    }
                }
                PHP_CSS;
                $css .= "\r\n";
        }

        if( $custom_css ){
            $css .= $custom_css."\r\n";
        }

        $wp_upload_dir = wp_upload_dir();
        $upload_dir_baseurl = $wp_upload_dir['baseurl'];
        $upload_dir_basepath = $wp_upload_dir['basedir'];

        $cache_dir = $upload_dir_basepath . '/cache/filter-everything/';

        if ( ! file_exists( $cache_dir ) ) {
            mkdir($cache_dir, 0777, true);
            chmod($cache_dir, 0777);
        }

        $filename = md5( $css ) . '.css';

        $fileurl  = $upload_dir_baseurl .'/cache/filter-everything/' . $filename;
        $filepath = $cache_dir . $filename;

        if ( $css !== '' ) {
            if ( ! file_exists( $filepath ) ) {
                file_put_contents( $filepath, $css );
            }

            if( file_exists( $filepath ) ){
                wp_enqueue_style('wpc-filter-everything-custom', $fileurl );
            }
        }

    }
}
