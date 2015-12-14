<?php 
	
	require_once MYMAIL_DIR.'/classes/templates.class.php';
	$t = new mymail_templates();
	$templates = $t->get_templates();
	
	$notice = false;
	
/*  Maybe later
	$updates = $t->get_updates();
	
	if($updates) $notice[] = _n(sprintf('An update for %s is available.', '"'.$templates[array_pop(array_keys($updates))]['name'].'"') , sprintf('For %d templates updates are available.', count($updates)), count($updates)).' '.'Please go to the themeforest download page to get the new version';	
*/

	if(isset($_GET['action'])){
		switch($_GET['action']){
			case 'activate':
				$slug = esc_attr($_GET['template']);
				if (isset($templates[$slug]) && wp_verify_nonce($_GET['_wpnonce'], 'activate-'.$slug) && current_user_can('mymail_manage_templates') ){
					mymail_update_option('default_template', esc_attr($_GET['template']));	
					$notice[] = sprintf(__('Template %s is now your default template', 'mymail'), '"'.$templates[$slug]['name'].'"');
				}
				break;
			case 'upload':
				if (wp_verify_nonce($_POST['_wpnonce'], 'upload-template') && current_user_can('mymail_upload_templates')){
					$result = $t->upload_template();
					if(!isset($result['error'])){
						$templates = $t->get_templates();
						$notice[] = __('Template uploaded', 'mymail');	
					}else{
						$notice[] = $result['error'];	
					}
				}
				break;
			case 'delete':
				$slug = esc_attr($_GET['template']);
				if (isset($templates[$slug]) && wp_verify_nonce($_GET['_wpnonce'], 'delete-'.$slug) && current_user_can('mymail_delete_templates')){
					if($slug == mymail_option('default_template')){
						$notice[] = sprintf(__('Cannot delete this template', 'mymail'), '"'.$templates[$slug]['name'].'"');
					}else if($t->remove_template($slug)){
						$notice[] = sprintf(__('Template %s deleted', 'mymail'), '"'.$templates[$slug]['name'].'"');
						$templates = $t->get_templates();
					}else{
						$notice[] = sprintf(__('Template %s NOT deleted', 'mymail'), '"'.$templates[$slug]['name'].'"');
					}
				}
				break;
				
		}
	}


	
	$uploadpage = isset($_GET['upload']);	
?>
<div class="wrap">
<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
<h2 class="nav-tab-wrapper">
	<a class="nav-tab <?php if(!$uploadpage || !current_user_can('mymail_upload_templates')){?>nav-tab-active<?php }?>" href="edit.php?post_type=newsletter&page=mymail_templates"><?php _e('Templates', 'mymail')?></a>
	<?php if(current_user_can('mymail_upload_templates')){ ?>
	<a class="nav-tab <?php if($uploadpage){?>nav-tab-active<?php }?>" href="edit.php?post_type=newsletter&page=mymail_templates&upload=1"><?php _e('Upload', 'mymail') ?></a>
	<?php } ?>
</h2>
<div id="mymail_templates">
<?php
	$slug = mymail_option('default_template', 'mymail');
	if(!isset($templates[$slug])){
		$slug = 'mymail';
		mymail_update_option('default_template', 'mymail');
		$notice[] = sprintf(__('Template %s is missing or broken. Reset to default', 'mymail'), '"'.$slug.'"');
		
		//mymail tempalte is missing 0> redownload it
		if(!isset($templates[$slug])){
			$t->renew_default_template();
			$templates = $t->get_templates();
		}
	}
	$template = $templates[$slug];
?>
<?php
wp_nonce_field('mymail_nonce');
if($notice){
	foreach($notice as $note){?>
<div class="updated"><p><?php echo $note ?></p></div>
<?php }
}?>

