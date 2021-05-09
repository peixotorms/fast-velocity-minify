<?php

# Exit if accessed directly				
if (!defined('ABSPATH')){ exit(); }	

# check for minimum requirements and prevent activation or disable if not fully compatible
function fvm_check_minimum_requirements() {
	if(current_user_can('manage_options')) {
		
		# defaults
		$error = '';

		# php version requirements
		if (version_compare( PHP_VERSION, '5.6', '<' )) { 
			$error = __( 'FVM requires PHP 5.6 or higher. You’re still on', 'fast-velocity-minify' ) .' '. PHP_VERSION; 
		}

		# php extension requirements	
		if (!extension_loaded('mbstring')) { 
			$error = __( 'FVM requires the PHP mbstring module to be installed on the server.', 'fast-velocity-minify' ); 
		}
		
		# wp version requirements
		if ( version_compare( $GLOBALS['wp_version'], '4.9', '<' ) ) {
			$error = __( 'FVM requires WP 4.9 or higher. You’re still on', 'fast-velocity-minify' ) .' '. $GLOBALS['wp_version']; 
		}
		
		# check cache directory
		$ch_info = fvm_get_cache_location();
		if(isset($ch_info['ch_url'])  && !empty($ch_info['ch_url']) && isset($ch_info['ch_dir']) && !empty($ch_info['ch_dir'])) {
			if(is_dir($ch_info['ch_dir']) && !is_writable($ch_info['ch_dir'])) {
				$error = __( 'FVM needs writing permissions on ', 'fast-velocity-minify' ). ' ['.$ch_info['ch_dir'].']';
			}
		}		
		
		# deactivate plugin forcefully
		global $fvm_var_basename;
		if ((is_plugin_active($fvm_var_basename) && !empty($error)) || !empty($error)) { 
		if (isset($_GET['activate'])) { unset($_GET['activate']); }
			deactivate_plugins($fvm_var_basename); 
			add_settings_error( 'fvm_admin_notice', 'fvm_admin_notice', $error, 'success' );
		}
		
	}
}


# check for soft errors and misconfiguration
function fvm_check_misconfiguration() {
	try {
				
		# plugin version
		global $fvm_var_plugin_version;
		if(is_null($fvm_var_plugin_version)) { return false; }
				
		# if no database version, regenerate
		$plugin_meta = get_option('fvm_plugin_meta');
		if($plugin_meta === false) {
			
			# startup routines
			fvm_plugin_deactivate();
			fvm_plugin_activate();
			
			# save
			update_option('fvm_plugin_meta', json_encode(array('dbv'=>0)) );
		}
		
		# updates
		if($plugin_meta !== false) {
			
			# future updates
			$meta = json_decode($plugin_meta, true);
			$previous_version = $meta['dbv'];
			if($fvm_var_plugin_version != $previous_version) {
				
				# startup routines
				fvm_plugin_deactivate();
				fvm_plugin_activate();
				
				# save
				update_option('fvm_plugin_meta', json_encode(array('dbv'=>$fvm_var_plugin_version)) );
				
			}
			
		}

	} catch (Exception $e) {
		error_log('Caught exception (fvm_initialize_database): '.$e->getMessage(), 0);
	}
}




# save plugin settings on wp-admin
function fvm_save_settings() {

	# save settings
	if(isset($_POST['fvm_action']) && isset($_POST['fvm_settings_nonce']) && $_POST['fvm_action'] == 'save_settings') {
		
		if(!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.', 'fast-velocity-minify'), __('Error:', 'fast-velocity-minify'), array('response'=>200)); 
		}
		
		if(!wp_verify_nonce($_POST['fvm_settings_nonce'], 'fvm_settings_nonce')) {
			wp_die( __('Invalid nounce. Please refresh and try again.', 'fast-velocity-minify'), __('Error:', 'fast-velocity-minify'), array('response'=>200)); 
		}
		
		# update fvm_settings in the global scope
		if(isset($_POST['fvm_settings']) && is_array($_POST['fvm_settings'])) {
			
			# sanitize recursively
			if(is_array($_POST['fvm_settings'])) {
				foreach ($_POST['fvm_settings'] as $group=>$arr) {
					if(is_array($arr)) {
						foreach ($arr as $k=>$v) {
							
							# only numeric, string or arrays allowed at this level
							if(!is_string($v) && !is_numeric($v) && !is_array($v)) { $_POST['fvm_settings'][$group][$k] = ''; }
							
							# numeric fields, only positive integers allowed 
							if(is_numeric($v)) { $_POST['fvm_settings'][$group][$k] = abs(intval($v)); }
							
							# sanitize text area content
							if(is_string($v)) { $_POST['fvm_settings'][$group][$k] = strip_tags($v); }
							
							# clean cdn url
							if($group == 'cdn' && $k == 'url') { 
								$_POST['fvm_settings'][$group][$k] = trim(trim(str_replace(array('http://', 'https://'), '', $v), '/'));
							}
		
						}
					}
				}
			}
			
			# get mandatory default exclusions
			global $fvm_settings;
			$fvm_settings = fvm_get_default_settings($_POST['fvm_settings']);
			
			# purge caches
			fvm_purge_all();
			
			# save settings
			update_option('fvm_settings', json_encode($fvm_settings), false);
			add_settings_error( 'fvm_admin_notice', 'fvm_admin_notice', 'Settings saved successfully!', 'success' );
		
		} else {
			wp_die( __('Invalid data!', 'fast-velocity-minify'), __('Error:', 'fast-velocity-minify'), array('response'=>200)); 
		}
	}
}

