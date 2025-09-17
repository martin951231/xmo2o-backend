<?php
/**
 * Created by PigCMS.
 * User: wanzy
 * Date: 2019/9/2.
 * Time: 17:54.
 */
class House_face_deviceModel extends Model{
    public $device_type_info = array(
        0 => array(
            'device_type' => 1,
            'name' => 'A1智能门禁机'
        ),
        1 => array(
            'device_type' => 2,
            'name' => 'A2智能门禁机'
        ),
        2 => array(
            'device_type' => 3,
            'name' => 'A3智能门禁机'
        ),
        3 => array(
            'device_type' => 4,
            'name' => 'A4智能门禁机'
        ),
        4 => array(
            'device_type' => 5,
            'name' => 'A5智能门禁机'
        ),
        5 => array(
            'device_type' => 6,
            'name' => 'A6智能门禁机'
        ),
        6 => array(
            'device_type' => 7,
            'name' => 'A7智能门禁机'
        ),
        7 => array(
            'device_type' => 61,
            'name' => 'A185智能门禁机'
        ),
        8 => array(
            'device_type' => 21,
            'name' => 'D1智能门禁机'
        ),
        9 =>array(
            'device_type' => 22,
            'name' => 'D2智能门禁机'
        ),
        10 => array(
            'device_type' => 23,
            'name' => 'D3智能门禁机'
        ),
        11 => array(
            'device_type' => 24,
            'name' => 'D4智能门禁机'
        ),
        12 => array(
            'device_type' => 88,
            'name' => '大华人脸门禁'
        ),
        13=>array(
            'device_type' => 26,
            'name' => 'D6智能门禁机'
        ),
        14 => array(
            'device_type' => 29,
            'name' => '朵普智能门禁机'
        ),
        15 =>array(
            'device_type' => 30,
            'name' => '260云智能门禁'
        ),
        16 =>array(
            'device_type' => 31,
            'name' => '西函智能门禁'
        ),
    );
    public $device_brand = array(
        0 => array(
            'brand_type' => 1,
            'brand_name' => '泰首'
        ),
        1 => array(
            'brand_type' => 2,
            'brand_name' => '笛虎云'
        ),
        2 => array(
            'brand_type' => 3,
            'brand_name' => '海康云眸'
        ),
        3 => array(
            'brand_type' => 4,
            'brand_name' => '大华'
        ),
        4 => array(
            'brand_type' => 5,
            'brand_name' => '狄耐克'
        ),
        5 => array(
            'brand_type' => 6,
            'brand_name' => '蓝牛'
        ),
        6 => array(
            'brand_type' => 7,
            'brand_name' => '快鲸'
        ),
        7 => array(
            'brand_type' =>8,
            'brand_name' => '智国互联'
        ),
        8 => array(
            'brand_type' =>9,
            'brand_name' => '华安视讯'
        ),
    );

    // 设备序列号拦截白名单
    public $blockWhitelistDeviceTypeArr = [
        5,6,8,21,22,23,24,26,61,29,88,30,31
    ];

    public $blockWhitelistCameraDeviceBrandArr = [
        'brand_haikang'
    ];

    public $brandHaikangCodeTip = [
        'title' => '设备验证码如何查看/修改？',
        'href' => 'https://hkrobot.hikvision.com/servlet/WXShow?action=sac&wxcId=121&sysNum=145716889796196&FromUserName=oNNCAjgtBqx25nruMCrRDWLgXdRA&sId=321219&subId=128817',
    ];

    /**
     * @var integer 海康设备类型 目前5
     */
    const HIK_DEVICE_TYPE = 5;
    /**
     * @var integer 大华设备类型 目前88
     */
    const DH_DEVICE_TYPE = 88;
    /**
     * 人脸设备已经添加过的默认协议 也是判断新的添加和编辑时候必传协议的依据
     * @var array
     */
    public $defaultProtocol = [
        self::HIK_DEVICE_TYPE => self::HIK_YUNMO_WAIBU,
        self::DH_DEVICE_TYPE  => self::DH_H8900,
    ];

    /**
     * 已经对接了新的协议相关
     * @var int[] 
     */
    public $newProtocol = [
        self::HIK_YUNMO_NEIBU_SHEQU,
        self::DH_YUNRUI,
        self::HIK_DEVICE_GATEWAY,
        self::HIK_DEVICE_GATEWAY_CUSTOM,
    ];
    
    /** 海康HIK 对接协议 以1开头 目前默认为【云牟外部独立协议】**/
    /**
     * @var string 标记设备品牌 '海康'
     */
    const HIK_BRAND_KEY = 'brand_haikang';
    /**
     * @var integer 标记协议 '云牟外部独立协议'
     */
    const HIK_YUNMO_WAIBU = 11;
    /**
     * @var string 对应协议名称 '云牟外部独立协议'
     */
    const HIK_YUNMO_WAIBU_TITLE = '云牟外部独立协议';
    /**
     * @var integer 标记协议 '云牟内部社区协议'
     */
    const HIK_YUNMO_NEIBU_SHEQU = 12;
    /**
     * @var string 对应协议名称 '云牟内部社区协议'
     */
    const HIK_YUNMO_NEIBU_SHEQU_TITLE = '云牟内部社区协议';
    /**
     * @var integer 标记协议 '海康综合安防管理平台 v1.5.100 版本'
     */
    const HIK_ISC_V151 = 13;
    /**
     * @var string 对应协议名称 '海康综合安防管理平台 v1.5.100 版本'
     */
    const HIK_ISC_V151_TITLE = '海康综合安防管理平台 v1.5.100 版本';

    /**
     * ==============================================
     *  Hik Device Gateway 海康网关
     * ==============================================
     */
    /**
     * @var integer Hik Device Gateway 海康网关(平台)
     */
    const HIK_DEVICE_GATEWAY = 16;
    const HIK_DEVICE_GATEWAY_TITLE = 'Hik Device Gateway 海康网关(平台)';

