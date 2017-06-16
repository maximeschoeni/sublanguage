<?php

$type = get_post_meta($post->ID, '_menu_item_type', true);
$hide = get_post_meta($post->ID, 'sublanguage_hide', true);

$_menu_item_type = get_post_meta($post->ID, '_menu_item_type', true);

if ($_menu_item_type === 'post_type') {
		
	$_menu_item_object_id = get_post_meta($post->ID, '_menu_item_object_id', true);
	$object_post = get_post($_menu_item_object_id);
	$post_object = $this->translate_post_field($object_post, 'post_title');
	$edit_link = add_query_arg(array($this->language_query_var => $this->get_language()->post_name), get_edit_post_link( $_menu_item_object_id, false ));
	
} else if ($_menu_item_type === 'taxonomy') {

	$_menu_item_object_id = get_post_meta($post->ID, '_menu_item_object_id', true);
	$_menu_item_object = get_post_meta($post->ID, '_menu_item_object', true);
	$object_term = get_term_by('id', $_menu_item_object_id, $_menu_item_object);
	$term_object = $object_term->name;
	
	
} else if ($_menu_item_type === 'custom') {

	$url = get_post_meta($post->ID, '_menu_item_url', true);
	
}

$classes = get_post_meta($post->ID, '_menu_item_classes', true);
$classes_string = (is_array($classes)) ? implode(' ', $classes) : $classes;

wp_nonce_field( 'sublanguage', 'sublanguage_extra_cpt_nonce', false, true );

?>
<table>
	<tbody>
		<?php if (isset($post_object)) { ?>
			<tr><td><label><?php echo __('Page Title', 'sublanguage'); ?></label></td><td><input type="text" value="<?php echo $post_object; ?>" readonly/> (<a href="<?php echo $edit_link ?>"><?php echo __('edit', 'sublanguage'); ?></a>)</td></tr>
		<?php } else if (isset($term_object)) { ?>
			<tr><td><label><?php echo __('Term Name', 'sublanguage'); ?></label></td><td><input type="text" value="<?php echo $term_object; ?>" readonly/> <?php edit_term_link( 'edit', '(', ')', $object_term, true ); ?></td></tr>
		<?php } else if (isset($url)) { ?>
			<tr><td><label><?php echo __('URL', 'sublanguage'); ?></label></td><td><input type="text" name="sublanguage_extra_cpt[_menu_item_url]" value="<?php echo $url; ?>"/></td></tr>
		<?php } ?>
		<tr><td><label><?php echo __('Title Attribute', 'sublanguage'); ?></label></td><td><input type="text" name="excerpt" value="<?php echo $post->post_excerpt; ?>"/></td></tr>
		<tr><td><label><?php echo __('Description', 'sublanguage'); ?></label></td><td><input type="text" name="content" value="<?php echo $post->post_content; ?>"/></td></tr>
		<?php if (in_array('_menu_item_classes', $this->get_post_type_metakeys($post->post_type))) { ?>
			<tr><td><label><?php echo __('Classes', 'sublanguage'); ?></label></td><td><input type="text" name="_menu_item_classes" value="<?php echo $classes_string; ?>"/></td></tr>
		<?php } ?>
		<tr><td><label><?php echo __('Hide', 'sublanguage'); ?></label></td><td><input type="hidden" id="sublanguage_nav_menu_hide" name="sublanguage_extra_cpt[sublanguage_hide]" value="<?php echo $hide; ?>"/><label><input type="checkbox" value="1" <?php if ($hide) echo ' checked'; ?> onchange="document.getElementById('sublanguage_nav_menu_hide').value=this.checked ? '1' : '';"/><?php echo __('Hide this menu item in this language', 'sublanguage'); ?></label></td></tr>
	</tbody>
</table>