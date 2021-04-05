<?php
// Dashboard Administration Screen

/** Load WordPress Bootstrap */
require_once(dirname(__FILE__) . '/admin.php');

/** Load WordPress dashboard API */
require_once(ABSPATH . 'wp-admin/includes/dashboard.php');

wp_dashboard_setup();


$parent_file = 'index.php';


$screen = get_current_screen();


include(ABSPATH . 'wp-admin/admin-header.php');
?>

<div class="wrap">
	<h1><?php echo __('Dashboard'); ?></h1>

	<h2><a href="<?php echo wp_logout_url(); ?>">Logout</a></h2>

	<div id="dashboard-widgets-wrap">
	<?php wp_dashboard(); ?>
	</div><!-- dashboard-widgets-wrap -->

</div><!-- wrap -->

<?php
require(ABSPATH . 'wp-admin/admin-footer.php');