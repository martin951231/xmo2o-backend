<?php


namespace app\real_estate\controller\api;

use app\real_estate\model\service\WishService;

class RealRecommendController extends ApiBaseController
{
    /**
     * 用户端-房产推荐列表
     * @return \json
     */
    public function index(){
        $params = [];
        $params['page'] = request()->param('page', 1, 'trim,intval');
        $params['page_size'] = request()->param('page_size', 10, 'trim,intval');
        $params['search_process'] = request()->param('search_process', 0, 'trim,intval');
        $params['user_phone'] = $this->userInfo['phone'];
        try {
            $data = (new WishService())->getRealStateList($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}