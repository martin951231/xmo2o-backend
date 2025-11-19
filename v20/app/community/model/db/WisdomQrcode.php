<?php


namespace app\community\model\db;

use think\Model;
class WisdomQrcode extends Model
{
    /**
     * Notes: 获取数量
     * @param $where
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/3 13:57
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes:获取数据
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/11/3 14:01
     */
    public function getList($where,$field=true,$order='id desc',$page=0,$limit=0)
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
     * @param $field
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/11/3 15:29
     */
    public function getFind($where,$field=true,$alias='')
    {
        if($alias){
            $data = $this->alias('q')->where($where)->field($field)->find();
        }else{
            $data = $this->where($where)->field($field)->find();
        }
        return $data;
    }
    public function addOneData($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }
    public function saveOneData($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }
}