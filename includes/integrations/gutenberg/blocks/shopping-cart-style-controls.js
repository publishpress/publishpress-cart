import { PanelBody, SelectControl, ColorPalette } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ShoppingCartStyleControls( { settings, onChange } ) {
    const setStyle = ( key, value ) => onChange( { ...settings, [ key ]: value } );
    return (
                <PanelBody title={ __( 'Style', 'publishpress-cart' ) } initialOpen={ false }>
                    <SelectControl label={ __( 'Preset', 'publishpress-cart' ) } value={ settings.preset || '' } onChange={ ( value ) => setStyle( 'preset', value ) } options={ [ [ '', __( 'Default', 'publishpress-cart' ) ], [ 'minimal', __( 'Minimal', 'publishpress-cart' ) ], [ 'carded', __( 'Carded', 'publishpress-cart' ) ], [ 'compact', __( 'Compact', 'publishpress-cart' ) ], [ 'contrast', __( 'High contrast', 'publishpress-cart' ) ] ].map( ( [ value, label ] ) => ( { value, label } ) ) } />
                    <p>{ __( 'Accent color', 'publishpress-cart' ) }</p>
                    <ColorPalette value={ settings.accentColor } onChange={ ( value ) => setStyle( 'accentColor', value || '' ) } />
                </PanelBody>
    );
}
