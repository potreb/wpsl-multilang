<?php
/**
 * Widget
 *
 * @package MultiLang
 * @since   1.0.0
 */
namespace WPSL\MultiLang\Integration;

/**
 * Class Widget
 *
 * @package WPSL\MultiLang
 */
class Widget extends \WP_Widget {

	/**
	 * @var Integration
	 */
	private $plugin;

	/**
	 * MultiLanguages_Frontend reference
	 *
	 * @var Frontend
	 */
	private $frontend;

	/**
	 * @param Integration $integration Plugin reference.
	 * @param Frontend    $frontend    Plugin reference.
	 */
	public function __construct( Integration $integration, Frontend $frontend ) {
		$this->plugin   = $integration;
		$this->frontend = $frontend;

		$widget_ops = array(
				'classname'                   => 'widget_languages',
				'description'                 => __( 'A list of languages.', 'wpsl-multilang' ),
				'customize_selective_refresh' => true,
		);
		parent::__construct( 'mls_languages', __( 'Languages', 'wpsl-multilang' ), $widget_ops );
	}

	/**
	 * Outputs the content for the current Pages widget instance.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Pages widget instance.
	 *
	 * @since 2.8.0
	 *
	 */
	public function widget( $args, $instance ) {
		$list_type    = empty( $instance['list_type'] ) ? 'list_with_flags' : $instance['list_type'];
		$allowed_html = wp_kses_allowed_html( 'post' );
		echo wp_kses( $args['before_widget'], $allowed_html );
		echo $this->frontend->html_list_flags( $list_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_kses( $args['after_widget'], $allowed_html );
	}

	/**
	 * Handles updating settings for the current Pages widget instance.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Updated settings to save.
	 * @since 2.8.0
	 *
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		if ( in_array( $new_instance['list_type'], array(
				'list_with_flags',
				'list_without_flags',
				'select'
		), true ) ) {
			$instance['list_type'] = $new_instance['list_type'];
		} else {
			$instance['list_type'] = 'list_with_flags';
		}

		return $instance;
	}

	/**
	 * Outputs the settings form for the Pages widget.
	 *
	 * @param array $instance Current settings.
	 *
	 * @since 2.8.0
	 *
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
				(array) $instance,
				array(
						'list_type' => 'list_with_flags',
				)
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'list_type' ) ); ?>"><?php _e( 'Sort by:', 'wpsl-multilang' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'list_type' ) ); ?>"
					id="<?php echo esc_attr( $this->get_field_id( 'list_type' ) ); ?>" class="widefat">
				<option value="list_with_flags"<?php selected( $instance['list_type'], 'list_with_flags' ); ?>><?php _e( 'List with flags', 'wpsl-multilang' ); ?></option>
				<option value="list_without_flags"<?php selected( $instance['list_type'], 'list_without_flags' ); ?>><?php _e( 'List without flags', 'wpsl-multilang' ); ?></option>
			</select>
		</p>
		<?php
	}

}
