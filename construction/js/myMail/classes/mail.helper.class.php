<?php if (!defined('ABSPATH')) die('not allowed');


//this class extends PHPMailer and offers some fixes
if(!class_exists('PHPMailer')) require_once MYMAIL_DIR . '/classes/libs/phpmailer/class.phpmailer.php';
if(!class_exists('SMTP')) require_once MYMAIL_DIR . '/classes/libs/phpmailer/class.smtp.php';

global $phpmailer;
if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
	$phpmailer = new PHPMailer( true );
}

class mymail_mail_helper extends PHPMailer {



	public function __construct($exceptions = false) {
		$this->XMailer = 'MyMail ' . MYMAIL_VERSION . ' ('.$this->Version.') by revaxarts.com (http://rxa.li/mymail)';
		$this->CharSet = 'UTF-8';
		parent::__construct( $exceptions );
	}
	
	public static function ValidateAddress($address) {
		return mymail_is_email($address);
	}
	public function PreSend() {
		try{
		
			return parent::PreSend();
			
		} catch (phpmailerException $e) {
			$this->SetError($e->getMessage());
			if ($this->exceptions) {
				throw $e;
			}
			return false;
		}
		
	}
	
	public function html2text( $html ){

		preg_match('#<body[^>]*>.*?<\/body>#is', $html, $matches);
		
		if(!empty($matches)){
			$html = $matches[0];
		}

		$text = preg_replace('# +#',' ',$html);
		$text = str_replace(array("\n","\r","\t"),'',$text);
		//$piclinks = "#< *a[^>]*> *< *img[^>]*> *< *\/ *a *>#isU";
		$piclinks = '/< *a[^>]*href *= *"([^#][^"]*)"[^>]*> *< *img[^>]*> *< *\/ *a *>/Uis';
		$style = "#< *style(?:(?!< */ *style *>).)*< */ *style *>#isU";
		$strikeTags =  '#< *strike(?:(?!< */ *strike *>).)*< */ *strike *>#iU';
		$headlines = '#< *(h1|h2)[^>]*>#Ui';
		$stars = '#< *li[^>]*>#Ui';
		$return1 = '#< */ *(li|td|tr|div|p)[^>]*> *< *(li|td|tr|div|p)[^>]*>#Ui';
		$return2 = '#< */? *(br|p|h1|h2|legend|h3|li|ul|h4|h5|h6|tr|td|div)[^>]*>#Ui';
		$links = '/< *a[^>]*href *= *"([^#][^"]*)"[^>]*>(.*)< *\/ *a *>/Uis';
		$text = preg_replace(array($piclinks,$style,$strikeTags,$headlines,$stars,$return1,$return2,$links),array('${1}'."\n",'','',"\n\n","\n● ","\n","\n",'${2} ( ${1} )'),$text);
		$text = str_replace(array(" ","&nbsp;"),' ',strip_tags($text));
		$text = trim(@html_entity_decode($text, ENT_QUOTES, $this->CharSet ));
		$text = preg_replace('# +#',' ',$text);
		$text = preg_replace('#\n *\n\s+#',"\n\n",$text);
		
		return $text;
		
	}

}

class mailerException extends Exception {}
?>