<?php
global $mymail_autoresponder;
$mymail_autoresponder_info = array(

	'units' => apply_filters('mymail_autoresponder_units', array(
		60 => __('minute(s)', 'mymail'),
		3600 => __('hour(s)', 'mymail'),
		86400 => __('day(s)', 'mymail'),
		604800 => __('weeks(s)', 'mymail'),
	)),
	
	'actions' => apply_filters('mymail_autoresponder_actions', array(
		'mymail_subscriber_insert' => array(
			'label' => __('user signed up', 'mymail'),
			'hook' => 'mymail_subscriber_insert'
		),
		'mymail_subscriber_unsubscribed' => array(
			'label' => __('user unsubscribed', 'mymail'),
			'hook' => 'mymail_subscriber_unsubscribed'
		),
		'mymail_post_published' => array(
			'label' => __('something has been published', 'mymail'),
			'hook' => 'transition_post_status'
		),
		/*
		'mymail_time_reached' => array(
			'label' => __('a certain time', 'mymail'),
			'hook' => 'transition_post_status'
		),
		*/
	)),
	
	'operators' => apply_filters('mymail_autoresponder_operators', array(
		'is' => __('is', 'mymail'),
		'is_not' => __('is not', 'mymail'),
		'contains' => __('contains', 'mymail'),
		'contains_not' => __('contains not', 'mymail'),
		'begin_with' => __('begins with', 'mymail'),
		'end_with' => __('ends with', 'mymail'),
		'is_less' => __('is less than', 'mymail'),
		'is_more' => __('is more than', 'mymail'),
	)),
		
		
);

?>