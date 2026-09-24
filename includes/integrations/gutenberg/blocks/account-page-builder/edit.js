import { createBlock } from '@wordpress/blocks';
import {
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	AccountPageBuilderWidthControl,
	buildAccountPageBuilderEditorStyle,
	getAccountPageBuilderInnerLayoutClass,
	hasAccountPageBuilderContainerStyles,
} from './layout-controls';
import { renderAccountColorSettings } from '../_shared/color-controls';
import {
	accountNavigationAllowedBlocks,
	accountPageBuilderAllowedBlocks,
	accountPageBuilderTemplate,
	getAccountTabDefinitionForBlock,
	normalizeAccountNavigationBlocks,
} from '../_shared/tabs';

export function AccountPageBuilderEdit( {
	attributes,
	clientId,
	setAttributes,
} ) {
	const hasContainerStyles =
		hasAccountPageBuilderContainerStyles( attributes );
	const innerLayoutClass =
		getAccountPageBuilderInnerLayoutClass( attributes );
	const innerBlocks = useSelect(
		( select ) => select( blockEditorStore ).getBlocks( clientId ),
		[ clientId ]
	);
	const { replaceInnerBlocks } = useDispatch( blockEditorStore );
	const blockProps = useBlockProps( {
		className: [
			'publishpress-cart-account-page-builder',
			'ppcart-account-page-builder-editor',
			hasContainerStyles
				? 'ppcart-account-page-builder-has-container'
				: '',
			innerLayoutClass,
		]
			.filter( Boolean )
			.join( ' ' ),
		style: buildAccountPageBuilderEditorStyle( attributes ),
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: accountPageBuilderAllowedBlocks,
		template: accountPageBuilderTemplate,
		templateLock: false,
	} );
	const layoutColorControls = [
		{
			type: 'color',
			attribute: 'containerBackgroundColor',
			label: __( 'Container background', 'publishpress-cart' ),
		},
		{
			type: 'color',
			attribute: 'containerBorderColor',
			label: __( 'Container border color', 'publishpress-cart' ),
		},
	];

	useEffect( () => {
		const navigationIndex = innerBlocks.findIndex(
			( block ) => block.name === 'publishpress-cart/account-navigation'
		);

		if ( navigationIndex < 0 ) {
			return;
		}

		const navigationBlock = innerBlocks[ navigationIndex ];
		const nextLayoutBlocks = [];
		const movedBlocks = [];

		innerBlocks.forEach( ( block, index ) => {
			const shouldMoveIntoNavigation =
				index > navigationIndex &&
				( accountNavigationAllowedBlocks.includes( block.name ) ||
					getAccountTabDefinitionForBlock( block.name ) );

			if ( shouldMoveIntoNavigation ) {
				movedBlocks.push( block );
				return;
			}

			if ( index !== navigationIndex ) {
				nextLayoutBlocks.push( block );
			}
		} );

		const normalizedNavigation = normalizeAccountNavigationBlocks( [
			...navigationBlock.innerBlocks,
			...movedBlocks,
		] );

		if ( ! movedBlocks.length && ! normalizedNavigation.changed ) {
			return;
		}

		const nextNavigationBlock = createBlock(
			navigationBlock.name,
			navigationBlock.attributes,
			normalizedNavigation.blocks
		);

		nextLayoutBlocks.splice( navigationIndex, 0, nextNavigationBlock );
		replaceInnerBlocks( clientId, nextLayoutBlocks, false );
	}, [ clientId, innerBlocks, replaceInnerBlocks ] );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Container', 'publishpress-cart' ) }
					initialOpen={ false }
				>
					<AccountPageBuilderWidthControl
						attributes={ attributes }
						setAttributes={ setAttributes }
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="styles">
				{ renderAccountColorSettings(
					layoutColorControls,
					attributes,
					setAttributes
				) }
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}
