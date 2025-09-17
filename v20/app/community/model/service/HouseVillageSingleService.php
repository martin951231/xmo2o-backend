<?php


namespace app\community\model\service;

use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\traits\house\HouseTraits;
use think\facade\Cache;
use think\facade\Db;

class HouseVillageSingleService
{
    public $db_house_village_single = '';
    public $db_house_village_floor = '';
    public $db_house_village_layer = '';
    public $db_house_village_user_vacancy = '';
	protected $cacheTagKey;

	use HouseTraits;

    public function __construct()
    {
        $this->db_house_village_single = new HouseVillageSingle();
        $this->db_house_village_floor = new HouseVillageFloor();
        $this->db_house_village_layer = new HouseVillageLayer();
        $this->db_house_village_user_vacancy = new HouseVillageUserVacancy();
    }

	protected function setCacheTag($village_id)
	{
		$this->cacheTagKey = 'village:cache:'.$village_id;
	}

	protected function getCacheTagKey()
	{
		return $this->cacheTagKey;
	}


    /**
     * 楼栋信息
     * @author lijie
     * @date_time 2021/07/16
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getSingleInfo($where=[],$field=true)
    {
        $data = $this->db_house_village_single->getOne($where,$field);
        return $data;
    }

    public function getSingleRawInfo($whereRaw,$field=true)
    {
        $data = $this->db_house_village_single->getRawOne($whereRaw,$field);
        return $data;
    }

    /**
     * 获取单元信息
     * @author lijie
     * @date_time 2021/09/22
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getFloorInfo($where=[],$field=true)
    {
        $data = $this->db_house_village_floor->getOne($where,$field);
        return $data;
    }

    public function getFloorRawInfo($whereRaw,$field=true)
    {
        $data = $this->db_house_village_floor->getRawOne($whereRaw,$field);
        return $data;
    }
    /**
     * 获取楼层信息
     * @author lijie
     * @date_time 2021/09/22
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getLayerInfo($where=[],$field=true)
    {
        $data = $this->db_house_village_layer->getOne($where,$field);
        return $data;
    }
    public function getLayerRawInfo($whereRaw,$field=true)
    {
        $data = $this->db_house_village_layer->getRawOne($whereRaw,$field);
        return $data;
    }

    public function addSingleInfo($param)
    {
        $id = $this->db_house_village_single->addOne($param);
        return $id;
    }
    public function addFloorInfo($param)
    {
        $id = $this->db_house_village_floor->addOne($param);
        return $id;
    }
    public function addLayerInfo($param)
    {
        $id = $this->db_house_village_layer->addOne($param);
        return $id;
    }

    public function saveSingleInfo($where, $save) {
        $id = $this->db_house_village_single->saveOne($where, $save);
        return $id;
    }

    public function saveFloorInfo($where, $save) {
        $id = $this->db_house_village_floor->saveOne($where, $save);
        return $id;
    }

    public function saveLayerInfo($where, $save) {
        $id = $this->db_house_village_layer->saveOne($where, $save);
        return $id;
    }
    public function getLayerInfoCount($where) {
        $tCount= $this->db_house_village_layer->getCount($where);
        return $tCount>0 ? $tCount:0;
    }
    public function saveUserBindInfo($where, $save) {
        $houseVillageUserBind =new HouseVillageUserBind();
        $id = $houseVillageUserBind->saveOne($where, $save);
        return $id;
    }
    public function getOneRoom($where=[],$field=true)
    {
        $dataObj = $this->db_house_village_user_vacancy->getOne($where,$field);
        $roomData=array();
        if($dataObj && !$dataObj->isEmpty()){
            $roomData=$dataObj->toArray();
        }
        return $roomData;
    }
    public function saveUserRoomInfo($where, $save) {
        $id = $this->db_house_village_user_vacancy->saveOne($where, $save);
        return $id;
    }

    public function addUserRoomInfo($param)
    {
        $id = $this->db_house_village_user_vacancy->addOne($param);
        return $id;
    }
    public function getRoomInfoSum($where=[],$field='housesize'){
        $sumData = $this->db_house_village_user_vacancy->getSum($where,$field);
        return $sumData;
    }
    /**
     * 楼栋组织架构
     * @param array $where
     * @param bool $field
     * @param string $select
     * @param string $title
     * @param string $isAllChoose
     * @return array
     * @author lijie
     * @date_time 2021/06/10
     */
    public function getNav($where = [], $field = true, $select = 1, $title = '房产', $isAllChoose = 0)
    {
	    $cacheKey = 'village:'.$where['village_id'].':getNav:'.md5(\json_encode($where).\json_encode($field).\json_encode($select));
	    $data = Cache::get($cacheKey);
		if (!empty($data)){
			return  $data;
		}
	    $this->setCacheTag($where['village_id']);
        $single_lists = $this->db_house_village_single->getList($where, $field,'sort DESC,id desc')->toArray();
        $items = [];
        foreach ($single_lists as $key => $value) {
            $list['permission_id'] = $value['id'];
            $list['title'] = $this->traitAutoFixLouDongTips($value['single_name'],true);
            if (intval($isAllChoose) == 1) {
                $list['disabled'] = false;
            } elseif ($select==1){
                $list['disabled'] = true;
            }else{
                $list['disabled'] = false;
            }

            $list['slots'] = ['icon' => 'cluster'];
            $list['scopedSlots'] = ['switcherIcon' => 'single'];
            $list['key'] = $value['id'] . '|' . $value['single_name'] . '|' . 'single';
            $items[] = $list;
        }
        if ($items){
            $res = $this->getTree($items, 'floor', $select, $isAllChoose);
        }else{
            $res = [];
        }
        if (!$title) {
            $title = '房产';
        } 
        $data[0]['permission_id'] = 0;
        $data[0]['title']         = $title;
        if (intval($isAllChoose) == 1) {
            $data[0]['disabled'] = false;
        } elseif ($select==1){
            $data[0]['disabled'] = true;
        }else{
            $data[0]['disabled'] = false;
        }
        $data[0]['scopedSlots'] = ['switcherIcon' => 'house'];
        if(empty($res)){
            if (intval($isAllChoose) == 1) {
                $data[0]['disabled'] = false;
            } else {
                $data[0]['disabled'] = true;
            }
            $data[0]['scopedSlots'] = ['switcherIcon' => 'house_empty'];
        }
        $data[0]['slots'] = ['icon' => 'cluster'];
        $data[0]['key'] = '0|房产|house';
        $data[0]['children'] = $res;
        try {
            Cache::tag($this->getCacheTagKey())->set($cacheKey, $data, 1800);
        }catch (\Exception $e){
                
        }
        return $data;
    }

