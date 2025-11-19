<?php
/**
 * 可视化页面相关
 * @author weili
 * @date 2020/9/7
 */

namespace app\community\model\service;

use app\community\model\db\AreaStreetServiceNavCat;
use app\community\model\db\HouseAdverCategory;
use app\community\model\db\HouseAdver;
use app\community\model\db\Area;
use app\community\model\db\AreaStreetInfoContent;
class VisualizationService
{
    /**
     * Notes: 获取轮播图列表
     * @param $cat_key
     * @param $village_id
     * @param $area_id
     * @param $level
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/9/7 18:02
     */
    public function bannerList($cat_key,$street_id,$area_id,$level,$page,$limit)
    {
        $dbHouseAdverCategory = new HouseAdverCategory();
        $dbHouseAdver = new HouseAdver();
        $dbArea = new Area();
        $car_where[] = ['cat_key','=',$cat_key];
        $now_category = $dbHouseAdverCategory->getFind($car_where);

        $condition_adver[] = ['cat_id','=',$now_category['cat_id']];
        if($street_id)
            $condition_adver[] = ['street_id','=',$street_id];
        else
            $condition_adver[] = ['community_id','=',$area_id];
        $condition_adver[] = ['status','<>','-1'];
        $condition_adver[] = ['village_id','=',0];
//        if ($area_id) {
//            if ($level == 1) {
//                $temp = $dbArea->getOne(['area_id' => $area_id]);
//                if($temp['area_type'] == 1){
//                    $city_list = $dbArea->get_arealist_by_areaPid($temp['area_id'],true);
//                    $area_id = array();
//                    foreach($city_list as $value){
//                        $area_id[] = $value['area_id'];
//                    }
//                }else if($temp['area_type'] == 2){
//                    $area_id = $temp['area_id'];
//                }else{
//                    $area_id = $temp['area_pid'];
//                }
//            }
//            if(is_array($area_id)){
//                $condition_adver['city_id'] = array('in',$area_id);
//            }else{
//                $condition_adver['city_id'] = $area_id;
//            }
//        }
        $count = $dbHouseAdver->getCount($condition_adver);
        $adver_list = $dbHouseAdver->getList($condition_adver,true,'sort desc',$page,$limit);

        if ($adver_list) {
            foreach ($adver_list as &$v) {
                $city = $dbArea->getOne(['area_id' => $v['city_id']]);
                if (empty($city)) {
                    $v['city_id'] = '通用';
                } else {
                    $v['city_id'] = $city['area_name'];
                }
                $v['last_time'] = date('Y-m-d H:i:s',$v['last_time']);
                $v['pic']  = dispose_url($v['pic']);
                if($v['status']!=1){
                    $v['status']=2;
                }
            }
        }

        $data['list'] = $adver_list;
        $data['cat_id'] = $now_category['cat_id'];
        $data['now_category'] = $now_category;
        $data['count'] = $count;
        return $data;
    }

