<?php


namespace app\life_tools\model\service;


use app\common\model\db\Area;
use app\common\model\service\AreaService;
use app\life_tools\model\db\LifeToolsHouseCardActivity;
use app\life_tools\model\db\LifeToolsHousesFloorPlan;
use app\life_tools\model\db\LifeToolsHousesList;
use app\life_tools\model\db\LifeToolsHouseSpreadUser;
use app\life_tools\model\db\LifeToolsHouseVisitLog;
use app\life_tools\model\service\house\HouseCardService;
use app\life_tools\model\service\house\HouseVisitLogService;
use think\facade\Db;

class LifeToolsHousesListService
{
    /**
     * 楼盘列表
     */
    public function getHousesFloorList($param)
    {
        $where=[['is_del','=',0]];
        $list['list']=(new LifeToolsHousesList())->getSome($where,true,'houses_id desc',($param['page']-1)*$param['pageSize'],$param['pageSize'])->toArray();
        $list['total']=(new LifeToolsHousesList())->getCount($where);
        return $list;
    }
    /**
     * @return \json
     * 获取楼盘信息
     */
    public function getHousesFloorMsg($param)
    {
        if($param['houses_id']){
            $list['list']=$this->getDetail($param);
        }else{
            $list['list']=[];
        }
        $list['areas'] = (new AreaService())->getAllArea(2, "area_type,area_id,area_pid,area_id as value,area_name as label",'children',false);
        return $list;
    }

