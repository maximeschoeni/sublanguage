<?php
		
$language = $this->get_language();
$languages = $this->get_languages();
$name = 'post_language_switch';

if ($language) {
	echo '<input type="hidden" name="'. $name . '" id="sublanguage-language-input" value="' . $language->post_name . '">';
	echo '<input type="hidden" name="'.$this->language_query_var.'" value="'.$language->post_name.'"/>';
}
echo '<h2 style="margin-top:20px" class="nav-tab-wrapper" id="sublanguage_language_tabs">';
foreach ($languages as $lng) {
	echo '<a data-language="' . $lng->post_name . '" class="nav-tab' . ($this->is_current($lng) ? ' nav-tab-active' : '') . '" href="'.add_query_arg(array($this->language_query_var => $lng->post_name), get_edit_post_link($post->ID)).'">' . $lng->post_title . '</a>';
}
echo '</h2>';
echo wp_nonce_field( 'sublanguage_switch_language', 'sublanguage_switch_language_nonce', false, false );
?>
<script type="text/javascript">
	(function() {
		var getSubmitBtn = function() {
			var inputs = document.querySelectorAll("input");
			for (var i = 0; i < inputs.length; i++) {
				if (inputs[i].name === "save") {
					return inputs[i];
				}
			}
		};
		var tab = document.getElementById("sublanguage_language_tabs");
		var links = tab.querySelectorAll("a");
		for (var i = 0; i < links.length; i++) {
			links[i].addEventListener("click", function(event) {
				event.preventDefault();
				var submit = getSubmitBtn();
				var input = document.getElementById("sublanguage-language-input");
				input.value = this.getAttribute("data-language");
				input.form.submit();
			});
		}
		window.onbeforeunload = function(e) {
			e.stopImmediatePropagation();
		};
	})();
</script>
