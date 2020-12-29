<?php

# Exit if accessed directly				
if (!defined('ABSPATH')){ exit(); }	

# functions needed for both frontend or backend

# top admin toolbar for cache purging
function fvm_admintoolbar() {
	if(current_user_can('manage_options')) {
		global $wp_admin_bar;

		# Add top menu to admin bar
		$wp_admin_bar->add_node(array(
			'id'    => 'fvm_menu',
			'title' => __("FVM", 'fvm') . '</span>',
			'href'  => wp_nonce_url(add_query_arg('fvm_do', 'clear_all'), 'fvm_clear', '_wpnonce')
		));
		
		# Add submenu
		$wp_admin_bar->add_node(array(
			'id'    => 'fvm_submenu_purge_all',
			'parent'    => 'fvm_menu', 
			'title' => __("Clear Everything", 'fvm'),
			'href'  => wp_nonce_url(add_query_arg('fvm_do', 'clear_all'), 'fvm_clear', '_wpnonce')			
		));
		
		# Add submenu
		$wp_admin_bar->add_node(array(
			'id'    => 'fvm_submenu_settings',
			'parent'    => 'fvm_menu', 
			'title' => __("FVM Settings", 'fvm'),
			'href'  => admin_url('options-general.php?page=fvm')
		));
		
		/*
		# Add submenu
		$wp_admin_bar->add_node(array(
			'id'    => 'fvm_submenu_upgrade',
			'parent'    => 'fvm_menu', 
			'title' => __("Upgrade", 'fvm'),
			'href'  => admin_url('options-general.php?page=fvm&tab=upgrade')
		));
		*/
		
		# Add submenu
		$wp_admin_bar->add_node(array(
			'id'    => 'fvm_submenu_help',
			'parent'    => 'fvm_menu', 
			'title' => __("Help", 'fvm'),
			'href'  => admin_url('options-general.php?page=fvm&tab=help')
		));

	}
}


# purge all caches when clicking the button on the admin bar
function fvm_process_cache_purge_request(){
	
	if(isset($_GET['fvm_do']) && isset($_GET['_wpnonce'])) {
		
		# must be able to cleanup cache
		if (!current_user_can('manage_options')) { 
			wp_die( __('You do not have sufficient permissions to access this page.', 'fast-velocity-minify'), __('Error:', 'fast-velocity-minify'), array('response'=>200)); 
		}
		
		# validate nonce
		if(!wp_verify_nonce($_GET['_wpnonce'], 'fvm_clear')) {
			wp_die( __('Invalid or expired request... please go back and refresh before trying again!', 'fast-velocity-minify'), __('Error:', 'fast-velocity-minify'), array('response'=>200)); 
		}
		
		# Purge All
		if($_GET['fvm_do'] == 'clear_all') {
			
			# purge everything
			$cache = fvm_purge_minification();
			$others = fvm_purge_others();
			
			if(is_admin()) {
				
				# merge notices
				$notices = array();
				if(is_string($cache)) { $notices[] = $cache; }
				if(is_string($others)) { $notices[] = $others; }
				
				# save transient for after the redirect
				if(count($notices) == 0) { $notices[] = __( 'FVM: All Caches are now cleared.', 'fast-velocity-minify' ) . ' ('.date("D, d M Y @ H:i:s e").')'; }
				set_transient( 'fvm_admin_notice', json_encode($notices), 10);
				
			}

		}
						
		# https://developer.wordpress.org/reference/functions/wp_safe_redirect/
		nocache_headers();
		wp_safe_redirect(remove_query_arg('_wpnonce', remove_query_arg('_fvm', wp_get_referer())));
		exit();
	}
}


# get cache directories and urls
function fvm_cachepath() {
	
	# must have
	if(!defined('WP_CONTENT_DIR')) { return false; }
	if(!defined('WP_CONTENT_URL')) { return false; }
	
	# to var
	$wp_content_dir = WP_CONTENT_DIR;
	$wp_content_url = WP_CONTENT_URL;
	
	# globals
	global $fvm_settings;
	
	# force https option
	if(isset($fvm_settings['global']['force-ssl']) && $fvm_settings['global']['force-ssl'] == true) {		
		$wp_content_url = str_ireplace('http:', 'https:', $wp_content_url);
	}

	# define cache directory
	$cache_dir         = $wp_content_dir . DIRECTORY_SEPARATOR . 'cache';
	$cache_base_dir    = $cache_dir . DIRECTORY_SEPARATOR .'fvm';
	$cache_base_dirurl = $wp_content_url . '/cache/fvm';
	
	# use alternative directory?
	if(isset($fvm_settings['cache']['path']) && !empty($fvm_settings['cache']['path']) && isset($fvm_settings['cache']['url']) && !empty($fvm_settings['cache']['url']) && is_dir($fvm_settings['cache']['path'])) {
		$cache_dir         = rtrim($fvm_settings['cache']['path'], '/');
		$cache_base_dir    = $cache_dir . DIRECTORY_SEPARATOR .'fvm';
		$cache_base_dirurl = rtrim($fvm_settings['cache']['url'], '/') . '/fvm';
	}
	
	# get requested hostname
	$host = fvm_get_domain();
	
	$cache_dir_min  = $cache_base_dir . DIRECTORY_SEPARATOR . 'min' . DIRECTORY_SEPARATOR . $host;
	$cache_url_min  = $cache_base_dirurl . '/min/' .$host;
		
	# mkdir and check if umask requires chmod, but only for hosts matching the site_url'
	$dirs = array($cache_dir, $cache_base_dir, $cache_dir_min);
	foreach ($dirs as $d) {
		fvm_create_dir($d);
	}

	# return
	return array(
		'cache_base_dir'=>$cache_base_dir, 
		'cache_base_dirurl'=>$cache_base_dirurl,
		'cache_dir_min'=>$cache_dir_min, 
		'cache_url_min'=>$cache_url_min
		);
}


# Purge everything
function fvm_purge_all() {
	fvm_purge_minification();
	fvm_purge_others();	
	return true;	
}

