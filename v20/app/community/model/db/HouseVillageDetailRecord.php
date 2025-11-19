<?php


namespace app\community\model\db;
use think\Model;

class HouseVillageDetailRecord extends Model
{
    /**
     * 插入电子发票详细记录
     * @author lijie
     * @date-time 2020/07/07
     * @param $data
     * @return int|string
     */
    public function addEDetailRecord($data)
    {
        $id = $this->insertAll($data);
        return $id;
    }

    /**
     * 查询发票详情
     * @author 朱宝娣
     * @date-time 2021/06/24
     * @param $where
     * @param $field
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function getOneOrder($where,$field =true,$order='print_num desc'){
        $info = $this->field($field)->where($where)->order($order)->find();
        if($info && !$info->isEmpty()){
            $info=$info->toArray();
        }else{
            $info=array();
        }
        return $info;
    }
    /**
     * 获取列表
     * User: zhanghan
     * Date: 2022/2/15
     * Time: 11:36
     * @param $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field =true){
        if(empty($where)){
            return [];
        }
        $info = $this->field($field)->where($where)->select();
        if($info && !$info->isEmpty()){
            return $info->toArray();
        }else{
            return [];
        }
    }
    public function incUpdate($where,$field ='',$step=1){
        if(empty($where) || empty($field)){
            return false;
        }
        $info = $this->where($where)->inc($field, $step)->update();
        return $info;
    }

}