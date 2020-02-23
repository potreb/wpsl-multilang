<?php
/** @var WPSL\MultiLang\Plugin $plugin */
/** @var WPSL\MultiLang\Posts $this */

global $post;

$plugin = $this->plugin;

$object_id      = isset( $_GET['object_id'] ) ? intval( $_GET['object_id'] ) : null; //phpcs:ignore
$object_blog_id = isset( $_GET['object_blog_id'] ) ? intval( $_GET['object_blog_id'] ) : null; //phpcs:ignore
$select_type_value = (int) $plugin->get_network_option( 'select_type', 2 );
?>
<div class="wpsl-language-select-box">
	<?php if ( isset( $post->ID ) && ! empty( $post->ID ) ) : ?>
		<?php foreach ( $plugin->get_multisite_languages( true ) as $site_id => $lang ) : ?>
			<?php
			$custom_data = 'data-post_blog_id="' . esc_attr( $site_id ) . '" data-object_id="' . esc_attr( $post->ID ) . '" data-object_blog_id="' . esc_attr( $plugin->get_current_blog_id() ) . '" data-post_type="' . esc_attr( get_post_type( $post->ID ) ) . '"';
			$blog_posts  = $this->blog_post_option( $site_id, get_post_type( $post->ID ) );
			?>
			<div class="wpsl-language-select">
			<label>
				<span class="wpsl-multilang-edit-post-language-icon"><?php echo $plugin->get_language_icon_html( $site_id ); //phpcs:ignore ?></span>
				<?php if ( 1 === $select_type_value || 2 === $select_type_value ) : ?>
				<span class="wpslml-select-posts-list-wrapper">
					<select name="wpslml_post_translations[<?php echo esc_attr( $site_id ); ?>]" class="wpslml-blog-posts" style="min-width: 200px; width: 400px;">
						<option value=""><?php esc_html_e( '&ndash; select translate &ndash;', 'wpsl-multilang' ); ?></option>
					<?php if ( isset( $translations[ $site_id ] ) && isset( $translations[ $site_id ]['post_title'] ) ) : ?>
						<?php
						$item   = $translations[ $site_id ];
						$has_id = $item['ID'];
						?>
						<option value="<?php echo esc_attr( $item['ID'] ); ?>" selected="selected"><?php echo esc_html( $item['post_title'] ); ?></option>
						<?php unset( $item ); ?>
					<?php endif; ?>
					<?php foreach ( $blog_posts as $blog_post ) : ?>
						<?php
						$blog_post_id = (int) $blog_post->ID;
						if ( isset( $has_id ) && $has_id === $blog_post->ID ) {
							unset( $has_id );
							continue;
						}
						?>
						<?php
						$selected = '';
						if ( $object_blog_id === $site_id && $object_id === $blog_post_id ) {
							$selected = selected( $object_id, $blog_post_id, false );
						};
						?>
						<option value="<?php echo esc_attr( $blog_post_id ); ?>" <?php echo $selected; ?>><?php echo esc_html( $blog_post->post_title ); ?></option>
					<?php endforeach; ?>
					</select>
				</span>

				<?php else : ?>

				<span class="wpslml-search-translation-wrapper">
					<select	style="min-width: 200px; width: 400px;" class="wpslml-search-translation" <?php echo $custom_data; //phpcs:ignore ?>>
						<?php if ( isset( $translations[ $site_id ] ) && isset( $translations[ $site_id ]['post_title'] ) ) : ?>
							<?php $item = $translations[ $site_id ]; ?>
							<option value="<?php echo esc_attr( $item['ID'] ); ?>" selected="selected"><?php echo esc_html( $item['post_title'] ); ?></option>
							<?php unset( $item ); ?>
						<?php endif; ?>
					</select>
					<span class="wpsl-multilang-remove-translation"></span>
					<span class="wpsl-multilang-duplicate"><?php esc_html_e( 'Duplicate', 'wpsl-multilang' ); ?></span>
					<span class="wpsl-multilang-duplicate-wrapper">
						<span class="wpsl-multilang-duplicate-confirm"><?php esc_html_e( 'Duplicate this post to another with all data and meta?', 'wpsl-multilang' ); ?></span>
						<span class="wpsl-multilang-duplicate-yes" <?php echo $custom_data; ?>><?php esc_html_e( 'Yes!', 'wpsl-multilang' ); ?></span> <span class="wpsl-multilang-duplicate-no"><?php esc_html_e( 'No!', 'wpsl-multilang' ); ?></span>
					</span>
				</span>

				<?php endif; ?>

			</label>
			</div>
		<?php endforeach; ?>

	<?php endif; ?>
	<?php if ( $object_id && $object_blog_id ) : ?>
		<input type="hidden" value="<?php echo esc_attr( $object_id ); ?>" name="translations[object_id]"/>
		<input type="hidden" value="<?php echo esc_attr( $object_blog_id ); ?>" name="translations[object_blog_id]"/>
	<?php endif; ?>
</div>
