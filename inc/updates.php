<?php

# Exit if accessed directly				
if (!defined('ABSPATH')){ exit(); }	

# update routines for new fields and replacements
function fvm_get_updated_field_routines($fvm_settings) {
	
	# current version
	global $fvm_var_plugin_version;	
	
	# must have
	if(!is_array($fvm_settings)) { return $fvm_settings; }
	
	# Version 3.0 routines start
	
	# delete old FVM files
	global $fvm_var_dir_path, $fvm_var_inc_lib, $fvm_var_inc_dir;
	if(file_exists($fvm_var_inc_dir.'functions-cache.php')) { @unlink($fvm_var_inc_dir.'functions-cache.php'); }
	if(file_exists($fvm_var_inc_dir.'functions-cli.php')) { @unlink($fvm_var_inc_dir.'functions-cli.php'); }
	if(file_exists($fvm_var_inc_dir.'functions-serverinfo.php')) { @unlink($fvm_var_inc_dir.'functions-serverinfo.php'); }
	if(file_exists($fvm_var_inc_dir.'functions-upgrade.php')) { @unlink($fvm_var_inc_dir.'functions-upgrade.php'); }
	if(file_exists($fvm_var_inc_dir.'functions.php')) { @unlink($fvm_var_inc_dir.'functions.php'); }
	if(file_exists($fvm_var_dir_path.'fvm.css')) { @unlink($fvm_var_dir_path.'fvm.css'); }
	if(file_exists($fvm_var_dir_path.'fvm.js')) { @unlink($fvm_var_dir_path.'fvm.js'); }
	if(file_exists($fvm_var_inc_lib.'mrclay' . DIRECTORY_SEPARATOR . 'HTML.php')) { 
		@unlink($fvm_var_inc_lib.'mrclay' . DIRECTORY_SEPARATOR . 'HTML.php');
		@unlink($fvm_var_inc_lib.'mrclay' . DIRECTORY_SEPARATOR . 'index.html');
		@rmdir($fvm_var_inc_lib.'mrclay');
	}
	
	
	# settings migration
	if (get_option("fastvelocity_upgraded") === false) {
		if (get_option("fastvelocity_plugin_version") !== false) {		
		
			# cache path
			if (get_option("fastvelocity_min_change_cache_path") !== false && !isset($fvm_settings['cache']['path'])) { 
				$fvm_settings['cache']['path'] = get_option("fastvelocity_min_change_cache_path");
			}
			
			# cache base_url
			if (get_option("fastvelocity_min_change_cache_base_url") !== false && !isset($fvm_settings['cache']['url'])) { 
				$fvm_settings['cache']['url'] = get_option("fastvelocity_min_change_cache_base_url");
				
			}
			
			# disable html minification
			if (get_option("fastvelocity_min_skip_html_minification") !== false && !isset($fvm_settings['html']['min_disable'])) { 
				$fvm_settings['html']['min_disable'] = 1;
			}
			
			# do not remove html comments
			if (get_option("fastvelocity_min_strip_htmlcomments") !== false && !isset($fvm_settings['html']['nocomments'])) { 
				$fvm_settings['html']['nocomments'] = 1;
			}
			
			
			
			# cdn url
			if (get_option("fastvelocity_min_fvm_cdn_url") !== false && !isset($fvm_settings['cdn']['domain'])) {
				$fvm_settings['cdn']['enable'] = 1;
				$fvm_settings['cdn']['cssok'] = 1;
				$fvm_settings['cdn']['jsok'] = 1;
				$fvm_settings['cdn']['domain'] = get_option("fastvelocity_min_fvm_cdn_url");
			}
			
			# force https
			if (get_option("fastvelocity_min_default_protocol") == 'https' && !isset($fvm_settings['global']['force-ssl'])) { 
				$fvm_settings['global']['force-ssl'] = 1;
			}
			
			# preserve settings on uninstall
			if (get_option("fastvelocity_preserve_settings_on_uninstall") !== false && !isset($fvm_settings['global']['preserve_settings'])) { 
				$fvm_settings['global']['preserve_settings'] = 1;
			}
			
			# inline all css
			if (get_option("fastvelocity_min_force_inline_css") !== false && !isset($fvm_settings['css']['inline-all'])) { 
				$fvm_settings['css']['inline-all'] = 1;
			}
			
			# remove google fonts
			if (get_option("fastvelocity_min_remove_googlefonts") !== false && !isset($fvm_settings['css']['remove'])) { 
				
				# add fonts.gstatic.com
				$arr = array('fonts.gstatic.com');
				$fvm_settings['css']['remove'] = implode(PHP_EOL, fvm_array_order($arr));
				
			}

			# Skip deferring the jQuery library, add them to the header render blocking
			if (get_option("fastvelocity_min_exclude_defer_jquery") !== false && !isset($fvm_settings['js']['merge_header'])) { 

				# add jquery + jquery migrate
				$arr = array('/jquery-migrate-', '/jquery-migrate.js', '/jquery-migrate.min.js', '/jquery.js', '/jquery.min.js');
				$fvm_settings['js']['merge_header'] = implode(PHP_EOL, fvm_array_order($arr));
				
			}
			
			# add recommended default scripts, if legacy mode is enabled
			if (!isset($fvm_settings['js']['merge_defer']) || isset($fvm_settings['js']['merge_defer']) && empty($fvm_settings['js']['merge_defer'])) {
				$arr = array('/ajax.aspnetcdn.com/ajax/', '/ajax.googleapis.com/ajax/libs/', '/cdnjs.cloudflare.com/ajax/libs/', '/stackpath.bootstrapcdn.com/bootstrap/', '/wp-admin/', '/wp-content/', '/wp-includes/');
				$fvm_settings['js']['merge_defer'] = implode(PHP_EOL, fvm_array_order($arr));	
			}
			
			
			# js ignore list
			if (get_option("fastvelocity_min_ignorelist") !== false || get_option("fastvelocity_min_blacklist") !== false || get_option("fastvelocity_min_ignore") !== false && !isset($fvm_settings['js']['ignore']) && !isset($fvm_settings['css']['ignore'])) {
				
				# default
				$arr1 = array();
				$arr2 = array();
				$arr3 = array();
				$arr4 = array();
				$arr5 = array();
				$arr6 = array();
				$arr7 = array();
				$arr8 = array();
				
				# legacy, merge 
				$arr1 = fvm_array_order(fvm_string_toarray(get_option("fastvelocity_min_ignore")));
				$arr2 = fvm_array_order(fvm_string_toarray(get_option("fastvelocity_min_ignorelist")));
				$arr3 = fvm_array_order(fvm_string_toarray(get_option("fastvelocity_min_blacklist")));
				$arr4 = array_merge($arr1, $arr2);
				$arr5 = array_merge($arr4, $arr3);
				$arr6 = fvm_array_order($arr5);
				
				# css / js list
				foreach($arr1 as $c) { if(substr($c, -3) != '.js') { $arr7[] = trim($c); } }
				foreach($arr6 as $c) { if(substr($c, -4) != '.css') { $arr8[] = trim($c); } }
				
				# save settings for css and js
				$fvm_settings['css']['ignore'] = implode(PHP_EOL, $arr7);
				$fvm_settings['js']['ignore'] = implode(PHP_EOL, $arr8);
				
			}

			# clear old cron
			wp_clear_scheduled_hook( 'fastvelocity_purge_old_cron_event' );

			# mark as done
			update_option('fastvelocity_upgraded', true);
		
		}
	}		
	# Version 3.0 routines end
	
	# Version 3.1 routines start
	if (get_option("fastvelocity_plugin_version") !== false) {
		if (version_compare($fvm_var_plugin_version, '3.1.0', '>=' )) {
			
			# cleanup
			delete_option('fastvelocity_upgraded');
			delete_option('fastvelocity_min_change_cache_path');
			delete_option('fastvelocity_min_change_cache_base_url');
			delete_option('fastvelocity_min_fvm_cdn_url');
			delete_option('fastvelocity_plugin_version');
			delete_option('fvm-last-cache-update');
			delete_option('fastvelocity_min_ignore');
			delete_option('fastvelocity_min_blacklist');
			delete_option('fastvelocity_min_ignorelist');
			delete_option('fastvelocity_min_excludecsslist');
			delete_option('fastvelocity_min_excludejslist');
			delete_option('fastvelocity_min_enable_purgemenu');
			delete_option('fastvelocity_min_default_protocol');
			delete_option('fastvelocity_min_disable_js_merge');
			delete_option('fastvelocity_min_disable_css_merge');
			delete_option('fastvelocity_min_disable_js_minification');
			delete_option('fastvelocity_min_disable_css_minification');
			delete_option('fastvelocity_min_remove_print_mediatypes');
			delete_option('fastvelocity_min_skip_html_minification');
			delete_option('fastvelocity_min_strip_htmlcomments');
			delete_option('fastvelocity_min_skip_cssorder');
			delete_option('fastvelocity_min_skip_google_fonts');
			delete_option('fastvelocity_min_skip_emoji_removal');
			delete_option('fastvelocity_fvm_clean_header_one');
			delete_option('fastvelocity_min_enable_defer_js');
			delete_option('fastvelocity_min_exclude_defer_jquery');
			delete_option('fastvelocity_min_force_inline_css');
			delete_option('fastvelocity_min_force_inline_css_footer');
			delete_option('fastvelocity_min_remove_googlefonts');
			delete_option('fastvelocity_min_defer_for_pagespeed');
			delete_option('fastvelocity_min_defer_for_pagespeed_optimize');
			delete_option('fastvelocity_min_exclude_defer_login');
			delete_option('fastvelocity_min_skip_defer_lists');
			delete_option('fastvelocity_min_fvm_fix_editor');
			delete_option('fastvelocity_min_loadcss');
			delete_option('fastvelocity_min_fvm_removecss');
			delete_option('fastvelocity_enabled_css_preload');
			delete_option('fastvelocity_enabled_js_preload');
			delete_option('fastvelocity_fontawesome_method');
			delete_option('fastvelocity_gfonts_method');
			
		}
	}
	# Version 3.1 routines end
	
	# return settings array
	return $fvm_settings;
}


