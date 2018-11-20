<?php wp_nonce_field( 'language_locale_action', 'language_locale_nonce', false, true ); ?>
<label>
	<input type="text" name="language_locale" value="<?php echo $post->post_content; ?>"/>
	<?php echo __('Locale string for language, eg: en_GB, es_ES, es_MX, pt_BR, etc. Used for localization files', 'sublanguage'); ?>
</label>