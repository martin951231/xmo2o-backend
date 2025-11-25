<?php
/**
 * 街道导航相关
 * @author weili
 * @date 2020/9/9
 */

namespace app\community\model\service;

use app\community\model\db\AreaStreetNav;
use app\community\model\db\AreaStreetServiceNav;
use app\community\model\db\AreaStreetServiceNavCat;
use app\community\model\db\HouseVillageUserLabel;
use app\community\model\service\HouseVillageService;
class StreetNavService
{
    /**
     * Notes: 获取街道导航列表
     * @author: weili
     * @datetime: 2020/9/9 18:24
     * @param $street_id
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getList($street_id,$page=1,$limit=20,$changeStatus=false)
    {
        $dbStreetNav = new AreaStreetNav();
        $where[] = ['street_id','=',$street_id];
        if($changeStatus){
            $where[] = ['status','<>',4];
        }else{
            $where[] = ['status','=',1];
        }
        $plat_app_id= cfg('pay_wxapp_appid');  //平台小程序
        $plat_app_id=$plat_app_id ? $plat_app_id:'';
        $count = $dbStreetNav->getCount($where);
        $street_nav_list = $dbStreetNav->getList($where,true,'sort desc,id desc',$page,$limit);
        foreach($street_nav_list as $k=>&$v){
            $v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            $v['img']  = dispose_url($v['img']);
            $v['plat_app_id']=$plat_app_id;
            if ($changeStatus && isset($v['status']) && !$v['status']) {
                $v['status'] = 2;
            }
        }
        $data['list'] = $street_nav_list;
        $data['count'] = $count;
        return $data;
    }

    public function getStreetFooterNavs($street_id = 0)
    {
        //定制 前端给的 数据，通过后端返回给前端
        $siteUrl=cfg('site_url');
        $siteUrl= $siteUrl ? rtrim($siteUrl,'/'):'';
        $footerNavs = array(
            "hidden" => false,
            "backgroundColor" => "#FFFFFF",
            "selectedColor" => "#0099CC",
            "color" => "#333333",
            "list" => array(
                array(
                    "hover_pic_path" => $siteUrl.'/static/images/xicon/street_home_active.png',
                    "name" => "首页",
                    "pic_path" => $siteUrl.'/static/images/xicon/street_home.png',
                    "url" => "/pages/street_menu/street_index",
                    "is_tab" => true,
                    "original_page" => "",
                    "wxapp_id" => "",
                    "wxapp_page" => "",
                ),
                array(
                    "hover_pic_path" => $siteUrl.'/static/images/xicon/street_service_active.png',
                    "name" => "服务",
                    "pic_path" => $siteUrl.'/static/images/xicon/street_service.png',
                    "url" => "/pages/street_menu/street_service",
                    "is_tab" => true,
                    "original_page" => "",
                    "wxapp_id" => "",
                    "wxapp_page" => ""
                ),
                array(
                    "hover_pic_path" => $siteUrl.'/static/images/xicon/street_my_active.png',
                    "name" => "我的",
                    "pic_path" => $siteUrl.'/static/images/xicon/street_my.png',
                    "url" => "/pages/village_menu/my",
                    "is_tab" => true,
                    "original_page" => "village_menu_my",
                    "wxapp_id" => "",
                    "wxapp_page" => ""
                )
            ),
            "tabbar_type" => "tabbar"
        );
        return $footerNavs;
    }
    /**
     * Notes: 添加、编辑街道导航
     * @param $data
     * @param $id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/9/9 19:06
     */
    public function saveNav($data,$id)
    {
        $dbStreetNav = new AreaStreetNav();
        if($id) {
            $where[] = ['id', '=', $id];
            $info = $dbStreetNav->getFind($where);
            $res = $dbStreetNav->updateFind($where, $data);
            if ($res) {
                if (array_key_exists('pic',$data) && $data['pic'] && $info['pic']) {
                    unlink('./upload/adver/' . $info['pic']);
                }
                return $data;
            } else {
                throw new \think\Exception('编辑失败');
            }
        }else{
            $res = $dbStreetNav->insertFind($data);
            if($res){
                return $data;
            }else{
                throw new \think\Exception('添加失败');
            }
        }
    }

