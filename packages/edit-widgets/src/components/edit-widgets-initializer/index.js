/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Layout from '../layout';

function EditWidgetsInitializer( { settings, updateWidgetEditorSettings } ) {
	useEffect( () => {
		updateWidgetEditorSettings( settings || {} );
	}, [ settings, updateWidgetEditorSettings ] );
	return <Layout />;
}

export default compose( [
	withDispatch( ( dispatch ) => {
		const { updateWidgetEditorSettings } = dispatch( 'core/edit-widgets' );
		return {
			updateWidgetEditorSettings,
		};
	} ),
] )( EditWidgetsInitializer );
