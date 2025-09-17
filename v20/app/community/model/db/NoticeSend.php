<?php


namespace app\community\model\db;

use think\Model;

class NoticeSend extends Model
{

    public function getLists($where,$field=true,$limit=10,$order='pigcms_id ASC')
    {
        $data = $this->field($field)->where($where)->limit($limit)->order($order)->select()->toArray();
        return $data;
    }

    public function getOne($where,$field,$order='news_id ASC')
    {
        $data = $this->field($field)->where($where)->order($order)->find();
        return $data;
    }
    
    public function updateValue($where,$data)
    {
        $update = $this->where($where)->update($data);
        return $update;
    }
    
    public function del($where)
    {
        $del = $this->where($where)->delete();
        return $del;
    }
}