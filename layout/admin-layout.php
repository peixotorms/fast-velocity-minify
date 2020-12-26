<div class="wrap">
<h1>Fast Velocity Minify</h1>
<div style="height: 20px;"></div>

<?php
# get active tab, set default
$at = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
?>

<h2 class="nav-tab-wrapper wp-clearfix">
	<a href="?page=fvm" class="nav-tab  <?php echo $at == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>	
	<a href="?page=fvm&tab=status" class="nav-tab <?php echo $at == 'status' ? 'nav-tab-active' : ''; ?>">Status</a> 
	<?php /*<a href="?page=fvm&tab=upgrade" class="nav-tab <?php echo $at == 'upgrade' ? 'nav-tab-active' : ''; ?>">Upgrade</a>*/ ?>
	<a href="?page=fvm&tab=help" class="nav-tab <?php echo $at == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
</h2>

<div id="fvm">

	<?php
		# settings
		include_once($fvm_var_dir_path . 'layout' . DIRECTORY_SEPARATOR . 'admin-layout-settings.php');

		# include other tabs
		include_once($fvm_var_dir_path . 'layout' . DIRECTORY_SEPARATOR . 'admin-layout-status.php');
		#include_once($fvm_var_dir_path . 'layout' . DIRECTORY_SEPARATOR . 'admin-layout-upgrade.php');	
		include_once($fvm_var_dir_path . 'layout' . DIRECTORY_SEPARATOR . 'admin-layout-help.php');	
	?>

</div>