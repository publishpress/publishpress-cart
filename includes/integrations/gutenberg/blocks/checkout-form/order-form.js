import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	ButtonGroup,
	ColorPalette,
	Notice,
	PanelBody,
	PanelRow,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import {
	useEffect,
	useLayoutEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const config = window.publishpressCartCheckoutBlock || {};
const blockName = config.blockName || 'publishpress-cart/checkout-form';
const restBase = config.restBase || '/publishpress-cart/v1';
const productPostTypes = Array.isArray( config.productPostTypes ) && config.productPostTypes.length
	? config.productPostTypes.map( ( postType ) => String( postType ) )
	: [ 'ppcart_product' ];
const blockTitle = __( 'Checkout', 'publishpress-cart' );
const siteTitle = config.siteTitle || __( 'Site title', 'publishpress-cart' );

function normalizeProductId( value ) {
	const productId = Number.parseInt( value, 10 );

	return Number.isInteger( productId ) && productId > 0 ? String( productId ) : '';
}

const templateOptions = [
	{ label: __( 'Product default', 'publishpress-cart' ), value: '' },
	{ label: __( 'Normal', 'publishpress-cart' ), value: 'normal' },
	{ label: __( '2-Step', 'publishpress-cart' ), value: '2-step' },
	{ label: __( 'Opt-in', 'publishpress-cart' ), value: 'opt-in' },
	{ label: __( 'Split-in', 'publishpress-cart' ), value: 'split-in' },
];

const styleColorPalette = [
	{ name: __( 'Blue', 'publishpress-cart' ), color: '#2271b1' },
	{ name: __( 'Indigo', 'publishpress-cart' ), color: '#4f46e5' },
	{ name: __( 'Green', 'publishpress-cart' ), color: '#15803d' },
	{ name: __( 'Gold', 'publishpress-cart' ), color: '#b7791f' },
	{ name: __( 'Red', 'publishpress-cart' ), color: '#b91c1c' },
	{ name: __( 'Black', 'publishpress-cart' ), color: '#111827' },
];

const stylePresetOptions = [
	{ label: __( 'Product default', 'publishpress-cart' ), value: '' },
	{ label: __( 'Minimal', 'publishpress-cart' ), value: 'minimal' },
	{ label: __( 'Carded', 'publishpress-cart' ), value: 'carded' },
	{ label: __( 'Compact', 'publishpress-cart' ), value: 'compact' },
	{ label: __( 'High contrast', 'publishpress-cart' ), value: 'contrast' },
];

const designStyleKeys = [
	'preset',
	'accentColor',
	'surfaceStyle',
	'density',
	'cornerRadius',
];

const defaultContentOrder = [
	'payment_plan',
	'coupon',
	'contact_info',
	'payment_method',
	'payment_details',
	'order_bumps',
	'order_summary',
	'terms_consent',
	'express_payment',
	'submit_button',
];

const arrangementSections = {
	payment_plan: __( 'Payment Plan', 'publishpress-cart' ),
	coupon: __( 'Coupon', 'publishpress-cart' ),
	contact_info: __( 'Contact Info', 'publishpress-cart' ),
	payment_method: __( 'Payment Method', 'publishpress-cart' ),
	payment_details: __( 'Payment Details', 'publishpress-cart' ),
	order_bumps: __( 'Order Bumps', 'publishpress-cart' ),
	order_summary: __( 'Order Summary', 'publishpress-cart' ),
	terms_consent: __( 'Terms & Consent', 'publishpress-cart' ),
	express_payment: __( 'Express Payment', 'publishpress-cart' ),
	submit_button: __( 'Submit Button', 'publishpress-cart' ),
};

const textSettingDefaults = {
	contactInfoHeading: __( 'Contact Info', 'publishpress-cart' ),
	paymentPlanHeading: __( 'Payment Plan', 'publishpress-cart' ),
	paymentInfoHeading: __( 'Payment Info', 'publishpress-cart' ),
	orderSummaryHeading: __( 'Order Summary', 'publishpress-cart' ),
	orderTotalHeading: __( 'Order Total', 'publishpress-cart' ),
	dueTodayLabel: __( 'Due Today', 'publishpress-cart' ),
	amountDueLabel: __( 'Amount Due', 'publishpress-cart' ),
	twoStepTabOneHeading: __( 'Get it Now', 'publishpress-cart' ),
	twoStepTabOneSubheading: __( 'Your Info', 'publishpress-cart' ),
	twoStepTabTwoHeading: __( 'Payment', 'publishpress-cart' ),
	twoStepTabTwoSubheading: __( 'of your order', 'publishpress-cart' ),
	splitSiteHeading: siteTitle,
	splitFormHeading: __( 'Get ready to start selling', 'publishpress-cart' ),
};

const commonTextControls = [
	{
		key: 'contactInfoHeading',
		label: __( 'Contact info heading', 'publishpress-cart' ),
	},
	{
		key: 'paymentPlanHeading',
		label: __( 'Payment plan heading', 'publishpress-cart' ),
	},
	{
		key: 'paymentInfoHeading',
		label: __( 'Payment info heading', 'publishpress-cart' ),
		excludedTemplates: [ 'opt-in' ],
	},
	{
		key: 'orderTotalHeading',
		label: __( 'Order total heading', 'publishpress-cart' ),
	},
	{
		key: 'orderSummaryHeading',
		label: __( 'Order summary heading', 'publishpress-cart' ),
	},
	{
		key: 'dueTodayLabel',
		label: __( 'Due today label', 'publishpress-cart' ),
	},
	{
		key: 'amountDueLabel',
		label: __( 'Amount due label', 'publishpress-cart' ),
	},
];

const twoStepTextControls = [
	{
		key: 'twoStepTabOneHeading',
		label: __( 'Step 1 heading', 'publishpress-cart' ),
	},
	{
		key: 'twoStepTabOneSubheading',
		label: __( 'Step 1 subheading', 'publishpress-cart' ),
	},
	{
		key: 'twoStepTabTwoHeading',
		label: __( 'Step 2 heading', 'publishpress-cart' ),
	},
	{
		key: 'twoStepTabTwoSubheading',
		label: __( 'Step 2 subheading', 'publishpress-cart' ),
	},
];

const splitInTextControls = [
	{
		key: 'splitSiteHeading',
		label: __( 'Site heading', 'publishpress-cart' ),
	},
	{
		key: 'splitFormHeading',
		label: __( 'Form heading', 'publishpress-cart' ),
	},
];

function normalizeContentOrder( contentOrder ) {
	const savedOrder = Array.isArray( contentOrder ) ? contentOrder : [];
	const normalized = [];

	savedOrder.forEach( ( sectionId ) => {
		if (
			defaultContentOrder.includes( sectionId ) &&
			! normalized.includes( sectionId )
		) {
			normalized.push( sectionId );
		}
	} );

	defaultContentOrder.forEach( ( sectionId ) => {
		if ( ! normalized.includes( sectionId ) ) {
			normalized.push( sectionId );
		}
	} );

	return normalized;
}

function areOrdersEqual( first, second ) {
	return JSON.stringify( first ) === JSON.stringify( second );
}

function moveItemToPlacement( items, sourceId, targetId, placement = 'before' ) {
	if ( ! sourceId || ! targetId || sourceId === targetId ) {
		return items;
	}

	if ( ! items.includes( sourceId ) || ! items.includes( targetId ) ) {
		return items;
	}

	const nextItems = items.filter( ( item ) => item !== sourceId );
	const targetIndex = nextItems.indexOf( targetId );

	if ( targetIndex < 0 ) {
		return items;
	}

	nextItems.splice( targetIndex + ( placement === 'after' ? 1 : 0 ), 0, sourceId );

	return nextItems;
}

function getDropPlacement( event ) {
	const rect = event.currentTarget.getBoundingClientRect();

	return event.clientY > rect.top + rect.height / 2 ? 'after' : 'before';
}

function buildQuery( params ) {
	return Object.keys( params )
		.filter( ( key ) => params[ key ] !== undefined && params[ key ] !== null )
		.map(
			( key ) =>
				`${ encodeURIComponent( key ) }=${ encodeURIComponent( params[ key ] ) }`
		)
		.join( '&' );
}

function getProductOptions( products, isLoading ) {
	if ( isLoading ) {
		return [ { label: __( 'Loading...', 'publishpress-cart' ), value: '' } ];
	}

	return [
		{ label: __( 'Dynamic product', 'publishpress-cart' ), value: '' },
		...products.map( ( product ) => ( {
			label: product.title,
			value: String( product.id ),
		} ) ),
	];
}

function SegmentedControl( { label, value, options, onChange } ) {
	return (
		<BaseControl label={ label } __nextHasNoMarginBottom>
			<ButtonGroup>
				{ options.map( ( option ) => (
					<Button
						key={ option.value }
						isPressed={ ( value || '' ) === option.value }
						variant={ ( value || '' ) === option.value ? 'primary' : 'secondary' }
						onClick={ () => onChange( option.value ) }
					>
						{ option.label }
					</Button>
				) ) }
			</ButtonGroup>
		</BaseControl>
	);
}

function ColorSetting( { label, value, onChange } ) {
	return (
		<BaseControl label={ label } __nextHasNoMarginBottom>
			<ColorPalette
				colors={ styleColorPalette }
				value={ value || '' }
				onChange={ ( nextValue ) => onChange( nextValue || '' ) }
			/>
			{ value && (
				<Button variant="secondary" onClick={ () => onChange( '' ) }>
					{ __( 'Clear color', 'publishpress-cart' ) }
				</Button>
			) }
		</BaseControl>
	);
}

function getTextSettingGroups( template ) {
	const normalizedTemplate = template || 'normal';
	const groups = [];

	if ( normalizedTemplate === '2-step' ) {
		groups.push( {
			label: __( 'Step tabs', 'publishpress-cart' ),
			controls: twoStepTextControls,
		} );
	}

	if ( normalizedTemplate === 'split-in' ) {
		groups.push( {
			label: __( 'Split-in', 'publishpress-cart' ),
			controls: splitInTextControls,
		} );
	}

	groups.push( {
		label: __( 'Section headings', 'publishpress-cart' ),
		controls: commonTextControls.filter(
			( control ) =>
				! control.excludedTemplates ||
				! control.excludedTemplates.includes( normalizedTemplate )
		),
	} );

	return groups;
}

function TextSettingsControl( { value, template, onChange, onReset } ) {
	const textSettings = value || {};
	const groups = getTextSettingGroups( template );
	const hasOverrides = Object.keys( textSettings ).length > 0;

	return (
		<div className="ppcart-text-settings">
			{ groups.map( ( group ) => (
				<div className="ppcart-text-settings__group" key={ group.label }>
					<div className="ppcart-text-settings__group-label">
						{ group.label }
					</div>
					{ group.controls.map( ( control ) => (
						<TextControl
							key={ control.key }
							label={ control.label }
							value={
								Object.prototype.hasOwnProperty.call(
									textSettings,
									control.key
								)
									? textSettings[ control.key ]
									: ''
							}
							placeholder={ textSettingDefaults[ control.key ] }
							onChange={ ( nextValue ) => onChange( control.key, nextValue ) }
							__nextHasNoMarginBottom
						/>
					) ) }
				</div>
			) ) }
			<Button
				className="ppcart-text-settings__reset"
				variant="secondary"
				onClick={ onReset }
				disabled={ ! hasOverrides }
			>
				{ __( 'Reset text', 'publishpress-cart' ) }
			</Button>
		</div>
	);
}

function SortableSectionList( {
	items,
	availability,
	draggedItem,
	isSortable = true,
	onDragStart,
	onDragEnd,
	onPreviewMove,
	onMove,
} ) {
	const itemRefs = useRef( {} );
	const previousRects = useRef( {} );

	const captureItemRects = () => {
		previousRects.current = items.reduce( ( rects, sectionId ) => {
			const element = itemRefs.current[ sectionId ];

			if ( element ) {
				rects[ sectionId ] = element.getBoundingClientRect();
			}

			return rects;
		}, {} );
	};

	useLayoutEffect( () => {
		const rects = previousRects.current;

		if ( ! Object.keys( rects ).length ) {
			return;
		}

		items.forEach( ( sectionId ) => {
			const element = itemRefs.current[ sectionId ];
			const previousRect = rects[ sectionId ];

			if ( ! element || ! previousRect ) {
				return;
			}

			const nextRect = element.getBoundingClientRect();
			const deltaX = previousRect.left - nextRect.left;
			const deltaY = previousRect.top - nextRect.top;

			if ( ! deltaX && ! deltaY ) {
				return;
			}

			element.style.transition = 'none';
			element.style.transform = `translate(${ deltaX }px, ${ deltaY }px)`;
			element.style.zIndex = '2';

			window.requestAnimationFrame( () => {
				element.style.transition = '';
				element.style.transform = '';
			} );

			const cleanup = () => {
				element.style.zIndex = '';
				element.removeEventListener( 'transitionend', cleanup );
			};

			element.addEventListener( 'transitionend', cleanup );
		} );

		previousRects.current = {};
	}, [ items ] );

	return (
		<div className="ppcart-arrangement-list">
			{ items.map( ( sectionId, index ) => {
				const isUnavailable = availability[ sectionId ] === false;
				const canDrag = isSortable;
				const canReceiveDrop = isSortable && draggedItem && draggedItem !== sectionId;
				const isDragging = draggedItem === sectionId;
				const label = arrangementSections[ sectionId ] || sectionId;
				const moveUpLabel = sprintf(
					/* translators: %s: checkout form section label. */
					__( 'Move %s up', 'publishpress-cart' ),
					label
				);
				const moveDownLabel = sprintf(
					/* translators: %s: checkout form section label. */
					__( 'Move %s down', 'publishpress-cart' ),
					label
				);

				return (
					<div
						key={ sectionId }
						ref={ ( element ) => {
							if ( element ) {
								itemRefs.current[ sectionId ] = element;
							} else {
								delete itemRefs.current[ sectionId ];
							}
						} }
						className={ `ppcart-arrangement-item${
							isUnavailable ? ' is-unavailable' : ''
						}${ ! isSortable ? ' is-locked' : '' }${
							isDragging ? ' is-dragging' : ''
						}${ canReceiveDrop ? ' is-drop-target' : '' }` }
						draggable={ canDrag }
						aria-disabled={ ! isSortable }
						onDragStart={ ( event ) => {
							if ( ! canDrag ) {
								event.preventDefault();
								return;
							}

							event.dataTransfer.effectAllowed = 'move';
							onDragStart( sectionId );
						} }
						onDragOver={ ( event ) => {
							event.preventDefault();
							event.dataTransfer.dropEffect = isSortable ? 'move' : 'none';

							if ( ! isSortable ) {
								return;
							}

							captureItemRects();
							onPreviewMove( draggedItem, sectionId, getDropPlacement( event ) );
						} }
						onDrop={ ( event ) => {
							event.preventDefault();
							if ( ! isSortable ) {
								return;
							}

							captureItemRects();
							onMove( draggedItem, sectionId, getDropPlacement( event ) );
						} }
						onDragEnd={ onDragEnd }
					>
						<span className="ppcart-arrangement-item__handle" aria-hidden>
							{ [ 0, 1, 2, 3, 4, 5 ].map( ( dot ) => (
								<span key={ dot } />
							) ) }
						</span>
						<span className="ppcart-arrangement-item__content">
							<span className="ppcart-arrangement-item__label">
								{ label }
							</span>
							{ isUnavailable && (
								<span className="ppcart-arrangement-item__status">
									{ __( 'Unavailable', 'publishpress-cart' ) }
								</span>
							) }
						</span>
						<span className="ppcart-arrangement-item__actions">
							<Button
								className="ppcart-arrangement-item__move"
								size="small"
								variant="tertiary"
								aria-label={ moveUpLabel }
								title={ moveUpLabel }
								disabled={ ! canDrag || index === 0 }
								onClick={ () => {
									captureItemRects();
									onMove( sectionId, items[ index - 1 ], 'before' );
								} }
							>
								<span
									className="ppcart-arrangement-item__move-icon is-up"
									aria-hidden
								/>
							</Button>
							<Button
								className="ppcart-arrangement-item__move"
								size="small"
								variant="tertiary"
								aria-label={ moveDownLabel }
								title={ moveDownLabel }
								disabled={ ! canDrag || index === items.length - 1 }
								onClick={ () => {
									captureItemRects();
									onMove( sectionId, items[ index + 1 ], 'after' );
								} }
							>
								<span
									className="ppcart-arrangement-item__move-icon is-down"
									aria-hidden
								/>
							</Button>
						</span>
					</div>
				);
			} ) }
		</div>
	);
}

function ContentArrangementControl( { value, template, availability, onChange } ) {
	const [ draggedItem, setDraggedItem ] = useState( null );
	const [ previewOrder, setPreviewOrder ] = useState( null );
	const previewTimer = useRef( null );
	const didDrop = useRef( false );
	const draggedItemRef = useRef( null );
	const normalizedOrder = normalizeContentOrder( value );
	const activeOrder = previewOrder || normalizedOrder;
	const isTwoStep = template === '2-step';
	const stepOneItems = [ 'contact_info' ];
	const stepTwoItems = activeOrder.filter( ( sectionId ) => sectionId !== 'contact_info' );
	const sortableItems = isTwoStep ? stepTwoItems : activeOrder;

	const updateOrder = ( nextOrder ) => {
		const normalizedNextOrder = normalizeContentOrder( nextOrder );

		if ( areOrdersEqual( normalizedNextOrder, defaultContentOrder ) ) {
			onChange( [] );
			return;
		}

		onChange( normalizedNextOrder );
	};

	const clearPreviewTimer = () => {
		if ( previewTimer.current ) {
			clearTimeout( previewTimer.current );
			previewTimer.current = null;
		}
	};

	const getMovedOrder = ( order, sourceId, targetId, placement ) => {
		if ( isTwoStep ) {
			const nextStepTwoItems = moveItemToPlacement(
				order.filter( ( sectionId ) => sectionId !== 'contact_info' ),
				sourceId,
				targetId,
				placement
			);

			return [ 'contact_info', ...nextStepTwoItems ];
		}

		return moveItemToPlacement( order, sourceId, targetId, placement );
	};

	const handleDragStart = ( sectionId ) => {
		clearPreviewTimer();
		didDrop.current = false;
		draggedItemRef.current = sectionId;
		setDraggedItem( sectionId );
		setPreviewOrder( normalizedOrder );
	};

	const handlePreviewMove = ( sourceId, targetId, placement ) => {
		clearPreviewTimer();
		sourceId = sourceId || draggedItemRef.current;

		if ( ! sourceId || ! targetId || sourceId === targetId ) {
			return;
		}

		previewTimer.current = setTimeout( () => {
			setPreviewOrder( ( currentPreviewOrder ) => {
				const baseOrder = currentPreviewOrder || normalizedOrder;
				const nextOrder = getMovedOrder( baseOrder, sourceId, targetId, placement );

				return areOrdersEqual( nextOrder, baseOrder ) ? currentPreviewOrder : nextOrder;
			} );
		}, 80 );
	};

	const handleMove = ( sourceId, targetId, placement = 'before' ) => {
		clearPreviewTimer();
		sourceId = sourceId || draggedItemRef.current;

		if ( ! sourceId || ! targetId || sourceId === targetId ) {
			return;
		}

		didDrop.current = true;

		const nextOrder = getMovedOrder(
			previewOrder || normalizedOrder,
			sourceId,
			targetId,
			placement
		);

		updateOrder( nextOrder );
		setDraggedItem( null );
		setPreviewOrder( null );
	};

	const handleDragEnd = () => {
		clearPreviewTimer();

		if (
			! didDrop.current &&
			previewOrder &&
			! areOrdersEqual( previewOrder, normalizedOrder )
		) {
			updateOrder( previewOrder );
		}

		didDrop.current = false;
		draggedItemRef.current = null;
		setDraggedItem( null );
		setPreviewOrder( null );
	};

	useEffect( () => () => clearPreviewTimer(), [] );

	return (
		<BaseControl
			className="ppcart-arrangement-control"
			__nextHasNoMarginBottom
		>
			{ isTwoStep && (
				<div className="ppcart-arrangement-step">
					<div className="ppcart-arrangement-step__label">
						{ __( 'Step 1', 'publishpress-cart' ) }
					</div>
					<SortableSectionList
						items={ stepOneItems }
						availability={ availability }
						draggedItem={ draggedItem }
						isSortable={ false }
						onDragStart={ handleDragStart }
						onDragEnd={ handleDragEnd }
						onPreviewMove={ () => {} }
						onMove={ () => {} }
					/>
					<div className="ppcart-arrangement-step__label">
						{ __( 'Step 2', 'publishpress-cart' ) }
					</div>
				</div>
			) }
			<SortableSectionList
				items={ sortableItems }
				availability={ availability }
				draggedItem={ draggedItem }
				onDragStart={ handleDragStart }
				onDragEnd={ handleDragEnd }
				onPreviewMove={ handlePreviewMove }
				onMove={ handleMove }
			/>
			<Button
				className="ppcart-arrangement-reset"
				variant="secondary"
				onClick={ () => onChange( [] ) }
				disabled={ ! value || ! value.length }
			>
				{ __( 'Reset arrangement', 'publishpress-cart' ) }
			</Button>
		</BaseControl>
	);
}

function handlePreviewClick( event ) {
	const target = event.target;

	if ( ! target || ! target.closest ) {
		return;
	}

	const step = target.closest( '.ppcart-checkout-form-steps .steps' );
	const nextButton = target.closest( '.ppcart-next-btn' );
	const submitButton = target.closest( 'button[type="submit"], input[type="submit"]' );

	if ( submitButton && ! nextButton ) {
		event.preventDefault();
		return;
	}

	if ( ! step && ! nextButton ) {
		return;
	}

	const wrapper = target.closest( '.ppcart-form-wrap' );

	if ( ! wrapper ) {
		return;
	}

	const form = wrapper.querySelector( '#ppcart-payment-form' );
	const steps = Array.from( wrapper.querySelectorAll( '.ppcart-checkout-form-steps .steps' ) );
	const nextStep = nextButton ? wrapper.querySelector( '.step-two' ) : step;

	if ( ! form || ! nextStep || nextStep.classList.contains( 'ppcart-current' ) ) {
		event.preventDefault();
		return;
	}

	event.preventDefault();

	const isStepTwo = nextStep.classList.contains( 'step-two' );

	form.classList.toggle( 'step-1', ! isStepTwo );
	form.classList.toggle( 'step-2', isStepTwo );

	steps.forEach( ( item ) => {
		item.classList.toggle(
			'ppcart-current',
			isStepTwo
				? item.classList.contains( 'step-two' )
				: item.classList.contains( 'step-one' )
		);
	} );
}

function CheckoutPreview( { attributes, previewProductId } ) {
	const [ preview, setPreview ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );
	const contentOrderKey = JSON.stringify(
		Array.isArray( attributes.contentOrder ) ? attributes.contentOrder : []
	);
	const textSettingsKey = JSON.stringify( attributes.textSettings || {} );

	useEffect( () => {
		let isMounted = true;
		setIsLoading( true );

		apiFetch( {
			path: `${ restBase }/checkout-block/preview?${ buildQuery( {
				pid: previewProductId || '',
				template: attributes.template || '',
				plan: attributes.plan || '',
				coupon: attributes.coupon || '',
				hide_labels: attributes.hide_labels ? '1' : '0',
				style_settings: JSON.stringify( attributes.styleSettings || {} ),
				content_order: contentOrderKey,
				text_settings: textSettingsKey,
			} ) }`,
		} )
			.then( ( response ) => {
				if ( isMounted ) {
					setPreview( response );
				}
			} )
			.catch( () => {
				if ( isMounted ) {
					setPreview( null );
				}
			} )
			.finally( () => {
				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [
		attributes.pid,
		previewProductId,
		attributes.template,
		attributes.plan,
		attributes.coupon,
		attributes.hide_labels,
		attributes.styleSettings,
		contentOrderKey,
		textSettingsKey,
	] );

	const productTitle = preview?.title || __( 'Dynamic product', 'publishpress-cart' );
	const skin = templateOptions.find( ( option ) => option.value === attributes.template );

	const hasRenderedPreview = preview?.html;

	if ( hasRenderedPreview ) {
		return (
			<div
				className="ppcart-checkout-preview__rendered ppcart-checkout-preview__rendered--standalone"
				onClick={ handlePreviewClick }
				onSubmit={ ( event ) => event.preventDefault() }
				dangerouslySetInnerHTML={ { __html: preview.html } }
			/>
		);
	}

	return (
		<div className="ppcart-checkout-preview">
			<div className="ppcart-checkout-preview__header">
				<div>
					<strong>{ blockTitle }</strong>
					<span>{ productTitle }</span>
				</div>
				{ isLoading && <Spinner /> }
			</div>
			<div className="ppcart-checkout-preview__meta">
				<span>{ skin?.label || __( 'Product default', 'publishpress-cart' ) }</span>
				{ attributes.hide_labels && <span>{ __( 'Labels hidden', 'publishpress-cart' ) }</span> }
				{ attributes.plan && <span>{ attributes.plan }</span> }
					{ attributes.coupon && <span>{ attributes.coupon }</span> }
				</div>
			<div className="ppcart-checkout-preview__empty">
				{ __(
					'Select a product to preview the checkout form.',
					'publishpress-cart'
				) }
			</div>
		</div>
	);
}

function Edit( props ) {
	const { attributes, setAttributes } = props;
	const blockProps = useBlockProps();
	const [ products, setProducts ] = useState( [] );
	const [ isLoadingProducts, setIsLoadingProducts ] = useState( true );
	const [ productError, setProductError ] = useState( false );
	const styleSettings = attributes.styleSettings || {};
	const textSettings = attributes.textSettings || {};
	const currentPost = useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		if ( ! editor ) {
			return { id: 0, type: '' };
		}

		const id = editor.getCurrentPostId
			? editor.getCurrentPostId()
			: editor.getEditedPostAttribute?.( 'id' );
		const type = editor.getCurrentPostType
			? editor.getCurrentPostType()
			: editor.getEditedPostAttribute?.( 'type' );

		return {
			id: id || 0,
			type: type || '',
		};
	}, [] );
	const selectedProductId = normalizeProductId( attributes.pid );
	const currentProductId =
		! selectedProductId && productPostTypes.includes( currentPost.type ) && currentPost.id
			? normalizeProductId( currentPost.id )
			: '';
	const previewProductId = selectedProductId || currentProductId;

	useEffect( () => {
		let isMounted = true;

		apiFetch( { path: `${ restBase }/checkout-block/products` } )
			.then( ( response ) => {
				if ( isMounted ) {
					setProducts( Array.isArray( response ) ? response : [] );
					setProductError( false );
				}
			} )
			.catch( () => {
				if ( isMounted ) {
					setProductError( true );
				}
			} )
			.finally( () => {
				if ( isMounted ) {
					setIsLoadingProducts( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [] );

	const selectedProduct = useMemo(
		() => products.find( ( product ) => String( product.id ) === String( previewProductId ) ),
		[ products, previewProductId ]
	);
	const effectiveTemplate =
		attributes.template || selectedProduct?.defaultTemplate || 'normal';
	const sectionAvailability = useMemo( () => {
		const availability = {
			payment_plan: false,
			coupon: false,
			contact_info: true,
			payment_method: false,
			payment_details: false,
			order_bumps: false,
			order_summary: true,
			terms_consent: false,
			express_payment: false,
			submit_button: true,
			...( selectedProduct?.sectionAvailability || {} ),
		};

		if ( effectiveTemplate === 'opt-in' ) {
			availability.coupon = false;
			availability.payment_method = false;
			availability.payment_details = false;
			availability.order_bumps = false;
			availability.express_payment = false;
		}

		if ( effectiveTemplate === 'split-in' ) {
			availability.coupon = false;
			availability.order_bumps = false;
			availability.order_summary = false;
		}

		return availability;
	}, [ selectedProduct, effectiveTemplate ] );

	const planOptions = useMemo( () => {
		const plans = selectedProduct?.plans || [];

		return [
			{ label: __( 'Product default', 'publishpress-cart' ), value: '' },
			...plans.map( ( plan ) => ( {
				label: plan.label,
				value: plan.value,
			} ) ),
		];
	}, [ selectedProduct ] );

	useEffect( () => {
		if ( ! attributes.plan || ! selectedProduct ) {
			return;
		}

		const selectedPlanExists = ( selectedProduct.plans || [] ).some(
			( plan ) => plan.value === attributes.plan
		);

		if ( ! selectedPlanExists ) {
			setAttributes( { plan: '' } );
		}
	}, [ attributes.plan, selectedProduct, setAttributes ] );

	const updateStyle = ( key, value ) => {
		const nextStyleSettings = { ...styleSettings };

		if ( value === '' ) {
			delete nextStyleSettings[ key ];
		} else {
			nextStyleSettings[ key ] = value;
		}

		setAttributes( { styleSettings: nextStyleSettings } );
	};

	const resetStyleSettings = ( keys ) => {
		const nextStyleSettings = { ...styleSettings };

		keys.forEach( ( key ) => {
			delete nextStyleSettings[ key ];
		} );

		setAttributes( { styleSettings: nextStyleSettings } );
	};

	const updateTextSetting = ( key, value ) => {
		const nextTextSettings = { ...textSettings };
		const nextValue = String( value ?? '' );

		nextTextSettings[ key ] = nextValue;

		setAttributes( { textSettings: nextTextSettings } );
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Product Settings', 'publishpress-cart' ) } initialOpen>
					{ productError && (
						<Notice status="warning" isDismissible={ false }>
							{ __( 'Products could not be loaded.', 'publishpress-cart' ) }
						</Notice>
					) }
					<PanelRow>
						<SelectControl
							label={ __( 'Product', 'publishpress-cart' ) }
							value={ attributes.pid || '' }
							options={ getProductOptions( products, isLoadingProducts ) }
							onChange={ ( value ) =>
								setAttributes( { pid: value, plan: '' } )
							}
							__nextHasNoMarginBottom
						/>
					</PanelRow>
					{ planOptions.length > 1 && (
						<PanelRow>
							<SelectControl
								label={ __( 'Payment plan', 'publishpress-cart' ) }
								value={ attributes.plan || '' }
								options={ planOptions }
								onChange={ ( value ) => setAttributes( { plan: value } ) }
								__nextHasNoMarginBottom
							/>
						</PanelRow>
					) }
					<PanelRow className="ppcart-checkout-toggle-row">
						<ToggleControl
							label={ __( 'Hide Labels', 'publishpress-cart' ) }
							checked={ !! attributes.hide_labels }
							onChange={ ( value ) => setAttributes( { hide_labels: value } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>
					<TextControl
						label={ __( 'Coupon Code', 'publishpress-cart' ) }
						value={ attributes.coupon || '' }
						onChange={ ( value ) => setAttributes( { coupon: value } ) }
						__nextHasNoMarginBottom
					/>
					</PanelBody>

				<PanelBody title={ __( 'Content Arrangement', 'publishpress-cart' ) } initialOpen={ false }>
					<ContentArrangementControl
						value={ attributes.contentOrder || [] }
						template={ effectiveTemplate }
						availability={ sectionAvailability }
						onChange={ ( value ) => setAttributes( { contentOrder: value } ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Text', 'publishpress-cart' ) } initialOpen={ false }>
					<TextSettingsControl
						value={ textSettings }
						template={ effectiveTemplate }
						onChange={ updateTextSetting }
						onReset={ () => setAttributes( { textSettings: {} } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<InspectorControls group="styles">
				<PanelBody title={ __( 'Design', 'publishpress-cart' ) } initialOpen>
					<SelectControl
						label={ __( 'Form skin', 'publishpress-cart' ) }
						value={ attributes.template || '' }
						options={ templateOptions }
						onChange={ ( value ) => setAttributes( { template: value } ) }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Style preset', 'publishpress-cart' ) }
						value={ styleSettings.preset || '' }
						options={ stylePresetOptions }
						onChange={ ( value ) => updateStyle( 'preset', value ) }
						__nextHasNoMarginBottom
					/>
					<ColorSetting
						label={ __( 'Accent color', 'publishpress-cart' ) }
						value={ styleSettings.accentColor }
						onChange={ ( value ) => updateStyle( 'accentColor', value ) }
					/>
					<SegmentedControl
						label={ __( 'Surface', 'publishpress-cart' ) }
						value={ styleSettings.surfaceStyle }
						options={ [
							{ label: __( 'Default', 'publishpress-cart' ), value: '' },
							{ label: __( 'Card', 'publishpress-cart' ), value: 'card' },
							{ label: __( 'Boxed', 'publishpress-cart' ), value: 'boxed' },
						] }
						onChange={ ( value ) => updateStyle( 'surfaceStyle', value ) }
					/>
					<SegmentedControl
						label={ __( 'Density', 'publishpress-cart' ) }
						value={ styleSettings.density }
						options={ [
							{ label: __( 'Default', 'publishpress-cart' ), value: '' },
							{ label: __( 'Compact', 'publishpress-cart' ), value: 'compact' },
							{ label: __( 'Spacious', 'publishpress-cart' ), value: 'spacious' },
						] }
						onChange={ ( value ) => updateStyle( 'density', value ) }
					/>
					<SegmentedControl
						label={ __( 'Corners', 'publishpress-cart' ) }
						value={ styleSettings.cornerRadius }
						options={ [
							{ label: __( 'Default', 'publishpress-cart' ), value: '' },
							{ label: __( 'Square', 'publishpress-cart' ), value: 'square' },
							{ label: __( 'Rounded', 'publishpress-cart' ), value: 'rounded' },
						] }
						onChange={ ( value ) => updateStyle( 'cornerRadius', value ) }
					/>
					<Button
						variant="secondary"
						onClick={ () => resetStyleSettings( designStyleKeys ) }
					>
						{ __( 'Reset design', 'publishpress-cart' ) }
					</Button>
				</PanelBody>
			</InspectorControls>

			<CheckoutPreview attributes={ attributes } previewProductId={ previewProductId } />
		</div>
	);
}

const settings = {
	title: blockTitle,
	description: __( 'Display a checkout form.', 'publishpress-cart' ),
	category: 'widgets',
	icon: 'cart',
	attributes: {
		pid: {
			type: 'string',
			default: '',
		},
		hide_labels: {
			type: 'boolean',
			default: false,
		},
		template: {
			type: 'string',
			default: '',
		},
		plan: {
			type: 'string',
			default: '',
		},
		coupon: {
			type: 'string',
			default: '',
		},
		styleSettings: {
			type: 'object',
			default: {},
		},
		contentOrder: {
			type: 'array',
			default: [],
		},
		textSettings: {
			type: 'object',
			default: {},
		},
		anchor: {
			type: 'string',
			default: '',
		},
	},
	supports: {
		align: [ 'wide', 'full' ],
		anchor: true,
		html: false,
		spacing: {
			margin: true,
		},
	},
	edit: Edit,
	save: () => null,
};

registerBlockType( blockName, settings );
