<?php

/** 
 * Base class for Sublanguage_site in front-end and Sublanguage_admin in admin.
 */
class Sublanguage_current extends Sublanguage_core {

	/**
	 * @from 1.2
	 *
	 * @var boolean
	 */
	var $disable_translate_home_url = false;
	
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
	 * Used when translating search query
	 *
	 * @var string
	 *
	 * @from 2.0
	 */
	private $search_sql;
	
	
	/**
	 * Register all filters needed for admin and front-end
	 *
	 * @from 1.4.7
	 */
	public function load() {
		
 		add_filter('request', array($this, 'translate_search_request'));
 		add_filter('parse_query', array($this, 'verify_posts_ordering'));
		add_filter('get_object_terms', array($this, 'filter_get_object_terms'), 10, 4);
		add_filter('get_term', array($this, 'translate_get_term'), 10, 2); // hard translate term
		add_filter('get_terms', array($this, 'translate_get_terms'), 10, 3); // hard translate terms
		add_filter('get_the_terms', array($this, 'translate_post_terms'), 10, 3);
		add_filter('list_cats', array($this, 'translate_term_name'), 10, 2);
		add_filter('the_posts', array($this, 'translate_the_posts'), 10, 2);
		add_filter('get_pages', array($this, 'translate_the_posts'), 10, 2);
		add_filter('sublanguage_translate_post_field', array($this, 'translate_post_field_custom'), 10, 5);
		add_filter('sublanguage_translate_term_field', array($this, 'translate_term_field_custom'), 10, 6);
		add_filter('sublanguage_query_add_language', array($this, 'query_add_language'));
		add_action('widgets_init', array($this, 'register_widget'));
		
	}
	
	/**
	 * Add translations query vars for name, pagename or s
	 *
	 * filter for 'request'
	 *
	 * @from 2.0
	 */
	public function translate_search_request($query_vars) {
		global $wpdb;
		
		if (!empty($query_vars['s'])) {
			
			$post_types = isset($query_vars['post_type']) ? $query_vars['post_type'] : array('post', 'page');
			
			$is_translatable = (!$post_types && $this->is_post_type_translatable('post')) || (is_string($post_types) && ($post_types === 'any' || $this->is_post_type_translatable($post_types))) || (is_array($post_types) && array_filter($post_types, array($this, 'is_post_type_translatable')));
			
			if ($this->is_sub() && $is_translatable) {
				
				if (isset($query_vars['s'])) {
					
					$query_vars['meta_query']['relation'] = 'OR';
					
					$query_vars['meta_query'][] = array(
						'key'     => $this->get_prefix() . 'post_title',
						'value'   => $query_vars['s'],
						'compare' => 'LIKE',
					);
					$query_vars['meta_query'][] = array(
						'key'     => $this->get_prefix() . 'post_content',
						'value'   => $query_vars['s'],
						'compare' => 'LIKE',
					);
					$query_vars['meta_query'][] = array(
						'key'     => $this->get_prefix() . 'post_excerpt',
						'value'   => $query_vars['s'],
						'compare' => 'LIKE',
					);
					
					add_filter('posts_search', array($this, 'catch_search'), 10, 2);
					
				}
				
			}
			
		}
		
		return $query_vars;
	}
	
	/**
	 * When posts are sorted by name for sub-languages, use cached title meta key instead
	 *
	 * @hook for 'parse_query'
	 *
	 * @from 2.0
	 */
	public function verify_posts_ordering($wp_query) {
		
		$query_vars = &$wp_query->query_vars;
		
		if (isset($query_vars['orderby']) && $query_vars['orderby'] === 'title' && $this->is_sub()) {
			
			$post_type = isset($query_vars['post_type']) ? $query_vars['post_type'] : 'post';
			
			if ((is_string($post_type) && $this->is_post_type_title_cached($post_type)) || (is_array($post_type) && array_filter($post_type, array($this, 'is_post_type_title_cached')))) {
			
				$order = isset($query_vars['order']) ? $query_vars['order'] : 'ASC';
				
				$query_vars['orderby'] = array( 'meta_value' => $order, 'title' => $order);
				$query_vars['meta_key'] = $this->get_prefix() . 'order_title';
				 
			}
			
		}
		
	}
	
