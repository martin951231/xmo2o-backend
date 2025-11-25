<?php
/**
 * 购买房间套餐相关
 * @author weili
 * @datetime 2020/8/18
 */

namespace app\community\controller\property_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\RoomPackageService;
use app\community\model\service\PackageRoomOrderService;
class RoomPackageController extends CommunityBaseController
{
    /**
     * Notes:  房间套餐
     * @return \json
     * @author: weili
     * @datetime: 2020/8/19 9:58
     */
    public function getRoomList()
    {
        $serviceRoomPackage = new RoomPackageService();
        $where[] = ['status','=',0];
        $order = 'room_id desc';
        $field = 'room_id,room_title,room_count,room_price,sort,status,create_time';
        try {
            $data = $serviceRoomPackage->getRoomPackageList($where,$field,$order,0,0);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * Notes:购买房间套餐  获取选中房间套餐单个房间的金额及总金额
     * @return \json
     * @author: weili
     * @datetime: 2020/8/19 11:01
     */
    public function getBuyRoom()
    {
        if (!isset($this->adminUser['property_id'])) {
            return api_output(1002, [], "物业信息不存在");
        }
        $property_id = $this->adminUser['property_id'];
        if (!isset($property_id)) {
            return api_output(1002, [], "物业信息不存在");
        }
        $room = $this->request->param('room');//房间套餐id和对应的数量
        //例如：
//        $room = [
//            [
//                'room_id'=>1,
//                'room_num'=>2,
//            ],
//            [
//                'room_id'=>2,
//                'room_num'=>2,
//            ],
//        ];
        if(!$room && count($room)<=0)
        {
            return api_output_error(-1,'参数错误');
        }
        $serviceRoomPackage = new RoomPackageService();
        try {
            $data = $serviceRoomPackage->getOptBuyRoomOrder($room,$property_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * Notes:房间套餐生成订单
     * @return \json
     * @author: weili
     * @datetime: 2020/8/19 15:44
     */
    public function createRoomOrder()
    {
        if (!isset($this->adminUser['property_id'])) {
            return api_output(1002, [], "物业信息不存在");
        }
        $property_id = $this->adminUser['property_id'];
        if (!isset($property_id)) {
            return api_output(1002, [], "物业信息不存在");
        }
        $room = $this->request->param('room');//房间套餐id和对应的数量
        //$pay_type = $this->request->param('pay_type','','intval');//支付类型 1微信 2支付宝
        //例如：
//        $room = [
//            [
//                'room_id'=>1,
//                'room_num'=>2,
//            ],
//            [
//                'room_id'=>2,
//                'room_num'=>2,
//            ],
//        ];
        if((!$room && count($room)<=0))
        {
            return api_output_error(-1,'参数错误');
        }
        $servicePackageRoomOrder = new PackageRoomOrderService();
        try {
            $data = $servicePackageRoomOrder->createOrder($room,$property_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
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
        $servicePackageRoomOrder = new PackageRoomOrderService();
        try {
            $data = $servicePackageRoomOrder->getOrderStatus($order_id);
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
        $order_type = 'package_room';
        $servicePackageRoomOrder =new PackageRoomOrderService();
        $url ='?order_id='.$order_id.'&order_type='.$order_type;
        try {
            $info = $servicePackageRoomOrder->createQrCode($url,$order_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }
}