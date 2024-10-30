=== CAMOO SMS ===
Contributors: camoo
Tags: sms, cameroon, subscribe, sms panel, subscribes-sms, camoo sarl, bulk sms
Requires at least: 3.0
Tested up to: 6.2.2
Requires PHP: 8.1
Stable tag: 3.0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

With CAMOO SMS, you have the ability to send (Bulk) SMS to a group, to a user, to a number, to members of SMS newsletter or to every single event in your site. The usage of this plugin is completely free.

== Description ==
Using CAMOO SMS adds to WordPress, the ability to :

* Send SMS to either your users’ numbers or specific numbers
* Get your users’ mobile numbers when they subscribe to your newsletters
* Send SMS automatically to users and admins in different situations
* Increase the security by two-step verification
* Send SMS to members of SMS newsletter
* And many more!

Try it out on your free dummy site: Click here => [https://tastewp.com/plugins/camoo-sms](https://tastewp.com/new?pre-installed-plugin-slug=camoo-sms&redirect=admin.php%3Fpage%3Dwp-camoo-sms-welcome&ni=true).

= Features =
* Sending SMS to the mobile number(s), your subscribers and WordPress users
* Subscribing for newsletters by SMS
* Sending Activation Codes to subscribers when a new post is published and also when subscribers are completing their subscription process
* Sending Notification SMS to admins
* To inform new releases of WordPress
* When a new user is registered
* When new comments are posted
* When users are logged into the WordPress
* When users are registered to subscribe in forms
* Integration with Contact Form 7, WooCommerce, Easy Digital Downloads. Integration with other plugins is also possible in WP SMS Pro version.
* Supporting Widget for showing SMS newsletters to subscribers
* Supporting WordPress Hooks
* Supporting WP REST API
* Importing/Exporting Subscribers.
* GPG SMS encryption
* Handle status report
* WooCommerce Integration: send SMS to Buyer after status changed
* Smobilpay for e-commerce SMS notification on status changed

== Frequently Asked Questions ==
= PHP 7 Support? =
Yes! Older version < 3.0.0 of CAMOO SMS are compatible with PHP version 7.4. We strongly recommend at least the use of PHP 8.1 to enjoy all the features offered

= How to get my access keys? =
All you need is just to [create an account](https://www.camoo.cm/join) and then ask our team for SMS access keys.

= Can I send Bulk SMS? =
Yes! But to be able to do so, your running PHP version should be at least PHP 8.1 and the function `shell_exec` enabled.

= Do I get delivered status for sending SMS? =
Yes you do! CAMOO SMS handle automatically status and show it up in your outbox section. The following status are available:
* 'delivered'		  Message successfully delivered
* 'scheduled'		  Message has been scheduled for delivery
* 'buffered'		  Message has been buffered
* 'sent'			  Message is sent, but not yet delivered
* 'expired'			  Delivery period over for the message (Failed)
* 'delivery_failed	  Message couldn't be delivered

= Is it possible to encrypt messages before sending? =
Yes! CAMOO SMS uses GPG encryption to ensure the end-to-end encryption between your WordPress site and our server

== Screenshots ==
1. Send SMS Page.
2. Gateway configuration.
3. Bulk SMS configuration.
4. SMS outbox example.
5. Features Page

== Upgrade Notice ==
N/A

== Changelog ==

= 3.0.1: July 21, 2023 =
* Fix: Save report sms status

= 3.0.0: July 21, 2023 =
* Teak: Support PHP8.1+ added
* Teak: Top up sms account in dashboard added
* Teak: Security improvements

= 2.0.6: January 31, 2023 =
* Fix: fetch current balance

= 2.0.5: January 31, 2023 =
* Teak: Gateway Nimbuz removed
* Teak: at least php 7.4 required
* Teak: Composer updated
* Teak: Camoo sms library updated

= 2.0.4: December 12, 2021 =
* Fix: OrderId replacement when sending Woocommerce status changed notification
* New: Send customized (with first name / last name) sms notification to buyer

= 2.0.3: December 07, 2021 =
* Tweak: Table list for outbox is now sorting by DESC per default
* New: Woocommerce Integration: Send SMS to Buyer after status changed added
* Teak: Minor improvements

= 2.0.2: December 04, 2021 =
* Fix: filter apply Country Code improved
* Fix: Get parameter type from strict array to mixed
* New: recommended PHP version from php 7.3

= 2.0.1: November 16, 2021 =
* Fix: Helper::satanise on multiselect field
* Fix: Integration on Contact Form 7
* New: Notification Integration for Smobilpay for e-commerce added
* Teak: Minor improvements

= 2.0.0: November 09, 2021 =
* New: uninstall.php added
* New: support from php 7.2
* Change: Camoo legacy gateway removed
* Fix: callback setting improved
* Teak: Minor improvements
* Teak: satanizeRequest renamed to sataniseRequest

= 1.1 =
* Added: Nimbuz Gateway
* Minor fix

= 1.0 =
* Start plugin
