<?php


namespace app\community\model\db;

use think\Model;

class HouseNewRepairDirectorScheduling extends Model
{
    public function getList($where=[],$field=true,$page=0,$limit=10,$order='id ASC')
    {
        if($page)
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        else
            $data = $this->where($where)->field($field)->order($order)->select();

        // print_r($this->getLastSql());exit;
        return $data;
    }

    /**
     * 查询一条数据
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:01
     */
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/8/16 9:52
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 删除信息
     * @author: zhubaodi
     * @date_time: 2021/8/16 9:52
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function del($where) {
        $set_info = $this->where($where)->delete();
        return $set_info;
    }


    /**
     * 添加信息
     * @author: zhubaodi
     * @date_time: 2021/8/16 9:52
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function addOne($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }







}