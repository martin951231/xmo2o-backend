<?php
/**
 * @author : liukezhu
 * @date : 2022/9/14
 */

namespace app\community\model\service;


use app\community\model\db\HousePublicRentalApplyRecordLog;
use app\community\model\db\HousePublicRentalRentingRecord;
use app\community\model\db\PluginMaterialDiyValue;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserVacancy;
class HousePublicRentalRentingService
{
    protected $time;
    protected $HousePublicRentalRentingRecord;
    protected $HouseVillageService;
    protected $HousePublicRentalApplyRecordLog;
    protected $PluginMaterialDiyValue;

    //流程状态
    public $flow_status=[
        '10'=>'退租待审核',
        '11'=>'退租审核不通过',
        '12'=>'退租审核通过',
        '20'=>'排号待审核',
        '21'=>'排号审核不通过',
        '22'=>'排号自动审核通过',
        '23'=>'排号审核通过',
        '24'=>'取消排号',
        '30'=>'验房不合格',
        '31'=>'验房合格'
    ];

    //流程状态对应操作动作
    public $flow_log_name=[
        '11'=>'renting_refuse',
        '12'=>'renting_success',
        '20'=>'arranging_appointment',
        '21'=>'arranging_appointment_refuse',
        '22'=>'arranging_appointment_auto_success',
        '23'=>'arranging_appointment_success',
        '24'=>'arranging_appointment_cancel',
        '30'=>'inspection_refuse',
        '31'=>'inspection_success'
    ];

    // 对应操作动作
    public $log_name = [
        'renting_submit' => '退租待审核',
        'renting_success' => '退租审核通过',
        'renting_refuse' => '退租审核不通过',
        'arranging_appointment' => '排号预约',
        'arranging_appointment_success' => '排号审核通过',
        'arranging_appointment_auto_success' => '排号自动审核通过',
        'arranging_appointment_refuse' => '排号审核拒绝',
        'arranging_appointment_cancel' => '排号取消预约',
        'inspection_success'=>'验房合格',
        'inspection_refuse'=>'验房不合格',
    ];

    //搜索类型
    public $search_hotel_type_status=[
        'renting_status'=>[
            ['key'=>'','value'=>'全部'],
            ['key'=>10,'value'=>'退租待审核'],
            ['key'=>11,'value'=>'退租审核不通过'],
            ['key'=>12,'value'=>'退租审核通过'],
        ],
        'arranging_status'=>[
            ['key'=>'','value'=>'全部'],
            ['key'=>20,'value'=>'排号待审核'],
            ['key'=>21,'value'=>'排号审核不通过'],
        ],
        'inspection_status'=>[
            ['key'=>'','value'=>'全部'],
            ['key'=>30,'value'=>'验房不合格'],
            ['key'=>31,'value'=>'验房合格'],
        ],
    ];

    public function __construct()
    {
        $this->time=time();
        $this->HousePublicRentalRentingRecord=new HousePublicRentalRentingRecord();
        $this->HouseVillageService = new HouseVillageService();
        $this->HousePublicRentalApplyRecordLog=new HousePublicRentalApplyRecordLog();
        $this->PluginMaterialDiyValue=new PluginMaterialDiyValue();
    }

    //todo 查询绑定退租记录
    public function getRecordBindUserMethod($where,$field=true,$source_type=1){
        $info=$this->HousePublicRentalRentingRecord->getRecordBindUser($where,$field);
        if (!$info || $info->isEmpty()) {
            throw new \think\Exception('该数据不存在，请重新操作');
        }
        return $info->toArray();
    }

