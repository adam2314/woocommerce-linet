=== Linet ERP-Woocommerce Integration Plugin ===
Contributors: aribhour
Tags: sync, business, ERP, accounting, woocommerce, Linet
Requires at least: 4.6
Tested up to: 5.7
Stable tag: 5.7
License: GPLv2 or later
Requires PHP: 5.2
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

= 2021.08.05 - version 2.6.7 =

* beter image fix  (bad post_id on first fire!)

= 2021.07.22 - version 2.6.6 =

* elmntor fix

= 2021.07.22 - version 2.6.5 =

* SNS ignored exclude bug fix

= 2021.06.10 - version 2.6.4 =

* Rectangular Picture sync

= 2021.06.07 - version 2.6.3 =

* small back sync bug

= 2021.06.01 - version 2.6.2 =

* cat sync improv

= 2021.05.31 - version 2.6.1 =

* added mapper support for elmntor

= 2021.05.26 - version 2.6.0 =

* orde status back sync

= 2021.05.18 - version 2.5.0 =

* elemntor form integrtion
* cf7 integrtion
* adding ext to pic

= 2021.04.20 - version 2.2.3 =

* support till 50 variations
* improve heb slug

= 2021.04.13 - version 2.2.1 =

* global attibute support hook
* better fee and shipping handle (neagtive sum)

= 2021.04.12 - version 2.2.0 =

* global attibute support

= 2021.03.18 - version 2.1.10 =

* back sync bug


= 2021.03.16 - version 2.1.8 =

* warehouse exclude list
* metadata block


= 2021.02.10 - version 2.1.6 =

* creditguard meta support

= 2021.02.04 - version 2.1.5 =

* better custom fields sync

= 2021.02.03 - version 2.1.4 =

* aa

= 2021.01.20 - version 2.1.3 =

* stringfy name and description

= 2021.01.19 - version 2.1.1 =

* small fix in singleProdSync

= 2021.01.14 - version 2.1.0 =

* change update/create using wc internal product api
* maintenance area: handle logs
* maintenance area: handle duplicate linet_id,sku
* maintenance area: handle missing meta data in attachmenet


= 2021.01.05 - version 2.0.3 =

* imporoved cat slug behviar

= 2020.12.28 - version 2.0.0 =

* sns update GA


= 2020.12.17 - version 1.7.7 =

* new filter woocommerce_linet_update_post_meta
* adding create folder
* wp_update_attachment_metadata



== Upgrade Notice ==

= 1.0 =
* we can update the photo gallery!
