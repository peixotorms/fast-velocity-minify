<?php if (!defined('ABSPATH')) { exit(); } ?>
<?php 

# server info
if( $active_tab == 'status' ) { 
?>
<div class="fvm-wrapper">

<div id="status">

<div style="height: 40px;"></div>
<h2 class="title"><?php esc_html_e( 'Latest Logs', 'fast-velocity-minify' ); ?></h2>
<h3 class="fvm-bold-green"><?php esc_html_e( 'In this section, you can check the latest logs for CSS and JS files', 'fast-velocity-minify' ); ?></h3>
<div class="row-log log-stats wpraiser-textarea"><textarea rows="10" cols="50" class="large-text code row-log log-css" disabled></textarea></div>

<div style="height: 40px;"></div>
<h2 class="title"><?php esc_html_e( 'Server Info', 'fast-velocity-minify' ); ?></h2>
<h3 class="fvm-bold-green"><?php esc_html_e( 'In this section, you can check some server stats and information', 'fast-velocity-minify' ); ?></h3>
<textarea rows="10" cols="50" class="large-text code row-log" disabled><?php fvm_get_generalinfo(); ?></textarea>


<script>fvm_get_logs();</script>
</div>

</div>
<?php 

}