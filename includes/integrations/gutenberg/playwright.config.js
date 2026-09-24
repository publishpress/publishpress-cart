const { defineConfig, devices } = require( '@playwright/test' );

require( '../../../tests/playwright/support/nerd-console-glyphs.cjs' );

module.exports = defineConfig( {
	testDir: './tests/e2e',
	timeout: 90000,
	expect: {
		timeout: 15000,
	},
	fullyParallel: false,
	reporter: process.env.CI ? [ [ 'list' ], [ 'html', { open: 'never' } ] ] : 'list',
	outputDir: '../../../tests/e2e-results',
	use: {
		baseURL: process.env.WP_BASE_URL,
		ignoreHTTPSErrors: true,
		screenshot: 'only-on-failure',
		trace: 'retain-on-failure',
		video: 'retain-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
