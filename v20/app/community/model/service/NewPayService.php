<?php


namespace app\community\model\service;

use app\common\model\service\send_message\SmsService;
use app\common\model\service\UserService;
use app\common\model\service\ConfigService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\ConstructionOrderList;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\service\ConstructionPayService;
use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\Car\HouseVillageCarFlowTemp;
use app\community\model\db\HouseAbchinaSet;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewDepositFrozenLog;
use app\community\model\db\HouseNewDepositLog;
use app\community\model\db\HouseNewChargePrepaid;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseNewPileUserMoney;
use app\community\model\db\HouseNewPileUserMoneyLog;
use app\community\model\db\HouseNewSelectProjectRecord;
use app\community\model\db\HouseProperty;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageConfig;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageCheckauthApply;
use app\community\model\db\HouseVillageCheckauthDetail;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\InPark;
use app\community\model\db\ParkScrcuRecord;
use app\community\model\db\UserRechargeOrder;
use app\community\model\service\Car\CarFlowShowScreenService;
use app\community\model\service\Car\CarHandleGetInfoService;
use app\community\model\service\Car\HouseVillageCarFlowService;
use app\community\model\db\VillageUserMoneyList;
use app\community\model\db\VillageUserMoneyLog;
use app\community\model\service\Device\HouseFaceImgService;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageNmvCard;
use app\community\model\db\HouseVillageNmvCharge;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageParkingTemp;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePileEquipment;
use app\community\model\db\HouseVillagePilePayOrder;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\ParkOpenLog;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkPlateresultLog;
use app\community\model\db\ParkSystem;
use app\community\model\db\ParkTotalRecord;
use app\community\model\db\PlatOrder;
use app\community\model\db\User;
use app\community\model\service\Park\A11Service;
use app\community\model\service\Park\D5Service;
use app\community\model\service\Park\D6Service;
use app\community\model\service\Park\QinLinCloudService;
use app\pay\model\db\PayOrderInfo;
use app\community\model\db\HouseVillagePayConfig;
use app\community\model\db\VillageChinaumsDivideRecord;
use app\pay\model\service\PayService as PlatPayService;
use customization\customization;
use app\community\model\db\HouseVillageOrderTicketNumber;
use Dompdf\Exception;
use think\facade\Cache;
use app\pay\model\db\PayChannel;
use app\pay\model\service\PayService;
use app\community\model\service\PayService as CommunityPayService;
use app\community\model\service\HouseNewChargeProjectService;
use app\community\model\service\CustomizeMeterWaterReadingService;
use app\community\model\db\HouseWorker;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\HouseVillageParkGarageConfigService;
use app\community\model\service\HouseVillageUserBindService;
use app\common\model\db\PaidOrderRecord;
use app\pay\model\service\channel\ChinaumsService;
use think\facade\Db;
use app\traits\HouseVillageAfterPayTraits;
use app\community\model\service\HouseAppJpushInfoService;
use app\community\model\service\StorageService;
use function GuzzleHttp\Psr7\str;

class NewPayService
{
    use HouseVillageAfterPayTraits;
    use customization;
    /**
     * 收银台生成支付账单
     * @author lijie
     * @date_time 2021/06/25
     * @param array $order_list
     * @param int $village_id
     * @param int $pay_type
     * @param int $offline_pay_type
     * @param string $auth_code
     * @param string $remark
     * @return array|bool|int[]
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function goPay($order_list=[],$village_id=0,$pay_type=1,$offline_pay_type=0,$auth_code='',$remark='',$extra_data=array(),$role_id=0,$param=[])
    {
        fdump_api([$order_list,$village_id,$pay_type,$offline_pay_type,$auth_code,'param'=>$param],'workgoPay_0414',1);
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_parking = new HouseVillageParkingService();
        $service_house_new_cashier = new HouseNewCashierService();
        $db_house_new_deposit_log=new HouseNewDepositLog();
        $houseNewChargeProjectDb =new HouseNewChargeProject();
        $houseNewChargeNumberDb =new HouseNewChargeNumber();
        $db_house_village_info=new HouseVillageInfo();
        if(empty($order_list)){
            return false;
        }
        $isHang = isset($param['isHang']) ? intval($param['isHang']) : 0;
        $configCustomizationService=new ConfigCustomizationService();
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        $jz_wsxdsj_open = $configCustomizationService->getWSXCustomizedOpen();
        $func_kaidalaicn_special=$configCustomizationService->getfuncKaidalaicnSpecial();
        if($jz_wsxdsj_open){
            $is_grapefruit_prepaid=1;
        }
        $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
        $yuanjino2o_customized_func=$configCustomizationService->getYuanJino2oCustomized();
        $now_order = $order_list[0];
        if (!empty($now_order['room_id'])&&!empty($param['deposit_type'])){
            $where['village_id']=$village_id;
            $where['room_id']=$now_order['room_id'];
            $info=$db_house_new_deposit_log->get_one($where,'total_money,id');
            if ($info['total_money']!=$param['deposit_money']){
                throw new \think\Exception("押金总额有变化，请刷新页面，重新操作");
            }
        }

        if($pay_type == 3 && !empty($auth_code)){
            if($now_order['room_id']){
                
                if(Cache::get($now_order['room_id'].'_'.$now_order['project_id'])){
                    throw new \think\Exception("不要重复扫码");
                }else{
                    Cache::set($now_order['room_id'].'_'.$now_order['project_id'], $now_order, 5);
                }
            }else{
                if(Cache::get($now_order['position_id'].'_'.$now_order['project_id'])){
                    throw new \think\Exception("不要重复扫码");
                }else{
                    Cache::set($now_order['position_id'].'_'.$now_order['project_id'], $now_order, 5);
                }
            }
        }

        if (!empty($now_order['room_id'])&&!empty($param['deposit_type'])){
            $where['village_id']=$village_id;
            $where['room_id']=$now_order['room_id'];
            $info=$db_house_new_deposit_log->get_one($where,'total_money,id');
            if ($info['total_money']!=$param['deposit_money']){
                throw new \think\Exception("押金总额有变化，请刷新页面，重新操作");
            }
        }
        $is_only_have_electric=false;
        if($jz_wsxdsj_open && $pay_type==11){
            $construction_rule_id='';  //只能有一个账号
            $construction_rules=array();
            $first_opt=false;
            $is_only_have_electric=true;
            foreach ($order_list as $opkk=>$opvv){
                if(!in_array($opvv['order_type'],array('electricity_loss','electric'))){
                    $is_only_have_electric=false;
                }
                $whereProjectWhere=array('id'=>$opvv['project_id']);
                $newChargeProject=$houseNewChargeProjectDb->getOne($whereProjectWhere,'id,name,type,subject_id');
                /***电损服务费不算账号**/
                if($opvv['order_type']!='electricity_loss' && $newChargeProject && isset($newChargeProject['subject_id'])){
                    $chargeProjectName=$newChargeProject['name'];
                    $subject_id = $newChargeProject['subject_id'] ?$newChargeProject['subject_id']: 0;
                    if(!isset($construction_rules[$opvv['project_id']])){
                        $construction_rules[$opvv['project_id']]='';
                        $newChargeNumbweInfo = $houseNewChargeNumberDb->get_one(['id'=>$subject_id],'rule_id');
                        if($newChargeNumbweInfo &&  isset($newChargeNumbweInfo['rule_id']) && !empty($newChargeNumbweInfo['rule_id'])){
                            $construction_rules[$opvv['project_id']]=$newChargeNumbweInfo['rule_id'];
                        }
                    }
                    if(!$first_opt){
                        //第一次赋值
                        $construction_rule_id=$construction_rules[$opvv['project_id']];
                        $first_opt=true;
                    }else{
                        //不是第一次比较
                        if($construction_rule_id !=$construction_rules[$opvv['project_id']]){
                            throw new \think\Exception($chargeProjectName.'收费项目下订单和所选的其他收费项目下订单因建行收费账号不能同时缴费！');
                            exit();
                        }
                    }
                }
            }
            if(empty($construction_rule_id)){
                throw new \think\Exception("所选的所有收费项目下订单都未配置建行收费账号不能进行缴费！");
            }
        }
        $wait_pay_money=0;  //需要支付的钱
        if($param && isset($param['pay_money']) && ($param['pay_money']>0)){
            $wait_pay_money=$param['pay_money'];
        }
        $fact_pay_money=0; //本次缴费金额
        if($is_aihorse_customized>0 && $param && isset($param['fact_pay_money'])){
            $fact_pay_money=$param['fact_pay_money'];
            if($wait_pay_money>0 && $fact_pay_money<=0 && ($pay_type==42 || $pay_type==2)){
                throw new \think\Exception("请输入本次缴费金额。");
            }
        }

        $divide_order_arr=array();
        $uid=0;
        if($param && isset($param['uid'])){
            $uid=$param['uid'];
        }
        $fact_pay_money_score=0; //包括积分抵扣的钱
        $user_score_deducte=0; //使用积分抵扣的钱
        $score_used_count=0; //抵扣掉的积分
        $user_score_use_percent=0;
        if(isset($param['fact_pay_money']) && ($param['fact_pay_money']>0)){
            $fact_pay_money_score=$param['fact_pay_money'];
        }
        fdump_api([$yuanjino2o_customized_func,'uid'=>$uid,'pay_type'=>$pay_type,'wait_pay_money'=>$wait_pay_money,'param'=>$param],'workgoPay_0414',1);
        if($yuanjino2o_customized_func>0 && $uid>0 && $pay_type==2 && $param && isset($param['fact_pay_money']) && $param['fact_pay_money']<=$wait_pay_money){
            $fact_pay_money=$param['fact_pay_money'];
            $fact_pay_money_score=$fact_pay_money;
            if($param['is_use_score']>0){
                $service_user = new UserService();
                $userOneObj=$service_user->getOne(array('uid'=>$uid),'score_count');
                $score_count=0;
                if($userOneObj && isset($userOneObj['score_count']) && $userOneObj['score_count']){
                    $score_count=round($userOneObj['score_count'],2);
                }
                $user_score_info=$this->get_score_by_money($score_count,$fact_pay_money,$village_id);
                $user_score_max_use=$user_score_info ? $user_score_info['score_can_use']:0;
                $pay_money_and_score=$user_score_max_use+$fact_pay_money;
                $user_score_use_percent = cfg('user_score_use_percent');//抵扣1元所需积分量（必须设置）多少积分抵扣一元
                $user_score_use_percent=$user_score_use_percent>0 ? round($user_score_use_percent,2):0;
                if($pay_money_and_score>$wait_pay_money){
                    //积分抵扣足够多
                    $user_score_deducte=$wait_pay_money-$fact_pay_money;
                    $fact_pay_money_score=$wait_pay_money;
                    $user_score_info=$this->get_score_by_money($score_count,$user_score_deducte,$village_id);
                    $score_used_count=$user_score_info['score_can_use'];
                }else{
                    $user_score_deducte=$user_score_max_use;
                    $fact_pay_money_score=$pay_money_and_score;
                    $score_used_count=$score_count;
                }
            }
        }else if($yuanjino2o_customized_func>0 && $uid>0 && $pay_type==2 && $param && isset($param['fact_pay_money']) && $param['fact_pay_money']>$wait_pay_money){
            //多的钱充到支付者住户余额中去
            $fact_pay_money_score=$param['fact_pay_money'];
            $over_pay_money=$param['fact_pay_money']-$wait_pay_money;
            $over_pay_money=round($over_pay_money,2);
            $divide_order_arr['uid']=$uid;
            $divide_order_arr['add_village_balance']=$over_pay_money;
        }
        if( $pay_type==42 && $uid<=0){
            throw new \think\Exception("业主住户余额支付错误。");
        }
        if( $pay_type==42 && $uid>0 && $yuanjino2o_customized_func<1){
            $db_village_user_money_list=new VillageUserMoneyList();
            $user_moneyObj=$db_village_user_money_list->getOne(['uid'=>$uid,'business_id'=>$village_id,'business_type'=>1],'*');
            $user_money=array();
            if($user_moneyObj && !$user_moneyObj->isEmpty()){
                $user_money=$user_moneyObj->toArray();
            }
            if(empty($user_money) || $user_money['current_money']<=0 || ($user_money['current_money']<$fact_pay_money)){
                throw new \think\Exception("业主住户余额不足，支付错误！");
            }
            $divide_order_arr['uid']=$uid;
            $divide_order_arr['village_balance']=$user_money['current_money'];
        }
        $summaryData=array();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $summaryData['village_id'] = $village_id;
        $summaryData['property_id'] = $village_info['property_id'];
   
        $orderData=array();
        if($now_order['room_id']){
            $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$now_order['room_id']],['type','in',array(0,3)],['status','=',1]],'pigcms_id,name,phone,uid');
            if($user_info && is_object($user_info) && !$user_info->isEmpty()){
                $user_info=$user_info->toArray();
            }
            $summaryData['room_id'] = $now_order['room_id'];
            if(isset($now_order['position_id']) && $now_order['position_id']>0){
                $summaryData['position_id'] = $now_order['position_id'];
            }
        }else{
            $summaryData['position_id'] = $now_order['position_id'];
            /*
            $bind_position = $service_house_village_parking->getBindPosition(['position_id'=>$now_order['position_id']]);
            $user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$bind_position['user_id']],'pigcms_id,name,phone,uid');
            */       
            $user_info=$service_house_new_cashier->getRoomUserBindByPosition($now_order['position_id'],$village_id);
        }
        if($user_info && is_object($user_info) && !$user_info->isEmpty()) {
            $user_info = $user_info->toArray();
        }
        $oder_pay_type=$pay_type;  //9混合支付
        if($pay_type==42 ){
            /***PC收银台住户余额支付****/
            $oder_pay_type=4;
        }
        $summaryData['pay_uid'] = 0;
        $summaryData['uid']=0;
        $summaryData['pigcms_id']=0;
        $summaryData['pay_bind_id']=0;
        if(!empty($user_info)){
            $summaryData['uid'] = isset($user_info['uid'])?$user_info['uid']:0;
            $summaryData['pay_uid'] = isset($user_info['uid'])?$user_info['uid']:0;
            $summaryData['pigcms_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;
            $summaryData['pay_bind_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;

            $orderData['uid'] = $user_info['uid'];
            $orderData['pigcms_id'] = $user_info['pigcms_id'];
            $orderData['name'] = $user_info['name'];
            $orderData['phone'] = $user_info['phone'];
            $orderData['pay_bind_name'] = $user_info['name'];
            $orderData['pay_bind_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;
            $orderData['pay_bind_phone'] = $user_info['phone'];
        }
        if($pay_type==11 && $jz_wsxdsj_open){
            /***定制对接建行支付****/
            $oder_pay_type=1;
            $summaryData['order_pay_type']='constructionPay';
        }
        if($pay_type==9){
            //$oder_pay_type=4;
            $summaryData['online_pay_type']='off_mix_pay';
            $summaryData['order_pay_type']='off_mix_pay';
        }
        if(isset($divide_order_arr['uid']) && $divide_order_arr['uid']>0){
            if($summaryData['pay_uid']>0 && $divide_order_arr['uid']!=$summaryData['pay_uid']){
                throw new \think\Exception("业主住户余额支付错误，业主身份错误！");
            }
            $summaryData['pay_uid']=$divide_order_arr['uid'];
            $user_info_obj = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$now_order['room_id']],['type','in',array(0,1,2,3)],['status','in',array(0,1,2)],['uid','=',$divide_order_arr['uid']]],'pigcms_id,name,phone,uid');
            if($user_info_obj && !$user_info_obj->isEmpty()){
                $orderData['pay_bind_id']=$user_info_obj['pigcms_id'];
                $summaryData['pay_bind_id']=$user_info_obj['pigcms_id'];
                $orderData['pay_bind_name']=$user_info_obj['name'];
                $orderData['pay_bind_phone']=$user_info_obj['phone'];
            }else{
                $userService=new UserService();
                $userInfo=$userService->getOne(array('uid'=>$divide_order_arr['uid']));
                if($userInfo && $userInfo['uid']){
                    $orderData['pay_bind_name']=$userInfo['nickname'];
                    $orderData['pay_bind_phone']=$userInfo['phone'];
                } 
            }
        }
        $houseVillageOrderTicketNumberDb= new HouseVillageOrderTicketNumber();
        $summaryData['order_no'] =  build_real_orderid($summaryData['pay_uid']);
        $summaryData['is_paid'] = 2;
        $summaryData['pay_type'] = $oder_pay_type;
        $summaryData['offline_pay_type'] = $offline_pay_type;
        $summaryData['is_online'] = 0;
        $summaryData['remark'] = $remark;
        $summaryData['add_time'] = time();
        $summary_id = $service_house_new_cashier->addOrderSummary($summaryData);
       // fdump_api([$summary_id,$summaryData,$order_list],'workgoPay_0414',1);
        $total_money = 0;
        $pay_money = 0;
        $orderData['pay_type'] = $oder_pay_type;
        $orderData['offline_pay_type'] = $offline_pay_type;
        $orderData['summary_id'] = $summary_id;
        $orderData['is_paid'] = 2;
        $orderData['is_online'] = 0;
        $orderData['update_time'] = time();
        $orderData['remark'] = $remark; // 也同步到子订单

        if (!empty($role_id)){
            $orderData['role_id'] = $role_id;
        }

        if(!empty($extra_data) && is_array($extra_data)){
            $orderData['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
        }
        $db_house_property_digit_service = new HousePropertyDigitService();
        $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$village_info['property_id']]);
        if (isset($param['deposit_money'])){
            $deposit_money=$param['deposit_money'];
        }else{
            $deposit_money=0;
        }
        
        $rule_order_discount = array();
        $divide_order_id=0;
        $divide_order_id_pay_money=0;
        $summaryExtraDataOrders = [];
      
        foreach ($order_list as $value){
            $orderDataSave=$orderData;
            $total_money += $value['total_money'];
            $pay_money += $value['late_payment_money'] + $value['modify_money'];
            if ($is_grapefruit_prepaid == 1 || $func_kaidalaicn_special>0) {
                    if(isset($value['detail_order']) && !empty($value['detail_order'])){
                        foreach ($value['detail_order'] as $dv) {
                            $month_num=$dv['service_month_num'];
                            if(isset($dv['unify_flage_id']) && !empty($dv['unify_flage_id'])){
                                $oneChargeRule=$service_house_new_cashier->getOneChargeRule(array('id' => $dv['rule_id']),'id,charge_name,charge_valid_type');
                                $charge_valid_type=!empty($oneChargeRule)&&isset($oneChargeRule['charge_valid_type']) ? $oneChargeRule['charge_valid_type']:0;
                                if($charge_valid_type==3){
                                    $month_num=$month_num/12;
                                }
                            }
                            if (isset($rule_order_discount[$dv['rule_id']])) {
                                $rule_order_discount[$dv['rule_id']]['num'] += $month_num;
                                $rule_order_discount[$dv['rule_id']]['order_count']++;
                                $rule_order_discount[$dv['rule_id']]['money'] += ($dv['modify_money'] + $dv['late_payment_money']);
                                $rule_order_discount[$dv['rule_id']]['order_ids'][]=$dv['order_id'];
                            } else {
                                $rule_order_discount[$dv['rule_id']] = array();
                                $rule_order_discount[$dv['rule_id']]['rule_id'] = $dv['rule_id'];
                                $rule_order_discount[$dv['rule_id']]['order_count'] = 1;
                                $rule_order_discount[$dv['rule_id']]['num'] = $month_num;
                                $rule_order_discount[$dv['rule_id']]['money'] = ($dv['modify_money'] + $dv['late_payment_money']);
                                $rule_order_discount[$dv['rule_id']]['order_ids']= array($dv['order_id']);
                            }
                        }
                    }elseif(isset($value['rule_id'])){
                        $fmonth_num=$value['service_month_num'];
                        if(isset($value['unify_flage_id']) && !empty($value['unify_flage_id'])){
                            $oneChargeRule=$service_house_new_cashier->getOneChargeRule(array('id' => $value['rule_id']),'id,charge_name,charge_valid_type');
                            $charge_valid_type=!empty($oneChargeRule)&&isset($oneChargeRule['charge_valid_type']) ? $oneChargeRule['charge_valid_type']:0;
                            if($charge_valid_type==3){
                                $fmonth_num=$fmonth_num/12;
                            }
                        }
                        if (isset($rule_order_discount[$value['rule_id']])) {
                            $rule_order_discount[$value['rule_id']]['num'] += $fmonth_num;
                            $rule_order_discount[$value['rule_id']]['order_count']++;
                            $rule_order_discount[$value['rule_id']]['money'] += ($value['modify_money'] + $value['late_payment_money']);
                            $rule_order_discount[$value['rule_id']]['order_ids'][]=$value['order_id'];
                        } else {
                            $rule_order_discount[$value['rule_id']] = array();
                            $rule_order_discount[$value['rule_id']]['rule_id'] = $value['rule_id'];
                            $rule_order_discount[$value['rule_id']]['order_count'] = 1;
                            $rule_order_discount[$value['rule_id']]['num'] = $fmonth_num;
                            $rule_order_discount[$value['rule_id']]['money'] = ($value['modify_money'] + $value['late_payment_money']);
                            $rule_order_discount[$value['rule_id']]['order_ids']= array($value['order_id']);
                        }
                    }
            }
            $where = [];
            if(isset($value['order_id']) && ($value['order_id']>0) && isset($value['is_only_pay_this']) && ($value['is_only_pay_this']==1)){
                $where[] = ['order_id','=',$value['order_id']];
                $summaryExtraDataOrders[] = $value['order_id'];
            }else if(isset($value['detail_order']) && !empty($value['detail_order']) && is_array($value['detail_order'])){
                $order_idArr=array();
                foreach ($value['detail_order'] as $dvv){
                    $order_idArr[]=$dvv['order_id'];
                    $summaryExtraDataOrders[] = $dvv['order_id'];
                }
                $order_idArr=array_unique($order_idArr);
                if(!empty($order_idArr)){
                    $where[] = ['order_id','in',$order_idArr];
                }else{
                    if($value['room_id']){
                        $where[] = ['room_id','=',$value['room_id']];
                    }
                    if($value['position_id']>0){
                        $where[] = ['position_id','=',$value['position_id']];
                    }
                }
                
            }else {
                if (isset($value['order_id']) && $value['order_id']) {
                    $summaryExtraDataOrders[] = $value['order_id'];
                }
                if($value['room_id']){
                    $where[] = ['room_id','=',$value['room_id']];
                } 
                if($value['position_id']>0){
                    $where[] = ['position_id','=',$value['position_id']];
                }
            }
            $where[] = ['add_time','<=',time()];
            $where[] = ['project_id','=',$value['project_id']];
            $where[] = ['is_paid','=',2];
            $where[] = ['is_refund','=',1];
            if ($isHang == 1) {
                $where[] = ['is_discard','=',3];
                $where[] = ['hang_time','>',100];
            } else {
                $where[] = ['is_discard','=',1];
                $where[] = ['hang_time','<',100];
            }
            $where[] = ['check_status','<>',1];
            //押金解冻
            $frozen_data=['order_id'=>$value['order_id'],'type'=>3];
            $this->editFrozenlog($frozen_data);
            if (!empty($param)&&!empty($param['deposit_type'])&&$deposit_money>0){
                if ($deposit_money>$pay_money){
                    $orderDataSave['deposit_money'] = $pay_money;
                    $deposit_money=$deposit_money-$pay_money;
                }else{
                    $orderDataSave['deposit_money'] = $deposit_money;
                    $deposit_money=0;
                }
                //冻结押金
                $frozen_log_data=[
                    'order_id'=>$value['order_id'],
                    'deposit_money'=>$orderDataSave['deposit_money'],
                ];
                $this->addFrozenlog($frozen_log_data);
            }
            if ($is_grapefruit_prepaid == 1 || $func_kaidalaicn_special>0) {
                $orderDataSave['rate']=0;
                $orderDataSave['diy_type']=0;
                $orderDataSave['diy_content']='';
            }
            $orderDataSave['ticket_number']='';
            fdump_api(['where' => $where,'orderData' => $orderDataSave],'workgoPay_0414',1);
            if(isset($param['ticket_number']) && !empty($param['ticket_number'])){
                $orderDataSave['ticket_number']=$param['ticket_number'];
            }
            $service_house_new_cashier->saveOrder($where,$orderDataSave);

            if($pay_money>0 && $is_aihorse_customized>0 && $wait_pay_money>$fact_pay_money &&  ($pay_type==42 || $pay_type==2) &&  $fact_pay_money<=$pay_money){
                $divide_order_id=$value['order_id'];
                $divide_order_id_pay_money=$pay_money-$fact_pay_money;
                $divide_order_id_pay_money=round($divide_order_id_pay_money,2);
                $divide_order_arr['fact_pay_money']=$fact_pay_money;
                $divide_order_arr['pay_money']=$pay_money;
                $divide_order_arr['is_aihorse_customized']=$is_aihorse_customized;
                $divide_order_arr['divide_order_id']=$divide_order_id;
                $divide_order_arr['divide_order_id_pay_money']=$divide_order_id_pay_money; //此单未支付的钱
                $pay_money=$fact_pay_money;  //本次缴费的钱
                break;
            }else if($yuanjino2o_customized_func>0 && $pay_money>0 && $wait_pay_money>$fact_pay_money_score && $uid>0 && $pay_type==2 && $fact_pay_money_score<=$pay_money && !isset($divide_order_arr['add_village_balance'])){
                $divide_order_id=$value['order_id'];
                $divide_order_id_pay_money=$pay_money-$fact_pay_money_score;
                $divide_order_id_pay_money=round($divide_order_id_pay_money,2);
                $divide_order_arr['fact_pay_money']=$fact_pay_money;
                $divide_order_arr['fact_pay_money_score']=$fact_pay_money_score;
                $divide_order_arr['user_score_deducte']=$user_score_deducte;
                $divide_order_arr['score_used_count']=$score_used_count;
                $divide_order_arr['user_score_use_percent']=$user_score_use_percent;
                $divide_order_arr['pay_money']=$pay_money;
                $divide_order_arr['yuanjino2o_customized_func']=$yuanjino2o_customized_func;
                $divide_order_arr['divide_order_id']=$divide_order_id;
                $divide_order_arr['divide_order_id_pay_money']=$divide_order_id_pay_money; //此单未支付的钱
                $pay_money=$fact_pay_money;  //本次缴费的钱
                break;
            }
        }
        
        $discount_money=0;
        if ($pay_money>0 &&  !empty($rule_order_discount)) {
            foreach ($rule_order_discount as $kk => $rv) {
                $rv['num']=round($rv['num'],2);
                $rv['num']=floor($rv['num']);
                $rv['num']=$rv['num']*1;
                $discountArr = $service_house_new_cashier->getChargePrepaidDiscount($rv['rule_id'], $rv);
                $rule_order_discount[$kk]['optimum'] = $discountArr['optimum'];
                $rule_order_discount[$kk]['discount_money'] = $discountArr['discount_money'];
                $order_is_processed= isset($discountArr['order_is_processed']) ? $discountArr['order_is_processed']:0;
                if($order_is_processed<1 && !empty($discountArr['optimum']) && !empty($rv['order_ids'])){
                    $whereArrTmp=array(['order_id','in',$rv['order_ids']]);
                    
                    $orderDataTmp=array('rate'=>$discountArr['optimum']['rate'],'diy_type'=>1,'diy_content'=>$discountArr['discount_type_str']);
                    if(!empty($extra_data) && is_array($extra_data)){
                        $extra_data['optimum']=$discountArr['optimum'];
                    }else{
                        $extra_data=array();
                        $extra_data['optimum']=$discountArr['optimum'];
                    }
                    $orderDataTmp['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                    $discount_info=array('rule_discount'=>$discountArr['optimum']);
                    $orderDataTmp['discount_info']=json_encode($discount_info,JSON_UNESCAPED_UNICODE);
                    $service_house_new_cashier->saveOrder($whereArrTmp,$orderDataTmp);
                }elseif($order_is_processed<1 && !empty($rv['order_ids'])){
                    $whereArrTmp=array(['order_id','in',$rv['order_ids']]);
                    $orderDataTmp=array('rate'=>0,'diy_type'=>0,'diy_content'=>'','discount_info'=>'');
                    $service_house_new_cashier->saveOrder($whereArrTmp,$orderDataTmp);
                }
                $discount_money += $discountArr['discount_money'];
            }
            $pay_money=$pay_money-$discount_money;
        }

        if (!empty($param)&&!empty($param['deposit_type'])){
            if ($pay_money>$param['deposit_money']){
                $pay_money = $pay_money-$param['deposit_money'];
            }else{
                $pay_money=0;
            }

        }else{
            $param['deposit_money']=0;
        }
        $total_money = formatNumber($total_money,2,1);
        $pay_money = formatNumber($pay_money,2,1);
        
        $whereOrderSummary = ['summary_id' => $summary_id];
        $updateOrderSummary = ['deposit_money' => $param['deposit_money'], 'total_money' => $total_money, 'pay_money' => $pay_money, 'pay_amount_points' => $pay_money * 100];
        if (!empty($summaryExtraDataOrders)) {
            $summaryExtraDataOrders = array_unique($summaryExtraDataOrders);
            $summaryExtraData = ['newPayOrderId' => $summaryExtraDataOrders];
            $updateOrderSummary['extra_data'] = json_encode($summaryExtraData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if($oder_pay_type==9 && isset($param['offline_pay_money']) && $param['offline_pay_money']>0){
            $updateOrderSummary['offline_pay_money']=$param['offline_pay_money'];
            $pay_money=$pay_money-$param['offline_pay_money'];
            if($pay_money<0.01){
                throw new \think\Exception("剩余需要支付金额不能小于0！");
            }
        }
        $service_house_new_cashier->saveOrderSummary($whereOrderSummary, $updateOrderSummary);
        if( isset($param['ticket_number']) && !empty($param['ticket_number'])){
           $orderTicketNumberSave=array('summary_id'=>$summary_id,'update_time'=>time(),'ticket_status'=>1);
           if(!empty($summaryExtraDataOrders)){
               $orderTicketNumberSave['order_ids']= json_encode($summaryExtraDataOrders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
           }
           $orderTicketNumberWhere=array(['village_id','=',$village_id],['ticket_fill_number','=',$param['ticket_number']]);
           $orderTicketNumberWhere[]=array('ticket_status','<',1);
           $orderTicketNumberWhere[]=array('del_time','<',10);
           $oneOrderTicketNumber=$houseVillageOrderTicketNumberDb->getOneData($orderTicketNumberWhere,'*','id ASC');
           if($oneOrderTicketNumber && !$oneOrderTicketNumber->isEmpty()){
               $houseVillageOrderTicketNumberDb->saveData(array('id'=>$oneOrderTicketNumber['id']),$orderTicketNumberSave);
           }
        }
        $service_user = new UserService();
        $user = $service_user->getUser($summaryData['uid'],'uid');
        //if($user){
            //$score = $this->get_score($summary_id,$user,false); 支付前掉传的 传$summary_id 是不对的 必须传$plat_order_id
        //}
        if ($pay_money==0 || $isHang == 1){
            $res = $this->offlineAfterPay($summary_id);
        }else{
            if($pay_type == 2 || $pay_type == 22){
                $res = $this->offlineAfterPay($summary_id,1,0,$divide_order_arr);
            }else if($pay_type == 42){
                //住户余额
                $service_plat_order = new PlatOrderService();
                $plat_order = array(
                    'business_type' => 'village_new_pay',
                    'business_id' => $summary_id,
                    'order_name' => count($order_list)>1?'多缴费订单':$now_order['order_name'],
                    'uid' => $summaryData['pay_uid'],
                    'total_money' => $pay_money,
                    'wx_cheap' => 0,
                    'add_time'=>time(),
                );
                $order_no = date('YmdHis') .'000' .rand(1000000,9999999);
                $plat_order['pay_type'] = '';
                $plat_order['pay_money'] = 0;
                $plat_order['orderid'] = $order_no;
                $plat_order['third_id'] = '';
                $plat_order['pay_time'] = time();
                $plat_order['paid'] = 1;
                $plat_order['village_balance'] = $pay_money;
                $plat_id = $service_plat_order->addPlatOrder($plat_order);
                if ($plat_id>0){
                    $db_pay_order_info=new PayOrderInfo();
                    $tmp_pay_money=$pay_money*100;
                    $order_info_add = [
                        'business' 				        => 'village_new_pay',
                        'business_order_id' 	        => $plat_id,
                        'orderid' 				        => $order_no,
                        'addtime' 				        => date('Y-m-d H:i:s'),
                        'money' 				        => $tmp_pay_money,
                        'pay_type' 				        => '',
                        'env'					        => '',
                        'channel' 				        =>'',
                        'pay_config' 			        => array(),
                        'is_own'				        => 0,
                        'paid' 					        => 1,
                        'paid_money' 			        => 0,
                        'paid_time' 			        => time(),
                        'paid_extra' 			=> '',
                        'current_score_use'		        =>  0,
                        'current_score_deducte'	        =>  0,
                        'current_system_balance'        =>  0,
                        'current_village_balance'       =>  0,
                        'current_merchant_balance'	    =>  0,
                        'current_merchant_give_balance'	=> 0,
                        'current_qiye_balance'	=>0 ,
                        'current_employee_balance'	=> 0,
                        'current_employee_score_deducte'	=> 0,
                        'employee_card_user_id'	=> 0,
                        'rate' 					=> 1,
                        'source'=>'',
                        'chinaums_merchant_already_get' => 0,
                        'current_village_balance'=>$tmp_pay_money,

                    ];
                    $payOrderInfo=$db_pay_order_info->add($order_info_add);
                }
                $divide_order_arr['is_inner_direct']=1;
                $divide_order_arr['is_village_balance_pay']=1;
                $divide_order_arr['village_balance_pay_money']=$pay_money;
                $res = $this->offlineAfterPay($summary_id,1,0,$divide_order_arr);
               
            }
            if($pay_type == 3){
                $show_scrcu=cfg('show_scrcu');
                if (empty($show_scrcu)){
                    throw new \think\Exception("暂未对接扫码枪支付");
                }
                $service_pay = new PayService();

                $PayChannel=(new PayChannel)->getOne(['channel'=>'hqpay','switch'=>1]);


                if($PayChannel && !$PayChannel->isEmpty()){
                    $PayChannel= $PayChannel->toArray();
                }else{
                    $PayChannel= array();
                }
                $isscrcupay=0;
                //todo 是否开启环球支付
                if($PayChannel){
                    $params = $service_pay->getChannelParam($PayChannel['id']);
                    if(empty($params)){
                        throw new \think\Exception("请先配置环球汇通聚合支付参数！");
                    }
                }else{
                    //todo 四川农信支付
                    $service_community_pay = new CommunityPayService();
                    $params = $service_community_pay->getVillageScrcu($village_id);
                    $isscrcupay=1;
                }
                if($params || $isscrcupay==1){
                    $plat_order = array(
                        'business_type' => 'village_new_pay',
                        'business_id' => $summary_id,
                        'order_name' => count($order_list)>1?'多缴费订单':$now_order['order_name'],
                        'uid' => $summaryData['pay_uid'],
                        'total_money' => $pay_money,
                        'wx_cheap' => 0,
                        'add_time'=>time(),
                    );
                    $service_plat_order = new PlatOrderService();
                    $service_plat_pay = new PlatPayService(0,0,0,$village_id);
                    try {
                        $payResult = $service_plat_pay->scanPay($auth_code, $pay_money);
                    } catch (\Exception $e) {
                        if ($e->getMessage()=="支付通道未配置！"){
                            throw new \think\Exception("支付通道未配置！");
                        }else{
                            throw new \think\Exception($e->getMessage(), $e->getCode());
                        }

                    }
                    fdump_api([$payResult,$auth_code, $pay_money],'scanPay_1215',1);
                    if($payResult['status'] == 1){
                        $payOrderParam['pay_type'] = $payResult['pay_type'];
                        $payOrderParam['paid_orderid'] = $payResult['order_no'];
                        $payOrderParam['is_own'] = $payResult['is_own'];
                        $payOrderParam['pay_money'] = $pay_money;
                        $plat_order['pay_type'] = $payResult['pay_type'];
                        $plat_order['pay_money'] = $pay_money;
                        $plat_order['orderid'] = $payResult['order_no'];
                        $plat_order['third_id'] = $payResult['transaction_no'];
                        $plat_order['pay_time'] = time();
                        $plat_order['paid'] = 1;
                        fdump_api([$plat_order],'scanPay_1215',1);
                        $plat_id = $service_plat_order->addPlatOrder($plat_order);
                        $pay_order_info_id=0;
                        if ($plat_id>0){
                            $db_pay_order_info=new PayOrderInfo();
                            $payOrderInfo=$db_pay_order_info->getByOrderNo($payResult['order_no']);
                            if (!empty($payOrderInfo)){
                                $db_pay_order_info->updateById($payOrderInfo['id'],['business_order_id'=>$plat_id]) ;
                                $pay_order_info_id=$payOrderInfo['id'];
                            }
                        }
                        fdump_api([$plat_id,$plat_order],'scanPay_1215',1);
                        //支付成功
                        $other_data=array('pay_type_from'=>'scan_pay','pay_order_info_id'=>$pay_order_info_id);
                        $res = $this->offlineAfterPay($summary_id,1,$pay_order_info_id,$other_data);
                        fdump_api([$res,$summary_id,$payOrderParam],'scanPay_1215',1);
                        return ['status'=>1,'info'=>$payOrderParam];
                    }elseif($payResult['status'] == 2){
                        // 正在支付
                        $payOrderParam['pay_type'] = $payResult['pay_type'];
                        $payOrderParam['paid_orderid'] = $payResult['order_no'];
                        $payOrderParam['summary_id'] = $summary_id;
                        $plat_order['pay_money'] = $pay_money;
                        $plat_id = $service_plat_order->addPlatOrder($plat_order);
                        if ($plat_id>0){
                            $db_pay_order_info=new PayOrderInfo();
                            $payOrderInfo=$db_pay_order_info->getByOrderNo($payResult['order_no']);
                            if (!empty($payOrderInfo)){
                                $db_pay_order_info->updateById($payOrderInfo['id'],['business_order_id'=>$plat_id]) ;
                            }
                        }
                        if(empty($PayChannel) && $payResult['order_no']){
                            // todo 目前只对接了 环球和农信扫码支付（不能同时支持） 为空就是农信
                            $record = [
                                'order_number' => $payResult['order_no'],
                                'order_type' => 'pay_order_info',
                                'limit' => 0,
                                'over' => 0,
                                'add_time' => time(),
                            ];
                            Db::name('Scrcu_record')->insertGetId($record);//支付记录，保险起见做主动查询支付结果的。
                        }
                        return ['status'=>2,'info'=>$payOrderParam];
                    }else{
                        return ['status'=>0];
                    }
                }else{
                    return ['status'=>0];
                }
            }
            if($pay_type == 1){
                $thirdPayUrl = '';
                if($is_aihorse_customized){
                    $villageInfo = $db_house_village_info->getOne(['village_id'=>$village_id],'third_pay_url,third_ccb_pay_url');
                    $thirdPayUrl = isset($villageInfo['third_pay_url']) && $villageInfo['third_pay_url'] ? $villageInfo['third_pay_url'] : $thirdPayUrl;
                    $thirdPayUrl = isset($villageInfo['third_ccb_pay_url']) && $villageInfo['third_ccb_pay_url'] ? $villageInfo['third_ccb_pay_url'] : $thirdPayUrl;
                }
                if(customization('abchina') == 1){
                    $abChinaSet = (new HouseAbchinaSet())->where('property_id', $village_info['property_id'])->find();
                    if($abChinaSet && $abChinaSet['epay_code'] && $abChinaSet['merchant_id'] && $abChinaSet['merchant_pfx'] && $abChinaSet['trust_pay_cer'] && $abChinaSet['abchina_prikey'] && $abChinaSet['pay_url']){
                        $thirdPayUrl = $abChinaSet['pay_url'];
                    }
                }
                $service_recognition = new RecognitionService();
                if($thirdPayUrl){
                    $data = $service_recognition->getAbChinaQrcode($summary_id,$thirdPayUrl);
                }elseif (cfg('pay_farmersbankpay_open')){
                    $data = $service_recognition->getWxTmpQrcode($summary_id);
                }else{
                    $data = $service_recognition->getTmpQrcode($summary_id*1+300000000);
                }

                return $data;
            }
            
            if($pay_type == 11){
                $service_recognition = new RecognitionService();
                $paramStr='?village_id=' . $village_id.'&resource_from=constructionPayScan&summary_id='.$summary_id.'&pigcms_id='.$summaryData['pay_bind_id'].'&is_only_have_electric=';
                if($is_only_have_electric){
                    $paramStr .='1';
                }else{
                    $paramStr .='0';
                }
                $constructionPayUrl=cfg('site_url') .'/packapp/village/pages/houseMeter/NewCollectMoney/newLiveExpenses'. $paramStr;
                $qrcodeImg=$service_recognition->createQrcodeFlow($constructionPayUrl,'construction_pay_scan'.$summary_id.date('mdHis'));
                $qrcodeData=array('error_code'=>false,'ticket'=>'');
                if($qrcodeImg){
                    $qrcodeData['error_code']=true;
                    $qrcodeData['ticket']=$qrcodeImg;
                }
                return $qrcodeData;
            }
            if($pay_type == 9){
                //混合支付
                $plat_order = array(
                    'orderid' =>'',
                    'business_type' => 'village_new_pay',
                    'business_id' => $summary_id,
                    'order_name' => count($order_list)>1?'多缴费订单':$now_order['order_name'],
                    'uid' => $summaryData['pay_uid'],
                    'total_money' => $pay_money,
                    'wx_cheap' => 0,
                    'add_time'=>time(),
                );
                $plat_order['pay_type'] = '';
                $plat_order['pay_money'] = 0;
                $plat_order['third_id'] = '';
                $service_plat_order = new PlatOrderService();
                $service_recognition = new RecognitionService();
                $plat_id = $service_plat_order->addPlatOrder($plat_order);
                    $base_url = $service_house_village->base_url;
                    $pay_order_url=cfg('site_url') . $base_url . 'pages/pay/check?order_id=' . $plat_id . '&order_type=village_new_pay&resource_from=off_mix_pay';
                    $qrcodeImg=$service_recognition->createQrcodeFlow($pay_order_url,'mix_pay_scan'.$summary_id.date('mdHis'));
                    $qrcodeData=array('error_code'=>false,'summary_id'=>$summary_id,'room_id'=>$now_order['room_id'],'order_id'=>$now_order['order_id'],'ticket'=>'','pay_xtype'=>'off_mix_pay','pay_order_url'=>$pay_order_url);
                    if($qrcodeImg){
                        $qrcodeData['error_code']=true;
                        $qrcodeData['ticket']=$qrcodeImg;
                    }
                    return $qrcodeData;
            }
        }

        return true;
    }

    /**
     * 押金解冻
     * @author:zhubaodi
     * @date_time: 2022/6/13 10:14
     */
    public function editFrozenlog($data){
        $service_house_new_cashier = new HouseNewCashierService();
        $db_house_new_deposit_log=new HouseNewDepositLog();
        $db_house_new_deposit_frozen_log=new HouseNewDepositFrozenLog();
        $where=['order_id'=>$data['order_id']];
        $order_deposit_info=$service_house_new_cashier->getInfo($where,'order_id,deposit_money,room_id,summary_id,role_id,village_id');
        $res=0;
        if (!empty($order_deposit_info)&&$order_deposit_info['deposit_money']>0){
            $frozen_log=$db_house_new_deposit_frozen_log->get_one(['order_id'=>$order_deposit_info['order_id'],'type'=>1]);
            $deposit_log=$db_house_new_deposit_log->get_one(['room_id'=>$order_deposit_info['room_id']]);
            if (!empty($frozen_log)){
                $f_res=$db_house_new_deposit_frozen_log->save_one(['order_id'=>$order_deposit_info['order_id'],'type'=>1],['type'=>$data['type'],'desc'=>'订单取消支付']);
                if ($f_res>0&&$data['type']==3){
                    if (!empty($deposit_log)){
                       $total_money=$deposit_log['total_money'];
                    }else{
                        $total_money=0;
                    }
                    $deposit_money11=$order_deposit_info['deposit_money']+$total_money;
                    $log_data=[
                        'order_id'=>$order_deposit_info['order_id'],
                        'order_no'=>'',
                        'type'=>1,
                        'before_money'=>$total_money,
                        'money'=>$order_deposit_info['deposit_money'],
                        'total_money'=>$deposit_money11,
                        'role_id'=>$order_deposit_info['role_id'],
                        'room_id'=>$order_deposit_info['room_id'],
                        'village_id'=>$order_deposit_info['village_id'],
                        'add_time'=>time(),
                    ];
                    $res=$db_house_new_deposit_log->addOne($log_data);
                }elseif($f_res>0){
                    $res=1;
                }
            }
        }else{
            $res=1;
        }
        return $res;
    }

    /**冻结押金
     * @author:zhubaodi
     * @date_time: 2022/6/13 10:14
     */
    public function addFrozenlog($data){
        $service_house_new_cashier = new HouseNewCashierService();
        $db_house_new_deposit_log=new HouseNewDepositLog();
        $db_house_new_deposit_frozen_log=new HouseNewDepositFrozenLog();
        $where=['order_id'=>$data['order_id']];
        $order_deposit_info=$service_house_new_cashier->getInfo($where,'order_id,deposit_money,room_id,summary_id,role_id,village_id');
        $res=0;
      //  print_r([$order_deposit_info,$data['deposit_money']]);die;
        if (!empty($order_deposit_info)&&$data['deposit_money']>0){
            $order_summary_info=$service_house_new_cashier->getOrderSummary(['summary_id'=>$order_deposit_info['summary_id']],'summary_id,order_no');
            $frozen_log=$db_house_new_deposit_frozen_log->get_one(['order_id'=>$order_deposit_info['order_id'],'type'=>1]);
            $deposit_log=$db_house_new_deposit_log->get_one(['room_id'=>$order_deposit_info['room_id']]);
          //   print_r([$frozen_log,$deposit_log]);die;
            if (empty($frozen_log)){
                if (!empty($deposit_log)&&$deposit_log['total_money']>=$data['deposit_money']){
                    $deposit_money11=$deposit_log['total_money']-$data['deposit_money'];
                    $frozen_log_data=[
                        'order_id'=>$order_deposit_info['order_id'],
                        'type'=>1,
                        'before_money'=>$deposit_log['total_money'],
                        'money'=>$data['deposit_money'],
                        'total_money'=>$deposit_money11,
                        'role_id'=>$order_deposit_info['role_id'],
                        'room_id'=>$order_deposit_info['room_id'],
                        'village_id'=>$order_deposit_info['village_id'],
                        'desc'=>'订单发起支付，冻结押金',
                        'add_time'=>time(),
                    ];
                //     print_r($frozen_log_data);die;
                    $f_res=$db_house_new_deposit_frozen_log->addOne($frozen_log_data);
                    if ($f_res>0){
                            $log_data=[
                                'order_id'=>$order_deposit_info['order_id'],
                                'order_no'=>isset($order_summary_info['order_no'])?$order_summary_info['order_no']:'',
                                'type'=>2,
                                'before_money'=>$deposit_log['total_money'],
                                'money'=>$data['deposit_money'],
                                'total_money'=>$deposit_money11,
                                'role_id'=>$order_deposit_info['role_id'],
                                'room_id'=>$order_deposit_info['room_id'],
                                'village_id'=>$order_deposit_info['village_id'],
                                'add_time'=>time(),
                            ];
                        $res=$db_house_new_deposit_log->addOne($log_data);
                    }
                }

            }
        }
        return $res;
    }

    /**
     * 移动管理端收银台生成支付账单
     * @author lijie
     * @date_time 2021/07/08
     * @param array $order_list
     * @param int $village_id
     * @return bool|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function frontCashierGoPay($order_list=[],$village_id=0,$param=[])
    {
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_parking = new HouseVillageParkingService();
        $service_house_new_cashier = new HouseNewCashierService();
        $db_house_new_deposit_log=new HouseNewDepositLog();
        if(empty($order_list)){
            return false;
        }
        $houseNewChargeProjectDb =new HouseNewChargeProject();
        $houseNewChargeNumberDb =new HouseNewChargeNumber();
        $configCustomizationService=new ConfigCustomizationService();
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        $jz_wsxdsj_open = $configCustomizationService->getWSXCustomizedOpen();
        if($jz_wsxdsj_open){
            $is_grapefruit_prepaid=1;
        }
        $func_kaidalaicn_special=$configCustomizationService->getfuncKaidalaicnSpecial();
        $summaryData=array();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $summaryData['village_id'] = $village_id;
        $summaryData['property_id'] = $village_info['property_id'];
        $now_order = $order_list[0];
        if($now_order['village_id']!=$village_id){
            throw new \think\Exception("小区订单错误！");
        }
        if (!empty($now_order['room_id'])&&!empty($param['deposit_type'])){
            $where['village_id']=$village_id;
            $where['room_id']=$now_order['room_id'];
            $info=$db_house_new_deposit_log->get_one($where,'total_money,id');
            if ($info['total_money']!=$param['deposit_money']){
                throw new \think\Exception("押金总额有变化，请刷新页面，重新操作");
            }
        }
        $construction_pay_scan_err_msg='';
        if($jz_wsxdsj_open){
            $construction_rule_id='';  //只能有一个账号
            $construction_rules=array();
            $first_opt=false;
            foreach ($order_list as $opkk=>$opvv){
                $whereProjectWhere=array('id'=>$opvv['project_id']);
                $newChargeProject=$houseNewChargeProjectDb->getOne($whereProjectWhere,'id,name,type,subject_id');
                /***电损服务费不算账号**/
                if($opvv['order_type']!='electricity_loss' && $newChargeProject && isset($newChargeProject['subject_id'])){
                    $chargeProjectName=$newChargeProject['name'];
                    $subject_id = $newChargeProject['subject_id'] ?$newChargeProject['subject_id']: 0;
                    if(!isset($construction_rules[$opvv['project_id']])){
                        $construction_rules[$opvv['project_id']]='';
                        $newChargeNumbweInfo = $houseNewChargeNumberDb->get_one(['id'=>$subject_id],'rule_id');
                        if($newChargeNumbweInfo &&  isset($newChargeNumbweInfo['rule_id']) && !empty($newChargeNumbweInfo['rule_id'])){
                            $construction_rules[$opvv['project_id']]=$newChargeNumbweInfo['rule_id'];
                        }
                    }
                    if(!$first_opt){
                        //第一次赋值
                        $construction_rule_id=$construction_rules[$opvv['project_id']];
                        $first_opt=true;
                    }else{
                        //不是第一次比较
                        if($construction_rule_id !=$construction_rules[$opvv['project_id']]){
                            $construction_pay_scan_err_msg=$chargeProjectName.'收费项目下订单和所选的其他收费项目下订单因建行收费账号不能同时缴费！';
                        }
                    }
                }
            }
            if(empty($construction_rule_id)){
                $construction_pay_scan_err_msg="所选的所有收费项目下订单都未配置建行收费账号不能进行缴费！";
            }
        }
        
        if($now_order['room_id']){
            $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$now_order['room_id']],['type','in','0,3'],['status','=',1]],'pigcms_id,name,phone,uid');
            if($user_info && is_object($user_info) && !$user_info->isEmpty()){
                $user_info=$user_info->toArray();
            }
            $summaryData['room_id'] = $now_order['room_id'];
            if(isset($now_order['position_id']) && $now_order['position_id']>0){
                $summaryData['position_id'] = $now_order['position_id'];
            }
        }else{
            $summaryData['position_id'] = $now_order['position_id'];
            /*
            $bind_position = $service_house_village_parking->getBindPosition(['position_id'=>$now_order['position_id']]);
            $user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$bind_position['user_id']],'pigcms_id,uid,name,phone');
            */
            $user_info=$service_house_new_cashier->getRoomUserBindByPosition($now_order['position_id'],$village_id);
        }
        $orderData=array();
        $summaryData['pay_uid']=0;
        if(!empty($user_info)){
            $summaryData['uid'] = isset($user_info['uid'])?$user_info['uid']:0;
            $summaryData['pay_uid'] = isset($user_info['uid'])?$user_info['uid']:0;
            $summaryData['pigcms_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;
            $summaryData['pay_bind_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;
            $orderData['uid'] = isset($user_info['uid'])?$user_info['uid']:0;
            $orderData['pigcms_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;
            $orderData['pay_bind_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;
            $orderData['name'] = isset($user_info['name'])?$user_info['name']:0;
            $orderData['phone'] = isset($user_info['phone'])?$user_info['phone']:0;
            $orderData['pay_bind_name'] = isset($user_info['name'])?$user_info['name']:0;
            $orderData['pay_bind_phone'] = isset($user_info['phone'])?$user_info['phone']:0;
        }

        $summaryData['order_no'] =  build_real_orderid($summaryData['pay_uid']);
        $summaryData['is_paid'] = 2;
        $summary_id = $service_house_new_cashier->addOrderSummary($summaryData);
        $total_money = 0;
        $pay_money = 0;
        $orderData['summary_id'] = $summary_id;
        if(isset($param['role_id']) && $param['role_id']>0){
            $orderData['role_id'] = $param['role_id'];
        }
        $orderData['is_paid'] = 2;
        $deposit_money=$param['deposit_money'];
        $rule_order_discount = array();
        $newPayOrderId=array();
        $order_name_arr=array();
        foreach ($order_list as $value){
            if((!isset($value['need_pay_order_type']) || ($value['need_pay_order_type']!='front_cashier_hang_order')) && ($value['is_discard']==3 || (isset($value['hang_time']) && $value['hang_time']>100))){
                continue;
            }
            $order_name=isset($value['order_name']) ? $value['order_name']:'';
            if(empty($order_name) && isset($value['charge_name']) && $value['charge_name']){
                $order_name[]=$value['charge_name'];
            }
            $order_name_arr[]=$order_name;
            $total_money += $value['total_money'];
            $pay_money += getFormatNumber($value['late_payment_money'] + $value['modify_money']);
            if ($is_grapefruit_prepaid == 1 || $func_kaidalaicn_special>0) {
                if(isset($value['detail_order']) && !empty($value['detail_order'])){
                    foreach ($value['detail_order'] as $dv) {
                        $month_num=$dv['service_month_num'];
                        if(isset($dv['unify_flage_id']) && !empty($dv['unify_flage_id'])){
                            $oneChargeRule=$service_house_new_cashier->getOneChargeRule(array('id' => $dv['rule_id']),'id,charge_name,charge_valid_type');
                            $charge_valid_type=!empty($oneChargeRule)&&isset($oneChargeRule['charge_valid_type']) ? $oneChargeRule['charge_valid_type']:0;
                            if($charge_valid_type==3){
                                $month_num=$month_num/12;
                            }
                        }
                        if (isset($rule_order_discount[$dv['rule_id']])) {
                            $rule_order_discount[$dv['rule_id']]['num'] += $month_num;
                            $rule_order_discount[$dv['rule_id']]['order_count']++;
                            $rule_order_discount[$dv['rule_id']]['money'] += ($dv['modify_money'] + $dv['late_payment_money']);
                            $rule_order_discount[$dv['rule_id']]['order_ids'][]=$dv['order_id'];
                        } else {
                            $rule_order_discount[$dv['rule_id']] = array();
                            $rule_order_discount[$dv['rule_id']]['rule_id'] = $dv['rule_id'];
                            $rule_order_discount[$dv['rule_id']]['num'] = $month_num;
                            $rule_order_discount[$dv['rule_id']]['order_count'] = 1;
                            $rule_order_discount[$dv['rule_id']]['money'] = ($dv['modify_money'] + $dv['late_payment_money']);
                            $rule_order_discount[$dv['rule_id']]['order_ids']= array($dv['order_id']);
                        }
                    }
                }elseif(isset($value['rule_id'])){
                    $fmonth_num=$value['service_month_num'];
                    if(isset($value['unify_flage_id']) && !empty($value['unify_flage_id'])){
                        $oneChargeRule=$service_house_new_cashier->getOneChargeRule(array('id' => $value['rule_id']),'id,charge_name,charge_valid_type');
                        $charge_valid_type=!empty($oneChargeRule)&&isset($oneChargeRule['charge_valid_type']) ? $oneChargeRule['charge_valid_type']:0;
                        if($charge_valid_type==3){
                            $fmonth_num=$fmonth_num/12;
                        }
                    }
                    if (isset($rule_order_discount[$value['rule_id']])) {
                        $rule_order_discount[$value['rule_id']]['num'] += $fmonth_num;
                        $rule_order_discount[$value['rule_id']]['order_count']++;
                        $rule_order_discount[$value['rule_id']]['money'] += ($value['modify_money'] + $value['late_payment_money']);
                        $rule_order_discount[$value['rule_id']]['order_ids'][]=$value['order_id'];
                    } else {
                        $rule_order_discount[$value['rule_id']] = array();
                        $rule_order_discount[$value['rule_id']]['rule_id'] = $value['rule_id'];
                        $rule_order_discount[$value['rule_id']]['num'] = $fmonth_num;
                        $rule_order_discount[$value['rule_id']]['order_count'] = 1;
                        $rule_order_discount[$value['rule_id']]['money'] = ($value['modify_money'] + $value['late_payment_money']);
                        $rule_order_discount[$value['rule_id']]['order_ids']= array($value['order_id']);
                    }
                }
            }
            $order_ids=array();
            if(isset($value['detail_order']) && !empty($value['detail_order'])){
                foreach ($value['detail_order'] as $dv) {
                    $order_ids[]=$dv['order_id'];
                }
            }
            $where = [];
            if($value['room_id']){
                $where[] = ['room_id','=',$value['room_id']];
            }else if($value['position_id']){
                $where[] = ['position_id','=',$value['position_id']];
            }
            
            if(!empty($order_ids)){
                $where[] = ['order_id','in',$order_ids];
                $newPayOrderId[]=$order_ids;
            }else if(isset($value['order_id']) && $value['order_id']>0){
                $where[] = ['order_id','=',$value['order_id']];
                $newPayOrderId[]=$value['order_id'];
            }
            $where[] = ['add_time','<=',time()];
            $where[] = ['project_id','=',$value['project_id']];
            $where[] = ['is_paid','=',2];
            $where[] = ['is_refund','=',1];
            if(isset($value['need_pay_order_type']) && ($value['need_pay_order_type']=='front_cashier_hang_order')){
                $where[] = ['is_discard','=',3];
            }else{
                $where[] = ['is_discard','=',1];
            }
            //押金解冻
            $frozen_data=['order_id'=>$value['order_id'],'type'=>3];
            $this->editFrozenlog($frozen_data);
            if (!empty($param['deposit_type'])&&$deposit_money>0){
                if ($deposit_money>$pay_money){
                    $orderData['deposit_money'] = $pay_money;
                    $deposit_money=$deposit_money-$pay_money;
                }else{
                    $orderData['deposit_money'] = $deposit_money;
                    $deposit_money=0;
                }
                //冻结押金
                $frozen_log_data=[
                    'order_id'=>$value['order_id'],
                    'deposit_money'=>$orderData['deposit_money'],
                ];
                $this->addFrozenlog($frozen_log_data);
            }
            if ($is_grapefruit_prepaid == 1 || $func_kaidalaicn_special>0) {
                $orderData['rate']=0;
                $orderData['diy_type']=0;
                $orderData['diy_content']='';
            }
            
            $service_house_new_cashier->saveOrder($where,$orderData);
        }
        $discount_money=0;
        if ($pay_money>0 && !empty($rule_order_discount)) {
            foreach ($rule_order_discount as $kk => $rv) {
                $rv['num']=round($rv['num'],2);
                $rv['num']=floor($rv['num']);
                $rv['num']=$rv['num']*1;
                $discountArr = $service_house_new_cashier->getChargePrepaidDiscount($rv['rule_id'], $rv);
                $rule_order_discount[$kk]['optimum'] = $discountArr['optimum'];
                $rule_order_discount[$kk]['discount_money'] = $discountArr['discount_money'];
                $order_is_processed= isset($discountArr['order_is_processed']) ? $discountArr['order_is_processed']:0;
                if($order_is_processed<1 && !empty($discountArr['optimum']) && !empty($rv['order_ids'])){
                    $whereArrTmp=array(['order_id','in',$rv['order_ids']]);
                    $orderDataTmp=array('rate'=>$discountArr['optimum']['rate'],'diy_type'=>1,'diy_content'=>$discountArr['discount_type_str']);
                    $extra_data=array();
                    $extra_data['optimum']=$discountArr['optimum'];
                    $orderDataTmp['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                    $discount_info=array('rule_discount'=>$discountArr['optimum']);
                    $orderDataTmp['discount_info']=$discount_info;
                    $service_house_new_cashier->saveOrder($whereArrTmp,$orderDataTmp);
                }elseif($order_is_processed<1 && !empty($rv['order_ids'])){
                    $whereArrTmp=array(['order_id','in',$rv['order_ids']]);
                    $orderDataTmp=array('rate'=>0,'diy_type'=>0,'diy_content'=>'','discount_info'=>'');
                    $service_house_new_cashier->saveOrder($whereArrTmp,$orderDataTmp);
                }
                $discount_money += $discountArr['discount_money'];
            }
            $pay_money=$pay_money-$discount_money;
        }
        /*$plat_order = array(
            'business_type' => 'village_new_pay',
            'business_id' => $summary_id,
            'order_name' => $now_order['order_name'],
            'uid' => $now_order['uid'],
            'total_money' => $pay_money,
            'wx_cheap' => 0,
            'add_time'=>time(),
        );
        $service_plat_order = new PlatOrderService();
        $plat_id = $service_plat_order->addPlatOrder($plat_order);*/
        if (!empty($param['deposit_type'])){
            if ($pay_money>$param['deposit_money']){
                $pay_money = $pay_money-$param['deposit_money'];
            }else{
                $pay_money=0;
            }

        }else{
            $param['deposit_money']=0;
        }
        $total_money = formatNumber($total_money,2,1);
        $pay_money = $pay_money>0 ? formatNumber($pay_money,2,1) :0;

        $returnArr=['order_id'=>$summary_id,'summary_id'=>$summary_id,'total_money'=>sprintf('%.2f',$total_money ),'pay_money'=>sprintf('%.2f',$pay_money )];
        $returnArr['jz_wsxdsj_open']=0;
        $returnArr['constructionPayScanUrl']='';
        $returnArr['construction_pay_scan_err_msg']=$construction_pay_scan_err_msg;
        $returnArr['discount_money']=$discount_money;
        $extra_data=array('order_name'=>$order_name_arr);
        if($jz_wsxdsj_open){
            $service_recognition = new RecognitionService();
            $paramStr='?village_id=' . $village_id.'&resource_from=constructionPayScan&summary_id='.$summary_id.'&pigcms_id='.$summaryData['pay_bind_id'];
            $constructionPayUrl=cfg('site_url') .'/packapp/village/pages/houseMeter/NewCollectMoney/newLiveExpenses'. $paramStr;
            $qrcodeImg=$service_recognition->createQrcodeFlow($constructionPayUrl,'construction_pay_scan'.$summary_id.date('mdHis'));
            if($qrcodeImg){
                $returnArr['constructionPayScanUrl']=$qrcodeImg;
            }
            $returnArr['jz_wsxdsj_open']=1;
            $extra_data['construction_pay_scan']=array('jz_wsxdsj_open'=>1,'summary_id'=>$summary_id,'constructionPayScanUrl'=>$qrcodeImg,'construction_pay_scan_err_msg'=>$construction_pay_scan_err_msg);
        }
        if(!empty($newPayOrderId)){
            $extra_data['newPayOrderId']=$newPayOrderId;
        }
        $saveSummaryData=['deposit_money'=>$param['deposit_money'],'total_money'=>$total_money,'pay_money'=>$pay_money];
        if(!empty($extra_data)){
            $saveSummaryData['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        $service_house_new_cashier->saveOrderSummary(['summary_id'=>$summary_id],$saveSummaryData);

        $returnArr['is_have_offline_mix_pay']=0;
        $returnArr['is_have_order_ticket_number']=0;
        //if($this->hasCloudIntercom()) {
            $returnArr['is_have_offline_mix_pay'] = 1; //线下支付组合支付
            $returnArr['is_have_order_ticket_number'] = 1;
       // }
        $yuanjino2o_customized_func=$configCustomizationService->getYuanJino2oCustomized();
        if($yuanjino2o_customized_func){
            $returnArr['is_off_mix_pay']=1;
        }
        return $returnArr;
    }
    
    public function getOrderSummaryPayInfo($summary_id=0,$village_id=0){
        if($summary_id<1 || $village_id<1){
            throw new \think\Exception("订单ID错误！");
        }
        $configCustomizationService=new ConfigCustomizationService();
        $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $summary_order_info = $db_house_new_pay_order_summary->getOne(['summary_id'=>$summary_id],true);
        if($summary_order_info && !$summary_order_info->isEmpty()){
            $room_id=$summary_order_info['room_id'];
            $summary_order_info=$summary_order_info->toArray();
            $total_money=$summary_order_info['total_money'];
            $pay_money=$summary_order_info['pay_money'];
            $returnArr=['order_id'=>$summary_id,'summary_id'=>$summary_id,'total_money'=>sprintf('%.2f',$total_money ),'pay_money'=>sprintf('%.2f',$pay_money )];
            if(isset($summary_order_info['extra_data']) && $summary_order_info['extra_data']){
                $extra_data=json_decode($summary_order_info['extra_data'],true);
                if($extra_data && isset($extra_data['construction_pay_scan']) && !empty($extra_data['construction_pay_scan'])){
                    $construction_pay_scan_arr=$extra_data['construction_pay_scan'];
                    $site_url= cfg('site_url');
                    $returnArr['jz_wsxdsj_open']=$construction_pay_scan_arr['jz_wsxdsj_open'];
                    $returnArr['constructionPayScanUrl']=$construction_pay_scan_arr['constructionPayScanUrl'] ? $site_url.$construction_pay_scan_arr['constructionPayScanUrl'] :'';
                    $returnArr['construction_pay_scan_err_msg']=$construction_pay_scan_arr['construction_pay_scan_err_msg'];
                }
            }
            $returnArr['bind_pigcms_id']=0;
            $returnArr['uid']=0;
            $returnArr['name']='';
            $returnArr['phone']='';
            $returnArr['phone']='';
            $returnArr['village_balance']=0;
            $returnArr['is_village_balance_pay']=0;
            $returnArr['is_aihorse_customized']=0;
            $returnArr['is_have_offline_mix_pay']=0;
            $returnArr['is_have_order_ticket_number']=0;
            $returnArr['is_off_mix_pay']=0;
            if($is_aihorse_customized){
                $returnArr['is_aihorse_customized']=$is_aihorse_customized;
                $returnArr['is_village_balance_pay']=1;
            }
            //if($this->hasCloudIntercom()) {
                $returnArr['is_have_offline_mix_pay'] = 1; //线下支付组合支付
                $returnArr['is_have_order_ticket_number'] = 1;
            //}
            $yuanjino2o_customized_func=$configCustomizationService->getYuanJino2oCustomized();
            if($yuanjino2o_customized_func){
                $returnArr['is_off_mix_pay']=1;
            }
            $service_house_village_user_bind = new HouseVillageUserBindService();
            if($room_id>0){
                $bind_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$room_id],['type','in',[0,3]],['status','=',1]],'pigcms_id,uid,name,phone');
                if(empty($bind_info) || $bind_info->isEmpty()){
                    $bind_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$room_id],['type','in',[1,2]],['status','=',1]],'pigcms_id,uid,name,phone');
                }
                if($bind_info && !$bind_info->isEmpty()){
                    $bind_info=$bind_info->toArray();
                    $returnArr['bind_pigcms_id']=$bind_info['pigcms_id'];
                    $returnArr['uid']=$bind_info['uid'];
                    $returnArr['name']=$bind_info['name'];
                    $returnArr['phone']=$bind_info['phone'];
                    $db_village_user_money_list=new VillageUserMoneyList();
                    if($bind_info['uid']>0){
                        $user_moneyObj=$db_village_user_money_list->getOne(['uid'=>$bind_info['uid'],'business_id'=>$village_id,'business_type'=>1],'*');
                        $user_money=array();
                        if($user_moneyObj && !$user_moneyObj->isEmpty()){
                            $user_money=$user_moneyObj->toArray();
                            $returnArr['village_balance']=$user_money['current_money'];
                        }
                    }

                } 
            }

            return $returnArr;
        }
        throw new \think\Exception("订单信息不存在！");
    }
    
    /**
     * 线下支付回调
     * @author lijie
     * @date_time 2021/06/29
     * @param int $summary_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * pay_order_info_id 是pay_order_info表id
     */
    public function offlineAfterPay($summary_id=0,$is_other=1,$pay_order_info_id=0,$other_data=array())
    {
        fdump_api([__LINE__,'summary_id' => $summary_id,'is_other' => $is_other,'pay_order_info_id'=>$pay_order_info_id,'other_data'=>$other_data],'after_pay/offlineAfterPay',1);
        if (!$summary_id) {
            fdump_api([__LINE__, 'msg' => '参数传递出错'], 'after_pay/offlineAfterPay', 1);
            return array('error_code' => 1, 'msg' => '参数传递出错！');
        }
        //Db::startTrans();
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $village_nmv_card = new HouseVillageNmvCard();
        $summary_order_info = $db_house_new_pay_order_summary->getOne(['summary_id'=>$summary_id],true);
        if($summary_order_info['is_paid'] == 1 && $is_other){
            //Db::rollback();
            fdump_api([__LINE__, 'summary_order_info' => $summary_order_info && !is_array($summary_order_info) ? $summary_order_info->toArray() : $summary_order_info, 'msg' => '该订单已经支付'], 'after_pay/offlineAfterPay', 1);
            return array('error_code' => 1, 'msg' => '该订单已经支付！');
        }
        $nowtime=time();
        $save_summary_order=array('is_paid'=>1,'pay_time'=>$nowtime);
        if($other_data && isset($other_data['is_village_balance_pay']) && $other_data['is_village_balance_pay']>0){
            $save_summary_order['village_balance']=$summary_order_info['pay_money'];
        }
        $configCustomizationService= new ConfigCustomizationService();
        
        $score_deducte=0;  //积分抵扣的钱
        $score_used_count=0; //抵扣掉的积分
        if($other_data && isset($other_data['user_score_deducte']) && $other_data['user_score_deducte']>0){
            $save_summary_order['score_deducte']=$other_data['user_score_deducte'];
            $save_summary_order['score_used_count']=$other_data['score_used_count'];
            $save_summary_order['score_percent']=$other_data['user_score_use_percent'];
            $score_deducte=$other_data['user_score_deducte'];  //积分抵扣的钱
            $score_used_count=$other_data['score_used_count']; //抵扣掉的积分
            $getIntegralPool=$configCustomizationService->getIntegralPool();
            if($getIntegralPool){
                //积分池扣分，积分扣除规则：积分使用总和占积分总和的百分比，用百分比乘每个人的积分，最后计算总和，如果总和小于积分使用总和，由积分最高的人承担差值
                $use_result = $this->userIntegralPool($summary_order_info['room_id'],$other_data['score_used_count'],$summary_order_info['order_no'],0,$summary_order_info['pay_uid']);
            }else{
                $use_result = (new UserService())->userScore($summary_order_info['pay_uid'], $other_data['score_used_count'], L_("物业缴费 ，扣除X1 ，订单编号X2", array( "X1" => cfg('score_name'), "X2" => $summary_order_info['order_no'])));
            }
            if ($use_result && $use_result['error_code']) {
                return array('error_code'=>1,'msg'=>'积分扣除失败！'.$use_result['msg']);
            }
        }
        $over_paid_money=0; //多缴的钱
        if(!empty($other_data) && isset($other_data['add_village_balance']) && $other_data['add_village_balance']>0){
            $save_summary_order['over_paid_money']=$other_data['add_village_balance'];
            $over_paid_money=$save_summary_order['over_paid_money'];
        }
        $res = $db_house_new_pay_order_summary->saveOne(['summary_id'=>$summary_id],$save_summary_order);
        if(!$res && $is_other){
            //Db::rollback();
            fdump_api([__LINE__, 'summary_id' => $summary_id, 'save_summary_order' => $save_summary_order, 'msg' => '支付失败'], 'after_pay/offlineAfterPay', 1);
            return array('error_code'=>1,'msg'=>'支付失败！');
        }
        /*$service_plat_order = new PlatOrderService();
        $plat_order_info = $service_plat_order->getPlatOrder(['business_id'=>$summary_id,'business_type'=>'village_new_pay']);
        if (empty($plat_order_info)){
            return array('error_code'=>1,'msg'=>'订单信息错误！'); 
        }*/
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_list = $db_house_new_pay_order->getList(['o.summary_id'=>$summary_id],'o.*',0,0,'o.order_id ASC');
        $updateSummaryId = 0;
        try {
            if (empty($order_list)) {
                // 没有子订单 获取额外记录
                $extra_data = isset($summary_order_info['extra_data']) && $summary_order_info['extra_data'] ? json_decode($summary_order_info['extra_data'], true) : [];
                if (isset($extra_data['newPayOrderId']) && !empty($extra_data['newPayOrderId'])) {
                    $whereSummaryOrder = [];
                    $whereSummaryOrder[] = ['o.order_id', 'in', $extra_data['newPayOrderId']];
                    $order_list = $db_house_new_pay_order->getList($whereSummaryOrder, 'o.*', 0, 0, 'o.order_id ASC');
                    $updateSummaryId = $summary_id;
                }
                fdump_api([__LINE__, 'summary_id' => $summary_id, 'extra_data' => $extra_data, 'msg' => '没有子订单'], 'after_pay/offlineAfterPay', 1);
            }
        } catch (\Exception $e) {
            fdump_api(['大的错误line：' . __LINE__, 'line' => $e->getLine(), 'err' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile()], 'after_pay/offlineAfterPay', true);
        } catch (\Throwable $e) {
            fdump_api(['大的错误line：' . __LINE__, 'line' => $e->getLine(), 'err' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile()], 'after_pay/offlineAfterPay', true);
        }
        if (!$order_list) {
            //Db::rollback();
            fdump_api([__LINE__, 'summary_id' => $summary_id, 'msg' => '没有子订单'], 'after_pay/offlineAfterPay', 1);
            return array('error_code'=>1,'msg'=>'没有子订单！');
        }
        $is_old_park_position=false;
        $yuanJino2oCustomize=$configCustomizationService->getYuanJino2oCustomized();
        if($yuanJino2oCustomize){
            $is_old_park_position=true;
        }
        $order_list=$order_list->toArray();
        $village_id=$summary_order_info['village_id'];
        $messageDatas=array();
        $messageDatas['property_id']=$summary_order_info['property_id'];
        $messageDatas['pay_money']=round($summary_order_info['pay_money'],2);
        $messageDatas['pay_money']=$messageDatas['pay_money']*1;
        $messageDatas['type']=2;
        $messageDatas['order_id']=$summary_id;
        $messageDatas['summary_id']=$summary_id;
        $updateData = [];
        $updateData['is_paid'] = 1;
        $updateData['pay_time'] = time();
        $updateData['pay_type'] = $summary_order_info['pay_type'];
        $updateData['offline_pay_type'] = $summary_order_info['offline_pay_type'];
        $updateData['order_pay_type'] = $summary_order_info['order_pay_type'];
        $updateData['check_apply_id'] = 0;
        $updateData['update_time'] = time();
        if ($updateSummaryId > 0) {
            $updateData['summary_id'] = $updateSummaryId;
        }
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_parking = new HouseVillageParkingService();
        $service_house_new_cashier = new HouseNewCashierService();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $db_house_new_order_log = new HouseNewOrderLog();
        $db_house_new_deposit_log = new HouseNewDepositLog();

        $db_house_property_digit_service = new HousePropertyDigitService();
        $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$summary_order_info['property_id']]);
        fdump_api([__LINE__, 'summary_id' => $summary_id, 'order_list' => $order_list], 'after_pay/offlineAfterPay', 1);
        $countnum=0;
        
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
        $car_number='';
        $messageDatas['order_type'] = [];
        $offline_pay = (new NewBuildingService())->getHouseNewOfflinePayFindMethod($summary_order_info['property_id'],'id,name');
        $messageDatas['pay_type_str'] = (new NewBuildingService())->getOrderPayTypeMethod($summary_id,$offline_pay,$summary_order_info['village_balance']);
        fdump_api([__LINE__,'summary_id' => $summary_id,'pay_type_str'=>$messageDatas['pay_type_str']],'after_pay/offlineAfterPay',1);
        $save_paid_order_record=array('source_from'=>1);
        $save_paid_order_record['business_type']='village_new_pay';
        $save_paid_order_record['uid']=$summary_order_info['pay_uid'] ? $summary_order_info['pay_uid']:$summary_order_info['uid'];
        $save_paid_order_record['order_id']=$summary_id;
        $save_paid_order_record['order_no']=$summary_order_info['order_no'];
        $save_paid_order_record['table_name']='house_new_pay_order_summary';
        $save_paid_order_record['order_money']=$summary_order_info['pay_money'];
        $save_paid_order_record['is_own']=1;
        $save_paid_order_record['pay_type']='offline';
        $save_paid_order_record['pay_type_from']=$summary_order_info['offline_pay_type'];
        if($summary_order_info['village_balance']>=$summary_order_info['pay_money']){
            $save_paid_order_record['pay_type']='offline_balance';
            $save_paid_order_record['balance_money']=$summary_order_info['pay_money'];
            $save_paid_order_record['pay_type_from']='village_balance';
        }else if($summary_order_info['system_balance']>=$summary_order_info['pay_money']){
            $save_paid_order_record['pay_type']='offline_balance';
            $save_paid_order_record['balance_money']=$summary_order_info['pay_money'];
            $save_paid_order_record['pay_type_from']='system_balance';
        }else{
            $tmp_pay_money=$summary_order_info['pay_money']-$summary_order_info['village_balance']-$summary_order_info['system_balance'];
            $save_paid_order_record['pay_money']=$tmp_pay_money>0 ? $tmp_pay_money:0;
            $save_paid_order_record['balance_money']=$summary_order_info['village_balance']+$summary_order_info['system_balance'];
        }
        $save_paid_order_record['pay_time']=$nowtime;
        $save_paid_order_record['room_id']=$summary_order_info['room_id'];
        $save_paid_order_record['bind_user_id']=$summary_order_info['pigcms_id'] ? $summary_order_info['pigcms_id']:$summary_order_info['pay_bind_id'];
        $save_paid_order_record['property_id']=$summary_order_info['property_id'];
        $save_paid_order_record['village_id']=$village_id;
        $position_id=0;

        $house_type_arr=array();
        $sub_order_ids=array();
        $business_name='';
        $tmp_order_type='';
        $tmp_order_type_v='';
        $worksOrderPayOrderIds = [];
        $ticket_number='';
        $totle_score_deducte=0;
        $totle_score_used_count=0;
        $totle_over_paid_money=0;
        foreach ($order_list as $kky=>$v){
            $tmpUpdateData=$updateData;
            $isHang = false;
            $is_discard = isset($v['is_discard']) ? intval($v['is_discard']) : 0;
            $hang_time = isset($v['hang_time']) ? $v['hang_time'] : 0;
            if ($is_discard == 3 && $hang_time > 1) {
                $isHang = true;
            }
            $countnum++;
            if($village_id<1 && $v['village_id']>0){
                $village_id=$v['village_id'];
            }
            if($v['order_type']){
                $house_type_arr[]=$v['order_type'];
            }
            if(isset($v['phone']) && $v['phone']){
                $save_paid_order_record['u_phone']=$v['phone'];
            }
            if(isset($v['pay_bind_phone']) && $v['pay_bind_phone']){
                $save_paid_order_record['u_phone']=$v['pay_bind_phone'];
            }
            if(isset($v['name']) && $v['name']){
                $save_paid_order_record['u_name']=$v['name'];
            }
            if(isset($v['pay_bind_name']) && $v['pay_bind_name']){
                $save_paid_order_record['u_name']=$v['pay_bind_name'];
            }
            $business_name=$v['order_name'];
            $sub_order_ids[]=$v['order_id'];
            if($v['position_id']>0){
                $position_id=$v['position_id'];
            }
            if(isset($v['ticket_number']) && $v['ticket_number']){
                $ticket_number=$v['ticket_number'];
            }
            $tmpUpdateData['pay_money'] = $v['modify_money'] + $v['late_payment_money'];
            if (!empty($other_data) && isset($other_data['divide_order_id']) && ($other_data['divide_order_id'] == $v['order_id']) && ($other_data['divide_order_id_pay_money'] > 0)) {
                $old_pay_money= round($tmpUpdateData['pay_money'], 2);
                $pay_money_tmp = $tmpUpdateData['pay_money'] - $other_data['divide_order_id_pay_money']; //算出这个单子已支付的钱
                $pay_money_tmp = round($pay_money_tmp, 2);
                $tmpUpdateData['pay_money'] = $pay_money_tmp;
                $tmpUpdateData['total_money'] = $pay_money_tmp;
                $tmpUpdateData['modify_money'] = $pay_money_tmp;
                $tmpUpdateData['modify_reason'] = '订单金额：'.$old_pay_money.'，支付其中一部分';
                $tmpUpdateData['modify_time'] = $nowtime;
                $tmpUpdateData['remark']='订单金额为：'.$old_pay_money.'，支付其中'.$pay_money_tmp.'剩余金额生成新订单';
                $tmp_divide_pay_order = $db_house_new_pay_order->get_one(array('order_id' => $v['order_id']));
                if ($tmp_divide_pay_order && !$tmp_divide_pay_order->isEmpty()) {
                    $tmp_divide_pay_order = $tmp_divide_pay_order->toArray();
                    if (isset($tmp_divide_pay_order['f_order_id']) && $tmp_divide_pay_order['f_order_id'] < 1) {
                        $tmp_divide_pay_order['f_order_id'] = $v['order_id'];
                    }
                    $extra_data_tmp = $tmp_divide_pay_order['extra_data'] ? json_decode($tmp_divide_pay_order['extra_data'], true) : array();
                    if ($extra_data_tmp && isset($extra_data_tmp['f_order_ids'])) {
                        $extra_data_tmp['f_order_ids'][] = $v['order_id'];
                    } elseif (empty($extra_data_tmp) && !isset($extra_data_tmp['f_order_ids'])) {
                        $extra_data_tmp['f_order_ids']=array();
                        if (isset($tmp_divide_pay_order['f_order_id']) && $tmp_divide_pay_order['f_order_id'] > 1) {
                            $extra_data_tmp['f_order_ids'][] = $tmp_divide_pay_order['f_order_id'];
                        }
                        $extra_data_tmp['f_order_ids'][] = $v['order_id'];
                    }
                    unset($tmp_divide_pay_order['order_id']);
                    $tmp_divide_pay_order['extra_data']=json_encode($extra_data_tmp,JSON_UNESCAPED_UNICODE);
                    $tmp_divide_pay_order['total_money']=$other_data['divide_order_id_pay_money'];
                    $tmp_divide_pay_order['modify_money']=$other_data['divide_order_id_pay_money'];
                    $tmp_divide_pay_order['summary_id']=0;
                    $tmp_divide_pay_order['is_paid']=2;
                    $tmp_divide_pay_order['pay_time']=0;
                    $tmp_divide_pay_order['add_time']=time();
                    $tmp_divide_pay_order['update_time']=0;
                    $tmp_divide_pay_order['remark']='订单ID'.$v['order_id'].'未支付金额部分生成新的订单';
                    $db_house_new_pay_order->addOne($tmp_divide_pay_order);
                }
            }

            if($score_deducte>0){
                if (isset($order_list[$kky+1])){
                    //实际积分抵扣的金额
                    $score_deducte_tmp=round_number($score_deducte*($tmpUpdateData['pay_money']/$summary_order_info['pay_money']),2);
                    $totle_score_deducte=$totle_score_deducte+$score_deducte_tmp;
                    //积分抵扣的数量
                    $score_used_count_tmp=$score_used_count*($tmpUpdateData['pay_money']/$summary_order_info['pay_money']);
                    $score_used_count_tmp=round($score_used_count_tmp,2);
                    $totle_score_used_count=$totle_score_used_count+$score_used_count_tmp;
                }else{
                    //实际积分抵扣的金额
                    $score_deducte_tmp=round_number(($score_deducte-$totle_score_deducte),2);
                    $score_deducte_tmp=$score_deducte_tmp>0 ? $score_deducte_tmp:0;
                    //积分抵扣的数量
                    $score_used_count_tmp=$score_used_count-$totle_score_used_count;
                    $score_used_count_tmp=round($score_used_count_tmp,2);
                    $score_used_count_tmp=$score_used_count_tmp>0 ? $score_used_count_tmp:0;
                }
                $tmpUpdateData['score_deducte']=$score_deducte_tmp;
                $tmpUpdateData['score_used_count']=$score_used_count_tmp;
            }
            if($over_paid_money>0){
                if (isset($order_list[$kky+1])){
                    //多缴钱平摊
                    $over_paid_money_tmp=round_number($over_paid_money*($tmpUpdateData['pay_money']/$summary_order_info['pay_money']),2);
                    $totle_over_paid_money=$totle_over_paid_money+$over_paid_money_tmp;
                }else{
                    //实际积分抵扣的金额
                    $over_paid_money_tmp=round_number(($over_paid_money-$totle_over_paid_money),2);
                    $over_paid_money_tmp=$over_paid_money_tmp>0 ? $over_paid_money_tmp:0;
                }
                $tmpUpdateData['over_paid_money']=$over_paid_money_tmp;
            }
            if ($v['deposit_money']>0){
                $deposit_info=$db_house_new_deposit_log->get_one(['order_id'=>$v['order_id'],'type'=>2]);
                if (empty($deposit_info)){
                    $deposit_info1=$db_house_new_deposit_log->get_one(['room_id'=>$v['room_id']]);
                    if ($deposit_info1['total_money']>$v['deposit_money']){
                        $deposit_money11= $deposit_info1['total_money']-$v['deposit_money'];
                    }else{
                        $deposit_money11=0;
                    }
                    $db_house_new_deposit_log->addOne([
                        'order_id'=>$v['order_id'],
                        'order_no'=>$summary_order_info['order_no'],
                        'type'=>2,
                        'before_money'=>$deposit_info1['total_money'],
                        'money'=>$v['deposit_money'],
                        'total_money'=>$deposit_money11,
                        'role_id'=>$v['role_id'],
                        'room_id'=>$v['room_id'],
                        'village_id'=>$v['village_id'],
                        'add_time'=>time(),
                    ]);
                    //押金解冻
                    $frozen_data=['order_id'=>$v['order_id'],'type'=>2];
                    $this->editFrozenlog($frozen_data);
                }
                if ($tmpUpdateData['pay_money']>$v['deposit_money']){
                    $tmpUpdateData['pay_money']=$tmpUpdateData['pay_money']-$v['deposit_money'];
                }else{
                    $tmpUpdateData['pay_money']=0;
                }
            }
            if ($summary_order_info['pay_type']!=2&& $summary_order_info['pay_type']!=22){
                $tmpUpdateData['pay_amount_points'] = $tmpUpdateData['pay_money']*100;
            }
            if($other_data && isset($other_data['is_village_balance_pay']) && $other_data['is_village_balance_pay']>0){
                $tmpUpdateData['pay_amount_points']=0;
            }
            $service_end_time = $v['service_end_time'];
            $service_start_time = $v['service_start_time'];
            $tmpUpdateData['service_start_time']=$v['service_start_time'];
            $tmpUpdateData['service_end_time']=$v['service_end_time'];
            $service_time_difference=$v['service_end_time']-$v['service_start_time'];
            $service_time_difference=$service_time_difference>0 ?$service_time_difference:0;
            if($v['order_type'] == 'non_motor_vehicle'){ // 非机动车缴费
                // 更新非机动车卡到期时间
                $tmp_order_type='non_motor_vehicle';
                $village_nmv_card->updateCardInfo(['id' => $v['position_id']],['expiration_time' => $service_end_time]);
                $send_nmv_card = new CloudIntercomService();
                $send_nmv_card->sendNmvToDevice($v['position_id']);
            }
            $rule_info = $service_house_new_charge_rule->getCallInfo(['r.id'=>$v['rule_id']],'n.charge_type,r.*,p.type');
            if($v['order_type'] != 'non_motor_vehicle' && ($rule_info['type'] == 2||$v['order_type'] == 'park_new')){
                if($v['position_id']){
                    $last_order = $db_house_new_order_log->getOne([['position_id','=',$v['position_id']],['order_type','=',$v['order_type']],['project_id','=',$v['project_id']]],true,'id DESC');
                } else{
                    $last_order = $db_house_new_order_log->getOne([['room_id','=',$v['room_id']],['order_type','=',$v['order_type']],['position_id','=',0],['project_id','=',$v['project_id']]],true,'id DESC');
                }
                // 兼容老版停车收费
                if($v['order_type'] == 'park'){
                    if($v['position_id']){
                        $where22=[
                            ['position_id','=',$v['position_id']],
                            ['order_type','=',$v['order_type']],
                            ['project_id','=',0],
                        ];
                    }else{
                        $where22=[
                            ['room_id','=',$v['room_id']],
                            ['order_type','=',$v['order_type']],
                        ];
                    }
                    $new_order_log = $db_house_new_order_log->getOne($where22,true,'id DESC');
                    if(!empty($new_order_log)){
                        $last_order=$new_order_log;
                    }
                    fdump_api(['线下支付house_new_order_log表数据=='.__LINE__,($v['position_id'] ? '车场' : '房产'),$new_order_log],'park/xia_log',1);
                }elseif ($v['order_type'] == 'property'){
                    if($v['position_id']){
                        $where22=[
                            ['position_id','=',$v['position_id']],
                            ['order_type','=',$v['order_type']],
                        ];
                    }else{
                        $where22=[
                            ['room_id','=',$v['room_id']],
                            ['position_id','=',0],
                            ['order_type','=',$v['order_type']],
                        ];
                    }
                    $new_order_log = $db_house_new_order_log->getOne($where22,true,'id DESC');
                    if(!empty($new_order_log)){
                        $last_order=$new_order_log;
                    }
                    fdump_api(['线上支付house_new_order_log表数据=='.__LINE__,($v['position_id'] ? '车场' : '房产')],'park/shang_log',1);
                }
                if($last_order && $v['order_type'] != 'park_new' && !$isHang){
                    $last_order = $last_order->toArray();
                    if($last_order && $last_order['service_end_time']>100){
                        $tmpUpdateData['service_start_time'] = $last_order['service_end_time']+100;
                        $tmpUpdateData['service_start_time'] = strtotime(date('Y-m-d',$tmpUpdateData['service_start_time']));
                        $service_start_time = $tmpUpdateData['service_start_time'];
                        if($v['is_prepare'] == 1){
                            $cycle = $v['service_give_month_num'] + $v['service_month_num'];
                        }else{
                            $cycle = $v['service_month_num']?$v['service_month_num']:1;
                        }
                        if($rule_info['bill_create_set'] == 1){
                            $tmpUpdateData['service_end_time'] = strtotime("+".$cycle." day",$tmpUpdateData['service_start_time'])-1;
                        }elseif(isset($v['unify_flage_id']) && !empty($v['unify_flage_id'])){
                            $tmpUpdateData['service_end_time'] =strtotime("+1 month",$tmpUpdateData['service_start_time'])-1;
                        }elseif ($rule_info['bill_create_set'] == 2){
                            //todo 判断是不是按照自然月来生成订单
                            if(cfg('open_natural_month') == 1){
                                $tmpUpdateData['service_end_time'] = strtotime("+".$cycle." month",$tmpUpdateData['service_start_time'])-1;
                            }else{
                                $cycle = $cycle*30;
                                $tmpUpdateData['service_end_time'] = strtotime("+".$cycle." day",$tmpUpdateData['service_start_time'])-1;
                            }
                            fdump_api(['线下支付【账单生成周期设置 1：按日生成2：按月生成3：按年生成】 当前是'.$rule_info['bill_create_set'].'=='.__LINE__,(cfg('open_natural_month') == 1 ? '开启自然月' : '未开启自然月'),'天数：'.$cycle,'service_end_time：'.date('Y-m-d H:i:s',$tmpUpdateData['service_end_time'])],'park/xia_log',1);
                        }else{
                            $tmpUpdateData['service_end_time'] = strtotime("+".$cycle." year",$tmpUpdateData['service_start_time'])-1;
                        }
                        if($v['service_time_handle']==5){
                            $tmpUpdateData['service_end_time']=$tmpUpdateData['service_start_time']+$service_time_difference;
                        }
                        $service_end_time = $tmpUpdateData['service_end_time'];
                    }else{
                        $tmpUpdateData['service_start_time'] = $service_start_time;
                        $tmpUpdateData['service_end_time'] = $service_end_time;
                    }
                }else{
                    $tmpUpdateData['service_start_time'] = $service_start_time;
                    $tmpUpdateData['service_end_time'] = $service_end_time;
                }
                if(isset($v['f_order_id']) && $v['f_order_id']>0 && (!isset($v['service_time_handle']) || ($v['service_time_handle']!=1))){
                    $tmpUpdateData['service_start_time'] = $v['service_start_time'];
                    $tmpUpdateData['service_end_time'] = $v['service_end_time'];
                }else if($v['order_type'] != 'park_new' && !$isHang){
                    $db_house_new_order_log->addOne([
                        'order_id'=>$v['order_id'],
                        'project_id'=>$v['project_id'],
                        'order_type'=>$v['order_type'],
                        'order_name'=>$v['order_name'],
                        'room_id'=>$v['room_id'],
                        'position_id'=>$v['position_id'],
                        'property_id'=>$v['property_id'],
                        'village_id'=>$v['village_id'],
                        'service_start_time'=>$service_start_time,
                        'service_end_time'=>$service_end_time,
                        'add_time'=>time(),
                    ]);
                }
                if($v['order_type'] == 'property'){
                    $service_house_village_user_bind->saveUserBind([
                        ['vacancy_id','=',$v['room_id']],
                        ['type','in',[0,3]],
                        ['status','=',1],
                    ],['property_endtime'=>$service_end_time]);
                }
                 if($v['order_type'] == 'park_new'){
                     $car_number=$v['car_number'] ? $v['car_number']:'';
                     $tmp_order_type=$v['car_type'] ? $v['car_type']:'';
                     $tmp_order_type_v=$car_number;
                    $db_House_new_parking=new HouseNewParkingService();
                    $db_house_village_park_config=new HouseVillageParkConfig();
                    $db_house_village_parking_car=new HouseVillageParkingCar();
                    $db_house_village_parking_position=new HouseVillageParkingPosition();
                    $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
                    $db_park_passage=new ParkPassage();
                    $db_house_village=new HouseVillage();
                    $db_park_system=new ParkSystem();
                    $db_park_total_record=new ParkTotalRecord();
                    $service_house_new_charge_prepaid=new HouseNewChargePrepaid();
                    $house_village =$db_house_village->getOne($v['village_id'],'village_name');
                    $park_system =$db_park_system->getFind(['park_id'=>$v['village_id']]);
                    fdump_api([$v,$house_village,$park_system],'children_position_log_0803',1);
                    //月租车缴费
                     if ($v['car_type']=='month_type') {
                         $prepaid_info = $service_house_new_charge_prepaid->getList(['charge_rule_id' => $v['rule_id']]);

                        $parking_position = array();
                        $parking_car = array();
                        $garage_id = 0;
                        $position_id=0;
                         if ($v['position_id'] > 0) {
                            $parking_position_obj = $db_house_village_parking_position->getFind(['position_id' => $v['position_id']]);
                            if ($parking_position_obj && !$parking_position_obj->isEmpty()) {
                                $parking_position = $parking_position_obj->toArray();
                            }
                            $position_id=$v['position_id'];
                        } elseif ($v['car_id'] > 0) {
                            $whereArr = array(['car_id', '=', $v['car_id']], ['village_id', '=', $v['village_id']]);
                            $parking_car_obj = $db_house_village_parking_car->getNewList($whereArr, '*', 0, 20, 'car_id DESC');
                            $car_position_id = 0;
                            if ($parking_car_obj && !$parking_car_obj->isEmpty()) {
                                $parking_car = $parking_car_obj->toArray();
                                $garage_id = isset($parking_car['0']['garage_id']) && $parking_car['0']['garage_id'] > 0 ? $parking_car['0']['garage_id'] : 0;
                                $car_position_id = isset($parking_car['0']['car_position_id']) && $parking_car['0']['car_position_id'] > 0 ? $parking_car['0']['car_position_id'] : 0;
                            }
                            if ($car_position_id > 0) {
                                $position_id=$car_position_id;
                                $parking_position_obj = $db_house_village_parking_position->getFind(['position_id' => $car_position_id]);
                                if ($parking_position_obj && !$parking_position_obj->isEmpty()) {
                                    $parking_position = $parking_position_obj->toArray();
                                }
                            }
                        }
                        if (!empty($parking_position)) {
                            $position_id=$parking_position['position_id'];
                            $whereArr = array(['village_id', '=', $v['village_id']]);
                            $whereRaw = '(car_position_id =' . $parking_position['position_id'] . ' OR FIND_IN_SET(' . $parking_position['position_id'] . ',bind_position_ids))';
                            $parking_car_obj = $db_house_village_parking_car->getNewList($whereArr, '*', 0, 20, 'car_id DESC', $whereRaw);
                            if ($parking_car_obj && !$parking_car_obj->isEmpty()) {
                                $parking_car = $parking_car_obj->toArray();
                            }
                            $garage_id = $parking_position['garage_id'] > 0 ? $parking_position['garage_id'] : $garage_id;
                        }

                        $channel_number_arr = $db_park_passage->getColumn(['village_id' => $v['village_id']], 'channel_number');
                        $channel_number_str = implode(',', $channel_number_arr);
                        fdump_api([$prepaid_info, $parking_position, $parking_car, $channel_number_arr, $channel_number_str], 'children_position_log_0803', 1);
                        if (!$channel_number_str) {
                            $channel_number_str = '';
                        }
                        $park_position_set = array();
                        $park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $v['village_id']);
                        $park_sys_type = $park_config['park_sys_type'];
                        if (2 != $parking_position['position_pattern']) {
                            // 固定车位-共同使用一个月卡会员编号
                            if ($parking_position['card_id']) {
                                $now_car_id = $parking_position['card_id'];
                            } else {
                                $now_car_id = '1' . sprintf("%09d", $parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                                $park_position_set['card_id'] = $now_car_id;
                            }
                            $address = $house_village['village_name'] . '车位：' . $parking_position['position_num'];
                        } else {
                            $address = $house_village['village_name'];
                        }
                        $dateStr = date('Y-m-d') . ' 00:00:00';
                        $tmp_parking_end_time = strtotime($dateStr);
                        $dateStartTimeStr = date('Y-m-d',$v['service_start_time']);
                        $start_time_xtmp=strtotime($dateStartTimeStr.' 00:00:00');
                        $park_end_time = $v['service_start_time'] > 100 ? $start_time_xtmp : $tmp_parking_end_time;
                        // 如果是虚拟停车位 对应每一个车生成唯一收费编号
                        $parking_car_type_arr = $db_House_new_parking->parking_car_type_arr;
                        if ($parking_position['end_time'] > $park_end_time) {
                            $dateStr = date('Y-m-d', $parking_position['end_time']) . ' 23:59:59';
                            $park_end_time = strtotime($dateStr) + 1;
                        }
                         $start_time_stamp=$park_end_time+1;
                         if ($parking_position['start_time'] > 100 && $parking_position['end_time']>=$tmp_parking_end_time) {
                             $start_time_stamp = $parking_position['start_time'];
                         }else if((empty($parking_position['start_time']) || $parking_position['start_time']<1) && $parking_position['end_time']>$tmp_parking_end_time){
                             $start_time_stamp = $tmp_parking_end_time;
                         }
                         
                        $park_set = [];
                        $park_data['begin_time'] = $start_time_stamp;
                        $park_end_time=$park_end_time;
                        $park_data['end_time'] = $park_end_time;
                        if ($v['is_prepare'] == 1) {
                            if (!empty($prepaid_info)) {
                                $prepaid_info = $prepaid_info->toArray();
                            }
                            if (!empty($prepaid_info)) {
                                $prepaidInfo = $prepaid_info[0];
                                if ($prepaidInfo['give_cycle_datetype'] == 1) {
                                    $park_end_time = strtotime("+" . $v['service_give_month_num'] . " day", $park_end_time);
                                } elseif ($prepaidInfo['give_cycle_datetype'] == 2) {
                                    $park_end_time = strtotime("+" . $v['service_give_month_num'] . " day", $park_end_time);
                                } elseif ($prepaidInfo['give_cycle_datetype'] == 3) {
                                    $park_end_time = strtotime("+" . $v['service_give_month_num'] . " day", $park_end_time);
                                }
                            }

                            $cycle = $v['service_month_num'];
                        } else {
                            $cycle = $v['service_month_num'] ? $v['service_month_num'] : 1;
                        }
                        if ($rule_info['bill_create_set'] == 1) {
                            $park_data['end_time'] = strtotime("+" . $cycle . " day", $park_end_time);
                        } elseif ($rule_info['bill_create_set'] == 2) {
                            //todo 判断是不是按照自然月来生成订单
                            if (cfg('open_natural_month') == 1) {
                                $park_data['end_time'] = strtotime("+" . $cycle . " month", $park_end_time);
                                $start_d=date('d',$park_end_time);
                                $tmp_service_end_time=strtotime("+$cycle month",$park_end_time);
                                $end_d=date('d',$tmp_service_end_time);
                                if($start_d!=$end_d){
                                    $end_date=date('Y-m',$tmp_service_end_time).'-01 00:00:00';
                                    $end_date_time=strtotime($end_date)-1;
                                    $park_data['end_time']=$end_date_time;
                                }
                            } else {
                                $cycle = $cycle * 30;
                                $park_data['end_time'] = strtotime("+" . $cycle . " day", $park_end_time);
                            }
                        } else {
                            $park_data['end_time'] = strtotime("+" . $cycle . " year", $park_end_time);
                        }
                        $park_data['end_time']=$park_data['end_time']-1;
                        $park_position_set['start_time'] = $park_data['begin_time'];
                        $park_position_set['end_time'] = $park_data['end_time'];
                        $db_house_village_parking_position->saveOne(['position_id' => $position_id], $park_position_set);
                        if (!empty($parking_car)) {

                            foreach ($parking_car as $k => $vv) {
                                $park_set['start_time'] = $park_data['begin_time'];
                                $park_set['end_time'] = $park_data['end_time'];
                                if ($park_sys_type != 'D1') {
                                    $park_data['park_id'] = $vv['village_id'];
                                }
                                $park_data['car_number'] = $vv['province'] . $vv['car_number'];
                                if ($now_car_id) {
                                    $park_position_set['start_time'] = $park_data['begin_time'];
                                    $park_position_set['end_time'] = $park_data['end_time'];
                                }
                                if ($park_sys_type == 'D5') {
                                    //todo D5智慧停车 触发下发到设备
                                    (new D5Service())->D5AddCar($vv['village_id'], $vv['card_id'], $park_set['start_time'], $park_set['end_time'], 'mobile', $garage_id);
                                } elseif ($park_sys_type == 'D6') {
                                    //todo D6智慧停车 触发下发到设备
                                    fdump_api(['D6智慧停车续费成功==' . __LINE__, $vv], 'd6_park/after_pay', 1);
                                    (new D6Service())->d6synVehicle($vv['village_id'], $vv['card_id'], 0, $park_set);
                                }

                                //D3同步白名单到设备上
                                $white_record = [
                                    'village_id' => $vv['village_id'],
                                    'car_number' => $park_data['car_number']
                                ];
                                (new HouseVillageParkingService())->addWhitelist($white_record);
                                // 3.7月卡会员同步（停车云）
                                $park_data['remark'] = '';
                                $park_data['pid'] = '';
                                $service_name = 'month_member_sync';// 添加会员卡操作
                                if ($now_car_id) {
                                    $card_id = $now_car_id;
                                } else {
                                    $card_id = '2' . sprintf("%09d", $vv['car_id']);//月卡会员编号（收费系统唯一编号  是
                                }
                                // 同步储值卡
                                if ($parking_car_type_arr && $vv['parking_car_type'] && isset($parking_car_type_arr[$vv['parking_car_type']]) && $parking_car_type_arr[$vv['parking_car_type']]) {
                                    $car_type_id = $parking_car_type_arr[$vv['parking_car_type']];
                                } else if ($vv['temporary_car_type']) {
                                    $car_type_id = $vv['store_value_car_type'];
                                } else {
                                    $car_type_id = '储值车A';
                                }
                                $park_set['card_id'] = $card_id;
                                if ($park_sys_type == 'D1' && $vv['card_id']) {
                                    $park_data['is_edit'] = 1;
                                } elseif ($park_sys_type != 'D1') {
                                    $park_data['operate_type'] = $vv['card_id'] ? '2' : '1';    //1 添加，2 编辑
                                    if ($park_data['operate_type'] == 1) {
                                        $park_data['create_time'] = time();
                                    } else {
                                        $park_data['update_time'] = time();
                                    }
                                    $park_data['price'] = $v['pay_money'];//实收金额
                                    $park_data['channel_id'] = $channel_number_str;//月卡允许通行的通道编号
                                    $park_data['amount_receivable'] = $v['pay_money'];//应收金额
                                }
                                if ($park_sys_type == 'D7') {
                                    if (!empty($v['parking_car_type']) && $v['parking_car_type'] < 17) {
                                        $res_park = $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $v['car_id']], $park_set);
                                        fdump_api([$res_park, $park_position_set, $parking_position['position_id']], 'D3park/mouth_type_0718', 1);
                                        /*
                                        if ($park_position_set) {
                                            $db_house_village_parking_position->saveOne(['position_id' => $v['car_position_id']], $park_position_set);
                                        }
                                        */
                                    }
                                } else {
                                    $park_data['name'] = $vv['car_user_name'];
                                    $park_data['car_type_id'] = $car_type_id;
                                    $park_data['tel'] = $vv['car_user_phone'] ? $vv['car_user_phone'] : '';
                                    $park_data['address'] = $address;
                                    $park_data['p_lot'] = strval($parking_position['position_num']);

                                    $park_data['card_id'] = $card_id;
                                    $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                                    $json_data['data'] = $park_data;
                                    $json_data['service_name'] = $service_name;

                                    $post_data['unserialize_desc'] = serialize($json_data);
                                    $post_data['status'] = 2;
                                    $post_data['card_id'] = $card_id;
                                    $post_data['msg_id'] = $json_data['msg_id'];
                                    $post_data['village_id'] = $v['village_id'];
                                    $post_data['park_id'] = $park_system['park_id'];
                                    $post_data['token'] = $park_system['token'];
                                    $post_data['car_number'] = $park_data['car_number'] ? $park_data['car_number'] : '';
                                    $post_data['service_name'] = $service_name;
                                    $post_data['create_time'] = time();
                                    $res = $db_park_total_record->add($post_data);
                                    // 没有配置停车场，或者数据添加成功都保存车辆信息。
                                    if (!($park_system['park_id'] && $park_system['token']) || $res) {
                                        $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $vv['car_id']], $park_set);
                                        /*
                                        if ($park_position_set) {
                                            $db_house_village_parking_position->saveOne(['position_id' => $parking_position['position_id']], $park_position_set);
                                        }
                                        */
                                    } else {
                                        //2021/2/1 多车多位  修改 start
                                        /* $infos = D('House_village_parking_car')->bind_parking_position($card_id);
                                         if($infos){
                                             $park_data['car_group'] = intval($infos['car_group']);
                                             $park_data['p_lot_number'] = intval($infos['p_lot_number']);
                                         }*/
                                        //2021/2/1 多车多位  修改 end
                                        $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                                        $json_data['data'] = $park_data;
                                        $json_data['service_name'] = $service_name;
                                        $post_data['unserialize_desc'] = serialize($json_data);
                                        $post_data['status'] = 2;
                                        $post_data['card_id'] = $card_id;
                                        $post_data['msg_id'] = $json_data['msg_id'];
                                        $post_data['village_id'] = $vv['village_id'];
                                        $post_data['park_id'] = $park_system['park_id'];
                                        $post_data['token'] = $park_system['token'];
                                        $post_data['car_number'] = $park_data['car_number'] ? $park_data['car_number'] : '';
                                        $post_data['service_name'] = $service_name;
                                        $post_data['create_time'] = time();
                                        $res = $db_park_total_record->add($post_data);//月卡会员下发
                                        if ($res) {
                                            $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $vv['car_id']], $park_set);
                                            /*
                                            if ($park_position_set) {
                                                $db_house_village_parking_position->saveOne(['position_id' => $parking_position['position_id']], $park_position_set);
                                            }
                                            */
                                        }
                                    }
                                }
                                
                                if($vv['equipment_no'] && isset($vv['add_cfrom']) && $vv['add_cfrom']=='third_ai_park'){
                                    try {
                                        $houseNewAiParkingService =  new \app\community\model\service\HouseNewAiParkingService();
                                        $dataCarTmpArr=array('park_key'=>$vv['equipment_no'],'village_id'=>$vv['village_id'],'cfrom'=>'month_afterpay_offline');
                                        $houseNewAiParkingService->synUpdateThirdAiParkCarTime($vv['car_id'],$dataCarTmpArr);
                                    }catch (\Exception $ee){
                                        fdump_api(['line' => __LINE__,'car_number'=>$car_number,'car_id'=>$vv['car_id'],'msg' => $ee->getMessage()], 'after_pay/offlineAfterPay', 1);
                                    }
                                }
                                
                            }
                        }
                       // fdump_api([$park_position_set,$park_set,$park_config],'children_position_log_0803',1);
                        //子母车位续费同步到期时间
                        if ($park_config['children_position_type']==1){
                            $parking_position_arr =$db_house_village_parking_position->getColumn(['parent_position_id'=>$v['position_id'],'children_type'=>2],'position_id');
                            fdump_api([$v['position_id'],$parking_position_arr,$park_position_set],'children_position_log_0803',1);
                            if (!empty($parking_position_arr)){
                                $db_house_village_parking_position->saveOne(['position_id'=>$parking_position_arr],$park_position_set);
                                $park_car_arr=$db_house_village_parking_car->get_column(['car_position_id'=>$parking_position_arr],'car_id');
                                fdump_api([$park_car_arr],'children_position_log_0803',1);
                                if (!empty($park_car_arr)&&isset($park_set)&&!empty($park_set)){
                                    $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$park_car_arr],$park_set);
                                }
                            }
                        }

                         $tmpUpdateData['service_start_time'] = $service_start_time;
                         $tmpUpdateData['service_end_time'] = $service_end_time;
                    }
                }
                 /*** 车位到期时间 老版的和新版的冲突了 去掉老版的统一用新版的  有的客服坚持要这个功能*/
                if($is_old_park_position && ($v['order_type'] == 'park' || $v['order_type'] == 'parking_management')){
                    fdump_api([$v['position_id'],$v['order_type'],$v['village_id']],'children_position_log_0803',1);
                    if((!isset($v['f_order_id'])||$v['f_order_id']<1) && $v['position_id']){
                        try {
                            $service_house_village_parking->editParkingPosition(['position_id' => $v['position_id']], ['end_time' => $service_end_time]);
                            $service_house_village_parking->editParkingCar(['car_position_id' => $v['position_id']], ['end_time' => $service_end_time]);
                        }catch (\Exception $e) {
                            fdump_api([ __LINE__, 'err' => $e->getMessage(), 'code' => $e->getCode()], 'after_pay/offlineAfterPay', true);
                        }
                    }
                }
               
            }
            if($v['order_type'] == 'property'){
                try {
                    $userListObj = $service_house_village_user_bind->getList([['vacancy_id','=',$v['room_id']], ['status','=',1]], 'uid, pigcms_id');
                    if($userListObj && !$userListObj->isEmpty()){
                        $userList=$userListObj->toArray();
                        $houseFaceImgService = new HouseFaceImgService();
                        foreach ($userList as $userInfo) {
                            $houseFaceImgService->commonUserToBox($userInfo['uid'], $userInfo['pigcms_id']);
                        } 
                    }
                } catch (\Exception $e){
                    fdump_api($e->getMessage(),'$houseFaceImgService');
                }
            }
            $tmpUpdateData['is_discard'] = 1;
            $tmpUpdateData['from'] = 1;
            $tmpUpdateData['discard_reason'] = '';
            $tmp_extra_data=isset($v['discount_info']) && $v['discount_info'] ? json_decode($v['discount_info'],true):array();
            $rule_rate=0;
            $rule_discount_info=array();
            if($tmp_extra_data && isset($tmp_extra_data['rule_discount']) && !empty($tmp_extra_data['rule_discount']) && isset($tmp_extra_data['rule_discount']['rate'])){
                $rule_rate=$tmp_extra_data['rule_discount']['rate'];
                $rule_discount_info=$tmp_extra_data['rule_discount'];
            }
            if($rule_rate>0 && $rule_discount_info && $rule_discount_info['diy_type']==1){
                $tmpUpdateData['pay_money']=($tmpUpdateData['pay_money']*$rule_rate)/100;
                $tmpUpdateData['pay_money']=round($tmpUpdateData['pay_money'],2);
            }
            if($other_data && isset($other_data['is_village_balance_pay']) && $other_data['is_village_balance_pay']>0){
                $tmpUpdateData['village_balance']=$tmpUpdateData['pay_money'];
            }
            $db_house_new_pay_order->saveOne(['order_id'=>$v['order_id']],$tmpUpdateData);
            array_push($messageDatas['order_type'],(new HouseNewChargeService())->getType($v['order_type']));

            if (isset($v['from']) && $v['from'] == 4) {
                $worksOrderPayOrderIds[] = $v['order_id'];
            }
            if($pay_order_info_id>0){
                $pay_db = new PayOrderInfo;
                $payOrderInfo = $pay_db->getOneOrder(array('id'=>$pay_order_info_id),'*');
                if($payOrderInfo && !$payOrderInfo->isEmpty() && $payOrderInfo['channel']=='scrcu'){
                    $service_community_pay = new CommunityPayService();
                    $params = $service_community_pay->getVillageScrcu($village_id);
                    fdump_api([__LINE__,'summary_id' => $summary_id,'village_id'=>$village_id,'params'=>$params,'business_order_id'=>$payOrderInfo['business_order_id']],'after_pay/offlineAfterPay',1);
                    if(empty($params)){
                        //平台的
                        try{
                            $tmp_order=$v;
                            $tmp_order['payment_money'] = $tmpUpdateData['pay_money'];
                            $tmp_order['order_type'] = 'village_new_pay';
                            $tmp_order['money'] = $tmpUpdateData['pay_money'];
                            $tmp_order['plat_order_id'] = $payOrderInfo['business_order_id'];
                            $tmp_order['desc'] = '社区扫码缴费';
                            $res = invoke_cms_model('SystemBill/bill_method',['type'=>5,'order_info'=>$tmp_order,'is_fenzhang'=>0,'is_new_charge'=>1]);
                        }catch (\Exception $e){
                            fdump_api(['line' . __LINE__, 'err_msg'=>$e->getMessage()], 'after_pay/newPayServiceLog', 1);
                        }
                    }
                }else if($payOrderInfo && !$payOrderInfo->isEmpty() && $payOrderInfo['business']=='scanpay' && $payOrderInfo['is_own']==0){
                    //平台的
                    try{
                        $tmp_order=$v;
                        $tmp_order['payment_money'] = $tmpUpdateData['pay_money'];
                        $tmp_order['order_type'] = 'village_new_pay';
                        $tmp_order['money'] = $tmpUpdateData['pay_money'];
                        $tmp_order['plat_order_id'] = $payOrderInfo['business_order_id'];
                        $tmp_order['desc'] = '社区扫码缴费';
                        $res = invoke_cms_model('SystemBill/bill_method',['type'=>5,'order_info'=>$tmp_order,'is_fenzhang'=>0,'is_new_charge'=>1]);
                    }catch (\Exception $e){
                        fdump_api(['line' . __LINE__, 'err_msg'=>$e->getMessage()], 'after_pay/newPayServiceLog', 1);
                    }
                }
            }
        }
        
        if(!empty($ticket_number)){
            $houseVillageOrderTicketNumberDb= new HouseVillageOrderTicketNumber();
            $orderTicketNumberSave=array('use_time'=>time(),'ticket_status'=>2);
            $orderTicketNumberWhere=array(['village_id','=',$village_id],['ticket_fill_number','=',$ticket_number]);
            $orderTicketNumberWhere[]=array('ticket_status','<',2);
            $orderTicketNumberWhere[]=array('summary_id','=',$summary_id);
            $oneOrderTicketNumber=$houseVillageOrderTicketNumberDb->getOneData($orderTicketNumberWhere,'*','id ASC');
            if($oneOrderTicketNumber && !$oneOrderTicketNumber->isEmpty()){
                $houseVillageOrderTicketNumberDb->saveData(array('id'=>$oneOrderTicketNumber['id']),$orderTicketNumberSave);
            }
        }
        fdump_api([__LINE__,'summary_id' => $summary_id],'after_pay/offlineAfterPay',1);
        if (!empty($other_data) && isset($other_data['village_balance_pay_money']) && ($other_data['village_balance_pay_money']>0) && isset($other_data['is_village_balance_pay']) && $other_data['is_village_balance_pay']&& isset($other_data['uid']) && $other_data['uid']>0 ) {
            $village_user = new StorageService();
            $village_money_data=[
                'uid'=>$other_data['uid'],
                'village_id'=>$village_id,
                'type'=>2,
                'current_village_balance'=>$other_data['village_balance_pay_money'],
                'role_id'=>$order_list['0']['role_id'],
                'desc'=>L_("物业缴费，扣除小区住户余额，订单编号X1", array("X1" => $summary_order_info['order_no'])),
                'order_id'=>$order_list['0']['order_id'],
                'order_type'=>1,
                'opt_money_type'=>''
            ];
            $villageUseResult = $village_user->addVillageUserMoney($village_money_data);
            if ($villageUseResult['error_code']) {
                fdump_api([$village_money_data,'villageUseResult'=>$villageUseResult],'00village_balance_pay',1);
            }
        }
        if(!empty($other_data) && isset($other_data['add_village_balance']) && $other_data['add_village_balance']>0 && isset($other_data['uid']) && $other_data['uid']>0){
            $village_user = new StorageService();
            $village_money_data=[
                'uid'=>$other_data['uid'],
                'village_id'=>$village_id,
                'type'=>1,
                'current_village_balance'=>$other_data['add_village_balance'],
                'role_id'=>$order_list['0']['role_id'],
                'desc'=>L_("物业缴费，线下多缴费充值到小区住户余额，订单编号X1", array("X1" => $summary_order_info['order_no'])),
                'order_id'=>$order_list['0']['order_id'],
                'order_type'=>1,
                'opt_money_type'=>''
            ];
            $villageUseResult = $village_user->addVillageUserMoney($village_money_data);
            if ($villageUseResult['error_code']) {
                fdump_api([$village_money_data,'villageUseResult'=>$villageUseResult],'00village_balance_pay',1);
            }
        }
        if (!empty($worksOrderPayOrderIds)) {
            // 存在工单订单 反向写入下  time()
            $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
            $whereWorksOrder = [];
            $whereWorksOrder[] = ['pay_order_id', 'in', $worksOrderPayOrderIds];
            $worksOrderUpdate = ['pay_time' => time()];
            $db_house_new_repair_works_order->saveOne($whereWorksOrder, $worksOrderUpdate);
        }
            fdump_api([__LINE__,'summary_id' => $summary_id],'after_pay/offlineAfterPay',1);
        if(empty($save_paid_order_record['village_id']) && $village_id>0){
            $save_paid_order_record['village_id']=$village_id;
        }
        $house_type_arr=array_unique($house_type_arr);
        $save_paid_order_record['house_type']=$house_type_arr ? implode(',',$house_type_arr):'';
        $save_paid_order_record['sub_order_ids']=$sub_order_ids ? implode(',',$sub_order_ids):'';
        $houseNewChargeProjectService=new HouseNewChargeProjectService();
        $business_name_tmp=$houseNewChargeProjectService->getChargeNanmeStr($house_type_arr);
        $save_paid_order_record['business_name']=$business_name_tmp ? $business_name_tmp:$business_name;
        $save_paid_order_record['order_type']=$tmp_order_type;
        $save_paid_order_record['order_type_v']=$tmp_order_type_v;
        $whereArr=array();
        if($pay_order_info_id>0){
            $whereArr[]=array('pay_order_info_id','=',$pay_order_info_id);
        }else{
            $whereArr[]=array('village_id','=',$save_paid_order_record['village_id']);
            $whereArr[]=array('source_from','=',1);
            $whereArr[]=array('order_id','=',$save_paid_order_record['order_id']);
            $whereArr[]=array('order_no','=',$save_paid_order_record['order_no']);
            $whereArr[]=array('table_name','=','house_new_pay_order_summary');
        }
        $paidOrderRecordDb = new PaidOrderRecord();
        $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereArr,'id,update_time');
        if($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()){
            if(!empty($paidOrderRecordInfo['business_type']) &&($paidOrderRecordInfo['business_type']=='scanpay') ){
                $save_paid_order_record['pay_type']='offline_scan'; //线下扫码支付
            }
            $extra_data=$paidOrderRecordInfo['extra_data'] ? json_decode($paidOrderRecordInfo['extra_data'],true):array();
            if($position_id>0){
                $extra_data['car_position_id']=$position_id;
                $save_paid_order_record['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
            $paidOrderRecordDb->saveOneData(array(['id', '=', $paidOrderRecordInfo['id']]),$save_paid_order_record);
        }else{
            $service_plat_order = new PlatOrderService();
            $plat_order_info = $service_plat_order->getPlatOrder(['business_id'=>$summary_id,'business_type'=>'village_new_pay']);
            $db_pay_order_info=new PayOrderInfo();
            if($plat_order_info && !$plat_order_info->isEmpty()){
                $plat_order_info=$plat_order_info->toArray();
                $save_paid_order_record['business_order_id']=$plat_order_info['order_id'];
                $save_paid_order_record['pay_order_no']=$plat_order_info['orderid'];
                $save_paid_order_record['third_transaction_no']=$plat_order_info['third_id'];
                $save_paid_order_record['pay_money']=$plat_order_info['pay_money'];
                
                $payOrderInfo=$db_pay_order_info->getByOrderNo($plat_order_info['orderid']);
                if (!empty($payOrderInfo) && !$payOrderInfo->isEmpty()){
                    $payOrderInfo=$payOrderInfo->toArray();
                    $save_paid_order_record['pay_env']=$payOrderInfo['env'];
                    $save_paid_order_record['pay_channel']=$payOrderInfo['channel'];
                    $save_paid_order_record['pay_order_info_id']=$payOrderInfo['id'];
                    if(!empty($payOrderInfo['business_type']) &&($payOrderInfo['business_type']=='scanpay') ){
                        $save_paid_order_record['pay_type']='offline_scan'; //线下扫码支付
                    }
                }
            }
            if($position_id>0){
                $save_paid_order_record['extra_data']=array('car_position_id'=>$position_id);
                $save_paid_order_record['extra_data']=json_encode($save_paid_order_record['extra_data'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
            $paidOrderRecordDb->addOneData($save_paid_order_record);
        }
        fdump_api([__LINE__,'summary_id' => $summary_id],'after_pay/offlineAfterPay',1);
        if($countnum>1){
            $messageDatas['type']=1;
        }
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(array('village_id'=>$village_id));
        $property_info = $service_house_village->get_house_property($village_info['property_id'],'property_name');
        $village_bind = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$summary_order_info['pay_bind_id']]);
        $messageDatas['property_id']=$village_info['property_id'];
        $messageDatas['property_name']=$property_info['property_name'];
        $messageDatas['village_name']=$village_info['village_name'];
        $messageDatas['name']=$village_bind['name'];
        if ($village_bind['single_id'] && $village_bind['floor_id'] && $village_bind['layer_id'] && $village_bind['vacancy_id'] && $village_bind['village_id']) {
            $address = $service_house_village->getSingleFloorRoom($village_bind['single_id'],$village_bind['floor_id'],$village_bind['layer_id'],$village_bind['vacancy_id'],$village_bind['village_id']);
            if ($address) {
                $village_bind['address'] = $address;
            }
        }
        $messageDatas['address']=$village_bind && $village_bind['address']?$village_bind['address']:'';
        if(empty($messageDatas['address']) && !empty($car_number)){
            $messageDatas['address']='车牌号 '.$car_number;
        }
        fdump_api([__LINE__,'summary_id' => $summary_id],'after_pay/offlineAfterPay',1);
        $this->sendMessageToHouseWorker($village_id,$messageDatas);
        //Db::commit();
        return array('error_code'=>0,'msg'=>'提交成功！');
    }

    /**
     * 线上支付回调
     * @author lijie
     * @date_time 2021/06/24
     * @param $plat_order_id
     * @param $extra
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function afterPay($plat_order_id,$extra)
    {
        set_time_limit(30);
        fdump_api(['支付回调' => __LINE__,'plat_order_id' => $plat_order_id,'extra' => $extra],'after_pay/newPayServiceLog',1);
        $serviceHouseVillage = new HouseVillageService();
        $service_house_new_cashier = new HouseNewCashierService();
        $service_plat_order = new PlatOrderService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $db_pay_order_info = new PayOrderInfo();
        $service_user = new UserService();
        $service_house_village_parking = new HouseVillageParkingService();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $db_house_new_order_log = new HouseNewOrderLog();
        $village_nmv_charge = new HouseVillageNmvCharge();
        $village_nmv_card = new HouseVillageNmvCard();
        $service_house_village_config = new HouseVillageConfigService();
        $village_id=0;
        $messageDatas=array();
        $plat_order_res = $service_plat_order->getPlatOrder(['order_id'=>$plat_order_id]);
        fdump_api(['支付回调' => __LINE__,'plat_order_res' => $plat_order_res && !is_array($plat_order_res) ? $plat_order_res->toArray() : $plat_order_res],'after_pay/newPayServiceLog',1);
        $pay_order_info = $db_pay_order_info->getByOrderNo($extra['paid_orderid']);
        fdump_api(['支付回调' => __LINE__,'pay_order_info' => $pay_order_info && !is_array($pay_order_info) ? $pay_order_info->toArray() : $pay_order_info],'after_pay/newPayServiceLog',1);
        if ($plat_order_res['paid']==1){
            fdump_api(['订单已支付' => __LINE__,'paid' => $plat_order_res['paid']],'after_pay/newPayServiceLog',1);
            return array('error_code'=>1,'msg'=>'订单已支付！');
        }
        $pay_order_info_id=$pay_order_info && isset($pay_order_info['id']) ? $pay_order_info['id']:0;
        $order_record_id=0;
        if(isset($extra['order_record_id'])){
            $order_record_id=$extra['order_record_id'];
        }
        if(isset($extra['pay_order_info_id'])){
            $pay_order_info_id=$extra['pay_order_info_id'];
        }
        $record_extra_data=$extra;
        $record_extra_data['plat_order_id']=$plat_order_id;
        $record_extra_data['after_pay_time']=time();
        $record_extra_data['car_position_id']=0;
        $save_paid_order_record=array('source_from'=>1);
        $messageDatas['pay_money']=$extra['paid_money'];
        /**
        ***定制的住户余额的冷水，热水，电费余额支付
         ***/
        $customized_meter_reading=cfg('customized_meter_reading');
        $is_customized_meter_reading=!empty($customized_meter_reading) ? intval($customized_meter_reading):0;
        $current_village_hot_water_balance=0;
        $current_village_cold_water_balance=0;
        $current_village_electric_balance=0;
        $current_village_balance=$extra['current_village_balance'];
        $opt_money_balance_type='';
        if($is_customized_meter_reading>0){
            if(isset($extra['current_village_hot_water_balance']) && $extra['current_village_hot_water_balance']>0){
                $current_village_hot_water_balance=$extra['current_village_hot_water_balance'];
                $current_village_balance=$current_village_hot_water_balance;
                $opt_money_balance_type='hot_water_balance';
            }
            if(isset($extra['current_village_cold_water_balance'])&& $extra['current_village_cold_water_balance']>0){
                $current_village_cold_water_balance=$extra['current_village_cold_water_balance'];
                $current_village_balance=$current_village_cold_water_balance;
                $opt_money_balance_type='cold_water_balance';
            }
            if(isset($extra['current_village_electric_balance'])&& $extra['current_village_electric_balance']>0){
                $current_village_electric_balance=$extra['current_village_electric_balance'];
                $current_village_balance=$current_village_electric_balance;
                $opt_money_balance_type='electric_balance';
            }
        }
        $save_paid_order_record['pay_type_from']=$opt_money_balance_type;
        if($current_village_balance>0){
            $save_paid_order_record['pay_type_from']=$save_paid_order_record['pay_type_from'] ? $save_paid_order_record['pay_type_from'].',village_balance':'village_balance';
        }
        $current_system_balance = isset($extra['current_system_balance']) ? $extra['current_system_balance'] : 0;
        if ($current_system_balance > 0) {
            $save_paid_order_record['pay_type_from'] = $save_paid_order_record['pay_type_from'] ? $save_paid_order_record['pay_type_from'] . ',system_balance' : 'system_balance';
        }
        $messageDatas['type']=2;
        if (isset($extra['is_own']) && intval($extra['is_own']) >= 1) {
            $is_own = 5;
        } else {
            $is_own = 4;
        }
        $service_plat_order->savePlatOrder(['order_id'=>$plat_order_id],
            [
                'system_balance'     => $current_system_balance,
                'village_balance'    => $current_village_balance,
                'pay_time'           => time(),
                'paid'               => 1,
                'pay_type'           => $extra['paid_type'],
                'pay_money'          => $extra['paid_money'],
                'third_id'           => isset($pay_order_info['paid_extra'])?$pay_order_info['paid_extra']:'',
                'system_score'       => $extra['current_score_use'],
                'system_score_money' => $extra['current_score_deducte'],
                'orderid'            => $extra['paid_orderid'],
                'is_own'             => $is_own
            ]);
        $plat_order_info = $service_plat_order->getPlatOrder(['order_id'=>$plat_order_id]);
        $order_id = $plat_order_info['business_id'];
        $is_chinaums_divide =0;
        $addVillageMoneyArr=array();
        if($pay_order_info && isset($pay_order_info['pay_config']) && $pay_order_info['pay_config'] && $pay_order_info['channel']=='chinaums' && $pay_order_info['env']=='wechat_mini'){
            $payOrderConfig = json_decode($pay_order_info['pay_config'],true);
            if($payOrderConfig && isset($payOrderConfig['without_asset']) && $payOrderConfig['without_asset'] == 1){
                $is_chinaums_divide=1;
                $chinaumsOrderid = $pay_order_info['orderid'];
                $chinaumsMoney = $pay_order_info['paid_money'];
                $chinaums_merchant_already_get=!empty($pay_order_info['chinaums_merchant_already_get']) ? $pay_order_info['chinaums_merchant_already_get']:0;
                $chinaums_platform_already_get = !empty($pay_order_info['chinaums_platform_already_get']) ? $pay_order_info['chinaums_platform_already_get']:0;
                $subOrders = [
                    ['merId' => $payOrderConfig['sub_mer_no'], 'satAmt' => $chinaums_merchant_already_get],
                ];
                $divide_money= $chinaums_merchant_already_get / 100;
                $divide_money=$divide_money >0 ? round($divide_money,2):0;
                try {
                    $chinaumsService=new ChinaumsService('wechat_mini', $payOrderConfig);
                    $result = $chinaumsService->fenzhangConfirm($chinaumsOrderid, $chinaumsMoney, $subOrders);
                    fdump_api(['payOrderConfig'=>$payOrderConfig, 'chinaumsOrderid'=>$chinaumsOrderid, 'chinaumsMoney'=>$chinaumsMoney, 'subOrders'=>$subOrders,'result'=>$result], 'after_pay/000chinaums_divide', 1);
                    if ($result) {
                        $addVillageMoneyArr['desc'] = "银联分账成功";
                        $db_pay_order_info->updateThis(['orderid' => $chinaumsOrderid, 'channel' => 'chinaums', 'paid' => 1], ['async_confirm_result' => json_encode($result, JSON_UNESCAPED_UNICODE)]);
                    }
                }catch (\Exception $e){
                    fdump_api([__LINE__,'payOrderConfig'=>$payOrderConfig, 'chinaumsOrderid'=>$chinaumsOrderid, 'chinaumsMoney'=>$chinaumsMoney, 'subOrders'=>$subOrders,'err_msg'=>$e->getMessage()], 'after_pay/000chinaums_divide', 1);
                    $err_msg=$e->getMessage();
                    $addVillageMoneyArr['desc']="银联分账失败！".$err_msg;
                }
                $addVillageMoneyArr['plat_order_id']=$plat_order_id;
                $addVillageMoneyArr['pay_orderid']=$pay_order_info['orderid'];
                $addVillageMoneyArr['is_chinaums_divide']=$is_chinaums_divide;
                $addVillageMoneyArr['money']=0;
                $addVillageMoneyArr['divide_money']=$divide_money;
                $addVillageMoneyArr['system_take']=$chinaums_platform_already_get / 100;
                $addVillageMoneyArr['system_take']=$addVillageMoneyArr['system_take'] >0 ? round($addVillageMoneyArr['system_take'],2):0;
            }
        }
        $cashier_order = $service_house_new_cashier->getOrderSummary(['summary_id'=>$order_id]);
        fdump_api(['支付回调' => __LINE__,'cashier_order' => $cashier_order && !is_array($cashier_order)  ? $cashier_order->toArray() : $cashier_order],'after_pay/newPayServiceLog',1);
        if ($cashier_order['is_paid']==1){
            fdump_api(['订单已支付' => __LINE__,'is_paid' => $cashier_order['is_paid']],'after_pay/newPayServiceLog',1);
            return array('error_code'=>1,'msg'=>'订单已支付！');
        }
        if($cashier_order['village_id']){
            $village_id=$cashier_order['village_id'];
            $service_electronic_invoice = new ElectronicInvoiceService();
            $e_config = $service_electronic_invoice->getEConfig($cashier_order['village_id']);
            if(empty($e_config))
                $auto = false;
            elseif ($e_config['is_open'] == 1 && $e_config['is_tui'] == 1)
                $auto = true;
            else
                $auto = false;
        }else{
            $auto = false;
        }
        $offline_pay_money=0;
        if(($cashier_order['offline_pay_type']=='off_mix_pay'||$cashier_order['order_pay_type']=='off_mix_pay') && $cashier_order['offline_pay_money']>0){
            $offline_pay_money=$cashier_order['offline_pay_money'];
        }
        if($is_chinaums_divide<1 && $pay_order_info && isset($pay_order_info['channel'])&& $pay_order_info['channel'] == 'chinaums' && $pay_order_info['chinaums_merchant_already_get']>0) {
            $chinaums_divide_record=array('village_id'=>$village_id,'chinaums_zg_mid'=>'');
            $db_house_village_pay_config  = new HouseVillagePayConfig();
            $whereConfig=array(['village_id','=',$village_id]);
            $village_pay_config= $db_house_village_pay_config->getOne($whereConfig,"*");
            if($village_pay_config && !$village_pay_config->isEmpty() && isset($village_pay_config['chinaums_zg_mid']) && $village_pay_config['chinaums_zg_mid']){
                $villageChinaumsDivideRecordDb= new VillageChinaumsDivideRecord();
                $chinaums_divide_record['chinaums_zg_mid']=$village_pay_config['chinaums_zg_mid'];
                $chinaums_divide_record['village_money']=$pay_order_info['chinaums_merchant_already_get'];
                $chinaums_divide_record['platform_money']=$pay_order_info['chinaums_platform_already_get'];
                $chinaums_divide_record['dividend_status']=1;
                $chinaums_divide_record['pay_order_info_id']=$pay_order_info['id'];
                $chinaums_divide_record['b_order_id']=$order_id;
                $chinaums_divide_record['order_table_type']='house_new_pay_order_summary';
                $chinaums_divide_record['add_time']=time();
                $whereDivideRecord=array(['pay_order_info_id','=',$pay_order_info['id']],['village_id','=',$village_id],['b_order_id','=',$order_id]);
                $haveDivideRecord=$villageChinaumsDivideRecordDb->getOneData($whereDivideRecord);
                if(empty($haveDivideRecord) || $haveDivideRecord->isEmpty()){
                    $villageChinaumsDivideRecordDb->addOneData($chinaums_divide_record);
                }
            }
        }
        $lakala_pay_switch=cfg('lakala_pay_switch');
        $lakala_pay_switch = $lakala_pay_switch ? intval($lakala_pay_switch):0;
        if($lakala_pay_switch>0 && $pay_order_info && isset($pay_order_info['channel'])&& $pay_order_info['channel'] == 'lakala' && $pay_order_info['pay_config'] && $pay_order_info['paid_extra'] && $pay_order_info['paid_money']>0){
            try {
                $pay_env=$pay_order_info['env'];
                $pay_config = json_decode($pay_order_info['pay_config'], true);
                $lakalaService=  new   \app\pay\model\service\channel\LakalaService($pay_env,$pay_config);
                $paid_order_no = $pay_order_info['paid_extra'];
                $pay_money=$pay_order_info['paid_money'];
                $mer_get=$pay_money;
                $plat_get=0;
                $now_village_info=$serviceHouseVillage->getHouseVillageInfo(array('village_id'=>$village_id),'*');
                if($now_village_info['percent']>0){
                    $percent = $now_village_info['percent'];
                }else{
                    $percent = cfg('platform_get_village_percent');
                }
                $system_take=0;
                if($percent>0 && $percent<100){
                    $system_take= ($pay_money*$percent)/100;
                    $system_take=round($system_take);
                    $plat_get=$system_take;
                    $mer_get=$pay_money-$system_take;
                }
                
                $lakalafz_ret=$lakalaService->fenzhang($paid_order_no, $mer_get, $plat_get);
                $mer_get_tmp=$mer_get/100;
                $mer_get_tmp=round($mer_get_tmp,2);
                $plat_get_tmp=$plat_get/100;
                $plat_get_tmp=$plat_get_tmp>0 ? round($plat_get_tmp,2):0;
                $addVillageMoneyArr['lakalafz_ret']=$lakalafz_ret;
                $addVillageMoneyArr['desc'] = "【拉卡拉分账】订单号：".$pay_order_info['orderid']." 小区：".$mer_get_tmp." 平台：".$plat_get_tmp;
                
            } catch (\Exception $ee) {
                fdump_api([$paid_order_no, $ee->getMessage(),'pay_order_info'=>$pay_order_info], 'pay/villageLakalaFenzhangErr', 1);
                $addVillageMoneyArr['desc']="拉卡拉分账失败！".$ee->getMessage();
            }
            $addVillageMoneyArr['plat_order_id']=$plat_order_id;
            $addVillageMoneyArr['pay_orderid']=$pay_order_info['orderid'];
            $addVillageMoneyArr['is_chinaums_divide']=0;
            $addVillageMoneyArr['is_lakala_divide']=1;
            $addVillageMoneyArr['money']=0;
            $addVillageMoneyArr['divide_money']=$mer_get/ 100;
            $addVillageMoneyArr['percent']=$percent;
            $addVillageMoneyArr['system_take']=$system_take/100;
        }
        $save_paid_order_record['order_id']=$order_id;
        $save_paid_order_record['order_no']=$cashier_order['order_no'];
        $save_paid_order_record['village_id']=$village_id;
        $save_paid_order_record['property_id']=$cashier_order['property_id'];
        $save_paid_order_record['room_id']=$cashier_order['room_id'];
        $save_paid_order_record['bind_user_id']=$cashier_order['pigcms_id'] ? $cashier_order['pigcms_id']:$cashier_order['pay_bind_id'];
        $save_paid_order_record['uid']=$cashier_order['pay_uid'] ? $cashier_order['pay_uid']:$cashier_order['uid'];
        $save_paid_order_record['order_money']=$cashier_order['pay_money']>0 ? $cashier_order['pay_money']:$cashier_order['total_money'];
        $save_paid_order_record['table_name']='house_new_pay_order_summary';
        fdump_api(['支付回调' => __LINE__,'save_paid_order_record' => $save_paid_order_record],'after_pay/newPayServiceLog',1);
        // 积分
        if (intval($extra['current_score_use']) > 0) { // 使用了积分
            $score_can_get = $cashier_order['score_can_get'];
        }else{
            $user = $service_user->getUser($cashier_order['pay_uid'],'uid');
            $score = $this->get_score($plat_order_id,$user);
            $score_can_get = $score['score_can_get'];
            $cashier_order['score_can_get']=$score_can_get;
        }
        if($messageDatas['pay_money']<=0 && $extra['current_system_balance']>0){
            $messageDatas['pay_money']= $extra['current_system_balance'];
        }
        $date_summary_order = [];
        if (isset($pay_order_info['own_from']) && $pay_order_info['own_from'] && isset($pay_order_info['own_from_id']) && $pay_order_info['own_from_id']) {
            $date_summary_order['own_from']    = $pay_order_info['own_from'];
            $date_summary_order['own_from_id'] = $pay_order_info['own_from_id'];
        }
        $summary_id=$order_id;
        $date_summary_order['pay_time'] = time();
        $date_summary_order['is_paid'] = 1;
        $date_summary_order['pay_money']=$plat_order_info['pay_money'];
        $date_summary_order['pay_amount_points']=$extra['paid_money']*100;
        $date_summary_order['system_balance']=$extra['current_system_balance'];
        $date_summary_order['village_balance']=$current_village_balance;
        $date_summary_order['score_used_count'] = $extra['current_score_use'];
        $date_summary_order['score_can_get'] = $score_can_get ? $score_can_get : 0;
        $date_summary_order['score_deducte'] = $extra['current_score_deducte'] ? $extra['current_score_deducte'] : 0;
        $date_summary_order['paid_orderid'] = isset($pay_order_info['paid_extra'])?$pay_order_info['paid_extra']:'';
        $date_summary_order['online_pay_type'] = isset($extra['paid_type'])?$extra['paid_type']:'';
        fdump_api(['支付回调' => __LINE__,'更新总订单' => $date_summary_order],'after_pay/newPayServiceLog',1);
        $summary_res=$service_house_new_cashier->saveOrderSummary(['summary_id'=>$order_id],$date_summary_order);//更新总订单
        if(isset($plat_order_info['is_own']) && $plat_order_info['is_own']==0) {
            $plat_order_info['is_own'] += 4;
        }
        $offline_pay = (new NewBuildingService())->getHouseNewOfflinePayFindMethod($cashier_order['property_id'],'id,name');
        $messageDatas['pay_type_str'] = (new NewBuildingService())->getOrderPayTypeMethod($order_id,$offline_pay,$cashier_order['village_balance']);
        //查询子订单
        $order_list = $service_house_new_cashier->getOrderList(['summary_id'=>$order_id],'o.*,n.charge_type,r.fees_type',0,0,'o.order_id ASC');
        $updateSummaryId = 0;
        try {
            if (empty($order_list)) {
                // 没有子订单 获取额外记录
                $extra_data = isset($cashier_order['extra_data']) && $cashier_order['extra_data'] ? json_decode($cashier_order['extra_data'], true) : [];
                if (isset($extra_data['newPayOrderId']) && !empty($extra_data['newPayOrderId'])) {
                    $whereSummaryOrder = [];
                    $whereSummaryOrder[] = ['o.order_id', 'in', $extra_data['newPayOrderId']];
                    $order_list = $service_house_new_cashier->getOrderList($whereSummaryOrder,'o.*,n.charge_type,r.fees_type',0,0,'o.order_id ASC');
                    $updateSummaryId = $order_id;
                }
                fdump_api([__LINE__, 'summary_id' => $order_id, 'extra_data' => $extra_data, 'msg' => '没有子订单'], 'after_pay/offlineAfterPay', 1);
            }
        } catch (\Exception $e) {
            fdump_api(['大的错误line：' . __LINE__, 'line' => $e->getLine(), 'err' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile()], 'after_pay/newPayServiceLog', true);
        } catch (\Throwable $e) {
            fdump_api(['大的错误line：' . __LINE__, 'line' => $e->getLine(), 'err' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile()], 'after_pay/newPayServiceLog', true);
        }
        if (empty($order_list)) {
            fdump_api(['没有子订单' => __LINE__,'summary_id' => $order_id],'after_pay/newPayServiceLog',1);
            return array('error_code'=>1,'msg'=>'没有子订单！');
        }
        $service_house_village = new HouseVillageService();

        $totle_money=0;
        $totle_score_deducte=0;
        $totle_system_balance=0;
        $totle_score_used_count=0;
        $totle_village_balance=0;
        $totle_offline_pay_money=0;
        //-----小区住户余额start-------//
        //获取小区用户信息
        $village_user = new StorageService();

        if (isset($extra['current_village_balance']) && ($extra['current_village_balance']>0)){
            $now_village_user=array();
            if(!empty($cashier_order['pay_uid'])&&!empty($cashier_order['village_id'])){
                $now_village_user = $village_user->getVillageUser($cashier_order['pay_uid'],$cashier_order['village_id']);
            }
            if (empty($now_village_user)) {
                fdump_api(['小区住户余额信息不存在' => __LINE__],'after_pay/newPayServiceLog',1);
                throw new \Exception(L_("小区住户余额信息不存在"), 1003);
            }
            //判断小区住户帐户余额,扣除余额
            $villageBalancePay = floatval($extra['current_village_balance']);
            if ($villageBalancePay && $now_village_user['current_money'] < $villageBalancePay) {
                fdump_api(['您的帐户余额不够此次支付' => __LINE__, 'current_money' => $now_village_user['current_money'], 'villageBalancePay' => $villageBalancePay],'after_pay/newPayServiceLog',1);
                throw new \Exception(L_("您的帐户余额不够此次支付"), 1003);
            }   
        }
        if($is_customized_meter_reading>0){
            $now_village_user=array();
            if(!empty($cashier_order['pay_uid'])&&!empty($cashier_order['village_id'])){
                $now_village_user = $village_user->getVillageUser($cashier_order['pay_uid'],$cashier_order['village_id']);
            }
            if(isset($extra['current_village_hot_water_balance']) && $extra['current_village_hot_water_balance']>0 && $now_village_user['hot_water_balance']<$extra['current_village_hot_water_balance']){
                fdump_api(['您的帐户热水余额不够此次支付' => __LINE__, 'hot_water_balance' => $now_village_user['hot_water_balance'], 'current_village_hot_water_balance' => $extra['current_village_hot_water_balance']],'after_pay/newPayServiceLog',1);
                throw new \Exception(L_("您的帐户热水余额不够此次支付"), 1003);
            }
            if(isset($extra['current_village_cold_water_balance'])&& $extra['current_village_cold_water_balance']>0 && $now_village_user['cold_water_balance']<$extra['current_village_cold_water_balance']){
                fdump_api(['您的帐户冷水余额不够此次支付' => __LINE__, 'cold_water_balance' => $now_village_user['cold_water_balance'], 'current_village_cold_water_balance' => $extra['current_village_cold_water_balance']],'after_pay/newPayServiceLog',1);
                throw new \Exception(L_("您的帐户冷水余额不够此次支付"), 1003);
            }
            if(isset($extra['current_village_electric_balance'])&& $extra['current_village_electric_balance']>0 && $now_village_user['electric_balance']<$extra['current_village_electric_balance']){
                fdump_api(['您的帐户冷电费余额不够此次支付' => __LINE__, 'electric_balance' => $now_village_user['electric_balance'], 'current_village_electric_balance' => $extra['current_village_electric_balance']],'after_pay/newPayServiceLog',1);
                throw new \Exception(L_("您的帐户冷电费余额不够此次支付"), 1003);
            }
        }
        //-------小区住户余额end--------//
        $messageDatas['order_id']=$order_id;
        if($order_list && count($order_list)>1){
            $messageDatas['type']=1;
            $messageDatas['summary_id']=$order_id;
        }
        if (!isset($extra['current_village_balance'])){
            $extra['current_village_balance']=0;
        }
        $park_new_type_arr=array();
        if (isset($extra['is_own']) && intval($extra['is_own']) >= 1) {
            $is_new_own = 1;
        } else {
            $is_new_own = 2;
        }

        $configCustomizationService=new ConfigCustomizationService();
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
        $car_number='';
        if (isset($cashier_order['pay_bind_id']) && $cashier_order['pay_bind_id']) {
            $village_bind = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$cashier_order['pay_bind_id']]);
        } else {
            $village_bind = [];
        }
        $name  = isset($village_bind['name'])  ? $village_bind['name']  : '';
        $phone = isset($village_bind['phone']) ? $village_bind['phone'] : '';
        $is_old_park_position=false;
        $yuanJino2oCustomize=$configCustomizationService->getYuanJino2oCustomized();
        if($yuanJino2oCustomize){
            $is_old_park_position=true;
        }
        $only_order_id    = 0;
        $car_number_arr   = [];// 车牌
        $car_stay_time    = 0;//  车辆停留时长 单位秒S
        $car_park_str     = '';// 车场名称
        $car_stored_price = 0;//  车辆储值金额
        $car_end_time     = 0;//  车辆月租结尾时间
        $db_in_park = new InPark();
        $db_house_village_parking_garage = new HouseVillageParkingGarage();
        $messageDatas['order_type'] = [];
        $house_type_arr=array();
        $sub_order_ids=array();
        $business_name='';
        $tmp_order_type='';
        $tmp_order_type_v='';

        $sHouseVillageCarFlowService = new HouseVillageCarFlowService();
        $sCarHandleGetInfoService = new CarHandleGetInfoService();
        $houseVillageCheckauthApply = new HouseVillageCheckauthApply();
        $houseVillageCheckauthDetail = new HouseVillageCheckauthDetail();
        $worksOrderPayOrderIds = [];
        $ishavepropertyfee=0;
        $ticket_number='';
        foreach ($order_list as $key => $now_order) {
            if (isset($now_order['from']) && $now_order['from'] == 4) {
                $worksOrderPayOrderIds[] = $now_order['order_id'];
            }
            $isHang = false;
            $is_discard = isset($now_order['is_discard']) ? intval($now_order['is_discard']) : 0;
            $hang_time = isset($now_order['hang_time']) ? $now_order['hang_time'] : 0;
            if ($is_discard == 3 && $hang_time > 1) {
                $isHang = true;
            }
            if(isset($now_order['ticket_number']) && $now_order['ticket_number']){
                $ticket_number=$now_order['ticket_number'];
            }
            //将订单未审核的作废申请全部拒绝
            $discardApply = $houseVillageCheckauthApply->getList([['order_id','=',$now_order['order_id']],['xtype','=','order_discard'],['status','in',[0,1]]],'id');
            if($discardApply){
                $idAry = array_column($discardApply,'id');
                $houseVillageCheckauthApply->updateApply([['id','in',$idAry]],['status' => 3, 'apply_time' => time()]);
                $houseVillageCheckauthDetail->updateDetail([['apply_id','in',$idAry]],['status' => 2, 'bak'=>'订单已支付', 'apply_time' => time()]);
            }
            $only_order_id = $now_order['order_id'];
            fdump_api(['多订单' => __LINE__,'now_order' => $now_order],'after_pay/newPayServiceLog',1);
            $village_id=$now_order['village_id'];
            if($now_order['order_type']){
                $house_type_arr[]=$now_order['order_type'];
            }
            if($now_order['position_id']>0){
                $record_extra_data['car_position_id']=$now_order['position_id'];
            }
            if(isset($now_order['phone']) && $now_order['phone']){
                $save_paid_order_record['u_phone']=$now_order['phone'];
            }
            if(isset($now_order['pay_bind_phone']) && $now_order['pay_bind_phone']){
                $save_paid_order_record['u_phone']=$now_order['pay_bind_phone'];
            }
            if(isset($now_order['name']) && $now_order['name']){
                $save_paid_order_record['u_name']=$now_order['name'];
            }
            if(isset($now_order['pay_bind_name']) && $now_order['pay_bind_name']){
                $save_paid_order_record['u_name']=$now_order['pay_bind_name'];
            }
            $business_name=$now_order['order_name'];
            $sub_order_ids[]=$now_order['order_id'];
            if($is_grapefruit_prepaid==1 && $now_order['rate']>0 && $now_order['diy_type']==1 && $now_order['diy_content'] && strpos($now_order['diy_content'],'预缴优惠')!==false) {
                $now_order['pay_money'] = ($now_order['pay_money'] * $now_order['rate']) / 100;
                $now_order['pay_money'] = round($now_order['pay_money'], 2);
            }
            if (!$name && isset($now_order['name']) && $now_order['name']) {
                $name = $now_order['name'];
            }
            if (!$phone && isset($now_order['phone']) && $now_order['phone']) {
                $phone = $now_order['phone'];
            }
            $offline_pay_money_tmp=0;
            $tmp_extra_data=isset($now_order['discount_info']) && $now_order['discount_info'] ? json_decode($now_order['discount_info'],true):array();
            $rule_discount_rate=0;
            $rule_discount_info=array();
            if($tmp_extra_data && isset($tmp_extra_data['rule_discount']) && !empty($tmp_extra_data['rule_discount']) && isset($tmp_extra_data['rule_discount']['rate'])){
                $rule_discount_rate=$tmp_extra_data['rule_discount']['rate'];
                $rule_discount_info=$tmp_extra_data['rule_discount'];
            }
            if($rule_discount_rate>0 && $rule_discount_info && $rule_discount_info['diy_type']==1){
                $now_order['pay_money']=($now_order['pay_money']*$rule_discount_rate)/100;
                $now_order['pay_money']=round($now_order['pay_money'],2);
            }
            if (isset($order_list[$key+1])){
                //实际线上支付的金额
                $tmp_pay_money=$extra['paid_money']*($now_order['pay_money']/$cashier_order['pay_money'])*100;
                $pay_money=round($tmp_pay_money);
                //实际积分抵扣的金额
                $score_deducte=round_number($extra['current_score_deducte']*($now_order['pay_money']/$cashier_order['pay_money']),2);
                //实际余额支付的金额
                $system_balance=round_number($extra['current_system_balance']*($now_order['pay_money']/$cashier_order['pay_money']),2);
                //实际小区余额支付的金额
                $village_balance=round_number($current_village_balance*($now_order['pay_money']/$cashier_order['pay_money']),2);
                //积分抵扣的数量
                $score_used_count_tmp=$extra['current_score_use']*($now_order['pay_money']/$cashier_order['pay_money']);
                $score_used_count=round($score_used_count_tmp,2);
                $offline_pay_money_tmp=round_number($offline_pay_money*($now_order['pay_money']/$cashier_order['pay_money']),2);
                $totle_money=$totle_money+$pay_money;
                $totle_score_deducte=$totle_score_deducte+$score_deducte;
                $totle_system_balance=$totle_system_balance+$system_balance;
                $totle_village_balance=$totle_village_balance+$village_balance;
                $totle_score_used_count=$totle_score_used_count+$score_used_count;
                $totle_offline_pay_money=$totle_offline_pay_money+$offline_pay_money_tmp;
            }else{
                $tmp_pay_money=$extra['paid_money']*100-$totle_money;
                $pay_money=round($tmp_pay_money);
                //实际积分抵扣的金额
                $score_deducte=round_number(($extra['current_score_deducte']-$totle_score_deducte),2);
                //实际余额支付的金额
                $system_balance=round_number(($extra['current_system_balance']-$totle_system_balance),2);
                //实际小区余额支付的金额
                $village_balance=round_number(($current_village_balance-$totle_village_balance),2);
                //积分抵扣的数量
                $score_used_count_tmp=$extra['current_score_use']-$totle_score_used_count;
                $score_used_count=round($score_used_count_tmp,2);
                $offline_pay_money_tmp=$offline_pay_money-$totle_offline_pay_money;
                $offline_pay_money_tmp=round($offline_pay_money_tmp,2);
            }
            // 跟新订单状态
            $date_order = [];
            $date_order['pay_time'] = time();
            $date_order['is_paid']  = 1;
            $date_order['is_own']   = $is_new_own;
            $date_order['pay_type'] = $cashier_order['pay_type'];
            $date_order['score_used_count'] = $score_used_count;
            $date_order['score_deducte'] = $score_deducte;
            if($offline_pay_money_tmp>0){
                $date_order['offline_pay_money']=$offline_pay_money_tmp;
                $date_order['order_pay_type']='off_mix_pay';
            }
            if ($updateSummaryId > 0) {
                $date_order['summary_id'] = $updateSummaryId;
            }
            //$now_order = $now_order->toArray();
            $tmp_order = [];
            $tmp_order['plat_order_id'] = $plat_order_id;
            $tmp_order['score_used_count'] = $date_summary_order['score_used_count'] ? $date_summary_order['score_used_count'] : 0;
            $tmp_order['score_can_get'] =$score_can_get ? $score_can_get : 0;
            $tmp_order['summary_id'] = $cashier_order['summary_id'];
            $tmp_order['is_own'] = $plat_order_info['is_own'];
            /*$tmp_order['payment_money'] = $plat_order_info['pay_money'];
            $tmp_order['score_deducte'] = $plat_order_info['current_score_deducte'];
            $tmp_order['balance_pay'] = $plat_order_info['current_system_balance'];*/
            $tmp_order['payment_money'] = round_number($pay_money/100,2);
            $tmp_order['score_deducte'] = $score_deducte;
            $tmp_order['score_deducte_money'] = $score_deducte;
            $tmp_order['balance_pay'] =$system_balance;
            $tmp_order['order_type'] = 'village_new_pay';
            $tmp_order['desc'] = '社区缴费';
            $service_start_time = $now_order['service_start_time'];
            $service_end_time = $now_order['service_end_time'];
            $rule_info = $service_house_new_charge_rule->getCallInfo(['r.id'=>$now_order['rule_id']],'n.charge_type,r.*,p.type');
            $service_time_difference=$now_order['service_end_time']-$now_order['service_start_time'];
            $service_time_difference=$service_time_difference>0 ? $service_time_difference:0;
            if($now_order['order_type'] == 'non_motor_vehicle'){ // 非机动车缴费
                $tmp_order_type='non_motor_vehicle';
                // 更新非机动车卡到期时间
                $village_nmv_card->updateCardInfo(['id' => $now_order['position_id']],['expiration_time' => $service_end_time]);
                $send_nmv_card = new CloudIntercomService();
                $send_nmv_card->sendNmvToDevice($now_order['position_id']);
            }
            elseif($now_order['order_type'] == 'pile'){ // 汽车充电桩缴费
                $db_house_new_pile_user_money=new HouseNewPileUserMoney();
                $db_house_new_pile_user_money_log=new HouseNewPileUserMoneyLog();
                $user_money_info=$db_house_new_pile_user_money->getOne(['business_id'=>$now_order['village_id'],'uid'=>$now_order['uid']]);
                if (empty($user_money_info)){
                    $user_money_info['current_money']=0;
                    $arr_add=[
                        'uid'=>$now_order['uid'],
                        'business_id'=>$now_order['village_id'],
                        'business_type'=>1,
                        'current_money'=>$now_order['modify_money'],
                        'add_time'=>time(),
                        'update_time'=>time(),
                        'remarks'=>'用户充值',
                    ];
                    $current_money=$now_order['modify_money'];
                    $update_money=$db_house_new_pile_user_money->addOne($arr_add);
                }else{
                    $current_money=$user_money_info['current_money']+$now_order['modify_money'];
                    $update_money=$db_house_new_pile_user_money->saveOne(['uid'=>$now_order['uid'],'business_id'=>$now_order['village_id'],'business_type'=>1],['current_money'=>$current_money]);
                }
                $tmp_order_type='car_pile';
                if ($update_money>0){
                    $log_data=[
                        'uid'=>$now_order['uid'],
                        'business_type'=>1,
                        'business_id'=>$now_order['village_id'],
                        'type'=>1,
                        'current_money'=>$user_money_info['current_money'],
                        'money'=>$now_order['modify_money'],
                        'after_price'=>$current_money,
                        'add_time'=>time(),
                        'role_id'=>isset($now_order['role_id'])?$now_order['role_id']:0,
                        'ip'=>get_client_ip(),
                        'desc'=>'用户充值',
                        'order_id'=>isset($now_order['order_id'])?$now_order['order_id']:0,
                        'order_type'=>isset($now_order['order_type'])?$now_order['order_type']:1,
                    ];
                    $db_house_new_pile_user_money_log->addOne($log_data);
                }
            }
            elseif($now_order['order_type'] == 'park_new'){ // 新版停车费
                $db_House_new_parking=new HouseNewParkingService();
                $db_house_village_park_config=new HouseVillageParkConfig();
                $db_house_village_parking_car=new HouseVillageParkingCar();
                $db_house_village_parking_position=new HouseVillageParkingPosition();
                $db_park_passage=new ParkPassage();
                $db_house_village=new HouseVillage();
                $db_park_system=new ParkSystem();
                $db_park_total_record=new ParkTotalRecord();
                $service_house_new_charge_prepaid=new HouseNewChargePrepaid();
                $park_sys_type='';
                $house_village =$db_house_village->getOne($now_order['village_id'],'village_name');
                $park_system =$db_park_system->getFind(['park_id'=>$now_order['village_id']]);
                //月租车缴费
                $park_new_type_arr[]=$now_order['car_type'];
                $car_number = isset($now_order['car_number']) && $now_order['car_number'] ? trim($now_order['car_number']) : '';
                $car_id = isset($now_order['car_id']) && $now_order['car_id'] ? intval($now_order['car_id']) : 0;
                if ($car_number) {
                    $car_number_arr[]  = $car_number;
                }
                $tmp_order_type=$now_order['car_type'] ? $now_order['car_type']:'';
                $tmp_order_type_v=$car_number;
                $free_park_time=900;  //转成秒
                $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
                if ($now_order['car_type']=='month_type') {
                    $prepaid_info = $service_house_new_charge_prepaid->getList(['charge_rule_id' => $now_order['rule_id']]);
                    $parking_position = array();
                    $parking_car = array();
                    $garage_id = 0;
                    $position_id=0;
                    if ($now_order['position_id'] > 0) {
                        $parking_position = $sCarHandleGetInfoService->getCarPosition($now_order['position_id'], $village_id);
                        $position_id=$now_order['position_id'];
                    } elseif ($car_id > 0) {
                        $whereCars = [];
                        $whereCars[] = ['car_id', '=', $car_id];
                        $whereCars[] = ['village_id', '=', $village_id];
                        $parking_car_obj = $db_house_village_parking_car->getNewList($whereCars, '*', 0, 20, 'car_id DESC');
                        $car_position_id=0;
                        if ($parking_car_obj && !$parking_car_obj->isEmpty()) {
                            $parking_car = $parking_car_obj->toArray();
                            $garage_id = isset($parking_car['0']['garage_id']) && $parking_car['0']['garage_id'] > 0 ? $parking_car['0']['garage_id'] : 0;
                            $car_position_id= isset($parking_car['0']['car_position_id']) && $parking_car['0']['car_position_id'] > 0 ? $parking_car['0']['car_position_id'] : 0;
                        }
                        if($car_position_id>0){
                            $position_id=$car_position_id;
                            $parking_position = $sCarHandleGetInfoService->getCarPosition($position_id, $village_id);
                        }
                        fdump_api(['新版停车费' => __LINE__,'park_sys_type' => $park_sys_type,'parking_position' => $parking_position,'parking_car' => $parking_car && !is_array($parking_car) ? $parking_car->toArray() : $parking_car],'after_pay/newPayServiceLog',1);
                    }
                    if (!empty($parking_position)) {
                        $position_id=$parking_position['position_id'];
                        $whereArr = array(['village_id', '=', $now_order['village_id']]);
                        $whereRaw = '(car_position_id =' . $parking_position['position_id'] . ' OR FIND_IN_SET(' . $parking_position['position_id'] . ',bind_position_ids))';
                        $parking_car_obj = $db_house_village_parking_car->getNewList($whereArr, '*', 0, 20, 'car_id DESC', $whereRaw);
                        if ($parking_car_obj && !$parking_car_obj->isEmpty()) {
                            $parking_car = $parking_car_obj->toArray();
                        }
                        $garage_id = $parking_position['garage_id'] > 0 ? $parking_position['garage_id'] : $garage_id;
                    } 
                    $park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $now_order['village_id']);
                    $park_sys_type = $park_config['park_sys_type'];
                    $park_position_set=array();
                    $channel_number_arr = $db_park_passage->getColumn(['village_id' => $now_order['village_id']], 'channel_number');
                    // $parking_car_single = D('House_village_parking_car')->where(array('car_id'=>$pay_order['car_id']))->find();
                    $channel_number_str = implode(',', $channel_number_arr);
                    if (!$channel_number_str) {
                        $channel_number_str = '';
                    }
                    $position_pattern = isset($parking_position['position_pattern']) ? $parking_position['position_pattern'] : 0;
                    fdump_api(['新版停车费' => __LINE__,'channel_number_str' => $channel_number_str,'position_pattern' => $position_pattern],'after_pay/newPayServiceLog',1);
                    if (2 != $position_pattern) {
                        // 固定车位-共同使用一个月卡会员编号
                        if ($parking_position['card_id']) {
                            $now_car_id = $parking_position['card_id'];
                        } else {
                            $now_car_id = '1' . sprintf("%09d", $parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                            $park_position_set['card_id'] = $now_car_id;
                        }
                        $address = $house_village['village_name'] . '车位：' . $parking_position['position_num'];
                    } else {
                        $address = $house_village['village_name'];
                    }

                    $dateStr = date('Y-m-d') . ' 00:00:00';
                    $tmp_parking_end_time = strtotime($dateStr);
                    $dateStartTimeStr = date('Y-m-d',$now_order['service_start_time']);
                    $start_time_xtmp=strtotime($dateStartTimeStr.' 00:00:00');
                    $park_end_time = $now_order['service_start_time'] > 100 ? $start_time_xtmp : $tmp_parking_end_time;
                    // 如果是虚拟停车位 对应每一个车生成唯一收费编号
                    $parking_car_type_arr = $db_House_new_parking->parking_car_type_arr;
                    fdump_api([$parking_car, $parking_position, $now_order], 'D3park/mouth_type_0718', 1);
                    if ($parking_position['end_time'] > $park_end_time) {
                        $dateStr = date('Y-m-d', $parking_position['end_time']) . ' 23:59:59';
                        $park_end_time = strtotime($dateStr) + 1;
                    }
                    $start_time_stamp=$park_end_time+1;
                    if ($parking_position['start_time'] > 100 && $parking_position['end_time']>=$tmp_parking_end_time) {
                        $start_time_stamp = $parking_position['start_time'];
                    }else if((empty($parking_position['start_time']) || $parking_position['start_time']<1) && $parking_position['end_time']>$tmp_parking_end_time){
                        $start_time_stamp = $tmp_parking_end_time;
                    }
                    $park_set = [];
                    $park_data['begin_time'] = $start_time_stamp;
                    $park_end_time=$park_end_time;
                    $park_data['end_time'] = $park_end_time;
                    if ($now_order['is_prepare'] == 1) {
                        if (!empty($prepaid_info)) {
                            $prepaid_info = $prepaid_info->toArray();
                        }
                        if (!empty($prepaid_info)) {
                            $prepaidInfo = $prepaid_info[0];
                            if ($prepaidInfo['give_cycle_datetype'] == 1) {
                                $park_end_time = strtotime("+" . $now_order['service_give_month_num'] . " day", $park_end_time);
                            } elseif ($prepaidInfo['give_cycle_datetype'] == 2) {
                                $park_end_time = strtotime("+" . $now_order['service_give_month_num'] . " day", $park_end_time);
                            } elseif ($prepaidInfo['give_cycle_datetype'] == 3) {
                                $park_end_time = strtotime("+" . $now_order['service_give_month_num'] . " day", $park_end_time);
                            }
                        }

                        $cycle = $now_order['service_month_num'];
                    } else {
                        $cycle = $now_order['service_month_num'] ? $now_order['service_month_num'] : 1;
                    }
                    if ($rule_info['bill_create_set'] == 1) {
                        $park_data['end_time'] = strtotime("+" . $cycle . " day", $park_end_time);
                    } elseif ($rule_info['bill_create_set'] == 2) {
                        //todo 判断是不是按照自然月来生成订单
                        if (cfg('open_natural_month') == 1) {
                            $park_data['end_time'] = strtotime("+" . $cycle . " month", $park_end_time);
                            $start_d=date('d',$park_end_time);
                            $tmp_service_end_time=strtotime("+$cycle month",$park_end_time);
                            $end_d=date('d',$tmp_service_end_time);
                            if($start_d!=$end_d){
                                $end_date=date('Y-m',$tmp_service_end_time).'-01 00:00:00';
                                $end_date_time=strtotime($end_date)-1;
                                $park_data['end_time']=$end_date_time;
                            }
                        } else {
                            $cycle = $cycle * 30;
                            $park_data['end_time'] = strtotime("+" . $cycle . " day", $park_end_time);
                        }
                    } else {
                        $park_data['end_time'] = strtotime("+" . $cycle . " year", $park_end_time);
                    }
                    $park_data['end_time']=$park_data['end_time']-1;
                    $park_position_set['start_time']= $park_data['begin_time'];
                    $park_position_set['end_time'] = $park_data['end_time'];
                    $car_end_time = $park_data['end_time'];
                    $db_house_village_parking_position->saveOne(['position_id' => $position_id], $park_position_set);
                    if (!empty($parking_car)) {
                        foreach ($parking_car as $k => $v) {
                            $park_set['start_time'] = $park_data['begin_time'];
                            $park_set['end_time'] = $park_data['end_time'];
                        if ($park_sys_type!='D1') {
                            $park_data['park_id'] = $v['village_id'];
                        }
                        $park_data['car_number'] = $v['province'].$v['car_number'];
                        if (!$car_number) {
                            $car_number_arr[]  = $park_data['car_number'];
                        }
                        $park_position_set['start_time'] = $park_data['begin_time'];
                        $park_position_set['end_time'] = $park_data['end_time'];
                        if($park_sys_type =='D5'){
                            //todo D5智慧停车 触发下发到设备
                                (new D5Service())->D5AddCar($v['village_id'], $v['card_id'], $park_set['start_time'], $park_set['end_time'], 'mobile', $garage_id);
                        }elseif ($park_sys_type =='D6'){
                            //todo D6智慧停车 触发下发到设备
                            fdump_api(['D6智慧停车续费成功' => __LINE__, 'v' => $v], 'after_pay/newPayServiceLog', 1);
                            (new D6Service())->d6synVehicle($v['village_id'],$v['card_id'],0,$park_set);
                        }
                        //D3同步白名单到设备上
                        $white_record=[
                            'village_id'=>$v['village_id'],
                            'car_number'=>$park_data['car_number']
                        ];
                        (new HouseVillageParkingService())->addWhitelist($white_record);
                        // 3.7月卡会员同步（停车云）
                        $park_data['remark'] = '';
                        $park_data['pid'] = '';
                        $service_name = 'month_member_sync';// 添加会员卡操作
                        if ($now_car_id) {
                            $card_id = $now_car_id;
                        } else {
                            $card_id = '2'.sprintf("%09d",$v['car_id']);//月卡会员编号（收费系统唯一编号  是
                        }
                        // 同步储值卡
                        if ($parking_car_type_arr && $v['parking_car_type'] && $parking_car_type_arr[$v['parking_car_type']]) {
                            $car_type_id = $parking_car_type_arr[$v['parking_car_type']];
                        } else if($v['temporary_car_type']) {
                            $car_type_id = $v['store_value_car_type'];
                        } else {
                            $car_type_id = '储值车A';
                        }
                        $park_set['card_id'] = $card_id;
                        if ($park_sys_type=='D1' && $v['card_id']) {
                            $park_data['is_edit'] = 1;
                        }
                        elseif ($park_sys_type!='D1') {
                            $park_data['operate_type'] = $v['card_id'] ? '2' : '1';	//1 添加，2 编辑
                            if ($park_data['operate_type']==1) {
                                $park_data['create_time'] = time();
                            } else {
                                $park_data['update_time'] = time();
                            }
                            $park_data['price'] = $now_order['pay_money'];//实收金额
                            $park_data['channel_id'] = $channel_number_str;//月卡允许通行的通道编号
                            $park_data['amount_receivable'] = $now_order['pay_money'];//应收金额
                        }
                        fdump_api(['line' => __LINE__, 'park_sys_type' => $park_sys_type, 'car_id' => $v['car_id'], 'park_set' => $park_set], 'after_pay/newPayServiceLog', 1);
                        if($park_sys_type=='D7'){
                            if (!empty($v['parking_car_type'])&&$v['parking_car_type']<17){
                                $d7_data=[];
                                $d7_data['park_id']=$park_config['d7_park_id'];
                                $d7_data['operate']=2;
                                $d7_data['vehicleId']=$v['vehicleId'];
                                $d7_data['vehicleNo']=$v['province'].$v['car_number'];
                                $d7_data['carTypeCode']=$db_House_new_parking->parking_D7_car_type_value[$v['parking_car_type']]['value'];
                                if (!empty($v['car_user_name'])){
                                    $d7_data['userName']=$v['car_user_name'];
                                }
                                if (!empty($v['car_user_phone'])){
                                    $d7_data['tels']=$v['car_user_phone'];
                                }
                                if (!empty($park_set['end_time'])){
                                    $d7_data['beginTime']=time();
                                    $d7_data['endTime']=$park_set['end_time'];
                                }
                                $res_whiteList=(new QinLinCloudService())->whiteList($d7_data);
                                fdump_api(['line' => __LINE__, 'd7_data' => $d7_data, 'res_whiteList' => $res_whiteList], 'after_pay/newPayServiceLog', 1);
                                $res_park=$db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$v['car_id']],$park_set);
                                fdump_api(['line' => __LINE__, 'res_park' => $res_park, 'park_position_set' => $park_position_set, 'position_id' => $parking_position['position_id']], 'after_pay/newPayServiceLog', 1);
                                    /*
                                if ($park_position_set) {
                                    $db_house_village_parking_position->saveOne(['position_id'=>$v['car_position_id']],$park_position_set);
                                }
                                    */
                            }
                        }elseif($park_sys_type=='D3'){
                            $res_park=$db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$v['car_id']],$park_set);
                            fdump_api(['line' => __LINE__, 'res_park' => $res_park, 'park_position_set' => $park_position_set, 'position_id' => $parking_position['position_id']], 'after_pay/newPayServiceLog', 1);
                                /*
                            if ($park_position_set) {
                                $parking_position_info =$db_house_village_parking_position->getFind(['position_id'=>$v['car_position_id']]);
                                if (!empty($parking_position_info)){
                                    $db_house_village_parking_position->saveOne(['position_id'=>$v['car_position_id']],$park_position_set);
                                }

                            }
                                */
                        }else{
                            $park_data['name'] = $v['car_user_name'];
                            $park_data['car_type_id'] = $car_type_id;
                            $park_data['tel'] = $v['car_user_phone'] ? $v['car_user_phone'] : '';
                            $park_data['address'] = $address;
                            $park_data['p_lot'] = strval($parking_position['position_num']);

                                $park_data['card_id'] = $card_id;
                                //2021/2/1 多车多位  修改 start
                                /*$infos = D('House_village_parking_car')->bind_parking_position($card_id);
                                if($infos){
                                    $park_data['car_group'] = intval($infos['car_group']);
                                    $park_data['p_lot_number'] = intval($infos['p_lot_number']);
                                }*/
                                //2021/2/1 多车多位  修改 end
                                $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                                $json_data['data'] = $park_data;
                                $json_data['service_name'] = $service_name;

                                $post_data['unserialize_desc'] = serialize($json_data);
                                $post_data['status'] = 2;
                                $post_data['card_id'] = $card_id;
                                $post_data['msg_id'] = $json_data['msg_id'];
                                $post_data['village_id'] = $v['village_id'];
                                $post_data['park_id'] = $park_system['park_id'];
                                $post_data['token'] = $park_system['token'];
                                $post_data['car_number'] = $park_data['car_number'] ? $park_data['car_number'] : '';
                                $post_data['service_name'] = $service_name;
                                $post_data['create_time'] = time();
                                $res = $db_park_total_record->add($post_data);
                                // 没有配置停车场，或者数据添加成功都保存车辆信息。
                                if (!($park_system['park_id'] && $park_system['token']) || $res) {
                                    $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $v['car_id']], $park_set);
                                    /*
                                    if ($park_position_set) {
                                        $db_house_village_parking_position->saveOne(['position_id' => $parking_position['position_id']], $park_position_set);
                                    }
                                    */
                                } else {
                                    //2021/2/1 多车多位  修改 start
                                    /* $infos = D('House_village_parking_car')->bind_parking_position($card_id);
                                     if($infos){
                                         $park_data['car_group'] = intval($infos['car_group']);
                                         $park_data['p_lot_number'] = intval($infos['p_lot_number']);
                                     }*/
                                    //2021/2/1 多车多位  修改 end
                                    $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                                    $json_data['data'] = $park_data;
                                    $json_data['service_name'] = $service_name;
                                    $post_data['unserialize_desc'] = serialize($json_data);
                                    $post_data['status'] = 2;
                                    $post_data['card_id'] = $card_id;
                                    $post_data['msg_id'] = $json_data['msg_id'];
                                    $post_data['village_id'] = $v['village_id'];
                                    $post_data['park_id'] = $park_system['park_id'];
                                    $post_data['token'] = $park_system['token'];
                                    $post_data['car_number'] = $park_data['car_number'] ? $park_data['car_number'] : '';
                                    $post_data['service_name'] = $service_name;
                                    $post_data['create_time'] = time();
                                    $res = $db_park_total_record->add($post_data);//月卡会员下发
                                    if ($res) {
                                        $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $v['car_id']], $park_set);
                                        /*
                                        if ($park_position_set) {
                                            $db_house_village_parking_position->saveOne(['position_id' => $parking_position['position_id']], $park_position_set);
                                        }
                                        */
                                    }
                                }
                            }
                            if($v['equipment_no'] && isset($v['add_cfrom']) && $v['add_cfrom']=='third_ai_park'){
                                try {
                                    $houseNewAiParkingService =  new \app\community\model\service\HouseNewAiParkingService();
                                    $dataCarTmpArr=array('park_key'=>$v['equipment_no'],'village_id'=>$v['village_id'],'cfrom'=>'month_afterpay');
                                    $houseNewAiParkingService->synUpdateThirdAiParkCarTime($v['car_id'],$dataCarTmpArr);
                                }catch (\Exception $ee){
                                    fdump_api(['line' => __LINE__,'car_number'=>$car_number,'car_id'=>$v['car_id'],'msg' => $ee->getMessage()], 'after_pay/newPayServiceLog', 1);
                                }
                            }
                            //子母车位续费同步到期时间
                            if ($park_config['children_position_type'] == 1) {
                                $parking_position_arr = $db_house_village_parking_position->getColumn(['parent_position_id' => $v['car_position_id'], 'garage_id' => $v['garage_id'], 'children_type' => 2], 'position_id');
                                if (!empty($parking_position_arr)) {
                                    $db_house_village_parking_position->saveOne(['position_id' => $parking_position_arr], $park_position_set);
                                    $park_car_arr = $db_house_village_parking_car->get_column(['car_position_id' => $parking_position_arr], 'car_id');
                                    if (!empty($park_car_arr)) {
                                        $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $park_car_arr], $park_set);
                                    }
                                }
                            }
                        }
                    }
                    $sCarHandleGetInfoService->clearCarPositionInfo($village_id);
                    $sCarHandleGetInfoService->clearCarInfo($village_id);
                }
                elseif($now_order['car_type'] == 'stored_type'){ //储值
                    $address = $house_village['village_name'];
                    $parking_car =$db_house_village_parking_car->getOne(['car_id'=>$now_order['car_id']]);
                    $stored_balance=$parking_car['stored_balance'];
                    $parking_position = $db_house_village_parking_position->getFind(['position_id' => $parking_car['car_position_id']],'garage_id');
                    $garage_id=0;
                    if($parking_position && $parking_position['garage_id']>0){
                        $garage_id=$parking_position['garage_id'];
                    }
                    $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$now_order['village_id']);
                    $park_sys_type=$park_config['park_sys_type'];
                    
                    if ($park_sys_type=='D7'){
                        if ($parking_car['parking_car_type']>=17&&$parking_car['parking_car_type']<=20){
                            $car_info=(new QinLinCloudService())->queryRentVehicle($park_config['d7_park_id'],$parking_car['province'].$parking_car['car_number']);
                            fdump_api(['line' => __LINE__, 'car_info' => $car_info], 'after_pay/newPayServiceLog', 1);
                            if ($car_info['state']==200&&!empty($car_info['data'])){
                                $carInfo=json_decode($car_info['data'],true);
                                $stored_balance=$carInfo['balance'];
                            }
                        }

                    }
                    $car_stored_price = $stored_balance+$now_order['modify_money'];
                    $service_house_new_cashier->saveOrder(['order_id'=>$now_order['order_id']],['stored_balance'=>$car_stored_price]);
                    $nowtime = date("mdHis");
                    $trade_no = $nowtime . sprintf("%04d", $order_id);
                    $res_balance=$db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$parking_car['car_id']],['stored_balance'=>$car_stored_price]);
                    fdump_api(['对应车辆储值余额变动' => __LINE__, 'res_balance' => $res_balance, 'car_number' => $parking_car['car_number'], 'now_order' => $now_order, 'stored_balance0' => $parking_car['stored_balance'],'stored_balance' => $stored_balance,'modify_money' => $now_order['modify_money'],'stored_balance1' => $car_stored_price], 'after_pay/newPayServiceLog', 1);
                    if ($res_balance&&$park_sys_type=='D7'&&$parking_car['parking_car_type']>=17&&$parking_car['parking_car_type']<=20){
                        $d7_data=[];
                        $d7_data['park_id']=$park_config['d7_park_id'];
                        $d7_data['operate']=2;
                        $d7_data['balance']=$car_stored_price;
                        $d7_data['vehicleId']=$parking_car['vehicleId'];
                        $d7_data['vehicleNo']=$parking_car['province'].$parking_car['car_number'];
                        $d7_data['carTypeCode']=$db_House_new_parking->parking_D7_car_type_value[$parking_car['parking_car_type']]['value'];
                        if (!empty($parking_car['car_user_name'])){
                            $d7_data['userName']=$parking_car['car_user_name'];
                        }
                        if (!empty($parking_car['car_user_phone'])){
                            $d7_data['tels']=$parking_car['car_user_phone'];
                        }
                        if (!empty($parking_car['end_time'])){
                            $d7_data['beginTime']=time();
                            $d7_data['endTime']=$parking_car['end_time'];
                        }
                        fdump_api(['line' => __LINE__, 'd7_data' => $d7_data], 'after_pay/newPayServiceLog', 1);
                        $res_whiteList=(new QinLinCloudService())->whiteList($d7_data);
                        fdump_api(['line' => __LINE__, 'res_whiteList' => $res_whiteList], 'after_pay/newPayServiceLog', 1);
                    }
                    if ($parking_car['stored_card']) {
                        // 储值卡续充
                        $service_name = 'prepay_card_trade_sync';
                        $park_data['trade_no'] = $trade_no;
                        $park_data['card_id'] = $parking_car['stored_card'];
                        $park_data['pay_time'] = $now_order['pay_time'] ? $now_order['pay_time'] : time();
                        $park_data['pay_type'] = $now_order['pay_type']!=3?'线上支付':'现金';
                        $park_data['total_price'] = $now_order['pay_money'];
                        $park_data['car_number'] = $parking_car['province'].$parking_car['car_number'];
                        $park_data['mobile'] = $parking_car['car_user_phone'];
                        $park_data['park_id'] = $park_system['park_id'];
                        $park_data['name'] = $parking_car['car_user_name'];
                        $json_data['service_name'] = $service_name;
                        $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                        $json_data['data'] = $park_data;
                        $post_data['unserialize_desc'] = serialize($json_data);
                        $post_data['park_id'] = $park_system['park_id'];
                        $post_data['token'] = $park_system['token'];
                        $post_data['service_name'] = $service_name;
                        $post_data['car_number'] = $parking_car['province'].$parking_car['car_number'];
                        $post_data['village_id'] = $now_order['village_id'];
                        $post_data['msg_id'] = $json_data['msg_id'];
                        $post_data['stored_card'] = $park_data['card_id'];
                        $post_data['order_id'] = $order_id;
                        $post_data['car_id'] = isset($now_order['car_id'])?intval($now_order['car_id']):0;
                        $post_data['create_time'] = time();
                        $iddd=$db_park_total_record->add($post_data);//储值卡续充同步
                        fdump_api([ __LINE__.'prepay_card_trade_sync','iddd'=>$iddd,'car_number'=>$park_data['car_number'],'post_data'=>$post_data], 'after_pay/newPayServiceLog', 1);
                    } else {
                        $parking_car_type_arr = $db_House_new_parking->parking_car_type_arr;
                        // 同步储值卡
                        if ($parking_car_type_arr && $parking_car['parking_car_type'] && $parking_car_type_arr[$parking_car['parking_car_type']]) {
                            $car_type_id = $parking_car_type_arr[$parking_car['parking_car_type']];
                        } else if($parking_car['temporary_car_type']) {
                            $car_type_id = $parking_car['store_value_car_type'];
                        } else {
                            $car_type_id = '储值车A';
                        }
                        // 同步储值卡
                        $service_name = 'prepay_card_sync';
                        $park_data['operate_type'] = 1;
                        $park_data['create_time'] = time();
                        $park_data['total_price'] = $now_order['pay_money'];
                        $park_data['car_number'] = $parking_car['province'].$parking_car['car_number'];
                        $park_data['mobile'] = $parking_car['car_user_phone'];
                        $park_data['name'] = $parking_car['car_user_name'];
                        $park_data['park_id'] = $park_system['park_id'];
                        $park_data['remark'] = '储值卡数据下发';
                        $park_data['card_id'] = '3_'.$park_system['park_id'].$parking_car['car_id'];//储值卡编号（收费系统唯一编号）
                        $park_data['car_type_id'] = $car_type_id;
                        $park_data['address'] = $address;
                        $json_data['service_name'] = $service_name;
                        $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                        $json_data['data'] = $park_data;
                        $post_data['unserialize_desc'] = serialize($json_data);
                        $post_data['park_id'] = $park_system['park_id'];
                        $post_data['car_number'] = $parking_car['province'].$parking_car['car_number'];
                        $post_data['token'] = $park_system['token'];
                        $post_data['service_name'] = $service_name;
                        $post_data['village_id'] = $now_order['village_id'];
                        $post_data['msg_id'] = $json_data['msg_id'];
                        $post_data['stored_card'] = $park_data['card_id'];
                        $post_data['car_id'] = isset($now_order['car_id'])?intval($now_order['car_id']):0;
                        $post_data['create_time'] = time();
                        $id = $db_park_total_record->add($post_data); //储值卡同步
                        fdump_api([ __LINE__.'prepay_card_trade_sync','iddd'=>$id,'car_number'=>$park_data['car_number'],'post_data'=>$post_data], 'after_pay/newPayServiceLog', 1);
                        $data_set = [
                            'stored_card' => $park_data['card_id']
                        ];
                        if ($id) {
                            $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$parking_car['car_id']],$data_set);
                        }
                    }
                    $sCarHandleGetInfoService->clearCarInfo($village_id);
                }
                elseif($now_order['car_type'] == 'temporary_type'){ //临时
                    $db_house_village_parking_temp=new HouseVillageParkingTemp();
                  
                    if($now_order['order_type_flag']=='third_ai_park_order' && !empty($now_order['extra_data'])){
                        $extra_data_arr=json_decode($now_order['extra_data'],true);
                        $extra_data_arr['payedMoney']=$now_order['pay_money'];
                        $parking_temp_id= $extra_data_arr['parking_temp_id'];
                        $db_house_village_parking_temp->saveOne(['id'=>$parking_temp_id],['is_paid'=>1,'pay_time'=> time()]);
                        $aiHorseSzymzhService=  new \app\community\model\service\Park\AiHorseSzymzhService($now_order['village_id']);
                        $extra_data_arr['payed_sn']='22hv_'.$now_order['village_id'].'_'.$now_order['order_id'];
                        $retTemp=$aiHorseSzymzhService->payToUnVehicleOut($extra_data_arr['parkKey'],$extra_data_arr);
                        $retTemp['out_time']=date('Y-m-d H:i:s');
                        $extra_data_arr['out_ret']=$retTemp;
                        $date_order['extra_data']=json_encode($extra_data_arr,JSON_UNESCAPED_UNICODE);
                    }else{
                        
                    $db_park_plateresult_log=new ParkPlateresultLog();
                    $db_park_scrcu_record=new ParkScrcuRecord();
                    $db_park_open_log=new ParkOpenLog();
                    $car_number = $now_order['car_number'];
                    $village_id = $now_order['village_id'];
                    $whereTemp= [
                        ['car_number','=',$now_order['car_number']], ['village_id','=',$now_order['village_id']], ['is_paid', '=', 0]
                    ];
                    $houseVillageCarFlowService = new HouseVillageCarFlowService();
                    $whereNewInPark = [];
                    $whereNewInPark[] = ['pay_order_id', '=', $now_order['summary_id']];
                    $whereNewInPark[] = ['village_id',      '=', $village_id];
                    $park_where = [];
                    $park_where[] = ['pay_order_id', '=', $now_order['summary_id']];
                    $park_where[] = ['park_id',      '=', $village_id];
                    $car_stay_time=0;
                    $channel_id='';
                    fdump_api([__LINE__, 'car_number'=>$car_number],'after_pay/newPayServiceLog',1);
                    /*
                    $new_in_park_arr = $sCarHandleGetInfoService->getLastCarFlowRecord($car_number, $village_id, $park_sys_type, $whereNewInPark, $park_where);

                    if (isset($new_in_park_arr['in_time']) && $new_in_park_arr['in_time']) {
                        $car_stay_time = time() - $new_in_park_arr['in_time'];
                        $channel_id=!empty($new_in_park_arr['in_channel_id'])? $new_in_park_arr['in_channel_id']:0;
                        if($new_in_park_arr['park_sys_type']){
                            $park_sys_type=$new_in_park_arr['park_sys_type'];
                        }
                    }
                    */
                   // if ((!$car_stay_time || $car_stay_time<=0)) {
                        $where_log = [
                            ['car_number','=',$now_order['car_number']], ['park_type','=',2],
                        ];
                        $in_log_info = $db_park_plateresult_log->get_one($where_log, '*', 'id DESC');
                        if (isset($in_log_info['add_time']) && $in_log_info['add_time']) {
                            $car_stay_time = time() - $in_log_info['add_time'];
                            $channel_number=!empty($in_log_info['channel_id'])? $in_log_info['channel_id']:0;
                            if($in_log_info['park_sys_type']){
                                $park_sys_type=$in_log_info['park_sys_type'];
                            }
                        }
                    //}
                    fdump_api([__LINE__, 'car_number'=>$car_number],'after_pay/newPayServiceLog',1);
                    $wherePassage=array();
                    if(isset($channel_number) && $channel_number){
                        $wherePassage[]=array('device_number','=',$channel_number);
                    }elseif (isset($channel_id) && $channel_id){
                        $wherePassage[]=array('id','=',$channel_id);
                    }
                    $garage_id = 0;
                    if($wherePassage) {
                        $wherePassage[] = array('village_id', '=',  $now_order['village_id']);
                        $wherePassage[] = array('status', '=', 1);
                        $wherePassage[] = array('passage_direction', '<>', 1);
                        $passage_info_obj = $db_park_passage->getFind($wherePassage);
                        if($passage_info_obj && !$passage_info_obj->isEmpty()){
                            $passage_info=$passage_info_obj->toArray();
                            $park_sys_type=$passage_info['park_sys_type'];
                            if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
                                $garage_id=$passage_info['garage_id'];
                            }elseif($passage_info['passage_area'] && $passage_info['area_type']==2){
                                $garage_id=intval($passage_info['passage_area']);
                            }
                        }
                    }
                    fdump_api([__LINE__, 'car_number'=>$car_number],'after_pay/newPayServiceLog',1);
                    $park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $now_order['village_id']);
                    $park_sys_type = $park_config['park_sys_type'];
                    if($park_config && isset($park_config['free_park_time']) && $park_config['free_park_time']>0 ){
                        $free_park_time=$park_config['free_park_time'];
                        $free_park_time=$free_park_time>0 ? $free_park_time*60:900;  //转成秒
                    }
                    $parking_temp_id = 0;
                    $in_record_id = 0;
                    $out_record_id = 0;
                    if($park_sys_type=='D6'){
                        $whereTemp= [
                            ['car_number','=',$now_order['car_number']], ['village_id','=',$now_order['village_id']],
                            ['service_name','=','d6_charging'], ['state','=',1],
                        ];
                        $house_village_parking_temp =  $db_house_village_parking_temp->getFind($whereTemp);
                    } else {
                        fdump_api([__LINE__, 'car_number'=>$car_number],'after_pay/newPayServiceLog',1);
                        $house_village_parking_temp = $sCarHandleGetInfoService->getCarFlowTemp($car_number, $village_id, 0, $park_sys_type);
                        $flow_temp_id = isset($house_village_parking_temp['flow_temp_id']) ? intval($house_village_parking_temp['flow_temp_id']) : 0;
                        if ($flow_temp_id) {
                            $dbHouseVillageCarFlowTemp = new HouseVillageCarFlowTemp();
                            $dbHouseVillageCarFlowTemp->updateThis(['id'=>$flow_temp_id],['is_paid'=>1,'pay_time'=> time()]);
                        }
                    }
                    fdump_api([__LINE__, 'car_number'=>$car_number,'village_id'=>$village_id,'house_village_parking_temp' => $house_village_parking_temp,'park_sys_type' => $park_sys_type, 'park_config' => $park_config, 'garage_id' => $garage_id],'after_pay/newPayServiceLog',1);
                    if($house_village_parking_temp && isset($house_village_parking_temp['id']) && $house_village_parking_temp['id']>0){
                        $parking_temp_id=$house_village_parking_temp['id'];
                        $db_house_village_parking_temp->saveOne(['id'=>$house_village_parking_temp['id']],['is_paid'=>1,'pay_time'=> time()]);
                        $in_record_id = isset($house_village_parking_temp['in_record_id']) && $house_village_parking_temp['in_record_id'] ? $house_village_parking_temp['in_record_id'] : 0;
                        $out_record_id = isset($house_village_parking_temp['out_record_id']) && $house_village_parking_temp['out_record_id'] ? $house_village_parking_temp['out_record_id'] : 0;
                    }

                    if (!$car_number && isset($house_village_parking_temp['car_number']) && $house_village_parking_temp['car_number']) {
                        $car_number = $house_village_parking_temp['car_number'];
                    }
                    if ((!$car_stay_time || $car_stay_time<=0) && $car_number) {
                        $recordLogParam = [
                            'car_number' => $car_number, 'garage_id' => 0, 'flow_direction' => 1, 'park_sys_type' => $park_sys_type,
                        ];
                        $in_log_info = $sCarHandleGetInfoService->getFlowIdentifyRecord($village_id, $recordLogParam);
                        if (isset($in_log_info['add_time']) && $in_log_info['add_time']) {
                            $car_stay_time = time() - $in_log_info['add_time'];
                        }
                    }
                    $queueFeeData = array('handleType' => 'park_new_fee_temporary', 'village_id' => $now_order['village_id']);
                    $queueFeeData['order_id'] = $now_order['order_id'];
                    $queueFeeData['car_number'] = $car_number;
                    $queueFeeData['parking_temp_id'] = $parking_temp_id;
                    $queueFeeData['free_park_time'] = $free_park_time;
                    $later_time = $free_park_time + 100;
                    $queueFeeData['pay_current_time'] = time();
                    try {
                        $pushToQueueRet = $this->houseVillageAfterPayQueueLaterPushToJob($queueFeeData, $later_time);
                        fdump_api([__LINE__, 'pushToQueueRet' => $pushToQueueRet, $queueFeeData, $later_time,'park_sys_type'=>$park_sys_type], '222temporary_car_afterpay', 1);
                    } catch (\Exception $e) {
                        fdump_api([__LINE__, $e->getMessage(), 'queueFeeData' => $queueFeeData,'park_sys_type'=>$park_sys_type], '222temporary_car_afterpay', 1);
                    }
                    if ($park_sys_type=='D1') {
                        // d1上报缴费结果
                        $param = [];
                        $param['car_number'] = $now_order['car_number'];
                        $param['money'] = $now_order['money'];
                        $param['pay_time'] = time();
                        $param['order_id'] = $cashier_order['paid_orderid'];
                        $data_park_d1=[
                           'village_id'=>$now_order['village_id'],
                            'param'=>$param,
                        ];
                        $toPark = invoke_cms_model('cms/Lib/Model/ParkService\payNoticeToPark',$data_park_d1);
                    }
                    elseif ($park_sys_type=='D3'){
                        fdump_api(['line' => __LINE__, 'house_village_parking_temp' => $house_village_parking_temp], 'after_pay/newPayServiceLog', 1);
                        if($house_village_parking_temp){
                            fdump_api(['line' => __LINE__, 'now_order' => $now_order, 'car_number' => $car_number], 'after_pay/newPayServiceLog', 1);
                            $out_channel_id = $house_village_parking_temp['out_channel_id'] ? $house_village_parking_temp['out_channel_id']:'';
                            if($house_village_parking_temp['is_pay_scene'] == 1 || is_null($house_village_parking_temp['is_pay_scene'])){//直付
                                if($car_number && strpos($car_number,'临')!==false && $out_channel_id){
                                    $wherePassage[] = array('village_id', '=',  $now_order['village_id']);
                                    $wherePassage[] = array('device_number', '=', $out_channel_id);
                                    $wherePassage[] = array('passage_direction', '<>', 1);
                                    $passage_info_obj = $db_park_passage->getFind($wherePassage);
                                    if($passage_info_obj && !$passage_info_obj->isEmpty()){
                                        $park_log_data = [];
                                        $park_log_data['car_number'] = $car_number;
                                        $park_log_data['channel_id'] = $out_channel_id;
                                        $park_log_data['park_type'] = 2;
                                        $park_log_data['add_time'] = time();
                                        $park_log_data['open_type'] = 2;
                                        $log_id= $db_park_open_log->add($park_log_data);
                                        fdump_api(['加入开门指令并加入显屏' => __LINE__,'log_id' => $log_id,'park_log_data' => $park_log_data],'after_pay/newPayServiceLog',1);
                                        $passage_info=$passage_info_obj->toArray();
                                        $data_screen = [
                                            'passage' => $passage_info, 'car_type' => 'temporary_type', 'village_id' => $now_order['village_id'],
                                            'car_number' => $car_number, 'channel_id' => $out_channel_id,
                                            'content' => '请通行,祝您一路平安', 'voice_content' => 6
                                        ];
                                        (new CarFlowShowScreenService())->addParkShowScreenLog($data_screen);
                                    }
                                }else{
                                    $whereFlow = [];
                                    $whereLog = [];
                                    $limitTop = time() + 180;
                                    $limitLow = time() - 600;
                                    $whereFlow[] = ['flow_time', '>=', $limitLow];
                                    $whereFlow[] = ['flow_time', '<=', $limitTop];
                                    $whereLog[] = ['add_time', '>=', $limitLow];
                                    $whereLog[] = ['add_time', '<=', $limitTop];
                                    $recordLogParam = [
                                        'car_number' => $car_number, 'garage_id' => 0, 'flow_direction' => 2, 'park_sys_type' => $park_sys_type,
                                        'out_channel_id'=>$out_channel_id
                                    ];
                                    //$log_info= $db_park_plateresult_log->get_one($where_log);
                                    $log_info = $sCarHandleGetInfoService->getFlowIdentifyRecord($village_id, $recordLogParam);
                                    // 查询车最后一次短期识别记录 存在
                                    fdump_api(['查询车最后一次短期识别记录 存在' => __LINE__, 'log_info' => $log_info,'whereLog' => $whereLog, 'whereFlow' => $whereFlow, 'recordLogParam' => $recordLogParam],'after_pay/newPayServiceLog',1);

                                    if (!empty($log_info)) {
                                        $park_log_data = [];
                                        $park_log_data['car_number'] = $log_info['car_number'];
                                        $park_log_data['channel_id'] = $log_info['channel_id'];
                                        $park_log_data['park_type'] = $log_info['park_type'];
                                        $park_log_data['add_time'] = time();
                                        $park_log_data['open_type'] = 2;
                                        $log_id= $db_park_open_log->add($park_log_data);
                                        //查询设备
                                        $passage_info = $sCarHandleGetInfoService->getParkPassageInfo($log_info['channel_id']);
                                        $data_screen = [
                                            'passage' => $passage_info, 'car_type' => 'temporary_type', 'village_id' => $now_order['village_id'],
                                            'car_number' => $log_info['car_number'], 'channel_id' => $log_info['channel_id'],
                                            'content' => '请通行,祝您一路平安', 'voice_content' => 6
                                        ];
                                        (new CarFlowShowScreenService())->addParkShowScreenLog($data_screen);
//                                    (new HouseVillageParkingService())->addParkShowScreenLog($data_screen);
                                        fdump_api(['加入开门指令并加入显屏' => __LINE__,'log_id' => $log_id,'park_log_data' => $park_log_data],'after_pay/newPayServiceLog',1);
                                    }else{
                                        $whereFlow = [];
                                        $whereLog = [];
                                        $whereFlow[] = ['park_passage_id', '=', $out_channel_id];
                                        $whereLog[] = ['channel_id', '=', $out_channel_id];
                                        $recordLogParam = [
                                            'car_number' => '', 'garage_id' => 0, 'flow_direction' => 2, 'park_sys_type' => $park_sys_type,'out_channel_id'=>$out_channel_id
                                        ];
                                        $log_info = $sCarHandleGetInfoService->getFlowIdentifyRecord($village_id, $recordLogParam, $whereFlow);

                                        fdump_api(['查询当前通道最后一通行记录存在-然后判断是否为当前车牌' => __LINE__, 'log_info' => $log_info,'whereLog' => $whereLog, 'whereFlow' => $whereFlow, 'recordLogParam' => $recordLogParam],'after_pay/newPayServiceLog',1);
                                        if (!empty($log_info) && $log_info['car_number'] == $car_number) {
                                            $park_log_data = [];
                                            $park_log_data['car_number'] = $log_info['car_number'];
                                            $park_log_data['channel_id'] = $log_info['channel_id'];
                                            $park_log_data['park_type'] = $log_info['park_type'];
                                            $park_log_data['add_time'] = time();
                                            $park_log_data['open_type'] = 2;
                                            $log_id = $db_park_open_log->add($park_log_data);
                                            fdump_api(['加入开门指令并加入显屏' => __LINE__,'log_id' => $log_id, 'park_log_data' => $park_log_data], 'park_after_pay_0521', 1);

                                            $passage_info = $sCarHandleGetInfoService->getParkPassageInfo($log_info['channel_id']);
                                            $data_screen = [
                                                'passage' => $passage_info, 'car_type' => 'temporary_type', 'village_id' => $now_order['village_id'],
                                                'car_number' => $log_info['car_number'], 'channel_id' => $log_info['channel_id'],
                                                'content' => '请通行,祝您一路平安', 'voice_content' => 6
                                            ];
                                            (new CarFlowShowScreenService())->addParkShowScreenLog($data_screen);
                                        }
                                    }
                                    // 已经变更过 无需重复变更
//                                fdump_api([$house_village_parking_temp['id']],'fter_pay/newPayServiceLog',1);
//                                $db_house_village_parking_temp->saveOne(['id'=>$house_village_parking_temp['id']],['is_paid'=>1,'pay_time'=> time()]);
                                }
                            } elseif ($house_village_parking_temp['is_pay_scene'] == 2) {//无牌车直付
                                // 已经变更过 无需重复变更
//                                $db_house_village_parking_temp->saveOne(['id'=>$house_village_parking_temp['id']],['is_paid'=>1,'pay_time'=> time()]);
                            } elseif ($house_village_parking_temp['is_pay_scene'] == 0) {//预付
                                $record_info = $db_park_scrcu_record->get_one(['order_number' => $extra['paid_orderid']]);
                                if (!empty($record_info) && $record_info['over'] == 1) {
                                    $db_park_scrcu_record->delOne(['id' => $record_info['id']]);
                                } else {
                                    $recordLogParam = [
                                        'car_number' => $car_number, 'garage_id' => 0, 'flow_direction' => 2, 'park_sys_type' => $park_sys_type,
                                    ];
                                    $whereFlow = [];
                                    $whereLog = [];
                                    if (!empty($out_channel_id)) {
                                        $recordLogParam['car_number'] = '';
                                        $whereFlow[] = ['park_passage_id', '=', $out_channel_id];
                                        $whereLog[] = ['channel_id', '=', $out_channel_id];
                                    } else {
                                        $limitTop = time() + 180;
                                        $limitLow = time() - 300;
                                        $whereFlow[] = ['flow_time', '>=', $limitLow];
                                        $whereFlow[] = ['flow_time', '<=', $limitTop];
                                        $whereLog[] = ['add_time', '>=', $limitLow];
                                        $whereLog[] = ['add_time', '<=', $limitTop];
                                    }
                                    $log_info = $sCarHandleGetInfoService->getFlowIdentifyRecord($village_id, $recordLogParam, $whereFlow, $whereLog);
                                    fdump_api(['预付的但是该车辆最后一次通行或者短期内识别的抬杆' => __LINE__, 'log_info' => $log_info,'whereLog' => $whereLog, 'whereFlow' => $whereFlow, 'recordLogParam' => $recordLogParam],'after_pay/newPayServiceLog',1);
                                    if (!empty($log_info) && $log_info['car_number'] == $car_number) {
                                        $park_log_data = [];
                                        $park_log_data['car_number'] = $log_info['car_number'];
                                        $park_log_data['channel_id'] = $log_info['channel_id'];
                                        $park_log_data['park_type'] = $log_info['park_type'];
                                        $park_log_data['add_time'] = time();
                                        $log_id=$db_park_open_log->add($park_log_data);
                                        //查询设备
                                        $passage_info = $sCarHandleGetInfoService->getParkPassageInfo($log_info['channel_id']);
                                        $data_screen = [
                                            'passage' => $passage_info, 'car_type' => 'temporary_type', 'village_id' => $now_order['village_id'],
                                            'car_number' => $log_info['car_number'], 'channel_id' => $log_info['channel_id'],
                                            'content' => '请通行,祝您一路平安', 'voice_content' => 6
                                        ];
                                        (new CarFlowShowScreenService())->addParkShowScreenLog($data_screen);
//                                        (new HouseVillageParkingService())->addParkShowScreenLog($data_screen);
                                        fdump_api([$log_id, $park_log_data], 'after_pay/newPayServiceLog', 1);
                                    }
                                }
                                // 已经变更过 无需重复变更
//                                fdump_api([$house_village_parking_temp['id']],'park_after_pay_0521',1);
//                                $res_park=$db_house_village_parking_temp->saveOne(['id'=>$house_village_parking_temp['id']],['is_paid'=>1,'pay_time'=> time()]);
//                                fdump_api([$res_park],'park_after_pay_0521',1);
                            }
                            $recordIds = [];
                            if (isset($in_record_id) && $in_record_id) {
                                $recordIds[] = $in_record_id;
                            }
                            if (isset($out_record_id) && $out_record_id) {
                                $recordIds[] = $out_record_id;
                            }
                            if (! empty($recordIds)) {
                                $whereRecord = [
                                    ['record_id', 'in', $recordIds],
                                ];
                                (new HouseVillageCarAccessRecord())->saveOne($whereRecord, array('pay_time' => time(), 'update_time' => time()));
                            }
                        }
                    }
		            elseif ($park_sys_type=='D7'){
                        $d7_pay_data=[];
                        $d7_pay_data['parkId']=$park_config['d7_park_id'];
                        $d7_pay_data['orderNo']=$house_village_parking_temp['order_id'];
                        $d7_pay_data['outTradeNo']=$cashier_order['paid_orderid'];
                        $d7_pay_data['tradeStatus']='SUCCESS';
                        $d7_pay_data['totalAmount']=$now_order['money']*100;
                        $d7_pay_data['discountAmount']=0;
                        $d7_pay_data['pointDeductAmount']=0;
                        $d7_pay_data['payTime']=time();
                        $d7_pay_data['payType']=1;
                        $d7_pay_data['payMethod']='CASHSELF';
                        (new QinLinCloudService())->notifyBill($d7_pay_data);
                    }
                    elseif ($park_sys_type=='D6'){
                        $paid_type=isset($cashier_order['online_pay_type']) ? $cashier_order['online_pay_type'] : '';
                        if(in_array($paid_type,['wechat','weixin'])){
                            $pay_type0729=1;
                        }elseif (in_array($paid_type,['alipay'])){
                            $pay_type0729=2;
                        }elseif (in_array($paid_type,['unionpay'])){
                            $pay_type0729=3;
                        }else{
                            $pay_type0729=5;
                        }
                        $temp0729=$house_village_parking_temp;
                        $time0729=time();
                        //todo D6智慧停车 支付成功
                        fdump_api(['D6智慧停车支付成功' => __LINE__, 'now_order' => $now_order, 'temp0729' => $temp0729, 'pay_money' => $now_order['pay_money'], 'pay_type0729' => $pay_type0729, 'time0729' => $time0729], 'after_pay/newPayServiceLog', 1);

                        (new D6Service())->d6ChargingResult($temp0729,$now_order['pay_money'],$pay_type0729,$time0729);
                    }
                    elseif ($park_sys_type=='A11'){
                        $village_id = $now_order['village_id'];
                        fdump_api(['停车支付回调'.__LINE__,'now_order' => $now_order, 'whereTemp' => $whereTemp, 'house_village_parking_temp' => $house_village_parking_temp],'after_pay/newPayServiceLog',1);
                        if($house_village_parking_temp){
                            $car_number = $now_order['car_number'] ? $now_order['car_number'] : $house_village_parking_temp['car_number'];
                            fdump_api(['停车支付回调'.__LINE__,'car_number' => $car_number],'after_pay/newPayServiceLog',1);
                            $out_channel_id = $house_village_parking_temp['out_channel_id'];
                            if(!$out_channel_id){
                                $out_channel_id = '1';
                            }
                            if($house_village_parking_temp['is_pay_scene'] == 1 || is_null($house_village_parking_temp['is_pay_scene'])){//直付
                                $whereFlow = [];
                                $whereLog = [];
                                $limitTop = time() + 180;
                                $limitLow = time() - 600;
                                $whereFlow[] = ['flow_time', '>=', $limitLow];
                                $whereFlow[] = ['flow_time', '<=', $limitTop];
                                $whereLog[] = ['add_time', '>=', $limitLow];
                                $whereLog[] = ['add_time', '<=', $limitTop];
                                $recordLogParam = [
                                    'car_number' => $car_number, 'garage_id' => 0, 'flow_direction' => 2, 'park_sys_type' => $park_sys_type,
                                ];
                                $log_info = $sCarHandleGetInfoService->getFlowIdentifyRecord($village_id, $recordLogParam, $whereFlow, $whereLog);
                                
                                fdump_api(['车辆识别记录'.__LINE__,'log_info' => $log_info,'whereLog' => $whereLog,'whereFlow' => $whereFlow,'recordLogParam' => $recordLogParam],'after_pay/newPayServiceLog',1);

                                if (!empty($log_info)) {
                                    $park_log_data = [];
                                    $park_log_data['car_number'] = $log_info['car_number'];
                                    $park_log_data['channel_id'] = $log_info['channel_id'];
                                    $park_log_data['park_type']  = $log_info['park_type'];
                                    $park_log_data['add_time']   = time();
                                    $log_id= $db_park_open_log->add($park_log_data);
                                    //查询设备
                                    $passage_info = $sCarHandleGetInfoService->getParkPassageInfo($log_info['channel_id']);
                                    $data_screen = [
                                        'passage' => $passage_info, 'car_type' => 'temporary_type', 'village_id' => $now_order['village_id'],
                                        'car_number' => $log_info['car_number'], 'channel_id' => $log_info['channel_id'],
                                        'content' => '请通行,祝您一路平安', 'voice_content' => 6
                                    ];
                                    (new CarFlowShowScreenService())->addA11ParkShowScreenLog($data_screen);
//                                    (new A11Service())->addParkShowScreenLog($data_screen);
                                    fdump_api(['显屏数据'.__LINE__,'passage_info' => $passage_info,'data_screen' => $data_screen],'after_pay/newPayServiceLog',1);
                                    $passage_area = isset($passage_info['passage_area']) && $passage_info['passage_area'] ? $passage_info['passage_area'] : 0;
                                    $whereGarage = [];
                                    $whereGarage[] = ['garage_id', '=', $passage_area];
                                    $infoGarage = $db_house_village_parking_garage->getOne($whereGarage, 'garage_num');
                                    $car_park_str = isset($infoGarage['garage_num']) && $infoGarage['garage_num'] ? $infoGarage['garage_num'] : '';
                                }else{
                                    $whereFlow = [];
                                    $whereLog = [];
                                    $whereFlow[] = ['park_passage_id', '=', $out_channel_id];
                                    $whereLog[] = ['channel_id', '=', $out_channel_id];
                                    $recordLogParam = [
                                        'car_number' => '', 'garage_id' => 0, 'flow_direction' => 2, 'park_sys_type' => $park_sys_type,
                                    ];
                                    $log_info = $sCarHandleGetInfoService->getFlowIdentifyRecord($village_id, $recordLogParam, $whereFlow, $whereLog);
                                    fdump_api(['车辆识别记录' . __LINE__, 'log_info' => $log_info,'whereLog' => $whereLog,'whereFlow' => $whereFlow,'recordLogParam' => $recordLogParam], 'after_pay/newPayServiceLog', 1);
                                    if ((!empty($log_info) && $log_info['car_number'] == $car_number)||(strpos($car_number,'临')!==false)) {
                                        $park_log_data = [];
                                        $park_log_data['car_number'] = $log_info['car_number'];
                                        $park_log_data['channel_id'] = $log_info['channel_id'];
                                        $park_log_data['park_type'] = $log_info['park_type'];
                                        $park_log_data['add_time'] = time();
                                        $log_id = $db_park_open_log->add($park_log_data);
                                        //查询设备
                                        $passage_info = $sCarHandleGetInfoService->getParkPassageInfo($log_info['channel_id']);
                                        if($passage_info && isset($passage_info['passage_area'])){
                                            $data_screen = [
                                                'passage' => $passage_info, 'car_type' => 'temporary_type', 'village_id' => $now_order['village_id'], 'car_number' => $car_number, 
                                                'channel_id' => $out_channel_id, 'content' => '请通行,祝您一路平安', 'voice_content' => 6
                                            ];
                                            (new CarFlowShowScreenService())->addA11ParkShowScreenLog($data_screen);
                                            if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
                                                $garage_id = intval($passage_info['garage_id']);
                                            } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
                                                $garage_id = intval($passage_info['passage_area']);
                                            } else {
                                                $garage_id = 0;
                                            }
                                            $whereGarage = [];
                                            $whereGarage[] = ['garage_id', '=', $garage_id];
                                            $infoGarage = $db_house_village_parking_garage->getOne($whereGarage, 'garage_num');
                                            $car_park_str = isset($infoGarage['garage_num']) && $infoGarage['garage_num'] ? $infoGarage['garage_num'] : '';
                                        }
                                        fdump_api(['记录车辆开门' . __LINE__, 'park_log_data' => $park_log_data, 'log_id' => $log_id], 'after_pay/newPayServiceLog', 1);
                                    }
                                }
                                fdump_api(['修改临时停车记录为已支付' . __LINE__, 'id' => $house_village_parking_temp['id']], 'after_pay/newPayServiceLog', 1);
                                // 已经变更过 无需重复变更
//                                $db_house_village_parking_temp->saveOne(['id'=>$house_village_parking_temp['id']],['is_paid'=>1,'pay_time'=> time()]);
                            } elseif ($house_village_parking_temp['is_pay_scene'] == 2) {//无牌车直付
                                fdump_api(['无牌车直付修改临时停车记录为已支付' . __LINE__, 'id' => $house_village_parking_temp['id']], 'after_pay/newPayServiceLog', 1);
                                // 已经变更过 无需重复变更
//                                $db_house_village_parking_temp->saveOne(['id'=>$house_village_parking_temp['id']],['is_paid'=>1,'pay_time'=> time()]);
                            } elseif ($house_village_parking_temp['is_pay_scene'] == 0) {//预付
                                $recordLogParam = [
                                    'car_number' => $car_number, 'garage_id' => 0, 'flow_direction' => 2, 'park_sys_type' => $park_sys_type,
                                ];
                                $whereFlow = [];
                                $whereLog = [];
                                if (intval($out_channel_id) > 1) {
                                    $recordLogParam['car_number'] = '';
                                    $whereFlow[] = ['park_passage_id', '=', $out_channel_id];
                                    $whereLog[] = ['channel_id', '=', $out_channel_id];
                                } else {
                                    $limitTop = time() + 180;
                                    $limitLow = time() - 300;
                                    $whereFlow[] = ['flow_time', '>=', $limitLow];
                                    $whereFlow[] = ['flow_time', '<=', $limitTop];
                                    $whereLog[] = ['add_time', '>=', $limitLow];
                                    $whereLog[] = ['add_time', '<=', $limitTop];
                                }
                                $log_info = $sCarHandleGetInfoService->getFlowIdentifyRecord($village_id, $recordLogParam, $whereFlow, $whereLog);
                                fdump_api(['预付的但是该车辆最后一次通行或者短期内识别的抬杆' => __LINE__, 'log_info' => $log_info,'whereLog' => $whereLog, 'whereFlow' => $whereFlow, 'recordLogParam' => $recordLogParam],'after_pay/newPayServiceLog',1);
                                if (!empty($log_info) && $log_info['car_number'] == $car_number) {
                                    $park_log_data = [];
                                    $park_log_data['car_number'] = $log_info['car_number'];
                                    $park_log_data['channel_id'] = $log_info['channel_id'];
                                    $park_log_data['park_type'] = $log_info['park_type'];
                                    $park_log_data['add_time'] = time();
                                    $log_id=$db_park_open_log->add($park_log_data);
                                    //查询设备
                                    if(empty($passage_info)) {
                                        $passage_info = $db_park_passage->getFind(['device_number' => $log_info['channel_id'], 'village_id' => $now_order['village_id'], 'status' => 1]);
                                    }
                                    $data_screen = [
                                        'passage' => $passage_info, 'car_type' => 'temporary_type', 'village_id' => $now_order['village_id'],
                                        'car_number' => $log_info['car_number'], 'channel_id' => $log_info['channel_id'], 
                                        'content' => '请通行,祝您一路平安', 'voice_content' => 6
                                    ];
                                    (new CarFlowShowScreenService())->addA11ParkShowScreenLog($data_screen);
//                                    (new A11Service())->addParkShowScreenLog($data_screen);
                                    fdump_api(['显屏数据'.__LINE__,'passage_info' => $passage_info,'data_screen' => $data_screen],'after_pay/newPayServiceLog',1);
                                    if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
                                        $garage_id = intval($passage_info['garage_id']);
                                    } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
                                        $garage_id = intval($passage_info['passage_area']);
                                    } else {
                                        $garage_id = 0;
                                    }
                                    $whereGarage = [];
                                    $whereGarage[] = ['garage_id', '=', $garage_id];
                                    $infoGarage = $db_house_village_parking_garage->getOne($whereGarage, 'garage_num');
                                    $car_park_str = isset($infoGarage['garage_num']) && $infoGarage['garage_num'] ? $infoGarage['garage_num'] : '';
                                }
                                fdump_api(['预付修改临时停车记录为已支付' . __LINE__, 'id' => $house_village_parking_temp['id']], 'after_pay/newPayServiceLog', 1);
                                // 已经变更过 无需重复变更
//                                $res_park=$db_house_village_parking_temp->saveOne(['id'=>$house_village_parking_temp['id']],['is_paid'=>1,'pay_time'=> time()]);
//                                fdump_api(['预付修改临时停车记录为已支付结果' . __LINE__, 'res_park' => $res_park], 'after_pay/newPayServiceLog', 1);
                            }
                        }
                    }
                    else {
                        //临时车处理
                       if($park_system){
                           $house_village_parking_temp =  $db_house_village_parking_temp->getFind(['car_number'=>$now_order['car_number'],'token'=>$park_system['token']]);
                           fdump_api(['临时车' . __LINE__, 'house_village_parking_temp' => $house_village_parking_temp], 'after_pay/newPayServiceLog', 1);
                             if($house_village_parking_temp){
                                $car_number = $now_order['car_number'] ? $now_order['car_number'] : $house_village_parking_temp['car_number'];
                                $out_channel_id = $house_village_parking_temp['out_channel_id'];
                                if(!$out_channel_id){
                                    $out_channel_id = '1';
                                }
                                if($house_village_parking_temp['is_pay_scene'] == 1){//直付
                                    $nowtime = date("mdHis");
                                    $orderid = $nowtime . sprintf("%04d", $now_order['order_id']);
                                    $json_data['state'] = 1;
                                    $json_data['order_id'] = $house_village_parking_temp['order_id'];
                                    $json_data['service_name'] = 'outpark';
                                    $json_data['trade_no'] = $orderid;
                                    $json_data['pay_type'] = 'scancode';
                                    $json_data['out_channel_id'] = $out_channel_id;//通道号暂时写1
                                    $json_data['total'] = floatval($house_village_parking_temp['price']);
                                    $json_data['errmsg'] = '支付成功';
                                    $json_data['pay_time'] = time();
                                    $json_data['car_number'] = $car_number;
                                    $json_data['arrive_money'] = $house_village_parking_temp['price'];
                                    $json_data['fee'] = 0.00;
                                    $json_data['account_type'] = 1;
                                    $json_data['remark'] = '收到停车费-'.$json_data['car_number'];

                                    $post_data['unserialize_desc'] = serialize($json_data);
                                    $post_data['park_id'] = $park_system['park_id'];
                                    $post_data['token'] = $park_system['token'];
                                    $post_data['service_name'] = 'outpark';
                                    $post_data['order_id'] = $house_village_parking_temp['order_id'] ? $house_village_parking_temp['order_id'] : '';
                                    $post_data['create_time'] = time();
                                    $post_data['car_number'] = $car_number;
                                    $post_data['village_id'] = $now_order['village_id'];
                                    $res =$db_park_total_record->add($post_data);
                                    fdump_api(['临时车' . __LINE__, 'post_data' => $post_data, 'res' => $res], 'after_pay/newPayServiceLog', 1);
                                }
                                elseif($house_village_parking_temp['is_pay_scene'] == 2){//无牌车直付
                                    $nowtime = date("mdHis");
                                    $orderid = $nowtime . sprintf("%04d", $now_order['order_id']);
                                    $json_data['state'] = 1;
                                    $json_data['order_id'] = $house_village_parking_temp['order_id'];
                                    $json_data['service_name'] = 'outpark';
                                    $json_data['trade_no'] = $orderid;
                                    $json_data['pay_type'] = 'scancode';
                                    $json_data['out_channel_id'] = $out_channel_id;//通道号暂时写1
                                    $json_data['total'] = floatval($house_village_parking_temp['price']);
                                    $json_data['errmsg'] = '支付成功';
                                    $json_data['pay_time'] = time();//
                                    $json_data['car_number'] = $car_number;
                                    $json_data['arrive_money'] = $house_village_parking_temp['price'];
                                    $json_data['fee'] = 0.00;
                                    $json_data['account_type'] = 1;
                                    $json_data['remark'] = '收到无牌车停车费-'.$json_data['car_number'];

                                    $post_data['unserialize_desc'] = serialize($json_data);
                                    $post_data['park_id'] = $park_system['park_id'];
                                    $post_data['token'] = $park_system['token'];
                                    $post_data['service_name'] = 'outpark';
                                    $post_data['order_id'] = $house_village_parking_temp['order_id'] ? $house_village_parking_temp['order_id'] : '';
                                    $post_data['create_time'] = time();
                                    $post_data['car_number'] = $car_number;
                                    $post_data['village_id'] = $now_order['village_id'];
                                    $res =$db_park_total_record->add($post_data);
                                }elseif($house_village_parking_temp['is_pay_scene'] == 0){//预付
                                    $nowtime = date("mdHis");
                                    $orderid = $nowtime . sprintf("%04d", $now_order['order_id']);

                                    $json_data['car_number'] = $car_number;
                                    $json_data['service_name'] = 'prepay_order';
                                    $json_data['prepay'] = '';//金额 float
                                    $json_data['prepay_type'] = '';
                                    $json_data['pay_channel'] = '0微信';
                                    $json_data['order_id'] = $house_village_parking_temp['order_id'];//车场订单id //跟入场一起
                                    $json_data['query_order_no'] = '';//查询价格编号，查询时收费系统返回的查询价格编号 否
                                    $json_data['park_id'] = $park_system['park_id'];//
                                    $json_data['trade_no'] = $orderid;//
                                    $json_data['query_time'] = $house_village_parking_temp['add_time'];//
                                    $json_data['pay_time'] =  time();//
                                    $json_data['arrive_money'] = $house_village_parking_temp['price'];//到账金额
                                    $json_data['fee'] = 0.00;//手续费
                                    $json_data['account_type'] = 1;//
                                    $json_data['remark'] = '收到停车费-'.$json_data['car_number'];

                                    $post_data['unserialize_desc'] = serialize($json_data);
                                    $post_data['park_id'] = $park_system['park_id'];
                                    $post_data['token'] = $park_system['token'];
                                    $post_data['service_name'] = 'prepay_order';
                                    $post_data['order_id'] = $house_village_parking_temp['order_id'] ? $house_village_parking_temp['order_id'] : '';
                                    $post_data['create_time'] = time();
                                    $post_data['car_number'] = $car_number;
                                    $post_data['village_id'] = $now_order['village_id'];
                                    $res =$db_park_total_record->add($post_data);
                                }
                                 $db_house_village_parking_temp->saveOne(['id'=>$house_village_parking_temp['id']],['is_paid'=>1,'pay_time'=> time()]);
                            }

                        }
                    }
                 }
               }
            }
            else{
                if($rule_info['type'] == 2){
                if($now_order['position_id']){
                    $last_order = $db_house_new_order_log->getOne([['position_id','=',$now_order['position_id']],['order_type','=',$now_order['order_type']],['project_id','=',$now_order['project_id']]],true,'id DESC');
                } else{
                    $last_order = $db_house_new_order_log->getOne([['room_id','=',$now_order['room_id']],['order_type','=',$now_order['order_type']],['position_id','=',0],['project_id','=',$now_order['project_id']]],true,'id DESC');
                }
                // 兼容老版停车收费
                if($now_order['order_type'] == 'park'){
                    if($now_order['position_id']){
                        $where22=[
                            ['position_id','=',$now_order['position_id']],
                            ['order_type','=',$now_order['order_type']],
                            ['project_id','=',0],
                        ];
                    }else{
                        $where22=[
                            ['room_id','=',$now_order['room_id']],
                            ['order_type','=',$now_order['order_type']],
                        ];
                    }
                    $new_order_log = $db_house_new_order_log->getOne($where22,true,'id DESC');
                    if(!empty($new_order_log)){
                        $last_order=$new_order_log;
                    }
                    //fdump_api(['线上支付house_new_order_log表数据=='.__LINE__,($now_order['position_id'] ? '车场' : '房产'),$new_order_log->toArray()],'park/shang_log',1);
                }elseif ($now_order['order_type'] == 'property'){
                    $ishavepropertyfee=1;
                    if($now_order['position_id']){
                        $where22=[
                            ['position_id','=',$now_order['position_id']],
                            ['order_type','=',$now_order['order_type']],
                        ];
                    }else{
                        $where22=[
                            ['room_id','=',$now_order['room_id']],
                            ['position_id','=',0],
                            ['order_type','=',$now_order['order_type']],
                        ];
                    }
                    $new_order_log = $db_house_new_order_log->getOne($where22,true,'id DESC');
                    if(!empty($new_order_log)){
                        $last_order=$new_order_log;
                    }
                    //fdump_api(['线上支付house_new_order_log表数据=='.__LINE__,($now_order['position_id'] ? '车场' : '房产'),$new_order_log->toArray()],'park/shang_log',1);
                }
                if($last_order){
                    $last_order = $last_order->toArray();
                    if($last_order && $last_order['service_end_time']>100 && !$isHang){
                        $date_order['service_start_time'] = $last_order['service_end_time']+100;
                        $date_order['service_start_time'] = strtotime(date('Y-m-d',$date_order['service_start_time']));
                        $service_start_time = $date_order['service_start_time'];
                        if($now_order['is_prepare'] == 1){
                            $cycle = $now_order['service_give_month_num'] + $now_order['service_month_num'];
                        }else{
                            $cycle = $now_order['service_month_num']?$now_order['service_month_num']:1;
                        }
                        if($rule_info['bill_create_set'] == 1){
                            $date_order['service_end_time'] = strtotime("+".$cycle." day",$date_order['service_start_time'])-1;
                        }elseif(isset($now_order['unify_flage_id']) && !empty($now_order['unify_flage_id'])){
                            $date_order['service_end_time'] =strtotime("+1 month",$date_order['service_start_time'])-1;
                        }elseif ($rule_info['bill_create_set'] == 2){
                            //todo 判断是不是按照自然月来生成订单
                            if(cfg('open_natural_month') == 1){
                                $date_order['service_end_time'] = strtotime("+".$cycle." month",$date_order['service_start_time'])-1;
                            }else{
                                $cycle = $cycle*30;
                                $date_order['service_end_time'] = strtotime("+".$cycle." day",$date_order['service_start_time'])-1;
                            }
                            fdump_api(['线上支付【账单生成周期设置 1：按日生成2：按月生成3：按年生成】 当前是'.$rule_info['bill_create_set'].'=='.__LINE__,(cfg('open_natural_month') == 1 ? '开启自然月' : '未开启自然月'),'天数：'.$cycle,'service_end_time：'.date('Y-m-d H:i:s',$date_order['service_end_time'])], 'after_pay/newPayServiceLog', 1);
                        }else{
                            $date_order['service_end_time'] = strtotime("+".$cycle." year",$date_order['service_start_time'])-1;
                        }
                        if($now_order['service_time_handle']==5){
                            $date_order['service_end_time']=$date_order['service_start_time']+$service_time_difference;
                        }
                        $service_end_time = $date_order['service_end_time'];
                    }
                }
                    if(isset($now_order['f_order_id']) && $now_order['f_order_id']>0 && (isset($now_order['service_time_handle']) && ($now_order['service_time_handle']!=1))){
                        //不处理时间
                        $service_start_time = $now_order['service_start_time'];
                        $service_end_time = $now_order['service_end_time'];
                        $date_order['service_start_time'] = $service_start_time;
                        $date_order['service_end_time'] = $service_end_time;
                    }elseif(!$isHang) {
                        $db_house_new_order_log->addOne([
                            'order_id' => $now_order['order_id'],
                            'project_id' => $now_order['project_id'],
                            'order_type' => $now_order['order_type'],
                            'order_name' => $now_order['order_name'],
                            'room_id' => $now_order['room_id'],
                            'position_id' => $now_order['position_id'],
                            'property_id' => $now_order['property_id'],
                            'village_id' => $now_order['village_id'],
                            'service_start_time' => $service_start_time,
                            'service_end_time' => $service_end_time,
                            'add_time' => time(),
                        ]);
                    }
                    if($now_order['order_type'] == 'property'){
                        $service_house_village_user_bind->saveUserBind([
                            ['vacancy_id','=',$now_order['room_id']],
                            ['type','in',[0,3]],
                            ['status','=',1],
                        ],['property_endtime'=>$service_end_time]);
                        $spec = date('Y-m-d',$service_start_time) . '至' . date('Y-m-d',$service_end_time);
                    }
                    /**** 车位到期时间 老版的和新版的冲突了 去掉老版的统一用新版的 有的老客户坚持要*/
                    if($is_old_park_position && ($now_order['order_type'] == 'park' || $now_order['order_type'] == 'parking_management')){
                        if($now_order['position_id']){
                            try {
                                $service_house_village_parking->editParkingPosition(['position_id'=>$now_order['position_id']],['end_time'=>$service_end_time]);
                                $service_house_village_parking->editParkingCar(['car_position_id'=>$now_order['position_id']],['end_time'=>$service_end_time]);
                            } catch (\Exception $e) {
                                fdump_api([ __LINE__, 'err' => $e->getMessage(), 'code' => $e->getCode()], 'after_pay/newPayServiceLog', true);
                            }
                        }
                    }
                    
                }
                if ($now_order['order_type'] == 'property') {
                    $ishavepropertyfee = 1;
                }
            }
            if($now_order['order_type'] == 'qunuanfei'){//住户余额，金额进入小住户余额，增加预存记录
                $village_config = $service_house_village_config->getConfig(['village_id'=>$now_order['village_id']]);
                if($village_config && $village_config['open_village_balance']==1){//开启是否允许使用小区住户余额
                    $village_user_money = (new VillageUserMoneyList())->getOne(['uid'=>$now_order['uid'],'business_id'=>$now_order['village_id'],'business_type'=>1],'current_money');
                    $village_user_money = $village_user_money ? $village_user_money->toArray() : [];
                    if($village_user_money){
                        $update_ret = (new VillageUserMoneyList())->saveOne(['uid'=>$now_order['uid'],'business_id'=>$now_order['village_id'],'business_type'=>1],[
                            'current_money'=>$extra['paid_money']+$village_user_money['current_money'],
                            'update_time'=>time(),
                        ]);
                    }else{
                        $update_ret = (new VillageUserMoneyList())->addOne([
                            'uid'=>$now_order['uid'],
                            'business_type'=>1,
                            'business_id'=>$now_order['village_id'],
                            'current_money'=>$extra['paid_money'],
                            'add_time'=>time(),
                            'update_time'=>time(),
                            'remarks'=>'用户端住户余额预存操作',
                        ]);
                    }
                    if($update_ret){
                        $logData = [
                            'uid' => $now_order['uid'],
                            'business_type' => 1,
                            'business_id' => $now_order['village_id'],
                            'type' => 1,
                            'current_money' => $village_user_money['current_money'],
                            'money' => $extra['paid_money'],
                            'after_price' => $extra['paid_money']+$village_user_money['current_money'],
                            'add_time' => time(),
                            'role_id' => 0,
                            'ip' => get_client_ip(),
                            'desc' => '用户端住户余额预存操作',
                            'order_id' => 0,
                            'order_type' => 1,
                        ];
                        (new VillageUserMoneyLog())->addOne($logData);
                    }
                }else{
                    (new UserService())->addMoney($now_order['uid'], $extra['paid_money'], L_('用户端').(new HouseNewChargeService())->charge_type[$now_order['order_type']].L_('预存操作'));
                }

            }
            fdump_api(['line' . __LINE__, 'village_id' => $now_order['village_id']], 'after_pay/newPayServiceLog', 1);
            //$now_village = $service_house_village->getHouseVillageInfo(['village_id'=>$now_order['village_id']]);
            $detail[$key]['goodsname'] = $now_order['order_name'];
            $detail[$key]['price'] = $now_order['modify_money'];
            $detail[$key]['order_id'] = $now_order['order_id'];
            $spec = isset($spec)?$spec:'';
            $date_order['village_balance'] =$village_balance;
            $date_order['system_balance'] =$system_balance;
            $date_order['pay_amount_points'] = $pay_money;
            $date_order['pay_money'] = $now_order['modify_money'] + $now_order['late_payment_money'];
            /*
            $tmp_extra_data=isset($now_order['discount_info']) && $now_order['discount_info'] ? json_decode($now_order['discount_info'],true):array();
            $rule_rate=0;
            $rule_discount_info=array();
            if($tmp_extra_data && isset($tmp_extra_data['rule_discount']) && !empty($tmp_extra_data['rule_discount']) && isset($tmp_extra_data['rule_discount']['rate'])){
                $rule_rate=$tmp_extra_data['rule_discount']['rate'];
                $rule_discount_info=$tmp_extra_data['rule_discount'];
            }
            */
            if($rule_discount_rate>0 && $rule_discount_info && $rule_discount_info['diy_type']==1){
                $pay_amount_discount_tmp=$date_order['pay_money']*$rule_discount_rate;
                $date_order['pay_amount_points'] = $pay_amount_discount_tmp;
                $date_order['pay_money']=$pay_amount_discount_tmp/100;
                $date_order['pay_money']=round($date_order['pay_money'],2);
            }
            $date_order['is_discard'] = 1;
            $date_order['from'] = 2;
            $date_order['discard_reason'] = '';
            $date_order['check_apply_id'] = 0;
            fdump_api(['line' . __LINE__, 'order_id' => $now_order['order_id'], 'date_order' => $date_order], 'after_pay/newPayServiceLog', 1);
            $service_house_new_cashier->saveOrder(['order_id'=>$now_order['order_id']],$date_order);
            $msgbalance='物业缴费，扣除小区住户余额';
            if($is_customized_meter_reading>0) {
                $customizeMeterWaterReadingService=new CustomizeMeterWaterReadingService();
                if ($now_order['order_type_flag'] == 'hot_water_balance') {
                    $msgbalance='热水费，扣除小区热水费余额';
                    $customizeMeterWaterReadingService->openUserWaterElectricUse($now_order['village_id'],$now_order['room_id'],$now_order['order_type_flag']);
                } else if ($now_order['order_type_flag'] == 'cold_water_balance') {
                    $msgbalance='冷水费，扣除小区冷水费余额';
                    $customizeMeterWaterReadingService->openUserWaterElectricUse($now_order['village_id'],$now_order['room_id'],$now_order['order_type_flag']);
                } else if ($now_order['order_type_flag']== 'electric_balance') {
                    $msgbalance='电费，扣除小区电费余额';
                    $customizeMeterWaterReadingService->openUserWaterElectricUse($now_order['village_id'],$now_order['room_id'],$now_order['order_type_flag']);
                }
            }
            if ($now_order['deposit_money']>0){
                $db_house_new_deposit_log=new HouseNewDepositLog();
                $deposit_info=$db_house_new_deposit_log->get_one(['order_id'=>$now_order['order_id'],'type'=>2]);
                if (empty($deposit_info)){
                    $deposit_info1=$db_house_new_deposit_log->get_one(['room_id'=>$now_order['room_id']]);
                    if ($deposit_info1['total_money']>$now_order['deposit_money']){
                        $deposit_money11= $deposit_info1['total_money']-$now_order['deposit_money'];
                    }else{
                        $deposit_money11=0;
                    }
                    $db_house_new_deposit_log->addOne([
                        'order_id'=>$now_order['order_id'],
                        'order_no'=>$cashier_order['order_no'],
                        'type'=>2,
                        'before_money'=>$deposit_info1['total_money'],
                        'money'=>$now_order['deposit_money'],
                        'total_money'=>$deposit_money11,
                        'role_id'=>$now_order['role_id'],
                        'room_id'=>$now_order['room_id'],
                        'village_id'=>$now_order['village_id'],
                        'add_time'=>time(),
                    ]);
                    //押金解冻
                    $frozen_data=['order_id'=>$now_order['order_id'],'type'=>2];
                    $this->editFrozenlog($frozen_data);
                }
            }
            //社区对账.
            $order_info = array_merge($now_order,$tmp_order);
            $order_info['order_id']=$now_order['order_id'];
            fdump_api(['line' . __LINE__, 'order_id' => $tmp_order, 'now_order' => $now_order, 'order_info' => $order_info], 'after_pay/newPayServiceLog', 1);
            $order_info['order_type'] = 'village_new_pay';
            if($plat_order_info['is_own']==0 || $plat_order_info['is_own']==1){
                $plat_order_info['is_own']+=4;
            }
            $order_info['is_own'] = $plat_order_info['is_own']-4;
            if (!$order_info['balance_pay']) {

                $order_info['balance_pay'] = 0;
            }
            if($village_balance>0){
                $order_info['pay_money']=$order_info['pay_money']-$village_balance;
                $order_info['pay_money']=$order_info['pay_money']>0 ? round($order_info['pay_money'],2):0;
            }
            $order_info['money'] = $order_info['pay_money'];
            $order_info['village_balance']=$village_balance;  //减掉小区住户余额
            $order_info['money']=$order_info['money']>0 ? $order_info['money']:0;
            $order_info['add_money_extra']=$addVillageMoneyArr;
            fdump_api(['line' . __LINE__, 'order_id' => $tmp_order, 'is_own'=>$plat_order_info['is_own'],'order_info' => $order_info], 'after_pay/newPayServiceLog', 1);
            try{
                $res = invoke_cms_model('SystemBill/bill_method',['type'=>$plat_order_info['is_own'],'order_info'=>$order_info,'is_fenzhang'=>0,'is_new_charge'=>1]);
            }catch (\Exception $e){
                fdump_api(['line' . __LINE__, 'err_msg'=>$e->getMessage()], 'after_pay/newPayServiceLog', 1);
            }
            if($res && isset($res['retval']) && isset($res['retval']['error_code']) && !$res['retval']['error_code']){
                $is_pay_bill = 2;
                $service_house_new_cashier->saveOrder(['summary_id'=>$order_id],['is_pay_bill'=>$is_pay_bill]);
            }
            fdump_api(['line' . __LINE__, 'res'=>$res,'order_info'=>$order_info], 'after_pay/newPayServiceLog', 1);
            //-----小区住户余额start-------//
            if ($village_balance > 0) {
                $village_money_data=[
                    'uid'=>$cashier_order['pay_uid'],
                    'village_id'=>$cashier_order['village_id'],
                    'type'=>2,
                    'current_village_balance'=>$village_balance,
                    'role_id'=>$now_order['role_id'],
                    'desc'=>L_($msgbalance."，订单编号X1", array("X1" => $cashier_order['order_no'])),
                    'order_id'=>$now_order['order_id'],
                    'order_type'=>1,
                    'opt_money_type'=>$opt_money_balance_type
                ];
                $villageUseResult = $village_user->addVillageUserMoney($village_money_data);
                if ($villageUseResult['error_code']) {
                    fdump_api([$village_money_data,'villageUseResult'=>$villageUseResult],'00village_balance_pay',1);
                }
            }
            $order_type_xtmp=(new HouseNewChargeService())->getType($now_order['order_type']);
            array_push($messageDatas['order_type'],$order_type_xtmp);
            //-------小区住户余额end--------//
            usleep(100000);
        }
        fdump_api(['line' . __LINE__, '订单循环处理结束','save_paid_order_record'=>$save_paid_order_record,'house_type_arr'=>$house_type_arr,'sub_order_ids'=>$sub_order_ids], 'after_pay/newPayServiceLog', 1);
        if(empty($save_paid_order_record['village_id']) && $village_id>0){
             $save_paid_order_record['village_id']=$village_id;
        }
        $house_type_arr=array_unique($house_type_arr);
        $save_paid_order_record['house_type']=$house_type_arr ? implode(',',$house_type_arr):'';
        $save_paid_order_record['sub_order_ids']=$sub_order_ids ? implode(',',$sub_order_ids):'';
        $houseNewChargeProjectService=new HouseNewChargeProjectService();
        $business_name_tmp=$houseNewChargeProjectService->getChargeNanmeStr($house_type_arr);
        $save_paid_order_record['business_name']=$business_name_tmp ? $business_name_tmp:$business_name;
        $save_paid_order_record['extra_data']=json_encode($record_extra_data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $save_paid_order_record['order_type']=$tmp_order_type;
        $save_paid_order_record['order_type_v']=$tmp_order_type_v;
        if(!empty($ticket_number)){
            $houseVillageOrderTicketNumberDb= new HouseVillageOrderTicketNumber();
            $orderTicketNumberSave=array('use_time'=>time(),'ticket_status'=>2);
            $orderTicketNumberWhere=array(['village_id','=',$village_id],['ticket_fill_number','=',$ticket_number]);
            $orderTicketNumberWhere[]=array('ticket_status','<',2);
            $orderTicketNumberWhere[]=array('summary_id','=',$summary_id);
            $oneOrderTicketNumber=$houseVillageOrderTicketNumberDb->getOneData($orderTicketNumberWhere,'*','id ASC');
            if($oneOrderTicketNumber && !$oneOrderTicketNumber->isEmpty()){
                $houseVillageOrderTicketNumberDb->saveData(array('id'=>$oneOrderTicketNumber['id']),$orderTicketNumberSave);
            }
        }

        if (!empty($worksOrderPayOrderIds)) {
            // 存在工单订单 反向写入下  time()
            $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
            $whereWorksOrder = [];
            $whereWorksOrder[] = ['pay_order_id', 'in', $worksOrderPayOrderIds];
            $worksOrderUpdate = ['pay_time' => time()];
            $db_house_new_repair_works_order->saveOne($whereWorksOrder, $worksOrderUpdate);
        }
        
        $whereArr=array();
        if ($pay_order_info_id > 0) {
            $whereArr[] = array('pay_order_info_id', '=', $pay_order_info_id);
        }else if($order_record_id>0){
            $whereArr[] = array('id', '=', $order_record_id);
        }else{
            $whereArr[]=array('village_id','=',$save_paid_order_record['village_id']);
            $whereArr[]=array('source_from','=',1);
            $whereArr[]=array('order_id','=',$save_paid_order_record['order_id']);
            $whereArr[]=array('order_no','=',$save_paid_order_record['order_no']);
            $whereArr[]=array('table_name','=','house_new_pay_order_summary');
        }
        fdump_api(['line' . __LINE__, 'whereArr' => $whereArr, 'save_paid_order_record' => $save_paid_order_record], 'after_pay/newPayServiceLog', 1);
        $paidOrderRecordDb = new PaidOrderRecord();
        $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereArr, 'id,update_time');
        if ($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()) {
            $paidOrderRecordDb->saveOneData(array(['id', '=', $paidOrderRecordInfo['id']]), $save_paid_order_record);
        } else {
            $save_paid_order_record['business_type']='village_new_pay';
            $save_paid_order_record['pay_type']=$plat_order_info['pay_type'];
            $save_paid_order_record['pay_money']=$plat_order_info['pay_money'];
            $save_paid_order_record['is_own']=$plat_order_info['is_own'];
            $save_paid_order_record['pay_order_no']=$plat_order_info['orderid'];
            $save_paid_order_record['business_order_id']=$plat_order_info['order_id'];
            $save_paid_order_record['third_transaction_no']=$plat_order_info['third_id'];
            $paidOrderRecordDb->addOneData($save_paid_order_record);
        }
        fdump_api(['line' . __LINE__, 'whereArr' => $whereArr, 'save_paid_order_record' => $save_paid_order_record], 'after_pay/newPayServiceLog', 1);
        if(isset($plat_order_info['village_balance'])){
            $messageDatas['pay_money']=$messageDatas['pay_money']+$plat_order_info['village_balance'];
//            $plat_order_info['pay_money']=$plat_order_info['pay_money']+$plat_order_info['village_balance'];
        }
        $plat_order_info['pay_money']=$cashier_order['total_money'];
        //获取购买用户信息
        $userService = new UserService();
        $buyer = $userService->getUser($cashier_order['pay_uid'], 'uid');
        if (empty($buyer)) {
            if($cashier_order['pay_uid']<1 && !empty($park_new_type_arr) && in_array('temporary_type',$park_new_type_arr)){
                //有临时 支付  且 pay_uid 字段值是0 不抛出异常
                $buyer=array('now_money'=>0,'score_count'=>0);
            }else{
                throw new \Exception(L_("购买用户不存在"), 1003);
            }
        }
        //判断帐户余额,扣除余额
        $balancePay = floatval($extra['current_system_balance']);
        if ($balancePay && $buyer['now_money'] < $balancePay) {
            throw new \Exception(L_("您的帐户余额不够此次支付"), 1003);
        }
        if ($balancePay > 0) {
            $useResult = (new UserService())->userMoney($cashier_order['pay_uid'], $balancePay, L_("物业缴费，扣除余额，订单编号X1", array("X1" => $cashier_order['order_no'])));
            if ($useResult['error_code']) {
                throw new \Exception($useResult['msg'], 1003);
            }
        }

        //积分抵扣，扣除积分
        $scoreUsedCount = $extra['current_score_use'];
        $buyer['score_count'] = $this->getUserScoreCount($cashier_order['room_id'],$buyer['score_count']);
        fdump_api(['line' . __LINE__, 'scoreUsedCount' => $scoreUsedCount, 'score_count' => $buyer['score_count']], 'after_pay/newPayServiceLog', 1);
        if ($scoreUsedCount && $buyer['score_count'] < $scoreUsedCount&&ceil($buyer['score_count'])<$scoreUsedCount) {
            throw new \Exception(L_("您的积分余额不够此次支付"), 1003);
        }
        if ($scoreUsedCount > 0) {
            if ($buyer['score_count'] < $scoreUsedCount&&ceil($buyer['score_count'])>=$scoreUsedCount){
                $scoreUsedCount=$buyer['score_count'];
            }
            $getIntegralPool=(new ConfigCustomizationService())->getIntegralPool();
            if($getIntegralPool){
                //积分池扣分，积分扣除规则：积分使用总和占积分总和的百分比，用百分比乘每个人的积分，最后计算总和，如果总和小于积分使用总和，由积分最高的人承担差值
                $use_result = $this->userIntegralPool($cashier_order['room_id'],$scoreUsedCount,$cashier_order['order_no'],$score_can_get,$cashier_order['pay_uid']);
            }else{
                $use_result = (new UserService())->userScore($cashier_order['pay_uid'], $scoreUsedCount, L_("物业缴费 ，扣除X1 ，订单编号X2", array( "X1" => cfg('score_name'), "X2" => $cashier_order['order_no'])));
            }
            if ($use_result['error_code']) {
                throw new \Exception($use_result['msg'], 1003);
            }
        }
        // 积分获得
        if ($cashier_order['score_can_get']>0) { // 使用了积分
            $score_can_get = $cashier_order['score_can_get'];
            $service_user->addScore($cashier_order['pay_uid'],$score_can_get,'缴纳社区收银台缴费获得积分');
            fdump_api(['支付回调' => __LINE__,'获得积分' => $score_can_get],'after_pay/newPayServiceLog',1);
        }else{
            $user = $service_user->getUser($cashier_order['pay_uid'],'uid');
            $score = $this->get_score($plat_order_id,$user,false);
            $score_can_get = $score['score_can_get'];
            $service_user->addScore($cashier_order['pay_uid'],$score_can_get,'缴纳社区收银台缴费获得积分');
            fdump_api(['支付回调' => __LINE__,'score'=>$score,'获得积分' => $score_can_get],'after_pay/newPayServiceLog',1);
        }
        
        /*if($auto){
            $order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $now_bind_info['name'] = !empty($now_bind_info['name'])?$now_bind_info['name']:'未知用户';
            $res = $this->addInvoice($now_bind_info['address'].'-'.$now_bind_info['name'],$now_bind_info['phone'],$order_no,$e_config['duty_paragraph'],$detail,$now_village['village_name'],$spec,$now_village['property_id']);
            if($res['status'] == '0000'){
                $this->addERecord($e_config['duty_paragraph'],$now_bind_info['name'],$now_bind_info['phone'],$res['fpqqlsh'],$order_no,$detail,$now_bind_info['pigcms_id']);
            }
        }*/
        if (isset($cashier_order['pay_uid']) && $cashier_order['pay_uid']) {
            $now_user = $service_user->getUser($cashier_order['pay_uid'],'uid');
        } else {
            $now_user = $service_user->getUser($cashier_order['uid'],'uid');
        }
        $village_info = $service_house_village->getHouseVillageInfo(array('village_id'=>$cashier_order['village_id']));
        $property_info = $service_house_village->get_house_property($village_info['property_id'],'property_name');
        $messageDatas['property_id']=$village_info['property_id'];
        $messageDatas['property_name']=$property_info['property_name'];
        $messageDatas['village_name']=$village_info['village_name'];
        $messageDatas['name'] = $name;
        $keynote1 = $property_info['property_name'];
        if (isset($village_info['village_name']) && $village_info['village_name']) {
            $keynote1 .= ' ' . $village_info['village_name'];
        }
        if ($car_park_str) {
            $car_park_str .= '（车场）';
            $keynote1 .= ' ' . $car_park_str;
        }

        //停车缴费通知需要的数据
        $carNum = '';//车牌号，不支持多个,$car_park_str 停车场
        $carEnd = '00:00';//限停时间
        $carStoredPrice = '0';//储值余额
        
        if (count($car_number_arr) == 1) {
            $car_number_txt = $car_number_arr[0];
            $keynote2 = '车牌 ' . $car_number_txt;
            $carNum = $car_number_txt;
        } elseif (count($car_number_arr) > 1) {
            $car_number_txt = implode(',', $car_number_arr);
            $keynote2 = '车牌 ' . $car_number_txt;
            $carNum = $car_number_arr[0];
        } elseif ($name) {
            $keynote2 = '用户 ' . $name;
        } else {
            $keynote2 = '物业号 '. $village_bind['usernum'];
        }
        $remark = '缴费时间：'.date('Y年n月j日 H:i',time()).'\n'.'缴费金额：'.$plat_order_info['pay_money'];
        if (count($car_number_arr) == 1 && $car_stay_time) {
            // 车辆停留时长
            $hours  = intval($car_stay_time / 3600);
            $mins   = intval(($car_stay_time - $hours * 3600) / 60);
            $second = $car_stay_time - $hours * 3600 - $mins * 60;
            $car_stay_time_txt = '';
            if ($hours) {
                $car_stay_time_txt .= $hours . '小时';
            }
            if ($mins) {
                $car_stay_time_txt .= $mins . '分钟';
            }
            if ($second) {
                $car_stay_time_txt .= $second . '秒';
            }
            $remark .= '\n停留时长：'.$car_stay_time_txt;
        }
        if (count($car_number_arr) == 1 && $car_stored_price) {
            // 车辆储值余额
            $remark .= '\n储值余额：'.$car_stored_price.'元';
            $carStoredPrice = getFormatNumber($car_stored_price);
        }
        if (count($car_number_arr) >= 1 && $car_end_time) {
            // 车辆月租
            $remark .= '\n月租期限：'.date('Y-m-d H:i:s', $car_end_time);
            $carEnd = date('Y-m-d H:i', $car_end_time);
        }
        //查询收费类型
        $thing9 = $messageDatas['order_type']??[];
        $thing9 = array_unique($thing9);
        $thing9 = implode('、',$thing9);
        if (mb_strlen($thing9, 'utf8') > 12) {
            $thing9 = mb_substr($thing9, 0, 12, 'utf8') . '......';
        }
        //查询收费标准
        $thing13 = $messageDatas['pay_type_str']??'';
        $dateStr=date('Y年n月j日H点i分',time());
        
        if($name && $carNum){
            $newName = $name.'('.$carNum.')';
        }elseif($name){
            $newName = $name;
        }else{
            $newName = $carNum;
        }
        if ($village_bind && $village_bind['single_id'] && $village_bind['floor_id'] && $village_bind['layer_id'] && $village_bind['vacancy_id'] && $village_bind['village_id']) {
            $address = $serviceHouseVillage->getSingleFloorRoom($village_bind['single_id'],$village_bind['floor_id'],$village_bind['layer_id'],$village_bind['vacancy_id'],$village_bind['village_id']);
            if ($address) {
                $village_bind['address'] = $address;
            }
        }
        $message_pay_money=$messageDatas['pay_money']*1;
        $message_pay_money=round($message_pay_money,2);
        $messageDatas['address']=$village_bind['address'];
        if(!empty($now_user['openid'])){
            $templateNewsService = new TemplateNewsService();
            if (count($order_list) == 1 && $only_order_id) {
                $href = get_base_url('pages/houseMeter/NewCollectMoney/billDetails?order_id=' .$only_order_id.'&type=2');
            } else {
                $href = get_base_url('pages/houseMeter/NewCollectMoney/billsPaid?pigcms_id='.$cashier_order['pay_bind_id'].'&village_id='.$cashier_order['village_id']);
            }
            $datamsg = [
                'tempKey' => 'TM01008',//todo 类目模板TM01008
                'dataArr' => [
                    'href'     => $href,
                    'wecha_id' => $now_user['openid'],
                    'first'    => '缴费成功提醒',
                    'keynote1' => $keynote1,
                    'keynote2' => $keynote2,
                    'remark'   => $remark,
                    'new_info' => [//新版本发送需要的信息
                        'type'=>1,//缴费提醒类型（0：提醒缴费，1：提醒缴费成功）
                        'thing8'=>$newName,//户名
                        'thing17'=>$messageDatas['village_name'].$messageDatas['address'],//地址
                        'thing9'=>$thing9?:'缴费',//账单类型
                        'thing13'=>$thing13?:'无',//缴费方式
                        'thing10'=>$messageDatas['property_name'],//商家名称（物业名称）
                        'amount3'=>$message_pay_money.'元',//缴费金额
                        'time12'=>$dateStr,//缴费时间
                    ],
                ]
            ];
            fdump_api(['模板消息推送' . __LINE__, 'tempKey' => $datamsg['tempKey'], 'dataArr' => $datamsg['dataArr'], 'property_id' => $village_info['property_id'], 'datamsg' => $datamsg], 'after_pay/newPayServiceLog', 1);
            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,$village_info['property_id'],1);
        }
        if($village_info['openid']){
            if (isset($village_bind['address']) && $village_bind['address']) {
                $first =  '业主 '.$name.'( '.$village_bind['address'].' )缴费成功提醒';
            } else {
                $first = '用户 '.$name.' 缴费成功提醒';
            }
            $templateNewsService = new TemplateNewsService();
            $datamsg = [
                'tempKey' => 'TM01008',//todo 类目模板TM01008
                'dataArr' => [
                    'wecha_id' => $village_info['openid'],
                    'first'    => $first,
                    'keynote1' => $keynote1,
                    'keynote2' => $name."( ".$phone." )",
                    'remark'   => '社区缴费\n：'.$remark,
                    'new_info' => [//新版本发送需要的信息
                        'type'=>1,//缴费提醒类型（0：提醒缴费，1：提醒缴费成功）
                        'thing8'=>$newName,//户名
                        'thing17'=>$messageDatas['village_name'].$messageDatas['address'],//地址
                        'thing9'=>$thing9?:'缴费',//账单类型
                        'thing13'=>$thing13?:'无',//缴费方式
                        'thing10'=>$messageDatas['property_name'],//商家名称（物业名称）
                        'amount3'=>$message_pay_money.'元',//缴费金额
                        'time12'=>$dateStr,//缴费时间
                    ],
                ]
            ];
            fdump_api(['模板消息推送' . __LINE__, 'tempKey' => $datamsg['tempKey'], 'dataArr' => $datamsg['dataArr'], 'property_id' => $village_info['property_id'], 'datamsg' => $datamsg], 'after_pay/newPayServiceLog', 1);
            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,$village_info['property_id'],1);
        }
        if($now_order['order_type'] == 'property' || $ishavepropertyfee>0){
            try {
                $userListObj = $service_house_village_user_bind->getList([['vacancy_id','=',$cashier_order['room_id']], ['status','=',1]], 'uid, pigcms_id');
                if($userListObj && !$userListObj->isEmpty()) {
                    $userList=$userListObj->toArray();
                    $houseFaceImgService = new HouseFaceImgService();
                    foreach ($userList as $userInfo) {
                        $houseFaceImgService->commonUserToBox($userInfo['uid'], $userInfo['pigcms_id']);
                    }
                }
            } catch (\Exception $e){
                fdump_api($e->getMessage(),'$houseFaceImgService');
            }
        }

        $this->sendMessageToHouseWorker($village_id,$messageDatas);
    }

    public function sendMessageToHouseWorker($village_id=0,$datas=array()){

        if($village_id<1){
            return false;
        }
        $configCustomizationService=new ConfigCustomizationService();
        $yuanjino2o_customized_func=$configCustomizationService->getYuanJino2oCustomized();
        if($yuanjino2o_customized_func){
            $houseAppJpushInfoService = new HouseAppJpushInfoService();
            $otherData = array('village_id' => $village_id, 'wid' => 0,'village_name'=>$datas['village_name']);
            $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'house_order_paid_tpis');
        }
        $templateNewsService = new TemplateNewsService();
        $property_id=isset($datas['property_id']) ? $datas['property_id']:0;
        $type=$property_id>0 ? 1:0;
        $service_house_village = new HouseVillageService();
        $village_info_extend = $service_house_village->getHouseVillageInfoExtend(['village_id'=>$village_id]);
        $urge_notice_type=0;
        if($village_info_extend && isset($village_info_extend['urge_notice_type'])){
            //1短信通知2微信模板通知3短信和微信模板通知
            $urge_notice_type=$village_info_extend['urge_notice_type'];
        }
        $service_house_new_charge_project = new HouseNewChargeProjectService();
        $charge_info = $service_house_new_charge_project->getChargeSetInfo($village_id);
        $wids='';
        $dateStr=date('Y年n月j日H点i分',time());
        if($charge_info && isset($charge_info['wids']) && !empty($charge_info['wids'])){
            if(!is_array($charge_info['wids'])){
                $wids=json_decode($charge_info['wids'],1);
            }else{
                $wids=$charge_info['wids'];
            }
            fdump_api(['wids'=>$wids],'000sendMessageToHouseWorker',1);
            if(!empty($wids)){
                $href = cfg('site_url').'/packapp/community/pages/Community/NewCollectMoney/FeeDetails?order_id='.$datas['order_id'].'&type=2&gotoIndex=tem_msg';
                if($datas['type']==1){
                    $href = cfg('site_url').'/packapp/community/pages/Community/NewCollectMoney/PaymentDetails?summary_id='.$datas['summary_id'].'&gotoIndex=tem_msg';
                }
                $first='缴费成功提醒';
                if(!empty($datas['name'])){
                    $first=' 业主【'.$datas['name'].'】缴费成功提醒';
                }
                //查询物业名称
                $property_name = $datas['property_name']??'';
                if(!$property_name){
                    $property_name = (new HouseProperty())->where('id',$village_info_extend['property_id'])->value('property_name');
                }
                //查询收费类型
                $thing9 = $datas['order_type']??[];
                $thing9 = array_unique($thing9);
                $thing9 = implode('、',$thing9);
                if (mb_strlen($thing9, 'utf8') > 12) {
                    $thing9 = mb_substr($thing9, 0, 12, 'utf8') . '......';
                }
                $datas['pay_money']=round( $datas['pay_money'],2);
                //查询收费标准
                $thing13 = $datas['pay_type_str']??'';
                $datamsg = [
                    'tempKey' => 'TM01008',//todo 类目模板TM01008
                    'dataArr' => [
                        'href' => $href,
                        'wecha_id' => '',
                        'first' => $first ,
                        'keynote1' =>$datas['village_name'],
                        'keynote2' =>$datas['address'],
                        'remark' => '社区缴费\n缴费时间：'.$dateStr.'\n'.'已缴总额为：'.$datas['pay_money'].'元，点击查看！',
                        'new_info' => [//新版本发送需要的信息
                            'type'=>1,//缴费提醒类型（0：提醒缴费，1：提醒缴费成功）
                            'thing8'=>$datas['name'],//户名
                            'thing17'=>$datas['village_name'].$datas['address'],//地址
                            'thing9'=>$thing9?:'缴费',//账单类型
                            'thing13'=>$thing13?:'无',//缴费方式
                            'thing10'=>$property_name,//商家名称（物业名称）
                            'amount3'=>$datas['pay_money'].'元',//缴费金额
                            'time12'=>$dateStr,//缴费时间
                        ],
                    ]
                ];
                $houseWorkerDb=new HouseWorker();
                foreach ($wids as $wid){
                    if($wid>0){
                        $whereArr=[['wid','=',$wid],['is_del','=',0]];
                        $whereArr[]=['status','in',array(0,1)];
                        $workerObj=$houseWorkerDb->get_one($whereArr,'wid,village_id,property_id,type,phone,name,openid,nickname,status');
                        if($workerObj && !$workerObj->isEmpty()){
                            $worker=$workerObj->toArray();
                            if($urge_notice_type!=2 && !empty($worker['phone']) && strlen($worker['phone'])==11){
                                //发短信
                                //模板 缴费成功通知，业主{1}在{2}缴纳{3}小区{4}物业费用。缴费总金额为{5}元
                                $sms_data = array('type' => 'fee_notice');
                                $sms_data['uid'] = 0;
                                $sms_data['village_id'] = $village_id;
                                $sms_data['mobile'] = $worker['phone'];
                                $sms_data['sendto'] = 'user';
                                $sms_data['mer_id'] = 0;
                                $sms_data['store_id'] = 0;
                                $sms_data['nationCode'] =86;
                                $sms_content = L_('缴费成功通知，业主x1在x2缴纳x3小区的x4物业费用。缴费总金额为x5元', array('x1' => $datas['name'], 'x2' => $dateStr,'x3' =>$datas['village_name'],'x4' =>$datas['address'],'x5'=>$datas['pay_money']));
                                $sms_data['content']=$sms_content;
                                $sms = (new SmsService())->sendSms($sms_data);
                                fdump_api([$sms_data,$sms],'000sendMessageToHouseWorker',1);
                            }
                            if($urge_notice_type!=1 && !empty($worker['openid'])){
                                //发模板消息
                                $datamsg['dataArr']['wecha_id']=$worker['openid'];
                                fdump_api([$datamsg,$property_id,$type],'000sendMessageToHouseWorker',1);
                                $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,$property_id,$type);
                            }
                        }
                    }

                }
            }
        }
    }

    /**
     * 可得到的积分
     * @author lijie
     * @date_time 2021/06/24
     * @param $order_id
     * @param $now_user
     * @param bool $user_score
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_score($order_id,$now_user,$user_score=true){
        fdump_api([$order_id,$now_user,'user_score'=>$user_score],'payinfo_0224',1);
        $service_house_new_cashier = new HouseNewCashierService();
        $service_house_village_config = new HouseVillageConfigService();
        $service_config = new ConfigService();
        $db_plat_order = new PlatOrder();
        $plat_order_info = $db_plat_order->get_one(['order_id'=>$order_id]);
        if($plat_order_info && !$plat_order_info->isEmpty()){
            $plat_order_info=$plat_order_info->toArray();
        }else{
            return false;
        }
        $order_info = $service_house_new_cashier->getOrderSummary(['summary_id'=>$plat_order_info['business_id']]);
        // 其他缴费也可获得积分
        $percent = 0;            //计算物业费和抵扣金额
        //判断是否允许使用积分
        $village_config = $service_house_village_config->getConfig(['village_id'=>$order_info['village_id']]);
        //首先得到 积分比例 和 用户总积分
        $system_config = $service_config->addConfigData();
        //得到多少积分抵扣一元
        $moneytointegral = cfg('user_score_use_percent');
        //得到用户总积分
        $user_score_count = $now_user['score_count'];
        //得到总金额
        $total_price = $order_info['pay_money'];
        if($order_info['is_paid']==1){
            $total_price=$total_price+$order_info['system_balance']+$order_info['village_balance'];
        }
        $total_price=round($total_price,2);
        //第一步 判断是否允许使用积分 (注意 这里是针对设置了小区本身的缴费配置)
        if($village_config['village_pay_use_integral']==1){ //允许使用积分 针对小区开启
            $percent = $village_config['use_max_integral_percentage'];
            $property_info = $this->get_use_score($total_price ,$village_config['use_max_integral_percentage'] , $village_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
        }elseif($village_config['village_pay_use_integral']==0){ // 继承系统设置
            if(cfg('village_pay_use_integral')==1){
                $percent = cfg('use_max_integral_percentage');
                $property_info = $this->get_use_score($total_price ,cfg('use_max_integral_percentage') , cfg('use_max_integral_num') , $moneytointegral , $user_score_count);
            }else{
                $property_info['use_total_score_count'] = 0;
                $property_info['use_total_money'] = 0;
            }
        }else{
            $property_info['use_total_score_count'] = 0;
            $property_info['use_total_money'] = 0;
        }
        //计算可生成积分多少
        if($village_config['village_pay_integral']==1){ //允许生成积分
            if($village_config['open_score_get_percent']==1){
                // 开启了积分百分比， 取当前小区的积分百分比计算
                $score_get = $village_config['score_get_percent']/100;
            }else{
                // 关闭了积分百分比， 取当前小区的积分计算
                $score_get = $village_config['user_score_get'];
            }
            if(empty($score_get)){
                $score_get=0;
            }
            if ($user_score) { // 使用了积分
                $property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
            }else{ // 未使用积分
                $property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
            }
        }elseif($village_config['village_pay_integral']==0){ //继承平台设置 是否允许生成积分
            if(cfg('village_pay_integral')==1){ // 平台允许生成积分
                if(cfg('open_score_get_percent')==1){
                    // 开启了积分百分比， 取平台小区的积分百分比计算
                    $score_get = cfg('score_get_percent')/100;
                }else{
                    // 关闭了积分百分比， 取平台小区的积分计算
                    $score_get = cfg('user_score_get');
                }
                if(empty($score_get)){
                    $score_get=0;
                }
                if ($user_score) { // 使用了积分
                    $property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
                }else{ // 未使用积分
                    $property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
                }
            }else{
                $property_info['generate_integral'] = 0; // 得到生成积分
            }
        }elseif($village_config['village_pay_integral']==-1){ //不允许生成积分
            $property_info['generate_integral'] = 0;
        }
		$data_order=array();
        $data_order['score_can_use'] = round_number($property_info['use_total_score_count'],2);
        $data_order['score_can_pay'] = round_number($property_info['use_total_money'],2);
        $data_order['score_percent'] = round_number($percent,2);
        $data_order['score_can_get'] = round_number($property_info['generate_integral'],2);
        fdump_api(['village_config'=>$village_config,$property_info,$data_order,$total_price,$score_get],'payinfo_0224',1);

        $service_house_new_cashier->saveOrderSummary(['summary_id'=>$plat_order_info['business_id']],$data_order);
        return $data_order;
    }
    public function get_score_by_money($score_count=0,$total_price=0,$village_id=0,$user_score=true){
        $service_house_village_config = new HouseVillageConfigService();
        $service_config = new ConfigService();
        //得到总金额
        if($total_price<=0){
            return array();
        }
        // 其他缴费也可获得积分
        $percent = 0;            //计算物业费和抵扣金额
        //判断是否允许使用积分
        $village_config = $service_house_village_config->getConfig(['village_id'=>$village_id]);
        //首先得到 积分比例 和 用户总积分
        $system_config = $service_config->addConfigData();
        //得到多少积分抵扣一元
        $moneytointegral = cfg('user_score_use_percent');
        //得到用户总积分
        $user_score_count = $score_count;
        
        //第一步 判断是否允许使用积分 (注意 这里是针对设置了小区本身的缴费配置)
        if($village_config['village_pay_use_integral']==1){ //允许使用积分 针对小区开启
            $percent = $village_config['use_max_integral_percentage'];
            $property_info = $this->get_use_score($total_price ,$village_config['use_max_integral_percentage'] , $village_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
        }elseif($village_config['village_pay_use_integral']==0){ // 继承系统设置
            if(cfg('village_pay_use_integral')==1){
                $percent = cfg('use_max_integral_percentage');
                $property_info = $this->get_use_score($total_price ,cfg('use_max_integral_percentage') , cfg('use_max_integral_num') , $moneytointegral , $user_score_count);
            }else{
                $property_info['use_total_score_count'] = 0;
                $property_info['use_total_money'] = 0;
            }
        }else{
            $property_info['use_total_score_count'] = 0;
            $property_info['use_total_money'] = 0;
        }
        //计算可生成积分多少
        if($village_config['village_pay_integral']==1){ //允许生成积分
            if($village_config['open_score_get_percent']==1){
                // 开启了积分百分比， 取当前小区的积分百分比计算
                $score_get = $village_config['score_get_percent']/100;
            }else{
                // 关闭了积分百分比， 取当前小区的积分计算
                $score_get = $village_config['user_score_get'];
            }
            if(empty($score_get)){
                $score_get=0;
            }
            if ($user_score) { // 使用了积分
                $property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
            }else{ // 未使用积分
                $property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
            }
        }elseif($village_config['village_pay_integral']==0){ //继承平台设置 是否允许生成积分
            if(cfg('village_pay_integral')==1){ // 平台允许生成积分
                if(cfg('open_score_get_percent')==1){
                    // 开启了积分百分比， 取平台小区的积分百分比计算
                    $score_get = cfg('score_get_percent')/100;
                }else{
                    // 关闭了积分百分比， 取平台小区的积分计算
                    $score_get = cfg('user_score_get');
                }
                if(empty($score_get)){
                    $score_get=0;
                }
                if ($user_score) { // 使用了积分
                    $property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
                }else{ // 未使用积分
                    $property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
                }
            }else{
                $property_info['generate_integral'] = 0; // 得到生成积分
            }
        }elseif($village_config['village_pay_integral']==-1){ //不允许生成积分
            $property_info['generate_integral'] = 0;
        }
        $data_order=array();
        $data_order['score_can_use'] = round_number($property_info['use_total_score_count'],2);
        $data_order['score_can_pay'] = round_number($property_info['use_total_money'],2);
        $data_order['score_percent'] = round_number($percent,2);
        $data_order['score_can_get'] = round_number($property_info['generate_integral'],2);

        return $data_order;
    }
    // 计算可使用积分
    /****************************************************************
    $total_price 总价格
    $use_max_integral_percentage 使用最大积分占总额百分比
    $use_max_integral_num 允许使用的最大积分
    $moneytointegral 多少积分抵扣一元
    $user_score_count 用户总共多少积分
     ****************************************************************/
    public function get_use_score($total_price ,$use_max_integral_percentage , $use_max_integral_num , $moneytointegral , $user_score_count){
        if ($user_score_count<=0) {
            $property_info['use_total_score_count'] = 0;
            $property_info['use_total_money'] = 0;
            return $property_info;
        }
        if($use_max_integral_percentage > 0){ //使用最大百分比

            //得到最大抵扣金额
            $max_use_price = sprintf("%.2f",$total_price * $use_max_integral_percentage / 100);

            //计算可以使用多少积分
            $use_total_score_count = floor($max_use_price * $moneytointegral);

            //这里计算用户应该支出的积分  和 应该抵扣的钱  和 要支付的钱
            if($user_score_count < $use_total_score_count){

                $property_info['use_total_money'] =sprintf("%.2f",$user_score_count/$moneytointegral);
                $property_info['use_total_score_count'] = $property_info['use_total_money'] * $moneytointegral;

            }else{
                $property_info['use_total_money'] = $max_use_price;
                $property_info['use_total_score_count'] = $use_total_score_count;
            }
        }elseif($use_max_integral_num > 0){ //使用最大积分
            if($use_max_integral_num < $user_score_count){
                $property_info['use_total_money'] = sprintf("%.2f",$use_max_integral_num / $moneytointegral);
                $property_info['use_total_score_count'] = $property_info['use_total_money'] * $moneytointegral;
            }else{
                $property_info['use_total_money'] = sprintf("%.2f",$user_score_count/$moneytointegral);
                $property_info['use_total_score_count'] = $property_info['use_total_money'] * $moneytointegral;
            }

            if ($property_info['use_total_money']>$total_price) { // 可抵扣金额大于支付金额
                $property_info['use_total_money'] = $total_price;
                $property_info['use_total_score_count'] = $total_price * $moneytointegral;
            }
        }else{ // 否则不计算
            $property_info['use_total_score_count'] = 0;
            $property_info['use_total_money'] = 0;
        }
        return $property_info;
    }

    /**
     * 移动管理端线下支付
     * @author lijie
     * @date_time 2021/07/08
     * @param int $summary_id
     * @param int $pay_type
     * @param string $remark
     * @param string $app_type
     * @param int $offline_pay_type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pay($summary_id=0,$pay_type=1,$remark='',$offline_pay_type=0,$param=array())
    {
        if (!$summary_id) {
            return array('error_code'=>1,'msg'=>'参数传递出错！');
        }
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $service_house_village_parking = new HouseVillageParkingService();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_new_cashier = new HouseNewCashierService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $db_house_new_order_log = new HouseNewOrderLog();
        $summary_order_info = $db_house_new_pay_order_summary->getOne(['summary_id'=>$summary_id],true);
        if($summary_order_info['is_paid'] == 1){
            return array('error_code'=>1,'msg'=>'该订单已经支付！');
        }
        $village_id=$summary_order_info['village_id'];
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_list = $db_house_new_pay_order->getList(['o.summary_id'=>$summary_id],'o.*',0,0,'o.order_id ASC');
        if (empty($order_list)) {
            return array('error_code'=>1,'msg'=>'没有子订单！');
        }
        $configCustomizationService=new ConfigCustomizationService();
        $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
        $is_old_park_position=false;
        $yuanJino2oCustomize=$configCustomizationService->getYuanJino2oCustomized();
        if($yuanJino2oCustomize){
            $is_old_park_position=true;
        }
        $oder_pay_type=$pay_type;
        if($pay_type==42){
            /***PC收银台住户余额支付****/
            $oder_pay_type=4;
        }
        if($oder_pay_type == 2){
            $is_online = 0;
        }else{
            $is_online = 1;
        }

        $fact_pay_money=0; //本次缴费金额
        if($param && isset($param['fact_pay_money'])){
            $fact_pay_money=$param['fact_pay_money'];
        }
        $divide_order_arr=array();
        $uid=0;
        if($param && isset($param['uid'])){
            $uid=$param['uid'];
        }
        $wait_pay_money=0;
        if($param && isset($param['pay_money']) && ($param['pay_money']>0)){
            $wait_pay_money=$param['pay_money'];
        }
        if($is_aihorse_customized>0 && $wait_pay_money>0 && $fact_pay_money<=0 && ($pay_type==42 || $pay_type==2)){
            return array('error_code'=>1,'msg'=>'请输入本次缴费金额。');
        }
        if($pay_type==42 && $uid<=0){
            return array('error_code'=>1,'msg'=>'业主住户余额支付错误。');
        }
        if( $pay_type==42 && $uid>0){
            $db_village_user_money_list=new VillageUserMoneyList();
            $user_moneyObj=$db_village_user_money_list->getOne(['uid'=>$uid,'business_id'=>$village_id,'business_type'=>1],'*');
            $user_money=array();
            if($user_moneyObj && !$user_moneyObj->isEmpty()){
                $user_money=$user_moneyObj->toArray();
            }
            if(empty($user_money) || $user_money['current_money']<=0 || ($user_money['current_money']<$fact_pay_money)){
                return array('error_code'=>1,'msg'=>'业主住户余额不足，支付错误！');
            }
            $divide_order_arr['uid']=$uid;
            $divide_order_arr['village_balance']=$user_money['current_money'];
        }

        $total_money = 0.00;
        $pay_money = 0.00;
        $updateData=array();
        $updateData['is_paid'] = 1;
        $updateData['pay_time'] = time();
        $updateData['pay_type'] = $oder_pay_type;
        $updateData['is_online'] = $is_online;
        $updateData['offline_pay_type'] = $offline_pay_type;
        $updateData['update_time'] = time();
        $updateData['remark'] = $remark;
        if(!empty($param['extra_data']) && is_array($param['extra_data'])){
            $updateData['extra_data']=json_encode($param['extra_data'],JSON_UNESCAPED_UNICODE);
        }
        $configCustomizationService=new ConfigCustomizationService();
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        $pay_money_tmp = 0;
        $pay_order_ids=array();
        $is_break_for=false;
        $ticket_number_get='';
        foreach ($order_list as $value){
            if($is_break_for){
                break;
            }
            if(isset($value['ticket_number']) && $value['ticket_number']){
                $ticket_number_get=$value['ticket_number'];
            }
            $isHang = false;
            $is_discard = isset($value['is_discard']) ? intval($value['is_discard']) : 0;
            $hang_time = isset($value['hang_time']) ? $value['hang_time'] : 0;
            if ($is_discard == 3 && $hang_time > 1) {
                $isHang = true;
            }
            $pay_order_ids[]=$value['order_id'];
            $total_money += getFormatNumber($value['late_payment_money'] + $value['modify_money']);
            $pay_money = getFormatNumber($value['late_payment_money'] + $value['modify_money']);
            $pay_money_tmp += $value['late_payment_money'] + $value['modify_money'];
            $pay_money_tmp = round($pay_money_tmp,2);
            
            $updateData['service_start_time'] = $value['service_start_time'];
            $updateData['service_end_time']= $value['service_end_time'];
            $service_start_time = $value['service_start_time'];
            $service_end_time = $value['service_end_time'];
            $updateData['pay_money'] = $pay_money;
            $rule_info = $service_house_new_charge_rule->getCallInfo(['r.id'=>$value['rule_id']],'n.charge_type,r.*,p.type');
            if($rule_info['type'] == 2||$value['order_type'] == 'park_new'){
                if($value['position_id']){
                    $last_order = $db_house_new_order_log->getOne([['position_id','=',$value['position_id']],['order_type','=',$value['order_type']],['project_id','=',$value['project_id']]],true,'id DESC');
                } else{
                    $last_order = $db_house_new_order_log->getOne([['room_id','=',$value['room_id']],['order_type','=',$value['order_type']],['position_id','=',0],['project_id','=',$value['project_id']]],true,'id DESC');
                }
                if($last_order){
                    $last_order = $last_order->toArray();
                    if($last_order && !$isHang){
                        $updateData['service_start_time'] = $last_order['service_end_time']+100;
                        $updateData['service_start_time'] = strtotime(date('Y-m-d',$updateData['service_start_time']));
                        $service_start_time = $updateData['service_start_time'];
                        if($value['is_prepare'] == 1){
                            $cycle = $value['service_give_month_num'] + $value['service_month_num'];
                        }else{
                            $cycle = $value['service_month_num']?$value['service_month_num']:1;
                        }
                        if($rule_info['bill_create_set'] == 1){
                            $updateData['service_end_time'] = strtotime("+".$cycle." day",$updateData['service_start_time'])-1;
                        }elseif ($rule_info['bill_create_set'] == 2){
                            //todo 判断是不是按照自然月来生成订单
                            if(cfg('open_natural_month') == 1){
                                $updateData['service_end_time'] = strtotime("+".$cycle." month",$updateData['service_start_time'])-1;
                            }else{
                                $cycle = $cycle*30;
                                $updateData['service_end_time'] = strtotime("+".$cycle." day",$updateData['service_start_time'])-1;
                            }
                        }else{
                            $updateData['service_end_time'] = strtotime("+".$cycle." year",$updateData['service_start_time'])-1;
                        }
                        $service_end_time = $updateData['service_end_time'];
                    }
                }

                if (isset($value['f_order_id']) && $value['f_order_id'] > 0 && (isset($value['service_time_handle']) && ($value['service_time_handle'] != 1))) {

                } else if ($value['order_type'] == 'property') {
                    $service_house_village_user_bind->saveUserBind([
                        ['vacancy_id', '=', $value['room_id']],
                        ['type', 'in', [0, 3]],
                        ['status', '=', 1],
                    ], ['property_endtime' => $service_end_time]);
                    $spec = date('Y-m-d', $service_start_time) . '至' . date('Y-m-d', $service_end_time);
                }
                if ($value['order_type'] == 'park_new') {
                    $db_House_new_parking=new HouseNewParkingService();
                    $db_house_village_park_config=new HouseVillageParkConfig();
                    $db_house_village_parking_car=new HouseVillageParkingCar();
                    $db_house_village_parking_position=new HouseVillageParkingPosition();
                    $db_park_passage=new ParkPassage();
                    $db_house_village=new HouseVillage();
                    $db_park_system=new ParkSystem();
                    $db_park_total_record=new ParkTotalRecord();
                    $service_house_new_charge_prepaid=new HouseNewChargePrepaid();
                    $park_sys_type='';
                    $house_village =$db_house_village->getOne($value['village_id'],'village_name');
                    $park_system =$db_park_system->getFind(['park_id'=>$value['village_id']]);
                    $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
                    fdump_api([$value,$house_village,$park_system],'children_position_log_0803',1);
                    //月租车缴费
                    if ($value['car_type']=='month_type'){
                        $prepaid_info= $service_house_new_charge_prepaid-> getList(['charge_rule_id'=>$value['rule_id']]);
                        $parking_position =$db_house_village_parking_position->getFind(['position_id'=>$value['position_id']]);
                        $parking_car = $db_house_village_parking_car->getHouseVillageParkingCarLists(['car_position_id'=>$parking_position['position_id']],'*',0);
                        $channel_number_arr =$db_park_passage->getColumn(['village_id'=>$value['village_id']],'channel_number');
                        $channel_number_str = implode(',', $channel_number_arr);
                        fdump_api([$prepaid_info,$parking_position,$parking_car,$channel_number_arr,$channel_number_str],'children_position_log_0803',1);
                        if (!$channel_number_str) {
                            $channel_number_str = '';
                        }
                        if (2!=$parking_position['position_pattern']) {
                            // 固定车位-共同使用一个月卡会员编号
                            if ($parking_position['card_id']) {
                                $now_car_id = $parking_position['card_id'];
                            } else {
                                $now_car_id = '1'.sprintf("%09d",$parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                                $park_position_set['card_id'] = $now_car_id;
                            }
                            $address = $house_village['village_name'] .'车位：'.$parking_position['position_num'];
                        } else {
                            $address = $house_village['village_name'];
                        }
                        $garage_id=0;
                        if($parking_position && $parking_position['garage_id']>0){
                            $garage_id=$parking_position['garage_id'];
                        }
                        $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$value['village_id']);
                        $park_sys_type=$park_config['park_sys_type'];
                        // 如果是虚拟停车位 对应每一个车生成唯一收费编号
                        $parking_car_type_arr = $db_House_new_parking->parking_car_type_arr;
                        foreach($parking_car as $k=>$vv){
                            if ($parking_position['end_time']<time()){
                                $park_end_time=strtotime(date('Y-m-d 23:59:59',time()));
                            }else{
                                $park_end_time= $parking_position['end_time'];
                            }
                            $park_set = [];
                            $park_data['begin_time'] = $park_end_time+1;
                            if($value['is_prepare'] == 1){
                                if (!empty($prepaid_info)){
                                    $prepaid_info=$prepaid_info->toArray();
                                }
                                if (!empty($prepaid_info)){
                                    $prepaidInfo=$prepaid_info[0];
                                    if ($prepaidInfo['give_cycle_datetype']==1){
                                        $park_end_time = strtotime("+".$value['service_give_month_num']." day",$park_end_time);
                                    }elseif($prepaidInfo['give_cycle_datetype']==2){
                                        $park_end_time = strtotime("+".$value['service_give_month_num']." day",$park_end_time);
                                    }elseif($prepaidInfo['give_cycle_datetype']==3){
                                        $park_end_time = strtotime("+".$value['service_give_month_num']." day",$park_end_time);
                                    }
                                }

                                $cycle = $value['service_month_num'];
                            }else{
                                $cycle = $value['service_month_num']?$value['service_month_num']:1;
                            }
                            if($rule_info['bill_create_set'] == 1){
                                $park_data['end_time'] = strtotime("+".$cycle." day",$park_end_time);
                            }elseif ($rule_info['bill_create_set'] == 2){
                                //todo 判断是不是按照自然月来生成订单
                                if(cfg('open_natural_month') == 1){
                                    $park_data['end_time'] = strtotime("+".$cycle." month",$park_end_time);
                                }else{
                                    $cycle = $cycle*30;
                                    $park_data['end_time'] = strtotime("+".$cycle." day",$park_end_time);
                                }
                            }else{
                                $park_data['end_time'] = strtotime("+".$cycle." year", $park_end_time);
                            }
                            if (intval($parking_position['end_time']) < intval(time())) {
                                $park_data['begin_time'] = time();
                            }
                            $park_set['start_time'] = $park_data['begin_time'];
                            $park_set['end_time'] = $park_data['end_time'];
                            if ($park_sys_type!='D1') {
                                $park_data['park_id'] = $vv['village_id'];
                            }
                            $park_data['car_number'] = $vv['province'].$vv['car_number'];
                            if ($now_car_id) {
                                $park_position_set['start_time'] = $park_data['begin_time'];
                                $park_position_set['end_time'] = $park_data['end_time'];
                            }
                            if($park_sys_type =='D5'){
                                //todo D5智慧停车 触发下发到设备
                                (new D5Service())->D5AddCar($vv['village_id'],$vv['card_id'],$park_set['start_time'],$park_set['end_time'],'mobile',$garage_id);
                            }elseif ($park_sys_type =='D6'){
                                //todo D6智慧停车 触发下发到设备
                                fdump_api(['D6智慧停车续费成功=='.__LINE__,$vv],'d6_park/after_pay',1);
                                (new D6Service())->d6synVehicle($vv['village_id'],$vv['card_id'],0,$park_set);
                            }

                            //D3同步白名单到设备上
                            $white_record=[
                                'village_id'=>$vv['village_id'],
                                'car_number'=>$park_data['car_number']
                            ];
                            (new HouseVillageParkingService())->addWhitelist($white_record);
                            // 3.7月卡会员同步（停车云）
                            $park_data['remark'] = '';
                            $park_data['pid'] = '';
                            $service_name = 'month_member_sync';// 添加会员卡操作
                            if ($now_car_id) {
                                $card_id = $now_car_id;
                            } else {
                                $card_id = '2'.sprintf("%09d",$vv['car_id']);//月卡会员编号（收费系统唯一编号  是
                            }
                            // 同步储值卡
                            if ($parking_car_type_arr && $vv['parking_car_type'] && $parking_car_type_arr[$vv['parking_car_type']]) {
                                $car_type_id = $parking_car_type_arr[$vv['parking_car_type']];
                            } else if($vv['temporary_car_type']) {
                                $car_type_id = $vv['store_value_car_type'];
                            } else {
                                $car_type_id = '储值车A';
                            }
                            $park_set['card_id'] = $card_id;
                            if ($park_sys_type=='D1' && $vv['card_id']) {
                                $park_data['is_edit'] = 1;
                            } elseif ($park_sys_type!='D1') {
                                $park_data['operate_type'] = $vv['card_id'] ? '2' : '1';	//1 添加，2 编辑
                                if ($park_data['operate_type']==1) {
                                    $park_data['create_time'] = time();
                                } else {
                                    $park_data['update_time'] = time();
                                }
                                $park_data['price'] = $value['pay_money'];//实收金额
                                $park_data['channel_id'] = $channel_number_str;//月卡允许通行的通道编号
                                $park_data['amount_receivable'] = $value['pay_money'];//应收金额
                            }
                            $park_data['name'] = $vv['car_user_name'];
                            $park_data['car_type_id'] = $car_type_id;
                            $park_data['tel'] = $vv['car_user_phone'] ? $vv['car_user_phone'] : '';
                            $park_data['address'] = $address;
                            $park_data['p_lot'] = strval($parking_position['position_num']);

                            $park_data['card_id'] = $card_id;
                            $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                            $json_data['data'] = $park_data;
                            $json_data['service_name'] = $service_name;

                            $post_data['unserialize_desc'] = serialize($json_data);
                            $post_data['status'] = 2;
                            $post_data['card_id'] = $card_id;
                            $post_data['msg_id'] = $json_data['msg_id'];
                            $post_data['village_id'] = $value['village_id'];
                            $post_data['park_id'] = $park_system['park_id'];
                            $post_data['token'] = $park_system['token'];
                            $post_data['car_number'] = $park_data['car_number']?$park_data['car_number']:'';
                            $post_data['service_name'] = $service_name;
                            $post_data['create_time'] = time();
                            $res=$db_park_total_record->add($post_data);
                            // 没有配置停车场，或者数据添加成功都保存车辆信息。
                            if(!($park_system['park_id'] && $park_system['token']) || $res){
                                $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$vv['car_id']],$park_set);
                                if ($park_position_set) {
                                    $db_house_village_parking_position->saveOne(['position_id'=>$parking_position['position_id']],$park_position_set);
                                }
                            } else {
                                //2021/2/1 多车多位  修改 start
                                /* $infos = D('House_village_parking_car')->bind_parking_position($card_id);
                                 if($infos){
                                     $park_data['car_group'] = intval($infos['car_group']);
                                     $park_data['p_lot_number'] = intval($infos['p_lot_number']);
                                 }*/
                                //2021/2/1 多车多位  修改 end
                                $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                                $json_data['data'] = $park_data;
                                $json_data['service_name'] = $service_name;
                                $post_data['unserialize_desc'] = serialize($json_data);
                                $post_data['status'] = 2;
                                $post_data['card_id'] = $card_id;
                                $post_data['msg_id'] = $json_data['msg_id'];
                                $post_data['village_id'] = $vv['village_id'];
                                $post_data['park_id'] = $park_system['park_id'];
                                $post_data['token'] = $park_system['token'];
                                $post_data['car_number'] = $park_data['car_number']?$park_data['car_number']:'';
                                $post_data['service_name'] = $service_name;
                                $post_data['create_time'] = time();
                                $res=$db_park_total_record->add($post_data);//月卡会员下发
                                if($res){
                                    $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$vv['car_id']],$park_set);
                                    if ($park_position_set) {
                                        $db_house_village_parking_position->saveOne(['position_id'=>$parking_position['position_id']],$park_position_set);
                                    }
                                }
                            }
                        }
                        //子母车位续费同步到期时间
                        if ($park_config['children_position_type']==1){
                            $parking_position_arr =$db_house_village_parking_position->getColumn(['parent_position_id'=>$value['position_id'],'children_type'=>2],'position_id');
                            fdump_api([$value['position_id'],$parking_position_arr],'children_position_log_0803',1);
                            if (!empty($parking_position_arr)){
                                $db_house_village_parking_position->saveOne(['position_id'=>$parking_position_arr],$park_position_set);
                                $park_car_arr=$db_house_village_parking_car->get_column(['car_position_id'=>$parking_position_arr],'car_id');
                                fdump_api([$park_car_arr],'children_position_log_0803',1);
                                if (!empty($park_car_arr)){
                                    $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$park_car_arr],$park_set);
                                }
                            }
                        }
                    }
                }

                if(isset($value['f_order_id']) && $value['f_order_id']>0 && (isset($value['service_time_handle']) && ($value['service_time_handle']!=1))){
                    $updateData['service_start_time'] = $value['service_start_time'];
                    $updateData['service_end_time'] = $value['service_end_time'];
                }else{
                    /***车位到期时间 老版的和新版的冲突了 去掉老版的统一用新版的   有的老用户坚持要**/
                    if($is_old_park_position && ($value['order_type'] == 'park' || $value['order_type'] == 'parking_management')){
                        if($value['position_id']){
                            try{    
                                $service_house_village_parking->editParkingPosition(['position_id'=>$value['position_id']],['end_time'=>$service_end_time]);
                                $service_house_village_parking->editParkingCar(['car_position_id'=>$value['position_id']],['end_time'=>$service_end_time]);
                            }catch (\Exception $e) {
                                fdump_api([ __LINE__, 'err' => $e->getMessage(), 'code' => $e->getCode()], 'children_position_log_0803', true);
                            }
                        }
                    }
                 
                    if(!$isHang){
                        $db_house_new_order_log->addOne([
                            'order_id'=>$value['order_id'],
                            'project_id'=>$value['project_id'],
                            'order_type'=>$value['order_type'],
                            'order_name'=>$value['order_name'],
                            'room_id'=>$value['room_id'],
                            'position_id'=>$value['position_id'],
                            'property_id'=>$value['property_id'],
                            'village_id'=>$value['village_id'],
                            'service_start_time'=>$service_start_time,
                            'service_end_time'=>$service_end_time,
                            'add_time'=>time(),
                        ]);
                    }
                }

                if($value['order_type'] == 'property'){
                    try {
                        $userList = $service_house_village_user_bind->getList([['vacancy_id','=',$value['room_id']], ['status','=',1]], 'uid, pigcms_id');
                        $houseFaceImgService = new HouseFaceImgService();
                        foreach ($userList as $userInfo) {
                            $houseFaceImgService->commonUserToBox($userInfo['uid'], $userInfo['pigcms_id']);
                        }
                    } catch (\Exception $e){
                        fdump_api($e->getMessage(),'$houseFaceImgService');
                    }
                }
            }
            if($is_grapefruit_prepaid==1 && $value['rate']>0 && $value['diy_type']==1 && $value['diy_content'] && strpos($value['diy_content'],'预缴优惠')!==false){
                $updateData['pay_money']=($updateData['pay_money']*$value['rate'])/100;
                $updateData['pay_money']=round($updateData['pay_money'],2);
            }
            $pay_money_tmp = isset($param['pay_money']) && $param['pay_money'] ? $param['pay_money'] : 0;
            if($is_aihorse_customized>0 && $wait_pay_money>$fact_pay_money &&  ($pay_type==42 || $pay_type==2) &&  $fact_pay_money<=$pay_money_tmp){
                $divide_order_id=$value['order_id'];
                $divide_order_id_pay_money=$pay_money_tmp-$fact_pay_money;
                $divide_order_id_pay_money=round($divide_order_id_pay_money,2);  //此单未支付的钱
                if($divide_order_id_pay_money>0){
                    $pay_money_order = $updateData['pay_money'] - $divide_order_id_pay_money; //算出这个单子已支付的钱
                    $pay_money_order = round($pay_money_order, 2);
                    $updateData['pay_money'] = $pay_money_order;
                    $tmp_divide_pay_order = $db_house_new_pay_order->get_one(array('order_id' => $value['order_id']));
                    if ($tmp_divide_pay_order && !$tmp_divide_pay_order->isEmpty()) {
                        $tmp_divide_pay_order = $tmp_divide_pay_order->toArray();
                        if (isset($tmp_divide_pay_order['f_order_id']) && $tmp_divide_pay_order['f_order_id'] < 1) {
                            $tmp_divide_pay_order['f_order_id'] = $value['order_id'];
                        }
                        $extra_data_tmp = $tmp_divide_pay_order['extra_data'] ? json_decode($tmp_divide_pay_order['extra_data'], true) : array();
                        if ($extra_data_tmp && isset($extra_data_tmp['f_order_ids'])) {
                            $extra_data_tmp['f_order_ids'][] = $value['order_id'];
                        } elseif (empty($extra_data_tmp) && !isset($extra_data_tmp['f_order_ids'])) {
                            $extra_data_tmp['f_order_ids']=array();
                            if (isset($tmp_divide_pay_order['f_order_id']) && $tmp_divide_pay_order['f_order_id'] > 1) {
                                $extra_data_tmp['f_order_ids'][] = $tmp_divide_pay_order['f_order_id'];
                            }
                            $extra_data_tmp['f_order_ids'][] = $value['order_id'];
                        }
                        unset($tmp_divide_pay_order['order_id']);
                        $tmp_divide_pay_order['extra_data']=json_encode($extra_data_tmp,JSON_UNESCAPED_UNICODE);
                        $tmp_divide_pay_order['total_money']=$divide_order_id_pay_money;
                        $tmp_divide_pay_order['modify_money']=$divide_order_id_pay_money;
                        $tmp_divide_pay_order['summary_id']=0;
                        $tmp_divide_pay_order['is_paid']=2;
                        $tmp_divide_pay_order['pay_time']=0;
                        $tmp_divide_pay_order['remark']='订单ID'.$value['order_id'].'未支付金额部分生成新的订单';
                        $db_house_new_pay_order->addOne($tmp_divide_pay_order);
                    }
                }
                $is_break_for=true;
            }
            $updateData['is_discard']=1;
            $updateData['ticket_number']='';
            if( isset($param['ticket_number']) && $param['ticket_number']){
                $updateData['ticket_number']=$param['ticket_number'];
            }
            $db_house_new_pay_order->saveOne(['order_id'=>$value['order_id']],$updateData);
            if ($value['deposit_money']>0 && !$isHang){
                $db_house_new_deposit_log=new HouseNewDepositLog();
                $deposit_info=$db_house_new_deposit_log->get_one(['order_id'=>$value['order_id'],'type'=>2]);
                if (empty($deposit_info)){
                    $deposit_info1=$db_house_new_deposit_log->get_one(['room_id'=>$value['room_id']]);
                    $db_house_new_order_log->addOne([
                        'order_id'=>$value['order_id'],
                        'order_no'=>$summary_order_info['order_no'],
                        'type'=>2,
                        'before_money'=>$deposit_info1['total_money'],
                        'money'=>$value['deposit_money'],
                        'total_money'=>($deposit_info1['total_money']-$value['deposit_money']),
                        'role_id'=>$value['role_id'],
                        'room_id'=>$value['room_id'],
                        'village_id'=>$value['village_id'],
                        'add_time'=>time(),
                    ]);
                }
            }
            if($is_break_for){
                break;
            }
        }
        $order_summary_save=array('is_paid'=>1,'pay_time'=>time(),'pay_type'=>$oder_pay_type,'remark'=>$remark,'is_online'=>$is_online,'offline_pay_type'=>$offline_pay_type);
        if($is_break_for && $fact_pay_money>0 && !empty($pay_order_ids)){
            $order_summary_save['pay_money']=$fact_pay_money;
            $whereArrTemp=array(['summary_id','=',$summary_id]);
            $whereArrTemp[]=array('order_id','in',$pay_order_ids);
            $whereArrTemp[]=array('is_paid','=',2);
            //未支付的订单 将 summary_id重置成0
            $db_house_new_pay_order->saveOne($whereArrTemp,array('summary_id'=>0));
        }
        $res = $db_house_new_pay_order_summary->saveOne(['summary_id'=>$summary_id],$order_summary_save);
        $houseVillageOrderTicketNumberDb= new HouseVillageOrderTicketNumber();
        if(isset($param['ticket_number']) && !empty($param['ticket_number'])){
            $orderTicketNumberSave=array('summary_id'=>$summary_id,'use_time'=>time(),'ticket_status'=>2);
            if(!empty($pay_order_ids)){
                $orderTicketNumberSave['order_ids']= json_encode($pay_order_ids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $orderTicketNumberWhere=array(['village_id','=',$village_id],['ticket_fill_number','=',$param['ticket_number']]);
            $orderTicketNumberWhere[]=array('ticket_status','<',1);
            $orderTicketNumberWhere[]=array('del_time','<',10);
            $oneOrderTicketNumber=$houseVillageOrderTicketNumberDb->getOneData($orderTicketNumberWhere,'*','id ASC');
            if($oneOrderTicketNumber && !$oneOrderTicketNumber->isEmpty()){
                $houseVillageOrderTicketNumberDb->saveData(array('id'=>$oneOrderTicketNumber['id']),$orderTicketNumberSave);
            }
            
        }
        if($ticket_number_get){
            //释放之前占用的
            $orderTicketNumberSave=array('summary_id'=>0,'ticket_status'=>0);
            $orderTicketNumberWhere=array(['village_id','=',$village_id],['ticket_fill_number','=',$ticket_number_get]);
            $orderTicketNumberWhere[]=array('ticket_status','<',2);
            $orderTicketNumberWhere[]=array('del_time','<',10);
            $oneOrderTicketNumber=$houseVillageOrderTicketNumberDb->getOneData($orderTicketNumberWhere,'*','id ASC');
            if($oneOrderTicketNumber && !$oneOrderTicketNumber->isEmpty()){
                $houseVillageOrderTicketNumberDb->saveData(array('id'=>$oneOrderTicketNumber['id']),$orderTicketNumberSave);
            }
        }
        /*
        if(!$res){
            return array('error_code'=>1,'msg'=>'支付失败！');
        }
        */
        return array('error_code'=>0,'msg'=>'支付成功！');
    }
    /***
     * 移动管理端混合支付处理
    ***/
    public function offline_mix_pay($summary_id,$offline_pay_money=0,$village_id=0,$param=array()){
        $returnArr=array('error_code'=>1,'msg'=>'支付信息出错了！');
        if($offline_pay_money<=0){
            $returnArr['msg']='混合支付的线下支付金额错误!';
            return $returnArr;
        }
        if($summary_id<=0){
            $returnArr['msg']='支付ID信息错误!';
            return $returnArr;
        }
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $service_house_new_cashier = new HouseNewCashierService();
        $summary_order_info = $db_house_new_pay_order_summary->getOne(['summary_id'=>$summary_id],'*');
        if($summary_order_info['is_paid'] == 1){
            return array('error_code'=>1,'msg'=>'该订单已经支付了！');
        }
        $village_id=$summary_order_info['village_id'];
        if($offline_pay_money>=$summary_order_info['pay_money']){
            $returnArr['msg']='混合支付的线下支付金额不能大于实收金额!';
            return $returnArr;
        }         
        $extra_data=$summary_order_info['extra_data'] ? json_decode($summary_order_info['extra_data'],true):array();
        $order_summary_save=array('pay_type'=>9,'online_pay_type'=>'off_mix_pay','order_pay_type'=>'off_mix_pay');
        $order_summary_save['offline_pay_money']=$offline_pay_money;
        $db_house_new_pay_order_summary->saveOne(['summary_id'=>$summary_id],$order_summary_save);
        $pay_money_mix=$summary_order_info['pay_money']-$offline_pay_money;
        $service_house_village = new HouseVillageService();
        //混合支付
        $plat_order = array(
            'orderid' =>'',
            'business_type' => 'village_new_pay',
            'business_id' => $summary_id,
            'order_name' => '小区订单缴费',
            'uid' => $summary_order_info['pay_uid'],
            'total_money' => $pay_money_mix,
            'wx_cheap' => 0,
            'add_time'=>time(),
        );
        $order_id=0;
        if($extra_data && isset($extra_data['newPayOrderId'])){
            $order_count=count($extra_data['newPayOrderId']);
            if($order_count>1){
                $plat_order['order_name']='多缴费订单';
            }
            $order_id=$extra_data['newPayOrderId']['0'];
        }else if($extra_data && isset($extra_data['order_name']) && $extra_data['order_name']){
            $plat_order['order_name']=$extra_data['order_name'];
        }
        $plat_order['pay_type'] = '';
        $plat_order['pay_money'] = 0;
        $plat_order['third_id'] = '';
        $service_plat_order = new PlatOrderService();
        $service_recognition = new RecognitionService();
        $plat_id = $service_plat_order->addPlatOrder($plat_order);
        $base_url = $service_house_village->base_url;
        $site_url=cfg('site_url');
        $site_url=rtrim($site_url,'/');
        $pay_order_url=$site_url . $base_url . 'pages/pay/check?order_id=' . $plat_id . '&order_type=village_new_pay&resource_from=off_mix_pay';
        $qrcodeImg=$service_recognition->createQrcodeFlow($pay_order_url,'mix_pay_scan'.$summary_id.date('mdHis'));
        $qrcodeData=array('error_code'=>1,'msg'=>'','summary_id'=>$summary_id,'village_id'=>$village_id,'order_id'=>$order_id,'room_id'=>$summary_order_info['room_id'],'ewm_url'=>'','pay_xtype'=>'off_mix_pay','pay_order_url'=>$pay_order_url);
        if($qrcodeImg){
            $qrcodeData['error_code']=0;
            $qrcodeData['ewm_url']=$site_url.$qrcodeImg;
        }else{
            $qrcodeData['msg']='剩余支付二维码生成失败！';
        }
        return $qrcodeData;
    }
    /**
     * 用户端生成支付账单
     * @author lijie
     * @date_time 2021/07/16
     * @param int $pigcms_id
     * @param int $village_id
     * @param string $app_type
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function CashierApiGoPay($pigcms_id=0,$village_id=0,$app_type='',$param=[])
    {
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_parking = new HouseVillageParkingService();
        $service_house_new_cashier = new HouseNewCashierService();
        $service_plat_order = new PlatOrderService();
        $db_pay_order_info=new PayOrderInfo();
        $configCustomizationService=new ConfigCustomizationService();
        $houseNewChargeNumber=new HouseNewChargeNumber();
        $houseNewChargeProject=new HouseNewChargeProject();
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
        if($isWsxCustomizedOpen){
            $is_grapefruit_prepaid=1;
        }
        $func_kaidalaicn_special=$configCustomizationService->getfuncKaidalaicnSpecial();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $summaryData=array();
        $summaryData['village_id'] = $village_id;
        $summaryData['property_id'] = $village_info['property_id'];
        $db_house_new_select_project_record = new HouseNewSelectProjectRecord();
        $pay_user_info = array();
        if($pigcms_id>0){
            $pay_user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id]);
        }
        $pigcms_id_pay=$pigcms_id;
        $summary_id=0;
        if(isset($param['summary_id']) && $param['summary_id']>0){
            $summary_id=$param['summary_id'];
        }
        $uid=0;
        if(isset($param['uid']) && $param['uid']>0){
            $userDb=new User();
            $uid=$param['uid'];
            $userWhere=array(['uid','=',$param['uid']]);
            $pay_user_info_tmp =$userDb->getOne($userWhere,'uid,nickname as name,phone');
            if(empty($pay_user_info) || !isset($pay_user_info['uid'])){
                $pay_user_info=$pay_user_info_tmp;
            }else if($pay_user_info && $pay_user_info['uid']!=$pay_user_info_tmp['uid']){
                $pay_user_info['uid']=$pay_user_info_tmp['uid'];
                $pay_user_info['name']=$pay_user_info_tmp['name'];
                $pay_user_info['phone']=$pay_user_info_tmp['phone'];
                $pigcms_id_pay=0;
            }
        }
        fdump_api(['pigcms_id'=>$pigcms_id,'param'=>$param,'village_id'=>$village_id,'pay_user_info'=>$pay_user_info],'order_02245',1);
         if($summary_id>0 && $uid>0){
            $record_list = $db_house_new_select_project_record->getList(['village_id'=>$village_id,'uid'=>$uid]);
        }else if($pigcms_id>0){
            $record_list = $db_house_new_select_project_record->getList(['village_id'=>$village_id,'pigcms_id'=>$pigcms_id]);
        }
        
        if($record_list && !$record_list->isEmpty()){
            $record_list = $record_list->toArray();
            $tmpRecord=array();
            foreach ($record_list as $rkk=>$rvv){
                if(!isset($tmpRecord[$rvv['order_id']])){
                    $tmpRecord[$rvv['order_id']]=$rvv;
                }
            }
            $record_list=array_values($tmpRecord);
        }else{
            $record_list=array();
        }
        if(empty($record_list)){
            return  false;
        }
        $order_arr = [];
        $yearArray=array();
        $projectIdAry = [];
        foreach ($record_list as $v){
            if($v['order_id'] > 0){
                $projectIdAry[] = $v['project_id'];
            }
            $time = $service_house_new_cashier->getStartAndEndUnixTimestamp($v['year']);
            /**
            if ($is_grapefruit_prepaid){
                $order_list = $service_house_new_cashier->getOrderList([['o.order_id','=',$v['order_id']],['o.add_time','between',array($time['start'],$time['end'])],['o.is_discard','=',1],['o.is_paid','=',2],['o.room_id','=',$bind_info['vacancy_id']]],'o.*,r.late_fee_top_day,r.late_fee_reckon_day,r.late_fee_rate,r.charge_valid_type,n.charge_type');
				fdump_api(['time'=>$time,'order_list'=>$order_list],'order_02245',1);
            }else{
                $order_list = $service_house_new_cashier->getOrderList([['o.project_id','=',$v['project_id']],['o.add_time','between',array($time['start'],$time['end'])],['o.is_discard','=',1],['o.is_paid','=',2],['o.room_id','=',$bind_info['vacancy_id']]],'o.*,r.late_fee_top_day,r.late_fee_reckon_day,r.late_fee_rate,r.charge_valid_type,n.charge_type');
            }
            */
            $whereOrderArr=[['o.order_id','=',$v['order_id']],['o.add_time','between',array($time['start'],$time['end'])],['o.is_discard','=',1],['o.is_paid','=',2]];
             if($summary_id>0 && $uid>0){
                $whereOrderArr[]=['o.summary_id','=',$summary_id];
            }elseif($v['pigcms_id']>0){
                $bind_info = $service_house_village_user_bind->getBindInfo([['pigcms_id','=',$v['pigcms_id']]],'vacancy_id');
                if($bind_info && isset($bind_info['vacancy_id'])){
                    $whereOrderArr[]=['o.room_id','=',$bind_info['vacancy_id']];
                }else if($summary_id>0){
                    $whereOrderArr[]=['o.summary_id','=',$summary_id];
                }
            }
            $order_list = $service_house_new_cashier->getOrderList($whereOrderArr,'o.*,r.late_fee_top_day,r.late_fee_reckon_day,r.late_fee_rate,r.charge_valid_type,n.charge_type');
            fdump_api(['time'=>$time,'order_list'=>$order_list],'order_02245',1);
            if($order_list && !is_array($order_list)){
                $order_list = $order_list->toArray();
            }
			$order_arr = array_merge($order_arr,$order_list);
            if(isset($yearArray[$v['project_id']]) && ($v['year']<$yearArray[$v['project_id']])){
                $yearArray[$v['project_id']]=$v['year'];
            }elseif(!isset($yearArray[$v['project_id']])){
                $yearArray[$v['project_id']]=$v['year'];
            }
        }
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $customized_meter_reading=cfg('customized_meter_reading');
        $customized_meter_reading=!empty($customized_meter_reading) ? intval($customized_meter_reading):0;
        $order_type_flag_arr=array();
        $no_order_type_flag_count=0;
        $order_type_flag_last='';
        $nowTime = time();
        if(empty($order_arr)){
            throw new \Exception('订单信息错误！');
        }
        $now_order = $order_arr[0];
        $vacancy_id=$now_order['room_id'];
        $property_fee_pay_first_open=0;
        if($customized_meter_reading>0) {
            $whereArrExtend=array('village_id'=>$village_id);
            $villageInfoExtend = $service_house_village->getHouseVillageInfoExtend($whereArrExtend, 'meter_extended_data');
            $meterExtendedData=$villageInfoExtend['meter_extended_data'] ? json_decode($villageInfoExtend['meter_extended_data'],true):array();
            if(!empty($meterExtendedData) && isset($meterExtendedData['property_fee_pay_first']) && !empty($meterExtendedData['property_fee_pay_first'])){
                $property_fee_pay_first_open=$meterExtendedData['property_fee_pay_first']['is_open'];
                $property_fee_pay_first_open=intval($property_fee_pay_first_open);
            }
            if($property_fee_pay_first_open>0 && $vacancy_id>0){
                $propertyFeeWhere=array(['is_discard','=',1],['is_paid','=',2],['check_status','<>',1]);
                $propertyFeeWhere[]=['order_type','=','property'];
                $propertyFeeWhere[]=['room_id','=',$vacancy_id];
                $propertyOrderObj=$service_house_new_cashier->getInfo($propertyFeeWhere,'*');
                if(empty($propertyOrderObj) || $propertyOrderObj->isEmpty()){
                    $property_fee_pay_first_open=0;
                }
            }
        }
        $summaryExtraDataOrders=array();
        $order_type_balance=array('cold_water_balance'=>'冷水费','hot_water_balance'=>'热水费','electric_balance'=>'电费');
            foreach ($order_arr as $tmpV){
                $summaryExtraDataOrders[]=$tmpV['order_id'];
                if (!empty($tmpV['summary_id'])){
                    $plat_info = $service_plat_order->getPlatOrder(['business_id'=>$tmpV['summary_id'],'business_type'=>'village_new_pay']);
                    if (!empty($plat_info)){
                        $pay_order=$db_pay_order_info->getSome(['business'=>'village_new_pay','business_order_id'=>$plat_info['order_id']]);
                        if (!empty($pay_order)){
                            $pay_order=$pay_order->toArray();
                            $diffTime = $nowTime - intval($plat_info['add_time']);
                            if($jz_wsxdsj_net_customized_open){
                                $orderIdAry = array_column($pay_order,'orderid');
                                //检查是否已经支付在等待订单回调
                                $create_time=$nowTime-180;
                                $whereArr=[['orderid','in',$orderIdAry],['is_notify','=',0],['is_refund','=',0],['create_time','>',$create_time]];
                                $hasConstructionOrder = (new ConstructionOrderList())->getOne($whereArr);
                                if($hasConstructionOrder && !$hasConstructionOrder->isEmpty()){
                                    $out_trade_no=$hasConstructionOrder['orderid'];
                                    //throw new \Exception('含有已支付等待处理的订单，请等待建行处理结果(大约5分钟)！');
                                    $constructionPayService= new ConstructionPayService();
                                    $orderParam=array('out_trade_no'=>$out_trade_no,'again_to_pay'=>1);
                                    $queryOrder=$constructionPayService->changeOrder($orderParam);
                                    if(isset($queryOrder['is_paid']) && $queryOrder['is_paid']==1){
                                        throw new \Exception('含有已支付的订单，请刷新页面！');
                                    }
                                }
                                if (!empty($pay_order)&&$diffTime < 50){
                                    $startTimes = 50 - $diffTime;
                                    throw new \Exception("支付订单已生成，请勿重复操作,如需再次支付请{$startTimes}秒后操作");
                                }
                            }elseif (!empty($pay_order)&&$diffTime < 50){
                                $startTimes = 50 - $diffTime;
                                throw new \Exception("支付订单已生成，请勿重复操作,如需再次支付请{$startTimes}秒后操作");
                            }
                        }
                    }
                }
                if($customized_meter_reading>0){
                    if($property_fee_pay_first_open>0 &&  $vacancy_id>0 && $tmpV['order_type']!='property'){
                        $e_msg='需要先缴物业费待缴账单，请先去缴物业费待缴账单！';
                        throw new \Exception($e_msg);
                        return  false;
                    }
                    $order_type_flag=!empty($tmpV['order_type_flag']) ? $tmpV['order_type_flag']:'';
                    if(!empty($order_type_flag) && !empty($order_type_flag_arr) && !in_array($order_type_flag,$order_type_flag_arr)){
                        $e_msg=$order_type_balance[$order_type_flag].'不能和其他费用一起缴！';
                        throw new \Exception($e_msg);
                        return  false;
                    }else if(empty($order_type_flag) && !empty($order_type_flag_arr) && !empty($order_type_flag_last)){
                        $e_msg=$order_type_balance[$order_type_flag_last].'不能和其他费用一起缴！';
                        throw new \Exception($e_msg);
                        return  false;
                    }else if(!empty($order_type_flag) && !empty($order_type_flag_arr) && in_array($order_type_flag,$order_type_flag_arr) && $no_order_type_flag_count>0){
                        $e_msg=$order_type_balance[$order_type_flag].'不能和其他费用一起缴！';
                        throw new \Exception($e_msg);
                        return  false;
                    }
                    if(!empty($order_type_flag)){
                        $order_type_flag_last=$order_type_flag;
                    }else{
                        $no_order_type_flag_count++;
                    }
                    $order_type_flag_arr[$tmpV['order_id']]=$order_type_flag;
            }
        }

        if($yearArray && $now_order['order_type']!='park_new'){
            foreach ($yearArray as $project_id_key=>$yv){
                $ytime = $service_house_new_cashier->getStartAndEndUnixTimestamp($yv);
                $whereTempArr=[['o.project_id','=',$project_id_key],['o.add_time','<',$ytime['start']],['o.is_discard','=',1],['o.is_paid','=',2],['o.room_id','=',$vacancy_id],['o.check_status','<>',1],['order_type','<>','park_new']];
                $order_list_tmp = $service_house_new_cashier->getOrderList($whereTempArr,'o.*,r.late_fee_top_day,r.late_fee_reckon_day,r.late_fee_rate,n.charge_type,p.name as project_name',1,1);
                if($order_list_tmp && is_object($order_list_tmp) && !is_array($order_list_tmp) && !$order_list_tmp->isEmpty()){
                    $order_list_tmp=$order_list_tmp->toArray();
                }
                if($order_list_tmp && is_array($order_list_tmp)){
                    //还有之前未支付的订单
                    $order_name=$order_list_tmp['0']['order_name'];
                    if($order_list_tmp['0']['project_name']){
                        $order_name=$order_list_tmp['0']['project_name'];
                    }
                    $year_str=date('Y',$order_list_tmp['0']['add_time']);
                    $e_msg='您还有'.$year_str.'年的'.$order_name.'订单未支付！';
                    throw new \Exception($e_msg);
                    return  false;
                }
            }
        }
        fdump_api([$record_list,$order_arr],'order_02245',1);
        if($now_order['room_id']){
            $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$now_order['room_id']],['type','in','0,3'],['status','=',1]],'pigcms_id,name,phone,uid');
            $summaryData['room_id'] = $now_order['room_id'];
            if($user_info && is_object($user_info) && !$user_info->isEmpty()){
                $user_info=$user_info->toArray();
            }
            if(isset($now_order['position_id']) && $now_order['position_id']>0){
                $summaryData['position_id'] = $now_order['position_id'];
            }
        }else{
            $summaryData['position_id'] = $now_order['position_id'];
            /*
            $bind_position = $service_house_village_parking->getBindPosition(['position_id'=>$now_order['position_id']]);
            $user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$bind_position['user_id']],'pigcms_id,name,phone,uid');
            */
            $user_info=$service_house_new_cashier->getRoomUserBindByPosition($now_order['position_id'],$village_id);
        }
        $orderData=array();
        if(!empty($user_info) && isset($user_info['uid'])){
            $summaryData['uid'] = isset($user_info['uid'])?$user_info['uid']:0;
            $summaryData['pigcms_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;
            $orderData['uid'] = $user_info['uid'];
            $orderData['pigcms_id'] = $user_info['pigcms_id'];
            $orderData['name'] = $user_info['name'];
            $orderData['phone'] = $user_info['phone'];
        }
        
        if($pay_user_info && isset($pay_user_info['uid']) && $pay_user_info['uid']){
            $summaryData['uid']=$pay_user_info['uid'];
            $orderData['uid'] = $pay_user_info['uid'];
            if($pigcms_id_pay<1){
                $orderData['name'] = $pay_user_info['name'];
                $orderData['phone'] = $pay_user_info['phone'];
            }
        }
        
        $summaryData['pay_uid'] = isset($pay_user_info['uid'])?$pay_user_info['uid']:0;
        $summaryData['pay_bind_id'] = $pigcms_id_pay;
        $summaryData['order_no'] =  build_real_orderid($summaryData['pay_uid']);
        $summaryData['is_paid'] = 2;
        $summaryData['pay_type'] = 4;
        $summaryData['is_online'] = 1;
        if($summaryExtraDataOrders){
            $summaryExtraDataOrders=array_unique($summaryExtraDataOrders);
            $summaryExtraData = array('newPayOrderId' => $summaryExtraDataOrders);
            $summaryData['extra_data'] = json_encode($summaryExtraData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if($jz_wsxdsj_net_customized_open == 1 && $projectIdAry){
            //检查是否都是同一个收费账号
            $projectIdAry = array_unique($projectIdAry);
            $ruleIdAry = $houseNewChargeNumber->getColumnByProject([['p.id','in',$projectIdAry]],'rule_id');
            $ruleIdAry = $ruleIdAry ? array_unique($ruleIdAry) : [];
            if(count($ruleIdAry) > 1){
                throw new \Exception('不同收费账号的收费类别不能一起支付！');
            }
            //获取收费账号id
            $summaryData['rule_id'] = $ruleIdAry[0] ?? '';
        }
        if($summary_id>0){
            $save_summary_ret = $service_house_new_cashier->saveOrderSummary(array('summary_id'=>$summary_id,'village_id'=>$village_id),$summaryData);
            if($summaryExtraDataOrders){
                $whereTmpxArr=array(['summary_id','=',$summary_id],['order_id','not in',$summaryExtraDataOrders]);
                $service_house_new_cashier->saveOrder($whereTmpxArr,array('summary_id'=>0));
            }
        }else{
            $summary_id = $service_house_new_cashier->addOrderSummary($summaryData);
        }
        fdump_api([$user_info,$summaryData,$summary_id],'order_02245',1);
        $total_money = 0;
        $pay_money = 0;
        $late_payment_money=0;
        $orderData['summary_id'] = $summary_id;
        $orderData['is_paid'] = 2;
        $orderData['is_online'] = 1;
        $orderData['pay_bind_name'] = $pay_user_info['name'];
        $orderData['pay_bind_id'] = $pigcms_id_pay;
        $orderData['pay_bind_phone'] = $pay_user_info['phone'];
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($village_id,'property_id');
        $property_id = $village_info['property_id'];
        if($property_id){
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$property_id]);
        }else{
            $digit_info = [];
        }
        $deposit_money=$param['deposit_money'];
        $rule_month_num=[];
        foreach ($order_arr as $value){
            if (($is_grapefruit_prepaid==1 || $func_kaidalaicn_special>0) && isset($value['rule_id'])){
                $month_num=$value['service_month_num'];
                if(isset($value['unify_flage_id']) && !empty($value['unify_flage_id']) && $value['charge_valid_type']==3){
                    $month_num=$month_num/12;
                }
                if (isset($rule_month_num[$value['rule_id']])){
                    $rule_month_num[$value['rule_id']]['num']=$rule_month_num[$value['rule_id']]['num']+$month_num;
                    $rule_month_num[$value['rule_id']]['order_count']++;
                    $rule_month_num[$value['rule_id']]['money'] +=($value['modify_money'] + $value['late_payment_money']);
                    $rule_month_num[$value['rule_id']]['order_ids'][]=$value['order_id'];
                }else{
                    $rule_month_num[$value['rule_id']] = array();
                    $rule_month_num[$value['rule_id']]['num']=$month_num;
                    $rule_month_num[$value['rule_id']]['order_count'] = 1;
                    $rule_month_num[$value['rule_id']]['money']=($value['modify_money'] + $value['late_payment_money']);
                    $rule_month_num[$value['rule_id']]['rule_id'] = $value['rule_id'];
                    $rule_month_num[$value['rule_id']]['order_ids']= array($value['order_id']);
                }
            }
            $total_money += $value['total_money'];
            $pay_money += $value['late_payment_money'] + $value['modify_money'];
            $late_payment_money+=$value['late_payment_money'];
            $orderData['late_payment_money'] = $value['late_payment_money'];
            $orderData['late_payment_day'] = $value['late_payment_day'];
            $where = [];
            if($value['room_id'])
                $where[] = ['room_id','=',$now_order['room_id']];
            else
                $where[] = ['position_id','=',$now_order['position_id']];
            $where[] = ['add_time','<=',time()];
            $where[] = ['project_id','=',$value['project_id']];
            $where[] = ['is_paid','=',2];
            $where[] = ['is_discard','=',1];
            if(isset($value['order_id']) && $value['order_id']){
                $where[] = ['order_id','=',$value['order_id']];
            }
            $where[]=['check_status','<>',1];
            $service_house_new_cashier->saveOrder($where,$orderData);
            //押金解冻
            $edit_res=$this->editFrozenlog(['order_id'=>$value['order_id'],'type'=>3]);
            $orderData['pay_type'] = 4;
            if (!empty($param['deposit_type'])&&$deposit_money>0){
                if ($deposit_money>$pay_money){
                    $orderData['deposit_money'] = $pay_money;
                    $deposit_money=$deposit_money-$pay_money;
                }else{
                    $orderData['deposit_money'] = $deposit_money;
                    $deposit_money=0;
                }
                //冻结押金
                $frozen_log_data=[
                    'order_id'=>$value['order_id'],
                    'deposit_money'=>$orderData['deposit_money'],
                ];
                $add_res= $this->addFrozenlog($frozen_log_data);
               //  print_r([$add_res,$frozen_log_data]);die;
            }
            if ($is_grapefruit_prepaid == 1 || $func_kaidalaicn_special>0) {
                $orderData['rate']=0;
                $orderData['diy_type']=0;
                $orderData['diy_content']='';
            }
            $service_house_new_cashier->saveOrder($where,$orderData);
        }
        $discount_money=0;
        if ($pay_money>0 &&  !empty($rule_month_num)) {
            foreach ($rule_month_num as $kk => $rv) {
                $rv['num']=round($rv['num'],2);
                $rv['num']=floor($rv['num']);
                $rv['num']=$rv['num']*1;
                $discountArr = $service_house_new_cashier->getChargePrepaidDiscount($rv['rule_id'], $rv);
                $rule_month_num[$kk]['optimum'] = $discountArr['optimum'];
                $rule_month_num[$kk]['discount_money'] = $discountArr['discount_money'];
                $order_is_processed= isset($discountArr['order_is_processed']) ? $discountArr['order_is_processed']:0;
                if($order_is_processed <1 && !empty($discountArr['optimum']) && !empty($rv['order_ids'])){
                    $whereArrTmp=array(['order_id','in',$rv['order_ids']]);

                    $orderDataTmp=array('rate'=>$discountArr['optimum']['rate'],'diy_type'=>1,'diy_content'=>$discountArr['discount_type_str']);
                    $extra_data=array();
                    $extra_data['optimum']=$discountArr['optimum'];
                    $orderDataTmp['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                    $discount_info=array('rule_discount'=>$discountArr['optimum']);
                    $orderDataTmp['discount_info']=json_encode($discount_info,JSON_UNESCAPED_UNICODE);
                    $service_house_new_cashier->saveOrder($whereArrTmp,$orderDataTmp);
                }elseif($order_is_processed <1 && !empty($rv['order_ids'])){
                    $whereArrTmp=array(['order_id','in',$rv['order_ids']]);
                    $orderDataTmp=array('rate'=>0,'diy_type'=>0,'diy_content'=>'','discount_info'=>'');
                    $service_house_new_cashier->saveOrder($whereArrTmp,$orderDataTmp);
                }
                $discount_money += $discountArr['discount_money'];
            }
            $pay_money=$pay_money-$discount_money;
        }
        
        $total_money = formatNumber($total_money,2,1);
        $pay_money = formatNumber($pay_money,2,1);
        if (!empty($param['deposit_type'])){
            if ($pay_money>$param['deposit_money']){
                $pay_money = $pay_money-$param['deposit_money'];
            }else{
                $pay_money=0;
            }

        }else{
            $param['deposit_money']=0;
        }
        $plat_order = array(
            'orderid' =>'',
            'business_type' => 'village_new_pay',
            'business_id' => $summary_id,
            'order_name' => count($order_arr)>1?'多缴费订单':$now_order['order_name'],
            'uid' => $summaryData['pay_uid'],
            'total_money' => $pay_money,
            'wx_cheap' => 0,
            'add_time'=>time(),
        );
        if($app_type == 'packapp'){
            $plat_order['is_mobile_pay'] = 1;
        }elseif ($app_type == 'flutter' || $app_type == 1 || $app_type == 2){
            $plat_order['is_mobile_pay'] = 2;
        }elseif ($app_type == 'wxapp'){
            $plat_order['is_mobile_pay'] = 2;
        }

        
        $plat_id = $service_plat_order->addPlatOrder($plat_order);
        $service_house_new_cashier->saveOrderSummary(['summary_id'=>$summary_id],['deposit_money'=>$param['deposit_money'],'total_money'=>$total_money,'pay_money'=>$pay_money]);
        if ($pay_money<=0){
            $res = $this->offlineAfterPay($summary_id);
            $res_url=$this->getPayResultUrl($plat_id);
           //  print_r([$res_url,$res]);die;
            return ['order_id'=>$plat_id,'order_url'=>$res_url['redirect_url']];
        }else{
            $base_url = $service_house_village->base_url;
            $pay_order_url=cfg('site_url') . $base_url . 'pages/pay/check?order_id=' . $plat_id . '&order_type=village_new_pay';
            if($param && isset($param['resource_from']) && $param['resource_from'] =='constructionPayScan'){
                $pay_order_url=$pay_order_url.'&resource_from='.$param['resource_from'];
            }
            return ['order_id'=>$plat_id,'order_url'=>$pay_order_url,'order_type'=>'village_new_pay','type'=>'village_new_pay'];
        }

    }
    /**
     * 用户端生成支付账单(二维码项目付款)
     */
    public function CashierApiGoPayQrcode($pigcms_id=0,$village_id=0,$app_type='',$param=[])
    {
        if(!$param['rule_id']){
            return false;
        }
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_parking = new HouseVillageParkingService();
        $service_house_new_cashier = new HouseNewCashierService();
        $service_plat_order = new PlatOrderService();
        $db_pay_order_info=new PayOrderInfo();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $summaryData=array();
        $summaryData['village_id'] = $village_id;
        $summaryData['property_id'] = $village_info['property_id'];
        $db_house_new_select_project_record = new HouseNewSelectProjectRecord();
        if($pigcms_id){
            $pay_user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id]);
        }else{
            $pay_user_info = (new User())->where('uid',$param['uid'])->field('nickname as name,phone')->find();
        }
//        $record_list = $db_house_new_select_project_record->getList(['village_id'=>$village_id,'pigcms_id'=>$pigcms_id]);
//        if($record_list){
//            $record_list = $record_list->toArray();
//        }
//        if(empty($record_list)){
            //根据项目标准id查询项目信息
            $project_id = (new HouseNewChargeRule())->where('id',$param['rule_id'])->value('charge_project_id');
            $record_list = [[
                'year'=>'',
                'pigcms_id'=>$pigcms_id,
                'project_id'=>$project_id
            ]];
            //创建订单表
            $service_house_new_charge = new HouseNewChargeService();
            $payData = [
                'order_type'=>$param['order_type'],
                'order_name'=>$service_house_new_charge->charge_type[$param['order_type']],
                'village_id'=>$village_id,
                'property_id'=>$village_info['property_id'],
                'total_money'=>$param['pay_money'],
                'diy_type'=>4,
                'modify_money'=>$param['pay_money'],
                'prepare_pay_money'=>$param['pay_money'],
                'is_paid'=>2,
                'is_prepare'=>2,
                'rule_id'=>$param['rule_id'],
                'project_id'=>$project_id,
                'order_no'=>'',
                'add_time'=>time(),
                'from'=>1,
                'service_start_time'=>strtotime(date('Y-m-d',time())),
                'service_end_time'=>strtotime(date('Y-m-d',time())),
                'unit_price'=>$param['pay_money'],
                'pigcms_id'=>$pigcms_id,
                'name'=>$pay_user_info['name']??'',
                'phone'=>$pay_user_info['phone']??'',
                'uid'=>$param['uid'],
                'pay_bind_name'=>$pay_user_info['name']??'',
                'pay_bind_id'=>$pigcms_id,
                'pay_bind_phone'=>$pay_user_info['phone']??'',
            ];
            $id = $service_house_new_cashier->addOrder($payData);
            if(!$id){
                return false;
            }
//        }
        $order_arr = [];
        $yearArray=array();
        foreach ($record_list as $v){
            $time = $service_house_new_cashier->getStartAndEndUnixTimestamp($v['year']);
            $bind_info = $service_house_village_user_bind->getBindInfo([['pigcms_id','=',$v['pigcms_id']]],'vacancy_id');
            $order_list = $service_house_new_cashier->getOrderList([['o.order_id','=',$id],['o.project_id','=',$v['project_id']],['o.add_time','between',array($time['start'],$time['end'])],['o.is_discard','=',1],['o.is_paid','=',2],['o.order_type','=',$param['order_type']],['o.check_status','<>',1]],'o.*,r.late_fee_top_day,r.late_fee_reckon_day,r.late_fee_rate,n.charge_type');
            if($order_list && !is_array($order_list)){
                $order_list = $order_list->toArray();
            }
            if(isset($yearArray[$v['project_id']]) && ($v['year']<$yearArray[$v['project_id']])){
                $yearArray[$v['project_id']]=$v['year'];
            }elseif(!isset($yearArray[$v['project_id']])){
                $yearArray[$v['project_id']]=$v['year'];
            }
            $order_arr = array_merge($order_arr,$order_list);
        }
        $now_order = $order_arr[0];
        if($yearArray && $now_order['order_type']!='park_new'){
            foreach ($yearArray as $project_id_key=>$yv){
                $ytime = $service_house_new_cashier->getStartAndEndUnixTimestamp($yv);
                $whereTempArr=[['o.project_id','=',$project_id_key],['o.add_time','<',$ytime['start']],['o.is_discard','=',1],['o.is_paid','=',2],['o.room_id','=',$bind_info['vacancy_id']],['o.check_status','<>',1],['order_type','<>','park_new']];
                $order_list_tmp = $service_house_new_cashier->getOrderList($whereTempArr,'o.*,r.late_fee_top_day,r.late_fee_reckon_day,r.late_fee_rate,n.charge_type,p.name as project_name',1,1);
                if($order_list_tmp && is_object($order_list_tmp) && !is_array($order_list_tmp) && !$order_list_tmp->isEmpty()){
                    $order_list_tmp=$order_list_tmp->toArray();
                }
                if($order_list_tmp && is_array($order_list_tmp)){
                    //还有之前未支付的订单
                    $order_name=$order_list_tmp['0']['order_name'];
                    if($order_list_tmp['0']['project_name']){
                        $order_name=$order_list_tmp['0']['project_name'];
                    }
                    $year_str=date('Y',$order_list_tmp['0']['add_time']);
                    $e_msg='您还有'.$year_str.'年的'.$order_name.'订单未支付！';
                    throw new \Exception($e_msg);
                    return  false;
                }
            }
        }
        fdump_api([$record_list,$order_arr],'order_02245',1);
        
        if($now_order['room_id']){
            $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$now_order['room_id']],['type','in','0,3'],['status','=',1]],'pigcms_id,name,phone,uid');
            $summaryData['room_id'] = $now_order['room_id'];
            if($user_info && is_object($user_info) && !$user_info->isEmpty()){
                $user_info=$user_info->toArray();
            }
            if(isset($now_order['position_id']) && $now_order['position_id']>0){
                $summaryData['position_id'] = $now_order['position_id'];
            }
        }else{
            $summaryData['position_id'] = $now_order['position_id'];
            /*
            $bind_position = $service_house_village_parking->getBindPosition(['position_id'=>$now_order['position_id']]);
            $user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$bind_position['user_id']],'pigcms_id,name,phone,uid');
            */
            $user_info=$service_house_new_cashier->getRoomUserBindByPosition($now_order['position_id'],$village_id);
        }
        $orderData=array();
        if(!empty($user_info)){
            $summaryData['uid'] = isset($user_info['uid'])?$user_info['uid']:0;
            $summaryData['pigcms_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;
            $orderData['uid'] = $user_info['uid'];
            $orderData['pigcms_id'] = $user_info['pigcms_id'];
            $orderData['name'] = $user_info['name'];
            $orderData['phone'] = $user_info['phone'];
        }
        
        $summaryData['pay_uid'] = $param['uid'];
        $summaryData['pay_bind_id'] = $pigcms_id;
        $summaryData['order_no'] =  build_real_orderid($summaryData['pay_uid']);
        $summaryData['is_paid'] = 2;
        $summaryData['pay_type'] = 4;
        $summaryData['is_online'] = 1;
        $summary_id = $service_house_new_cashier->addOrderSummary($summaryData);
        fdump_api([$user_info,$summaryData,$summary_id],'order_02245',1);
        $total_money = 0;
        $pay_money = 0;
        $orderData['summary_id'] = $summary_id;
        $orderData['is_paid'] = 2;
        $orderData['is_online'] = 1;
        $orderData['pay_bind_name'] = $pay_user_info['name'];
        $orderData['pay_bind_id'] = $pigcms_id;
        $orderData['pay_bind_phone'] = $pay_user_info['phone'];
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($village_id,'property_id');
        $property_id = $village_info['property_id'];
        if($property_id){
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$property_id]);
        }else{
            $digit_info = [];
        }
        $deposit_money=$param['deposit_money'];
        $nowTime = time();
        foreach ($order_arr as $value){
            if (!empty($value['summary_id'])){
                $plat_info = $service_plat_order->getPlatOrder(['business_id'=>$value['summary_id'],'business_type'=>'village_new_pay']);
                if (!empty($plat_info)){
                    $pay_order=$db_pay_order_info->getSome(['business'=>'village_new_pay','business_order_id'=>$plat_info['order_id']]);
                    if (!empty($pay_order)){
                        $pay_order=$pay_order->toArray();
                        $diffTime = $nowTime - intval($plat_info['add_time']);
                        if (!empty($pay_order)&&$diffTime < 100){
                            $startTimes = 100 - $diffTime;
                            throw new \Exception("支付订单已生成，请勿重复操作,如需再次支付请{$startTimes}秒后操作");
                        }
                    }
                }
            }
            
            $total_money += $value['total_money'];
            $pay_money += $value['late_payment_money'] + $value['modify_money'];
            $orderData['late_payment_money'] = $value['late_payment_money'];
            $orderData['late_payment_day'] = $value['late_payment_day'];
            $where = [];
            if($value['room_id'])
                $where[] = ['room_id','=',$now_order['room_id']];
            else
                $where[] = ['position_id','=',$now_order['position_id']];
            $where[] = ['add_time','<=',time()];
            $where[] = ['project_id','=',$value['project_id']];
            $where[] = ['is_paid','=',2];
            $where[] = ['is_discard','=',1];
            if(isset($value['order_id']) && $value['order_id']){
                $where[] = ['order_id','=',$value['order_id']];
            }
            $where[]=['check_status','<>',1];
            $service_house_new_cashier->saveOrder($where,$orderData);
            //押金解冻
            $edit_res=$this->editFrozenlog(['order_id'=>$value['order_id'],'type'=>3]);
            $orderData['pay_type'] = 4;
            if (!empty($param['deposit_type'])&&$deposit_money>0){
                if ($deposit_money>$pay_money){
                    $orderData['deposit_money'] = $pay_money;
                    $deposit_money=$deposit_money-$pay_money;
                }else{
                    $orderData['deposit_money'] = $deposit_money;
                    $deposit_money=0;
                }
                //冻结押金
                $frozen_log_data=[
                    'order_id'=>$value['order_id'],
                    'deposit_money'=>$orderData['deposit_money'],
                ];
                $add_res= $this->addFrozenlog($frozen_log_data);
               //  print_r([$add_res,$frozen_log_data]);die;
            }
            $service_house_new_cashier->saveOrder($where,$orderData);
        }
        $total_money = formatNumber($total_money,2,1);
        $pay_money = formatNumber($pay_money,2,1);
        if (!empty($param['deposit_type'])){
            if ($pay_money>$param['deposit_money']){
                $pay_money = $pay_money-$param['deposit_money'];
            }else{
                $pay_money=0;
            }

        }else{
            $param['deposit_money']=0;
        }
        $plat_order = array(
            'business_type' => 'village_new_pay',
            'business_id' => $summary_id,
            'order_name' => count($order_arr)>1?'多缴费订单':$now_order['order_name'],
            'uid' => $summaryData['pay_uid'],
            'total_money' => $pay_money,
            'wx_cheap' => 0,
            'add_time'=>time(),
        );
        if($app_type == 'packapp'){
            $plat_order['is_mobile_pay'] = 1;
        }elseif ($app_type == 'flutter' || $app_type == 1 || $app_type == 2){
            $plat_order['is_mobile_pay'] = 2;
        }elseif ($app_type == 'wxapp'){
            $plat_order['is_mobile_pay'] = 2;
        }

        
        $plat_id = $service_plat_order->addPlatOrder($plat_order);
        $service_house_new_cashier->saveOrderSummary(['summary_id'=>$summary_id],['deposit_money'=>$param['deposit_money'],'total_money'=>$total_money,'pay_money'=>$pay_money]);
        if ($pay_money==0){
            $res = $this->offlineAfterPay($summary_id);
            $res_url=$this->getPayResultUrl($plat_id);
           //  print_r([$res_url,$res]);die;
            return ['order_id'=>$plat_id,'order_url'=>$res_url['redirect_url']];
        }else{
            $base_url = $service_house_village->base_url;
            return ['order_id'=>$plat_id,'order_url'=>cfg('site_url') . $base_url . 'pages/pay/check?order_id=' . $plat_id . '&order_type=village_new_pay','order_type'=>'village_new_pay','type'=>'village_new_pay'];
        }

    }

    /**
     * 预缴支付
     * @author lijie
     * @date_time 2021/07/27
     * @param array $order_list
     * @param int $village_id
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function CashierPrepaidGoPay($order_list=[],$village_id=0,$pigcms_id=0,$app_type='')
    {
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_parking = new HouseVillageParkingService();
        $service_house_new_cashier = new HouseNewCashierService();
        if(empty($order_list))
            return false;
        $pay_user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id]);
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $summaryData=array();
        $summaryData['village_id'] = $village_id;
        $summaryData['property_id'] = $village_info['property_id'];
        $now_order = $order_list;
        if(isset($now_order['room_id']) && $now_order['room_id']){
            $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$now_order['room_id']],['type','in','0,3'],['status','=',1]],'pigcms_id,name,phone,uid');
            if($user_info && is_object($user_info) && !$user_info->isEmpty()){
                $user_info=$user_info->toArray();
            }
            $summaryData['room_id'] = $now_order['room_id'];
            if(isset($now_order['position_id']) && $now_order['position_id']>0){
                $summaryData['position_id'] = $now_order['position_id'];
            }
        }else{
            $summaryData['position_id'] = $now_order['position_id'];
            /*
            $bind_position = $service_house_village_parking->getBindPosition(['position_id'=>$now_order['position_id']]);
            $user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$bind_position['user_id']],'pigcms_id,name,phone,uid');
            */
            $user_info=$service_house_new_cashier->getRoomUserBindByPosition($now_order['position_id'],$village_id);
        }
        $orderData=array();
        if(!empty($user_info)){
            $summaryData['uid'] = isset($user_info['uid'])?$user_info['uid']:0;
            $summaryData['pigcms_id'] = isset($user_info['pigcms_id'])?$user_info['pigcms_id']:0;
            $orderData['uid'] = $user_info['uid'];
            $orderData['name'] = $user_info['name'];
            $orderData['phone'] = $user_info['phone'];
        }
        $summaryData['pay_uid'] = isset($pay_user_info['uid'])?$pay_user_info['uid']:0;
        $summaryData['is_paid'] = 2;
        $summaryData['pay_bind_id'] = $pigcms_id;
        $summaryData['order_no'] =  build_real_orderid($summaryData['pay_uid']);
        $summaryData['pay_type'] = 4;
        $summary_id = $service_house_new_cashier->addOrderSummary($summaryData);

        $total_money = 0;
        $pay_money = 0;
        $orderData['summary_id'] = $summary_id;

        $orderData['is_paid'] = 2;
        $total_money += $now_order['total_money'];
        $pay_money += $now_order['modify_money'];
        $orderData['late_payment_money'] = 0;
        $orderData['late_payment_day'] = 0;
        $where = [];
        $where['order_id'] = $now_order['order_id'];
        $service_house_new_cashier->saveOrder($where,$orderData);
        $plat_order = array(
            'business_type' => 'village_new_pay',
            'business_id' => $summary_id,
            'order_name' => $now_order['order_name'],
            'uid' => $summaryData['pay_uid'],
            'total_money' => $pay_money,
            'wx_cheap' => 0,
            'add_time'=>time(),
        );
        if($app_type == 'packapp'){
            $plat_order['is_mobile_pay'] = 1;
        }elseif ($app_type == 'flutter' || $app_type == 1 || $app_type == 2){
            $plat_order['is_mobile_pay'] = 2;
        }elseif ($app_type == 'wxapp'){
            $plat_order['is_mobile_pay'] = 2;
        }
        $service_plat_order = new PlatOrderService();
        $plat_id = $service_plat_order->addPlatOrder($plat_order);
        $service_house_new_cashier->saveOrderSummary(['summary_id'=>$summary_id],['total_money'=>$total_money,'pay_money'=>$pay_money]);
        $base_url = $service_house_village->base_url;
        return ['order_id'=>$plat_id,'order_url'=>cfg('site_url') . $base_url . 'pages/pay/check?order_id=' . $plat_id . '&order_type=village_new_pay','order_type'=>'village_new_pay','type'=>'village_new_pay'];
    }
    
    public function getOneHouseNewPayOrder($whereArr=array(),$field='*'){
        $service_house_new_pay_order = new HouseNewPayOrder();
        $new_pay_order=$service_house_new_pay_order->get_one($whereArr,$field);
        $pay_order=array();
        if($new_pay_order && !$new_pay_order->isEmpty()){
            $pay_order= $new_pay_order->toArray();
        }
        return $pay_order;
    }
    /**
     * 支付之前调用
     * @author lijie
     * @date_time 2021/07/27
     * @param $order_id
     * @return mixed
     */
    public function getOrderPayInfo($order_id)
    {
        $service_house_village_order = new PlatOrderService();
        $service_house_new_cashier = new HouseNewCashierService();
        $order_info = $service_house_village_order->getPlatOrder(['order_id'=>$order_id]);
        
        $summary_field = 'village_id,is_paid,room_id';
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if($jz_wsxdsj_net_customized_open){
            $summary_field .= ',rule_id,pigcms_id,order_no';
        }
        $summary_info = $service_house_new_cashier->getOrderSummary(['summary_id'=>$order_info['business_id']],$summary_field);
        
        $service_house_new_pay_order = new HouseNewPayOrder();
        $order_list=$service_house_new_pay_order->getPayLists(['summary_id'=>$order_info['business_id'],'village_id'=>$summary_info['village_id']],'*');
        $customized_meter_reading=cfg('customized_meter_reading');
        $is_customized_meter_reading=!empty($customized_meter_reading) ? intval($customized_meter_reading):0;
        $data['village_id'] = $summary_info['village_id'];
        $data['is_paid'] = $summary_info['is_paid'];
        $data['paid'] = 0;
        $data['mer_id'] = 0;
        $data['city_id'] = 0;//城市id
        $data['store_id'] = 0;
        $data['order_money'] = $order_info['total_money'];//订单支付金额
        $data['uid'] = $order_info['uid'];//用户id
        $data['order_no'] = $order_id;//订单号
        $data['title'] = '生活缴费支付';
        $data['time_remaining'] = $order_info['add_time']+15*60-time();//倒计时（15分钟），订单可付款的剩余时间，超出不可支付
        $data['is_cancel'] = 0;//1表示取消订单
        $data['goods_desc']='';
        
        if($order_list && !is_array($order_list) && !$order_list->isEmpty()){
            $order_list=$order_list->toArray();
        }
        //建行支付v2.2所需参数
        $data['summary_order_no'] = $summary_info['order_no'] ?? '';
        $data['rule_id'] = $summary_info['rule_id'] ?? '';
        $data['business_type'] = $order_info['business_type'];
        
        $order_type_flag='';
        $have_no_order_type_flag=0;
        $isRecharge = false;//是否包含充值订单
        if(!empty($order_list)){
            $goods_desc='';
            foreach ($order_list as $ovv){
                $isRecharge = in_array($ovv['order_type'],['qunuanfei']) ? 1 : 0;
                if(isset($ovv['order_type_flag'])){
                    $order_type_flag=!empty($ovv['order_type_flag']) ? $ovv['order_type_flag']:'';
                }else{
                    $have_no_order_type_flag=true;
                }
                if($ovv['order_type']=='park_new'){
                    $goods_desc='停车费用';
                }else{
                    $goods_desc='';
                    $car_number='';
                    break;
                }
            }
            $count=count($order_list);
            if($goods_desc && $count==1 && !empty($order_list['0']['car_number'])){
                $goods_desc='停车费用-'.$order_list['0']['car_number'];
            }
            $data['goods_desc']=$goods_desc;
            if($is_customized_meter_reading>0 && $order_type_flag && !$have_no_order_type_flag){
                $data['village_order_type_flag']=$order_type_flag;
            }
        }
        
        $data['is_recharge'] = $isRecharge;
        if($jz_wsxdsj_net_customized_open){
            $payInfo = (new PayOrderInfo())->getOne(['business'=>'village_new_pay','business_order_id'=>$order_info['order_id']],'orderid');
            $payInfo = $payInfo ? $payInfo->toArray() : [];
            $orderid = isset($payInfo['orderid']) && $payInfo['orderid'] ? $payInfo['orderid'] : 0;
            if($orderid){
                $constructionOrder = (new ConstructionOrderList())->getOne(['orderid'=>$orderid,'is_notify'=>0,'is_refund'=>0],'pigcms_id');
                $data['wait_construction_notify'] = isset($constructionOrder['pigcms_id']) && $constructionOrder['pigcms_id'] ? 1 : 0;//等待建行处理
            }
        }
        $getIntegralPool=(new ConfigCustomizationService())->getIntegralPool();
        $data['use_score'] = true;
        $data['room_id'] = $summary_info['room_id'];
        if($getIntegralPool){
            //查询有没有拖欠物业费
            $propertyOrder = $service_house_new_pay_order->get_one([
                'room_id'=>$summary_info['room_id'],
                'order_type'=>'property',
                'is_paid'=>2,
                'is_discard'=>1
            ],'service_start_time','o.order_id ASC');
            $propertyOrder = $propertyOrder ? $propertyOrder->toArray() : [];
            $data['use_score'] = (isset($propertyOrder['service_start_time']) && strtotime(date('Y-m-d',$propertyOrder['service_start_time'])) <= strtotime(date('Y-m-d'))) ? false : true;

        }
        unset($order_list);
        return $data;
    }

    /**
     * 支付成功后跳转url
     * @author lijie
     * @date_time 2021/07/27
     * @param int $order_id
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPayResultUrl($order_id=0)
    {
        if (empty($order_id)) {
            throw new \think\Exception("请上传订单id！");
        }
        $service_order = new PlatOrderService();
        $service_pay_order = new HouseNewPayOrderSummary();
        $service_house_new_pay_order = new HouseNewPayOrder();
        $plat_info = $service_order->getPlatOrder(['order_id' => $order_id]);
        $order_info = $service_pay_order->getOne(['summary_id'=>$plat_info['business_id']],'pay_bind_id,village_id');
        $order_list=$service_house_new_pay_order->get_one(['summary_id'=>$plat_info['business_id']],'order_id,order_type,car_number');
        if (!$plat_info || !$order_info||!$order_list) {
            throw new \think\Exception("订单信息不存在！");
        }
        $url = get_base_url('pages/houseMeter/NewCollectMoney/billsPaid?pigcms_id=' .$order_info['pay_bind_id'].'&village_id='.$order_info['village_id']);
        if($plat_info['order_name'] == '非机动车停车费'){
            $url = get_base_url('pages/houseMeter/BicycleLane/PayRecord?pigcms_id=' .$order_info['pay_bind_id'].'&village_id='.$order_info['village_id']);
        }

        if($order_list['order_type'] == 'park_new'){
            $url = get_base_url('pages/parkingLot/pages/payList?village_id='.$order_info['village_id']);
        }
        if($order_list['order_type'] == 'pile'){
            if (!empty($order_list['car_number'])){
                $url = get_base_url('pages/newCharge/pages/startCharging?equipment_num='.$order_list['car_number']);
            }else{
                $url = get_base_url('pages/newCharge/index?village_id='.$order_info['village_id']);
            }
        }
        $res['redirect_url'] = $url;
        $res['direct'] = 1;
        return $res;
    }
    
    
    public function setHouseNewOrderLog($now_order) {
        $service_start_time = $now_order['service_start_time'];
        $service_end_time = $now_order['service_end_time'];
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $db_house_new_order_log = new HouseNewOrderLog();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_parking = new HouseVillageParkingService();
        $rule_info = $service_house_new_charge_rule->getCallInfo(['r.id'=>$now_order['rule_id']],'n.charge_type,r.*,p.type');
        if($rule_info['type'] == 2){
            if($now_order['position_id']){
                $last_order = $db_house_new_order_log->getOne([['position_id','=',$now_order['position_id']],['order_type','=',$now_order['order_type']],['project_id','=',$now_order['project_id']]],true,'id DESC');
            } else{
                $last_order = $db_house_new_order_log->getOne([['room_id','=',$now_order['room_id']],['order_type','=',$now_order['order_type']],['position_id','=',0],['project_id','=',$now_order['project_id']]],true,'id DESC');
            }
            // 兼容老版停车收费
            if($now_order['order_type'] == 'park'){
                if($now_order['position_id']){
                    $where22=[
                        ['position_id','=',$now_order['position_id']],
                        ['order_type','=',$now_order['order_type']],
                        ['project_id','=',0],
                    ];
                }else{
                    $where22=[
                        ['room_id','=',$now_order['room_id']],
                        ['order_type','=',$now_order['order_type']],
                    ];
                }
                $new_order_log = $db_house_new_order_log->getOne($where22,true,'id DESC');
                if(!empty($new_order_log)){
                    $last_order=$new_order_log;
                }
            }elseif ($now_order['order_type'] == 'property'){
                if($now_order['position_id']){
                    $where22=[
                        ['position_id','=',$now_order['position_id']],
                        ['order_type','=',$now_order['order_type']],
                    ];
                }else{
                    $where22=[
                        ['room_id','=',$now_order['room_id']],
                        ['position_id','=',0],
                        ['order_type','=',$now_order['order_type']],
                    ];
                }
                $new_order_log = $db_house_new_order_log->getOne($where22,true,'id DESC');
                if(!empty($new_order_log)){
                    $last_order=$new_order_log;
                }
            }
            if($last_order){
                $last_order = $last_order->toArray();
                if($last_order && isset($last_order['id'])){
                    $date_order['service_start_time'] = $last_order['service_end_time']+100;
                    $date_order['service_start_time'] = strtotime(date('Y-m-d',$date_order['service_start_time']));
                    $service_start_time = $date_order['service_start_time'];
                    if($now_order['is_prepare'] == 1){
                        $cycle = $now_order['service_give_month_num'] + $now_order['service_month_num'];
                    }else{
                        $cycle = $now_order['service_month_num']?$now_order['service_month_num']:1;
                    }
                    if($rule_info['bill_create_set'] == 1){
                        $date_order['service_end_time'] = strtotime("+".$cycle." day",$date_order['service_start_time'])-1;
                    }elseif ($rule_info['bill_create_set'] == 2){
                        //todo 判断是不是按照自然月来生成订单
                        if(cfg('open_natural_month') == 1){
                            $date_order['service_end_time'] = strtotime("+".$cycle." month",$date_order['service_start_time'])-1;
                        }else{
                            $cycle = $cycle*30;
                            $date_order['service_end_time'] = strtotime("+".$cycle." day",$date_order['service_start_time'])-1;
                        }
                        fdump_api(['线上支付【账单生成周期设置 1：按日生成2：按月生成3：按年生成】 当前是'.$rule_info['bill_create_set'].'=='.__LINE__,(cfg('open_natural_month') == 1 ? '开启自然月' : '未开启自然月'),'天数：'.$cycle,'service_end_time：'.date('Y-m-d H:i:s',$date_order['service_end_time'])],'park/shang_log',1);
                    }else{
                        $date_order['service_end_time'] = strtotime("+".$cycle." year",$date_order['service_start_time'])-1;
                    }
                    $service_end_time = $date_order['service_end_time'];
                }
            }
            $db_house_new_order_log->addOne([
                'order_id'=>$now_order['order_id'],
                'project_id'=>$now_order['project_id'],
                'order_type'=>$now_order['order_type'],
                'order_name'=>$now_order['order_name'],
                'room_id'=>$now_order['room_id'],
                'position_id'=>$now_order['position_id'],
                'property_id'=>$now_order['property_id'],
                'village_id'=>$now_order['village_id'],
                'service_start_time'=>$service_start_time,
                'service_end_time'=>$service_end_time,
                'add_time'=>time(),
            ]);
            if($now_order['order_type'] == 'property'){
                $service_house_village_user_bind->saveUserBind([
                    ['vacancy_id','=',$now_order['room_id']],
                    ['type','in',[0,3]],
                    ['status','=',1],
                ],['property_endtime'=>$service_end_time]);
                $spec = date('Y-m-d',$service_start_time) . '至' . date('Y-m-d',$service_end_time);
            }
            /***车位到期时间 老版的和新版的冲突了 去掉老版的统一用新版的
            if($now_order['order_type'] == 'park' || $now_order['order_type'] == 'parking_management'){
                if($now_order['position_id']){
                    try{    
                        $service_house_village_parking->editParkingPosition(['position_id'=>$now_order['position_id']],['end_time'=>$service_end_time]);
                        $service_house_village_parking->editParkingCar(['car_position_id'=>$now_order['position_id']],['end_time'=>$service_end_time]);
                    }catch (\Exception $e) {
                        
                    }
                }
            }
            **/
            if($now_order['order_type'] == 'property'){
                try {
                    $userList = $service_house_village_user_bind->getList([['vacancy_id','=',$now_order['room_id']], ['status','=',1]], 'uid, pigcms_id');
                    $houseFaceImgService = new HouseFaceImgService();
                    foreach ($userList as $userInfo) {
                        $houseFaceImgService->commonUserToBox($userInfo['uid'], $userInfo['pigcms_id']);
                    }
                } catch (\Exception $e){
                    fdump_api($e->getMessage(),'$houseFaceImgService');
                }
            }
        }
        return true;
    }
    
    /**
     * 获取用户积分
     */
    public function getUserScoreCount($roomId,$scoreCount)
    {
        $getIntegralPool=(new ConfigCustomizationService())->getIntegralPool();
        if($getIntegralPool && $roomId){
            //查房间积分池（房间的业主、家属、租客的积分之和）
            $scoreCount = (new HouseVillageUserBind())->getScoreCount([
                ['ub.vacancy_id','=',$roomId],
                ['ub.status','=',1],
                ['ub.type','in',[0,1,2,3]],
            ],'sum(u.score_count)');
        }
        return $scoreCount;
    }
    
    /**
     * 积分扣除处理
     * 积分使用总和占积分总和获取百分比，用百分比乘每个人的积分即为每个人扣除的积分。
     * 由于百分比小数只保留四位，如果最后扣除积分之和小于积分使用数，差值由积分最高的人承担，积分最高的人积分不够的话，继续扣下一个最高的。
     * @date 2023/11/17
     * @param $roomId 房间id
     * @param $scoreUse 使用的积分
     * @param $orderNo 订单编号
     * @param $scoreCanGet 本次订单获得的积分
     * @param $payUid 支付本次订单的用户id
     * @return array|bool[]
     */
    public function userIntegralPool($roomId,$scoreUse,$orderNo,$scoreCanGet,$payUid)
    {
        $return = [
            'error_code'=>false
        ];
        if($scoreUse <= 0){
            return $return;
        }
        $scoreUse = strval($scoreUse);
        $scoreCanGet = strval($scoreCanGet);
        //获取房间的业主、家属、租户
        $userList = (new HouseVillageUserBind())->getRoomBindUser([
            ['ub.vacancy_id','=',$roomId],
            ['ub.status','=',1],
            ['ub.type','in',[0,1,2,3]],
            ['u.score_count','>',0],
        ],'u.uid,u.score_count');
        if(!$userList){
            return $return;
        }
        //获取积分总和(要减去本订单获得的积分)
        $scoreAll = array_sum(array_column($userList,'score_count'));
        $scoreAll = bcsub($scoreAll,$scoreCanGet,2);
        if($scoreAll < $scoreUse){
            $return = array('error_code' => true, 'msg' => L_("减少X1失败！请联系管理员协助解决。",array("X1" => cfg('score_name'))));
            return $return;
        }
        //计算积分使用总和占积分总和的百分比(保留四位小数)
        $percentage = bcdiv($scoreUse,$scoreAll,4);
        //获取每个用户应该扣除的积分 = 本人积分 * （使用积分 ÷ 积分总和）
        $useScoreAry = [];
        foreach ($userList as $value){
            //本地订单加上的积分暂时先减去，不然不好计算
            $value['score_count'] = $value['uid'] == $payUid ? bcsub(strval($value['score_count']),$scoreCanGet,2) : $value['score_count'];
            $useScoreAry[$value['uid']] = bcmul(strval($value['score_count']),$percentage,0);
        }
        $scoreUseAll = array_sum($useScoreAry);
        $score = bcsub($scoreUse,$scoreUseAll,2);//小数导致的差值
        if($score > 0){
            foreach ($userList as $user){
                $user['score_count'] = bcsub(strval($user['score_count']),$useScoreAry[$user['uid']],2);
                if($user['score_count'] >= $score){
                    //该用户的积分剩余足够支付差值，扣掉响应的积分，到此结束
                    $useScoreAry[$user['uid']] = bcadd($useScoreAry[$user['uid']],$score,2);
                    break;
                }else{
                    //该用户的剩余积分不够支付差值，扣完他的积分，循环到下一个幸运用户
                    $useScoreAry[$user['uid']] = bcadd($useScoreAry[$user['uid']],$user['score_count'],2);
                    $score = bcsub($score,$user['score_count'],2);
                }
            }
        }
        foreach ($useScoreAry as $uid=>$scoreUserUse){
            if($scoreUserUse <= 0){
                continue;
            }
            //挨个扣除积分
            $userScore = (new UserService())->userScore($uid, $scoreUserUse, L_("物业缴费 ，扣除X1 ，订单编号X2", array( "X1" => cfg('score_name'), "X2" => $orderNo)));
            if ($userScore['error_code']) {
                $return = array('error_code' => true, 'msg' => $userScore['msg']);
            }
        }
        return $return;
    }
}