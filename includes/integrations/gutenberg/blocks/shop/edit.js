import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, __experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown, __experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl, __experimentalToolsPanel as ToolsPanel } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { brandIcon } from '../_shared/block-icon';
import metadata from './block.json';

function syncPreviewPrice( event ) {
    const select = event.target.closest( '.ppcart-shopping-cart-add select' );
    const price = select?.closest( '.ppcart-shop-card' )?.querySelector( '[data-ppcart-shop-price]' );
    const selected = select?.selectedOptions[ 0 ]?.dataset.ppcartPrice;
    if ( price && selected ) {
        price.textContent = selected;
    }
}

function blockPreviewSubmit( event ) {
    event.preventDefault();
}

registerBlockType( metadata.name, {
    ...metadata,
    icon: brandIcon( metadata.icon ),
    title: __( 'PP Cart Shop', 'publishpress-cart' ),
    edit( { attributes, setAttributes, clientId } ) {
        const preview = useRef();
        const colorSettings = useMultipleOriginColorsAndGradients();
        const style = attributes.styleSettings || {};
        const setStyle = ( key, value ) => setAttributes( { styleSettings: { ...style, [ key ]: value || '' } } );
        const design = attributes.cardDesign || {};
        const setDesign = ( key, value ) => {
            const next = { ...design, [ key ]: value };
            if ( value === undefined || value === '' ) {
                delete next[ key ];
            }
            setAttributes( { cardDesign: next } );
        };
        useEffect( () => {
            const node = preview.current;
            node.addEventListener( 'change', syncPreviewPrice );
            node.addEventListener( 'submit', blockPreviewSubmit );
            return () => {
                node.removeEventListener( 'change', syncPreviewPrice );
                node.removeEventListener( 'submit', blockPreviewSubmit );
            };
        }, [] );
        return <div { ...useBlockProps( { ref: preview } ) }>
            <InspectorControls>
                <PanelBody title={ __( 'Shop', 'publishpress-cart' ) }>
                    <SelectControl label={ __( 'Pagination', 'publishpress-cart' ) } value={ attributes.pagination } onChange={ ( pagination ) => setAttributes( { pagination } ) } options={ [ [ 'numbers', __( 'Page numbers', 'publishpress-cart' ) ], [ 'prev_next', __( 'Previous and next', 'publishpress-cart' ) ], [ 'load_more', __( 'Load more button', 'publishpress-cart' ) ], [ 'none', __( 'Show all products', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } />
                    { 'none' !== attributes.pagination && <RangeControl label={ 'load_more' === attributes.pagination ? __( 'Products per load', 'publishpress-cart' ) : __( 'Products per page', 'publishpress-cart' ) } value={ attributes.perPage } onChange={ ( perPage ) => setAttributes( { perPage } ) } min={ 1 } max={ 100 } /> }
                    <RangeControl label={ __( 'Columns', 'publishpress-cart' ) } value={ attributes.columns } onChange={ ( columns ) => setAttributes( { columns } ) } min={ 1 } max={ 4 } />
                    <SelectControl label={ __( 'Order by', 'publishpress-cart' ) } value={ attributes.orderBy } onChange={ ( orderBy ) => setAttributes( { orderBy } ) } options={ [ [ 'date', __( 'Date', 'publishpress-cart' ) ], [ 'title', __( 'Title', 'publishpress-cart' ) ], [ 'price', __( 'Price', 'publishpress-cart' ) ], [ 'menu_order', __( 'Menu order', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } />
                    { [ [ 'categories', __( 'Category IDs', 'publishpress-cart' ) ], [ 'tags', __( 'Tag IDs', 'publishpress-cart' ) ] ].map( ( [ key, label ] ) => <TextControl key={ key } label={ label } help={ __( 'Separate term IDs with commas.', 'publishpress-cart' ) } value={ attributes[ key ].join( ', ' ) } onChange={ ( value ) => setAttributes( { [ key ]: value.split( ',' ).map( Number ).filter( ( id ) => Number.isInteger( id ) && id > 0 ) } ) } /> ) }
                    { [ [ 'showImage', __( 'Show image', 'publishpress-cart' ) ], [ 'showExcerpt', __( 'Show description', 'publishpress-cart' ) ], [ 'showPrice', __( 'Show price', 'publishpress-cart' ) ], [ 'includeCollections', __( 'Include collections', 'publishpress-cart' ) ], [ 'includeSubscriptions', __( 'Include subscription products', 'publishpress-cart' ) ], [ 'linkToProduct', __( 'Link to product page', 'publishpress-cart' ) ] ].map( ( [ key, label ] ) => <ToggleControl key={ key } label={ label } checked={ attributes[ key ] } onChange={ ( value ) => setAttributes( { [ key ]: value } ) } /> ) }
                    { attributes.showExcerpt && <SelectControl label={ __( 'Description', 'publishpress-cart' ) } value={ attributes.descriptionSource } onChange={ ( descriptionSource ) => setAttributes( { descriptionSource } ) } options={ [ [ 'excerpt', __( 'Short excerpt', 'publishpress-cart' ) ], [ 'full', __( 'Full description', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } help={ 'full' === attributes.descriptionSource ? __( 'Shows the whole product description with its lists and formatting.', 'publishpress-cart' ) : __( 'Shows a short plain-text summary.', 'publishpress-cart' ) } /> }
                </PanelBody>
                <PanelBody title={ __( 'Design', 'publishpress-cart' ) } initialOpen={ false }>
                    <SelectControl label={ __( 'Preset', 'publishpress-cart' ) } value={ style.preset || '' } onChange={ ( value ) => setStyle( 'preset', value ) } options={ [ [ '', __( 'Default', 'publishpress-cart' ) ], [ 'minimal', __( 'Minimal', 'publishpress-cart' ) ], [ 'carded', __( 'Carded', 'publishpress-cart' ) ], [ 'compact', __( 'Compact', 'publishpress-cart' ) ], [ 'contrast', __( 'High contrast', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } />
                    <SelectControl label={ __( 'Image shape', 'publishpress-cart' ) } value={ design.imageRatio || 'landscape' } onChange={ ( value ) => setDesign( 'imageRatio', 'landscape' === value ? undefined : value ) } options={ [ [ 'landscape', __( 'Landscape (16:10)', 'publishpress-cart' ) ], [ 'wide', __( 'Wide (16:9)', 'publishpress-cart' ) ], [ 'standard', __( 'Standard (4:3)', 'publishpress-cart' ) ], [ 'square', __( 'Square (1:1)', 'publishpress-cart' ) ], [ 'portrait', __( 'Portrait (3:4)', 'publishpress-cart' ) ], [ 'original', __( 'Original (no crop)', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } />
                    <RangeControl label={ __( 'Card padding (px)', 'publishpress-cart' ) } value={ design.cardPadding } onChange={ ( value ) => setDesign( 'cardPadding', value ) } min={ 0 } max={ 64 } initialPosition={ 24 } allowReset />
                    <RangeControl label={ __( 'Space between cards (px)', 'publishpress-cart' ) } value={ design.cardGap } onChange={ ( value ) => setDesign( 'cardGap', value ) } min={ 0 } max={ 64 } initialPosition={ 20 } allowReset />
                    <ToolsPanel
                        className="block-editor-panel-color-gradient-settings ppcart-shop-design-colors"
                        style={ { margin: '0 -16px -16px' } }
                        label={ __( 'Colors', 'publishpress-cart' ) }
                        panelId={ clientId }
                        hasInnerWrapper
                        headingLevel={ 3 }
                        __experimentalFirstVisibleItemClass="first"
                        __experimentalLastVisibleItemClass="last"
                        resetAll={ () => {
                            const next = { ...design };
                            [ 'cardBackground', 'titleColor', 'textColor', 'priceColor' ].forEach( ( key ) => delete next[ key ] );
                            setAttributes( { cardDesign: next, styleSettings: { ...style, accentColor: '' } } );
                        } }
                    >
                    <div className="block-editor-panel-color-gradient-settings__inner-wrapper">
                    <ColorGradientSettingsDropdown
                        __experimentalIsRenderedInSidebar
                        panelId={ clientId }
                        { ...colorSettings }
                        gradients={ [] }
                        disableCustomGradients
                        settings={ [
                            ...[ [ 'cardBackground', __( 'Card background', 'publishpress-cart' ) ], [ 'titleColor', __( 'Title', 'publishpress-cart' ) ], [ 'textColor', __( 'Description', 'publishpress-cart' ) ], [ 'priceColor', __( 'Price', 'publishpress-cart' ) ] ].map( ( [ key, label ] ) => ( { label, colorValue: design[ key ], onColorChange: ( value ) => setDesign( key, value ), clearable: true } ) ),
                            { label: __( 'Button', 'publishpress-cart' ), colorValue: style.accentColor, onColorChange: ( value ) => setStyle( 'accentColor', value ), clearable: true },
                        ] }
                    />
                    </div>
                    </ToolsPanel>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender block={ metadata.name } attributes={ attributes } />
        </div>;
    },
    save: () => null,
} );
