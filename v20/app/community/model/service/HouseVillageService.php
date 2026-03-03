<?php
/**
 * Created by PhpStorm.
 * User: wanziyang
 * Date Time: 2020/4/23 12:00
 */
namespace app\community\model\service;

use app\common\model\service\LangService;
use app\community\model\db\AreaStreet;
use app\community\model\db\Express;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseProperty;
use app\community\model\db\HousePropertyVillageWorkerRelation;
use app\community\model\service\HousePropertyVillageWorkerService;
use app\community\model\db\HouseVillage;
use app\community\model\db\ApplicationBind;
use app\community\model\db\HouseMenuNew;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageUserUnbind;
use app\community\model\db\BbsAricle;
use app\community\model\db\HouseVillageRepairList;
use app\community\model\db\HouseVillageExpress;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\Country;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\User;
use app\community\model\db\HouseWorker;
use app\community\model\service\Car\CarHandleGetInfoService;
use file_handle\FileHandle;
use templateNews;

use app\common\model\service\ConfigDataService;
use app\common\model\service\config\ConfigCustomizationService;
class HouseVillageService {
    public $base_url = '';
    public $street_url = '';

    public function __construct(){
        // if(C('config.local_dev')){
        // $this->base_url = '/packapp/plat_dev/';
        // }else{
        if(cfg('system_type') == 'village'){
            $this->base_url = '/packapp/village/';
        }else{
            $this->base_url = '/packapp/plat/';
        }
        $this->street_url = '/packapp/street/';
        // }
    }

    /**
     * 目前仅用于处理  pages/village/my/ 目录迁移 pages/village_my/
     * @param string $appVersion 版本
     * @param string $deviceId 设备类别 对应POST传参Device-Id
     * @param array $param
     * array ('pageName'=> '路径别名', 'isAll'=> '是否全路径返回 配合路径别名使用', 'urlParam' => '路径后面参数 不用带？','pagePath'=> '具体路径 前面参数就可以不传了 以pageName参数为主')
     * @return bool|string|string[]
     */
    public function villagePagePath($appVersion='',$deviceId='',$param=[]) {
        if (!isset($param['pageName']) && !isset($param['pagePath'])) {
            return '';
        }
        $configDataService = new ConfigDataService();
        $deviceIdVersion = 0;
        $packappVersion = 0;
        $deviceIdTimes = 0;
        $newPage = false;
        $isSave = false;
        $deviceIdKey = '';
        $deviceIdTimeKey = '';
        if ($deviceId) {
            $deviceIdKey = $deviceId.'LatestVersion';
            $deviceIdTimeKey = $deviceId.'LatestVersionTime';
            $whereData = [];
            $whereData[] = ['name','=',$deviceIdKey];
            $config_data = $configDataService->getDataOne($whereData);
            if ($config_data && isset($config_data['value'])) {
                // 当前端版本
                $deviceIdVersion = intval($config_data['value']);
            }
            $whereData = [];
            $whereData[] = ['name','=',$deviceIdTimeKey];
            $config_data = $configDataService->getDataOne($whereData);
            if ($config_data && isset($config_data['value'])) {
                // 当前端版本记录时间
                $deviceIdTimes = intval($config_data['value']);
            }
            if (!in_array($deviceId,['wxapp', 'packapp'])) {
                $whereData = [];
                $whereData[] = ['name','=','packappLatestVersion'];
                $config_data = $configDataService->getDataOne($whereData);
                if ($config_data && isset($config_data['value'])) {
                    // 当前端版本
                    $packappVersion = intval($config_data['value']);
                }
            }
        } else {
            $whereData = [];
            $whereData[] = ['name','=','packappLatestVersion'];
            $config_data = $configDataService->getDataOne($whereData);
            if ($config_data && isset($config_data['value'])) {
                // 当前端版本
                $packappVersion = intval($config_data['value']);
            }
        }
        if ($deviceId=='wxapp'&&$deviceIdVersion>=60200) {
            $newPage = true;
            $isSave = true;
        } elseif ($deviceId=='packapp'&&$deviceIdVersion>=60200) {
            $newPage = true;
            $isSave = true;
        } elseif (in_array($deviceId,['wxapp', 'packapp']) && $appVersion && $appVersion>=60200) {
            $newPage = true;
        } elseif (!in_array($deviceId,['wxapp', 'packapp']) && $packappVersion>=60200) {
            $newPage = true;
        } elseif (!$deviceId && $packappVersion>=60200) {
            $newPage = true;
        }
        $nowTime = time();
        if (!in_array($deviceId,['wxapp', 'packapp']) && $deviceIdVersion && $deviceIdTimes && $deviceIdTimes<=$nowTime+86400) {
            $isSave = true;
        }
        if ($appVersion && $deviceIdKey && $deviceIdTimeKey && ($appVersion>$deviceIdVersion || !$isSave)) {
            $whereData = [];
            $whereData[] = ['name','=',$deviceIdKey];
            $saveData = [
                'value' => $appVersion,
            ];
            $configDataService->saveData($whereData,$saveData);
            $whereData = [];
            $whereData[] = ['name','=',$deviceIdTimeKey];
            $saveData = [
                'value' => $nowTime,
            ];
            $configDataService->saveData($whereData,$saveData);
        }
        if (isset($param['isAll'])&&$param['isAll']) {
            $isAll = true;
        } else {
            $isAll = false;
        }
        if (isset($param['urlParam'])&&$param['urlParam']) {
            $urlParam = trim($param['urlParam']);
            $urlParam = str_replace('?','',$urlParam);
        } else {
            $urlParam = '';
        }
        $pageName = isset($param['pageName'])&&trim($param['pageName'])?trim($param['pageName']):'';
        $pagePath = isset($param['pagePath'])&&trim($param['pagePath'])?trim($param['pagePath']):'';
        if ($pageName) {
            $pagePath = $this->pageNametoPath($pageName,$newPage);
            if ($pagePath && $urlParam) {
                $pagePath .= '?'.$urlParam;
            }
            if ($isAll) {
                $pagePath = get_base_url($pagePath);
            }
        } elseif ($pagePath) {
            if ($newPage) {
                // 如果是新页面 直接替换
                $pagePath = str_replace('pages/village/my/','pages/village_my/', $pagePath);
            }
            if ($pagePath && $urlParam) {
                $pagePath .= '?'.$urlParam;
            }
            if ($isAll) {
                $pagePath = get_base_url($pagePath);
            }
        }
        return $pagePath;
    }

    private function pageNametoPath($pageName, $newPage) {
        if ($newPage) {
            $baseUrl = 'pages/village_my/';
        } else {
            $baseUrl = 'pages/village/my/';
        }
        switch ($pageName) {
            case 'waterReportlist':
                // 水电燃上报列表链接
            case 'waterReport':
                // 水电燃上报链接
            case 'selectRooms':
                // 楼栋单元楼层房屋选择
            case 'myVillage':
                // 我的小区 页面
            case 'payMentlist':
                // 生活缴费 页面
            case 'myinfo':
                // 完善资料 页面
            case 'faceAccessControl':
                // 人脸识别门禁 页面
            case 'selectBindFace':
                // 人脸识别门禁绑定选择人员 页面
            case 'familyMembersNotice':
                // 家属出入通知 页面
            case 'repairComment':
                //
            case 'lifepayment':
                // 物业费
            case 'payMentdetailds':
                //
            case 'unpaidMentdetailds':
                //
            case 'healthManagementfee':
                //
            case 'independentPayement':
                // 自主缴费
            case 'expressManagement':
                // 快递管理
            case 'expressAppointment':
                //
            case 'expressDelivery':
                //
            case 'addexpressInfo':
                //
            case 'visitorRegistration':
                // 访客登记
            case 'visitorRegistrationlist':
                // 访客登记列表
            case 'addvisitor':
                //
            case 'QRvisitor':
                //
            case 'shareVisitor':
                //
            case 'villagelist':
                // 小区列表
            case 'QRcode':
                // 智能访客开门
            case 'myPosts':
                // 我的帖子 页面
            case 'myPostsdetailds':
                // 我的帖子详情 页面
            case 'convenientList':
                // 便民列表 页面
            case 'bindFamily':
                // 绑定家属 页面
            case 'joinHouse':
                // 加入房屋
            case 'myvillageList':
                //
            case 'myApartment':
                //
            case 'myRoom':
                //
            case 'hawkEye':
                // 鹰眼服务 页面
            case 'AIcharge':
                //
            case 'addVisitorinfo':
                //
            case 'addVisitorinfoTwo':
                //
            case 'shareVisitorTwo':
                //
            case 'applicationCode':
                //
            case 'visitorRecords':
                // 访客记录
            case 'visitorInfo':
                // 访客信息
            case 'waterReportdetailds':
                // 任务详情
                $pagePath = $baseUrl.$pageName;
                break;
            default:
                $pagePath = $baseUrl.$pageName;
                break;
        }
        return $pagePath;
    }


    public $village_param_word = array(
        0=>array('key' => '{楼栋}', 'word' => 'single_name'),
        1=>array('key' => '{单元}', 'word' => 'floor_name'),
        2=>array('key' => '{层数}', 'word' => 'layer'),
        3=>array('key' => '{房间号}', 'word' => 'room'),
    );
    public $village_param_txt = "{楼栋}、{单元}、{层数}、{房间号}";
    public $defaulf_diy_txt = "{楼栋}{单元}{层数}{房间号}";
    // 房屋类型 1住宅 2商铺 3办公
    public $house_type_arr = [
        1=>'住宅',2=>'商铺',3=>'办公'
    ];
    // 使用状态 1业主入住 2未入住 3租客入住
    public $user_status_arr = [
        1=>'业主入住', 2=>'未入住', 3=>'租客入住'
    ];
    // 出售状态 1正常居住 2出售中 3出租中
    public $sell_status_arr = [
        1=>'正常居住', 2=>'出售中', 3=>'出租中'
    ];
    // 房屋状态 1空置 2审核中 3已绑定业主
    public $room_status_arr = [
        1=>'空置', 2=>'审核中', 3=>'已绑定业主'
    ];
    
    //快捷添加小区楼栋 start
    public $village_param_words = array(
        0=>array('key' => '{楼栋}', 'word' => 'single_name','name'=>'号楼'),
        1=>array('key' => '{单元}', 'word' => 'floor_name','name'=>'单元'),
        2=>array('key' => '{层数}', 'word' => 'layer','name'=>'层'),
        3=>array('key' => '{房间号}', 'word' => 'room','name'=>''),
    );
    public $defaulf_diy_txts = "{楼栋}{单元}{层数}{房间号}";
    public $test_room = '暂无房间';
    
