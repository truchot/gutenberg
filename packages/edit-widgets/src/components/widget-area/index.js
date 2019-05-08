/**
 * External dependencies
 */
import { defaultTo } from 'lodash';

/**
 * WordPress dependencies
 */
import { Panel, PanelBody } from '@wordpress/components';
import {
	BlockEditorProvider,
	BlockList,
} from '@wordpress/block-editor';
import { withSelect } from '@wordpress/data';
import { useMemo, useState } from '@wordpress/element';
import { mediaUpload } from '@wordpress/media-upload';
import { compose } from '@wordpress/compose';

function getBlockEditorSettings( settings, hasUploadPermissions ) {
	if ( ! hasUploadPermissions ) {
		return {};
	}
	const mediaUploadBlockEditor = ( { onError, ...argumentsObject } ) => {
		mediaUpload( {
			wpAllowedMimeTypes: settings.allowedMimeTypes,
			onError: ( { message } ) => onError( message ),
			...argumentsObject,
		} );
	};
	return {
		__experimentalMediaUpload: mediaUploadBlockEditor,
	};
}

function WidgetArea( {
	title,
	initialOpen,
	hasUploadPermissions,
	settings,
} ) {
	const [ blocks, updateBlocks ] = useState( [] );
	const blockEditorSettings = useMemo(
		() => getBlockEditorSettings( settings, hasUploadPermissions ),
		[ settings, hasUploadPermissions ]
	);

	return (
		<Panel>
			<PanelBody
				title={ title }
				initialOpen={ initialOpen }
			>
				<BlockEditorProvider
					value={ blocks }
					onInput={ updateBlocks }
					onChange={ updateBlocks }
					settings={ blockEditorSettings }
				>
					<BlockList />
				</BlockEditorProvider>
			</PanelBody>
		</Panel>
	);
}
export default compose( [
	withSelect( ( select ) => {
		const {
			getWidgetEditorSettings,
		} = select( 'core/edit-widgets' );
		const { canUser } = select( 'core' );

		return {
			settings: getWidgetEditorSettings(),
			hasUploadPermissions: defaultTo( canUser( 'create', 'media' ), true ),
		};
	} ),
] )( WidgetArea );
