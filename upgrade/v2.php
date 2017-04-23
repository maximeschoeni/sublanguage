<?php 

/**
 * @from 2.0
 */
class Sublanguage_V2 {
	
	/**
	 * @from 2.0
	 */
	public function __construct() {
		
		add_action('wp_ajax_sublanguage_upgrade_get_posts', array($this, 'ajax_upgrade_get_posts') );
		add_action('wp_ajax_sublanguage_upgrade_posts', array($this, 'ajax_upgrade_posts') );
		add_action('wp_ajax_sublanguage_upgrade_get_terms', array($this, 'ajax_upgrade_get_terms') );
		add_action('wp_ajax_sublanguage_upgrade_terms', array($this, 'ajax_upgrade_terms') );
		add_action('wp_ajax_sublanguage_upgrade_done', array($this, 'ajax_upgrade_done') );
		
	}
	
	/**
	 * Notice
	 */
	public function notice() {
		
		add_action('admin_notices', array($this, 'print_admin_notice'));
		
	}
	
	/**
	 * Notice about upgrade
	 *
	 * @from 2.0
	 */
	public function print_admin_notice() {
    global $sublanguage_admin;
      
		$post_ids = $this->get_non_upgraded_posts();
		$term_ids = $this->get_non_upgraded_term_ids();
		
		if ($post_ids || $term_ids) {
  		
  		include plugin_dir_path( __FILE__ ) . 'include/v2-upgrader.php';
    
    } else {
    	
    	$sublanguage_admin->update_option('db_version', $sublanguage_admin->db_version);
    
    }
    
	}
	
	/**	 
	 * Upgrade options
	 *
	 * @from 2.0
	 */
	public function upgrade_options() {
		global $sublanguage_admin;
		
		$old_options = get_option('sublanguage_options');
		$old_translations = get_option('sublanguage_translations');
		$new_options = array();
		
		// general options
		$new_options['show_slug'] = isset($old_options['show_slug']) && $old_options['show_slug'];
		$new_options['autodetect'] = isset($old_options['autodetect']) && $old_options['autodetect'];
		$new_options['current_first'] = isset($old_options['current_first']) && $old_options['current_first'];
		$new_options['main'] = isset($old_options['main']) ? intval($old_options['main']) : 0;
		$new_options['default'] = isset($old_options['default']) ? intval($old_options['default']) : 0;
    $new_options['version'] = $sublanguage_admin->version;
    $new_options['need_flush'] = 1;
    $new_options['frontend_ajax'] = true; // -> default true when upgrade from previous version
    
		// custom post types
		if (isset($old_options['cpt'])) {
			
			foreach ($old_options['cpt'] as $post_type) {
				
				$new_options['post_type'][$post_type] = array(
					'translatable' => true
				);
				
				if (!empty($old_options['meta_keys'])) {
					
					$meta_keys = $this->filter_meta_keys($old_options['meta_keys'], $post_type);
					
					$new_options['post_type'][$post_type]['meta_keys'] = $meta_keys;
				
				}
				
			}
		
		}
		
		// taxonomies
		if (isset($old_options['taxonomy'])) {
			
			foreach ($old_options['taxonomy'] as $taxonomy) {
				
				$new_options['taxonomy'][$taxonomy] = array(
					'translatable' => true
				);
				
			}
		
		}
		
    // post type slug translations
    if (isset($old_translations['cpt'])) {
    
    	foreach ($old_translations['cpt'] as $lng_id => $post_type_translations) {
    		
    		foreach ($post_type_translations as $slug => $slug_translation) {
    			
    			if ($slug_translation) {
    			
    				$new_options['translations']['post_type'][$slug][$lng_id] = $slug_translation;
    				
    			}
    		
    		}
    	
    	}
		
		}
		
		// taxonomy slug translations
    if (isset($old_translations['taxonomy'])) {
    
    	foreach ($old_translations['taxonomy'] as $lng_id => $taxonomy_translations) {
    		
    		foreach ($taxonomy_translations as $slug => $slug_translation) {
    			
    			if ($slug_translation) {
    			
    				$new_options['translations']['taxonomy'][$slug][$lng_id] = $slug_translation;
    				
    			}
    		
    		}
    	
    	}
		
		}
		
		// options translations
		if (isset($old_translations['option'])) {
		
			$new_options['translations']['option'] = $old_translations['option'];
		
		}
		
		update_option($sublanguage_admin->option_name, $new_options);
		
	}
	
