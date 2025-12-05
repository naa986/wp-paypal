=== Payment Button for PayPal ===
Contributors: naa986
Donate link: https://wphowto.net/
Tags: paypal, cart, checkout, payment, ecommerce
Requires at least: 5.3
Tested up to: 6.9
Stable tag: 1.2.3.41
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily accept payment in WordPress by adding a PayPal button to your website. Add PayPal Buy Now, Add to Cart, Subscription or Donation button.

== Description ==

[Payment Button for PayPal](https://wphowto.net/wordpress-paypal-plugin-732) plugin (also known as WP PayPal) allows you to easily create PayPal Buy Now, Add to Cart, Donation or Subscription type buttons. It generates dynamic buttons using shortcodes that enable PayPal checkout on your WordPress site.

Your customers will be able to pay for your products using PayPal or Credit Card. This plugin makes it easy for you to set up your online e-commerce store. You do not need to spend days configuring products and settings. All you need to do is insert a shortcode into one of your web pages and your website will be ready to go live.

Payment Button for PayPal supports PayPal Sandbox. PayPal Sandbox is a simulation environment which allows you to do test purchases between a test buyer and a seller account. This is to make sure that your store can process PayPal transactions without any issues. It also helps you get prepared before selling to real customers.

=== Payment Button for PayPal Add-ons ===

* [Buy Now Custom Amount](https://wphowto.net/wordpress-paypal-plugin-732)
* [Buy Now Discount](https://wphowto.net/applying-discount-to-a-paypal-buy-button-in-wordpress-834)
* [Contact Form 7 Integration](https://wphowto.net/wp-paypal-button-integration-with-contact-form-7-6710)
* [Custom Input](https://wphowto.net/how-to-show-a-text-box-at-wp-paypal-checkout-to-collect-custom-data-from-buyers-6517)
* [Mailchimp Integration](https://wphowto.net/wp-paypal-mailchimp-integration-6559)
* [Product Variations](https://wphowto.net/how-to-configure-product-variations-in-wp-paypal-6413)
* [Variable Price](https://wphowto.net/wp-paypal-variable-price-6988)
* [Variable Quantity](https://wphowto.net/how-to-add-a-quantity-field-to-a-paypal-button-6428)
* [Variable Subscription](https://wphowto.net/how-to-add-a-recurring-price-field-to-a-paypal-subscription-button-6450)
* [Custom Donations](https://wphowto.net/custom-donations-for-wordpress-paypal-donate-buttons-6778)
* [Order Export](https://wphowto.net/wp-paypal-order-export-7347)

=== Payment Button for PayPal Features ===

* Sell products or services using PayPal
* Create PayPal buttons on the fly in a post/page using shortcodes
* Accept once off payments
* Accept recurring subscription payments
* Accept donations from users
* Use multiple PayPal accounts to accept payments
* Allow users to add multiple items to the shopping cart and checkout
* View or Manage orders received via PayPal buttons from your WordPress admin dashboard
* Quick settings configurations
* Enable debug to troubleshoot various issues (e.g. orders not getting updated)
* Open PayPal log to see how IPN (Instant Payment Notification) is being received from PayPal
* Accept debit or credit card payments
* Accept recurring subscription payments on a daily, weekly, monthly or yearly basis
* Switch your store to PayPal sandbox mode for testing
* Sell in any currency supported by PayPal
* Charge shipping on your products or services
* Charge tax on your products or services
* Send a purchase confirmation email to your customer after a transaction
* Send a sale notification email to one or more recipients (e.g. the seller) after a transaction
* Accept payments with PayPal smart payment buttons
* Accept Pay Later Pay in 4 payments

*Note: This is NOT an official PayPal product.*

=== How to Use Payment Button for PayPal ===

Once you have installed this plugin you need to go to the settings menu to configure some PayPal settings (WP PayPal -> Settings).

= PayPal Checkout Settings =

These settings apply to the "[wp_paypal_checkout]" shortcode button. It uses the PayPal Checkout payment method.

* **Client ID**: The client ID for your PayPal REST API app
* **Currency Code**: The default currency code for payments
* **Return URL**: The redirect URL after a successful payment
* **Cancel URL**: The redirect URL when a payment is cancelled

= PayPal Payments Standard Settings =

These settings apply to the "[wp_paypal]" shortcode button. It uses the PayPal Payments Standard payment method.

* **PayPal Merchant ID**: Your PayPal Merchant ID
* **PayPal Email**: Your PayPal email address
* **Currency Code**: The default currency code

=== Payment Button for PayPal Emails ===

Payment Button for PayPal plugin comes with an "Emails" tab where you will be able to configure some email related settings.

**Email Sender Options**

In this section you can choose to customize the default From Name and From Email Address that will be used when sending an email.

**Purchase Receipt Email**

When this feature is enabled an email sent to the customer after completion of a successful purchase. Options you can customize here:

* The subject of the purchase receipt email
* The content type of the purchase receipt email. The default is "Plain Text". But you can also set it to "HTML"
* The body of the purchase receipt email.

**Sale Notification Email**

When this feature is enabled an email is sent to your chosen recipient(s) after completion of a successful purchase. Options you can customize here:

* The subject of the sale notification email
* The content type of the sale notification email. The default is "Plain Text". But you can also set it to "HTML"
* The body of the sale notification email.

You can use various email tags in the subject/body of an email to dynamically change its content. You can find the full list of available email tags in the [WordPress PayPal](https://wphowto.net/wordpress-paypal-plugin-732) plugin page.

Can the email messages be sent over SMTP? Absolutely. The following SMTP plugins have been tested:

* SMTP Mailer
* Gmail SMTP
* WP Mail SMTP
* Post SMTP
* FluentSMTP
* Easy WP SMTP

=== How to Create a PayPal Checkout Button ===

In order to create a PayPal Checkout button insert the shortcode like the following:

`[wp_paypal_checkout description="test checkout product" amount="3.99"]`

= PayPal Checkout Shortcode Parameters =

You can use additional parameters to customize your PayPal Checkout buttons.

* **description** - Description of the purchase.
* **amount** - The price of the product.

For more information check the [PayPal Checkout](https://wphowto.net/wordpress-paypal-plugin-732) documentation page.

=== How to Create a PayPal Payments Standard Button ===

In order to create a PayPal Payments Standard button insert the shortcode like the following.

= PayPal Buy Now =

Buy Now buttons are for single item purchases. In order to create a buy button you need to specify it in the button parameter of the shortcode.

`[wp_paypal button="buynow" name="My product" amount="1.00"]`

= PayPal Add to Cart =

Add To Cart buttons let users add multiple items to their PayPal shopping cart and checkout.

`[wp_paypal button="cart" name="My product" amount="1.00"]`

= PayPal View Cart =

View Cart buttons let users view items that were added to their PayPal shopping cart.

`[wp_paypal button="viewcart"]`

= PayPal Donation =

Donation buttons let you accept donations from your users.

`[wp_paypal button="donate" name="My product"]`

= PayPal Subscription =

`[wp_paypal button="subscribe" name="My product" a3="1.00" p3="1" t3="M" src="1"]`

Subscribe buttons let you set up payment subscriptions.

= PayPal Payments Standard Shortcode Buttons Parameters =

You can use additional parameters to customize your PayPal buttons.

* **type** - The type of button to render (e.g. "buynow", "cart", "donate" or "subscribe")
* **name** - Description of the item.
* **button_image** - Your custom button image URL (e.g. button_image="https://example.com/images/buy.png").
* **button_text** - Your custom button text (e.g. button_text="Buy Now").
* **number** - The number of the item (Also known as SKU. e.g. number="16").
* **amount**- The price of the item (e.g. amount="4.95").
* **currency** - The currency of the item (e.g. currency="USD").
* **quantity** - Quantity of items to purchase (e.g. quantity="2").
* **shipping** - The cost of shipping this item. (e.g. shipping="0.75"). If you specify "shipping" and "shipping2" is not defined, this flat amount is charged regardless of the quantity of items purchased. 
* **shipping2** - The cost of shipping each additional unit of this item (e.g. shipping2="1.25")
* **handling** - The handling cost of an item (e.g. handling="2.5")
* **tax** - The flat tax amount for an item (e.g. tax="2.99").
* **tax_rate** - The rate of tax for an item (e.g. tax_rate="2.9").
* **locale** - The desired locale of the PayPal site (e.g. locale="GB"). This feature is useful if you want to render the payment page in a specific language.
* **return** - The URL to which the user will be redirected after the payment (e.g. return="https://example.com/thank-you").
* **cancel_return** - The URL to which PayPal will redirect the buyer if they cancel checkout before completing the payment (e.g. cancel_return="https://example.com/payment-canceled").
* **no_shipping** - This parameter allows you to control whether or not to prompt buyers for a shipping address (e.g. no_shipping="1"). Allowable values: 0 - Prompt for an address, but do not require one (This is set by default), 1 - Do not prompt for an address, 2 - Prompt for an address, and require one.
* **undefined_quantity** - Allow buyers to specify the quantity of the item on the payment page (e.g. undefined_quantity="1"). This option is only used with a Buy Now button.
* **target** - This parameter allows you to open a PayPal button in a new window or tab (e.g. target="_blank").
* **shopping_url** - This parameter allows you to customize the Continue Shopping URL for the View Cart button (e.g. shopping_url="https://example.com/shop").
* **business** - This parameter allows you to override the seller account specified in the settings. You can specify either your PayPal merchant ID or email address in it (e.g. business="HV3QO52MBTT34" or business="rbg123@gmail.com").
* **form_class** - Your custom CSS class to target the button form (e.g. form_class="ppbtn"). Multiple classes are supported (e.g. form_class="ppbtn ppbtn2 ppbtn3").

= PayPal Add to Cart Button/Shopping Cart Specific Parameters =

* **handling** - Handling charges. This parameter is not quantity-specific, which means the same handling cost applies, regardless of the number of items on the order. (e.g. handling="2.00").

For detailed documentation please check out the [Payment Button for PayPal](https://wphowto.net/wordpress-paypal-plugin-732) plugin page.

=== Translation ===

If you are a non-English speaker please help translate the plugin into your language.

=== Additional PayPal Documentation ===

* [Add automatic discount to a buy button](https://wphowto.net/applying-discount-to-a-paypal-buy-button-in-wordpress-834)
* [Subscriptions & Recurring Payments Setup](https://wphowto.net/how-to-create-a-paypal-subscription-button-in-wordpress-911)

== Installation ==

1. Go to the Add New plugins screen in your WordPress Dashboard
1. Click the upload tab
1. Browse for the plugin file (wp-paypal.zip) on your computer
1. Click "Install Now" and then hit the activate button

== Frequently Asked Questions ==

= Can I accept PayPal payments in WordPress using this plugin? =

Yes.

= Can I accept one-time PayPal payments using this plugin? =

Yes.

= Can I accept PayPal recurring subscription payments using this plugin? =

Yes.

= Can I accept PayPal donation payments using this plugin? =

Yes.

= Does this plugin support PayPal shopping cart checkout? =

Yes.

= Can I accept WooCommerce PayPal payments using this plugin? =

No. This is not a WooCommerce plugin.

= Can I create a PayPal account using this plugin? =

No.

= Can I set up a PayPal account for my client using this plugin? =

No.

= Can I log in to my PayPal account using this plugin? =

No.

== Screenshots ==

1. PayPal Checkout Button
2. PayPal Payments Standard Button
3. PayPal Orders
4. PayPal Email Sender Options
5. PayPal Purchase Receipt Email Settings
6. PayPal Sale Notification Email Settings

== Upgrade Notice ==
none

== Changelog ==

= 1.2.3.41 =
* Added support for optional custom input.

= 1.2.3.40 =
* Made changes to save payment data.

= 1.2.3.39 =
* Added an option to edit order data shown in the table.

= 1.2.3.38 =
* Fixed broken parameters in the return URL.

= 1.2.3.37 =
* File naming changes.
* Language file updated.

= 1.2.3.36 =
* Some improvements in security reported by Wordfence.

= 1.2.3.35 =
* Some improvements in security reported by Wordfence.

= 1.2.3.34 =
* Added an option to append the purchase email to the sale notification email.

= 1.2.3.33 =
* Added the label parameter to customize the PayPal button text.

= 1.2.3.32 =
* Added an option to load PayPal Checkout scripts on every page.

= 1.2.3.31 =
* Added support for the variable price add-on.

= 1.2.3.30 =
* Added an action hook after the IPN is received.

= 1.2.3.29 =
* Added an email tag to generate three digit random numbers.

= 1.2.3.28 =
* Added an option to enable funding sources.

= 1.2.3.27 =
* Made changes to the code that retrieve the plugin url and path.

= 1.2.3.26 =
* Added an option to disable funding sources.

= 1.2.3.25 =
* Better debug logging.

= 1.2.3.24 =
* Additional check for the settings link.

= 1.2.3.23 =
* Changed the order of variations and custom input fields.

= 1.2.3.22 =
* Added email tag for variations.

= 1.2.3.21 =
* Added support for PayPal checkout payment method.

= 1.2.3.20 =
* Added support for email tags in the email subject.

= 1.2.3.19 =
* Added button_text parameter to create PayPal buttons without images.
* Added form_class parameter to target PayPal button form and apply custom styling.

= 1.2.3.18 =
* A fixed amount can be specified in the donate button.
* Added support for the custom donations add-on.

= 1.2.3.17 =
* Made the PayPal buttons compatible with AMP.

= 1.2.3.16 =
* Added a shortcode parameter to receive payments on separate accounts.
* Added an option to enable/disable the receiver check.

= 1.2.3.15 =
* Added an email tag for shipping address.

= 1.2.3.14 =
* Added an option to disable ipn validation.

= 1.2.3.13 =
* Added support for Contact Form 7.
* Fixed a sanitization issue in the general settings.

= 1.2.3.12 =
* Fixed a minor bug that was preventing From Name from saving into the database.

= 1.2.3.11 =
* Added custom variable to order list.

= 1.2.3.10 =
* Added support for Mailchimp.

= 1.2.3.9 =
* Product name and price can be dynamically changed for a Buy Now button via query strings in the URL.

= 1.2.3.8 =
* Made some security related improvements in the orders menu.

= 1.2.3.7 =
* Added support for custom input. This can be used to show a custom text box at checkout where buyers can enter any data.

= 1.2.3.6 =
* Product names are shown in the edit order interface.

= 1.2.3.5 =
* Sale notification email can be sent to multiple recipients.

= 1.2.3.4 =
* Added support for email options.
* The orders menu now shows the email address of the customer.

= 1.2.3.3 =
* Added support for variable subscription price.

= 1.2.3.2 =
* Added support for variable quantity.

= 1.2.3.1 =
* Added support for product variations add-on.

= 1.2.3.0 =
* Added support for trial period 2 in the PayPal subscription button.

= 1.2.2.9 =
* Added the shopping_url parameter for the View Cart button.

= 1.2.2.8 =
* Removed unused JS from the plugin.

= 1.2.2.7 =
* Added the notify_url parameter to send instant payment notification to a different URL.

= 1.2.2.6 =
* Made the shopping_url parameter available for add to cart type buttons.

= 1.2.2.5 =
* Made some security related improvements in the plugin

= 1.2.2.4 =
* The plugin now shows more error messages if it fails to insert/update an order.

= 1.2.2.3 =
* Merchant ID can now be entered in the plugin settings.

= 1.2.2.2 =
* Added a view cart button for the PayPal shopping cart.

= 1.2.2.1 =
* Charset is now set to utf-8 for all the buttons.

= 1.2.2 =
* Fixed an issue where the add to cart button would open a new tab.

= 1.2.1 =
* Shipping address is now displayed on a separate row in the order content area.

= 1.2.0 =
* Updated the parameters in the subscribe button. This should fix the button image rendering issue on some websites.

= 1.1.9 =
* Donate buttons now support the no_shipping parameter.

= 1.1.8 =
* Made some improvements to the donate button.

= 1.1.7 =
* Made some improvements to the orders menu.

= 1.1.6 =
* Added support for custom field in the shortcode.

= 1.1.5 =
* An action is now triggered after processing the PayPal ipn.

= 1.1.4 =
* Fixed a warning notice in the orders menu.

= 1.1.3 =
* Fixed a bug where the target attribute was not working.

= 1.1.2 =
* Made some improvements to WP PayPal orders.

= 1.1.1 =
* Made some improvements to the PayPal Buy Now button.

= 1.1.0 =
* Fixed this admin notice: screen_icon is deprecated since version 3.8.0 with no alternative available.
* Made some improvements to the add to cart functionality.
* Fixed a PayPal error that occurred when an item was added to the shopping cart: Things don't appear to be working at the moment. Please try again later.


= 1.0.9 =
* Added an option to open a PayPal button in a new window or tab.

= 1.0.8 =
* Fixed a bug where queries could be performed on orders on the front end.
* WP PayPal orders are now also excluded from search.

= 1.0.7 =
* Added a new action hook which will get triggered once the payment is processed by the plugin.
* Added a new shortcode parameter to customize the default PayPal button.

= 1.0.6 =
* Fixed an issue that was causing subscription payments to not get processed.

= 1.0.5 =
* Fixed an issue that was causing this error - "Cannot load wp-paypal settings".

= 1.0.4 =
* Made some improvements so language packs can be enabled

= 1.0.3 =
* Fixed a minor bug in the settings which was causing this error: "You do not have sufficient permissions to access this page".
* Plugin strings are now translatable.

= 1.0.2 =
* PayPal Button plugin is now compatible with WordPress 4.3

= 1.0.1 =
* First commit
