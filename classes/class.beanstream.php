<?


////////// TEST KEYS ////////////////////
define("DECLINED",'4003050500040005');
define("APPROVED",'4030 0000 1000 1234');
define('PIXELGEMS_MERCHANT',);
define("BUSERNAME","");
define("BPASS","");
/////////////////////////////////////////


class beanstream {
	public $merchant_id;
	public $order_number;
	public $test;
	public $posts = array();
	public $giftcard_found = false;
	public $token = '';

	protected $amount;	
	protected $name_on_card;
	protected $card_number;
	protected $exp_month;
	protected $exp_year;
	protected $cvd;

	private $conn;
	
	public function __construct($merchant_id='', $amount, $test=false) {
		global $db;
		$this->conn = $db;
		$this->amount = $amount;
		$this->test = $test;
		$this->merchant_id = $merchant_id;
		if($this->test) $this->merchant_id = PIXELGEMS_MERCHANT;
	}
	
	public function getUser($db='',$user=''){
		return select_single_by_id($db,$user);
	}

	public function setUser($user=array()) {

		$this->user = $user;
		$this->order_number = $user['id'].'-'.time();
	}
	
	public function setCard($cc=array()) {
		if($this->test) {
			$this->name_on_card = 'Test Card';
			$this->card_number = APPROVED;
			$this->exp_month = 12;
			$this->exp_year = 14;
			$this->cvd = '123';

		} else {
			$this->name_on_card = $cc['cardholder'];
			$this->card_number = $cc['number'];
			$this->exp_month = $cc['exp_month'];
			$this->exp_year = $cc['exp_year'];
			$this->cvd = $cc['cvd'];
			
			if(strlen($cc['exp_year']) == 4) $this->exp_year = substr($cc['exp_year'],2,4);
		}
	}
	
	public function setPosts($type='bill') {
		$this->posts = array(
			"requestType" => "BACKEND",
			"merchant_id" => $this->merchant_id,
			"trnCardOwner" => urlencode($this->name_on_card),
			"trnCardNumber" => urlencode($this->card_number),
			"trnExpMonth" => $this->exp_month,
			"trnExpYear" => $this->exp_year,
			"trnCardCvd" => $this->cvd,
			"trnOrderNumber" => $this->order_number,
			"trnAmount" => $this->amount,
			"ordEmailAddress" => $this->user['email'],
			"ordName" => urlencode($this->getUserName($this->user)),
			"ordPhoneNumber" => urlencode($this->user['phone']),
			"ordAddress1" => urlencode($this->user[$type.'_address']),
			"ordAddress2" => '',
			"ordCity" => urlencode($this->user[$type.'_city']),
			"ordProvince" => urlencode($this->user[$type.'_state']),
			"ordPostalCode" => urlencode($this->user[$type.'_zip']),
			"ordCountry" => urlencode($this->user[$type.'_country'])
		);
		if($this->test) {
			$this->posts['username'] = BUSERNAME;
			$this->posts['password'] = BPASS;
		}
	}
	
	public function returnPosts($order_number,$transaction_id,$returntype = "R") {
		$this->posts = array(
			"requestType" => "BACKEND",
			"merchant_id" => $this->merchant_id,
			"trnOrderNumber" => $order_number,
			"trnAmount" => $this->amount,
			"adjId" => $transaction_id,
			"trnType" => $returntype, // available options: R = Return, VR = Void Return, V = Void, VP = Void Purchase; Voids can only be completed on the same day of the transaction and will not show up on customers bill. Returns will always show on customers bill.

		);
		if($this->test) {
			$this->posts['username'] = BUSERNAME;
			$this->posts['password'] = BPASS;
		}
	}
	
	public function email($admin=false){
		
	}
	
