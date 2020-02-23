<?php
/**
 * Plugin class
 *
 * @package    MultiLang
 * @subpackage \WPSL\MultiLang
 * @since      1.0
 */

namespace WPSL\MultiLang;

use WP_Site;

/**
 * Class MultiLanguagesPlugin
 */
class Plugin extends \AbstractPlugin {

	/**
	 * Current blog ID.
	 *
	 * @var int
	 */
	private $current_blog_id;

	/**
	 * List of blog's ID.
	 *
	 * @var array List of all blog ID's
	 */
	private $blog_sites_id;

	/**
	 * List of site domains.
	 *
	 * @var array List of all blog domains
	 */
	public $blog_sites_domains;

	/**
	 * List of all blog languages.
	 *
	 * @var array
	 */
	private $multisite_languages;

	/**
	 * Languages info.
	 *
	 * @var array
	 */
	public $languages_info;

	/**
	 * Setting name
	 */
	const SETTING_NAME = 'wpslmu_settings';

	/**
	 * Relation meta key
	 */
	const RELATION_META_KEY = '_trid';

	/**
	 * \WPSL\MultiLang\Plugin constructor.
	 *
	 * @param string $file Parent file.
	 */
	public function __construct( $file ) {
		parent::__construct( $file );
		$this->get_site_blogs();
		$this->multisite_languages = $this->process_multisite_languages();
		$this->current_blog_id     = (int) get_current_blog_id();
		$this->languages_info      = $this->get_languages_details();

		define( 'WPSLML_FLAGS_URL', $this->plugin_assets . 'images/flags/' );
	}

	/**
	 * Admin enqueue scripts
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css', array(), '3.5.3' );
		wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array( 'jquery' ), '3.5.3', true );

		wp_register_style( $this->plugin_text_domain, $this->plugin_assets . 'css/admin.css', false, $this->scripts_version );
		wp_enqueue_style( $this->plugin_text_domain );

		wp_register_script( $this->plugin_text_domain, $this->plugin_assets . 'js/admin.js', array( 'jquery' ), $this->scripts_version, true );
		$translation_array = array(
			'before_duplicate' => __( 'Before duplicate, remove translation from select', 'wpsl-multilang' ),
		);
		wp_localize_script( $this->plugin_text_domain, 'wpsl_multilang', $translation_array );
		wp_enqueue_script( $this->plugin_text_domain );
	}

	/**
	 * Proces languages from option to array for multisite blogs
	 *
	 * @return array
	 */
	private function process_multisite_languages() {
		$sites   = [];
		$results = $this->get_network_option( 'multisite_languages', [] );
		if ( $results && is_array( $results ) ) {
			foreach ( $results as $blog_id => $language ) {
				/* Remove site if doesn't set language */
				if ( empty( $language ) ) {
					continue;
				}
				$sites[ $blog_id ] = $language;
			}
		}

		return $sites;
	}

	/**
	 * Get language details
	 *
	 * @return string
	 */
	public function get_languages_details() {
		$langs = wpsl_multilang_languages_list();
		foreach ( $this->multisite_languages as $blog_id => $lang ) {
			$code = sanitize_key( $this->get_short_lang( $blog_id ) );

			$icons[ $blog_id ] = array(
				'icon' => $this->plugin_assets . 'images/flags/' . $code . '.png',
				'code' => isset( $langs[ $lang ]['code'] ) ? $langs[ $lang ]['code'] : '',
				'lang' => $lang,
				'name' => isset( $langs[ $lang ]['name'] ) ? $langs[ $lang ]['name'] : '',
				'domain' => isset( $this->blog_sites_domains[ $blog_id ] ) ? $this->blog_sites_domains[ $blog_id ] : '',
			);
		}
		$GLOBALS['wpslml_languages'] = $icons;
		return $icons;
	}

	/**
	 * Get multisite languages
	 *
	 * @param bool $remove_current Set true to unset current blog ID.
	 *
	 * @return array
	 */
	public function get_multisite_languages( $remove_current = false ) {
		$languages = $this->multisite_languages;
		if ( $remove_current ) {
			if ( isset( $languages[ $this->current_blog_id ] ) ) {
				unset( $languages[ $this->current_blog_id ] );
			}
		}

		return $languages;
	}

	/**
	 * Get full language slug (en_US)
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return string
	 */
	public function get_full_lang( $blog_id ) {
		if ( isset( $this->multisite_languages[ $blog_id ] ) ) {
			return $this->multisite_languages[ $blog_id ];
		} else {
			return '';
		}
	}

	/**
	 * Get short language slug (pl)
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return string
	 */
	public function get_short_lang( $blog_id ) {
		if ( isset( $this->multisite_languages[ $blog_id ] ) ) {
			return substr( $this->multisite_languages[ $blog_id ], 0, 2 );
		} else {
			return '';
		}
	}

	/**
	 * Get a list of blog ID's & domains
	 *
	 * @return void
	 */
	public function get_site_blogs() {
		if ( function_exists( 'wp_get_sites' ) ) {
			$sites    = get_sites();
			$protocol = is_ssl() ? 'https://' : 'http://';
			foreach ( $sites as $site ) {
				$this->blog_sites_id[ $site->blog_id ]      = $site->blog_id;
				$this->blog_sites_domains[ $site->blog_id ] = $protocol . $this->prepare_site_domain_url( $site );
			}
		}
	}

	/**
	 * Prepare site domain URL
	 *
	 * @param WP_Site $site WP_Site object.
	 *
	 * @return string
	 */
	private function prepare_site_domain_url( WP_Site $site ) {
		if ( ! SUBDOMAIN_INSTALL ) {
			return $site->domain . $site->path;
		}

		return $site->domain;
	}

	/**
	 * Get current blog ID
	 *
	 * @return int
	 */
	public function get_current_blog_id() {
		return $this->current_blog_id;
	}

	/**
	 * Get blog sites ID
	 *
	 * @return array
	 */
	public function get_blog_sites_id() {
		return $this->blog_sites_id;
	}

	/**
	 * Get blog sites domain
	 *
	 * @return array
	 */
	public function get_blog_sites_domains() {
		return $this->blog_sites_domains;
	}

	/**
	 * Get network setting
	 *
	 * @param string $name     Setting name.
	 * @param mixed  $defaults Setting default value.
	 *
	 * @return bool|mixed
	 */
	public function get_network_option( $name, $defaults = false ) {
		$value = get_network_option( SITE_ID_CURRENT_SITE, self::SETTING_NAME . '_' . $name, $defaults );
		if ( empty( $value ) ) {
			return $defaults;
		}

		return $value;
	}

	/**
	 * Update network setting
	 *
	 * @param string $name     Setting name.
	 * @param mixed  $value    Setting value.
	 *
	 * @return bool|mixed
	 */
	public function update_network_option( $name, $value ) {
		$setting = update_network_option( SITE_ID_CURRENT_SITE, self::SETTING_NAME . '_' . $name, $value );
		return $setting;
	}

	/**
	 * Get language icon HTML
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return string
	 */
	public function get_language_icon_html( $blog_id ) {
		$code = sanitize_key( $this->get_short_lang( $blog_id ) );
		$img  = '<img src="' . $this->plugin_assets . 'images/flags/' . $code . '.png" alt="" /> ';

		return $img;
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
			'<a href="' . admin_url( 'wp-admin/network/admin.php?page=' . Settings::MENU_SLUG ) . '">' . __( 'Settings' ) . '</a>',
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
			'<a href="' . admin_url( 'wp-admin/network/admin.php?page=' . Settings::MENU_SLUG ) . '">' . __( 'Settings' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}
}
