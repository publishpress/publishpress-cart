const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const ABSPATH_GUARD = `<?php
if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

return `;

class PpCartDependencyExtractionWebpackPlugin extends DependencyExtractionWebpackPlugin {
	stringify( asset ) {
		const output = super.stringify( asset );

		if ( this.options.outputFormat !== 'php' ) {
			return output;
		}

		return output.replace( /^<\?php return /, ABSPATH_GUARD );
	}
}

function withAbspathGuard( config ) {
	return {
		...config,
		plugins: ( config.plugins || [] ).map( ( plugin ) => {
			if ( plugin instanceof DependencyExtractionWebpackPlugin ) {
				return new PpCartDependencyExtractionWebpackPlugin( plugin.options );
			}

			return plugin;
		} ),
	};
}

module.exports = Array.isArray( defaultConfig )
	? defaultConfig.map( withAbspathGuard )
	: withAbspathGuard( defaultConfig );
