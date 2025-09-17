<?php
/**
 * 购物车接口
 * Created by subline.
 * Author: lumin
 * Date Time: 2020/9/09 10:46
 */

namespace app\mall\controller\api;
use app\common\model\db\UserAdress;
use app\mall\model\db\MallCartNew;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoodsArea;
use app\mall\model\db\MerchantStore;
use app\mall\model\db\MerchantStoreMall;
use app\mall\model\service\MallDeliveryService;
use app\mall\model\service\MallGoodsService as MallGoodsService;
use app\mall\model\service\MallGoodsSkuService as MallGoodsSkuService;
use app\mall\model\service\CartService;
use app\mall\model\service\MallOrderService;
use app\merchant\model\service\MerchantStoreService;
use app\store_marketing\model\db\StoreMarketingPerson;
use app\store_marketing\model\db\StoreMarketingPersonSetprice;
use map\longLat;
use think\facade\Db;

class CartController extends ApiBaseController
{
	//加入购物车
	public function addCart(){
		$CartService = new CartService;
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$sku_id = $this->request->param("sku_id", "", "intval");
        if(empty($sku_id)){
        	return api_output_error(1001, 'sku_id必传!');
        }
        $share_id = $this->request->param("share_id", 0, "intval");//分享id，分享商品直接购买时必传
        $num = $this->request->param("num", 1, "intval");
        $note = $this->request->param("note");
        $form = $this->request->param("form");

        $sku = (new MallGoodsSkuService)->getSkuById($sku_id);//sku信息
        if(empty($sku) || empty($sku['goods_id'])){
        	return api_output_error(1001, '当前商品未找到或已被删除');	
        }
        $goods = (new MallGoodsService)->getOne($sku['goods_id']);//商品信息
        Db::startTrans();
        try{
	        if($CartService->addCart($this->request->log_uid, $sku, $goods, $num, $note, $form, $share_id)){
	        	$compute = $CartService->computeCart($this->request->log_uid);
	        	Db::commit();
	        	return api_output(0, $compute);
	        }
	        else{
	        	return api_output_error(1003, '加入购物车失败!');
	        }
	    }
	    catch(\Exception $e){
			Db::rollback();
	    	return api_output_error(1005, $e->getMessage());
	    }
	}

	//修改购物车
	public function updateCart(){
		$CartService = new CartService;
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$cart_id = $this->request->param("cart_id", "", "intval");
        if(empty($cart_id)){
        	return api_output_error(1001, 'cart_id必传!');
        }
        $sku_id = $this->request->param("sku_id", "", "intval");
        $num = $this->request->param("num", '', "intval");//修改为几
        $note = $this->request->param("note");
        $form = $this->request->param("form");
        if($num <= 0){
        	return api_output_error(1001, 'num参数必须为一个正整数!');
        }

        try{
	        if($CartService->updateCart($this->request->log_uid, $cart_id, $num, $sku_id, $note, $form) !== false){
	        	return api_output(0, $CartService->computeCart($this->request->log_uid));
	        }
	        else{
	        	return api_output_error(1003, '修改购物车失败!');
	        }
	    }
	    catch(\Exception $e){
	    	return api_output_error(1005, $e->getMessage());
	    }
	}

	//删除购物车
	public function delCart(){
		$CartService = new CartService;
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$cart_ids = $this->request->param("cart_ids");
        if(empty($cart_ids)){
        	return api_output_error(1001, 'cart_ids必传!');
        }

        try{
        	$cart_ids = explode(',', $cart_ids);
	        if($CartService->delCart($this->request->log_uid, $cart_ids)){
	        	return api_output(0, $CartService->computeCart($this->request->log_uid));
	        }
	        else{
	        	return api_output_error(1003, '删除购物车失败!');
	        }
	    }
	    catch(\Exception $e){
	    	return api_output_error(1005, $e->getMessage());
	    }
	}

