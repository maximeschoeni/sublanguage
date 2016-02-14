<?php 

class Sublanguage_languages {

	
	/**
	 * @from 1.0
	 */
	public function __construct() {
		
		add_action('init', array($this, 'init'));
		
		add_filter('themes_update_check_locales', array($this, 'check_update'));
		add_filter('plugins_update_check_locales', array($this, 'check_update'));
			
	}
	
	/**
	 *	@from 1.0
	 */
	public function init() {
		global $sublanguage_admin;
		
		// save post meta box - create translation if not exist
		add_filter('wp_insert_post_data', array($this, 'set_default_slug_and_name'), null, 2);
		add_action('save_post', array($this, 'save_meta_box'), 10, 2);
		//add_action('save_post', array($this, 'sort_language_option'), 11, 2);
				
		// sort languages by menu_order in language list (no default order because it is not hierarchical)
		add_filter( 'parse_query', array($this, 'sort_languages' ));
		
		// never hide the slug meta box
		add_filter('default_hidden_meta_boxes', array($this, 'hide_settings'), null, 2);
	
	
		// delete all translation when deleting language post
		add_action('before_delete_post', array($this, 'delete_post'));
		
		// Remove title, slug and attributes on new post
		add_action('load-post-new.php', array($this, 'new_language_post'));
		
		// add meta-box for locale and rtl
		add_action('load-post.php', array($this, 'edit_language_post'));
	
		register_post_type($sublanguage_admin->language_post_type, array(
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
			'show_in_menu'       => $sublanguage_admin->page_name,
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
			'menu_icon'			 => 'dashicons-translation'
		));
	
	
		
	}
	
		
	/**
	 * Ask to get all registered languages when upgrading (instead of just admin language)
	 *
	 * Filter for 'themes_update_check_locales', 'plugins_update_check_locales'
	 *
	 * @from 1.1
	 */	
	public function check_update($locales) {
		global $sublanguage_admin;
		
		return $sublanguage_admin->get_language_column('post_content');
			
	}
	
	/**
	 * Print language locale input meta-box
	 *
	 * @from 1.0
	 */
	public function locale_meta_box_callback( $post ) {
		global $sublanguage_admin;
		
		wp_nonce_field( 'language_locale_action', 'language_locale_nonce' );
		
		echo '<input type="text" name="language_locale" value="'.$post->post_content.'"/>';
		
	}
	
