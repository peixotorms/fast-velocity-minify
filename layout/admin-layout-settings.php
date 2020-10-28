<?php if( $at == 'settings' ) { ?>
<div class="fvm-wrapper">

<form method="post" id="fvm-save-changes">
			
<?php
	# nounce
	wp_nonce_field('fvm_settings_nonce', 'fvm_settings_nonce');
?>

<h2 class="title">HTML Settings</h2>
<h3 class="fvm-bold-green">Optimize your HTML and remove some clutter from the HTML page.</h3>

<table class="form-table fvm-settings">
<tbody>

<tr>
<th scope="row">HTML Options</th>
<td>
<p class="fvm-bold-green fvm-rowintro">Select your options below</p>

<fieldset>
<label for="fvm_settings_html_enable">
<input name="fvm_settings[html][enable]" type="checkbox" id="fvm_settings_html_enable" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'html', 'enable')); ?>>
Enable HTML Processing <span class="note-info">[ Will enable processing for the settings below ]</span></label>
<br />

<label for="fvm_settings_html_min_disable">
<input name="fvm_settings[html][min_disable]" type="checkbox" id="fvm_settings_html_min_disable" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'html', 'min_disable')); ?>>
Disable HTML Minification <span class="note-info">[ Will disable HTML minification for testing purposes ]</span></label>
<br />

<label for="fvm_settings_html_nocomments">
<input name="fvm_settings[html][nocomments]" type="checkbox" id="fvm_settings_html_nocomments" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'html', 'nocomments')); ?>>
Strip HTML Comments <span class="note-info">[ Will strip HTML comments from your HTML page ]</span></label>
<br />

<label for="fvm_settings_html_cleanup_header">
<input name="fvm_settings[html][cleanup_header]" type="checkbox" id="fvm_settings_html_cleanup_header" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'html', 'cleanup_header')); ?>>
Cleanup Header <span class="note-info">[ Removes resource hints, generator tag, shortlinks, manifest link, etc ]</span></label>
<br />

<label for="fvm_settings_html_disable_emojis">
<input name="fvm_settings[html][disable_emojis]" type="checkbox" id="fvm_settings_html_disable_emojis" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'html', 'disable_emojis')); ?>>
Remove Emoji <span class="note-info">[ Removes the default emoji scripts and styles that come with WordPress ]</span></label>
<br />

</fieldset></td>
</tr>

</tbody>
</table>





<div style="height: 60px;"></div>
<h2 class="title">CSS Settings</h2>
<h3 class="fvm-bold-green">Optimize your CSS and Styles settings.</h3>

<table class="form-table fvm-settings">
<tbody>

<tr>
<th scope="row">CSS Options</th>
<td>
<p class="fvm-bold-green fvm-rowintro">Select your options below</p>

<fieldset>
<label for="fvm_settings_css_enable">
<input name="fvm_settings[css][enable]" type="checkbox" id="fvm_settings_css_enable" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'css', 'enable')); ?>>
Enable CSS Processing <span class="note-info">[ Will enable processing for the settings below ]</span></label>
<br />

<label for="fvm_settings_css_min_disable">
<input name="fvm_settings[css][min_disable]" type="checkbox" id="fvm_settings_css_min_disable" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'css', 'min_disable')); ?>>
Disable CSS Minification <span class="note-info">[ Will allow merging but without CSS minification for testing purposes ]</span></label>
<br />

<label for="fvm_settings_css_noprint">
<input name="fvm_settings[css][noprint]" type="checkbox" id="fvm_settings_css_noprint" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'css', 'noprint')); ?>>
Remove "Print" stylesheets <span class="note-info">[ Will remove CSS files of mediatype "print" from the frontend ]</span></label>
<br />

</fieldset></td>
</tr>

<tr>
<th scope="row">CSS Ignore List</th>
<td><fieldset>
<label for="fvm_settings_css_ignore"><span class="fvm-bold-green fvm-rowintro">Ignore the following CSS URL's</span></label>
<p><textarea name="fvm_settings[css][ignore]" rows="7" cols="50" id="fvm_settings_css_ignore" class="large-text code" placeholder="ex: /plugins/something/assets/problematic.css"><?php echo fvm_get_settings_value($fvm_settings, 'css', 'ignore'); ?></textarea></p>
<p class="description">[ CSS files are merged and grouped automatically by mediatype, hence you have an option to exclude files. ]</p>
<p class="description">[ Will match using <code>PHP stripos</code> against the <code>href attribute</code> on the <code>link tag</code> ]</p>
</fieldset></td>
</tr>


