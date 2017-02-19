<?php
		
$languages = $this->get_languages();

if ($languages) {
	foreach ($languages as $i => $lng) {
		$link = add_query_arg(array($this->language_query_var => $lng->post_name), $base_url);
		if (empty($_GET['post_status'])) {
			$link = add_query_arg(array('all_posts' => '1'), $link);
		}
		echo '<li>'.($i > 0 ? ' | ' : '').'<a data-language="' . $lng->post_name . '" class="language' . ($this->is_current($lng) ? ' current' : '') . '" href="'.$link.'">' . $lng->post_title . '</a></li>';
	}
}

?>