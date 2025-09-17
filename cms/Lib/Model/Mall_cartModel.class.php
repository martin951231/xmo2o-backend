<?php
class Mall_cartModel extends Model{
	//更新商城购物车数量缓存
    public function saveCookieData($uid = 0){
    	$condition=array();
    	if($uid){
            $where['uid'] = $uid;
            $where['cart_cookie'] = cookie('cart_cookie');
            $where['_logic'] = 'or';
            $condition['_complex'] = $where;
        }else{
            $condition['cart_cookie'] = cookie('cart_cookie');
        }
        $cartList = $this->where($condition)->order('`id` ASC')->select();
        $cartNum=0;
        foreach ($cartList as $k => $v) {
        	$cartNum+=intval($v['count']);
        }
        cookie('mallCartNum',$cartNum);
        return $cartList;
    }

}
