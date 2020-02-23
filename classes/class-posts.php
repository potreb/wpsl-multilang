<?php
/**
 * Posts class
 *
 * @package    MultiLang
 * @subpackage \WPSL\MultiLang
 * @since      1.0
 */

namespace WPSL\MultiLang;

/**
 * Class Posts
 */
class Posts {

	/**
	 * \WPSL\MultiLang\Plugin reference
	 *
	 * @var \WPSL\MultiLang\Plugin
	 */
	private $plugin;

	/**
	 * Posts constructor.
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
		add_filter( 'save_post', array( $this, 'save_translation' ), 10, 3 );
		add_filter( 'delete_post', array( $this, 'delete_translation' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'add_translation_column_to_post_type' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'add_multi_languages_meta_box' ) );
	}

	/**
	 * Add translation column to public post types.
	 */
	public function add_translation_column_to_post_type() {
		$post_types = $this->plugin->get_network_option( 'supported_post_types', array( 'post', 'page' ) );
		unset( $post_types['attachment'] );

		foreach ( $post_types as $name ) {
			add_filter( 'manage_' . $name . '_posts_columns', array( $this, 'post_column_header' ), 999 );
			add_action( 'manage_' . $name . '_posts_custom_column', array( $this, 'post_column_body' ), 10, 2 );
		}
	}

