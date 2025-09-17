<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsCarPark extends Model
{
    /**
     * 获取停车场列表
     * @param $where
     * @param int $page
     * @param int $page_size
     * @param string $field
     * @param string $sord
     * @return \think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$page=0,$page_size=10,$field='',$sord='car_park_id desc'){
        if(!$field) $field='*';
        $query = $this->field($field)
            ->where($where)
            ->order($sord);
        if($page){
            $list = $query->paginate($page_size);
        }else{
            $list = $query->select();
        }
        return $list;
    }
}