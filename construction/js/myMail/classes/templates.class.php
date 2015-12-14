<?php if(!defined('ABSPATH')) die('not allowed');


class mymail_templates {
	
	
	public $raw;
	public $doc;
	public $data;
	public $modules;
	
	public $path;
	public $url;
	
	private $slug;
	private $templatepath;
	private $file;
	private $download_url = 'https://dl.dropbox.com/u/9916342/data/mymail.zip';
	private $headers = array(
			'name' => 'Template Name',
			'label' => 'Name',
			'uri' => 'Template URI',
			'description' => 'Description',
			'author' => 'Author',
			'author_uri' => 'Author URI',
			'version' => 'Version',
	);
	
	
	public function __construct($slug = NULL, $file = 'index.html') {
	
		
		$this->file = $file;

		$this->path = MYMAIL_UPLOAD_DIR.'/templates';
		$this->url = MYMAIL_UPLOAD_URI.'/templates';
		
		if(!is_null($slug)){
			$this->load_template($slug);
		}
		if(is_admin()){
		
			register_activation_hook(MYMAIL_DIR . '/myMail.php', array( &$this, 'activate'));
			
			add_action('init', array( &$this, 'setup' ) );
			
			if (version_compare(MYMAIL_VERSION, '1.3.4', '<')) {
				add_filter('upgrader_pre_install', array( &$this, 'backup_templates' ) );
			}
			add_filter('upgrader_post_install', array( &$this, 'restore_templates' ) );

		}
	}
	
	public function setup() {
		add_action('admin_menu', array( &$this, 'admin_menu' ));
		$this->ajax();
	}
	
	
	
	public function get($modules = true, $absolute_img = false) {
		if ( !$modules ) {
			
			if(!$this->doc) return '';
			$xpath = new DOMXpath($this->doc);
			$modulecontainer = $xpath->query("//*/div[@class='modulecontainer']");
			
			foreach( $modulecontainer as $container) {

				$activemodules = $this->get_modules(true);
				$container->nodeValue = '';
				foreach ($activemodules as $domElement){
					$domNode = $this->doc->importNode($domElement, true);
					$container->appendChild($domNode);
				}
				
			}
			
			$html = $this->doc->saveHTML();
			
		}else{
		
			$html = $this->raw;	
			
		}
		
		if($absolute_img) $html = $this->make_img_absolute( $html );
		
		return $html;
	}
	
	
	public function backup_templates( $a ) {
		mymail_require_filesystem();
		global $wp_filesystem;
		wp_mkdir_p(WP_CONTENT_DIR.'/upgrade/mymail_templates');
		copy_dir(MYMAIL_DIR.'/templates', WP_CONTENT_DIR.'/upgrade/mymail_templates');
	}
	
	
	public function restore_templates( $a ) {
		if(!is_dir(WP_CONTENT_DIR.'/upgrade/mymail_templates')) return;
		mymail_require_filesystem();
		global $wp_filesystem;
		copy_dir(WP_CONTENT_DIR.'/upgrade/mymail_templates', MYMAIL_DIR.'/templates');
		$wp_filesystem->delete(WP_CONTENT_DIR.'/upgrade/mymail_templates', true);
	}
	
	
	private function make_img_absolute( $html ) {
		preg_match_all("/(src|background)=[\"'](.*)[\"']/Ui", $html, $images);
		$images = array_unique( $images[2] );
		foreach ( $images as $image ) {
			if(substr($image, 0, 7) == 'http://') continue;
			if(substr($image, 0, 8) == 'https://') continue;
			$html = str_replace( $image, $this->url .'/' . $this->slug . '/' . $image, $html );
		}
		return $html;
	}
	
	
	public function load_template($slug = '') {
	
		if(!empty($slug)) $this->slug = $slug;
		if(!$this->slug) return false;
		
		$this->templatepath = $this->path .'/' . $this->slug;
		
		$file = $this->templatepath . '/' . $this->file;
		
		if (!file_exists( $file ) )
			return false;
		
		$doc = new DOMDocument();
		$doc->validateOnParse = true;
		@$doc->loadHTMLFile($file);
		
		
		$raw = $doc->saveHTML();
		$data = $this->get_template_data( $file );
		if($data['name']){
			$raw = trim(substr($raw, strpos($raw, '-->')+3));
			$this->data = $data;
		}
		
		$this->doc = $doc;
		$this->raw = $raw;
	}
	
	
	public function remove_template($slug = '') {
		
		if(!empty($slug)) $this->slug = $slug;
		if(!$this->slug) return false;
	
		$this->templatepath = $this->path .'/' . $this->slug;
		
		if ( !file_exists( $this->templatepath . '/index.html' ) )
			return false;
			
		mymail_require_filesystem();
		
		global $wp_filesystem;
		return $wp_filesystem->delete($this->templatepath, true);
	}
	
