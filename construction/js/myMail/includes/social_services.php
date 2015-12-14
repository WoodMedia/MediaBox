<?php
$mymail_social_services = apply_filters('mymail_social_services', array(
		'twitter' => array(
				'name' => 'Twitter',
				'url' => "https://twitter.com/intent/tweet?source=myMail&text=%title&url=%url",
		),
		'facebook' => array(
				'name' => 'Facebook',
				'url' => "https://www.facebook.com/sharer.php?u=%url&t=%title",
		),
		'google' => array(
				'name' => 'Google+',
				'url' => "https://plusone.google.com/_/+1/confirm?url=%url&title=%title",
		),
		'googlebookmark' => array(
				'name' => 'Google Bookmarks',
				'url' => "https://www.google.com/bookmarks/mark?op=edit&bkmk=%url&title=%title",
		),
		'pinterest' => array(
				'name' => 'Pinterest',
				'url' => "http://pinterest.com/pin/create/button/?url=%url&description=%title",
		),
		'delicious' => array(
				'name' => 'Delicious',
				'url' => "http://del.icio.us/post?url=%url&title=%title",
		),
		'blogger' => array(
				'name' => 'Blogger',
				'url' => "http://www.blogger.com/blog_this.pyra?t&u=%url&n=%title",
		),
		'sharethis' => array(
				'name' => 'ShareThis',
				'url' => "https://www.sharethis.com/share?url=%url&title=%title",
		),
		'reddit' => array(
				'name' => 'Reddit',
				'url' => "http://en.reddit.com/submit?url=%url&title=%title",
		),
		'digg' => array(
				'name' => 'Digg',
				'url' => "http://digg.com/submit?url=%url&title=%title",
		),
		'evernote' => array(
				'name' => 'Evernote',
				'url' => "http://s.evernote.com/grclip?url=%url&title=%title",
		),
		'stumbleupon' => array(
				'name' => 'StumbleUpon',
				'url' => "http://www.stumbleupon.com/submit?url=%url",
		),
		'linkedin' => array(
				'name' => 'LinkedIn',
				'url' => 'http://www.linkedin.com/shareArticle?mini=true&url=%url&title=%title',
		),
		'xing' => array(
				'name' => 'Xing',
				'url' => "http://www.xing.com/app/user?op=share;url=%url;title=%title",
		),
		'yahoo' => array(
				'name' => 'Yahoo!',
				'url' => "http://bookmarks.yahoo.com/toolbar/savebm?u=%url&t=%title",
		),
		
		
));


?>