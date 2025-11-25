<?php

namespace app\recruit\model\db;

use think\Model;

class NewRecruitWelfare extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 列表
     */
    public function getRecruitWelfareList($where, $field, $order, $page, $pageSize){
        $result = $this->alias('g')
            ->where($where)
            ->field($field);
            // ->join('grow_grass_article at', 'at.article_id = g.article_id')
            // ->join('user u', 'g.uid = u.uid');
        $assign['count'] = $result->count();
        if($page){
            $assign['list'] = $result->order($order)
                ->page($page, $pageSize)
                ->select()
                ->toArray();
        }else{
            $assign['list'] = $result->order($order)
                ->select()
                ->toArray();
        }

        return $assign;
    }


    /**
     * 获取当前值
     * @param string $field
     * @param array $where
     * @return mixed
     */
    public function getVal($field, $where = []) {
        $result = $this->where($where)->value($field);
        return $result;
    }
    /**
     * 保存
     */
    public function getRecruitWelfareCreate($id, $data){
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
    public function getRecruitWelfareInfo($where){
        return $this->where($where)->find();
    }

    /**
     * 移除
     */
    public function getRecruitWelfareDel($where){
        return $this->where($where)->update(['status'=>1]);
    }
}