# return checked, or empty for checkboxes in admin
function fvm_get_settings_checkbox($value) {
	if($value == 1) { return 'checked'; }
	return '';
}

# return checked, or empty for checkboxes in admin
function fvm_get_settings_radio($key, $value) {
	if($key == $value) { return 'checked'; }
	return '';
}


# add settings link on plugins listing page
add_filter("plugin_action_links_".$fvm_var_basename, 'fvm_min_settings_link' );
function fvm_min_settings_link($links) {
	global $fvm_var_basename;
	if (is_plugin_active($fvm_var_basename)) { 
		$settings_link = '<a href="'.admin_url('admin.php?page=fvm').'">Settings</a>'; 
		array_unshift($links, $settings_link); 
	}
return $links;
}


# Enqueue plugin UI CSS and JS files
function fvm_add_admin_jscss($hook) {
	if(current_user_can('manage_options')) {
		if ('settings_page_fvm' != $hook) { return; }
		global $fvm_var_dir_path, $fvm_var_url_path;
		
		# ui
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-accordion' );
		
		# js
		wp_enqueue_script('fvm', $fvm_var_url_path . 'assets/fvm.js', array('jquery'), filemtime($fvm_var_dir_path.'assets'. DIRECTORY_SEPARATOR .'fvm.js'));
		
		# css
		wp_enqueue_style('fvm', $fvm_var_url_path . 'assets/fvm.css', array(), filemtime($fvm_var_dir_path.'assets'. DIRECTORY_SEPARATOR .'fvm.css'));
		
	}
}


# create sidebar admin menu and add templates to admin
function fvm_add_admin_menu() {
	if (current_user_can('manage_options')) {
		add_options_page('FVM Settings', 'Fast Velocity Minify', 'manage_options', 'fvm', 'fvm_add_settings_admin');
	}
}


# print admin notices when needed (json)
function fvm_show_admin_notice_from_transient() {
	if(current_user_can('manage_options')) {
		$inf = get_transient('fvm_admin_notice');
		if($inf != false && !empty($inf)) {
			$jsonarr = json_decode($inf, true);
			if(!is_null($jsonarr) && is_array($jsonarr)){
				
				# add all
				$jsonarr = array_unique($jsonarr);
				foreach ($jsonarr as $notice) {
					add_settings_error( 'fvm_admin_notice', 'fvm_admin_notice', 'FVM: '.$notice, 'info' );
				}	
				
				# output on other pages
				if(!isset($_GET['page']) || (isset($_GET['page']) && $_GET['page'] != 'fvm')) {
					settings_errors( 'fvm_admin_notice' );
				}
			}
			
			# remove
			delete_transient('fvm_admin_notice');
		}
	}
}

# manage settings page
function fvm_add_settings_admin() {
	
	# admin only
	if (!current_user_can('manage_options')) { 
		wp_die( __('You do not have sufficient permissions to access this page.'), __('Error:'), array('response'=>200)); 
	}

	# include admin html template
	global $fvm_settings, $fvm_var_dir_path;
	
	# admin html templates
	include($fvm_var_dir_path . 'layout' . DIRECTORY_SEPARATOR . 'admin-layout.php');

}


