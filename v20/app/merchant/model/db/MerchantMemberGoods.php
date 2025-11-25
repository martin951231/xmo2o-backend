<?php

namespace app\merchant\model\db;

use think\Model;

class MerchantMemberGoods extends Model
{
    /**
     * 获取会员商品列表
     * @param $where
     * @param int $page
     * @param int $page_size
     * @param string $field
     * @return \think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$page=0,$page_size=10,$field='*'){
        $query = $this->field($field)
            ->where($where)
            ->order('id desc');
        if($page>0){
            $list = $query->paginate($page_size);
        }else{
            $list = $query->select();
        }
        return $list;
    }
}