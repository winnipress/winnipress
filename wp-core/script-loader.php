<?php

/** WordPress Dependency Class */
require(ABSPATH . 'wp-core/classes/dependency.php');

/** WordPress Dependencies Class */
require(ABSPATH . 'wp-core/classes/dependencies.php');

/** WordPress Scripts Class */
require(ABSPATH . 'wp-core/classes/scripts.php');

/** WordPress Scripts Functions */
require(ABSPATH . 'wp-core/functions/scripts.php');

/** WordPress Styles Class */
require(ABSPATH . 'wp-core/classes/styles.php');

/** WordPress Styles Functions */
require(ABSPATH . 'wp-core/functions/styles.php');



/**
 * Administration Screen CSS for changing the styles.
 *
 * If installing the 'admin/' directory will be replaced with './'.
 *
 * The $_wp_admin_css_colors global manages the Administration Screens CSS
 * stylesheet that is loaded. The option that is set is 'admin_color' and is the
 * color and key for the array. The value for the color key is an object with
 * a 'url' parameter that has the URL path to the CSS file.
 *
 * The query from $src parameter will be appended to the URL that is given from
 * the $_wp_admin_css_colors array value URL.
 *
 * @since 2.6.0
 * @global array $_wp_admin_css_colors
 *
 * @param string $src    Source URL.
 * @param string $handle Either 'colors' or 'colors-rtl'.
 * @return string|false URL path to CSS stylesheet for Administration Screens.
 */
function wp_style_loader_src($src, $handle){
	global $_wp_admin_css_colors;

	if(wp_installing())
		return preg_replace('#^admin/#', './', $src);

	if('colors' == $handle){
		$color = get_user_option('admin_color');

		if(empty($color) || !isset($_wp_admin_css_colors[$color]))
			$color = 'fresh';

		$color = $_wp_admin_css_colors[$color];
		$url = $color->url;

		if(!$url){
			return false;
		}

		$parsed = parse_url($src);
		if(isset($parsed['query']) && $parsed['query']){
			wp_parse_str($parsed['query'], $qv);
			$url = add_query_arg($qv, $url);
		}

		return $url;
	}

	return $src;
}

/**
 * Prints the script queue in the HTML head on admin pages.
 *
 * Postpones the scripts that were queued for the footer.
 * print_footer_scripts() is called in the footer to print these scripts.
 *
 * @since 2.8.0
 *
 * @see wp_print_scripts()
 *
 * @global bool $concatenate_scripts
 *
 * @return array
 */
function print_head_scripts(){
	global $concatenate_scripts;

	if(!did_action('wp_print_scripts')){
		/** This action is documented in wp-core/functions.wp-scripts.php */
		do_action('wp_print_scripts');
	}

	$wp_scripts = wp_scripts();

	script_concat_settings();
	$wp_scripts->do_concat = $concatenate_scripts;
	$wp_scripts->do_head_items();

	/**
	 * Filters whether to print the head scripts.
	 *
	 * @since 2.8.0
	 *
	 * @param bool $print Whether to print the head scripts. Default true.
	 */
	if(apply_filters('print_head_scripts', true)){
		_print_scripts();
	}

	$wp_scripts->reset();
	return $wp_scripts->done;
}

/**
 * Prints the scripts that were queued for the footer or too late for the HTML head.
 *
 * @since 2.8.0
 *
 * @global WP_Scripts $wp_scripts
 * @global bool       $concatenate_scripts
 *
 * @return array
 */
function print_footer_scripts(){
	global $wp_scripts, $concatenate_scripts;

	if(!($wp_scripts instanceof WP_Scripts)){
		return array(); // No need to run if not instantiated.
	}
	script_concat_settings();
	$wp_scripts->do_concat = $concatenate_scripts;
	$wp_scripts->do_footer_items();

	/**
	 * Filters whether to print the footer scripts.
	 *
	 * @since 2.8.0
	 *
	 * @param bool $print Whether to print the footer scripts. Default true.
	 */
	if(apply_filters('print_footer_scripts', true)){
		_print_scripts();
	}

	$wp_scripts->reset();
	return $wp_scripts->done;
}

/**
 * Print scripts (internal use only)
 *
 * @ignore
 *
 * @global WP_Scripts $wp_scripts
 * @global bool       $compress_scripts
 */
function _print_scripts(){
	global $wp_scripts, $compress_scripts;

	$zip = $compress_scripts ? 1 : 0;
	if($zip && defined('ENFORCE_GZIP') && ENFORCE_GZIP)
		$zip = 'gzip';

	if($concat = trim($wp_scripts->concat, ', ')){

		if(!empty($wp_scripts->print_code)){
			echo "\n<script type='text/javascript'>\n";
			echo "/* <![CDATA[ */\n"; // not needed in HTML 5
			echo $wp_scripts->print_code;
			echo "/* ]]> */\n";
			echo "</script>\n";
		}

		$concat = str_split($concat, 128);
		$concat = 'load%5B%5D=' . implode('&load%5B%5D=', $concat);

		$src = $wp_scripts->base_url . "/admin/load-scripts.php?c={$zip}&" . $concat . '&ver=' . $wp_scripts->default_version;
		echo "<script type='text/javascript' src='" . esc_attr($src) . "'></script>\n";
	}

	if(!empty($wp_scripts->print_html))
		echo $wp_scripts->print_html;
}

