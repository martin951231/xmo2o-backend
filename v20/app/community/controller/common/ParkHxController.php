<?php
/**
 * Created by PhpStorm.
 *华夏t85设备
 * 
 */

namespace app\community\controller\common;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Park\ParkDevInfoHandleService;

class ParkHxController extends CommunityBaseController
{

    public function handleOpt()
    {
        //www.aihorse.net/v20/public/index.php/community/common.ParkHx/handleOpt
        $method=$this->request->post('type','','trim');
        switch ($method){
            case 'online':
                $this->plateresult();
                break;
            case 'offline':
                $this->plateresult();
                break;
            case 'heartbeat':
                //心跳
                $this->receivebeat();
                break;
            case 'ioinput':
                $this->gio();
                break;
            case 'rs485datain':
                $this->serial();
                break;
            case 'encrypt_verify':
                $this->encryptVerify();
                break;    
            default:
                $returnArr = array('error_num' => 1, 'error_str' => '未授权的请求');
                $this->json_out($returnArr);
                exit();
        }
    }
    public function encryptVerify(){
        $postArr = $this->request->post();
        fdump_api(['postArr'=>$postArr], 'hxPark/encryptVerify');
        if (!empty($postArr)) {
            /**
             * $type=$this->request->post('type','','trim');  //encrypt_verify  表示加密验证上报
             * $cam_id =$this->request->post('cam_id','','trim'); //相机ID
             * $cam_ip  =$this->request->post('cam_ip','','trim');  //相机IP
             * $cam_time =$this->request->post('cam_time','','trim');  //机时间字符串，格式：2022/4/18 15:08:13
             * $action =$this->request->post('action','','trim'); //  conn表示连接验证
             * $tmp_str  =$this->request->post('tmp_str ','','trim'); //  随机串  最长120个字符，长度不定
             * $version  =$this->request->post('version ','','trim'); //  加密验证版本号
             **/
            $parkDevInfoHandleService = new ParkDevInfoHandleService();
            $serialno = isset($postArr['cam_id']) && $postArr['cam_id'] ? trim($postArr['serialno']) : '';
            $whereArr = ['device_number' => $serialno, 'status' => '1'];
            $passageInfo=$parkDevInfoHandleService->getOnePassageInfo($whereArr);
            $pwd='admin123';
            if($passageInfo && $passageInfo['device_pwd']){
                $pwd=$passageInfo['device_pwd'];
            }
            $encryptVerifyStr=$parkDevInfoHandleService->encryptVerifyTxt($pwd);
            $returnArr = array('error_num' => 0, 'error_str' => 'ok');
            $returnArr['encrypt_verify_rsp']=array('sign_str'=>$encryptVerifyStr);
            fdump_api(['serialno'=>$serialno,'returnArr'=>$returnArr], 'hxPark/encryptVerify');
            $this->json_out($returnArr);
            exit();
        }
        $returnArr = array('error_num' => 0, 'error_str' => '访问成功');
        $this->json_out($returnArr);
        exit();
    }
    /**
     * 停车系统轮询心跳
     */
    public function receivebeat()
    {
        $postArr = $this->request->post();
        if (!empty($postArr)) {
            /**
             * $type=$this->request->post('type','','trim');  //heartbeat  表示是心跳
             * $interval=$this->request->post('interval','','trim');  //心跳间隔，单位秒
             * $park_id =$this->request->post('park_id','');  //车场ID 只支持英文、数字 可以是纯数字 或者英文数字混合 或者纯英文
             * $cam_id =$this->request->post('cam_id','','trim'); //相机ID
             *  $cam_ip  =$this->request->post('cam_ip','','trim');  //相机IP
             */
            $parkDevInfoHandleService = new ParkDevInfoHandleService();
            $serialno = isset($postArr['cam_id']) && $postArr['cam_id'] ? trim($postArr['cam_id']) : '';
            $showScreenCacheKey = $serialno . '_show_screen_';
            $showScreenCacheEffective = 20;
            $receiveTime = date('mdH');
            try {
                fdump_api(['轮询心跳' => $serialno, $postArr], 'hxPark/heartbeat_' . $serialno . '_log');

                $res = $parkDevInfoHandleService->open_gate($postArr);

            } catch (\Exception $e) {
                $txt = $e->getMessage();
                fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $postArr], 'hxPark/errReceivedeviceinfo', true);
                $tempScreenTxt = $parkDevInfoHandleService->showTempScreenTxt($txt, 1);
                $showScreenCacheKey .= md5($tempScreenTxt);
                $showScreenCacheTime = cache($showScreenCacheKey);
                if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                    fdump_api(['推送车牌识别反馈重复' => $serialno, 'err' => $e->getMessage(), 'showScreenCacheTime' => $showScreenCacheTime], 'hxPark/heartbeat_' . $serialno . '_log', 1);
                    echo json_encode(['code' => 200, 'msg' => 'OK']);
                    exit();
                }
                $serialData=array();
                $serialData[] = [
                    'encodetype' => 'hex2string',
                    'data' => $tempScreenTxt,
                ];
                $returnArr = array('error_num' => 1, 'error_str' => $txt);
                $returnArr['rs485_data'] =$serialData;
                cache($showScreenCacheKey, time(), $showScreenCacheEffective);
                $this->json_out($returnArr);
                exit();
            }

            //正常的开始
            $returnArr = array('error_num' => 0, 'error_str' => 'ok');
            $serialData = [];
            $white_list_operate = [];
            $show_screen_data=array();
            if(isset($res['showscreen']) && $res['showscreen']){
                $show_screen_data=$res['showscreen'];
            }
            $showcontent = array();
            if($show_screen_data && isset($show_screen_data['showcontent']) && $show_screen_data['showcontent']){
                $showcontent=json_decode($show_screen_data['showcontent'], true);
            }
            if ($show_screen_data && $show_screen_data['content_type'] == 1 && empty($showcontent)) {
                //显屏
                $txt = $show_screen_data['content'];
                $tempScreenTxt = $parkDevInfoHandleService->showTempScreenTxt($txt, $show_screen_data['screen_row']);
                $showScreenCacheKey .= md5($tempScreenTxt);
                $showScreenCacheTime = cache($showScreenCacheKey);
                if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                    fdump_api(['推送车牌识别反馈重复' => $serialno, 'txt' => $txt, 'tempScreenTxt' => $tempScreenTxt, 'showScreenCacheTime' => $showScreenCacheTime], 'hxPark/heartbeat_' . $serialno . '_log', 1);
                    $this->json_out($returnArr);
                    exit();
                }
                $serialData[] = [
                    'encodetype' => 'hex2string',
                    'data' => $tempScreenTxt,
                ];
            }
            if ($show_screen_data && $show_screen_data['content_type'] == 2 && !$showcontent) {
                //语音
                $voiceData = $parkDevInfoHandleService->showVoice($show_screen_data);
                if($voiceData){
                    $showScreenCacheKey .= md5($voiceData);
                    $showScreenCacheTime = cache($showScreenCacheKey);
                    if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                        fdump_api(['推送车牌识别反馈重复' => $serialno,'voiceData'=>$voiceData, 'showScreenCacheTime' => $showScreenCacheTime], 'hxPark/heartbeat_' . $serialno . '_log', 1);
                        $this->json_out($returnArr);
                        exit();
                    }
                    $serialData[] = [
                        'encodetype' => 'hex2string',
                        'data' => $voiceData,
                    ];
                }
            }
            if (isset($res['white_list']) && !empty($res['white_list'])) {
                $operate_type = $res['white_list']['operate_type'];//操作类型(0:增加，1：删除)
                //白名单数组：单次操作，最大支持 5 条
                $white_list_data=array();
                $operate_type_str='add';
                if($operate_type==1){
                    $operate_type_str='delete';
                }
                $typeStr='W';
                if($res['white_list']['need_alarm']==1){
                    $typeStr='B'; //表示黑名单
                }
                $white_list_data[] = [
                    'Action' => $operate_type_str,
                    'PlateNumber' => $res['white_list']['car_number'],//车牌号
                    'Type'=>$typeStr,
                    'Start' => date('Y/m/d H:i:s', $res['white_list']['start_time']),//当前名单生效时间，如：2018/01/01 11:11:11
                    'End' => date('Y/m/d H:i:s', $res['white_list']['end_time']),//当前名单过期时间，如：2018/01/01 11:11:11
                ];
                $white_list_operate=$white_list_data;
            }
            if($showcontent || !empty($serialData)){
                $returnArr['rs485_data'] = $showcontent ? $showcontent : $serialData;
            }
            if(!empty($white_list_operate)){
                $returnArr['whitelist_data'] = $white_list_operate;
            }
            //数据处理结束
            cache($showScreenCacheKey, time(), $showScreenCacheEffective);
            
            if ($res['data'] == true) {
                $returnArr['error_num'] =0;
                $returnArr['error_str'] ='正常通行';
                $returnArr['gpio_data'] = array(array('ionum' => 'io1', 'action' => 'on'));
                fdump_api(['serialno' => $serialno, 'res' => $res], 'hxPark/heartbeat_' . $receiveTime . '_log', 1);
                $this->json_out($returnArr);
                exit();
            } else {
                $returnArr['error_num'] =0;
                $returnArr['error_str'] ='OK';
                $this->json_out($returnArr);
                exit();
            }
        }
        $returnArr = array('error_num' => 0, 'error_str' => '访问成功');
        $this->json_out($returnArr);
        exit();
    }

    /**
     * 停车系统推送车牌识别结果
     */
    public function plateresult()
    {
        $postArr = $this->request->post();
        /*
        $type=$this->request->post('type','','trim');  //固定online  或offline示断网续传结果
        $car_number=$this->request->post('plate_num','','trim');  //车牌号码
        $plate_val =$this->request->post('plate_val','');  //虚假车牌信息，true表示真牌，false表示虚假车牌 
        $confidence =$this->request->post('confidence',0,'intval'); //置信度，范围：0-28 
        $car_logo  =$this->request->post('car_logo','','trim');  //车辆品牌  丰田
        $car_sublogo =$this->request->post('car_sublogo','','trim');  //车辆子品牌 卡罗拉
        $car_color =$this->request->post('car_color','','trim');  //车辆颜色  白色
        $vehicle_type =$this->request->post('vehicle_type','','trim');  //车辆类型 轿车 卡车
        $start_time =$this->request->post('start_time',0,'intval');  //车牌识别时间 时间戳
        $park_id  =$this->request->post('park_id','');  //车场ID 只支持英文、数字 可以是纯数字 或者英文数字混合 或者纯英文
        
        $cam_id   =$this->request->post('cam_id','','trim'); //相机ID 相机ID号根据配置决定是使用MAC还是UID 我们用uid
        $cam_ip   =$this->request->post('cam_ip','','trim'); //相机IP
        $vdc_type   =$this->request->post('vdc_type','','trim'); //出入口类型 in表示入口，out表示出口 

        $is_whitelist =$this->request->post('is_whitelist','');  //是否是白名单车辆，true表示白名单，false表示非白名单 
        $triger_type   =$this->request->post('triger_type','','trim'); //video 表示视频触发，hwtriger表示地感触发，swtriger 表示软触发
        $picture =$this->request->post('picture','');   //全景图 BASE64编码 大图
        $closeup_pic =$this->request->post('closeup_pic',''); //车牌特写图 BASE64编码 小图
        */
        $parkTime = date('YmdH');
        fdump_api(['postArr' => $postArr], 'hxPark/plateresult' . $parkTime, true);

        if (!empty($postArr) && isset($postArr['cam_id']) && $postArr['cam_id'] && isset($postArr['plate_num']) && $postArr['plate_num']) {

            $cam_id = trim($postArr['cam_id']);
            $car_number = trim($postArr['plate_num']);

            $parkDevInfoHandleService = new ParkDevInfoHandleService();
            try {
                $res = $parkDevInfoHandleService->add_park_info($postArr, $parkTime);
                $returnArr = array('error_num' => 0, 'error_str' => '正常通行');
                fdump_api(['推送车牌识别结果' => $res, 'cam_id' => $cam_id, 'car_number' => $car_number], 'hxPark/plateresult' . $parkTime, true);
                if ($res && $res['can_open_gate']) {
                    //开闸json指令 '{"error_num":0,"error_str":"noerror","gpio_data":[{"ionum":"io1","action":"on"}]}';
                    $returnArr['gpio_data'] = array(array('ionum' => 'io1', 'action' => 'on'));
                    fdump_api(['event_opt' => '开闸指令数据下发', 'returnArr' => $returnArr, 'cam_id' => $cam_id, 'car_number' => $car_number], 'hxPark/plateresult' . $parkTime, true);
                    $this->json_out($returnArr, true);
                    exit;
                } else {
                    $returnArr['error_num'] = 1;
                    $returnArr['error_str'] = $res && $res['err_msg'] ? $res['err_msg'] : '开闸失败';
                    $this->json_out($returnArr);
                    exit;
                }
            } catch (\Exception $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'hxPark/plateresult' . $parkTime, true);
                $txt = $e->getMessage();
                $tempScreenTxt = $parkDevInfoHandleService->showTempScreenTxt($txt, 1);
                $returnArr = array('error_num' => 1, 'error_str' => $txt);
                $serialData = [
                    'encodetype' => 'hex2string',
                    'data' => $tempScreenTxt,
                ];
                $returnArr['rs485_data'] = array();
                $returnArr['rs485_data'][] = $serialData;
                fdump_api(['event_opt' => '开闸异常错误', 'returnArr' => $returnArr, 'car_number' => $car_number, 'txt' => $txt], 'hxPark/plateresult' . $parkTime, true);
                $this->json_out($returnArr);
                exit;
            }

        } else {
            $returnArr = array('error_num' => 1, 'error_str' => '车牌数据为空');
            $this->json_out($returnArr);
            exit;
        }
    }

    function json_out($data = array(), $debug = false)
    {
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $lengthStr = strlen($jsonData);
        header('Content-type:application/json;charset=utf-8');
        header('content-length:' . $lengthStr);
        echo $jsonData;
        if ($debug) {
            fdump_api(['data' => $data, 'jsonData' => $jsonData,'lengthStr'=>$lengthStr], 'hxPark/plateresult' . date('YmdH'), true);
        }
        exit();
    }

    /**
     * 停车系统推送端口触发信息
     */
    public function gio()
    {
        
        $postArr = $this->request->post();
        fdump_api(['推送端口触发信息', $_GET, 'post'=>$_POST,'postArr'=>$postArr], 'hxPark/gio', true);
        /**
            $type=$this->request->post('type','','trim');  //ioinput  表示是IO事件上报
            $ionum=$this->request->post('ionum','','trim');  //输入IO口  不同型号设备，IO数量不同，输入IO口取值范围不同，如二输入设备是1-2，四输入设备是1-4 
            $park_id =$this->request->post('park_id','','trim'); //车场ID  只支持英文、数字
         *  $iostatus =$this->request->post('iostatus','');  //IO 状态 0/1
            $cam_id =$this->request->post('cam_id','','trim'); //相机ID
         *  $vehicle_type =$this->request->post('vehicle_type','','trim');  //车辆类型 轿车 卡车
         *  $start_time  =$this->request->post('start_time ',0,'intval');  //状态变化时间，1970/01/01到现在的秒数目
         **/
        if (!empty($postArr)) {
            fdump_api(['dataArr' => $postArr], 'hxPark/gio', true);
            if (!empty($data) && isset($data['cam_id']) && !empty($data['cam_id'])) {
                $param = array();
                $param['serialno'] = $data['cam_id'];
                $param['device_name'] = '';
                $param['channel'] = '';
                $param['opt_source'] = -1;
                $param['opt_value'] = -1;
                if (isset($data['iostatus']) && $data['iostatus']<1) {
                    $param['opt_source'] = 0;  //0开闸 1关闸 其他值暂时用不上
                    $param['opt_value'] = 0; //0 表示开了 是1的  表示复位了 暂时用不上
                    if ($param['opt_source'] === 0 && $param['opt_value'] === 0) {
                        //iostatus 0的时候是开闸，1的时候继电器断开闸也不会落，就是开闸信号断开 

                        $parkDevInfoHandleService = new ParkDevInfoHandleService();
                        $parkDevInfoHandleService->addPortData($param);

                    }
                }

            }
        }
        $returnArr = array('error_num' => 0, 'error_str' => '访问成功');
        $this->json_out($returnArr);
        exit;
    }

    /**
     * 停车系统推送串口数据
     * 未调试对接
     */
    public function serial()
    {
        $serialTime = date('YmdH');
        
        $postArr = $this->request->post();
        fdump_api(['推送串口数据', $_GET, 'post'=>$_POST,'postArr'=>$postArr], 'hxPark/serial' . $serialTime, true);
        /**
        $type=$this->request->post('type','','trim');  //rs485datain  表示是RS485接收数据事件上报
        $park_id =$this->request->post('park_id','','trim'); //车场ID  只支持英文、数字
        $cam_id =$this->request->post('cam_id','','trim'); //相机ID
        $start_time  =$this->request->post('start_time ',0,'intval');  //状态变化时间，1970/01/01到现在的秒数目
        $data_type =$this->request->post('data_type',''); //数据编码类型 只支持hex2string
        $dataStr =$this->request->post('data','','trim');  //hex2string数据类型时，1字节16进制数据会转成2个字符，如0xAF会转成2个字符"AF" 
         *  
         **/
        
        $returnArr = array('error_num' => 0, 'error_str' => '访问成功');
        $this->json_out($returnArr);
        exit;
        if (!empty($postArr)) {
            $parkDevInfoHandleService = new ParkDevInfoHandleService();
            $msg = isset($postArr['data']) ? $postArr['data']:'';
            $serialno=isset($postArr['cam_id']) ? $postArr['cam_id']:'';
            try {
                if (!empty($msg) && !empty($serialno)) {
                    $param = [
                        'serialno' => $serialno,
                        'msg' => $msg,
                        'content' => $msg,
                        'serialTime' => $serialTime,
                    ];

                    //$parkDevInfoHandleService->addSerialData($param);

                }
            } catch (\Exception $exception) {
                fdump_api(['推送串口数据--错误了' => $exception->getMessage()], 'hxPark/serial' . $serialTime, true);
            }
        }
        $returnArr = array('error_num' => 0, 'error_str' => '访问成功');
        $this->json_out($returnArr);
        exit;
    }

}