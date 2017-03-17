<?php 

class Sublanguage_admin_ui extends Sublanguage_admin {
	
	
	/**
	 * @var array
	 */
	var $extra_post_types = array();
	
	/**
	 * @var array
	 */
	var $menu_languages;
	
	
	/** deprecated??
	 * @from 1.0
	 */
	var $page_name = 'sublanguage';
	
	/**
	 * @var string
	 */
	var $option_page_name = 'translate_options';
	
	
	/**
	 * @override Sublanguage_admin::load
	 *
	 * @from 2.0
	 */
	public function load() {
				
		parent::load();
		
		add_action('admin_menu', array($this, 'admin_menu'));
		
		// save post and taxnomy options
		add_action('init', array($this, 'save_post_option'), 99); 
		
		// register post types without UI
		add_action('init', array($this, 'register_extra_post'), 20);
		
		// register languages
		add_action('init', array($this, 'register_languages'), 20);
		
		// redirect post to requested translation
		add_filter('redirect_post_location', array($this, 'language_redirect'));
		
		// on load post.php
 		add_action('load-post.php', array($this, 'admin_post_page'));
 		add_action('load-post-new.php', array($this, 'admin_post_page'));
 		
		// on load edit.php
		add_action('load-edit.php', array($this, 'admin_edit_page'));
		
		// save term translations
		add_action('edit_term', array($this, 'save_term_translation'), 10, 3);
		
		// tags table
		add_action('load-edit-tags.php', array($this, 'admin_edit_tags'));
		
		// add language meta box in appearance > menu
		add_action('admin_init', array($this, 'add_language_meta_box'));
		
		// register CSS and JS for option translation page
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		
		// enqueue ajax script
		add_action('admin_enqueue_scripts', array($this, 'ajax_enqueue_scripts'));
		
		// add option translation page to tools menu
		add_action('admin_menu', array($this, 'admin_menu'));
		
		// Editor button
		add_action('admin_head', array($this, 'load_editor_button'));
		add_action('load-post.php', array($this, 'load_editor_page'));
		
		// Register for Tinymce Advanced Plugin
		add_filter('tadv_allowed_buttons', array($this, 'tadv_register_button'));
		add_action('admin_head', array($this, 'tadv_set_icon'));
		
		// Flush rewrite rules if needed
		add_action('wp_loaded', array($this, 'flush_rewrite_rules'), 12);
		
		// set nav menu item translation defaults
  	add_filter('sublanguage_post_type_default', array($this, 'nav_menu_item_post_type_default'));
  	
	}
	
	/**
	 * Admin init
	 *
	 * @hook 'admin_init'
	 * @from 1.5
	 */
	public function admin_init() {
		
	}
	
	
	/**
	 * Add sublanguage subpage for options translation
	 *
	 * @hook 'admin_menu'
	 * @from 1.5
	 */
	public function admin_menu() {
		
		add_submenu_page(
			'options-general.php',
			__('Sublanguage Settings', 'sublanguage'), 
			__('Sublanguage', 'sublanguage'), 
			'manage_options', 
			'sublanguage-settings', 
			array($this, 'print_setting_page')
		);	
		
		$post_types = get_post_types(array(
			'show_ui' => true
		));
		
		foreach ($post_types as $post_type) {
			
			if ($this->is_post_type_translatable($post_type)) {
				
				if (in_array($post_type, $this->extra_post_types)) { // -> extra (without default interface) custom posts
					
					add_submenu_page (
						'tools.php',
						$post_type . ' Post Language Options',
						'Language Options',
						'manage_options',
						$post_type . '_language_option',
						array($this, 'print_extra_post_language_option_page') 
					);
					
				} else if ($post_type === 'nav_menu_item') {
					
					add_submenu_page ( // -> nav menu items
						'tools.php',
						'Nav Menu Items Language Options',
						'Nav Menu Items Language Options',
						'manage_options',
						$post_type . '_language_option',
						array($this, 'print_extra_post_language_option_page') 
					);
					
				} else if ($post_type === 'post') { // -> posts
				
					add_submenu_page (
						'edit.php',
						'Post Language Options',
						'Language Options',
						'manage_options',
						$post_type . '_language_option',
						array($this, 'print_post_language_option_page') 
					);
				
				} else { // -> public custom posts
				
					add_submenu_page (
						'edit.php?post_type=' . $post_type,
						'Post Language Options',
						'Language Options',
						'manage_options',
						$post_type . '_language_option',
						array($this, 'print_post_language_option_page') 
					);
					
				}
				
			}
		
		}
		
		if ($this->is_post_type_translatable('attachment')) {
			
			add_submenu_page (
				'upload.php',
				'Media Language Options',
				'Language Options',
				'manage_options',
				'attachment_language_option',
				array($this, 'print_attachment_language_option_page') 
			);
			
		}
		
		$taxonomies = get_taxonomies(array(
			'public'   => true
		));
		
		foreach ($taxonomies as $taxonomy) {
			
			if ($this->is_taxonomy_translatable($taxonomy)) {
			
				add_submenu_page (
					null, // no parent
					'Taxonomy Language Options',
					'Taxonomy Language Options',
					'manage_options',
					$taxonomy . '_language_option',
					array($this, 'print_taxonomy_language_option_page') 
				);
				
			}
		
		}
		
		// options translate page in tools
		add_submenu_page (
			'tools.php',
			'Translate Options',
			'Translate Options',
			'manage_options',
			$this->option_page_name,
			array($this, 'print_page') 
		);
		
		
	}


	/* Settings
	----------------------------------------------- */
	
	/**
	 * Print general setting page
	 * 
	 * @from 1.5
	 */
	public function print_setting_page() {
		
		include plugin_dir_path( __FILE__ ) . 'include/settings-page.php';
		
	}
	
	/**
	 * Print post-type option page
	 * 
	 * @callback for add_submenu_page()
	 *
	 * @from 2.0
	 */
	public function print_post_language_option_page() {
		
		$screen = get_current_screen();
		$post_type = empty($screen->post_type) ? 'post' : $screen->post_type;
		$post_type_obj = get_post_type_object($post_type);
		$meta_keys = $this->query_post_type_metakeys($post_type);
		$registered_meta_keys = get_registered_meta_keys( 'posts' );
		
		if ($this->is_post_type_translatable($post_type)) {
			
			include plugin_dir_path( __FILE__ ) . 'include/settings-post-option-page.php';
		
		}
		
	}
	
