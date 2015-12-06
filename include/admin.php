<?php 

class Sublanguage_admin extends Sublanguage_main {

	/**
	 * @from 1.0
	 */
	var $disable_postmeta_filter = false;

	/**
	 * @from 1.0
	 */
	var $disable_post_filter = false;
	
	/**
	 * @from 1.0
	 */
	var $disable_term_filter = false;
	
	/**
	 * @from 1.0
	 */
	var $sublanguage_data;

	
	/**
	 * @from 1.0
	 */
	public function __construct() {
	
		add_action( 'plugins_loaded', array($this, 'load'));
		
	}
	
	/**
	 * @from 1.4.7
	 */
	public function load() {
		
		$this->find_current_language();
		
		if ($this->current_language) {
			
			load_plugin_textdomain('sublanguage', false, dirname(plugin_basename(__FILE__)).'/languages');
			
			parent::load();
			
			add_action('init', array($this, 'update'), 15);
		
			add_filter('get_post_metadata', array($this, 'translate_meta_data'), null, 4);

			add_filter('the_posts', array($this, 'hard_translate_posts'), 20, 2);
			add_filter('the_post', array($this, 'hard_translate_post'));

			add_filter('page_link', array($this, 'translate_page_link'), 10, 3);					
			add_filter('post_type_link', array($this, 'translate_custom_post_link'), 10, 3);
			add_filter('attachment_link', array($this, 'translate_attachment_link'), 10, 2);
				
			add_filter('single_term_title', array($this, 'filter_single_term_title')); // filter term title
			add_filter('single_cat_title', array($this, 'filter_single_term_title')); // filter term title
			add_filter('single_tag_title', array($this, 'filter_single_term_title')); // filter term title
		
			add_filter('get_edit_post_link', array($this, 'translate_edit_post_link'), null, 3);
		
			// restore post data before post saves
			add_filter('wp_insert_post_data', array($this, 'insert_post'), 10, 2);
		
			// save post translation after post saves
			add_action('save_post', array($this, 'save_translation_post_data'), 10, 2);
		
			// delete all translations when a post is deleted
			add_action('delete_post', array($this, 'delete_post_translations'));
		
			add_filter('preview_post_link', array($this, 'translate_preview_post_link'), null , 2);
		
			// javascript for ajax
			add_action('admin_enqueue_scripts', array($this, 'ajax_enqueue_scripts'));
		
			// edit post meta data
			add_filter('update_post_metadata', array($this, 'update_translated_postmeta'), null, 5);
			add_filter('add_post_metadata', array($this, 'add_translated_postmeta'), null, 5);
			add_filter('delete_post_metadata', array($this, 'delete_translated_meta_data'), null, 5);				
		
			// terms
			add_action('edit_term', array($this, 'save_term_translation'), null, 3);
			add_action('delete_term', array($this, 'delete_term_translations'), 10, 4);
			add_filter('terms_clauses', array($this, 'terms_clauses'), 10, 3); // -> added in 1.4.4
			add_filter('terms_to_edit', array($this, 'terms_to_edit'), 10, 2); // -> added in 1.4.4
			add_action('create_term', array($this, 'create_translation_term'), 10, 3); // -> added in 1.4.5
			add_filter('pre_insert_term', array($this, 'cancel_term'), 10, 2); // -> added in 1.4.5
		
			// translate walker for pages dropdown
			add_filter('list_pages', array($this, 'translate_list_pages'), 10 , 2);
					
		}
		
	}

	/** 
	 *	Get current language
	 *
	 * @from 1.0
	 */
	public function find_current_language() {

		if (isset($_REQUEST[$this->language_query_var])) {
			
			$this->current_language = $this->get_language_by($_REQUEST[$this->language_query_var], 'post_name');
			
			if (!$this->current_language) {
				
				$this->current_language = $this->get_main_language();
				
			}
			
		} else {
			
			$this->current_language = $this->get_main_language();
			
		}
	
	}

