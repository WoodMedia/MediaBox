<?php 
		
		require_once MYMAIL_DIR.'/classes/templates.class.php';
		$t = new mymail_templates();
		$templates = $t->get_templates();
		
if($camps = mymail_get_finished_campaigns(array( 'posts_per_page' => 10, 'post_status' => array('finished', 'active')))){
		$campaign_data = get_post_meta( $camps[0]->ID, 'mymail-campaign', true );
		
	?>
<div class="stats table_content <?php if($camps[0]->post_status == 'active') echo "isactive";?>" id="stats_cont">
	<p class="sub"><?php _e('Recent Campaign', 'mymail') ?> <a class="prev_camp disabled"></a> <a id="camp_name" href="post.php?post=<?php echo $camps[0]->ID ?>&action=edit" title="<?php _e('edit')?>"><?php echo $camps[0]->post_title?></a> <a class="next_camp<?php if(count($camps) <= 1) echo ' disabled';?>"></a></p>
	
<table id="stats">
	<tr><th width="60"><?php echo ($camps[0]->post_status == 'active') ? __('sent', 'mymail') : __('total', 'mymail'); ?></th><th><?php _e('opens', 'mymail') ?></th><th><?php echo _n("clicks", "clicks", 2, 'mymail') ?></th><th><?php echo _x('unsubscribes', 'count of', 'mymail') ?></th><th><?php _e('bounces', 'mymail') ?></th></tr>
	<tr>
	<td align="right"><span class="verybold" id="stats_total"><?php echo ($campaign_data['sent'])?></span></td>
	<td width="100"><div id="stats_open" class="piechart" data-value="<?php echo $campaign_data['opens']?>" data-total="<?php echo $campaign_data['sent']?>"></div></td>
	<td width="100"><div id="stats_clicks" class="piechart" data-value="<?php echo $campaign_data['totaluniqueclicks']?>" data-total="<?php echo $campaign_data['opens']?>"></div></td>
	<td width="100"><div id="stats_unsubscribes" class="piechart" data-value="<?php echo $campaign_data['unsubscribes']?>" data-total="<?php echo $campaign_data['opens']?>"></div></td>
	<td width="100"><div id="stats_bounces" class="piechart" data-value="<?php echo $campaign_data['hardbounces']?>" data-total="<?php echo $campaign_data['sent']+$campaign_data['hardbounces']?>"></div></td>
	</tr>
</table>
<div class="info"><p><?php _e('This Campaign is currently progressing', 'mymail')?></p></div>
		<?php foreach($camps as $camp){
			$campaign_data = get_post_meta( $camp->ID, 'mymail-campaign', true );
			
			
			unset($campaign_data['clicks']);
			
			$tempdata = $campaign_data;
			unset($tempdata['countries']);
			unset($tempdata['cities']);
			?>
<div class="camp" data-id="<?php echo $camp->ID?>" data-active="<?php echo ($camp->post_status == 'active')?>" data-name="<?php echo $camp->post_title?>" data-data='<?php echo json_encode($tempdata)?>'></div>
		<?php }?>
</div>
<?php }?>
<div class="table table_content">
	<p class="sub"><?php
		$camps = wp_count_posts( 'newsletter', 'readable' );
		$total_camps = $camps->pending+$camps->active+$camps->queued+$camps->finished+$camps->paused+$camps->draft; 
		echo number_format_i18n($total_camps) ?><?php echo sprintf( _n( '%n Campaign', '%n Campaigns', $total_camps, 'mymail'), $total_camps); ?>
		(<a href="post-new.php?post_type=newsletter"><?php _e('new') ?></a>)
	</p>
	<?php if($total_camps):?>
	<table>
		<tbody>
		<?php if($camps->pending){?>
			<tr><td class="b"><a href="edit.php?post_status=pending&post_type=newsletter"><?php echo number_format_i18n($camps->pending) ?></a></td><td class="t"><a class="waiting" href="edit.php?post_status=pending&post_type=newsletter"><?php echo sprintf( _n( '%n Pending Campaign', '%n Pending Campaigns', $camps->pending, 'mymail'), $camps->pending) ?></a></td></tr>
		<?php }?>
		<?php if($camps->active){?>
			<tr><td class="b"><a href="edit.php?post_status=active&post_type=newsletter"><?php echo number_format_i18n($camps->active) ?></a></td><td class="t"><a class="active" href="edit.php?post_status=active&post_type=newsletter"><?php echo sprintf( _n( '%n Active Campaign', '%n Active Campaigns', $camps->active, 'mymail'), $camps->active) ?></a></td></tr>
		<?php }?>
		<?php if($camps->queued){?>
			<tr><td class="b"><a href="edit.php?post_status=queued&post_type=newsletter"><?php echo number_format_i18n($camps->queued) ?></a></td><td class="t"><a class="queued" href="edit.php?post_status=queued&post_type=newsletter"><?php echo sprintf( _n( '%n Queued Campaign', '%n Queued Campaigns', $camps->queued, 'mymail'), $camps->queued) ?></a></td></tr>
		<?php }?>
		<?php if($camps->finished){?>
			<tr><td class="b"><a href="edit.php?post_status=finished&post_type=newsletter"><?php echo number_format_i18n($camps->finished) ?></a></td><td class="t"><a class="finished" href="edit.php?post_status=finished&post_type=newsletter"><?php echo sprintf( _n( '%n Finished Campaign', '%n Finished Campaigns', $camps->finished, 'mymail'), $camps->finished) ?></a></td></tr>
		<?php }?>
		<?php if($camps->paused){?>
			<tr><td class="b"><a href="edit.php?post_status=paused&post_type=newsletter"><?php echo number_format_i18n($camps->paused) ?></a></td><td class="t"><a href="edit.php?post_status=paused&post_type=newsletter"><?php echo sprintf( _n( '%n Paused Campaign', '%n Paused Campaigns', $camps->paused, 'mymail'), $camps->paused) ?></a></td></tr>
		<?php }?>
		<?php if($camps->draft){?>
			<tr><td class="b"><a href="edit.php?post_status=draft&post_type=newsletter"><?php echo number_format_i18n($camps->draft) ?></a></td><td class="t"><a href="edit.php?post_status=draft&post_type=newsletter"><?php echo sprintf( _n( '%n Drafted Campaign', '%n Drafted Campaigns', $camps->draft, 'mymail'), $camps->draft) ?></a></td></tr>
		<?php }?>
		</tbody>
	</table>
	<?php else:?>
	<div class="info">
		<p><?php _e("Crap! You don't have any Campaigns yet", 'mymail')?><br>
		<a href="post-new.php?post_type=newsletter"><?php _e("create a new Campaign", 'mymail')?></a></p>
	</div>
	<?php endif;?>
