<?php
/*
 * 短信发送记录
 *
 */

class SmsAction extends BaseAction
{
    public function index()
    {
        $where = [];
        $phone = I('get.phone', '', 'trim');
        $begin_time = I('get.begin_time', '', 'trim');
        $end_time = I('get.end_time', '', 'trim');

        if($phone){
            $where['b.phone'] = $phone;
        }
        if($begin_time){
            $where['b.time'][] = ['gt',strtotime($begin_time)];
        }
        if($end_time){
            $where['b.time'][] = ['lt',strtotime($end_time)+86400];
        }

		$count = D("Sms_record")->alias('b')->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
        $res = M('Merchant')->alias('a')->join("RIGHT JOIN " . C('DB_PREFIX') . "sms_record AS b ON a.mer_id=b.mer_id")
            ->field('a.name, b.*')
            ->where($where)
            ->order('b.pigcms_id DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();

		$status = array('0' =>'发送成功', '-1' => '验证失败未购买', '-2' => '短信不足', '-3' => '操作失败', '-4' => '非法字符', '-5' => '内容过多', '-6' => '号码过多', '-7' => '频率过快', '-8' => '号码内容空', '-9' => '账号冻结', '-10' => '禁止频繁单条发送', '-11' => '系统暂定发送', '-12' => '有错误号码', '-13' => '定时时间不对', '-14' => '账号被锁，10分钟后登录', '-15' => '连接失败', '-16' => '禁止接口发送', '-17' => '绑定IP不正确', '-18' => '系统升级', '-19' => '域名不对', '-20' => 'key不匹配', '-21' => '用户不存在', '-22' => '余额不足', '-100' => '发送的token不合法','-101' => '短信key错误','-999'=>'频繁发送');
		
		$this->assign('record_list', $res);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->assign('status', $status);
		$this->display();
    }
    
	public function getAppInfo(){
		$appObj  = new Apkparser(); 
		$targetFile = str_replace($this->config['site_url'].'/','',$_POST['apk']);
		$targetFile = ltrim($targetFile,'/');
		
		if(!file_exists($targetFile)){
			$this->error('文件不存在！请注意，上传文件不能包含中文。');
		}
		
		$res   = $appObj->open($targetFile);
		
		$arr = array(
			'apkName' => $appObj->getAppName(),
			'apkPackage' => $appObj->getPackage(),
			'apkVersionName' => $appObj->getVersionName(),
			'apkVersionCode' => $appObj->getVersionCode(),
		);
		
		if(stripos($arr['apkName'],'com.tencent.') !== 0 && stripos($arr['apkName'],'com.stub.StubApp') !== 0){
			$this->error('APK包没有加壳，无法提交');
		}
		$this->success($arr);
	}

	public function mobileAppList() {
        $db_list = D('Appapi_pack_list');
        import('@.ORG.system_page');
        $where = [
            'from' => 0
        ];
        $count = $db_list->where($where)->count();
		
		//如果表里没有APP，则直接跳转单平台APP里
		if($count == 0){
			redirect(U('mobileApp'));
		}
		
        $p = new Page($count, 20);
        $list = $db_list->where($where)->order('pack_id DESC')->limit($p->firstRow,$p->listRows)->select();
        if (!empty($list)) {
            $type_arr = [
                1 => '平台APP',
                2 => '社区APP',
                3 => '跑腿APP',
            ];
            $config = D("Appapi_app_config");
            foreach ($list as &$val) {
                $val['type_name'] = $type_arr[$val['type']];
                $count = $config->where(['pack_id'=> $val['pack_id'],'value' => ['neq', '']])->count();
                if (empty($count) || !$count) {
                    $val['tip_color'] = 'red';
                } elseif (intval($count)<7) {
                    $val['tip_color'] = '#FFB868';
                } else {
                    $val['tip_color'] = 'green';
                }
            }
        }
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list', $list);
		
		
		$app_pack = new app_pack();
		$new_version_arr = $app_pack->getNewVersion();
		$this->assign('new_version', $new_version_arr['plat']);
		$this->assign('all_new_version', $new_version_arr);
			
			
        $this->display();
    }

    public function mobileAppAdd() {
        $db_list = D('Appapi_pack_list');
        $pack_id = I('pack_id',0, 'intval');
        $where = [
            'pack_id' => $pack_id
        ];
        $info = $db_list->where($where)->find();
        if ($pack_id && empty($info)) {
            $this->error('编辑对象不存在');
        }
        $this->assign('info', $info);
        $this->display();
    }

    public function mobileAmend() {
        $pack_id = I('pack_id',0, 'intval');
        $name = I('name','','trim');
        $type = I('type',0,'intval');
        $package_name = I('package_name','','trim');
        $package_name2 = I('package_name2','','trim');
        $jpush_appkey = I('jpush_appkey','','trim');
        $jpush_secret = I('jpush_secret','','trim');
        $weixinapp_appid = I('weixinapp_appid','','trim');
        $weixinapp_appsecret = I('weixinapp_appsecret','','trim');
        if (empty($name)) {
            $this->error('请填写APP名称');
        }
        if (empty($type)) {
            $this->error('请选择APP类型');
        }
        if (empty($package_name) && empty($package_name2)) {
            $this->error('请至少填写一个包名');
        }
        $data = [
            'from' => 0,
            'name' => $name,
            'type' => $type,
            'package_name' => $package_name?$package_name:'',
            'package_name2' => $package_name2?$package_name2:'',
            'jpush_appkey' => $jpush_appkey?$jpush_appkey:'',
            'jpush_secret' => $jpush_secret?$jpush_secret:'',
            'weixinapp_appid' => $weixinapp_appid?$weixinapp_appid:'',
            'weixinapp_appsecret' => $weixinapp_appsecret?$weixinapp_appsecret:'',
        ];
        $db_list = D('Appapi_pack_list');
        if (empty($pack_id)) {
            $data['add_time'] = time();
            $pack_id = $db_list->add($data);
            if (!$pack_id) {
                $this->error('添加失败');
            }
            $this->success('添加成功！');
        } else {
            $data['update_time'] = time();
            $where = [
                'pack_id' => $pack_id
            ];
            $info = $db_list->where($where)->find();
            if (empty($info)) {
                $this->error('编辑对象不存在');
            }
            $set_id = $db_list->where($where)->save($data);
            if ($set_id==false) {
                $this->error('编辑失败');
            }
            $this->success('编辑成功！');
        }
    }
    //APP版本升级
    public function mobileApp() {
        $config = D("Appapi_app_config");
        $pack_id = intval($_GET['pack_id']);
        if (IS_POST) {
            $columns = array();
            $columns['ios_version'] = I("ios_version", '');
            $columns['android_version'] = I("android_version", '');
			$columns['ios_download_url'] = htmlspecialchars_decode($_POST['ios_download_url']);
            $columns['android_download_url'] = htmlspecialchars_decode($_POST['android_download_url']);
            $columns['android_version_code'] = I("android_version_code", '');
            $columns['ios_version_code'] = I("ios_version_code", '');
            $columns['android_version_desc'] = I("android_version_desc", '');
            $columns['platform_icp'] = I("platform_icp", '');
			$columns['privacy_policy'] = htmlspecialchars_decode($_POST['privacy_policy']);
            $columns['user_agreement'] = htmlspecialchars_decode($_POST['user_agreement']);


            $where_columns_version = array('var'=>'android_version_code');
            if ($pack_id) {
                $where_columns['pack_id'] = $pack_id;
            }
			$android_version_code = $config->where($where_columns_version)->getField('value');
			if($_POST['uploadApk'] && $columns['android_version_code'] <= $android_version_code){
				$this->error('APP版本号必须比原来大，原来的版本号为 '.$android_version_code);
			}
			
			$i = 1;
            foreach ($columns as $key=>$val) {
                $where_columns = array('var'=>$key);
                if ($pack_id) {
                    $where_columns['pack_id'] = $pack_id;
                }
				$result=$config->where($where_columns)->data(array('value'=>$val))->save();
                if($result === 0){
                    $date['var'] = $key;
                    $date['value'] = $val;
                    $date['sort'] = $i;
                    $i++;
                    if ($pack_id) {
                        $date['pack_id'] = $pack_id;
                    }
                    $config->data($date)->add();
                }
            }
			
			$config->remove($pack_id);
            $this->success("修改成功");
        } else {
            if ($pack_id) {
                $where_config = [
                    'pack_id' => $pack_id
                ];
                $data = $config->where($where_config)->select();
            } elseif (isset($_GET['pack_id'])) {
                $data = [];
            } else {
                $data = $config->select();
            }
            $return = array();
            foreach ($data as $val) {
                $return[$val['var']] = $val['value'];
                if ($val['var']=='privacy_policy') {
                    $return[$val['var']] = htmlspecialchars_decode($val['value']);
                }
            }
            $this->assign($return);
            if ($pack_id) {
                $where_pack_config = [
                    'pack_id' => $pack_id
                ];
                $pack_data = D('Appapi_pack_config')->where($where_pack_config)->select();
            } elseif (isset($_GET['pack_id'])) {
                $pack_data = [];
            } else {
                $pack_data = D('Appapi_pack_config')->select();
            }
			foreach ($pack_data as $val) {
				$pack_return[$val['var']] = $val['value'];
			}
			$this->assign('pack_return', $pack_return);
			
			$app_pack = new app_pack();
			$new_version_arr = $app_pack->getNewVersion();
			$this->assign('new_version', $new_version_arr['plat']);
			$this->assign('all_new_version', $new_version_arr);
			
			
			//如果有多打包机制，则用一个
			if($pack_id){
				$nowPackApp = D('Appapi_pack_list')->where(['pack_id' => $pack_id])->find();
				$this->assign('nowPackApp', $nowPackApp);
			}
			
			
            $this->display();
        }
    }
	
	public function ios_appstore_version(){
		$app_info = trim(file_get_contents('https://itunes.apple.com/cn/lookup?id=' . $_GET['appid']));
		
		$app_info_arr = json_decode($app_info, true);
		
		$return_arr = [
			'version' => $app_info_arr['results'][0]['version'],
			'bundleId' => $app_info_arr['results'][0]['bundleId'],
			'down_url' => 'https://apps.apple.com/cn/app/id' . $_GET['appid'],
		];
		
		$this->success($return_arr);
	}
	
	public function ajax_upload_pic(){
		if($_FILES['imgFile']['error'] != 4){
			if($_GET['type']){
				$type = $_GET['type'];
			}else{
				$type =  'config';
			}
			$image = D('Image')->handle($this->system_session['id'], $type, 0, array('size' => 3), false);
			if($image['error']){
				exit(json_encode(array('error' => 1,'message' =>$image['message'])));
			}
			$image_info = getimagesize(ltrim($image['url']['imgFile'], '/'));
			if($_GET['width'] && $image_info[0] != $_GET['width']){
				exit(json_encode(array('error' => 1,'message' =>'上传图片宽度为'.$image_info[0].'px，需要宽度为'.$_GET['width'].'px')));
			}
			if($_GET['height'] && $image_info[1] != $_GET['height']){
				exit(json_encode(array('error' => 1,'message' =>'上传图片高度为'.$image_info[0].'px，需要高度为'.$_GET['width'].'px')));
			}
			if($_GET['suffix'] == 'png' && $image_info['mime'] != 'image/png'){
				exit(json_encode(array('error' => 1,'message' =>'请上传png格式图片！更改图片文件名后缀格式是不会成为png的，需要通过ps等工具编辑导出。')));
			}
			if (!$image['error']) {
				exit(json_encode(array('error' => 0,'url' => $image['url']['imgFile'], 'title' => $image['title']['imgFile'])));
			}
			exit(json_encode($image));
		}else{
			exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
		}
	}
	
	public function ajax_upload_zip(){

		if($_FILES['imgFile']['error'] != 4){
			$img_admin_id = sprintf("%09d", $this->system_session['id']);
			$rand_num = substr($img_admin_id,0,3) . '/' . substr($img_admin_id,3,3) . '/' . substr($img_admin_id,6,3);
			$upload_dir = "./upload/files/{$rand_num}/";
			if(!is_dir($upload_dir)){
				mkdir($upload_dir,0777,true);
			}
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();
			$upload->maxSize = 10*1024*1024;
			$upload->allowExts = ['zig','jks','keystore','p8'];
			$upload->savePath = $upload_dir;
			$upload->saveRule = 'uniqid';
			if($upload->upload()){
				$uploadList = $upload->getUploadFileInfo();
				$title = $rand_num.','.$uploadList[0]['savename'];
				exit(json_encode(array('error' => 0,'url' =>'./upload/files/'.$rand_num.'/'.$uploadList[0]['savename'],'title'=>$title)));
			}else{
				exit(json_encode(array('error' => 1,'message' =>$upload->getErrorMsg())));
			}
		}else{
			exit(json_encode(array('error' => 1,'message' =>'没有选择文件')));
		}
	}
	
	public function mobileAppUpdatePack(){
		$app_pack = new app_pack();
		$resultArr = $app_pack->packApp($_GET['type'], $_GET['pack_id']);
		
		if($resultArr['err_code']){
			$this->error($resultArr['err_msg']);
		}else{
			$key = $_GET['type'] . '_pack_id';
			$where = [
				'var' => $key,
			];
			if($_GET['pack_id']){
				$where['pack_id'] = $_GET['pack_id'];
			}
			if(D('Appapi_pack_config')->where($where)->find()){
				D('Appapi_pack_config')->where($where)->data(['value' => trim($resultArr['pack_id'])])->save();
			}else{
				$where['value'] = trim($resultArr['pack_id']);
				D('Appapi_pack_config')->data($where)->add();
			}
			$this->success($resultArr['err_msg']);
		}
	}
	
	public function mobileAppUpdateSave(){
        if($_POST['plat_android_bundleid'] && strpos($_POST['plat_android_bundleid'], 'android') !== false){
			$this->error('Android 包名中不能包含 android 等系统关键字。');
		}
		if($_POST['storestaff_android_bundleid'] && strpos($_POST['storestaff_android_bundleid'], 'android') !== false){
			$this->error('Android 包名中不能包含 android 等系统关键字。');
		}
		if($_POST['merchant_android_bundleid'] && strpos($_POST['merchant_android_bundleid'], 'android') !== false){
			$this->error('Android 包名中不能包含 android 等系统关键字。');
		}
		if($_POST['deliver_android_bundleid'] && strpos($_POST['deliver_android_bundleid'], 'android') !== false){
			$this->error('Android 包名中不能包含 android 等系统关键字。');
		}
		if($_POST['village_manager_android_bundleid'] && strpos($_POST['village_manager_android_bundleid'], 'android') !== false){
			$this->error('Android 包名中不能包含 android 等系统关键字。');
        }
        $pack_id = intval($_GET['pack_id']);
        if ($pack_id) {
            $where = [];
            $where['pack_id'] = $pack_id;
        } else {
            $where = [];
        }
		foreach($_POST as $key=>$value){
            $where['var'] = $key;
			if(D('Appapi_pack_config')->where($where)->find()){
				D('Appapi_pack_config')->where($where)->data(['value' => trim($value)])->save();
			}else{
			    if ($pack_id) {
			        $data = ['var' => $key, 'value' => trim($value),'pack_id' => $pack_id];
                } else {
			        $data = ['var' => $key, 'value' => trim($value)];
                }
				D('Appapi_pack_config')->data($data)->add();
			}
		}
		$this->success('保存成功');
	}
	
	public function mobileAppUpdate(){
		
		$parse_url = parse_url($this->config['site_url']);
		if($parse_url['host'] != 'www.group.com' && $parse_url['scheme'] != 'https'){
			$this->error('打包APP必须为https的，请联系客服索要教程先将网站升级为https。');
		}
        $pack_id = intval($_GET['pack_id']);
        $db_appapi_pack_config = D('Appapi_pack_config');
        if ($pack_id) {
            $where_pack_config = [
                'pack_id' => $pack_id
            ];
            $data = $db_appapi_pack_config->where($where_pack_config)->select();
        } elseif (isset($_GET['pack_id'])) {
            $data = [];
        } else {
            $data = $db_appapi_pack_config->select();
        }
		foreach ($data as $val) {
			$return[$val['var']] = $val['value'];
		}
		$this->assign($return);
		
		$pack_type = $_GET['type'];
		
		if($return[$pack_type . '_pack_id']){
			$app_pack = new app_pack();
			$resultArr = $app_pack->getPackInfo($return[$pack_type . '_pack_id'], $_GET['pack_id']);
			$this->assign('autoPackInfo', $resultArr);
		}
		
		$app_pack = new app_pack();
		$new_version_arr = $app_pack->getNewVersion();
		$this->assign('all_new_version', $new_version_arr);
		
		//如果有A4人脸识别设备需要展示可视对讲参数
		if($pack_type == 'android_plat' || $pack_type == 'ios_plat'){
			$has_a4_face_door = M('House_face_device')->where(['device_type' => '4'])->count();
			$this->assign('has_a4_face_door', $has_a4_face_door);
		}
	
		$this->display('mobileAppUpdate_' . $pack_type);
	}

    //店员App版本升级
    public function storestaffApp() {
        $config = D("Appapi_app_config");
        if (IS_POST) {
            $columns = array();
            $columns['staff_android_v'] = I("android_version", '');
            $columns['staff_android_url'] = htmlspecialchars_decode($_POST['android_download_url']);
            $columns['staff_android_vcode'] = I("android_version_code", '');
            $columns['staff_android_vdesc'] = I("android_version_desc", '');
			$columns['storestaff_android_package_name'] = I("storestaff_android_package_name", '');
			
            $columns['storestaff_ios_download_url'] = I("storestaff_ios_download_url", '');
            $columns['storestaff_ios_package_name'] =  I("storestaff_ios_package_name", '');
            $columns['storestaff_ios_version'] =  I("storestaff_ios_version", '');
            $columns['storestaff_ios_version_code'] =  I("storestaff_ios_version_code", '');
            $columns['storestaff_icp'] =  I("storestaff_icp", '');
            
            $i = 1;
            $columns['staff_privacy_policy'] = htmlspecialchars_decode(I("staff_privacy_policy", ''));
            $columns['staff_user_agreement'] = htmlspecialchars_decode(I("staff_user_agreement", ''));
			
			$android_version_code = $config->where(array('var'=>'staff_android_vcode'))->getField('value');
			if($_POST['uploadApk'] && $columns['staff_android_vcode'] <= $android_version_code){
				$this->error('安卓APP版本号必须比原来大，原来的版本号为 '.$android_version_code);
			}
			
			
            foreach ($columns as $key=>$val) {
                $result=$config->where(array('var'=>$key))->data(array('value'=>$val))->save();
                if($result === 0){
                    $date['var'] = $key;
                    $date['value'] = $val;
                    $date['sort'] = $i;
                    $i++;
                    $config->data($date)->add();
                }
            }
			$config->remove();
			
			$storestaff_app_voice_time = intval($_POST['storestaff_app_voice_time']);
			if($storestaff_app_voice_time < 1 || $storestaff_app_voice_time > 20){
				$this->error('店员语音播报次数，只能设置 1-20次');
			}
			if(isset($this->config['storestaff_app_voice_time'])){
				M('Config')->where(array('name'=>'storestaff_app_voice_time'))->data(array('value'=>$storestaff_app_voice_time))->save();
			}else{
				M('Config')->data(array('name'=>'storestaff_app_voice_time','value'=>$storestaff_app_voice_time,'info'=>'店员APP语音播报次数','status'=>'0'))->add();
			}
			//删除缓存
			D('Config')->clearDataCacheDir('system_config');
            $this->success("修改成功");
        } else {
            $data = $config->select();
            $return = array();
            foreach ($data as $val) {
                $return[$val['var']] = $val['value'];
            }
            $this->assign($return);
			
			$pack_data = D('Appapi_pack_config')->select();
			foreach ($pack_data as $val) {
				$pack_return[$val['var']] = $val['value'];
			}
			$this->assign('pack_return', $pack_return);
			
			$app_pack = new app_pack();
			$new_version_arr = $app_pack->getNewVersion();
			$this->assign('new_version', $new_version_arr['storestaff']);
			$this->assign('all_new_version', $new_version_arr);
			
			
            $this->display();
        }
    }

    //社区app

    public function VillageApp() {
        $config = D("Appapi_app_config");
        if (IS_POST) {
            $android_version = I("android_version", '');
            $android_download_url = htmlspecialchars_decode($_POST['android_download_url']);
            $android_version_desc = I("android_version_desc", '');
            $android_version_code = I("android_version_code", '');
            $village_android_icp=I("village_android_icp", '','trim');
            $columns = array();
            $columns['village_android_v'] = $android_version;
            $columns['village_android_url'] = $android_download_url;
            $columns['village_android_vcode'] = $android_version_code;
            $columns['village_android_vdesc'] = $android_version_desc;
            $columns['village_android_icp'] = $village_android_icp;
            $i = 1;
			
			if(!$columns['village_android_vcode']){
				$this->error('APP版本号未填写');
			}
			$android_version_code = $config->where(array('var'=>'village_android_vcode'))->getField('value');
			if($_POST['uploadApk'] && $columns['village_android_vcode'] <= $android_version_code){
				$this->error('APP版本号必须比原来大，原来的版本号为 '.$android_version_code);
			}
			
            foreach ($columns as $key=>$val) {
                $configArr=$config->where(array('var'=>$key))->find();
                if(!empty($configArr)){
                    $result=$config->where(array('var'=>$key))->data(array('value'=>$val))->save();
                }else{
                    $date=array();
                    $date['var'] = $key;
                    $date['value'] = $val;
                    $date['sort'] = $i;
                    $i++;
                    $config->data($date)->add();
                }
            }
			
			$config->remove();
            $this->success("修改成功");
        } else {
            $data = $config->select();
            $return = array();
            foreach ($data as $val) {
                $return[$val['var']] = $val['value'];
            }
            $this->assign($return);
            $this->display();
        }
    }
	
	public function VillageManergeApp() {
        $config = D("Appapi_app_config");
        if (IS_POST) {
            $columns = array();
			$columns['v_manerge_ios_url'] = trim($_POST['village_manager_ios_download_url']);
			$columns['village_manager_ios_version'] = trim($_POST['village_manager_ios_version']);
			$columns['village_manager_ios_version_code'] = trim($_POST['village_manager_ios_version_code']);
			
			
            $columns['v_manerge_android_v'] = trim($_POST['village_manager_android_version']);
            $columns['v_manerge_android_url'] = trim($_POST['village_manager_android_download_url']);
            $columns['v_manerge_android_vcode'] = trim($_POST['village_manager_android_version_code']);
            $columns['v_manerge_android_vdesc'] = trim($_POST['village_manager_android_version_desc']);
            $columns['village_privacy_policy'] = htmlspecialchars_decode(I("village_privacy_policy", ''));
            $columns['village_manager_icp'] = I("village_manager_icp", '');
            $i = 1;
			
			$android_version_code = $config->where(array('var'=>'v_manerge_android_vcode'))->getField('value');
			if($_POST['uploadApk'] && $columns['v_manerge_android_vcode'] <= $android_version_code){
				$this->error('APP版本号必须比原来大，原来的版本号为 '.$android_version_code);
			}
			
            foreach ($columns as $key=>$val) {
                $configArr=$config->where(array('var'=>$key))->find();
                if(!empty($configArr)){
                    $result=$config->where(array('var'=>$key))->data(array('value'=>$val))->save();
                }else{
                    $date=array();
                    $date['var'] = $key;
                    $date['value'] = $val;
                    $date['sort'] = $i;
                    $i++;
                    $config->data($date)->add();
                }
            }
			
			$config->remove();
            $this->success("修改成功");
        } else {
            $data = $config->select();
            $return = array();
            foreach ($data as $val) {
                $return[$val['var']] = $val['value'];
            }
			
			$return['village_manager_ios_version'] = $return['village_manager_ios_version'];
			$return['village_manager_ios_version_code'] = $return['village_manager_ios_version_code'];
			$return['village_manager_ios_download_url'] = $return['v_manerge_ios_url'];
			
			$return['village_manager_android_version'] = $return['v_manerge_android_v'];
			$return['village_manager_android_download_url'] = $return['v_manerge_android_url'];
			$return['village_manager_android_version_code'] = $return['v_manerge_android_vcode'];
			$return['village_manager_android_version_desc'] = $return['v_manerge_android_vdesc'];
            $return['village_manager_icp'] = $return['village_manager_icp'];
            $this->assign($return);
			
			$app_pack = new app_pack();
			$new_version_arr = $app_pack->getNewVersion();
			$this->assign('new_version', $new_version_arr['village_manager']);
			$this->assign('all_new_version', $new_version_arr);
			
            $this->display();
        }
    }
    
    //商家中心App版本升级
    public function merchantApp() {
        $config = D("Appapi_app_config");
        if (IS_POST) {
            $columns = array();
            $columns['mer_android_v'] = I("android_version", '');
            $columns['mer_android_url'] = htmlspecialchars_decode($_POST['android_download_url']);
            $columns['mer_android_vcode'] = I("android_version_code", '');
            $columns['mer_android_vdesc'] = I("android_version_desc", '');
			$columns['mer_android_package_name'] = I("mer_android_package_name", '');
			
            $columns['merchant_ios_version'] = I("merchant_ios_version", '');
            $columns['merchant_ios_version_code'] = I("merchant_ios_version_code", '');
            $columns['mer_ios_download_url'] = I("mer_ios_download_url", '');
            $columns['mer_ios_package_name'] = I("mer_ios_package_name", '');
            $columns['merchant_icp'] = I("merchant_icp", '');
            
            $i = 1;
            $columns['mer_privacy_policy'] = htmlspecialchars_decode(I("mer_privacy_policy", ''));
            $columns['mer_user_agreement'] = htmlspecialchars_decode(I("mer_user_agreement", ''));
			
			
			$android_version_code = $config->where(array('var'=>'mer_android_vcode'))->getField('value');
			if($_POST['uploadApk'] && $columns['mer_android_vcode'] <= $android_version_code){
				$this->error('APP版本号必须比原来大，原来的版本号为 '.$android_version_code);
			}
			
            foreach ($columns as $key=>$val) {
                $result=$config->where(array('var'=>$key))->data(array('value'=>$val))->save();
                if($result === 0){
                    $date['var'] = $key;
                    $date['value'] = $val;
                    $date['sort'] = $i;
                    $i++;
                    $config->data($date)->add();
                }
            }
			
			$config->remove();
            $this->success("修改成功");
        } else {
            $data = $config->select();
            $return = array();
            foreach ($data as $val) {
                $return[$val['var']] = $val['value'];
            }
            $this->assign($return);
			
			$pack_data = D('Appapi_pack_config')->select();
			foreach ($pack_data as $val) {
				$pack_return[$val['var']] = $val['value'];
			}
			$this->assign('pack_return', $pack_return);
			
			$app_pack = new app_pack();
			$new_version_arr = $app_pack->getNewVersion();
			$this->assign('new_version', $new_version_arr['merchant']);
			$this->assign('all_new_version', $new_version_arr);
			
            $this->display();
        }
    }

    //店员中心App版本升级
    public function deliverApp() {
        $config = D("Appapi_app_config");
        if (IS_POST) {
            $columns = array();
            $columns['deliver_ios_url'] = I("deliver_ios_url", '');
            $columns['deliver_ios_version'] = I("deliver_ios_version", '');
            $columns['deliver_ios_version_code'] = I("deliver_ios_version_code", '');
			
            $columns['deliver_android_version'] = I("android_version", '');
            $columns['deliver_android_url'] = htmlspecialchars_decode($_POST['android_download_url']);
            $columns['deliver_android_vcode'] = I("android_version_code", '');
            $columns['deliver_android_vdes'] = I("android_version_desc", '');
			$columns['deliver_android_package_name'] = I("android_package_name", '');
            $columns['deliver_icp'] = I("deliver_icp", '');
            $i = 1;

            $columns['deliver_privacy_policy'] = htmlspecialchars_decode(I("deliver_privacy_policy", ''));
            $columns['deliver_user_agreement'] = htmlspecialchars_decode(I("deliver_user_agreement", ''));
			

			$android_version_code = $config->where(array('var'=>'deliver_android_version'))->getField('value');
			if($_POST['uploadApk'] && $columns['deliver_android_version'] <= $android_version_code){
				$this->error('APP版本号必须比原来大，原来的版本号为 '.$android_version_code);
			}
			
            foreach ($columns as $key=>$val) {
                $result=$config->where(array('var'=>$key))->data(array('value'=>$val))->save();
                if($result === 0){
                    $date['var'] = $key;
                    $date['value'] = $val;
                    $date['sort'] = $i;
                    $i++;
                    $config->data($date)->add();
                }
            }
			
			$deliver_app_voice_time = intval($_POST['deliver_app_voice_time']);
			if($deliver_app_voice_time < 1 || $deliver_app_voice_time > 20){
				$this->error('配送员语音播报次数，只能设置 1-20次');
			}
			if(isset($this->config['deliver_app_voice_time'])){
				M('Config')->where(array('name'=>'deliver_app_voice_time'))->data(array('value'=>$deliver_app_voice_time))->save();
			}else{
				M('Config')->data(array('name'=>'deliver_app_voice_time','value'=>$deliver_app_voice_time,'info'=>'配送员APP语音播报次数','status'=>'0'))->add();
			}
			
			D('Config')->clearDataCacheDir('system_config');
			$config->remove();
            $this->success("修改成功");
        } else {
            $data = $config->select();
            $return = array();
            foreach ($data as $val) {
                $return[$val['var']] = $val['value'];
            }

            $this->assign($return);
			
			$pack_data = D('Appapi_pack_config')->select();
			foreach ($pack_data as $val) {
				$pack_return[$val['var']] = $val['value'];
			}
			$this->assign('pack_return', $pack_return);
			
			$app_pack = new app_pack();
			$new_version_arr = $app_pack->getNewVersion();
			$this->assign('new_version', $new_version_arr['deliver']);
			$this->assign('all_new_version', $new_version_arr);
			
            $this->display();
        }
    }

    //极光推送群发
    public function jpushGroup() {
        if (IS_POST) {
            $jpush_client       =   I('jpush_client',3);  // 客户端：1苹果  2安卓 3苹果、安卓
            $jpush_title        =   I('jpush_title');     // 标题：苹果专用
            $jpush_msg          =   I('jpush_msg');       // 发送内容
            $jpush_extra['url'] =   I('jpush_url');       // 跳转URL
            $device_id          =   I('device_id','all');
            if(empty($jpush_title)){
                $this->assign('标题不能为空');
            }else if(empty($jpush_msg)){
                $this->assign('内容不能为空');
            }
            if($device_id != 'all'){
                $device_id  =   explode("\n",$device_id);
                foreach($device_id as $v){
                    $tmp[]  =   str_replace('-','',$v);
                }
                $audience   =   array('tag'=>array($tmp));
            }else{
                $audience   =   $device_id;
            }
            $result = D('Group_order')->AuroraMass($jpush_client, $jpush_title, $jpush_msg, $jpush_extra,$audience);
            $this->success($result['msg']);
        }else{
            $this->display();
        }
    }

    public function ali_sms_header(){
        redirect(U('Config/index',array('galias'=>'ali_sms','header'=>'Sms/ali_sms_header')));
    }

    public function ali_sms(){
        $keyword = htmlspecialchars($_GET['keyword']);
        $condition['_string'] = " name like '%{$keyword}%' OR templateCode like '%{$keyword}%'";
        $count = M('Ali_sms_template')->where($condition)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $sms_list = M('Ali_sms_template')->where($condition)->order('id DESC')->limit($p->firstRow,$p->listRows)->select();
        $this->assign('pagebar',$p->show());
        $this->assign('sms_list',$sms_list);
        $this->display();
    }

    public function del_ali_sms_template(){
        M('Ali_sms_template')->where(array('id'=>$_POST['id']))->delete();
        $this->success('删除成功！');
    }

    public function add_ali_sms_template(){
        if(IS_POST){
			$res = false;
            if($_POST['id']){
                $res = M('Ali_sms_template')->where(array('id'=>$_POST['id']))->save($_POST);
            }else{
                $res = M('Ali_sms_template')->add($_POST);
            }

            if($res !== false){
                $this->success('编辑成功！');
            }else{
                $this->error('编辑失败！请重试~');
            }
        }else{
            if($_GET['id']){
                $res = M('Ali_sms_template')->where(array('id'=>$_GET['id']))->find();
                $this->assign('sms',$res);
            }
            $this->display();
        }
    }
}
?>