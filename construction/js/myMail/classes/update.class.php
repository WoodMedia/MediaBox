<?php if(!defined('ABSPATH')) die('not allowed');


//Envato_Plugin_UpdateClass
class Envato_Plugin_Update {
	
	private $purchasecode = false;
	private $remote_url = false;
	private $version = false;
	private $plugin_slug = false;
	private $plugin_path = false;
	private $time_upgrade_check = false;
	private $plugins = '';
	private $option_name = 'envato_plugins';

	function __construct( $purchasecode, $args = array() ) {
	
		extract( wp_parse_args( $args, array(
			'remote_url' => false,
			'version' => false,
			'plugin_slug' => false,
			'plugin_path' => false,
			'time' => 43200,
			
		) ) );
		
		$this->purchasecode = $purchasecode;
		
		$this->remote_url = $remote_url;
		$this->version = $version;
		$this->plugin_slug = $plugin_slug;
		$this->plugin_path = ($plugin_path) ? $plugin_path : $plugin_slug;
		$this->time_upgrade_check = $time;

		//Get plugins for upgrading
		$this->plugins = $this->get_plugin_options();
		
		add_action( 'init', array( &$this, 'init' ), 1 );
		add_filter( 'upgrader_post_install', array( &$this, 'clear_option' ) );
		

	}
	
	public function http_request_args($r, $url) {
		
		if($url == $this->plugins[ $this->plugin_slug ]->package){
			$r['method'] = 'POST';
			$r['body'] = $this->header_infos();
		}
		return $r;
	}
	
	public function install_plugins_information() {
		 global $tab;
	  
		 $api = plugins_api('plugin_information', array('slug' => stripslashes( $_REQUEST['plugin'] ) ));
		 
	}

	public function plugins_api($noidea, $action, $args) {
	
		$version_info = $this->perform_remote_request( );
		if(!$version_info) wp_die('There was an error while getting the information about the plugin. Please try again later');
		$res = $version_info->data;
		$res->slug = $this->plugin_slug;
		if(isset($res->contributors))$res->contributors = (array) $res->contributors; 
		$res->sections = (array) $res->sections; 
		return $res;
		
		
	}
	
	public function plugins_api_result($res, $action, $args) {
		if($args->slug == $this->plugin_slug){
			$res->external = true;
		}
		
		return $res;
		
	}

	public function init() {
		//Set up update checking and hook in the filter for automatic updates
		//Do upgrade stuff
		if(is_admin() && current_user_can("update_plugins")){
				
			global $pagenow, $wp_header_to_desc;

			if($pagenow == 'update.php'){
				if(($_GET['action'] == 'upgrade-plugin' && $_GET['plugin'] == $this->plugin_slug) || ($_GET['action'] == 'update-selected')){
					add_filter('http_request_args', array(&$this, 'http_request_args'), 10, 2);
				}
				
			}else if($pagenow == 'update-core.php'){
				
				//force check on the updates page
				add_filter( "plugins_api",  array( &$this, 'plugins_api' ), 10, 3);
				add_filter( "plugins_api_result",  array( &$this, 'plugins_api_result' ), 10, 3);
				$this->check_for_updates(true);
	
			}else if($pagenow == 'plugin-install.php'){
				if(isset($_GET['plugin']) && $_GET['plugin'] == $this->plugin_slug){
					//add_action( "install_plugins_pre_plugin-information", array( &$this, 'install_plugins_information' ) );
					add_filter( "plugins_api",  array( &$this, 'plugins_api' ), 10, 3);
					add_filter( "plugins_api_result",  array( &$this, 'plugins_api_result' ), 10, 3);
				}
			}
			$wp_header_to_desc[678] = 'No Purchasecode entered! Please provide a purchasecode';
			$wp_header_to_desc[679] = 'Purchasecode invalid';
			$wp_header_to_desc[680] = 'Purchasecode already in use';
			
			$this->check_periodic_updates();
			
			if ( isset( $this->plugins[ $this->plugin_slug ]->error ) ) {
				global $notice;
				$notice = $this->plugins[ $this->plugin_slug ]->error;
				add_action( 'admin_notices', array( &$this, 'notice' )); 
			}
			if ( isset( $this->plugins[ $this->plugin_slug ]->new_version ) ) {
				if( !version_compare( $this->version, $this->plugins[ $this->plugin_slug ]->new_version, '>=' ) ) {
					add_filter( 'site_transient_update_plugins', array( &$this, 'update_plugins_filter' ), 1 );
				}
			}
			
		}
	}
	//Performs a periodic upgrade check to see if the plugin needs to be upgraded or not
	private function check_periodic_updates( $force = false ) {
		$last_update = isset( $this->plugins[ $this->plugin_slug ]->last_update ) ? $this->plugins[ $this->plugin_slug ]->last_update : 0;
		if( ( time() - $last_update ) > $this->time_upgrade_check || $force ){
			$this->check_for_updates( $force );
		}
	}
	
