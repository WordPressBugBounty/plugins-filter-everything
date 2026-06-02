<?php
namespace FilterEverything\Filter;

trait SeoTabTrait
{

    public function render()
    {
        print('<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" id="wpc-seo-settings">');

        if ( ! empty( $_GET['settings-updated'] ) ) {
            add_settings_error(
                'wpc_seo_settings',
                'wpc_seo_settings_updated',
                __( 'SEO settings saved successfully' ),
                'updated'
            );
        }

        settings_errors();

        echo '<input type="hidden" name="action" value="wpc_seo_settings">';
        wp_nonce_field( 'wpc_seo_settings' );

        $this->doSettingsSections($this->page);

        if( apply_filters('wpc_settings_submit_button', true ) ){
            submit_button();
        }

        print('</form>');
    }

    public function doSettingsSections( $page ) {
        global $wp_settings_sections, $wp_settings_fields;

        if ( ! isset( $wp_settings_sections[ $page ] ) ) {
            return;
        }


        echo '<h2 class="nav-tab-wrapper">';
        foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
            if(!empty($section['before_section'])){
                $css_classes = ($section['id'] === 'wpc_slugs') ? ' nav-tab-active' : '';
                echo '<a href="#' . $section['id']  . '" class="nav-tab' . $css_classes . '">' . $section['before_section'] . '</a>';
            }
        }
        if(method_exists($this, 'proSettingsLink')){
            $link = $this->proSettingsLink();
            if(!empty($link)){
                foreach ($link as $key => $val) {
                    echo '<a href="'. admin_url($val['link']) .'" class="nav-tab wpc-pro-tab disabled wpc-pro-badge-text">'. $val['text'] .'</a>';
                }
            }
        }
        echo '</h2>';


        foreach ( (array) $wp_settings_sections[ $page ] as $section ) {

            if(!empty($section['before_section'])){
                $class = '';
                if( $section['id'] === 'wpc_slugs' ){
                    $class = ' active';
                }
                echo '<div id="' . $section['id'] . '" class="tab-content' .$class. '">';
                do_action('wpc_before_seo_setting_section_title_' . $section['id'], $page);
                do_action('wpc_before_sections_settings_fields', $this->page );
            }

            if ( !empty($section['title']) ) {
                echo "<h3>". wp_kses( $section['title'], array( 'span' => array( 'class' => true ) )) ."</h3>\n";
            }

            if(!empty($section['before_section'])){
                do_action('wpc_after_seo_setting_section_title_' . $section['id'], $page);
            }

            if ( $section['callback'] ) {
                call_user_func( $section['callback'], $section );
            }

            $sortable = ( $section['id'] === 'wpc_slugs' ) ? ' wpc-sortable-table' : '';

            echo '<table class="wpc-form-table form-table'.esc_attr($sortable).'" role="presentation">';
            $this->doSettingsFields( $page, $section['id'] );
            echo '</table>';

            if(!empty($section['after_section'])){
                do_action('wpc_after_sections_settings_fields_' . $section['id'], $this->page );
                echo '</div>';
            }
        }
        do_action('wpc_after_settings_fields', $page );
    }

    public function start_section_html($is_open = false) {
        return $this->sectionName();
    }

    public function end_section_html() {
        return 'true';
    }

    protected function addSectionSettingsWrapper($settings, $is_open = false)
    {
        $first_key = array_key_first($settings);
        $last_key = array_key_last($settings);

        if ($first_key !== $last_key) {
            $settings[$first_key]['args'] = ['before_section' => $this->start_section_html($is_open)];
            $settings[$last_key]['args'] = ['after_section' => $this->end_section_html()];
        }
        if ($first_key === $last_key) {
            $settings[$first_key]['args'] = ['before_section' => $this->start_section_html($is_open), 'after_section' => $this->end_section_html()];
        }
        return $settings;
    }
}