# Purge minification only
function fvm_purge_minification() {
	
	# flush opcache
	if(function_exists('opcache_reset')) { 
		@opcache_reset(); 
	}
	
	# increment cache file names
	$now = fvm_cache_increment();

	# truncate cache table
	global $wpdb;
	$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}fvm_cache");
	$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}fvm_logs");
	
	# get cache and min directories
	global $fvm_cache_paths, $fvm_settings;
	
	# fetch settings on wp-cli
	if(is_null($fvm_settings)) { $fvm_settings = fvm_get_settings(); }
	if(is_null($fvm_cache_paths)) { $fvm_cache_paths = fvm_cachepath(); }
		
	# purge html directory?
	if(isset($fvm_cache_paths['cache_dir_min']) && is_dir($fvm_cache_paths['cache_dir_min']) && is_writable($fvm_cache_paths['cache_dir_min']) && stripos($fvm_cache_paths['cache_dir_min'], '/fvm') !== false) {
		
		# purge css/js files instantly
		if(isset($fvm_settings['cache']['min_instant_purge']) && $fvm_settings['cache']['min_instant_purge'] == true) {
			$result = fvm_purge_minification_now();
			return $result;
		} else {
			# schedule purge for 24 hours later, only once
			add_action( 'fvm_purge_minification_later', 'fvm_purge_minification_expired' );
			wp_schedule_single_event(time() + 3600 * 24, 'fvm_purge_minification_later');
			return __( 'Expired minification files are set to be deleted in 24 hours.', 'fast-velocity-minify' );
		}
		
	} else {
		return __( 'The cache directory is not rewritable!', 'fast-velocity-minify' );
	}
	
	return false;	
}


# purge minified files right now
function fvm_purge_minification_now() {
	global $fvm_cache_paths;
	if(isset($fvm_cache_paths['cache_dir_min']) && stripos($fvm_cache_paths['cache_dir_min'], '/fvm') !== false) {
		$result = fvm_rrmdir($fvm_cache_paths['cache_dir_min']);
		return $result;
	} else {
		return __( 'The cache directory is not writeable!', 'fast-velocity-minify' );
	}
}

# purge expired minification files only
function fvm_purge_minification_expired() {
	global $fvm_cache_paths;
	if(isset($fvm_cache_paths['cache_dir_min']) && !empty($fvm_cache_paths['cache_dir_min']) && stripos($fvm_cache_paths['cache_dir_min'], '/fvm') !== false) {
		
		# must be on the allowed path
		$wd = $fvm_cache_paths['cache_dir_min'];
		if(empty($wd) || !defined('WP_CONTENT_DIR') || stripos($wd, '/fvm') === false) {
			return __( 'Requested purge path is not allowed!', 'fast-velocity-minify' );
		}
		
		# prefix
		$skip = get_option('fvm_last_cache_update', '0');
		
		# purge only the expired cache that doesn't match the current cache version prefix and it's older than 24 hours
		clearstatcache();
		if(is_dir($wd)) {
			try {
				$i = new DirectoryIterator($wd);
				foreach($i as $f){
					if($f->isFile() && stripos(basename($f->getRealPath()), $skip) === false){ 
						if($f->getMTime() <= time() - 86400) {
							@unlink($f->getRealPath());
						}
					}
				}
			} catch (Exception $e) {
				return get_class($e) . ": " . $e->getMessage();
			}
		}
		
		return __( 'Expired cache is now deleted!', 'fast-velocity-minify' );
	}
}


