<?php
/**
 * 固定资产分类
 * @author weili
 * @date 2020/11/20
 */

namespace app\community\model\db;

use think\Model;
class AreaStreetAssetsCategory extends Model
{
    /**
     * Notes: 获取多条数据
     * @param $where
     * @param $field
     * @param $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/11/20 9:33
     */
    public function getSelect($where,$field=true,$order='cat_id asc',$page=0,$limit=20)
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
     * @datetime: 2020/11/20 9:37
     */
    public function getFind($where,$field=true,$order='cat_id asc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/11/20 9:37
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/11/20 11:09
     */
    public function addFind($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return AreaStreetAssetsCategory
     * @author: weili
     * @datetime: 2020/11/20 11:19
     */
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
}