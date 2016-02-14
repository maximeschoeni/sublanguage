<?php

class Sublanguage_menu {

	/**
	 * @var array
	 */
	var $menu_languages;
  	
  	
	public function __construct() {
		
		add_action('init', array($this, 'init'), 20);
		
	}
	
	/**
	 * Init
	 */
	public function init() {
		global $sublanguage_admin;
		
		if (in_array('nav_menu_item', $sublanguage_admin->get_post_types())) {
		
			$this->register_post_type();
			
			add_action('admin_init', array($this, 'add_nav_menu_items_meta_box'));
			add_action('save_post_nav_menu_item', array($this, 'save_nav_menu_item'), 10, 2);
			
			add_filter('request', array($this, 'request'));
			add_filter('enter_title_here', array($this, 'title_placeholder'), 11, 2);
			
		}
		
		add_action('admin_init', array($this, 'add_language_meta_box'));
		
	}

	/**
	 * Add metabox on admin menu page
	 */
	public function add_language_meta_box() {
		
		add_meta_box(
			'language_menu',
			__('Language', 'sublanguage'),
			array( $this, 'render_meta_box_content' ),
			'nav-menus',
			'side',
			'high'
		);
		
	}
	
	/**
	 * Add metabox for nav menu items
	 *
	 * Hook for 'admin_init'
	 *
	 * @from 1.5
	 */
	public function add_nav_menu_items_meta_box() {
		
		add_meta_box(
			'menu-item-translation',
			__('Menu Item Object', 'sublanguage'),
			array( $this, 'render_menu_item_meta_box' ),
			'nav_menu_item',
			'normal'
		);
			
	}

	/**
	 * Render Meta Box content
	 */
	public function render_meta_box_content() {
	
 ?>
<div id="posttype-wl-login" class="posttypediv">
	<div id="tabs-panel-wishlist-login" class="tabs-panel tabs-panel-active">
		<ul id ="wishlist-login-checklist" class="categorychecklist form-no-clear">
			<li>
				<label class="menu-item-title">
					<input type="checkbox" class="menu-item-checkbox" name="menu-item[-1][menu-item-object-id]" value="-1"><?php echo _e('Add one of this for each of your languages', 'sublanguage'); ?>
				</label>
				<input type="hidden" class="menu-item-type" name="menu-item[-1][menu-item-type]" value="custom">
				<input type="hidden" class="menu-item-title" name="menu-item[-1][menu-item-title]" value="language">
				<input type="hidden" class="menu-item-url" name="menu-item[-1][menu-item-url]" value="">
				<input type="hidden" class="menu-item-classes" name="menu-item[-1][menu-item-classes]" value="sublanguage">
			</li>
		</ul>
	</div>
	<p class="button-controls">
		<span class="add-to-menu">
			<input type="submit" class="button-secondary submit-add-to-menu right" value="Add to Menu" name="add-post-type-menu-item" id="submit-posttype-wl-login">
			<span class="spinner"></span>
		</span>
	</p>
</div>
	<?php 

  }
	