	/**
	 * Catch search query
	 *
	 * filter for 'posts_search'
	 *
	 * @from 2.0
	 */
	public function catch_search($search, $wp_query) {
		
		$this->search_sql = $search;
		
		add_filter('get_meta_sql', array($this, 'append_search_meta'));
		
		return '';
		
	}
	
	/**
	 * Append translations data to search query
	 *
	 * filter for 'get_meta_sql'
	 *
	 * @from 2.0
	 */
	public function append_search_meta($sql) {
		
		if (isset($this->search_sql)) {
			
			$sql['where'] = " AND ((1=1 {$sql['where']}) OR (1=1 {$this->search_sql}))";
			
			unset($this->search_sql);
			
		}
		
		return $sql;
	}
	
	
	/**
	 * Translate post
	 *
	 * @from 2.0 Changed parameters
	 * @from 1.1
	 *
	 * @param object WP_post $post
	 * @param int|string|object language. Optionnal
	 * @return object WP_post
	 */	
	public function translate_post($post) {
		
		$translation = $post;
		
		$this->hard_translate_post($translation);
		
		return $translation;
	}
	
	/**
	 * Translate post meta. Public API
	 *
	 * filter for 'sublanguage_translate_post_meta'
	 *
	 * @param string $original Value to translate.
	 * @param object WP_Post $post Term object.
	 * @param string $meta_key Meta key name.
	 * @param bool $single Single meta value.
	 * @param object WP_Post $language Language. Optional.
	 * @param mixed language id, slug or anything
	 * @param string $by Field to search language by. Accepts 'post_name', 'post_title', 'post_content'. Optional. 
	 *
	 * @from 2.0 changed params
	 * @from 1.1
	 */	
	public function translate_custom_post_meta($original, $post, $meta_key, $single = false, $language = null, $by = null) {
		
		$language = $this->find_language($language);
		
		return $this->translate_post_meta($post, $meta_key, $single, $language, $original);
	}
	
	
	
	// IS THIS NEEDED ?
	
	/**
	 * Allow filters on menu get_posts
	 *
	 * @filter for 'parse_query'
	 *
	 * @from 1.2
	 */
// 	public function allow_filters(&$query) {
// 		
// 		if (isset($query->query_vars['post_type']) && in_array($query->query_vars['post_type'], $this->get_post_types())) {
// 		//if (isset($query->query_vars['post_type']) && is_string($query->query_vars['post_type']) && $this->is_post_type_translatable($query->query_vars['post_type'])) {
// 		
// 			$query->query_vars['suppress_filters'] = false;
// 		
// 		}
// 		
// 	}

	/**
	 *	Append language slug to home url 
	 *	Filter for 'home_url'
	 *  
	 * @from 1.0
	 */
	public function translate_home_url($url, $path, $orig_scheme, $blog_id) {
		
		$language = $this->get_language();
		
		if (!$this->disable_translate_home_url
			&& $language
			&& $this->get_option('default')
			&& ($this->get_option('show_slug') || !$this->is_default())) {
			
			if (get_option('permalink_structure')) {
			
				$url = rtrim(substr($url, 0, strlen($url) - strlen($path)), '/') . '/' . $language->post_name . '/' . ltrim($path, '/');
			
			} else {
				
				$url = add_query_arg( array('language' => $language->post_name), $url);
			
			}
			
		}
		
		return $url;
	
	}
	
