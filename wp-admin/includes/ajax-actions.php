<?php
/**
 * Administration API: Core Ajax handlers
 *
 * @package WordPress
 * @subpackage Administration
 * @since 2.1.0
 */

//
// No-privilege Ajax handlers.
//

/**
 * Ajax handler for the Heartbeat API in
 * the no-privilege context.
 *
 * Runs when the user is not logged in.
 *
 * @since 3.6.0
 */
function wp_ajax_nopriv_heartbeat() {
	$response = array();

	// screen_id is the same as $current_screen->id and the JS global 'pagenow'.
	if( !empty($_POST['screen_id']) )
		$screen_id = sanitize_key($_POST['screen_id']);
	else
		$screen_id = 'front';

	if( !empty($_POST['data']) ) {
		$data = wp_unslash( (array) $_POST['data'] );

		/**
		 * Filters Heartbeat Ajax response in no-privilege environments.
		 *
		 * @since 3.6.0
		 *
		 * @param array|object $response  The no-priv Heartbeat response object or array.
		 * @param array        $data      An array of data passed via $_POST.
		 * @param string       $screen_id The screen id.
		 */
		$response = apply_filters( 'heartbeat_nopriv_received', $response, $data, $screen_id );
	}

	/**
	 * Filters Heartbeat Ajax response when no data is passed.
	 *
	 * @since 3.6.0
	 *
	 * @param array|object $response  The Heartbeat response object or array.
	 * @param string       $screen_id The screen id.
	 */
	$response = apply_filters( 'heartbeat_nopriv_send', $response, $screen_id );

	/**
	 * Fires when Heartbeat ticks in no-privilege environments.
	 *
	 * Allows the transport to be easily replaced with long-polling.
	 *
	 * @since 3.6.0
	 *
	 * @param array|object $response  The no-priv Heartbeat response.
	 * @param string       $screen_id The screen id.
	 */
	do_action( 'heartbeat_nopriv_tick', $response, $screen_id );

	// Send the current time according to the server.
	$response['server_time'] = time();

	wp_send_json($response);
}

//
// GET-based Ajax handlers.
//

/**
 * Ajax handler for fetching a list table.
 *
 * @since 3.1.0
 */
function wp_ajax_fetch_list() {
	$list_class = $_GET['list_args']['class'];
	check_ajax_referer( "fetch-list-$list_class", '_ajax_fetch_list_nonce' );

	$wp_list_table = _get_list_table( $list_class, array( 'screen' => $_GET['list_args']['screen']['id'] ) );
	if( !$wp_list_table ) {
		wp_die( 0 );
	}

	if( !$wp_list_table->ajax_user_can() ) {
		wp_die( -1 );
	}

	$wp_list_table->ajax_response();

	wp_die( 0 );
}

/**
 * Ajax handler for tag search.
 *
 * @since 3.1.0
 */
function wp_ajax_ajax_tag_search() {
	if( !isset( $_GET['tax'] ) ) {
		wp_die( 0 );
	}

	$taxonomy = sanitize_key( $_GET['tax'] );
	$tax = get_taxonomy( $taxonomy );
	if( !$tax ) {
		wp_die( 0 );
	}

	if( !current_user_can( $tax->cap->assign_terms ) ) {
		wp_die( -1 );
	}

	$s = wp_unslash( $_GET['q'] );

	$comma = _x( ',', 'tag delimiter' );
	if( ',' !== $comma )
		$s = str_replace( $comma, ',', $s );
	if( false !== strpos( $s, ',' ) ) {
		$s = explode( ',', $s );
		$s = $s[count( $s ) - 1];
	}
	$s = trim( $s );

	/**
	 * Filters the minimum number of characters required to fire a tag search via Ajax.
	 *
	 * @since 4.0.0
	 *
	 * @param int         $characters The minimum number of characters required. Default 2.
	 * @param WP_Taxonomy $tax        The taxonomy object.
	 * @param string      $s          The search term.
	 */
	$term_search_min_chars = (int) apply_filters( 'term_search_min_chars', 2, $tax, $s );

	/*
	 * Require $term_search_min_chars chars for matching (default: 2)
	 * ensure it's a non-negative, non-zero integer.
	 */
	if( ( $term_search_min_chars == 0 ) || ( strlen( $s ) < $term_search_min_chars ) ){
		wp_die();
	}

	$results = get_terms( $taxonomy, array( 'name__like' => $s, 'fields' => 'names', 'hide_empty' => false ) );

	echo join( $results, "\n" );
	wp_die();
}

/**
 * Ajax handler for compression testing.
 *
 * @since 3.1.0
 */
