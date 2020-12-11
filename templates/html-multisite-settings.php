<?php
/** @var WPSL\MultiLang\Plugin $plugin */

/**
 * @var \WPSL\MultiLang\Integration\Integration $plugin;
 */
$plugin = $args['plugin'];

/**
 * @var \WPSL\MultiLang\Integration\MenuPage $parent;
 */
$parent = $args['parent'];

/**
 * @var \WPSL\MultiLang\Integration\NetworkOptions $settings;
 */
$settings = $args['settings'];
?>
<form method="post">
	<?php wp_nonce_field( 'save_settings', 'wpslml_settings_field' ); ?>
	<div class="wrap">
		<h2><?php esc_html_e( 'Settings' ); ?></h2>
		<div id="postbody">

			<table class="form-table">
				<tbody>
					<tr>
						<th><h4><?php esc_html_e( 'Languages', 'wpsl-multilang' ); ?></h4></th>
						<td>
							<table class="wp-list-table widefat fixed striped table-view-list posts">
								<thead>
								<tr>
									<td id="cb">Active</td id="cb">
									<th><?php esc_html_e( 'Site Title', 'wpsl-multilang' ); ?></th>
									<th><?php esc_html_e( 'Site URL', 'wpsl-multilang' ); ?></th>
									<th style="width: 80%"><?php esc_html_e( 'Language', 'wpsl-multilang' ); ?></th>
								</tr>
								</thead>
								<tbody>
								<?php foreach ( $plugin->get_blog_sites_id() as $blog_id => $url ) : ?>
								<tr>
									<th width="10"><input type="checkbox" value="yes" /></th>
									<?php
									$siteurl   = get_blog_option( $blog_id, 'siteurl', 'en' );
									$lang      = $plugin->get_full_lang( $blog_id );
									$blog_name = get_blog_option( $blog_id, 'blogname', 'en' );
									?>
									<td class="title column-title has-row-actions column-primary page-title">
										<label for="blog-lang-<?php echo esc_attr( $blog_id ); ?>"><?php echo esc_html( $blog_name ); ?></label>
									</td>
									<td><?php echo esc_html( $siteurl ); ?></td>
									<td>
									<select id="blog-lang-<?php echo esc_attr( $blog_id ); ?>"
											name="wpslmu_settings[multisite_languages][<?php echo esc_attr( $blog_id ); ?>]">
										<?php echo $parent->language_select_options( esc_attr( $lang ) ); ?>
									</select>
									</td>
								</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<th>
							<h4><?php esc_html_e( 'Select type', 'wpsl-multilang' ); ?></h4>
						</th>
						<td>
							<?php $select_type_value = $settings->get( 'select_type', 2 ); ?>
							<select name="wpslmu_settings[select_type]">
								<option value="1" <?php selected( 1, $select_type_value, true ); ?>><?php esc_html_e( 'Select', 'wpsl-multilang' ); ?></option>
								<option value="2" <?php selected( 2, $select_type_value, true ); ?>><?php esc_html_e( 'Select 2', 'wpsl-multilang' ); ?></option>
								<option value="3" <?php selected( 3, $select_type_value, true ); ?>><?php esc_html_e( 'Select 2 (AJAX)', 'wpsl-multilang' ); ?></option>
							</select>
							<div class="wpsl-description"><?php esc_html_e( 'Select which type of select you want do have in edit page.', 'wpsl-multilang' ); ?></div>
						</td>
					</tr>
					<tr>
						<th>
							<h4><label for="post_in_select"></label><?php esc_html_e( 'Number of post in select', 'wpsl-multilang' ); ?><label></label></h4>
						</th>
						<td>
							<input id="post_in_select" type="number" name="wpslmu_settings[post_in_select]" value="<?php echo esc_attr( (int) $settings->get( 'post_in_select', 50 ) ); ?>" min="5" max="100" size="14">
						</td>

					</tr>
					<tr>
						<th>
							<h4><?php esc_html_e( 'Exclude Post Types', 'wpsl-multilang' ); ?></h4>
						</th>
						<td>
							<?php echo $parent->post_type_select(); // phpcs:ignore ?>
							<div class="wpsl-description"><?php esc_html_e( 'Exclude post types for translation.', 'wpsl-multilang' ); ?></div>
						</td>

					</tr>

					<tr>
						<th>
							<h4><?php esc_html_e( 'Attachments', 'wpsl-multilang' ); ?></h4>
						</th>
						<td>
							<?php
							$checked = $settings->get( 'synchronize_attachments:int', 0 ) === 1 ? 'checked="checked"' : '';
							?>
							<p>
								<label>
									<input <?php echo $checked; ?> type="checkbox" name="wpslmu_settings[synchronize_attachments]" value="1" /> <?php _e( 'Synchronize attachments', 'wpsl-multilang' ); ?>
								</label>
							</p>
						</td>

					</tr>
				</tbody>


			</table>
			<?php submit_button(); ?>
		</div>
	</div>
</form>