</div>
<div class="table table_content">
	<p class="sub"><?php
		$subscribers = wp_count_posts( 'subscriber', 'readable' );
		$total_subscribers = $subscribers->subscribed+$subscribers->unsubscribed+$subscribers->hardbounced;?>
		<?php echo number_format_i18n($total_subscribers) ?><?php echo sprintf( _n( '%n Subscriber', '%n Subscribers', $total_subscribers, 'mymail'), $total_subscribers) ?>
	</p>
	<div class="subscriber_stats_wrap">
	<?php if($subscribers):?>
	<table>
		<tbody>
		<?php if($subscribers->subscribed){?>
			<tr><td class="b"><a href="edit.php?post_status=subscribed&post_type=subscriber"><?php echo number_format_i18n($subscribers->subscribed) ?></a></td><td class="t"><a href="edit.php?post_status=subscribed&post_type=subscriber"><?php echo sprintf( _n( '%n Active Subscribers', '%n Active Subscribers', $subscribers->subscribed, 'mymail'), count($subscribers)) ?></a></td></tr>
		<?php }?>
		<?php if($subscribers->unsubscribed){?>
			<tr><td class="b"><a href="edit.php?post_status=unsubscribed&post_type=subscriber"><?php echo number_format_i18n($subscribers->unsubscribed) ?></a></td><td class="t"><a href="edit.php?post_status=unsubscribed&post_type=subscriber"><?php echo sprintf( _n( '%n Unsubscribed Subscribers', '%n Unsubscribed Subscribers', count($subscribers), 'mymail'), $subscribers->unsubscribed) ?></a></td></tr>
		<?php }?>
		<?php if($subscribers->hardbounced){?>
			<tr><td class="b"><a href="edit.php?post_status=hardbounced&post_type=subscriber"><?php echo number_format_i18n($subscribers->hardbounced) ?></a></td><td class="t"><a href="edit.php?post_status=hardbounced&post_type=subscriber"><?php echo sprintf( _n( '%n Hardbounced Subscribers', '%n Hardbounced Subscribers', count($subscribers), 'mymail'), $subscribers->hardbounced) ?></a></td></tr>
		<?php }?>
		</tbody>
	</table>
	<?php endif;?>
	</div>
