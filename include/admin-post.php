<?php 

class Sublanguage_admin_post {

	/**
	 * Cache for current post translation
	 *
	 * @from 1.0
	 */
	var $translation;
	
	
	/**
	 * @from 1.0
	 */
	public function __construct() {
		
		// redirect post to requested translation
		add_filter('redirect_post_location', array($this, 'language_redirect'));
		
		// on load post.php
 		add_action('load-post.php', array($this, 'admin_post_page'));
 		add_action('load-post-new.php', array($this, 'admin_post_page'));
 		
		// on load edit.php
		add_action('load-edit.php', array($this, 'admin_edit_page'));
		
	}
	
	/**
	 * Fire filters on post.php
	 *
	 * Hook for 'load-post.php'
	 *
	 * @from 1.1
	 */
	public function admin_post_page() {	
		global $sublanguage_admin;
		
		$current_screen = get_current_screen();
		
		if ($sublanguage_admin->current_language && isset($current_screen->post_type) && in_array($current_screen->post_type, $sublanguage_admin->get_post_types())) {

			add_filter('editable_slug', array($this, 'translate_slug'));
			
			// add translate meta box for posts
			if (!$sublanguage_admin->get_option('ajax_post_admin', false)) {
			
				add_action('add_meta_boxes', array($this, 'add_meta_box'));
			
			}
			
			// display current language
			add_action('edit_form_top', array($this, 'display_current_language'));
			
			// post title placeholder
			add_filter('enter_title_here', array($this, 'title_placeholder'), 10, 2);
			
			add_action('edit_form_top', array($this, 'edit_form')); 
			
			add_action('pre_get_posts', array($this, 'translate_revisions'));
			
			add_filter('home_url', array($sublanguage_admin,'translate_home_url'), 10, 4);
			
		}
		
		
		// redirect translation post-type
		if (isset($current_screen->post_type) 
			&& $sublanguage_admin->get_language_by_type($current_screen->post_type)
			&& isset($_GET['post'])) {
			
			$language = $sublanguage_admin->get_language_by_type($current_screen->post_type);
			
			$translation = get_post(intval($_GET['post']));
			
			if ($translation) {
				
				wp_redirect(add_query_arg(array(
					'post' => $translation->post_parent, 
					'action' => 'edit',
					$sublanguage_admin->language_query_var => $language->post_name
				), admin_url('post.php')));
				
				exit;
				
			}
			
		}
		
	}


	/**
	 * When quering for revisions, query current language's revision instead
	 * Hook for 'pre_get_posts'
	 *
	 * @from 1.0
	 */		
	public function translate_revisions($wp_query) {
		
		if (isset($wp_query->query_vars['post_type'], $wp_query->query_vars['post_parent']) && $wp_query->query_vars['post_type'] == 'revision') {
			
			$translation = $this->get_translation($wp_query->query_vars['post_parent']);
			
			if ($translation) {
			
				$wp_query->query_vars['post_parent'] = $translation->ID;
			
			}
			
		}
		
	}
	

	/**
	 * Change the values of $post at the begin of form in post.php
	 * Hook for 'edit_form_top'
	 *
	 * @from 1.0
	 */		
	public function edit_form($post) {
		global $sublanguage_admin;
		
		if ($sublanguage_admin->is_sub()) {
		
			$this->translation = $sublanguage_admin->get_post_translation($post->ID, $sublanguage_admin->current_language->ID);
			
			if ($this->translation) {
				
				$post->post_title = $this->translation->post_title;
				$post->post_content = $this->translation->post_content;
				$post->post_excerpt = $this->translation->post_excerpt;
				$post->post_name = $this->translation->post_name;
			
			} else {
			
				$post->post_title = '';
				$post->post_content = '';
				$post->post_excerpt = '';
				$post->post_name = '';
				
			}
		
		}
			
	}
	
	/**
	 * Get and cache post translation
	 *
	 * @from 1.0
	 */		
	public function get_translation($post_id) {
		global $sublanguage_admin;
			
		if ($sublanguage_admin->is_sub()) {
	
			return $sublanguage_admin->get_post_translation($post_id, $sublanguage_admin->current_language->ID);
	
		}
			
		return false;
	}
	
	/**
	 * Add translation meta-box on post edit form.
	 *
	 * @from 1.0
	 */
	public function add_meta_box($post_type) {
	
		add_meta_box(
			'sublanguage-switch',
			__( 'Language', 'sublanguage' ),
			array($this, 'meta_box_callback'),
			$post_type,
			'side',
			'high'
		);
				
	}
	
