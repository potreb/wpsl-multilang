<?php
/**
 * Relations class
 *
 * @package    MultiLang
 * @subpackage \WPSL\MultiLang
 * @since      1.0
 */

namespace WPSL\MultiLang;

/**
 * Class to retrieve all posts relations
 */
class Relations {

	/**
	 * Translations data
	 *
	 * @var array
	 */
	public $posts;

	/**
	 * MultiLanguagesTranslations constructor.
	 *
	 * @param int $post_id Post ID.
	 */
	public function __construct( int $post_id ) {
		$this->posts = $this->get_relations( $post_id );
	}

	/**
	 * Get translation from DB
	 *
	 * @param int $object_id Post ID.
	 *
	 * @return array
	 */
	private function get_relations( int $object_id ) {
		global $wpdb;
		$relations = wp_cache_get( 'relations_' . $object_id );
		if ( false === $relations ) {
			$relations = [];
			// phpcs:ignore
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->mslt} WHERE `post_id` = %d AND `post_blog_id` = %d", $object_id, get_current_blog_id() ), ARRAY_A );
			foreach ( $results as $row ) {
				$relations[] = $row;
			}
			wp_cache_set( 'relations_' . $object_id, $relations );
		}

		return $relations;
	}

	/**
	 * Get post relations.
	 *
	 * @return array
	 */
	public function get_post_relations() {
		$data = [];
		foreach ( $this->posts as $id => $row ) {
			$data[ $row['object_blog_id'] ] = $row['object_id'];
		}
		return $data;
	}

	/**
	 * Delete exists relation with this same object ID for the same blog ID.
	 *
	 * @param int $object_id      Object ID.
	 * @param int $object_blog_id Object blog ID.
	 * @param int $post_blog_id   Post blog ID.
	 */
	private static function delete_exists_relation( $object_id, $object_blog_id, $post_blog_id ) {
		global $wpdb;

		// phpcs:ignore
		$has_relation = $wpdb->get_var(  "SELECT object_id FROM {$wpdb->mslt} WHERE object_id = {$object_id} AND object_blog_id = {$object_blog_id} AND post_blog_id = {$post_blog_id}" );

		if ( ! $has_relation ) {
			return false;
		}
		// phpcs:ignore
		$wpdb->delete(
			$wpdb->mslt,
			array(
				'object_id'      => $object_id,
				'object_blog_id' => $object_blog_id,
				'post_blog_id'   => $post_blog_id,
			),
			array(
				'%d',
				'%d',
				'%d',
			)
		);
		return true;
	}

	/**
	 * Insert post relation
	 *
	 * @param int $object_id      Object ID.
	 * @param int $object_blog_id Object blog ID.
	 * @param int $post_id        Post ID.
	 * @param int $post_blog_id   Post blog ID.
	 * @param int $sync           Synchronize media.
	 *
	 * @return int
	 */
	public static function insert(
		int $object_id,
		int $object_blog_id,
		int $post_id,
		int $post_blog_id,
		$sync = 0
	) {
		global $wpdb;

		if ( empty( $object_id ) || empty( $object_blog_id ) || empty( $post_id ) || empty( $post_blog_id ) ) {
			return false;
		}

		if ( 0 === $post_id || 0 === $post_blog_id ) {
			return false;
		}

		self::delete_exists_relation( $object_id, $object_blog_id, $post_blog_id );
		$wpdb->show_errors();
		// phpcs:ignore
		$insert = $wpdb->replace(
			$wpdb->mslt,
			[

				'object_id'      => $object_id,
				'object_blog_id' => $object_blog_id,
				'post_id'        => $post_id,
				'post_blog_id'   => $post_blog_id,
				'sync'           => $sync,
			],
			[
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
			]
		);
		$wpdb->print_error();
		return $insert;
	}

	/**
	 * Delete relations
	 *
	 * @param int $object_id      Object ID.
	 * @param int $object_blog_id Object blog ID.
	 * @param int $post_id        Post ID.
	 * @param int $post_blog_id   Post blog ID.
	 *
	 * @return bool
	 */
	public static function delete( int $object_id, int $object_blog_id, int $post_id, int $post_blog_id ) {
		global $wpdb;
		// phpcs:ignore
		$wpdb->delete(
			$wpdb->mslt,
			array(
				'object_id'      => $object_id,
				'object_blog_id' => $object_blog_id,
				'post_id'        => $post_id,
				'post_blog_id'   => $post_blog_id,
			),
			array(
				'%d',
				'%d',
				'%d',
				'%d',
			)
		);

		return true;
	}

	/**
	 * Delete post relation without object_id
	 *
	 * @param int $object_blog_id Object blog ID.
	 * @param int $post_id        Post ID.
	 * @param int $post_blog_id   Post blog ID.
	 *
	 * @return bool
	 */
	public static function delete_without_object_id( int $object_blog_id, int $post_id, int $post_blog_id ) {
		global $wpdb;
		// phpcs:ignore
		$wpdb->delete(
			$wpdb->mslt,
			array(
				'object_blog_id' => $object_blog_id,
				'post_id'        => $post_id,
				'post_blog_id'   => $post_blog_id,
			),
			array(
				'%d',
				'%d',
				'%d',
			)
		);

		return true;
	}

	/**
	 * Delete relations when post deleted.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public static function delete_post_relations( int $post_id ) {
		global $wpdb;

		// phpcs:ignore
		$wpdb->delete(
			$wpdb->mslt,
			array(
				'post_id'      => $post_id,
				'post_blog_id' => get_current_blog_id(),
			),
			array(
				'%d',
				'%d',
			)
		);

		// phpcs:ignore
		$wpdb->delete(
			$wpdb->mslt,
			array(
				'object_id'      => $post_id,
				'object_blog_id' => get_current_blog_id(),
			),
			array(
				'%d',
				'%d',
			)
		);

		return true;
	}

}
