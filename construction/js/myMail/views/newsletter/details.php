<?php 
	$editable = !in_array($post->post_status, array('active', 'finished'));
	
?>


<?php if($editable) :?>
<table class="form-table">
		<tbody>
		
		<tr valign="top">
			<th scope="row"><?php _e('Subject', 'mymail') ?></th>
			<td><input type="text" class="widefat" value="<?php echo (isset($this->post_data['subject'])) ? $this->post_data['subject'] : ''?>" name="mymail_data[subject]" id="mymail_subject"></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Preheader', 'mymail') ?></th>
			<td><input type="text" class="widefat" value="<?php echo (isset($this->post_data['preheader'])) ? $this->post_data['preheader'] : ''?>" name="mymail_data[preheader]" id="mymail_preheader"></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('From Name', 'mymail') ?> <a class="default-value" data-for="mymail_from-name" data-value="<?php echo mymail_option('from_name')?>" title="<?php _e('restore default', 'mymail') ?>"></a></th>
			<td><input type="text" class="widefat" value="<?php echo (isset($this->post_data['from_name'])) ? $this->post_data['from_name'] : mymail_option('from_name')?>" name="mymail_data[from_name]" id="mymail_from-name"></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('From Email', 'mymail') ?> <a class="default-value" data-for="mymail_from" data-value="<?php echo mymail_option('from')?>" title="<?php _e('restore default', 'mymail') ?>"></a></th>
			<td><input type="text" class="widefat" value="<?php echo (isset($this->post_data['from'])) ? $this->post_data['from'] : mymail_option('from')?>" name="mymail_data[from]" id="mymail_from"></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('reply-to email', 'mymail') ?> <a class="default-value" data-for="mymail_reply_to" data-value="<?php echo mymail_option('reply_to')?>" title="<?php _e('restore default', 'mymail') ?>"></a></th>
			<td><input type="text" class="widefat" value="<?php echo (isset($this->post_data['reply_to'])) ? $this->post_data['reply_to'] : mymail_option('reply_to')?>" name="mymail_data[reply_to]" id="mymail_reply_to"></td>
		</tr>
	 </tbody>
</table>
<input type="hidden" value="<?php echo $this->get_template() ?>" name="mymail_data[template]" id="mymail_template">
<input type="hidden" value="<?php echo $this->get_file() ?>" name="mymail_data[file]" id="mymail_template">


<?php else : ?>
<?php 
	$totals = $this->campaign_data['sent'];
?>

<table>
	<tr><th width="16.666%"><?php _e('Subject', 'mymail') ?></th><td><?php echo $this->post_data['subject'];?></td></tr>
	<tr><th><?php _e('Date', 'mymail') ?></th><td><?php echo date(get_option('date_format').' '.get_option('time_format'),  $this->post_data['timestamp']);
	if($post->post_status == 'finished') echo ' &ndash; '.date(get_option('date_format').' '.get_option('time_format'), $this->campaign_data['timestamp']);?>
	</td></tr>
	<tr><th><?php _e('Preheader', 'mymail') ?></th><td><?php echo $this->post_data['preheader']?></td></tr>
	<tr><th><?php _e('Total Recipients', 'mymail') ?></th><td> <span class="big"><?php echo number_format_i18n($this->campaign_data['total']) ?></span>
<?php if(!empty($this->campaign_data['sent'])) :?>	
	<a href="#" id="show_recipients" class="alignright"><?php _e('show details' ,'mymail') ?></a>
	<span class="spinner" id="recipients-ajax-loading"></span><div id="recipients-list"></div>
<?php endif; ?>
	</td></tr>
<?php if(!empty($this->campaign_data['errors'])) :?>	
	<tr><th><?php _e('Total Errors', 'mymail') ?></th><td> <span class="big"><?php echo number_format_i18n($this->campaign_data['totalerrors']) ?></span> <a href="#" id="show_errors" class="alignright"><?php _e('show details' ,'mymail') ?></a>
	<span class="spinner" id="errors-ajax-loading"></span>
	<div id="error-list">
		<table class="wp-list-table widefat"><tbody>
		<?php $i = 1; foreach($this->campaign_data['errors'] as $email => $error){?>	
		<tr <?php if($i%2) echo ' class="alternate"'; ?>><td><?php echo $i++ ?></td><td><?php echo $email ?></td><td class="error"><?php echo $error ?></td></tr>
		<?php } ?>
		</tbody>
		</table>
	</div></td></tr>
	</td></tr>
