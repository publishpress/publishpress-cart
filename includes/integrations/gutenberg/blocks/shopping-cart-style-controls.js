import { PanelBody, SelectControl, ColorPalette } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ShoppingCartStyleControls( { settings, onChange } ) {
    const setStyle = ( key, value ) => onChange( { ...settings, [ key ]: value } );
    return (
                <PanelBody title={ __( 'Style', 'publishpress-cart' ) } initialOpen={ false }>
                    <SelectControl label={ __( 'Preset', 'publishpress-cart' ) } value={ settings.preset || '' } onChange={ ( value ) => setStyle( 'preset', value ) } options={ [ [ '', __( 'Default', 'publishpress-cart' ) ], [ 'minimal', __( 'Minimal', 'publishpress-cart' ) ], [ 'carded', __( 'Carded', 'publishpress-cart' ) ], [ 'compact', __( 'Compact', 'publishpress-cart' ) ], [ 'contrast', __( 'High contrast', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } />
                    <p>{ __( 'Accent color', 'publishpress-cart' ) }</p>
                    <ColorPalette value={ settings.accentColor } onChange={ ( value ) => setStyle( 'accentColor', value || '' ) } />
                    { [ [ 'surfaceStyle', __( 'Surface', 'publishpress-cart' ), [ [ 'card', __( 'Card', 'publishpress-cart' ) ], [ 'boxed', __( 'Boxed', 'publishpress-cart' ) ] ] ], [ 'density', __( 'Density', 'publishpress-cart' ), [ [ 'compact', __( 'Compact', 'publishpress-cart' ) ], [ 'spacious', __( 'Spacious', 'publishpress-cart' ) ] ] ], [ 'cornerRadius', __( 'Corners', 'publishpress-cart' ), [ [ 'square', __( 'Square', 'publishpress-cart' ) ], [ 'rounded', __( 'Rounded', 'publishpress-cart' ) ] ] ] ].map( ( [ key, label, values ] ) => <SelectControl key={ key } label={ label } value={ settings[ key ] || '' } onChange={ ( value ) => setStyle( key, value ) } options={ [ { value: '', label: __( 'Default', 'publishpress-cart' ) }, ...values.map( ( [ value, optionLabel ] ) => ( { value, label: optionLabel } ) ) ] } /> ) }
                </PanelBody>
    );
}
