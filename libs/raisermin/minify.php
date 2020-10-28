<?php
/**
*
* Website: https://fvm.com/
* Author: Raul Peixoto (https://www.upwork.com/fl/raulpeixoto)
* Licensed under GPLv2 (or later)
* Version 1.0
*
* Usage: fvm_raisermin_js($js);
*
*/

# Exit if accessed directly				
if (!defined('ABSPATH')){ exit(); }	

# minify js, whitespace only
function fvm_raisermin_js($code){

	# remove // comments
	$code = preg_replace('/(^|\s)\/\/(.*)\n/m', '', $code);
	$code = preg_replace('/(\{|\}|\[|\]|\(|\)|\;)\/\/(.*)\n/m', '$1', $code);
	
	# remove /* ... */ comments
	$code = preg_replace('/(^|\s)\/\*(.*)\*\//Us', '', $code);
	$code = preg_replace('/(\;|\{)\/\*(.*)\*\//Us', '$1', $code);

	# remove sourceMappingURL
	$code = preg_replace('/(\/\/\s*[#]\s*sourceMappingURL\s*[=]\s*)([a-zA-Z0-9-_\.\/]+)(\.map)/ui', '', $code);
	
	# uniform line endings, make them all line feed
	$code = str_replace(array("\r\n", "\r"), "\n", $code);

	# collapse all non-line feed whitespace into a single space
	$code = preg_replace('/[^\S\n]+/', ' ', $code);

	# strip leading & trailing whitespace
	$code = str_replace(array(" \n", "\n "), "\n", $code);

	# collapse consecutive line feeds into just 1
	$code = preg_replace('/\n+/', "\n", $code);
		
	# process horizontal space
	$code = preg_replace('/(\h?)(\|\||\&\&|[\{\}\[\]\?:\.;=])(\h?)/ui', '$2', $code);
	$code = preg_replace('/([\[\]\(\)\{\}\;\<\>])(\h+)([\[\]\(\)\{\}\;\<\>])/ui', '$1 $3', $code);
	$code = preg_replace('/([\)])(\h?)(\.)/ui', '$1$3', $code);
	$code = preg_replace('/([\)\?])(\h?)(\.)/ui', '$1$3', $code);
	$code = preg_replace('/(\,)(\h+)/ui', '$1 ', $code);
	$code = preg_replace('/(\h+)(\,)/ui', ' $2', $code);
	$code = preg_replace('/([if])(\h+)(\()/ui', '$1$3', $code);
			
	# trim whitespace on beginning/end
	return trim($code);
}


# remove UTF8 BOM
function fvm_min_remove_utf8_bom($text) {
    $bom = pack('H*','EFBBBF');
	while (preg_match("/^$bom/", $text)) {
		$text = preg_replace("/^$bom/ui", '', $text);
	}
    return $text;
}




# minify html, don't touch certain tags
function fvm_raisermin_html($html) {

			# clone
			$content = $html;
			
			# get all scripts
			$allscripts = array();
			preg_match_all('/\<script(.*?)\<(\s*)\/script(\s*)\>/uis', $html, $allscripts);
			
			# replace all scripts and styles with a marker
			if(is_array($allscripts) && isset($allscripts[0]) && count($allscripts[0]) > 0) {
				foreach ($allscripts[0] as $k=>$v) {
					$content = str_replace($v, '<!-- SCRIPT '.$k.' -->', $content);
				}
			}
			
			# remove linebreaks, and colapse two or more white spaces into one
			$content = preg_replace('/\s+/u', " ", $content);
			
			# remove space between tags
			$content = str_replace('> <', '><', $content);
			
			# replace markers with scripts and styles			
			if(is_array($allscripts) && isset($allscripts[0]) && count($allscripts[0]) > 0) {
				foreach ($allscripts[0] as $k=>$v) {
					$content = str_replace('<!-- SCRIPT '.$k.' -->', $v, $content);
				}
			}
			
			# save as html, if not empty
			if(!empty($content)) {
				$html = $content;
			}
	
	# return
	return $html;
}
