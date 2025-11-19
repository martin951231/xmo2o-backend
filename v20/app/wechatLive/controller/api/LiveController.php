<?php


namespace app\wechatLive\controller\api;

use app\common\controller\api\ApiBaseController;
use app\wechatLive\model\service\LiveService;

class LiveController extends ApiBaseController
{
    const STATUS_OK = 1000;//状态码 正常

    /**
     * 直播列表
     * @return \think\response\Json
     */
    public function liveList()
    {
        $limit = 4;//默认显示4条
        
        $data = (new LiveService())->liveList($limit);

        return api_output(self::STATUS_OK, $data);
    }
}