<?php

class Ip2Country {

	//maxmind doesn't provide a zip version so I've uploaded it to my dropbox (updated weekly)
	public $zip = 'https://dl.dropbox.com/u/9916342/data/GeoIPv6.zip';
	public $renewindays = 6.8;
	private $zipfile;
	private $dbfile;
	private $gi;
	private $renew = true;

	public function __construct() {
	
		if(!function_exists('geoip_open')){
			include MYMAIL_DIR ."/classes/libs/geoip.inc";
		}
		
		$this->zipfile = MYMAIL_UPLOAD_DIR.'/GeoIPv6.dat.zip';
		$this->dbfile = MYMAIL_UPLOAD_DIR.'/GeoIPv6.dat';
		
		
		if(mymail_option('countries_db') && mymail_option('countries_db') != $this->dbfile){
			$this->dbfile = mymail_option('countries_db');
			$this->renew = false;
		}else if (!file_exists($this->dbfile) || !get_option('mymail_countries') ) {
			add_action('shutdown', array( &$this, 'renew' ));
		}
		
		if(file_exists($this->dbfile)){
			$this->gi = geoip_open($this->dbfile, GEOIP_STANDARD);
			if(!get_option('mymail_countries')){
				update_option('mymail_countries', filemtime($this->dbfile));
			}
		}
	}


	public function __destruct() {
		geoip_close($this->gi);
	}


	public function country($code) {
		return (isset($this->gi->GEOIP_COUNTRY_CODE_TO_NUMBER[strtoupper($code)])) ? $this->gi->GEOIP_COUNTRY_NAMES[$this->gi->GEOIP_COUNTRY_CODE_TO_NUMBER[strtoupper($code)]] : $code;
	}


	public function get_contries() {

		$rawcountries = $this->gi->GEOIP_COUNTRY_NAMES;
		$countries = array();
		foreach ($rawcountries as $key => $country) {
			if (!$key) continue;
			$countries[$this->gi->GEOIP_COUNTRY_CODES[$key]] = $country;
		}

		return $countries;
	}


	public function get($ip, $part = NULL) {

		//append two semicollons for ipv4 addresses
		if(strlen($ip) <= 15) $ip = '::'.$ip;
		

		if (!is_null($part)) {
			if (function_exists('geoip_country_'.$part.'_by_addr_v6')) {
				return call_user_func('geoip_country_'.$part.'_by_addr_v6', $this->gi, $ip);
			}else {
				return false;
			}
		}
		$return = (object) array(
			'id' => call_user_func('geoip_country_ip_by_addr_v6', $this->gi, $ip),
			'code' => call_user_func('geoip_country_code_by_addr_v6', $this->gi, $ip),
			'country' => call_user_func('geoip_country_name_by_addr_v6', $this->gi, $ip),
		);
		return $return;
	}


	public function renew($force = false) {
	
		if (!$force && time()-get_option('mymail_countries') < 86400*$this->renewindays && get_option('mymail_countries') && !$this->renew) return false;
	
		global $wp_filesystem;
		
		mymail_require_filesystem();
		
		@set_time_limit(120);

		$zip = wp_remote_get( $this->zip, array('timeout' => 120, 'sslverify' => false) );
		
		if ( is_wp_error( $zip ) || $zip['response']['code'] != 200 ) {
			if (file_exists($this->dbfile) ) {
				@touch($this->dbfile);
				update_option('mymail_countries', filemtime($this->dbfile));
				return $this->dbfile;
			}
			return $zip;
		}

		if (!is_dir( dirname($this->dbfile) )) wp_mkdir_p( dirname($this->dbfile) ) ;

		if ( !$wp_filesystem->put_contents( $this->zipfile, $zip['body'], FS_CHMOD_FILE) ) {
			return new WP_Error('write_file', 'error saving file');
		}

		if ( !unzip_file($this->zipfile, dirname($this->dbfile)) ) {
			return new WP_Error('unzip_file', 'error unzipping file');
		}else if ( !$wp_filesystem->delete( $this->zipfile )) {
			return new WP_Error('delete_file', 'error deleting old file');
		}

		if (!file_exists($this->dbfile) ) {
			mymail_update_option( 'trackcountries' , false );
			return new WP_Error('file_missing', 'file is missing');
		}

		update_option('mymail_countries', filemtime($this->dbfile));

		$this->gi = geoip_open($this->dbfile, GEOIP_STANDARD);

		return $this->dbfile;

	}


	public function remove() {

		delete_option('mymail_countries');

		global $wp_filesystem;
		mymail_require_filesystem();

		return $wp_filesystem->delete( $this->dbfile );

	}


	public function get_real_ip() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}


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


}


?>