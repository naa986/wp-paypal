# PayPal for WordPress

## Description

[WordPress PayPal](https://wphowto.net/wordpress-paypal-plugin-732) plugin allows you to easily create a PayPal Buy Now, Add to Cart, Donation or Subscription type button. It generates the button dynamically using a shortcode that enables PayPal checkout on your WordPress site. It was developed by [wphowto](https://wphowto.net/) and is currently being used on over 10,000 websites.

Your customers will be able to pay for your products using PayPal or Credit Card. This plugin makes it easy for you to set up your online e-commerce store. You do not need to spend days configuring products and settings. All you need to do is insert a shortcode into one of your web pages and your website will be ready to go live.

WP PayPal supports PayPal Sandbox. PayPal Sandbox is a simulation environment which allows you to do test purchases between a test buyer and a seller account. This is to make sure that your store can process PayPal transactions without any issues. It also helps you get prepared before selling to real customers.

## Features

* Sell products or services using PayPal
* Create PayPal buttons on the fly in a post/page using shortcodes
* Accept once off payments or recurring payments
* Accept donations from users
* Allow users to add multiple items to the shopping cart and checkout
* View or Manage orders received via PayPal buttons from your WordPrss admin dashboard
* Quick settings configurations
* Enable debug to troubleshoot various issues (e.g. orders not getting updated)
* Accept subscriptions on a daily, weekly, monthly or yearly basis
* Sell items with different variation options (e.g. size, color, price)
* Switch your store to PayPal sandbox mode for testing
* Compatible with the latest version of WordPress
* Compatible with any WordPress theme
* Sell in any currency supported by PayPal
* Coupon/discount functionality
* Accept recurring payments/subscriptions
* Charge shipping on your products or services
* Charge tax on your products or services

## Usage

Once you have installed this plugin you need to go to the settings menu to configure some default options (WP PayPal -> Settings).

* PayPal Email: Your PayPal email address
* Currency Code: The default currency code

In order to create a button insert the shortcode like the following:

### PayPal Buy Now

Buy Now buttons are for single item purchases. In order to create a buy button you need to specify it in the button parameter of the shortcode.
```
[wp_paypal button="buynow" name="My product" amount="1.00"]
```
### PayPal Add to Cart

Add To Cart buttons let users add multiple items to their PayPal shopping cart and checkout.
```
[wp_paypal button="cart" name="My product" amount="1.00"]
```
### PayPal Donation

Donation buttons let you accept donations from your users.
```
[wp_paypal button="donate" name="My product"]
```
### PayPal Subscription

Subscribe buttons let you set up payment subscriptions. 
```
[wp_paypal button="subscribe" name="My product" a3="1.00" p3="1" t3="M" src="1"]
```
## PayPal Button Parameters

You can use additional parameters to customize your PayPal buttons.

* **type** - The type of button to render (e.g. "buynow", "cart", "donate" or "subscribe")
* **name** - Description of the item.
* **button_image** - Your custom button image URL (e.g. ```button_image="http://example.com/images/buy.png"```).
* **number** - The number of the item (Also known as SKU. e.g. number="16").
* **amount**- The price of the item (e.g. amount="4.95").
* **currency** - The currency of the item (e.g. currency="USD").
* **quantity** - Quantity of items to purchase (e.g. quantity="2").
* **shipping** - The cost of shipping this item. (e.g. shipping="0.75"). If you specify "shipping" and "shipping2" is not defined, this flat amount is charged regardless of the quantity of items purchased. 
* **shipping2** - The cost of shipping each additional unit of this item (e.g. shipping2="1.25")
* **tax** - Transaction-based tax override variable (e.g. tax="2.99").
* **locale** - The desired locale of the PayPal site (e.g. locale="GB"). This feature is useful if you want to render the payment page in a specific language.
* **return** - The URL to which the user will be redirected after the payment (e.g. ```return="http://example.com/thank-you"```).
* **cancel_return** - The URL to which PayPal will redirect the buyer if they cancel checkout before completing the payment (e.g. ```cancel_return="http://example.com/payment-canceled"```).
* **no_shipping** - This parameter allows you to control whether or not to prompt buyers for a shipping address (e.g. no_shipping="1"). Allowable values: 0 - Prompt for an address, but do not require one (This is set by default), 1 - Do not prompt for an address, 2 - Prompt for an address, and require one.
* **undefined_quantity** - Allow buyers to specify the quantity of the item on the payment page (e.g. undefined_quantity="1"). This option is only used with a Buy Now button.
* **target** - This parameter allows you to open a PayPal button in a new window or tab (e.g. target="_blank").

## Add to Cart Button/Shopping Cart Specific Parameters

* **handling** - Handling charges. This parameter is not quantity-specific, which means the same handling cost applies, regardless of the number of items on the order. (e.g. handling="2.00").

## Documentation

For detailed documentation please check out the [PayPal Plugin](https://wphowto.net/wordpress-paypal-plugin-732) page.
