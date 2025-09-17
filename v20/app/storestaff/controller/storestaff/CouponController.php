<?php
/**
 * 店员后台
 * author by hengtingmei
 */
namespace app\storestaff\controller\storestaff;

use app\storestaff\model\service\CouponService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;

class CouponController extends AuthBaseController {
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * desc: 获取优惠券列表
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/08/31 09:52
     */
    public function couponList(){
        $param['uid']  = $this->request->param("uid", "", "trim");
        $param['business']  = $this->request->param("business", "", "trim");
        $param['type']  = $this->request->param("type", "", "trim");
        $param['money']  = $this->request->param("money", "", "trim");
        $param['has_merchant_discount']  = $this->request->param("has_merchant_discount", "", "trim");
        $result = (new CouponService($this->staffUser))->getCouponList($param);
        try {
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result );
    }
}