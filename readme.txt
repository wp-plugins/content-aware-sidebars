=== Plugin Name ===
Contributors: intoxstudio
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=KPZHE6A72LEN4&lc=US&item_name=WordPress%20Plugin%3a%20Content%20Aware%20Sidebars&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: sidebar, widget area, content aware, context aware, seo, dynamic, flexible, modular, bbpress, buddypress, qtranslate, polylang, transposh, wpml, woocommerce
Requires at least: 3.3
Tested up to: 3.8
Stable tag: 2.0.3
License: GPL2

Create and display sidebars according to the content being viewed.

== Description ==

Manage an infinite number of sidebars. Make your WordPress site even more dynamic and boost SEO by controlling what content your sidebars should be displayed with. Creating flexible, dynamic sidebars has never been easier, and no code is needed at all as everything is easily done in the administration panel.
No extra database tables or table columns will be added.

> **New in version 2**
>
> Condition groups let you display a sidebar together with both associated and distinct content.
>
> Improved GUI makes it even easier to select content and edit sidebars.
>
> Improved API for developers who want to extend and manipulate content support.

= Features =

* Easy-to-use sidebar manager
* Display sidebars with all or specific:
	* Singulars - e.g. some posts or pages
	* (Custom) Post Types
	* Singulars with given (custom) taxonomies or taxonomy terms
	* Singulars by a given author
	* Page Templates
	* Post Type Archives
	* Author Archives
	* (Custom) Taxonomy Archives or Taxonomy Term Archives
	* Search results
	* 404 Page
	* Front Page
	* bbPress User Profiles
	* BuddyPress Member Pages
	* Languages (qTranslate, Polylang, Transposh, WPML)
	* **Any combination of the above**
* Merge new sidebars with others, replace others or simply add them to your theme manually with a template tag
* Create complex content with nested sidebars
* Private sidebars only for members
* Schedule sidebars for later publishing

= Builtin Plugin Support =

* bbPress (v2.0.2+)
* BuddyPress (v1.6.2+)
* qTranslate (v2.5.29+)
* Polylang (v0.9.6+)
* Transposh Translation Filter (v0.8.2+)
* WPML Multilingual Blog/CMS (v2.4.3+)

= Translations =

