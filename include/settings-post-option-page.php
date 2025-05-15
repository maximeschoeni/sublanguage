<form action="<?php echo admin_url(); ?>" method="POST">
	<?php wp_nonce_field('sublanguage_action', 'sublanguage_post_option', true, true); ?>
	<input type="hidden" name="post_type" value="<?php echo $post_type; ?>">
	<h2><?php echo sprintf(__('%s Language Options', 'sublanguage'), isset($post_type_obj->label) ? $post_type_obj->label : $post_type); ?></h2>
	<nav><a href="<?php echo admin_url('options-general.php?page=sublanguage-settings'); ?>"><?php echo __('Sublanguage General Settings', 'sublanguage'); ?></a></nav>
	<table class="form-table">
		<tbody>
			<?php if ($post_type !== 'post' && $post_type !== 'page' && $post_type !== 'attachment' && $post_type_obj->publicly_queryable) { ?>
				<?php 
					add_filter('home_url', array($this,'translate_home_url'), 10, 4);
				?>
				<tr>
					<th><?php echo __('Post Type Permalink Base', 'sublanguage'); ?></th>
					<td>
						<ul id="sublanguage-post-options-permalink">
							<?php foreach ($this->get_languages() as $language) { ?>
								<?php 
									$this->set_language($language);
									$cpt_translation = $this->get_cpt_translation($post_type, $language);
									$cpt_default = $this->translate_cpt($post_type, $language);
								?>
								<li>
									<code><?php echo home_url('/'); ?></code><input type="text" class="text-input" name="cpt[<?php echo $language->ID; ?>]" value="<?php echo $cpt_translation; ?>" placeholder="<?php echo $cpt_default; ?>" autocomplete="off" style="padding: 0 3px;"><code>/...</code>
								</li>
							<?php } ?>
						</ul>
						<p class="description"><?php echo sprintf(__('Permalink base slug is originally: %s. It is overwrited by Sublanguage.'), '<code>'.$post_type_obj->rewrite['slug'].'</code>'); ?></p>
					</td>
				</tr>
				<?php if ($post_type_obj->has_archive && $post_type_obj->has_archive !== true) { ?>
					<tr>
						<th><?php echo __('Post Type Archive Link', 'sublanguage'); ?></th>
						<td>
						
							<ul id="sublanguage-post-options-permalink">
								<?php foreach ($this->get_languages() as $language) { ?>
									<?php 
										$this->set_language($language);
										$cpt_archive_translation = $this->get_cpt_archive_translation($post_type, $language, '');
										$cpt_archive_default = $this->translate_cpt_archive($post_type, $language);
									?>
									<li>
										<code><?php echo home_url('/'); ?></code><input type="text" class="text-input" name="cpt_archive[<?php echo $language->ID; ?>]" value="<?php echo $cpt_archive_translation; ?>" placeholder="<?php echo $cpt_archive_default; ?>" autocomplete="off" style="padding: 0 3px;">
									</li>
								<?php } ?>
							</ul>
							<p class="description"><?php echo sprintf(__('Archive slug is originally: %s. It is overwrited by Sublanguage.'), '<code>'.$post_type_obj->has_archive.'</code>'); ?></p>
						</td>
					</tr>
				<?php } ?>
			<?php } ?>
			<tr>
				<th><?php echo __('Translatable post fields', 'sublanguage'); ?></th>
				<td>
					<ul>
						<?php foreach ($this->fields as $value) { ?>
							<li><label><input type="checkbox" name="fields[]" value="<?php echo $value; ?>" <?php if (in_array($value, $this->get_post_type_fields($post_type))) echo 'checked'; ?>/><?php echo $value; ?></label></li>
						<?php } ?>
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
			<tr>
				<th>Revisions</th>
				<td>
					<label><input type="checkbox" name="enable_revisions" value="1" <?php if ($this->get_post_type_option($post_type, 'enable_revisions')) echo 'checked' ?>/><?php echo __('Save language data in revisions', 'sublanguage'); ?></label>
				</td>
			</tr>
			<tr>
				<th>Gutenberg</th>
				<td>
					<label><input type="checkbox" name="gutenberg_async_switch" value="1" <?php if (!$this->get_post_type_option($post_type, 'gutenberg_metabox_compat')) echo 'checked' ?>/><?php echo __('Use asynchronous language switch in Gutenberg. NOT COMPATIBLE WITH METABOXES!', 'sublanguage'); ?></label>
				</td>
			</tr>
		</tbody>
	</table>
	<?php echo submit_button(); ?>
</form>