    /**
     * Notes: 获取街道导航详情
     * @param $id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/9/9 19:07
     */
    public function getStreetNav($id)
    {
        $dbStreetNav = new AreaStreetNav();
        $where[] = ['id','=',$id];
        $info = $dbStreetNav->getFind($where);
        if(!$info){
            throw new \think\Exception('该广告不存在');
        }
        $info['img'] = dispose_url($info['img']);
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 删除街道导航
     * @param $id
     * @return bool
     * @author: weili
     * @datetime: 2020/9/9 19:07
     */
    public function del($id)
    {
        $dbStreetNav = new AreaStreetNav();
        $where[] = ['id','=',$id];
        $info = $dbStreetNav->getFind($where);
        $data['status'] = 4;
        $res = $dbStreetNav->updateFind($where,$data);
        return $res;
    }
    
    public function getOneStreetServiceNav($where,$field='*',$orderby='id DESC')
    {
        $areaStreetServiceNav= new AreaStreetServiceNav();
        $retObj=$areaStreetServiceNav->getOneData($where,$field,$orderby);
        $dataInfo=array();
        if($retObj && !$retObj->isEmpty()){
            $dataInfo= $retObj->toArray();
            $dataInfo['img_url_full']=$dataInfo['img_url'] ? replace_file_domain($dataInfo['img_url']):'';
            if(isset($dataInfo['xcontent']) && $dataInfo['xcontent']){
                $dataInfo['xcontent']=htmlspecialchars_decode($dataInfo['xcontent'],ENT_QUOTES);
            }
        }
        return $dataInfo;
    }

    public function addOneStreetServiceNav($addData=array())
    {
        $areaStreetServiceNav= new AreaStreetServiceNav();
        $insert_id=$areaStreetServiceNav->addOneData($addData);
        return $insert_id;
    }
    
    public function editOneStreetServiceNav($where,$saveData)
    {
        $areaStreetServiceNav= new AreaStreetServiceNav();
        $ret=$areaStreetServiceNav->saveOneData($where,$saveData);
        return $ret;
    }
    public function saveOneStreetServiceNav($data,$idd=0,$whereArr){
        if($idd>0){
            $whereArr[]=array('id','=',$idd);
            $data['update_time']=time();
            return $this->editOneStreetServiceNav($whereArr,$data);
        }else{
            $data['add_time']=time();
            return $this->addOneStreetServiceNav($data);
        }
    }
    public function delOneStreetServiceNav($where=array())
    {
        $areaStreetServiceNav= new AreaStreetServiceNav();
        $ret=$areaStreetServiceNav->delDatas($where);
        return $ret;
    }
    public function getStreetServiceNavList($where,$field='*',$orderby='id DESC',$page = 0, $page_size = 10,$pigcms_id=0)
    {
        $areaStreetServiceNav= new AreaStreetServiceNav();
        $HouseVillageUserLabel=new HouseVillageUserLabel();
        $returnArr=array('list'=>array(),'count'=>0,'page_size'=>$page_size);
        $count=$areaStreetServiceNav->getCount($where);
        $count=$count ? $count:0;
        $returnArr['count']=$count;
        if($count<1){
            return $returnArr;
        }
        $is_party_member_g=0;  //共产党员
        if($pigcms_id>0){
            $fieldStr='user_political_affiliation,user_special_groups,user_focus_groups,user_vulnerable_groups,part_time,logo';
            $user_label=$HouseVillageUserLabel->getOne([['bind_id','=',$pigcms_id]],$fieldStr);
            //政治面貌
            if(isset($user_label['user_political_affiliation']) && $user_label['user_political_affiliation'] && $user_label['user_political_affiliation']==1){
                $is_party_member_g=1;
            }
        }
        $returnArr['is_party_member_g']=$is_party_member_g;
        $retObj=$areaStreetServiceNav->getListData($where,$field,$orderby,$page,$page_size);
        $dataList=array();
        $plat_app_id= cfg('pay_wxapp_appid');  //平台小程序
        $plat_app_id=$plat_app_id ? $plat_app_id:'';
        if($retObj && !$retObj->isEmpty()){
            $dataList= $retObj->toArray();
            foreach ($dataList as $kk=>$vv){
                if($vv['img_url']){
                    $dataList[$kk]['img_url']=replace_file_domain($vv['img_url']);
                }
                $dataList[$kk]['plat_app_id']=$plat_app_id;
                $dataList[$kk]['level_show_str']='全部用户可见';
                $dataList[$kk]['is_need_pop_tips']=0;
                $dataList[$kk]['pop_tips_msg']='';
                if($vv['level_show']==1 && $is_party_member_g<1){
                    $dataList[$kk]['is_need_pop_tips']=1;
                    $dataList[$kk]['pop_tips_msg']='此功能仅对党员用户开放，请联系平台';
                    $dataList[$kk]['level_show_str']='仅党员可见';
                }
                $dataList[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);
                
            }
        }
        $returnArr['list']=$dataList;
        return $returnArr;
    }

    public function getOneStreetServiceNavCat($where,$field='*',$orderby='id DESC')
    {
        $areaStreetServiceNavCat= new AreaStreetServiceNavCat();
        $retObj=$areaStreetServiceNavCat->getOneData($where,$field,$orderby);
        $dataInfo=array();
        if($retObj && !$retObj->isEmpty()){
            $dataInfo= $retObj->toArray();
        }
        return $dataInfo;
    }

    public function addOneStreetServiceNavCat($addData=array())
    {
        $areaStreetServiceNavCat= new AreaStreetServiceNavCat();
        $insert_id=$areaStreetServiceNavCat->addOneData($addData);
        return $insert_id;
    }

    public function editOneStreetServiceNavCat($where,$saveData)
    {
        $areaStreetServiceNavCat= new AreaStreetServiceNavCat();
        $ret=$areaStreetServiceNavCat->saveOneData($where,$saveData);
        return $ret;
    }
    public function saveOneStreetServiceNavCat($data,$idd=0,$whereArr){
        if($idd>0){
            $whereArr[]=array('id','=',$idd);
            $data['update_time']=time();
            return $this->editOneStreetServiceNavCat($whereArr,$data);
        }else{
            $data['add_time']=time();
            return $this->addOneStreetServiceNavCat($data);
        }
    }
    public function delOneStreetServiceNavCat($where=array(),$saveData)
    {
        $areaStreetServiceNavCat= new AreaStreetServiceNavCat();
        $ret=$areaStreetServiceNavCat->saveOneData($where,$saveData);
        return $ret;
    }
    public function getStreetServiceNavCatList($where,$field='*',$orderby='id DESC',$page = 0, $page_size = 10,$pigcms_id=0)
    {
        $areaStreetServiceNavCat= new AreaStreetServiceNavCat();
        $HouseVillageUserLabel=new HouseVillageUserLabel();
        $returnArr=array('list'=>array(),'count'=>0,'page_size'=>$page_size);
        $count=$areaStreetServiceNavCat->getCount($where);
        $count=$count ? $count:0;
        $returnArr['count']=$count;
        if($count<1){
            return $returnArr;
        }
        $is_party_member_g=0;  //共产党员
        if($pigcms_id>0){
            $fieldStr='user_political_affiliation,user_special_groups,user_focus_groups,user_vulnerable_groups,part_time,logo';
            $user_label=$HouseVillageUserLabel->getOne([['bind_id','=',$pigcms_id]],$fieldStr);
            //政治面貌
            if(isset($user_label['user_political_affiliation']) && $user_label['user_political_affiliation'] && $user_label['user_political_affiliation']==1){
                $is_party_member_g=1;
            }
        }
        $returnArr['is_party_member_g']=$is_party_member_g;
        $retObj=$areaStreetServiceNavCat->getListData($where,$field,$orderby,$page,$page_size);
        $dataList=array();
        $plat_app_id= cfg('pay_wxapp_appid');  //平台小程序
        $plat_app_id=$plat_app_id ? $plat_app_id:'';
        $site_url=cfg('site_url');
        $site_url=trim($site_url,'/');
        //$house_village_service = new HouseVillageService();
        //$base_url = $house_village_service->base_url;
        $urlBaste=$site_url.'/packapp/stCommPage/';
        if($retObj && !$retObj->isEmpty()){
            $dataList= $retObj->toArray();
            foreach ($dataList as $kk=>$vv){
                $dataList[$kk]['plat_app_id']=$plat_app_id;
                $dataList[$kk]['level_show_str']='全部用户可见';
                $dataList[$kk]['is_need_pop_tips']=0;
                $dataList[$kk]['pop_tips_msg']='';
                $area_street_id=$vv['street_id'];
                if($vv['community_id']>0){
                    $area_street_id=$vv['community_id'];
                }
                $dataList[$kk]['url_link']=$urlBaste.'pages/street/index/streetServiceCentre?cat_id='.$vv['id'].'&area_street_id='.$area_street_id;
                
                if($vv['level_show']==1 && $is_party_member_g<1){
                    $dataList[$kk]['is_need_pop_tips']=1;
                    $dataList[$kk]['pop_tips_msg']='此功能仅对党员用户开放，请联系平台';
                    $dataList[$kk]['level_show_str']='仅党员可见';
                }
                $dataList[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);

            }
        }
        $returnArr['list']=$dataList;
        return $returnArr;
    }
}