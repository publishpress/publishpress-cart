import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import ShoppingCartStyleControls from '../shopping-cart-style-controls';
import { brandIcon } from '../_shared/block-icon';
import metadata from './block.json';

registerBlockType( metadata.name, {
    ...metadata,
    icon: brandIcon( metadata.icon ),
    title: __( 'PP Cart Mini Cart', 'publishpress-cart' ),
    edit( { attributes, setAttributes } ) {
        return <div { ...useBlockProps() }>
            <InspectorControls>
                <PanelBody title={ __( 'Mini Cart', 'publishpress-cart' ) }>
                    { [ [ 'showCount', __( 'Show count', 'publishpress-cart' ) ], [ 'showTotal', __( 'Show total', 'publishpress-cart' ) ], [ 'opensDrawer', __( 'Open drawer', 'publishpress-cart' ) ] ].map( ( [ key, label ] ) => <ToggleControl key={ key } label={ label } checked={ attributes[ key ] } onChange={ ( value ) => setAttributes( { [ key ]: value } ) } /> ) }
                </PanelBody>
                <ShoppingCartStyleControls settings={ attributes.styleSettings } onChange={ ( styleSettings ) => setAttributes( { styleSettings } ) } />
            </InspectorControls>
            <ServerSideRender block={ metadata.name } attributes={ attributes } />
        </div>;
    },
    save: () => null,
} );
