<?php


namespace app\community\model\db;

use think\Model;
class AreaStreetAssetsRecord extends Model
{
    /**
     * Notes: 新增数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/11/21 13:05
     */
    public function addFind($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    /**
     * Notes: 一次插入多条
     * @param $data
     * @return int
     * @author: weili
     * @datetime: 2020/11/21 13:49
     */
    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return AreaStreetAssetsRecord
     * @author: weili
     * @datetime: 2020/11/21 16:47
     */
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
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
     * @datetime: 2020/11/21 13:07
     */
    public function getList($where,$field=true,$order='id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
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