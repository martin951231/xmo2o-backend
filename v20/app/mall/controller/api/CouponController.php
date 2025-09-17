<?php
/**
 * 优惠券接口
 * Created by subline.
 * Author: lumin
 * Date Time: 2020/9/09 10:46
 */

namespace app\mall\controller\api;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\coupon\MerchantCouponService;

class CouponController extends ApiBaseController
{
	public function receive(){
		$type = $this->request->param("type");
		$coupon_id = $this->request->param("coupon_id", "", "intval");
		if(empty($type) || empty($coupon_id)){
			return api_output_error(1001, '必填参数未传递!');
		}
		$now_user_id = request()->log_uid;
		if(empty($now_user_id)){
			return api_output_error(1002, '未监测到登录!');
		}
		try{
			if($type == 'system'){
				$receive = (new SystemCouponService)->receiveCoupon($now_user_id, $coupon_id);
				if($receive){
					return api_output(0, ['get'=>1]);
				}
			}
			elseif($type == 'merchant'){
				$receive = (new MerchantCouponService)->receiveCoupon($now_user_id, $coupon_id);
				if($receive){
					return api_output(0, ['get'=>1]);
				}
			}
			throw new \Exception("领取失败!");	
		}
		catch(\Exception $e){
			return api_output_error(1005, $e->getMessage());
		}
	}
}