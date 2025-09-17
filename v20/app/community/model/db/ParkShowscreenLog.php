<?php
/**
 * 智慧停车场配置
 * @author weili
 * @datetime 2020/8/27
 */

namespace app\community\model\db;

use think\Model;
class ParkShowscreenLog extends Model
{
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/27 13:37
     */
    public function getFind($where,$field=true,$order='id asc')
    {
         $info = $this->where($where)->field($field)->order($order)->find();
         return $info;
    }

    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2022/1/12
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
     * @author: liukezhu
     * @date : 2022/1/12
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
     * @date_time: 2021/12/7 15:13
     * @param array $where 改写内容的条件
     * @return bool
     */
    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }


    /**
     * Notes: 获取数量
     * @param $where
     * @author:zhubaodi
     * @datetime: 2022/7/26 09:48
     * @return integer
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}