	/**
	 * Print language locale dropdown meta-box
	 *
	 * @from 1.0
	 */
	public function locale_dropdown_meta_box_callback( $post ) {
		global $sublanguage_admin;
		
		wp_nonce_field( 'language_locale_dropdown_action', 'language_locale_dropdown_nonce' );
		
		wp_dropdown_languages(array(
			'selected' => '', 
			'languages' => array_filter($sublanguage_admin->get_language_column('post_content')),
			'name' => 'language_locale_dropdown',
			'id' => 'language_locale_dropdown',
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
		global $sublanguage_admin;
		
		$current_screen = get_current_screen();
		
		if (isset($current_screen->post_type) && $current_screen->post_type == $sublanguage_admin->language_post_type) {
			
			global $_wp_post_type_features;
			
			remove_post_type_support($current_screen->post_type, 'title');
			remove_post_type_support($current_screen->post_type, 'slug'); // does not work!
			
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
		global $sublanguage_admin;
		
		$current_screen = get_current_screen();
		
		if (isset($current_screen->post_type) && $current_screen->post_type == $sublanguage_admin->language_post_type) {
			
			add_meta_box(
				'languages_local',
				__( 'Locale', 'sublanguage' ),
				array($this, 'locale_meta_box_callback'),
				'language',
				'normal',
				'default'
			);
		
			add_meta_box(
				'languages_settings',
				__( 'Settings', 'sublanguage' ),
				array($this, 'settings_meta_box_callback'),
				'language',
				'normal',
				'default'
			);
			
		}
	
	}
	
	/**
	 * Print meta-box
	 *
	 * @from 1.0
	 */
	public function settings_meta_box_callback( $post ) {

		wp_nonce_field( 'language_settings_action', 'language_settings_nonce' );
		
		$is_rtl = get_post_meta($post->ID, 'rtl', true);
				
		echo '<input type="checkbox" id="language_rtl" name="language_rtl" value="1"'.($is_rtl ? ' checked' : '').'
					<label for="language_rtl">'.__('Right-to-left', 'sublanguage').'</label>';
		
	}
	
	/**
	 * Set default language title and slug
	 * Filter for 'wp_insert_post_data'
	 *
	 * @from 1.0
	 */
	public function set_default_slug_and_name($data, $postarr) {
		global $sublanguage_admin;
		
		if ($data['post_type'] == $sublanguage_admin->language_post_type) {
			
			if (isset($_POST['language_locale_dropdown'])) {
				
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
	 * Hook for 'save_post'
	 *
	 * @from 1.0
	 */
	public function save_meta_box( $post_id, $post ) {
		global $sublanguage_admin;
		
		if ($post->post_type == $sublanguage_admin->language_post_type) {
		
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
					
					flush_rewrite_rules();
					
				}
				
				
				// save language settings
				
				if (isset($_POST['language_settings_nonce']) 
					&& wp_verify_nonce($_POST['language_settings_nonce'], 'language_settings_action')) {
					
					if (isset($_POST['language_rtl']) && $_POST['language_rtl']) {
						
						update_post_meta($post->ID, 'rtl', 1);
						
					}
					
				}
				
				// verify settings -> added in 1.4.7
				
				if ($post->post_status == "trash" && $sublanguage_admin->is_main($post_id)) {
					
					$options = get_option($sublanguage_admin->option_name);
					
					$next_language = $this->get_valid_language($post_id);
					
					$options['main'] = $next_language ? $next_language->ID : false;
					
					update_option($sublanguage_admin->option_name, $options);
					
				} else if (!$sublanguage_admin->get_option('main')) {
					
					$options = get_option($sublanguage_admin->option_name);
			
					$options['main'] = $post_id;
			
					update_option($sublanguage_admin->option_name, $options);
				
				}
								
			}
			
		}
		
	}
	

	/**
	 * delete all translations when deleting a language post
	 * Hook for 'before_delete_post'
	 *
	 * @from 1.1
	 */
	public function delete_post($language_id) {
		global $wpdb, $sublanguage_admin;
		
		$language = get_post($language_id);
		
		if ($language->post_type === $sublanguage_admin->language_post_type) {
			
			$translations = get_option($sublanguage_admin->translation_option_name);
			
			if (isset($translations['taxonomy'][$language_id])) {
			
				unset($translations['taxonomy'][$language_id]);
				
			}
			
			if (isset($translations['cpt'][$language_id])) {
			
				unset($translations['cpt'][$language_id]);
				
			}
			
			update_option($sublanguage_admin->translation_option_name, $translations);
						
			$translation_ids = $wpdb->get_col($wpdb->prepare(
				"SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE post_type=%s",
				$sublanguage_admin->post_translation_prefix.$language_id
			));
			
			if ($translation_ids) {
			
				foreach ($translation_ids as $id) {
				
					wp_delete_post($id, true);
			
				}
			
			}
			
			$term_ids = $wpdb->get_col($wpdb->prepare(
				"SELECT t.term_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s",
				$sublanguage_admin->term_translation_prefix.$language_id
			));
			
			foreach ($term_ids as $term_id) {
				
				wp_delete_term($term_id, $sublanguage_admin->term_translation_prefix.$language_id);
			
			}
			
		}
	
	}
	
	/**
	 * Order posts by menu_order
	 * Hook for 'parse_query'
	 *
	 * @from 1.0
	 */
	public function sort_languages($query) {
		
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
	 * Hook for 'default_hidden_meta_boxes'
	 *
	 * @from 1.0
	 */
	public function hide_settings($hidden, $screen) {
		
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
	public function get_valid_language($except_post_id) {
		global $sublanguage_admin;
		
		foreach ($sublanguage_admin->get_languages() as $language) {
			
			if ($language->post_status != 'trash' && $language->ID != $except_post_id) return $language;
		
		}
		
		return false;
	}
	
	
}


