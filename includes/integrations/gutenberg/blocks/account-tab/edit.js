import {
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useContext } from '@wordpress/element';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { renderAccountColorSettings } from '../_shared/color-controls';
import { defaultIncludedNavigationTabs } from '../_shared/options';
import {
	AccountNavigationPreviewContext,
	getAccountTabDefinition,
	getIncludedNavigationTabs,
} from '../_shared/tabs';

export function buildAccountTabStyle( attributes ) {
	const style = {};

	if ( Number.isFinite( attributes.panelPadding ) ) {
		style[
			'--ppcart-account-panel-padding'
		] = `${ attributes.panelPadding }px`;
	}
	if ( Number.isFinite( attributes.panelRadius ) ) {
		style[
			'--ppcart-account-panel-radius'
		] = `${ attributes.panelRadius }px`;
	}
	if ( Number.isFinite( attributes.headingFontSize ) ) {
		style[
			'--ppcart-account-heading-font-size'
		] = `${ attributes.headingFontSize }px`;
	}
	if ( attributes.headingFontWeight ) {
		style[ '--ppcart-account-heading-font-weight' ] =
			attributes.headingFontWeight;
	}
	if ( Number.isFinite( attributes.buttonRadius ) ) {
		style[
			'--ppcart-account-button-radius'
		] = `${ attributes.buttonRadius }px`;
	}

	const colorMap = {
		panelBackgroundColor: '--ppcart-account-panel-background',
		panelTextColor: '--ppcart-account-panel-color',
		panelBorderColor: '--ppcart-account-panel-border-color',
		panelLinkColor: '--ppcart-account-panel-link-color',
		panelLinkHoverColor: '--ppcart-account-panel-link-hover-color',
		headingColor: '--ppcart-account-heading-color',
		tableHeaderBackgroundColor: '--ppcart-account-table-header-background',
		tableHeaderTextColor: '--ppcart-account-table-header-color',
		tableRowBackgroundColor: '--ppcart-account-table-row-background',
		tableRowAltBackgroundColor: '--ppcart-account-table-row-alt-background',
		tableRowTextColor: '--ppcart-account-table-row-color',
		tableBorderColor: '--ppcart-account-table-border-color',
		buttonBackgroundColor: '--ppcart-account-button-background',
		buttonTextColor: '--ppcart-account-button-color',
		buttonHoverBackgroundColor: '--ppcart-account-button-hover-background',
		buttonHoverTextColor: '--ppcart-account-button-hover-color',
	};

	Object.entries( colorMap ).forEach( ( [ attribute, variable ] ) => {
		if ( attributes[ attribute ] ) {
			style[ variable ] = attributes[ attribute ];
		}
	} );

	return style;
}

export function buildAccountTabClasses( attributes, isActive ) {
	const classes = [
		'ppcart-account-tab-editor',
		'publishpress-cart-account-tab',
		isActive ? 'is-active' : 'is-inactive',
	];
	const panelStyle = attributes.panelStyle || 'plain';
	const style = buildAccountTabStyle( attributes );

	if ( panelStyle !== 'plain' ) {
		classes.push( `ppcart-account-tab-panel-style-${ panelStyle }` );
	}

	if ( panelStyle !== 'plain' || Object.keys( style ).length > 0 ) {
		classes.push( 'ppcart-account-tab-has-custom-panel' );
	}

	if ( attributes.tableDensity ) {
		classes.push( `ppcart-account-tab-table-${ attributes.tableDensity }` );
	}

	return classes.join( ' ' );
}

export function AccountTabEdit( { attributes, clientId, setAttributes } ) {
	const tabId = attributes.tabId || 'tab-orders';
	const definition = getAccountTabDefinition( tabId );
	const navigationState = useSelect(
		( select ) => {
			const editor = select( blockEditorStore );
			const parents = editor.getBlockParentsByBlockName(
				clientId,
				'publishpress-cart/account-navigation'
			);

			if ( ! parents.length ) {
				return null;
			}

			const navigationBlock = editor.getBlock(
				parents[ parents.length - 1 ]
			);

			return navigationBlock?.attributes || null;
		},
		[ clientId ]
	);
	const navigationPreviewState = useContext(
		AccountNavigationPreviewContext
	);
	let selectedNavigationTabs = defaultIncludedNavigationTabs;

	if ( navigationPreviewState ) {
		selectedNavigationTabs = navigationPreviewState.includedTabs;
	} else if ( navigationState ) {
		selectedNavigationTabs = getIncludedNavigationTabs( navigationState );
	}

	const isVisible = selectedNavigationTabs.includes( tabId );
	let resolvedActiveTab = selectedNavigationTabs[ 0 ] || null;

	if ( navigationPreviewState ) {
		resolvedActiveTab = navigationPreviewState.activeTab;
	} else if (
		navigationState &&
		selectedNavigationTabs.includes( navigationState.activeTab )
	) {
		resolvedActiveTab = navigationState.activeTab;
	}

	const isActive =
		isVisible && ( ! resolvedActiveTab || resolvedActiveTab === tabId );
	const tabStyle = buildAccountTabStyle( attributes );
	const blockProps = useBlockProps( {
		className: buildAccountTabClasses( attributes, isActive ),
		style: tabStyle,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'ppcart-account-tab-editor__content tabcontent active',
		},
		{
			allowedBlocks: definition.blocks,
			template: definition.template,
			templateLock: false,
		}
	);
	const tabColorGroups = [
		{
			title: __( 'Panel Color', 'publishpress-cart' ),
			controls: [
				{
					type: 'color',
					attribute: 'panelBackgroundColor',
					label: __( 'Panel background', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'panelTextColor',
					label: __( 'Panel text color', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'panelBorderColor',
					label: __( 'Panel border color', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'panelLinkColor',
					label: __( 'Panel link color', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'panelLinkHoverColor',
					label: __( 'Panel link hover color', 'publishpress-cart' ),
				},
			],
		},
		{
			title: __( 'Table Color', 'publishpress-cart' ),
			controls: [
				{
					type: 'color',
					attribute: 'tableHeaderBackgroundColor',
					label: __( 'Header background', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'tableHeaderTextColor',
					label: __( 'Header text color', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'tableRowBackgroundColor',
					label: __( 'Row background', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'tableRowAltBackgroundColor',
					label: __(
						'Alternate row background',
						'publishpress-cart'
					),
				},
				{
					type: 'color',
					attribute: 'tableRowTextColor',
					label: __( 'Row text color', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'tableBorderColor',
					label: __( 'Table border color', 'publishpress-cart' ),
				},
			],
		},
		{
			title: __( 'Content Color', 'publishpress-cart' ),
			controls: [
				{
					type: 'color',
					attribute: 'headingColor',
					label: __( 'Heading color', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'buttonBackgroundColor',
					label: __( 'Button background', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'buttonTextColor',
					label: __( 'Button text color', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'buttonHoverBackgroundColor',
					label: __( 'Button hover background', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'buttonHoverTextColor',
					label: __( 'Button hover text color', 'publishpress-cart' ),
				},
			],
		},
	];

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Account Tab', 'publishpress-cart' ) }
					initialOpen
				>
					<TextControl
						label={ __( 'Tab label', 'publishpress-cart' ) }
						value={ attributes.label || definition.label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="styles">
				{ tabColorGroups.map( ( group ) =>
					renderAccountColorSettings(
						group,
						attributes,
						setAttributes
					)
				) }
			</InspectorControls>
			<div className="ppcart-account-tab-editor__label">
				{ attributes.label || definition.label }
			</div>
			<div { ...innerBlocksProps } />
		</div>
	);
}
