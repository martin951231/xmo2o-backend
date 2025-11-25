<?php
/**
 * 热词
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;

class HouseHotwordUrllist extends Model{
    protected $pk = 'id';
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function getAll($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('xsort asc')->select();
        return $data;
    }

    public function editData($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    public function delData($where)
    {
        $res = $this->where($where)->delete();
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

    public function getList($where = [],$whereRaw='', $field = true,$order=true,$page=0,$limit=0)
    {
        if ($whereRaw) {
            $sql = $this->field($field)->whereRaw($whereRaw)->order($order);
        } else {
            $sql = $this->field($field)->where($where)->order($order);
        }
        if($limit)
        {
            $sql->limit($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }
    
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
    
}
