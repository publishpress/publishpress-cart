import { __ } from '@wordpress/i18n';
import {
	accountListDetailPresentationOptions,
	accountOrdersBlockStyleOptions,
	accountOrdersItemLayoutOptions,
} from '../_shared/options';
import { buildListStylePanel } from '../_shared/list-controls';
import metadata from './block.json';

export default {
	...metadata,
	controls: [
		{
			type: 'select',
			attribute: 'detailPresentation',
			label: __( 'Detail presentation', 'publishpress-cart' ),
			options: accountListDetailPresentationOptions,
			help: __(
				'Choose how order details open from this order list.',
				'publishpress-cart'
			),
		},
		{
			type: 'text',
			attribute: 'emptyText',
			label: __( 'Empty state message', 'publishpress-cart' ),
			help: __(
				'Leave empty for the default "No orders found" text.',
				'publishpress-cart'
			),
		},
	],
	panels: [
		buildListStylePanel( {
			styleOptions: accountOrdersBlockStyleOptions,
			styleHelp: __(
				'Applies a coordinated card / minimal / pill look.',
				'publishpress-cart'
			),
			itemLayoutOptions: accountOrdersItemLayoutOptions,
			itemLayoutHelp: __(
				'Switch between data table or card grid.',
				'publishpress-cart'
			),
			showStatusPillStyle: false,
			showActionButtonShape: false,
			showRowHoverEffect: false,
		} ),
		{
			title: __( 'Links', 'publishpress-cart' ),
			controls: [
				{
					type: 'color',
					attribute: 'productLinkColor',
					label: __( 'Product link color', 'publishpress-cart' ),
					stateLabel: __( 'Product link color', 'publishpress-cart' ),
					colorState: 'normal',
				},
				{
					type: 'color',
					attribute: 'productLinkHoverColor',
					label: __(
						'Product link hover color',
						'publishpress-cart'
					),
					stateLabel: __( 'Product link color', 'publishpress-cart' ),
					colorState: 'hover',
				},
				{
					type: 'color',
					attribute: 'actionLinkColor',
					label: __( 'View Order link color', 'publishpress-cart' ),
					stateLabel: __(
						'View Order link color',
						'publishpress-cart'
					),
					colorState: 'normal',
				},
				{
					type: 'color',
					attribute: 'actionLinkHoverColor',
					label: __( 'View Order hover color', 'publishpress-cart' ),
					stateLabel: __(
						'View Order link color',
						'publishpress-cart'
					),
					colorState: 'hover',
				},
			],
		},
		{
			title: __( 'Other Text', 'publishpress-cart' ),
			controls: [
				{
					type: 'color',
					attribute: 'statusColor',
					label: __( 'Status background color', 'publishpress-cart' ),
				},
			],
		},
	],
};