<tr>
<th scope="row">CSS Async Options</th>
<td>
<p class="fvm-bold-green fvm-rowintro">Select your options below</p>

<fieldset>
<label for="fvm_settings_css_fonts">
<input name="fvm_settings[css][fonts]" type="checkbox" id="fvm_settings_css_fonts" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'css', 'fonts')); ?>>
Load Fonts and Icons Async<span class="note-info">[ Will try to merge known font and icon CSS files and load them Async ]</span></label>
<br />

<label for="fvm_settings_css_async">
<input name="fvm_settings[css][async]" type="checkbox" id="fvm_settings_css_async" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'css', 'async')); ?>>
Load generated CSS file Async <span class="note-info">[ Will load the merged CSS files Async (use your own inline code for the critical path) ]</span></label>
<br />

</fieldset></td>
</tr>


<tr>
<th scope="row">Remove CSS files</th>
<td><fieldset>
<label for="fvm_settings_css_remove"><span class="fvm-bold-green fvm-rowintro">Remove the following CSS files</span></label>
<p><textarea name="fvm_settings[css][remove]" rows="7" cols="50" id="fvm_settings_css_remove" class="large-text code" placeholder="ex: fonts.googleapis.com"><?php echo fvm_get_settings_value($fvm_settings, 'css', 'remove'); ?></textarea></p>
<p class="description">[ This will allow you to remove unwanted CSS files by URL path from the frontend ]</p>
<p class="description">[ Will match using <code>PHP stripos</code> against the <code>href attribute</code> on the <code>link tag</code> ]</p>
</fieldset></td>
</tr>


</tbody>
</table>



<div style="height: 60px;"></div>
<h2 class="title">JS Settings</h2>
<h3 class="fvm-bold-green">In this section, you can optimize your JS files and inline scripts</h3>

<table class="form-table fvm-settings">
<tbody>

<tr>
<th scope="row">JS Options</th>
<td>
<p class="fvm-bold-green fvm-rowintro">Select your options below</p>

<fieldset>
<label for="fvm_settings_css_enable">
<input name="fvm_settings[js][enable]" type="checkbox" id="fvm_settings_css_enable" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'js', 'enable')); ?>>
Enable JS Processing <span class="note-info">[ Will enable processing for the settings below ]</span></label>
<br />

<label for="fvm_settings_js_min_disable">
<input name="fvm_settings[js][min_disable]" type="checkbox" id="fvm_settings_js_min_disable" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'js', 'min_disable')); ?>>
Disable JS Minification <span class="note-info">[ Will disable JS minification (merge only) for testing purposes ]</span></label>
<br />

<label for="fvm_settings_js_jqupgrade">
<input name="fvm_settings[js][jqupgrade]" type="checkbox" id="fvm_settings_js_jqupgrade" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'js', 'jqupgrade')); ?>>
Upgrade to jQuery 3 <span class="note-info">[ Will use jQuery 3.5.1 and jQuery Migrate 3.3.1 from Cloudflare (if enqueued) ]</span></label>
<br />

</fieldset></td>
</tr>




<tr>
<th scope="row">Merge render blocking JS files in the header</th>
<td><fieldset>
<label for="fvm_settings_merge_header"><span class="fvm-bold-green fvm-rowintro">This will merge and render block all JS files that match the paths below</span></label>
<p><textarea name="fvm_settings[js][merge_header]" rows="7" cols="50" id="fvm_settings_js_merge_header" class="large-text code" placeholder="--- suggested --- 

/jquery-migrate.js 
/jquery.js 
/jquery.min.js"><?php echo fvm_get_settings_value($fvm_settings, 'js', 'merge_header'); ?></textarea></p>
<p class="description">[ One possible match per line, after minification and processing, as seen on the frontend. ]</p>
<p class="description">[ Will match using <code>PHP stripos</code> against the script <code>src attribute</code> ]</p>
</fieldset></td>
</tr>

