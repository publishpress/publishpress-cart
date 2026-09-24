( function () {
	'use strict';

	const config = window.publishpressCartAccountView || {};
	const detailPresentationClasses = {
		'ppcart-account-detail-presentation-slide-right': 'slide-right',
		'ppcart-account-detail-presentation-slide-left': 'slide-left',
		'ppcart-account-detail-presentation-slide-down': 'slide-down',
		'ppcart-account-detail-presentation-popup': '',
	};
	const detailQueryKeys = [ 'ppcart-order', 'ppcart-plan', 'ppcart-manage', 'action' ];
	const animatedClass = 'ppcart-account-detail-presenter--animated';
	const openClass = 'ppcart-account-detail-presenter--open';
	const closingClass = 'ppcart-account-detail-presenter--closing';
	const closeFallbackDelay = 280;

	if ( ! config.restUrl || ! window.fetch || ! window.URL ) {
		return;
	}

	function getLinkUrl( link ) {
		try {
			return new URL( link.getAttribute( 'href' ), window.location.href );
		} catch ( error ) {
			return null;
		}
	}

	function getReturnUrl() {
		const url = new URL( window.location.href );

		detailQueryKeys.forEach( ( key ) => url.searchParams.delete( key ) );

		return url.toString();
	}

	function getPresentation( context ) {
		const classNames = Object.keys( detailPresentationClasses );

		for ( let index = 0; index < classNames.length; index += 1 ) {
			if ( context.classList.contains( classNames[ index ] ) ) {
				return detailPresentationClasses[ classNames[ index ] ];
			}
		}

		return '';
	}

	function getDetailRequest( link ) {
		const url = getLinkUrl( link );

		if ( ! url || url.origin !== window.location.origin ) {
			return null;
		}

		const orderContext = link.closest( '.publishpress-cart-account-orders' );

		if ( orderContext && url.searchParams.get( 'ppcart-order' ) ) {
			return {
				context: orderContext,
				fallbackUrl: url.toString(),
				id: url.searchParams.get( 'ppcart-order' ),
				presentation: getPresentation( orderContext ),
				type: 'order',
			};
		}

		const subscriptionContext = link.closest(
			'.publishpress-cart-account-subscriptions'
		);

		if ( ! subscriptionContext || ! url.searchParams.get( 'ppcart-plan' ) ) {
			return null;
		}

		if ( url.searchParams.get( 'ppcart-manage' ) || url.searchParams.get( 'action' ) ) {
			return null;
		}

		return {
			context: subscriptionContext,
			fallbackUrl: url.toString(),
			id: url.searchParams.get( 'ppcart-plan' ),
			presentation: getPresentation( subscriptionContext ),
			type: 'subscription',
		};
	}

	function buildRequestUrl( detailRequest ) {
		const url = new URL( config.restUrl );

		url.searchParams.set( 'type', detailRequest.type );
		url.searchParams.set( 'id', detailRequest.id );
		url.searchParams.set( 'presentation', detailRequest.presentation );
		url.searchParams.set( 'returnUrl', getReturnUrl() );

		return url.toString();
	}

	function removeExistingPresenters( context ) {
		const scope = context.closest( '.ppcart-my-account' ) || context.parentElement;

		if ( ! scope ) {
			return;
		}

		scope
			.querySelectorAll( '.ppcart-account-detail-presenter' )
			.forEach( ( presenter ) => presenter.remove() );
	}

	function openPresenter( presenter ) {
		presenter.classList.add( animatedClass );

		window.requestAnimationFrame( () => {
			presenter.classList.add( openClass );
		} );
	}

	function removePresenter( presenter ) {
		if ( ! presenter || presenter.classList.contains( closingClass ) ) {
			return;
		}

		if ( ! presenter.classList.contains( animatedClass ) ) {
			presenter.remove();
			return;
		}

		let removed = false;
		const finish = () => {
			if ( removed ) {
				return;
			}

			removed = true;
			presenter.remove();
		};

		presenter.classList.remove( openClass );
		presenter.classList.add( closingClass );
		window.setTimeout( finish, closeFallbackDelay );
	}

	function showDetail( detailRequest, html ) {
		const template = document.createElement( 'template' );

		template.innerHTML = String( html || '' ).trim();

		const presenter = template.content.querySelector(
			'.ppcart-account-detail-presenter'
		);

		if ( ! presenter ) {
			window.location.href = detailRequest.fallbackUrl;
			return;
		}

		removeExistingPresenters( detailRequest.context );
		detailRequest.context.appendChild( presenter );
		openPresenter( presenter );

		const closeButton = presenter.querySelector(
			'.ppcart-account-detail-presenter__close'
		);

		if ( closeButton ) {
			closeButton.focus( { preventScroll: true } );
		}
	}

	function setLoading( context, isLoading ) {
		context.classList.toggle( 'ppcart-account-detail-loading', isLoading );

		if ( isLoading ) {
			context.setAttribute( 'aria-busy', 'true' );
		} else {
			context.removeAttribute( 'aria-busy' );
		}
	}

	function loadDetail( detailRequest ) {
		setLoading( detailRequest.context, true );

		window
			.fetch( buildRequestUrl( detailRequest ), {
				credentials: 'same-origin',
				headers: {
					Accept: 'application/json',
					'X-WP-Nonce': config.nonce || '',
				},
			} )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( 'Account detail request failed.' );
				}

				return response.json();
			} )
			.then( ( data ) => {
				showDetail( detailRequest, data.html );
			} )
			.catch( () => {
				window.location.href = detailRequest.fallbackUrl;
			} )
			.finally( () => {
				setLoading( detailRequest.context, false );
			} );
	}

	function closePresenter( link ) {
		const presenter = link.closest( '.ppcart-account-detail-presenter' );

		if ( ! presenter ) {
			return;
		}

		const closeUrl = getLinkUrl( link );

		removePresenter( presenter );

		if ( closeUrl && window.history && window.history.replaceState ) {
			window.history.replaceState( null, '', closeUrl.toString() );
		}
	}

	document.addEventListener( 'click', ( event ) => {
		if ( ! event.target || ! event.target.closest ) {
			return;
		}

		const closeLink = event.target.closest(
			'.ppcart-account-detail-presenter__backdrop, .ppcart-account-detail-presenter__close'
		);

		if ( closeLink ) {
			event.preventDefault();
			closePresenter( closeLink );
			return;
		}

		if (
			event.defaultPrevented ||
			event.button !== 0 ||
			event.metaKey ||
			event.ctrlKey ||
			event.shiftKey ||
			event.altKey
		) {
			return;
		}

		const link = event.target.closest( 'a[href]' );

		if ( ! link || ( link.target && '_self' !== link.target ) ) {
			return;
		}

		const detailRequest = getDetailRequest( link );

		if ( ! detailRequest ) {
			return;
		}

		event.preventDefault();
		loadDetail( detailRequest );
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' !== event.key ) {
			return;
		}

		const presenter = document.querySelector(
			'.ppcart-account-detail-presenter'
		);

		if ( presenter ) {
			removePresenter( presenter );
		}
	} );
} )();
