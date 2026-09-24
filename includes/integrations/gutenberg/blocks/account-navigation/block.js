import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { navigationOptions } from '../_shared/options';
import { AccountNavigationEdit } from './edit';
import metadata from './block.json';

export default {
	...metadata,
	controls: [
		{
			type: 'select',
			attribute: 'activeTab',
			label: __( 'Initial tab', 'publishpress-cart' ),
			options: navigationOptions,
		},
		{
			type: 'checkboxes',
			attribute: 'includedTabs',
			label: __( 'Visible tabs', 'publishpress-cart' ),
			options: navigationOptions,
		},
	],
	edit: AccountNavigationEdit,
	save: () => <InnerBlocks.Content />,
};
