<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsWifi extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取商家wifi信息列表
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