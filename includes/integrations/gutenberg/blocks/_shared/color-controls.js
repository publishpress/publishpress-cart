import { ColorPalette, PanelColorSettings } from '@wordpress/block-editor';
import { Button, Dropdown, PanelBody } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { colorStateOptions } from './options';

export function getColorControls( controls = [] ) {
	return controls.filter( ( control ) => control.type === 'color' );
}

export function getAccountColorSettings( controls, attributes, setAttributes ) {
	return getColorControls( controls ).map( ( control ) => ( {
		label: control.label,
		value: attributes[ control.attribute ] || '',
		onChange: ( value ) =>
			setAttributes( { [ control.attribute ]: value || '' } ),
		clearable: true,
	} ) );
}

export function getAccountColorPanelGroups( controls, panels ) {
	const groups = [];
	const groupIndexes = {};

	const appendGroupControls = ( colorControls, title ) => {
		if ( ! colorControls.length ) {
			return;
		}

		const panelTitle = title || __( 'Color', 'publishpress-cart' );

		if ( groupIndexes[ panelTitle ] === undefined ) {
			groupIndexes[ panelTitle ] = groups.length;
			groups.push( {
				title: panelTitle,
				controls: [],
			} );
		}

		groups[ groupIndexes[ panelTitle ] ].controls.push( ...colorControls );
	};

	appendGroupControls(
		getColorControls( controls ),
		__( 'Color', 'publishpress-cart' )
	);

	panels.forEach( ( panel ) => {
		const panelColorControls = getColorControls( panel.controls || [] );

		if ( ! panelColorControls.length ) {
			return;
		}

		const groupedControls = {};

		panelColorControls.forEach( ( control ) => {
			const panelTitle =
				control.colorPanel ||
				panel.colorPanel ||
				__( 'Color', 'publishpress-cart' );

			if ( ! groupedControls[ panelTitle ] ) {
				groupedControls[ panelTitle ] = [];
			}

			groupedControls[ panelTitle ].push( control );
		} );

		Object.entries( groupedControls ).forEach(
			( [ panelTitle, colorControls ] ) => {
				appendGroupControls( colorControls, panelTitle );
			}
		);
	} );

	return groups;
}

export function getAccountStateColorRows( controls ) {
	const rows = [];
	const rowIndexes = {};

	controls.forEach( ( control ) => {
		const rowLabel = control.stateLabel || control.label;

		if ( rowIndexes[ rowLabel ] === undefined ) {
			rowIndexes[ rowLabel ] = rows.length;
			rows.push( {
				label: rowLabel,
				controls: [],
				controlsByState: {},
			} );
		}

		const row = rows[ rowIndexes[ rowLabel ] ];
		const colorState = control.colorState || 'normal';
		row.controls.push( control );
		row.controlsByState[ colorState ] = control;
	} );

	return rows;
}

