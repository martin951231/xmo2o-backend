<?php
/**
 * Created by PhpStorm.
 * Author: weili
 * Date Time: 2020/7/8 11:38
**/

namespace app\community\model\service;
use app\community\model\db\HouseAdminGroup;
use app\community\model\db\HouseMenuNew;
class HouseAdminGroupService
{

    public function getGroupList($where,$field=true)
    {
        if(empty($where)){
            return false;
        }
        $houseAdminGroupDb = new HouseAdminGroup();
        $groupList=$houseAdminGroupDb->getGroupList($where,$field);
        return $groupList;
    }

    public function getOneGroup($where,$field=true){
         if(empty($where)){
             return false;
         }
        $houseAdminGroupDb = new HouseAdminGroup();
        $info = $houseAdminGroupDb->get_one($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }else{
            $info=$info->toArray();
        }
        return $info;
    }

    public function  getGroupPageList($whereArr,$field='*',$page=1,$limit=20)
    {
        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $houseAdminGroupDb = new HouseAdminGroup();
        $count = $houseAdminGroupDb->getHouseAdminGroupCount($whereArr);
        if ($count > 0) {
            $houseAdminGroupDb = new HouseAdminGroup();
            $dataArr['count'] = $count;
            $res = $houseAdminGroupDb->getHouseAdminGroupLists($whereArr, $field,'group_id DESC', $page, $limit);
            if (!empty($res)) {
                $dataArr['list'] = $res;
            } else {
                $dataArr['list'] = array();
            }
        }
        return $dataArr;
    }

    // 处理下目录权限结构数据
    public function repairMenusInfo($menus_arr)
    {
        if (!$menus_arr) {
            return $menus_arr;
        }
        if (!is_array($menus_arr)) {
            $menus_arr = explode(',', $menus_arr);
        }
        $where_val = array();
        $where_val[] = ['id', 'in', $menus_arr];
        $where_val[] = ['type', '=', 2];
        $houseMenuNewDb = new HouseMenuNew();
        $menus_arr_msg = $houseMenuNewDb->getList($where_val, 'id,fid', 'sort DESC,id asc');
        if ($menus_arr_msg && !$menus_arr_msg->isEmpty()) {
            $menus_arr_msg = $menus_arr_msg->toArray();
            foreach ($menus_arr_msg as &$val) {
                if ($val['fid'] > 0) {
                    if (!in_array($val['fid'], $menus_arr)) {
                        $menus_arr[] = $val['fid'];
                    }
                    $ffid=0;
                    $tmpArr=$houseMenuNewDb->getOne(['id' => $val['fid']],'fid');
                    if($tmpArr && !$tmpArr->isEmpty()){
                        $tmpArr = $tmpArr->toArray();
                        $ffid= $tmpArr['fid'];
                    }
                    if ($ffid > 0) {
                        if (!in_array($ffid, $menus_arr)) {
                            $menus_arr[] = $ffid;
                        }
                        $fffid=0;
                        $tmpArr=$houseMenuNewDb->getOne(['id' => $ffid],'fid');
                        if($tmpArr && !$tmpArr->isEmpty()){
                            $tmpArr = $tmpArr->toArray();
                            $fffid= $tmpArr['fid'];
                        }
                        if ($fffid > 0 && !in_array($fffid, $menus_arr)) {
                            $menus_arr[] = $fffid;
                        }
                    }
                }
            }
            if ($menus_arr_msg && is_array($menus_arr_msg) && !in_array(430, $menus_arr_msg)) {
                $menus_arr_msg[] = 430;
            }
        }
        return $menus_arr;
    }

    public function addGroup($addData=array()){
        if(empty($addData)){
            return false;
        }
        $houseAdminGroupDb = new HouseAdminGroup();
        $idd=$houseAdminGroupDb->addGroup($addData);
        return $idd;
    }

    //更新数据
    public function updateGroup($where=array(),$updateArr=array()){
        if(empty($where) || empty($updateArr)){
            return false;
        }
        $houseAdminGroupDb = new HouseAdminGroup();
        return $houseAdminGroupDb->updateGroup($where,$updateArr);
    }
    //删除
    public function deleteGroup($where=array()){

        if(empty($where)){
            return false;
        }
        $houseAdminGroupDb = new HouseAdminGroup();
        return $houseAdminGroupDb->deleteGroup($where);
    }
}
