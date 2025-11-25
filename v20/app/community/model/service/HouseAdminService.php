<?php
/**
 * Created by PhpStorm.
 * Author: weili
 * Date Time: 2020/7/8 11:38
**/

namespace app\community\model\service;
use app\community\model\db\HouseAdmin;
use app\community\model\db\HouseAdminGroup;
use app\community\model\db\HouseMenuNew;
use app\community\model\db\HouseWorker;

class HouseAdminService
{
    /**
     * 获取信息
     * @author: weili
     * @datetime:2020/7/8 10:53
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @return array|null|\think\Model
     */
    public function getFind($where,$field=true,$orderBy='wid desc')
    {
        // 初始化 数据层
        $houseAdminDb = new HouseAdmin();
        $houseAdminGroupDb = new HouseAdminGroup();
        $houseMenuNewDb = new HouseMenuNew();
        $info = $houseAdminDb->getOne($where,$field,$orderBy);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }

        if($info && !empty($info['wid'])){
            $info['menus']= (new HouseProgrammeService())->getProgrammeGroupMenus($info['village_id'],$info['wid'],$info['group_id'],$info['menus']);
        }elseif($info && $info['group_id']){
            $group = $houseAdminGroupDb->get_one(['group_id'=>$info['group_id'],'status'=>1],'group_id,group_menus');
            $info['menus'] = $group['group_menus'] ? $group['group_menus'] : '';
        }
//        if($info && $info['group_id']) {
//            $group = $houseAdminGroupDb->get_one(['group_id'=>$info['group_id'],'status'=>1],'group_id,group_menus');
//            $info['menus'] = $group['group_menus'] ? $group['group_menus'] : '';
//        }
        if($info && $info['menus']) {
            $menus = explode(',', $info['menus']);
            $map[] = ['id','in',$menus];
            $map[] = ['status','=',1];
            $menusInfo = $houseMenuNewDb->getList($map,'id');
            $existmenus = [];
            if ($menusInfo) {
                foreach ($menusInfo as $key => $value) {
                    $existmenus[] = $value['id'];
                }
            }
            $info['menus'] = implode(',',$existmenus);
            //fdump($info->toArray(),'$info');
        }
        return $info;
    }
    /**
     * 编辑数据
     * @author weili
     * @param array $where
     * @param array $data
     * @return
    **/
    public function editData($where,$data=array(),$now_admin=array())
    {
        $houseAdminDb = new HouseAdmin();
        $res = $houseAdminDb->save_one($where,$data);
        if(!empty($now_admin) && $now_admin['wid']>0){
            $whereArr=array();
            $whereArr['wid']=$now_admin['wid'];
            $db_HouseWorker = new HouseWorker();
            $updateArr=array();
            $updateArr['phone']=$data['phone'];
            if(isset($data['account']) && !empty($data['account'])){
                $updateArr['account']=$data['account'];
            }
            if(isset($data['pwd']) && !empty($data['pwd'])){
                $updateArr['password']=$data['pwd'];
            }
            if(isset($data['realname']) && !empty($data['realname'])){
                $updateArr['name']=$data['realname'];
            }
            if(isset($data['remarks'])){
                $updateArr['remarks']=$data['remarks'];
            }
            $ret = $db_HouseWorker->editData($whereArr, $updateArr);
        }
        return $res;
    }
    public function getHouseWorker($where,$field='*')
    {
        $db_HouseWorker = new HouseWorker();
        $res = $db_HouseWorker->get_one($where,$field);
        return $res;
    }
    /**
     * 获取单个小区管理员数据信息
     * @author lijie
     * @date_time 2020/07/15
     * @param $where
     * @param $field
     * @return array|\think\Model|null
     */
    public function getAdminInfo($where,$field=true)
    {
        $houseAdminDb = new HouseAdmin();
        $res = $houseAdminDb->getOne($where,$field);
        return $res;
    }
    public function  get_one_normal_house_admin($where,$field='*',$orderBy='a.status ASC'){
        $houseAdminDb = new HouseAdmin();
        $oneAdminObj=$houseAdminDb->get_one_normal_house_admin($where,$field,$orderBy);
        $oneAdmin=array();
        if($oneAdminObj && !$oneAdminObj->isEmpty()){
            $oneAdmin= $oneAdminObj->toArray();
        }
        return $oneAdmin;
    }
    public function  get_house_admin_list($whereArr,$field='*',$page=1,$limit=20)
    {
        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $houseAdminDb = new HouseAdmin();
        $count = $houseAdminDb->getHouseAdminCount($whereArr);
        if ($count > 0) {
            $houseAdminGroupDb = new HouseAdminGroup();
            $dataArr['count'] = $count;
            $res = $houseAdminDb->getHouseAdminLists($whereArr, $field,'id DESC', $page, $limit);
            if (!empty($res)) {
                foreach ($res as $kk => $vv) {
                    $res[$kk]['group_name']='';
                    if($vv['group_id']>0){
                        $tmp_group=$houseAdminGroupDb->get_one(['group_id'=>$vv['group_id']],'group_id,name');
                        if($tmp_group && !$tmp_group->isEmpty()){
                            $tmp_group=$tmp_group->toArray();
                            $res[$kk]['group_name']=$tmp_group['name'];
                        }
                    }
                    $res[$kk]['set_pwd']=0;
                    if(!empty($vv['pwd'])){
                        $res[$kk]['set_pwd']=1;
                    }
                    unset($res[$kk]['pwd']);
                }
                $dataArr['list'] = $res;
            } else {
                $dataArr['list'] = array();
            }
        }
        return $dataArr;
    }
}
