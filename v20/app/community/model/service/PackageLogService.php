<?php
/**
 * 套餐日志
 */

namespace app\community\model\service;

use app\community\model\db\PackageLog;
class PackageLogService
{
    /**
     * Notes: 操作日志
     * @param $bind_type
     * @param $package_id
     * @param $operate_type
     * @param $uid
     * @param $property_id
     * @param $order_on
     * @param $log_type
     * @param $log_msg
     * @return int|string
     * @author: weili
     * @datetime: 2020/8/15 18:11
     */
    public function addLog($log_msg,$package_id,$property_id,$order_on,$uid,$bind_type,$operate_type,$log_type=1)
    {
        $dbPackageLog = new PackageLog();
        $log_data = [
            'bind_type'=>$bind_type,
            'bind_id'=>$package_id,
            'operate_type'=>$operate_type,
            'uid'=>$uid,
            'property_id'=>$property_id,
            'order_no'=>$order_on,
            'log_type'=>$log_type,
            'describe'=>$log_msg,
            'create_time'=>time(),
        ];
        $res =  $dbPackageLog->createLog($log_data);
        return $res;
    }
}