	/**
	 * Print meta-box language switch.
	 *
	 * @from 1.0
	 */
	public function meta_box_callback( $post ) {
		global $sublanguage_admin;
		
		wp_nonce_field( 'language_switch_action', 'language_switch_nonce' );
		
		if (has_action('sublanguage_admin_custom_switch')) {
			
			/**
			 * Customize language switch output in metabox
			 *
			 * @from 1.2
			 *
			 * @param Sublanguage_admin $sublanguage_admin Sublanguage_admin instance.
			 */
			do_action('sublanguage_admin_custom_switch', $sublanguage_admin);
		
		} else {
		
			echo $this->print_language_switch();
			
		}
		
		echo '<input type="hidden" name="'.$sublanguage_admin->language_query_var.'" value="'.$sublanguage_admin->current_language->post_name.'"/>';
		
	}
	
	/**
	 *
	 * @from 1.0
	 */
	public function print_language_switch() {
		global $sublanguage_admin;
		
		$languages = $sublanguage_admin->get_languages();
		
		$name = 'post_language_switch';
		
		$html = sprintf('<select name="'.$name.'">');
		
		foreach ($languages as $lng) {
			
			$html .= sprintf('<option value="%s"%s>%s</option>', 
				$lng->post_name,
				($sublanguage_admin->current_language->ID == $lng->ID ? ' selected' : ''),
				$lng->post_title
			);
		
		}
		
		$html .= '</select>';
		
		$html .= '		
<script type="text/javascript">
	//<![CDATA[
		jQuery(document).ready(function($) {
			$("select[name='.$name.']").change(function() {
				$(this).closest("form").find("input#save[type=submit]").click();
			});
		});
	//]]>
</script>';
			
		return $html;
		
	}
	

	/*
	 * Set requested language
	 * Filter for 'wp_redirect'
	 *
	 * @from 1.0
	 */
	public function language_redirect( $location ) {
		global $sublanguage_admin;
		
		if (isset($_POST['post_language_switch'])) { // if language change requested
			
			if ($sublanguage_admin->get_language_by($_POST['post_language_switch'], 'post_name')) {
			
				$location = add_query_arg(array($sublanguage_admin->language_query_var => esc_attr($_POST['post_language_switch'])), $location);
			
			}
			
		}
		
		return $location;
	
	}
	

	
	/**
	 * Translate slug
	 * Hook for 'editable_slug'
	 *
	 * @from 1.0
	 */	
	public function translate_slug($postname) {
		global $post, $sublanguage_admin;
		
		// can be either the current post name or ancestor for hierarchical posts
		
		if ($postname && $sublanguage_admin->is_sub()) {

			$translation = $this->get_postname_translation($postname, $sublanguage_admin->current_language->ID);
		
			if (isset($translation) && $translation) {
			
				return $translation;
		
			} else if (isset($post->ID, $this->translation->post_parent) && $this->translation->post_parent == $post->ID) {
				
				if (isset($this->translation->post_name) && $this->translation->post_name) {
			
					return $this->translation->post_name;
				
				}
			
			}

		}
		
		return $postname;
		
	}

	/**
	 * Get slug translation from original slug
	 *
	 * @from 1.0
	 */	
	public function get_postname_translation($post_name, $language_id) {
		global $wpdb, $sublanguage_admin;
		
		return $wpdb->get_var( $wpdb->prepare(
			"SELECT translation.post_name FROM $wpdb->posts AS translation 
				INNER JOIN $wpdb->posts ON ($wpdb->posts.ID = translation.post_parent)
				WHERE $wpdb->posts.post_name = %s AND translation.post_type = %s",
			$post_name, 
			$sublanguage_admin->post_translation_prefix.$language_id
		));
		
	}
	
	/**
	 * fire filters on edit.php
	 *
	 * Hook for 'load-edit.php'
	 *
	 * @from 1.2
	 */
	public function admin_edit_page() {	
		global $sublanguage_admin;
		
		$options = get_option($sublanguage_admin->option_name);
		$current_screen = get_current_screen();
		
		if ($sublanguage_admin->current_language && isset($current_screen->post_type) && in_array($current_screen->post_type, $options['cpt'])) {
			
			// print switch
			add_filter('views_'.$current_screen->id, array($this, 'table_views'));
			add_action('restrict_manage_posts', array($this, 'print_table_filtering'));
			
		}
	
	}
	
	/**
	 * Print language switch for posts table
	 * Add language param in view links
	 *
	 * Filter for "views_{$this->screen->id}" ('views_edit-post')
	 *
	 * @from 1.2
	 */	
	public function table_views($views) {
		global $sublanguage_admin;
		
		$new_views = array();
		$new_views[] = $this->print_table_view_language_switch($sublanguage_admin->language_query_var);
		
		foreach ($views as $view) {
			
			if (preg_match('/href=[\'"]([^\'"]*)/', $view, $matches)) {
			
				$match_decoded = html_entity_decode($matches[1]); //handle HTML encoding in links with existing parameters (IE in WooCommerce "Sort Products" link) // thx to @delacqua
				$new_views[] = str_replace($matches[1], add_query_arg(array($sublanguage_admin->language_query_var => $sublanguage_admin->current_language->post_name), $match_decoded), $view);
			
			} else {
				
				$new_views[] = $view;
				
			}
			
		}
		
		return $new_views;
	}
	
