<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/7/22 15:55
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePileRefundOrder extends Model{
    /**
     * 添加退款信息
     * @author:zhubaodi
     * @date_time: 2021/7/22 15:55
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function add_order($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }

    /**
     * 获取统计
     * @author: zhubaodi
     * @date_time: 2021/7/22 15:55
     * @param array $where 查询条件
     * @return \think\Collection
     */
    public function get_count($where) {
        $count = $this->where($where)->count();
        return $count;
    }


    /**
     * 查询对应条件退款订单
     * @author: zhubaodi
     * @date_time: 2021/7/22 15:55
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }


    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/7/22 15:55
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取订单列表
     * @author:zhubaodi
     * @date_time: 2021/7/22 15:55
     */
    public function getList($where,$field=true,$page=0,$limit=20,$order='id DESC',$type=0) {
        $list = $this->field($field)->where($where);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }
}