	/**
	 * Set menu items visible in admin
	 *
	 * @from 1.5
	 */
	public function register_post_type() {
		global $sublanguage_admin;
		
		register_post_type('nav_menu_item', array(
			'labels'             => array(
				'name'               => __( 'Nav Menu Items', 'sublanguage' ),
				'singular_name'      => __( 'Nav Menu Item', 'sublanguage' )
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => $sublanguage_admin->page_name,
			'query_var'          => false,
			'rewrite'			 => false,
			'has_archive'        => false,
			'hierarchical'       => true,
			'menu_icon'			 => 'dashicons-translation',
			'taxonomies'		 => array('nav_menu')
		));
		
		register_taxonomy('nav_menu', array('nav_menu_item'), array(
			'hierarchical'      => true,
			'labels'            => array(
				'name' => __('Nav Menus')
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'public' 			=> true,
			'show_in_quick_edit' => false,
			'meta_box_cb' => false,
			'query_var'         => false,
			'rewrite'           => false,
		
		));
		
		remove_post_type_support( 'nav_menu_item', 'editor');
		
	}
	
	public function render_menu_item_meta_box($post) {
		
		global $sublanguage_admin, $_wp_post_type_features;
		
		$type = get_post_meta($post->ID, '_menu_item_type', true);
		$hide = get_post_meta($post->ID, 'sublanguage_hide', true);
		
		$_menu_item_type = get_post_meta($post->ID, '_menu_item_type', true);
		
		if ($_menu_item_type === 'post_type') {
				
			$_menu_item_object_id = get_post_meta($post->ID, '_menu_item_object_id', true);
			$object_post = get_post($_menu_item_object_id);
			$post_object = apply_filters( 'sublanguage_translate_post_field', $object_post->post_title, $object_post, 'post_title');
			$edit_link = add_query_arg(array($sublanguage_admin->language_query_var => $sublanguage_admin->current_language->post_name), get_edit_post_link( $_menu_item_object_id, false ));
			
		} else if ($_menu_item_type === 'taxonomy') {
	
			$_menu_item_object_id = get_post_meta($post->ID, '_menu_item_object_id', true);
			$_menu_item_object = get_post_meta($post->ID, '_menu_item_object', true);
			$object_term = get_term_by('id', $_menu_item_object_id, $_menu_item_object);
			$term_object = $object_term->name;
			
			
		} else if ($_menu_item_type === 'custom') {
		
			$url = get_post_meta($post->ID, '_menu_item_url', true);
			
		}
		
		 wp_nonce_field( 'sublanguage_menu_nav_item_action', 'sublanguage_menu_nav_item_nonce', false, true );
		
?>
<table>
	<tbody>
		<?php if (isset($post_object)) { ?>
			<tr><td><label><?php echo __('Page Title'); ?></label></td><td><input type="text" value="<?php echo $post_object; ?>" readonly/> (<a href="<?php echo $edit_link ?>"><?php echo __('edit'); ?></a>)</td></tr>
		<?php } else if (isset($term_object)) { ?>
			<tr><td><label><?php echo __('Term Name'); ?></label></td><td><input type="text" value="<?php echo $term_object; ?>" readonly/> <?php edit_term_link( 'edit', '(', ')', $object_term, true ); ?></td></tr>
		<?php } else if (isset($url)) { ?>
			<tr><td><label><?php echo __('URL'); ?></label></td><td><input type="text" name="sublanguage_menu_item_url" value="<?php echo $url; ?>"/></td></tr>
		<?php } ?>
		<tr><td><label><?php echo __('Title Attribute'); ?></label></td><td><input type="text" name="excerpt" value="<?php echo $post->post_excerpt; ?>"/></td></tr>
		<tr><td><label><?php echo __('Description'); ?></label></td><td><input type="text" name="content" value="<?php echo $post->post_content; ?>"/></td></tr>
		<tr><td><label><?php echo __('Hide'); ?></label></td><td><label><input type="checkbox" name="sublanguage_menu_item_hide" value="1" <?php if ($hide) echo ' checked'; ?>/><?php echo __('Hide this menu item in this language', 'sublanguage'); ?></label></td></tr>
	</tbody>
</table>
<?php
	
	}
	
	
	/**
	 * Customize title placeholder
	 *
	 * Filter for 'enter_title_here'
	 *
	 * @from 1.5
	 */	
	public function title_placeholder($title, $post) {
		global $sublanguage_admin;
		
		if ($post->post_type === 'nav_menu_item') {
			
			$_menu_item_type = get_post_meta($post->ID, '_menu_item_type', true);
			
			if ($_menu_item_type === 'post_type') {
				
				$_menu_item_object_id = get_post_meta($post->ID, '_menu_item_object_id', true);
			
				$object_post = get_post($_menu_item_object_id);
			
				$title = apply_filters( 'sublanguage_translate_post_field', $object_post->post_title, $object_post, 'post_title');
			
			
			} else if ($_menu_item_type === 'taxonomy') {
		
				$_menu_item_object_id = get_post_meta($post->ID, '_menu_item_object_id', true);
				$_menu_item_object = get_post_meta($post->ID, '_menu_item_object', true);
				
				$object_term = get_term_by('id', $_menu_item_object_id, $_menu_item_object);
				
				$title = $object_term->name;
				
			} else if ($_menu_item_type === 'custom') {
			
				$title = $post->post_title;
			
			}
			
		}
		
		return $title;
	}
	
	public function request($query_vars) {

		if ($query_vars['post_type'] == 'nav_menu_item') {
			
			add_filter('the_posts', array($this, 'the_menu_item_posts'), 15);
			add_filter('the_posts', array($this, 'replace_language_keyword'), 14);
			add_filter('the_title', array($this, 'translate_nav_menu_item_title'), 10, 2);
			add_filter('posts_clauses', array($this, 'orderby_nav_menu_term'), 10, 2);
			
		}
		
		return $query_vars;
	}
	
	/**
	 * Correct menu items parents
	 *
	 * @from 1.5
	 */
	public function the_menu_item_posts($posts) {
		global $sublanguage_admin;
		
		foreach ($posts as $post) {
			
			if ($post->post_type === 'nav_menu_item') {
			
				$_menu_item_menu_item_parent = get_post_meta($post->ID, '_menu_item_menu_item_parent', true);
				
				$post->post_parent = $_menu_item_menu_item_parent;
				
			}
			
		}
		
		return $posts;
	}
	
	/**
	 * Translate nav menu items
	 *
	 * Hook for the_title
	 *
	 * @from 1.5
	 */
	public function translate_nav_menu_item_title($title, $post_id) {
		global $sublanguage_admin;
		
		$post = get_post($post_id);
		
		if ($post->post_type === 'nav_menu_item') {
			
			$title = apply_filters( 'sublanguage_translate_post_field', $post->post_title, $post, 'post_title');

			$_menu_item_type = get_post_meta($post_id, '_menu_item_type', true);
			
			if ($_menu_item_type === 'post_type') {
				
				if (!$title) {
				
					$_menu_item_object_id = get_post_meta($post_id, '_menu_item_object_id', true);
				
					$object_post = get_post($_menu_item_object_id);
				
					$title = apply_filters( 'sublanguage_translate_post_field', $object_post->post_title, $object_post, 'post_title');
				
				}
				
			} else if ($_menu_item_type === 'taxonomy') {
			
				if (!$title) {
				
					$_menu_item_object_id = get_post_meta($post_id, '_menu_item_object_id', true);
					$_menu_item_object = get_post_meta($post_id, '_menu_item_object', true);
					
					$object_term = get_term_by('id', $_menu_item_object_id, $_menu_item_object);
					
					$title = $object_term->name;
					
				}
				
				
			} else if ($_menu_item_type === 'custom') {
				
				if ($title === 'language' && isset($this->menu_languages[$post->ID])) {
					
					$title = $this->menu_languages[$post->ID]->post_title;
					
				}
				
			}
			
		}
		
		return $title;
	}
	
	
	public function replace_language_keyword($posts) {
		global $sublanguage_admin;
		
		$menu_language_index = 0;
		$languages = $sublanguage_admin->get_languages();
		
		foreach ($posts as $post) {
			
			if ($post->post_type === 'nav_menu_item' && $post->post_title === 'language' && get_post_meta($post->ID, '_menu_item_type', true) === 'custom' && isset($languages[$menu_language_index])) {
				
				if (empty($this->menu_languages)) {
					
					$this->menu_languages = array();
					
				}
				
				$this->menu_languages[$post->ID] = $languages[$menu_language_index];
				
				$menu_language_index++;
				
			}
			
		}
		
		return $posts;
	}
	
	/**
	 * Order wp_query by nav menu term
	 *
	 * Filter for posts_clauses
	 *
	 * @from 1.5
	 */
	public function orderby_nav_menu_term($clauses, $wp_query ) {
		global $wpdb;
		
		if (isset($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] === 'nav_menu_item') {
			
			$clauses['join'] .= "
				INNER JOIN {$wpdb->term_relationships} AS tr ON {$wpdb->posts}.ID = tr.object_id
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
				
			$clauses['orderby'] = "tt.term_id";
				
		}
		return $clauses;
	
	}
	
	/**
	 * Save nav menu item
	 *
	 * Hook for "save_post_{$post->post_type}"
	 *
	 * @from 1.5
	 */
	public function save_nav_menu_item($post_id, $post) {
		
		if ((!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE)	&& current_user_can('edit_post', $post_id)) {
			
			if (isset($_POST['sublanguage_menu_nav_item_nonce']) && wp_verify_nonce($_POST['sublanguage_menu_nav_item_nonce'], 'sublanguage_menu_nav_item_action' )) {
					
				update_post_meta($post_id, 'sublanguage_hide', (empty($_POST['sublanguage_menu_item_hide']) ? 0 : 1));
				
				if (isset($_POST['sublanguage_menu_item_url'])) {
				
					update_post_meta($post->ID, '_menu_item_url', esc_url($_POST['sublanguage_menu_item_url']));
					
				}
				
			}
			
		}
		
	}
	
	
	
}