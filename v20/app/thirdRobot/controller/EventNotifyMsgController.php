<?php
/**
 * 订阅事件推送异步通知地址
 * v20/public/index.php/thirdRobot/EventNotifyMsg/receiveMsg
 */
/*
declare (strict_types=1);
*/
namespace app\thirdRobot\controller;
use app\thirdRobot\model\service\ThirdRobotDeviceService;
use app\thirdRobot\model\service\ThirdRobotSubscribeMessageService;
class EventNotifyMsgController
{
    public function receiveMsg()
    {
        $input = file_get_contents("php://input");
        $post=$_POST;
        fdump_api(['receiveMsg'.__LINE__,'input'=>$input,'post'=>$post],'000EventNotifyMsg',1);
        if(empty($post)){
            $post=array();
        }
        if(!empty($input)){
            $inputData = json_decode($input, true);
            if ($inputData && !empty($inputData) && is_string($input)) {
                $input = str_replace('"[{', '[{', $input);
                $input = str_replace('}]"', '}]', $input);
                $input = stripslashes($input);
            }
            if (!$inputData || empty($inputData)) {
                $inputData = is_array($input) ? $input : json_decode($input, true);
            }
            if ($inputData && !empty($inputData) && !empty($post)) {
                $inputData = array_merge($post, $inputData);
            }
            $post = $inputData;
        }
        $responseArr=array('code'=>0,'message'=>'success','date_time'=>date('Y-m-d H:i:s'));
        $type = !empty($post) && isset($post['type']) ? trim($post['type']) : '';
        $nowtime = time();
        if ($type == 'event' && isset($post['params'])&& !empty($post['params'])) {
            $params =$post['params'];
            if(!is_array($params) && is_string($params) ){
                $params =json_decode($params, true);
            }
            if($params &&  isset($params['robotCode']) && !empty($params['robotCode'])){
                $msg_type_code=isset($params['msgTypeCode']) ? trim($params['msgTypeCode']):0;
                $robot_code=trim($params['robotCode']);
                $thirdRobotSubscribeMessageService=new ThirdRobotSubscribeMessageService();
                $messageArr=array();
                $messageArr['type']=$type;
                $messageArr['robot_code']=$robot_code;
                $messageArr['response_data']=json_encode($post,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                $messageArr['add_time']=$nowtime;
                $unique_key=$type.'_'.$robot_code.'_'.md5($messageArr['response_data']);
                $messageArr['unique_key']=$unique_key;
                try {
                    $thirdRobotSubscribeMessageService->addOneRobotMessage($messageArr);
                }catch (\Exception $e){
                    $responseArr['code']=1;
                    $responseArr['message']='重复通知!';
                    echo json_encode($responseArr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    exit();
                }
                $thirdRobotDeviceService = new ThirdRobotDeviceService();
                $whereArr = array('robot_code' => $robot_code);
                $oneRobotDevice = $thirdRobotDeviceService->getOneDataInfo($whereArr);
                if(!empty($oneRobotDevice)){
                    if($msg_type_code=='2100001'){
                        //设备上线
                        $saveData=array('online_flag'=>1,'update_time'=>$nowtime,'del_time'=>0);
                        $thirdRobotDeviceService->saveOneRobotInfo(['id'=>$oneRobotDevice['id']],$saveData);
                    }else if($msg_type_code=='2100002'){
                        //设备下线
                        $saveData=array('online_flag'=>0,'update_time'=>$nowtime,'del_time'=>0);
                        $thirdRobotDeviceService->saveOneRobotInfo(['id'=>$oneRobotDevice['id']],$saveData);
                    }else if($msg_type_code=='2100028'){
                        //人脸黑名单
                    }
                }else{
                    //队列更新机器人列表
                }
            }
        }
        echo json_encode($responseArr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit();
    }

}

