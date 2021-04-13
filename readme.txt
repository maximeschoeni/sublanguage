=== Sublanguage ===
Contributors: maximeschoeni
Tags: multilanguage, multilingual, language, translation
Requires at least: 4.5
Tested up to: 5.7
Stable tag: 2.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sublanguage is a lightweight multilanguage plugin for wordpress.

== Description ==

Sublanguage is a multilanguage plugin for wordpress.

= Concept =

- no duplicated content (untranslated or untranslatable data inherits main language value)
- no additional database table (translation data is stored in post_meta)
- no markup added into content (unlike q-translate)
- no cookies (language is defined solely by URLs, better for SEO)

= Features =

- [NEW] support for Gutenberg (beta feature)
- translation UI for posts content, title, permalink, excerpt and meta (for posts, pages and custom posts)
- translation UI for terms name, slug and description
- translation UI for attachments title, caption, description, alt and meta
- translation UI for nav menus
- translation UI for options
- translate localized text
- translate login, password change, etc.
- translatability: define which content is translatable or not
- URL rewrite: translate posts and terms permalinks and child pages path
- support revisions
- support multisite
- extendable

= Documentation =

Plugin documentation is available on [github](https://github.com/maximeschoeni/sublanguage)

= Extensions =

- [Sublanguage Switcher Widget](https://wordpress.org/plugins/sublanguage-switcher-widget/) by [Ralf Geschke](https://profiles.wordpress.org/geschke)

= Thanks =

- [uggur](https://profiles.wordpress.org/uggur) for Turkish translation

== Installation ==

Upload and activate the plugin

Add languages

1. click the "Languages" tab
2. click the "Add language" sub-tab
3. choose a language in the list. If it is not in the list, you can just pick any one, then edit the language title, slug and locale code (xx_XX) later
4. choose the language order (it will affect order in the language switch)
5. click "Publish". If you want to keep the language hidden on the front-end while you edit the translations, you can click "Save draft" instead

Add a language switch on the site (within a menu)

1. click the "Appearance" tab
2. click the "Menus" sub-tab.
3. choose or create a menu
4. click "Screen Options" (top right) and verify "Language" is checked
5. open the "language" tab on the left, check the checkbox and click "Add to Menu". Repeat it once for every language.
6. if you want to organize the language items hierarchically, with the current language as root, you also need to go in the Sublanguage Settings and check "Current language first" checkbox.

Add a language switch in a widgets zone

1. click the "Appearance" tab
2. click the "Widgets" sub-tab
3. Drag "Sublanguage" item in the zone you want

Add a language switch anywhere on the site (for developpers)

1. Use this function: `do_action('sublanguage_print_language_switch');`. You can customize HTML output through the filter "sublanguage_custom_switch" (read FAQ for more info).

Add translatable custom post types or custom taxonomies
Plugins or themes may add any number of extra post-type and extra taxonomies. If you can't figure wether a post type or taxonomy need to be translatable, please ask in the forum.

1. click the "Settings" tab
2. click the "Sublanguage" sub-tab
3. select every post type and every taxonomy you need. For better performance, only select translatable post types and taxonomies.
4. click "Save Change"
5. click "Option" link after a post type or taxonomy to access specific options

Translate menu items when your menu is using custom links or item names different from page titles

1. click the "Settings" tab
2. click the "Sublanguage" sub-tab
3. select "Navigation Menu Items" in "Translate Post Type" section
4. click "Save Change"
5. click "Edit translations" after "Navigation Menu Items" in "Translate Post Type" section
6. find the items you need to translate and click to edit

== Frequently Asked Questions ==

= How to clean uninstall this plugin? =

In menu click on `Languages`, remove all language custom posts and empty trash. Deleting a language will permanently delete all translations
associated to this language. Deleting main language will not delete original posts.

= How to add a language switch on template file ? =

Add this function in your template file

	do_action('sublanguage_print_language_switch');

= How to customize the language switch output? =

Add this in your `function.php` file and customize it:

	add_action('sublanguage_custom_switch', 'my_custom_switch', 10, 2);

	/**
	 * @param array of WP_Post language custom post
	 * @param Sublanguage_site $this The Sublanguage instance.
	 */
	function my_custom_switch($languages, $sublanguage) {

	?>
	<ul>
	<?php foreach ($languages as $language) { ?>
		<li class="<?php echo $language->post_name; ?> <?php if ($sublanguage->current_language->ID == $language->ID) echo 'current'; ?>">
			<a href="<?php echo $sublanguage->get_translation_link($language); ?>"><?php echo $language->post_title; ?></a>
		</li>
	<?php } ?>
	</ul>
	<?php

	}

= How to have language switch in navigation menus ? =

If you are using menus and you want the language switch into a menu,
go to Display > Menu, open option drawer and verify 'language' is selected.
Then add as much 'language item' as you have languages.
You can even distribute languages on different hierarchy level.
If you need current language to be on the first level and further language on the second, you will also want to check `current language first` in `Settings -> Sublanguage`

= Post title, content or excerpt does not translate as expected in my theme =

First go to `Sublanguage>Settings` and verify the relevant post type is set to be translatable.

Then verify you are using the proper filters in you template file.

For echoing post title, you need to ensure ´the_title´ filter is called.

	// echoing post title inside the loop
	the_title();

	// echoing post title outside the loop
	echo get_the_title($some_post->ID);

	// but...
	echo $post->post_title;	// -> Does not translate

For echoing post content, you need to ensure ´the_content´ filter is called.

	// echoing content inside the loop
	the_content();

	// or...
	echo apply_filters('the_content', get_the_content());

	// but...
	echo $post->post_content; // -> Does not translate
	echo get_the_content(); // -> Does not translate

	// echoing post content outside the loop:
	echo apply_filters('sublanguage_translate_post_field', $some_post->post_content, $some_post, 'post_content');

Same for Excerpts.

Permalinks are automatically translated, inside or outside the loop:

	// echoing permalink
	echo get_permalink($post->ID);

= Some texts in my theme do not translate as expected, like 'Comment', 'Leave a Reply', etc. =

In your template files, verify texts are [properly localized](https://codex.wordpress.org/I18n_for_WordPress_Developers), and language packages are properly installed.

= How can I access the current language for custom usage? =

Use the global `$sublanguage`, like this:

	global $sublanguage;
	echo $sublanguage->current_language // -> WP_Post object
	echo $sublanguage->current_language->post_title; // -> Français
	echo $sublanguage->current_language->post_name; // -> fr
	echo $sublanguage->current_language->post_content; // -> fr_FR

Alternatively you can use a sublanguage filter to call a user function with `$current_language` value in parameters:

Function to use in your template file:

	echo apply_filters('sublanguage_custom_translate', 'text to translate', 'my_custom_translation', 'optional value');

Code to add in your `function.php` file:

	/**
	 * @param string $original_text. Original text to translate.
	 * @param WP_Post object $current_language
	 * @param mixed $args. Optional arguments
	 */
	function my_custom_translation($original_text, $current_language, $optional_arg) {

		if ($current_language->post_name == 'fr') {

			return 'texte traduit en français!';

		}

		return $original_text;

	}

Note: of course, for a basic usage like this, you should use the standard localization way: `__('text to translate', 'my_domain')`.

= How to translate wordpress options like 'blog name' or 'blog description' ? =

Go to `Sublanguage>Translate Options` and try to find the corresponding option key. Options may be nested in a data tree.

= How to translate texts in widgets? =

Go to `Sublanguage>Translate Options` and find the corresponding widget option name (like 'widget_somthing'). Expand items with value corresponding to 'DATA' until you find the text you need to translate.

= How to translate nav menu items? =

Your nav menu items that are linked to translated posts, pages or terms should be automatically translated.

If you need to translate custom link or to change the default value for items name, you can select "Nav Menu Item" in "Translate post types" section of Sublanguage settings.
Then open `Sublanguage>Nav Menu Item` and edit like a normal post.

= How to make a custom post-meta value translatable? =

Go to Sublanguage settings and select custom post-meta key in the checkbox list under "Translate Meta". A meta key needs to be at least used once to appear in this list.

= How to make post thumbnails translatable (different picture for each language)? =

Go to Sublanguage settings and select '_thumbnail_id' in the checkbox list under "Translate Meta". At least one thumbnail must be set before metakey appears in this list.

= How to access language data in javascript for ajax usage ? =

Add this action to enqueue a small script to define values in javascript:

	add_action('init', 'my_init');

	function my_init() {

		do_action('sublanguage_prepare_ajax');

	}

This will first define a global in javascript. Use `console.log(sublanguage)` to explore it.

Furthermore, a small script will automatically add a language attribute in every jquery ajax call. You can change this language using `sublanguage.current` (in javascript). This language will be used if you need to get/update posts/terms using ajax.

= How to import/export my blog with translations ? =

You cannot export or import using the wordpress builtin tool while Sublanguage is active. It just does not work yet. But this feature will come in a future release.

If you want to create a custom importer for posts and terms, you can use these 2 functions:

    do_action( 'sublanguage_import_post', $data);
    do_action( 'sublanguage_import_term', $taxonomy, $data);

These functions are documented in sublanguage/include/admin.php. See examples on [github](https://github.com/maximeschoeni/sublanguage#import-posts-and-terms).

= Will this plugin affect my site performance? =

Yes it will, unfortunately. A few more database queries are necessary to load every page.
If performance drops noticeably, you may want to install a cache plugin. Sublanguage have been sucessfully tested with [WP Super Cache](http://wordpress.org/extend/plugins/wp-super-cache/) and [W3 Total Cache](http://wordpress.org/extend/plugins/w3-total-cache/) (with default settings).

Sublanguage also works with [SQLite Integration plugin](https://wordpress.org/plugins/sqlite-integration/).

= My language is not in the language list =

Use any language instead, then update, then edit language title, slug and locale, then update again.

== Screenshots ==

1. Add or edit language screen.
2. Post.php screen with language switch and tinyMCE button for quick interface.
3. Tinymce plugin: quick interface for translation
4. Wp.media interface with language tabs for medias translation
5. Edit-tags.php screen.
6. Nav-menus.php screen with language custom metabox
7. Options-permalink.php screen with inputs for taxonomy slug or custom post archive slug translation.
8. Minimal UI settings



== Changelog ==

= 2.8 =

- Fix Bug that erase other language content when a post is saved multiple times with Gutenberg.
- Fix Bug when a post has a translated post_name for main language causing a canonical loop
- Allow special characters in cpt archive url or slug
- Activate rewrite also when property is not set when registering post type

= 2.7.1 =

- Fix bug in Sublanguage::catch_translation: in some case (eg. querying posts page) query_vars "page" property become the pagename, leading to 404 error.

= 2.7 =

- Fix bug when data is not a string in /js/ajax.js
- Class Sublanguage_Site now extends Sublanguage_Rewrite instead of Sublanguage_Current
- Add filter 'sublanguage_untranslated_meta' to handle a translation input placeholder
- Remove trailing slash on translated home url

= 2.6 =

- Support revisions in classic editor
- Partial support revisions in Gutenberg (autosave still bugged)
- Gutenberg UI remove language manager stupid block
- Fix bug when using differently parented pages with same slug
- Fix bug in Gutenberg when saving twice in sub-language
- Fix bug when inserting empty post and sub-language data not empty
- Fix permalink correct language slug in Gutenberg

= 2.5 =

- Basic support for Gutenberg
- Fix bug: Force update_post_caches before translating posts
- Fix bug: only query languages object with status 'publish' or 'draft' in admin side
- Add a function (has_post_translation) and a filter (sublanguage_has_post_translation) to detect if a post have a translation
- A language object can be passed in find_language() function
- Add hook (sublanguage_search_meta) to filter meta keys searcheable for translation
- Improved detect http language
- Use of IETF language tag ("en", "en-en", "es-mx", etc.) to be used for SEO and language detection

= 2.4 =

- Fix bug: attachment translations were not correctly displayed when current language is sub.
- Fix bug: get_post_meta_translation was returning translated value for main language when current language is sub.
- First language locale value is set to "en_US" instead of "" when language is english
- Fix translate sample link
- Verify meta_query parameter is an array in terms_clauses filter
- Rewrite wp-json rules

= 2.3 =

- Fix bug when post or page was not translatable
- Fix bug: dont translate untranslatable taxonomy
- Fix bug when a taxonomy was registered without rewrite args
- Overwrite rewrite rules for pages using every root pages
- Separate custom post type permalink base and archive slug
- Customize notice message when editing language
- Fix bug: nav menu items couldn't be hidden
- Improve default post type options settings
- Enable nav menu item classes translation

= 2.2 =

- Improve search in sub-languages
- Fix bug when saving multiple posts at once in admin
- Fix bug when upgrading from 1.x with orphan translation posts

= 2.1 =

- When upgrading, ajax_frontend option should default true
- On activation, set main language post_content (locale code)
- Query post_status when quering languages
- Fix error in translate_menu_nav_item()
- Set db_version to '2.0' also when there is nothing to upgrade
- Fix error when translatable post meta value is an array

= 2.0 =

- The core of Sublanguage have been rewriten in order to improve general performances and compatibility
- Store posts and terms translations in postmeta/termmeta instead of child posts/terms
- Bugs fixed in rewrite URL
- User interface restructuration

= 1.5.4 =

- Change way of retrieving post_type for post archive link queries
- Function 'translate_post_type_archive_link' now also translate the main language slug for post type archives
- Put back soft translations for post_content, post_title and post_excerpt. Data are translated twice, but compatibility is improved.
- "add_{$meta_type}_metadata" and "update_{$meta_type}_metadata" filters now return correct value
- When preparing attachment data for saving, only filter fields when doing AJAX, in order to support old media interface.
- add html classes to language switch items in the menu for styling
- add filter 'sublanguage_insert_post_data' after parsing post data before updating post

= 1.5.3 =

- Removed translation filter on 'posts_clause' when posts were queried by name or postname. Issue with 'page_for_posts'.
- Hard translate post_content, post_title, post_excerpt to resolve issue with "more" tag
- Remove soft translations for post_content, post_title and post_excerpt
- stripslashes options when translating

= 1.5.2 =

- Correct link url when inserting internal link from editor links popup in admin
- Better language detection with Autodetect Language option
- Fix a bug occuring when language switch is used in more than one menu
- Redirect correctly when Auto-detect language is on and show language slug is off
- Syntaxical changes to prepare 2.0 migration
- Remove html escaping when saving option translation
- When gettign translate, "sublanguage_hide" meta key never inherit value

= 1.5.1 =

- Correct an error when translating nav menu titles
- Correct an error in javascript when saving option translation

= 1.5 =

- New UI for options translation
- New UI for postmeta registration
- New UI for nav menu item translation
- New UI for custom post type translation (when no admin_ui provided)
- New API for import posts and terms
- New language switch interface in post admin
- Changes in admin menu: Sublanguage is now a top level page
- Improvement and simplification in term url (get_term_link) translation

= 1.4.9 =

- Bug fix: Sub-taxonomy archive pages returned incorrect results when taxonomy rewrite slug was different from taxonomy name
- Bug fix: embed shortcodes were not active because the_content filter was called too late
- Bug fix: some table views buttons were unproperly encoded and did't work

= 1.4.8 =

- Adds `Sublanguage_site::get_default_language_switch` function
- Bug fix: terms were not translated correctly when using shared terms (on `wp_term_taxonomy` table).
- Bug fix: removed use of filter for `'home_url'` except in `post.php` page, in order to prevent possible bugs when rebuilding permalinks
- Bug fix: styles in admin terms UI

= 1.4.7 =

- Adds optional `context` parameter for `sublanguage_print_language_switch` and `sublanguage_custom_switch` hooks
- `load_plugin_textdomain` now only called in admin.
- Deprecate `sublanguage_current_language`. Use `sublanguage_init` instead.
- Deprecate `sublanguage_load_admin`. No alternative.
- Bug fix: Multiple errors occured when option was not set
- Bug fix: Multiple errors occured when no languages post type was set.

= 1.4.6 =

- Improves `sublanguage_translate_term_field` to allow translation in any language
- Improves `sublanguage_translate_post_field` to allow translation in any language
- Adds `sublanguage_enqueue_terms` action to handle custom translation terms query
- Adds `sublanguage_enqueue_posts` action to handle custom translation posts query
- Bug fix: Terms order was incorrect when queried order was by name, description or slug on secondary language
- Bug fix: Posts order was incorrect when queried order was by name or title on secondary language
- Bug fix: translation terms taxonomy was incorrectly associated to post object type when registered
- Bug fix: Terms were incorrectly cached when multiple taxonomies were queried at once

= 1.4.5 =

- Support for [hreflang tag](https://moz.com/learn/seo/hreflang-tag)
- Add filter to determine whether empty translated post meta values override originals
- Automatically create term translation when term is created when not on main language
- Improved multilanguage search
- Bug fix: switching language on search page was incorrectly redirecting to home
- Bug fix: getting post meta values without providing key value now return correct values
- Bug fix: `translate_post_type_archive_link()` function did not return the correct link for main language if it was edited.
- Bug fix: problems occured when tag was added while not on main language
- Bug fix: using `sublanguage_custom_switch` hook with only one language was causing error

= 1.4.4 =

- Bug fix: language was mixed when inserting media into post when media and post languages did not match.
- Bug fix: changing language on `wp-admin/post.php` triggered ajax of all submit buttons, including `Delete` in `Custom Fields` box, which deleted all post meta.
- Bug fix: terms translations where not properly deleted when original terms were deleted
- Bug fix: tags name in `Tags` box on `wp-admin/post.php` were not translated
- Bug fix: language was not properly sent by ajax when using GET method
- Bug fix: result of get_terms was not properly translated when only names were queried
- Bug fix: deleting a post was throwing a notice
- Updating from 1.4.3 or before cleans database from all orphan terms

= 1.4.3 =

- Bug fix: editing fields in media interface sometimes failed to save

= 1.4.2 =

- Bug fix: notice was thrown on plugin activation since 1.4
- Bug fix: Error were thrown when quick editing language post slug
- Bug fix: correct save button appearance in tinymce interface

= 1.4.1 =

- Bug fix: added `registration_redirect` function. Language was lost after registering.
- Bug fix: `translate_login_url`. Language was lost on login screen when english was not the main language.
- Bug fix: `sublanguage_translate_post_field`. Filter was not called in admin.

= 1.4 =

- Add support for attachment translation
- Add support to handle editor button in Tinymce Advanced Plugin -
- Undocumented bug fixes

= 1.3 =

- Tinymce plugin, a fast interface for managing posts translations.
- Support widget
- Undocumented bug fixes

= 1.2.2 =

- Bug fix: term description is now correctly translated.
- Bug fix: draft languages are no longer present in front-end language switch.

= 1.2.1 =

Some changes in readme file and adding medias (screenshots, banner, etc.).

= 1.2 =

Undocumented modifications.

= 1.1 =

Undocumented modifications.

== Upgrade Notice ==

No notice yet.
