<?php
/**
 * The Template for displaying filter selected terms.
 *
 * This template can be overridden by copying it to yourtheme/filters/chips.php.
 *
 * $chips - array, with the Filter Set parameters
 *
 * @see https://filtereverything.pro/resources/templates-overriding/
 */

if ( ! defined('ABSPATH') ) {
    exit;
}
global $chips_count;
// Iterate global chips widget count
$selected_and_above_status = (isset($filter['selected_and_above']) && $filter['selected_and_above'] === 'yes') ? true : false;
$chips_count++;
?>
<ul class="wpc-filter-chips-list wpc-filter-chips-<?php echo esc_attr( $setid .'-' .$chips_count ); ?> wpc-filter-chips-<?php echo esc_attr( $setid ); ?><?php if( ! $chips ){echo ' wpc-empty-chips-container';} ?>" data-set="<?php echo esc_attr( $setid ); ?>" data-setcount="<?php echo $setid .'-' .$chips_count ; ?>">
    <?php if( $chips ) : ?>
        <?php foreach( $chips as $chip ): ?>
            <li class="wpc-filter-chip <?php echo esc_attr( $chip['class'] ); ?>"><a href="<?php echo esc_url( $chip['link'] ); ?>" title="<?php if( $chip['name'] !== esc_html__('Reset all', 'filter-everything') ){
                    if ( $chip['class'] === 'wpc-chip-search' ) {
                        echo esc_attr( sprintf( __('Remove %s from results', 'filter-everything'), '&laquo;'.$chip['label'].'&raquo;' ) );
                    } else {
                        echo esc_attr( sprintf( __('Remove %s from results', 'filter-everything'), '&laquo;'.$chip['label'] .': '.$chip['name'].'&raquo;' ) );
                    }
                } ?>"><span class="wpc-chip-content"><?php if(isset($chip['rating'])) : ?><span class="wpc-chip-stars"><?php
                            for ($i = 1; $i <=  $chip['rating']; $i++){
                                echo '<span>' . flrt_rating_star() . '</span>';
                            }
                            ?></span><?php
                        else :
                            ?><span class="wpc-filter-chip-name"><?php echo esc_html( $chip['name'] ); ?></span><?php
                        endif;
                        ?><span class="wpc-chip-remove-icon">&#215;</span></a></span></li>
        <?php endforeach; ?>
    <?php endif; ?>
</ul>