* Danish (da_DK): [Joachim Jensen](http://www.intox.dk/)
* German (de_DE): Enno Wulff
* Hungarian (hu_HU): Kis Lukács
* Italian (it_IT): [Luciano Del Fico](http://www.myweb2.it/)
* Latvian (lv_LV): [Haralds Gribusts](http://www.fireclubllatvia.lv/)
* Lithuanian (lt_LT): [Vincent G](http://host1free.com/)
* Slovak (sk_SK): [Branco](http://webhostinggeeks.com/)
* Spanish (es_ES): [Analia Jensen](http://www.linkedin.com/in/analiajensen)

Do you want to see your name here?

If you have translated the plugin into your language or updated an existing translation, please send the .po and .mo files to jv[at]intox.dk.
Download the latest [template .po file](http://plugins.svn.wordpress.org/content-aware-sidebars/trunk/lang/content-aware-sidebars.po) or the [.po file in your language](http://plugins.svn.wordpress.org/content-aware-sidebars/trunk/lang/).

= Contact =

www.intox.dk

== Installation ==

1. Upload the full plugin directory to your `/wp-content/plugins/` directory or install the plugin through `Plugins` in the administration 
1. Activate the plugin through `Plugins` in the administration
1. Have fun creating your first sidebar
1. Optional: Insert `<?php display_ca_sidebar( $args ); ?>` in a template if you have a special spot for the new, manual handled, sidebars.

== Frequently Asked Questions ==

If you have any questions not answered here, head over to the [Support Forum](http://wordpress.org/tags/content-aware-sidebars?forum_id=10).

= Will Content Aware Sidebars work with my theme? =

Yes.

If the theme supports dynamic widget areas/sidebars, new sidebars can be created to replace or merge with those under certain conditions.
If not, it is still possible to create sidebars and then use the function `display_ca_sidebar()` in the theme.

= Will Content Aware Sidebars work with/support my plugin? =

Work with? Yes.

Support? Most likely.

If the plugin uses public Custom Post Types or Custom Taxonomies, these will automatically be supported. Additionally, Content Aware Sidebars uses modules with WordPress Hooks that you can control and it features builtin support for some of the most popular plugins in the WordPress Repository.

Content Aware Sidebars will never alter data used by your plugin.

= My new sidebar is not displayed where I expect it to? =

As of version 2.0, all types of content added to a condition group will be recognized as associated. The introduction of these groups has made it possible to create extremely focused and at the same time distinct conditions for when to display a sidebar.

You can have one group with the conditions "All posts containing tag X" and another group with "All pages written by author Y". The sidebar will then be displayed with all posts containing tag X **or** all pages authored by Y.
The conditions "All posts containing tag X and written by author Y" in one group are possible as well.

The downside to this is that, currently, Content Aware Sidebars does not validate that the content you add to a group actually is associated. Creating a group with the conditions "All posts and Search Results" is possible, but it will never be satisfied because the types of content (post type and static page) are incompatible.

An empty condition group will cause the sidebar to be displayed with all content.

Also note the exposure setting as it determines whether the selected content apply to archives, singulars or both.

= All content items are not listed in the sidebar editor? =

As of version 2.0, several types of content have pagination in the sidebar editor. Items can also by found with a search function.

For post types, only public, private and scheduled items are displayed.

= How do I use display_ca_sidebar( $args )? =

This function is optional and handles all sidebars that are set to be handled manually. It can be inserted anywhere on your site in any quantity, either as it is, or with the following parameters:

`$args = array(
'include' => '',
'before' => '<div id="sidebar" class="widget-area"><ul class="xoxo">',
'after' => '</ul></div>'
);`

If ID's of specific sidebars are passed to `include`, the function will only handle these. The visuals of the content aware sidebars can be modified by passing `before` and `after`.
The function accepts URL-style strings as parameters too, like the standard WordPress Template Tags.

= What are the minimum requirements? =

* WordPress 3.3
* PHP 5.2.4
* MySQL 5

== Screenshots ==

1. Add a new Content Aware Sidebar to be displayed with all Posts that contains the category Very Categorized. It replaces `Primary Sidebar`
2. Simple overview of all created Content Aware Sidebars
3. Add widgets to the newly added sidebar
4. Viewing front page of site. `Primary Sidebar` is displayed
5. Viewing a Post that contains Very Categorized. `Very Categorized Posts` sidebar has replaced `Primary Sidebar`

== Changelog ==

= 2.0.3 =

* Fixed: taxonomy pagination in sidebar editor
* Fixed: categories found in search can now be saved. props Xandoc

= 2.0.2 =

* Fixed: terms caused a sidebar to be displayed on all pages

= 2.0.1 =

* Fixed: admin menu would in some cases be overwritten by other plugins

= 2.0 =

* Added: condition groups
* Added: gui and uxd overhaul for sidebar editor
* Added: pagination for taxonomies in sidebar editor
* Added: pagination for post types in sidebar editor
* Added: mysql 5.6+ compatibility
* Added: more efficient uninstall process
* Added: easier for developers to extend and manipulate content support
* Added: wp3.8 and mp6 compatibility
* Added: german translation
* Added: hungarian translation
* Added: latvian translation
* Added: spanish translation
* Added: all conditions follow a strict logical "and" operator per group
* Fixed: scripts and styles only loaded on sidebar administrative pages
* Fixed: slovak translation now recognized
* Fixed: paths to assets compatible with ssl
* Removed: jquery ui autocomplete and accordion

= 1.3.5 =

* Fixed: menu would disappear in rare cases. Props grezvany13
* Fixed: search function now searches in title and slug (not content) for post types
* Added: search function displays at most 20 results instead of 10

= 1.3.4 =

* Fixed: cas_walker_checklist now follows walker declaration for wp3.6
* Fixed: content list in accordion now not scrollable
* Fixed: only terms from public taxonomies are included for content recognition.
* Fixed: polylang fully supported again
* Fixed: consistent css across wp versions
* Removed: flushing rewrite rules on activation/deactivation is needless

= 1.3.3 =

* Added: html placeholder in search field
* Added: items already displayed in edit page moved to top and checked when found in search
* Fixed: private and scheduled singulars included in search results
* Fixed: search results displayed in ascending order

= 1.3.2 =

* Added: items found in search now added to list directly on select
* Fixed: some terms found by search could not be saved
* Fixed: widget locations are saved again for each theme

= 1.3.1 =

* Added: authors and bbpress user profiles now searchable on edit page
* Added: items found in search on edit page are prepended and checked by default
* Added: updated edit page gui
* Added: search field only visible when quantity is above 20
* Fixed: select all checkbox will now disable all input in container
* Fixed: host sidebar could sometimes not be found in sidebar list

= 1.3 =

* Added: post type posts and taxonomy terms now searchable on edit page
* Added: sidebar handle and host shown on widgets page
* Added: slovak translation
* Fixed: sidebar meta boxes more robust to external modifications
* Fixed: admin column headers more robust to external modifications
* Fixed: sidebar menu now always hidden for users without right cap
* Fixed: code optimization and refactor for performance
* Removed: support for sidebar excerpt

= 1.2 =

* Added: polylang support
* Added: buddypress support
* Added: managing sidebars now requires edit_theme_options cap
* Added: bbpress user profile has own rules instead of author rules
* Added: filter for content recognition
* Added: auto-select new children of selected taxonomy or post type ancestor

= 1.1.2 =

* Added: wordpress 3.5 compatibility 
* Fixed: slight css changes on edit screen
* Fixed: "show with all" checkbox toggles other checkboxes correctly

= 1.1.1 =

* Fixed: slight css changes on edit screen
* Fixed: tick.png included
* Fixed: taxonomy terms could influence each other in rare cases
* Fixed: taxonomy wide rules for taxonomy archives
* Fixed: cache caused db update module to skip 1.1 update if going from 0

= 1.1 =

* Added: improved gui on edit screen including content accordion 
* Added: bbpress forum-topic dependency
* Added: sidebars hidden on password protected content
* Added: relevant usermeta cleared on plugin deletion
* Fixed: performance gain by dropping serialized metadata
* Fixed: database data update module revised
* Fixed: css class in posts and terms walker
* Fixed: limit of max 200 of each content type on edit screen (temp)
* Fixed: style and scripts loaded properly
* Removed: individual content meta boxes on edit screen

= 1.0 =

* Added: plugin rewritten to flexible modular system
* Added: builtin support for bbpress, qtranslate, transposh, wpml
* Added: lithuanian translation
* Fixed: all present rules now dependent of each other
* Fixed: sidebar update messages
* Fixed: specific hooks now not sitewide
* Fixed: better use of meta cache
* Fixed: dir structure
* Fixed: unexpected output notice on plugin activation

= 0.8.3 =

* Added: danish and italian translation
* Fixed: sidebar query might be larger than max_join_size
* Fixed: row content in admin overview would be loaded with post types with matching keys

= 0.8.2 =

* Fixed: new rules caused issues with post types with taxonomies

= 0.8.1 =

* Fixed: several checks for proper widget and sidebar removal

= 0.8 =

* Added: some rules are dependent of each other if present
* Added: widgets in removed sidebars will be removed too
* Added: database data update module
* Added: rewrite rules flushed on plugin deactivation
* Added: data will be removed when plugin is uninstalled
* Added: icon-32 is back
* Added: message if a host is not available in sidebar overview
* Fixed: prefixed data
* Fixed: data hidden from custom fields
* Fixed: manage widgets link removed from trashed sidebars
* Fixed: view sidebar link removed in wp3.1.x
* Fixed: all custom taxonomies could not be removed again when assigned to sidebar
* Fixed: altered options meta box on edit screen
* Fixed: check if host of sidebar exists before handling it

= 0.7 =

* Added: sidebars will be displayed even if empty (i.e. hidden)
* Added: author rules on singulars and archives
* Added: page template rules
* Added: javascript handling for disabling/enabling specific input on editor page
* Fixed: minor tweak for full compatibility with wp3.3
* Fixed: function for meta boxes is called only on editor page
* Fixed: proper column sorting in administration
* Fixed: specific post type label not supported in wp3.1.x
* Fixed: type (array) not supported as post_status in get_posts() in wp3.1.x
* Fixed: code cleanup

= 0.6.3 =

* Added: scheduled and private singulars are selectable in sidebar editor
* Added: combined cache for manual and automatically handled sidebars
* Added: display_ca_sidebar accepts specific ids to be included
* Fixed: only a limited amount of sidebars were present in widgets area
* Fixed: better caching in sidebar editor
* Fixed: page list in sidebar editor could behave incorrectly if some pages were static

= 0.6.2 =

* Fixed: array_flip triggered type mismatch errors in some cases

= 0.6.1 =

* Fixed: an image caused headers already sent error

= 0.6 =

* Added: sidebars can be set with specific singulars
* Added: sidebars can be set with specific post formats
* Added: updated gui
* Fixed: draft sidebars save meta

= 0.5 =

* Added: search, 404, front page rules now supported
* Fixed: custom tax and terms are now supported properly (again)

= 0.4 =

* Added: post type archives, taxonomy archives and taxonomy terms archives now supported
* Added: taxonomy rules
* Added: removable donation button
* Fixed: faster!

= 0.3 =

* Added: sidebars can now be private
* Fixed: taxonomy terms are now supported by template function
* Fixed: faster rule recognition and handling
* Fixed: custom taxonomies are now supported properly
* Fixed: error if several sidebars had taxonomy terms rules

= 0.2 =

* Added: taxonomy terms rules
* Added: optional description for sidebars
* Added: display_ca_sidebar also accepts URL-style string as parameter
* Fixed: saving meta now only kicks in with sidebar types
* Fixed: archives are not singulars and will not be treated like them

= 0.1 =

* First stable release

== Upgrade Notice ==

= 2.0 = 

* Content Aware Sidebars data in your database will be updated automatically. It is highly recommended to backup this data before updating the plugin.
* Minimum WordPress version compatibility is now 3.3.

= 1.1 =

* Content Aware Sidebars data in your database will be updated automatically. Remember to backup this data before updating the plugin.

= 0.8 =

* Content Aware Sidebars data in your database will be updated automatically. Remember to backup this data before updating the plugin.

= 0.5 =

* Note that the plugin now requires at least WordPress 3.1 because of post type archives.

= 0.4 =

* All current custom sidebars have to be updated after plugin upgrade due to the new archive rules

= 0.1 =

* Hello World
 