# purge supported hosting and plugins
function fvm_purge_others(){

	# third party plugins
		
	# Purge all W3 Total Cache
	if (function_exists('w3tc_pgcache_flush')) {
		w3tc_pgcache_flush();
		return __( 'All caches on <strong>W3 Total Cache</strong> have been purged.', 'fast-velocity-minify' );
	}

	# Purge WP Super Cache
	if (function_exists('wp_cache_clear_cache')) {
		wp_cache_clear_cache();
		return __( 'All caches on <strong>WP Super Cache</strong> have been purged.', 'fast-velocity-minify' );
	}

	# Purge WP Rocket
	if (function_exists('rocket_clean_domain')) {
		rocket_clean_domain();
		return __( 'All caches on <strong>WP Rocket</strong> have been purged.', 'fast-velocity-minify' );
	}

	# Purge Cachify
	if (function_exists('cachify_flush_cache')) {
		cachify_flush_cache();
		return __( 'All caches on <strong>Cachify</strong> have been purged.', 'fast-velocity-minify' );
	}

	# Purge Comet Cache
	if ( class_exists("comet_cache") ) {
		comet_cache::clear();
		return __( 'All caches on <strong>Comet Cache</strong> have been purged.', 'fast-velocity-minify' );
	}

	# Purge Zen Cache
	if ( class_exists("zencache") ) {
		zencache::clear();
		return __( 'All caches on <strong>Comet Cache</strong> have been purged.', 'fast-velocity-minify' );
	}

	# Purge LiteSpeed Cache 
	if (class_exists('LiteSpeed_Cache_Tags')) {
		LiteSpeed_Cache_Tags::add_purge_tag('*');
		return __( 'All caches on <strong>LiteSpeed Cache</strong> have been purged.', 'fast-velocity-minify' );
	}

	# Purge Hyper Cache
	if (class_exists( 'HyperCache' )) {
		do_action( 'autoptimize_action_cachepurged' );
		return __( 'All caches on <strong>HyperCache</strong> have been purged.', 'fast-velocity-minify' );
	}

	# purge cache enabler
	if ( has_action('ce_clear_cache') ) {
		do_action('ce_clear_cache');
		return __( 'All caches on <strong>Cache Enabler</strong> have been purged.', 'fast-velocity-minify' );
	}

	# purge wpfc
	if (function_exists('wpfc_clear_all_cache')) {
		wpfc_clear_all_cache(true);
	}

	# add breeze cache purge support
	if (class_exists("Breeze_PurgeCache")) {
		Breeze_PurgeCache::breeze_cache_flush();
		return __( 'All caches on <strong>Breeze</strong> have been purged.', 'fast-velocity-minify' );
	}


	# swift
	if (class_exists("Swift_Performance_Cache")) {
		Swift_Performance_Cache::clear_all_cache();
		return __( 'All caches on <strong>Swift Performance</strong> have been purged.', 'fast-velocity-minify' );
	}


	# hosting companies

	# Purge SG Optimizer (Siteground)
	if (function_exists('sg_cachepress_purge_cache')) {
		sg_cachepress_purge_cache();
		return __( 'All caches on <strong>SG Optimizer</strong> have been purged.', 'fast-velocity-minify' );
	}

	# Purge Godaddy Managed WordPress Hosting (Varnish + APC)
	if (class_exists('WPaaS\Plugin') && method_exists( 'WPass\Plugin', 'vip' )) {
		fvm_godaddy_request('BAN');
		return __( 'A cache purge request has been sent to <strong>Go Daddy Varnish</strong>', 'fast-velocity-minify' );
	}


	# Purge WP Engine
	if (class_exists("WpeCommon")) {
		if (method_exists('WpeCommon', 'purge_memcached')) { WpeCommon::purge_memcached(); }
		if (method_exists('WpeCommon', 'purge_varnish_cache')) { WpeCommon::purge_varnish_cache(); }
		if (method_exists('WpeCommon', 'purge_memcached') || method_exists('WpeCommon', 'purge_varnish_cache')) {
			return __( 'A cache purge request has been sent to <strong>WP Engine</strong>', 'fast-velocity-minify' );
		}
	}

	# Purge Kinsta
	global $kinsta_cache;
	if ( isset($kinsta_cache) && class_exists('\\Kinsta\\CDN_Enabler')) {
		if (!empty( $kinsta_cache->kinsta_cache_purge)){
			$kinsta_cache->kinsta_cache_purge->purge_complete_caches();
			return __( 'A cache purge request has been sent to <strong>Kinsta</strong>', 'fast-velocity-minify' );
		}
	}

	# Purge Pagely
	if ( class_exists( 'PagelyCachePurge' ) ) {
		$purge_pagely = new PagelyCachePurge();
		$purge_pagely->purgeAll();
		return __( 'A cache purge request has been sent to <strong>Pagely</strong>', 'fast-velocity-minify' );
	}

	# Purge Pressidum
	if (defined('WP_NINUKIS_WP_NAME') && class_exists('Ninukis_Plugin')){
		$purge_pressidum = Ninukis_Plugin::get_instance();
		$purge_pressidum->purgeAllCaches();
		return __( 'A cache purge request has been sent to <strong>Pressidium</strong>', 'fast-velocity-minify' );
	}

	# Purge Savvii
	if (defined( '\Savvii\CacheFlusherPlugin::NAME_DOMAINFLUSH_NOW')) {
		$purge_savvii = new \Savvii\CacheFlusherPlugin();
		if ( method_exists( $plugin, 'domainflush' ) ) {
			$purge_savvii->domainflush();
			return __( 'A cache purge request has been sent to <strong>Savvii</strong>', 'fast-velocity-minify' );
		}
	}

	# Purge Pantheon Advanced Page Cache plugin
	if(function_exists('pantheon_wp_clear_edge_all')) {
		pantheon_wp_clear_edge_all();
	}

	# wordpress default cache
	if (function_exists('wp_cache_flush')) {
		wp_cache_flush();
	}
	
}


# Purge Godaddy Managed WordPress Hosting (Varnish)
function fvm_godaddy_request( $method, $url = null ) {
	$url  = empty( $url ) ? home_url() : $url;
	$host = parse_url( $url, PHP_URL_HOST );
	$url  = set_url_scheme( str_replace( $host, WPaas\Plugin::vip(), $url ), 'http' );
	update_option( 'gd_system_last_cache_flush', time(), 'no'); # purge apc
	wp_remote_request( esc_url_raw( $url ), array('method' => $method, 'blocking' => false, 'headers' => array('Host' => $host)) );
}