	public function send() {
		$ch = curl_init();
		
		if(empty($this->posts)) $this->setPosts();
		$postdata = $this->posts;
		
		$postfields = '';
		foreach($postdata as $k=>$v) $postfields .= $k.'='.$v.'&';
		$postfields = substr($postfields,0,-1);		
		
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_URL, "https://www.beanstream.com/scripts/process_transaction.asp" );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $postfields );
		$txResult = curl_exec( $ch );
		parse_str($txResult, $results);
		curl_close( $ch );
		
		
		if($results['trnApproved'] == 1) {
		
//			$cart_id = $this->addToDb($results);
//			$results['cart_id'] = $cart_id;
			
//			$this->addItems($results);
		
		}
		
		return $results;
	}
	
	public function updateGiftCard($cart_id) {

		foreach($_SESSION['giftcard'] as $gc) {
			$cid = $gc['id'];
			$dis = $gc['discount'];
			
			$giftcard = $this->conn->id('giftcards',$cid);
			$cr = $giftcard['remaining'];
			$nr = $cr - $dis;
	
			if($nr < 0) 
				$cr = 0;
			
			$ud = array('remaining'=>$nr);
			$this->conn->update('giftcards',$ud,'id="'.$cid.'"',1);	
			
			
			$gccart = array(
				'cart_id'=>$cart_id,
				'giftcard_id'=>$cid,
				'amount_used'=>$dis
			);
			$this->conn->insert('cart_session_gift_cards',$gccart);
		
		}
	}
	
	public function addToDB($res, $shipto=array()){
		$fd = array();
		
		$fd['account_id'] = $_SESSION['user_id'];
		$fd['purchase_order'] = '';
		$fd['special_instructions'] = $_SESSION['special_instructions'];
		$fd['date_ordered'] = date('Y-m-d');
		$fd['total_price'] = $res['trnAmount'];
		$fd['txn_id'] = $res['trnId'];
		$fd['auth_code'] = $res['authCode'];
		$fd['order_number'] = $res['trnOrderNumber'];
		$fd['order_status'] = 'ordered';
		$fd['shipping'] = $_SESSION['shipping_amount'];

		if(!empty($shipto)) {
			foreach($shipto as $key=>$val) {
				$fd[$key] = $this->conn->escape($val);
			}
		}
		
		$fd['token'] = $this->generateToken(20);			
		
		$fd['handling'] = $_SESSION['handling'];
		
		$this->token = $fd['token'];

		

		if(isset($_SESSION['code']))
			$fd['promo_code'] = $_SESSION['code'];
		if(isset($_SESSION['promo_amount']))
			$fd['promo_amount'] = $_SESSION['promo_amount'];
		if(isset($_SESSION['promo_type']))
			$fd['promo_type'] = $_SESSION['promo_type'];
		
		$fd['gst'] = $_SESSION['GST'];
		$fd['hst'] = $_SESSION['HST'];
		$fd['pst'] = $_SESSION['PST'];
				
		$id = $this->conn->insert('cart_session',$fd);
		$_SESSION['NEW_ID'] = $id;
		return $id;
	}
	
	private function _getPrice($d) { // final price check to ensure correct billing!
		$price = $d['price'];
		if(($d['sale_price'] > 0 && $d['price'] > $d['sale_price']) && ($d['on_sale'] == 'Y' || $d['on_sale'] == "Yes") )
			$price = $d['sale_price'];	
		
		return $price;
	}

	private function _gcNotify() {
		$email = new email();
				
		$ud = $this->conn->id('accounts',$_SESSION['user_id']);
		$email->html_body = 'Hello Recipient! '.$ud['first_name'].' '.$ud['last_name'].' has sent you a gift card for $'.number_format($data['price']).'.';
				
		$email->html_body .= 'You may redeem this card at <a href="https://www.alianicola.com">Alia Nicola</a> using the code '.$gc_code;
				
		$email->subject = 'New giftcard from Alia Nicola';
		$email->send(array($item['email']=>$item['rec']));		
	}
	
	private function _removeInventory($id,$qty) {
		$item = $this->conn->id('products',$id);
		$remain = $item['inventory'];
		$remain = $item['inventory'] - $qty;
		$fd = array(
			'inventory'=>$remain
		);
		$this->conn->update('products',$fd,'id='.$id,1);
	}
	
	public function addItems($cid,$product_db='products') {	
		foreach($_SESSION['cart'] as &$item) { // pbr to unset when added to DB
			$data = $this->conn->id($product_db,$item['item_id']);
			
			$p = $this->_getPrice($data);
			
			$ad = array(
			      'account_id'=>$_SESSION['user_id'],			
				  'cart_id'=>$cid,
				  'item_qty'=>$item['item_qty'],
				  'item_id'=>$data['id'],
				  'item_name'=>$data['name'],
				  'price'=>$p,
				  'pst'=>$item['pst'],
				  'gst'=>$item['gst'],
				  'hst'=>$item['hst']
				 );
			
			$this->conn->insert('cart_session_items',$ad);
		}
	}
	
	public function giftCardNumber($length=10) {
		$chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
		$size = strlen($chars);
		$str = '';
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $chars[ rand( 0, $size - 1 ) ];
		}	
		return $str;
	}
	
	public function generateToken($length=10) {
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabsdefghijklmnopqrstuvwxyz0123456789";
		$size = strlen($chars);
		$str = '';
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $chars[ rand( 0, $size - 1 ) ];
		}	
		return $str;
	}
	
	
	
	private function getUserName($user) {
		if(array_key_exists('first_name',$user)) $name = $user['first_name'].' '.$user['last_name'];
		else if(array_key_exists('name',$user)) $name = $user['name'];
		return $name;
	}
}
