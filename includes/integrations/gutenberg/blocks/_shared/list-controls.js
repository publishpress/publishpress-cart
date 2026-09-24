import { __ } from '@wordpress/i18n';
import {
	actionButtonShapeOptions,
	accountListBlockStyleOptions,
	accountListItemLayoutOptions,
	rowHoverEffectOptions,
	statusPillStyleOptions,
} from './options';

export function buildListStylePanel( {
	styleOptions = accountListBlockStyleOptions,
	styleHelp = __(
		'Applies a coordinated card / minimal / pill / dashboard look.',
		'publishpress-cart'
	),
	itemLayoutOptions = accountListItemLayoutOptions,
	itemLayoutHelp = __(
		'Switch between data table, card grid, or stacked rows.',
		'publishpress-cart'
	),
	statusPillOptions = statusPillStyleOptions,
	showStatusPillStyle = true,
	showActionButtonShape = true,
	showRowHoverEffect = true,
} = {} ) {
	const controls = [
		{
			type: 'select',
			attribute: 'blockStyle',
			label: __( 'Preset style', 'publishpress-cart' ),
			options: styleOptions,
			help: styleHelp,
		},
		{
			type: 'select',
			attribute: 'itemLayout',
			label: __( 'Item layout', 'publishpress-cart' ),
			options: itemLayoutOptions,
			help: itemLayoutHelp,
		},
	];

	if ( showStatusPillStyle ) {
		controls.push( {
			type: 'select',
			attribute: 'statusPillStyle',
			label: __( 'Status indicator', 'publishpress-cart' ),
			options: statusPillOptions,
		} );
	}

	if ( showActionButtonShape ) {
		controls.push( {
			type: 'select',
			attribute: 'actionButtonShape',
			label: __( 'Action button shape', 'publishpress-cart' ),
			options: actionButtonShapeOptions,
		} );
	}

	if ( showRowHoverEffect ) {
		controls.push( {
			type: 'select',
			attribute: 'rowHoverEffect',
			label: __( 'Hover effect', 'publishpress-cart' ),
			options: rowHoverEffectOptions,
		} );
	}

	return {
		title: __( 'Style', 'publishpress-cart' ),
		initialOpen: true,
		controls,
	};
}
