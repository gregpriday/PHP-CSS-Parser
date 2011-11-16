<?php

require_once(dirname(__FILE__).'/type.php');

class Origin_Type_File extends Origin_Type {
	function render_form(){
		if(!empty($this->settings['is_image']) && !empty($this->value)) {
			// Display the image
			$height = min($this->value['size'][1], 40);
			$width = round($this->value['size'][0] / ($this->value['size'][1]/$height));
			?>
			<div class="container">
				<div class="current">
					<img src="<?php print $this->value['fileurl'] ?>" width="<?php esc_attr_e($width) ?>" height="<?php esc_attr_e($height) ?>">
				</div>
			</div>
			<?php
		}
		
		if(!empty($this->settings['removable']) && !empty($this->value)) {
			?>
			<span>
				<input type="checkbox" name="<?php esc_attr_e($this->form_name.'_remove') ?>" id="<?php esc_attr_e($this->form_id.'-remove') ?>">
				<label for="<?php esc_attr_e($this->form_id.'-remove') ?>"><?php _e('Remove', 'origin') ?></label>
			</span>
			<?php
		}
		
		?>
		<div class="clear"></div>
		<input type="file" name="<?php esc_attr_e($this->form_name) ?>" id="<?php esc_attr_e($this->form_id) ?>" />
		<?php
	}
	
	function process_input($input){
		if(!empty($input[$this->form_name.'_remove'])){
			unlink($this->value['filepath']);
			unset($this->value);
		}
		
		// Get the file
		if(!empty($_FILES[$this->form_name]['tmp_name'])){
			// Handle the upload
			$path = WP_CONTENT_DIR.'/'.$this->settings['path'];
			
			if(!is_dir($path)) mkdir($path, 0777, true);
			$fileinfo = pathinfo($_FILES[$this->form_name]['name']);
			$file_valid = true;
			
			// Validate the file
			if(!empty($this->settings['restrict']) && !in_array($fileinfo['extension'], $this->settings['restrict'])){
				$this->error = __('Invalid file extension.', 'rocket');
			}
			else{ // The file is valid, we can upload it
				// Create a unique name for the file
				$filename = $fileinfo['filename'].'.'.$fileinfo['extension'];
				$i = 1;
				while(file_exists($path.'/'.$filename)){
					$filename = $fileinfo['filename'].'.'.($i++).'.'.$fileinfo['extension'];
				}
				
				// Upload the file
				if(!move_uploaded_file($_FILES[$this->form_name]['tmp_name'], $path.'/'.$filename)){
					$this->error = __('Error uploading file.', 'origin');
				}
				
				if(empty($this->error) && file_exists($this->value['filepath'])){
					// Remove the old file and settings
					unlink($this->value['filepath']);
					unset($this->value);
				}
				
				if(!empty($this->settings['is_image']) && empty($this->error)){
					$new_logo = true;
					// The file has been moved, store its size
					if($fileinfo['extension'] == 'png')
						$img = imagecreatefrompng($path.'/'.$filename);
					elseif($fileinfo['extension'] == 'gif')
						$img = imagecreatefromgif($path.'/'.$filename);
					
					$this->value = array(
						'size' => array(imagesx($img), imagesy($img)),
						'filepath' => $path.'/'.$filename,
						'fileurl' => content_url($this->settings['path'].'/'.$filename),
					);
				}
			}
		}
	}
	
	function validate_settings(){
		return true;
	}
}