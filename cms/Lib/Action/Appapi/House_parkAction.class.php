<?php
/**
 * Created by PhpStorm.
 * User: guojie
 * Date: 2020/1/14
 * Time: 9:22
 */
class House_parkAction  extends BaseAction
{
    public $union_id = '123';
    public $park_id = '50';
    public $local_id = 'channels_A1_A2_A3';
    public $key = '123456789';//key
    //sign = 676a88d906259f3bac3390b35c7a9d1e
    public $c_type_int_arr=['0'=>'0','1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'10','7'=>'6'];
    public $c_type_string_arr=['NFC刷卡进场'=>'0','iBeacon蓝牙'=>'1','手持POS机照牌'=>'2','通道扫牌'=>'3','通道照牌'=>'3','APP直付'=>'4','月卡用户'=>'5','无牌车扫码进出场'=>'6','手动抬竿'=>'7','自动（ETC、无感等）'=>'8','临时车登记入场'=>'9','个人工（遥控）进出场'=>'10'];
    public $c_type_string=['NFC刷卡进场','iBeacon蓝牙','手持POS机照牌','通道照牌','通道扫牌','APP直付','月卡用户','个人工（遥控）进出场','无牌车扫码进出场'];
    public $c_type_int=['0','1','2','3','4','5','6','7'];

    public function upload()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $token = $arrData['token'];
        $park_id = M('Park_system')->where(array('token'=>$token))->getField('park_id');
        if (!$park_id) {
            $park_id = $token;
        }

        if($arrData['service_name'] == 'login'){//初始化
            $this->upload_login();
        }elseif($arrData['service_name'] == 'inpark'){//车辆入场2.1
            $this->upload_inpark();
        }elseif($arrData['service_name'] == 'upload_carpic'){//上传图片
            $this->upload_carpic();
        }elseif($arrData['service_name'] == 'outpark'){//车辆出场电子支付 2.2
            $this->upload_outpark();
        }elseif($arrData['service_name'] == 'code_url'){//获取二维码地址
            $this->upload_code_url();
        }elseif($arrData['service_name'] == 'month_member_sync'){//月卡会员下发返回参数
            $this->upload_month_member_sync();
        }elseif($arrData['service_name'] == 'month_pay_sync'){//月卡续费记录下发返回参数
            $this->upload_month_pay_sync();
        }elseif($arrData['service_name'] == 'prepay_card_sync'){//储值卡同步下发返回参数
            $this->upload_prepay_card_sync();
        }elseif($arrData['service_name'] == 'prepay_card_trade_sync'){//储值卡充值记录下发返回参数
            $this->upload_prepay_card_trade_sync();
        }elseif($arrData['service_name'] == 'blackuser_sync_rsp'){//黑名单下发返回参数
            $this->upload_blackuser_sync_rsp();
        }elseif($arrData['service_name'] == 'query_price'){//查询订单返回参数
            $this->upload_query_price();
        }elseif($arrData['service_name'] == 'deliver_ticket'){//优惠券
            $this->upload_deliver_ticket();
        }elseif($arrData['service_name'] == 'gate_sync'){//
            $this->upload_gate_sync();
        }elseif($arrData['service_name'] == 'prepay_order'){//预付订单通知
            $this->upload_prepay_order();
        }elseif($arrData['service_name'] == 'query_prepay_card_balance'){//储值卡余额查询
            $this->upload_query_prepay_card_balance();
        }elseif($arrData['service_name'] == 'nolicence_in_park'){//无牌车
            $this->upload_nolicence_in_park();
        }elseif($arrData['service_name'] == 'nolicence_inpark'){//临时车禁止入场, 摄像机检测到车牌仍然需要上报到平台
            $this->upload_nolicence_inpark();
        }elseif($arrData['service_name'] == 'operate_gate'){//临时车禁止入场, 摄像机检测到车牌仍然需要上报到平台
            $this->upload_operate_gate();
        }elseif($arrData['service_name'] == 'get_car_info'){//平台下发查询车辆信息命令反馈
            $this->upload_get_car_info();
        }else{
            $data['state'] = 0;
            $data['errmsg'] = '没有参数返回!';
            echo json_encode($data);die;
        }
        $data['state'] = 1;
        $data['order_id'] = $arrData['data']['order_id'];
        $data['errmsg'] = '获得反馈成功!';
        $data['service_name'] = $arrData['service_name'];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();

    }

    public function get_sign(){
        $arr['union_id'] = $this->union_id;
        $arr['park_id'] = $this->park_id;
        $arr['local_id'] = $this->local_id;
        $jsonData = json_encode($arr);
        $sign = md5($jsonData.'key='.$this->key);
        return $sign;
    }


    protected function upload_login(){
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);

