<?php
/**
 * 热词
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;

class HouseHotwordMaterialCategory extends Model{
    protected $pk = 'cate_id';
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function getAllCategory($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('cate_id desc')->select();
        return $data;
    }

    public function editData($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
    //添加数据
    public function addData($addData=array()){
        if(empty($addData)){
            return false;
        }
        $idd=$this->insertGetId($addData);
        return $idd;
    }
    
    public function getCategoryLists($where = [] ,$field = true,$order='cate_id desc',$page=0,$limit=20)
    {
        $sql = $this->field($field)->where($where)->order($order);
        if($page)
        {
            $sql->page($page,$limit);
        }
        $result = $sql->select();
        //echo $sql->getLastSql();
        return $result;
    }

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    //更新字段值 加
    public function updateFieldPlusNum($whereArr=array(),$fieldname='',$fieldv=1)
    {
        if(empty($whereArr) || empty($fieldname)){
            return false;
        }
        $ret = $this->where($whereArr)->inc($fieldname,$fieldv)->update();
        return $ret;
    }

    //更新字段数值 减
    public function updateFieldMinusNum($whereArr=array(),$fieldname='',$fieldv=1)
    {
        if(empty($whereArr) || empty($fieldname)){
            return false;
        }
        $ret = $this->where($whereArr)->dec($fieldname,$fieldv)->update();
        return $ret;
    }
}
