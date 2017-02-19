<?php wp_nonce_field( 'language_settings_action', 'language_settings_nonce' ); ?>
<?php $is_rtl = get_post_meta($post->ID, 'rtl', true); ?>
<label>		
	<input type="checkbox" name="language_rtl" value="1"<?php echo $is_rtl ? ' checked' : ''; ?>/>
<?php echo __('Right-to-left', 'sublanguage'); ?></label>