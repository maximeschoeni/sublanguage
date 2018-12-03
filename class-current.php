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
	 * @var array
	 *
	 * Gutenberg markup
	 *
	 * @from 2.5
	 */
	var $gutenberg_language_tag = '<!-- wp:sublanguage/language-manager {"current_language":"%s"} /-->';
	var $gutenberg_language_reg = '<!-- wp:sublanguage/language-manager \{"current_language":"([^"]+)"\} /-->';
	
	/**
	 * @var object WP_Post language
	 *
	 * Language to edit from Gutenberg
	 *
	 * @from 2.5
	 */
	var $edit_language;
	
	/**
	 * Register all filters needed for admin and front-end
	 *
	 * @from 1.4.7
	 */
	public function load() {
		
		add_action('init', array($this, 'register_languages')); // @from 2.5 moved from admin-ui
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
		
		// Gutenberg -> @from 2.5
		add_action('init', array($this, 'register_translation_meta'));
		add_action('registered_post_type', array($this, 'rest_register_post_type'));
		add_filter('rest_prepare_revision', array($this, 'rest_prepare_post'), 10, 3);
		
		// Revision -> @from 2.6
		add_action('save_post_revision', array($this, 'save_post_revision'), 10, 2);
		add_action('wp_restore_post_revision', array($this, 'restore_revision'), 10, 2);
		add_filter('_wp_post_revision_fields', array($this, 'revision_fields'), 10, 2);
		add_filter('wp_save_post_revision_post_has_changed', array($this, 'revision_post_has_changed'), 10, 3);
		
	}
	
	/**
	 * Register language post-type
	 *
	 * @hook 'init'
	 *
	 * @from 2.5
	 */
	public function register_languages() {
		
		register_post_type($this->language_post_type, array(
			'labels'             => array(
				'name'               => __( 'Languages', 'sublanguage' ),
				'singular_name'      => __( 'Language', 'sublanguage' ),
				'menu_name'          => __( 'Languages', 'sublanguage' ),
				'name_admin_bar'     => __( 'Languages', 'sublanguage' ),
				'add_new'            => __( 'Add language', 'sublanguage' ),
				'add_new_item'       => __( 'Add language', 'sublanguage' ),
				'new_item'           => __( 'New language', 'sublanguage' ),
				'edit_item'          => __( 'Edit language', 'sublanguage' ),
				'view_item'          => __( 'View language', 'sublanguage' ),
				'all_items'          => __( 'Languages', 'sublanguage' ),
				'search_items'       => __( 'Search languages', 'sublanguage' ),
				'parent_item_colon'  => __( 'Parent language:', 'sublanguage' ),
				'not_found'          => __( 'No language found.', 'sublanguage' ),
				'not_found_in_trash' => __( 'No language found in Trash.', 'sublanguage' )
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			//'show_in_menu'       => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'rewrite'						 => false,
			'capabilities' => array(
				'edit_post' => 'edit_language',
				'edit_posts' => 'edit_languages',
				'edit_others_posts' => 'edit_other_languages',
				'publish_posts' => 'publish_languages',
				'read_post' => 'read_language',
				'read_private_posts' => 'read_private_languages',
				'delete_post' => 'delete_language'
			),
			'map_meta_cap' => true,
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array('title', 'slug', 'page-attributes') ,
			'menu_icon'			 => 'dashicons-translation',
			'can_export'		 => false
		));
	
	}
	
	/**
	 * Register meta for REST
	 *
	 * @from 2.5
	 */
	public function register_translation_meta() {
		
		foreach ($this->get_languages() as $language) {
			
			// also need to register main language!
			
			foreach ($this->fields as $field) {
			
				register_meta('post', $this->get_prefix($language) . $field, array(
					'type' => 'string',
					'auth_callback' => '__return_true',
					'single' => true,
					'show_in_rest' => true
				));
			
			}
				
		}
		
		// @from 2.6 register edit_language meta
		register_meta('post', 'edit_language', array(
			'type' => 'string',
			'auth_callback' => '__return_true',
			'single' => true,
			'show_in_rest' => true
		));
		
	}
	
	/**
	 * Register post types for Parse meta for REST
	 *
	 * @filter 'registered_post_type'
	 * @from 2.5
	 * @from 2.6 get ride of sublanguage manager template
	 */
	public function rest_register_post_type($post_type) {
	
		if ($this->is_post_type_translatable($post_type)) {
			
			add_filter('rest_pre_insert_' . $post_type, array($this, 'rest_pre_insert_post'), 10, 2);
			add_filter('rest_prepare_' . $post_type, array($this, 'rest_prepare_post'), 10, 3);
			
		}
	
	}
	
	/**
	 * Parse meta for REST
	 *
	 * @filter "rest_pre_insert_{$this->post_type}"
	 * @from 2.5
	 * @from 2.6 $meta -> $this->meta, edit_language from meta not tag
	 */
	public function rest_pre_insert_post($prepared_post, $request) {
		
		$this->meta = $request->get_param('meta');
		
		if (!$this->meta) {
		
			$this->meta = array();
		
		}
		
		if (isset($this->meta['edit_language'])) {
			
			$this->edit_language = $this->find_language($this->meta['edit_language'], 'post_name');
			unset($this->meta['edit_language']);
			
		} 
		
		if (empty($this->edit_language)) {
			
			$this->edit_language = $this->get_language();
			
		}
		
		$this->current_language = $this->get_main_language(); // now data are parsed like its the main language
	
		if (isset($prepared_post->post_content)) $post_content = $prepared_post->post_content;
		if (isset($prepared_post->post_excerpt)) $post_excerpt = $prepared_post->post_excerpt;
		if (isset($prepared_post->post_title)) $post_title = $prepared_post->post_title;
		if (isset($prepared_post->post_name)) $post_name = $prepared_post->post_name;
	
		foreach ($this->get_languages() as $language) {
		
			$content_field = $this->get_prefix($language) . 'post_content';
			$excerpt_field = $this->get_prefix($language) . 'post_excerpt';
			$title_field = $this->get_prefix($language) . 'post_title';
			$name_field = $this->get_prefix($language) . 'post_name';
			
			// keep for compat
			if (preg_match('#'.$this->gutenberg_language_reg.'#', $content_field, $matches)) {
		
				$content_field = str_replace($matches[0], '', $content_field);
	
			}
			
			if ($this->is_sub($language)) {
			
				if ($language === $this->edit_language) {
				
					if (isset($post_content)) $this->meta[$content_field] = $post_content;
					if (isset($post_excerpt)) $this->meta[$excerpt_field] = $post_excerpt;
					if (isset($post_title)) $this->meta[$title_field] = $post_title;
					if (isset($post_name)) $this->meta[$name_field] = $post_name;
				
				}
			
			} else {
			
				if ($language !== $this->edit_language) {
				
					if (isset($this->meta[$content_field])) $prepared_post->post_content = $this->meta[$content_field];	
					else unset($prepared_post->post_content);
				
					if (isset($this->meta[$excerpt_field])) $prepared_post->post_excerpt = $this->meta[$excerpt_field];	
					else unset($prepared_post->post_excerpt);
				
					if (isset($this->meta[$title_field])) $prepared_post->post_title = $this->meta[$title_field];
					else unset($prepared_post->post_title);
				
					if (isset($this->meta[$name_field])) $prepared_post->post_name = $this->meta[$name_field];
					else unset($prepared_post->post_name);
				
				}
			
				// do not save main language in meta
				unset($this->meta[$content_field]);
				unset($this->meta[$excerpt_field]);
				unset($this->meta[$title_field]);
				unset($this->meta[$name_field]);
			
			}
		
		}
		
		$request->set_param('meta', $this->meta);
		
		return $prepared_post;
	}

	
	/**
	 * Filters the post data for a response (REST).
	 *
	 * @filter "rest_prepare_{$this->post_type}"
	 *
	 * @from 2.5
	 * @from 2.6 get ride of language manager tag/reg
	 */
	public function rest_prepare_post($response, $post, $request) {
		global $wpdb;
				
		$meta = $request->get_param('meta');
		
		$data = $response->get_data();
		
		$post_content = $post->post_content;
		$post_excerpt = $post->post_excerpt;
		$post_title = $post->post_title;
		$post_name = $post->post_name;		
		
		if (empty($this->edit_language)) {
			
			$this->edit_language = $this->get_language();
			
		}
		
		foreach ($this->get_languages() as $language) {
			
			$content_field = $this->get_prefix($language) . 'post_content';
			$excerpt_field = $this->get_prefix($language) . 'post_excerpt';
			$title_field = $this->get_prefix($language) . 'post_title';
			$name_field = $this->get_prefix($language) . 'post_name';
			
			if ($this->is_sub($language)) {
				
				if ($language === $this->edit_language) {
					
					$data['content']['raw'] = isset($meta[$content_field]) ? $meta[$content_field] : get_post_meta($post->ID, $content_field, true);
					$data['content']['rendered'] = post_password_required( $post ) ? '' : apply_filters( 'the_content', $data['content']['raw'] );
					
					$data['excerpt']['raw'] = isset($meta[$excerpt_field]) ? $meta[$excerpt_field] : get_post_meta($post->ID, $excerpt_field, true);
					$data['excerpt']['rendered'] = apply_filters( 'the_excerpt', $data['excerpt']['raw'] );
					
					$data['title']['raw'] = isset($meta[$title_field]) ? $meta[$title_field] : get_post_meta($post->ID, $title_field, true);
					$data['title']['rendered'] = apply_filters( 'the_title', $data['title']['raw'], $post->ID );
					
					$data['slug'] = isset($meta[$name_field]) ? $meta[$name_field] : get_post_meta($post->ID, $name_field, true);	
					
				}
				
			} else {
				
				if ($this->is_sub($this->edit_language)) { // -> should never be the case
					
					// get untranslated post
					$post = $wpdb->get_row($wpdb->prepare("SELECT ID, post_content, post_excerpt, post_title, post_name FROM $wpdb->posts WHERE ID = %d", $post->ID));
					
				}
				
				$data['meta'][$content_field] = $post->post_content;
				$data['meta'][$excerpt_field] = $post->post_excerpt;
				$data['meta'][$title_field] = $post->post_title;
				$data['meta'][$name_field] = $post->post_name;
				
			}
			
		}
		
		$data['meta']['edit_language'] = $this->edit_language->post_name;
		
		$response->set_data($data);
		
		return $response;
	}
	
	
	
	
	/**
	 * Save meta with post revision (REST only, classic editor ajax calls override it)
	 *
	 * @hook 'save_post_revision'
	 *
	 * @from 2.6
	 */
	public function save_post_revision($revision_id, $revision) {
		
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			
			// skip when doing autosave because data are not available.
			// -> just let autosave do weird stuffs
			return;
			
		}
		
		if (isset($this->meta)) {
			
			$post = get_post($revision->post_parent);
			$post_id = $post->ID;
			
			if ($this->get_post_type_option($post->post_type, 'enable_revisions')) {
			
				foreach ($this->get_languages() as $language) {
				
					$prefix = $this->get_prefix($language);
				
					foreach (array('post_title', 'post_content', 'post_excerpt') as $field) {
					
						if ($this->is_sub($language)) {
												
							if (isset($this->meta[$prefix.$field])) {
						
								if ($this->meta[$prefix.$field]) {
							
									update_metadata('post', $revision_id, $prefix.$field, $this->meta[$prefix.$field]);
						
								} else {
				
									delete_metadata('post', $revision_id, $prefix.$field);
								}
						
							} else {
						
								$meta_value = get_metadata('post', $post_id, $prefix.$field, true);
						
								if ($meta_value) {
							
									update_metadata('post', $revision_id, $prefix.$field, $meta_value);
							
								}
						
							}
						
						}
				
					}
				
				}
				
			}

		}
		
	}
	
	/**
	 * Restore meta data when restoring revision
	 *
	 * @hook 'wp_restore_post_revision'
	 *
	 * @from 2.6
	 */
	public function restore_revision( $post_id, $revision_id ) {

		foreach ($this->get_languages() as $language) {
			
			if ($this->is_sub($language)) {
				
				$prefix = $this->get_prefix($language);
				
				foreach (array('post_title', 'post_content', 'post_excerpt') as $field) {
					
					$value  = get_metadata('post', $revision_id, $prefix.$field, true );
					
					if ($value) {
						
						update_metadata('post', $post_id, $prefix.$field, $value);
						
					} else {
						
						delete_metadata('post', $post_id, $prefix.$field);
						
					}
					
				}
					
			}
			
		}

	}
	
	
	/**
	 * Add revision field
	 *
	 * @hook '_wp_post_revision_fields'
	 *
	 * @from 2.6
	 */
	public function revision_fields($fields, $post) {
		
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE && isset($this->meta) && empty($this->meta)) {
			
			// -> it means an autosave is done using rest. 
			// Rest autosave cannot work because meta data are not sent. Editing language is unknown.
			// -> revision will save potentially all wrong but that's life
			
			return $fields;
		
		}
		
		if ($this->get_post_type_option($post['post_type'], 'enable_revisions')) {
		
			foreach ($this->get_languages() as $language) {
			
				if ($this->is_sub($language)) {
			
					$prefix = $this->get_prefix($language);
					$fields[$prefix.'post_title'] = sprintf(__('Title (%s)', 'sublanguage'), $language->post_title);
					$fields[$prefix.'post_content'] = sprintf(__('Content (%s)', 'sublanguage'), $language->post_title);
					$fields[$prefix.'post_excerpt'] = sprintf(__('Excerpt (%s)', 'sublanguage'), $language->post_title);
			
					add_filter('_wp_post_revision_field_'.$prefix.'post_title', array($this, 'revision_field'), 10, 3);
					add_filter('_wp_post_revision_field_'.$prefix.'post_content', array($this, 'revision_field'), 10, 3);
					add_filter('_wp_post_revision_field_'.$prefix.'post_excerpt', array($this, 'revision_field'), 10, 3);
			
				}
			
			}
			
		}
		
		return $fields;
	}
	
	/**
	 * Fix revision check for change. Overrided in Sublanguage_admin.
	 *
	 * @filter 'wp_save_post_revision_post_has_changed'
	 *
	 * @from 2.6
	 */
	public function revision_post_has_changed($post_has_changed, $last_revision, $post) {
		
		return $post_has_changed; // actually not used in Gutenberg!
	
	}
	
	/**
	 * Add revision field
	 *
	 * @hook '_wp_post_revision_field_{$field}'
	 *
	 * @from 2.6
	 */
	public function revision_field($value, $field, $revision) {
		
		return get_metadata( 'post', $revision->ID, $field, true );

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
			
			return array_filter($post_type, array($this, 'is_post_type_translatable')); // at least one is
		
		}
		
	}
	
	/**
	 * Handle search query
	 *
	 * @hook for 'parse_query'
	 *
	 * @from 2.2
	 * @from 2.5, added 'sublanguage_search_meta' filter
	 */
	public function parse_query($wp_query) {
		
		if ($this->is_query_translatable($wp_query->query_vars)) {
			
			$language = isset($wp_query->query_vars['sublanguage']) ? $this->find_language($wp_query->query_vars['sublanguage']) : null;
				
			if (isset($wp_query->query_vars['s']) && $wp_query->query_vars['s']) { // query_vars['s'] is empty string by default 
				
				$search_meta_fields = array();
				
				if ($this->is_sub($language)) {
				
					$search_meta_fields = array(
						$this->get_prefix($language) . 'post_title',
						$this->get_prefix($language) . 'post_content',
						$this->get_prefix($language) . 'post_excerpt'
					);
				
				}
				
				/**
				 * Filter meta keys searcheable for translation
				 *
				 * @from 2.5
				 *
				 * @param array $search_meta_fields. Array of custom field keys to search into
				 * @param WP_Query object $query
				 * @param Post object $language
				 * @param Sublanguage_current object $this
				 */	
				$search_meta_fields = apply_filters('sublanguage_search_meta', $search_meta_fields, $wp_query, $language, $this);
				
				if ($search_meta_fields) {
					
					$wp_query->query_vars['sublanguage_search_meta'] = $search_meta_fields;
					$wp_query->query_vars['sublanguage_search_alias'] = 'postmeta_search';
				
					add_filter('posts_join_request', array($this, 'meta_search_join'), 10, 2);
					add_filter('posts_search', array($this, 'meta_search'), 10, 2);
					add_filter('posts_distinct_request', array($this, 'meta_search_distinct'), 10, 2);
				
				}
				
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
	 * @from 2.5 perform update_post_caches before translation
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
		
		if ($this->is_sub($language) && $posts) {
			
			// normally update_post_caches() is performed after 'the_posts' filter trigger.
			update_post_caches($posts, $posts[0]->post_type, $wp_query->query_vars['update_post_term_cache'], true);
			$wp_query->query_vars['cache_results'] = false; // prevent further caching
			
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
	 * @from 2.5 check wether content corresponds to global post ('the_content' filter may be used outside of The Loop or when global $post does not match) 
	 */	
	public function translate_post_content($content) {
		global $post;
		
		if ($post && $post->post_content === $content && $this->is_sub() && $this->is_post_type_translatable($post->post_type) && empty($post->sublanguage)) {
		
			$content = $this->translate_post_field($post, 'post_content', null, $content);
									
		}
		
		return $content;
		
	}

	/**
	 * Translate excerpt to current language (the_excerpt() and the_content() behave very differently!)
	 * @filter for 'get_the_excerpt'
	 *
	 * @from 1.0
	 * @from 2.5 Introduce $post parameter (@since wp 4.5)
	 */	
	public function translate_post_excerpt($excerpt, $post = null) {
		
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
	 * Public API
	 * Filter for 'sublanguage_has_post_translation'
	 *
	 * @from 2.5
	 *
	 * @param boolean $false Value to be filtered. Should be false.
	 * @param object WP_Post $post Post to translate field.
	 * @param string|mixed $field Field name. Accepts 'post_content', 'post_title', 'post_name', 'post_excerpt', an array of those, or null
	 * @param object WP_Post $language Language
	 *
	 * @return string
	 */
	public function filter_has_post_translation($false, $post = null, $field = null, $language = null) {
		
		$language = $this->find_language($language);
		
		return (bool) $post && $this->has_post_translation($post, $field, $language);
	
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
	 * @from 2.3 use specific archive slug if any
	 */
	function translate_post_type_archive_link($link, $post_type) {
		global $wp_rewrite;
    	
		if ($this->is_post_type_translatable($post_type)) {
    	
			$post_type_obj = get_post_type_object($post_type);
			
			if ($post_type_obj && get_option( 'permalink_structure' )) {
				
				$struct = $this->translate_cpt_archive($post_type);
        
				if ( $post_type_obj->rewrite['with_front'] )
					$struct = $wp_rewrite->front . $struct;
				else
					$struct = $wp_rewrite->root . $struct;
				
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
		
		if (isset($object->post_type) && $this->is_sub() && $this->translate_meta) {
			
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
		
		if (get_option('permalink_structure') && $this->is_taxonomy_translatable($taxonomy)) {
			
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
	 * @from 2.5 add post_type_options + gutenberg tag and reg
	 */	
	public function ajax_enqueue_scripts() {
		
		$language = $this->get_language();
		
		$sublanguage = array(
			'current' => $language ? $language->post_name : 0,
			'languages' => array(),
			'query_var' => $this->language_query_var,
			'post_type_options' => $this->get_post_types_options(),
			'gutenberg' => array(
				'tag' => $this->gutenberg_language_tag,
				'reg' => $this->gutenberg_language_reg
			)
		);
		
		foreach($this->get_languages() as $language) {
			
			$this->set_language($language);
			$home_url = home_url();
			$this->restore_language();
			
			$sublanguage['languages'][] = array(
				'name' => $language->post_title,
				'slug' => $language->post_name,
				'id' => $language->ID,
				'prefix' => $this->get_prefix($language),
				'tag' => $language->post_excerpt,
				'locale' => $language->post_content,
				'isDefault' => $this->is_default($language),
				'isMain' => $this->is_main($language),
				'isSub' => $this->is_sub($language),
				'home_url' => $home_url
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

