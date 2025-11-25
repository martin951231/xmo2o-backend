<?php


namespace app\community\model\db;
use think\Model;

class HouseNewRepairSubject extends Model
{
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }


    public function getCount($where) {
        $list = $this->where($where)->count();
        return $list;
    }

    public function getList($where=[],$field=true,$page=0,$limit=10,$order='id DESC')
    {
        if($page)
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        else
            $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }
    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/8/13 9:52
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    /**
     * 添加添加购买的月卡信息
     * @author: zhubaodi
     * @date_time: 2021/8/13 9:52
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function addOne($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }

    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }
}