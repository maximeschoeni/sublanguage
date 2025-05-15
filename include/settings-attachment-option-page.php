<form action="<?php echo admin_url(); ?>" method="POST">
	<?php wp_nonce_field('sublanguage_action', 'sublanguage_post_option', true, true); ?>
	<input type="hidden" name="post_type" value="<?php echo $post_type; ?>">
	<h2><?php echo sprintf(__('%s Language Options', 'sublanguage'), isset($post_type_obj->label) ? $post_type_obj->label : $post_type); ?></h2>
	<nav><a href="<?php echo admin_url('options-general.php?page=sublanguage-settings'); ?>"><?php echo __('Sublanguage Settings', 'sublanguage'); ?></a></nav>
	<table class="form-table">
		<tbody>
			<tr>
				<th><?php echo __('Translatable post fields', 'sublanguage'); ?></th>
				<td>
					<ul>
						<ul>
							<li><label><input type="checkbox" name="fields[]" value="post_title" <?php echo (in_array('post_title', $this->get_post_type_fields('attachment')) ? ' checked' : ''); ?>/><?php echo __('Image Title', 'sublanguage') ?></label></li>
							<li><label><input type="checkbox" name="fields[]" value="post_excerpt" <?php echo (in_array('post_excerpt', $this->get_post_type_fields('attachment')) ? ' checked' : ''); ?>/><?php echo __('Image Caption', 'sublanguage') ?></label></li>
							<li><label><input type="checkbox" name="fields[]" value="post_content" <?php echo (in_array('post_content', $this->get_post_type_fields('attachment')) ? ' checked' : ''); ?>/><?php echo __('Image Description', 'sublanguage') ?></label></li>
						</ul>
					</ul>
				</td>
			</tr>
			<tr>
				<?php if ($meta_keys) { ?>
					<th><?php echo __('Translatable post meta', 'sublanguage'); ?></th>
					<td>
						<ul>
							<?php foreach ($meta_keys as $key => $values) { ?>
								<li><label title="value sample: '<?php echo isset($values[0]) ? $values[0] : ''; ?>'"><input type="checkbox" name="meta_keys[]" value="<?php echo $key; ?>" <?php if (in_array($key, $this->get_post_type_metakeys($post_type))) echo 'checked'; ?>/><?php echo isset($registered_meta_keys[$key]['description']) && $registered_meta_keys[$key]['description'] ? $registered_meta_keys[$key]['description'] : $key; ?></label></li>
							<?php } ?>
						</ul>
					</td>
				<?php } ?>
			</tr>
		</tbody>
	</table>
	<?php echo submit_button(); ?>
</form>