    /**
     * @var 小区自己配置
     */
    const HIK_DEVICE_GATEWAY_CUSTOM = 17;
    const HIK_DEVICE_GATEWAY_CUSTOM_TITLE =  'Hik Device Gateway 海康网关(小区)';
    
    /** 大华DH 对接协议 以3开头 目前默认为【大华h8900平台】**/
    /**
     * @var string 标记设备品牌 '大华'
     */
    const DH_BRAND_KEY = 'brand_dahua';
    /**
     * @var integer 标记协议 '大华云睿开放平台协议'
     */
    const  DH_YUNRUI = 31;
    /**
     * @var string 对应协议名称 '大华云睿开放平台协议'
     */
    const DH_YUNRUI_TITLE = '大华云睿开放平台协议';
    /**
     * @var integer 标记协议 '大华h8900平台'
     */
    const  DH_H8900 = 32;
    /**
     * @var string 对应协议名称 '大华h8900平台'
     */
    const DH_H8900_TITLE = '大华h8900平台';

    /**
     * @var integer 标记协议 '6000C社区边缘'
     */
    const HIK_YUNMO_NEIBU_6000C = 14;

    /**
     * @var string 对应协议名称 '6000C社区边缘'
     */
    const HIK_YUNMO_NEIBU_6000C_TITLE = '6000C社区边缘';

    //**↓↓↓↓↓↓ 对应数据表【device_bind_info】中 bind_type ↓↓↓↓↓↓**//
    /**
     * @var int 绑定的人脸设备信息
     */
    const BIND_FACE_DEVICE              = 2;
    //**↑↑↑↑↑↑ 对应数据表【device_bind_info】中 bind_type ↑↑↑↑↑↑**//
    
    /**
     * 对应设备协议相关信息数组
     * @var array[][]
     */
    public $thirdProtocol = [
        self::HIK_BRAND_KEY => [
            self::HIK_YUNMO_WAIBU=> [
                'brand_key'     => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_YUNMO_WAIBU,
                'thirdTitle'    => self::HIK_YUNMO_WAIBU_TITLE,
            ],
            self::HIK_YUNMO_NEIBU_SHEQU=> [
                'brand_key'     => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_YUNMO_NEIBU_SHEQU,
                'thirdTitle'    => self::HIK_YUNMO_NEIBU_SHEQU_TITLE,
            ],
            self::HIK_DEVICE_GATEWAY => [
                'brand_key'     => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_DEVICE_GATEWAY,
                'thirdTitle'    => self::HIK_DEVICE_GATEWAY_TITLE,
            ],
            self::HIK_DEVICE_GATEWAY_CUSTOM => [
                'brand_key'     => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_DEVICE_GATEWAY_CUSTOM,
                'thirdTitle'    => self::HIK_DEVICE_GATEWAY_CUSTOM_TITLE
            ],
//            self::HIK_ISC_V151=> [
//                'brand_key'     => self::HIK_BRAND_KEY,
//                'thirdProtocol' => self::HIK_ISC_V151,
//                'thirdTitle'    => self::HIK_ISC_V151_TITLE,
//            ],
        ],
        self::DH_BRAND_KEY => [
            self::DH_YUNRUI=> [
                'brand_key'     => self::DH_BRAND_KEY,
                'thirdProtocol' => self::DH_YUNRUI,
                'thirdTitle'    => self::DH_YUNRUI_TITLE,
            ],
//            self::DH_H8900=> [
//                'brand_key'     => self::DH_BRAND_KEY,
//                'thirdProtocol' => self::DH_H8900,
//                'thirdTitle'    => self::DH_H8900_TITLE,
//            ],
        ],
    ];


    /***
     * 获取设备协议
     * @param bool $showTxt
     * @return \array[][]
     */
    public function getThirdProtocolArr()
    {
        return [
            self::HIK_YUNMO_WAIBU => [
                'brand_key' => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_YUNMO_WAIBU,
                'thirdTitle' => self::HIK_YUNMO_WAIBU_TITLE,
                'brand' => '海康',
            ],
            self::HIK_YUNMO_NEIBU_SHEQU => [
                'brand_key' => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_YUNMO_NEIBU_SHEQU,
                'thirdTitle' => self::HIK_YUNMO_NEIBU_SHEQU_TITLE,
                'brand' => '海康',
            ],
            self::HIK_ISC_V151 => [
                'brand_key' => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_ISC_V151,
                'thirdTitle' => self::HIK_ISC_V151_TITLE,
                'brand' => '海康',
            ],
            self::HIK_DEVICE_GATEWAY => [
                'brand_key' => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_DEVICE_GATEWAY,
                'thirdTitle' => self::HIK_DEVICE_GATEWAY_TITLE,
                'brand' => '海康',
            ],
            self::HIK_DEVICE_GATEWAY_CUSTOM => [
                'brand_key' => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_DEVICE_GATEWAY_CUSTOM,
                'thirdTitle' => self::HIK_DEVICE_GATEWAY_CUSTOM_TITLE,
                'brand' => '海康',
            ],
            self::HIK_YUNMO_NEIBU_6000C => [
                'brand_key' => self::HIK_BRAND_KEY,
                'thirdProtocol' => self::HIK_YUNMO_NEIBU_6000C,
                'thirdTitle' => self::HIK_YUNMO_NEIBU_6000C_TITLE,
                'brand' => '海康',
            ],
            self::DH_YUNRUI => [
                'brand_key' => self::DH_BRAND_KEY,
                'thirdProtocol' => self::DH_YUNRUI,
                'thirdTitle' => self::DH_YUNRUI_TITLE,
                'brand' => '大华',
            ],
            self::DH_H8900 => [
                'brand_key' => self::DH_BRAND_KEY,
                'thirdProtocol' => self::DH_H8900,
                'thirdTitle' => self::DH_H8900_TITLE,
                'brand' => '大华',
            ],
        ];
    }

