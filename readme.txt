=== Linet ERP Integration For Woocommerce ===
Contributors: aribhour
Tags: sync, business, ERP, accounting, woocommerce
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 3.6.31
License: GPLv2 or later
Requires PHP: 7.4
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

= 2026.10.01 - version 3.6.31 =

* Fix: a lookup by sku, and the check on the Linet id a product carries, now say isProduct out loud so Linet does not answer them out of the products alone. One cell of a matrix built in Linet is an item with isProduct 0, which a newsearch leaves out unless it is asked not to, so a variation that was already in Linet read as missing - and the push went on to create it, which Linet refused because the sku was taken. A document line matched by sku finds a cell of a matrix for the same reason
* Updated: a lookup by sku asks about both states of active at once, so a switched off item holding the sku is found by the one call. It used to take a second call to see those, and only after Linet had already refused to create the item; the sku is unique across the whole items table, switched off rows included, so the first call is the one that has to see them
* Fix: a lookup by sku reads a handful of rows rather than one. Linet matches a sku with a LIKE, newest first, so a sku that reads as part of a longer one was answered with that other item alone - and the item actually asked for was never in the answer to be picked out of it

= 2026.09.30 - version 3.6.30 =

* New: an option that leaves every product, variation and category that already carries a Linet id out of a WC->Linet run, so a run sends only what has never been pushed. A catalogue that has been pushed once is mostly items Linet already knows, and each of them still cost the run a lookup and an update. Off by default, and a product pushed by hand or on save still goes up whatever its id says
* New: a picture over 2 MB goes up as the largest of the sizes WordPress made for it that fits under the limit, rather than as the original. The file is sent base64 encoded inside the create/file body, half as long again for being encoded, which is what turned one heavy picture into a request Linet refused or never answered. A picture with no version small enough is left out and said so in the log. The limit can be changed, or turned off, with the woocommerce_linet_pic_max_bytes filter
* New: the J5 fields of a document carry the card token and the approval number of the hold for PayPlus orders, which is what a held J5 is charged against - the same pair a zcredit order already sent. An order with no token sends the transaction uid as before
* Updated: the picture itself is left out of the log. One create/file wrote the whole file into the debug log, base64 and all, so a run over a catalogue of pictures wrote a log of mostly pictures; the line now says how big the file was instead
* Updated: the plugin asks for php 7.4 rather than 8.0. Nothing in it needs 8.0 - the newest thing the code uses is from 7.0 - and WooCommerce itself asks for no more than 7.4, so the higher number was only keeping the plugin off sites it runs on perfectly well, and off the update WordPress would otherwise offer them. Tested against php 8.4, WordPress 7.0 and WooCommerce 10.9
* Fix: a call that died mid request - an empty reply, a connection reset, a refused connection, a name that will not resolve - counts as Linet not answering, the same as a call that ran out of time already did. A create/file that Linet fell over on read as an ordinary failure, so the run went on to ask the same dead server once per product for the rest of the catalogue

= 2026.09.21 - version 3.6.29 =

* New: three calls in a row that never come back stop the run and say so, instead of walking the rest of the catalogue a timeout at a time. When Linet stopped answering, one product could spend a minute and a half on three calls that were never going to arrive, and the run went on knocking for as long as the page was open. Calls that time out now and again, with answers in between, are not enough to stop anything
* New: a call that goes unanswered holds every process back before the next one is sent - ten seconds, then twenty, then thirty - the same as a refusal for going over the rate limit already does. A call that never came back is the plainest word Linet has for "not now", and the plugin was answering it by asking again straight away
* New: the hold lets go by itself once its wait is up, and the next call through decides whether Linet is back. A sync that gave up in the afternoon can never leave an order that evening with its document refused unasked
* Updated: every item and file lookup now goes through newsearch, which is where Linet documents filtering, rather than search/item and search/file. A lookup by sku or by Linet id asks for a single row, because that is all there is to find; the count of items in a category still reads the category, because a row count is the answer it is after
* Updated: asking whether a picture is already on an item asks for a single row, and reading an item's gallery asks for at most 50 files, instead of reading every file the item has gathered. The number can be changed with the woocommerce_linet_gallery_limit filter
* Fix: a picture is created when the answer carries no rows as well as when Linet says so with errorCode 1000, so a picture that is not in Linet yet is still sent whichever way the answer is worded A sku is unique in Linet's items table, so one row is all there is to find, and this is the call the sync makes most - once per product on a push, and again for every line of a document when items are matched by sku
* Fix: a document whose sku lookup went unanswered no longer stops with a php error from reading the answer that never came
* Fix: the check for whether the Linet id noted on a product is still there now reads an empty answer as "not there" as well as the not-found reply search/item sends, so an id that points at a deleted item is let go of rather than updated. A lookup that went unanswered is neither, and is left alone as before
* Updated: a call is given 15 seconds to answer rather than 30. A healthy call comes back in well under a second, so thirty was a long time to hold a php process open for an answer that was not coming

