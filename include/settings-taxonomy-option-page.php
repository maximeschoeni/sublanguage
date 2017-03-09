<form action="<?php echo admin_url(); ?>" method="POST">
	<?php wp_nonce_field('sublanguage_action', 'sublanguage_taxonomy_option', true, true); ?>
	<input type="hidden" name="taxonomy" value="<?php echo $taxonomy; ?>">
	<h2><?php echo sprintf(__('%s Language Options', 'sublanguage'), isset($tax_obj->label) ? $tax_obj->label : $taxonomy); ?></h2>
	<nav><a href="<?php echo admin_url('options-general.php?page=sublanguage-settings'); ?>"><?php echo __('Sublanguage Settings', 'sublanguage'); ?></a></nav>
	<table class="form-table">
		<tbody>
			<tr>
				<th><?php echo __('Terms Link', 'sublanguage'); ?></th>
				<td>
					<?php 
						add_filter('home_url', array($this,'translate_home_url'), 10, 4);
					?>
					<ul id="sublanguage-post-options-permalink">
						<?php foreach ($this->get_languages() as $language) { ?>
							<?php 
								$this->set_language($language);
								$tt = $this->get_taxonomy_translation($taxonomy, $language);
								$translated_slug = $tt ? $tt : $taxonomy;
							?>
							<li>
								<code><?php echo $language->post_name; ?></code>
								<span class="read-mode">
									<a class="full-url" target="_blank" href="<?php echo home_url('/'.$translated_slug); ?>"><?php echo home_url('/'); ?><span class="slug"><?php echo $translated_slug; ?></span>/</a>
									<button class="button button-small edit-btn" style="vertical-align: bottom;"><?php echo __('edit', 'sublanguage'); ?></button>
								</span>
								<span class="edit-mode hidden"><?php echo home_url('/'); ?>
									<input type="text" class="text-input" name="tax[<?php echo $language->ID; ?>]" value="<?php echo $tt; ?>" data-def="<?php echo $taxonomy; ?>" placeholder="<?php echo $taxonomy; ?>" autocomplete="off" style="padding: 0 3px;">
									<button class="button button-small ok-btn" style="vertical-align: bottom;">ok</button>
								</span>
							</li>
						<?php } ?>
					</ul>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Translatable post fields', 'sublanguage'); ?></th>
				<td>
					<ul>
						<li><label><input type="checkbox" name="fields[]" value="name" <?php if (in_array('name', $this->get_taxonomy_fields($taxonomy))) echo 'checked'; ?>/><?php echo __('Name', 'sublanguage'); ?></label></li>
						<li><label><input type="checkbox" name="fields[]" value="slug" <?php if (in_array('slug', $this->get_taxonomy_fields($taxonomy))) echo 'checked'; ?>/><?php echo __('Slug', 'sublanguage'); ?></label></li>
						<li><label><input type="checkbox" name="fields[]" value="description" <?php if (in_array('description', $this->get_taxonomy_fields($taxonomy))) echo 'checked'; ?>/><?php echo __('Description', 'sublanguage'); ?></label></li>
					</ul>
				</td>
			</tr>
			<tr>
				<?php if ($meta_keys) { ?>
					<th><?php echo __('Translatable Term Meta', 'sublanguage'); ?></th>
					<td>
						<ul>
							<?php foreach ($meta_keys as $key => $values) { ?>
								<li><label title="value sample: '<?php echo isset($values[0]) ? $values[0] : ''; ?>'"><input type="checkbox" name="meta_keys[]" value="<?php echo $key; ?>" <?php if (in_array($key, $this->get_taxonomy_metakeys($taxonomy))) echo 'checked'; ?>/><?php echo isset($registered_meta_keys[$key]['description']) && $registered_meta_keys[$key]['description'] ? $registered_meta_keys[$key]['description'] : $key; ?></label></li>
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
		for (var i = 0; i < ul.children.length; i++) {
			registerClick(ul.children[i].querySelector(".edit-mode"), ul.children[i].querySelector(".read-mode"));
		}
	})();
</script>