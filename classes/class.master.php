<?
/*
 * PHP Master Page Class V0.0.5
 * http://pixelgems.com
 *
 * Copyright 2013, Wayne Janzen
 * 
 * April 2013
 */

class master {
  var $defaults;
  var $pagealias;
	var $alias;
	var $aliased;
	
	public function __construct(){
		$this->defaults = select_single_by_id('defaults',1);
		$this->pagealias    = substr($_SERVER['SCRIPT_URL'],1);
		if(preg_match('/\.php/',$this->pagealias)) $this->aliased = false;
		else $this->aliased = true;
		
		
		if($this->aliased) $this->alias = explode('/',$this->pagealias);
	}
	
	
	public function getTaxes($state){
		$tax = db_select('*','tax_table','WHERE province = "'.$state.'"');
		$taxes = array('hst'=>0,'pst'=>0,'gst'=>0);
		for($t=0; $t<count($tax); $t++) {
			if($tax[$t]['tax_type'] == 'GST') $taxes['gst'] = $tax[$t]['rate'];
			if($tax[$t]['tax_type'] == 'PST') $taxes['pst'] = $tax[$t]['rate'];
			if($tax[$t]['tax_type'] == 'HST') $taxes['hst'] = $tax[$t]['rate'];
		}
		return $taxes;
	}
	
	
	public function breadcrumb() {
		if($this->aliased) {
	
			$str = '<div class="bc"><a href="/">Home</a>';
			$hr  = '';
			$name = '';
			$list = $this->alias;
			
			$item = select_single_by_key('pages','alias',$list[count($list)-1]);
			
			
			if(!empty($item['section']) && $item['section'] != '' && $item['section'] != "None") {
				$sec = count(db_select('*','pages','WHERE section = "'.$item['section'].'"'));
				if($sec>1) $str.=' &rsaquo; '.$item['section'];
			}
			
			if(!empty($item['parent']) || $item['parent'] != '') {
				$par = select_single_by_id('pages',$item['parent']);
				if(!empty($par['menu_label']))
					$str.=' &rsaquo; <a href="/'.$par['alias'].'">'.$par['menu_label'].'</a>';			
			}
			
			if($item < 0) {
				$item = select_single_by_key('pages','alias',$list[0]);
				if($item['master'] == 'news' || $item['master'] == 'blog') {
					$str .= " &rsaquo; <a href='/".$item['alias']."'>".$item['menu_label']."</a>";
					if($item['master'] == 'news') $title = 'headline';
					else $title = 'blog_title';
					$article = select_single_by_key($item['master'],'alias',$list[count($list)-1]);
					$str .= " &rsaquo; <a href='javascript:void(0);' class='select'>".$article[$title]."</a>";
				}
			} else {
				$str .= " &rsaquo; <a href='javascript:void(0);' class='select'>".$item['menu_label']."</a>";
			}
			
			$str .= '</div>';
			return $str;
		}
	}
	
	
	
	public function pagination($page,$query,$n,$pl,$sp,$max=10) {
		
		if(substr($query,0,1) != '?') $query = '?'.$query;
		$query = preg_replace('/(\&)?sp=(\d*)/','',$query);
		$str = '';
				
		if($n > $pl && $pl > 0) {
			$str = '<div class="pagination">';
			
			$nb = ceil($n / $pl); 
			$mb = ceil($max/2);
			$cb = (($sp - $pl) / $pl) + 1;

			$cb > $mb ? $fb = $cb - $mb : $fb = 0;
			
			$lb = $max + $fb;
			if ($lb > $nb) {
				$lb = $nb;
				$fb = $nb - $max;
				if ($fb < 0) $fb = 0;
			}
			
			if($cb < 0) $cb = 0;
			
			
			$str .= '<div class="page_numbers">';
			
			if($sp > 0) {
				$pos = $sp-$pl;
				$str .= '<a class="previous pg-prev" href="'.$page.$query.'&sp='. $pos .'">&lsaquo; prev</a>';
			} else $str .= '<span class="previous pg-prev off">&nbsp;</span>';
			
			for ($x = $fb; $x < $lb; $x++) { 
					if (($x * $pl) == $sp) {
						$str .= '<a class="pg-num select">'.($x + 1).'</a>';
					} else { 
						$pos = ($x * $pl);
						$str .= '<a href="'.$page.$query.'&sp='.$pos.'" class="pg-num">'.($x + 1).'</a>';
					}
			}
			if (($sp + $pl) < $n) {
				$pos = $sp+$pl;
				$str .= '<a class="next pg-next" href="'.$page.$query.'&sp='. $pos.'">next &rsaquo;</a>';
			} else $str .= '<span class="next pg-next off">&nbsp;</span>';
			
			
			$str .= '</div>';
			
			
			$str .= '</div>';
		} 
		
		return $str;
	}
	
