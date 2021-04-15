<?php
/**
 * Media Library administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if( !current_user_can('upload_files') )
	wp_die( __( 'Sorry, you are not allowed to upload files.' ) );

$wp_list_table = _get_list_table('WP_Media_List_Table');
$pagenum = $wp_list_table->get_pagenum();

// Handle bulk actions
$doaction = $wp_list_table->current_action();

if( $doaction ) {
	check_admin_referer('bulk-media');

	if( 'delete_all' == $doaction ) {
		$post_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_status = 'trash'" );
		$doaction = 'delete';
	} elseif( isset( $_REQUEST['media'] ) ) {
		$post_ids = $_REQUEST['media'];
	} elseif( isset( $_REQUEST['ids'] ) ) {
		$post_ids = explode( ',', $_REQUEST['ids'] );
	}

	$location = 'upload.php';
	if( $referer = wp_get_referer() ) {
		if( false !== strpos( $referer, 'upload.php' ) )
			$location = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'message', 'ids', 'posted' ), $referer );
	}

	switch ( $doaction ) {
		case 'detach':
			wp_media_attach_action( $_REQUEST['parent_post_id'], 'detach' );
			break;

		case 'attach':
			wp_media_attach_action( $_REQUEST['found_post_id'] );
			break;

		case 'trash':
			if( !isset( $post_ids ) )
				break;
			foreach( (array) $post_ids as $post_id ) {
				if( !current_user_can( 'delete_post', $post_id ) )
					wp_die( __( 'Sorry, you are not allowed to move this item to the Trash.' ) );

				if( !wp_trash_post( $post_id ) )
					wp_die( __( 'Error in moving to Trash.' ) );
			}
			$location = add_query_arg( array( 'trashed' => count( $post_ids ), 'ids' => join( ',', $post_ids ) ), $location );
			break;
		case 'untrash':
			if( !isset( $post_ids ) )
				break;
			foreach( (array) $post_ids as $post_id ) {
				if( !current_user_can( 'delete_post', $post_id ) )
					wp_die( __( 'Sorry, you are not allowed to restore this item from the Trash.' ) );

				if( !wp_untrash_post( $post_id ) )
					wp_die( __( 'Error in restoring from Trash.' ) );
			}
			$location = add_query_arg( 'untrashed', count( $post_ids ), $location );
			break;
		case 'delete':
			if( !isset( $post_ids ) )
				break;
			foreach( (array) $post_ids as $post_id_del ) {
				if( !current_user_can( 'delete_post', $post_id_del ) )
					wp_die( __( 'Sorry, you are not allowed to delete this item.' ) );

				if( !wp_delete_attachment( $post_id_del ) )
					wp_die( __( 'Error in deleting.' ) );
			}
			$location = add_query_arg( 'deleted', count( $post_ids ), $location );
			break;
		default:
			$location = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $location, $doaction, $post_ids );
	}

	wp_redirect( $location );
	exit;
} elseif( !empty( $_GET['_wp_http_referer'] ) ) {
	 wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
	 exit;
}

$wp_list_table->prepare_items();

$title = __('Media Library');
$parent_file = 'upload.php';

wp_enqueue_script( 'media' );

add_screen_option( 'per_page' );

require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>

<?php
if( current_user_can( 'upload_files' ) ) { ?>
	<a href="<?php echo admin_url( 'media-new.php' ); ?>" class="page-title-action"><?php echo esc_html_x( 'Add New', 'file' ); ?></a><?php
}

if( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
	/* translators: %s: search keywords */
	printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', get_search_query() );
}
?>

<hr class="wp-header-end">

<?php
$message = '';
if( !empty( $_GET['posted'] ) ) {
	$message = __( 'Media file updated.' );
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('posted'), $_SERVER['REQUEST_URI']);
}

if( !empty( $_GET['attached'] ) && $attached = absint( $_GET['attached'] ) ) {
	if( 1 == $attached ) {
		$message = __( 'Media file attached.' );
	} else {
		/* translators: %s: number of media files */
		$message = _n( '%s media file attached.', '%s media files attached.', $attached );
	}
	$message = sprintf( $message, number_format_i18n( $attached ) );
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'detach', 'attached' ), $_SERVER['REQUEST_URI'] );
}

if( !empty( $_GET['detach'] ) && $detached = absint( $_GET['detach'] ) ) {
	if( 1 == $detached ) {
		$message = __( 'Media file detached.' );
	} else {
		/* translators: %s: number of media files */
		$message = _n( '%s media file detached.', '%s media files detached.', $detached );
	}
	$message = sprintf( $message, number_format_i18n( $detached ) );
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'detach', 'attached' ), $_SERVER['REQUEST_URI'] );
}

if( !empty( $_GET['deleted'] ) && $deleted = absint( $_GET['deleted'] ) ) {
	if( 1 == $deleted ) {
		$message = __( 'Media file permanently deleted.' );
	} else {
		/* translators: %s: number of media files */
		$message = _n( '%s media file permanently deleted.', '%s media files permanently deleted.', $deleted );
	}
	$message = sprintf( $message, number_format_i18n( $deleted ) );
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('deleted'), $_SERVER['REQUEST_URI']);
}

if( !empty( $_GET['trashed'] ) && $trashed = absint( $_GET['trashed'] ) ) {
	if( 1 == $trashed ) {
		$message = __( 'Media file moved to the trash.' );
	} else {
		/* translators: %s: number of media files */
		$message = _n( '%s media file moved to the trash.', '%s media files moved to the trash.', $trashed );
	}
	$message = sprintf( $message, number_format_i18n( $trashed ) );
	$message .= ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.(isset($_GET['ids']) ? $_GET['ids'] : ''), "bulk-media" ) ) . '">' . __('Undo') . '</a>';
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('trashed'), $_SERVER['REQUEST_URI']);
}

if( !empty( $_GET['untrashed'] ) && $untrashed = absint( $_GET['untrashed'] ) ) {
	if( 1 == $untrashed ) {
		$message = __( 'Media file restored from the trash.' );
	} else {
		/* translators: %s: number of media files */
		$message = _n( '%s media file restored from the trash.', '%s media files restored from the trash.', $untrashed );
	}
	$message = sprintf( $message, number_format_i18n( $untrashed ) );
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('untrashed'), $_SERVER['REQUEST_URI']);
}

$messages[1] = __( 'Media file updated.' );
$messages[2] = __( 'Media file permanently deleted.' );
$messages[3] = __( 'Error saving media file.' );
$messages[4] = __( 'Media file moved to the trash.' ) . ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.(isset($_GET['ids']) ? $_GET['ids'] : ''), "bulk-media" ) ) . '">' . __( 'Undo' ) . '</a>';
$messages[5] = __( 'Media file restored from the trash.' );

if( !empty( $_GET['message'] ) && isset( $messages[ $_GET['message'] ] ) ) {
	$message = $messages[ $_GET['message'] ];
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
}

if( !empty($message) ) { ?>
<div id="message" class="updated notice is-dismissible"><p><?php echo $message; ?></p></div>
<?php } ?>

<form id="posts-filter" method="get">

<?php $wp_list_table->views(); ?>

<?php $wp_list_table->display(); ?>


<div id="ajax-response"></div>
<?php find_posts_div(); ?>
</form>
</div>

<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );
