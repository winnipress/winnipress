<?php
/**
 * WordPress Dashboard Widget Administration Screen API
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Registers dashboard widgets.
 *
 * Handles POST data, sets up filters.
 *
 * @since 2.5.0
 *
 * @global array $wp_registered_widgets
 * @global array $wp_registered_widget_controls
 * @global array $wp_dashboard_control_callbacks
 */
function wp_dashboard_setup() {
	global $wp_registered_widgets, $wp_registered_widget_controls, $wp_dashboard_control_callbacks;
	$wp_dashboard_control_callbacks = array();
	$screen = get_current_screen();



		wp_add_dashboard_widget( 'dashboard_right_now', __( 'At a Glance'), 'wp_dashboard_right_now');






	

	if( is_user_admin()) {

		/**
		 * Fires after core widgets for the User Admin dashboard have been registered.
		 *
		 * @since 3.1.0
		 */
		do_action( 'wp_user_dashboard_setup');

		/**
		 * Filters the list of widgets to load for the User Admin dashboard.
		 *
		 * @since 3.1.0
		 *
		 * @param array $dashboard_widgets An array of dashboard widgets.
		 */
		$dashboard_widgets = apply_filters( 'wp_user_dashboard_widgets', array());
	} else {

		/**
		 * Fires after core widgets for the admin dashboard have been registered.
		 *
		 * @since 2.5.0
		 */
		do_action( 'wp_dashboard_setup');

		/**
		 * Filters the list of widgets to load for the admin dashboard.
		 *
		 * @since 2.5.0
		 *
		 * @param array $dashboard_widgets An array of dashboard widgets.
		 */
		$dashboard_widgets = apply_filters( 'wp_dashboard_widgets', array());
	}

	foreach( $dashboard_widgets as $widget_id) {
		$name = empty( $wp_registered_widgets[$widget_id]['all_link']) ? $wp_registered_widgets[$widget_id]['name'] : $wp_registered_widgets[$widget_id]['name'] . " <a href='{$wp_registered_widgets[$widget_id]['all_link']}' class='edit-box open-box'>" . __('View all') . '</a>';
		wp_add_dashboard_widget( $widget_id, $name, $wp_registered_widgets[$widget_id]['callback'], $wp_registered_widget_controls[$widget_id]['callback']);
	}

}

/**
 * Adds a new dashboard widget.
 *
 * @since 2.7.0
 *
 * @global array $wp_dashboard_control_callbacks
 *
 * @param string   $widget_id        Widget ID  (used in the 'id' attribute for the widget).
 * @param string   $widget_name      Title of the widget.
 * @param callable $callback         Function that fills the widget with the desired content.
 *                                   The function should echo its output.
 * @param callable $control_callback Optional. Function that outputs controls for the widget. Default null.
 * @param array    $callback_args    Optional. Data that should be set as the $args property of the widget array
 *                                   (which is the second parameter passed to your callback). Default null.
 */
function wp_add_dashboard_widget( $widget_id, $widget_name, $callback, $control_callback = null, $callback_args = null) {
	$screen = get_current_screen();
	global $wp_dashboard_control_callbacks;

	$private_callback_args = array( '__widget_basename' => $widget_name);

	if( is_null( $callback_args)) {
		$callback_args = $private_callback_args;
	} else if( is_array( $callback_args)) {
		$callback_args = array_merge( $callback_args, $private_callback_args);
	}

	if( $control_callback && current_user_can( 'edit_dashboard') && is_callable( $control_callback)) {
		$wp_dashboard_control_callbacks[$widget_id] = $control_callback;
		if( isset( $_GET['edit']) && $widget_id == $_GET['edit']) {
			list($url) = explode( '#', add_query_arg( 'edit', false), 2);
			$widget_name .= ' <span class="postbox-title-action"><a href="' . esc_url( $url) . '">' . __( 'Cancel') . '</a></span>';
			$callback = '_wp_dashboard_control_callback';
		} else {
			list($url) = explode( '#', add_query_arg( 'edit', $widget_id), 2);
			$widget_name .= ' <span class="postbox-title-action"><a href="' . esc_url( "$url#$widget_id") . '" class="edit-box open-box">' . __( 'Configure') . '</a></span>';
		}
	}

	$side_widgets = array( 'dashboard_quick_press', 'dashboard_primary');

	$location = 'normal';
	if( in_array($widget_id, $side_widgets))
		$location = 'side';

	$priority = 'core';
	if( 'dashboard_browser_nag' === $widget_id)
		$priority = 'high';

	add_meta_box( $widget_id, $widget_name, $callback, $screen, $location, $priority, $callback_args);
}

/**
 * Outputs controls for the current dashboard widget.
 *
 * @access private
 * @since 2.7.0
 *
 * @param mixed $dashboard
 * @param array $meta_box
 */