	//选中|非选中购物车
	public function selectCart(){
		$CartService = new CartService;
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$cart_id = $this->request->param("cart_id", 0, "intval");//为0时=全选/全不选
        $select = $this->request->param("select", 1, "intval"); // 1=选中 0=不选中
        $store_id = $this->request->param("store_id", 0, "intval"); // 全选/全不选某个店铺的购物车数据
        $address_id = $this->request->param("address_id", 0, "intval");
        
        try{
	        if($cart_id > 0){
	            $mall_cart_address_id = $address_id?:($this->userInfo['mall_cart_address_id']??0);
	            if(cfg('open_mall_order_area')&&$select&&$mall_cart_address_id){
	                //获取商品id
                    $goods_id = (new MallCartNew())->where(['id'=>$cart_id])->value('goods_id');
	                //获取地址
                    $address_info = (new UserAdress())->where(['adress_id'=>$mall_cart_address_id,'uid'=>$this->request->log_uid])->findOrEmpty()->toArray();
                    if(cfg('open_mall_order_area')==1){
                        $goods_area_id[] = 0;
                        if($address_info['area']>0){
                            $goods_area_id[] = $address_info['area'];
                        }
                        if($address_info['city']>0){
                            $goods_area_id[] = $address_info['city'];
                        }
                        if($address_info['province']>0){
                            $goods_area_id[] = $address_info['province'];
                        }
                        $goods_area = (new MallGoodsArea())->where([['goods_id','=',$goods_id],['area_id','in',$goods_area_id]])->findOrEmpty()->toArray();
                        if(!$goods_area){
                            return api_output_error(1003, '超出可服务范围，无法勾选!');
                        }
                    }else{
                        $store_info = (new MallGoods())->alias('g')
                            ->join('merchant_store s','g.store_id = s.store_id')
                            ->where(['g.goods_id'=>$goods_id])
                            ->column('s.store_id,s.long,s.lat');
                        $address_info['lng'] = $address_info['longitude'];
                        $address_info['lat'] = $address_info['latitude'];
                        $is_out_range = (new MallDeliveryService())->checkRange($address_info,$store_info)['is_out_range'];
                        if($is_out_range){
                            return api_output_error(1003, '超出可服务范围，无法勾选!');
                        }
                    }
                }
                $res = $CartService->selectCart($this->request->log_uid, $cart_id, $select);
                if($res){
                    $ret_discount_money = 0;
                    return api_output(0, $CartService->computeCart($this->request->log_uid,false,$ret_discount_money,0,$mall_cart_address_id));
                }else{
                    return api_output_error(1003, '选中购物车失败（购物车无有效数据）!');
                }
	        }
	        elseif($cart_id == 0){
	        	$cart = $CartService->computeCart($this->request->log_uid);
	        	$cart_ids = [];
	        	foreach ($cart['normal_list'] as $key => $value) {
	        		if($store_id > 0 && $value['store_id'] != $store_id) continue;
	        		foreach ($value['goods_list'] as $goods) {
	        		    if($goods['is_out_range']){
	        		        //不在服务范围内的商品过滤掉
                            continue;
                        }
	        			$cart_ids[] = $goods['cart_id'];
	        		}
	        	}
	        	if(!empty($cart_ids) && $CartService->selectCart($this->request->log_uid, $cart_ids, $select)){
	        		return api_output(0, $CartService->computeCart($this->request->log_uid));
	        	}
	        	else{
	        		return api_output_error(1003, '选中购物车失败（购物车无有效数据）!');
	        	}
	        }
	        else{
	        	return api_output_error(1003, '选中购物车失败!');
	        }
	    }
	    catch(\Exception $e){
	    	return api_output_error(1005, $e->getMessage());
	    }
	}

