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

class HouseVillageAboutConfig extends Model
{
    /**
     * 获取小区相关配置信息
     * @param $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getVillageAboutConfig($where,$field = true){
        if(empty($where)){
            return [];
        }
        $configInfo = $this->where($where)->field($field)->find();
        if(!$configInfo->isEmpty()){
            return $configInfo->toArray();
        }else{
            return [];
        }
    }
}