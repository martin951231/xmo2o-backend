<?php
/**
 * 智慧停车场配置
 * @author:zhubaodi
 * @date_time: 2022/9/9 13:27
 */

namespace app\community\model\db;

use think\Model;
class HouseNewPileNews
    extends Model
{
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author:zhubaodi
     * @date_time: 2022/9/9 13:27
     */
    public function getFind($where,$field=true)
    {
         $info = $this->where($where)->field($field)->find();
         return $info;
    }

    /**
     * 数量统计
     * @author:zhubaodi
     * @date_time: 2022/9/9 13:29
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
    
    
    
    /**
     * 查询列表
     * @author:zhubaodi
     * @date_time: 2022/9/9 13:27
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
     * @date_time: 2022/9/9 13:27
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
     * @author:zhubaodi
     * @date_time: 2022/9/9 13:27
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

}