<?php
/**
 * WordPress media templates.
 *
 * @package WordPress
 * @subpackage Media
 * @since 3.5.0
 */

/**
 * Output the markup for a audio tag to be used in an Underscore template
 * when data.model is passed.
 *
 * @since 3.9.0
 */
function wp_underscore_audio_template(){
	$audio_types = wp_get_audio_extensions();
?>
<audio style="visibility: hidden"
	controls
	class="wp-audio-shortcode"
	width="{{ _.isUndefined(data.model.width ) ? 400 : data.model.width }}"
	preload="{{ _.isUndefined(data.model.preload ) ? 'none' : data.model.preload }}"
	<#
	<?php foreach(array('autoplay', 'loop' ) as $attr ):
	?>if(!_.isUndefined(data.model.<?php echo $attr ?> ) && data.model.<?php echo $attr ?> ){
		#> <?php echo $attr ?><#
	}
	<?php endforeach ?>#>
>
	<# if(!_.isEmpty(data.model.src ) ){ #>
	<source src="{{ data.model.src }}" type="{{ wp.media.view.settings.embedMimes[ data.model.src.split('.').pop() ] }}" />
	<# } #>

	<?php foreach($audio_types as $type ):
	?><# if(!_.isEmpty(data.model.<?php echo $type ?> ) ){ #>
	<source src="{{ data.model.<?php echo $type ?> }}" type="{{ wp.media.view.settings.embedMimes[ '<?php echo $type ?>' ] }}" />
	<# } #>
	<?php endforeach;
?></audio>
<?php
}

/**
 * Output the markup for a video tag to be used in an Underscore template
 * when data.model is passed.
 *
 * @since 3.9.0
 */
function wp_underscore_video_template(){
	$video_types = wp_get_video_extensions();
?>
<#  var w_rule = '', classes = [],
		w, h, settings = wp.media.view.settings,
		isYouTube = isVimeo = false;

	if(!_.isEmpty(data.model.src ) ){
		isYouTube = data.model.src.match(/youtube|youtu\.be/);
		isVimeo = -1 !== data.model.src.indexOf('vimeo');
	}

	if(settings.contentWidth && data.model.width >= settings.contentWidth ){
		w = settings.contentWidth;
	} else{
		w = data.model.width;
	}

	if(w !== data.model.width ){
		h = Math.ceil((data.model.height * w ) / data.model.width );
	} else{
		h = data.model.height;
 	}

	if(w ){
		w_rule = 'width: ' + w + 'px; ';
	}

	if(isYouTube ){
		classes.push('youtube-video' );
	}

	if(isVimeo ){
		classes.push('vimeo-video' );
	}

#>
<div style="{{ w_rule }}" class="wp-video">
<video controls
	class="wp-video-shortcode{{ classes.join(' ' ) }}"
	<# if(w ){ #>width="{{ w }}"<# } #>
	<# if(h ){ #>height="{{ h }}"<# } #>
	<?php
	$props = array('poster' => '', 'preload' => 'metadata' );
	foreach($props as $key => $value ):
		if(empty($value ) ){
		?><#
		if(!_.isUndefined(data.model.<?php echo $key ?> ) && data.model.<?php echo $key ?> ){
			#> <?php echo $key ?>="{{ data.model.<?php echo $key ?> }}"<#
		} #>
		<?php } else{
			echo $key ?>="{{ _.isUndefined(data.model.<?php echo $key ?> ) ? '<?php echo $value ?>' : data.model.<?php echo $key ?> }}"<?php
		}
	endforeach;
	?><#
	<?php foreach(array('autoplay', 'loop' ) as $attr ):
	?> if(!_.isUndefined(data.model.<?php echo $attr ?> ) && data.model.<?php echo $attr ?> ){
		#> <?php echo $attr ?><#
	}
	<?php endforeach ?>#>
>
	<# if(!_.isEmpty(data.model.src ) ){
		if(isYouTube ){ #>
		<source src="{{ data.model.src }}" type="video/youtube" />
		<# } else if(isVimeo ){ #>
		<source src="{{ data.model.src }}" type="video/vimeo" />
		<# } else{ #>
		<source src="{{ data.model.src }}" type="{{ settings.embedMimes[ data.model.src.split('.').pop() ] }}" />
		<# }
	} #>

	<?php foreach($video_types as $type ):
	?><# if(data.model.<?php echo $type ?> ){ #>
	<source src="{{ data.model.<?php echo $type ?> }}" type="{{ settings.embedMimes[ '<?php echo $type ?>' ] }}" />
	<# } #>
	<?php endforeach; ?>
	{{{ data.model.content }}}
</video>
</div>
<?php
}

/**
 * Prints the templates used in the media manager.
 *
 * @since 3.5.0
 *
 * @global bool $is_IE
 */
function wp_print_media_templates(){
	global $is_IE;
	$class = 'media-modal wp-core-ui';
	if($is_IE && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7') !== false )
		$class .= ' ie7';
	?>
	<!--[if lte IE 8]>
	<style>
		.attachment:focus{
			outline: #1e8cbe solid;
		}
		.selected.attachment{
			outline: #1e8cbe solid;
		}
	</style>
	<![endif]-->


	

	<?php

	/**
	 * Fires when the custom Backbone media templates are printed.
	 *
	 * @since 3.5.0
	 */
	do_action('print_media_templates' );
}
