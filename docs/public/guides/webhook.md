---
title: "Webhooks"
order: 43
doc_category: publishpress-cart
---

# Webhooks

Table of Contents

- [Webhook Parameters](#webhook-parameters)[With method: GET](#with-method-get)[With method: POST](#with-method-post)[Field Map](#field-map)

You can use a webhook integration to send an **HTTP GET**or**POST** request to a target URL. This is useful for running actions and integrating with third-party platforms, and other external APIs that support them.

## Webhook Parameters [#](#webhook-parameters)

#### **With method: GET** [#](#with-method-get)

Please view our article, Personalization Fields for a list of variables you can add to your webhook url.

#### **With method: POST** [#](#with-method-post)

The following parameters are included in our notifications:

| Variable Name | Description |
| --- | --- |
| bump_amount | (decimal) Order bump amount |
| bump_id | (integer) Bump product ID |
| bump_name | (string) Bump product name |
| coupon | (string) Coupon code used at checkout |
| currency | (string) Uppercase, 3-character currency code – USD, GBP, etc |
| customer_country | (string) Customer country |
| customer_email | (string) Customer’s email address |
| customer_firstname | (string) Customer’s first name |
| customer_lastname | (string) Customer’s last name |
| customer_name | (string) Customer’s full name |
| customer_phone | (string) Customer’s first name |
| order_id | (integer) Order ID |
| order_amount | (decimal) Total order amount |
| order_status | (string) Order status |
| order_url | (string) The page the order was placed on |
| payment_plan | (string) The name of the selected payment plan |
| product_id | (string) The main product’s ID |
| product_name | (string) The main product’s name |
| studiocart_secret | (string) The secret is the same as the API Key found under **[PublishPress Cart](https://publishpress.com/links/cart) > Settings**. You should check for this variable on the platform receiving this data, and make sure its value is the same as your API Key to ensure that nobody else can post data to your URL. |
| total order excluding VAT | (string) Pre tax amount |
| VAT amount charged | (string) Tax amount |
| VAT number | (string) VAT number |
| VAT percentage charged | (string) Tax rate |
| website_url | (string) The website URL |
| XXXXXX | (string) Custom field information, replace **XXXXXX** with the ID of your custom field. |
| “XXX” | (string) Static value wrapped in double quotes, replace **XXX** with the string you want to send to the webhook |

#### **Field**Map [#](#field-map)

You can use the **Field Map** field to map order information to custom keys. In addition to the variables listed above, you can also use the following for custom fields and static values.

| Variable Name | Description |
| --- | --- |
| XXXXXX | (string) Custom field information, replace **XXXXXX** with the ID of your custom field. |
| “XXX” | (string) Static value wrapped in double quotes, replace **XXX** with the string you want to send to the webhook. |

To use the field map, add the **Field Key**and the matching **[PublishPress Cart](https://publishpress.com/links/cart) Variable** separated by a colon (“:”), in one string per line.

For example:  
NAME : customer_name  
TEST_FIELD : “static value”

![PublishPress Cart webhook integration settings on a product](https://docs-media.publishpress.com/publishpress-cart/webhook-product-integration-2-95ba1faea0.webp)

**Note**: Field Map and Headers field are both optional. If they’re empty all available order information will be sent in the body along with a header of Content-Type: application/json; charset=utf-8.
