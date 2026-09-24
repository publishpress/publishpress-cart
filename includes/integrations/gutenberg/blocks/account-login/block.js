import { __ } from '@wordpress/i18n';
import {
	fontWeightOptions,
	loginBlockStyleOptions,
	loginHoverEffectOptions,
	loginInputShapeOptions,
} from '../_shared/options';
import metadata from './block.json';

export default {
	...metadata,
	controls: [
		{
			type: 'toggle',
			attribute: 'hideWhenLoggedIn',
			label: __( 'Hide when logged in', 'publishpress-cart' ),
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
					options: loginBlockStyleOptions,
					help: __(
						'Applies structural login form presets. Colors remain editable in the controls below.',
						'publishpress-cart'
					),
				},
				{
					type: 'select',
					attribute: 'inputShape',
					label: __( 'Input shape', 'publishpress-cart' ),
					options: loginInputShapeOptions,
				},
				{
					type: 'select',
					attribute: 'hoverEffect',
					label: __( 'Hover effect', 'publishpress-cart' ),
					options: loginHoverEffectOptions,
				},
			],
		},
		{
			title: __( 'Form Container', 'publishpress-cart' ),
			colorPanel: __( 'Form', 'publishpress-cart' ),
			controls: [
				{
					type: 'alignmentDropdown',
					attribute: 'formAlignment',
					label: __( 'Alignment', 'publishpress-cart' ),
				},
				{
					type: 'unitRange',
					attribute: 'formMaxWidth',
					label: __( 'Max. content width', 'publishpress-cart' ),
					min: 280,
					max: 800,
					step: 10,
					unit: 'px',
				},
				{
					type: 'unitRange',
					attribute: 'formPadding',
					label: __( 'Paddings', 'publishpress-cart' ),
					min: 0,
					max: 80,
					unit: 'px',
				},
				{
					type: 'unitRange',
					attribute: 'formRadius',
					label: __( 'Border radius', 'publishpress-cart' ),
					min: 0,
					max: 32,
					unit: 'px',
				},
				{
					type: 'color',
					attribute: 'formBackgroundColor',
					label: __( 'Form background', 'publishpress-cart' ),
					colorPanel: __( 'Form', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'formBorderColor',
					label: __( 'Form border color', 'publishpress-cart' ),
					colorPanel: __( 'Form', 'publishpress-cart' ),
				},
				{
					type: 'color',
					attribute: 'labelColor',
					label: __( 'Label color', 'publishpress-cart' ),
					colorPanel: __( 'Text', 'publishpress-cart' ),
				},
			],
		},
		{
			title: __( 'Heading', 'publishpress-cart' ),
			controls: [
				{
					type: 'text',
					attribute: 'headingText',
					label: __( 'Heading text', 'publishpress-cart' ),
					help: __(
						'Shown above the login fields. Leave empty to hide it.',
						'publishpress-cart'
					),
				},
				{
					type: 'alignmentDropdown',
					attribute: 'headingAlignment',
					label: __( 'Heading alignment', 'publishpress-cart' ),
				},
				{
					type: 'fontSize',
					attribute: 'headingFontSize',
					label: __( 'Heading font size', 'publishpress-cart' ),
				},
				{
					type: 'select',
					attribute: 'headingFontWeight',
					label: __( 'Heading font weight', 'publishpress-cart' ),
					options: fontWeightOptions,
				},
				{
					type: 'color',
					attribute: 'headingColor',
					label: __( 'Heading color', 'publishpress-cart' ),
					colorPanel: __( 'Text', 'publishpress-cart' ),
				},
			],
		},
		{
			title: __( 'Description', 'publishpress-cart' ),
			controls: [
				{
					type: 'textarea',
					attribute: 'descriptionText',
					label: __( 'Description text', 'publishpress-cart' ),
					help: __(
						'Shown below the heading. Leave empty to hide it.',
						'publishpress-cart'
					),
				},
				{
					type: 'alignmentDropdown',
					attribute: 'descriptionAlignment',
					label: __( 'Description alignment', 'publishpress-cart' ),
				},
				{
					type: 'fontSize',
					attribute: 'descriptionFontSize',
					label: __( 'Description font size', 'publishpress-cart' ),
				},
				{
					type: 'select',
					attribute: 'descriptionFontWeight',
					label: __( 'Description font weight', 'publishpress-cart' ),
					options: fontWeightOptions,
				},
				{
					type: 'color',
					attribute: 'descriptionColor',
					label: __( 'Description color', 'publishpress-cart' ),
					colorPanel: __( 'Text', 'publishpress-cart' ),
				},
			],
		},
		{
			title: __( 'Button', 'publishpress-cart' ),
			colorPanel: __( 'Button', 'publishpress-cart' ),
			controls: [
				{
					type: 'color',
					attribute: 'buttonBackgroundColor',
					label: __( 'Button color', 'publishpress-cart' ),
					stateLabel: __( 'Button color', 'publishpress-cart' ),
					colorState: 'normal',
				},
				{
					type: 'color',
					attribute: 'buttonTextColor',
					label: __( 'Text color', 'publishpress-cart' ),
					stateLabel: __( 'Text color', 'publishpress-cart' ),
					colorState: 'normal',
				},
				{
					type: 'color',
					attribute: 'buttonHoverBackgroundColor',
					label: __( 'Hover button color', 'publishpress-cart' ),
					stateLabel: __( 'Button color', 'publishpress-cart' ),
					colorState: 'hover',
				},
				{
					type: 'color',
					attribute: 'buttonHoverTextColor',
					label: __( 'Hover text color', 'publishpress-cart' ),
					stateLabel: __( 'Text color', 'publishpress-cart' ),
					colorState: 'hover',
				},
			],
		},
	],
};