	/** 
	 * @from 1.1
	 */
	public function activate() {
		
		// initialization

		$options = get_option($this->option_name);
		$languages = $this->get_languages();
		
		if (empty($languages) && empty($options)) {
			
			require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
		
			$translations = wp_get_available_translations();
			$locale = get_locale();
			$language_name = isset($translations[$locale]['native_name']) ? $translations[$locale]['native_name'] : 'English';
			$language_slug = (isset($translations[$locale]['iso']) && !empty($translations[$locale]['iso'])) ? array_shift($translations[$locale]['iso']) : 'en';
		
			$post_id = wp_insert_post(array(
				'post_type' 		=> $this->language_post_type,
				'post_title'    => $language_name,
				'post_name'  	=> $language_slug,
				'post_status'   => 'publish'
			));
			
			$options = array(
				'main' => $post_id,
				'default' => $post_id,
				'show_slug' => false,
				'autodetect' => false,
				'current_first' => false,
				'taxonomy' => array('category'),
				'cpt' => array('post', 'page'),
				'version' => $this->version
			);
			
			update_option($this->option_name, $options);
			
		}
		
		// -> avoid database request when this option is not saved.
		if (!get_option($this->translation_option_name)) {
		
			update_option($this->translation_option_name, array());
			
		}
		
		$admins = get_role( 'administrator' );

		$admins->add_cap( 'edit_language' ); 
		$admins->add_cap( 'edit_languages' ); 
		$admins->add_cap( 'edit_other_languages' ); 
		$admins->add_cap( 'publish_languages' ); 
		$admins->add_cap( 'read_language' ); 
		$admins->add_cap( 'read_private_languages' ); 
		$admins->add_cap( 'delete_language' ); 
		
	}
	
	/** 
	 * @from 1.1
	 */
	public function desactivate() {
		
		$languages = $this->get_languages();
		
		if (count($languages) < 1) {
		
			delete_option($this->option_name);
			delete_option($this->translation_option_name); 
		
		} 
		
		$admins = get_role( 'administrator' );

		$admins->remove_cap( 'edit_language' ); 
		$admins->remove_cap( 'edit_languages' ); 
		$admins->remove_cap( 'edit_other_languages' ); 
		$admins->remove_cap( 'publish_languages' ); 
		$admins->remove_cap( 'read_language' ); 
		$admins->remove_cap( 'read_private_languages' ); 
		$admins->remove_cap( 'delete_language' ); 
		
	}

	/** 
	 * Hook for 'init'
	 *
	 * @from 1.2
	 */
	public function update() {
		
		if (version_compare($this->version, $this->get_option('version', '0')) > 0) {
		
			// upgrades start here :
			
			if (version_compare($this->get_option('version', '0'), "1.4.3") <= 0) {
				
				$this->clean_orphan_terms();
			
			}
			
			// upgrades end
			
			$options = get_option($this->option_name);
			
			$options['version'] = $this->version;
			
			update_option($this->option_name, $options);
			
		}	
	
	}
	

	/**
	 * Restore main language post data before post saves.
	 * Filter for 'wp_insert_post_data'
	 *
	 * @from 1.0
	 */	
	public function insert_post($data, $postarr) {
					
		if (isset($data['post_type']) && in_array($data['post_type'], $this->get_post_types())) { // -> only for translatable post
			
			if ($this->is_sub()) { 
				
				$this->sublanguage_data = array();
			
				$post = get_post($postarr['ID']); // original post
				
				// set default post name
				if ($data['post_title'] == '') {
					
					if (empty($_POST['post_name']) || $_POST['post_name'] == '') {
					
						if ($post->post_name) {
					
							$data['post_name'] = $post->post_name;
					
						} else if ($post->post_title) {
					
							$data['post_name'] = sanitize_title($post->post_title);
						
						}
					
					}
		
				} else if ($data['post_name'] == '') {
				
					$data['post_name'] = sanitize_title($data['post_title']);
				
				}
				
				foreach ($this->fields as $field) {
					
					// store translated data
					$this->sublanguage_data[$this->current_language->ID][$field] = $data[$field];
					
					// and restore original data
					$data[$field] = $post->$field;
				}
				
			}
		
		}
			
		return $data;
	
	}