    // 对应类型门禁机 上面新增请按照以下规则   1-20 为我们主推类型1-2暂时不推了；21-40 为我们定制类型；61之后为我们自身开发门禁机
    public $device_type_value = array(
        1 => 'A1智能门禁机',
        2 => 'A2智能门禁机',
        3 => 'A3智能门禁机',
        4 => 'A4智能门禁机',
        5 => 'A5智能门禁机',
        6 => 'A6智能门禁机',
        7 => 'A7智能门禁机',
        8 => 'A8智能门禁机',
        9 => 'A9智能门禁机',
        10 => 'A10智能门禁机',
        21 => 'D1智能门禁机',
        22 => 'D2智能门禁机',
        23 => 'D3智能门禁机',
        24 => 'D4智能门禁机',
        25 => 'D5智能门禁机',
        26 => 'D6智能门禁机',
        27 => 'D7智能门禁机',
        28 => 'D8智能门禁机',
        29 => '朵普智能门禁机',
        30 => '260云智能门禁机',
        31 => '西函智能门禁机',
        61 => 'A185智能门禁机',
        88 => '大华人脸门禁',
    );

    // 人员活动轨迹表中 捕捉人员来源
    public $logFromArr  = [1=>'人脸识别',2=>'用户操作',3=>'IC卡刷卡',6=>'摄像机抓拍',21=>'蓝牙开门',31=>'二维码开门',51=>'高空抛物'];
    
    public function getLogFromText($logFrom, $card_number = '') {
        $logFromTxt = '';
        switch ($logFrom) {
            case 1:
                $logFromTxt = '人脸识别';
                break;
            case 2:
                $logFromTxt = '用户操作';
                break;
            case 3:
                if ($card_number && intval($card_number) > 100000000000) {
                    $logFromTxt = 'IC卡刷卡(访客二维码)';
                } else {
                    $logFromTxt = 'IC卡刷卡';
                }
                break;
            case 6:
                $logFromTxt = '摄像机抓拍';
                break;
            case 10:
                $logFromTxt = '证件开门';
                break;
            case 11:
                $logFromTxt = '身份证开门';
                break;
            case 21:
                $logFromTxt = '蓝牙开门';
                break;
            case 31:
                if (strpos($card_number, 'owner') !== false) {
                    $logFromTxt = '二维码开门(业主)';
                } elseif (strpos($card_number, 'family') !== false) {
                    $logFromTxt = '二维码开门(家属)';
                } elseif (strpos($card_number, 'tenant') !== false) {
                    $logFromTxt = '二维码开门(租客)';
                } elseif (strpos($card_number, 'worker') !== false) {
                    $logFromTxt = '二维码开门(工作人员)';
                } elseif (strpos($card_number, 'pass') !== false) {
                    $logFromTxt = '二维码开门(通行证)';
                } elseif (strpos($card_number, 'visitor') !== false) {
                    $logFromTxt = '二维码开门(访客)';
                } else {
                    $logFromTxt = '二维码开门';
                }
                break;
            case 41:
                $logFromTxt = '密码开门';
                break;
            case 42:
                $logFromTxt = '动态密码开锁';
                break;
            case 45:
                $logFromTxt = '指纹开门';
                break;
            case 51:
                $logFromTxt = '高空抛物';
                break;
            case 61:
            case 62:
                $logFromTxt = '健康码开门';
                break;
            case 70:
                $logFromTxt = '组合认证刷卡加密码开锁';
                break;
            case 71:
                $logFromTxt = '组合认证刷卡加指纹开锁';
                break;
            case 72:
                $logFromTxt = '组合认证刷卡加指纹加密码开锁';
                break;
            case 73:
                $logFromTxt = '组合认证指纹加密码开锁';
                break;
            case 74:
                $logFromTxt = '组合认证人脸加指纹开锁';
                break;
            case 75:
                $logFromTxt = '组合认证人脸加密码开锁';
                break;
            case 76:
                $logFromTxt = '组合认证人脸加刷卡开锁';
                break;
            case 77:
                $logFromTxt = '组合认证人脸加密码加指纹开锁';
                break;
            case 78:
                $logFromTxt = '组合认证人脸加刷卡加指纹开锁';
                break;
            case 79:
                $logFromTxt = '组合认证工号加指纹开锁';
                break;
            case 80:
                $logFromTxt = '组合认证工号加指纹加密码开锁';
                break;
            case 81:
                $logFromTxt = '组合认证工号加人脸开锁';
                break;
            case 90:
                $logFromTxt = '组合认证';
                break;
        }
        return $logFromTxt;
    }

