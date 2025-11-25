<?php
/**
 * MerchantPositionService.php
 * 店铺岗位管理service
 * Create on 2021/6/2
 * Created by wangchen
 */

namespace app\merchant\model\service;

use app\merchant\model\db\MerchantPosition;
use app\common\model\db\MerchantCategory;

class MerchantPositionService
{
    /**
     * 岗位列表
     * @return \json
     */
    public function getPositionList($remarks, $cat_id, $page, $pageSize)
    {
        $this->merchantPositionModel = new MerchantPosition();
        $where = [['b.cat_status','=',1]];
        if (!empty($remarks)) {
            $arr = [['a.remarks', 'like', '%' . $remarks . '%']];
            $where = array_merge($where, $arr);
        }
        if ($cat_id > 0) {
            $arr = [['a.cat_id', '=', $cat_id]];
            $where = array_merge($where, $arr);
        }
        $order = 'sort DESC, id DESC';
        $list = $this->merchantPositionModel->getPositionList($where, $order, $page, $pageSize);
        $categoryList = (new MerchantCategory())->getCategory();
        
        if (!empty($list)) {
            //获取总数
            $list1['list'] = $list;
            $list1['categoryList'] = $categoryList;
            $list1['count'] = $this->merchantPositionModel->getPositionCount($where);
            return $list1;
        } else {
            $list1['list'] = [];
            $list1['categoryList'] = $categoryList;
            $list1['count'] = 0;
            return $list1;
        }
    }

    /**
     * 岗位分类
     * @return \json
     */
    public function getPositionCategoryList()
    {
        $arr = (new MerchantCategory())->getCategory();
        return $arr;
    }

    /**
     * 岗位操作
     * @return \json
     */
    public function getPositionCreate($id, $cat_id, $name, $remarks)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('分类参数缺失');
        }
        $arr = (new MerchantPosition())->getPositionCreate($id, $cat_id, $name, $remarks);
        return $arr;
    }

    /**
     * 岗位详情
     * @return \json
     */
    public function getPositionInfo($id)
    {
        if (empty($id)) {
            throw new \think\Exception('id参数缺失');
        }
        $where = ['id' => $id];
        $arr = (new MerchantPosition())->getPositionInfo($where);
        return $arr;
    }

    /**
     * 岗位删除
     * @return \json
     */
    public function getPositionDelAll($id)
    {
        if (empty($id)) {
            throw new \think\Exception('id参数缺失');
        }
        $where = ['id'=>$id];
        $result = (new MerchantPosition())->getPositionDelAll($where);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $result;
    }
}