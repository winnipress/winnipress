<?php

// Installing WordPress
define('WP_INSTALLING', true);

// Load WP
require_once(dirname(dirname(__FILE__)) . '/wp-load.php');

// Load the WP upgrade API (gotta change some stuff)
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Load WP DB
require_once(ABSPATH . WPINC . '/wp-db.php');

nocache_headers();

// Installation step
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;

// Display installation header
function display_header(){ yeah(__METHOD__);
	header('Content-Type: text/html; charset=utf-8');
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta name="viewport" content="width=device-width" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="robots" content="noindex,nofollow" />
		<title><?php _e('WinniPress &rsaquo; Installation'); ?></title>
		<?php
			wp_admin_css('install', true);
			wp_admin_css('dashicons', true);
		?>
	</head>
	<body class="wp-core-ui">
	<h1>WinniPress</h1>
<?php
}

// Display installer setup form
function display_setup_form($error = null){ yeah(__METHOD__);
	global $wpdb;

	$installation_title = isset($_POST['installation_title']) ? trim(wp_unslash($_POST['installation_title'])) : '';
	$user_name = isset($_POST['user_name']) ? trim(wp_unslash($_POST['user_name'])) : '';
	$admin_email  = isset($_POST['admin_email']) ? trim(wp_unslash($_POST['admin_email'])) : '';

	if(!is_null($error)){
?>
<h2>Hi</h2>
<p class="message"><?php echo $error; ?></p>
<?php } ?>
<form id="setup" method="post" action="install.php?step=2">
	<table class="form-table">
		<tr>
			<th scope="row"><label for="installation_title"><?php _e('Installation title'); ?></label></th>
			<td>
			<input name="installation_title" required type="text" id="installation_title" size="25" value="<?php echo esc_attr($installation_title); ?>" />
			<p><?php _e('A title for your blog, website, app or whatever it is...'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="user_login"><?php _e('Username'); ?></label></th>
			<td>
			<input name="user_name" required type="text" id="user_login" size="25" value="<?php echo esc_attr(sanitize_user($user_name, true)); ?>" />
			<p><?php _e('Alphanumeric characters, spaces, underscores, hyphens, periods, and the @ symbol.'); ?></p>
			</td>
		</tr>
		<tr class="form-field form-required user-pass1-wrap">
			<th scope="row">
				<label for="pass1">
					<?php _e('Password'); ?>
				</label>
			</th>
			<td>
				<input type="password" required name="admin_password" id="pass1" class="regular-text" autocomplete="off" />
			</td>
		</tr>
		<tr class="form-field form-required user-pass2-wrap">
			<th scope="row">
				<label for="pass2">
				<?php _e('Repeat Password'); ?>
				</label>
			</th>
			<td>
				<input name="admin_password2" required type="password" id="pass2" autocomplete="off" />
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="admin_email"><?php _e('Your Email'); ?></label></th>
			<td><input name="admin_email" required type="email" id="admin_email" size="25" value="<?php echo esc_attr($admin_email); ?>" />
			<p><?php _e('Triple-check your email address before continuing.'); ?></p></td>
		</tr>
	</table>
	<p class="step"><?php submit_button(__('Install'), 'large', 'Submit', false, array('id' => 'submit')); ?></p>
	<input type="hidden" name="language" value="<?php echo isset($_REQUEST['language']) ? esc_attr($_REQUEST['language']) : ''; ?>" />
</form>
<?php
}

// Let's check to make sure WP isn't already installed.
if(is_wp_installed()){
	display_header();
	die(
		'<h1>' . __('Already Installed') . '</h1>' .
		'<p>' . __('You appear to have already installed WordPress. To reinstall please clear your old database tables first.') . '</p>' .
		'<p class="step"><a href="' . esc_url(wp_login_url()) . '" class="button button-large">' . __('Log In') . '</a></p>' .
		'</body></html>'
	);
}

// Grab version infos
global $wp_version, $required_php_version, $required_mysql_version;

$php_version    = phpversion();
$mysql_version  = $wpdb->db_version();
$php_compat     = version_compare($php_version, $required_php_version, '>=');
$mysql_compat   = version_compare($mysql_version, $required_mysql_version, '>=') || file_exists(WP_CONTENT_DIR . '/db.php');

if(!$mysql_compat && !$php_compat){
	$compat = sprintf(__('You cannot install because WinniPress %1$s requires PHP version %2$s or higher and MySQL version %3$s or higher. You are running PHP version %4$s and MySQL version %5$s.'), $wp_version, $required_php_version, $required_mysql_version, $php_version, $mysql_version);
}elseif(!$php_compat){
	$compat = sprintf(__('You cannot install because WinniPress %1$s requires PHP version %2$s or higher. You are running version %3$s.'), $wp_version, $required_php_version, $php_version);
}elseif(!$mysql_compat){
	$compat = sprintf(__('You cannot install because WinniPress %1$s requires MySQL version %2$s or higher. You are running version %3$s.'), $wp_version, $required_mysql_version, $mysql_version);
}

if(!$mysql_compat || !$php_compat){
	display_header();
	die('<h1>' . __('Insufficient Requirements') . '</h1><p>' . $compat . '</p></body></html>');
}


switch($step){
	case 1: // Step 1, direct link or from language chooser.
		display_header();
		?>
		<p><?php _e('Just fill in the following stuff and go for it!'); ?></p>

		<?php
		display_setup_form();
		break;
	case 2:
		$loaded_language = 'en_US';
		
		if(!empty($wpdb->error)){
			wp_die($wpdb->error->get_error_message());
		}

		display_header();

		// Fill in the data we gathered
		$installation_title = isset($_POST['installation_title']) ? trim(wp_unslash($_POST['installation_title'])) : '';
		$user_name = isset($_POST['user_name']) ? trim(wp_unslash($_POST['user_name'])) : '';
		$admin_password = isset($_POST['admin_password']) ? wp_unslash($_POST['admin_password']) : '';
		$admin_password_check = isset($_POST['admin_password2']) ? wp_unslash($_POST['admin_password2']) : '';
		$admin_email  = isset($_POST['admin_email']) ?trim(wp_unslash($_POST['admin_email'])) : '';
		$public       = isset($_POST['blog_public']) ? (int) $_POST['blog_public'] : 1;

		// Check stuff
		$error = false;
		if(empty($user_name)){
			display_setup_form(__('Please provide a valid username.'));
			$error = true;
		}elseif($user_name != sanitize_user($user_name, true)){
			display_setup_form(__('The username you provided has invalid characters.'));
			$error = true;
		}elseif($admin_password != $admin_password_check){
			display_setup_form(__('Your passwords do not match. Please try again.'));
			$error = true;
		}elseif(empty($admin_email)){
			display_setup_form(__('You must provide an email address.'));
			$error = true;
		}elseif(!is_email($admin_email)){
			display_setup_form(__('You must provide a valid email address.'));
			$error = true;
		}

		if($error === false){
			$wpdb->show_errors();
			$result = wp_install($installation_title, $user_name, $admin_email, $public, '', wp_slash($admin_password), $loaded_language);
			?>

			<p><?php _e('Yeah, your new installation is ready. Go make something awesome!'); ?></p>

			<table class="form-table install-success">
				<tr>
					<th><?php _e('Username'); ?></th>
					<td><?php echo esc_html(sanitize_user($user_name, true)); ?></td>
				</tr>
				<tr>
					<th><?php _e('Password'); ?></th>
					<td><?php
					if(!empty($result['password']) && empty($admin_password_check)): ?>
						<code><?php echo esc_html($result['password']) ?></code><br />
					<?php endif ?>
						<p><?php echo $result['password_message'] ?></p>
					</td>
				</tr>
			</table>

			<p class="step"><a href="<?php echo esc_url(wp_login_url()); ?>" class="button button-large"><?php _e('Log In'); ?></a></p>

			<?php
		}
		break;
}
?>

</body>
</html>