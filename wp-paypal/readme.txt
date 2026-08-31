=== Payment Button for PayPal ===
Contributors: naa986
Donate link: https://wphowto.net/
Tags: paypal, checkout, payment, ecommerce
Requires at least: 5.3
Tested up to: 7.1
Stable tag: 1.2.3.45
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily accept payment in WordPress by adding a PayPal button to your website.

== Description ==

[Payment Button for PayPal](https://wphowto.net/wordpress-paypal-plugin-732) plugin (also known as WP PayPal) allows you to easily create PayPal Buy Now buttons. It generates dynamic buttons using shortcodes that enable PayPal checkout on your WordPress site.

Your customers will be able to pay for your products using PayPal or Credit Card. This plugin makes it easy for you to set up your online e-commerce store.

Payment Button for PayPal supports PayPal Sandbox. PayPal Sandbox is a simulation environment which allows you to do test purchases between a test buyer and a seller account. This is to make sure that your store can process PayPal transactions without any issues. It also helps you get prepared before selling to real customers.

=== Payment Button for PayPal Features ===

* Sell products or services using PayPal
* Create PayPal buttons in a post/page using shortcodes
* Accept once off payments
* Accept donations from users
* View or Manage orders received via PayPal buttons from your WordPress admin dashboard
* Quick settings configurations
* Enable debug to troubleshoot various issues (e.g. orders not getting updated)
* Open PayPal log to see how order are being processed
* Accept debit or credit card payments
* Switch your store to PayPal sandbox mode for testing
* Sell in any currency supported by PayPal
* Charge shipping on your products or services
* Send a purchase confirmation email to your customer after a transaction
* Send a sale notification email to one or more recipients (e.g. the seller) after a transaction
* Accept payments with PayPal smart payment buttons
* Accept Pay Later Pay in 4 payments

*Note: This is NOT an official PayPal product.*

=== How to Use Payment Button for PayPal ===

Once you have installed this plugin you need to go to the settings menu to configure some PayPal settings (WP PayPal -> Settings).

= PayPal Checkout Settings =

* **Client ID**: The client ID for your PayPal REST API app
* **Secret Key**: The secret key for your PayPal REST API app
* **Currency Code**: The default currency code for payments
* **Return URL**: The redirect URL after a successful payment
* **Cancel URL**: The redirect URL when a payment is cancelled
* **Checkout Page URL**: The URL of the page where PayPal checkout options will appear

=== How to Create a PayPal Payment Button ===

To create a PayPal Checkout button create a product first (WP PayPal -> Products). Now insert the shortcode into a page like the following:

`[wp_paypal_product id="1"]`

Replace 1 with the actual product ID.

= How to Create a PayPal Checkout Page =

To create a PayPal checkout page insert the shortcode into a page like the following.

`[wp_paypal_checkout]`

You will also need to save the URL of the page in the settings.

For more information check the [PayPal Checkout](https://wphowto.net/wordpress-paypal-plugin-732) documentation page.

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
2. PayPal Orders
3. PayPal Email Sender Options
4. PayPal Purchase Receipt Email Settings
5. PayPal Sale Notification Email Settings

== Upgrade Notice ==

= 1.2.3.45 =
This version disabled existing payment buttons. Please follow the updated documentation to set up.

== Changelog ==

= 1.2.3.45 =
* Added a product interface.
* Disabled existing payment buttons.

= 1.2.3.44 =
* Changes to URL parameters.

= 1.2.3.43 =
* Changed order menu icon.

= 1.2.3.42 =
* Checkout API update.

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
