<?php if(!defined('ABSPATH')) die('not allowed');

class mymail_shortcodes {
	
	public function __construct( ) {
		add_shortcode('newsletter', array( &$this, 'newsletter'));
		add_shortcode('newsletter_signup', array( &$this, 'newsletter_signup'));
		add_shortcode('newsletter_signup_form', array( &$this, 'newsletter_signup_form'));
		add_shortcode('newsletter_confirm', array( &$this, 'newsletter_confirm'));
		add_shortcode('newsletter_unsubscribe', array( &$this, 'newsletter_unsubscribe'));
	}
	
	public function newsletter($atts, $content) {
	
		if(!$atts['id'] || (!is_single() && !is_page())) return false;
		
		$link = get_permalink($atts['id']);
		
		if(!$link) return '';
		
		extract( shortcode_atts( array(
			'scrolling' => true,
		), $atts ) );
		
		return '<iframe class="mymail_frame" src="'.add_query_arg( 'frame', 0, $link).'" style="min-width:610px;" width="100%" scrolling="'.($scrolling ? 'auto' : 'no').'" frameborder="0" onload="this.height=this.contentWindow.document.body.scrollHeight+20;"></iframe>';
		
	}
	public function newsletter_signup($atts, $content) {
		 if(!isset($_REQUEST['unsubscribe']) && !isset($_REQUEST['subscribe'])){
			 return do_shortcode($content);
		 }
	}
	public function newsletter_signup_form($atts, $content) {
		 if(!isset($_REQUEST['unsubscribe'])){
			extract( shortcode_atts( array(
				'id' => 0,
				'tabindex' => 100,
			), $atts ) );
			
			return mymail_form($id, $tabindex, false);
		 }
	}
	public function newsletter_confirm($atts, $content) {
		 if(isset($_REQUEST['subscribe'])){
			 return do_shortcode($content);
		 }
	}
	public function newsletter_unsubscribe($atts, $content) {
		 if(isset($_REQUEST['unsubscribe'])){
			extract( shortcode_atts( array(
				'tabindex' => 100,
			), $atts ) );
			
			require_once MYMAIL_DIR.'/classes/form.class.php';
				
			global $mymail_form;
				
			if(!$mymail_form) $mymail_form = new mymail_form();
			return do_shortcode($content).$mymail_form->unsubscribe_form($_REQUEST['unsubscribe'], isset($_REQUEST['k']) ? $_REQUEST['k'] : '', $tabindex);
		 }
	}
}
?>