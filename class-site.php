<?php 

class Sublanguage_site extends Sublanguage_current {
	
	/**
	 * @var boolean
	 */
	var $canonical = true;	
	
	/**
	 * @compat from 2.0
	 * @var int
	 */
	var $current_language;
	
	/**
	 *
	 * @from 1.0
	 */
	public function __construct() {
		
		add_filter('locale', array($this, 'get_locale'));
		add_action( 'plugins_loaded', array($this, 'load'));
		
	}
	
	/**
	 * @from 1.4.7
	 */
	public function load() {
		
		if ($this->current_language = $this->get_language()) {
			
			parent::load();
			
			add_filter('the_title', array($this, 'translate_post_title'), 10, 2);
			add_filter('get_the_excerpt', array($this, 'translate_post_excerpt'), 9);
			add_filter('single_post_title', array($this, 'translate_single_post_title'), 10, 2);
			add_filter('get_post_metadata', array($this, 'translate_meta_data'), 10, 4);
			add_filter('wp_setup_nav_menu_item', array($this, 'translate_menu_nav_item'));
			add_filter('wp_nav_menu_objects', array($this, 'filter_nav_menu_objects'), 10, 2); // -> @from 1.5. Filter list for hidden items 
			add_filter('tag_cloud_sort', array($this,'translate_tag_cloud'), 10, 2);
			add_action('init', array($this, 'init'));
			
			$this->add_options_filters();
			
		}
		
	}

	/**
	 * @from 1.0
	 */	
	public function init() {
		
		if (get_option('permalink_structure')) {
			
			add_filter('query_vars', array($this, 'query_vars'));
			add_filter('request', array($this, 'catch_translation')); // detect query type and language out of query vars
 			add_action('wp', array($this, 'redirect_uncanonical'), 11);
			
		}
		
		// enqueue AJAX script
		if ($this->get_option('frontend_ajax')) {
			
			add_action('wp_enqueue_scripts', array($this, 'ajax_enqueue_scripts'));
		
		}
		
		// link filters only after request have been parsed
		add_action('parse_request', array($this, 'add_links_translation_filters'));
		
		// login	
		add_filter('login_url', array($this, 'translate_login_url'));
		add_filter('lostpassword_url', array($this, 'translate_login_url'));
		add_filter('logout_url', array($this, 'translate_login_url'));
		add_filter('register_url', array($this, 'translate_login_url'));
		add_action('login_form', array($this, 'translate_login_form'));
		add_action('lostpassword_form', array($this, 'translate_login_form'));
		add_action('resetpass_form', array($this, 'translate_login_form'));
		add_action('register_form', array($this, 'translate_login_form'));
		add_filter('retrieve_password_message', array($this, 'translate_retrieve_password_message'));
		add_filter('lostpassword_redirect', array($this, 'lostpassword_redirect'));
		add_filter('registration_redirect', array($this, 'registration_redirect'));
		
		// print hreflang in template head
		add_action('wp_head', array($this, 'print_hreflang')); // -> added in 1.4.5
		
		// API
		add_action('sublanguage_print_language_switch', array($this, 'print_language_switch'));
		add_filter('sublanguage_custom_translate', array($this, 'custom_translate'), 10, 3);
		
		/**
		 * Hook called after initializing most hooks and filters
		 *
		 * @from 1.2
		 *
		 * @param Sublanguage_site object
		 */	
		do_action('sublanguage_init', $this);
		
	}
	
	/**
	 * Override set_language for compat
	 *
	 * @from 2.0
	 *
	 * @param object WP_post $language Language. Optional
	 */
	public function set_language($language = null) {
		
		Sublanguage_core::set_language($language);
		
		$this->current_language = $this->get_language();
	}
	
	/**
	 * Custom translation. Sublanguage API
	 *
	 * Filter for 'sublanguage_custom_translate'
	 *
	 * @from 1.0
	 */	
	public function custom_translate($content, $callback, $args = null) {
		
		if ($this->has_language()) {
			
			return call_user_func($callback, $content, $this->get_language(), $this, $args); 
		
		}
		
		return $content;
	}
	
