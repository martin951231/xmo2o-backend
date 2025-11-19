<?php
/**
 * 小区缴费订单记录表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 15:14
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillagePayOrder extends Model{

    /**
     * 查询对应条件缴费订单
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 获取小区缴费信息
     * @author:wanziyang
     * @date_time: 2020/4/26 14:04
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @return array|null|\think\Model
     */
    public function get_order_limit_list($where,$page=0,$field ='a.*',$order='a.pay_time DESC,a.order_id DESC, a.paid ASC',$limit = 10) {
        $db_count = $this->alias('a')
            ->leftJoin('house_village_user_bind hvu', 'hvu.pigcms_id=a.bind_id AND hvu.village_id=a.village_id')
            ->where($where)
            ->count();
        $db_list = $this->alias('a')
            ->leftJoin('house_village_user_bind hvu', 'hvu.pigcms_id=a.bind_id AND hvu.village_id=a.village_id')
            ->field($field)
            ->order($order);
        // 是否有下一页
        $next_page = false;
        if ($page) {
            $db_list->page($page,$limit);
        }
        $list = $db_list->where($where)->select();
        if($page && count($list) == $limit){
            $next_page = true;
        }
        $data = [
            'count' => $db_count,
            'next_page' => $next_page,
            'list' => $list
        ];
        return $data;
    }

    /**
     * 获取小区缴费信息
     * @author:wanziyang
     * @date_time: 2020/4/26 14:04
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @return array|null|\think\Model
     */
    public function get_order_limit_list1($where,$page=0,$field ='a.*',$order='a.pay_time DESC,a.order_id DESC, a.paid ASC') {
        $db_list = $this->alias('a')
            ->Join('house_village_user_bind hvu', 'hvu.pigcms_id=a.bind_id AND hvu.village_id=a.village_id')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list= $db_list->page($page,10);
        }
        $list = $db_list->where($where)->select();

        return $list;
    }

    /**
     * 添加小区缴费信息
     * @author:wanziyang
     * @date_time: 2020/4/26 20:55
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function add_order($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/27 17:27
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取列表
     * @author: wanziyang
     * @date_time: 2020/4/24 19:56
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param bool|array $order 排序
     * @return array|null|Model
     */
    public function get_list($where,$field=true, $order = 'pay_time DESC, time DESC, order_id DESC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    /**
     * 获取条件下订单数量
     * @author: wanziyang
     * @date_time: 2020/4/29 10:46
     * @param array $where 查询条件
     * @return \think\Collection
     */
    public function get_count($where) {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 删除
     * @author: wanziyang
     * @date_time: 2020/4/29 17:17
     * @param array $where 删除数据条件
     * @return array|null|Model
     */
    public function del_one($where) {
        $del = $this->where($where)->delete();
        return $del;
    }

    /**
     * Notes: 计算金额
     * @param $where
     * @param $whereAnd
     * @param $field
     * @return float
     * @author: weili
     * @datetime: 2020/8/3 21:31
     */
    public function sumMoney($where,$whereAnd = array(),$field='money')
    {
        $sumMoney = $this->where($where)->where($whereAnd)->sum($field);
        return $sumMoney;
    }


    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
}