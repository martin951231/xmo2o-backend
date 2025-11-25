<?php
/**
 * 物业订购套餐相关
 * @author weili
 * @datetime 2020/8/17
 */

namespace app\community\controller\property_api;

use app\community\model\service\PackageRoomOrderService;
use app\community\model\service\RoomPackageService;
use app\community\model\service\PackageOrderService;
use app\community\model\service\PrivilegePackageService;
use app\community\controller\CommunityBaseController;

use app\community\model\service\ConfigService;
use app\community\model\service\MarketOrderService;
class PrivilagePackageController extends CommunityBaseController
{
    /**
     * Notes: 获取功能订购套餐
     * @return \json
     * @author: weili
     * @datetime: 2020/8/17 14:38
     */
    public function getOrderPackage()
    {
        if (!isset($this->adminUser['property_id'])) {
            return api_output(1002, [], "物业信息不存在");
        }
        $is_overdue = $this->request->param('is_overdue',0,'intval');
        $property_id = $this->adminUser['property_id'];
        $servicePrivilegePackage = new PrivilegePackageService();
        $where[] = ['status','=',1];
        $order='package_price asc,sort desc,package_id asc';
        try {
            $list = $servicePrivilegePackage->getPackageContent($where,$order,$property_id,$is_overdue);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * Notes:功能套餐/房间套餐
     * @return \json
     * @author: weili
     * @datetime: 2020/8/18 9:01
     */
    public function getPackageRoom()
    {
        if (!$this->adminUser['property_id']) {
            return api_output(1002, [], "物业信息不存在");
        }
        $property_id = $this->adminUser['property_id'];

        $package_id = $this->request->param('package_id','0','intval');
        $package_num = $this->request->param('package_num','0','intval');
        $type = $this->request->param('type','0','intval');//1续费 2升级购买
        $order_id = $this->request->param('order_id','0','intval');//订单id
        if(!$package_id || !$package_num || !$type){
            return api_output_error(-1, '传参异常');
        }
        if($type == 1 && !$order_id)
        {
            return api_output_error(-1, '传参异常');
        }
        $serviceRoomPackage = new RoomPackageService();
        try {
            $data = $serviceRoomPackage->getRoomList($package_id,$package_num,$type,$order_id,$property_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }


    /**
     * 获取房间套餐列表数据
     * @author:zhubaodi
     * @date_time: 2022/3/4 9:53
     */
    public function getPackageRoomList()
    {
        if (!$this->adminUser['property_id']) {
            return api_output(1002, [], "物业信息不存在");
        }
        $property_id = $this->adminUser['property_id'];

        $serviceRoomPackage = new RoomPackageService();
        try {
            $data = $serviceRoomPackage->getPackageRoomList($property_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * 计算购买房间的价格
     * @author:zhubaodi
     * @date_time: 2022/3/4 9:53
     */
    public function getRoomOrderPrice(){
        if (!$this->adminUser['property_id']) {
            return api_output(1002, [], "物业信息不存在");
        }
        $data['room_list'] = $this->request->param('room_list','','trim');
        $data['property_id'] = $this->adminUser['property_id'];
        $serviceRoomPackage = new RoomPackageService();
        try {
            $data = $serviceRoomPackage->getRoomOrderPrice($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * 购买房间生成新的订单
     */
    public function createRoomOrderNew()
    {
        if (!$this->adminUser['property_id']) {
            return api_output(1002, [], "物业信息不存在");
        }
        $data['room_list'] = $this->request->param('roomList','','trim');
        $data['property_id'] = $this->adminUser['property_id'];
        $serviceRoomPackage = new RoomPackageService();
        try {
            $data = $serviceRoomPackage->createRoomOrderNew($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * Notes: 生成功能套餐房间套餐订单
     * @return \json
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/19 17:55
     */
    public function createOrder()
    {
        $property_id = $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], "物业信息不存在");
        }

        $package_id = $this->request->param('package_id','0','intval');
        $package_num = $this->request->param('package_num','0','intval');
        $room = $this->request->param('room');
        $type = $this->request->param('type','','trim');//(type=1续费 2升级 3购买)
        $pay_type = $this->request->param('pay_type','','trim');//支付方式（wechat微信 alipay支付宝）
        $order_id = $this->request->param('order_id','','trim');
        $compute_type = $this->request->param('compute_type','','intval');//只计算价格 2生成订单，否则只计算价格
//        $room = [
//            [
//                'room_id'=>1,
//                'room_num'=>0,
//            ],
//            [
//                'room_id'=>2,
//                'room_num'=>0,
//            ],
//        ];

        $servicePackageOrder =new PackageOrderService();
        $new_order = $servicePackageOrder->getPropertyOrderPackage($property_id);

        if(!$package_id || !$package_num || !$type)
        {
            return api_output_error(-1, '传参异常');
        }
        if($new_order && ($type==2 || $type==3) && !$order_id)
        {
            return api_output_error(-1, '传参异常');
        }
//        $property_id=30;
        $postArr = [
            'package_id'=>$package_id,
            'package_num'=>$package_num,
            'room'=>$room,
            'property_id'=>$property_id,
            'pay_type'=>$pay_type,
        ];
        if($order_id){
            $postArr['order_id'] = $order_id;
        }
        $postArr['compute_type'] = $compute_type;

        try {
            $data = $servicePackageOrder->createPackageOrder($postArr,$type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * Notes: 获取当前可使用的支付方式   (弃用)
     * @return \json
     * @author: weili
     * @datetime: 2020/8/18 20:23
     */
    public function getPayType()
    {
        $serviceConfig = new ConfigService();
        $serviceMarketOrder = new MarketOrderService();
        $pay_method_all = $serviceConfig->get_pay_method(0,0);

        $pay_method = $serviceMarketOrder->pay_method(true, false);

        if ($pay_method_all['weixin']) {
            $pay_method['wechat'] = $pay_method_all['weixin'];
            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
            $pay_method['wechat']['icon'] = $http_type.$_SERVER['SERVER_NAME'].'/static/images/pay/weixin.gif';
        }
        return api_output(0, $pay_method, "成功");
    }
    /**
     * Notes: 循环查询订单状态
     * @return \json
     * @author: weili
     * @datetime: 2020/8/20 14:25
     */
    public function queryOrderPayStatus()
    {
        $order_id = $this->request->param('order_id','','trim');
        if(!$order_id)
        {
            return api_output_error(-1, '传参异常');
        }
        $servicePackageOrder =new PackageOrderService();
        try {
            $data = $servicePackageOrder->getOrderStatus($order_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * Notes: 套餐支付调转二维码
     * @return \json
     * @author: weili
     * @datetime: 2020/8/20 16:39
     */
    public function createQrCode()
    {
        $order_id = $this->request->param('order_id','','trim');
        if(!$order_id){
            return api_output_error(-1, '传参异常');
        }
        $order_type = 'package';
        $servicePackageOrder =new PackageOrderService();
        $url ='?order_id='.$order_id.'&order_type='.$order_type;
        try {
            $info = $servicePackageOrder->createQrCode($url,$order_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }

    /**
     * 套餐支付调转二维码
     * @author:zhubaodi
     * @date_time: 2022/3/5 11:36
     */
    public function createRoomQrCode(){
        $order_id = $this->request->param('order_id','','trim');
        if(!$order_id){
            return api_output_error(-1, '传参异常');
        }
        $order_type = 'package_room';
        $servicePackageOrder =new PackageRoomOrderService();
        $url ='?order_id='.$order_id.'&order_type='.$order_type;
        try {
            $info = $servicePackageOrder->createQrCode($url,$order_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }
}