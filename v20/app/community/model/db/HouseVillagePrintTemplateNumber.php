<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/6/28 10:02
 */
namespace app\community\model\db;

use think\Model;

class HouseVillagePrintTemplateNumber extends Model
{
    /**
     * 获取单条数据
     * User: zhanghan
     * Date: 2022/2/7
     * Time: 16:31
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where = [],$field = true,$order='id DESC'){
        $sql = $this;
        if(!empty($where)){
            $sql = $sql->where($where);
        }
        $data = $sql->field($field)->order($order)->find();
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            return [];
        }
    }

    /**
     * 插入一条数据
     * User: zhanghan
     * Date: 2022/2/7
     * Time: 17:02
     * @param $data
     * @return int|string
     */
    public function addOne($data){
        return $this->insert($data);
    }

    /**
     * 获取列表
     * User: zhanghan
     * Date: 2022/2/15
     * Time: 14:45
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where = [],$field = true,$order='id DESC'){
        $sql = $this;
        if(!empty($where)){
            $sql = $sql->where($where);
        }
        $data = $sql->field($field)->order($order)->select();
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            return [];
        }
    }
}
