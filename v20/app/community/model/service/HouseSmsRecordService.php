<?php
/**
 * Created by PhpStorm.
 * Author: lihongshun
 * Date Time: 2023/7/25 9:20
 */

namespace app\community\model\service;

use app\community\model\db\HouseSmsRecord;
use think\facade\Db;
use message\Jpush;

class HouseSmsRecordService
{
    
    public function getOneSmsRecord($whereArr = array(),$field="*",$orderby='id desc')
    {
        $oneRecordInfo = array();
        $houseSmsRecordDb = new HouseSmsRecord();
        if (!empty($whereArr)) {
            $houseSmsRecordObj = $houseSmsRecordDb->getOneData($whereArr,$field,$orderby);
            if ($houseSmsRecordObj && !$houseSmsRecordObj->isEmpty()) {
                $oneRecordInfo = $houseSmsRecordObj->toArray();
            }
        }
        return $oneRecordInfo;
    }
    public function addOneSmsRecord($addArr = array())
    {
        $houseSmsRecordDb = new HouseSmsRecord();
        $ret = $houseSmsRecordDb->addOneData($addArr);
        return $ret;
    }
    
    public function saveSmsRecord($whereArr = array(), $saveArr = array())
    {
        $houseSmsRecordDb = new HouseSmsRecord();
        $ret = $houseSmsRecordDb->saveOneData($whereArr, $saveArr);
        return $ret;
    }

    public function getSendSmsRecordList($where, $field = '*', $page = 0, $limit = 15, $order = 'id DESC')
    {
        $houseSmsRecordDb = new HouseSmsRecord();
        $listDataObj = $houseSmsRecordDb->getListData($where, $field, $order, $page, $limit);
        if ($listDataObj && !$listDataObj->isEmpty()) {
            $listData = $listDataObj->toArray();
        } else {
            $listData = array();
        }
        return $listData;
    }
}