    public function getTree($arr = [], $type = '',$select=0, $isAllChoose = 0)
    {
        foreach ($arr as $k => $v) {
            if ($type == 'floor') {
                $floor_list = $this->get_floor_list(['single_id' => $v['permission_id'], 'status' => 1], 'floor_id,floor_name');
                $list = [];
                foreach ($floor_list as $k1 => $v1) {
                    $list[$k1]['title'] = $this->traitAutoFixDanyuanTips($v1['floor_name'],true);
                    if(intval($isAllChoose)==1){
                        $list[$k1]['disabled'] = false;
                    } elseif($select==1){
                        $list[$k1]['disabled'] = true;
                    }else{
                        $list[$k1]['disabled'] = false;
                    }
                    $list[$k1]['slots'] = $v1['slots'];
                    $list[$k1]['scopedSlots'] = ['switcherIcon' => 'floor'];
                    $list[$k1]['parent_permission_id'] = $v['permission_id'];
                    $list[$k1]['permission_id'] = $v1['floor_id'];
                    $list[$k1]['key'] = $v1['key'];
                }
                $list = $this->getTree($list, 'layer',$select, $isAllChoose);
                if(empty($list)){
                    if(intval($isAllChoose)==1){
                        $arr[$k]['disabled'] = false;
                    } else {
                        $arr[$k]['disabled'] = true;
                    }
                    $arr[$k]['scopedSlots'] = ['switcherIcon' => 'single_empty'];
                }
                $arr[$k]['children'] = $list;
            } elseif ($type == 'layer') {
                $layer_list = $this->get_layer_list(['floor_id' => $v['permission_id'], 'status' => 1], 'id,layer_name');
                $list = [];
                foreach ($layer_list as $k2 => $v2) {
                    $list[$k2]['title'] = $this->traitAutoFixLoucengTips($v2['layer_name'],true);
                    if(intval($isAllChoose)==1){
                        $list[$k2]['disabled'] = false;
                    } elseif($select==1){
                        $list[$k2]['disabled'] = true;
                    }else{
                        $list[$k2]['disabled'] = false;
                    }
                    $list[$k2]['slots'] = $v2['slots'];
                    $list[$k2]['scopedSlots'] = ['switcherIcon' => 'layer'];
                    $list[$k2]['parent_permission_id'] = $v['permission_id'];
                    $list[$k2]['permission_id'] = $v2['id'];
                    $list[$k2]['key'] = $v2['key'];
                }
                $list = $this->getTree($list, 'room',0,$isAllChoose);
                if(empty($list)){
                    if(intval($isAllChoose)==1){
                        $arr[$k]['disabled'] = false;
                    } else {
                        $arr[$k]['disabled'] = true;
                    }
                    $arr[$k]['scopedSlots'] = ['switcherIcon' => 'floor_empty'];
                }
                $arr[$k]['children'] = $list;
            } elseif ($type == 'room') {
                $room_list = $this->get_room_list([['layer_id','=',$v['permission_id']],['is_del','=',0],['status','in',[1,2,3]]], 'pigcms_id,room',$v['permission_id']);
                $list = [];
                foreach ($room_list as $k3 => $v3) {
                    $list[$k3]['title'] = $v3['title'];
                    $list[$k3]['disabled'] = false;
                    $list[$k3]['slots'] = $v3['slots'];
                    $list[$k3]['scopedSlots'] = ['switcherIcon' => 'room'];
                    $list[$k3]['key'] = $v3['key'];
                }
                if(empty($list)){
                    if(intval($isAllChoose)==1){
                        $arr[$k]['disabled'] = false;
                    } else {
                        $arr[$k]['disabled'] = true;
                    }
                    $arr[$k]['scopedSlots'] = ['switcherIcon' => 'layer_empty'];
                }
                $arr[$k]['children'] = $list;
            }
        }
        return $arr;
    }

