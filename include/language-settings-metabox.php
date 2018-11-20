<?php 
	wp_nonce_field( 'language_settings_action', 'language_settings_nonce' );
?>

<label>
	<input type="text" name="language_tag" value="<?php echo $post->post_excerpt; ?>"/>
	<?php echo __('IETF language tag. Eg: en, en-gb, es, es-es, es-mx, etc. Used for browsers language detection & SEO', 'sublanguage'); ?>
</label>