<?php
/**
 * @author : liukezhu
 * @date : 2021/5/12
 */
namespace app\community\model\db;

use think\Model;
class WorkMsgAuditInfoViolationGroup extends Model{


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
    public function getList($where,$field='*',$order='g.id desc',$page=0,$limit=20)
    {
        $w='';
        if(isset($where['_string'])){
            $w=$where['_string'];
            unset($where['_string']);
        }
        $sql = $this->alias('g')->where($where)->where($w)->field($field)->order($order);
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
        $count =$this->alias('g')->where($where)->count();
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

    public function getAll($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('id desc')->select();
        return $data;
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


    public function getOne($where){
        $list = $this->alias('g')->where($where)->field('g.id,g.rule_name,g.action,g.remind_type,g.sensitive_id,g.remind_info,g.group_id,g.status,( SELECT group_concat( g1.roomname ) FROM pigcms_work_msg_audit_info_group AS g1 WHERE FIND_IN_SET( g1.id, g.group_id ) ) AS groups')->find();
        return $list;
    }


    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }


}