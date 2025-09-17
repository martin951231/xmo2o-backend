<?php
/**
 * 智慧停车场配置
 * @author weili
 * @datetime 2020/8/27
 */

namespace app\community\model\db;

use think\Model;
class HouseVillageParkShowscreenConfig extends Model
{
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/27 13:37
     */
    public function getFind($where,$field=true)
    {
         $info = $this->where($where)->field($field)->find();
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


    /**
     * 添加数据
     * @author weili
     * @datetime 2020/7/7 10:43
     * @param array $data
     * @return integer
     **/
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }
}