import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import {
	getAccountColorPanelGroups,
	renderAccountColorSettings,
} from './color-controls';
import { renderAccountControl } from './controls';
import {
	getAccountTypographyControls,
	getNonColorControls,
	renderAccountTypographySettings,
} from './typography-controls';

export function AccountBlockPreview( {
	blockName,
	attributes,
	setAttributes,
	controls = [],
	panels = [],
} ) {
	const blockProps = useBlockProps( {
		className: 'ppcart-account-preview',
	} );
	const displayControls = getNonColorControls( controls );
	const displayPanels = panels
		.map( ( panel ) => ( {
			...panel,
			controls: getNonColorControls( panel.controls || [] ),
		} ) )
		.filter( ( panel ) => panel.controls.length > 0 );
	const colorGroups = getAccountColorPanelGroups( controls, panels );
	const typographyControls = getAccountTypographyControls( controls, panels );
	const hasInspector =
		displayControls.length > 0 ||
		displayPanels.length > 0 ||
		typographyControls.length > 0;
	const hasStyleInspector = colorGroups.length > 0;

	return (
		<div { ...blockProps }>
			{ hasInspector && (
				<InspectorControls>
					{ displayControls.length > 0 && (
						<PanelBody
							title={ __(
								'Account Display',
								'publishpress-cart'
							) }
							initialOpen
						>
							{ displayControls.map( ( control ) =>
								renderAccountControl(
									control,
									attributes,
									setAttributes
								)
							) }
						</PanelBody>
					) }
					{ displayPanels.map( ( panel, index ) => (
						<PanelBody
							key={ panel.title || index }
							title={ panel.title }
							initialOpen={ !! panel.initialOpen }
						>
							{ panel.controls.map( ( control ) =>
								renderAccountControl(
									control,
									attributes,
									setAttributes
								)
							) }
						</PanelBody>
					) ) }
					{ renderAccountTypographySettings(
						typographyControls,
						attributes,
						setAttributes
					) }
				</InspectorControls>
			) }
			{ hasStyleInspector && (
				<InspectorControls group="styles">
					{ colorGroups.map( ( group ) =>
						renderAccountColorSettings(
							group,
							attributes,
							setAttributes
						)
					) }
				</InspectorControls>
			) }

			<div className="ppcart-account-preview__rendered">
				<ServerSideRender
					block={ blockName }
					attributes={ attributes }
					skipBlockSupportAttributes
				/>
			</div>
		</div>
	);
}
