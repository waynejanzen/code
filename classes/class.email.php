<?

// var should be either private or public and $from_email needs to be define() FROM config files
// added $this->patient for child nodes of providers

class email {
	
	public $colors = array(
		'unsubscribe'=>'#1A83CF',
		'company'    =>'#1A83CF',
		'items'      =>'#3d3d3c',
		'header'     =>'#374ca0',
		'footer'     =>'#374ca0',
		'border'     =>'#EDEDED',
		'foottext'   =>'#999',
		'label'     =>'#999'
	);
	
	private $transport = NULL;
	private $swift = NULL;
	private $message = NULL;
	
	var $from_email = '';
	var $company_name = '';
	var $url = '';
	var $included = false;
	
	var $defaults = array();
	
	var $css = array();
	var $user = array();
	var $to = array();
	var $appendemail='';
	var $tags = array();
	var $emailheader = '';
	var $subject = false;
	var $searches = array();
	var $replaces = array();
	var $cc_list = array();
	var $bcc_list = array();
	
	var $responder = array();
	private $conn;
	
	function __construct($user,$from_email, $company_name=COMPANY_NAME, $url=SITE_URL,$data=array()) {

		global $db;
		
		$this->company_name = $company_name;
		$this->url = $url;

		if($db instanceof common)
			$this->conn = $db;
		else $this->conn = new common();
		
		$this->defaults = $this->conn->id('defaults',1);		
		$this->colors['header'] = $this->defaults['maincolor'];
		$this->colors['footer'] = $this->defaults['maincolor'];
				
		if(is_object($user)) {
			// set data from the userclass object		
			$this->user = $user->user;
			$this->to = $user->user;
			
		} else {
			// for auto responders when not logged in, pass array of data to this class
			$this->user = $user;
			$this->to = $user;
		}
		
		$this->css = $this->_css();
		$this->tags = $this->_setTags($this->to,$data); // these two function must be called AFTER user and to is set
		
		
		$this->emailheader = $this->_getHeader();
		$this->setFrom($from_email);
		
		
		/** set up the message here so we can add attachments via a function **/
		if(!$this->included) {
			if(defined('CMS_CORE_VERSION')) require_once SERVER_PATH.'_core/'.CMS_CORE_VERSION.'/_libs/swift_lib/swift_required.php';
			$this->included = true;
		}
		
		if($this->transport == NULL)  {
			$this->transport = Swift_SmtpTransport::newInstance('smtp1.canadawebhosting.com');
/*			$this->transport = Swift_SmtpTransport::newInstance('smtp.com')
				->setUsername('contactforms')
				->setPassword('pg2013contactFORM')
				; */
		}
		
		$this->swift = Swift_Mailer::newInstance($this->transport);
		$this->message = Swift_Message::newInstance();
	}
	
	public function setTo($user) {
		$this->to = $user->user;
		$res = $this->_setTags($this->to);
		$this->tags = $res;
	}
	
	public function addCC($email) {
		array_push($this->cc_list,$email);
	}
	public function addBcc($email) {
		array_push($this->bcc_list,$email);
	}
	
	public function setToEmail($to='') {
		$this->to = $to;
	}
	
	public function setSubject($subject = '') {		
		$this->subject = true;
		$this->message->setSubject($subject);
	}
	
	public function setFrom($from = '') {		
		$this->from_email = $from;
	}	
	
	public function setHeader($img) {
		$this->emailheader = $this->_getHeader($img);
	}
	
	
	public function addAttachment($path) {
		$this->message->attach(Swift_Attachment::fromPath($path));
	}
	
