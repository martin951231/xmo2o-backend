<?php
/**
 * 餐饮桌台控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/30 10:16
 */

namespace app\foodshop\controller\api;

use app\foodshop\model\service\store\FoodshopTableService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\foodshop\model\service\store\FoodshopQueueService;

class FoodshopTableController extends ApiBaseController
{
    /**
     * 获得预订详情
     */
    public function bookTime()
    {
        // 店铺ID
        $storeId = $this->request->param("store_id", "", "intval");
        // 预订人数
        $bookNum = $this->request->param("book_num", "", "intval");
        // 预订日期
        $date = $this->request->param("date", "", "trim");
        // 预定时间
        $time = $this->request->param("time", "", "trim");
        // 桌台类型
        $tableType = $this->request->param("table_type", "", "trim");

        $param['storeId'] = $storeId;
        $param['bookNum'] = $bookNum;
        $param['date'] = $date;
        $param['time'] = $time;
        $param['tableType'] = $tableType;

        try {
            // 获得预订详情
            $timeList = (new FoodshopTableService())->getBookInfo($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $timeList);
    }


    /**
     * 扫码
     */
    public function scanCode()
    {
        // 店铺ID
        $storeId = $this->request->param("store_id", "", "intval");
        // 订单来源 0-在线预订选桌，1-桌台二维码，2-店员下单，3-在线预订选菜，4-直接选菜
        $orderFrom = $this->request->param("order_from", "", "intval");
        // 桌台id
        $tableId = $this->request->param("table_id", "", "intval");

        $foodshopStore = (new MerchantStoreFoodshopService())->getOne(['store_id'=>$storeId]);
        if(!$foodshopStore['take_seat_by_scan']){
            echo "<script>alert('未开启扫码落座');</script>";die;
        }

        // 直接跳转
        $url = $this->config['site_url'] . '/packapp/plat/pages/foodshop/store/storeMenu?order_from=' . $orderFrom . '&scan=1&store_id=' . $storeId . '&table_id=' . $tableId;
        return redirect($url);

    }

    /**
     * 桌台信息接口
     */
    public function detail()
    {
        // 店铺ID
        $param['store_id'] = $this->request->param("store_id", "", "intval");
        // 桌台id
        $param['table_id'] = $this->request->param("table_id", "", "intval");
        try {
            // 获得详情
            $result = (new FoodshopTableService())->getDetail($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $result);

    }

    /**
     * 获取线上取号开启/关闭状态
     * @author 钱大双
     * @date 2020/12/4
     */
    public function status()
    {
        try {
            // 店铺ID
            $store_id = $this->request->param("store_id", "", "intval");
            $rs = (new MerchantStoreFoodshopService())->checkStoreQueue($store_id, true);
            $status = $rs == true ? 1 : 0;
            $data = ['status' => $status];
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $data);
    }

    //线上排号列表
    public function queue_list()
    {
        try {
            // 店铺ID
            $param['store_id'] = $this->request->param("store_id", "", "intval");
            // 用户ID
            $param['uid'] = $this->_uid;
            $rs = (new FoodshopQueueService())->getFoodshopQueueListOn($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $rs);
    }

    //线上取号
    public function add_queue()
    {
        if(!$this->_uid){
            throw new \think\Exception('未登录', 1002);
        }
        
        try {
            // 店铺ID
            $param['store_id'] = $this->request->param("store_id", "", "intval");
            // 用户ID
            $param['uid'] = $this->_uid;
            //桌台类型ID
            $param['table_type'] = $this->request->param('table_type', '0', 'intval');
            $param['queue_from'] = 0;//排号来源(0:在线，1:现场取号)

            //取号之前先校验店铺状态
            (new MerchantStoreFoodshopService())->checkStore($param['store_id'], true, true);
            (new FoodshopQueueService())->addFoodshopQueue($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0);
    }

    //线上取消排号 如果第三个排号是线上取号，同时发送排队提醒通知
    public function update_queue()
    {
        try {
            $param['id'] = $this->request->param('queue_id', '0', 'intval');
            $param['status'] = $this->request->param('status', '0', 'intval');
            $param['queue_from'] = 0;//排号来源(0:在线，1:现场取号)
            (new FoodshopQueueService())->updateFoodshopQueue($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0);
    }

    //获取线上排号详情
    public function detail_queue()
    {
        try {
            $param['id'] = $this->request->param('queue_id', '0', 'intval');
            $detail = (new FoodshopQueueService())->getDetailById($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $detail);
    }
}
