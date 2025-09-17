<?php
/**
 * 收银台总订单记录表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/27 16:57
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class HouseVillagePayCashierOrder extends Model{

    /**
     * 查询对应条件收银台总订单
     * @author: wanziyang
     * @date_time:  2020/4/27 17:00
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 添加收银台总订单
     * @author:wanziyang
     * @date_time:  2020/4/27 17:00
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function add_order($data) {
        $cashier_id = $this->insertGetId($data);
        return $cashier_id;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/27 19:51
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取订单列表
     * @author: wanziyang
     * @date_time: 2020/4/28 20:15
     * @param array $where
     * @param string $field
     * @param string $page
     * @param string $order
     * @param int $page_size
     * @return mixed
     */
    public function get_limit_list($where,$field='b.name,b.address,b.phone,a.*',$page='',$order='a.cashier_id DESC',$page_size=10) {
        $sql = $this->alias('a')
            ->leftjoin('house_village_user_bind b', 'b.pigcms_id=a.pigcms_id')
            ->leftjoin('house_village_pay_type p', 'p.id=a.pay_type')
            ->where($where)
            ->field($field)
            ->order($order);
        if ($page) {
            $sql->page($page, $page_size);
        }
        $list = $sql->select();
        return $list;
    }
}