<?php if (!$uploadpage || !current_user_can('mymail_upload_templates')){?>
<h2><?php _e('Default Template', 'mymail') ?></h2>
<div id="current-theme">
	<div class="preview">
	<?php

	$files = $t->get_files($slug);
	foreach($files as $name => $data){
		?>	
		
	<a class="thickbox thickbox-preview" href="<?php echo $t->url .'/'. $slug .'/'.$name?>?preview_iframe=1&amp;TB_iframe=true&amp;width=700&amp;height=700">
		<img alt="<?php _e('Default Template', 'mymail') ?>" src="<?php echo $t->get_screenshot($slug, $name)?>" />
		<?php if(!in_array($name, array('index.html', 'notification.html')) && current_user_can('mymail_delete_templates')) : ?><span class="remove-file" data-file="<?php echo $slug .'/'.$name ?>" title="<?php echo sprintf(__('remove file %s', 'mymail'), $name ); ?>" onclick="return confirm(<?php echo "'".esc_html(sprintf(__('You are about to delete the file %s', 'mymail'), $name))."'" ?> );">&#10005;</span><?php endif; ?>
		<span class="caption"><?php echo $name ?></span>
	</a>
	
	<?php 
	
	}
?>	
	</div>
	<h4><?php echo $template['name'].' '.$template['version']?> by <a href="<?php echo $template['author_uri']?>"><?php echo $template['author']?></a></h4>
	<?php if(isset($template['description'])){?><p class="description"><?php echo $template['description']?></p><?php }?>
	<br class="clear">
</div>
<div id="templateeditor">
<input type="hidden" id="slug">
<input type="hidden" id="file">

	<div class="nav-tab-wrapper">
	</div>
	<div class="inner">
		<div class="edit-buttons">
			<span class="spinner template-ajax-loading"></span>
			<span class="message"></span>
			<button class="button-primary save"><?php _e('Save')?></button> <?php _e('or', 'mymail') ?> 
			<a class="cancel" href="#"><?php _e('Cancel')?></a>
		</div>
			<textarea class="editor"></textarea>
		<div class="edit-buttons">
			<span class="message"></span>
			<span class="spinner template-ajax-loading"></span>
			<button class="button-primary save"><?php _e('Save')?></button> <?php _e('or', 'mymail') ?> 
			<a class="cancel" href="#"><?php _e('Cancel')?></a>
		</div>
	</div>
<br class="clear">
</div>
<h2><?php _e('Available Templates', 'mymail') ?> <a href="http://rxa.li/mymailtemplates" class="button"> <?php _e('get more', 'mymail'); ?> </a></h2>
<div id="availablethemes">

<?php 
	$i = 0;
	foreach($templates as $slug => $data){
		?>	
		<div class="available-theme" id="theme_<?php echo $i?>" data-id="<?php echo $i++?>">
			<?php if(isset($updates[$slug])){?>
				<span class="update-badge"><?php echo $updates[$slug]?></span>
			<?php }?>
			<a class="thickbox thickbox-preview screenshot" title="<?php echo $data['name'].' '.$data['version']?> by <?php echo $data['author']?>" href="<?php echo $t->url .'/' .$slug .'/index.html'?>?preview_iframe=1&amp;TB_iframe=true&amp;width=700&amp;height=700">
				<img alt="" src="<?php echo $t->get_screenshot($slug)?>" width="300">
			</a>
			<h3><?php echo $data['name'].' '.$data['version']?> by <a href="<?php echo $data['author_uri']?>"><?php echo $data['author']?></a></h3>
			<?php if(isset($data['description'])){?><p class="description"><?php echo $data['description']?></p><?php }?>
			<div class="action-links">
			<ul>
				<li><a title="Set &quot;<?php echo $data['name'] ?>&quot; as default" class="activatelink" href="edit.php?post_type=newsletter&amp;page=mymail_templates&amp;action=activate&amp;template=<?php echo $slug?>&amp;_wpnonce=<?php echo wp_create_nonce('activate-'.$slug)?>"><?php _e('Use as default', 'mymail'); ?></a></li>
			 	<li><a title="Preview &quot;<?php echo $data['name'] ?>&quot;" class="thickbox thickbox-preview" href="<?php echo $t->url .'/' .$slug .'/index.html'?>?preview_iframe=1&amp;TB_iframe=true&amp;width=700&amp;height=700">Preview</a></li>
			 	<?php if(current_user_can('mymail_edit_templates')){ 
				 	$writeable = is_writeable($t->path .'/'.$slug .'/index.html');
			 	?>
				<li><a title="Edit &quot;<?php echo $data['name'] ?>&quot;" class="edit <?php echo (!$writeable ? 'disabled' : '')?>" data-slug="<?php echo $slug?>" href="<?php echo $slug .'/index.html'?>" <?php if(!$writeable) :?>onclick="alert('<?php _e('This file is no writeable! Please change the file permission', 'mymail'); ?>');return false;"<?php endif; ?>><?php _e('Edit HTML', 'mymail') ?></a></li>
				<?php }?>
			</ul>
			<?php if($slug != mymail_option('default_template') && current_user_can('mymail_delete_templates')) { ?>
				<div class="delete-theme">
					<a onclick="return confirm(<?php echo "'".esc_html(sprintf(__('You are about to delete this template "%s"', 'mymail'), $data['name']))."'" ?> );" href="edit.php?post_type=newsletter&amp;page=mymail_templates&amp;action=delete&amp;template=<?php echo $slug?>&amp;_wpnonce=<?php echo wp_create_nonce('delete-'.$slug)?>" class="submitdelete deletion">Delete</a>
				</div>
			<?php }?>
			</div>
		</div>
		<?php
	}
	
?>
</div>

<?php }else{
?>
<h2><?php _e('Upload a template in .zip format', 'mymail') ?></h2>
<p class="install-help"><?php _e('Please upload the whole zip file here.', 'mymail') ?></p>
<form action="edit.php?post_type=newsletter&page=mymail_templates&action=upload" enctype="multipart/form-data" method="post">
	<?php wp_nonce_field('upload-template');?>
	<input type="file" name="templatefile">
	<input type="hidden" name="action" value="wp_handle_upload">
	<input type="submit" value="<?php _e('Upload', 'mymail') ?>" class="button" id="install-template-submit" name="install-template-submit">
</form>
<?php } ?>
<div id="ajax-response"></div>
<br class="clear">
</div>
