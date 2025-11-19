<?php


namespace app\merchant\controller\merchant;


use app\merchant\model\service\yibao\MerchantYibaoService;

class MerchantYibaoController extends AuthBaseController
{
    /**
     * 获取银行编号
     * @return \think\response\Json
     */
    public function getBankCode(){
        try {
            $data = (new MerchantYibaoService())->getBankCode();
            return api_output(1000,$data);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 获取区域列表
     * @return \think\response\Json
     */
    public function getAreaList(){
        try {
            $data = (new MerchantYibaoService())->getAreaList();
            return api_output(1000,$data);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 入网申请
     * @return \think\response\Json
     */
    public function applyYibao(){
        $params = $this->request->param();
        $params['mer_id'] = $this->merId;
        try {
            $data = (new MerchantYibaoService())->applyYibao($params);
            return api_output(1000,$data);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 入网详情
     * @return \think\response\Json
     */
    public function getYibaoInfo(){
        $param['mer_id'] = $this->merId;
        try {
            $data = (new MerchantYibaoService())->getYibaoInfo($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function uploadFile(){
        $param['mer_id'] = $this->merId;
        $param['file'] = $this->request->file();
        try {
            $data = (new MerchantYibaoService())->uploadFile($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 绑定银行卡
     * @return \think\response\Json
     */
    public function bindBank(){
        $params = $this->request->param();
        $params['mer_id'] = $this->merId;
        try {
            $data = (new MerchantYibaoService())->bindBank($params);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}