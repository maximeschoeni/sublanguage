<?php 

class Sublanguage_admin_editor_button {

	/**
	 * @from 1.3
	 */
	public function __construct() {
		
		add_action('admin_head', array($this, 'admin_head'));
		
		add_action('load-post.php', array($this, 'admin_post_page'));
		
		add_action( 'wp_ajax_sublanguage_save_translation', array($this, 'save_translation'));
		
		
		// Register for Tinymce Advanced Plugin
		add_filter('tadv_allowed_buttons', array($this, 'tadv_register_button'));
		add_action('admin_head', array($this, 'tadv_set_icon'));
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
		
		if (isset($current_screen->post_type) && in_array($current_screen->post_type, $sublanguage_admin->get_post_types()) && isset($_GET['post'])) {
			
			$post_id = intval($_GET['post']);
			
			$sublanguage_admin->enqueue_post_id($post_id);
			
			add_action('admin_footer-post.php', array($this, 'print_javascript_post_translations'));
			
		}
		
	}

	/**
	 * @from 1.3
	 */
	public function admin_head() {
		global $sublanguage_admin;
		
		$current_screen = get_current_screen();
		
		if (isset($current_screen->post_type) && in_array($current_screen->post_type, $sublanguage_admin->get_post_types()) && isset($_GET['post']) && current_user_can( 'edit_posts')) {

			add_filter('mce_buttons', array($this, 'register_tinymce_button'));
			add_filter('mce_external_plugins', array($this, 'add_tinymce_button'));

		}
		 
	}
	
	/**
	 * @from 1.3
	 */
	public function register_tinymce_button( $buttons ) {
	
		 array_push( $buttons, "sublanguage");
		 
		 return $buttons;
		 
	}

	/**
	 * @from 1.3
	 */
	public function add_tinymce_button( $plugin_array ) {
	
		 $plugin_array['sublanguage'] = plugins_url('js/editor-btn.js', __FILE__);
		 
		 return $plugin_array;
	}

	/**
	 * Print post translations data for javascript
	 *
	 * Hook for 'admin_footer-{...}'
	 *
	 * @from 1.3
	 */
	public function print_javascript_post_translations() {
		global $sublanguage_admin;
		
		$post_id = intval($_GET['post']);
		
		$languages = $sublanguage_admin->get_languages($sublanguage_admin);
		$data = array();
		
		$screen = get_current_screen();
		$hidden_meta_boxes = get_hidden_meta_boxes( $screen );
		
		foreach ($languages as $language) {
			
			$translation = $sublanguage_admin->get_post_translation($post_id, $language->ID);
			
			$translation_data = array(
				'lid' => $language->ID,
				'l' => $language->post_title,
				'ls' => $language->post_name,
				'id' => (isset($translation->ID) ? $translation->ID : 0),
				't' => (isset($translation->post_title) ? $translation->post_title : ''),
				'n' => (isset($translation->post_name) ? $translation->post_name : ''),
				'c' => (isset($translation->post_content) ? $translation->post_content : '')
			);
			
			if (post_type_supports($screen->post_type, 'excerpt') && !in_array('postexcerpt', $hidden_meta_boxes)) {
			
				$translation_data['e'] = (isset($translation->post_excerpt) ? $translation->post_excerpt : '');
		
			}
			
			$data[] = $translation_data;
			
		}
		
?>
<script type="text/javascript">
	//<![CDATA[
		var sublanguageTranslations = <?php echo json_encode($data) ?>;
		var currentPostId = <?php echo $post_id; ?>;
	//]]> 
</script>
<?php
	
	}


	/**
	 * Ajax Callback. Save post.
	 *
	 * Hook for 'wp_ajax_sublanguage_save_translation'
	 *
	 * @from 1.3
	 */
	public function save_translation() {
		global $sublanguage_admin;
			
		$post_id = intval($_POST['id']);
		$response = array();
		$translations = $_POST['translations'];
		
		foreach ($translations as $translation) {
			
			$sublanguage_admin->enqueue_post_id($post_id, $translation['lng']);
		
		}
		
		foreach ($translations as $translation) {
			
			$sublanguage_admin->current_language = $sublanguage_admin->get_language_by($translation['lng'], 'post_name');
			
			$postdata = array(
				'ID' => $post_id,
				'post_content' => $translation['fields']['content'],
				'post_title' => esc_attr($translation['fields']['title']),
				'post_name' => sanitize_title($translation['fields']['slug'])
			);
			
			if (isset($translation['fields']['excerpt'])) {
				
				$postdata['post_excerpt'] = $translation['fields']['excerpt'];
			
			}
			
			$result = wp_update_post($postdata);
			
			if (!$translation['fields']['slug']) {
				$response[] = array(
					'lng' => $translation['lng'],
					'slug' => $sublanguage_admin->translate_post_field($post_id, $translation['lng'], 'post_name', get_post($post_id)->post_name)
				);
			} else if ($postdata['post_name'] != $translation['fields']['slug']) {
				$response[] = array(
					'lng' => $translation['lng'],
					'slug' => $postdata['post_name']
				);
			}
			
		}
		
		echo json_encode($response);
		
		exit;
	}


	/**
	 * Use button with Tinymce Advanced plugin
	 *
	 * Hook for 'tadv_allowed_buttons'
	 *
	 * @from 1.3
	 */
	public function tadv_register_button($buttons) {
	
		$buttons['sublanguage'] = 'Translation';
		
		return $buttons;
		
	}

	/**
	 * Use button with Tinymce Advanced plugin
	 *
	 * Hook for 'admin_head'
	 *
	 * @from 1.3
	 */
	public function tadv_set_icon() {

?>
<style>
	.mce-i-sublanguage:before {
		content: "\f326";
		font-family: "dashicons";
	}
  </style>
<?php

	}
	

}