function wp_ajax_wp_compression_test() {
	if( !current_user_can( 'manage_options' ) )
		wp_die( -1 );

	if( ini_get('zlib.output_compression') || 'ob_gzhandler' == ini_get('output_handler') ) {
		update_site_option('can_compress_scripts', 0);
		wp_die( 0 );
	}

	if( isset($_GET['test']) ) {
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
		header('Content-Type: application/javascript; charset=UTF-8');
		$force_gzip = ( defined('ENFORCE_GZIP') && ENFORCE_GZIP );
		$test_str = '"wpCompressionTest Lorem ipsum dolor sit amet consectetuer mollis sapien urna ut a. Eu nonummy condimentum fringilla tempor pretium platea vel nibh netus Maecenas. Hac molestie amet justo quis pellentesque est ultrices interdum nibh Morbi. Cras mattis pretium Phasellus ante ipsum ipsum ut sociis Suspendisse Lorem. Ante et non molestie. Porta urna Vestibulum egestas id congue nibh eu risus gravida sit. Ac augue auctor Ut et non a elit massa id sodales. Elit eu Nulla at nibh adipiscing mattis lacus mauris at tempus. Netus nibh quis suscipit nec feugiat eget sed lorem et urna. Pellentesque lacus at ut massa consectetuer ligula ut auctor semper Pellentesque. Ut metus massa nibh quam Curabitur molestie nec mauris congue. Volutpat molestie elit justo facilisis neque ac risus Ut nascetur tristique. Vitae sit lorem tellus et quis Phasellus lacus tincidunt nunc Fusce. Pharetra wisi Suspendisse mus sagittis libero lacinia Integer consequat ac Phasellus. Et urna ac cursus tortor aliquam Aliquam amet tellus volutpat Vestibulum. Justo interdum condimentum In augue congue tellus sollicitudin Quisque quis nibh."';

		 if( 1 == $_GET['test'] ) {
		 	echo $test_str;
		 	wp_die();
		 } elseif( 2 == $_GET['test'] ) {
			if( !isset($_SERVER['HTTP_ACCEPT_ENCODING']) )
				wp_die( -1 );
			if( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') && function_exists('gzdeflate') && !$force_gzip ) { 
				header('Content-Encoding: deflate');
				$out = gzdeflate( $test_str, 1 );
			} elseif( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('gzencode') ) { 
				header('Content-Encoding: gzip');
				$out = gzencode( $test_str, 1 );
			} else {
				wp_die( -1 );
			}
			echo $out;
			wp_die();
		} elseif( 'no' == $_GET['test'] ) {
			check_ajax_referer( 'update_can_compress_scripts' );
			update_site_option('can_compress_scripts', 0);
		} elseif( 'yes' == $_GET['test'] ) {
			check_ajax_referer( 'update_can_compress_scripts' );
			update_site_option('can_compress_scripts', 1);
		}
	}

	wp_die( 0 );
}





/**
 * Ajax handler for dashboard widgets.
 *
 * @since 3.4.0
 */
function wp_ajax_dashboard_widgets() {
	require_once ABSPATH . 'wp-admin/includes/dashboard.php';

	$pagenow = $_GET['pagenow'];
	if( $pagenow === 'dashboard-user' || $pagenow === 'dashboard-network' || $pagenow === 'dashboard' ) {
		set_current_screen( $pagenow );
	}

	switch ( $_GET['widget'] ) {
		case 'dashboard_primary' :
			wp_dashboard_primary();
			break;
	}
	wp_die();
}

/**
 * Ajax handler for Customizer preview logged-in status.
 *
 * @since 3.4.0
 */
function wp_ajax_logged_in() {
	wp_die( 1 );
}

//
// Ajax helpers.
//



//
// POST-based Ajax handlers.
//

/**
 * Ajax handler for adding a hierarchical term.
 *
 * @access private
 * @since 3.1.0
 */
function _wp_ajax_add_hierarchical_term() {
	$action = $_POST['action'];
	$taxonomy = get_taxonomy(substr($action, 4));
	check_ajax_referer( $action, '_ajax_nonce-add-' . $taxonomy->name );
	if( !current_user_can( $taxonomy->cap->edit_terms ) )
		wp_die( -1 );
	$names = explode(',', $_POST['new'.$taxonomy->name]);
	$parent = isset($_POST['new'.$taxonomy->name.'_parent']) ? (int) $_POST['new'.$taxonomy->name.'_parent'] : 0;
	if( 0 > $parent )
		$parent = 0;
	if( $taxonomy->name == 'category' )
		$post_category = isset($_POST['post_category']) ? (array) $_POST['post_category'] : array();
	else
		$post_category = ( isset($_POST['tax_input']) && isset($_POST['tax_input'][$taxonomy->name]) ) ? (array) $_POST['tax_input'][$taxonomy->name] : array();
	$checked_categories = array_map( 'absint', (array) $post_category );
	$popular_ids = wp_popular_terms_checklist($taxonomy->name, 0, 10, false);

	foreach( $names as $cat_name ) {
		$cat_name = trim($cat_name);
		$category_nicename = sanitize_title($cat_name);
		if( '' === $category_nicename )
			continue;

		$cat_id = wp_insert_term( $cat_name, $taxonomy->name, array( 'parent' => $parent ) );
		if( !$cat_id || is_wp_error( $cat_id ) ) {
			continue;
		} else {
			$cat_id = $cat_id['term_id'];
		}
		$checked_categories[] = $cat_id;
		if( $parent ) // Do these all at once in a second
			continue;

		ob_start();

		wp_terms_checklist( 0, array( 'taxonomy' => $taxonomy->name, 'descendants_and_self' => $cat_id, 'selected_cats' => $checked_categories, 'popular_cats' => $popular_ids ));

		$data = ob_get_clean();

		$add = array(
			'what' => $taxonomy->name,
			'id' => $cat_id,
			'data' => str_replace( array("\n", "\t"), '', $data),
			'position' => -1
		);
	}

	if( $parent ) { // Foncy - replace the parent and all its children
		$parent = get_term( $parent, $taxonomy->name );
		$term_id = $parent->term_id;

		while ( $parent->parent ) { // get the top parent
			$parent = get_term( $parent->parent, $taxonomy->name );
			if( is_wp_error( $parent ) )
				break;
			$term_id = $parent->term_id;
		}

		ob_start();

		wp_terms_checklist( 0, array('taxonomy' => $taxonomy->name, 'descendants_and_self' => $term_id, 'selected_cats' => $checked_categories, 'popular_cats' => $popular_ids));

		$data = ob_get_clean();

		$add = array(
			'what' => $taxonomy->name,
			'id' => $term_id,
			'data' => str_replace( array("\n", "\t"), '', $data),
			'position' => -1
		);
	}

	ob_start();

	wp_dropdown_categories( array(
		'taxonomy' => $taxonomy->name, 'hide_empty' => 0, 'name' => 'new'.$taxonomy->name.'_parent', 'orderby' => 'name',
		'hierarchical' => 1, 'show_option_none' => '&mdash; '.$taxonomy->labels->parent_item.' &mdash;'
	) );

	$sup = ob_get_clean();

	$add['supplemental'] = array( 'newcat_parent' => $sup );

	$x = new WP_Ajax_Response( $add );
	$x->send();
}



