<?php

namespace FilterEverything;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "FilterEverything\\FilterEverythingFilters",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class FilterEverythingFilters extends \Breakdance\Elements\Element
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
        return esc_html__('Filter Everything - Filters', 'filter-everything');
    }

    static function className()
    {
        return 'filter-everything-filters';
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

    /**
     * @param mixed $propertiesData
     * @param mixed $parentPropertiesData
     * @param bool $isBuilder
     * @param int $repeaterItemNodeId
     * @return string
     */
    static function ssr($propertiesData, $parentPropertiesData = [], $isBuilder = false, $repeaterItemNodeId = null)
    {
        ob_start();

        if($isBuilder){
            echo '<h5>' . self::name() . '</h5>';
        }

        if(!$isBuilder){
            if (isset($propertiesData['content']['settings'])) {
                $setting = $propertiesData['content']['settings'];
                if (!isset($setting['show_count'])) {
                    $propertiesData['show_count'] = false;
                }
                if (!isset($setting['chips'])) {
                    $propertiesData['chips'] = false;
                }
                the_widget('\FilterEverything\Filter\FiltersWidget', $setting);
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
        "show_count",
        esc_html__("Show the number of posts found", 'filter-everything'),
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
        
      ), c(
        "chips",
        esc_html__("Show selected terms (Chips)", 'filter-everything'),
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
        
      )],
        ['type' => 'section', 'layout' => 'vertical'],
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
        return false;
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
