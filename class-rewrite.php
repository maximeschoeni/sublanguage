<?php 

class Sublanguage_rewrite extends Sublanguage_current {
	
	/**
	 * @from 2.0
	 */
	var $rewritable_post_types = array();
	
	/**
	 * @from 2.0
	 */
	var $rewritable_taxonomies = array();
	
	/**
	 * @from 1.4.7
	 */
	public function load() {
		
		parent::load();
		
		add_action('generate_rewrite_rules', array($this, 'generate_rewrite_rules'));
		
		add_filter('register_post_type_args', array($this, 'register_post_type_args'), 10, 2);
		add_action('registered_post_type', array($this, 'registered_post_type'), 10, 2);
		
		add_filter('register_taxonomy_args', array($this, 'register_taxonomy_args'), 10, 2);
		add_action('registered_taxonomy', array($this, 'registered_taxonomy'), 10, 3);
		
		add_filter('page_rewrite_rules', array($this, 'page_rewrite_rules'));
		
		// Append language slugs to every rules
		add_filter('rewrite_rules_array', array($this, 'append_language_slugs'), 12);
		
	}
	
	/**
	 * Update rules
	 *
	 * @hook for 'generate_rewrite_rules'
	 * @from 2.0
	 */
	public function generate_rewrite_rules($wp_rewrite) {
		
		$this->update_option('need_flush', 0);
		
	}
	
	/**
	 * Shortcut cpt rule generation
	 *
	 * @filter 'register_post_type_args'
	 * @from 2.0
	 */
	public function register_post_type_args($args, $post_type) {
		
		if ($this->is_post_type_translatable($post_type) && isset($args['rewrite']) && $args['rewrite'] !== false && get_option('permalink_structure') != '') {
			
			// -> WP_Post_Type::set_props()
			if ( ! is_array( $args['rewrite'] ) ) {
				$args['rewrite'] = array();
			}
			if ( empty( $args['rewrite']['slug'] ) ) {
				$args['rewrite']['slug'] = $post_type;
			}
			if ( ! isset( $args['rewrite']['with_front'] ) ) {
				$args['rewrite']['with_front'] = true;
			}
			if ( ! isset( $args['rewrite']['pages'] ) ) {
				$args['rewrite']['pages'] = true;
			}
			if ( ! isset( $args['rewrite']['feeds'] ) || ! $args['has_archive'] ) {
				$args['rewrite']['feeds'] = (bool) $args['has_archive'];
			}
			if ( ! isset( $args['rewrite']['ep_mask'] ) ) {
				if ( isset( $args['permalink_epmask'] ) ) {
					$args['rewrite']['ep_mask'] = $args['permalink_epmask'];
				} else {
					$args['rewrite']['ep_mask'] = EP_PERMALINK;
				}
			}
			
			$this->rewritable_post_types[$post_type] = $args['rewrite'];
			
			$args['rewrite'] = false; // fake it to skip normal rules generation
				
		}
		
		return $args;
		
	}
	
	/**
	 * Translate custom post type rewrite rules.
	 * See WP_Post_Type::add_rewrite_rules()
	 *
	 * @hook 'registered_post_type'
	 * @from 2.0
	 */
	public function registered_post_type($post_type, $post_type_obj) {
		global $wp_rewrite;
		
		if (isset($this->rewritable_post_types[$post_type])) {
			
			$post_type_obj->rewrite = $this->rewritable_post_types[$post_type];
			$post_type_obj->rewrite['walk_dirs'] = false;
			
			$translation_slugs = array();
			
			foreach ($this->get_languages() as $language) {
			
				$translation_slugs[] = $this->translate_cpt($post_type, $language, $post_type);
			
			}
		
			$translation_slugs = array_unique($translation_slugs);
			
			$translation_slug = '(' . implode('|', $translation_slugs) . ')';
			
			add_rewrite_tag( "%$post_type-slug%", $translation_slug, "sublanguage_slug=" );
			
			if ( $post_type_obj->hierarchical ) {
				add_rewrite_tag( "%$post_type%", '(.+?)', $post_type_obj->query_var ? "{$post_type_obj->query_var}=" : "post_type=$post_type&pagename=" );
			} else {
				add_rewrite_tag( "%$post_type%", '([^/]+)', $post_type_obj->query_var ? "{$post_type_obj->query_var}=" : "post_type=$post_type&name=" );
			}
			
			if ( $post_type_obj->has_archive ) {
				$archive_slug = $translation_slug;
				
				if ( $post_type_obj->rewrite['with_front'] ) {
					$archive_slug = substr( $wp_rewrite->front, 1 ) . $archive_slug;
				} else {
					$archive_slug = $wp_rewrite->root . $archive_slug;
				}

				add_rewrite_rule( "{$archive_slug}/?$", 'index.php?post_type='.$post_type.'&sublanguage_slug=$matches[1]', 'top' );
				if ( $post_type_obj->rewrite['feeds'] && $wp_rewrite->feeds ) {
					$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
					add_rewrite_rule( "{$archive_slug}/feed/$feeds/?$", 'index.php?post_type='.$post_type.'&sublanguage_slug=$matches[1]&feed=$matches[2]', 'top' );
					add_rewrite_rule( "{$archive_slug}/$feeds/?$", 'index.php?post_type='.$post_type.'&sublanguage_slug=$matches[1]&feed=$matches[2]', 'top' );
				}
				if ( $post_type_obj->rewrite['pages'] ) {
					add_rewrite_rule( "{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$", 'index.php?post_type='.$post_type.'&sublanguage_slug=$matches[1]&paged=$matches[2]', 'top' );
				}
			}

			$permastruct_args = $post_type_obj->rewrite;
			$permastruct_args['feed'] = $permastruct_args['feeds'];
			
			add_permastruct($post_type, "%$post_type-slug%/%$post_type%", $permastruct_args);
			
			// -> Get ride of attachment rules
			add_filter($post_type.'_rewrite_rules', array($this, 'drop_cpt_attachment_rules'));
			
		}
	
	}
	
