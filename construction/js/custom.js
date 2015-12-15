$(function() {
	window.scrollReveal = new scrollReveal();
	"use strict";
	
	// PreLoader
	$(window).load(function() {
		$(".loader").fadeOut(400);
	});

	
	// Countdown
	$('.countdown').downCount({
		date: '12/24/2015 14:30:00',
		offset: +10
	});			
    
});