	public function upload_template() {
		$result = wp_handle_upload( $_FILES['templatefile'], array(
			'mimes' => array('zip' => 'multipart/x-zip'),
		) );
		if(isset($result['error'])){
			return $result;	
		}
		
		mymail_require_filesystem();
		
		$tempfolder = MYMAIL_UPLOAD_DIR.'/uploads';
		
		wp_mkdir_p($tempfolder);
		
		return $this->unzip_template($result['file'], $tempfolder);
		
	}
	
	
	public function unzip_template($templatefile, $uploadfolder) {
		
		if(!unzip_file($templatefile, $uploadfolder)){
			return false;	
		}
		//die();
		if($folders = scandir($uploadfolder)){
		
			global $wp_filesystem;
			
			mymail_require_filesystem();
		
			foreach($folders as $folder){
				if(in_array($folder, array('.', '..')) || !is_dir($uploadfolder.'/'.$folder)) continue;
				
				//need index.html file
				if(file_exists($uploadfolder.'/'.$folder.'/index.html')){
					$data = $this->get_template_data($uploadfolder.'/'.$folder.'/index.html');
					
					//with name value
					if(!empty($data['name'])){
						wp_mkdir_p($this->path .'/'.$folder);
						copy_dir($uploadfolder.'/'.$folder, $this->path .'/'.$folder);
					}
					
				}
				
				if(file_exists($uploadfolder.'/'.$folder.'/colors.json')){
				
					$colors = $wp_filesystem->get_contents($uploadfolder.'/'.$folder.'/colors.json');
					
					if($colors){
						$colorschemas = json_decode($colors);
						
						$customcolors = get_option('mymail_colors', array());
						
						if(!isset($customcolors[$folder])){
						
							$customcolors[$folder] = array();
							foreach($colorschemas as $colorschema){
								$hash = md5(implode('', $colorschema));
								$customcolors[$folder][$hash] = $colorschema;
							}
							
							update_option('mymail_colors', $customcolors);
							
						}
						

					}
				}
			}
			
			return $wp_filesystem->delete($uploadfolder, true);
		}

		return false;	
		
	}
	
	
	public function renew_default_template() {
	
		$zip = wp_remote_get( $this->download_url, array('timeout' => 60, 'sslverify' => false) );
		
		if ( is_wp_error( $zip ) ) {
			die($zip->get_error_message());
		}
		
		
		if($zip['response']['code'] == 200){
		
			mymail_require_filesystem();
			
			$tempfolder = MYMAIL_UPLOAD_DIR.'/uploads';
			wp_mkdir_p($tempfolder);
			
			global $wp_filesystem;
			
			$wp_filesystem->put_contents( $tempfolder . '/mymail.zip', $zip['body']);
			
			return $this->unzip_template($tempfolder . '/mymail.zip', $tempfolder);
		}
		
		return false;
	}
	
	
	public function create_new($name, $content = '', $modules = true, $overwrite = true) {
	
	
		if(!$this->slug) return false;
		
		$filename = strtolower(sanitize_file_name($name).'.html');
		
		if($name == __('Base', 'mymail')) $filename = 'index.html';
		if($name == __('Notification', 'mymail')) $filename = 'notification.html';
		
		$pre = '<!--'."\n\n";
		
		foreach($this->data as $k => $v){
			$pre .= "\t".$this->headers[$k].": ".($k == 'label' ? $name : $v)."\n";
		}

		$pre .= "\n-->\n";
		
		if($modules){
			
			//remove active from class
			$content = preg_replace('#class=(["\'])?(.*)(active)(.*)("|\')?#i', 'class=$1$2$4$5', $content);
			
		}else{
			//remove module from class
			$content = preg_replace('#class=(["\'])?(.*)(module)(.*)("|\')?#i', 'class=$1$2$4$5', $content);
		}
		
		if(!$overwrite && file_exists($this->templatepath. '/' . $filename)) return false;
		
		//remove absolute path to images from the template
		$content = str_replace('src="'. $this->url .'/' . $this->slug. '/', 'src="', $content);
		
		global $wp_filesystem;
		mymail_require_filesystem();
		
		if ($wp_filesystem->put_contents( $this->templatepath. '/' . $filename, $pre.$content, FS_CHMOD_FILE) ) {
			return $filename;
		}
		
		return false;	
		
	}
	
	
	public function get_modules_html($activeonly = false) {
	
		return $this->make_img_absolute( $this->get_html_from_nodes($this->get_modules($activeonly)) );
	}
	
	
	public function get_modules($activeonly = false) {
		
		if(!$this->slug) return false;
		
		$xpath = new DOMXpath($this->doc);
		
		$modules = ($activeonly) 
		 ? $xpath->query("//*/div[contains(concat(' ',normalize-space(@class),' '),' module active ')]")
		 : $xpath->query("//*/div[contains(concat(' ',normalize-space(@class),' '),' module ')]");
		
		$modulenames = array();
		
/*
		if (!is_null($modules)) {
			foreach ($modules as $module) {
				$name = $module->getAttribute('data-module');
				if(!$name || in_array($name, $modulenames)) continue;
				$auto = ($module->getAttribute('data-auto')) ? ' data-auto="true"' : '';
				$class = $module->getAttribute('class');
				$typepreview = ($module->getAttribute('data-type')) ? ' data-type="'.$module->getAttribute('data-type').'"' : '';
				$modulenames[] = $name;
				$tmp_html = $this->get_html_from_node($module);
				if($tmp_html) $html .= '<div data-module="'.$name.'"'.$auto.$typepreview.' class="'.$class.'">'.$tmp_html.'</div>';
			}
		
		}
*/
		//$html = $this->make_img_absolute( $html );	
		
		return $modules;
		
		//return $html;
	}
	
	
	public function admin_menu() {
		
		$page = add_submenu_page('edit.php?post_type=newsletter', __('Templates','mymail'), __('Templates','mymail'), 'mymail_manage_templates', 'mymail_templates', array( &$this, 'templates' ));
		add_action('admin_print_styles-'.$page, array( &$this, 'admin_print_styles' ) );
		add_action('admin_print_scripts-'.$page, array( &$this, 'admin_print_scripts' ) );
		
	}
	
