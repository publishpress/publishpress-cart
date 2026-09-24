import {
	Button,
	ButtonGroup,
	CheckboxControl,
	FontSizePicker,
	RangeControl,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { fontSizeOptions, textAlignmentOptions } from './options';

export function renderAccountControl( control, attributes, setAttributes ) {
	if ( control.type === 'select' ) {
		return (
			<SelectControl
				key={ control.attribute }
				label={ control.label }
				help={ control.help }
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

	if ( control.type === 'text' ) {
		return (
			<TextControl
				key={ control.attribute }
				label={ control.label }
				help={ control.help }
				value={ attributes[ control.attribute ] || '' }
				onChange={ ( value ) =>
					setAttributes( { [ control.attribute ]: value } )
				}
				__nextHasNoMarginBottom
			/>
		);
	}

	if ( control.type === 'textarea' ) {
		return (
			<TextareaControl
				key={ control.attribute }
				label={ control.label }
				help={ control.help }
				value={ attributes[ control.attribute ] || '' }
				onChange={ ( value ) =>
					setAttributes( { [ control.attribute ]: value } )
				}
				__nextHasNoMarginBottom
			/>
		);
	}

	if ( control.type === 'range' ) {
		const currentValue = attributes[ control.attribute ];
		return (
			<RangeControl
				key={ control.attribute }
				label={ control.label }
				help={ control.help }
				value={ Number.isFinite( currentValue ) ? currentValue : '' }
				onChange={ ( value ) =>
					setAttributes( {
						[ control.attribute ]:
							value === undefined ? undefined : value,
					} )
				}
				min={ control.min }
				max={ control.max }
				step={ control.step || 1 }
				allowReset
				__nextHasNoMarginBottom
			/>
		);
	}

	if ( control.type === 'unitRange' ) {
		const currentValue = attributes[ control.attribute ];
		const hasValue = Number.isFinite( currentValue );
		const min = Number.isFinite( control.min ) ? control.min : 0;
		const max = Number.isFinite( control.max ) ? control.max : 100;
		const step = control.step || 1;
		const sliderValue = hasValue
			? currentValue
			: Number.isFinite( control.defaultValue )
			? control.defaultValue
			: min;
		const sliderProgress =
			max > min ? ( ( sliderValue - min ) / ( max - min ) ) * 100 : 0;
		const setUnitValue = ( value ) => {
			if ( value === undefined || value === '' ) {
				setAttributes( { [ control.attribute ]: undefined } );
				return;
			}

			const nextValue = Number( value );

			if ( ! Number.isFinite( nextValue ) ) {
				return;
			}

			setAttributes( {
				[ control.attribute ]: Math.max(
					min,
					Math.min( max, nextValue )
				),
			} );
		};

		return (
			<div
				className="ppcart-account-preview__unit-range"
				key={ control.attribute }
			>
				<div className="ppcart-account-preview__unit-range-header">
					<span className="components-base-control__label">
						{ control.label }
					</span>
					<span className="ppcart-account-preview__unit-range-unit">
						{ control.unit || 'px' }
					</span>
				</div>
				{ control.help && (
					<p className="components-base-control__help">
						{ control.help }
					</p>
				) }
				<div className="ppcart-account-preview__unit-range-body">
					<input
						aria-label={ control.label }
						className="ppcart-account-preview__unit-range-slider"
						type="range"
						value={ sliderValue }
						style={ {
							'--ppcart-unit-range-progress': `${ sliderProgress }%`,
						} }
						onChange={ ( event ) =>
							setUnitValue( event.target.value )
						}
						min={ min }
						max={ max }
						step={ step }
					/>
					<input
						aria-label={ `${ control.label } ${
							control.unit || 'px'
						}` }
						className="ppcart-account-preview__unit-range-input"
						type="number"
						value={ hasValue ? currentValue : '' }
						onChange={ ( event ) =>
							setUnitValue( event.target.value )
						}
						min={ min }
						max={ max }
						step={ step }
					/>
					<Button
						className="ppcart-account-preview__unit-range-reset"
						variant="secondary"
						size="small"
						disabled={ ! hasValue }
						onClick={ () => setUnitValue( undefined ) }
					>
						{ __( 'Reset', 'publishpress-cart' ) }
					</Button>
				</div>
			</div>
		);
	}

	if ( control.type === 'fontSize' ) {
		const currentValue = attributes[ control.attribute ];

		return (
			<div
				className="ppcart-account-preview__font-size"
				key={ control.attribute }
			>
				<FontSizePicker
					fontSizes={ fontSizeOptions }
					value={
						Number.isFinite( currentValue )
							? currentValue
							: undefined
					}
					onChange={ ( value ) =>
						setAttributes( {
							[ control.attribute ]:
								value === undefined ? undefined : value,
						} )
					}
					withSlider={ false }
					__next40pxDefaultSize
				/>
			</div>
		);
	}

	if ( control.type === 'alignmentDropdown' ) {
		const currentValue = attributes[ control.attribute ] || 'left';

		return (
			<div
				className="ppcart-account-preview__alignment"
				key={ control.attribute }
			>
				<p className="components-base-control__label">
					{ control.label }
				</p>
				<ButtonGroup className="ppcart-account-preview__alignment-buttons">
					{ textAlignmentOptions.map( ( option ) => (
						<Button
							key={ option.value }
							icon={ option.icon }
							label={ option.label }
							isPressed={ currentValue === option.value }
							variant={
								currentValue === option.value
									? 'primary'
									: 'secondary'
							}
							onClick={ () =>
								setAttributes( {
									[ control.attribute ]: option.value,
								} )
							}
							__next40pxDefaultSize
						/>
					) ) }
				</ButtonGroup>
			</div>
		);
	}

	if ( control.type === 'checkboxes' ) {
		const selectedValues = Array.isArray( attributes[ control.attribute ] )
			? attributes[ control.attribute ]
			: [];

		return (
			<div
				className="ppcart-account-preview__checkboxes"
				key={ control.attribute }
			>
				<p className="components-base-control__label">
					{ control.label }
				</p>
				{ control.options.map( ( option ) => (
					<CheckboxControl
						key={ option.value }
						label={ option.label }
						checked={ selectedValues.includes( option.value ) }
						onChange={ ( checked ) => {
							const nextValues = checked
								? [ ...selectedValues, option.value ]
								: selectedValues.filter(
										( value ) => value !== option.value
								  );

							setAttributes( {
								[ control.attribute ]: nextValues,
							} );
						} }
						__nextHasNoMarginBottom
					/>
				) ) }
			</div>
		);
	}

	return (
		<ToggleControl
			key={ control.attribute }
			label={ control.label }
			help={ control.help }
			checked={ !! attributes[ control.attribute ] }
			onChange={ ( value ) =>
				setAttributes( { [ control.attribute ]: value } )
			}
			__nextHasNoMarginBottom
		/>
	);
}
