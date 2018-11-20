<?php 

/**
 * @from 1.5.1
 */
class Sublanguage_Activation {
	
	
	/** 
	 * @from 1.1
	 * @from 1.5.1 Moved in Sublanguage_Activation
	 * @from 2.5 bug fix: replace cpt by post_type
	 */
	public static function activate() {
		global $sublanguage_admin;
		
		// initialization

		$options = get_option($sublanguage_admin->option_name);
		$languages = $sublanguage_admin->get_languages();
		
		if (empty($options)) {
			
			$post_id = 0;
			
			if (empty($languages)) {
			
				require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
		
				$translations = wp_get_available_translations();
				$locale = get_locale();
				$language_name = isset($translations[$locale]['native_name']) ? $translations[$locale]['native_name'] : 'English';
				$language_slug = (isset($translations[$locale]['iso']) && !empty($translations[$locale]['iso'])) ? array_shift($translations[$locale]['iso']) : 'en';
		
				$post_id = wp_insert_post(array(
					'post_type' 	=> $sublanguage_admin->language_post_type,
					'post_title'    => $language_name,
					'post_name'  	=> $language_slug,
					'post_status'   => 'publish',
					'post_content' => $locale
				));
				
			}
			
			$options = array(
				'main' => $post_id,
				'default' => $post_id,
				'show_slug' => false,
				'autodetect' => false,
				'current_first' => false,
				'taxonomy' => array(
					'category' => array('translatable' => true)
				),
				'post_type' => array(
					'post' => array('translatable' => true), 
					'page' => array('translatable' => true)
				),
				'frontend_ajax' => false,
				'need_flush' => 1,
				'version' => $sublanguage_admin->version
			);
			
			update_option($sublanguage_admin->option_name, $options);
			
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
	 * @from 1.5.1 Moved in Sublanguage_Activation
	 * @from 1.1
	 */
	public static function desactivate() {
		global $sublanguage_admin;
		
		$languages = $sublanguage_admin->get_languages();
		
		if (count($languages) < 1) {
		
			delete_option($sublanguage_admin->option_name);
		
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
	
}


