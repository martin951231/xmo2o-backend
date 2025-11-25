<?php


namespace app\community\model\service;
use app\community\model\db\ProcessSubPlan;
use app\community\model\db\WorkMsgAuditInfo;
use app\community\model\db\WorkMsgAuditInfoText;
use app\community\model\db\WorkMsgAuditInfoImage;
use app\community\model\db\WorkMsgAuditInfoRevoke;
use app\community\model\db\WorkMsgAuditInfoAgree;
use app\community\model\db\WorkMsgAuditInfoVideo;
use app\community\model\db\WorkMsgAuditInfoCard;
use app\community\model\db\WorkMsgAuditInfoLocation;
use app\community\model\db\WorkMsgAuditInfoEmotion;
use app\community\model\db\WorkMsgAuditInfoFile;
use app\community\model\db\WorkMsgAuditInfoLink;
use app\community\model\db\WorkMsgAuditInfoWeapp;
use app\community\model\db\WorkMsgAuditInfoTodo;
use app\community\model\db\WorkMsgAuditInfoVote;
use app\community\model\db\WorkMsgAuditInfoRedpacket;
use app\community\model\db\WorkMsgAuditInfoMeeting;
use app\community\model\db\WorkMsgAuditInfoDocmsg;
use app\community\model\db\WorkMsgAuditInfoMarkdown;
use app\community\model\db\WorkMsgAuditInfoNews;
use app\community\model\db\WorkMsgAuditInfoCalendar;
use app\community\model\db\WorkMsgAuditInfoCalendarAttendee;
use app\community\model\db\WorkMsgAuditInfoCollect;
use app\community\model\db\WorkMsgAuditInfoCollectDetails;
use app\community\model\db\WorkMsgAuditInfoChatrecordItem;
use app\community\model\db\WorkMsgAuditInfoChatrecord;
use app\community\model\db\WorkMsgAuditInfoMixed;
use app\community\model\db\WorkMsgAudit;
use app\community\model\db\WorkMsgAuditKey;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseContactWayUser;
use app\community\model\db\WorkMsgAuditInfoVoice;
use app\community\model\db\WorkMsgAuditInfoGroup;
use app\community\model\service\EnterpriseWeChatService;
use think\facade\Cache;

class SessionFileService
{
    //存储记录
    public function storeRecord($param)
    {
        $property_id = '';
        $dbWorkMsgAuditInfo = new WorkMsgAuditInfo();
        $dbWorkMsgAudit = new WorkMsgAudit();
        $dbHouseWorker = new HouseWorker();
        $dbHouseContactWayUser = new HouseContactWayUser();
        $audit_data = $dbWorkMsgAudit->getFind(['corp_id'=>$param['corpid']]);
        $dbWorkMsgAudit->editFind(['corp_id'=>$param['corpid']],['seq'=>$param['seq']]);
        $msg_data = $param['msg_data'];
        $corpid = $param['corpid'];
        if(!array_key_exists('tolist',$msg_data) || !array_key_exists('msgtype',$msg_data) ){
            return true;
        }
        if(!in_array($msg_data['msgtype'],['text','image','revoke','disagree','agree','voice','video','card','location','emotion','file','link','weapp','chatrecord','todo','collect','redpacket','meeting','docmsg','markdown','news','calendar','mixed']))
        {
            return true;
        }
        $tolist = implode(',',$msg_data['tolist']);
        $data = [
            'msgid'=>$msg_data['msgid'],
            'action'=>$msg_data['action'],
            'from'=>$msg_data['from'],
            'tolist'=>$tolist,
            'audit_id'=>$audit_data['id'],
            'msgtype'=>$msg_data['msgtype'],
        ];
        if($msg_data['roomid']){
            $data['roomid'] = $msg_data['roomid'];
        }
        $msgtime = explode('.',$msg_data['msgtime']);
        $data['msgtime'] = $msgtime[0];
        $msg_type = substr($msg_data['msgid'],-1,9);
        if($msg_type == '_external'){
            //表明是外部消息
        }
        $to_user_arr = explode('_',$msg_data['from']);

        if($to_user_arr[0] == 'worker'){
            $data['from_type'] = 1;
            $whereArr=array();
            $whereArr[] = ['status','<',4];
            $whereArr[] = ['is_del','<',1];
            $whereArr[]=['qy_id','=',$msg_data['from']];
            $worker_info = $dbHouseWorker->get_one($whereArr,'wid');
            $data['user_id'] = $worker_info['wid'];
        }else {
            $member_type = substr($msg_data['from'], 0, 2);
            if ($member_type == 'wb') {
                $data['from_type'] = 3;
                $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' => $msg_data['from'], 'corpid' => $param['corpid']], 'customer_id');
                $data['external_id'] = $way_user['customer_id'];
            } elseif ($member_type == 'wo' || $member_type == 'wm') {
                $data['from_type'] = 2;
                $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' => $msg_data['from'], 'corpid' => $param['corpid']], 'customer_id');
                $data['external_id'] = $way_user['customer_id'];
            } else {
                $data['from_type'] = 1;
                $whereArr=array();
                $whereArr[] = ['status','<',4];
                $whereArr[] = ['is_del','<',1];
                $whereArr[]=['qy_id','=',$msg_data['from']];
                $worker_info = $dbHouseWorker->get_one($whereArr, 'wid');
                $data['user_id'] = $worker_info['wid'];
            }
        }
        if(!$msg_data['roomid']){//为空 不是群里 是单聊
            $to_user_id = $msg_data['tolist'][0];
            $to_user_arr = explode('_',$to_user_id);
            if($to_user_arr[0] == 'worker'){
                $data['to_type'] = 1;
                $whereArr=array();
                $whereArr[] = ['status','<',4];
                $whereArr[] = ['is_del','<',1];
                $whereArr[]=['qy_id','=',$to_user_id];
                $worker_info = $dbHouseWorker->get_one($whereArr,'wid');
                $data['to_user_id'] = $worker_info['wid'];
            }else {
                $member_type = substr($to_user_id, 0, 2);
                if ($member_type == 'wb') {
                    $data['to_type'] = 3;
                    $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' => $to_user_id, 'corpid' => $param['corpid']], 'customer_id');
                    $data['to_external_id'] = $way_user['customer_id'];
                } elseif ($member_type == 'wo' || $member_type == 'wm') {
                    $data['to_type'] = 2;
                    $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' => $to_user_id, 'corpid' => $param['corpid']], 'customer_id');
                    $data['to_external_id'] = $way_user['customer_id'];
                } else {
                    $data['to_type'] = 1;
                    $whereArr=array();
                    $whereArr[] = ['status','<',4];
                    $whereArr[] = ['is_del','<',1];
                    $whereArr[]=['qy_id','=',$to_user_id];
                    $worker_info = $dbHouseWorker->get_one($whereArr, 'wid');
                    $data['to_user_id'] = $worker_info['wid'];
                }
            }
        }
        if($msg_data['roomid']){
            $serviceEnterpriseWeChat = new EnterpriseWeChatService();
            $serviceWorkMsgAuditInfoGroup =new WorkMsgAuditInfoGroup();
            if(!$property_id){
                $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
                $wx_bind_info = $dbHouseEnterpriseWxBind->getOne(['corpid'=>$corpid,'bind_type'=>0],'bind_id');
                $property_id = $wx_bind_info['bind_id'];
            }
            $group_data = $serviceEnterpriseWeChat->groupChat($property_id,$msg_data['roomid'],2);
            if($group_data && $group_data['errcode'] == 0 && $group_data['group_chat'] && $group_data['group_type'] == 2){
                $group_type = 2;
                $group_data = $group_data['group_chat'];
                $member_userid = [];
                $member_external_id = [];
                foreach ($group_data['member_list'] as $v)
                {
                    $to_user_arr = explode('_',$v['userid']);
                    if($to_user_arr[0] == 'worker') {
                        $member_userid[] = $this->getOnlineGroupMember($property_id, $v['userid'], $corpid,1);
                    }else{
                        $member_external_id[] = $this->getOnlineGroupMember($property_id, $v['userid'], $corpid,2);
                    }
                }

                $member_userid_str = implode(',',$member_userid);
                $groups_data['member_userid'] = $member_userid_str;

                $external_userid_str = implode(',',$member_external_id);
                $groups_data['member_external_id'] = $external_userid_str;

                $groups_data['members'] = json_encode($group_data['member_list'],JSON_UNESCAPED_UNICODE);
                $groups_data['admin_list'] = json_encode($group_data['admin_list'],JSON_UNESCAPED_UNICODE);
                $room_create_time= $group_data['create_time'];
                $creator = $group_data['owner'];
                $roomname = $group_data['name'];

            }else{
                $group_type = 1;
                $group_data = $serviceEnterpriseWeChat->groupChat($property_id,$msg_data['roomid']);
                $member_userid = [];
                $member_external_id = [];
                foreach ($group_data['members'] as $v)
                {
                    $to_user_arr = explode('_',$v['memberid']);
                    if($to_user_arr[0] == 'worker') {
                        $member_userid[] = $this->getOnlineGroupMember($property_id, $v['memberid'], $corpid,1);
                    }else{
                        $member_external_id[] = $this->getOnlineGroupMember($property_id, $v['memberid'], $corpid,2);
                    }
                }

                $member_userid_str = implode(',',$member_userid);
                $groups_data['member_userid'] = $member_userid_str;

                $external_userid_str = implode(',',$member_external_id);
                $groups_data['member_external_id'] = $external_userid_str;

                $groups_data['members'] = json_encode($group_data['members'],JSON_UNESCAPED_UNICODE);
                $room_create_time= $group_data['room_create_time'];
                $creator = $group_data['creator'];
                $roomname = $group_data['roomname'];
            }
//            $groups_data['audit_info_id'] = $audit_info_id;
            $owner = explode('_',$creator);
            if($owner[0] == 'worker') {
                $owner_id = $this->getOnlineGroupMember($property_id, $owner, $corpid,1);
                $data['owner_type'] = 1;
            }else{
                $owner_id = $this->getOnlineGroupMember($property_id, $owner, $corpid,2);
                $data['owner_type'] = 2;
            }
            $data['owner_id'] = $owner_id;
            $groups_data['group_type'] = $group_type;
            $groups_data['roomname'] = $roomname;
            $groups_data['creator'] = $creator;
            $groups_data['room_create_time'] = $room_create_time;
            $groups_data['notice'] = $group_data['notice'];
            $groups_data['group_id'] = $msg_data['roomid'];
            $info = $serviceWorkMsgAuditInfoGroup->getFind(['group_type'=>$group_type,'group_id'=>$msg_data['roomid']]);
            if($info){
                $serviceWorkMsgAuditInfoGroup->editFind(['id'=>$info['id']],['members'=>$groups_data['members'],'roomname'=>$roomname,'member_userid'=>$member_userid_str,'member_external_id'=>$external_userid_str]);
                $chat_id = $info['id'];
            }else{
                $chat_id = $serviceWorkMsgAuditInfoGroup->addFind($groups_data);
            }
            $data['chat_id'] = $chat_id;
        }
        $audit_info = $dbWorkMsgAuditInfo->getFind(['msgid'=>$msg_data['msgid']]);
        if($audit_info){
            $audit_info_id = $audit_info['id'];
        }else{
            $audit_info_id = $dbWorkMsgAuditInfo->addFind($data);
        }
        $content = $msg_data[$msg_data['msgtype']];
        $msgtype = $msg_data['msgtype'];
//        if($msgtype == 'mixed'){//混合消息特殊处理
//            $dbWorkMsgAuditInfoMixed = new WorkMsgAuditInfoMixed();
//            $item_mixed = $content['item'];
//            foreach ($item_mixed as $value){
//                $result = $this->disposeType($audit_info_id,$value['type'],$value['content'],$corpid,false);
//                $result['audit_info_id'] = $audit_info_id;
//                $result['type'] = $value['type'];
//                $mixed_info = $dbWorkMsgAuditInfoMixed->getFind($result);
//                if(!$mixed_info){
//                    $dbWorkMsgAuditInfoMixed->addFind($result);
//                }
//            }
//            $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '混合消息']);
//
//        }else{
            $this->disposeType($audit_info_id,$msgtype,$content,$corpid,true);
//        }
        return true;
    }
    public function disposeType($audit_info_id,$msgtype,$content,$corpid,$is_mixed)
    {
        $data = [];
        $dbWorkMsgAuditInfo = new WorkMsgAuditInfo();
        $dbHouseWorker = new HouseWorker();
        $dbHouseContactWayUser = new HouseContactWayUser();
        switch ($msgtype){
            case 'text':
                //文本
                $dbWorkMsgAuditInfoText = new WorkMsgAuditInfoText();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'content'=>$content['content'],
                    'content_convert'=>json_encode($content,JSON_UNESCAPED_UNICODE),
                ];
                $text_info = $dbWorkMsgAuditInfoText->getFind(['audit_info_id'=>$audit_info_id]);
                if($text_info){
                    $text_id = $text_info['id'];
                }else{
                    $text_id = $dbWorkMsgAuditInfoText->addFind($new_data);
//                    $arr= array();
//                    $arr['param'] = serialize(array('text_id'=>$text_id));
//                    $arr['plan_time'] = -100;
//                    $arr['space_time'] = 0;
//                    $arr['add_time'] = time();
//                    $arr['file'] = 'sub_violation_monitor';
//                    $arr['time_type'] = 1;
//                    $arr['unique_id'] = 'violation_monitor_'.$text_id;
//                    $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
//                    $process_sub_plan=new ProcessSubPlan();
//                    $process_sub_plan->add($arr);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => $content['content']]);
                }
                $data['text_id'] = $text_id;
                break;
            case 'image':
                //图片
                $dbWorkMsgAuditInfoImage = new WorkMsgAuditInfoImage();
                $file_path =  $this->storageFile($content['file_name'],$content['file']);
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'md5sum'=>$content['md5sum'],
                    'filesize'=>$content['filesize'],
                    'file_path'=>$file_path,
                    'is_finish'=>$content['media_data']['is_finish'],
                    'sdkfileid'=>$content['sdkfileid'],
                ];
                if(isset($content['media_data']['indexbuf'])){
                    $new_data['indexbuf'] = $content['media_data']['indexbuf'];
                }
                $image_info = $dbWorkMsgAuditInfoImage->getFind(['audit_info_id'=>$audit_info_id,'sdkfileid'=>$content['sdkfileid']]);
                if($image_info){
                    $image_id = $image_info['id'];
                }else{
                    $image_id = $dbWorkMsgAuditInfoImage->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '图片']);
                }
                $data['image_id'] = $image_id;
                break;
            case 'revoke':
                //撤回消息
                $dbWorkMsgAuditInfoRevoke = new WorkMsgAuditInfoRevoke();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'pre_msgid'=>$content['pre_msgid'],
                ];
                if(isset($content['pre_info_id'])){
                    $new_data['pre_info_id'] = $content['pre_info_id'];
                }else{
                    $new_data['pre_info_id'] =$corpid;
                }
                $revoke_info = $dbWorkMsgAuditInfoRevoke->getFind($new_data);
                if($revoke_info){
                    $revoke_id = $revoke_info['id'];
                }else{
                    $revoke_id = $dbWorkMsgAuditInfoRevoke->addFind($new_data);
                }
                break;
            case 'disagree'://不同意会话聊天内容
                $dbWorkMsgAuditInfoAgree = new WorkMsgAuditInfoAgree();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'userid'=>$content['userid'],
                    'agree_type'=>0,
                    'agree_time'=>$content['disagree_time'],
                ];
                $member_types = substr($content['userid'],0,2);
                if($member_types == 'wb' || $member_types == 'wo' || $member_types == 'wm'){
                    $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' =>$content['userid'], 'corpid' => $corpid], 'customer_id');
                    $new_data['external_id'] = $way_user['customer_id'];
                }else{
                    $whereArr=array();
                    $whereArr[] = ['status','<',4];
                    $whereArr[] = ['is_del','<',1];
                    $whereArr[]=['qy_id','=',$content['creatorname']];
                    $worker_info = $dbHouseWorker->get_one($whereArr, 'wid');
                    $new_data['user_id'] = $worker_info['wid'];
                }
                $agree_info = $dbWorkMsgAuditInfoAgree->getFind(['audit_info_id'=>$audit_info_id,'userid'=>$content['userid'],'agree_type'=>0]);
                if(!$agree_info){
                    $agree_id = $dbWorkMsgAuditInfoAgree->addFind($new_data);
                }else{
                    $agree_id = $agree_info['id'];
                }
                break;
            case 'agree'://同意会话聊天内容
                $dbWorkMsgAuditInfoAgree = new WorkMsgAuditInfoAgree();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'userid'=>$content['userid'],
                    'agree_type'=>1,
                    'agree_time'=>$content['agree_time'],
                ];
                $member_types = substr($content['userid'],0,2);
                if($member_types == 'wb' || $member_types == 'wo' || $member_types == 'wm'){
                    $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' =>$content['userid'], 'corpid' => $corpid], 'customer_id');
                    $new_data['external_id'] = $way_user['customer_id'];
                }else{
                    $whereArr=array();
                    $whereArr[] = ['status','<',4];
                    $whereArr[] = ['is_del','<',1];
                    $whereArr[]=['qy_id','=',$content['creatorname']];
                    $worker_info = $dbHouseWorker->get_one($whereArr, 'wid');
                    $new_data['user_id'] = $worker_info['wid'];
                }
                $agree_info = $dbWorkMsgAuditInfoAgree->getFind(['audit_info_id'=>$audit_info_id,'userid'=>$content['userid'],'agree_type'=>1]);
                if(!$agree_info){
                    $agree_id = $dbWorkMsgAuditInfoAgree->addFind($new_data);
                }else{
                    $agree_id = $agree_info['id'];
                }

                break;
            case 'voice':
                //语音
                $dbWorkMsgAuditInfoVoice = new WorkMsgAuditInfoVoice();
                $file_path_arr = explode(',',$content['file_name']);
                $file_names = $file_path_arr[0];
