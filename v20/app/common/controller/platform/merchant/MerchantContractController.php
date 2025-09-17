<?php
namespace app\common\controller\platform\merchant;

use app\common\controller\platform\AuthBaseController;
use app\common\model\service\MerchantContractService;

class MerchantContractController extends AuthBaseController
{
    /**
     * 获取商家签约合同列表
     * @return \think\response\Json
     */
    public function getList(){
        $param['mer_id'] = $this->request->param('mer_id',0,'trim,intval');
        try {
            $res = (new MerchantContractService())->getList($param);
            return api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 重新签署合同文案修改
     * @return \think\response\Json
     */
    public function addResignTip(){
        $param['tips'] = $this->request->param('tips','','trim,string');
        try {
            $res = (new MerchantContractService())->addResignTip($param);
            return api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}