    /**
     * 单元列表
     * @param array $where
     * @param bool $field
     * @return array
     * @author lijie
     * @date_time 2021/06/10
     */
    public function get_floor_list($where = [], $field = true)
    {
		$cacheKey = 'village:get_floor_list:'.md5(\json_encode($where).\json_encode($field));
	    $floor_list = Cache::get($cacheKey);
		if (!empty($floor_list)){
			return  $floor_list;
		}
        $floor_list = $this->db_house_village_floor->getList($where, $field,'sort DESC,floor_id desc')->toArray();
        if ($floor_list) {
            foreach ($floor_list as $k => $v) {
                $floor_list[$k]['slots'] = ['icon' => 'user'];
                $floor_list[$k]['key'] = $v['floor_id'] . '|' . $v['floor_name'] . '|' . 'floor';
            }
        }
        try {
           Cache::tag($this->getCacheTagKey())->set($cacheKey,$floor_list,7200-mt_rand(10,30));
        }catch (\Exception $e){

        }
        return $floor_list;
    }


    /**
     * 楼层列表
     * @param array $where
     * @param bool $field
     * @return array
     * @author lijie
     * @date_time 2021/06/10
     */
    public function get_layer_list($where = [], $field = true)
    {
	    $cacheKey = 'village:get_layer_list:'.md5(\json_encode($where).\json_encode($field));
	    $layer_list = Cache::get($cacheKey);
	    if (!empty($layer_list)){
		    return  $layer_list;
	    }
        $layer_list = $this->db_house_village_layer->getList($where, $field,'sort DESC,id desc')->toArray();
        if ($layer_list) {
            foreach ($layer_list as $k => $v) {
                $layer_list[$k]['slots'] = ['icon' => 'user'];
                $layer_list[$k]['key'] = $v['id'] . '|' . $v['layer_name'] . '|' . 'layer';
            }
        }
	    
        try {
            Cache::tag($this->getCacheTagKey())->set($cacheKey,$layer_list,7200-mt_rand(10,30));
        }catch (\Exception $e){

        }
        return $layer_list;
    }


