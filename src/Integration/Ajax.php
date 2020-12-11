<?php
/**
 * Ajax class
 *
 * @package    MultiLang
 * @subpackage \WPSL\MultiLang
 * @since      1.0.0
 */

namespace WPSL\MultiLang\Integration;

/**
 * Class Ajax
 */
class Ajax {

	/**
	 * @var Integration
	 */
	private $integration;

	/**
	 * @param Integration $integration
	 */
	public function __construct( Integration $integration ) {
		$this->integration = $integration;
	}

	/**
	 * Fires hooks
	 */
	public function hooks() {
		add_action( 'wp_ajax_wpsl_search_translation_post', array( $this, 'wp_ajax_wpsl_search_translation_post' ) );
		add_action( 'wp_ajax_wpsl_insert_translation', array( $this, 'insert_translation' ) );
		add_action( 'wp_ajax_wpsl_remove_translation', array( $this, 'remove_translation' ) );
		add_action( 'wp_ajax_wpsl_duplicate_translation', array( $this, 'duplicate_translation' ) );
	}

	/**
	 * Search post for translation
	 */
	public function wp_ajax_wpsl_search_translation_post() {
		$attr         = wp_unslash( $_POST ); //phpcs:ignore
		$post_blog_id = (int) $attr['post_blog_id'];
		$term         = $attr['term'];
		$post_type    = $attr['post_type'];

		$blogs_id = $this->integration->get_blog_sites_id();

		if ( ! isset( $blogs_id[ $post_blog_id ] ) ) {
			wp_send_json_error( array( 'error' => '100' ) );
		}
		switch_to_blog( $post_blog_id );
		$args = array(
			'post_type' => $post_type,
			's'         => $term,
		);
		$data  = [];
		$query = get_posts( $args );
		foreach ( $query as $post ) {
			$data[] = [
				'id'   => $post->ID,
				'text' => $post->post_title,
			];
		}
		restore_current_blog();

		wp_send_json( array( 'items' => $data ) );
	}

	/**
	 * Insert new relation to site post
	 */
	public function insert_translation() {
		$attr = wp_unslash( $_POST );
		Relations::insert(
			(int) $attr['post_id'],
			(int) $attr['post_blog_id'],
			(int) $attr['object_id'],
			(int) $attr['object_blog_id']
		);
		Relations::insert(
			(int) $attr['object_id'],
			(int) $attr['object_blog_id'],
			(int) $attr['post_id'],
			(int) $attr['post_blog_id']
		);
		wp_send_json_success( 'saved', 200 );
	}

	/**
	 * Insert new relation to site post
	 */
	public function remove_translation() {
		$attr = wp_unslash( $_POST );
		Relations::delete(
			(int) $attr['post_id'],
			(int) $attr['post_blog_id'],
			(int) $attr['object_id'],
			(int) $attr['object_blog_id']
		);
		Relations::delete(
			(int) $attr['object_id'],
			(int) $attr['object_blog_id'],
			(int) $attr['post_id'],
			(int) $attr['post_blog_id']
		);
		wp_send_json_success( 'removed', 200 );
	}

	/**
	 * Duplicate post to another blog
	 */
	public function duplicate_translation() {
		$attr = wp_unslash( $_POST );
		$post = get_post( $attr['object_id'] );
		$meta = get_post_meta( $attr['object_id'] );
		switch_to_blog( $attr['post_blog_id'] );
		unset( $post->ID );
		$post_id  = wp_insert_post( $post );
		$new_post = get_post( $post_id );
		restore_current_blog();
		wp_send_json( array( 'post' => $new_post ), 200 );
	}

}