        $token = generate_password(25);
        $park_system = M('Park_system')->where(array('park_id'=>$arrData['data']['park_id']))->find();
        fdump_api(['停车系统登录'.__LINE__,$arrData,$park_system,$token],'park/upload_login_log',1);
        if($park_system){
            //M('Park_system')->where(array('park_id'=>$arrData['data']['park_id']))->data(array('token'=>$token))->save();
            $post_data['union_id'] = $arrData['data']['union_id'];
            $post_data['park_id'] = $arrData['data']['park_id'];
            $post_data['local_id'] = $arrData['data']['local_id'];
            $data['service_name'] = 'login';
            $data['sign'] = $arrData['sign'];
            $data['data'] = $post_data;
            $data['token'] = $park_system['token'];
            $data['errmsg'] = '登录成功!';
            fdump_api(['1登录信息返回'.__LINE__,$data,json_encode($data,JSON_UNESCAPED_UNICODE)],'park/upload_login_log',1);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }else{
            //没有,则返回数据给我
            $park_system['union_id'] = $arrData['data']['union_id'];
            $park_system['park_id'] = $arrData['data']['park_id'];
            $park_system['local_id'] = $arrData['data']['local_id'];
            $park_system['sign'] = $arrData['sign'];
            $park_system['token'] = $token;
            $insert_id = M('Park_system')->data($park_system)->add();
            if($insert_id){
                $post_data['union_id'] = $arrData['data']['union_id'];
                $post_data['park_id'] = $arrData['data']['park_id'];
                $post_data['local_id'] = $arrData['data']['local_id'];
                $data['service_name'] = 'login';
                $data['sign'] = $arrData['sign'];
                $data['data'] = $post_data;
                $data['token'] = $token;
                $data['errmsg'] = '登录成功!';
                fdump_api(['2登录信息返回'.__LINE__,$data,json_encode($data,JSON_UNESCAPED_UNICODE)],'park/upload_login_log',1);
                echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
            }
        }
    }

    protected function upload_inpark()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();
        $data = [];
        if(!$park_system){
            $data['state'] = 0;
            $data['errmsg'] = '无消息';
            $data['data'] = [];
            //fdump_api(['车辆入场系统未配置'.__LINE__,$arrData,$park_system,$data],'park/in_park_log',1);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
        $park_id = $park_system['park_id'];
        //fdump_api(['车辆入场'.__LINE__,$arrData,$park_system],'park/'.$park_id.'/in_park_log',1);
        $car_number = $arrData['data']['car_number'];
        $config_data = D('Config_data')->get_config_data();
        // 如果所有入场车辆和出场车辆均已完成迁移，不在记录数据进入in_park表格
        if (!isset($config_data['inParkMove']) || !$config_data['inParkMove'] || !isset($config_data['outParkMove']) || !$config_data['outParkMove']) {
            $in_park_tmp = M('In_park')->where(array('car_number'=>$car_number,'is_paid'=>0))->field('id')->order('id desc')->find();
            //fdump_api(['查询车辆是否已经存在入场记录'.__LINE__,$car_number,$in_park_tmp],'park/'.$park_id.'/in_park_log',1);
            if($in_park_tmp){
                // 将存在之前的该车入场记为已经出场并支付
                $save_id = M('In_park')->where(array('id'=>$in_park_tmp['id']))->data(['is_paid'=>1,'is_out'=>1])->save();
                //fdump_api(['将存在之前的该车入场记为已经出场并支付'.__LINE__,$save_id,$in_park_tmp],'park/'.$park_id.'/in_park_log',1);
            }

            if (isset($arrData['data']['c_type'])&&!empty($arrData['data']['c_type'])){
                if (in_array($arrData['data']['c_type'],$this->c_type_string)){
                    $arrData['data']['c_type']=$this->c_type_string_arr[$arrData['data']['c_type']];
                }elseif(in_array($arrData['data']['c_type'],$this->c_type_int)){
                    $arrData['data']['c_type']=$this->c_type_int_arr[$arrData['data']['c_type']];
                }
            }
            $in_park = [];
            $in_park['car_number'] = $car_number;
            $in_park['c_type'] = $arrData['data']['c_type'] ? $arrData['data']['c_type']:'' ;
            $in_park['in_time'] = $arrData['data']['in_time'];
            $in_park['order_id'] = $arrData['data']['order_id'];
            $in_park['empty_plot'] = $arrData['data']['empty_plot'];
            $in_park['token'] = $arrData['token'];
            $in_park['sign'] = $arrData['sign'];
            $in_park['in_channel_id'] = $park_system['local_id'];//通道号
            $in_park['park_id'] = $park_system['park_id'];//车场编号17317816787
            $in_park['service_name'] = $arrData['service_name'];
            $ss=S($in_park['car_number'].$in_park['in_channel_id'].$in_park['park_id']);
            $starttime=time()-10;
            $endtime=time()+10;
            if ($starttime<$ss&&$ss<$endtime){
                $insert_id=1;
            }else{
                $insert_id = M('In_park')->data($in_park)->add();
                S($in_park['car_number'].$in_park['in_channel_id'].$in_park['park_id'],time());
            }
        }
        $accessType = 1;//	通行类型 1进  2出
        // 车场编号为小区 查询小区相关信息
        $village_info = M('House_village')->where(['village_id'=> $park_id])->field('village_id,village_name')->find();
        // 查询对应车辆的现有绑定情况
        $where_car = [];
        $where_car['village_id'] = $park_id;
        $where_car['_string'] = "concat(`province`,'',`car_number`)='{$car_number}'";
        $infos_car_info = D('House_village_parking_car')->where($where_car)->field('car_id,car_position_id,car_user_name,car_user_phone,car_stop_num')->find();
        if ($infos_car_info && $infos_car_info['car_id'] && floatval($arrData['data']['total_price']) > 0) {
            $bind_info = D('House_village_bind_car')->where(['car_id' => $infos_car_info['car_id']])->find();
            fdump_api(['house_park对应车辆储值余额变动'=>$car_number,'arrData' => $arrData,'total_price' => $arrData['data']['total_price']], 'park_temp/stored_balance_' . $car_number, 1);
            D('House_village_parking_car')->where($where_car)->save(['stored_balance'=>$arrData['data']['total_price']]);
        }
        $channel_number = $arrData['data']['in_channel_id']??'';
        $channel_id = 0;
        $channel_name = '';
        if($channel_number){
            //查询入场通道信息
            $park_passage_info = M('Park_passage')->where(['channel_number'=>$channel_number,'village_id'=>$park_id])->field('id,passage_name')->find();
            $channel_id = $park_passage_info['id']??0;
            $channel_name = $park_passage_info['passage_name']??'';
        }
        // 记录车辆进入信息
        $car_access_record = [];
        $car_access_record['business_type'] = 0;
        $car_access_record['business_id'] = $park_id;
        $car_access_record['car_number'] = $car_number;
        $car_access_record['accessType'] = $accessType;
        $car_access_record['accessTime'] = $arrData['data']['in_time']?$arrData['data']['in_time']:time();
        $car_access_record['accessMode'] = $arrData['data']['c_type'] ? $arrData['data']['c_type']:'3';
        $car_access_record['park_sys_type'] = 'A1';
        $car_access_record['is_out'] = 0;
        $car_access_record['park_id'] = $park_id;
        $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
        $car_access_record['empty_plot'] = $arrData['data']['empty_plot']?$arrData['data']['empty_plot']:'';
        $car_access_record['token'] = $arrData['token']?$arrData['token']:'';
        $car_access_record['sign'] = $arrData['sign']?$arrData['sign']:'';
        $car_access_record['uid'] = ($bind_info&&$bind_info['uid'])?$bind_info['uid']:'';
        $car_access_record['car_id'] = ($infos_car_info&&$infos_car_info['car_id'])?$infos_car_info['car_id']:'';
        $car_access_record['bind_id'] = ($bind_info&&$bind_info['user_id'])?$bind_info['user_id']:'';
        $car_access_record['user_name'] = ($infos_car_info&&$infos_car_info['car_user_name'])?$infos_car_info['car_user_name']:'';
        $car_access_record['user_phone'] = ($infos_car_info&&$infos_car_info['car_user_phone'])?$infos_car_info['car_user_phone']:'';
        $car_access_record['order_id'] = $arrData['data']['order_id'] ? $arrData['data']['order_id']:'';
        $car_access_record['service_name'] = $arrData['service_name'] ? $arrData['service_name']:'';
        $car_access_record['update_time'] = time();
        $car_access_record['channel_id'] = $channel_id ?:0;
        $car_access_record['channel_number'] = $channel_number ?:'';
        $car_access_record['channel_name'] = $channel_name ?:'';
        if ($arrData['data']['total_price']) {
            fdump_api(['house_park对应车辆储值余额变动'=>$car_number,'arrData' => $arrData,'total_price' => $arrData['data']['total_price']], 'park_temp/stored_balance_' . $car_number, 1);
            $car_access_record['stored_balance'] = $arrData['data']['total_price'];
        }
        if ($insert_id) {
            $car_access_record['from_id'] = $insert_id;
        }
        $record_id = M('House_village_car_access_record')->add($car_access_record);
        if (!$record_id) {
            fdump_api(['添加车辆入场记录失败'.__LINE__,$car_number,$in_park,$car_access_record,M('House_village_car_access_record')->getDbError()],'park/'.$park_id.'/in_park_log',1);
        }
        /*
        $updateSave=array('is_out'=>1,'update_time'=>time(),'flag_status'=>'A1_in_recorded_out');
        $whereArr=array('park_id'=>$park_id,'car_number'=>$car_number,'record_id'=>array('lt',$record_id),'is_out'=>0);
        M('House_village_car_access_record')->where($whereArr)->save($updateSave);
        */
        $data['state'] = 1;
        $data['order_id'] = $arrData['data']['order_id'];
        $data['errmsg'] = '订单上传成功!';
        $data['service_name'] = 'inpark';
        fdump_api(['订单上传成功'.__LINE__,$data,$in_park,M('In_park')->getDbError()],'park/'.$park_id.'/in_park_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    protected function upload_nolicence_inpark()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();
        $data = [];
        if(!$park_system){
            $data['state'] = 0;
            $data['errmsg'] = '无消息';
            $data['data'] = [];
            fdump_api(['车辆入场系统未配置'.__LINE__,$arrData,$park_system,$data],'park/nolicence_inpark_log',1);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
        $park_id = $park_system['park_id'];
        fdump_api(['车辆入场'.__LINE__,$arrData,$park_system],'park/'.$park_id.'/nolicence_inpark_log',1);
        $car_number = $arrData['data']['car_number'];
        // 记录车辆进入信息
        $park_log_data = [];
        $park_log_data['car_number'] = $car_number;
        $park_log_data['channel_id'] = $arrData['data']['in_channel'];
        $park_log_data['park_type'] = 1;
        $park_log_data['village_id'] = $park_id;
        $park_log_data['park_sys_type'] = 'A1';
        $park_log_data['add_time'] = time();
        $record_id = M('Park_plateresult_log')->add($park_log_data);
        if (!$record_id) {
            fdump_api(['添加车辆记录失败'.__LINE__,$car_number,$park_log_data,M('Park_plateresult_log')->getDbError()],'park/'.$park_id.'/nolicence_inpark_log',1);
        }
        $data['state'] = 1;
        $data['order_id'] = $arrData['data']['order_id'];
        $data['errmsg'] = '订单上传成功!';
        $data['service_name'] = 'nolicence_inpark';
        fdump_api(['订单上传成功'.__LINE__,$data,M('Park_plateresult_log')->getDbError()],'park/'.$park_id.'/nolicence_inpark_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    protected function upload_get_car_info()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);

        $res = M('Park_total_record')->where(array('msg_id'=>$arrData['msg_id'],'status'=>2))->order('id desc')->find();
        $park_id = $res['park_id'];
        fdump_api(['upload_get_car_info:'=>__LINE__,'jsonData'=>$jsonData],"park/{$park_id}/upload_get_car_info",1);

        if($arrData['service_name'] == 'get_car_info'){
            fdump_api(['upload_get_car_info:'=>__LINE__,'res'=>$res],"park/{$park_id}/upload_get_car_info_log",1);
            if(($arrData['state'] == 1) && $res){
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                $where_car = [];
                $where_car['village_id'] = $park_id;
                $where_car['_string'] = "concat(`province`,'',`car_number`)='{$arrData['car_number']}'";
                $car_info = D('House_village_parking_car')->where($where_car)->field('*')->find();
                if (!empty($car_info)){
                    $car_data=[];
                    $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_value;
                    $car_data['parking_car_type']= $parking_car_type_arr[$arrData['car_type']];
                    $car_data['end_time']= $arrData['end_time'];
                    if (isset($arrData['total_price'])) {
                        fdump_api(['house_park对应车辆储值余额变动'=>$arrData['car_number'],'arrData' => $arrData,'total_price' => $arrData['total_price']], 'park_temp/stored_balance_' . $arrData['car_number'], 1);
                        $car_data['stored_balance']= $arrData['total_price'];
                    }
                    D('House_village_parking_car')->where($where_car)->data($car_data)->save();
                }
            }else{
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
            }
            fdump_api(['upload_get_car_info:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_get_car_info_log",1);
            M('Park_total_record')->where($where_record)->delete();
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'get_car_info';
        fdump_api(['记录成功'.__LINE__,$data],'park/'.$res['park_id'].'/upload_get_car_info_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function upload_carpic()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();
        $data = [];
        if(!$park_system){
            $data['state'] = 0;
            $data['errmsg'] = '无消息';
            $data['data'] = [];
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
//        $carPicArr = $arrData;
//        if (isset($carPicArr['data']['content'])) {
//            unset($carPicArr['data']['content']);
//        }
//        fdump_api(['index' => 'upload_carpic','carPicArr' => $carPicArr],'park/'.$park_id.'/upload_carpic_log',1);
        $park_id = $park_system['park_id'];
        $config_data = D('Config_data')->get_config_data();
        $where_order = [];
        $where_order['order_id'] = $arrData['data']['order_id'];
        if($where_order['order_id'] && $arrData['data']['park_order_type'] == 'in'){
            // 如果所有入场车辆和出场车辆均已完成迁移，不在记录数据进入in_park表格
            if (!isset($config_data['inParkMove']) || !$config_data['inParkMove'] || !isset($config_data['outParkMove']) || !$config_data['outParkMove']) {
                $in_park = M('In_park')->where($where_order)->find();
                if ($in_park&&$arrData['data']['content']) {
                    $accessImage = D('House_village_parking_car')->base64ToLocalImg($arrData['data']['content']);
                    $where_order = array('order_id'=>$arrData['data']['order_id']);
                    if ($accessImage) {
                        fdump_api(['入场图片'.__LINE__,$in_park,$accessImage],'park/'.$park_id.'/upload_carpic_log',1);
                        $saveData = [
                            'content_path' => $accessImage,
                            'update_time' => time(),
                        ];
                        M('In_park')->where($where_order)->save($saveData);
                    }
                }
            }
            // 记录进场捕捉照片,转存本地
            $where_order['accessType'] = 1;
            $record_id = M('House_village_car_access_record')->where($where_order)->getField('record_id');
            if($record_id && $arrData['data']['content']){
                $where_order['record_id']=$record_id;
                if (!$accessImage) {
                    $accessImage = D('House_village_parking_car')->base64ToLocalImg($arrData['data']['content']);
                }
                if ($accessImage) {
                    $saveData = [
                        'accessImage' => $accessImage,
                        'update_time' => time(),
                    ];
                    M('House_village_car_access_record')->where($where_order)->save($saveData);
                }
            }
        }elseif($where_order['order_id'] && $arrData['data']['park_order_type'] == 'out'){
            // 如果所有入场车辆和出场车辆均已完成迁移，不在记录数据进入in_park表格
            if (!isset($config_data['inParkMove']) || !$config_data['inParkMove'] || !isset($config_data['outParkMove']) || !$config_data['outParkMove']) {
                $out_park = M('Out_park')->where($where_order)->find();
                if($out_park){
                    $accessImage = D('House_village_parking_car')->base64ToLocalImg($arrData['data']['content']);
                    $where_order = array('order_id'=>$arrData['data']['order_id']);
                    if ($accessImage && $where_order['order_id']) {
                        fdump_api(['出场图片'.__LINE__,$out_park,$accessImage],'park/'.$park_id.'/upload_carpic_log',1);
                        $saveData = [
                            'content_path' => $accessImage,
                            'update_time' => time(),
                        ];
                        M('Out_park')->where($where_order)->save($saveData);
                    }
                }
            }
            // 记录出场捕捉照片,转存本地
            $where_order['accessType'] = 2;
            $record_id = M('House_village_car_access_record')->where($where_order)->getField('record_id');
            if( $record_id && $arrData['data']['content']){
                $where_order['record_id']=$record_id;
                if (!$accessImage) {
                    $accessImage = D('House_village_parking_car')->base64ToLocalImg($arrData['data']['content']);
                }
                if ($accessImage) {
                    $saveData = [
                        'accessImage' => $accessImage,
                        'update_time' => time(),
                    ];
                    M('House_village_car_access_record')->where($where_order)->save($saveData);
                }
            }
        }
        $data['state'] = 1;
        $data['errmsg'] = '上传成功';
        $data['order_id'] = $arrData['data']['order_id'];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function upload_outpark()
    {
        //cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();
        $data = [];
        $park_id = $park_system['park_id'];
        fdump_api(['车辆出场'.__LINE__,$jsonData,$park_system],'park/'.$park_id.'/upload_outpark_log',1);
        if(!$park_system){
            $data['state'] = 0;
            $data['errmsg'] = '无消息';
            $data['data'] = [];
            fdump_api(['对应车辆系统未配置'.__LINE__,$data],'park/'.$park_id.'/upload_outpark_log',1);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
        $config_data = D('Config_data')->get_config_data();
        // 如果所有入场车辆和出场车辆均已完成迁移，不在记录数据进入in_park表格
        $outParkData = $arrData['data'];
        $car_number = $outParkData['car_number'];
        $order_id = $outParkData['order_id'] ? $outParkData['order_id'] : '';
        $nowtime = date("ymdHis");
        $outParks = false;
        if (!isset($config_data['inParkMove']) || !$config_data['inParkMove'] || !isset($config_data['outParkMove']) || !$config_data['outParkMove']) {
            $outParks = true;
            $where_data = $outParkData;
            $where_in = [];
            if ($car_number) {
                $where_in['car_number'] = $car_number;
                $order_id && $where_in['order_id'] = $order_id;
                isset($outParkData['in_time']) && $outParkData['in_time'] && $where_in['in_time'] = $outParkData['in_time'];
            } elseif ($order_id) {
                $where_in['order_id'] = $order_id;
            } elseif($outParkData['in_time']) {
                $where_in['in_time'] = $outParkData['in_time'];
            }
            $where_in['park_id'] = $park_id;
            $where_in['is_out'] = 0;
            $in_park = M('In_park')->where($where_in)->order('id DESC')->find();
            !$car_number && $car_number = isset($in_park['car_number']) && $in_park['car_number'] ? trim($in_park['car_number']) : '';
            if(empty($in_park)){
                fdump_api(['对应车辆无入场记录'.__LINE__,$where_in,M('In_park')->_sql(),$data],'park/'.$park_id.'/upload_outpark_log',1);
                $out_park = [];
                $out_park['service_name'] = $arrData['service_name'];
                $out_park['token'] = $arrData['token'];
                $out_park['sign'] = $arrData['sign'];
                $out_park['park_id'] = $park_id;
                $out_park['car_number'] = $car_number;
                $out_park['in_time'] = $outParkData['in_time'];
                $out_park['out_time'] = $where_data['out_time'];
                $out_park['total'] = $where_data['total'] ? $where_data['total'] : 0;
                $out_park['order_id'] = $where_data['order_id'] ? $where_data['order_id'] : '';
                $out_park['empty_plot'] = $where_data['empty_plot'] ? $where_data['empty_plot'] : '';
                $out_park['pay_type'] = $where_data['pay_type'] ? $where_data['pay_type'] : '';
                $out_park['auth_code'] = $where_data['auth_code'] ? $where_data['auth_code'] : '';
                $insert_id = M('Out_park')->data($out_park)->add();
                fdump_api(['单独记录无入场车辆的出场记录'.__LINE__,$insert_id,$out_park,M('Out_park')->getDbError()],'park/'.$park_id.'/upload_outpark_log',1);
            }else{
                if($in_park['in_time'] && $outParkData['in_time'] && $in_park['in_time'] != $outParkData['in_time']){
                    // 两个入场时间不一致 以入场记录为准
                    $in_time = $in_park['in_time'];
                    fdump_api(['两个入场时间不一致'.__LINE__,$arrData,$in_park],'park/'.$park_id.'/upload_outpark_log',1);
                } else {
                    $in_time = $outParkData['in_time'];
                }
                $out_park = [];
                $out_park['service_name'] = $arrData['service_name'];
                $out_park['token'] = $arrData['token'];
                $out_park['sign'] = $arrData['sign'];
                $out_park['park_id'] = $park_id;
                $out_park['car_number'] = $car_number;
                $out_park['in_time'] = $in_time;
                $out_park['out_time'] = $where_data['out_time'];
                $out_park['total'] = $where_data['total'] ? $where_data['total'] : 0;
                $out_park['order_id'] = $where_data['order_id'] ? $where_data['order_id'] : '';
                $out_park['empty_plot'] = $where_data['empty_plot'] ? $where_data['empty_plot'] : '';
                $out_park['pay_type'] = $where_data['pay_type'] ? $where_data['pay_type'] : '';
                $out_park['auth_code'] = $where_data['auth_code'] ? $where_data['auth_code'] : '';
                $insert_id = M('Out_park')->data($out_park)->add();
            }
        }
        $accessType = 2;//通行类型 1进  2出
        $whereRecordIn = [];
        if ($car_number) {
            $whereRecordIn['car_number'] = $car_number;
        }
        if ($order_id) {
            $whereRecordIn['order_id'] = $order_id;
        }
        $whereRecordIn['park_id'] = $park_id;
        $whereRecordIn['accessType'] = 1;
        $inRecordPark = M('House_village_car_access_record')->where($whereRecordIn)->order('record_id DESC')->find();
        if ($inRecordPark && $inRecordPark['park_name']) {
            $park_name = $inRecordPark['park_name'];
        } else {
            // 车场编号为小区 查询小区相关信息
            $village_info = M('House_village')->where(['village_id'=> $park_id])->field('village_id,village_name')->find();
            $park_name = $village_info['village_name']?$village_info['village_name']:'';
        }
        $uid = 0;
        if ($inRecordPark['uid']) {
            $uid = $inRecordPark['uid'];
        }
        $car_id = 0;
        if ($inRecordPark['car_id']) {
            $car_id = $inRecordPark['car_id'];
        }
        $bind_id = 0;
        if ($inRecordPark['bind_id']) {
            $bind_id = $inRecordPark['bind_id'];
        }
        $user_name = '';
        if ($inRecordPark['user_name']) {
            $user_name = $inRecordPark['user_name'];
        }
        $user_phone = '';
        if ($inRecordPark['user_phone']) {
            $user_phone = $inRecordPark['user_phone'];
        }
        if (isset($arrData['balance']) && floatval($arrData['balance']) > 99999999) {
            unset($arrData['balance']);
        }
        // 查询对应车辆的现有绑定情况
        if (!$uid || !$car_id || !$bind_id || !$user_name || !$user_phone) {
            $where_car = [];
            $where_car['village_id'] = $park_id;
            $where_car['_string'] = "concat(`province`,'',`car_number`)='{$car_number}'";
            $infos_car_info = D('House_village_parking_car')->where($where_car)->field('car_id,car_position_id,car_user_name,car_user_phone,car_stop_num')->find();
            if ($infos_car_info && $infos_car_info['car_id'] && isset($outParkData['balance'])) {
                $bind_info = D('House_village_bind_car')->where(['car_id' => $infos_car_info['car_id']])->find();
                fdump_api(['house_park对应车辆储值余额变动'=>$car_number,'arrData' => $arrData,'balance' => $arrData['balance']], 'park_temp/stored_balance_' .$car_number, 1);
                D('House_village_parking_car')->where($where_car)->save(['stored_balance'=>$outParkData['balance']]);
            }
            $uid = ($bind_info&&$bind_info['uid'])?$bind_info['uid']:'';
            $car_id = ($infos_car_info&&$infos_car_info['car_id'])?$infos_car_info['car_id']:'';
            $bind_id = ($bind_info&&$bind_info['user_id'])?$bind_info['user_id']:'';
            $user_name = ($infos_car_info&&$infos_car_info['car_user_name'])?$infos_car_info['car_user_name']:'';
            $user_phone = ($infos_car_info&&$infos_car_info['car_user_phone'])?$infos_car_info['car_user_phone']:'';
        }
        if (isset($outParkData['c_type'])&&!empty($outParkData['c_type'])){
            if (in_array($outParkData['c_type'],$this->c_type_string)){
                $outParkData['c_type']=$this->c_type_string_arr[$outParkData['c_type']];
            }elseif(in_array($outParkData['c_type'],$this->c_type_int)){
                $outParkData['c_type']=$this->c_type_int_arr[$outParkData['c_type']];
            }
        }
        if (isset($outParkData['car_type'])){
            if(stripos($outParkData['car_type'],'储值车')!=false){
                $car_type='storedCar';
            }elseif(stripos($outParkData['car_type'],'月租车')!=false){
                $car_type='monthCar';
                $outParkData['c_type']=5;
            }else{
                $car_type='temporaryCar';
                $outParkData['c_type']=3;
            }
        }else{
            $car_type=''; 
        }
        $channel_number = $outParkData['out_channel_id']??'';
        $channel_id = 0;
        $channel_name = '';
        if($channel_number){
            //查询入场通道信息
            $park_passage_info = M('Park_passage')->where(['channel_number'=>$channel_number,'village_id'=>$park_id])->field('id,passage_name')->find();
            $channel_id = $park_passage_info['id']??0;
            $channel_name = $park_passage_info['passage_name']??'';
        }
        // 记录车辆出场信息
        $car_access_record = [];
        $car_access_record['business_type'] = 0;
        $car_access_record['business_id'] = $park_id;
        $car_access_record['car_number'] = $car_number;
        $car_access_record['accessType'] = $accessType;
        $car_access_record['accessTime'] = $outParkData['out_time']?$outParkData['out_time']:time();
        $car_access_record['accessMode'] = $outParkData['c_type'] ? $outParkData['c_type']:'3';
        $car_access_record['park_sys_type'] = 'A1';
        $car_access_record['is_out'] = 1;
        $car_access_record['car_type'] = $car_type;
        $car_access_record['park_id'] = $park_id;
        $car_access_record['park_name'] = $park_name;
        $car_access_record['empty_plot'] = $outParkData['empty_plot']?$outParkData['empty_plot']:'';
        $car_access_record['token'] = $token?$token:'';
        $car_access_record['sign'] = $arrData['sign']?$arrData['sign']:'';
        $car_access_record['uid'] = $uid;
        $car_access_record['car_id'] = $car_id;
        $car_access_record['bind_id'] = $bind_id;
        $car_access_record['user_name'] = $user_name;
        $car_access_record['user_phone'] = $user_phone;
        $car_access_record['order_id'] = $order_id;
        $car_access_record['service_name'] = $arrData['service_name'] ? $arrData['service_name']:'';
        $car_access_record['total'] = $outParkData['total'] ? $outParkData['total'] : 0;
        // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
        // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
        //交易流水号(pay_type为wallet、scancode、sweepcode必传)
        $car_access_record['pay_type'] = $outParkData['pay_type'] ? $outParkData['pay_type'] : '';
        $car_access_record['auth_code'] = $outParkData['auth_code'] ? $outParkData['auth_code'] : '';
        $car_access_record['update_time'] = time();
        $car_access_record['channel_id'] = $channel_id ?:0;
        $car_access_record['channel_number'] = $channel_number ?:'';
        $car_access_record['channel_name'] = $channel_name ?:'';
        if ($outParkData['trade_no']) {
            $car_access_record['trade_no'] = $outParkData['trade_no'];
        } else {
            $car_access_record['trade_no'] = $nowtime . rand(10, 99) . sprintf("%08d", $park_id);
        }
        if ($insert_id) {
            $car_access_record['from_id'] = $insert_id;
        }
        isset($outParkData['balance']) && !is_null($outParkData['balance']) && $car_access_record['stored_balance'] = $outParkData['balance'];
        $park_time = 0;
        $jTime = 1199116800; // 判断入场时间有效依据  目前是2008年1月1日
        if ($inRecordPark&&isset($inRecordPark['accessTime'])&&$inRecordPark['accessTime']&&$car_access_record['accessTime']) {
            $park_time = $car_access_record['accessTime'] - $inRecordPark['accessTime'];
        } elseif ($outParkData['in_time']&&$outParkData['in_time']>$jTime&&$car_access_record['accessTime']) {
            $park_time = $outParkData['in_time'] - $inRecordPark['accessTime'];
        }
        if ($park_time&&$park_time>0) {
            $car_access_record['park_time'] = $park_time;
        }

        $record_id = M('House_village_car_access_record')->add($car_access_record);
        if (!$record_id) {
            fdump_api(['添加车辆入场记录失败'.__LINE__,$car_number,$in_park,$car_access_record,M('House_village_car_access_record')->getDbError()],'park/'.$park_id.'/in_park_log',1);
        }
        if($insert_id || $record_id){
            if ($car_access_record['trade_no']) {
                $data['trade_no'] = $car_access_record['trade_no'];
            } elseif ($arrData['data']['trade_no']) {
                $data['trade_no'] = $outParkData['trade_no'];
            } else {
                $data['trade_no'] = $nowtime . rand(10, 99) . sprintf("%08d", $park_id);//交易流水号(pay_type为wallet、scancode、sweepcode必传)
            }
            if (!empty($inRecordPark)){
                M('House_village_car_access_record')->where(['record_id'=>$inRecordPark['record_id']])->data(['is_out'=>1,'order_id'=>$order_id])->save();
            }
            /*
            if($record_id>0){
                $updateSave=array('is_out'=>1,'update_time'=>time(),'flag_status'=>'A1_out_recorded_out');
                $whereArr=array('park_id'=>$park_id,'car_number'=>$car_number,'record_id'=>array('lt',$record_id),'is_out'=>0);
                M('House_village_car_access_record')->where($whereArr)->save($updateSave);
            }
            */
            $parking_temp = M('House_village_parking_temp')->where(array('car_number'=>$car_number,'is_out_park'=>0))->find();
            if($parking_temp){//临时
                fdump_api(['临时'.__LINE__,$parking_temp],'park/'.$park_id.'/upload_outpark_log',1);
            }elseif($where_data['pay_type'] == 'monthuser'){//月租
                fdump_api(['月租'.__LINE__,$arrData['data']],'park/'.$park_id.'/upload_outpark_log',1);
            }else{ //储值
                fdump_api(['储值'.__LINE__,$data],'park/'.$park_id.'/upload_outpark_log',1);
            }
            if ($outParks) {
                M('Out_park')->where(array('id'=>$insert_id))->data(array('trade_no'=>$data['trade_no']))->save();
                fdump_api(['更改出场记录'.__LINE__,M('Out_park')->_sql()],'park/'.$park_id.'/upload_outpark_log',1);
                M('In_park')->where(array('id'=>$in_park['id']))->data(array('is_out'=>1,'is_paid'=>1))->save();
                fdump_api(['更改入场记录'.__LINE__,M('In_park')->_sql()],'park/'.$park_id.'/upload_outpark_log',1);
            }
            if($car_number){
                $house_village_parking_temp = M('House_village_parking_temp')->where(array('car_number'=>$car_number,'is_out_park'=>0))->find();
                if($house_village_parking_temp){
                    M('House_village_parking_temp')->where(array('car_number'=>$car_number,'is_out_park'=>0))->data(array('is_out_park'=>1))->save();
                    fdump_api(['更改临时车记录'.__LINE__,$house_village_parking_temp,M('House_village_parking_temp')->_sql()],'park/'.$park_system['park_id'].'/upload_outpark_log',1);
                }
            }
            if ($arrData['msg_id']) {
                $where_record = [
                    'msg_id' => $arrData['msg_id'],
                ];
            } else {
                $where_record = array('token'=>$token,'service_name'=>'outpark', 'order_id' => $arrData['data']['order_id']);
            }
            $dataSave = [
                'status'=>1,
                'update_time' => time(),
            ];
            M('Park_total_record')->where($where_record)->delete();
            // 如果是无牌车 记录出场
            if ($house_village_parking_temp['is_pay_scene']==2) {
                M('Nolicence_in_park')->where(array('car_number' => $car_number))->data(array('is_out_park'=>1))->save();
            } elseif (M('Nolicence_in_park')->where(array('car_number' => $car_number))->find()) {
                M('Nolicence_in_park')->where(array('car_number' => $car_number))->data(array('is_out_park'=>1))->save();
            }
        }
        $data['state'] = 1;
        $data['errmsg'] = '结算成功';
        $data['service_name'] = 'outpark';
        $data['order_id'] = $arrData['data']['order_id'];
        $data['pay_type'] = $arrData['data']['pay_type'] ? $arrData['data']['pay_type'] : 'cash';
        fdump_api(['结算成功'.__LINE__,$data,json_encode($data,JSON_UNESCAPED_UNICODE)],'park/'.$park_system['park_id'].'/upload_outpark_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function upload_code_url()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();
        $data = [];
        fdump_api(['获取二维码地址'.__LINE__,$jsonData,$park_system],'park/upload_code_url_log',1);
        if(!$park_system){
            $data['state'] = 0;
            $data['errmsg'] = '令牌错误!';
            $data['service_name'] = 'code_url!';
            fdump_api(['令牌错误'.__LINE__,$data,$park_system],'park/upload_code_url_log',1);
            echo json_encode($data);die;
        }
        $park_id = $park_system['park_id'];
        fdump_api(['获取二维码地址'.__LINE__,$arrData,$park_system],'park/'.$park_id.'/upload_code_url_log',1);

        $qrcode['service_name'] = $arrData['service_name'];
        $qrcode['token'] = $arrData['token'];
        $qrcode['sign'] = $arrData['sign'];
        $qrcode['type'] = $arrData['data']['type'];
        $qrcode['channel_id'] = $arrData['data']['channel_id'];
        $qrcode['change'] = $arrData['data']['change'];
        $insert_id = M('Park_qrcode')->data($qrcode)->add();
        if($insert_id){
            $data['state'] = 1;
            $data['code_url'] = 'https://beta.bolink.club/unionapi/nolienceinpark?code=0yDMpHWRRohQyYZb';
            $data['type'] = $arrData['data']['type'];
            $data['channel_id'] = $arrData['data']['channel_id'];
            $data['errmsg'] = 'getCodeUrl success';
            $data['service_name'] = 'code_url!';

            fdump_api(['获取二维码地址'.__LINE__,$data,$qrcode],'park/'.$park_id.'/upload_code_url_log',1);
            echo json_encode($data);die;
        }else{
            $data['state'] = 0;
            $data['errmsg'] = 'getCodeUrl error';
            $data['service_name'] = 'code_url!';
            fdump_api(['二维码获取错误'.__LINE__,$data,M('Park_qrcode')->getDbError(),$qrcode],'park/'.$park_id.'/upload_code_url_log',1);
            echo json_encode($data);die;
        }
    }

    //月卡会员信息下传
    public function upload_month_member_sync()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $res = M('Park_total_record')->where(array('msg_id'=>$arrData['msg_id'],'status'=>2))->order('id desc')->find();
        $park_id = $res['park_id'];
        fdump_api(['月卡会员信息下传'.__LINE__,$jsonData,$res],'park/'.$park_id.'/upload_month_member_sync_log',1);
        if($arrData['service_name'] == 'month_member_sync'){
            if(($arrData['state'] == 1) && $res){
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                ];
                M('Park_total_record')->where($where_record)->delete();
                fdump_api(['月卡会员信息下传更改状态'.__LINE__,M('Park_total_record')->_sql()],'park/'.$park_id.'/upload_month_member_sync_log',1);
                // 将对应车辆信息更改为同步成功
                $setData = [
                    'parkSysType' => 'A1',
                    'toParkTime' => time(),
                    'toParkStatus' => 2,
                    'toFailCode' => '',
                    'toFailMessage' =>''
                ];
                $where_car = [];
                if ($res && $res['car_id']) {
                    $where_car = [
                        'car_id' =>$res['car_id']
                    ];
                } elseif ($res && $res['car_number']) {
                    $where_car['_string'] = "concat(`province`,'',`car_number`)='{$res['car_number']}'";
                }
                if (!empty($where_car)) {
                    M('House_village_parking_car')->where($where_car)->save($setData);
                    fdump_api(['车辆信息变更'.__LINE__,M('House_village_parking_car')->_sql(),$setData],'park/'.$park_id.'/upload_month_member_sync_log',1);
                }
            }else{
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
                fdump_api(['月卡会员信息下传记录错误状态'.__LINE__,M('Park_total_record')->_sql(),$arrData],'park/'.$park_id.'/upload_month_member_sync_log',1);
            }
            $data['state'] = 1;
            $data['errmsg'] = '记录成功';
            $data['service_name'] = 'month_member_sync';
            fdump_api(['记录成功'.__LINE__,$data,json_encode($data,JSON_UNESCAPED_UNICODE)],'park/'.$park_id.'/upload_month_member_sync_log',1);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        } else {
            $data['state'] = 0;
            $data['errmsg'] = '无消息';
            $data['data'] = [];
            fdump_api(['无消息'.__LINE__,$data],'park/'.$park_id.'/upload_month_member_sync_log',1);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
    }

    //月卡续费记录下发
    public function upload_month_pay_sync()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $res = M('Park_total_record')->where(array('msg_id'=>$arrData['msg_id'],'status'=>2))->order('id desc')->find();
        $park_id = $res['park_id'];
        fdump_api(['upload_month_pay_sync:'=>__LINE__,'jsonData'=>$jsonData],"park/{$park_id}/upload_month_pay_sync_log",1);
        if($arrData['service_name'] == 'month_pay_sync'){
            if(($arrData['state'] == 1) && $res){
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                ];
                M('Park_total_record')->where($where_record)->delete();
            }else{
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
            }
            fdump_api(['upload_month_pay_sync:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_month_pay_sync_log",1);
            
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'month_pay_sync';
        fdump_api(['记录成功'.__LINE__,$data],'park/'.$res['park_id'].'/upload_month_pay_sync_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function upload_prepay_card_sync()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $res = M('Park_total_record')->where(array('msg_id'=>$arrData['msg_id'],'status'=>2))->order('id desc')->find();
        $park_id = $res['park_id'];
        fdump_api(['upload_prepay_card_sync:'=>__LINE__,'jsonData'=>$jsonData],"park/{$park_id}/upload_prepay_card_sync_log",1);
        if($arrData['service_name'] == 'prepay_card_sync'){
            fdump_api(['upload_prepay_card_sync:'=>__LINE__,'res'=>$res],"park/{$park_id}/upload_prepay_card_sync_log",1);
            if(($arrData['state'] == 1) && $res){
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                ];
                M('Park_total_record')->where($where_record)->delete();
            }else{
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
            }
            fdump_api(['upload_prepay_card_sync:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_prepay_card_sync_log",1);
          
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'prepay_card_sync';
        fdump_api(['记录成功'.__LINE__,$data],'park/'.$res['park_id'].'/upload_prepay_card_sync_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function upload_prepay_card_trade_sync()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);

        $res = M('Park_total_record')->where(array('msg_id'=>$arrData['msg_id'],'status'=>2))->order('id desc')->find();
        $park_id = $res['park_id'];
        fdump_api(['upload_prepay_card_trade_sync:'=>__LINE__,'jsonData'=>$jsonData],"park/{$park_id}/upload_prepay_card_trade_sync_log",1);

        if($arrData['service_name'] == 'prepay_card_trade_sync'){
            fdump_api(['upload_prepay_card_trade_sync:'=>__LINE__,'res'=>$res],"park/{$park_id}/upload_prepay_card_trade_sync_log",1);
            if(($arrData['state'] == 1) && $res){
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                ];
                $carSynStatus = 4;
                $carSynTimes = time();
                $carSynRemarks = '';
                M('Park_total_record')->where($where_record)->delete();
            }else{
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                $carSynStatus = 5;
                $carSynTimes = time();
                $carSynRemarks = $arrData['errmsg'];
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
            }
            fdump_api(['upload_prepay_card_trade_sync:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_prepay_card_sync_log",1);
       
            if ($res['order_id']) {
                $order = D('House_village_pay_order')->where(['order_id'=>$res['order_id']])->find();
                if (2==$order['order_status']) {
                    $saveOrder = [
                        'carSynStatus' => $carSynStatus,
                        'carSynTimes' => $carSynTimes,
                        'carSynRemarks' => $carSynRemarks,
                    ];
                    D('House_village_pay_order')->where(['order_id'=>$res['order_id']])->save($saveOrder);
                }
            }
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'prepay_card_trade_sync';
        fdump_api(['记录成功'.__LINE__,$data],'park/'.$res['park_id'].'/upload_prepay_card_trade_sync_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function upload_operate_gate()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);

        $res = M('Park_total_record')->where(array('msg_id'=>$arrData['msg_id'],'status'=>2))->order('id desc')->find();
        $park_id = $res['park_id'];
        fdump_api(['upload_operate_gate:'=>__LINE__,'jsonData'=>$jsonData],"park/{$park_id}/upload_operate_gate_log",1);

        if($arrData['service_name'] == 'operate_gate'){
            fdump_api(['upload_operate_gate:'=>__LINE__,'res'=>$res],"park/{$park_id}/upload_operate_gate_log",1);
            if(($arrData['state'] == 1) && $res){
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                M('Park_total_record')->where($where_record)->delete();
            }else{
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
            }
            fdump_api(['upload_operate_gate:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_operate_gate_log",1);
           
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'operate_gate';
        fdump_api(['记录成功'.__LINE__,$data],'park/'.$res['park_id'].'/upload_operate_gate_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function upload_blackuser_sync_rsp()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);

        $res = M('Park_total_record')->where(array('msg_id'=>$arrData['msg_id'],'status'=>2))->order('id desc')->find();
        $park_id = $res['park_id'];
        fdump_api(['upload_blackuser_sync_rsp:'=>__LINE__,'jsonData'=>$jsonData],"park/{$park_id}/upload_blackuser_sync_rsp_log",1);
        if($arrData['service_name'] == 'blackuser_sync_rsp'){
            fdump_api(['upload_blackuser_sync_rsp:'=>__LINE__,'res'=>$res],"park/{$park_id}/upload_blackuser_sync_rsp_log",1);
            if(($arrData['state'] == 1) && $res){
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                ];
                M('Park_total_record')->where($where_record)->delete();
            }else{
                $where_record = array('msg_id'=>$arrData['msg_id']);
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
            }
            fdump_api(['upload_blackuser_sync_rsp:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_blackuser_sync_rsp_log",1);
           
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'prepay_card_trade_sync';
        fdump_api(['记录成功'.__LINE__,$data],'park/'.$park_id.'/upload_blackuser_sync_rsp_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    //查询订单返回数据
    public function upload_query_price()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);

        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();
        $park_id = $park_system['park_id'];
        fdump_api(['upload_query_price_log:'=>__LINE__,'jsonData'=>$jsonData,'arrData'=>$arrData],"park/{$park_id}/upload_query_price_log",1);
        $data = [];
        if(!$park_system){
            $data['state'] = 0;
            $data['errmsg'] = '无消息';
            $data['data'] = [];
            fdump('停车系统交互未完成请检查配置<'.__LINE__,'api/log/'.date('Ymd').'/park/'.$park_id.'/upload_query_price_log',true);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
        if (isset($arrData['data']['query_order_no'])&&$arrData['data']['query_order_no']) {
            $now_record = M('Park_total_record')->where(array('park_id' => $park_id, 'token'=>$token,'service_name'=>'query_price','msg_id'=>$arrData['data']['query_order_no']))->order('id DESC')->find();
        } elseif (isset($arrData['data']['order_id'])&&$arrData['data']['order_id']) {
            $now_record = M('Park_total_record')->where(array('park_id' => $park_id, 'token'=>$token,'service_name'=>'query_price','order_id'=>$arrData['data']['order_id']))->order('id DESC')->find();
        }
        if (1==$now_record['status']) {
            $data['state'] = 0;
            $data['errmsg'] = '任务已执行';
            $data['data'] = [];
            fdump('停车系统交互未完成请检查配置<'.__LINE__,'api/log/'.date('Ymd').'/park/'.$park_id.'/upload_query_price_log',true);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
        $config_data = D('Config_data')->get_config_data();
        $inParkData = $arrData['data'];
        $car_number = $inParkData['car_number']?$inParkData['car_number']:'';
        $inParkData['price'] = $inParkData['price']?$inParkData['price']:0;
        $inParkData['errmsg']=(isset($inParkData['errmsg'])&&!empty($inParkData['errmsg']))?$inParkData['errmsg']:'';
        if (!empty($now_record['car_number'])&&!empty($car_number)&&$car_number!=$now_record['car_number']){
            $data['state'] = 0;
            $data['errmsg'] = '查询数据错误';
            $data['data'] = [];
            fdump('停车系统交互未完成请检查配置<'.__LINE__,'api/log/'.date('Ymd').'/park/'.$park_id.'/upload_query_price_log',true);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
        // 如果所有入场车辆和出场车辆均已完成迁移，不再记录数据进入in_park表格
        $intParks = false;
        if (!isset($config_data['inParkMove']) || !$config_data['inParkMove'] || !isset($config_data['outParkMove']) || !$config_data['outParkMove']) {
            $intParks = true;
            $where_data = $inParkData;
            $where_in_park = [];
            $where_in_park['is_out'] = 0;
            if ($where_data['park_id']) {
                $where_in_park['park_id'] = $park_id;
            }
            if ($car_number) {
                $where_in_park['car_number'] = $car_number;
            }
            if ($where_data['order_id']) {
                $where_in_park['order_id'] = $where_data['order_id'];
            }
            $in_park = M('In_park')->where($where_in_park)->order('id desc')->find();
            fdump(['line:--->'.__LINE__, $in_park],'api/log/'.date('Ymd').'/park/'.$park_id.'/upload_query_price_log',true);
        }
        $whereInParkRecord = [];
        $whereInParkRecord['park_id'] = $park_id;
        if ($car_number) {
            $whereInParkRecord['car_number'] = $car_number;
        }
        if ($whereInParkRecord['order_id']) {
            $whereInParkRecord['order_id'] = $inParkData['order_id'];
        }
        $whereInParkRecord['accessType'] = 1;
        $inRecordPark = M('House_village_car_access_record')->where($whereInParkRecord)->order('record_id DESC')->find();

        if ($now_record['msg_id'] && strpos($now_record['msg_id'],'query_price_') !== false) {
            $where_data['query_order_no'] = $now_record['msg_id'];
        }
        if(strpos($where_data['query_order_no'],'query_price_') !== false){
            $query_order_no = str_replace('query_price_','',$where_data['query_order_no']);
            $temp = M('House_village_parking_temp')->where(['id' => $query_order_no])->find();
            fdump_api(['upload_query_price_log:'=>__LINE__,'now_record'=>$now_record,'query_order_no'=>$query_order_no,'temp'=>$temp],"park/{$park_id}/upload_query_price_log",1);
            if ($temp) {
                // 如果已经存在，直接更改数据即可
                $in_time = '';
                $car_number = '';
                if (!$in_time && $in_park && $in_park['in_time']) {
                    $in_time = $in_park['in_time'];
                } elseif (!$in_time && $inRecordPark && $inRecordPark['accessTime']) {
                    $in_time = $inRecordPark['accessTime'];
                }
                if (!$car_number && $inParkData && $inParkData['car_number']) {
                    $car_number = $inParkData['car_number'];
                }
                if (!$car_number && $in_park && $in_park['car_number']) {
                    $car_number = $in_park['car_number'];
                }
                if (!$in_time && $inParkData && $inParkData['in_time']) {
                    $in_time = $inParkData['in_time'];
                }
                $data_temp['service_name'] = $arrData['service_name'];
                $data_temp['village_id'] = $park_system['park_id'];
                $data_temp['add_time'] = time();
                $data_temp['token'] = $arrData['token'];
                $data_temp['sign'] = $arrData['sign'];
                $data_temp['state'] = $inParkData['state'];
                $data_temp['free_out_time'] = $inParkData['free_out_time'] ? $inParkData['free_out_time']:'';
                $data_temp['query_time'] = $inParkData['query_time']? $inParkData['query_time']:'';
                $data_temp['duration'] = $inParkData['duration'] ? $inParkData['duration']:'';
                $data_temp['derate_money'] = $inParkData['derate_money']?$inParkData['derate_money']:'';
                $data_temp['derate_duration'] = $inParkData['derate_duration']?$inParkData['derate_duration']:'';
                $data_temp['order_id'] = $inParkData['order_id'];
                $data_temp['query_order_no'] = $inParkData['query_order_no'];
                $data_temp['errmsg'] = $inParkData['errmsg'];
                $data_temp['price'] = $inParkData['price'];
                $data_temp['total'] = $inParkData['total'] ? $inParkData['total']:'';
                $data_temp['position'] = $inParkData['position']?$inParkData['position']:'';
                $data_temp['in_time'] = $in_time;
                $data_temp['car_number'] = $car_number;
                $data_temp['multiple_prepay'] = $inParkData['multiple_prepay']?$inParkData['multiple_prepay']:'';
                $temp_save = M('House_village_parking_temp')->where(['id' => $temp['id']])->data($data_temp)->save();
                fdump_api(['upload_query_price_log:'=>__LINE__,'data_temp'=>$data_temp],"park/{$park_id}/upload_query_price_log",1);
                if($temp_save!==false){
                    $where_record = array('id'=>$now_record['id']);
                    $dataSave = [
                        'status'=>1,
                        'update_time' => time(),
                    ];
                    M('Park_total_record')->where($where_record)->delete();
                }else{
                    $where_record = array('id'=>$now_record['id']);
                    $dataSave = [
                        'status'=>0,
                        'update_time' => time(),
                        'errmsg'=>$arrData['errmsg'],
                    ];
                    M('Park_total_record')->where($where_record)->data($dataSave)->save();
                }
                fdump_api(['upload_query_price_log:'=>__LINE__,'temp_save'=>$temp_save,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_query_price_log",1);
          
            }
        } elseif($in_park || $inRecordPark){ //如果存在 则为预付
            $data_temp['service_name'] = $arrData['service_name'];
            $data_temp['village_id'] = $park_system['park_id'];
            $data_temp['add_time'] = time();
            $data_temp['token'] = $arrData['token'];
            $data_temp['sign'] = $arrData['sign'];
            $data_temp['state'] = $inParkData['state'];
            $data_temp['free_out_time'] = $inParkData['free_out_time'] ? $inParkData['free_out_time']:'';
            $data_temp['query_time'] = $inParkData['query_time']? $inParkData['query_time']:'';
            $data_temp['duration'] = $inParkData['duration'] ? $inParkData['duration']:'';
            $data_temp['derate_money'] = $inParkData['derate_money']?$inParkData['derate_money']:'';
            $data_temp['derate_duration'] = $inParkData['derate_duration']?$inParkData['derate_duration']:'';
            $data_temp['order_id'] = $inParkData['order_id'];
            $data_temp['query_order_no'] = $inParkData['query_order_no'];
            $data_temp['errmsg'] = $inParkData['errmsg'];
            $data_temp['price'] = $inParkData['price'];
            $data_temp['total'] = $inParkData['total'] ? $inParkData['total']:'';
            $data_temp['position'] = $inParkData['position']?$inParkData['position']:'';
            $data_temp['in_time'] = $inRecordPark['accessTime']?$inRecordPark['accessTime']:$in_park['in_time'];
            $data_temp['car_number'] = $car_number;
            $data_temp['multiple_prepay'] = $inParkData['multiple_prepay']?$inParkData['multiple_prepay']:'';
            $data_temp['status'] = 0;
            $data_temp['is_pay_scene'] = 0;
            $res = M('House_village_parking_temp')->data($data_temp)->add();
            fdump_api(['预付upload_query_price_log:'=>__LINE__,'data_temp'=>$data_temp,'res'=>$res],"park/{$park_id}/upload_query_price_log",1);

            if($res){
                $where_record = array('id'=>$now_record['id']);
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                ];
                M('Park_total_record')->where($where_record)->delete();
            }else{
                $where_record = array('id'=>$now_record['id']);
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
            }
            fdump_api(['upload_query_price_log:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_query_price_log",1);
 
        }else{ //无,则为直付
            if ($intParks) {
                $straight_in_park = M('In_park')->where(array('order_id'=>$inParkData['order_id'],'is_out'=>0))->order('id desc')->find();
            } else {
                $straight_in_park = M('House_village_car_access_record')->where(array('order_id'=>$inParkData['order_id'],'accessType'=>1))->order('record_id desc')->find();
            }
            if(empty($straight_in_park)){//无数据
                $data['state'] = 0;
                $data['errmsg'] = '无消息';
                $data['data'] = [];
                fdump_api(['直付无入场记录:'=>__LINE__,'straight_in_park'=>$straight_in_park],"park/{$park_id}/upload_query_price_log",1);
                echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
            }else{
                $data_temp['service_name'] = $arrData['service_name'];
                $data_temp['village_id'] = $park_id;
                $data_temp['add_time'] = time();
                $data_temp['token'] = $arrData['token'];
                $data_temp['sign'] = $arrData['sign'];
                $data_temp['state'] = $inParkData['state'];
                $data_temp['free_out_time'] = $inParkData['free_out_time'] ? $inParkData['free_out_time']:'';
                $data_temp['query_time'] = $inParkData['query_time']? $inParkData['query_time']:'';
                $data_temp['duration'] = $inParkData['duration'] ? $inParkData['duration']:'';
                $data_temp['derate_money'] = $inParkData['derate_money']?$inParkData['derate_money']:'';
                $data_temp['derate_duration'] = $inParkData['derate_duration']?$inParkData['derate_duration']:'';
                $data_temp['order_id'] = $inParkData['order_id'];
                $data_temp['query_order_no'] = $inParkData['query_order_no'];
                $data_temp['errmsg'] = $inParkData['errmsg'];
                $data_temp['price'] = $inParkData['price'];
                $data_temp['total'] = $inParkData['total'] ? $inParkData['total']:'';
                $data_temp['position'] = $inParkData['position']?$inParkData['position']:'';
                $data_temp['in_time'] = '';
                $data_temp['car_number'] = $car_number;
                $data_temp['multiple_prepay'] = $inParkData['multiple_prepay']?$inParkData['multiple_prepay']:'';
                $data_temp['status'] = 0;
                $data_temp['is_pay_scene'] = 1;
                $res = M('House_village_parking_temp')->data($data_temp)->add();

                fdump_api(['直付:'=>__LINE__,'straight_in_park'=>$straight_in_park,'res'=>$res,'data_temp'=>$data_temp],"park/{$park_id}/upload_query_price_log",1);

                if($res){
                    $where_record = array('id'=>$now_record['id']);
                    $dataSave = [
                        'status'=>1,
                        'update_time' => time(),
                    ];
                    M('Park_total_record')->where($where_record)->delete();
                }else{
                    $where_record = array('id'=>$now_record['id']);
                    $dataSave = [
                        'status'=>0,
                        'update_time' => time(),
                        'errmsg'=>$arrData['errmsg'],
                    ];
                    M('Park_total_record')->where($where_record)->data($dataSave)->save();
                }
                fdump_api(['upload_query_price_log:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_query_price_log",1);
         
            }
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'query_price';
        fdump_api(['记录成功'.__LINE__,$data],'park/'.$park_id.'/upload_query_price_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    //优惠券上传回来处理
    public function upload_deliver_ticket()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);

        $res = M('Park_total_record')->where(array('msg_id'=>$arrData['msg_id'],'status'=>2))->order('id desc')->find();
        $park_id = $res['park_id'];
        if (!$park_id) {
            $token = $arrData['token'];
            $park_system = M('Park_system')->where(array('token'=>$token))->find();
            $park_id = $park_system['park_id'];
        }
        fdump_api(['upload_deliver_ticket:'=>__LINE__,'jsonData'=>$jsonData,'arrData'=>$arrData],"park/{$park_id}/upload_deliver_ticket_log",1);

        if($arrData['service_name'] == 'deliver_ticket'){
            $where_record = [
                'token'=>$arrData['token'],
                'status'=>2,
                'service_name' => 'deliver_ticket',
            ];
            $where_coupon = [
                'status' => 2,
                'is_use' => 0,
            ];
            $data_coupon = [
                'status' => 1,
                'is_use' => 1,
                'use_time' => time(),
                'use_txt' => serialize($arrData),
            ];
            $where_data = $arrData['data'];
            if ($where_data['ticket_id']) {
                $where_record['ticket_id'] = $where_data['ticket_id'];
                $where_coupon['ticket_id'] = $where_data['ticket_id'];
            }
            if ($where_data['time_range']) {
                $data_coupon['time_range'] = $where_data['time_range'];
            }
            if ($where_data['have_order']) {
                $data_coupon['have_order'] = $where_data['have_order'];
            }
            $record = M('Park_total_record')->where($where_record)->order('id desc')->find();
            fdump_api(['upload_deliver_ticket:'=>__LINE__,'record'=>$record,'where_record'=>$where_record],"park/{$park_id}/upload_deliver_ticket_log",1);
            if(($arrData['data']['state'] == 1) && $record){
                $where_record = array('id'=>$record['id']);
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                ];
                M('House_village_park_coupon')->where($where_coupon)->data($data_coupon)->save();
                M('Park_total_record')->where($where_record)->delete();
            }else{
                $errmsg = $where_data['errmsg'] ? $where_data['errmsg'] : '未知错误';
                $where_record = array('id'=>$record['id']);
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$errmsg,
                ];
                $data_coupon['status'] = 0;
                $data_coupon['is_use'] = 0;
                M('House_village_park_coupon')->where($where_coupon)->data($data_coupon)->save();
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
            }
            fdump_api(['upload_blackuser_sync_rsp:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave,'where_coupon'=>$where_coupon,'data_coupon'=>$data_coupon],"park/{$park_id}/upload_blackuser_sync_rsp_log",1);
          
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'deliver_ticket';
        fdump_api(['记录成功'.__LINE__,$data],'/park/upload_deliver_ticket_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    //通道上传回来处理
    public function upload_gate_sync()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $res = M('Park_total_record')->where(array('token'=>$arrData['token'],'service_name'=>'gate_sync','status'=>2))->order('id desc')->find();
        $park_id = $res['park_id'];
        if (!$park_id) {
            $token = $arrData['token'];
            $park_system = M('Park_system')->where(array('token'=>$token))->find();
            $park_id = $park_system['park_id'];
        }
        fdump_api(['upload_gate_sync:'=>__LINE__,'jsonData'=>$jsonData,'arrData'=>$arrData],"park/{$park_id}/upload_gate_sync_log",1);

        if($arrData['service_name'] == 'gate_sync'){

            if(($arrData['state'] == 1) && $res){
                M('Park_total_record')->where(array('token'=>$arrData['token'],'service_name'=>'gate_sync','status'=>2))->delete();
            }else{
                M('Park_total_record')->where(array('token'=>$arrData['token'],'service_name'=>'gate_sync','status'=>2))->data(array('status'=>0,'errmsg'=>$arrData['errmsg']))->save();
            }
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'gate_sync';
        fdump_api(['记录成功'.__LINE__,$data],'/park/'.$park_id.'/upload_deliver_ticket_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    public function upload_prepay_order()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);

        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();
        $park_id = $park_system['park_id'];
        fdump_api(['upload_prepay_order->'.__LINE__,'jsonData'=>$jsonData,'arrData'=>$arrData],'park/'.$park_id.'/upload_prepay_order_log',1);
        if($arrData['service_name'] == 'prepay_order'){
            $data = [];
            if(!$park_system){
                $data['state'] = 0;
                $data['errmsg'] = '无消息';
                $data['data'] = [];
                fdump_api(['error'=>'设备无登录信息'],'park/'.$park_id.'/upload_prepay_order_log',1);
                echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
            }

            $where_data = $arrData['data'];
            $where_record = [
                'token'=>$arrData['token'],
                'service_name'=>'prepay_order',
            ];
            if ($where_data['park_id']){
                $where_record['park_id'] = $where_data['park_id'];
            }
            if ($where_data['order_id']){
                $where_record['order_id'] = $where_data['order_id'];
            }
            $park_total_record = M('Park_total_record')->where($where_record)->find();
            $config_data = D('Config_data')->get_config_data();
            if($where_data['state'] == 1){
                // 如果所有入场车辆和出场车辆均已完成迁移，不再记录数据进入in_park表格
                $intParks = false;
                if (!isset($config_data['inParkMove']) || !$config_data['inParkMove'] || !isset($config_data['outParkMove']) || !$config_data['outParkMove']) {
                    $intParks = true;
                    $in_park = M('In_park')->where(array('order_id' => $where_data['order_id']))->find();
                    if ($in_park) {
                        M('In_park')->where(array('order_id' => $arrData['data']['order_id']))->data(array('is_paid' => 1))->save();
                    }
                }
                $whereInParkRecord = [];
                $whereInParkRecord['park_id'] = $park_system['park_id'];
                if ($where_data['park_id']){
                    $where_record['park_id'] = $where_data['park_id'];
                }
                if ($where_data['order_id']){
                    $where_record['order_id'] = $where_data['order_id'];
                }
                $whereInParkRecord['accessType'] = 1;
                $inRecordPark = M('House_village_car_access_record')->where($whereInParkRecord)->order('record_id DESC')->find();
                if ($inRecordPark && $park_total_record) {
                    if ($park_total_record['unserialize_desc']) {
                        $unserialize_desc = unserialize($park_total_record['unserialize_desc']);
                        $trade_no = $unserialize_desc['trade_no']?$unserialize_desc['trade_no']:'';
                        $prepayTotal = $unserialize_desc['arrive_money']?$unserialize_desc['arrive_money']:0;
                        $pay_time = $unserialize_desc['pay_time']?$unserialize_desc['pay_time']:0;
                        $is_paid = 2;
                        $inRecordParkData = [];
                        if ($trade_no) {
                            $inRecordParkData['trade_no'] = $trade_no;
                        }
                        if ($prepayTotal) {
                            $inRecordParkData['prepayTotal'] = $prepayTotal + floatval($inRecordPark['prepayTotal']);
                        }
                        if ($pay_time) {
                            $inRecordParkData['pay_time'] = $pay_time;
                        }
                        if ($is_paid) {
                            $inRecordParkData['is_paid'] = $is_paid;
                        }
                        if ($inRecordParkData) {
                            M('House_village_car_access_record')->where(['record_id' => $inRecordPark['record_id']])->save($inRecordParkData);
                        }
                    }
                }
                $data_save = [
                    'update_time' => time(),
                    'status'=>1,
                ];
                $set = M('Park_total_record')->where($where_record)->delete();
            }else{
                $data_save = [
                    'update_time' => time(),
                    'status'=>0,
                    'errmsg'=>$arrData['errmsg'],
                ];
                $set = M('Park_total_record')->where($where_record)->data($data_save)->save();
            }
            fdump_api(['upload_prepay_order->'.__LINE__,'where_record'=>$where_record,'data_save'=>$data_save,'set' => $set],'park/'.$park_id.'/upload_prepay_order_log',1);
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'prepay_order';
        fdump_api(['记录成功'.__LINE__,$data],'/park/'.$park_system['park_id'].'/upload_prepay_order_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();

    }

    /**
     * 储值余额查询
     */
    public function upload_query_prepay_card_balance()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);

        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();
        $park_id = $park_system['park_id'];
        fdump_api(['upload_query_prepay_card_balance:'=>__LINE__,'jsonData'=>$jsonData,'arrData'=>$arrData],"park/{$park_id}/upload_query_prepay_card_balance_log",1);

        $data = [];
        if(!$park_system){
            $data['state'] = 0;
            $data['errmsg'] = '无消息';
            $data['data'] = [];
            fdump_api(['设备未登陆:'=>__LINE__],"park/{$park_id}/upload_query_prepay_card_balance_log",1);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
        if($arrData['service_name'] == 'query_prepay_card_balance'){
            $where_record = array('token'=>$arrData['token'],'service_name'=>'query_prepay_card_balance','card_id'=>$arrData['card_id']);
            if($arrData['state'] == 1){
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                ];
                M('Park_total_record')->where($where_record)->delete();
            }else{
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$arrData['errmsg'],
                ];
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
            }
            fdump_api(['upload_query_prepay_card_balance:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_query_prepay_card_balance_log",1);
        
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'query_prepay_card_balance';
        fdump_api(['记录成功'.__LINE__,$data],'/park/'.$park_id.'/upload_query_prepay_card_balance_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function upload_nolicence_in_park()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();
        $park_id = $park_system['park_id'];
        fdump(['upload_nolicence_in_park>'.__LINE__,$jsonData],'api/log/'.date('Ymd').'/park/'.$park_id.'/upload_nolicence_in_park_log',true);
        if(!$park_system){
            $data = [];
            $data['state'] = 0;
            $data['errmsg'] = '无消息';
            $data['data'] = [];
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
        $service_name = 'nolicence_in_park';
        if($arrData['service_name'] == $service_name){
            $car_number = $arrData['data']['car_number'];
            $where_record = [
                'token'=>$arrData['token'],
                'service_name'=>$service_name,
                'car_number'=>$car_number,
                'park_id'=>$park_id,
            ];
            if($arrData['data']['state'] == 1){
                $dataSave = [
                    'status'=>1,
                    'update_time' => time(),
                ];
                fdump_api(['upload_nolicence_in_park:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_nolicence_in_park_log",1);
                M('Park_total_record')->where($where_record)->delete();
                $where_no = [
                    'token'=>$arrData['token'],
                    'park_id'=>$park_id,
                    'car_number'=>$car_number,
                ];
                M('Nolicence_in_park')->where($where_no)->setField('is_in_park', 1);
                // 查询下无牌车信息 记录进入统一表格
                $noCarNumberPark = M('Nolicence_in_park')->where($where_no)->find();
                $where_passage = [];
                $where_passage['channel_number'] = $noCarNumberPark['channel_id'];
                $where_passage['village_id'] = $park_id;
                $channel_info = M('Park_passage')->where($where_passage)->find();
                // 车场编号为小区 查询小区相关信息
                $village_info = M('House_village')->where(['village_id'=> $park_id])->field('village_id,village_name')->find();
                $park_name = $village_info['village_name']?$village_info['village_name']:'';
                $uid = $noCarNumberPark['uid']?$noCarNumberPark['uid']:0;
                // 查询对应车辆的现有绑定情况
                $where_car = [];
                $where_car['_string'] = "concat(`province`,'',`car_number`)='{$car_number}'";
                $infos_car_info = D('House_village_parking_car')->where($where_car)->field('car_id,car_position_id,car_user_name,car_user_phone,car_stop_num')->find();
                if ($infos_car_info && $infos_car_info['car_id']) {
                    $bind_info = D('House_village_bind_car')->where(['car_id' => $infos_car_info['car_id']])->find();
                }
                if (!$uid) {
                    $uid = ($bind_info&&$bind_info['uid'])?$bind_info['uid']:'';
                }
                $car_id = ($infos_car_info&&$infos_car_info['car_id'])?$infos_car_info['car_id']:'';
                $bind_id = ($bind_info&&$bind_info['user_id'])?$bind_info['user_id']:'';
                $user_name = ($infos_car_info&&$infos_car_info['car_user_name'])?$infos_car_info['car_user_name']:'';
                $user_phone = ($infos_car_info&&$infos_car_info['car_user_phone'])?$infos_car_info['car_user_phone']:'';

                $now_time = time();
                // 记录无牌车入场
                $access_record = [
                    'business_type' => 0,
                    'business_id' => $park_id,
                    'car_number' => $car_number,
                    'accessType' => 1,
                    'accessTime' => $now_time,
                    'channel_id' => $channel_info['id']?$channel_info['id']:0,
                    'channel_number' => $noCarNumberPark['channel_id']?$noCarNumberPark['channel_id']:'',
                    'channel_name' => $channel_info['passage_name']?$channel_info['passage_name']:'',
                    'accessMode' => 6,
                    'park_sys_type' => 'A1',
                    'park_id' => $park_id,
                    'park_name' => $park_name,
                    'token' => $token,
                    'sign' => $arrData['sign']?$arrData['sign']:'',
                    'uid' => $uid,
                    'car_id' => $car_id,
                    'bind_id' => $bind_id,
                    'user_name' => $user_name,
                    'user_phone' => $user_phone,
                    'update_time' => $now_time,
                    'service_name' => 'nolicence_in_park',
                    'from_id' => $noCarNumberPark['id'],
                    'carType' => 1,
                ];
                $record_id = M('House_village_car_access_record')->add($access_record);
            }else{
                $errmsg = $arrData['data']['errmsg'] ? $arrData['data']['errmsg'] : '未知错误';
                $dataSave = [
                    'status'=>0,
                    'update_time' => time(),
                    'errmsg'=>$errmsg,
                ];
                fdump_api(['upload_nolicence_in_park:'=>__LINE__,'where_record'=>$where_record,'dataSave' => $dataSave],"park/{$park_id}/upload_nolicence_in_park_log",1);
                M('Park_total_record')->where($where_record)->data($dataSave)->save();
            }
            fdump(['park_total_record-'.__LINE__,M('Park_total_record')->_sql(),$record_id,$access_record],'api/log/'.date('Ymd').'/park/'.$park_id.'/upload_nolicence_in_park_log',true);
        }
        $data['state'] = 1;
        $data['errmsg'] = '记录成功';
        $data['service_name'] = 'nolicence_in_park';
        fdump_api(['记录成功'.__LINE__,$data],'/park/'.$park_id.'/upload_nolicence_in_park_log',1);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function download()
    {
        $jsonData = file_get_contents('php://input');
        $arrData = json_decode($jsonData,true);
        $token = $arrData['token'];
        $park_system = M('Park_system')->where(array('token'=>$token))->find();

		//记录停车主机上报时间
		if($park_system){
            $park_id = $park_system['park_id'];
            $cacheKey = "park_system_" . $park_id;
            cache($cacheKey,time());
    		M('Park_system')->where(['id' => $park_system['id']])->data(['last_upload_time' => time()])->save();
		}
        if(!$park_system){
            $i=1;
            while($i<=5)
            {
                if($i == 5){
                    $data['state'] = 0;
                    $data['errmsg'] = '无消息';
                    $data['data'] = [];
                    echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
                }
                sleep(1);
                $i++;
            }
        }elseif($arrData['service_name'] == 'login' && $park_system){
            $data['state'] = 1;
            $data['token'] = $token;
            $data['service_name'] = 'login';
            fdump(['系统登录成功'.__LINE__,$data,$park_system,$jsonData],'api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/download_log',true);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }elseif($park_system && $token){
            //返回给我参数
            $park_total_record = M('Park_total_record')->where(array('park_id'=>$park_system['park_id'],'status'=>2))->order('down_times ASC, id DESC')->find();
            fdump_api(['设备反馈:'=>__LINE__,'Park_total_record'=>$park_total_record],"park/{$park_system['park_id']}/download_log",1);
            if($park_total_record['service_name'] == 'month_member_sync') {//月卡会员下发
                $whereNew = [
                    'service_name' => $park_total_record['service_name'],
                    'create_time'  => ['gt', $park_total_record['create_time']],
                    'status'       => 1,
                    'park_id'      => $park_system['park_id'],
                    'car_number'   => $park_total_record['car_number'],
                ];
                $hasNewRecord = M('Park_total_record')->where($whereNew)->find();
                if ($hasNewRecord) {
                    // 如果存在最新的 月卡会员下发本次就当成已经下发过了
                    $saveData = [
                        'status'      => 0,
                        'errmsg'      => '已经存在更新的数据下发成功了，当前不进行下发',
                        'update_time' => time(),
                    ];
                    M('Park_total_record')->where(array('id'=>$park_total_record['id']))->delete();
                    $data['state'] = 0;
                    $data['errmsg'] = '无消息!';
                    $data['data'] = [];
                    echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
                }
            }
            // 暂定 下行次数大于等于50次的 直接默认同步失败
            $down_times = 50;
            if($park_total_record){
                if (intval($park_total_record['down_times'])> $down_times) {
                    M('Park_total_record')->where(array('id'=>$park_total_record['id']))->setField('status',0);
                    $i=1;
                    while($i<=5)
                    {
                        if($i == 5){
                            $data['state'] = 0;
                            $data['errmsg'] = '无消息!';
                            $data['data'] = [];
                            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
                        }
                        sleep(1);
                        $i++;
                    }
                } else {
                    // 每次查询添加次数，以防同一个下行卡主导致后续无法执行
                    M('Park_total_record')->where(array('id'=>$park_total_record['id']))->setInc('down_times',1);
                    fdump_api(['具体反馈:'=>__LINE__,'id'=>$park_total_record['id'],'service_name' => $park_total_record['service_name']],"park/{$park_system['park_id']}/download_log",1);
                    if($park_total_record['service_name'] == 'month_member_sync'){//月卡会员下发
                        $this->download_month_member_sync($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'month_pay_sync'){//月卡续费记录下发
                        $this->download_month_pay_sync($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'prepay_card_sync'){//储值卡同步
                        $this->download_prepay_card_sync($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'prepay_card_trade_sync'){//储值卡充值记录下发
                        $this->download_prepay_card_trade_sync($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'blackuser_sync'){//黑名单下发
                        $this->download_blackuser_sync($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'query_price'){//查询订单
                        $this->download_query_price($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'deliver_ticket'){//优惠券下发数据
                        $this->download_deliver_ticket($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'gate_sync'){//通道下发
                        $this->download_gate_sync($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'prepay_order'){//同步下发
                        $this->download_prepay_order($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'outpark'){//3.4电子收费异步返回结果（泊链）
                        $this->download_outpark($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'query_prepay_card_balance'){//2.28 储值卡余额查询（停车云
                        $this->download_query_prepay_card_balance($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'nolicence_in_park'){//无牌车
                        $this->download_nolicence_in_park($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'operate_gate'){//无牌车
                        $this->download_operate_gate($park_total_record['id']);
                    }elseif($park_total_record['service_name'] == 'get_car_info'){//无牌车
                        $this->download_get_car_info($park_total_record['id']);
                    }else{
                        $i=1;
                        while($i<=5)
                        {
                            if($i == 5){
                                $data['state'] = 0;
                                $data['errmsg'] = '无消息!';
                                $data['data'] = [];
                                echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
                            }
                            sleep(1);
                            $i++;
                        }
                    }
                    $data['state'] = 0;
                    $data['errmsg'] = '无消息!';
                    $data['data'] = [];
                    echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
                }
            }else{
                $i=1;
                while($i<=5)
                {
                    if($i == 5){
                        $data['state'] = 0;
                        $data['errmsg'] = '无消息!';
                        $data['data'] = [];
                        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
                    }
                    sleep(1);
                    $i++;
                }
            }
        }else{
            $i=1;
            while($i<=5)
            {
                if($i == 5){
                    $data['state'] = 0;
                    $data['errmsg'] = '无消息!';
                    $data['data'] = [];
                    echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
                }
                sleep(1);
                $i++;
            }
        }




    }

    /*
         * @desc 登录返回
         * @User guoxiansen
         */
    public function download_login($id)
    {

        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0登录返回:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1登录返回:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /*
     * @desc 月卡会员下发
     * @User guoxiansen
     */
    public function download_month_member_sync($id)
    {

        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0月卡会员下发:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_month_member_sync_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1月卡会员下发:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_month_member_sync_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    /*
 * @desc 临时车C登记完成后, 平台发送远程开闸命令给设备
 * @User zhubaodi
 */
    public function download_operate_gate($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0远程开闸命令下发:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_operate_gate_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1远程开闸命令下发:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_operate_gate_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /*
* @desc 平台下发查询车辆信息命令
* @User zhubaodi
*/
    public function download_get_car_info($id)
    {

        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0查询车辆信息命令下发:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_get_car_info_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1查询车辆信息命令下发:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_get_car_info_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }
    /*
     * @desc 月卡续费记录下发
     * @User guoxiansen
     */
    public function download_month_pay_sync($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0月卡续费记录下发:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_month_pay_sync_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1月卡续费记录下发:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_month_pay_sync_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /*
     * @desc 储值卡同步
     * @User guoxiansen
     */
    public function download_prepay_card_sync($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0储值卡同步:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_prepay_card_sync_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1储值卡同步:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_prepay_card_sync_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /*
    * @desc 储值卡充值记录下发
    * @User guoxiansen
    */
    public function download_prepay_card_trade_sync($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0储值卡充值记录下发:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_prepay_card_trade_sync_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1储值卡充值记录下发:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_prepay_card_trade_sync_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /*
    * @desc 黑名单下发
    * @User guoxiansen
    */
    public function download_blackuser_sync($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0黑名单下发:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_prepay_card_trade_sync_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1黑名单下发:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_blackuser_sync_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    public function download_query_price($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0订单:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_query_price_log",1);
        if($park_total_record){
            $unserialize_desc = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1订单:'=>__LINE__,'unserialize_desc'=>$unserialize_desc],"park/{$park_total_record['park_id']}/download_blackuser_sync_log",1);
            echo json_encode($unserialize_desc,JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    public function download_deliver_ticket($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0优惠券:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_deliver_ticket_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1优惠券:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_deliver_ticket_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    //通道下发
    public function download_gate_sync($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0通道下发:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_gate_sync_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1通道下发:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_deliver_ticket_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    //支付成功
    public function download_prepay_order($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();

        fdump_api(['0支付成功:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_prepay_order_log",1);

        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            $data_target = $park_total_record['unserialize_desc'];
            if (!$data_target['prepay'] && $data_target['arrive_money']) {
                $data_target['prepay'] = floatval($data_target['arrive_money']);
            } elseif(!$data_target['prepay']) {
                $data_target['prepay'] = 0;
            }
            $data_target['pay_channel'] = str_replace('微信','', $data_target['pay_channel']);
            fdump_api(['1支付成功:'=>__LINE__,'data_target'=>$data_target],"park/{$park_total_record['park_id']}/download_prepay_order_log",1);
            echo json_encode($data_target,JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function download_outpark($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();

        fdump_api(['0出场:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_outpark_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1出场:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_outpark_log",1);
            // 处理下字段为应该返回的字段属性
//            M('Park_total_record')->where(array('id'=>$id))->data(array('status'=>1))->save();
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    public function download_query_prepay_card_balance($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();

        fdump_api(['0储值卡余额查询:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_query_prepay_card_balance_log",1);
        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1储值卡余额查询:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_query_prepay_card_balance_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    public function download_nolicence_in_park($id)
    {
        $park_total_record = M('Park_total_record')->where(array('id'=>$id,'status'=>2))->find();
        fdump_api(['0无牌车入场请求:'=>__LINE__,'id'=>$id,'Park_total_record'=>$park_total_record],"park/{$park_total_record['park_id']}/download_nolicence_in_park_log",1);

        if($park_total_record){
            $park_total_record['unserialize_desc'] = unserialize($park_total_record['unserialize_desc']);
            fdump_api(['1无牌车入场请求:'=>__LINE__,'unserialize_desc'=>$park_total_record['unserialize_desc']],"park/{$park_total_record['park_id']}/download_nolicence_in_park_log",1);
            echo json_encode($park_total_record['unserialize_desc'],JSON_UNESCAPED_UNICODE);exit();
        }
        $data['state'] = 0;
        $data['errmsg'] = '无消息!';
        $data['data'] = [];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }
}