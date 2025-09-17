<?php
/**
 * 党员相关信息
 * @author weili
 * @date 2020/9/17
 */

namespace app\community\model\db;

use think\Model;
class StreetPartyBindUser extends  Model
{
    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return StreetPartyBindUser
     * @author: weili
     * @datetime: 2020/9/17 15:08
     */
    public function edit($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * Notes: 获取一条数据
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/9/17 15:33
     */
    public function getFind($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes: 添加一条数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/9/17 15:33
     */
    public function addFind($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    public function getBindPartyBranch($where,$field)
    {
        $data = $this->alias('a')
            ->leftJoin('area_street_party_branch b', 'b.id=a.party_id')
            ->field($field)
            ->where($where)
            ->find();
        return $data;
    }
}