	/**
	 * Request current language
	 *
	 * Override Sublanguage_core::request_language()
	 *
	 * @from 2.0
	 *
	 * @return object WP_post
	 */
	public function request_language() {
		
		if (isset($_REQUEST[$this->language_query_var])) {
	
			$language = $this->get_language_by($_REQUEST[$this->language_query_var], 'post_name');
	
		} else if (isset($_SERVER['REQUEST_URI'])) {
			
			if (preg_match('/\/('.implode('|', $this->get_language_column('post_name')).')(\/|$|\?|#)/', $_SERVER['REQUEST_URI'], $matches)) { // -> language detected!
	
				$language = $this->get_language_by($matches[1], 'post_name');
		
				if ($this->is_default($language) && !$this->get_option('show_slug')) {
				
					$this->canonical = false;
			
				}
		
			} else {
				
				if ($this->get_option('show_slug')) {
				
					$this->canonical = false;
			
				} 
			
				if ($this->get_option('autodetect')) { // auto detect language on home page
			
					// detect only on home page? --> rtrim($_SERVER['SCRIPT_URI'], '/') == rtrim(home_url())
				
					$detected_language = $this->auto_detect_language();
				
					if ($detected_language) {
				
						$language = $detected_language;
					
					}
	
				}
			
			}
	
		} 
		
		if (empty($language)) {
		
			$language = $this->get_default_language();
		
		}
		
		if ($language && get_post_meta($language->ID, 'rtl', true)) {
		
			$GLOBALS['text_direction'] = 'rtl';
		
		}
		
		return $language;
	}
	
	/**
	 * Filter for 'locale'
	 *
	 * @from 1.0
	 */
	public function get_locale($locale) {
		
		if ($language = $this->get_language()) {
		
			return $language->post_content;
		
		}
		
		return $locale;
	}	
	
	/**
	 *	Translate menu nav items
	 *	Filter for 'wp_setup_nav_menu_item'
	 */
	public function translate_menu_nav_item($menu_item) {
		
		if ($menu_item->type == 'post_type') {
			
			if ($this->is_post_type_translatable($menu_item->object)) {
				
				$original_post = get_post($menu_item->object_id);
				
				$menu_item = $this->translate_nav_menu_item($menu_item);
				
				if (empty($menu_item->post_title)) {
				
					$menu_item->title = $this->translate_post_field($original_post, 'post_title', null, $menu_item->title);
					
				} else {
					
					$menu_item->title = $menu_item->post_title;
				
				}
				
				$menu_item->url = get_permalink($original_post); 
				
			}
			
		} else if ($menu_item->type == 'taxonomy') {
			
			if ($this->is_taxonomy_translatable($menu_item->object)) {
				
				$original_term = get_term($menu_item->object_id, $menu_item->object);
				
				if ($original_term && !is_wp_error($original_term)) {
				
					$menu_item = $this->translate_nav_menu_item($menu_item);
				
					if (empty($menu_item->post_title)) {
					
						$menu_item->title = $this->translate_term_field($original_term, $original_term->taxonomy, 'name', null, $menu_item->title);
					
					} else {
					
						$menu_item->title = $menu_item->post_title;
				
					}
				
					// url already filtered
				
				}
				
			}
		
		} else if ($menu_item->type == 'custom') {
			
			if ($menu_item->title == 'language') {
				
				static $languages, $language_index;
				
				if (!isset($languages)) {
				
					$languages = $this->get_sorted_languages();
				
				}
				
				if (!isset($language_index) || $language_index >= count($languages)) {
					
					$language_index = 0;
					
				}
					
				$language = $languages[$language_index];
				
				/**
				 * Filter language name
				 *
				 * @from 1.2
				 *
				 * @param WP_post object
				 */
				$menu_item->title = apply_filters('sublanguage_language_name', $language->post_title, $language);
				$menu_item->url = $this->get_translation_link($language);
				$menu_item->classes[] = $this->is_current($language) ? 'active_language' : 'inactive_language';
				$menu_item->classes[] = 'sublanguage';
				$menu_item->classes[] = $language->post_name;
				
				$language_index++;
			
			}
			
			$menu_item = $this->translate_nav_menu_item($menu_item, true);
			
		}
		
		return $menu_item;
		
	}
	
	/**
	 * Translate a nav menu item
	 *
	 * @param object WP_Post $menu_item
	 * @return object WP_Post
	 *
	 * @from 1.5
	 */
	public function translate_nav_menu_item($menu_item, $fill_default_title = false) {
	
		if ($this->is_sub() && $this->is_post_type_translatable('nav_menu_item')) {
		
			$menu_item->post_title = $this->translate_post_field($menu_item, 'post_title', null, ($fill_default_title ? $menu_item->title : ''));
			$menu_item->description = $this->translate_post_field($menu_item, 'post_content', null, $menu_item->description);
			$menu_item->attr_title = $this->translate_post_field($menu_item, 'post_excerpt', null, $menu_item->attr_title);
			
		}
		
		return $menu_item;	
	}
	