	/**
	 * Print extra post-type option page
	 * 
	 * @callback for add_submenu_page()
	 *
	 * @from 2.0
	 */
	public function print_extra_post_language_option_page() {
		
		$page = esc_attr($_GET['page']);
		$post_type = substr($page, 0, strrpos($page, '_language_option'));
		$post_type_obj = get_post_type_object($post_type);
		$meta_keys = $this->query_post_type_metakeys($post_type);
		$registered_meta_keys = get_registered_meta_keys( 'posts' );
		
		if ($this->is_post_type_translatable($post_type)) {
			
			include plugin_dir_path( __FILE__ ) . 'include/settings-post-option-page.php';
		
		}
		
	}
	
	/**
	 * Print attachment option page
	 * 
	 * @from 2.0
	 */
	public function print_attachment_language_option_page() {
		
		$post_type = 'attachment';
		$post_type_obj = get_post_type_object($post_type);
		$meta_keys = $this->query_post_type_metakeys($post_type);
		$registered_meta_keys = get_registered_meta_keys( 'posts' );
		
		if ($this->is_post_type_translatable($post_type)) {
			
			include plugin_dir_path( __FILE__ ) . 'include/settings-attachment-option-page.php';
		
		}
		
	}	

	/**
	 * Print taxonomy option page
	 * 
	 * @from 1.5
	 */
	public function print_taxonomy_language_option_page() {
		
		if (isset($_GET['taxonomy'])) {

			$taxonomy = esc_attr($_GET['taxonomy']);
			$tax_obj = get_taxonomy($taxonomy);
			$meta_keys = $this->query_taxonomy_metakeys($taxonomy);
			$registered_meta_keys = get_registered_meta_keys( 'term' );
			
			if ($this->is_taxonomy_translatable($taxonomy)) {
			
				include plugin_dir_path( __FILE__ ) . 'include/settings-taxonomy-option-page.php';
		
			}
			
		}
		
	}
	
	/**
	 * Save post-type/taxonomy option page
	 * 
	 * @from 2.0
	 */
	public function save_post_option() {
		
		if (current_user_can('manage_options')) {
		
			if (isset($_POST['sublanguage_post_option'], $_POST['post_type']) && wp_verify_nonce($_POST['sublanguage_post_option'], 'sublanguage_action')) {
			
				$post_type = esc_attr($_POST['post_type']);
			
				if ($this->is_post_type_translatable($post_type)) {
				
					// permalinks
					if (isset($_POST['cpt'])) {
					
						$translations = $this->get_option('translations', array());
						$cpt = isset($_POST['cpt']) ? array_map('esc_attr', $_POST['cpt']) : array();
					
						if (!isset($translations['post_type'][$post_type]) || $translations['post_type'][$post_type] !== $cpt) {
							$translations['post_type'][$post_type] = $cpt;
							$this->update_option('translations', $translations);
							$this->update_option('need_flush', 1);
						}
					
					}
					
					$post_types_options = $this->get_post_type_options();
					
					// fields
					$fields = isset($_POST['fields']) ? array_map('esc_attr', $_POST['fields']) : array();
					$post_types_options[$post_type]['fields'] = $fields;
			
					// meta
					$meta_keys = isset($_POST['meta_keys']) ? array_map('esc_attr', $_POST['meta_keys']) : array();
					$post_types_options[$post_type]['meta_keys'] = $meta_keys;
			
					// advanced options
					$exclude_untranslated = isset($_POST['exclude_untranslated']) && $_POST['exclude_untranslated'];
					$post_types_options[$post_type]['exclude_untranslated'] = $exclude_untranslated;
			
					$this->update_option('post_type', $post_types_options);
				
				}
			
				wp_redirect($_POST['_wp_http_referer']);
				exit;
			
			} else if (isset($_POST['sublanguage_taxonomy_option'], $_POST['taxonomy']) && wp_verify_nonce($_POST['sublanguage_taxonomy_option'], 'sublanguage_action')) {
			
				$taxonomy = esc_attr($_POST['taxonomy']);
			
				if ($this->is_taxonomy_translatable($taxonomy)) {
				
					// permalinks
					if (isset($_POST['tax'])) {
					
						$translations = $this->get_option('translations', array());
						$tax =  isset($_POST['tax']) ? array_map('esc_attr', $_POST['tax']) : array();
					
						if (!isset($translations['taxonomy'][$taxonomy]) || $translations['taxonomy'][$taxonomy] !== $tax) {
							$translations['taxonomy'][$taxonomy] = $tax;
							$this->update_option('translations', $translations);
							$this->update_option('need_flush', 1);
						}
					
						$this->update_option('translations', $translations);
				
					}
					
					$taxonomies_options = $this->get_taxonomies_options();
						
					// fields
					$fields = isset($_POST['fields']) ? array_map('esc_attr', $_POST['fields']) : array();
					$taxonomies_options[$taxonomy]['fields'] = $fields;
			
					// meta
					$meta_keys = isset($_POST['meta_keys']) ? array_map('esc_attr', $_POST['meta_keys']) : array();
					$taxonomies_options[$taxonomy]['meta_keys'] = $meta_keys;
				
					$this->update_option('taxonomy', $taxonomies_options);
				
				}
			
				wp_redirect($_POST['_wp_http_referer']);
				exit;
			
			} else if (isset($_POST['sublanguage_settings_option']) && wp_verify_nonce($_POST['sublanguage_settings_option'], 'sublanguage_action')) {
			
				$options = get_option($this->option_name);
			
				$post_type_options = $this->get_post_type_options();
				$post_type_objs = get_post_types(array(), 'objects');
			
				foreach ($post_type_objs as $post_type_obj) {
				
					$post_type_options[$post_type_obj->name]['translatable'] = isset($_POST['post_type']) && in_array($post_type_obj->name, $_POST['post_type']);
				
				}
			
				$taxonomies_options = $this->get_taxonomies_options();
				$taxonomy_objs = get_taxonomies(array('show_ui' => true), 'objects');
			
				foreach ($taxonomy_objs as $taxonomy_obj) {
				
					$taxonomies_options[$taxonomy_obj->name]['translatable'] = isset($_POST['taxonomy']) && in_array($taxonomy_obj->name, $_POST['taxonomy']);
				
				}
			
				$options['post_type'] = $post_type_options;
				$options['taxonomy'] = $taxonomies_options;
				$options['show_slug'] = (isset($_POST['show_slug']) && $_POST['show_slug']) ? true : false;
				$options['autodetect'] = (isset($_POST['autodetect']) && $_POST['autodetect']) ? true : false;
				$options['current_first'] = (isset($_POST['current_first']) && $_POST['current_first']) ? true : false;
				$options['main'] = isset($_POST['main']) ? intval($_POST['main']) : 0;
				$options['default'] = isset($_POST['default']) ? intval($_POST['default']) : 0;
				$options['frontend_ajax'] = (isset($_POST['frontend_ajax']) && $_POST['frontend_ajax']) ? true : false;
				
				update_option($this->option_name, $options);
			
				wp_redirect($_POST['_wp_http_referer']);
				exit;
			
			}
		
		}
		
	}
	
