import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import ShoppingCartStyleControls from '../shopping-cart-style-controls';

registerBlockType( metadata.name, {
    ...metadata,
    title: __( 'Cart Shop', 'publishpress-cart' ),
    edit( { attributes, setAttributes } ) {
        return <div { ...useBlockProps() }>
            <InspectorControls>
                <PanelBody title={ __( 'Shop', 'publishpress-cart' ) }>
                    <RangeControl label={ __( 'Products per page', 'publishpress-cart' ) } value={ attributes.perPage } onChange={ ( perPage ) => setAttributes( { perPage } ) } min={ 1 } max={ 100 } />
                    <RangeControl label={ __( 'Columns', 'publishpress-cart' ) } value={ attributes.columns } onChange={ ( columns ) => setAttributes( { columns } ) } min={ 1 } max={ 4 } />
                    <SelectControl label={ __( 'Order by', 'publishpress-cart' ) } value={ attributes.orderBy } onChange={ ( orderBy ) => setAttributes( { orderBy } ) } options={ [ [ 'date', __( 'Date', 'publishpress-cart' ) ], [ 'title', __( 'Title', 'publishpress-cart' ) ], [ 'price', __( 'Price', 'publishpress-cart' ) ], [ 'menu_order', __( 'Menu order', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } />
                    { [ [ 'categories', __( 'Category IDs', 'publishpress-cart' ) ], [ 'tags', __( 'Tag IDs', 'publishpress-cart' ) ] ].map( ( [ key, label ] ) => <TextControl key={ key } label={ label } help={ __( 'Separate term IDs with commas.', 'publishpress-cart' ) } value={ attributes[ key ].join( ', ' ) } onChange={ ( value ) => setAttributes( { [ key ]: value.split( ',' ).map( Number ).filter( ( id ) => Number.isInteger( id ) && id > 0 ) } ) } /> ) }
                    { [ [ 'showImage', __( 'Show image', 'publishpress-cart' ) ], [ 'showExcerpt', __( 'Show excerpt', 'publishpress-cart' ) ], [ 'showPrice', __( 'Show price', 'publishpress-cart' ) ], [ 'includeCollections', __( 'Include collections', 'publishpress-cart' ) ], [ 'includeSubscriptions', __( 'Include subscription products', 'publishpress-cart' ) ] ].map( ( [ key, label ] ) => <ToggleControl key={ key } label={ label } checked={ attributes[ key ] } onChange={ ( value ) => setAttributes( { [ key ]: value } ) } /> ) }
                </PanelBody>
                <ShoppingCartStyleControls settings={ attributes.styleSettings } onChange={ ( styleSettings ) => setAttributes( { styleSettings } ) } />
            </InspectorControls>
            <ServerSideRender block={ metadata.name } attributes={ attributes } />
        </div>;
    },
    save: () => null,
} );