	/**
	 * Save translation data after post saves.
	 * Hook for 'save_post'
	 *
	 * @from 1.0
	 */
	public function save_translation_post_data($post_id) {
		
		if ($this->disable_post_filter) return;
				
		if ($this->is_sub() && isset($this->sublanguage_data[$this->current_language->ID])) {
			
			if (in_array(get_post($post_id)->post_type, $this->get_post_types())
				&& current_user_can('edit_post', $post_id)) {
				
				$translation = $this->get_post_translation($post_id, $this->current_language->ID);
		
				$translation_data = $this->sublanguage_data[$this->current_language->ID];
				
				if ($translation) { // -> update translation
			
					$translation_data['ID'] = $translation->ID;
				
 					$this->disable_post_filter = true;
					
					/**
					 * Fire before a translation is updated.
					 * @param int $post_id. Original post id.
					 * @param array $translation_data. Translation data.
					 *
					 * @from 1.3
					 */
					do_action('sublanguage_admin_update_translation', $post_id, $translation_data);
					
					wp_update_post($translation_data);
					
 					$this->disable_post_filter = false;
					
					unset($this->post_translation_cache[$this->current_language->ID][$post_id]);
					
				} else { // -> create translation
		
					$translation_data['post_parent'] = $post_id;
					$translation_data['post_type'] = $this->post_translation_prefix.$this->current_language->ID;
					$translation_data['post_status'] = 'publish'; // or inherit ?
					
					/**
					 * Fire before a translation is created.
					 * @param int $post_id. Original post id.
					 * @param array $translation_data. Translation data.
					 *
					 * @from 1.3
					 */
					do_action('sublanguage_admin_create_translation', $post_id, $translation_data);
					
					$translation_id = wp_insert_post($translation_data);
					
					unset($this->post_translation_cache[$this->current_language->ID][$post_id]);
					
				}
			
			}
		
		}
		
	}

	/** 
	 * Delete all translation of a post
	 * Hook for 'delete_post'
	 * 
	 * @from 1.0
	 */
	public function delete_post_translations($post_id) {
		
		$languages = $this->get_languages();
		
		foreach ($languages as $lng) {
			
			if ($this->is_sub($lng->ID)) {
			
				$translation = $this->get_post_translation($post_id, $lng->ID);
			
				if ($translation) {
				
					wp_delete_post($translation->ID, true);
			
				}
				
			}
			
		}
			
	}




	/** 
	 *	Rectify preview post link
	 *	Filter for 'preview_post_link'
	 *
	 * @from 1.0
	 */
	public function translate_preview_post_link($url, $post) {
		
		// Now using uncanonical link

		return $url;
		
	}





	/********* META ***********/


	/**
	 * translate post meta on add
	 * Filter for "add_{$meta_type}_metadata"
	 *
	 * @from 1.0
	 */	
	public function add_translated_postmeta($null, $object_id, $meta_key, $meta_value, $unique) {
		
		if ($this->disable_postmeta_filter) return $null;
		
		$post = get_post($object_id);

		if ($this->get_language_by_type($post->post_type)) {
	
			return true; // -> exit
			
		} else if (in_array($post->post_type, $this->get_post_types())) {
			
			$translatable = in_array($meta_key, $this->postmeta_keys) || apply_filters('sublanguage_translatable_postmeta', false, $meta_key, $object_id);
		
			if ($translatable) {
				
				if ($this->is_sub()) {
					
					$translation = $this->get_post_translation($object_id, $this->current_language->ID);
					
					if ($translation) {
					
						$this->disable_postmeta_filter = true;
		
						add_post_meta($translation->ID, $meta_key, $meta_value, $unique);
		
						$this->disable_postmeta_filter = false;
					
					}

					return true; // -> exit;
			
				}
				
			}
			
		}
	
		return $null;
	
	}
	
	/**
	 * update post meta translation
	 * Filter for "update_{$meta_type}_metadata"
	 *
	 * @from 1.0
	 */	
	public function update_translated_postmeta($null, $object_id, $meta_key, $meta_value, $prev_value) {

		if ($this->disable_postmeta_filter) return $null;
		
		$post = get_post($object_id);

		if ($this->get_language_by_type($post->post_type)) {
	
			return true; // -> exit
			
		} else if (in_array($post->post_type, $this->get_post_types())) {
			
			$translatable = in_array($meta_key, $this->postmeta_keys) || apply_filters('sublanguage_translatable_postmeta', false, $meta_key, $object_id);

			if ($translatable) {
		
				if ($this->is_sub()) {
		
					$translation = $this->get_post_translation($object_id, $this->current_language->ID);
					
					if ($translation) {
					
						$this->disable_postmeta_filter = true;
		
						update_post_meta($translation->ID, $meta_key, $meta_value, $prev_value);
		
						$this->disable_postmeta_filter = false;
					
					}
		
					return true; // -> exit;
			
				}
				
			}
			
		}
	
		return $null;
		
	}

