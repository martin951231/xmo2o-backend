<?php
/*
 * 首页
 *
 */

use app\community\model\service\Park\D3ShowScreenService;

class IndexAction extends BaseAction {
    public function index(){
		if($this->config['single_system_type'] == 'shop'){
			redirect($this->config['site_url'].'/shop/');
		}
		if($this->config['single_system_type'] == 'village_group'){
			redirect($this->config['site_url'].'/merchant.php');
		}
		if($this->config['single_system_type'] == 'community'){
			redirect($this->config['site_url'].'/admin.php');
		}
		if($this->config['system_type'] == 'village'){
			redirect($this->config['site_url'].'/shequ.php?g=House&c=Login&a=house_village');
		}

		//顶部广告
		$index_top_adver = D('Adver')->get_adver_by_key('index_top');
		$this->assign('index_top_adver',$index_top_adver);

		//热门搜索词
    	$search_hot_list = D('Search_hot')->get_list(12);
    	$this->assign('search_hot_list',$search_hot_list);

		//右侧广告
		$index_right_adver = D('Adver')->get_adver_by_key('index_right',3);
		$this->assign('index_right_adver',$index_right_adver);

		//所有分类 包含2级分类
		$all_category_list = D('Group_category')->get_category();
		$this->assign('all_category_list',$all_category_list);

		//热门二级分类
		$hot_group_category = D('Group_category')->get_hot_category();
		$this->assign('hot_group_category',$hot_group_category);

		//所有区域
		$all_area_list = D('Area')->get_area_list();
		$this->assign('all_area_list',$all_area_list);

		//热门商圈
		$hot_circle_list = D('Area')->get_hot_circle_list();
		$this->assign('hot_circle_list',$hot_circle_list);

		//最新团购
		$new_group_list = D('Group')->get_group_list('new',12);
		$this->assign('new_group_list',$new_group_list);

		//手动首页排序团购
		$index_sort_group_list = D('Group')->get_group_list('index_sort',12);
		$this->assign('index_sort_group_list',$index_sort_group_list);

		//首页大分类下的团购列表
		$index_group_list = D('Group')->get_category_arr_group_list($all_category_list,12);
		
		$this->assign('index_group_list',$index_group_list);

		//活动列表
		if($this->config['activity_open']){
			$now_activity = D('Extension_activity')->where(array('begin_time'=>array('lt',$_SERVER['REQUEST_TIME']),'end_time'=>array('gt',$_SERVER['REQUEST_TIME'])))->order('`activity_id` ASC')->find();
			if($now_activity){
				// list($time_array['j'],$time_array['h'],$time_array['m'],$time_array['s']) = explode(' ',date('j H i s',$now_activity['end_time'] - $_SERVER['REQUEST_TIME']));
				$time = $now_activity['end_time'] - $_SERVER['REQUEST_TIME'];
				$time_array['j'] = floor($time/86400);
				$time_array['h'] = floor($time%86400/3600);
				$time_array['m'] = floor($time%86400%3600/60);
				$time_array['s'] = floor($time%86400%60);
				// $activity_list = D('Extension_activity_list')->field('`pigcms_id`,`name`,`title`,`index_pic`,`part_count`')->where(array('activity_term'=>$now_activity['activity_id'],'status'=>'1','is_finish'=>'0','index_sort'=>array('neq','0')))->order('`index_sort` DESC,`pigcms_id` DESC')->limit(6)->select();
				$activity_list = D('')->field('`eac`.`pigcms_id`,`eac`.`name`,`eac`.`title`,`eac`.`index_pic`,`eac`.`part_count`')->table(array(C('DB_PREFIX').'extension_activity_list'=>'eac',C('DB_PREFIX').'merchant'=>'m'))->where("`eac`.activity_term='{$now_activity['activity_id']}' AND `eac`.`status`='1' AND `eac`.`is_finish`='0' AND `eac`.`index_sort`>0 AND `eac`.`mer_id`=`m`.`mer_id` AND `m`.`city_id`='{$this->config['now_city']}'")->order('`eac`.`index_sort` DESC,`eac`.`pigcms_id` DESC')->limit(6)->select();
				if(empty($activity_list)){
					unset($now_activity);
				}
				$this->assign('now_activity',$now_activity);
				$this->assign('time_array',$time_array);

				// $activity_list = D('Extension_activity_list')->field('`pigcms_id`,`name`,`title`,`index_pic`,`part_count`')->where(array('activity_term'=>$now_activity['activity_id'],'status'=>'1','index_sort'=>array('neq','0')))->order('`index_sort` DESC,`pigcms_id` DESC')->limit(6)->select();

				$extension_image_class = new extension_image();
				foreach($activity_list as &$value){
					$value['index_pic'] = file_domain().'/upload/activity/index_pic/'.$value['index_pic'];
					$value['url'] = $this->config['site_url'].'/activity/'.$value['pigcms_id'].'.html';
				}
				$this->assign('activity_list',$activity_list);
				$this->assign('activity_url',$this->config['site_url'].'/activity/');
			}

			//本站信息
			$index_site_info = S('index_site_info');
			if(empty($index_site_info)){
				$today_zero_time = strtotime(date('Y-m-d',$_SERVER['REQUEST_TIME']) . ' 00:00:00');
				$index_site_info = array();
				$index_site_info['user_count'] = D('User')->where(array('add_time'=>array('gt',$today_zero_time)))->count('uid');
				$index_site_info['merchant_count'] = D('Merchant')->where(array('reg_time'=>array('gt',$today_zero_time)))->count('mer_id');
				$index_site_info['merchant_store_count'] = D('Merchant_store')->where(array('last_time'=>array('gt',$today_zero_time)))->count('store_id');
				$index_site_info['group_count'] = D('Group')->where(array('last_time'=>array('gt',$today_zero_time)))->count('group_id');
				$index_site_info['meal_store_count'] = D('Merchant_store_meal')->where(array('last_time'=>array('gt',$today_zero_time)))->count('store_id');
			}
			$this->assign('index_site_info',$index_site_info);
		}
		//友情链接
		$flink_list = D('Flink')->get_flink_list();
		$this->assign('flink_list',$flink_list);

		$this->display();
    }

