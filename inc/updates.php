<?php

# update routines for new fields and replacements
function fvm_get_updated_field_routines($fvm_settings) {
	
	# must have
	if(!is_array($fvm_settings)) { return $fvm_settings; }
	
	# Version 3.0 routines start
	if (get_option("fastvelocity_plugin_version") !== false) { 
		
		# cache path
		if (get_option("fastvelocity_min_change_cache_path") !== false) { 
			$fvm_settings['cache']['path'] = get_option("fastvelocity_min_change_cache_path");
			delete_option('fastvelocity_min_change_cache_path');
		}
		
		# cache base_url
		if (get_option("fastvelocity_min_change_cache_base_url") !== false) { 
			$fvm_settings['cache']['url'] = get_option("fastvelocity_min_change_cache_base_url");
			delete_option('fastvelocity_min_change_cache_base_url');
		}
				
		# cleanup
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
		delete_option('fastvelocity_min_fvm_cdn_url');
		delete_option('fastvelocity_enabled_css_preload');
		delete_option('fastvelocity_enabled_js_preload');
		delete_option('fastvelocity_fontawesome_method');
		delete_option('fastvelocity_gfonts_method');
		
		# clear cron
		wp_clear_scheduled_hook( 'fastvelocity_purge_old_cron_event' );
			
	}		
	# Version 3.0 routines end
	
	# return settings array
	return $fvm_settings;
}