/**
 * Ajax handler for deleting a tag.
 *
 * @since 3.1.0
 */
function wp_ajax_delete_tag() {
	$tag_id = (int) $_POST['tag_ID'];
	check_ajax_referer( "delete-tag_$tag_id" );

	if( !current_user_can( 'delete_term', $tag_id ) ) {
		wp_die( -1 );
	}

	$taxonomy = !empty($_POST['taxonomy']) ? $_POST['taxonomy'] : 'post_tag';
	$tag = get_term( $tag_id, $taxonomy );
	if( !$tag || is_wp_error( $tag ) )
		wp_die( 1 );

	if( wp_delete_term($tag_id, $taxonomy))
		wp_die( 1 );
	else
		wp_die( 0 );
}



/**
 * Ajax handler for deleting meta.
 *
 * @since 3.1.0
 */
function wp_ajax_delete_meta() {
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	check_ajax_referer( "delete-meta_$id" );
	if( !$meta = get_metadata_by_mid( 'post', $id ) )
		wp_die( 1 );

	if( is_protected_meta( $meta->meta_key, 'post' ) || !current_user_can( 'delete_post_meta',  $meta->post_id, $meta->meta_key ) )
		wp_die( -1 );
	if( delete_meta( $meta->meta_id ) )
		wp_die( 1 );
	wp_die( 0 );
}

/**
 * Ajax handler for deleting a post.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_delete_post( $action ) {
	if( empty( $action ) )
		$action = 'delete-post';
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	check_ajax_referer( "{$action}_$id" );
	if( !current_user_can( 'delete_post', $id ) )
		wp_die( -1 );

	if( !get_post( $id ) )
		wp_die( 1 );

	if( wp_delete_post( $id ) )
		wp_die( 1 );
	else
		wp_die( 0 );
}

/**
 * Ajax handler for sending a post to the trash.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_trash_post( $action ) {
	if( empty( $action ) )
		$action = 'trash-post';
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	check_ajax_referer( "{$action}_$id" );
	if( !current_user_can( 'delete_post', $id ) )
		wp_die( -1 );

	if( !get_post( $id ) )
		wp_die( 1 );

	if( 'trash-post' == $action )
		$done = wp_trash_post( $id );
	else
		$done = wp_untrash_post( $id );

	if( $done )
		wp_die( 1 );

	wp_die( 0 );
}

/**
 * Ajax handler to restore a post from the trash.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_untrash_post( $action ) {
	if( empty( $action ) )
		$action = 'untrash-post';
	wp_ajax_trash_post( $action );
}

/**
 * @since 3.1.0
 *
 * @param string $action
 */
function wp_ajax_delete_page( $action ) {
	if( empty( $action ) )
		$action = 'delete-page';
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	check_ajax_referer( "{$action}_$id" );
	if( !current_user_can( 'delete_page', $id ) )
		wp_die( -1 );

	if( !get_post( $id ) )
		wp_die( 1 );

	if( wp_delete_post( $id ) )
		wp_die( 1 );
	else
		wp_die( 0 );
}



/**
 * Ajax handler to add a tag.
 *
 * @since 3.1.0
 */