	/**
	 * Get post_type meta keys
	 *
	 * @from 2.0
	 */
	private function query_post_type_metakeys($post_type) {
		global $wpdb;
		
		$prefixes = array();
		
		foreach ($this->get_languages() as $language) {
			
			$prefixes[] = $this->get_prefix($language);
		
		}
		
		$sql_prefixes = implode("%' AND meta.meta_key NOT LIKE '", array_map('esc_sql', $prefixes));
		$sql_blacklist = implode("', '", array_map('esc_sql', $this->get_post_meta_keys_blacklist()));
		$sql_post_type = esc_sql($post_type);
		
		// find all existing meta data for this post type
		$results = $wpdb->get_results("
			SELECT meta.meta_key, meta.meta_value
			FROM $wpdb->postmeta AS meta
			LEFT JOIN $wpdb->posts AS post ON (post.ID = meta.post_id)
			WHERE post.post_type = '$sql_post_type' AND meta.meta_key NOT LIKE '$sql_prefixes%' AND meta.meta_key NOT IN ('$sql_blacklist')
			GROUP BY meta.meta_key"
		);
			
		// empty array of meta data sorted by meta keys
		$meta_keys = array();
		
		// registered meta_keys
		$registered_meta_keys = $this->get_post_type_metakeys($post_type);
		
		foreach ($registered_meta_keys as $meta_key) {
			
			$meta_keys[$meta_key] = array();
		
		}
		
		// add database results
		foreach ($results as $row) {
			
			$meta_keys[$row->meta_key][] = substr(wp_strip_all_tags($row->meta_value, true), 0, 120);
		
		}
		
		ksort($meta_keys);
		
		return $meta_keys;
				
	}
	
	/*
	 * List of post meta keys that should never be translated
	 * 
	 * @from 1.5
	 */
	private function get_post_meta_keys_blacklist() {
	
		return apply_filters('sublanguage_meta_keys_blacklist', array(
			'_wp_attached_file',
			'_wp_attachment_metadata',
			'_edit_lock',
			'_edit_last',
			'_wp_page_template'
		));
		
	}
	
	/**
	 * Get taxonomy meta keys
	 *
	 * @from 2.0
	 */
	private function query_taxonomy_metakeys($taxonomy) {
		global $wpdb;
		
		$prefixes = array();
		
		foreach ($this->get_languages() as $language) {
			
			$prefixes[] = $this->get_prefix($language);
		
		}
		
		$sql_prefixes = implode("%' AND meta.meta_key NOT LIKE '", array_map('esc_sql', $prefixes));
		$sql_blacklist = implode("', '", array_map('esc_sql', $this->get_taxonomy_meta_keys_blacklist()));
		$sql_taxonomy = esc_sql($taxonomy);
		
		// find all existing meta data for this post type
		$results = $wpdb->get_results("
			SELECT meta.meta_key, meta.meta_value
			FROM $wpdb->termmeta AS meta
			LEFT JOIN $wpdb->term_taxonomy AS tt ON (tt.term_id = meta.term_id)
			WHERE tt.taxonomy = '$sql_taxonomy' AND meta.meta_key NOT LIKE '$sql_prefixes%' AND meta.meta_key NOT IN ('$sql_blacklist')
			GROUP BY meta.meta_key"
		);
			
		// empty array of meta data sorted by meta keys
		$meta_keys = array();
		
		// registered meta_keys
		$registered_meta_keys = $this->get_taxonomy_metakeys($taxonomy);
		
		foreach ($registered_meta_keys as $meta_key) {
			
			$meta_keys[$meta_key] = array();
		
		}
		
		// add database results
		foreach ($results as $row) {
			
			$meta_keys[$row->meta_key][] = substr(wp_strip_all_tags($row->meta_value, true), 0, 120);
		
		}
		
		ksort($meta_keys);
		
		return $meta_keys;
		
	}

	/*
	 * List of taxonomy meta keys that should never be translated
	 * 
	 * @from 1.5
	 */
	private function get_taxonomy_meta_keys_blacklist() {
	
		return apply_filters('sublanguage_meta_keys_blacklist', array());
		
	}
	
	/**
	 * Flush rewrite rules
	 *
	 * @hook 'wp_loaded'
	 * @from 2.0
	 */
	public function flush_rewrite_rules() {
		
		if ($this->get_option('need_flush')) {
		
			$this->disable_translate_home_url = true;
		
			flush_rewrite_rules();
		
			$this->disable_translate_home_url = false;
			
		}
		
	}
	
	
	
	/* Languages UI
	----------------------------------------------- */
	
