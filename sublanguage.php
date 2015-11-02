<?php
/*
Plugin Name: Sublanguage
Plugin URI: http://sublanguageplugin.wordpress.com
Description: Plugin for building a site with multiple languages
Author: Maxime Schoeni
Version: 1.4.4
Author URI: http://sublanguageplugin.wordpress.com
Text Domain: sublanguage
Domain Path: /languages
License: GPL

Copyright 2015 Maxime Schoeni <contact@maximeschoeni.ch>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 2.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.


*/




if (is_admin()) {
	
	include( plugin_dir_path( __FILE__ ) . 'include/admin.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-settings.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-permalink.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-post.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-terms.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-languages.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-pagenode.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-taxnode.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-menu.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-editor-button.php');
	include( plugin_dir_path( __FILE__ ) . 'include/admin-attachment.php');
	
	global $sublanguage_admin;
	
	$sublanguage_admin = new Sublanguage_admin();
	
	register_activation_hook(__FILE__, array($sublanguage_admin, 'activate'));
	register_deactivation_hook(__FILE__, array($sublanguage_admin, 'desactivate'));
		
	new Sublanguage_settings();
	new Sublanguage_permalink();
	new Sublanguage_admin_post();
	new Sublanguage_terms();
	new Sublanguage_languages();
	new Sublanguage_hierarchical_pages();
	new Sublanguage_hierarchical_taxonomies();
	new Sublanguage_menu();
	new Sublanguage_admin_editor_button();
	new Sublanguage_admin_attachment();
	
} else {
	
	include( plugin_dir_path( __FILE__ ) . 'include/site.php');
	
	global $sublanguage;
	
	$sublanguage = new Sublanguage_site();

}




class Sublanguage_main {

	/** 
	 * @from 1.1
	 *
	 * @var float
	 */
	var $version = '1.4.4';
	
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
	 * @from 1.2
	 *
	 * @var array
	 */
	var $options;

	/**
	 * @from 1.0
	 *
	 * @var array
	 */
	var $current_language;
	
	/**
	 * @from 1.1
	 *
	 * @var array
	 */
	var $postmeta_keys = array();
	
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
	 *	@from 1.1
	 */
	public function __construct() {
		
		$this->options = get_option($this->option_name);
		
		add_action( 'plugins_loaded', array($this, 'load_textdomain'));
		add_filter('terms_clauses', array($this, 'filter_terms_clauses'), 10, 3);
		add_filter('wp_get_object_terms', array($this, 'filter_get_terms'), 9, 1);
		add_filter('get_terms', array($this, 'filter_get_terms'), 9, 1);
		add_filter('posts_where_request', array($this, 'filter_posts_where'), 10, 2);
		add_action('init', array($this, 'register_translations'));
		add_action('widgets_init', array($this, 'register_widget'));
		add_filter('sublanguage_translate_post_field', array($this, 'translate_post_field_custom'), null, 3);
		add_action('init', array($this, 'register_postmeta_keys'), 99); // register post meta
		
	}

	/**
	 * Load text domain
	 *
	 * @from 1.0
	 */
	public function load_textdomain() {
		
		load_plugin_textdomain('sublanguage', false, dirname(plugin_basename(__FILE__)).'/languages');
		
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
	 *	@from 1.1
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
		
		return $this->get_language_by($this->options['default'], 'ID');
    
	}

