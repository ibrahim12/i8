<?php

class ImageAddon {
	
	
	function init()
	{
		add_action('wp_ajax_i8_image_upload', array($this, 'a__wp_ajax_i8_image_upload')); 
		add_action('wp_ajax_i8_image_delete', array($this, 'a__wp_ajax_i8_image_delete')); 
		
		add_image_size('i8-thumb', 200, 120, true);
	}
	
	
	function a__wp_ajax_i8_image_upload()
	{
		if (!is_numeric($_POST['post_parent'])) {
			die;	
		}
		
		if (!$file = $this->handle_upload($_FILES['file'])) {
			// error
			die;	
		}
		
		if ($att_id = $this->add_attachment($_POST['post_parent'], $file)) :
			
			$info = wp_get_attachment_image_src($att_id, 'i8-thumb');
        	list($src,,) = $info; 
			
			?><li id="i8-item-<?php echo $att_id; ?>">
				<a href="<?php echo $file['url']; ?>" class="thickbox" rel="showroom-<?php echo $_POST['post_parent']; ?>">
					<img src="<?php echo $src; ?>" />
				</a>
				<img class="i8-close" src="<?php echo $this->url; ?>/images/close.gif" title="<?php _e('Remove'); ?>" />
			</li><?php
			exit;
		
		else :
			die; // some error	
		endif;
	}
	
	
	function a__wp_ajax_i8_image_delete()
	{
		if (!is_numeric($_POST['post_id'])) {
			die;	
		}
		
		if (wp_delete_attachment($_POST['post_id'], true)) {
			die(json_encode(array('OK' => 1)));	
		}
		
		die(json_encode(array('OK' => 0)));
	}
	
	
	function load_manager_scripts()
	{
		wp_enqueue_script('i8-image-plupload', $this->url . "/js/plupload.full.js"	, array('thickbox', 'post'), '1.5.1.1');
		wp_enqueue_style('thickbox');
	}
	
	
	function _gallery_manager_metabox($post)
	{
		$atts = get_posts("post_parent={$post->ID}&post_type=attachment&numberposts=-1&orderby=menu_order&order=ASC");
		include("$this->path/tpls/manager.php");
	}
	
	
	
	function get_size_url($id, $size = 'medium')
	{
		if ($arr = image_downsize($id, $size)) {
			return $arr[0];	
		}	
		return false;
	}
	
	
	function handle_upload($file)
	{		
		# put $ext and $type (file MIME-Type) into local scope 
		extract(wp_check_filetype($file['name']));
		if (!$ext || !$type) {
			return false;	
		}
		
		$tmp_dir = wp_upload_dir(current_time('mysql'));
		if (!is_writable($tmp_dir['path'])) {
			return false;
		}
		
		$filename = wp_unique_filename($tmp_dir['path'], $this->_convert_accents($file['name']));
		$filepath = $tmp_dir['path'] . '/' . $filename;
		
		# copy file
		rename($file['tmp_name'], $filepath);
		
		# Set correct file permissions
		$stat = stat( dirname( $filepath ));
		$perms = $stat['mode'] & 0000666;
		@chmod( $filepath, $perms );
	
		# Compute the URL
		$url = $tmp_dir['url'] . "/$filename";
		
		return apply_filters( 'wp_handle_upload', array('file' => $filepath, 'url' => $url, 'type' => $type) );	
	}
	
	
	function fetch($url)
	{
		$url = urldecode($url);
		
		# put $ext and $type (file MIME-Type) into local scope 
		$path = parse_url($url, PHP_URL_PATH);
		extract(wp_check_filetype($path));
		if (!$ext || !$type) {
			return false;	
		}
	
		if (!$body = wp_remote_fopen($url)) {
			return false;
		}
			
		$tmp_dir = wp_upload_dir(current_time('mysql'));
		if (!is_writable($tmp_dir['path'])) {
			return false;
		}
		
		$image = imagecreatefromstring($body);
		
		$filename = wp_unique_filename($tmp_dir['path'], $this->_convert_accents(basename($path)));
		$filepath = $tmp_dir['path'] . '/' . $filename;
	
		switch ($type) :
			case 'image/jpeg':
				imagejpeg($image, $filepath, 100);
				break;
			case 'image/gif':
				imagegif($image, $filepath);
				break;
			case 'image/png':
				imagepng($image, $filepath);
				break;
			default:
				return false;
		endswitch;
		
		imagedestroy($image);
	
		# Set correct file permissions
		$stat = stat( dirname( $filepath ));
		$perms = $stat['mode'] & 0000666;
		@chmod( $filepath, $perms );
	
		# Compute the URL
		$url = $tmp_dir['url'] . "/$filename";
		
		return apply_filters( 'wp_handle_upload', array('file' => $filepath, 'url' => $url, 'type' => $type) );			
	}
	
	/**
	 * Adds specified file: array('file' => $filepath, 'url' => $url, 'type' => $type) as attachment to specified post
	 */
	function add_attachment($post_id, $file)
	{	
		// by init action this file is not yet included, so we need to include it manually
		require_once(ABSPATH . '/wp-admin/includes/image.php');
	
		$url = $file['url'];
		$type = $file['type'];
		$file = $file['file'];
		$title = preg_replace('/\.[^.]+$/', '', basename($file));
		$content = '';
	
		# use image exif/iptc data for title and caption defaults if possible
		if ( $image_meta = wp_read_image_metadata($file) ) {
			if ( trim($image_meta['title']) )
				$title = $image_meta['title'];
			if ( trim($image_meta['caption']) )
				$content = $image_meta['caption'];
		}
	
		# Construct the attachment array
		$attachment = array(
			'post_mime_type' => $type,
			'guid' => $url,
			'post_parent' => $post_id,
			'post_title' => $title,
			'post_content' => $content
		);
	
		# Save the data
		$id = wp_insert_attachment($attachment, $file, $post_id);
		if ( !is_wp_error($id) ) {
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
			return $id;
		}
		
		return false;	
	}
	
	
	protected function _convert_accents($string) 
	{
		$table = array(
			'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
			'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
			'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
			'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
			'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
			'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
		);
		return strtr($string, $table);
	}
	
}


?>