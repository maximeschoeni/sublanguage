<?php
/*

Compat version 1.5.4

*/




	
include( plugin_dir_path( __FILE__ ) . 'include/site.php');

global $sublanguage;

$sublanguage = new Sublanguage_site();


/** 
 * Base class for Sublanguage_site in front-end and Sublanguage_admin in admin.
 */
class Sublanguage_main {

	/** 
	 * @from 1.1
	 *
	 * @var float
	 */
	var $version = '1.5.4';
	
	/** 
	 * @from 1.0
	 *
	 * @var string
	 */
	var $option_name = 'sublanguage_options';

	/** 
	 * @from 1.1
	 *
	 * @var string
	 */
	var $translation_option_name = 'sublanguage_translations';

	/**
	 * @from 1.0
	 *
	 * @var string
	 */
	var $language_post_type = 'language';
	
	/**
	 * @from 1.1
	 *
	 * E.g: $language_slug = $_REQUEST[$this->language_query_var]
	 *
	 * @var string
	 */
	var $language_query_var = 'language';
	
	/**
	 * @from 1.0
	 *
	 * @var array
	 */	
	var $languages_cache;

	/** 
	 * Queue for terms to translate
	 *
	 * @from 1.2
	 *
	 * @var array
	 */
	var $post_translation_queue = array();
	
	/**
	 * @from 1.2
	 *
	 * @var array
	 */	
	var $post_translation_cache = array();

	/** 
	 * @from 1.2
	 *
	 * @var string
	 */
	var $post_translation_prefix = 'translation_';

	/** 
	 * Queue for terms to translate
	 *
	 * @from 1.2
	 *
	 * @var array
	 */
	var $term_translation_queue = array();
	
	/**
	 * @from 1.2
	 *
	 * @var array
	 */	
	var $term_translation_cache = array();

	/** 
	 * @from 1.1
	 *
	 * @var string
	 */
	var $term_translation_prefix = 'translation_';

	/**
	 * @from 1.0
	 *
	 * @var array
	 */
	var $current_language = false;
	
	/**
	 * @from 1.1
	 *
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
	 * @var array
	 */
	private $post_types;
	
	/**
	 * @from 1.2
	 *
	 * @var boolean
	 */
	var $disable_translate_home_url = false;
	
	/**
	 * @from 1.4
	 *
	 * @var array
	 */
	var $fields = array('post_content', 'post_name', 'post_excerpt', 'post_title');
	
	/**
	 * @from 1.4.5
	 *
	 * @var array
	 */
	var $disable_postmeta_filter = false;
	
	/**
	 * @from 1.4.6
	 *
	 * @var array
	 */
	var $term_sort_cache;
	
	/**
	 * @from 1.4.6
	 *
	 * @var array
	 */
	var $post_sort_cache;
	
	/**
	 * @var Array
	 *
	 * Used to save original $wp_rewrite->extra_permastructs values
	 *
	 * @from 1.5
	 */
	var $original_permastructs;
	

	/**
	 * Register all filters needed for admin and front-end
	 *
	 * @from 1.4.7
	 */
	public function load() {
				
		add_filter('terms_clauses', array($this, 'filter_terms_clauses'), 10, 3);
		add_filter('posts_clauses', array($this, 'filter_search_query'), 10, 2); // -> added in 1.4.5
		
		add_filter('get_object_terms', array($this, 'filter_get_object_terms'), 10, 4);
		add_filter('get_term', array($this, 'translate_get_term'), 10, 2); // hard translate term
		add_filter('get_terms', array($this, 'translate_get_terms'), 10, 3); // hard translate terms
		add_filter('get_terms', array($this, 'register_taxonomy_terms_order'), 10, 3); // register terms order - added in 1.4.6
		add_filter('get_the_terms', array($this, 'translate_post_terms'), 10, 3);
		add_filter('list_cats', array($this, 'translate_term_name'), 10, 2);
		
		add_filter('the_posts', array($this, 'translate_the_posts'), 10, 2);
		add_filter('the_posts', array($this, 'sort_the_posts'), 11, 2); // -> added in 1.4.6
		
		add_filter('posts_where_request', array($this, 'filter_posts_where'), 10, 2);
		add_action('init', array($this, 'register_translations'));

		add_filter('sublanguage_translate_post_field', array($this, 'translate_post_field_custom'), null, 5);
		add_filter('sublanguage_translate_term_field', array($this, 'translate_term_field_custom'), null, 6);
						
		add_action('sublanguage_enqueue_terms', array($this, 'enqueue_terms'), 10, 2); // -> added in 1.4.6
		add_action('sublanguage_enqueue_posts', array($this, 'enqueue_posts'), 10, 2); // -> added in 1.4.6
		
	}

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
	 * Get array of languages
	 *
	 * @from 1.2
	 *
	 * @return array of WP_post objects
	 */
	public function get_languages() {
		
		if (empty($this->languages_cache)) {
			
			$this->languages_cache = get_posts(array(
				'post_type' => $this->language_post_type,
				'post_status' => 'any',
				'orderby' => 'menu_order' ,
				'order'   => 'ASC',
				'nopaging' => true,
				'update_post_term_cache' => false
			));
			
		}
    
    	return $this->languages_cache;
    
	}
	
	

	/**
	 * Select language object by translation post-type
	 *
	 * @from 1.1
	 *
	 * @param string $post_type.
	 * @return WP_Post object.
	 */
	public function get_language_by_type($post_type) {
		
		$languages = $this->get_languages();
		
		foreach ($languages as $lng) {
			
			if ($this->post_translation_prefix.$lng->ID == $post_type) return $lng;
		
		}
		
		return false;		
		
	}
  
