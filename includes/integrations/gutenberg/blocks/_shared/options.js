import { __ } from '@wordpress/i18n';

export const config = window.publishpressCartAccountBlock || {};
export const navigationOptions =
	Array.isArray( config.navigationTabs ) && config.navigationTabs.length
		? config.navigationTabs
		: [
				{
					label: __( 'Orders', 'publishpress-cart' ),
					value: 'tab-orders',
				},
				{
					label: __( 'Subscriptions', 'publishpress-cart' ),
					value: 'tab-subscriptions',
				},
				{
					label: __( 'Installment Plans', 'publishpress-cart' ),
					value: 'tab-plans',
				},
				{
					label: __( 'Profile', 'publishpress-cart' ),
					value: 'tab-profile',
				},
				{
					label: __( 'Downloads', 'publishpress-cart' ),
					value: 'tab-files',
				},
		  ];
export const defaultIncludedNavigationTabs = navigationOptions.map(
	( option ) => option.value
);

export const navigationStyleOptions = [
	{ label: __( 'Underline', 'publishpress-cart' ), value: 'underline' },
	{ label: __( 'Pills', 'publishpress-cart' ), value: 'pills' },
	{ label: __( 'Boxed', 'publishpress-cart' ), value: 'boxed' },
];

export const navigationAlignmentOptions = [
	{ label: __( 'Left', 'publishpress-cart' ), value: 'left' },
	{ label: __( 'Center', 'publishpress-cart' ), value: 'center' },
	{ label: __( 'Right', 'publishpress-cart' ), value: 'right' },
	{ label: __( 'Stretch', 'publishpress-cart' ), value: 'stretch' },
];

export const fontWeightOptions = [
	{ label: __( 'Default', 'publishpress-cart' ), value: '' },
	{ label: __( 'Normal (400)', 'publishpress-cart' ), value: '400' },
	{ label: __( 'Medium (500)', 'publishpress-cart' ), value: '500' },
	{ label: __( 'Semibold (600)', 'publishpress-cart' ), value: '600' },
	{ label: __( 'Bold (700)', 'publishpress-cart' ), value: '700' },
];

export const fontSizeOptions = [
	{ name: __( 'S', 'publishpress-cart' ), slug: 'small', size: 12 },
	{ name: __( 'M', 'publishpress-cart' ), slug: 'medium', size: 14 },
	{ name: __( 'L', 'publishpress-cart' ), slug: 'large', size: 16 },
	{ name: __( 'XL', 'publishpress-cart' ), slug: 'x-large', size: 20 },
	{ name: __( 'XXL', 'publishpress-cart' ), slug: 'xx-large', size: 24 },
];

export const textAlignmentOptions = [
	{
		label: __( 'Align text left', 'publishpress-cart' ),
		value: 'left',
		icon: 'editor-alignleft',
	},
	{
		label: __( 'Align text center', 'publishpress-cart' ),
		value: 'center',
		icon: 'editor-aligncenter',
	},
	{
		label: __( 'Align text right', 'publishpress-cart' ),
		value: 'right',
		icon: 'editor-alignright',
	},
];

export const colorStateOptions = [
	{
		label: __( 'Normal State', 'publishpress-cart' ),
		value: 'normal',
	},
	{
		label: __( 'Hover State', 'publishpress-cart' ),
		value: 'hover',
	},
];

export const textTransformOptions = [
	{ label: __( 'Default', 'publishpress-cart' ), value: '' },
	{ label: __( 'None', 'publishpress-cart' ), value: 'none' },
	{ label: __( 'Uppercase', 'publishpress-cart' ), value: 'uppercase' },
	{ label: __( 'Lowercase', 'publishpress-cart' ), value: 'lowercase' },
	{ label: __( 'Capitalize', 'publishpress-cart' ), value: 'capitalize' },
];

export const accountListBlockStyleOptions = [
	{ label: __( 'Default', 'publishpress-cart' ), value: '' },
	{ label: __( 'Card', 'publishpress-cart' ), value: 'card' },
	{ label: __( 'Minimal', 'publishpress-cart' ), value: 'minimal' },
	{ label: __( 'Pill rows', 'publishpress-cart' ), value: 'pill' },
	{ label: __( 'Dashboard', 'publishpress-cart' ), value: 'dashboard' },
];

export const accountListBlockStyleOptionsWithoutDashboard =
	accountListBlockStyleOptions.filter(
		( option ) => option.value !== 'dashboard'
	);

export const accountOrdersBlockStyleOptions =
	accountListBlockStyleOptionsWithoutDashboard;

export const accountSubscriptionsBlockStyleOptions =
	accountListBlockStyleOptionsWithoutDashboard;

export const accountPaymentPlansBlockStyleOptions =
	accountListBlockStyleOptionsWithoutDashboard;

