<?php
/**
 * @var \FilterEverything\Filter\TabInterface[] $tabs
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

?>
<div class="wrap">
    <h1><?php esc_html_e( 'Filters Settings', 'filter-everything' ) ?></h1>
    <h2 class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab ): ?>
			<?php if ( $tab->valid() ): ?>
				<?php
                $class = ( $tab == $current ) ? ' nav-tab-active' : '';
                $class .= ' wpc-tab-' . $tab->getName();
                $tabUrl   = admin_url( 'edit.php?post_type=filter-set&page=filters-settings&tab=' . $tab->getName() );
                $label = esc_html($tab->getLabel());
                ?>
                <a class='nav-tab<?php echo esc_attr( $class ); ?>'
                   href='<?php echo esc_url( $tabUrl ); ?>'><?php echo $label ?>
                    <?php if (method_exists($tab, 'labelIcon')) {
                        echo '<span class="' . esc_attr('wpc-nav-tab-icon') . '">' .$tab->labelIcon() . '</span>';
                    }?>
                </a>
                <?php
                if (method_exists($tab, 'getFreeLink')) {
                    $links = $tab->getFreeLink();
                    if (!empty($links)) {
                        $tabUrl = $links['link'];
                        $label = $links['text']; ?>
                        <a class='nav-tab<?php echo esc_attr(' wpc-tab-' . $tab->getName()); ?> wpc-pro-badge-text'
                           href='<?php echo esc_url($tabUrl); ?>'><?php echo $label ?></a>
                    <?php }
                }
                ?>
            <?php endif; ?>
		<?php endforeach; ?>
    </h2>
	<?php echo $current->render() //already escaped in the method ?>
</div>