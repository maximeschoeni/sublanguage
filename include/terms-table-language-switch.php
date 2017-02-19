<?php


$language = $this->get_language();
$languages = $this->get_languages();
$name = 'post_language_switch';
$taxonomy = isset($_GET['taxonomy']) ? esc_attr($_GET['taxonomy']) : '';

// if ($language) {
// 	echo '<input type="hidden" name="'. $name . '" value="' . $language->post_name . '">';
// 	echo '<input type="hidden" name="'.$this->language_query_var.'" id="sublanguage-language-input" value="'.$language->post_name.'"/>';
// }

// echo '<h2 style="margin-top:20px" class="nav-tab-wrapper" id="sublanguage-language-tabs">';
// 
// foreach ($languages as $lng) {
// 	
// 	echo '<a data-language="' . $lng->post_name . '" class="nav-tab' . ($this->is_current($lng) ? ' nav-tab-active' : '') . '" href="'.add_query_arg(array('taxonomy' => $taxonomy, $this->language_query_var => $lng->post_name), admin_url('edit-tags.php')).'">' . $lng->post_title . '</a>';
// 
// }
// 
// echo '</h2>';


if ($languages) {
	echo '<ul class="subsubsub" id="sublanguage-language-tabs">';
	foreach ($languages as $i => $lng) {
		echo '<li>'.($i > 0 ? ' | ' : '').'<a data-language="' . $lng->post_name . '" class="language' . ($this->is_current($lng) ? ' current' : '') . '" href="'.add_query_arg(array('taxonomy' => $taxonomy, $this->language_query_var => $lng->post_name), admin_url('edit-tags.php')).'">' . $lng->post_title . '</a></li>';
	}
	echo '</ul>';
}


?>

<script type="text/javascript">
	var tab = document.getElementById("sublanguage-language-tabs");
	tab.parentNode.insertBefore(tab, tab.parentNode.firstChild);
</script>




<?php /*

$languages = $this->get_languages();
		
echo '<form method="get" style="display:inline">';
if (isset($_GET['taxonomy'])) echo '<input type="hidden" name="taxonomy" value="'.esc_attr($_GET['taxonomy']).'">';

echo '<select name="'.$this->language_query_var.'" id="sublanguage-language-selector">';

foreach ($languages as $lng) {
	
	echo sprintf('<option value="%s"%s>%s</option>', 
		$lng->post_name,
		($this->is_current($lng) ? ' selected' : ''),
		$lng->post_title
	);

}

echo '</select>';
echo '<noscript><input type="submit" class="button"/></noscript>';
echo '</form>';
?>
<script type="text/javascript">
	var selector = document.getElementById("sublanguage-language-selector");
	selector.addEventListener("change", function() {
		selector.form.submit();
	});
	
	//<![CDATA[
// 		jQuery(document).ready(function($) {
// 			var select = $("select[name='.$this->language_query_var.']");
// 			select.change(function() {
// 				$(this).closest("form").submit();
// 			});
// 			select.closest("form").prependTo(select.closest(".col-wrap"));
// 		});
	//]]>
</script>
			
*/