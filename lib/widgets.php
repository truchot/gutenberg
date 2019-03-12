<?php
/**
 * Functions used in making widgets interopable with block editors.
 *
 * @package gutenberg
 */

/**
 * Emulates the Widgets screen `admin_print_styles` when at the block editor
 * screen.
 */
function gutenberg_block_editor_admin_print_styles() {
	if ( get_current_screen()->is_block_editor() ) {
		/** This action is documented in wp-admin/admin-footer.php */
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		do_action( 'admin_print_styles-widgets.php' );
	}
}
add_action( 'admin_print_styles', 'gutenberg_block_editor_admin_print_styles' );

/**
 * Emulates the Widgets screen `admin_print_scripts` when at the block editor
 * screen.
 */
function gutenberg_block_editor_admin_print_scripts() {
	if ( get_current_screen()->is_block_editor() ) {
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		do_action( 'admin_print_scripts-widgets.php' );
	}
}
add_action( 'admin_print_scripts', 'gutenberg_block_editor_admin_print_scripts' );

/**
 * Emulates the Widgets screen `admin_print_footer_scripts` when at the block
 * editor screen.
 */
function gutenberg_block_editor_admin_print_footer_scripts() {
	if ( get_current_screen()->is_block_editor() ) {
		/** This action is documented in wp-admin/admin-footer.php */
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		do_action( 'admin_print_footer_scripts-widgets.php' );
	}
}
add_action( 'admin_print_footer_scripts', 'gutenberg_block_editor_admin_print_footer_scripts' );

/**
 * Emulates the Widgets screen `admin_footer` when at the block editor screen.
 */
function gutenberg_block_editor_admin_footer() {
	if ( get_current_screen()->is_block_editor() ) {
		/** This action is documented in wp-admin/admin-footer.php */
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		do_action( 'admin_footer-widgets.php' );
	}
}
add_action( 'admin_footer', 'gutenberg_block_editor_admin_footer' );

/**
 * Extends default editor settings with values supporting legacy widgets.
 *
 * @param array $settings Default editor settings.
 *
 * @return array Filtered editor settings.
 */
function gutenberg_legacy_widget_settings( $settings ) {
	/**
	 * TODO: The hardcoded array should be replaced with a mechanism to allow
	 * core and third party blocks to specify they already have equivalent
	 * blocks, and maybe even allow them to have a migration function.
	 */
	$core_widgets = array(
		'WP_Widget_Pages',
		'WP_Widget_Calendar',
		'WP_Widget_Archives',
		'WP_Widget_Media_Audio',
		'WP_Widget_Media_Image',
		'WP_Widget_Media_Gallery',
		'WP_Widget_Media_Video',
		'WP_Widget_Meta',
		'WP_Widget_Search',
		'WP_Widget_Text',
		'WP_Widget_Categories',
		'WP_Widget_Recent_Posts',
		'WP_Widget_Recent_Comments',
		'WP_Widget_RSS',
		'WP_Widget_Tag_Cloud',
		'WP_Nav_Menu_Widget',
		'WP_Widget_Custom_HTML',
	);

	$has_permissions_to_manage_widgets = current_user_can( 'edit_theme_options' );
	$available_legacy_widgets          = array();
	global $wp_widget_factory, $wp_registered_widgets, $wp_registered_widget_controls;
	// Add widgets implemented as a class to the available_legacy_widgets widgets array.
	// All widgets implemented as a class have an edit form.
	foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
		if ( ! in_array( $class, $core_widgets ) ) {
			$available_legacy_widgets[ $class ] = array(
				'name'             => html_entity_decode( $widget_obj->name ),
				// wp_widget_description is not being used because its input parameter is a Widget Id.
				// Widgets id's reference to a specific widget instance.
				// Here we are iterating on all the available widget classes even if no widget instance exists for them.
				'description'      => isset( $widget_obj->widget_options['description'] ) ?
					html_entity_decode( $widget_obj->widget_options['description'] ) :
					null,
				'isCallbackWidget' => false,
				'hasEditForm'      => true,
			);
		}
	}
	// Add widgets registered using wp_register_sidebar_widget.
	foreach ( $wp_registered_widgets as $widget_id => $widget_obj ) {
		// Skip instances of widgets that are implemented as classes.
		if (
			is_array( $widget_obj['callback'] ) &&
			isset( $widget_obj['callback'][0] ) &&
			( $widget_obj['callback'][0] instanceof WP_Widget )
		) {
			continue;
		}
		// By default widgets registered with wp_register_sidebar_widget don't have an edit form, but a form may be added.
		$has_edit_form = false;
		// Checks if an edit form was added.
		if (
			isset( $wp_registered_widget_controls[ $widget_id ]['callback'] ) &&
			is_callable( $wp_registered_widget_controls[ $widget_id ]['callback'] )
		) {
			$has_edit_form = true;
		}
		$available_legacy_widgets[ $widget_id ] = array(
			'name'             => html_entity_decode( $widget_obj['name'] ),
			'description'      => html_entity_decode( wp_widget_description( $widget_id ) ),
			'isCallbackWidget' => true,
			'hasEditForm'      => $has_edit_form,
		);
	}

	$settings['hasPermissionsToManageWidgets'] = $has_permissions_to_manage_widgets;
	$settings['availableLegacyWidgets']        = $available_legacy_widgets;

	return $settings;
}
add_filter( 'block_editor_settings', 'gutenberg_legacy_widget_settings' );