	/**
	 * Get ride of attachment rules: buggy and useless!
	 *
	 * @filter "{$permastructname}_rewrite_rules"
	 * @from 2.0
	 */
	public function drop_cpt_attachment_rules($rules) {
		
		$new_rules = array();
		
		foreach ($rules as $match => $rewrite) {
		
			if (!preg_match('/(?:\?|&)attachment=/', $rewrite)) {
			
				$new_rules[$match] = $rewrite;
				
			}
			
		}
		
		return $new_rules;
	}
	
	/**
	 * Shortcut taxonomy rule generation
	 *
	 * @filter 'register_taxonomy_args'
	 * @from 2.0
	 */
	public function register_taxonomy_args($args, $taxonomy) {
		
		if ($this->is_taxonomy_translatable($taxonomy) && isset($args['rewrite']) && $args['rewrite'] !== false && get_option('permalink_structure') != '') {
			
			$args['rewrite'] = wp_parse_args( $args['rewrite'], array(
				'with_front'   => true,
				'hierarchical' => false,
				'ep_mask'      => EP_NONE,
			) );

			if ( empty( $args['rewrite']['slug'] ) ) {
				$args['rewrite']['slug'] = sanitize_title_with_dashes($taxonomy);
			}
			
			$this->rewritable_taxonomies[$taxonomy] = $args['rewrite'];
			
			$args['rewrite'] = false; // fake it to skip normal rules generation
			
		}
		
		return $args;
	}
	
	/**
	 * Translate taxonomy rewrite rules.
	 * Bypass: WP_Taxonomy::add_rewrite_rules()
	 *
	 * @hook 'registered_taxonomy'
	 * @from 2.0
	 */
	public function registered_taxonomy($taxonomy, $object_type, $taxonomy_obj) {
		global $wp, $wp_taxonomies;
		
		if (isset($this->rewritable_taxonomies[$taxonomy])) {
			
			$taxonomy_obj['rewrite'] = $this->rewritable_taxonomies[$taxonomy]; // why is $taxonomy_obj an array!?
			$taxonomy_obj['rewrite']['walk_dirs'] = false;
			
			if ($taxonomy_obj['hierarchical'] && $taxonomy_obj['rewrite']['hierarchical']) {
				$tag = '(.+?)'; // -> not supported yet!!
			} else {
				$tag = '([^/]+)';
			}
			
			$translation_slugs = array();
			
			foreach ($this->get_languages() as $language) {
			
				$translation_slugs[] = $this->translate_taxonomy($taxonomy, $language, $taxonomy);
			
			}
			
			$translation_slugs = array_unique($translation_slugs);
			
			$translation_slug = '(' . implode('|', $translation_slugs) . ')';
			
			add_rewrite_tag( "%$taxonomy-slug%", $translation_slug, 'sublanguage_slug=' );
			add_rewrite_tag( "%$taxonomy%", $tag, $taxonomy_obj['query_var'] ? $taxonomy_obj['query_var'].'=' : "taxonomy=$taxonomy&term=" );
			
			add_permastruct( $taxonomy, "%$taxonomy-slug%/%$taxonomy%", $taxonomy_obj['rewrite'] );
			
			// set back the original rewrite properties
			$wp_taxonomies[$taxonomy]->rewrite = $taxonomy_obj['rewrite'];
			
		}
		
	}
	
	/**
	 * Duplicate page rules.
	 * - bypass WP "verbose page rules" check in WP::parse_request()
	 * - deal with WooCommerce "permalink fix" that modifie page rules order
	 *
	 * @filter 'register_post_type_args'
	 * @from 2.0
	 */
	public function page_rewrite_rules($rules) {
		
		if ($this->is_post_type_translatable('page')) {
		
			$duplicate_rules = array();
		
			foreach ($rules as $key => $rule) {
			
				$key = str_replace('(.?.+?)', '(x|.?.+?)', $key);
				$rule = str_replace('pagename=', 'sublanguage_page=', $rule);
			
				$duplicate_rules[$key] = $rule;
			
			}
		
			$rules = array_merge($duplicate_rules, $rules);
			
		}
		
		return $rules;
	}
	
	
	/**
	 * Append language slugs to every rules
	 *
	 * @filter 'rewrite_rules_array'
	 * @from 2.0
	 */
	public function append_language_slugs($rules) {
		
		$language_slugs = array();
			
		foreach ($this->get_languages() as $language) {
		
			$language_slugs[] = $language->post_name;
		
		}
		
		$new_rules = array();
		
		$new_rules['(?:' . implode('|', $language_slugs) . ')/?$'] = 'index.php'; // -> rule for home
		
		$languages_slug = '(?:' . implode('/|', $language_slugs) . '/)?';
		
		foreach ($rules as $key => $rule) {
			
			if (substr($key, 0, 1) !== '^') {
			
				$new_rules[$languages_slug . $key] = $rule;
				
			} else {
				
				$new_rules[$key] = $rule;
				
			}
			
		}
		
		return $new_rules;
	}
	

	
}


