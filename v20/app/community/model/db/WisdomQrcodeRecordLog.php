<?php


namespace app\community\model\db;
use think\Model;

class WisdomQrcodeRecordLog extends Model
{
    /**
     * 获取巡检记录数
     * @author lijie
     * @date_time 2020/08/04 16:46
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function getCountGroup($where,$group)
    {
        $data =$this->where($where)->where($where)->group($group)->count();
        return $data;
    }

    /**
     * Notes: 获取数据列表
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/11/4 15:38
     */
    public function getList($where,$field=true,$order='',$page=0,$limit=0)
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
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/11/5 15:46
     */
    public function addData($data)
    {
        $insert_id = $this->insertGetId($data);
        return $insert_id;
    }

    public function getOneData($where, $field = true, $orderby = 'id DESC')
    {
        $data = $this->field($field)->where($where)->order($orderby)->find();
        return $data;
    }

    public function saveOneData($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
}