    public function getVillageParamWords($village_id=0)
    {
        $village_param_word = $this->village_param_words;
        foreach ($village_param_word as &$v){
            if(in_array($v['word'],['single_name','floor_name'])){
                $v['name'] = $this->getHouseAlias($v['word'],$village_id) ?: $v['name'];
            }
        }
        return $village_param_word;
    }
    //快捷添加小区楼栋 end
    /**
     * 自定义小区地址  (暂废弃，方法保留)
     * @author:wanziyang
     * @date_time: 2020/4/26 15:41
     * @param array $data 提供的对应参数
     * array(
     *   'single_name' => '楼栋名称',
     *   'floor_name' => '单元名称',
     *   'layer' => '层数',
     *   'room' => '房间号',
     * )
     * @param int $village_id 社区id
     * @param string $word 需要替换的原始自定义 个性化名称
     * @return mixed
     */
    public function word_replce_msgs($data=array(),$village_id=0,$word='') {
//        $village_param_word = $this->village_param_word;
        $village_param_word = $this->getVillageParamWords($village_id);
        if (empty($word) && $village_id) {
            $db_house_village = new HouseVillage();
            $village_info = $db_house_village->getOne($village_id,'diy_village_name');
            $diy_village_name = $village_info['diy_village_name'];
            if ($diy_village_name) {
                $word = $diy_village_name;
            } else {
                $word = $this->defaulf_diy_txt;
            }
        } elseif (empty($word)) {
            $word = $this->defaulf_diy_txt;
        }
        $word = preg_replace('/{楼号}/', '{楼栋}', $word);
        if ($word && $village_param_word) {
            foreach ($village_param_word as $val) {
                if ($val && $val['word']) {
                    // 不存在的信息替换成空
                    if (!$data[$val['word']]) {
                        $data[$val['word']] = '';
                    }
                    if($val['word']=='single_name' && is_numeric($data[$val['word']])){
                        $data[$val['word']]=$data[$val['word']].($this->getHouseAlias('single_name',$village_id)?:'号楼');
                    }
                    if($val['word']=='floor_name' && is_numeric($data[$val['word']])){
                        $data[$val['word']]=$data[$val['word']].($this->getHouseAlias('floor_name',$village_id)?:'单元');
                    }
                    if($val['word']=='layer' && is_numeric($data[$val['word']])){
                        $data[$val['word']]=$data[$val['word']].'层';
                    }
                    $word = preg_replace('/'.$val['key'].'/', $data[$val['word']], $word);
                }
            }
        }
        return $word;
    }
    /**
     * Notes: 提供参数返回对应地址（新增 注：为了保持之前有调用次方法，命名暂未按照新版命名规则）
     * array(
     *   'single_name' => '楼栋名称',
     *   'floor_name' => '单元名称',
     *   'layer' => '层数',
     *   'room' => '房间号',
     * )
     * @param array $data
     * @param int $village_id
     * @param string $word
     * @return string|string[]|null
     * @author: weili
     * @datetime: 2020/9/16 11:17
     */
    public function word_replce_msg($data=array(),$village_id=0,$word='') {
        if (empty($data['single_name']) && empty($data['floor_name']) && empty($data['layer']) && empty($data['room'])) {
            $word = $this->test_room;
            return $word;
        }
        if(preg_match('/[\x7f-\xff]/', $data['single_name'].$data['floor_name'])){
            return $this->word_replce_msgs($data,$village_id,$word);
        }else {

			if(strlen($data['room']) == 1 && is_numeric($data['layer'])){
                $room = $data['layer'] . '0' . $data['room'];
            }elseif (strlen($data['room']) == 2 && is_numeric($data['layer'])){
                $room = $data['layer'] .$data['room'];
            }else{
                $room = $data['room'];
            }
            $data['room'] = $room;
			
//            $village_param_word = $this->village_param_words;
            $village_param_word = $this->getVillageParamWords($village_id);
            $word = $this->defaulf_diy_txts;
            $word = preg_replace('/{楼号}/', '{楼栋}', $word);
            if ($word && $village_param_word) {
                foreach ($village_param_word as $val) {
                    if ($val && $val['word']) {
                        // 不存在的信息替换成空
                        if (!$data[$val['word']]) {
                            $data[$val['word']] = '';
                        } else {
                            // 去除重复汉字拼接
                            $data[$val['word']] = preg_replace('/'.$val['name'].'/', '', $data[$val['word']]);
                        }
                        $word = preg_replace('/' . $val['key'] . '/', $data[$val['word']] . $val['name'], $word);
                    }
                }
            }
            return $word;
        }
    }
    /**
     * Notes: 根据楼栋id 单元id 楼层id 房间id 小区id 获取对应 地址
     * @param:
     * @param int $single_id
     * @param int $floor_id
     * @param string $layer
     * @param int $room_id
     * @param int $village_id
     * @return mixed|string
     * @author: weili
     * @datetime: 2020/7/18 13:50
     */
    public function getSingleFloorRoom($singleId = 0,$floorId=0,$layer = '',$roomId = 0,$villageId = 0,$is_split=0)
    {
        $dbHouseVillageSingle = new HouseVillageSingle(); //楼栋
        $dbHouseVillageFloor = new HouseVillageFloor();   //单元
        $dbHouseVillageLayer = new HouseVillageLayer();   //楼层
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy(); //门牌号
        $room = '';
        if($singleId==0 && $floorId==0 && $layer==0 && $roomId>0 && $villageId>0 ){
            $vacancyObj = $dbHouseVillageUserVacancy->getOne(['pigcms_id'=>$roomId,'village_id'=>$villageId],'*');
            if($vacancyObj && !$vacancyObj->isEmpty()){
                $singleId=$vacancyObj['single_id'];
                $floorId=$vacancyObj['floor_id'];
                $layer=$vacancyObj['layer_id'];
                $room=$vacancyObj['room'];
            }
        }
        if($villageId == 0){
            return '';
        }
        if($singleId == 0){
            $singleName = '';
        }else{
            $single = $dbHouseVillageSingle->getOne(['id'=>$singleId],'single_name');
            $singleName = $single['single_name'];
            if(is_numeric($singleName)){
                $singleName=$singleName.($this->getHouseAlias('single_name',$villageId)?:'栋');
            }else{
                $lastchar=substr($singleName,-1,1);
                if(is_numeric($lastchar)){
                    $singleName=$singleName.($this->getHouseAlias('single_name',$villageId)?:'栋');
                }
            }
        }
        if($floorId == 0){
            $floorName = '';
        }else{
            $floor = $dbHouseVillageFloor->getOne(['floor_id'=>$floorId],'floor_name');
            $floorName = $floor['floor_name'];
            if(is_numeric($floorName)){
                $floorName=$floorName.($this->getHouseAlias('floor_name',$villageId)?:'单元');
            }else{
                $lastchar=substr($floorName,-1,1);
                if(is_numeric($lastchar)){
                    $floorName=$floorName.($this->getHouseAlias('floor_name',$villageId)?:'单元');
                }
            }
        }
        if($layer == ''){
            $layer = '';
        }elseif (is_numeric($layer)){
            $layer_info = $dbHouseVillageLayer->getOne(['id'=>$layer],'layer_name');
            $layer = $layer_info['layer_name'];
            if(is_numeric($layer)){
                $layer=$layer.'层';
            }else{
                $lastchar=substr($layer,-1,1);
                if(is_numeric($lastchar)){
                    $layer=$layer.'层';
                }
            }
        }
        if(empty($room) && $roomId > 0){
            $vacancy = $dbHouseVillageUserVacancy->getOne(['pigcms_id'=>$roomId],'room');
            $room = $vacancy['room'];
        }
        if($is_split){
            return array('single_name'=>$singleName,'floor_name'=>$floorName,'layer'=>$layer,'room'=>$room);
        }
        return $this->word_replce_msg(array('single_name'=>$singleName,'floor_name'=>$floorName,'layer'=>$layer,'room'=>$room),$villageId);

    }
    /**
     * 系统自带业主资料字段
     * @author:wanziyang
     * @date_time: 2020/5/8 13:24
     * @param string $key
     * @return array
     */
    public  function get_system_value($key)
    {
        $arr = array();
        if(empty($key)){
            return $arr;
        }
        if($key == 'sex'){
            $arr = array('男','女');
        }elseif($key == 'nation'){
            $arr = array('汉族','满族','蒙古族','回族','藏族','维吾尔族','苗族','彝族','壮族','布依族','侗族','瑶族','白族','土家族','哈尼族','哈萨克族','傣族','黎族','傈僳族','佤族','畲族','高山族','拉祜族','水族','东乡族','纳西族','景颇族','柯尔克孜族','土族','达斡尔族','仫佬族','羌族','布朗族','撒拉族','毛南族','仡佬族','锡伯族','阿昌族','普米族','朝鲜族','塔吉克族','怒族','乌孜别克族','俄罗斯族','鄂温克族','德昂族','保安族','裕固族','京族','塔塔尔族','独龙族','鄂伦春族','赫哲族','门巴族','珞巴族','基诺族');
        }elseif($key == 'nationality'){
            $db_country = new Country();
            $country = $db_country->getList([]);
            foreach($country as $k=>$v){
                $arr[] = $v['name_zh'];
            }
        }elseif($key == 'marriage_status'){
            $arr = array('未婚','已婚','离婚','丧偶');//未婚、已婚、离婚、丧偶
        }elseif($key == 'edcation'){
            $arr = array('无','初中及以下','高中或中专','大专','本科','硕士','博士','其他');
        }elseif($key == 'unit_nature'){
            $arr = array('国有企业','民营企业','外商独资','中外合资','港澳台企业','政府机关','事业单位','非营利性组织','自主创业','其他');
        }elseif($key == 'is_sign_agreement'){
            $arr = array('是','否');
        }

        return $arr;
    }

