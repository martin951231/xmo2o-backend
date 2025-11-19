<?php
/**
 * @author : liukezhu
 * @date : 2021/5/10
 */
namespace app\community\model\db;

use think\Model;
class WorkMsgAuditInfoViolationStaff extends Model
{


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
    public function getList($where,$field='*',$order='s.id desc',$page=0,$limit=20)
    {
        $sql = $this->alias('s')->leftJoin('house_worker w','w.wid=s.staff_id')
            ->where($where)->field($field)->order($order);
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
        $count =$this->alias('s')->leftJoin('house_worker w','w.wid=s.staff_id')
            ->where($where)->count();
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


    public function getOne($where){
        $list = $this->alias('s')->leftJoin('house_worker w','w.wid=s.staff_id')
            ->where($where)->field('s.*,w.name as staff_name')->find();
        return $list;
    }

    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }

    /**
     * 获取所有数据
     * @param array $where
     * @param bool $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author weili
     * @datatime 2020/07/14 18:12
     */
    public function getAll($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('id desc')->select();
        return $data;
    }

    public function getViolationWork($where,$field){
        $list = $this->alias('s')
            ->leftJoin('work_msg_audit_info m','m.user_id=s.staff_id and m.from_type=1')
            ->where($where)->field($field)->order('s.id asc')->find();
        return $list;
    }

}