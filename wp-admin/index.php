<?php
/**
 * Dashboard Administration Screen
 *
 * @package WordPress
 * @subpackage Administration
 */

/** Load WordPress Bootstrap */
require_once(dirname(__FILE__) . '/admin.php');

/** Load WordPress dashboard API */
require_once(ABSPATH . 'wp-admin/includes/dashboard.php');

wp_dashboard_setup();

wp_enqueue_script('dashboard');

if (current_user_can('install_plugins')) {
	wp_enqueue_script('plugin-install');
	wp_enqueue_script('updates');
}
if (current_user_can('upload_files'))
	wp_enqueue_script('media-upload');

if (wp_is_mobile())
	wp_enqueue_script('jquery-touch-punch');

$title = __('Dashboard');
$parent_file = 'index.php';

$help = '<p>' . __('Welcome to your WordPress Dashboard!This is the screen you will see when you log in to your site, and gives you access to all the site management features of WordPress. You can get help for any screen by clicking the Help tab above the screen title.') . '</p>';

$screen = get_current_screen();


include(ABSPATH . 'wp-admin/admin-header.php');
?>

<div class="wrap">
	<h1><?php echo esc_html($title); ?> - <a href="<?php echo wp_logout_url(); ?>">Logout</a></h1>

<?php if (has_action('welcome_panel') && current_user_can('edit_theme_options')) :
	$classes = 'welcome-panel';

	$option = get_user_meta(get_current_user_id(), 'show_welcome_panel', true);
	// 0 = hide, 1 = toggled to show or single site creator, 2 = multisite site owner
	$hide = '0' === $option || ('2' === $option && wp_get_current_user()->user_email != get_option('admin_email'));
	if ($hide)
		$classes .= ' hidden'; ?>

	<div id="welcome-panel" class="<?php echo esc_attr($classes); ?>">
		<?php wp_nonce_field('welcome-panel-nonce', 'welcomepanelnonce', false); ?>
		<a class="welcome-panel-close" href="<?php echo esc_url(admin_url('?welcome=0')); ?>" aria-label="<?php esc_attr_e('Dismiss the welcome panel'); ?>"><?php _e('Dismiss'); ?></a>
		<?php
		/**
		 * Add content to the welcome panel on the admin dashboard.
		 *
		 * To remove the default welcome panel, use remove_action():
		 *
		 *
		 * @since 3.5.0
		 */
		do_action('welcome_panel');
		?>
	</div>
<?php endif; ?>

	<div id="dashboard-widgets-wrap">
	<?php wp_dashboard(); ?>
	</div><!-- dashboard-widgets-wrap -->

</div><!-- wrap -->

<?php
require(ABSPATH . 'wp-admin/admin-footer.php');