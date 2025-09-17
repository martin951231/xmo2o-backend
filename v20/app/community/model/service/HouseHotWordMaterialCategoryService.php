<?php
/**
 * 热词service
 **/

namespace app\community\model\service;

use app\community\model\db\HouseHotwordMaterialCategory;
use think\Exception;

class HouseHotWordMaterialCategoryService
{


    public function getOneMaterialCategory($where, $field = true)
    {
        $dbMaterialCategory = new HouseHotwordMaterialCategory();
        $dataObj = $dbMaterialCategory->get_one($where, $field);
        if ($dataObj && !$dataObj->isEmpty()) {
            $data = $dataObj->toArray();
        } else {
            $data = [];
        }
        return $data;
    }

    public function getMaterialCategoryList($whereArr, $field = '*', $page = 1, $limit = 20)
    {
        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $dbMaterialCategory = new HouseHotwordMaterialCategory();
        $count = $dbMaterialCategory->getCount($whereArr);
        if ($count > 0) {
            $dataArr['count'] = $count;
            $resObj = $dbMaterialCategory->getCategoryLists($whereArr, $field, 'cate_id desc', $page, $limit);
            if (!empty($resObj) && !$resObj->isEmpty()) {
                $res = $resObj->toArray();
                foreach ($res as $kk => $vv) {
                    $res[$kk]['add_time_str'] = date('Y-m-d H:i:s', $vv['add_time']);
                    $res[$kk]['update_time_str'] = $vv['update_time'] > 0 ? date('Y-m-d H:i:s', $vv['update_time']) : '';
                }
                $dataArr['list'] = $res;
            } else {
                $dataArr['list'] = array();
            }
        }
        return $dataArr;
    }

    /***
     **获取素材库数据
     ***/
    public function getHotWordMaterialLibrary($whereArr, $fieldStr = '*')
    {
        $retArr = array('list' => array());
        if (empty($whereArr)) {
            return $retArr;
        }
        $dbMaterialCategory = new HouseHotwordMaterialCategory();
        $resObj = $dbMaterialCategory->getAllCategory($whereArr, $fieldStr);
        if (!empty($resObj) && !$resObj->isEmpty()) {
            $res = $resObj->toArray();
            $retArr['list'] = $res;
        } 
        return $retArr;
    }

    public function addMaterialCategory($addArr = array())
    {
        $dbMaterialCategory = new HouseHotwordMaterialCategory();
        $idd = 0;
        if ($addArr) {
            $nowtime = time();
            $addArr['add_time'] = $nowtime;
            $addArr['update_time'] = $nowtime;
            $idd = $dbMaterialCategory->addData($addArr);
        }
        return $idd;
    }

    //更新数据
    public function updateMaterialCategory($where = array(), $updateArr = array())
    {
        $dbMaterialCategory = new HouseHotwordMaterialCategory();
        $dataObj = $dbMaterialCategory->get_one($where);
        $mCategory = '';
        if ($dataObj && !$dataObj->isEmpty()) {
            $mCategory = $dataObj->toArray();
        }
        if (empty($mCategory)) {
            throw new \think\Exception("修改失败，分类信息不存在！");
        }
        if ($updateArr) {
            $nowtime = time();
            $updateArr['update_time'] = $nowtime;
            $ret = $dbMaterialCategory->editData($where, $updateArr);
            return $ret;
        }
        return false;
    }

    public function delMaterialCategoryData($whereArr)
    {
        if (empty($whereArr)) {
            throw new \think\Exception("删除失败，请确认删除的数据！");
        }
        $dbMaterialCategory = new HouseHotwordMaterialCategory();
        $updateArr = array('del_time' => time());
        $ret = $dbMaterialCategory->editData($whereArr, $updateArr);
        return $ret;
    }
    //更新字段值 加
    public function updateFieldPlusNum($whereArr = array(), $fieldv = 1, $fieldname = 'subcount')
    {
        $dbMaterialCategory = new HouseHotwordMaterialCategory();
        if (empty($whereArr) || empty($fieldname)) {
            return false;
        }
        $ret = $dbMaterialCategory->updateFieldPlusNum($whereArr, $fieldname, $fieldv);
        return $ret;
    }
    //更新字段数值 减
    public function updateFieldMinusNum($whereArr = array(), $fieldv = 1, $fieldname = 'subcount')
    {
        $dbMaterialCategory = new HouseHotwordMaterialCategory();
        if (empty($whereArr) || empty($fieldname)) {
            return false;
        }
        $ret = $dbMaterialCategory->updateFieldMinusNum($whereArr, $fieldname, $fieldv);
        return $ret;
    }

    //更新数据
    public function updateMaterialCategorySubCount($where = array(), $subcount = 0)
    {
        $dbMaterialCategory = new HouseHotwordMaterialCategory();
        $dataObj = $dbMaterialCategory->get_one($where);
        $mCategory = '';
        if ($dataObj && !$dataObj->isEmpty()) {
            $mCategory = $dataObj->toArray();
        }
        if (empty($mCategory)) {
            return false;
        }
        $updateArr = array('subcount' => $subcount);
        $ret = $dbMaterialCategory->editData($where, $updateArr);
        return $ret;

        return false;
    }
}
