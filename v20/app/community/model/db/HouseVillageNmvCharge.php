<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/30
 * Time: 15:14
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class HouseVillageNmvCharge extends Model
{
    /**
     * 获取非机动车收费规则
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChargeList($where,$field,$page = 0,$limit = 10){
        $res = $this->where($where)->field($field);
        if($page > 0){
            $data = $res->page($page,$limit)->select();
        }else{
            $data = $res->select();
        }
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            return [];
        }
    }

    /**
     * 获取机动车收费规则总条数
     * @param $where
     * @return int
     */
    public function getChargeCount($where){
        return $this->where($where)->count();
    }

    /**
     * 获取非机动车收费规则详情
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChargeInfo($where,$field){
        $res = $this->where($where)->field($field)->find();
        if($res && !$res->isEmpty()){
            return $res->toArray();
        }else{
            return [];
        }
    }

    /**
     * 插入数据
     * @param $data
     * @return int|string
     */
    public function addCharge($data){
        return $this->insertGetId($data);
    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     * @return array|bool
     */
    public function updateCharge($where,$data){
        if(empty($where) || empty($data)){
            return [];
        }
        return $this->where($where)->save($data);
    }
}