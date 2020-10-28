<?php

# Exit if accessed directly				
if (!defined('ABSPATH')){ exit(); }	

# functions needed only for frontend ###########

# must have for large strings processing during minification
@ini_set('pcre.backtrack_limit', 5000000); 
@ini_set('pcre.recursion_limit', 5000000); 

# our own minification libraries
include_once($fvm_var_inc_lib . DIRECTORY_SEPARATOR . 'raisermin' . DIRECTORY_SEPARATOR . 'minify.php');

# php simple html
# https://sourceforge.net/projects/simplehtmldom/
define('MAX_FILE_SIZE', 2000000); # Process HTML up to 2 Mb
include_once($fvm_var_inc_lib . DIRECTORY_SEPARATOR . 'simplehtmldom' . DIRECTORY_SEPARATOR . 'simple_html_dom.php');

# PHP Minify [1.3.60] for CSS minification only
# https://github.com/matthiasmullie/minify
$fvm_var_inc_lib_mm = $fvm_var_inc_lib . DIRECTORY_SEPARATOR . 'matthiasmullie' . DIRECTORY_SEPARATOR;
include_once($fvm_var_inc_lib_mm . 'minify' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Minify.php');
include_once($fvm_var_inc_lib_mm . 'minify' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'CSS.php');
include_once $fvm_var_inc_lib_mm . 'minify'. DIRECTORY_SEPARATOR .'src'. DIRECTORY_SEPARATOR .'JS.php';
include_once $fvm_var_inc_lib_mm . 'minify'. DIRECTORY_SEPARATOR .'src'. DIRECTORY_SEPARATOR .'Exception.php';
include_once $fvm_var_inc_lib_mm . 'minify'. DIRECTORY_SEPARATOR .'src'. DIRECTORY_SEPARATOR .'Exceptions'. DIRECTORY_SEPARATOR .'BasicException.php';
include_once $fvm_var_inc_lib_mm . 'minify'. DIRECTORY_SEPARATOR .'src'. DIRECTORY_SEPARATOR .'Exceptions'. DIRECTORY_SEPARATOR .'FileImportException.php';
include_once $fvm_var_inc_lib_mm . 'minify'. DIRECTORY_SEPARATOR .'src'. DIRECTORY_SEPARATOR .'Exceptions'. DIRECTORY_SEPARATOR .'IOException.php';
include_once($fvm_var_inc_lib_mm . 'path-converter' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ConverterInterface.php');
include_once($fvm_var_inc_lib_mm . 'path-converter' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Converter.php');

################################################


# start buffering before template
function fvm_start_buffer() {
	if(fvm_can_minify()) {
		ob_start('fvm_process_page', 0, PHP_OUTPUT_HANDLER_REMOVABLE);
	}
}

# process html from fvm_end_buffer
function fvm_process_page($html) {
	
	# get globals
	global $fvm_settings, $fvm_cache_paths, $fvm_urls;
	
	# can process minification?
	if(fvm_can_minify()) {
				
		# defaults
		$tvers = get_option('fvm_last_cache_update', '0');
		$now = time();
		$htmlpreloader = array();
		$htmlcssheader = array();
			
		# get html into an object
		# https://simplehtmldom.sourceforge.io/manual.htm
		$html_object = str_get_html($html, true, true, 'UTF-8', false, PHP_EOL, ' ');

		# return early if html is not an object, or overwrite html into an object for processing
		if (!is_object($html_object)) {
			return $html . '<!-- simplehtmldom failed to process the html -->';
		} else {
			$html = $html_object;
		}
		
		
		# process css, if not disabled
		if(isset($fvm_settings['css']['enable']) && $fvm_settings['css']['enable'] == true) {
						
			# defaults
			$fvm_styles = array();
			$fvm_styles_log = array();
			$enable_css_minification = true;
			
			# exclude styles and link tags inside scripts, no scripts or html comments
			$excl = array();
			foreach($html->find('script link[rel=stylesheet], script style, noscript style, noscript link[rel=stylesheet], comment') as $element) {
				$excl[] = $element->outertext;
			}

			# collect all styles, but filter out if excluded
			$allcss = array();
			foreach($html->find('link[rel=stylesheet], style') as $element) {
				if(!in_array($element->outertext, $excl)) {
					$allcss[] = $element;
				}
			}
						
			# merge and process
			foreach($allcss as $k=>$tag) {
				
				# ignore list, leave these alone
				if(isset($tag->href) && isset($fvm_settings['css']['ignore']) && !empty($fvm_settings['css']['ignore'])) {
					$arr = fvm_string_toarray($fvm_settings['css']['ignore']);
					if(is_array($arr) && count($arr) > 0) {
						foreach ($arr as $e) { 
							if(stripos($tag->href, $e) !== false) {
								continue 2;
							} 
						}
					}
				}
				
				# remove css files
				if(isset($tag->href) && isset($fvm_settings['css']['remove']) && !empty($fvm_settings['css']['remove'])) {
					$arr = fvm_string_toarray($fvm_settings['css']['remove']);
					if(is_array($arr) && count($arr) > 0) {
						foreach ($arr as $e) { 
							if(stripos($tag->href, $e) !== false) {
								$tag->outertext = '';
								unset($allcss[$k]);
								continue 2;
							} 
						}
					}
				}
				
				# change the mediatype for files that are to be merged into the fonts css 
				if(isset($tag->href) && isset($fvm_settings['css']['fonts']) && $fvm_settings['css']['fonts'] == true) {
					$arr = array('/fonts.googleapis.com', '/animate.css', '/animate.min.css', '/icomoon.css', '/animations/', '/eicons/css/', 'font-awesome', 'fontawesome', '/flag-icon.min.css', '/fonts.css', '/pe-icon-7-stroke.css', '/fontello.css', '/dashicons.min.css');
					if(is_array($arr) && count($arr) > 0) {
						foreach ($arr as $e) { 
							if(stripos($tag->href, $e) !== false) {
								$tag->media = 'fonts';
								break;
							}
						} 
					}
				}
				
					
				# normalize mediatypes
				$media = 'all';
				if(isset($tag->media)) {
					$media = $tag->media;
					if ($media == 'screen' || $media == 'screen, print' || empty($media) || is_null($media) || $media == false) { 
						$media = 'all'; 
					}
				}
							
				# remove print mediatypes
				if(isset($fvm_settings['css']['noprint']) && $fvm_settings['css']['noprint'] == true && $media == 'print') {
					$tag->outertext = '';
					unset($allcss[$k]);
					continue;
				}	

				# process css files
				if($tag->tag == 'link' && isset($tag->href)) {
					
					# default
					$css = '';
					
					# make sure we have a complete url
					$href = fvm_normalize_url($tag->href, $fvm_urls['wp_domain'], $fvm_urls['wp_home']);
					
					# get minification settings for files
					if(isset($fvm_settings['css']['min_disable']) && $fvm_settings['css']['min_disable'] == '1') {
						$enable_css_minification = true;
					}					
					
					# force minification on google fonts
					if(stripos($href, 'fonts.googleapis.com') !== false) {
						$enable_css_minification = true;
					}
					
					# download, minify, cache (no ver query string)
					$tkey = hash('sha1', $href);
					$css = fvm_get_transient($tkey);
					if ($css === false) {
						
						# open or download file, get contents
						$css = fvm_maybe_download($href);
						$css = fvm_maybe_minify_css_file($css, $href, $enable_css_minification);
										
						# quick integrity check
						if(!empty($css) && $css != false) {

							# trim code
							$css = trim($css);
							
							# execution time in ms, size in bytes
							$fs = strlen($css);
							$ur = str_replace($fvm_urls['wp_home'], '', $href);
							$tkey_meta = array('fs'=>$fs, 'url'=>str_replace($fvm_cache_paths['cache_url_min'].'/', '', $ur), 'mt'=>$media);
												
							# save
							fvm_set_transient(array('uid'=>$tkey, 'date'=>$tvers, 'type'=>'css', 'content'=>$css, 'meta'=>$tkey_meta));

						}
					}
					
					# success, get final contents to array
					if($css !== false) {
						$fvm_styles[$media][] = $css;
						$fvm_styles_log[$media][] = $tkey;
						$tag->outertext = '';
						unset($allcss[$k]);
						continue;
					}
				
				}
		
		
				# process styles
				if($tag->tag == 'style' && !isset($tag->href)) {
				
					# default
					$css = '';
					
					# get minification settings for files
					if(isset($fvm_settings['css']['min_disable']) && $fvm_settings['css']['min_disable'] == '1') {
						$enable_css_minification = true;
					}
					
					# minify inline CSS
					$css = $tag->innertext;
					if($enable_css_minification) {
						$css = fvm_minify_css_string($css); 
					}	
					
					# trim code
					$css = trim($css);
					
					# decide what to do with the inlined css
					if(empty($css)) {
						# delete empty style tags
						$tag->outertext = '';
						unset($allcss[$k]);
						continue;
					} else {
						# process inlined styles
						$tag->innertext = $css;
						unset($allcss[$k]);
						continue;
					}

				}
				
			}
			
			# generate merged css files, foreach mediatype
			if(is_array($fvm_styles) && count($fvm_styles) > 0) {
				
				# collect fonts for last
				$lp_css_last = '';
				$lp_css_last_ff = '';
				
				# merge files
				foreach ($fvm_styles as $mediatype=>$css_process) {
					
					# skip fonts file
					if($mediatype == 'fonts') {
						$lp_css_last = $fvm_styles['fonts'];
						continue;
					}		
				
					# merge code, generate cache file paths and urls
					$file_css_code = implode('', $css_process);
					$css_uid = $tvers.'-'.hash('sha1', $file_css_code);
					$file_css = $fvm_cache_paths['cache_dir_min'] . DIRECTORY_SEPARATOR .  $css_uid.'.min.css';
					$file_css_url = $fvm_cache_paths['cache_url_min'].'/'.$css_uid.'.min.css';
					
					# remove fonts and icons from final css
					$mff = array();
					preg_match_all('/(\@font-face)([^}]+)(\})/', $file_css_code, $mff);
					if(isset($mff[0]) && is_array($mff[0])) {
						foreach($mff[0] as $ff) {
							$file_css_code = str_replace($ff, '', $file_css_code);
							$lp_css_last_ff.= $ff . PHP_EOL;
						}
					}
					
					# add cdn support
					if(isset($fvm_settings['cdn']['enable']) && $fvm_settings['cdn']['enable'] == true && 
					isset($fvm_settings['cdn']['domain']) && !empty($fvm_settings['cdn']['domain'])) {
						if(isset($fvm_settings['cdn']['cssok']) && $fvm_settings['cdn']['cssok'] == true) {
							$pos = strpos($file_css_url, $fvm_urls['wp_domain']);
							if ($pos !== false) {
								$file_css_url = substr_replace($file_css_url, $fvm_settings['cdn']['domain'], $pos, strlen($fvm_urls['wp_domain']));
							}
						}
					}
					
					# generate cache file
					clearstatcache();
					if (!file_exists($file_css)) {
						
						# prepare log
						$log = (array) array_values($fvm_styles_log[$mediatype]);
						$log_meta = array('loc'=>home_url(add_query_arg(NULL, NULL)), 'fl'=>$file_css_url, 'mt'=>$mediatype);
						
						# generate cache, write log
						if(!empty($file_css_code)) {
							fvm_save_log(array('uid'=>$file_css_url, 'date'=>$now, 'type'=>'css', 'meta'=>$log_meta, 'content'=>$log));
							fvm_save_file($file_css, $file_css_code);
						}

					}
					
					# if file exists
					clearstatcache();
					if (file_exists($file_css)) {
						
						# preload and save for html implementation (with priority order prefix)
						$htmlpreloader['b_'.$css_uid] = '<link rel="preload" href="'.$file_css_url.'" as="style" media="'.$mediatype.'" />';
								
						# async or render block css
						if(isset($fvm_settings['css']['async']) && $fvm_settings['css']['async'] == true) {
							$htmlcssheader['b_'.$css_uid] = '<link rel="stylesheet" href="'.$file_css_url.'" media="print" onload="this.media=\''.$mediatype.'\'">';
						} else {
							$htmlcssheader['b_'.$css_uid] = '<link rel="stylesheet" href="'.$file_css_url.'" media="'.$mediatype.'" />';
						}
					}

				}
				
				
				# generate merged css files, foreach mediatype
				if(!empty($lp_css_last) || !empty($lp_css_last_ff)) {
					
					# merge code, generate cache file paths and urls
					$file_css_code = implode('', $lp_css_last).$lp_css_last_ff;
					$css_uid = $tvers.'-'.hash('sha1', $file_css_code);
					$file_css = $fvm_cache_paths['cache_dir_min'] . DIRECTORY_SEPARATOR .  $css_uid.'.min.css';
					$file_css_url = $fvm_cache_paths['cache_url_min'].'/'.$css_uid.'.min.css';
					
					# add cdn support
					if(isset($fvm_settings['cdn']['enable']) && $fvm_settings['cdn']['enable'] == true && 
					isset($fvm_settings['cdn']['domain']) && !empty($fvm_settings['cdn']['domain'])) {
						if(isset($fvm_settings['cdn']['cssok']) && $fvm_settings['cdn']['cssok'] == true) {
							$pos = strpos($file_css_url, $fvm_urls['wp_domain']);
							if ($pos !== false) {
								$file_css_url = substr_replace($file_css_url, $fvm_settings['cdn']['domain'], $pos, strlen($fvm_urls['wp_domain']));
							}
						}
					}
						
					# generate cache file
					clearstatcache();
					if (!file_exists($file_css)) {
						
						# prepare log
						$log = (array) array_values($fvm_styles_log[$mediatype]);
						$log_meta = array('loc'=>home_url(add_query_arg(NULL, NULL)), 'fl'=>$file_css_url, 'mt'=>$mediatype);
						
						# generate cache, write log
						if(!empty($file_css_code)) {
							fvm_save_log(array('uid'=>$file_css_url, 'date'=>$now, 'type'=>'css', 'meta'=>$log_meta, 'content'=>$log));
							fvm_save_file($file_css, $file_css_code);
						}				
					}
					
					# if file exists
					clearstatcache();
					if (file_exists($file_css)) {
						# preload and save for html implementation (with priority order prefix)
						$htmlpreloader['b_'.$css_uid] = '<link rel="preload" href="'.$file_css_url.'" as="style" media="'.$mediatype.'" />';
								
						# Load CSS Asynchronously with javascript
						# https://www.filamentgroup.com/lab/load-css-simpler/
						$htmlcssheader['a_'.$css_uid] = '<link rel="stylesheet" href="'.$file_css_url.'" media="print" onload="this.media=\'all\'">';
					}
						
				}	
				
			}
		}
	
		
		
		
		# always disable js minification in certain areas
		$nojsmin = false;
		if(function_exists('is_cart') && is_cart()){ $nojsmin = true; } # cart
		
		# process js, if not disabled
		if(isset($fvm_settings['js']['enable']) && $fvm_settings['js']['enable'] == true && $nojsmin === false) {
			
			# defaults
			$scripts_duplicate_check = array();
			$enable_js_minification = true;
			$htmljscodeheader = array();
			$htmljscodedefer = array();
			$scripts_header = array();
			$scripts_footer = array();
				
			# get all scripts
			$allscripts = array();
			foreach($html->find('script') as $element) {
				$allscripts[] = $element;
			}
			
			# process all scripts
			if (is_array($allscripts) && count($allscripts) > 0) {
				foreach($allscripts as $k=>$tag) {
											
					# handle application/ld+json or application/json before anything else
					if(isset($tag->type) && ($tag->type == 'application/ld+json' || $tag->type == 'application/json')) {
						$tag->innertext = fvm_minify_microdata($tag->innertext);
						unset($allscripts[$k]);
						continue;
					}
					
					# remove js code
					if(isset($tag->outertext) && isset($fvm_settings['js']['remove']) && !empty($fvm_settings['js']['remove'])) {
						$arr = fvm_string_toarray($fvm_settings['js']['remove']);
						if(is_array($arr) && count($arr) > 0) {
							foreach ($arr as $e) { 
								if(stripos($tag->outertext, $e) !== false) {
									$tag->outertext = '';
									unset($allscripts[$k]);
									continue 2;
								} 
							}
						}
					}

			
					# process inline scripts
					if(!isset($tag->src)) {
						
						# default
						$js = '';
						
						# get minification settings for files
						if(isset($fvm_settings['js']['min_disable']) && $fvm_settings['js']['min_disable'] == true) {
							$enable_js_minification = false;
						}	
						
						# minify inline scripts
						$js = $tag->innertext;
						$js = fvm_maybe_minify_js($js, null, $enable_js_minification);

						# Delay third party scripts and tracking codes (uses PHP stripos against the script innerHTML or the src attribute)
						if(isset($fvm_settings['js']['thirdparty']) && !empty($fvm_settings['js']['thirdparty'])) {
							if(isset($fvm_settings['js']['thirdparty']) && !empty($fvm_settings['js']['thirdparty'])) {
								$arr = fvm_string_toarray($fvm_settings['js']['thirdparty']);
								if(is_array($arr) && count($arr) > 0) {
									foreach ($arr as $b) {
										if(stripos($js, $b) !== false) {
											$js = 'window.addEventListener("load",function(){var c=setTimeout(b,5E3),d=["mouseover","keydown","touchmove","touchstart"];d.forEach(function(a){window.addEventListener(a,e,{passive:!0})});function e(){b();clearTimeout(c);d.forEach(function(a){window.removeEventListener(a,e,{passive:!0})})}function b(){console.log("FVM: Loading Third Party Script!");'.$js.'};});';
											break;
										}
									}
								}
							}
						}
						
						# delay inline scripts until after the 'window.load' event
						if(isset($fvm_settings['js']['defer_dependencies']) && !empty($fvm_settings['js']['defer_dependencies'])) {
							$arr = fvm_string_toarray($fvm_settings['js']['defer_dependencies']);
							if(is_array($arr) && count($arr) > 0) {
								foreach ($arr as $e) { 
									if(stripos($js, $e) !== false && stripos($js, 'FVM:') === false) {
										$js = 'window.addEventListener("load",function(){console.log("FVM: Loading Inline Dependency!");'.$js.'});';
									} 
								}
							}
						}
						
								
						# replace tag on the html
						$tag->innertext = $js;
							
						# mark as processed, unset and break inner loop
						unset($allscripts[$k]);
						continue;

					}
					
					
					# process js files
					if(isset($tag->src)) {
						
						# make sure we have a complete url
						$href = fvm_normalize_url($tag->src, $fvm_urls['wp_domain'], $fvm_urls['wp_home']);

						# upgrade jQuery library and jQuery migrate to version 3
						if(isset($fvm_settings['js']['jqupgrade']) && $fvm_settings['js']['jqupgrade'] == true) {
							# jquery 3
							if(stripos($tag->src, '/jquery.js') !== false || stripos($tag->src, '/jquery.min.js') !== false) {
								$href = 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js';
							}
							# jquery migrate 3
							if(stripos($tag->src, '/jquery-migrate.') !== false || stripos($tag->src, '/jquery-migrate-') !== false) { $href = 'https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.3.1/jquery-migrate.min.js'; }
						}			
						
						
						# get minification settings for files
						if(isset($fvm_settings['js']['min_disable']) && $fvm_settings['js']['min_disable'] == true) {
							$enable_js_minification = false;
						}
						
						
						# render blocking scripts in the header
						if(isset($fvm_settings['js']['merge_header']) && !empty($fvm_settings['js']['merge_header'])) {
							$arr = fvm_string_toarray($fvm_settings['js']['merge_header']);
							if(is_array($arr) && count($arr) > 0) {
								foreach ($arr as $e) { 
									if(stripos($href, $e) !== false) {
										
										# download, minify, cache
										$tkey = hash('sha1', $href);
										$js = fvm_get_transient($tkey);
										if ($js === false) {

											# open or download file, get contents
											$js = fvm_maybe_download($href);
															
											# minify, save and wrap
											$js = fvm_maybe_minify_js($js, $href, $enable_js_minification);
														
											# try catch
											$js = fvm_try_catch_wrap($js);
														
											# quick integrity check
											if(!empty($js) && $js != false) {
															
												# execution time in ms, size in bytes
												$fs = strlen($js);
												$ur = str_replace($fvm_urls['wp_home'], '', $href);
												$tkey_meta = array('fs'=>$fs, 'url'=>str_replace($fvm_cache_paths['cache_url_min'].'/', '', $ur));
															
												# save
												fvm_set_transient(array('uid'=>$tkey, 'date'=>$tvers, 'type'=>'js', 'content'=>$js, 'meta'=>$tkey_meta));	
															
											}
										}
													
										# collect and mark as done for html removal
										$scripts_header[$tkey] = $js;
										$scripts_header_log[$tkey] = $tkey;
										
										# mark as processed, unset and break inner loop
										$tag->outertext = '';
										unset($allscripts[$k]);
										continue 2;
										
									} 
								}
							}
						}
					
							
						# merge and defer scripts
						if(isset($fvm_settings['js']['merge_defer']) && !empty($fvm_settings['js']['merge_defer'])) {
							$arr = fvm_string_toarray($fvm_settings['js']['merge_defer']);
							if(is_array($arr) && count($arr) > 0) {
								foreach ($arr as $e) { 
									if(stripos($href, $e) !== false) {
										
										# download, minify, cache
										$tkey = hash('sha1', $href);
										$js = fvm_get_transient($tkey);
										if ($js === false) {

											# open or download file, get contents
											$js = fvm_maybe_download($href);
															
											# minify, save and wrap
											$js = fvm_maybe_minify_js($js, $href, $enable_js_minification);
														
											# try catch
											$js = fvm_try_catch_wrap($js);
														
											# quick integrity check
											if(!empty($js) && $js != false) {
															
												# execution time in ms, size in bytes
												$fs = strlen($js);
												$ur = str_replace($fvm_urls['wp_home'], '', $href);
												$tkey_meta = array('fs'=>$fs, 'url'=>str_replace($fvm_cache_paths['cache_url_min'].'/', '', $ur));
															
												# save
												fvm_set_transient(array('uid'=>$tkey, 'date'=>$tvers, 'type'=>'js', 'content'=>$js, 'meta'=>$tkey_meta));	
															
											}
										}
													
										# collect and mark as done for html removal
										$scripts_footer[$tkey] = $js;
										$scripts_footer_log[$tkey] = $tkey;
										
										# mark as processed, unset and break inner loop
										$tag->outertext = '';
										unset($allscripts[$k]);
										continue 2;
										
									} 
								}
							}
						}
				
					}
					
				}
			}
			


			# generate header merged scripts
			if(count($scripts_header) > 0) {

				# merge code, generate cache file paths and urls
				$fheader_code = implode('', $scripts_header);
				$js_header_uid = $tvers.'-'.hash('sha1', $fheader_code).'.header';
				$fheader = $fvm_cache_paths['cache_dir_min']  . DIRECTORY_SEPARATOR .  $js_header_uid.'.min.js';
				$fheader_url = $fvm_cache_paths['cache_url_min'].'/'.$js_header_uid.'.min.js';
				
				# add cdn support
				if(isset($fvm_settings['cdn']['enable']) && $fvm_settings['cdn']['enable'] == true && 
				isset($fvm_settings['cdn']['domain']) && !empty($fvm_settings['cdn']['domain'])) {
					if(isset($fvm_settings['cdn']['jsok']) && $fvm_settings['cdn']['jsok'] == true) {
						$pos = strpos($fheader_url, $fvm_urls['wp_domain']);
						if ($pos !== false) {
							$fheader_url = substr_replace($fheader_url, $fvm_settings['cdn']['domain'], $pos, strlen($fvm_urls['wp_domain']));
						}
					}
				}

				# generate cache file
				clearstatcache();
				if (!file_exists($fheader)) {
					
					# prepare log
					$log = (array) array_values($scripts_header_log);
					$log_meta = array('loc'=>home_url(add_query_arg(NULL, NULL)), 'fl'=>$fheader_url);
					
					# generate cache, write log
					if(!empty($fheader_code)) {
						fvm_save_log(array('uid'=>$fheader_url, 'date'=>$now, 'type'=>'js', 'meta'=>$log_meta, 'content'=>$log));
						fvm_save_file($fheader, $fheader_code);
					}
				}
				
				# preload and save for html implementation (with priority order prefix)
				$htmlpreloader['c_'.$fheader_url] = '<link rel="preload" href="'.$fheader_url.'" as="script" />';
				$htmljscodeheader['c_'.$js_header_uid] = "<script data-cfasync='false' src='".$fheader_url."'></script>";
				
			}
			
			# generate footer merged scripts
			if(count($scripts_footer) > 0) {
				
				# merge code, generate cache file paths and urls
				$ffooter_code = implode('', $scripts_footer);
				$js_ffooter_uid = $tvers.'-'.hash('sha1', $ffooter_code).'.footer';
				$ffooter = $fvm_cache_paths['cache_dir_min']  . DIRECTORY_SEPARATOR .  $js_ffooter_uid.'.min.js';
				$ffooter_url = $fvm_cache_paths['cache_url_min'].'/'.$js_ffooter_uid.'.min.js';
				
				# add cdn support
				if(isset($fvm_settings['cdn']['enable']) && $fvm_settings['cdn']['enable'] == true && 
				isset($fvm_settings['cdn']['domain']) && !empty($fvm_settings['cdn']['domain'])) {
					if(isset($fvm_settings['cdn']['jsok']) && $fvm_settings['cdn']['jsok'] == true) {
						$pos = strpos($ffooter_url, $fvm_urls['wp_domain']);
						if ($pos !== false) {
							$ffooter_url = substr_replace($ffooter_url, $fvm_settings['cdn']['domain'], $pos, strlen($fvm_urls['wp_domain']));
						}
					}
				}
				
				# generate cache file
				clearstatcache();
				if (!file_exists($ffooter)) {
					
					# prepare log
					$log = (array) array_values($scripts_footer_log);
					$log_meta = array('loc'=>home_url(add_query_arg(NULL, NULL)), 'fl'=>$ffooter_url);
												
					# generate cache, write log
					if(!empty($ffooter_code)) {
						fvm_save_log(array('uid'=>$ffooter_url, 'date'=>$now, 'type'=>'js', 'meta'=>$log_meta, 'content'=>$log));
						fvm_save_file($ffooter, $ffooter_code);
					}
				}
						
				# preload and save for html implementation (with priority order prefix)
				$htmlpreloader['d_'.$ffooter_url] = '<link rel="preload" href="'.$ffooter_url.'" as="script" />';
				$htmljscodedefer['d_'.$js_ffooter_uid] = "<script defer src='".$ffooter_url."'></script>";
						
			}

		}
		
		
	
		# process html, if not disabled
		if(isset($fvm_settings['html']['enable']) && $fvm_settings['html']['enable'] == true) {
			
			# Remove HTML comments and IE conditionals
			if(isset($fvm_settings['html']['nocomments']) && $fvm_settings['html']['nocomments'] == true) {
				foreach($html->find('comment') as $element) {
					 $element->outertext = '';
				}
			}
			
			# cleanup header
			if(isset($fvm_settings['html']['cleanup_header']) && $fvm_settings['html']['cleanup_header'] == true) {
				foreach($html->find('head meta[name=generator], head link[rel=shortlink], head link[rel=dns-prefetch], head link[rel=preconnect], head link[rel=prefetch], head link[rel=prerender], head link[rel=EditURI], head link[rel=preconnect], head link[rel=wlwmanifest], head link[type=application/rss+xml], head link[rel=https://api.w.org/], head link[type=application/json+oembed], head link[type=text/xml+oembed], head meta[name*=msapplication], head link[rel=apple-touch-icon]') as $element) {
					 $element->outertext = '';
				}
			}
			
		}
		
		# cdn rewrites, when needed
		$html = fvm_rewrite_assets_cdn($html);
		
		# get charset meta tag
		$metacharset = '';
		foreach($html->find('meta[charset]') as $element) {
			$metacharset = $element->outertext;
			$element->outertext = '';
		}
		

		# add markers for files in the header
		$html->find('head', 0)->innertext = '<!-- fvm_add_preheader --><!-- fvm_add_cssheader --><!-- fvm_add_jsheader -->' . $html->find('head', 0)->innertext;
		
		# add markers for files in the footer
		$html->find('body', -1)->innertext = $html->find('body', -1)->innertext . '<!-- fvm_add_footer_lozad -->';
				
		# convert html object to string
		$html = trim($html->save());
		
		# move charset to the top, if found
		if(!empty($metacharset)) {
			$html = str_replace('<!-- fvm_add_preheader -->', $metacharset.'<!-- fvm_add_preheader -->', $html);
		}
				
		# add preload headers to header
		if(is_array($htmlpreloader)) {
			ksort($htmlpreloader); # priority
			$html = str_replace('<!-- fvm_add_preheader -->', implode('', $htmlpreloader), $html);
		}		
		
		# add stylesheets
		if(is_array($htmlcssheader) && count($htmlcssheader) > 0) {
			ksort($htmlcssheader); # priority
			$html = str_replace('<!-- fvm_add_cssheader -->', implode('', $htmlcssheader).'<!-- fvm_add_cssheader -->', $html);
		}
		
		# add header scripts
		if(is_array($htmljscodeheader) && count($htmljscodeheader) > 0) {
			ksort($htmljscodeheader); # priority
			$html = str_replace('<!-- fvm_add_jsheader -->', implode('', $htmljscodeheader).'<!-- fvm_add_jsheader -->', $html);
		}
		
		# add footer scripts
		if(is_array($htmljscodedefer) && count($htmljscodedefer) > 0) {
			ksort($htmljscodedefer); # priority
			$html = str_replace('<!-- fvm_add_jsheader -->', implode('', $htmljscodedefer), $html);
		}
		
		# cleanup markers
		$html = str_replace(array('<!-- fvm_add_preheader -->', '<!-- fvm_add_cssheader -->', '<!-- fvm_add_jsheader -->', '<!-- fvm_add_footer_lozad -->'), '', $html);
		
		# minify HTML, if not disabled
		if(!isset($fvm_settings['html']['min_disable']) || $fvm_settings['html']['min_disable'] != 1) {
			$html = fvm_raisermin_html($html);
		}
		
	}
		
	# return html
	return $html;
	
}



