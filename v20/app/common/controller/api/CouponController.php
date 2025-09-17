<?php


namespace app\common\controller\api;


use app\common\model\service\coupon\CouponService;

class CouponController extends ApiBaseController
{
    /**
     * 获取优惠券列表
     * @return \think\response\Json
     */
    public function getCouponList(){
        $params['type'] = $this->request->param("type", '','trim,string');
        $params['goods_id'] = $this->request->param("goods_id", 0,'trim,intval');
        $params['mer_id'] = $this->request->param("mer_id", 0,'trim,intval');
        $params['coupon_type'] = $this->request->param("coupon_type", 'merchant','trim,string');
        $params['uid'] = $this->uid;
        $params['all_coupon'] = $this->request->param("all_coupon", 0,'trim,intval');
        try{
            $res = (new CouponService())->getCouponList($params);
            return api_output(0,$res);
        }
        catch(\Exception $e){
            return api_output_error(1005, $e->getMessage());
        }
    }
    
    /**
     * 领取优惠券
     * @return \think\response\Json
     */
    public function receive(){
        $this->checkLogin();
        $type = $this->request->param("type",'','trim,string');
        $coupon_id = $this->request->param("coupon_id");
        $uid = $this->uid;
        try{
            $res = (new CouponService())->receive($type,$uid,$coupon_id);
            return api_output(0,$res);
        }
        catch(\Exception $e){
            return api_output_error(1005, $e->getMessage());
        }
    }
}