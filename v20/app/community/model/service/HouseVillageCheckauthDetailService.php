<?php


namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseAdmin;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseVillageCheckauthApply;
use app\community\model\db\HouseVillageCheckauthDetail;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseWorker;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseVillageService;
use think\Exception;

class HouseVillageCheckauthDetailService
{
    // 审核申请类型
    public $xtype = [
        ['title' => '全部','type' => ''],
        ['title' => '退款','type' => 'order_refund'],
        ['title' => '作废','type' => 'order_discard'],
        ['title' => '修改物业服务时间','type' => 'service_time_check'],
    ];
    
    public function getXType() {
        $xtype = [
            ['title' => '全部','type' => ''],
            ['title' => '退款','type' => 'order_refund'],
            ['title' => '作废','type' => 'order_discard'],
            ['title' => '修改物业服务时间','type' => 'service_time_check'],
        ];
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_wsxdsj_net_customized_open) {
            $xtype[] = ['title' => '订单金额修改申请','type' => 'modify_money_check'];
            $xtype[] = ['title' => '工单转单','type' => 'transfer_other_work'];
        }
        return $xtype;
    }

    // 审核状态
    public $status = [
        ['title' => '全部','status' => 999],
        ['title' => '审核中','status' => 0],
        ['title' => '审核通过','status' => 1],
        ['title' => '审核未通过','status' => 2],
    ];

    public function getOneData($where,$field = true) {

        $db_villageCheckauthDetail = new HouseVillageCheckauthDetail();
        $info = $db_villageCheckauthDetail->getOne($where,$field);
        return $info;
    }

    public function addDetail($addData=array()){
        if(empty($addData)){
            return false;
        }
        $db_villageCheckauthDetail = new HouseVillageCheckauthDetail();
        if(!isset($addData['add_time'])){
            $addData['add_time']=time();
        }
        $idd=$db_villageCheckauthDetail->addDetail($addData);
        return $idd;
    }

    //更新数据
    public function updateDetail($where=array(),$updateArr=array()){
        if(empty($where) || empty($updateArr)){
            return false;
        }
        $db_villageCheckauthDetail = new HouseVillageCheckauthDetail();
        return $db_villageCheckauthDetail->updateDetail($where,$updateArr);
    }

    /**
     * 获取搜索条件列表
     * User: zhanghan
     * Date: 2022/2/10
     * Time: 14:02
     * @return array
     */
    public function getSearchList(){
        $list['xtype_list'] = $this->getXType();
        $list['status_list'] = $this->status;
        return $list;
    }

    /**
     * 获取我的审批列表
     * User: zhanghan
     * Date: 2022/2/9
     * Time: 20:55
     * @param $param
     * @param bool $field
     * @param string $order
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function myCheckList($param,$field = true,$order = 'a.id DESC',$limit = 10){
        if(empty($param['village_id']) || empty($param['uid']) || empty($param['role'])){
            throw new Exception('缺少小区用户相关参数');
        }
        $db_checkauth_detail = new HouseVillageCheckauthDetail();

        $wid = $param['uid'];
        if($param['role'] == 5){
            $wid = $this->getWorkerId($param['uid']);
        }

        $where = [];
        $where[] = ['d.wid','=',$wid];
        $where[] = ['d.village_id','=',$param['village_id']];
        if($param['status'] != 999 && in_array($param['status'],[0,1,2])){
            $where[] = ['d.status','=',$param['status']];
        }
        if(!empty($param['xtype']) && in_array($param['xtype'],['order_refund','order_discard','service_time_check','transfer_other_work'])){
            $where[] = ['a.xtype','=',$param['xtype']];
        }else{
            $where[] = ['a.xtype','not in',array('order_servicetime_check')];
        }
        $list = $db_checkauth_detail->myCheckList($where,$field,$order,$param['page'],$limit);
        if(!empty($list['list'])){
            $db_checkauth_apply = new HouseVillageCheckauthApply();
            $nowtime=time();
            foreach ($list['list'] as &$value){
                // 物业服务时间使用 申请原因
                if($value['xtype'] == 'service_time_check'){
                    $value['order_name'] = $value['apply_reason'];
                }
                if($value['xtype'] == 'transfer_other_work'){
                    $extra_data = isset($value['extra_data']) ? json_decode($value['extra_data'], 1) : [];
                    $value['order_name'] = isset($extra_data['title']) ? trim($extra_data['title']) : '工单转单';
                }

                $value['add_time'] = date("Y-m-d H:i:s",$value['add_time']);
                $value['status_txt'] = empty($value['status']) ? '审核中' : (($value['status'] == 1) ? '审核通过' : '审核未通过');
                if($value['xtype'] != 'transfer_other_work' && isset($value['is_discard']) && $value['is_discard'] == 2){
                    //已经作废了
                    $value['status_txt']='审核通过';
                    $verifyData= ['apply_time'=>$nowtime,'status'=>1];
                    if($value['discard_reason']){
                        $verifyData['bak']=$value['discard_reason'];
                    }
                    $this->updateDetail(array('apply_id' => $value['apply_id']),$verifyData);
                    $updateArr = array('status' => 2, 'apply_time' => $nowtime);
                    $db_checkauth_apply->updateApply(array('id' => $value['apply_id']), $updateArr);
                }
                $value['list'] = [
                    [
                        'title' => '当前审批人',
                        'content' => $value['name'],
                    ],
                    [
                        'title' => '申请时间',
                        'content' => $value['add_time'],
                    ]
                ];
            }
        }
        return $list;
    }

    /**
     * 获取审批详情
     * User: zhanghan
     * Date: 2022/2/11
     * Time: 9:57
     * @param $apply_id
     * @param $uid
     * @param $role
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function myCheckDetail($apply_id,$uid,$role){
        if(empty($apply_id)){
            throw new Exception('申请信息错误');
        }
        $db_checkauth_detail = new HouseVillageCheckauthDetail();
        $db_checkauth_apply = new HouseVillageCheckauthApply();
        $new_pay = new HouseNewPayOrder();

        $wid = $uid;
        if($role == 5){
            $wid = $this->getWorkerId($uid);
        }

        $return = [];

        $where = [];
        $where[] = ['apply_id','=',$apply_id];
        $where[] = ['wid','=',$wid];
        $info = $db_checkauth_detail->getOne($where,'status,order_id,apply_id,bak');
        $return['info'] = $info;
        if(empty($info)){
            throw new Exception('未查询到相关信息');
        }
        $where_apply = [];
        $where_apply[] = ['id','=',$apply_id];
        $apply_info = $db_checkauth_apply->getOne($where_apply,'order_id,xtype,add_time,apply_name,service_start_time,service_end_time,apply_reason,status,extra_data');

        $extra_data = [];
        if(!empty($apply_info['extra_data'])){
            $extra_data = json_decode($apply_info['extra_data'],true);
        }

        $return['info']['xtype'] = $apply_info['xtype'];

        $return['detail_list'] = [];
        $address='';
        $yname='';
        if(in_array($apply_info['xtype'],['order_refund','order_discard','modify_money_check','order_paytime_check'])){
            // 订单信息
            $where_order = [];
            $where_order[] = ['o.order_id','=',$info['order_id']];
            $field = 'r.charge_name,r.charge_valid_time,r.late_fee_rate,o.summary_id,o.name,o.phone,o.village_id,o.room_id,o.order_name,o.service_start_time,o.service_end_time,o.modify_money,o.pay_money,o.add_time,o.late_payment_day,o.late_payment_money,o.modify_reason,o.total_money,o.order_serial,o.order_no,o.unit_price,o.refund_money,o.pay_time,o.pay_money,o.remark';
            $order = $new_pay->getOne($where_order,$field);
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $service_house_village = new HouseVillageService();
            $yname=$order['name'];
            $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $order['room_id']], 'pigcms_id,single_id,floor_id,layer_id,village_id,usernum');
            if ($vacancy_info) {
                $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $order['room_id'], $vacancy_info['village_id']);
            }
            if($apply_info['xtype'] == 'order_refund'){
                if (!empty($order['summary_id'])){
                    $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
                    $summary_info= $db_house_new_pay_order_summary->getOne(['summary_id' => $order['summary_id']]);
                    if (!empty($summary_info)){
                        $order['order_serial']=$summary_info['paid_orderid'];
                        $order['order_no']=$summary_info['order_no'];
                        $order['remark']=$summary_info['remark'];
                    }
                }
                $return['detail_list'][] = ['title' => '订单编号', 'content' => $order['order_no']];
                $return['detail_list'][] = ['title' => '支付单号', 'content' => $order['order_serial']];
            }
            $return['detail_list'][] = ['title' => '收费标准名称', 'content' => $order['charge_name']];
            $return['detail_list'][] = ['title' => '项目名称', 'content' => $order['order_name']];
            $return['detail_list'][] = ['title' => '账单开始时间', 'content' => !empty($order['service_start_time']) &&  $order['service_start_time']>100 ? date('Y-m-d H:i:s',$order['service_start_time']) : '--'];
            $return['detail_list'][] = ['title' => '账单结束时间', 'content' => !empty($order['service_end_time'])&&  $order['service_start_time']>100 ? date('Y-m-d H:i:s',$order['service_end_time']) : '--'];
            $return['detail_list'][] = ['title' => '应收费用', 'content' => $order['total_money']];

            if($apply_info['xtype'] == 'order_refund'){
                $return['detail_list'][] = ['title' => '修改后费用', 'content' => $order['modify_money']];
                $return['detail_list'][] = ['title' => '修改原因', 'content' => $order['modify_reason']];
                $return['detail_list'][] = ['title' => '实际缴费金额', 'content' => $order['pay_money']];
                $return['detail_list'][] = ['title' => '支付时间', 'content' => !empty($order['pay_time']) &&  $order['pay_time']>100 ? date("Y-m-d H:i:s",$order['pay_time']) : '--'];
            }else{
                $return['detail_list'][] = ['title' => '实收费用', 'content' => $order['pay_money']];
                $return['detail_list'][] = ['title' => '收费标准生效时间', 'content' => !empty($order['charge_valid_time']) ? date("Y-m-d H:i:s",$order['charge_valid_time']) : ''];
                $return['detail_list'][] = ['title' => '账单生成时间', 'content' => !empty($order['add_time']) ? date("Y-m-d H:i:s",$order['add_time']) : ''];
            }
            if($apply_info['xtype'] == 'order_refund'){
                $return['detail_list'][] = ['title' => '单价', 'content' => $order['unit_price']];
            }
            if($apply_info['xtype'] == 'order_paytime_check'){
                $return['detail_list'][] = ['title' => '支付时间', 'content' => !empty($order['pay_time']) &&  $order['pay_time']>100 ? date("Y-m-d H:i:s",$order['pay_time']) : '--'];
            }

            $return['detail_list'][] = ['title' => '违约天数', 'content' => $order['late_payment_day']];
            $return['detail_list'][] = ['title' => '违约金收取比例（每天）', 'content' => $order['late_fee_rate'].'%'];
            $return['detail_list'][] = ['title' => '违约金费用', 'content' => $order['late_payment_money']];

            if($apply_info['xtype'] == 'order_refund'){
                $return['detail_list'][] = ['title' => '退款总金额', 'content' => $order['refund_money']];
            }

            $order['service_start_time'] = !empty($order['service_start_time']) && $order['service_start_time'] >100 ? date('Y-m-d H:i:s',$order['service_start_time']) : '--';
            $order['service_end_time'] = !empty($order['service_end_time'])  && $order['service_end_time'] >100 ? date('Y-m-d H:i:s',$order['service_end_time']) : '--';
            $order['charge_valid_time'] = !empty($order['charge_valid_time']) ? date('Y-m-d H:i:s',$order['charge_valid_time']) : '';
            $order['add_time'] = !empty($order['add_time']) ? date('Y-m-d H:i:s',$order['add_time']) : '';
            $order['pay_time'] = !empty($order['pay_time']) && $order['pay_time']>100 ? date('Y-m-d H:i:s',$order['pay_time']) : '--';
            $return['detail'] = $order;
        }

        // 申请详情
        $return['apply_list'] = [];
        $return['apply_list'][] = ['title' => '申请人', 'content' => $apply_info['apply_name']];
        $return['apply_list'][] = ['title' => '申请时间', 'content' => !empty($apply_info['add_time']) ? date("Y-m-d H:i:s",$apply_info['add_time']) : ''];

        if ($apply_info['xtype'] == 'service_time_check'){
            $return['apply_list'][] = ['title' => '修改后服务时间', 'content' => (!empty($apply_info['service_start_time']) && !empty($apply_info['service_end_time'])) ? date('Y-m-d',$apply_info['service_start_time']).'-'.date('Y-m-d',$apply_info['service_end_time']) : ''];
            $return['apply_list'][] = ['title' => '申请原因', 'content' => $apply_info['apply_reason']];
        }elseif ($apply_info['xtype'] == 'order_refund'){
            $return['apply_list'][] = ['title' => '退款金额', 'content' => !empty($extra_data['refund_money']) ? $extra_data['refund_money'].'元' : ''];
            $return['apply_list'][] = ['title' => '退款原因', 'content' => !empty($extra_data['refund_reason']) ? $extra_data['refund_reason'] : ''];
            $apply_info['apply_reason'] = !empty($extra_data['refund_reason']) ? $extra_data['refund_reason'] : '';
            $apply_info['refund_money'] = !empty($extra_data['refund_money']) ? $extra_data['refund_money'].'元' : '';
        }elseif ($apply_info['xtype'] == 'order_discard'){
            $return['apply_list'][] = ['title' => '作废原因', 'content' => !empty($extra_data['discard_reason']) ? $extra_data['discard_reason'] : ''];
            $apply_info['apply_reason'] = !empty($extra_data['discard_reason']) ? $extra_data['discard_reason'] : '';
        }elseif ($apply_info['xtype'] == 'modify_money_check'){
            $return['apply_list'][] = ['title' => '应收费用', 'content' => !empty($extra_data['total_money']) ? round($extra_data['total_money'],2) : ''];
            $return['apply_list'][] = ['title' => '修改前金额', 'content' => !empty($extra_data['modify_money']) ? round($extra_data['modify_money'],2) : ''];
            $return['apply_list'][] = ['title' => '修改金额', 'content' => !empty($extra_data['money']) ? round($extra_data['money'],2) : ''];
            $return['apply_list'][] = ['title' => '申请原因', 'content' => $extra_data['modify_reason']];
        }elseif ($apply_info['xtype'] == 'transfer_other_work'){
            $return['apply_list'][] = ['title' => '申请类型', 'content' => '转单申请'];
            if (isset($extra_data['to_work_id']) && $extra_data['to_work_id']) {
               $worker = (new HouseWorker())->getOne(['wid' => $extra_data['to_work_id']], 'wid, name');
               if (isset($worker['name']) && trim($worker['name'])) {
                   $return['apply_list'][] = ['title' => '转单工作人员', 'content' => trim($worker['name'])];
               }
            }
            $return['apply_list'][] = ['title' => '申请原因', 'content' => $extra_data['apply_reason']];
        }elseif ($apply_info['xtype'] == 'order_paytime_check'){
            $return['apply_list'][] = ['title' => '订单支付时间', 'content' =>  !empty($extra_data['front_pay_time']) &&  $extra_data['front_pay_time']>100 ? date("Y-m-d H:i:s",$extra_data['front_pay_time']) : '--'];
            $return['apply_list'][] = ['title' => '申请修改时间', 'content' => !empty($extra_data['mdfy_pay_time']) &&  $extra_data['mdfy_pay_time']>100 ? date("Y-m-d H:i:s",$extra_data['mdfy_pay_time']) : '--'];
        }
        $return['apply_list'][] = ['title' => '房间号', 'content' => $address];
        $return['apply_list'][] = ['title' => '业主姓名', 'content' => $yname];

        
        $apply_info['service_start_time'] = !empty($apply_info['service_start_time']) && $apply_info['service_start_time']>100 ? date('Y-m-d',$apply_info['service_start_time']) : '--';
        $apply_info['service_end_time'] = !empty($apply_info['service_end_time']) && $apply_info['service_end_time']>100 ? date('Y-m-d',$apply_info['service_end_time']) : '--';
        $apply_info['apply_time'] = !empty($apply_info['apply_time']) ? date('Y-m-d',$apply_info['apply_time']) : '';
        $apply_info['add_time'] = !empty($apply_info['add_time']) ? date('Y-m-d',$apply_info['add_time']) : '';
        $return['apply_info'] = $apply_info;

        // 审批流程
        $where_check = [];
        $where_check[] = ['d.apply_id','=',$apply_id];
        $check_log = $db_checkauth_detail->getApplyList($where_check,'w.name,w.avatar,d.status,d.apply_time,d.bak,d.apply_id');
        if(!empty($check_log)){
            foreach ($check_log as &$value){
                $value['list'] = [
                    ['title' => '审批人','content' => $value['name']],
                    ['title' => '审核状态','content' => empty($value['status']) ? '审核中' : (($value['status'] == 1) ? '审核通过' : '审核未通过')],
                    ['title' => '审核时间','content' => !empty($value['apply_time']) ? date("Y-m-d H:i:s",$value['apply_time']) : ''],
                    ['title' => '审核说明','content' => $value['bak']],
                ];
                $value['apply_time'] = !empty($value['apply_time']) ? date("Y-m-d H:i:s",$value['apply_time']) : '';
            }
        }
        $return['check_log'] = $check_log;
        return $return;
    }

    public function getDetailList($whereArr=array()){
        if(empty($whereArr)){
            return array();
        }
        $db_checkauth_detail = new HouseVillageCheckauthDetail();
        $check_log=$db_checkauth_detail->getDetailLists($whereArr);
        return $check_log;
    }

    /**
     * 物业管理人员账号查询关联的工作人员信息
     * User: zhanghan
     * Date: 2022/2/11
     * Time: 16:33
     * @param $uid
     * @param $village_id
     * @return mixed
     * @throws Exception
     */
    public function getWorkerId($uid){
        $db_house_admin = new HouseAdmin();
        $db_house_worker = new HouseWorker();

        $where_house_admin = [];
        $where_house_admin[] = ['id','=',$uid];
        // 查询物业管理人员表与工作人员的关系
        $info = $db_house_admin->getOne($where_house_admin,'wid,village_id,phone');
        if($info && !$info->isEmpty()){
            $info = $info->toArray();
            if(!empty($info['wid'])){
                $wid = $info['wid'];
            }else{
                $where_house_worker = [];
                $where_house_worker[] = ['phone','=',$info['phone']];
                $where_house_worker[] = ['village_id','=',$info['village_id']];
                // 查询物业管理人员表与工作人员的关系
                $infoWorker = $db_house_worker->getOne($where_house_worker,'wid');
                if($infoWorker && !$infoWorker->isEmpty()){
                    $infoWorker = $infoWorker->toArray();
                    $wid = $infoWorker['wid'];
                }else{
                    throw new Exception('参数错误，未查询到相关物业管理人员');
                }
            }
        }else{
            throw new Exception('参数错误，未查询到相关物业管理人员');
        }
        return $wid;
    }
}