export function AccountStateColorPanel( { group, attributes, setAttributes } ) {
	const [ activeStatesByRow, setActiveStatesByRow ] = useState( {} );
	const rows = getAccountStateColorRows( group.controls );

	return (
		<PanelBody key={ group.title } title={ group.title }>
			<div className="ppcart-account-color-state">
				<div className="ppcart-account-color-state__fields">
					{ rows.map( ( row ) => {
						const activeState =
							activeStatesByRow[ row.label ] || 'normal';
						const control =
							row.controlsByState[ activeState ] ||
							row.controlsByState.normal ||
							row.controls[ 0 ];
						const value = attributes[ control.attribute ] || '';
						const activeControlState =
							control.colorState || 'normal';
						const activeStateOption =
							colorStateOptions.find(
								( option ) =>
									option.value === activeControlState
							) || colorStateOptions[ 0 ];
						const availableStateOptions = colorStateOptions.filter(
							( option ) => row.controlsByState[ option.value ]
						);
						const hasStateToggle = availableStateOptions.length > 1;

						return (
							<div
								className="ppcart-account-color-state__field"
								key={ row.label }
							>
								<div className="ppcart-account-color-state__field-header">
									<label className="components-base-control__label">
										{ row.label }
									</label>
									{ hasStateToggle && (
										<Dropdown
											className="ppcart-account-color-state__dropdown"
											contentClassName="ppcart-account-color-state__dropdown-content"
											popoverProps={ {
												placement: 'bottom-start',
											} }
											renderToggle={ ( {
												isOpen,
												onToggle,
											} ) => (
												<Button
													className="ppcart-account-color-state__state-toggle"
													label={
														activeStateOption.label
													}
													title={
														activeStateOption.label
													}
													aria-expanded={ isOpen }
													onClick={ onToggle }
												>
													<ColorStateIcon
														state={
															activeControlState
														}
													/>
												</Button>
											) }
											renderContent={ ( { onClose } ) => (
												<div
													className="ppcart-account-color-state__state-menu"
													role="menu"
													aria-label={ __(
														'Color state',
														'publishpress-cart'
													) }
												>
													{ availableStateOptions.map(
														( option ) => (
															<Button
																key={
																	option.value
																}
																className="ppcart-account-color-state__state-menu-button"
																isPressed={
																	activeState ===
																	option.value
																}
																label={
																	option.label
																}
																title={
																	option.label
																}
																onClick={ () => {
																	setActiveStatesByRow(
																		(
																			currentStates
																		) => ( {
																			...currentStates,
																			[ row.label ]:
																				option.value,
																		} )
																	);
																	onClose();
																} }
																role="menuitemradio"
																aria-checked={
																	activeState ===
																	option.value
																}
															>
																<ColorStateIcon
																	state={
																		option.value
																	}
																/>
															</Button>
														)
													) }
												</div>
											) }
										/>
									) }
								</div>
								<AccountColorDropdown
									control={ control }
									value={ value }
									onChange={ ( nextValue ) =>
										setAttributes( {
											[ control.attribute ]:
												nextValue || '',
										} )
									}
								/>
							</div>
						);
					} ) }
				</div>
			</div>
		</PanelBody>
	);
}

export function AccountColorDropdown( { control, value, onChange } ) {
	return (
		<Dropdown
			className="ppcart-account-color-state__color-dropdown"
			contentClassName="ppcart-account-color-state__color-popover"
			popoverProps={ {
				placement: 'bottom-start',
			} }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					className="ppcart-account-color-state__color-toggle"
					label={ control.label }
					aria-expanded={ isOpen }
					onClick={ onToggle }
				>
					<AccountColorSwatch value={ value } />
					<span>{ control.stateLabel || control.label }</span>
				</Button>
			) }
			renderContent={ () => (
				<div className="ppcart-account-color-state__color-picker">
					<ColorPalette
						value={ value || '' }
						onChange={ ( nextValue ) =>
							onChange( nextValue || '' )
						}
						clearable
					/>
				</div>
			) }
		/>
	);
}

export function AccountColorSwatch( { value } ) {
	return (
		<span
			className="ppcart-account-color-state__swatch"
			style={
				value
					? {
							background: value,
					  }
					: undefined
			}
			aria-hidden="true"
		/>
	);
}

export function ColorStateIcon( { state } ) {
	const isHover = state === 'hover';

	return (
		<svg
			className="ppcart-account-color-state__icon"
			aria-hidden="true"
			focusable="false"
			viewBox="0 0 24 24"
		>
			<path d="M6.5 3.5 17.75 12l-5.3 1.15 3.25 5.65-2.7 1.55-3.25-5.65-3.25 4.15v-15.35Z" />
			{ isHover && (
				<path d="M16.25 4.25a4 4 0 0 1 3.5 3.5M14.9 7.1a2 2 0 0 1 2 2" />
			) }
		</svg>
	);
}

export function renderAccountColorSettings( group, attributes, setAttributes ) {
	const controls = Array.isArray( group )
		? group
		: Array.isArray( group.controls )
		? group.controls
		: [];
	const title = Array.isArray( group )
		? __( 'Color', 'publishpress-cart' )
		: group.title || __( 'Color', 'publishpress-cart' );

	if ( controls.some( ( control ) => control.colorState ) ) {
		return (
			<AccountStateColorPanel
				key={ title }
				group={ { ...group, title, controls } }
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
		);
	}

	const colorSettings = getAccountColorSettings(
		controls,
		attributes,
		setAttributes
	);

	if ( ! colorSettings.length ) {
		return null;
	}

	return (
		<PanelColorSettings
			key={ title }
			title={ title }
			colorSettings={ colorSettings }
		/>
	);
}
