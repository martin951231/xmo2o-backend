<?php


namespace app\thirdAccess\controller\village;

use app\common\controller\CommonBaseController;

use app\thirdAccess\model\service\BillAccessService;

class BillManagementController extends CommonBaseController
{
    public function villageLists() {
        $property_id = $this->request->param('property_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        if ($thirdErrMsg && $thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = $this->request->param('thirdId','','trim');
        if ($property_id) {
            $businessType = 'property';
            $businessId = $property_id;
            if (!$thirdId) {
                $thirdId = -$property_id;
            }
        } else {
            $businessType = 'system';
            $businessId = $thirdId = -999;
        }
        $thirdSite = $_SERVER['HTTP_HOST'];
        $thirdPostType = $this->request->param('thirdType','','trim');
        if ($thirdType!=$thirdPostType) {
            return api_output_error('1001', '鉴权失败');
        }
        $page = $this->request->param('page',0,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $paramData = [
            'property_id' => $property_id,
            'operation' => 'getData',
            'thirdType' => $thirdType,
            'businessType' => $businessType,
            'businessId' => $businessId,
            'thirdSite' => $thirdSite,
            'thirdId' => $thirdId,
            'page' => $page,
            'limit' => $limit,
        ];
        $billAccessService = new BillAccessService();
        $arr = [];
        try {
            $data = $billAccessService->getVillageData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        $arr['list'] = $data;
        return api_output(0,$arr);
    }
    public function getVillageOwnerInfo() {
        $village_id = $this->request->param('village_id','','intval');
        $phone = $this->request->param('phone','','trim');
        $name = $this->request->param('name','','trim');
        $billAccessService = new BillAccessService();
        if ($village_id<1) {
            return api_output_error('1001', '小区编号参数错误！');
        }
        if (empty($phone)) {
            return api_output_error('1001', '查询手机号不能为空！');
        }
        try {
            $dataArr = $billAccessService->getVillageOwnerInfo($village_id,$phone);
            $dataArr['village_id']=$village_id;
            $dataArr['phone']=$phone;
            return api_output(0,$dataArr);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
    }
    
    public function villageUserBalanceChange() {
        $village_id = $this->request->param('village_id','','intval');
        $uid = $this->request->param('uid',0,'intval');  //平台身份
        $opt_type = $this->request->param('opt_type','user_recharge','trim');  //user_recharge增加  user_deduct扣除
        $room_id = $this->request->param('room_id',0,'intval');  //房间
        $user_bind_id = $this->request->param('user_bind_id',0,'intval'); //住户身份
        $ip = $this->request->param('ip','','trim'); //ip
        $opt_money= $this->request->param('opt_money',0); //操作金额
        $pay_type= $this->request->param('pay_type','','trim'); //支付方式
        $third_order_id= $this->request->param('third_order_id','','trim'); //支付订单id
        $desc = $this->request->param('opt_bak','','trim'); //备注
        $billAccessService = new BillAccessService();
        if ($village_id<1) {
            return api_output_error('1001', '小区编号参数错误！');
        }
        if ($uid<1) {
            return api_output_error('1001', '业主平台UID参数错误！');
        }
        if (!in_array($opt_type,['user_recharge','user_deduct'])) {
            return api_output_error('1001', '住户余额操作类型错误！');
        }
        if ($opt_money<=0) {
            return api_output_error('1001', '住户余额操作金额错误！');
        }
        if ($room_id<1 || $user_bind_id<1) {
            return api_output_error('1001', '业主住户信息错误！');
        }
        if (empty($desc)) {
            //return api_output_error('1001', '余额操作备注不能为空！');
        }
        try {
            $pdata=array('opt_type'=>$opt_type,'room_id'=>$room_id,'user_bind_id'=>$user_bind_id,'ip'=>$ip,'opt_money'=>$opt_money,'pay_type'=>$pay_type,'third_order_id'=>$third_order_id);
            $pdata['desc']=$desc;
            $dataArr = $billAccessService->villageUserBalanceChange($village_id,$uid,$pdata);
            return api_output(0,$dataArr);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
    }
    
    public function userNewPayOrders() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg && $thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        if (!$village_id) {
            return api_output_error(1001, '请上传小区ID');
        }
        $phone = $this->request->param('phone','','trim');
        $name = $this->request->param('name','','trim');
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        $order_type = $this->request->param('order_type','','trim');
        if (!$pigcms_id && !$phone) {
            return api_output_error(1001, '请上传身份ID或者手机号');
        }
        $page = $this->request->param('page',0,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $paramData = [
            'village_id' => $village_id,
            'operation' => 'getData',
            'thirdType' => $thirdType,
            'thirdSite' => $thirdSite,
            'phone' => $phone,
            'name' => $name,
            'pigcms_id' => $pigcms_id,
            'page' => $page,
            'order_type'=>$order_type,
            'limit' => $limit,
        ];
        $billAccessService = new BillAccessService();
        $arr = [];
        try {
            $data = $billAccessService->userNewPayOrders($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        $arr['list'] = $data;
        return api_output(0,$arr);
    }

    public function payUserOrders() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        if ($thirdErrMsg && $thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        if (!$village_id) {
            return api_output_error(1001, '请上传小区ID');
        }
        $phone = $this->request->param('phone','','trim');
        $name = $this->request->param('name','','trim');
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (!$pigcms_id && !$phone) {
            return api_output_error(1001, '请上传身份ID或者手机号');
        }
        /** order: 7cc9886b964d438b91e903c2205fff3d,a637d9eacd5e8f3d6f651b4e6cd54a66,c89d74639ca36990191d0a61adb69586  注意是英文逗号拼接 */
        $orders = $this->request->param('orders');
        if (!$orders) {
            return api_output_error(1001, '请上传订单');
        }
        /** orderDetail: [{orderId: cc9886b964d438b91e903c2205fff3d, payMoney: 100},{orderId: a637d9eacd5e8f3d6f651b4e6cd54a66, payMoney: 80},{orderId: c89d74639ca36990191d0a61adb69586, payMoney: 70}] */
        $orderDetail = $this->request->param('orderDetail');
        fdump_api($orderDetail, '$orderDetail');
        if (!is_array($orderDetail) || !isset($orderDetail[0])) {
            fdump_api($orderDetail, '$orderDetail',1);
            $orderDetail = json_decode($orderDetail, true);
        }
        fdump_api($orderDetail, '$orderDetail',1);
        if (!is_array($orderDetail) && !isset($orderDetail[0])) {
            return api_output_error(1001, '请上传正确的订单详情格式');
        }
        /** payType: 支付方式 英文别称 比如 alipay  支付宝支付  weixin  微信支付  aiHorsePay 小红马对接支付*/
        $payType = $this->request->param('payType','','trim');
        if (!$payType && $thirdType) {
            $payType = $thirdType.'Pay';
        }
        $payTime = $this->request->param('payTime',0,'intval');
        $paramData = [
            'village_id' => $village_id,
            'operation' => 'payOrder',
            'thirdType' => $thirdType,
            'phone' => $phone,
            'name' => $name,
            'pigcms_id' => $pigcms_id,
            'orders' => $orders,
            'orderDetail' => $orderDetail,
            'payType' => $payType,
            'payTime' => $payTime,
        ];
        $billAccessService = new BillAccessService();
        $arr = [];
        try {
            $data = $billAccessService->payUserOrders($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        $arr['list'] = $data;
        return api_output(0,$arr);
    }

    public function billOrders() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        if ($thirdErrMsg && $thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        if (!$village_id) {
            return api_output_error(1001, '请上传小区ID');
        }
        $startTime = $this->request->param('startTime',0,'intval');
        $endTime = $this->request->param('endTime',0,'intval');
        $paramData = [
            'village_id' => $village_id,
            'operation' => 'billOrders',
            'thirdType' => $thirdType,
            'startTime' => $startTime,
            'endTime' => $endTime,
        ];
        $billAccessService = new BillAccessService();
        $arr = [];
        try {
            $data = $billAccessService->billOrders($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        $arr['list'] = $data;
        return api_output(0,$arr);
    }

    public function billBankFile() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg && $thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        if (!$village_id) {
            return api_output_error(1001, '请上传小区ID');
        }
        $sftpFilePath = $this->request->param('sftpFilePath','','trim');
        if (!$sftpFilePath) {
            return api_output_error(1001, '请上传对账文件路径');
        }
        $billId = $this->request->param('billId','','trim');
        if (!$billId) {
            return api_output_error(1001, '请上传对账Id');
        }
        $paramData = [
            'village_id' => $village_id,
            'operation' => 'billBankFile',
            'thirdSite' => $thirdSite,
            'thirdType' => $thirdType,
            'sftpFilePath' => $sftpFilePath,
            'billId' => $billId,
        ];
        $billAccessService = new BillAccessService();
        $arr = [];
        try {
            $data = $billAccessService->billBankFile($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        $arr['list'] = $data;
        return api_output(0,$arr);
    }
}