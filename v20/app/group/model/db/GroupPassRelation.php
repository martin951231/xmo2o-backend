<?php

/**
 * 团购卷核销
 * Created by PhpStorm.
 * Author: 钱大双
 * Date Time: 2020年12月25日15:30:17
 */

namespace app\group\model\db;

use think\Model;

class GroupPassRelation extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getStatusMsg($status,$deadline_time,$effective_type,$pay_time)
    {
        $msg = '';
        if($status == 2){
            $msg = '已退款';
        }elseif($status == 1){
            $msg = '已核销';
        }elseif($status == 0){
            $msg = '待核销';
            if(($effective_type == 0 && $deadline_time < time()) || ($effective_type == 1 && $deadline_time*24*60*60+$pay_time < time())){
                $msg = '已过期';
            }
        }
        return $msg;
    }

    public function getVerifyTypeMsg($verify_type)
    {
        $msg = '';
        if($verify_type == 1){
            $msg = '移动端核销';
        }elseif($verify_type == 2){
            $msg = 'pc端核销';
        }
        return $msg;
    }
    
    public function getPassNum($order_id,$status=0){
        $count = $this->where(array('order_id'=>$order_id,'status'=>$status))->count();
        return $count;
    }
    /**
     * 查询核销记录
     */
    public function getGroupPassList($where,$field,$order=[],$pageSize=0)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $list = $this->alias('gp')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.'group_order o','o.order_id = gp.order_id')
            ->leftJoin($prefix.'group g','g.group_id=o.group_id')
            ->leftJoin($prefix.'user u','u.uid = o.uid')
            ->order($order)
            ->paginate($pageSize)
            ->toArray();
        return $list;

    }

}