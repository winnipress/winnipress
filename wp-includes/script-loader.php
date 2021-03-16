<?php
/**
 * WordPress scripts and styles default loader.
 *
 * Several constants are used to manage the loading, concatenating and compression of scripts and CSS:
 * define('SCRIPT_DEBUG', true); loads the development (non-minified) versions of all scripts and CSS, and disables compression and concatenation,
 * define('CONCATENATE_SCRIPTS', false); disables compression and concatenation of scripts and CSS,
 * define('COMPRESS_SCRIPTS', false); disables compression of scripts,
 * define('COMPRESS_CSS', false); disables compression of CSS,
 * define('ENFORCE_GZIP', true); forces gzip for compression (default is deflate).
 *
 * The globals $concatenate_scripts, $compress_scripts and $compress_css can be set by plugins
 * to temporarily override the above settings. Also a compression test is run once and the result is saved
 * as option 'can_compress_scripts' (0/1). The test will run again if that option is deleted.
 *
 * @package WordPress
 */


/** WordPress Dependency Class */
require(ABSPATH . WPINC . '/class-wp-dependency.php' );

/** WordPress Dependencies Class */
require(ABSPATH . WPINC . '/class.wp-dependencies.php' );

/** WordPress Scripts Class */
require(ABSPATH . WPINC . '/class.wp-scripts.php' );

/** WordPress Scripts Functions */
require(ABSPATH . WPINC . '/functions.wp-scripts.php' );

/** WordPress Styles Class */
require(ABSPATH . WPINC . '/class.wp-styles.php' );

/** WordPress Styles Functions */
require(ABSPATH . WPINC . '/functions.wp-styles.php' );



/**
 * Assign default styles to $styles object.
 *
 * Nothing is returned, because the $styles parameter is passed by reference.
 * Meaning that whatever object is passed will be updated without having to
 * reassign the variable that was passed back to the same value. This saves
 * memory.
 *
 * Adding default styles is not the only task, it also assigns the base_url
 * property, the default version, and text direction for the object.
 *
 * @since 2.6.0
 *
 * @param WP_Styles $styles
 */
function wp_default_styles($styles){

	if (!$guessurl = site_url() )
		$guessurl = wp_guess_url();

	$styles->base_url = $guessurl;
	$styles->content_url = defined('WP_CONTENT_URL')? WP_CONTENT_URL : '';
	$styles->default_version = get_bloginfo('version' );

	$suffix = '.min';

	// Admin CSS
	$styles->add('common',              "/wp-admin/css/common$suffix.css" );
	$styles->add('forms',               "/wp-admin/css/forms$suffix.css" );
	$styles->add('admin-menu',          "/wp-admin/css/admin-menu$suffix.css" );
	$styles->add('dashboard',           "/wp-admin/css/dashboard$suffix.css" );
	$styles->add('list-tables',         "/wp-admin/css/list-tables$suffix.css" );
	$styles->add('edit',                "/wp-admin/css/edit$suffix.css" );
	$styles->add('media',               "/wp-admin/css/media$suffix.css" );
	$styles->add('login',               "/wp-admin/css/login$suffix.css" );

}



/**
 * Load localized data on print rather than initialization.
 *
 * These localizations require information that may not be loaded even by init.
 *
 * @since 2.5.0
 */
function wp_just_in_time_script_localization(){

	wp_localize_script('autosave', 'autosaveL10n', array(
		'autosaveInterval' => AUTOSAVE_INTERVAL,
		'blog_id' => get_current_blog_id(),
	) );

	wp_localize_script('mce-view', 'mceViewL10n', array(
		'shortcodes' => !empty($GLOBALS['shortcode_tags'] ) ? array_keys($GLOBALS['shortcode_tags'] ) : array()
	) );

	wp_localize_script('word-count', 'wordCountL10n', array(
		/*
		 * translators: If your word count is based on single characters (e.g. East Asian characters),
		 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
		 * Do not translate into your own language.
		 */
		'type' => _x('words', 'Word count type. Do not translate!' ),
		'shortcodes' => !empty($GLOBALS['shortcode_tags'] ) ? array_keys($GLOBALS['shortcode_tags'] ) : array()
	) );
}

/**
 * Localizes the jQuery UI datepicker.
 *
 * @since 4.6.0
 *
 * @link https://api.jqueryui.com/datepicker/#options
 *
 * @global WP_Locale $wp_locale The WordPress date and time locale object.
 */
function wp_localize_jquery_ui_datepicker(){
	global $wp_locale;

	if (!wp_script_is('jquery-ui-datepicker', 'enqueued' ) ){
		return;
	}

	// Convert the PHP date format into jQuery UI's format.
	$datepicker_date_format = str_replace(
		array(
			'd', 'j', 'l', 'z', // Day.
			'F', 'M', 'n', 'm', // Month.
			'Y', 'y'            // Year.
		),
		array(
			'dd', 'd', 'DD', 'o',
			'MM', 'M', 'm', 'mm',
			'yy', 'y'
		),
		get_option('date_format' )
	);

	$datepicker_defaults = wp_json_encode(array(
		'closeText'       => __('Close' ),
		'currentText'     => __('Today' ),
		'monthNames'      => array_values($wp_locale->month ),
		'monthNamesShort' => array_values($wp_locale->month_abbrev ),
		'nextText'        => __('Next' ),
		'prevText'        => __('Previous' ),
		'dayNames'        => array_values($wp_locale->weekday ),
		'dayNamesShort'   => array_values($wp_locale->weekday_abbrev ),
		'dayNamesMin'     => array_values($wp_locale->weekday_initial ),
		'dateFormat'      => $datepicker_date_format,
		'firstDay'        => absint(get_option('start_of_week' ) ),
		'isRTL'           => $wp_locale->is_rtl(),
	) );

	wp_add_inline_script('jquery-ui-datepicker', "jQuery(document).ready(function(jQuery){jQuery.datepicker.setDefaults({$datepicker_defaults});});" );
}