# check if we can minify the page
function fvm_can_minify() {
	
	global $fvm_urls;
	
	# only GET requests allowed
	if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
		return false;
	}
		
	# compatibility with DONOTCACHEPAGE
	if( defined('DONOTCACHEPAGE') && DONOTCACHEPAGE ){ return false; }
	
	# detect api requests (only defined after parse_request hook)
	if( defined('REST_REQUEST') && REST_REQUEST ){ return false; } 
	
	# always skip on these tasks
	if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){ return false; }
	if( defined('WP_INSTALLING') && WP_INSTALLING ){ return false; }
	if( defined('WP_REPAIRING') && WP_REPAIRING ){ return false; }
	if( defined('WP_IMPORTING') && WP_IMPORTING ){ return false; }
	if( defined('DOING_AJAX') && DOING_AJAX ){ return false; }
	if( defined('WP_CLI') && WP_CLI ){ return false; }
	if( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ){ return false; }
	if( defined('WP_ADMIN') && WP_ADMIN ){ return false; }
	if( defined('SHORTINIT') && SHORTINIT ){ return false; }
	if( defined('IFRAME_REQUEST') && IFRAME_REQUEST ){ return false; }
	
	# don't minify specific WordPress areas
	if(function_exists('is_404') && is_404()){ return false; }
	if(function_exists('is_feed') && is_feed()){ return false; }
	if(function_exists('is_comment_feed') && is_comment_feed()){ return false; }
	if(function_exists('is_attachment') && is_attachment()){ return false; }
	if(function_exists('is_trackback') && is_trackback()){ return false; }
	if(function_exists('is_robots') && is_robots()){ return false; }
	if(function_exists('is_preview') && is_preview()){ return false; }
	if(function_exists('is_customize_preview') && is_customize_preview()){ return false; }	
	if(function_exists('is_embed') && is_embed()){ return false; }
	if(function_exists('is_admin') && is_admin()){ return false; }
	if(function_exists('is_blog_admin') && is_blog_admin()){ return false; }
	if(function_exists('is_network_admin') && is_network_admin()){ return false; }
	
	# don't minify specific WooCommerce areas
	if(function_exists('is_checkout') && is_checkout()){ return false; }
	if(function_exists('is_account_page') && is_account_page()){ return false; }
	if(function_exists('is_ajax') && is_ajax()){ return false; }
	if(function_exists('is_wc_endpoint_url') && is_wc_endpoint_url()){ return false; }
	
	# don't minify amp pages by the amp plugin
	if(function_exists('is_amp_endpoint') && is_amp_endpoint()){ return false; }
	if(function_exists('ampforwp_is_amp_endpoint') && ampforwp_is_amp_endpoint()){ return false; }
	
	# get requested hostname
	$host = fvm_get_domain();
	
	# only for hosts matching the site_url
	if(isset($fvm_urls['wp_domain']) && !empty($fvm_urls['wp_domain'])) {
		if($host != $fvm_urls['wp_domain']) {
			return false;
		}
	}
	
	# if there is an url, avoid known static files
	if(isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
		
		# parse url (path, query)
		$ruri = str_replace('//', '/', str_replace('..', '', preg_replace( '/[ <>\'\"\r\n\t\(\)]/', '', strtok($_SERVER['REQUEST_URI'], '?'))));

		# avoid robots.txt and other situations
		$noext = array('.txt', '.xml', '.map', '.css', '.js', '.png', '.jpeg', '.jpg', '.gif', '.webp', '.ico', '.php', '.htaccess', '.json', '.pdf', '.mp4', '.webm', '.zip', '.sql', '.gz');
		foreach ($noext as $ext) {
			if(substr($ruri, -strlen($ext)) == $ext) {
				return false;
			}
		}		
		
	}
	
	# user roles
	if(function_exists('is_user_logged_in')) {
		if(is_user_logged_in()) {
			
			# get user roles
			global $fvm_settings;
			$user = wp_get_current_user();
			$roles = (array) $user->roles;
			foreach($roles as $role) {
				if(isset($fvm_settings['minify'][$role])) { return true; }
			}
			
			# disable for logged in users by default
			return false;
		}
	}
	
	
	
	# default
	return true;
}


# create a directory, recursively
function fvm_create_dir($d) {

	# create recursively
	if(!is_dir($d) && function_exists('wp_mkdir_p')) {
		wp_mkdir_p($d);
	}
	
	return true;
}


# check if PHP has some functions disabled
function fvm_function_available($func) {
	if (ini_get('safe_mode')) return false;
	$disabled = ini_get('disable_functions');
	if ($disabled) {
		$disabled = explode(',', $disabled);
		$disabled = array_map('trim', $disabled);
		return !in_array($func, $disabled);
	}
	return true;
}


# open a multiline string, order, filter duplicates and return as array
function fvm_string_toarray($value){
	$arr = explode(PHP_EOL, $value);
	return fvm_array_order($arr);}

# filter duplicates, order and return array
function fvm_array_order($arr){
	if(!is_array($arr)) { return array(); }
	$a = array_map('trim', $arr);
	$b = array_filter($a);
	$c = array_unique($b);
	sort($c);
	return $c;
}


# return size in human format
function fvm_format_filesize($bytes, $decimals = 2) {
    $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
    for ($i = 0; ($bytes / 1024) > 0.9; $i++, $bytes /= 1024) {}
	if($i == 0) { $i = 1; $bytes = $bytes / 1024; } # KB+ only
    return sprintf( "%1.{$decimals}f %s", round( $bytes, $decimals ), $units[$i] );
}


# increment file names
function fvm_cache_increment() {
	$now = time();
	update_option('fvm_last_cache_update', $now, 'no');
	return $now;
}


# remove a director, recursively
function fvm_rrmdir($path) {

	# must be on the allowed path
	if(empty($path) || !defined('WP_CONTENT_DIR') || stripos($path, '/fvm') === false) {
		return __( 'Requested purge path is not allowed!', 'fast-velocity-minify' );
	}
	
	# purge recursively
	clearstatcache();
	if(is_dir($path)) {
		try {
			$i = new DirectoryIterator($path);
			foreach($i as $f){
				if($f->isFile()){ @unlink($f->getRealPath());
				} else if(!$f->isDot() && $f->isDir()){
					fvm_rrmdir($f->getRealPath());
					if(is_dir($f->getRealPath())) { @rmdir($f->getRealPath()); }
				}
			}
		} catch (Exception $e) {
			return get_class($e) . ": " . $e->getMessage();
		}
		
		# self
		if(is_dir($path)) { @rmdir($path); }
	}
	
}


# Fix the permission bits on generated files
function fvm_fix_permission_bits($file){

	# must be on the allowed path
	if(empty($file) || !defined('WP_CONTENT_DIR') || stripos($file, '/fvm') === false) {
		return __( 'Requested path is not allowed!', 'fast-velocity-minify' );
	}
	
	if(function_exists('stat') && fvm_function_available('stat')) {
		if ($stat = @stat(dirname($file))) {
			$perms = $stat['mode'] & 0007777;
			@chmod($file, $perms);
			clearstatcache();
			return true;
		}
	}
	
	# get permissions from parent directory
	$perms = 0777; 
	if(function_exists('stat') && fvm_function_available('stat')) {
		if ($stat = @stat(dirname($file))) { $perms = $stat['mode'] & 0007777; }
	}
	
	if (file_exists($file)){
		if ($perms != ($perms & ~umask())){
			$folder_parts = explode( DIRECTORY_SEPARATOR, substr( $file, strlen(dirname($file)) + 1 ) );
				for ( $i = 1, $c = count( $folder_parts ); $i <= $c; $i++ ) {
				@chmod(dirname($file) . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, array_slice( $folder_parts, 0, $i ) ), $perms );
			}
		}
		return true;
	}

	return false;
}


