<?
/*
 * PHP User Class V0.0.5
 * http://pixelgems.com
 *
 * Copyright 2013, Wayne Janzen
 * 
 * April 2013
 */
 
class user {
  
  var $user;										//variable to store user data in
													// also stores in self variable
	
	function __construct($id=0) {
		if($id > 0) $this->setUser($id);
	}
	
	public function setUser($id) {
		if(!empty($id)) {
			$this->user = select_single_by_id('accounts',$id);
			foreach($this->user as $k=>$v) $this->{$k} = $v;
		}
	}
	
	public function login($data) {
		$pass = $data->password;
		if(isset($data->username))
			$username = $data->username;
		else 
			$username = $data->email;
			
		$r = array();
		$_SESSION['login'] = false;
		
		$check = db_select('*', 'accounts', 'WHERE email = "'.$username.'" OR username = "'.$username.'"');
		if (count($check) == 0) {
			$r['login'] = false;
			$r['error'] = 'Email address cannot be found';
		} else {
			if ($pass != $check[0]['password']) {
				$r['login'] = false;
				$r['error'] = 'Invalid password';
			} else {
				$_SESSION['user_id'] = $check[0]['id'];
				$_SESSION['login'] = true;
				$r['login'] = true;
				$r['user_id'] = $check[0]['id'];
			}
		}
		return $r;
	}
	
	public function logout($sess) {
		$sess->unsetKey(user_id);
		$sess->setVal('login',false);
		return $sess;
	}
	
	public function addUser($data) {
		$check = select_single_by_key('users','email',$data['email']);
		if($check > 0) return(false); // kill function if exists
		$id = db_insert('accounts',$data,1);
		$this->setUser($id);
	}		

}
?>
