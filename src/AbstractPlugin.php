<?php
/**
 * WPSL_Plugin class
 *
 * @package MultiLang
 * @since   1.0
 */

namespace WPSL\MultiLang;

/**
 * Class WPSL_Plugin
 */
class AbstractPlugin {

	/**
	 * @var array
	 */
	protected $plugin_info;

	/**
	 * Plugin url
	 *
	 * @var string
	 */
	protected $plugin_url;

	/**
	 * Plugin url
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * Plugin text domain
	 *
	 * @var string
	 */
	protected $plugin_text_domain;

	/**
	 * @var string
	 */
	protected $plugin_version;

	/**
	 * @param array $plugin_info
	 */
	public function __construct( array $plugin_info ) {
		$this->plugin_info = $plugin_info;
		$this->init_base_variables();
	}

	/**
	 * Init base variables
	 *
	 * @param string $file Plugin file.
	 */
	public function init_base_variables() {
		$this->plugin_url         = $this->plugin_info['plugin_url'];
		$this->plugin_dir         = $this->plugin_info['plugin_path'];
		$this->plugin_text_domain = $this->plugin_info['text_domain'];
		$this->plugin_version     = $this->plugin_info['plugin_version'];
	}

	/**
	 * Fires hooks
	 */
	public function hooks() {
		add_action( 'plugins_loaded', [ $this, 'load_plugin_text_domain' ] );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . $this->plugin_info['plugin_basename'], [ $this, 'links_filter' ] );
		if ( is_multisite() ) {
			add_filter( 'network_admin_plugin_action_links_' . $this->plugin_info['plugin_basename'], [
				$this,
				'multisite_links_filter'
			] );
		}
	}

	public function get_scripts_version(): string {
		return $this->plugin_version;
	}

	/**
	 * Define assets
	 *
	 * @return mixed
	 */
	public function get_template_dir(): string {
		return trailingslashit( $this->plugin_dir . 'templates' );
	}

	/**
	 * Define assets
	 *
	 * @return mixed
	 */
	public function get_assets_url(): string {
		return trailingslashit( $this->plugin_url . 'assets' );
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
	 * @param array $links Plugin links.
	 *
	 * @return mixed
	 */
	public function multisite_links_filter( $links ) {
		return $links;
	}

	/**
	 * @param array $links Plugin links.
	 *
	 * @return mixed
	 */
	public function links_filter( $links ) {
		return $links;
	}

	/**
	 * @return array
	 */
	public function get_plugin_info() {
		return $this->plugin_info;
	}

}
