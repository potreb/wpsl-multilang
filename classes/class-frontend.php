<?php
/**
 * Frontend class
 *
 * @package    MultiLang
 * @subpackage \WPSL\MultiLang
 * @since      1.0
 */

namespace WPSL\MultiLang;

/**
 * Class Frontend
 */
class Frontend {

	/**
	 * \WPSL\MultiLang\Plugin reference
	 *
	 * @var \WPSL\MultiLang\Plugin
	 */
	private $plugin;

	/**
	 * Sync constructor.
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
		add_shortcode( 'mls_language', array( $this, 'html_list_flags' ) );
	}

	/**
	 * Generate HTML list with active links to blogs
	 *
	 * @param string $list_type Type of HTML list.
	 *
	 * @return string
	 */
	public function html_list_flags( $list_type = false ) {
		$languages = wpsl_multilang_languages_list();
		$output    = '<ul id="mls-language-list" class="mls_' . sanitize_key( $list_type ) . '">';
		$domains   = $this->plugin->get_blog_sites_domains();

		$current_blog_id = $this->plugin->get_current_blog_id();

		foreach ( $this->plugin->get_multisite_languages( false ) as $blog_id => $lang ) {

			$url     = isset( $domains[ $blog_id ] ) ? esc_url( $domains[ $blog_id ] ) : 0;
			$code    = sanitize_key( $this->plugin->get_short_lang( $blog_id ) );
			$name    = $languages[ $lang ]['name'];
			$title   = '<span class="screen-reader-text sr-only lang-name-' . $code . '">' . $name . '</span> ';
			$current = $current_blog_id === $blog_id ? 'current' : '';

			if ( 'list_with_flags' === $list_type ) {
				$img = '<img src="' . esc_url( $this->plugin->get_assets( 'images/flags' ) ) . $code . '.png" alt="' . esc_attr( $name ) . '"> ';
			} else {
				$img = '';
			}
			$list    = sprintf( '<li><a class="mls-lang-%1$s ' . $current . '" href="%2$s">%3$s%4$s</a></li>', $code, $url, $img, $title );
			$output .= apply_filters( 'multi_languages_html_language_list_item', $list, $code, $url, $img, $title );
		}
		$output .= '</ul>';
		return $output;
	}

}
