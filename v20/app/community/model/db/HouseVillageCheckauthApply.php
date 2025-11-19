<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/12 19:24
 */
namespace app\community\model\db;

use think\Model;
class HouseVillageCheckauthApply extends Model
{
    public function getOne($where,$field ='*',$orderBy='id desc'){
        $info = $this->field($field)->where($where)->order($orderBy)->find();
        if (!$info || $info->isEmpty()) {
            $info = [];
        }else{
            $info=$info->toArray();
        }
        return $info;
    }
    //添加数据
    public function  addApply($datas=array()){
        if(empty($datas)){
            return false;
        }
        $idd=$this->insertGetId($datas);
        return $idd;
    }

    //更新数据
    public function updateApply($where=array(),$updateData=array()){
        if(empty($where) || empty($updateData)){
            return false;
        }
        $ret=$this->where($where)->update($updateData);
        return $ret;
    }

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
    public function getListData($where, $field = '*', $page = 1, $page_size = 10, $orderby = 'id DESC')
    {
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }
    public function getList($where,$field)
    {
        $list = $this->where($where)
            ->field($field)
            ->select()
            ->toArray();
        return $list;
    }
}