//                $file_path =  $this->storageFile($file_names.'.mp3',$content['file']);
                $file_path =  $this->storageFile($content['file_name'],$content['file'],'voice');
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'voice_size'=>$content['voice_size'],
                    'play_length'=>$content['play_length'],
                    'sdkfileid'=>$content['sdkfileid'],
                    'md5sum'=>$content['md5sum'],
                    'voice_path'=>$file_path,
                    'is_finish'=>$content['media_data']['is_finish'],
                ];
                if(isset($content['media_data']['indexbuf'])){
                    $new_data['indexbuf'] = $content['media_data']['indexbuf'];
                }
                $voice_info = $dbWorkMsgAuditInfoVoice->getFind(['audit_info_id'=>$audit_info_id,'sdkfileid'=>$content['sdkfileid']]);
                if($voice_info){
                    $voice_id = $voice_info['id'];
                }else{
                    $voice_id = $dbWorkMsgAuditInfoVoice->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '语音']);
                }
                $data['voice_id'] = $voice_id;
                break;
            case 'video':
                //视频
                $dbWorkMsgAuditInfoVideo = new WorkMsgAuditInfoVideo();
                $file_path =  $this->storageFile($content['file_name'],$content['file']);
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'filesize'=>$content['filesize'],
                    'play_length'=>$content['play_length'],
                    'sdkfileid'=>$content['sdkfileid'],
                    'md5sum'=>$content['md5sum'],
                    'video_path'=>$file_path,
                    'is_finish'=>$content['media_data']['is_finish'],
                ];
                if(isset($content['media_data']['indexbuf'])){
                    $new_data['indexbuf'] = $content['media_data']['indexbuf'];
                }
                $video_info = $dbWorkMsgAuditInfoVideo->getFind(['audit_info_id'=>$audit_info_id,'sdkfileid'=>$content['sdkfileid']]);
                if($video_info){
                    $video_id = $video_info['id'];
                }else{
                    $video_id = $dbWorkMsgAuditInfoVideo->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '视频']);
                }
                $data['video_id'] = $video_id;
                break;
            case 'card':
                //名片
                $dbWorkMsgAuditInfoCard = new WorkMsgAuditInfoCard();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'corpname'=>$content['corpname'],
                    'userid'=>$content['userid'],
                ];
                $member_types = substr($content['userid'],0,2);
                if($member_types == 'wb' || $member_types == 'wo' || $member_types == 'wm'){
                    $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' => $content['userid'], 'corpid' => $corpid], 'customer_id');
                    $new_data['external_id'] = $way_user['customer_id'];
                }else{
                    $whereArr=array();
                    $whereArr[] = ['status','<',4];
                    $whereArr[] = ['is_del','<',1];
                    $whereArr[]=['qy_id','=',$content['userid']];
                    
                    $worker_info = $dbHouseWorker->get_one($whereArr, 'wid');
                    $new_data['user_id'] = $worker_info['wid'];
                }
                $card = $dbWorkMsgAuditInfoCard->getFind(['audit_info_id'=>$audit_info_id,'userid'=>$content['userid']]);
                if($card){
                    $card_id = $card['id'];
                }else{
                    $card_id = $dbWorkMsgAuditInfoCard->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '名片']);
                }
                break;
            case 'location':
                //位置
                $dbWorkMsgAuditInfoLocation = new WorkMsgAuditInfoLocation();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'longitude'=>$content['longitude'],
                    'latitude'=>$content['latitude'],
                    'address'=>$content['address'],
                    'title'=>$content['title'],
                    'zoom'=>$content['zoom'],
                ];
                $location_info = $dbWorkMsgAuditInfoLocation->getFind(['audit_info_id'=>$audit_info_id,'longitude'=>$content['longitude'],'latitude'=>$content['latitude']]);
                if($location_info){
                    $location_id = $location_info['id'];
                }else{
                    $location_id = $dbWorkMsgAuditInfoLocation->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '位置']);
                }
                $data['location_id'] = $location_id;
                break;
            case 'emotion':
                //表情
                $dbWorkMsgAuditInfoEmotion = new WorkMsgAuditInfoEmotion();
                $file_path =  $this->storageFile($content['file_name'],$content['file']);

                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'type'=>$content['type'],
                    'width'=>$content['width'],
                    'height'=>$content['height'],
                    'sdkfileid'=>$content['sdkfileid'],
                    'md5sum'=>$content['md5sum'],
                    'imagesize'=>$content['imagesize'],
                    'local_path'=>$file_path,
                    'is_finish'=>$content['media_data']['is_finish'],
                ];
                if(isset($content['media_data']['indexbuf'])){
                    $new_data['indexbuf'] = $content['media_data']['indexbuf'];
                }
                $emotion_info = $dbWorkMsgAuditInfoEmotion->getFind(['audit_info_id'=>$audit_info_id,'sdkfileid'=>$content['sdkfileid']]);
                if($emotion_info){
                    $emotion_id = $emotion_info['id'];
                }else{
                    $emotion_id = $dbWorkMsgAuditInfoEmotion->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '表情']);
                }
                $data['emotion_id'] = $emotion_id;
                break;
            case 'file':
                //文件
                $dbWorkMsgAuditInfoFile = new WorkMsgAuditInfoFile();
                $file_path =  $this->storageFile($content['file_name'],$content['file']);
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'sdkfileid'=>$content['sdkfileid'],
                    'md5sum'=>$content['md5sum'],
                    'filename'=>$content['file_name'],
                    'filesize'=>$content['filesize'],
                    'local_path'=>$file_path,
                    'is_finish'=>$content['media_data']['is_finish'],
                ];
                $loc = strripos($file_path,'.');
                $fileext = substr($file_path,$loc);
                if($fileext){
                    $new_data['fileext'] = $fileext;
                }
                if(isset($content['media_data']['indexbuf'])){
                    $new_data['indexbuf'] = $content['media_data']['indexbuf'];
                }
                $file_info = $dbWorkMsgAuditInfoFile->getFind(['audit_info_id'=>$audit_info_id,'sdkfileid'=>$content['sdkfileid']]);
                if($file_info){
                    $file_id = $file_info['id'];
                }else{
                    $file_id = $dbWorkMsgAuditInfoFile->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '文件']);
                }
                $data['file_id'] = $file_id;
                break;
            case 'link':
                //链接
                $dbWorkMsgAuditInfoLink = new WorkMsgAuditInfoLink();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'title'=>$content['title'],
                    'description'=>$content['description'],
                    'link_url'=>$content['link_url'],
                    'image_url'=>$content['image_url'],
                ];
                $link_where[] = ['audit_info_id','=',$audit_info_id];
                if($content['link_url'] && $content['image_url']){
                    $link_where[] = ['link_url','=',$content['link_url']];
                    $link_where[] = ['image_url','=',$content['image_url']];
                }elseif($content['link_url'] && !$content['image_url']){
                    $link_where[] = ['link_url','=',$content['link_url']];
                }else{
                    $link_where[] = ['image_url','=',$content['image_url']];
                }
                $link_info = $dbWorkMsgAuditInfoLink->getFind($link_where);
                if($link_info){
                    $link_id = $link_info['id'];
                }else{
                    $link_id = $dbWorkMsgAuditInfoLink->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '链接']);
                }
                $data['link_id'] = $link_id;
                break;
            case 'weapp':
                //小程序消息
                $dbWorkMsgAuditInfoWeapp = new WorkMsgAuditInfoWeapp();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'title'=>$content['title'],
                    'description'=>$content['description'],
                    'username'=>$content['username'],
                    'displayname'=>$content['displayname'],
                ];
                $weapp_info = $dbWorkMsgAuditInfoWeapp->getFind(['audit_info_id'=>$audit_info_id,'username'=>$content['username']]);
                if($weapp_info){
                    $weapp_id = $weapp_info['id'];
                }else{
                    $weapp_id = $dbWorkMsgAuditInfoWeapp->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '小程序信息']);
                }
                $data['weapp_id'] = $weapp_id;
                break;
            case 'chatrecord':
                //会话记录消息
                $dbWorkMsgAuditInfoChatrecordItem = new WorkMsgAuditInfoChatrecordItem();
                $dbWorkMsgAuditInfoChatrecord = new WorkMsgAuditInfoChatrecord();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'title'=>$content['title'],
                ];
                $item = $content['item'];
                $chatrecord = $dbWorkMsgAuditInfoChatrecord->getFind(['audit_info_id'=>$audit_info_id]);
                if($chatrecord){
                    $chatrecord_id = $chatrecord['id'];
                }else{
                    $chatrecord_id = $dbWorkMsgAuditInfoChatrecord->addFind($new_data);
                }

                foreach ($item as $key=>$value){
                    $item_arr= [];
//                    $item_arr['record_id'] = $corpid;
                    $item_arr['chatrecord_id'] = $chatrecord_id;
                    $item_arr['audit_info_id'] = $audit_info_id;
                    $item_arr['type'] = $value['type'];
                    if($value['from_chatroom']){
                        $item_arr['from_chatroom'] = 1;
                    }else{
                        $item_arr['from_chatroom'] = 0;
                    }
                    $isContinue = false;
                    switch ($value['type']){
                        case 'ChatRecordText':
                            //文本
                            $chatrecord_msgtype = 'text';
                            $chatrecord_content = $value['content'];
                            if($chatrecord_content['content']  == '[该消息类型暂不能展示]'){
                                $isContinue = true;
                                break;
                            }
                            $text_infos = $this->disposeType($audit_info_id,$chatrecord_msgtype,$chatrecord_content,$corpid,false);

                            $item_arr['text_id'] = $text_infos['text_id'];
                            break;
                        case 'ChatRecordFile':
                            //文件
                            $chatrecord_msgtype = 'file';
                            $chatrecord_content = $value['content'];
                            $text_infos = $this->disposeType($audit_info_id,$chatrecord_msgtype,$chatrecord_content,$corpid,false);
                            $item_arr['file_id'] = $text_infos['file_id'];
                            break;
                        case 'ChatRecordImage':
                            //图片
                            $chatrecord_msgtype = 'image';
                            $chatrecord_content = $value['content'];
                            $text_infos = $this->disposeType($audit_info_id,$chatrecord_msgtype,$chatrecord_content,$corpid,false);
                            $item_arr['image_id'] = $text_infos['image_id'];

                            break;
                        case 'ChatRecordVideo':
                            //视频
                            $chatrecord_msgtype = 'file';
                            $chatrecord_content = $value['content'];
                            $text_infos = $this->disposeType($audit_info_id,$chatrecord_msgtype,$chatrecord_content,$corpid,false);
                            $item_arr['video_id'] = $text_infos['video_id'];
                            break;
                        case 'ChatRecordLink':
                            //在线文件 （根据结构 按照链接处理）
                            $chatrecord_msgtype = 'link';
                            $chatrecord_content = $value['content'];
                            $text_infos = $this->disposeType($audit_info_id,$chatrecord_msgtype,$chatrecord_content,$corpid,false);
                            $item_arr['link_id'] = $text_infos['link_id'];
                            break;
                        case 'ChatRecordLocation':
                            //定位
                            $chatrecord_msgtype = 'location';
                            $chatrecord_content = $value['content'];
                            $text_infos = $this->disposeType($audit_info_id,$chatrecord_msgtype,$chatrecord_content,$corpid,false);
                            $item_arr['location_id'] = $text_infos['location_id'];
                            break;
                        case 'ChatRecordMixed':
                            //混合消息
                            $chatrecord_msgtype = 'mixed';
                            $chatrecord_content = $value['content'];
                            $text_infos = $this->disposeType($audit_info_id,$chatrecord_msgtype,$chatrecord_content,$corpid,false);
                            $item_arr['mixed_id'] = $text_infos['mixed_id'];
                            break;
                    }
                    if ($isContinue) {
                        continue;
                    }
                    if($value['type'] == 'ChatRecordText' && !isset($item_arr['text_id'])){
                        $item_count = $dbWorkMsgAuditInfoChatrecordItem->getCount(['audit_info_id'=>$audit_info_id,'chatrecord_id'=>$chatrecord_id]);
                        if(!$item_count){
                            $dbWorkMsgAuditInfoChatrecord->realDel(['id'=>$chatrecord_id]);
                        }
                        return '';
                    }
                    $item_info = $dbWorkMsgAuditInfoChatrecordItem->getFind($item_arr);

                    if(!$item_info){
                        $dbWorkMsgAuditInfoChatrecordItem->addFind($item_arr);
                    }

                }
                $data['chatrecord_id'] = $chatrecord_id;
                break;
            case 'todo':
                //待办消息
                $dbWorkMsgAuditInfoTodo = new WorkMsgAuditInfoTodo();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'title'=>$content['title'],
                    'content'=>$content['content'],
                ];
                $todo_info = $dbWorkMsgAuditInfoTodo->getFind(['audit_info_id'=>$audit_info_id]);
                if($todo_info){
                    $todo_id = $todo_info['id'];
                }else{
                    $todo_id = $dbWorkMsgAuditInfoTodo->addFind($new_data);
                }

                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => $content['content']]);
                }
                $data['todo_id'] = $todo_id;
                break;
            case 'vote':
                //投票消息26
                $dbWorkMsgAuditInfoVote = new WorkMsgAuditInfoVote();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'votetitle'=>$content['votetitle'],
                    'voteitem'=>$content['voteitem'],
                    'votetype'=>$content['votetype'],
                    'voteid'=>$content['voteid'],
                ];
                $vote_info = $dbWorkMsgAuditInfoVote->getFind(['audit_info_id'=>$audit_info_id,'voteid'=>$content['voteid']]);
                if($vote_info){
                    $vote_id = $vote_info['id'];
                }else{
                    $vote_id = $dbWorkMsgAuditInfoVote->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '投票']);
                }
                $data['vote_id'] = $vote_id;
                break;
            case 'collect':
                //填表消息
                $dbWorkMsgAuditInfoCollect = new WorkMsgAuditInfoCollect();
                $dbWorkMsgAuditInfoCollectDetails = new WorkMsgAuditInfoCollectDetails();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'room_name'=>$content['room_name'],
                    'creator'=>$content['creator'],
                    'create_time'=>$content['create_time'],
                    'title'=>$content['title'],
                ];
                $details = $content['details'];
                $collect_info = $dbWorkMsgAuditInfoCollect->getFind(['audit_info_id'=>$audit_info_id]);
                if($collect_info){
                    $collect_id = $collect_info['id'];
                }else{
                    $collect_id = $dbWorkMsgAuditInfoCollect->addFind($new_data);
                }
                $collect = [
                    'collect_id'=>$collect_id,
                ];
                foreach ($details as $val){
                    $collect['detail_id'] = $val['id'];
                    $collect['ques'] = $val['ques'];
                    $collect['type'] = $val['type'];
                    $details_info = $dbWorkMsgAuditInfoCollectDetails->getFind(['collect_id'=>$collect_id,'detail_id'=>$val['id']]);
                    if(!$details_info){
                        $dbWorkMsgAuditInfoCollectDetails->addFind($collect);
                    }
                }
                $data['collect_id'] = $collect_id;
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '填表']);
                }
                break;
            case 'redpacket':
                //红包消息
                $dbWorkMsgAuditInfoRedpacket = new WorkMsgAuditInfoRedpacket();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'type'=>$content['type'],
                    'wish'=>$content['wish'],
                    'totalcnt'=>$content['totalcnt'],
                    'totalamount'=>$content['totalamount'],
                ];
                $packet_info = $dbWorkMsgAuditInfoRedpacket->getFind(['audit_info_id'=>$audit_info_id]);
                if($packet_info){
                    $packet_id = $packet_info['id'];
                }else{
                    $packet_id = $dbWorkMsgAuditInfoRedpacket->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '红包']);
                }
                break;
            case 'meeting':
                //会议邀请消息
                $dbWorkMsgAuditInfoMeeting = new WorkMsgAuditInfoMeeting();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'topic'=>$content['type'],
                    'starttime'=>$content['starttime'],
                    'endtime'=>$content['endtime'],
                    'address'=>$content['address'],
                    'remarks'=>$content['remarks'],
                    'meetingtype'=>$content['meetingtype'],
                    'meetingid'=>$content['meetingid'],
                    'status'=>$content['status'],
                ];
                $meeting_info = $dbWorkMsgAuditInfoMeeting->getFind(['audit_info_id'=>$audit_info_id,'meetingid'=>$content['meetingid']]);
                if($meeting_info){
                    $meeting_id = $meeting_info['id'];
                }else{
                    $meeting_id = $dbWorkMsgAuditInfoMeeting->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '会议邀请']);
                }
                $data['meeting_id'] = $meeting_id;
                break;
            case 'docmsg':
                //在线文档消息
                $dbWorkMsgAuditInfoDocmsg = new WorkMsgAuditInfoDocmsg();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'title'=>$content['title'],
                    'link_url'=>$content['link_url'],
                    'doc_creator'=>$content['doc_creator'],
                ];
                $member_type_d = substr($new_data['doc_creator'],0,2);
                if($member_type_d == 'wb' || $member_type_d == 'wo' || $member_type_d == 'wm'){
                    $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' => $new_data['doc_creator'], 'corpid' => $corpid], 'customer_id');
                    $new_data['external_id'] = $way_user['customer_id'];
                }else{
                    $whereArr=array();
                    $whereArr[] = ['status','<',4];
                    $whereArr[] = ['is_del','<',1];
                    $whereArr[]=['qy_id','=',$content['creatorname']];
                    $worker_info = $dbHouseWorker->get_one($whereArr, 'wid');
                    $new_data['user_id'] = $worker_info['wid'];
                }
                $docmsg_info = $dbWorkMsgAuditInfoDocmsg->getFind(['audit_info_id'=>$audit_info_id,'link_url'=>$content['link_url']]);
                if($docmsg_info){
                    $docmsg_id =$docmsg_info['id'];
                }else{
                    $docmsg_id = $dbWorkMsgAuditInfoDocmsg->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '在线文档']);
                }
                $data['docmsg_id'] = $docmsg_id;
                break;
            case 'markdown':
                //MarkDown格式消息
                $dbWorkMsgAuditInfoMarkdown = new WorkMsgAuditInfoMarkdown();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'content'=>$content['content'],
                ];
                $markdown_info = $dbWorkMsgAuditInfoMarkdown->getFind(['audit_info_id'=>$audit_info_id]);
                if($markdown_info){
                    $markdown_id = $markdown_info['id'];
                }else{
                    $markdown_id = $dbWorkMsgAuditInfoMarkdown->addFind($new_data);
                }
                $data['markdown_id'] = $markdown_id;
                break;
            case 'news':
                //图文消息
                $dbWorkMsgAuditInfoNews = new WorkMsgAuditInfoNews();
                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'title'=>$content['title'],
                    'description'=>$content['description'],
                    'url'=>$content['url'],
                    'picurl'=>$content['picurl'],
                ];
                $news_info = $dbWorkMsgAuditInfoNews->getFind(['audit_info_id'=>$audit_info_id,'picurl'=>$content['picurl']]);
                if($news_info){
                    $news_id = $news_info['id'];
                }else{
                    $news_id = $dbWorkMsgAuditInfoNews->addFind($new_data);
                }
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '图文']);
                }
                $data['news_id'] = $news_id;
                break;
            case 'calendar':
                //日程消息
                $dbWorkMsgAuditInfoCalendar = new WorkMsgAuditInfoCalendar();
                $dbWorkMsgAuditInfoCalendarAttendee = new WorkMsgAuditInfoCalendarAttendee();

                $new_data = [
                    'audit_info_id'=>$audit_info_id,
                    'title'=>$content['title'],
                    'creatorname'=>$content['creatorname'],
                    'starttime'=>$content['starttime'],
                    'endtime'=>$content['endtime'],
                    'place'=>$content['place'],
                    'remarks'=>$content['remarks'],
                ];
                $creatorname = $content['creatorname'];
                $creatorname_type = substr($creatorname,0,2);
                if($creatorname_type == 'wb' || $creatorname_type == 'wo' || $creatorname_type == 'wm'){
                    $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' => $content['creatorname'], 'corpid' => $corpid], 'customer_id');
                    $new_data['external_id'] = $way_user['customer_id'];
                }else{
                    $whereArr=array();
                    $whereArr[] = ['status','<',4];
                    $whereArr[] = ['is_del','<',1];
                    $whereArr[]=['qy_id','=',$content['creatorname']];
                    $worker_info = $dbHouseWorker->get_one($whereArr, 'wid');
                    $new_data['user_id'] = $worker_info['wid'];
                }
                $calendar_info = $dbWorkMsgAuditInfoCalendar->getFind(['audit_info_id'=>$audit_info_id,'starttime'=>$content['starttime'],'endtime'=>$content['endtime']]);
                if($calendar_info){
                    $calendar_id = $calendar_info['id'];
                }else{
                    $calendar_id = $dbWorkMsgAuditInfoCalendar->addFind($new_data);
                }
                $attendeename = $content['attendeename'];
                $attendee_arr = [
                    'calendar_id'=>$calendar_id,
                    'attendeename'=>implode(',',$attendeename),
                ];
                foreach ($attendeename as $value){
                    $att_member_type = substr($value,0,2);
                    if($att_member_type == 'wb' || $att_member_type == 'wo' || $att_member_type == 'wm'){
                        $way_user = $dbHouseContactWayUser->getFind(['ExternalUserID' =>$value, 'corpid' => $corpid], 'customer_id');
                        $attendee_arr['external_id'] = $way_user['customer_id'];
                    }else{
                        $whereArr=array();
                        $whereArr[] = ['status','<',4];
                        $whereArr[] = ['is_del','<',1];
                        $whereArr[]=['qy_id','=',$value];
                        
                        $worker_info = $dbHouseWorker->get_one($whereArr, 'wid');
                        $attendee_arr['user_id'] = $worker_info['wid'];
                    }
                    $attendee = $dbWorkMsgAuditInfoCalendarAttendee->getFind(['calendar_id'=>$calendar_id]);
                    if(!$attendee){
                        $dbWorkMsgAuditInfoCalendarAttendee->addFind($attendee_arr);
                    }
                }
                $data['calendar_id'] = $calendar_id;
                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '日程']);
                }
                break;
            case 'mixed':
                //混合消息
                $dbWorkMsgAuditInfoMixed = new WorkMsgAuditInfoMixed();
                $item_mixed = $content['item'];
                $mixed_id= [];
                foreach ($item_mixed as $value){
                    $result = $this->disposeType($audit_info_id,$value['type'],$value['content'],$corpid,false);
                    $result['audit_info_id'] = $audit_info_id;
                    $result['type'] = $value['type'];
                    $mixed_info = $dbWorkMsgAuditInfoMixed->getFind($result);
                    if(!$mixed_info){
                        $mixed_id[] = $dbWorkMsgAuditInfoMixed->addFind($result);
                    }else{
                        $mixed_id[] =$mixed_info['id'];
                    }
                }
                if($mixed_id){
                    $data['mixed_id'] = implode(',',$mixed_id);
                }else{
                    $data['mixed_id'] = '';
                }

                if($is_mixed) {
                    $dbWorkMsgAuditInfo->editFind(['id' => $audit_info_id], ['content' => '混合消息']);
                }
                break;
        }
        return $data;
    }

    /**
     * Notes: base64 转图片/视频/语音
     * @datetime: 2021/4/2 14:40
     * @param $file_name
     * @param $base64_file
     * @return
     */
    public function storageFile($file_name,$base64_file,$type = '')
    {
        $saveDir  = "/upload/house/qyweixin/file/" .date('Ymd') . "/";
        if($type == 'voice') {
            $saveDir  = "/v20/public/upload/file/" .date('Ymd') . "/";
        }
        $savePath = request()->server('DOCUMENT_ROOT') . $saveDir;
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $file_path = $savePath.$file_name;
        file_put_contents($file_path, base64_decode($base64_file));
        $return_url = $saveDir.$file_name;
        if($type == 'voice')
        {
            $saveDir_oss  = "/upload/house/qyweixin/file/" .date('Ymd') . "/";
            $savePath = request()->server('DOCUMENT_ROOT') . $saveDir_oss;
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            $file_path_arr = explode('.',$file_name);
            $return_url_mp3 = $savePath.$file_path_arr[0].'.mp3';

            fdump_api('-----','$file_names.mp3',true);
            $url = "ffmpeg -i ".$file_path." -ab 32 -ar 11025 ".$return_url_mp3." 2>&1 | grep time";
            fdump_api($url,'$file_names.mp3',true);
            exec("ffmpeg -i ".$file_path." -ab 32 -ar 11025 ".$return_url_mp3." 2>&1 | grep time",$callback,$status);
            fdump_api($callback,'$file_names.mp3',true);
            fdump_api($status,'$file_names.mp3',true);
//            if(filesize($return_url_mp3) > 0){
                unlink($file_path);//删除原文件
                $return_url_mp3 = $saveDir.$file_path_arr[0].'.mp3';
                $return_url_oss_mp3 = $saveDir_oss.$file_path_arr[0].'.mp3';
                return $return_url_oss_mp3;
//            }
        }
        return $return_url;
    }
    //获取消息
    public function getMsgList($param,$page=0,$limit=20,$order = 'msgtime desc')
    {
        $cacheKey = "session_file:".$param['property_id'].'_'.$param['from_id'].'_'.$param['to_id'].'_'.$param['type'].':'.$page.'_'.$limit;
        $where = [];
        if($param['msg_type']) {//查全部
            if($param['msg_type'] == 'other'){
                $where[] = ['msgtype','in',['revoke','disagree','agree','card','location','emotion','link','chatrecord','todo','vote','collect','redpacket','meeting','docmsg','markdown','calendar','mixed']];
            }else{
                $where[] = ['msgtype','=',$param['msg_type']];
            }
            $cacheKey .= ':'.$param['msg_type'];
        }
//      $where[] = ['audit_id','=',$audit_id];//可能需要
        $from_id = $param['from_id'];
        $to_id = $param['to_id'];
        if (isset($param['search_name'])) {
            $where[] = ['content','like','%'.$param['search_name'].'%'];
            $cacheKey .= ':'.$param['search_name'];
        }
        if (isset($param['start_date']) && isset($param['end_date'])) {
            $where[] = ['msgtime', '>=', $param['start_date']];
            $where[] = ['msgtime', '<=', $param['end_date']];
            $cacheKey .= ':'.$param['start_date'].'_'.$param['end_date'];
        } elseif (isset($param['start_date'])) {
            $where[] = ['msgtime', '>=', $param['start_date']];
            $cacheKey .= ':'.$param['start_date'].'_0';
        } elseif (isset($param['end_date'])) {
            $where[] = ['msgtime', '<=', $param['end_date']];
            $cacheKey .= ':0_'.$param['end_date'];
        }
        $dbHouseContactWayUser = new HouseContactWayUser();
        if(in_array($param['type'],[2,3])){
            $sql_where = "";
            $where[] = ['chat_id','=',$param['chat_id']];
            $cacheKey .= ':'.$param['chat_id'];
            if($param['chat_from_id']) {
                $cacheKey .= '_'.$param['chat_from_id'];
                $where[] = ['user_id|external_id', '=', $param['chat_from_id']];
            }
        }else{
            $whereToIdVal = 0;
            $whereFromVal = '';
            $whereToListVal = '';
            if (strpos($from_id, 'audit_') !== false && strpos($to_id, 'audit_') !== false) {
                $whereFromVal = str_replace('audit_', '', $from_id);
                $whereToListVal = str_replace('audit_', '', $to_id);
            } elseif (strpos($from_id, 'audit_') !== false) {
                $whereFromVal = str_replace('audit_', '', $from_id);
                if ($param['type'] == 1) {
                    $toInfo = $dbHouseContactWayUser->getFind(['customer_id'=>$to_id],'customer_id,phone,name,avatar,gender,ExternalUserID');
                    if ($toInfo && !is_array($toInfo)) {
                        $toInfo = $toInfo->toArray();
                    }
                    $whereToListVal = isset($toInfo['ExternalUserID']) && $toInfo['ExternalUserID'] ? $toInfo['ExternalUserID'] : '';
                }
                ! $whereToListVal && $whereToIdVal = $to_id;
            } elseif (strpos($to_id, 'audit_') !== false) {
                $whereToListVal = str_replace('audit_', '', $to_id);
                $whereFromIdVal = $from_id;
            } else {
                $whereToIdVal = $to_id;
                $whereFromIdVal = $from_id;
                if ($param['type'] == 1) {
                    $toInfo = $dbHouseContactWayUser->getFind(['customer_id'=>$to_id],'customer_id,phone,name,avatar,gender,ExternalUserID');
                    if ($toInfo && !is_array($toInfo)) {
                        $toInfo = $toInfo->toArray();
                    }
                    $whereToListVal = isset($toInfo['ExternalUserID']) && $toInfo['ExternalUserID'] ? $toInfo['ExternalUserID'] : '';
                }
            }
            if ($whereFromVal && $whereToListVal) {
                $sql_where = "( `from`='{$whereFromVal}' and `tolist`='{$whereToListVal}' ) or ( `from`='{$whereToListVal}' and `tolist`='{$whereFromVal}' )";
            } elseif ($whereFromVal && $whereToIdVal) {
                $sql_where = "(`from`='{$whereFromVal}' and (to_user_id=$whereToIdVal or to_external_id=$whereToIdVal) ) or ( (user_id=$whereToIdVal or external_id=$whereToIdVal) and `tolist`='{$whereFromVal}' )";
            } elseif ($whereToListVal && $whereFromIdVal) {
                $sql_where = "( (user_id=$whereFromIdVal or external_id=$whereFromIdVal) and `tolist`='{$whereToListVal}') or (`from`='{$whereToListVal}' and (to_user_id=$whereFromIdVal or to_external_id=$whereFromIdVal) )";
            } else {
                $sql_where = "( (user_id=$whereFromIdVal or external_id=$whereFromIdVal) and (to_user_id=$whereToIdVal or to_external_id=$whereToIdVal) ) or ( (user_id=$whereToIdVal or external_id=$whereToIdVal) and (to_user_id=$whereFromIdVal or to_external_id=$whereFromIdVal) )";
            }
        }
        $dbHouseWorker = new HouseWorker();
        $dbWorkMsgAuditInfo = new WorkMsgAuditInfo();
        $list = $dbWorkMsgAuditInfo->getList($where, true, $order, $sql_where, $page, $limit);
        fdump_api($list, '$list');
        foreach ($list as $key=>&$val){
            $info = Cache::get($cacheKey.':'.$val['id']);
            if(empty($info)){
                $info = $this->actionMsg($val['msgtype'],$val['id'],[],true);
                if($info){
                    Cache::set($cacheKey.':'.$val['id'],$info);
                }
            }
            $val['info'] = $info;
            $val['msg_time'] = date("Y-m-d H:i:s",floor($val['msgtime']/1000));
            if(isset($list[$key-1]['msgtime']) && $list[$key-1]['msgtime'] > 0 && abs($list[$key]['msgtime']-$list[$key-1]['msgtime']) > 5*60*1000){
                $val['is_show_msgtime'] = true;
            }else{
                $val['is_show_msgtime'] = false;
            }

            if($val['user_id']){
                $from_ids = $val['user_id'];
            }else{
                $from_ids = $val['external_id'];
            }
            if($val['to_user_id']){
                $to_ids = $val['to_user_id'];
            }else{
                $to_ids = $val['to_external_id'];
            }
            if($val['from_type'] == 1)
            {
                $from_info = $dbHouseWorker->get_one(['wid'=>$from_ids],'wid,name,avatar,phone,gender');
                if ($from_info && !is_array($from_info)) {
                    $from_info = $from_info->toArray();
                }
                if (empty($from_info)) {
                    $from_info = [
                        'wid' => 'audit_'. $val['from'],
                        'phone' => '',
                        'name' => $val['from'],
                        'avatar' => '',
                        'gender' => '',
                    ];
                    $val['user_id'] = 'audit_'. $val['from'];
                }
            }else{
                $from_info =$dbHouseContactWayUser->getFind(['customer_id'=>$from_ids],'customer_id,phone,name,avatar,gender');
                if ($from_info && !is_array($from_info)) {
                    $from_info = $from_info->toArray();
                }
                if (empty($from_info)) {
                    $from_info =$dbHouseContactWayUser->getFind(['ExternalUserID'=>$val['from']],'customer_id,phone,name,avatar,gender');
                    if ($from_info && !is_array($from_info)) {
                        $from_info = $from_info->toArray();
                    }
                }
                if (empty($from_info)) {
                    $from_info = [
                        'phone' => '',
                        'name' => $val['from'],
                        'avatar' => dispose_url('/v20/public/static/community/qywx/default.png'),
                        'gender' => '',
                    ];
                }
                $val['from_info'] = $from_info;
                ! empty($from_info) && ! $from_info['avatar'] && $from_info['avatar'] = dispose_url('/v20/public/static/community/qywx/default.png');
            }
            ! empty($from_info) && ! $from_info['name'] && $from_info['name'] = $val['from'];
            $val['from_info'] = $from_info;
            if($val['to_type'] == 1)
            {
                $to_info = $dbHouseWorker->get_one(['wid' => $to_ids], 'wid,name,avatar,phone,gender');
                if ($to_info && !is_array($to_info)) {
                    $to_info = $to_info->toArray();
                }
                if (empty($to_info)) {
                    $to_info = [
                        'wid' => 'audit_'. $val['tolist'],
                        'phone' => '',
                        'name' => $val['tolist'],
                        'avatar' => '',
                        'gender' => '',
                    ];
                }
                $val['to_user_id'] = 'audit_'. $val['tolist'];
            }else{
                $to_info = $dbHouseContactWayUser->getFind(['customer_id' => $to_ids], 'customer_id,phone,name,avatar,gender');
                if ($to_info && !is_array($to_info)) {
                    $to_info = $to_info->toArray();
                }
                if (empty($to_info)) {
                    $to_info =$dbHouseContactWayUser->getFind(['ExternalUserID'=>$val['tolist']],'customer_id,phone,name,avatar,gender');
                    if ($to_info && !is_array($to_info)) {
                        $to_info = $to_info->toArray();
                    }
                }
                if (empty($to_info)) {
                    $to_info = [
                        'phone' => '',
                        'name' => $val['tolist'],
                        'avatar' => dispose_url('/v20/public/static/community/qywx/default.png'),
                        'gender' => '',
                    ];
                }
                ! empty($to_info) && ! $to_info['avatar'] && $to_info['avatar'] = dispose_url('/v20/public/static/community/qywx/default.png');
            }
            ! empty($to_info) && ! $to_info['name'] && $to_info['name'] = $val['tolist'];
            $val['to_info'] = $to_info;
        }
        return $list;

    }
    public function actionMsg($msgtype,$audit_info_id,$param_data=[],$is_cover=false)
    {
        $data = [];
        switch ($msgtype){
            case 'text':
                //文本
                $map =[];
                $dbWorkMsgAuditInfoText = new WorkMsgAuditInfoText();
                if($param_data && $param_data['text_id']){
                    $map[] = ['id','=',$param_data['text_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoText->getFind($map);
                if($data['content_convert']){
                    $data['content_convert'] = json_decode($data['content_convert'],true);
                }
                break;
            case 'image':
                //图片
                $dbWorkMsgAuditInfoImage = new WorkMsgAuditInfoImage();
                $map =[];
                if($param_data && $param_data['image_id']){
                    $map[] = ['id','=',$param_data['image_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoImage->getFind($map);
                if($data){
                    $data['local_path'] = dispose_url($data['file_path']);
                    if ($is_cover) {
                        $data['file_path'] = $data['local_path'];
                    }
                }
                break;
            case 'revoke':
                //撤回消息
                $dbWorkMsgAuditInfoRevoke = new WorkMsgAuditInfoRevoke();
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoRevoke->getFind($map);
                break;
            case 'disagree'://不同意会话聊天内容
            case 'agree'://同意会话聊天内容
                $dbWorkMsgAuditInfoAgree = new WorkMsgAuditInfoAgree();
                $data = $dbWorkMsgAuditInfoAgree->getFind(['audit_info_id'=>$audit_info_id]);
                if($data['agree_type'] == 0){
                    $data['agree_type_msg'] = '不同意';
                }else{
                    $data['agree_type_msg'] = '同意';
                }
                break;
            case 'voice':
                //语音
                $dbWorkMsgAuditInfoVoice = new WorkMsgAuditInfoVoice();
                $map =[];
                if($param_data && $param_data['voice_id']){
                    $map[] = ['id','=',$param_data['voice_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoVoice->getFind($map);
                if($data){
                    $data['local_path'] = dispose_url($data['voice_path']);
                }
                break;
            case 'video':
                //视频
                $dbWorkMsgAuditInfoVideo = new WorkMsgAuditInfoVideo();
                $map =[];
                if($param_data && $param_data['video_id']){
                    $map[] = ['id','=',$param_data['video_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoVideo->getFind($map);
                if($data){
                    $data['local_path'] = dispose_url($data['video_path']);
                }
                break;
            case 'card':
                //名片
                $dbWorkMsgAuditInfoCard = new WorkMsgAuditInfoCard();
                $data = $dbWorkMsgAuditInfoCard->getFind(['audit_info_id'=>$audit_info_id]);
                break;
            case 'location':
                //位置
                $map =[];
                if($param_data && $param_data['location_id']){
                    $map[] = ['id','=',$param_data['location_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $dbWorkMsgAuditInfoLocation = new WorkMsgAuditInfoLocation();
                $data = $dbWorkMsgAuditInfoLocation->getFind(['audit_info_id'=>$audit_info_id]);
                break;
            case 'emotion':
                //表情
                $map =[];
                if($param_data && $param_data['emotion_id']){
                    $map[] = ['id','=',$param_data['emotion_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $dbWorkMsgAuditInfoEmotion = new WorkMsgAuditInfoEmotion();
                $data = $dbWorkMsgAuditInfoEmotion->getFind($map);
                if($data){
                    $data['local_path'] = dispose_url($data['local_path']);
                }
                break;
            case 'file':
                //文件
                $dbWorkMsgAuditInfoFile = new WorkMsgAuditInfoFile();
                $map =[];
                if($param_data && $param_data['file_id']){
                    $map[] = ['id','=',$param_data['file_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoFile->getFind($map);
                if($data){
                    $data['local_path'] = dispose_url($data['local_path']);
                }
                break;
            case 'link':
                //链接
                $dbWorkMsgAuditInfoLink = new WorkMsgAuditInfoLink();
                $map =[];
                if($param_data && $param_data['link_id']){
                    $map[] = ['id','=',$param_data['link_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoLink->getFind($map);
                break;
            case 'weapp':
                //小程序消息
                $dbWorkMsgAuditInfoWeapp = new WorkMsgAuditInfoWeapp();
                $map =[];
                if($param_data && $param_data['weapp_id']){
                    $map[] = ['id','=',$param_data['weapp_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoWeapp->getFind($map);
                break;
            case 'chatrecord':
                //会话记录消息
                $dbWorkMsgAuditInfoChatrecordItem = new WorkMsgAuditInfoChatrecordItem();
                $dbWorkMsgAuditInfoChatrecord = new WorkMsgAuditInfoChatrecord();
                $data = $dbWorkMsgAuditInfoChatrecord->getFind(['audit_info_id'=>$audit_info_id]);
                if($data){
                    $chatrecord_id = $data['id'];
                    $item_data = $dbWorkMsgAuditInfoChatrecordItem->getList(['audit_info_id'=>$audit_info_id,'chatrecord_id'=>$chatrecord_id]);
                    $infos = [];

                    foreach ($item_data as $value){
                        switch ($value['type']){
                            case 'ChatRecordText':
                                //文本
                                $chatrecord_msgtype = 'text';
                                $infos[][] = $this->actionMsg($chatrecord_msgtype,$audit_info_id,$value);
                                break;
                            case 'ChatRecordFile':
                                //文件
                                $chatrecord_msgtype = 'file';
                                $infos[][] = $this->actionMsg($chatrecord_msgtype,$audit_info_id,$value);
                                break;
                            case 'ChatRecordImage':
                                //图片
                                $chatrecord_msgtype = 'image';
                                $infos[][] = $this->actionMsg($chatrecord_msgtype,$audit_info_id,$value);
                                break;
                            case 'ChatRecordVideo':
                                //视频
                                $chatrecord_msgtype = 'file';
                                $infos[][] = $this->actionMsg($chatrecord_msgtype,$audit_info_id,$value);
                                break;
                            case 'ChatRecordLink':
                                //在线文件 （根据结构 按照链接处理）
                                $chatrecord_msgtype = 'link';
                                $infos[][] = $this->actionMsg($chatrecord_msgtype,$audit_info_id,$value);
                                break;
                            case 'ChatRecordLocation':
                                //定位
                                $chatrecord_msgtype = 'location';
                                $infos[][] = $this->actionMsg($chatrecord_msgtype,$audit_info_id,$value);
                                break;
                            case 'ChatRecordMixed':
                                //混合消息
                                $chatrecord_msgtype = 'mixed';
                                $infos[] = $this->actionMsg($chatrecord_msgtype,$audit_info_id,$value);
                                break;
                        }
                    }
                    $data['item'] = $infos;
                }

                break;
            case 'todo':
                //待办消息
                $dbWorkMsgAuditInfoTodo = new WorkMsgAuditInfoTodo();
                $map =[];
                if($param_data && $param_data['todo_id']){
                    $map[] = ['id','=',$param_data['todo_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoTodo->getFind($map);
                break;
            case 'vote':
                //投票消息26
                $dbWorkMsgAuditInfoVote = new WorkMsgAuditInfoVote();
                $map =[];
                if($param_data && $param_data['vote_id']){
                    $map[] = ['id','=',$param_data['vote_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoVote->getFind($map);
                break;
            case 'collect':
                //填表消息
                $dbWorkMsgAuditInfoCollect = new WorkMsgAuditInfoCollect();
                $dbWorkMsgAuditInfoCollectDetails = new WorkMsgAuditInfoCollectDetails();

                $data = $dbWorkMsgAuditInfoCollect->getFind(['audit_info_id'=>$audit_info_id]);
                if($data){
                    $collect_id = $data['id'];
                    $details_data = $dbWorkMsgAuditInfoCollectDetails->getList(['collect_id'=>$collect_id]);
                    $data['details'] = $details_data;
                }
                break;
            case 'redpacket':
                //红包消息
                $dbWorkMsgAuditInfoRedpacket = new WorkMsgAuditInfoRedpacket();
                $data = $dbWorkMsgAuditInfoRedpacket->getFind(['audit_info_id'=>$audit_info_id]);
                break;
            case 'meeting':
                //会议邀请消息
                $dbWorkMsgAuditInfoMeeting = new WorkMsgAuditInfoMeeting();
                $map =[];
                if($param_data && $param_data['meeting_id']){
                    $map[] = ['id','=',$param_data['meeting_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoMeeting->getFind($map);
                break;
            case 'docmsg':
                //在线文档消息
                $dbWorkMsgAuditInfoDocmsg = new WorkMsgAuditInfoDocmsg();
                $map =[];
                if($param_data && $param_data['docmsg_id']){
                    $map[] = ['id','=',$param_data['docmsg_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoDocmsg->getFind($map);
                break;
            case 'markdown':
                //MarkDown格式消息
                $dbWorkMsgAuditInfoMarkdown = new WorkMsgAuditInfoMarkdown();
                $map =[];
                if($param_data && $param_data['markdown_id']){
                    $map[] = ['id','=',$param_data['markdown_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoMarkdown->getFind($map);
                break;
            case 'news':
                //图文消息
                $dbWorkMsgAuditInfoNews = new WorkMsgAuditInfoNews();
                $map =[];
                if($param_data && $param_data['news_id']){
                    $map[] = ['id','=',$param_data['news_id']];
                }
                $map[] = ['audit_info_id','=',$audit_info_id];
                $data = $dbWorkMsgAuditInfoNews->getFind(['audit_info_id'=>$audit_info_id]);
                break;
            case 'calendar':
                //日程消息
                $dbWorkMsgAuditInfoCalendar = new WorkMsgAuditInfoCalendar();
                $dbWorkMsgAuditInfoCalendarAttendee = new WorkMsgAuditInfoCalendarAttendee();
                $dbHouseWorker = new HouseWorker();
                $dbHouseContactWayUser = new HouseContactWayUser();
                $data = $dbWorkMsgAuditInfoCalendar->getFind(['audit_info_id'=>$audit_info_id]);
                if($data){
                    $calendar_id = $data['id'];
                    $attendee = $dbWorkMsgAuditInfoCalendarAttendee->getList(['calendar_id'=>$calendar_id]);
                    if($attendee && count($attendee)>0){
                        $attendee = $attendee->toArray();
                        foreach ($attendee as &$v){
                            if($v['external_id']){
                                $user_info = $dbHouseContactWayUser->getFind(['customer_id' =>$v['external_id']],'customer_id as id,phone,name,avatar,gender');
                                $v['user_info'] = $user_info;
                            }else{
                                $user_info = $dbHouseWorker->get_one(['wid' =>$v['user_id']], 'wid as id,nickname as name,avatar,phone,gender');
                                $v['user_info'] = $user_info;
                            }
                        }
                    }
                    $data['attendeename'] = $attendee;
                }
                break;
            case 'mixed':
                $dbWorkMsgAuditInfoMixed = new WorkMsgAuditInfoMixed();
                $mixed_info = $dbWorkMsgAuditInfoMixed->getList(['audit_info_id'=>$audit_info_id]);
                if($mixed_info && count($mixed_info)>0){
                    foreach ($mixed_info as $key=>$val){
                        $data[] = $this->actionMsg($val['type'],$audit_info_id,$val);
                    }

                }else{
                    $data = [];
                }
                break;
        }
        return $data;
    }

    /**
     * Notes: 获取聊天列表的工作人员列表
     * @datetime: 2021/4/7 13:58
     * @param $param
     * @return
     */
    public function getChatMemberList($param)
    {
        $dbHouseWorker = new HouseWorker();
        $work_id = $param['work_id'];
        $isQy = false;
        if (strpos($work_id,'audit_') !== false) {
            $qy_id = str_replace('audit_', '', $work_id);
            $isQy = true;
        } else {
            $qy_id = '';
        }
        $whereRaw = '';
        $where = [];
        if(in_array($param['type'],[2,3])){
            if (!$qy_id) {
                $worker_infos = $dbHouseWorker->get_one(['wid'=>$work_id],'qy_id');
                if(!$worker_infos){
                    throw new \Exception('工作人员不存在');
                }
                $qy_id = $worker_infos['qy_id'];
                $whereRaw = "(FIND_IN_SET('".$qy_id."',tolist) or user_id=$work_id or external_id=$work_id ) and roomid !='' and chat_id!=''";
            } else {
                $whereRaw = "(FIND_IN_SET('{$qy_id}',tolist) or `from`='{$qy_id}') and roomid !='' and chat_id!=''";
            }
        } elseif ($qy_id) {
            $whereRaw = "(`tolist`='{$qy_id}' or `from`='{$qy_id}')";
        } elseif (! $qy_id){
            $worker_infos = $dbHouseWorker->get_one(['wid'=>$work_id],'qy_id');
            isset($worker_infos['qy_id']) && $worker_infos['qy_id'] && $qy_id = $worker_infos['qy_id'];
            $where[] = ['user_id|external_id|to_user_id|to_external_id','=',$work_id];
        }
        $dbWorkMsgAuditInfo = new WorkMsgAuditInfo();
        $dbHouseContactWayUser = new HouseContactWayUser();
        $field = 'id,from_type,to_type,user_id,external_id,to_user_id,to_external_id,chat_id,roomid,tolist,from,msgtime,content';
        fdump_api([$where, $whereRaw], '$whereRaw');
        $list = $dbWorkMsgAuditInfo->getList($where, $field, 'msgtime asc', $whereRaw);
        fdump_api([$list, $qy_id, $dbWorkMsgAuditInfo->getLastSql()], '$sql');
        if($list){
            $list = $list->toArray();
        }
        $user_id = '';
        $external_id = '';
        $room_id='';
        $chat_id='';
        $useridLists = '';
        $workArr = [];
        foreach ($list as $k=>$v){
            if(in_array($param['type'],[2,3])) {
                if($v['roomid']){
                    $room_id .= ',' . $v['roomid'];
                }
                if($v['chat_id']){
                    $chat_id .= ',' . $v['chat_id'];
                }
            }else{
                $hasUserId = false;
                $hasExternalId = false;
                if ($v['user_id'] && $isQy && $v['from'] <> $qy_id) {
                    $hasUserId = true;
                    $user_id .= ',' . $v['user_id'];
                } elseif ($v['user_id'] && $v['user_id'] <> $work_id) {
                    $hasUserId = true;
                    $user_id .= ',' . $v['user_id'];
                }
                $toListArr = isset($v['tolist']) && $v['tolist'] ? explode(',',$v['tolist']) : [];
                if ($v['to_user_id'] && $isQy && ! in_array($qy_id, $toListArr)) {
                    $hasUserId = true;
                    $user_id .= ',' . $v['to_user_id'];
                } elseif ($v['to_user_id'] && $v['to_user_id'] <> $work_id) {
                    $hasUserId = true;
                    $user_id .= ',' . $v['to_user_id'];
                }
                if ($v['external_id'] && $isQy && $v['from'] <> $qy_id) {
                    $hasExternalId = true;
                    $external_id .= ',' . $v['external_id'];
                } elseif ($v['external_id'] && $v['external_id'] <> $work_id) {
                    $hasExternalId = true;
                    $external_id .= ',' . $v['external_id'];
                }
                if ($v['to_external_id'] && $isQy && ! in_array($qy_id, $toListArr)) {
                    $hasExternalId = true;
                    $external_id .= ',' . $v['to_external_id'];
                } elseif ($v['to_external_id'] && $v['to_external_id'] <> $work_id) {
                    $hasExternalId = true;
                    $external_id .= ',' . $v['to_external_id'];
                }
                if (!$hasUserId && intval($v['from_type']) == 1 && $v['from'] && $v['from'] <> $qy_id) {
                    ! isset($workArr[$v['from']]) && $workArr[$v['from']] = $v;
                }
                if (!$hasExternalId && intval($v['from_type']) == 2 && $v['from']) {
                    $useridLists .= ',' . $v['from'];
                }
                if (!$hasUserId && intval($v['to_type']) == 1 && $v['tolist'] && $v['tolist'] <> $qy_id) {
                    ! isset($workArr[$v['tolist']]) && $workArr[$v['tolist']] = $v;
                }
                if (!$hasExternalId && intval($v['to_type']) == 2 && $v['tolist']) {
                    $useridLists .= ',' . $v['tolist'];
                }
            }

        }
        $data= [];
        $externalUserArr = [];
        if($param['type'] == 0){
            $user_id = trim($user_id,',');
            $user_arr = explode(',',$user_id);
            $new_user_arr = array_unique($user_arr);
            $where = [];
            $where[] = ['wid','in',$new_user_arr];
            if($param['name']){
                $where[] = ['name','like','%'.$param['name'].'%'];
            }
            $employee = $dbHouseWorker->getAll($where,'wid as id,name,avatar,phone,gender');
            if($employee){
                $employee = $employee->toArray();
            }
            $chat_data = [];
            foreach ($employee as $key=>$val){
                $to_id = $val['id'];
                if ($isQy) {
                    $sql_where = "( (`tolist`='{$qy_id}' or `from`='{$qy_id}') and (to_user_id=$to_id or to_external_id=$to_id) ) or ( (user_id=$to_id or external_id=$to_id) and (`tolist`='{$qy_id}' or `from`='{$qy_id}') )";
                } else {
                    $sql_where = "( (user_id=$work_id or external_id=$work_id) and (to_user_id=$to_id or to_external_id=$to_id) ) or ( (user_id=$to_id or external_id=$to_id) and (to_user_id=$work_id or to_external_id=$work_id) )";
                }
                $audit_info = $dbWorkMsgAuditInfo->getFind($sql_where,'id,msgtime,content');
                $chat_data[$key] = [
                    'content'=>$audit_info['content'],
                    'msgtime'=>$audit_info['msgtime'],
                    'id'=>$audit_info['id'],
                ];
                if(!$val['avatar']){
                    $val['avatar'] = dispose_url('/v20/public/static/community/qywx/default.png');
                }
                $chat_data[$key]['user'] = $val;
            }
            $property_id = isset($param['property_id']) && $param['property_id'] ? $param['property_id'] : 0;
            if ($workArr) {
                foreach ($workArr as $workItem) {
                    $itemVal = [
                        'content' => $workItem['content'],
                        'msgtime' => $workItem['msgtime'],
                        'id' => $workItem['id'],
                    ];
                    if ($qy_id && $qy_id == $workItem['from']) {
                        $id = 'audit_'.$workItem['tolist'];
                        $name = $workItem['tolist'];
                        $whereWork = [
                            ['property_id' ,'=', $property_id],
                            ['qy_id' ,'=', $workItem['tolist']],
                        ];
                        $whereWork[] = ['status','<',4];
                        $whereWork[] = ['is_del','<',1];
                    } elseif ($qy_id && $qy_id == $workItem['from']) {
                        $id = 'audit_'.$workItem['from'];
                        $name = $workItem['from'];
                        $whereWork = [
                            ['property_id','=', $property_id],
                            ['qy_id','=', $workItem['from']],
                        ];
                        $whereWork[] = ['status','<',4];
                        $whereWork[] = ['is_del','<',1];
                    } elseif (intval($workItem['from_type']) == 1) {
                        $id = 'audit_'.$workItem['from'];
                        $name = $workItem['from'];
                        $whereWork = [
                            ['property_id' ,'=', $property_id],
                            ['qy_id' ,'=', $workItem['from']],
                        ];
                        $whereWork[] = ['status','<',4];
                        $whereWork[] = ['is_del','<',1];
                    } else {
                        $id = 'audit_'.$workItem['tolist'];
                        $name = $workItem['tolist'];
                        $whereWork = [
                            ['property_id' ,'=', $property_id],
                            ['qy_id' ,'=', $workItem['tolist']],
                        ];
                        $whereWork[] = ['status','<',4];
                        $whereWork[] = ['is_del','<',1];
                    }
                    $user = $dbHouseWorker->get_one($whereWork,'wid as id, name, gender, phone, avatar');
                    if ($user && !is_array($user)) {
                        $user = $user->toArray();
                    }
                    if (empty($user)) {
                        $user = [
                            'id' => $id,
                            'name' => $name,
                            'phone' => '',
                            'gender' => '',
                            'avatar' => dispose_url('/v20/public/static/community/qywx/default.png'),
                        ];
                    }
                    $itemVal['user'] = $user;
                    $chat_data[] = $itemVal;
                }
            }
            $chat_data = array_values($chat_data);
            $data['list'] = $chat_data;
            fdump_api($chat_data, '$chat_data');
        }
        if($param['type'] == 1) {
            $external_id = trim($external_id, ',');
            $external_arr = explode(',', $external_id);
            $new_external_arr = array_unique($external_arr);
            $where = [];
            $where[] = ['customer_id','in',$new_external_arr];
            if($param['name']){
                $where[] = ['name','like','%'.$param['name'].'%'];
            }
            $customer = $dbHouseContactWayUser->getAll($where, 'customer_id as id,phone,name,avatar,gender');
            if($customer){
                $customer = $customer->toArray();
            }
            empty($customer) && $customer = [];
            if ($useridLists) {
                $useridLists = trim($useridLists, ',');
                $useridLists = explode(',', $useridLists);
                $userid_List_arr = array_unique($useridLists);
                $where = [];
                $where[] = ['ExternalUserID','in',$userid_List_arr];
                if($param['name']){
                    $where[] = ['name','like','%'.$param['name'].'%'];
                }
                $customer1 = $dbHouseContactWayUser->getAll($where, 'customer_id as id,phone,name,avatar,gender,ExternalUserID');
                if($customer1){
                    $customer1 = $customer1->toArray();
                }
                $customer = array_merge($customer, $customer1);
                foreach ($userid_List_arr as $users) {
                    $externalUserArr[$users] = $users;
                }
            }
            fdump_api($customer, '$customer');
            $chat_data = [];
            $hasData = [];
            foreach ($customer as $key=>$val){
                if (isset($val['ExternalUserID']) && isset($hasData[$val['ExternalUserID']]) && $hasData[$val['ExternalUserID']]) {
                    continue;
                }
                if (isset($val['ExternalUserID']) && isset($externalUserArr[$val['ExternalUserID']])) {
                    unset($externalUserArr[$val['ExternalUserID']]);
                }
                $to_id = $val['id'];
                if ($isQy) {
                    $sql_where = "(`tolist`='{$qy_id}' AND `from`='{$val['ExternalUserID']}') or (`from`='{$qy_id}' and `tolist`='{$val['ExternalUserID']}')";
                } else {
                    $sql_where = "( (user_id=$work_id or external_id=$work_id) and (to_user_id=$to_id or to_external_id=$to_id) ) or ( (user_id=$to_id or external_id=$to_id) and (to_user_id=$work_id or to_external_id=$work_id) )";
                }
                $audit_info = $dbWorkMsgAuditInfo->getFind($sql_where,'id,msgtime,content');
                $chat_data[$key] = [
                    'content'=>$audit_info['content'],
                    'msgtime'=>$audit_info['msgtime'],
                    'id'=>$audit_info['id'],
                ];
                if(!$val['avatar']){
                    $val['avatar'] = dispose_url('/v20/public/static/community/qywx/default.png');
                }
                !isset($val['name']) || !$val['name'] && $val['name'] = '客户' . $audit_info['id'];
                $chat_data[$key]['user'] = $val;
                isset($val['ExternalUserID']) && $hasData[$val['ExternalUserID']] = 1;
            }
            foreach ($externalUserArr as $externalUser) {
                $user = [
                    'id' => 'audit_'.$externalUser,
                    'name' => $externalUser,
                    'phone' => '',
                    'gender' => '',
                    'avatar' => dispose_url('/v20/public/static/community/qywx/default.png'),
                ];
                $sql_where = "(`tolist`='{$qy_id}' AND `from`='{$externalUser}') or (`from`='{$qy_id}' and `tolist`='{$externalUser}')";
                $audit_info = $dbWorkMsgAuditInfo->getFind($sql_where,'id,msgtime,content');
                $itemVal = [
                    'user' => $user,
                    'content'=>$audit_info['content'],
                    'msgtime'=>$audit_info['msgtime'],
                    'id'=>$audit_info['id'],
                ];
                $chat_data[] = $itemVal;
            }
            $chat_data = array_values($chat_data);
            $data['list'] = $chat_data;
        }

        if($param['type'] == 2 || $param['type'] == 3)
        {
            if($param['type'] == 2) {//员工群
                $group_where[] = ['group_type','=',1];
            }
            if($param['type'] == 3)
            {
                $group_where[] = ['group_type','=',2];
            }
            $dbWorkMsgAuditInfoGroup = new WorkMsgAuditInfoGroup();
            $chat_id = trim($chat_id,',');
            $chat_arr = explode(',',$chat_id);
            $new_chat_id_arr = array_unique($chat_arr);
            $group_where[] = ['id','in',$new_chat_id_arr];
            if($param['name']){
                $group_where[] = ['roomname','like','%'.$param['name'].'%'];
            }
            $group_data = $dbWorkMsgAuditInfoGroup->getList($group_where,'id as chat_id,roomname as chat,group_id as roomid,member_userid,member_external_id,owner_id,owner_type');
            foreach ($group_data as $key=>&$val){
                $member_userid = explode(',',$val['member_userid']);
                $member_external_id = explode(',',$val['member_external_id']);
                $owner_id = $val['owner_id'];
                $owner_type = $val['owner_type'];
                if($owner_type ==1){
                    $owner_info = $dbHouseWorker->get_one(['wid'=>$owner_id],'avatar');
                    $avatar[]= $owner_info['avatar'];
                }else{
                    $owner_info = $dbHouseContactWayUser->getFind(['customer_id'=>$owner_id],'avatar');
                    $avatar[]= $owner_info['avatar'];
                }
                $w_where = [];
                $w_where[] = ['wid','in',$member_userid];
                $worker_avatar = $dbHouseWorker->getColumn($w_where,'avatar');
                $w_where = [];
                $w_where[] = ['customer_id','in',$member_external_id];
                $way_user_avatar = $dbHouseContactWayUser->getColumn($w_where,'avatar');
                $avatarDatas = array_merge($avatar,$worker_avatar,$way_user_avatar);
                $avatarData =[];
                foreach ($avatarDatas as $vs){
                    if($vs){
                        $avatarData[] = $vs;
                    }
                }
                $val['avatarData'] = $avatarData;
                unset($val['member_userid']);
                unset($val['member_external_id']);
                unset($val['owner_id']);
                unset($val['owner_type']);
                $sql_where = [];
                $sql_where[] = ['chat_id','=',$val['chat_id']];
                $audit_info = $dbWorkMsgAuditInfo->getFind($sql_where,'id,msgtime,content','id desc');
                $val['content'] = $audit_info['content'];
                $val['msgtime'] = $audit_info['msgtime'];
                $val['id'] = $audit_info['id'];
            }
            $data['list'] = $group_data;
        }
        return $data;
    }
    public function identifys($to_from_id)
    {
        $member_type = substr($to_from_id,0,2);
        if($member_type == 'wb' || $member_type == 'wo' || $member_type == 'wm'){
            $m_type = 2;//外部或者机器人
        }else{
            $m_type = 1;//内部
        }
        return $m_type;
    }
    //设置会话
    public function setConversation($property_id,$secret,$version_number,$id,$audit_id,$is_checked)
    {
        $dbWorkMsgAudit = new WorkMsgAudit();
        $dbWorkMsgAuditKey = new WorkMsgAuditKey();
        $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        $map[] = ['audit_id','=',$audit_id];
        $map[] = ['id','=',$id];
        $res= $dbWorkMsgAuditKey->editFind($map,['key_version'=>$version_number]);
        $where[] = ['bind_id','=',$property_id];
        $wxBind = $dbHouseEnterpriseWxBind->getOne($where,'corpid');
        if($is_checked){
            $status = 1;
            $data['status'] = $status;
        }else{
            $status = 0;
            $data['status'] = $status;
        }
        $data['secret'] = $secret;
        $dbWorkMsgAudit->editFind(['corp_id'=>$wxBind['corpid']],$data);
        return $res;
    }
    public function getSetConversation($property_id)
    {
        $dbWorkMsgAuditKey = new WorkMsgAuditKey();
        $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        $dbWorkMsgAudit = new WorkMsgAudit();

        $where[] = ['bind_id','=',$property_id];
        $wxBind = $dbHouseEnterpriseWxBind->getOne($where,'corpid');
        $audit_where[] = ['corp_id','=',$wxBind['corpid']];
        $msgAudit = $dbWorkMsgAudit->getFind($audit_where,'id,secret,status');
        if(!$msgAudit){
            $audit_arr=[
                'corp_id'=>$wxBind['corpid'],
            ];
            $msgAudit['id'] = $dbWorkMsgAudit->addFind($audit_arr);
        }
        $map[] = ['audit_id','=',$msgAudit['id']];
        $key_info = $dbWorkMsgAuditKey->getFind($map);
        if(!$key_info){
            $sEnterpriseWeChatService = new EnterpriseWeChatService();
            $data_file = $sEnterpriseWeChatService->create2048($property_id);
            if($data_file){
                $arr = [
                    'audit_id'=>$msgAudit['id'],
                    'private_key' => $data_file['private_key'],
                    'private_key_path' => $data_file['private_path'],
                    'public_key' => $data_file['public_key'],
                    'public_key_path' => $data_file['public_path'],
                ];
                $id = $dbWorkMsgAuditKey->addFind($arr);
            }
//            $id='';
        }else{
            $data_file['private_key'] = $key_info['private_key'];
            $data_file['private_path'] = $key_info['private_key_path'];
            $data_file['public_key'] = $key_info['public_key'];
            $data_file['public_path'] = $key_info['public_key_path'];
            $data_file['key_version'] = $key_info['key_version'];
            $id =$key_info['id'];
        }

        //本地假数据
//        $private_key = 'qweqew';//私钥内容
//        $private_key_file = '/../upload/qrcode/20201202/2d633c695be46686308ff9c2ace18432.jpg';//私钥文件
//        $public_key = '132132';//公钥文件
//        $public_key_file = '/../upload/qrcode/20201202/2d633c695be46686308ff9c2ace18432.jpg';//公钥文件
        //线上真数据
        $private_key = $data_file['private_key'];//私钥内容
        $private_key_file = $data_file['private_path'];//私钥文件
        $public_key = $data_file['public_key'];//公钥文件
        $public_key_file = $data_file['public_path'];//公钥文件
        
        $root_url = request()->server('DOCUMENT_ROOT');
        $private_key_file = str_replace($root_url, '', $private_key_file);
        $public_key_file = str_replace($root_url, '', $public_key_file);
        $private_key_file = cfg('site_url') . $private_key_file;
        $public_key_file = cfg('site_url') . $public_key_file;

        $trusted_ip = $_SERVER['SERVER_ADDR'];
        $ip = GetHostByName($_SERVER['SERVER_NAME']);
        if ($ip=='127.0.0.1' && $trusted_ip) {
            $ip = $trusted_ip;
        }
        $data['trusted_ip'] = $ip;
        $data['private_key'] = $private_key;
        $key = strrpos($private_key_file,'.');
        $file_name = substr($private_key_file,$key);
        $data['private_key_file_name'] = '私钥文件'.$file_name;
        $data['private_key_file'] = $private_key_file;
        $data['public_key'] = $public_key;
        $data['public_key_file'] = $public_key_file;
        $p_key = strrpos($public_key_file,'.');
        $p_file_name = substr($public_key_file,$p_key);
        $data['public_key_file_name'] = '公钥文件'.$p_file_name;
        $data['id'] = $id;

        $data['key_version'] = $key_info['key_version'];
        $data['is_checked'] = $msgAudit['status']==1?true:false;
        $data['secret'] = $msgAudit['secret'];
        $data['audit_id'] = $msgAudit['id'];
        return $data;
    }
    //获取群成员列表
    public function getGroupList($param,$page,$page_size=20)
    {

//        $where[] = ['group_type','=',$param['type']];
        $where[] = ['id','=',$param['chat_id']];
        $serviceWorkMsgAuditInfoGroup =new WorkMsgAuditInfoGroup();
        $dbHouseWorker = new HouseWorker();
        $dbHouseContactWayUser = new HouseContactWayUser();
        $group_list = $serviceWorkMsgAuditInfoGroup->getFind($where,$field=true,$order='id desc');
        if($group_list['owner_type'] ==1){
            $owner_info = $dbHouseWorker->get_one(['wid'=>$group_list['owner_id']],'wid as chat_from_id,name,avatar');
            if (!empty($owner_info)) {
                $owner_info = $owner_info->toArray();
                $owner_info['chat_from_type'] = 1;
                $owner_info['type_name'] = '群主';
                if($owner_info && !$owner_info['avatar']){
                    $owner_info['avatar'] = dispose_url('/v20/public/static/community/qywx/default.png');
                }
            } else {
                $owner_info = [];
            }
        }else{
            $owner_info = $dbHouseContactWayUser->getFind(['customer_id'=>$group_list['owner_id']],'customer_id as chat_from_id,name,avatar');
            if (!empty($owner_info)) {
                $owner_info = $owner_info->toArray();
                $owner_info['chat_from_type'] = 2;
                $owner_info['type_name'] = '群主';
                if($owner_info && !$owner_info['avatar']){
                    $owner_info['avatar'] = dispose_url('/v20/public/static/community/qywx/default.png');
                }
            } else {
                $owner_info = [];
            }
        }
        $owner_list[] = $owner_info;
        $way_user= [];
        $user_list = [];
        $member_userid = explode(',', $group_list['member_userid']);
        $arr = array_flip($member_userid);
        unset($arr[$group_list['owner_id']]);
        $member_userid = array_flip($arr);
        $map = [];
        $map[] = ['wid', 'in', $member_userid];
        if($param['name']){
            $map[] = ['name','like','%'.$param['name'].'%'];
        }
        $user_count = $dbHouseWorker->getMemberCount($map);
        $user_list = $dbHouseWorker->getWorkLists($map, 'wid as chat_from_id,name,avatar', 'wid desc', $page, $page_size);
        $user_list = $user_list->toArray();
        if ($user_list && count($user_list) > 0) {
            foreach ($user_list as &$value) {
                $value['chat_from_type'] = 1;
                $value['type_name'] = '内部成员';
                if(!$value['avatar']){
                    $value['avatar'] = dispose_url('/v20/public/static/community/qywx/default.png');
                }
            }
        }
        $member_external_id = explode(',', $group_list['member_external_id']);
        $where = [];
        $where[] = ['customer_id', 'in', $member_external_id];
        if($param['name']){
            $where[] = ['name','like','%'.$param['name'].'%'];
        }
        $way_count = $dbHouseContactWayUser->getMemberCount($where);
        $way_user = $dbHouseContactWayUser->getLists($where, 'customer_id as chat_from_id,name,avatar','customer_id desc', $page, $page_size);
        if ($way_user && count($way_user) > 0) {
            $way_user = $way_user->toArray();
            foreach ($way_user as &$vals) {
                $vals['chat_from_type'] = 2;
                $vals['type_name'] = '外部联系人';
                if(!$vals['avatar']){
                    $vals['avatar'] = dispose_url('/v20/public/static/community/qywx/default.png');
                }
            }
        }
        if (empty($owner_list)) {
            $owner_list = [];
        }
        if (empty($user_list)) {
            $user_list = [];
        }
        if (empty($way_user)) {
            $way_user = [];
        }
        $user = [];
        if (!empty($owner_list)) {
            foreach ($owner_list as $v1) {
                if($v1){
                    $user[] = $v1;
                }
            }
        }
        if (!empty($user_list)) {
            foreach ($user_list as $v2) {
                if($v2){
                    $user[] = $v2;
                }
            }
        }
        if (!empty($way_user)) {
            foreach ($way_user as $v3) {
                if($v3){
                    $user[] = $v3;
                }
            }
        }
        $data['chatData'] = $user;
        $data['count'] = $user_count+$way_count+1;
        $data['sum'] = $user_count+$way_count+1;
        return $data;
    }
    public function getOnlineGroupMember($property_id,$userid,$corpid,$type)
    {
        $sEnterpriseWeChatService = new EnterpriseWeChatService();
        $dbHouseWorker = new HouseWorker();
        $dbHouseContactWayUser = new HouseContactWayUser();
        $id = [];
        if($type == 1){
            //内部人员
            $whereArr=[['property_id','=',$property_id],['qy_id','=',$userid]];
            $whereArr[] = ['status','<',4];
            $whereArr[] = ['is_del','<',1];
            $worker_info = $dbHouseWorker->get_one($whereArr);
            if($worker_info) {
                $id= $worker_info['wid'];
            }else{
                $interior = $sEnterpriseWeChatService->getPermitUserInfo($property_id, $userid);
                if ($interior && $interior['errcode'] == 0) {
                    $data = [
                        'qy_id' => $userid,
                        'phone' => $interior['mobile'],
                        'name' => $interior['name'],
                        'gender' => $interior['gender'],
                        'avatar' => $interior['avatar'],
                    ];
                    $id = $dbHouseWorker->addFind($data);
                }
            }
            return $id;
        }else {
            //外部成员
            $way_user = $dbHouseContactWayUser->getFind(['corpid' => $corpid, 'ExternalUserID' => $userid]);
            if ($way_user) {
                $id = $way_user['customer_id'];
            } else {
                $external = $sEnterpriseWeChatService->getExternalUserInfo($property_id, $userid);
                if ($external && $external['errcode'] == 0 && $external['external_contact']) {
                    $external_contact = $external['external_contact'];
                    $data = [
                        'corpid' => $corpid,
                        'name' => $external_contact['name'],
                        'avatar' => $external_contact['avatar'],
                        'type' => $external_contact['type'],
                        'gender' => $external_contact['gender'],
                        'ExternalUserID' => $userid,
                    ];
                    $id = $dbHouseContactWayUser->addFind($data);
                }else if($external['errcode'] == 84061){
                    $way_user_abnormal = $dbHouseContactWayUser->getFind(['corpid' => $corpid,'type' => 3, 'is_qywx_abnormal' => 1]);
                    if($way_user_abnormal){
                        $id = $way_user_abnormal['customer_id'];
                    }else{
                        $data = [
                            'corpid' => $corpid,
                            'name' => '默认用户(异常)',
                            'avatar' => '',
                            'type' => 3,
                            'gender' => 0,
                            'ExternalUserID' => $userid,
                        ];
                        $id = $dbHouseContactWayUser->addFind($data);
                    }
                }else{
                    $way_user = $dbHouseContactWayUser->getFind(['corpid' => $corpid, 'UserID' => $userid]);
                    if ($way_user) {
                        $id = $way_user['customer_id'];
                    }
                }
            }
            return $id;
        }

    }
}