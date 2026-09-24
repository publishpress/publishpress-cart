import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { AccountBlockPreview } from '../_shared/preview';
import accountPageBuilderBlock from './block';
import accountNavigationBlock from '../account-navigation/block';
import accountTabBlock from '../account-tab/block';
import accountOrdersBlock from '../account-orders/block';
import accountSubscriptionsBlock from '../account-subscriptions/block';
import accountPaymentPlansBlock from '../account-payment-plans/block';
import accountProfileBlock from '../account-profile/block';
import accountLoginBlock from '../account-login/block';
import accountDownloadsBlock from '../account-downloads/block';

const blocks = [
	accountPageBuilderBlock,
	accountNavigationBlock,
	accountTabBlock,
	accountOrdersBlock,
	accountSubscriptionsBlock,
	accountPaymentPlansBlock,
	accountProfileBlock,
	accountLoginBlock,
	accountDownloadsBlock,
];

blocks.forEach( ( block ) => {
	const {
		controls = [],
		edit,
		name,
		panels = [],
		save,
		transforms,
		...settings
	} = block;

	delete settings.$schema;
	const textdomain = settings.textdomain || 'publishpress-cart';

	if ( settings.title ) {
		settings.title = __( settings.title, textdomain );
	}

	if ( settings.description ) {
		settings.description = __( settings.description, textdomain );
	}

	registerBlockType( name, {
		...settings,
		edit:
			edit ||
			( ( props ) => (
				<AccountBlockPreview
					blockName={ name }
					controls={ controls }
					panels={ panels }
					{ ...props }
				/>
			) ),
		save: save || ( () => null ),
		transforms,
	} );
} );