export const accountListItemLayoutOptions = [
	{ label: __( 'Table (default)', 'publishpress-cart' ), value: '' },
	{ label: __( 'Card grid', 'publishpress-cart' ), value: 'cards' },
	{ label: __( 'Stacked rows', 'publishpress-cart' ), value: 'stack' },
];

export const accountOrdersItemLayoutOptions =
	accountListItemLayoutOptions.filter(
		( option ) => option.value !== 'stack'
	);

export const accountSubscriptionsItemLayoutOptions =
	accountOrdersItemLayoutOptions;

export const accountPaymentPlansItemLayoutOptions =
	accountOrdersItemLayoutOptions;

export const statusPillStyleOptions = [
	{ label: __( 'Plain text', 'publishpress-cart' ), value: '' },
	{ label: __( 'Pill', 'publishpress-cart' ), value: 'pill' },
	{
		label: __( 'Pill (rounded square)', 'publishpress-cart' ),
		value: 'square',
	},
	{ label: __( 'Dot + label', 'publishpress-cart' ), value: 'dot' },
];

export const actionButtonShapeOptions = [
	{ label: __( 'Plain link', 'publishpress-cart' ), value: '' },
	{ label: __( 'Filled button', 'publishpress-cart' ), value: 'filled' },
	{ label: __( 'Ghost (outlined)', 'publishpress-cart' ), value: 'ghost' },
	{ label: __( 'Pill chip', 'publishpress-cart' ), value: 'chip' },
];

export const rowHoverEffectOptions = [
	{ label: __( 'None', 'publishpress-cart' ), value: '' },
	{ label: __( 'Lift (shadow)', 'publishpress-cart' ), value: 'lift' },
	{ label: __( 'Tint background', 'publishpress-cart' ), value: 'tint' },
	{
		label: __( 'Slide accent border', 'publishpress-cart' ),
		value: 'border-slide',
	},
	{ label: __( 'Highlight outline', 'publishpress-cart' ), value: 'outline' },
];

export const accountListDetailPresentationOptions = [
	{ label: __( 'Popup', 'publishpress-cart' ), value: '' },
	{ label: __( 'Slide right', 'publishpress-cart' ), value: 'slide-right' },
];

export const loginBlockStyleOptions = [
	{ label: __( 'Default', 'publishpress-cart' ), value: '' },
	{ label: __( 'Minimal', 'publishpress-cart' ), value: 'minimal' },
];

export const loginInputShapeOptions = [
	{ label: __( 'Default', 'publishpress-cart' ), value: '' },
	{ label: __( 'Pill', 'publishpress-cart' ), value: 'pill' },
	{ label: __( 'Underline only', 'publishpress-cart' ), value: 'underline' },
];

export const loginHoverEffectOptions = [
	{ label: __( 'None', 'publishpress-cart' ), value: '' },
	{ label: __( 'Card lift', 'publishpress-cart' ), value: 'lift' },
	{ label: __( 'Border glow', 'publishpress-cart' ), value: 'border' },
];

export const downloadsBlockStyleOptions = [
	{ label: __( 'Default', 'publishpress-cart' ), value: '' },
	{ label: __( 'Card', 'publishpress-cart' ), value: 'card' },
	{ label: __( 'Minimal', 'publishpress-cart' ), value: 'minimal' },
	{ label: __( 'Dashboard', 'publishpress-cart' ), value: 'dashboard' },
	{ label: __( 'Folder', 'publishpress-cart' ), value: 'folder' },
];

export const downloadsItemLayoutOptions = [
	{ label: __( 'List (default)', 'publishpress-cart' ), value: '' },
	{ label: __( 'Card grid', 'publishpress-cart' ), value: 'cards' },
	{ label: __( 'Compact rows', 'publishpress-cart' ), value: 'rows' },
	{ label: __( 'Tile grid', 'publishpress-cart' ), value: 'tiles' },
];

export const downloadsItemHoverOptions = [
	{ label: __( 'None', 'publishpress-cart' ), value: '' },
	{ label: __( 'Lift', 'publishpress-cart' ), value: 'lift' },
	{ label: __( 'Tint', 'publishpress-cart' ), value: 'tint' },
	{ label: __( 'Border slide', 'publishpress-cart' ), value: 'border-slide' },
	{ label: __( 'Reveal download', 'publishpress-cart' ), value: 'reveal' },
];

export const downloadsButtonShapeOptions = [
	{ label: __( 'Plain link', 'publishpress-cart' ), value: '' },
	{ label: __( 'Filled', 'publishpress-cart' ), value: 'filled' },
	{ label: __( 'Ghost', 'publishpress-cart' ), value: 'ghost' },
	{ label: __( 'Pill chip', 'publishpress-cart' ), value: 'chip' },
	{ label: __( 'Icon only', 'publishpress-cart' ), value: 'icon' },
];
