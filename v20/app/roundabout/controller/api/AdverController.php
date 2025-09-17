<?php
namespace app\roundabout\controller\api;

use app\common\controller\api\ApiBaseController;
use app\roundabout\model\service\AdverService;

class AdverController extends ApiBaseController
{
    /**
     * 首页广告
     * @return \think\response\Json
     */
    public function getAdver(){
        $params['name_key'] = $this->request->param('name_key','home','trim,string');
        $params['now_city'] = $this->request->param('now_city',0,'intval');
        try {
            $res = (new AdverService())->getAdver($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}