<?php
/**
 * Start: Include for phase 2
 * Widget Areas REST API: WP_REST_Widget_Areas_Controller class
 *
 * @package gutenberg
 * @since 5.7.0
 */

/**
 * Controller which provides REST endpoint for the widget areas.
 *
 * @since 5.2.0
 *
 * @see WP_REST_Controller
 */
class WP_Widgets_Manager {

	private static function get_wp_registered_widgets() {
		global $wp_registered_widgets;
		return $wp_registered_widgets;
	}

	private static function get_wp_registered_sidebars() {
		global $wp_registered_sidebars;
		return $wp_registered_sidebars;
	}

	public static function get_wp_registered_sidebars_sidebar( $sidebar_id ) {
		return self::get_wp_registered_sidebars()[ $sidebar_id ];
	}

	public static function get_post_id_referenced_in_sidebar( $sidebar_id ) {
		$sidebars = wp_get_sidebars_widgets();
		$sidebar  = $sidebars[ $sidebar_id ];
		return is_numeric( $sidebar ) ? $sidebar : 0;
	}

	public static function reference_post_id_in_sidebar( $sidebar_id, $post_id ) {
		$sidebars = wp_get_sidebars_widgets();
		$sidebar  = $sidebars[ $sidebar_id ];
		wp_set_sidebars_widgets(
			array_merge(
				$sidebars,
				array(
					$sidebar_id           => $post_id,
					'wp_inactive_widgets' => array_merge(
						$sidebars['wp_inactive_widgets'],
						$sidebar
					),
				)
			)
		);
	}

	public static function get_sidebar_as_blocks( $sidebar_id ) {
		$blocks = array();

		$sidebars_items = wp_get_sidebars_widgets();

		foreach ( $sidebars_items[ $sidebar_id ] as $item ) {
			$widget_class = self::get_widget_class( $item );
			$blocks[]     = array(
				'blockName' => 'core/legacy-widget',
				'attrs'     => array(
					'identifier' => $widget_class ? $widget_class : $item,
					'instance'   => self::get_sidebar_widget_instance( $sidebar, $item ),
				),
				'innerHTML' => '',
			);
		}
		return $blocks;
	}

	/**
	 * Verifies if a sidabar id is valid or not.
	 *
	 * @since 5.7.0
	 *
	 * @param string $sidebar_id Indentifier of the sidebar.
	 * @return boolean True if the $sidebar_id value is valid and false otherwise.
	 */
	public static function is_valid_sidabar_id( $sidebar_id ) {
		return isset( self::get_wp_registered_sidebars()[ $sidebar_id ] );
	}


	/**
	 * Given a widget id returns the name of the class the represents the widget.
	 *
	 * @since 5.7.0
	 *
	 * @param string $widget_id Indentifier of the widget.
	 * @return string|null Name of the class that represents the widget or null if the widget is not represented by a class.
	 */
	private static function get_widget_class( $widget_id ) {
		$wp_registered_widgets = self::get_wp_registered_widgets();
		if (
			isset( $wp_registered_widgets[ $widget_id ]['callback'][0] ) &&
			$wp_registered_widgets[ $widget_id ]['callback'][0] instanceof WP_Widget
		) {
			return get_class( $wp_registered_widgets[ $widget_id ]['callback'][0] );
		}
		return null;
	}

	/**
	 * Retrieves a widget instance.
	 *
	 * @since 5.7.0
	 *
	 * @param array  $sidebar sidebar data available at $wp_registered_sidebars.
	 * @param string $id Idenfitier of the widget instance.
	 * @return array Array containing the widget instance.
	 */
	private static function get_sidebar_widget_instance( $sidebar, $id ) {
		list( $object, $number, $name ) = self::get_widget_info( $id );
		if ( ! $object ) {
			return array();
		}

		$object->_set( $number );

		$instances = $object->get_settings();
		$instance  = $instances[ $number ];

		$args = array_merge(
			$sidebar,
			array(
				'widget_id'   => $id,
				'widget_name' => $name,
			)
		);

		/**
		 * Filters the settings for a particular widget instance.
		 *
		 * Returning false will effectively short-circuit display of the widget.
		 *
		 * @since 2.8.0
		 *
		 * @param array     $instance The current widget instance's settings.
		 * @param WP_Widget $this     The current widget instance.
		 * @param array     $args     An array of default widget arguments.
		 */
		$instance = apply_filters( 'widget_display_callback', $instance, $object, $args );

		if ( false === $instance ) {
			return array();
		}

		return $instance;
	}

	/**
	 * Given a widget id returns an array containing information about the widget.
	 *
	 * @since 5.7.0
	 *
	 * @param string $widget_id Indentifier of the widget.
	 * @return array Array containing the the wiget object, the number, and the name.
	 */
	private static function get_widget_info( $widget_id ) {
		global $wp_registered_widgets;

		if (
			! isset( $wp_registered_widgets[ $widget_id ]['callback'][0] ) ||
			! isset( $wp_registered_widgets[ $widget_id ]['params'][0]['number'] ) ||
			! isset( $wp_registered_widgets[ $widget_id ]['name'] ) ||
			! ( $wp_registered_widgets[ $widget_id ]['callback'][0] instanceof WP_Widget )
		) {
			return array( null, null, null );
		}

		$object = $wp_registered_widgets[ $widget_id ]['callback'][0];
		$number = $wp_registered_widgets[ $widget_id ]['params'][0]['number'];
		$name   = $wp_registered_widgets[ $widget_id ]['name'];
		return array( $object, $number, $name );
	}
}
