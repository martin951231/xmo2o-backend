<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsCarParkTools extends Model
{
    /**
     * 获取景区/场馆/课程停车场数
     * @param $where
     * @return int|mixed
     */
    public function get_car_park_num($where){
        $prefix = config('database.connections.mysql.prefix');
        $list = $this->alias('a')
            ->field('count(a.id) as num')
            ->join($prefix.'life_tools_car_park b','a.car_park_id = b.car_park_id and b.status = 1')
            ->where($where)
            ->group('tools_id')
            ->select();
        $car_num = $list->toArray()?$list->toArray()[0]['num']:0;
        return $car_num;
    }
}