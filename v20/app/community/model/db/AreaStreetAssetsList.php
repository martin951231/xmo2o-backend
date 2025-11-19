<?php
/**
 * 资产数据
 */

namespace app\community\model\db;

use think\Model;
class AreaStreetAssetsList extends Model
{
    /**
     * Notes: 添加多条数据
     * @param $data
     * @return int
     * @author: weili
     * @datetime: 2020/11/20 16:32
     */
    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }

    /**
     * Notes: 一次修复多条
     * @param $data
     * @return \think\Collection
     * @throws \Exception
     * @author: weili
     * @datetime: 2020/11/21 13:59
     */
    public function editAll($data)
    {
        $res = $this->saveAll($data);
        return $res;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return AreaStreetAssetsList
     * @author: weili
     * @datetime: 2020/11/21 14:29
     */
    public function editFind($where,$data)
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
     * @datetime: 2020/11/20 16:47
     */
    public function getFind($where,$field=true,$order='id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    /**
     * Notes: 获取多条数据
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/11/20 18:15
     */
    public function getSelect($where,$field=true,$order='id desc',$page=0,$limit=20)
    {
        $sql=$this->where($where)->field($field)->order($order);
        if($page)
        {
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * Notes:获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/11/23 13:45
     */
    public function getCount($where){
        $count = $this->where($where)->count();
        return $count;
    }
}