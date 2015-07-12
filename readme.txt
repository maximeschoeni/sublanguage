
=== Sublanguage ===
Contributors: maximeschoeni
Donate link: sublanguageplugin.wordpress.com
Tags: multilanguage, multilingual, language, translation
Requires at least: 4.0
Tested up to: 4.2.2
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sublanguage is a lightweight multilanguage plugin for wordpress.

== Description ==

Sublanguage is a lightweight multilanguage plugin for wordpress. It is designed for developpers who need to build custom multilanguage website with full control over language functionalities.

= Sublanguage can be used to translate =

- title, content, excerpt, permalink (url) and post-meta of posts, pages and custom posts
- name, slug (url) and description of tags, categories and custom taxonomies
- login screens
- localized texts in scripts

= Additional features =

- clean admin interface
- no duplicated content
- urls fully translatable
- support quick edit
- support revisions
- support ajax
- extensible

= Notices =

Front-end interface is fully customizable but there is no graphical interface for that. You will need to handle everything by code.

Compatibility with existing themes or plugins is not guaranteed. But you can make any new theme or plugin compatible with Sublanguage. Please read the FAQ.

Sublanguage does not handle automatic translations.


== Installation ==

1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How does Sublanguage work? =

Sublanguage plugin first adds a `language` custom post type. `Language` custom posts have the following attributes: 

- `post_title`: language name (English, Français, Deutch, etc.)
- `post_name`: language slug used in url (en, fr, de, etc.)
- `post_content`: language code for localization (en_GB, fr_FR, de_DE, etc.)

Adding a language custom post then creates a `translation` custom post type, by combining this language post id with a prefix.

A `translation` custom post is created for every post, page or custom post needing a translation.

Translation custom posts have the following attributes:

- `post_title`: translation of post title
- `post_content`: translation of post content
- `post_excerpt`: translation of post excerpt
- `post_name`: translation of post name (permalink)
- `post_parent`: Id of the original post
- `post_type`: language custom post id combined with a prefix.

Every other post attributes is inherited from original. If `post_title`, `post_content`, `post_excerpt` or `post_name` is left
blank, it will inherit value from original post. Translation custom post may also have post meta values.

Adding a language also creates a custom taxonomy, by also combining this language post id with a prefix.

A translation term is created for every term needing a translation, and has the following attributes:

- `slug`: translation of term slug
- `name`: translation of term name
- `description`: translation of term description
- `parent`: term id of original term
- `taxonomy`: language custom post id combined with a prefix.

A language post status must be set to `publish` to have posts using this language to be visible on front-end. If set to `draft`, language will still be available in admin for translation.

Deleting a language post will permanently delete all translations associated to this language. Deleting main language will not delete original posts.

On a standard front-end request, Sublanguage plugin will first try to find out the requested language.
If found, it will then query the original post(s) and the relevant translation(s).
Then it will add a few hooks to force translation in various template function, like `the_content()` or `get_post_title()`.

Sublanguage does not add any custom table in database.

= How to clean uninstall this plugin? =

In menu click on `Languages`, remove all language custom posts and empty trash. Deleting a language will permanently delete all translations 
associated to this language. Deleting main language will not delete original posts.

= How to add a language switch on front-end? =

There is 2 ways:

- If you are using menus and you want the language switch into a menu, 
go to Display > Menu, open option drawer and verify 'language' is selected. 
Then add as much 'language item' as you have languages.
You can even distribute languages on different hierarchy level.
If you need current language to be on the first level and further language on second, you will also want to check `current language first` in `Settings -> Sublanguage`
- Otherwise, add this function in your template file (read below for customization)

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

= How to translate various entries like blog title, blog description, user descriptions or attachments fields? =

Sublanguage does not provide an dedicated interface to translate this, so you need to exend it. 

Here is one solution you may try. Let's say we need to translate the blog description:

1. Go to `Settings -> General` and enter something like this into `tagline` (for an english/french site): `[:en]Just another WordPress site[:fr]Encore un vieux site Wordpress`
1. Add the following into your `function.php`:

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

= How to translate login pages? =

First verify your language packages are [properly installed](http://codex.wordpress.org/Installing_WordPress_in_Your_Language).

To get the link to the localized login page, you need to add a `language` parameter in login url: `www.exemple.com/wp-login.php?language=fr`.

If you are using `wp_login_url()` function to get the link, it should be automatically localized.

= May I customize the admin language interface ? =

Currently you can only customize post.php admin page.

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

== Changelog ==

= 1.2.1 =

Some changes in readme file and adding medias (screenshots, banner, etc.).

= 1.2 =

Various core modifications.

= 1.1 =

Various core modifications.

== Upgrade Notice ==

No notice yet.

