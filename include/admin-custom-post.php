<?php

class Sublanguage_custom_post {
	
	private $post_types = array();
	
	
	public function __construct() {
		
		add_action('init', array($this, 'init'), 20);
		
	}
	
	/**
	 * Init
	 */
	public function init() {
		global $sublanguage_admin;
		
		$cpts = get_post_types(array(
			'show_ui' => false
		), 'objects' );
		
		foreach ($cpts as $cpt) {
			
			if ($cpt->name !== 'nav_menu_item' && in_array($cpt->name, $sublanguage_admin->get_post_types())) {
				
				$this->register_post_type($cpt->name);
				
				$this->post_types[] = $cpt->name;
				
				add_action('save_post_' . $cpt->name, array($this, 'save'), 10, 2);
			
			}
		
		}
		
		if (!empty($this->post_types)) {
		
			add_filter('wp_insert_post_data', array($this, 'insert_post_data'), 12, 2);
			
		}
		
		add_action('admin_init', array($this, 'admin_init'));
		
	}

	/**
	 * Re-register post types not showing in admin
	 *
	 * @from 1.5
	 */
	public function register_post_type($post_type) {
		global $sublanguage_admin;
		
		register_post_type($post_type, array(
			'labels'             => array(
				'name'               => $post_type,
				'singular_name'      => $post_type
			),
			'show_ui'            => true,
			'show_in_menu'       => $sublanguage_admin->page_name
		));
		
		remove_post_type_support( $post_type, 'editor');
		
	}
	
	/**
	 * Add metabox for post_name, post_content, post_excerpt and custom fields
	 *
	 * Hook for 'admin_init'
	 *
	 * @from 1.5
	 */
	public function admin_init() {
		
		add_meta_box(
			'sublanguage-cpt-translation',
			__('Parameters', 'sublanguage'),
			array( $this, 'render_meta_box' ),
			$this->post_types,
			'normal'
		);
		
	}
	
	/**
	 * Render metabox
	 *
	 * Callback for add_meta_box()
	 *
	 * @from 1.5
	 */
	public function render_meta_box($post) {
		global $sublanguage_admin, $wpdb;
		
		$sql_meta_keys = implode("', '", array_map('esc_sql', $sublanguage_admin->get_postmeta_keys()));
		
		$results = $wpdb->get_results( $wpdb->prepare( "
			SELECT DISTINCT meta.meta_key
			FROM $wpdb->postmeta AS meta
			LEFT JOIN $wpdb->posts AS post ON (post.ID = meta.post_id)
			WHERE post.post_type = %s AND meta.meta_key IN ('$sql_meta_keys')",
			$post->post_type
		));
		
		$meta_fields = array();
		
	
		$meta_fields[] = array(
			'key' => 'content',
			'name' => 'content',
			'value' => apply_filters( 'sublanguage_translate_post_field', $post->post_content, $post, '' ),
			'placeholder' => $sublanguage_admin->is_sub() ? get_post($post->ID)->post_content : '',
			'field' => 'textarea'
		);
		
		$meta_fields[] = array(
			'key' => 'excerpt',
			'name' => 'excerpt',
			'value' => apply_filters( 'sublanguage_translate_post_field', $post->post_excerpt, $post, '' ),
			'placeholder' => $sublanguage_admin->is_sub() ? get_post($post->ID)->post_excerpt : '',
			'field' => 'textarea'
		);
		
		foreach ($results as $result) {
		
			$key = $result->meta_key;
			$values = get_post_meta($post->ID, $key);
			$original_values = array();
			
			if ($sublanguage_admin->is_sub()) {
				
				$sublanguage_admin->disable_postmeta_filter = true;
				$original_values = get_post_meta($post->ID, $key);
				$sublanguage_admin->disable_postmeta_filter = false;
				
			}
			
			$len = max(count($values), count($original_values), 1);
			
			for ($i = 0; $i < $len; $i++) {
			
				$meta_fields[] = array(
					'key' => $key,
					'name' => 'sublanguage_cpt[' . $key . ']',
					'value' => isset($values[$i]) ? $values[$i] : '',
					'placeholder' => isset($original_values[$i]) ? $original_values[$i] : '',
					'field' => 'intput'
				);
			
			}
			
		}
		
		wp_nonce_field( 'sublanguage_translate_cpt_action', 'sublanguage_translate_cpt_nonce', false, true );
		
		echo '<table style="width:100%">';
		echo '<colgroup><col style="width:25%"><col style="width:75%"></colgroup>';
		echo '<tbody>';
		
		foreach ($meta_fields as $meta_field) {
			
			echo '<tr><td><label for="sublanguage-' . $meta_field['key'] . '">' . $meta_field['key'] . '</label></td><td>';
			
			if ($meta_field['field'] === 'textarea') {
			
				echo '<textarea  style="width:100%;box-sizing:border-box;" id="sublanguage-' . $meta_field['key'] . '" type="text" name="' . $meta_field['name'] . '" placeholder="' . esc_html($meta_field['placeholder']) . '">' . esc_html($meta_field['value']) . '</textarea>';
			
			} else {
			
				echo '<input  style="width:100%;box-sizing:border-box;" id="sublanguage-' . $meta_field['key'] . '" type="text" name="' . $meta_field['name'] . '" value="' . esc_html($meta_field['value']) . '" placeholder="' . esc_html($meta_field['placeholder']) . '"/>';
			
			}
			
			echo '</td></tr>';
			
		}

		echo '</tbody>';
		echo '</table>';
	
	}
	
	/**
	 * unescape html for content and excerpt
	 *
	 * Filter for 'wp_insert_post_data'
	 *
	 * @from 1.5
	 */
	public function insert_post_data($data, $postarr) {
		global $sublanguage_admin;
		
		if (isset($this->post_types) && in_array($data['post_type'], $this->post_types)) {
			
			// I think this is actually not really needed!
			
			if (isset($sublanguage_admin->sublanguage_data['post_content'])) {
				
				$sublanguage_admin->sublanguage_data['post_content'] = html_entity_decode($sublanguage_admin->sublanguage_data['post_content']);
			
			}
			
			if (isset($sublanguage_admin->sublanguage_data['post_excerpt'])) {
				
				$sublanguage_admin->sublanguage_data['post_excerpt'] = html_entity_decode($sublanguage_admin->sublanguage_data['post_excerpt']);
			
			}
			
		}
		
		return $data;
	}
	
	/**
	 * Save nav menu item
	 *
	 * Hook for "save_post_{$post->post_type}"
	 *
	 * @from 1.5
	 */
	public function save($post_id, $post) {
		global $sublanguage_admin;
		
		
		if ((!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE)	&& current_user_can('edit_post', $post_id)) {
			
			if (isset($_POST['sublanguage_translate_cpt_nonce'], $_POST['sublanguage_cpt']) && wp_verify_nonce($_POST['sublanguage_translate_cpt_nonce'], 'sublanguage_translate_cpt_action' )) {
				
				foreach ($_POST['sublanguage_cpt'] as $key => $value) {
					
					if (in_array($key, $sublanguage_admin->get_postmeta_keys())) {
					
						update_post_meta($post->ID, $key, html_entity_decode($value));
						
					}
				
				}
				
			}
			
		}
		
	}
	
	
	
}