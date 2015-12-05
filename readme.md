
#Sublanguage

Sublanguage is a [multi-language plugin for Wordpress](https://wordpress.org/plugins/sublanguage/).

## Features overview

- allow translation of `title`, `content`, `excerpt`, `permalink` and `meta fields` for `posts`, `page` and `custom post types`
- allow translation of `title`, `caption`, `alt field` and `description` for `medias`
- allow translation of `name`, `slug` and `description` for `categories`, `tags` and `custom taxonomies`
- allow translation of `post type archive slug` and `taxonomies slugs`
- allow translation of localized texts and login screens
- use URL rewrite
- support quick edit
- support multisite
- support ajax
- extendable

## Philosophy: adaptability and customization

Sublanguage is more a toolkit than a ready-made solution for building a multi-language website. It focuses on customizing public interface for visitors, and adapting user experience for editors. It is design to bring multilingual functionalities and let room for personalization. While UI configuration is quite minimal, multiple hooks and filters are available to fit every needs. 

Sublanguage is based on the concept of inheritance. Translations are custom-post-types parented to original posts, pages or custom-posts. Each translations have 4 relevant fields: `post_title`, `post_content`, `post_name` and `post_excerpt`. If one field is empty, or if translation is missing, original language field content is inherited. The intention is to completely avoid duplicated or even synchronized content, because it is a pain for content editors.

Sublanguage cares about SEO. It uses rewrite URL to structures language content into subdirectories, accordingly with [Google recommendations](https://support.google.com/webmasters/answer/182192?hl=en). Moreover, URL permalink are fully translatable, not only post slugs but also terms, taxonomies and post-type archives slugs.

## Documentation

Additional documentation is available on [Wordpress plugin FAQ](https://wordpress.org/plugins/sublanguage/faq/)

### Installation

1. Upload to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use `do_action('sublanguage_print_language_switch')` in your template file to print the language switch. Please read the doc if you need to customize it.
4. Add language in `Language -> Add language`
5. Configure further options in `Settings -> Sublanguage`

### Uninstallation

For erasing all content added by Sublanguage:

1. Go to `Languages` menu, remove all language custom posts and empty trash
2. Uninstall plugin

Deleting a language will permanently delete all translations associated to this language. Deleting main language will NOT delete original posts.


### Language Switch

Add this function in your template file to echo the language switch.

	do_action('sublanguage_print_language_switch');


For customization, use `sublanguage_custom_switch` filter in your `function.php`.

	add_action('sublanguage_custom_switch', 'my_custom_switch', 10, 2); 

	/**
	 * @param array of WP_Post language custom post
	 * @param Sublanguage_site $this Sublanguage instance.
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

When different language switches are needed in different places of the template, a ´context´ Parameter may be used:

	do_action('sublanguage_print_language_switch', 'top');

        // ...

	do_action('sublanguage_print_language_switch', 'footer');

This parameter is then available in the `sublanguage_custom_switch` filter:

	add_action('sublanguage_custom_switch', 'my_custom_switch', 10, 3); 

	/**
	 * @param array of WP_Post language custom post
	 * @param Sublanguage_site $this Sublanguage instance.
	 */
	function my_custom_switch($languages, $sublanguage, $context) {

		if ($context == 'top') {

			// -> print custom switch

		} else if ($context == 'footer') {

			// -> print custom switch

		}

	}

### Template Loop

Most translations are done automatically through template filters. But you must ensure proper filters are called. 

For echoing post title, you need to ensure ´the_title´ filter is called.

	// echoing post title inside the loop
	the_title();
	
	// echoing post title outside the loop
	echo get_the_title($some_post->ID);
	
	// but...
	echo $post->post_title;	// -> Does not work !

For echoing post content, you need to ensure ´the_content´ filter is called.

	// echoing content inside the loop
	the_content();
	
	// or...
	echo apply_filters('the_content', get_the_content());

	// but...
	echo $post->post_content; // -> Does not work !
	echo get_the_content(); // -> Does not work !
	
	// echoing post content outside the loop:
	echo apply_filters( 'sublanguage_translate_post_field', $some_post->post_content, $some_post, 'post_content' );
	
Idem for post excerpt, you need to ensure ´the_excerpt´ filter is called.
	
For echoing permalink, term link, archive post or home url, just use the usual functions:

	get_permalink();
	get_term_link( $term_id, $taxonomy );
	get_post_type_archive_link( $post_type_name );
	home_url();



### Meta fields

If you want to use translatable meta fields, you need to register related meta key using the `sublanguage_register_postmeta_key` hook. For example:

	add_filter( 'sublanguage_register_postmeta_key', 'my_translate_postmeta' );

	function my_translate_postmeta( $postmeta_keys ) {

		$postmeta_keys[] = '_my_postmeta_key';
	
		return $postmeta_keys;

	}

Then you can use the translated value in your template file just by calling `get_post_meta()` function. It will automatically translate 
accordingly to current language. If translation meta value is empty, it will still inherit from the original post.

	echo get_post_meta($post_id, '_my_postmeta_key', true);

Please note the Wordpress builtin Custom Fields box is not supported. You need to use your own [custom meta box](https://codex.wordpress.org/Function_Reference/add_meta_box) in order to edit the translation meta values in admin.

### AJAX

If you need to access data through AJAX, you just need to add the wanted language slug into the ajax request:

	$.get(wp.url, {action:'my_action', id:myID, language:'fr'}, function(result){
		$(body).append(result); 
	});
	
With something like this in your php:

	add_action( 'wp_ajax_my_action', 'my_action' );
	add_action( 'wp_ajax_nopriv_my_action', 'my_action' );	
	
	function my_action() {
		
		$post = get_post( intval($GET_['id']) );
		
		echo '<h1>' . get_the_title( $post->ID ) . '</h1>';
		
		echo apply_filters( 'sublanguage_translate_post_field', $post->post_content, $post, 'post_content' );
		
		exit;
		
	}
		
You can also use this function to enqueue a small helper javascript file. 

	add_action('init', 'my_init');

	function my_init() {
		
		do_action('sublanguage_prepare_ajax');
		
	}

This script will automatically send current language within every ajax call. Moreover, it will register some useful variables under `sublanguage` javascript global. Use `console.log(sublanguage)` to explore it.

### Current language and PHP Globals

You can use `$sublanguage` global to access most plugins properties and function. For example if you need to access the current language:

	global $sublanguage;
	echo $sublanguage->current_language // -> WP_Post object
	echo $sublanguage->current_language->post_title; // -> Français
	echo $sublanguage->current_language->post_name; // -> fr
	echo $sublanguage->current_language->post_content; // -> fr_FR

Alternatively (and preferably) you can use a sublanguage filter to call a user function with `$current_language` value in parameters:

Function to use in your template file: 

	echo apply_filters('sublanguage_custom_translate', 'hello', 'my_custom_translation', 'optional value');

Code to add in your `function.php` file:

	/**
	 * @param string $original_text. Original text to translate.
	 * @param WP_Post object $current_language
	 * @param mixed $args. Optional arguments
	 */
	function my_custom_translation($original_text, $current_language, $optional_arg) {
	
		if ($current_language->post_content == 'fr_FR') {
			
			return 'Bonjour!';
		
		} else if ($current_language->post_content == 'de_DE') {
			
			return 'Guten Tag!';
		
		}
	
		return $original_text;
	
	}



