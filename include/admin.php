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
		
		parent::__construct();
		
		add_action('init', array($this, 'update'), 15);
		
		add_action('plugins_loaded', array($this, 'find_current_language'), 11);
		
		add_action('init', array($this, 'register_postmeta_keys'), 99);
		add_filter('get_post_metadata', array($this, 'translate_meta_data'), null, 4);
		
		add_filter('the_posts', array($this, 'translate_the_posts'), 10, 2);
		add_filter('the_posts', array($this, 'hard_translate_posts'), 20, 2);
		add_filter('the_post', array($this, 'hard_translate_post'));

		add_filter('home_url', array($this,'translate_home_url'), 10, 4); // /!\ MAY BE USED BY INTERNAL FUNCTIONS /!\
		add_filter('page_link', array($this, 'translate_page_link'), 10, 3);					
		add_filter('post_type_link', array($this, 'translate_custom_post_link'), 10, 3);
				
		add_filter('list_cats', array($this, 'translate_term_name'), 10, 2);
		add_filter('get_the_terms', array($this, 'translate_post_terms'), 10, 3); // filter in get_the_terms()
		add_filter('single_term_title', array($this, 'filter_single_term_title')); // filter term title
		add_filter('single_cat_title', array($this, 'filter_single_term_title')); // filter term title
		add_filter('single_tag_title', array($this, 'filter_single_term_title')); // filter term title
		add_filter('get_terms', array($this, 'translate_get_terms'), 10, 3); // translate post list of categories		
		
		// for ajax
		add_filter('get_term', array($this, 'translate_get_term'), 10, 2);
		
		add_filter('get_edit_post_link', array($this, 'translate_edit_post_link'), null, 3);
		
		// restore post data before post saves
		add_filter('wp_insert_post_data', array($this, 'insert_post'), 10, 2);
		
		// save post translation after post saves
		add_action('save_post', array($this, 'save_translation_post_data'), 10, 2);
		
		// delete all translations when a post is deleted
		add_action('delete_post', array($this, 'delete_post_translations'));
		
		add_filter('preview_post_link', array($this, 'translate_preview_post_link'), null , 2);
		
		// javascript for ajax
		add_action('admin_footer-post.php', array($this, 'print_javascript_ajax_hook'));	
		add_action('admin_footer-edit.php', array($this, 'print_javascript_ajax_hook'));
		add_action('admin_footer-edit-tags.php', array($this, 'print_javascript_ajax_hook'));
		
		// edit post meta data
		add_filter('update_post_metadata', array($this, 'update_translated_postmeta'), null, 5);
		add_filter('add_post_metadata', array($this, 'add_translated_postmeta'), null, 5);
		add_filter('delete_post_metadata', array($this, 'delete_translated_meta_data'), null, 5);				
		
		// term
		add_action('edit_term', array($this, 'save_term_translation'), null, 3);
		add_action('delete_term', array($this, 'delete_term_translations'), 10, 4);
				
		// translate walker for pages dropdown
		add_filter('list_pages', array($this, 'translate_list_pages'), 10 , 2);
		
		
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
			
			$options = array();
		
			$options['main'] = $post_id;
			$options['default'] = $post_id;
			$options['show_slug'] = false;
			$options['autodetect'] = false;
			$options['current_first'] = false;
			$options['taxonomy'] = array('category');
			$options['cpt'] = array('post', 'page');
			$options['version'] = '1.2';
	
			update_option($this->option_name, $options);
		
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
		
		if (!$this->options || $this->options['version'] != $this->version) {
		
			include( plugin_dir_path( __FILE__ ) . 'update.php');
		
			$old_options = get_option('sublanguage');
			
			if ($old_options) {
			
				sublanguage_update_1($this);
			
			}
			
			if ($this->options['version'] != $this->version) {
				
				sublanguage_update_2($this);
			
			}
			
			$this->options = get_option($this->option_name);
			
		}	
	
	}


	/**
	 * Restore main language post data before post saves.
	 * Filter for 'wp_insert_post_data'
	 *
	 * @from 1.0
	 */	
	public function insert_post($data, $postarr) {
		
		if (isset($this->options['cpt'], $data['post_type']) && $this->options['cpt'] && in_array($data['post_type'], $this->options['cpt'])) { // -> only for translatable post
			
			if ($this->current_language->ID != $this->options['main']) { 
				
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
				
				// store translated data
				$this->sublanguage_data[$this->current_language->ID]['post_title'] = $data['post_title'];
				$this->sublanguage_data[$this->current_language->ID]['post_content'] = $data['post_content'];
				$this->sublanguage_data[$this->current_language->ID]['post_excerpt'] = $data['post_excerpt'];
				$this->sublanguage_data[$this->current_language->ID]['post_name'] = $data['post_name'];
			
				// and restore original data
				$data['post_title'] = $post->post_title;
				$data['post_content'] = $post->post_content;
				$data['post_excerpt'] = $post->post_excerpt;
				$data['post_name'] = $post->post_name;
				
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
				
		if (isset($this->current_language->ID) && isset($this->sublanguage_data[$this->current_language->ID])) {
			
			if (in_array(get_post($post_id)->post_type, $this->options['cpt'])
				&& current_user_can('edit_post', $post_id)) {
				
				$translation = $this->get_post_translation($post_id, $this->current_language->ID);
		
				$translation_data = $this->sublanguage_data[$this->current_language->ID];
				
				if ($translation) { // -> update translation
			
					$translation_data['ID'] = $translation->ID;
				
 					$this->disable_post_filter = true;
					
					wp_update_post($translation_data);
					
 					$this->disable_post_filter = false;
					
					unset($this->post_translation_cache[$this->current_language->ID][$post_id]);
					
				} else { // -> create translation
		
					$translation_data['post_parent'] = $post_id;
					$translation_data['post_type'] = $this->post_translation_prefix.$this->current_language->ID;
					$translation_data['post_status'] = 'publish'; // or inherit ?
					
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
			
			$translation = $this->get_post_translation($post_id, $lng->ID);
			
			if ($translation) {
				
				wp_delete_post($translation->ID, true);
			
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



// 		BUGGED...
// 		
// 		if ($this->current_language->ID != $this->options['main']) {
// 			
// 			//$url = preg_replace('#/'.$this->current_language->post_name.'(/|\?)#', '', $url);
// 			
// 			if (!$this->disable_translate_home_url) {
// 				
// 				$this->disable_translate_home_url = true;
// 				
// 				$url = post_preview();
// 				$url = add_query_arg(array($this->language_query_var => $this->current_language->post_name), $url);
// 				
// 				$this->disable_translate_home_url = false;
// 				
// 			}
// 						
// 		}
		
		return $url;
		
	}

	/**
	 * Print javascript for interceptig ajax request
	 *
	 * Hook for 'admin_footer-{...}'
	 *
	 * @from 1.1
	 */
	public function print_javascript_ajax_hook() {
		
		if (isset($this->current_language->ID) && $this->current_language->ID != $this->options['main']) {
		
			$params = $this->language_query_var.'='.$this->current_language->post_name;
			$params .= '&language_switch_nonce='.wp_create_nonce('language_switch_action');
			
			// -> add nonce ?
		
?>
<script type="text/javascript">
	//<![CDATA[
		jQuery(document).ready(function($) {
			$( document ).ajaxSend(function( event, jqxhr, settings ) {
				settings.url += ((settings.url.indexOf("?") > -1) ? "&" : "?") + "<?php echo $params; ?>";
			});
		});
	//]]>
</script>
<?php
		
		}
		
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
			
		} else if (in_array($post->post_type, $this->options['cpt'])) {
			
			$translatable = in_array($meta_key, $this->postmeta_keys) || apply_filters('sublanguage_translatable_postmeta', false, $meta_key, $object_id);
		
			if ($translatable) {
				
				if ($this->current_language->ID != $this->options['main']) {
					
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
			
		} else if (in_array($post->post_type, $this->options['cpt'])) {
			
			$translatable = in_array($meta_key, $this->postmeta_keys) || apply_filters('sublanguage_translatable_postmeta', false, $meta_key, $object_id);
		
			if ($translatable) {
				
				if ($this->current_language->ID != $this->options['main']) {
		
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

		if ($this->get_language_by_type($post->post_type)) {
	
			return true; // -> exit
			
		} else if (in_array($post->post_type, $this->options['cpt'])) {
			
			$translatable = in_array($meta_key, $this->postmeta_keys) || apply_filters('sublanguage_translatable_postmeta', false, $meta_key, $object_id);
		
			if ($translatable) {
				
				if ($this->current_language->ID != $this->options['main']) {
		
					$translation = $this->get_post_translation($object_id, $this->current_language->ID);
		
					$this->disable_postmeta_filter = true;
		
					delete_metadata('post', $translation->ID, $meta_key, $meta_value, $delete_all);
		
					$this->disable_postmeta_filter = false;
		
					return true; // -> exit;
			
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
		
		if (isset($this->options['taxonomy']) && in_array($taxonomy, $this->options['taxonomy'])) {
			
			if (isset($this->current_language) && $this->current_language->ID != $this->options['main']) { 
				
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
		
		$original_term = get_term_by('id', $term_id, $taxonomy);
		$this->enqueue_terms($original_term->term_id);
		
		$languages = $this->get_languages();
		
		foreach ($languages as $language) {
		
			$translation = $this->get_term_translation($original_term, $taxonomy, $language->ID);
			
			if ($translation) {
				
				 wp_delete_term($translation->term_id, $this->term_translation_prefix.$language->ID);
			
			}
			
		}
		

	}

}