    /**
     * 房间列表
     * @param array $where
     * @param int $layer_id
     * @param bool $field
     * @return array
     * @author lijie
     * @date_time 2021/06/10
     */
    public function get_room_list($where = [], $field = true,$layer_id=0)
    {
	    $cacheKey = 'village:get_layer_list:'.md5(\json_encode($where).\json_encode($field));
	    $layer_list = Cache::get($cacheKey);
	    if (!empty($layer_list)){
		    return  $layer_list;
	    }
        $room_list = $this->db_house_village_user_vacancy->getList($where, $field, 'sort desc, pigcms_id asc')->toArray();
        $data = [];
        if ($room_list) {
//            if($layer_id){
//                $db_house_village_layer = new HouseVillageLayer();
//                $layer_info = $db_house_village_layer->getOne(['id'=>$layer_id],'layer_name');
//                $layer_name = $layer_info['layer_name'];
//            }else{
//                $layer_name = '';
//            }
            foreach ($room_list as $k => $v) {
//                if($layer_name){
//                    if(strlen($v['room']) == 1){
//                        $v['room'] = $layer_name . '0' . $v['room'];
//                    }elseif (strlen($v['room']) == 2){
//                        $v['room'] = $layer_name .$v['room'];
//                    }
//                }
                $data[$k]['title'] = $v['room'];
                $data[$k]['disabled'] = false;
                $data[$k]['slots'] = ['icon' => 'user'];
                $data[$k]['key'] = $v['pigcms_id'] . '|' . $v['room'] . '|' . 'room';
            }
        }
        try {
            Cache::tag($this->getCacheTagKey())->set($cacheKey,$data,7200-mt_rand(10,30));
        }catch (\Exception $e){

        }
	    
        return $data;
    }
    
    public function getUserBindVacancyId($whereArr=array()){
        if(empty($whereArr)){
            return array();
        }
        $vacancyIdArr=array();
        $houseVillageUserBind =new HouseVillageUserBind();
        $userBindObj=$houseVillageUserBind->getUserBindVacancyId($whereArr, 'vacancy_id');
        if($userBindObj && !$userBindObj->isEmpty()){
            $userBindArr=$userBindObj->toArray();
            foreach ($userBindArr as $vcc){
                $vacancyIdArr[]=$vcc['vacancy_id'];
            }
        }
        return $vacancyIdArr;
    }
    
