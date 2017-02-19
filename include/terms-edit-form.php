<?php

	$languages = $this->get_languages();
			
?>
<tr>
	<th><h2><?php echo __('Translations', 'sublanguage'); ?></h2></th>
	<td><?php wp_nonce_field('sublanguage', 'sublanguage_term_nonce', false, true); ?></td>
</tr>	
<?php foreach ($languages as $language) { ?>
	<?php 
		if ($this->is_main($language)) continue;
		$slug = $this->translate_term_field($tag, $taxonomy, 'slug', $language, '');
		$name = $this->translate_term_field($tag, $taxonomy, 'name', $language, '');
		$desc = $this->translate_term_field($tag, $taxonomy, 'description', $language, '');
	?>
	<tr>
		<th>
			<label><?php echo $language->post_title; ?></label>
			<input type=hidden name="<?php echo $this->language_query_var; ?>" value="<?php echo $this->get_main_language()->post_name; ?>"/>
		</th>
		<td>
			<div style="display:flex;display: -webkit-flex;flex-wrap:wrap;-webkit-flex-wrap:wrap">
				<div style="margin-bottom:1em">
					<input name="sublanguage_term[<?php echo $taxonomy; ?>][<?php echo $language->ID; ?>][name]" type="text" value="<?php echo $name; ?>" placeholder="<?php echo $tag->name; ?>" size="40" style="box-sizing:border-box">
					<p class="description"><?php echo __('Term name', 'sublanguage'); ?></p>
				</div>
				<div style="margin-bottom:1em">
					<input name="sublanguage_term[<?php echo $taxonomy; ?>][<?php echo $language->ID; ?>][slug]" type="text" value="<?php echo $slug; ?>" placeholder="<?php echo $tag->slug; ?>" size="40" style="box-sizing:border-box">
					<p class="description"><?php echo __('Term slug', 'sublanguage'); ?></p>
				</div>
				<div style="margin-bottom:1em; width:100%">
					<textarea name="sublanguage_term[<?php echo $taxonomy; ?>][<?php echo $language->ID; ?>][description]" style="box-sizing:border-box;width:95%;"><?php echo $desc; ?></textarea>
					<p class="description"><?php echo __('Term description.', 'sublanguage'); ?></p>
				</div>
			</div>
		</td>
	</tr>
<?php } 
	