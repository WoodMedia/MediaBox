<?php

class Ip2City {

	//maxmind doesn't provide a zip version so I've uploaded it to my dropbox (updated weekly)
	public $zip = 'https://dl.dropbox.com/u/9916342/data/GeoIPCity.zip';
	public $renewindays = 7.2;
	private $zipfile;
	private $dbfile;
	private $gi;
	private $renew = true;
	
	public function __construct() {
	
		if(!class_exists('geoiprecord')){
			include MYMAIL_DIR ."/classes/libs/geoipcity.inc";
		}
		
		$this->zipfile = MYMAIL_UPLOAD_DIR.'/GeoIPCity.dat.zip';
		$this->dbfile = MYMAIL_UPLOAD_DIR.'/GeoIPCity.dat';
		
		if(mymail_option('cities_db') && mymail_option('cities_db') != $this->dbfile){
			$this->dbfile = mymail_option('cities_db');
			$this->renew = false;
		}else if (!file_exists($this->dbfile) || !get_option('mymail_cities') ) {
			add_action('shutdown', array( &$this, 'renew' ));
		}
		
		if(file_exists($this->dbfile)){
			$this->gi = geoip_open($this->dbfile, GEOIP_STANDARD);
			if(!get_option('mymail_cities')){
				update_option('mymail_cities', filemtime($this->dbfile));
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
	
	
		$record = geoip_record_by_addr($this->gi,$ip);
		
		if (is_null($part)) {
			if(isset($record->city)) $record->city = utf8_encode(trim($record->city));
			return $record;
		}else{
			return isset($record->{$part}) ? utf8_encode($record->{$part}) : false;
		}
		
	}


	public function renew($force = false) {

		
		if (!$force && time()-get_option('mymail_cities') < 86400*$this->renewindays && $this->renew) return false;
		if (!$force && time()-get_option('mymail_cities') < 86400*$this->renewindays && get_option('mymail_cities') && !$this->renew) return false;

		global $wp_filesystem;
		
		mymail_require_filesystem();
		
		@set_time_limit(120);

		$zip = wp_remote_get( $this->zip, array('timeout' => 120, 'sslverify' => false) );

		if ( is_wp_error( $zip ) || $zip['response']['code'] != 200 ) {
			if (file_exists($this->dbfile) ) {
				@touch($this->dbfile);
				update_option('mymail_cities', filemtime($this->dbfile));
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
			mymail_update_option( 'trackcities' , false );
			return new WP_Error('file_missing', 'file is missing');
		}

		update_option('mymail_cities', filemtime($this->dbfile));

		$this->gi = geoip_open($this->dbfile, GEOIP_STANDARD);

		return $this->dbfile;

	}


	public function remove() {

		delete_option('mymail_cities');

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