    /**
     * 房间列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return array
     * @author zhubaodi
     * is_user_bind 0全部 1有绑定住户 2无绑定住户
     * @date_time 2021/06/18
     */
    public function getVacancylist($id, $where = [], $field = true, $page, $limit,$extra_data=array())
    {
        // 房间ID
        $vacancy_id = [];
        // 根据收费标准获取所有已绑定的车位对应的业主信息
        $userBind = $this->getPositionToVacancy($id,'vacancy_id');
        if(!empty($userBind)){
            $vacancy_id = array_column($userBind,'vacancy_id');
        }
        //不需要已绑定的
        if(!empty($extra_data) && isset($extra_data['nobinded']) && $extra_data['nobinded']){
            $xwhere=array();
            if(isset($extra_data['xwhere'])){
                $xwhere=$extra_data['xwhere'];
            }
            $ruleBindVacancy=$this->getRuleBindToVacancy($id,'vacancy_id',$xwhere);
            if(!empty($ruleBindVacancy)){
                $vacancyArr = array_column($ruleBindVacancy,'vacancy_id');
                $vacancy_id=array_merge($vacancy_id,$vacancyArr);
            }
        }

        if(!empty($extra_data) && isset($extra_data['is_user_bind']) && isset($extra_data['xwhere'])){
            $xwhere=array();
            foreach ($extra_data['xwhere'] as $fieldKey=>$xvv){
                $xwhere[]=array($fieldKey,'=',$xvv);
            }
            $xwhere[]=array('status','=',1);
            $xwhere[]=array('vacancy_id','>',0);
            if($extra_data['is_user_bind']==1){
                //1有绑定住户
                $userBindVacancyIdArr=$this->getUserBindVacancyId($xwhere);
                if(!empty($userBindVacancyIdArr) && !empty($vacancy_id)){
                    $new_vacancy_id=array_diff($userBindVacancyIdArr,$vacancy_id);
                    if(!empty($new_vacancy_id)){
                        $where[] = ['a.pigcms_id','in',$new_vacancy_id];
                    }else{
                        $where[] = ['a.pigcms_id','=',0];
                    }
                    $vacancy_id=array();
                }elseif(!empty($userBindVacancyIdArr) && empty($vacancy_id)){
                    $where[] = ['a.pigcms_id','in',$userBindVacancyIdArr];
                }
            }else if($extra_data['is_user_bind']==2){
                //2无绑定住户
                $userBindVacancyIdArr=$this->getUserBindVacancyId($xwhere);
                $vacancy_id=array_merge($vacancy_id,$userBindVacancyIdArr);
            }
        }
        // 同一收费标准不能绑定同一业主的房产和车位  过滤已绑定车位的房间
        if(!empty($vacancy_id)){
            $vacancy_id=array_unique($vacancy_id);
            $where[] = ['a.pigcms_id','not in',$vacancy_id];
        }
        $count = $this->db_house_village_user_vacancy->count($where);
        $room_list = $this->db_house_village_user_vacancy->getLists($where, $field, $page, $limit)->toArray();
        if (empty($id)) {
            throw new \think\Exception("收费标准id不能为空");
        }
        $db_rule = new HouseNewChargeRuleService();
        $ruleInfo = $db_rule->getRuleInfo( $id);
        if (empty($ruleInfo)) {
            throw new \think\Exception("收费标准信息不存在");
        }
        if ($ruleInfo['charge_type']==2){
            $is_show = 1;
        }else{
            if (empty($ruleInfo['fees_type'])||($ruleInfo['bill_type']==1&&empty($ruleInfo['unit_gage']))||($ruleInfo['charge_type']==1&&empty($ruleInfo['unit_gage']))){
                $is_show= 2;
            } else {
                $is_show = 1;
            }
        }
        $houseVillageUserBind =new HouseVillageUserBind();
        if (!empty($room_list)) {
            foreach ($room_list as $k => $v) {
                $room_list[$k] = $v;
                $room_list[$k]['user_bind_status']='无';
                $whereArr=array();
                $whereArr[]=array('village_id','=',$v['village_id']);
                $whereArr[]=array('status','=',1);
                $whereArr[]=array('vacancy_id','=',$v['pigcms_id']);
                $userBindObj=$houseVillageUserBind->getOne($whereArr, 'vacancy_id');
                if($userBindObj && !$userBindObj->isEmpty()){
                    $room_list[$k]['user_bind_status']='有';
                }
                $room_list[$k]['show'] = true;
                if (empty($ruleInfo['fees_type'])||($ruleInfo['bill_type']==1&&empty($ruleInfo['unit_gage']))||($ruleInfo['charge_type']==1&&empty($ruleInfo['unit_gage']))){
                    $room_list[$k]['is_show']= 2;
                } else {
                    $room_list[$k]['is_show'] = 1;
                }

            }
        }

        $data = [];
        $data['is_show'] = $is_show;
        $data['count'] = $count;
        $data['list'] = $room_list;
        $data['total_limit'] = $limit;
        return $data;
    }

	/**
	 * 获取楼栋列表
	 * @param        $where
	 * @param bool   $field
	 * @param array $order
	 *
	 * @return array|\think\Model|null
	 */
    public function getList($where,$field = true,$order=['id'=>'DESC']) {
        $list = $this->db_house_village_single->getList($where,$field,$order);
        return $list;
    }

	/**
	 * 根据条件获取楼栋总数
	 * @param $where
	 *
	 * @return int
	 */
	public function getBuildingCount($where)
	{
		return $this->db_house_village_single->getCount($where);
	}


    /**
     * 根据条件获取单元总数
     * @param $where
     *
     * @return int
     */
    public function getSingleFloorCount($where)
    {
        return $this->db_house_village_floor->getCount($where);
    }

