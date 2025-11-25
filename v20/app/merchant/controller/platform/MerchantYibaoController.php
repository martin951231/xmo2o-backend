<?php


namespace app\merchant\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\merchant\model\service\yibao\MerchantYibaoService;

class MerchantYibaoController extends AuthBaseController
{
    /**
     * 易宝分账-商户入网信息
     * @return \think\response\Json
     */
    public function getMerchantYibaoInfo(){
        $param['mer_id'] = $this->request->param('mer_id',0,'trim,intval');
        try {
            $data = (new MerchantYibaoService())->getYibaoInfo($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}