	/**
	 *	Translate title to current language
	 *	Filter for 'the_title'
	 *
	 * @from 1.0
	 */
	public function translate_post_title($title, $id) {
		
		$post = get_post($id);
		
		if ($post && $this->is_sub() && $this->is_post_type_translatable($post->post_type) && empty($post->sublanguage)) {
			
			$title = $this->translate_post_field($post, 'post_title', null, $title);
			
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
		
		if ($post && $this->is_sub() && $this->is_post_type_translatable($post->post_type) && empty($post->sublanguage)) {
		
			$content = $this->translate_post_field($post, 'post_content', null, $content);
			
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
		
		if ($post && $this->is_sub() && $this->is_post_type_translatable($post->post_type) && empty($post->sublanguage)) {
		
			$excerpt = $this->translate_post_field($post, 'post_excerpt', null, $excerpt);
			
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
		
		if ($post && $this->is_sub() && $this->is_post_type_translatable($post->post_type) && empty($post->sublanguage)) {
		
			$title = $this->translate_post_field($post, 'post_title', null, $title);
			
		}
		
		return $title;
	}

	/**
	 * Public API
	 * Filter for 'sublanguage_translate_post_field'
	 *
	 * @from 1.0
	 *
	 * @param string $original Original value to translate
	 * @param object WP_Post $post Post to translate field.
	 * @param string $field Field name. Accepts 'post_content', 'post_title', 'post_name', 'post_excerpt'
	 * @param mixed $language Language object, id or anything. Optional. 
	 * @param string $by Field to search language by. Accepts 'post_name', 'post_title', 'post_content'. Optional. 
	 * @return string
	 */
	public function translate_post_field_custom($original, $post, $field, $language = null, $by = null) {
		
		if ($this->is_post_type_translatable($post->post_type)) {
			
			$language = $this->find_language($language, $by);
			
			return $this->translate_post_field($post, $field, $language, $original);
			
		}
		
		return $original;
	}
	
	
	/**
	 * Translate term field. Public API
	 * Filter for 'sublanguage_translate_term_field'
	 *
	 * @param string $original Original value
	 * @param object WP_Term $term Term object
	 * @param string $taxonomy Taxonomy
	 * @param string $field. Field to translate ('name', 'slug' or 'description')
	 * @param mixed $language Language object, id or anything. Optional. 
	 * @param string $by Field to search language by. Accepts 'post_name', 'post_title', 'post_content'. Optional. 
	 *
	 * @from 1.4.6
	 */
	public function translate_term_field_custom($original, $term, $taxonomy, $field, $language = null, $by = null) {
		
		if ($this->is_taxonomy_translatable($taxonomy)) {
			
			$language = $this->find_language($language, $by);
			
			if ($language) {
				
				return $this->translate_term_field($term, $taxonomy, $field, $language, $original);
			
			}
			
		}
		
		return $original;
	}
	
	
	
	
	/**
	 * translate posts
	 *
	 * @filter 'the_posts', 'get_pages'
	 *
	 * @from 1.1
	 * @from 2.0 also used for 'get_pages' 
	 */
	public function translate_the_posts($posts, $wp_query = null) { // -> $wp_query parameter is unreliable!
		
		if ($this->is_sub()) {
		
			$this->hard_translate_posts($posts);
			
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
		
		if (!isset($term)) { // looks like there are 2 differents list_cats filters
			
			return $name;
			
		}
		
		return $this->translate_term_field($term, $term->taxonomy, 'name', null, $name);
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
		
		return $this->translate_term_field($term, $term->taxonomy, 'name', null, $title);
		
	}

	/**
	 * Filter post terms
	 *
	 * @filter 'get_the_terms'
	 *
	 * @from 1.0
	 */
	public function translate_post_terms($terms, $post_id, $taxonomy) {
		
		if ($this->is_sub() && $this->is_taxonomy_translatable($taxonomy)) {
		
			foreach ($terms as $term) {
			
				$this->translate_term($term);
		
			}
			
		}
		
		return $terms;
		
	}

	/**
	 * Hard translate term
	 * @filter 'get_term'
	 *
	 * @from 1.2
	 */		
	public function translate_get_term($term, $taxonomy) {
		
		if ($this->is_sub() && $this->is_taxonomy_translatable($taxonomy)) {
		
			$this->translate_term($term);
			
		}
		
		return $term;
		
	}

	/**
	 * Filter post terms. Hard translate
	 *
	 * @filter 'get_terms'
	 *
	 * @from 1.1
	 */		
	public function translate_get_terms($terms, $taxonomies, $args) {
		
		if ($this->is_sub() && (is_array($taxonomies) && array_filter($taxonomies, array($this, 'is_taxonomy_translatable')) || is_string($taxonomies) && $this->is_taxonomy_translatable($taxonomies))) {
		
			if (isset($args['fields']) && $args['fields'] == 'names') { // -> Added in 1.4.4
		
				$terms = array(); // -> restart query
		
				unset($args['fields']);
		
				$results = get_terms($taxonomies, $args);
		
				foreach ($results as $term) {
			
					$terms[] = $this->translate_term_field($term, $term->taxonomy, 'name');
		
				}
		
				return $terms;
			}
	
			if (empty($args['fields']) || $args['fields'] == 'all' || $args['fields'] == 'all_with_object_id') { // -> $terms may be an array of ids !
		
				foreach ($terms as $term) {
		
					$this->translate_term($term);
				
				}
			
			}
			
		}
		
		return $terms;
	}
	

	/**
	 *	Translate tag cloud
	 *  Filter for 'tag_cloud_sort'
	 * @from 1.0
	 */
	public function translate_tag_cloud($tags, $args) {
		
		if ($this->is_sub()) {
		
			foreach ($tags as $term) {
			
				$this->translate_term($term);
			
			}
			
		}
		
		return $tags;
		
	}
	
	/**
	 *	Enqueue terms for translation as they are queried
	 *	Filter for 'get_object_terms'
	 *
	 * @from 1.4.5
	 */
	public function filter_get_object_terms($terms, $object_ids, $taxonomies, $args) {	
		
		return $this->translate_get_terms($terms, $taxonomies, $args);
			
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
			
			$translated_name = $this->translate_post_field($post, 'post_name');
		
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
		
		if (!$sample && $this->is_sub() && $this->is_post_type_translatable('page')) {
		
			$original = get_post($post_id);
			
			$translated_slug = $this->translate_post_field($original, 'post_name');
			
			// hierarchical pages
			while ($original->post_parent != 0) {
				
				$original = get_post($original->post_parent);
				
				$parent_slug = $this->translate_post_field($original, 'post_name');
				
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
			
			if ($post && $this->is_post_type_translatable($post->post_type)) {
			
				// translate post type
				
				$post_type_obj = get_post_type_object($post->post_type);
				
				$translated_cpt = $this->translate_cpt($post->post_type, null, $post_type_obj->rewrite['slug']);
				
				// translate post name
				
				if ($this->is_sub() || $translated_cpt !== $post_type_obj->rewrite['slug']) {
				
					$translated_slug = $this->translate_post_field($post, 'post_name');
				
					if ($post_type_obj->hierarchical) {
							
						while ($post->post_parent != 0) {
				
							$post = get_post($post->post_parent);
						
							$parent_slug = $this->translate_post_field($post, 'post_name');
						
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
			
				$translation_name = $this->translate_post_field($post, 'post_name');
			
				$link = str_replace ('/'.$post->post_name.'/', '/'.$translation_name.'/', $link);
				
				do {
				
					$translation_parent_name = $this->translate_post_field($parent, 'post_name');
			
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
			
			$language = $this->get_language();
				
			if ($this->is_sub($language) && isset($post->post_type) && $this->is_post_type_translatable($post->post_type)) {
				
				$url = add_query_arg(array($this->language_query_var => $language->post_name), $url);
			
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
	 * Translation post type archive link
	 *
	 * Filter for 'post_type_archive_link'
	 *
	 * @from 1.0
	 */
	function translate_post_type_archive_link($link, $post_type) {
		global $wp_rewrite;
    	
		if ($this->is_post_type_translatable($post_type)) {
    	
			$post_type_obj = get_post_type_object($post_type);
			
			$translated_cpt = $this->translate_cpt($post_type, null, $post_type_obj->rewrite['slug']);
			
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
	 *
	 *	Filter for "get_{$meta_type}_metadata"
	 *
	 * @from 1.0
	 */
	public function translate_meta_data($null, $object_id, $meta_key, $single) {
		
		static $disable = false;
		
		if ($disable) {
		
			return $null;
			
		}
		
		$object = get_post($object_id);
		
		if (isset($object->post_type) && $this->is_sub()) {
							
			if (!$meta_key) { // meta_key is not defined -> more work
				
				$disable = true;
				
				$meta_vals = get_post_meta($object_id);
				
				foreach ($meta_vals as $key => $val) {
					
					if (in_array($key, $this->get_post_type_metakeys($object->post_type))) {
						
						$meta_val = $this->get_post_meta_translation($object, $key, $single);
														
						/**
						 * Filter whether an empty translation inherit original value
						 *
						 * @from 1.4.5
						 *
						 * @param mixed $meta_value
						 * @param string $meta_key
						 * @param int $object_id
						 */	
						if (apply_filters('sublanguage_postmeta_override', $meta_val, $key, $object_id)) {
							
							$meta_vals[$key] = $meta_val;
							
						}
						
					}
					
				}
				
				$disable = false;
				
				return $meta_vals;
				
			} else if (in_array($meta_key, $this->get_post_type_metakeys($object->post_type))) { // -> just one key
			
				$meta_val = $this->get_post_meta_translation($object, $meta_key, $single);
			
				/**
				 * Documented just above
				 */	
				if (apply_filters('sublanguage_postmeta_override', $meta_val, $meta_key, $object_id)) {
			
					return $meta_val;
				
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
		
			return $this->translate_post_field($page, 'post_title', null, $title);
		
		}
		
		return $title;
	}	
	
	/**
	 * Print javascript data for ajax
	 *
	 * Hook for 'admin_enqueue_script', 'sublanguage_prepare_ajax', wp_enqueue_scripts
	 *
	 * @from 1.4
	 */	
	public function ajax_enqueue_scripts() {
		
		$language = $this->get_language();
		
		$sublanguage = array(
			'current' => $language ? $language->post_name : 0,
			'languages' => array(),
			'query_var' => $this->language_query_var
		);
					
		foreach($this->get_languages() as $language) {
		
			$sublanguage['languages'][] = array(
				'name' => $language->post_title,
				'slug' => $language->post_name,
				'id' => $language->ID
			);
		
		}
					
		wp_register_script('sublanguage-ajax', plugin_dir_url( __FILE__ ) . 'js/ajax.js', array('jquery'), false, true);
		wp_localize_script('sublanguage-ajax', 'sublanguage', $sublanguage);
		wp_enqueue_script('sublanguage-ajax');
		
	}
	
	
	/**
	 * Hard Translate taxonomy permastruct
	 *
	 * Must be called before getting a term link but after all taxonomies are registered
	 *
	 * @param string $taxonomy Taxonomy name
	 * @param object WP_Post $language Language. Optional
	 *
	 * @from 1.5
	 */
	public function translate_taxonomy_permastruct($taxonomy, $language = null) {
		global $wp_rewrite;
		
		if ($this->is_taxonomy_translatable($taxonomy)) {
			
			if (empty($this->original_permastructs)) {
				
				$this->original_permastructs = $wp_rewrite->extra_permastructs;
			
			}
			
			$t = get_taxonomy($taxonomy);
			
			$tax_slug = isset($t->rewrite['slug']) ? $t->rewrite['slug'] : $taxonomy;
			
			$translated_taxonomy = $this->translate_taxonomy($taxonomy, $language, $tax_slug);
			
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
	 * @param object WP_Post $language Language. Optional
	 *
	 * @from 1.5
	 */
	public function translate_taxonomies_permastructs($language = null) {
		global $wp_rewrite;
		
		foreach ($wp_rewrite->extra_permastructs as $taxonomy => $permaschtroumpf) {
			
			$this->translate_taxonomy_permastruct($taxonomy, $language);
		
		}
		
	}

	/**
	 * Restore original permastruct
	 *
	 * @param string $taxonomy Taxonomy name
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
	 * Add language query args to url (Public API)
	 *
	 * @filter 'sublanguage_query_add_language'
	 *
	 * @from 2.0
	 */
	public function query_add_language($url) {
		
		if ($this->is_sub()) {
		
			$url = add_query_arg(array($this->language_query_var => $this->get_language()->post_name), $url);

		}
		
		return $url;
	}

	/** 
	 * Register widget
	 *
	 * @from 1.3
	 */
	public function register_widget() {
		
		require( plugin_dir_path( __FILE__ ) . 'widget.php');
		register_widget( 'Sublanguage_Widget' );
		
	}
  
}

