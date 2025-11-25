<?php
/**
 * 广告相关
 * @author weili
 * @date 2020/09/07
 */

namespace app\community\model\db;

use think\Model;
class HouseAdver extends Model
{
    /**
     * Notes: 获取一条
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/7 17:20
     */
    public function getFind($where=[],$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes:
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/9/7 17:38
     */
    public function getList($where=[],$field=true,$order='id desc',$page=0,$limit=0)
    {
//        $list = $this->where($where)->field($field)->order($order)->select();
//        return $list;
        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->limit($page,$limit);
        }
        $list = $sql->select();
        return $list;
    }

    /**
     * Notes: 添加数据获取id
     * @param $where
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/9/8 14:57
     */
    public function addFind($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return HouseAdver
     * @author: weili
     * @datetime: 2020/9/8 14:59
     */
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * Notes: 删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @author: weili
     * @datetime: 2020/9/8 15:24
     */
    public function del($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/9/10 13:12
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}