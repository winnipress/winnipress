<?php
/**
 * WordPress Administration Template Footer
 *
 * @package WordPress
 * @subpackage Administration
 */

// don't load directly
if (!defined('ABSPATH'))
	die('-1');

/**
 * @global string $hook_suffix
 */
global $hook_suffix;
?>

<div class="clear"></div></div><!-- wpbody-content -->
<div class="clear"></div></div><!-- wpbody -->
<div class="clear"></div></div><!-- wpcontent -->


<?php
/**
 * Prints scripts or data before the default footer scripts.
 *
 * @since 1.2.0
 *
 * @param string $data The data to print.
 */
do_action('admin_footer', '');

/**
 * Prints scripts and data queued for the footer.
 *
 * The dynamic portion of the hook name, `$hook_suffix`,
 * refers to the global hook suffix of the current page.
 *
 * @since 4.6.0
 */
do_action("admin_print_footer_scripts-{$hook_suffix}");

/**
 * Prints any scripts and data queued for the footer.
 *
 * @since 2.8.0
 */
do_action('admin_print_footer_scripts');

/**
 * Prints scripts or data after the default footer scripts.
 *
 * The dynamic portion of the hook name, `$hook_suffix`,
 * refers to the global hook suffix of the current page.
 *
 * @since 2.8.0
 */
do_action("admin_footer-{$hook_suffix}");

// get_site_option() won't exist when auto upgrading from <= 2.7
if (function_exists('get_site_option')) {
	if (false === get_site_option('can_compress_scripts'))
		compression_test();
}






// Print logs
winni_print_logs();



// Register all called files to see what we use and what not
$all_included_files_so_far = get_included_files();
global $wpdb;
foreach ($all_included_files_so_far as $the_included_file) {
    $filennenemae = '.'.str_replace('\\','/',str_replace('C:\laragon\www\winnipress','',$the_included_file))."\n";
	$wpdb->get_results("INSERT IGNORE INTO calledfiles (filename) VALUES ('".sanitize_text_field($filennenemae)."') ON DUPLICATE KEY UPDATE calls=calls+1");
}

?>

<div class="clear"></div></div><!-- wpwrap -->
<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
</body>
</html>
