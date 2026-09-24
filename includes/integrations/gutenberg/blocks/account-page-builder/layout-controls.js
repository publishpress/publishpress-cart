import {
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const accountPageBuilderWidthUnits = [
	{ value: 'px', label: 'px', default: 480 },
	{ value: '%', label: '%', default: 100 },
	{ value: 'em', label: 'em', default: 40 },
	{ value: 'rem', label: 'rem', default: 40 },
	{ value: 'vw', label: 'vw', default: 100 },
	{ value: 'vh', label: 'vh', default: 100 },
];
export const accountPageBuilderWidthUnitSettings = {
	px: { min: 320, max: 1600, step: 1 },
	'%': { min: 1, max: 100, step: 1 },
	em: { min: 1, max: 120, step: 0.1 },
	rem: { min: 1, max: 120, step: 0.1 },
	vw: { min: 1, max: 100, step: 1 },
	vh: { min: 1, max: 100, step: 1 },
};
export const accountPageBuilderInnerLayoutOptions = [
	{ label: __( 'Default', 'publishpress-cart' ), value: '' },
	{ label: __( 'Left', 'publishpress-cart' ), value: 'left' },
	{ label: __( 'Center', 'publishpress-cart' ), value: 'center' },
	{ label: __( 'Right', 'publishpress-cart' ), value: 'right' },
	{ label: __( 'Stretch', 'publishpress-cart' ), value: 'stretch' },
];

export function getAccountPageBuilderWidthUnit( unit ) {
	return accountPageBuilderWidthUnits.some(
		( option ) => option.value === unit
	)
		? unit
		: 'px';
}

export function getAccountPageBuilderWidthSetting( unit ) {
	return (
		accountPageBuilderWidthUnitSettings[
			getAccountPageBuilderWidthUnit( unit )
		] || accountPageBuilderWidthUnitSettings.px
	);
}

export function clampAccountPageBuilderWidth( value, unit ) {
	const setting = getAccountPageBuilderWidthSetting( unit );

	return Math.max( setting.min, Math.min( setting.max, value ) );
}

export function formatAccountPageBuilderWidthNumber( value ) {
	if ( ! Number.isFinite( value ) ) {
		return '';
	}

	return Number.isInteger( value )
		? String( value )
		: String( parseFloat( value.toFixed( 4 ) ) );
}

export function getAccountPageBuilderWidthControlValue( attributes ) {
	const width = attributes.contentWidth;

	if ( ! Number.isFinite( width ) ) {
		return '';
	}

	const unit = getAccountPageBuilderWidthUnit( attributes.contentWidthUnit );

	return `${ formatAccountPageBuilderWidthNumber( width ) }${ unit }`;
}

export function parseAccountPageBuilderWidthControlValue(
	value,
	fallbackUnit
) {
	if ( value === undefined || value === null || value === '' ) {
		return null;
	}

	const match = String( value )
		.trim()
		.match( /^(-?\d+(?:\.\d+)?)([a-z%]*)$/i );

	if ( ! match ) {
		return null;
	}

	const unit = getAccountPageBuilderWidthUnit( match[ 2 ] || fallbackUnit );
	const width = Number( match[ 1 ] );

	if ( ! Number.isFinite( width ) ) {
		return null;
	}

	return {
		width: clampAccountPageBuilderWidth( Math.abs( width ), unit ),
		unit,
	};
}

export function AccountPageBuilderWidthControl( {
	attributes,
	setAttributes,
} ) {
	const unit = getAccountPageBuilderWidthUnit( attributes.contentWidthUnit );
	const setting = getAccountPageBuilderWidthSetting( unit );
	const value = getAccountPageBuilderWidthControlValue( attributes );
	const label = __( 'Content max width', 'publishpress-cart' );
	const help = __(
		'Leave empty to inherit the theme content width.',
		'publishpress-cart'
	);

	return (
		<div className="ppcart-account-page-builder-width-control">
			<UnitControl
				label={ label }
				labelPosition="top"
				help={ help }
				value={ value }
				units={ accountPageBuilderWidthUnits }
				onChange={ ( nextValue ) => {
					if ( ! nextValue ) {
						setAttributes( {
							contentWidth: undefined,
							contentWidthUnit: undefined,
						} );
						return;
					}

					const parsedValue =
						parseAccountPageBuilderWidthControlValue(
							nextValue,
							unit
						);

					if ( ! parsedValue ) {
						return;
					}

					setAttributes( {
						contentWidth: parsedValue.width,
						contentWidthUnit: parsedValue.unit,
					} );
				} }
				min={ setting.min }
				max={ setting.max }
				step={ setting.step }
				isResetValueOnUnitChange
				prefix={
					<InputControlPrefixWrapper variant="icon">
						<span
							className="dashicons dashicons-editor-justify"
							aria-hidden="true"
						/>
					</InputControlPrefixWrapper>
				}
				__next40pxDefaultSize
			/>
		</div>
	);
}

export function hasAccountPageBuilderContainerStyles( attributes ) {
	return (
		!! getAccountPageBuilderWidthControlValue( attributes ) ||
		Number.isFinite( attributes.containerPadding ) ||
		Number.isFinite( attributes.containerRadius ) ||
		!! attributes.containerBackgroundColor ||
		!! attributes.containerBorderColor
	);
}

export function getAccountPageBuilderInnerLayout( attributes ) {
	const value = attributes.innerLayout || '';
	return accountPageBuilderInnerLayoutOptions.some(
		( option ) => option.value === value
	)
		? value
		: '';
}

export function getAccountPageBuilderInnerLayoutClass( attributes ) {
	const innerLayout = getAccountPageBuilderInnerLayout( attributes );

	return innerLayout
		? `ppcart-account-page-builder-inner-${ innerLayout }`
		: '';
}

export function buildAccountPageBuilderEditorStyle( attributes ) {
	const style = {};
	const width = getAccountPageBuilderWidthControlValue( attributes );

	if ( width ) {
		style.maxWidth = width;
		style[ '--ppcart-account-page-builder-max-width' ] = width;
	}

	if ( Number.isFinite( attributes.containerPadding ) ) {
		style[
			'--ppcart-account-page-builder-padding'
		] = `${ attributes.containerPadding }px`;
	}

	if ( Number.isFinite( attributes.containerRadius ) ) {
		style[
			'--ppcart-account-page-builder-radius'
		] = `${ attributes.containerRadius }px`;
	}

	if ( attributes.containerBackgroundColor ) {
		style[ '--ppcart-account-page-builder-background' ] =
			attributes.containerBackgroundColor;
	}

	if ( attributes.containerBorderColor ) {
		style[ '--ppcart-account-page-builder-border-color' ] =
			attributes.containerBorderColor;
	}

	return style;
}
