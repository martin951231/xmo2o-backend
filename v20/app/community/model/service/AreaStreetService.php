<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/24 13:16
 * 街道社区相关业务层
 */
namespace app\community\model\service;

use app\common\model\db\Area;
use app\community\model\db\AreaStreet;
use app\community\model\db\AreaStreetConfig;
use app\community\model\db\AreaStreetLoginLog;
use app\community\model\db\HouseVillageGridMember;
use app\community\model\db\StreetCommunitySuggests;
use app\community\model\db\StreetCommunitySuggestsReply;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseProperty;
use app\traits\CommonLogTraits;
use customization\customization;
use file_handle\FileHandle;

class AreaStreetService {
    use customization;
	use CommonLogTraits;

    public $suggests_status = [
        1 => '未回复',
        2 => '已回复'
    ];

    public $position=[
        'lng'=>114.527570,
        'lat'=>26.319529
    ];

    /**
     * 获取单个街道社区管理员
     * @author: wanziyang
     * @date_time: 2020/4/24 11:31
     * @param array $where 对应查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function getAreaStreet($where,$field = true,$orderby='is_open desc,area_id desc') {
        // 初始化 街道社区管理员 数据层
        $db_area_street = new AreaStreet();
        $info = $db_area_street->getOne($where,$field,$orderby);
        if (!$info || $info->isEmpty()) {
            $info = [];
        } else {
            $info = $info->toArray();
        }
        if($info && isset($info['logo'])){
            $info['logo'] =$info['logo'] ?  replace_file_domain($info['logo']): cfg('site_url') . '/v20/public/static/community/images/community.png';
        }
        return $info;
    }
    public function saveAreaStreet($where,$saveArr = array()) {
        // 初始化 街道社区管理员 数据层
        $db_area_street = new AreaStreet();
        $ret = $db_area_street->saveOne($where,$saveArr);
        return $ret;
    }
    /**
     * 添加登录记录
     * @author: wanziyang
     * @date_time: 2020/4/24 14:56
     * @param array $data 对应修改信息数组
     * @return array|null|\think\Model
     */
    public function addLoginLog($data) {
        // 初始化 街道社区管理员 数据层
        $db_area_street_login_log = new AreaStreetLoginLog();
        $area_id = $db_area_street_login_log->addOne($data);
        return $area_id;
    }


    /**
     * 获取街道下小区相关人数相关数量
     * @author: wanziyang
     * @date_time: 2020/5/22 16:25
     * @param array $where
     * @param int $bind_type
     * @return int
     */
    public function getStreetVillageUserNum($where,$bind_type=0) {
        // 初始化 数据层
        $db_area_street = new AreaStreet();
        $count = $db_area_street->getStreetVillageUserNum($where,$bind_type);
        if (!$count) {
            $count =0;
        }
        return $count;
    }


    /**
     * 获取社区下小区相关人数相关数量
     * @author: wanziyang
     * @date_time: 2020/5/22 16:26
     * @param array $where
     * @return int
     */
    public function getCommunityVillageUserNum($where) {
        // 初始化 数据层
        $db_area_street = new AreaStreet();
        $count = $db_area_street->getCommunityVillageUserNum($where);
        if (!$count) {
            $count =0;
        }
        return $count;
    }

    /**
     * 获取街道下社区数量
     * @author lijie
     * @date_time 2020/09/10
     * @param $where
     * @return int
     */
    public function getStreetCommunityNum($where)
    {
        $db_area_street = new AreaStreet();
        $count = $db_area_street->getStreetCommunityNum($where);
        return $count;
    }

    public function getStreetVillageNum($where,$bind_type)
    {
        $db_area_street = new AreaStreet();
        $count = $db_area_street->getStreetVillageNum($where,$bind_type);
        return $count;
    }

