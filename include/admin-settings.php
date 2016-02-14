<?php 

class Sublanguage_settings {
	
	/**
	 *	@from 1.5
	 */
	private $page_name = 'settings';
	
	/**
	 *	@from 1.0
	 */
	public function __construct() {
		global $sublanguage_admin;
		
		add_action('admin_menu', array($this, 'admin_menu'), 5);
		add_action('admin_init', array($this, 'admin_init') );
		
		add_action('update_option_' . $sublanguage_admin->option_name, array($this, 'update_option'), 10, 3);
		
	}
	
	/**
	 *	@from 1.0
	 */
	public function admin_menu() {
		global $sublanguage_admin;
		
		add_menu_page(
			__('Sublanguage Settings', 'sublanguage'), 
			__('Sublanguage', 'sublanguage'),
			'manage_options',
			$sublanguage_admin->page_name,
			array($this, 'options_page'),
			'dashicons-translation', 
			100 );
			
	}
	
	/**
	 *	@from 1.0
	 */
	public function admin_init() {
		global $sublanguage_admin;
		
    	register_setting( $sublanguage_admin->page_name , $sublanguage_admin->option_name, array($this, 'sanitize_settings') );
    	
		add_settings_section( 
			'section-settings', 
			__('Sublanguage Settings', 'sublanguage'), 
			array($this, 'section_settings'), 
			$sublanguage_admin->page_name
		);
		
		add_settings_field(	
			'translate-cpt', 
			__('Translate post types', 'sublanguage'), 
			array($this, 'field_translate_cpt'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);
		
		add_settings_field(	
			'translate-taxonomies', 
			__('Translate Taxonomies', 'sublanguage'), 
			array($this, 'field_translate_taxonomies'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);
		
		add_settings_field(	
			'main-language', 
			__('Original language', 'sublanguage'), 
			array($this, 'field_main_language'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);
		
		add_settings_field(	
			'default-language', 
			__('Default language', 'sublanguage'), 
			array($this, 'field_default_language'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);

		add_settings_field(	
			'show-slug', 
			__('Show slug for main language', 'sublanguage'), 
			array($this, 'field_show_slug'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);
		
		add_settings_field(	
			'autodetect-language', 
			__('Auto-detect language', 'sublanguage'), 
			array($this, 'field_autodetect_language'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);
		
		add_settings_field(	
			'current-first', 
			__('Current language first', 'sublanguage'), 
			array($this, 'field_current_first_language'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);
		
		add_settings_field(	
			'ajax-post-admin', 
			__('Ajax Post Admin Interface', 'sublanguage'), 
			array($this, 'field_ajax_post_admin'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);
		
		add_settings_field(	
			'translate-meta', 
			__('Translate Meta', 'sublanguage'), 
			array($this, 'field_translate_meta'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);
		
		add_settings_field(	
			'sublanguage-version', 
			__('Version', 'sublanguage'), 
			array($this, 'field_version'), 
			$sublanguage_admin->page_name, 
			'section-settings'
		);
		
	}
	
	/**
	 *	@from 1.0
	 */
	public function section_settings() {				
		
	}

	/**
	 *	@from 1.3
	 */
	function field_version($args) {
		global $sublanguage_admin;
      	
      	echo '<input type="hidden" name="'.$sublanguage_admin->option_name.'[version]" value="'.$sublanguage_admin->get_option('version').'"/>';
		echo '<p>'.$sublanguage_admin->get_option('version').'</p>';
		
	}
		
	/**
	 *	@from 1.0
	 */
	function field_translate_taxonomies($args) {
		global $sublanguage_admin;
        
		$taxonomies = get_taxonomies(array(
			'show_ui' => true
		), 'objects');
		
		if (isset($taxonomies)) {
		
			foreach ($taxonomies as $taxonomy) {
				
				$checked = in_array($taxonomy->name, $sublanguage_admin->get_taxonomies()) ? ' checked' : '';
				
				echo '<input type="checkbox" name="'.$sublanguage_admin->option_name.'[taxonomy][]" value="'.$taxonomy->name.'" id="'.$sublanguage_admin->option_name.'-taxi-'.$taxonomy->name.'"'.$checked.'/>
					<label for="'.$sublanguage_admin->option_name.'-taxi-'.$taxonomy->name.'">'.(isset($taxonomy->labels->name) ? $taxonomy->labels->name : $taxonomy->name).'</label><br/>';
				
			}
			
		}
		
		echo '<p>'.sprintf(__('You can set taxonomy translations in %s permalink section', 'sublanguage'), '<a href="'.admin_url('options-permalink.php').'">').'</a></p>';
		
	}
	
	/**
	 *	@from 1.0
	 */
	function field_translate_cpt($args) {
		global $sublanguage_admin;
       
		$cpts = get_post_types(array(
			//'show_ui' => true,
			//'public' => true,
		), 'objects' );
		
		if (isset($cpts)) {
		
			foreach ($cpts as $post_type) {
				
				if ($post_type->name === 'revision' || $post_type->name === 'language' || $sublanguage_admin->get_language_by_type($post_type->name)) {
				
					continue;
				
				}
				
				$checked = in_array($post_type->name, $sublanguage_admin->get_post_types()) ? ' checked' : '';
				
				echo '<input type="checkbox" id="'.$sublanguage_admin->option_name.'-cpt-'.$post_type->name.'" name="'.$sublanguage_admin->option_name.'[cpt][]" value="'.$post_type->name.'" '.$checked.'/>
					<label for="'.$sublanguage_admin->option_name.'-cpt-'.$post_type->name.'">'.(isset($post_type->labels->name) ? $post_type->labels->name : $post_type->name).'</label><br/>';
				
			}
			
		}
		
		echo '<p>'.sprintf(__('You can set post-type translations in %s permalink section', 'sublanguage'), '<a href="'.admin_url('options-permalink.php').'">').'</a></p>';
		
	}
	
	/**
	 *	@from 1.1
	 */
	function field_main_language($args) {
		global $sublanguage_admin;
    
		$languages = $sublanguage_admin->get_languages();
   		
   		$html = '';
   		
   		if ($languages) {
   		
			$html .= sprintf('<label><select name="%s[main]">', $sublanguage_admin->option_name);
		
			foreach ($languages as $lng) {
		
				$html .= sprintf('<option value="%d"%s>%s</option>',
					$lng->ID,
					($sublanguage_admin->is_main($lng->ID)) ? ' selected' : '',
					$lng->post_title);
			
			}
		
			$html .= '</select> ';
			$html .= __('This is the langage that will be used if a translation is missing for a post.', 'sublanguage').'</label>';
			
		} 
		
		$html .= ' <a href="'.admin_url('edit.php?post_type='.$sublanguage_admin->language_post_type).'">'.__('Add language', 'sublanguage').'</a>';
		
		echo $html;
		
	}	
	
	/**
	 *	@from 1.1
	 */
	function field_default_language($args) {
		global $sublanguage_admin;
    
		$languages = $sublanguage_admin->get_languages();
   		
   		$html = '';
   		
   		if ($languages) {
   		
			$html .= '<label><select name="'.$sublanguage_admin->option_name.'[default]">';
		
			foreach ($languages as $lng) {
		
				$html .= sprintf('<option value="%d"%s>%s</option>',
					$lng->ID,
					($sublanguage_admin->is_default($lng->ID)) ? ' selected' : '',
					$lng->post_title);
			
			}
		
			$html .= '</select> ';
			$html .= __('This is the langage visitors will see when language is not specified in url.', 'sublanguage').'</label>';
		
		}
		
		echo $html;
		
	}	

	/**
	 *	@from 1.1
	 */
	function field_show_slug($args) {
		global $sublanguage_admin;
       
		echo sprintf('<label><input type="checkbox" name="%s" value="1"%s/>%s</label>', 
			$sublanguage_admin->option_name.'[show_slug]',
			$sublanguage_admin->get_option('show_slug') ? ' checked' : '',
			__('Show language slug for main language in site url', 'sublanguage')
		);
		
	}
	
	/**
	 *	@from 1.1
	 */
	function field_show_edit_language($args) {
		global $sublanguage_admin;
       
		echo sprintf('<label><input type="checkbox" name="%s" value="1"%s/>%s</label>', 
    	$sublanguage_admin->option_name.'[show_edit_lng]',
			$sublanguage_admin->get_option('show_edit_lng') ? ' checked' : '',
			__('Display current language in content and title fields when editing post.', 'sublanguage')
		);
		
	}

	/**
	 *	@from 1.2
	 */
	function field_autodetect_language($args) {
		global $sublanguage_admin;
       
		echo sprintf('<label><input type="checkbox" name="%s" value="1"%s/>%s</label>', 
			$sublanguage_admin->option_name.'[autodetect]',
			$sublanguage_admin->get_option('autodetect') ? ' checked' : '',
			__('Auto-detect language when language is not specified in url.', 'sublanguage')
		);
		
	}
	
	/**
	 *	@from 1.2
	 */
	function field_current_first_language($args) {
		global $sublanguage_admin;
    	   
		echo sprintf('<label><input type="checkbox" name="%s" value="1"%s/>%s</label>', 
			$sublanguage_admin->option_name.'[current_first]',
			$sublanguage_admin->get_option('current_first') ? ' checked' : '',
			__('Set the current language to be the first in the language selectors.', 'sublanguage')
		);
		
	}
	
	/**
	 *	@from 1.5
	 */
	function field_ajax_post_admin($args) {
		global $sublanguage_admin;
       
		echo sprintf('<label><input type="checkbox" name="%s" value="1"%s/>%s</label>', 
			$sublanguage_admin->option_name.'[ajax_post_admin]',
			$sublanguage_admin->get_option('ajax_post_admin') ? ' checked' : '',
			__('BETA FEATURE. This is not fully functional yet.', 'sublanguage')
		);
		
	}
	
	/**
	 *	@from 1.5
	 */
	public function field_translate_meta($args) {
		global $wpdb, $sublanguage_admin;
		
		$post_types = $sublanguage_admin->get_post_types();
		$results = array();
		
		if ($post_types) {
		
			$sql_post_types = implode("', '", array_map('esc_sql', wp_unslash($post_types)));
			$sql_blacklist = implode("', '", array_map('esc_sql', $this->get_meta_keys_blacklist()));
			
			$results = $wpdb->get_results( "
				SELECT meta.meta_key, meta.meta_value
				FROM $wpdb->postmeta AS meta
				LEFT JOIN $wpdb->posts AS post ON (post.ID = meta.post_id)
				WHERE post.post_type IN ('$sql_post_types') AND meta.meta_key NOT IN ('$sql_blacklist')" 
			);
			
		}
		
		$sorted_meta_keys = array();
		
		foreach ($results as $row) {
			
			$sorted_meta_keys[$row->meta_key][] = substr(wp_strip_all_tags($row->meta_value, true), 0, 120);
		
		}
		
		$meta_keys = $sublanguage_admin->get_option('meta_keys', $sublanguage_admin->get_postmeta_keys());
		
		foreach ($meta_keys as $meta_key) {
			
			if (!isset($sorted_meta_keys[$meta_key])) {
				
				$sorted_meta_keys[$meta_key] = array();
			
			}
		
		}
		
		ksort($sorted_meta_keys);
		
		foreach ($sorted_meta_keys as $key => $values) {
			
			$checked = in_array($key, $meta_keys) ? ' checked' : '';
			
			$values = array_unique(array_filter($values));
			
			$sliced_values = array_slice($values, 0, 5);
			
			if (count($values) > count($sliced_values) && count($sliced_values) > 0) {
				
				$sliced_values[] = '...';
				
			}
			
			if (empty($sliced_values)) {
				
				$sliced_values[] = 'no value';
				
			}
			
			echo '<label title="Values sample: '.implode(', ', $sliced_values).'"><input type="checkbox" name="'.$sublanguage_admin->option_name.'[meta_keys][]" value="'.$key.'" '.$checked.'/>'.$key.'</label><br/>';
		
		}
		
		echo '<p>'.__('Custom Fields metabox is not supported for handling translation.', 'sublanguage').'</p>';
		
	}
	
	/**
	 *	@from 1.5
	 */
	public function options_page() {
		global $sublanguage_admin;
		
		echo '<form action="options.php" method="POST">';
		echo settings_fields($sublanguage_admin->page_name);
		echo do_settings_sections($sublanguage_admin->page_name);
		echo submit_button();
		echo '</form>';
		
	}
	

	/**
	 *	@from 1.0
	 */
	public function sanitize_settings($input) {
		
		$output = array();
		$output['cpt'] = isset($input['cpt']) ? array_map('esc_attr', $input['cpt']) : array();
		$output['taxonomy'] = isset($input['taxonomy']) ? array_map('esc_attr', $input['taxonomy']) : array();
		$output['meta_keys'] = isset($input['meta_keys']) ? array_map('esc_attr', $input['meta_keys']) : array();
		$output['show_slug'] = (isset($input['show_slug']) && $input['show_slug']);
		$output['main'] = isset($input['main']) && $input['main'] ? $input['main'] : 0;
		$output['default'] = isset($input['default']) && $input['default'] ? $input['default'] : 0;
		$output['autodetect'] = (isset($input['autodetect']) && $input['autodetect']);
		$output['current_first'] = (isset($input['current_first']) && $input['current_first']);
		$output['version'] = isset($input['version']) ? esc_attr($input['version']) : '-';
		$output['ajax_post_admin'] = isset($input['ajax_post_admin']) && $input['ajax_post_admin'] ? 1 : 0;
		
    	return $output;
	}
	

	
	/*
	 * List of meta keys that should never be translated
	 * 
	 * @from 1.5
	 */
	private function get_meta_keys_blacklist() {
	
		return apply_filters('sublanguage_meta_keys_blacklist', array(
			'_wp_attached_file',
			'_wp_attachment_metadata',
			'_edit_lock',
			'_edit_last',
			'_wp_page_template'
		));
		
	}
	
	/*
	 * Register/Unregister post meta when specific post_type is updated
	 * 
	 * Hook for "update_option_{$option}"
	 *
	 * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     * @param string $option    Option name.
     *
	 * @from 1.5
	 */
	public function update_option($old_value, $value, $option) {
		
		if (!isset($old_value['cpt'])) {
			
			$old_value['cpt'] = array();
		
		}
		
		if (!isset($value['cpt'])) {
			
			$value['cpt'] = array();
		
		}
		
		if ($value['cpt'] != $old_value['cpt']) {
			
			$added = array_diff($value['cpt'], $old_value['cpt']);
			$removed = array_diff($old_value['cpt'], $value['cpt']);
			$new_value = $value;
			
			if (in_array('attachment', $added)) {
				
				$new_value['meta_keys'][] = '_wp_attachment_image_alt';
				
			}
			
			if (in_array('attachment', $removed) && isset($new_value['meta_keys'])) {
				
				$this->remove_val('_wp_attachment_image_alt', $new_value['meta_keys']);
				
			}
			
			if (in_array('nav_menu_item', $added)) {
				
				$new_value['meta_keys'][] = '_menu_item_url';
				$new_value['meta_keys'][] = 'sublanguage_hide';
				
			}
			
			if (in_array('nav_menu_item', $removed) && isset($new_value['meta_keys'])) {
				
				$this->remove_val('_menu_item_url', $new_value['meta_keys']);
				$this->remove_val('sublanguage_hide', $new_value['meta_keys']);
				
			}
			
			if ($new_value != $value) {
				
				update_option( $option, $new_value );
			
			}
			
		}
		
		
	}
	
	/*
	 * Remove value from array
	 *
	 * @from 1.5
	 */
	public function remove_val($value, $array) {
		
		$key = array_search($value, $array);
		
		if ($key !== false) {
			
			unset($array[$key]);
			
		}
		
		return $array;
	}

}


