<?php
/**
 * Class PackageOrderModel
 * 套餐检测
 */

class PackageOrderModel extends Model
{
    public function judgeOrderEmploy($property_id)
    {
        $dbPackageOrder = D('Package_order');
        $where['property_id'] = $property_id;
//        $where[] = ['status','in',[0,1]];
        $where['status'] = 1;
        $field = 'order_id,order_no,package_try_end_time,package_end_time,order_type,pay_type';
        $packageOrderInfo = $dbPackageOrder->where($where)->field($field)->order('order_id desc')->find();
        if(!$packageOrderInfo){
            unset($where['status']);
            $where['status'] = 0;
            $where['pay_type'] = 4;
            $packageOrderInfo = $dbPackageOrder->where($where)->field($field)->order('order_id desc')->find();
        }
        if($packageOrderInfo){

            // pay_type=4 表示试用
            if($packageOrderInfo['pay_type'] == 4 && ($packageOrderInfo['package_try_end_time'] < time())){
//                $return['msg'] = '试用套餐已过期';
                $return['msg'] = '该物业已到期';
                $return['code'] = '2';
            }else if( $packageOrderInfo['package_end_time'] && ($packageOrderInfo['order_type'] == 3 || $packageOrderInfo['package_end_time'] < time()) ){//order_type=3表示过期
//                $return['msg'] = '使用套餐已过期';
                $return['msg'] = '该物业已到期';
                $return['code'] = '3';
            }else{
                $return['msg'] = '套餐正常试用';
                $return['code'] = '1';
            }
        }
        $return['data'] = $packageOrderInfo;
        return $return;
    }
}