	/**
	 * Remove items that need to be hidden in current language 
	 *
	 * Filter for 'wp_nav_menu_objects'
	 *
	 * @from 1.5
	 */
	public function filter_nav_menu_objects($sorted_menu_items, $args) {
		
		if ($this->is_post_type_translatable('nav_menu_item') && in_array('sublanguage_hide', $this->get_post_type_metakeys('nav_menu_item'))) {
			
			$filtered_items = array();
			
			foreach ($sorted_menu_items as $menu_item) {
				
				if ($this->is_sub() && !get_post_meta($menu_item->ID, $this->get_prefix().'sublanguage_hide', true) || $this->is_main() && !get_post_meta($menu_item->ID, 'sublanguage_hide', true)) {
				
					$filtered_items[] = $menu_item;
				
				}
				
			}
			
			return $filtered_items;
		}
		
		return $sorted_menu_items;
	}
	
	/**
	 * Print language switch
	 *
	 * hook for 'sublanguage_print_language_switch'
	 *
	 * @from 1.0
	 */
	public function print_language_switch($context = null) {
		
		$languages = $this->get_sorted_languages();
		
		if (has_action('sublanguage_custom_switch')) {
		
			/**
			 * Customize language switch output
			 *
			 * @from 1.2
			 *
			 * @param array of WP_Post language custom post
			 * @param Sublanguage_site $this The Sublanguage instance.
			 * @param mixed context
			 */
			do_action_ref_array('sublanguage_custom_switch', array($languages, $this, $context));
			
		} else {
			
			$output = '<ul>';
			
			foreach ($languages as $language) {
			
				/**
				 * Filter language name
				 *
				 * @from 1.2
				 *
				 * @param string language name
				 * @param WP_Post language custom post
				 */
				$output .= sprintf('<li class="%s%s"><a href="%s">%s</a></li>',
					$language->post_name,
					($this->is_current($language) ? ' current' : ''),
					$this->get_translation_link($language),
					apply_filters('sublanguage_language_name', $language->post_title, $language)
				);

			}
	
			$output .= '</ul>';
			
			echo $output;
		
		}
		
	}
	
