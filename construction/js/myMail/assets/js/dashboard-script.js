jQuery(document).ready(function($) {
		
	"use strict"
	
	var current_camp = 0,
		campaigndata = [],
		campaigncount = 0;
	
	//init the whole thing
	function _init(){
		
		$('.piechart').piechart({
			duration:400
		});
		
		$.each($('.camp'), function(){
			var _this = $(this),
				data = _this.data('data');
			
			campaigndata.push({
				ID:	_this.data('id'),
				name: _this.data('name'),
				active: _this.data('active'),
				total: parseInt(data.sent, 10),
				opens: parseInt(data.opens, 10),
				clicks: parseInt(data.totaluniqueclicks, 10),
				unsubscribes: parseInt(data.unsubscribes, 10),
				bounces: parseInt(data.hardbounces, 10),
				
			});
		});
		
		campaigncount = campaigndata.length;
		
		$('.prev_camp').on('click', function(){
			loadCamp(--current_camp);
		});
		$('.next_camp').on('click', function(){
			loadCamp(++current_camp);
		});
		
		
	}
	
	function loadCamp(number){
		if(number <	0){
			current_camp = 0;
			return false;	
		}else if(number >= campaigncount){
			current_camp = campaigncount-1;
			return false;	
		}
		var camp = campaigndata[number];
		
		$('#camp_name').html(camp.name).attr('href', 'post.php?post='+camp.ID+'&action=edit');
		(camp.active) ? $('#stats_cont').addClass('isactive') :  $('#stats_cont').removeClass('isactive');
		$('#stats_total').html(camp.total);
		$('#stats_open').piechart('update',{value:camp.opens,total:camp.total});
		$('#stats_clicks').piechart('update',{value:camp.clicks,total:camp.opens});
		$('#stats_unsubscribes').piechart('update',{value:camp.unsubscribes,total:camp.opens});
		$('#stats_bounces').piechart('update',{value:camp.bounces,total:camp.total+camp.bounces});
		
		$('.prev_camp, .next_camp').removeClass('disabled');
		if(number == campaigncount-1){
			$('.next_camp').addClass('disabled');
		}else if(!number){
			$('.prev_camp').addClass('disabled');
		}
	}
	
	_init();
	
});


