<?php 

wp_nonce_field( 'language_locale_dropdown_action', 'language_locale_dropdown_nonce', false, true );
		
wp_dropdown_languages(array(
	'selected' => '', 
	'languages' => array_filter($this->get_language_column('post_content')),
	'name' => 'language_locale_dropdown',
	'id' => 'language_locale_dropdown',
));
		