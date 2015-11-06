
=== Sublanguage ===
Contributors: maximeschoeni
Donate link: sublanguageplugin.wordpress.com
Tags: multilanguage, multilingual, language, translation
Requires at least: 4.0
Tested up to: 4.3.1
Stable tag: 1.4.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sublanguage is a lightweight multilanguage plugin for wordpress.

== Description ==

Sublanguage is a lightweight solution for building multilanguage sites with wordpress.

= No duplicated content =

Sublanguage works by extending a "main language" by one or more "sub-languages". When data is missing for a "sub-language", 
"main language" data will be used instead. Thus, you only need to translate data that actually differ between languages. 

= Translate posts, pages, medias and custom posts =

You can translate title, content, excerpt, permalink (url) and meta fields for any kind of post types,
and title, caption, alt field and description for medias (attachment).
When a field is let blank, it will inherit value from "main language".
By default, only posts and pages are translatable. Go to `Settings` > `Sublanguage` to enable other post types.
You can also translate meta fields, but you need first to register them using `sublanguage_register_postmeta_key` filter. And the default `Custom Fields` box is not supported. Read the faq for more information.
Post revisions are supported for every languages.

= Translate categories, tags and custom taxonomies =

You can translate the name, slug and description field for any term. 
As for posts, blank fields will also inherit the main language value.
You cannot translate term relationship: all translation of a post inherit the same term relationship.
By default, only categories are translatable. Go to `Settings` > `Sublanguage` to enable other taxonomies.

= Quick edit =

Sublanguage support the quick edit interface in post list table, and adds a custom button in the mce editor for opening a javascript interface to handle quick posts translation.

= No additional tables in database =

All translation data is stored using the standard Wordpress API for custom posts and taxonomies.
So you won't run into problems if you have other plugins dealing with database (import/export plugin, archive plugin, cache plugin, etc.)

= Support ajax =

You can use ajax to get/upload posts from front-end. Read the faq for more information.
Use `do_action('sublanguage_prepare_ajax')` in your template file to enqueue a script to provide useful data in javscript. Read the faq for more information.

= Support multisite =

Sublanguage works independantly for each site of a multisite installation.

= Translate login screens =

Translate screens for login, reset password, register, and email alerts.

= Extensible =

You can extend functionalities by using some hooks/filters. Read the faq for more information.

= No automatic translations ! =

Sublanguage provide an interface to deal with multilanguage but does not handle automatic translations.

== Installation ==

1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use `do_action('sublanguage_print_language_switch')` in your template file to print the language switch. Please read the faq if you need to customize it.
1. Add language in `Language -> Add language`
1. Configure further options in `Settings -> Sublanguage`

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
			<a href="<?php echo $sublanguage->get_translation_link($language); ?>"><?php echo apply_filters('sublanguage_language_name', $language->post_title, $language); ?></a>
		</li>
	<?php } ?>
	</ul>
	<?php 

	}

By the way, if you just want to replace the language name by the slug, use this instead:

	add_filter('sublanguage_language_name', 'my_language_name', 10, 2); 
	/**
	 * @param string language name
	 * @param WP_Post language custom post
	 */
	function my_language_name($name, $language) {

		return $language->post_name;

	}
	
= How to have language switch in navigation menus ? =

If you are using menus and you want the language switch into a menu, 
go to Display > Menu, open option drawer and verify 'language' is selected. 
Then add as much 'language item' as you have languages.
You can even distribute languages on different hierarchy level.
If you need current language to be on the first level and further language on the second, you will also want to check `current language first` in `Settings -> Sublanguage`

= Post title, content or excerpt does not translate as expected in my theme =

First go to `Settings -> Sublanguages` and verify the relevant post type is set to be translatable.

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
	
	// or...
	global $post;
	$post = $some_post;
	the_content();
	wp_reset_postdata();	

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

= How to translate various entries like blog title, blog description, or user descriptions ? =

Sublanguage does not provide an dedicated interface to translate this, so you need to exend it. 

Here is one solution you may try. Let's say we need to translate the blog description:

1. Go to `Settings -> General` and enter something like this into `tagline` (for an english/french site): `[:en]Just another WordPress site[:fr]Encore un vieux site Wordpress`
1. Add the following into your `function.php`.

		add_filter('option_blogdescription', 'my_translate_like_old_qtranslate'); 

		function my_translate_like_old_qtranslate($original) {

			return apply_filters('sublanguage_custom_translate', $original, 'my_custom_language_parser');

		}

		function my_custom_language_parser($original, $language) {

			if (preg_match('/\[:'.$language->post_name.'\]([^[]*)/', $original, $matches)) {

				return $matches[1];

			}

			return $original;

		}

= How to make a custom post-meta value translatable? =

