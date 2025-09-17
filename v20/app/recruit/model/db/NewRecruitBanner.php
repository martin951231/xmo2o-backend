<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitBanner extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 列表
     */
    public function getRecruitBannerList($where, $field, $order, $page, $pageSize){
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
    public function getRecruitBannerCreate($id, $data){
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
    public function getRecruitBannerInfo($where){
        return $this->where($where)->find();
    }

    /**
     * 展示
     */
    public function getRecruitBannerDis($where, $type){
        if($type){
            return $this->where($where)->update(['is_dis'=>1]);
        }else{
            return $this->where($where)->update(['is_dis'=>0]);
        }
    }

    /**
     * 排序
     */
    public function getRecruitBannerSort($where, $data){
        return $this->where($where)->update($data);
    }

    /**
     * 移除
     */
    public function getRecruitBannerDel($where){
        return $this->where($where)->update(['status'=>1]);
    }
}