	public function replace($data) {
		return preg_replace($this->searches,$this->replaces,$data);
	}
	
	
	public function buildEmail($key,$additional_data='',$autosend=true,$newsletter=true) {
		
//		ini_set('display_errors',1);
		
		$responder = $this->conn->bykey('autoresponders','action',$key);
		$this->ar_id = $responder['id'];
		
		$this->responder = $responder;
		
		
		if($responder['active'] == 'N') return false;
		else {
			
			foreach($this->tags as $k=>$v) {
				$this->searches[] = "/$k/";
				$this->replaces[] = "$v";
			}
			
			if(!$this->subject) $this->setSubject($this->replace($responder['subject']));
											
			$html_data = '<!doctype html>
			<html>
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
			</head>
			<body style="'.$this->css['body'].'">
			<table style="width: 100%; max-width: 760px; padding: 10px; border: solid '.$this->colors['border'].' 2px; -webkit-border-radius: 4px; border-radius: 4px; -webkit-text-size-adjust:none;">
				<tbody>
					'.$this->emailheader.'
										
					<tr><td>&nbsp;</td></tr>
					<tr>
						<td style="padding: 0 15px;">
					'.nl2br(stripslashes($this->replace($responder['email_body']))).$this->appendemail.$additional_data.'
						</td>
					</tr>
					'.$this->_footer(true,$newsletter).'
				<tbody>
			</table>
			</body>
			</head>';
			
			$plain_text = stripslashes($this->replace($responder['subject']))."\r\r".stripslashes(strip_tags($this->replace($responder['email_body'])))."\r\r".$this->_footer(false)."\r\r";
			
			$data['email_data'] = $html_data;
			$data['plain_text'] = $plain_text;
			
			if($autosend) {
				$this->appendemail = ''; // clear append data before next email
				return $this->sendEmail($data);
			} else return $data;
		}
	}
	
	// data,cs,prov
	
	public function sendEmail($email_data=array(), $from=array(), $to=array()) {
		
		$update = false;
		if(!empty($from)) {
			$update = true;			
			$c = $this->_setTags($from);	
			$v = $this->tags;
			$this->tags = array_merge($c,$v);
			$update = true;
		}
		
		if(!empty($to)) {
			$update = true;
			$c = $this->_setTags($to);
			$v = $this->tags;
			$this->tags = array_merge($c,$v);
		}
		
		foreach($this->tags as $k=>$v) {
			$this->searches[] = "/$k/";
			$this->replaces[] = "$v";
		}
		
		if($update) {
			$email_data['email_data'] = stripslashes($this->replace($email_data['email_data']));
			$email_data['plain_text'] = stripslashes($this->replace($responder['subject']))."\r\r".stripslashes(strip_tags($this->replace($email_data['plain_text'])));
				
		}

		if(empty($to))  {
			if(is_array($this->to))
				$to = array($this->to['email']=>$this->to['email']);
			else 
				$to = array($this->to=>$this->to);
		}

		if(empty($from)) {
			$from = array($this->from_email=>$this->company_name);	
		}
		
		if(!isset($_GET['test'])) {		
			
			
			if(!$this->subject) $this->setSubject($this->replace($this->responder['subject']));

			$this->message->setFrom($from);
			$this->message->setTo($to);
			$this->message->setCc($this->cc_list);
			$this->message->setBcc($this->bcc_list);
			$this->message->setBody($email_data['email_data'],"text/html");
			$this->message->addPart($email_data['plain_text'],"text/plain");
					
			return $this->swift->send($this->message);
			
		} else {
			print $email_data['email_data'];
			die();	
		}
	}
	
	
	private function _css() {
		$css = array();
		
		$color1 = $this->colors['items'];
		$color2 = $this->colors['header'];
		$color3 = $this->colors['footer'];
		
		$css['body'] = "font-family: Tahoma, Geneva, sans-serif; font-size: 12px; line-height: 18px; -webkit-text-size-adjust:none;";
		$css['subject_style'] = "font-size: 18px; font-weight: bold; -webkit-text-size-adjust:none;";
		$css['label'] = "font-weight: bold; width: 200px; text-align: right; float: left; clear: both; color: ".$this->colors['label']."; font-size: 13px; -webkit-text-size-adjust:none;";
		$css['item'] = "float: left; margin-left: 10px; color: $color1; font-size: 13px; -webkit-text-size-adjust:none;";
		$css['foot'] = "float: left; color: ".$this->colors['foottext']."; font-size: 10px; border-top: 5px solid $color3; max-width: 760px; -webkit-text-size-adjust:none; width: 100%";
		$css['header'] = "float: left; border-bottom: 5px solid $color2; width: 100%; max-width: 760px; -webkit-text-size-adjust:none;";
		
		return $css;
	}
	
	
	private function _setTags($targ,$data=array()) {
		if($targ instanceof user)
			$targ = $targ->user;
			
		$tags = array();
								
		if(isset($targ['first_name']))
			$tags['{{firstname}}'] = $targ['first_name'];
			
		if(isset($targ['last_name']))
			$tags['{{lastname}}'] = $targ['last_name'];
		
		if(isset($targ['first_name']) && isset($targ['last_name']))
			$tags['{{fullname}}'] = $targ['first_name'].' '.$targ['last_name'];			

		if(isset($targ['password']))
			$tags['{{password}}'] = $targ['password'];
			
		if(isset($targ['email']))
			$tags['{{email}}'] = $targ['email'];
		
		if(isset($targ['pwtoken']))
			$tags['{{recoverlink}}'] = SITE_URL.'reset?id='.$targ['id'].'&token='.$targ['pwtoken'].'&c='.sha1($targ['password']);
			
		$tags['{{COMPANYNAME}}'] = COMPANY_NAME;
			
		if(!empty($data)) {
			
			
			foreach($data as $k=>$v) {
				$tags['{{'.$k.'}}'] = $v;
			}
			
			if(isset($data['pwresetlink']))
				$tags['{{passwordlink}}'] = $data['pwresetlink'];

		}
		
		
		
		$tags['(\/)?_userfiles'] = $this->url.'_userfiles';
		
		if(!is_array($tags))
			$tags = array();
	
		return($tags);
	}
	