# get options into an array
function fvm_get_settings() {

	$fvm_settings = json_decode(get_option('fvm_settings'), true);

	# mandatory default exclusions
	$fvm_settings_default = fvm_get_default_settings($fvm_settings);
	
	# check if there are any pending field update routines
	$fvm_settings_default = fvm_get_updated_field_routines($fvm_settings_default);
	
	# update database if needed
	if($fvm_settings != $fvm_settings_default) {
		update_option('fvm_settings', json_encode($fvm_settings_default), false);
	}
	
	# return
	return $fvm_settings;	
}

# return value from section and key name
function fvm_get_settings_value($fvm_settings, $section, $key) {
	if($fvm_settings != false && is_array($fvm_settings) && count($fvm_settings) > 1) {
		if(isset($fvm_settings[$section][$key])) {
			return $fvm_settings[$section][$key]; 
		}
	}
	return '';
}


# default exclusions by seting name
function fvm_get_default_settings($fvm_settings) {
	if(!is_array($fvm_settings) || empty($fvm_settings)){
		
		# initialize
		$fvm_settings = array();
		
		# global
		$fvm_settings['global']['preserve_settings'] = 1;		
		
		# html
		$fvm_settings['html']['enable'] = 1;
		$fvm_settings['html']['nocomments'] = 1;
		$fvm_settings['html']['cleanup_header'] = 1;
		$fvm_settings['html']['disable_emojis'] = 1;
		
		# css
		$fvm_settings['css']['enable'] = 1;
		$fvm_settings['css']['noprint'] = 1;
		
		# cdn
		$arr = array('img[src*=/wp-content/], img[data-src*=/wp-content/], img[data-srcset*=/wp-content/]', 'picture source[srcset*=/wp-content/]', 'video source[type*=video]', 'image[height]', 'link[rel=icon], link[rel=apple-touch-icon]', 'meta[name=msapplication-TileImage]', 'a[data-interchange*=/wp-content/]', 'rs-slide[data-thumb]', 'form[data-product_variations]', 'div[data-background-image], section[data-background-image]');
		$fvm_settings['cdn']['integration'] = implode(PHP_EOL, fvm_array_order($arr));

	}
	
	# return	
	return $fvm_settings;
}


# save log to database
function fvm_save_log($arr) {
	
	# must have
	if(!is_array($arr) || (is_array($arr) && (count($arr) == 0 || empty($arr)))) { return false; }
	if(!isset($arr['uid']) || !isset($arr['date']) || !isset($arr['type']) || !isset($arr['content']) || !isset($arr['meta'])) { return false; }
	
	# normalize unknown keys
	if(strlen($arr['uid']) != 40) { $arr['uid'] = hash('sha1', $arr['uid']); }
	
	# else insert
	global $wpdb, $fvm_cache_paths;
	
	# initialize arrays (fields, types, values)
	$fld = array();
	$tpe = array();
	$vls = array();
	
	# define possible data types
	$str = array('uid', 'type', 'content', 'meta');
	$int = array('date');
	$all = array_merge($str, $int);
	
	# process only recognized columns
	foreach($arr as $k=>$v) {
		if(in_array($k, $all)) {
			if(in_array($k, $str)) { $tpe[] = '%s'; } else { $tpe[] = '%d'; }
			if($k == 'content') { $v = json_encode($v); }
			if($k == 'meta') { $v = json_encode($v); }
			if($k == 'uid') { $v = hash('sha1', $v); }
			
			# array for prepare
			$fld[] = $k;
			$vls[] = $v;
		}
	}
	
	# prepare and insert to database
	$sql = $wpdb->prepare("INSERT IGNORE INTO ".$wpdb->prefix."fvm_logs (".implode(', ', $fld).") VALUES (".implode(', ', $tpe).")", $vls);
	$result = $wpdb->query($sql);
	
	# check if it already exists
	if($result) {
		return true;
	}
	
	# fallback
	return false;
	
}




# try to open the file from the disk, before downloading
# try to open the file from the disk, before downloading
function fvm_maybe_download($url) {
	
	# must have
	if(is_null($url) || empty($url)) { return false; }
	
	# get domain
	global $fvm_urls;
	
	# check if we can open the file locally first
	if (stripos($url, $fvm_urls['wp_domain']) !== false && isset($_SERVER['DOCUMENT_ROOT'])) {
		
		# file path
		$f = str_replace(rtrim($fvm_urls['wp_home'], '/'), $_SERVER['DOCUMENT_ROOT'], $url);
		$f = str_replace('/', DIRECTORY_SEPARATOR, $f);	# windows compatibility
		
		# did it work?
		if (file_exists($f)) {
			return array('content'=>file_get_contents($f), 'src'=>'Disk');
		}
	}

	# fallback to downloading
	
	# this useragent is needed for google fonts (woff files only + hinted fonts)
	$uagent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586';

	# cache buster
	$query = 'cache='.time();
	$parsedUrl = parse_url($url);
	if ($parsedUrl['path'] === null) { $url .= '/'; }
	if ($parsedUrl['query'] === null) { $separator = '?'; } else { $separator = '&'; }
	$url .= $separator.$query;

	# fetch via wordpress functions
	$response = wp_remote_get($url, array('user-agent'=>$uagent, 'timeout' => 7, 'httpversion' => '1.1', 'sslverify'=>false)); 
	$res_code = wp_remote_retrieve_response_code($response);
	if($res_code == '200') {
		$content = wp_remote_retrieve_body($response);
		if(strlen($content) > 1) {
			return array('content'=>$content, 'src'=>'Web');
		}
	}
	
	# failed
	return array('error'=> __( 'Could not read or fetch from URL', 'fast-velocity-minify' ) . ' ['. $url . ']');
}


# save cache file, if allowed
function fvm_save_file($file, $content) {

	# get directory
	$path = dirname($file);
				
	# must be on the allowed path
	if(empty($path) || !defined('WP_CONTENT_DIR') || stripos($path, '/fvm') === false) {
		return __( 'Requested path is not allowed!', 'fast-velocity-minify' );
	}
											
	# create directory structure
	fvm_create_dir($path);
		
	# save file
	file_put_contents($file, $content);
	fvm_fix_permission_bits($file);
	return true;

}


