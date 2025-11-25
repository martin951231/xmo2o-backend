<?php
/**
 * MallOrderController.php
 * 店员端订单列表控制器
 * Create on 2020/11/2 9:18
 * Created by zhumengqun
 */

namespace app\mall\controller\storestaff;

use app\common\model\db\MerchantStore;
use app\mall\model\db\Express;
use app\mall\model\db\MallOrder;
use app\mall\model\db\ShopGoodsBatchLog;
use app\mall\model\service\activity\MallNewPeriodicPurchaseOrderService;
use app\mall\model\service\MallOrderService;
use app\mall\model\service\order_print\PrintHaddleService;
use app\mall\validate\OrderDelivery;
use app\storestaff\controller\storestaff\AuthBaseController;
use think\facade\Db;
use think\response\Json;

class MallOrderController extends AuthBaseController
{
    public function getOrderList()
    {
        //搜索条件(pc/wap)
        $param['re_type'] = 'storestaff';
        $param['type'] = $this->request->param('type', 'pc', 'trim');//pc/wap
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');//1=订单编号 2=第三方支付号 3=客户姓名 4=客户电话
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['act'] = $this->request->param('act', 'all', 'trim');//all:全部；砍价：bargain；拼团：group；限时：limited；预售：prepare；周期购：periodic；N元N价：reached；满包邮 :shipping;满赠 ：give；满减：minus；满折：discount
        $param['pay'] = $this->request->param('pay', '', 'trim');//微信支付：wechat，支付宝支付：alipay, 线下支付：offline_pay，云闪付：quick_pass，商家余额支付：merchant_balance，翼支付：win_pay，平台支付：platform_balance
        $param['express_type'] = $this->request->param('express_type', 0, 'trim');//1=骑手配送，2：普通快递 3：到店自提
        $param['source'] = $this->request->param('source', 1, 'trim');//安卓APP=androidapp,苹果APP=iosapp,微信小程序=wechat_mini,微信公众号=wechat_h5,移动网页=h5
        $param['store_id'] = $this->staffUser['store_id'];
        //$param['store_id'] = $this->request->param('store_id');
        $param['status'] = $this->request->param('status', 3, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['staff_id'] = $this->staffId;
        $param['use_type'] = 1; //1=列表使用  2= 导出使用
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->searchOrders($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    public function getOrderListCopy()
    {
        //搜索条件(pc/wap)
        $param['re_type'] = 'storestaff';
        $param['type'] = $this->request->param('type', 'pc', 'trim');//pc/wap
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');//1=订单编号 2=第三方支付号 3=客户姓名 4=客户电话
        $param['search_time_type'] = 'create_time';
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['act'] = $this->request->param('act', 'all', 'trim');//all:全部；砍价：bargain；拼团：group；限时：limited；预售：prepare；周期购：periodic；N元N价：reached；满包邮 :shipping;满赠 ：give；满减：minus；满折：discount
        $param['pay'] = $this->request->param('pay', '', 'trim');//微信支付：wechat，支付宝支付：alipay, 线下支付：offline_pay，云闪付：quick_pass，商家余额支付：merchant_balance，翼支付：win_pay，平台支付：platform_balance
        $param['express_type'] = $this->request->param('express_type', 0, 'trim');//1=骑手配送，2：普通快递 3：到店自提
        $param['source'] = $this->request->param('source', 1, 'trim');//安卓APP=androidapp,苹果APP=iosapp,微信小程序=wechat_mini,微信公众号=wechat_h5,移动网页=h5
        $param['store_id'] = $this->staffUser['store_id'];
        //$param['store_id'] = $this->request->param('store_id');
        $param['status'] = $this->request->param('status', 3, 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['staff_id'] = $this->staffId;
        $param['use_type'] = 1; //1=列表使用  2= 导出使用
        $param['verify'] = $this->request->param('verify', '', 'trim'); //1=列表使用  2= 导出使用
        $param['first_order_id'] = $this->request->param('first_order_id', 0, 'intval');//第一个订单id,id大于0时，获取的参数必须是这个订单之后的（杜绝产生新订单时，订单排序发生改变，加载数据出现重复订单）
        $mallStorestaffService = new MallOrderService();
        try {
            $store = MerchantStore::where(['store_id' => $param['store_id']])->find();
            $param['miaomaiyun_erp_storeid'] = $store['miaomaiyun_erp_storeid'] ?? 0;
            $res = $mallStorestaffService->searchOrdersCopy($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 手动接单
     */
    public function orderTaking()
    {
        $periodic_order_id = $this->request->param('periodic_order_id', '', 'intval');
        $order_id = $this->request->param('order_id', '', 'intval');
        $current_periodic = $this->request->param('current_periodic', '', 'intval');
        $periodic_count = $this->request->param('periodic_count', '', 'intval');
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->orderTaking($order_id, $periodic_order_id, $current_periodic, $periodic_count);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 店员修改配送方式
     * @return Json
     */
    public function changeDeliveryType(): Json
    {
        $orderId = $this->request->param('order_id', '', 'intval');
        $deliveryType = $this->request->param('express_style', '', 'intval');
        $mallOrderService = new MallOrderService();
        try {
            $res = $mallOrderService->changeDeliveryType($orderId, $deliveryType, $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $res, 'success');
    }
    
    public function uploadOrderDeliveryExcel(): Json
    {
        $file = request()->file('file');
        try {
            validate(['file'=>'require|fileSize:10240|fileExt:xlsx,xls'])->check(['file' => $file]);
            Db::transaction(function ()use($file){
                foreach (getExcelData($file, [
                    'A' => 'order_no',
                    'B' => 'delivery_type',
                    'C' => 'delivery_name',
                    'D' => 'delivery_num',
                ]) as $data) {
                    foreach ($data as $order){
                        if(empty($order['order_no'])){
                            continue;
                        }
                        validate([
                            'order_no|订单编号'=>'require',
                            'delivery_type|配送方式'=>'require|in:1,2'
                        ])->check($order);
                        if($order['delivery_type'] == 2){
                            validate([
                                'delivery_name|物流名称'=>'require',
                                'delivery_num|物流单号'=>'require'
                            ])->check($order);
                        }
                        $mallOrder = MallOrder::where(['order_no' => $order['order_no']])->find();
                        if($mallOrder['activity_type'] == 'periodic'){
                            throw_exception("[{$order['order_no']}]:周期购订单暂不支持导入");
                        }
                        try {
                            (new MallOrderService())->changeDeliveryType($mallOrder['order_id'], $order['delivery_type'], $this->staffUser);
                       
                            if($order['delivery_type'] == 2){//配送方式1：骑手配送，2：普通快递 3：到店自提'
                                $expressInfo = (new Express())->getOne(['name' => $order['delivery_name']]);
                                empty($expressInfo) && throw_exception("[{$order['order_no']}]:".$order['delivery_name'].'快递暂不支持');
                                (new MallOrderService())->deliverGoodsByExpress([
                                    'order_id' => $mallOrder['order_id'],
                                    'store_id' => $mallOrder['store_id'],
                                    'activity_type' => $mallOrder['activity_type'],
                                    'fh_type' => 1,
                                    'express_type' => $order['delivery_type'],
                                    'express_no' => $order['delivery_num'],
                                    'express_id' => $expressInfo['id'],
                                    'express_name' => $order['delivery_name'],
                                ]);
                            }
                        } catch (\Exception $e) {
                            throw_exception("[{$order['order_no']}]:".$e->getMessage());
                        }
                    }
                };
            });
        } catch (\Exception $e) {
            throw_exception($e->getMessage());
        }
        
        return api_output(0, true, 'success');
    }
    /**
     * 店员核销(杨宁宁说周期购和预售没有自提)
     */
    public function staffVerify()
    {
        $order_id = $this->request->param('order_id', '', 'intval');
        $type= $this->request->param('wxapp_type', '', 'trim');
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->staffVerify($order_id,$type,$this->staffUser);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 快递发货
     */
    public function deliverGoodsByExpress()
    {
        $param['order_id'] = $this->request->param('order_id', '', 'intval');
        $param['periodic_order_id'] = $this->request->param('periodic_order_id', '', 'intval');
        $param['current_periodic'] = $this->request->param('current_periodic', '', 'intval');
        $param['periodic_count'] = $this->request->param('periodic_count', '', 'intval');
        $param['activity_type'] = $this->request->param('activity_type', '', 'trim');
        $param['express_name'] = $this->request->param('express_name', '', 'trim');
        $param['express_type'] = $this->request->param('express_type', '', 'trim');
        $param['express_no'] = $this->request->param('express_no', '', 'trim');
        $param['express_id'] = $this->request->param('express_id', '', 'trim');
        $param['store_id'] = $this->staffUser['store_id'];
        $param['fh_type'] = $this->request->param('fh_type', '', 'intval');//1=发货 2=修改快递
        if(input('extra_delivery')){
            return $this->orderDelivery();//订单多包裹发货
        } 
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->deliverGoodsByExpress($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 订单多包裹发货
     */
    public function orderDelivery()
    {
        $params = array_merge([
            'periodic_order_id' => 0,
            'current_periodic'  => 0,
            'periodic_count'    => 0,
            'activity_type'     => '',
        ], input('post.'));
        $params['store_id'] = $this->staffUser['store_id'];
        
        try {
            $data = (new MallOrderService())->orderDelivery($params);
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    public function orderDeliveryList()
    {
        $params = array_merge([
            'periodic_order_id' => 0,
        ],input('post.'));
        try {
            validate(OrderDelivery::class)->scene('order_delivery_list')->check($params);
            $res = (new MallOrderService())->orderDeliveryList($params['order_id'], $params['periodic_order_id']);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 骑手配送
     */
    public function deliverGoodsByHouseman()
    {
        $order_id = $this->request->param('order_id', '', 'intval');
        $periodic_order_id = $this->request->param('periodic_order_id', '', 'intval');
        $current_periodic = $this->request->param('current_periodic', '', 'intval');
        $merId = $this->merId;
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->deliverGoodsByHouseman($order_id, $periodic_order_id,$current_periodic,$merId);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            fdump($e,"errorMallOrderDeliverGoodsByHouseman111",1);
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 顺延发货
     */
    public function postponeDelivery()
    {
        $periodic_order_id = $this->request->param('periodic_order_id', '', 'intval');
        $order_id = $this->request->param('order_id', '', 'intval');
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->postponeDelivery($periodic_order_id, $order_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 同意退款
     */
    public function AgreeRefund()
    {
        $order_id = $this->request->param('order_id', '', 'intval');
        $refund_id = $this->request->param('refund_id', '', 'intval');
        $is_all = $this->request->param('is_all', '', 'intval');
        if($this->request->param('is_all')===""){
            $is_all=1;
        }
        $mallStorestaffService = new MallOrderService();
        try {
            $lockKey = "mall_refund_" . $order_id;
            if (cache($lockKey)) {
                throw new \Exception('操作过于频繁，请30秒后再试');
            }
            cache($lockKey, 1, 30);

            $res = $mallStorestaffService->AgreeRefund($order_id, $refund_id, $is_all, 1);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 拒绝退款
     */
    public function RefuseRefund()
    {
        $order_id = $this->request->param('order_id', '', 'intval');
        $refund_id = $this->request->param('refund_id', '', 'intval');
        $reason = $this->request->param('reason', '', 'trim');
        $status = $this->request->param('status', '', 'intval');
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->RefuseRefund($order_id, $refund_id, $reason, $status);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 查看物流/骑手轨迹
     */
    public function viewLogistics()
    {
        $periodic_order_id = $this->request->param('periodic_order_id', '', 'intval');
        $order_id = $this->request->param('order_id', '', 'intval');
        $order_type = $this->request->param('order_type', '', 'trim');
        $express_style = $this->request->param('express_style', '', 'trim');
        
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->viewLogistics($order_id, $periodic_order_id, $order_type, $express_style);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *查看订单详情
     */
    public function getOrderDetails()
    {
        $order_id = $this->request->param('order_id', '', 'intval');
        $periodic_order_id = $this->request->param('periodic_order_id', '', 'intval');
        $refund_id = $this->request->param('refund_id', '', 'intval');
        $re_type = 'storestaff';
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->getOrderDetails($order_id, $periodic_order_id, $re_type, $refund_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *查看订单详情
     */
    public function getOrderDetailsCopy()
    {
        $order_id = $this->request->param('order_id', '', 'trim');
        $periodic_order_id = $this->request->param('periodic_order_id', '', 'intval');
        $refund_id = $this->request->param('refund_id', '', 'intval');
        $now_status = $this->request->param('now_status', 1, 'intval');
        $re_type = 'storestaff';
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->getOrderDetailsCopy($order_id, $periodic_order_id, $re_type, $refund_id, $now_status, $this->staffUser);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 店员备注
     */
    public function clerkNotes()
    {
        $order_id = $this->request->param('order_id', '', 'intval');
        $notes = $this->request->param('notes', '', 'trim');
        $status = $this->request->param('status', '', 'intval');
        $staff = isset(($this->staffUser)['name']) ? ($this->staffUser)['name'] : '';
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->clerkNotes($order_id, $notes, $status, $staff);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取快递
     */
    public function getExpress()
    {
        $mallStorestaffService = new MallOrderService();
        $store_id = $this->staffUser['store_id'];
        //$store_id = $this->request->param('store_id');
        try {
            $res = $mallStorestaffService->getExpress($store_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取各种状态数
     */
    public function getNumber()
    {
        $store_id = $this->staffUser['store_id'];
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->getNumber($store_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 店员修改价格
     */
    public function clerkDiscount()
    {
        $order_id = $this->request->param('order_id', '', 'intval');
        $before_money = $this->request->param('before_money', '', 'trim');
        $after_money = $this->request->param('after_money', '', 'trim');
        $staff = isset(($this->staffUser)['name']) ? ($this->staffUser)['name'] : '';
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->clerkDiscount($order_id, $before_money, $after_money, $staff);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 提醒
     * @author mrdeng
     */
    public function mallNotice()
    {
        $store_id = $this->staffUser['store_id'];
        $intval = $this->request->param('intval', 5, 'intval');
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->mallNotice($store_id, $intval);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导出商品
     * @return \json
     */
    public function exportOrder()
    {
        //搜索条件(pc/wap)
        $param['re_type'] = 'storestaff';
        $param['type'] = $this->request->param('type', 'pc', 'intval');//pc/wap
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');//1=订单编号 2=第三方支付号 3=客户姓名 4=客户电话
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['act'] = $this->request->param('act', 'all', 'trim');//all:全部；砍价：bargain；拼团：group；限时：limited；预售：prepare；周期购：periodic；N元N价：reached；满包邮 :shipping;满赠 ：give；满减：minus；满折：discount
        $param['pay'] = $this->request->param('pay', '', 'trim');//微信支付：wechat，支付宝支付：alipay, 线下支付：offline_pay，云闪付：quick_pass，商家余额支付：merchant_balance，翼支付：win_pay，平台支付：platform_balance
        $param['express_type'] = $this->request->param('express_type', 0, 'trim');//1=骑手配送，2：普通快递 3：到店自提
        $param['source'] = $this->request->param('source', 1, 'trim');//安卓APP=androidapp,苹果APP=iosapp,微信小程序=wechat_mini,微信公众号=wechat_h5,移动网页=h5
        $param['store_id'] = $this->staffUser['store_id'];
        $param['status'] = $this->request->param('status', '3', 'intval');
        $param['staff_id'] = $this->staffId;
        $param['use_type'] = 2; //1=列表使用  2= 导出使用
        $param['search_time_type'] = $this->request->param('search_time_type', '', 'trim');
        $param['columns'] =  $this->request->param('columns', '', 'trim');
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->addOrderExport($param, [], []);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 获取各种和
     * @return \json
     */
    public function getCollect()
    {
        $re_type = 'store_staff';
        $store_id = $this->staffUser['store_id'];
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getCollect($store_id, $re_type);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 周期购期数展示
     * @author 邓远辉
     */
    public function getPeriodicList()
    {
        $order_id = $this->request->param("order_id", 0, "intval");//订单id
        if (empty($order_id)) {
            throw new \think\Exception('参数缺失');
        }
        $where = [
            ['order_id', '=', $order_id],
            ['is_complete', '<>', 2]
        ];
        $all_list = (new MallNewPeriodicPurchaseOrderService())->getPeriodicOrderList($where);//总期数
        $return['nums'] = count($all_list);//总期数
        $where1 = [
            ['order_id', '=', $order_id],
            ['is_complete', 'in', [3, 4]],
        ];
        $complete_list = (new MallNewPeriodicPurchaseOrderService())->getPeriodicOrderList($where1);//完成期数
        $return['complete_num'] = count($complete_list);//完成期数
        $return['deliver_msg'] = (new MallNewPeriodicPurchaseOrderService())->getStoreManPreiodic($order_id);
        return api_output(0, $return);
    }

    /**
     * @return \json
     * 下载模板
     */
    public function downExcel()
    {
        $param['store_id'] = $this->staffUser['store_id'];
        $param['status'] = 10;

        $addr = (new MallOrderService())->orderExportExcel($param);
        return api_output(0, $addr);
    }

    /**
     * 失败模板
     */
    public function downFailExcel()
    {
        $log_id = $this->request->param("log_id", 0, "intval");//订单id
        $param['log_id'] = $log_id;
        $addr = (new MallOrderService())->failLogExportExcel($param);
        return api_output(0, $addr);
    }

    /**
     * @return \json
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \think\Exception
     *上传文件
     */
    public function uploadFile()
    {
        $param['store_id'] = $this->staffUser['store_id'];
        ///$param['type'] = $this->request->param("type", '', "trim");
        $type= $this->request->param("type", '', "trim");
        $param['type'] = empty($type) ?'add': $type;

        $param['file_url'] = $this->request->param("file_url", '', "trim");//订单id
        $param['name'] = $this->staffUser['name'];
        if (empty($param['file_url'])) {
            throw new \think\Exception('参数缺失');
        }
        $fileExtendName = substr(strrchr($param['file_url'], '.'), 1);
        $ret = (new MallOrderService())->uploadData($param, $fileExtendName);
        return api_output(0, $ret);
    }


    /**
     * 列表
     */
    public function shopGoodsBatchLogList()
    {
        $param['store_id'] = $this->staffUser['store_id'];
        $param['action'] = 6;
        $where = [['store_id', '=', $this->staffUser['store_id']], ['action', '=', 6]];
        $result['list'] = (new ShopGoodsBatchLog())->getList($where, 'pigcms_id desc');
        // (invoke_cms_model('Shop_order_log/goods_batch_log_list', [$param]))['retval'];
        if (!empty($result['list'])) {
            foreach ($result['list'] as $key => $val) {
                $result['list'][$key]['order_num'] = $val['error_num'] + $val['success_num'];
                $result['list'][$key]['work_status'] = $val['status'];
            }
        }
        return api_output(0, $result);
    }

    /**
     * 补打小票
     */
    public function printOrder()
    {
        $order_id = $this->request->post('order_id', 0, 'intval');
        $print_type = $this->request->post('print_type', 'bill_account', 'trim');
        try {
            if(empty($order_id)){
                throw new \think\Exception('order_id不能为空');
            }
              // 支付成功后打印小票
            $param = [
                'order_id' => $order_id,
                'print_type' => $print_type
            ];
            $data = (new PrintHaddleService)->printOrder($param, 2);
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
		return api_output(0, $data, 'success');
    }

}
