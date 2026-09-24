const { test, expect } = require( '@playwright/test' );
const fs = require( 'fs/promises' );
const path = require( 'path' );

const baseURL = ( process.env.WP_BASE_URL || '' ).replace( /\/$/, '' );
const adminUser = process.env.WP_ADMIN_USER;
const adminPassword = process.env.WP_ADMIN_PASSWORD;
const productId = process.env.PPCART_E2E_PRODUCT_ID;
const pageId = process.env.PPCART_E2E_PAGE_ID;
const pageURL = process.env.PPCART_E2E_PAGE_URL || `${ baseURL }/?page_id=${ pageId }`;
const blockName = 'publishpress-cart/checkout-form';
const defaultPlan = 'e2e_plan_200';
const captureSuccessScreenshots = /^(1|true|yes|on)$/i.test( process.env.PPCART_E2E_SCREENSHOTS || '' );
let screenshotsPrepared = false;

const arrangementLabelsById = {
	payment_plan: 'Payment Plan',
	coupon: 'Coupon',
	contact_info: 'Contact Info',
	payment_method: 'Payment Method',
	payment_details: 'Payment Details',
	order_bumps: 'Order Bumps',
	order_summary: 'Order Summary',
	terms_consent: 'Terms & Consent',
	express_payment: 'Express Payment',
	submit_button: 'Submit Button',
};

if ( ! baseURL || ! adminUser || ! adminPassword || ! productId || ! pageId ) {
	throw new Error(
		'Missing required E2E environment. Use tests/legacy/e2e/checkout-block/run.sh to create fixtures, or set WP_BASE_URL, WP_ADMIN_USER, WP_ADMIN_PASSWORD, PPCART_E2E_PRODUCT_ID, and PPCART_E2E_PAGE_ID.'
	);
}

test.describe.configure( { mode: 'serial' } );

function getScreenshotDir( testInfo ) {
	if ( process.env.PPCART_E2E_SCREENSHOT_DIR ) {
		return path.resolve( process.env.PPCART_E2E_SCREENSHOT_DIR );
	}

	return testInfo.outputPath( 'screenshots' );
}

function sanitizeScreenshotName( name ) {
	return name
		.replace( /[^a-z0-9._-]+/gi, '-' )
		.replace( /^-+|-+$/g, '' )
		.toLowerCase();
}

async function maybeCaptureScreenshot( page, testInfo, name, options = {} ) {
	if ( ! captureSuccessScreenshots ) {
		return;
	}

	const screenshotDir = getScreenshotDir( testInfo );
	if ( ! screenshotsPrepared ) {
		await fs.rm( screenshotDir, { recursive: true, force: true } );
		await fs.mkdir( screenshotDir, { recursive: true } );
		screenshotsPrepared = true;
	}

	const filePath = path.join( screenshotDir, `${ sanitizeScreenshotName( name ) }.png` );
	await page.screenshot( {
		path: filePath,
		fullPage: !! options.fullPage,
	} );
	await testInfo.attach( name, {
		path: filePath,
		contentType: 'image/png',
	} );
}

async function login( page ) {
	await page.context().addCookies( [
		{
			name: 'wordpress_test_cookie',
			value: 'WP Cookie check',
			url: baseURL,
		},
	] );

	const response = await page.request.post( `${ baseURL }/wp-login.php`, {
		form: {
			log: adminUser,
			pwd: adminPassword,
			'wp-submit': 'Log In',
			redirect_to: `${ baseURL }/wp-admin/`,
			testcookie: '1',
		},
		maxRedirects: 0,
	} );

	if ( ! [ 302, 303 ].includes( response.status() ) ) {
		const body = await response.text().catch( () => '' );
		throw new Error( `WordPress login failed with HTTP ${ response.status() }: ${ body.replace( /<[^>]+>/g, ' ' ).replace( /\\s+/g, ' ' ).trim() }` );
	}

	await page.goto( `${ baseURL }/wp-admin/`, { waitUntil: 'domcontentloaded' } );
	await expect( page.locator( '#wpadminbar' ) ).toBeVisible( { timeout: 30000 } );
}

