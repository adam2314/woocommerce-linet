=== Linet ERP Integration For Woocommerce ===
Contributors: aribhour
Tags: sync, business, ERP, accounting, woocommerce
Requires at least: 4.6
Tested up to: 6.8
Stable tag: 3.6.3
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

- 2025.05.20 - version 3.6.3 =

- incrase time out

- 2025.05.20 - version 3.6.2 =

- incrase time out

- avoid some escaping pitfulls


- 2025.05.19 - version 3.6.1 =

- minor security fixs

- sync back cats


- 2025.05.13 - version 3.6.0 =

- minor security fix

- revert back for find function



- 2025.03.27 - version 3.5.12 =

- revert sku and linet id find

- better product handler


- 2025.02.25 - version 3.5.11 =

- add option to disable nonce

- 2025.01.14 - version 3.5.10 =

- woo comerce no product id to linet gen item

- 2024.12.25 - version 3.5.9 =

- added send SMS

- 2024.12.18 - version 3.5.8 =

- added nonce support for csrf protction

- 2024.12.03 - version 3.5.7 =

- vari change

- 2024.09.19 - version 3.5.6 =

- sku change

- linet find- 2024.09.2 - version 3.5.5 =

- woocommerce hpos support
- tranzila native plugin support
- var prod error
- sku find
- linet find

- 2024.06.27 - version 3.4.9 =

- item mapper now supports not only eavs!

- 2024.06.18 - version 3.4.8 =

- vatIn,discount price fix v2

- 2024.06.17 - version 3.4.7 =

- vatIn,discount price fix

- 2024.06.16 - version 3.4.6 =

- vatIn price support

- 2024.03.11 - version 3.4.5 =

- add new doc type opt: Quote

- 2024.02.22 - version 3.4.4 =

- bad var name

- 2024.02.21 - version 3.4.3 =

- total discount fallback

- 2024.02.08 - version 3.4.2 =

- percent coupon code support


- 2024.01.29 - version 3.4.1 =

* better error handling
* new logic on manul send doc 2 linet

- 2023.08.23 - version 3.4.0 =

* attribute options sort support
* sync from site stock set
* sync from site gallery pics


- 2023.07,06 - version 3.3.4 =

* singleProdSync improve items


- 2023.07,03 - version 3.3.3 =

* typo in the url
* invoice class run over


= 2023.07.02 - version 3.3.2 =

* rename rect_img to pic opt and added org file

= 2023.06.05 - version 3.3.1 =

* support for image as url

= 2023.05.10 - version 3.3.0 =

* shipping and billing address support

= 2023.03.15 - version 3.2.1 =

* add descrption block flag for item sync

= 2023.02.21 - version 3.2.0 =

* major security update
* improved mutex support
* genral item function

= 2022.09.21 - version 3.1.7 =

* minor mutex attribute sync
* finfo protect

= 2022.08.28 - version 3.1.6 =

* mutex ruler name update
* mutex ruler error handle

= 2022.08.25 - version 3.1.5 =

* mutex imporoved
* backorder custom field
* stockmange no

= 2022.07.13 - version 3.1.4 =

* hide wp->line sync
* mutex urldecode

= 2022.06.23 - version 3.1.3 =

* better delete handler
* product vartion duplcate finder

= 2022.06.23 - version 3.1.2 =

* small mistake big errors

= 2022.06.22 - version 3.1.1 =

* performnce and stablity fixes

= 2022.05.11 - version 3.0.1 =

* timeout bug wp->linet


= 2022.05.11 - version 3.0.0 =

* wp->linet mutex sync support
* single product wp->linet sync button
* better intial sync performnce(time & limit and auto resume)


= 2022.03.29 - version 2.8.11 =

* ship and fee hooks
* improved qty support

= 2022.03.28 - version 2.8.10 =

* remove old zero invoice

= 2022.02.27 - version 2.8.9 =

* new log points
* new hooks

= 2022.02.22 - version 2.8.8 =

* small update
* added button to sync global attributes
* fix duplicate name with woothemes


= 2022.01.24 - version 2.8.7 =

* Fix: double sns item creation protction

= 2022.01.22 - version 2.8.6 =

* MINOR Fix: gobitpaymentgateway wrong payment type map
* MINOR Fix: creditguard wrong payment type map

= 2022.01.22 - version 2.8.5 =

* everybody loves cake

= 2022.01.20 - version 2.8.4 =

* bad send invoice

= 2022.01.19 - version 2.8.3 =

* add refnums fronm bitpay,mesholm-pay

= 2021.12.14 - version 2.8.2 =

* manul send flag
* custemer note to description instead


= 2021.12.08 - version 2.8.1 =

* multipule status support
* better sns

= 2021.10.28 - version 2.7.1 =

* admin table product linet_id
* admin table cat linet_id
* admin table order download link
* mail send on create doc error


= 2021.09.29 - version 2.6.12 =

* go bit index fish

= 2021.09.26 - version 2.6.11 =

* cat sync save old meta

= 2021.09.23 - version 2.6.10 =

* cat sync save old meta

= 2021.09.14 - version 2.6.9 =

* gobitpaymentgateway tranzila_authnr

= 2021.08.12 - version 2.6.8 =

* fast sku and linet_id save to pravent duplicate create in big sites

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
