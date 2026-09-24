import { __ } from '@wordpress/i18n';
import {
	downloadsBlockStyleOptions,
	downloadsButtonShapeOptions,
	downloadsItemHoverOptions,
	downloadsItemLayoutOptions,
} from '../_shared/options';
import metadata from './block.json';

export default {
	...metadata,
	controls: [
		{
			type: 'text',
			attribute: 'emptyText',
			label: __( 'Empty state message', 'publishpress-cart' ),
			help: __(
				'Shown when no downloads are available.',
				'publishpress-cart'
			),
		},
	],
	panels: [
		{
			title: __( 'Style', 'publishpress-cart' ),
			initialOpen: true,
			controls: [
				{
					type: 'select',
					attribute: 'blockStyle',
					label: __( 'Preset style', 'publishpress-cart' ),
					options: downloadsBlockStyleOptions,
					help: __(
						'Card / Minimal / Dashboard / Folder.',
						'publishpress-cart'
					),
				},
				{
					type: 'select',
					attribute: 'itemLayout',
					label: __( 'Item layout', 'publishpress-cart' ),
					options: downloadsItemLayoutOptions,
				},
				{
					type: 'select',
					attribute: 'itemHoverEffect',
					label: __( 'Item hover effect', 'publishpress-cart' ),
					options: downloadsItemHoverOptions,
				},
				{
					type: 'select',
					attribute: 'buttonShape',
					label: __( 'Download button shape', 'publishpress-cart' ),
					options: downloadsButtonShapeOptions,
				},
				{
					type: 'toggle',
					attribute: 'showFileIcon',
					label: __( 'Show file icon', 'publishpress-cart' ),
				},
			],
		},
		{
			title: __( 'Colors', 'publishpress-cart' ),
			controls: [
				{
					type: 'color',
					attribute: 'linkColor',
					label: __( 'Download link color', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'linkHoverColor',
					label: __(
						'Download link hover color',
						'publishpress-cart'
					),
				},
				{
					type: 'color',
					attribute: 'itemBackgroundColor',
					label: __( 'Item background', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'itemBorderColor',
					label: __( 'Item border color', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'emptyTextColor',
					label: __( 'Empty state color', 'publishpress-cart' ),
				},
			],
		},
	],
};
