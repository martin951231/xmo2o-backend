<?php
/**
 * 订单详情页-吃喝玩乐推荐
 */

namespace app\shop\controller\api;


use app\shop\model\service\goods\RecommendService;

class RecommendController extends ApiBaseController {
    /**
     * 获取推荐商品列表
     */
    public function getRecommendDetailByStore()
    {
        $params = [];
        $params['store_id'] = $this->request->post('store_id', 0,'intval');
        $params['user_long'] = $this->request->post('user_long', 0,'trim');
        $params['user_lat'] = $this->request->post('user_lat', 0,'trim');
        $params['now_city'] = $this->request->post('now_city', 0,'trim');
        $params['userInfo'] = $this->userInfo;
        try {
            $data = (new RecommendService())->getRecommendDetailByStore($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

}
