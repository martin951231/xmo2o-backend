<?php
/**
 * 资产相关
 * @author weili
 * @date 2020/11/20
 */

namespace app\community\model\db;

use think\Model;
class AreaStreetAssets extends Model
{
    /**
     * Notes: 获取多条
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/11/20 13:43
     */
    public function getSelect($where,$field=true,$order='assets_id asc',$page=0,$limit=20)
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
     * Notes: 获取数据
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/11/20 14:41
     */
    public function getFind($where,$field=true,$order='assets_id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/11/20 14:41
     */
    public function addFind($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * Notes: 编辑数据
     * @param $where
     * @param $data
     * @return AreaStreetAssets
     * @author: weili
     * @datetime: 2020/11/20 14:42
     */
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
}