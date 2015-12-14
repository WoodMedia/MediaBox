<?php if(!defined('ABSPATH')) die('not allowed');

class mymail_placeholder {
	
	private $content;
	private $placeholder = array();
	private $rounds = 2;


	public function __construct($content = '', $basic = NULL) {
		$this->content = $content;
		
		//hardcoded tags
		if(!is_array($basic)){
			$timestamp = current_time('timestamp');
			$basic = array(
				'year' => date('Y', $timestamp),
				'month' => date('m', $timestamp),
				'day' => date('d', $timestamp),
				'hour' => date('H', $timestamp),
				'minute' => date('m', $timestamp),
			);
		}
		
		$this->add($basic);
		$this->add(mymail_option('custom_tags', array()));
		$this->add(mymail_option('tags', array()));

	}
	
	
	public function __destruct() {
	}
	
	public function set_content($content = '') {
		$this->content = $content;
	}
	
	public function get_content($removeunused = true, $placeholders = array(), $relative_to_absolute = false ) {
		return $this->do_placeholder($removeunused, $placeholders, $relative_to_absolute );
	}
	
	
	public function clear_placeholder( ) {
		$this->placeholder = array();
	}
	
	
	public function add( $placeholder = array(), $brackets = true ) {
		if(empty($placeholder)) return false;
		foreach($placeholder as $key => $value){
			($brackets)
				? $this->placeholder['{'.$key.'}'] = $value
				: $this->placeholder[$key] = $value;
		}
	}
	
	public function do_placeholder($removeunused = true, $placeholders = array(), $relative_to_absolute = false, $round = 1 ) {
		
		
		$this->add($placeholders);
		
		$this->replace_dynamic($relative_to_absolute);
		
		foreach($this->placeholder as $search => $replace){
			$this->content = str_replace( $search, $replace, $this->content );
		}
		
		if($count = preg_match_all( '#\{([a-z0-9-_]+)\|([^\}]+)\}#', $this->content, $hits_fallback )){
		
			for ( $i = 0; $i < $count; $i++ ) {
				
				$search = $hits_fallback[0][$i];
				$placeholder = '{'.$hits_fallback[1][$i].'}';
				$fallback = $hits_fallback[2][$i];
				//use placeholder
		
				if ( !empty( $this->placeholder[$placeholder] ) ) {
					$this->content = str_replace( $search, $this->placeholder[$placeholder], $this->content );
				
				//use fallback
				} else if($removeunused && $round < $this->rounds){
				
					$this->content = str_replace( $search, $fallback, $this->content );
					
				}
			}
			
		}
		
		global $mymail_mytags;
		
		if(!empty($mymail_mytags)){
		
			foreach($mymail_mytags as $tag => $replacecallback){
			
				if($count = preg_match_all( '#\{'.$tag.':?([^\}|]+)?\|?([^\}]+)?\}#', $this->content, $hits_fallback )){
					
					for ( $i = 0; $i < $count; $i++ ) {
					
						$search = $hits_fallback[0][$i];
						$option = $hits_fallback[1][$i];
						$fallback = $hits_fallback[2][$i];
						$replace = call_user_func_array($replacecallback, array($option, $fallback));
						
						if(!empty($replace)){
							$this->content = str_replace( $search, ''.$replace, $this->content );
						}else{
							$this->content = str_replace( $search, ''.$fallback, $this->content );
						}
					
					}
					
				}
				
			}
		}
		
		//do it twice to get tags inside tags ;)
		if($round < $this->rounds)	return $this->do_placeholder($removeunused, $placeholders, $relative_to_absolute, ++$round );
		
		//remove unused placeholders
		if($removeunused){
		
			preg_match_all('#(<style(>|[^<]+?>)([^<]+)<\/style>)#', $this->content, $styles);
			
			if($hasstyle = !empty($styles[0])){
				$this->content = str_replace( $styles[0], '%%%STYLEBLOCK%%%', $this->content );
			}
			
			$this->content = preg_replace('#\{([a-z0-9-_,;:| ]+)\}#','', $this->content);
			
			if($hasstyle){
				$search = explode('|', str_repeat('/%%%STYLEBLOCK%%%/|', count($styles[0])-1).'/%%%STYLEBLOCK%%%/');
				$this->content = preg_replace($search, $styles[0], $this->content, 1);
			}
		}
		
		
		return $this->content;
		
	}
	
