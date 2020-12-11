<?php
/**
 * Plugin Name: Multi Language Network for WooCommerce
 * Plugin URI: https://wpsmartlab.com
 * Description: This plugin creates a multi language platform for WooCommerce with a network of sites.
 * Version: 1.0.0
 * Author: Piotr Potrebka
 * Author URI: https://wpsmartlab.com
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/* THESE TWO VARIABLES CAN BE CHANGED AUTOMATICALLY */
$plugin_version           = '1.0.0';
$plugin_release_timestamp = '2020-11-16 13:42';

$plugin_name        = __( 'Multi Language Network for WooCommerce', 'wpsl-multilang' );
$plugin_desc        = __( 'This plugin creates a multi language platform with a network of sites.', 'wpsl-multilang' );
$plugin_uri         = __( 'https://wpsmartlab.com', 'wpsl-multilang' );
$plugin_text_domain = 'wpsl-multilang';
$plugin_file        = __FILE__;
$plugin_dir         = dirname( __FILE__ );

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook( __FILE__, array( 'WPSL\MultiLang\Integration\Database', 'install' ) );

add_action( 'plugins_loaded', function () use ( $plugin_name, $plugin_desc, $plugin_file, $plugin_text_domain, $plugin_version ) {
	$plugin_info['plugin_file']     = $plugin_file;
	$plugin_info['plugin_basename'] = plugin_basename( $plugin_file );
	$plugin_info['plugin_name']     = $plugin_name;
	$plugin_info['plugin_desc']     = $plugin_desc;
	$plugin_info['plugin_path']     = trailingslashit( plugin_dir_path( $plugin_file ) );
	$plugin_info['plugin_url']      = plugin_dir_url( $plugin_file );
	$plugin_info['text_domain']     = $plugin_text_domain;
	$plugin_info['plugin_version']  = $plugin_version;

	/**
	 * Fire main plugin class.
	 */
	( new \WPSL\MultiLang\Plugin( $plugin_info ) )->hooks();

}, 100 );
