<?php 

class Sublanguage_permalink {
	
	/**
	 * @from 1.0
	 */
	var $permalink_translation = 'sublanguage_permalink';

	/**
	 * @from 1.0
	 */
	var $nonce = 'sublanguage_permalink_nonce';
		
	/**
	 * @from 1.0
	 */
	var $action = 'sublanguage_permalink_action';
	
	/**
	 * @from 1.0
	 */
	public function __construct() {
		
		add_action('admin_init', array($this, 'admin_init'));
		
	}
	
	/**
	 *
	 * @from 1.0
	 */
	function admin_init() {
	
		add_action( 'load-options-permalink.php', array($this, 'save_permalinks') );
		
		add_action( 'load-options-permalink.php', array($this, 'print_taxonomy_translation_form') );
		add_action( 'load-options-permalink.php', array($this, 'print_cpt_translation_form') );
		
		add_filter('rewrite_rules_array', array($this, 'rewrite_rules'), 12);
		
		add_action('generate_rewrite_rules', array($this, 'rewrite_taxonomy'));
		add_action('generate_rewrite_rules', array($this, 'rewrite_cpt'));
		add_action('generate_rewrite_rules', array($this, 'rewrite_cpt_archive'));
		
		add_rewrite_tag('%sub_tax_term%', '([^&]+)');
		add_rewrite_tag('%sub_tax_o%', '([^&]+)');
		add_rewrite_tag('%sub_tax_t%', '([^&]+)');
		add_rewrite_tag('%sub_tax_qv%', '([^&]+)');
		
		add_rewrite_tag('%sub_cpt_name%', '([^&]+)');
		add_rewrite_tag('%sub_cpt_o%', '([^&]+)');
		add_rewrite_tag('%sub_cpt_t%', '([^&]+)');
		add_rewrite_tag('%sub_cpt_qv%', '([^&]+)');
		
	}
	
	/**
	*	Filter for 'rewrite_rules_array'
	*
	* @from 1.0
	*/
	public function rewrite_rules($rules) {
		global $sublanguage_admin;
		
		$slugs = $sublanguage_admin->get_language_column('post_name');
		
		if ($slugs) {
			
			$new_rules = array();
			
			$new_rules['(?:'.implode('|', $slugs).')/?$'] = 'index.php';
		
			foreach ($rules as $key => $val) {
			
				$key = '(?:'.implode('/|', $slugs).'/)?' . $key;
			
				$new_rules[$key] = $val;
			
			}
			
			return $new_rules;
				
		}
	
		return $rules;
		
	}
	

	
	/**
	 *
	 * @from 1.0
	 */
	public function save_permalinks() {
		global $sublanguage_admin;
		
		if (isset($_POST[$this->nonce]) 
			&& wp_verify_nonce($_POST[$this->nonce], $this->action)
			&& isset($_POST[$this->permalink_translation])) {
			
			$translations = get_option($sublanguage_admin->translation_option_name);
					
			foreach ($_POST[$this->permalink_translation] as $type => $data) {
				
				foreach ($data as $language_id => $translation_data) {
							
					foreach ($translation_data as $original => $translation) {
						
						if (($type == 'taxonomy' || $type == 'cpt') 
							&& $sublanguage_admin->get_language_by($language_id, 'ID') !== false
							&& in_array($original, $sublanguage_admin->get_option($type, array()))) {
								
							$translations[$type][$language_id][$original] = sanitize_title($translation);
								
						}
					
					}
				
				}
			
			}
			
			update_option($sublanguage_admin->translation_option_name, $translations);
			
		}
		
	}
	
