<?php
namespace app\real_estate\model\db;

use think\Model;

class RealEstateWish extends Model
{
    /**
     * 获取信息列表
     * @param array $where
     * @param int $page
     * @param $page_size
     * @param string $field
     * @param string $sort
     */
    public function getList($where=[],$page=0,$page_size=10,$field='',$sort='a.id desc'){
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if(!$field){
            $field = 'a.*,b.name as process_name,b.font_color,c.name as project_name,d.name as type_name,e.realname';
        }
        $query = $this->field($field)->alias('a')
            ->join($prefix.'real_estate_process b','a.process_id = b.id')
            ->join($prefix.'real_estate_project c','a.project_id = c.id')
            ->join($prefix.'real_estate_type d','a.type_id = d.id')
            ->join($prefix.'admin e','a.uid = e.id and e.status = 1')
            ->where($where)
            ->order($sort);
        if($page>0){
            $list = $query->paginate($page_size);
        }else{
            $list = $query->select();
        }
        return $list;
    }

    /**
     * 统计信息
     */
    public function getCount($where=[],$field="count(a.id) as num"){
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->field($field)->alias('a')
            ->join($prefix.'real_estate_process b','a.process_id = b.id')
            ->join($prefix.'real_estate_project c','a.project_id = c.id')
            ->join($prefix.'real_estate_type d','a.type_id = d.id')
            ->where($where)
            ->select();
        return $count;
    }

}