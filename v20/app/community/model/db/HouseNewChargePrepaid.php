<?php
/**
 * @author : liukezhu
 * @date : 2021/6/11
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseNewChargePrepaid extends Model{

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


    public function getFind($where,$field=true,$order='id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }


    public function getOne($where,$field='*'){
        $list = $this->where($where)->field($field)->find();
        return $list;
    }

    public function getDetail($where=[],$field=true)
    {
        $data = $this->alias('pre')
            ->leftJoin('house_new_charge_rule r','pre.charge_rule_id = r.id')
            ->leftJoin('house_new_charge_project p','p.id = r.charge_project_id')
            ->leftJoin('house_new_charge_number n','n.id = p.subject_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

    public function getLists($where=[],$field=true,$order='pre.cycle DESC')
    {
        $data = $this->alias('pre')
            ->leftJoin('house_new_charge_rule r','pre.charge_rule_id = r.id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }


    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }

    public function getMax($where,$column)
    {
        $data = $this->where($where)->max($column);
        return $data;
    }
}