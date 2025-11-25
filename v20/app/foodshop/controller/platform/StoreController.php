<?php
/**
 * 餐饮店铺控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:46
 */

namespace app\foodshop\controller\platform;
use app\foodshop\controller\platform\AuthBaseController;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
class StoreController extends AuthBaseController
{
    
    /**
     * 获得店铺列表
     */
    public function storeList()
    {
        
        $merchantStoreFoodshopService = new MerchantStoreFoodshopService();

        // 搜索条件
        $keyword = $this->request->param("keyword", "", "trim");
        $param['keyword'] = $keyword;
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        // 排序
        $param['order'] = $this->request->param("order",'' , "");

        // 获得列表
        $list = $merchantStoreFoodshopService->getPlatStoreList($param,$this->systemUser);

        
        return api_output(0, $list);
    }   
    
    /**
     * 保存店铺排序
     */
    public function saveSort()
    {
        
        $merchantStoreFoodshopService = new MerchantStoreFoodshopService();
        // 店铺ID
        $param['store_id'] = $this->request->param("store_id", "", "intval");
        // 排序值
        $param['sort'] = $this->request->param("sort", "", "intval");
        
        // 保存店铺排序
        $res = $merchantStoreFoodshopService->saveSort($param);
        return api_output(0, $res);
    }
}
