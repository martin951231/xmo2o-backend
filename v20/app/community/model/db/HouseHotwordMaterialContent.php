<?php
/**
 * 热词
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;

class HouseHotwordMaterialContent extends Model{
    protected $pk = 'material_id';
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function getAllMaterial($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('material_id desc')->select();
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
    
    public function getMaterialLists($where = [] ,$field = true,$order='material_id desc',$page=0,$limit=20)
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
    
    
}
