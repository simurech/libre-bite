=== Libre Bite ===
Contributors: simon61
Donate link: https://github.com/simurech/libre-bite
Tags: woocommerce, restaurant, pos, gastronomy, food
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete order and location management system for WooCommerce. Perfect for restaurants, delivery services, and food businesses.

== Description ==

Libre Bite is a powerful WooCommerce extension specifically designed for the gastronomy and food business. It transforms your WooCommerce store into a professional restaurant management system with multi-location support, a kitchen kanban board, and a built-in POS system.

The plugin is modular, allowing you to activate only the features you need. It is fully compatible with WooCommerce HPOS (High-Performance Order Storage) and modern Block Themes.

**Important Note:** This plugin uses Freemius (an external service) to provide optional premium features and professional support. Freemius is a secure platform used by thousands of WordPress plugins. When you opt-in, some information about your site and usage will be shared with Freemius. Premium features require an active subscription.

= Free Features =
*   **Basic Location Management** – Manage a single branch with address and opening hours.
*   **Kanban Dashboard** – Real-time order overview for kitchen staff.
*   **POS / Point of Sale** – Basic interface for walk-in customers.
*   **5-Rappen Rounding** – Native support for Swiss currency rounding.
*   **Product Options** – Basic management for add-ons and extras.

= Pro Features (Requires Subscription) =
*   **Multi-Location Support** – Manage and sync multiple restaurant branches.
*   **Optimized Checkout Flow** – Simplified and conversion-optimized checkout.
*   **Advanced Tip System** – Flexible percentage options and custom amounts.
*   **Pickup Reminders** – Automated email alerts before orders are ready.
*   **Nutritional Info & Allergens** – Full EU-compliant product labeling.
*   **Advanced Sound Notifications** – Custom alerts for new orders.

= High Performance =
Built with performance in mind, Libre Bite supports WooCommerce HPOS and follows modern WordPress coding standards.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/libre-bite` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the settings under the 'Libre Bite' menu in your dashboard.
4. Ensure WooCommerce is installed and active.

== Frequently Asked Questions ==

= Does it require WooCommerce? =
Yes, Libre Bite is an extension for WooCommerce and requires it to be installed and active.

= Is it compatible with HPOS? =
Yes, Libre Bite is fully compatible with WooCommerce High-Performance Order Storage (HPOS).

= Can I manage multiple locations? =
Yes, the location management module allows you to create and manage multiple restaurant branches.

== Screenshots ==

1. The Kanban Dashboard for kitchen management.
2. POS interface for on-site orders.
3. Feature Toggles to customize your setup.

== Changelog ==

= 1.0.6 =
* Fix: Premium-Override nur noch in Premium-Version verfuegbar (wp.org Compliance).
* Fix: Naehrwertangaben, Allergene und Sound-Benachrichtigungen als Premium markiert.
* Fix: readme.txt Feature-Liste an Free/Pro-Zuordnung angepasst.
* Fix: Plugin-Beschreibung auf Englisch uebersetzt (wp.org Richtlinie).
* Fix: Output Escaping in Admin-Notices ergaenzt.
* Bump: Version auf 1.0.6.

= 1.0.5 =
* Fix: Heredoc/Nowdoc-Syntax durch externe JS-Dateien ersetzt (WPCS-Konformitaet).

= 1.0.4 =
* Fix: Prefix von lb_ auf lbite_ geaendert (WordPress.org Namenskonvention).
* Fix: Inline CSS/JS aus Templates in separate Dateien und wp_enqueue verschoben.
* Fix: Nonce-Checks in Checkout und Product-Options ergaenzt.
* Fix: Freemius is_org_compliant Parameter hinzugefuegt.
* Fix: SortableJS auf Version 1.15.7 aktualisiert.
* Fix: Contributors in readme.txt korrigiert.

= 1.0.3 =
* Fix: Behebe alle WordPress.org Plugin Check Fehler und Warnungen.
* Fix: Direkter Dateizugriff-Schutz in Hauptdatei korrigiert.
* Fix: Output Escaping bei wp_die() korrigiert.
* Fix: strip_tags() durch wp_strip_all_tags() ersetzt.
* Fix: PHPCS-Ignore-Kommentare für legitime Verwendungen hinzugefügt.
* Fix: readme.txt Tags auf maximal 5 reduziert.

= 1.0.2 =
* Fix: Addressed WordPress.org Plugin Check errors (Tested up to tag, Textdomain loading).
* Fix: Removed Domain Path header.

= 1.0.1 =
* Improved: Freemius integration for better WordPress.org compliance.
* Fixed: Uninstall process to allow feedback tracking.
* Security: Enhanced security checks and escaping.

= 1.0.0-beta =
* Initial beta release.
* Added Location Management.
* Added POS System.
* Added Kanban Order Board.
* Added Tip System and Scheduled Orders.
* Added Freemius integration for Pro features.

== Upgrade Notice ==

= 1.0.0-beta =
This is the initial beta version. Enjoy all features for free during the beta phase.
