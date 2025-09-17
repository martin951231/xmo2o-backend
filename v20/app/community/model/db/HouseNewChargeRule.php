<?php
/**
 * @author : liukezhu
 * @date : 2021/6/11
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseNewChargeRule extends Model{

    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2021/5/11
     * @param $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getList($where,$field='*',$order='id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }


    /**
     * 查询总数
     * @author: liukezhu
     * @date : 2021/5/11
     * @param $where
     * @return mixed
     */
    public function getCount($where) {
        $count =$this->where($where)->count();
        return $count;
    }


    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     */
    public function addFind($data)
    {
        $id =$this->insertGetId($data);
        return $id;
    }

    /**
     * Notes:修改数据
     * @param $where
     * @param $data
     * @return WorkMsgAuditInfo
     */
    public function editFind($where,$data){
        $res = $this->where($where)->update($data);
        return $res;
    }


    public function getFind($where,$field=true)
    {
        $data = $this->alias('r')
            ->leftJoin('house_new_charge_number c','c.id = r.subject_id')
            ->leftJoin('house_new_charge_project p','p.id = r.charge_project_id')
            ->where($where)
            ->field($field)->find();
        return $data;
    }


    public function getOne($where,$field='*',$order='id DESC'){
        $list = $this->where($where)->order($order)->field($field)->find();
        return $list;
    }


    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }

    public function getDetail($where=[],$field=true)
    {
        $data = $this->alias('r')
            ->leftJoin('house_new_charge_project p','p.id = r.charge_project_id')
            ->leftJoin('house_new_charge_number n','n.id = p.subject_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

    public function getLists($where=[],$field=true,$page=0,$limit=20,$order='r.id desc')
    {
        $sql = $this->alias('r')
            ->leftJoin('house_new_charge_project p','p.id = r.charge_project_id')->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list; 
        
    }

    public function getCounts($where=[])
    {
        $data = $this->alias('r')
            ->leftJoin('house_new_charge_project p','p.id = r.charge_project_id')
            ->where($where)
            ->count();
        return $data;
    }
}