	public function group_index_sort(){
		$group_id = $_POST['id'];
		$database_index_group_hits = D('Index_group_hits_'.substr(dechex($group_id),-1));
		$data_index_group_hits['group_id'] = $group_id;
		$data_index_group_hits['ip']		= get_client_ip();
		if(!$database_index_group_hits->field('`group_id`')->where($data_index_group_hits)->find()){
			$condition_group['group_id'] = $group_id;
			if(M('Group')->where($condition_group)->setDec('index_sort')){
				$data_index_group_hits['time'] = $_SERVER['REQUEST_TIME'];
				$database_index_group_hits->data($data_index_group_hits)->add();
			}
		}
	}
	
	public function plat_wxapp(){
		$arr['appid'] = $this->config['pay_wxapp_appid'];
		$arr['paotui_appid'] = $this->config['pay_wxapp_paotui_appid'] ? $this->config['pay_wxapp_paotui_appid'] : '';
		$arr['shequn_appid'] = $this->config['pay_wxapp_group_appid'] ? $this->config['pay_wxapp_group_appid'] : '';
		$arr['house_manage_wxapp_appid'] = $this->config['house_manage_wxapp_appid'] ? $this->config['house_manage_wxapp_appid'] : '';
		$arr['house_manage_wxapp_name'] = $this->config['house_manage_wxapp_name'] ? $this->config['house_manage_wxapp_name'] : '';
		$arr['mobile_head_color'] = $this->config['mobile_head_color'] ? $this->config['mobile_head_color'] : '#06C1AE';

		// 打开其他小程序列表
		$arr['wxapp_appid_list'] = array();
		if ($this->config['pay_wxapp_paotui_appid']) {
		 	$arr['wxapp_appid_list'][] = $this->config['pay_wxapp_paotui_appid'];
		}
		if ($this->config['pay_wxapp_group_appid']) {
		 	$arr['wxapp_appid_list'][] = $this->config['pay_wxapp_group_appid'];
		}
		$wxapp_other_list = S('wxapp_other_list');
		if (!$wxapp_other_list) {
			$wxapp_other_list = M('Wxapp_other')->field('appid')->select();
            foreach ($wxapp_other_list as $value) {
                $wxapp_other_list_temp[] = $value['appid'];
            }
            $wxapp_other_list = $wxapp_other_list_temp;
            S('wxapp_other_list',$wxapp_other_list,3600);
		}
		if ($wxapp_other_list) {
			$arr['wxapp_appid_list'] = array_unique(array_merge($arr['wxapp_appid_list'],$wxapp_other_list));
			count($arr['wxapp_appid_list']) > 10 && $arr['wxapp_appid_list'] = array_slice($arr['wxapp_appid_list'],0,10);
		}

		$now_city_id = M('Config')->where(array('name'=>'now_city'))->find();
		$city_info = D('Area')->get_area_by_areaId($now_city_id['value']);
		$arr['city'] = $city_info['area_name'];
		$arr['wxappName'] = $this->config['site_name'];
		$arr['shopName'] = $this->config['shop_alias_name'];
		$arr['open_multilingual'] = $this->config['open_multilingual'] ? 'true' : 'false';
		$arr['system_type'] = $this->config['system_type'] ? : 'plat';
		
		if(empty($this->config['baidu_map_ak'])){
			$arr['baidu_map'] = '未填写';
		}else{
			$url = 'http://api.map.baidu.com/geoconv/v1/?coords=116.397470,39.908823&from=3&to=5&ak=' . $this->config['baidu_map_ak'] . '&output=json';
			import('ORG.Net.Http');
			$http = new Http();
			$result = $http->curlGet($url);
			if ($result) {
				$result = json_decode($result, true);
				if ($result['status'] == 0) {
					$arr['baidu_map'] = 'ok';
				}else{
					$arr['baidu_map'] = $result['message'];
				}
			}else{
				$arr['baidu_map'] = '百度地图请求异常';
			}
		}

        //$arr['tencent_map_key'] = $this->config['tencent_map_key'] ? $this->config['tencent_map_key'] : '';
		
		// if (!empty($this->config['wechat_appid']) && !empty($this->config['wechat_appsecret'])) {
			// $im = new im();
			// $im->create();
		// }
		
		echo json_encode($arr);
	}
    /**
    **好快充 接收接口挂起预警
     **/
    public function hkcApiError()
    {
        fdump_api([$_GET,'post'=>$_POST],'haokuaichong/hkcApiError',true);
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }
    /***
    ** 好快充设备离线通知
     ***/
    public function hkcEquOffLine(){
        fdump_api(['好快充设备离线通知',$_GET,'post'=>$_POST],'haokuaichong/hkcEquOffLine',true);
        $deviceId = isset($_GET['deviceId'])? trim($_GET['deviceId']):'';  //16进制设备号
        $projectId= isset($_GET['projectId'])? trim($_GET['projectId']):'';  //小区对接ID
        if($deviceId){
            $equipment_num = strtoupper($deviceId);
            $whereArr=array('equipment_num'=>$equipment_num,'type'=>3,'is_del'=>1);
            $pile_equipment=M('House_village_pile_equipment')->where($whereArr)->field('*')->find();
            if($pile_equipment){
                import('@.ORG.PileHaoKuaiChong');
                $pileHaoKuaiChongApi=new PileHaoKuaiChong($pile_equipment['village_id']);
                $apiRet=$pileHaoKuaiChongApi->getEquipmentDetail($deviceId,$pile_equipment['id']);
                fdump_api(['pile_equipment'=>$pile_equipment,'apiRet'=>$apiRet],'haokuaichong/hkcEquOffLine',true);
            }
        }
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }
    /**
     * 充电桩回调接口
     */
    public function notification()
    {
        fdump_api(['充电结束通知',$_GET,'post'=>$_POST],'haokuaichong/notification',true);
        $orderNum = $_GET['orderNo'];
        $orderNum=$orderNum ? trim($orderNum):'';
        $stopReason=$_GET['stopReason'];
        $stopReason=$stopReason ? trim($stopReason):'';
        $data = [
            'orderNo' => $orderNum,
            'time' => $_GET['time'],
            'power' => $_GET['power'],
            'stopReason' => $stopReason,
            'startType' => $_GET['startType'],
            'powerConsumption' => $_GET['powerConsumption'],
            'ports' => $_GET['ports'],
            'cards' => $_GET['cards'],
            'deviceId' => $_GET['deviceId'],
            'code' => $_GET['code'],
            'money' => $_GET['money'],
            'occupyMoney' => $_GET['occupyMoney'],
            'occupyTime' => $_GET['occupyTime'],
        ];
        if($orderNum){
            $house_village_pile_pay_orderDb=M('House_village_pile_pay_order');
            $whereArr=array('order_no'=>$orderNum);
            $pileOrder=$house_village_pile_pay_orderDb->where($whereArr)->field('*')->find();
            if(!empty($pileOrder) && $pileOrder['type']==3){
                if($pileOrder['status']==1){
                    //处理过了
                    echo json_encode(['code' => 200, 'msg' => '访问成功']);
                    exit;
                }
                $notificationTxt=array();
                if($pileOrder && $pileOrder['notificationTxt']){
                    $notificationTxt=json_decode($pileOrder['notificationTxt'],true);
                }
                if(empty($notificationTxt)){
                    $notificationTxt=array();
                }
                $notice_time=time();
                $notice_time_str=date('Y-m-d H:i:s');
                $data['notice_time']=$notice_time;
                $data['notice_time_str']=$notice_time_str;
                $notificationTxt['chargeEnd']=$data;
                $notificationTxtStr=json_encode($notificationTxt,JSON_UNESCAPED_UNICODE);
                $saveArr=array('notificationTxt'=>$notificationTxtStr,'end_time'=>$notice_time,'status'=>1);
                $saveArr['continued_time']=$data['time']>0 ? $data['time']:0;
                if($saveArr['continued_time']<1){
                    $start_time=$pileOrder['start_time']>0 ? $pileOrder['start_time']:$pileOrder['pay_time'];
                    $saveArr['continued_time']=$notice_time-$start_time;
                }
                $saveArr['end_power']=$data['power'];
                import('@.ORG.PileHaoKuaiChong');
                $pileHaoKuaiChongApi=new PileHaoKuaiChong($pileOrder['village_id']);
                $saveArr['end_result']=$pileHaoKuaiChongApi->getStopReason($data['stopReason']);
                if($data['powerConsumption']){
                    $saveArr['use_ele']=$data['powerConsumption'];
                }
                $house_village_pile_pay_orderDb->where(array('id'=>$pileOrder['id']))->data($saveArr)->save();
                $data['comefrom']='notification';
                $pileHaoKuaiChongApi->outSettlementRefund($pileOrder['id'],$data);
                echo json_encode(['code' => 200, 'msg' => '访问成功']);
                exit;
            }
        }
        $param=[
            'data'=>$data
        ];
        invoke_v20_service('\app\community\model\service\PileUserService/outSettlement', $param);
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }

    /**
     * 充电桩上电回调接口
     */
    public function chargeResult()
    {
        fdump_api(['接收上电结果',$_GET,'post'=>$_POST],'haokuaichong/chargeResult',true);
        $orderNum = isset($_GET['orderNo']) ? $_GET['orderNo']:'';
        $orderNum=trim($orderNum);
        $code = isset($_GET['code']) ? $_GET['code']:'';
        $code=trim($code);
        $msg = $_GET['msg'];
        $param = [
            'code' => $code,
            'orderNo' => $orderNum,
        ];
        if($orderNum){
            $house_village_pile_pay_orderDb=M('House_village_pile_pay_order');
            $whereArr=array('order_no'=>$orderNum);
            $pileOrder=$house_village_pile_pay_orderDb->where($whereArr)->field('*')->find();
            if(empty($pileOrder)){
                echo json_encode(['code' => 200, 'msg' => '访问成功了']);
                exit;
            }
            $notificationTxt=array();
            if($pileOrder && $pileOrder['notificationTxt']){
                $notificationTxt=json_decode($pileOrder['notificationTxt'],true);
            }
            if(empty($notificationTxt)){
                $notificationTxt=array();
            }
            $notice_time=time();
            $notice_time_str=date('Y-m-d H:i:s');
            $chargeStart=array('notice_time'=>$notice_time,'notice_time_str'=>$notice_time_str);
            $chargeStart['notice_code']=$code;
            $chargeStart['notice_msg']=$msg;
            $notificationTxt['chargeStart']=$chargeStart;
            $notificationTxtStr=json_encode($notificationTxt,JSON_UNESCAPED_UNICODE);
            $saveArr=array('notificationTxt'=>$notificationTxtStr);
            $is_open_charge=true;
            if($code==200 || $code=='200'){
                $saveArr['start_time']=$notice_time;
            }else{
                //充电时失败 退款
                $saveArr['status']=1;
                $saveArr['end_time']=$notice_time;
                $saveArr['end_result']=$msg;
                $is_open_charge=false;
            }
            M('House_village_pile_pay_order')->where(array('id'=>$pileOrder['id']))->data($saveArr)->save();
            if(!$is_open_charge){
                //充电时失败 退款
                import('@.ORG.PileHaoKuaiChong');
                $pileHaoKuaiChongApi=new PileHaoKuaiChong($pileOrder['village_id']);
                $data=array('comefrom'=>'notice_charge_fail','money'=>0,'fail_msg'=>$msg);
                $data['stopReason']=0;
                $data['powerConsumption']=0;
                $pileHaoKuaiChongApi->outSettlementRefund($pileOrder['id'],$data);
            }
        }
       // invoke_v20_service('\app\community\model\service\PileUserService/chargeResult', $param);
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }


    /**
     * 充电桩扫码跳转接口
     */
    public function quickuser()
    {
        $equipment_num=isset($_GET['device']) ? trim($_GET['device']) :'';
        $whereArr=array('equipment_num'=>$equipment_num,'is_del'=>'1');
        $equipment=M('House_village_pile_equipment')->where($whereArr)->find();
        $port=isset($_GET['port']) ? trim($_GET['port']) :'';
        $urlpath='pages/village/smartCharge/smartCharge?type=21&qcodeNum='.$equipment_num;
        if($equipment && $equipment['type']==3){
            $urlpath='pages/village/smartCharge/smartCharge?type=3&qcodeNum='.$equipment_num.'&village_id='.$equipment['village_id'];
        }
        //$url=get_base_url($urlpath);
        $site_url=C('config.site_url');
        if(C('config.config_site_url')){
            $site_url=C('config.config_site_url');
        }
        $url=$site_url.'/packapp/village/'.$urlpath;
        redirect($url);
    }


    /**
     * 刷卡获取电卡信息
     */
    public function getCard()
    {
        fdump_api(['接收上电结果',$_GET,$_POST],'getCard',true);
        $orderNum = $_GET['cardNo'];
        $param = [
            'cardNo' => $orderNum,
        ];
        invoke_v20_service('\app\community\model\service\PileUserService/getCard', $param);
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }

    /**
     * 刷卡请求上电
     */
    public function chargeRequest()
    {
        fdump_api(['接收上电结果',$_GET,$_POST],'chargeRequest',true);
        $param = [
            'cardNo' => $_GET['cardNo'],
            'deviceId' => $_GET['deviceId'],
            'port' => $_GET['port'],
        ];
        invoke_v20_service('\app\community\model\service\PileUserService/chargeRequest', $param);
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }


    /**
     * 刷卡请求上电
     */
    public function test()
    {
        $param = [
            'village_id' => 53,
        ];
        $res=invoke_v20_service('\app\community\model\service\HouseVillageThirdService/insertOrUpdateCommunity', $param);
        print_r($res);
        exit;
    }


    /**
     * 获取临时车进场信息回调接口
     */
    public function getTemporaryCarInfo()
    {
        fdump_api(['接收结果',$_GET,$_POST,file_get_contents("php://input"),json_decode(file_get_contents("php://input"),true)],'getTemporaryCarInfo',true);
        $data = file_get_contents("php://input");
        if (!empty($data)){
            $data=json_decode($data,true);
        }
        $param = [
            'data' => $data,
        ];
        invoke_v20_service('\app\community\model\service\HouseVillageParkingService/getTemporaryCarInfo', $param);
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }


    /**
     * D3停车系统http推送接口
     */
    public function getCarInfo()
    {
        fdump_api(['接收结果',$_GET,$_POST,file_get_contents("php://input"),json_decode(file_get_contents("php://input"),true)],'getCarInfo',true);
        $data = file_get_contents("php://input");
        if (!empty($data)){
            $data=json_decode($data,true);
        }

        $param = [
            'data' => $data,
        ];
        echo '{"Response_AlarmInfoPlate":{"info":"ok","content":"...","is_pay":"true"}}';
        exit;
    }

    /**
     * D3停车系统轮询心跳
     */
    public function receivedeviceinfo()
    {
        fdump_api(['轮询心跳',$_GET,$_POST],'devicemanagement',true);
        if (!empty($_POST)){
            $param = [
                'data' => $_POST,
            ];
            $res=invoke_v20_service('\app\community\model\service\HouseVillageParkingService/open_gate', $param);
            fdump_api(['推送车牌识别结果'=>$res],'devicemanagement1',true);
            if ($res['data']['retval']['data']==true){
                echo '{"Response_AlarmInfoPlate":{"info":"ok","content":"OK111","is_pay":"true"}}';
                exit;
            }
        }
        echo json_encode(['code' => 200, 'msg' => 'OK']);
        exit;
    }

