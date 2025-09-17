<?php
/**
 * 热词service
 **/

namespace app\community\model\service;

use app\community\model\db\HouseHotwordMaterialContent;
use think\Exception;

class HouseHotWordMaterialContentService
{


    public function getOneMaterialContent($where, $field = true)
    {
        $dbMaterialContent = new HouseHotwordMaterialContent();
        $dataObj = $dbMaterialContent->get_one($where, $field);
        if ($dataObj && !$dataObj->isEmpty()) {
            $data = $dataObj->toArray();
        } else {
            $data = [];
        }
        return $data;
    }

    public function getMaterialContentList($whereArr, $field = '*', $page = 1, $limit = 20)
    {
        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $dbMaterialContent = new HouseHotwordMaterialContent();
        $count = $dbMaterialContent->getCount($whereArr);
        if ($count > 0) {
            $dataArr['count'] = $count;
            $resObj = $dbMaterialContent->getMaterialLists($whereArr, $field, 'material_id desc', $page, $limit);
            if (!empty($resObj) && !$resObj->isEmpty()) {
                $res = $resObj->toArray();
                foreach ($res as $kk => $vv) {
                    $res[$kk]['add_time_str'] = date('Y-m-d H:i:s', $vv['add_time']);
                    $res[$kk]['update_time_str'] = $vv['update_time'] > 0 ? date('Y-m-d H:i:s', $vv['update_time']) : '';
                    $res[$kk]['audio_url']='';
                    $res[$kk]['xtype']=intval($vv['xtype']);
                    $res[$kk]['word_imgs']=array();
                    if($vv['xtype']==2){
                        $audio_url=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                        $audio_url=replace_file_domain($audio_url);
                        $res[$kk]['audio_url']=$audio_url;
                    }elseif ($vv['xtype']==3){
                        $img_url=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                        $tmpimgs=explode(',',$img_url);
                        $img_url_arr=array();
                        foreach ($tmpimgs as $imgv){
                            $newimgsrc=replace_file_domain($imgv);
                            $img_url_arr[]=$newimgsrc;
                        }
                        $res[$kk]['word_imgs']=$img_url_arr;
                    }elseif ($vv['xtype']==1){
                        $res[$kk]['xcontent']=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                    }
                }
                $dataArr['list'] = $res;
            } else {
                $dataArr['list'] = array();
            }
        }
        return $dataArr;
    }
    
    /***
     * 获取分类下数据
     */
    
    public function getHotWordMaterialLibraryDetails($whereArr, $fieldStr= '*', $page = 1, $limit = 20){
        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $dbMaterialContent = new HouseHotwordMaterialContent();
        $count = $dbMaterialContent->getCount($whereArr);
        if ($count > 0) {
            $dataArr['count'] = $count;
            $resObj = $dbMaterialContent->getMaterialLists($whereArr, $fieldStr, 'material_id desc', $page, $limit);
            if (!empty($resObj) && !$resObj->isEmpty()) {
                $res = $resObj->toArray();
                foreach ($res as $kk => $vv) {
                    $res[$kk]['audio_url']='';
                    $res[$kk]['xtype']=intval($vv['xtype']);
                    $res[$kk]['word_imgs']=array();
                    if($vv['xtype']==2){
                        $audio_url=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                        $audio_url=replace_file_domain($audio_url);
                        $res[$kk]['audio_url']=$audio_url;
                    }elseif ($vv['xtype']==3){
                        $img_url=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                        $tmpimgs=explode(',',$img_url);
                        $img_url_arr=array();
                        foreach ($tmpimgs as $imgv){
                            $newimgsrc=replace_file_domain($imgv);
                            $img_url_arr[]=$newimgsrc;
                        }
                        $res[$kk]['word_imgs']=$img_url_arr;
                    }elseif ($vv['xtype']==1){
                        $res[$kk]['xcontent']=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                    }
                }
                $dataArr['list'] = $res;
            } else {
                $dataArr['list'] = array();
            }
        }
        return $dataArr;
    }

    public function addMaterialContent($addArr = array())
    {
        $dbMaterialContent = new HouseHotwordMaterialContent();
        $idd = 0;
        if ($addArr) {
            $nowtime = time();
            $addArr['add_time'] = $nowtime;
            $addArr['update_time'] = $nowtime;
            $idd = $dbMaterialContent->addData($addArr);
        }
        return $idd;
    }

    //更新数据
    public function updateMaterialContent($where = array(), $updateArr = array())
    {
        $dbMaterialContent = new HouseHotwordMaterialContent();
        $dataObj = $dbMaterialContent->get_one($where);
        $mContent = '';
        if ($dataObj && !$dataObj->isEmpty()) {
            $mContent = $dataObj->toArray();
        }
        if (empty($mContent)) {
            throw new \think\Exception("修改失败，数据信息不存在！");
        }
        if ($updateArr) {
            $nowtime = time();
            $updateArr['update_time'] = $nowtime;
            $ret = $dbMaterialContent->editData($where, $updateArr);
            return $ret;
        }
        return false;
    }

    public function delMaterialContentData($whereArr)
    {
        if(empty($whereArr)){
            throw new \think\Exception("删除失败，请确认删除的数据！");
        }
        $dbMaterialContent = new HouseHotwordMaterialContent();
        $updateArr = array('del_time' => time());
        $ret=$dbMaterialContent->editData($whereArr, $updateArr);
        return $ret;
    }
    
    public function getMaterialContentCount($whereArr){
        $dbMaterialContent = new HouseHotwordMaterialContent();
        $count = $dbMaterialContent->getCount($whereArr);
        return $count ? $count:0;
    }
}