	//我的购物车
	public function lists(){
        $address_id = $this->request->param('address_id',0,'trim,intval');
		$CartService = new CartService;
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}	
		$ret_discount_money = 0;
		$output = $CartService->computeCart($this->request->log_uid, false, $ret_discount_money, 1,$address_id);
		return api_output(0, $output);
	}

	//清空失效宝贝
	public function clearCart(){
		$CartService = new CartService;
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}	
		$output = $CartService->computeCart($this->request->log_uid);
		if(empty($output['no_effective_list'])){
			return api_output(0, $output);
		}
		$cart_ids = [];
		foreach ($output['no_effective_list'] as $key => $value) {
			$cart_ids = array_merge($cart_ids, array_column($value['goods_list'], 'cart_id'));
		}
		if($CartService->delCart($this->request->log_uid, $cart_ids)){
        	return api_output(0, $CartService->computeCart($this->request->log_uid));
        }
        else{
        	return api_output_error(1003, '清除无效购物车数据失败!');
        }
	}

	//购物车结算（包含直接购买某个单品功能---商品详情页）
	public function confirm(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$address_id = $this->request->param("address_id", "0", "intval");
		$coupon_id = $this->request->param("coupon_id", "0", "intval");
		$store = $this->request->param("store", "");
		$is_pintuan = $this->request->param("is_pintuan", 0,'trim,intval'); //1-从拼团页进来，不需要验证范围直接进入下单页
		//直接购买功能
		$type = $this->request->param("type", "0", "intval");//0=购物车结算  1=直接购买单品
		$sku_id = $this->request->param("sku_id", "", "intval");
        if($type == '1' && empty($sku_id)){
        	return api_output_error(1001, '请选择商品规格');
        }
        $share_id = $this->request->param("share_id", 0, "intval");//分享id，分享商品直接购买时必传
        $num = $this->request->param("num", 1, "intval");
        $activity_type = $this->request->param("activity_type", 'normal');//商品级活动
        $activity_field = $this->request->param("activity_field", '');//活动附属字段
        $note = $this->request->param("note");
        $form = $this->request->param("form");

		$CartService = new CartService;
		$discount_money = 0;
		$from = 'cart';
        if($type == '1'){

	        $cart = $CartService->createCartData($sku_id, $num, $activity_type, $activity_field, $note, $form, $this->request->log_uid, $share_id, $address_id);
            if(isset($cart['error'])){
                return api_output_error(1001, $cart['msg']);
            }

	        $normal_list = $cart;
	        $from = 'detail';//来源（详情页）
	    }
	    else{
	    	$cart = $CartService->computeCart($this->request->log_uid, true, $discount_money,0,$address_id);
	    	$normal_list = $cart;
	    }
        if(cfg('open_mall_order_area')&&$is_pintuan!=1){
            foreach ($normal_list as $item){
                //获取店铺信息
                $mall_store = (new MerchantStoreMall())->where(['store_id'=>$item['store_id']])->findOrEmpty();
                $is_only_zt = 0;
                if($mall_store&&$mall_store['is_delivery']==0&&$mall_store['is_houseman']==0&&$mall_store['is_zt']==1){
                    $is_only_zt = 1;
                }
                foreach ($item['goods_list'] as $value){
                    if($value['is_out_range']&&!$mall_store['is_zt']){
                        return api_output_error(1003, '商品'.$value['goods_name'].'超出可服务范围，无法下单!');
                    }
                }
            }
        }
		
		if(empty($cart)){
			return api_output_error(1003, '未检测到选中商品');
		}
		//查询店铺状态
        $storeIdInfo = [];
        foreach ($cart as $vC){
            $storeIdInfo[] = $vC['store_id'];
        }
        $merchantStoreInfo = (new MerchantStore())->where([['store_id','IN',$storeIdInfo]])->field('status,have_mall')->select()->toArray();
        foreach ($merchantStoreInfo as $vM){
            if($vM['status'] == 0){
                return api_output_error(1001, '店铺已关闭!');
            }
            if($vM['have_mall'] == 0){
                return api_output_error(1001, '店铺（商城）已关闭!');
            }
        }
		try{
			$confirm = $CartService->confirmCartData($this->request->log_uid, $normal_list, $address_id, $coupon_id, $store, $discount_money, $from);
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	
		foreach($confirm['order'] as $key => $val){
		    foreach ($val['goods_list'] as $goods){
                $goods = (new MallGoodsService)->getOne($goods['goods_id']);//商品信息
                if(isset($goods['merchant_audit_status']) && empty($goods['merchant_audit_status'])){
                    throw_exception("商品【{$goods['name']}】未开放销售，暂无法下单");
                }
            }
			//$location2 = (new longLat())->baiduToGcj02($val['lat'], $val['lng']);//转换百度坐标到腾讯坐标
			//$confirm['order'][$key]['lng'] = $location2['lng'] ?? $val['lng'];
			//$confirm['order'][$key]['lat'] =$location2['lat'] ?? $val['lat'];
		}

		return api_output(0, $confirm);
	}

	//提交订单
	public function saveOrder(){
		$address_id = $this->request->param("address_id", "0", "intval");
		$coupon_id = $this->request->param("coupon_id", "0", "intval");
		$current_liveshow_id = $this->request->param("current_liveshow_id", "0", "intval");
		$current_livevideo_id = $this->request->param("current_livevideo_id", "0", "intval");
		$store = $this->request->param("store", "");

		//直接购买功能
		$type = $this->request->param("type", "0", "intval");//0=购物车结算  1=直接购买单品
		$sku_id = $this->request->param("sku_id", "", "intval");
        if($type == '1' && empty($sku_id)){
        	return api_output_error(1001, 'sku_id必传!');
        }
        $share_id = $this->request->param("share_id", 0, "intval");//分享id，分享商品直接购买时必传
        $num = $this->request->param("num", 1, "intval");
        $activity_type = $this->request->param("activity_type", 'normal');//商品级活动
        $activity_field = $this->request->param("activity_field", '');//活动附属字段
        $note = $this->request->param("note");
        $form = $this->request->param("form");

		$CartService = new CartService;
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$from = 'cart';
		$discount_money = 0;
		if($type == '1'){

	        $cart = $CartService->createCartData($sku_id, $num, $activity_type, $activity_field, $note, $form, $this->request->log_uid, $share_id, $address_id);
            if(isset($cart['error'])){
                return api_output_error(1001, $cart['msg']);
            }

	        $from = 'detail';//来源（详情页）
	    }
	    else{
			$cart = $CartService->computeCart($this->request->log_uid, true);
		}
		if(empty($cart)){
			return api_output_error(1003, '未检测到选中商品');
		}
        $wxNickname = $this->userInfo['nickname'] ?: $this->userInfo['truename'];
        $wxPhone = $this->userInfo['phone'];

		try{
			$confirm = $CartService->confirmCartData($this->request->log_uid, $cart, $address_id, $coupon_id, $store, $discount_money, $from);
			if($confirm){
                $confirm['address']['phone'] = $confirm['address']['phone'] ?: $wxPhone;
                $confirm['address']['name'] = $confirm['address']['name'] ?: $wxNickname;
            }
			foreach ($confirm['order'] as $vOrder){
				$thirdDeliveryConfig = (new \app\common\model\service\MerchantStoreService())->getThirdConfigByStoreId($vOrder['store_id']);
                if($thirdDeliveryConfig && $vOrder['express']['style']==1 && count($vOrder['goods_list']) > 1){
                    throw_exception(L_('该店铺仅支持单商品下单，如有疑问请联系客服'));
                }
                foreach ($vOrder['goods_list'] as $goods){
                    if(cfg('open_mall_order_area')&&$goods['is_out_range']==1&&$vOrder['express']['style']==1){
                        throw_exception("商品【{$goods['goods_name']}】超出可服务范围，无法下单");
                    }
                    $goods = (new MallGoodsService)->getOne($goods['goods_id']);//商品信息
                    if(isset($goods['merchant_audit_status']) && empty($goods['merchant_audit_status'])){
                        throw_exception("商品【{$goods['name']}】未开放销售，暂无法下单");
                    }
                }
            }
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}

		//写入订单表
		Db::startTrans();
		try{
			$order_id = (new MallOrderService)->saveOrder($this->request->log_uid, $confirm, $store, $current_liveshow_id, $current_livevideo_id,$activity_type);
			Db::commit();
		}
		catch(\Exception $e){
			Db::rollback();
			return api_output_error(1005, $e->getMessage());
		}
		
		if($order_id){
			//清空购物车里面的数据
			if($type == '0'){
				foreach($cart as $c){
					$CartService->delCart($this->request->log_uid, array_column($c['goods_list'], 'cart_id'));
				}
			}
			return api_output(0, ['order_type' => 'mall', 'order_id' => $order_id, 'use_merchant_balance'=>0, 'use_qiye_balance'=>0, 'use_system_balance'=>1, 'use_user_score'=>1]);
		}
		else{
			return api_output_error(1003, '提交失败！');
		}
	}
}