You need to register the relevant meta key using the following hook. Add this to your `function.php` file:

	add_filter('sublanguage_register_postmeta_key', 'my_translate_postmeta');

	function my_translate_postmeta($postmeta_keys) {

		$postmeta_keys[] = '_my_postmeta_key';
	
		return $postmeta_keys;

	}

Then you can access it in your template files using `get_post_meta($post->ID, '_my_postmeta_key')`. It will automatically translate 
accordingly to current language. If translation's meta value is empty, it will still inherit from the original post.

= How to make post thumbnails translatable (different picture for each language)? =

Use the same code as above and replace `'_my_postmeta_key'` by `'_thumbnail_id'`

= How to access language data in javascript for ajax usage ? =

Add this action to enqueue a small script to define values in javascript:

	add_action('init', 'my_init');

	function my_init() {
		
		do_action('sublanguage_prepare_ajax');
		
	}
	
This will first define a global in javascript. Use `console.log(sublanguage)` to explore it.

Furthermore, a small script will automatically add a language attribute in every jquery ajax call. You can change this language using `sublanguage.current` (in javascript). This language will be used if you need to get/update posts/terms using ajax.

= How to translate login pages? =

First verify your language packages are [properly installed](http://codex.wordpress.org/Installing_WordPress_in_Your_Language).

To get the link to the localized login page, you need to add a `language` parameter in login url: `www.exemple.com/wp-login.php?language=fr`.

If you are using `wp_login_url()` function to get the link, it should be automatically localized.

= May I customize the admin language interface ? =

Use the following hooks to customize sublanguage admin interface.

To modify or remove the language indication in the top of the form, add this action:

	add_action('sublanguage_admin_display_current_language', 'my_display_language');

	function my_display_language($sublanguage_admin) {
		
		echo 'put this instead.';
		
		// call this to get the current language object: $sublanguage_admin->current_language;
		
		// call this to get a list of all language objects: $sublanguage_admin->get_languages();
		
	}

To change language switch output in metabox:

	add_action('sublanguage_admin_custom_switch', 'my_admin_language_switch');

	function my_admin_language_switch($sublanguage_admin) {
		
		echo 'put this instead.';
		
		// please refer to 'print_language_switch()' function in 'sublanguage/include/admin-post.php'
		
	}

To remove completely the language metabox:

	add_action('add_meta_boxes', 'my_remove_admin_language_switch', 12);

	function my_remove_admin_language_switch($post_type) {
		
		remove_meta_box('sublanguage-switch', $post_type, 'side');

	}

= Will this plugin affect my site performance? =

Yes it will, unfortunately. A few more database queries are necessary to load every page.
If performance drops noticeably, you may want to install a cache plugin. Sublanguage have been sucessfully tested with [WP Super Cache](http://wordpress.org/extend/plugins/wp-super-cache/) and [W3 Total Cache](http://wordpress.org/extend/plugins/w3-total-cache/) (with default settings).

Sublanguage also works with [SQLite Integration plugin](https://wordpress.org/plugins/sqlite-integration/).

== Screenshots ==

1. Every language correspond to a language custom post
2. edit.php screen for translatable posts.
3. post.php screen for translatable post.
4. edit-tags.php screen for translatable term.
5. edit-tags.php screen for translatable term.
6. options-permalink.php screen for taxonomy slug or custom post archive slug translation.
7. custom settings for Sublanguage
8. tinymce plugin: quick interface for translation
9. media interface for translation

== Changelog ==

= 1.4.4 =

- Bug fix: language was mixed when inserting media into post when media and post languages did not match.
- Bug fix: changing language on `wp-admin/post.php` triggered ajax of all submit buttons, including `Delete` in `Custom Fields` box, which deleted all post meta.
- Bug fix: terms translations where not properly deleted when original terms were deleted
- Bug fix: tags name in `Tags` box on `wp-admin/post.php` were not translated
- Bug fix: language was not properly sent by ajax when using GET method
- Bug fix: result of get_terms was not properly translated when only names were queried
- Bug fix: deleting a post was throwing a notice
- Feature: updating from 1.4.3 or before cleans database from all orphan terms

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

- New feature: add support for attachment translation
- New feature: add support to handle editor button in Tinymce Advanced Plugin -
- Various bug fixes

= 1.3 =

- New feature: tinymce plugin, a fast interface for managing posts translations.
- New feature: support widget
- Various bug fixes

= 1.2.2 =

- Bug fix: term description is now correctly translated.
- Bug fix: draft languages are no longer present in front-end language switch.

= 1.2.1 =

Some changes in readme file and adding medias (screenshots, banner, etc.).

= 1.2 =

Various core modifications.

= 1.1 =

Various core modifications.

== Upgrade Notice ==

No notice yet.