	/**
	 * delete post meta translation
	 * Filter for "delete_{$meta_type}_metadata"
	 *
	 * @from 1.0
	 */	
	public function delete_translated_meta_data($null, $object_id, $meta_key, $meta_value, $delete_all) {

		if ($this->disable_postmeta_filter) return $null;
		
		$post = get_post($object_id);
		
		if ($post) {
			
			if ($this->get_language_by_type($post->post_type)) {
	
				return true; // -> exit
			
			} else if (in_array($post->post_type, $this->get_post_types())) {
			
				$translatable = in_array($meta_key, $this->postmeta_keys) || apply_filters('sublanguage_translatable_postmeta', false, $meta_key, $object_id);
		
				if ($translatable) {
				
					if ($this->is_sub()) {
		
						$translation = $this->get_post_translation($object_id, $this->current_language->ID);
		
						$this->disable_postmeta_filter = true;
		
						delete_metadata('post', $translation->ID, $meta_key, $meta_value, $delete_all);
		
						$this->disable_postmeta_filter = false;
		
						return true; // -> exit;
			
					}
				
				}
			
			}
			
		}
	
		return $null;
		
	}
	
	
	
	/********* TERMS ***********/
	

	/**
	 * Save terms when quick editing (ajax).
	 * Hook for "edit_term"
	 *
	 * @from 1.2
	 */
	public function save_term_translation($term_id, $tt_id, $taxonomy) {
		
		if ($this->disable_term_filter) return;
		
		if (in_array($taxonomy, $this->get_taxonomies())) {
			
			if ($this->is_sub()) { 
				
				$this->update_term_translation($term_id, $taxonomy, $_POST, $this->current_language->ID);
				
				// restore term
				
				remove_filter('get_term', array($this, 'translate_get_term'));
				
				$term = get_term_by('id', $term_id, $taxonomy); // old term (not cached yet)
				
				add_filter('get_term', array($this, 'translate_get_term'), null, 2);
				
				$this->disable_term_filter = true;
				
				wp_update_term($term_id, $taxonomy, array(
					'name' => $term->name,
					'slug' => $term->slug
				));
		
				$this->disable_term_filter = false;
				
			}
		
		}
		
	}

	/**
	 * Update term translation when saving with ajax
	 *
	 * @from 1.2
	 */
	public function update_term_translation($term_id, $taxonomy, $data, $language_id) {
		
		$original_term = get_term_by('id', $term_id, $taxonomy);

		$translation = $this->get_term_translation($original_term, $taxonomy, $language_id);
		
		$data = array_intersect_key($data, array(
			'name' => true,
			'slug' => true,
			'description' => true
		));
		
		if ($translation)	{
			
			if ($data['name'] || $data['slug']) {
				
				if ($data['name'] == '') $data['name'] = $original_term->name;
				if ($data['slug'] == '') $data['slug'] = $original_term->slug;
				
				wp_update_term($translation->term_id, $this->term_translation_prefix.$language_id, $data);
				
			} else {
				
				wp_delete_term($translation->term_id, $this->term_translation_prefix.$language_id);
			
			}
		
		} else {
			
			if ($data['name'] || $data['slug']) {
				
				if ($data['name'] == '') $data['name'] = $original_term->name;
				if ($data['slug'] == '') $data['slug'] = sanitize_title($data['name']);
				$data['parent'] = $term_id;
				
				wp_insert_term($data['name'], $this->term_translation_prefix.$language_id, $data);
				
			}
	
		}
		
		unset($this->term_translation_cache[$language_id][$term_id]);
				
	}


	/**	 
	 * delete all translations of the term
	 * hook for 'delete_term'
	 * @from 1.0
	 */
	public function delete_term_translations($term_id, $tt_id, $taxonomy, $deleted_term) {
		
		$languages = $this->get_languages();
		
		foreach ($languages as $language) {
		
			$translation = $this->get_term_translation($deleted_term, $deleted_term->taxonomy, $language->ID);
			
			if ($translation) {
				
				 wp_delete_term($translation->term_id, $this->term_translation_prefix.$language->ID);
			
			}
			
		}

	}
	
	/**	 
	 * When a term is created while not in main language, create a translation for current language
	 *
	 * hook for 'create_term'
	 *
	 * @from 1.4.5
	 */
	public function create_translation_term($term_id, $tt_id, $taxonomy) {

		if ($this->is_sub()) { // -> not main language
		
			if ($this->is_taxonomy_translatable($taxonomy)) {  // -> translatable taxonomy
				
				$original_term = get_term_by('id', $term_id, $taxonomy);
				
				wp_insert_term($original_term->name, $this->get_translation_taxonomy(), array('parent' => $term_id));
				
			}
		
		}
		
	}
	
