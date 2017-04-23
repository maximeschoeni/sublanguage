<?php

/** 
 * Base class for Sublanguage_site in front-end and Sublanguage_admin in admin.
 */
class Sublanguage_core {


	/** 
	 * @from 1.1
	 *
	 * @var string
	 */
	var $version = '2.2';

	/** 
	 * @from 2.0
	 *
	 * @var string
	 */
	var $db_version = '2.0';
	
	/**
	 * @from 1.0
	 *
	 * @var array
	 */
	var $current_language;
	
	/**
	 * @from 1.1
	 *
	 * @var string
	 */
	var $language_query_var = 'language';
	
	/** 
	 * @from 1.0
	 *
	 * @var string
	 */
	var $option_name = 'sublanguage';

	/**
	 * @from 1.0
	 *
	 * @var string
	 */
	var $language_post_type = 'language';

	/**
	 * @from 1.1
	 *
	 * @deprecated from 2.0. Use get_post_type_metakeys() instead.
	 * @var array
	 */
	private $postmeta_keys;
	
	/**
	 * @from 1.5
	 *
	 * @var array
	 */
	private $taxonomies;
	
	/**
	 * @from 1.5
	 *
	 * @deprecated from 2.0. Use is_post_type_translatable() instead.
	 * @var array
	 */
	private $post_types;
	
	/**
	 * @from 1.4
	 *
	 * @var array
	 */
	var $fields = array('post_content', 'post_name', 'post_excerpt', 'post_title');
	
	/**
	 * @from 2.0
	 *
	 * @var array
	 */
	var $taxonomy_fields = array('name', 'slug', 'description');
	
	/**
	 * @var string
	 *
	 * Prefix for creating language unique id.
	 *
	 * @deprecated from 2.0. Use get_prefix() instead. STILL NEEDED FOR COMPAT.
	 * @from 1.5.1
	 */
	var $translation_prefix = 'translation_';
	
	/** 
	 * Get option
	 *
	 * @param string $option_name. Option name
	 * @param mixed $default. Default value if option does not exist
	 * @return mixed
	 *
	 * @from 1.4.7
	 */
	public function get_option($option_name, $default = false) {
	
		$options = get_option($this->option_name);
		
		if (isset($options[$option_name])) {
			
			return $options[$option_name];
		
		}
		
		return $default;
	}
	
	/**
	 * Get current language
	 *
	 * @from 2.0
	 *
	 * @return object WP_post|false
	 */
	public function get_language() {
		
		if (!isset($this->current_language)) {
			
			$this->current_language = $this->request_language();
			
		}
		
		return $this->current_language;
	}
	
	/**
	 * Check wether current language is defined
	 *
	 * @from 2.0
	 *
	 * @return bool
	 */
	public function has_language() {
		
		return isset($this->current_language) && $this->current_language;
		
	}
	
	/**
	 * Set current language
	 *
	 * @from 2.0
	 *
	 * @param object WP_post $language Language. Optional
	 */
	public function set_language($language = null) {
		
		static $original;
		
		if (!isset($original)) {
			
			$original = $this->get_language();
			
		}
		
		if (empty($language)) {
			
			$language = $original;
			
		}
		
		$this->current_language = $language;
	}
	
	/**
	 * Restore original language after changing with set_language()
	 *
	 * @from 2.0
	 */
	public function restore_language() {
		
		$this->set_language();
		
	}
	
	/**
	 * Requestion current language
	 *
	 * @from 2.0
	 *
	 * @return object WP_post|false
	 */
	public function request_language() {
		
		if (isset($_REQUEST[$this->language_query_var])) {
		
			return $this->get_language_by($_REQUEST[$this->language_query_var], 'post_name');
			
		} 
		
		return $this->get_main_language();
	}
	
	/**
	 * Get array of languages
	 *
	 * @from 1.2
	 *
	 * @return array of WP_post objects
	 */
	public function get_languages() {
		global $wpdb;
		
		static $languages;
		
		if (!isset($languages)) {

			$languages = $wpdb->get_results( $wpdb->prepare(
				"SELECT post.ID, post.post_name, post.post_title, post.post_content, post.menu_order, post.post_excerpt, post.post_status FROM $wpdb->posts AS post
					WHERE post.post_type = %s
					ORDER BY post.menu_order ASC",					
				$this->language_post_type
			));
			
		}
    
		return $languages;
	}

