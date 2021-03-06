<?php
/**
 * WordPress Upgrade API
 *
 * Most of the functions are pluggable and can be overwritten.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** Include user installation customization script. */
if(file_exists(WP_CONTENT_DIR . '/install.php'))
	require (WP_CONTENT_DIR . '/install.php');

/** WordPress Administration API */
require_once(ABSPATH . 'admin/includes/admin.php');

/** WordPress Schema API */
require_once(ABSPATH . 'admin/includes/schema.php');

if(!function_exists('wp_install')) :
/**
 * Installs the site.
 *
 * Runs the required functions to set up and populate the database,
 * including primary admin user and initial options.
 *
 * @since 2.1.0
 *
 * @param string $blog_title    Site title.
 * @param string $user_name     User's username.
 * @param string $user_email    User's email.
 * @param bool   $public        Whether site is public.
 * @param string $deprecated    Optional. Not used.
 * @param string $user_password Optional. User's chosen password. Default empty (random password).
 * @param string $language      Optional. Language chosen. Default empty.
 * @return array Array keys 'url', 'user_id', 'password', and 'password_message'.
 */
function wp_install($blog_title, $user_name, $user_email, $public, $deprecated = '', $user_password = '', $language = ''){
	if(!empty($deprecated))
		_deprecated_argument(__FUNCTION__, '2.6.0');

	wp_check_mysql_version();
	wp_cache_flush();
	make_db_current_silent();
	populate_options();
	populate_roles();

	update_option('website_title', $blog_title);
	update_option('admin_email', $user_email);
	update_option('blog_public', $public);

	// Freshness of site - in the future, this could get more specific about actions taken, perhaps.
	update_option('fresh_site', 1);

	if($language){
		update_option('WPLANG', $language);
	}

	$guessurl = wp_guess_url();

	update_option('siteurl', $guessurl);

	// If not a public blog, don't ping.
	if(!$public)
		update_option('default_pingback_flag', 0);

	/*
	 * Create default user. If the user already exists, the user tables are
	 * being shared among sites. Just set the role in that case.
	 */
	$user_id = username_exists($user_name);
	$user_password = trim($user_password);
	$email_password = false;
	if(!$user_id && empty($user_password)){
		$user_password = wp_generate_password(12, false);
		$message = __('<strong><em>Note that password</em></strong> carefully!It is a <em>random</em> password that was generated just for you.');
		$user_id = wp_create_user($user_name, $user_password, $user_email);
		update_user_option($user_id, 'default_password_nag', true, true);
		$email_password = true;
	} elseif(!$user_id){
		// Password has been provided
		$message = '<em>'.__('Your chosen password.').'</em>';
		$user_id = wp_create_user($user_name, $user_password, $user_email);
	} else {
		$message = __('User already exists. Password inherited.');
	}

	$user = new WP_User($user_id);
	$user->set_role('administrator');

	wp_install_defaults($user_id);

	wp_install_maybe_enable_pretty_permalinks();

	flush_rewrite_rules();

	wp_new_blog_notification($blog_title, $guessurl, $user_id, ($email_password ? $user_password : __('The password you chose during installation.')));

	wp_cache_flush();

	/**
	 * Fires after a site is fully installed.
	 *
	 * @since 3.9.0
	 *
	 * @param WP_User $user The site owner.
	 */
	do_action('wp_install', $user);

	return array('url' => $guessurl, 'user_id' => $user_id, 'password' => $user_password, 'password_message' => $message);
}
endif;

if(!function_exists('wp_install_defaults')) :
/**
 * Creates the initial content for a newly-installed site.
 *
 * Adds the default "Uncategorized" category, the first post (with comment),
 * first page, and default widgets for default theme for the current version.
 *
 * @since 2.1.0
 *
 * @global wpdb       $wpdb
 * @global WP_Rewrite $wp_rewrite
 * @global string     $table_prefix
 *
 * @param int $user_id User ID.
 */
function wp_install_defaults($user_id){
	global $wpdb, $wp_rewrite, $table_prefix;

	// Default category
	$cat_name = __('Uncategorized');
	/* translators: Default category slug */
	$cat_slug = sanitize_title(_x('Uncategorized', 'Default category slug'));

	if(global_terms_enabled()){
		$cat_id = $wpdb->get_var($wpdb->prepare("SELECT cat_ID FROM {$wpdb->sitecategories} WHERE category_nicename = %s", $cat_slug));
		if($cat_id == null){
			$wpdb->insert($wpdb->sitecategories, array('cat_ID' => 0, 'cat_name' => $cat_name, 'category_nicename' => $cat_slug, 'last_updated' => current_time('mysql', true)));
			$cat_id = $wpdb->insert_id;
		}
		update_option('default_category', $cat_id);
	} else {
		$cat_id = 1;
	}

	$wpdb->insert($wpdb->terms, array('term_id' => $cat_id, 'name' => $cat_name, 'slug' => $cat_slug, 'term_group' => 0));
	$wpdb->insert($wpdb->term_taxonomy, array('term_id' => $cat_id, 'taxonomy' => 'category', 'description' => '', 'parent' => 0, 'count' => 1));
	$cat_tt_id = $wpdb->insert_id;

}
endif;