	/**
	 * Register language post-type
	 * @from 1.0
	 */
	public function register_languages() {
		
		// preset slug and title
		add_filter('wp_insert_post_data', array($this, 'set_default_slug_and_name'), 10, 2);
		
		// Update post on save
		add_action('save_post_' . $this->language_post_type, array($this, 'save_language_meta_box'), 10, 3);
		
		// sort languages by menu_order in language list (no default order because it is not hierarchical)
		add_filter( 'parse_query', array($this, 'sort_languages_table' ));
		
		// never hide the slug meta box
		add_filter('default_hidden_meta_boxes', array($this, 'hide_language_settings'), null, 2);
		
		// Remove title, slug and attributes on new post
		add_action('load-post-new.php', array($this, 'new_language_post'));
		
		// add meta-box for locale and rtl
		add_action('load-post.php', array($this, 'edit_language_post'));
	
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
	 * Remove title on post-new.php
	 *
	 * Hook for 'load-post-new.php'
	 *
	 * @from 1.2
	 */	
	public function new_language_post() {
		
		$current_screen = get_current_screen();
		
		if (isset($current_screen->post_type) && $current_screen->post_type === $this->language_post_type) {
			
			remove_post_type_support($current_screen->post_type, 'title');
			remove_meta_box('slugdiv', $current_screen->post_type, 'normal');
			
			add_meta_box(
				'languages_local',
				__( 'Locale', 'sublanguage' ),
				array($this, 'locale_dropdown_meta_box_callback'),
				'language',
				'normal',
				'default'
			);
			
		}
	
	}
	
	/**
	 * Add meta-boxes on post.php
	 *
	 * Hook for 'load-post-new.php'
	 *
	 * @from 1.2
	 */	
	public function edit_language_post() {
		
		$current_screen = get_current_screen();
		
		if (isset($current_screen->post_type) && $current_screen->post_type === $this->language_post_type) {
			
			add_meta_box(
				'languages_local',
				__( 'Locale', 'sublanguage' ),
				array($this, 'locale_meta_box_callback'),
				$this->language_post_type,
				'normal',
				'default'
			);
		
			add_meta_box(
				'languages_settings',
				__( 'Settings', 'sublanguage' ),
				array($this, 'settings_meta_box_callback'),
				$this->language_post_type,
				'normal',
				'default'
			);
			
		}
	
	}
	
	/**
	 * Print language locale input meta-box
	 *
	 * @from 1.0
	 */
	public function locale_meta_box_callback( $post ) {
		
		include plugin_dir_path( __FILE__ ) . 'include/language-locale-metabox.php';
		
	}
	
	/**
	 * Print language locale dropdown meta-box
	 *
	 * @from 1.0
	 */
	public function locale_dropdown_meta_box_callback( $post ) {
		
		include plugin_dir_path( __FILE__ ) . 'include/language-dropdown-metabox.php';
				
	}
	
	/**
	 * Print meta-box
	 *
	 * @from 1.0
	 */
	public function settings_meta_box_callback( $post ) {
		
		include plugin_dir_path( __FILE__ ) . 'include/language-settings-metabox.php';
				
	}
	
	/**
	 * Set default language title and slug
	 * Filter for 'wp_insert_post_data'
	 *
	 * @from 1.0
	 */
	public function set_default_slug_and_name($data, $postarr) {
		
		if ($data['post_type'] === $this->language_post_type) {
				
			if (isset($_POST['language_locale_dropdown'], $_POST['language_locale_dropdown_nonce']) && wp_verify_nonce( $_POST['language_locale_dropdown_nonce'], 'language_locale_dropdown_action' )) {
			
				require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
			
				$translations = wp_get_available_translations();
				$locale = $_POST['language_locale_dropdown'];
			
				$data['post_content'] = esc_attr($_POST['language_locale_dropdown']);
			
				if (isset($translations[$locale]['native_name'])) {
				
					$data['post_title'] = $translations[$locale]['native_name'];
			
				} else { // -> added in 1.4.7
				
					$data['post_title'] = 'English';
				
				}
			
				if (isset($translations[$locale]['iso'])) {
				
					while (is_array($translations[$locale]['iso'])) {
					
						$translations[$locale]['iso'] = array_shift($translations[$locale]['iso']);
					
					}
				
					$data['post_name'] = $translations[$locale]['iso'];
				
				} else { // -> added in 1.4.7
				
					$data['post_name'] = 'en';
				
				}
				
			}
			
		}	
		
		return $data;
		
	}
	
	/**
	 * Save meta-box.
	 * Hook for "save_post_{$post->post_type}"
	 *
	 * @from 1.4.7 update main option if post is trashed or if not exist.
	 * @from 1.0
	 */
	public function save_language_meta_box( $post_id, $post, $update ) {
		
		if ($post->post_type === $this->language_post_type) {
			
			if ((!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE)	&& current_user_can('edit_language', $post_id)) {
				
				// save locale (into post_content)
				
				if (isset($_POST['language_locale_nonce'], $_POST['language_locale']) 
					&& wp_verify_nonce($_POST['language_locale_nonce'], 'language_locale_action' )) {
					
					$locale = esc_attr($_POST['language_locale']);
					
					// download language pack
										
					if ($post->post_content != $locale) {
					
						require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
						
						wp_download_language_pack($locale);
						
						wp_update_post(array(
							'ID' => $post->ID,
							'post_content' => $locale
						));
						
					}
					
					$this->update_option('need_flush', 1);
					
				}
				
				// save language settings
				if (isset($_POST['language_settings_nonce']) 
					&& wp_verify_nonce($_POST['language_settings_nonce'], 'language_settings_action')) {
					
					if (isset($_POST['language_rtl']) && $_POST['language_rtl']) {
						
						update_post_meta($post->ID, 'rtl', 1);
						
					}
					
				}
				
				// verify settings -> added in 1.4.7
				if ($post->post_status == "trash" && $this->is_main($post)) {
					
					$next_language = $this->get_valid_language($post);
					
					if ($next_language) {
						
						$this->update_option('main', $next_language->ID);
					
					}
					
				} else if (!$this->get_option('main')) {
					
					$this->update_option('main', $post_id);
					
				}
							
			}
			
		}
		
	}
	
	/**
	 * Order posts by menu_order
	 * @hook 'parse_query'
	 *
	 * @from 1.0
	 */
	public function sort_languages_table($query) {
		
		if (function_exists('get_current_screen')) {
			
			$screen = get_current_screen();
			
			if (isset($screen->id) && $screen->id == 'edit-language') {
			
				$query->query_vars['orderby'] = 'menu_order';
				$query->query_vars['order'] = 'ASC';
				
			}
			
		}
		
	}

	/**
	 * By default hide settings
	 * @hook 'default_hidden_meta_boxes'
	 *
	 * @from 1.0
	 */
	public function hide_language_settings($hidden, $screen) {
		
		if (isset($screen) && $screen->id == 'language') {
			
			$hidden[] = 'languages_settings';
			
			$key = array_search('slugdiv', $hidden);
			
			if ($key !== false) {
			
				unset($hidden[$key]);
				
			}
			
		}
		
		return $hidden;
	}
	
	/**
	 * find a language not in trash
	 *
	 * @from 1.4.7
	 */
	public function get_valid_language($except_post) {
		
		foreach ($this->get_languages() as $language) {
			
			if ($language->post_status != 'trash' && $language->ID !== $except_post->ID) return $language;
		
		}
		
		return false;
	}



	
	/* Posts UI
	----------------------------------------------- */
	
	/**
	 * Fire filters on post.php
	 *
	 * Hook for 'load-post.php'
	 *
	 * @from 1.1
	 */
	public function admin_post_page() {	
		
		$current_screen = get_current_screen();
		
		if ($this->get_language() && isset($current_screen->post_type) && $this->is_post_type_translatable($current_screen->post_type)) {
			
			// translate $post global
			add_action('edit_form_top', array($this, 'edit_form')); 
			
			// translate post permalink 2.0
			add_filter('get_sample_permalink', array($this, 'translate_sample_permalink'), 10, 5);
			
			// print language tab
			add_action('edit_form_top', array($this, 'print_post_language_tabs'));
		
			// post title placeholder
			add_filter('enter_title_here', array($this, 'set_post_title_placeholder'), 10, 2);
			
			// allow translate home url
			add_filter('home_url', array($this,'translate_home_url'), 10, 4);
			
		}
		
	}
	
	/**
	 * Change the values of $post at the begin of form in post.php
	 *
	 * @hook 'edit_form_top'
	 * @from 1.0
	 */		
	public function edit_form($post) {
		
		if ($this->is_sub()) {
			
			foreach ($this->fields as $field) {
				
				$post->$field = $this->translate_post_field($post, $field, null, '');
				
			}
			
		}
			
	}
	
	/**
	 * Translate post slug
	 * 
	 * @filter 'get_sample_permalink'
	 * @from 2.0
	 */	
	public function translate_sample_permalink($permalink, $post_id, $title, $name, $post) {
		
		if ($this->is_post_type_translatable($post->post_type)) {
		
			$translation = $this->translate_cpt($post->post_type, null, $post->post_type);
			$permalink[0] = str_replace("%{$post->post_type}-slug%", $translation, $permalink[0]);
			
			if ($this->is_sub()) {
		
				// translate ancestors slugs
				$current = $post;
				while ($current->post_parent) {
					$current = get_post($current->post_parent);
					$original_name = $current->post_name;
					$translated_name = $this->translate_post_field($current, 'post_name');
					if ($original_name !== $translated_name) {
						$permalink[0] = str_replace("/$original_name/", "/$translated_name/", $permalink[0]);
					}
				}
			
				$permalink[1] = $this->translate_post_field($post, 'post_name');
		
			}
		 
		}
		
		return $permalink;
	}
	
	
	/**
	 * Customize title placeholder
	 *
	 * @filter 'enter_title_here'
	 * @from 1.2
	 */	
	public function set_post_title_placeholder($title, $post) {
		
		if ($this->is_sub()) {
			
			return get_post($post->post_parent)->post_title;
		
		}
		
		return $title;
	}
	
	/**
	 * Renders language switch tab
	 * 
	 * @hook 'edit_form_top'
	 * @from 1.5
	 */
	public function print_post_language_tabs($post) {
				
		include plugin_dir_path( __FILE__ ) . 'include/posts-form-language-tabs.php';
		
	}
	
	
	/**
	 * fire filters on edit.php
	 *
	 * @hook 'load-edit.php'
	 * @from 1.2
	 */
	public function admin_edit_page() {	
		
		$current_screen = get_current_screen();
		
		if ($this->get_language() && isset($current_screen->post_type) && $this->is_post_type_translatable($current_screen->post_type)) {
			
			add_filter('views_'.$current_screen->id, array($this, 'table_views'));
			add_action('restrict_manage_posts', array($this, 'print_table_filtering'));
			
		}
	
	}
	
	/*
	 * Set requested language
	 * Filter for 'wp_redirect'
	 *
	 * @from 1.0
	 */
	public function language_redirect( $location ) {
		
		if (isset($_POST['post_language_switch'], $_POST['sublanguage_switch_language_nonce']) && wp_verify_nonce($_POST['sublanguage_switch_language_nonce'], 'sublanguage_switch_language')) {
			
			if ($this->get_language_by($_POST['post_language_switch'], 'post_name')) {
			
				$location = add_query_arg(array($this->language_query_var => esc_attr($_POST['post_language_switch'])), $location);
			
			}
			
		}
		
		return $location;
	}
	
	/**
	 * Print language switch for posts table
	 * Add language param in view links
	 *
	 * @filter "views_{$this->screen->id}" ('views_edit-post')
	 * @from 1.2
	 */	
	public function table_views($views) {
		
		$new_views = array();
		
		$base_url = admin_url('edit.php');
		
		if (isset($_GET['post_status'])) {
		
			$base_url = add_query_arg(array('post_status' => esc_attr($_GET['post_status'])), $base_url);
			
		}
		
		if (isset($_GET['post_type'])) {
		
			$base_url = add_query_arg(array('post_type' => esc_attr($_GET['post_type'])), $base_url);
			
		}
		
		ob_start();
		
		include plugin_dir_path( __FILE__ ) . 'include/posts-table-language-switch.php';
		
		$new_views[] = ob_get_contents();
		ob_end_clean();
		
		$language = $this->get_language();
		
		if ($language) {
		
			foreach ($views as $view) {
				
				if (preg_match('/href=[\'"]([^\'"]*)/', $view, $matches)) {
			
					$match_decoded = html_entity_decode($matches[1]); //handle HTML encoding in links with existing parameters (IE in WooCommerce "Sort Products" link) // thx to @delacqua
					$new_views[] = str_replace($matches[1], add_query_arg(array($this->language_query_var => $language->post_name), $match_decoded), $view);
			
				} else {
				
					$new_views[] = $view;
				
				}
			
			}
			
		}
		
		return $new_views;
	}
	
	/**
	 * Add language switch on posts table
	 *
	 * Hook for 'restrict_manage_posts'
	 *
	 * @from 1.1
	 */
	public function print_table_filtering() {
		
		$language = $this->get_language();
		
		if ($language) {
		
			echo '<input type=hidden name="'.$this->language_query_var.'" value="'.$language->post_name.'"/>';
		
		}
		
	}
	

	
	
	
	
	/* Terms UI
	----------------------------------------------- */
	
	/**
	 * fire filters on edit.php
	 *
	 * @hook 'load-edit-tags.php'
	 * @from 1.2
	 */
	public function admin_edit_tags() {	
		
		$current_screen = get_current_screen();
		
		if ($this->get_language() && isset($current_screen->taxonomy) && $this->is_taxonomy_translatable($current_screen->taxonomy)) {
			 
			add_action($current_screen->taxonomy.'_edit_form_fields', array($this, 'add_term_edit_form'), 12, 2);
			
			add_action('after-'.$current_screen->taxonomy.'-table', array($this, 'add_terms_language_switch'));
			
		}
	
	}
	
	/**
	 * Add translation box on terms edit form.
	 *
	 * @from 1.0
	 */
	public function add_term_edit_form($tag, $taxonomy) {
		
		include plugin_dir_path( __FILE__ ) . 'include/terms-edit-form.php';
		
	}
	
	/**
	 *
	 * @from 1.2
	 */		
	public function add_terms_language_switch($taxonomy) {
		
		include plugin_dir_path( __FILE__ ) . 'include/terms-table-language-switch.php';
		
	}
	
	/**
	 * Intercept update term and save term translation
	 *
	 * @hook "edit_term"
	 * @from 1.0
	 */
	public function save_term_translation($term_id, $tt_id, $taxonomy) {
		
		if ($this->is_taxonomy_translatable($taxonomy)
			&& isset($_POST['sublanguage_term_nonce'], $_POST['sublanguage_term'][$taxonomy]) 
 			&& wp_verify_nonce($_POST['sublanguage_term_nonce'], 'sublanguage')) {
			
			foreach ($_POST['sublanguage_term'][$taxonomy] as $lng_id => $data) {
				
				$language = $this->get_language_by($lng_id, 'ID');
				
				if ($this->is_sub($language)) {
					
					$this->update_term_translation($term_id, $data, $language);
					
				}
				
			}
			
		}		
		
	}
	
	
	

	/* Menu and Extra Post Types
	----------------------------------------------- */
	
	/**
	 * Add metabox on admin menu page
	 * @from 1.2
	 */
	public function add_language_meta_box() {
		
		add_meta_box(
			'language_menu',
			__('Language', 'sublanguage'),
			array( $this, 'render_menu_language_metabox' ),
			'nav-menus',
			'side',
			'high'
		);
		
	}

	/**
	 * Render Meta Box content
	 * @from 1.2
	 */
	public function render_menu_language_metabox() {
		
		include plugin_dir_path( __FILE__ ) . 'include/nav-menu-language-metabox.php';
		
  }
  
  /**
	 * @filter 'sublanguage_post_type_default'
	 * @from 2.0
	 */
	public function nav_menu_item_post_type_default($post_type_options) {
		
		$post_type_options['nav_menu_item']['meta_keys'] = array('sublanguage_hide', '_menu_item_url');
		
		return $post_type_options;
		
  }	
  
	/**
	 * Re-register translatable custom post without admin UI
	 *
	 * @hook 'init'
	 * @from 1.5
	 */
	public function register_extra_post() {
		
		$cpts = get_post_types(array(
			'show_ui' => false
		), 'objects' );
			
		foreach ($cpts as $cpt) {
			
			if ($this->is_post_type_translatable($cpt->name)) {
				
				register_post_type($cpt->name, array(
					'labels'             => array(
						'name'               => isset($cpt->labels->name) ? $cpt->labels->name : $cpt->name,
						'singular_name'      => isset($cpt->labels->singular_name) ? $cpt->labels->singular_name : $cpt->name
					),
					'show_ui'            => true,
					'menu_position'			 => 50,
					'show_in_menu'       => 'tools.php',
				));
				
				remove_post_type_support($cpt->name, 'editor');
				
				if ($cpt->name === 'nav_menu_item') {
			
					add_filter('the_posts', array($this, 'fix_nav_menu_item_parent'), 15);
					add_filter('the_posts', array($this, 'nav_menu_replace_language_keyword'), 14);
					add_filter('the_title', array($this, 'translate_nav_menu_item_title'), 10, 2);
					add_filter('enter_title_here', array($this, 'nav_menu_item_title_placeholder'), 11, 2);
					
					add_action('admin_init', array($this, 'register_nav_menu_item_metabox'));
					
				} else {
					
					$this->extra_post_types[] = $cpt->name;
					
				}
				
				add_action('save_post_' . $cpt->name, array($this, 'save_extra_custom_post'), 10, 2);
				
			}
		
		}
		
		if ($this->extra_post_types) {
		
			add_action('admin_init', array($this, 'register_extra_post_metabox'));
			
		}
		
	}
	
	/**
	 * Register metabox for extra post-types (without UI)
	 *
	 * @hook 'admin_init'
	 * @from 1.5
	 */
	public function register_extra_post_metabox() {
		
		add_meta_box(
			'sublanguage-cpt-translation',
			__('Parameters', 'sublanguage'),
			array( $this, 'print_extra_post_metabox' ),
			$this->extra_post_types,
			'normal'
		);
		
	}
	
	/**
	 * Register metabox specifically for Nav Menu Items
	 *
	 * @hook 'admin_init'
	 * @from 2.0
	 */
	public function register_nav_menu_item_metabox() {
	
		add_meta_box(
			'sublanguage-cpt-translation',
			__('Parameters', 'sublanguage'),
			array( $this, 'print_nav_menu_items_metabox' ),
			'nav_menu_item',
			'normal'
		);
	
	}
	
	/**
	 * Render metabox for extra post types
	 *
	 * @callback for 'add_meta_box()'
	 * @from 1.5
	 */
	public function print_extra_post_metabox($post) {
		
		include plugin_dir_path( __FILE__ ) . 'include/extra-post-metabox.php';
		
	}
	
	/**
	 * Render metabox for extra post types
	 *
	 * @callback for 'add_meta_box()'
	 * @from 1.5
	 */
	public function print_nav_menu_items_metabox($post) {
		
		include plugin_dir_path( __FILE__ ) . 'include/nav-menu-item-metabox.php';
		
	}
	
	/**
	 * Correct menu items parents
	 *
	 * @filter 'the_posts'
	 * @from 1.5
	 */
	public function fix_nav_menu_item_parent($posts) {
		
		foreach ($posts as $post) {
			
			if ($post->post_type === 'nav_menu_item') {
			
				$_menu_item_menu_item_parent = get_post_meta($post->ID, '_menu_item_menu_item_parent', true);
				
				$post->post_parent = $_menu_item_menu_item_parent;
				
			}
			
		}
		
		return $posts;
	}
	
	
	/**
	 * Replace 'language' keyword by language name
	 *
	 * @filter 'the_posts'
	 * @from 1.5
	 */
	public function nav_menu_replace_language_keyword($posts) {
		
		$menu_language_index = 0;
		$languages = $this->get_languages();
		
		foreach ($posts as $post) {
			
			if ($post->post_type === 'nav_menu_item' && $post->post_title === 'language' && get_post_meta($post->ID, '_menu_item_type', true) === 'custom' && isset($languages[$menu_language_index])) {
				
				if (empty($this->menu_languages)) {
					
					$this->menu_languages = array();
					
				}
				
				$this->menu_languages[$post->ID] = $languages[$menu_language_index];
				
				$menu_language_index++;
				
			}
			
		}
		
		return $posts;
	}

	/**
	 * Translate nav menu items title
	 *
	 * @filter 'the_title'
	 * @from 1.5
	 */
	public function translate_nav_menu_item_title($title, $post_id) {
		
		$post = get_post($post_id);
		
		if (isset($post->post_type) && $post->post_type === 'nav_menu_item') {
			
			$title = apply_filters( 'sublanguage_translate_post_field', $post->post_title, $post, 'post_title');

			$_menu_item_type = get_post_meta($post_id, '_menu_item_type', true);
			
			if ($_menu_item_type === 'post_type') {
				
				if (!$title) {
				
					$_menu_item_object_id = get_post_meta($post_id, '_menu_item_object_id', true);
				
					$object_post = get_post($_menu_item_object_id);
				
					$title = apply_filters( 'sublanguage_translate_post_field', $object_post->post_title, $object_post, 'post_title');
				
				}
				
			} else if ($_menu_item_type === 'taxonomy') {
			
				if (!$title) {
				
					$_menu_item_object_id = get_post_meta($post_id, '_menu_item_object_id', true);
					$_menu_item_object = get_post_meta($post_id, '_menu_item_object', true);
					
					$object_term = get_term_by('id', $_menu_item_object_id, $_menu_item_object);
					
					$title = $object_term->name;
					
				}
				
				
			} else if ($_menu_item_type === 'custom') {
				
				if ($title === 'language' && isset($this->menu_languages[$post->ID])) {
					
					$title = $this->menu_languages[$post->ID]->post_title;
					
				}
				
			}
			
		}
		
		return $title;
	}

	/**
	 * Customize title placeholder for nav menu items
	 *
	 * @filter 'enter_title_here'
	 * @from 1.5
	 */	
	public function nav_menu_item_title_placeholder($title, $post) {
		
		if ($post->post_type === 'nav_menu_item') {
			
			$_menu_item_type = get_post_meta($post->ID, '_menu_item_type', true);
			
			if ($_menu_item_type === 'post_type') {
				
				$_menu_item_object_id = get_post_meta($post->ID, '_menu_item_object_id', true);
			
				$object_post = get_post($_menu_item_object_id);
			
				$title = apply_filters( 'sublanguage_translate_post_field', $object_post->post_title, $object_post, 'post_title');
			
			
			} else if ($_menu_item_type === 'taxonomy') {
		
				$_menu_item_object_id = get_post_meta($post->ID, '_menu_item_object_id', true);
				$_menu_item_object = get_post_meta($post->ID, '_menu_item_object', true);
				
				$object_term = get_term_by('id', $_menu_item_object_id, $_menu_item_object);
				
				$title = $object_term->name;
				
			} else if ($_menu_item_type === 'custom') {
			
				$title = $post->post_title;
			
			}
			
		}
		
		return $title;
	}
	
	/**
	 * Save translatable custom post type without admin UI
	 *
	 * @hook for "save_post_{$post->post_type}"
	 * @from 1.5
	 */
	public function save_extra_custom_post($post_id, $post) {
		
		if ((!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE)	&& current_user_can('edit_post', $post_id)) {
			
			if (isset($_POST['sublanguage_extra_cpt_nonce'], $_POST['sublanguage_extra_cpt']) && wp_verify_nonce($_POST['sublanguage_extra_cpt_nonce'], 'sublanguage' )) {
				
				foreach ($_POST['sublanguage_extra_cpt'] as $key => $value) {
					
					if (in_array($key, $this->get_post_type_metakeys($post->post_type))) {
					
						update_post_meta($post->ID, $key, $value);
						
					}
				
				}
				
			}
			
		}
		
	}
	
	
	
	
	
	/* Option translation
	----------------------------------------------- */
	
	/*
	 * Renders Translate Options page
	 * 
	 * @from 1.5
	 */
	public function print_page() {
		global $wpdb;
		
		$sql_blacklist = "option_name NOT IN ('" . implode("', '", array_map('esc_sql', $this->get_options_blacklist())) . "')";
		
		$options = $wpdb->get_results( "
			SELECT option_name, option_value
			FROM $wpdb->options
			WHERE option_name NOT LIKE '_transient%' AND option_name NOT LIKE '_site_transient%' AND $sql_blacklist
			ORDER BY option_name" 
		);
		
		include plugin_dir_path( __FILE__ ) . 'include/option-page.php';
		
	}
	
	/*
	 * List of options that should not be translated
	 * 
	 * @from 1.5
	 */
	private function get_options_blacklist() {
	
		return apply_filters('sublanguage_options_blacklist', array(
			'active_plugins',
			'admin_email',
			'auto_core_update_notified',
			'avatar_default',
			'avatar_rating',
			'blacklist_keys',
			'blog_charset',
			'blog_public',
			'can_compress_scripts',
			'category_base',
			'close_comments_days_old',
			'close_comments_for_old_posts',
			'comment_max_links',
			'comment_moderation',
			'comment_order',
			'comment_registration',
			'comment_whitelist',
			'comments_notify',
			'comments_per_page',
			'cron',
			'db_upgraded',
			'db_version',
			'default_category',
			'default_comment_status',
			'default_comments_page',
			'default_email_category',
			'default_link_category',
			'default_ping_status',
			'default_pingback_flag',
			'default_post_format',
			'default_role',
			'finished_splitting_shared_terms',
			'gmt_offset',
			'hack_file',
			'home',
			'html_type',
			'image_default_align',
			'image_default_link_type',
			'image_default_size',
			'initial_db_version',
			'large_size_h',
			'large_size_w',
			'link_manager_enabled',
			'links_updated_date_format',
			'mailserver_login',
			'mailserver_pass',
			'mailserver_port',
			'mailserver_url',
			'medium_large_size_h',
			'medium_large_size_w',
			'medium_size_h',
			'medium_size_w',
			'moderation_keys',
			'moderation_notify',
			'nav_menu_options',
			'page_comments',
			'page_for_posts',
			'page_on_front',
			'permalink_structure',
			'ping_sites',
			'posts_per_page',
			'posts_per_rss',
			'recently_activated',
			'recently_edited',
			'require_name_email',
			'rewrite_rules',
			'rss_use_excerpt',
			'show_avatars',
			'show_on_front',
			'sidebars_widgets',
			'site_icon',
			'siteurl',
			'start_of_week',
			'sticky_posts',
			'stylesheet',
			'sublanguage_options',
			'sublanguage_translations',
			'tag_base',
			'template',
			'theme_mods_twentyfifteen',
			'thread_comments',
			'thread_comments_depth',
			'thumbnail_crop',
			'thumbnail_size_h',
			'thumbnail_size_w',
			'timezone_string',
			'uninstall_plugins',
			'upload_path',
			'upload_url_path',
			'uploads_use_yearmonth_folders',
			'use_balanceTags',
			'use_smilies',
			'use_trackback',
			'users_can_register',
			'wp_user_roles',
			'WPLANG'
		));
	
	}
	
	/** 
	 * Enqueue javascript and styles on option translation page
	 *
	 * @from 1.5
	 */	
	 public function admin_enqueue_scripts($hook) {
		
		if (strpos($hook, "_page_".$this->option_page_name) !== false) {
				
			wp_enqueue_style('sublanguage-options-style', plugin_dir_url( __FILE__ ) . 'js/options-style.css');
			wp_enqueue_script('sublanguage-options', plugin_dir_url( __FILE__ ) . 'js/options.js', array('sublanguage-ajax'), false, true);
			
		}
		
	}

	
	
	
	
	/* Add Editor Button
	----------------------------------------------- */
	
	/**
	 * Fire filters on post.php
	 *
	 * @hook 'load-post.php'
	 *
	 * @from 1.1
	 */
	public function load_editor_page() {	
		
		$current_screen = get_current_screen();
		
		if (isset($current_screen->post_type) && $this->is_post_type_translatable($current_screen->post_type) && isset($_GET['post'])) {
			
			add_action('admin_footer-post.php', array($this, 'print_javascript_post_translations'));
			
		}
		
	}

	/**
	 * @from 1.3
	 */
	public function load_editor_button() {
		
		$current_screen = get_current_screen();
		
		if (isset($current_screen->post_type) && $this->is_post_type_translatable($current_screen->post_type) && isset($_GET['post']) && current_user_can( 'edit_posts')) {

			add_filter('mce_buttons', array($this, 'register_tinymce_button'));
			add_filter('mce_external_plugins', array($this, 'add_tinymce_button'));

		}
		 
	}
	
	/**
	 * @from 1.3
	 */
	public function register_tinymce_button( $buttons ) {
	
		 array_push( $buttons, "sublanguage");
		 
		 return $buttons;
		 
	}

	/**
	 * @from 1.3
	 */
	public function add_tinymce_button( $plugin_array ) {
	
		 $plugin_array['sublanguage'] = plugins_url('js/editor-btn.js', __FILE__);
		 
		 return $plugin_array;
	}

	/**
	 * Print post translations data for javascript
	 *
	 * @hook 'admin_footer-{...}'
	 *
	 * @from 1.3
	 */
	public function print_javascript_post_translations() {
		
		if (isset($_GET['post'])) {
		
			$post_id = intval($_GET['post']);
			$post = get_post($post_id);
			$screen = get_current_screen();
			
			if ($post) {
		
				$languages = $this->get_languages();
				$data = array();
				
				$hidden_meta_boxes = get_hidden_meta_boxes( $screen );
		
				foreach ($languages as $language) {
			
					$translation_data = array(
						'lid' => $language->ID,
						'l' => $language->post_title,
						'ls' => $language->post_name,
						'id' => $post_id,
						't' => $this->translate_post_field($post, 'post_title', $language, ''),
						'n' => $this->translate_post_field($post, 'post_name', $language, ''),
						'c' => $this->translate_post_field($post, 'post_content', $language, ''),
					);
			
					if (isset($screen->post_type) && post_type_supports($screen->post_type, 'excerpt') && !in_array('postexcerpt', $hidden_meta_boxes)) {
			
						$translation_data['e'] = $this->translate_post_field($post, 'post_excerpt', $language, '');
		
					}
			
					$data[] = $translation_data;
			
				}
		
				include plugin_dir_path( __FILE__ ) . 'include/editor-button-script.php';
		
			}
		
		}
	
	}

	/**
	 * Use button with Tinymce Advanced plugin
	 *
	 * Hook for 'tadv_allowed_buttons'
	 *
	 * @from 1.3
	 */
	public function tadv_register_button($buttons) {
	
		$buttons['sublanguage'] = 'Translation';
		
		return $buttons;
		
	}

	/**
	 * Use button with Tinymce Advanced plugin
	 *
	 * Hook for 'admin_head'
	 *
	 * @from 1.3
	 */
	public function tadv_set_icon() {

?>
<style>
	.mce-i-sublanguage:before {
		content: "\f326";
		font-family: "dashicons";
	}
  </style>
<?php

	}
	
	
	
	
	
}