	/**
	 * Select language object by translation post-type
	 *
	 * @deprecated from 1.5.1
	 * @from 1.1
	 *
	 * @param string $post_type.
	 * @return WP_Post object.
	 */
	public function get_language_by_type($post_type) {
		
		return $this->get_language_by($post_type, 'post_excerpt');
		
	}
  
	/**
	 * Select language object by translation taxonomy
	 * 
	 * @deprecated from 1.5.1
	 * @from 1.1
	 *
	 * @param string $post_type. 
	 * @return WP_Post object.
	 */
	public function get_language_by_taxonomy($taxonomy) {
		
		return $this->get_language_by($taxonomy, 'post_excerpt');
	
	}
	
	/**
	 * Get language by field.
	 * ID corresponds to language ID.
	 * post_name corresponds to language slug.
	 * post_content corresponds to language locale.
	 *
	 * @from 1.1
	 *
	 * @param string $val.
	 * @param string $key.
	 * @return false|WP_post object
	 */
	public function get_language_by($val, $key = 'post_name') {
		
		$languages = $this->get_languages();
		
		foreach ($languages as $lng) {
			
			if ($lng->$key == $val) return $lng;
		
		}
		
		return false;
	}
	
	/**
	 * get an array of all languages values for one field
	 *
	 *	@from 1.1
	 *
	 * @param string $column.
	 * @return array
	 */
	public function get_language_column($column) {
		
		$output = array();
		
		$languages = $this->get_languages();
		
		foreach ($languages as $lng) {
			
			$output[] = isset($lng->$column) ? $lng->$column : false;
			
		}
		
		return $output;
	}
	
	/**
	 * Get default language 
	 *
	 * @from 1.2
	 *
	 * @return WP_Post object
	 */
	public function get_default_language() {
		
		return $this->get_language_by($this->get_option('default'), 'ID');
    
	}

	/**
	 * Get main language 
	 *
	 * @from 1.2
	 *
	 * @return WP_Post object
	 */
	public function get_main_language() {
		
		return $this->get_language_by($this->get_option('main'), 'ID');
    
	}
	
	/**
	 * Get an array of all languages translation post-types
	 *
	 * @deprecated from 1.5.1. Use get_language_column() instead.
	 * @from 1.4.4
	 *
	 * @param string $column.
	 * @return array
	 */
	public function get_language_post_types() {
		
		return $this->get_language_column('post_excerpt');
		
	}
	
	/**
	 * Create prefix for translation meta keys
	 *
	 * @from 2.0
	 *
	 * @param string $language_slug
	 * @return string
	 */
	public function create_prefix($slug) {
		
		return '_' . $slug . '_';
		
	}
	
	/**
	 * Get prefix for translation meta keys
	 *
	 * @from 2.0
	 *
	 * @param object WP_Post $language. Optional
	 * @return string
	 */
	public function get_prefix($language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
		
		if (empty($language)) {
			
			return false;
		
		}
		
		return $this->create_prefix($language->post_name);
		
	}
	
	/**
	 * Get post_type options for translation.
	 *
	 * @from 2.0
	 * 
	 * @return mixed
	 */
	public function get_post_type_options() {
		
		static $post_types_options;
		
		if (!isset($post_types_options)) {
			
			$post_types_options = $this->get_option('post_type', false);
			
			if ($post_types_options === false) {
			
				/**
				 * Filter post type default options
				 *
				 * @from 2.0
				 *
				 * @param mixed. Default option
				 */
				$post_types_options = apply_filters("sublanguage_post_type_default", array(
					'post' => array('translatable' => true), 
					'page' => array('translatable' => true)
				));
			
			}
			
		}
		
		return $post_types_options;
	}
	
	
	/**
	 * Get post_type single option for translation.
	 *
	 * @from 2.0
	 *
	 * @param string $post_type
	 * @param string $option_name. Accepts 'translatable', 'meta_keys', 'fields', 'title_cached', 'exclude_untranslated'
	 * @param mixed $fallback. Value returned when option is not defined.
	 * 
	 * @return mixed
	 */
	public function get_post_type_option($post_type, $option_name, $fallback = false) {
		
		$post_types_options = $this->get_post_type_options();
		
		if (isset($post_types_options[$post_type][$option_name])) {
		
			return $post_types_options[$post_type][$option_name];
			
		}
		
		return $fallback;
	}
	
	/**
	 * Check if post_type is translatable.
	 *
	 * @from 2.0 $post_type should no longer be 'any' or array.
	 * @from 1.4.5
	 *
	 * @param string $post_type
	 * 
	 * @return boolean
	 */
	public function is_post_type_translatable($post_type) {
		
		return $this->get_post_type_option($post_type, 'translatable');
		
	}
	