async function dismissWelcomeGuide( page ) {
	await page.evaluate( () => {
		const data = window.wp && window.wp.data;

		if ( ! data ) {
			return;
		}

		const preferences = data.dispatch( 'core/preferences' );
		if ( preferences && preferences.set ) {
			preferences.set( 'core/edit-post', 'welcomeGuide', false );
			preferences.set( 'core', 'welcomeGuide', false );
			preferences.set( 'core', 'enableChoosePatternModal', false );
		}

		const interfaceStore = data.dispatch( 'core/interface' );
		if ( interfaceStore && interfaceStore.setFeatureValue ) {
			interfaceStore.setFeatureValue( 'core/edit-post', 'welcomeGuide', false );
			interfaceStore.setFeatureValue( 'core', 'welcomeGuide', false );
		}
		if ( interfaceStore && interfaceStore.closeModal ) {
			interfaceStore.closeModal();
		}

		const editPost = data.dispatch( 'core/edit-post' );
		const editPostSelect = data.select( 'core/edit-post' );
		if (
			editPost &&
			editPost.toggleFeature &&
			editPostSelect &&
			editPostSelect.isFeatureActive &&
			editPostSelect.isFeatureActive( 'welcomeGuide' )
		) {
			editPost.toggleFeature( 'welcomeGuide' );
		}
	} ).catch( () => {} );

	const welcomeDialog = page.getByRole( 'dialog' ).filter( { hasText: 'Welcome to the editor' } ).first();
	if ( ! await welcomeDialog.isVisible().catch( () => false ) ) {
		return;
	}

	const closeButton = welcomeDialog.getByRole( 'button', { name: /Close|Skip|Got it/i } ).first();
	if ( await closeButton.count().catch( () => 0 ) ) {
		await closeButton.click().catch( async () => {
			await page.keyboard.press( 'Escape' ).catch( () => {} );
		} );
	} else {
		await page.keyboard.press( 'Escape' ).catch( () => {} );
	}

	await expect( welcomeDialog ).toBeHidden( { timeout: 5000 } ).catch( () => {} );
}

async function dismissChoosePatternModal( page ) {
	const patternDialog = page.getByRole( 'dialog' ).filter( { hasText: 'Choose a pattern' } ).first();
	if ( ! await patternDialog.isVisible().catch( () => false ) ) {
		return;
	}

	const starterPatternCheckbox = patternDialog.getByRole(
		'checkbox',
		{ name: /Always show starter patterns for new pages/i }
	).first();
	if ( await starterPatternCheckbox.isChecked().catch( () => false ) ) {
		await starterPatternCheckbox.uncheck().catch( () => {} );
	}

	const closeButton = patternDialog.getByRole( 'button', { name: /Close|Cancel/i } ).first();
	if ( await closeButton.count().catch( () => 0 ) ) {
		await closeButton.click().catch( async () => {
			await page.keyboard.press( 'Escape' ).catch( () => {} );
		} );
	} else {
		await page.keyboard.press( 'Escape' ).catch( () => {} );
	}

	await expect( patternDialog ).toBeHidden( { timeout: 5000 } ).catch( () => {} );

	await page.evaluate( () => {
		const preferences = window.wp && window.wp.data && window.wp.data.dispatch( 'core/preferences' );
		if ( preferences && preferences.set ) {
			preferences.set( 'core', 'enableChoosePatternModal', false );
		}
	} ).catch( () => {} );
}

async function openEditor( page ) {
	await page.goto( `${ baseURL }/wp-admin/post.php?post=${ pageId }&action=edit`, { waitUntil: 'domcontentloaded' } );
	await page.waitForFunction( () => {
		return !! (
			window.wp &&
			window.wp.blocks &&
			window.wp.data &&
			window.wp.data.select( 'core/block-editor' ) &&
			window.wp.data.dispatch( 'core/block-editor' )
		);
	} );
	await dismissWelcomeGuide( page );
	await dismissChoosePatternModal( page );
	await page.keyboard.press( 'Escape' ).catch( () => {} );
}