	/**
	 * Add language meta box in post admin
	 */
	public function add_multi_languages_meta_box() {
		$screens = $this->plugin->get_network_option( 'supported_post_types', array( 'post', 'page' ) );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'wpsl_multilang_post_box',
				__( 'Translations', 'wpsl-multilang' ),
				array( $this, 'multi_languages_meta_box_content' ),
				$screen
			);
		}
	}

	/**
	 * Post column header
	 *
	 * @param array $defaults Columns name.
	 *
	 * @return mixed
	 */
	public function post_column_header( $defaults ) {
		global $post_type;
		$output = '';
		foreach ( $this->plugin->get_multisite_languages( true ) as $blog_id => $lang ) {
			$url     = $this->plugin->blog_sites_domains[ $blog_id ] . 'wp-admin/edit.php?post_type=' . $post_type;
			$output .= sprintf( '<a href="' . $url . '">%s</a>', $this->plugin->get_language_icon_html( $blog_id ) );
		}

		foreach ( $defaults as $index => $name ) {
			$columns[ $index ] = $name;
			if ( 'title' === $index || ( 'product' === $post_type && 'name' === $index ) ) {
				$columns['language'] = $output;
			}
		}

		return $columns;
	}

	/**
	 * Post column body
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function post_column_body( $column, int $post_id ) {
		switch ( $column ) {
			case 'language':
				$relations = new Relations( $post_id );
				$post_rel  = $relations->get_post_relations();
				foreach ( $this->plugin->get_multisite_languages( true ) as $blog_id => $lang ) {
					if ( ! isset( $post_rel[ $blog_id ] ) && isset( $this->plugin->blog_sites_domains[ $blog_id ] ) ) {
						$this->new_post_link( $blog_id, $this->plugin->get_current_blog_id(), $post_id );
					} else {
						$this->edit_post_link( $post_rel[ $blog_id ], $blog_id );
					}
				}
				break;
		}
	}

	/**
	 * Render add link for language column
	 *
	 * @param int $object_blog_id Object blog ID.
	 * @param int $post_blog_id   Post blog ID.
	 * @param int $post_id        Post ID.
	 */
	private function new_post_link( int $object_blog_id, int $post_blog_id, int $post_id ) {
		/* translators: language name. */
		$title  = sprintf( __( 'Add translation for: %s', 'wpsl-multilang' ), $this->plugin->languages_info[ $object_blog_id ]['name'] );
		$domain = trailingslashit( $this->plugin->blog_sites_domains[ $object_blog_id ] );
		$url    = add_query_arg(
			array(
				'post_type'      => get_post_type( $post_id ),
				'object_blog_id' => $post_blog_id,
				'object_id'      => $post_id,
			),
			$domain . 'wp-admin/post-new.php'
		);
		printf( '<a href="%s" title="' . esc_attr( $title ) . '"><img src="%s" width="18" alt="add"></a> ', esc_url( $url ), esc_url( $this->plugin->get_assets( 'images' ) ) . 'add.svg' );
	}

	/**
	 * Render edit link for language column
	 *
	 * @param array $post_id Post ID.
	 * @param int   $blog_id Blog ID.
	 */
	private function edit_post_link( $post_id, $blog_id ) {
		/* translators: language name. */
		$title  = sprintf( __( 'Edit translation for: %s', 'wpsl-multilang' ), $this->plugin->languages_info[ $blog_id ]['name'] );
		$domain = trailingslashit( $this->plugin->blog_sites_domains[ $blog_id ] );
		$url    = add_query_arg(
			array(
				'post'   => $post_id,
				'action' => 'edit',
			),
			$domain . 'wp-admin/post.php'
		);
		printf( '<a href="%1$s" title="' . esc_attr( $title ) . '"><img src="%2$s" width="18" alt="add"></a> ', esc_url( $url ), esc_url( $this->plugin->get_assets( 'images' ) ) . 'edit.svg' );
	}

	/**
	 * Display language meta box box content
	 *
	 * @param object $post Post object.
	 */
	public function multi_languages_meta_box_content( $post ) {
		wp_nonce_field( 'multi-languages-post', 'multi-languages-nonce-post' );
		$translations = $this->get_translation_posts( $post->ID );
		require_once $this->plugin->plugin_template . 'html-select-language.php';
	}

	/**
	 * Get blog post option.
	 *
	 * @param int    $blog_id   Blog ID.
	 * @param string $post_type Post type.
	 *
	 * @return array
	 */
	private function blog_post_option( $blog_id, $post_type ) {
		switch_to_blog( $blog_id );
		global $wpdb;
		$posts_array = wp_cache_get( 'wpslml_option_' . $post_type . '_' . $blog_id );
		if ( false === $posts_array ) {
			$posts_array = [];
			// phpcs:ignore
			$limit = (int) $this->plugin->get_network_option( 'post_in_select', 50 );

			add_filter( 'posts_fields', array( $this, 'posts_fields_filter' ) );
			$args        = array(
				'posts_per_page'   => $limit,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'post_type'        => $post_type,
				'post_status'      => array( 'draft', 'publish' ),
				'suppress_filters' => false,
			);
			$posts_array = get_posts( $args );
			remove_filter( 'posts_fields', array( $this, 'posts_fields_filter' ) );
			wp_cache_set( 'wpslml_option_' . $post_type . '_' . $blog_id, $posts_array );
		}
		restore_current_blog();
		return $posts_array;
	}

	/**
	 * Add filter to post_fields to get only selected fields
	 *
	 * @param string $fields Fields for query select.
	 *
	 * @return string
	 */
	public function posts_fields_filter( $fields ) {
		global $wpdb;
		return "{$wpdb->posts}.ID, {$wpdb->posts}.post_title, {$wpdb->posts}.post_date, {$wpdb->posts}.post_date_gmt, {$wpdb->posts}.guid, {$wpdb->posts}.post_type, {$wpdb->posts}.post_status";
	}

	/**
	 * Get relations with post data
	 *
	 * @param array $translation_posts Translations posts.
	 *
	 * @return array
	 */
	public function get_translation_with_posts( $translation_posts ) {
		$posts = [];
		$blogs = $this->plugin->get_blog_sites_id();
		foreach ( $translation_posts as $blog_id => $element ) {
			if ( ! isset( $blogs[ $element['object_blog_id'] ] ) ) {
				continue;
			}
			switch_to_blog( $element['object_blog_id'] );
			$post = (array) get_post( $element['object_id'] );
			unset( $post['post_content'] );
			$posts[ $element['object_blog_id'] ] = $element + $post;
			restore_current_blog();
		}
		return $posts;
	}

	/**
	 * Get translation posts
	 *
	 * @param int $post_id Post ID.
	 */
	private function get_translation_posts( int $post_id ) {
		if ( 0 === $post_id ) {
			return [];
		}

		$translations = new Relations( (int) $post_id );
		$posts        = $this->get_translation_with_posts( $translations->posts );
		$row          = [];
		foreach ( $posts as $id => $entry ) {
			$row[ $entry['object_blog_id'] ] = [
				'ID'         => $entry['ID'],
				'post_title' => $entry['post_title'],
			];
		}

		return $row;

	}

	/**
	 * Save translation data for posts
	 *
	 * @param int    $post_id     Post ID.
	 * @param object $post_object Post object.
	 *
	 * @return $post_id
	 */
	public function save_translation( $post_id, $post_object ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return $post_id;
		}

		if (
		in_array(
			$post_object->post_type,
			array(
				'revision',
				'nav_menu_item',
				'custom_css',
				'customize_changeset',
				'user_request',
			),
			true
		)
		) {
			return $post_id;
		}

		if ( ! $post_id || in_array( $post_object->post_status, array( 'auto-draft', 'inherit', 'trash' ), true ) ) {
			return $post_id;
		}

		$translations = isset( $_POST['wpslml_post_translations'] ) ? $_POST['wpslml_post_translations'] : null; //phpcs:ignore

		if( ! $translations ) {
			return $post_id;
		}
		foreach ( $translations as $object_blog_id => $object_id ) {
			if ( empty( $object_id ) ) {
				Relations::delete_without_object_id(
					(int) $object_blog_id,
					(int) $post_id,
					(int) $this->plugin->get_current_blog_id()
				);
			} else {
				Relations::insert(
					(int) $post_id,
					(int) $this->plugin->get_current_blog_id(),
					(int) $object_id,
					(int) $object_blog_id
				);
				Relations::insert(
					(int) $object_id,
					(int) $object_blog_id,
					(int) $post_id,
					(int) $this->plugin->get_current_blog_id()
				);
			}

		}

		return $post_id;
	}

	/**
	 * Delete translation for current post ID
	 *
	 * @param int $post_id Post ID.
	 */
	public function delete_translation( int $post_id ) {
		$blog_id = $this->plugin->get_current_blog_id();
		Relations::delete_post_relations( $post_id );
	}

}