	private function _getHeader($img = 'default.jpg') {
		$content = '
			<table style="width: 100%; max-width: 760px">
			<tr>
				<td style="'.$this->css['header'].'">
					<a href="'.$this->url.'" border="0">
						<img src="'.$this->url.'assets/images/email_headers/'.$img.'" style="border: none; display:block;" height="120px" />
					</a>
				</td>
			</tr>

			</table>';
		return $content;
	}
	
	private function _footer($html=true,$newsletter=true) {
		
		if($newsletter) {
			if($html) $unsubscribe = '  |   If you wish not to receive newsletters from <a href="'.$this->url.'login"  style="color: '.$this->colors['company'].'">'.$this->company_name.'</a>, <a href="'.SITE_URL.'unsubscribe" target="_blank"  style="'.$this->colors['unsubscribe'].'">Unsubscribe</a>';
			else $unsubscribe = "  |   If you wish not to receive newsletters from ".$this->company_name.", Unsubscribe by clicking here ".SITE_URL."unsubscribe";
		} else $unsubscribe = '';
		
		if($html) {
			return '<table style="width: 100%; max-width: 760px"><tr><td style="'.$this->css['foot'].'">This email is subject to <a href="'.$this->url.'" style="color:'.$this->colors['company'].'">'.$this->company_name.'&rsquo;s</a> standard terms of use and privacy policy.<br/>
You have received this email because you are a registered member of <a href="'.$this->url.'"  style="color: '.$this->colors['company'].';">'.$this->company_name.'</a>.<br/>
				E-Newsletter solution provided by Pixelgems Creative Inc. &copy; '.date('Y').$unsubscribe.'</td><tr></table>';
		} else {
			return "This email is subject to ".$this->company_name."&rsquo;s standard <a href='".$this->url."terms' target='_blank'>terms</a> of use and <a href='".$this->url."privacy' target='_blank'>privacy policy</a>.<br/>
You have received this email either because you are a registered member of ".$this->company_name.", or at the request of a registered healthcare provider of ".$this->company_name.".\r\rE-Newsletter solution provided by Pixelgems Creative Inc. &copy; ".date('Y').$unsubscribe;
		}
	}
}
?>
