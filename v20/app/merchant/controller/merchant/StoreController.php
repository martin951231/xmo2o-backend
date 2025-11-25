<?php

namespace app\merchant\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\service\MerchantStoreService;

class StoreController extends AuthBaseController
{

    /**
     * 获取商家店铺
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function getStoreList()
    {
        $rs = (new MerchantStoreService)->getStoreListByMerId($this->merId, 'name,store_id');
        return api_output(0, $rs);
    }

    /**
     * 获取优惠买单返还记录
     */
    public function getCashBackList()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['search_by'] = $this->request->post('search_by', 0, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        try {
            $data = (new MerchantStoreService)->getCashBackList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

     /**
     * 导出优惠买单返还记录
     */
    public function exportCashBackList()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['search_by'] = $this->request->post('search_by', 0, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        try {
            $data = (new MerchantStoreService)->exportCashBackList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}
