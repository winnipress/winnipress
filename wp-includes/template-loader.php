<?php
// WHAT IS THIS FILE: Load the correct template based on the visitor's url

// Fires before determining which template to load
do_action('template_redirect');

// Whether to exit without generating any content for 'HEAD' requests
if('HEAD' === $_SERVER['REQUEST_METHOD'] && apply_filters('exit_on_http_head', true)){
	exit();
}

$template = false;
if     (is_embed()          && $template = get_embed_template()               ) :
elseif(is_404()            && $template = get_404_template()                 ) :
elseif(is_search()         && $template = get_search_template()              ) :
elseif(is_front_page()     && $template = get_front_page_template()          ) :
elseif(is_home()           && $template = get_home_template()                ) :
elseif(is_post_type_archive() && $template = get_post_type_archive_template()) :
elseif(is_tax()            && $template = get_taxonomy_template()            ) :
elseif(is_attachment()     && $template = get_attachment_template()          ) :
	    remove_filter('the_content', 'prepend_attachment');
elseif(is_single()         && $template = get_single_template()              ) :
elseif(is_page()           && $template = get_page_template()                ) :
elseif(is_singular()       && $template = get_singular_template()            ) :
elseif(is_category()       && $template = get_category_template()            ) :
elseif(is_tag()            && $template = get_tag_template()                 ) :
elseif(is_author()         && $template = get_author_template()              ) :
elseif(is_date()           && $template = get_date_template()                ) :
elseif(is_archive()        && $template = get_archive_template()             ) :
else :
	$template = get_index_template();
endif;

// Filter the path of the current template before actually including it to allow customizations
// If the filtered value is set to false, do not include
if($template = apply_filters('template_include', $template)){
    include($template);
}