<?php
/**
 * 商家后台店铺管理控制器
 * Author: hengtingmei
 * Date Time: 2021/08/30 20:12
 */

namespace app\new_marketing\controller\merchant;
use app\merchant\controller\merchant\AuthBaseController;
use app\new_marketing\model\service\MarketingStoreService;

class StoreController extends AuthBaseController
{
    /**
     * 店铺使用情况
     */
    public function getStoreUserdDetail()
    {
        $list = (new MarketingStoreService())->getStoreUserdDetail($this->merchantUser);
        return api_output(0, $list);
    }
    
    /**
     * 获得某个分类下购买的店铺详情
     */
    public function getCategoryStoreDetail()
    {
        $param['cat_id'] = $this->request->param('cat_id', 0, 'intval');
        $list = (new MarketingStoreService())->getCategoryStoreDetail($param['cat_id'], $this->merchantUser['mer_id']);
        return api_output(0, $list);
    }
    
    
    /**
     * 获得分类下购买的店铺数量
     */
    public function getCategoryStoreList()
    {
        $list = (new MarketingStoreService())->getCategoryStoreList($this->merchantUser['mer_id']);
        return api_output(0, $list);
    }
}
