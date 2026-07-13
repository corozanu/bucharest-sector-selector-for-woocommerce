=== Bucharest Sector Selector for WooCommerce ===
Contributors: catalin28
Tags: woocommerce, bucharest, efactura, checkout, romania
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce
WC requires at least: 7.0
WC tested up to: 9.4

Adds Bucharest sector selection at WooCommerce checkout for e-Factura / SPV ANAF address compatibility.

== Description ==

When customers select Bucharest (București) as their county/state at checkout, this plugin displays a sector dropdown (Sector 1 through Sector 6) and automatically sets the city/locality field to the selected sector value.

This helps ensure order address data matches the mapping required by Romanian e-Factura / SPV ANAF systems:

* State/County: București
* City/Locality: Sector X

**Features**

* Sector dropdown for billing and shipping addresses
* Automatic city/locality sync to the selected sector
* Checkout validation with Romanian error messages
* Order meta storage for sector values
* My Account address editing support
* WooCommerce settings page for configuration
* Debug logging via WooCommerce logs

**Checkout compatibility**

This plugin supports:

* **Classic WooCommerce checkout** (shortcode-based)
* **WooCommerce Checkout Block** (WooCommerce 8.9+ Additional Checkout Fields API)

Phase 1 and Phase 2 Blocks support includes sector selection, conditional visibility for Bucharest, validation, server-side city sync, and admin-configurable hide/readonly city behavior in Checkout Block.

Phase 3 adds ship-to-different-address handling for Blocks, PHPUnit tests, CI, and extended testing documentation.

Phase 4 adds HPOS compatibility, Romanian translations, and WordPress.org submission assets and documentation.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/bucharest-sector-selector-for-woocommerce`, or install the ZIP via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure **WooCommerce** is installed and active.
4. Configure the plugin under **WooCommerce > Settings > eFactura Sector**.

== Frequently Asked Questions ==

= Is this compatible with WooCommerce HPOS (High-Performance Order Storage)? =

Yes. The plugin declares compatibility with WooCommerce custom order tables and stores order data through the `WC_Order` API.

= Does this work with WooCommerce Blocks checkout? =

Yes, starting with version 1.1.0. The plugin uses the WooCommerce Additional Checkout Fields API (WooCommerce 8.9+). Classic checkout and Checkout Block are both supported.

= Does this work with classic checkout? =

Yes. The plugin targets classic WooCommerce checkout and My Account address editing.

= Will billing_city contain the sector value? =

Yes. When a sector is selected, `billing_city` and `shipping_city` are saved as `Sector 1` through `Sector 6`.

= Why is this needed for e-Factura? =

For Bucharest addresses, Romanian e-Factura / SPV ANAF systems expect the county/state to remain București while the city/locality reflects the selected sector.

= Does the plugin add links or branding on the storefront? =

No. The plugin does not add external links, tracking scripts, or branding on the public site.

== Screenshots ==

1. Sector dropdown displayed when Bucharest is selected at checkout.

== Changelog ==

= 1.4.0 =
* Declared WooCommerce HPOS and Checkout Block compatibility.
* Added Romanian (ro_RO) translation files.
* Added WordPress.org submission guide and plugin directory assets.

= 1.3.0 =
* Ship-to-different-address: skip duplicate shipping validation when billing and shipping match.
* Blocks checkout: mirror billing sector/city to shipping when "Use same address" is enabled.
* Added PHPUnit tests, Composer dev setup, GitHub Actions CI, and docs/TESTING.md.

= 1.2.0 =
* Checkout Block UI: sync city from sector via cart store.
* Checkout Block UI: hide/readonly city respects plugin settings per billing/shipping.
* Store API customer sync for city values during Blocks checkout.

= 1.1.0 =
* Added WooCommerce Checkout Block support (Phase 1).
* Registered sector field via Additional Checkout Fields API.
* Synced Blocks sector values to legacy order meta and city fields.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.4.0 =
Adds HPOS compatibility declaration, Romanian translations, and WordPress.org release preparation.

= 1.3.0 =
Improves Blocks checkout when billing and shipping use the same address; adds automated tests and CI.

= 1.2.0 =
Adds Checkout Block city hide/readonly UX and live city sync from sector selection.

= 1.1.0 =
Adds WooCommerce Checkout Block support while keeping classic checkout compatibility.

= 1.0.0 =
Initial release.