	/**
	 * Register sublanguage specific query_vars
	 *
	 * @filter 'query_vars'
	 *
	 * @from 1.0
	 */
	public function query_vars($public_query_vars) {
		
		$public_query_vars[] = 'sublanguage_slug';
		$public_query_vars[] = 'sublanguage_page';
		$public_query_vars[] = 'preview_language';
		
		return $public_query_vars;
	
	}
	
	
	/**
	 * Intercept query_vars to find out type of query and get parent.
	 * Must return an array of query vars
	 *
	 * Hook for 'request'
	 *
	 * @from 1.0
	 */
	public function catch_translation($query_vars) {
		global $wp_rewrite;
		
		if (isset($query_vars['sublanguage_page']) || isset($query_vars['pagename']) || isset($query_vars['name'])) { // -> page, post or custom post type 
			
			$name = '';
			
			if (isset($query_vars['sublanguage_page'])) {
				
				$name = $query_vars['sublanguage_page'];
			
			} else if (isset($query_vars['pagename'])) {
				
				$name = $query_vars['pagename'];
			
			} else if (isset($query_vars['name'])) {
				
				$name = $query_vars['name'];
			
			}
			
			$ancestors = explode('/', $name);
			
			// -> remove the permalink structure prefix if there is one
			if (isset($query_vars['sublanguage_page']) && !empty($wp_rewrite->front) && $wp_rewrite->front !== '/' && trim($wp_rewrite->front, '/') === $ancestors[0]) {
				
				array_shift($ancestors);
			
			}
			
			$post_name = array_pop($ancestors);
			
			$post_types = isset($query_vars['post_type']) ? array($query_vars['post_type']) : array('page', 'post');
			
			$post = $this->query_post($post_name, $post_types, $ancestors);
			
			if ($post) {
				
				$post_type_obj = get_post_type_object($post->post_type);
				
				if (isset($query_vars['sublanguage_slug']) && $query_vars['sublanguage_slug'] !== $this->translate_cpt($post->post_type, null, $post->post_type)) {
					
					// wrong slug
					$this->canonical = false;
					
				}
				
				if ($post_type_obj->hierarchical) {
					
					$path = '';
					$parent_id = $post->post_parent;
					
					while ($parent_id) {
						
						$parent = get_post($parent_id);
						$path = $parent->post_name . '/' . $path;
						$parent_id = $parent->post_parent;
						
					}
					
					if (isset($query_vars[$post->post_type])) {
						
						$query_vars[$post->post_type] = $path . $post->post_name;
					
					}
					
					if (isset($query_vars['name'])) {
						
						$query_vars['name'] = $path . $post->post_name;
						
					} else {
						
						$query_vars['pagename'] = $path . $post->post_name;
					
					}
					
				} else {
				
					if (isset($query_vars['pagename'])) {
						
						$query_vars['pagename'] = $post->post_name;
						
					} else {
						
						$query_vars['name'] =  $post->post_name;
					
					}
					
					if (isset($query_vars[$post->post_type])) {
						
						$query_vars[$post->post_type] = $post->post_name;
					
					}
					
				}
				
			} else if (isset($query_vars['sublanguage_page'])) { // -> nothing found. Let's pretend we did not see
			
				$query_vars['name'] = $query_vars['sublanguage_page'];
			
			}
			
		} else if (isset($query_vars['attachment']) && $this->is_post_type_translatable('attachment')) { // -> attachment (this is a child of a "post" post-type)
			
			$post = $this->query_post($query_vars['attachment'], 'attachment');
			
			if ($post) {
			
				$query_vars['attachment'] = $post->post_name; 
				
			}

		} else if (isset($query_vars['post_type'])) { // -> custom-post-type archive
		
			$post_type = $query_vars['post_type'];
			
			if ($this->is_post_type_translatable($post_type)) {
								
				if (isset($query_vars['sublanguage_slug']) && $query_vars['sublanguage_slug'] !== $this->translate_cpt_archive($post_type, null)) {
	
					// wrong slug
					$this->canonical = false;

				}
				
			}
			
		} else if ($results = array_filter(array_map(array($this, 'query_var_to_taxonomy'), array_keys($query_vars)), array($this, 'is_taxonomy_translatable'))) { // -> untranslated taxonomy
			
			if (isset($query_vars['sublanguage_slug'])) {
			
				$taxonomy = '';
			
				foreach ($results as $r) {
				
					$taxonomy = $r;
					break;
			
				}
				
				if (!$taxonomy) throw new Exception('Taxonomy not found!');
			
				$tax_obj = get_taxonomy($taxonomy);
				$tax_qv = $tax_obj->query_var;
				$term_name = $query_vars[$tax_qv];
				$term = $this->query_taxonomy($term_name, $taxonomy);
			
				if ($term) {
			
					$query_vars[$tax_qv] = $term->slug; // -> restore original language name in query_var
				
					$tax_translation = $this->translate_taxonomy($taxonomy, null, $taxonomy);
				
					if ($this->translate_taxonomy($taxonomy, null, $taxonomy) !== $query_vars['sublanguage_slug']) { // taxonomy should be translated
						
						$this->canonical = false;
			
					}
				
				}
				
			}
			
		}
		
		if (isset($query_vars['preview'])) {
			
			$this->canonical = true;
			
		}
		
		return $query_vars;
		
	}
	
	/**
	 * Add links translation filters after all query variables for the current request have been parsed.
	 *
	 * @hook 'parse_request'
	 * @from 2.0
	 */
	public function add_links_translation_filters($wp = null) {
	
		add_filter('home_url', array($this,'translate_home_url'), 10, 4);
		add_filter('pre_post_link', array($this, 'pre_translate_permalink'), 10, 3);
		add_filter('post_link', array($this, 'translate_permalink'), 10, 3);
		add_filter('page_link', array($this, 'translate_page_link'), 10, 3);
		add_filter('post_type_link', array($this, 'translate_custom_post_link'), 10, 3);
		add_filter('attachment_link', array($this, 'translate_attachment_link'), 10, 2);
		add_filter('post_link_category', array($this, 'translate_post_link_category'), 10, 3); // not implemented yet
		add_filter('post_type_archive_link', array($this, 'translate_post_type_archive_link'), 10, 2);
		add_filter('year_link', array($this,'translate_month_link'));
		add_filter('month_link', array($this,'translate_month_link'));
		add_filter('day_link', array($this,'translate_month_link'));
		add_filter('term_link', array($this, 'translate_term_link'), 10, 3);
		add_filter('get_edit_post_link', array($this, 'translate_edit_post_link'), 10, 3);
		
	}
	
	
	/**
	 * Redirection when not canoncal url
	 * Must be fired after filters. 
	 * Must be fired after conditional tags are set.
	 *
	 * @from 1.0
	 */
	public function redirect_uncanonical() {
		
		$query_object = get_queried_object();
		
		if (!$this->canonical) {
			
			if (is_singular()) {
				
				$url = get_permalink($query_object->ID);
				
			} else if (is_post_type_archive()) {
				
				$url = get_post_type_archive_link($query_object->name);
			
			} else if (is_category() || is_tag() || is_tax()) {
			
				$url = get_term_link($query_object->term_id, $query_object->taxonomy);
				
			} else {
				
				$url = home_url();
				
			}
			
			wp_redirect($url);
			
			exit;
			
		}
		
	}	
		

