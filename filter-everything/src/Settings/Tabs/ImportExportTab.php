<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class ImportExportTab extends BaseSettings
{
    protected $page = 'wpc-filter-import-export-settings';

    protected $group = 'wpc_filter_import_export';

    protected $optionName = 'wpc_filter_import_export';

    public function init()
    {
        add_action('admin_init', array($this, 'initSettings'));
        add_action('admin_notices', array($this, 'adminExportErrorNotice'));
        add_action('admin_notices', array($this, 'adminImportErrorNotice'));
        add_action( 'wpc_import_button_info', array( $this, 'backupMessage' ) );
    }

    public function initSettings()
    {
        //register_setting($this->group, $this->optionName);

        $settings = array(
            'wpc_export_settings' => array(
                    'label'  => esc_html__('Export settings', 'filter-everything'),
                    'fields' => array(
                        'export_all' => array(
                            'type'  => 'checkbox',
                            'title' => esc_html__('Export all settings', 'filter-everything'),
                            'id'    => 'export_all',
                            'label' => esc_html__('All', 'filter-everything'),
                        ),
                        'export_filter_set' => array(
                            'type'  => 'checkbox',
                            'title' => esc_html__('Export filters and filter sets', 'filter-everything'),
                            'id'    => 'export_filter_set',
                            'label' => esc_html__('Filters and filter sets', 'filter-everything'),
                        ),
                        'export_options' => array(
                            'type'  => 'checkbox',
                            'title' => esc_html__('Export plugin settings', 'filter-everything'),
                            'id'    => 'export_settings',
                            'label' => esc_html__('Settings', 'filter-everything'),
                        ),
                    )
                ),
            'wpc_import_settings' => array(
                'label'  => esc_html__('Import settings', 'filter-everything'),
                'fields' => array(
                    'import_file' => array(
                        'type'  => 'file',
                        'title' => esc_html__('Import the settings file to import', 'filter-everything'),
                        'id'    => 'import_file',
                        'label' => esc_html__('Select File', 'filter-everything'),
                        'required' => 'required'
                    ),
                    'import_all' => array(
                        'type'  => 'checkbox',
                        'title' => esc_html__('Import all settings from file', 'filter-everything'),
                        'id'    => 'import_all',
                        'label' => esc_html__('Import all setting', 'filter-everything'),
                    ),
                    'import_filter_set' => array(
                        'type'  => 'checkbox',
                        'title' => esc_html__('Import filters and filter sets', 'filter-everything'),
                        'id'    => 'import_filter_set',
                        'label' => esc_html__('Import filters and filter sets', 'filter-everything'),
                    ),
                    'import_options' => array(
                        'type'  => 'checkbox',
                        'title' => esc_html__('Import plugin settings', 'filter-everything'),
                        'id'    => 'import_options',
                        'label' => esc_html__('Import settings', 'filter-everything'),
                    ),
                )
            ),
        );
        if( defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO ){
            $settings['wpc_export_settings']['fields']['export_seo_rule'] = array(
                'type'  => 'checkbox',
                'title' => esc_html__('Export all seo rules', 'filter-everything'),
                'id'    => 'export_seo_rule',
                'label' => esc_html__('Seo rules', 'filter-everything')
            );
            $settings['wpc_import_settings']['fields']['import_filter_seo_rule'] = array(
                'type'  => 'checkbox',
                'title' => esc_html__('Import all seo rules', 'filter-everything'),
                'id'    => 'import_filter_seo_rule',
                'label' => esc_html__('Import seo rules', 'filter-everything')
            );
        }

        $settings = apply_filters('wpc_import_export_filters_settings', $settings);

        $this->registerSettings($settings, $this->page, $this->optionName);
    }
    public function getLabel()
    {
        return esc_html__('Export/Import', 'filter-everything');
    }

    public function getName()
    {
        return 'import_export';
    }

    public function valid()
    {
        return true;
    }
    public function render()
    {

        settings_errors();

        do_action('wpc_before_import_export_sections_settings_fields', $this->page );

        $this->doSettingsSections($this->page);

        do_action('wpc_after_import_export_sections_settings_fields', $this->page );
    }
    public function doSettingsSections( $page ) {
        global $wp_settings_sections, $wp_settings_fields;

        if ( ! isset( $wp_settings_sections[ $page ] ) ) {
            return;
        }

        foreach ( (array) $wp_settings_sections[ $page ] as $section ) {


            do_action('wpc_import_export_before_settings_fields_title', $page );

            if ( $section['title'] ) {
                echo "<h2>". wp_kses( $section['title'], array( 'span' => array( 'class' => true ) )) ."</h2>\n";
            }

            do_action('wpc_import_export_after_settings_fields_title', $page );

            if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
                continue;
            }

            $sortable = ( $section['id'] === 'wpc_slugs' ) ? ' wpc-sortable-table' : '';

            $form_url = admin_url('admin.php?action=' . $section['id']);

            $enctype = '';
            if ($section['id'] == 'wpc_import_settings'){
                $enctype = 'enctype="multipart/form-data"';
            }
            print('<form method="post" class="form_' . $section['id'] . '" action="' . $form_url . '"' . $enctype . '>');
            wp_nonce_field($section['id'], $section['id']);
            $button_text = ($section['id'] === 'wpc_export_settings') ? esc_html__('Export settings', 'filter-everything') : esc_html__('Import settings', 'filter-everything');
            echo '<table class="wpc-form-table form-table'.esc_attr($sortable).'" role="presentation">';
            $this->doSettingsFields( $page, $section['id'] );
            echo '</table>';

            if($section['id'] === 'wpc_import_settings') do_action('wpc_import_button_info', $page );

            if( apply_filters('wpc_settings_submit_button', true ) ){
                submit_button($button_text);
            }
            print('</form>');
        }
    }

    public function adminExportErrorNotice()
    {
        if (isset($_GET['flrt_export_error']) && !empty($_GET['flrt_export_error'])) {
            $error = $_GET['flrt_export_error'];
            switch ($error) {
                case 'empty':
                    $this->viewError(esc_html__('No export options selected', 'filter-everything'));
                    break;
                case 'empty_set':
                    $this->viewError(esc_html__("You don't have any settings yet", 'filter-everything'));
                    break;
                case 'no_edit_right':
                    $this->viewError(esc_html__('No editing rights', 'filter-everything'));
                    break;
            }
        }
    }

    public function adminImportErrorNotice()
    {
        if (isset($_GET['flrt_import_error']) && !empty($_GET['flrt_import_error'])) {
            $error = $_GET['flrt_import_error'];
            switch ($error) {
                case 'empty_params':
                    $this->viewError(esc_html__('No import options selected', 'filter-everything'));
                    break;
                case 'empty_file_input':
                    $this->viewError(esc_html__('File to import not selected', 'filter-everything'));
                    break;
                case 'upload_file_error':
                    $this->viewError(esc_html__('Import file upload error', 'filter-everything'));
                    break;
                case 'not_json_format':
                    $this->viewError(esc_html__('The file must be in JSON format', 'filter-everything'));
                    break;
                case 'no_edit_right':
                    $this->viewError(esc_html__('No editing rights', 'filter-everything'));
                    break;
                case 'invalid_extension':
                    $this->viewError(esc_html__('The file must have the extension .json', 'filter-everything'));
                    break;
                case 'invalid_json':
                    $this->viewError(esc_html__('The file contains invalid JSON', 'filter-everything'));
                    break;
                case 'empty_in_import_filter_set':
                    $this->viewError(esc_html__("The import file doesn't contain any filter sets data", 'filter-everything'));
                    break;
                case 'empty_in_import_options':
                    $this->viewError(esc_html__("The import file doesn't contain any settings data", 'filter-everything'));
                    break;
                case 'empty_in_import_seo_rule':
                    $this->viewError(esc_html__("The import file doesn't contain any SEO rules data", 'filter-everything'));
                    break;
            }
        }
        if (isset($_GET['flrt_import_success']) && !empty($_GET['flrt_import_success'])) {
            if($_GET['flrt_import_success'] == 'import_success'){
                $success_str = '<div class="notice notice-success is-dismissible"><p>%s</p></div>';
                printf(
                    $success_str,
                    esc_html__("Data import completed successfully", 'filter-everything')
                );
            }

        }
    }

    public function viewError($text)
    {
        $error_str = '<div class="notice notice-error is-dismissible"><p>%s</p></div>';
        printf(
            $error_str,
            $text
        );
    }
    public function backupMessage( $page )
    {
        if( $page === $this->page ){
            echo '<p><strong>'.esc_html__( 'Before importing any data, always make a backup of your website’s database to prevent data loss.', 'filter-everything' ).'</strong></p>';
        }
    }
}
