<?php
/**
 * 店员app扫一扫
 * author by hengtingmei
 * Date Time: 2020/12/09 18:14
 */
namespace app\storestaff\controller\storestaff;

use app\merchant\model\service\print_order\OrderprintListService;
use app\merchant\model\service\print_order\OrderprintService;
use app\storestaff\model\service\ScanService;
use think\Exception;

class ScanController extends AuthBaseController {
    /**
     * desc: 店员app首页扫一扫
     * return :array
     */
    public function index(){
        $param = $this->request->param();
        try {
            $returnArr = (new ScanService())->indexScan($param, $this->staffUser);
            return api_output(0, $returnArr);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 核销商家优惠券
     * @author: zt
     * @date: 2022/12/13
     */
    public function verifyMerCoupon()
    {
        $hadpullId = $this->request->param('hadpull_id', 0, 'intval');
        $couponUid = $this->request->param('uid', 0, 'intval');
        try {
            $returnArr = (new ScanService())->verifyMerCoupon($hadpullId, $couponUid, $this->staffUser);
            return api_output(0, $returnArr, '核销成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

}