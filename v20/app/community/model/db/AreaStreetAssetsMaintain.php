<?php
/**
 * 维修相关
 * @author weili
 * @date 2020/11/21
 */

namespace app\community\model\db;

use think\Model;
class AreaStreetAssetsMaintain extends Model
{
    /**
     * Notes: 添加一条数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/11/21 18:50
     */
    public function addFind($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    /**
     * Notes: 获取多条数据
     * @param $where
     * @param bool $field
     * @param string $order
     * @param $page
     * @param $limit
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/11/21 18:50
     */
    public function getList($where,$field=true,$order='id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * Notes: 获取一条数据
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/11/23 9:23
     */
    public function getFind($where,$field=true,$order='id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }
    /**
     * Notes: 修改一条数据
     * @param $where
     * @param $data
     * @return AreaStreetAssetsMaintain
     * @author: weili
     * @datetime: 2020/11/23 9:24
     */
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * Notes: 获取数量
     * @author: weili
     * @datetime: 2020/11/23 13:50
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}