    /**
     * 获取社区下小区相关人数相关数量
     * @author: wanziyang
     * @date_time: 2020/5/22 17:05
     * @param array $data
     * @return int
     */
    public function addIndex($data) {
        $db_area_street = new AreaStreet();
        $queuData = [
            'logData' => [
                'tbname' => '街道社区表',
                'table' => 'area_street',
                'client' => '街道|社区',
                'trigger_path' => '街道管理->基本信息',
                'addtime' => time(),
                'area_id' => $data['area_id']
            ]
        ];
        if (isset($data['area_id']) && $data['area_id']>0) {
            // 编辑
            $area_id = $data['area_id'];
			$oldData = $this->getAreaStreet(['area_id'=>$area_id]);
            unset($data['area_id']);
	        $queuData['logData']['trigger_type'] =  $this->getUpdateNmae();
	        $queuData['newData']                 = $data;
	        $queuData['oldData']                 = $oldData;
            $set = $db_area_street->saveOne(['area_id'=>$area_id],$data);
            if (!$set) {
                $area_id = 0;
            }
        } else {
	        $queuData['logData'] = ['trigger_type' => $this->getAddLogName()];
	        $queuData['newData'] = $data;
			$queuData['oldData'] = [];
            $area_id = $db_area_street->addOne($data);
        }

		$this->laterLogInQueue($queuData);
        return $area_id;
    }
    
    public function updateVr720yunConfig($data)
    {
        $area_id = isset($data['area_id']) ? intval($data['area_id']) : 0;
        if (! $area_id) {
            return false;
        }
        $vr_720yun_switch = isset($data['vr_720yun_switch']) ? intval($data['vr_720yun_switch']) : 0;
        $vr_720yun_url = isset($data['vr_720yun_url']) ? trim($data['vr_720yun_url']) : '';
        $dbAreaStreetConfig = new AreaStreetConfig();
        $whereConfig = [];
        $whereConfig[] = ['area_id', '=', $area_id];
        $configInfo = $dbAreaStreetConfig->getFind($whereConfig, 'area_id, vr_720yun_switch, vr_720yun_url');
        if (isset($configInfo['area_id']) && $configInfo['area_id']) {
            $dataUpdate = ['vr_720yun_switch' => $vr_720yun_switch, 'vr_720yun_url' => $vr_720yun_url];
            $dbAreaStreetConfig->saveData($whereConfig, $dataUpdate);
        } else {
            $dataAdd = ['area_id' => $area_id, 'vr_720yun_switch' => $vr_720yun_switch, 'vr_720yun_url' => $vr_720yun_url];
            $dbAreaStreetConfig->addData($dataAdd);
        }
        return $area_id;
    }

