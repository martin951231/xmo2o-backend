<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\model\service\SocialActivityIndexService;

class SocialActivityIndexController extends AuthBaseController
{
    /**
     * 获取活动统计数据
     * @return \think\response\Json
     */
    public function getInfo()
    {
        try {
            $res = (new SocialActivityIndexService())->getInfo();
            return api_output(0, $res);
        }catch(\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}
