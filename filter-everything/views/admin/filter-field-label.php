<?php

if ( ! defined('ABSPATH') ) {
    exit;
}
$pro_class = '';
if( isset( $attributes['pro_label'] ) && $attributes['pro_label'] ){
    $pro_class = ' wpc-pro-badge-text';
}
?><td class="wpc-filter-label-td<?php echo $pro_class; ?>"><?php
    $label = esc_html( $attributes['label'] );
    $pro_class = '';
    if( isset( $attributes['pro_label'] ) && $attributes['pro_label'] ){
        $pro_class = ' wpc-filter-label-in-pro';
    }
    if( $label ) :
        ?><label <?php if( isset( $attributes['id'] ) && $attributes['type'] !== 'Radio' ){ echo 'for="'. esc_attr( $attributes['id'] ).'"'; } ?> class="wpc-filter-label<?php echo $pro_class; ?>"><?php
        echo '<span class="wpc-label-text">'.$label.'</span>';
        if( isset( $attributes['required'] ) && $attributes['required'] ){
            echo '<span class="wpc-field-required">*</span>'."\n";
        }
        if( isset( $attributes['pro_label'] ) && $attributes['pro_label'] ){
            echo '<span>' . $attributes['pro_label'] . '</span>';
        }
        ?></label>
        <?php echo flrt_field_instructions($attributes); // Already escaped in function ?>
        <?php echo flrt_tooltip($attributes); ?>
    <?php endif; ?>
</td>