	public function templates() {
	
		include MYMAIL_DIR.'/views/templates.php';

	}
	
	
	/*----------------------------------------------------------------------*/
	/* AJAX
	/*----------------------------------------------------------------------*/
	
	
	private function ajax() {
		
		add_action('wp_ajax_mymail_get_template_html', array( &$this, 'ajax_get_template_html') );
		add_action('wp_ajax_mymail_set_template_html', array( &$this, 'ajax_set_template_html') );
		
		add_action('wp_ajax_mymail_remove_template', array( &$this, 'ajax_remove_template') );
	}
	
	
	public function ajax_get_template_html( ) {
		$return['success'] = false;
		
		$this->ajax_nonce( json_encode( $return ) );
		
		$return['slug'] = $_POST['slug'];
		$return['file'] = basename($_POST['href']);
		$file = $this->path .'/'.$return['slug'].'/'.$return['file'];
		
		$return['files'] = $this->get_files($return['slug']);
		
		if(file_exists($file)){
			$return['success'] = !!$return['html'] = @file_get_contents($file);
		}
		
		echo json_encode( $return );
		
		exit;
	}
	
	public function ajax_set_template_html( ) {
		$return['success'] = false;
		
		$this->ajax_nonce( json_encode( $return ) );
		
		$this->ajax_filesystem( );
		
		global $wp_filesystem;
		mymail_require_filesystem();
		
		$return['slug'] = $_POST['slug'];
		$return['file'] = $_POST['file'];
		$file = $this->path .'/'.$return['slug'].'/'.$return['file'];
		$content = stripslashes($_POST['content']);
		
		if ($return['success'] = $wp_filesystem->put_contents( $file, $content, FS_CHMOD_FILE) ) {
			$return['msg'] = __('File has been saved!', 'mymail');
		}else{
			$return['msg'] = __('Not able to save file!', 'mymail');
			echo json_encode( $return );
			exit;
		}
		
		wp_remote_get( $this->get_screenshot($return['slug'], $return['file']) );
		
		echo json_encode( $return );
		
		exit;
	}
	