    /**
     * @param $where
     * @param $page
     * @param $page_count
     * @return array|int
     */
    public function get_face_door_list($where,$page,$page_count){
        if(!$where){
            return	array();
        }
        if (!isset($where['is_del'])) {
            $where['is_del'] = 0;
        }
        fdump_api($where, '$where');
        $count_arr = $this->where($where)->count();
        if(!$count_arr){
            return	array();
        }

        if($_GET['sort'] == 'last_open_time'){
            $sort = '`last_open_time` DESC';
        }else{
            $sort = '`device_id` DESC';
        }

        $aDoor	=	$this->field(true)->page($page,$page_count)->where($where)->order($sort)->select();
        foreach($aDoor as $k=>$v){
            if (isset($v['public_area_id']) && $v['public_area_id']>0) {
                $public_area_name = M('House_village_public_area')->where(array('public_area_id'=>$v['public_area_id']))->getField('public_area_name');
                $aDoor[$k]['floor_name']	=	$public_area_name;
                $aDoor[$k]['floor_layer']	=	C('config.house_name');
            } else if ( $v['floor_id'] == -1) {
                $aDoor[$k]['floor_name']	=	'大门';
                $aDoor[$k]['floor_layer']	=	C('config.house_name');
            }else{
                $floor_arr = explode(',',$v['floor_id']);
                if (count($floor_arr)==1) {
                    $v['floor_id'] = $floor_arr[0];
                    $aFloor	=	M('House_village_floor')->field(array('floor_name','floor_layer','single_id'))->where(array('floor_id'=>$v['floor_id']))->find();
                    $single_name	=	M('House_village_single')->where(array('id'=>$aFloor['single_id']))->getField('single_name');
                    $aDoor[$k]['floor_name']	=	$aFloor['floor_name'] ? $aFloor['floor_name'] : '';
                    $aDoor[$k]['floor_layer']	=	$single_name ? $single_name : $aFloor['floor_layer'];
                } else {
                    $floor_about_info = M('House_village_floor')
                        ->join(' as f LEFT JOIN '.C('DB_PREFIX').'house_village_single as s on s.id=f.single_id')
                        ->where(array('floor_id' => ['in', $floor_arr]))->field('f.floor_id, s.id as single_id, f.floor_name, s.single_name')->select();
                    $aDoor[$k]['floor_about_info'] = $floor_about_info;
                    $aDoor[$k]['floor_name']	=	'';
                    $aDoor[$k]['floor_layer']	=	'关联多单元';
                }
//                $aFloor	=	M('House_village_floor')->field(array('floor_name','floor_layer','single_id'))->where(array('floor_id'=>$v['floor_id']))->find();
//                $single_name	=	M('House_village_single')->where(array('id'=>$aFloor['single_id']))->getField('single_name');
//                $aDoor[$k]['floor_name']	=	$aFloor['floor_name'] ? $aFloor['floor_name'] : '';
//                $aDoor[$k]['floor_layer']	=	$single_name ? $single_name : $aFloor['floor_layer'];
            }
        }
        if($aDoor){
            import('@.ORG.merchant_page');
            $p = new Page($count_arr,$page_count,'page');
            $arr = array(
                'door_list'	=>	isset($aDoor)?$aDoor:array(),
                'pagebar'	=>	$p->show(),
            );
            return	$arr;
        }else{
            return	2;
        }
    }

    //	修改人脸识别门禁设备
    public function save_door($where,$data){
        if(!$where || !$data){
            return	false;
        }
        $addDoor = $this->where($where)->data($data)->save();
        return	$addDoor;
    }

    /**
     * 摄像头列表
     * @param $where
     * @param $page
     * @param $page_count
     * @return array|int
     */
    public function get_camera_door_list($where,$page,$page_count){
        if(!$where){
            return	array();
        }
        if(!isset($where['camera_status'])) {
            $where['camera_status'] = ['neq', 4];
        }
        $count_arr = M('House_camera_device')->where($where)->count();
        if(!$count_arr){
            return	array();
        }

        $sort = '`camera_id` ASC';

        $acamera	=	M('House_camera_device')->field(true)->page($page,$page_count)->where($where)->order($sort)->select();
        if($acamera){
            import('@.ORG.merchant_page');
            $p = new Page($count_arr,$page_count,'page');

            foreach($acamera as $k=>$v){
                if (isset($v['public_area_id']) && $v['public_area_id']>0) {
                    $public_area_name = M('House_village_public_area')->where(array('public_area_id'=>$v['public_area_id']))->getField('public_area_name');
                    $acamera[$k]['floor_name']	=	$public_area_name;
                    $acamera[$k]['floor_layer']	=	C('config.house_name');
                } else if ( $v['floor_id'] == -1) {
                    $acamera[$k]['floor_name']	=	'大门';
                    $acamera[$k]['floor_layer']	=	C('config.house_name');
                }else{
                    $aFloor	=	M('House_village_floor')->field(array('floor_name','floor_layer','single_id'))->where(array('floor_id'=>$v['floor_id']))->find();
                    $single_name	=	M('House_village_single')->where(array('single_id'=>$v['single_id']))->getField('single_name');
                    $acamera[$k]['floor_name']	=	$aFloor['floor_name'] ? $aFloor['floor_name'] : '';
                    $acamera[$k]['floor_layer']	=	$single_name ? $single_name : $aFloor['floor_layer'];
                }
            }


            $arr = array(
                'list'	=>	isset($acamera)?$acamera:array(),
                'pagebar'	=>	$p->show(),
            );
            return	$arr;
        }else{
            return	2;
        }
    }