async function openBlockSidebar( page ) {
	await page.evaluate( () => {
		const block = window.wp.data
			.select( 'core/block-editor' )
			.getBlocks()
			.find( ( item ) => item.name === 'publishpress-cart/checkout-form' );

		if ( block ) {
			window.wp.data.dispatch( 'core/block-editor' ).selectBlock( block.clientId );
		}

		const editPost = window.wp.data.dispatch( 'core/edit-post' );
		if ( editPost && editPost.openGeneralSidebar ) {
			editPost.openGeneralSidebar( 'edit-post/block' );
		}
	} );

	await expect( page.locator( '.interface-interface-skeleton__sidebar' ).first() ).toBeVisible();
}

async function openSidebarPanel( page, panelName ) {
	const sidebar = page.locator( '.interface-interface-skeleton__sidebar' ).first();
	const panelButton = sidebar.getByRole( 'button', { name: panelName, exact: true } );

	await expect( panelButton ).toBeVisible();

	if ( await panelButton.getAttribute( 'aria-expanded' ) !== 'true' ) {
		await panelButton.click();
	}
}

async function openSidebarTab( page, names ) {
	const sidebar = page.locator( '.interface-interface-skeleton__sidebar' ).first();
	const labels = Array.isArray( names ) ? names : [ names ];

	for ( const label of labels ) {
		for ( const role of [ 'tab', 'button' ] ) {
			const control = sidebar.getByRole( role, { name: label, exact: true } ).first();
			if ( await control.isVisible().catch( () => false ) ) {
				await control.click();
				return;
			}
		}
	}

	throw new Error( `Could not find sidebar tab: ${ labels.join( ', ' ) }` );
}

function getArrangementItem( page, label ) {
	return page
		.locator( '.interface-interface-skeleton__sidebar .ppcart-arrangement-item' )
		.filter( {
			has: page.getByText( label, { exact: true } ),
		} )
		.first();
}

async function getArrangementLabels( page ) {
	return page
		.locator( '.interface-interface-skeleton__sidebar .ppcart-arrangement-item__label' )
		.evaluateAll( ( labels ) => labels.map( ( label ) => label.textContent.trim() ) );
}

async function getCheckoutBlockAttributes( page ) {
	return page.evaluate( () => {
		const block = window.wp.data
			.select( 'core/block-editor' )
			.getBlocks()
			.find( ( item ) => item.name === 'publishpress-cart/checkout-form' );

		return block ? block.attributes : {};
	} );
}

function getArrangementLabelsFromOrder( order ) {
	return order.map( ( sectionId ) => arrangementLabelsById[ sectionId ] || sectionId );
}

async function hoverArrangementItemOverTarget( page, sourceLabel, targetLabel, placement = 'before' ) {
	const source = getArrangementItem( page, sourceLabel );
	const target = getArrangementItem( page, targetLabel );

	await expect( source ).toBeVisible();
	await expect( target ).toBeVisible();
	await source.scrollIntoViewIfNeeded();
	await target.scrollIntoViewIfNeeded();

	const sourceBox = await source.boundingBox();
	const targetBox = await target.boundingBox();

	expect( sourceBox ).not.toBeNull();
	expect( targetBox ).not.toBeNull();

	const sourceX = sourceBox.x + 16;
	const sourceY = sourceBox.y + sourceBox.height / 2;
	const targetX = targetBox.x + targetBox.width / 2;
	const targetY = targetBox.y + targetBox.height * ( placement === 'after' ? 0.75 : 0.25 );
	const dragDirection = targetY >= sourceY ? 1 : -1;

	await page.mouse.move( sourceX, sourceY );
	await page.mouse.down();
	await page.mouse.move( sourceX, sourceY + ( dragDirection * 24 ), { steps: 6 } );
	await page.waitForTimeout( 160 );
	await page.mouse.move( targetX, targetY, { steps: 16 } );
}

