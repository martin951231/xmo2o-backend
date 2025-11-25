<?php


namespace app\voice_robot\controller\api;

use app\real_estate\model\service\WishService;
use app\voice_robot\model\service\HotWordManageService;

class HotWordManageController extends ApiBaseController
{
    /**
     * 通过关键词获取数据
     */
    public function getVillageIdentifyInfo()
    {
        $param['keywords'] = $this->request->param('keywords', '', 'trim');
        try {
            $result= (new HotWordManageService())->getVillageIdentifyInfo($param);
            return api_output(0, $result, "success");
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 获取关键词设置
     */
    public function getVoiceWssUrl()
    {
        try {
            $result= (new HotWordManageService())->getVoiceWssUrl();
            return api_output(0, $result, "success");
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}