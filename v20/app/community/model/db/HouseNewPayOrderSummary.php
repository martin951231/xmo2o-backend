<?php


namespace app\community\model\db;

use think\Model;

class HouseNewPayOrderSummary extends Model
{
    /**
     * 添加总订单
     * @author lijie
     * @date_time 2021/06/15
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * 修改总订单
     * @author lijie
     * @date_time 2021/06/15
     * @param $where
     * @param array $data
     * @return bool
     */
    public function saveOne($where,$data=[])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 获取总订单详情
     * @author lijie
     * @date_time 2021/06/24
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where=[],$field=true)
    {
        $data = $this->field($field)->where($where)->find();
        return $data;
    }


    /**
     * 查询数据详情（加数据锁）
     * @author:zhubaodi
     * @date_time: 2022/10/25 16:33
     */
    public function get_one($where=[],$field=true)
    {
        $data = $this->field($field)->where($where)->lock(true)->find();
        return $data;
    }

    /**
     * 订单列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author zhubaodi
     * @date_time 2021/06/29
     */
    public function getList($where = [],$where1 = '', $field = true, $page = 0, $limit = 10, $order = 'summary_id DESC')
    {
        if ($page){
            $data = $this->where($where)->whereRaw($where1)->field($field)->page($page, $limit)->order($order)->select();

        }
        else{
            $data = $this->where($where)->whereRaw($where1)->field($field)->order($order)->select();
        }

        return $data;
    }


    /**
     * 订单列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author zhubaodi
     * @date_time 2021/06/29
     */
    public function getLists($where = [], $field = true, $page = 0, $limit = 10, $order = 'summary_id DESC')
    {
        if ($page)
            $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select();
        else
            $data = $this->where($where)->field($field)->order($order)->select();

        return $data;
    }

    /**
     * 订单统计
     * @author: liukezhu
     * @date : 2021/7/17
     * @param $where
     * @param array $whereAnd
     * @param string $field
     * @return mixed
     */
    public function sumMoney($where,$field='pay_money')
    {
        $sumMoney = $this->where($where)->sum($field);
        return $sumMoney;
    }
    /**
     * 订单统计
     * @author: liukezhu
     * @date : 2021/7/17
     * @param $where
     * @param array $whereAnd
     * @param string $field
     * @return mixed
     */
    public function sumRefundMoney($where,$field='refund_money')
    {
        $sumMoney = $this->where($where)->sum($field);
        return $sumMoney;
    }

    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }

}