async function dragArrangementItemAndExpectOrder( page, sourceLabel, targetLabel, placement, expectedOrder ) {
	await hoverArrangementItemOverTarget( page, sourceLabel, targetLabel, placement );

	const expectedLabels = getArrangementLabelsFromOrder( expectedOrder );

	await expect.poll( async () => getArrangementLabels( page ) ).toEqual( expectedLabels );

	await page.mouse.up();

	await expect.poll( async () => {
		const attributes = await getCheckoutBlockAttributes( page );

		return attributes.contentOrder || [];
	} ).toEqual( expectedOrder );

	await expect.poll( async () => getArrangementLabels( page ) ).toEqual( expectedLabels );
	await page.waitForTimeout( 150 );
}

async function waitForEditorPreview( page ) {
	return waitForAnyFrameLocator( page, '#ppcart-form-container' );
}

async function expectEditorSelectedPlan( page, plan ) {
	if ( ! plan ) {
		return;
	}

	await waitForAnyFrameLocator(
		page,
		`input[name="ppcart_product_option"][value="${ plan }"]:checked`,
		{ visible: false }
	);
}

async function waitForAnyFrameLocator( page, selector, options = {} ) {
	const timeout = options.timeout || 30000;
	const deadline = Date.now() + timeout;
	let lastError;

	while ( Date.now() < deadline ) {
		for ( const frame of page.frames() ) {
			const locator = frame.locator( selector ).first();

			try {
				if ( await locator.count() ) {
					if ( options.visible === false || await locator.isVisible() ) {
						return locator;
					}
				}
			} catch ( error ) {
				lastError = error;
			}
		}

		await page.waitForTimeout( 250 );
	}

	throw lastError || new Error( `Unable to find selector in editor frames: ${ selector }` );
}

async function waitForEditorCssContaining( page, expectedText ) {
	const timeout = 30000;
	const deadline = Date.now() + timeout;

	while ( Date.now() < deadline ) {
		for ( const frame of page.frames() ) {
			const styles = frame.locator( '.ppcart-checkout-preview__rendered style, .wp-block-publishpress-cart-checkout-form style' );
			const count = await styles.count().catch( () => 0 );

			for ( let index = 0; index < count; index++ ) {
				const text = await styles.nth( index ).textContent().catch( () => '' );

				if ( text && text.includes( expectedText ) ) {
					return text;
				}
			}
		}

		await page.waitForTimeout( 250 );
	}

	throw new Error( `Unable to find editor CSS containing: ${ expectedText }` );
}

async function waitForEditorCssContainingAll( page, expectedTexts ) {
	const timeout = 30000;
	const deadline = Date.now() + timeout;

	while ( Date.now() < deadline ) {
		let combinedCss = '';

		for ( const frame of page.frames() ) {
			const styles = frame.locator( '.ppcart-checkout-preview__rendered style, .wp-block-publishpress-cart-checkout-form style' );
			const count = await styles.count().catch( () => 0 );

			for ( let index = 0; index < count; index++ ) {
				combinedCss += await styles.nth( index ).textContent().catch( () => '' );
			}
		}

		if ( expectedTexts.every( ( expected ) => combinedCss.includes( expected ) ) ) {
			return combinedCss;
		}

		await page.waitForTimeout( 250 );
	}

	throw new Error( `Unable to find editor CSS containing all expected rules: ${ expectedTexts.join( ', ' ) }` );
}

async function setCheckoutBlockAttributes( page, attributes ) {
	await page.evaluate( ( nextAttributes ) => {
		const editor = window.wp.data.dispatch( 'core/block-editor' );
		const currentBlocks = window.wp.data.select( 'core/block-editor' ).getBlocks();
		const currentBlock = currentBlocks.find( ( block ) => block.name === 'publishpress-cart/checkout-form' );

		if ( currentBlock ) {
			editor.updateBlockAttributes( currentBlock.clientId, nextAttributes );
			editor.selectBlock( currentBlock.clientId );
			return;
		}

		const block = window.wp.blocks.createBlock( 'publishpress-cart/checkout-form', nextAttributes );
		editor.resetBlocks( [ block ] );
		const insertedBlock = window.wp.data
			.select( 'core/block-editor' )
			.getBlocks()
			.find( ( item ) => item.name === 'publishpress-cart/checkout-form' );

		if ( insertedBlock ) {
			editor.selectBlock( insertedBlock.clientId );
		}
	}, attributes );

	await dismissChoosePatternModal( page );
	await waitForEditorPreview( page );
}

