<?php
/**
 * MenuPage
 *
 * @package MultiLang
 * @since   1.0.0
 */

namespace WPSL\MultiLang\Integration;

/**
 * Class MenuPage
 */
class MenuPage {

	/**
	 * Language codes
	 *
	 * @var $languages
	 */
	private $languages;

	/**
	 * Menu slug
	 */
	const MENU_SLUG = 'wpsl_multilang';

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
		$this->languages   = Helper::wpsl_multilang_languages_list();
	}

	/**
	 * Fires hooks
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'network_admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_footer', array( $this, 'replace_multisite_menu_blavatar' ) );
		add_action( 'wp_footer', array( $this, 'replace_multisite_menu_blavatar' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ), 10 );
	}

	/**
	 * Defined settings fields
	 *
	 * @return array
	 */
	private function defined_setting_fields() {
		return [
			'multisite_languages',
			'supported_post_types',
			'synchronize_attachments',
		];
	}

	/**
	 * Save languages for blogs
	 */
	public function save_settings() {
		if (
			isset( $_POST['multi-languages-nonce-settings'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['multi-languages-nonce-settings'] ) ), 'multi-languages-settings' )
		) {

			foreach ( $this->defined_setting_fields() as $setting ) {
				$value = $_POST[ NetworkOptions::SLUG ][ $setting ];
				$this->settings->set( $setting, $value );
			}
			wp_safe_redirect( admin_url( 'network/admin.php?page=' . self::MENU_SLUG ), 301 );
			exit;
		}
	}

	/**
	 * Add menu page to the network admin
	 *
	 * @return void
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'MultiLanguages', 'wpsl-multilang' ),
			__( 'MultiLanguages', 'wpsl-multilang' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'menu_page_wrapper' ),
			'dashicons-translation'
		);
	}

	/**
	 * Menu page content
	 *
	 * @return void
	 */
	public function menu_page_wrapper() {
		$this->renderer->get_template( 'html-multisite-settings', array(
			'plugin'   => $this->integration,
			'parent'   => $this,
			'settings' => $this->settings,
		) );
	}

	/**
	 * Generate output for language select
	 *
	 * @param string $lang Short language code.
	 *
	 * @return string
	 */
	public function language_select_options( $lang ) {
		$output = '<option></option>';
		foreach ( $this->languages as $code => $language ) {
			if ( isset( $language['name'] ) ) {
				$output .= '<option value="' . $code . '" ' . selected( $lang, $code, false ) . '>' . $language['name'] . ' (' . $code . ')</option>';
			}
		}

		return $output;
	}

	/**
	 * Generate output for language select
	 *
	 * @return string
	 */
	public function post_type_select() {
		$multiple   = true;
		$value      = $this->settings->get( 'supported_post_types', array( 'post', 'page' ) );
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$output     = '<select class="wpsl-multilangual-post-type-select" name="wpslmu_settings[supported_post_types][]" multiple="multiple">';
		foreach ( $post_types as $post_type_slug => $post_type_name ) {
			$obj = get_post_type_object( $post_type_slug );
			if ( $multiple ) {
				$selected = in_array( $post_type_slug, $value, true ) ? 'selected="selected"' : '';
			} else {
				$selected = selected( $post_type_slug, $value, false );
			}
			$output .= '<option value="' . $post_type_slug . '" ' . $selected . '>' . $obj->labels->singular_name . '</option>';
		}
		$output .= '</select>';

		return $output;
	}

	/**
	 * Replace the icon for multisite menu blog list
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function replace_multisite_menu_blavatar() {
		$this->renderer->get_template( 'html-style',
			array(
				'plugin'     => $this->integration,
				'parent'     => $this,
				'settings'   => $this->settings,
				'assets_url' => $this->assets_url,
			)
		);
	}

}
