---
title: "ActiveCampaign"
order: 2
doc_category: publishpress-cart
---

# ActiveCampaign

Do you use ActiveCampaign? The ActiveCampaign integration allows you to automatically add or remove new users to your email lists. You can integrate your account with [PublishPress Cart](https://publishpress.com/links/cart) by adding your ActiveCampaign URL and Secret Key in the Integrations settings tab.

**How To Integrate ActiveCampaign With [PublishPress Cart](https://publishpress.com/links/cart)**

Log in to your ActiveCampaign account. In the navigation menu, select Settings > Developer.

In the developer window copy your ActiveCampaign API. Then head over to your WordPress Dashboard, select PublishPress Cart > Settings > Integrations.

![](https://docs-media.publishpress.com/publishpress-cart/active-campaign-6db56d3343.webp)

Then paste your ActiveCampaign URL into the matching field box. Repeat this process to copy and paste the ActiveCampaign Secret Key.

Scroll to the bottom of the Integrations window and select Save Settings. Your ActiveCampaign account is now linked to PublishPress Cart.

From here you can set up an ActiveCampaign integration by going to a PublishPress Cart product and selecting “ActiveCampaign” from the services menu in the Integrations tab. You’ll see a ActiveCampaign List dropdown with a list of all the email lists you previously created in ActiveCampaign. Choose the list you would like to link to this product.

If you have created Tags with this list, type the name in the ActiveCampaign Tags field box and use a comma to separate each one.

**Note**: If your ActiveCampaign Lists and Tags are not showing up in the dropdown, click the **Renew Mailing Lists**button to fetch your latest mailing list info.

In the example below the following will occur from the settings chosen. When a product is purchased with any payment plan a contact is added to the SLO Customers list in ActiveCampaign.

![](https://docs-media.publishpress.com/publishpress-cart/active-campaign-integration-list-a591a837e0.webp)

You have the option to enable Required Consent. If turned on, only customers who have given consent will be added to the list. Note, you will also need to turn on the Opt-in Checkbox in the Form Fields tab for this feature to work.

You also have the option to send custom field information to Active Campaign using Field Maps. To do this, you need to add the **Personalization Tag**and matching **Custom Field ID**in one string per line.

For example:   
%TEST_FIELD%: test  
%DATE%: test2

![](https://www.studiocart.co/wp-content/uploads/2022/02/SC-field-map-1024x135.png)

Once you have finished filling out all the settings needed for your product, complete the order form by selecting Publish or Update.
