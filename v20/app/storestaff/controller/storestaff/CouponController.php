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
        
        try {
            $result = (new CouponService($this->staffUser))->getCouponList($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result );
    }

    /**
     * 获取扫描的核销码/条形码信息
     */
    public function getCodeInfo(){
        $params = [
            'code' => $this->request->param('code','','trim,string'),
            'staff_user' => $this->staffUser,
        ];
        try {
            $res = (new CouponService($this->staffUser))->getCodeInfo($params);
            return api_output(0,$res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 核销优惠券
     */
    public function verifyCoupon(){
        $params = [
            'code' => $this->request->param('code','','trim,string'),
            'type' => $this->request->param('type','','trim,string'),
            'staff_user' => $this->staffUser,
        ];
        try {
            $res = (new CouponService($this->staffUser))->verifyCoupon($params);
            return api_output(0,$res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 核销记录
     */
    public function getCouponVerifyRecord(){
        $params = [
            'page' => $this->request->param('page',1,'trim,intval'),
            'pageSize' => $this->request->param('pageSize',10,'trim,intval'),
            'keywords' => $this->request->param('keywords','','trim,string'),
            'type' => $this->request->param('type','','trim,string'),
            'is_export' => $this->request->param('is_export',0,'trim,intval'),
            'start_time' => $this->request->param('start_time','','trim,string'),
            'end_time' => $this->request->param('end_time','','trim,string'),
            'staff_user' => $this->staffUser,
        ];
        try {
            $res = (new CouponService($this->staffUser))->getCouponVerifyRecord($params);
            return api_output(0,$res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}