/**
 * Maybe enable pretty permalinks on installation.
 *
 * If after enabling pretty permalinks don't work, fallback to query-string permalinks.
 *
 * @since 4.2.0
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @return bool Whether pretty permalinks are enabled. False otherwise.
 */
function wp_install_maybe_enable_pretty_permalinks(){
	global $wp_rewrite;

	// Bail if a permalink structure is already enabled.
	if(get_option('permalink_structure')){
		return true;
	}

	/*
	 * The Permalink structures to attempt.
	 *
	 * The first is designed for mod_rewrite or nginx rewriting.
	 *
	 * The second is PATHINFO-based permalinks for web server configurations
	 * without a true rewrite module enabled.
	 */
	$permalink_structures = array(
		'/%year%/%monthnum%/%day%/%postname%/',
		'/index.php/%year%/%monthnum%/%day%/%postname%/'
	);

	foreach((array) $permalink_structures as $permalink_structure){
		$wp_rewrite->set_permalink_structure($permalink_structure);

		/*
	 	 * Flush rules with the hard option to force refresh of the web-server's
	 	 * rewrite config file (e.g. .htaccess or web.config).
	 	 */
		$wp_rewrite->flush_rules(true);

		$test_url = '';

		
		return true;
		
	}

	/*
	 * If it makes it this far, pretty permalinks failed.
	 * Fallback to query-string permalinks.
	 */
	$wp_rewrite->set_permalink_structure('');
	$wp_rewrite->flush_rules(true);

	return false;
}

if(!function_exists('wp_new_blog_notification')) :
/**
 * Notifies the site admin that the setup is complete.
 *
 * Sends an email with wp_mail to the new administrator that the site setup is complete,
 * and provides them with a record of their login credentials.
 *
 * @since 2.1.0
 *
 * @param string $blog_title Site title.
 * @param string $blog_url   Site url.
 * @param int    $user_id    User ID.
 * @param string $password   User's Password.
 */
function wp_new_blog_notification($blog_title, $blog_url, $user_id, $password){
	$user = new WP_User($user_id);
	$email = $user->user_email;
	$name = $user->user_login;
	$login_url = wp_login_url();
	/* translators: New site notification email. 1: New site URL, 2: User login, 3: User password or password reset link, 4: Login URL */
	$message = sprintf(__("Your new WordPress site has been successfully set up at:

%1\$s

You can log in to the administrator account with the following information:

Username: %2\$s
Password: %3\$s
Log in here: %4\$s

We hope you enjoy your new site. Thanks!

--The WordPress Team
https://wordpress.org/
"), $blog_url, $name, $password, $login_url);

	@wp_mail($email, __('New WordPress Site'), $message);
}
endif;

if(!function_exists('wp_upgrade')) :
/**
 * Runs WordPress Upgrade functions.
 *
 * Upgrades the database if needed during a site update.
 *
 * @since 2.1.0
 *
 * @global int  $wp_current_db_version
 * @global int  $wp_db_version
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function wp_upgrade(){
	global $wp_current_db_version, $wp_db_version, $wpdb;

	$wp_current_db_version = __get_option('db_version');

	// We are up-to-date. Nothing to do.
	if($wp_db_version == $wp_current_db_version)
		return;

	if(!is_wp_installed())
		return;

	wp_check_mysql_version();
	wp_cache_flush();
	pre_schema_upgrade();
	make_db_current_silent();
	upgrade_all();
	wp_cache_flush();



	/**
	 * Fires after a site is fully upgraded.
	 *
	 * @since 3.9.0
	 *
	 * @param int $wp_db_version         The new $wp_db_version.
	 * @param int $wp_current_db_version The old (current) $wp_db_version.
	 */
	do_action('wp_upgrade', $wp_db_version, $wp_current_db_version);
}
endif;

/**
 * Functions to be called in installation and upgrade scripts.
 *
 * Contains conditional checks to determine which upgrade scripts to run,
 * based on database version and WP version being updated-to.
 *
 * @ignore
 * @since 1.0.1
 *
 * @global int $wp_current_db_version
 * @global int $wp_db_version
 */
function upgrade_all(){
	global $wp_current_db_version, $wp_db_version;
	$wp_current_db_version = __get_option('db_version');

	return;

}



//
// General functions we use to actually do stuff
//

/**
 * Creates a table in the database if it doesn't already exist.
 *
 * This method checks for an existing database and creates a new one if it's not
 * already present. It doesn't rely on MySQL's "IF NOT EXISTS" statement, but chooses
 * to query all tables first and then run the SQL statement creating the table.
 *
 * @since 1.0.0
 *
 * @global wpdb  $wpdb
 *
 * @param string $table_name Database table name to create.
 * @param string $create_ddl SQL statement to create table.
 * @return bool If table already exists or was created by function.
 */
function maybe_create_table($table_name, $create_ddl){
	global $wpdb;

	$query = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($table_name));

	if($wpdb->get_var($query) == $table_name){
		return true;
	}

	// Didn't find it try to create it..
	$wpdb->query($create_ddl);

	// We cannot directly tell that whether this succeeded!
	if($wpdb->get_var($query) == $table_name){
		return true;
	}
	return false;
}