    //todo 查询单条退租记录
    public function getApplyRecordFindMethod($where,$field=true,$source=0){
        $info=$this->HousePublicRentalRentingRecord->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            if($source){
                return false;
            }else{
                throw new \think\Exception('该数据不存在，请重新操作');
            }
        }
        return $info->toArray();
    }

    //todo 获取流程状态按钮
    public function getFlowButtonStatusMethod($flow_status){
        $examine_arr=[];
        if(in_array($flow_status,[10])){ //退租待审核,退租审核不通过
            $examine_arr=[
                ['key'=>12,'value'=>'通过'],
                ['key'=>11,'value'=>'不通过']
            ];
        }
        else if(in_array($flow_status,[20])){ //排号待审核
            $examine_arr=[
                ['key'=>23,'value'=>'通过'],
                ['key'=>21,'value'=>'不通过']
            ];
        }
        else if(in_array($flow_status,[22,23,30])){ //排号自动审核通过,排号审核通过
            $examine_arr=[
                ['key'=>31,'value'=>'合格'],
                ['key'=>30,'value'=>'不合格']
            ];
        }
        return $examine_arr;
    }

    //todo 获取办理退租类型
    public function getRentingFlowTypeMethod($flow_status,$source=0,$cfrom=''){
        $type=0;
        $value='';
        $is_examine=false;
        if(in_array($flow_status,[10,11,12])){
            $type=9;
            $value='退租';
            if(in_array($flow_status,[10])){
                $is_examine=true;
            }
        }
        else if(in_array($flow_status,[20,21])){
            $type=2;
            $value='排号';
            if($flow_status == 20){
                $is_examine=true;
            }
        }
        else if(in_array($flow_status,[22,23,30,31])){
            $type=3;
            $value='验房';
            if(in_array($flow_status,[22,23,30])){
                $is_examine=true;
            }
            if($cfrom=='manage' && $flow_status==30){
                
            }
        }
        $data=[
            'type'=>$type,
            'value'=>$value
        ];
        $examine_arr=$this->getFlowButtonStatusMethod($flow_status);
        if($source){
            $data['is_examine'] =$is_examine;
            $data['examine_data'] =$examine_arr;
        }
        return $data;
    }

    //todo 校验办理退租权限按钮
    public function checkRentingPowerButtonMethod($menus=[],$data=[],$param=[]){
        $is_see=true;
        $is_edit=true;
        $is_del=true;
        if(isset($data['is_examine']) && !$data['is_examine']){
            $is_edit=false;
        }
        return ['is_see'=>$is_see,'is_edit'=>$is_edit,'is_del'=>$is_del];
    }

    //todo 写入操作记录
    public function rentingRecordMethod($record_id,$log_type,$param=[]){
        $where[]=['r.id','=',$record_id];
        $where[]=['r.del_time', '=', 0];
        $field='r.id,r.village_id,r.uid,u.nickname,u.phone';
        $record_info=$this->HousePublicRentalRentingRecord->getRecordBindUser($where,$field);
        if (!$record_info || $record_info->isEmpty()) {
            return false;
        }
        $record_info=$record_info->toArray();
        $log=[
            'type'=>2,
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
            case "renting_submit":
                $log['log_operator']=$record_info['nickname'];
                $log['log_phone']=$record_info['phone'];
                break;
            case "renting_success":

                break;
            case "renting_refuse":

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

    //todo 获取预约记录状态
    public function getRecordStatusMethod($status){
        $status=(int)$status;
        $flow_type=$this->getRentingFlowTypeMethod($status);
        $flow_status='';
        $flow_button=['is_see'];
        switch ($status) {
            case 20: //排号待审核
                $flow_status='待审核';
                break;
            case 22: //排号自动审核通过  可取消预约 走退租审核通过
                $flow_status='审核通过';
                $flow_button=['is_see','cancel_appoint'];
                break;
            case 21: //排号审核不通过
                $flow_status='审核拒绝';
                break;
            case 23: //排号审核通过 可取消预约 走退租审核通过
                $flow_status='审核通过';
                $flow_button=['is_see','cancel_appoint'];
                break;
            case 24: //取消排号
                $flow_status='取消排号';
                break;
            case 30: //验房不合格
                $flow_status='验房不合格';
                break;
            case 31: //验房合格
                $flow_status='验房合格';
                break;
        }
        return ['type'=>$flow_type['value'],'status'=>$flow_status,'button'=>$flow_button];
    }


    /**
     * 退租列表
     * @author: liukezhu
     * @date : 2022/9/14
     * @param $param
     * @return mixed
     */
    public function getRentingList($param){
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
                case "renting_status": //退租
                    $where[] = ['r.flow_status','in',[10,11]];
                    break;
                case "arranging_status": //排号
                    $where[] = ['r.flow_status','in',[20,21]];
                    break;
                case "inspection_status": //验房
                    $where[] = ['r.flow_status','in',[22,23,30,31]];
                    break;
            }
        }
        if(!empty($param['handle_status'])){
            $where[]=['r.flow_status', '=', $param['handle_status']];
        }
        $field='r.id,r.number,r.village_id,r.single_id,r.floor_id,r.layer_id,r.vacancy_id,r.flow_status,r.add_time,u.nickname,u.phone,( SELECT log_type FROM '.$prefix.'house_public_rental_apply_record_log AS g WHERE g.rental_id = r.id and g.type = 2 ORDER BY g.id DESC LIMIT 0, 1 ) AS handle_status ';
        $order='r.id desc';
        $count=0;
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$param['village_id']),'*');
        $list = $this->HousePublicRentalRentingRecord->getList($where,$field,$order,$param['page'],$param['limit']);
        if ($list && !$list->isEmpty()){
            $list=$list->toArray();
            foreach ($list as &$v){
                $flow_info=$this->getRentingFlowTypeMethod($v['flow_status'],1);
                if($v['vacancy_id']){
                    $address = $this->HouseVillageService->getSingleFloorRoom($v['single_id'], $v['floor_id'], $v['layer_id'], $v['vacancy_id'],$v['village_id']);
                }else{
                    $address='';
                }
                if($v['phone']){
                    $v['phone']=$houseVillageDb->phone_desensitization_v($v['phone'],$param['village_id'],$village_info);
                }
                $v['address']=$address;
                $v['handle_status']= isset($this->log_name[$v['handle_status']]) ? $this->log_name[$v['handle_status']] : $v['handle_status'] ;
                $v['handle_type']=$flow_info['value'];
                $v['source_type']=2;
                $v['button']=$this->checkRentingPowerButtonMethod($param['menus'],$flow_info,$v);
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            }
            $count = $this->HousePublicRentalRentingRecord->getCount($where);
        }
        $data['list']=$list;
        $data['count']=$count;
        $data['total_limit'] = $param['limit'];
        $data['status_list']=$this->search_hotel_type_status;
        return $data;
    }

    /**
     * 删除退租申请
     * @author: liukezhu
     * @date : 2022/9/15
     * @param $village_id
     * @param $id
     * @return bool
     * @throws \think\Exception
     */
    public function rentingDel($village_id,$id){
        $info=$this->HousePublicRentalRentingRecord->getOne([
            ['id', '=', $id],
            ['village_id', '=', $village_id],
            ['del_time', '=', 0]
        ],'*');
        if (!$info || $info->isEmpty()) {
            throw new \think\Exception('该数据不存在，请重新操作');
        }
        //删除申请记录
        $this->HousePublicRentalRentingRecord->saveOne([['id', '=', $id]],['update_time'=>$this->time,'del_time'=>$this->time]);
        //删除提交模板记录
        $this->PluginMaterialDiyValue->saveOne([
            ['id', '=', $info['value_id']],
            ['template_id', '=', $info['template_id']],
            ['diy_tatus','<>',4]
        ],['diy_tatus'=>4,'last_time'=>$this->time]);
        return true;
    }

    /**
     * 退租申请列表
     * @author: liukezhu
     * @date : 2022/9/15
     * @param $param
     * @return mixed
     */
    public function getUserApplyRecordList($param){
        $where[]=['village_id','=',$param['village_id']];
        $where[]=['del_time', '=', 0];
        $where[]=['uid', '=', $param['uid']];
        $where[]=['vacancy_id', '>', 0];
        $field='id as record_id,number,template_id,value_id,village_id,pigcms_id,single_id,floor_id,layer_id,vacancy_id,flow_status,add_time';
        $order='id desc';
        $count=0;
        $list = $this->HousePublicRentalRentingRecord->getRecordList($where,$field,$order,$param['page'],$param['limit']);
        if ($list && !$list->isEmpty()){
            $list=$list->toarray();
            foreach ($list as &$v){
                $qr_code='';
                $address='';
                if($v['vacancy_id']){
                   $address = $this->HouseVillageService->getSingleFloorRoom($v['single_id'], $v['floor_id'], $v['layer_id'], $v['vacancy_id'],$v['village_id']);
               }
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
                //$qr_code=$this->assembleUrlMethod(get_base_url('/pages/houseManage/pages/subscribeDetail?village_id='.$v['village_id'].'&pigcms_id='.$v['pigcms_id'].'&record_id='.$v['record_id'].'&page_type=rentcancell'));
                
                $urltmp=cfg('site_url').'/packapp/community/pages/Community/checkoutApplication/voucherManagement?record_id='.$v['record_id'].'&page_type=rentcancell&type=1&gotoIndex=tem_msg';
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
                        'label'=>'地址',
                        'value'=>$address
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
                unset($v['number'],$v['add_time'],$v['template_id'],$v['value_id'],$v['village_id'],$v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id']);
            }
            unset($v);
            $count = $this->HousePublicRentalRentingRecord->getRecordCount($where);
        }
        $data['list']=$list;
        $data['count']=$count;
        $data['total_limit'] = $param['limit'];
        return $data;
    }
    //todo 组装url生成二维码
    public function assembleUrlMethod($url){
        $path=urlencode(htmlspecialchars_decode($url));
        return cfg('site_url').'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.$path;
    }
    /**
     * 退租预约记录
     * @author: liukezhu
     * @date : 2022/9/15
     * @param $param
     * @return mixed
     */
    public function getMyRentingRecordList($param){
        $where[]=['r.village_id','=',$param['village_id']];
        $where[]=['r.del_time', '=', 0];
        $where[]=['r.uid', '=', $param['uid']];
        //$where[]=['r.pigcms_id', '=', $param['pigcms_id']];
        $where[]=['r.flow_status','>=',12];
        $field='r.id as record_id,r.number,r.flow_status,a.appoint_time';
        $order='r.id desc';
        $count=0;
        $list = $this->HousePublicRentalRentingRecord->getRecordArrangingList($where,$field,$order,$param['page'],$param['limit']);
        if ($list && !$list->isEmpty()){
            $list=$list->toArray();
            foreach ($list as &$v){
                $flow_info=$this->getRecordStatusMethod($v['flow_status']);
                $v['title']='公租房'.$flow_info['type'];
                $v['appoint_time']=date('Y-m-d H:i:s',$v['appoint_time']);
                $v['flow_info']=$flow_info;
                unset($v['flow_status']);
            }
            unset($v);
            $count = $this->HousePublicRentalRentingRecord->getRecordArrangingCount($where);
        }
        $data['list']=$list;
        $data['count']=$count;
        $data['total_limit'] = $param['limit'];
        return $data;
    }

    /**
     * 退租 取消预约+ 验房状态
     * @author: liukezhu
     * @date : 2022/9/16
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function operationInspection($param){
        $HousePublicRentalApplyService= new HousePublicRentalApplyService();
        $where[]=['id', '=', $param['record_id']];
        if(is_array($param['village_id'])){
            $where[]=['village_id','in',$param['village_id']];
        }
        else{
            $where[]=['village_id','=',$param['village_id']];
        }
        if(isset($param['uid'])){
            $where[]=['uid', '=', $param['uid']];
        }
        $where[]=['del_time', '=', 0];
        $field='id,flow_status';
        $info=$this->getApplyRecordFindMethod($where,$field);
        $flow_status=$remarks=$succ_msg=$error_msg='';
        if(in_array($param['type'],['cancel_renting'])){ //取消预约
            $flow_info=$this->getRecordStatusMethod($info['flow_status']);
            $flow_status=24;
            $remarks='用户取消预约，进入退租处理类型，需要重新预约排号';
            $succ_msg='取消成功';
            $error_msg='取消预约';
            if(!$flow_info['button'] || !in_array($param['type'],$flow_info['button'])){
                throw new \think\Exception('该预约无须'.$error_msg.'，请勿重新操作');
            }
        }
        elseif ($param['type'] == 'is_inspection'){ //验房状态
            $flow_info=$this->getRentingFlowTypeMethod($info['flow_status'],1);
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
            'uid'=>isset($param['uid']) ? $param['uid'] : 0,
            'pigcms_id'=>isset($param['pigcms_id']) ? $param['pigcms_id'] : 0,
            'flow_status'=>$flow_status,
            'record_id'=>$info['id'],
            'remarks'=>$remarks,
            'source_type'=>2,
        ];
        //针对移动管理端审核退租
        if(isset($param['ht_log_operator'])){
            $record_arr['ht_log_operator']=$param['ht_log_operator'];
        }
        //针对验房提交上传图片
        if(isset($param['imags'])){
            $record_arr['imags']=$param['imags'];
        }

        //针对取消预约，状态扭转为退租审核通过
        if($flow_status == 24){
            $flow_status=12;
        }
        $result=$HousePublicRentalApplyService->userAddAppointmentRecordMethod($record_arr);
        $data=[
            'flow_status'=>$flow_status,
            'update_time'=>$this->time
        ];
        if($flow_status == 21){
            $data['arranging_record_id'] =0;
        }
        if($result){
            //更改流程状态
            $this->HousePublicRentalRentingRecord->saveOne([
                ['id', '=', $info['id']],
                ['del_time', '=', 0]
            ],$data);
        }
        return ['code'=>2002,'msg'=>$succ_msg];
    }

    /**
     * 退租 退租记录
     * @author: liukezhu
     * @date : 2022/9/16
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public function getWorkerRentingRecordList($param){
        $where[]=['r.village_id','in',$param['village_id']];
        $where[]=['r.del_time', '=', 0];
        switch ((int)$param['type']) { //筛选凭证
            case 1: //待审核
                $where[]=['r.flow_status', '=', 10];
                break;
            case 2: //审核通过
                $where[]=['r.flow_status', '=', 12];
                break;
            case 3: //审核不通过
                $where[]=['r.flow_status', '=', 11];
                break;
            default :
                throw new \think\Exception('参数不合法，请稍后再试！');
                break;
        }
        if($param['keyword']){
            $where[]  = ['r.number|b.name','like',"%".$param['keyword']."%"];
        }
        $return_flow_status=[
            10=>1,
            12=>2,
            11=>3
        ];
        $field='r.id as record_id,r.number,r.flow_status,b.name,a.appoint_time';
        $order='r.id desc';
        $count=0;
        $list = $this->HousePublicRentalRentingRecord->getRecordBindUserList($where,$field,$order,$param['page'],$param['limit']);
        if ($list && !$list->isEmpty()){
            $list=$list->toArray();
            foreach ($list as &$v){
                $v['flow_status']=isset($return_flow_status[$v['flow_status']]) ? $return_flow_status[$v['flow_status']] : 0;
                $v['appoint_time']= $v['appoint_time'] ? date('Y-m-d H:i:s',$v['appoint_time']) : '--';
            }
            unset($v);
            $count = $this->HousePublicRentalRentingRecord->getRecordBindUserCount($where);
        }
        $data['list']=$list;
        $data['count']=$count;
        $data['total_limit'] = $param['limit'];
        return $data;
    }

    /**
     * 退租 验房记录
     * @author: liukezhu
     * @date : 2022/9/16
     * @param $param
     * @return mixed
     */
    public function getWorkerRentingHouseRecord($param){
        $where[]=['r.village_id','in',$param['village_id']];
        $where[]=['r.del_time', '=', 0];
        $where[]=['r.flow_status','in',[22,23,30,31]];
        $return_flow_status=[
            22=>'待验房',
            23=>'待验房',
            30=>'不合格',
            31=>'合格',
        ];
        $field='r.id as record_id,r.number,r.flow_status,b.name,a.appoint_time';
        $order='r.id desc';
        $count=0;
        $list = $this->HousePublicRentalRentingRecord->getRecordBindUserList($where,$field,$order,$param['page'],$param['limit']);
        if ($list && !$list->isEmpty()){
            $list=$list->toArray();
            foreach ($list as &$v){
                $v['btnTxt']='查看';
                if($v['flow_status']==22 || $v['flow_status']==23){
                    $v['btnTxt']='审核';
                }
                if(isset($param['cfrom']) && ($param['cfrom']=='manage') && $v['flow_status']==30){
                    $v['btnTxt']='验房';
                }
                $v['flow_status']=isset($return_flow_status[$v['flow_status']]) ? $return_flow_status[$v['flow_status']] : 0;
                $v['appoint_time']= $v['appoint_time'] ? date('Y-m-d H:i:s',$v['appoint_time']) : '--';

            }
            unset($v);
            $count = $this->HousePublicRentalRentingRecord->getRecordBindUserCount($where);
        }
        $data['list']=$list;
        $data['count']=$count;
        $data['total_limit'] = $param['limit'];
        return $data;
    }


}