    public function getSingleFloorList($where,$field = true,$order='floor_id desc') {
        $listObj = $this->db_house_village_floor->getList($where,$field,$order);
        $list=array();
        if($listObj && !$listObj->isEmpty()){
            $list=$listObj->toArray();
            foreach ($list as $kk=>$vv){
                $list[$kk]['single_name']='';
                $list[$kk]['add_time_str']='';
                if($vv['add_time']>0){
                    $list[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);
                }
                $whereArr=array();
                $whereArr[]=array('id','=',$vv['single_id']);
                $singleObj=$this->getSingleInfo($whereArr,'single_name');
                if($singleObj && !$singleObj->isEmpty()){
                    $list[$kk]['single_name']=$singleObj['single_name'];
                }
            }
        }
        return $list;
    }

    /**
     * 根据条件获取楼层总数
     * @param $where
     *
     * @return int
     */
    public function getSingleLayerCount($where)
    {
        return $this->db_house_village_layer->getCount($where);
    }

    public function getSingleLayerList($where,$field = true,$order='id desc') {
        $listObj = $this->db_house_village_layer->getList($where,$field,$order);
        $list=array();
        if($listObj && !$listObj->isEmpty()){
            $list=$listObj->toArray();
            foreach ($list as $kk=>$vv){
                $list[$kk]['single_name']='';
                $list[$kk]['floor_name']='';
                $list[$kk]['add_time_str']='';
                if(isset($vv['add_time']) && $vv['add_time']>0){
                    $list[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);
                }
                $whereArr=array();
                $whereArr[]=array('id','=',$vv['single_id']);
                $singleObj=$this->getSingleInfo($whereArr,'single_name');
                if($singleObj && !$singleObj->isEmpty()){
                    $list[$kk]['single_name']=$singleObj['single_name'];
                }

                $whereArr=array();
                $whereArr[]=array('floor_id','=',$vv['floor_id']);
                $singleObj=$this->getFloorInfo($whereArr,'floor_name');
                if($singleObj && !$singleObj->isEmpty()){
                    $list[$kk]['floor_name']=$singleObj['floor_name'];
                }

            }
        }
        return $list;
    }
    /**
     * 根据收费标准获取所有已绑定的车位对应的业主信息
     * User: zhanghan
     * Date: 2022/1/12
     * Time: 14:13
     * @param $id
     * @param string $field
     * @return array
     */
    public function getPositionToVacancy($id,$field = 'vacancy_id'){
        $standard_bind = new HouseNewChargeStandardBind();
        $user_bind = new HouseVillageUserBind();
        $position_bind = new HouseVillageBindPosition();

        $where_position = [];
        $where_position[] = ['rule_id','=',$id];
        $where_position[] = ['bind_type','=',2];
        $where_position[] = ['is_del','=',1];
        // 获取该收费规则下的所有绑定的车位id
        $position_id = [];
        $positionList = $standard_bind->getLists1($where_position,'position_id');
        if($positionList && !$positionList->isEmpty()){
            $position_id = array_column($positionList->toArray(),'position_id');
        }

        $userList = [];
        // 根据绑定车辆ID查询小区用户ID
        if(!empty($position_id)){
            $where_bind = [];
            $where_bind[] = ['position_id','in',$position_id];
            // 获取小区用户对应的车辆信息
            $positionBindList = $position_bind->getList($where_bind,'user_id');
            if($positionBindList && !$positionBindList->isEmpty()){
                $userList = array_column($positionBindList->toArray(),'user_id');
            }
        }

        // 根据小区用户ID查询绑定的房间ID
        $userBindList = [];
        if(!empty($userList)){
            $where_user = [];
            $where_user[] = ['pigcms_id','in',$userList];
            // 获取车位对应的用户信息房间信息
            $userList = $user_bind->getLimitUserList($where_user,0,$field);
            if($userList && !$userList->isEmpty()){
                $userBindList = $userList->toArray();
            }
        }
        return $userBindList;
    }
    //获取已绑定的房间
    public function  getRuleBindToVacancy($rule_id,$field = 'vacancy_id',$xwhere=array()){
        $standard_bind = new HouseNewChargeStandardBind();
        $whereArr = [];
        $whereArr[] = ['rule_id','=',$rule_id];
        $whereArr[] = ['bind_type','=',1];
        $whereArr[] = ['is_del','=',1];
        if($xwhere && isset($xwhere['single_id']) && ($xwhere['single_id']>0)){
            $whereArr[] = ['single_id','=',$xwhere['single_id']];
        }
        if($xwhere && isset($xwhere['floor_id']) && ($xwhere['floor_id']>0)){
            $whereArr[] = ['floor_id','=',$xwhere['floor_id']];
        }
        if($xwhere && isset($xwhere['layer_id']) && ($xwhere['layer_id']>0)){
            $whereArr[] = ['layer_id','=',$xwhere['layer_id']];
        }
        if($xwhere && isset($xwhere['vacancy_id']) && ($xwhere['vacancy_id']>0)){
            $whereArr[] = ['vacancy_id','=',$xwhere['vacancy_id']];
        }
        // 获取该收费规则下的所有绑定的车位id
        $tmpList = $standard_bind->getLists1($whereArr,$field);
        $tmpdatas=array();
        if($tmpList && !$tmpList->isEmpty()){
            $tmpdatas=$tmpList->toArray();
        }
        return $tmpdatas;
    }