<tr>
<th scope="row">Merge and Defer Scripts</th>
<td><fieldset>
<label for="fvm_settings_merge_defer"><span class="fvm-bold-green fvm-rowintro">This will merge and defer all JS files that match the paths below</span></label>
<p><textarea name="fvm_settings[js][merge_defer]" rows="7" cols="50" id="fvm_settings_js_merge_defer" class="large-text code" placeholder="--- example --- 

/wp-admin/ 
/wp-includes/ 
/wp-content/"><?php echo fvm_get_settings_value($fvm_settings, 'js', 'merge_defer'); ?></textarea></p>
<p class="description">[ One possible match per line, after minification and processing, as seen on the frontend. ]</p>
<p class="description">[ Will match using <code>PHP stripos</code> against the script <code>src attribute</code> ]</p>
</fieldset></td>
</tr>

<tr>
<th scope="row">Inline JavaScript Dependencies</th>
<td><fieldset>
<label for="fvm_settings_defer_dependencies"><span class="fvm-bold-green fvm-rowintro">Delay Inline JavaScript until after the deferred scripts merged above finish loading</span></label>
<p><textarea name="fvm_settings[js][defer_dependencies]" rows="7" cols="50" id="fvm_settings_js_defer_dependencies" class="large-text code" placeholder="--- any inline scripts that should load, only after the merged deferred scripts above ---"><?php echo fvm_get_settings_value($fvm_settings, 'js', 'defer_dependencies'); ?></textarea></p>
<p class="description">[ Inline JavaScript matching these rules, will wait until after the window.load event ]</p>
<p class="description">[ Will match using <code>PHP stripos</code> against the script <code>innerHTML</code> ]</p>
</fieldset></td>
</tr>

<tr>
<th scope="row">Execute third party inline scripts after user interaction</th>
<td><fieldset>
<label for="fvm_settings_js_thirdparty"><span class="fvm-bold-green fvm-rowintro">Delay the following inline scripts until after user interaction</span></label>
<p><textarea name="fvm_settings[js][thirdparty]" rows="7" cols="50" id="fvm_settings_js_thirdparty" class="large-text code" placeholder="--- example --- 

function(w,d,s,l,i) 
function(f,b,e,v,n,t,s)
function(h,o,t,j,a,r)"><?php echo fvm_get_settings_value($fvm_settings, 'js', 'thirdparty'); ?></textarea></p>
<p class="description">[ If there is no interaction from the user, scripts will still load after 5 seconds automatically. ]</p>
<p class="description">[ Will match using <code>PHP stripos</code> against the script <code>innerHTML</code> ]</p>
</fieldset></td>
</tr>

<tr>
<th scope="row">Remove JavaScript Scripts</th>
<td><fieldset>
<label for="fvm_settings_js_remove"><span class="fvm-bold-green fvm-rowintro">Remove the following JS files or Inline Scripts</span></label>
<p><textarea name="fvm_settings[js][remove]" rows="7" cols="50" id="fvm_settings_js_remove" class="large-text code" placeholder="--- should be empty in most cases ---"><?php echo fvm_get_settings_value($fvm_settings, 'js', 'remove'); ?></textarea></p>
<p class="description">[ This will allow you to remove unwanted script tags from the frontend ]</p>
<p class="description">[ Will match using <code>PHP stripos</code> against the script <code>outerHTML</code> ]</p>
</fieldset></td>
</tr>


</tbody>
</table>



<div style="height: 60px;"></div>
<h2 class="title">CDN Settings</h2>
<h3 class="fvm-bold-green">If your CDN provider gives you a different URL for your assets, you can use it here</h3>
<table class="form-table fvm-settings">
<tbody>
<tr>
<th scope="row">CDN Options</th>
<td>
<p class="fvm-bold-green fvm-rowintro">Select your options below</p>

<fieldset>
<label for="fvm_settings_cdn_enable">
<input name="fvm_settings[cdn][enable]" type="checkbox" id="fvm_settings_cdn_enable" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'cdn', 'enable')); ?>>
Enable CDN Processing <span class="note-info">[ Will enable processing for the settings below ]</span></label>
<br />

