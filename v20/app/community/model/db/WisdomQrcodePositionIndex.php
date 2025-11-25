<?php
/**
 * @author : liukezhu
 * @date : 2022/5/23
 */
namespace app\community\model\db;

use think\Model;
class WisdomQrcodePositionIndex extends  Model
{

    public function getList($where,$field='*',$order='i.id desc',$page=0,$limit=20){
        $sql = $this->alias('i')
            ->leftJoin('wisdom_qrcode_position_record r','r.index_id = i.id')
            ->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getCount($where) {
        $count =$this->alias('i')
            ->leftJoin('wisdom_qrcode_position_record r','r.index_id = i.id')
            ->where($where)->count();
        return $count;
    }

    public function getCounts($where) {
        $count =$this->where($where)->count();
        return $count;
    }

    public function addFind($data){
        $id =$this->insertGetId($data);
        return $id;
    }


    public function editFind($where,$data){
        $res = $this->where($where)->update($data);
        return $res;
    }

    public function getFind($where,$field='*',$order='id DESC'){
        $list = $this->where($where)->order($order)->field($field)->find();
        return $list;
    }


    public function getIndexList($where, $field = true, $having=false,$order = 'i.id desc', $page = 0, $limit = 10)
    {
        $sql = $this->alias('i')->leftJoin('house_worker w','w.wid = i.wid')->where($where)->field($field)->order($order);
        if($having != false){
            $sql->having($having);
        }
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }

    public function getFinds($where,$field='*',$order='a.id DESC'){
        $list = $this->alias('a')
            ->leftJoin('wisdom_qrcode_cate b','b.id = a.cate_id')
            ->leftJoin('wisdom_qrcode_person c','c.uid = a.wid')
            ->where($where)->order($order)->field($field)->find();
        return $list;
    }




}