# function to list all cache files on the status page (js ajax code)
function fvm_get_logs_callback() {
		
	# must be able to cleanup cache
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.'), __('Error:'), array('response'=>200)); 
	}
	
	# must have
	if(!defined('WP_CONTENT_DIR')) { 
		wp_die( __('WP_CONTENT_DIR is undefined!'), __('Error:'), array('response'=>200)); 
	}
	
	# defaults
	global $wpdb;
	if(is_null($wpdb)) { 
		wp_die( __('Database error!'), __('Error:'), array('response'=>200)); 
	}
	
	# initialize log
	$log = '';
		
	# build css logs from database
	$results = $wpdb->get_results("SELECT date, msg FROM ".$wpdb->prefix."fvm_logs ORDER BY id DESC LIMIT 500", 'ARRAY_A');
		
	# build log
	if(is_array($results)) {
		foreach (array_reverse($results, true) as $r) {
			$log.= 'PROCESSED ON - ' . date('r', $r['date']) . PHP_EOL;
			$log.= $r['msg'] .  PHP_EOL . PHP_EOL;
		}
	}
	
	# default message
	if(empty($log)) { $log = 'No logs generated yet.'; }

		# build info
		$result = array(
			'log' => $log,
			'success' => 'OK'
		);
		
		# return result
		header('Content-Type: application/json');
		echo json_encode($result);
		exit();
	
}


# run during activation
register_activation_hook($fvm_var_file, 'fvm_plugin_activate');
function fvm_plugin_activate() {
		
	global $wpdb;
	if(is_null($wpdb)) { return false; }
		
	# defauls
	$sql = array();
	$wpdb_collate = $wpdb->collate;
	
	# create cache table
	$sqla_table_name = $wpdb->prefix . 'fvm_cache';
	$sqla = "CREATE TABLE IF NOT EXISTS {$sqla_table_name} (
         `id` bigint(20) unsigned NOT NULL auto_increment ,
         `uid` varchar(64) NOT NULL,
		 `date` bigint(10) unsigned NOT NULL, 
		 `type` varchar(3) NOT NULL, 
		 `content` mediumtext NOT NULL, 
		 `meta` mediumtext NOT NULL,
         PRIMARY KEY  (id),
		 UNIQUE KEY uid (uid), 
		 KEY date (date), KEY type (type) 
         )
         COLLATE {$wpdb_collate}";
		 
	# create logs table
	$sqlb_table_name = $wpdb->prefix . 'fvm_logs';
	$sqlb = "CREATE TABLE IF NOT EXISTS {$sqlb_table_name} (
         `id` bigint(20) unsigned NOT NULL auto_increment, 
		 `uid` varchar(64) NOT NULL,
		 `date` bigint(10) unsigned NOT NULL, 
		 `type` varchar(10) NOT NULL, 
		 `msg` mediumtext NOT NULL, 
		 `meta` mediumtext NOT NULL, 
		 PRIMARY KEY  (id), 
		 UNIQUE KEY uid (uid), 
		 KEY date (date), 
		 KEY type (type)
         )
         COLLATE {$wpdb_collate}";

	# run sql
	$wpdb->query($sqla);
	$wpdb->query($sqlb);

}


# run during deactivation
register_deactivation_hook($fvm_var_file, 'fvm_plugin_deactivate');
function fvm_plugin_deactivate() {

	global $wpdb;
	if(is_null($wpdb)) { return false; }
	
	# remove options and tables
	$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name = 'fvm_last_cache_update'");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fvm_cache");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fvm_logs");
	
	# process cache settings
	fvm_purge_static_files();

}

# run during uninstall
register_uninstall_hook($fvm_var_file, 'fvm_plugin_uninstall');	
function fvm_plugin_uninstall() {
	global $wpdb;
	if(is_null($wpdb)) { return false; }
	
	# remove options and tables
	$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name = 'fvm_settings'");
	$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name = 'fvm_last_cache_update'");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fvm_cache");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fvm_logs");
	
	# process cache settings
	fvm_purge_static_files();
	
}


# get all known roles
function fvm_get_user_roles_checkboxes() {
	
	global $wp_roles, $fvm_settings;
	$roles_list = array();
	if(is_object($wp_roles)) {
		$roles = (array) $wp_roles->get_names();
		foreach ($roles as $role=>$rname) {
			
			$roles_list[] = '<label for="fvm_settings_minify_'.$role.'"><input name="fvm_settings[minify]['.$role.']" type="checkbox" id="fvm_settings_minify_'.$role.'" value="1" '. fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'minify', $role)).'> '.$rname.' </label><br />';
		
		}
	}
	
	# return
	if(!empty($roles_list)) { return implode(PHP_EOL, $roles_list); } else { return __( 'No roles detected!', 'fast-velocity-minify' ); }

}