	/**
	 * 软删除楼栋
	 * @param $where
	 * @param $data
	 *
	 * @return bool
	 */
	public function softDeleteBuilding($where,$data)
	{
		return $this->db_house_village_single->where($where)->data($data)->save();
	}

    /**
     * 软删除单元
     * @param $where
     * @param $data
     *
     * @return bool
     */
    public function softDeleteFloor($where,$data)
    {
         $ret=$this->db_house_village_floor->where($where)->data($data)->save();
         return $ret;
    }

    /**
     * 软删除楼层
     * @param $where
     * @param $data
     *
     * @return bool
     */
    public function softDeleteLayer($where,$data)
    {
        $ret=$this->db_house_village_layer->where($where)->data($data)->save();
        return $ret;
    }
    
    /**
     * 删除指定楼栋下所有的单元、楼层、房间、住户
     */
    public function deleteSingleAllInfo($village_id,$single_id)
    {
        $return  = [
            'error'=>0,
            'msg'=>''
        ];
        $del_time=time();
        Db::startTrans();
        try {
            //删除住户（工作人员不删除）
            (new HouseVillageUserBind())->saveOne([
                ['village_id','=',$village_id],
                ['single_id','=',$single_id],
                ['type','<>',4],
            ],['status'=>4]);
            //删除房间
            $houseVillageUserVacancyDb=new HouseVillageUserVacancy();
            $houseVillageUserVacancyDb->saveOne([
                'village_id'=>$village_id,
                'single_id'=>$single_id
            ],['is_del'=>1,'del_time'=>$del_time]);
            
            //作废订单
            $whereArr=array();
            $whereArr[]=array('v.village_id','=',$village_id);
            $whereArr[]=array('v.single_id','=',$single_id);
            $whereArr[]=array('o.village_id','=',$village_id);
            $whereArr[]=array('o.is_paid','=',2);
            $whereArr[]=array('o.is_discard','=',1);
            $houseVillageUserVacancyDb->discardOrderByRoom($whereArr,['o.is_discard' => 2, 'o.discard_reason' => '房间被删除了', 'o.update_time' =>$del_time]);
            //删除楼层
            $this->softDeleteLayer([
                'village_id'=>$village_id,
                'single_id'=>$single_id
            ],['status'=>4]);
            //删除单元
            $this->softDeleteFloor([
                'village_id'=>$village_id,
                'single_id'=>$single_id
            ],['status'=>4]);
            //删除楼栋
            $this->softDeleteBuilding([
                'village_id'=>$village_id,
                'id'=>$single_id
            ],['status' => 4]);
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $return['error'] = 1;
            $return['msg'] = $e->getMessage();
            return $return;
        }
        return $return;
    }
    
    public function addRoomWaterBalanceLog($data){
        $houseVillageRoomWaterBalanceLog=new \app\community\model\db\HouseVillageRoomWaterBalanceLog();
        $insert_id= $houseVillageRoomWaterBalanceLog->addOneData($data);
        return $insert_id;
    }
}
