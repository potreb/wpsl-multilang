<?php
/**
 * Integration
 *
 * @package MultiLang
 * @since   1.0.0
 */

namespace WPSL\MultiLang\Integration;

use WP_Site;

/**
 * Class MultiLanguagesPlugin
 */
class Integration {
	/**
	 * Current blog ID
	 *
	 * @var int
	 */
	private $current_blog_id;

	/**
	 * List of blog's ID
	 *
	 * @var array List of all blog ID's
	 */
	private $blog_sites_id;

	/**
	 * List of site domains
	 *
	 * @var array List of all blog domains
	 */
	public $blog_sites_domains;

	/**
	 * List of all blog languages
	 *
	 * @var array
	 */
	private $multisite_languages;

	/**
	 * Relation meta key
	 */
	const RELATION_META_KEY = '_trid';

	/**
	 * @var string
	 */
	private $plugin_assets;

	/**
	 * @var NetworkOptions
	 */
	private $settings;

	/**
	 * @param string $plugin_assets
	 */
	public function __construct( NetworkOptions $settings, $plugin_assets ) {
		$this->settings      = $settings;
		$this->plugin_assets = $plugin_assets;
		$this->get_site_blogs();
		$this->multisite_languages = $this->process_multisite_languages();
		$this->current_blog_id     = (int) get_current_blog_id();
	}

	/**
	 * Process languages from option to array for multisite blogs
	 *
	 * @return array
	 */
	private function process_multisite_languages(): array {
		$sites   = [];
		$results = $this->settings->get( 'multisite_languages', [] );
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
	 * @return array
	 */
	public function get_languages_details(): array {
		$langs = Helper::wpsl_multilang_languages_list();
		$icons = [];

		foreach ( $this->multisite_languages as $blog_id => $lang ) {
			$code = sanitize_key( $this->get_short_lang( $blog_id ) );

			$icons[ $blog_id ] = array(
				'icon'   => $this->plugin_assets . 'images/flags/' . $code . '.png',
				'code'   => isset( $langs[ $lang ]['code'] ) ? $langs[ $lang ]['code'] : '',
				'lang'   => $lang,
				'name'   => isset( $langs[ $lang ]['name'] ) ? $langs[ $lang ]['name'] : '',
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
	public function get_multisite_languages( $remove_current = false ): array {
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
	public function get_full_lang( $blog_id ): string {
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
	public function get_short_lang( $blog_id ): string {
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
	private function prepare_site_domain_url( WP_Site $site ): string {
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
	public function get_current_blog_id(): int {
		return $this->current_blog_id;
	}

	/**
	 * Get blog sites ID
	 *
	 * @return array
	 */
	public function get_blog_sites_id(): array {
		return $this->blog_sites_id;
	}

	/**
	 * Get blog sites domain
	 *
	 * @return array
	 */
	public function get_blog_sites_domains(): array {
		return $this->blog_sites_domains;
	}

	/**
	 * Get language icon HTML
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return string
	 */
	public function get_language_icon_html( $blog_id ): string {
		$code = sanitize_key( $this->get_short_lang( $blog_id ) );
		$img  = '<img src="' . $this->plugin_assets . 'images/flags/' . $code . '.png" alt="" /> ';

		return $img;
	}


}
