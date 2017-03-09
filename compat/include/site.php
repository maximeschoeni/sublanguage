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
		
		$this->detect_language();
		
		add_action( 'plugins_loaded', array($this, 'load'));
		
	}
	
	/**
	 * @from 1.4.7
	 * @from 1.5.3 post_content, post_title and post_excerpt are hard-translated
	 */
	public function load() {
		
		if ($this->current_language) {
			
			if ($this->get_option('current_first')) {
			
				$this->set_current_language_first();
		
			}
			
			parent::load();
			
			add_filter('the_posts', array($this, 'hard_translate_posts'), 20, 2);
			
			add_filter('the_content', array($this, 'translate_post_content'), 5);
			add_filter('the_title', array($this, 'translate_post_title'), 10, 2);
			add_filter('get_the_excerpt', array($this, 'translate_post_excerpt'), 9);
			add_filter('single_post_title', array($this, 'translate_single_post_title'), 10, 2);
			
			add_filter('get_post_metadata', array($this, 'translate_meta_data'), 10, 4);
			add_filter('wp_setup_nav_menu_item', array($this, 'translate_menu_nav_item'));
			add_filter('wp_nav_menu_objects', array($this, 'filter_nav_menu_objects'), 10, 2); // -> @from 1.5 Filter list for hidden items 
			add_filter('wp_nav_menu', array($this, 'reset_menu_index'));// -> @from 1.5.2 reset menu index after printing menu.
			
			add_filter('tag_cloud_sort', array($this,'translate_tag_cloud'), 10, 2);
			add_action('parse_query', array($this, 'allow_filters')); // allow filters on menu get_posts
			add_action('sublanguage_prepare_ajax', array($this, 'ajax_enqueue_scripts'));
			
			add_action('init', array($this, 'init'));
			
			$this->add_options_filters();
			
		}
		
	}

	/**
	 * @from 1.0
	 */	
	public function init() {
		
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
			
			// added in 1.5
			add_action('wp', array($this, 'filter_permastructs'), 9);
						
 			add_action('wp', array($this, 'redirect_uncanonical'), 11);
			
		}
		
		// link filters
		add_filter('home_url', array($this,'translate_home_url'), 10, 4);
		add_filter('pre_post_link', array($this, 'pre_translate_permalink'), 10, 3);
		add_filter('post_link', array($this, 'translate_permalink'), 10, 3);
		add_filter('page_link', array($this, 'translate_page_link'), 10, 3);
		add_filter('post_type_link', array($this, 'translate_custom_post_link'), 10, 3);
		add_filter('attachment_link', array($this, 'translate_attachment_link'), 10, 2);
		add_filter('post_link_category', array($this, 'translate_post_link_category'), 10, 3); // not implemented yet
		add_filter('post_type_archive_link', array($this, 'translate_post_type_archive_link'), 10, 2);
		//add_filter('term_link', array($this, 'translate_term_link'), 10, 3);
		add_filter('year_link', array($this,'translate_month_link'));
		add_filter('month_link', array($this,'translate_month_link'));
		add_filter('day_link', array($this,'translate_month_link'));
		
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
	 * Custom translation. Sublanguage API
	 *
	 * Filter for 'sublanguage_custom_translate'
	 *
	 * @from 1.0
	 */	
	public function custom_translate($content, $callback, $args = null) {
		
		if ($this->current_language) {
			
			return call_user_func($callback, $content, $this->current_language, $this, $args); 
		
		}
		
		return $content;
	}
	
	/**
	 * Figure out the language. To be called as soon as possible.
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

				if ($this->is_default() && !$this->get_option('show_slug')) {
					
					$this->canonical = false;
				
				}
			
			} else {
		
				$this->current_language = $this->get_default_language();
				
				if ($this->get_option('show_slug')) {
					
					$this->canonical = false;
				
				} 
				
				if ($this->get_option('autodetect')) { // auto detect language on home page
				
					// detect only on home page? --> rtrim($_SERVER['SCRIPT_URI'], '/') == rtrim(home_url())
					
					$detected_language = $this->auto_detect_language();
					
					if ($detected_language) {
					
						$this->current_language = $detected_language;
						
						// @from 1.5.2
						if (!$this->is_default()) {
							
							$this->canonical = false;
							
						}
						
					}
					
				}
				
			}
		
		} else {

			$this->current_language = $this->get_default_language();
			
		}
		
		add_filter('locale', array($this, 'get_locale'));
		
		/**
		 * DEPRECATED. Use 'sublanguage_init' instead.
		 * Filter current language
		 *
		 * @from 1.0
		 *
		 * @param WP_post object $this->current_language
		 * @param array of WP_post objects $this->languages
		 */		
		$this->current_language = apply_filters('sublanguage_current_language', $this->current_language);
		
		// rtl
		if ($this->current_language && get_post_meta($this->current_language->ID, 'rtl', true)) {
			
			$GLOBALS['text_direction'] = 'rtl';
			
		}
		
	}

	/**
	 * Filter for 'locale'
	 *
	 * @from 1.0
	 */
	public function get_locale($locale) {
		
		if ($this->current_language) {
		
			return $this->current_language->post_content;
		
		}
		
		return $locale;
	}	
	
	/**
	 *	Translate menu nav items
	 *	Filter for 'wp_setup_nav_menu_item'
	 */
	public function translate_menu_nav_item($menu_item) {
		
		if ($menu_item->type == 'post_type') {
			
			if (in_array($menu_item->object, $this->get_post_types())) {
				
				$original_post = get_post($menu_item->object_id);
				
				$menu_item = $this->translate_nav_menu_item($menu_item);
				
				if (empty($menu_item->post_title)) {
				
					$menu_item->title = $this->translate_post_field($original_post->ID, $this->current_language->ID, 'post_title', $menu_item->title);
					
				} else {
					
					$menu_item->title = $menu_item->post_title;
				
				}
				
				$menu_item->url = get_permalink($original_post); 
				
			}
			
		} else if ($menu_item->type == 'taxonomy') {
			
			if (in_array($menu_item->object, $this->get_taxonomies())) {
				
				$original_term = get_term($menu_item->object_id, $menu_item->object);
				
				$menu_item = $this->translate_nav_menu_item($menu_item);
				
				if (empty($menu_item->post_title)) {
					
					$menu_item->title = $this->translate_term_field($original_term, $original_term->taxonomy, $this->current_language->ID, 'name', $menu_item->title);
					
				} else {
					
					$menu_item->title = $menu_item->post_title;
				
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
					$menu_item->classes[] = 'sublanguage';
					$menu_item->classes[] = $this->current_language->post_name;
					
					$this->menu_language_index++;
			
				}
			
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
	
		if ($this->is_sub() && in_array('nav_menu_item', $this->get_post_types())) {
		
			$menu_item->post_title = $this->translate_post_field($menu_item->ID, $this->current_language->ID, 'post_title', ($fill_default_title ? $menu_item->title : ''));
			$menu_item->description = $this->translate_post_field($menu_item->ID, $this->current_language->ID, 'post_content', $menu_item->description);
			$menu_item->attr_title = $this->translate_post_field($menu_item->ID, $this->current_language->ID, 'post_excerpt', $menu_item->attr_title);
			
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
		
		if (in_array('sublanguage_hide', $this->get_postmeta_keys()) && in_array('nav_menu_item', $this->get_post_types())) {
			
			$filtered_items = array();
			
			foreach ($sorted_menu_items as $menu_item) {
				
				if (!get_post_meta($menu_item->ID, 'sublanguage_hide', true)) {
					
					$filtered_items[] = $menu_item;
				
				}
				
			}
			
			return $filtered_items;
		}
		
		return $sorted_menu_items;
	}

	/**
	 * Reset menu_language_index after printing menu
	 *
	 * Filter for 'wp_nav_menu'
	 *
	 * @from 1.5.2
	 */	
	public function reset_menu_index($nav_menu) {
		
		$this->menu_language_index = 0;
		
		return $nav_menu;
	}	
	
	
	/**
	 * Print language switch
	 *
	 * hook for 'sublanguage_print_language_switch'
	 *
	 * @from 1.0
	 */
	public function print_language_switch($context = null) {
		
		$languages = $this->get_languages();
		
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
			$post_types = array($post_type);
			
			if (in_array('attachment', $this->get_post_types())) {
				
				array_push($post_types, 'attachment');
				
			}
			
			$post = $this->query_post($query_vars['nodepage'], $post_types);
			
			if ($post) {
				
				if ($post->post_type != 'attachment' && $post->post_parent != $query_vars['nodepage_parent']) { // -> path does not match
					
					$this->canonical = false;
					
				} 
				
				if ($post->post_type == 'attachment') {
					
					$query_vars['attachment'] = $post->post_name;
					$query_vars['post_type'] = $post->post_type;
					$query_vars['name'] = $post->post_name; // ?
					
				} else if ($post->post_type == 'page') {
					
					if ($this->canonical) {
						
						$query_vars[$post_type_qv] = $query_vars['nodepage_path'].'/'.$post->post_name;
						
					} else {
						
						$query_vars[$post_type_qv] = get_page_uri($post->ID);
					
					}
					
				} else {
					
					$query_vars[$post_type_qv] = $post->post_name;
					$query_vars['post_type'] = $post->post_type;
					$query_vars['name'] = $post->post_name; // ?
					
				}
				
				$this->enqueue_post_id($post->ID);
				
			} else { // -> post not found
				
				$query_vars[$post_type_qv] = $query_vars['nodepage'];
			
			}
				
			unset($query_vars['nodepage']);
			unset($query_vars['nodepage_path']);
			unset($query_vars['nodepage_parent']);
			unset($query_vars['nodepage_type']);
			
		} else if (isset($query_vars['attachment']) && in_array('attachment', $this->get_post_types())) { // -> attachment (this is a child of a "post" post-type)
			
			$post = $this->query_post($query_vars['attachment'], 'attachment');
			
			if ($post) {
			
				$query_vars['attachment'] = $post->post_name; 
				
			}
			
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
					
					$this->enqueue_post_id($post->ID);
					
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
			
			if (in_array($custom_type, $this->get_post_types())) {
				
				$post_type_data = get_post_type_object( $custom_type );
				$custom_type_qv = $post_type_data->query_var;
			
				if (isset($query_vars[$custom_type_qv])) { // -> single cpt (not an archive)
					
					$post = $this->query_post($query_vars[$custom_type_qv], array($custom_type));
			
					if ($post) {

						$query_vars[$custom_type_qv] = $post->post_name;
						$query_vars['name'] = $post->post_name; 
						
						$this->enqueue_post_id($post->ID);
	
					} 
					
				} 
				
				$custom_type_slug = $this->get_post_type_slug($custom_type);
				$cpt_translation = $this->translate_cpt($custom_type, $this->current_language->ID, $custom_type_slug);
				
				if ($cpt_translation !== $custom_type_slug) { // -> there is a custom cpt translation for this
					
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
			
				$this->enqueue_post_id($post->ID);
			
			} 
			
		} else if (isset($query_vars['nodeterm'])) { // -> node taxonomy
			
			$tax_qv = $query_vars['nodeterm_qv'];
			$taxonomy = $query_vars['nodeterm_tax'];
			$term_parent = $query_vars['nodeterm_parent'];
			$term_name = $query_vars['nodeterm'];
			
			// added in 1.4.9
			$taxonomy_obj = get_taxonomy($taxonomy);
			$taxonomy_qv = isset($taxonomy_obj->rewrite, $taxonomy_obj->rewrite['slug']) ? $taxonomy_obj->rewrite['slug'] : $taxonomy;
			
			
			$term = $this->query_taxonomy($term_name, $taxonomy);
			
			if ($term) {
			
				$query_vars[$tax_qv] = $query_vars['nodeterm_path'].'/'.$term->slug;
				
				if ($term->parent != $term_parent) { // -> wrong path
				
					$this->canonical = false;
				
				}
				
				$ttax = $this->translate_taxonomy($taxonomy, $this->current_language->ID, $taxonomy_qv);
				
				if ($ttax != $query_vars['nodeterm_ttax']) {
				
					$this->canonical = false;
					
				}
				
				$this->enqueue_term_id($term->term_id);
				
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
				
				$this->enqueue_term_id($term->term_id);
				
			} else {
				
				$query_vars[$tax_qv] = $term_name;
			
			}
			
			unset($query_vars['sub_tax_qv']);
			unset($query_vars['sub_tax_o']);
			unset($query_vars['sub_tax_t']);
			unset($query_vars['sub_tax_term']);
			
		} else if ($results = array_intersect(array_keys($query_vars), array_map(array($this, 'taxonomy_to_query_var'), $this->get_taxonomies()))) { // -> untranslated taxonomy
			
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
				
				$this->enqueue_term_id($term->term_id);
				
			} else {
				
				$query_vars[$tax_qv] = $term_name;
			
			}
			
		} else if (isset($query_vars['error']) && $query_vars['error'] == '404' && in_array('attachment', $this->get_post_types())) { // -> maybe an attachment translation (if it is a child of a subpage)
			
			// ./wp-include/class-wp.php, line 213
			// no attachment were found through get_page_by_path(), but there is no filter...
			
		}
		
		if (isset($query_vars['preview'])) {
			
			$this->canonical = true;
			
		}
		
		return $query_vars;
		
	}
	
	/**
	 * Redirection when not canoncal url
	 * Must be called before getting term links but after all taxonomies are registered
	 *
	 * Hook for 'wp'
	 *
	 * @from 1.5
	 */
	public function filter_permastructs() {
		
		$this->translate_taxonomies_permastructs($this->current_language->ID);
		
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
					WHERE tt.taxonomy = %s AND t.term_id ".(count($translation_parents) === 1 ? '= '.$translation_parents[0] : 'IN ('.implode(',',$translation_parents).')'),
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
					
					$taxonomies[] = $this->term_translation_prefix.intval($lng->ID);
				
				}

			}
			
			// find in other languages
			$term = $wpdb->get_row($wpdb->prepare(
				"SELECT t.term_id, t.slug, tt.taxonomy, tt.parent FROM $wpdb->terms AS t 
				INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
				INNER JOIN $wpdb->term_taxonomy AS subtt ON t.term_id = subtt.parent
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
	 * Add language slug in login url
	 *
	 * Filter for 'login_url', 'logout_url', 'lostpassword_url', 'register_url'
	 *
	 * @from 1.2
	 */
	public function translate_login_url($login_url){
		
		if (isset($this->current_language->post_name)) {
		
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
	 * registration redirect
	 *
	 * Filter for 'registration_redirect'
	 *
	 * @from 1.4.1
	 */
	public function registration_redirect($redirect_to) {
		
		return 'wp-login.php?checkemail=registered'.'&'.$this->language_query_var.'='.$this->current_language->post_name;
	
	}	
	
	/**
	 * Detect language
	 *
	 * @from 1.5.1 Better language detection
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
	 * @from 1.5.3 Change way of retrieving post_type for post archive link queries
	 */
	public function get_translation_link($language) {
		global $wp_query, $wp_rewrite;
		
		$current_language = $this->current_language; // save current_language value
		
		$query_object = get_queried_object();
		
		$this->current_language = $language; // -> pretend this is the current language
		
		$link = '';
		
		if (is_category() || is_tag() || is_tax()) {
						
			$original_term = get_term($query_object->term_id, $query_object->taxonomy);
			
			$this->translate_taxonomy_permastruct($query_object->taxonomy, $language->ID);
			
			$link = get_term_link($original_term, $original_term->taxonomy);
			
			$this->restore_permastruct($query_object->taxonomy);
			
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
	
	/**
	 * Override get_language to select only published language
	 *
	 * @from 1.2.2
	 *
	 * @return array of WP_post objects
	 */
	public function get_languages() {
		
		if (empty($this->languages_cache)) {
			
			$this->languages_cache = get_posts(array(
				'post_type' => $this->language_post_type,
				'post_status' => 'publish',
				'orderby' => 'menu_order' ,
				'order'   => 'ASC',
				'nopaging' => true,
				'update_post_term_cache' => false
			));
						
		}
    
		return $this->languages_cache;
    
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
		
			$output .= sprintf('<link rel="alternate" href="%s" hreflang="%s" />',
				$this->get_translation_link($language),
				$language->post_content ? strtolower(str_replace('_', '-', $language->post_content)) : 'en'
			);
			
		}
		
		echo $output;
		
	}
	
	/**
	 * Add filters for options translation
	 *
	 * @from 1.5
	 */
	public function add_options_filters() {
		
		$translations = get_option($this->translation_option_name);
		
		if (isset($translations['option'][$this->current_language->ID])) {
			
			foreach ($translations['option'][$this->current_language->ID] as $option => $val) {
				
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
		
		$translations = get_option($this->translation_option_name);
		
		if (isset($this->current_language->ID, $translations['option'][$this->current_language->ID][$option])) {

			$this->translate_option($value, $translations['option'][$this->current_language->ID][$option]);
		
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
	 * Get current language
	 *
	 * @from 1.5.2
	 *
	 * @return WP_post object
	 */
	public function get_language() {
		
    	return $this->current_language;
    
	}
	
	/**
	 * Set current language
	 *
	 * @from 1.5.2
	 *
	 * @param WP_post object $language
	 * @param bool $canonical
	 */
	public function set_language($language) {
		
    	$this->current_language = $language;
    
	}
	
	/**
	 * don't hard translate post_name on front-end
	 *
	 * @override Sublanguage::hard_translate_post()
	 *
	 * Hook for 'the_post' (triggered on setup_postdata)
	 *
	 * @from 1.5.3
	 * @from 1.5.4 add property 'translated' to translated posts
	 */	
	public function hard_translate_post($post) {
		
		if ($this->is_sub() && in_array($post->post_type, $this->get_post_types())) {
			
			foreach ($this->fields as $field) {
				
				if ($field !== 'post_name') {
				
					$post->$field = $this->translate_post_field($post->ID, $this->current_language->ID, $field, $post->$field);
				
				}
				
			}
			
			$post->translated = true;
		}
		
		return $post;
	}	
	
	
}

/**
 * DEPRECATED
 */	
class Sublanguage_language {
	
	var $slug = '';
	var $name = '';
	var $locale = '';
	var $url = '';
	var $current = false;
	var $default = false;
	
}