    /**
     * @return \json
     * 获取楼盘户型信息
     */
    public function getHousesFloorPlanMsg($param)
    {
        $where=[['pigcms_id','=',$param['pigcms_id']]];
        $detail=(new LifeToolsHousesFloorPlan())->getDetailMsg($where);
        if(!empty($detail)){
            $detail['image']=replace_file_domain($detail['image']);
        }
        return $detail;
    }
    /**
     * 在售楼盘前端列表
     */
    public function getApiList($param)
    {
        $field="*";
        $where=[['is_del','=',0]];

        if ($param['long']!=0 && $param['lat']!=0) {
            $field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$param['lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$param['lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$param['long']}*PI()/180-`long`*PI()/180)/2),2)))*1000) AS juli";
        }
        switch($param['sort_name']){
            case 'juli':// 按距离排序
                if ($param['long']>0 && $param['lat']>0) {
                    $order['juli'] = $param['sort_type'];
                }
                break;
            case 'price':// 按价格排序
                $order['price'] = $param['sort_type'];
        }

        // 默认排序
        $order['houses_id'] = 'DESC';

        if(!empty($param['content'])){
            array_push($where,['title','like','%'.$param['content'].'%']);
        }

        $list=(new LifeToolsHousesList())->getApiList($where,$field,$order,$param['page'],$param['pageSize']);//列表
        if(!empty($list['data'])){
            foreach ($list['data'] as $k=>$v){
               if(!empty($v['cover_image'])){
                   $list['data'][$k]['cover_image']=thumb_img($v['cover_image'], 400, 400, 'fill');
               }else{
                   $list['data'][$k]['cover_image']="";
               }

               $list['data'][$k]['juli'] = isset($v['juli']) ? get_format_number($v['juli']) : 0;
                if (!empty($v['juli'])) { //计算距离
                    $list['data'][$k]['juli'] = get_range($v['juli']);
                }

                $pro_name="";
                $city_name="";
                $area_name="";
                if($v['province_id']){
                    $pro_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$v['province_id']]);
                }

                if($v['city_id']){
                    $city_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$v['city_id']]);
                }

                if($v['area_id']){
                    $area_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$v['area_id']]);
                }

                $list['data'][$k]['area_name']=$pro_name.'、'.$city_name.' '.$area_name;
                $list['data'][$k]['url']=get_base_url()."pages/lifeTools/buildingSale/detail?id=".$v['houses_id'];
                //是否参与活动
                $list['data'][$k]['is_join_activity'] = 0;
                $activity_info = (new LifeToolsHouseCardActivity())->where([['','exp',Db::raw("FIND_IN_SET(".$v['houses_id'].",house_ids)")],['is_del','=',0],['start_time','<',time()],['end_time','>',time()]])->findOrEmpty()->toArray();
                if($activity_info){
                    $list['data'][$k]['is_join_activity'] = 1;
                }
            }
        }
        //浏览记录
        if($param['uid']){
            (new HouseVisitLogService())->saveLog($param['uid']);
        }
        
        return $list;
    }

    /**
     * @return \json
     * 详情
     */
    public function getDetail($param)
    {
        $uid = $param['uid']??0;
        $share_uid = $param['share_uid']??0;
        $visit_type = $param['visit_type']??'';
        $where=[['houses_id','=',$param['houses_id']],['is_del','=',0]];
        $detail=(new LifeToolsHousesList())->getDetailMsg($where);
        if(empty($detail)){
            throw new \think\Exception(L_("楼盘信息已被删除"), 1003);
        }
        $detail['cover_image']=empty($detail['cover_image'])?"":thumb_img($detail['cover_image'], 1000, 1000, 'fill');
        $pro_name="";
        $city_name="";
        $area_name="";

        if($detail['province_id']){
            $pro_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$detail['province_id']]);
        }

        if($detail['city_id']){
            $city_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$detail['city_id']]);
        }

        if($detail['area_id']){
            $area_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$detail['area_id']]);
        }

        $detail['area_name']=$pro_name.'、'.$city_name.' '.$area_name;
        $arr=[];
        if(!empty($detail['images'])){
            $imgs=explode(',',$detail['images']);
            foreach ($imgs as $k=>$v){
                $arr[]=thumb_img($v, 400, 400, 'fill');
            }
        }
        $detail['swiper_image']=$arr;
        $floor=(new LifeToolsHousesFloorPlan())->getSome(['houses_id'=>$detail['houses_id']],'*','pigcms_id desc')->toArray();
        if(!empty($floor)){
            foreach ($floor as $key=>$val){
                if(!empty($val['image'])){
                    $floor[$key]['image']=replace_file_domain($val['image']);
                }
            }
        }
        $detail['floor_arr']=$floor;
        $detail['content'] = replace_file_domain_content_img($detail['content']);
        (new LifeToolsHousesList())->setInc(['houses_id'=>$detail['houses_id']],'view_count');
        
        //分享信息
        $detail['share_url'] = cfg('site_url').'/packapp/plat/pages/lifeTools/buildingSale/detail?id='.$param['houses_id'].'&share_uid=';
        $detail['share_code_url'] = '';
        if($uid){
            //通过分享进来建立上下级关系
            if($share_uid&&$uid!=$share_uid){
                //查询是否已有上级
                $p_user = (new LifeToolsHouseSpreadUser())->where(['uid'=>$uid])->findOrEmpty()->toArray();
                if(!$p_user){
                    (new LifeToolsHouseSpreadUser())->save(['uid'=>$uid,'p_uid'=>$share_uid,'add_time'=>time()]);
                }
            }
            $detail['share_url'] = cfg('site_url').'/packapp/plat/pages/lifeTools/buildingSale/detail?id='.$param['houses_id'].'&share_uid='.$uid;
            $detail['share_code_url'] = cfg('site_url').'/index.php?g=Index&c=Recognition&a=get_own_qrcode&'.http_build_query(['qrCon'=>$detail['share_url'].'&visit_type=scan']);

            //浏览记录
            if($visit_type){
                (new HouseVisitLogService())->saveLog($uid,$visit_type,$param['houses_id']);
            }
        }
        
        //是否参与活动
        $detail['is_join_activity'] = 0;
        $activity = (new HouseCardService())->checkActivity($param['houses_id']);
        if($activity){
            $detail['is_join_activity'] = 1;
        }
        return $detail;
    }


    /**
     * @return \json
     * 修改状态
     */
    public function updateStatus($param)
    {
        $where=[['houses_id','=',$param['houses_id']]];

        $data=[];
        if(isset($param['is_del'])){
            $data['is_del']=$param['is_del'];
        }
        if(empty($data)){
            return false;
        }
        $ret=(new LifeToolsHousesList())->updateThis($where,$data);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }


    /**
     * @return \json
     * 修改状态
     */
    public function updateHousesFloorPlanStatus($param)
    {
        $where=[['pigcms_id','=',$param['pigcms_id']]];
        $ret=(new LifeToolsHousesFloorPlan())->where($where)->delete();
        return $ret;
    }
    /**
     *楼盘户型列表
     */
    public function getChildList($param)
    {
        $where=[['houses_id','=',$param['houses_id']]];
        $list=(new LifeToolsHousesFloorPlan())->getSome($where,'*','pigcms_id desc')->toArray();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                if(!empty($v['image'])){
                    $list[$k]['image']=replace_file_domain($v['image']);
                }
            }
        }
        return $list;
    }

    /**
     *楼盘新增编辑
     */
    public function editHouseFloor($param){
        if(!empty($param['longlat'])){
            $arr=explode(",",$param['longlat']);
            $param['long']=$arr[0];
            $param['lat']=$arr[1];
        }
        unset($param['longlat']);
        if(empty($param['houses_id'])){//新增
            $param['add_time']=time();
            $ret=(new LifeToolsHousesList())->add($param);
        }else{//编辑
            $where=[['houses_id','=',$param['houses_id']]];
            $ret=(new LifeToolsHousesList())->updateThis($where,$param);
            if($ret!==false){
                $ret=true;
            }
        }
        return $ret;
    }

    /**
     *楼盘户型新增编辑
     */
    public function editHouseFloorPlan($param)
    {
        if(empty($param['pigcms_id'])){//新增
            $param['add_time']=time();
            $ret=(new LifeToolsHousesFloorPlan())->add($param);
        }else{
            $where=[['pigcms_id','=',$param['pigcms_id']]];
            $ret=(new LifeToolsHousesFloorPlan())->updateThis($where,$param);
            if($ret!==false){
                $ret=true;
            }
        }
        return $ret;
    }
}