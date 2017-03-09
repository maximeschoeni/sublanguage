<form action="<?php echo admin_url(); ?>" method="POST">
	<?php wp_nonce_field('sublanguage_action', 'sublanguage_post_option', true, true); ?>
	<input type="hidden" name="post_type" value="<?php echo $post_type; ?>">
	<h2><?php echo sprintf(__('%s Language Options', 'sublanguage'), isset($post_type_obj->label) ? $post_type_obj->label : $post_type); ?></h2>
	<nav><a href="<?php echo admin_url('options-general.php?page=sublanguage-settings'); ?>"><?php echo __('Sublanguage General Settings', 'sublanguage'); ?></a></nav>
	<table class="form-table">
		<tbody>
			<?php if ($post_type !== 'post' && $post_type !== 'page' && $post_type !== 'attachment' && $post_type_obj->publicly_queryable) { ?>
				<tr>
					<th><?php echo __('Post Type Archive Link', 'sublanguage'); ?></th>
					<td>
						<?php 
							add_filter('home_url', array($this,'translate_home_url'), 10, 4);
							add_filter('post_type_archive_link', array($this, 'translate_post_type_archive_link'), 10, 2);
						?>
						<ul id="sublanguage-post-options-permalink">
							<?php foreach ($this->get_languages() as $language) { ?>
								<?php 
									$this->set_language($language);
									$link = get_post_type_archive_link($post_type);
									$cpt_translation = $this->get_cpt_translation($post_type, $language);
									$translated_slug = $cpt_translation ? $cpt_translation : $post_type;
								?>
								<li>
									<code><?php echo $language->post_name; ?></code>
									<span class="read-mode">
										<a class="full-url" target="_blank" href="<?php echo $link; ?>"><?php echo home_url('/'); ?><span class="slug"><?php echo $translated_slug; ?></span>/</a>
										<button class="button button-small edit-btn" style="vertical-align: bottom;"><?php echo __('edit', 'sublanguage'); ?></button>
									</span>
									<span class="edit-mode hidden"><?php echo home_url('/'); ?>
										<input type="text" class="text-input" name="cpt[<?php echo $language->ID; ?>]" value="<?php echo $cpt_translation; ?>" data-def="<?php echo $post_type; ?>" placeholder="<?php echo $post_type; ?>" autocomplete="off" style="padding: 0 3px;">
										<button class="button button-small ok-btn" style="vertical-align: bottom;">ok</button>
									</span>
								</li>
							<?php } ?>
						</ul>
					</td>
				</tr>
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
		</tbody>
	</table>
	<?php echo submit_button(); ?>
</form>
<script>
	(function() {
		var ul = document.getElementById("sublanguage-post-options-permalink");
		var registerClick = function(editMode, readMode) {
			var onClick = function(event) {
				editMode.classList.toggle("hidden");
				readMode.classList.toggle("hidden");
				event.preventDefault();
			};
			readMode.querySelector("button").addEventListener("click", onClick);
			editMode.querySelector("button").addEventListener("click", function(event) {
				var input = editMode.querySelector("input");
				readMode.querySelector(".slug").innerHTML = input.value ? input.value : input.dataset.def;
				onClick(event);
			});
		}
		if (ul) {
			for (var i = 0; i < ul.children.length; i++) {
				registerClick(ul.children[i].querySelector(".edit-mode"), ul.children[i].querySelector(".read-mode"));
			}
		}
	})();
</script>