import { createBlock } from '@wordpress/blocks';
import { createContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { defaultIncludedNavigationTabs } from './options';

export const AccountNavigationPreviewContext = createContext( null );

export function getIncludedNavigationTabs( attributes = {} ) {
	if (
		attributes.includedTabsConfigured &&
		Array.isArray( attributes.includedTabs )
	) {
		return attributes.includedTabs;
	}

	if (
		Array.isArray( attributes.includedTabs ) &&
		attributes.includedTabs.length
	) {
		return attributes.includedTabs;
	}

	return defaultIncludedNavigationTabs;
}

export function getVisibleNavigationTabId(
	visibleTabs,
	preferredTab,
	fallbackTab = 'tab-orders'
) {
	const visibleTabIds = visibleTabs.map( ( tab ) => tab.tabId );

	if ( preferredTab && visibleTabIds.includes( preferredTab ) ) {
		return preferredTab;
	}

	if ( fallbackTab && visibleTabIds.includes( fallbackTab ) ) {
		return fallbackTab;
	}

	return (
		( visibleTabs[ 0 ] && visibleTabs[ 0 ].tabId ) ||
		preferredTab ||
		fallbackTab
	);
}

export const accountTabDefinitions = [
	{
		tabId: 'tab-orders',
		label: __( 'Orders', 'publishpress-cart' ),
		blocks: [ 'publishpress-cart/account-orders' ],
		template: [ [ 'publishpress-cart/account-orders', {} ] ],
	},
	{
		tabId: 'tab-subscriptions',
		label: __( 'Subscriptions', 'publishpress-cart' ),
		blocks: [ 'publishpress-cart/account-subscriptions' ],
		template: [ [ 'publishpress-cart/account-subscriptions', {} ] ],
	},
	{
		tabId: 'tab-plans',
		label: __( 'Installment Plans', 'publishpress-cart' ),
		blocks: [ 'publishpress-cart/account-payment-plans' ],
		template: [ [ 'publishpress-cart/account-payment-plans', {} ] ],
	},
	{
		tabId: 'tab-profile',
		label: __( 'My Profile', 'publishpress-cart' ),
		blocks: [ 'publishpress-cart/account-profile' ],
		template: [ [ 'publishpress-cart/account-profile', {} ] ],
	},
	{
		tabId: 'tab-files',
		label: __( 'Downloads', 'publishpress-cart' ),
		blocks: [ 'publishpress-cart/account-downloads' ],
		template: [ [ 'publishpress-cart/account-downloads', {} ] ],
	},
];

export function getAccountTabDefinition( tabId ) {
	return (
		accountTabDefinitions.find(
			( definition ) => definition.tabId === tabId
		) || accountTabDefinitions[ 0 ]
	);
}

export function getAccountTabDefinitionForBlock( blockName ) {
	return accountTabDefinitions.find( ( definition ) =>
		definition.blocks.includes( blockName )
	);
}

export function createBlocksFromTemplate( template = [] ) {
	return template.map( ( [ name, attributes = {}, children = [] ] ) =>
		createBlock( name, attributes, createBlocksFromTemplate( children ) )
	);
}

export function createAccountTabBlock( tabId, innerBlocks = null ) {
	const definition = getAccountTabDefinition( tabId );
	const childBlocks = Array.isArray( innerBlocks )
		? innerBlocks
		: createBlocksFromTemplate( definition.template );

	return createBlock(
		'publishpress-cart/account-tab',
		{
			tabId: definition.tabId,
			label: definition.label,
		},
		childBlocks
	);
}

export function appendBlocksToAccountTab( tabBlock, blocks ) {
	return createBlock( tabBlock.name, tabBlock.attributes, [
		...tabBlock.innerBlocks,
		...blocks,
	] );
}

export function normalizeAccountNavigationBlocks( innerBlocks ) {
	const normalizedBlocks = [];
	const groupedBlocks = {};
	let changed = false;

	innerBlocks.forEach( ( block ) => {
		if ( block.name === 'publishpress-cart/account-tab' ) {
			normalizedBlocks.push( block );
			return;
		}

		const definition = getAccountTabDefinitionForBlock( block.name );

		if ( definition ) {
			if ( ! groupedBlocks[ definition.tabId ] ) {
				groupedBlocks[ definition.tabId ] = [];
			}

			groupedBlocks[ definition.tabId ].push( block );
			changed = true;
			return;
		}

		normalizedBlocks.push( block );
	} );

	accountTabDefinitions.forEach( ( definition ) => {
		const blocks = groupedBlocks[ definition.tabId ] || [];

		if ( ! blocks.length ) {
			return;
		}

		const existingIndex = normalizedBlocks.findIndex(
			( block ) =>
				block.name === 'publishpress-cart/account-tab' &&
				block.attributes?.tabId === definition.tabId
		);

		if ( existingIndex >= 0 ) {
			normalizedBlocks[ existingIndex ] = appendBlocksToAccountTab(
				normalizedBlocks[ existingIndex ],
				blocks
			);
			return;
		}

		normalizedBlocks.push(
			createAccountTabBlock( definition.tabId, blocks )
		);
	} );

	return {
		blocks: normalizedBlocks,
		changed,
	};
}

export const accountPageBuilderAllowedBlocks = [
	'publishpress-cart/account-login',
	'publishpress-cart/account-navigation',
	'publishpress-cart/account-orders',
	'publishpress-cart/account-subscriptions',
	'publishpress-cart/account-payment-plans',
	'publishpress-cart/account-profile',
	'publishpress-cart/account-downloads',
];

export const accountNavigationAllowedBlocks = [
	'publishpress-cart/account-tab',
];

export const accountNavigationTemplate = [
	[
		'publishpress-cart/account-tab',
		{ tabId: 'tab-orders', label: __( 'Orders', 'publishpress-cart' ) },
		[ [ 'publishpress-cart/account-orders', {} ] ],
	],
	[
		'publishpress-cart/account-tab',
		{
			tabId: 'tab-subscriptions',
			label: __( 'Subscriptions', 'publishpress-cart' ),
		},
		[ [ 'publishpress-cart/account-subscriptions', {} ] ],
	],
	[
		'publishpress-cart/account-tab',
		{
			tabId: 'tab-plans',
			label: __( 'Installment Plans', 'publishpress-cart' ),
		},
		[ [ 'publishpress-cart/account-payment-plans', {} ] ],
	],
	[
		'publishpress-cart/account-tab',
		{
			tabId: 'tab-profile',
			label: __( 'My Profile', 'publishpress-cart' ),
		},
		[ [ 'publishpress-cart/account-profile', {} ] ],
	],
];

export const accountPageBuilderTemplate = [
	[ 'publishpress-cart/account-login', {} ],
	[ 'publishpress-cart/account-navigation', {}, accountNavigationTemplate ],
];
