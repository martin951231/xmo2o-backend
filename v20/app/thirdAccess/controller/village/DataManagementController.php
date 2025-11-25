<?php


namespace app\thirdAccess\controller\village;

use app\common\controller\CommonBaseController;

use app\thirdAccess\model\service\ThirdAccessCheckService;

class DataManagementController extends CommonBaseController
{
    // 生成相关配置项-系统级
    public function productHouseThirdConfig() {
        $village_id = $this->request->param('village_id','','intval');
        $property_id = $this->request->param('property_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        if (($village_id || $property_id) && $thirdErrMsg && $thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = $this->request->param('thirdId','','trim');
        if (!$property_id && !$village_id) {
            $businessType = 'system';
            $businessId = $thirdId = -999;
        } elseif ($property_id && !$village_id) {
            $businessType = 'property';
            $businessId = $property_id;
            if (!$thirdId) {
                $thirdId = -$property_id;
            }
        } elseif ($village_id) {
            $businessType = 'village';
            $businessId = $village_id;
            if (!$thirdId) {
                $thirdId = -$village_id;
            }
        } else {
            fdump('鉴权失败','鉴权失败');
            return api_output_error(1001, '鉴权失败');
        }
        $thirdSite = $_SERVER['HTTP_HOST'];
        $thirdName = $this->request->param('thirdName','','trim');
        if (!$thirdType) {
            $thirdType = 'aiHorse';
            if (!$thirdName) {
                $thirdName = '小红马';
            }
        }
        $paramData = [
            'village_id' => $village_id,
            'property_id' => $property_id,
            'operation' => 'ProductSecretKey',
            'thirdType' => $thirdType,
            'businessType' => $businessType,
            'businessId' => $businessId,
            'thirdSite' => $thirdSite,
            'thirdName' => $thirdName,
            'thirdId' => $thirdId,
        ];
        $thirdAccessCheckService = new ThirdAccessCheckService();
        try {
            $data = $thirdAccessCheckService->setDataConfig($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$data);
    }
}