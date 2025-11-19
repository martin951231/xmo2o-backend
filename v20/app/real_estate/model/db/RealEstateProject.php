<?php
namespace app\real_estate\model\db;
use think\Model;

class RealEstateProject extends Model {
    /**
     * 获取信息列表
     * @param array $where
     * @param int $page
     * @param $page_size
     * @param string $field
     * @param string $sort
     */
    public function getList($where=[],$page=1,$page_size,$field='*',$sort='id desc'){
        $query = $this->field($field)
            ->where($where)
            ->order($sort);
        if($page){
            $list = $query->paginate($page_size);
        }else{
            $list = $query->select();
        }
        return $list;
    }
}