= 2026.09.21 - version 3.6.28 =

* Fix: a sync pulse that has not come back is now given up on after five minutes rather than one, and is asked for again at most three times before the run is stopped. A minute was under what a pulse honestly takes once Linet starts holding calls back, so the page was dropping pulses that were still working and asking for the same products over again - and the pulse it dropped carried on talking to Linet at the site's end. The count of retries was already being kept, but nothing was reading it, so this could go on for as long as the page was left open
* Fix: only one pulse of a Linet->WooCommerce pull runs at a time. The pull had no lock of its own, so every pulse the page gave up on left another reader behind it, and the number of them talking to Linet at once grew for as long as the sync was left running. The category half of a push is under the same lock as the items
* Fix: a pulse turned away because an earlier one is still running now waits longer each time instead of asking every five seconds for ever, and gives up after twenty tries
* Fix: a pull whose call to Linet went unanswered - a network error, a rate limit that outlived its retries, or an error page where the answer was meant to be - now stops the run and says so. An answer that was not there reads exactly like an answer with nothing left in it, so the sync was ending early and reporting that it had finished

= 2026.09.21 - version 3.6.27 =

* New: a variation is placed in its Linet matrix by the ruler unit it sits on, sent on the item itself, instead of by numbers packed into its SKU, and goes up as a matrix cell (item type 4). A variation that went up under the old numbered SKU is found and renamed rather than created a second time beside itself
* New: a variation keeps its own SKU in Linet, and one that has no SKU of its own is given one rather than taking the parent's. A variable product's SKU is no longer rewritten in WooCommerce to take the dashes out of it
* New: a variable product can carry more than two attributes in both directions
* Fix: a variable product coming back from Linet with more than one ruler now carries an attribute for each of them; every ruler after the first was writing over the one before it, leaving the product with a single attribute
* Fix: a ruler that could not be mapped no longer takes up an axis of the matrix, which was leaving a gap in the order the rulers sit in
* Fix: the code a ruler unit is filed under in Linet is sent in Code 39, which is all Linet accepts there. A Hebrew attribute value was refused outright ("Only Code39 characters are allowed") and the unit was never created, so no variation could be placed on its ruler. The unit's name and slug are unchanged, so it still reads as the word itself in Linet; only the code falls back, to the WooCommerce term ID
* Fix: a variation whose parent product has not reached Linet yet sends a parent item of 0 rather than an empty value

= 2026.09.20 - version 3.6.26 =

* Fix: pushing a product whose SKU is already on a Linet item no longer ends the push with a database error from Linet ("Duplicate entry ... for key 'sku'"); the existing item is found and updated instead, including an item that was switched off in Linet, which is looked up with newsearch/item and active 0 and comes back on as part of the update
* Fix: only one pulse of a WooCommerce to Linet push runs at a time, so a pulse that is slow because of Linet's rate limit and gets asked again by the page can no longer create the same item twice
* Fix: a product is no longer created in Linet when the search for its SKU went unanswered (timeout, or the rate limit ran out); it is left for the next run
* Fix: stripping the dashes out of a variable product's SKU is skipped when another product already uses the stripped SKU, instead of stopping the push with a WooCommerce error
* New: the Maintenance tab lists how many products and categories carry a Linet ID and clears the lot with one button, for IDs that point at items from somewhere other than the connected company. Nothing is deleted in WooCommerce or in Linet, only the link, and the next sync maps everything again, items by SKU and categories by name

= 2026.09.18 - version 3.6.25 =

* Fix: 3.6.24 was released without the rate limiter and sync cache classes, causing "Class not found" errors on API calls, the settings page and inventory sync

= 2026.09.17 - version 3.6.24 =

