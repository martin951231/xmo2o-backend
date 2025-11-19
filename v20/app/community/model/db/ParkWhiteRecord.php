<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/6/25 9:36
 */

namespace app\community\model\db;

use think\Model;
class ParkWhiteRecord extends Model{

    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author:zhubaodi
     * @date_time: 2022/6/25 9:37
     */
    public function getFind($where,$field=true,$order='id DESC')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }
    public function get_count($where) {
        $count = $this->where($where)->count();
        return $count;
    }
    /**
     * 查询列表
     * @author:zhubaodi
     * @date_time: 2022/6/25 9:37
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function get_list($where,$field=true,$page=0,$limit=15,$order='id DESC')
    {
        if ($page){
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $data = $this->where($where)->field($field)->order($order)->select();
        }
        return $data;
    }

    /**
     *编辑数据
     * @author:zhubaodi
     * @date_time: 2022/6/25 9:37
     * @param $where
     * @param $save
     * @return mixed
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    public function add($data){
        $result = $this->insertGetId($data);
        return $result;
    }

    /**
     * 删除信息
     * @author:zhubaodi
     * @date_time: 2022/6/25 9:37
     * @param array $where 改写内容的条件
     * @return bool
     */
    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }

    public function addAll($data) {
        return $this->insertAll($data);
    }

}