    /**
     * D3停车系统推送车牌识别结果
     */
    public function plateresult1()
    {
        $data = file_get_contents("php://input");
        if (!empty($data)){
            $data=json_decode($data,true);
            $param = [
                'data' => $data,
            ];
            $aa=$data;
            unset($aa['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            fdump_api(['推送车牌识别结果'=>$aa],'receivedeviceinfo1111',true);
            $res=invoke_v20_service('\app\community\model\service\HouseVillageParkingService/add_park_info', $param);
            fdump_api(['推送车牌识别结果'=>$res],'receivedeviceinfo',true);
            if ($res['data']['retval']==true){
                echo '{"Response_AlarmInfoPlate":{"info":"ok","content":"OK111","is_pay":"true"}}';
                exit;
            }
            /*unset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            fdump_api(['推送车牌识别结果'=>$data],'receivedeviceinfo',true);
            if (in_array($data['AlarmInfoPlate']['result']['PlateResult']['license'],['京AF0236','沪A0023领','使166001'])){//沪A0023领   使166001
                echo '{"Response_AlarmInfoPlate":{"info":"ok","content":"OK111","is_pay":"true"}}';
                exit;
            }*/
        }
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }


    public function plateresult()
    {
        import('@.ORG.park.ParkShowScreen');
       /* $data = file_get_contents("php://input");
        if (!empty($data)){
            $data=json_decode($data,true);
            $param = [
                'data' => $data,
            ];
			
			$data1='AA5516640027000C01000100CDA3B3B5BDC9B7D1013CAF';*/
           // $base64DataText = base64_encode($data1);
            // $base64DataText ='QUE1NTE2NjQwMDI3MDAwQzAxMDAwMTAwQ0RBM0IzQjVCREM5QjdEMTAxM0NBRg==';
           // $base64DataText ='qlUWZAAnAAwBAAEAzaOztb3Jt9EBPK8=';
            $parkShowScreen = new ParkShowScreen();
           // $service_D3_show_service=new D3ShowScreenService();
            $txt='05';
          //  $zz=$parkShowScreen->showVoiceTxt($txt,1,'01');
            $zz=$parkShowScreen->showVoice($txt,'01');
            $base64DataText = $parkShowScreen->translation($zz);
            $base64DataTextLength = strlen($base64DataText);
            $serialData[] = [
                'serialChannel' => 0,
                'data' => $base64DataText,
                'dataLen' => $base64DataTextLength,
            ];
			$return  = [
                'Response_AlarmInfoPlate' => [
					'serialData' => $serialData,
                    'info' => 'ok',
                    'content' => 'OK111',
                    'is_pay' => 'true',
                ],
            ];
            $msg = json_encode($return,JSON_UNESCAPED_UNICODE);
            print_r([$zz,$msg]);exit;
            fdump_api(['获取推送的解析$msg'=>$msg,'data1' =>$data1,'return' =>$return],'receivedeviceinfo',true);
            echo $msg;exit();
            $aa=$data;
            unset($aa['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            fdump_api(['推送车牌识别结果'=>$aa],'receivedeviceinfo1111',true);
            $res=invoke_v20_service('\app\community\model\service\HouseVillageParkingService/add_park_info', $param);
            $serialData = [];
            // 包头
            $head = '0xAA';
            // 流水 00
            $order = '01';
            // 地址
            $address = '0X64';
            // 保留
            $retain = '02';
            //   0x27： 显示命令  0x22： 语音命令
            $typeVoice = '0x22';
            $typeText = '0x27';
            $dataVoice = '0x030x060x200x330x340x350x200x200xFA';
//            $dataVoice = strtoupper($dataVoice);
            $dataVoiceLength = strlen($dataVoice);
            $dataVoiceLength = $this->NumToHex($dataVoiceLength,2);
            $dataText = '0x040x070x010x410x320x300x300x300x330x73';
//            $dataText = strtoupper($dataText);
            $dataTextLength = strlen($dataText);
            $dataTextLength = $this->NumToHex($dataTextLength,2);
            $checkText = $this->packCrc16($dataText);
            $checkVoice = $this->packCrc16($dataVoice);
            $end = '0xAF';
           // $data1 = $head.$order.$address.$retain.$typeText.$dataTextLength.$dataText.$checkText.$end;
			$data1='AA5529640027000C02000100BCF5CBD9C2FDD0D07EF4AF';
            $base64DataText = base64_encode($data1);
            $base64DataTextLength = strlen($base64DataText);
            $serialData[] = [
                'serialChannel' => 0,
                'data' => $base64DataText,
                'dataLen' => $base64DataTextLength,
            ];

           // $data2 = $head.$order.$address.$retain.$typeVoice.$dataVoiceLength.$dataVoice.$checkVoice.$end;
			$data2='AA5511640027000C02000200BBA2C4EAB4F3BCAA7726AF';
            $base64DataVoice = base64_encode($data2);
            $base64DataVoiceLength = strlen($base64DataText);
//            $serialData[] = [
//                'serialChannel' => 0,
//                'data' => $base64DataVoice,
//                'dataLen' => $base64DataVoiceLength,
//            ];
            $return  = [
                'Response_AlarmInfoPlate' => [
                    'serialData' => $serialData,
                    'info' => 'ok',
                    'content' => 'OK111',
                    'is_pay' => 'true',
                ],
            ];
			$return  = [
                'Response_AlarmInfoPlate' => [
                    'info' => 'ok',
                    'content' => 'OK111',
                    'is_pay' => 'true',
                ],
            ];
            $msg = json_encode($return,JSON_UNESCAPED_UNICODE);
            fdump_api(['获取推送的解析$msg'=>$msg,'data1' =>$data1,'return' =>$return,'data2' =>$data2],'receivedeviceinfo',true);
            echo $msg;exit();

            $ss = $data;
            unset($ss['result']['PlateResult']['imageFragmentFile']);
            fdump_api(['获取推送的解析data'=>$ss,'推送车牌识别结果'=>$res],'receivedeviceinfo',true);
            if ($res['data']['retval']==true){
                echo '{"Response_AlarmInfoPlate":{"info":"ok","content":"OK111","is_pay":"true"}}';
                exit;
            }
            /*unset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            fdump_api(['推送车牌识别结果'=>$data],'receivedeviceinfo',true);
            if (in_array($data['AlarmInfoPlate']['result']['PlateResult']['license'],['京AF0236','沪A0023领','使166001'])){//沪A0023领   使166001
                echo '{"Response_AlarmInfoPlate":{"info":"ok","content":"OK111","is_pay":"true"}}';
                exit;
            }*/
       // }
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }

    public function twoToBase64($msg) {
        import('@.ORG.park.ParkShowScreen');
      //   $msg = "AA 55 A3 64 00 26 01 FF D4 C1 42 31 34 35 39 0C AF";
        $parkShowScreen = new ParkShowScreen($msg);
        $data = $parkShowScreen->translation();

      /*  dump($msg);
        dump($data);
        $msg1 = "AA 55 7F 64 00 26 00 0C 01 00 01 FF BE AF";
        $data1 = $parkShowScreen->translation($msg1);
        dump($msg1);
        dump($data1);*/
    }
    /**
     * D3停车系统推送端口触发信息
     */
    public function gio()
    {
        fdump_api(['推送端口触发信息',$_GET,$_POST,file_get_contents("php://input"),json_decode(file_get_contents("php://input"),true)],'gio',true);
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }

    /**
     * D3停车系统推送串口数据
     */
    public function serial()
    {
        fdump_api(['推送串口数据',$_GET,$_POST,file_get_contents("php://input"),json_decode(file_get_contents("php://input"),true)],'serial',true);
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }


    public function NumToHex($num, $len)
    {
        $num_hex = base_convert($num, 10, 16);
        if (strlen($num_hex) != $len) {
            $add = '';
            for ($i = 0; $i < $len - strlen($num_hex); $i++) {
                $add .= '0';
            }
            $num_hex = $add . $num_hex;
        }
        return $num_hex;
    }


    private function packCrc161($data) {
        $s = pack('H*', $data);
        $t = $this->crc161($s);
        $cs = sprintf('%02x%02x', $t%256, floor($t/256));
        return $cs;
    }

    private function crc161($string) {
        $crc = 0xFFFF;
        for ($x = 0; $x < strlen ($string); $x++) {
            $crc = $crc ^ ord($string[$x]);
            for ($y = 0; $y < 8; $y++) {
                if (($crc & 0x0001) == 0x0001) {
                    $crc = (($crc >> 1) ^ 0xA001);
                } else { $crc = $crc >> 1; }
            }
        }
        return $crc;
    }

    public function packCrc16()
    {
        $data = 'AA5502640027001402000100B9A7CFB2B7A2B2C6CDF2CAC2C8E7D2E2';
        $s = pack('H*', $data);
        $t = $this->crc166($s);
        $t = unpack("H*", $s . $t);
        // return $t[1];
        echo '<pre>';
        // iconv('UTF-8', 'GBK', $s);
        var_dump($t[1]);
    }

    public function crc166($string, $length = 0)
    {
        $auchCRCHi = array(0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81,
            0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0,
            0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01,
            0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81,
            0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0,
            0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01,
            0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81,
            0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0,
            0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01,
            0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81,
            0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0,
            0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01,
            0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81,
            0x40);
        $auchCRCLo = array(0x00, 0xC0, 0xC1, 0x01, 0xC3, 0x03, 0x02, 0xC2, 0xC6, 0x06, 0x07, 0xC7, 0x05, 0xC5, 0xC4,
            0x04, 0xCC, 0x0C, 0x0D, 0xCD, 0x0F, 0xCF, 0xCE, 0x0E, 0x0A, 0xCA, 0xCB, 0x0B, 0xC9, 0x09,
            0x08, 0xC8, 0xD8, 0x18, 0x19, 0xD9, 0x1B, 0xDB, 0xDA, 0x1A, 0x1E, 0xDE, 0xDF, 0x1F, 0xDD,
            0x1D, 0x1C, 0xDC, 0x14, 0xD4, 0xD5, 0x15, 0xD7, 0x17, 0x16, 0xD6, 0xD2, 0x12, 0x13, 0xD3,
            0x11, 0xD1, 0xD0, 0x10, 0xF0, 0x30, 0x31, 0xF1, 0x33, 0xF3, 0xF2, 0x32, 0x36, 0xF6, 0xF7,
            0x37, 0xF5, 0x35, 0x34, 0xF4, 0x3C, 0xFC, 0xFD, 0x3D, 0xFF, 0x3F, 0x3E, 0xFE, 0xFA, 0x3A,
            0x3B, 0xFB, 0x39, 0xF9, 0xF8, 0x38, 0x28, 0xE8, 0xE9, 0x29, 0xEB, 0x2B, 0x2A, 0xEA, 0xEE,
            0x2E, 0x2F, 0xEF, 0x2D, 0xED, 0xEC, 0x2C, 0xE4, 0x24, 0x25, 0xE5, 0x27, 0xE7, 0xE6, 0x26,
            0x22, 0xE2, 0xE3, 0x23, 0xE1, 0x21, 0x20, 0xE0, 0xA0, 0x60, 0x61, 0xA1, 0x63, 0xA3, 0xA2,
            0x62, 0x66, 0xA6, 0xA7, 0x67, 0xA5, 0x65, 0x64, 0xA4, 0x6C, 0xAC, 0xAD, 0x6D, 0xAF, 0x6F,
            0x6E, 0xAE, 0xAA, 0x6A, 0x6B, 0xAB, 0x69, 0xA9, 0xA8, 0x68, 0x78, 0xB8, 0xB9, 0x79, 0xBB,
            0x7B, 0x7A, 0xBA, 0xBE, 0x7E, 0x7F, 0xBF, 0x7D, 0xBD, 0xBC, 0x7C, 0xB4, 0x74, 0x75, 0xB5,
            0x77, 0xB7, 0xB6, 0x76, 0x72, 0xB2, 0xB3, 0x73, 0xB1, 0x71, 0x70, 0xB0, 0x50, 0x90, 0x91,
            0x51, 0x93, 0x53, 0x52, 0x92, 0x96, 0x56, 0x57, 0x97, 0x55, 0x95, 0x94, 0x54, 0x9C, 0x5C,
            0x5D, 0x9D, 0x5F, 0x9F, 0x9E, 0x5E, 0x5A, 0x9A, 0x9B, 0x5B, 0x99, 0x59, 0x58, 0x98, 0x88,
            0x48, 0x49, 0x89, 0x4B, 0x8B, 0x8A, 0x4A, 0x4E, 0x8E, 0x8F, 0x4F, 0x8D, 0x4D, 0x4C, 0x8C,
            0x44, 0x84, 0x85, 0x45, 0x87, 0x47, 0x46, 0x86, 0x82, 0x42, 0x43, 0x83, 0x41, 0x81, 0x80,
            0x40);
        $length = ($length <= 0 ? strlen($string) : $length);
        $uchCRCHi = 0xFF;
        $uchCRCLo = 0xFF;
        $uIndex = 0;
        for ($i = 0; $i < $length; $i++) {
            $uIndex = $uchCRCLo ^ ord(substr($string, $i, 1));
            $uchCRCLo = $uchCRCHi ^ $auchCRCHi[$uIndex];
            $uchCRCHi = $auchCRCLo[$uIndex];
        }
        return (chr($uchCRCHi) . chr($uchCRCLo));
    }

    public function propertyAllToNewCharge() {
        D('House_property')->propertyAllToNewCharge();
        echo '处理完成';
        exit;
    }
}
