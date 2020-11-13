<?php 

# server info
if( $at == 'status' ) { 
?>
<div class="fvm-wrapper">

<div id="status">
<h2 class="title">Cache Stats</h2>
<h3 class="fvm-cache-stats fvm-bold-green"></h3>


<div style="height: 40px;"></div>
<h2 class="title">CSS Logs</h2>
<h3 class="fvm-bold-green">In this section, you can check the latest CSS merging logs</h3>
<textarea rows="10" cols="50" class="large-text code row-log log-css" disabled></textarea>


<div style="height: 40px;"></div>
<h2 class="title">JS Logs</h2>
<h3 class="fvm-bold-green">In this section, you can check the latest JS merging logs</h3>
<textarea rows="10" cols="50" class="large-text code row-log log-js" disabled></textarea>
<div style="height: 20px;"></div>


<div style="height: 40px;"></div>
<h2 class="title">Server Info</h2>
<h3 class="fvm-bold-green">In this section, you can check some server stats and information</h3>
<textarea rows="10" cols="50" class="large-text code row-log" disabled><?php fvm_get_generalinfo(); ?></textarea>


<script>fvm_get_logs();</script>
</div>

</div>
<?php 

}