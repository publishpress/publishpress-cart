import {
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import {
	CheckboxControl,
	PanelBody,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { renderAccountColorSettings } from '../_shared/color-controls';
import {
	navigationAlignmentOptions,
	navigationOptions,
	navigationStyleOptions,
} from '../_shared/options';
import {
	AccountNavigationPreviewContext,
	accountNavigationAllowedBlocks,
	accountNavigationTemplate,
	getAccountTabDefinition,
	getIncludedNavigationTabs,
	getVisibleNavigationTabId,
	normalizeAccountNavigationBlocks,
} from '../_shared/tabs';

export function buildAccountNavigationClasses( attributes ) {
	const classes = [ 'publishpress-cart-account-navigation' ];
	const tabStyle = attributes.tabStyle || 'underline';

	if ( tabStyle !== 'underline' ) {
		classes.push( `ppcart-account-nav-style-${ tabStyle }` );
	}

	const tabAlignment = attributes.tabAlignment || 'left';

	if ( tabAlignment !== 'left' ) {
		classes.push( `ppcart-account-nav-align-${ tabAlignment }` );
	}

	if ( attributes.tabTextTransform ) {
		classes.push(
			`ppcart-account-nav-transform-${ attributes.tabTextTransform }`
		);
	}

	return classes.join( ' ' );
}

export function buildAccountNavigationStyle( attributes ) {
	const style = {};

	if ( Number.isFinite( attributes.tabGap ) && attributes.tabGap !== 48 ) {
		style[ '--ppcart-account-nav-gap' ] = `${ attributes.tabGap }px`;
	}
	if ( Number.isFinite( attributes.tabPaddingX ) ) {
		style[
			'--ppcart-account-nav-padding-x'
		] = `${ attributes.tabPaddingX }px`;
	}
	if ( Number.isFinite( attributes.tabPaddingY ) ) {
		style[
			'--ppcart-account-nav-padding-y'
		] = `${ attributes.tabPaddingY }px`;
	}
	if ( Number.isFinite( attributes.tabFontSize ) ) {
		style[
			'--ppcart-account-nav-font-size'
		] = `${ attributes.tabFontSize }px`;
	}
	if ( attributes.tabFontWeight ) {
		style[ '--ppcart-account-nav-font-weight' ] = attributes.tabFontWeight;
	}
	if ( Number.isFinite( attributes.tabIndicatorThickness ) ) {
		style[
			'--ppcart-account-nav-indicator'
		] = `${ attributes.tabIndicatorThickness }px`;
	}

	const colorMap = {
		accentColor: '--ppcart-account-nav-accent',
		tabTextColor: '--ppcart-account-nav-color',
		activeTabTextColor: '--ppcart-account-nav-active-color',
		hoverTabTextColor: '--ppcart-account-nav-hover-color',
		tabBackgroundColor: '--ppcart-account-nav-background',
		activeTabBackgroundColor: '--ppcart-account-nav-active-background',
		hoverTabBackgroundColor: '--ppcart-account-nav-hover-background',
	};

	Object.entries( colorMap ).forEach( ( [ attribute, variable ] ) => {
		if ( attributes[ attribute ] ) {
			style[ variable ] = attributes[ attribute ];
		}
	} );

	return style;
}

export function AccountNavigationEdit( {
	attributes,
	clientId,
	setAttributes,
} ) {
	const [ previewActiveTab, setPreviewActiveTab ] = useState(
		attributes.activeTab || 'tab-orders'
	);
	const innerBlocks = useSelect(
		( select ) => select( blockEditorStore ).getBlocks( clientId ),
		[ clientId ]
	);
	const { replaceInnerBlocks } = useDispatch( blockEditorStore );
	const blockProps = useBlockProps( {
		className: 'ppcart-account-navigation-editor',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'ppcart-account-navigation-editor__content',
		},
		{
			allowedBlocks: accountNavigationAllowedBlocks,
			template: accountNavigationTemplate,
			templateLock: false,
		}
	);

	useEffect( () => {
		const normalizedNavigation =
			normalizeAccountNavigationBlocks( innerBlocks );

		if ( normalizedNavigation.changed ) {
			replaceInnerBlocks( clientId, normalizedNavigation.blocks, false );
		}
	}, [ clientId, innerBlocks, replaceInnerBlocks ] );

	useEffect( () => {
		setPreviewActiveTab( attributes.activeTab || 'tab-orders' );
	}, [ attributes.activeTab ] );

	const editorTabs = innerBlocks
		.filter( ( block ) => block.name === 'publishpress-cart/account-tab' )
		.map( ( block ) => {
			const tabId = block.attributes?.tabId || 'tab-orders';
			const fallback = getAccountTabDefinition( tabId );
			return {
				tabId,
				label: block.attributes?.label || fallback.label,
			};
		} );

	const selectedNavigationTabs = getIncludedNavigationTabs( attributes );
	const visibleTabs = editorTabs.filter( ( tab ) =>
		selectedNavigationTabs.includes( tab.tabId )
	);
	const activeTab = getVisibleNavigationTabId(
		visibleTabs,
		previewActiveTab,
		attributes.activeTab || 'tab-orders'
	);
	const navigationPreviewState = {
		activeTab,
		includedTabs: selectedNavigationTabs,
	};
	const navigationColorControls = [
		{
			type: 'color',
			attribute: 'accentColor',
			label: __( 'Accent color', 'publishpress-cart' ),
			stateLabel: __( 'Accent color', 'publishpress-cart' ),
			colorState: 'normal',
		},
		{
			type: 'color',
			attribute: 'tabTextColor',
			label: __( 'Tab text color', 'publishpress-cart' ),
			stateLabel: __( 'Tab text color', 'publishpress-cart' ),
			colorState: 'normal',
		},
		{
			type: 'color',
			attribute: 'hoverTabTextColor',
			label: __( 'Hover tab text color', 'publishpress-cart' ),
			stateLabel: __( 'Tab text color', 'publishpress-cart' ),
			colorState: 'hover',
		},
		{
			type: 'color',
			attribute: 'activeTabTextColor',
			label: __( 'Active tab text color', 'publishpress-cart' ),
			stateLabel: __( 'Active tab text color', 'publishpress-cart' ),
			colorState: 'normal',
		},
		{
			type: 'color',
			attribute: 'tabBackgroundColor',
			label: __( 'Tab background', 'publishpress-cart' ),
			stateLabel: __( 'Tab background', 'publishpress-cart' ),
			colorState: 'normal',
		},
		{
			type: 'color',
			attribute: 'hoverTabBackgroundColor',
			label: __( 'Hover tab background', 'publishpress-cart' ),
			stateLabel: __( 'Tab background', 'publishpress-cart' ),
			colorState: 'hover',
		},
		{
			type: 'color',
			attribute: 'activeTabBackgroundColor',
			label: __( 'Active tab background', 'publishpress-cart' ),
			stateLabel: __( 'Active tab background', 'publishpress-cart' ),
			colorState: 'normal',
		},
	];

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Account Display', 'publishpress-cart' ) }
					initialOpen
				>
					<SelectControl
						label={ __( 'Initial tab', 'publishpress-cart' ) }
						value={ attributes.activeTab || 'tab-orders' }
						options={ navigationOptions }
						onChange={ ( value ) =>
							setAttributes( { activeTab: value } )
						}
						__nextHasNoMarginBottom
					/>
					<div className="ppcart-account-preview__checkboxes">
						<p className="components-base-control__label">
							{ __( 'Visible tabs', 'publishpress-cart' ) }
						</p>
						{ navigationOptions.map( ( option ) => {
							return (
								<CheckboxControl
									key={ option.value }
									label={ option.label }
									checked={ selectedNavigationTabs.includes(
										option.value
									) }
									onChange={ ( checked ) => {
										const nextValues = checked
											? Array.from(
													new Set( [
														...selectedNavigationTabs,
														option.value,
													] )
											  )
											: selectedNavigationTabs.filter(
													( value ) =>
														value !== option.value
											  );
										const nextAttributes = {
											includedTabs: nextValues,
											includedTabsConfigured: true,
										};

										if (
											nextValues.length &&
											! nextValues.includes(
												attributes.activeTab
											)
										) {
											nextAttributes.activeTab =
												nextValues[ 0 ];
										}

										setAttributes( {
											...nextAttributes,
										} );
									} }
									__nextHasNoMarginBottom
								/>
							);
						} ) }
					</div>
				</PanelBody>
				<PanelBody
					title={ __( 'Navigation Style', 'publishpress-cart' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Tab style', 'publishpress-cart' ) }
						value={ attributes.tabStyle || 'underline' }
						options={ navigationStyleOptions }
						onChange={ ( value ) =>
							setAttributes( { tabStyle: value } )
						}
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Alignment', 'publishpress-cart' ) }
						value={ attributes.tabAlignment || 'left' }
						options={ navigationAlignmentOptions }
						onChange={ ( value ) =>
							setAttributes( { tabAlignment: value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="styles">
				{ renderAccountColorSettings(
					navigationColorControls,
					attributes,
					setAttributes
				) }
			</InspectorControls>

			<div
				className={ `ppcart-account-navigation-editor__preview ${ buildAccountNavigationClasses(
					attributes
				) }` }
				style={ buildAccountNavigationStyle( attributes ) }
			>
				<div className="tab">
					<ul className="ppcart-nav-tabs">
						{ visibleTabs.map( ( tab ) => (
							<li key={ tab.tabId }>
								<a
									className={ `tablinks${
										tab.tabId === activeTab ? ' active' : ''
									}` }
									href={ `#${ tab.tabId }` }
									onClick={ ( event ) => {
										event.preventDefault();
										setPreviewActiveTab( tab.tabId );
									} }
								>
									{ tab.label }
								</a>
							</li>
						) ) }
					</ul>
				</div>
			</div>
			<AccountNavigationPreviewContext.Provider
				value={ navigationPreviewState }
			>
				<div { ...innerBlocksProps } data-active-tab={ activeTab } />
			</AccountNavigationPreviewContext.Provider>
		</div>
	);
}
