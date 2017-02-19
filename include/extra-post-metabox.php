<?php

global $wpdb;

$original_post = get_post($post->ID);
$sql_meta_keys = implode("', '", array_map('esc_sql', $this->get_post_type_metakeys($post->post_type)));

$results = $wpdb->get_results( $wpdb->prepare( "
	SELECT DISTINCT meta.meta_key
	FROM $wpdb->postmeta AS meta
	LEFT JOIN $wpdb->posts AS post ON (post.ID = meta.post_id)
	WHERE post.post_type = %s AND meta.meta_key IN ('$sql_meta_keys')",
	$post->post_type
));

$meta_fields = array();

$meta_fields[] = array(
	'key' => 'content',
	'name' => 'content',
	'value' => $this->translate_post_field($post, 'post_content', null, ''),
	'placeholder' => $this->is_sub() ? $original_post->post_content : '',
	'field' => 'textarea'
);

$meta_fields[] = array(
	'key' => 'excerpt',
	'name' => 'excerpt',
	'value' => $this->translate_post_field($post, 'post_excerpt', null, ''),
	'placeholder' => $this->is_sub() ? $original_post->post_excerpt : '',
	'field' => 'textarea'
);

foreach ($results as $result) {

	$key = $result->meta_key;
	$values = get_post_meta($post->ID, $key);
	$original_values = array();
	
	if ($this->is_sub()) {
		
		$this->disable_postmeta_filter = true;
		$original_values = get_post_meta($post->ID, $key);
		$this->disable_postmeta_filter = false;
		
	}
	
	$len = max(count($values), count($original_values), 1);
	
	for ($i = 0; $i < $len; $i++) {
	
		$meta_fields[] = array(
			'key' => $key,
			'name' => 'sublanguage_extra_cpt[' . $key . ']',
			'value' => isset($values[$i]) ? $values[$i] : '',
			'placeholder' => isset($original_values[$i]) ? $original_values[$i] : '',
			'field' => 'intput'
		);
	
	}
	
}

wp_nonce_field( 'sublanguage', 'sublanguage_extra_cpt_nonce', false, true );

echo '<table style="width:100%">';
echo '<colgroup><col style="width:25%"><col style="width:75%"></colgroup>';
echo '<tbody>';

foreach ($meta_fields as $meta_field) {
	
	echo '<tr><td><label for="sublanguage-' . $meta_field['key'] . '">' . $meta_field['key'] . '</label></td><td>';
	
	if ($meta_field['field'] === 'textarea') {
	
		echo '<textarea  style="width:100%;box-sizing:border-box;" id="sublanguage-' . $meta_field['key'] . '" type="text" name="' . $meta_field['name'] . '" placeholder="' . esc_html($meta_field['placeholder']) . '">' . esc_html($meta_field['value']) . '</textarea>';
	
	} else {
	
		echo '<input  style="width:100%;box-sizing:border-box;" id="sublanguage-' . $meta_field['key'] . '" type="text" name="' . $meta_field['name'] . '" value="' . esc_html($meta_field['value']) . '" placeholder="' . esc_html($meta_field['placeholder']) . '"/>';
	
	}
	
	echo '</td></tr>';
	
}

echo '</tbody>';
echo '</table>';