function _wp_dashboard_control_callback( $dashboard, $meta_box) {
	echo '<form method="post" class="dashboard-widget-control-form wp-clearfix">';
	wp_dashboard_trigger_widget_control( $meta_box['id']);
	wp_nonce_field( 'edit-dashboard-widget_' . $meta_box['id'], 'dashboard-widget-nonce');
	echo '<input type="hidden" name="widget_id" value="' . esc_attr($meta_box['id']) . '" />';
	submit_button( __('Submit'));
	echo '</form>';
}

/**
 * Displays the dashboard.
 *
 * @since 2.5.0
 */
function wp_dashboard() {
	$screen = get_current_screen();
	$columns = absint( $screen->get_columns());
	$columns_css = '';
	if( $columns) {
		$columns_css = " columns-$columns";
	}

?>
<div id="dashboard-widgets" class="metabox-holder<?php echo $columns_css; ?>">
	<div id="postbox-container-1" class="postbox-container">
	<?php do_meta_boxes( $screen->id, 'normal', ''); ?>
	</div>
	<div id="postbox-container-2" class="postbox-container">
	<?php do_meta_boxes( $screen->id, 'side', ''); ?>
	</div>
	<div id="postbox-container-3" class="postbox-container">
	<?php do_meta_boxes( $screen->id, 'column3', ''); ?>
	</div>
	<div id="postbox-container-4" class="postbox-container">
	<?php do_meta_boxes( $screen->id, 'column4', ''); ?>
	</div>
</div>

<?php
	wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false);
	wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false);

}

//
// Dashboard Widgets
//

/**
 * Dashboard widget that displays some basic stats about the site.
 *
 * Formerly 'Right Now'. A streamlined 'At a Glance' as of 3.8.
 *
 * @since 2.7.0
 */
function wp_dashboard_right_now() {
?>
	<div class="main">
	<ul>
	<?php
	// Posts and Pages
	foreach( array( 'post', 'page') as $post_type) {
		$num_posts = wp_count_posts( $post_type);
		if( $num_posts && $num_posts->publish) {
			if( 'post' == $post_type) {
				$text = _n( '%s Post', '%s Posts', $num_posts->publish);
			} else {
				$text = _n( '%s Page', '%s Pages', $num_posts->publish);
			}
			$text = sprintf( $text, number_format_i18n( $num_posts->publish));
			$post_type_object = get_post_type_object( $post_type);
			if( $post_type_object && current_user_can( $post_type_object->cap->edit_posts)) {
				printf( '<li class="%1$s-count"><i class="la la-file"></i> <a href="edit.php?post_type=%1$s">%2$s</a></li>', $post_type, $text);
			} else {
				printf( '<li class="%1$s-count"><i class="la la-file"></i> <span>%2$s</span></li>', $post_type, $text);
			}

		}
	}


	/**
	 * Filters the array of extra elements to list in the 'At a Glance'
	 * dashboard widget.
	 *
	 * Prior to 3.8.0, the widget was named 'Right Now'. Each element
	 * is wrapped in list-item tags on output.
	 *
	 * @since 3.8.0
	 *
	 * @param array $items Array of extra 'At a Glance' widget items.
	 */
	$elements = apply_filters( 'dashboard_glance_items', array());

	if( $elements) {
		echo '<li>' . implode( "</li>\n<li>", $elements) . "</li>\n";
	}

	?>
	</ul>

	</div>
	<?php
	/*
	 * activity_box_end has a core action, but only prints content when multisite.
	 * Using an output buffer is the only way to really check if anything's displayed here.
	 */
	ob_start();

	/**
	 * Fires at the end of the 'At a Glance' dashboard widget.
	 *
	 * Prior to 3.8.0, the widget was named 'Right Now'.
	 *
	 * @since 2.5.0
	 */
	do_action( 'rightnow_end');

	/**
	 * Fires at the end of the 'At a Glance' dashboard widget.
	 *
	 * Prior to 3.8.0, the widget was named 'Right Now'.
	 *
	 * @since 2.0.0
	 */
	do_action( 'activity_box_end');

	$actions = ob_get_clean();

	if( !empty( $actions)) : ?>
	<div class="sub">
		<?php echo $actions; ?>
	</div>
	<?php endif;
}

/**
 * @since 3.1.0
 */
