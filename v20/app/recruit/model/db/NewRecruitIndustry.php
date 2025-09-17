<?php

namespace app\recruit\model\db;

use think\Model;

/**
 * 公司行业
 * @package app\recruit\model\db
 */
class NewRecruitIndustry extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 列表
     */
    public function getRecruitIndustryList($where, $field, $order, $page, $pageSize){
        $result = $this->alias('g')
            ->where($where)
            ->field($field);
            // ->join('grow_grass_article at', 'at.article_id = g.article_id')
            // ->join('user u', 'g.uid = u.uid');
        $assign['count'] = $result->count();
        $assign['list'] = $result->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $assign;
    }

    /**
     * 保存
     */
    public function getRecruitIndustryCreate($id, $data){
        if($id > 0){
            // 修改
            $where = ['id'=>$id];
            return $this->where($where)->update($data);
        }else{
            // 新增
            return $this->insert($data);
        }
    }

    /**
     * 单条
     */
    public function getRecruitIndustryInfo($where){
        return $this->where($where)->find();
    }

    /**
     * 排序
     */
    public function getRecruitIndustrySort($where, $data){
        return $this->where($where)->update($data);
    }

    /**
     * 移除
     */
    public function getRecruitIndustryDel($where){
        return $this->where($where)->update(['status'=>1]);
    }

    /**
     * 获取字段值
     * @param string $field
     * @param array $where
     * @return mixed
     */
    public function getColVal($field, $where = []) {
        $result = $this->where($where)->value($field);
        return $result;
    }

}