/**
 * Drops a specified index from a table.
 *
 * @since 1.0.1
 *
 * @global wpdb  $wpdb
 *
 * @param string $table Database table name.
 * @param string $index Index name to drop.
 * @return true True, when finished.
 */
function drop_index($table, $index){
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->query("ALTER TABLE `$table` DROP INDEX `$index`");
	// Now we need to take out all the extra ones we may have created
	for ($i = 0; $i < 25; $i++){
		$wpdb->query("ALTER TABLE `$table` DROP INDEX `{$index}_$i`");
	}
	$wpdb->show_errors();
	return true;
}

/**
 * Adds an index to a specified table.
 *
 * @since 1.0.1
 *
 * @global wpdb  $wpdb
 *
 * @param string $table Database table name.
 * @param string $index Database table index column.
 * @return true True, when done with execution.
 */
function add_clean_index($table, $index){
	global $wpdb;
	drop_index($table, $index);
	$wpdb->query("ALTER TABLE `$table` ADD INDEX (`$index`)");
	return true;
}

/**
 * Adds column to a database table if it doesn't already exist.
 *
 * @since 1.3.0
 *
 * @global wpdb  $wpdb
 *
 * @param string $table_name  The table name to modify.
 * @param string $column_name The column name to add to the table.
 * @param string $create_ddl  The SQL statement used to add the column.
 * @return bool True if already exists or on successful completion, false on error.
 */
function maybe_add_column($table_name, $column_name, $create_ddl){
	global $wpdb;
	foreach($wpdb->get_col("DESC $table_name", 0) as $column){
		if($column == $column_name){
			return true;
		}
	}

	// Didn't find it try to create it.
	$wpdb->query($create_ddl);

	// We cannot directly tell that whether this succeeded!
	foreach($wpdb->get_col("DESC $table_name", 0) as $column){
		if($column == $column_name){
			return true;
		}
	}
	return false;
}

/**
 * If a table only contains utf8 or utf8mb4 columns, convert it to utf8mb4.
 *
 * @since 4.2.0
 *
 * @global wpdb  $wpdb
 *
 * @param string $table The table to convert.
 * @return bool true if the table was converted, false if it wasn't.
 */