	public function ajax_remove_template( ) {
		$return['success'] = false;
		
		$this->ajax_nonce( json_encode( $return ) );
		
		$file = $this->path .'/'.esc_attr($_POST['file']);
		
		if(file_exists($file) && current_user_can('mymail_delete_templates')){
			mymail_require_filesystem();
			
			global $wp_filesystem;
			
			$return['success'] = $wp_filesystem->delete( $file );
		}
		
		echo json_encode( $return );
		
		die();
	}
	
	private function ajax_nonce($return = NULL, $nonce = 'mymail_nonce') {
		if (!wp_verify_nonce($_REQUEST['_wpnonce'], $nonce)) {
			die( $return );
		}
		
	}
	
	private function ajax_filesystem() {
		if('ftpext' == get_filesystem_method() && (!defined('FTP_HOST') || !defined('FTP_USER') || !defined('FTP_PASS'))){
			$return['msg'] = __('WordPress is not able to access to your filesystem!', 'mymail');
			$return['msg'] .= "\n".sprintf(__('Please add following lines to the wp-config.php %s', 'mymail'), "\n\ndefine('FTP_HOST', 'your-ftp-host');\ndefine('FTP_USER', 'your-ftp-user');\ndefine('FTP_PASS', 'your-ftp-password');\n");
			$return['success'] = false;
			echo json_encode( $return );
			exit;
		}
		
	}
	/*----------------------------------------------------------------------*/
	/* Filters
	/*----------------------------------------------------------------------*/
	
	public function get_templates($slugsonly = false) {
		
		$templates = array();
		$files = list_files($this->path);
		sort($files);
		foreach($files as $file){
			if(basename($file) == 'index.html'){
				
				$filename = str_replace($this->path .'/', '', $file);
				$slug = dirname($filename);
				if(!$slugsonly){
					$templates[$slug] = $this->get_template_data($file);
				}else{
					$templates[] = $slug;
				}
			}
		}
		
		return $templates;
		
	}
	
	public function get_files($slug = '') {
		
		if(!empty($slug)) $this->slug = $slug;
		if(!$this->slug) return false;
		
		$templates = array();
		$files = list_files($this->path .'/'.$this->slug, 1);
		
		sort($files);
		
		$list = array(
			'index.html' => $this->get_template_data($this->path .'/'.$this->slug .'/index.html'),
		);
		
		if(file_exists($this->path .'/'.$slug .'/notification.html'))
			$list['notification.html'] = $this->get_template_data($this->path .'/'.$this->slug .'/notification.html');
			
		foreach($files as $file){
			
			if(strpos($file, '.html') && is_file($file)) $list[basename($file)] = $this->get_template_data($file);
			
		}
		
		return $list;
		
	}

	public function get_versions($slugsonly = false) {
		
		$templates = $this->get_templates();
		$return = array();
		foreach($templates as $slug => $data){
			
			$return[$slug] = $data['version'];
		}
		
		return $return;
		
	}
	