	/**
	 * Get post_type translatable meta keys.
	 *
	 * @from 2.0
	 *
	 * @param string $post_type
	 * 
	 * @return array of strings
	 */
	public function get_post_type_metakeys($post_type) {
		
		return $this->get_post_type_option($post_type, 'meta_keys', array());
		
	}

	/**
	 * Get post_type translatable fields.
	 *
	 * @from 2.0
	 *
	 * @param string $post_type
	 * 
	 * @return array of strings
	 */
	public function get_post_type_fields($post_type) {
		
		return $this->get_post_type_option($post_type, 'fields', $this->fields);
		
	}
	
	/**
	 * Check if post_type title is cached (for ordering)
	 *
	 * @from 2.0
	 *
	 * @param string $post_type
	 * 
	 * @return boolean
	 */
	public function is_post_type_title_cached($post_type) {
		
		return $this->get_post_type_option($post_type, 'title_cached');
		
	}
	
	/**
	 * Get taxonomies options for translation.
	 *
	 * @from 2.0
	 *
	 * @return mixed
	 */
	public function get_taxonomies_options() {
		
		static $taxonomy_options;
		
		if (!isset($taxonomy_options)) {
		
			$taxonomy_options = $this->get_option('taxonomy', false);
			
			if ($taxonomy_options === false) {
			
				/**
				 * Filter taxonomy default options
				 *
				 * @from 2.0
				 *
				 * @param mixed. Default option
				 */
				$taxonomy_options = apply_filters("sublanguage_taxonomy_default", array(
					'category' => array('translatable' => true)
				));
				
			}
			
		}
		
		return $taxonomy_options;
	}
	
	/**
	 * Get taxonomy single option for translation.
	 *
	 * @from 2.0
	 *
	 * @param string $taxonomy
	 * @param string $option_name. Accepts 'translatable', 'meta_keys', 'fields'
	 * @param mixed $fallback. Value returned when option is not defined.
	 * 
	 * @return mixed
	 */
	public function get_taxonomy_option($taxonomy, $option_name, $fallback = false) {
		
		$taxonomy_options = $this->get_taxonomies_options();
		
		if (isset($taxonomy_options[$taxonomy][$option_name])) {
		
			return $taxonomy_options[$taxonomy][$option_name];
			
		}
		
		return $fallback;
	}
	
	/**
	 * Check if taxonomy is translatable.
	 *
	 * @from 2.0 $taxonomy should no longer be an array.
	 * @from 1.4.5
	 *
	 * @param string $taxonomy
	 * 
	 * @return boolean
	 */
	public function is_taxonomy_translatable($taxonomy) {
		
		return $this->get_taxonomy_option($taxonomy, 'translatable');
		
	}

	/**
	 * Get taxonomy translatable meta keys.
	 *
	 * @from 2.0
	 *
	 * @param string $taxonomy
	 * 
	 * @return array of strings
	 */
	public function get_taxonomy_metakeys($taxonomy) {
		
		return $this->get_taxonomy_option($taxonomy, 'meta_keys', array());
		
	}

	/**
	 * Get taxonomy translatable fields.
	 *
	 * @from 2.0
	 *
	 * @param string $taxonomy
	 * 
	 * @return array of strings
	 */
	public function get_taxonomy_fields($taxonomy) {
		
		return $this->get_taxonomy_option($taxonomy, 'fields', $this->taxonomy_fields);
		
	}


	/**
	 * Check whether language is main
	 *
	 * @from 2.0 Changed parameters
	 * @from 1.4.7
	 *
	 * @param object WP_Post $language Language. Optional
	 * @return boolean
	 */
	public function is_main($language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
			
		}
		
