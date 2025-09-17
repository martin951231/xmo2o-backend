<?php
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseServiceCategory extends Model{
    /**
     * Notes:获取全部
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     */
    public function getList($where,$field=true,$order='id desc',$page=0,$limit=10)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
    /**
     * Notes:获取一条
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     */
    public function getFind($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes:添加一条数据
     * @param $data
     * @return int|string
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
     * @return VillageQywxEngineContent
     */
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
    public function getSum($where,$column)
    {
        $sum = $this->where($where)->sum($column);
        return $sum;
    }
}