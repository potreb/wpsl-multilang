<?php
/**
 * Attachments class
 *
 * @package    MultiLanguages
 * @subpackage \WPSL\MultiLang
 * @since      1.0
 */

namespace WPSL\MultiLang;

/**
 * Class Attachments
 */
class Attachments {

	/**
	 * \WPSL\MultiLang\Plugin reference
	 *
	 * @var \WPSL\MultiLang\Plugin
	 */
	private $plugin;

	/**
	 * Attachments constructor.
	 *
	 * @param \WPSL\MultiLang\Plugin $plugin Plugin reference.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Fires hooks
	 */
	public function hooks() {
/*		add_action( 'add_attachment', [ $this, 'synchronize_attachment_data_between_sites' ], 10, 3 );
		add_filter( 'upload_dir', array( $this, 'set_new_multisite_upload_dir' ) );
		add_filter( 'delete_attachment', array( $this, 'delete_translation' ), 10, 2 );
		add_filter( 'admin_post_thumbnail_html', array( $this, 'add_post_thumbnail_synchronize_checkbox' ), 10, 2 );
		add_filter( 'save_post', array( $this, 'set_thumbnail_to_other_translations' ), 10, 2 );*/
	}

	/**
	 * Add checkbox to post thumbnail meta box for set thumbnail to other sites.
	 *
	 * @param string $content HTML output.
	 *
	 * @return string
	 */
	public function add_post_thumbnail_synchronize_checkbox( $content ) {
		$content .= '<div><label for="thumbnail-synchronize"><input id="thumbnail-synchronize" type="checkbox" name="thumbnail_synchronize" value="1" />' . __( 'Set the thumbnail for other translations!', 'wpsl-multilang' ) . '</label></div>';
		return $content;
	}

	/**
	 * Change upload dirs.
	 *
	 * @param array $dirs Upload dirs.
	 *
	 * @return array
	 */
	public function set_new_multisite_upload_dir( array $dirs ) {
		$dirs['baseurl'] = network_site_url( '/wp-content/uploads' );
		$dirs['basedir'] = ABSPATH . 'wp-content/uploads';
		$dirs['path']    = $dirs['basedir'] . $dirs['subdir'];
		$dirs['url']     = $dirs['baseurl'] . $dirs['subdir'];

		return $dirs;
	}

	/**
	 * Synchronize meta between sites
	 *
	 * @param int $post_id Posi ID.
	 */
	public function synchronize_attachment_data_between_sites( $post_id ) {
		$data = (array) get_post( $post_id );
		$relation_id = WPSL\MultiLang\Relations::get_new_relation_id();

		$blog_sites_id  = $this->plugin->get_multisite_languages( true );
		$parent_blog_id = $this->plugin->get_current_blog_id();
		$upload_dir     = wp_upload_dir();
		unset( $data['ID'] );

		WPSL\MultiLang\Relations::insert( 'attachment', $post_id, $relation_id, $this->plugin->get_current_blog_id(), 1 );
		$attached_file = get_post_meta( $post_id, '_wp_attached_file', true );
		add_post_meta( $post_id, \WPSL\MultiLang\Plugin::RELATION_META_KEY, $relation_id );

		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( $blog_sites_id as $blog_id => $lang ) {
			// Must remove action because WP create infinity loop.
			remove_action( 'add_attachment', array( $this, 'synchronize_attachment_data_between_sites' ) );

			switch_to_blog( $blog_id );
			$attach_id = wp_insert_attachment( $data );
			add_post_meta( $attach_id, \WPSL\MultiLang\Plugin::RELATION_META_KEY, $relation_id );

			if ( $attached_file ) {
				add_post_meta( $attach_id, '_wp_attached_file', $attached_file );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_dir['basedir'] . '/' . $attached_file );
				wp_update_attachment_metadata( $attach_id, $attach_data );
			}

			WPSL\MultiLang\Relations::insert( 'attachment', $attach_id, $relation_id, $blog_id, $post_id );
			restore_current_blog();

			add_action( 'add_attachment', array( $this, 'synchronize_attachment_data_between_sites' ), 10, 2 );
		}
	}

	/**
	 * Delete translation
	 * If the file exists on the other pages, it will not be deleted.
	 *
	 * @param int $post_id Post ID.
	 */
	public function delete_translation( int $post_id ) {
		$relation_id  = WPSL\MultiLang\Relations::get_element_relation_id( $post_id, $this->plugin->get_current_blog_id(), 'attachment' );
		$count = WPSL\MultiLang\Relations::get_translations_count_by_relation_id( $relation_id );

		/* Do not delete the file if it is on the other pages */
		if ( $count > 1 ) {
			add_filter( 'wp_delete_file', '__return_empty_string' );
		}
		$blog_id = $this->plugin->get_current_blog_id();
		WPSL\MultiLang\Relations::delete( $post_id, $blog_id );
	}

	/**
	 * Set thumbnail to other translations
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post_object Post object.
	 */
	public function set_thumbnail_to_other_translations( $post_id, $post_object ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( in_array( $post_object->post_type, array( 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'user_request' ), true ) ) {
			return;
		}

		if ( ! $post_id || in_array( $post_object->post_status, array( 'auto-draft', 'inherit', 'trash' ), true ) ) {
			return;
		}

		//phpcs:disable
		if ( ! empty( $_POST ) && isset( $_POST['thumbnail_synchronize'] ) ) {
			$_thumbnail_id = isset( $_POST['_thumbnail_id'] ) ? (int) $_POST['_thumbnail_id'] : null;
			$this->set_thumbnail( $post_id, $_thumbnail_id );
		}
		//phpcs:enable

	}

	/**
	 * Set thumbnail to post
	 *
	 * @param int $post_id Post ID.
	 * @param int $_thumbnail_id Attachment ID.
	 *
	 * @see set_thumbnail_to_other_translations
	 */
	private function set_thumbnail( int $post_id, int $_thumbnail_id ) {
		if ( $post_id && $_thumbnail_id ) {
			$translation_attachments = ( new WPSL\MultiLang\Relations( $_thumbnail_id, 'attachment' ) )->get_blog_translations();
			unset( $translation_attachments[ $this->plugin->get_current_blog_id() ] );

			$translation_posts = ( new WPSL\MultiLang\Relations( $post_id, 'post' ) )->get_blog_translations();
			unset( $translation_posts[ $this->plugin->get_current_blog_id() ] );

			foreach ( $translation_posts as $blog_id => $translation_post ) {
				remove_action( 'save_post', array( $this, 'set_thumbnail_to_other_translations' ) );
				switch_to_blog( $blog_id );
				$translation_post_id   = isset( $translation_post['element_id'] ) ? $translation_post['element_id'] : '';
				$translation_attach_id = isset( $translation_attachments[ $blog_id ]['element_id'] ) ? $translation_attachments[ $blog_id ]['element_id'] : null;
				if ( $translation_post_id && $translation_attach_id ) {
					update_post_meta( $translation_post_id, '_thumbnail_id', $translation_attach_id );
				}
				restore_current_blog();
				add_action( 'save_post', array( $this, 'set_thumbnail_to_other_translations' ) );
			}
		}
	}

}