async function savePost( page ) {
	await page.evaluate( async () => {
		await window.wp.data.dispatch( 'core/editor' ).savePost();
	} );

	await page.waitForFunction( () => {
		const editor = window.wp.data.select( 'core/editor' );

		return ! editor.isSavingPost() && ! editor.isAutosavingPost();
	} );
}

async function expectSavedContentContains( page, expectedAttributes ) {
	const content = await page.evaluate( () => window.wp.data.select( 'core/editor' ).getEditedPostContent() );

	for ( const expected of expectedAttributes ) {
		expect( content ).toContain( expected );
	}
}

async function assertFrontendScenario( page, scenario ) {
	await page.goto( pageURL );

	const wrapper = page.locator( '.publishpress-cart-checkout-form' ).first();
	await expect( wrapper ).toBeVisible();
	await expect( page.locator( '#ppcart-form-container' ) ).toBeVisible();

	if ( scenario.anchor ) {
		await expect( wrapper ).toHaveAttribute( 'id', scenario.anchor );
	}

	const css = await wrapper.locator( 'style' ).first().textContent();
	for ( const expected of scenario.cssIncludes ) {
		expect( css ).toContain( expected );
	}

	if ( scenario.expectedPlan ) {
		await expect(
			page.locator( `input[name="ppcart_product_option"][value="${ scenario.expectedPlan }"]` )
		).toBeChecked();
	}
}

async function expectFrontendTotalPlacement( page ) {
	const total = page.locator( '.publishpress-cart-checkout-form .ppcart .total' ).first();
	const price = total.locator( '.total-rhs .price' ).first();

	await expect( total ).toBeVisible();
	await expect( price ).toHaveText( /\S/, { timeout: 30000 } );

	await expect( total ).toHaveCSS( 'display', 'flex' );
	await expect( price ).toHaveCSS( 'float', 'none' );

	const totalBox = await total.boundingBox();
	const priceBox = await price.boundingBox();

	expect( totalBox ).not.toBeNull();
	expect( priceBox ).not.toBeNull();
	expect( priceBox.y ).toBeGreaterThanOrEqual( totalBox.y - 1 );
	expect( priceBox.y + priceBox.height ).toBeLessThanOrEqual( totalBox.y + totalBox.height + 1 );
}

function scenarioAttributes( scenario ) {
	return {
		pid: String( productId ),
		template: scenario.template || 'normal',
		plan: scenario.plan === false ? '' : ( scenario.plan || defaultPlan ),
		coupon: scenario.coupon === false ? '' : 'SAVE10',
		hide_labels: !! scenario.hideLabels,
		anchor: scenario.anchor,
		styleSettings: scenario.styleSettings,
	};
}

const customizationScenarios = [
	{
		name: 'checkout-design',
		anchor: 'ppcart-e2e-design',
		template: '2-step',
		hideLabels: true,
		styleSettings: {
			preset: 'carded',
			accentColor: '#2271b1',
			surfaceStyle: 'card',
			density: 'spacious',
			cornerRadius: 'rounded',
		},
		cssIncludes: [
			'box-shadow: 0 16px 40px rgba(17, 24, 39, 0.08)',
			'padding: 32px',
			'border-radius: 10px',
			'border-color: #2271b1',
		],
		savedAttributes: [
			'"template":"2-step"',
			'"plan":"e2e_plan_200"',
			'"hide_labels":true',
			'"anchor":"ppcart-e2e-design"',
		],
	},
];

test.beforeEach( async ( { page } ) => {
	await login( page );
	await openEditor( page );
} );

