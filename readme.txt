=== Sublanguage ===
Contributors: maximeschoeni
Tags: multilanguage, multilingual, language, translation
Requires at least: 4.4
Tested up to: 4.4
Stable tag: 1.5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sublanguage is a lightweight multilanguage plugin for wordpress.

== Description ==

= Philosophy =

Sublanguage is more a toolkit than a ready-made solution for building a multi-language website. It focuses on customizing public interface for visitors, and adapting user experience for editors. It is design to bring multilingual functionalities and let room for personalization. While UI configuration is quite minimal, multiple hooks and filters are available to fit every needs. 

Sublanguage is based on the concept of inheritance. Translations are custom-post-types parented to original posts, pages or custom-posts. Each translations have 4 relevant fields: `post_title`, `post_content`, `post_name` and `post_excerpt`. If one field is empty, or if translation is missing, original language field content is inherited. The intention is to completely avoid duplicated or even synchronized content.

To comply with SEO standards, Sublanguage uses rewrite URL to structures language content into subdirectories. Moreover, URL permalink are fully translatable, not only post slugs but also terms, taxonomies and post-type archives slugs.

= Features =

- can translate `title`, `content`, `excerpt`, `permalink` and `meta fields` for `posts`, `page` and `custom post types`
- can translate `title`, `caption`, `alt field` and `description` for `medias`
- can translate `name`, `slug` and `description` for `categories`, `tags` and `custom taxonomies`
- can translate `post type archive slug` and `taxonomies slugs`
- can translate nav menus items
- can translate options
- can translate localized texts and login screens
- fields inherit original language by default
- use URL rewrite
- support quick edit
- support multisite
- support ajax
- extendable

= Documentation =

Plugin documentation is available on [github](https://github.com/maximeschoeni/sublanguage)

= Thanks =

- [uggur](https://profiles.wordpress.org/uggur) for Turkish translation

== Installation ==

1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use `do_action('sublanguage_print_language_switch')` in your template file to print the language switch. Please read the faq if you need to customize it.
1. Add language in `Sublanguage>Languages` and click on `Add language`
1. Configure further options in `Sublanguage>Settings`

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
4. Wp.media interface width language tabs for medias translation
5. Edit-tags.php screen.
6. Nav-menus.php screen with language custom metabox
7. Options-permalink.php screen with inputs for taxonomy slug or custom post archive slug translation.
8. Minimal UI settings



== Changelog ==

= 1.5.2 =

- Correct link url when inserting internal link from editor links popup in admin
- Better language detection with Autodetect Language option
- Fix a bug occuring when language switch is used in more than one menu 
- Redirect correctly when Auto-detect language is on and show language slug is off
- Syntaxical changes to prepare 2.0 migration
- Remove html escaping when saving option translation

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