	/**
	 * Get main language 
	 *
	 * @from 1.2
	 *
	 * @return WP_Post object
	 */
	public function get_main_language() {
		
		return $this->get_language_by($this->options['main'], 'ID');
    
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
	
  
 /********* POST TRANSLATION *********/   


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
			
			if ($lng_id != $this->options['main']) {
			
				foreach ($ids as $id) {
				
					if (!isset($this->post_translation_cache[$lng_id][$id])) {
				
						$this->post_translation_queue[$lng_id][$id] = true;
				
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
				'update_post_meta_cache' => !empty($this->postmeta_keys), // FOR 1.4 UPDATE.
				'update_post_term_cache' => false
			));	
			
			foreach ($translations as $translation) {
				
				$language = $this->get_language_by_type($translation->post_type);
				$this->post_translation_cache[$language->ID][$translation->post_parent] = $translation;
		
				unset($this->post_translation_queue[$language->ID][$translation->post_parent]);
		
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
		if ($language_id == $this->options['main']) {
			
			return get_post($post_id);
			
		}
		
		if (in_array(get_post($post_id)->post_type, $this->options['cpt'])) { // -> should be done before
			
			if (!isset($this->post_translation_cache[$language_id][$post_id])) {
				
				$this->enqueue_translation($post_id, $language_id);
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
		
		if ($language_id != $this->options['main'] && in_array($field, $this->fields)) { // added in 1.4
		
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
	 * enqueue term to translate
	 *
	 * @from 1.1
	 *
	 * @param int|array $ids. Term ids.
	 * @param int|array $lngs. Language ids.
	 */
	public function enqueue_terms($ids, $lngs = null) {
		
		if (empty($lngs)) {
			
			$lngs = $this->get_language_column('ID');
		
		} else if (!is_array($lngs)) {
			
			$lngs = array($lngs);
		
		}
		
		if (!is_array($ids)) {
			
			$ids = array($ids);
		
		}
		
		foreach ($lngs as $lng_id) {
			
			if ($lng_id != $this->options['main']) {
			
				foreach ($ids as $id) {
				
					if (!isset($this->term_translation_cache[$lng_id][$id])) {
				
						$this->term_translation_queue[$lng_id][$id] = true;
				
					}
				
				}
				
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
		
		if (isset($term->taxonomy) && in_array($term->taxonomy, $this->options['taxonomy']) && $language_id != $this->options['main']) {
			
			if (!isset($this->term_translation_cache[$language_id][$term->term_id])) {
				
				if (empty($this->term_translation_cache[$language_id][$term->term_id])) {
				
					$this->enqueue_terms($term->term_id, $language_id);
				
				}
				
				$this->translate_term_queue();
			
			}
			
			return $this->term_translation_cache[$language_id][$term->term_id];
			
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
			
			

		register_taxonomy($this->term_translation_prefix.$lng->ID, array('post'), array(
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
	 * Register translatable postmeta keys. Sublanguage API
	 *
	 * @from 1.0
	 */	
	public function register_postmeta_keys() {

		/**
		 * Register translatable post meta data by adding post meta key to the array
		 *
		 * @from 1.0
		 *
		 * @param array $this->postmeta_keys. Array containing post_meta key strings.
		 */
		$this->postmeta_keys = apply_filters('sublanguage_register_postmeta_key', $this->postmeta_keys);
		
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
		
		foreach ($this->fields as $field) {
				
			$translation->$field = $this->translate_post_field($post->ID, $language->ID, $field, $post->$field);
			
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
		
		if ($language->ID != $this->options['main']) {
		
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
	
	/**
	 *	Translate title to current language
	 *	Filter for 'get_the_title'
	 *
	 * @from 1.0
	 */
	public function translate_post_title($title, $id) {
		
		if ($this->current_language->ID != $this->options['main']) {
			
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
	 */	
	public function translate_post_content($content) {
		global $post;
		
		if ($this->current_language->ID != $this->options['main']) {
			
			return $this->translate_post_field($post->ID, $this->current_language->ID, 'post_content', $content);
		
		}
		
		return $content;
	}

	/**
	 *	Translate excerpt to current language
	 *	Filter for 'get_the_excerpt'
	 *
	 * @from 1.0
	 */	
	public function translate_post_excerpt($excerpt) {
		global $post;
		
		if ($this->current_language->ID != $this->options['main']) {
			
			return $this->translate_post_field($post->ID, $this->current_language->ID, 'post_excerpt', $excerpt);
		
		}
		
		return $excerpt;
	}
    
	/**
	 *	Translate page title in wp_title()
	 *	Filter for 'single_post_title'
	 *
	 * @from 1.0
	 */
	public function translate_single_post_title($title, $post) {
		
		if ($this->current_language->ID != $this->options['main']) {
			
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
	public function translate_post_field_custom($original, $post, $field) {
		
		if ($this->current_language->ID != $this->options['main']) {
			
			return $this->translate_post_field($post->ID, $this->current_language->ID, $field, $original);
		
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
	 */	
	public function hard_translate_post($post) {
		
		if (in_array($post->post_type, $this->options['cpt']) && $this->current_language->ID != $this->options['main']) {
			
			foreach ($this->fields as $field) {
				
				$post->$field = $this->translate_post_field($post->ID, $this->current_language->ID, $field, $post->$field);
				
			}
			
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
		
		foreach ($posts as $post) {
			
			if (in_array($post->post_type, $this->options['cpt'])) {
				
				$this->enqueue_translation($post->ID, $this->current_language->ID);
				
				// added in 1.4
				if ($post->post_parent) {
					
					$this->enqueue_translation($post->post_parent, $this->current_language->ID);
				
				}
				
			}
			
		}
		
		return $posts;
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
		
		if (isset($term->taxonomy) && in_array($term->taxonomy, $this->options['taxonomy']) && $this->current_language->ID != $this->options['main']) {
		
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
		
		if (isset($term->taxonomy) && in_array($term->taxonomy, $this->options['taxonomy']) && $this->current_language->ID != $this->options['main']) {
		
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
		
		foreach ($terms as $term) {
		
			$this->translate_term($term, $this->current_language);
		
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
		
		$this->translate_term($term, $this->current_language);
		
		return $term;
		
	}

	/**
	 * Filter post terms. Hard translate
	 * for 'get_terms'
	 *
	 * @from 1.1
	 */		
	public function translate_get_terms($terms, $taxonomies, $args) {
		
		if (isset($args['fields']) && $args['fields'] == 'names') { // -> Added in 1.4.4
			
			$terms = array(); // -> restart query
			
			unset($args['fields']);
			
			$results = get_terms($taxonomies, $args);
			
			foreach ($results as $term) {
				
				$terms[] = $this->translate_term_field($term, $term->taxonomy, $this->current_language->ID, 'name', $term->name);
			
			}
			
			return $terms;
		}
		
		foreach ($terms as $term) {
			
			$this->translate_term($term, $this->current_language);
			
		}
		
		return $terms;
		
	}


	/**
	 *	Translate tag cloud
	 *  Filter for 'tag_cloud_sort'
	 * @from 1.0
	 */
	public function translate_tag_cloud($tags, $args) {
		
		foreach ($tags as $term) {
			
			$this->translate_term($term, $this->current_language);
			
		}
		
		return $tags;
		
	}
	
	/**
	 *	Hard translate term
	 *  
	 * @from 1.2
	 */
	public function translate_term($term, $language) {
		
		if (isset($term->taxonomy) && in_array($term->taxonomy, $this->options['taxonomy']) && $language->ID != $this->options['main']) {
		
			$term->name = $this->translate_term_field($term, $term->taxonomy, $language->ID, 'name', $term->name);
			$term->slug = $this->translate_term_field($term, $term->taxonomy, $language->ID, 'slug', $term->slug);
			$term->description = $this->translate_term_field($term, $term->taxonomy, $language->ID, 'description', $term->description);
			
		}
				
	}
	
	/**
	 *	Enqueue terms for translation as they are queried
	 *	Filter for 'wp_get_object_terms', 'get_terms'
	 *
	 * @from 1.0
	 */
	public function filter_get_terms($terms) {
		
		$ids = array();
		
		foreach ($terms as $term) {
			
			if (isset($term->term_id)) $ids[] = $term->term_id;
		
		}
		
		$this->enqueue_terms($ids, $this->current_language->ID);
		
		return $terms;
	}  

	
	/**
	 *	allow filters on menu get_posts
	 *	Filter for 'parse_query'
	 */
	public function allow_filters(&$query) {
		
		if (isset($query->query_vars['post_type']) && in_array($query->query_vars['post_type'], $this->options['cpt'])) {
		
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
			&& isset($this->current_language->ID) 
			&& ($this->options['show_slug'] || $this->current_language->ID != $this->options['default'])) {
			
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
		
		if ($this->current_language->ID != $this->options['main']) {
		
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
		
		if ($this->current_language->ID != $this->options['main']) {
			
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
		
		if (!$sample && $this->current_language->ID != $this->options['main'] && in_array('page', $this->options['cpt'])) {
		
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
		
		if (!$sample && $this->current_language->ID != $this->options['main']) {
			
			$post = get_post($post_id);
			
			if (in_array($post->post_type, $this->options['cpt'])) {
			
				// translate post type
				
				$translated_cpt = $this->translate_cpt($post->post_type, $this->current_language->ID, $post->post_type);
			
				// translate post name
				
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
 		
 		if ($this->current_language->ID != $this->options['main']) {
 		
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
		
		if ($this->current_language->ID != $this->options['main']) {
		
			$post = get_post($post_id);
		
			if (isset($post->post_type) && in_array($post->post_type, $this->options['cpt']) && $this->current_language->ID != $this->options['main']) {
			
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
	 *	Translate term link
	 *	Filter for 'term_link'
	 *
	 * @from 1.0
	 */
	public function translate_term_link($termlink, $term, $taxonomy) {
		
		if ($this->current_language->ID != $this->options['main']) {
		
			$termlink = $this->get_term_link($term, $this->current_language->ID);
		
		}
		
		return $termlink;
	}
	
	/**
	 *	Translate term link
	 *
	 *	Copied from 4.0 
	 *	-> wp-include/taxonomy.php, line ~3646 
	 *
	 * @from 1.0
	 */
	public function get_term_link($term, $language_id) {
		global $wp_rewrite;
		
		$taxonomy = $term->taxonomy;
		
		$termlink = $wp_rewrite->get_extra_permastruct($taxonomy);
		
		$t = get_taxonomy($taxonomy);
		
		$tax_slug = isset($t->rewrite['slug']) ? $t->rewrite['slug'] : $taxonomy;
		
		$translated_taxonomy = $this->translate_taxonomy($taxonomy, $language_id, $tax_slug);
		
		if ($termlink) {
			
			$termlink = preg_replace("#(/|^)($tax_slug)(/|$)#", "$1$translated_taxonomy$3", $termlink);
			
		}
		
		$slug = $term->slug;
		
		if (!empty($termlink)) {
				if ( $t->rewrite['hierarchical'] ) {
						$hierarchical_slugs = array();
						$ancestors = get_ancestors($term->term_id, $taxonomy);
						foreach ( (array)$ancestors as $ancestor ) {
								$ancestor_term = get_term($ancestor, $taxonomy);
								$translated_slug = $this->translate_term_field($ancestor_term, $taxonomy, $language_id, 'slug', $ancestor_term->slug);
								$hierarchical_slugs[] = $translated_slug;
						}
						$hierarchical_slugs = array_reverse($hierarchical_slugs);
						$translated_slug = $this->translate_term_field($term, $taxonomy, $language_id, 'slug', $term->slug);
						$hierarchical_slugs[] = $translated_slug;
						$termlink = str_replace("%$taxonomy%", implode('/', $hierarchical_slugs), $termlink);
				} else {
						$translated_slug = $this->translate_term_field($term, $taxonomy, $language_id, 'slug', $term->slug);
						$termlink = str_replace("%$taxonomy%", $translated_slug, $termlink);
				}
				$termlink = home_url( user_trailingslashit($termlink, 'category') );
		}
		
		return $termlink; 
	}
	
	
	/**
	 *	Translation post type archive link
	 *  Filter for 'post_type_archive_link'
	 *  
	 *	Based on get_post_type_archive_link(), wp-includes/link-template.php, 1083
	 *
	 *  @from 1.0
	 */
	function translate_post_type_archive_link($link, $post_type) {
		global $wp_rewrite;
    	
		if ($this->current_language->ID != $this->options['main'] && in_array($post_type, $this->options['cpt'])) {
    
			$translated_cpt = $this->translate_cpt($post_type, $this->current_language->ID, $post_type);
			
			$post_type_obj = get_post_type_object($post_type);
		
			if ($post_type_obj && get_option( 'permalink_structure' ) ) {
					if ( $post_type_obj->rewrite['with_front'] )
							$struct = $wp_rewrite->front . $translated_cpt;
					else // actually not tested...
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
		
		/**
		 *	Deprecated. Use 'sublanguage_register_postmeta_key' filter instead
		 *
		 * @from 1.0
		 */
		$translatable = in_array($meta_key, $this->postmeta_keys) || apply_filters('sublanguage_translatable_postmeta', false, $meta_key, $object_id);
		
		if ($translatable && $this->current_language->ID != $this->options['main']) {
			
			$object = get_post($object_id);
			
			
			if (in_array($object->post_type, $this->options['cpt'])) {
			
				$translation = $this->get_post_translation($object_id, $this->current_language->ID);
				
				if ($translation) {
		
					$meta_val = get_post_meta($translation->ID, $meta_key, $single);
					
					if ($meta_val !== '') {
					
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
		
		if ($this->current_language->ID != $this->options['main']) {
		
			return $this->translate_post_field($page->ID, $this->current_language->ID, 'post_title', $title);
		
		}
		
		return $title;
	}	
	
	/** 
	 *	Get current language
	 *
	 * @from 1.0
	 */
	public function find_current_language() {

		if (isset($_REQUEST[$this->language_query_var])) {
			
			$this->current_language = $this->get_language_by($_REQUEST[$this->language_query_var], 'post_name');
			
			if (!$this->current_language) {
				
				$this->current_language = $this->get_language_by($this->options['main'], 'ID');
				
			}
			
		} else {
			
			$this->current_language = $this->get_language_by($this->options['main'], 'ID');
			
		}
	
	}
	
	/**
	 * Print javascript data for ajax
	 *
	 * Hook for 'admin_enqueue_script', 'sublanguage_prepare_ajax'
	 *
	 * @from 1.4
	 */	
	public function ajax_enqueue_scripts() {
	
		$languages = array();
			
		foreach($this->get_languages() as $language) {
			
			$languages[] = array(
				'name' => $language->post_title,
				'slug' => $language->post_name
			);
			
		}
		
		$sublanguage = array(
			'current' => $this->current_language->post_name,
			'languages' => $languages,
			'query_var' => $this->language_query_var
		);
		
		wp_register_script('sublanguage-ajax', plugin_dir_url( __FILE__ ) . 'include/js/ajax.js', array('jquery'), false, true);
		
		wp_localize_script('sublanguage-ajax', 'sublanguage', $sublanguage);
		
		wp_enqueue_script('sublanguage-ajax');
		
	}
	
	/** 
	 * Register widget
	 *
	 * @from 1.3
	 */
	public function register_widget() {
	
		register_widget( 'Sublanguage_Widget' );
		
	}
	
  
}


/**
 * Adds widget.
 */
class Sublanguage_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'sublanguage_widget', // Base ID
			__( 'Sublanguage', 'sublanguage' ), // Name
			array( 'description' => __( 'Language switch', 'sublanguage' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
	
		echo $args['before_widget'];
		
		if ( ! empty( $instance['title'] ) ) {
		
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
			
		}
		
		do_action('sublanguage_print_language_switch');
		
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Languages', 'sublanguage' );
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

}