	public function get_updates() {
		$updates = get_site_transient( 'mymail_updates' );
		if(isset($updates['templates'])){
			$updates = $updates['templates'];
		}else{
			$updates = array();	
		}
		return $updates;
	}
	
	public function buttons( $basefolder = 'img' ) {
		
		$root = list_files($this->path .'/'.$this->slug.'/'.$basefolder, 1);
		
		sort($root);
		$folders = array();
		
		foreach($root as $file){
		
			if(!is_dir($file)) continue;
			$rootbtn = '';
			
			?>
		<div class="button-nav-wrap">
			<?php
			$nav = $btn = '';
			$id = basename($file);
			$files = list_files($file, 1);
			foreach($files as $file){
				if(is_dir($file)){
					$file = str_replace('//','/', $file);
					$folders[] = basename($file);
					$nav .= '<a class="nav-tab" href="#buttons-'.$id.'-'.basename($file).'">'.basename($file).'</a>';
					$btn .= $this->list_buttons(substr($file,0,-1), $id);
				}else{
					if(!in_array(strrchr($file, '.'), array('.png', '.gif', '.jpg', '.jpeg'))) continue;
					if($rootbtn) continue;
					$rootbtn = $this->list_buttons(dirname($file), 'root');
					
				}
			}
			
			if($nav) :?>
		<div id="button-nav-<?php echo $id ?>" class="button-nav nav-tab-wrapper hide-if-no-js" data-folders="<?php echo implode('-', $folders)?>"><?php echo $nav ?></div>
			<?php endif;
		echo $btn;
			?>
		</div>
		
		
		<?php if($rootbtn):?>
		<div class="button-nav-wrap button-nav-wrap-root"><?php	echo $rootbtn; ?></div>
		<?php endif;
		
		}
		
		
		
		
	}
	
	
	public function list_buttons($folder, $id) {
		
		$files = list_files($folder, 1);
		
		$btn = '<ul class="buttons buttons-'.basename($folder).'" id="tab-buttons-'.$id.'-'.basename($folder).'">';
		
		foreach($files as $file){
		
			if(is_dir($file)) continue;
			if(!in_array(strrchr($file, '.'), array('.png', '.gif', '.jpg', '.jpeg'))) continue;
			
			$filename = str_replace($folder .'/', '', $file);
			$btn .= '<li><a class="btnsrc" title="'.substr($filename, 0, strrpos($filename, '.')).'" data-link="'.$this->get_social_link($filename).'"><img src="'.str_replace($this->path .'/', $this->url .'/', $file).'"></a></li>';
			
		}
		
		$btn .= '</ul>';

		return $btn;
		
		
	}
	
	
	public function get_social_link($file) {
		
		$network = substr($file, 0, strrpos($file, '.'));
		
		$links = array(
			'amazon' => 'http://amazon.com',
			'android' => 'http://android.com',
			'apple' => 'http://apple.com',
			'appstore' => 'http://apple.com',
			'behance' => 'http://www.behance.net/USERNAME',
			'blogger' => 'http://USERNAME.blogspot.com/',
			'delicious' => 'https://delicious.com/USERNAME',
			'deviantart' => 'http://USERNAME.deviantart.com',
			'digg' => 'http://digg.com/users/USERNAME',
			'dribbble' => 'http://dribbble.com/USERNAME',
			'drive' => 'https://drive.google.com',
			'dropbox' => 'https://dropbox.com',
			'ebay' => 'http://www.ebay.com',
			'facebook' => 'https://facebook.com/USERNAME',
			'flickr' => 'http://www.flickr.com/photos/USERNAME',
			'forrst' => 'http://forrst.me/USERNAME',
			'google' => 'http://www.google.com',
			'googleplus' => 'http://plus.google.com/USERNAME',
			'html5' => 'http://html5.com',
			'instagram' => 'http://instagram.com/USERNAME',
			'lastfm' => 'http://www.lastfm.de/user/USERNAME',
			'linkedin' => 'http://www.linkedin.com/in/USERNAME',
			'myspace' => 'http://www.myspace.com/USERNAME',
			'paypal' => 'http://paypal.com',
			'picasa' => 'http://picasa.com',
			'pinterest' => 'http://pinterest.com/USERNAME',
			'rss' => get_bloginfo('rss2_url'),
			'skype' => 'skype:USERNAME',
			'soundcloud' => 'http://soundcloud.com/USERNAME',
			'stumbleupon' => 'http://stumbleupon.com',
			'technorati' => 'http://technorati.com',
			'tumblr' => 'http://USERNAME.tumblr.com',
			'twitter' => 'https://twitter.com/USERNAME',
			'twitter_2' => 'https://twitter.com/USERNAME',
			'vimeo' => 'http://vimeo.com/USERNAME',
			'windows' => 'http://microsoft.com',
			'windows_8' => 'http://microsoft.com',
			'wordpress' => 'http://profiles.wordpress.org/USERNAME',
			'yahoo' => 'http://yahoo.com',
			'youtube' => 'http://youtube.com/user/USERNAME', 
		);
		
		return (isset($links[$network])) ? $links[$network] : '';
		
	}
	
	
	
	
	public function get_raw_template( $file = 'index.html') {
		if ( !file_exists( $this->path .'/' . $this->slug . '/' .$file) )
			return false;
		
		return file_get_contents( $this->path .'/' . $this->slug . '/'. $file );
	}
	
	

