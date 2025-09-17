<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitCompanyUserCollect extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    public function deleteCollect($id){
    	return $this->where('id', $id)->delete();
    }

    /**
     * 公司收藏列表
     */
    public function companyUserCollectList($where, $order, $field, $page = 0, $pageSize = 20){
        $result = $this->alias('g')
            ->where($where)
            ->field($field)
            ->leftJoin('new_recruit_company a', 'a.mer_id = g.mer_id')
            ->leftJoin('new_recruit_industry b', 'b.id = a.industry_id1')
            ->leftJoin('new_recruit_industry c', 'c.id = a.industry_id2');

        if ($page > 0 && $pageSize > 0) {
            $result->page($page, $pageSize);
        }
        $assign = $result->order($order)
            ->select()
            ->toArray();
        return $assign;
    }
}