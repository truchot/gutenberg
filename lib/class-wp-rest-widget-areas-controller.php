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
class WP_REST_Widget_Areas_Controller extends WP_REST_Controller {

	/**
	 * Constructs the controller.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->namespace = '__experimental';
		$this->rest_base = 'widget-areas';
	}

	/**
	 * Registers the necessary REST API routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				// Todo: Add schema.
			)
		);

		$id_argument = array(
			'description'       => __( 'The sidebar’s ID.', 'gutenberg' ),
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => array( $this, 'is_valid_sidabar_id' ),
		);

		$content_argument = array(
			'description' => __( 'Sidebar content.', 'gutenberg' ),
			'type'        => 'string',
			'required'    => true,
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>.+)',
			array(
				'args' => array(
					'id' => $id_argument,
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'id'      => $id_argument,
						'content' => $content_argument,
					),
				),
				// Todo: Add schema.
			)
		);
	}

	/**
	 * Checks whether a given request has permission to read widget areas.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error(
				'rest_user_cannot_edit',
				__( 'Sorry, you are not allowed to edit sidebars.', 'gutenberg' )
			);
		}

		return true;
	}

	/**
	 * Retrieves all widget areas.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		global $wp_registered_sidebars;

		$data = array();

		foreach ( array_keys( $wp_registered_sidebars ) as $sidebar_id ) {
			$data[ $sidebar_id ] = $this->get_sidebar_data( $sidebar_id );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Checks if a given request has access to read a widget area.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error(
				'rest_user_cannot_edit',
				__( 'Sorry, you are not allowed to edit sidebars.', 'gutenberg' )
			);
		}

		return true;
	}

	/**
	 * Retrieves a specific widget area.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		return rest_ensure_response( $this->get_sidebar_data( $request['id'] ) );
	}

	/**
	 * Checks if a given REST request has access to update a widget area.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool True if the request has access to update the item, error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error(
				'rest_user_cannot_edit',
				__( 'Sorry, you are not allowed to edit sidebars.', 'gutenberg' )
			);
		}

		return true;
	}

	/**
	 * Updates a single widget area.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$sidebar_id      = $request->get_param( 'id' );
		$sidebar_content = $request->get_param( 'content' );
		global $wp_registered_sidebars;

		$sidebars = wp_get_sidebars_widgets();
		$sidebar  = $sidebars[ $sidebar_id ];
		$post_id  = wp_insert_post(
			array(
				'ID'           => is_numeric( $sidebar ) ? $sidebar : 0,
				'post_content' => $sidebar_content,
				'post_type'    => 'wp_area',
			)
		);
		if ( ! is_numeric( $sidebar ) ) {
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

		return rest_ensure_response( $this->get_sidebar_data( $request['id'] ) );
	}

	/**
	 * Verifies if a sidabar id is valid or not.
	 *
	 * @since 5.7.0
	 *
	 * @param string $sidebar_id Indentifier of the sidebar.
	 * @return boolean True if the $sidebar_id value is valid and false otherwise.
	 */
	public function is_valid_sidabar_id( $sidebar_id ) {
		global $wp_registered_sidebars;
		return isset( $wp_registered_sidebars[ $sidebar_id ] );
	}

	/**
	 * Returns the sidebar data together with a content array containing the blocks present in the sidebar.
	 * The bocks may be legacy widget blocks representing the widgets currently present in the sidebar, or the content of a wp_area post that the sidebar references.
	 *
	 * @since 5.7.0
	 *
	 * @param string $sidebar_id Indentifier of the sidebar.
	 * @return object Sidebar data with a content array.
	 */
	protected function get_sidebar_data( $sidebar_id ) {
		global $wp_registered_sidebars;

		if ( ! isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
			return new WP_Error(
				'rest_sidebar_invalid_id',
				__( 'Invalid sidebar ID.', 'gutenberg' ),
				array( 'status' => 404 )
			);
		}

		$sidebar        = $wp_registered_sidebars[ $sidebar_id ];
		$content_string = '';

		$sidebars_items = wp_get_sidebars_widgets();
		if ( is_numeric( $sidebars_items[ $sidebar_id ] ) ) {
			$post           = get_post( $sidebars_items[ $sidebar_id ] );
			$content_string = $post->post_content;
			apply_filters( 'the_content', $post->post_content );
		} elseif ( ! empty( $sidebars_items[ $sidebar_id ] ) ) {
			$blocks = array();
			foreach ( $sidebars_items[ $sidebar_id ] as $item ) {
				$widget_class = $this->get_widget_class( $item );
				$blocks[]     = array(
					'blockName' => 'core/legacy-widget',
					'attrs'     => array(
						'identifier' => $widget_class ? $widget_class : $item,
						'instance'   => $this->get_sidebar_widget_instance( $sidebar, $item ),
					),
					'innerHTML' => '',
				);
			}
			$content_string = serialize_blocks( $blocks );
		}

		return array_merge(
			$sidebar,
			array(
				'content' => array(
					'raw'           => $content_string,
					/** This filter is documented in wp-includes/post-template.php */
					'rendered'      => apply_filters( 'the_content', $content_string ),
					'block_version' => block_version( $content_string ),
				),
			)
		);
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
	private function get_sidebar_widget_instance( $sidebar, $id ) {
		list( $object, $number, $name ) = $this->get_widget_info( $id );
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
	 * Given a widget id returns the name of the class the represents the widget.
	 *
	 * @since 5.7.0
	 *
	 * @param string $widget_id Indentifier of the widget.
	 * @return string|null Name of the class that represents the widget or null if the widget is not represented by a class.
	 */
	private function get_widget_class( $widget_id ) {
		global $wp_registered_widgets;
		if (
			isset( $wp_registered_widgets[ $widget_id ]['callback'][0] ) &&
			$wp_registered_widgets[ $widget_id ]['callback'][0] instanceof WP_Widget
		) {
			return get_class( $wp_registered_widgets[ $widget_id ]['callback'][0] );
		}
		return null;
	}

	/**
	 * Given a widget id returns an array containing information about the widget.
	 *
	 * @since 5.7.0
	 *
	 * @param string $widget_id Indentifier of the widget.
	 * @return array Array containing the the wiget object, the number, and the name.
	 */
	private function get_widget_info( $widget_id ) {
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