<?php endif; ?>
	<tr><th><?php _e('Total Clicks', 'mymail') ?></th><td> <span class="big"><?php echo number_format_i18n($this->campaign_data['totalclicks']) ?></span>
	<?php if(!empty($this->campaign_data['clicks'])) :?>
		<a href="#" id="show_clicks" class="alignright"><?php _e('show details' ,'mymail') ?></a>
		<div id="click-list">
		<table class="wp-list-table widefat"><tbody><?php
		$links = array();
		foreach($this->campaign_data['clicks'] as $link => $count){
			$links[$link] = array_sum($count);
		}
		arsort($links);
		$i = 1;
		foreach($links as $link => $count){
			echo '<tr '.(($i%2) ? ' class="alternate"' : '').'><td>'.sprintf( _n( '%s click', '%s clicks', $count, 'mymail'), $count).'</td><td>'. round(($count/$this->campaign_data['totalclicks']*100),2).' %</td><td><a href="'.$link.'" class="external clicked-link">'.$link.'</a></td></tr>';
		}
	
		?></tbody>
		</table></div>
	<?php endif; ?>
		</td></tr>
</table>
<table id="stats">
	<tr>
	<td><span class="verybold"><?php echo $totals?></span> <?php echo _n('receiver', 'receivers', $totals, 'mymail')?></td>
	<td width="60"><div id="stats_open" class="piechart" data-value="<?php echo $this->campaign_data['opens']?>" data-total="<?php echo $totals?>"></div></td>
	<td><span class="verybold"><?php echo $this->campaign_data['opens']?></span> <?php echo _n('opened', 'opens', $this->campaign_data['opens'], 'mymail')?></td>
	<td width="60"><div id="stats_click" class="piechart" data-value="<?php echo $this->campaign_data['totaluniqueclicks']?>" data-total="<?php echo $this->campaign_data['opens']?>"></div></td>
	<td><span class="verybold"><?php echo $this->campaign_data['totaluniqueclicks']?></span> <?php echo _n('clicks', 'clicks', $this->campaign_data['totaluniqueclicks'], 'mymail')?></td>
	<td width="60"><div id="stats_unsubscribes" class="piechart" data-value="<?php echo $this->campaign_data['unsubscribes']?>" data-total="<?php echo $this->campaign_data['opens']?>"></div></td>
	<td><span class="verybold"><?php echo $this->campaign_data['unsubscribes']?></span> <?php echo _n('unsubscribe', 'unsubscribes', $this->campaign_data['unsubscribes'], 'mymail')?></td>
	<td width="60"><div id="stats_bounces" class="piechart" data-value="<?php echo $this->campaign_data['hardbounces']?>" data-total="<?php echo $totals+$this->campaign_data['hardbounces']?>"></div></td>
	<td><span class="verybold"><?php echo $this->campaign_data['hardbounces']?></span> <?php echo _n('bounce', 'bounces', $this->campaign_data['hardbounces'], 'mymail')?></td>
	</tr>
</table>
	