* Fix: account name, phone, address and city are cut to the lengths Linet accepts before the account is created or updated, so a long company name or phone no longer makes Linet reject the account (also for accounts created from Elementor and Contact Form 7 forms)
* Fix: document company, address, city, phone and the billing and shipping address fields are cut to Linet's limits, so a long value no longer stops the document from being created
* Fix: document line names (products, variations, coupons, shipping and fees) are cut to 255 characters, and a line with an empty name is sent as "Item" instead of being rejected by Linet

= 2026.08.25 - version 3.6.23 =

* Fix: the per-product sync actions now check the admin nonce, so a third-party page can no longer drive a logged-in administrator's browser into syncing a product
* Updated: when Sync Items is set to Off, the "Sync Item From Linet" button is hidden and the request is refused, instead of pulling from Linet anyway

= 2026.08.19 - version 3.6.22 =

* New: the Maintenance tab now lists every product behind a problem, each one linked to its WooCommerce edit screen and to the shop
* New: duplicate rows show which product is kept and which are the duplicates, with a delete link per product next to the "delete all duplicates" button
* New: a duplicate Linet ID can now be cleared from the extra products instead of deleting them
* New: images without sizes list the products that use them, and the rebuild action now uses the file WordPress has on record
* New: maintenance actions ask for confirmation and report what happened, instead of silently hiding the row
* Fix: duplicate detection ignores empty SKUs, trashed products and postmeta rows left behind by deleted posts, so the tab no longer reports duplicates that do not exist
* Fix: duplicate variations are matched on their real attribute values rather than on the attribute summary text
* Fix: clearing a duplicate Linet ID no longer removes it from the product that keeps it
* Fix: a failed search/file or create/file call during WC->Linet sync no longer crashes with "Attempt to assign property body on null" - the image is skipped, logged, and the sync batch carries on
* Fix: the log no longer shows percent-encoded slugs - "%d7%9e%d7%99%d7%93%d7%94" is written as the Hebrew it stands for, while a %20 inside a logged url is left alone
* Fix: requests and answers are logged as readable text instead of \u05de escapes
* Fix: downloading a log file returns it as it was written, instead of html-escaping every quote to &quot;
* Fix: maintenance actions now require the manage_woocommerce capability
* Updated: each section lists at most 100 groups and says so, instead of building an unbounded page

= 2026.08.16 - version 3.6.21 =

* New: WC->Linet sync now pushes all product categories in a first phase, before any item is synced
* New: category hierarchy is now sent to Linet - parent_id set from the parent term's Linet category id
* Fix: a category already mapped to Linet is now updated on sync, instead of only being checked for existence
* Fix: Linet->WC categories are now synced parents first, so a child no longer lands at top level when it arrives before its parent
* Fix: a category moved to top level in Linet is now flattened in WooCommerce as well
* Updated: WC->Linet item sync no longer re-resolves categories through the API for every product


= 2026.06.25 - version 3.6.20 =

* Upgrade: new paypal gateway support


= 2026.06.25 - version 3.6.19 =

* Fix: variable product type not converting from simple on sync - wp_set_object_terms now runs after wp_update_post


= 2026.05.13 - version 3.6.18 =

* Fix: variable product type not converting from simple on sync - wp_set_object_terms now runs after wp_update_post
* Fix: product found by SKU not updating - post_id now correctly set from SKU lookup
* Fix: stock update now clears WooCommerce transient cache and WordPress object cache
* Updated: WordPress compatibility 6.9.4, WooCommerce compatibility 10.7.0
* Updated: minimum requirements PHP 8.0, WordPress 6.0

- 2026.03.17 - version 3.6.17 =
	- wp_slash in set_name

- 2026.03.09 - version 3.6.16 =
	- added new config opt ignore_supported_gateways


- 2025.09.10 - version 3.6.15 =
	- undiefed var
  

- 2025.09.08 - version 3.6.13 =
	- first name to last name update
  

- 2025.08.25 - version 3.6.12 =

- cat linet_id
- prod linet_id


- 2025.07.30 - version 3.6.11 =

- new switch to presevere old attributes

- 2025.05.27 - version 3.6.10 =

- new array validator and better plugin headers

- 2025.05.21 - version 3.6.9 =

- sync back last one for 2day

- 2025.05.21 - version 3.6.8 =

- sync back

- 2025.05.21 - version 3.6.7 =

- Cannot redeclare getRequestHeaders

- 2025.05.21 - version 3.6.6 =

- sync to linet with subs

- 2025.05.21 - version 3.6.5 =

- imporoved settings sections to restore all privuos functionlty

- 2025.05.21 - version 3.6.4 =

- change get_image_extension_from_mime

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