		return $language && $language->ID == $this->get_option('main');
		
	}
	
	/**
	 * Check whether language is sub-language
	 *
	 * @from 2.0 Changed parameters
	 * @from 1.4.7
	 *
	 * @param object WP_Post $language Language. Optional
	 * @return boolean
	 */
	public function is_sub($language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
			
		}
		
		return $language && $language->ID != $this->get_option('main');
		
	}
	
	/**
	 * Check whether language is default
	 *
	 * @from 2.0 Changed parameters
	 * @from 1.4.7
	 *
	 * @param object WP_Post $language Language. Optional
	 * @return boolean
	 */
	public function is_default($language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
			
		}
		
		return $language && $language->ID == $this->get_option('default');
		
	}
	
	/**
	 * Check whether language is current language.
	 *
	 * @from 1.4.7
	 *
	 * @param object WP_Post $language Language.
	 * @return boolean
	 */
	public function is_current($language) {
		
		return $language && $this->has_language() && $language->ID === $this->get_language()->ID;
		
	}
	
	/**
	 * Find a language by ID, slug, object or anything
	 *
	 * @from 2.0
	 *
	 * @param mixed $language Language object, id or anything. Optional. 
	 * @param string $by Field to search language by. Accepts 'post_name', 'post_title', 'post_content'. Optional. 
	 * @return WP_Post object|false
	 */
	public function find_language($language = null, $by = null) {
		
		if (empty($language)) {
			
			return $this->get_language();
		
		} else if (isset($by)) {
			
			$language_obj = $this->get_language_by($language, $by);
			
		} else if (is_int($language)) {
		
			$language_obj = $this->get_language_by($language, 'ID');
		
		}
			
		if (empty($language_obj)) {
			
			$language_obj = $this->get_language_by($language, 'post_name');
			
			if (empty($language_obj)) {
			
				$language_obj = $this->get_language_by($language, 'post_content');
			
				if (empty($language_obj)) {
			
					$language_obj = $this->get_language_by($language, 'post_title');
			
				}
			
			}
			
		}
		
		if (isset($language_obj)) {
			
			return $language_obj;
		
		}
		
		return false;
	}
	
	
	/**
	 * Get translation post type
	 *
	 * @deprecated from 2.0
	 * @from 1.4.7
	 *
	 * @return boolean
	 */
	public function get_translation_post_type($language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
			
		return $language->post_excerpt;
	}
	
	/**
	 * Get translation taxonomy
	 *
	 * @deprecated from 2.0
	 * @from 1.4.7
	 *
	 * @return boolean
	 */
	public function get_translation_taxonomy($language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
		
		return $language->post_excerpt;
	}
	
	/**
	 * Check whether meta key is translatable
	 *
	 * @from 2.0
	 *
	 * @param string $meta_key Meta key
	 * @return boolean
	 */
	public function is_meta_key_translatable($post_type, $meta_key) {
		
		return $this->is_post_type_translatable($post_type) && in_array($meta_key, $this->get_post_type_metakeys($post_type));
		
	}
	
	/**
	 * Filter translatable taxonomies from array 
	 *
	 * @from 1.4.6
	 *
	 * @param mixed string|array $taxonomies
	 * @return array
	 */
	public function filter_translatable_taxonomies($taxonomies) {
		
		if (!is_array($taxonomies)) {
		
			$taxonomies = array($taxonomies);
			
		} 
		
		return array_intersect($this->get_taxonomies(), $taxonomies);
	}
	
	/**
	 * Filter translatable post-types from post-types array 
	 *
	 * @from 1.4.6
	 *
	 * @param mixed string|array $post_types
	 * @return array
	 */
	public function filter_translatable_post_types($post_types) {
		
		trigger_error('dont use filter_translatable_post_types anymore!');
		
	}
	
	/**
	 * get taxonomy translation
	 *
	 * @from 1.1
	 *
	 * @param string $original_taxonomy. Original taxonomy name (e.g 'category')
	 * @param int $language_id. Language id
	 * @return string|false Translated taxonomy if exists.
	 */
	public function get_taxonomy_translation($original_taxonomy, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
			
		}
		
		$translations = $this->get_option('translations', array());
		
		if ($language && isset($translations['taxonomy'][$original_taxonomy][$language->ID]) && $translations['taxonomy'][$original_taxonomy][$language->ID]) {

			return $translations['taxonomy'][$original_taxonomy][$language->ID];

		}
		
		return false;
	}
	
	/**
	 * Translate taxonomy
	 *
	 * @from 1.1
	 *
	 * @param string $original_taxonomy. Original taxonomy name (e.g 'category')
	 * @param int $language_id. Language id
	 * @param string $fallback
	 * @return string Translated taxonomy
	 */
	public function translate_taxonomy($original_taxonomy, $language = null, $fallback = null) {
		
		$translated_taxonomy = $this->get_taxonomy_translation($original_taxonomy, $language);
		
		if ($translated_taxonomy) {
		
			return $translated_taxonomy;
			
		} else if (isset($fallback)) {
		
			return $fallback;
			
		}
		
		return $original_taxonomy;
	}

	/**
	 * get original taxonomy
	 *
	 * @from 1.1
	 *
	 * @param string $translated_taxonomy. Translated taxonomy name (e.g 'categorie')
	 * @param int $language_id. Language id
	 * @return string Original taxonomy or false.
	 */
	public function get_taxonomy_original($translated_taxonomy, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
			
		}
		
		$translations = $this->get_option('translations', array());
		
		if (isset($translations['taxonomy'])) {
		
			foreach ($translations['taxonomy'] as $original => $translation) {

				if (isset($translation[$language->ID]) && $translation[$language->ID] === $translated_taxonomy) {

					return $original;

				}

			}
			
		}
		
    return false;
	}
	
	/**
	 * Translate custom post type
	 *
	 * @from 1.1
	 *
	 * @param string $original_cpt. Original custom post type name (e.g 'book')
	 * @param int $language_id. Language id
	 * @return string Translated cpt (may be equal to original).
	 */
	public function get_cpt_translation($original_cpt, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
			
		}
		
		$translations = $this->get_option('translations', array());
		
		if ($language && isset($translations['post_type'][$original_cpt][$language->ID]) && $translations['post_type'][$original_cpt][$language->ID]) {
			
			return $translations['post_type'][$original_cpt][$language->ID];

		}
		return false;
		
	}
	
	/**
	 * Translate custom post type
	 *
	 * @from 1.1
	 *
	 * @param string $original_cpt. Original custom post type name (e.g 'book')
	 * @param int $language_id. Language id
	 * @param string $fallback Fallback. Optional
	 * @return string Translated cpt (may be equal to original).
	 */
	public function translate_cpt($original_cpt, $language = null, $fallback = null) {
		
		$translated_cpt = $this->get_cpt_translation($original_cpt, $language);
		
		if ($translated_cpt) {
		
			return $translated_cpt;
			
		} else if (isset($fallback)) {
		
			return $fallback;
			
		}
		
		return $original_cpt;

	}
	
	/**
	 * get original custom post type
	 *
	 * @from 1.1
	 *
	 * @param string $translated_taxonomy. Translated custom post type name (e.g 'livre')
	 * @param int $language_id. Language id
	 * @return string|false
	 */
	public function get_cpt_original($translated_cpt, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
			
		}
		
		$translations = $this->get_option('translations', array());
		
		if ($language && isset($translations['post_type'])) {
		
			foreach ($translations['post_type'] as $original => $translation) {

				if (isset($translation[$language->ID]) && $translation[$language->ID] === $translated_cpt) {

					return $original;

				}

			}
    
    }
    
    return false;

	}
	
	/**
	 * get option translation
	 *
	 * @from 1.5
	 *
	 * @param int $language_id. Language id
	 * @return array
	 */
	public function get_option_translation($language_id, $option_name) {
		
		$translations = $this->get_option('translations', array());
		
		if (isset($translations['option'][$language_id][$option_name]) && $translations['option'][$language_id][$option_name]) {
			
			return $translations['option'][$language_id][$option_name];
			
		}
		
		return false;
		
	}
	
	/**
	 * get all options translations
	 *
	 * @from 1.5
	 *
	 * @return array
	 */
	public function get_option_translations() {
		
		$translations = $this->get_option('translations', array());
		
		if (isset($translations['option'])) {

			return $translations['option'];

		}
		
		return array();
		
	}
	
	/**
	 * get post field translation if it exists
	 *
	 * @from 2.0 Changed parameters
	 * @from 1.1
	 *
	 * @param object WP_Post $post Post to translate field.
	 * @param object WP_Post $language Language.
	 * @param string $field Field name. Accepts 'post_content', 'post_title', 'post_name', 'post_excerpt'
	 * @return string
	 */
	public function get_post_field_translation($post, $field, $language = null) {
		
		if ($this->is_sub($language) && $this->is_post_type_translatable($post->post_type) && in_array($field, $this->get_post_type_fields($post->post_type))) {
			
			return get_post_meta($post->ID, $this->get_prefix($language) . $field, true);
			
		} else {
			
			return $post->$field;
		
		}
		
	}
	
	/**
	 * get post field translation
	 *
	 * @from 2.0 Changed parameters
	 * @from 1.1
	 *
	 * @param object WP_Post $post Post to translate field.
	 * @param object WP_Post $language Language.
	 * @param string $field Field name. Accepts 'post_content', 'post_title', 'post_name', 'post_excerpt'
	 * @param string $fallback Defaut value to return when translation does not exist. Optional.
	 * @return string
	 */
	public function translate_post_field($post, $field, $language = null, $fallback = null) {
		
		$value = $this->get_post_field_translation($post, $field, $language);
		
		if ($value) {
			
			return $value;
		
		} else if (isset($fallback)) {
			
			return $fallback;
		
		} 
			
		return $post->$field;
		
	}
  
	/**
	 * get term field translation if it exists
	 *
	 * @from 2.0 
	 *
	 * @param object WP_Term $term.
	 * @param string $taxonomy.
	 * @param string $field. Accepts 'name', 'slug', 'description'
	 * @param object WP_Post $language.
	 * @return string
	 */
	public function get_term_field_translation($term, $taxonomy, $field, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
		
		if ($this->is_sub($language) && $this->is_taxonomy_translatable($term->taxonomy)) {
			
			return get_term_meta($term->term_id, $this->get_prefix($language) . $field, true);
			
		} else {
			
			return $term->$field;
		
		}
		
	}
	
	/**
	 * Get term field translation
	 *
	 * @from 2.0 Changed parameters
	 * @from 1.1
	 *
	 * @param object WP_Term $term.
	 * @param string $taxonomy.
	 * @param string $field. Accepts 'name', 'slug', 'description'
	 * @param object WP_Post $language.
	 * @param string $fallback Defaut value to return when translation does not exist. Optional.
	 * @return string.
	 */
	public function translate_term_field($term, $taxonomy, $field, $language = null, $fallback = null) {
		
		$value = $this->get_term_field_translation($term, $taxonomy, $field, $language);
		
		if ($value) {
			
			return $value;
		
		} else if (isset($fallback)) {
			
			return $fallback;
		
		} 
			
		return $term->$field;

	}
	
	
	/**
	 * Get post meta translation if it exists
	 *
	 * @from 2.0 
	 *
	 * @param object WP_Post $post Post to translate field.
	 * @param string $meta_key Meta key.
	 * @param bool $single Single meta value.
	 * @param object WP_Post $language Language.
	 * @return string|array|false
	 */
	public function get_post_meta_translation($post, $meta_key, $single, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
		
		if ($this->is_sub($language) && in_array($meta_key, $this->get_post_type_metakeys($post->post_type))) {
			
			return get_post_meta($post->ID, $this->create_prefix($language->post_name) . $meta_key, $single);
			
		} else {
			
			return false;
		
		}
		
	}
	
	/**
	 * Translate post meta
	 *
	 * @param object WP_Post $post Post to translate field.
	 * @param string $meta_key Meta key.
	 * @param bool $single Single meta value.
	 * @param object WP_Post $language Language.
	 * @param string $fallback Fallback to return if no meta value.
	 * @return string|array
	 *
	 * @from 2.0 params changed
	 * @from 1.1
	 */	
	public function translate_post_meta($post, $meta_key, $single, $language = null, $fallback = null) {
		
		$translation = $this->get_post_meta_translation($post, $meta_key, $single, $language);
		
		if ($translation) {
		
			return $translation;
		
		} else if (isset($fallback)) {
			
			return $fallback;
		
		}
		
		return get_post_meta($post->ID, $meta_key, $single);
	}
	
	/**
	 * Get term meta translation if it exists
	 *
	 * @from 2.0 
	 *
	 * @param object WP_Term $term. Term to translate field.
	 * @param string $meta_key Meta key.
	 * @param bool $single Single meta value.
	 * @param object WP_Post $language Language.
	 * @return string|array
	 */
	public function get_term_meta_translation($term, $meta_key, $single, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
		
		if ($this->is_sub($language) && in_array($meta_key, $this->get_taxonomy_metakeys($term->taxonomy))) {
			
			return get_term_meta($term->term_id, $this->create_prefix($language->post_name) . $meta_key, $single);
			
		} else {
			
			return false;
		
		}
		
	}
	
	/**
	 * Translate term meta
	 *
	 * @param object WP_Term $term. Term to translate field.
	 * @param string $meta_key Meta key.
	 * @param bool $single Single meta value.
	 * @param object WP_Post $language Language.
	 * @param string $fallback Fallback to return if no meta value.
	 * @return string|array
	 *
	 * @from 2.0
	 */	
	public function translate_term_meta($term, $meta_key, $single, $language = null, $fallback = null) {
		
		$translation = $this->get_term_meta_translation($term, $meta_key, $single, $language);
		
		if ($translation) {
		
			return $translation;
		
		} else if (isset($fallback)) {
			
			return $fallback;
		
		}
		
		return get_term_meta($term->term_id, $meta_key, $single);
	}
	
}