<?php if(mymail_option('trackcountries') && $this->campaign_data['countries'] ) :
	require_once MYMAIL_DIR.'/classes/libs/Ip2Country.php';
	$ip2Country = new Ip2Country();
	?>
	<div id="countries_wrap">
	<div id="countries_map"></div>
		<a class="zoomout button" title="<?php _e('back to world view', 'mymail'); ?>">&nbsp;</a>
		<div id="mapinfo"></div>
	<div id="countries_table">
		<table class="wp-list-table widefat">
		<tbody>
		<?php
		
		$k = 0;
			
			arsort($this->campaign_data['countries']);	

			foreach($this->campaign_data['countries'] as $countrycode => $count){
				$flag = '/assets/img/flags/'.strtolower($countrycode).'.gif';
				if(!file_exists(MYMAIL_DIR.$flag)) $flag = '/assets/img/flags/unknown.gif';
				$k+=$count;
			?>
			<tr data-code="<?php echo $countrycode ?>" id="country-row-<?php echo $countrycode ?>"><td width="20"><img src="<?php echo MYMAIL_URI.$flag ?>" width="16" height="12" alt=""></td><td width="100%"><?php echo $ip2Country->country($countrycode);?></td><td><?php echo $count?></td></tr>
			<?php 
			}
		?>
		</tbody>
		</table>
	</div>
	<?php 
	$countries = $ip2Country->get_contries();
	?>
	<script type="text/javascript">
	google.load('visualization', '1', {'packages':['geochart', 'corechart']});
		
	jQuery(window).ready(function($) {
	
		var countries = {<?php echo implode(array_map(create_function('$key, $value', 'return $key.":\"".utf8_encode($value)."\"";'), array_keys($countries), array_values($countries)), ','); ?>},
			data = countrydata = google.visualization.arrayToDataTable([
				['<?php _e('Country', 'mymail') ?>', '<?php _e('open', 'mymail') ?>'],<?php 
				echo implode(array_map(create_function('$key, $value', 'return "[\'".$key."\', ".$value."]";'), array_keys($this->campaign_data['countries']), array_values($this->campaign_data['countries'])), ',');
			?>]),
			citydata = {<?php
	$city_data = $city_data_unknown = array();
	if(isset($this->campaign_data['cities'])) {
		foreach($this->campaign_data['cities'] as $country => $cities){
			if(empty($cities) || !$country) continue;
			$citydata = array();
			foreach($cities as $city => $data){
				if($city == 'unknown' || !$city){
					$city_data_unknown[$country] = $data['opens'];
					continue;
				}
				$citydata[] = "[".$data['lat'].",".$data['lng'].",'". addslashes($city)."',".$data['opens'].",'".sprintf(_n('%d opened', '%d opens', $data['opens'], 'mymail'), $data['opens'])."']";
			}
			if(!empty($citydata))$city_data[] = $country.':['.implode(',', $citydata).']';
		}
	
	echo implode(',', $city_data);
	}
?>},
			city_data_unknown = {<?php echo implode(array_map(create_function('$key, $value', 'return "\'".$key."\':".$value."";'), array_keys($city_data_unknown), array_values($city_data_unknown)), ','); ?>},
			mapoptions = {
				legend: false,
				region: 'world',
				resolution: 'countries',
				datalessRegionColor: '#ffffff',
				enableRegionInteractivity: true,
				colors: ['#d7f1fc','#21759B'],
				backgroundColor: {
					fill: 'none',
					stroke: null,
					strokeWidth: 0
				}
			},
			hash,
			geopmap;
			
			for( row in data.D){data.D[row].c[0].f = countries[data.D[row].c[0].v];}
			
					
			google.setOnLoadCallback(function(){
											  
				geomap = new google.visualization.GeoChart(document.getElementById('countries_map'));
					
				if(location.hash && (hash = location.hash.match(/region=([A-Z]{2})/))){
					regionClick(hash[1]);
				}else{
					draw();
				}
				
				google.visualization.events.addListener(geomap, 'regionClick', regionClick);
			
			});
			
			$('a.zoomout').on('click', function(){
				showWorld();
				return false;
			});
			
			$('#countries_table').find('tbody').find('tr').on('click', function(){
				var code = $(this).data('code');
				(code == 'unknown')
					? showWorld()
					: regionClick(code);
					
				return false;
			});
			
			function showWorld(){
				var options = {
					'region': 'world',
					'displayMode': 'region',
					'resolution': 'countries',
					'colors': ['#d7f1fc','#21759B']
				};
				
				data = countrydata;
				draw(options);
				
				$('#countries_table').find('tr').removeClass('active');
				$('#mapinfo').hide();
				
				location.hash = '#region=';
				
			}
			
			function regionClick(event){
				
				var options = {};
				var region = event.region ? event.region : event;
				
				if(region.match(/-/)) return false;
				
				options['region'] = region;
				
<?php if(mymail_option('trackcities') && isset($this->campaign_data['cities']) ) :?>

				if(city_data_unknown[region]){
					$('#mapinfo').show().html('+ '+city_data_unknown[region]+' <?php _e('unknown locations', 'mymail'); ?>')
				}else{
					$('#mapinfo').hide();
				}
				
				d = citydata[region] ? citydata[region] : [];
				
				options['resolution'] = 'provinces';
				options['displayMode'] = 'markers';
				options['dataMode'] = 'markers';
				options['colors'] = ['#4397BD','#21759B'];
				
				data = new google.visualization.DataTable()
				data.addColumn('number', 'Lat');
				data.addColumn('number', 'Long');
				data.addColumn('string', 'tooltip');
				data.addColumn('number', 'Value');
				data.addColumn({type:'string', role:'tooltip'}); 
				
				data.addRows(d);
					
				
<?php endif; ?>
				$('#countries_table').find('tr').removeClass('active');
				$('#country-row-'+region).addClass('active');
				
				location.hash = '#region='+region
				draw(options);
				
					
			}
			
			
			
			function draw(options){
				options = $.extend(mapoptions, options);
				geomap.draw(data, options);
				$('a.zoomout').css({'visibility': (mapoptions['region'] != 'world' ? 'visible' : 'hidden') });
			}
			
			function regTo3dig(region){
				var regioncode = region;
				$.each(regions, function(code, regions){
					if($.inArray(region, regions) != -1) regioncode = code;
				})
				return regioncode;
			}
		
	});  
	 </script>
<br class="clear">
	</div>
<?php endif; ?>
<br class="clear">
<?php endif; ?>
<input type="hidden" value="<?php echo !$editable?>" id="mymail_disabled" readonly>