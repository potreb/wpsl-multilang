<?php
/**
 * Database installation
 *
 * @package    MultiLang
 * @subpackage \WPSL\MultiLang
 * @since      1.0.0
 */

namespace WPSL\MultiLang\Integration;

/**
 * Class Database
 */
class Database {

	const SETTING_NAME = 'wpslmu_settings';

	const TABLE_NAME = 'wpslml_object_relations';

	public function __construct() {
		$this->define_table_prefix();
	}

	/**
	 * Define table prefix.
	 */
	private function define_table_prefix() {
		global $wpdb;
		$wpdb->mslt = $wpdb->get_blog_prefix() . Database::TABLE_NAME;
	}

	/**
	 * Install table into database and some options.
	 */
	public static function install() {
		global $wpdb;
		if ( ! is_multisite() || ! defined( 'SITE_ID_CURRENT_SITE' ) ) {
			return false;
		}

		$table_name = $wpdb->get_blog_prefix() . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$installed       = get_site_option( self::SETTING_NAME . '_install', 0 );
		if ( ! $installed ) {
			$sql = "
				CREATE TABLE ".$table_name." (
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
			$wpdb->query( 'ALTER TABLE ' . $table_name . ' ADD UNIQUE KEY `REL` (object_blog_id,post_id,post_blog_id)' ); //phpcs:ignore
			update_network_option( SITE_ID_CURRENT_SITE, self::SETTING_NAME . '_install', 1 );
			update_network_option( SITE_ID_CURRENT_SITE, self::SETTING_NAME . '_activation_date', strtotime( 'NOW' ) );
		}
	}

}
