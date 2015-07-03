<?php 

/**
 *	@from 1.1
 */
function sublanguage_update_1($sublanguage_admin) {
	global $wpdb;
	
	$old_options = get_option('sublanguage');

	$new_options = array();
	
	foreach ($old_options['lng'] as $language_slug => $language) {

	  $lng_id = $wpdb->get_var($wpdb->prepare(
		"SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE post_type=%s AND post_name=%s", 
		$sublanguage_admin->language_post_type,
		$language_slug));
	

	  $new_options['lng'][] = array(
		'id' => $lng_id,
		'slug' => $language_slug,
		'name' => $language['name'],
		'locale' => $language['locale'],
		'rtl' => false // who care ?
	  );

	  if ($language_slug == $old_options['main']) {

		$new_options['main'] = $lng_id;

	  }

	}
	
	$new_options['taxonomy'] = $old_options['taxonomies'];
	$new_options['cpt'] = $old_options['cpt'];
	$new_options['show_slug'] = false;
	$new_options['version'] = '1.1';
		
	foreach ($new_options['lng'] as $language) {

	  $language_slug = $language['slug'];

	  $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_type=%s WHERE post_type=%s",
		$sublanguage_admin->post_translation_prefix.$language['id'],
		$language_slug
	  ));

	}

	update_option($sublanguage_admin->option_name, $new_options);

}

/**
 *	@from 1.2
 */
function sublanguage_update_2($sublanguage_admin) {
	
	
	$options = get_option($sublanguage_admin->option_name);
	
	$options['default'] = $options['main'];
	$options['show_slug'] = false;
	//$options['show_edit_lng'] = false;
	$options['autodetect'] = false;
	$options['current_first'] = false;
	
	if (isset($options['lng']) && $options['lng']) {
	
		foreach ($options['lng'] as $lng) {
	
			wp_update_post(array(
				'ID' => $lng['id'],
				'post_content' => $lng['locale']
			));
	
		}
		
	}

	unset($options['lng']);

	$options['version'] = '1.2';
	
	update_option($sublanguage_admin->option_name, $options);

	
	$translations = get_option($sublanguage_admin->translation_option_name);
	
	if (isset($translations['term']) && $translations['term']) {
	
		foreach ($translations['term'] as $taxonomy => $data) {
		
			foreach ($data as $language_id => $subdata) {
			
				foreach ($subdata as $term_id => $translation) {
					
					$original_term = get_term_by('id', $term_id, $taxonomy);
					
					$name = (isset($translation['name']) && $translation['name']) ? $translation['name'] : $original_term->name;
					$slug = (isset($translation['slug']) && $translation['slug']) ? $translation['slug'] : $original_term->slug;
					
					wp_insert_term($name, $sublanguage_admin->term_translation_prefix.$language_id, array(
						'slug' => $slug,
						'parent' => $term_id
					));
				
				}
			
			}
		
		}
	
	}
	
	unset($translations['term']);
	
	update_option($sublanguage_admin->translation_option_name, $translations);
	

}










