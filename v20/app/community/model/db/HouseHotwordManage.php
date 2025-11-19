<?php
/**
 * 热词
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;

class HouseHotwordManage extends Model{
    protected $pk = 'id';
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function getAll($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('id desc')->select();
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
    
    public function getHotwordLists($where = [] ,$field = true,$order='',$page=0,$limit=20,$whereRaw='')
    {
        $sql = $this->field($field)->where($where);
        if($order){
            $sql->order($order);
        }
        if($whereRaw){
            $sql->whereRaw($whereRaw);
        }
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
    
    public function getHotwordUrlList($where,$field = true,$order=true,$page=0,$limit=20){
        $sql = $this->alias('hw')
            ->leftJoin('house_hotword_urllist hwu','hw.id=hwu.word_id')
            ->field($field)->where($where)->order($order);
        if($page) {
            $sql->page($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }
    
}
