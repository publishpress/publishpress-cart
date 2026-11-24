import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl, ColorPalette } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
    ...metadata,
    title: __( 'Cart Shop', 'publishpress-cart' ),
    edit( { attributes, setAttributes } ) {
        const style = attributes.styleSettings;
        const setStyle = ( key, value ) => setAttributes( { styleSettings: { ...style, [ key ]: value } } );
        return <div { ...useBlockProps() }>
            <InspectorControls>
                <PanelBody title={ __( 'Shop', 'publishpress-cart' ) }>
                    <RangeControl label={ __( 'Products per page', 'publishpress-cart' ) } value={ attributes.perPage } onChange={ ( perPage ) => setAttributes( { perPage } ) } min={ 1 } max={ 100 } />
                    <RangeControl label={ __( 'Columns', 'publishpress-cart' ) } value={ attributes.columns } onChange={ ( columns ) => setAttributes( { columns } ) } min={ 1 } max={ 4 } />
                    <SelectControl label={ __( 'Order by', 'publishpress-cart' ) } value={ attributes.orderBy } onChange={ ( orderBy ) => setAttributes( { orderBy } ) } options={ [ [ 'date', __( 'Date', 'publishpress-cart' ) ], [ 'title', __( 'Title', 'publishpress-cart' ) ], [ 'price', __( 'Price', 'publishpress-cart' ) ], [ 'menu_order', __( 'Menu order', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } />
                    { [ [ 'categories', __( 'Category IDs', 'publishpress-cart' ) ], [ 'tags', __( 'Tag IDs', 'publishpress-cart' ) ] ].map( ( [ key, label ] ) => <TextControl key={ key } label={ label } help={ __( 'Separate term IDs with commas.', 'publishpress-cart' ) } value={ attributes[ key ].join( ', ' ) } onChange={ ( value ) => setAttributes( { [ key ]: value.split( ',' ).map( Number ).filter( ( id ) => Number.isInteger( id ) && id > 0 ) } ) } /> ) }
                    { [ [ 'showImage', __( 'Show image', 'publishpress-cart' ) ], [ 'showExcerpt', __( 'Show excerpt', 'publishpress-cart' ) ], [ 'showPrice', __( 'Show price', 'publishpress-cart' ) ], [ 'includeCollections', __( 'Include collections', 'publishpress-cart' ) ] ].map( ( [ key, label ] ) => <ToggleControl key={ key } label={ label } checked={ attributes[ key ] } onChange={ ( value ) => setAttributes( { [ key ]: value } ) } /> ) }
                </PanelBody>
                <PanelBody title={ __( 'Style', 'publishpress-cart' ) } initialOpen={ false }>
                    <SelectControl label={ __( 'Preset', 'publishpress-cart' ) } value={ style.preset || '' } onChange={ ( value ) => setStyle( 'preset', value ) } options={ [ [ '', __( 'Default', 'publishpress-cart' ) ], [ 'minimal', __( 'Minimal', 'publishpress-cart' ) ], [ 'carded', __( 'Carded', 'publishpress-cart' ) ], [ 'compact', __( 'Compact', 'publishpress-cart' ) ], [ 'contrast', __( 'High contrast', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } />
                    <p>{ __( 'Accent color', 'publishpress-cart' ) }</p>
                    <ColorPalette value={ style.accentColor } onChange={ ( value ) => setStyle( 'accentColor', value || '' ) } />
                    { [ [ 'surfaceStyle', __( 'Surface', 'publishpress-cart' ), [ [ 'card', __( 'Card', 'publishpress-cart' ) ], [ 'boxed', __( 'Boxed', 'publishpress-cart' ) ] ] ], [ 'density', __( 'Density', 'publishpress-cart' ), [ [ 'compact', __( 'Compact', 'publishpress-cart' ) ], [ 'spacious', __( 'Spacious', 'publishpress-cart' ) ] ] ], [ 'cornerRadius', __( 'Corners', 'publishpress-cart' ), [ [ 'square', __( 'Square', 'publishpress-cart' ) ], [ 'rounded', __( 'Rounded', 'publishpress-cart' ) ] ] ] ].map( ( [ key, label, values ] ) => <SelectControl key={ key } label={ label } value={ style[ key ] || '' } onChange={ ( value ) => setStyle( key, value ) } options={ [ { value: '', label: __( 'Default', 'publishpress-cart' ) }, ...values.map( ( [ value, optionLabel ] ) => ( { value, label: optionLabel } ) ) ] } /> ) }
                </PanelBody>
            </InspectorControls>
            <ServerSideRender block={ metadata.name } attributes={ attributes } />
        </div>;
    },
    save: () => null,
} );
