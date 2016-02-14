<?php 

/**
 * @from 1.4
 */
class Sublanguage_admin_attachment {
	
	/**
	 * @from 1.4
	 */
	public function __construct() {
		global $sublanguage_admin;
		
		if (in_array('attachment', $sublanguage_admin->get_post_types())) {
			
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
			
			add_filter('wp_prepare_attachment_for_js', array($this, 'prepare_attachment_for_js'), 10, 3);
			add_filter('wp_insert_attachment_data', array($this, 'insert_attachment'), 10, 2);
			add_action('edit_attachment', array($sublanguage_admin, 'save_translation_post_data'));
									
			add_filter('image_add_caption_text', array($this, 'add_caption'), 10, 2); // translate caption when send to editor
			add_filter('get_image_tag', array($this, 'get_image_tag'), 10, 6); // translate alt when send to editor
		}
		
	}

	/** 
	 * @from 1.4
	 *
	 * Enqueue Javascript (only on post pages)
	 */	
	 public function admin_enqueue_scripts($hook) {
		global $sublanguage_admin;
		
		if ($sublanguage_admin->current_language && ($hook == 'post.php' || $hook == 'post-new.php' || $hook == 'upload.php')) {
		
			wp_enqueue_media();
		
			wp_enqueue_script('sublanguage-monkey-patch-wp-media', plugin_dir_url( __FILE__ ) . 'js/attachments.js');
			wp_enqueue_style('sublanguage-style-wp-media', plugin_dir_url( __FILE__ ) . 'js/attachments-style.css');
		}
		
	}
	
	/** 
	 * Send translation for javascript
	 *
	 * Filter for 'wp_prepare_attachment_for_js'
	 *
	 * @from 1.4
	 */	
	 public function prepare_attachment_for_js($response, $attachment, $meta) {
		global $sublanguage_admin;
		
		$languages = $sublanguage_admin->get_languages();
		
		$save_current_language = $sublanguage_admin->current_language;
		
		foreach ($languages as $language) {
		
			$sublanguage_admin->current_language = $language; // -> for post meta
			
			$translation = $sublanguage_admin->get_post_translation($attachment->ID, $language->ID);
			
			$response['sublanguage'][$language->post_name] = array(
				'title' => $translation ? $translation->post_title : '',
				'alt' => $translation ? get_post_meta($translation->ID, '_wp_attachment_image_alt', true ) : '',
				'caption' => $translation ? $translation->post_excerpt : '',
				'description' => $translation ? $translation->post_content : '',
				'name' => $translation ? $translation->post_name : ''
			);
				
		}
		
		$sublanguage_admin->current_language = $save_current_language;
		
		unset($languages);
		unset($save_current_language);
		
		return $response;
	
	}
	
	/**
	 * When data are inserted from ajax, new data is filled on original attachment data. 
	 * Only field that actually changed should be updated.
	 *
	 * Filter for 'wp_insert_attachment_data'
	 *
	 * @from 1.4
	 */	
	public function insert_attachment($data, $postarr) {
		global $sublanguage_admin;
		
		$sublanguage_admin->fields = array();
		
		if (isset($_REQUEST['changes']['title'])) {
			
			$sublanguage_admin->fields[] = 'post_title';
			
		}

		if (isset($_REQUEST['changes']['name'])) {
			
			$sublanguage_admin->fields[] = 'post_name';
			
		}
				
		if (isset($_REQUEST['changes']['caption'])) {
			
			$sublanguage_admin->fields[] = 'post_excerpt';
			
		}
		
		if (isset($_REQUEST['changes']['description'])) {
			
			$sublanguage_admin->fields[] = 'post_content';
			
		}
		
		return $sublanguage_admin->insert_post($data, $postarr);
	}
	
	/**
	 * Translate caption when sending image in editor
	 *
	 * Filter for 'image_add_caption_text'
	 *
	 * @from 1.4
	 */
	public function add_caption($caption, $id) {	
		global $sublanguage_admin;
		
		if ($sublanguage_admin->current_language) {
		
			return $sublanguage_admin->translate_post_field($id, $sublanguage_admin->current_language->ID, 'post_excerpt', $caption);
		
		}
		
		return $caption;
	}

	/**
	 * Translate alt when send to editor
	 *
	 * Filter for 'get_image_tag'
	 *
	 * @from 1.4
	 */
	public function get_image_tag($html, $id, $alt, $title, $align, $size) {	
		global $sublanguage_admin;
		
		return preg_replace('/alt="[^"]*"/', 'alt="'.get_post_meta($id, '_wp_attachment_image_alt', true).'"', $html);
		
	}
	
}