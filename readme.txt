=== Linet ERP-Woocommerce Integration Plugin ===
Contributors: ari@speedcomp.co.il 
Tags: sync, business, ERP, accounting, woocommerce, Linet
Requires at least: 4.6
Tested up to: 4.7
Stable tag: 4.6
License: GPLv2 or later
Donate link: http://www.linet.org.il
License URI: https://www.gnu.org/licenses/gpl-2.0.html

After installing this plugin you can sync woocommerce with Linet ERP.

== Description ==

This Plugin enables integration and sync between Linet ERP & woocommerce through Linet ERP API. The integration/sync includes:

1. Connect woocommerce (Login) to API of Linet ERP at https://app.linet.org.il with special unique identifiers as follows: 
	a. User unique ID
	b. API Key
	c. Company ID
2. Automatically creates sales documents at Linet ERP upon order complition in Woocommerce estore. The auto created documents are:
	a. Invoice-receipt or Invoice (configurable through plugin settings), sent automaticaly by email to the client.
	b. Sales order for company internal use. 
3. Update Linet ERP client list with new clients created at Woocommerce.
4. Update Woocommerce category list with new item category created at Linet ERP.
5. Update Woocommerce items list with new items created at Linet ERP.
6. Decrease item inventory in Linet ERP upon completed order of specific item unit/s purchased at Woocommerce estore.
7. Update items inventory from Linet ERP to Woocommerce estore every round hour.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->WooCommerce->Linet for entering the credentials to use to connect woocommerce to your specific tennant, company and warehouse at Linet ERP and customizing the sync properties.

== Frequently Asked Questions ==

= No Questions asked =

No answer to that question.

== Screenshots ==

1. No screenshots attached

== Changelog ==

= 1.0 =
* This is the first version. No changes since.


== Upgrade Notice ==

= 1.0 =
No upgrade needed.