	/**
	 * Add a section in settings>permalink to translate taxonomies
	 *
	 * @from 1.0
	 */
	public function print_taxonomy_translation_form() {
		global $sublanguage_admin;
					
		foreach ($sublanguage_admin->get_taxonomies() as $taxonomy_name) {
		
			$taxonomy = get_taxonomy($taxonomy_name);
			
			if (isset($taxonomy->rewrite) && $taxonomy->rewrite) {
			
				add_settings_section(
					'sublanguage_taxis_section', 
					__('Translate taxonomies', 'sublanguage'), 
					array($this, 'add_taxonomy_section'), 
					'permalink' 
				);
				
				break;
			}
			
		}
		
		foreach ($sublanguage_admin->get_taxonomies() as $taxonomy_name) {
		
			$taxonomy = get_taxonomy($taxonomy_name);
			
			if (isset($taxonomy->rewrite) && $taxonomy->rewrite) {
			
				add_settings_field( 
					'sublanguage_'.$taxonomy_name, 
					isset($taxonomy->labels->name) ? $taxonomy->labels->name : $taxonomy_name, 
					array($this, 'add_field'), 
					'permalink', 
					'sublanguage_taxis_section', 
					array(
						'name' => $taxonomy_name, 
						'slug' => $taxonomy->rewrite['slug'],
						'type' => 'taxonomy'
					) 
				);
				
			}
		
		}
				
	}


	/**
	 * Add a section in settings>permalink to translate taxonomies
	 *
	 * @from 1.0
	 */
	function print_cpt_translation_form() {
		global $sublanguage_admin;
					
		foreach ($sublanguage_admin->get_post_types() as $cpt_name) {
		
			$cpt = get_post_type_object($cpt_name);
				
			if (isset($cpt) && $cpt->rewrite) {
			
				add_settings_section(
					'sublanguage_cpt_section', 
					__('Translate Custom Post Types', 'sublanguage'), 
					array($this, 'add_cpt_section'), 
					'permalink' 
				);
				break;
				
			}
		
		}
	
		foreach ($sublanguage_admin->get_post_types() as $cpt_name) {
		
			$cpt = get_post_type_object($cpt_name);
			
			if (isset($cpt) && $cpt->rewrite) {
			
				add_settings_field( 
					'sublanguage_'.$cpt_name, 
					isset($cpt->labels->name) ? $cpt->labels->name : $cpt_name, 
					array($this, 'add_field'), 
					'permalink', 
					'sublanguage_cpt_section', 
					array(
						'name' => $cpt_name, 
						'slug' => $cpt->rewrite['slug'],
						'type' => 'cpt'
					) 
				);
				
			}
			
		}
					
	}

	
	/**
	 * Add a section in settings>permalink
	 *
	 * @from 1.0
	 */
	function add_taxonomy_section() {
		
		wp_nonce_field($this->action, $this->nonce, false);
				
		echo __('You can enter translations for taxonomies. These translations will be used in the url of category/tag/term pages or taxonomy archive pages. If you leave these blank the defaults will be used.', 'sublanguage');
		
		
		
		return;
		
	}
	
	/**
	 * @from 1.0
	 */
	function add_cpt_section() {
						
		echo __('You can enter translations for custom post-types. These translations will be used in the url of custom post-type pages or custom post-type archive pages. If you leave these blank the defaults will be used.', 'sublanguage');

		return;
		
	}

