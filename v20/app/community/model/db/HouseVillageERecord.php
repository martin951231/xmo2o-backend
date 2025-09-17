<?php

namespace app\community\model\db;

use think\Model;

class HouseVillageERecord extends Model
{
    /**
     * 获取用户最后一次开电子发票的信息
     * @author lijie
     *@date_time 2020/07/06
     * @param $where
     * @param $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLastRecord($where,$order)
    {
        $res = $this->where($where)->order($order)->limit(1)->find();
        return $res;
    }

    /**
     * 插入电子发票记录
     * @author lijie
     * @date-time 2020/07/07
     * @param $data
     * @return int|string
     */
    public function addErecord($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * @根据pigcms_id 获取所有的电子发票开具记录
     * @author lije
     * @date_time 2020/07/07
     * @param $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllERecord($where,$field='*',$order='id DESC',$page=1,$limit=10)
    {
        $data = $this->where($where)->field($field)->order($order)->page($page,$limit)->select();
        return $data;
    }

    /**
     * 获取历史开票记录详情
     * @author lijie
     * @date_time 2020/07/10
     * @param $where
     * @param string $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detailInvoice($where,$field='*')
    {
        $res = $this->where($where)->field($field)->find();
        return $res;
    }

}