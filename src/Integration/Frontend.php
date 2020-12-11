<?php
/**
 * Frontend class
 *
 * @package    MultiLang
 * @subpackage \WPSL\MultiLang
 * @since      1.0.0
 */

namespace WPSL\MultiLang\Integration;

/**
 * Class Frontend
 */
class Frontend {

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
		add_shortcode( 'mls_language', array( $this, 'html_list_flags' ) );
		add_action( 'wp_head', [ $this, 'wpsl_set_hreflang' ] );
	}

	/**
	 * Set hreflang.
	 */
	public function wpsl_set_hreflang() {
		global $post, $wpslml_languages;
		echo '<!-- WPSL Multilangual LangHrefs\ -->' . PHP_EOL;
		if ( is_home() ) {
			foreach ( $wpslml_languages as $blog_id => $blog ) {
				echo '<link rel="alternate" hreflang="' . $blog['code'] . '" href="' . $blog['domain'] . '" />' . PHP_EOL;
			}
		} else {
			if ( isset( $post->ID ) ) {
				$relations = new Relations( $post->ID );
				$rows      = $relations->get_post_relations();
				foreach ( $rows as $blog_id => $post_id ) {
					$code      = $wpslml_languages[ $blog_id ]['code'];
					$permalink = get_blog_permalink( $blog_id, $post_id );
					echo '<link rel="alternate" hreflang="' . $code . '" href="' . $permalink . '" />' . PHP_EOL;
				}
			}

		}
		echo '<!-- /WPSL Multilangual LangHrefs -->' . PHP_EOL;
	}

	/**
	 * Generate HTML list with active links to blogs
	 *
	 * @param string $list_type Type of HTML list.
	 *
	 * @return string
	 */
	public function html_list_flags( $list_type = false ) {
		$languages = Helper::wpsl_multilang_languages_list();
		$output    = '<ul id="mls-language-list" class="mls_' . sanitize_key( $list_type ) . '">';
		$domains   = $this->integration->get_blog_sites_domains();

		$current_blog_id = $this->integration->get_current_blog_id();

		foreach ( $this->integration->get_multisite_languages( false ) as $blog_id => $lang ) {

			$url     = isset( $domains[ $blog_id ] ) ? esc_url( $domains[ $blog_id ] ) : 0;
			$code    = sanitize_key( $this->integration->get_short_lang( $blog_id ) );
			$name    = $languages[ $lang ]['name'];
			$title   = '<span class="screen-reader-text sr-only lang-name-' . $code . '">' . $name . '</span> ';
			$current = $current_blog_id === $blog_id ? 'current' : '';

			if ( 'list_with_flags' === $list_type ) {
				$img = '<img src="' . esc_url( $this->integration->get_assets( 'images/flags' ) ) . $code . '.png" alt="' . esc_attr( $name ) . '"> ';
			} else {
				$img = '';
			}
			$list   = sprintf( '<li><a class="mls-lang-%1$s ' . $current . '" href="%2$s">%3$s%4$s</a></li>', $code, $url, $img, $title );
			$output .= apply_filters( 'multi_languages_html_language_list_item', $list, $code, $url, $img, $title );
		}
		$output .= '</ul>';

		return $output;
	}

}