	/*----------------------------------------------------------------------*/
	/* Styles & Scripts
	/*----------------------------------------------------------------------*/
	
	
	public function admin_print_styles() {

		wp_register_style('mymail_templates', MYMAIL_URI.'/assets/css/templates-style.css', array(), MYMAIL_VERSION);
		wp_enqueue_style('mymail_templates');
				
	}
	
	public function admin_print_scripts() {

		wp_enqueue_script('thickbox');
		wp_enqueue_style('thickbox');
		wp_register_script('mymail_templates', MYMAIL_URI.'/assets/js/templates-script.js', array('jquery'), MYMAIL_VERSION);
		wp_enqueue_script('mymail_templates');
		
	}
	
	
	
	
	/*----------------------------------------------------------------------*/
	/* Other
	/*----------------------------------------------------------------------*/
	
	
	public function get_screenshot( $slug, $file = 'index.html', $size = 300 ) {
	
		$fileuri = $this->url .'/'.$slug.'/'.$file;
		$screenshotfile = MYMAIL_UPLOAD_DIR.'/screenshots/'.$slug.'_'.$file.'.jpg';
		$screenshoturi = MYMAIL_UPLOAD_URI.'/screenshots/'.$slug.'_'.$file.'.jpg';
		$file = $this->path .'/'.$slug.'/'.$file;
		
		//serve saved
		if(file_exists($screenshotfile) && file_exists($file) && filemtime($file) < filemtime($screenshotfile)){
			$url = $screenshoturi.'?c='.filemtime($screenshotfile);
		}else if(!file_exists($file) || substr($_SERVER['REMOTE_ADDR'], 0, 4) == '127.' || $_SERVER['REMOTE_ADDR'] == '::1'){
			$url = 'http://s.wordpress.com/wp-content/plugins/mshots/default.gif';
		}else{
			$url = 'http://s.wordpress.com/mshots/v1/'.(urlencode($fileuri.'?c='.md5_file($file))).'?w='.$size;
			
			$remote = wp_remote_get($url, array('redirection' => 0));
			
			if(wp_remote_retrieve_response_code($remote) == 200){
				wp_filesystem();
				global $wp_filesystem;
				
				if(!is_dir( dirname($screenshotfile) )) wp_mkdir_p( dirname($screenshotfile) ) ;
				
				$wp_filesystem->put_contents($screenshotfile, wp_remote_retrieve_body($remote), false );
			}
			
		}
		return $url;
	}
	
	
	
	
	