<label for="fvm_settings_cdn_cssok">
<input name="fvm_settings[cdn][cssok]" type="checkbox" id="fvm_settings_cdn_cssok" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'cdn', 'cssok')); ?>>
Enable CDN for merged CSS files <span class="note-info">[ Will serve the FVM generated CSS files from the CDN ]</span></label>
<br />

<label for="fvm_settings_cdn_jsok">
<input name="fvm_settings[cdn][jsok]" type="checkbox" id="fvm_settings_cdn_jsok" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'cdn', 'jsok')); ?>>
Enable CDN for merged JS files <span class="note-info">[ Will serve the FVM generated JS files from the CDN ]</span></label>
<br />

</fieldset></td>
</tr>
<tr>
<th scope="row"><span class="fvm-label-special">CDN URL</span></th>
<td><fieldset>
<label for="fvm_settings_cdn_domain">
<p><input type="text" name="fvm_settings[cdn][domain]" id="fvm_settings_cdn_domain" value="<?php echo fvm_get_settings_value($fvm_settings, 'cdn', 'domain'); ?>" size="80" /></p>
<p class="description">[ Not needed for Cloudflare or same domain reverse proxy cdn services. ]</p>
</label>
<br />
</fieldset></td>
</tr>
<tr>
<th scope="row">CDN Integration</th>
<td><fieldset>
<label for="fvm_settings_cdn_integration"><span class="fvm-bold-green fvm-rowintro">Replace the following elements</span></label>
<p><textarea name="fvm_settings[cdn][integration]" rows="7" cols="50" id="fvm_settings_cdn_integration" class="large-text code" placeholder="--- check the help section for suggestions ---"><?php echo fvm_get_settings_value($fvm_settings, 'cdn', 'integration'); ?></textarea></p>
<p class="description">[ Uses syntax from <code>https://simplehtmldom.sourceforge.io/manual.htm</code> ]</p>
<p class="description">[ You can target a child of a specific html tag, an element with a specific attribute, class or id. ]</p>
</fieldset></td>
</tr>
</tbody></table>



<div style="height: 60px;"></div>
<h2 class="title">Cache Settings</h2>
<h3 class="fvm-bold-green">You can adjust your FVM cache settings here</h3>
<table class="form-table fvm-settings">
<tbody>
<tr>
<th scope="row">Instant Cache Purge</th>
<td>


<fieldset>
<label for="fvm_settings_cache_min_instant_purge">
<input name="fvm_settings[cache][min_instant_purge]" type="checkbox" id="fvm_settings_cache_min_instant_purge" value="1" <?php echo fvm_get_settings_checkbox(fvm_get_settings_value($fvm_settings, 'cache', 'min_instant_purge')); ?>>
Purge Minified CSS/JS files instantly <span class="note-info">[ Cache files can take up to 24 hours to be deleted by default, for compatibility reasons with certain hosts. ]</span></label>
<br />



</fieldset></td>
</tr>
<tr>
<th scope="row"><span class="fvm-label-special">Public Cache Path</span></th>
<td><fieldset>
<label for="fvm_settings_cache_path">
<p><input type="text" name="fvm_settings[cache][path]" id="fvm_settings_cache_path" value="<?php echo fvm_get_settings_value($fvm_settings, 'cache', 'path'); ?>" size="80" /></p>
<p class="description">[ Current base path: <code><?php echo $fvm_cache_paths['cache_base_dir']; ?></code> ]</p>
</label>
<br />
</fieldset></td>
</tr>
<tr>
<th scope="row"><span class="fvm-label-special">Public Cache URL</span></th>
<td><fieldset>
<label for="fvm_settings_cache_url">
<p><input type="text" name="fvm_settings[cache][url]" id="fvm_settings_cache_url" value="<?php echo fvm_get_settings_value($fvm_settings, 'cache', 'url'); ?>" size="80" /></p>
<p class="description">[ Current base url: <code><?php echo $fvm_cache_paths['cache_base_dirurl']; ?></code> ]</p>
</label>
<br />
</fieldset></td>
</tr>
</tbody></table>





<input type="hidden" name="fvm_action" value="save_settings" />
<p class="submit"><input type="submit" class="button button-primary" value="Save Changes"></p>

</form>
</div>
<?php 
}
