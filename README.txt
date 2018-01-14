=== WooGoSend ===
Contributors: sofyansitorus
Tags: woocommerce shipping,go-jek,ojek shipping,gosend,gojek shipping
Requires at least: 4.8
Tested up to: 4.9.1
Requires PHP: 5.6
Stable tag: trunk
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

WooCommerce per kilometer shipping rates calculator for GoSend courier from Go-Jek Indonesia.

== Description ==
WooCommerce per kilometer shipping rates calculator for GoSend courier from Go-Jek Indonesia.

Please note that this plugin is not using official Gojek Indonesia API. This plugin just estimate the distance matrix using using Google Maps Distance Matrix API and then calculating the cost using the rates defined in the settings.

= Features =

* Automatically split shipping for multiple items into several drivers if the package size exceeded package weight and dimensions limitation.
* Available 2 shipping services: Instant Delivery, Same Day Delivery.
* Set shipping cost per kilometer.
* Set minimum cost that will be billed to customer.
* Set maximum cost that will be billed to customer.
* Set maximum shipping distances that allowed to use the courier.
* Set maximum package weight and dimensions that allowed to use the courier.
* Set shipping origin info by coordinates.
* Set travel mode: Driving, Walking, Bicycling.
* Set route restrictions: Avoid Tolls, Avoid Highways, Avoid Ferries, Avoid Indoor.
* Set visibility distance info to customer.

== Installation ==
= Minimum Requirements =

* WordPress 4.8 or later
* WooCommerce 3.0 or later

= AUTOMATIC INSTALLATION =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t even need to leave your web browser. To do an automatic install of WooGoSend, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type “WooGoSend” and click Search Plugins. You can install it by simply clicking Install Now. After clicking that link you will be asked if you’re sure you want to install the plugin. Click yes and WordPress will automatically complete the installation. After installation has finished, click the ‘activate plugin’ link.

= MANUAL INSTALLATION =

1. Download the plugin zip file to your computer
1. Go to the WordPress admin panel menu Plugins > Add New
1. Choose upload
1. Upload the plugin zip file, the plugin will now be installed
1. After installation has finished, click the ‘activate plugin’ link

== Frequently Asked Questions ==
= How to set the plugin settings? =
You can setup the plugin setting from the Shipping Zones settings. Please check the following video tutorial how to setup the WooCommerce Shipping Zones:

https://www.youtube.com/watch?v=eThWmrnBP38

Credits: [InMotion Hosting](https://www.inmotionhosting.com/)

= Where can I get support? =
You can either create ticket at plugin support forum or GitHub repository:

* [Plugin Support Forum](https://wordpress.org/support/plugin/woogosend)
* [Plugin GitHub Repository](https://github.com/sofyansitorus/WooGoSend)

= Where can I report bugs? =
You can either create ticket at plugin support forum or GitHub repository:

* [Plugin Support Forum](https://wordpress.org/support/plugin/woogosend)
* [Plugin GitHub Repository](https://github.com/sofyansitorus/WooGoSend)

= Where can I request a feature? =
You can either create ticket at plugin support forum or GitHub repository:

* [Plugin Support Forum](https://wordpress.org/support/plugin/woogosend)
* [Plugin GitHub Repository](https://github.com/sofyansitorus/WooGoSend)

== Screenshots ==
1. Settings panel: General Options
2. Settings panel: Instant Delivery Service Options
3. Settings panel: Same Day Delivery Service Options
4. Shipping Calculator preview

== Changelog ==

= 1.1.0 - 2018-01-14 =

* Improvements - Add new settings field to enable or disabled multiple drivers function.

= 1.0.2 - 2018-01-14 =

* Fix - A non-numeric value encountered warning.

= 1.0.1 - 2018-01-10 =

* Improvements - Add new filter hooks: woocommerce_woogosend_shipping_destination_info.
* Improvements - Add new filter hooks: woocommerce_woogosend_shipping_origin_info.

= 1.0.0 - 2018-01-09 =

* Feature - Automatically split shipping for multiple items into several drivers if the package size exceeded package weight and dimensions limitation.
* Feature - Available 2 shipping services: Instant Delivery, Same Day Delivery.
* Feature - Set shipping cost per kilometer.
* Feature - Set minimum cost that will be billed to customer.
* Feature - Set maximum cost that will be billed to customer.
* Feature - Set maximum shipping distances that allowed to use the courier.
* Feature - Set maximum package weight and dimensions that allowed to use the courier.
* Feature - Set shipping origin info by coordinates.
* Feature - Set travel mode: Driving, Walking, Bicycling.
* Feature - Set route restrictions: Avoid Tolls, Avoid Highways, Avoid Ferries, Avoid Indoor.
* Feature - Set visibility distance info to customer.

== Upgrade Notice ==

= 1.1.0 =
This version add some improvements and fixes several critical bugs. Upgrade immediately.