( function( wp ) {
    'use strict';

    var MIN_HEIGHT = 200;
    // Tall enough for the product settings tabs and one panel.
    var DEFAULT_HEIGHT = 520;

    function getPreferences() {
        try {
            return {
                dispatch: wp.data.dispatch( 'core/preferences' ),
                select: wp.data.select( 'core/preferences' )
            };
        } catch ( error ) {
            return null;
        }
    }

    function setMetaboxPanelPreferences() {
        var preferences = getPreferences();
        var currentHeight;

        if (
            ! preferences ||
            ! preferences.dispatch ||
            ! preferences.select ||
            typeof preferences.dispatch.set !== 'function' ||
            typeof preferences.select.get !== 'function'
        ) {
            return;
        }

        if ( typeof preferences.dispatch.setDefaults === 'function' ) {
            preferences.dispatch.setDefaults( 'core/edit-post', {
                metaBoxesMainIsOpen: true,
                metaBoxesMainOpenHeight: DEFAULT_HEIGHT
            } );
        }

        if ( preferences.select.get( 'core/edit-post', 'metaBoxesMainIsOpen' ) !== true ) {
            preferences.dispatch.set( 'core/edit-post', 'metaBoxesMainIsOpen', true );
        }

        currentHeight = preferences.select.get( 'core/edit-post', 'metaBoxesMainOpenHeight' );

        // Earlier versions saved MIN_HEIGHT for every user; treat that value as "never resized".
        if ( typeof currentHeight !== 'number' || currentHeight === MIN_HEIGHT ) {
            preferences.dispatch.set( 'core/edit-post', 'metaBoxesMainOpenHeight', DEFAULT_HEIGHT );
        } else if ( currentHeight < MIN_HEIGHT ) {
            preferences.dispatch.set( 'core/edit-post', 'metaBoxesMainOpenHeight', MIN_HEIGHT );
        }
    }

    if ( ! wp || ! wp.data || ! wp.domReady ) {
        return;
    }

    wp.domReady( setMetaboxPanelPreferences );
}( window.wp ) );
