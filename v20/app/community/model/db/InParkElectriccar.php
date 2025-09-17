<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/3/14 16:19
 */

namespace app\community\model\db;

use think\Model;

class InParkElectriccar extends Model{
    /**
     * 插入数据
     * @author: liukezhu
     * @date : 2022/1/13
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     *更新数据
     * @author: liukezhu
     * @date : 2022/1/13
     * @param $where
     * @param $data
     * @return mixed
     */
    public function saveOne($where,$data)
    {
        $data = $this->where($where)->save($data);
        return $data;
    }

    /**
     * 查询单条
     * @author: liukezhu
     * @date : 2022/1/13
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }


    /**
     * 查询总数
     * @author: liukezhu
     * @date : 2022/1/19
     * @param $where
     * @return mixed
     */
    public function getCount($where){
        $data = $this->where($where)->count();
        return $data;
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
     * 查询总数
     * @author: liukezhu
     * @date : 2022/1/19
     * @param $where
     * @return mixed
     */
    public function getCounts($where){
        $data = $this->alias('i')
            ->leftJoin('house_village_parking_car c','i.village_id=c.village_id AND i.car_stop_num=c.car_stop_num')
            ->where($where)->count();
        return $data;
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
    public function getList($where,$field=true,$page=0,$limit=15,$order='i.id DESC')
    {
        $data =$this->alias('i')
            ->leftJoin('house_village_parking_car c','i.village_id=c.village_id AND i.car_stop_num=c.car_stop_num');
        if ($page){
            $data = $data->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $data = $data->where($where)->field($field)->order($order)->select();
        }
        return $data;
    }
}