<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/12/4
 * Time: 14:24
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class HouseVillageThirdConfig extends Model
{
    /**
     * 获取小区三方对接配置项
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCloudIntercomConfig($where,$field){
        $data = $this->where($where)->field($field)->find();
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            return [];
        }
    }

    /**
     * 存储配置信息
     * @param $data
     * @param string $type
     * @param array $where
     * @return bool
     */
    public function saveCloudIntercomConfig($data,$type = 'add',$where = []){
        if($type == 'add'){
            $res = $this->insertGetId($data);
        }else{
            $res = $this->where($where)->save($data);
        }
        if($res){
            return true;
        }else{
            return false;
        }
    }
}