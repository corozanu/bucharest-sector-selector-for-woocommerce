=== Bucharest Sector Selector for WooCommerce ===
Contributors: corozanu
Tags: woocommerce, bucharest, efactura, checkout, romania
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.1.0
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

Phase 1 Blocks support includes sector selection, conditional visibility for Bucharest, validation, and server-side city sync. Hiding or making the city field readonly in the Blocks UI is planned for a later phase.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/bucharest-sector-selector-for-woocommerce`, or install the ZIP via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure **WooCommerce** is installed and active.
4. Configure the plugin under **WooCommerce > Settings > eFactura Sector**.

== Frequently Asked Questions ==

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

= 1.1.0 =
* Added WooCommerce Checkout Block support (Phase 1).
* Registered sector field via Additional Checkout Fields API.
* Synced Blocks sector values to legacy order meta and city fields.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Adds WooCommerce Checkout Block support while keeping classic checkout compatibility.

= 1.0.0 =
Initial release.
