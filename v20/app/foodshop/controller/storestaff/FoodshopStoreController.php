<?php
/**
 * 餐饮
 * @author 衡婷妹
 * @date 2020/08/26
 */

namespace app\foodshop\controller\storestaff;

use app\foodshop\model\service\order\DiningOrderService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\foodshop\model\service\store\FoodshopTableService;
use app\foodshop\model\service\store\FoodshopTableTypeService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\foodshop\model\service\store\FoodshopQueueService;
use app\foodshop\model\service\store\MerchantStoreStaffService;

class FoodshopStoreController extends AuthBaseController
{
    /**
     * 获取餐饮店铺桌台分类
     * @author 衡婷妹
     * @date 2020/08/26
     */
    public function tableTypeList()
    {
        $param['is_change_table'] = $this->request->param('is_change_table', 0, 'intval');
        $rs = (new FoodshopTableTypeService())->getStaffTableTypeList($param, $this->staffUser);
        try {
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $rs);
    }

    /**
     * 获取桌台列表
     * @author 衡婷妹
     * @date 2020/08/26
     */
    public function tableList()
    {
        $param['order_status'] = $this->request->param('order_status', 0, 'intval');
        $param['is_change_table'] = $this->request->param('is_change_table', 0, 'intval');
        $param['table_id'] = $this->request->param('table_id', 0, 'intval');

        $rs = (new FoodshopTableService())->getStaffTableList($param, $this->staffUser);
        try {
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $rs);
    }

    /**
     * 获取桌台订单列表
     * @author 衡婷妹
     * @date 2020/08/26
     */
    public function tableOrderList()
    {
        $param['table_id'] = $this->request->param('table_id', '0', 'intval');
        $where['order_status'] = 3;
        $where['table_id'] = $param['table_id'];
        $where['order_by'] = ['o.order_id' => 'ASC'];
        try {
            $rs = (new DiningOrderService())->getOrderListLimit($where, 1);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $rs);
    }

    /**
     * 店员重置排号
     * @author 钱大双
     * @date 2020/12/4
     */
    public function reset_queue()
    {
        try {
            $param['store_id'] = $this->staffUser['store_id'];
            (new FoodshopQueueService())->delData($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0);
    }

    /**
     * 店员开启/关闭线上取号功能
     * @author 钱大双
     * @date 2020/12/4
     */
    public function change_queue()
    {
        try {
            $queue_is_open = $this->request->param('queue_is_open', '0', 'intval');//0:关闭，1：开启
            $where = [];
            $where['store_id'] = $this->staffUser['store_id'];
            $data = ['queue_is_open' => $queue_is_open, 'queue_open_time' => time()];
            (new MerchantStoreFoodshopService())->updateThis($where, $data);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0);
    }

    //线下排号列表
    public function queue_list()
    {
        try {
            $rs = (new FoodshopQueueService())->getFoodshopQueueListOff($this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $rs);
    }

    //线下取号
    public function add_queue()
    {
        try {
            $param['table_type'] = $this->request->param('table_type', '0', 'intval');//桌台类型ID 店员移动端必传
            $param['num'] = $this->request->param('num', '0', 'intval');//就餐人数 PC端取号必传
            $param['phone'] = $this->request->param('phone', '', 'intval');//就餐电话
            $param['queue_from'] = 1;//排号来源(0:在线，1:现场取号)
            $param['store_id'] = $this->staffUser['store_id'];// 店铺ID
            (new FoodshopQueueService())->addFoodshopQueue($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0);
    }

    //线下就餐过号，如果第三个排号是线上取号，同时发送排队提醒通知
    public function update_queue()
    {
        try {
            $param['id'] = $this->request->param('id', '0', 'intval');
            $param['status'] = $this->request->param('status', '0', 'intval');
            $param['queue_from'] = 1;//排号来源(0:在线，1:现场取号)
            (new FoodshopQueueService())->updateFoodshopQueue($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0);
    }

    //线下叫号 如果当前排号线上取号，同时发送到号提醒通知
    public function use_queue()
    {
        try {
            $param['id'] = $this->request->param('id', '', 'intval');//排号ID
            $rs = (new MerchantStoreStaffService())->sendMsgQueue($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $rs);
    }

    /**
     * 获取线上取号开启/关闭状态
     * @author 衡婷妹
     * @date 2021/04/09
     */
    public function status()
    {
        try {
            // 店铺ID
            $rs = (new MerchantStoreFoodshopService())->checkStoreQueue($this->staffUser['store_id'], true);
            $status = $rs == true ? 1 : 0;
            $data = ['status' => $status];
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $data);
    }
}
