import { __ } from '@wordpress/i18n';
import { fontWeightOptions } from '../_shared/options';
import metadata from './block.json';

export default {
	...metadata,
	panels: [
		{
			title: __( 'Style', 'publishpress-cart' ),
			initialOpen: true,
			controls: [
				{
					type: 'toggle',
					attribute: 'showAvatar',
					label: __(
						'Show avatar identity header',
						'publishpress-cart'
					),
				},
			],
		},
		{
			title: __( 'Typography', 'publishpress-cart' ),
			controls: [
				{
					type: 'fontSize',
					styleGroup: 'typography',
					attribute: 'labelFontSize',
					linkedAttributes: [ 'inputFontSize' ],
					label: __( 'Font size', 'publishpress-cart' ),
				},
				{
					type: 'select',
					styleGroup: 'typography',
					attribute: 'labelFontWeight',
					label: __( 'Font weight', 'publishpress-cart' ),
					options: fontWeightOptions,
				},
			],
		},
		{
			title: __( 'Button', 'publishpress-cart' ),
			controls: [
				{
					type: 'color',
					attribute: 'buttonBackgroundColor',
					label: __( 'Button Background Color', 'publishpress-cart' ),
					stateLabel: __(
						'Button Background Color',
						'publishpress-cart'
					),
					colorState: 'normal',
				},
				{
					type: 'color',
					attribute: 'buttonHoverBackgroundColor',
					label: __(
						'Button Hover Background Color',
						'publishpress-cart'
					),
					stateLabel: __(
						'Button Background Color',
						'publishpress-cart'
					),
					colorState: 'hover',
				},
				{
					type: 'color',
					attribute: 'buttonTextColor',
					label: __( 'Button Text Color', 'publishpress-cart' ),
					stateLabel: __( 'Button Text Color', 'publishpress-cart' ),
					colorState: 'normal',
				},
				{
					type: 'color',
					attribute: 'buttonHoverTextColor',
					label: __( 'Button Hover Text Color', 'publishpress-cart' ),
					stateLabel: __( 'Button Text Color', 'publishpress-cart' ),
					colorState: 'hover',
				},
			],
		},
	],
};