	/**	 
	 * When a post with tags is saved while not in main language, a new term with the translated name is going to be created for each tags. 
	 * This function prevent this term creation by faking an error
	 * 
	 * Quite ugly but no better alternative found
	 *
	 * hook for 'pre_insert_term'
	 *
	 * @from 1.4.5
	 */
	public function cancel_term($term_name, $taxonomy) {
		
		if ($this->is_sub()) { // -> not main language
		
			if ($this->is_taxonomy_translatable($taxonomy)) {  // -> translatable taxonomy
				
				$translation_terms = get_terms($this->get_translation_taxonomy(), array(
					'name' => $term_name,
					'hide_empty' => false
				));
				
				if ($translation_terms) { // -> ok, there is already a translation for this term
					
					return new WP_Error('sublanguage_cancel_term', 'Prevent term duplication (Sublanguage junk)');
					
				}
				
			}
		
		}
		
		return $term_name;
	}
	
	/**	 
	 * Translate terms used in tags meta box
	 *
	 * filter for 'terms_to_edit'
	 *
	 * @from 1.4.4
	 */
	public function terms_to_edit($terms_to_edit, $taxonomy) {
		
		if ($this->is_sub() && $this->is_taxonomy_translatable($taxonomy)) { // -> not main language && translatable taxonomy
			
			$terms = get_terms($taxonomy, array(
				'name' => array_map('trim', explode(',', $terms_to_edit)),
				'hide_empty' => false
			));
			
			$this->enqueue_terms($terms, $this->current_language->ID);
			
			$translations = array();
			
			foreach ($terms as $term) {
				
				$translations[] = $this->translate_term_field($term, $taxonomy, $this->current_language->ID, 'name', $term->name);
			
			}
			
			$terms_to_edit = esc_attr( join( ',', $translations ) );
			
		}
		
		return $terms_to_edit;
		
	}
	
	/**	 
	 * when terms are queried by name, join translations to the query.
	 * This is needed in admin when receiving a translated post tags 
	 *
	 * filter for 'terms_clauses'
	 *
	 * @from 1.4.5
	 */
	public function terms_clauses($pieces, $taxonomies, $args) {
		global $wpdb;
		
		if ($this->is_sub() && $this->is_taxonomy_translatable($taxonomies)) { // -> not main language & translatable taxonomy
			
			if (!empty($args['name']) || !empty($args['slug']) || !empty($args['name__like']) || !empty($args['description__like'])) {
				
				unset($args['fields']);
				
				$args['hide_empty'] = false; // -> translation terms are always empty!
			
				$translation_terms = get_terms($this->term_translation_prefix.$this->current_language->ID, $args);
			
				$translation_parents = array();
				
				foreach ($translation_terms as $translation_term) {
			
					$translation_parents[] = $translation_term->parent;
			
				}
		
				if ($translation_parents) {
			
					$pieces['where'] .= " OR t.term_id in (" . implode(', ', array_map('intval', $translation_parents)) . ")";
				
				}
			
			}
			
		}
		
		return $pieces;
		
	}
	
	/**	 
	 * Clean orphan terms
	 *
	 * @from 1.4.4
	 */
	public function clean_orphan_terms() {
		global $wpdb;
		
		$languages = $this->get_languages();
		$translation_post_types = array();
	
		foreach ($languages as $lng) {
		
			if ($this->is_sub($lng->ID)) {
		
				$translation_post_types[] = esc_sql($this->post_translation_prefix.$lng->ID);
			
			}
			
		}
		
		$translation_terms = array();
		
		if ($translation_post_types) {
				
			$translation_terms = get_terms($translation_post_types, array(
				'hide_empty' => false,
				'fields' => 'id=>parent'
			));
		
		}
		
		if ($translation_terms) {
		
			$original_terms = get_terms($this->get_taxonomies(), array(
				'hide_empty' => false,
				'include' => array_values($translation_terms),
				'fields' => 'ids'
			));
			
			$orphans = array_diff($translation_terms, $original_terms);
			
			if ($orphans) {
				
				// but now we need the taxonomy to delete them...
				$orphan_terms = get_terms($translation_post_types, array(
					'hide_empty' => false,
					'include' => array_keys($orphans)
				));
				
				foreach ($orphan_terms as $orphan_term) {
					
					wp_delete_term($orphan_term->term_id, $orphan_term->taxonomy);
				
				}
				
			}
			
			
		}
		
	}

}