	/**
	 *	Find original post based on query vars info.
	 *  
	 *  @from 1.0
	 *
	 * @param string $post_name
	 * @param string|array $post_types
	 */
	public function query_post($post_name, $post_types, $ancestors = array()) {
		global $wpdb;
		
		$post_types = esc_sql($post_types);
		
		$post_type_strings = is_array($post_types) ? "'".implode("','", $post_types)."'" : "'".$post_types."'";
		
		$translation_slug = $this->get_prefix().'post_name';
		
		$post_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", 
			$translation_slug,
			$post_name
		));
		
		if ($post_ids) { 
		
			// Translations found but we're not sure about post_type
			$posts = $wpdb->get_results(
				"SELECT post.* FROM $wpdb->posts AS post
					WHERE post.post_type IN ($post_type_strings)
						AND post.ID IN (".implode(",", array_map('intval', $post_ids)).")"
			);
			
			// Use WP_Query (for caching)
// 			$posts_query = new WP_Query(array(
// 				'post_type' => $post_types,
// 				'post__in' => $post_ids,
// 				'posts_per_page' => -1,
// 				'ignore_sticky_posts' => true
// 			));
// 			$posts = $posts_query->posts;
			
			foreach ($posts as $post) {
				
				if ($ancestors) { // -> verify ancestors recursively
					
					$parent_name = array_pop($ancestors);
					
					$parent = $this->query_post($parent_name, $post_types, $ancestors);
					
					if ($parent && $post->post_parent == $parent->ID) {
						
						return $post;
					
					}
				
				} else {
					
					// This one will just do
					return $post;
				
				}
				
			}
			
			foreach ($posts as $post) {
			
				// -> no ancestor matched
				$this->canonical = false;
			
				// This one will do
				return $post;
				
			}
			
		}
		
		$posts = $wpdb->get_results( $wpdb->prepare(
			"SELECT post.* FROM $wpdb->posts AS post
				WHERE post.post_name = %s AND post.post_type IN ($post_type_strings)",
			$post_name
		));

		// Use WP_Query
