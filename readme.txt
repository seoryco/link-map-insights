=== Link Map Insights ===
Contributors: seoryco
Tags: internal links, seo, link map, visualization, internal linking
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
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
* Link list (table view) of every detected source-to-target link
* Download the detected links as a CSV file

The plugin is fully translatable. The admin interface is available in English and Japanese.

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

= Which links are detected? =

The plugin detects: absolute URLs (`https://...`) in the post content, `href` attributes in the post content (including root-relative URLs starting with `/`, resolved against your site's URL), and the block comment format `<!-- link id="123" -->` used by some editors. Relative paths that do not start with `/` (for example `sub-page/`) are not detected. Links added only through ACF fields, shared theme parts (such as headers/footers), or JavaScript-rendered content are not analyzed, since the plugin only reads the post content stored in the database.

= Can I download the detected links as a CSV file? =

Yes. After clicking "Generate", use the "Download CSV" button on the Link Map screen. The file is generated on the fly and is not stored on the server.

== Changelog ==

= 1.3.0 =
* Added a "Download CSV" button to export the detected links (source/target post ID, title, URL, and post type) without saving anything on the server.
* Added a "Link list" tab with a table view of every detected source-to-target link, as an alternative to the graph view.
* Link detection now also covers `href` attributes, including root-relative URLs (starting with `/`), in addition to the existing absolute URL and block comment detection.

= 1.2.1 =
* Updated the bundled Japanese translation to follow the WordPress Japanese translation style guide (half-width colons, parentheses and question marks).

= 1.2.0 =
* Internationalization: all interface strings are now in English in the source, so the plugin can be translated on translate.wordpress.org.
* Added a bundled Japanese translation (languages/link-map-insights-ja.mo).

= 1.1.0 =
* Prepared for the WordPress.org plugin directory: removed the external update mechanism, bundled vis-network locally, and added readme.txt.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.3.0 =
Adds CSV export and a link list table view. Link detection now also covers relative URLs starting with "/", so results from Generate may change after updating.

= 1.2.1 =
Japanese translation refinements only. No functional changes.

= 1.2.0 =
The interface is now translatable. English and Japanese are included.

= 1.1.0 =
Updates are now delivered through the WordPress.org plugin directory.
