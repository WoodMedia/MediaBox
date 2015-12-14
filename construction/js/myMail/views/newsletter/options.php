<?php
	$editable = !in_array($post->post_status, array('active', 'finished'));
?>
<?php if($editable) :?>

	<span class="spinner" id="colorschema-ajax-loading"></span>
	<p><label><input name="mymail_data[embed_images]" id="mymail_data_embed_images" value="1" type="checkbox" <?php echo (isset($this->post_data['embed_images'])) ? (($this->post_data['embed_images']) ? 'checked' : '') :  (mymail_option('embed_images') ? 'checked' : '') ?> <?php echo ($editable) ? 'disabled' : '' ?>> <?php _e('Embed Images', 'mymail') ?></label></p>
	<label><?php _e('Colors', 'mymail'); ?></label> <a class="savecolorschema"><?php _e('save this schema', 'mymail') ?></a>
	
	<ul class="colors">	
	<?php 
	
		$html = $this->templateobj->get(true);
		$colors = array();
		preg_match_all('/#[a-fA-F0-9]{6}/', $html, $hits);
		$original_colors = array_unique($hits[0]);
		$html = $post->post_content;
		
		if(!empty($html) && isset($this->post_data['template']) && $this->post_data['template'] == $this->get_template() && $this->post_data['file'] == $this->get_file()){
			preg_match_all('/#[a-fA-F0-9]{6}/', $html, $hits);
			$current_colors = array_unique($hits[0]);
		}else{
			$current_colors = $original_colors;
		}
		
		foreach($current_colors as $color){
			$value = strtolower($color);
			$colors[] = $value;
			
		?>	
		<li><input type="text" class="form-input-tip color" name="mymail_data[newsletter_color][<?php echo $color?>]"  value="<?php echo $value?>" data-value="<?php echo $value?>" data-default="<?php echo $value?>"> <a class="default-value" href="#"></a></li>
		<?php 
		}
	?>
	</ul>
	<label><?php _e('Colors Schemas', 'mymail'); ?></label>
	<?php 
		$customcolors = get_option('mymail_colors');
		if(isset($customcolors[$this->get_template()])) :
	?>
	<a class="colorschema-delete-all"><?php _e('delete all custom schemas', 'mymail') ?></a>
	<?php endif; ?>
	<br><ul class="colorschema" title="<?php _e('original', 'mymail')?>">
	<?php 
		$original_colors_temp = array();
		foreach($original_colors as $color){
			$color = strtolower($color);
			$original_colors_temp[] = $color;
		?>	
		<li class="colorschema-field" data-hex="<?php echo $color?>" style="background-color:<?php echo $color?>"></li>
		<?php 
		}
	?>
	</ul>
	<?php
	if(strtolower(implode('',$original_colors_temp)) != strtolower(implode('',$current_colors))) :?>
	<ul class="colorschema" title="<?php _e('current', 'mymail')?>">
	<?php 
		foreach($colors as $color){
		?>	
		<li class="colorschema-field" data-hex="<?php echo strtolower($color)?>" style="background-color:<?php echo $color?>"></li>
		<?php 
		}
	?>
	</ul>	
	<?php 
	endif;
		
	if(isset($customcolors[$this->get_template()])){
		foreach($customcolors[$this->get_template()] as $hash => $colorschema){
		?>
		<ul class="colorschema custom" data-hash="<?php echo $hash?>">
		<?php
			foreach($colorschema as $color){
			?>	
			<li class="colorschema-field" data-hex="<?php echo strtolower($color)?>" style="background-color:<?php echo $color?>"></li>
			<?php 
			}
		?>
			<li class="colorschema-delete-field"><a class="colorschema-delete">&#10005;</a></li>
		</ul>
		<?php
		}
	}
	
	if(is_dir(MYMAIL_DIR.'/templates/'.$this->get_template().'/img/version1')){
		
		$folder = MYMAIL_DIR.'/templates/'.$this->get_template().'/img';
		$versions = array();
		if ( $dir = @opendir( $folder ) ) {
			while (($file = readdir( $dir ) ) !== false ) {
				if ( in_array($file, array('.', '..')) || is_file($folder . '/' .$file) )
					continue;
				if ( is_dir( $folder . '/' . $file ) && preg_match( '#^version#', $file) ) {
					$versions[] = $file;
				}
			}
		}
		@closedir( $dir );
		sort($versions);
	?>	
	<hr>
	<label><?php echo _x('Elements', 'the different versions (color) of the newsletter', 'mymail') ?></label><br>
	<select class="widefat" id="mymail_version" name="mymail_data[version]">
	<?php
		foreach($versions as $version){
		$value = (isset($this->post_data['version'])) ? $this->post_data['version'] : 'version1';
		?>
		<option class="<?php echo $version?>"<?php if($version == $value) echo ' selected';?>><?php echo $version?></option>	
		<?php 
		}
	?>
	</select>	
	<?php 
	}
	?>
	<hr>
	<label><?php _e('Background', 'mymail') ?></label><br>
	<?php 
		$value = (isset($this->post_data['background']) && $this->post_data['template'] == $this->get_template()) ? $this->post_data['background'] : '';
	?>
	<input type="hidden" id="mymail_background" name="mymail_data[background]" value="<?php echo $value?>">
	<ul class="backgrounds">
		<li><a style="background-image:<?php echo ($value == 'none' || empty($value)) ? 'none' : 'url('.$value.')'?>"></a>
		<?php
		
			$custombgs = MYMAIL_UPLOAD_DIR.'/backgrounds';
			$custombgsuri = MYMAIL_UPLOAD_DIR.'/backgrounds';
				
			if(!is_dir($custombgs)) wp_mkdir_p($custombgs);

			if($files = list_files($custombgs)){
			
		?>
			<ul data-base="<?php echo $custombgsuri?>">
		<?php
			sort($files);
			foreach($files as $file){
				if(!in_array(strrchr($file, '.'), array('.png', '.gif', '.jpg', '.jpeg'))) continue;
				$value = (isset($this->post_data['background'])) ? $this->post_data['background'] : false;
				$file = str_replace($custombgs,'', $file);
				?>	
				<li><a title="<?php echo basename($file);?>" data-file="<?php echo $file?>" style="background-image:url(<?php echo $custombgsuri.$file?>)"<?php if($custombgsuri.$file == $value) echo ' class="active"';?>>&nbsp;</a></li>
				<?php 
			}
			?> 
			</ul>
		<?php 
			}
		?>
			<ul data-base="<?php echo MYMAIL_URI?>">
				<li><a title="<?php _e('none', 'mymail') ?>" data-file="" <?php if(!$value) echo ' class="active"';?>><?php _e('none', 'mymail') ?></a></li>
		<?php 
			$files = list_files(MYMAIL_DIR.'/assets/img/bg');
			sort($files);
			foreach($files as $file){
				if(!in_array(strrchr($file, '.'), array('.png', '.gif', '.jpg', '.jpeg'))) continue;
				$value = (isset($this->post_data['background'])) ? $this->post_data['background'] : false;
				$file = str_replace(MYMAIL_DIR,'', $file);
				?>	
				<li><a title="<?php echo basename($file);?>" data-file="<?php echo $file?>" style="background-image:url(<?php echo MYMAIL_URI.$file?>)"<?php if(MYMAIL_URI.$file == $value) echo ' class="active"';?>>&nbsp;</a></li>
				<?php 
			}
		?>
			</ul>
		</li>
		
	</ul>
	<p class="howto"><?php _e('background images are not displayed on all clients!', 'mymail') ?></p>
	<hr>
<?php else : ?>
	
	<p><?php if($this->post_data['embed_images']){?>&#10004;<?php }else{ ?>&#10005;<?php }?> <?php _e('Embedded Images', 'mymail') ?></p>
	<label><?php _e('Colors Schema', 'mymail') ?></label><br>
	<ul class="colorschema finished">
	<?php
		$colors = $this->post_data['newsletter_color'];
		foreach($colors as $color){
		?>	
		<li data-hex="<?php echo $color?>" style="background-color:<?php echo $color?>"></li>
		<?php 
		}
	?>
	</ul>	
	<hr>
	<?php if($this->post_data['background']){
		$file = $this->post_data['background'];	
	?>
	<label><?php _e('Background', 'mymail') ?></label><br>
	<ul class="backgrounds finished">
		<li><a title="<?php echo basename($file);?>" style="background-image:url(<?php echo $file?>)"></a></li>
	</ul>
	<?php } ?>
	
<?php endif; ?>