// 		$posts_query = new WP_Query(array(
// 			'post_type' => $post_types,
// 			'name' => $post_name,
// 			'posts_per_page' => -1,
// 			'ignore_sticky_posts' => true
// 		));
// 		$posts = $posts_query->posts;
					
		if ($posts) { 
			
			foreach ($posts as $post) {
				
				if ($ancestors) { // -> we need to verify ancestors recursively
					
					$parent_name = array_pop($ancestors);
					
					$parent = $this->query_post($parent_name, $post_types, $ancestors);
					
					// check if parent match and there is no specific translation...
					if ($parent && $post->post_parent == $parent->ID) {
						
						// Post found
						if ($this->is_post_type_translatable($post->post_type) && get_post_meta($post->ID, $this->get_prefix() . 'post_name', true)) {
							
							// But there is a specific translation for this post
							$this->canonical = false;
				
						}
						
						return $post;
					
					}
				
				} else {
					
					// Post found
					if ($this->is_post_type_translatable($post->post_type) && get_post_meta($post->ID, $this->get_prefix() . 'post_name', true)) {
				
						// But there is a specific translation for this post
						$this->canonical = false;
				
					}
					
					return $post;
				
				}
				
			}
			
			foreach ($posts as $post) {
			
				$this->canonical = false;
			
				// no ancestor matched but lets return this one
				return $post;
				
			}
			
		} else {
			
			// Nothing found. -> Search in other languages...
			$post = $wpdb->get_row( $wpdb->prepare(
				"SELECT post.* FROM $wpdb->posts AS post
					INNER JOIN $wpdb->postmeta AS meta ON (post.ID = meta.post_id)
					WHERE post.post_type IN ($post_type_strings) AND (meta_key IN ('".implode("post_name', '", esc_sql(array_map(array($this, 'create_prefix'), $this->get_language_column('post_name'))))."post_name') AND meta.meta_value = %s)",					
				$post_name
			));
			
			// teacher, do I need to check ancestors?
			
			if ($post) {
			
				// Post found in wrong language.
				$this->canonical = false;
				
				return $post;
		
			}
			
		}
		
		return false;
		
	}

	/**
	 *	Find original term based on query vars info.
	 *  
	 *  @from 1.0
	 *
	 * @param string $slug
	 * @param string|array $taxonomy
	 */
	public function query_taxonomy($slug, $taxonomies) {
		global $wpdb;
		
		$taxonomy_string = is_array($taxonomies) ? "'".implode("','", esc_sql($taxonomies))."'" : "'".esc_sql($taxonomies)."'";
		
		$translation_slug = $this->get_prefix() . 'slug';
		
		$term_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT term_id FROM $wpdb->termmeta WHERE meta_key = %s AND meta_value = %s", 
			$translation_slug,
			$slug
		));
		
		if ($term_ids) { 
		
			// Translations found but we're not sure about taxonomy
			$term = $wpdb->get_row(
				"SELECT t.term_id, t.slug, tt.taxonomy, tt.parent FROM $wpdb->terms AS t 
					INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
					WHERE tt.taxonomy IN ($taxonomy_string)
						AND t.term_id IN (".implode(",", array_map('intval', $term_ids)).")"
			);
			
			return $term;

// 			$terms = get_terms(array(
// 				'taxonomy' => $taxonomies,
//     		'hide_empty' => false,
//     		'include' => $term_ids,
//     		'language' => false
// 			));

		}
		
		// -> no translated term for this slug
		$term = $wpdb->get_row( $wpdb->prepare(
			"SELECT t.term_id, t.slug, tt.taxonomy, tt.parent FROM $wpdb->terms AS t 
				INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy IN ($taxonomy_string)
					AND t.slug = %s",
			$slug
		));
		