# get transients
function fvm_get_transient($key, $check=null) {
	
	global $wpdb;
	
	# normalize unknown keys
	if(strlen($key) != 40) { $key = hash('sha1', $key); }
	
	# check or fetch
	if($check) {
		$sql = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}fvm_cache WHERE uid = %s LIMIT 1", $key);
	} else {
		$sql = $wpdb->prepare("SELECT content FROM {$wpdb->prefix}fvm_cache WHERE uid = %s LIMIT 1", $key);
	}

	# get result from database
	$result = $wpdb->get_row($sql);
	
	# return true if just checking if it exists
	if(isset($result->id)) {
		return true;
	}
	
	# return content if found
	if(isset($result->content)) {
		return $result->content;
	}
	
	# fallback
	return false;
}

# set cache
function fvm_set_transient($arr) {
	
	# must have
	if(!is_array($arr) || (is_array($arr) && (count($arr) == 0 || empty($arr)))) { return false; }
	if(!isset($arr['uid']) || !isset($arr['date']) || !isset($arr['type']) || !isset($arr['content']) || !isset($arr['meta'])) { return false; }
	
	# normalize unknown keys
	if(strlen($arr['uid']) != 40) { $arr['uid'] = hash('sha1', $arr['uid']); }
	
	# check if it already exists and return early if it does
	$status = fvm_get_transient($arr['uid'], true);
	if($status) { return true; }
	
	# else insert
	global $wpdb;
	
	# initialize arrays (fields, types, values)
	$fld = array();
	$tpe = array();
	$vls = array();
	
	# define possible data types
	$str = array('uid', 'type', 'content', 'meta');
	$int = array('date');
	$all = array_merge($str, $int);
	
	# process only recognized columns
	foreach($arr as $k=>$v) {
		if(in_array($k, $all)) {
			if(in_array($k, $str)) { $tpe[] = '%s'; } else { $tpe[] = '%d'; }
			if($k == 'meta') { $v = json_encode($v); }
			$fld[] = $k;
			$vls[] = $v;
		}
	}
		
	# prepare and insert to database
	$sql = $wpdb->prepare("INSERT IGNORE INTO ".$wpdb->prefix."fvm_cache (".implode(', ', $fld).") VALUES (".implode(', ', $tpe).")", $vls);
	$result = $wpdb->query($sql);
	
	# check if it already exists
	if($result) {
		return true;
	}
	
	# fallback
	return false;
	
}

# delete transient
function fvm_del_transient($key) {
	
	global $wpdb;
	
	# normalize unknown keys
	if(strlen($key) != 40) { $key = hash('sha1', $key); }
	
	# delete
	$sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}fvm_cache WHERE uid = %s", $key);
	$result = $wpdb->get_row($sql);
	return true;
}


# functions, get full url
function fvm_normalize_url($src, $wp_domain, $wp_home) {
	
	# preserve empty source handles
	$hurl = trim($src); if(empty($hurl)) { return $hurl; }      

	# some fixes
	$hurl = str_replace(array('&#038;', '&amp;'), '&', $hurl);

	#make sure wp_home doesn't have a forward slash
	$wp_home = rtrim($wp_home, '/');
	
	# protocol scheme
	$scheme = parse_url($wp_home)['scheme'].'://';

	# apply some filters
	if (substr($hurl, 0, 2) === "//") { $hurl = $scheme.ltrim($hurl, "/"); }  # protocol only
	if (substr($hurl, 0, 4) === "http" && stripos($hurl, $wp_domain) === false) { return $hurl; } # return if external domain
	if (substr($hurl, 0, 4) !== "http" && stripos($hurl, $wp_domain) !== false) { $hurl = $wp_home.'/'.ltrim($hurl, "/"); } # protocol + home

	# prevent double forward slashes in the middle
	$hurl = str_replace('###', '://', str_replace('//', '/', str_replace('://', '###', $hurl)));

	# consider different wp-content directory for relative paths
	$proceed = 0; 
	if(!empty($wp_home)) { 
		$alt_wp_content = basename($wp_home); 
		if(substr($hurl, 0, strlen($alt_wp_content)) === $alt_wp_content) { $proceed = 1; } 
	}

	# protocol + home for relative paths
	if (substr($hurl, 0, 12) === "/wp-includes" || substr($hurl, 0, 9) === "/wp-admin" || substr($hurl, 0, 11) === "/wp-content" || $proceed == 1) { 
		$hurl = $wp_home.'/'.ltrim($hurl, "/"); 
	}

	# make sure there is a protocol prefix as required
	$hurl = $scheme.str_replace(array('http://', 'https://'), '', $hurl); # enforce protocol

	# no query strings on css and js files
	if (stripos($hurl, '.js?') !== false) { $hurl = stristr($hurl, '.js?', true).'.js'; } # no query strings
	if (stripos($hurl, '.css?') !== false) { $hurl = stristr($hurl, '.css?', true).'.css'; } # no query strings

	# add filter for developers
	$hurl = apply_filters('fvm_get_url', $hurl);

	return $hurl;	
}


# minify ld+json scripts
function fvm_minify_microdata($data) {
	# remove // comments
	$data = trim(preg_replace('/(\v)+(\h)+[\/]{2}(.*)+(\v)+/u', '', $data));
	
	# minify
	$data = trim(preg_replace('/\s+/u', ' ', $data));
	$data = str_replace(array('" ', ' "'), '"', $data);
	$data = str_replace(array('[ ', ' ['), '[', $data);
	$data = str_replace(array('] ', ' ]'), ']', $data);
	$data = str_replace(array('} ', ' }'), '}', $data);
	$data = str_replace(array('{ ', ' {'), '{', $data);
	return $data;
}


# check for php or html, skip if found
function fvm_not_php_html($code) {
	if((strtolower(substr($code, 0, 2)) != "<?" && stripos($code, "<?php") === false) || strtolower(substr($code, 0, 9)) != "<!doctype") {
		return true;
	}
	return false;
}