	public function get_remote_version() {
		if ( isset( $this->plugins[ $this->plugin_slug ]->new_version ) ) {
			return $this->plugins[ $this->plugin_slug ]->new_version;
		}
		return false;
	}
	
	public function clear_option() {
		is_multisite() ? update_site_option( $this->option_name, '' ) : update_option( $this->option_name, '' );
	}
	
	private function get_plugin_options() {
		//Get plugin options
		$options = is_multisite() ? get_site_option( $this->option_name ) : get_option( $this->option_name );
		
		if ( !$options ) $options = array();
		
		return $options;
	}
	
	private function save_plugin_options() {
		if(isset($this->plugins[$this->plugin_slug]->item_data)) unset($this->plugins[$this->plugin_slug]->item_data);
		is_multisite() ? update_site_option( $this->option_name, $this->plugins ) : update_option( $this->option_name, $this->plugins );
	}
	
	public function check_for_updates( $force = false ) {

		if ( !is_array( $this->plugins ) ) return false;
		//Check to see that plugin options exist
		if ( !isset( $this->plugins[ $this->plugin_slug ] ) ) {

			$plugin_options = new stdClass;
			$plugin_options->slug = $this->plugin_slug;
			$plugin_options->package = '';
			$plugin_options->upgrade_notice = '';
			$plugin_options->new_version = $this->version;
			$plugin_options->last_update = time();
			$plugin_options->id = "0";

			$this->plugins[ $this->plugin_slug ] = $plugin_options;
			$this->save_plugin_options();
			
			//set to true to force update for the first time
			$force = true;
		}

		$current_plugin = $this->plugins[ $this->plugin_slug ];
		if( ( time() - $current_plugin->last_update ) > $this->time_upgrade_check || $force ) {
			//Check for updates
			unset($current_plugin->error);
			$version_info = $this->perform_remote_request( );
			if ( is_wp_error( $version_info ) || !$version_info){
				global $notice;
				$this->plugins[ $this->plugin_slug ]->error = $notice;
				$this->plugins[ $this->plugin_slug ]->last_update = time();
				$this->plugins[ $this->plugin_slug ]->new_version = $this->version;
				$this->save_plugin_options();
				return false;
			}
			//$version_info should be an array with keys ['version'] and ['download_url']
			if ( isset( $version_info->version ) && isset( $version_info->download_url ) ) {
				$current_plugin->new_version = $version_info->version;
				$current_plugin->package = $version_info->download_url;
				$current_plugin->last_update = time();
				if( isset( $version_info->upgrade_notice ) ) $current_plugin->upgrade_notice = $version_info->upgrade_notice;
				if( isset( $version_info->data ) ) $current_plugin->item_data = $version_info->data;
				$this->plugins[ $this->plugin_slug ] = $current_plugin;
				$this->save_plugin_options();
			}
		}
		
		return $this->plugins[ $this->plugin_slug ];
	}

	public function perform_remote_request( $body = array(), $headers = array() ) {

		$body = wp_parse_args( $body, $this->header_infos() ) ;
		
		$body = http_build_query( $body );

		$headers = wp_parse_args( $headers, array(
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Content-Length' => strlen( $body )
		) );
		

		$post = array( 'headers' => $headers, 'body' => $body );
		//Retrieve response
		$response = wp_remote_post( add_query_arg( array('envato_item_info' => '' ), esc_url( $this->remote_url )), $post );
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code != 200 || is_wp_error( $response_body ) ) {
			return false;
		}
		
		$result = json_decode( $response_body );
		
		if(!empty($result->error)){
			global $notice;
			$notice = $result->error;
			return false;
		}
		return $result;
		
	}
	
	public function notice( ) {
		global $notice;
		$msg = '';
		if($notice->errors){
			foreach($notice->errors as $error){
				$msg .= $error.'<br>'; 
			}
			
		}
	?>
		<div class="error"><p><strong><?php	echo $msg; ?></strong></p></div>
	<?php
		
	}
	//Return an updated version to WordPress when it runs its update checker
	public function update_plugins_filter( $value ) {
		if ( isset( $this->plugins[ $this->plugin_slug ] ) && $this->plugin_path ) {
			$value->response[ $this->plugin_path ] = $this->plugins[ $this->plugin_slug ];
		}
		return $value;
	}
	
	private function header_infos( ) {
		return array(
			'purchasecode' => $this->purchasecode,
			'version' => $this->version,
			'slug' => $this->plugin_slug,
			'wp-version' => get_bloginfo( 'version' ),
			'referer' => home_url(),
			'multisite' => is_multisite(),
		);
	}
	

} //end class
?>