// 		$terms = get_terms(array(
// 			'taxonomy' => $taxonomies,
// 			'hide_empty' => false,
// 			'slug' => $slug,
// 			'language' => false
// 		));

		if ($term) {
		
			if ($this->is_taxonomy_translatable($term->taxonomy) && get_term_meta($term->term_id, $this->get_prefix() . 'slug', true)) {
				
				// -> But there is a specific translation for this term
				$this->canonical = false;
			
			}
			
			return $term;
							
		} else {
			
			// Nothing found. -> Search in other languages...
			
			$language_slugs = $this->get_language_column('post_name');
			
			$term_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT term_id FROM $wpdb->termmeta WHERE meta_key IN ('_" . implode("slug','", esc_sql(array_map(array($this, 'create_prefix'), $language_slugs))) . "slug') AND meta_value = %s", 
				$slug
			));
			
			if ($term_ids) { 
				
				$term = $wpdb->get_row(
					"SELECT t.term_id, t.slug, tt.taxonomy, tt.parent FROM $wpdb->terms AS t 
						INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
						WHERE tt.taxonomy IN ($taxonomy_string)
							AND t.term_id IN (".implode(",", array_map('intval', $term_ids)).")"
				);
				
				if ($term) {
			
					// Term found in wrong language.
					$this->canonical = false;
				
					return $term;
		
				}
				
			}
			
		}
		
		return false;
	
	}

	
	
	/**
	 * Add language slug in login url
	 *
	 * Filter for 'login_url', 'logout_url', 'lostpassword_url', 'register_url'
	 *
	 * @from 1.2
	 */
	public function translate_login_url($login_url){
		
		if ($this->has_language()) {
		
			$login_url = add_query_arg(array($this->language_query_var => $this->get_language()->post_name), $login_url);
			
		}
	
		return $login_url;
		
	}	
	
	/**
	 * Add language input in login forms
	 *
	 * Hook for 'login_form', 'lostpassword_form', 'resetpass_form', 'register_form'
	 *
	 * @from 1.2
	 */
	public function translate_login_form() {
	
		echo '<input type="hidden" name="'.$this->language_query_var.'" value="'.$this->get_language()->post_name.'"/>';

	}
	
	/**
	 * Translate link in retrieve password message
	 *
	 * Filter for 'retrieve_password_message'
	 *
	 * @from 1.2
	 */
	public function translate_retrieve_password_message($message) {
	
		return  preg_replace('/(wp-login\.php[^>]*)/', '$1'.'&'.$this->language_query_var.'='.$this->get_language()->post_name, $message);
		
	}
	
	/**
	 * lostpassword redirect
	 *
	 * Filter for 'lostpassword_redirect'
	 *
	 * @from 1.2
	 */
	public function lostpassword_redirect($redirect_to) {
		
		return 'wp-login.php?checkemail=confirm'.'&'.$this->language_query_var.'='.$this->get_language()->post_name;
	
	}
	
	/**
	 * registration redirect
	 *
	 * Filter for 'registration_redirect'
	 *
	 * @from 1.4.1
	 */
	public function registration_redirect($redirect_to) {
		
		return 'wp-login.php?checkemail=registered'.'&'.$this->language_query_var.'='.$this->get_language()->post_name;
	
	}	
	
	/**
	 * Detect language
	 *
	 * @from 1.2
	 */
	public function auto_detect_language() {
		
		if (class_exists('Locale')) {
			
			$locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']); 
			
			return $this->find_language_by_locale($locale);
		
		}
		
		return false;
		
	}
	
	/**
	 * Find best matching language from locale code
	 * 
	 * @from 1.5.2
	 *
	 * @param string $locale Locale code
	 * @return object|false Language Object
	 */
	public function find_language_by_locale($locale) {
		
		$language = $this->get_language_by($locale, 'post_content');
		
		if (!$language) {
			
			$locale = preg_replace("/^([a-z]+).*/", '$1', $locale);
			
			$language = $this->get_language_by($locale, 'post_content');
			
			if (!$language) {
				
				$locales = preg_grep("/$locale/", $this->get_language_column('post_content'));
				
				if ($locales) {
					
					$language = $this->get_language_by(array_shift($locales), 'post_content');
					
				}
				
			}
			
		}
		
		return $language;
	}
	
	/**
	 * Get language link
	 *
	 * @from 1.2
	 */
	public function get_translation_link($language) {
		global $wp_query, $wp_rewrite;
		
		$query_object = get_queried_object();
				
		$this->set_language($language); // -> pretend this is the current language
		
		$link = '';
		
		if (is_category() || is_tag() || is_tax()) {
						
			$original_term = get_term($query_object->term_id, $query_object->taxonomy);
						
			$link = get_term_link($original_term, $original_term->taxonomy);
						
		} else if (is_post_type_archive()) {
			
			$link = get_post_type_archive_link(get_post_type());
			
		} else if (is_singular() || $wp_query->is_posts_page) {
					
			$link = get_permalink($query_object->ID);
		
		} else if (is_date()) {
			
			if (is_day()) 
				$link = get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
			else if (is_month()) 
				$link = get_month_link(get_query_var('year'), get_query_var('monthnum'));
			else if (is_year()) 
				$link = get_year_link(get_query_var('year'));
			else 
				$link = home_url('/');
				
		} else if (is_author()) {
		
			$link = get_author_posts_url(get_user_by('slug', get_query_var('author_name'))->ID);
		
		} else if (is_search()) {
			
			$link = get_search_link( get_search_query() );
			
		} else { // is_home, is_404
		
			$link = home_url('/');
			
		}
		
		/* TODO: keep paged, endpoints and url search arguments 
		
			 -> get_pagenum_link()
			 
		*/
		
		$this->restore_language(); // restore original current language after messing with it		
		
		return $link;
	}
	
	/**
	 * Check if current page has translation
	 *
	 * @from 2.5
	 */
	public function has_translation($language) {
		global $wp_query;
		
		if (is_category() || is_tag() || is_tax()) {
			
			$query_object = get_queried_object();
			
			return apply_filters('sublanguage_has_term_translation', true, $query_object, $language, $this);
				
		} else if (is_post_type_archive()) {
			
			return apply_filters('sublanguage_has_archive_translation', true, $language, $this);
			
		} else if (is_singular() || $wp_query->is_posts_page) {
			
			$query_object = get_queried_object();
			
			return apply_filters('sublanguage_has_post_translation', true, $query_object, $language, $this);
			
		} else if (is_date()) {
			
			return apply_filters('sublanguage_has_date_translation', true, $language, $this);
				
		} else if (is_author()) {
		
			return apply_filters('sublanguage_has_author_translation', true, $language, $this);
		
		} else if (is_search()) {
			
			return apply_filters('sublanguage_has_search_translation', true, $language, $this);
			
		} else if (is404()) {
		
			return apply_filters('sublanguage_has_404_translation', true, $language, $this);
			
		} else if (is_home()) {
		
			return apply_filters('sublanguage_has_home_translation', true, $language, $this);
			
		}
		
		return apply_filters('sublanguage_has_other_translation', true, $language, $this);
	}
	
	/** 
	 * Get taxonomy query var
	 *
	 * @from 1.0
	 */
	public function taxonomy_to_query_var($taxonomy_name) {
	
		$t = get_taxonomy($taxonomy_name);
		
		if (isset($t->query_var)) {
			
			return $t->query_var;
			
		}
		
		return false;
		
	}
	
	/**
	 * Find taxonomy by query var
	 * 
	 * @from 1.0
	 */
	public function query_var_to_taxonomy($taxonomy_qv) {
	
		$results = get_taxonomies(array('query_var' => $taxonomy_qv));
		
		foreach ($results as $result) {
		
			return $result;
		
		}
		
		return false;
		
	}


	/**
	 * Override get_language to select only published language
	 *
	 * @from 1.2.2
	 *
	 * @return array of WP_post objects
	 */
	public function get_languages() {
		global $wpdb;
		
		static $languages;
		
		if (!isset($languages)) {
			
			$languages = $wpdb->get_results( $wpdb->prepare(
				"SELECT post.ID, post.post_name, post.post_title, post.post_content, post.post_excerpt, post.menu_order, post.post_status FROM $wpdb->posts AS post
					WHERE post.post_type = %s AND post_status = %s
					ORDER BY post.menu_order ASC",					
				$this->language_post_type,
				'publish'
			));
			
		}
    
		return $languages;
		
	}
	
	/**
	 * Get languages and sort by "current first"
	 *
	 * @from 2.0
	 *
	 * @return array of WP_post objects
	 */
	public function get_sorted_languages() {
		
		$languages = $this->get_languages();
		
		if ($languages && $this->get_option('current_first')) {
		
			$current = $this->get_language();
			
			array_splice($languages, array_search($current, $languages), 1);
			array_unshift($languages, $current);		
		
		}
		
		return $languages;
	}
	
	
	/**
	 * Print hreflang
	 *
	 * Filter for 'wp_head'
	 *
	 * @from 1.4.5
	 */
	public function print_hreflang() {
	
		$languages = $this->get_languages();
		
		$output = '';
		
		foreach ($languages as $language) {
			
			if ($this->has_translation($language)) {
			
				$output .= sprintf('<link rel="alternate" href="%s" hreflang="%s" />',
					$this->get_translation_link($language),
					$language->post_content ? strtolower(str_replace('_', '-', $language->post_content)) : 'en'
				);
				
			}
			
		}
		
		echo $output;
		
	}
	
	/**
	 * Add filters for options translation
	 *
	 * @from 1.5
	 */
	public function add_options_filters() {
		
		$translations = $this->get_option('translations', array());
		
		$language = $this->get_language();
		
		if ($language && isset($translations['option'][$language->ID])) {
			
			foreach ($translations['option'][$language->ID] as $option => $val) {
				
				add_filter('option_' . $option, array($this,  'filter_option'), 10, 2);
				
			}
			
		}
		
	}
	
	/**
	 * Add filters for options translation
	 *
	 * @from 1.5
	 */
	public function filter_option($value, $option = null) {
		
		if (empty($option)) return $value; // $option is only defined since wp 4.4
		
		$translations = $this->get_option('translations', array());
		
		$language = $this->get_language();
		
		if ($language && isset($translations['option'][$language->ID][$option])) {

			$this->translate_option($value, $translations['option'][$language->ID][$option]);
		
		}
		
		return $value;
	}

	/**
	 * translate options
	 *
	 * @from 1.5.3 add striplashes
	 * @from 1.5
	 */	
	private function translate_option(&$option, $translation ) {
		
		if (is_array($translation)) {
		
			foreach ($translation as $key => $value) {
				
				if (isset($option[$key])) {
				
					$item = $this->translate_option($option[$key], $value );
					
				}
				
			}
			
		} else {
			
			$option = stripslashes($translation);
			
		}
		
	}

	/**
	 * Translate post. This function is overrided on front-end.
	 *
	 * @override Sublanguage_Core::translate_post()
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
				
				if ($field !== 'post_name') {
				
					$post->$field = $this->translate_post_field($post, $field, $language);
				
				}
				
			}
				
			$post->sublanguage = true;
			
		}
		
		return $post;
		
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
			$term->description = $this->translate_term_field($term, $term->taxonomy, 'description', $language);
			
		}
		
		return $term;
				
	}

	
}