    /**
     * Notes: 获取对应的banner详情
     * @param $id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/9/8 15:20
     */
    public function getAdvert($id)
    {
        $dbHouseAdver = new HouseAdver();
        $where[] = ['id','=',$id];
        $info = $dbHouseAdver->getFind($where);
        if(!$info){
            throw new \think\Exception('该广告不存在');
        }
        if($info['pic']) {
            $info['pic'] = dispose_url($info['pic']);
        }
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes:修改/添加banner
     * @param $data
     * @param $id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/9/8 15:20
     */
    public function saveAdvert($data,$id)
    {
        $dbHouseAdver = new HouseAdver();
        if($id) {
            $where[] = ['id', '=', $id];
            $info = $dbHouseAdver->getFind($where);
            $res = $dbHouseAdver->editFind($where, $data);
            if ($res) {
                return $data;
            } else {
                throw new \think\Exception('编辑失败');
            }
        }else{
            $res = $dbHouseAdver->addFind($data);
            if($res){
                return $data;
            }else{
                throw new \think\Exception('添加失败');
            }
        }
    }
    public function getClass()
    {

    }
    /**
     * Notes: 删除banner
     * @param $id
     * @return bool
     * @throws \Exception
     * @author: weili
     * @datetime: 2020/9/8 15:30
     */
    public function del($id)
    {
        $dbHouseAdver = new HouseAdver();
        $where[] = ['id','=',$id];
        $info = $dbHouseAdver->getFind($where);
        $res = $dbHouseAdver->del($where);
        return $res;
    }

    /**
     * 街道功能库二级分类数据
     * @author: liukezhu
     * @date : 2022/6/8
     * @param $street_id
     * @param $area_id
     * @param $type
     * @param $page
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStreetLibraryClass($street_id, $community_id=0,$type='',$page=0){
        $house_village_service = new HouseVillageService();
        $service_area_street_event_category = new AreaStreetEventCategoryService();
        $base_url = $house_village_service->base_url;
        $site_url=cfg('site_url');
        $site_url=trim($site_url,'/');
        $url=$site_url.$base_url;
        $url=str_replace('//','/',$url);
        $limit=10;
        $list=[];
        $count=0;
        if($type == 'workerOrder'){
            $where[] = ['area_id','=',$street_id];
            $where[] = ['status','=',1];
            $where[] = ['cat_fid','=',0];
            $data = $service_area_street_event_category->getCategoryList($where,'cat_id as id,cat_name as title',$page,$limit,'sort desc,cat_id DESC');
            if ($data && !$data->isEmpty()) {
                $data=$data->toArray();
                foreach ($data as $v){
                    $list[]=[
                        'id'=>$v['id'],
                        'title'=>$v['title'],
                        'url'=>$url.'pages/village/grid/addevent?cat_id='.$v['id'].'&source=1&area_street_id='.$street_id
                    ];
                }
                $count = $service_area_street_event_category->getCategoryCount($where);
            }
        }elseif ($type == 'streetServiceNavCat') {
            $page = $page > 0 ? $page : 1;
            $limit = 10;
            $areaStreetServiceNavCat= new AreaStreetServiceNavCat();
        
                $whereArr = array();
                $tmp_street_id=$street_id;
                if ($community_id > 0) {
                    $tmp_street_id=$community_id;
                    $whereArr[] = array('community_id', '=', $community_id);
                    $whereArr[] = array('cat_type', '=', 1);   //社区
                } else if ($community_id < 1) {
                    $whereArr[] = array('street_id', '=', $street_id);
                    $whereArr[] = array('community_id', '=', 0);
                    $whereArr[] = array('cat_type', '=', 0);   //街道
                }
                $urlBaste=$site_url.'/packapp/stCommPage/';
                $whereArr[] = array('del_time', '<', 10);
                $retObj=$areaStreetServiceNavCat->getListData($whereArr,'id,street_id,community_id,cat_name,level_show','id DESC',$page,$limit);
                if($retObj && !$retObj->isEmpty()){
                    $dataList= $retObj->toArray();
                    foreach ($dataList as $kk=>$vv){
                        $list[]=[
                            'id'=>$vv['id'],
                            'title'=>$vv['cat_name'],
                            'url'=>$urlBaste.'pages/street/index/streetServiceCentre?cat_id='.$vv['id'].'&area_street_id='.$tmp_street_id,
                        ];
                    }
                    $count = $areaStreetServiceNavCat->getCount($whereArr);
                }
        }
        $res['list'] = $list;
        $res['count'] = $count;
        $res['total_limit'] = $limit;
        return $res;
    }

    public function getOneStreetInfoContent($where,$field='*',$orderby='id DESC')
    {
        $areaStreetInfoContent= new AreaStreetInfoContent();
        $retObj=$areaStreetInfoContent->getOneData($where,$field,$orderby);
        $dataInfo=array();
        if($retObj && !$retObj->isEmpty()){
            $dataInfo= $retObj->toArray();
            $dataInfo['cover_img_full']=$dataInfo['cover_img'] ? replace_file_domain($dataInfo['cover_img']):'';
            $dataInfo['xcontent']=$dataInfo['xcontent'] ? htmlspecialchars_decode($dataInfo['xcontent'],ENT_QUOTES):'';
        }
        return $dataInfo;
    }

    public function addOneStreetInfoContent($addData=array())
    {
        $areaStreetInfoContent= new AreaStreetInfoContent();
        $insert_id=$areaStreetInfoContent->addOneData($addData);
        return $insert_id;
    }

    public function editOneStreetInfoContent($where,$saveData)
    {
        $areaStreetInfoContent= new AreaStreetInfoContent();
        $ret=$areaStreetInfoContent->saveOneData($where,$saveData);
        return $ret;
    }
    public function saveOneStreetInfoContent($data,$idd=0,$whereArr){
        if($idd>0){
            $whereArr[]=array('id','=',$idd);
            $data['update_time']=time();
            return $this->editOneStreetInfoContent($whereArr,$data);
        }else{
            $data['add_time']=time();
            return $this->addOneStreetInfoContent($data);
        }
    }
    public function delStreetInfoContent($where=array())
    {
        $areaStreetInfoContent= new AreaStreetInfoContent();
        $ret=$areaStreetInfoContent->delDatas($where);
        return $ret;
    }
    public function getStreetInfoContentList($where,$field='*',$orderby='id DESC',$page = 0, $page_size = 10,$is_from_wap=false)
    {
        $areaStreetInfoContent= new AreaStreetInfoContent();
   
        $returnArr=array('list'=>array(),'count'=>0,'page_size'=>$page_size);
        $count=$areaStreetInfoContent->getCount($where);
        $count=$count ? $count:0;
        $returnArr['count']=$count;
        if($count<1){
            return $returnArr;
        }
        $retObj=$areaStreetInfoContent->getListData($where,$field,$orderby,$page,$page_size);
        $dataList=array();
        if($retObj && !$retObj->isEmpty()){
            $dataList= $retObj->toArray();
            foreach ($dataList as $kk=>$vv){
                if($vv['cover_img']){
                    $dataList[$kk]['cover_img']=replace_file_domain($vv['cover_img']);
                }
                $dataList[$kk]['status_str']=$vv['status']==1 ? '已启用' :'已关闭';
                if($is_from_wap){
                    $dataList[$kk]['add_time_str']=date('m-d H:i',$vv['add_time']);
                }else{
                    $dataList[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);
                }
            }
        }
        $returnArr['list']=$dataList;
        return $returnArr;
    }
}