	public function alias($str){
		$str = str_replace("'","",str_replace('"','',$str));
    	return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-', html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($str, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
	}
	
	public function mysql_alias($field){
		$str = ' REPLACE(REPLACE(REPLACE(REPLACE(LOWER('.$field.')," ","-"),"&",""),"\'",""),\'"\',\'\')';
		//$str = ' PREG_REPLACE("/\"/","",'.$field.')';
		return $str;
	}
		public function checkPromoCode($code, $codetype){
		$date = date('Y-m-d');
		$arr = array();
			if ($codetype == 'promocode') {
				$checkcode = db_select('*', 'promo_codes', 'WHERE promo_code = "'.$code.'"');
				$arr['code_type'] = 'promo';
				$_SESSION['code'] = '';
				
				if(count($checkcode) > 0) {
					$st = $checkcode[0]['start_time'];
					$et = $checkcode[0]['end_time'];
					if ($date >= $checkcode[0]['start_date']) { // && ($st == '' || (date('g:i a',strtotime($st)) < date('g:i a')))) {
						if ($date <= $checkcode[0]['end_date']) { // && ($et == '' || (date('g:i a',strtotime($et)) >= date('g:i a')))) {
														
							$arr['error'] = 0;
							$arr['discount_amount'] = $checkcode[0]['amount'];
							$arr['discount_type'] = $checkcode[0]['discount_type'];
							$arr['discount_what'] = $checkcode[0]['discount_what'];
							
							$arr['start_time'] = $st;
							$arr['end_time'] = $et;
							
							if(array_key_exists('category_id',$checkcode[0]))
								$disc_cat = $checkcode[0]['category_id'];
							else $disc_cat = '';
							$arr['discount_cat'] = $disc_cat;
							
							$arr['min_purchase'] = $checkcode[0]['min_purchase'];
							if($checkcode[0]['reg_only'] == 'Y') $regonly = true;
							else $regonly = false;
							
							$arr['regprice_only'] = $regonly;
							
							if(array_key_exists('brand',$checkcode[0]))
								$disc_brand = $checkcode[0]['brand'];
							else $disc_brand = 0;
							$arr['discount_brand'] = $disc_brand;
							
							$arr['code'] = $code;
							$_SESSION['code'] = $code;
							$arr['good'] = 'yes';
							

						} else {
							$arr['error'] = 3; // expired code
							$arr['code'] = '';
						}
					} else {
						$arr['error'] = 2; // hasn't started yet.
						$arr['code'] = '';
					}
				} else {
					$arr['error'] = 1; // no code
					$arr['code'] = '';
				}
			} else {
				$checkcode = db_select('*','giftcards','WHERE card_number = "'.$code.'"');
				if(count($checkcode) != 0) {
					$arr['code_type'] = 'giftcard';
					if(!isset($_SESSION['giftcard_id']) || !in_array($checkcode[0]['id'],$_SESSION['giftcard_id'])) {
						$arr['giftcard_id'] = $checkcode[0]['id']; 
						$arr['discount_cat'] = '';
						$arr['discount_brand'] = '';
						$arr['min_purchase'] = 0;
						$arr['error'] = 0;
						$_SESSION['giftcard_id'][] = $arr['giftcard_id'];
						$arr['code'] = '';
					}
				} else {
					$arr['error'] = 1; // no code
					$arr['code'] = '';
				}
			}
		return $arr;
	}
	
}
?>
