import { __ } from '@wordpress/i18n';
import {
	accountPaymentPlansBlockStyleOptions,
	accountPaymentPlansItemLayoutOptions,
} from '../_shared/options';
import { buildListStylePanel } from '../_shared/list-controls';
import metadata from './block.json';

export default {
	...metadata,
	controls: [
		{
			type: 'checkboxes',
			attribute: 'visibleGroups',
			label: __( 'Visible status groups', 'publishpress-cart' ),
			options: [
				{
					label: __( 'Active', 'publishpress-cart' ),
					value: 'all',
				},
				{
					label: __( 'Past Due', 'publishpress-cart' ),
					value: 'past_due',
				},
				{
					label: __( 'Expired / Completed', 'publishpress-cart' ),
					value: 'completed',
				},
			],
		},
	],
	panels: [
		buildListStylePanel( {
			styleOptions: accountPaymentPlansBlockStyleOptions,
			styleHelp: __(
				'Applies a coordinated card / minimal / pill look.',
				'publishpress-cart'
			),
			itemLayoutOptions: accountPaymentPlansItemLayoutOptions,
			itemLayoutHelp: __(
				'Switch between data table or card grid.',
				'publishpress-cart'
			),
			showStatusPillStyle: false,
			showActionButtonShape: false,
			showRowHoverEffect: false,
		} ),
		{
			title: __( 'Action Links', 'publishpress-cart' ),
			controls: [
				{
					type: 'color',
					attribute: 'actionLinkColor',
					label: __( 'Pay / Manage link color', 'publishpress-cart' ),
					stateLabel: __(
						'Pay / Manage link color',
						'publishpress-cart'
					),
					colorState: 'normal',
				},
				{
					type: 'color',
					attribute: 'actionLinkHoverColor',
					label: __(
						'Pay / Manage hover color',
						'publishpress-cart'
					),
					stateLabel: __(
						'Pay / Manage link color',
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