	/**
	 *
	 * @from 1.2
	 */
	public function print_table_view_language_switch($name) {
		global $sublanguage_admin;
		
		$languages = $sublanguage_admin->get_languages();
		
		$html = '<form method="get" style="display:inline">';
		if (isset($_GET['post_status'])) $html .= '<input type="hidden" name="post_status" value="'.esc_attr($_GET['post_status']).'">';
		if (isset($_GET['post_type'])) $html .= '<input type="hidden" name="post_type" value="'.esc_attr($_GET['post_type']).'">';
		$html .= '<select name="'.$name.'">';
		
		foreach ($languages as $lng) {
			
			$html .= sprintf('<option value="%s"%s>%s</option>', 
				$lng->post_name,
				($sublanguage_admin->current_language->ID == $lng->ID ? ' selected' : ''),
				$lng->post_title
			);
		
		}
		
		$html .= '</select>';
		$html .= '<noscript><input type="submit" class="button"/></noscript>';
		$html .= '</form>';
		$html .= '		
<script type="text/javascript">
	//<![CDATA[
		jQuery(document).ready(function($) {
			$("select[name='.$name.']").change(function() {
				$(this).closest("form").submit();
			});
		});
	//]]>
</script>';
			
		return $html;
		
	}	

	/**
	 * Add language switch on posts table
	 *
	 * Hook for 'restrict_manage_posts'
	 *
	 * @from 1.1
	 */
	public function print_table_filtering() {
		global $sublanguage_admin;
		
		echo '<input type=hidden name="'.$sublanguage_admin->language_query_var.'" value="'.$sublanguage_admin->current_language->post_name.'"/>';
		
	}

	/**
	 * Display current language on top of post.php form
	 *
	 * Hook for 'edit_form_top'
	 *
	 * @from 1.2
	 */	
	public function display_current_language($post) {
		global $sublanguage_admin;
		
		if (has_action('sublanguage_admin_display_current_language')) {
			
			/**
			 * Customize display current language on top of post.php form
			 *
			 * @from 1.2
			 *
			 * @param WP_Post current language post
			 * @param Sublanguage_admin $this Sublanguage_admin instance.
			 */
			do_action('sublanguage_admin_display_current_language', $sublanguage_admin);
		
		} else if ($sublanguage_admin->get_option('ajax_post_admin', false)) {
			
			echo $this->print_language_tabs();
		
		} else {
		
			echo '<h3 style="margin-top:60px">'.$sublanguage_admin->current_language->post_title.'</h3>';
		
		}
		
		
		
	}
	
	/**
	 * Customize title placeholder
	 *
	 * Filter for 'enter_title_here'
	 *
	 * @from 1.2
	 */	
	public function title_placeholder($title, $post) {
		global $sublanguage_admin;
		
		if ($sublanguage_admin->is_sub()) {
			
			return get_post($post->post_parent)->post_title;
		
		}
		
		return $title;
	}
	
	/*
	 * Renders language switch tab
	 * 
	 * @from 1.5
	 */
	public function print_language_tabs() {
		global $sublanguage_admin;
		
		$languages = $sublanguage_admin->get_languages();
		
		$name = 'post_language_switch';
		
		$html = '<input type="hidden" name="'. $name . '" value="' . $sublanguage_admin->current_language->post_name . '">';
		$html .= '<input type="hidden" name="'.$sublanguage_admin->language_query_var.'" value="'.$sublanguage_admin->current_language->post_name.'"/>';
		$html .= '<h2 style="margin-top:20px" class="nav-tab-wrapper sublanguage_language_tabs">';
		
		foreach ($languages as $lng) {
			
			$html .= '<a id="' . $lng->post_name . '" class="nav-tab' . ($sublanguage_admin->current_language->ID == $lng->ID ? ' nav-tab-active' : '') . '" href="#">' . $lng->post_title . '</a>';

		}
		
		$html .= '</h2>';
		
		$html .= '		
<script type="text/javascript">
	//<![CDATA[
		jQuery(document).ready(function($) {
			$(".sublanguage_language_tabs a").click(function() {
				var $form = $(this).closest("form");
				$form.find("input[name=' . $name . ']").val(this.id);
				$form.find("input:submit").first().click();
				return false;
			});
		});
	//]]>
</script>';
			
		return $html;
		
	}
	
}