function maybe_convert_table_to_utf8mb4($table){
	global $wpdb;

	$results = $wpdb->get_results("SHOW FULL COLUMNS FROM `$table`");
	if(!$results){
		return false;
	}

	foreach($results as $column){
		if($column->Collation){
			list($charset) = explode('_', $column->Collation);
			$charset = strtolower($charset);
			if('utf8' !== $charset && 'utf8mb4' !== $charset){
				// Don't upgrade tables that have non-utf8 columns.
				return false;
			}
		}
	}

	$table_details = $wpdb->get_row("SHOW TABLE STATUS LIKE '$table'");
	if(!$table_details){
		return false;
	}

	list($table_charset) = explode('_', $table_details->Collation);
	$table_charset = strtolower($table_charset);
	if('utf8mb4' === $table_charset){
		return true;
	}

	return $wpdb->query("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

/**
 * Retrieve all options as it was for 1.2.
 *
 * @since 1.2.0
 *
 * @global wpdb  $wpdb
 *
 * @return stdClass List of options.
 */
function get_alloptions_110(){
	global $wpdb;
	$all_options = new stdClass;
	if($options = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options")){
		foreach($options as $option){
			if('siteurl' == $option->option_name || 'home' == $option->option_name || 'category_base' == $option->option_name)
				$option->option_value = untrailingslashit($option->option_value);
			$all_options->{$option->option_name} = stripslashes($option->option_value);
		}
	}
	return $all_options;
}

/**
 * Utility version of get_option that is private to installation/upgrade.
 *
 * @ignore
 * @since 1.5.1
 * @access private
 *
 * @global wpdb  $wpdb
 *
 * @param string $setting Option name.
 * @return mixed
 */
function __get_option($setting){
	global $wpdb;

	if($setting == 'home' && defined('WP_HOME'))
		return untrailingslashit(WP_HOME);

	if($setting == 'siteurl' && defined('WP_SITEURL'))
		return untrailingslashit(WP_SITEURL);

	$option = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", $setting));

	if('home' == $setting && '' == $option)
		return __get_option('siteurl');

	if('siteurl' == $setting || 'home' == $setting || 'category_base' == $setting || 'tag_base' == $setting)
		$option = untrailingslashit($option);

	return maybe_unserialize($option);
}

/**
 * Filters for content to remove unnecessary slashes.
 *
 * @since 1.5.0
 *
 * @param string $content The content to modify.
 * @return string The de-slashed content.
 */
function deslash($content){
	// Note: \\\ inside a regex denotes a single backslash.

	/*
	 * Replace one or more backslashes followed by a single quote with
	 * a single quote.
	 */
	$content = preg_replace("/\\\+'/", "'", $content);

	/*
	 * Replace one or more backslashes followed by a double quote with
	 * a double quote.
	 */
	$content = preg_replace('/\\\+"/', '"', $content);

	// Replace one or more backslashes with one backslash.
	$content = preg_replace("/\\\+/", "\\", $content);

	return $content;
}

/**
 * Modifies the database based on specified SQL statements.
 *
 * Useful for creating new tables and updating existing tables to a new structure.
 *
 * @since 1.5.0
 *
 * @global wpdb  $wpdb
 *
 * @param string|array $queries Optional. The query to run. Can be multiple queries
 *                              in an array, or a string of queries separated by
 *                              semicolons. Default empty.
 * @param bool         $execute Optional. Whether or not to execute the query right away.
 *                              Default true.
 * @return array Strings containing the results of the various update queries.
 */
function dbDelta($queries = '', $execute = true){
	global $wpdb;

	if(in_array($queries, array('', 'all', 'blog', 'global', 'ms_global'), true))
	    $queries = wp_get_db_schema($queries);

	// Separate individual queries into an array
	if(!is_array($queries)){
		$queries = explode(';', $queries);
		$queries = array_filter($queries);
	}

	/**
	 * Filters the dbDelta SQL queries.
	 *
	 * @since 3.3.0
	 *
	 * @param array $queries An array of dbDelta SQL queries.
	 */
	$queries = apply_filters('dbdelta_queries', $queries);

	$cqueries = array(); // Creation Queries
	$iqueries = array(); // Insertion Queries
	$for_update = array();

	// Create a tablename index for an array ($cqueries) of queries
	foreach($queries as $qry){
		if(preg_match("|CREATE TABLE ([^ ]*)|", $qry, $matches)){
			$cqueries[ trim($matches[1], '`') ] = $qry;
			$for_update[$matches[1]] = 'Created table '.$matches[1];
		} elseif(preg_match("|CREATE DATABASE ([^ ]*)|", $qry, $matches)){
			array_unshift($cqueries, $qry);
		} elseif(preg_match("|INSERT INTO ([^ ]*)|", $qry, $matches)){
			$iqueries[] = $qry;
		} elseif(preg_match("|UPDATE ([^ ]*)|", $qry, $matches)){
			$iqueries[] = $qry;
		} else {
			// Unrecognized query type
		}
	}

	/**
	 * Filters the dbDelta SQL queries for creating tables and/or databases.
	 *
	 * Queries filterable via this hook contain "CREATE TABLE" or "CREATE DATABASE".
	 *
	 * @since 3.3.0
	 *
	 * @param array $cqueries An array of dbDelta create SQL queries.
	 */
	$cqueries = apply_filters('dbdelta_create_queries', $cqueries);

	/**
	 * Filters the dbDelta SQL queries for inserting or updating.
	 *
	 * Queries filterable via this hook contain "INSERT INTO" or "UPDATE".
	 *
	 * @since 3.3.0
	 *
	 * @param array $iqueries An array of dbDelta insert or update SQL queries.
	 */
	$iqueries = apply_filters('dbdelta_insert_queries', $iqueries);

	$text_fields = array('tinytext', 'text', 'mediumtext', 'longtext');
	$blob_fields = array('tinyblob', 'blob', 'mediumblob', 'longblob');

	$global_tables = $wpdb->tables('global');
	foreach($cqueries as $table => $qry){
		// Upgrade global tables only for the main site. Don't upgrade at all if conditions are not optimal.
		if(in_array($table, $global_tables) && !wp_should_upgrade_global_tables()){
			unset($cqueries[ $table ], $for_update[ $table ]);
			continue;
		}

		// Fetch the table column structure from the database
		$suppress = $wpdb->suppress_errors();
		$tablefields = $wpdb->get_results("DESCRIBE {$table};");
		$wpdb->suppress_errors($suppress);

		if(!$tablefields)
			continue;

		// Clear the field and index arrays.
		$cfields = $indices = $indices_without_subparts = array();

		// Get all of the field names in the query from between the parentheses.
		preg_match("|\((.*)\)|ms", $qry, $match2);
		$qryline = trim($match2[1]);

		// Separate field lines into an array.
		$flds = explode("\n", $qryline);

		// For every field line specified in the query.
		foreach($flds as $fld){
			$fld = trim($fld, " \t\n\r\0\x0B,"); // Default trim characters, plus ','.

			// Extract the field name.
			preg_match('|^([^ ]*)|', $fld, $fvals);
			$fieldname = trim($fvals[1], '`');
			$fieldname_lowercased = strtolower($fieldname);

			// Verify the found field name.
			$validfield = true;
			switch ($fieldname_lowercased){
				case '':
				case 'primary':
				case 'index':
				case 'fulltext':
				case 'unique':
				case 'key':
				case 'spatial':
					$validfield = false;

					/*
					 * Normalize the index definition.
					 *
					 * This is done so the definition can be compared against the result of a
					 * `SHOW INDEX FROM $table_name` query which returns the current table
					 * index information.
					 */

					// Extract type, name and columns from the definition.
					preg_match(
						  '/^'
						.   '(?P<index_type>'             // 1) Type of the index.
						.       'PRIMARY\s+KEY|(?:UNIQUE|FULLTEXT|SPATIAL)\s+(?:KEY|INDEX)|KEY|INDEX'
						.   ')'
						.   '\s+'                         // Followed by at least one white space character.
						.   '(?:'                         // Name of the index. Optional if type is PRIMARY KEY.
						.       '`?'                      // Name can be escaped with a backtick.
						.           '(?P<index_name>'     // 2) Name of the index.
						.               '(?:[0-9a-zA-Z$_-]|[\xC2-\xDF][\x80-\xBF])+'
						.           ')'
						.       '`?'                      // Name can be escaped with a backtick.
						.       '\s+'                     // Followed by at least one white space character.
						.   ')*'
						.   '\('                          // Opening bracket for the columns.
						.       '(?P<index_columns>'
						.           '.+?'                 // 3) Column names, index prefixes, and orders.
						.       ')'
						.   '\)'                          // Closing bracket for the columns.
						. '$/im',
						$fld,
						$index_matches
					);

					// Uppercase the index type and normalize space characters.
					$index_type = strtoupper(preg_replace('/\s+/', ' ', trim($index_matches['index_type'])));

					// 'INDEX' is a synonym for 'KEY', standardize on 'KEY'.
					$index_type = str_replace('INDEX', 'KEY', $index_type);

					// Escape the index name with backticks. An index for a primary key has no name.
					$index_name = ('PRIMARY KEY' === $index_type) ? '' : '`' . strtolower($index_matches['index_name']) . '`';

					// Parse the columns. Multiple columns are separated by a comma.
					$index_columns = $index_columns_without_subparts = array_map('trim', explode(',', $index_matches['index_columns']));

					// Normalize columns.
					foreach($index_columns as $id => &$index_column){
						// Extract column name and number of indexed characters (sub_part).
						preg_match(
							  '/'
							.   '`?'                      // Name can be escaped with a backtick.
							.       '(?P<column_name>'    // 1) Name of the column.
							.           '(?:[0-9a-zA-Z$_-]|[\xC2-\xDF][\x80-\xBF])+'
							.       ')'
							.   '`?'                      // Name can be escaped with a backtick.
							.   '(?:'                     // Optional sub part.
							.       '\s*'                 // Optional white space character between name and opening bracket.
							.       '\('                  // Opening bracket for the sub part.
							.           '\s*'             // Optional white space character after opening bracket.
							.           '(?P<sub_part>'
							.               '\d+'         // 2) Number of indexed characters.
							.           ')'
							.           '\s*'             // Optional white space character before closing bracket.
							.        '\)'                 // Closing bracket for the sub part.
							.   ')?'
							. '/',
							$index_column,
							$index_column_matches
						);

						// Escape the column name with backticks.
						$index_column = '`' . $index_column_matches['column_name'] . '`';

						// We don't need to add the subpart to $index_columns_without_subparts
						$index_columns_without_subparts[ $id ] = $index_column;

						// Append the optional sup part with the number of indexed characters.
						if(isset($index_column_matches['sub_part'])){
							$index_column .= '(' . $index_column_matches['sub_part'] . ')';
						}
					}

					// Build the normalized index definition and add it to the list of indices.
					$indices[] = "{$index_type} {$index_name} (" . implode(',', $index_columns) . ")";
					$indices_without_subparts[] = "{$index_type} {$index_name} (" . implode(',', $index_columns_without_subparts) . ")";

					// Destroy no longer needed variables.
					unset($index_column, $index_column_matches, $index_matches, $index_type, $index_name, $index_columns, $index_columns_without_subparts);

					break;
			}

			// If it's a valid field, add it to the field array.
			if($validfield){
				$cfields[ $fieldname_lowercased ] = $fld;
			}
		}

		// For every field in the table.
		foreach($tablefields as $tablefield){
			$tablefield_field_lowercased = strtolower($tablefield->Field);
			$tablefield_type_lowercased = strtolower($tablefield->Type);

			// If the table field exists in the field array ...
			if(array_key_exists($tablefield_field_lowercased, $cfields)){

				// Get the field type from the query.
				preg_match('|`?' . $tablefield->Field . '`? ([^ ]*(unsigned)?)|i', $cfields[ $tablefield_field_lowercased ], $matches);
				$fieldtype = $matches[1];
				$fieldtype_lowercased = strtolower($fieldtype);

				// Is actual field type different from the field type in query?
				if($tablefield->Type != $fieldtype){
					$do_change = true;
					if(in_array($fieldtype_lowercased, $text_fields) && in_array($tablefield_type_lowercased, $text_fields)){
						if(array_search($fieldtype_lowercased, $text_fields) < array_search($tablefield_type_lowercased, $text_fields)){
							$do_change = false;
						}
					}

					if(in_array($fieldtype_lowercased, $blob_fields) && in_array($tablefield_type_lowercased, $blob_fields)){
						if(array_search($fieldtype_lowercased, $blob_fields) < array_search($tablefield_type_lowercased, $blob_fields)){
							$do_change = false;
						}
					}

					if($do_change){
						// Add a query to change the column type.
						$cqueries[] = "ALTER TABLE {$table} CHANGE COLUMN `{$tablefield->Field}` " . $cfields[ $tablefield_field_lowercased ];
						$for_update[$table.'.'.$tablefield->Field] = "Changed type of {$table}.{$tablefield->Field} from {$tablefield->Type} to {$fieldtype}";
					}
				}

				// Get the default value from the array.
				if(preg_match("| DEFAULT '(.*?)'|i", $cfields[ $tablefield_field_lowercased ], $matches)){
					$default_value = $matches[1];
					if($tablefield->Default != $default_value){
						// Add a query to change the column's default value
						$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN `{$tablefield->Field}` SET DEFAULT '{$default_value}'";
						$for_update[$table.'.'.$tablefield->Field] = "Changed default value of {$table}.{$tablefield->Field} from {$tablefield->Default} to {$default_value}";
					}
				}

				// Remove the field from the array (so it's not added).
				unset($cfields[ $tablefield_field_lowercased ]);
			} else {
				// This field exists in the table, but not in the creation queries?
			}
		}

		// For every remaining field specified for the table.
		foreach($cfields as $fieldname => $fielddef){
			// Push a query line into $cqueries that adds the field to that table.
			$cqueries[] = "ALTER TABLE {$table} ADD COLUMN $fielddef";
			$for_update[$table.'.'.$fieldname] = 'Added column '.$table.'.'.$fieldname;
		}

		// Index stuff goes here. Fetch the table index structure from the database.
		$tableindices = $wpdb->get_results("SHOW INDEX FROM {$table};");

		if($tableindices){
			// Clear the index array.
			$index_ary = array();

			// For every index in the table.
			foreach($tableindices as $tableindex){

				// Add the index to the index data array.
				$keyname = strtolower($tableindex->Key_name);
				$index_ary[$keyname]['columns'][] = array('fieldname' => $tableindex->Column_name, 'subpart' => $tableindex->Sub_part);
				$index_ary[$keyname]['unique'] = ($tableindex->Non_unique == 0)?true:false;
				$index_ary[$keyname]['index_type'] = $tableindex->Index_type;
			}

			// For each actual index in the index array.
			foreach($index_ary as $index_name => $index_data){

				// Build a create string to compare to the query.
				$index_string = '';
				if($index_name == 'primary'){
					$index_string .= 'PRIMARY ';
				} elseif($index_data['unique']){
					$index_string .= 'UNIQUE ';
				}
				if('FULLTEXT' === strtoupper($index_data['index_type'])){
					$index_string .= 'FULLTEXT ';
				}
				if('SPATIAL' === strtoupper($index_data['index_type'])){
					$index_string .= 'SPATIAL ';
				}
				$index_string .= 'KEY ';
				if('primary' !== $index_name){
					$index_string .= '`' . $index_name . '`';
				}
				$index_columns = '';

				// For each column in the index.
				foreach($index_data['columns'] as $column_data){
					if($index_columns != ''){
						$index_columns .= ',';
					}

					// Add the field to the column list string.
					$index_columns .= '`' . $column_data['fieldname'] . '`';
				}

				// Add the column list to the index create string.
				$index_string .= " ($index_columns)";

				// Check if the index definition exists, ignoring subparts.
				if(!(($aindex = array_search($index_string, $indices_without_subparts)) === false)){
					// If the index already exists (even with different subparts), we don't need to create it.
					unset($indices_without_subparts[ $aindex ]);
					unset($indices[ $aindex ]);
				}
			}
		}

		// For every remaining index specified for the table.
		foreach((array) $indices as $index){
			// Push a query line into $cqueries that adds the index to that table.
			$cqueries[] = "ALTER TABLE {$table} ADD $index";
			$for_update[] = 'Added index ' . $table . ' ' . $index;
		}

		// Remove the original table creation query from processing.
		unset($cqueries[ $table ], $for_update[ $table ]);
	}

	$allqueries = array_merge($cqueries, $iqueries);
	if($execute){
		foreach($allqueries as $query){
			$wpdb->query($query);
		}
	}

	return $for_update;
}

/**
 * Updates the database tables to a new schema.
 *
 * By default, updates all the tables to use the latest defined schema, but can also
 * be used to update a specific set of tables in wp_get_db_schema().
 *
 * @since 1.5.0
 *
 * @uses dbDelta
 *
 * @param string $tables Optional. Which set of tables to update. Default is 'all'.
 */
function make_db_current($tables = 'all'){
	$alterations = dbDelta($tables);
	echo "<ol>\n";
	foreach($alterations as $alteration) echo "<li>$alteration</li>\n";
	echo "</ol>\n";
}

/**
 * Updates the database tables to a new schema, but without displaying results.
 *
 * By default, updates all the tables to use the latest defined schema, but can
 * also be used to update a specific set of tables in wp_get_db_schema().
 *
 * @since 1.5.0
 *
 * @see make_db_current()
 *
 * @param string $tables Optional. Which set of tables to update. Default is 'all'.
 */
function make_db_current_silent($tables = 'all'){
	dbDelta($tables);
}



/**
 * Creates a site theme from the default theme.
 *
 * {@internal Missing Long Description}}
 *
 * @since 1.5.0
 *
 * @param string $theme_name The name of the theme.
 * @param string $template   The directory name of the theme.
 * @return false|void
 */
function make_site_theme_from_default($theme_name, $template){
	$site_dir = WP_CONTENT_DIR . "/themes/$template";
	$default_dir = WP_CONTENT_DIR . '/themes/' . WP_DEFAULT_THEME;

	// Copy files from the default theme to the site theme.
	//$files = array('index.php', 'footer.php', 'header.php', 'sidebar.php', 'style.css');

	$theme_dir = @ opendir($default_dir);
	if($theme_dir){
		while(($theme_file = readdir($theme_dir)) !== false){
			if(is_dir("$default_dir/$theme_file"))
				continue;
			if(!@copy("$default_dir/$theme_file", "$site_dir/$theme_file"))
				return;
			chmod("$site_dir/$theme_file", 0777);
		}
	}
	@closedir($theme_dir);

	// Rewrite the theme header.
	$stylelines = explode("\n", implode('', file("$site_dir/style.css")));
	if($stylelines){
		$f = fopen("$site_dir/style.css", 'w');

		foreach($stylelines as $line){
			if(strpos($line, 'Theme Name:') !== false) $line = 'Theme Name: ' . $theme_name;
			elseif(strpos($line, 'Theme URI:') !== false) $line = 'Theme URI: ' . __get_option('url');
			elseif(strpos($line, 'Description:') !== false) $line = 'Description: Your theme.';
			elseif(strpos($line, 'Version:') !== false) $line = 'Version: 1';
			elseif(strpos($line, 'Author:') !== false) $line = 'Author: You';
			fwrite($f, $line . "\n");
		}
		fclose($f);
	}

	// Copy the images.
	umask(0);
	if(!mkdir("$site_dir/images", 0777)){
		return false;
	}

	$images_dir = @ opendir("$default_dir/images");
	if($images_dir){
		while(($image = readdir($images_dir)) !== false){
			if(is_dir("$default_dir/images/$image"))
				continue;
			if(!@copy("$default_dir/images/$image", "$site_dir/images/$image"))
				return;
			chmod("$site_dir/images/$image", 0777);
		}
	}
	@closedir($images_dir);
}

/**
 * Creates a site theme.
 *
 * {@internal Missing Long Description}}
 *
 * @since 1.5.0
 *
 * @return false|string
 */
function make_site_theme(){
	// Name the theme after the blog.
	$theme_name = __get_option('website_title');
	$template = sanitize_title($theme_name);
	$site_dir = WP_CONTENT_DIR . "/themes/$template";

	// If the theme already exists, nothing to do.
	if(is_dir($site_dir)){
		return false;
	}

	// We must be able to write to the themes dir.
	if(!is_writable(WP_CONTENT_DIR . "/themes")){
		return false;
	}

	umask(0);
	if(!mkdir($site_dir, 0777)){
		return false;
	}

	if(file_exists(ABSPATH . 'wp-layout.css')){
		if(!make_site_theme_from_oldschool($theme_name, $template)){
			// TODO: rm -rf the site theme directory.
			return false;
		}
	} else {
		if(!make_site_theme_from_default($theme_name, $template))
			// TODO: rm -rf the site theme directory.
			return false;
	}

	// Make the new site theme active.
	$current_template = __get_option('template');
	if($current_template == WP_DEFAULT_THEME){
		update_option('template', $template);
		update_option('stylesheet', $template);
	}
	return $template;
}

/**
 * Translate user level to user role name.
 *
 * @since 2.0.0
 *
 * @param int $level User level.
 * @return string User role name.
 */
function translate_level_to_role($level){
	switch ($level){
	case 10:
	case 9:
	case 8:
		return 'administrator';
	case 7:
	case 6:
	case 5:
		return 'editor';
	case 4:
	case 3:
	case 2:
		return 'author';
	case 1:
		return 'contributor';
	case 0:
		return 'subscriber';
	}
}

/**
 * Checks the version of the installed MySQL binary.
 *
 * @since 2.1.0
 *
 * @global wpdb  $wpdb
 */
function wp_check_mysql_version(){
	global $wpdb;
	$result = $wpdb->check_database_version();
	if(is_wp_error($result))
		die($result->get_error_message());
}

/**
 * Disables the Automattic widgets plugin, which was merged into core.
 *
 * @since 2.2.0
 */
function maybe_disable_automattic_widgets(){
	$plugins = __get_option('active_plugins');

	foreach((array) $plugins as $plugin){
		if(basename($plugin) == 'widgets.php'){
			array_splice($plugins, array_search($plugin, $plugins), 1);
			update_option('active_plugins', $plugins);
			break;
		}
	}
}

/**
 * Disables the Link Manager on upgrade if, at the time of upgrade, no links exist in the DB.
 *
 * @since 3.5.0
 *
 * @global int  $wp_current_db_version
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function maybe_disable_link_manager(){
	global $wp_current_db_version, $wpdb;

	if($wp_current_db_version >= 22006 && get_option('link_manager_enabled') && !$wpdb->get_var("SELECT link_id FROM $wpdb->links LIMIT 1"))
		update_option('link_manager_enabled', 0);
}

/**
 * Runs before the schema is upgraded.
 *
 * @since 2.9.0
 *
 * @global int  $wp_current_db_version
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function pre_schema_upgrade(){
	global $wp_current_db_version, $wpdb;

	// Upgrade versions prior to 2.9
	if($wp_current_db_version < 11557){
		// Delete duplicate options. Keep the option with the highest option_id.
		$wpdb->query("DELETE o1 FROM $wpdb->options AS o1 JOIN $wpdb->options AS o2 USING (`option_name`) WHERE o2.option_id > o1.option_id");

		// Drop the old primary key and add the new.
		$wpdb->query("ALTER TABLE $wpdb->options DROP PRIMARY KEY, ADD PRIMARY KEY(option_id)");

		// Drop the old option_name index. dbDelta() doesn't do the drop.
		$wpdb->query("ALTER TABLE $wpdb->options DROP INDEX option_name");
	}

	

	// Upgrade versions prior to 4.2.
	if($wp_current_db_version < 31351){
			$wpdb->query("ALTER TABLE $wpdb->usermeta DROP INDEX meta_key, ADD INDEX meta_key(meta_key(191))");
		
		$wpdb->query("ALTER TABLE $wpdb->terms DROP INDEX slug, ADD INDEX slug(slug(191))");
		$wpdb->query("ALTER TABLE $wpdb->terms DROP INDEX name, ADD INDEX name(name(191))");
		$wpdb->query("ALTER TABLE $wpdb->commentmeta DROP INDEX meta_key, ADD INDEX meta_key(meta_key(191))");
		$wpdb->query("ALTER TABLE $wpdb->postmeta DROP INDEX meta_key, ADD INDEX meta_key(meta_key(191))");
		$wpdb->query("ALTER TABLE $wpdb->posts DROP INDEX post_name, ADD INDEX post_name(post_name(191))");
	}

	// Upgrade versions prior to 4.4.
	if($wp_current_db_version < 34978){
		// If compatible termmeta table is found, use it, but enforce a proper index and update collation.
		if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->termmeta}'") && $wpdb->get_results("SHOW INDEX FROM {$wpdb->termmeta} WHERE Column_name = 'meta_key'")){
			$wpdb->query("ALTER TABLE $wpdb->termmeta DROP INDEX meta_key, ADD INDEX meta_key(meta_key(191))");
			maybe_convert_table_to_utf8mb4($wpdb->termmeta);
		}
	}
}

if(!function_exists('install_global_terms')) :
/**
 * Install global terms.
 *
 * @since 3.0.0
 *
 * @global wpdb   $wpdb
 * @global string $charset_collate
 */
function install_global_terms(){
	global $wpdb, $charset_collate;
	$ms_queries = "
CREATE TABLE $wpdb->sitecategories (
  cat_ID bigint(20) NOT NULL auto_increment,
  cat_name varchar(55) NOT NULL default '',
  category_nicename varchar(200) NOT NULL default '',
  last_updated timestamp NOT NULL,
  PRIMARY KEY  (cat_ID),
  KEY category_nicename (category_nicename),
  KEY last_updated (last_updated)
) $charset_collate;
";
// now create tables
	dbDelta($ms_queries);
}
endif;

/**
 * Determine if global tables should be upgraded.
 *
 * This function performs a series of checks to ensure the environment allows
 * for the safe upgrading of global WordPress database tables. It is necessary
 * because global tables will commonly grow to millions of rows on large
 * installations, and the ability to control their upgrade routines can be
 * critical to the operation of large networks.
 *
 * In a future iteration, this function may use `wp_is_large_network()` to more-
 * intelligently prevent global table upgrades. Until then, we make sure
 * WordPress is on the main site of the main network, to avoid running queries
 * more than once in multi-site or multi-network environments.
 *
 * @since 4.3.0
 *
 * @return bool Whether to run the upgrade routines on global tables.
 */
function wp_should_upgrade_global_tables(){

	// Return false early if explicitly not upgrading
	if(defined('DO_NOT_UPGRADE_GLOBAL_TABLES')){
		return false;
	}

	// Assume global tables should be upgraded
	$should_upgrade = true;

	// Set to false if not on main network (does not matter if not multi-network)
	if(!is_main_network()){
		$should_upgrade = false;
	}

	// Set to false if not on main site of current network (does not matter if not multi-site)
	if(!is_main_site()){
		$should_upgrade = false;
	}

	/**
	 * Filters if upgrade routines should be run on global tables.
	 *
	 * @param bool $should_upgrade Whether to run the upgrade routines on global tables.
	 */
	return apply_filters('wp_should_upgrade_global_tables', $should_upgrade);
}
