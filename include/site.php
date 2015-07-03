<?php 

class Sublanguage_site extends Sublanguage_main {
	
	/**
	 * @var boolean
	 */
	var $canonical = true;	
	
	/**
	 * @var int
	 */
	var $menu_language_index = 0;
	
	/**
	 *
	 * @from 1.0
	 */
	public function __construct() {
		
		parent::__construct();
		
		
		$this->detect_language();
		
		
		//add_action('plugins_loaded', array($this, 'load'), 11);
		
		add_action('init', array($this, 'init'));
		
	}

	/**
	 * @from 1.0
	 */	
	public function init() {
		
		add_action('parse_query', array($this, 'allow_filters')); // allow filters on menu get_posts
		add_filter('the_posts', array($this, 'translate_the_posts'), 10, 2);
		
		
		//add_filter('the_posts', array($this, 'hard_translate_posts'), 20, 2);
		
		add_filter('single_term_title', array($this, 'filter_single_term_title'));
		add_filter('single_cat_title', array($this, 'filter_single_term_title'));
		add_filter('single_tag_title', array($this, 'filter_single_term_title'));
		add_filter('the_content', array($this, 'translate_post_content'), 9);
		add_filter('the_title', array($this, 'translate_post_title'), 10, 2);
		add_filter('get_the_excerpt', array($this, 'translate_post_excerpt'), 9);
		add_filter('single_post_title', array($this, 'translate_single_post_title'), 10, 2);

		add_filter('get_post_metadata', array($this, 'translate_meta_data'), 10, 4);
		add_filter('wp_setup_nav_menu_item', array($this, 'translate_menu_nav_item'));
		add_filter('list_cats', array($this, 'translate_term_name'), 10, 2);
		add_filter('get_the_terms', array($this, 'translate_post_terms'), 10, 3); // filter in get_the_terms()
		add_filter('get_terms', array($this, 'translate_get_terms'), 10, 3);
		add_filter('tag_cloud_sort', array($this,'translate_tag_cloud'), 10, 2);
		
 		add_filter('home_url', array($this,'translate_home_url'), 10, 4); // should it not be set later?
		
		// SEARCH
		add_filter('posts_search', array($this, 'posts_search'), null, 2);
		
		// API
		add_action('sublanguage_print_language_switch', array($this, 'print_language_switch'));
		add_action('init', array($this, 'register_postmeta_keys'), 99); // register post meta
		add_filter('sublanguage_custom_translate', array($this, 'custom_translate'), null, 3);
		add_filter('sublanguage_translate_post_field', array($this, 'translate_post_field_custom'), null, 3);
		add_action('sublanguage_load_admin', array($this, 'load_admin'));
		do_action('sublanguage_init', $this);
		
		if ($this->options['current_first']) {
			
			$this->set_current_language_first();
		
		}
		
		if (get_option('permalink_structure')) {
		
			add_rewrite_tag('%sub_tax_term%', '([^&]+)');
			add_rewrite_tag('%sub_tax_o%', '([^&]+)');
			add_rewrite_tag('%sub_tax_t%', '([^&]+)');
			add_rewrite_tag('%sub_tax_qv%', '([^&]+)');
		
			// cpt
			add_rewrite_tag('%sub_cpt_name%', '([^&]+)');
			add_rewrite_tag('%sub_cpt_o%', '([^&]+)');
			add_rewrite_tag('%sub_cpt_t%', '([^&]+)');
			add_rewrite_tag('%sub_cpt_qv%', '([^&]+)');
		
			//nodepage
			add_rewrite_tag('%nodepage_type%', '([^&]+)');
			add_rewrite_tag('%nodepage_parent%', '([^&]+)');
			add_rewrite_tag('%nodepage_path%', '([^&]+)');
			add_rewrite_tag('%nodepage%', '([^&]+)');
		
			// node terms
			add_rewrite_tag('%nodeterm_tax%', '([^&]+)');
			add_rewrite_tag('%nodeterm_ttax%', '([^&]+)');
			add_rewrite_tag('%nodeterm_parent%', '([^&]+)');
			add_rewrite_tag('%nodeterm_path%', '([^&]+)');
			add_rewrite_tag('%nodeterm_qv%', '([^&]+)');
			add_rewrite_tag('%nodeterm%', '([^&]+)');
		
			add_rewrite_tag('%preview_language%', '([^&]+)');
		
			add_filter('request', array($this, 'catch_translation')); // detect query type and language out of query vars
		
 			add_action('wp', array($this, 'redirect_uncanonical'), 11);
			
		}
		
		// ajax
		add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));
		
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
		
		
	}
	