function wp_ajax_add_tag() {
	check_ajax_referer( 'add-tag', '_wpnonce_add-tag' );
	$taxonomy = !empty($_POST['taxonomy']) ? $_POST['taxonomy'] : 'post_tag';
	$tax = get_taxonomy($taxonomy);

	if( !current_user_can( $tax->cap->edit_terms ) )
		wp_die( -1 );

	$x = new WP_Ajax_Response();

	$tag = wp_insert_term($_POST['tag-name'], $taxonomy, $_POST );

	if( !$tag || is_wp_error($tag) || (!$tag = get_term( $tag['term_id'], $taxonomy )) ) {
		$message = __('An error has occurred. Please reload the page and try again.');
		if( is_wp_error($tag) && $tag->get_error_message() )
			$message = $tag->get_error_message();

		$x->add( array(
			'what' => 'taxonomy',
			'data' => new WP_Error('error', $message )
		) );
		$x->send();
	}

	$wp_list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => $_POST['screen'] ) );

	$level = 0;
	if( is_taxonomy_hierarchical($taxonomy) ) {
		$level = count( get_ancestors( $tag->term_id, $taxonomy, 'taxonomy' ) );
		ob_start();
		$wp_list_table->single_row( $tag, $level );
		$noparents = ob_get_clean();
	}

	ob_start();
	$wp_list_table->single_row( $tag );
	$parents = ob_get_clean();

	$x->add( array(
		'what' => 'taxonomy',
		'supplemental' => compact('parents', 'noparents')
	) );
	$x->add( array(
		'what' => 'term',
		'position' => $level,
		'supplemental' => (array) $tag
	) );
	$x->send();
}

/**
 * Ajax handler for getting a tagcloud.
 *
 * @since 3.1.0
 */
function wp_ajax_get_tagcloud() {
	if( !isset( $_POST['tax'] ) ) {
		wp_die( 0 );
	}

	$taxonomy = sanitize_key( $_POST['tax'] );
	$tax = get_taxonomy( $taxonomy );
	if( !$tax ) {
		wp_die( 0 );
	}

	if( !current_user_can( $tax->cap->assign_terms ) ) {
		wp_die( -1 );
	}

	$tags = get_terms( $taxonomy, array( 'number' => 45, 'orderby' => 'count', 'order' => 'DESC' ) );

	if( empty( $tags ) )
		wp_die( $tax->labels->not_found );

	if( is_wp_error( $tags ) )
		wp_die( $tags->get_error_message() );

	foreach( $tags as $key => $tag ) {
		$tags[ $key ]->link = '#';
		$tags[ $key ]->id = $tag->term_id;
	}

	// We need raw tag names here, so don't filter the output
	$return = wp_generate_tag_cloud( $tags, array( 'filter' => 0, 'format' => 'list' ) );

	if( empty($return) )
		wp_die( 0 );

	echo $return;

	wp_die();
}





/**
 * Ajax handler for adding meta.
 *
 * @since 3.1.0
 */
function wp_ajax_add_meta() {
	check_ajax_referer( 'add-meta', '_ajax_nonce-add-meta' );
	$c = 0;
	$pid = (int) $_POST['post_id'];
	$post = get_post( $pid );

	if( isset($_POST['metakeyselect']) || isset($_POST['metakeyinput']) ) {
		if( !current_user_can( 'edit_post', $pid ) )
			wp_die( -1 );
		if( isset($_POST['metakeyselect']) && '#NONE#' == $_POST['metakeyselect'] && empty($_POST['metakeyinput']) )
			wp_die( 1 );

		// If the post is an autodraft, save the post as a draft and then attempt to save the meta.
		if( $post->post_status == 'auto-draft' ) {
			$post_data = array();
			$post_data['action'] = 'draft'; // Warning fix
			$post_data['post_ID'] = $pid;
			$post_data['post_type'] = $post->post_type;
			$post_data['post_status'] = 'draft';
			$now = current_time('timestamp', 1);
			/* translators: 1: Post creation date, 2: Post creation time */
			$post_data['post_title'] = sprintf( __( 'Draft created on %1$s at %2$s' ), date( __( 'F j, Y' ), $now ), date( __( 'g:i a' ), $now ) );

			$pid = edit_post( $post_data );
			if( $pid ) {
				if( is_wp_error( $pid ) ) {
					$x = new WP_Ajax_Response( array(
						'what' => 'meta',
						'data' => $pid
					) );
					$x->send();
				}

				if( !$mid = add_meta( $pid ) )
					wp_die( __( 'Please provide a custom field value.' ) );
			} else {
				wp_die( 0 );
			}
		} elseif( !$mid = add_meta( $pid ) ) {
			wp_die( __( 'Please provide a custom field value.' ) );
		}

		$meta = get_metadata_by_mid( 'post', $mid );
		$pid = (int) $meta->post_id;
		$meta = get_object_vars( $meta );
		$x = new WP_Ajax_Response( array(
			'what' => 'meta',
			'id' => $mid,
			'data' => _list_meta_row( $meta, $c ),
			'position' => 1,
			'supplemental' => array('postid' => $pid)
		) );
	} else { // Update?
		$mid = (int) key( $_POST['meta'] );
		$key = wp_unslash( $_POST['meta'][$mid]['key'] );
		$value = wp_unslash( $_POST['meta'][$mid]['value'] );
		if( '' == trim($key) )
			wp_die( __( 'Please provide a custom field name.' ) );
		if( '' == trim($value) )
			wp_die( __( 'Please provide a custom field value.' ) );
		if( !$meta = get_metadata_by_mid( 'post', $mid ) )
			wp_die( 0 ); // if meta doesn't exist
		if( is_protected_meta( $meta->meta_key, 'post' ) || is_protected_meta( $key, 'post' ) ||
			!current_user_can( 'edit_post_meta', $meta->post_id, $meta->meta_key ) ||
			!current_user_can( 'edit_post_meta', $meta->post_id, $key ) )
			wp_die( -1 );
		if( $meta->meta_value != $value || $meta->meta_key != $key ) {
			if( !$u = update_metadata_by_mid( 'post', $mid, $value, $key ) )
				wp_die( 0 ); // We know meta exists; we also know it's unchanged (or DB error, in which case there are bigger problems).
		}

		$x = new WP_Ajax_Response( array(
			'what' => 'meta',
			'id' => $mid, 'old_id' => $mid,
			'data' => _list_meta_row( array(
				'meta_key' => $key,
				'meta_value' => $value,
				'meta_id' => $mid
			), $c ),
			'position' => 0,
			'supplemental' => array('postid' => $meta->post_id)
		) );
	}
	$x->send();
}

