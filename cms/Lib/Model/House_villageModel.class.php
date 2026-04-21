<?php
class House_villageModel extends Model{
    protected $_validate = array(
        array('wx_image','require','图片必须存在！',1),
    );

    protected $_auto = array(
        array('last_time','time',3,'function'),
    );

    // 社区基本链接
    public $base_url = '';
    public $home_url = '';
    // 街道社区基本链接
    public $street_url = '';
	
	public function __construct(){
		if(C('config.system_type') == 'village'){
			$this->base_url = '/packapp/village/';
			$this->home_url = 'pages/village_menu/index';
		}else{
			$this->base_url = '/packapp/plat/';
			$this->home_url = 'pages/plat_menu/index';
		}
        $this->street_url = '/packapp/street/';
		parent::__construct();
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
        $config_data     = D('Config_data')->get_config_data();
        $wxappVersion    = $config_data['wxappLatestVersion'];
        $packappVersion  = $config_data['packappLatestVersion'];
        $deviceIdKey     = $deviceId.'LatestVersion';
        $deviceIdTimeKey = $deviceId.'LatestVersionTime';
        $deviceIdVersion = $config_data[$deviceIdKey];

        $deviceIdTimes   = $config_data[$deviceIdTimeKey];
        $newPage         = false;
        $isSave          = false;
        if (isset($_POST['wxapp_own_village_id']) && $_POST['wxapp_own_village_id'] && $deviceId == 'wxapp') {
            $wxapp_own_village_id = trim($_POST['wxapp_own_village_id']);
            $deviceIdKey          = 'OwnVillageWxappLatestVersion'.$wxapp_own_village_id;
            $deviceIdTimeKey      = 'OwnVillageWxappLatestVersionTime'.$wxapp_own_village_id;
            $deviceIdVersion      = $config_data[$deviceIdKey];
            if (($deviceIdVersion && $deviceIdVersion>=60200) || $appVersion>=60200) {
                $newPage = true;
                $isSave  = true;
            } elseif (!$deviceIdVersion) {
                $isSave  = true;
            }
        } elseif ($deviceId=='wxapp'&&$wxappVersion>=60200) {
            $newPage     = true;
            $isSave      = true;

        } elseif ($deviceId=='packapp'&&$packappVersion>=60200) {
            $newPage     = true;
            $isSave      = true;
        } elseif (in_array($deviceId,['wxapp', 'packapp']) && $appVersion && $appVersion>=60200) {
            $newPage     = true;
        } elseif (!in_array($deviceId,['wxapp', 'packapp']) && $packappVersion>=60200) {
            $newPage     = true;
        } elseif (!$deviceId && $packappVersion>=60200) {
            $newPage     = true;
        }
        $nowTime = time();
        if (!in_array($deviceId,['wxapp', 'packapp']) && $deviceIdVersion && $deviceIdTimes && $deviceIdTimes<=$nowTime+86400) {
            $isSave      = true;
        }
        if ($appVersion && ($appVersion>$deviceIdVersion || !$isSave)) {
            D('Config_data')->save_config_data($deviceIdKey, $appVersion);
            D('Config_data')->save_config_data($deviceIdTimeKey, $nowTime);
        }
        $isAll = false;
        if (isset($param['isAll'])&&$param['isAll']) {
            $isAll = true;
        }
        $urlParam = '';
        if (isset($param['urlParam'])&&$param['urlParam']) {
            $urlParam = trim($param['urlParam']);
            $urlParam = str_replace('?','',$urlParam);
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
            if ($newPage && 'villagelist' == $pageName && $deviceId && !in_array($deviceId, ['wxapp', 'packapp'])) {
                $pagePath = str_replace('pages/village_my/','pages/village/my/', $pagePath);
            }
        } elseif ($pagePath) {
            if ($newPage && $deviceId && !in_array($deviceId, ['wxapp', 'packapp']) && strstr($pagePath, 'pages/village/my/villagelist')) {
                // 考虑APP端匹配原生页面 不进行处理
            } elseif ($newPage) {
                // 如果是新页面 直接替换
                $pagePath = str_replace('pages/village/my/','pages/village_my/', $pagePath);
            } else {
                // 如果是老页面 直接替换 新页面
                $pagePath = str_replace('pages/village_my/','pages/village/my/', $pagePath);
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

    public $village_param_words = array(
        0=>array('key' => '{楼栋}', 'word' => 'single_name','name'=>'(号楼)'),
        1=>array('key' => '{单元}', 'word' => 'floor_name','name'=>'(单元)'),
        2=>array('key' => '{层数}', 'word' => 'layer','name'=>'(层)'),
        3=>array('key' => '{房间号}', 'word' => 'room','name'=>''),
    );
    public $defaulf_diy_txts = "{楼栋}{单元}{层数}{房间号}";

	public $test_room = '天字楼地号房（测试房间）';
	
	public function get_village_param_words($village_id=0)
    {
        $village_param_words = $this->village_param_words;
        foreach ($village_param_words as &$v){
            if(in_array($v['word'],['single_name','floor_name'])){
                $v['name'] = $this->getHouseAlias($v['word'],$village_id)?:$v['name'];
            }
        }
        return $village_param_words;
    }


	/**（暂废弃->2020/9/4）
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
		if (empty($data['single_name']) && empty($data['floor_name']) && empty($data['layer']) && empty($data['room'])) {
            if (C('config.is_demo_domain')) {
                $word = $this->test_room;
            } else {
                $word = '无';
            }
			return $word;
		}

//		$village_param_word = $this->village_param_word;
        $village_param_word = $this->get_village_param_words($village_id);
		if (empty($word) && $village_id) {
			$diy_village_name = $this->where(array('village_id'=> $village_id))->getField('diy_village_name');
			if ($diy_village_name) {
				$word = $diy_village_name;
			} else {
				$word = $this->defaulf_diy_txt;
			}
		} elseif (empty($word)) {
			$word = $this->defaulf_diy_txt;
		}
		$word = preg_replace('/{楼号}/', '{楼栋}', $word);
        $singleName = $this->getHouseAlias('single_name',$village_id);
        $floorName = $this->getHouseAlias('floor_name',$village_id);
		if ($word && $village_param_word) {
			foreach ($village_param_word as $val) {
				if ($val && $val['word']) {
					// 不存在的信息替换成空
					if (!$data[$val['word']]) {
						$data[$val['word']] = '';
					}
                    if($val['word']=='single_name' && is_numeric($data[$val['word']])){
                        $data[$val['word']]=$data[$val['word']].($singleName?:'号楼');
                    }
                    if($val['word']=='floor_name' && is_numeric($data[$val['word']])){
                        $data[$val['word']]=$data[$val['word']].($floorName ?: '单元');
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
     * Notes: 提供参数返回对应地址（新增）
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
     * @datetime: 2020/9/4 17:03
     */
    public function word_replce_msg($data=array(),$village_id=0,$word='') {
        if (empty($data['single_name']) && empty($data['floor_name']) && empty($data['layer']) && empty($data['room'])) {
            if (C('config.is_demo_domain')) {
                $word = $this->test_room;
            } else {
                $word = '无';
            }
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
            $village_param_word = $this->get_village_param_words($village_id);
            $word = $this->defaulf_diy_txts;
            $word = preg_replace('/{楼号}/', '{楼栋}', $word);
            if ($word && $village_param_word) {
                foreach ($village_param_word as $val) {
                    if ($val && $val['word']) {
                        if($val['word']=='single_name' && is_numeric($data[$val['word']])){
                            $data[$val['word']]=$data[$val['word']].'号楼';
                        }
                        if($val['word']=='floor_name' && is_numeric($data[$val['word']])){
                            $data[$val['word']]=$data[$val['word']].'单元';
                        }
                        if($val['word']=='layer' && is_numeric($data[$val['word']])){
                            $data[$val['word']]=$data[$val['word']].'层';
                        }
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
	public function get_single_floor_room($single_id = 0,$floor_id=0,$layer = '',$room_id = 0,$village_id = 0)
	{
		if($village_id == 0){
			return '';
		}
		if($single_id == 0){
			$single_name = '';
		}else{
			$single = M('House_village_single')->field('single_name')->where(array('id'=>$single_id))->find();
			$single_name = $single['single_name'];
		}

		if($floor_id == 0){
			$floor_name = '';
		}else{
			$floor = M('House_village_floor')->field('floor_name')->where(array('floor_id'=>$floor_id))->find();
			$floor_name = $floor['floor_name'];
		}
		if($layer == ''){
			$layer = '';
		}elseif (is_numeric($layer)){
			$layer_info = M('House_village_layer')->where(array('id'=>$layer))->find();
			$layer = $layer_info['layer_name'];
		}
        if($room_id == 0){
			$room = '';
		}else{
			$vacancy = M('House_village_user_vacancy')->field('room')->where(array('pigcms_id'=>$room_id))->find();
			$room = $vacancy['room'];
		}

		return $this->word_replce_msg(array('single_name'=>$single_name,'floor_name'=>$floor_name,'layer'=>$layer,'room'=>$room),$village_id);

	}


    /*得到用户关注的小区
	*is_family 是否查询家属租客
    */
	public function get_bind_list($uid,$phone = '',$flag = false,$long_lat=array(),$is_family = false, $property_id=0){
		$where = "`hv`.`status`='1' AND`hv`.`village_id`=`hvub`.`village_id` AND `hvub`.`status`='1'";
        $where .= " AND`hv`.`village_id`=3";
		if (!$is_family) {
			$where .= " AND `parent_id`=0";
		}
        $where .= " AND `type` IN (0,1,2,3,4)";
		
		if(!empty($uid)){
			$where .= " AND `hvub`.`uid`='$uid'";
		}else{
			if (empty($phone)) {
				$where .= " AND `hvub`.`uid`=-1";
			}else{
				$where .= " AND `hvub`.`phone`=$phone";
			}			
		}
		if ($property_id && $property_id>0) {
            $where .= " AND `hv`.`property_id`=$property_id";
        }

		if(!empty($phone)){
			D('House_village_user_bind')->bind($uid,$phone);
		}
		$village_list = D('')->field('`hv`.*,`hvub`.*')->table(array(C('DB_PREFIX').'house_village'=>'hv',C('DB_PREFIX').'house_village_user_bind'=>'hvub'))->where($where)->order('`hvub`.`pigcms_id` DESC')->group('`hv`.`village_id`')->select();

		//社区演示站添加默认数据
		if(empty($village_list) && $_SERVER['HTTP_HOST'] == 'shequ-demo.fastwhale.com.cn' && $flag == false){
			$now_user = D('User')->get_user($uid);
			$data_arr = array(
				'village_id'=>'1',
				'uid'=>$uid,
				'usernum'=>20010305+$uid,
				'name'=>'测试业主',
				'phone'=>$now_user['phone'],
				'housesize'=>'100',
				'park_flag'=>1,
				'address'=>'测试业主之家',
			);
			D('House_village_user_bind')->data($data_arr)->add();
			return $this->get_bind_list($uid,$phone,true);
		}
		//O2O演示站添加默认数据
		if(empty($village_list) && $_SERVER['HTTP_HOST'] == 'hf.pigcms.com' && $flag == false){
			$now_user = D('User')->get_user($uid);
			$data_arr = array(
				'village_id'=>'9',
				'uid'=>$uid,
				'usernum'=>20010305+$uid,
				'name'=>'测试业主',
				'phone'=>$now_user['phone'],
				'housesize'=>'100',
				'park_flag'=>1,
				'address'=>'测试业主之家',
			);
			D('House_village_user_bind')->data($data_arr)->add();
			return $this->get_bind_list($uid,$phone,true);
		}
		if($flag == true && $village_list && C('config.is_network_demo_domain')){
			$village_list[0]['first_test'] = true;
		}
		if($village_list){
			foreach($village_list as &$village_value){
				if($long_lat){
					$village_value['range'] = getRange(getDistance($village_value['lat'],$village_value['long'],$long_lat['lat'],$long_lat['long']));
				}
				$village_value['village_logo'] = $village_value['village_logo'] ? file_domain().$village_value['village_logo'] : C('config.site_url').'/static/images/wap_house/village_logo.png';
				$village_value['seal_img_new'] = !empty($village_value['seal_img_new']) ? file_domain().$village_value['seal_img_new'] : '';
			}
		}

		return $village_list;
	}

	//衩绑定家属小区列表
	public function get_bind_family_list($where,$long_lat=array(),$property_id=0){
	    if(!$where){
		    return false;
	    }
	    $field = array('`hv`.*','`hvub`.`pigcms_id`','`hvub`.`name`','`hvub`.`uid`');
	    $table = array(C('DB_PREFIX').'house_village'=>'hv',C('DB_PREFIX').'house_village_user_bind'=>'hvub');
	    $where['_string'] = '`hv`.`village_id` = `hvub`.`village_id`';
        if ($property_id && $property_id>0) {
            $where['hv.property_id']= $property_id;
        }
        $where['hv.status']= 1;
	    $list = D('')->field($field)->table($table)->where($where)->group('`hv`.`village_id`')->select();
	    foreach($list as $k=>$v){
			$list[$k]['flag'] = 1;
			$list[$k]['village_logo'] = $v['village_logo'] ? file_domain().$v['village_logo']: C('config.site_url').'/static/images/wap_house/village_logo.png';
			$list[$k]['seal_img_new'] = !empty($v['seal_img_new']) ? file_domain().$v['seal_img_new'] : '';
			if($long_lat){
				$list[$k]['range'] = getRange(getDistance($v['lat'],$v['long'],$long_lat['lat'],$long_lat['long']));
			}else{
				$list[$k]['range'] = '';
			}
	    }

	    return $list;
	}


	/*得到小区列表，支持经纬度*/
	public function wap_get_list($long_lat='',$keyword= '',$account = ''){
		$condition_village = array(
			'status'=>'1',
		);
		if ($long_lat['lat']&&$long_lat['long']) {
			$order = "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']} * PI() / 180- `lat` * PI()/180)/2),2)+COS({$long_lat['lat']} *PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']} *PI()/180- `long`*PI()/180)/2),2)))*1000) ASC";
		} else {
			// 没有经纬度显示当前城市的小区，否则显示所有小区
			$condition_village['city_id'] = C('config.now_city');
			$order = "`village_id` DESC";
		}
		import('@.ORG.wap_group_page');
		if(!empty($keyword)){
			$condition_village['village_name'] = array('like','%'.$keyword.'%');
		}
		if(!empty($account)){
			$condition_village['account'] = $account;
		}
		$count = $this->where($condition_village)->count('village_id');
		$p = new Page($count,10,'page');
		$village_list = $this->field(true)->where($condition_village)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		if($village_list){
			foreach($village_list as &$village_value){
				if($long_lat){
					$village_value['range'] = getRange(getDistance($village_value['lat'],$village_value['long'],$long_lat['lat'],$long_lat['long']));
				}
				$village_value['village_logo'] = $village_value['village_logo'] ? file_domain().$village_value['village_logo'] : C('config.site_url').'/static/images/wap_house/village_logo.png';
				$village_value['seal_img_new'] = !empty($village_value['seal_img_new']) ? file_domain().$village_value['seal_img_new'] : '';
			}
		}
		$return = array();
		if($village_list){
			$return['village_list'] = $village_list;
			$return['totalPage'] = ceil($count/10);
			$return['village_count'] = count($village_list);
			$return['pagebar'] =$p->show();
		}
		return $return;
	}


	/*得到小区列表，支持经纬度*/
	public function wap_get_list_new($long_lat='',$keyword= '',$account = '',$page = 1,$property_id=0){
		$condition_village = array(
			'a.status'=>'1',
		);
		if ($long_lat['lat']&&$long_lat['long']) {
			$order = "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']} * PI() / 180- `lat` * PI()/180)/2),2)+COS({$long_lat['lat']} *PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']} *PI()/180- `long`*PI()/180)/2),2)))*1000) ASC,b.village_sort DESC";
		} else {
			//新版隐藏城市，没有选择城市概念了
			// 没有经纬度显示当前城市的小区，否则显示所有小区
			// $condition_village['city_id'] = C('config.now_city');
			$order = "`b`.`village_sort` DESC,`a`.`village_id` DESC";
		}
		import('@.ORG.wap_group_page');
		if(!empty($keyword)){
			$condition_village['village_name'] = array('like','%'.$keyword.'%');
		}
      //  $condition_village['status'] = array('neq',5);
		if(!empty($account)){
			$condition_village['account'] = $account;
		}
		if ($property_id && $property_id>0) {
            $condition_village['property_id'] = $property_id;
        }

		$count = $this->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")->where($condition_village)->count('a.village_id');

//		$p = new Page($count,10,'page');
		$village_list = $this->field('a.*,b.village_sort')->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")->where($condition_village)->order($order)->page($page,10)->select();

		if($village_list){
			foreach($village_list as &$village_value){
				if($long_lat){
					$village_value['range'] = getRange(getDistance($village_value['lat'],$village_value['long'],$long_lat['lat'],$long_lat['long']));
				}else{
					$village_value['range'] = '';
				}
				$village_value['village_logo'] = $village_value['village_logo'] ? file_domain().$village_value['village_logo'] : C('config.site_url').'/static/images/wap_house/village_logo.png';
				$village_value['seal_img_new'] = !empty($village_value['seal_img_new']) ? file_domain().$village_value['seal_img_new'] : '';
			}
		}
//		$dataList = array();
//		if($village_list){
//			foreach($village_list as $k=>$village_value){
//				$list['village_id'] = $village_value['village_id'];
//				$list['village_name'] = $village_value['village_name'];
//				$list['village_address'] = $village_value['village_address'];
//				$list['village_id'] = $village_value['village_id'];
//				$list['village_id'] = $village_value['village_id'];
//				if($long_lat){
//					$list['range'] = getRange(getDistance($village_value['lat'],$village_value['long'],$long_lat['lat'],$long_lat['long']));
//				}else{
//					$list['range'] = '';
//				}
//				$list['village_logo'] = $village_value['village_logo'] ? file_domain().$village_value['village_logo'] : C('config.site_url').'/static/images/wap_house/village_logo.png';
//				$dataList[] = $list;
//			}
//		}
		$return = array();
		if($village_list){
			$return['village_list'] = $village_list;
			$return['totalPage'] = ceil($count/10);
			$return['village_count'] = count($village_list);
//			$return['pagebar'] =$p->show();
		}
		return $return;
	}
	public function get_one($village_id,$field ='*'){
		$info = $this->field($field)->where(array('village_id'=>$village_id,'status'=>'1'))->find();
		if(!$info){
			return [];
		}
		$m_application_bind = M('Application_bind');
		$where = array(
			'from' => 0,
			'use_id' => $village_id,
			'status' => 0,
		);
		$bind_arr = $m_application_bind->where($where)->getField('application_id',true);
		// 对应人脸识别门禁  默认开启
		if(isset($info['open_face_door'])) {
			// 对应人脸识别门禁  开启
			$info['open_face_door'] = 1;
		}
		// 对应客服im 开关以新版 应用绑定为准
		if(isset($info['has_kefu']) && in_array(1,$bind_arr)) {
			$info['has_kefu'] = 1;
		} elseif (isset($info['has_kefu'])) {
			$info['has_kefu'] = 0;
		}
		// 对应访客登记 开关以新版 应用绑定为准
		if(isset($info['has_visitor']) && in_array(28,$bind_arr)) {
			$info['has_visitor'] = 1;
		} elseif (isset($info['has_visitor'])) {
			$info['has_visitor'] = 0;
		}
		// 对应快递代收代送 开关以新版 应用绑定为准
		if (isset($info['has_express_service']) && in_array(14,$bind_arr)) {
			$info['has_express_service'] = 1;
		} elseif (isset($info['has_express_service'])) {
			$info['has_express_service'] = 0;
		}
		if (isset($info['has_express_send']) && in_array(14,$bind_arr)) {
			$info['has_express_send'] = 1;
		} elseif (isset($info['has_express_send'])) {
			$info['has_express_send'] = 0;
		}
		
		$info['village_logo'] = $info['village_logo'] ? file_domain().$info['village_logo'] : C('config.site_url').'/static/images/wap_house/village_logo.png';
		$info['seal_img_new'] = !empty($info['seal_img_new']) ? file_domain().$info['seal_img_new'] : '';
		
		return $info;
	}

	/*得到小区列表，支持经纬度*/
	public function wap_get_list_new_test($long_lat='',$keyword= '',$account = '',$page = 1){
		$condition_village = array(
			'status'=>'1',
		);
		if ($long_lat['lat']&&$long_lat['long']) {
			$order = "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']} * PI() / 180- `lat` * PI()/180)/2),2)+COS({$long_lat['lat']} *PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']} *PI()/180- `long`*PI()/180)/2),2)))*1000) ASC";
		} else {
			$order = "`village_id` DESC";
		}
		import('@.ORG.wap_group_page');
		if(!empty($keyword)){
			$condition_village['village_name'] = array('like','%'.$keyword.'%');
		}
		if(!empty($account)){
			$condition_village['account'] = $account;
		}
		$count = $this->where($condition_village)->count('village_id');
//		$p = new Page($count,10,'page');
		$village_list = $this->field(true)->where($condition_village)->order($order)->page($page,10)->select();

		if($village_list){
			foreach($village_list as &$village_value){
				if($long_lat){
					$village_value['range'] = getRange(getDistance($village_value['lat'],$village_value['long'],$long_lat['lat'],$long_lat['long']));
				}else{
					$village_value['range'] = '';
				}
				$village_value['village_logo'] = $village_value['village_logo'] ? file_domain().$village_value['village_logo'] : C('config.site_url').'/static/images/wap_house/village_logo.png';
				$village_value['seal_img_new'] = !empty($village_value['seal_img_new']) ? file_domain().$village_value['seal_img_new'] : '';
			}
		}
//		$dataList = array();
//		if($village_list){
//			foreach($village_list as $k=>$village_value){
//				$list['village_id'] = $village_value['village_id'];
//				$list['village_name'] = $village_value['village_name'];
//				$list['village_address'] = $village_value['village_address'];
//				$list['village_id'] = $village_value['village_id'];
//				$list['village_id'] = $village_value['village_id'];
//				if($long_lat){
//					$list['range'] = getRange(getDistance($village_value['lat'],$village_value['long'],$long_lat['lat'],$long_lat['long']));
//				}else{
//					$list['range'] = '';
//				}
//				$list['village_logo'] = $village_value['village_logo'] ? file_domain().$village_value['village_logo'] : C('config.site_url').'/static/images/wap_house/village_logo.png';
//				$dataList[] = $list;
//			}
//		}
		$return = array();
		if($village_list){
			$return['village_list'] = $village_list;
			$return['totalPage'] = ceil($count/10);
			$return['village_count'] = count($village_list);
//			$return['pagebar'] =$p->show();
		}
		return $return;
	}

	// 二维码
	public function get_qrcode($id){
		$where['village_id'] = $id;
		$now_village = $this->field('`village_id`,`qrcode_id`')->where($where)->find();
		if(empty($now_village)){
			return false;
		}
		return $now_village;
	}
	public function save_qrcode($id,$qrcode_id){
		$where['village_id'] = $id;
		$data['qrcode_id'] = $qrcode_id;
		if($this->where($where)->data($data)->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>'保存二维码至社区失败！请重试。'));
		}
	}

        public function house_village_edit($where){
            if(!$where){
                return false;
            }
            if(!$this->create()){
                return array('status'=>0,'msg'=>$this->getError());
            }else{
                if($this->where($where)->save()){
                    return array('status'=>1,'msg'=>'修改成功！');
                }else{
                    return array('status'=>0,'msg'=>'修改失败！');
                }
            }
        }
	
	//获取小区基本信息 - wangdong
	public function get_village_info($village_id,$field=''){
	
		$condition['village_id'] = $village_id;
		
		if(empty($field)) $field=true;
		
		$info = $this->field($field)->where($condition)->find();
		
		return $info;
		
	}

	public function get_village_list(){

		return M('House_village')->field('village_id,village_name')->where(array('status'=>1))->order('village_id DESC')->select();
	}


    /**
     * 鉴定图片内容是否安全
     * @param $img
     * @return bool
     */
    public function imgSecCheck($img) {
        if (!C('config.pay_wxapp_appid') || !C('config.pay_wxapp_appsecret')) {
            $check_ret = array();
            $check_ret['errcode'] = 0;
            $check_ret['message'] = '不需要鉴定';
            return $check_ret;
        } else {
            $access_token_array = D('Access_token_wxapp_expires')->get_access_token();
            if ($access_token_array['errcode'] == 0) {
                $access_token = $access_token_array['access_token'];
                $url = 'https://api.weixin.qq.com/wxa/img_sec_check?access_token=' . $access_token;
                if (class_exists('\CURLFile')) {
                    $data['media'] = new \CURLFile(realpath($img));
                } else {
                    $data['media'] = '@'.realpath($img);
                }
                import('ORG.Net.Http');
                $http = new Http();
                $check_ret = Http::curlPost($url, $data);
                if ($check_ret['errcode'] <= -1) {
                    $check_ret['errcode'] = 0;
                }
                return $check_ret;
            } else {
                fdump('检测错误返回>'.__LINE__,'api/log/'.date('Ymd').'/msgSecCheck_err_log',true);
                fdump($access_token_array,'api/log/'.date('Ymd').'/msgSecCheck_err_log',true);
                $check_ret['errcode'] = 0;
                $check_ret['message'] = '鉴定接口返回错误';
                return $check_ret;
            }
        }
    }

    /**
     * 鉴定文字内容是否安全
     * @param $msg
     * @return bool
     */
    public function msgSecCheck($msg, $is_repeat=false) {
        // 如果不存在小程序配置，不执行鉴定
        if (!C('config.pay_wxapp_appid') || !C('config.pay_wxapp_appsecret')) {
            $check_ret = array();
            $check_ret['errcode'] = 0;
            $check_ret['message'] = '不需要鉴定';
            return $check_ret;
        } else {
            $access_token_array = D('Access_token_wxapp_expires')->get_access_token();
            if ($access_token_array['errcode'] == 0) {
                $access_token = $access_token_array['access_token'];
                $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=' . $access_token;
                $data['content'] = $msg;
                import('ORG.Net.Http');
                $http = new Http();
                $check_ret = Http::curlPost($url, json_encode($data, JSON_UNESCAPED_UNICODE));
                if ($check_ret['errcode']==40001 && !$is_repeat) {
                    $this->msgSecCheck($msg, true);
                }
                if ($check_ret['errcode'] <= -1 || $check_ret['errcode'] == 40001) {
                    $check_ret['errcode'] = 0;
                }
                return $check_ret;
            } else {
                fdump('检测错误返回>'.__LINE__,'api/log/'.date('Ymd').'/msgSecCheck_err_log',true);
                fdump($access_token_array,'api/log/'.date('Ymd').'/msgSecCheck_err_log',true);
                $check_ret['errcode'] = 0;
                $check_ret['message'] = '鉴定接口返回错误';
                return $check_ret;
            }
        }
    }

	/**
	 * 获取社区是否存在门禁或者开启门禁
	 * @param $village_id
	 * @return bool
	 */
	public function village_door_status($village_id) {
		$door_count = M('House_village_door')->where(array('village_id' => $village_id,'door_status' => 1))->count();
		if ($door_count>0) {
			return true;
		} else {
			$face_count = M('House_face_device')->where(array('village_id' => $village_id,'device_status' => 1))->count();
			if ($face_count>0) {
				return true;
			} else {
				return false;
			}
		}
	}

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
            $country = M('Country')->order('sort desc')->select();
			foreach($country as $k=>$v){
				$arr[] = $v['name_zh'];
			}
		}elseif($key == 'marriage_status'){
			$arr = array('未婚','已婚','离婚','丧偶');//未婚、已婚、离婚、丧偶
		}elseif($key == 'edcation'){
			$arr = array('无','初中及以下','高中或中专','大专','本科','硕士','博士','其他');
		}elseif($key == 'unit_nature'){
			$arr = array('国有企业','民营企业','外商独资','中外合资','港澳台企业','政府机关','事业单位','非营利性组织','自主创业','其他');
		}

		return $arr;
	}

    /**
     * Notes: 初始化数据信息
     * @param int $village_id
     * @author: wanzy
     * @date_time: 2020/10/9 13:09
     */
    public function update_user_info($village_id = 0,$property_id=0)
    {
        set_time_limit(0);
        $where = [];
        if ($village_id) {
            $where_village = [];
            $where_village['village_id'] = $village_id;
            $property_id = D('House_village')->where($where_village)->getField('property_id');
            $where['property_id'] = $property_id;
        } elseif ($property_id) {
            $where['property_id'] = $property_id;
        }
        $db_house_village_data_config = M('House_village_data_config');
        $where_repeat = [
            'property_id' => ['gt', 0],
        ];
        $property_arr = $db_house_village_data_config->where($where_repeat)->group('property_id')->getField('property_id',true);
        if ($property_arr) {
            $where['property_id'] = ['not in', $property_arr];
        }
        
        $dataList = D('House_property')->where($where)->select();
        foreach($dataList as $k=>$v){
            if($v['id']){
                $village_config = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id']))->getField('key',true);
                if (empty($village_config)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('性别', 'sex', '2', '0', '99','0', ".$v['id'].", '', '1', '1', '0', '1', NULL)";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('出生日期', 'birthday', '4', '0', '98','0', ".$v['id'].", NULL, '1', '1', '0', '1', NULL)";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('民族', 'nation', '2', '0', '97','0', ".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('籍贯', 'native_place', '3', '0', '96', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('国籍', 'nationality', '2', '0', '95', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('户口所在地', 'household_register', '1', '0', '94', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('婚姻状况', 'marriage_status', '2', '0', '93', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('教育水平', 'edcation', '2', '0', '92', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('紧急联系人', 'emergent_person', '1', '0', '91', '0',".$v['id'].", '', '1', '0', '1', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('联系人手机号', 'person_phone', '1', '0', '90', '0',".$v['id'].", '', '1', '0', '1', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('工作单位', 'job', '1', '0', '89', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('单位性质', 'unit_nature', '2', '0', '88', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('工作单位地址', 'job_address', '1', '0', '87', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }else{
                    if(!in_array('sex',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('性别', 'sex', '2', '0', '99','0', ".$v['id'].", '', '1', '1', '0', '1', NULL)";
                        M()->query($sql);
                    }
                    if(!in_array('birthday',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('出生日期', 'birthday', '4', '0', '98','0', ".$v['id'].", NULL, '1', '1', '0', '1', NULL)";
                        M()->query($sql);
                    }

                    if(!in_array('nation',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('民族', 'nation', '2', '0', '97','0', ".$v['id'].", '', '1', '0', '0', '1', '')";
                        M()->query($sql);
                    }

                    if(!in_array('native_place',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('籍贯', 'native_place', '3', '0', '96', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                        M()->query($sql);
                    }

                    if(!in_array('nationality',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('国籍', 'nationality', '2', '0', '95', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                        M()->query($sql);
                    }

                    if(!in_array('household_register',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('户口所在地', 'household_register', '1', '0', '94', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                        M()->query($sql);
                    }
                    if(!in_array('marriage_status',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('婚姻状况', 'marriage_status', '2', '0', '93', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                        M()->query($sql);
                    }
                    if(!in_array('edcation',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('教育水平', 'edcation', '2', '0', '92', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                        M()->query($sql);
                    }

                    if(!in_array('emergent_person',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES (' 紧急联系人', 'emergent_person', '1', '0', '91', '0',".$v['id'].", '', '1', '0', '1', '1', '')";
                        M()->query($sql);
                    }

                    if(!in_array('person_phone',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('联系人手机号', 'person_phone', '1', '0', '90', '0',".$v['id'].", '', '1', '0', '1', '1', '')";
                        M()->query($sql);
                    }

                    if(!in_array('job',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('工作单位', 'job', '1', '0', '89', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                        M()->query($sql);
                    }

                    if(!in_array('unit_nature',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('单位性质', 'unit_nature', '2', '0', '88', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                        M()->query($sql);
                    }

                    if(!in_array('job_address',$village_config)){
                        $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('工作单位地址', 'job_address', '1', '0', '87', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                        M()->query($sql);
                    }
                }
                
               /* $village_config_sex = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'sex'))->find();
                if(empty($village_config_sex)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('性别', 'sex', '2', '0', '99','0', ".$v['id'].", '', '1', '1', '0', '1', NULL)";
                    M()->query($sql);
                }
                $village_config_birthday = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'birthday'))->find();
                if(empty($village_config_birthday)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('出生日期', 'birthday', '4', '0', '98','0', ".$v['id'].", NULL, '1', '1', '0', '1', NULL)";
                    M()->query($sql);
                }

                $village_config_nation = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'nation'))->find();
                if(empty($village_config_nation)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('民族', 'nation', '2', '0', '97','0', ".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }

                $village_config_native_place = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'native_place'))->find();
                if(empty($village_config_native_place)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('籍贯', 'native_place', '3', '0', '96', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }

                $village_config_nationality = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'nationality'))->find();
                if(empty($village_config_nationality)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('国籍', 'nationality', '2', '0', '95', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }

                $village_config_household_register = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'household_register'))->find();
                if(empty($village_config_household_register)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('户口所在地', 'household_register', '1', '0', '94', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }
                $village_config_marriage_status = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'marriage_status'))->find();
                if(empty($village_config_marriage_status)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('婚姻状况', 'marriage_status', '2', '0', '93', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }
                $village_config_edcation = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'edcation'))->find();
                if(empty($village_config_edcation)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('教育水平', 'edcation', '2', '0', '92', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }

                $village_config_emergent_person = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'emergent_person'))->find();
                if(empty($village_config_emergent_person)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES (' 紧急联系人', 'emergent_person', '1', '0', '91', '0',".$v['id'].", '', '1', '0', '1', '1', '')";
                    M()->query($sql);
                }

                $village_config_person_phone = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'person_phone'))->find();
                if(empty($village_config_person_phone)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('联系人手机号', 'person_phone', '1', '0', '90', '0',".$v['id'].", '', '1', '0', '1', '1', '')";
                    M()->query($sql);
                }

                $village_config_job = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'job'))->find();
                if(empty($village_config_job)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('工作单位', 'job', '1', '0', '89', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }

                $village_config_unit_nature = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'unit_nature'))->find();
                if(empty($village_config_unit_nature)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('单位性质', 'unit_nature', '2', '0', '88', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }

                $village_config_job_address = M('House_village_data_config')->where(array('village_id'=>0,'property_id' => $v['id'],'key'=>'job_address'))->find();
                if(empty($village_config_job_address)){
                    $sql="INSERT INTO `pigcms_house_village_data_config` (`title`, `key`, `type`, `add_time`, `sort`, `village_id`,`property_id`, `use_field`, `is_display`, `is_must`, `is_close`, `is_system`, `system_value`) VALUES ('工作单位地址', 'job_address', '1', '0', '87', '0',".$v['id'].", '', '1', '0', '0', '1', '')";
                    M()->query($sql);
                }*/
            }
        }
    }

    //扣除积分
    public function deduction_points($village_id)
    {
        $day = (int)date('d',time());
        $house_village = D('House_village')->where(['village_id'=>$village_id])->field('points_deduction_day_set,points_deduction_time_set,sms_notice_set,village_name,money')->find();

        if(!$house_village['points_deduction_day_set'] || $house_village['points_deduction_day_set'] == ''){
            return true;
        }
        if($house_village && $house_village['points_deduction_day_set'] != $day){
            return true;
        }
        $user_score_use_percent = C('config.user_score_use_percent');//1RMB所需积分
        $score_round_two = C('config.score_round_two');//积分获得保留2位
        //是否开启短信
        $sms_notice_set = $house_village['sms_notice_set'];

        $ids = [];
        $condition = '( ((property_endtime <> "" OR property_endtime <> null) AND property_endtime<"' . time() . '")) AND parent_id=0 AND type in (0,3) AND village_id=' . $village_id;
        $list = D('House_village_user_bind')->field('pigcms_id')->where($condition)->select();
        if($list) {
            foreach ($list as $key => $value) {
                $ids[] = $value['pigcms_id'];
            }
        }

        $condition = '`psb`.`pigcms_id` = `b`.`pigcms_id`  AND `psb`.`standard_id` = `ps`.`standard_id` AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND `psb`.`start_time` <"' . time() . '" AND psb.village_id =' . $village_id;
        $condition_table = array(C('DB_PREFIX') . 'house_village_payment_standard_bind' => 'psb', C('DB_PREFIX') . 'house_village_user_bind' => 'b', C('DB_PREFIX') . 'house_village_payment_standard' => 'ps');
        $field = '`psb`.*,`ps`.`cycle_type`,`ps`.`pay_cycle`,`b`.`pigcms_id`';

        $list_custom = D('')->field($field)->table($condition_table)->where($condition)->select();

        $list_custom = $list_custom ? $list_custom : [];
        $custom_ids = [];
        if($list_custom){
            foreach ($list_custom as $key => $value) {
                switch ($value['cycle_type']) {
                    case 'Y': // 年
                        // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                        $end_time = $value['start_time'] + $value['pay_cycle'] * $value['paid_cycle'] * 86400 * 365;
                        break;
                    case 'M': //月
                        $end_time = $value['start_time'] + $value['pay_cycle'] * $value['paid_cycle'] * 86400 * 30;
                        break;
                    case 'D': // 日
                        $end_time = $value['start_time'] + $value['pay_cycle'] * $value['paid_cycle'] * 86400;
                        break;
                }
                if ($end_time < time()) {
                    $custom_ids[] = $value['pigcms_id'];
                }
            }
        }
        //欠费业主id集
        $ids = array_merge($ids, $custom_ids);
        $list = [];
        $condition = '`b`.`status`=1 AND `v`.`status`=1 AND f.floor_id=b.floor_id AND b.pigcms_id in('.implode(',',$ids).') AND b.village_id=v.village_id AND b.uid=u.uid AND b.village_id ='.$village_id;

        $condition_table  = array(C('DB_PREFIX').'house_village_floor'=>'f',C('DB_PREFIX').'house_village_user_bind'=>'b',C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'house_village'=>'v');
        //$field = 'f.single_id as floor_single_id,b.single_id,b.layer_id,b.room_addrss,b.layer_num,b.housesize,v.village_name,b.village_id,b.name,b.property_endtime,b.pigcms_id,b.water_price,b.electric_price,b.gas_price,b.park_price,f.floor_name,f.floor_layer,f.property_fee,v.property_name,v.send_user_type,v.send_date,v.property_price,u.openid,u.uid';
        $field = 'b.pigcms_id,b.property_price,b.village_id,b.name,b.property_endtime,b.housesize,b.pigcms_id,u.openid,u.uid,b.phone,u.score_count,f.property_fee,f.floor_name,b.single_id,b.floor_id,b.layer_id,b.vacancy_id';
        $order = ' `b`.`pigcms_id` DESC';

        $list = D('')->field($field)->table($condition_table)->where($condition)->order($order)->select();

//        $condition = 'b.pigcms_id in(' . implode(',', $ids) . ') AND b.village_id=' . $village_id;
//        $field = 'b.pigcms_id,b.property_price,u.openid,u.uid,b.phone,u.score_count';
//        $order = ' `b`.`pigcms_id` DESC';
//        $left_join = 'as b left join ' . C('DB_PREFIX') . 'user as u on u.uid=b.uid';
//        $list = D('House_village_user_bind')->field($field)->join($left_join)->where($condition)->order($order)->select();
        if ($list) {
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'payMentlist',
                'isAll' => true,
                'urlParam' => '',
            ];
            $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            foreach ($list as $key=>&$v) {
//                if($key !=1){
//                    continue;
//                }
                $property_price = 0;
                $nums=0;
                if ($v['property_endtime'] && $v['property_endtime'] < strtotime(date("Y-m-d"))) {
                    $nums = D('House_village_user_bind')->getTimeNum($v['property_endtime'],strtotime(date("Y-m-d")),'M');
//                    if (floatval($v['property_fee'])>0) {
//                        $property_price = floatval($v['property_fee']) * floatval($v['housesize']) * floatval($nums);
//                    } else {
//                        $property_price = floatval($v['property_price']) * floatval($v['housesize']) * floatval($nums);
//                    }
                    // 计算欠缴物业费
                    $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$v['vacancy_id'],'is_del'=>1])->field('standard_id')->find();
                    if(!$standard_id['standard_id']){
                        $property_price = 0;
                    }elseif ($v['property_endtime'] && $v['property_endtime'] < time()){
                        $dateMonth = array();
                        $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                        $num = D('House_village_user_bind')->getMonthNum($v['property_endtime'] + 1, time());
                        if(date('d',time()) > date('d',$v['property_endtime'] + 1))
                            $num+=1;
                        $start_time = strtotime(date('Y-m',$v['property_endtime']));
                        $dateMonth[] = $start_time;
                        for ($i=1;$i<$num;$i++){
                            $dateMonth[] = strtotime("+ $i month",$start_time);
                        }
                        $property_price = D('House_village_user_bind')->property_price($dateMonth,unserialize($date['date_month']),$v['housesize']);
                    }else{
                        $property_price = 0;
                    }
                    if ($property_price > 0) {
                        $property_msg = array(
                            'money' => $property_price,
                            'property_month_num' => $nums,
                            'uid' => $v['uid'],
                            'bind_id' => $v['pigcms_id'],
                            'village_id' => $v['village_id'],
                            'pay_type' => 'property',
                            'payment_id' => '-2'
                        );
                        $special_arr[] = $property_msg;
                        $v['property_price'] = $property_price;
                    }
                }
                $single_name = D('House_village_single')->where(['id'=>$v['single_id']])->getField('single_name');
                $layer_name = D('House_village_layer')->where(['id'=>$v['layer_id']])->getField('layer_name');
                $room = D('House_village_user_vacancy')->where(['pigcms_id'=>$v['vacancy_id']])->getField('room');
                $addre = $single_name.'-'.$v['floor_name'].'-'.$layer_name.$room;
                $address = $this->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$village_id);

                $openid = $v['openid'];
                $score_count = $v['score_count'];//业主积分
                $uid = $v['uid'];
                if(!$v['uid'] && !$openid){
                    $user_info = D('User')->where(['phone'=>$v['phone']])->field('uid,openid,score_count')->find();
                    $openid = $user_info['openid'];
                    $score_count = $user_info['score_count'];//业主积分
                    $uid = $user_info['uid'];
                }
//                $score_money = round($score_count/$user_score_use_percent,2);//积分现金比率换算成money
                $score_money = $score_count/$user_score_use_percent;//积分现金比率换算成money

                $v['$score_money'] = $score_money;
                if($score_money){

                    $residue_score_money = $score_money;//初始化剩余的money
                    $data_arr['uid'] = $uid;
                    $data_arr['pigcms_id'] = $v['pigcms_id'];
                    $data_arr['village_id'] = $v['village_id'];
                    //1.物业费
//                    if($v['property_price'] && $v['property_price']>0){
                    if($property_price>0){
                        $old_date = date('Y-m-d',$v['property_endtime']);
                        $old_y = date('Y',$v['property_endtime']);
                        $old_m = date('m',$v['property_endtime']);
                        $day = date("t",strtotime("$old_y-$old_m"));
                        if($nums>1){
                            for ($i=1;$i<$nums;$i++){
                                $d_m = date('m',strtotime("$old_date +$i month"));
                                $d_y = date('Y',strtotime("$old_date +$i month"));
                                $day += date("t",strtotime("$d_y-$d_m"));
                            }
                        }

                        $one_day_price = $property_price/$day;//每天所欠物业费

                        $score_day = floor($score_money/$one_day_price);//计算积分钱可抵消天数 取整
//                        $to_deduction_score_money = $one_day_price*$score_day;//要抵扣的积分钱
//                        $to_deduction_score= round($to_deduction_score_money*$user_score_use_percent);//要抵扣的积分
//                        $residue_score_money = round($score_money-($one_day_price*$score_day),2);//剩余积分钱
//                        $residue_score = floor($user_score_use_percent*$residue_score_money);//剩余积分
                        //如果当前积分钱可抵消天数 大于0
                        if($score_day>0)
                        {
                            //物业天数大于积分可抵扣的天数
                            if($day>$score_day)
                            {

                                if($score_round_two)
                                {
                                    $to_deduction_score_money = round($one_day_price*$score_day,2);//要抵扣的积分钱
                                    $residue_owe = round($property_price-$to_deduction_score_money,2);//剩余欠费
                                    $to_deduction_score= round($to_deduction_score_money*$user_score_use_percent,2);//要抵扣的积分
                                    $residue_score_money = round($score_money-($one_day_price*$score_day),2);//剩余积分钱
                                    $residue_score = round($user_score_use_percent*$residue_score_money,2);//剩余积分
                                }else{
                                    $to_deduction_score_money = round($one_day_price*$score_day);//要抵扣的积分钱
                                    $residue_owe = round($property_price-$to_deduction_score_money);//剩余欠费
                                    $to_deduction_score= round($to_deduction_score_money*$user_score_use_percent);//要抵扣的积分
                                    $residue_score_money = round($score_money-($one_day_price*$score_day));//剩余积分钱
                                    $residue_score = round($user_score_use_percent*$residue_score_money);//剩余积分
                                }
                                //物业费就是抵扣积分 得出物业到期时间
                                $to_residue_time = date('Y-m-d',strtotime("$old_date +$score_day day"));
                                $score_day = 0;//积分钱可抵消天数为0
                            }else{//物业天数小于积分可抵扣的天数

                                if($score_round_two) {
                                    $to_deduction_score_money = round($one_day_price*$day,2);//要抵扣的积分钱
                                    $to_deduction_score = round($to_deduction_score_money * $user_score_use_percent,2);//要抵扣的积分
                                    $residue_score_money = round($score_money - ($one_day_price * $day), 2);//剩余积分钱
                                    $residue_score = round($user_score_use_percent * $residue_score_money,2);//剩余积分
                                }else{
                                    $to_deduction_score_money = round($one_day_price*$day);//要抵扣的积分钱
                                    $to_deduction_score = round($to_deduction_score_money * $user_score_use_percent);//要抵扣的积分
                                    $residue_score_money = round($score_money - ($one_day_price * $day));//剩余积分钱
                                    $residue_score = round($user_score_use_percent * $residue_score_money);//剩余积分
                                }
                                $to_residue_time = date('Y-m-d',strtotime("$old_date +$day day"));
                                $score_day = $score_day-$day;//剩余 积分钱可抵消天数
                                $residue_owe = 0;//剩余欠费
                            }
                            //生成物业账单
                         /*   $data_arr['title'] = '物业费';
                            $data_arr['money'] = $to_deduction_score_money;
                            $data_arr['score_can_use'] = $residue_score;
                            $data_arr['score_can_pay'] = $to_deduction_score;
                            $data_arr['score_used_count'] = $to_deduction_score;
                            $data_arr['score_deducte'] = $to_deduction_score_money;
                            $data_arr['order_type'] = 'property';
                            $order_id = $this->recordPropertyBill($data_arr);*/
                            //记录小区余额
                          /*  $data_arr['order_id'] = $order_id;
                            $this->recordVillageMoney($data_arr);*/
                            //生成消费积分记录
                          /*  $desc = '缴费物业费抵扣积分';
                            $score_arr = [
                                'uid'=>$uid,
                                'score'=>$to_deduction_score,
                                'used_count'=>$residue_score,
                                'desc'=>$desc,
                            ];
                            $this->user_action_log($score_arr);*/
                            //D('User_score_list')->add_row($uid,2,0,$desc,1,0,0,[]);//暂不用
//                            //修改物业到期时间
                           // D('House_village_user_bind')->where(['pigcms_id'=>$v['pigcms_id']])->data(['property_endtime'=>strtotime($to_residue_time)])->save();
//                            //修改用户积分
                           // D('User')->where(['uid'=>$v['uid']])->data(['score_count'=>$residue_score])->save();

                            if($openid){//尊敬的业主，您的代缴总额剩余：xxx元，您现有xxx积分可自主抵扣xxx元物业费。
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $keyword = '尊敬的业主，您的代缴总额剩余：'.$property_price.'元，您现有'.$to_deduction_score.'积分可自主抵扣'.$to_deduction_score_money.'元物业费';
                                $remark='点击缴费！';
                               // $keyword = '尊敬的业主，系统使用您'.$to_deduction_score.'积分已抵扣物业费'.$to_deduction_score_money.'元';
                                /*if($residue_owe){
                                    $remark = '您的待缴总额剩余:'.$residue_owe.'元，点击缴费！';
                                }else{
                                    $remark = '';
                                }*/

                                $href = $payMentlistUrl.'?village_id='.$v['village_id'].'&pigcms_id='.$v['pigcms_id'];

                                $model->sendTempMsg('TM01008',
                                    array(
                                        'href' => $href,
                                        'wecha_id' => $openid,
                                        'first' => $keyword,
                                        'keynote1' => $house_village['village_name'],
                                        'keynote2' => $address,
                                        'remark' => $remark
                                    )
                                );
                            }
                            //短信推送
                            if($sms_notice_set){
                                if($v['phone']){
                                    $sms_data['uid'] = $v['uid'];
                                    $sms_data['mobile'] = $v['phone'];
                                    $sms_data['village_id'] = $v['village_id'];//propertyfee
                                    $sms_data['type'] = 'propertyfee';//
                                    $sms_data['sendto'] = 'user';//尊敬的（1-1-1101）户业主，您的代缴总额剩余：【100元】，您现有【8.85】积分可自主抵扣【8.85】元物业费。
                                    $sms_data['content'] = '尊敬的'.$addre.'户业主，您的代缴总额剩余：'.$property_price.'，您现有'.$to_deduction_score.'积分可自主抵扣'.$to_deduction_score_money.'元物业费';
                                   // $sms_data['content'] = '尊敬的'.$addre.'户业主，'.$house_village['village_name'].'提醒您，系统使用您'.$to_deduction_score.'积分已抵扣物业费'.$to_deduction_score_money.'元';
                                    /*if($residue_owe){
                                        $remark = '，剩余'.$residue_owe.'元请及时缴费';
                                        $sms_data['content'] = $sms_data['content'].$remark;
                                    }*/
                                    fdump($sms_data,'api/sendTempMsg',true);
                                    Sms::sendSms($sms_data);
                                }
                            }
                        }

                    }
                    //2.自定义物业费
                    if($residue_score_money>0) {
                        //自定义项欠费
                        $payment_list = D('')->table(array(C('DB_PREFIX') . 'house_village_payment_standard_bind' => 'psb',
                            C('DB_PREFIX') . 'house_village_payment_standard' => 'ps',
                            C('DB_PREFIX') . 'house_village_payment' => 'p'))
                            ->where("p.payment_name like '%物业费%' AND psb.pigcms_id= '" . $v['pigcms_id'] . "'AND psb.village_id =" . $village_id . " AND p.payment_id = psb.payment_id AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND ps.standard_id = psb.standard_id AND `psb`.`start_time` <" . time())->select();

                        $payment_list = $payment_list ? $payment_list : [];
                        $payment_money = 0;
                        $cycle_type = array('Y' => 'year', 'M' => 'month', 'D' => 'day');
                        if ($payment_list) {
                            $db_house_village = D('House_village');
                            $app_version = intval($_POST['app_version']);
                            $deviceId = trim($_POST['Device-Id']);
                            $param = [
                                'pageName' => 'payMentlist',
                                'isAll' => true,
                                'urlParam' => '',
                            ];
                            $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                            foreach ($payment_list as $key => $value) {
                                if ($value['cycle_sum'] - $value['paid_cycle'] <= 0) {
                                    // 签订周期已缴完
                                    unset($payment_list[$key]);
                                    continue;
                                }
                                // 已缴到期时间
                                $end_time = strtotime(date('Y-m-d', $value['start_time']) . '+' . $value['pay_cycle'] * $value['paid_cycle'] . $cycle_type[$value['cycle_type']]);
                                if ($end_time < time()) {
                                    $num = D('House_village_user_bind')->getTimeNum($end_time, time(), $value['cycle_type']);
                                    $num = ceil($num / $value['pay_cycle']);
                                    $num = min($num, $value['cycle_sum']);
                                    if ($value['pay_type'] == 1) {
//                                        $value['payment_money'] = round($value['pay_money'] * $num, 2);
                                        $value['payment_money'] = $value['pay_money'] * $num;
                                        $payment_money = $value['payment_money'];
                                    } else {
//                                        $value['payment_money'] = round($value['pay_money'] * $num * $value['metering_mode_val'], 2);
                                        $value['payment_money'] = $value['pay_money'] * $num * $value['metering_mode_val'];
                                        $payment_money = $value['payment_money'];
                                    }
                                    if($value['payment_money']>0){
                                        if($score_round_two) {
                                            $cycle_money = round($value['payment_money'] / $num, 2);//计算个周期的价格
                                            $score_money_cycle = round($residue_score_money / $cycle_money,2);//计算积分钱可抵扣多少个周期 舍去法取整
                                            $to_score_money = $cycle_money * $score_money_cycle;//要抵扣的积分钱
                                            $residue_score = round(($residue_score_money - $to_score_money) * $user_score_use_percent,2);//所剩余的积分 取整
                                            $payment_money = round($payment_money,2);
                                        }else{
                                            $cycle_money = round($value['payment_money'] / $num);//计算个周期的价格
                                            $score_money_cycle = round($residue_score_money / $cycle_money);//计算积分钱可抵扣多少个周期 舍去法取整
                                            $to_score_money = $cycle_money * $score_money_cycle;//要抵扣的积分钱
                                            $residue_score = round(($residue_score_money - $to_score_money) * $user_score_use_percent);//所剩余的积分
                                            $payment_money = round($payment_money);
                                        }
                                        //如果每个周期的钱小于等于每个可抵扣的积分钱则进行抵扣，否则不抵扣
                                        if($cycle_money<=$residue_score_money){
                                            if($payment_money>=$to_score_money)
                                            {
                                                $residue_custom_money = $payment_money-$to_score_money;//剩余欠费
                                                $residue = 0;//剩余积分钱0
                                                $deduction = $to_score_money*$user_score_use_percent;//已抵扣的积分
                                                $to_deduction_score_money = $to_score_money;//抵扣的积分钱
                                            }else{
                                                $residue = $residue_score_money-$payment_money;//剩余积分钱
                                                $residue_custom_money = 0;//剩余欠费0
                                                $deduction = $payment_money*$user_score_use_percent;//已抵扣的积分
                                                $to_deduction_score_money = $payment_money;//抵扣的积分钱
                                            }

                                            //将剩余积分存入业主用户
                                          //  D('User')->where(['uid'=>$uid])->data(['score_count'=>$residue*$user_score_use_percent])->save();
                                            //生成积分消费记录
                                           /* $desc = '缴费'.$value['payment_name'].'抵扣积分';
                                            $score_arr = [
                                                'uid'=>$uid,
                                                'score'=>$deduction,
                                                'used_count'=>$residue*$user_score_use_percent,
                                                'desc'=>$desc,
                                            ];
                                            $this->user_action_log($score_arr);*/
                                            //D('User_score_list')->add_row($uid,2,0,$desc,1,0,0,[]);

                                            //生成物业账单
                                          /*  $data_arr['title'] = $value['payment_name'];
                                            $data_arr['money'] = $payment_money;
                                            $data_arr['score_can_use'] = $residue_score;
                                            $data_arr['score_can_pay'] = $deduction;
                                            $data_arr['score_used_count'] = $deduction;
                                            $data_arr['score_deducte'] = $to_deduction_score_money;
                                            $data_arr['order_type'] = 'custom_payment';
                                            $order_id = $this->recordPropertyBill($data_arr);*/
                                            //记录小区余额
                                          /*  $data_arr['order_id'] = $order_id;
                                            $this->recordVillageMoney($data_arr);
                                            D('House_village_payment_standard_bind')->where(['bind_id'=>$value['bind_id']])->data(['paid_cycle'=>$num])->save();*/
                                            if($openid){
                                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                                $keyword = '尊敬的业主，您的代缴总额剩余：'.$payment_money.'元，您现有'.$deduction.'积分可自主抵扣'.$value['payment_name'].$payment_money.'元';
                                                $remark = '点击缴费！';
                                                /*$keyword = '尊敬的业主，系统使用您'.$deduction.'积分已抵扣'.$value['payment_name'].$payment_money.'元';
                                                if($residue_custom_money){
                                                    $remark = '您的待缴总额剩余:'.$residue_custom_money.'元，点击缴费！';
                                                }else{
                                                    $remark = '';
                                                }*/
                                                $href = $payMentlistUrl.'?village_id='.$v['village_id'].'&pigcms_id='.$v['pigcms_id'];
                                                $model->sendTempMsg('TM01008',
                                                    array(
                                                        'href' => $href,
                                                        'wecha_id' => $openid,
                                                        'first' => $keyword,
                                                        'keynote1' => $house_village['village_name'],
                                                        'keynote2' => $address,
                                                        'remark' => $remark
                                                    )
                                                );
                                            }

                                            //短信推送
                                            if($sms_notice_set){
                                                if($v['phone']){
                                                    $sms_data['uid'] = $v['uid'];
                                                    $sms_data['mobile'] = $v['phone'];
                                                    $sms_data['sendto'] = 'user';
                                                    $sms_data['village_id'] = $v['village_id'];
                                                    $sms_data['type'] = 'propertyfee';//
                                                    $sms_data['content'] = '尊敬的'.$addre.'户业主，您的代缴总额剩余：'.$payment_money.'，您现有'.$deduction.'积分可自主抵扣'.$value['payment_name'].$payment_money.'元';
                                                  //   $sms_data['content'] = '尊敬的'.$addre.'户业主，'.$house_village['village_name'].'提醒您，系统使用您'.$deduction.'积分已抵扣'.$value['payment_name'].$payment_money.'元';
                                                    /*if($residue_custom_money){
                                                        $remark = '，剩余'.$residue_custom_money.'元请及时缴费';
                                                        $sms_data['content'] = $sms_data['content'].$remark;
                                                    }*/
                                                    Sms::sendSms($sms_data);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }
    //记录小区余额
    public function recordVillageMoney($data)
    {
        $now_village_money = D('Village_money_list')->where(['village_id'=>$data['village_id']])->order('id desc')->getField('now_village_money');
        $arr = [
            'village_id'=>$data['village_id'],
            'order_id'=>$data['order_id'],
            'num'=>1,
            'type'=>'village_pay',
            'money'=>$data['money'],
            'total_money'=>$data['money'],
            'income'=>1,
            'use_time'=>time(),
            'desc'=>$data['title'],
            'now_village_money'=>($now_village_money+$data['money']),
            'score_count'=>$data['score_can_pay']
        ];
        $res = D('Village_money_list')->add($arr);
        $money = D('House_village')->where(['village_id'=>$data['village_id']])->getField('money');
        D('House_village')->where(['village_id'=>$data['village_id']])->data(['money'=>$money+$data['money']])->save();
        return $res;
    }
    //记录物业已缴总账单
    public function recordPropertyBill($data)
    {
        $arr = [
            'order_name'=>$data['title'],
            'order_type'=>$data['order_type'],
            'uid'=>$data['uid'],
            'bind_id'=>$data['pigcms_id'],
            'village_id'=>$data['village_id'],
            'money'=>$data['money'],
            'time'=>time(),
            'pay_time'=>time(),
            'paid'=>1,
            'score_can_use'=>$data['score_can_use'],
            'score_can_pay'=>$data['score_can_pay'],
            'score_used_count'=>$data['score_used_count'],
            'score_deducte'=>$data['score_deducte'],
            'pay_type'=>2,
            'pay_cost_type'=>1,
        ];
        $id = D('House_village_pay_order')->data($arr)->add();
        return $id;
    }
    //用户积分操作记录
    public function user_action_log($data)
    {
        $arr = [
            'uid'=>$data['uid'],
            'type'=>2,
            'score'=>$data['score'],
            'time'=>time(),
            'desc'=>$data['desc'],
            'used_count'=>$data['used_count'],
        ];
        $id = D('User_score_list')->data($arr)->add();
        return $id;
    }
    

    /**
     * Notes: 获取物业相关信息
     * @param int $property_id
     * @param int $village_id
     * @param int $pigcms_id
     * @param bool $is_manage
     * @return array
     * @author: wanzy
     * @date_time: 2021/1/26 14:27
     */
    public function property_contact($property_id=0,$village_id=0,$pigcms_id=0,$is_manage=false) {
        if ($pigcms_id) {
            $db_house_village_user_bind = D('House_village_user_bind');
            $where_bind = [
                'pigcms_id' => $pigcms_id
            ];
            $house_bind = $db_house_village_user_bind->where($where_bind)->field('pigcms_id,village_id,single_id,floor_id,vacancy_id')->find();
            if ($house_bind) {
                $village_id = $house_bind['village_id'] ? $house_bind['village_id'] : $village_id;
                $single_id = $house_bind['single_id'] ? $house_bind['single_id'] : 0;
                $floor_id = $house_bind['floor_id'] ? $house_bind['floor_id'] : 0;
                $room_id = $house_bind['vacancy_id'] ? $house_bind['vacancy_id'] : 0;
            }
        }
        $db_house_village = D('House_village');
        $condition_village = [];
        $condition_village['status'] = 1;
        if ($property_id) {
            $condition_village['property_id'] = $property_id;
        } elseif ($village_id) {
            $condition_village['village_id'] = $village_id;
        }
        $village_arr = [];
        $property_phone_list = $db_house_village->where($condition_village)->field('village_id,property_id,property_phone')->select();
        $property_phone_arr = [];
        if ($property_phone_list) {
            foreach ($property_phone_list as $val) {
                if (!$property_id && $val['property_id']) {
                    $property_id = $val['property_id'];
                }
                $village_arr[] = $val['village_id'];
                if ($val['property_phone']) {
                    $property_phone = explode(' ', $val['property_phone']);
                    if ($property_phone) {
                        $property_phone_arr = array_merge($property_phone, $property_phone_arr);
                    }
                }
            }
        }
        $db_house_services_housekeeper = M('House_services_housekeeper');
        $keeper_info = [];
        if ($floor_id) {
            $where_services_housekeeper = [];
            $where_services_housekeeper['floor_id'] = $floor_id;
            $where_services_housekeeper['work_arr'] = ['neq',''];
            $services_housekeeper = $db_house_services_housekeeper->where($where_services_housekeeper)->find();
            if ($services_housekeeper) {
                $keeper_info['title'] = '单元管家';
            }
        }
        if ($single_id && !$services_housekeeper) {
            $where_services_housekeeper = [];
            $where_services_housekeeper['floor_id'] = 0;
            $where_services_housekeeper['single_id'] = $single_id;
            $where_services_housekeeper['work_arr'] = ['neq',''];
            $services_housekeeper = $db_house_services_housekeeper->where($where_services_housekeeper)->find();
            if ($services_housekeeper) {
                $keeper_info['title'] = '楼栋管家';
            }
        }
        if ($village_id && !$services_housekeeper) {
            $where_services_housekeeper = [];
            $where_services_housekeeper['floor_id'] = 0;
            $where_services_housekeeper['single_id'] = 0;
            $where_services_housekeeper['village_id'] = $village_id;
            $where_services_housekeeper['work_arr'] = ['neq',''];
            $services_housekeeper = $db_house_services_housekeeper->where($where_services_housekeeper)->find();
            if ($services_housekeeper) {
                $keeper_info['title'] = '物业管家';
            }
        }
        if ($village_arr && !$services_housekeeper) {
            $where_services_housekeeper = [];
            $where_services_housekeeper['floor_id'] = 0;
            $where_services_housekeeper['single_id'] = 0;
            $where_services_housekeeper['village_id'] = ['in', $village_arr];
            $where_services_housekeeper['work_arr'] = ['neq',''];
            $services_housekeeper = $db_house_services_housekeeper->where($where_services_housekeeper)->find();
            if ($services_housekeeper) {
                $keeper_info['title'] = '物业管家';
            }
        }
        if ($is_manage) {
            $enterprise_wx_corpid = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->getField('corpid');
            $work_arr = $services_housekeeper['work_arr'];
            $where_work = [];
            $where_work['_string'] = "wid in ({$work_arr})";
            $where_work['status'] = 1;
            /*
            if ($enterprise_wx_corpid) {
                $where_work['qy_id'] = ['neq', ''];
                $where_work['qy_status'] = 1;
            }
           */ 
            $work = M('House_worker')->where($where_work)->field('wid,name, phone,avatar, qy_id, qy_status')->order('qy_id desc')->find();
            if ($work) {
                if ($enterprise_wx_corpid) {
                    $param = [
                        'housekeeper_id' => $services_housekeeper['housekeeper_id'],
                        'room_id' => $room_id,
                    ];
                    $result = invoke_v20_service('\app\community\model\service\QywxService/contactWayHousekeeper', $param);
                    $wx_contact_info = isset($result['data']['retval']) && $result['data']['retval'] ? $result['data']['retval'] : [];
                } else {
                    $wx_contact_info = [];
                }
                $keeper_info['name'] = $work['name'] ? $work['name'] : '';
                $keeper_info['phone'] = $work['phone'] ? $work['phone'] : '';
                $keeper_info['head'] = $work['avatar'] ? replace_file_domain($work['avatar']) : C('config.site_url') . '/static/images/user_avatar.jpg';
                $keeper_info['url'] = '';
                $keeper_info['icon'] = C('config.site_url') . '/static/images/house/keeper.png?t=02';
                $keeper_info['wx_contact'] = isset($wx_contact_info['qr_code']) ? $wx_contact_info['qr_code'] : '';
                $keeper_info['wx_config_id'] = isset($wx_contact_info['config_id']) ? $wx_contact_info['config_id'] : '';
            } else {
                $keeper_info = [];
            }
            return $keeper_info;
        } else {
            $property_phone_arr = array_unique($property_phone_arr);
            $property_phone_arr = array_values($property_phone_arr);
            if ($services_housekeeper['housekeeper_id']) {
                $param = [
                    'housekeeper_id' => $services_housekeeper['housekeeper_id'],
                    'room_id' => $room_id,
                ];
                $result = invoke_v20_service('\app\community\model\service\QywxService/contactWayHousekeeper', $param);
                $wx_contact_info = isset($result['data']['retval']) && $result['data']['retval'] ? $result['data']['retval'] : [];
            } else {
                $wx_contact_info = [];
            }
            $keeper_info = [];
            $keeper_info['property_phone'] = $property_phone_arr;
            $keeper_info['phone_icon'] = C('config.site_url') . '/static/images/house/qywx/phone.png';
            $keeper_info['wx_icon'] = C('config.site_url') . '/static/images/house/qywx/wx.png';
            $keeper_info['wx_contact'] = isset($wx_contact_info['qr_code']) ? $wx_contact_info['qr_code'] : '';
            $keeper_info['wx_config_id'] = isset($wx_contact_info['config_id']) ? $wx_contact_info['config_id'] : '';
            return $keeper_info;
        }
    }

    public function del_data($type='single', $type_id=0) {
        if (empty($type) || empty($type_id) || !in_array($type,['single','floor','layer','room'])) {
            return false;
        }
        if ('single'==$type) {
            // 删除了楼栋 删除下属 单元
            $db_house_village_floor = M('House_village_floor');
            $set_floor = [
                'status' => 4,
                'floor_layer' => array('exp',"concat(`floor_layer`,'single{$type_id}')")
            ];
            $db_house_village_floor->where(['single_id' => $type_id,'status' => ['neq', 4]])->save($set_floor);
            // 删除了楼栋 删除下属 楼层
            $db_house_village_layer = M('House_village_layer');
            $set_layer = [
                'status' => 4,
                'layer_name' => array('exp',"concat(`layer_name`,'single{$type_id}')")
            ];
            $db_house_village_layer->where(['single_id' => $type_id,'status' => ['neq', 4]])->save($set_layer);
            // 删除了楼栋 删除下属 房屋
            $db_house_village_user_vacancy = M('House_village_user_vacancy');
            $set_vacancy = [
                'is_del' => 1,
                'del_time' => time(),
                'usernum' => array('exp',"concat(`usernum`,'single{$type_id}')")
            ];
            $db_house_village_user_vacancy->where(['single_id' => $type_id,'is_del' => ['neq', 1]])->save($set_vacancy);
            // 删除了楼栋 删除下属 住户
            $db_house_village_user_bind = M('House_village_user_bind');
            $set_user_bind = [
                'status' => 4,
                'usernum' => array('exp',"concat(`usernum`,'single{$type_id}')")
            ];
            $db_house_village_user_bind->where(['single_id' => $type_id,'status' => ['neq', 4]])->save($set_user_bind);
        } elseif ('floor'==$type) {
            // 删除了单元 删除下属 楼层
            $db_house_village_layer = M('House_village_layer');
            $set_layer = [
                'status' => 4,
                'layer_name' => array('exp',"concat(`layer_name`,'floor{$type_id}')")
            ];
            $db_house_village_layer->where(['floor_id' => $type_id,'status' => ['neq', 4]])->save($set_layer);
            // 删除了单元 删除下属 房屋
            $db_house_village_user_vacancy = M('House_village_user_vacancy');
            $set_vacancy = [
                'is_del' => 1,
                'del_time' => time(),
                'usernum' => array('exp',"concat(`usernum`,'floor{$type_id}')")
            ];
            $db_house_village_user_vacancy->where(['floor_id' => $type_id,'is_del' => ['neq', 1]])->save($set_vacancy);
            // 删除了单元 删除下属 住户
            $db_house_village_user_bind = M('House_village_user_bind');
            $set_user_bind = [
                'status' => 4,
                'usernum' => array('exp',"concat(`usernum`,'floor{$type_id}')")
            ];
            $db_house_village_user_bind->where(['floor_id' => $type_id,'status' => ['neq', 4]])->save($set_user_bind);
        } elseif ('layer'==$type) {
            // 删除了楼层 删除下属 房屋
            $db_house_village_user_vacancy = M('House_village_user_vacancy');
            $set_vacancy = [
                'is_del' => 1,
                'del_time' => time(),
                'usernum' => array('exp',"concat(`usernum`,'layer{$type_id}')")
            ];
            $db_house_village_user_vacancy->where(['layer_id' => $type_id,'is_del' => ['neq', 1]])->save($set_vacancy);
            // 删除了楼层 删除下属 住户
            $db_house_village_user_bind = M('House_village_user_bind');
            $set_user_bind = [
                'status' => 4,
                'usernum' => array('exp',"concat(`usernum`,'layer{$type_id}')")
            ];
            $db_house_village_user_bind->where(['layer_id' => $type_id,'status' => ['neq', 4]])->save($set_user_bind);
        } elseif ('room'==$type) {
            // 删除了房屋 删除下属 住户
            $db_house_village_user_bind = M('House_village_user_bind');
            $set_user_bind = [
                'status' => 4,
                'usernum' => array('exp',"concat(`usernum`,'room{$type_id}')")
            ];
            $db_house_village_user_bind->where(['vacancy_id' => $type_id,'status' => ['neq', 4]])->save($set_user_bind);
        }
        return true;
    }


    //todo 校验是否开启新版物业收费
    public function checkNewCharge($property_id){
        $new_charge_time = D('House_new_charge_time')->field('take_effect_time')->where(array('property_id' => $property_id))->find();
        $nowtime = time();
        $status=0;
        if (!empty($new_charge_time) && $new_charge_time['take_effect_time'] < $nowtime && $new_charge_time['take_effect_time'] > 1) {
            $status=1;
        }
        return $status;
    }

    //todo 查询当前房间的合同时间
    public function getContractTime($vacancy_id){
        //查房间的合同时间
        $vacancy_time=D('House_village_user_vacancy')->field('village_id,single_id,contract_time_start,contract_time_end')->where(array('pigcms_id' => $vacancy_id))->find();
        $arr=[];
        if (!empty($vacancy_time)&&$vacancy_time['contract_time_start']>1){
            $arr['contract_time_start']=$vacancy_time['contract_time_start'];
            $arr['contract_time_end']=$vacancy_time['contract_time_end'];
            return $arr;
        }
        //楼栋的合同时间
        $single_time=D('House_village_single')->field('contract_time_start,contract_time_end')->where(array('id' => $vacancy_time['single_id']))->find();
        if (!empty($single_time)&&$single_time['contract_time_start']>1){
            $arr['contract_time_start']=$single_time['contract_time_start'];
            $arr['contract_time_end']=$single_time['contract_time_end'];
            return $arr;
        }
        //小区的合同时间
        $village_time=D('House_village_info')->field('contract_time_start,contract_time_end')->where(array('village_id' => $vacancy_time['village_id']))->find();
        if (!empty($village_time)&&$village_time['contract_time_start']>1){
            $arr['contract_time_start']=$village_time['contract_time_start'];
            $arr['contract_time_end']=$village_time['contract_time_end'];
            return $arr;
        }
        $arr['contract_time_start']=0;
        $arr['contract_time_end']=0;
        return $arr;
    }

    //获取新版物业收费已经生效的所有物业id数组
    public function checkNewChargeAllIds(){
        $where_ = [];
        $where_['property_id'] = ['gt',0];
        $where_['take_effect_time'] = array('between',array(0,time()));
        $propertyIdArr= D('House_new_charge_time')->where($where_)->getField('property_id',true);
        if (empty($propertyIdArr)){
            $propertyIdArr = [];
        }
        return $propertyIdArr;
    }
    public function isSoftwareMew() {
        $software_new_version = C('config.software_new_version');
        if ($software_new_version) {
            return true;
        } else {
            return false;
        }
    }
    public function  setSoftwareNewEffectCharge($property_id) {
        $isSoftwareMew = isSoftwareMew();
        if ($isSoftwareMew) {
            // 如果是符合条件的 直接 按照当前时间前推10天设置物业新版收费生效时间
            $nowTimeS = strtotime(date('Y-m-d'));
            $setTakeEffectTime = $nowTimeS-864000;
            $new_charge_time = D('House_new_charge_time')->field(true)->where(array('property_id' => $property_id))->find();
            if ($new_charge_time['take_effect_time']&&$new_charge_time['take_effect_time']<$nowTimeS) {
                return true;
            }
            $saveData = [
                'property_id' => $property_id,
                'take_effect_time' => $setTakeEffectTime,
            ];
            if ($new_charge_time) {
                $saveData['update_time'] = time();
                D('House_new_charge_time')->where(array('property_id' => $property_id))->save($saveData);
            } else {
                $saveData['add_time'] = time();
                D('House_new_charge_time')->add($saveData);
            }
        }
        return true;
    }

    /**
     * 校验是否可以发送小区短信
     */
    public function checkVillageSms($village_id, $smsNum = 1, $getPropertySmsSign = false) {
        $now_sms_number = $this->correctVillageSmsNumber($village_id, $smsNum);
        if (intval($smsNum) > $now_sms_number) {
            return false;
        }
        $arr = [
            'now_sms_number' => $now_sms_number
        ];
        if ($getPropertySmsSign) {
            $property_id = $this->where(['village_id' => $village_id])->getField('property_id');
            $sms_sign = M('House_property')->where(['id' => $property_id])->getField('sms_sign');
            $arr['sms_sign'] = $sms_sign;
        }
        return $arr;
    }
    
    public function correctVillageSmsNumber($village_id, $smsNum = 1) {
        $sms_type = M('House_village_info')->where(array('village_id' => $village_id))->getField('sms_type');
        if (intval($sms_type) === 2) {
            // 短信使用类型 1小区短信，2 平台短信
            // 使用平台短信返回数量直接是满足发送条数，并不变更小区发送条数
            return $smsNum + 1;
        }
        $buy_order_where = array('type_id' => $village_id, 'type' => 1, 'paid' => array('gt', 0), 'pay_time' => array('gt', 100));
        $sms_number = D("Sms_buy_order")->where($buy_order_where)->sum('sms_number');
        $sedcount = D("Sms_record")->where(array('status' => 0, 'village_id' => $village_id))->count();
        /*
        $villageSendIds = D("Sms_record")->where(array('status' => 0, 'village_id' => $village_id, 'village_send_id' => array('gt', 0)))->getField('village_send_id', true);
        ! empty($villageSendIds) && $villageSendIds = array_unique($villageSendIds);
        if (! empty($villageSendIds)) {
            $villageSendCount = M('House_sms_group_send')->where(['village_id' => ['in', $villageSendIds]])->sum('success_send_num');
            $sedcount += intval($villageSendCount);
        }
        */
        $now_sms_number = $sms_number - $sedcount;
        $now_sms_number = $now_sms_number > 0 ? $now_sms_number : 0;
        M('House_village')->where(array('village_id'=>$village_id))->data(array('now_sms_number'=>$now_sms_number))->save();
        return $now_sms_number;
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
        if(!C('customization')){
            $Config_customizationDb=D('Config_customization');
            if(method_exists($Config_customizationDb,'get_config')){
                C('customization',$Config_customizationDb->get_config());
            }
        }
        if(C('customization.set_house_alias') == 1){
            $return['house_name'] = C('config.house_name')?:L_('小区');
            $return['single_name'] = C('config.single_name')?:L_('楼栋');
            $return['floor_name'] = C('config.floor_name')?:L_('单元');
            $return['room_name'] = C('config.room_name')?:L_('房间');
            if($village_id){
                $villageConfigData = M('House_village')->where(['village_id'=>$village_id])->field('single_name,floor_name,room_name')->find();
                if($villageConfigData){
                    $return['single_name'] = $villageConfigData['single_name'] ?: $return['single_name'];
                    $return['floor_name'] = $villageConfigData['floor_name'] ?: $return['floor_name'];
                    $return['room_name'] = $villageConfigData['room_name'] ?: $return['room_name'];
                }
            }
        }else{
            $return['house_name'] = C('config.house_name')?:L_('小区');
        }
        if($name){
            return $return[$name]??'';
        }
        return $return;
    }
    public function getVillageNameById($villageId)
    {
            $key = 'c:village:info:'.$villageId;
           $info =  S($key);   
           if (!empty($info)){
               return $info['village_name'];
           }
        $info =  M('House_village')->field('village_id,village_name')->where(array('village_id'=>(int)$villageId))->find();
        S($key,$info,7200);
        
        return $info['village_name'];
    }

    public function phone_desensitization_v($phone = '', $village_id = 0, $village_info = array())
    {
        if (empty($phone)) {
            return $phone;
        }
        if(strpos($phone,'-') || strlen($phone)<9){
            return $phone;
        }
        if ((empty($village_info) || !isset($village_info['desensitization_tel'])) && $village_id > 0) {
            $village_info = $this->field('*')->where(array('village_id' => $village_id))->find();
        }
        if (!empty($village_info) && isset($village_info['desensitization_tel']) && $village_info['desensitization_tel'] < 1) {
            //小区未开启 手机号脱敏
            return $phone;
        }
        return phone_desensitization($phone);
    }

    public function idnum_desensitization_v($id_card = '', $village_id = 0, $village_info = array())
    {
        if (empty($id_card)) {
            return $id_card;
        }
        if ((empty($village_info) || !isset($village_info['desensitization_idnum'])) && $village_id > 0) {
            $village_info = $this->field('*')->where(array('village_id' => $village_id))->find();
        }
        if (!empty($village_info) && isset($village_info['desensitization_idnum']) && $village_info['desensitization_idnum'] < 1) {
            //小区未开启 身份证号脱敏
            return $id_card;
        }
        return idnum_desensitization($id_card);
    }
}
?>
