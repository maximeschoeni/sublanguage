<?php wp_nonce_field( 'language_locale_action', 'language_locale_nonce', false, true ); ?>
<input type="text" name="language_locale" value="<?php echo $post->post_content; ?>"/>
		