    /**
     * 获取街道社区留言建议
     * @author: wanziyang
     * @date_time: 2020/5/27 15:39
     * @param array $where
     * @param int $page
     * @param bool|string $field
     * @param string $order
     * @param int $page_size
     * @return array|null|\think\Model
     */
    public function getLimitSuggestsList($where,$page=0,$field =true,$order='suggestions_id DESC',$page_size=10) {

        $db_street_community_suggests = new StreetCommunitySuggests();
        $list = $db_street_community_suggests->getLimitSuggestsList($where,$page,$field, $order, $page_size);
        $count = $db_street_community_suggests->getLimitSuggestsCount($where);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            foreach($list as &$val) {
                $val['status_txt'] = $this->suggests_status[$val['status']];
                $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                $val['content'] = htmlspecialchars_decode($val['content']);
                if($page>0 && !empty($val['phone'])){
                    $val['phone']=phone_desensitization($val['phone']);
                }
            }
        }
        $out = [
            'list' => $list,
            'count' => $count ? $count : 0
        ];
        return $out;
    }


    /**
     * 获取街道社区留言建议
     * @author: wanziyang
     * @date_time: 2020/5/27 15:39
     * @param array $where
     * @param int $page
     * @param bool|string $field
     * @param string $order
     * @param int $page_size
     * @return array|null|\think\Model
     */
    public function getCommunitySuggestsList($where,$page=0,$field =true,$order='suggestions_id DESC',$page_size=10) {
        $db_street_community_suggests = new StreetCommunitySuggests();
        $list = $db_street_community_suggests->getLimitSuggestsList($where,$page,$field, $order, $page_size);
        $count = $db_street_community_suggests->getLimitSuggestsCount($where);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            foreach($list as &$val) {
                $val['status_txt'] = $this->suggests_status[$val['status']];
                $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                $val['add_time']=$val['add_time_txt'];
                $val['content'] = htmlspecialchars_decode($val['content']);
            }
        }
        $out = [
            'list' => $list,
            'count' => $count ? $count : 0
        ];
        return $out;
    }

    /**
     * 获取街道社区留言建议详情
     * @author: wanziyang
     * @date_time: 2020/5/27 17:19
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function getSuggestsDetail($where, $field =true) {

        $db_street_community_suggests = new StreetCommunitySuggests();
        $detail = $db_street_community_suggests->getSuggestsDetail($where, $field);
        $count = $db_street_community_suggests->getLimitSuggestsCount($where);
        if (!$detail || $detail->isEmpty()) {
            $detail = [];
        } else {
            $detail = $detail->toArray();
            $detail['status_txt'] = $this->suggests_status[$detail['status']];
            $detail['add_time_txt'] = date('Y-m-d H:i:s',$detail['add_time']);
            $detail['content'] = htmlspecialchars_decode($detail['content']);
            $whereReply = [];
            $whereReply[] = ['suggestions_id', '=', $detail['suggestions_id']];
            $replayInfo = $this->getLimitSuggestsReplyList($whereReply);
            if ($replayInfo['list']) {
                $detail['reply_content'] = $replayInfo['list'];
            }
            if ($detail['img']) {
                $detail['img_arr'] = unserialize($detail['img']);
                if ($detail['img_arr']) {
                    foreach ($detail['img_arr'] as $key=>$val) {
                        $detail['img_arr'][$key] = replace_file_domain($val);
                    }
                }
            } else {
                $detail['img_arr'] = [];
            }
        }
        $out = [
            'info' => $detail
        ];
        return $out;
    }
    /**
     * 获取街道社区留言建议
     * @author: wanziyang
     * @date_time: 2020/5/27 17:26
     * @param array $where
     * @param int $page
     * @param bool|string $field
     * @param string $order
     * @param int $page_size
     * @return array|null|\think\Model
     */
    public function getLimitSuggestsReplyList($where,$page=0,$field =true,$order='reply_id ASC',$page_size=10) {
        $db_street_community_suggests_reply = new StreetCommunitySuggestsReply();
        $list = $db_street_community_suggests_reply->getLimitSuggestsReplyList($where,$page,$field, $order, $page_size);
        $count = $db_street_community_suggests_reply->getLimitSuggestsReplyCount($where);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            foreach($list as &$val) {
                $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                $val['reply_content'] = htmlspecialchars_decode($val['reply_content']);
            }
        }
        $out = [
            'list' => $list,
            'count' => $count ? $count : 0
        ];
        return $out;
    }

    /**
     * 添加回复
     * @author: wanziyang
     * @date_time: 2020/5/28 10:11
     * @param array $where
     * @param array $data
     * @return bool|int
     */
    public function saveSuggestsOne($where, $data) {
        $db_street_community_suggests = new StreetCommunitySuggests();
        $set = $db_street_community_suggests->saveOne($where,$data);
        return $set;
    }

    /**
     * 添加留言
     * @author lijie
     * @date_time 2020/09/14
     * @param $data
     * @return int|string
     */
    public function addSuggest($data)
    {
        $db_street_community_suggests = new StreetCommunitySuggests();
        $res = $db_street_community_suggests->addOne($data);
        return $res;
    }

    /**
     * 添加回复
     * @author: wanziyang
     * @date_time: 2020/5/27 17:51
     * @param array $data
     * @return bool|int
     */
    public function addSuggestsReplyOne($data) {
        $db_street_community_suggests_reply = new StreetCommunitySuggestsReply();
        $reply_id = $db_street_community_suggests_reply->addOne($data);
        return $reply_id;
    }

	/**
	 * 删除留言建议以及对应的恢复
	 * @param $where
	 */
	public function deleteSuggestsAndReply($suggestions_id)
	{
		(new StreetCommunitySuggests())->deleteStreetCommunitySuggests(['suggestions_id'=>$suggestions_id]);
		(new StreetCommunitySuggestsReply())->deleteStreetCommunitySuggestsReply(['suggestions_id'=>$suggestions_id]);
		return true;
	}

    /**
     * 获取街道列表
     * @author lijie
     * @date_time 2020/09/09 10:27
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStreetLists($where,$field=true,$page=1,$limit=20,$order='area_id DESC')
    {
        $db_area_street = new AreaStreet();
        $data = $db_area_street->getLists($where,$field,$page,$limit,$order);
        $list=array();
        if($data && !$data->isEmpty()){
            $list=$data->toArray();
            foreach ($list as $k=>&$val){
                if(empty($val['logo'])){
                    $val['logo'] = cfg('site_url') . '/v20/public/static/community/images/community.png';
                }else{
                    $val['logo'] = replace_file_domain($val['logo']);
                }
            }
        }
        return $list;
    }

    public function getAreaStreetLists($where,$field=true,$page=1,$limit=20,$order='area_id DESC')
    {
        $db_area_street = new AreaStreet();
        $data = $db_area_street->getLists($where,$field,$page,$limit,$order);
        $list=array('count'=>0,'list'=>array(),'pageSize'=>$limit);
        if($data && !$data->isEmpty()){
            $listTem=$data->toArray();
            foreach ($listTem as $k=>&$val){
                if(empty($val['logo'])){
                    $val['logo'] = cfg('site_url') . '/v20/public/static/community/images/community.png';
                }else{
                    $val['logo'] = replace_file_domain($val['logo']);
                }
                
                $area_info=$this->get_area_info($val['area_pid'],'street');
                $val['area_info_str']=$area_info['province'].' / '.$area_info['city'].' / '.$area_info['county'].' / '.$area_info['street_name'].' / '.$val['area_name'];
                $val['is_open_str']='显示';
                $val['access_str']='访问';
                $val['access_url']='/admin.php?g=System&c=House&a=v20AreaStreetLogin&area_id='.$val['area_id'];
                if($val['is_open']<1){
                    $val['is_open_str']='隐藏';
                }
            }
            $list['list']=$listTem;
            $count=$db_area_street->getStreetCommunityNum($where);
            $count=$count ? $count:0;
            $list['count']=$count;
        }
        return $list;
    }
    public function getAreaStreetVillageLists($where,$field=true,$page=1,$limit=20){
        $db_house_village = new HouseVillage();
        $db_house_property = new HouseProperty();
        $list=array('count'=>0,'list'=>array(),'pageSize'=>$limit);
        $house_village=$db_house_village->getVillageList($where,$field,$page,$limit);
        if($house_village && !$house_village->isEmpty()){
            $villages=$house_village->toArray();
            $house_property_arr=array();
            foreach ($villages as $kk=>$vv){
                $last_time_str='从未登录';
                $status_str='正常';
                if($vv['property_id']>0 && isset($house_property_arr[$vv['property_id']])){
                    $villages[$kk]['property_name']=$house_property_arr[$vv['property_id']]['property_name'];
                }else if($vv['property_id']>0){
                    $house_property=$db_house_property->get_one(['id'=>$vv['property_id']],'id,property_name,property_phone,property_address');
                    if($house_property && !$house_property->isEmpty()){
                        $house_property=$house_property->toArray();
                        $house_property_arr[$vv['property_id']]=$house_property;
                        $villages[$kk]['property_name']=$house_property['property_name'];
                    }
                }

                if($vv['last_time']>100){
                    $last_time_str=date('Y-m-d H:i:s',$vv['last_time']);
                }
                $villages[$kk]['access_str']='访问';
                $villages[$kk]['access_url']='/admin.php?g=System&c=House&a=v20_village_login&village_id='.$vv['village_id'];
                if($vv['status']<1){
                    $status_str='待完善信息';
                }else if($vv['status']>1){
                    $status_str='禁止';
                    $villages[$kk]['access_str']='禁止状态';
                    $villages[$kk]['access_url']='';
                }
                $villages[$kk]['last_time_str']=$last_time_str;
                $villages[$kk]['status_str']=$status_str;

            }
            $list['list']=$villages;
            $count=$db_house_village->getNum($where);
            $count=$count ? $count:0;
            $list['count']=$count;
        }
        return $list;
    }
    public function get_area_info($area_id=0,$xtype='street')
    {
        $area_info=array('street_name'=>'','county'=>'','city'=>'','province'=>'');
        $db_area_db = new Area();
        if($xtype=='street'){
            $db_area_street = new AreaStreet();
            $whereArr=array();
            $whereArr[]=array('area_id','=',$area_id);
            $whereArr[]=array('area_type','=',0);
            $streetObj=$db_area_street->getOne($whereArr,'area_id,area_pid,area_name');
            if($streetObj && !$streetObj->isEmpty()){
                $area_info['street_name']=$streetObj['area_name'];
                $tmpAreaObj=$db_area_db->getAreaByAreaId($streetObj['area_pid']);
                if($tmpAreaObj && !$tmpAreaObj->isEmpty()){
                    $area_info['county']=$tmpAreaObj['area_name'];
                    $tmpCityObj=$db_area_db->getAreaByAreaId($tmpAreaObj['area_pid']);
                    if($tmpCityObj && !$tmpCityObj->isEmpty()){
                        $area_info['city']=$tmpCityObj['area_name'];
                        $tmpProvinceObj=$db_area_db->getAreaByAreaId($tmpCityObj['area_pid']);
                        if($tmpProvinceObj && !$tmpProvinceObj->isEmpty()){
                            $area_info['province']=$tmpProvinceObj['area_name'];
                        }
                    }
                }
            }
        }
        return $area_info;
    }
    //上传街道logo
    public function uploads($file, $putFile='street')
    {
        if($file){
            // 上传到本地服务器
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile( $putFile,$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $imgurl = '/upload/'.$savename;
            $params = ['savepath'=>'/upload/' . $imgurl];

            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                invoke_cms_model('Image/oss_upload_image', $params);
            }
            return $imgurl;
        }else{
            throw new \think\Exception('请上传图片有效');
        }
    }


    /**
     * 查询社区
     * @author: liukezhu
     * @date : 2021/11/8
     * @param $where
     * @param bool $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field=true) {
        $db_area_street = new AreaStreet();
        $info = $db_area_street->getLists($where,$field,0);
        if($info && !$info->isEmpty()){
            $info=$info->toArray();
        }else{
            $info=array();
        }
        return $info;
    }

    /**
     * 获取街道经纬度
     * @author: liukezhu
     * @date : 2022/4/23
     * @param $area_id
     * @return array
     */
    public function getAreaStreetFind($area_id){
        $db_area_street = new AreaStreet();
        $info=$db_area_street->getOne(['area_id'=>$area_id]);
        if($info){
            $data=[
                'lng'=>$info['long'],
                'lat'=>$info['lat'],
            ];
        }else{
            $data=$this->position;
        }
        return $data;
    }

    /**
     * 获取可视化导航数据
     * @author: liukezhu
     * @date : 2022/5/11
     * @param $info
     * @param int $type
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAreaStreetVisualizeNav($info,$type=0){
        if($info && $info['area_type'] == 1){ //登录是社区 查询上级街道数据
            $info = $this->getAreaStreet(['area_id'=>$info['area_pid']]);
        }
        $configCustomizationService=new ConfigCustomizationService();
        $cityCountyManageFuncJudge=$configCustomizationService->getCityCountyManageFuncJudge();
        if(!isset($info['nav_info']) || empty($info['nav_info'])){
            $visualize_title='智慧社区管理平台';
            //todo 针对客户定制
            if($this->hasSuiChuan()){
                $visualize_title='江西省遂川县智慧社区管理平台';
            }
            $data=[
                'visualize_title'=>$visualize_title,
                'visualize_nav1'=>'社区党建',
                'visualize_nav2'=>'人口分析',
                'visualize_nav3'=>'事件分析',
                //'visualize_nav4'=>'疫情防控数据统计',
                'visualize_nav4'=>'民族统计',
            ];
            if($cityCountyManageFuncJudge>0){
                $data['visualize_nav4']='民族统计';
            }
        }else{
            $data=json_decode($info['nav_info'],true);
        }
        if($info && $type == 1){
            if($info['long'] && $info['lat']){
                $data['position']=[
                    'lng'=>(float)$info['long'],
                    'lat'=>(float)$info['lat'],
                ];
            }else{
                $data['position']=$this->position;
            }
            $area=(new Area())->getAreaByAreaId($info['area_pid']);
            $data['position']['zoomLevel']=42;
            $data['position']['area_name']=$area['area_name'];
            $data['area_name']=$area['area_name'];
            $data['position']['icon']=cfg('site_url').'/static/images/house/community_committee/dw-wd.png';
        }else{
            $data['area_name']='';
        }
        if($cityCountyManageFuncJudge>0){
            $data['cityCountyManageFuncJudge']=1;
            
            $data['visualize_nav4']='民族统计';
            
        }
        
        return $data;
    }


    /**
     * 街道功能库
     * @author: liukezhu
     * @date : 2022/6/2
     * @param $admin
     * @return mixed
     */
    public function getAreaStreetLibrary($admin){
        $house_village_service = new HouseVillageService();
        $base_url = $house_village_service->base_url;
        $url=cfg('site_url').$base_url;
        $app_arr[] = ['title'=>'小区首页','url'=>$url.'pages/village_menu/index?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'投票表决','url'=>$url.'pages/street/vote/index?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
        $xtitle=cfg('area_street_active_alias');
        if(empty($xtitle)){
            $xtitle='预约活动';
        }
        if($admin['area_type'] == 1){ //社区
            $res = $this->getAreaStreet(['area_id'=>$admin['area_id']],'area_pid');
            $app_arr[] = ['title'=>'街道新闻','url'=>$url.'pages/street/streetNews/streetNews?area_street_id='.$res['area_pid'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'意见箱','url'=>$url.'pages/street/tookPictures/tookPicturesList?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>$xtitle,'url'=>$url.'pages/street/index/volunteersActivityList?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'社区新闻','url'=>$url.'pages/street/streetNews/streetNews?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'党建首页','url'=>$url.'pages/street/streetParty_index?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'党员列表','url'=>$url.'pages/street/index/partyList?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'事项办理材料管理','url'=>$url.'pages/street/matter/matterList?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'党员信息','url'=>$url.'pages/street/index/partyDetail?area_street_id='.$admin['area_pid'],'sub'=>false,'module'=>''];
        }
        else{ //街道
            $app_arr[] = ['title'=>'街道新闻','url'=>$url.'pages/street/streetNews/streetNews?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'意见箱','url'=>$url.'pages/street/tookPictures/tookPicturesList?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>$xtitle,'url'=>$url.'pages/street/index/volunteersActivityList?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'党建首页','url'=>$url.'pages/street/streetParty_index?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'党员列表','url'=>$url.'pages/street/index/partyList?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'事项办理材料管理','url'=>$url.'pages/street/matter/matterList?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
            $app_arr[] = ['title'=>'网格工单上报','url'=>$url.'pages/village/grid/eventList?current=0&type=todo&area_street_id='.$admin['area_id'],'sub'=>true,'module'=>'workerOrder'];
            $app_arr[] = ['title'=>'党员信息','url'=>$url.'pages/street/index/partyDetail?area_street_id='.$admin['area_id'],'sub'=>false,'module'=>''];
        }
        $configCustomizationService = new ConfigCustomizationService();
        $isVr720yunCustomized = $configCustomizationService->getVr720yunCustomized();
        $data['isVr720yunCustomized'] = $isVr720yunCustomized;
        if ($isVr720yunCustomized) {
            $vr720yunCustomizedUrl = "https://www.720yun.com/vr/e8ajOgmaOy5";
            $app_arr[] = ['title' => 'VR实景链接', 'url' => $vr720yunCustomizedUrl];
        }
        $configCustomizationService=new ConfigCustomizationService();
        $is_func_yougejianet_special=$configCustomizationService->getfuncYougejianetSpecial();
        if($is_func_yougejianet_special){
            $app_arr[] = ['title'=>'服务中心','url'=>'','sub'=>true,'module'=>'streetServiceNavCat'];
        }
        $data['list'] = $app_arr;
        return $data;
    }

    //读取对应街道/社区工作人员
    public function checkAreaStreetIdentity($login_info,$user){
        $area_type=$login_info['area_type'];
        $area_id=$login_info['area_id'];
        $house_village_grid_member=new HouseVillageGridMember();
        if($user['worker_id']){
            $grid_member=$house_village_grid_member->getOne([
                ['workers_id','=',$user['worker_id']]
            ],'community_id,business_id,business_type');
            if ($grid_member && !$grid_member->isEmpty()){
                if($grid_member['business_type'] == 2){
                    $area_type=1;
                    $area_id=$grid_member['business_id'];
                }
            }
        }
        return ['area_type'=>$area_type,'area_id'=>$area_id];
    }
    

}