---
title: "SendFox"
order: 38
doc_category: publishpress-cart
---

# SendFox

Table of Contents

- [How To Integrate SendFox With PublishPress Cart](#0-toc-title)

Do you use SendFox? You can integrate your account with [PublishPress Cart](https://publishpress.com/links/cart) by adding your SendFox Personal Access Token in the Integrations settings tab.

## How To Integrate SendFox With PublishPress Cart [#](#0-toc-title)

Log in to your SendFox account and navigate to this page [https://sendfox.com/account/oauth](https://sendfox.com/account/oauth) and click **Create New Token**.

Enter a name for your token that describes what it will be used for and click **Create**.

Note: The token will only be shown once, so be sure to copy and save it before closing the window.

Copy the Personal Access Token and head over to your WordPress Dashboard, select **Cart > Settings**. Select the Integrations tab and find SendFox in the list of integrations.

Click **Configure** next to SendFox, then paste the Personal Access Token into the **SendFox API Key** field.

![SendFox API Key field in PublishPress Cart](https://docs-media.publishpress.com/publishpress-cart/sendfox-api-key-settings-bb6c9e163e.webp)

Select **Save changes**. Your SendFox account is now linked to [PublishPress Cart](https://publishpress.com/links/cart).

From here you can set up a SendFox integration by going to a PublishPress Cart product and selecting “SendFox” from the Service menu in the Integrations tab. You’ll see a SendFox List dropdown with a list of all the email lists you previously created in SendFox. Choose the list you would like to link to this product.

Note: If your SendFox Lists are not showing up in the dropdown, click the **Renew Mailing Lists** button to fetch your latest mailing list info.

In the example below the following will occur from the settings chosen. When a product is purchased with any payment plan a contact is added to your chosen list in SendFox.

![](https://docs-media.publishpress.com/publishpress-cart/sendfox-integration-5659657a7d.webp)

You have the option to enable Required Consent. If turned on, only customers who have given consent will be added to the list. Note, you will also need to turn on the Opt-in Checkbox in the Form Fields tab for this feature to work.

Once you have finished filling out all the settings needed for your product, complete the order form by selecting **Publish** or **Update**.
