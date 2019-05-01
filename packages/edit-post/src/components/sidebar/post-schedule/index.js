/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelRow, Dropdown, Button } from '@wordpress/components';
import { withInstanceId } from '@wordpress/compose';
import { PostSchedule as PostScheduleForm, PostScheduleLabel, PostScheduleCheck } from '@wordpress/editor';
import { Fragment, renderToString } from '@wordpress/element';

export function PostSchedule( { instanceId } ) {
	return (
		<PostScheduleCheck>
			<PanelRow className="edit-post-post-schedule">
				<span
					id={ `edit-post-post-schedule__heading-${ instanceId }` }
				>
					{ __( 'Publish' ) }
				</span>
				<Dropdown
					position="bottom left"
					contentClassName="edit-post-post-schedule__dialog"
					renderToggle={ ( { onToggle, isOpen } ) => (
						<Fragment>
							<Button
								type="button"
								className="edit-post-post-schedule__toggle"
								onClick={ onToggle }
								aria-expanded={ isOpen }
								aria-live="polite"
								aria-label={ renderToString(
									<Fragment>
										<PostScheduleLabel /> { __( 'Click to change' ) }
									</Fragment>
								) }
								aria-describedby={ `edit-post-post-schedule__heading-${ instanceId }` }
								isLink
							>
								<PostScheduleLabel />
							</Button>
						</Fragment>
					) }
					renderContent={ () => <PostScheduleForm /> }
				/>
			</PanelRow>
		</PostScheduleCheck>
	);
}

export default withInstanceId( PostSchedule );