	/**
	 * Add a field in section for custom-post-types
	 *
	 * @from 1.0
	 */
	function add_field($args) {
		global $sublanguage_admin;
		
		$translations = get_option($sublanguage_admin->translation_option_name);
		$languages = $sublanguage_admin->get_languages();
		
		$original = $args['name'];
		$slug = $args['slug'];
		$type = $args['type'];
			
   		foreach ($languages as $language) {
						
			if (isset($translations[$type][$language->ID][$original]) && $translations[$type][$language->ID][$original]) {
			
				$value = $translations[$type][$language->ID][$original];
				
			} else {
				
				$value = '';
			
			}
			
			echo '<label><input type="text" value="'.$value.'" name="'.$this->permalink_translation.'['.$type.']['.$language->ID.']['.$original.']" class="regular-text" />
				'.$language->post_title.'</label><br />';
			
		}
		
		echo '<p>'.sprintf(__('default: %s'), '<code>'.$slug.'</code>').'</p>';
		
		
	}
	
	
	/**
	 * @from 1.0
	 */
	function rewrite_taxonomy($rewrite) {
		global $sublanguage_admin;
		
		$translations = get_option($sublanguage_admin->translation_option_name);
		
		if (isset($translations['taxonomy']) && $translations['taxonomy']) {
			
			foreach($translations['taxonomy'] as $language_id => $data) {
			
				foreach($data as $original => $translation) {
				
					$taxonomy_obj = get_taxonomy($original);
				
					if (in_array($original, $sublanguage_admin->get_taxonomies()) 
						&& isset($taxonomy_obj->query_var, $taxonomy_obj->rewrite) 
						&& $taxonomy_obj->rewrite) {
						
						if ($translation != '' && $translation != $taxonomy_obj->rewrite['slug']) {
						
							$rewrite->add_rewrite_tag(
								'%'.$translation.'%', 
								$translation.'/([^/]+)', 
								'sub_tax_o='.$original.'&sub_tax_qv='.$taxonomy_obj->query_var.'&sub_tax_t='.$translation.'&sub_tax_term='
							);
						
							$rules = $rewrite->generate_rewrite_rules(
								'%'.$translation.'%', 
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
	
	/**
	 * @from 1.0
	 */
	function rewrite_cpt($rewrite) {
		global $sublanguage_admin;
		
		$translations = get_option($sublanguage_admin->translation_option_name);
		
		if (isset($translations['cpt']) && $translations['cpt']) {
			
			foreach($translations['cpt'] as $language_id => $items) {
				
				foreach($items as $original => $translation) {
				
					$post_type_obj = get_post_type_object($original);
				
					if (isset($post_type_obj->query_var, $post_type_obj->rewrite) && $post_type_obj->rewrite) {
					
						$post_type_rewrite = $post_type_obj->rewrite;
						$regex_cpt = '';
											
						if ($translation != '' && $translation != $post_type_obj->rewrite['slug']) {
							
							$regex_cpt .= $translation . '|'; 
							
							$rewrite->add_rewrite_tag(
								'%'.$translation.'%', 
								$translation.'/([^/]+)', 
								'sub_cpt_o='.$original.'&sub_cpt_qv='.$post_type_obj->query_var.'&sub_cpt_t='.$translation.'&sub_cpt_name='
							);
							
							$rules = $rewrite->generate_rewrite_rules(
								'%'.$translation.'%', 
								$post_type_rewrite['ep_mask'], // ep_mask
								$post_type_rewrite['pages'], // paged
								false, // feed
								true, // forcomments
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
	
	
	/**
	 * @from 1.0
	 */
	function rewrite_cpt_archive($rewrite) {
		global $sublanguage_admin;
		
		$translations = get_option($sublanguage_admin->translation_option_name);
			
		if (isset($translations['cpt']) && $translations['cpt']) {
			
			$sorted_translations = array();
			
			foreach($translations['cpt'] as $language_id => $items) {
				
				foreach($items as $original => $translation) {
				
					$sorted_translations[$original][$language_id] = $translation;
					
				}
				
			}
			
			foreach($sorted_translations as $original => $item) {
			
				$post_type_obj = get_post_type_object($original);
			
				if (isset($post_type_obj) && $post_type_obj->has_archive) {
					
					$post_type_rewrite = $post_type_obj->rewrite;
					$regex_cpt = '';
				
					foreach($item as $language_id => $translation) {
											
						if ($translation != '' && $translation != $post_type_obj->rewrite['slug']) {
							
							$regex_cpt .= $translation . '|'; 
							
						}
						
					}
					
					if ($regex_cpt) {
						
						$regex_cpt = rtrim($regex_cpt, '|');
									
						$rewrite->add_rewrite_tag(
							'%'.$regex_cpt.'%', 
							'('.$regex_cpt.')', 
							'sub_cpt_o='.$original.'&sub_cpt_qv='.$post_type_obj->query_var.'&sub_cpt_t='
						);
		
						$rules = $rewrite->generate_rewrite_rules(
							'%'.$regex_cpt.'%', 
							$post_type_rewrite['ep_mask'], // ep_mask
							$post_type_rewrite['pages'], // paged
							$post_type_rewrite['feeds'], // feed
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