</div>
<br class="clear">
	<?php 
	$counts = get_option('mymail_subscribers_count', array());
	
	if(!empty($counts)) : 
		//$counts =asort($counts);
		$today = floor(current_time('timestamp')/86400);
		$number = 7;
		$firstday = $today-$number;
		$data = array();
		$maxvalue = 0;
		for($day = $today; $day >= $today-$number; $day--){
			$sub = isset($counts[$day]) ? $counts[$day]['sub'] : 0;
			$unsub = isset($counts[$day]) ? $counts[$day]['unsub'] : 0;
			$clicks = isset($counts[$day]) ? $counts[$day]['click'] : 0;
			$data[] = "['".__(date('D', ($day)*86400))."', ".$unsub.",".$sub.",".$clicks."]";	
			$maxvalue = max($maxvalue, $sub, $unsub, $clicks);
		}
		?>
	
		<script type="text/javascript">google.load('visualization', '1', {'packages':['geochart', 'corechart']});</script>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var data = google.visualization.arrayToDataTable([
				['<?php _e('Date') ?>','<?php _e('New unsubscribers', 'mymail') ?>','<?php _e('New subscribers', 'mymail') ?>','<?php _e('Clicks', 'mymail') ?>'],
				<?php echo implode(',', $data); ?>]),
			mapoptions = {
				legend: {
					position: 'none'
				},
				chartArea: {width: '90%', height: '75%'},
				lineWidth: 2,
				 backgroundColor: {
					fill: '#F6F6F6',
					stroke: null,
					strokeWidth: 0
				},
				vAxis: {minValue:0, format:'#', logScale: 1},
				colors: ['#D54E21', '#21759B', '#d7f1fc'],
				width: '100%',
				height: 250
			};
			var ac = new google.visualization.ColumnChart(document.getElementById('dashboard_chart'));
			ac.draw(data, mapoptions);
		});
		</script>
		
	<div id="dashboard_chart">
	</div>
	<ul class="legend">
		<li class="clicks"><span></span> <?php _e('Clicks', 'mymail') ?></li>
		<li class="sub"><span></span> <?php _e('New subscribers', 'mymail') ?></li>
		<li class="unsub"><span></span> <?php _e('New unsubscribers', 'mymail') ?></li>
	</ul>
	<br class="clear">
<?php endif;?>
<div class="versions">
	<span id="wp-version-message"><?php echo sprintf(__('You are using %s' ,'mymail'), '<strong>MyMail '.MYMAIL_VERSION.'</strong>') ?>.</span>
	<?php 
	if(current_user_can('update_plugins') && !is_plugin_active_for_network(MYMAIL_SLUG)){
		$plugins = get_site_transient('update_plugins');
		if(!$camps->active && isset($plugins->response[MYMAIL_SLUG]) && version_compare( $plugins->response[MYMAIL_SLUG]->new_version, MYMAIL_VERSION, '>' ) ) {
	?>
	<a href="update.php?action=upgrade-plugin&plugin=<?php echo urlencode(MYMAIL_SLUG);?>&_wpnonce=<?php echo wp_create_nonce('upgrade-plugin_' . MYMAIL_SLUG)?>" class="button"><?php printf( __('Update to %s'), $plugins->response[MYMAIL_SLUG]->new_version ? $plugins->response[MYMAIL_SLUG]->new_version : __( 'Latest' ) )?></a>
	<?php 
		}
	}

	?>
</div>