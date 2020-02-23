<?php
/**
 * WPSL_Plugin class
 *
 * @package MultiLang
 * @since   1.0
 */

/**
 * Class WPSL_Plugin
 */
class AbstractPlugin
{

	/**
	 * Plugin url
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Plugin url
	 *
	 * @var string
	 */
	public $plugin_dir;

	/**
	 * Plugin url
	 *
	 * @var string
	 */
	public $plugin_assets;

	/**
	 * Plugin text domain
	 *
	 * @var string
	 */
	public $plugin_text_domain;

	/**
	 * Plugin template path
	 *
	 * @var string
	 */
	public $plugin_template;

	/**
	 * Scripts version
	 *
	 * @var string
	 */
	public $scripts_version = '0.1';

	/**
	 * WPSL_Plugin constructor.
	 *
	 * @param string $file Parent path to file.
	 */
	public function __construct( $file ) {
		$this->init_base_variables( $file );
	}

	/**
	 * Init base variables
	 *
	 * @param string $file Plugin file.
	 */
	public function init_base_variables( $file ) {
		$this->plugin_file        = plugin_basename( $file );
		$this->plugin_url         = plugin_dir_url( $file );
		$this->plugin_dir         = plugin_dir_path( $file );
		$this->plugin_assets      = $this->plugin_assets();
		$this->plugin_template    = $this->plugin_templates();
		$this->plugin_text_domain = basename( $this->plugin_dir );
		$this->plugin_settings    = '#';
	}

	/**
	 * Fires hooks
	 */
	public function hooks() {
		add_action( 'plugins_loaded', [ $this, 'load_plugin_text_domain' ], 999 );
		add_action( 'init', array( $this, 'detect_user_language' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10 );
		add_filter( 'plugin_action_links_' . $this->plugin_file, [ $this, 'links_filter' ] );
		if ( is_multisite() ) {
			add_filter( 'network_admin_plugin_action_links_' . $this->plugin_file, [ $this, 'multisite_links_filter' ] );
		}
	}

	/**
	 * Detect user language and redirect to correct website
	 *
	 * @return string
	 */
	public function detect_user_language() {
		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$lang = substr( sanitize_key( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ), 0, 2 );

			return $lang;
		}

		return null;
	}

	/**
	 * Define assets
	 *
	 * @return mixed
	 */
	private function plugin_assets() {
		return trailingslashit( $this->plugin_url . 'assets' );
	}

	/**
	 * Define assets
	 *
	 * @return mixed
	 */
	private function plugin_templates() {
		return trailingslashit( $this->plugin_dir . 'templates' );
	}

	/**
	 * Get assets
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	public function get_assets( $path = '' ) {
		return trailingslashit( $this->plugin_assets . $path );
	}

	/**
	 * Enqueue script
	 */
	public function admin_enqueue_scripts() {

	}

	/**
	 * @return void
	 */
	public function load_plugin_text_domain() {
		load_plugin_textdomain( $this->plugin_text_domain, false, $this->plugin_text_domain . '/languages/' );
	}

	/**
	 * Get view template
	 *
	 * @param string $filename Filename.
	 * @param array  $args     Arguments.
	 */
	public function get_template( $filename, array $args ) {
		$template = $this->plugin_template . $filename . '.php';
		if ( file_exists( $template ) ) {
			require_once $template;
		}
	}

	/**
	 * Multisite action links filter
	 *
	 * @param array $links Plugin links.
	 *
	 * @return mixed
	 */
	public function multisite_links_filter( $links ) {
		return $links;
	}

	/**
	 * Action links filter
	 *
	 * @param array $links Plugin links.
	 *
	 * @return mixed
	 */
	public function links_filter( $links ) {
		return $links;
	}

}
