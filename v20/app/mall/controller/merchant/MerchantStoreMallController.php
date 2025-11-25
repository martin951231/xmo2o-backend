<?php
/**
 * MerchantStoreMallController.php
 * 编辑、完善店铺信息
 * Create on 2020/9/22 17:14
 * Created by zhumengqun
 */

namespace app\mall\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\mall\model\service\MerchantStoreMallService;

class MerchantStoreMallController extends AuthBaseController
{
    /**
     * 完善店铺信息
     * @return \json
     */
    public function perfectedStore()
    {
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['is_delivery'] = $this->request->param('is_delivery', 1, 'intval');
        $param['is_houseman'] = $this->request->param('is_houseman', 1, 'intval');
        $param['is_zt'] = $this->request->param('is_zt', 1, 'intval');
        $param['horseman_type'] = $this->request->param('horseman_type', 1, 'intval');
        $param['stockup_minute'] = $this->request->param('stockup_minute', 0, 'trim');
        $param['stockup_hour'] = $this->request->param('stockup_hour', 0, 'trim');
        $param['stockup_day'] = $this->request->param('stockup_day', 0, 'trim');
        $param['new_order_warn'] = $this->request->param('new_order_warn', 1, 'intval');
        $param['delivery_fee_type'] = $this->request->param('delivery_fee_type', 1, 'intval');
        $param['e_invoice_status'] = $this->request->param('e_invoice_status', 0, 'intval');
        //$param['level_off'] = $this->request->param('level_off', '');
        $param['store_notice'] = $this->request->param('store_notice', '', 'trim');
        $param['invoice_money'] = $this->request->param('invoice_money', '', 'trim');
        $param['device_id'] = $this->request->param('device_id', '', 'trim');
        $param['single_face_info'] = $this->request->param('single_face_info','');
        $param['delivery_range_type'] = $this->request->param('delivery_range_type', 1, 'intval');
        $param['delivery_area'] = $this->request->param('delivery_area', '', 'trim');
        $param['delivery_range_polygon'] = $this->request->param('delivery_range_polygon', '', 'trim');

        $param['s_basic_price'] = $this->request->param('s_basic_price', 0, 'intval');
        $param['s_extra_price'] = $this->request->param('s_extra_price', 0, 'intval');
        $param['s_is_open_own'] = $this->request->param('s_is_open_own', 0, 'intval');
        $param['s_basic_price1'] = $this->request->param('s_basic_price1', 0, 'intval');

        $param['s_free_type'] = $this->request->param('s_free_type', 0, 'intval');
        $param['s_delivery_fee'] = $this->request->param('s_delivery_fee', 0, 'intval');
        $param['s_basic_distance'] = $this->request->param('s_basic_distance', 0, 'intval');
        $param['s_per_km_price'] = $this->request->param('s_per_km_price', 0, 'intval');
        $param['s_full_money'] = $this->request->param('s_full_money', 0, 'intval');
        $param['s_basic_price2'] = $this->request->param('s_basic_price2', 0, 'intval');
        $param['s_free_type2'] = $this->request->param('s_free_type2', 0, 'intval');
        $param['s_delivery_fee2'] = $this->request->param('s_delivery_fee2', 0, 'intval');

        $param['s_basic_distance2'] = $this->request->param('s_basic_distance2', 0, 'intval');
        $param['s_per_km_price2'] = $this->request->param('s_per_km_price2', 0, 'intval');
        $param['s_full_money2'] = $this->request->param('s_full_money2', 0, 'intval');
        $param['s_basic_price3'] = $this->request->param('s_basic_price3', 0, 'intval');

        $param['s_free_type3'] = $this->request->param('s_free_type3', 0, 'intval');
        $param['s_delivery_fee3'] = $this->request->param('s_delivery_fee3', 0, 'intval');
        $param['s_basic_distance3'] = $this->request->param('s_basic_distance3', 0, 'intval');
        $param['s_per_km_price3'] = $this->request->param('s_per_km_price3', 0, 'intval');
        $param['s_full_money3'] = $this->request->param('s_full_money3', 0, 'intval');
        $param['virtual_delivery_fee'] = $this->request->param('virtual_delivery_fee', 0, 'intval');
        $param['level_off'] = $this->request->param('level_off', '', 'trim');
        $param['store_notice'] = $this->request->param('store_notice', '', 'trim');
        /*运维关闭严格模式就可以了*/
        $merchantStoreMallService = new MerchantStoreMallService();
        try {
            $result = $merchantStoreMallService->perfectedStore($param);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 获取配置列表
     * @return \json
     */
    public function getStoreConfigList()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $merchantStoreMallService = new MerchantStoreMallService();
        try {
            $arr = $merchantStoreMallService->getStoreConfigList($store_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 获取商城店铺列表
     * @author 钱大双
     * @date 2020/09/21
     */
    public function getStoreList()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;
        if ($merId < 1) {
            return api_output(1003, [], '商家ID不存在');
        }
        $page = $this->request->param("page", "1", "intval");
        $pageSize = $this->request->param("pageSize", "15", "intval");
        $lists = (new MerchantStoreMallService())->getStoremallStoreListByMerId($merId, $page, $pageSize);
        return api_output(0, $lists);
    }
}