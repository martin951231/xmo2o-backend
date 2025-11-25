<?php


namespace app\community\model\db;

use think\Model;
class WorkMsgAuditInfoGroup extends Model
{
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

    /**
     * Notes: 获取列表
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return
     */
    public function getList($where,$field=true,$order='id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }
    public function getFind($where,$field=true,$order='id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }


    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }


    /**
     * 获取内部群聊数据
     * @author: liukezhu
     * @date : 2021/5/13
     * @param $where
     * @return mixed
     */
    public function getWorkerGroup($where,$column){
        $list = $this->alias('g')
            ->leftJoin('house_worker w','w.wid=g.owner_id')
            ->where($where)
            ->column($column);
        return $list;
    }


    /**
     * 获取外部群聊数据
     * @author: liukezhu
     * @date : 2021/5/13
     * @param $where
     * @return mixed
     */
    public function getContactWayUserGroup($where,$column){
        $list = $this->alias('g')
            ->leftJoin('house_contact_way_user w','w.customer_id=g.owner_id')
            ->where($where)
            ->column($column);
        return $list;
    }
}