import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import ShoppingCartStyleControls from '../shopping-cart-style-controls';
import metadata from './block.json';

registerBlockType( metadata.name, {
    ...metadata,
    title: __( 'Cart Shopping Cart', 'publishpress-cart' ),
    edit( { attributes, setAttributes } ) {
        return <div { ...useBlockProps() }>
            <InspectorControls>
                <PanelBody title={ __( 'Continue shopping link', 'publishpress-cart' ) }>
                    <ToggleControl label={ __( 'Show the link', 'publishpress-cart' ) } help={ __( 'The link goes to the Shop page.', 'publishpress-cart' ) } checked={ attributes.showContinueShopping } onChange={ ( showContinueShopping ) => setAttributes( { showContinueShopping } ) } />
                    { attributes.showContinueShopping && <TextControl label={ __( 'Link text', 'publishpress-cart' ) } placeholder={ __( '← Continue shopping', 'publishpress-cart' ) } help={ __( 'Leave empty to use the default text.', 'publishpress-cart' ) } value={ attributes.continueShoppingText } onChange={ ( continueShoppingText ) => setAttributes( { continueShoppingText } ) } /> }
                </PanelBody>
                <ShoppingCartStyleControls settings={ attributes.styleSettings } onChange={ ( styleSettings ) => setAttributes( { styleSettings } ) } />
            </InspectorControls>
            <ServerSideRender block={ metadata.name } attributes={ attributes } />
        </div>;
    },
    save: () => null,
} );
