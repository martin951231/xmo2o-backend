<?php
/**
 * 店员后台会员卡管理控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/08/29 16:44
 */

namespace app\storestaff\controller\storestaff;
use app\merchant\model\service\card\CardUserlistService;
class MerchantCardController extends AuthBaseController
{

    /**
     * 查询会员列表
     * Author: hengtingmei
     * Date Time: 2020/08/29 16:44
     */
    public function getUserCard()
    {

        // 搜索关键词
        $param['keyword'] = $this->request->param("keyword", "", "trim");

        // 店铺id
        $param['store_id'] = $this->staffUser['store_id'];

        // 获得商品列表
        try {
            $result = (new CardUserlistService())->getUserCard($param, []);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0,$result);
    }

}
