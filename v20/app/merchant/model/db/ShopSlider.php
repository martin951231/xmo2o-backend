<?php


namespace app\merchant\model\db;


use think\Model;

class ShopSlider extends Model
{
    /**
     * 获取导航列表
     * @param $where
     * @param int $page
     * @param int $pageSize
     * @return \think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$page=0,$pageSize=10){
        $query = $this->field('*')
            ->where($where)->order('sort desc,id desc');
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list;
    }
}