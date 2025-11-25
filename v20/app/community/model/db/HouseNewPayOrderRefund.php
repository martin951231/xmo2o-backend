<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/6/25 15:20
 */

namespace app\community\model\db;

use think\Model;

class HouseNewPayOrderRefund extends Model
{

    /**
     * 修改退款订单
     * @author:zhubaodi
     * @date_time: 2021/6/25 14:45
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveOne($where=[],$data=[])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 添加退款订单
     * @author:zhubaodi
     * @date_time: 2021/6/25 14:45
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        $id = $this->insertGetId($data);
        return $id;
    }


    /**
     * 退款订单详情
     * @author:zhubaodi
     * @date_time: 2021/6/25 14:45
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    //累加字段值
    public function sumFieldv($where=[],$field='')
    {
        $sumV = $this->where($where)->sum($field);
        return (!empty($sumV) ? $sumV:0);
    }

    /**
     * 退款订单列表
     * @author:zhubaodi
     * @date_time: 2021/6/25 14:45
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where=[],$field=true,$page=0,$limit=10,$order='id DESC')
    {
        if($page)
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        else
            $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }

    /**
     * 统计数量
     * @author:zhubaodi
     * @date_time: 2021/6/25 14:45
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}