	public function replace_links($base, $links = array(), $hash = '') {
		$used = array();
		foreach ( $links as $link ) {
			
			$search = '"'.$link.'"';
			
			$link = apply_filters('mymail_replace_link', $link, $base, $hash);
			
			if(!isset($used[$link])){
				$replace = '"'.$base . '&k=' . $hash . '&t=' . urlencode( $link ).'&c=0"';
				$used[$link] = 1;
			}else{
				$replace = '"'.$base . '&k=' . $hash . '&t=' . urlencode( $link ).'&c='.($used[$link]++).'"';
			}
			
			$pos = strpos($this->content, $search);
			if ($pos !== false) {
				$this->content = substr_replace( $this->content, $replace, $pos, strlen($search) );
			}			
		}
	}
	
	
	public function share_service($url, $title = '' ) {
		
		$placeholders = array();
		
		if($count = preg_match_all('#\{(share:(twitter|facebook|google|linkedin) ?([^}]+)?)\}#', $this->content, $hits)){
			
			require_once MYMAIL_DIR.'/includes/social_services.php';
			
			for($i = 0; $i < $count; $i++){

				$service = $hits[2][$i];
				
				if(isset($mymail_social_services[$service]))
				
					$_url = str_replace('%title', (!empty($hits[3][$i])) ? '' : urlencode($title), ($mymail_social_services[$service]['url']));
					$_url = str_replace('%url', (!empty($hits[3][$i])) ? urlencode($hits[3][$i]) : urlencode(($url)), $_url);
					
					$url_content = apply_filters('mymail_share_button_'.$service, '<img src="'.MYMAIL_URI.'/assets/img/share/share_'.$service.'.png" />');
					$placeholders[$hits[1][$i]] = '<a href="'.$_url.'" class="social">'.$url_content.'</a>'."\n";
					
				
			}
			
		}
		
		$this->add($placeholders);
		
	}
	
	
	public function replace_dynamic( $relative_to_absolute = false ) {
		
		//$placeholders = array();
		
		$pts = array_keys(get_post_types( array( 'public' => true ), 'objects' ));
		$pts = array_diff($pts, array( 'newsletter', 'attachment'));
		$pts = implode('|',$pts);
		
		//placeholder images
		$ajaxurl = admin_url('admin-ajax.php');
		if($count = preg_match_all( '#src="'.$ajaxurl.'\?action=mymail_image_placeholder([^"]+)"#', $this->content, $hits )){
		
			global $mymail;
		
			for ( $i = 0; $i < $count; $i++ ) {
			
				$search = $hits[0][$i];
				$querystring = str_replace('&amp;', '&', $hits[1][$i]);
				parse_str($querystring, $query);
				
				if(isset($query['tag'])){
				
					$replace_to = wp_cache_get( 'mymail_'.$querystring );
					
					if ( false === $replace_to ) {
						$parts = explode(':', trim($query['tag']));
						$width = isset($query['w']) ? intval($query['w']) : NULL;
						$height = isset($query['h']) ? intval($query['h']) : NULL;
						
						$post_type = str_replace('_image', '', $parts[0]);
						
						$extra = explode('|', $parts[1]);
						$term_ids = explode(';', $extra[0]);
						$fallback_id = isset($extra[1]) ? intval($extra[1]) : mymail_option('fallback_image');
						
						$post_id = intval(array_shift($term_ids));
						
						if($post_id < 0){
						
							$post = $this->get_last_post( abs($post_id)-1, $post_type, $term_ids );
							
						}else if($post_id > 0){
						
							$post = get_post($post_id);
							
							if($relative_to_absolute) continue;
							
						}
						
						if(!$relative_to_absolute){
						
							$thumb_id = get_post_thumbnail_id($post->ID);
							
							$org_src = wp_get_attachment_image_src( $thumb_id, 'full');
							
							if(empty($org_src) && $fallback_id){
							
								$org_src = wp_get_attachment_image_src( $fallback_id, 'full');
								
							}
							
							if(!empty($org_src)){
							
								$img = $mymail->create_image(NULL, $org_src[0], $width, $height);
								
								$replace_to = 'src="'.$img['url'].'" height="'.$img['height'].'"';
								
								wp_cache_set( 'mymail_'.$querystring, $replace_to );
								
							}else{
							
								
							}
						}else{
						
							$replace_to = str_replace('tag='.$query['tag'], 'tag='.$post_type.'_image:'.$post->ID, $search);
							
						}
						
					}
					
					if($replace_to) $this->content = str_replace( $search, $replace_to, $this->content );
					
				}
			}
		}
		
		if(!$relative_to_absolute){
		
			//absolute posts
			if($count = preg_match_all('#\{(('.$pts.')_([^}-]+):([\d]+))\}#', $this->content, $hits)){
				
				for($i = 0; $i < $count; $i++){
					$post = get_post($hits[4][$i]);
					if($post){
						$what = $hits[3][$i];
						switch($what){
							case 'link':
							case 'permalink':
								$replace_to = get_permalink($post->ID);
								break;
							case 'date':
							case 'date_gmt':
							case 'modified':
							case 'modified_gmt':
								$replace_to = date(get_option('date_format').' '.get_option('time_format'), strtotime($post->{'post_'.$what}));
								break;
							case 'excerpt':
								$replace_to = strip_shortcodes((!empty($post->{'post_excerpt'}) ? $post->{'post_excerpt'} : $post->{'post_content'}));
								break;
							case 'content':
								$replace_to = strip_shortcodes(($post->{'post_content'}));
								break;
							default:
								$replace_to = isset($post->{'post_'.$what})
									? $post->{'post_'.$what}
									: $post->{$what};
						}
						
					}else{
						$replace_to = '';
					}
					
					$this->content = str_replace( $hits[0][$i], $replace_to, $this->content );
					//$placeholders[$hits[1][$i]] = $replace_to;
				}
				
			}
		
		}
		
		//relative posts without options
		if($count = preg_match_all('#\{(('.$pts.')_([^}-]+):-([\d]+))\}#', $this->content, $hits)){
			
			for($i = 0; $i < $count; $i++){
			
				$offset = $hits[4][$i]-1;
				$post_type = $hits[2][$i];
				$post = $this->get_last_post( $offset, $post_type );
				
				if($post){
				
					$what = $relative_to_absolute ? '_relative_to_absolute' : $hits[3][$i];
					switch($what){
						case '_relative_to_absolute':
							$replace_to = '{'.$post_type.'_'.$hits[3][$i].':'.$post->ID.'}';
							break;
						case 'link':
						case 'permalink':
							$replace_to = get_permalink($post->ID);
							break;
						case 'date':
						case 'date_gmt':
						case 'modified':
						case 'modified_gmt':
							$replace_to = date(get_option('date_format').' '.get_option('time_format'), strtotime($post->{'post_'.$what}));
							break;
						case 'excerpt':
							$replace_to = strip_shortcodes((!empty($post->{'post_excerpt'}) ? $post->{'post_excerpt'} : $post->{'post_content'}));
							break;
						case 'content':
							$replace_to = strip_shortcodes(($post->{'post_content'}));
							break;
						default:
							$replace_to = isset($post->{'post_'.$what})
								? $post->{'post_'.$what}
								: $post->{$what};
					}
					
				}else{
					$replace_to = '';
				}
				
				$this->content = str_replace( $hits[0][$i], $replace_to, $this->content );
				//$placeholders[$hits[1][$i]] = $replace_to;
			}
			
		}
		
		
		//relative posts with options
		if($count = preg_match_all('#\{(('.$pts.')_([^}-]+):-([\d]+);([0-9;,]+))\}#', $this->content, $hits)){
			
			for($i = 0; $i < $count; $i++){
			
				$search = $hits[0][$i];
				$offset = $hits[4][$i]-1;
				$post_type = $hits[2][$i];
				$term_ids = explode(';', trim($hits[5][$i]));
				$post = $this->get_last_post( $offset, $post_type, $term_ids  );
			
				if($post){
					$what = $relative_to_absolute ? '_relative_to_absolute' : $hits[3][$i];
					switch($what){
						case '_relative_to_absolute':
							$replace_to = '{'.$post_type.'_'.$hits[3][$i].':'.$post->ID.'}';
							break;
						case 'link':
						case 'permalink':
							$replace_to = get_permalink($post->ID);
							break;
						case 'date':
						case 'date_gmt':
						case 'modified':
						case 'modified_gmt':
							$replace_to = date(get_option('date_format').' '.get_option('time_format'), strtotime($post->{'post_'.$what}));
							break;
						case 'excerpt':
							$replace_to = strip_shortcodes((!empty($post->{'post_excerpt'}) ? $post->{'post_excerpt'} : $post->{'post_content'}));
							break;
						case 'content':
							$replace_to = strip_shortcodes(($post->{'post_content'}));
							break;
						default:
							$replace_to = isset($post->{'post_'.$what})
								? $post->{'post_'.$what}
								: $post->{$what};
					}
					
				}else{
					$replace_to = '';
				}
				
				$this->content = str_replace( $search, $replace_to, $this->content );
				//$placeholders[$hits[1][$i]] = $replace_to;
			}
			
		}
		
		
		if(!$relative_to_absolute){
			if($count = preg_match_all('#\{(tweet:([^}|]+)\|?([^}]+)?)\}#', $this->content, $hits)){
				
				for($i = 0; $i < $count; $i++){
					$search = $hits[0][$i];
					$tweet = $this->get_last_tweet($hits[2][$i], $hits[3][$i]);
					$this->content = str_replace( $search, $tweet, $this->content );
					//$placeholders[$hits[1][$i]] = $tweet;
				}
				
			}
		}
		
	}
	
	
	private function get_last_post( $offset = 0, $post_type = 'post', $term_ids = false ) {
		
		$post = wp_cache_get( 'mymail_get_last_post_'.$offset.'_', $post_type );
		
		if ( false !== $post ) return $post;
		
		$args = array(
			'numberposts' => 1,
			'post_type' => $post_type,
			'offset' => $offset,
		);
		
		if(is_array($term_ids)){
			
			$tax_query = array();
			
			$taxonomies = get_object_taxonomies( $post_type, 'names' );
			
			for($i = 0; $i < count($term_ids); $i++){
				if(empty($term_ids[$i])) continue;
				$tax_query[] = array(
					'taxonomy' => $taxonomies[$i],
					'field' => 'id',
					'terms' => explode(',', $term_ids[$i]),
				);
			}
			
			if(!empty($tax_query)){
				$tax_query['relation'] = 'AND';
				$args = wp_parse_args( $args, array('tax_query' => $tax_query));
			}
			
		}
		
		$post = get_posts( $args );
		
		if(!$post) return false;
		
		$post = $post[0];
		
		if(!$post->post_excerpt){
			if ( preg_match('/<!--more(.*?)?-->/', $post->post_content, $matches) ) {
				$content = explode($matches[0], $post->post_content, 2);
				$post->post_excerpt = trim($content[0]);
			}
		}
		
		$post->post_excerpt = apply_filters( 'the_excerpt', $post->post_excerpt );
		
		$post->post_content = apply_filters( 'the_content', $post->post_content );
		
		wp_cache_set( 'mymail_get_last_post_'.$offset, $post, $post_type );
		
		return $post;
	}
	
