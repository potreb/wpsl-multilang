<?php
/**
 * Network Options
 *
 * @package MultiLang
 * @since   1.0.0
 */

namespace WPSL\MultiLang\Integration;

/**
 * Class NetworkOptions
 */
class NetworkOptions {

	const SLUG = 'wpslmu_settings';

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @param string $prefix
	 */
	public function __construct( $prefix = '' ) {
		if ( $prefix ) {
			$this->prefix = $prefix;
		}
		$this->prefix = self::SLUG;
	}

	/**
	 * Get network setting
	 *
	 * @param string $name     Setting name.
	 * @param mixed  $defaults Setting default value.
	 *
	 * @return bool|mixed
	 */
	public function get( $name, $defaults = false ) {
		$value = get_site_option( $this->prefix . '_' . $name, $defaults );
		if ( empty( $value ) ) {
			return $defaults;
		}

		return $value;
	}

	/**
	 * Update network setting
	 *
	 * @param string $name  Setting name.
	 * @param mixed  $value Setting value.
	 *
	 * @return bool|mixed
	 */
	public function set( $name, $value ) {
		$setting = update_site_option( $this->prefix . '_' . $name, $value );

		return $setting;
	}

}
