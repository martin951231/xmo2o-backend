<?php


namespace app\community\model\db;
use think\Model;

class WisdomQrcodeCate extends Model
{
    /**
     * 获取巡检列表
     * @author lijie
     * @date_time 2020/08/04 14:15
     * @param $where
     * @param bool $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$order='id desc',$page=0,$limit=0)
    {
//        $data = $this->where($where)->field($field)->select()->toArray();
//        return $data;
        $sql = $this->where($where)->field($field)->order($order);
        $data=array();
        if($page){
            $dataObj = $sql->page($page,$limit)->select();
        }else{
            $dataObj = $sql->select();
        }
        if($dataObj && !$dataObj->isEmpty()){
            $data=$dataObj->toArray();
        }
        return $data;
    }

    /**
     * Notes: 获取一条数据
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/11/3 14:52
     */
    public function getFind($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }
    public function addOneData($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }
    public function saveOneData($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
    /**
     * Notes: 获取字段值
     * @param string|\think\model\concern\string $where
     * @param mixed $value
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/3 15:34
     */
    public function getValues($where,$value)
    {
        $data = $this->where($where)->value($value);
        return $data;
    }
    public function getSelect($where,$field=true,$order='c.id desc')
    {
        $list = $this->alias('c')->leftJoin('wisdom_qrcode_person p','c.id=p.cate_id')->where($where)->field($field)->order($order)->select();
        return $list;
    }
}