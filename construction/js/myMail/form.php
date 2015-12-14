<?php 
$time_start = microtime(true);
if ( !defined('ABSPATH') ) {
	/** Load WordPress Bootstrap */
	require_once('../../../wp-load.php');
	
}
?>
<!doctype html>
<html lang="en-us">
<head>
	<meta charset="utf-8">
	
	<meta name='robots' content='noindex,nofollow'>
	
	<?php if(isset($_GET['s']) && $_GET['s'] == 1) :?>
	<link rel='stylesheet' href='<?php echo get_template_directory_uri() ?>/style.css' type='text/css' media='all' />
	<?php endif;?>
	<link rel='stylesheet' href='<?php echo admin_url('admin-ajax.php?action=mymail_form_css&'.mymail_option('form_css_hash')) ?>' type='text/css' media='all' />
	

<?php
do_action('wp_mymail_head');
?>

</head>
<body>
<div id="formwrap">
<?php
	mymail_form((isset($_GET['id']) ? intval($_GET['id']) : 0), 1, true, 'embeded');
?>
<?php
do_action('wp_mymail_footer');

if(mymail_option('ajax_form')) : ?>
	<script type="text/javascript" src="<?php echo get_site_url() .'/'. WPINC . '/js/jquery/jquery.js?ver='.MYMAIL_VERSION; ?>"></script>
	<script type="text/javascript" src="<?php echo MYMAIL_URI.'/assets/js/form.js?ver='.MYMAIL_VERSION ?>"></script>
	
<?php endif; ?>
</div>
</body>
</html>