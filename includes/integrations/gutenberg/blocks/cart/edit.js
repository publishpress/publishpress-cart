import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
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
                <ShoppingCartStyleControls settings={ attributes.styleSettings } onChange={ ( styleSettings ) => setAttributes( { styleSettings } ) } />
            </InspectorControls>
            <ServerSideRender block={ metadata.name } attributes={ attributes } />
        </div>;
    },
    save: () => null,
} );
