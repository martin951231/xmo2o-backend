<?php
/**
 * 车位信息
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 15:27
 */

namespace app\community\model\db;

use app\community\model\service\Car\CarHandleGetInfoService;
use think\Model;
use think\facade\Db;

class HouseVillageParkingPosition extends Model{

    /**
     * 查询对应条件下车位数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where
     * @return int
     */
    public function get_village_park_position_num($where) {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取车位列表
     * @author lijie
     * @date_time 2020/07/16 16:05
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getLists($where,$field=true,$page=1,$limit=15,$order='pp.position_id DESC',$group='')
    {
        $whereRaw='';
        if(isset($where['_string'])){
            $whereRaw=$where['_string'];
            unset($where['_string']);
        }

        $sql = $this->alias('pp')
            ->leftJoin('house_village_parking_garage pg','pp.garage_id = pg.garage_id')
            ->where($where)
            ->field($field);
        if($whereRaw){
            $sql = $sql->whereRaw($whereRaw);
        }
        if($group){
            $data= $sql->group($group);
        }
        if(empty($page)){
            $data = $sql->order($order)
                ->select();
        }else{
            $data = $sql->page($page,$limit)
                ->order($order)
                ->select();
        }
        return $data;
    }


    /**
     * 获取车位列表
     * @author lijie
     * @date_time 2020/07/16 16:05
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getListss($where,$field=true,$page=1,$limit=15,$order='pp.position_id DESC',$group='')
    {

        $sql = $this->alias('pp')
            ->leftJoin('house_village_parking_garage pg','pp.garage_id = pg.garage_id')
            ->leftJoin('house_village_parking_car c','c.car_position_id = pp.position_id AND c.village_id = pp.village_id')
            ->where($where)
            ->field($field);
        if($group){
            $data= $sql->group($group);
        }
        if(empty($page)){
            $data = $sql->order($order)
                ->select();
        }else{
            $data = $sql->page($page,$limit)
                ->order($order)
                ->select();
        }
        return $data;
    }

    public function getPositionListss($where,$field=true,$page=1,$limit=15,$order='pp.position_id DESC',$group='')
    {
        $data = $this->alias('pp')
            ->leftJoin('house_village_parking_garage pg','pp.garage_id = pg.garage_id')
            ->where($where)
            ->field($field);
        if($group){
            $data= $data->group($group);
        }
        if(empty($page)){
            $data = $data->order($order)
                ->select();
        }else{
            $data = $data->page($page,$limit)
                ->order($order)
                ->select();
        }
        return $data;
    }
    
    /**
     * 统计车位列表
     * @author zhubaodi
     * @date_time 2021/06/18 16:05
     * @param array $where
     * @return int
     */
    public function getCountss($where,$count='')
    {
        $sql = $this->alias('pp')
            ->leftJoin('house_village_parking_garage pg','pp.garage_id = pg.garage_id')
            ->where($where);
        if($count){
            $data=$sql->count($count);
        }else{
            $data=$sql->count();
        }
        return $data;
    }
    


    /**
     * 统计车位列表
     * @author zhubaodi
     * @date_time 2021/06/18 16:05
     * @param array $where
     * @return int
     */
    public function getCount($where,$count='')
    {
        $sql = $this->alias('pp')
            ->leftJoin('house_village_parking_garage pg','pp.garage_id = pg.garage_id')
            ->leftJoin('house_village_parking_car c','c.car_position_id = pp.position_id')
            ->where($where);
        if($count){
            $data=$sql->count($count);
        }else{
            $data=$sql->count();
        }
        return $data;
    }

    
    /**
     * 统计车位列表
     * @author zhubaodi
     * @date_time 2021/06/18 16:05
     * @param array $where
     * @return int
     */
    public function getCounts($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取车位详情
     * @author lijie
     * @date_time 2020/07/16 16:07
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getOne($where,$field=true)
    {
        $data = $this->alias('pp')
            ->leftJoin('house_village_parking_garage pg','pp.garage_id = pg.garage_id')
            ->leftJoin('house_village_bind_position bp','bp.position_id = pp.position_id')
            ->leftJoin('house_village_user_bind ub','ub.pigcms_id = bp.user_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

    /**
     * 添加车位
     * @author lijie
     * @date_time 2020/07/16 16:08
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    /**
     * 编辑车位
     * @author lijie
     * @date_time 2020/07/16 16:11
     * @param $where
     * @param $data
     * @return mixed
     */
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        if (!$village_id) {
            $info = $this->where($where)->field('village_id')->find();
            $village_id = isset($info['village_id']) ? intval($info['village_id']) : 0;
        }
        if ($village_id > 0) {
            (new CarHandleGetInfoService())->clearCarPositionInfo($village_id);
        }
        return $res;
    }

    /**
     * 删除车位
     * @author lijie
     * @date_time 2020/07/17 9:34
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 获取已绑定车位
     * @param $where
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/3 20:10
     */
    public function getBindPosition($where)
    {
        $count = $this->alias('p')->Join('house_village_bind_position b','p.position_id=b.position_id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * Notes: 获取已绑定车辆的车位
     * @param $where
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/4 10:07
     */
    public function getBindCar($where)
    {
        $count = $this->alias('p')->leftJoin('house_village_parking_car c','p.position_id=c.car_position_id')->where($where)->count();
        return $count;
    }
    public function getBindCarCount($where)
    {
        $count = $this->alias('p')->where($where)->count();
        return $count;
    }
    public function getUserPositionBindList($bind_id, $where=[], $field=true) {
        $where['b.user_id'] = $bind_id;
        $list = $this->alias('a')
            ->leftJoin('house_village_bind_position b', 'b.position_id=a.position_id')
            ->leftJoin('house_village_parking_garage c', 'a.garage_id=c.garage_id')
            ->where($where)
            ->field($field)
            ->select();
        return $list;
    }
    //获取入场绑定车位的车辆数据
    public function getInPackCount($where)
    {
        $count = $this->alias('p')
            ->leftJoin('house_village_parking_car c','p.position_id=c.car_position_id')
            ->leftJoin('in_park i','c.car_number=i.car_number')
            ->where($where)
            ->count();
        return $count;
    }


    public function getFind($where,$field =true,$order='position_id DESC'){
        $info = $this->field($field)->where($where)->order($order)->find();
        return $info;
    }

    public function getFindOne($where = [], $field = true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 获取车位列表
     * @author lijie
     * @date_time 2020/07/16 16:05
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getList($where,$field=true,$page=1,$limit=15,$order='position_id DESC')
    {
        $whereRaw='';
        if(isset($where['_string'])){
            $whereRaw=$where['_string'];
            unset($where['_string']);
        }
        $sql = $this->where($where)->field($field);
        if($whereRaw){
            $sql= $sql->whereRaw($whereRaw);
        }
        if(empty($page)){
            $data = $sql->order($order)
                ->select();
        }else{
            $data = $sql->page($page,$limit)
                ->order($order)
                ->select();
        }
        return $data;
    }

    /**
     * 查询单个字段列表
     * @author:zhubaodi
     * @date_time: 2022/7/29 9:24
     */
    public function getColumn($where,$field, $key = ''){
        $info = $this->where($where)->column($field, $key);
        return $info;
    }

}