<?php
//会话消息汇总表

namespace app\community\model\db;

use think\Model;
class WorkMsgAuditInfo extends Model
{
    /**
     * Notes: 添加数据
     * @datetime: 2021/3/29 11:39
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
     * @datetime: 2021/3/29 11:40
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
     * @datetime: 2021/3/29 11:40
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return
     */
    public function getList($where,$field=true,$order='id desc',$whereRaw ='',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if ($whereRaw) {
            $sql->whereRaw($whereRaw);
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getFind($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
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
}