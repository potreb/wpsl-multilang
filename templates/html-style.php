<?php
/** @var \WPSL\MultiLang\Integration\Integration $plugin */
$plugin     = $args['plugin'];
$assets_url = $args['assets_url'];
?>
<style>
	<?php foreach ( $plugin->get_blog_sites_id() as $blog_id => $url ) : ?>
	<?php if ( $plugin->get_short_lang( $blog_id ) ) : ?>
	#wpadminbar .quicklinks .menu-langual-blog-id-<?php echo $blog_id; ?> a:before,
	#wpadminbar .quicklinks #wp-admin-bar-blog-<?php echo $blog_id; ?> .blavatar:before {
		content: url('<?php echo  esc_url( $assets_url . 'images/flags/' ) . $plugin->get_short_lang( $blog_id ) . '.png'; ?>') !important;
		top: 0;
		margin: 0px 8px 0 -2px;
	}

	#wpadminbar .quicklinks li .blavatar:before {
		content: "";
	}

	<?php endif; ?>
	.wp-admin #wpadminbar #wp-admin-bar-site-name > .ab-item::before {
		content: url('<?php echo  esc_url( $assets_url . 'images/flags/' ) . $plugin->get_short_lang( $plugin->get_current_blog_id() ) . '.png'; ?>');
		top: 0;
	}

	<?php endforeach; ?>
</style>
