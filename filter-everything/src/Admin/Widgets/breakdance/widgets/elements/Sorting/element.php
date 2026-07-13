<?php

namespace FilterEverything;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;
use FilterEverything\Filter\Sorting;


\Breakdance\ElementStudio\registerElementForEditing(
    "FilterEverything\\FilterEverythingSorting",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class FilterEverythingSorting extends \Breakdance\Elements\Element
{
    static function uiIcon()
    {
        return '<div class="breakdance-icon" style="width: 16px; height: 16px;">' . flrt_get_icon_logo_svg(16, 16) . '</div>';
    }

    static function tag()
    {
        return 'div';
    }

    static function tagOptions()
    {
        return [];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return esc_html__('Filter Everything - Sorting', 'filter-everything');
    }

    static function className()
    {
        return 'filter-everything-sorting';
    }

    static function category()
    {
        return 'filter-everything';
    }

    static function badge()
    {
        return false;
    }

    static function slug()
    {
        return __CLASS__;
    }

    static function template()
    {
        return '%%SSR%%';
    }

    static function ssr($propertiesData, $parentPropertiesData = [], $isBuilder = false, $repeaterItemNodeId = null) {
        ob_start();

        if ($isBuilder) {
            echo '<h5>' . self::name() . '</h5>';
        } else {
            if (isset($propertiesData['content']['settings']['sorting_options'])) {
                $arguments = [
                    'title' => !empty($propertiesData['content']['settings']['title']) ? $propertiesData['content']['settings']['title'] : '',
                    'titles' => [],
                    'orderbies' => [],
                    'orders' => [],
                    'meta_keys' => []
                ];

                foreach ($propertiesData['content']['settings']['sorting_options'] as $key => $setting) {
                    $arguments['titles'][$key] = $setting['titles'];
                    $arguments['orderbies'][$key] = $setting['orderbies'];
                    $arguments['orders'][$key] = $setting['orders'];
                    $arguments['meta_keys'][$key] = !empty($setting['meta_keys']) ? $setting['meta_keys'] : '';
                }

                the_widget('\FilterEverything\Filter\SortingWidget', $arguments);
            } else {
                flrt_log('Missing required keys in $propertiesData');
            }
        }

        return ob_get_clean();
    }


    static function defaultCss()
    {
        return file_get_contents(__DIR__ . '/default.css');
    }

    static function defaultProperties()
    {
        $filterSorting = new Sorting();

        if(!empty($default)){
            return [
                'content' =>[
                    'title' => '',
                    'settings' => [
                        'sorting_options' => $filterSorting->prepareForPageBuilder(),
                    ],
                ]
            ];
        }
        return false;
    }

    static function defaultChildren()
    {
        return false;
    }

    static function cssTemplate()
    {
        $template = file_get_contents(__DIR__ . '/css.twig');
        return $template;
    }

    static function designControls()
    {
        return [];
    }

    static function contentControls()
    {
        $filterSorting = new Sorting();
        $orderbies = [];
        if(!empty($filterSorting->getSortingOptions())){
            foreach ($filterSorting->getSortingOptions() as $text => $option){
                $orderbies[] = ['text' => $option, 'value' => $text];
            }
        }
        return [c(
        "settings",
        esc_html__("Settings", 'filter-everything'),
        [c(
        "title",
        esc_html__("Title", 'filter-everything'),
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
        
      ), c(
        "sorting_options",
        esc_html__("Sorting options:", 'filter-everything'),
        [c(
        "titles",
        "Title",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
        
      ), c(
        "orderbies",
        esc_html__("Order by", 'filter-everything'),
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => $orderbies],
        false,
        false,
        ['order'],
        
      ), c(
        "meta_keys",
        esc_html__("Meta key", 'filter-everything'),
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => esc_html__("When ordering by Meta Key", 'filter-everything')],
        false,
        false,
        [],
        
      ), c(
        "orders",
        esc_html__("Order", 'filter-everything'),
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'asc', 'text' => 'ASC'], ['text' => 'DESC', 'value' => 'desc']]],
        false,
        false,
        [],
        
      )],
        ['type' => 'repeater', 'layout' => 'vertical', 'repeaterOptions' => ['titleTemplate' => '{titles}', 'defaultTitle' => 'Item', 'buttonName' => 'Add sorting option']],
        false,
        false,
        [],
        
      )],
        ['type' => 'section', 'layout' => 'vertical', 'sectionOptions' => ['type' => 'accordion']],
        false,
        false,
        [],
        
      )];
    }

    static function settingsControls()
    {
        return [];
    }

    static function dependencies()
    {
        return false;
    }

    static function settings()
    {
        return false;
    }

    static function addPanelRules()
    {
        return false;
    }

    static public function actions()
    {
        return false;
    }

    static function nestingRule()
    {
        return ['type' => 'final'];
    }

    static function spacingBars()
    {
        return false;
    }

    static function attributes()
    {
        return false;
    }

    static function experimental()
    {
        return false;
    }

    static function availableIn()
    {
        return ['breakdance'];
    }


    static function order()
    {
        return 0;
    }

    static function dynamicPropertyPaths()
    {
        return false;
    }

    static function additionalClasses()
    {

        return [['name' => 'wpc-fe-breakdance-sorting', 'template' => 'yes']];
    }

    static function projectManagement()
    {
        return false;
    }

    static function propertyPathsToWhitelistInFlatProps()
    {
        return false;
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }
}