	/*----------------------------------------------------------------------*/
	/* Activation
	/*----------------------------------------------------------------------*/
	

	
	public function activate() {
	
		add_action('shutdown', array( &$this, 'copy_templates'), 99 );
		
	}
	
	public function copy_templates() {
	
		global $wpdb;
		
		if (function_exists('is_multisite') && is_multisite()) {
		
			$old_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
			
		}else{
		
			$blogids = array(false);
			
		}
		
		mymail_require_filesystem();
		
		foreach ($blogids as $blog_id) {
		
			if($blog_id) switch_to_blog( $blog_id );
	
			$upload_folder = wp_upload_dir();
		
			if(!is_dir( $upload_folder['basedir'].'/myMail/templates' )){
				wp_mkdir_p(  $upload_folder['basedir'].'/myMail/templates' );
				copy_dir(MYMAIL_DIR . '/templates', $upload_folder['basedir'].'/myMail/templates' );
			}
		}
		
		if($blog_id) switch_to_blog($old_blog);
		

	}
	
	
	
	
	
	/*----------------------------------------------------------------------*/
	/* Privates
	/*----------------------------------------------------------------------*/
	

	
	private function require_filesystem($redirect = '', $method = '') {
		
		global $wp_filesystem;
		
		ob_start();
		
		if ( false === ($credentials = request_filesystem_credentials($redirect, $method)) ) {
			$data = ob_get_contents();
			ob_end_clean();
			if ( ! empty($data) ){
				include_once( ABSPATH . 'wp-admin/admin-header.php');
				echo $data;
				include( ABSPATH . 'wp-admin/admin-footer.php');
				exit;
			}
			return;
		}
	
		if ( ! WP_Filesystem($credentials) ) {
			request_filesystem_credentials($redirect, $method, true); // Failed to connect, Error and request again
			$data = ob_get_contents();
			ob_end_clean();
			if ( ! empty($data) ) {
				include_once( ABSPATH . 'wp-admin/admin-header.php');
				echo $data;
				include( ABSPATH . 'wp-admin/admin-footer.php');
				exit;
			}
			return;
		}
	
	}
	
	
	private function get_html_from_nodes($nodes){
	
		$html = '';
		
		if(!$nodes) return $html;
		foreach ($nodes as $node) {
			$html .= $this->get_html_from_node($node);
		}
	
		return $html;
	}
	
	private function get_html_from_node($node){
	
		return $node->ownerDocument->saveXML($node);
		
	}
	
	
	private function get_template_data($file) {
	
		$basename = basename($file);
		
		if(!file_exists($file)) return false;
		$fp = fopen( $file, 'r' );
		$file_data = fread( $fp, 2048 );
		fclose( $fp );
		
		foreach ( $this->headers as $field => $regex ) {
			preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, ${$field});
			if ( !empty( ${$field} ) )
				${$field} = _cleanup_header_comment( ${$field}[1] );
			else
				${$field} = '';
			
		}
		
		$file_data = compact( array_keys( $this->headers ) );
		if(empty($file_data['label'])) $file_data['label'] = $file_data['name'];
		
		if($basename == 'index.html') $file_data['label'] = __('Base', 'mymail');
		if($basename == 'notification.html') $file_data['label'] = __('Notification', 'mymail');
		
		if(empty($file_data['label'])) $file_data['label'] = substr($basename, 0, strrpos($basename, '.'));
		
		return $file_data;
		
	}
	
	
	private function post_meta( $post_id, $meta_key, $data, $unique = false ) {
		
		$meta_value = get_post_meta( $post_id, $meta_key, true );
		
		/* If a new meta value was added and there was no previous value, add it. */
		if ( $data && '' == $meta_value ) {
			add_post_meta( $post_id, $meta_key, $data, true );
		/* If the new meta value does not match the old value, update it. */
		} elseif ( $data && $data != $meta_value ) {
			update_post_meta( $post_id, $meta_key, $data );
		/* If there is no new meta value but an old value exists, delete it. */
		} elseif ( '' == $data && $meta_value ) {
			delete_post_meta( $post_id, $meta_key, $meta_value );
		}
	}


}
?>