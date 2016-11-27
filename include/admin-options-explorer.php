<?php 

/**
 * Explore wordpress options
 *
 * @from 1.5
 */
class Sublanguage_options_explorer {
	
	var $page_name = 'translate_options';
	
	/**
	 * @from 1.5
	 */
	public function __construct() {
		global $sublanguage_admin;
		
		// register CSS and JS for this page
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		
		// register ajax hooks
		add_action( 'wp_ajax_sublanguage_export_options', array($this, 'export_options') );
		add_action( 'wp_ajax_sublanguage_option_translations', array($this, 'get_option_translations') );
		add_action( 'wp_ajax_sublanguage_set_option_translation', array($this, 'set_option_translation') );
		
		// add page to menu
		add_action('admin_menu', array($this, 'admin_menu'));
		
	}
	
	
	/**
	 * Add sublanguage subpage for options translation
	 *
	 * hook for 'admin_menu'
	 *
	 * @from 1.5
	 */
	public function admin_menu() {
		global $sublanguage_admin;
		
		add_submenu_page (
			$sublanguage_admin->page_name,
			'Translate Options',
			'Translate Options',
			'manage_options',
			$this->page_name,
			array($this, 'print_page') 
		);
		
	}
	
	
	/*
	 * Renders Translate Options page
	 * 
	 * @from 1.5
	 */
	public function print_page() {
		global $wpdb, $sublanguage_admin;
		
		$sql_blacklist = "option_name NOT IN ('" . implode("', '", array_map('esc_sql', $this->get_options_blacklist())) . "')";
		
		$options = $wpdb->get_results( "
			SELECT option_name, option_value
			FROM $wpdb->options
			WHERE option_name NOT LIKE '_transient%' AND option_name NOT LIKE '_site_transient%' AND $sql_blacklist
			ORDER BY option_name" 
		);
		
		// 
		
		echo '<h1>'.__('Translate Options', 'sublanguage').'</h1>';
		
		echo '<ul class="sublanguage-options">';

		foreach ($options as $option) {
	
			$disabled = false;

			if ( $option->option_name == '' ) {

				continue;

			}

			if (is_serialized($option->option_value) && !is_serialized_string($option->option_value)) {

				$value = 'DATA';
				$disabled = true;
	
			} else {

				$value = $option->option_value;
	
			}

			$name = esc_attr( $option->option_name );
			
			echo '<li><a href="#">';
			echo '<span class="handle dashicons dashicons-arrow-right"></span>';
			echo '<label for="'.$name.'">'.esc_html( $option->option_name ).'</label>';
			echo '<input class="regular-text all-options" type="text" name="sublanguage_translate_options['.$name.']" id="'.$name.'" value="'.esc_attr( $value ).'" readonly="readonly" />';
			echo '</a></li>';
			
		}
			
		echo '</ul>';
		
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
	 * @from 1.5
	 *
	 * Enqueue javascript and styles on options.php
	 */	
	 public function admin_enqueue_scripts($hook) {
		global $sublanguage_admin;
		
		if ($hook == 'sublanguage_page_' . $this->page_name) {
						
			wp_enqueue_style('sublanguage-options-style', plugin_dir_url( __FILE__ ) . 'js/options-style.css');
			wp_enqueue_script('sublanguage-options', plugin_dir_url( __FILE__ ) . 'js/options.js', array('sublanguage-ajax'), false, true);
			
		}
		
	}
	
	/**
	 * Ajax route to fetch options
	 *
	 * @from 1.5
	 */	
	public function export_options() {
		global $wpdb;
		
		$options = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options ORDER BY option_name" );
		
		$options = array_reduce($options, array($this, 'unserialize_option'), array());
		
		echo json_encode($options);
		
		wp_die();
		
	}
	
	/**
	 * Unserialize options. Callback for array_reduce
	 *
	 * @from 1.5
	 */	
	public function unserialize_option($result, $option) {
		
		if ( $option->option_name != '' ) {
			
			$result[$option->option_name] = maybe_unserialize( $option->option_value );
			
		}
		
		return $result;
	}
	
	
	/**
	 * Save "sub" options
	 *
	 * hook for 'load-options.php'
	 *
	 * @from 1.5
	 */	
	public function options_page() {
		
		if (isset($_POST['option_explorer'])) {
			
			$options = $_POST['option_explorer'];
			
			foreach ($options as $name => $option) {
				
				$original = get_option($name);
				
				$option = array_replace_recursive($original, $option);
				
				$option = $this->map_deep($option, array($this, 'format_option'));
				
				update_option($name, $option);
				
			}
			
		}
		
	}
	
	/**
	 * Format option
	 *
	 * @from 1.5.3 add stripslashes
	 * @from 1.5.2 remove default html escaping
	 * @from 1.5
	 */	
	public function format_option($value) {
		
		$value = stripslashes(trim($value));
		
		switch ($value) {
			
			case 'false':
				return false;
				
			case 'true':
				return true;
			
		}
		
		return $value;
	}
	
	/**
	 * Map deep. Copied from wp-includes/formatting.php
	 *
	 * @from 1.5
	 */	
	private function map_deep( $value, $callback ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			foreach ( $value as &$item ) {
				$item = $this->map_deep( $item, $callback );
			}
			return $value;
		} else {
			return call_user_func( $callback, $value );
		}
	}
	
	
	
	/**
	 * Get option translations for ajax
	 *
	 * @from 1.5
	 */	
	public function get_option_translations() {
		global $sublanguage_admin;
		
		echo json_encode($sublanguage_admin->get_option_translations());
		
		wp_die();
	
	}
	
	/**
	 * Set option translation for ajax
	 *
	 * @from 1.5
	 */	
	public function set_option_translation() {
		
		if (isset($_POST['sublanguage_option_translation'])) {
			
			$option_tree = $this->map_deep($_POST['sublanguage_option_translation'], array($this, 'format_option'));
			
			$this->update_option_translations($option_tree);
			
		}
		
		wp_die();
	
	}	
	
	/**
	 * Update option translations
	 *
	 * @from 1.5
	 *
	 * @return array
	 */
	public function update_option_translations($option_tree) {
		global $sublanguage_admin;
		
		$translations = get_option($sublanguage_admin->translation_option_name);
		
		if (empty($translations['option'])) {

			$translations['option'] = array();

		}
		
		$translations['option'] = array_replace_recursive($translations['option'], $option_tree);
		
		// clean array
		$translations['option'] = $this->clean_translations($translations['option']);
		
		update_option($sublanguage_admin->translation_option_name, $translations);
				
	}
	
	/**
	 * Clean array deep. Callback for array_reduce
	 *
	 * @from 1.5
	 */	
	private function clean_translations($node) {
		
		if (is_array($node)) {
			
			$clean_node = array();
			
			foreach ($node as $key => $child) {
				
				$child = $this->clean_translations($child);
				
				if ($child !== '' && !(is_array($child) && !$child)) {
					
					$clean_node[$key] = $child;
				
				}
				
			}
			
			return $clean_node;
		}
		
		return $node;
	}


	
}