/**
 * Ajax handler for adding a user.
 *
 * @since 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_add_user( $action ) {
	if( empty( $action ) ) {
		$action = 'add-user';
	}

	check_ajax_referer( $action );
	if( !current_user_can('create_users') )
		wp_die( -1 );
	if( !$user_id = edit_user() ) {
		wp_die( 0 );
	} elseif( is_wp_error( $user_id ) ) {
		$x = new WP_Ajax_Response( array(
			'what' => 'user',
			'id' => $user_id
		) );
		$x->send();
	}
	$user_object = get_userdata( $user_id );

	$wp_list_table = _get_list_table('WP_Users_List_Table');

	$role = current( $user_object->roles );

	$x = new WP_Ajax_Response( array(
		'what' => 'user',
		'id' => $user_id,
		'data' => $wp_list_table->single_row( $user_object, '', $role ),
		'supplemental' => array(
			'show-link' => sprintf(
				/* translators: %s: the new user */
				__( 'User %s added' ),
				'<a href="#user-' . $user_id . '">' . $user_object->user_login . '</a>'
			),
			'role' => $role,
		)
	) );
	$x->send();
}

/**
 * Ajax handler for closed post boxes.
 *
 * @since 3.1.0
 */
function wp_ajax_closed_postboxes() {
	check_ajax_referer( 'closedpostboxes', 'closedpostboxesnonce' );
	$closed = isset( $_POST['closed'] ) ? explode( ',', $_POST['closed']) : array();
	$closed = array_filter($closed);

	$hidden = isset( $_POST['hidden'] ) ? explode( ',', $_POST['hidden']) : array();
	$hidden = array_filter($hidden);

	$page = isset( $_POST['page'] ) ? $_POST['page'] : '';

	if( $page != sanitize_key( $page ) )
		wp_die( 0 );

	if( !$user = wp_get_current_user() )
		wp_die( -1 );

	if( is_array($closed) )
		update_user_option($user->ID, "closedpostboxes_$page", $closed, true);

	if( is_array($hidden) ) {
		$hidden = array_diff( $hidden, array('submitdiv', 'linksubmitdiv', 'manage-menu', 'create-menu') ); // postboxes that are always shown
		update_user_option($user->ID, "metaboxhidden_$page", $hidden, true);
	}

	wp_die( 1 );
}

/**
 * Ajax handler for hidden columns.
 *
 * @since 3.1.0
 */
function wp_ajax_hidden_columns() {
	check_ajax_referer( 'screen-options-nonce', 'screenoptionnonce' );
	$page = isset( $_POST['page'] ) ? $_POST['page'] : '';

	if( $page != sanitize_key( $page ) )
		wp_die( 0 );

	if( !$user = wp_get_current_user() )
		wp_die( -1 );

	$hidden = !empty( $_POST['hidden'] ) ? explode( ',', $_POST['hidden'] ) : array();
	update_user_option( $user->ID, "manage{$page}columnshidden", $hidden, true );

	wp_die( 1 );
}






/**
 * Ajax handler for saving the meta box order.
 *
 * @since 3.1.0
 */
function wp_ajax_meta_box_order() {
	check_ajax_referer( 'meta-box-order' );
	$order = isset( $_POST['order'] ) ? (array) $_POST['order'] : false;
	$page_columns = isset( $_POST['page_columns'] ) ? $_POST['page_columns'] : 'auto';

	if( $page_columns != 'auto' )
		$page_columns = (int) $page_columns;

	$page = isset( $_POST['page'] ) ? $_POST['page'] : '';

	if( $page != sanitize_key( $page ) )
		wp_die( 0 );

	if( !$user = wp_get_current_user() )
		wp_die( -1 );

	if( $order )
		update_user_option($user->ID, "meta-box-order_$page", $order, true);

	if( $page_columns )
		update_user_option($user->ID, "screen_layout_$page", $page_columns, true);

	wp_die( 1 );
}



/**
 * Ajax handler to retrieve a permalink.
 *
 * @since 3.1.0
 */
function wp_ajax_get_permalink() {
	check_ajax_referer( 'getpermalink', 'getpermalinknonce' );
	$post_id = isset($_POST['post_id'])? intval($_POST['post_id']) : 0;
	wp_die( get_preview_post_link( $post_id ) );
}

/**
 * Ajax handler to retrieve a sample permalink.
 *
 * @since 3.1.0
 */
function wp_ajax_sample_permalink() {
	check_ajax_referer( 'samplepermalink', 'samplepermalinknonce' );
	$post_id = isset($_POST['post_id'])? intval($_POST['post_id']) : 0;
	$title = isset($_POST['new_title'])? $_POST['new_title'] : '';
	$slug = isset($_POST['new_slug'])? $_POST['new_slug'] : null;
	wp_die( get_sample_permalink_html( $post_id, $title, $slug ) );
}

