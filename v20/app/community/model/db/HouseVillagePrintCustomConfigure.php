<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/6/28 13:21
 */
namespace app\community\model\db;

use think\Model;

class HouseVillagePrintCustomConfigure extends Model
{
    /**
     * 获取一条数据
     * User: zhanghan
     * Date: 2022/1/8
     * Time: 9:06
     * @param array $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where = [], $field = true){
        if(empty($where)){
            return [];
        }
        $data = $this->where($where)->field($field)->find();
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            return [];
        }
    }

    /**
     * 获取数据列表
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 13:50
     * @param array $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where = [], $field = true){
        if(empty($where)){
            return [];
        }
        $data = $this->where($where)->field($field)->order('sort desc,configure_id asc')->select();
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            return [];
        }
    }
}
