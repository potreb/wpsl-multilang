<?php

/**
 * Plugin main class.
 *
 * @package    MultiLang
 * @subpackage \WPSL\MultiLang
 * @since      1.0
 */

namespace WPSL\MultiLang;

use WPSL\MultiLang\Integration\Database;
use \WPSL\MultiLang\Integration\Integration;
use \WPSL\MultiLang\Integration\Ajax;
use \WPSL\MultiLang\Integration\MenuPage;
use \WPSL\MultiLang\Integration\Attachments;
use WPSL\MultiLang\Integration\NetworkOptions;
use \WPSL\MultiLang\Integration\Posts;
use WPSL\MultiLang\Integration\Renderer;
use \WPSL\MultiLang\Integration\Widget;
use \WPSL\MultiLang\Integration\Frontend;

/**
 * Main WPSL_MultiLang Class.
 *
 * @class MultiLanguages
 */
class Plugin extends AbstractPlugin {

	/**
	 * @var Integration
	 */
	private $integration;

	/**
	 * Plugin
	 *
	 * @var Frontend
	 */
	private $frontend;

	/**
	 * MultiLanguages Constructor.
	 */
	public function __construct( $plugin_info ) {
		parent::__construct( $plugin_info );
	}

	/**
	 * Init hooks
	 */
	public function hooks() {
		if ( is_multisite() ) {
			parent::hooks();

			$database = new Database();

			$settings = new NetworkOptions();
			$renderer = new Renderer( $this->get_template_dir() );

			$this->integration = new Integration( $settings, $this->get_assets_url() );

			$ajax = new Ajax( $this->integration );
			$ajax->hooks();

			$menu_page = new MenuPage( $this->integration, $settings, $renderer, $this->get_assets_url() );
			$menu_page->hooks();

			if ( empty( $this->integration->get_multisite_languages() ) ) {
				add_action( 'admin_notices', array( $this, 'admin_notice_no_defined_languages' ), 1 );
			}

			$this->frontend = new Frontend( $this->integration );
			$this->frontend->hooks();

			$posts = new Posts( $this->integration, $settings, $renderer, $this->get_assets_url() );
			$posts->hooks();

			$posts = new Attachments( $this->integration );
			$posts->hooks();

			add_action( 'widgets_init', array( $this, 'multi_languages_widget' ) );
		}
	}

	/**
	 * Add notice if plugin is not configure.
	 */
	public function admin_notice_no_defined_languages() {
		$settings_url = network_admin_url( 'admin.php?page=' . MenuPage::MENU_SLUG );
		$current_user = wp_get_current_user();
		$allowed_tags = wp_kses_allowed_html();

		printf(
		/* translators: 1: user name 2: settings url */
			'<div class="notice notice-error is-dismissible"><p>' . wp_kses( __( 'Hello %1$s. Thank you for using our plugin. Go to the plugin <a href="%2$s">settings to add languages</a>.', 'wpsl-multilang' ), $allowed_tags ) . ' </p></div>',
			esc_html( $current_user->display_name ),
			esc_url( $settings_url )
		);
	}

	/**
	 * Register language widget.
	 */
	public function multi_languages_widget() {
		$widget = new Widget( $this->integration, $this->frontend );
		register_widget( $widget );
	}

	/**
	 * Add links to plugin on plugins page.
	 *
	 * @param array $links Links array.
	 *
	 * @return array
	 */
	public function links_filter( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'wp-admin/network/admin.php?page=' . MenuPage::MENU_SLUG ) . '">' . __( 'Settings' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add links to plugin on multisite plugins page.
	 *
	 * @param array $links Links array.
	 *
	 * @return array
	 */
	public function multisite_links_filter( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'wp-admin/network/admin.php?page=' . MenuPage::MENU_SLUG ) . '">' . __( 'Settings' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Admin enqueue scripts
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css', array(), '3.5.3' );
		wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array( 'jquery' ), '3.5.3', true );

		wp_register_style( $this->plugin_text_domain, $this->get_assets_url() . 'css/admin.css', false, $this->get_scripts_version() );
		wp_enqueue_style( $this->plugin_text_domain );

		wp_register_script( $this->plugin_text_domain, $this->get_assets_url() . 'js/admin.js', array( 'jquery' ), $this->get_scripts_version(), true );
		$translation_array = array(
			'before_duplicate' => __( 'Before duplicate, remove translation from select', 'wpsl-mulilangual' ),
		);
		wp_localize_script( $this->plugin_text_domain, 'wpsl_multilang', $translation_array );
		wp_enqueue_script( $this->plugin_text_domain );
	}


}
