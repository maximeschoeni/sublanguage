<?php 

echo '<h1>'.__('Translate Options', 'sublanguage').'</h1>';

echo '<ul class="sublanguage-options">';

foreach ($options as $option) {

	$disabled = false;

	if ( $option->option_name == '' ) {

		continue;

	}

	if (is_serialized($option->option_value) && !is_serialized_string($option->option_value)) {

		$value = 'DATA';
		$disabled = true;

	} else {

		$value = $option->option_value;

	}

	$name = esc_attr( $option->option_name );
	
	echo '<li>';
	echo '<span class="handle dashicons dashicons-arrow-right"></span>';
	echo '<label for="'.$name.'">'.esc_html( $option->option_name ).'</label>';
	echo '<input class="regular-text all-options" type="text" name="sublanguage_translate_options['.$name.']" id="'.$name.'" value="'.esc_attr( $value ).'" readonly="readonly" />';
	echo '</li>';
	
}
	
echo '</ul>';