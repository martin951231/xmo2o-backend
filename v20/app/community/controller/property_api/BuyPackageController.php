<?php
/**
 * 物业订购套餐相关（功能套餐/房间套餐）
 * @author weili
 * @datetime 2020/8/19
 */

namespace app\community\controller\property_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\PackageOrderService;//功能套餐
use app\community\model\service\PackageRoomOrderService;//房间套餐
class BuyPackageController extends CommunityBaseController
{
    /**
     * Notes: 物业订购功能套餐
     * @return \json
     * @author: weili
     * @datetime: 2020/8/19 17:12
     */
    public function getPrivilagePackage()
    {
        $property_id = $this->adminUser['property_id'];
        if (!isset($property_id)) {
            return api_output(1002, [], "物业信息不存在");
        }
        $page = $this->request->param('page','0','intval');
        $limit = 10;
        if($page)
        {
            $page = ($page-1)*$limit;
        }
        $servicePackageOrder = new PackageOrderService();
        $where[] = ['property_id','=',$property_id];
        $where[] = ['status','=',1];
        $field = 'order_id,order_no,pay_time,package_period,package_title,package_try_end_time,package_end_time,order_money,pay_money,room_num,order_type,package_price,status';
        try {
            $list = $servicePackageOrder->getPackageOrderList($where, $field, $page, $limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }
    /**
     * Notes:物业购买的所有房间套餐
     * @return \json
     * @author: weili
     * @datetime: 2020/8/19 17:12
     */
    public function getRoomPackage()
    {
        $property_id = $this->adminUser['property_id'];
        if (!isset($property_id)) {
            return api_output(1002, [], "物业信息不存在");
        }
        $page = $this->request->param('page','0','intval');
        $limit = 10;
        if($page)
        {
            $page = ($page-1)*$limit;
        }
        $servicePackageRoomOrder = new PackageRoomOrderService();
        $where[] = ['status','=',1];
        $where[] = ['property_id','=',$property_id];
        $field = 'order_id,order_no,pay_time,package_period,num,room_title,package_end_time,order_money,pay_money,room_num,room_prcie,status';
        try {
            $list = $servicePackageRoomOrder->getPackageRoomOrderList($where,$field, $page, $limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

}