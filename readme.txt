=== Link Map Insights ===
Contributors: seoryco
Tags: internal links, seo, link map, visualization, internal linking
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Visualize your site's internal link structure in one click and find pages with no inbound or outbound links.

== Description ==

Link Map Insights is an admin-side plugin that visualizes the internal link structure of your site in a single click. It analyzes your published posts, pages, and custom post types, then renders the internal links between them as an interactive network graph.

It also highlights isolated content by listing pages that have no inbound links and pages that have no outbound links, helping you improve your internal linking and discover orphaned pages.

Main features:

* Generate an internal link map with one click
* Filter by post type and taxonomy (categories, tags, etc.)
* Node search, zoom in/out, and shortened title display
* List of pages with no inbound links
* List of pages with no outbound links

The plugin admin interface is provided in Japanese.

== External services ==

This plugin does not send any data to external services. All analysis is performed locally within your WordPress installation.

== Third-party libraries ==

This plugin bundles vis-network 9.1.13 for graph visualization, distributed under the MIT / Apache-2.0 license.

* Project / documentation: https://visjs.github.io/vis-network/
* Full source code and build tools (non-minified): https://github.com/visjs/vis-network
* The bundled file js/lib/vis-network.min.js is the official, unmodified distribution build of version 9.1.13.

The rest of the plugin source (PHP, CSS, and js/link-map-insights-admin.js) is included in the plugin in its original, human-readable form and is not compiled or minified.

== Installation ==

1. Upload the plugin and activate it.
2. Open the "Link Map" menu in the WordPress admin sidebar.
3. Click the "Generate" button to display the internal link map.

== Frequently Asked Questions ==

= Which post types are analyzed? =

All post types registered as public are analyzed (attachments are excluded). You can narrow down the displayed types using the checkboxes on the screen.

= Where are the analysis results stored? =

Results are stored locally on your site as a transient. Nothing is ever sent to external services.

== Changelog ==

= 1.1.0 =
* Prepared for the WordPress.org plugin directory: removed the external update mechanism, bundled vis-network locally, and added readme.txt.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Updates are now delivered through the WordPress.org plugin directory.
