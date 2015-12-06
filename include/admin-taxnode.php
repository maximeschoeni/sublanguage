<?php 

class Sublanguage_hierarchical_taxonomies {
	

	
	/**
	 * @from 1.0
	 */
	public function __construct() {
		
		add_action('generate_rewrite_rules', array($this, 'hierarchical_taxonomy_rewrite_rules'));
		
		// add rewrite tag
		add_action('admin_init', array($this, 'admin_init'));
		
		// flush permalink when editing term
		add_action('edit_term', array($this, 'update_term_permalinks'), 10, 3);
		
		// flush permalink when deleting term
		add_action('delete_term', array($this, 'update_term_permalinks'), 10, 3);
				
	}
	
	/**
	 *
	 * @from 1.0
	 */
	function admin_init() {
		
		add_rewrite_tag('%nodeterm_tax%', '([^&]+)');
		add_rewrite_tag('%nodeterm_ttax%', '([^&]+)');
		add_rewrite_tag('%nodeterm_parent%', '([^&]+)');
		add_rewrite_tag('%nodeterm_path%', '([^&]+)');
		add_rewrite_tag('%nodeterm_qv%', '([^&]+)');
		add_rewrite_tag('%nodeterm%', '([^&]+)');
			
	}
	
	
	/**
	 * rebuild permalink when saving term
	 * Hook for 'edit_term'
	 */
	public function update_term_permalinks($term_id, $tt_id, $taxonomy) {
		global $sublanguage_admin;
				
		$taxonomy_obj = get_taxonomy($taxonomy);
		
		if (in_array($taxonomy, $sublanguage_admin->get_taxonomies()) && $taxonomy_obj->hierarchical) {
			
			$sublanguage_admin->disable_translate_home_url = true;
			flush_rewrite_rules();
			$sublanguage_admin->disable_translate_home_url = false;
				
		}
		
	}
	

	
	
	/**
	 * @from 1.0
	 */
	public function hierarchical_taxonomy_rewrite_rules($rewrite) {
		global $sublanguage_admin;
		
		$nodes = $this->get_hierarchical_term_nodes();
		
		if (count($nodes)) {
			
			foreach ($nodes as $node) {
				
				$sublanguage_admin->enqueue_term_id($node->term->term_id);
				
			}
					
			foreach ($nodes as $node) {
		
				if (count($node->children)) {
				
					$taxonomy = $node->term->taxonomy;
					$taxonomy_obj = get_taxonomy( $taxonomy );
				
					if (isset($taxonomy_obj->query_var) && $taxonomy_obj->rewrite) {
						
						$taxonomy_name = $sublanguage_admin->translate_taxonomy($taxonomy, $sublanguage_admin->get_option('main'), $taxonomy_obj->rewrite['slug']);
						$ancestors = $node->get_ancestors();
						$ancestors = array_reverse($ancestors);
						$path = ''; //$path = $taxonomy_name.'/';
				
						foreach ($ancestors as $ancestor) {
					
							$path .= $ancestor->slug.'/';
				
						}
						
						$path = rtrim($path, '/');
						
						$full_path = $taxonomy_name.'/'.$path;
						
						$rewrite->add_rewrite_tag(
							'%'.$full_path.'%', 
							$full_path.'/([^/]+)', 
							'nodeterm_tax='.$taxonomy.'&nodeterm_ttax='.$taxonomy_name.'&nodeterm_parent='.$node->term->term_id.'&nodeterm_path='.$path.'&nodeterm_qv='.$taxonomy_obj->query_var.'&nodeterm='
						);
				
						$rules = $rewrite->generate_rewrite_rules(
							'%'.$full_path.'%', 
							$taxonomy_obj->rewrite['ep_mask'], // ep_mask 
							true, // paged  
							true, // feed  
							false, // forcomments  
							false, // walk_dirs
							true // endpoints
						);
						
						$rewrite->rules = array_merge($rules, $rewrite->rules);
						
						$languages = $sublanguage_admin->get_languages();
						
						foreach ($languages as $language) {
						
							$taxonomy_name = $sublanguage_admin->translate_taxonomy($taxonomy, $language->ID, $taxonomy_obj->rewrite['slug']);
							$translated_path = '';
					
							foreach ($ancestors as $ancestor) {
								
								$translated_path .= $sublanguage_admin->translate_term_field($ancestor, $taxonomy, $language->ID, 'slug', $ancestor->slug).'/';
			
							}
							
							$translated_path = rtrim($translated_path, '/');
							
							$translated_full_path = $taxonomy_name.'/'.$translated_path;
					
							if ($translated_full_path != $full_path) {
							
								$rewrite->add_rewrite_tag( 
									'%'.$translated_full_path.'%', 
									$translated_full_path.'/([^/]+)', 
									'nodeterm_tax='.$taxonomy.'&nodeterm_ttax='.$taxonomy_name.'&nodeterm_parent='.$node->term->term_id.'&nodeterm_path='.$path.'&nodeterm_qv='.$taxonomy_obj->query_var.'&nodeterm='
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
	
	/**
	 * @from 1.0
	 */
	public function get_hierarchical_term_nodes() {
		global $wpdb, $sublanguage_admin;
				
		$taxonomies = get_taxonomies(array(
			'public'   => true,
			'hierarchical' => true
		), 'names');
		
		$taxonomies = array_intersect($taxonomies, $sublanguage_admin->get_taxonomies());
		
		$nodes = array();
		
		if (count($taxonomies)) {
			
			$taxonomies = array_map('esc_attr', $taxonomies);
			
			$terms = $wpdb->get_results(
				"SELECT t.slug, tt.taxonomy, t.term_id, tt.parent FROM $wpdb->terms AS t 
				INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy IN ('".implode("','", $taxonomies)."')"
			);
			
			foreach ($terms as $term) {
		
				$node = new Sublanguage_termnode();
				$node->term = $term;
				$nodes[$term->term_id] = $node;
	
			}
		
			foreach ($nodes as $node) {
		
				if ($node->term->parent && isset($nodes[$node->term->parent])) {
			
					$nodes[$node->term->parent]->children[] = $node;
					$node->parent = $nodes[$node->term->parent];
				
				}
		
			}
		
		}
		
		return $nodes;
	}
	

	
	
	
}






Class Sublanguage_termnode {
	
	var $term;
	var $children = array();
	var $parent;
	
	public function get_ancestors($ancestors = array()) {
		
		array_push($ancestors, $this->term);
		
		if (isset($this->parent)) {
			
			$ancestors = $this->parent->get_ancestors($ancestors);
		
		} 
		
		return $ancestors;
	}
	
}