    public function face_device_runtask($param){
        switch ($param['type']) {
            case 'addDeviceAuthGroup':
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $is_all = $param['is_all']?$param['is_all']:0;
                $orderGroupId = $param['orderGroupId']?$param['orderGroupId']:'';
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                $a5Service = D('Face_door_a5_service');
                fdump_api(['添加设备先关权限组'.__LINE__,$device_id,$village_id,$is_all],'a5New/planAddDeviceAuthGroupLog',1);
                $otherParam = [
                    'orderGroupId' => $orderGroupId
                ];
                usleep(200000);//休眠200毫秒
                $info = $a5Service->addDeviceAuthGroup($device_id, $village_id,[],$is_all,$otherParam);
                return 0;
            case 'authGroupBindDevice':
                $group_id = $param['group_id'];
                $device_id = $param['device_id'];
                $bind_id = $param['bind_id'];
                $bind_type = $param['bind_type'];
                $device_sn = $param['device_sn']?$param['device_sn']:'';
                $is_all = $param['is_all']?$param['is_all']:0;
                $orderGroupId = $param['orderGroupId']?$param['orderGroupId']:'';
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                $a5Service = D('Face_door_a5_service');
                fdump_api(['权限组绑定设备'.__LINE__,$param,$group_id, $device_id, $device_sn,$bind_id,$bind_type,$is_all],'a5New/planAuthGroupBindDeviceLog',1);
                $otherParam = [
                    'orderGroupId' => $orderGroupId
                ];
                usleep(200000);//休眠200毫秒
                $info = $a5Service->authGroupBindDevice($group_id, $device_id, $device_sn,$bind_id,$bind_type,$is_all,$otherParam);
                return 0;
            case 'addA5Card':
                $employeeNo = $param['employeeNo'];
                $ic_card = $param['ic_card'];
                $bind_type = $param['bind_type'];
                $device_type = $param['device_type']?$param['device_type']:5;
                $orderGroupId = $param['orderGroupId']?$param['orderGroupId']:'';
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                $a5Service = D('Face_door_a5_service');
                fdump_api(['权限组绑定设备'.__LINE__,$employeeNo,$ic_card,$bind_type,$orderGroupId],'a5New/planAuthGroupBindDeviceLog',1);
                $otherParam = [
                    'orderGroupId' => $orderGroupId
                ];
                usleep(200000);//休眠200毫秒
                $info = $a5Service->addCard($employeeNo,$ic_card,$bind_type,$device_type,$otherParam);
                return 0;
            case 'sync_a5_face_member': // A5和A6 人员云同步
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:0;
                $pigcms_id = $param['pigcms_id']?$param['pigcms_id']:0;
                $orderGroupId = $param['orderGroupId']?$param['orderGroupId']:'';
                $device_sn = $param['device_sn']?$param['device_sn']:'';
                $a5Service = D('Face_door_a5_service');
                if (!$village_id) {
                    fdump_api(['同步缺少参数'.__LINE__, $param],'a5New/errPlanSyncA5FaceMemberLog',1);
                } else {
                    fdump_api(['同步人员开始>>'.__LINE__, $param, microtime(true)],'a5New/planSyncA5FaceMemberLog',1);
                    set_time_limit(0);
                    ini_set('memory_limit', '2048M');
                    $otherParam = [
                        'orderGroupId' => $orderGroupId,
                        'device_sn' => $device_sn
                    ];
                    $info = $a5Service->addMember($village_id,$device_id, $pigcms_id, $floor_id,$otherParam);
                    fdump_api(['<<同步人员结束'.__LINE__, $village_id,$device_id, $pigcms_id, $floor_id,$info, microtime(true)],'a5New/planSyncA5FaceMemberLog',1);
                    if ($info) {
                        // 人脸门禁类型A5和A6 统一考虑
                        $planParam = array();
                        $planParam['type'] = 'sync_a5_face_floor';
                        $planParam['device_id'] = $device_id;
                        $planParam['village_id'] = $village_id;
                        if (strpos($floor_id,',') !== false) {
                            // 多floor_id  不传参
                            $floor_id = 0;
                        } elseif ($floor_id>0) {
                            $planParam['floor_id'] = $floor_id;
                        }
                        if ($pigcms_id>0) {
                            $planParam['pigcms_id'] = $pigcms_id;
                        }
                        if ($orderGroupId) {
                            $planParam['orderGroupId'] = $orderGroupId;
                        }
                        $planParam['unique_id'] = 'a5_face_floor_'.$village_id.'_'.$device_id.'_'.$floor_id.'_'.$pigcms_id;
                        fdump_api(['<<同步人员结束'.__LINE__, $param,$planParam, microtime(true)],'a5New/planSyncA5FaceMemberLog',1);
                        $a5PlanDeviceAuthGroup = D('House_face_device_log')->a5PlanDeviceAuthGroup;
                        usleep(200000);//休眠200毫秒
                        D('Face_door_service')->add_plan($planParam, 'sub_house_village_face_device',$a5PlanDeviceAuthGroup);
                    }
                }
                return 0;
            case 'sync_a5_face_floor': // A5和A6 权限组处理
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:0;
                $pigcms_id = $param['pigcms_id']?$param['pigcms_id']:0;
                $orderGroupId = $param['orderGroupId']?$param['orderGroupId']:'';
                $a5Service = D('Face_door_a5_service');
                if (!$village_id) {
                    fdump_api(['同步缺少参数'.__LINE__, $param],'a5New/errPlanSyncA5FaceFloorLog',1);
                } else {
                    set_time_limit(0);
                    ini_set('memory_limit', '2048M');

                    // 人脸门禁类型A5和A6 统一考虑
                    $planParam = array();
                    $planParam['type'] = 'sync_a5_face_group_member';
                    $planParam['device_id'] = $device_id;
                    $planParam['village_id'] = $village_id;
                    if ($floor_id>0) {
                        $planParam['floor_id'] = $floor_id;
                    }
                    $planTime = D('House_face_device_log')->a5SyncFaceGroupMember;
                    if ($pigcms_id>0) {
                        $planParam['pigcms_id'] = $pigcms_id;
                        $db_face_user_bind_device = D('Face_user_bind_device');
                        $where_repeat = [
                            'bind_type' => 0,
                            'bind_id' => $pigcms_id,
                            'device_type' => 5,
                            'group_id' => ['neq', '']
                        ];
                        $repeatId = $db_face_user_bind_device->where($where_repeat)->getField('id');
                        if ($repeatId) {
                            $planTime = D('House_face_device_log')->a5SyncFaceGroupMembers;
                        }
                    }
                    if ($orderGroupId) {
                        $planParam['orderGroupId'] = $orderGroupId;
                    }
                    $planParam['unique_id'] = 'a5_face_group_member_'.$village_id.'_'.$device_id.'_'.$pigcms_id;
                    fdump_api(['权限组下发'.__LINE__, $param, $planParam],'a5New/planSyncA5FaceFloorLog',1);
                    D('Face_door_service')->add_plan($planParam, 'sub_house_village_face_device',$planTime);

                    if($floor_id && strstr($floor_id, ',') != false){
                        // 如果设备单元涉及多个 直接将单元参数置0
                        $floor_id = 0;
                    }
                    $otherParam = [
                        'orderGroupId' => $orderGroupId,
                        'pigcms_id' => $pigcms_id,
                    ];
                    usleep(200000);//休眠200毫秒
                    $authGroup = $a5Service->getFloor($village_id,$device_id, $floor_id,$otherParam);
                    if ($authGroup) {
                        // 人脸门禁类型A5和A6 统一考虑
                        if (empty($planParam)) {
                            $planParam = array();
                            $planParam['type'] = 'sync_a5_face_group_member';
                            $planParam['device_id'] = $device_id;
                            $planParam['village_id'] = $village_id;
                            if ($floor_id>0) {
                                $planParam['floor_id'] = $floor_id;
                            }
                            if ($pigcms_id>0) {
                                $planParam['pigcms_id'] = $pigcms_id;
                            }
                            if ($orderGroupId) {
                                $planParam['orderGroupId'] = $orderGroupId;
                            }
                            $planParam['unique_id'] = 'a5_face_group_member_'.$village_id.'_'.$device_id.'_'.$pigcms_id;
                            fdump_api(['权限组下发'.__LINE__, $param, $planParam],'a5New/planSyncA5FaceFloorLog',1);
                        }
                        D('Face_door_service')->add_plan($planParam, 'sub_house_village_face_device',$planTime);
                    }
                }
                return 0;
            case 'sync_a5_face_group_member': // A5和A6 人员权限组绑定关系
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:0;
                $pigcms_id = $param['pigcms_id']?$param['pigcms_id']:0;
                $orderGroupId = $param['orderGroupId']?$param['orderGroupId']:'';
                $a5Service = D('Face_door_a5_service');
                if (!$village_id) {
                    fdump_api(['同步缺少参数'.__LINE__, $param],'a5New/errPlanSyncA5FaceGroupMemberLog',1);
                } else {
                    set_time_limit(0);
                    ini_set('memory_limit', '2048M');
                    // 存在单元权限组 走下发逻辑
                    // 人脸门禁类型A5和A6 统一考虑
                    $planParam = array();
                    $planParam['type'] = 'sync_a5_issued_by_group';
                    $planParam['device_id'] = $device_id;
                    $planParam['village_id'] = $village_id;
                    if ($floor_id>0) {
                        $planParam['floor_id'] = $floor_id;
                    }
                    if ($pigcms_id>0) {
                        $planParam['pigcms_id'] = $pigcms_id;
                    }
                    $planParam['unique_id'] = 'a5_face_issued_by_group_'.$village_id.'_'.$device_id.'_'.$pigcms_id;

                    $planTime = time() + 300;
                    D('Face_door_service')->add_plan($planParam, 'sub_house_village_face_device',$planTime);

                    $otherParam = [
                        'orderGroupId' => $orderGroupId
                    ];
                    usleep(200000);//休眠200毫秒
                    $bindMember = $a5Service->getBindAuthGroupMember($village_id, $floor_id, $pigcms_id,$device_id,$otherParam);
                    $judgeNewAuthGroup = $a5Service->judgeNewAuthGroup($device_id);

                    if ($bindMember && (!$judgeNewAuthGroup || $judgeNewAuthGroup['floorGroup'])) {
                        // 存在单元权限组 走下发逻辑
                        // 人脸门禁类型A5和A6 统一考虑
                        if (empty($planParam)) {
                            $planParam = array();
                            $planParam['type'] = 'sync_a5_issued_by_group';
                            $planParam['device_id'] = $device_id;
                            $planParam['village_id'] = $village_id;
                            if ($floor_id > 0) {
                                $planParam['floor_id'] = $floor_id;
                            }
                            if ($pigcms_id > 0) {
                                $planParam['pigcms_id'] = $pigcms_id;
                            }
                            $planParam['unique_id'] = 'a5_face_issued_by_group_' . $village_id . '_' . $device_id . '_' . $pigcms_id;

                            fdump_api(['权限组下发' . __LINE__, $param, $planParam], 'a5New/planSyncA5FaceFloorLog', 1);
                            D('Face_door_service')->add_plan($planParam, 'sub_house_village_face_device', -9);
                        }
                    }
                }
                return 0;
            case 'sync_a5_issued_by_group': // A5和A6 权限组信息下发
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:0;
                $pigcms_id = $param['pigcms_id']?$param['pigcms_id']:0;
                $a5Service = D('Face_door_a5_service');
                if (!$village_id) {
                    fdump('line:----'.__LINE__,'api/log/'.date('Ymd').'/face/sync_a5_issued_by_group_log',true);
                    fdump($param, 'api/log/' . date('Ymd') . '/face/sync_a5_issued_by_group_log', true);
                } else {
                    set_time_limit(0);
                    ini_set('memory_limit', '2048M');
                    //权限组下发设备 4
                    for($i=0;$i<6;$i++){
                        sleep(2);
                        $group = $a5Service->getAuthGroup($floor_id,$village_id);
                        if($group['code'] != 40021003)
                        {
                            fdump(['line:----'.__LINE__,'第'.$i.'次调用结束：',$group], 'api/log/' . date('Ymd') . '/face/sync_a5_issued_by_group_log', true);
                            break;
                        } else {
                            fdump(['line:----'.__LINE__,'第'.$i.'次调用继续：',$group], 'api/log/' . date('Ymd') . '/face/sync_a5_issued_by_group_log', true);
                        }
                    }
                }
                return 0;
            case 'a5_get_channel_msg': // 楼栋下所有单元进行同步处理
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:0;
                $a5_get_channel_msg = M('Process_sub_plan')->where(['unique_id' => 'a5_get_channel_msg'])->find();
                if (!$a5_get_channel_msg) {
                    // 15秒执行一次
                    $now_time = time();
                    $arr= array();
                    $param = array();
                    $param['type'] = 'a5_get_channel_msg';
                    $param['device_id'] = $device_id;
                    $param['village_id'] = $village_id;
                    if ($floor_id>0) {
                        $param['floor_id'] = $floor_id;
                    }
                    $param['time'] = $now_time;
                    $arr['param'] = serialize($param);
                    $arr['plan_time'] = time();
                    $arr['space_time'] = 15;
                    $arr['add_time'] = $now_time;
                    $arr['file'] = 'sub_house_village_face_device';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = 'a5_get_channel_msg';
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    M('Process_sub_plan')->data($arr)->add();
                }
                $a5Service = D('Face_door_a5_service');
                usleep(200000);//休眠200毫秒
                $a5Service->getChannelMsg();
                return 0;
            case 'add_a4_community': // 同步A4小区
                $village_id = $param['village_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addA4Community($village_id,[], $device_id, $is_plan);
                return 0;
            case 'add_a4_zone': // 同步分区（小区）到A4  暂时没有分区概念 使用小区信息
                $village_id = $param['village_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $communityId = $param['communityId']?$param['communityId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addA4Zone($village_id, $communityId, [], $device_id, $is_plan);
                return 0;
            case 'add_village_a4_single': // 小区下所有楼栋进行同步处理
                $village_id = $param['village_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $zoneId = $param['zoneId']?$param['zoneId']:0;
                $communityId = $param['communityId']?$param['communityId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addVillageA4Single($village_id, $zoneId, $communityId, $device_id, $is_plan);
                return 0;
            case 'add_a4_building': // 同步单个楼栋
                $single_id = $param['single_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $communityId = $param['communityId']?$param['communityId']:0;
                $zoneId = $param['zoneId']?$param['zoneId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addA4Building($single_id, $zoneId, $communityId, [], $device_id, $is_plan);
                return 0;
            case 'add_single_a4_floor': // 楼栋下所有单元进行同步处理
                $single_id = $param['single_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $buildingId = $param['buildingId']?$param['buildingId']:0;
                $communityId = $param['communityId']?$param['communityId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addSingleA4Floor($single_id, $buildingId, $communityId,$device_id, $device_id, $is_plan);
                return 0;
            case 'add_a4_unit': // 单个单元进行同步处理
                $floor_id = $param['floor_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $buildingId = $param['buildingId']?$param['buildingId']:0;
                $communityId = $param['communityId']?$param['communityId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addA4Unit($floor_id, $buildingId, $communityId,[], $device_id, $is_plan);
                return 0;
            case 'add_unit_a4_room': // 单元下所有房间进行同步处理
                $floor_id = $param['floor_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $unitId = $param['unitId']?$param['unitId']:0;
                $communityId = $param['communityId']?$param['communityId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addUnitA4Room($floor_id, $communityId, $unitId, [], $device_id, $is_plan);
                return 0;
            case 'add_a4_room': // 同步房屋
                $bind_id = $param['bind_id'];
                $communityId = $param['communityId']?$param['communityId']:0;
                $unitId = $param['unitId']?$param['$unitId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addA4Room($bind_id, $communityId, $unitId, [], 0, $is_plan);
                return 0;
            case 'add_a4_room_holds': // 房间下所有成员进行同步处理
                $vacancy_id = $param['vacancy_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $roomId = $param['roomId']?$param['roomId']:0;
                $communityId = $param['communityId']?$param['communityId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addA4RoomHolds($vacancy_id, $communityId, $roomId, $device_id, $is_plan);
                return 0;
            case 'add_a4_household': // 房间下所有成员进行同步处理
                $bind_id = $param['bind_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $roomId = $param['roomId']?$param['roomId']:0;
                $communityId = $param['communityId']?$param['communityId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addA4Household($bind_id, $roomId, $communityId, [], $device_id, $is_plan);
                return 0;
            case 'add_a4_member_household': // 同步单个成员
                $bind_id = $param['bind_id'];
                $device_id = $param['device_id'] ? $param['device_id'] : 0;
                $roomId = $param['roomId']?$param['roomId']:0;
                $communityId = $param['communityId']?$param['communityId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addA4MemberHousehold($bind_id, [], $roomId, $communityId, $device_id, $is_plan);
                return 0;
            case 'add_a4_device': // 同步设备
                $device_id = $param['device_id'];
                $zoneId = $param['zoneId']?$param['zoneId']:0;
                $unitId = $param['unitId']?$param['unitId']:0;
                D('Face_door_a4_service')->addA4Device($device_id,$zoneId, $unitId, []);
                return 0;
            case 'notify_a4_img_up': // 同步人脸
                $bind_id = $param['bind_id'];
                $communityId = $param['communityId']?$param['communityId']:0;
                $userId = $param['userId']?$param['userId']:0;
                D('Face_door_a4_service')->notifyA4ImgUp($bind_id, $userId, $communityId, []);
                return 0;
            case 'add_all_a4_property': // 同步工作人员
                $village_id = $param['village_id'];
                $communityId = $param['communityId']?$param['communityId']:0;
                $zoneId = $param['zoneId']?$param['zoneId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addAllA4Property($village_id, $zoneId, $communityId, $is_plan);
                return 0;
            case 'add_a4_property': // 同步工作人员
                $bind_id = $param['bind_id'];
                $communityId = $param['communityId']?$param['communityId']:0;
                $zoneId = $param['zoneId']?$param['zoneId']:0;
                $is_plan = isset($param['is_plan'])?$param['is_plan']:1;
                D('Face_door_a4_service')->addA4Property($bind_id, $zoneId, $communityId, [], $is_plan);
                return 0;
            case 'searchUnlockRecordCount':
                $village_id = $param['village_id'];
                $log_time_max = $param['log_time_max']?$param['log_time_max']:0;
                D('Face_door_a4_service')->getA4SearchUnlockRecordCount($village_id,0,$log_time_max,1);
                return 0;
            case 'sync_a185_face_all_per': //todo A185
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:'';
                $a185 = D('Face_a185_request_record');
                $a185->sync_a185_face_all_per($device_id, $village_id, $floor_id, []);
                return 0;
            case 'sync_a185_face_single_per': //todo A185
                $device_id = $param['device_id'];
                $pigcms_id = $param['pigcms_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $bind_info = $param['bind_info']?$param['bind_info']:[];
                $device_info = $param['device_info']?$param['device_info']:[];
                $a185 = D('Face_a185_request_record');
                $a185->a185_create_face_record($device_id, $pigcms_id, $village_id, $bind_info, [], $device_info);
                return 0;
            case 'sync_a7_face_all_per':
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:'';
                $a7Service = D('Face_door_a7_service');
                $a7Service->sync_a7_face_all_per($device_id, $village_id, $floor_id, []);
                return 0;
            case 'sync_d3_face_all_per': //todo d3
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:'';
                $d3 = D('Face_d3_request_record');
                $d3->sync_d3_face_all_per($device_id, $village_id, $floor_id, []);
                return 0;
            case 'sync_d3_face_single_per': //todo d3
                $device_id = $param['device_id'];
                $pigcms_id = $param['pigcms_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $bind_info = $param['bind_info']?$param['bind_info']:[];
                $device_info = $param['device_info']?$param['device_info']:[];
                $db_face_d3_request_record = D('Face_d3_request_record');
                $db_face_d3_request_record->add_create_face_record($device_id, $pigcms_id, $village_id, $bind_info, [], $device_info);
                return 0;
            case 'sync_d4_face_all_per': //todo D4 智能门禁

                fdump(['sync_d4_face_all_per',$param],'api/log/'.date('Ymd').'/d4/2_d4_jihua',true);
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:'';
                $d4 = D('Face_d4_request_record');
                $d4->sync_d4_face_all_per($device_id, $village_id, $floor_id, []);
                return 0;
            case 'sync_d4_face_single_per': //todo D4 智能门禁
                fdump(['sync_d4_face_all_per',$param],'api/log/'.date('Ymd').'/d4/3_d4_jihua',true);
                $device_id = $param['device_id'];
                $pigcms_id = $param['pigcms_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $bind_info = $param['bind_info']?$param['bind_info']:[];
                $device_info = $param['device_info']?$param['device_info']:[];
                $db_face_d4_request_record = D('Face_d4_request_record');
                $db_face_d4_request_record->add_create_face_record($device_id, $pigcms_id, $village_id, $bind_info, [], $device_info);
                return 0;
            case 'sync_a7_face_single_per':
                $device_id = $param['device_id'];
                $pigcms_id = $param['pigcms_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $bind_info = $param['bind_info']?$param['bind_info']:[];
                $device_info = $param['device_info']?$param['device_info']:[];
                $data = [];
                if(isset($param['log_id'])){
                    $data['log_id'] = $param['log_id'];
                }
                $db_face_a7_request_record = D('Face_a7_request_record');
                $db_face_a7_request_record->add_create_face_record($device_id, $pigcms_id, $village_id, $bind_info, $data, $device_info);
                return 0;
            case 'repair_bind_user_number':
                // 修复下相关编号数据
                $village_id = $param['village_id']?$param['village_id']:0;
                $db_face_door_service = D('Face_door_service');
                $db_face_door_service->repair_bind_user_number($village_id);
                return 0;

            case 'sync_d6_face_single_per':
                $device_id = $param['device_id'];
                $pigcms_id = $param['pigcms_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $bind_info = $param['bind_info']?$param['bind_info']:[];
                $device_info = $param['device_info']?$param['device_info']:[];
                $db_face_d6_request_record = D('Face_d6_request_record');
                $db_face_d6_request_record->add_create_face_record($device_id, $pigcms_id, $village_id, $bind_info, [], $device_info);
                return 0;
            case 'sync_d6_face_all_per':
                fdump(['sync_d6_face_all_per',$param],'api/log/'.date('Ymd').'/d6/2_d6_jihua',true);
                $device_id = $param['device_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $floor_id = $param['floor_id']?$param['floor_id']:'';
                $d4 = D('Face_d6_request_record');
                $d4->sync_d6_face_all_per($device_id, $village_id, $floor_id, []);
                return 0;
        }
    }


    public function a5_get_channel_msg() {

    }
    
    /**
     * 判断下是否显示指纹 需要有添加指纹设备最少一台
     * @param $village_id
     * @return bool
     */
    public function judgeFingerprintConfig($village_id) {
        if (!C('config.DHYunRuiCloudClientId') || !C('config.DHYunRuiCloudClientSecret')) {
            $judgeConfig = false;
        } else {
            $judgeConfig = true;
        }
        $device_da_hua_yun_rui_nb_fingerprint_judge = D('Config_customization')->getDhYunRuiNbJudge();
        if ($judgeConfig && $device_da_hua_yun_rui_nb_fingerprint_judge) {
            return true;
        } else {
            return false;
        }
    }
    
    public function getOpenNumberMaxLimit($village_id) {
        $village_vistor_open_number_max_limit = C('config.village_vistor_open_number_max_limit') ? C('config.village_vistor_open_number_max_limit') : 9;
        $getFaceCloud260Judge = D('Config_customization')->getFaceCloud260Judge();
        $getFaceXiHanDoorJudge = D('Config_customization')->getFaceXiHanDoorSwitch();
        if (intval($village_vistor_open_number_max_limit) < 1) {
            $village_vistor_open_number_max_limit = ($getFaceCloud260Judge || $getFaceXiHanDoorJudge) ? 24 : 9;
        }
        return intval($village_vistor_open_number_max_limit);
    }
    
    public function getQrCodeNumber($village_id) {
        $village_vistor_open_number_max_limit = $this->getOpenNumberMaxLimit($village_id);
        $visitor_open_number_limit = D('House_village_config')->where(array('village_id'=>$village_id))->getField('vistor_open_number_limit');
        $getFaceCloud260Judge = D('Config_customization')->getFaceCloud260Judge();
        $getFaceXiHanDoorJudge = D('Config_customization')->getFaceXiHanDoorSwitch();
        if (!$visitor_open_number_limit || $visitor_open_number_limit < 1) {
            $visitor_open_number_limit = ($getFaceCloud260Judge || $getFaceXiHanDoorJudge) ? 24 : 1;
        } elseif ($visitor_open_number_limit > $village_vistor_open_number_max_limit) {
            $visitor_open_number_limit = $village_vistor_open_number_max_limit;
        }
        return $visitor_open_number_limit;
    }
}