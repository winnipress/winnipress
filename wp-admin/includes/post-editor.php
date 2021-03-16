<?php

// The Admin Post editor
global $post_type, $post_type_object, $post;


$post_ID = isset($post_ID) ? (int) $post_ID : 0;
$user_ID = isset($user_ID) ? (int) $user_ID : 0;
$action = isset($action) ? $action : '';

if ( $post_ID == get_option( 'page_for_posts' ) && empty( $post->post_content ) ) {
	add_action( 'edit_form_after_title', '_wp_posts_page_notice' );
	remove_post_type_support( $post_type, 'editor' );
}

$thumbnail_support = current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' );
if ( !$thumbnail_support && 'attachment' === $post_type && $post->post_mime_type ) {
	if ( wp_attachment_is( 'audio', $post ) ) {
		$thumbnail_support = post_type_supports( 'attachment:audio', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:audio' );
	} elseif ( wp_attachment_is( 'video', $post ) ) {
		$thumbnail_support = post_type_supports( 'attachment:video', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:video' );
	}
}

if ( $thumbnail_support ) {
	wp_enqueue_media( array( 'post' => $post_ID ) );
}



/*
 * @todo Document the $messages array(s).
 */
$permalink = get_permalink( $post_ID );
if ( !$permalink ) {
	$permalink = '';
}

$messages = array();

$preview_post_link_html = $scheduled_post_link_html = $view_post_link_html = '';
$preview_page_link_html = $scheduled_page_link_html = $view_page_link_html = '';

$preview_url = get_preview_post_link( $post );

$viewable = is_post_type_viewable( $post_type_object );

if ( $viewable ) {

	// Preview post link.
	$preview_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $preview_url ),
		__( 'Preview post' )
	);

	// Scheduled post preview link.
	$scheduled_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'Preview post' )
	);

	// View post link.
	$view_post_link_html = sprintf( ' <a href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'View post' )
	);

	// Preview page link.
	$preview_page_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $preview_url ),
		__( 'Preview page' )
	);

	// Scheduled page preview link.
	$scheduled_page_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'Preview page' )
	);

	// View page link.
	$view_page_link_html = sprintf( ' <a href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'View page' )
	);

}

/* translators: Publish box date format, see https://secure.php.net/date */
$scheduled_date = date_i18n( __( 'M j, Y @ H:i' ), strtotime( $post->post_date ) );

