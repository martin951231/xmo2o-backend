<?php


namespace app\community\model\db;

use think\Model;
class WorkMsgAudit extends Model
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
    public function getList($where,$field=true,$order='id desc',$where_or=[],$page=0,$limit=20)
    {
        $sql = $this->where($where)->where($where_or)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }
    public function getFind($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }
}