    /**
     * 获取社区单个信息
     * @author:wanziyang
     * @date_time: 2020/4/23 12:00
     * @param int $village_id 对应社区id
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function getHouseVillage($village_id,$field = true) {
        if ($village_id<=0) {
            return [];
        }
        // 初始化社区数据层
        $db_house_village = new HouseVillage();
        $db_application_bind = new ApplicationBind();
        $info = $db_house_village->getOne($village_id,$field);
        if($info && !$info->isEmpty()){
            $info = $info->toArray();
        }else{
            $info = array();
        }
        if ($field!==true && $field==='has_express_service') {
            // 对应快递代收代送 开关以新版 应用绑定为准
            $where = array(
                'application_id' => 14,
                'from' => 0,
                'use_id' => $village_id,
                'status' => 0,
            );
            $bind_info = $db_application_bind->getOne($where,'bind_id');
            if ($bind_info) {
                $info['has_express_service'] = 1;
            } else {
                $info['has_express_service'] = 0;
            }
        } elseif($field!==true && $field==='has_visitor') {
            // 对应访客登记 开关以新版 应用绑定为准
            $where = array(
                'application_id' => 28,
                'from' => 0,
                'use_id' => $village_id,
                'status' => 0,
            );
            $bind_info = $db_application_bind->getOne($where,'bind_id');
            if ($bind_info) {
                $info['has_visitor'] = 1;
            } else {
                $info['has_visitor'] = 0;
            }
        } elseif($field!==true && $field==='has_kefu') {
            // 对应客服im 开关以新版 应用绑定为准
            $where = array(
                'application_id' => 1,
                'from' => 0,
                'use_id' => $village_id,
                'status' => 0,
            );
            $bind_info = $db_application_bind->getOne($where,'bind_id');
            if ($bind_info) {
                $info['has_kefu'] = 1;
            } else {
                $info['has_kefu'] = 0;
            }
        } elseif($field!==true && $field==='open_face_door') {
            // 对应人脸识别门禁  开启
            $info['open_face_door'] = 1;
        }
        return $info;
    }

    /**
     * 获取单个物业数据信息
     * @author:wanziyang
     * @date_time: 2020/4/23 13:15
     * @param int $id 对应物业管理id
     * @param string|bool $field 需要获取的对应字段
     * @param string $key 查询条件字段
     * @return array|null|\think\Model
     */
    public function get_house_property($id,$field = true,$key = 'id') {
        // 初始化 物业管理员 数据层
        $where[$key] = $id;
        $info = $this->get_house_property_where($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 条件获取单个物业数据信息
     * @author:wanziyang
     * @date_time: 2020/4/23 13:15
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_house_property_where($where,$field = true) {
        // 初始化 物业管理员 数据层
        $db_house_property = new HouseProperty();
        $info = $db_house_property->get_one($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }
    public function save_house_property($where,$saveArr = array()) {

        $db_house_property = new HouseProperty();
        $ret = $db_house_property->save_one($where,$saveArr);
        return $ret;
    }
    /**
     * 条件获取单个楼栋数据信息
     * @author:wanziyang
     * @date_time: 2020/4/23 13:15
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_house_village_single_where($where,$field = true) {
        // 初始化 数据层
        $db_house_village_single= new HouseVillageSingle();
        $info = $db_house_village_single->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 条件获取单个层级数据信息
     * @author:wanziyang
     * @date_time: 2020/6/12 17:48
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function getHouseVillageLayerWhere($where,$field = true) {
        // 初始化 数据层
        $db_house_village_layer = new HouseVillageLayer();
        $info = $db_house_village_layer->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }


    /**
     * 条件获取单个单元数据信息
     * @author:wanziyang
     * @date_time: 2020/4/23 13:15
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function getHouseVillageFloorWhere($where,$field = true) {
        // 初始化 物业管理员 数据层
        $db_house_village_floor = new HouseVillageFloor();
        $info = $db_house_village_floor->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }


    /**
     * 获取单个物业数据信息
     * @author:wanziyang
     * @date_time: 2020/4/23 13:18
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function getHouseMenuNew($where,$field = true) {
        // 初始化 物业管理员 数据层
        $db_house_menu_new = new HouseMenuNew();
        $list = $db_house_menu_new->getList($where,$field);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }

    /**
     * 获得首页底部菜单
     * @author:wanziyang
     * @date_time: 2020/4/23 13:35
     * @param array $menu 目录信息
     * @return array
     */
    public function getFooter($menu=array()){
        $arr = array(
            // 底部菜单
            'is_show' => false,
            'child' => array(
                'cashier' =>false, //收银台
                'door' =>false, //智慧门禁
            )
        );
        // 角色权限
        foreach ($menu as $value) {
            switch ($value) {
                case '66':
                    $arr['is_show'] = true;
                    $arr['child']['cashier'] = true;
                    break;
                case '226':
                    $arr['is_show'] = true;
                    $arr['child']['door'] = true;
                    break;
            }
        }
        return $arr;
    }

    /**
     * 获得开门密码
     * @author: wanziyang
     * @date_time: 2020/4/24 9:05
     * @param array $now_house 小区信息数组
     * @return mixed
     */
    public function get_lock_pwd($now_house){
        if ($now_house && isset($now_house['lock_pwd']) && $now_house['lock_pwd']) {
            return $now_house['lock_pwd'];
        }else{
            $lock_pwd = mt_rand(10000000,99999999);
            if (empty($now_house['village_id'])) {
                return $lock_pwd;
            }
            $db_house_village = new HouseVillage();
            $db_house_village->saveOne(['village_id' => $now_house['village_id']],['lock_pwd'=>$lock_pwd]);
            return $lock_pwd;
        }
    }

    /**
     * 获取单个物业数据信息
     * @author:wanziyang
     * @date_time: 2020/4/23 13:18
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|\think\Model
     */
    public function getList($where,$field = true,$page=1,$limit=20,$order='village_id DESC',$type=0) {
        // 初始化 物业管理员 数据层
        $db_house_village = new HouseVillage();
        $list = $db_house_village->getList($where,$field,$page,$limit,$order);
        return $list;
    }

    /**
     * 获取单个小区数据信息
     * @author:wanziyang
     * @date_time: 2020/4/23 13:18
     * @param array $village_id 社区ID
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function getFind($where = [],$field = true) {
        // 初始化 物业管理员 数据层
        $db_house_village = new HouseVillage();
        $list = $db_house_village->getInfo($where,$field);
        return $list;
    }

    /**
     * 编辑社区信息
     * @author: wanziyang
     * @date_time: 2020/4/24 17:45
     * @param array $where 查询条件
     * @param array $data 对修改信息数组
     * @return array|null|\think\Model
     */
    public function edit_house_village($where,$data) {
        // 初始化 小区 数据层
        $db_house_village = new HouseVillage();
        $edit = $db_house_village->saveOne($where,$data);
        if (isset($where['village_id']) && intval($where['village_id']) > 0) {
            (new CarHandleGetInfoService())->clearVillageInfo($where['village_id']);
        }
        return $edit;
    }

    /**
     * 查询对应小区下房间数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where
     * @return int
     */
    public function getVillageRoomNum($where) {
        // 初始化 小区房屋 数据层
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $count = $db_house_village_user_vacancy->getVillageRoomNum($where);
        if (!$count) {
            $count =0;
        }
        return $count;
    }

    /**
     * 查询对应小区下用户数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where
     * @return int
     */
    public function getVillageUserNum($where) {
        // 初始化 小区房间绑定 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $count = $db_house_village_user_bind->getVillageUserNum($where);
        if (!$count) {
            $count =0;
        }
        return $count;
    }
    public function getOneVillageUserBindInfo($where,$field='*') {
        // 初始化 小区房间绑定 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $village_user_bind = $db_house_village_user_bind->getOne($where,$field,'pigcms_id desc');
        $user_bind_info=array();
        if ($village_user_bind && !$village_user_bind->isEmpty()) {
            $user_bind_info=$village_user_bind->toArray();
        }
        return $user_bind_info;
    }
    public function getUserBindRoomLists($where,$whereOr='',$field='*',$page=0,$limit=15){
        $db_house_village_user_bind = new HouseVillageUserBind();
        $village_user_bind_obj = $db_house_village_user_bind->getUserLists($where,$whereOr,$field,$page,$limit);
        $user_bind_info=array();
        if ($village_user_bind_obj && !$village_user_bind_obj->isEmpty()) {
            $user_bind_info=$village_user_bind_obj->toArray();
        }
        return $user_bind_info;
    }
    /**
     * 查询对应小区下申请解绑用户数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where
     * @return int
     */
    public function get_village_unbind_user_num($where) {
        // 初始化 数据层
        $db_house_village_user_unbind = new HouseVillageUserUnbind();
        $count = $db_house_village_user_unbind->getVillageUnbindUserNum($where);
        if (!$count) {
            $count =0;
        }
        return $count;
    }

    /**
     * 保存信息
     * @author: wanziyang
     * @date_time: 2020/5/13 13:11
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveUnbindUser($where,$data) {
        // 初始化 数据层
        $db_house_village_user_unbind = new HouseVillageUserUnbind();
        $set = $db_house_village_user_unbind->saveOne($where,$data);
        return $set;
    }

    /**
     * 获取信息
     * @author: wanziyang
     * @date_time: 2020/5/13 13:11
     * @param array $where
     * @param bool|string $field
     * @return bool
     */
    public function getUnbindUser($where,$field=true) {
        // 初始化 数据层
        $db_house_village_user_unbind = new HouseVillageUserUnbind();
        $info = $db_house_village_user_unbind->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 查询对应小区下申请解绑列表
     * @author: wanziyang
     * @date_time: 2020/5/12 19:57
     * @param $where
     * @param int $page
     * @param string $field
     * @param string $order
     * @param int $page_size
     *    * array(
     *   'single_name' => '楼栋名称',
     *   'floor_name' => '单元名称',
     *   'layer' => '层数',
     *   'room' => '房间号',
     * )
     * @return array|null|\think\Model
     */
    public function getUnbindUserLimitList($where,$page=0,$field ='a.*',$order='a.pigcms_id DESC',$page_size=10) {
        // 初始化 小区房间绑定 数据层
        $db_house_village_user_unbind = new HouseVillageUserUnbind();
        $list = $db_house_village_user_unbind->getLimitList($where,$page,$field,$order,$page_size);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            foreach($list as &$val) {
                if (($val['single_name'] || $val['floor_layer']) && $val['floor_name'] && $val['layer'] && $val['room']) {
                    $word = [
                        'single_name' => $val['single_name'] ? $val['single_name'] : $val['floor_layer'],
                        'floor_name' => $val['floor_name'],
                        'layer' => $val['layer'],
                        'room' => $val['room'],
                    ];
                    $address = $this->word_replce_msg($word,$val['village_id']);
                    if ($address) {
                        $val['address'] = $address;
                    }
                }
            }
        }
        return $list;
    }


    /**
     * 查询对应小区下车辆数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where
     * @return int
     */
    public function get_village_car_num($where) {
        // 初始化 小区车辆 数据层
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $count = $db_house_village_parking_car->get_village_car_num($where);
        if (!$count) {
            $count =0;
        }
        return $count;
    }

    /**
     * 查询对应小区下车位数量
     * @author: wanziyang
     * @date_time: 2020/4/25 16:01
     * @param array $where
     * @return int
     */
    public function get_village_park_position_num($where) {
        // 初始化 小区车辆 数据层
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $count = $db_house_village_parking_position->get_village_park_position_num($where);
        if (!$count) {
            $count =0;
        }
        return $count;
    }


    /**
     * 查询对应小区下论坛文章数量
     * @author: wanziyang
     * @date_time: 2020/4/25 16:10
     * @param array $where
     * @return int
     */
    public function get_bbs_aricle_num($where) {
        // 初始化 小区车辆 数据层
        $db_bbs_aricle = new BbsAricle();
        $count = $db_bbs_aricle->get_bbs_aricle_num($where);
        if (!$count) {
            $count =0;
        }
        return $count;
    }

    /**
     * 查询对应小区在线报修数量
     * @author: wanziyang
     * @date_time: 2020/4/25 16:10
     * @param array $where
     * @return int
     */
    public function get_repair_list_num($where) {
        // 初始化 小区车辆 数据层
        $db_house_village_repair_list = new HouseVillageRepairList();
        $count = $db_house_village_repair_list->get_repair_list_num($where);
        if (!$count) {
            $count =0;
        }
        return $count;
    }

    /**
     * 查询对应小区快递数量
     * @author: wanziyang
     * @date_time: 2020/4/25 16:50
     * @param array $where
     * @return int
     */
    public function get_village_express_num($where) {
        // 初始化 小区车辆 数据层
        $db_house_village_express = new HouseVillageExpress();
        $count = $db_house_village_express->get_village_express_num($where);
        if (!$count) {
            $count =0;
        }
        return $count;
    }

    /**
     * 获取楼栋列表
     * @author: wanziyang
     * @date_time:2020/5/6 14:57
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @param string $order 排序规则
     * @return array|null|\think\Model
     */
    public function getSingleList($where,$field = true,$order='id DESC') {
        // 初始化 数据层
        $db_house_village_single= new HouseVillageSingle();
        $list = $db_house_village_single->getList($where,$field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }

    /**
     * 获取单元列表
     * @author: wanziyang
     * @date_time:2020/5/6 15:09
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @param string $order 排序规则
     * @return array|null|\think\Model
     */
    public function getFloorList($where,$field = true,$order='floor_id DESC') {
        // 初始化 数据层
        $db_house_village_floor= new HouseVillageFloor();
        $list = $db_house_village_floor->getList($where,$field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }

    /**
     * 获取层级列表
     * @author: wanziyang
     * @date_time:2020/6/12 18:26
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @param string $order 排序规则
     * @return array|null|\think\Model
     */
    public function getHouseVillageLayerList($where,$field = true,$order='id DESC') {
        // 初始化 数据层
        $db_house_village_layer= new HouseVillageLayer();
        $list = $db_house_village_layer->getList($where,$field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }

    /**
     * 获取房间列表
     * @author: wanziyang
     * @date_time:2020/5/6 15:09
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @param string $order 排序规则
     * @return array|null|\think\Model
     */
    public function getUserVacancyList($where,$field = true,$order='pigcms_id DESC') {
        // 初始化 数据层
        $db_house_village_user_vacancy= new HouseVillageUserVacancy();
        $list = $db_house_village_user_vacancy->getList($where,$field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }

    /**
     * 获取业主房间相关（待审核）
     * @author: wanziyang
     * @date_time: 2020/5/12 10:19
     * @param array $where
     * @param bool|string $field
     * @param string|int $page
     * @param string $order
     * @param int $page_size
     * @return array|null|\think\Model
     */
    public function getUserVacancyPageList($where,$field='a.*,b.single_name,c.floor_name,c.floor_layer,d.avatar',$page='',$order='a.pigcms_id desc',$page_size=10) {
        // 初始化 数据层
        $db_house_village_user_bind= new HouseVillageUserBind();
        $list = $db_house_village_user_bind->getPageList($where,$field,$page,$order,$page_size);
        if (!$list || $list->isEmpty()) {
            $user_list = [];
        } else {
            $service_login = new ManageAppLoginService();
            $dbHouseVillageLayer = new HouseVillageLayer();
            $user_bind_type_color = $service_login->user_bind_type_color;
            $user_bind_type_arr = $service_login->user_bind_type_arr;
            $site_url = cfg('site_url');
            $static_resources = static_resources(true);
            $houseVillageDb=new HouseVillage();
            $village_id=$list['0']['village_id'];
            $village_info=$houseVillageDb->getInfo(array('village_id'=>$village_id),'*');
            
            $user_list = [];
            foreach ($list as $value) {
                $msg = [];
                $msg['pigcms_id'] = $value['pigcms_id'];
                $msg['bind_id'] = $value['bind_id'];
                if (isset($value['bind_number']) && $value['bind_number']) {
                    $msg['usernum'] = $value['bind_number'];
                } elseif(isset($msg['usernum'])) {
                    $msg['usernum'] = $value['usernum'];
                }
                $msg['identity_tag'] = $user_bind_type_arr[$value['type']];
                $msg['tag_color'] = $user_bind_type_color[$value['type']];
                if (empty($value['avatar'])) {
                    $value['avatar'] =  $site_url . $static_resources . 'images/avatar.png';
                }
                $msg['name'] = $value['name'];
                $msg['avatar'] = $value['avatar'];

                $value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
                $value['application_time'] = $value['application_time'] ? date('Y-m-d H:i:s',$value['application_time']) : 0;
                if($value['layer_id']>0){
                    $layer_info = $dbHouseVillageLayer->getOne(['id'=>$value['layer_id']],'layer_name');
                    if($layer_info && !$layer_info->isEmpty()){
                        $layer_info=$layer_info->toArray();
                        if($layer_info && $layer_info['layer_name']){
                            $value['layer']=$layer_info['layer_name'];
                        }
                    }
                }

                $word_msg = [
                    'single_name' => $value['single_name'] ? $value['single_name'] : $value['floor_layer'],
                    'floor_name' => $value['floor_name'],
                    'layer' => $value['layer'],
                    'room' => $value['room'],
                ];
                $value['address'] = $this->word_replce_msg($word_msg);
                $phone=$houseVillageDb->phone_desensitization_v($value['phone'],$value['village_id'],$village_info);
                $content = [];
                $content[] = [
                    'title' => '手机号码',
                    'info' => $phone
                ];
                $content[] = [
                    'title' => '家庭住址',
                    'info' => $value['address']
                ];
                $content[] = [
                    'title' => '申请时间',
                    'info' => $value['application_time']
                ];
                $msg['content'] = $content;

                $user_list[] = $msg;
            }
        }
        return $user_list;
    }


    /**
     * 获取房间信息
     * @author: wanziyang
     * @date_time: 2020/5/7 10:09
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @return array|null|\think\Model
     */
    public function getRoomInfoWhere($where,$field = true) {
        // 初始化 数据层
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $info = $db_house_village_user_vacancy->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        } else {
            if (isset($info['property_number']) && $info['property_number']) {
                $info['usernum'] = $info['property_number'];
            }
        }
        return $info;
    }

    /**
     * 获取房间相关用户数量
     * @author: wanziyang
     * @date_time: 2020/5/7 14:26
     * @param array $where 查询条件
     * @return array|null|\think\Model
     */
    public function getRoomUserNumber($where) {
        // 初始化 数据层
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $count = $db_house_village_user_vacancy->getRoomUserNumber($where);
        if (!$count) {
            $count = 0;
        }
        return $count;
    }

    /**
     * 获取房间相关用户信息
     * @author: wanziyang
     * @date_time: 2020/5/7 14:26
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @param string $order 排序规则
     * @return array|null|\think\Model
     */
    public function getRoomUserList($where,$field = 'a.*,b.*',$order='b.pigcms_id DESC') {
        // 初始化 数据层
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $list = $db_house_village_user_vacancy->getRoomUserList($where,$field,$order);
        if (!$list) {
            $list = [];
        } else {
            $list = $list->toArray();
            // 过滤虚拟业主
            foreach ($list as $key => $val) {
                if (isset($val['name']) && isset($val['phone']) && isset($val['uid']) && !$val['name'] && !$val['phone'] && !$val['uid']) {
                    unset($list[$key]);
                }
                if (isset($val['bind_number']) && $val['bind_number']) {
                    $val['usernum'] = $val['bind_number'];
                }
            }
            $list = array_values($list);
        }
        return $list;
    }


    /**
     * 获取公共区域列表
     * @author: wanziyang
     * @date_time: 2020/5/14 15:10
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function getHouseVillagePublicAreaList($where,$field=true) {
        $db_house_village_public_area = new HouseVillagePublicArea();
        $list = $db_house_village_public_area->getList($where,$field);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }


    /**
     * 获取公共区域列表
     * @author: wanziyang
     * @date_time: 2020/5/15 14:43
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function getHouseVillagePublicAreaOne($where,$field=true) {
        $db_house_village_public_area = new HouseVillagePublicArea();
        $info = $db_house_village_public_area->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;

    }
    /**
     * 注册 添加数据
     * @author: weili
     * @datetime: 2020/7/7 10:40
     * @paramarray $data
    **/
    public function addHouseVillageData($data)
    {
        $serviceHouseVillage = new HouseVillage();
        $res = $serviceHouseVillage->insertOne($data);
        return $res;
    }
    /**
     * 获取小区信息数据
     * @author weili
     * @datetime 2020/7/7 13:16
     * @param array $where
     * @param bool $field
     * @return array
    **/
    public function getHouseVillageInfo($where,$field=true)
    {
        $serviceHouseVillage = new HouseVillage();
        $res = $serviceHouseVillage->getInfo($where,$field);
        return $res;
    }

    public function getHouseVillageInfoExtend($where,$field='*')
    {
        $houseVillageInfoExtend = new HouseVillageInfo();
        $res = $houseVillageInfoExtend->getOne($where,$field);
        if($res && !$res->isEmpty()){
            $res=$res->toArray();
        }else{
            $res=array();
        }
        return $res;
    }

    public function saveHouseVillageInfoExtendWorkOrderExtra($where,$work_order_extra=array(),$tmpSaveArr=array())
    {
        $savedata=is_array($tmpSaveArr) ? $tmpSaveArr:array();
        $houseVillageInfoExtend = new HouseVillageInfo();
        $res = $houseVillageInfoExtend->getOne($where);
        if($res && !$res->isEmpty()){
            $villageInfo=$res->toArray();
            if(!empty($villageInfo['work_order_extra'])){
                $tmp_work_order_extra=json_decode($villageInfo['work_order_extra'],1);
                $work_order_extra=array_merge($tmp_work_order_extra,$work_order_extra);
            }
            $work_order_extra=json_encode($work_order_extra,JSON_UNESCAPED_UNICODE);
            $savedata['work_order_extra']=$work_order_extra;
            $tmp_ret=$houseVillageInfoExtend->saveOne($where,$savedata);
        }else{
            $savedata['work_order_extra']=$work_order_extra;
            $savedata=array_merge($where,$savedata);
            $savedata['add_time']=time();
            $tmp_ret=$houseVillageInfoExtend->addOne($savedata);
        }
        return $tmp_ret;
    }
    
    public function saveHouseVillageInfoExtend($where,$savedata=array())
    {
        $houseVillageInfoExtend = new HouseVillageInfo();
        $res = $houseVillageInfoExtend->getOne($where);
        if($res && !$res->isEmpty()){
            $tmp_ret=$houseVillageInfoExtend->saveOne($where,$savedata);
        }else{
            $savedata=array_merge($where,$savedata);
            $savedata['add_time']=time();
            $tmp_ret=$houseVillageInfoExtend->addOne($savedata);
        }
        return $tmp_ret;
    }
    
    /**
     * 获取小区快递列表
     * @author lijie
     * @date_time 2020/08/17 16:47
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getExpressLists($where,$field=true,$page=1,$limit=10,$order='hev.id DESC')
    {
        $house_village_express = new HouseVillageExpress();
        $houseVillageDb=new HouseVillage();
        $dataObj = $house_village_express->getLists($where,$field,$page,$limit,$order);
        if($dataObj && !$dataObj->isEmpty()){
            $data=$dataObj->toArray();
            $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['0']['village_id']),'*');
            foreach ($data as $k=>$v){
                $data[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                $data[$k]['delivery_time'] = date('Y-m-d H:i:s',$v['delivery_time']);
                if(isset($v['phone']) && !empty($v['phone'])){
                    $data[$k]['phone']=$houseVillageDb->phone_desensitization_v($v['phone'],$v['village_id'],$village_info);
                }
            }
        }else{
            $data=array();
        }

        return $data;
    }

    /**
     * 添加快递
     * @author lijie
     * @date_time 2020/08/17 16:48
     * @param $data
     * @return mixed
     */
    public function addExpress($data)
    {
        $house_village_express = new HouseVillageExpress();
        $res = $house_village_express->addOne($data);
        if($res){
            //发送微信模板消息
            /*$user = new User();
            $where['status'] = 1;
            $where['phone'] = $data['phone'];
            $field = 'nickname,openid';
            $userInfo = $user->getOne($where,$field);
            if($userInfo && $userInfo['openid']){
                $house_village = new HouseVillage();
                $village_info = $house_village->getOne(['village_id'=>$data['village_id']]);
                $express = new Express();
                $express = $express->getOne(['id'=>$data['express_type']]);
                $href = config('config.site_url').'/wap.php?g=Wap&c=Library&a=express_service_list&village_id='.$data['village_id'];
                $model = new templateNews(config('config.wechat_appid'), config('config.wechat_appsecret'));
                $express_info = '\n您的'.$express['name'].'快递包裹到达'.$village_info["village_name"].'服务站，取货码'.$data['fetch_code'].'，电话'.$village_info['property_phone'].'，地址：'.$village_info['property_address'].'。';
                $model->sendTempMsg('OPENTM405462911',
                    array(
                        'href' => $href,
                        'wecha_id' => $userInfo['openid'],
                        'first' => $express_info,
                        'keyword1' => '新快递待取',
                        'keyword2' =>'已发送',
                        'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                        'keyword4' => $userInfo['nickname'],
                        'remark' => '\n请点击查看详细信息！'
                    )
                );
            }*/
        }
        return true;
    }

    /**
     * 查看快递详情
     * @author lijie
     * @date_time 2020/08/17 17:04
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getExpressDetail($where,$field=true)
    {
        $house_village_express = new HouseVillageExpress();
        $data = $house_village_express->getOne($where,$field);
        if ($data && isset($data['delivery_time'])) {
            $data['delivery_time'] = date('Y-m-d H:i:s',$data['delivery_time']);
        }
        if ($data && isset($data['add_time'])) {
            $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
        }
        if(isset($data['phone']) && !empty($data['phone'])){
            $houseVillageDb=new HouseVillage();
            $village_id=isset($data['village_id']) ? $data['village_id']:0;
            $data['phone']=$houseVillageDb->phone_desensitization_v($data['phone'],$village_id);
        }
        if($data){
            switch ($data['status']){
                case 0:
                    $data['status'] = '未取件';
                    break;
                case 1:
                    $data['status'] = '业主确认取件';
                    break;
                case 2:
                    $data['status'] = '物业确认取件';
                    break;
                default:
                    $data['status'] = '未取件';
            }
        }
        return $data;
    }

    /**
     * 快递类型
     * @author lijie
     * @date_time 2020/08/19 14:17
     * @param $where
     * @param bool $filed
     * @return mixed
     */
    public function getExpress($where,$filed=true)
    {
        $express = new Express();
        $data = $express->getLists($where,$filed);
        return $data;
    }

    /**
     * Notes:查询小区工作人员
     * @param $data
     * @return array|\think\Model|null
     * @author: weili
     * @datetime: 2020/10/20 16:36
     */
    public function getHouseWorker($data)
    {
        $dbHouseWorker = new HouseWorker();
        if(isset($data['village_id'])  && $data['village_id']>0){
            $where[] = ['village_id','=',$data['village_id']];
        }else{
            $where[] = ['village_id','>',0];
        }
        $where[] = ['status','=',1];
        $where[] = ['is_del','=',0];
//        $where[] = ['type','=',1];
//        $where[] = ['type','in',[0,1]];
        if(isset($data['account'])  && $data['account']) {
            $where[] = ['account','=',$data['account']];
        } else {
//            return  [];
            $where[] = ['wid','=',$data['wid']];
        }
       //  print_r($where);die;
        $info = $dbHouseWorker->getOne($where,'*','wid asc');
        return $info;
    }
    public function getHouseWorkerList($data)
    {
        $dbHouseWorker = new HouseWorker();
        if(isset($data['village_id'])  && $data['village_id']>0){
            $where[] = ['village_id','=',$data['village_id']];
        }else{
            $where[] = ['village_id','>',0];
        }
        $where[] = ['status','=',1];
        $where[] = ['is_del','=',0];
        if(isset($data['account'])  && $data['account']) {
            $where[] = ['account','=',$data['account']];
        } else {

            $where[] = ['wid','=',$data['wid']];
        }
        $info = $dbHouseWorker->getSome($where,'*','wid desc',0,100);
        return $info;
    }
    /**
     * Notes: 修改
     * @param $id
     * @param $data
     * @return HouseWorker
     * @author: weili
     * @datetime: 2020/10/20 16:40
     */
    public function saveHouseWorker($id,$data,$opt='')
    {
        $dbHouseWorker = new HouseWorker();
        $where=array();
        $where[] = ['wid','=',$id];
        $houseWorker=$dbHouseWorker->get_one($where,'*');
        $res = $dbHouseWorker->editData($where,$data);
        if($opt=='destroy' && $houseWorker && !$houseWorker->isEmpty()){
            $houseWorker=$houseWorker->toArray();
            //注销操作
            if(isset($houseWorker['hw_id']) && $houseWorker['hw_id']>0){
                $housePropertyVillageWorkerService= new HousePropertyVillageWorkerService();
                $remarks=isset($data['note']) ? $data['note']:'';
                if(empty($remarks) && isset($data['remarks']) && $data['remarks']){
                    $remarks=$data['remarks'];
                }
                $saveArr=array('status'=>4,'remarks'=>$remarks,'openid'=>'','nickname'=>'','avatar'=>'');
                $housePropertyVillageWorkerService->synEditPropertyVillageWorker($houseWorker['hw_id'],$saveArr);
            }
        }
        
        return $res;
    }

    /**
     * 获取楼栋和小区名称
     * @author lijie
     * @date_time 2020/01/07
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getSingleVillageName($where,$field=true)
    {
        $model_house_village_single = new HouseVillageSingle();
        $data = $model_house_village_single->getSingleVillageName($where,$field);
        return $data;
    }

    /**
     * 获取楼栋下单元信息
     * @author lijie
     * @date_time 2020/01/12
     * @param $where
     * @param $field
     * @param $order
     * @return array|\think\Model|null
     */
    public function getFloorInfo($where,$field=true,$order='s.id desc')
    {
        $db_house_village_floor= new HouseVillageFloor();
        $db_house_village_user_bind = new HouseVillageUserBind();
        $list = $db_house_village_floor->floorSingleName($where,$field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }else{
            foreach ($list as $k=>$v){
                $list[$k]['count'] = $db_house_village_user_bind->getVillageUserNum(['floor_id'=>$v['floor_id']]);
            }
        }
        return $list;
    }

    /**
     * 获取单元数量
     * @author lijie
     * @date_time 2020/01/12
     * @param $where
     * @return int
     */
    public function getFloorCount($where)
    {
        $db_house_village_floor = new HouseVillageFloor();
        $count = $db_house_village_floor->getCount($where);
        return $count;
    }
    /**
     * 计算小区区域数量
     * @author lijie
     * @date_time 2020/12/04
     * @param $where
     * @return mixed
     */
    public function getAreaNum($where)
    {
        $db_house_village = new HouseVillage();
        $count = $db_house_village->getAreaNum($where);
        return $count;
    }

    /**
     * 计算小区区域面积
     * @author lijie
     * @date_time 2020/12/04
     * @param $where
     * @param string $field
     * @return float
     */
    public function getAreaSize($where,$field='plot_area')
    {
        $db_house_village_info = new HouseVillageInfo();
        $size = $db_house_village_info->getNum($where,$field);
        return $size;
    }

    /**
     * 小区数量
     * @author lijie
     * @date_time 2020/12/04
     * @param $where
     * @return int
     */
    public function getVillageNum($where)
    {
        $db_house_village = new HouseVillage();
        $count = $db_house_village->getNum($where);
        return $count;
    }

    /**
     * 获取楼层信息
     * @author lijie
     * @date_time 2020/01/12
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return array|\think\Model|null
     */
    public function getLayerInfo($where,$field = true,$order='id desc',$page=0,$limit=10)
    {
        $db_house_village_layer = new HouseVillageLayer();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $data = $db_house_village_layer->getList($where,$field,$order,$page,$limit);
        if($data){
            foreach ( $data as $k=>$v){
                $room_list = $db_house_village_user_vacancy->getList(['layer_id'=>$v['id'],'is_del'=>0],'room,pigcms_id,house_type');
                $data[$k]['room_list'] = $room_list;
            }
        }
        return $data;
    }

    /**
     * 获取小区下的所有区域
     * @author lijie
     * @date_time 2020/12/05
     * @param $where
     * @param bool $field
     * @param string $group
     * @return mixed
     */
    public function getVillageArea($where,$field=true,$group='v.city_id')
    {
        $db_house_village = new HouseVillage();
        $data = $db_house_village->getVillageArea($where,$field,$group);
        return $data;
    }

    /**
     * 获取楼层信息
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getHouseVillageFloorInfo($where,$field=true)
    {
        $db_house_village_floor = new HouseVillageFloor();
        $data = $db_house_village_floor->getOne($where,$field);
        return $data;
    }

    /**
     *
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getHouseVillageLayerInfo($where,$field=true)
    {
        $db_house_village_layer = new HouseVillageLayer();
        $data = $db_house_village_layer->getOne($where,$field);
        return $data;
    }

    /**
     * Notes: 组合返回公共区域和楼栋
     * @param $village_id
     * @return array
     * @author: wanzy
     * @date_time: 2021/8/13 15:20
     */
    public function getHousePosition($village_id) {
        $wherePublicArea = [];
        $wherePublicArea[] = ['village_id', '=', $village_id];
        $wherePublicArea[] = ['status', '=', 1];
        $db_house_village_public_area = new HouseVillagePublicArea();
        $publicAreaList = $db_house_village_public_area->getList($wherePublicArea,'public_area_id, public_area_name');
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];
        $list = $this->getSingleList($where, 'single_name as name,id,single_name,village_id', 'sort DESC,id desc');
        $housePosition = [];
        if (!empty($publicAreaList)) {
            $db_house_village = new HouseVillage();
            $village_info = $db_house_village->getOne($village_id,'village_id,village_name');
            foreach ($publicAreaList as $val1) {
                $item = [];
                if (isset($village_info['village_name']) && $village_info['village_name']) {
                    $item['address'] = $village_info['village_name'].' '.$val1['public_area_name'];
                } else {
                    $item['address'] = $val1['public_area_name'];
                }
                if (isset($val1['public_area_id']) && $val1['public_area_id']) {
                    $item['id'] = $val1['public_area_id'];
                }
                if (isset($val1['public_area_name']) && $val1['public_area_name']) {
                    $item['name'] = $val1['public_area_name'];
                }
                if (!empty($item)) {
                    $item['type'] = 'public';
                    $housePosition[] = $item;
                }
            }
        }
        if (!empty($list)) {
            foreach ($list as $val2) {
                $item = [];
                $item['address'] = '';
                if (isset($val2['id']) && $val2['id']) {
                    $item['id'] = $val2['id'];
                }
                if (isset($val2['name']) && $val2['name']) {
                    $item['name'] = $val2['name'];
                } elseif (isset($val2['single_name']) && $val2['single_name']) {
                    $item['name'] = $val2['single_name'];
                }
                if (!empty($item)) {
                    $item['type'] = 'single';
                    $housePosition[] = $item;
                }
            }
        }
        return $housePosition;
    }

    /**
     * Notes: 查询子集
     * @param $village_id
     * @param $id
     * @param string $type
     * @return array
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/8/13 16:56
     */
    public function getHousePositionChildren($village_id,$id,$type='single') {
        switch($type) {
            case 'public':
                // 公共区域没有下一级
                return [];
            case 'single':
                // 楼栋下一级是单元
                $where = [];
                $where[] = ['village_id', '=', $village_id];
                $where[] = ['single_id', '=', $id];
                $where[] = ['status', '=', 1];
                // 查询单元
                $list = $this->getFloorList($where, 'floor_name as name,floor_id as id ,floor_id,floor_name,single_id', 'sort DESC,floor_id desc');
                $housePosition = [];
                if (!empty($list)) {
                    foreach ($list as $val) {
                        $item = [];
                        $item['address'] = '';
                        if (isset($val['id']) && $val['id']) {
                            $item['id'] = $val['id'];
                        }
                        if (isset($val['name']) && $val['name']) {
                            $item['name'] = $val['name'];
                        } elseif (isset($val['floor_name']) && $val['floor_name']) {
                            $item['name'] = $val['floor_name'];
                        }
                        if(!preg_match('/[\x7f-\xff]/', $item['name'])) {
                            $item['name'] = $item['name'].($this->getHouseAlias('floor_name',$village_id)?:'单元');
                        }
                        if (!empty($item)) {
                            $item['type'] = 'floor';
                            $housePosition[] = $item;
                        }
                    }
                }
                return $housePosition;
            case 'floor':
                // 单元下一级是楼层
                $where = [];
                $where[] = ['village_id', '=', $village_id];
                $where[] = ['floor_id', '=', $id];
                $where[] = ['status', '=', 1];
                // 查询单元
                $list = $this->getHouseVillageLayerList($where, 'layer_name as name,id ,floor_id,layer_name,single_id','sort DESC,id desc');
                $housePosition = [];
                if (!empty($list)) {
                    foreach ($list as $val) {
                        $item = [];
                        $item['address'] = '';
                        if (isset($val['id']) && $val['id']) {
                            $item['id'] = $val['id'];
                        }
                        if (isset($val['name']) && $val['name']) {
                            $item['name'] = $val['name'];
                        } elseif (isset($val['layer_name']) && $val['layer_name']) {
                            $item['name'] = $val['layer_name'];
                        }
                        if(!preg_match('/[\x7f-\xff]/', $item['name'])) {
                            $item['name'] = $item['name'].'层';
                        }
                        if (!empty($item)) {
                            $item['type'] = 'layer';
                            $housePosition[] = $item;
                        }
                    }
                }
                return $housePosition;
            case 'layer':
                $db_house_village = new HouseVillage();
                $village_info = $db_house_village->getOne($village_id,'village_id,village_name');
                // 楼层下一级是房屋
                $where[] = ['village_id', '=', $village_id];
                $where[] = ['layer_id', '=', $id];
                $where[] = ['status', '<>', 4];
                // 查询单元
                $list = $this->getUserVacancyList($where, 'room as name,pigcms_id as id,room,layer_id,floor_id,single_id','sort desc, pigcms_id asc');
                $housePosition = [];
                if (!empty($list)) {
                    foreach ($list as $val) {
                        if (isset($val['single_id']) && isset($val['floor_id']) && isset($val['layer_id']) && isset($val['id'])) {
                            $address = $this->getSingleFloorRoom($val['single_id'], $val['floor_id'], $val['layer_id'], $val['id'], $village_id);
                        } else {
                            $address = '';
                        }
                        if ($address && isset($village_info['village_name']) && $village_info['village_name']) {
                            $address = $village_info['village_name'].' '.$address;
                        }
                        $item = [];
                        $item['address'] = $address;
                        if (isset($val['id']) && $val['id']) {
                            $item['id'] = $val['id'];
                        }
                        if (isset($val['name']) && $val['name']) {
                            $item['name'] = $val['name'];
                        } elseif (isset($val['room']) && $val['room']) {
                            $item['name'] = $val['room'];
                        }
                        if (!empty($item)) {
                            $item['type'] = 'room';
                            $housePosition[] = $item;
                        }
                    }
                }
                return $housePosition;
            default:
                throw new \think\Exception("请传正确参数");
                break;
        }
    }

    /**
     * Notes: 上传图片
     * @param $file
     * @param string $putFile
     * @return string
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/8/16 14:03
     */
    public function uploads($file, $putFile='v20HouseVillage')
    {
        if ($file) {
            // 上传到本地服务器
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile($putFile, $file);
            if (strpos($savename, "\\") !== false) {
                $savename = str_replace('\\', '/', $savename);
            }
            $imgurl = '/upload/' . $savename;
            $params = ['savepath' => '/upload/' . $imgurl];
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                invoke_cms_model('Image/oss_upload_image', $params);
            }
            return $imgurl;
        } else {
            throw new \think\Exception('请上传图片有效');
        }
    }
    /**
     * @param 获得一条数据
     * @param int $villageId
     * @param bool|string $field
     * @return array|\think\Model|null
     */
    public function getHouseVillageByVillageId($villageId,$field=true)
    {
        $data = (new HouseVillage())->getOne($villageId,$field);
        return $data;
    }

    /**
     * 获取小区id 集合
     * @author: liukezhu
     * @date : 2021/11/5
     * @param $where
     * @param bool $column
     * @return array
     */
    public function getVillageIds($where,$column=true,$field='community_id')
    {
        $village_id_all=[];
        $area_id_all=(new AreaStreet())->getColumn($where,$column);
        if($area_id_all){
            $where = [];
            $where[] = [$field, 'in', $area_id_all];
            $village_id_all=(new HouseVillage())->getColumn($where,'village_id');
        }
        return $village_id_all;
    }

    /**
     * 得到小区某个列的数组
     * @param $where
     * @param string $column 字段名 多个字段用逗号分隔
     * @param string $key   索引
     * @return array
     * @return array
     */
    public function getVillageColumn($where,$column, $key = '') {
        $column = (new HouseVillage())->getColumn($where,$column, $key);
        return $column;
    }

    public function getVillageUserColumn($where,$column, $key = '') {
        $column = (new HouseVillageUserBind())->getColumn($where,$column, $key);
        return $column;
    }

    /**
     * Notes:
     * @param int $type 区分是小区还是物业
     * @param int $property_id 物业id
     * @param int $village_id 小区id
     * @return array
     * @author: wanzy
     * @date_time: 2021/9/28 14:32
     */
    public function houseConfig($type, $property_id, $village_id) {
        $houseConfig = [];
        if (2==$type) {
            // 初始化 物业管理员 数据层
            $where = [];
            $where[] = ['id','=', $property_id];
            $db_house_property = new HouseProperty();
            $info = $db_house_property->get_one($where,'id,property_name,property_logo');
            if (!empty($info)) {
                $info = $info->toArray();
                if (isset($info['id'])) {
                    $houseConfig['id'] = $info['id'];
                }
                if (isset($info['property_logo'])) {
                    $houseConfig['logo'] = replace_file_domain($info['property_logo']);
                }
                if (isset($info['property_name'])) {
                    $houseConfig['name'] = $info['property_name'];
                }
            }
        } elseif (1==$type) {
            // 初始化 物业管理员 数据层
            $db_house_village = new HouseVillage();
            $info = $db_house_village->getOne($village_id,'village_id,village_name,village_logo');
            if (!empty($info)) {
                $info = $info->toArray();
                if (isset($info['village_id'])) {
                    $houseConfig['id'] = $info['village_id'];
                }
                if (isset($info['village_logo'])) {
                    $houseConfig['logo'] = replace_file_domain($info['village_logo']);
                }
                if (isset($info['village_name'])) {
                    $houseConfig['name'] = $info['village_name'];
                }
            }
        }
        if (!empty($houseConfig)) {
            $houseConfig['type'] = $type;
        }
        return $houseConfig;
    }

    /**
     * 移动管理端获取底部导航
     * @author:zhubaodi
     * @date_time: 2021/12/14 14:11
     */
    public function footer_list($village_id)
    {
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($village_id, 'village_id,village_name,property_id');
        $list = [
            [
                "pagePath" => "/pages/Community_menu/index",
                "text" => "首页",
                "iconPath" => "/static/village_icon/index_no.png",
                "selectedIconPath" => "/static/village_icon/index_1.png",
                "type" => 0
            ],
            [
                "pagePath" => "/pages/Community_menu/Workbench",
                "text" => "工作台",
                "iconPath" => "/static/village_icon/work_no.png",
                "selectedIconPath" => "/static/village_icon/work_1.png",
                "type" => 0
            ],
            [
                "pagePath" => "/pages/Community_menu/CollectMoney",
                "text" => "收银台",
                "iconPath" => "/static/village_icon/money_no.png",
                "selectedIconPath" => "/static/village_icon/money_1.png",
                "type" => 0
            ],
            [
                "pagePath" => "/pages/Community_menu/my",
                "text" => "我的",
                "iconPath" => "/static/village_icon/my_no.png",
                "selectedIconPath" => "/static/village_icon/my_1.png",
                "type" => 0
            ]
        ];
        if (!empty($village_info)) {
            $serviceHouseNewPorperty = new HouseNewPorpertyService();
            $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($village_info['property_id']);
            if ($takeEffectTimeJudge) {
                $list = [
                    [
                        "pagePath" => "/pages/Community_menu/index",
                        "text" => "首页",
                        "iconPath" => "/static/village_icon/index_no.png",
                        "selectedIconPath" => "/static/village_icon/index_1.png",
                        "type" => 0
                    ],
                    [
                        "pagePath" => "/pages/Community_menu/Workbench",
                        "text" => "工作台",
                        "iconPath" => "/static/village_icon/work_no.png",
                        "selectedIconPath" => "/static/village_icon/work_1.png",
                        "type" => 0
                    ],
                    [
                        "pagePath" => "/pages/Community_menu/selectHouseCar",
                        "text" => "收银台",
                        "iconPath" => "/static/village_icon/money_no.png",
                        "selectedIconPath" => "/static/village_icon/money_1.png",
                        "type" => 0
                    ],
                    [
                        "pagePath" => "/pages/Community_menu/my",
                        "text" => "我的",
                        "iconPath" => "/static/village_icon/my_no.png",
                        "selectedIconPath" => "/static/village_icon/my_1.png",
                        "type" => 0
                    ]
                ];
            }
        }

        return $list;

    }

    /**
     * 获取模板id
     * print_type 0已缴费账单模板设置 1待缴账单模板设置
     */
    public function getPrintTemplateId($village_id,$print_type=0){
        $village_info=new HouseVillageInfo();
        $template_id=0;
        $village_info_obj=$village_info->getOne(['village_id'=>$village_id],'*');
        if($village_info_obj && !$village_info_obj->isEmpty()){
            if($print_type==0){
                $template_id=$village_info_obj['print_template_id'];
            }elseif($print_type==1){
                $template_id=$village_info_obj['nopay_print_template_id'];
            }elseif($print_type==3){
                $template_id=$village_info_obj['refund_template_id'];
            }
        }
        return $template_id;
    }
    
    /*
     **设置绑定打印模板
     **print_type 0已缴费账单模板设置 1待缴账单模板设置
     ** 
    */
    public function setPrintTemplateId($print_type,$village_id,$template_id=0){
        $village_info=new HouseVillageInfo();
        if($print_type == 2){
            $param=array('materiel_print_template_id'=>$template_id);
            $village_info->saveOne(['village_id'=>$village_id],$param);
        }elseif($print_type == 1){
            $param=array('nopay_print_template_id'=>$template_id);
            $village_info->saveOne(['village_id'=>$village_id],$param);
        }elseif($print_type == 3){
            $param=array('refund_template_id'=>$template_id);
            $village_info->saveOne(['village_id'=>$village_id],$param);
        }else{
            $param=array('print_template_id'=>$template_id);
            $village_info->saveOne(['village_id'=>$village_id],$param);
        }
        return $template_id;
    }
    
    /**
     * 设置绑定打印模板 废弃
     * @author: liukezhu
     * @date : 2022/2/15
     * @param $type
     * @param $village_id
     * @param array $param
     * @return array
     */
    public function bindPrintTemplateId($type,$village_id,$param=[]){
        $village_info=new HouseVillageInfo();
        $data=[
            'template_id'=>0
        ];
        $template_id=$village_info->getOne(['village_id'=>$village_id],'print_template_id');
        if($type == 1){ //获取参数
            if($template_id && $template_id['print_template_id']){
                $data['template_id'] =$template_id['print_template_id'];
            }
        }
        else{
            if(empty($template_id)){
                throw new \think\Exception('社区数据暂未配置，请前往小区控制台=>基本信息');
            }
            $village_info->saveOne(['village_id'=>$village_id],$param);
        }
        return $data;

    }
    /**
     * 校验小区配置项
     * @author: liukezhu
     * @date : 2022/4/7
     * @param $village_id
     * @param $field
     * @return int
     */
    public function checkVillageField($village_id,$field){
        $status=0;
        if(!empty($village_id)){
            $village_info=(new HouseVillageInfo())->getOne(['village_id'=>$village_id],$field);
            if($village_info && !$village_info->isEmpty()){
                $status=$village_info[$field];
            }
        }
        return $status;
    }


    /**
     * 预存、预缴数据
     * @author: liukezhu
     * @date : 2022/5/17
     * @param $village_id
     * @param $pigcms_id
     * @return array
     * $is_hidde 1屏蔽预缴
     */
    public function getVillageStorage($village_id,$pigcms_id,$is_hidde=0){
        $data=[];
        $status=$this->checkVillageField($village_id,'is_new_storage');
        $icon_url = cfg('site_url') . '/static/images/house/';
        $yujiao=get_base_url('pages/houseMeter/NewCollectMoney/preStorage?pigcms_id='.$pigcms_id);
        $yucun=get_base_url('pages/houseMeter/preStorage/preStorageIndex?pigcms_id='.$pigcms_id);
        switch (intval($status)) {
            case 0://预缴
                $data=[
                    [ 'icon'=>$icon_url.'yujiao.png', 'url'=>$yujiao],
                ];
                if($is_hidde==1){
                    $data=[];
                }
                break;
            case 1://预存
                $data=[
                    [ 'icon'=>$icon_url.'yucun.png', 'url'=>$yucun],
                ];
                break;
            case 2://预存、预缴
                $data=[
                    [ 'icon'=>$icon_url.'yucun.png', 'url'=>$yucun],
                    [ 'icon'=>$icon_url.'yujiao.png', 'url'=>$yujiao]
                ];
                if($is_hidde==1){
                    $data=[
                        [ 'icon'=>$icon_url.'yucun.png', 'url'=>$yucun],
                    ];
                }
                break;
        }
        return $data;

    }

    public function tabList($login_role,$adminUser=[]) {
        $adminLoginService = new AdminLoginService();
        $showTabRoleArr = $adminLoginService->showTabRoleArr;
        $showTabRolePropertyArr = $adminLoginService->showTabRolePropertyArr;
        $arr = [];
        $configCustomizationService=new ConfigCustomizationService();
        $isHangLanShequCustom=$configCustomizationService->getHangLanShequCustom();
        $tabVillageTitle='小区管理';
        if($isHangLanShequCustom==1){
            $tabVillageTitle='智慧社区';
        }
        if (in_array($login_role, $showTabRoleArr)) {
            $tabLeft = [];
            $site_url = cfg('site_url');
            $tabBackgroundColor = '#001529';// 左侧背景色
            $tabChooseColor = '#002140';// 选中背景色
            if (in_array($login_role, $showTabRolePropertyArr)) {
                $village_id = 0;
                $weather_info_key='propertyChange'.$adminUser['property_id'];
                $service_config_data = new ConfigDataService();
                $configData = $service_config_data->get_one(array('name'=>$weather_info_key));
                $serviceHouseVillage = new HouseVillage();
                $whereVillage = [];
                $whereVillage[] = ['property_id','=',$adminUser['property_id']];
                $whereVillage[] = ['status','in',[0,1]];
                $villageInfo = $serviceHouseVillage->getOneByConfigOrder($whereVillage, 'a.village_id', ['b.village_sort'=>'DESC','village_id'=>'ASC']);
                if (!$villageInfo || !isset($villageInfo['village_id'])) {
                    $arr = [];
                    return $arr;
                }
                if ($configData && isset($configData['value']) && $configData['value']) {
                    $village_id = $configData['value'];
                    $whereArr=array('village_id'=>$village_id,'property_id'=>$adminUser['property_id']);
                    $houseVillageObj=$serviceHouseVillage->getOneOrder($whereArr,'village_id,village_name,village_address,status');
                    if(empty($houseVillageObj) || !in_array($houseVillageObj['status'],[0,1])){
                        if ($villageInfo&&isset($villageInfo['village_id'])) {
                            $village_id = $villageInfo['village_id'];
                        }
                    }
                } elseif(isset($adminUser['property_id']) && !empty($adminUser['property_id'])){
                    if ($villageInfo&&isset($villageInfo['village_id'])) {
                        $village_id = $villageInfo['village_id'];
                    }
                }
                $tabLeft[] = [
                    'tabTitle'=> '物业配置',
                    'backColor'=> $tabChooseColor,
                    'icon' => 'iconqiyeyuanquwuye',
                    'color'=> '#ffffff',
                    'sysName'=> 'property',
                    'href'=> '',
                ];
                $tabLeft[] = [
                    'tabTitle'=>$tabVillageTitle,
                    'backColor'=> $tabBackgroundColor,
                    'icon' => 'iconxiaoqu',
                    'color'=> '',
                    'sysName'=> 'village',
                    'href'=>  $site_url."/shequ.php?g=House&c=NewProperty&a=village_login&village_id={$village_id}&&villageTabLogin={$adminUser['property_id']}",
                ];
            } else {
                $property_id = 0;
                if(isset($adminUser['property_id']) && !empty($adminUser['property_id'])){
                    $property_id = $adminUser['property_id'];
                }
                $tabLeft[] = [
                    'tabTitle'=> '物业配置',
                    'backColor'=> $tabBackgroundColor,
                    'icon' => 'iconqiyeyuanquwuye',
                    'color'=> '',
                    'sysName'=> 'property',
                    'href'=>  $site_url."/shequ.php?g=House&c=Login&a=property_login&property_id={$property_id}&&villageTabLogin={$adminUser['village_id']}",
                ];
                $tabLeft[] = [
                    'tabTitle'=> $tabVillageTitle,
                    'backColor'=> $tabChooseColor,
                    'icon' => 'iconxiaoqu',
                    'color'=> '#ffffff',
                    'sysName'=> 'village',
                    'href'=> '',
                ];
            }
            $arr['tabLeft'] = $tabLeft;
            $arr['tabBackgroundColor'] = $tabBackgroundColor;
            $arr['tabChooseColor'] = $tabChooseColor;
        }
        return $arr;
    }

    public function changeVillage($login_role,$adminUser=[]) {
        $adminLoginService = new AdminLoginService();
        $changeVillageRoleArr = $adminLoginService->changeVillageRoleArr;
        $serviceHouseVillage = new HouseVillage();
        $arr = [];
        if (in_array($login_role, $changeVillageRoleArr)) {
            $whereVillage = [];
            if (isset($adminUser['village_id'])&&$adminUser['village_id']) {
                $whereVillage[] = ['a.village_id','<>',$adminUser['village_id']];
            }
            $whereVillage[] = ['a.property_id','=',$adminUser['property_id']];
            $whereVillage[] = ['a.status','in',[0,1]];
            $villageList = $serviceHouseVillage->getOneByConfigList($whereVillage, 'a.village_id,a.village_name', ['b.village_sort'=>'DESC','village_id'=>'ASC']);
            if (!empty($villageList)) {
                $villageList = $villageList->toArray();
            }
            $changList = [];
            if (!empty($villageList)) {
                $site_url = cfg('site_url');
                foreach ($villageList as $item) {
                    $changList[] = [
                        'name' => $item['village_name'],
                        'village_id' => $item['village_id'],
                        'url' => $site_url."/shequ.php?g=House&c=NewProperty&a=village_login&village_id={$item['village_id']}&villageTabType={$adminUser['property_id']}",
                    ];
                }
            }
            $arr['changList'] = $changList;
            $arr['changNum'] = count($changList);
        }else if(in_array($login_role, $adminLoginService->villageUserArr) && isset($adminUser['wid']) && $adminUser['wid']>0){
            $houseWorkerDb=new HouseWorker();
            $whereArr=array('wid'=>$adminUser['wid'],'village_id'=>$adminUser['village_id']);
            $houseWorkerObj=$houseWorkerDb->get_one($whereArr,'*');
            if($houseWorkerObj && !$houseWorkerObj->isEmpty()){
                $houseWorker=$houseWorkerObj->toArray();
                if(isset($houseWorker['hw_id']) && ($houseWorker['hw_id']>0)){
                    $housePropertyVillageWorkerRelation=new HousePropertyVillageWorkerRelation();
                    $whereRArr=array(['r.hw_id','=',$houseWorker['hw_id']]);
                    $whereRArr[]=array('r.xtype','=',2);
                    $whereRArr[]=array('r.village_id','>',0);
                    $whereRArr[]=array('r.del_time','<',1);
                    $whereRArr[]=array('r.wid','<>',$houseWorker['wid']);
                    $whereRArr[]=array('w.hw_id','=',$houseWorker['hw_id']);
                    $whereRArr[]=array('w.wid','<>',$houseWorker['wid']);
                    $whereRArr[]=array('w.xtype','=',2);
                    $whereRArr[]=array('w.relation_id','>',0);
                    $whereRArr[]=array('w.status','in',[0,1]);
                    $whereRArr[]=array('w.is_del','<',1);
                    $whereRArr[]=array('v.status','in',[0,1]);
                    $property_id=isset($adminUser['property_id']) ? $adminUser['property_id']:0;
                    if($property_id<1 && $houseWorker['property_id']>0){
                        $property_id=$houseWorker['property_id'];
                    }
                    if($property_id>0){
                        $whereRArr[]=array('v.property_id','=',$property_id);
                    }
                    $villageListObj=$housePropertyVillageWorkerRelation->getRelationVillageLists($whereRArr,'w.wid,w.relation_id,r.village_id,v.village_name,v.property_id');
                    if($villageListObj && !$villageListObj->isEmpty()){
                        $villageList = $villageListObj->toArray();
                        $changList = [];
                        if (!empty($villageList)) {
                            $site_url = cfg('site_url');
                            foreach ($villageList as $item) {
                                $changList[] = [
                                    'name' => $item['village_name'],
                                    'village_id' => $item['village_id'],
                                    'url' => $site_url."/shequ.php?g=House&c=NewProperty&a=village_login&village_id={$item['village_id']}&wid={$item['wid']}&house_admin_id={$item['relation_id']}",
                                ];
                            }
                        }
                        $arr['village_id'] = $adminUser['village_id'];
                        $arr['wid'] = $houseWorker['wid'];
                        $arr['hw_id'] = $houseWorker['hw_id'];
                        $arr['changList'] = $changList;
                        $arr['changNum'] = count($changList);
                    }
                }
            }
        }
        return $arr;
    }

	/**
	 *
	 * @param array  $where
	 * @param bool   $field
	 * @param string $order
	 *
	 * @return array|\think\Model|null
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
	public function getHouseNewOrderLogServiceTime($where=[],$field=true,$order='id DESC')
	{
		return (new HouseNewOrderLog())->getOne($where,$field,$order);
	}

    public function addHouseNewOrderLog($datas=array()){
	    if(!empty($datas)){
            $houseNewOrderLogDb=new HouseNewOrderLog();
            return  $houseNewOrderLogDb->addOne($datas);
        }
	    return false;
    }
    /**
     * 获取楼层数量
     * @param $where
     * @return int
     */
    public function getLayerCount($where)
    {
        $db_house_village_layer = new HouseVillageLayer();
        $count = $db_house_village_layer->getCount($where);
        return $count;
    }


    //检查是否有相关权限
    function checkPermissionMenu($pid=0,$adminUser=array(),$login_role=0,$dismissRole=array(3,7,105,303))
    {
        $logomenus = array();
        if (isset($adminUser['menus']) && !empty($adminUser['menus'])) {
            if (is_array($adminUser['menus'])) {
                $logomenus = $adminUser['menus'];
            } else {
                $logomenus = explode(',', $adminUser['menus']);
            }
        }
        //return 0;
        $db_house_menu_new = new HouseMenuNew();
        $whereArr = array('id' => $pid);
        $menu_new_obj = $db_house_menu_new->getOne($whereArr);
        if (empty($menu_new_obj) || $menu_new_obj->isEmpty()) {
            return 1;
        }
        if (!empty($dismissRole) && in_array($login_role, $dismissRole)) {
            return 1;
        }
        if (empty($logomenus)) {
            return 1;
        } else if (!empty($logomenus) && in_array($pid, $logomenus)) {
            return 1;
        } else {
            return 0;
        }

    }
    
    //更新字段数值 减
    public function updateFieldMinusNum($whereArr = array(), $fieldv = 1, $fieldname = 'now_sms_number')
    {
        $db_house_village = new HouseVillage();
        if (empty($whereArr) || empty($fieldname)) {
            return false;
        }
        $ret = $db_house_village->updateFieldMinusNum($whereArr, $fieldname, $fieldv);
        return $ret;
    }
    
    //获取小区、楼栋、单元、房间的别名替代
    public function getHouseAlias($name='',$village_id=0)
    {
        $return = [
            'house_name'=>'',
            'single_name'=>'',
            'floor_name'=>'',
            'room_name'=>'',
        ];
        if(customization('set_house_alias') == 1){
            $return['house_name'] = cfg('house_name');
            $return['single_name'] = cfg('single_name');
            $return['floor_name'] = cfg('floor_name');
            $return['room_name'] = cfg('room_name');
            if($village_id){
                $village_config_data = (new HouseVillage())->getOne($village_id,'single_name,floor_name,room_name');
                $village_config_data = $village_config_data ? $village_config_data->toArray() : [];
                if($village_config_data){
                    $return['single_name'] = $village_config_data['single_name'] ?: $return['single_name'];
                    $return['floor_name'] = $village_config_data['floor_name'] ?: $return['floor_name'];
                    $return['room_name'] = $village_config_data['room_name'] ?: $return['room_name'];
                }
            }
        }else{
            $return['house_name'] = cfg('house_name');
        }
        if($name){
            return $return[$name]??'';
        }
        return $return;
    }
    
    /**
     * 获取配置信息
     */
    public function getConfig($villageId)
    {
        // 网站名称
        $returnArr['site_name'] = cfg('site_name');
        $returnArr['site_short_name'] = cfg('site_short_name');
        // 网站logo
        $returnArr['site_logo'] = cfg('site_logo');
        // 网站白色logo
        $returnArr['site_white_logo'] = cfg('site_white_logo');
        // 网站是否开始https 若开启强制使用
        $returnArr['use_https'] = cfg('use_https') ?? false;
        // 系统后台LOGO
        if (cfg('system_admin_logo')) {
            $system_admin_logo = cfg('system_admin_logo');
            $system_admin_logo = replace_file_domain($system_admin_logo);
        } else {
            $system_admin_logo = cfg('site_url') . "/tpl/System/Static/images/pigcms_logo.png";
        }
        $returnArr['system_admin_logo'] = $system_admin_logo;
        // 网站描述
        $returnArr['site_desc'] = cfg('site_desc') ?? '';
        //网站网址
        $returnArr['site_url'] = cfg('site_url');
        //在线咨询
        $returnArr['site_qq'] = cfg('site_qq');
        //公众号二维码
        $returnArr['wechat_qrcode'] = cfg('wechat_qrcode');
        //联系电话
        $returnArr['site_phone'] = cfg('site_phone');
        //联系邮箱
        $returnArr['site_email'] = cfg('site_email');
        //是否为演示站
        $returnArr['is_demo_domain'] = cfg('is_demo_domain') ? true : false;
        //底部版权信息
        $returnArr['copyright_txt'] = 'Copyright © ' . date('Y') . ' ' . cfg('site_name') . '' . (cfg('top_domain') ?? '') . '版权所有 ' . (cfg('site_icp') ?: '');
        $returnArr['open_bd_spread'] = cfg('open_bd_spread') ?? 0;
        //商家注册协议
        $returnArr['store_register_agreement'] = cfg('store_register_agreement');
        $returnArr['store_privacy_policy'] = cfg('store_privacy_policy');

        // 是否开启商家注册短信验证
        $returnArr['open_merchant_reg_sms'] = cfg('open_merchant_reg_sms');

        //是否开启多语言切换
        $returnArr['open_multilingual'] = cfg('open_multilingual') ? cfg('open_multilingual') : '0';
        //语言配置
        $returnArr['lang_config'] = (new LangService())->langList();
        // 餐饮团购分类 编辑器数量配置
        $returnArr['group_content_switch'] = cfg('group_content_switch');
        // 是否开启国际手机
        $returnArr['international_phone'] = cfg('international_phone');
        // 餐饮团购分类 编辑器数量配置
        $returnArr['group_content_switch'] = cfg('group_content_switch');
        // 是否开启新版业务经理、业务员功能
        $returnArr['open_bd_spread_new'] =cfg('open_bd_spread_new') ?? 0;

        //物业小区使用自己的logo
        $returnArr['property_self_logo'] = cfg('property_self_logo') ? cfg('property_self_logo') : '0';

        //小区驾驶舱
        $returnArr['cockpit'] = cfg('cockpit') ? cfg('cockpit') : 0;

        //顶象验证码安全的appid
        $returnArr['code_captcha_url'] = (cfg('qcloud_captcha_appid') || cfg('dingxiang_captcha_appid')) ?cfg('site_url') . '/wap.php?c=Login&a=app_sms_safe&from=pc_iframe' : '';

        //硬件大数据
        $returnArr['hardware_switch'] = empty(cfg('hardware_switch')) ? false : true;
        $show_sky_cockpit_entrance=cfg('show_sky_cockpit_entrance');
        $returnArr['show_sky_cockpit_entrance'] = empty($show_sky_cockpit_entrance) ? false : true;
        $configCustomizationService=new ConfigCustomizationService();
        $isHangLanShequCustom=$configCustomizationService->getHangLanShequCustom();
        $returnArr['grid_event_center_name']='网格事件中心';
        if($isHangLanShequCustom==1){
            $returnArr['grid_event_center_name']='监控中心';
        }
        //社区别名配置
        $returnArr['house_name'] = cfg('house_name')?:L_('小区');
        $returnArr['single_name'] = cfg('single_name')?:L_('楼栋');
        $returnArr['floor_name'] = cfg('floor_name')?:L_('单元');
        $returnArr['room_name'] = cfg('room_name')?:L_('房间');
        if($villageId && customization('set_house_alias')==1){
            $villageConfigData = (new HouseVillage())->getOne($villageId,'single_name,floor_name,room_name');
            $villageConfigData = $villageConfigData ? $villageConfigData->toArray() : [];
            if($villageConfigData){
                $returnArr['single_name'] = $villageConfigData['single_name'] ?: $returnArr['single_name'];
                $returnArr['floor_name'] = $villageConfigData['floor_name'] ?: $returnArr['floor_name'];
                $returnArr['room_name'] = $villageConfigData['room_name'] ?: $returnArr['room_name'];
            }
        }
        return $returnArr;
    }

    public function phoneDesensitizationV($phone = '', $village_id = 0, $village_info = array())
    {
        if (empty($phone)) {
            return $phone;
        }
        $houseVillageDb = new HouseVillage();
        $phone_tmp = $houseVillageDb->phone_desensitization_v($phone, $village_id, $village_info);
        return $phone_tmp;
    }

    public function idnumDesensitizationV($id_card = '', $village_id = 0, $village_info = array())
    {
        if (empty($id_card)) {
            return $id_card;
        }
        $houseVillageDb = new HouseVillage();
        $id_card_tmp = $houseVillageDb->idnum_desensitization_v($id_card, $village_id, $village_info);
        return $id_card_tmp;
    }
}