function wp_network_dashboard_right_now() {
	$actions = array();
	if( current_user_can('create_sites'))
		$actions['create-site'] = '<a href="' . network_admin_url('site-new.php') . '">' . __( 'Create a New Site') . '</a>';
	if( current_user_can('create_users'))
		$actions['create-user'] = '<a href="' . network_admin_url('user-new.php') . '">' . __( 'Create a New User') . '</a>';

	$c_users = get_user_count();
	$c_blogs = get_blog_count();

	/* translators: %s: number of users on the network */
	$user_text = sprintf( _n( '%s user', '%s users', $c_users), number_format_i18n( $c_users));
	/* translators: %s: number of sites on the network */
	$blog_text = sprintf( _n( '%s site', '%s sites', $c_blogs), number_format_i18n( $c_blogs));

	/* translators: 1: text indicating the number of sites on the network, 2: text indicating the number of users on the network */
	$sentence = sprintf( __( 'You have %1$s and %2$s.'), $blog_text, $user_text);

	if( $actions) {
		echo '<ul class="subsubsub">';
		foreach( $actions as $class => $action) {
			 $actions[ $class ] = "\t<li class='$class'>$action";
		}
		echo implode( " |</li>\n", $actions) . "</li>\n";
		echo '</ul>';
	}
?>
	<br class="clear" />

	<p class="youhave"><?php echo $sentence; ?></p>


	<?php
		/**
		 * Fires in the Network Admin 'Right Now' dashboard widget
		 * just before the user and site search form fields.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param null $unused
		 */
		do_action( 'wpmuadminresult', '');
	?>

	<form action="<?php echo network_admin_url('users.php'); ?>" method="get">
		<p>
			<label class="screen-reader-text" for="search-users"><?php _e( 'Search Users'); ?></label>
			<input type="search" name="s" value="" size="30" autocomplete="off" id="search-users"/>
			<?php submit_button( __( 'Search Users'), '', false, false, array( 'id' => 'submit_users')); ?>
		</p>
	</form>

	<form action="<?php echo network_admin_url('sites.php'); ?>" method="get">
		<p>
			<label class="screen-reader-text" for="search-sites"><?php _e( 'Search Sites'); ?></label>
			<input type="search" name="s" value="" size="30" autocomplete="off" id="search-sites"/>
			<?php submit_button( __( 'Search Sites'), '', false, false, array( 'id' => 'submit_sites')); ?>
		</p>
	</form>
<?php
	/**
	 * Fires at the end of the 'Right Now' widget in the Network Admin dashboard.
	 *
	 * @since MU (3.0.0)
	 */
	do_action( 'mu_rightnow_end');

	/**
	 * Fires at the end of the 'Right Now' widget in the Network Admin dashboard.
	 *
	 * @since MU (3.0.0)
	 */
	do_action( 'mu_activity_box_end');
}



/**
 * Show recent drafts of the user on the dashboard.
 *
 * @since 2.7.0
 *
 * @param array $drafts
 */
function wp_dashboard_recent_drafts( $drafts = false) {
	if( !$drafts) {
		$query_args = array(
			'post_type'      => 'post',
			'post_status'    => 'draft',
			'author'         => get_current_user_id(),
			'posts_per_page' => 4,
			'orderby'        => 'modified',
			'order'          => 'DESC'
		);

		/**
		 * Filters the post query arguments for the 'Recent Drafts' dashboard widget.
		 *
		 * @since 4.4.0
		 *
		 * @param array $query_args The query arguments for the 'Recent Drafts' dashboard widget.
		 */
		$query_args = apply_filters( 'dashboard_recent_drafts_query_args', $query_args);

		$drafts = get_posts( $query_args);
		if( !$drafts) {
			return;
 		}
 	}

	echo '<div class="drafts">';
	if( count( $drafts) > 3) {
		echo '<p class="view-all"><a href="' . esc_url( admin_url( 'edit.php?post_status=draft')) . '">' . __( 'View all drafts') . "</a></p>\n";
 	}
	echo '<h2 class="hide-if-no-js">' . __( 'Your Recent Drafts') . "</h2>\n<ul>";

	$drafts = array_slice( $drafts, 0, 3);
	foreach( $drafts as $draft) {
		$url = get_edit_post_link( $draft->ID);
		$title = _draft_or_post_title( $draft->ID);
		echo "<li>\n";
		/* translators: %s: post title */
		echo '<div class="draft-title"><a href="' . esc_url( $url) . '" aria-label="' . esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;'), $title)) . '">' . esc_html( $title) . '</a>';
		echo '<time datetime="' . get_the_time( 'c', $draft) . '">' . get_the_time( __( 'F j, Y'), $draft) . '</time></div>';
		if( $the_content = wp_trim_words( $draft->post_content, 10)) {
			echo '<p>' . $the_content . '</p>';
 		}
		echo "</li>\n";
 	}
	echo "</ul>\n</div>";
}




//
// Dashboard Widgets Controls
//

/**
 * Calls widget control callback.
 *
 * @since 2.5.0
 *
 * @global array $wp_dashboard_control_callbacks
 *
 * @param int $widget_control_id Registered Widget ID.
 */
function wp_dashboard_trigger_widget_control( $widget_control_id = false) {
	global $wp_dashboard_control_callbacks;

	if( is_scalar($widget_control_id) && $widget_control_id && isset($wp_dashboard_control_callbacks[$widget_control_id]) && is_callable($wp_dashboard_control_callbacks[$widget_control_id])) {
		call_user_func( $wp_dashboard_control_callbacks[$widget_control_id], '', array( 'id' => $widget_control_id, 'callback' => $wp_dashboard_control_callbacks[$widget_control_id]));
	}
}