// 	/**
// 	 * Public hook when plugin loads. Sublanguage API
// 	 *
// 	 * Hook for 'plugins_loaded'
// 	 *
// 	 * @from 1.0
// 	 */	
// 	public function load() {
// 		
// 		do_action('sublanguage_load', $this);
// 	
// 	}



	/**
	 * Start admin session (if admin functions needed on frontend). Sublanguage API
	 *
	 * Hook for 'sublanguage_load_admin'
	 *
	 * @from 1.0
	 */	
	public function load_admin() {
		global $sublanguage_admin;
		
		if (empty($sublanguage_admin)) {
		
			include( plugin_dir_path( __FILE__ ) . 'admin.php');
	
			$sublanguage_admin = new Sublanguage_admin();
			$sublanguage_admin->current_language = $this->current_language;
		
		}
	
	}


		
	/**
	 * Custom translation. Sublanguage API
	 *
	 * Filter for 'sublanguage_custom_translate'
	 *
	 * @from 1.0
	 */	
	public function custom_translate($content, $callback, $args = null) {
		
		return call_user_func($callback, $content, $this->current_language, $this, $args); 
	
	}
	

	/**
	 * Figure out the language. To be called as soon as possible.
	 * Filter for 'plugins_loaded'
	 *
	 * @from 1.0
	 */
	public function detect_language() {
		
		$lng_slugs = $this->get_language_column('post_name');
		
		if (isset($_REQUEST[$this->language_query_var]) && in_array($_REQUEST[$this->language_query_var], $lng_slugs)) {
		
			$this->current_language = $this->get_language_by($_REQUEST[$this->language_query_var], 'post_name');
		
		} else if (get_option('permalink_structure')) {
			
			$original_request = $_SERVER['REQUEST_URI'];
			
			preg_match('/\/('.implode('|', $lng_slugs).')(\/|$|\?|#)/', $original_request, $matches);
			
			if ($matches) { // -> language detected!
		
				$this->current_language = $this->get_language_by($matches[1], 'post_name');

				if ($this->current_language->ID == $this->options['default'] && !$this->options['show_slug']) {
					
					$this->canonical = false;
				
				}
			
			} else {
		
				$this->current_language = $this->get_language_by($this->options['default'], 'ID');
			
				if ($this->options['show_slug']) {
					
					$this->canonical = false;
				
				} 
				
				if ($this->options['autodetect']) { // auto detect language on home page
				
					// detect only on home page? --> rtrim($_SERVER['SCRIPT_URI'], '/') == rtrim(home_url())
					
					$detected_language = $this->auto_detect_language();
					
					if ($detected_language) {
					
						$this->current_language = $detected_language;
						
					}
		
				}
				
			}
		
		} else {

			$this->current_language = $this->get_language_by($this->options['default'], 'ID');
			
		}
		
		add_filter('locale', array($this, 'get_locale'));
		
		/**
		 * Filter current language
		 *
		 * @from 1.0
		 *
		 * @param WP_post object $this->current_language
		 * @param array of WP_post objects $this->languages
		 */		
		$this->current_language = apply_filters('sublanguage_current_language', $this->current_language);
		
		// rtl
		if (get_post_meta($this->current_language->ID, 'rtl', true)) {
			
			$GLOBALS['text_direction'] = 'rtl';
			
		}
		
	}

	/**
	 * Filter for 'locale'
	 *
	 * @from 1.0
	 */
	public function get_locale() {
		
		return $this->current_language->post_content;
		
	}	
	
	
	/** // -> to be moved in read-post
	 *	Change SQL query for search
	 *	Filter for 'posts_search'
	 *
	 * @from 1.0
	 */
	public function posts_search($where, $wp_query) {
		global $wpdb;
		
		if (isset($wp_query->query_vars['s']) && $wp_query->query_vars['s'] && $this->current_language->ID != $this->options['main']) {
			
			$where = str_replace($wpdb->posts, 'translation', $where);
			
			add_filter('posts_join', array($this, 'post_join_for_search'), null, 2);
			
		}
		
		return $where;
	}
	

	
	/** // -> to be moved in read-post
	 *	Append SQL join for search
	 *	Filter for 'posts_join'
	 *
	 * @from 1.0
	 */
	public function post_join_for_search($join, $wp_query) {
		global $wpdb;
		
		if (isset($wp_query->query_vars['s']) && $wp_query->query_vars['s'] && $this->current_language->ID != $this->options['main']) {
			
			$join .= $wpdb->prepare(" 
				INNER JOIN $wpdb->posts AS translation ON (translation.post_parent = $wpdb->posts.id AND translation.post_type = %s) ",
				$this->post_translation_prefix.$this->current_language->ID
			);
		
		}
		
		return $join;
	}
	
 	
	
	
	/**
	 *	Translate menu nav items
	 *	Filter for 'wp_setup_nav_menu_item'
	 */
	public function translate_menu_nav_item($menu_item) {
		
		if ($menu_item->type == 'post_type') {
			
			if (in_array($menu_item->object, $this->options['cpt'])) {
				
				$original_post = get_post($menu_item->object_id);
				
				if (!$menu_item->post_title) {
				
					$menu_item->title = $this->translate_post_field($original_post->ID, $this->current_language->ID, 'post_title', $menu_item->title);
					
				}
				
				$menu_item->url = get_permalink($original_post); 
				
			}
			
		} else if ($menu_item->type == 'taxonomy') {
			
			if (in_array($menu_item->object, $this->options['taxonomy'])) {
				
				$original_term = get_term($menu_item->object_id, $menu_item->object);
				
				if (!$menu_item->post_title) {
					
					$menu_item->title = $this->translate_term_field($original_term, $original_term->taxonomy, $this->current_language->ID, 'name', $menu_item->title);
					
				}
				
				// url already filtered
				
			}
		
		} else if ($menu_item->type == 'custom') {
			
			if ($menu_item->title == 'language') {
				
				$languages = $this->get_languages();
				
				if ($this->menu_language_index < count($languages)) {
					
					$language = $languages[$this->menu_language_index];
					
					/**
					 * Filter language name
					 *
					 * @from 1.2
					 *
					 * @param WP_post object
					 */	
					$menu_item->title = apply_filters('sublanguage_language_name', $language->post_title, $language);
					$menu_item->url = $this->get_translation_link($language);
					$menu_item->classes[] = ($language->ID == $this->current_language->ID) ? 'active_language' : 'inactive_language';
					
					$this->menu_language_index++;
			
				}
			
			} 
			
		}
		
		
		return $menu_item;
		
	}
	
	/**
	 * Print language switch
	 *
	 * @from 1.0
	 */
	public function print_language_switch() {
		
		$languages = $this->get_languages();
		
		if (has_action('sublanguage_custom_switch')) {
		
			/**
			 * Customize language switch output
			 *
			 * @from 1.2
			 *
			 * @param array of WP_Post language custom post
			 * @param Sublanguage_site $this The Sublanguage instance.
			 */
			do_action('sublanguage_custom_switch', $languages, $this);		
		
		} else if (has_filter('sublanguage_language_switch') || has_action('sublanguage_custom_language_switch')) {
			
			$language_objs = array();
			
			foreach ($languages as $language) {
				
				$language_obj = new Sublanguage_language();
				
				$language_obj->current = ($this->current_language->ID == $language->ID);
				$language_obj->slug = $language->post_name;
				$language_obj->name = $language->post_title;
				$language_obj->url = $this->get_translation_link($language);
				
				$language_objs[] = $language_obj;
				
			}	
		
			/**
			 * DEPRECATED. Filter language switch html
			 *
			 * @from 1.0
			 *
			 * @param array of Sublanguage_language object
			 * @param Sublanguage_language object
			 */	
			echo apply_filters('sublanguage_language_switch', '', $language_objs);
			
			/**
			 * DEPRECATED. Print language switch
			 *
			 * @from 1.1
			 *
			 * @param array of Sublanguage_language object
			 */	
			do_action('sublanguage_custom_language_switch', $language_objs);
			
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
					($this->current_language->ID == $language->ID ? ' current' : ''),
					$this->get_translation_link($language),
					apply_filters('sublanguage_language_name', $language->post_title, $language)
				);

			}
		
			$output .= '</ul>';
			
			echo $output;
		
		}
		
	}
	
	

	/**
	 * Intercept query_vars to find out type of query and get parent.
	 * Must be fired before filters are set
	 * Must return an array of query vars
	 *
	 * Hook for 'request'
	 *
	 * @from 1.0
	 */
	public function catch_translation($query_vars) {

		if (isset($query_vars['nodepage'])) { // -> node page
			
			$post_type = $query_vars['nodepage_type'];
			$post_type_qv = $post_type == 'page' ? 'pagename' : 'name';
			$post_name = $query_vars['nodepage'];
			
			$post = $this->query_post($query_vars['nodepage'], array($post_type));

			if ($post) {
				
				if ($post->post_parent != $query_vars['nodepage_parent']) { // -> path does not match
					
					$this->canonical = false;
					
				} 
				 
				if ($post_type == 'page') {
					
					$query_vars[$post_type_qv] = $this->canonical ? $query_vars['nodepage_path'].'/'.$post->post_name : get_page_uri($post->ID);
					
				} else {
					
					$query_vars[$post_type_qv] = $post->post_name;
					$query_vars['post_type'] = $post_type;
					$query_vars['name'] = $post->post_name; // ?
					
				}
				
				$this->enqueue_translation($post->ID);
				
			} else { // -> post not found
				
				$query_vars[$post_type_qv] = $query_vars['nodepage'];
			
			}
				
			unset($query_vars['nodepage']);
			unset($query_vars['nodepage_path']);
			unset($query_vars['nodepage_parent']);
			unset($query_vars['nodepage_type']);
						
		} else if (isset($query_vars['sub_cpt_t'])) { // -> translated custom-post-type
			
			$custom_type = $query_vars['sub_cpt_o'];
			$custom_type_qv = $query_vars['sub_cpt_qv'];
			$translated_cpt = $query_vars['sub_cpt_t'];
			
			if (isset($query_vars['sub_cpt_name'])) { // -> single cpt (not an archive)
								
				$post = $this->query_post($query_vars['sub_cpt_name'], array($custom_type));
			
				if ($post) {
					
					$query_vars[$custom_type_qv] = $post->post_name; // restore classical custom post query vars
					$query_vars['post_type'] = $custom_type;
					$query_vars['name'] = $post->post_name; 
					
					$this->enqueue_translation($post->ID);
					
				} else {
					
					$query_vars[$custom_type_qv] = $query_vars['sub_cpt_name'];
					$query_vars['post_type'] = $custom_type;
					$query_vars['name'] = $query_vars['sub_cpt_name'];
				
				}
				
			} else { // -> archive cpt
			
				$query_vars['post_type'] = $custom_type;
				
			}
			
			if ($this->get_cpt_translation($query_vars['sub_cpt_o'], $this->current_language->ID) != $query_vars['sub_cpt_t']) { // -> wrong language
				
				$this->canonical = false;
				
			}
			
			unset($query_vars['sub_cpt_name']);
			unset($query_vars['sub_cpt_t']);
			unset($query_vars['sub_cpt_o']);
			unset($query_vars['sub_cpt_qv']);
			
		} else if (isset($query_vars['post_type'])) { // -> untranslated custom-post-type
		
			$custom_type = $query_vars['post_type'];
			
			if (in_array($custom_type, $this->options['cpt'])) {
				
				if (isset($query_vars[$custom_type])) { // -> single cpt (not an archive)
					
					$post = $this->query_post($query_vars[$custom_type], array($custom_type));
			
					if ($post) {

						$query_vars[$custom_type] = $post->post_name;
						$query_vars['name'] = $post->post_name; 
						
						$this->enqueue_translation($post->ID);
	
					} 
					
				} 
				
				if ($this->current_language->ID != $this->options['main'] 
					&& $this->get_cpt_translation($custom_type, $this->current_language->ID)) { // -> there is a custom cpt translation for this
					
					$this->canonical = false;
				
				}
				
			}
			
		} else if (isset($query_vars['pagename']) || isset($query_vars['name'])) { // -> untranslated page or post
			
			$post_name = isset($query_vars['pagename']) ? $query_vars['pagename'] : $query_vars['name'];
			
			$post = $this->query_post($post_name, array('post', 'page'));
			
			if ($post) {
			
				if ($post->post_type == 'page') {
					
					$query_vars['pagename'] = $post->post_name;
					unset($query_vars['name']);
					
				} else {
				
					$query_vars['name'] = $post->post_name;
					
				}
				
				$this->enqueue_translation($post->ID);
				
			} 
			
		} else if (isset($query_vars['nodeterm'])) { // -> node taxonomy
			
			$tax_qv = $query_vars['nodeterm_qv'];
			$taxonomy = $query_vars['nodeterm_tax'];
			$term_parent = $query_vars['nodeterm_parent'];
			$term_name = $query_vars['nodeterm'];
			
			$term = $this->query_taxonomy($term_name, $taxonomy);
			
			if ($term) {
			
				$query_vars[$tax_qv] = $query_vars['nodeterm_path'].'/'.$term->slug;
				
				if ($term->parent != $term_parent) { // -> wrong path
				
					$this->canonical = false;
				
				}
				
				$ttax = $this->translate_taxonomy($taxonomy, $this->current_language->ID, $taxonomy);
				
				if ($ttax != $query_vars['nodeterm_ttax']) {
				
					$this->canonical = false;
					
				}
				
				$this->enqueue_terms($term->term_id);
				
			} else {
				
				$query_vars[$tax_qv] = $term_name;
			
			}
			
			unset($query_vars['nodeterm_qv']);
			unset($query_vars['nodeterm_tax']);
			unset($query_vars['nodeterm_parent']);
			unset($query_vars['nodeterm_ttax']);
			unset($query_vars['nodeterm_path']);
			unset($query_vars['nodeterm']);
			
		} else if (isset($query_vars['sub_tax_term'])) { // -> translated taxonomy
			
			$tax_qv = $query_vars['sub_tax_qv'];
 			$taxonomy = $query_vars['sub_tax_o'];
 			$term_name = $query_vars['sub_tax_term'];
			
			$term = $this->query_taxonomy($term_name, $taxonomy);
			
			if ($term) {
			
				$query_vars[$tax_qv] = $term->slug;
				
				if ($this->get_taxonomy_translation($term->taxonomy, $this->current_language->ID) != $query_vars['sub_tax_t']) { // wrong taxonomy translation
						
					$this->canonical = false;
			
				}
				
				$this->enqueue_terms($term->term_id);
				
			} else {
				
				$query_vars[$tax_qv] = $term_name;
			
			}
			
			unset($query_vars['sub_tax_qv']);
			unset($query_vars['sub_tax_o']);
			unset($query_vars['sub_tax_t']);
			unset($query_vars['sub_tax_term']);
			
		} else if ($results = array_intersect(array_keys($query_vars), array_map(array($this, 'taxonomy_to_query_var'), $this->options['taxonomy']))) { // -> untranslated taxonomy
			
			$tax_qv = '';
			
			foreach ($results as $r) {
				
				$tax_qv = $r;
				break;
			
			}
			
			if ($tax_qv == '') throw new Exception('Taxonomy query var not found!');
			
			$taxonomy = $this->query_var_to_taxonomy($tax_qv);
			$term_name = $query_vars[$tax_qv];
			
			$term = $this->query_taxonomy($term_name, $taxonomy);
			
			if ($term) {
			
				$query_vars[$tax_qv] = $term->slug;
				
				if ($this->get_taxonomy_translation($taxonomy, $this->current_language->ID)) { // taxonomy should be translated
						
					$this->canonical = false;
			
				}
				
				$this->enqueue_terms($term->term_id);
				
			} else {
				
				$query_vars[$tax_qv] = $term_name;
			
			}
			
		}
		
		if (isset($query_vars['preview'])) {
			
			$this->canonical = true;
			
		}
		
		add_filter('pre_post_link', array($this, 'pre_translate_permalink'), 10, 3);
		add_filter('post_link', array($this, 'translate_permalink'), 10, 3);
		add_filter('page_link', array($this, 'translate_page_link'), 10, 3);
		add_filter('post_type_link', array($this, 'translate_custom_post_link'), 10, 3);
		add_filter('post_link_category', array($this, 'translate_post_link_category'), 10, 3); // not implemented yet
		add_filter('post_type_archive_link', array($this, 'translate_post_type_archive_link'), 10, 2);
		add_filter('term_link', array($this, 'translate_term_link'), 10, 3);
		add_filter('year_link', array($this,'translate_month_link'));
		add_filter('month_link', array($this,'translate_month_link'));
		add_filter('day_link', array($this,'translate_month_link'));
		
		return $query_vars;
		
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
	public function query_post($post_name, $post_types) {
		global $wpdb;
		
		if (!is_array($post_types)) {
			
			$post_types = array($post_types);
		
		}
		
		$types = implode("','", esc_sql($post_types));
		
		$translation_post_type = $this->post_translation_prefix.$this->current_language->ID;
		
		$post = $wpdb->get_row( $wpdb->prepare(
			"SELECT $wpdb->posts.* FROM $wpdb->posts
				WHERE $wpdb->posts.post_name = %s AND ($wpdb->posts.post_type = %s OR $wpdb->posts.post_type IN ('$types'))",					
			$post_name,
			$translation_post_type
		));
	
		if ($post) { // translation or original
			
			if ($post->post_type == $translation_post_type) { // -> this is a translation
				
				$original = $wpdb->get_row( $wpdb->prepare(
					"SELECT original.* FROM $wpdb->posts AS original
						WHERE original.ID = %d AND original.post_type IN ('$types')",	
					$post->post_parent
				));
				
				if ($original) {
					
					return $original;
			
				} else { // orphan OR different post-type
					
					return false;
				
				}
				
			} else { // -> this is the parent post
			
				$translation = $wpdb->get_row( $wpdb->prepare(
					"SELECT translation.* FROM $wpdb->posts AS translation
						WHERE translation.post_type = %s AND translation.post_parent = %d",					
					$translation_post_type,
					$post->ID
				));
				
				if ($translation && $translation->post_name != $post->post_name) { // -> there is a specific translation for this post
				
					$this->canonical = false;
			
				}
				
				return $post;
			
			}
			
		} else { // not found
			
			$translation_post_types = array();
			
			$languages = $this->get_languages();
			
			foreach ($languages as $lng) {
				
				if ($lng->post_status == 'publish') {
				
					$translation_post_types[] = esc_sql($this->post_translation_prefix.$lng->ID);
				
				}
				
			}
			
			$translation_types = implode("','", $translation_post_types);
			
			$translation = $wpdb->get_row( $wpdb->prepare(
				"SELECT translation.* FROM $wpdb->posts AS translation
					WHERE translation.post_name = %s AND translation.post_type IN ('$translation_types')",					
				$post_name
			));
			
			if ($translation) { // -> this is different language
				
				$original = $wpdb->get_row( $wpdb->prepare(
					"SELECT original.* FROM $wpdb->posts AS original
						WHERE original.ID = %d AND original.post_type IN ('$types')",	
					$translation->post_parent
				));
				
				if ($original) {
				
					$this->canonical = false;
					
					return $original;
					
				}
			
			}
			
			return false;
			
		}
		
	}


	
	/**
	 *	Find original term based on query vars info.
	 *  
	 *  @from 1.0
	 *
	 * @param string $slug
	 * @param string|array $post_types
	 */
	public function query_taxonomy($slug, $taxonomy) {
		global $wpdb;
		
		$terms = $wpdb->get_results($wpdb->prepare(
			"SELECT t.term_id, t.slug, tt.taxonomy, tt.term_taxonomy_id, tt.parent FROM $wpdb->terms AS t 
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
			WHERE t.slug = %s AND tt.taxonomy IN (%s, %s)",		
			$slug,
			$taxonomy,
			$this->term_translation_prefix.$this->current_language->ID
		));
		
		if ($terms) { // term found
			
			$translation_parents = array();
			
			foreach ($terms as $term) {
				
				if ($term->taxonomy == $taxonomy) {
					
					$original = $term;
				
				} else {
			
					$translation_parents[] = $term->parent;

				}

			}
			
			if ($translation_parents) { // translation term
				
				$translation_parents = array_map('intval', $translation_parents);
				
				// need find parent:
				$term = $wpdb->get_row($wpdb->prepare(
					"SELECT t.term_id, t.slug, tt.taxonomy, tt.parent FROM $wpdb->terms AS t 
					INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id 
					WHERE tt.taxonomy = %s AND tt.term_taxonomy_id ".(count($translation_parents) === 1 ? '= '.$translation_parents[0] : 'IN ('.implode(',',$translation_parents).')'),
					$taxonomy
				));
			
				if (isset($term)) { // -> parent term found
				
					return $term;
				
				} 
		
			} else if (isset($original)) { // original term
			
				// verify there is no child
				$subterm = $wpdb->get_row($wpdb->prepare(
					"SELECT subt.slug FROM $wpdb->terms AS subt 
					INNER JOIN $wpdb->term_taxonomy AS subtt ON subt.term_id = subtt.term_id 
					WHERE subtt.parent = %d AND subtt.taxonomy = %s",		
					$original->term_taxonomy_id,
					$this->term_translation_prefix.$this->current_language->ID
				));
				
				if (isset($subterm)) { // -> there is an ad-hoc translation for this language
				
					$this->canonical = false;
					
				} 
				
				return $original;
			
			}
			
		} else { // -> term not found
			
			$languages = $this->get_languages();
			
			$taxonomies = array();

			foreach ($languages as $lng) {

				if ($lng->post_status == 'publish') {
					
					$taxonomies[] = $this->term_translation_prefix.$lng->ID;
				
				}

			}
			
			// find in other languages
			$term = $wpdb->get_row($wpdb->prepare(
				"SELECT t.term_id, t.slug, tt.taxonomy, tt.parent FROM $wpdb->terms AS t 
				INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
				INNER JOIN $wpdb->term_taxonomy AS subtt ON tt.term_taxonomy_id = subtt.parent
				INNER JOIN $wpdb->terms AS subt ON subt.term_id = subtt.term_id
				WHERE subt.slug = %s AND tt.taxonomy = %s AND subtt.taxonomy IN ('".implode("','",$taxonomies)."')",		
				$slug,
				$taxonomy
			));
			
			if (isset($term)) { // -> different language
				
				$this->canonical = false;
				
				return $term;
				
			} 
			
		}
		
		return false;
	
	}

	/**
	 * Enqueue javascript file
	 *
	 * Hook for 'wp_enqueue_scripts'
	 *
	 * @from 1.1
	 */
	public function enqueue_script() {

		wp_register_script('sublanguage', plugins_url('/js/site.js', __FILE__), array('jquery'), false, true );

		wp_localize_script('sublanguage', 'sublanguage', array(
			'current' => $this->current_language->post_name,
			'qv' => $this->language_query_var
		));

		wp_enqueue_script('sublanguage');
		
	}
	
	/**
	 * Add language slug in login url
	 *
	 * Filter for 'login_url', 'logout_url', 'lostpassword_url', 'register_url'
	 *
	 * @from 1.2
	 */
	public function translate_login_url($login_url){
		
		if ($this->current_language->ID != $this->options['main']) {
		
			$login_url = add_query_arg(array($this->language_query_var => $this->current_language->post_name), $login_url);
			
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
	
		echo '<input type="hidden" name="'.$this->language_query_var.'" value="'.$this->current_language->post_name.'"/>';

	}
	
	
	/**
	 * Translate link in retrieve password message
	 *
	 * Filter for 'retrieve_password_message'
	 *
	 * @from 1.2
	 */
	public function translate_retrieve_password_message($message) {
	
		return  preg_replace('/(wp-login\.php[^>]*)/', '$1'.'&'.$this->language_query_var.'='.$this->current_language->post_name, $message);
		
	}
	
	/**
	 * lostpassword redirect
	 *
	 * Filter for 'lostpassword_redirect'
	 *
	 * @from 1.2
	 */
	public function lostpassword_redirect($redirect_to) {
		
		return 'wp-login.php?checkemail=confirm'.'&'.$this->language_query_var.'='.$this->current_language->post_name;
	
	}
	
	
	/**
	 * Detect language
	 *
	 * @from 1.2
	 */
	public function auto_detect_language() {
		
		if (class_exists('Locale')) {
			
			$locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']); 
			
			return $this->get_language_by($locale, 'post_content');
		
		}
		
		return false;
		
	}
	
	
	/**
	 * Get language link
	 *
	 * @from 1.2
	 */
	public function get_translation_link($language) {
		global $wp_query;
		
		$current_language = $this->current_language; // save current_language value
		$query_object = get_queried_object();
		
		$this->current_language = $language; // -> pretend this is the current language
		
		$link = '';
		
		if (is_category() || is_tag() || is_tax()) {
						
			$original_term = get_term($query_object->term_id, $query_object->taxonomy);
			
			$link = get_term_link($original_term, $language->ID);
			
		} else if (is_post_type_archive()) {
			
			$link = get_post_type_archive_link($query_object->name);
				
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
			
		} else { // is_home, is_404
		
			$link = home_url('/');
			
		}
		
		$this->current_language = $current_language; // restore current_language after messing with it		
		
		return $link;
	}
		
	/**
	 * Set current language to be the first
	 *
	 * @from 1.2
	 */
	public function set_current_language_first() {
		
		usort($this->languages_cache, array($this, 'sort_language_by_current'));
	
	}
	
	/**
	 * Callback for sorting language
	 *
	 * @from 1.2
	 */
	public function sort_language_by_current($a, $b) {
		
		if ($a->ID == $this->current_language->ID) return -1;
		if ($b->ID == $this->current_language->ID) return 1;
		return 0;
	
	}
	
	/** 
	 * Utils functions 
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
	 * Utils functions 
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

	
}




	
class Sublanguage_language {
	
	var $slug = '';
	var $name = '';
	var $locale = '';
	var $url = '';
	var $current = false;
	var $default = false;
	
}






