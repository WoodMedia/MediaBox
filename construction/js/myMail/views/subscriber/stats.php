<?php 
if($this->campaign_data){
	
	$campaigns = array_reverse($this->campaign_data, true);
	$deleted = 0;
	
	foreach($campaigns as $id => $data){
		$campaign = get_post($id);
		if(!$campaign){
			$deleted++;
			continue;
		}
?>
	<div class="campaing-stats">
	<h4 class="campaign-title"><?php echo $campaign->post_title?> <a href="post.php?post=<?php echo $campaign->ID ?>&action=edit">view</a></h4>
	<table class="wp-list-table widefat">
		<tr class="strong"><td><?php _e('sent', 'mymail') ?></td><td><?php _e('opened', 'mymail') ?></td><td><?php _e('first click', 'mymail') ?></td><td><?php _e('clicks', 'mymail') ?></td><td><?php _e('unique clicks', 'mymail') ?></td></tr>
		<tr class="alternate">
		<td width="20%"><?php echo isset($this->campaign_data[$id]['sent']) ? date(get_option('date_format').' '.get_option('time_format'), $this->campaign_data[$id]['timestamp']) : _e('not yet', 'mymail') ?></td>
		<td width="20%"><?php echo isset($this->campaign_data[$id]['open']) ? date(get_option('date_format').' '.get_option('time_format'), $this->campaign_data[$id]['open']) : _e('not yet', 'mymail') ?></td>
		<td width="20%"><?php echo isset($this->campaign_data[$id]['firstclick']) ? date(get_option('date_format').' '.get_option('time_format'), $this->campaign_data[$id]['firstclick']) : _e('never', 'mymail') ?></td>
		<td width="20%"><?php echo isset($this->campaign_data[$id]['totalclicks']) ? $this->campaign_data[$id]['totalclicks'] : '-' ?></td>
		<td width="20%"><?php echo isset($this->campaign_data[$id]['totaluniqueclicks']) ? $this->campaign_data[$id]['totaluniqueclicks'] : '-' ?></td>
		</tr>
		<tr>
			<td valign="top"><?php _e('Clicks', 'mymail') ?></td>
			<td colspan="4" valign="top">
			<?php if(isset($this->campaign_data[$id]['clicks'])){?>
				<ul>
			<?php 
			foreach($this->campaign_data[$id]['clicks'] as $uri => $count){
				echo '<li><a href="'.$uri.'">'.$uri.'</a> ('.$count.')</li>';	
			}
			?>
				</ul>
			<?php
			
			}else{ _e('No clicks yet', 'mymail');}?>
			</td>
		</tr>
	</table>
	</div>
<?php
	}
	if($deleted) echo '<p class="howto">'.sprintf (_n('%d campaign was deleted', '%d campaigns where deleted', $deleted, 'mymail'), $deleted).'</p>';
?>
<?php
}else{
?>
	<p class="howto"><?php _e('This subscriber has never recieved any newsletter', 'mymail') ?></p>
<?php
}
?>