/**
 * Ajax handler for Quick Edit saving a post from a list table.
 *
 * @since 3.1.0
 *
 * @global string $mode List table view mode.
 */
function wp_ajax_inline_save() {
	global $mode;

	check_ajax_referer( 'inlineeditnonce', '_inline_edit' );

	if( !isset($_POST['post_ID']) || !( $post_ID = (int) $_POST['post_ID'] ) )
		wp_die();

	if( 'page' == $_POST['post_type'] ) {
		if( !current_user_can( 'edit_page', $post_ID ) )
			wp_die( __( 'Sorry, you are not allowed to edit this page.' ) );
	} else {
		if( !current_user_can( 'edit_post', $post_ID ) )
			wp_die( __( 'Sorry, you are not allowed to edit this post.' ) );
	}

	if( $last = wp_check_post_lock( $post_ID ) ) {
		$last_user = get_userdata( $last );
		$last_user_name = $last_user ? $last_user->display_name : __( 'Someone' );
		printf( $_POST['post_type'] == 'page' ? __( 'Saving is disabled: %s is currently editing this page.' ) : __( 'Saving is disabled: %s is currently editing this post.' ),	esc_html( $last_user_name ) );
		wp_die();
	}

	$data = &$_POST;

	$post = get_post( $post_ID, ARRAY_A );

	// Since it's coming from the database.
	$post = wp_slash($post);

	$data['content'] = $post['post_content'];
	$data['excerpt'] = $post['post_excerpt'];

	// Rename.
	$data['user_ID'] = get_current_user_id();

	if( isset($data['post_parent']) )
		$data['parent_id'] = $data['post_parent'];

	// Status.
	if( isset( $data['keep_private'] ) && 'private' == $data['keep_private'] ) {
		$data['visibility']  = 'private';
		$data['post_status'] = 'private';
	} else {
		$data['post_status'] = $data['_status'];
	}


	// Exclude terms from taxonomies that are not supposed to appear in Quick Edit.
	if( !empty( $data['tax_input'] ) ) {
		foreach( $data['tax_input'] as $taxonomy => $terms ) {
			$tax_object = get_taxonomy( $taxonomy );
			/** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
			if( !apply_filters( 'quick_edit_show_taxonomy', $tax_object->show_in_quick_edit, $taxonomy, $post['post_type'] ) ) {
				unset( $data['tax_input'][ $taxonomy ] );
			}
		}
	}

	// Hack: wp_unique_post_slug() doesn't work for drafts, so we will fake that our post is published.
	if( !empty( $data['post_name'] ) && in_array( $post['post_status'], array( 'draft', 'pending' ) ) ) {
		$post['post_status'] = 'publish';
		$data['post_name'] = wp_unique_post_slug( $data['post_name'], $post['ID'], $post['post_status'], $post['post_type'], $post['post_parent'] );
	}

	// Update the post.
	edit_post();

	$wp_list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => $_POST['screen'] ) );

	$mode = $_POST['post_view'] === 'excerpt' ? 'excerpt' : 'list';

	$level = 0;
	if( is_post_type_hierarchical( $wp_list_table->screen->post_type ) ) {
		$request_post = array( get_post( $_POST['post_ID'] ) );
		$parent       = $request_post[0]->post_parent;

		while ( $parent > 0 ) {
			$parent_post = get_post( $parent );
			$parent      = $parent_post->post_parent;
			$level++;
		}
	}

	$wp_list_table->display_rows( array( get_post( $_POST['post_ID'] ) ), $level );

	wp_die();
}

/**
 * Ajax handler for quick edit saving for a term.
 *
 * @since 3.1.0
 */
function wp_ajax_inline_save_tax() {
	check_ajax_referer( 'taxinlineeditnonce', '_inline_edit' );

	$taxonomy = sanitize_key( $_POST['taxonomy'] );
	$tax = get_taxonomy( $taxonomy );
	if( !$tax )
		wp_die( 0 );

	if( !isset( $_POST['tax_ID'] ) || !( $id = (int) $_POST['tax_ID'] ) ) {
		wp_die( -1 );
	}

	if( !current_user_can( 'edit_term', $id ) ) {
		wp_die( -1 );
	}

	$wp_list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-' . $taxonomy ) );

	$tag = get_term( $id, $taxonomy );
	$_POST['description'] = $tag->description;

	$updated = wp_update_term($id, $taxonomy, $_POST);
	if( $updated && !is_wp_error($updated) ) {
		$tag = get_term( $updated['term_id'], $taxonomy );
		if( !$tag || is_wp_error( $tag ) ) {
			if( is_wp_error($tag) && $tag->get_error_message() )
				wp_die( $tag->get_error_message() );
			wp_die( __( 'Item not updated.' ) );
		}
	} else {
		if( is_wp_error($updated) && $updated->get_error_message() )
			wp_die( $updated->get_error_message() );
		wp_die( __( 'Item not updated.' ) );
	}
	$level = 0;
	$parent = $tag->parent;
	while ( $parent > 0 ) {
		$parent_tag = get_term( $parent, $taxonomy );
		$parent = $parent_tag->parent;
		$level++;
	}
	$wp_list_table->single_row( $tag, $level );
	wp_die();
}

/**
 * Ajax handler for querying posts for the Find Posts modal.
 *
 * @see window.findPosts
 *
 * @since 3.1.0
 */
function wp_ajax_find_posts() {
	check_ajax_referer( 'find-posts' );

	$post_types = get_post_types( array( 'public' => true ), 'objects' );
	unset( $post_types['attachment'] );

	$s = wp_unslash( $_POST['ps'] );
	$args = array(
		'post_type' => array_keys( $post_types ),
		'post_status' => 'any',
		'posts_per_page' => 50,
	);
	if( '' !== $s )
		$args['s'] = $s;

	$posts = get_posts( $args );

	if( !$posts ) {
		wp_send_json_error( __( 'No items found.' ) );
	}

	$html = '<table class="widefat"><thead><tr><th class="found-radio"><br /></th><th>'.__('Title').'</th><th class="no-break">'.__('Type').'</th><th class="no-break">'.__('Date').'</th><th class="no-break">'.__('Status').'</th></tr></thead><tbody>';
	$alt = '';
	foreach( $posts as $post ) {
		$title = trim( $post->post_title ) ? $post->post_title : __( '(no title)' );
		$alt = ( 'alternate' == $alt ) ? '' : 'alternate';

		switch ( $post->post_status ) {
			case 'publish' :
			case 'private' :
				$stat = __('Published');
				break;
			case 'future' :
				$stat = __('Scheduled');
				break;
			case 'pending' :
				$stat = __('Pending Review');
				break;
			case 'draft' :
				$stat = __('Draft');
				break;
		}

		if( '0000-00-00 00:00:00' == $post->post_date ) {
			$time = '';
		} else {
			/* translators: date format in table columns, see https://secure.php.net/date */
			$time = mysql2date(__('Y/m/d'), $post->post_date);
		}

		$html .= '<tr class="' . trim( 'found-posts ' . $alt ) . '"><td class="found-radio"><input type="radio" id="found-'.$post->ID.'" name="found_post_id" value="' . esc_attr($post->ID) . '"></td>';
		$html .= '<td><label for="found-'.$post->ID.'">' . esc_html( $title ) . '</label></td><td class="no-break">' . esc_html( $post_types[$post->post_type]->labels->singular_name ) . '</td><td class="no-break">'.esc_html( $time ) . '</td><td class="no-break">' . esc_html( $stat ). ' </td></tr>' . "\n\n";
	}

	$html .= '</tbody></table>';

	wp_send_json_success( $html );
}


/**
 * Ajax handler for date formatting.
 *
 * @since 3.1.0
 */
function wp_ajax_date_format() {
	wp_die( date_i18n( sanitize_option( 'date_format', wp_unslash( $_POST['date'] ) ) ) );
}

/**
 * Ajax handler for time formatting.
 *
 * @since 3.1.0
 */
function wp_ajax_time_format() {
	wp_die( date_i18n( sanitize_option( 'time_format', wp_unslash( $_POST['date'] ) ) ) );
}



/**
 * Ajax handler for removing a post lock.
 *
 * @since 3.1.0
 */
function wp_ajax_wp_remove_post_lock() {
	if( empty( $_POST['post_ID'] ) || empty( $_POST['active_post_lock'] ) )
		wp_die( 0 );
	$post_id = (int) $_POST['post_ID'];
	if( !$post = get_post( $post_id ) )
		wp_die( 0 );

	check_ajax_referer( 'update-post_' . $post_id );

	if( !current_user_can( 'edit_post', $post_id ) )
		wp_die( -1 );

	$active_lock = array_map( 'absint', explode( ':', $_POST['active_post_lock'] ) );
	if( $active_lock[1] != get_current_user_id() )
		wp_die( 0 );

	/**
	 * Filters the post lock window duration.
	 *
	 * @since 3.3.0
	 *
	 * @param int $interval The interval in seconds the post lock duration
	 *                      should last, plus 5 seconds. Default 150.
	 */
	$new_lock = ( time() - apply_filters( 'wp_check_post_lock_window', 150 ) + 5 ) . ':' . $active_lock[1];
	update_post_meta( $post_id, '_edit_lock', $new_lock, implode( ':', $active_lock ) );
	wp_die( 1 );
}

/**
 * Ajax handler for dismissing a WordPress pointer.
 *
 * @since 3.1.0
 */
function wp_ajax_dismiss_wp_pointer() {
	$pointer = $_POST['pointer'];
	if( $pointer != sanitize_key( $pointer ) )
		wp_die( 0 );

//	check_ajax_referer( 'dismiss-pointer_' . $pointer );

	$dismissed = array_filter( explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) ) );

	if( in_array( $pointer, $dismissed ) )
		wp_die( 0 );

	$dismissed[] = $pointer;
	$dismissed = implode( ',', $dismissed );

	update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', $dismissed );
	wp_die( 1 );
}


/**
 * Ajax handler for the Heartbeat API.
 *
 * Runs when the user is logged in.
 *
 * @since 3.6.0
 */
function wp_ajax_heartbeat() {
	if( empty( $_POST['_nonce'] ) ) {
		wp_send_json_error();
	}

	$response = $data = array();
	$nonce_state = wp_verify_nonce( $_POST['_nonce'], 'heartbeat-nonce' );

	// screen_id is the same as $current_screen->id and the JS global 'pagenow'.
	if( !empty( $_POST['screen_id'] ) ) {
		$screen_id = sanitize_key($_POST['screen_id']);
	} else {
		$screen_id = 'front';
	}

	if( !empty( $_POST['data'] ) ) {
		$data = wp_unslash( (array) $_POST['data'] );
	}

	if( 1 !== $nonce_state ) {
		$response = apply_filters( 'wp_refresh_nonces', $response, $data, $screen_id );

		if( false === $nonce_state ) {
			// User is logged in but nonces have expired.
			$response['nonces_expired'] = true;
			wp_send_json( $response );
		}
	}

	if( !empty( $data ) ) {
		/**
		 * Filters the Heartbeat response received.
		 *
		 * @since 3.6.0
		 *
		 * @param array  $response  The Heartbeat response.
		 * @param array  $data      The $_POST data sent.
		 * @param string $screen_id The screen id.
		 */
		$response = apply_filters( 'heartbeat_received', $response, $data, $screen_id );
	}

	/**
	 * Filters the Heartbeat response sent.
	 *
	 * @since 3.6.0
	 *
	 * @param array  $response  The Heartbeat response.
	 * @param string $screen_id The screen id.
	 */
	$response = apply_filters( 'heartbeat_send', $response, $screen_id );

	/**
	 * Fires when Heartbeat ticks in logged-in environments.
	 *
	 * Allows the transport to be easily replaced with long-polling.
	 *
	 * @since 3.6.0
	 *
	 * @param array  $response  The Heartbeat response.
	 * @param string $screen_id The screen id.
	 */
	do_action( 'heartbeat_tick', $response, $screen_id );

	// Send the current time according to the server
	$response['server_time'] = time();

	wp_send_json( $response );
}






/**
 * Ajax handler for destroying multiple open sessions for a user.
 *
 * @since 4.1.0
 */
function wp_ajax_destroy_sessions() {
	$user = get_userdata( (int) $_POST['user_id'] );
	if( $user ) {
		if( !current_user_can( 'edit_user', $user->ID ) ) {
			$user = false;
		} elseif( !wp_verify_nonce( $_POST['nonce'], 'update-user_' . $user->ID ) ) {
			$user = false;
		}
	}

	if( !$user ) {
		wp_send_json_error( array(
			'message' => __( 'Could not log out user sessions. Please try again.' ),
		) );
	}

	$sessions = WP_Session_Tokens::get_instance( $user->ID );

	if( $user->ID === get_current_user_id() ) {
		$sessions->destroy_others( wp_get_session_token() );
		$message = __( 'You are now logged out everywhere else.' );
	} else {
		$sessions->destroy_all();
		/* translators: %s: User's display name. */
		$message = sprintf( __( '%s has been logged out.' ), $user->display_name );
	}

	wp_send_json_success( array( 'message' => $message ) );
}



/**
 * Ajax handler for generating a password.
 *
 * @since 4.4.0
 */
function wp_ajax_generate_password() {
	wp_send_json_success( wp_generate_password( 24 ) );
}

/**
 * Ajax handler for saving the user's WordPress.org username.
 *
 * @since 4.4.0
 */
function wp_ajax_save_wporg_username() {
	if( !current_user_can( 'install_themes' ) && !current_user_can( 'install_plugins' ) ) {
		wp_send_json_error();
	}

	check_ajax_referer( 'save_wporg_username_' . get_current_user_id() );

	$username = isset( $_REQUEST['username'] ) ? wp_unslash( $_REQUEST['username'] ) : false;

	if( !$username ) {
		wp_send_json_error();
	}

	wp_send_json_success( update_user_meta( get_current_user_id(), 'wporg_favorites', $username ) );
}





/**
 * Ajax handler for searching plugins.
 *
 * @since 4.6.0
 *
 * @global string $s Search term.
 */
function wp_ajax_search_plugins() {
	check_ajax_referer( 'updates' );

	$pagenow = isset( $_POST['pagenow'] ) ? sanitize_key( $_POST['pagenow'] ) : '';
	if( 'plugins-network' === $pagenow || 'plugins' === $pagenow ) {
		set_current_screen( $pagenow );
	}

	/** @var WP_Plugins_List_Table $wp_list_table */
	$wp_list_table = _get_list_table( 'WP_Plugins_List_Table', array(
		'screen' => get_current_screen(),
	) );

	$status = array();

	if( !$wp_list_table->ajax_user_can() ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to manage plugins for this site.' );
		wp_send_json_error( $status );
	}

	// Set the correct requester, so pagination works.
	$_SERVER['REQUEST_URI'] = add_query_arg( array_diff_key( $_POST, array(
		'_ajax_nonce' => null,
		'action'      => null,
	) ), network_admin_url( 'plugins.php', 'relative' ) );

	$GLOBALS['s'] = wp_unslash( $_POST['s'] );

	$wp_list_table->prepare_items();

	ob_start();
	$wp_list_table->display();
	$status['count'] = count( $wp_list_table->items );
	$status['items'] = ob_get_clean();

	wp_send_json_success( $status );
}