/**
 * Administration Screen CSS for changing the styles.
 *
 * If installing the 'wp-admin/' directory will be replaced with './'.
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
function wp_style_loader_src($src, $handle ){
	global $_wp_admin_css_colors;

	if (wp_installing() )
		return preg_replace('#^wp-admin/#', './', $src );

	if ('colors' == $handle ){
		$color = get_user_option('admin_color');

		if (empty($color) || !isset($_wp_admin_css_colors[$color]) )
			$color = 'fresh';

		$color = $_wp_admin_css_colors[$color];
		$url = $color->url;

		if (!$url ){
			return false;
		}

		$parsed = parse_url($src );
		if (isset($parsed['query']) && $parsed['query'] ){
			wp_parse_str($parsed['query'], $qv );
			$url = add_query_arg($qv, $url );
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

	if (!did_action('wp_print_scripts') ){
		/** This action is documented in wp-includes/functions.wp-scripts.php */
		do_action('wp_print_scripts' );
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
	if (apply_filters('print_head_scripts', true ) ){
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

	if (!($wp_scripts instanceof WP_Scripts ) ){
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
	if (apply_filters('print_footer_scripts', true ) ){
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
	if ($zip && defined('ENFORCE_GZIP') && ENFORCE_GZIP )
		$zip = 'gzip';

	if ($concat = trim($wp_scripts->concat, ', ' ) ){

		if (!empty($wp_scripts->print_code) ){
			echo "\n<script type='text/javascript'>\n";
			echo "/* <![CDATA[ */\n"; // not needed in HTML 5
			echo $wp_scripts->print_code;
			echo "/* ]]> */\n";
			echo "</script>\n";
		}

		$concat = str_split($concat, 128 );
		$concat = 'load%5B%5D=' . implode('&load%5B%5D=', $concat );

		$src = $wp_scripts->base_url . "/wp-admin/load-scripts.php?c={$zip}&" . $concat . '&ver=' . $wp_scripts->default_version;
		echo "<script type='text/javascript' src='" . esc_attr($src) . "'></script>\n";
	}

	if (!empty($wp_scripts->print_html) )
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
	if (!did_action('wp_print_scripts') ){
		/** This action is documented in wp-includes/functions.wp-scripts.php */
		do_action('wp_print_scripts' );
	}

	global $wp_scripts;

	if (!($wp_scripts instanceof WP_Scripts ) ){
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
	do_action('wp_print_footer_scripts' );
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
	do_action('wp_enqueue_scripts' );
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
	if (apply_filters('print_admin_styles', true ) ){
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

	if (!($wp_styles instanceof WP_Styles ) ){
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
	if (apply_filters('print_late_styles', true ) ){
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
	if ($zip && defined('ENFORCE_GZIP') && ENFORCE_GZIP )
		$zip = 'gzip';

	if ($concat = trim($wp_styles->concat, ', ' ) ){
		$dir = $wp_styles->text_direction;
		$ver = $wp_styles->default_version;

		$concat = str_split($concat, 128 );
		$concat = 'load%5B%5D=' . implode('&load%5B%5D=', $concat );

		$href = $wp_styles->base_url . "/wp-admin/load-styles.php?c={$zip}&dir={$dir}&" . $concat . '&ver=' . $ver;
		echo "<link rel='stylesheet' href='" . esc_attr($href) . "' type='text/css' media='all' />\n";

		if (!empty($wp_styles->print_code) ){
			echo "<style type='text/css'>\n";
			echo $wp_styles->print_code;
			echo "\n</style>\n";
		}
	}

	if (!empty($wp_styles->print_html) )
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

	$compressed_output = (ini_get('zlib.output_compression') || 'ob_gzhandler' == ini_get('output_handler') );

	if (!isset($concatenate_scripts) ){
		$concatenate_scripts = defined('CONCATENATE_SCRIPTS') ? CONCATENATE_SCRIPTS : true;
		if ((!is_admin() && !did_action('login_init' ) ) || (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) )
			$concatenate_scripts = false;
	}

	if (!isset($compress_scripts) ){
		$compress_scripts = defined('COMPRESS_SCRIPTS') ? COMPRESS_SCRIPTS : true;
		if ($compress_scripts && (!get_site_option('can_compress_scripts') || $compressed_output ) )
			$compress_scripts = false;
	}

	if (!isset($compress_css) ){
		$compress_css = defined('COMPRESS_CSS') ? COMPRESS_CSS : true;
		if ($compress_css && (!get_site_option('can_compress_scripts') || $compressed_output ) )
			$compress_css = false;
	}
}
