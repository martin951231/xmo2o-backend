<?php
/**
 * @author : liukezhu
 * @date : 2022/8/3
 */

namespace app\community\model\service;

use app\common\model\service\send_message\SmsService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HousePublicRentalApplyRecord;
use app\community\model\db\HousePublicRentalApplyRecordLog;
use app\community\model\db\HousePublicRentalAppointmentRecord;
use app\community\model\db\HousePublicRentalArrangingRecord;
use app\community\model\db\HousePublicRentalQueuingRule;
use app\community\model\db\HousePublicRentalRentingRecord;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\PluginMaterialDiyFile;
use app\community\model\db\PluginMaterialDiyTemplate;
use app\community\model\db\PluginMaterialDiyValue;
use app\community\model\service\HousePublicRentalRentingService;
use app\community\model\db\User;

class HousePublicRentalApplyService
{

    protected $time;
    protected $HousePublicRentalApplyRecord;
    protected $PluginMaterialDiyFile;
    protected $PluginMaterialDiyTemplate;
    protected $HouseVillage;
    protected $HouseVillageService;
    protected $PluginMaterialDiyValue;
    protected $TemplateNewsService;
    protected $SmsService;
    protected $HousePublicRentalQueuingRule;
    protected $HousePublicRentalAppointmentRecord;
    protected $HousePublicRentalApplyRecordLog;
    protected $HousePublicRentalArrangingRecord;
    protected $HouseVillageUserBind;
    protected $HouseVillageUserVacancy;
    protected $User;
    protected $HousePublicRentalRentingRecord;

    //退租状态
    public $cancel_status=[
        '10'=>'退租待审核',
        '12'=>'退租审核通过',
        '11'=>'退租审核不通过',
        4=>'排号待审核',
        5=>'排号自动审核通过',
        6=>'排号审核不通过',
        7=>'排号审核通过',
        8=>'验房合格',
        9=>'验房不合格'
    ];
    //流程状态
    public $flow_status=[
        '10'=>'入住待审核',
        '11'=>'入住审核不通过',
        '12'=>'入住审核通过',
        '20'=>'凭证审核不通过',
        '21'=>'凭证审核通过',
        '22'=>'退租审核通过',
        '30'=>'排号待审核',
        '31'=>'排号自动审核不通过',
        '32'=>'排号自动审核通过',
        '33'=>'排号审核不通过',
        '34'=>'排号审核通过',
        '35'=>'取消排号',
        '40'=>'验房合格',
        '41'=>'验房不合格',
        '42'=>'领取钥匙',
    ];

    //入住状态
    public $search_hotel_status=[
        ['key'=>0,'value'=>'全部'],
        ['key'=>10,'value'=>'待审核'],
        ['key'=>11,'value'=>'已拒绝'],
        ['key'=>12,'value'=>'已通过'],
    ];
    //类型状态
    public $search_hotel_type_status=[
        'voucher_status'=>[
            ['key'=>'','value'=>'全部'],
            ['key'=>12,'value'=>'入住审核通过'],
            ['key'=>21,'value'=>'凭证审核通过'],
            ['key'=>20,'value'=>'凭证审核不通过'],
        ],
        'arranging_status'=>[
            ['key'=>'','value'=>'全部'],
            ['key'=>30,'value'=>'排号待审核'],
//            ['key'=>32,'value'=>'排号自动审核通过'],
            ['key'=>33,'value'=>'排号审核不通过'],
//            ['key'=>34,'value'=>'排号审核通过'],
        ],
        'inspection_status'=>[
            ['key'=>'','value'=>'全部'],
            ['key'=>40,'value'=>'验房合格'],
            ['key'=>41,'value'=>'验房不合格'],
            ['key'=>42,'value'=>'领取钥匙'],
        ],
    ];
    // 对应操作动作
    public $log_name = [
        'user_submit' => '入住审核通过',
        'voucher_success' => '凭证审核通过',
        'voucher_refuse' => '凭证审核拒绝',
        'arranging_appointment' => '排号预约',
        'arranging_appointment_success' => '排号审核通过',
        'arranging_appointment_auto_success' => '排号自动审核通过',
        'arranging_appointment_refuse' => '排号审核拒绝',
        'arranging_appointment_cancel' => '排号取消预约',
        'inspection_success'=>'验房合格',
        'inspection_refuse'=>'验房不合格',
        'inspection_receive'=>'验房领取钥匙',
    ];
    //流程状态对应操作动作
    public $flow_log_name=[
        '20'=>'voucher_refuse',
        '21'=>'voucher_success',
        '30'=>'arranging_appointment',
        '32'=>'arranging_appointment_auto_success',
        '33'=>'arranging_appointment_refuse',
        '34'=>'arranging_appointment_success',
        '35'=>'arranging_appointment_cancel',
        '40'=>'inspection_success',
        '41'=>'inspection_refuse',
        '42'=>'inspection_receive',
    ];
    // 图片文件的所有类型
    public $image_type=[
        'image/png', 'image/x-png', 'image/jpg', 'image/jpeg',
        'image/pjpeg', 'image/gif', 'image/x-icon'
    ];

    public function __construct()
    {
        $this->time=time();
        $this->HousePublicRentalApplyRecord=new HousePublicRentalApplyRecord();
        $this->PluginMaterialDiyFile=new PluginMaterialDiyFile();
        $this->PluginMaterialDiyTemplate=new PluginMaterialDiyTemplate();
        $this->HouseVillage=new HouseVillage();
        $this->HouseVillageService = new HouseVillageService();
        $this->PluginMaterialDiyValue=new PluginMaterialDiyValue();
        $this->TemplateNewsService=new TemplateNewsService();
        $this->SmsService=new SmsService();
        $this->HousePublicRentalQueuingRule=new HousePublicRentalQueuingRule();
        $this->HousePublicRentalApplyRecordLog=new HousePublicRentalApplyRecordLog();
        $this->HousePublicRentalAppointmentRecord=new HousePublicRentalAppointmentRecord();
        $this->HousePublicRentalArrangingRecord=new HousePublicRentalArrangingRecord();
        $this->HouseVillageUserBind=new HouseVillageUserBind();
        $this->HouseVillageUserVacancy = new HouseVillageUserVacancy();
        $this->User = new User();
        $this->HousePublicRentalRentingRecord=new HousePublicRentalRentingRecord();
    }

    //====================================todo 共用方法 start======================================

    //todo 查询入住记录
    public function getApplyRecordListMethod($where,$field,$order,$page,$limit){
        $list = $this->HousePublicRentalApplyRecord->getList($where,$field,$order,$page,$limit);
        $count = $this->HousePublicRentalApplyRecord->getCount($where);
        return ['list'=>$list,'count'=>$count];
    }

    //todo 查询操作记录
    public function getApplyRecordLogMethod($where,$field=true,$order,$page,$limit){
        $list = $this->HousePublicRentalApplyRecordLog->getList($where,$field,$order,$page,$limit);
        $count = $this->HousePublicRentalApplyRecordLog->getCount($where);
        return ['list'=>$list,'count'=>$count];
    }

    //todo 查询模板数据
    public function getDiyTemplateMethod($where,$field=true,$order,$page,$limit){
        $list = $this->PluginMaterialDiyTemplate->getList($where,$field,$order,$page,$limit);
        $count = $this->PluginMaterialDiyTemplate->getCount($where);
        return ['list'=>$list,'count'=>$count];
    }

    //todo 查询提交模板数据
    public function getDiyValueMethod($where,$field=true,$order,$page,$limit){
        $list = $this->PluginMaterialDiyValue->getLeftList($where,$field,$order,$page,$limit);
        $count = $this->PluginMaterialDiyValue->getLeftCount($where);
        return ['list'=>$list,'count'=>$count];
    }

    //todo 查询附件记录
    public function getEnclosureMethod($where,$field=true,$order,$page,$limit){
        $list = $this->PluginMaterialDiyFile->getList($where,$page,$limit,$field,$order);
        if($list){
            foreach ($list as &$v){
                if(isset($v['file_type'])){
                    $is_image = false;
                    if (in_array($v['file_type'], $this->image_type)) {
                        $is_image= true;
                    }
                    $v['is_image']=$is_image;
                }
                if(isset($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
                if(isset($v['account'])){
                    $v['account']=empty($v['account']) ? '--' : $v['account'];
                }
                if(isset($v['file_url'])){
                    $v['file_url'] = empty($v['file_url']) ? false : replace_file_domain($v['file_url']);
                }
            }
            unset($v);
        }
        $count = $this->PluginMaterialDiyFile->getCount($where);
        return ['list'=>$list,'count'=>$count];
    }

    //todo 查询单条入住记录
    public function getApplyRecordFindMethod($where,$field=true,$source=0){
        $info=$this->HousePublicRentalApplyRecord->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            if($source){
                return false;
            }else{
                throw new \think\Exception('该数据不存在，请重新操作');
            }
        }
        return $info->toArray();
    }

    //todo 查询单条操作记录
    public function getApplyRecordLogFindMethod($where,$field=true,$source=0){
        $info=$this->HousePublicRentalApplyRecordLog->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            if($source){
                return false;
            }else{
                return array();
            }
        }
        return $info->toArray();
    }

    //todo 查询入住记录数据
    public function getRecordBindUserMethod($where,$field=true){
        $info=$this->HousePublicRentalApplyRecord->getRecordBindUser($where,$field);
        if (!$info || $info->isEmpty()) {
            throw new \think\Exception('该数据不存在，请重新操作');
        }
        return $info->toArray();
    }

    //todo 查询排号时间内排号成功的数量
    public function getDayArrangingCountMethod($type,$village_id,$queuing_rule_id,$start_time,$end_time){
        if($type==2){
            $where[]=['a.type','=',$type];
            $where[]=['a.status','=',1];
            $where[]=['a.village_id','=',$village_id];
            $where[]=['a.queuing_rule_id','=',$queuing_rule_id];
            $where[]=['a.apply_time','between',[$start_time,$end_time]];
            $where[]=['a.del_time', '=', 0];
            $where[]=['r.arranging_record_id', '>', 0];
            $where[]=['r.del_time', '=', 0];
            $count = $this->HousePublicRentalRentingRecord->getRecordArrangingCount($where);
        }else{
            $where[]=['a.type','=',$type];
            $where[]=['a.status','=',1];
            $where[]=['a.village_id','=',$village_id];
            $where[]=['a.queuing_rule_id','=',$queuing_rule_id];
            $where[]=['a.apply_time','between',[$start_time,$end_time]];
            $where[]=['a.del_time', '=', 0];
            $where[]=['r.arranging_record_id', '>', 0];
            $where[]=['r.del_time', '=', 0];
            $count = $this->HousePublicRentalApplyRecord->getRecordArrangingCount($where);
        }
        return $count;
    }

