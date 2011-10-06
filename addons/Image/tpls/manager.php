<style>
#i8-gallery {
	padding:7px 0;	
}


#i8-gallery li {
	float:left;
	margin:0 10px 10px 0;
	padding:3px;
	border:1px solid #DFDFDF;
    border-radius:3px;
	background:#fff;
	position:relative;
}

#i8-gallery li a {
	display:block;	
}

.i8-close {
	position:absolute;
	right:0px;
	top:0px;
	margin:-5px -5px 0 0;
	cursor:pointer;	
}

</style>

<ul id="i8-gallery">
	<?php foreach ((array)$atts as $att) :
		if (!wp_attachment_is_image($att->ID)) {
			continue;	
		}
		
		$info = wp_get_attachment_image_src($att->ID, 'i8-thumb');
        list($src,,) = $info; 
		
	?>
    <li id="i8-item-<?php echo $att->ID; ?>">
    	<a href="<?php echo wp_get_attachment_url($att->ID); ?>" class="thickbox" rel="showroom-<?php echo $post->ID; ?>">
        	<img src="<?php echo $src; ?>" />
        </a>
        <img class="i8-close" src="<?php echo $this->url; ?>/images/close.gif" title="<?php _e('Remove'); ?>" />
    </li>
    <?php endforeach; ?>
</ul>
<div style="clear:both"> </div>


<div id="i8-container">
    <button id="i8-uploader" class="button-secondary" type="button">
        <span><?php _e('Browse...'); ?></span>&nbsp;<img class="i8-loader" src="<?php echo $this->url; ?>/images/ajax-loader.gif" style="display:none" />
    </button> <span class="description">to add more images or simply drag&amp;drop them onto the metabox.</span>
</div>
<script>
(function($) {
	var loader = $('#i8-uploader').find('.i8-loader'),
		uploader = new plupload.Uploader({
			runtimes : 'html5,flash,silverlight,html4',
			url: ajaxurl,
			multipart_params: {
				action: 'i8_image_upload',
				post_parent: '<?php echo intval($post->ID); ?>'
			},
			browse_button: 'i8-uploader',
			drop_element: 'showroom',
			container: 'i8-container',
			browse_button_hover: 'hover',
			browse_button_active: 'active',
			filters : [
				{title : "Image files", extensions : "jpg,gif,png"}
			],
			flash_swf_url : '<?php echo $this->url; ?>/js/plupload.flash.swf',
			silverlight_xap_url : '<?php echo $this->url; ?>/js/plupload.silverlight.xap'
		});
	
	uploader.init();
	
	uploader.bind('FilesAdded', function() {
		loader.show();
		uploader.start();
	});
	
	uploader.bind('FileUploaded', function(up, file, r) {
		var html = r.response;
		if (html && $.trim(html) !== '') {
			$('#i8-gallery').append($(html));
		}
	});
	
	uploader.bind('UploadComplete', function() {
		loader.hide();
	});
	
	
	$('#i8-gallery').click(function(e) {
		var it = $(e.target), li;
		
		if (!it.is('.i8-close')) {
			return;	
		}
		
		li = it.parent();
		
		li.animate({ backgroundColor: '#c00', opacity: 0}, 'fast', 'swing', function() { li.remove(); });
		
		$.post(ajaxurl, {
				post_id: li.attr('id').replace(/^i8-item-/, ''),
				action: 'i8_image_delete'
			}, function(r) {}, 'json'
		);
		
	});
	
}(jQuery));
</script>