<?php 

class Sublanguage_terms extends Sublanguage_admin_post {
	
	/**
	 * @from 1.0
	 */
	var $term_translation = 'sublanguage_term';
	
	/**
	 * @from 1.0
	 */
	var $nonce = 'sublanguage_term_nonce';
		
	/**
	 * @from 1.0
	 */
	var $action = 'sublanguage_term_action';
	
	
	/**
	 * @from 1.0
	 */
	public function __construct() {
		
		// save term translations in options
		add_action('edit_term', array($this, 'save_term_translation'), 10, 3);
		
		// tags table
		add_action('load-edit-tags.php', array($this, 'admin_edit_tags'));
		
	}
	
	/**
	 * Add translation box on terms edit form.
	 *
	 * @from 1.0
	 */
	public function add_term_edit_form($tag, $taxonomy) {
		global $sublanguage_admin;
		
		$languages = $sublanguage_admin->get_languages();
		
		$sublanguage_admin->enqueue_term_id($tag->term_id);
	
?>
<tr>
	<th><h2><?php echo __('Translations', 'sublanguage'); ?></h2></th>
	<td><?php wp_nonce_field($this->action, $this->nonce); ?></td>
</tr>	
<?php foreach ($languages as $language) { ?>
	<?php 
				
		if ($sublanguage_admin->is_main($language->ID)) continue;
		
		$slug = $sublanguage_admin->translate_term_field($tag, $taxonomy, $language->ID, 'slug', '');
		$name = $sublanguage_admin->translate_term_field($tag, $taxonomy, $language->ID, 'name', '');
		$desc = $sublanguage_admin->translate_term_field($tag, $taxonomy, $language->ID, 'description', '');
		
	?>
	<tr>
		<th>
			<label><?php echo $language->post_title; ?></label>
			<input type=hidden name="<?php echo $sublanguage_admin->language_query_var; ?>" value="<?php echo $sublanguage_admin->get_main_language()->post_name; ?>"/>
		</th>
		<td>
			<div style="display:flex;display: -webkit-flex;flex-wrap:wrap;-webkit-flex-wrap:wrap">
				<div style="margin-bottom:1em">
					<input name="<?php echo $this->term_translation; ?>[<?php echo $taxonomy; ?>][<?php echo $language->ID; ?>][<?php echo $tag->term_id; ?>][name]" type="text" value="<?php echo $name; ?>" size="40" style="box-sizing:border-box">
					<p class="description"><?php echo sprintf(__('Term name. Default: %s', 'sublanguage'), "<code>$tag->name</code>"); ?></p>
				</div>
				<div style="margin-bottom:1em">
					<input name="<?php echo $this->term_translation; ?>[<?php echo $taxonomy; ?>][<?php echo $language->ID; ?>][<?php echo $tag->term_id; ?>][slug]" type="text" value="<?php echo $slug; ?>" size="40" style="box-sizing:border-box">
					<p class="description"><?php echo sprintf(__('Term slug. Default: %s', 'sublanguage'), "<code>$tag->slug</code>"); ?></p>
				</div>
				<div style="margin-bottom:1em; width:100%">
					<textarea name="<?php echo $this->term_translation; ?>[<?php echo $taxonomy; ?>][<?php echo $language->ID; ?>][<?php echo $tag->term_id; ?>][description]" style="box-sizing:border-box;width:100%"><?php echo $desc; ?></textarea>
					<p class="description"><?php echo __('Term description.', 'sublanguage'); ?></p>
				</div>
			</div>
		</td>
	</tr>
<?php } 
	
	}
	
	
	/**
	 * Intercept update term and save term translation
	 * Hook for "edit_term"
	 *
	 * @from 1.0
	 */
	public function save_term_translation($term_id, $tt_id, $taxonomy) {
		global $sublanguage_admin;
		
		if (in_array($taxonomy, $sublanguage_admin->get_taxonomies())
			&& isset($_POST[$this->nonce], $_POST[$this->term_translation][$taxonomy]) 
 			&& wp_verify_nonce($_POST[$this->nonce], $this->action)) {
			
			foreach ($_POST[$this->term_translation][$taxonomy] as $lng_id => $data) {
				
				if ($sublanguage_admin->is_sub($lng_id)) {
				
					foreach ($data as $term_id => $translation) {
					
						$sublanguage_admin->update_term_translation($term_id, $taxonomy, $translation, $lng_id);
			
					}
				
				}
				
			}
			
		}		
		
	}	
	
	/**
	 * fire filters on edit.php
	 *
	 * Hook for 'load-edit-tags.php'
	 *
	 * @from 1.2
	 */
	public function admin_edit_tags() {	
		global $sublanguage_admin;
		
		$current_screen = get_current_screen();
		
		if ($sublanguage_admin->current_language && isset($current_screen->taxonomy) && in_array($current_screen->taxonomy, $sublanguage_admin->get_taxonomies())) {
			
			add_action($current_screen->taxonomy.'_edit_form_fields', array($this, 'add_term_edit_form'), 12, 2);
			
			add_action('after-'.$current_screen->taxonomy.'-table', array($this, 'language_switch'));
			
		}
	
	}

	/**
	 *
	 * @from 1.2
	 */		
	public function language_switch($taxonomy) {
		global $sublanguage_admin;
		
		echo $this->print_table_view_language_switch($sublanguage_admin->language_query_var);

	}
	
	/**
	 *
	 * @from 1.2
	 */
	public function print_table_view_language_switch($name) {
		global $sublanguage_admin;
		
		$languages = $sublanguage_admin->get_languages();
		
		$html = '<form method="get" style="display:inline">';
		if (isset($_GET['taxonomy'])) $html .= '<input type="hidden" name="taxonomy" value="'.esc_attr($_GET['taxonomy']).'">';

		$html .= '<select name="'.$name.'">';
		
		foreach ($languages as $lng) {
			
			$html .= sprintf('<option value="%s"%s>%s</option>', 
				$lng->post_name,
				($sublanguage_admin->current_language->ID == $lng->ID ? ' selected' : ''),
				$lng->post_title
			);
		
		}
		
		$html .= '</select>';
		$html .= '<noscript><input type="submit" class="button"/></noscript>';
		$html .= '</form>';
		$html .= '		
<script type="text/javascript">
	//<![CDATA[
		jQuery(document).ready(function($) {
			var select = $("select[name='.$name.']");
			select.change(function() {
				$(this).closest("form").submit();
			});
			select.closest("form").prependTo(select.closest(".col-wrap"));
		});
	//]]>
</script>';
			
		return $html;
		
	}	
	
			
}


