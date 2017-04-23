<?php
/*
Plugin Name: Sublanguage
Plugin URI: http://sublanguageplugin.wordpress.com
Description: Plugin for building a site with multiple languages
Author: Maxime Schoeni
Version: 2.2
Author URI: http://sublanguageplugin.wordpress.com
Text Domain: sublanguage
Domain Path: /languages
License: GPL

Copyright 2015 Maxime Schoeni <contact@maximeschoeni.ch>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 2.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.


*/

require( plugin_dir_path( __FILE__ ) . 'class-core.php');
require( plugin_dir_path( __FILE__ ) . 'class-current.php');


if (is_admin()) {
	
	global $sublanguage_admin;
	
	require( plugin_dir_path( __FILE__ ) . 'class-rewrite.php');
	require( plugin_dir_path( __FILE__ ) . 'class-admin.php');
	
	if (defined('DOING_AJAX') && DOING_AJAX) {
	
		require( plugin_dir_path( __FILE__ ) . 'class-ajax.php');
		$sublanguage_admin = new Sublanguage_ajax();
	
	} else {
		
		require( plugin_dir_path( __FILE__ ) . 'class-admin-ui.php');
		$sublanguage_admin = new Sublanguage_admin_ui();
		
	}
	
	register_activation_hook(__FILE__, array($sublanguage_admin, 'activate'));
	register_deactivation_hook(__FILE__, array($sublanguage_admin, 'desactivate'));
	
} else {
	
	$options_v2 = get_option('sublanguage');
		
	if (!$options_v2 || empty($options_v2['version']) || version_compare($options_v2['version'], '2.0') < 0 || empty($options_v2['db_version']) || version_compare($options_v2['db_version'], '2.0') < 0) {
	
		// run compat
		require( plugin_dir_path( __FILE__ ) . 'compat/sublanguage.php');
	
	} else {
	
		global $sublanguage;
		
		require( plugin_dir_path( __FILE__ ) . 'class-site.php');
		$sublanguage = new Sublanguage_site();
		
	}

}





