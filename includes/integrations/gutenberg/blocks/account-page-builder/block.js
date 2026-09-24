import { InnerBlocks } from '@wordpress/block-editor';
import { AccountPageBuilderEdit } from './edit';
import metadata from './block.json';

export default {
	...metadata,
	edit: AccountPageBuilderEdit,
	save: () => <InnerBlocks.Content />,
};