/**
 * Prints the script queue in the HTML head on the front end.
 *
 * Postpones the scripts that were queued for the footer.
 * wp_print_footer_scripts() is called in the footer to print these scripts.
 *
 * @since 2.8.0
 *
 * @global WP_Scripts $wp_scripts
 *
 * @return array
 */
function wp_print_head_scripts(){
	if(!did_action('wp_print_scripts')){
		/** This action is documented in wp-core/functions.wp-scripts.php */
		do_action('wp_print_scripts');
	}

	global $wp_scripts;

	if(!($wp_scripts instanceof WP_Scripts)){
		return array(); // no need to run if nothing is queued
	}
	return print_head_scripts();
}

/**
 * Private, for use in *_footer_scripts hooks
 *
 * @since 3.3.0
 */
function _wp_footer_scripts(){
	print_late_styles();
	print_footer_scripts();
}

/**
 * Hooks to print the scripts and styles in the footer.
 *
 * @since 2.8.0
 */
function wp_print_footer_scripts(){
	/**
	 * Fires when footer scripts are printed.
	 *
	 * @since 2.8.0
	 */
	do_action('wp_print_footer_scripts');
}

/**
 * Wrapper for do_action('wp_enqueue_scripts')
 *
 * Allows plugins to queue scripts for the front end using wp_enqueue_script().
 * Runs first in wp_head() where all is_home(), is_page(), etc. functions are available.
 *
 * @since 2.8.0
 */
function wp_enqueue_scripts(){
	/**
	 * Fires when scripts and styles are enqueued.
	 *
	 * @since 2.8.0
	 */
	do_action('wp_enqueue_scripts');
}

/**
 * Prints the styles queue in the HTML head on admin pages.
 *
 * @since 2.8.0
 *
 * @global bool $concatenate_scripts
 *
 * @return array
 */
function print_admin_styles(){
	global $concatenate_scripts;

	$wp_styles = wp_styles();

	script_concat_settings();
	$wp_styles->do_concat = $concatenate_scripts;
	$wp_styles->do_items(false);

	/**
	 * Filters whether to print the admin styles.
	 *
	 * @since 2.8.0
	 *
	 * @param bool $print Whether to print the admin styles. Default true.
	 */
	if(apply_filters('print_admin_styles', true)){
		_print_styles();
	}

	$wp_styles->reset();
	return $wp_styles->done;
}

/**
 * Prints the styles that were queued too late for the HTML head.
 *
 * @since 3.3.0
 *
 * @global WP_Styles $wp_styles
 * @global bool      $concatenate_scripts
 *
 * @return array|void
 */
function print_late_styles(){
	global $wp_styles, $concatenate_scripts;

	if(!($wp_styles instanceof WP_Styles)){
		return;
	}

	script_concat_settings();
	$wp_styles->do_concat = $concatenate_scripts;
	$wp_styles->do_footer_items();

	/**
	 * Filters whether to print the styles queued too late for the HTML head.
	 *
	 * @since 3.3.0
	 *
	 * @param bool $print Whether to print the 'late' styles. Default true.
	 */
	if(apply_filters('print_late_styles', true)){
		_print_styles();
	}

	$wp_styles->reset();
	return $wp_styles->done;
}

/**
 * Print styles (internal use only)
 *
 * @ignore
 * @since 3.3.0
 *
 * @global bool $compress_css
 */
function _print_styles(){
	global $compress_css;

	$wp_styles = wp_styles();

	$zip = $compress_css ? 1 : 0;
	if($zip && defined('ENFORCE_GZIP') && ENFORCE_GZIP)
		$zip = 'gzip';

	if($concat = trim($wp_styles->concat, ', ')){
		$dir = $wp_styles->text_direction;
		$ver = $wp_styles->default_version;

		$concat = str_split($concat, 128);
		$concat = 'load%5B%5D=' . implode('&load%5B%5D=', $concat);

		$href = $wp_styles->base_url . "/admin/load-styles.php?c={$zip}&dir={$dir}&" . $concat . '&ver=' . $ver;
		echo "<link rel='stylesheet' href='" . esc_attr($href) . "' type='text/css' media='all' />\n";

		if(!empty($wp_styles->print_code)){
			echo "<style type='text/css'>\n";
			echo $wp_styles->print_code;
			echo "\n</style>\n";
		}
	}

	if(!empty($wp_styles->print_html))
		echo $wp_styles->print_html;
}

/**
 * Determine the concatenation and compression settings for scripts and styles.
 *
 * @since 2.8.0
 *
 * @global bool $concatenate_scripts
 * @global bool $compress_scripts
 * @global bool $compress_css
 */
function script_concat_settings(){
	global $concatenate_scripts, $compress_scripts, $compress_css;

	$compressed_output = (ini_get('zlib.output_compression') || 'ob_gzhandler' == ini_get('output_handler'));

	if(!isset($concatenate_scripts)){
		$concatenate_scripts = defined('CONCATENATE_SCRIPTS') ? CONCATENATE_SCRIPTS : true;
		if((!is_admin() && !did_action('login_init')) || (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG))
			$concatenate_scripts = false;
	}

	if(!isset($compress_scripts)){
		$compress_scripts = defined('COMPRESS_SCRIPTS') ? COMPRESS_SCRIPTS : true;
		if($compress_scripts && (!get_site_option('can_compress_scripts') || $compressed_output))
			$compress_scripts = false;
	}

	if(!isset($compress_css)){
		$compress_css = defined('COMPRESS_CSS') ? COMPRESS_CSS : true;
		if($compress_css && (!get_site_option('can_compress_scripts') || $compressed_output))
			$compress_css = false;
	}
}
