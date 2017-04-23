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
		
 		add_filter('parse_query', array($this, 'parse_query'));
		add_filter('get_object_terms', array($this, 'filter_get_object_terms'), 10, 4);
		add_filter('get_term', array($this, 'translate_get_term'), 10, 2); // hard translate term
		add_filter('get_terms', array($this, 'translate_get_terms'), 10, 3); // hard translate terms
		add_filter('get_the_terms', array($this, 'translate_post_terms'), 10, 3);
		add_filter('list_cats', array($this, 'translate_term_name'), 10, 2);
		add_filter('the_posts', array($this, 'translate_the_posts'), 10, 2);
		add_filter('get_pages', array($this, 'translate_the_pages'), 10, 2);
		add_filter('sublanguage_translate_post_field', array($this, 'translate_post_field_custom'), 10, 5);
		add_filter('sublanguage_translate_term_field', array($this, 'translate_term_field_custom'), 10, 6);
		add_filter('sublanguage_query_add_language', array($this, 'query_add_language'));
		add_action('widgets_init', array($this, 'register_widget'));
		
	}
	
	/**
	 * Helper for parse_query(). Check if query is to be translated
	 *
	 * @param array $query_vars.
	 *
	 * @from 2.0
	 */
	public function is_query_translatable($query_vars) {
		
		$post_type = isset($query_vars['post_type']) ? $query_vars['post_type'] : 'post';
		
		if ($post_type === 'any') {
			
			return true; // lets pretend it is...
		
		} else if (is_string($post_type)) {
			
			return $this->is_post_type_translatable($post_type);
		
		} else if (is_array($post_type)) {
			
			return array_filter($post_type, array($this, 'is_post_type_translatable'));
		
		}
		
	}
	
	/**
	 * Handle search query
	 *
	 * @hook for 'parse_query'
	 *
	 * @from 2.2
	 */
	public function parse_query($wp_query) {
		
		$language = isset($wp_query->query_vars['sublanguage']) ? $this->find_language($wp_query->query_vars['sublanguage']) : null;
		
		if ($this->is_sub($language) && $this->is_query_translatable($wp_query->query_vars)) {
			
			if (isset($wp_query->query_vars['s']) && $wp_query->query_vars['s']) { // query_vars['s'] is empty string by default 
				
				$wp_query->query_vars['sublanguage_search_meta'] = array(
					$this->get_prefix($language) . 'post_title',
					$this->get_prefix($language) . 'post_content',
					$this->get_prefix($language) . 'post_excerpt'
				);
				$wp_query->query_vars['sublanguage_search_alias'] = 'postmeta_search';
				
				add_filter('posts_join_request', array($this, 'meta_search_join'), 10, 2);
				add_filter('posts_search', array($this, 'meta_search'), 10, 2);
				add_filter('posts_distinct_request', array($this, 'meta_search_distinct'), 10, 2);
				
			}
			
			/**
			 * Hook called on parsing a query that needs translation 
			 *
			 * @from 2.0
			 *
			 * @param WP_Query object $query
			 * @param Sublanguage_current object $this
			 */	
			do_action('sublanguage_parse_query', $wp_query, $language, $this);
			
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
		
		if (!empty($wp_query->query_vars['sublanguage_search_deep'])) { // -> will search in original language as well... quite hacky!
		
			$this->search_sql = $search;
		
			add_filter('get_meta_sql', array($this, 'append_search_meta'));
		
		}
		
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
	 * translate posts
	 *
	 * @filter 'the_posts'
	 *
	 * @from 1.1
	 */
	public function translate_the_posts($posts, $wp_query) {
		
		if (isset($wp_query->query_vars[$this->language_query_var])) {
			
			if (!$wp_query->query_vars[$this->language_query_var]) { // false -> do not translate
				
				// Documented below
				return apply_filters('sublanguage_translate_the_posts', $posts, $posts, null, $wp_query, $this);
				
			} else { // language slug or ID -> find language
				
				$language = $this->find_language($wp_query->query_vars[$this->language_query_var]);
			
			}
			
		} else { // language not set -> use current
			
			$language = null;
		
		}
		
		if ($this->is_sub($language)) {
			
			/**
			 * Filter the posts after translation
			 *
			 * @from 2.0
			 *
			 * @param array of object WP_Post $translated posts.
			 * @param array of object WP_Post $original posts.
			 * @param object WP_Post $language.
			 * @param object WP_Query $wp_query.
			 * @param object Sublanguage_core $this.
			 */
			$posts = apply_filters('sublanguage_translate_the_posts', $this->translate_posts($posts, $language), $posts, $language, $wp_query, $this);
			
		}
		
		return $posts;
	}
	
	/**
	 * translate posts
	 *
	 * @filter 'get_pages'
	 *
	 * @from 2.0
	 */
	public function translate_the_pages($posts, $r) {
		
		if ($this->is_sub()) {
		
			$posts = $this->translate_posts($posts);
			
		}
		
		return $posts;
	}
	
	
	/** 
	 * Translate posts
	 *
	 * @from 2.0
	 *
	 * @param object WP_post $post
	 * @param object language
	 */
	public function translate_posts($posts, $language = null) {
		
		$new_posts = array();
		
		foreach ($posts as $post) {
			
			/**
			 * Filter the post after translation
			 *
			 * @from 2.0
			 *
			 * @param object WP_Post $translated post.
			 * @param object WP_Post $original post.
			 * @param object WP_Post $language.
			 * @param object Sublanguage_core $this.
			 */
			$new_posts[] = apply_filters('sublanguage_translate_the_post', $this->translate_post($post, $language), $post, $language, $this);
			
		}
		
		return $new_posts;
	}
	
	/**
	 * Translate post. This function is overrided on front-end !
	 *
	 * @from 2.0
	 *
	 * @param object WP_post $post
	 * @param object language
	 */	
	public function translate_post($post, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
		
		if ($this->is_sub($language) && $this->is_post_type_translatable($post->post_type) && empty($post->sublanguage)) {
			
			foreach ($this->fields as $field) {
			
				$post->$field = $this->translate_post_field($post, $field, $language);
			
			}
				
			$post->sublanguage = true;
			
		}
		
		return $post;
		
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
		
		if (isset($args[$this->language_query_var])) {
			
			if (!$args[$this->language_query_var]) { // false -> do not translate
				
				// Documented below
				return apply_filters('sublanguage_translate_the_terms', $terms, $terms, null, $args, $this);
				
			} else { // language slug or ID -> find language
				
				$language = $this->find_language($args[$this->language_query_var]);
			
			}
			
		} else { // language not set -> use current
			
			$language = null;
		
		}
		
		if ($this->is_sub($language)) {
			
			if (isset($args['fields']) && $args['fields'] == 'names') { // -> Added in 1.4.4
				
				$terms = array(); // -> restart query
		
				unset($args['fields']);
		
				$results = get_terms($taxonomies, $args);
		
				foreach ($results as $term) {
			
					$terms[] = $this->translate_term_field($term, $term->taxonomy, 'name');
		
				}
		
				return $terms;
			}
			
			if (empty($args['fields']) || $args['fields'] == 'all' || $args['fields'] == 'all_with_object_id') {
			
				/**
				 * Filter the terms after translation
				 *
				 * @from 2.0
				 *
				 * @param array of object WP_Term $translated terms.
				 * @param array of object WP_Term $original terms.
				 * @param object WP_Post $language.
				 * @param object $args.
				 * @param object Sublanguage_core $this.
				 */
				$terms = apply_filters('sublanguage_translate_the_terms', $this->translate_terms($terms, $language), $terms, $language, $args, $this);
			
			}
			
		}
		
		return $terms;
	}
	
	/** 
	 * Translate terms
	 *
	 * @from 2.0
	 *
	 * @param array of object WP_Terms $terms
	 * @param object language
	 */
	public function translate_terms($terms, $language = null) {
		
		$new_terms = array();
		
		foreach ($terms as $term) {
			
			/**
			 * Filter the term after translation
			 *
			 * @from 2.0
			 *
			 * @param object WP_Term $translated post.
			 * @param object WP_Term $original post.
			 * @param object WP_Term $language.
			 * @param object Sublanguage_core $this.
			 */
			$new_terms[] = apply_filters('sublanguage_translate_the_term', $this->translate_term($term, $language), $term, $language, $this);
			
		}
		
		return $new_terms;
	}	
	
	
	/**
	 * Translate term
	 *  
	 * @from 1.2
	 */
	public function translate_term($term, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
		
		if ($this->is_taxonomy_translatable($term->taxonomy) && $this->is_sub($language)) {
		
			$term->name = $this->translate_term_field($term, $term->taxonomy, 'name', $language);
			$term->slug = $this->translate_term_field($term, $term->taxonomy, 'slug', $language);
			$term->description = $this->translate_term_field($term, $term->taxonomy, 'description', $language);
			
		}
		
		return $term;
				
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
		global $wp_rewrite;
		
		if (!$sample) {
	
			$post = get_post($post_id);
			
			if ($post && $this->is_post_type_translatable($post->post_type) && get_option('permalink_structure')) {
			
				$post_type_obj = get_post_type_object($post->post_type);
				
				$translated_cpt = ($post_type_obj->rewrite['with_front']) ? $wp_rewrite->front : $wp_rewrite->root;
				
				$translated_cpt .= $this->translate_cpt($post->post_type, null, $post->post_type);
				
				$translated_slug = $this->translate_post_field($post, 'post_name');
				
				if ($post_type_obj->hierarchical) {
					
					$parent_id = $post->post_parent;
					
					while ($parent_id != 0) {
			
						$parent = get_post($parent_id);
						$parent_slug = $this->translate_post_field($parent, 'post_name');
						$translated_slug = $parent_slug.'/'.$translated_slug;
						$parent_id = $parent->post_parent;
			
					}
					
				}
			
				$post_link = $translated_cpt.'/'.user_trailingslashit($translated_slug);
					
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
			
			$translated_cpt = $this->translate_cpt($post_type, null, $post_type);
			
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
					
					return ($single && is_array($meta_val)) ? array($meta_val) : $meta_val; // watch out: foresee meta_val array check in get_metadata()
				
				}
				
			}
			
		}
		
		return $null;
	}

	/**
	 * Translate term link
	 *
	 * @filter 'term_link'
	 *
	 * @from 2.0
	 */
	public function translate_term_link($termlink, $term, $taxonomy) {
		global $wp_rewrite;
		
		if (get_option('permalink_structure')) {
			
			$taxonomy_obj = get_taxonomy($taxonomy);
			$termlink = ($taxonomy_obj->rewrite['with_front']) ? $wp_rewrite->front : $wp_rewrite->root;
			$termlink .= $this->translate_taxonomy($taxonomy, null, $taxonomy);
			
			// -> todo: handle hierarchical taxonomy... 
			
			$translated_slug = $this->translate_term_field($term, $taxonomy, 'slug');
			$termlink = home_url(user_trailingslashit($termlink . '/' . $translated_slug, 'category'));
				
		}
   
		return $termlink;
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
	
	/** 
	 * @filter 'posts_join'
	 *
	 * @from 1.3
	 */
	public function meta_search_join($join, $wp_query) {
		global $wpdb;
		
		if (isset($wp_query->query_vars['sublanguage_search_alias'])) {
			$alias = $wp_query->query_vars['sublanguage_search_alias'];
			$fields = $wp_query->query_vars['sublanguage_search_meta'];
			return "LEFT JOIN $wpdb->postmeta AS $alias ON ($wpdb->posts.ID = $alias.post_id AND $alias.meta_key IN ('".implode("','", esc_sql($fields))."')) " . $join;
		} 
		
		return $join;
	}
	
	/** 
	 * @filter 'posts_distinct_request'
	 *
	 * @from 1.3
	 */
	public function meta_search_distinct($distinct, $wp_query) {
		if (isset($wp_query->query_vars['sublanguage_search_alias'])) {
			return 'DISTINCT';
		}
		return $distinct;
	}
	
	/** 
	 * @filter 'posts_search'
	 *
	 * @from 1.3
	 */
	public function meta_search($search, $wp_query) {
		global $wpdb;
		
		if (isset($wp_query->query_vars['sublanguage_search_alias'])) {
			$alias = $wp_query->query_vars['sublanguage_search_alias'];
			
			$q = $wp_query->query_vars;

			$search = '';

			// added slashes screw with quote grouping when done early, so done later
			$q['s'] = stripslashes( $q['s'] );
			if ( empty( $_GET['s'] ) && $wp_query->is_main_query() )
				$q['s'] = urldecode( $q['s'] );
			// there are no line breaks in <input /> fields
			$q['s'] = str_replace( array( "\r", "\n" ), '', $q['s'] );
			$q['search_terms_count'] = 1;
			if ( ! empty( $q['sentence'] ) ) {
				$q['search_terms'] = array( $q['s'] );
			} else {
				if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $q['s'], $matches ) ) {
					$q['search_terms_count'] = count( $matches[0] );
					$q['search_terms'] = $wp_query->parse_search_terms( $matches[0] );
					// if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence
					if ( empty( $q['search_terms'] ) || count( $q['search_terms'] ) > 9 )
						$q['search_terms'] = array( $q['s'] );
				} else {
					$q['search_terms'] = array( $q['s'] );
				}
			}
		
			$n = ! empty( $q['exact'] ) ? '' : '%';
			$searchand = '';
			$q['search_orderby_title'] = array();

			/**
			 * Filters the prefix that indicates that a search term should be excluded from results.
			 *
			 * @since 4.7.0
			 *
			 * @param string $exclusion_prefix The prefix. Default '-'. Returning
			 *                                 an empty value disables exclusions.
			 */
			$exclusion_prefix = apply_filters( 'wp_query_search_exclusion_prefix', '-' );

			foreach ( $q['search_terms'] as $term ) {
				// If there is an $exclusion_prefix, terms prefixed with it should be excluded.
				$exclude = $exclusion_prefix && ( $exclusion_prefix === substr( $term, 0, 1 ) );
				if ( $exclude ) {
					$like_op  = 'NOT LIKE';
					$andor_op = 'AND';
					$term     = substr( $term, 1 );
				} else {
					$like_op  = 'LIKE';
					$andor_op = 'OR';
				}

				if ( $n && ! $exclude ) {
					$like = '%' . $wpdb->esc_like( $term ) . '%';
					$q['search_orderby_title'][] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $like );
				}

				$like = $n . $wpdb->esc_like( $term ) . $n;

				$termsearch = $wpdb->prepare( "($wpdb->posts.post_title $like_op %s) $andor_op ($wpdb->posts.post_excerpt $like_op %s) $andor_op ($wpdb->posts.post_content $like_op %s)", $like, $like, $like );
				
				// -> modified
				$termsearch .= $wpdb->prepare( " $andor_op ($alias.meta_value $like_op %s)", $like );
			
				$search .= "{$searchand}($termsearch)";

				$searchand = ' AND ';
			}

			if ( ! empty( $search ) ) {
				$search = " AND ({$search}) ";
				if ( ! is_user_logged_in() ) {
					$search .= " AND ({$wpdb->posts}.post_password = '') ";
				}
			}
		
		}
		
		return $search;
		
	}
	
	
  
}