test( 'exposes the expected checkout customization panels in the editor', async ( { page }, testInfo ) => {
	await setCheckoutBlockAttributes( page, scenarioAttributes( customizationScenarios[0] ) );
	await openBlockSidebar( page );
	const sidebar = page.locator( '.interface-interface-skeleton__sidebar' ).first();
	await openSidebarTab( page, [ 'Settings', 'Block settings' ] );

	const panels = [
		[ 'Product Settings', 'Product' ],
		[ 'Content Arrangement', 'Payment Plan' ],
		[ 'Text', 'Step 1 heading' ],
	];

	for ( const [ panel, field ] of panels ) {
		const panelButton = sidebar.getByRole( 'button', { name: panel, exact: true } );
		const fieldText = sidebar.getByText( field, { exact: true } );

		if ( await panelButton.getAttribute( 'aria-expanded' ) !== 'true' ) {
			await panelButton.click();
		}

		await expect( fieldText.first() ).toBeVisible();
	}

	await expect( sidebar.getByRole( 'button', { name: 'Fields', exact: true } ) ).toHaveCount( 0 );
	await expect( sidebar.getByRole( 'button', { name: 'Steps & Payment Options', exact: true } ) ).toHaveCount( 0 );
	await expect( sidebar.getByRole( 'button', { name: 'Button, Coupon & Summary', exact: true } ) ).toHaveCount( 0 );
	await expect( sidebar.getByRole( 'button', { name: 'Design', exact: true } ) ).toHaveCount( 0 );

	await openSidebarTab( page, [ 'Styles', 'Style' ] );
	await openSidebarPanel( page, 'Design' );
	await expect( sidebar.getByText( 'Form skin', { exact: true } ).first() ).toBeVisible();
	await expect( sidebar.getByText( 'Style preset', { exact: true } ).first() ).toBeVisible();
	await maybeCaptureScreenshot( page, testInfo, 'editor-customization-panels' );
} );

test( 'applies text customization in the editor and frontend', async ( { page }, testInfo ) => {
	await setCheckoutBlockAttributes( page, {
		...scenarioAttributes( {
			template: 'normal',
			coupon: false,
		} ),
		textSettings: {
			contactInfoHeading: 'Buyer Details',
			paymentPlanHeading: 'Choose Your Plan',
			paymentInfoHeading: 'Billing Details',
			orderTotalHeading: 'Checkout Total',
			amountDueLabel: 'Pay Now',
		},
	} );

	await waitForAnyFrameLocator( page, 'text=Buyer Details' );
	await waitForAnyFrameLocator( page, 'text=Choose Your Plan' );
	await waitForAnyFrameLocator( page, 'text=Billing Details' );
	await maybeCaptureScreenshot( page, testInfo, 'editor-text-customization' );
	await savePost( page );
	await page.goto( pageURL );

	for ( const expectedText of [
		'Buyer Details',
		'Choose Your Plan',
		'Billing Details',
		'Checkout Total',
	] ) {
		await expect( page.getByText( expectedText, { exact: true } ).first() ).toBeVisible();
	}

	await expect(
		page.locator( '.publishpress-cart-checkout-form .ppcart .total' ).first()
	).toContainText( 'Pay Now' );

	await maybeCaptureScreenshot( page, testInfo, 'frontend-text-customization', { fullPage: true } );
	await openEditor( page );

	await setCheckoutBlockAttributes( page, {
		...scenarioAttributes( {
			template: 'split-in',
			coupon: false,
		} ),
		textSettings: {
			splitSiteHeading: 'Checkout Brand',
			splitFormHeading: 'Finish Order',
		},
	} );

	await waitForAnyFrameLocator( page, 'text=Checkout Brand' );
	await waitForAnyFrameLocator( page, 'text=Finish Order' );
	await savePost( page );
	await page.goto( pageURL );
	await expect( page.getByText( 'Checkout Brand', { exact: true } ).first() ).toBeVisible();
	await expect( page.getByText( 'Finish Order', { exact: true } ).first() ).toBeVisible();
	await openEditor( page );
} );

