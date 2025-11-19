<?php
/**
 * 街道导航
 * @author weili
 * @date 2020/9/9
 */

namespace app\community\model\db;

use think\Model;
class AreaStreetNav extends Model
{
    /**
     * Notes: 获取所有
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/9/9 18:09
     */
    public function getList($where,$field=true,$order='id desc',$page=0,$limit=0)
    {
        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->page($page,$limit);
        }
        $list = $sql->select();
        return $list;
    }

    /**
     * Notes:新增一条
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/9/9 18:14
     */
    public function insertFind($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * Notes: 获取详情
     * @param $where
     * @param string $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/9/9 18:15
     */
    public function getFind($where,$order='id desc')
    {
        $info = $this->where($where)->order($order)->find();
        return $info;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return StreetNav
     * @author: weili
     * @datetime: 2020/9/9 18:15
     */
    public function updateFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * Notes:获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/9/9 18:22
     */
    public function getCount($where=[])
    {
        $num = $this->where($where)->count();
        return $num;
    }

    /**
     * Notes: 删除数据
     * @param $where
     * @return bool
     * @throws \Exception
     * @author: weili
     * @datetime: 2020/9/10 14:13
     */
    public function del($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}