	/**
	 * Select language object by translation taxonomy
	 * 
	 * @from 1.1
	 *
	 * @param string $post_type. 
	 * @return WP_Post object.
	 */
	public function get_language_by_taxonomy($taxonomy) {
		
		$languages = $this->get_languages();
		
		foreach ($languages as $lng) {
			
			if ($this->term_translation_prefix.$lng->ID == $taxonomy) return $lng;
		
		}
		
		return false;		
	
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
	 * get array of one field values for all languages
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
	 * @from 1.4.4
	 *
	 * @param string $column.
	 * @return array
	 */
	public function get_language_post_types() {
		
		$languages = $this->get_languages();
		$post_types = array();
		
		foreach ($languages as $lng) {
			
			$post_types[] = $this->post_translation_prefix.intval($lng->ID);
			
		}
		
		return $post_types;
	}

	/**
	 * Get translatable taxonomies 
	 *
	 * @from 1.4.7
	 *
	 * @return WP_Post object
	 */
	public function get_taxonomies() {
		
		if (!isset($this->taxonomies)) {
		
			$this->taxonomies = $this->get_option('taxonomy', apply_filters('sublanguage_register_taxonomy', array()));
			
		}
		
		return $this->taxonomies;
	}
	
	/**
	 * Get translatable post_types 
	 *
	 * @from 1.4.7
	 *
	 * @return WP_Post object
	 */
	public function get_post_types() {
		
		if (!isset($this->post_types)) {
		
			$this->post_types = $this->get_option('cpt', apply_filters('sublanguage_register_cpt', array()));
			
		}
		
		return $this->post_types;
	}
	
	/**
	 * Get list of translatable meta keys
	 *
	 * @from 1.5
	 */	
	public function get_postmeta_keys() {
		
		if (empty($this->postmeta_keys)) {
			
			/**
			 * Register an array of default translatable post meta data
			 * This is to be overrided by options from 1.5.
			 *
			 * @from 1.0
			 *
			 * @param array of strings. Array containing the post_meta keys.
			 */
			$this->postmeta_keys = $this->get_option('meta_keys', apply_filters('sublanguage_register_postmeta_key', array()));
			
		}
		
		return $this->postmeta_keys;
		
	}
	
	/**
	 * Check whether language is main
	 *
	 * @from 1.4.7
	 *
	 * @return boolean
	 */
	public function is_main($language_id = null) {
		
		if (isset($language_id)) {
			
			return $language_id == $this->get_option('main');
		
		} else if ($this->current_language) {
			
			return $this->current_language->ID == $this->get_option('main');
		
		}
		
		return false;
	}
	
	/**
	 * Check whether language is sub-language
	 *
	 * @from 1.4.7
	 *
	 * @return boolean
	 */
	public function is_sub($language_id = null) {
		
		if (isset($language_id)) {
			
			return $language_id != $this->get_option('main');
		
		} else if ($this->current_language) {
			
			return $this->current_language->ID != $this->get_option('main');
		
		}
		
		return false;
	}
	
	/**
	 * Check whether language is sub-language
	 *
	 * @from 1.5.2 $language_id may be an object
	 * @from 1.4.7
	 *
	 * @return boolean
	 */
	 public function is_default($language_id = null) {
		
		if (isset($language_id)) {
			
			// @from 1.5.2
			if (is_object($language_id)) {
				
				$language_id = $language_id->ID;
			
			}
			
			return $language_id == $this->get_option('default');
		
		} else if ($this->current_language) {
			
			return $this->current_language->ID == $this->get_option('default');
		
		}
		
		return false;
	}

	/**
	 * Check whether language is current language
	 *
	 * @from 1.4.7
	 *
	 * @return boolean
	 */
	public function is_current($language) {
		
		return $this->current_language && $language->ID === $this->current_language->ID;
		
	}	
	
	/**
	 * Get translation post type
	 *
	 * @from 1.4.7
	 *
	 * @return boolean
	 */
	public function get_translation_post_type($language = null) {
		
		if (empty($language)) {
			
			if ($this->current_language) return $this->post_translation_prefix.$this->current_language->ID;
		
		} else {
			
			return $this->post_translation_prefix.$language->ID;
			
		}

		return $this->post_translation_prefix . '0';
	}	
	
	/**
	 * Get translation taxonomy
	 *
	 * @from 1.4.7
	 *
	 * @return boolean
	 */
	public function get_translation_taxonomy($language = null) {
		
		if (empty($language)) {
			
			if ($this->current_language) return $this->term_translation_prefix.$this->current_language->ID;
		
		} else {
			
			return $this->term_translation_prefix.$language->ID;
			
		}

		return $this->term_translation_prefix . '0';
	}
	
	/**
	 * Find whether taxonomy or taxonomies array is translatable. 
	 *
	 * @from 1.4.5
	 *
	 * @param mixed string|array $taxonomies
	 * @return boolean
	 */
	public function is_taxonomy_translatable($taxonomies) {
		
		return ((is_string($taxonomies) && in_array($taxonomies, $this->get_taxonomies()))
			|| (is_array($taxonomies) && !array_diff($taxonomies, $this->get_taxonomies())));
		
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
	 * Find whether post-type or post-types array is translatable. 
	 *
	 * @from 1.4.5
	 *
	 * @param mixed string|array $taxonomies
	 * @return boolean
	 */
	public function is_post_type_translatable($post_type) {
		
		return ((is_string($post_type) && ($post_type == 'any' || empty($post_type) || in_array($post_type, $this->get_post_types()))) || (is_array($post_type) && !array_diff($post_type, $this->get_post_types())));
		
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
		
		if (is_array($post_types)) {
		
			return array_intersect($this->get_post_types(), $post_types);
			
		} else if (!$post_types || $post_types === 'any') {
			
			return $this->get_post_types();
		
		} else if (is_string($post_types) && in_array($post_types, $this->get_post_types())) {
			
			return array($post_types);
		
		}
		
		return array();
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
	public function get_taxonomy_translation($original_taxonomy, $language_id) {
		
		$translations = get_option($this->translation_option_name);
		
		if (isset($translations['taxonomy'][$language_id][$original_taxonomy]) 
			&& $translations['taxonomy'][$language_id][$original_taxonomy]) {

			return $translations['taxonomy'][$language_id][$original_taxonomy];

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
	public function translate_taxonomy($original_taxonomy, $language_id, $fallback) {
		
		$translated_taxonomy = $this->get_taxonomy_translation($original_taxonomy, $language_id);
		
		return ($translated_taxonomy === false) ? $fallback : $translated_taxonomy;
		
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
	public function get_taxonomy_original($translated_taxonomy, $language_id) {
		
    $translations = get_option($this->translation_option_name);
		
		if (isset($translations['taxonomy'][$language_id])) {
		
			foreach ($translations['taxonomy'][$language_id] as $original => $translation) {

				if ($translation == $translated_taxonomy) {

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
	public function get_cpt_translation($original_cpt, $language_id) {
		
		$translations = get_option($this->translation_option_name);
		
		if (isset($translations['cpt'][$language_id][$original_cpt]) 
			&& $translations['cpt'][$language_id][$original_cpt]) {

       return $translations['cpt'][$language_id][$original_cpt];

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
	 * @param string $fallback
	 * @return string Translated cpt (may be equal to original).
	 */
	public function translate_cpt($original_cpt, $language_id, $fallback) {
		
		$translated_cpt = $this->get_cpt_translation($original_cpt, $language_id);
		
		if ($translated_cpt) {
		
			return $translated_cpt;
			
		}
		
		return $fallback;

	}
	
	/**
	 * Get original (not translated) post type slug
	 *
	 * @from 1.5.5
	 *
	 * @param string $post_type. Post type name
	 * @return string
	 */
	public function get_post_type_slug($post_type) {

		$post_type_data = get_post_type_object( $post_type );

		$post_type_slug = $post_type_data->rewrite['slug'];

		return $post_type_slug;
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
	public function get_cpt_original($translated_cpt, $language_id) {
		
    	$translations = get_option($this->translation_option_name);
		
		if (isset($translations['cpt'][$language_id])) {
		
			foreach ($translations['cpt'][$language_id] as $original => $translation) {

				if ($translation == $translated_cpt) {

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
		
		$translations = get_option($this->translation_option_name);
		
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
		
		$translations = get_option($this->translation_option_name);
		
		if (isset($translations['option'])) {

			return $translations['option'];

		}
		
		return array();
		
	}
	

	
	
  
 /********* POST TRANSLATION *********/   




	
	/**
	 * filter search query
	 *
	 * filter for 'posts_clauses'
	 *
	 * @from 1.4.5
	 * @from 1.5.3 don't allow filter anymore for query_vars 'name' or 'postname'. 
	 */
	public function filter_search_query($pieces, $query) {
		global $wpdb;
		
		if (!empty($query->query_vars['s'])) {
		
			$post_types = $this->filter_translatable_post_types($query->query_vars['post_type']);
		
			if ($post_types) { // -> language is not main and post-type is translatable
			
				/**
				 * Filter language search mode
				 *
				 * @param string:
				 * 'self' 	=> search only in current language
				 * 'parent' => search in current language and in original language
				 * 'all' 	=> search in all languages
				 *
				 * @from 1.0
				 */		
				$search_mode = apply_filters('sublanguage_post_search_mode', 'self'); // 'self', 'parent', 'all'
			
				if ($search_mode == 'all' || $this->is_sub()) {
					
					$args = $query->query_vars;
				
					$args['post_type'] = ($search_mode == 'all') ? $this->get_language_post_types() : $this->get_translation_post_type();
					$args['fields'] = 'id=>parent';
				
					$translations = new WP_Query($args);
				
					if ($translations->have_posts()) {
					
						$parent_ids = array();
					
						foreach ($translations->posts as $item) {
						
							$parent_ids[] = intval($item->post_parent);
						
						}
						
						$sql_post_types = " AND $wpdb->posts.post_type in ('" . implode("', '", array_map('esc_sql', $post_types)) . "')";
						
						if ($search_mode == 'self') {
							
							$pieces['where'] = " AND $wpdb->posts.ID in (" . implode(', ', $parent_ids) . ")" . $sql_post_types;
							
						} else {
						
							$pieces['where'] .= " OR ($wpdb->posts.ID in (" . implode(', ', $parent_ids) . ")" . $sql_post_types . ")";
						
						}
						
						//$query->translations = $translations->posts;
					
					}
					
				}
				
			}

		}
		
		return $pieces;
	}
	
	/**
	 * enqueue post translation
	 *
	 * @from 1.1
	 *
	 * @param int|array $ids. Post ids.
	 */
	public function enqueue_translation($ids, $lngs = null) {

		if (empty($lngs)) {
			
			$lngs = $this->get_language_column('ID');
		
		} else if (!is_array($lngs)) {
			
			$lngs = array($lngs);
		
		}
		
		if (!is_array($ids)) {
			
			$ids = array($ids);
		
		}
		
		foreach ($lngs as $lng_id) {
			
			if ($this->is_sub($lng_id)) {
			
				foreach ($ids as $id) {
				
					if (!isset($this->post_translation_cache[$lng_id][$id])) {
				
						$this->post_translation_queue[$lng_id][$id] = true;
				
					}
				
				}
				
			}
			
		}
		
	}

	/**
	 * enqueue posts array for translation
	 *
	 * @from 1.4.6
	 *
	 * @param int|array $ids. Post ids.
	 */
	public function enqueue_post_id($id, $lngs = null) {

		if (empty($lngs)) {
			
			$lngs = $this->get_language_column('ID');
		
		} else if (!is_array($lngs)) {
			
			$lngs = array($lngs);
		
		}
		
		foreach ($lngs as $lng_id) {
			
			if ($this->is_sub($lng_id) && !isset($this->post_translation_cache[$lng_id][$id])) {
				
				$this->post_translation_queue[$lng_id][$id] = true;
				
			}
			
		}
		
	}

	/**
	 * enqueue posts array for translation
	 *
	 * @from 1.4.6
	 *
	 * @param int|array $ids. Post ids.
	 */
	public function enqueue_posts($posts, $lngs = null) {

		if (empty($lngs)) {
			
			$lngs = $this->get_language_column('ID');
		
		} else if (!is_array($lngs)) {
			
			$lngs = array($lngs);
		
		}
		
		foreach ($lngs as $lng_id) {
			
			if ($this->is_sub($lng_id)) {
			
				foreach ($posts as $post) {
				
					if (!isset($this->post_translation_cache[$lng_id][$post->ID])) {
				
						$this->post_translation_queue[$lng_id][$post->ID] = true;
				
					}
				
				}
				
			}
			
		}
		
	}

	/**
	 * translate queue
	 *
	 * @from 1.1
	 */
	public function translate_queue() {
   
		if ($this->post_translation_queue) {
			
			$translations = get_posts(array(
				'sublanguage_query' => $this->post_translation_queue,
				'suppress_filters' => false,
				'posts_per_page' => -1,
				'no_found_rows' => true,
				'nopaging' => true,
				'update_post_meta_cache' => (bool) $this->get_postmeta_keys(),
				'update_post_term_cache' => false
			));	
			
			foreach ($translations as $translation) {
				 
				$language = $this->get_language_by_type($translation->post_type);
				
				if ($language) {
				
					$this->post_translation_cache[$language->ID][$translation->post_parent] = $translation;
					unset($this->post_translation_queue[$language->ID][$translation->post_parent]);
				
				}
				
			}
			
			// set false when query have no translation (avoid further queries)
	  
			foreach ($this->post_translation_queue as $lng_id => $translations) {
		
				foreach ($translations as $id => $translation) {
	
					$this->post_translation_cache[$lng_id][$id] = false; 

				}
	  
			}
      
			$this->post_translation_queue = array();
		
		}
		
	}

	/**
	 * uncache translation
	 *
	 * @from 1.2
	 *
	 * @param int. Post id.
	 */
	public function uncache_post_translation($post_id, $lng_id) {
		
		unset($this->post_translation_cache[$lng_id][$post_id]);
		
	}

	/**
	 * get post translation. (get from cache if available)
	 *
	 * @from 1.1
	 *
	 * @param int $post_id. original post id.
	 * @param int $language_id. Language id.
	 * @return WP_Post|false
	 */
	public function get_post_translation($post_id, $language_id) {
		
		// added in 1.4
		if ($this->is_main($language_id)) {
			
			return get_post($post_id);
			
		}
		
		if (in_array(get_post($post_id)->post_type, $this->get_post_types())) { // -> should be done before
			
			if (!isset($this->post_translation_cache[$language_id][$post_id])) {
				
				$this->enqueue_post_id($post_id, $language_id);
				$this->translate_queue();
			
			} 
			
			return $this->post_translation_cache[$language_id][$post_id];
			
		}
		
		return false;

	}
	
	/**
	 * get post field translation
	 *
	 * @from 1.1
	 *
	 * @param int $post_id. original post id.
	 * @param int $language_id. Language id.
	 * @param string $field. (post_content, post_title, post_name, post_excerpt)
	 * @param string $fallback.
	 * @return string.
	 */
	public function translate_post_field($post_id, $language_id, $field, $fallback) {
		
		if ($this->is_sub($language_id) && in_array($field, $this->fields)) { // added in 1.4
		
			$translation = $this->get_post_translation($post_id, $language_id);

			if ($translation && isset($translation->$field) && $translation->$field) {
	
				return $translation->$field;
		
			}
		
		}
		
		return $fallback;
	
	}
  
	/**
	 *	build 'where' clause from sublanguage query when quering posts
	 *	Filter for 'posts_where_request'
	 *
	 * @from 1.0
	 */
	public function filter_posts_where($where, $query) {
		global $wpdb;
		
		if (isset($query->query_vars['sublanguage_query'])) {
						
			$conditions = array();
			
			foreach ($query->query_vars['sublanguage_query'] as $lng_id => $ids) {
			
				$ids = array_map('intval', array_keys($ids));
				$conditions[] = "$wpdb->posts.post_type = '".$this->post_translation_prefix.intval($lng_id)."' AND $wpdb->posts.post_parent ".(count($ids) === 1 ? '= '.$ids[0] : 'IN ('.implode(',', $ids).')');
			
			}
			
			$where = ' AND '.((count($conditions) === 1) ? $conditions[0] : '((' . implode(') OR (', $conditions) . '))');
			
		}
		
		return $where;
	
	}

  
  
  
  
 /********* TERM TRANSLATION *********/ 
  

	
	/**
	 * enqueue terms to translate
	 *
	 * @from 1.4.6
	 *
	 * @param int|array $ids. Term ids.
	 * @param int|array $lngs. Language ids.
	 */
	public function enqueue_terms($terms, $lngs = null) {
		
		if (is_int($terms)) {
			
			$this->enqueue_term_id($terms, $lngs);
		
		}
		
		if (empty($lngs)) {
			
			$lngs = $this->get_language_column('ID');
		
		} else if (!is_array($lngs)) {
			
			$lngs = array($lngs);
		
		}
		
		foreach ($lngs as $lng_id) {
			
			if ($this->is_sub($lng_id)) {
			
				foreach ($terms as $term) {
					
					if ($this->is_taxonomy_translatable($term->taxonomy) && !isset($this->term_translation_cache[$lng_id][$term->term_id])) {
				
						$this->term_translation_queue[$lng_id][$term->term_id] = true;
				
					}
				
				}
				
			}
			
		}
		
	}
	
	/**
	 * enqueue term to translate
	 *
	 * @from 1.4.6
	 *
	 * @param int|array $ids. Term ids.
	 * @param int|array $lngs. Language ids.
	 */
	public function enqueue_term_id($id, $lngs = null) {
		
		if (empty($lngs)) {
			
			$lngs = $this->get_language_column('ID');
		
		} else if (!is_array($lngs)) {
			
			$lngs = array($lngs);
		
		}
		
		foreach ($lngs as $lng_id) {
			
			if ($this->is_sub($lng_id) && !isset($this->term_translation_cache[$lng_id][$id])) {
				
				$this->term_translation_queue[$lng_id][$id] = true;
				
			}
			
		}
		
	}
	  
	/**
	 * translate term queue
	 *
	 * @from 1.1
	 */
	public function translate_term_queue() {

		if ($this->term_translation_queue) {
			
			// set all translations taxonomy ()
			$taxonomies = array();
			
			foreach ($this->term_translation_queue as $lng_id => $translations) {
				
				$taxonomies[] = $this->term_translation_prefix.$lng_id;
			
			}
		
			$terms = get_terms($taxonomies, array(
				'hide_empty' => false,
				'sublanguage_query' => $this->term_translation_queue,
				'cache_domain' => 'sublanguage_'.md5(serialize($this->term_translation_queue))
			));
	 
			foreach ($terms as $term) {
		
				$language = $this->get_language_by_taxonomy($term->taxonomy);
				$this->term_translation_cache[$language->ID][$term->parent] = $term;

				unset($this->term_translation_queue[$language->ID][$term->parent]);
		
			}

			// set false queries that have no translation (avoid further queries)

			foreach ($this->term_translation_queue as $lng_id => $translations) {

				foreach ($translations as $id => $translation) {
	
					$this->term_translation_cache[$lng_id][$id] = false; 

				}

			}

			$this->term_translation_queue = array();

		}
		
	} 
 

	/**
	 * get term translation. (get from cache if available)
	 *
	 * @from 1.1
	 *
	 * @param object $term. Term object
	 * @param int $language_id. Language id.
	 * @return object|false
	 */
	public function get_term_translation($term, $taxonomy, $language_id) {
		
		if ($this->is_taxonomy_translatable($taxonomy)) {
			
			if ($this->is_sub($language_id)) {
			
				if (!isset($this->term_translation_cache[$language_id][$term->term_id])) {
				
					if (empty($this->term_translation_cache[$language_id][$term->term_id])) {
					
						$this->enqueue_term_id($term->term_id, $language_id);
				
					}
				
					$this->translate_term_queue();
			
				}
				
				return $this->term_translation_cache[$language_id][$term->term_id];
			
			} else {
				
				return $term;
				
			}
			
		}
		
    	return false;
	
	}  

	/**
	 * get term field translation
	 *
	 * @from 1.1
	 *
	 * @param object $term. Term object.
	 * @param int $language_id. Language id.
	 * @param string $field. (name, slug, description)
	 * @param string $fallback.
	 * @return string.
	 */
	public function translate_term_field($term, $taxonomy, $language_id, $field, $fallback) {
	
		$translation = $this->get_term_translation($term, $taxonomy, $language_id);
	
		if ($translation && isset($translation->$field) && $translation->$field) {

			return $translation->$field;
	
		}

		return $fallback;

	} 
  
 
  
	/**
	 *	build 'where' clause from sublanguage query when quering terms
	 *	Filter for 'terms_clauses'
	 *
	 * @from 1.0
	 */
	public function filter_terms_clauses($clauses, $taxonomies, $args) {
		
		if (isset($args['sublanguage_query'])) {
				
			$conditions = array();
			
			foreach ($args['sublanguage_query'] as $lng_id => $ids) {
			
				$ids = array_map('intval', array_keys($ids));
				$conditions[] = "(tt.taxonomy = '".$this->term_translation_prefix.intval($lng_id)."' AND tt.parent ".(count($ids) === 1 ? '= '.$ids[0] : 'IN ('.implode(',', $ids).')').')';
			
			}
			
			$clauses['where'] = count($conditions) === 1 ? $conditions[0] : '(' . implode(' OR ', $conditions) . ')';
		
		}
		
		return $clauses;
	
	}
	
	/**
	 *	@from 1.1
	 */
	public function register_translations() {
		
		$languages = $this->get_languages();
		
		foreach ($languages as $lng) {
			
			register_post_type($this->post_translation_prefix.$lng->ID, array(
				'labels' => array(
					'name' => sprintf(__('Translations - %s', 'sublanguage'), $lng->post_title)
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => false,
				'rewrite'						 => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'supports'           => array('title', 'editor', 'revisions')
			));
			
			register_taxonomy($this->term_translation_prefix.$lng->ID, array(), array(
				'hierarchical'      => true,
				'labels'            => array(
					'name' => sprintf(__('Translations - %s', 'sublanguage'), $lng->post_title)
				),
				'show_ui'           => false,
				'show_admin_column' => false,
				'public' 			=> false,
				'query_var'         => false,
				'rewrite'           => false,
			
			));
			
		}
		
	}
	
	/**
	 * Translate post. Public API
	 *
	 * filter for 'sublanguage_translate_post'
	 *
	 * @param object WP_post
	 * @param int|string|array. language id or slug or array
	 *
	 * @from 1.1
	 */	
	public function translate_post($post, $language = null) {
		
		if (!isset($language)) {
			
			$language = $this->current_language;
		
		} else if (is_int($language)) {
			
			$language = $this->get_language_by($language, 'ID');
			
		} else if (is_string($language)) {
			
			$language = $this->get_language_by($language, 'post_name');
		
		}
		
		$translation = $post;
		
		if ($language) {
		
			foreach ($this->fields as $field) {
				
				$translation->$field = $this->translate_post_field($post->ID, $language->ID, $field, $post->$field);
			
			}
			
		}
		
		return $translation;
	}
	
	/**
	 * Translate post meta. Public API
	 *
	 * filter for 'sublanguage_translate_post_meta'
	 *
	 * @param object WP_post
	 * @param int|string language id or slug
	 *
	 * @from 1.1
	 */	
	public function translate_post_meta($translation, $post_id, $meta_key, $single = false, $language = null) {
		
		if ($this->is_sub($language->ID)) {
		
			$temp_language = $this->current_language; // save current language
		
			if (isset($language)) {
			
				if (is_int($language)) {
			
					$this->current_language = $this->get_language_by($language, 'ID');
			
				} else if (is_string($language)) {
			
					$this->current_language = $this->get_language_by($language, 'post_name');
		
				}
			
			}
		
			$translation = get_post_meta($post_id, $meta_key, $single);
		
			$this->current_language = $temp_language; // restore current language
		
		}
		
		return $translation;
	}
	

 	/********* TRANSLATE FILTERS *********/ 

	
	
	/**
	 *	Translate title to current language
	 *	Filter for 'the_title'
	 *
	 * @from 1.0
	 */
	public function translate_post_title($title, $id) {
		
		if ($this->is_sub()) {
			
			$post = get_post($id);
			
			return $this->translate_post_field($post->ID, $this->current_language->ID, 'post_title', $title);
		
		}
		
		return $title;
	}
	
	/**
	 *	Translate content to current language
	 *	Filter for 'the_content'
	 *
	 * @from 1.0
	 * @from 1.5.4 Don't retranslate 'hard translated' posts
	 */	
	public function translate_post_content($content) {
		global $post;
		
		if ($this->is_sub() && empty($post->translated)) {
			
			return $this->translate_post_field($post->ID, $this->current_language->ID, 'post_content', $content);
		
		}
		
		return $content;
	}

	/**
	 *	Translate excerpt to current language
	 *	Filter for 'get_the_excerpt'
	 *
	 * @from 1.0
	 * @from 1.5.4 Don't retranslate 'hard translated' posts
	 */	
	public function translate_post_excerpt($excerpt) {
		global $post;
		
		if ($this->is_sub() && empty($post->translated)) {
			
			return $this->translate_post_field($post->ID, $this->current_language->ID, 'post_excerpt', $excerpt);
		
		}
		
		return $excerpt;
	}
    
	/**
	 *	Translate page title in wp_title()
	 *	Filter for 'single_post_title'
	 *
	 * @from 1.0
	 * @from 1.5.4 Don't retranslate 'hard translated' posts
	 */
	public function translate_single_post_title($title, $post) {
		
		if ($this->is_sub() && empty($post->translated)) {
			
			return $this->translate_post_field($post->ID, $this->current_language->ID, 'post_title', $title);
		
		}
		
		return $title;
	}

	/**
	 *	Public API
	 *	Filter for 'sublanguage_translate_post_field'
	 *
	 * @from 1.0
	 */
	public function translate_post_field_custom($original, $post, $field, $language = null, $by = null) {
		
		if ($this->is_post_type_translatable($post->post_type)) {
			
			if (!isset($language)) {
				
				$language = $this->current_language;
				
			} else if (!$language) {
			
				$language = $this->get_language_by($this->get_option('main'), 'ID');
			
			} else if (is_int($language)) {
			
				$language = $this->get_language_by($language, 'ID');
			
			} else if (is_string($language)) {
				
				if (empty($by)) {
					
					$by = 'post_name';
					
				}
				
				$language = $this->get_language_by($language, $by);
			
			}
			
			if (!is_a($language, 'WP_Post')) {
				
				return $original;
			
			}
			
			return $this->translate_post_field($post->ID, $language->ID, $field, $original);
		
		}
		
		return $original;
	}
	
	
	/**
	 * Translate term field. Public API
	 * Filter for 'sublanguage_translate_term_field'
	 *
	 * @param string $original. Default value
	 * @param object WP_Term $term. Term object
	 * @param string $taxonomy. Taxonomy
	 * @param string $field. Field to translate ('name', 'slug' or 'description')
	 * @param mixed $language. Language. Null => current language. False => main language.
	 * @param string $by. Language search mode ('post_name', 'post_title', 'post_content'). Default: 'post_name'.
	 *
	 * @from 1.4.6
	 */
	public function translate_term_field_custom($original, $term, $taxonomy, $field, $language = null, $by = null) {
		
		if ($this->is_taxonomy_translatable($taxonomy)) {
			
			if (!isset($language)) {
				
				$language = $this->current_language;
				
			} else if (!$language) {
			
				$language = $this->get_language_by($this->get_option('main'), 'ID');
			
			} else if (is_int($language)) {
			
				$language = $this->get_language_by($language, 'ID');
			
			} else if (is_string($language)) {
				
				if (empty($by)) {
					
					$by = 'post_name';
					
				}
				
				$language = $this->get_language_by($language, $by);
			
			}
			
			if (!is_a($language, 'WP_Post')) {
				
				return $original;
			
			}
			
			if (!$this->is_current($language)) {
				
				$current_language = $this->current_language;
				
				$this->current_language = $language;
				
				$term = get_term($term->term_id, $taxonomy);
				
				$this->current_language = $current_language;
			
			}
			
			return $this->translate_term_field($term, $taxonomy, $language->ID, $field, $term->$field);
		
		}
		
		return $original;
	}
	
	
	
	/** 
	 * hard translate posts for quick edit
	 *
	 * Hook for 'the_posts'
	 *
	 * @from 1.2
	 */
	public function hard_translate_posts($posts) {
		
		foreach ($posts as $post) {
			
			$this->hard_translate_post($post);
	
		}
		
		return $posts;
	}
	
	/**
	 * hard translate post for quick edit
	 *
	 * Hook for 'the_post' (triggered on setup_postdata)
	 *
	 * @from 1.2
	 * @from 1.5.4 add property 'translated' to translated posts
	 */	
	public function hard_translate_post($post) {
		
		if ($this->is_sub() && in_array($post->post_type, $this->get_post_types())) {
			
			foreach ($this->fields as $field) {
				
				$post->$field = $this->translate_post_field($post->ID, $this->current_language->ID, $field, $post->$field);
				
			}
			
			$post->translated = true;
		}
		
		return $post;
	}	
	
	/**
	 * enqueue posts for translate
	 *
	 * Filter for 'the_posts'
	 *
	 * @from 1.1
	 */
	public function translate_the_posts($posts, $wp_query = null) {
		
		if ($this->is_sub()) {
		
			foreach ($posts as $post) {
			
				if (in_array($post->post_type, $this->get_post_types())) {
				
					$this->enqueue_post_id($post->ID, $this->current_language->ID);
				
					// added in 1.4
					if ($post->post_parent) {
					
						$this->enqueue_post_id($post->post_parent, $this->current_language->ID);
				
					}
				
				} 
			
			}
			
		}
		
		return $posts;
	}

	/**
	 * Sort translated posts when ordered by title or name
	 *
	 * Filter for 'the_posts'
	 *
	 * @from 1.1
	 */
	public function sort_the_posts($posts, $wp_query = null) {
	
		if ($this->is_sub() && isset($wp_query->query_vars['orderby']) && is_string($wp_query->query_vars['orderby']) && in_array($wp_query->query_vars['orderby'], array('name', 'title')) && $this->filter_translatable_post_types($wp_query->query_vars['post_type'])) {
			
			$this->post_sort_cache = array(
				'order' => (empty($wp_query->query_vars['order']) || $wp_query->query_vars['order'] === 'ASC' ? 1 : -1),
				'orderby' => ($wp_query->query_vars['orderby'] == 'name' ? 'post_name' : 'post_title')
			);
			
			usort($posts, array($this, 'sort_posts'));
		}
		
		return $posts;
	}
	
	/**
	 * Sort by value
	 * Callback for usort function
	 *
	 * @from 1.4.6
	 *
	 * @param object WP_Term $term
	 */
	public function sort_posts($item_a, $item_b) {
		
		return strcasecmp(
			$this->translate_post_field($item_a->ID, $this->current_language->ID, $this->post_sort_cache['orderby'], $item_a->{$this->post_sort_cache['orderby']}),
			$this->translate_post_field($item_b->ID, $this->current_language->ID, $this->post_sort_cache['orderby'], $item_b->{$this->post_sort_cache['orderby']})
		)*$this->post_sort_cache['order'];
		
	}
	
	
	
	/**
	 *	Translate term name
	 *	Filter for 'list_cats'
	 *
	 * @from 1.0
	 */
	public function translate_term_name($name, $term = null) {
		
		if (!isset($term)) { // looks like there is 2 differents list_cats filters
			
			return $name;
			
		}
		
		if ($this->is_taxonomy_translatable($term->taxonomy) && $this->is_sub()) {
		
			return $this->translate_term_field($term, $term->taxonomy, $this->current_language->ID, 'name', $name);
			
		}
		
		return $name;
	}
	
	/**
	 *	Translate term name
	 *	Filter for 'single_cat_title', single_tag_title, single_term_title 
	 *	In single_term_title()
	 *
	 * @from 1.0
	 */
	public function filter_single_term_title($title) {
		$term = get_queried_object();
		
		if ($this->is_taxonomy_translatable($term->taxonomy) && $this->is_sub()) {
		
			return $this->translate_term_field($term, $term->taxonomy, $this->current_language->ID, 'name', $title);
		
		}
		
		return $title;	
	}

	/**
	 *	Filter post terms
	 *  for 'get_the_terms'
	 *  in get_the_terms()
	 *
	 * @from 1.0
	 */
	public function translate_post_terms($terms, $post_id, $taxonomy) {
		
		if ($this->is_sub() && $this->is_taxonomy_translatable($taxonomy)) {
		
			foreach ($terms as $term) {
		
				$this->translate_term($term, $this->current_language);
		
			}
			
			if ($this->has_taxonomy_terms_order($taxonomy)) { // -> added in 1.4.6
			
				usort($terms, array($this, 'sort_terms'));
			
			}
			
		}
		
		return $terms;
		
	}

	/**
	 * Hard translate term
	 * Filter for 'get_term'
	 *
	 * @from 1.2
	 */		
	public function translate_get_term($term, $taxonomy) {
		
		if ($this->is_sub() && $this->is_taxonomy_translatable($taxonomy)) {
		
			$this->translate_term($term, $this->current_language);
			
		}
		
		return $term;
		
	}

	/**
	 * Filter post terms. Hard translate
	 * for 'get_terms'
	 *
	 * @from 1.1
	 */		
	public function translate_get_terms($terms, $taxonomies, $args) {
		
		if ($this->is_sub() && $this->filter_translatable_taxonomies($taxonomies)) {
			
			if (isset($args['fields']) && $args['fields'] == 'names') { // -> Added in 1.4.4
		
				$terms = array(); // -> restart query
		
				unset($args['fields']);
		
				$results = get_terms($taxonomies, $args);
		
				foreach ($results as $term) {
			
					$terms[] = $this->translate_term_field($term, $term->taxonomy, $this->current_language->ID, 'name', $term->name);
		
				}
		
				return $terms;
			}
	
			if (empty($args['fields']) || $args['fields'] == 'all' || $args['fields'] == 'all_with_object_id') { // -> $terms may be an array of ids !
		
				$this->enqueue_terms($terms, $this->current_language->ID);
		
				foreach ($terms as $term) {
		
					$this->translate_term($term, $this->current_language);
				
				}
			
			}
			
		}
		
		return $terms;
	}

	/**
	 * Register terms order for queried taxonomies
	 * 
	 * filter for 'get_terms'
	 *
	 * @from 1.4.6
	 */		
	public function register_taxonomy_terms_order($terms, $taxonomies, $args) {
		
		if ($this->is_sub() && isset($args['orderby']) && in_array($args['orderby'], array('name', 'slug', 'description'))) {
			
			foreach ($this->filter_translatable_taxonomies($taxonomies) as $taxonomy) {
				
				$this->term_sort_cache[$taxonomy] = array(
					'order' => (empty($args['order']) || $args['order'] == 'ASC' ? 1 : -1),
					'orderby' => $args['orderby']
				);
				
			}
			
		}
		
		return $terms;
		
	}

	/**
	 * Check if taxonomies have registered terms order
	 * 
	 * @params string|array $taxonomies. Taxonomy or taxonomies.
	 * @return boolean.
	 *
	 * @from 1.4.6
	 */		
	public function has_taxonomy_terms_order($taxonomies) {
		
		if (!is_array($taxonomies)) {
				
			$taxonomies = array($taxonomies);
			 
		}
		
		foreach ($taxonomies as $taxonomy) {
			
			if (empty($this->term_sort_cache[$taxonomy])) {
				
				return false;
			
			}
			
		}
			
		return true;
	}
	
	/**
	 * Sort by translated value
	 * Callback for usort function
	 *
	 * @from 1.4.6
	 *
	 * @param object WP_Term $term
	 */
	public function sort_terms($term_a, $term_b) {
		
		return strcasecmp(
			$term_a->{$this->term_sort_cache[$term_a->taxonomy]['orderby']}, 
			$term_b->{$this->term_sort_cache[$term_b->taxonomy]['orderby']}
		)*$this->term_sort_cache[$term_a->taxonomy]['order'];
		
	}
	 
	 

	/**
	 *	Translate tag cloud
	 *  Filter for 'tag_cloud_sort'
	 * @from 1.0
	 */
	public function translate_tag_cloud($tags, $args) {
		
		if ($this->is_sub()) {
		
			foreach ($tags as $term) {
			
				$this->translate_term($term, $this->current_language);
			
			}
			
		}
		
		return $tags;
		
	}
	
	/**
	 *	Hard translate term
	 *  
	 * @from 1.2
	 */
	public function translate_term($term, $language) {
		
		if ($this->is_taxonomy_translatable($term->taxonomy) && $this->is_sub($language->ID)) {
		
			$term->name = $this->translate_term_field($term, $term->taxonomy, $language->ID, 'name', $term->name);
			$term->slug = $this->translate_term_field($term, $term->taxonomy, $language->ID, 'slug', $term->slug);
			$term->description = $this->translate_term_field($term, $term->taxonomy, $language->ID, 'description', $term->description);
			
		}
				
	}
	
	/**
	 *	Enqueue terms for translation as they are queried
	 *	Filter for 'get_object_terms'
	 *
	 * @from 1.4.5
	 */
	public function filter_get_object_terms($terms, $object_ids, $taxonomies, $args) {	
		
		$this->register_taxonomy_terms_order($terms, $taxonomies, $args);
			
		return $this->translate_get_terms($terms, $taxonomies, $args);
			
	}
	
	/**
	 *	allow filters on menu get_posts
	 *	Filter for 'parse_query'
	 */
	public function allow_filters(&$query) {
		
		if (isset($query->query_vars['post_type']) && in_array($query->query_vars['post_type'], $this->get_post_types())) {
		
			$query->query_vars['suppress_filters'] = false;
		
		}
		
	}

	/**
	 *	Append language slug to home url 
	 *	Filter for 'home_url'
	 *  
	 * @from 1.0
	 */
	public function translate_home_url($url, $path, $orig_scheme, $blog_id) {
		
		if (!$this->disable_translate_home_url
			&& isset($this->current_language->post_name) 
			&& $this->get_option('default')
			&& ($this->get_option('show_slug') || !$this->is_default())) {
			
			if (get_option('permalink_structure')) {
			
				$url = rtrim(substr($url, 0, strlen($url) - strlen($path)), '/') . '/' . $this->current_language->post_name . '/' . ltrim($path, '/');
			
			} else {
				
				$url = add_query_arg( array('language' => $this->current_language->post_name), $url);
			
			}
			
		}
		
		return $url;
	
	}
	
	/**
	 *	Pre translate post link
	 *	Filter for 'pre_post_link'
	 *
	 * @from 1.0
	 */
	public function pre_translate_permalink($permalink, $post, $leavename) {
		
		if ($this->is_sub()) {
		
			$permalink = str_replace('%postname%', '%translated_postname%', $permalink);
			$permalink = str_replace('%pagename%', '%translated_postname%', $permalink);
		
		}
		
		return $permalink;
	}
	
	/**
	 *	Translate post link category
	 *	Filter for 'post_link_category'
	 *
	 * @from 1.0
	 */
	public function translate_post_link_category($cat, $cats, $post) {
		
		// to be done...
		
		return $cat;
	}
	
	/**
	 * Translate permalink
	 * Filter for 'post_link'
	 *
	 * @from 1.0
	 */
	public function translate_permalink($permalink, $post, $leavename) {
		
		if ($this->is_sub()) {
			
			$translated_name = $this->translate_post_field($post->ID, $this->current_language->ID, 'post_name', $post->post_name);
		
			$permalink = str_replace('%translated_postname%', $translated_name, $permalink);
		
		}
		
		return $permalink;
		
	}
	
	/**
	 * Translate page link
	 * Filter for 'page_link'
	 *  
	 *
	 * @from 1.0
	 */
	public function translate_page_link($link, $post_id, $sample = false) {
		
		if (!$sample && $this->is_sub() && in_array('page', $this->get_post_types())) {
		
			$original = get_post($post_id);
			
			$translated_slug = $this->translate_post_field($original->ID, $this->current_language->ID, 'post_name', $original->post_name);
			
			// hierarchical pages
			while ($original->post_parent != 0) {
				
				$original = get_post($original->post_parent);
				
				$parent_slug = $this->translate_post_field($original->ID, $this->current_language->ID, 'post_name', $original->post_name);
				
				$translated_slug = $parent_slug.'/'.$translated_slug;
				
			}
			
			$link = get_page_link($original, true, true);
			$link = str_replace('%pagename%', $translated_slug, $link);
			
		}
		
		return $link;
	}	
	
	
	/**
	 * Translate custom post type link
	 * Filter for 'post_type_link'
	 * 
	 * @from 1.0
	 */
	public function translate_custom_post_link($link, $post_id, $sample = false) {
		
		if (!$sample) {
			
			$post = get_post($post_id);
			
			if (in_array($post->post_type, $this->get_post_types())) {
			
				// translate post type
				
				$post_type_slug = $this->get_post_type_slug($post->post_type);
				$translated_cpt = $this->translate_cpt($post->post_type, $this->current_language->ID, $post_type_slug);
				
				// translate post name
				if ($this->is_sub() || $translated_cpt !== $post_type_slug) {
				
					$translated_slug = $this->translate_post_field($post->ID, $this->current_language->ID, 'post_name', $post->post_name);
				
					$post_type_obj = get_post_type_object($post->post_type);
			
					if ($post_type_obj->hierarchical) {
							
						while ($post->post_parent != 0) {
				
							$post = get_post($post->post_parent);
						
							$parent_slug = $this->translate_post_field($post->ID, $this->current_language->ID, 'post_name', $post->post_name);
						
							$translated_slug = $parent_slug.'/'.$translated_slug;
				
						}
			
					}
			
					$post_link = $translated_cpt . '/' . user_trailingslashit($translated_slug);
			
					$link = home_url($post_link);
					
				}
			
			}
			
		}
		
		return $link;
	}
	
	/**
	 * Translate attachment link
	 * Filter for 'attachment_link'
	 * 
	 * @from 1.4
	 */
	public function translate_attachment_link($link, $post_id) {
		global $wp_rewrite;
 		
 		if ($this->is_sub()) {
 		
			$link = trailingslashit($link);
			$post = get_post( $post_id );
			$parent = ( $post->post_parent > 0 && $post->post_parent != $post->ID ) ? get_post( $post->post_parent ) : false;
 
			if ( $wp_rewrite->using_permalinks() && $parent ) {
			
				$translation_name = $this->translate_post_field($post->ID, $this->current_language->ID, 'post_name', $post->post_name);
			
				$link = str_replace ('/'.$post->post_name.'/', '/'.$translation_name.'/', $link);
			
				do {
				
					$translation_parent_name = $this->translate_post_field($parent->ID, $this->current_language->ID, 'post_name', $parent->post_name);
			
					$link = str_replace ('/'.$parent->post_name.'/', '/'.$translation_parent_name.'/', $link);
				
					$parent = ( $parent->post_parent > 0 && $parent->post_parent != $parent->ID ) ? get_post( $parent->post_parent ) : false;
				
				} while ($parent);
			
			}
		
		}
		
		return $link;
	}
	

	/**
	 * Add language in edit link
	 *
	 * Filter for 'get_edit_post_link'
	 *
	 * @from 1.1
	 */
	public function translate_edit_post_link($url, $post_id, $context) {
		
		if ($this->is_sub()) {
		
			$post = get_post($post_id);
		
			if (isset($post->post_type) && in_array($post->post_type, $this->get_post_types()) && $this->is_sub()) {
			
				$url = add_query_arg(array($this->language_query_var => $this->current_language->post_name), $url);
			
			}
		
		}
		
		return $url;
		
	}	


	/**
	 * Translate month link
	 * Filter for 'month_link'
	 * 
	 * @from 1.0
	 */
	public function translate_month_link($monthlink) {
		
		return $monthlink;
		
	}
	
	/**
	 * Hard Translate taxonomy permastruct
	 *
	 * Must be called before getting a term link but after all taxonomies are registered
	 *
	 * @from 1.5
	 */
	public function translate_taxonomy_permastruct($taxonomy, $language_id = null) {
		global $wp_rewrite;
		
		if (in_array($taxonomy, $this->get_taxonomies())) {
		
			if (empty($language_id)) {
			
				$language_id = $this->current_language->ID;
			
			}
		
			if (empty($this->original_permastructs)) {
				
				$this->original_permastructs = $wp_rewrite->extra_permastructs;
			
			}
			
			$t = get_taxonomy($taxonomy);
			
			$tax_slug = isset($t->rewrite['slug']) ? $t->rewrite['slug'] : $taxonomy;
				
			$translated_taxonomy = $this->translate_taxonomy($taxonomy, $language_id, $tax_slug);
			
			if ($translated_taxonomy !== $tax_slug) {
				
				$wp_rewrite->extra_permastructs[$taxonomy]["struct"] = preg_replace("#(/|^)($tax_slug)(/|$)#", "$1$translated_taxonomy$3", $this->original_permastructs[$taxonomy]["struct"]);
			
			}
			
		}
		
	}
	
	/**
	 * Hard Translate all taxonomy permastructs
	 *
	 * Must be called before getting a term link but after all taxonomies are registered
	 *
	 * @from 1.5
	 */
	public function translate_taxonomies_permastructs($language_id = null) {
		global $wp_rewrite;
		
		foreach ($wp_rewrite->extra_permastructs as $taxonomy => $permaschtroumpf) {
			
			$this->translate_taxonomy_permastruct($taxonomy, $language_id);
		
		}
		
	}

	/**
	 * Restore original permastruct
	 *
	 * @from 1.5
	 */
	public function restore_permastruct($taxonomy) {
		global $wp_rewrite;
		
		if (isset($this->original_permastructs[$taxonomy])) {
		
			$wp_rewrite->extra_permastructs[$taxonomy] = $this->original_permastructs[$taxonomy];
			
		}
	
	}
	
	/**
	 * Restore original permastructs
	 *
	 * @from 1.5
	 */
	public function restore_permastructs() {
		global $wp_rewrite;
		
		if (isset($this->original_permastructs)) {
		
			$wp_rewrite->extra_permastructs = $this->original_permastructs;
			
		}
	
	}
	
	/**
	 *	Translation post type archive link
	 *  Filter for 'post_type_archive_link'
	 *  
	 *	Based on get_post_type_archive_link(), wp-includes/link-template.php, 1083
	 *
	 *  @from 1.0
	 *	@from 1.5.4 allow translation for main language 
	 */
	function translate_post_type_archive_link($link, $post_type) {
		global $wp_rewrite;
    	
		if (in_array($post_type, $this->get_post_types())) {
    
			$translated_cpt = $this->translate_cpt($post_type, $this->current_language->ID, $this->get_post_type_slug($post_type));
			
			$post_type_obj = get_post_type_object($post_type);
		
			if ($post_type_obj && get_option( 'permalink_structure' ) ) {
				if ( $post_type_obj->rewrite['with_front'] )
					$struct = $wp_rewrite->front . $translated_cpt;
				else
					$struct = $wp_rewrite->root . $translated_cpt;
				$link = home_url( user_trailingslashit( $struct, 'post_type_archive' ) );
			} 
    	
		}
   
		return $link;
	}
	
	/**
	 *	Translate post meta data
	 *	Filter for "get_{$meta_type}_metadata"
	 *
	 * @from 1.0
	 */
	public function translate_meta_data($null, $object_id, $meta_key, $single) {
		
		if ($this->disable_postmeta_filter) return $null;
		
		/**
		 *	Deprecated. Use 'sublanguage_register_postmeta_key' filter instead
		 *
		 * @from 1.0
		 */
		$translatable = !$meta_key || in_array($meta_key, $this->get_postmeta_keys()) || apply_filters('sublanguage_translatable_postmeta', false, $meta_key, $object_id);
		
		if ($translatable && $this->is_sub()) {
			
			$object = get_post($object_id);
			
			if (in_array($object->post_type, $this->get_post_types())) {
				
				$translation = $this->get_post_translation($object_id, $this->current_language->ID);
				
				if ($translation) {
					
					if (!$meta_key) { // meta_key is not defined -> more work
						
						$this->disable_postmeta_filter = true;
						
						$meta_vals = get_post_meta($object_id);
						
						foreach ($meta_vals as $key => $val) {
							
							if (in_array($key, $this->get_postmeta_keys())) {
							
								$meta_val = get_post_meta($translation->ID, $key);
																
								/**
								 * Filter whether an empty translation inherit original value
								 *
								 * @from 1.4.5
								 *
								 * @param mixed $meta_value
								 * @param string $meta_key
								 * @param int $object_id
								 */	
								if ($meta_key === 'sublanguage_hide' || apply_filters('sublanguage_postmeta_override', $meta_val, $key, $object_id)) {
									
									$meta_vals[$key] = $meta_val;
									
								}
								
							}
							
						}
						
						$this->disable_postmeta_filter = false;
						
						return $meta_vals;
					}
					
					$meta_val = get_post_meta($translation->ID, $meta_key, $single);
					
					/**
					 * Documented just above
					 */	
					if ($meta_key === 'sublanguage_hide' || apply_filters('sublanguage_postmeta_override', $meta_val, $meta_key, $object_id)) {
					
						return $meta_val;
						
					}
					
				}
				
			}
		
		}
		
		return $null;
	}
	
	/** 
	 *	Translate page name in walker when printing pages dropdown. Filter for 'list_pages'.
	 *
	 * @from 1.2
	 */
	public function translate_list_pages($title, $page) {
		
		if ($this->is_sub()) {
		
			return $this->translate_post_field($page->ID, $this->current_language->ID, 'post_title', $title);
		
		}
		
		return $title;
	}	
	
	/**
	 * Print javascript data for ajax
	 *
	 * Hook for 'admin_enqueue_script', 'sublanguage_prepare_ajax'
	 *
	 * @from 1.4
	 */	
	public function ajax_enqueue_scripts() {
		
		$sublanguage = array(
			'current' => $this->current_language ? $this->current_language->post_name : 0,
			'languages' => array(),
			'query_var' => $this->language_query_var
		);
		
		$languages = $this->get_languages();
		
		if ($languages) {
			
			foreach($languages as $language) {
			
				$sublanguage['languages'][] = array(
					'name' => $language->post_title,
					'slug' => $language->post_name,
					'id' => $language->ID
				);
			
			}
			
		}
		
		wp_register_script('sublanguage-ajax', plugin_dir_url( __FILE__ ) . 'include/js/ajax.js', array('jquery'), false, true);
		wp_localize_script('sublanguage-ajax', 'sublanguage', $sublanguage);
		wp_enqueue_script('sublanguage-ajax');
		
	}
	

	
	
	
	
  
}



