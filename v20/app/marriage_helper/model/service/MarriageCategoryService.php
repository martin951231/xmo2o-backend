<?php
namespace app\marriage_helper\model\service;

use app\marriage_helper\model\db\MarriageCategory;
use app\merchant\model\db\MerchantPosition;

class MarriageCategoryService
{
    /**
     * 分类列表
     * @return \json
     */
    public function getCategoryList($page, $pageSize)
    {
        $this->marriageCategoryModel = new MarriageCategory();
        $where = [['a.is_del','=',0]];
        $order = 'sort DESC, id DESC';
        $list = $this->marriageCategoryModel->getCategoryList($where, $order, $page, $pageSize);
        
        if (!empty($list)) {
            //获取总数
            $list1['list'] = $list;
            $list1['count'] = $this->marriageCategoryModel->getCategoryCount($where);
            return $list1;
        } else {
            $list1['list'] = [];
            $list1['count'] = 0;
            return $list1;
        }
    }

    /**
     * 分类岗位列表
     * @return \json
     */
    public function getCategoryPositionList()
    {
        $arr = (new MerchantPosition())->getPosition('');
        return $arr;
    }

    /**
     * 分类操作
     * @return \json
     */
    public function getCategoryCreate($cat_id, $pos_id, $cat_name, $sort, $status)
    {
        if (empty($pos_id)) {
            throw new \think\Exception('职位参数缺失');
        }
        $arr = (new MarriageCategory())->getCategoryCreate($cat_id, $pos_id, $cat_name, $sort, $status);
        return $arr;
    }

    /**
     * 分类详情
     * @return \json
     */
    public function getCategoryInfo($cat_id)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('编号参数缺失');
        }
        $where = ['cat_id' => $cat_id];
        $arr = (new MarriageCategory())->getCategoryInfo($where);
        return $arr;
    }

    /**
     * 分类排序
     * @return \json
     */
    public function getCategorySort($cat_id, $sort)
    {
        $list = (new MarriageCategory())->getCategorySort($cat_id, $sort);
        return $list;
    }

    /**
     * 分类删除
     * @return \json
     */
    public function getCategoryDelAll($cat_id)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('编号参数缺失');
        }
        $where = ['cat_id'=>$cat_id];
        $result = (new MarriageCategory())->getCategoryDelAll($where);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $result;
    }
}