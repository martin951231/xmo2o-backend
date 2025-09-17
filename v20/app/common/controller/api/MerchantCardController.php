<?php


namespace app\common\controller\api;


use app\common\model\service\merchant_card\MemberShipCardService;

class MerchantCardController extends ApiBaseController
{

    /**
     * 获取用户商家会员卡详情
     */
    public function getMerchantCardDetail(){
        $this->checkLogin();
        $param['uid'] = $this->request->log_uid;
        $param['mer_id'] = $this->request->param('mer_id',0,'trim,intval');
        $param['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $rs = (new MemberShipCardService())->getMerchantCardDetail($param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}