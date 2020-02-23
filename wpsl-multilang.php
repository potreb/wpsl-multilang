<?php
/**
 * Plugin Name: MultiSite Languages
 * Plugin URI: wpsmartlab.com
 * Description: This plugin creates a multi language platform from a network of sites
 * Version: 1.1.3
 * Author: Piotr Potrebka
 * Author URI: wpsmartlab.com
 * Text Domain: wpsl-multilang
 * Domain Path: /languages
 *
 * License: GPL v3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Multilang
 */

use \WPSL\MultiLang\Plugin;
use \WPSL\MultiLang\Ajax;
use \WPSL\MultiLang\Settings;
use \WPSL\MultiLang\Attachments;
use \WPSL\MultiLang\Posts;
use \WPSL\MultiLang\Widget;
use \WPSL\MultiLang\Frontend;

/**
 * Main WPSL_MultiLang Class.
 *
 * @class MultiLanguages
 */
final class WPSL_MultiLang {

	/**
	 * Plugin
	 *
	 * @var \WPSL\MultiLang\Plugin
	 */
	private $plugin;

	/**
	 * Plugin
	 *
	 * @var Frontend
	 */
	private $frontend;

	const TABLE_NAME = 'multi_langual';

	const SETTING_NAME = 'wpslmu_settings';

	/**
	 * MultiLanguages Constructor.
	 */
	public function __construct() {}

	/**
	 * Init plugin
	 */
	public function init() {
		if ( is_multisite() ) {
			$this->define_table_prefix();
			$this->load_dependencies();
			$this->autoloader();
			$this->init_hooks();
		}
	}

	/**
	 * Load dependencies
	 */
	private function load_dependencies() {
		require_once __DIR__ . '/classes/helper.php';
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Define table prefix.
	 */
	private function define_table_prefix() {
		global $wpdb;
		$dbprefix   = $wpdb->get_blog_prefix( SITE_ID_CURRENT_SITE );
		$wpdb->mslt = $dbprefix . self::TABLE_NAME;
	}

	/**
	 * Use autoloader to load all classes.
	 */
	private function autoloader() {
		require_once __DIR__ . '/vendor/autoload.php';
	}

	/**
	 * Init hooks
	 */
	private function init_hooks() {
		add_action( 'widgets_init', array( $this, 'multi_languages_widget' ) );

		$this->plugin = new Plugin( __FILE__ );
		$this->plugin->hooks();

		$ajax = new Ajax( $this->plugin );
		$ajax->hooks();

		$menu_page = new Settings( $this->plugin );
		$menu_page->hooks();

		if ( empty( $this->plugin->get_multisite_languages() ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_no_defined_languages' ), 1 );
		}

		$this->frontend = new Frontend( $this->plugin );
		$this->frontend->hooks();

		$posts = new Posts( $this->plugin );
		$posts->hooks();

		$posts = new Attachments( $this->plugin );
		$posts->hooks();
	}

	/**
	 * Add notice if plugin is not configure.
	 */
	public function admin_notice_no_defined_languages() {
		$settings_url = network_admin_url( 'admin.php?page=' . Settings::MENU_SLUG );
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
		$widget = new Widget( $this->plugin, $this->frontend );
		register_widget( $widget );
	}

	/**
	 * Install plugin.
	 *
	 * Install table into database and some other variables.
	 *
	 * @return bool
	 */
	public static function install() {
		global $wpdb;
		if ( ! is_multisite() || ! defined( 'SITE_ID_CURRENT_SITE' ) ) {
			return false;
		}
		$charset_collate = $wpdb->get_charset_collate();
		$installed       = get_network_option( SITE_ID_CURRENT_SITE, self::SETTING_NAME . '_install', 0 );
		if ( ! $installed ) {
			$sql = "
				CREATE TABLE {$wpdb->mslt} (
				  object_id bigint(20) NOT NULL,
				  object_blog_id tinyint(2) NOT NULL,
				  post_id bigint(20) NOT NULL,
				  post_blog_id tinyint(2) NOT NULL,
				  sync int(11) NOT NULL,
				  PRIMARY KEY  ( object_id, post_id )
				) $charset_collate;
			";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			$wpdb->query( "ALTER TABLE {$wpdb->mslt} ADD UNIQUE KEY `REL` (object_blog_id,post_id,post_blog_id)" ); //phpcs:ignore
			update_network_option( SITE_ID_CURRENT_SITE, self::SETTING_NAME . '_install', 1 );
			update_network_option( SITE_ID_CURRENT_SITE, self::SETTING_NAME . '_activation_date', 1 );

			return true;
		}

		return false;
	}

}

register_activation_hook( __FILE__, array( 'WPSL_MultiLang', 'install' ) );

// Init plugin!
add_action(
	'plugins_loaded',
	function () {
		$plugin = new WPSL_MultiLang();
		$plugin->init();
	},
	10
);