$messages['post'] = array(
	 0 => '', // Unused. Messages start at index 1.
	 1 => __( 'Post updated.' ) . $view_post_link_html,
	 2 => __( 'Custom field updated.' ),
	 3 => __( 'Custom field deleted.' ),
	 4 => __( 'Post updated.' ),
	 6 => __( 'Post published.' ) . $view_post_link_html,
	 7 => __( 'Post saved.' ),
	 8 => __( 'Post submitted.' ) . $preview_post_link_html,
	 9 => sprintf( __( 'Post scheduled for: %s.' ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_post_link_html,
	10 => __( 'Post draft updated.' ) . $preview_post_link_html,
);
$messages['page'] = array(
	 0 => '', // Unused. Messages start at index 1.
	 1 => __( 'Page updated.' ) . $view_page_link_html,
	 2 => __( 'Custom field updated.' ),
	 3 => __( 'Custom field deleted.' ),
	 4 => __( 'Page updated.' ),
	 6 => __( 'Page published.' ) . $view_page_link_html,
	 7 => __( 'Page saved.' ),
	 8 => __( 'Page submitted.' ) . $preview_page_link_html,
	 9 => sprintf( __( 'Page scheduled for: %s.' ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_page_link_html,
	10 => __( 'Page draft updated.' ) . $preview_page_link_html,
);
$messages['attachment'] = array_fill( 1, 10, __( 'Media file updated.' ) ); // Hack, for now.

/**
 * Filters the post updated messages.
 *
 * @since 3.0.0
 *
 * @param array $messages Post updated messages. For defaults @see $messages declarations above.
 */
$messages = apply_filters( 'post_updated_messages', $messages );

$message = false;
if ( isset($_GET['message']) ) {
	$_GET['message'] = absint( $_GET['message'] );
	if ( isset($messages[$post_type][$_GET['message']]) )
		$message = $messages[$post_type][$_GET['message']];
	elseif ( !isset($messages[$post_type]) && isset($messages['post'][$_GET['message']]) )
		$message = $messages['post'][$_GET['message']];
}

$notice = false;
$form_action = 'editpost';
$nonce_action = 'update-post_' . $post_ID;



$post_type_object = get_post_type_object($post_type);

// All meta boxes should be defined and added before the first do_meta_boxes() call (or potentially during the do_meta_boxes action).
require_once( ABSPATH . 'wp-admin/includes/meta-boxes.php' );


$publish_callback_args = null;


add_meta_box( 'submitdiv', __( 'Publish' ), 'post_submit_meta_box', null, 'side', 'core', $publish_callback_args );

// all taxonomies
foreach ( get_object_taxonomies( $post ) as $tax_name ) {
	$taxonomy = get_taxonomy( $tax_name );
	if ( !$taxonomy->show_ui || false === $taxonomy->meta_box_cb )
		continue;

	$label = $taxonomy->labels->name;

	if ( !is_taxonomy_hierarchical( $tax_name ) )
		$tax_meta_box_id = 'tagsdiv-' . $tax_name;
	else
		$tax_meta_box_id = $tax_name . 'div';

	add_meta_box( $tax_meta_box_id, $label, $taxonomy->meta_box_cb, null, 'side', 'core', array( 'taxonomy' => $tax_name ) );
}

if ( post_type_supports( $post_type, 'page-attributes' ) || count( get_page_templates( $post ) ) > 0 ) {
	add_meta_box( 'pageparentdiv', $post_type_object->labels->attributes, 'page_attributes_meta_box', null, 'side', 'core' );
}

if ( $thumbnail_support && current_user_can( 'upload_files' ) )
	add_meta_box('postimagediv', esc_html( $post_type_object->labels->featured_image ), 'post_thumbnail_meta_box', null, 'side', 'low');

if ( post_type_supports($post_type, 'excerpt') )
	add_meta_box('postexcerpt', __('Excerpt'), 'post_excerpt_meta_box', null, 'normal', 'core');


if ( post_type_supports($post_type, 'custom-fields') )
	add_meta_box('postcustom', __('Custom Fields'), 'post_custom_meta_box', null, 'normal', 'core');

/**
 * Fires in the middle of built-in meta box registration.
 *
 * @since 2.1.0
 * @deprecated 3.7.0 Use 'add_meta_boxes' instead.
 *
 * @param WP_Post $post Post object.
 */
do_action( 'dbx_post_advanced', $post );


$stati = get_post_stati( array( 'public' => true ) );
if ( empty( $stati ) ) {
	$stati = array( 'publish' );
}
$stati[] = 'private';


if ( !( 'pending' == get_post_status( $post ) && !current_user_can( $post_type_object->cap->publish_posts ) ) ){
	add_meta_box('slugdiv', __('Slug'), 'post_slug_meta_box', null, 'normal', 'core');
}

if ( post_type_supports( $post_type, 'author' ) && current_user_can( $post_type_object->cap->edit_others_posts ) ) {
	add_meta_box( 'authordiv', __( 'Author' ), 'post_author_meta_box', null, 'normal', 'core' );
}

// Fires after all built-in meta boxes have been added
do_action( 'add_meta_boxes', $post_type, $post );

// Fires after all built-in meta boxes have been added, contextually for the given post type
do_action( "add_meta_boxes_{$post_type}", $post );

// Fires after meta boxes have been added, once for each of the default meta box contexts: normal, advanced, and side
do_action( 'do_meta_boxes', $post_type, 'normal', $post );
do_action( 'do_meta_boxes', $post_type, 'advanced', $post );
do_action( 'do_meta_boxes', $post_type, 'side', $post );

add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );

require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h1 class="wp-heading-inline"><?php
echo esc_html( $title );
?></h1>

<?php
if ( isset( $post_new_file ) && current_user_can( $post_type_object->cap->create_posts ) ) {
	echo ' <a href="' . esc_url( admin_url( $post_new_file ) ) . '" class="page-title-action">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
}
?>

<hr class="wp-header-end">

<?php
if($notice){
?>
<div id="notice" class="notice notice-warning"><p id="has-newer-autosave"><?php echo $notice ?></p></div>
<?php
}
?>

<?php
if($message){
?>
<div id="message" class="updated notice notice-success is-dismissible"><p><?php echo $message; ?></p></div>
<?php
}
?>

<form name="post" action="post.php" method="post" id="post"<?php
//Fires inside the post editor form tag
do_action('post_edit_form_tag', $post);
?>>
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
<input type="hidden" id="hiddenaction" name="action" value="<?php echo esc_attr( $form_action ) ?>" />
<input type="hidden" id="originalaction" name="originalaction" value="<?php echo esc_attr( $form_action ) ?>" />
<input type="hidden" id="post_author" name="post_author" value="<?php echo esc_attr( $post->post_author ); ?>" />
<input type="hidden" id="post_type" name="post_type" value="<?php echo esc_attr( $post_type ) ?>" />
<input type="hidden" id="original_post_status" name="original_post_status" value="<?php echo esc_attr( $post->post_status) ?>" />
<?php $referer = wp_get_referer(); ?>
<input type="hidden" id="referredby" name="referredby" value="<?php echo $referer ? esc_url( $referer ) : ''; ?>" />
<?php
if ( 'draft' != get_post_status( $post ) ){
	wp_original_referer_field(true, 'previous');
}
echo "<input type='hidden' id='post_ID' name='post_ID' value='" . esc_attr($post_ID) . "' />";

wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>

<?php
// ires at the beginning of the edit form
do_action( 'edit_form_top', $post ); ?>

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">
<div id="post-body-content">






<?php
// Display title edition if supported by post type
if(post_type_supports($post_type, 'title')){
?>
<div id="titlediv">
<div id="titlewrap">
	<input type="text" name="post_title" size="30" value="<?php echo esc_attr( $post->post_title ); ?>" id="title" spellcheck="true" autocomplete="off" />
</div>
<?php
// Fires before the permalink field in the edit form
do_action( 'edit_form_before_permalink', $post );
?>
<div class="inside">
<?php
if($viewable){
	$sample_permalink_html = $post_type_object->public ? get_sample_permalink_html($post->ID) : '';
	if($post_type_object->public && !( 'pending' == get_post_status( $post ) && !current_user_can( $post_type_object->cap->publish_posts ) ) ){
	?>
	<div id="edit-slug-box"><?php echo $sample_permalink_html; ?></div>
	<?php
	}
}
?>
</div>
<?php
wp_nonce_field( 'samplepermalink', 'samplepermalinknonce', false );
?>
</div><!-- /titlediv -->
<?php
}
// Fires after the title field
do_action('edit_form_after_title', $post);
?>


<?php
// Display rich text editor for main content if supported by post type
if(post_type_supports($post_type, 'editor')){
?>
<div id="postdivrich" class="postarea">
<?php wp_editor( $post->post_content, 'content' ); ?>
</div>
<?php
}
// Fires after the content editor
do_action( 'edit_form_after_editor', $post );
?>
</div><!-- /post-body-content -->










<div id="postbox-container-1" class="postbox-container">
<?php
if('page' == $post_type){
	// Fires before meta boxes with 'side' context are output for the 'page' post type
	do_action('submitpage_box', $post);
}else{
	//Fires before meta boxes with 'side' context are output for all post types other than 'page'
	do_action('submitpost_box', $post);
}

do_meta_boxes($post_type, 'side', $post);
?>
</div>



<div id="postbox-container-2" class="postbox-container">
<?php
do_meta_boxes(null, 'normal', $post);

if ('page' == $post_type){
	// Fires after 'normal' context meta boxes have been output for the 'page' post type
	do_action('edit_page_form', $post);
}else{
	// Fires after 'normal' context meta boxes have been output for all post types other than 'page'
	do_action('edit_form_advanced', $post);
}

do_meta_boxes(null, 'advanced', $post);
?>
</div>



<?php
// Fires after all meta box sections have been output, before the closing #post-body div
do_action('dbx_post_sidebar', $post);
?>

</div><!-- /post-body -->
<br class="clear" />
</div><!-- /poststuff -->
</form>
</div>