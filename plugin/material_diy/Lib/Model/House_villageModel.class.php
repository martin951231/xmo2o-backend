<?php
class House_villageModel extends Model{
    protected $_validate = array(
        array('wx_image','require','图片必须存在！',1),
    );

    protected $_auto = array(
        array('last_time','time',3,'function'),
    );

    public $base_url = '';
	
	public function __construct(){
		// if(C('config.local_dev')){
			// $this->base_url = '/packapp/plat_dev/';
		// }else{
			if(C('config.system_type') == 'village'){
				$this->base_url = '/packapp/village/';
			}else{
				$this->base_url = '/packapp/plat/';
			}
		// }
		parent::__construct();
	}


	public $village_param_word = array(
		0=>array('key' => '{楼栋}', 'word' => 'single_name'),
		1=>array('key' => '{单元}', 'word' => 'floor_name'),
		2=>array('key' => '{层数}', 'word' => 'layer'),
		3=>array('key' => '{房间号}', 'word' => 'room'),
	);
	public $village_param_txt = "{楼栋}、{单元}、{层数}、{房间号}";
	public $defaulf_diy_txt = "{楼栋}{单元}{层数}{房间号}";

	public $test_room = '天字楼地号房（测试房间）';


	/**
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
	public function word_replce_msg($data=array(),$village_id=0,$word='') {
		if (empty($data['single_name']) && empty($data['floor_name']) && empty($data['layer']) && empty($data['room'])) {
            if (C('config.is_demo_domain')) {
                $word = $this->test_room;
            } else {
                $word = '无';
            }
			return $word;
		}

		$village_param_word = $this->village_param_word;
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
					}
					$word = preg_replace('/'.$val['key'].'/', $data[$val['word']], $word);
				}
			}
		}
		return $word;
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
		if (!$is_family) {
			$where .= " AND `parent_id`=0";
		}
		
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
	    $list = D('')->field($field)->table($table)->where($where)->group('`hv`.`village_id`')->select();
	    foreach($list as $k=>$v){
			$list[$k]['flag'] = 1;
			$list[$k]['village_logo'] = $v['village_logo'] ? file_domain().$v['village_logo']: C('config.site_url').'/static/images/wap_house/village_logo.png';
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
			'status'=>'1',
		);
		if ($long_lat['lat']&&$long_lat['long']) {
			$order = "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']} * PI() / 180- `lat` * PI()/180)/2),2)+COS({$long_lat['lat']} *PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']} *PI()/180- `long`*PI()/180)/2),2)))*1000) ASC";
		} else {
			// 没有经纬度显示当前城市的小区，否则显示所有小区
			// $condition_village['city_id'] = C('config.now_city');
			$order = "`village_id` DESC";
		}
		import('@.ORG.wap_group_page');
		if(!empty($keyword)){
			$condition_village['village_name'] = array('like','%'.$keyword.'%');
		}
		if(!empty($account)){
			$condition_village['account'] = $account;
		}
		if ($property_id && $property_id>0) {
            $condition_village['property_id'] = $property_id;
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
	public function get_one($village_id,$field =true){
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
                return $access_token_array;
            }
        }
    }

    /**
     * 鉴定文字内容是否安全
     * @param $msg
     * @return bool
     */
    public function msgSecCheck($msg) {
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
                if ($check_ret['errcode'] <= -1) {
                    $check_ret['errcode'] = 0;
                }
                return $check_ret;
            } else {
                $access_token_array['message'] = L_('内容含有违法违规内容');
                return $access_token_array;
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
}

?>