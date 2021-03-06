<?php
/**
 * Helper functions for displaying a list of items in an clean HTML table.
 *
 * @package WordPress
 * @subpackage List_Table
 * @since 3.1.0
 */

/**
 * Fetch an instance of a WP_List_Table class.
 *
 * @access private
 * @since 3.1.0
 *
 * @global string $hook_suffix
 *
 * @param string $class The type of the list table, which is the class name.
 * @param array $args Optional. Arguments to pass to the class. Accepts 'screen'.
 * @return object|bool Object on success, false if the class does not exist.
 */
function _get_list_table( $class, $args = array()) {
	$core_classes = array(
		'WP_Posts_List_Table' => 'posts',
		'WP_Terms_List_Table' => 'terms',
		'WP_Users_List_Table' => 'users',
		'WP_Plugins_List_Table' => 'plugins',
	);

	if( isset( $core_classes[ $class ])) {
		foreach( (array) $core_classes[$class] as $required){
			require_once( ABSPATH . 'admin/includes/class-wp-' . $required . '-list-table.php');
		}

		if( isset( $args['screen']))
			$args['screen'] = convert_to_screen( $args['screen']);
		elseif( isset( $GLOBALS['hook_suffix']))
			$args['screen'] = get_current_screen();
		else
			$args['screen'] = null;

		return new $class( $args);
	}

	return false;
}

/**
 * Register column headers for a particular screen.
 *
 * @since 2.7.0
 *
 * @param string $screen The handle for the screen to add help to. This is usually the hook name returned by the add_*_page() functions.
 * @param array $columns An array of columns with column IDs as the keys and translated column names as the values
 * @see get_column_headers(), print_column_headers(), get_hidden_columns()
 */
function register_column_headers($screen, $columns) {
	new _WP_List_Table_Compat( $screen, $columns);
}

/**
 * Prints column headers for a particular screen.
 *
 * @since 2.7.0
 *
 * @param string|WP_Screen $screen  The screen hook name or screen object.
 * @param bool             $with_id Whether to set the id attribute or not.
 */
function print_column_headers( $screen, $with_id = true) {
	$wp_list_table = new _WP_List_Table_Compat($screen);

	$wp_list_table->print_column_headers( $with_id);
}