	private function get_last_tweet( $username, $fallback = '' ) {
		
		if ( false === ( $tweet = get_transient( 'mymail_tweet_'.$username ) ) ) {
			$response = wp_remote_get('http://api.twitter.com/1/statuses/user_timeline/'.$username.'.json?exclude_replies=1&include_rts=1&count=1&include_entities=1');
			
			if(is_wp_error($response)) return $fallback;
			$data = json_decode($response['body']);
			
			if(isset($data->errors)) return $fallback;
			if(isset($data->error)) return $fallback;
			
			$tweet = $data[0];
			
			if(!isset($tweet->text)) return $fallback;
			
			if($tweet->entities->hashtags){
				foreach($tweet->entities->hashtags as $hashtag) {
					$tweet->text = str_replace('#'.$hashtag->text, '#<a href="https://twitter.com/search/%23'.$hashtag->text.'">'.$hashtag->text.'</a>', $tweet->text);
					
				}
			}
			if($tweet->entities->urls){
				foreach($tweet->entities->urls as $url) {
					$tweet->text = str_replace($url->url, '<a href="'.$url->url.'">'.$url->display_url.'</a>', $tweet->text);
					
				}
			}
			
			//$tweet->text = preg_replace('/(http|https|ftp|ftps)\:\/\/([a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*))?/','<a href="\0">\2</a>', $tweet->text);
			//$tweet->text = preg_replace('/(^|\s)#(\w+)/','\1#<a href="https://twitter.com/search/%23\2">\2</a>',$tweet->text);
			$tweet->text = preg_replace('/(^|\s)@(\w+)/','\1@<a href="https://twitter.com/\2">\2</a>', $tweet->text);
			
			set_transient( 'mymail_tweet_'.$username , $tweet, 60*mymail_option('tweet_cache_time') );
		}
		
		return $tweet->text;
	}
	


}
?>