	/**	 
	 * Filter meta keys list by post type
	 *
	 * @from 2.0
	 */
	public function filter_meta_keys($old_meta_keys, $post_type) {
		
		$new_meta_keys = array();
		
		foreach ($old_meta_keys as $meta_key) {
			
			if ($meta_key === 'sublanguage_hide' && $post_type !== 'nav_menu_item') {
				
				continue;
				
			} else if ($meta_key === 'sublanguage_upgraded') {
				
				continue;
				
			}
			
			$new_meta_keys[] = esc_attr($meta_key);
		}
		
		return $new_meta_keys;
	}
	
	/**	 
	 * Ajax get non-upgraded post ids
	 *
	 * @hook  wp_ajax_sublanguage_upgrade_get_posts
	 */
	public function ajax_upgrade_get_posts() {
		
		$post_ids = $this->get_non_upgraded_posts();
		
		echo json_encode($post_ids);
		die();
		
	}
	
	/**	 
	 * Get non-upgraded post ids
	 *
	 * @from 2.0
	 */
	public function get_non_upgraded_posts() {
		global $sublanguage_admin, $wpdb;
		
		$translation_post_types = array();
		
		foreach ($sublanguage_admin->get_languages() as $lng) {
		
			$translation_post_types[] = 'translation_' . $lng->ID;
		
		}
		
		$post_ids = $wpdb->get_col($wpdb->prepare(
			"SELECT p.post_parent FROM $wpdb->posts  AS p
			LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id AND pm.meta_key = %s) 
			WHERE pm.post_id IS NULL AND p.post_type IN ('".implode("','", $translation_post_types)."')
			GROUP BY p.ID",
			'sublanguage_upgraded'
		));
		
		// filter orphans
		if ($post_ids) {
			$post_ids = $wpdb->get_col(
				"SELECT p.ID FROM $wpdb->posts  AS p WHERE p.ID IN (".implode(",", array_map('intval',$post_ids)).")"
			);
		}
		
