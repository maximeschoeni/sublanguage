<form action="<?php echo admin_url(); ?>" method="POST">
	<?php wp_nonce_field('sublanguage_action', 'sublanguage_settings_option', true, true); ?>
	<h2><?php echo __('Sublanguage Settings', 'sublanguage'); ?></h2>
	<table class="form-table">
		<tbody>
			<tr>
				<th><?php echo __('Translate Post Types', 'sublanguage'); ?></th>
				<td>
					<?php 
						$cpts = get_post_types(array(
							//'show_ui' => true,
							//'public' => true,
						), 'objects' );
					?>
					<?php if (isset($cpts)) { ?>
						<ul>
						<?php foreach ($cpts as $post_type) { ?>
							<?php if ($post_type->name === 'revision' || $post_type->name === 'language') continue; ?>
								<li><input type="checkbox" id="<?php echo $this->option_name.'-cpt-'.$post_type->name; ?>" name="post_type[]" value="<?php echo $post_type->name; ?>" <?php if ($this->is_post_type_translatable($post_type->name)) echo 'checked'; ?>/>
									<label for="<?php echo $this->option_name.'-cpt-'.$post_type->name; ?>"><?php echo (isset($post_type->labels->name) ? $post_type->labels->name : $post_type->name); ?></label>
									<?php if ($this->is_post_type_translatable($post_type->name)) { ?>
										|
										<?php if ($post_type->name === 'nav_menu_item' || in_array($post_type->name, $this->extra_post_types)) { ?>
											<a href="<?php echo admin_url('edit.php?') . 'post_type=' . $post_type->name; ?>"><?php echo __('Edit translations', 'sublanguage'); ?></a> |
											<a href="<?php echo admin_url('tools.php?') . 'page=' . $post_type->name . '_language_option'; ?>"><?php echo __('Options', 'sublanguage'); ?></a>
										<?php } else { ?>
											<a href="<?php echo admin_url('edit.php?').($post_type->name === 'post' ? '' : 'post_type='.$post_type->name.'&') . 'page=' . $post_type->name . '_language_option'; ?>"><?php echo __('Options', 'sublanguage'); ?></a>
										<?php } ?>
									<?php } ?>
								</li>
							<?php } ?>
						</ul>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Translate Taxonomies', 'sublanguage'); ?></th>
				<td>
					<?php 
						$taxonomies = get_taxonomies(array(
							'show_ui' => true
						), 'objects');
					?>
					<?php if (isset($taxonomies)) { ?>
						<ul>
						<?php foreach ($taxonomies as $taxonomy) { ?>
								<li>
									<input type="checkbox" name="taxonomy[]" value="<?php echo $taxonomy->name; ?>" id="<?php echo $this->option_name.'-taxi-'.$taxonomy->name; ?>" <?php if ($this->is_taxonomy_translatable($taxonomy->name)) echo 'checked'; ?>/>
									<label for="<?php echo $this->option_name.'-taxi-'.$taxonomy->name; ?>"><?php echo (isset($taxonomy->labels->name) ? $taxonomy->labels->name : $taxonomy->name); ?></label>
									<?php if ($this->is_taxonomy_translatable($taxonomy->name)) { ?>
										|
										<a href="<?php echo admin_url('options-general.php?page=' . $taxonomy->name . '_language_option&taxonomy='.$taxonomy->name); ?>">Options</a>
									<?php } ?>
								</li>
							<?php } ?>
						</ul>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Translate Options', 'sublanguage'); ?></th>
				<td><a href="<?php echo admin_url('tools.php?page=translate_options');  ?>"><?php echo __('Edit translations', 'sublanguage'); ?></a></td>
			</tr>
			<tr>
				<th><?php echo __('Original language', 'sublanguage'); ?></th>
				<td>
					<label>
						<select name="main">
							<?php foreach ($this->get_languages() as $lng) { ?>
								<option value="<?php echo $lng->ID; ?>" <?php if ($this->is_main($lng)) echo 'selected'; ?>><?php echo $lng->post_title; ?></option>
							<?php } ?>
						</select>
						<?php echo __('This is the langage that will be used if a translation is missing for a post.', 'sublanguage'); ?>
					</label>
					<a href="<?php echo admin_url('edit.php?post_type='.$this->language_post_type); ?>"><?php echo __('Add language', 'sublanguage'); ?></a>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Default language', 'sublanguage'); ?></th>
				<td>
					<label>
						<select name="default">
							<?php foreach ($this->get_languages() as $lng) { ?>
								<option value="<?php echo $lng->ID; ?>" <?php if ($this->is_default($lng)) echo 'selected'; ?>><?php echo $lng->post_title; ?></option>
							<?php } ?>
						</select>
						<?php echo __('This is the langage visitors will see when language is not specified in url.', 'sublanguage'); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Show slug for main language', 'sublanguage'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="show_slug" value="1"<?php echo $this->get_option('show_slug') ? ' checked' : '' ?>/>
						<?php echo __('Show language slug for main language in site url', 'sublanguage') ?>
					</label> 
 				</td>
			</tr>
			<tr>
				<th><?php echo __('Auto-detect language', 'sublanguage'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="autodetect" value="1"<?php echo $this->get_option('autodetect') ? ' checked' : ''; ?>/>
						<?php echo __('Auto-detect language when language is not specified in url.', 'sublanguage'); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Current language first', 'sublanguage'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="current_first" value="1"<?php echo $this->get_option('current_first') ? ' checked' : ''; ?>/>
						<?php echo __('Set the current language to be the first in the language selectors.', 'sublanguage'); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Use AJAX in Front-End', 'sublanguage'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="frontend_ajax" value="1"<?php echo $this->get_option('frontend_ajax') ? ' checked' : ''; ?>/>
						<?php echo __('Translate AJAX queries (using jQuery)', 'sublanguage'); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Version', 'sublanguage'); ?></th>
				<td>
					<p><?php echo $this->get_option('version'); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php echo submit_button(); ?>
</form>