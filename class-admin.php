<?php 

class Sublanguage_admin extends Sublanguage_current {
	
	// FOR DEBUG
	public  $home_url_translation_alert = false;
	
	
	/**
	 * @from 1.0
	 */
	var $disable_postmeta_filter = false;
	
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
		
		$this->update();
		
		if ($this->get_language()) {
			
			parent::load();
			
			add_filter('get_post_metadata', array($this, 'translate_meta_data'), null, 4);
			add_filter('sublanguage_postmeta_override', '__return_true');

			add_filter('the_posts', array($this, 'translate_the_posts'), 20, 2);
			add_filter('the_post', array($this, 'translate_post'));

			add_filter('page_link', array($this, 'translate_page_link'), 10, 3);					
			add_filter('post_type_link', array($this, 'translate_custom_post_link'), 10, 3);
			add_filter('attachment_link', array($this, 'translate_attachment_link'), 10, 2);
				
			add_filter('single_term_title', array($this, 'filter_single_term_title')); // filter term title
			add_filter('single_cat_title', array($this, 'filter_single_term_title')); // filter term title
			add_filter('single_tag_title', array($this, 'filter_single_term_title')); // filter term title
		
			add_filter('get_edit_post_link', array($this, 'translate_edit_post_link'), 10, 3);
		
			// restore post data before post saves
			add_filter('wp_insert_post_data', array($this, 'insert_post'), 10, 2);
		
			// save post translation after post saves
			add_action('save_post', array($this, 'save_translation_post_data'), 10, 2);
			
			// Save original title in meta data for ordering post translations by title. @from 2.0
			add_action('save_post', array($this, 'save_translations_title_cache'), 10, 2);
			
			// delete all translation when deleting language post
			add_action('before_delete_post', array($this, 'delete_language'));
			
			add_filter('preview_post_link', array($this, 'translate_preview_post_link'), 10 , 2);
			
			// add/update/delete post meta data
			add_filter('update_post_metadata', array($this, 'update_translated_postmeta'), 10, 5);
			add_filter('add_post_metadata', array($this, 'add_translated_postmeta'), 10, 5);
			add_filter('delete_post_metadata', array($this, 'delete_translated_meta_data'), 10, 5);				
			
			
			add_filter('terms_clauses', array($this, 'terms_clauses'), 10, 3); // -> added in 1.4.4
			add_filter('pre_insert_term', array($this, 'cancel_term'), 10, 2); // -> added in 1.4.5
		
			// translate walker for pages dropdown
			add_filter('list_pages', array($this, 'translate_list_pages'), 10 , 2);
			
			// when upgrading upgrade all languages (instead of just the admin language)
			add_filter('themes_update_check_locales', array($this, 'update_all_languages'));
			add_filter('plugins_update_check_locales', array($this, 'update_all_languages'));
		
			// update db on slug change (must also be fired on upgrade)
			add_action('post_updated', array($this, 'update_language_slug'), 9, 3);
			
			// prevent replacing language slug by one already existing
			add_filter('wp_insert_post_empty_content', array($this, 'filter_duplicate_language_slug'), 10, 2);
			
			// Rewrite rules
			add_action('generate_rewrite_rules', array($this, 'generate_rewrite_rules'));
			
			// flush rule when updating page
			add_action('post_updated', array($this, 'rebuild_subpage_permalink'), 10, 3);
		
			// flush rule when deleting page
			add_action('before_delete_post', array($this, 'rebuild_permalink_before_delete_post'));
			
			// flush permalink when editing term
			add_action('edit_term', array($this, 'update_term_permalinks'), 10, 3);
		
			// flush permalink when deleting term
			add_action('delete_term', array($this, 'update_term_permalinks'), 10, 3);
		
			// add rewrite tags 
			add_action('admin_init', array($this, 'register_subpage_rewrite_tags'));
			
			// Attachments
			if ($this->is_post_type_translatable('attachment')) {
				
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
			
				add_filter('wp_prepare_attachment_for_js', array($this, 'prepare_attachment_for_js'), 10, 3);
				add_filter('wp_insert_attachment_data', array($this, 'insert_attachment'), 10, 2);
				add_action('edit_attachment', array($this, 'edit_attachment'));
				
				// translate caption when send to editor
				add_filter('image_add_caption_text', array($this, 'add_caption'), 10, 2);
				
				// translate alt when send to editor
				add_filter('get_image_tag', array($this, 'get_image_tag'), 10, 6);
			}
			
			// Public API
			
			// API import post
			add_action('sublanguage_import_post', array($this, 'import_post'));
			
			// API import term
			add_action('sublanguage_import_term', array($this, 'import_term'), 10, 2);
			
			// API init sublanguage admin
			add_action('init', array($this, 'init'));
		}
		
	}
	
	/**
	 * @from 2.0
	 */	
	public function init() {
		
		load_plugin_textdomain('sublanguage', false, dirname(plugin_basename(__FILE__)).'/languages');
		
		/**
		 * Hook called after initializing most hooks and filters
		 *
		 * @from 2.0
		 *
		 * @param Sublanguage_admin object
		 */	
		do_action('sublanguage_admin_init', $this);
		
	}
	
	/** 
	 * Update Option
	 *
	 * @from 2.0
	 */
	public function update_option($option_name, $value) {
		
		$options = get_option($this->option_name);
		$options[$option_name] = $value;
		update_option($this->option_name, $options);
		
	}
	
	/** 
	 * Delete Option
	 *
	 * @from 2.0
	 */
	public function delete_option($option_name) {
		
		$options = get_option($this->option_name);
		unset($options[$option_name]);
		update_option($this->option_name, $options);
		
	}
	
	/** 
	 * @from 1.1
	 */
	public function activate() {
		
		require_once( plugin_dir_path( __FILE__ ) . 'activation.php');
		
		Sublanguage_Activation::activate();
		
	}
	
	/** 
	 * @from 1.1
	 */
	public function desactivate() {
		
		require_once( plugin_dir_path( __FILE__ ) . 'activation.php');
		
		Sublanguage_Activation::desactivate();
		
	}

	/** 
	 * Upgrades if required
	 *
	 * Hook for 'init'
	 *
	 * @from 1.2
	 */
	public function update() {
		
		if (version_compare($this->get_option('version', '0'), '2.0') < 0) {
			
			require( plugin_dir_path( __FILE__ ) . 'upgrade/v2.php');
			
			$v2 = new Sublanguage_V2();
			
			$v2->upgrade_options();
			
		} else if (version_compare($this->get_option('db_version', '0'), '2.0') < 0) {
			
			require( plugin_dir_path( __FILE__ ) . 'upgrade/v2.php');
			
			new Sublanguage_V2();
			
		}
	
	}

	/**
	 * delete all translations when deleting a language post
	 * @hook 'before_delete_post'
	 *
	 * @from 1.1
	 */
	public function delete_language($language_id) {
		global $wpdb;
		
		$language = get_post($language_id);
		
		if ($language->post_type === $this->language_post_type) {
			
			$translations = $this->get_option('translations', array());
			
			if (isset($translations['taxonomy'])) {
				
				foreach ($translations['taxonomy'] as $taxonomy => $translation) {
					
					if (isset($translations['taxonomy'][$taxonomy][$language_id])) {
						
						unset($translations['taxonomy'][$taxonomy][$language_id]);
						
					}
					
				}
				
			}
			
			if (isset($translations['cpt'])) {
				
				foreach ($translations['cpt'] as $cpt => $translation) {
					
					if (isset($translations['cpt'][$cpt][$language_id])) {
						
						unset($translations['cpt'][$cpt][$language_id]);
						
					}
					
				}
				
			}
			
			$this->update_option('translations', $translations);
			
			$prefix = $this->get_prefix($language);
			
			$wpdb->query($wpdb->prepare( 
				"DELETE FROM $wpdb->postmeta WHERE meta_key LIKE (%s)",
				$prefix . '%'
			));
			
			$wpdb->query($wpdb->prepare( 
				"DELETE FROM $wpdb->termmeta WHERE meta_key LIKE (%s)",
				$prefix . '%'
			));
			
		}
	
	}
	
	/**
	 * Prevent replacing language slug by one already existing
	 *
	 * @hook 'wp_insert_post_empty_content'
	 *
	 * @from 2.0
	 */
	public function filter_duplicate_language_slug($maybe_empty, $postarr) {
		
// 		if ($postarr['post_type'] === $this->language_post_type && isset($postarr['post_name']) && $this->get_language_by($postarr['post_name'], 'post_name')) {
// 				
// 				return true;
// 				
// 		}
		
		return $maybe_empty;
	}
	
	/**
	 * Update db on slug change
	 *
	 * @hook 'post_updated'
	 *
	 * @from 1.5.1
	 */
	public function update_language_slug($post_ID, $post_after, $post_before) {
		global $wpdb;
		
		if ($post_after->post_type === $this->language_post_type && $post_after->post_name !== $post_before->post_name) {
			
			if (!$this->get_language_by($post_after->post_name, 'post_name')) { // -> cancel if language slug already exists
			
				$prefix_before = $this->create_prefix($post_before->post_name);
				$prefix_after = $this->create_prefix($post_after->post_name);
			
				$wpdb->query($wpdb->prepare( 
					"UPDATE $wpdb->postmeta SET meta_key = REPLACE(meta_key, %s, %s) WHERE meta_key LIKE (%s)",
					$prefix_before, 
					$prefix_after,
					$prefix_before . '%'
				));
				
				$wpdb->query($wpdb->prepare( 
					"UPDATE $wpdb->termmeta SET meta_key = REPLACE(meta_key, %s, %s) WHERE meta_key LIKE (%s)",
					$prefix_before, 
					$prefix_after,
					$prefix_before . '%'
				));
			
			}
			
		}
		
	}
	
	/**
	 * Ask to get all registered languages when upgrading (instead of just admin language)
	 *
	 * Filter for 'themes_update_check_locales', 'plugins_update_check_locales'
	 *
	 * @from 1.1
	 */	
	public function update_all_languages($locales) {
		
		return $this->get_language_column('post_content');
			
	}
	
	/**
	 * Restore main language post data before post saves.
	 * Filter for 'wp_insert_post_data'
	 *
	 * @from 1.0
	 */	
	public function insert_post($data, $postarr) {
		
		if (isset($data['post_type']) && is_string($data['post_type']) && $this->is_post_type_translatable($data['post_type'])) { // -> only for translatable post
			
			$language = $this->get_language();
			
			if ($this->is_sub($language)) { 
				
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
					$this->sublanguage_data[$language->ID][$field] = $data[$field];
					
					// and restore original data
					$data[$field] = wp_slash($post->$field);
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
	public function save_translation_post_data($post_id, $post) {
		
		if ($this->is_post_type_translatable($post->post_type) && current_user_can('edit_post', $post_id)) {
			
			$language = $this->get_language();
			
			if ($this->is_sub($language) && isset($this->sublanguage_data[$language->ID])) {
			
				$this->update_post_translation($post_id, $this->sublanguage_data[$language->ID], $language);
				
			}
					
		}
		
	}
	
	/**
	 * Save Post from Ajax (for editor button)
	 *
	 * @hook 'wp_ajax_sublanguage_save_translation'
	 *
	 * @from 1.3
	 */
	public function ajax_save_translation() {
		
		if (isset($_POST['id'], $_POST['translations'])) {
		
			$post_id = intval($_POST['id']);
			$post = get_post($post_id);
			$response = array();
			$translations = $_POST['translations'];
			
			if ($post && current_user_can('edit_posts', $post_id)) {
			
				foreach ($translations as $translation) {
			
					$language = $this->get_language_by($translation['lng'], 'post_name');
				
					$postdata = array(
						'post_content' => $translation['fields']['content'],
						'post_title' => $translation['fields']['title'],
						'post_name' => sanitize_title($translation['fields']['slug'])
					);
			
					if (isset($translation['fields']['excerpt'])) {
				
						$postdata['post_excerpt'] = $translation['fields']['excerpt'];
			
					}
				
					if ($this->is_sub($language)) {
				
						$this->update_post_translation($post_id, $postdata, $language);
					
					} else {
						
						$this->set_language($language);
						
						$postdata['ID'] = $post_id;
						
						wp_update_post($postdata);
						
						$this->restore_language();
						
					}
				
					if (!$translation['fields']['slug']) {
						$response[] = array(
							'lng' => $language->post_name,
							'slug' => $this->translate_post_field($post, 'post_name', $language)
						);
					} else if ($postdata['post_name'] != $translation['fields']['slug']) {
						$response[] = array(
							'lng' => $language->post_name,
							'slug' => $postdata['post_name']
						);
					}
					
				}
				
			}
		
			//echo json_encode($response);
		
			exit;
		
		}
		
	}
	
	/**
	 * Update post translation
	 *
	 * @param int $post_id Post ID
	 * @param array $data {
	 *		List of field to save.
	 *		@string $post_name Post name
	 *		@string $post_title Post title
	 *		@string $post_content Post content
	 *		@string $post_excerpt Post excerpt
	 *		@string $custom_meta_key Post meta
	 * }
	 * @param object WP_Post $language Language. Optional
	 *
	 * @from 2.0
	 */
	public function update_post_translation($post_id, $data, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
			
		if ($this->is_sub($language)) {
			
			foreach ($data as $field => $value) { 
				
				if (in_array($field, $this->fields)) {
				
					/**
					 * Filter before a translation field is updated.
					 * @param int $post_id. Original post id.
					 * @param string $field. Field name.
					 * @param string $value. Value.
					 *
					 * @from 2.0
					 */
					update_post_meta($post_id, $this->get_prefix($language).$field, apply_filters('sublanguage_admin_update_post', $value, $post_id, $field));
					
				}
		
			}
		
		}
				
	}
	
	
	/**
	 * Save original title in meta data for ordering post translations by title.
	 *
	 * @hook for 'save_post'
	 *
	 * @from 2.0
	 */
	public function save_translations_title_cache($post_id, $post) {
		
		if ($this->get_post_type_option($post->post_type, 'title_cached')) {
			
			if ($this->is_main()) {
			
				foreach ($this->get_languages() as $language) {
					
					if ($this->is_sub($language)) {
					
						$key = $this->get_prefix($language) . 'order_title';
					
						if (get_post_meta($post->ID, $key, true) === '') {
						
							update_post_meta($post->ID, $key, $post->post_title);
					
						}
						
					}
					
				}
				
			} else if ($this->is_sub()) {
				
				$language_id = $this->get_language()->ID;
				 
				if (isset($this->sublanguage_data[$language_id]['post_title']) && $this->sublanguage_data[$language_id]['post_title']) {
					
					$key = $this->get_prefix() . 'order_title';
					
					update_post_meta($post->ID, $key, $this->sublanguage_data[$language_id]['post_title']);
				
				}
				
			}
			
		}
		
	}
	
	/**
	 * Update term translation
	 *
	 * @param int $term_id Term ID
	 * @param array $data {
	 *		List of field to save.
	 *		@string $name Name
	 *		@string $slug Slug
	 *		@string $description Description
	 *		@string $custom_meta_key Post meta
	 * }
	 * @param object WP_Post $language Language. Optional
	 *
	 * @from 2.0
	 */
	public function update_term_translation($term_id, $data, $language = null) {
		
		if (empty($language)) {
			
			$language = $this->get_language();
		
		}
		
		if ($this->is_sub($language)) {
			
			foreach ($data as $field => $value) { 
				
				if ($field === 'slug') {
						
					$value = sanitize_title($value);
				
				}
				
				if ($value) {
					
					/**
					 * Filter before a term translation field is updated.
					 * @param int $post_id. Original post id.
					 * @param string $field. Field name.
					 * @param string $value. Value.
					 *
					 * @from 2.0
					 */
					update_term_meta($term_id, $this->get_prefix($language).$field, apply_filters('sublanguage_admin_update_term', $value, $term_id, $field));
					
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

	
	/**
	 * translate post meta on add
	 * Filter for "add_{$meta_type}_metadata"
	 *
	 * @from 1.0
	 */	
	public function add_translated_postmeta($null, $object_id, $meta_key, $meta_value, $unique) {
		
		$post = get_post($object_id);
		
		if ($post && $this->is_sub() && $this->is_meta_key_translatable($post->post_type, $meta_key)) {
		
			add_post_meta($object_id, $this->get_prefix().$meta_key, $meta_value, $unique);
		
			return true; // -> exit;
							
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
		
		$post = get_post($object_id);
		
		if ($post && $this->is_sub() && $this->is_meta_key_translatable($post->post_type, $meta_key)) {
			
			update_post_meta($object_id, $this->get_prefix().$meta_key, $meta_value, $prev_value);
		
			return true; // -> exit;
							
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
		
		$post = get_post($object_id);
		
		if ($post && $this->is_sub() && $this->is_meta_key_translatable($post->post_type, $meta_key)) {
			
			delete_metadata('post', $object_id, $this->get_prefix().$meta_key, $meta_value, $delete_all);
		
			return true; // -> exit;
							
		}
		
		return $null;
		
	}
	

	
	/* Terms
	----------------------------------------------- */	
	
	/**	 
	 * When a post with tags is saved while not in main language, a new term with the translated name is going to be created for each tags. 
	 * This function prevent this term creation by faking an error
	 * 
	 * No better alternative found so far
	 *
	 * @hook 'pre_insert_term'
	 *
	 * @from 1.4.5
	 */
	public function cancel_term($term_name, $taxonomy) {
		
		return $term_name;
		
		if ($this->is_sub() && $this->is_taxonomy_translatable($taxonomy)) {  // -> translatable taxonomy
			
			return new WP_Error('sublanguage_cancel_term', 'Prevent term duplication (Sublanguage)');
			
		}
				
		return $term_name;
	}
	
	/**	 
	 * when terms are queried by name, join translations to the query.
	 * This is needed in admin when receiving a translated post tags 
	 *
	 * @filter 'terms_clauses'
	 * @from 1.4.5
	 */
	public function terms_clauses($pieces, $taxonomies, $args) {
		global $wpdb;
		
		if ($this->is_sub() && array_filter($taxonomies, array($this, 'is_taxonomy_translatable'))) { // -> not main language & translatable taxonomy
			
			$translation_args = $args;
			
			foreach (array('name', 'slug') as $field) {
			
				if (isset($args[$field]) && $args[$field]) {
				
					$translation_args['meta_query'][] = array(
						'key'     => $this->get_prefix() . $field,
						'value'   => $args[$field]
					);
					
					$translation_args[$field] = '';
				
				}
			
			}
			
			if (isset($args['name__like']) && $args['name__like']) {
				
				$translation_args['meta_query'][] = array(
					'key'     => $this->get_prefix() . 'name',
					'value'   => $args['name__like'],
					'compare' => 'LIKE'
				);
				
				$translation_args['name__like'] = '';
				
			}
			
			if (isset($args['description__like']) && $args['description__like']) {
				
				$translation_args['meta_query'][] = array(
					'key'     => $this->get_prefix() . 'description',
					'value'   => $args['description__like'],
					'compare' => 'LIKE'
				);
				
				$translation_args['description__like'] = '';
				
			}
			
			if ($translation_args !== $args) {
				
				$translation_args['fields'] = 'ids';
				
				$translation_ids = get_terms($taxonomies, $translation_args);
				
				if ($translation_ids) {
			
					$pieces['where'] .= " OR t.term_id in (" . implode(', ', array_filter(array_map('intval', $translation_ids))) . ")";
				
				}
			
			}
			
		}
		
		return $pieces;
		
	}
	
	
	
	
	
	
	/* Attachments
	----------------------------------------------- */	
	
	/** 
	 * Enqueue Javascript (only on post pages)
	 *
	 * @from 1.4
	 */	
	 public function admin_enqueue_scripts($hook) {
		
		if ($this->get_languages() && ($hook == 'post.php' || $hook == 'post-new.php' || $hook == 'upload.php')) {
		
			wp_enqueue_media();
		
			wp_enqueue_script('sublanguage-monkey-patch-wp-media', plugin_dir_url( __FILE__ ) . 'js/attachments.js');
			wp_enqueue_style('sublanguage-style-wp-media', plugin_dir_url( __FILE__ ) . 'js/attachments-style.css');
		}
		
	}
	
	/** 
	 * Send translation for javascript
	 *
	 * @filter 'wp_prepare_attachment_for_js'
	 *
	 * @from 1.4
	 */	
	public function prepare_attachment_for_js($response, $attachment, $meta) {
		
		$languages = $this->get_languages();
		
		foreach ($languages as $language) {
			
			$response['sublanguage'][$language->post_name] = array(
				'title' => $this->translate_post_field($attachment, 'post_title', $language, ''),
				'alt' => $this->translate_post_meta($attachment, '_wp_attachment_image_alt', true, $language, ''), //$translation ? get_post_meta($translation->ID, '_wp_attachment_image_alt', true ) : '',
				'caption' => $this->translate_post_field($attachment, 'post_excerpt', $language, ''), //$translation ? $translation->post_excerpt : '',
				'description' => $this->translate_post_field($attachment, 'post_content', $language, ''), //$translation ? $translation->post_content : '',
				'name' => $this->translate_post_field($attachment, 'post_name', $language, ''), //$translation ? $translation->post_name : ''
			);
				
		}
		
		return $response;
	
	}
	
	/**
	 * When data are inserted from ajax, new data is filled on original attachment data. 
	 * Only field that actually changed should be updated.
	 *
	 * @filter 'wp_insert_attachment_data'
	 *
	 * @from 1.4
	 */	
	public function insert_attachment($data, $postarr) {
		
		
		
		$this->fields = array();
		
		if (isset($_REQUEST['changes']['title'])) {
			
			$this->fields[] = 'post_title';
			
		}

		if (isset($_REQUEST['changes']['name'])) {
			
			$this->fields[] = 'post_name';
			
		}
				
		if (isset($_REQUEST['changes']['caption'])) {
			
			$this->fields[] = 'post_excerpt';
			
		}
		
		if (isset($_REQUEST['changes']['description'])) {
			
			$this->fields[] = 'post_content';
			
		}
		
		return $this->insert_post($data, $postarr);
	}
	
	/**
	 * Save translation data after attachment saves.
	 * Hook for 'edit_attachment'
	 *
	 * @from 2.0
	 */
	public function edit_attachment($post_id) {
		
		$this->save_translation_post_data($post_id, get_post($post_id));
		
	}
	
	
	/**
	 * Translate caption when sending image in editor
	 *
	 * @filter for 'image_add_caption_text'
	 *
	 * @from 1.4
	 */
	public function add_caption($caption, $id) {	
		
		if ($this->is_sub()) {
			
			return $this->translate_post_field(get_post($id), 'post_excerpt', null, $caption);
		
		}
		
		return $caption;
	}

	/**
	 * Translate alt when send to editor
	 *
	 * @filter for 'get_image_tag'
	 *
	 * @from 1.4
	 */
	public function get_image_tag($html, $id, $alt, $title, $align, $size) {	
		
		$alt_translation = $this->translate_post_meta(get_post($id), '_wp_attachment_image_alt', true);
		
		return preg_replace('/alt="[^"]*"/', 'alt="'.$alt_translation.'"', $html);
		
	}
	
	
	
	
	
	
	/* URL Rewrite
	----------------------------------------------- */
	
	/**
	 * Register rewrite tags
	 *
	 * @from 1.0
	 */
	public function register_subpage_rewrite_tags() {
		
		add_rewrite_tag('%sublanguage_cpt%', '([^&]+)');
		add_rewrite_tag('%sublanguage_nodepost%', '([^&]+)');
		add_rewrite_tag('%sublanguage_post%', '([^&]+)');
		add_rewrite_tag('%sublanguage_slug%', '([^&]+)');
		add_rewrite_tag('%sublanguage_tax%', '([^&]+)');
		add_rewrite_tag('%sublanguage_term%', '([^&]+)');
		add_rewrite_tag('%sublanguage_nodeterm%', '([^&]+)');
		add_rewrite_tag('%sublanguage_parent%', '([^&]+)');
		add_rewrite_tag('%sublanguage_path%', '([^&]+)');
			
	}
	
	/**
	 * Rebuild permalink when saving post if parent/name has changed
	 *
	 * @hook 'post_updated'
	 *
	 * @from 1.0
	 */
	public function rebuild_subpage_permalink($post_ID, $post_after, $post_before) {
		
		// only if post is hierarchical and translatable
		if ($this->is_post_type_translatable($post_after->post_type) && is_post_type_hierarchical($post_after->post_type) !== false) {
			
			// only if parent, status or name have changed
			if ($post_after->post_parent !== $post_before->post_parent 
				|| $post_after->post_name !== $post_before->post_name
				|| $post_after->post_status !== $post_before->post_status) {
				
				$this->update_option('need_flush', 1);
				
			}
			
		}
		
	}
	
	/**
	 * rebuild permalink when saving post if parent/name has changed
	 *
	 * @hook 'before_delete_post'
	 *
	 * @from 1.0
	 */
	public function rebuild_permalink_before_delete_post($post_id) {
		
		$post = get_post($post_id);
		
		if ($this->is_post_type_translatable($post->post_type)) {
		
			add_action('after_delete_post', array($this, 'rebuild_permalink_after_delete_post'));
		
		}
		
	}
	
	/**
	 * rebuild permalink after post was deleted
	 *
	 * Hook for 'after_delete_post'
	 *
	 * @from 1.0
	 */
	public function rebuild_permalink_after_delete_post($post_id) {
		
		$this->update_option('need_flush', 1);
		
	}
	
	/**
	 * rebuild permalink when saving term
	 *
	 * @hook 'edit_term', 'delete_term'
	 */
	public function update_term_permalinks($term_id, $tt_id, $taxonomy) {
				
		$taxonomy_obj = get_taxonomy($taxonomy);
		
		if ($this->is_taxonomy_translatable($taxonomy) && $taxonomy_obj->hierarchical) {
			
			$this->update_option('need_flush', 1);
		
				
		}
		
	}
	
	/**
	 * @hook 'generate_rewrite_rules'
	 * @from 2.0
	 */
	public function generate_rewrite_rules($rewrite) {
		
		$this->cpt_rewrite_rules($rewrite);
		$this->taxonomy_rewrite_rules($rewrite);
		$this->subpage_rewrite_rules($rewrite);
		$this->subterm_rewrite_rules($rewrite);
		
		$this->update_option('need_flush', 0);
	
	}

	/**
	 * Add rewrite rules for custom post types
	 *
	 * @hook 'generate_rewrite_rules'
	 * @from 1.0
	 */
	public function cpt_rewrite_rules($rewrite) {
		
		$post_types = get_post_types(array(), 'names');
		
		$post_types = array_filter($post_types, array($this, 'is_post_type_translatable'));
		
		if (count($post_types)) {
			
			$languages = $this->get_languages();
			
			foreach ($post_types as $post_type) {
				
				$post_type_obj = get_post_type_object($post_type);
				$post_type_slug = $post_type_obj->rewrite['slug'];
				$translated_slugs = array();
				
				foreach ($languages as $language) {
					
					$translated_slug = $this->translate_cpt($post_type, $language, $post_type_slug);
					
					if ($translated_slug !== $post_type_slug) {
						
						// -> custom post type singulars
						
						$rewrite->add_rewrite_tag(
							'%'.$translated_slug.'%', 
							$translated_slug.'/([^/]+)', 
							'sublanguage_cpt='.$post_type.'&sublanguage_slug='.$translated_slug.'&sublanguage_post='
						);
						
						$rules = $rewrite->generate_rewrite_rules(
							'%'.$translated_slug.'%', 
							$post_type_obj->rewrite['ep_mask'], // ep_mask
							$post_type_obj->rewrite['pages'], // paged
							false, // feed
							true, // forcomments
							false, // walk_dirs
							true // endpoints
						);
					
						$rewrite->rules = array_merge($rules, $rewrite->rules);
						
						$translated_slugs[] = $translated_slug;
						
					}
					
				}
				
				// -> custom post type archive
				
				if ($translated_slugs) {
					
					$translated_slugs[] = $post_type_slug;
					$slug = implode('|', $translated_slugs);
					
					$rewrite->add_rewrite_tag(
						'%'.$slug.'%', 
						'('.$slug.')', 
						'sublanguage_cpt='.$post_type.'&sublanguage_slug='
					);
	
					$rules = $rewrite->generate_rewrite_rules(
						'%'.$slug.'%', 
						$post_type_obj->rewrite['ep_mask'], // ep_mask
						$post_type_obj->rewrite['pages'], // paged
						$post_type_obj->rewrite['feeds'], // feed
						false, // forcomments
						false, // walk_dirs
						true // endpoints
					);
				
					$rewrite->rules = array_merge($rules, $rewrite->rules);
				
				
				}
				
				
			}
		
		}
		
	}
	
	/**
	 * Add rewrite rules to handle hierarchical posts translations
	 *
	 * @hook 'generate_rewrite_rules'
	 * @from 1.0
	 */
	public function subpage_rewrite_rules($rewrite) {
		global $wpdb;
		
		$post_types = get_post_types(array(
			'hierarchical' => true
		), 'names');
		
		$post_types = array_filter($post_types, array($this, 'is_post_type_translatable'));
		
		$pages = $wpdb->get_results(
			"SELECT $wpdb->posts.* FROM $wpdb->posts
			WHERE $wpdb->posts.post_type IN ('".implode("','", esc_sql($post_types))."') AND $wpdb->posts.post_status = 'publish'"
		);
		
		if (count($pages)) {
		
			foreach ($pages as $page) {
		
				if ($this->get_page_children($page->ID, $pages)) {
				
					$post_type = $page->post_type;
					$post_type_obj = get_post_type_object($post_type);
					$post_type_rewrite = $post_type_obj->rewrite;
				
					if ($post_type == 'page') {
					
						$path = '';
					
						if (!$post_type_rewrite) {
					
							$post_type_rewrite = array(
								'with_front' => true,
								'feeds' => false,
								'pages' => true,
								'ep_mask' => EP_PAGES
							);
					
						}
					
					} else {
						
						$post_type_slug = $this->get_cpt_translation($post_type, $this->get_main_language());
					
						$path = $post_type_slug.'/';

					}
					
					$path .= $this->get_page_path($page, $pages);
					
					foreach ($this->get_languages() as $language) {
				
						if ($post_type == 'page') {
					
							$translated_path = '';
						
						} else {
						
							$translated_path = $this->get_cpt_translation($post_type, $language).'/';
					
						}
						
						$translated_path .= $this->get_page_path($page, $pages, $language);
						
						if ($translated_path != $path) {
							
							$rewrite->add_rewrite_tag( 
								'%'.$translated_path.'%', 
								$translated_path.'/([^/]+)', 
								'sublanguage_cpt='.$post_type.'&sublanguage_parent='.$page->ID.'&sublanguage_path='.$path.'&sublanguage_nodepost='
							);
						
							$rules = $rewrite->generate_rewrite_rules(
								'%'.$translated_path.'%', 
								$post_type_rewrite['ep_mask'],  // ep_mask
								$post_type_rewrite['pages'], 		// paged 
								$post_type_rewrite['feeds'], 		// feed 
								false, 													// forcomments 
								false,													// walk_dirs
								true														// endpoints
							);
						
							$rewrite->rules = array_merge($rules, $rewrite->rules);

						}
										
					}
				
				}
		
			}
			
		}

	}
	
	/**
	 * Get page children from a page collection
	 *
	 * @from 2.0
	 *
	 * @param int $page_id
	 * @param array of WP_Post objects $pages
	 * @return array of WP_Post objects
	 */
	public function get_page_children($page_id, $pages) {
		
		$children = array();

		foreach ($pages as $page) {
		
			if ($page->post_parent == $page_id) {
			
				$children[] = $page;
				
			}
			
		}

    return $children;
	}
	
	/**
	 * Find page from a page collection
	 *
	 * @from 2.0
	 *
	 * @param int $page_id
	 * @param array of WP_Post objects $pages
	 * @return WP_Post object or null
	 */
	public function get_page($page_id, $pages) {
		
		foreach ($pages as $page) {
		
			if ($page->ID === $page_id) {
			
				return $page;
				
			}
			
		}

	}
	
	/**
	 * Get subpage path from a page collection
	 *
	 * @from 2.0
	 *
	 * @param WP_Post object $page
	 * @param array of WP_Post objects $pages
	 * @param WP_Post object $language. Optional
	 * @return string
	 */
	public function get_page_path($page, $pages, $language = null) {
		
		$path = $this->translate_post_field($page, 'post_name', $language);
		
		while ($page->post_parent) {
			
			$page = $this->get_page($page->post_parent, $pages);
			$page_name = $this->translate_post_field($page, 'post_name', $language);
			$path = $page_name . '/' . $path;
			
		}
		
    return $path;
	}

	/**
	 * Generate rewrite rules for taxonomies
	 *
	 * @from 1.0
	 *
	 * @hook 'generate_rewrite_rules'
	 */
	public function taxonomy_rewrite_rules($rewrite) {
		global $wpdb;
		
		$taxonomies = get_taxonomies(array(
			'public'   => true
		), 'names');
		
		$taxonomies = array_filter($taxonomies, array($this, 'is_taxonomy_translatable'));
		
		if (count($taxonomies)) {
			
			foreach ($taxonomies as $taxonomy) {
				
				$taxonomy_obj = get_taxonomy($taxonomy);
				$taxonomy_slug = $taxonomy_obj->rewrite['slug'];
				
				foreach ($this->get_languages() as $language) {
					
					$translated_slug = $this->translate_taxonomy($taxonomy, $language, $taxonomy_slug);
				
					if ($translated_slug !== $taxonomy_slug) {
						
						$rewrite->add_rewrite_tag(
							'%'.$translated_slug.'%', 
							$translated_slug.'/([^/]+)', 
							'sublanguage_tax='.$taxonomy.'&sublanguage_slug='.$translated_slug.'&sublanguage_term='
						);
						
						$rules = $rewrite->generate_rewrite_rules(
							'%'.$translated_slug.'%', 
							$taxonomy_obj->rewrite['ep_mask'], // ep_mask 
							true, // paged  
							true, // feed  
							false, // forcomments  
							false, // walk_dirs
							true // endpoints
						);
					
						$rewrite->rules = array_merge($rules, $rewrite->rules);
						
					}
					
				}
				
			}
			
		}
		
	}
	
	/**
	 * Generate rewrite rules for taxonomies "node" terms
	 *
	 * @from 1.0
	 *
	 * @hook 'generate_rewrite_rules'
	 */
	public function subterm_rewrite_rules($rewrite) {
		global $wpdb;
		
		$taxonomies = get_taxonomies(array(
			'public'   => true,
			'hierarchical' => true
		), 'names');
		
		$taxonomies = array_filter($taxonomies, array($this, 'is_taxonomy_translatable'));
		
		if (count($taxonomies)) {
			
			$taxonomies = array_map('esc_sql', $taxonomies);
			
			$terms = $wpdb->get_results(
				"SELECT t.slug, tt.taxonomy, t.term_id, tt.parent FROM $wpdb->terms AS t 
				INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy IN ('".implode("','", $taxonomies)."')"
			);
			
			if ($terms) {
				
				foreach ($terms as $term) {
		
					if ($this->get_term_children($term->term_id, $terms)) {
				
						$taxonomy = $term->taxonomy;
						$taxonomy_obj = get_taxonomy( $taxonomy );
				
						if (isset($taxonomy_obj->query_var) && $taxonomy_obj->rewrite) {
						
							$taxonomy_name = $this->translate_taxonomy($taxonomy, $this->get_main_language(), $taxonomy_obj->rewrite['slug']);
							$path = $this->get_term_path($term, $terms);
							$full_path = $taxonomy_name.'/'.$path;
												
							foreach ($this->get_languages() as $language) {
						
								$taxonomy_name = $this->translate_taxonomy($taxonomy, $language, $taxonomy_obj->rewrite['slug']);
								$translated_path = $this->get_term_path($term, $terms, $language);
								$translated_full_path = $taxonomy_name.'/'.$translated_path;
					
								if ($translated_full_path != $full_path) {
							
									$rewrite->add_rewrite_tag( 
										'%'.$translated_full_path.'%', 
										$translated_full_path.'/([^/]+)', 
										'sublanguage_tax='.$taxonomy.'&sublanguage_slug='.$taxonomy_name.'&sublanguage_parent='.$term->term_id.'&sublanguage_path='.$path.'&sublanguage_nodeterm='
									);
								
									$rules = $rewrite->generate_rewrite_rules(
										'%'.$translated_full_path.'%', 
										$taxonomy_obj->rewrite['ep_mask'], // ep_mask
										true, // paged 
										true, // feed 
										false, // forcomments 
										false, // walk_dirs
										true // endpoints
									);
							
									$rewrite->rules = array_merge($rules, $rewrite->rules);
							
								}
						
							}
				
						}
				
					}
		
				}
		
			}
			
		}
		
	}
	
	/**
	 * Get term children from terms collection
	 *
	 * @from 2.0
	 *
	 * @param int $term_id
	 * @param array of WP_Term objects $terms
	 * @return array of WP_Term objects
	 */
	public function get_term_children($term_id, $terms) {
		
		$children = array();

		foreach ($terms as $term) {
		
			if ($term->parent === $term_id) {
			
				$children[] = $term;
				
			}
			
		}

    return $children;
	}
	
	/**
	 * Find term from collection
	 *
	 * @from 2.0
	 *
	 * @param int $term_id
	 * @param array of WP_Term objects $terms
	 * @return WP_Term object or null
	 */
	public function get_term($term_id, $terms) {
		
		foreach ($terms as $term) {
		
			if ($term_id === $term->term_id) {
			
				return $term;
				
			}
			
		}

	}
	
	/**
	 * Get sub-term path from terms collection
	 *
	 * @from 2.0
	 *
	 * @param WP_Term object $term
	 * @param array of WP_Term objects $terms
	 * @param WP_Post object $language. Optional
	 * @return string
	 */
	public function get_term_path($term, $terms, $language = null) {
		
		$path = $language ? $this->translate_term_field($term, $term->taxonomy, 'slug', $language) : $term->slug;
		
		while ($term->parent) {
			
			$term = $this->get_term($term->parent, $terms);
			$slug = $language ? $this->translate_term_field($term, $term->taxonomy, 'slug', $language) : $term->slug;
			$path = $slug . '/' . $path;
			
		}
			
    return $path;
	}
	
	
	
	
	
	
	/* Options
	----------------------------------------------- */
	
	/**
	 * Ajax route to fetch options
	 *
	 * @from 1.5
	 */	
	public function ajax_export_options() {
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
	 * Save "sub" options. Save options without ajax (not used so far...)
	 *
	 * @hook 'load-options.php'
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
	public function ajax_get_option_translations() {
		
		echo json_encode($this->get_option_translations());
		
		wp_die();
	
	}
	
	/**
	 * Set option translation for ajax
	 *
	 * @from 1.5
	 */	
	public function ajax_set_option_translation() {
		
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
		
		$translations = $this->get_option('translations', array());
		
		if (empty($translations['option'])) {

			$translations['option'] = array();

		}
		
		$translations['option'] = array_replace_recursive($translations['option'], $option_tree); // only PHP 5.3 !
		
		// clean array
		$translations['option'] = $this->clean_translations($translations['option']);
		
		$this->update_option('translations', $translations);
		
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
	
	
	
	
	
	
	
	
	
	/* Import
	----------------------------------------------- */
	
	
	/**	 
	 * Import post
	 *
	 * Hook for 'sublanguage_import_post'
	 *
	 * @param array $data {
	 *		List of parameters.
	 *		If $id or $post_name is not provided, an original post (of main language) is created by passing this array to wp_insert_post().
	 *		Else only translations are created and parented to this post.
	 *
	 *		@int 	$ID (Optional) post Id
	 *		@string $post_name (Optional) post name
	 *		@string $post_type (Optional) post type. Required if ID or post_name is not set
	 *		@string $post_title (Optional) post title
	 *		@string $post_content (Optional) post content
	 *		@string $post_status (Optional) post status
	 *		@array  $sublanguages (Required) {
	 *			List of translation. One array by language
	 *
	 *			@array {
	 *				List of parameters for translation
	 *
	 *				@int|string $language (Required) Language id, slug, locale or title (123, 'en', 'en_US', 'English')
	 *				@string $post_name (Optional) Translation name
	 *				@string $post_title (Optional) Translation title
	 *				@string $post_content (Optional) Translation content
	 *				@string $post_excerpt (Optional) Translation excerpt
	 *			}
	 *		}
	 *		@mixed $xxx Refer to wp_insert_post() $postarr for a complete list of parameters
	 * }
	 *
	 * @from 1.5
	 */
	public function import_post($data) {
		global $wpdb;
		
		if (isset($data['ID']) && $data['ID']) {
			
			$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT  post.ID FROM $wpdb->posts AS post WHERE post.ID = %d", $data['ID'] ));
			
		} else if (isset($data['post_name']) && $data['post_name']) {
			
			$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT  post.ID FROM $wpdb->posts AS post WHERE post.post_name = %s", $data['post_name']));
			
		}
		
		if (empty($post_id)) {
			
			$post_id = wp_insert_post($data);
			
		}
		
		if (isset($post_id, $data['sublanguages'], $data['post_type']) && $this->is_post_type_translatable($data['post_type'])) {
			
			foreach ($data['sublanguages'] as $sub_data) {
				
				if (isset($sub_data['language'])) {
				
					$sub_data['ID'] = $post_id;
					$sub_data['post_type'] = $data['post_type'];
					$sub_data['post_status'] = get_post_field('post_status', $post_id);
					
					$language = $this->find_language($sub_data['language']);
					
					if (isset($language)) {
						
						foreach ($this->fields as $field) {
							
							if (isset($sub_data[$field])) {
							
								update_post_meta($post_id, $this->create_prefix($language->post_name).$field, $sub_data[$field]);
							
							}
							
						}
						
					}
					
				}
		
			}
			
		}
		
	}
	
	
	/**	 
	 * Import term
	 *
	 * Hook for 'sublanguage_import_term'
	 *
	 * @param string $taxonomy Taxonomy name
	 * @param array $data {
	 *		List of parameters.
	 *		If $id or $slug is not provided, original term (of main language) is created by passing $name and this array to wp_insert_term().
	 *		Else only translation are created and parented to this term.
	 *
	 *		@int 	$id term Id
	 *		@string $slug term slug
	 *		@string $name term name
	 *		@string $description term description
	 *		@int 	$parent term parent
	 *		@array  $sublanguages (Required) {
	 *			List of translation. One array by language
	 *
	 *			@array {
	 *				List of parameters for translation
	 *
	 *				@int|string $language (Required) Language id, slug, locale or title (123, 'en', 'en_US', 'English')
	 *				@string $slug (Optional) Translation slug
	 *				@string $name (Optional) Translation name
	 *				@string $name (Optional) Translation description
	 *			}
	 *		}
	 * }
	 *
	 * @from 1.5
	 */
	public function import_term($taxonomy, $data) {
		
		if ($this->is_taxonomy_translatable($taxonomy)) {
			
			if (isset($data['term_id']) && $data['term_id']) {
				
				$term = get_term_by( 'id', $data['term_id'], $taxonomy );
				
			} else if (isset($data['id']) && $data['id']) {
				
				$term = get_term_by( 'id', $data['id'], $taxonomy );
				
			} else if (isset($data['slug']) && $data['slug']) {
				
				$term = get_term_by( 'slug', $data['slug'], $taxonomy );
				
			}
			
			if (isset($term)) {
				
				$term_id = $term->term_id;
				
			}
			
			if (empty($term_id) && $data['name']) {
				
				$results = wp_insert_term( $data['name'], $taxonomy, $data );
				
				if (isset($results['term_id'])) {
				
					$term_id = $results['term_id'];
					
				}
			
			}
		
			if (isset($term_id) && isset($data['sublanguages'])) {
			
				foreach ($data['sublanguages'] as $sub_data) {
					
					if (isset($sub_data['language'])) {
						
						$language = $this->find_language($sub_data['language']);
						
						if (isset($language)) {
						
							//$this->update_term_translation($term_id, $taxonomy, $sub_data, $language->ID);
							
							$fields = array('name', 'slug', 'description');
							
							foreach ($fields as $field) {
								
								if (isset($sub_data[$field])) {
								
									update_term_meta($term_id, $this->create_prefix($language->post_name).$field, $sub_data[$field]);
								
								}
								
							}
			
						}
						
					}
					
				}
				
			}
			
		}
		
	}
	
}


