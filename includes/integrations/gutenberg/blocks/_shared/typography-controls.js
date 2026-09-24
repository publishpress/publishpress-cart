import {
	FontSizePicker,
	PanelBody,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { fontSizeOptions } from './options';

export function isTypographyControl( control ) {
	return control.styleGroup === 'typography';
}

export function getNonColorControls( controls = [] ) {
	return controls.filter(
		( control ) =>
			control.type !== 'color' && ! isTypographyControl( control )
	);
}

export function getTypographyControls( controls = [] ) {
	return controls.filter( isTypographyControl );
}

export function getAccountTypographyControls( controls, panels ) {
	const typographyControls = [ ...getTypographyControls( controls ) ];

	panels.forEach( ( panel ) => {
		typographyControls.push(
			...getTypographyControls( panel.controls || [] )
		);
	} );

	return typographyControls;
}

export function getLinkedFontSizeValue( control, attributes ) {
	const attributeNames = [
		control.attribute,
		...( control.linkedAttributes || [] ),
	];

	for ( const attributeName of attributeNames ) {
		const value = attributes[ attributeName ];

		if ( Number.isFinite( value ) ) {
			return value;
		}
	}

	return undefined;
}

export function setLinkedFontSizeValue( control, value, setAttributes ) {
	const attributeNames = [
		control.attribute,
		...( control.linkedAttributes || [] ),
	];
	const nextAttributes = {};

	attributeNames.forEach( ( attributeName ) => {
		nextAttributes[ attributeName ] =
			value === undefined ? undefined : value;
	} );

	setAttributes( nextAttributes );
}

export function renderAccountTypographyControl(
	control,
	attributes,
	setAttributes
) {
	if ( control.type === 'fontSize' ) {
		return (
			<div
				className="ppcart-account-preview__font-size"
				key={ control.attribute }
			>
				<FontSizePicker
					fontSizes={ fontSizeOptions }
					value={ getLinkedFontSizeValue( control, attributes ) }
					onChange={ ( value ) =>
						setLinkedFontSizeValue( control, value, setAttributes )
					}
					withSlider={ false }
					__next40pxDefaultSize
				/>
			</div>
		);
	}

	if ( control.type === 'select' ) {
		return (
			<SelectControl
				key={ control.attribute }
				label={ control.label }
				value={
					attributes[ control.attribute ] ||
					control.defaultValue ||
					''
				}
				options={ control.options }
				onChange={ ( value ) =>
					setAttributes( { [ control.attribute ]: value } )
				}
				__nextHasNoMarginBottom
			/>
		);
	}

	return null;
}

export function renderAccountTypographySettings(
	controls,
	attributes,
	setAttributes
) {
	if ( ! controls.length ) {
		return null;
	}

	return (
		<PanelBody
			key="typography"
			title={ __( 'Typography', 'publishpress-cart' ) }
			initialOpen
		>
			{ controls.map( ( control ) =>
				renderAccountTypographyControl(
					control,
					attributes,
					setAttributes
				)
			) }
		</PanelBody>
	);
}
