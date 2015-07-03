<?php

class Sublanguage_menu {
  
	public function __construct() {
	
		add_action('admin_init', array($this, 'add_meta_box'));
		
	}

	/**
	 * Adds the meta box container
	 */
	public function add_meta_box(){
	
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
	
}