test( 'persists content arrangement after repeated live drag preview reorders', async ( { page }, testInfo ) => {
	await setCheckoutBlockAttributes( page, {
		...scenarioAttributes( {
			template: 'split-in',
			coupon: false,
		} ),
		contentOrder: [],
	} );
	await openBlockSidebar( page );
	await openSidebarPanel( page, 'Content Arrangement' );

	await expect( getArrangementItem( page, 'Coupon' ).getByText( 'Unavailable', { exact: true } ) ).toBeVisible();

	await dragArrangementItemAndExpectOrder( page, 'Payment Plan', 'Coupon', 'after', [
		'coupon',
		'payment_plan',
		'contact_info',
		'payment_method',
		'payment_details',
		'order_bumps',
		'order_summary',
		'terms_consent',
		'express_payment',
		'submit_button',
	] );

	await dragArrangementItemAndExpectOrder( page, 'Contact Info', 'Coupon', 'before', [
		'contact_info',
		'coupon',
		'payment_plan',
		'payment_method',
		'payment_details',
		'order_bumps',
		'order_summary',
		'terms_consent',
		'express_payment',
		'submit_button',
	] );

	await dragArrangementItemAndExpectOrder( page, 'Coupon', 'Payment Method', 'after', [
		'contact_info',
		'payment_plan',
		'payment_method',
		'coupon',
		'payment_details',
		'order_bumps',
		'order_summary',
		'terms_consent',
		'express_payment',
		'submit_button',
	] );

	await maybeCaptureScreenshot( page, testInfo, 'editor-content-arrangement-repeated-drag-preview' );
} );

test( 'applies design customization in the editor and frontend', async ( { page }, testInfo ) => {
	for ( const scenario of customizationScenarios ) {
		const attributes = scenarioAttributes( scenario );
		await setCheckoutBlockAttributes( page, attributes );
		const editorCss = await waitForEditorCssContainingAll( page, scenario.cssIncludes );

		for ( const expected of scenario.cssIncludes ) {
			expect( editorCss ).toContain( expected );
		}

		const scenarioWithExpectedPlan = {
			...scenario,
			expectedPlan: attributes.plan,
		};

		await expectEditorSelectedPlan( page, scenarioWithExpectedPlan.expectedPlan );
		await expectSavedContentContains( page, scenario.savedAttributes );
		await maybeCaptureScreenshot( page, testInfo, `editor-${ scenario.name }` );
		await savePost( page );
		await assertFrontendScenario( page, scenarioWithExpectedPlan );
		await maybeCaptureScreenshot( page, testInfo, `frontend-${ scenario.name }`, { fullPage: true } );
		await openEditor( page );
	}
} );

test( 'renders supported skins in the editor and frontend', async ( { page }, testInfo ) => {
	const skinScenarios = [
		{ template: 'normal', frontendSelector: '#ppcart-form-container:not(.ppcart-splitin-form)' },
		{ template: '2-step', frontendSelector: '.ppcart-checkout-form-steps' },
		{ template: 'opt-in', frontendSelector: '#ppcart-form-container' },
		{ template: 'split-in', frontendSelector: '#ppcart-form-container.ppcart-splitin-form' },
	];

	for ( const skin of skinScenarios ) {
		const scenario = {
			anchor: `ppcart-e2e-${ skin.template.replace( /[^a-z0-9_-]/gi, '-' ) }`,
			template: skin.template,
			coupon: false,
			styleSettings: {
				preset: 'minimal',
				accentColor: '#111827',
			},
		};

		await setCheckoutBlockAttributes( page, scenarioAttributes( scenario ) );
		await waitForEditorCssContaining( page, '#111827' );
		await maybeCaptureScreenshot( page, testInfo, `editor-skin-${ skin.template }` );
		await savePost( page );
		await page.goto( pageURL );
		await expect( page.locator( '.publishpress-cart-checkout-form' ) ).toBeVisible();
		await expect( page.locator( skin.frontendSelector ).first() ).toBeVisible();
		if ( skin.template === 'normal' ) {
			await expectFrontendTotalPlacement( page );
		}
		await maybeCaptureScreenshot( page, testInfo, `frontend-skin-${ skin.template }`, { fullPage: true } );
		await openEditor( page );
	}
} );
