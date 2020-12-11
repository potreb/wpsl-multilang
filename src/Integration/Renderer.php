<?php
/**
 * Template
 *
 * @package MultiLang
 * @since   1.0.0
 */
namespace WPSL\MultiLang\Integration;

/**
 * Resolve template view.
 */
class Renderer {

	/**
	 * @var string
	 */
	private $template_dir;

	/**
	 * @param $template_dir
	 */
	public function __construct( $template_dir ) {
		$this->template_dir = $template_dir;
	}

	/**
	 * @param string $filename
	 * @param array  $args
	 */
	public function get_template( string $filename, array $args ) {
		$template = $this->template_dir . $filename . '.php';
		if ( file_exists( $template ) ) {
			require_once $template;
		} else {
			throw new \Exception( 'Unknown template path: ' . $template );
		}
	}

}
