<?php 

class Sublanguage_hierarchical_pages {
	
	/**
	 * $translations[postID][language]
	 * @from 1.0
	 */
	var $translations = array();
	
	/**
	 * @from 1.0
	 */
	public function __construct() {
		
		// on generating rules (happens when flush)
		add_action('generate_rewrite_rules', array($this, 'subpage_rewrite_rules'));
		
		// flush rule when updating page
		add_action('post_updated', array($this, 'update_post'), null, 3);
		
		// flush rule when deleting page
		add_action('before_delete_post', array($this, 'before_delete_post'));
		
		// add rewrite tag
		add_action('admin_init', array($this, 'admin_init'));
		
	}
	
	/**
	 *
	 * @from 1.0
	 */
	function admin_init() {
		
		add_rewrite_tag('%nodepage_type%', '([^&]+)');
		add_rewrite_tag('%nodepage_parent%', '([^&]+)');
		add_rewrite_tag('%nodepage_path%', '([^&]+)');
		add_rewrite_tag('%nodepage%', '([^&]+)');
				
	}
	
	
	/**
	 * rebuild permalink when saving post if parent/name has changed
	 * Hook for 'post_updated'
	 */
	public function update_post($post_ID, $post_after, $post_before) {
		global $sublanguage_admin;
				
		// only if post is hierarchical and translatable or translation
		if ((in_array($post_after->post_type, $sublanguage_admin->get_post_types()) && is_post_type_hierarchical($post_after->post_type))
			|| $sublanguage_admin->get_language_by_type($post_after->post_type) !== false) {
			
			// only if parent or name have changed
			
			if ($post_after->post_parent != $post_before->post_parent 
				|| $post_after->post_name != $post_before->post_name
				|| $post_after->post_status != $post_before->post_status) {
				
				$sublanguage_admin->disable_translate_home_url = true;
				flush_rewrite_rules();
				$sublanguage_admin->disable_translate_home_url = false;
			}
			
		}
		
	}
	
	/**
	 * rebuild permalink when saving post if parent/name has changed
	 * Hook for 'post_updated'
	 */
	public function before_delete_post($post_id) {
		global $sublanguage_admin;
		
		$post = get_post($post_id);
		
		if (in_array($post->post_type, $sublanguage_admin->get_post_types())) {
		
			add_action('after_delete_post', array($this, 'after_delete_post'));
		
		}
		
	}
	
	/**
	 * rebuild permalink after post was deleted
	 * Hook for 'after_delete_post'
	 */
	public function after_delete_post($post_id) {
		global $sublanguage_admin;
		
		$sublanguage_admin->disable_translate_home_url = true;
		flush_rewrite_rules();
		$sublanguage_admin->disable_translate_home_url = false;
	
	}
	
	
	/**
	 * @from 1.0
	 */
	public function subpage_rewrite_rules($rewrite) {
		global $sublanguage_admin;
		
		$nodes = $this->get_page_nodes();
		
		if (count($nodes)) {
		
			$page_ids = array_keys($nodes);
		
			$sublanguage_admin->enqueue_translation($page_ids);
			$sublanguage_admin->translate_queue();
		
			foreach ($nodes as $node) {
		
				if (count($node->children)) {
				
					$ancestors = $node->get_ancestors();
					$ancestors = array_reverse($ancestors);
				
					$post_type = $node->page->post_type;
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
					
						$post_type_slug = $sublanguage_admin->get_cpt_translation($post_type, $sublanguage_admin->get_option('main'));
					
						$path = $post_type_slug.'/';

					}
								
					foreach ($ancestors as $ancestor) {
					
						$path .= $ancestor->post_name.'/';
				
					}
				
					$path = rtrim($path, '/');
				
					$rewrite->add_rewrite_tag(
						'%'.$path.'%', 
						$path.'/([^/]+)', 
						'nodepage_type='.$post_type.'&nodepage_parent='.$node->page->ID.'&nodepage_path='.$path.'&nodepage='
					);
				
					$rules = $rewrite->generate_rewrite_rules(
						'%'.$path.'%', 
						$post_type_rewrite['ep_mask'], // ep_mask 
						$post_type_rewrite['pages'], // paged  
						false, // feed  
						false, // forcomments  
						false, // walk_dirs
						true // endpoints
					);
				
					$rewrite->rules = array_merge($rules, $rewrite->rules);
				
					//do_action('sublanguage_rewrite_nodepage', $node->page->ID, $post_type, $path, $post_type_rewrite);
					
					$languages = $sublanguage_admin->get_languages();
					
					foreach ($languages as $language) {
				
						if ($sublanguage_admin->is_sub($language->ID)
							&& $sublanguage_admin->get_post_translation($node->page->ID, $language->ID) !== false) {
				
							if ($post_type == 'page') {
						
								$translated_path = '';
							
							} else {
							
								$translated_path = $sublanguage_admin->get_cpt_translation($post_type, $language->ID).'/';
						
							}
						
							foreach ($ancestors as $ancestor) {

								$translated_path .= $sublanguage_admin->translate_post_field($ancestor->ID, $language->ID, 'post_name', $ancestor->post_name) . '/';
				
							}
						
							$translated_path = rtrim($translated_path, '/');
						
							if ($translated_path != $path) {
								
								$rewrite->add_rewrite_tag( 
									'%'.$translated_path.'%', 
									$translated_path.'/([^/]+)', 
									'nodepage_type='.$post_type.'&nodepage_parent='.$node->page->ID.'&nodepage_path='.$path.'&nodepage='
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

	}

	
	/**
	 * @from 1.0
	 */
	public function get_page_nodes() {
		global $wpdb, $sublanguage_admin;
				
		$hierarchical_post_types = array();
		
		foreach ($sublanguage_admin->get_post_types() as $post_type) {
		
			$obj = get_post_type_object($post_type);
			
			if (isset($obj) && $obj->hierarchical && ($obj->_builtin || $obj->rewrite)) {
			
				$hierarchical_post_types[] = $post_type;
				
			}
			
		}
		
		$in_post_type = implode("','", esc_sql($hierarchical_post_types));

		$hierarchical_posts = $wpdb->get_results(
			"SELECT $wpdb->posts.* FROM $wpdb->posts
			WHERE $wpdb->posts.post_type IN ('$in_post_type') AND $wpdb->posts.post_status = 'publish'"
		);
		
		$nodes = array();
	
		foreach ($hierarchical_posts as $page) {
		
			$node = new Sublanguage_pagenode();
			$node->page = $page;
			$nodes[$page->ID] = $node;
	
		}
		
		foreach ($nodes as $node) {
		
			if ($node->page->post_parent && isset($nodes[$node->page->post_parent])) {
			
				$nodes[$node->page->post_parent]->children[] = $node;
				$node->parent = $nodes[$node->page->post_parent];
				
			}
		
		}
		
		return $nodes;
	
	}

	
	
}




Class Sublanguage_pagenode {
	
	var $page;
	var $children = array();
	var $parent;
	
	public function get_ancestors($ancestors = array()) {
		
		array_push($ancestors, $this->page);
		
		if (isset($this->parent)) {
			
			$ancestors = $this->parent->get_ancestors($ancestors);
		
		} 
		
		return $ancestors;
	}
	
}

