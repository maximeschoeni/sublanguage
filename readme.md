
#Sublanguage

Sublanguage is a [multi-language plugin for Wordpress](https://wordpress.org/plugins/sublanguage/).

## Features overview

- translation of posts (pages, attachments and custom posts), terms, options, localized texts and login screens
- translation of post meta and term meta
- translatability: define which content is translatable or not
- inheritance: untranslated or untranslatable contents inherit original values
- URL rewrite: translate posts and terms permalinks and child page path
- quick edit: tinyEditor button for quick edit interface
- support multisite
- support ajax
- extendable

## Philosophy: adaptability and customization

Sublanguage is more a toolkit than a ready-made solution for building a multi-language website. It focuses on customizing public interface for visitors, and adapting user experience for editors. It is design to bring multilingual functionalities and let room for personalization. While UI configuration is quite minimal, multiple hooks and filters are available to fit every needs. 

Sublanguage is based on the concept of inheritance. Translations are custom-post-types parented to original posts, pages or custom-posts. Each translations have 4 relevant fields: `post_title`, `post_content`, `post_name` and `post_excerpt`. If one field is empty, or if translation is missing, original language field content is inherited. The intention is to completely avoid duplicated or even synchronized content.

To comply with SEO standards, Sublanguage uses rewrite URL to structures language content into subdirectories. Moreover, URL permalink are fully translatable, not only post slugs but also terms, taxonomies and post-type archives slugs.

## FAQ

Please read the faq on [wordpress plugin page](https://wordpress.org/plugins/sublanguage/faq/)

## Documentation

- [Installation](#installation)
- [Uninstallation](#uninstallation)
- [Language Switch](#language-switch)
- [Translate posts fields](#translate-posts-fields)
- [Translate terms fields](#translate-terms-fields)
- [Translate meta fields](#translate-meta-fields)
- [AJAX](#ajax)
- [Accessing current language](#accessing-current-language)
- [Import posts and terms](#import-posts-and-terms)

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

When different language switches are needed in different places of the template, a `context` Parameter may be used:

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

### Translate posts fields

You can use `sublanguage_translate_post_field` filter to translate any post field.

	apply_filters( 'sublanguage_translate_post_field', $default, $post, $field, $language = null, $by = null)

This function use 6 parameters:

- `'sublanguage_translate_post_field'`: filter name
- `default`: value to use if translation does not exist
- `post`: the Post object you want to translate the field of
- `field`: field name ('post_title', 'post_content', 'post_name' or 'post_excerpt')
- `language`: Optional. Language slug, id, locale or object. By default, the current language will be used
- `by`: Optional. Use 'ID' or 'post_content' only if language is set to id or locale.

Example:

	echo apply_filters( 'sublanguage_translate_post_field', $some_post->post_title, $some_post, 'post_title' );

Most translations are done automatically within template filters though. Calling `get_title()`, `get_the_title($id)`, `the_content()`, `the_excerpt()` or `get_permalink($id)` will automatically return or echo translated text.

Example for echoing post title:

	// echoing post title inside the loop
	the_title();
	
	// echoing post title inside or outside the loop
	echo get_the_title( $some_post->ID );
	
	// or
	echo apply_filters( 'the_title', $some_post->post_title, $some_post->ID );
	
	// or
	echo apply_filters( 'sublanguage_translate_post_field', $some_post->post_title, $some_post, 'post_title' );

Example for echoing post content:

	// echoing content inside the loop
	the_content();
	
	// or
	echo apply_filters('the_content', get_the_content());

	// echoing post content outside the loop:
	echo apply_filters( 'sublanguage_translate_post_field', $some_post->post_content, $some_post, 'post_content' );
	
Most links are automatically translated through specific function:
	
	// get post, page, media or custom post link:
	get_permalink( $id );
	
	// get catgory, tag or term:
	get_term_link( $term_id, $taxonomy );
	
	// get custom post type archive:
	get_post_type_archive_link( $post_type_name );
	
	// get home
	home_url();

You can also translate post field into non-current language:

	// echo post title in spanish by slug
	echo apply_filters( 'sublanguage_translate_post_field', $some_post->post_title, $some_post, 'post_title', 'es' );
	
	// echo post title in spanish by locale
	echo apply_filters( 'sublanguage_translate_post_field', $some_post->post_title, $some_post, 'post_title', 'es_ES', 'post_content' );

### Translate terms fields

All fields of fetched terms are automatically translated to current language. Example:

	// echo translated term name
	echo $term->name;

For translating term fields in non-current language, use `sublanguage_translate_term_field` filter.

	apply_filters( 'sublanguage_translate_term_field', $default, term, $field, $language = null, $by = null)

This function use 6 parameters:

- `'sublanguage_translate_term_field'`: filter name
- `default`: value to use if translation does not exist
- `term`: the Term object you want to translate the field of
- `field`: field name ('name', 'slug', or 'description')
- `language`: Optional. Language slug, id, locale or object. By default, the current language will be used
- `by`: Optional. Use 'ID' or 'post_content' only if language is set to id or locale.

Example:
	
	// translate term name in Korean:
	echo apply_filters( 'sublanguage_translate_term_field', term->name, term, $field, 'ko')

### Translate meta fields

Translatable postmeta keys need to be registered in Admin -> Sublanguage.

Postmeta translated value is accessible in template file by calling `get_post_meta()` function. It will automatically translate 
accordingly to current language. If meta value is empty for a translation, it will inherit from the original post.

	echo get_post_meta( $post_id, '_my_postmeta_key', true );

Note: the Wordpress builtin Custom Fields box is not supported. [Custom meta box](https://codex.wordpress.org/Function_Reference/add_meta_box) are needed in order to edit the translation meta values in admin.

### AJAX

If you need to access data through AJAX, you just need to add the wanted language slug into the ajax request:

	$.get(ajaxurl, {action:'my_action', id:myID, language:'fr'}, function(result){
		$(body).append(result); 
	});
	
With something like this in your php:

	add_action( 'wp_ajax_my_action', 'my_action' );
	add_action( 'wp_ajax_nopriv_my_action', 'my_action' );	
	
	function my_action() {
		
		$post = get_post( intval($_GET['id']) );
		
		echo '<h1>' . get_the_title( $post->ID ) . '</h1>';
		
		echo apply_filters( 'sublanguage_translate_post_field', $post->post_content, $post, 'post_content' );
		
		exit;
		
	}

Also see [AJAX in Wordpress](https://codex.wordpress.org/AJAX_in_Plugins) documentation.

#### Ajax Helper
	
You can  use the following function to enqueue a small helper javascript file.

	add_action('wp_enqueue_scripts', 'my_prepare_ajax');

	function my_init() {
		
		do_action('sublanguage_prepare_ajax');
		
	}

This script will automatically send current language within every ajax call. Moreover, it will register some useful variables under `sublanguage` javascript global. Use `console.log(sublanguage)` to explore it.

### Accessing current language

You can use `$sublanguage` global to access most plugins properties and function. For example if you need to access the current language (lets say it is french):

	global $sublanguage;
	echo $sublanguage->current_language // -> WP_Post object
	echo $sublanguage->current_language->post_title; // -> Français
	echo $sublanguage->current_language->post_name; // -> fr
	echo $sublanguage->current_language->post_content; // -> fr_FR

Alternatively (and preferably) you can use `sublanguage_custom_translate` filter to call a user function with `$current_language` value in parameters:

	apply_filters('sublanguage_custom_translate', $default, $callback, $context = null);

This function use 4 parameters:

- `'sublanguage_custom_translate'`: filter name
- `default`: value to use if translation does not exist
- `$callback`: user function to be called
- `$context`: Optional value.

Example. Function to use in your template file: 

	echo apply_filters('sublanguage_custom_translate', 'hello', 'my_custom_translation', 'optional value');

Code to add in your `function.php` file:

	/**
	 * @param string $original_text. Original text to translate.
	 * @param WP_Post object $current_language
	 * @param Sublanguage_site object $sublanguage
	 * @param mixed $args. Optional argument passed.
	 */
	function my_custom_translation($original_text, $current_language, $sublanguage, $optional_arg) {
	
		if ($current_language->post_content == 'it_IT') {
			
			return 'Ciao!';
		
		} else if ($current_language->post_content == 'de_DE') {
			
			return 'Hallo!';
		
		}
	
		return $original_text;
	}

### Import posts and terms

Sublanguage does not provide any importer tool, but these two functions can be useful for importing data from various place.

#### Importing post

This Function create or update one post and add or update associated translations.

	do_action( 'sublanguage_import_post', $data);
	
Examples:

This code creates an english page (provided that english is main language) with translations for french and italian.

	do_action( 'sublanguage_import_post', array(
		'post_type' => 'page',
		'post_title' => 'Hello',
		'post_content' => 'Sample content'
		'sublanguages' => array(
			array(
				'language' => 'fr',
				'post_title' => 'Bonjour'
				'post_content' => 'Exemple de contenu'
			),
			array(
				'language' => 'it',
				'post_title' => 'Ciao'
				'post_content' => 'Contenuto di esempio'
			)
		)
	));

This code find an existing post whose ID equals 123 and adds translations for french and italian.

	do_action( 'sublanguage_import_post', array(
		'ID' => 123,
		'sublanguages' => array(
			array(
				'language' => 'fr',
				'post_title' => 'Bonjour'
				'post_content' => 'Exemple de contenu'
			),
			array(
				'language' => 'it',
				'post_title' => 'Ciao'
				'post_content' => 'Contenuto di esempio'
			)
		)
	));

#### Importing term

	do_action( 'sublanguage_import_term', $taxonomy, $data);
	
Examples:

This code creates an english tag (provided that english is main language) with translations for french and italian.

	do_action( 'sublanguage_import_term', 'post_tag' array(
		'name' => 'Example',
		'sublanguages' => array(
			array(
				'language' => 'fr',
				'name' => 'Exemple'
			),
			array(
				'language' => 'it',
				'name' => 'Esempio'
			)
		)
	));
	
This code find an existing category with slug 'uncategorized' and adds translations for french and italian.

	do_action( 'sublanguage_import_term', 'category', array(
		'slug' => 'uncategorized',
		'sublanguages' => array(
			array(
				'language' => 'fr',
				'name' => 'Non-categorisé'
			),
			array(
				'language' => 'it',
				'name' => 'Non è stato classificato'
			)
		)
	));
