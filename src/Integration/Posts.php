<?php
/**
 * Posts
 *
 * @package MultiLang
 * @since   1.0.0
 */

namespace WPSL\MultiLang\Integration;

/**
 * Class Posts
 */
class Posts {

	/**
	 * @var Integration
	 */
	private $integration;

	/**
	 * @var NetworkOptions
	 */
	private $settings;

	/**
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * @var string
	 */
	private $assets_url;

	/**
	 * @param Integration    $integration
	 * @param NetworkOptions $settings
	 * @param Renderer       $renderer
	 * @param string         $assets_url
	 */
	public function __construct( Integration $integration, NetworkOptions $settings, Renderer $renderer, string $assets_url ) {
		$this->integration = $integration;
		$this->settings    = $settings;
		$this->renderer    = $renderer;
		$this->assets_url  = $assets_url;
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
		$post_types = get_post_types( array( 'public' => true ), 'names' );
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
		$screens = $this->settings->get( 'supported_post_types', array( 'post', 'page' ) );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'MultiLanguages_post_box',
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
		foreach ( $this->integration->get_multisite_languages( true ) as $blog_id => $lang ) {
			$url    = $this->integration->blog_sites_domains[ $blog_id ] . 'wp-admin/edit.php?post_type=' . $post_type;
			$output .= sprintf( '<a href="' . $url . '">%s</a>', $this->integration->get_language_icon_html( $blog_id ) );
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
				foreach ( $this->integration->get_multisite_languages( true ) as $blog_id => $lang ) {
					if ( ! isset( $post_rel[ $blog_id ] ) && isset( $this->integration->blog_sites_domains[ $blog_id ] ) ) {
						$this->new_post_link( $blog_id, $this->integration->get_current_blog_id(), $post_id );
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
		global $post_type;
		$domain = $this->integration->blog_sites_domains[ $object_blog_id ];
		$url    = add_query_arg(
			array(
				'post_type'      => $post_type,
				'object_blog_id' => $post_blog_id,
				'object_id'      => $post_id,
			),
			$domain . 'wp-admin/post-new.php'
		);
		$icon   = esc_url( trailingslashit( $this->assets_url . 'images' ) . 'add.svg' );
		printf( '<a href="%s" title="' . esc_attr__( 'Add translation', 'wpsl-mulilang' ) . '"><img src="%s" width="18" alt="add"></a> ', esc_url( $url ), $icon );
	}

	/**
	 * Render edit link for language column
	 *
	 * @param array $post_id Post ID.
	 * @param int   $blog_id Blog ID.
	 */
	private function edit_post_link( $post_id, $blog_id ) {
		$domain = $this->integration->blog_sites_domains[ $blog_id ];
		$url    = add_query_arg(
			array(
				'post'   => $post_id,
				'action' => 'edit',
			),
			$domain . 'wp-admin/post.php'
		);

		$icon   = esc_url( trailingslashit( $this->assets_url . 'images' ) . 'edit.svg' );
		printf( '<a href="%1$s" title="' . esc_attr__( 'Edit translation', 'wpsl-mulilang' ) . '"><img src="%2$s" width="18" alt="add"></a> ', esc_url( $url ), $icon );
	}

	/**
	 * Display language meta box box content
	 *
	 * @param object $post Post object.
	 */
	public function multi_languages_meta_box_content( $post ) {
		wp_nonce_field( 'multi-languages-post', 'multi-languages-nonce-post' );
		$translations = $this->get_translation_posts( $post->ID );
		$this->renderer->get_template(
			'html-select-language',
			array(
				'translations' => $translations,
				'plugin'       => $this->integration,
			)
		);
	}

	/**
	 * Get relations with post data
	 *
	 * @return array
	 */
	public function get_translation_with_posts( $translation_posts ) {
		$posts = [];
		$blogs = $this->integration->get_blog_sites_id();
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

		$rel = isset( $_POST['translations'] ) ? $_POST['translations'] : null; //phpcs:ignore

		if ( isset( $rel['object_id'] ) && $rel['object_blog_id'] ) {
			Relations::insert(
				(int) $post_id,
				(int) $this->integration->get_current_blog_id(),
				(int) $rel['object_id'],
				(int) $rel['object_blog_id']
			);
			Relations::insert(
				(int) $rel['object_id'],
				(int) $rel['object_blog_id'],
				(int) $post_id,
				(int) $this->integration->get_current_blog_id()
			);
		}

		return $post_id;
	}

	/**
	 * Delete translation for current post ID
	 *
	 * @param int $post_id Post ID.
	 */
	public function delete_translation( int $post_id ) {
		$blog_id = $this->integration->get_current_blog_id();
		Relations::delete_post_relations( $post_id );
	}

}