		return $post_ids;
		
	}
	
	/**	 
	 * Upgrade posts for ajax
	 *
	 * @from 2.0
	 */
	public function ajax_upgrade_posts() {
	
		$post_ids = array_map('intval', $_POST['post_ids']);
		
		foreach ($post_ids as $post_id) {
			
			$this->upgrade_post($post_id);
		
		}
		
		die();
	}
	
	/**	 
	 * Upgrade single post
	 *
	 * @from 2.0
	 */
	public function upgrade_post($post_id) {
		global $sublanguage_admin, $wpdb;
		
		$post = $wpdb->get_row($wpdb->prepare(
			"SELECT p.* FROM $wpdb->posts  AS p WHERE p.ID = %d",
			$post_id
		));
		
		if ($sublanguage_admin->is_post_type_translatable($post->post_type)) {
		
			$translation_post_types = array();
		
			foreach ($sublanguage_admin->get_languages() as $lng) {
			
				$translation_post_types[] = 'translation_' . esc_sql($lng->ID);
			
			}
			
			$translation_posts = $wpdb->get_results($wpdb->prepare(
				"SELECT p.* FROM $wpdb->posts  AS p
				LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id AND pm.meta_key = %s) 
				WHERE p.post_parent = %d AND (pm.post_id IS NULL) AND p.post_type IN ('".implode("','", $translation_post_types)."')
				GROUP BY p.ID",
				'sublanguage_upgraded',
				$post->ID
			));
			
			$output = array();
			
			foreach ($translation_posts as $translation_post) {
			
				$lng_id = intval(substr($translation_post->post_type, 12));
			
				$language = $sublanguage_admin->get_language_by($lng_id, 'ID');
				
				foreach ($sublanguage_admin->fields as $field) {
				
					if ($translation_post->$field) {
				
						update_post_meta($post->ID, $sublanguage_admin->get_prefix($language).$field, $translation_post->$field);
						
					}
			
				}
				
				$meta_keys = $sublanguage_admin->get_post_type_metakeys($post->post_type);
				
				foreach ($meta_keys as $meta_key) {
					
					$values = get_post_meta($translation_post->ID, $meta_key);
					
					delete_post_meta($post->ID, $sublanguage_admin->get_prefix($language).$meta_key);
					
					foreach ($values as $value) {
				
						add_post_meta($post->ID, $sublanguage_admin->get_prefix($language).$meta_key, $value);
					
					}
					
				}
				
				update_post_meta($translation_post->ID, 'sublanguage_upgraded', '1');
		
			}
			
		}
		
	}
	
	
	
	
	
	/**	 
	 * Upgrade single term
	 *
	 * @from 2.0
	 */
	public function upgrade_term($term_id) {
		global $sublanguage_admin, $wpdb;
		
		$term = $wpdb->get_row($wpdb->prepare(
			"SELECT t.term_id, t.name, t.slug, tt.description, tt.taxonomy, tt.term_taxonomy_id, tt.parent FROM $wpdb->terms AS t 
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_taxonomy_id
			WHERE tt.term_taxonomy_id = %d",
			$term_id
		));
		
		if ($sublanguage_admin->is_taxonomy_translatable($term->taxonomy)) {
			
			$taxonomies = array();
	
			foreach ($sublanguage_admin->get_languages() as $lng) {
		
				$taxonomies[] = 'translation_' . esc_sql($lng->ID);
		
			}
			
			$translation_terms = $wpdb->get_results($wpdb->prepare(
				"SELECT t.term_id, t.name, t.slug, tt.description, tt.taxonomy, tt.term_taxonomy_id, tt.parent FROM $wpdb->terms AS t 
				LEFT JOIN $wpdb->termmeta AS tm ON (t.term_id = tm.term_id AND tm.meta_key = %s)
				INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_taxonomy_id
				WHERE tt.parent = %s AND tt.taxonomy IN ('".implode("','", $taxonomies)."') AND tm.term_id IS NULL
				GROUP BY t.term_id",		
				'sublanguage_upgraded',
				$term_id
			));
			
			foreach ($translation_terms as $translation_term) {
			
				$lng_id = intval(substr($translation_term->taxonomy, 12));
			
				$language = $sublanguage_admin->get_language_by($lng_id, 'ID');
				
				foreach ($sublanguage_admin->taxonomy_fields as $field) {
					
					if ($translation_term->$field) {

						update_term_meta($term->term_id, $sublanguage_admin->get_prefix($language).$field, $translation_term->$field);
					
					}
			
				}
				
				update_term_meta($translation_term->term_id, 'sublanguage_upgraded', '1');
		
			}
			
		}
		
	}
	
	/**	 
	 * Get non-upgraded terms
	 *
	 * @from 2.0
	 */
	public function get_non_upgraded_term_ids() {
		global $sublanguage_admin, $wpdb;
		
		$taxonomies = array();
		
		foreach ($sublanguage_admin->get_languages() as $lng) {
			
			$taxonomies[] = 'translation_' . esc_sql($lng->ID);
			
		}
		
		$term_ids = $wpdb->get_col($wpdb->prepare(
			"SELECT tt.parent FROM $wpdb->terms AS t 
			LEFT JOIN $wpdb->termmeta AS tm ON (t.term_id = tm.term_id AND tm.meta_key = %s)
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id 
			WHERE tt.taxonomy IN ('".implode("','", $taxonomies)."') AND tm.term_id IS NULL
			GROUP BY t.term_id",
			'sublanguage_upgraded'
		));
		
		// filter orphans
		if ($term_ids) {
			$term_ids = $wpdb->get_col(
				"SELECT t.term_id FROM $wpdb->terms AS t WHERE t.term_id IN (".implode(",", array_map('intval',$term_ids)).")"
			);
		}
		
		return $term_ids;
		
	}
	
	/**	 
	 * Upgrade terms for ajax
	 *
	 * @from 2.0
	 */
	public function ajax_upgrade_terms() {
		
		$term_ids = array_map('intval', $_POST['term_ids']);
		
		foreach ($term_ids as $term_id) {
			
			$this->upgrade_term($term_id);
		
		}
		
		die();
	
	}

	/**	 
	 * Get non-upgraded post ids
	 *
	 * @from 2.0
	 */
	public function ajax_upgrade_get_terms() {
		
		$term_ids = $this->get_non_upgraded_term_ids();
		
		echo json_encode($term_ids);
		die();
		
	}

	/**	 
	 * Get non-upgraded post ids
	 *
	 * @from 2.0
	 */
	public function ajax_upgrade_done() {
		global $sublanguage_admin;
		
		$sublanguage_admin->update_option('db_version', $sublanguage_admin->db_version);
		
		echo json_encode('done');
		die();
		
	}


	
	
	
}