# remove UTF8 BOM
function fvm_remove_utf8_bom($text) {
    $bom = pack('H*','EFBBBF');
	while (preg_match("/^$bom/", $text)) {
		$text = preg_replace("/^$bom/ui", '', $text);
	}
    return $text;
}


# validate and minify css
function fvm_maybe_minify_css_file($css, $url, $min) {
	
	# return early if empty
	if(empty($css) || $css == false) { return $css; }
		
	# process css only if it's not php or html
	if(fvm_not_php_html($css)) {
	
		# filtering
		$css = fvm_remove_utf8_bom($css); 
		$css = str_ireplace('@charset "UTF-8";', '', $css);
		
		# remove query strings from fonts
		$css = preg_replace('/(.eot|.woff2|.woff|.ttf)+[?+](.+?)(\)|\'|\")/ui', "$1"."$3", $css);

		# remove sourceMappingURL
		$css = preg_replace('/(\/\/\s*[#]\s*sourceMappingURL\s*[=]\s*)([a-zA-Z0-9-_\.\/]+)(\.map)/ui', '', $css);
		
		# fix url paths
		if(!empty($url)) {
			$matches = array(); preg_match_all("/url\(\s*['\"]?(?!data:)(?!http)(?![\/'\"])(.+?)['\"]?\s*\)/ui", $css, $matches);
			foreach($matches[1] as $a) { $b = trim($a); if($b != $a) { $css = str_replace($a, $b, $css); } }
			$css = preg_replace("/url\(\s*['\"]?(?!data:)(?!http)(?![\/'\"#])(.+?)['\"]?\s*\)/ui", "url(".dirname($url)."/$1)", $css);	
		}
	
		# minify string with relative urls
		if($min) {
			$css = fvm_minify_css_string($css);
		}
		
		# add font-display for google fonts and fontawesome
		# https://developers.google.com/web/updates/2016/02/font-display
		$css = str_ireplace('font-family:', 'font-display:block;font-family:', $css);
		
		# return css
		return trim($css);
	
	}

	return false;	
}


# validate and minify js
function fvm_maybe_minify_js($js, $url, $enable_js_minification) {

	# return early if empty
	if(empty($js) || $js == false) { return $js; }
		
	# process js only if it's not php or html
	if(fvm_not_php_html($js)) {
		
		# globals
		global $fvm_settings;
	
		# filtering
		$js = fvm_remove_utf8_bom($js); 
		
		# remove sourceMappingURL
		$js = preg_replace('/(\/\/\s*[#]\s*sourceMappingURL\s*[=]\s*)([a-zA-Z0-9-_\.\/]+)(\.map)/ui', '', $js);
	
		# minify?
		if($enable_js_minification == true) {

			# PHP Minify from https://github.com/matthiasmullie/minify
			$minifier = new FVM\MatthiasMullie\Minify\JS($js);
			$min = $minifier->minify();
			
			# return if not empty
			if($min !== false && strlen(trim($min)) > 0) { 
				return $min;
			}
		}
	
		# return js
		return trim($js);
	
	}

	return false;	
}


# minify css string with PHP Minify
function fvm_minify_css_string($css) {
	
	# return early if empty
	if(empty($css) || $css == false) { return $css; }
	
	# get domain
	global $fvm_urls;
	
	# minify	
	$minifier = new FVM\MatthiasMullie\Minify\CSS($css);
	$minifier->setMaxImportSize(10); # embed assets up to 10 Kb (default 5Kb) - processes gif, png, jpg, jpeg, svg & woff
	$min = $minifier->minify();

	# make relative urls
	$min = str_replace('http://'.$fvm_urls['wp_domain'], '', $min);
	$min = str_replace('https://'.$fvm_urls['wp_domain'], '', $min);
	$min = str_replace('//'.$fvm_urls['wp_domain'], '', $min);	
	$min = str_replace('http://'.str_ireplace('www.', '', $fvm_urls['wp_domain']), '', $min);
	$min = str_replace('https://'.str_ireplace('www.', '', $fvm_urls['wp_domain']), '', $min);
	$min = str_replace('//'.str_ireplace('www.', '', $fvm_urls['wp_domain']), '', $min);	
		
	# return
	if($min != false) { 
		return $min; 
	}
	
	# fallback
	return $css;
}


# escape html tags for document.write
function fvm_escape_url_js($str) {
	$str = trim(preg_replace('/[\t\n\r\s]+/iu', ' ', $str));
	return str_replace(array('\\\\\"', '\\\\"', '\\\"', '\\"'), '\"', json_encode($str));
}


# try catch wrapper for merged javascript
function fvm_try_catch_wrap($js, $href=null) {
	$loc = ''; if(isset($href)) { $loc = '[ Merged: '. $href . ' ] '; }
	return 'try{'. PHP_EOL . $js . PHP_EOL . '}catch(e){console.error("An error has occurred. '.$loc.'[ "+e.stack+" ]");}';
}


# Disable the emoji's on the frontend
function fvm_disable_emojis() {
	global $fvm_settings;
		if(isset($fvm_settings['html']['disable_emojis']) && $fvm_settings['html']['disable_emojis'] == true) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );	
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );	
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		}
}


# stop slow ajax requests for bots
function fvm_ajax_optimizer() {
	if(isset($_SERVER['HTTP_USER_AGENT']) && (defined('DOING_AJAX') && DOING_AJAX) || (function_exists('is_ajax') && is_ajax()) || (function_exists('wp_doing_ajax') && wp_doing_ajax())){
		if (preg_match('/'.implode('|', array('x11.*ox\/54', 'id\s4.*us.*ome\/62', 'oobo', 'ight', 'tmet', 'eadl', 'ngdo', 'PTST')).'/i', $_SERVER['HTTP_USER_AGENT'])){ echo '0'; exit(); }
	}
}

