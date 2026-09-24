import { InnerBlocks } from '@wordpress/block-editor';
import { AccountTabEdit } from './edit';
import metadata from './block.json';

export default {
	...metadata,
	edit: AccountTabEdit,
	save: () => <InnerBlocks.Content />,
};
