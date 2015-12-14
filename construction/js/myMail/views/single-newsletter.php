<!doctype html>  
<html lang="en-us">
<head>
	<meta charset="utf-8">
	
	<title><?php the_title() ?></title>
	
	<meta property="og:title" content="<?php the_title(); ?>">
	
	<link rel="canonical" href="<?php echo add_query_arg( 'frame', 0, get_permalink()) ?>">
	
	<link rel="stylesheet" href="<?php echo MYMAIL_URI ?>/assets/css/frontpage.css">
	
	<script>
	var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
	</script>
	<script type="text/javascript" src="<?php echo admin_url() ?>load-scripts.php?c=1&load=jquery,json2&amp;ver=<?php global $wp_version; echo $wp_version?>"></script>
	<script src="<?php echo MYMAIL_URI ?>/assets/js/frontpage.js"></script>
	

</head>
<body>
	<ul id="header">
		<li class="logo header"><a href="<?php bloginfo('url')?>"><?php bloginfo('name')?></a></li>
<?php if( get_previous_post() && mymail_option('frontpage_pagination') ) : ?>
 		<li class="button header"><?php previous_post_link('%link', '&#9666;')?></li>
<?php endif; ?>	
		<li class="subject header"><a href="<?php echo get_permalink()?>"><?php the_title(); ?></a></li>
<?php if( get_next_post() && mymail_option('frontpage_pagination') ) : ?>
		<li class="button header"><?php next_post_link('%link','&#9656;')?></li>
<?php endif; ?>
		<li class="button header closeframe"><a title="remove frame" href="<?php echo add_query_arg( 'frame', 0, get_permalink()) ?>">&#10005;</a></li>
<?php if( mymail_option('share_button') && !$preview ) :
	$is_forward = (isset($_REQUEST['forward'])) ? $_REQUEST['forward'] : '';
?>
		<li class="share header">
			<a><?php _e('Share', 'mymail') ?></a>
			<div class="sharebox" <?php if($is_forward) echo ' style="display:block"';?>>
				<div class="arrow"></div>
				<div class="sharebox-inner">
				<ul class="sharebox-panel">
			<?php if( $services = mymail_option('share_services') ) : ?>
					<li class="sharebox-panel-option <?php if(!$is_forward) echo ' active';?>">
						<h4><?php echo sprintf(__('Share this via %s', 'mymail'), '&hellip;') ?></h4>
						<div>
							<ul class="social-services">
							<?php 
							foreach($services as $service){
								if(!isset($mymail_social_services[$service])) continue;
								?>						
								<li><a title="<?php echo sprintf(__('Share this via %s', 'mymail'), $mymail_social_services[$service]['name']) ?>" class="<?php echo $service?>" href="<?php echo str_replace('%title', urlencode(get_the_title()), str_replace('%url', urlencode(get_permalink()), htmlentities($mymail_social_services[$service]['url']))); ?>"<?php if(isset($mymail_social_services[$service]['icon'])) echo ' style="background-image:url('.$mymail_social_services[$service]['icon'].')"'?> >
								<?php echo $mymail_social_services[$service]['name']?>
								</a></li>
								<?php
							}
							?>
							</ul>
						</div>
					</li>
			<?php endif; ?>
					<li class="sharebox-panel-option <?php if($is_forward) echo ' active';?>">
						<h4><?php echo sprintf(__('Share with %s', 'mymail'), __('email', 'mymail')); ?></h4>
						<div>
							<form id="emailform" novalidate>
								<p>
									<input type="text" name="sendername" id="sendername" placeholder="<?php _e('Your name', 'mymail') ?>" value="">
								</p>
								<p>
									<input type="email" name="sender" id="sender" placeholder="<?php _e('Your email address', 'mymail') ?>" value="<?php echo $is_forward ?>">
								</p>
								<p>
									<input type="email" name="receiver" id="receiver" placeholder="<?php _e("Your friend's email address", 'mymail') ?>" value="">
								</p>
								<p>
									<textarea name="message" id="message" placeholder="<?php _e('A personal note to your friend', 'mymail') ?>"></textarea>
								</p>
								<p>
									<span class="status">&nbsp;</span>
									<input type="submit" class="button" value="<?php _e('Send now', 'mymail') ?>" >
								</p>
									<div class="loading" id="ajax-loading"></div>
								<p>
									<a class="appsend" href="mailto:?body=%0D%0A%0D%0A<?php echo get_permalink()?>"><?php _e('or send it with your mail application', 'mymail'); ?></a>
								</p>
								<p class="info"><?php _e('We respect your privacy. Nothing you enter on this page is saved by anyone', 'mymail') ?></p>
								<?php wp_nonce_field(get_permalink()); ?>
								<input type="hidden" name="url" id="url" value="<?php echo get_permalink()?>">
							</form>
						</div>
					</li>
					<li class="sharebox-panel-option">
						<h4><?php _e("Share the link", 'mymail') ?></h4>
						<div>
					<input type="text" value="<?php echo get_permalink()?>" onclick="this.select()">
						</div>
					</li>
				</ul>
				</div>
			</div>
		</li>
<?php endif; ?>
	</ul>
	<div id="shadow"></div>
	<div id="iframe-wrap">
		<iframe src="<?php echo add_query_arg( 'frame', 0, get_permalink()) ?>"></iframe>
	</div>
</body>
</html>