# rewrite assets to cdn
function fvm_rewrite_assets_cdn($html) {
	
	# settings
	global $fvm_settings, $fvm_urls;
	
	if(isset($fvm_urls['wp_domain']) && !empty($fvm_urls['wp_domain']) && 
	isset($fvm_settings['cdn']['enable']) && $fvm_settings['cdn']['enable'] == true &&  
	isset($fvm_settings['cdn']['domain']) && !empty($fvm_settings['cdn']['domain']) &&
	isset($fvm_settings['cdn']['integration']) && !empty($fvm_settings['cdn']['integration'])) {
		$arr = fvm_string_toarray($fvm_settings['cdn']['integration']);
		if(is_array($arr) && count($arr) > 0) {
			foreach($html->find(implode(', ', $arr) ) as $elem) {
				
				# preserve some attributes but replace others
				if (is_object($elem) && isset($elem->attr)) {

					# get all attributes
					foreach ($elem->attr as $key=>$val) {
						
						# skip href attribute for links
						if($key == 'href' && stripos($elem->outertext, '<a ') !== false) { continue; }
							
						# skip certain attributes							
						if(in_array($key, array('id', 'class', 'action'))) { continue; }

						# replace other attributes
						$elem->{$key} = str_replace('//'.$fvm_urls['wp_domain'], '//'.$fvm_settings['cdn']['domain'], $elem->{$key});
						$elem->{$key} = str_replace('\/\/'.$fvm_urls['wp_domain'], '\/\/'.$fvm_settings['cdn']['domain'], $elem->{$key});

					}
						
				}

			}
		}
	}
	
	return $html;
}



# replace css imports with origin css code
function fvm_replace_css_imports($css, $rq=null) {
	
	# globals
	global $fvm_urls, $fvm_settings;

	# handle import url rules
	$cssimports = array();
	preg_match_all ("/@import[ ]*['\"]{0,}(url\()*['\"]*([^;'\"\)]*)['\"\)]*[;]{0,}/ui", $css, $cssimports);
	if(isset($cssimports[0]) && isset($cssimports[2])) {
		foreach($cssimports[0] as $k=>$cssimport) {
				
			# if @import url rule, or guess full url
			if(stripos($cssimport, 'import url') !== false && isset($cssimports[2][$k])) {
				$url = trim($cssimports[2][$k]);
			} else {
				if(!is_null($rq) && !empty($rq)) {
					$url = dirname($rq) . '/' . trim($cssimports[2][$k]);	
				}
			}
			
			# must have
			if(!empty($url)) {
				
				# make sure we have a complete url
				$href = fvm_normalize_url($url, $fvm_urls['wp_domain'], $fvm_urls['wp_home']);

				# download, minify, cache (no ver query string)
				$tkey = hash('sha1', $href);
				$subcss = fvm_get_transient($tkey);
				if ($subcss === false) {
				
					# get minification settings for files
					if(isset($fvm_settings['css']['min_files'])) {
						$enable_css_minification = $fvm_settings['css']['min_files'];
					}					
					
					# force minification on google fonts
					if(stripos($href, 'fonts.googleapis.com') !== false) {
						$enable_css_minification = true;
					}
					
					# download file, get contents, merge
					$ddl = fvm_maybe_download($href);

					# if success
					if(isset($ddl['content'])) {
							
						# contents
						$subcss = $ddl['content'];
						
						# minify
						$subcss = fvm_maybe_minify_css_file($subcss, $href, $enable_css_minification);
						
						# developers filter
						$subcss = apply_filters( 'fvm_after_download_and_minify_code', $subcss, 'css');

						# remove specific, minified CSS code
						if(isset($fvm_settings['css']['remove_code']) && !empty($fvm_settings['css']['remove_code'])) {
							$arr = fvm_string_toarray($fvm_settings['css']['remove_code']);
							if(is_array($arr) && count($arr) > 0) {
								foreach($arr as $str) {
									$subcss = str_replace($str, '', $subcss);
								}
							}
						}
							
						# trim code
						$subcss = trim($subcss);
							
						# size in bytes
						$fs = strlen($subcss);
						$ur = str_replace($fvm_urls['wp_home'], '', $href);
						$tkey_meta = array('fs'=>$fs, 'url'=>str_replace($fvm_cache_paths['cache_url_min'].'/', '', $ur), 'mt'=>$media);
								
						# save
						fvm_set_transient(array('uid'=>$tkey, 'date'=>$tvers, 'type'=>'css', 'content'=>$subcss, 'meta'=>$tkey_meta));
					}
				}

				# replace import rule with inline code
				if ($subcss !== false && !empty($subcss)) {
					$css = str_replace($cssimport, $subcss, $css);
				}
				
			}
		}
	}
	
	# return
	return $css;
	
}


# add our function in the header
function fvm_add_header_function($html) {
	
	# create function
	$lst = array('x11.*ox\/54', 'id\s4.*us.*ome\/62', 'oobo', 'ight', 'tmet', 'eadl', 'ngdo', 'PTST');
	$fvmf = '<script data-cfasync="false">function fvmuag(){var e=navigator.userAgent;if(e.match(/'.implode('|', $lst).'/i))return!1;if(e.match(/x11.*me\/86\.0/i)){var r=screen.width;if("number"==typeof r&&1367==r)return!1}return!0}</script>';
	
	# remove duplicates
	if(stripos($html, $fvmf) !== false) { 
		$html = str_ireplace($fvmf, '', $html); 
	}
	
	# add function 
	$html = str_replace('<!-- h_header_function -->', $fvmf, $html);
	return $html;
}


# get the domain name
function fvm_get_domain() {
	if(isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])) {
		return $_SERVER['SERVER_NAME'];
	} elseif (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
		return $_SERVER['HTTP_HOST'];
	} elseif (function_exists('site_url')) {
		return parse_url(site_url())['host'];
	} else {
		return false;
	}
}

# get the settings file path, current domain name, and uri path without query strings
function fvm_get_uripath() {
	if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) { 
		$current_uri = strtok($_SERVER['REQUEST_URI'], '?');
		$current_uri = str_replace('//', '/', str_replace('..', '', preg_replace( '/[ <>\'\"\r\n\t\(\)]/', '', $current_uri)));
		return $current_uri;
	} else {
		return false; 
	}
}