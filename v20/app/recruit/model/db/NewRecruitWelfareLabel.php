<?php
/**
 * HR管理model
 * Author: wangchen
 * Date Time: 2021/6/22
 */

namespace app\recruit\model\db;

use think\Model;

class NewRecruitWelfareLabel extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 列表
     */
    public function getRecruitHrList($where, $field, $order, $page, $pageSize){
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
    public function getRecruitHrCreate($id, $data){
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
     * 验证
     */
    public function getRecruitHrCount($where){
        return $this->where($where)->count();
    }

    /**
     * 单条
     */
    public function getRecruitHrInfo($where){
        return $this->where($where)->find();
    }

    /**
     * 移除
     */
    public function getRecruitHrDel($where){
        return $this->where($where)->update(['status'=>1]);
    }
}