<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      推送过来的消息接收
 */

namespace app\community\controller\common;


use app\common\model\service\plan\PlanMsgService;
use app\common\model\service\user\AppapiAppLoginLogService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\FaceDeviceService;
use app\consts\DahuaConst;
use app\consts\DeviceConst;
use message\Jpush;

class PushMessageController extends CommunityBaseController
{
    /**
     * callId	        string	必须		呼叫唯一ID
     * calleeDeviceKey	string	必须		被叫方设备序列号或手机号，多个手机号用逗号隔开
     * calleeGroupId	string	必须		被叫场所id
     * calleeSipId	    string	必须		被叫号码（房间号）
     * callerDeviceKey	string	必须		主叫方设备序列号
     * callerGroupId	string	必须		主叫场所编码
     * callerSipId	    string	必须		VTO设备序列号
     * msgType	        string	必须		事件类型。（callControlReq-获取被呼叫用户列表，callStatus-呼叫状态，callRecord-呼叫记录，sipDeviceStatus-可视对讲通道状态）
     * time	            number	必须		呼叫时间（utc 0时区的时间,单位为秒）
     * @return bool
     */
    public function dHYrNotice() {
        $input = empty($_POST) ? json_decode(file_get_contents("php://input"), true) : $_POST;
        $msgType = isset($input['msgType']) && $input['msgType'] ? $input['msgType'] : '';
        fdump_api(['input' => $input, 'msgType' => $msgType],'commonPushMessage/dHYrNoticeLog',1);
        if ($msgType == 'card.record') {
            // 开门记录
            $param = [
                'notice_type' => 'dHYrNotice',
            ];
            (new FaceDeviceService())->recordUserLog([$input], DeviceConst::DH_YUNRUI, $param);
        } elseif ($msgType == 'thirdAuthRecord') {
            // 授权结果通知
            (new FaceDeviceService())->thirdAuthDhRecord($input);
        }
        if ($msgType != 'callControlReq') {
            return true;
        }
        if (isset($input['calleeDeviceKey']) && $input['calleeDeviceKey']) {
            $phoneArr = explode(',', $input['calleeDeviceKey']);
        } else {
            fdump_api(['input' => $input],'commonPushMessage/dHYrNoticeLog',1);
            return true;
        }
        $deviceSn      = isset($input['callerDeviceKey']) && $input['callerDeviceKey'] ? $input['callerDeviceKey'] : '';
        $roomNum       = isset($input['calleeSipId'])     && $input['calleeSipId']     ? $input['calleeSipId']     : '';
        $callId        = isset($input['callId'])          && $input['callId']          ? $input['callId']          : '';
        $callerGroupId = isset($input['callerGroupId'])   && $input['callerGroupId']   ? $input['callerGroupId']   : '';
        $time          = isset($input['time'])            && $input['time']            ? $input['time']            : '';
        $title         = '智慧社区';
        $msg           = '您的好友正在通过门禁机邀请您视频，请点击进行视频沟通';
        
        $voice_second = 6;
        $voice_mp3 = '';
        $url       = '';
        $extra = array(
            'pigcms_tag'     => 'da_hua_yun_rui_visual',
            'tag_desc'       => $callId,
            'voice_mp3'      => $voice_mp3,
            'voice_second'   => $voice_second,
            'url'            => $url,
            'device_sn'      => $deviceSn,
            'room_num'       => $roomNum,
            'caller_groupId' => $callerGroupId,
            'call_time'      => $time,
        );
        
        $service = (new AppapiAppLoginLogService());
        $jpush = new Jpush();
        foreach ($phoneArr as $phone) {
            if (!$phone) {
                continue;
            }
            $whereUser = [];
            $whereUser[] = ['phone', '=', $phone];
            $whereUser[] = ['client', '<>', 0];
            $whereUser[] = ['device_id', '<>', 'packapp'];
            $login_log = $service->getOne($whereUser, true, 'create_time DESC');
            if (empty($login_log)) {
                continue;
            }
            if ($login_log && !is_array($login_log)) {
                $login_log = $login_log->toArray();
            }
            $whereDevice = [];
            $whereDevice[] = ['device_id','=', $login_log['device_id']];
            $whereDevice[] = ['client', '<>', 0];
            $login_device_last_log = $service->getOne($whereDevice, true, 'create_time DESC');
            if ($login_device_last_log && !is_array($login_device_last_log)) {
                $login_device_last_log = $login_device_last_log->toArray();
            }
            if ($login_device_last_log['phone'] != $phone) {
                fdump_api([$login_device_last_log, $phone, $login_log],'commonPushMessage/errDHYrNoticeLog',1);
                continue;
            }
            
            
            $client = $login_log['client'];
            if ($client == 1) {
                $device_id = str_replace('-', '', $login_log['device_id']);
                $audience = array('tag' => array($device_id));
            } else {
                $audience = array('tag' => array($login_log['device_id']));
            }
            if(!empty($login_log['jpush_registrationId'])){
                $audience['registration_id'] = [$login_log['jpush_registrationId']];
            }
            
            $notification = $jpush->createBody(3, $title, $msg, $extra);
            $message      = $jpush->createMsg($title, $msg, $extra);
            
            $columns = array();
            $columns['platform']     = $client == 1 ? array('ios') : array('android');
            $columns['audience']     = $audience;
            $columns['notification'] = $notification;
            $columns['message']      = $message;
            (new PlanMsgService())->addTask(array('type' => '4', 'content' => array($columns)));
        }
        return true;
    }
}