    //todo 查询单条排号规则
    public function getQueuingRuleFindMethod($where,$field=true){
        $info= $this->HousePublicRentalQueuingRule->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            throw new \think\Exception('未设置排号规则，请稍后再试！');
        }
        return $info->toArray();
    }

    //todo 写入操作记录
    public function appointmentRecordMethod($record_id,$log_type,$param=[]){
        $where[]=['r.id','=',$record_id];
        $where[]=['r.del_time', '=', 0];
        $field='r.id,r.village_id,r.uid,u.nickname,u.phone';
        $record_info=$this->HousePublicRentalApplyRecord->getRecordBindUser($where,$field);
        if (!$record_info || $record_info->isEmpty()) {
            return false;
        }
        $record_info=$record_info->toArray();
        $log=[
            'type'=>1,
            'village_id'=>$record_info['village_id'],
            'rental_id'=>$record_info['id'],
            'operator_id'=>0,
            'log_type'=>$log_type,
            'log_uid'=>$record_info['uid'],
            'add_time'=>$this->time
        ];
        if(isset($param['log_operator'])){
            $log['log_operator']=$param['log_operator'];
        }
        if(isset($param['log_imgs']) && !empty($param['log_imgs'])){
            if(is_array($param['log_imgs'])){
                $log['log_imgs']=serialize($param['log_imgs']);
            }else{
                $log['log_imgs']=$param['log_imgs'];
            }
        }
        switch ($log_type) {
            case "user_submit":
                $log['log_operator']=$record_info['nickname'];
                $log['log_phone']=$record_info['phone'];
                break;
            case "voucher_success":

                break;
            case "voucher_refuse":

                break;
            case "arranging_appointment":

                break;
            case "arranging_appointment_auto_success":

                break;
            case "arranging_appointment_success":

                break;
            case "arranging_appointment_refuse":

                break;
            case "arranging_appointment_cancel":

                break;
            case "inspection_success":

                break;
            case "inspection_refuse":

                break;
            case "inspection_receive":

                break;
            default:
                return false;
        }
        if(isset($param['reason'])){
            $log['log_content']=$param['reason'];
        }
        if($param){
            $log['log_param']=serialize($param);
        }
        return $this->HousePublicRentalApplyRecordLog->addOne($log);
    }

    //todo 获取流程状态按钮
    public function getFlowButtonStatusMethod($flow_status){
        $examine_arr=[];
        if(in_array($flow_status,[12,20])){ //入住审核通过,凭证审核不通过
            $examine_arr=[
                ['key'=>21,'value'=>'通过'],
                ['key'=>20,'value'=>'不通过']
            ];
        }
        else if(in_array($flow_status,[30])){ //排号待审核
            $examine_arr=[
                ['key'=>34,'value'=>'通过'],
                ['key'=>33,'value'=>'不通过']
            ];
        }
        else if(in_array($flow_status,[32,34,41])){ //排号自动审核通过,排号审核通过
            $examine_arr=[
                ['key'=>40,'value'=>'合格'],
                ['key'=>41,'value'=>'不合格']
            ];
        }
        return $examine_arr;
    }

    //todo 获取办理入住类型 $source=0 默认查询 1：给后端查询 2：给用户端查询
    public function getApplyRecordFlowTypeMethod($flow_status,$source=0){
        $type=0;
        $value='';
        $is_examine=false;
        if(in_array($flow_status,[12,20,21])){
            $type=1;
            $value='凭证';
            if(in_array($flow_status,[12,20])){
                $is_examine=true;
            }
        }
        else if(in_array($flow_status,[30,31,33])){
            $type=2;
            $value='排号';
            if($flow_status == 30){
                $is_examine=true;
            }
        }
        else if(in_array($flow_status,[32,34,40,41,42])){
            $type=3;
            $value='验房';
            if(in_array($flow_status,[32,34,41])){
                $is_examine=true;
            }
        }
        $data=[
            'type'=>$type,
            'value'=>$value
        ];
        $examine_arr=$this->getFlowButtonStatusMethod($flow_status);
        if($source){
            if($source == 2 && $flow_status == 40 ){ //针对用户端 验房合格 下步流程就是领取钥匙
                $is_examine=true;
                $examine_arr=[
                    ['key'=>42,'value'=>'领取钥匙']
                ];
            }
            $data['is_examine'] =$is_examine;
            $data['examine_data'] =$examine_arr;
        }
        return $data;
    }

    //todo 校验申请入住权限按钮
    public function checkApplyPowerButtonMethod($menus=[],$param=[]){
        $is_see=false;
        $is_edit=false;
        $is_del=false;
        if(in_array(112049,$menus)){
            $is_see=true;
        }
        if(in_array(112050,$menus)){
            $is_edit=true;
        }
        if(in_array(112051,$menus)){
            $is_del=true;
        }
        if($param['flow_status'] == 12){
            $is_edit=false;
            $is_del=false;
        }
        return ['is_see'=>$is_see,'is_edit'=>$is_edit,'is_del'=>$is_del];
    }

    //todo 校验办理入住权限按钮
    public function checkHandlePowerButtonMethod($menus=[],$data=[],$param=[]){
        $is_see=false;
        $is_edit=false;
        $is_contract=false;
        $is_del=false;
        if(in_array(112053,$menus)){
            $is_see=true;
        }
        if(in_array(112054,$menus)){
            $is_edit=true;
        }
        if(in_array(112055,$menus)){
            $is_contract=true;
        }
        if(in_array(112056,$menus)){
            $is_del=true;
        }
        if(isset($data['is_examine']) && !$data['is_examine']){
            $is_edit=false;
        }
        return ['is_see'=>$is_see,'is_edit'=>$is_edit,'is_contract'=>$is_contract,'is_del'=>$is_del];
    }

    //todo 组装url生成二维码
    public function assembleUrlMethod($url){
        $path=urlencode(htmlspecialchars_decode($url));
        return cfg('site_url').'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.$path;
    }

    //todo 获取预约记录状态
    public function getRecordStatusMethod($status,$cfrom=''){
        $status=(int)$status;
        $flow_type=$this->getApplyRecordFlowTypeMethod($status);
        $flow_status='';
        $flow_button=['is_see'];
        switch ($status) {
            case 30: //排号待审核
                $flow_status='待审核';
                break;
            case 31: //排号自动审核不通过
                $flow_status='审核拒绝';
                break;
            case 32: //排号自动审核通过  可取消预约 走凭证审核通过
                $flow_status='审核通过';
                $flow_button=['is_inspection','cancel_appoint'];
                break;
            case 33: //排号审核不通过
                $flow_status='审核拒绝';
                break;
            case 34: //排号审核通过 可取消预约 走凭证审核通过
                $flow_status='审核通过';
                $flow_button=['is_inspection','cancel_appoint'];
                break;
            case 35: //取消排号
                $flow_status='取消排号';
                break;
            case 40: //验房合格
                $flow_status='验房合格';
                $flow_button=['receive_key'];
                break;
            case 41: //验房不合格
                $flow_status='验房不合格';
                if($cfrom=='user'){
                    $flow_button=['is_inspection','is_see'];
                }
                break;
            case 42: //领取钥匙
                $flow_status='验房合格';
                break;
        }
        return ['type'=>$flow_type['value'],'status'=>$flow_status,'button'=>$flow_button];
    }

    //todo 获取用户信息
    public function getUserBind($pigcms_id,$field=true){
        $uu=$this->HouseVillageUserBind->getOne([
            ['pigcms_id', '=', $pigcms_id],
            ['status','<>',4]
        ],$field);
        if (!$uu || $uu->isEmpty()) {
            return array();
        }
        return $uu->toArray();
    }

    //todo 用户端写入操作记录
    public function userAddAppointmentRecordMethod($param){
        $HousePublicRentalRentingService=new HousePublicRentalRentingService();
        if(isset($param['ht_log_operator'])){
            $log_operator=$param['ht_log_operator'];
        }else{
            $uu=$this->getUserBind($param['pigcms_id'],'name');
            $log_operator=$uu['name'];
        }
        //写入log记录
        $data=[
            'reason'=>$param['remarks'],
            'log_operator'=>$log_operator,
        ];
        if (isset($param['appoint_time'])){
            $data['appoint_time']=$param['appoint_time'];
        }
        if(isset($param['imags'])){
            $data['log_imgs']=serialize($param['imags']);
        }
        if(isset($param['source_type']) && $param['source_type'] == 2){  //todo 办理退租
            $log_type=$HousePublicRentalRentingService->flow_log_name[$param['flow_status']];
            $result= $HousePublicRentalRentingService->rentingRecordMethod($param['record_id'],$log_type,$data);
        }else{ //todo 办理入住
            $log_type=$this->flow_log_name[$param['flow_status']];
            $result= $this->appointmentRecordMethod($param['record_id'],$log_type,$data);
        }
        return $result;
    }

    //todo 审核 触发发送通知
    public function sendUserNoticeMethod($record_id,$type){
        //本地环境 暂时关闭
        $where=[
            ['r.id','=',$record_id],
            ['r.del_time', '=', 0]
        ];
        $obj=$this->HousePublicRentalApplyRecord;
        if(in_array($type,['renting_examine','renting_arranging_examine'])){ //针对办理退租处理
            $obj=$this->HousePublicRentalRentingRecord;
        }
        $info = $obj->getRecordUser($where,'r.id,r.template_id,r.value_id,r.village_id,r.pigcms_id,r.uid,r.flow_status,u.openid,u.nickname,u.phone,v.village_name,v.property_id,r.arranging_record_id');
        if (!$info || $info->isEmpty()) {
            return true;
        }
        $info=$info->toArray();
        fdump_api(['触发发送通知=='.__LINE__,$record_id,$type,$info],'public_rental/sendNoticeMethod',1);
        $village_info_extend = $this->HouseVillageService->getHouseVillageInfoExtend(['village_id'=>$info['village_id']]);
        $urge_notice_type=0;
        if($village_info_extend && isset($village_info_extend['urge_notice_type'])){
            //1短信通知2微信模板通知3短信和微信模板通知
            $urge_notice_type=$village_info_extend['urge_notice_type'];
        }
        switch ($type) {
            case "hotel_examine": //入住审核
                if($info['flow_status'] == 12){
                    $msg='已通过';
                }
                elseif($info['flow_status'] == 11){
                    $msg='未通过';
                }
                else{
                    return true;
                }
                /**
                 * todo 此处需要换前端链接 已替换
                 */
                $href= get_base_url('/pages/houseManage/pages/subscribeDetail?village_id='.$info['village_id'].'&pigcms_id='.$info['pigcms_id'].'&record_id='.$info['id'].'&page_type=checkin');
                $first=$info['nickname'].'您好，您在'.$info['village_name'].'小区公租房入住申请'.$msg;
                $keyword1='公租房入住申请';
                $content=L_('尊敬的x1您好，您在x2小区公租房入住申请x3', array(
                    'x1' => $info['nickname'],
                    'x2' => $info['village_name'],
                    'x3' => $msg,
                ));
                break;
            case "arranging_examine": //办理入住排号流程
                if(in_array($info['flow_status'],[32,34])){
                    $msg='已通过';
                }
                elseif(in_array($info['flow_status'],[31,33])){
                    $msg='未通过';
                }
                else{
                    return true;
                }
                if(!$info['arranging_record_id']){
                    return true;
                }
                $arranging_record=$this->HousePublicRentalArrangingRecord->getOne([
                    ['id', '=', $info['arranging_record_id']],
                    ['del_time', '=', 0]
                ],'id,appoint_time');
                if (!$arranging_record || $arranging_record->isEmpty()) {
                    return true;
                }
                /**
                 * todo 此处需要换前端链接 已替换
                 */
                $appoint_time=date('Y年m月d日',$arranging_record['appoint_time']);
                $href= get_base_url('/pages/houseManage/pages/subscribeDetail?village_id='.$info['village_id'].'&pigcms_id='.$info['pigcms_id'].'&record_id='.$info['id'].'&page_type=checkin');
                $first=$info['nickname'].'您好，您在'.$info['village_name'].'小区申请的'.$appoint_time.'排号验房'.$msg;
                $keyword1='排号验房';
                $content=L_('尊敬的x1您好，您在x2小区申请的x3排号验房x4', array(
                    'x1' => $info['nickname'],
                    'x2' => $info['village_name'],
                    'x3' => $appoint_time,
                    'x4' => $msg,
                ));
                break;
            case "renting_examine": //退租审核
                if($info['flow_status'] == 12){
                    $msg='已通过';
                }
                elseif($info['flow_status'] == 11){
                    $msg='未通过';
                }
                else{
                    return true;
                }
                /**
                 * todo 此处需要换前端链接 已替换
                 */
                $href= get_base_url('/pages/houseManage/pages/subscribeDetail?village_id='.$info['village_id'].'&pigcms_id='.$info['pigcms_id'].'&record_id='.$info['id'].'&page_type=rentcancell');
                $first=$info['nickname'].'您好，您在'.$info['village_name'].'小区公租房退租申请'.$msg;
                $keyword1='公租房入住申请';
                $content=L_('尊敬的x1您好，您在x2小区公租房退租申请x3', array(
                    'x1' => $info['nickname'],
                    'x2' => $info['village_name'],
                    'x3' => $msg,
                ));
                break;
            case "renting_arranging_examine": //办理退租排号流程
                if(in_array($info['flow_status'],[22,23])){
                    $msg='已通过';
                }
                elseif(in_array($info['flow_status'],[21])){
                    $msg='未通过';
                }
                else{
                    return true;
                }
                if(!$info['arranging_record_id']){
                    return true;
                }
                $arranging_record=$this->HousePublicRentalArrangingRecord->getOne([
                    ['id', '=', $info['arranging_record_id']],
                    ['del_time', '=', 0]
                ],'id,appoint_time');
                if (!$arranging_record || $arranging_record->isEmpty()) {
                    return true;
                }
                /**
                 * todo 此处需要换前端链接 已替换
                 */
                $appoint_time=date('Y年m月d日',$arranging_record['appoint_time']);
                $href= get_base_url('/pages/houseManage/pages/subscribeDetail?village_id='.$info['village_id'].'&pigcms_id='.$info['pigcms_id'].'&record_id='.$info['id'].'&page_type=rentcancell');
                $first='您好，您在'.$info['village_name'].'小区申请的'.$appoint_time.'排号验房'.$msg;
                $keyword1='排号验房';
                $content=L_('尊敬的x1您好，您在x2小区申请的x3排号验房x4', array(
                    'x1' => $info['nickname'],
                    'x2' => $info['village_name'],
                    'x3' => $appoint_time,
                    'x4' => $msg,
                ));
                break;
            default:
                return true;
        }
        $temData=[
            'href' => $href,
            'wecha_id' => $info['openid'],
            'first' => $first,
            'keyword1' => $keyword1,
            'keyword2' => '已发送',
            'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
            'remark' => '请点击查看详细信息！'
        ];
        $smsData=[
            'type' => 'fee_notice',
            'uid'=>$info['uid'],
            'village_id'=>$info['village_id'],
            'mobile'=>$info['phone'],
            'sendto'=> 'user',
            'mer_id'=>0,
            'store_id'=>0,
            'content'=>$content
        ];
        if(!empty($info['openid']) && $urge_notice_type!=1){
            $property_id=$info['property_id'];
            $this->TemplateNewsService->sendTempMsg('OPENTM400166399', $temData,0,$property_id,($property_id ? 1 : 0));
        }
        if(!empty($info['phone']) && $urge_notice_type!=2){
            $this->SmsService->sendSms($smsData);
        }
        fdump_api(['集合=='.__LINE__,$type,$record_id,$temData,$smsData],'public_rental/sendNoticeMethod',1);
        return true;
    }

    //todo 组装办理记录数据
    public function assembleRecordLogMethod($data,$source_type=1){
        $log_param=[];
        if(isset($data['log_param']) && @unserialize($data['log_param'])){
           $log_param=unserialize($data['log_param']);
        }
        $log_name=$this->log_name;
        if($source_type == 2){ //todo 办理退租
            $log_name=(new HousePublicRentalRentingService())->log_name;
        }
        $log=[];
        $log[]=['label'=>'类型','value'=>$log_name[$data['log_type']],'type'=>'txt'];
        if(in_array($data['log_type'],['user_submit','renting_submit'])){
            $log[]=['label'=>'申请人','value'=>$data['log_operator'],'type'=>'txt'];
        }else{
            $log[]=['label'=>'操作人','value'=>$data['log_operator'],'type'=>'txt'];
        }
        if($data['log_type'] == 'arranging_appointment' && isset($log_param['appoint_time'])){
            $log[]=['label'=>'排号时间','value'=>date('Y-m-d H:i:s',$log_param['appoint_time']),'type'=>'txt'];
        }
        if(in_array($data['log_type'],['user_submit','renting_submit'])){
            $log[]=['label'=>'申请时间','value'=>date('Y-m-d H:i:s',$data['add_time']),'type'=>'txt'];
        }else{
            $log[]=['label'=>'操作时间','value'=>date('Y-m-d H:i:s',$data['add_time']),'type'=>'txt'];
        }
        if ($data['log_imgs'] && @unserialize($data['log_imgs'])) {
            $img = unserialize($data['log_imgs']);
            foreach ($img as &$vv){
                $vv=replace_file_domain($vv);
            }
            unset($vv);
            $log[]=['label'=>'图片','value'=>$img,'type'=>'img'];
        }
        if(!in_array($data['log_type'],['user_submit','renting_submit'])){
            $log[]=['label'=>'审核意见','value'=>$data['log_content'],'type'=>'txt'];
        }
        return $log;
    }

    //todo 获取平台用户
    public function getUserMethod($uid,$field=true){
        $uu=$this->User->getOne(['uid'=>$uid],$field);
        if (!$uu || $uu->isEmpty()){
            throw new \think\Exception('该用户不存在，请重新登录');
        }
        return $uu->toArray();
    }

    //todo 校验房间绑定业主的唯一性
    public function checkUserBindVacancyMethod($vacancy_id,$uid){
        $uu=$this->HouseVillageUserBind->getOne([
            ['vacancy_id', '=', $vacancy_id],
            ['type', 'in', [0,3]],
            ['status','<>',4],
            ['uid','<>',$uid],
        ],'pigcms_id');
        if ($uu && !$uu->isEmpty()) {
            throw new \think\Exception('该房间已绑定业主，请重新操作');
        }
        return true;
    }

    //todo 校验该手机号是小区否有业主身份
    public function checkUserBindPhoneMethod($village_id,$phone){
        $uu=$this->HouseVillageUserBind->getOne([
            ['village_id', '=', $village_id],
            ['phone', '=', $phone],
            ['type', 'in', [0,3]],
            ['status','<>',4],
        ],'pigcms_id,vacancy_id');
        if ($uu && !$uu->isEmpty()) {
            return $uu->toArray();
        }else{
            return [];
        }
    }

    //todo 针对无小区身份 同步生成业主身份
    public function addUserBindMethod($param){
        //获取平台用户信息
        $user=$this->getUserMethod($param['uid'],'nickname,phone');
        //校验该手机号是否存在身份
        //$user_info=$this->checkUserBindPhoneMethod($param['village_id'],$user['phone']);
        $user_info=array(); //直接填加 不能覆盖原有的
        //获取房间数据
        $room_info=$this->HouseVillageUserVacancy->getInfo([
            ['a.pigcms_id','=',$param['vacancy_id']]
        ],'','a.pigcms_id,a.floor_id,a.single_id,a.housesize,a.layer,a.room,a.layer_id,a.usernum');
        if (!$room_info){
            throw new \think\Exception('未查询到相应房间，请重新操作');
        }
        if(!$user_info){ //不存在业主身份
            $user_data=[
                'village_id'=>$param['village_id'],
                'uid'=>$param['uid'],
                'name'=>$user['nickname'],
                'phone'=>$user['phone'],
                'type'=>0,
                'status'=>1,
                'add_time'=>$this->time,
                'single_id'=>$param['single_id'],
                'floor_id'=>$param['floor_id'],
                'layer_id'=>$param['layer_id'],
                'vacancy_id'=>$param['vacancy_id'],
                'housesize'=>$room_info['housesize'],
                'layer_num'=>$room_info['layer'],
                'room_addrss'=>$room_info['room'],
                'usernum'=>rand(0,99999) . '-' . time().'-hpr'
            ];
            $pigcmsid=$this->HouseVillageUserBind->addOne($user_data);
            if($pigcmsid>0 && isset($param['apply_id']) && ($param['apply_id']>0)){
                $this->HousePublicRentalApplyRecord->saveOne([
                    ['id', '=', $param['apply_id']],
                    ['del_time', '=', 0]
                ],array('pigcms_id'=>$pigcmsid));
            }
        }else{
            //绑定成为其它房间业主
            $user_data=[
                'uid'=>$param['uid'],
                'single_id'=>$param['single_id'],
                'floor_id'=>$param['floor_id'],
                'layer_id'=>$param['layer_id'],
                'vacancy_id'=>$param['vacancy_id'],
                'housesize'=>$room_info['housesize'],
                'layer_num'=>$room_info['layer'],
                'room_addrss'=>$room_info['room'],
            ];
            $this->HouseVillageUserBind->saveOne(['pigcms_id'=>$user_info['pigcms_id']],$user_data);
        }
        $data_info['uid'] = $param['uid'];
        $data_info['name'] = $user['nickname'];
        $data_info['phone'] = $user['phone'];
        $data_info['type'] = 0;
        $data_info['status'] = 3;
        $data_info['park_flag'] = '';
        $data_info['add_time'] = $this->time;
        $this->HouseVillageUserVacancy->saveOne(['pigcms_id'=>$room_info['pigcms_id']],$data_info);
        return true;
    }



    //====================================todo  用户端+移动管理端 方法 start======================================

    /**
     * 获取用户端入住申请
     * @author: liukezhu
     * @date : 2022/8/25
     * @param $param
     * @return mixed
     */
    public function getUserApplyRecordList($param){
        $where[]=['village_id','=',$param['village_id']];
        $where[]=['del_time', '=', 0];
        $whereor=[
            [
                ['uid','=',$param['uid']],
                ['pigcms_id','=',$param['pigcms_id']],
            ],
            [
                ['uid','=',$param['uid']],
                ['pigcms_id','=',0],
            ]
        ];
        $field='id as record_id,number,template_id,value_id,village_id,pigcms_id,flow_status,add_time';
        $order='id desc';
        $count=0;
        $list = $this->HousePublicRentalApplyRecord->getRecordList($where,$field,$order,$param['page'],$param['limit'],$whereor);
        if ($list && !$list->isEmpty()){
            foreach ($list as &$v){
                $qr_code='';
                if($v['flow_status'] == 10){
                    $flow_arr=[
                        'color'=>'black',
                        'title'=>'待审核'
                    ];
                }
                elseif ($v['flow_status'] == 11){
                    $flow_arr=[
                        'color'=>'red',
                        'title'=>'已拒绝'
                    ];
                }
                else{
                    $flow_arr=[
                        'color'=>'#2182F3',
                        'title'=>'已通过'
                    ];
                }
                /**
                 * todo 此处需要换前端链接 已替换
                 */
                //扫码进入详情
                //$qr_code=$this->assembleUrlMethod(get_base_url('/pages/houseManage/pages/subscribeDetail?village_id='.$v['village_id'].'&pigcms_id='.$v['pigcms_id'].'&record_id='.$v['record_id'].'&page_type=checkin'));
                $urltmp=cfg('site_url').'/packapp/community/pages/Community/checkoutApplication/voucherManagement?record_id='.$v['record_id'].'&page_type=checkin&type=1&gotoIndex=tem_msg';
                if($v['flow_status']==12){
                    $urltmp=cfg('site_url').'/packapp/community/pages/Community/checkoutApplication/voucherManagement?record_id='.$v['record_id'].'&page_type=checkin&type=2&gotoIndex=tem_msg';
                }
                $urltmp=str_replace('//packapp','/packapp ',$urltmp);
                $qr_code=$this->assembleUrlMethod($urltmp);
                    
                if(in_array($v['flow_status'],array(10,11))){
                    $qr_code='';
                }
                //点击跳转装修模板
                $jump_url=get_base_url('pages/village/materialDiy/materialDetails?diy_id='.$v['value_id'].'&from_id='.$v['village_id']);
                $v['propsList']=[
                    [
                        'label'=>'编号',
                        'value'=>$v['number']
                    ],
                    [
                        'label'=>'申请时间',
                        'value'=>date('Y-m-d H:i:s',$v['add_time'])
                    ],
                ];
                $v['title']='装修申请单';
                $v['flow_arr']=$flow_arr;
                $v['jump_url']=$jump_url;
                $v['qr_code']=$qr_code;
                unset($v['number'],$v['add_time'],$v['template_id'],$v['value_id'],$v['village_id']);
            }
            unset($v);
            $count = $this->HousePublicRentalApplyRecord->getRecordCount($where,$whereor);
        }
        $data['list']=$list;
        $data['count']=$count;
        $data['total_limit'] = $param['limit'];
        return $data;
    }

    //todo 获取单个人员信息
    public function getExamineApplicant($param){
        $HousePublicRentalRentingService=new HousePublicRentalRentingService();
        $where[]=['r.id', '=', $param['record_id']];
        $where[]=['r.village_id', 'in', $param['village_id']];
        $field='r.id,r.uid,r.village_id,r.number,r.value_id,r.flow_status,r.single_id,r.floor_id,r.layer_id,r.vacancy_id,r.add_time,u.nickname,u.phone';
        if(isset($param['source_type']) && $param['source_type'] == 2){ //todo 办理退租
            $info=$HousePublicRentalRentingService->getRecordBindUserMethod($where,$field);
        }else{ //todo 办理入住
            $info=$this->getRecordBindUserMethod($where,$field);
        }
        $url=get_base_url('pages/village/materialDiy/materialDetails?diy_id='.$info['value_id'].'&from_id='.$info['village_id'].'&record_uid='.$info['uid']);
        $data[]=['label'=>'编号', 'value'=>$info['number'], 'type'=>0, 'url'=>''];
        $data[]=['label'=>'申请人', 'value'=>$info['nickname'], 'type'=>0, 'url'=>''];
        $data[]=['label'=>'联系方式', 'value'=>$info['phone'], 'type'=>0, 'url'=>''];
        $data[]=['label'=>'申请时间', 'value'=>date('Y-m-d H:i:s',$info['add_time']), 'type'=>0, 'url'=>''];
        return ['data'=>$data,'url'=>$url];
    }

    /**
     * 移动管理端 审核流程
     * @author: liukezhu
     * @date : 2022/8/25
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function getWorkerExamineFlowData($param){
        $appoint=$this->getExamineApplicant($param);
        $flow_info=[
            'village_id'=>$param['village_id'],
            'id'=>$param['record_id']
        ];
        $source_type=1;
        if(isset($param['source_type'])){ //todo 办理退租
            $flow_info['source_type']=$param['source_type'];
            $source_type=$param['source_type'];
        }
        if(isset($param['is_manage'])){
            $flow_info['is_manage']=$param['is_manage'];
        }
        $info=$this->getApplyRecordInfo($flow_info);
        $label=(isset($param['source_type']) && $param['source_type'] == 2) ? '退租申请' : '入住申请';
        $appoint_info=[
            ['label'=>$label,'value'=>$appoint['url']]
        ];
        return ['user'=>$appoint['data'],'info'=>$info,'appoint_info'=>$appoint_info,'source_type'=>$source_type];
    }

    //todo 校验是否满足排号规则
    public function checkUserArranging($village_id,$info,$arranging=[]){
        if(!isset($info['id'])){
            return ['status'=>false,'msg'=>'您暂未参与入住申请，请稍后再试！'];
        }
        if($info['arranging_record_id']){
            $record=$this->HousePublicRentalArrangingRecord->getOne([
                ['type', '=', $info['source_type']],
                ['village_id','=',$village_id],
                ['id','=',$info['arranging_record_id']],
                ['status', 'in', [0,1]],
                ['del_time', '=', 0]
            ],'id,status');
            if ($record && !$record->isEmpty()){
                if((int)$record['status'] == 0){
                    return ['status'=>false,'msg'=>'您已提交预约，请等待管理员审核！'];
                }elseif ((int)$record['status'] == 1){
                    return ['status'=>false,'msg'=>'您已预约审核通过，请勿重复！'];
                }
            }
        }
        if((int)$arranging['current_num'] >= (int)$arranging['total_num']){
            return ['status'=>false,'msg'=>'当前预约数已满，请稍后再试！'];
        }
        if(isset($arranging['appoint_date'])){
            if($arranging['appoint_date'] < $arranging['queuing_start_time'] || $arranging['appoint_date'] > $arranging['queuing_end_time']){
                return ['status'=>false,'msg'=>'所选预约日期，超出范围！'];
            }
        }
        if($info['source_type'] == 1 && !in_array($info['flow_status'],[21,31,33,35])){ //针对办理入住
            return ['status'=>false,'msg'=>'当前状态无须排号，请稍后再试！'];
        }
        if($info['source_type'] == 2 && !in_array($info['flow_status'],[12,21,24])){ //针对办理退租
            return ['status'=>false,'msg'=>'当前状态无须排号，请稍后再试！'];
        }
        return ['status'=>true,'msg'=>'ok'];
    }

    /**
     * 获取预约排号设置
     * @author: liukezhu
     * @date : 2022/8/25
     * @param $param
     * @param int $source
     * @return array
     * @throws \think\Exception
     */
    public function getArrangingSet($param,$source=0){
        $field='id,title,user_name,user_phone,long,lat,adress,max_queue_number,content,queuing_start_time,queuing_end_time,work_start_time,work_end_time,examine_type';
        //查询排号规则
        $info=$this->getQueuingRuleFindMethod([
            ['type','=',$param['source_type']],
            ['village_id','=',$param['village_id']],
            ['del_time','=',0]
        ],$field);
        //查询已经预约成功的数量
        //$arranging_num=$this->getDayArrangingCountMethod($param['source_type'],$param['village_id'],$info['id'],$info['queuing_start_time'],$info['queuing_end_time']);
        $queuing_start_time=strtotime(date('Y-m-d').' 00:00:00')+1;
        $queuing_end_time=strtotime(date('Y-m-d').' 23:59:59');
        $arranging_num=$this->getDayArrangingCountMethod($param['source_type'],$param['village_id'],$info['id'],$queuing_start_time,$queuing_end_time);
        $where=[
            ['village_id','=',$param['village_id']],
            ['uid', '=', $param['uid']],
            ['del_time', '=', 0]
        ];
        if($param['pigcms_id']){
            //$where[]=['pigcms_id', '=', $param['pigcms_id']];
        }
        $field='id,flow_status,arranging_record_id,village_id,single_id,floor_id,layer_id,vacancy_id';
        //查询入住申请记录
        if(isset($param['source_type']) && $param['source_type'] == 2){
            $record_info=(new HousePublicRentalRentingService())->getApplyRecordFindMethod($where,$field,1);
        }else{
            $record_info=$this->getApplyRecordFindMethod($where,$field,1);
        }
        $houseadress='';
        if($record_info['vacancy_id']){
            $houseVillageInfo=$this->HouseVillageService->getHouseVillageInfo(['village_id'=>$record_info['village_id']],'village_id,village_name,village_address,property_name');
            $houseadress = $this->HouseVillageService->getSingleFloorRoom($record_info['single_id'], $record_info['floor_id'], $record_info['layer_id'], $record_info['vacancy_id'],$record_info['village_id']);
            $houseadress=$houseVillageInfo['village_name']." ".$houseadress;
        }
        $record_info['source_type']=$param['source_type'];
        //校验用户是否可预约
        $arranging_arr=[
            'total_num'=>$info['max_queue_number'],
            'current_num'=>$arranging_num
        ];
        if($source == 2){ //针对用户端提交预约时间 统一方法校验
            $arranging_arr['appoint_date'] = $param['date'];
            $arranging_arr['queuing_start_time'] = $info['queuing_start_time'];
            $arranging_arr['queuing_end_time'] = $info['queuing_end_time'];
        }
        $appoint_info=$this->checkUserArranging($param['village_id'],$record_info,$arranging_arr);
        $queuing_time=date('Y-m-d',$info['queuing_start_time']).'至'.date('Y-m-d',$info['queuing_end_time']);
        $work_time=$info['work_start_time'].' - '.$info['work_end_time'];
        $data[]=['label'=>'联系人', 'value'=>$info['user_name'], 'type'=>0];
        $data[]=['label'=>'联系方式', 'value'=>$info['user_phone'], 'type'=>0];
        $data[]=['label'=>'排号时间', 'value'=>$queuing_time, 'type'=>0];
        $data[]=['label'=>'办公时间', 'value'=>$work_time, 'type'=>0];
        $data[]=['label'=>'每日最大预约数', 'value'=>$info['max_queue_number'], 'type'=>0];
        $data[]=['label'=>'内容', 'value'=>$info['content'], 'type'=>0];
        $data[]=['label'=>'验房地址', 'value'=>$info['adress'], 'type'=>1,'adress'=>['long'=>$info['long'],'lat'=>$info['lat']]];
        $data[]=['label'=>'所在房间', 'value'=>$houseadress, 'type'=>0];
        $result=[
            'title'=>$info['title'],
            'village_id'=>$param['village_id'],
            'pigcms_id'=>$param['pigcms_id'],
            'arranging_info'=>$data,
            'appoint_info'=>$appoint_info
        ];
        if($source == 1){//针对预约页面方法共用
            $result['max_queue_number']=$info['max_queue_number'];
            $result['current_queue_number']=$arranging_num;
            $result['queuing_start_time']=date('Y-m-d H:i:s',$info['queuing_start_time']);
            $result['queuing_end_time']=date('Y-m-d H:i:s',$info['queuing_end_time']);
            unset($result['title'],$result['arranging_info']);
        }
        elseif ($source == 2){//针对用户提交排号
            $result['max_queue_number']=$info['max_queue_number'];
            $result['current_queue_number']=$arranging_num;
            $result['examine_type']=$info['examine_type'];
            $result['record_info']=$record_info;
            $result['queuing_rule_id']=$info['id'];
            unset($result['title'],$result['arranging_info']);
        }
        return $result;
    }

    /**
     * 用户点击预约排号
     * @author: liukezhu
     * @date : 2022/9/7
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function addArranging($param){
        $info=$this->getArrangingSet($param,2);
        if(!$info['appoint_info']['status']){
            return ['code'=>2001,'msg'=>$info['appoint_info']['msg']];
        }
        $record_info=$info['record_info'];
        $apply_time=0;
        $status=0;
        if((int)$info['examine_type'] == 1){ //开启自动审核通过
            if(isset($param['source_type']) && $param['source_type'] == 2){
                $flow_status=22;
            }else{
                $flow_status=32;
            }
            $msg='预约成功';
            $apply_time=$this->time;
            $status=1;
        }else{
            if(isset($param['source_type']) && $param['source_type'] == 2){
                $flow_status=20;
            }else{
                $flow_status=30;
            }
            $msg='提交成功等待管理员审核';
        }
        $record_arr=[
            'village_id'=>$param['village_id'],
            'uid'=>$param['uid'],
            'pigcms_id'=>$param['pigcms_id'],
            'flow_status'=>$flow_status,
            'record_id'=>$record_info['id'],
            'remarks'=>$param['remarks'],
            'appoint_time'=>$param['date'],
            'source_type'=>$param['source_type']
        ];
        $result=$this->userAddAppointmentRecordMethod($record_arr);
        $data=[
            'flow_status'=>$flow_status,
            'update_time'=>$this->time
        ];
        $arranging_record=[
            'type'=>$param['source_type'],
            'village_id'=>$param['village_id'],
            'queuing_rule_id'=>$info['queuing_rule_id'],
            'record_id'=>$record_info['id'],
            'status'=>$status,
            'appoint_time'=>$param['date'],
            'apply_time'=>$apply_time,
            'add_time'=>$this->time,
            'remark'=>$param['remarks'],
            'del_time'=>0
        ];
        //写入排号记录
        $arranging_record_id=$this->HousePublicRentalArrangingRecord->addOne($arranging_record);
        if($result){
            //更改流程状态
            $data['arranging_record_id']=$arranging_record_id;
            $obj=$this->HousePublicRentalApplyRecord;
            if(isset($param['source_type']) && $param['source_type'] == 2){ //针对办理退租
                $obj=$this->HousePublicRentalRentingRecord;
            }
            $obj->saveOne([
                ['id', '=', $record_info['id']],
                ['del_time', '=', 0]
            ],$data);
            //触发发送 通知
            if(isset($param['source_type']) && $param['source_type'] == 2){//针对办理退租
                $this->sendUserNoticeMethod($record_info['id'],'renting_arranging_examine');
            }else{
                $this->sendUserNoticeMethod($record_info['id'],'arranging_examine');
            }
        }
        return ['code'=>2002,'msg'=>$msg];
    }

    /**
     * 公租房预约记录
     * @author: liukezhu
     * @date : 2022/9/5
     * @param $param
     * @return mixed
     */
    public function getMyAppointRecordList($param){
        $where[]=['r.village_id','=',$param['village_id']];
        $where[]=['r.del_time', '=', 0];
        $where[]=['r.uid', '=', $param['uid']];
        //$where[]=['r.pigcms_id', '=', $param['pigcms_id']];
        $where[]=['r.flow_status','>=',30];
        $field='r.id as record_id,r.number,r.flow_status,a.appoint_time';
        $order='r.id desc';
        $count=0;
        $list = $this->HousePublicRentalApplyRecord->getRecordArrangingList($where,$field,$order,$param['page'],$param['limit']);
        if ($list && !$list->isEmpty()){
            $list=$list->toArray();
            foreach ($list as &$v){
                $flow_info=$this->getRecordStatusMethod($v['flow_status'],$param['cfrom']);
                $v['title']='公租房'.$flow_info['type'];
                $v['appoint_time']=date('Y-m-d H:i:s',$v['appoint_time']);
                $v['flow_info']=$flow_info;
                unset($v['flow_status']);
            }
            unset($v);
            $count = $this->HousePublicRentalApplyRecord->getRecordArrangingCount($where);
        }
        $data['list']=$list;
        $data['count']=$count;
        $data['total_limit'] = $param['limit'];
        return $data;
    }

    /**
     *取消预约+领取钥匙+验房状态
     * @author: liukezhu
     * @date : 2022/9/7
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function operationMyAppointRecord($param){
        $where[]=['id', '=', $param['record_id']];
        $where[]=['village_id','=',$param['village_id']];
        $where[]=['uid', '=', $param['uid']];
        $where[]=['del_time', '=', 0];
        $field='id,flow_status';
        $info=$this->getApplyRecordFindMethod($where,$field);
        $flow_status=$remarks=$succ_msg=$error_msg='';
        if(in_array($param['type'],['cancel_appoint','receive_key'])){ //取消预约+领取钥匙
            $flow_info=$this->getRecordStatusMethod($info['flow_status']);
            if($param['type'] == 'cancel_appoint'){
                $flow_status=35;
                $remarks='用户取消预约，进入凭证处理类型，需要重新预约排号';
                $succ_msg='取消成功';
                $error_msg='取消预约';
            }else if($param['type'] == 'receive_key'){
                $flow_status=42;
                $remarks='验房合格，用户领取钥匙';
                $succ_msg='领取成功';
                $error_msg='领取钥匙';
            }
            if(!$flow_info['button'] || !in_array($param['type'],$flow_info['button'])){
                throw new \think\Exception('该预约无须'.$error_msg.'，请勿重新操作');
            }
        }elseif ($param['type'] == 'is_inspection'){ //验房状态
            $flow_info=$this->getApplyRecordFlowTypeMethod($info['flow_status'],1);
            if(!$flow_info['is_examine']){
                throw new \think\Exception('该预约['.$flow_info['value'].']无须审核，请勿重新操作');
            }
            $key = array_column($flow_info['examine_data'],'key');
            if(empty($key) || !in_array($param['examine_status'],$key)){
                throw new \think\Exception('请选择'.$flow_info['value'].'状态');
            }
            $flow_status=$param['examine_status'];
            $remarks=$param['remarks'];
            $succ_msg='操作成功';
        }
        $record_arr=[
            'village_id'=>$param['village_id'],
            'uid'=>$param['uid'],
            'pigcms_id'=>$param['pigcms_id'],
            'flow_status'=>$flow_status,
            'record_id'=>$info['id'],
            'remarks'=>$remarks,
        ];
        //针对验房提交上传图片
        if(isset($param['imags'])){
            $record_arr['imags']=$param['imags'];
        }
        //针对取消预约，状态扭转为凭证审核通过
        if($flow_status == 35){
            $flow_status=21;
        }
        $result=$this->userAddAppointmentRecordMethod($record_arr);
        $data=[
            'flow_status'=>$flow_status,
            'update_time'=>$this->time
        ];
        if($flow_status == 21){
            $data['arranging_record_id'] =0;
        }
        if($result){
            //更改流程状态
            $this->HousePublicRentalApplyRecord->saveOne([
                ['id', '=', $info['id']],
                ['del_time', '=', 0]
            ],$data);
        }
        return ['code'=>2002,'msg'=>$succ_msg];
    }

    /**
     *获取详情
     * @author: liukezhu
     * @date : 2022/9/7
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function getMyAppointRecordDetails($param){
        $HousePublicRentalRentingService=new HousePublicRentalRentingService();
        $where[]=['r.id', '=', $param['record_id']];
        $where[]=['r.del_time', '=', 0];
        if(is_array($param['village_id'])){
            $where[]=['r.village_id','in',$param['village_id']];
        }
        else{
            $where[]=['r.village_id','=',$param['village_id']];
        }
        if(isset($param['uid']) && $param['uid']){
            $where[]=['r.uid', '=', $param['uid']];
        }
        $field='r.id,r.flow_status,r.village_id,r.single_id,r.floor_id,r.layer_id,r.vacancy_id,a.appoint_time,r.pigcms_id,a.remark';
        if(isset($param['source_type']) && $param['source_type'] == 2){
            //查询退租申请记录
            $record_info=$this->HousePublicRentalRentingRecord->getRecordArrangingFind($where,$field);
        }else{
            //查询入住申请记录
            $record_info=$this->HousePublicRentalApplyRecord->getRecordArrangingFind($where,$field);
        }
        if (!$record_info || $record_info->isEmpty()) {
            throw new \think\Exception('该记录不存在，请稍后再试！');
        }
        $record_info=$record_info->toArray();
        if(isset($param['source_type']) && $param['source_type'] == 2){ //todo 办理退租
            $flow_info=$HousePublicRentalRentingService->getRecordStatusMethod($record_info['flow_status']);
            $flow_status=$HousePublicRentalRentingService->flow_status;
        }
        else{ //todo 办理入住
            $flow_info=$this->getRecordStatusMethod($record_info['flow_status']);
            $flow_status=$this->flow_status;
        }
        $houseadress='';
        if($record_info['vacancy_id']){
            $houseVillageInfo=$this->HouseVillageService->getHouseVillageInfo(['village_id'=>$record_info['village_id']],'village_id,village_name,village_address,property_name');
            $houseadress = $this->HouseVillageService->getSingleFloorRoom($record_info['single_id'], $record_info['floor_id'], $record_info['layer_id'], $record_info['vacancy_id'],$record_info['village_id']);
            $houseadress=$houseVillageInfo['village_name']." ".$houseadress;
        }
        $field='id,title,user_name,user_phone,long,lat,adress,max_queue_number,content,queuing_start_time,queuing_end_time,work_start_time,work_end_time,examine_type';
        //查询排号规则
        $source_type=1;
        if(isset($param['source_type'])){
            $source_type=$param['source_type'];
        }
        $arranging_info=$this->getQueuingRuleFindMethod([
            ['type','=',$source_type],
            ['village_id','=',$record_info['village_id']],
            ['del_time','=',0]
        ],$field);
        //查询用户信息
        if(isset($param['pigcms_id'])){
            $pigcms_id=$param['pigcms_id'];
        }
        else{
            $pigcms_id=$record_info['pigcms_id'];
        }
        $uu=$this->getUserBind($pigcms_id,'name,phone');
        //最新log
        $whereArr=[
            ['rental_id','=',$record_info['id']],
        ];
        if($flow_info['type']=='验房' && in_array($record_info['flow_status'],array(31,42))){
            $whereArr[]=array('log_type','=','inspection_success');
        }
        $record_log=$this->getApplyRecordLogFindMethod($whereArr,'log_content,log_imgs');
        $flow_record=[];
        //查询编辑数据 需要编辑的 不展示已经编辑的数据
        $cfrom=isset($param['cfrom']) ? $param['cfrom']:'';
        if(isset($param['uid']) || isset($param['is_edit'])){
            if(isset($param['source_type']) && $param['source_type'] == 2){ //todo 办理退租
                $flow_record=$HousePublicRentalRentingService->getRentingFlowTypeMethod($record_info['flow_status'],1,$cfrom);
            }else{ //todo 办理入住
                $flow_record=$this->getApplyRecordFlowTypeMethod($record_info['flow_status'],1);
            }
            $flow_record['examine_value']=isset($flow_record['examine_data'][0]['key']) ? $flow_record['examine_data'][0]['key'] : 0;
        }
        $data[]=['label'=>'标题', 'value'=>'公租房'.$flow_info['type'], 'type'=>0];
        $data[]=['label'=>'联系人', 'value'=>$arranging_info['user_name'], 'type'=>0];
        $data[]=['label'=>'联系方式', 'value'=>$arranging_info['user_phone'], 'type'=>0];
        $data[]=['label'=>'内容', 'value'=>$arranging_info['content'], 'type'=>0];
        $data[]=['label'=>'验房地址', 'value'=>$arranging_info['adress'], 'type'=>1,'adress'=>['long'=>$arranging_info['long'],'lat'=>$arranging_info['lat']]];
        $data[]=['label'=>'所在房间', 'value'=>$houseadress, 'type'=>0];
        $data[]=['label'=>'预约人', 'value'=>$uu['name'], 'type'=>0];
        if($record_info['appoint_time']){
            $data[]=['label'=>'预约时间', 'value'=>date('Y-m-d H:i:s',$record_info['appoint_time']), 'type'=>0];
        }
        $data[]=['label'=>'备注', 'value'=>$record_info['remark'], 'type'=>0];
        if(empty($flow_record) || !$flow_record['is_examine'] || in_array($record_info['flow_status'],array(41)) ||($cfrom=='manage' && in_array($record_info['flow_status'],array(30)))){
            $data[]=['label'=>$flow_info['type'].'状态', 'value'=>$flow_status[$record_info['flow_status']], 'type'=>0];
            $newimags=[];
            if ($record_log && $record_log['log_imgs']) {
                $imags = unserialize($record_log['log_imgs']);
                if($imags){
                    foreach ($imags as &$vv){
                        $vv=replace_file_domain($vv);
                        $newimags[]=$vv;
                    }
                }
            }
            $data[]=['label'=>$flow_info['type'].'情况', 'value'=>$record_log['log_content'], 'type'=>2,'imags'=>$newimags];
        }
        $result=[
            'arranging_info'=>$data
        ];
        if($flow_record){
            $result['flow_info']=$flow_record;
        }
        return $result;
    }

    //todo 工作人员端 获取审核列表
    public function getWorkerApplyRecordList($param){
        $where[]=['r.village_id','in',$param['village_id']];
        $where[]=['r.del_time', '=', 0];
        switch ((int)$param['type']) { //筛选凭证
            case 1: //待审核
                $where[]=['r.flow_status', '=', 12];
                break;
            case 2: //审核通过
                $where[]=['r.flow_status', '=', 21];
                break;
            case 3: //审核不通过
                $where[]=['r.flow_status', '=', 20];
                break;
            default :
                throw new \think\Exception('参数不合法，请稍后再试！');
                break;
        }
        if($param['keyword']){
            $where[]  = ['r.number|b.name','like',"%".$param['keyword']."%"];
        }
        $return_flow_status=[
            12=>1,
            21=>2,
            20=>3
        ];
        $field='r.id as record_id,r.number,r.flow_status,b.name,a.appoint_time';
        $order='r.id desc';
        $count=0;
        $list = $this->HousePublicRentalApplyRecord->getRecordBindUserList($where,$field,$order,$param['page'],$param['limit']);
        if ($list && !$list->isEmpty()){
            foreach ($list as &$v){
                $v['flow_status']=isset($return_flow_status[$v['flow_status']]) ? $return_flow_status[$v['flow_status']] : 0;
                $v['appoint_time']=$v['appoint_time'] ? date('Y-m-d H:i:s',$v['appoint_time']) : '--';
            }
            unset($v);
            $count = $this->HousePublicRentalApplyRecord->getRecordBindUserCount($where);
        }
        $data['list']=$list;
        $data['count']=$count;
        $data['total_limit'] = $param['limit'];
        return $data;
    }

    //todo 工作人员端 获取验房 办理入住列表
    public function getWorkerInspectionRecordList($param){
        $where[]=['r.village_id','in',$param['village_id']];
        $where[]=['r.del_time', '=', 0];
        $where[]=['r.flow_status','in',[32,34,40,41,42]];
        $return_flow_status=[
            32=>'待验房',
            34=>'待验房',
            40=>'合格',
            41=>'不合格',
            42=>'通过'
        ];
        $field='r.id as record_id,r.number,r.flow_status,b.name,a.appoint_time';
        $order='r.id desc';
        $count=0;
        $list = $this->HousePublicRentalApplyRecord->getRecordBindUserList($where,$field,$order,$param['page'],$param['limit']);
        if ($list && !$list->isEmpty()){
            foreach ($list as &$v){
                $v['flow_status']=isset($return_flow_status[$v['flow_status']]) ? $return_flow_status[$v['flow_status']] : 0;
                $v['appoint_time']=date('Y-m-d H:i:s',$v['appoint_time']);
                $v['btnTxt']='查看';
            }
            unset($v);
            $count = $this->HousePublicRentalApplyRecord->getRecordBindUserCount($where);
        }
        $data['list']=$list;
        $data['count']=$count;
        $data['total_limit'] = $param['limit'];
        return $data;
    }


    //====================================todo 移动管理端账号登录 区分village_id start=======================

    //todo 区分当前账号管理小区 返回小区id
    public function getGrantVillageIds($info){
        $village_id=[];
        switch ((int)$info['login_role']) {
            case 6: //小区工作人员
                $village_id[]= $info['user']['village_id'];
                break;
            case 5: //小区管理人员
                $village_id[]= $info['user']['village_id'];
                break;
            case 4: //物业管理员
                if(isset($info['user']['village_ids']) && $info['user']['village_ids']){
                    $village_id=explode(',',$info['user']['village_ids']);
                }
                break;
            case 3: //物业总管理员
                $village_id=$this->HouseVillage->getColumn([
                    ['property_id', '=', $info['user']['id']],
                    ['status', '=', 1]
                ],'village_id');
                break;
        }
        return $village_id;
    }


    //====================================todo 后端方法 start======================================

    /**
     * 入住申请列表
     * @author: liukezhu
     * @date : 2022/8/5
     * @param $param
     * @return mixed
     */
    public function getApplyList($param){
        $where[]=['r.village_id','=',$param['village_id']];
        $where[]=['r.del_time', '=', 0];
        if($param['value'] && in_array($param['key'],['r.number','u.nickname','u.phone'])){
            $where[] = [$param['key'], 'like', '%'.$param['value'].'%'];
        }
        if(!empty($param['flow_status']) &&  in_array($param['flow_status'],[10,11,12])){
            $where[] = ['r.flow_status', '=', $param['flow_status']];
        }else{
            $where[] = ['r.flow_status','in',[10,11,12]];
        }
        if(!empty($param['date']) && is_array($param['date'])){
            $start_time = strtotime($param['date'][0].' 00:00:00');
            $end_time = strtotime($param['date'][1].' 23:59:59');
            $where[] = ['r.add_time','between',[$start_time,$end_time]];
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$param['village_id']),'*');
        $field='r.id,r.number,r.template_id,r.value_id,r.add_time,r.flow_status,t.template_title,u.nickname,u.phone';
        $order='r.id desc';
        $data=$this->getApplyRecordListMethod($where,$field,$order,$param['page'],$param['limit']);
        $site_url=cfg('site_url');
        if($data['list']){
            foreach ($data['list'] as &$v){
                if(!empty($v['phone'])){
                    $v['phone']= $houseVillageDb->phone_desensitization_v($v['phone'],$param['village_id'],$village_info);
                }
                $v['phone']=empty($v['phone']) ? '--' : $v['phone'];
                $v['see_url']=$site_url.'/packapp/material_diy/index.html#/template_look?diy_id='.$v['value_id'].'&paths=write_list&source=publicRental';
                $v['edit_url']=$site_url.'/packapp/material_diy/index.html#/template_write?diy_id='.$v['value_id'].'&paths=write_list&source=publicRental';
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                $v['button']=$this->checkApplyPowerButtonMethod($param['menus'],$v);
            }
            unset($v);
        }
        $data['search_hotel_status'] = $this->search_hotel_status;
        $data['total_limit'] = $param['limit'];
        return $data;
    }

    /**
     * 删除入住申请
     * @author: liukezhu
     * @date : 2022/8/5
     * @param $village_id
     * @param $id
     * @return bool
     * @throws \think\Exception
     */
    public function applyDel($village_id,$id){
        $info=$this->HousePublicRentalApplyRecord->getOne([
            ['id', '=', $id],
            ['village_id', '=', $village_id],
            ['del_time', '=', 0]
        ],'*');
        if (!$info || $info->isEmpty()) {
            throw new \think\Exception('该数据不存在，请重新操作');
        }
        //删除申请记录
        $this->HousePublicRentalApplyRecord->saveOne([['id', '=', $id]],['update_time'=>$this->time,'del_time'=>$this->time]);
        //删除提交模板记录
        $this->PluginMaterialDiyValue->saveOne([
            ['id', '=', $info['value_id']],
            ['template_id', '=', $info['template_id']],
            ['diy_tatus','<>',4]
        ],['diy_tatus'=>4,'last_time'=>$this->time]);
        if($info['contract_template_id']){
            //删除提交合同模板记录
            $this->PluginMaterialDiyValue->saveOne([
                ['id', '=', $info['contract_value_id']],
                ['template_id', '=', $info['contract_template_id']],
                ['diy_tatus','<>',4]
            ],['diy_tatus'=>4,'last_time'=>$this->time]);
        }
        //删除附件记录
        $this->PluginMaterialDiyFile->updatePluginMaterialDiyFile([
            ['from_id', '=', $village_id],
            ['from', '=', 'house'],
            ['file_status','<>',4],
            ['diy_id', '=', $info['value_id']],
        ],['file_status' => 4,'last_time' =>$this->time]);

        //删除申请记录
        if($info['vacancy_id']>0){
            $whereArr=array('vacancy_id'=>$info['vacancy_id']);
            $whereArr['layer_id']=$info['layer_id'];
            $whereArr['floor_id']=$info['floor_id'];
            $whereArr['single_id']=$info['single_id'];
            $houseVillageUserBind= new HouseVillageUserBind();
            $userBind=$houseVillageUserBind->getOne($whereArr);
            $saveData=array('status'=>4);
            $houseVillageUserBind->saveOne($whereArr,$saveData);
            $saveVData=array();
            $saveVData['uid'] = 0;
            $saveVData['status'] = 1;
            $saveVData['name'] = "";
            $saveVData['phone'] = "";
            $saveVData['type'] = 0;
            $saveVData['park_flag'] = 0;
            $houseVillageUserVacancy =new HouseVillageUserVacancy();
            $houseVillageUserVacancy->saveOne(['pigcms_id'=>$info['vacancy_id']],$saveVData);
            
            $whereArr=array('vacancy_id'=>$info['vacancy_id']);
            $whereArr['uid']=$info['uid'];
            $whereArr['village_id']=$info['village_id'];
            $whereArr['pigcms_id']=$info['pigcms_id'];
            $whereArr['layer_id']=$info['layer_id'];
            $whereArr['floor_id']=$info['floor_id'];
            $whereArr['single_id']=$info['single_id'];
            $whereArr['del_time']=0;
            $rentingRecord=$this->HousePublicRentalRentingRecord->getOne($whereArr);
            if($rentingRecord && !$rentingRecord->isEmpty()){
                $housePublicRentalRentingService=new HousePublicRentalRentingService();
                $housePublicRentalRentingService->rentingDel($info['village_id'],$rentingRecord['id']);
            }
        }
        return true;
    }

    /**
     * 获取附件列表
     * @author: liukezhu
     * @date : 2022/8/5
     * @param $param
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getEnclosureList($param){
        $where[]=['from_id','=',$param['village_id']];
        $where[]=['from', '=', 'house'];
        $where[]=['file_status','<>',4];
        $where[]=['diy_id', '=', $param['value_id']];
        $field='file_id,file_remark,file_type,account,add_time,file_url';
        $order='file_id desc';
        $data=$this->getEnclosureMethod($where,$field,$order,$param['page'],$param['limit']);
        $data['total_limit'] = $param['limit'];
        return $data;
    }

    /**
     * 删除附件
     * @author: liukezhu
     * @date : 2022/8/4
     * @param $village_id
     * @param $id
     * @return bool
     */
    public function enclosureDel($village_id,$id){
        $where[]=['from_id', '=', $village_id];
        $where[]=['from', '=', 'house'];
        $where[]=['file_id', '=', $id];
        $where[]=['file_status','<>',4];
        $data=[
            'file_status' => 4,
            'last_time' =>$this->time
        ];
        return $this->PluginMaterialDiyFile->updatePluginMaterialDiyFile($where,$data);
    }

    /**
     * 写入附件
     * @author: liukezhu
     * @date : 2022/8/4
     * @param $param
     * @return int|string
     * @throws \think\Exception
     */
    public function enclosureAddFile($param){
        $where[]=['from_id', '=', $param['village_id']];
        $where[]=['from', '=', 'house'];
        $where[]=['template_id', '=', $param['template_id']];
        $templateInfo=$this->PluginMaterialDiyTemplate->getOne($where,'template_id');
        if (!$templateInfo || $templateInfo->isEmpty()) {
            throw new \think\Exception('该数据不存在，请重新操作');
        }
        $file=$param['file'];
        $pathinfo=pathinfo($file['url']);
        $imageSizeArr = @getimagesize(rtrim($_SERVER['DOCUMENT_ROOT'],'/').$file['url']);
        $file_extra = serialize(array(
            'img_width'=>isset($imageSizeArr[0]) ? intval($imageSizeArr[0]) : 0,
            'img_height'=>isset($imageSizeArr[1]) ? intval($imageSizeArr[1]) : 0,
            'size'=>$file['file']['size']
        ));
        $villageInfo=$this->HouseVillage->getOne($param['village_id'],'village_name');
        if (!$villageInfo || $villageInfo->isEmpty()) {
            throw new \think\Exception('该小区不存在，请重新操作');
        }
        $data=[
            'diy_id' => $param['value_id'],
            'ip' => get_client_ip(),
            'from'=>'house',
            'from_id'=>$param['village_id'],
            'add_time' => $this->time,
            'file_url' => $file['url'],
            'file_remark'=>$file['file']['name'],
            'file_suffix' => $pathinfo['extension'],
            'file_name' => $pathinfo['basename'],
            'file_type' => $file['file']['type'],
            'file_extra' => $file_extra,
            'from_type'=>'小区',
            'from_name'=>$villageInfo['village_name']
        ];
        $result=$this->PluginMaterialDiyFile->addPluginMaterialDiyFile($data);
        return $result;
    }

    /**
     * 办理入住
     * @author: liukezhu
     * @date : 2022/8/8
     * @param $param
     * @return array
     */
    public function getHandleApplyList($param){
        $prefix = config('database.connections.mysql.prefix');
        $where[]=['r.village_id','=',$param['village_id']];
        $where[]=['r.del_time', '=', 0];
        if($param['value'] && in_array($param['key'],['r.number','u.nickname','u.phone'])){
            $where[] = [$param['key'], 'like', '%'.$param['value'].'%'];
        }
        if(!empty($param['date']) && is_array($param['date'])){
            $start_time = strtotime($param['date'][0].' 00:00:00');
            $end_time = strtotime($param['date'][1].' 23:59:59');
            $where[] = ['r.add_time','between',[$start_time,$end_time]];
        }
        if(!empty($param['handle_type']) && empty($param['handle_status'])){
            switch ($param['handle_type']) {
                case "voucher_status": //凭证
                    $where[] = ['r.flow_status','in',[12,20,21]];
                    break;
                case "arranging_status": //排号
                    $where[] = ['r.flow_status','in',[30,31,33]];
                    break;
                case "inspection_status": //验房
                    $where[] = ['r.flow_status','in',[32,34,40,41,42]]; //包含排号审核自动/手动审核通过
                    break;
            }
        }
        if(!empty($param['handle_status'])){
            $where[]=['r.flow_status', '=', $param['handle_status']];
        }
        $where[] = ['r.flow_status','>=',12];
        $field='r.id,r.number,r.template_id,r.value_id,r.add_time,r.flow_status,t.template_title,u.nickname,u.phone,( SELECT log_type FROM '.$prefix.'house_public_rental_apply_record_log AS g WHERE g.rental_id = r.id and g.type = 1 ORDER BY g.id DESC LIMIT 0, 1 ) AS handle_status ';
        $order='r.id desc';
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$param['village_id']),'*');
        $data=$this->getApplyRecordListMethod($where,$field,$order,$param['page'],$param['limit']);
        if($data['list'] && !$data['list']->isEmpty()){
            foreach ($data['list'] as &$v){
                $flow_info=$this->getApplyRecordFlowTypeMethod($v['flow_status'],1);
                if(!empty($v['phone']) ){
                    $v['phone']=$houseVillageDb->phone_desensitization_v($v['phone'],$param['village_id'],$village_info);
                }
                $v['phone']=empty($v['phone']) ? '--' : $v['phone'];
                $v['handle_status']= isset($this->log_name[$v['handle_status']]) ? $this->log_name[$v['handle_status']] : $v['handle_status'] ;
                $v['handle_type']=$flow_info['value'];
                $v['source_type']=1;
                $v['button']=$this->checkHandlePowerButtonMethod($param['menus'],$flow_info,$v);
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            }
            unset($v);
        }
        $data['total_limit'] = $param['limit'];
        $data['status_list']=$this->search_hotel_type_status;
        return $data;
    }

    /**
     * 入住用户详情
     * @author: liukezhu
     * @date : 2022/8/10
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function getApplyRecordUserInfo($param){
        $where[]=['r.village_id','=',$param['village_id']];
        $where[]=['r.id', '=', $param['id']];
        $field='r.id,r.number,r.value_id,r.single_id,r.floor_id,r.layer_id,r.vacancy_id,r.add_time,u.nickname,u.phone';
        if(isset($param['source_type']) && $param['source_type'] == 2){ //todo 办理退租
            $info=(new HousePublicRentalRentingService())->getRecordBindUserMethod($where,$field);
        }else{ //todo 办理入住
            $info=$this->getRecordBindUserMethod($where,$field);
        }
        $address='--';
        if($info['vacancy_id']){
            $address = $this->HouseVillageService->getSingleFloorRoom($info['single_id'], $info['floor_id'], $info['layer_id'], $info['vacancy_id'],$param['village_id']);
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$param['village_id']),'*');
        $roomName = (new HouseVillageService())->getHouseAlias('room_name',$param['village_id'])?:'房间';
        $url=cfg('site_url').'/packapp/material_diy/index.html#/template_look?diy_id='.$info['value_id'].'&paths=write_list&source=publicRental';
        $user[]=['label'=>'编号', 'value'=>$info['number'], 'type'=>0, 'url'=>''];
        $user[]=['label'=>'姓名', 'value'=>$info['nickname'], 'type'=>0, 'url'=>''];
        $phone_tmp=$houseVillageDb->phone_desensitization_v($info['phone'],$param['village_id'],$village_info);
        $user[]=['label'=>'手机号', 'value'=>$phone_tmp, 'type'=>0, 'url'=>''];
        $user[]=['label'=>'申请时间', 'value'=>date('Y-m-d H:i:s',$info['add_time']), 'type'=>0, 'url'=>''];
        $user[]=['label'=>$roomName, 'value'=>$address, 'type'=>0, 'url'=>''];
        $user[]=['label'=>'模板内容', 'value'=>'查看模板内容', 'type'=>1, 'url'=>$url];
        $data=[
            'user'=>$user
        ];
        return $data;
    }

    /**
     * 入住操作记录
     * @author: liukezhu
     * @date : 2022/8/9
     * @param $param
     * @return array
     */
    public function getApplyRecordLog($param){
        $where[]=['village_id','=',$param['village_id']];
        $where[]=['rental_id', '=', $param['id']];
        $where[]=['type', '=', $param['source_type']];
        $field='id,log_type,log_operator,add_time,log_content,log_imgs,log_param';
        $order='id desc';
        $list=[];
        $result=$this->getApplyRecordLogMethod($where,$field,$order,$param['page'],$param['limit']);
        if($result['list'] && !$result['list']->isEmpty()) {
            foreach ($result['list'] as &$v){
                $list[]=$this->assembleRecordLogMethod($v,$param['source_type']);
            }
        }
        $data['list'] = $list;
        $data['count'] = $result['count'];
        $data['total_limit'] = $param['limit'];
        return $data;
    }

    /**
     * 办理入住  处理内容
     * @author: liukezhu
     * @date : 2022/8/10
     * @param $param
     * @param int $source
     * @return array
     * @throws \think\Exception
     */
    public function getApplyRecordInfo($param,$source=0){
        $HousePublicRentalRentingService=new HousePublicRentalRentingService();
        $where[]=['id', '=', $param['id']];
        $where[]=['del_time', '=', 0];
        if(is_array($param['village_id'])){
            $where[]=['village_id','in',$param['village_id']];
        }else{
            $where[]=['village_id','=',$param['village_id']];
        }
        $field='id,uid,number,village_id,value_id,flow_status,single_id,floor_id,layer_id,vacancy_id,add_time,arranging_record_id';
        if(isset($param['source_type']) && $param['source_type'] == 2){ //todo 办理退租
            $field='*';
            $info=$HousePublicRentalRentingService->getApplyRecordFindMethod($where,$field);
            $flow_info=$HousePublicRentalRentingService->getRentingFlowTypeMethod($info['flow_status'],1);
        }else{ //todo 办理入住
            $info=$this->getApplyRecordFindMethod($where,$field);
            $flow_info=$this->getApplyRecordFlowTypeMethod($info['flow_status'],1);
        }
        $data=[
            'id'=>$info['id'],
            'handle_type'=>$flow_info['type'],
            'handle_name'=>$flow_info['value'],
            'is_examine'=>$flow_info['is_examine'],
        ];
        if($data['is_examine']){ //未审核
            $data['examine_data']=$flow_info['examine_data'];
            $data['examine_value']=(int)$flow_info['examine_data'][0]['key'];
            $data['examine_info']='';
            
            if(isset($param['is_manage']) && $param['is_manage']==1 && ($info['flow_status']==33  || $info['flow_status']==20 || $info['flow_status']==41)){
                $data['is_examine']=false;
                if ($param['source_type'] == 2){
                    $data['examine_info']=$this->cancel_status[$info['flow_status']];
                }else{
                    $data['examine_info']=$this->flow_status[$info['flow_status']];
                }
            }
            if($info['flow_status']==22){
                $data['examine_info']=$this->flow_status[$info['flow_status']];
            }
        }else{ //已审核
            $data['examine_data']=[];
            $data['examine_value']=(int)$info['flow_status'];
            if ($param['source_type'] == 2){
                $data['examine_info']=$this->cancel_status[$info['flow_status']];
            }else{
                $data['examine_info']=$this->flow_status[$info['flow_status']];
            }
            
        }
        $data['flow_status']=$info['flow_status'];
        if($source){
            $data['info']=$info;
            $data['flow_info']=$flow_info;
        }
        return $data;
    }

    /**
     * 审核操作流程
     * @author: liukezhu
     * @date : 2022/8/25
     * @param $param
     * @return bool|int|string
     * @throws \think\Exception
     */
    public function operationExamineRecord($param){
        $HousePublicRentalRentingService=new HousePublicRentalRentingService();
        $info=$this->getApplyRecordInfo($param,$param['source']);
        if(!$info['is_examine']){
            throw new \think\Exception('当前['.$info['handle_name'].']流程无须操作');
        }
        if(!isset($info['flow_info']['examine_data']) || empty($info['flow_info']['examine_data'])){
            throw new \think\Exception('该数据异常，请重新操作');
        }
        $key = array_column($info['flow_info']['examine_data'],'key');
        if(empty($key) || !in_array($param['examine_status'],$key)){
            throw new \think\Exception('请选择审核状态');
        }
        $flow_log_name=$this->flow_log_name;
        if(isset($param['source_type']) && $param['source_type'] == 2){ //todo 办理退租
            $flow_log_name=$HousePublicRentalRentingService->flow_log_name;
        }
        if(!isset($flow_log_name[$param['examine_status']])){
            throw new \think\Exception('该数据审核异常，请重新操作');
        }
        $log_type=$flow_log_name[$param['examine_status']];
        if($param['source'] == 1){ //后端操作审核
            $returnArr = (new AdminLoginService())->formatUserData($param['user'], $param['role']);
        }else{ //移动管理端审核
            $returnArr['name'] = isset($param['log_operator']) ? $param['log_operator'] : '';
        }
        $log_param=[
            'reason'=>$param['remarks'],
            'log_operator'=>$returnArr['name']
        ];
        if(isset($param['log_imgs']) && !empty($param['log_imgs'])){
            $log_param['log_imgs']=$param['log_imgs'];
        }
        $data=[
            'flow_status'=>$param['examine_status'],
            'update_time'=>$this->time
        ];
        if(isset($param['source_type']) && $param['source_type'] == 2){  //todo 办理退租
            $result= $HousePublicRentalRentingService->rentingRecordMethod($info['id'],$log_type,$log_param);
            if($info['info']['flow_status'] == 20){ //审核排号
                if($param['examine_status'] == 23){
                    $status=1;
                }else{
                    $status=2;
                }
                $this->HousePublicRentalArrangingRecord->saveOne([
                    ['id', '=', $info['info']['arranging_record_id']],
                    ['del_time', '=', 0]
                ],[
                    'status'=>$status,
                    'apply_time'=>$this->time,
                    'update_time'=>$this->time
                ]);
            }
            $this->HousePublicRentalRentingRecord->saveOne([
                ['id', '=', $info['id']],
                ['del_time', '=', 0]
            ],$data);
            if($param['examine_status']==31){
                //退租 验房审核 通过
                $whereArr=array('vacancy_id'=>$info['info']['vacancy_id']);
                $whereArr['layer_id']=$info['info']['layer_id'];
                $whereArr['floor_id']=$info['info']['floor_id'];
                $whereArr['single_id']=$info['info']['single_id'];
                $userBind=$this->HouseVillageUserBind->getOne($whereArr);
                $saveData=array('status'=>4);
                $this->HouseVillageUserBind->saveOne($whereArr,$saveData);
                $saveVData=array();
                $saveVData['uid'] = 0;
                $saveVData['status'] = 1;
                $saveVData['name'] = "";
                $saveVData['phone'] = "";
                $saveVData['type'] = 0;
                $saveVData['park_flag'] = 0;
                $this->HouseVillageUserVacancy->saveOne(['pigcms_id'=>$info['info']['vacancy_id']],$saveVData);
            }
            if($info['info']['flow_status'] == 10){
                //触发发送 通知
                $this->sendUserNoticeMethod($info['id'],'renting_examine');
            }
        }
        else{  //todo 办理入住
            if(in_array($data['flow_status'],[12,20,21])){
                if($param['examine_status'] == 21 && (empty($param['single_id']) || empty($param['floor_id']) || empty($param['layer_id']) || empty($param['vacancy_id']))){
                    throw new \think\Exception('请绑定房间');
                }
                $data['single_id']=$param['single_id'];
                $data['floor_id']=$param['floor_id'];
                $data['layer_id']=$param['layer_id'];
                $data['vacancy_id']=$param['vacancy_id'];
                //校验房间唯一性
                if($param['examine_status'] == 21){
                    $this->checkUserBindVacancyMethod($data['vacancy_id'],$info['info']['uid']);
                }
            }
            $result= $this->appointmentRecordMethod($info['id'],$log_type,$log_param);
            if($info['info']['flow_status'] == 30){ //审核排号
                if($param['examine_status'] == 34){
                    $status=1;
                }else{
                    $status=2;
                    //审核失败 清空绑定房间数据
                    unset($data['single_id'],$data['floor_id'],$data['layer_id'],$data['vacancy_id']);
                }
                $this->HousePublicRentalArrangingRecord->saveOne([
                    ['id', '=', $info['info']['arranging_record_id']],
                    ['del_time', '=', 0]
                ],[
                    'status'=>$status,
                    'apply_time'=>$this->time,
                    'update_time'=>$this->time
                ]);
            }
            $this->HousePublicRentalApplyRecord->saveOne([
                ['id', '=', $info['id']],
                ['del_time', '=', 0]
            ],$data);
            if($param['examine_status'] == 21){
                $rr=[
                    'village_id'=>$info['info']['village_id'],
                    'uid'=>$info['info']['uid'],
                    'single_id'=>$data['single_id'],
                    'floor_id'=>$data['floor_id'],
                    'layer_id'=>$data['layer_id'],
                    'vacancy_id'=>$data['vacancy_id'],
                    'apply_id'=>$info['id'],
                ];
                $this->addUserBindMethod($rr);
            }
            if($info['info']['flow_status'] == 30){
                //触发发送 通知
                $this->sendUserNoticeMethod($info['id'],'arranging_examine');
            }
        }
        return $result;
    }

    /**
     * 获取合同状态
     * @author: liukezhu
     * @date : 2022/8/31
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function getContractStatus($param){
        $field='id,uid,pigcms_id,number,flow_status,add_time,contract_template_id,contract_value_id';
        $record_info=$this->getApplyRecordFindMethod([
            ['village_id','=',$param['village_id']],
            ['id', '=', $param['id']],
            ['del_time', '=', 0]
        ],$field);
        $value_tid=0;
        $template_tid=0;
        $value_info = $this->PluginMaterialDiyValue->getLeftFind([ //已经提交的模板记录
            ['a.from_id','=',$param['village_id']],
            ['a.from', '=', 'house'],
            ['a.diy_tatus','<>',4],
            ['a.uid', '=', $record_info['uid']],
            ['b.template_type', '=', 2],
        ],'a.id,b.template_id');
        if ($value_info && !$value_info->isEmpty()){
            $value_info=$value_info->toArray();
            $value_tid=(int)$value_info['template_id'];
        }
        $template_info=$this->PluginMaterialDiyTemplate->getOne([ //查询最新的模板
            ['from', '=', 'house'],
            ['from_id','=',$param['village_id']],
            ['template_type', '=', 2],
            ['template_status','=',0],
        ],'template_id,template_title');
        if ($template_info && !$template_info->isEmpty()){
            $template_info=$template_info->toArray();
            $template_tid=(int)$template_info['template_id'];
        }
        if(!$template_tid || !$value_tid){//针对数据被删除 记录合同关联id置为0
            if($record_info['contract_template_id'] || $record_info['contract_value_id']){
                $this->HousePublicRentalApplyRecord->saveOne([['id', '=', $record_info['id']]],[
                    'contract_template_id'=>0,
                    'contract_value_id'=>0,
                    'update_time'=>$this->time
                ]);
            }
        }
        $url=cfg('site_url').'/packapp/material_diy/index.html#/template_write?diy_id='.$record_info['contract_value_id'].'&paths=write_list&source=publicRental';
        //301:未生成合同（需要选择模板） 302:已生成合同,不是最新（选择模板） 303:是最新合同模板对应合同 304:未设置合同模板
        if(!$template_tid){
            return ['code'=>304,'msg'=>'未设置合同模板,请在[我的应用]=>[在线文件夹管理]中列表数据选择设置[电子合同]。','data'=>[]];
        }
        if(!$value_tid || (empty($record_info['contract_template_id']) && empty($record_info['contract_value_id']))){
            return ['code'=>301,'msg'=>'未生成合同，请选择模板','data'=>[]];
        }
        if($template_tid > $value_tid){
            return ['code'=>302,'msg'=>'存在新的模板['.$template_info['template_title'].']是否替换','data'=>[
                'msg'=>'点击"是"后请选择一个模板并且需要填充数据才生效，点击"否"跳转原合同数据。',
                'url'=>$url
            ]];
        }else{
            return ['code'=>303,'msg'=>'ok','data'=>[
                'msg'=>'',
                'url'=>$url
            ]];
        }
    }

    /**
     * 获取合同列表
     * @author: liukezhu
     * @date : 2022/8/31
     * @param $param
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getContractList($param){
        $info=$this->getApplyRecordFindMethod([
            ['village_id','=',$param['village_id']],
            ['id', '=', $param['id']],
            ['del_time', '=', 0]
        ],'id,uid,contract_template_id,contract_value_id');
        $where=[];
        $where[]=['from_id','=',$param['village_id']];
        $where[]=['from', '=', 'house'];
        $where[]=['template_status','=',0];
        $where[]=['template_type', '=', 2];
        $field='template_id,template_title,add_time';
        $order='template_id desc';
        $data=$this->getDiyTemplateMethod($where,$field,$order,$param['page'],$param['limit']);
        $site_url=cfg('site_url');
        if($data['list']){
            foreach ($data['list'] as &$v){
                $status=0;
                if($info['contract_template_id'] && $info['contract_value_id']){
                    $value_info=$this->PluginMaterialDiyValue->getOne([
                        ['id','=',$info['contract_value_id']],
                        ['template_id','=',$v['template_id']],
                        ['diy_tatus','<>',4],
                    ],'id');
                    if ($value_info){
                        $status=1;
                    }
                }
                $v['contract_status']=$status;
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                $v['operation_add_url']=$site_url.'/packapp/material_diy/index.html#/template_write?template_id='.$v['template_id'].'&paths=write_list&source=publicRental&record_id='.$info['id'];
                $v['id']=$info['id'];
            }
        }
        $data['total_limit'] = $param['limit'];
        return $data;
    }


    /**
     * 编辑排号规则
     * @author:zhubaodi
     * @date_time: 2022/8/6 15:22
     */
    public function addQueuingRule($data){
        $db_rental_queuing_rule=new HousePublicRentalQueuingRule();
        $rule_info=$db_rental_queuing_rule->getOne(['village_id'=>$data['village_id'],'type'=>$data['source_type'],'del_time'=>0],'id');
        $data_arr=[];
        $data_arr['type']=$data['source_type'];
        $data_arr['title']=$data['title'];
        $data_arr['user_name']=$data['user_name'];
        $data_arr['user_phone']=$data['user_phone'];
        $data_arr['long']=$data['long'];
        $data_arr['lat']=$data['lat'];
        $data_arr['max_queue_number']=$data['max_queue_number'];
        $data_arr['content']=$data['content'];
        $data_arr['examine_type']=$data['examine_type'];
        $data_arr['adress']=$data['adress'];
        $data_arr['queuing_start_time']=strtotime($data['queuing_start_time']);
        $data_arr['queuing_end_time']=strtotime($data['queuing_end_time']);
        if (!empty($data['work_start_time'])){
            $data_arr['work_start_time']=$data['work_start_time'];
        }
        if (!empty($data['work_end_time'])){
            $data_arr['work_end_time']=$data['work_end_time'];
        }
        $data_arr['update_time']=$this->time;
        if (!empty($rule_info)){
          $res=$db_rental_queuing_rule->saveOne(['id'=>$rule_info['id']],$data_arr);
        }else{
            $data_arr['village_id']=$data['village_id'];
            $data_arr['add_time']=$this->time;
            $res=$db_rental_queuing_rule->addOne($data_arr);
        }
        return $res;
    }


    /**
     * 查询排号规则
     * @author:zhubaodi
     * @date_time: 2022/8/6 15:22
     */
    public function getQueuingRule($data){
        $field='id,title,user_name,user_phone,long,lat,adress,queuing_start_time,queuing_end_time,work_start_time,work_end_time,max_queue_number,examine_type,content';
        $rule_info=$this->HousePublicRentalQueuingRule->getOne([
            'village_id'=>$data['village_id'],
            'type'=>$data['source_type'],
            'del_time'=>0
        ],$field);
        if ($rule_info && !$rule_info->isEmpty()) {
            $rule_info= $rule_info->toArray();
            $rule_info['queuing_time']=[
                date('Y-m-d',$rule_info['queuing_start_time']),
                date('Y-m-d',$rule_info['queuing_end_time'])
            ];
            unset($rule_info['queuing_start_time'],$rule_info['queuing_end_time']);
        }else{
            $rule_info=[
                'id'=>0,
                'title'=>'',
                'user_name'=>'',
                'user_phone'=>'',
                'long'=>'',
                'lat'=>'',
                'adress'=>'',
                'examine_type'=>0,
                'content'=>'',
                'queuing_time'=>[]
            ];
        }
        $village_info=$this->HouseVillage->getOne($data['village_id'],'work_time,long,lat');
        if(!isset($rule_info['work_start_time']) || empty($rule_info['work_start_time'])){
            if($village_info){
                $work_time=explode('-',$village_info['work_time']);
                if(is_array($work_time) && !empty($work_time)){
                    $rule_info['work_start_time']=$work_time[0];
                    $rule_info['work_end_time']=$work_time[1];
                }
            }
        }
        if(!isset($rule_info['long']) || empty($rule_info['long'])){
            if($village_info){
                $rule_info['long']=$village_info['long'];
                $rule_info['lat']=$village_info['lat'];
            }
        }
        if(isset($rule_info['id']) && $rule_info['id']){
            /**
             * todo 此处需要换前端链接 已替换
             */
            $url=get_base_url('pages/houseManage/pages/leaseApply?village_id='.$data['village_id'].'&source_type='.$data['source_type']);
            $url=$this->assembleUrlMethod($url);
        }else{
            $url='';
        }
        return [
            'data'=>$rule_info,
            'voucher_img'=>[$url]
        ];
    }

    /**
     *  申请记录（add_apply）+审核记录（examine_apply）+模板消息通知
     * @author: liukezhu
     * @date : 2022/8/8
     * @param $id
     * @param $type
     * @return bool
     */
    public function addApplyV20($id,$type){
        fdump_api(['申请记录+审核记录=='.__LINE__,$id,$type],'public_rental/addApplyV20',1);
        if($type == 'add_apply'){
            $this->appointmentRecordMethod($id,'user_submit');
        }elseif ($type == 'examine_apply'){
            $this->sendUserNoticeMethod($id,'hotel_examine');
        }elseif ($type == 'add_renting'){
            //todo 添加退租 待处理
            (new HousePublicRentalRentingService())->rentingRecordMethod($id,'renting_submit');
        }
        return true;
    }



    /**
     * 查询退租详情
     * @author:zhubaodi
     * @date_time: 2022/8/6 17:10
     */
    public function getCancelRentalInfo($data){
        $recordInfo = $this->HousePublicRentalApplyRecord->getOne(['village_id'=>$data['village_id'],'id'=>$data['id']]);
        if (!empty($recordInfo)){
            $log_list=$this->getRecordLogList(['village_id'=>$data['village_id'],'rental_id'=>$data['id']]);
        }

        $data1=[];
        $data1['recordInfo']=$recordInfo;
        $data1['log_list']=$log_list;
        return $data1;
    }


    /**
     * 查询处理纪录
     * @author:zhubaodi
     * @date_time: 2022/8/6 17:37
     */
    public function getRecordLogList($data){
        $db_house_public_rental_apply_record_log=new HousePublicRentalApplyRecordLog();
        $log_list=$db_house_public_rental_apply_record_log->getList([['village_id'=>$data['village_id'],'rental_id'=>$data['rental_id']]]);
        if (!empty($log_list)){
            $log_list=$log_list->toArray();
        }
        if (!empty($log_list)){
            foreach ($log_list as &$v){
                if (!empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
            }
        }
        return $log_list;
    }


    /**
     * 修改申请状态
     * @author:zhubaodi
     * @date_time: 2022/8/6 17:57
     * int  $data['id'] 申请id
     * int  $data['village_id'] 小区id
     * int  $data['type] 业务类型 1:办理入住 2:电子合同 3:办理退租
     * int  $data['role_id'] 操作人id
     * string $data['role_name'] 操作人姓名
     * string $data['role_phone'] 操作人手机号
     * string $data['content']  操作审核内容/备注
     * array|string $data['imgs'] 上传的图片（可上传多张）
     * int $data['status'] 操作类型 （退租状态  1：退租待审核 2:退租审核通过 3:退租审核不通过 4:排号待审核 5:排号自动审核通过 6:排号审核不通过7:排号审核通过 8:验房合格 9:验房不合格）
     *
     */
    public function editRentalStatus($data){
        $db_house_public_rental_apply_record_log=new HousePublicRentalApplyRecordLog();
        $time=time();
        $res=0;
        $recordInfo = $this->HousePublicRentalApplyRecord->getOne(['village_id'=>$data['village_id'],'id'=>$data['id']]);
        if (!empty($recordInfo)){
            if ($data['type']==3){
                $res= $this->HousePublicRentalApplyRecord->saveOne(['village_id'=>$data['village_id'],'id'=>$data['id']],['refund_status'=>$data['status']]);
            }
           if ($res>0){
               if (is_array($data['imgs'])){
                   $data['imgs']=implode(',',$data['imgs']);
               }
               $data_log=[
                   'type'=>$data['type'],
                   'village_id'=>$data['village_id'],
                   'rental_id'=>$data['id'],
                   'operator_id'=> isset($data['role_id']),
                   'log_operator'=> isset($data['role_name'])?$data['role_name']:'',
                   'log_phone' => isset($data['role_phone'])?$data['role_phone']:'',
                   'log_uid' => $recordInfo['uid'],
                   'log_content' => $data['content'],
                   'log_imgs'=>isset($data['imgs'])?$data['imgs']:'',
                   'status'=>$data['status'],
                   'add_time'=>$time,
               ];
               $db_house_public_rental_apply_record_log->addOne($data_log);
           }
        }

        return $res;
    }

    /**
     * 修改退租状态
     * @author:zhubaodi
     * @date_time: 2022/8/8 14:54
     */
    public function editCancelRentalstatus($data){
       $res= $this->HousePublicRentalApplyRecord->getOne(['village_id'=>$data['village_id'],'id'=>$data['id']]);
       if (empty($res)){
           throw new \think\Exception('申请信息不存在，无法操作');
       }
       if ($data['status']==1){
           if ($res['refund_status']==1){
               $data['status']=2;
           }elseif($res['refund_status']==4){
               $data['status']=5;
           }
           elseif(in_array($res['refund_status'],[5,7])){
               $data['status']=8;
           }
       }elseif ($data['status']==2){
           if ($res['refund_status']==1){
               $data['status']=3;
           }elseif($res['refund_status']==4){
               $data['status']=6;
           }
           elseif(in_array($res['refund_status'],[5,7])){
               $data['status']=9;
           }
       }
       return $this->editRentalStatus($data);

    }


    /**
     * 添加排号申请
     * @author:zhubaodi
     * @date_time: 2022/8/9 9:01
     * int  $data['record_id'] 申请id
     * int  $data['village_id'] 小区id
     * int  $data['type] 业务类型 1:办理入住 2:电子合同 3:办理退租
     * int  $data['rule_id'] 排号规则id
     * string $data['apply_time'] 申请时间
     * string $data['remark'] 备注
     */
    public function addAppointmentApply($data){
        $res= $this->HousePublicRentalApplyRecord->getOne(['village_id'=>$data['village_id'],'id'=>$data['record_id']]);
        if (empty($res)){
            throw new \think\Exception('申请信息不存在，无法操作');
        }
          $rule_info=$this->HousePublicRentalQueuingRule->getOne(['id'=>$data['rule_id'],'type'=>$data['type']]);
          if (empty($rule_info)){
              throw new \think\Exception('排号规则不存在，无法申请');
          }
          $where=[
              ['rule_id','=',$data['rule_id']],
              ['village_id','=',$data['village_id']],
              ['type','=',$data['type']],
              ['status','<',2],
              ['apply_time','>=',strtotime($data['apply_time'].' 00:00:00')],
              ['apply_time','<=',strtotime($data['apply_time'].' 23:59:59')],
          ];
          $appointment_count=$this->HousePublicRentalAppointmentRecord->getCount($where);
          if ($appointment_count>0&&$rule_info['max_queue_number']<$appointment_count){
              throw new \think\Exception('今日申请量已满，请选择其他时间申请');
          }
          if ($rule_info['examine_type']==1){
              $status=1;
              $edit_status=5;
          }else{
              $status=0;
              $edit_status=4;
          }
          $data_arr=[
              'type'=>$data['type'],
              'uid'=>$res['uid'],
              'pigcms_id'=>$res['pigcms_id'],
              'village_id'=>$data['village_id'],
              'status'=>$status,
              'remark'=>$data['remark'],
              'apply_time'=>strtotime($data['apply_time']),
              'rule_id'=>$data['rule_id'],
              'apply_id'=>$data['record_id'],
              'add_time'=>time(),
              'update_time'=>time(),
          ];
        $id=$this->HousePublicRentalAppointmentRecord->addOne($data_arr);
        if ($id>0){
            $data_edit=[
                'id'=> $data['record_id'],
                'village_id'=> $data['village_id'],
                'type'=> $data['type'],
                'content'=> $data['remark'],
                'status'=> $edit_status,
            ];
            $this->editRentalStatus($data_edit);
        }
        return $id;
    }

    /**
     * 取消验房申请
     * @author:zhubaodi
     * @date_time: 2022/8/9 20:32
     */
    public function editAppointmentApplyStatus($data){
        $res= $this->HousePublicRentalApplyRecord->getOne(['village_id'=>$data['village_id'],'id'=>$data['record_id']]);
        if (empty($res)){
            throw new \think\Exception('申请信息不存在，无法操作');
        }
        $appointment_info=$this->HousePublicRentalAppointmentRecord->getOne(['apply_id'=>$data['record_id'],'id'=>$data['id']]);
        if (empty($appointment_info)){
            throw new \think\Exception('排号申请信息不存在，无法操作');
        }
        $id=$this->HousePublicRentalAppointmentRecord->saveOne(['village_id'=>$data['village_id'],'id'=>$data['id']],['status'=>3]);
        if ($id>0){
            $data_edit=[
                'id'=> $data['record_id'],
                'village_id'=> $data['village_id'],
                'type'=> $data['type'],
                'content'=> '',
                'status'=> 6,
            ];
            $this->editRentalStatus($data_edit);
        }
        return $id;
    }

    /**
     * 查询排号列表
     * @author:zhubaodi
     * @date_time: 2022/8/9 20:55
     */
    public function getAppointmentList($data){
        $where=['village_id'=>$data['village_id'],'type'=>$data['type']];
        if (!empty($data['pigcms_id'])){
            $where['pigcms_id']=$data['pigcms_id'];
        }

        $appointment_info=$this->HousePublicRentalAppointmentRecord->getList($where);
        if (empty($appointment_info)){
            throw new \think\Exception('排号申请信息不存在，无法操作');
        }
    }


    /**
     * 查询排号预约详情
     * @author:zhubaodi
     * @date_time: 2022/8/10 13:35
     */
    public function getAppointmentInfo($data){
        $where=['village_id'=>$data['village_id'],'type'=>$data['type'],'id'=>$data['id']];
        if (!empty($data['pigcms_id'])){
            $where['pigcms_id']=$data['pigcms_id'];
        }

        $appointment_info=$this->HousePublicRentalAppointmentRecord->getOne($where);
        if (empty($appointment_info)){
            throw new \think\Exception('排号申请信息不存在，无法操作');
        }
    }
}