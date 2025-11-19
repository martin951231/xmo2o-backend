<?php
namespace app\complaint\model\db;

use think\Model;

class Complaint extends Model
{
    /**
     * 获取投诉列表
     * @param $where
     * @param $page
     * @param int $page_size
     * @param string $field
     * @return mixed
     */
    public function getList($where,$page,$page_size=10,$field=''){
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if(!$field){
            $field = 'a.*,b.nickname,g.name as type,t.name as other_type';
        }
        $query = $this->field($field)->alias('a')
            ->join($prefix.'user b','a.uid = b.uid')
            ->join('complaint_group g','a.type = g.type and g.is_del = 0','left')
            ->join('complaint_group_type t','a.other_type = t.id and t.gid = g.id','left')
            ->where($where)
            ->order('create_time desc');
        if($page){
            $list = $query->paginate($page_size);
        }else{
            $list = $query->select();
        }
        return $list;
    }
}