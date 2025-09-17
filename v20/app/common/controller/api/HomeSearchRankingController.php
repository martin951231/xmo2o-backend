<?php


namespace app\common\controller\api;


use app\common\model\service\HomeSearchRankingService;

class HomeSearchRankingController extends ApiBaseController
{
    /**
     * 获取排行榜列表
     * @return \think\response\Json
     */
    public function getApiList()
    {
        $params = [
            'show_position' => $this->request->param('show_position', '', 'trim,string'),
            'is_from_api' => $this->request->param('is_from_api', 1, 'trim,intval')
        ];
        try {
            $res = (new HomeSearchRankingService())->getApiList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage() . $e->getFile() . $e->getLine());
        }
    }
}