<?php


namespace app\community\model\service;

use app\common\model\db\Admin;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseAdmin;
use app\community\model\db\HousePropertyDigit;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePrintCustomConfigure;
use app\community\model\db\HouseVillagePrintTemplateNumber;
use app\community\model\db\PlatOrder;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;
use app\community\model\db\HouseNewCharge;
use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewChargeTime;
use app\community\model\db\HouseNewOfflinePay;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderRefund;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageDetailRecord;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillagePrintCustom;
use app\community\model\db\HouseVillagePrintTemplate;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseNewSelectProjectLog;
use app\community\model\db\HouseNewSelectProjectRecord;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\community\model\db\User;
use app\community\model\service\HouseVillageCheckauthSetService;
use app\community\model\service\HouseVillageCheckauthApplyService;
use app\community\model\service\HouseVillageCheckauthDetailService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\HouseVillageUserVacancyService;

use think\Exception;
use function Qcloud\Cos\encodeKey;

class HouseNewCashierService
{
    public $pay_type = [1 => '扫码支付', 2 => '线下支付', 3 => '付款码支付', 4 => '线上支付',22=>'线下支付'];
    public $diy_type = [1 => '折扣', 2 => '赠送周期', 3 => '自定义文本', 4 => '无优惠'];
    public $pay_type_arr = ['wechat' => '微信', 'alipay' => '支付宝', 'unionpay' => '银联','hqpay_wx'=>'环球汇通微信支付','hqpay_al'=>'环球汇通支付宝支付'];
    public $pay_type_arr1 = [0=>'',1 => 'wechat', 2 => 'alipay', 3 => 'unionpay',4=>'hqpay_'];

    /**
     * 获取科目信息
     * @author lijie
     * @date_time 2021/09/07
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getNumberInfo($where=[],$field=true)
    {
        $db_house_new_charge_number = new HouseNewChargeNumber();
        $data = $db_house_new_charge_number->get_one($where,$field);
        return $data;
    }

    /**
     * 收费项目信息
     * @author lijie
     * @date_time 2021/09/07
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getProjectInfo($where=[],$field=true)
    {
        $db_house_new_charge_project = new HouseNewChargeProject();
        $data = $db_house_new_charge_project->getOne($where,$field);
        return $data;
    }
    /**
     * 根据分组获取未交费列表
     * @param array $where
     * @param array $whereOr
     * @param string $group
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/06/15
     */
    public function getSumByGroup($where = [], $group = '', $field = true,$page=1,$limit=10,$whereOr=[],$type='')
    {
        $check_level_info = array();
        if (isset($where['check_level_info'])) {
            $check_level_info = $where['check_level_info'];
            unset($where['check_level_info']);
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->getSumByGroup($where, $group, $field,$page,$limit,$whereOr);
        $total_money = 0;
        if($data){
            $data = $data->toArray();
        }
        if ($data) {
            $property_id = $data[0]['property_id'];
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$property_id]);
            foreach ($data as $k => &$v) {
                $whereTmp=array();
                $whereTmp[] = ['o.project_id','=',$v['project_id']];
                $whereTmp[] = ['o.is_paid','=',2];
                $whereTmp[] = ['o.is_discard','=',1];
                if(!empty($type) && $type == 'room'){
                    $whereTmp[] = ['o.room_id','=',$v['room_id']];
                } elseif(!empty($type) && $type == 'position'){
                    $whereTmp[] = ['o.position_id','=',$v['position_id']];
                }else{
                    if($v['position_id']){
                        $whereTmp[] = ['o.position_id','=',$v['position_id']];
                    } else{
                        $whereTmp[] = ['o.room_id','=',$v['room_id']];
                    }
                }
                $data[$k]['detail_order']=array();

                $detailOrder=$db_house_new_pay_order->getList($whereTmp,'o.order_id,o.pigcms_id');
                if($detailOrder && !$detailOrder->isEmpty()){
                    $orderTmps=$detailOrder->toArray();
                    $data[$k]['detail_order']=$orderTmps;
                }

                if(empty($digit_info)){
                    $v['total_money'] = formatNumber($v['total_money'],2,1);
                    $v['modify_money'] = formatNumber($v['modify_money'],2,1);
                    $v['late_payment_money'] = formatNumber($v['late_payment_money'],2,1);
                }else{
                    if($v['order_type'] == 'water' || $v['order_type'] == 'electric' || $v['order_type'] == 'gas'){
                        $v['total_money'] = formatNumber($v['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $v['modify_money'] = formatNumber($v['modify_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $v['late_payment_money'] = formatNumber($v['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']);
                    }else{
                        $data[$k]['total_money'] = formatNumber($v['total_money'],$digit_info['other_digit'],$digit_info['type']);
                        $v['modify_money'] = formatNumber($v['modify_money'],$digit_info['other_digit'],$digit_info['type']);
                        $v['late_payment_money'] = formatNumber($v['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
                    }
                }
                $where = [];
                $where[] = ['o.is_paid', '=', 2];
                $where[] = ['o.is_discard', '=', 1];
                $where[] = ['o.project_id', '=', $v['project_id']];
                if($type){
                    if($type == 'room'){
                        $where[] = ['o.room_id', '=', $v['room_id']];
                    }else{
                        $where[] = ['o.position_id', '=', $v['position_id']];
                    }
                }else{
                    if ($v['position_id'])
                        $where[] = ['o.position_id', '=', $v['position_id']];
                    else
                        $where[] = ['o.room_id', '=', $v['room_id']];
                }
                $order_list = $db_house_new_pay_order->getList($where, 'o.order_id,o.add_time,r.late_fee_reckon_day,r.late_fee_rate,r.late_fee_top_day,o.modify_money,o.late_payment_day,o.late_payment_money')->toArray();
                if(isset($v['check_status']) && ($v['check_status']==1)){
                    $data[$k]['pay_money']=0;
                    $v['modify_money']=0;
                    $v['late_payment_money']=0;
                }else{
                    $total_money += ($v['modify_money'] + $v['late_payment_money']);
                    $data[$k]['pay_money'] = $v['modify_money'] + $v['late_payment_money'];
                }
                if (count($order_list) > 1){
                    $data[$k]['show_action'] = 0;
                } else{
                    $data[$k]['show_action'] = 1;
                }
                if(empty($digit_info)){
                    $data[$k]['pay_money'] = formatNumber($data[$k]['pay_money'],2,1);
                }else{
                    $data[$k]['pay_money'] = formatNumber($data[$k]['pay_money'],$digit_info['other_digit'],$digit_info['type']);
                }
                $data[$k]['type_txt'] = empty($v['type']) ? '-' : (($v['type'] == 1) ? "一次性费用" : "周期性费用");

                $v['my_check_status']=0;  //我看到审核状态 1审核中 2审核 自己已审核
                $v['order_apply_info']='';
                $v['check_status_str']='';
                if(isset($v['check_status']) && $v['check_status']==1){
                    $v['check_status_str']='审核中';
                }
                //处理审核状态
                if(isset($v['check_status']) && $v['check_status']==1 && !empty($check_level_info)){
                    $v['my_check_status']=1;
                    $houseVillageCheckauthDetailService=new HouseVillageCheckauthDetailService();
                    if(!empty($check_level_info['wid'])){
                        $checkauthApplyWhere=array('order_id'=>$v['order_id'],'village_id'=>$v['village_id'],'wid'=>$check_level_info['wid'],'apply_id'=>$v['check_apply_id']);
                        $checkDetail=$houseVillageCheckauthDetailService->getOneData($checkauthApplyWhere);
                        if(!empty($checkDetail) && $checkDetail['status']==0){
                            $v['my_check_status']=2;
                            $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
                            $order_apply = $houseVillageCheckauthApplyService->getOneData(['id' => $checkDetail['apply_id'],'order_id'=>$v['order_id']]);
                            if($order_apply && !empty($order_apply['extra_data'])){
                                $order_apply_info=json_decode($order_apply['extra_data'],1);
                                if($order_apply_info){
                                    $order_apply_info['opt_time_str']= $order_apply_info['opt_time']>0?date('Y-m-d H:i:s' ,$order_apply_info['opt_time']):'';
                                }
                                $v['order_apply_info']=$order_apply_info;
                            }
                        }elseif(!empty($checkDetail) && $checkDetail['status']==1){
                            $v['my_check_status']=3;
                        }
                    }

                }elseif(isset($v['check_status']) && $v['check_status']==1){
                    $v['my_check_status']=1;
                }
            }
        }
        if(empty($digit_info)){
            $total_money = formatNumber($total_money,2,1);
        }else{
            $total_money = formatNumber($total_money,$digit_info['other_digit'],$digit_info['type']);
        }
        $res['total_money'] = $total_money;
        $res['order_list'] = $data;
        return $res;
    }

    /**
     * 根据分组获取未交费数量
     * @param array $where
     * @param string $group
     * @return mixed
     * @author lijie
     * @date_time 2021/06/24
     */
    public function getCountByGroup($where = [], $group = '')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $count = $db_house_new_pay_order->getCountByGroup($where, $group);
        return $count;
    }

    /**
     * 修改订单
     * @param array $where
     * @param array $data
     * @return bool
     * @author lijie
     * @date_time 2021/06/15
     */
    public function saveOrder($where = [], $data = [])
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $res = $db_house_new_pay_order->saveOne($where, $data);
        return $res;
    }

    /**
     * 添加订单
     * @param array $data
     * @return int|string
     * @author lijie
     * @date_time 2021/06/15
     */
    public function addOrder($data = [])
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $id = $db_house_new_pay_order->addOne($data);
        return $id;
    }

    /**
     * 物业参数配置
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getDigit($where=[],$field=true)
    {
        $db_house_property_digit = new HousePropertyDigit();
        return $db_house_property_digit->getOne($where,$field);
    }

    /**
     * 预缴账单30分钟内未支付作废
     * @author lijie
     * @date_time 2021/09/16
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function discardPrepaidOrder()
    {
        $where=array();
        $where[] = ['o.is_paid','=',2];
        $where[] = ['o.is_discard','=',1];
        $where[] = ['o.is_prepare','=',1];
        $field = 'o.order_id,o.add_time';
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_list = $db_house_new_pay_order->getList($where,$field);
        if($order_list){
            fdump_api(['where'=>$where],'000discardPrepaidOrder',1);
            foreach ($order_list as $v){
                if(time()-$v['add_time'] > 1800){
                    $db_house_new_pay_order->saveOne(['order_id'=>$v['order_id']],['is_discard'=>2,'discard_reason'=>'预缴账单30分钟内未支付','update_time'=>time()]);
                }
            }
        }
        return true;
    }

    /**
     * 每天凌晨0点更新滞纳金和滞纳天数
     * @author lijie
     * @date_time 2021/09/14
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function automaticLatePayment()
    {
        $where = [];
        $where[] = ['o.is_paid','=',2];
        $where[] = ['o.is_discard','=',1];
        $where[] = ['r.late_fee_rate','>',0];
        $field = 'o.order_id,o.add_time,r.late_fee_reckon_day,r.late_fee_top_day,r.late_fee_rate,o.modify_money';
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_list = $db_house_new_pay_order->getList($where,$field);
        if($order_list){
            foreach ($order_list as $k=>$v){
                if (isset($v['late_fee_reckon_day']) && isset($v['add_time']) && isset($v['late_fee_top_day']) && isset($v['late_fee_rate']) && isset($v['modify_money'])) {
                    $differ_day = ceil((time() - $v['add_time']) / 86400) - $v['late_fee_reckon_day'];
                    fdump_api([$v['order_id'],$differ_day],'automaticLatePayment',true);
                    if ($differ_day > $v['late_fee_top_day'] && $v['late_fee_top_day'] > 0){
                        $differ_day = $v['late_fee_top_day'];
                    }
                    if ($differ_day < 0) {
                        $differ_day = 0;
                    }
                    $late_payment_money = get_format_number($differ_day * $v['late_fee_rate']/100 * $v['modify_money']);
                    $late_payment_day = $differ_day;
                    $db_house_new_pay_order->saveOne(['order_id'=>$v['order_id']],['late_payment_money'=>$late_payment_money,'late_payment_day'=>$late_payment_day,'update_time'=>time()]);
                }
            }
        }
        return true;
    }

    /**
     * 订单详情
     * @param array $where
     * @param bool $field
     * @param int $isKeyVal
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/07/06
     */
    public function getOrder($where = [], $field = true, $isKeyVal = 0)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->getOne($where, $field);
        if(isset($data['property_id'])){
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$data['property_id']]);
        }else{
            $digit_info = [];
        }
        if(empty($digit_info)){
            $data['modify_money'] = formatNumber($data['modify_money'],2,1);
            $data['total_money'] = formatNumber($data['total_money'],2,1);
            $data['late_payment_money'] = formatNumber($data['late_payment_money'],2,1);
        }else{
            $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
            $data['modify_money'] = formatNumber($data['modify_money'],$digit_info['other_digit'],$digit_info['type']);
            $data['total_money'] = formatNumber($data['total_money'],$digit_info['other_digit'],$digit_info['type']);
            $data['late_payment_money'] = formatNumber($data['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
        }
        if (isset($data['add_time'])) {
            $data['add_time_txt'] = date('Y-m-d H:i:s', $data['add_time']);
        }
        if (isset($data['charge_valid_time'])) {
            $data['charge_valid_time_txt'] = date('Y-m-d H:i:s', $data['charge_valid_time']);
        }
        if (isset($data['service_start_time'])) {
            if ($data['service_start_time'] == 1)
                $data['service_start_time_txt'] = '无';
            else
                $data['service_start_time_txt'] = date('Y-m-d H:i:s', $data['service_start_time']);
        }
        if (isset($data['service_end_time'])) {
            if ($data['service_end_time'] == 1)
                $data['service_end_time_txt'] = '无';
            else
                $data['service_end_time_txt'] = date('Y-m-d H:i:s', $data['service_end_time']);
        }
        if ($isKeyVal) {
            $return_data[0]['title'] = '账单基本信息';
            $return_data[0]['list'][] = array(
                'title' => '收费标准名称',
                'val' => $data['charge_name']
            );
            $return_data[0]['list'][] = array(
                'title' => '项目名称',
                'val' => $data['project_name']
            );
            $return_data[0]['list'][] = array(
                'title' => '账单开始时间',
                'val' => $data['service_start_time_txt']
            );
            $return_data[0]['list'][] = array(
                'title' => '账单结束时间',
                'val' => $data['service_end_time_txt']
            );
            $return_data[0]['list'][] = array(
                'title' => '应收费用',
                'val' => $data['total_money']
            );
            $return_data[0]['list'][] = array(
                'title' => '实收费用',
                'val' => $data['modify_money']
            );
            if(isset($data['now_ammeter']) && isset($data['last_ammeter']) && $data['now_ammeter']-$data['last_ammeter']>0){
                $return_data[0]['list'][] = array(
                    'title' => '用量',
                    'val' => $data['now_ammeter']-$data['last_ammeter']
                );
            }
            $return_data[0]['list'][] = array(
                'title' => '收费标准生效时间',
                'val' => $data['charge_valid_time_txt']
            );
            $return_data[0]['list'][] = array(
                'title' => '账单生成时间',
                'val' => $data['add_time_txt']
            );
            if(isset($data['type']) && $data['type'] == 2){
                $data['service_month_num'] = $data['service_month_num']?$data['service_month_num']:1;
                if (!empty($data['service_month_num'])){
                    if ($data['bill_create_set']==1){
                        $data['service_month_num']=$data['service_month_num'].'天';
                    }elseif ($data['bill_create_set']==2){
                        $data['service_month_num']=$data['service_month_num'].'个月';
                    }elseif ($data['bill_create_set']==3){
                        $data['service_month_num']=$data['service_month_num'].'年';
                    }else{
                        $data['service_month_num']=$data['service_month_num'].'';
                    }
                }
                $return_data[0]['list'][] = array(
                    'title' => '收费周期',
                    'val' => $data['service_month_num']
                );
            }
            $return_data[1]['title'] = '滞纳金信息';
            $return_data[1]['list'][] = array(
                'title' => '滞纳天数',
                'val' => $data['late_payment_day']
            );
            $return_data[1]['list'][] = array(
                'title' => '滞纳金收取比例（每天）',
                'val' => $data['late_fee_rate'].'%'
            );
            $return_data[1]['list'][] = array(
                'title' => '滞纳金费用',
                'val' => $data['late_payment_money']
            );
            return $return_data;
        }
        return $data;
    }

    public function getOneOrder($where = [], $field = true,$order='order_id DESC')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->get_one($where, $field,$order);
        if($data){
            $data['service_end_time'] = date('Y-m-d H:i:s',$data['service_end_time']);
        }else{
            $data['service_end_time'] = 1;
        }
        return $data;
    }

    public function getInfo($where = [], $field = true,$order='order_id DESC')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->get_one($where, $field,$order);
        return $data;
    }

    /**
     * 添加总订单
     * @param array $data
     * @return int|string
     */
    public function addOrderSummary($data = [])
    {
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $id = $db_house_new_pay_order_summary->addOne($data);
        return $id;
    }

    /**
     * 更新总订单
     * @param array $where
     * @param array $data
     * @return bool
     * @author lijie
     * @date_time 2021/06/24
     */
    public function saveOrderSummary($where = [], $data = [])
    {
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $res = $db_house_new_pay_order_summary->saveOne($where, $data);
        return $res;
    }

    /**
     * 获取总订单详情
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * date_time 2021/06/24
     */
    public function getOrderSummary($where = [], $field = true)
    {
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $data = $db_house_new_pay_order_summary->getOne($where, $field);
        return $data;
    }

    /**
     * 获取订单列表
     * @author lijie
     * @date_time 2021/06/15
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $village_id
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderList($where = [], $field = true, $page = 0, $limit = 10, $order = 'order_id DESC',$village_id=0)
    {
        $check_level_info = array();
        if (isset($where['check_level_info'])) {
            $check_level_info = $where['check_level_info'];
            unset($where['check_level_info']);
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->getList($where, $field, $page, $limit, $order);

        if($data){
            $data = $data->toArray();
        }
        if ($data) {

            $digit_info=array();
            foreach ($data as $k => &$v) {
                if(empty($digit_info)){
                    $v['late_payment_money'] = formatNumber($v['late_payment_money'],2,1);
                    $v['modify_money'] = formatNumber($v['modify_money'],2,1);
                    $v['total_money'] = formatNumber($v['total_money'],2,1);
                }else{
                    $v['charge_type']=isset($v['charge_type'])?$v['charge_type']:(isset($v['order_type'])?$v['order_type']:'');
                    if($v['charge_type'] != 'water' && $v['charge_type'] != 'electric' && $v['charge_type'] != 'gas'){
                        $v['late_payment_money'] = formatNumber($v['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
                        $v['modify_money'] = formatNumber($v['modify_money'],$digit_info['other_digit'],$digit_info['type']);
                        $v['total_money'] = formatNumber($v['total_money'],$digit_info['other_digit'],$digit_info['type']);
                    }else{
                        $v['late_payment_money'] = formatNumber($v['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $v['modify_money'] = formatNumber($v['modify_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $v['total_money'] = formatNumber($v['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                    }
                }
                $late_payment_money = $v['late_payment_money'];
                $data[$k]['pay_money'] = $v['modify_money'] + $late_payment_money;
                if($v['charge_type'] != 'water' && $v['charge_type'] != 'electric' && $v['charge_type'] != 'gas' && $v['charge_type'] != 'park'){
                    if (isset($v['service_start_time'])) {
                        if ($v['service_start_time'] && $v['service_start_time'] > 1)
                            $data[$k]['service_start_time_txt'] = date('Y-m-d', $v['service_start_time']);
                        else
                            $data[$k]['service_start_time_txt'] = '--';
                    }
                    if (isset($v['service_end_time'])) {
                        if ($v['service_end_time'] && $v['service_end_time'] > 1)
                            $data[$k]['service_end_time_txt'] = date('Y-m-d', $v['service_end_time']);
                        else
                            $data[$k]['service_end_time_txt'] = '--';
                    }
                }else{
                    $data[$k]['service_end_time_txt'] = '--';
                    $data[$k]['service_start_time_txt'] = '--';
                }
                if (isset($v['add_time'])) {
                    $data[$k]['add_time_txt'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if($v['is_auto']){
                    $data[$k]['add_time_txt'] = $data[$k]['service_start_time_txt'].'至'.$data[$k]['service_end_time_txt'];
                }
                if (isset($v['last_ammeter'])) {
                    if (!$v['last_ammeter'] && $v['charge_type'] != 'water' && $v['charge_type'] != 'electric' && $v['charge_type'] != 'gas')
                        $data[$k]['last_ammeter'] = '--';
                }
                if (isset($v['now_ammeter'])) {
                    if (!$v['now_ammeter'] && $v['charge_type'] != 'water' && $v['charge_type'] != 'electric' && $v['charge_type'] != 'gas')
                        $data[$k]['now_ammeter'] = '--';
                }

                if(in_array($v['charge_type'],['water','gas','electric'])){
                    $data[$k]['not_house_rate'] = '-';
                    $data[$k]['fees_type'] = '-';
                    $data[$k]['bill_create_set'] = '-';
                    $data[$k]['bill_arrears_set'] = '-';
                    $data[$k]['bill_type'] = '-';
                }else{
                    if(isset($v['not_house_rate'])){
                        $data[$k]['not_house_rate'] = empty($v['not_house_rate']) ? '-' : ($v['not_house_rate'].'%');
                    }
                    if(isset($v['fees_type'])){
                        $data[$k]['fees_type'] = empty($v['fees_type']) ? '-' : (($v['fees_type'] == 1) ? '固定费用' : '单价计量单位');
                    }
                    if(isset($v['bill_create_set'])){
                        $data[$k]['bill_create_set'] = empty($v['bill_create_set']) ? '-' : (($v['bill_create_set'] == 1) ? '按日生成' : (($v['bill_create_set'] == 2) ? '按月生成' : '按年生成'));
                    }
                    if(isset($v['bill_arrears_set'])){
                        $data[$k]['bill_arrears_set'] = empty($v['bill_arrears_set']) ? '-' : (($v['bill_arrears_set'] == 1) ? '预生成' : '后生成');
                    }
                    if(isset($v['bill_type'])){
                        $data[$k]['bill_type'] = empty($v['bill_type']) ? '-' : (($v['bill_type'] == 1) ? '手动' : '自动');
                    }
                }

                //防止其他调用接口没有传此字段
                if($field === true || (strpos($field,'charge_valid_type') && strpos($field,'charge_valid_time'))){
                    if($v['charge_valid_type'] == 3) {
                        $data[$k]['charge_valid_time_txt'] = date('Y', $v['charge_valid_time']);
                    }elseif ($v['charge_valid_type'] == 2) {
                        $data[$k]['charge_valid_time_txt'] = date('Y-m', $v['charge_valid_time']);
                    }elseif ($v['charge_valid_type'] == 1) {
                        $data[$k]['charge_valid_time_txt'] = date('Y-m-d', $v['charge_valid_time']);
                    }
                }

                $v['my_check_status']=0;  //我看到审核状态 1审核中 2审核 自己已审核
                $v['order_apply_info']='';
                $v['check_status_str']='';
                if(isset($v['check_status']) && $v['check_status']==1){
                    $v['check_status_str']='审核中';
                }
                //处理审核状态
                if(isset($v['check_status']) && $v['check_status']==1 && !empty($check_level_info)){
                    $v['my_check_status']=1;
                    $houseVillageCheckauthDetailService=new HouseVillageCheckauthDetailService();
                    if(!empty($check_level_info['wid'])){
                        $checkauthApplyWhere=array('order_id'=>$v['order_id'],'village_id'=>$v['village_id'],'wid'=>$check_level_info['wid'],'apply_id'=>$v['check_apply_id']);
                        $checkDetail=$houseVillageCheckauthDetailService->getOneData($checkauthApplyWhere);
                        if(!empty($checkDetail) && $checkDetail['status']==0){
                            $v['my_check_status']=2;
                            $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
                            $order_apply = $houseVillageCheckauthApplyService->getOneData(['id' => $checkDetail['apply_id'],'order_id'=>$v['order_id']]);
                            if($order_apply && !empty($order_apply['extra_data'])){
                                $order_apply_info=json_decode($order_apply['extra_data'],1);
                                if($order_apply_info){
                                    $order_apply_info['opt_time_str']= $order_apply_info['opt_time']>0?date('Y-m-d H:i:s' ,$order_apply_info['opt_time']):'';
                                }
                                $v['order_apply_info']=$order_apply_info;
                            }
                        }elseif(!empty($checkDetail) && $checkDetail['status']==1){
                            $v['my_check_status']=3;
                        }
                    }

                }elseif(isset($v['check_status']) && $v['check_status']==1){
                    $v['my_check_status']=1;
                }

            }
        }
        return $data;
    }

    /**
     * 应收明细
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $village_id
     * @return mixed
     */
    public function getNewPayOrders($where = [], $field = true, $page = 0, $limit = 10, $order = 'order_id DESC')
    {
        $check_level_info = array();
        if (isset($where['check_level_info'])) {
            $check_level_info = $where['check_level_info'];
            unset($where['check_level_info']);
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->getPayOrders($where, $field, $page, $limit, $order);

        if($data){
            $data = $data->toArray();
        }
        if ($data) {
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$data[0]['property_id']]);
            foreach ($data as $k => &$v) {
                if(empty($digit_info)){
                    $v['total_money'] = formatNumber($v['total_money'],2,1);
                    $v['charge_type']=isset($v['charge_type'])?$v['charge_type']:(isset($v['order_type'])?$v['order_type']:'');
                }else{
                    $v['charge_type']=isset($v['charge_type'])?$v['charge_type']:(isset($v['order_type'])?$v['order_type']:'');
                    if($v['charge_type'] != 'water' && $v['charge_type'] != 'electric' && $v['charge_type'] != 'gas'){
                        $v['total_money'] = formatNumber($v['total_money'],$digit_info['other_digit'],$digit_info['type']);
                    }else{
                        $v['total_money'] = formatNumber($v['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                    }
                }
                if($v['room_id']){
                    $db_house_village_user_vacancy = new HouseVillageUserVacancy();
                    $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
                    if (!empty($room)) {
                        $room = $room->toArray();
                        if (!empty($room)) {
                            $room1 = $room[0];
                            $house_village_service=new HouseVillageService();
                            $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$data[0]['property_id']['village_id']);

                           //  $number = $room1['single_name'] . '栋' . $room1['floor_name'] . '单元' . $room1['layer_name'] . '层' . $room1['room'];
                            $v['number'] = $number;
                        }else{
                            $v['number'] = '';
                        }
                    }else{
                        $v['number'] = '';
                    }
                }else{
                    $db_house_village_parking_position = new HouseVillageParkingPosition();
                    $position_num = $db_house_village_parking_position->getLists(['position_id' => $v['position_id']], 'pp.position_num,pg.garage_num', 0);
                    if (!empty($position_num)) {
                        $position_num = $position_num->toArray();
                        if (!empty($position_num)) {
                            $position_num1 = $position_num[0];
                            if (empty($position_num1['garage_num'])) {
                                $position_num1['garage_num'] = '临时车库';
                            }
                            $number = $position_num1['garage_num'] . '-' . $position_num1['position_num'];
                            $v['number'] = $number;
                        }else{
                            $v['number'] = '';
                        }
                    }else{
                        $v['number'] = '';
                    }
                }
                if($v['charge_type'] != 'water' && $v['charge_type'] != 'electric' && $v['charge_type'] != 'gas' && $v['charge_type'] != 'park'){
                    if (isset($v['service_start_time'])) {
                        if ($v['service_start_time'] && $v['service_start_time'] > 1)
                            $data[$k]['service_start_time_txt'] = date('Y-m-d', $v['service_start_time']);
                        else
                            $data[$k]['service_start_time_txt'] = '--';
                    }
                    if (isset($v['service_end_time'])) {
                        if ($v['service_end_time'] && $v['service_end_time'] > 1)
                            $data[$k]['service_end_time_txt'] = date('Y-m-d', $v['service_end_time']);
                        else
                            $data[$k]['service_end_time_txt'] = '--';
                    }
                }else{
                    $data[$k]['service_end_time_txt'] = '--';
                    $data[$k]['service_start_time_txt'] = '--';
                }
                if (isset($v['last_ammeter'])) {
                    if (!$v['last_ammeter'] && $v['charge_type'] != 'water' && $v['charge_type'] != 'electric' && $v['charge_type'] != 'gas')
                        $data[$k]['last_ammeter'] = '--';
                }
                if (isset($v['now_ammeter'])) {
                    if (!$v['now_ammeter'] && $v['charge_type'] != 'water' && $v['charge_type'] != 'electric' && $v['charge_type'] != 'gas')
                        $data[$k]['now_ammeter'] = '--';
                }
                $data[$k]['add_time_txt'] = '--';
                if(isset($v['add_time']) && !empty($v['add_time'])){
                    $data[$k]['add_time_txt'] = date('Y-m-d', $v['add_time']);
                }
                $v['my_check_status']=0;  //我看到审核状态 1审核中 2审核 自己已审核
                $v['order_apply_info']='';
                $v['check_status_str']='';
                if(isset($v['check_status']) && $v['check_status']==1){
                    $v['check_status_str']='审核中';
                }
                //处理审核状态
                if(isset($v['check_status']) && $v['check_status']==1 && !empty($check_level_info)){
                    $v['my_check_status']=1;
                    $houseVillageCheckauthDetailService=new HouseVillageCheckauthDetailService();
                    if(!empty($check_level_info['wid'])){
                        $checkauthApplyWhere=array('order_id'=>$v['order_id'],'village_id'=>$v['village_id'],'wid'=>$check_level_info['wid'],'apply_id'=>$v['check_apply_id']);
                        $checkDetail=$houseVillageCheckauthDetailService->getOneData($checkauthApplyWhere);
                        if(!empty($checkDetail) && $checkDetail['status']==0){
                            $v['my_check_status']=2;
                            $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
                            $order_apply = $houseVillageCheckauthApplyService->getOneData(['id' => $checkDetail['apply_id'],'order_id'=>$v['order_id']]);
                            if($order_apply && !empty($order_apply['extra_data'])){
                                $order_apply_info=json_decode($order_apply['extra_data'],1);
                                if($order_apply_info){
                                    $order_apply_info['opt_time_str']= $order_apply_info['opt_time']>0?date('Y-m-d H:i:s' ,$order_apply_info['opt_time']):'';
                                }
                                $v['order_apply_info']=$order_apply_info;
                            }
                        }elseif(!empty($checkDetail) && $checkDetail['status']==1){
                            $v['my_check_status']=3;
                        }
                    }

                }elseif(isset($v['check_status']) && $v['check_status']==1){
                    $v['my_check_status']=1;
                }
            }
        }
        return $data;
    }

    public function getNewPayOrdersCount($where=[])
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $count = $db_house_new_pay_order->getPayOrdersCount($where);
        return $count;
    }

    public function getOrderLists($where=[],$field=true)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->getList($where, $field);
        return $data;
    }


    /**
     * 获取应收订单详情
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/06/15
     */
    public function getReceivableOrderInfo($where = [], $field = true, $page = 1, $limit = 1, $order = 'order_id DESC')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data1 = $db_house_new_pay_order->getList($where, $field, $page, $limit, $order);
      //  print_r($data1);exit;
        //  print_r($data1->toArray());exit;
        if ($data1) {
            $late_payment_money = 0.00;
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$data1[0]['property_id']]);
            $data = [];
            foreach ($data1 as $k => $v) {
                $data[$k] = $v;
                $data[$k]['pay_money'] = $v['total_money'];
                if(empty($digit_info)){
                    $data[$k]['pay_money']= formatNumber($data[$k]['total_money'],2,1);
                }else{
                    if($v['order_type'] == 'water' || $v['order_type'] == 'electric' || $v['order_type'] == 'gas'){
                        $data[$k]['pay_money']= formatNumber($data[$k]['pay_money'],$digit_info['meter_digit'],$digit_info['type']);
                    }else{
                        $data[$k]['pay_money']= formatNumber($data[$k]['pay_money'],$digit_info['other_digit'],$digit_info['type']);
                    }
                }
                if ((!isset($v['service_start_time'])||$v['service_start_time']<=1)&&(!isset($v['service_end_time'])||$v['service_end_time']<=1)){
                    $service_time=0;
                }else{
                    $service_time=1;
                }
                if (isset($v['service_start_time'])) {
                    if ($v['service_start_time'])
                        $data[$k]['service_start_time_txt'] = date('Y-m-d H:i:s', $v['service_start_time']);
                    else
                        $data[$k]['service_start_time_txt'] = '--';
                }
                if (isset($v['service_end_time'])) {
                    if ($v['service_end_time'])
                        $data[$k]['service_end_time_txt'] = date('Y-m-d H:i:s', $v['service_end_time']);
                    else
                        $data[$k]['service_end_time_txt'] = '--';
                }
                if (isset($v['add_time'])) {
                    $data[$k]['add_time_txt'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if ((!isset($v['last_ammeter'])||empty($v['last_ammeter']))&&(!isset($v['now_ammeter'])||empty($v['now_ammeter']))){
                    $ammeter=0;
                }else{
                    $ammeter=1;
                }
                if (isset($v['last_ammeter'])) {
                    if (!$v['last_ammeter'])
                        $data[$k]['last_ammeter'] = '--';
                }
                if (isset($v['now_ammeter'])) {
                    if (!$v['now_ammeter'])
                        $data[$k]['now_ammeter'] = '--';
                }
            }
        }

        $list = [];
        $list[0]['title'] = '收费标准名称';
        $list[0]['val'] = $data[0]['charge_name'];
        $list[1]['title'] = '收费项目名称';
        $list[1]['val'] = $data[0]['name'];
        $list[2]['title'] = '所属收费科目';
        $list[2]['val'] = $data[0]['charge_number_name'];
        $list[3]['title'] = '应收费用';
        $list[3]['val'] = '￥' . $data[0]['pay_money'];
        if (!empty($service_time)){
            $list[4]['title'] = '计费开始时间';
            $list[4]['val'] = $data[0]['service_start_time_txt'];
            $list[5]['title'] = '计费结束时间';
            $list[5]['val'] = $data[0]['service_end_time_txt'];
        }
        if (!empty($ammeter)){
            $list[6]['title'] = '上次度数';
            $list[6]['val'] = $data[0]['last_ammeter'];
            $list[7]['title'] = '本次度数';
            $list[7]['val'] = $data[0]['now_ammeter'];
        }
        $res = [];
        $res['list'] = $list;
        $res['total_money'] = $data[0]['pay_money'];

        return $res;
    }

    /**
     * 计算账单合计费用
     * @param array $where
     * @param bool $field
     * @return float|int|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/06/28
     */
    public function getPayMoney($where = [], $field = true)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->getList($where, $field);
        $total_money = 0.00;
        if ($data) {
            foreach ($data as $k => $v) {
                $total_money += ($v['modify_money'] + $v['late_payment_money']);
            }
        }
        return get_format_number($total_money);
    }

    /**
     * 获取订单详情
     * @param array $where
     * @param bool $field
     * @param int $isKeyVal
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderInfo($where = [], $field = true, $isKeyVal = 0)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->getOne($where, $field);
        if (empty($data['project_name'])&&$data['car_type']=='stored_type'){
            $data['project_name']='储值车充值（'.$data['car_number'].'）';
        }
        if (empty($data['project_name'])&&$data['car_type']=='temporary_type'){
            $data['project_name']='临时车缴费（'.$data['car_number'].'）';
        }
        if(isset($data['property_id'])){
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$data['property_id']]);
        }else{
            $digit_info = [];
        }
        if(empty($digit_info)){
            $late_payment_money = formatNumber($data['late_payment_money'],2,1);
        }else{
            if($data['order_type'] == 'water' || $data['order_type'] == 'electric' || $data['order_type'] == 'gas'){
                $digit_info['meter_digit'] = $digit_info['meter_digit']>2 || empty($digit_info['meter_digit'])?2:$digit_info['meter_digit'];
                $late_payment_money = formatNumber($data['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']);
            }else {
                $digit_info['other_digit'] = $digit_info['other_digit'] > 2 || empty($digit_info['other_digit']) ? 2 : $digit_info['other_digit'];
                $late_payment_money = formatNumber($data['late_payment_money'], $digit_info['other_digit'], $digit_info['type']);
            }
         }
        $late_payment_day = $data['late_payment_day'];
        $data['late_payment_money'] = $late_payment_money;
        $data['late_payment_day'] = $late_payment_day;
        if($data['charge_valid_type'] == 3)
            $data['charge_valid_time_txt'] = date('Y', $data['charge_valid_time']);
        elseif ($data['charge_valid_type'] == 2)
            $data['charge_valid_time_txt'] = date('Y-m', $data['charge_valid_time']);
        elseif ($data['charge_valid_type'] == 1)
            $data['charge_valid_time_txt'] = date('Y-m-d', $data['charge_valid_time']);
        $data['add_time_txt'] = date('Y-m-d H:i:s', $data['add_time']);
        if ($data['is_prepare'] == 1) {
            if ($data['bill_create_set'] == 1)
                $data['service_month_num'] = $data['service_month_num'] . '日';
            elseif ($data['bill_create_set'] == 2)
                $data['service_month_num'] = $data['service_month_num'] . '月';
            elseif ($data['bill_create_set'] == 3)
                $data['service_month_num'] = $data['service_month_num'] . '年';
            else
                $data['service_month_num'] = '--';
        }
        if ($data['service_end_time']) {
            if ($data['service_end_time'] >= time())
                $data['is_in_service'] = 1;
            else
                $data['is_in_service'] = 2;
        }
        if ($data['service_end_time']) {
            if ($data['service_end_time'] == 1)
                $data['service_end_time'] = '无';
            else
                $data['service_end_time'] = date('Y-m-d H:i:s', $data['service_end_time']);
        }
        if ($data['service_start_time']) {
            if ($data['service_start_time'] == 1)
                $data['service_start_time'] = '无';
            else
                $data['service_start_time'] = date('Y-m-d H:i:s', $data['service_start_time']);
        }
        if ($data['pay_time'])
            $data['pay_time_txt'] = date('Y-m-d H:i:s', $data['pay_time']);
        if(empty($digit_info)){
            $data['modify_money'] = formatNumber($data['modify_money'],2,1);
            $data['total_money'] = formatNumber($data['total_money'],2,1);
            $data['refund_money'] = formatNumber($data['refund_money'],2,1);
            $data['pay_money'] = formatNumber($data['pay_money'],2,1);

            $data['pay_amount_points']=formatNumber($data['pay_amount_points']/100,2,1);
            $data['system_balance']=formatNumber($data['system_balance'],2,1);
            $data['score_deducte']=formatNumber($data['score_deducte'],2,1);
            $data['score_used_count']=formatNumber($data['score_used_count'],2,1);

        }else{
          //  print_r($data['order_type']);exit;
            if($data['order_type'] == 'water' || $data['order_type'] == 'electric' || $data['order_type'] == 'gas'){
                $digit_info['meter_digit'] = $digit_info['meter_digit']>2 || empty($digit_info['meter_digit'])?2:$digit_info['meter_digit'];
                $data['modify_money'] = formatNumber($data['modify_money'], $digit_info['meter_digit'], $digit_info['type']);
                $data['total_money'] = formatNumber($data['total_money'], $digit_info['meter_digit'], $digit_info['type']);
                $data['refund_money'] = formatNumber($data['refund_money'], $digit_info['meter_digit'], $digit_info['type']);
                $data['pay_money'] = formatNumber($data['pay_money'], $digit_info['meter_digit'], $digit_info['type']);

                $data['pay_amount_points']=formatNumber($data['pay_amount_points']/100,$digit_info['meter_digit'], $digit_info['type']).'元';
                $data['system_balance']=formatNumber($data['system_balance'],$digit_info['meter_digit'], $digit_info['type']);
                $data['score_deducte']=formatNumber($data['score_deducte'],$digit_info['meter_digit'], $digit_info['type']);
                $data['score_used_count']=formatNumber($data['score_used_count'],$digit_info['meter_digit'], $digit_info['type']);

            }else {
                $digit_info['other_digit'] = $digit_info['other_digit'] > 2 || empty($digit_info['other_digit']) ? 2 : $digit_info['other_digit'];
                $data['modify_money'] = formatNumber($data['modify_money'], $digit_info['other_digit'], $digit_info['type']);
                $data['total_money'] = formatNumber($data['total_money'], $digit_info['other_digit'], $digit_info['type']);
                $data['refund_money'] = formatNumber($data['refund_money'], $digit_info['other_digit'], $digit_info['type']);
                $data['pay_money'] = formatNumber($data['pay_money'], $digit_info['other_digit'], $digit_info['type']);

                $data['pay_amount_points']=formatNumber($data['pay_amount_points']/100,$digit_info['other_digit'], $digit_info['type']);
                $data['system_balance']=formatNumber($data['system_balance'],$digit_info['other_digit'], $digit_info['type']);
                $data['score_deducte']=formatNumber($data['score_deducte'],$digit_info['other_digit'], $digit_info['type']);
                $data['score_used_count']=formatNumber($data['score_used_count'],$digit_info['other_digit'], $digit_info['type']);

            }
        }
        $data['all_fee'] = $data['modify_money'] + $data['late_payment_money'];
        if($data['is_prepare'] == 1){
            $data['prepare_money'] = $data['total_money'];
        }else{
            $data['prepare_money'] = 0;
        }
        if ($isKeyVal) {
            $return_data[0]['title'] = '账单基本信息';
            $return_data[0]['list'][] = array(
                'title' => '订单编号',
                'val' => $data['order_id']
            );
            $return_data[0]['list'][] = array(
                'title' => '支付单号',
                'val' => $data['order_no']
            );
            $return_data[0]['list'][] = array(
                'title' => '收费项目名称',
                'val' => $data['project_name']
            );
            $return_data[0]['list'][] = array(
                'title' => '应收费用',
                'val' => $data['total_money']
            );
            $return_data[0]['list'][] = array(
                'title' => '修改后费用',
                'val' => $data['modify_money']
            );
            $return_data[0]['list'][] = array(
                'title' => '修改原因',
                'val' => $data['modify_reason']
            );
            $return_data[0]['list'][] = array(
                'title' => '实际缴费金额',
                'val' => $data['pay_money']
            );
            $return_data[0]['list'][] = array(
                'title' => '线上支付金额',
                'val' => $data['pay_amount_points']
            );
            $return_data[0]['list'][] = array(
                'title' => '余额支付金额',
                'val' => $data['system_balance']
            );
            $return_data[0]['list'][] = array(
                'title' => '积分抵扣金额',
                'val' => $data['score_deducte']
            );
            $return_data[0]['list'][] = array(
                'title' => '积分使用数量',
                'val' => $data['score_used_count']
            );
            if(isset($data['now_ammeter']) && isset($data['last_ammeter']) && $data['now_ammeter']-$data['last_ammeter']>0){
                $return_data[0]['list'][] = array(
                    'title' => '用量',
                    'val' => $data['now_ammeter']-$data['last_ammeter']
                );
            }
            $return_data[0]['list'][] = array(
                'title' => '支付时间',
                'val' => $data['pay_time_txt']
            );
            $return_data[0]['list'][] = array(
                'title' => '计费开始时间',
                'val' => $data['service_start_time']
            );
            $return_data[0]['list'][] = array(
                'title' => '计费结束时间',
                'val' => $data['service_end_time']
            );
            $return_data[0]['list'][] = array(
                'title' => '单价',
                'val' => $data['unit_price']
            );
            if($data['type'] == 2 && $data['is_prepare'] != 1){
                if ($data['bill_create_set'] == 1){
                    $cycle = '收费周期（日）';
                }elseif($data['bill_create_set'] == 2){
                    $cycle = '收费周期（月）';
                }else{
                    $cycle = '收费周期（年）';
                }
                $return_data[0]['list'][] = array(
                    'title' => $cycle,
                    'val' => $data['service_month_num']+$data['service_give_month_num']
                );
            }
            $return_data[1]['title'] = '滞纳金信息';
            $return_data[1]['list'][] = array(
                'title' => '滞纳天数',
                'val' => $data['late_payment_day']
            );
            $return_data[1]['list'][] = array(
                'title' => '滞纳金收取比例（每天）',
                'val' => $data['late_fee_rate'].'%'
            );
            if ($data['is_prepare'] == 1) {
                $return_data[2]['title'] = '预缴信息';
                $return_data[2]['list'][] = array(
                    'title' => '预缴周期',
                    'val' => $data['service_month_num']
                );
                $return_data[2]['list'][] = array(
                    'title' => '预缴优惠',
                    'val' => $data['diy_content']
                );
                $return_data[2]['list'][] = array(
                    'title' => '预缴费用',
                    'val' => $data['pay_money']
                );
            }
            if ($data['is_refund'] == 2) {
                $return_data[3]['title'] = '退款信息';
                $return_data[3]['list'][] = array(
                    'title' => '退款总金额',
                    'val' => $data['refund_money']
                );
            }
            return array_values($return_data);
        }
        $data['late_fee_rate'] = $data['late_fee_rate'].'%';
        return $data;
    }

    /**
     * 获取订单详情
     * @param array $where
     * @param bool $field
     * @param int $isKeyVal
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderIn($where = [], $field = true, $isKeyVal = 0)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $data = $db_house_new_pay_order->getOne($where, $field);
        if(isset($data['property_id'])){
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$data['property_id']]);
        }else{
            $digit_info = [];
        }
        if(empty($digit_info)){
            $late_payment_money = formatNumber($data['late_payment_money'],2,1);
        }else{
            if($data['order_type'] == 'water' || $data['order_type'] == 'electric' || $data['order_type'] == 'gas'){
                $late_payment_money = formatNumber($data['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']);
            }else{
                $late_payment_money = formatNumber($data['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
            }
        }
        $late_payment_day = $data['late_payment_day'];
        $data['late_payment_money'] = $late_payment_money;
        $data['late_payment_day'] = $late_payment_day;
        if($data['charge_valid_type'] == 3) {
            $data['charge_valid_time_txt'] = date('Y', $data['charge_valid_time']);
        }elseif ($data['charge_valid_type'] == 2) {
            $data['charge_valid_time_txt'] = date('Y-m', $data['charge_valid_time']);
        }elseif ($data['charge_valid_type'] == 1) {
            $data['charge_valid_time_txt'] = date('Y-m-d', $data['charge_valid_time']);
        }

        $data['add_time_txt'] = date('Y-m-d H:i:s', $data['add_time']);
        if ($data['type'] == 2) {
            if(empty($data['service_month_num'])){
                $data['service_month_num'] = 1;
            }
            if ($data['bill_create_set'] == 1)
                $data['service_month_num'] = $data['service_month_num'] . '天';
            elseif ($data['bill_create_set'] == 2)
                $data['service_month_num'] = $data['service_month_num'] . '个月';
            elseif ($data['bill_create_set'] == 3)
                $data['service_month_num'] = $data['service_month_num'] . '年';
            else
                $data['service_month_num'] = '--';
        }
        if ($data['service_end_time']) {
            if ($data['service_end_time'] >= time())
                $data['is_in_service'] = 1;
            else
                $data['is_in_service'] = 2;
        }
        if ($data['service_end_time']) {
            if ($data['service_end_time'] == 1)
                $data['service_end_time'] = '--';
            else
                $data['service_end_time'] = date('Y-m-d', $data['service_end_time']);
        }
        if ($data['service_start_time']) {
            if ($data['service_start_time'] == 1)
                $data['service_start_time'] = '--';
            else
                $data['service_start_time'] = date('Y-m-d', $data['service_start_time']);
        }
        if ($data['pay_time'])
            $data['pay_time_txt'] = date('Y-m-d H:i:s', $data['pay_time']);
        if(empty($digit_info)){
            $data['modify_money'] = formatNumber($data['modify_money'],2,1);
            $data['total_money'] = formatNumber($data['total_money'],2,1);
            $data['refund_money'] = formatNumber($data['refund_money'],2,1);
            $data['pay_money'] = formatNumber($data['pay_money'],2,1);
        }else{
            if($data['order_type'] == 'water' || $data['order_type'] == 'electric' || $data['order_type'] == 'gas'){
                $data['modify_money'] = formatNumber($data['modify_money'],$digit_info['meter_digit'],$digit_info['type']);
                $data['total_money'] = formatNumber($data['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                $data['refund_money'] = formatNumber($data['refund_money'],$digit_info['meter_digit'],$digit_info['type']);
                $data['pay_money'] = formatNumber($data['pay_money'],$digit_info['meter_digit'],$digit_info['type']);
                $data['not_house_rate'] = '-';
                $data['fees_type'] = '-';
                $data['bill_create_set'] = '-';
                $data['bill_arrears_set'] = '-';
                $data['bill_type'] = '-';
            }else{
                $data['modify_money'] = formatNumber($data['modify_money'],$digit_info['other_digit'],$digit_info['type']);
                $data['total_money'] = formatNumber($data['total_money'],$digit_info['other_digit'],$digit_info['type']);
                $data['refund_money'] = formatNumber($data['refund_money'],$digit_info['other_digit'],$digit_info['type']);
                $data['pay_money'] = formatNumber($data['pay_money'],$digit_info['other_digit'],$digit_info['type']);

                if(isset($data['not_house_rate'])){
                    $data['not_house_rate_txt'] = empty($data['not_house_rate']) ? '-' : ($data['not_house_rate'].'%');
                }
                if(isset($data['fees_type'])){
                    $data['fees_type_txt'] = empty($data['fees_type']) ? '-' : (($data['fees_type'] == 1) ? '固定费用' : '单价计量单位');
                }
                if(isset($data['bill_create_set'])){
                    $data['bill_create_set_txt'] = empty($data['bill_create_set']) ? '-' : (($data['bill_create_set'] == 1) ? '按日生成' : (($data['bill_create_set'] == 2) ? '按月生成' : '按年生成'));
                }
                if(isset($data['bill_arrears_set'])){
                    $data['bill_arrears_set_txt'] = empty($data['bill_arrears_set']) ? '-' : (($data['bill_arrears_set'] == 1) ? '预生成' : '后生成');
                }
                if(isset($data['bill_type'])){
                    $data['bill_type_txt'] = empty($data['bill_type']) ? '-' : (($data['bill_type'] == 1) ? '手动' : '自动');
                }
            }

        }
        $data['all_fee'] = $data['modify_money'] + $data['late_payment_money'];
        if($data['is_prepare'] == 1){
            $data['prepare_money'] = $data['total_money'];
        }else{
            $data['prepare_money'] = 0;
        }
        if ($isKeyVal) {
            $return_data[0]['title'] = '账单基本信息';
            $return_data[0]['list'][] = array(
                'title' => '订单编号',
                'val' => $data['order_id']
            );
            $return_data[0]['list'][] = array(
                'title' => '支付单号',
                'val' => $data['order_no']
            );
            $return_data[0]['list'][] = array(
                'title' => '收费项目名称',
                'val' => $data['project_name']
            );
            $return_data[0]['list'][] = array(
                'title' => '应收费用',
                'val' => $data['total_money']
            );
            $return_data[0]['list'][] = array(
                'title' => '修改后费用',
                'val' => $data['modify_money']
            );
            $return_data[0]['list'][] = array(
                'title' => '修改原因',
                'val' => $data['modify_reason']
            );
            $return_data[0]['list'][] = array(
                'title' => '实际缴费金额',
                'val' => $data['pay_money']
            );
            if(isset($data['now_ammeter']) && isset($data['last_ammeter']) && $data['now_ammeter']-$data['last_ammeter']>0){
                $return_data[0]['list'][] = array(
                    'title' => '用量',
                    'val' => $data['now_ammeter']-$data['last_ammeter']
                );
            }
            $return_data[0]['list'][] = array(
                'title' => '支付时间',
                'val' => $data['pay_time_txt']
            );
            $return_data[0]['list'][] = array(
                'title' => '计费开始时间',
                'val' => $data['service_start_time']
            );
            $return_data[0]['list'][] = array(
                'title' => '计费结束时间',
                'val' => $data['service_end_time']
            );
            $return_data[0]['list'][] = array(
                'title' => '单价',
                'val' => $data['unit_price']
            );
            $return_data[1]['title'] = '滞纳金信息';
            $return_data[1]['list'][] = array(
                'title' => '滞纳天数',
                'val' => $data['late_payment_day']
            );
            $return_data[1]['list'][] = array(
                'title' => '滞纳金收取比例（每天）',
                'val' => $data['late_fee_rate'].'%'
            );
            if ($data['is_prepare'] == 1) {
                $return_data[2]['title'] = '预缴信息';
                $return_data[2]['list'][] = array(
                    'title' => '预缴周期',
                    'val' => $data['service_month_num']
                );
                $return_data[2]['list'][] = array(
                    'title' => '预缴优惠',
                    'val' => $data['diy_content']
                );
                $return_data[2]['list'][] = array(
                    'title' => '预缴费用',
                    'val' => $data['pay_money']
                );
            }
            if ($data['is_refund'] == 2) {
                $return_data[3]['title'] = '退款信息';
                $return_data[3]['list'][] = array(
                    'title' => '退款总金额',
                    'val' => $data['refund_money']
                );
            }
            return array_values($return_data);
        }
        $data['late_fee_rate'] = $data['late_fee_rate'].'%';
        return $data;
    }

    public function getOrderDetail($where = [], $field = true)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_new_charge_project = new HouseNewChargeProject();
        $data = $db_house_new_pay_order->getOne($where, $field);
        if(isset($data['property_id'])){
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$data['property_id']]);
        }else{
            $digit_info = [];
        }
        if(empty($digit_info)){
            $data['late_payment_money'] = formatNumber($data['late_payment_money'],2,1);
            $data['modify_money'] = formatNumber($data['modify_money'],2,1);
            $data['total_money'] = formatNumber($data['total_money'],2,1);
        }else{
            if($data['order_type'] == 'water' || $data['order_type'] == 'electric' || $data['order_type'] == 'gas'){
                $data['late_payment_money'] = formatNumber($data['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']);
                $data['modify_money'] = formatNumber($data['modify_money'],$digit_info['meter_digit'],$digit_info['type']);
                $data['total_money'] = formatNumber($data['total_money'],$digit_info['meter_digit'],$digit_info['type']);
            }else{
                $data['late_payment_money'] = formatNumber($data['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
                $data['modify_money'] = formatNumber($data['modify_money'],$digit_info['other_digit'],$digit_info['type']);
                $data['total_money'] = formatNumber($data['total_money'],$digit_info['other_digit'],$digit_info['type']);
            }
        }
        $late_payment_money = $data['late_payment_money'];
        $late_payment_day = $data['late_payment_day'];
        $data['late_payment_money'] = $late_payment_money;
        $data['late_payment_day'] = $late_payment_day;
        $data['charge_valid_time_txt'] = date('Y-m-d H:i:s', $data['charge_valid_time']);
        $project_info = $db_house_new_charge_project->getOne(['id'=>$data['project_id']],'type');
        if ($project_info['type'] == 2) {
            $data['service_month_num'] = $data['service_month_num']?$data['service_month_num']:1;
            if ($data['bill_create_set'] == 1)
                $data['service_month_num'] = $data['service_month_num'] . '日';
            elseif ($data['bill_create_set'] == 2)
                $data['service_month_num'] = $data['service_month_num'] . '个月';
            elseif ($data['bill_create_set'] == 3)
                $data['service_month_num'] = $data['service_month_num'] . '年';
            else
                $data['service_month_num'] = '--';
        }
        if ($data['service_end_time']) {
            if ($data['service_end_time'] >= time())
                $data['is_in_service'] = 1;
            else
                $data['is_in_service'] = 2;
        }
        if ($data['service_end_time'])
            $data['service_end_time'] = date('Y-m-d H:i:s', $data['service_end_time']);
        if ($data['service_start_time'])
            $data['service_start_time'] = date('Y-m-d H:i:s', $data['service_start_time']);
        if ($data['pay_time'])
            $data['pay_time_txt'] = date('Y-m-d H:i:s', $data['pay_time']);
        if ($data['add_time'])
            $data['add_time_txt'] = date('Y-m-d H:i:s', $data['add_time']);
        $data['all_fee'] = $data['modify_money'] + $data['late_payment_money'];
        $return_data['all_fee'] = $data['all_fee'];
        $return_data['list'][] = array(
            'title' => '收费标准名称',
            'val' => $data['charge_name']
        );
        $return_data['list'][] = array(
            'title' => '收费项目',
            'val' => $data['project_name']
        );
        $return_data['list'][] = array(
            'title' => '应收费用',
            'val' => $data['total_money']
        );
        $return_data['list'][] = array(
            'title' => '实收费用',
            'val' => $data['modify_money']
        );
        if(isset($data['now_ammeter']) && isset($data['last_ammeter']) && $data['now_ammeter']-$data['last_ammeter']>0){
            $return_data['list'][] = array(
                'title' => '用量',
                'val' => $data['now_ammeter']-$data['last_ammeter']
            );
        }
        $return_data['list'][] = array(
            'title' => '收费标准生效时间',
            'val' => $data['charge_valid_time_txt']
        );
        $return_data['list'][] = array(
            'title' => '账单生成时间',
            'val' => $data['add_time_txt']
        );
        if ($data['is_prepare'] != 1) {
            $return_data['list'][] = array(
                'title' => '收费周期',
                'val' => $data['service_month_num']
            );
            $return_data['list'][] = array(
                'title' => '预缴周期',
                'val' => '无'
            );
        }else{
            $return_data['list'][] = array(
                'title' => '收费周期',
                'val' => '无'
            );
            $return_data['list'][] = array(
                'title' => '预缴周期',
                'val' => $data['service_month_num']
            );
        }
        if ($data['is_prepare'] == 1) {
            $return_data['list'][] = array(
                'title' => '预缴优惠',
                'val' => $data['diy_content']
            );
            $return_data['list'][] = array(
                'title' => '预缴费用',
                'val' => $data['modify_money']
            );
        } else {
            $return_data['list'][] = array(
                'title' => '预缴周期',
                'val' => 0
            );
            $return_data['list'][] = array(
                'title' => '预缴优惠',
                'val' => ''
            );
            $return_data['list'][] = array(
                'title' => '预缴费用',
                'val' => 0
            );
        }
        $return_data['list'][] = array(
            'title' => '滞纳天数',
            'val' => $data['late_payment_day']
        );
        $return_data['list'][] = array(
            'title' => '滞纳金收取比例（每天）',
            'val' => $data['late_fee_rate'].'%'
        );
        $return_data['list'][] = array(
            'title' => '滞纳金费用',
            'val' => $data['late_payment_money']
        );
        return $return_data;
    }

    /**
     * 订单数量
     * @param array $where
     * @return int
     * @author lijie
     * @date_time 2021/06/15
     */
    public function getCount($where = [])
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $count = $db_house_new_pay_order->getCount($where);
        return $count;
    }

    /**
     * 房间/车位绑定消费标准列表
     * @author lijie
     * @date_time 2021/06/15
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     */
    public function getChargeStandardBindList($where = [], $field = true, $page = 0, $limit = 6, $order = 'b.id DESC')
    {
        $db_house_new_charge_standard_bind = new HouseNewChargeStandardBind();
        $data = $db_house_new_charge_standard_bind->getList($where, $field, $page, $limit, $order);
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        if ($data) {
            if($data)
                $data = $data->toArray();
            foreach ($data as $k => $v) {
                $condition=[];
                if ($v['cycle']<1){
                    $v['cycle']=1;
                }
                if($v['vacancy_id']){
                    $type = 1;
                    $id = $v['vacancy_id'];
                    $condition[] = ['room_id','=',$v['vacancy_id']];
                }else{
                    $type = 2;
                    $id = $v['position_id'];
                    $condition[] = ['position_id','=',$v['position_id']];
                }
                $is_valid = $service_house_new_charge_rule->checkChargeValid($v['project_id'], $v['charge_rule_id'],$id,$type);
                if (!$is_valid) {
                    $data[$k]['is_valid'] = 0;
                }else{
                    $data[$k]['is_valid'] = 1;
                }
                if(isset($v['charge_valid_type'])){
                    if ($v['charge_valid_type'] == 1)
                        $data[$k]['charge_valid_time_txt'] = date('Y-m-d', $v['charge_valid_time']);
                    elseif ($v['charge_valid_type'] == 2)
                        $data[$k]['charge_valid_time_txt'] = date('Y-m', $v['charge_valid_time']);
                    else
                        $data[$k]['charge_valid_time_txt'] = date('Y', $v['charge_valid_time']);
                }else{
                    $data[$k]['charge_valid_time_txt'] = date('Y-m-d', $v['charge_valid_time']);
                }
                //$condition[] = ['project_id','=',$v['project_id']];
                $condition[] = ['is_paid','=',1];
                $condition[] = ['refund_type','<>',2];
                $projectInfo = $this->getProjectInfo(['id'=>$v['project_id']],'subject_id');
                $numberInfo = $this->getNumberInfo(['id'=>$projectInfo['subject_id']],'charge_type');
                $condition[] = ['order_type','=',$numberInfo['charge_type']];
                $info = $this->getOneOrder($condition,'service_end_time','service_end_time DESC');
                if(strtotime($info['service_end_time']) > time()){
                    $is_expire = 0;
                }else{
                    $is_expire = 1;
                }
                $data[$k]['order_add_time_txt'] = date('Y-m-d', $v['order_add_time']);
                if ($v['type'] == 2) {
                    if ($v['order_add_type'] == 1)
                        $data[$k]['order_add_type_txt'] = '按日生成';
                    elseif ($v['order_add_type'] == 2)
                        $data[$k]['order_add_type_txt'] = '按月生成';
                    elseif ($v['order_add_type'] == 3)
                        $data[$k]['order_add_type_txt'] = '按年生成';
                    else
                        $data[$k]['order_add_type_txt'] = '--';
                } else {
                    $data[$k]['order_add_type_txt'] = '--';
                }
                // r.not_house_rate,r.fees_type,r.bill_create_set,r.bill_arrears_set,r.bill_type
                if(in_array($v['charge_type'],['water','gas','electric'])){
                    $data[$k]['not_house_rate_txt'] = '-';
                    $data[$k]['fees_type_txt'] = '-';
                    $data[$k]['bill_create_set_txt'] = '-';
                    $data[$k]['bill_arrears_set_txt'] = '-';
                    $data[$k]['bill_type_txt'] = '-';
                }else{
                    if(isset($v['not_house_rate'])){
                        $data[$k]['not_house_rate_txt'] = empty($v['not_house_rate']) ? '-' : ($v['not_house_rate'].'%');
                    }
                    if(isset($v['fees_type'])){
                        $data[$k]['fees_type_txt'] = empty($v['fees_type']) ? '-' : (($v['fees_type'] == 1) ? '固定费用' : '单价计量单位');
                    }
                    if(isset($v['bill_create_set'])){
                        $data[$k]['bill_create_set_txt'] = empty($v['bill_create_set']) ? '-' : (($v['bill_create_set'] == 1) ? '按日生成' : (($v['bill_create_set'] == 2) ? '按月生成' : '按年生成'));
                    }
                    if(isset($v['bill_arrears_set'])){
                        $data[$k]['bill_arrears_set_txt'] = empty($v['bill_arrears_set']) ? '-' : (($v['bill_arrears_set'] == 1) ? '预生成' : '后生成');
                    }
                    if(isset($v['bill_type'])){
                        $data[$k]['bill_type_txt'] = empty($v['bill_type']) ? '-' : (($v['bill_type'] == 1) ? '手动' : '自动');
                    }
                    if($v['charge_type']=='park_new'){
                        $data[$k]['is_prepaid'] =$v['is_prepaid']= 2;
                    }
                }

                $manual_btn = 0;
                $prepaid_btn = 0;
                if($is_valid){
                    if(($v['bill_type'] == 1 || $v['type'] == 1) && $v['type'] != 0){
                        $manual_btn = 1;
                    }
                    if($v['is_prepaid'] == 1 && $v['type'] != 1 && $v['bill_type'] != 1 && $v['type'] != 0){
                        $prepaid_btn = 1;
                    }
                }
                $data[$k]['manual_btn'] = $manual_btn;
                $data[$k]['prepaid_btn'] = $prepaid_btn;
                /*$db_house_new_pay_order = new HouseNewPayOrder();
                if(!$position_id){
                    $his_record = $db_house_new_pay_order->get_one(['project_id'=>$v['project_id'],'room_id'=>$room_id,'is_refund'=>1,'is_discard'=>1], 'service_end_time');
                }else{
                    $his_record = $db_house_new_pay_order->get_one(['project_id'=>$v['project_id'],'position_id'=>$position_id,'is_refund'=>1,'is_discard'=>1], 'service_end_time');
                }
                if($his_record){
                    if($his_record['service_end_time'] > time()){
                        $is_expire = 0;
                    }else{
                        $is_expire = 1;
                    }
                }else{
                    $is_expire = 1;
                }*/
                $data[$k]['is_expire'] = $is_expire;
            }
        }
        return array_values($data);
    }

    /**
     * 可预存收费项列表页
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param $room_id
     * @return mixed
     * @author lijie
     * @date_time 2021/07/07
     */
    public function getPrepaidList($where = [], $field = true, $page = 0, $limit = 6, $order = 'b.id DESC', $room_id)
    {
        $db_house_new_charge_standard_bind = new HouseNewChargeStandardBind();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_new_charge_project = new HouseNewChargeProject();
        $db_house_new_charge_number = new HouseNewChargeNumber();
        $data = $db_house_new_charge_standard_bind->getList($where, $field, $page, $limit, $order);
        if ($data) {
            foreach ($data as $k => $v) {
                $res = $db_house_new_pay_order->get_one(['is_paid' => 1, 'is_refund' => 1, 'is_discard' => 1, 'room_id' => $room_id, 'project_id' => $v['project_id']], 'service_end_time');
                if($res){
                    $service_end_time = $res['service_end_time'];
                }else{
                    $service_end_time = time();
                }
                if($v['bill_create_set'] == 1)
                    $data[$k]['service_end_time'] = date('Y-m-d', $service_end_time);
                elseif($v['bill_create_set'] == 2)
                    $data[$k]['service_end_time'] = date('Y-m', $service_end_time);
                else
                    $data[$k]['service_end_time'] = date('Y', $service_end_time);
                $project_info = $db_house_new_charge_project->getOne(['id'=>$v['project_id']],'subject_id');
                $number_info = $db_house_new_charge_number->get_one(['id'=>$project_info['subject_id']]);
                if($number_info['charge_type'] == 'park'){
                    $data[$k]['service_end_time'] = '';
                }
            }
        }
        return $data;
    }

    /**
     * 房间/车位绑定消费标准数量
     * @author lijie
     * @date_time 2021/06/17
     * @param array $where
     * @param array $whereOr
     * @return int
     */
    public function getChargeStandardBindCount($where = [],$whereOr=[])
    {
        $db_house_new_charge_standard_bind = new HouseNewChargeStandardBind();
        $count = $db_house_new_charge_standard_bind->getCount($where,$whereOr);
        return $count;
    }

    /**
     * 删除房间/车位绑定消费标准
     * @param array $where
     * @return bool
     * @author lijie
     * @date_time 2021/06/17
     */
    public function delChargeStandardBind($where = [])
    {
        $db_house_new_charge_standard_bind = new HouseNewChargeStandardBind();
        $res = $db_house_new_charge_standard_bind->delOne($where);
        return $res;
    }


    /**
     *获取作废订单
     * @author: liukezhu
     * @date : 2021/6/17
     */
    public function getCancelOrder($where = [], $where1 = '', $field = true, $page = 1, $limit = 10,$order='',$menus=[])
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village_detail_record = new HouseVillageDetailRecord();
        $db_house_new_charge_project = new HouseNewChargeProject();
        $db_village_info = new HouseVillageInfo();

        $village_id = 0;
        $is_online = 0;
        $room_flag=0;
        $where_summary = [];
        $check_level_info=array();
        if (!empty($where)) {
            if(isset($where['check_level_info'])){
                $check_level_info=$where['check_level_info'];
                unset($where['check_level_info']);
            }
            foreach ($where as $k => &$va) {
                if ($va[0] == 'record_status') {
                    $record_status = $va[2];
                    unset($where[$k]);
                }
                if ($va[0] == 'o.village_id') {
                    $village_id = $va[2];
                }
                if ($va[0] == 'o.room_id') {
                    $room_flag=1;
                }
                if ($va[0] == 'o.pay_type') {
                    $pay_type_arr =explode('-',$va[2]);
                    if ($pay_type_arr[0]==1){
                        $va[2]=1;
                        $where_summary[]=['pay_type','=',1];
                       // unset($where[$k]);
                    }elseif($pay_type_arr[0]==2){
                        $where[]=['o.offline_pay_type','find in set',$pay_type_arr[1]]; //whereFindInSet
                        $va[1]='in';
                        $va[2]=array(2,22);
                    }elseif($pay_type_arr[0]==4){
                        $va[2]=4;
                        $where_summary[]=['online_pay_type','=',$this->pay_type_arr1[$pay_type_arr[1]]];
                        $where_summary[]=['pay_type','=',4];
                        $is_online = 1;
                      //  unset($where[$k]);
                    }elseif($pay_type_arr[0]==5){
                        $where_summary[]=['online_pay_type','in',['hqpay_wx','hqpay_al']];
                        unset($where[$k]);
                    }
                }
                if ($va[0] == 'o.is_online' && $va[2] == 1) {
                    $is_online = 1;
                }
            }
            $where = array_values($where);
        }
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $where_summary[] = ['is_paid', '=', 1];
        $where_summary[] = ['village_id', '=', $village_id];
        $summary_list = $db_house_new_pay_order_summary->getLists($where_summary, '*');
        $sumMoney=$db_house_new_pay_order_summary->sumMoney(['is_paid'=>1,'village_id'=>$village_id,'pay_type'=>4]);
        $sumRefundMoney=$db_house_new_pay_order_summary->sumRefundMoney(['is_paid'=>1,'village_id'=>$village_id,'pay_type'=>4]);
        if (!empty($summary_list)) {
            $summary_list = $summary_list->toArray();
            if (!empty($summary_list)) {
                foreach ($summary_list as $val) {
                     $summary_arr[]=$val['summary_id'];
                }
            }
        }
        $db_house_new_offline_pay = new HouseNewOfflinePay();
        $where_pay = [];
        $pay_list = $db_house_new_offline_pay->getList($where_pay, '*');
        $payList = [];
        if (!empty($pay_list)) {
            $pay_list = $pay_list->toArray();
            if (!empty($pay_list)) {
                foreach ($pay_list as $vv) {
                    $payList[$vv['id']] = $vv['name'];
                }
            }
        }
        if (isset($summary_arr)&&!empty($summary_arr)){
            $where[]=['o.summary_id','in',$summary_arr];
        }
        if ($is_online == 1&&(!isset($summary_arr)||empty($summary_arr))){
            $data['list'] = [];
            $data['total_limit'] = $limit;
            $data['count'] = 0;
            $data['sumMoney'] = $sumMoney-$sumRefundMoney;
            $data['sumMoney'] =formatNumber($data['sumMoney'],2,1);
            return $data;
        }

        // 有房间ID，就不查询有车场ID的 对应原1945行条件
        if($room_flag == 1){
            $where[] = ['o.position_id','=',''];
        }
        $record = $db_house_village_detail_record->getList([['order_type','=',2]],'order_id');
        $record_order = [0];
        if(!empty($record)){
            $record_order = array_column($record,'order_id');
            if (isset($record_status) && $record_status == 1) {
                $where[]=['o.order_id','in',$record_order];
            } elseif (isset($record_status) && $record_status == 2) {
                $where[]=['o.order_id','not in',$record_order];
            }
        }else{
            if (isset($record_status) && $record_status == 1) {
                $where[]=['o.order_id','in',$record_order];
            } elseif (isset($record_status) && $record_status == 2) {
                $where[]=['o.order_id','not in',$record_order];
            }
        }
        $village_info_config = $db_village_info->getOne([['village_id','=',$village_id]],'print_number_times');

        $is_allow_print=true;
        if(!empty($menus) && in_array(111182,$menus)){
            $is_allow_print=false;
        }

        $list = $db_house_new_pay_order->getCancelOrder($where, $where1, $field, $page, $limit,$order);
        if ($list) {
            $list = $list->toArray();
            if(!empty($list)){

		 $digit_info=array();
                if(empty($digit_info)){
                    $sumMoney = formatNumber($sumMoney,2,1);
                    $sumRefundMoney= formatNumber($sumRefundMoney,2,1);
                }else{
                    $sumMoney = formatNumber($sumMoney,$digit_info['other_digit'],$digit_info['type']);
                    $sumRefundMoney = formatNumber($sumRefundMoney,$digit_info['other_digit'],$digit_info['type']);
                }
                // print_r($list);exit;
                foreach ($list as $k => &$v) {
                    $is_button=false;
                    if(isset($village_info_config['print_number_times']) && (intval($village_info_config['print_number_times']) == 0)){
                        if($is_allow_print && !empty($record_order) && in_array($v['order_id'],$record_order)){
                            $is_button=true;
                        }
                    }
                    $v['is_button']=$is_button;
                    $v['pay_money'] = formatNumber($v['pay_money'],2,1);
                    // print_r($v);
                    if (!empty($summary_list) && !empty($v['summary_id'])) {
                        foreach ($summary_list as $val) {
                            //    print_r($val);
                            $is_online_show = 0;
                            if ($val['summary_id'] == $v['summary_id']) {
                                $pay_time = $val['pay_time'];
                                $pay_type = $val['pay_type'];
                                $offline_pay_type = '';
                                if (!empty($payList) && !empty($val['offline_pay_type'])) {
                                    if(strpos($val['offline_pay_type'],',')>0){
                                        $offline_pay_type_arr=explode(',',$val['offline_pay_type']);
                                        foreach ($offline_pay_type_arr as $opay){
                                            if(isset($payList[$opay])){
                                                $offline_pay_type.=empty($offline_pay_type) ? $payList[$opay]:'、'.$payList[$opay];
                                            }
                                        }
                                    }else{
                                        $offline_pay_type = isset($payList[$val['offline_pay_type']]) ? $payList[$val['offline_pay_type']]:'';
                                    }

                                }
                                $online_pay_type = $val['online_pay_type'];
                                if ($val['pay_type'] == 4 && $is_online == 1) {
                                    if (empty($val['online_pay_type'])) {
                                        $is_online_show = 1;
                                    }
                                }
                                $order_no=$val['paid_orderid'];
                                break;
                            }

                        }
                    }
                    /* if (!empty($is_online_show) && isset($is_online_show)) {
                         unset($list[$k]);
                         continue;
                     }*/
                    //  print_r($pay_type);exit;
                    $v['pay_type_way']=0;
                    if (isset($pay_type) && !empty($pay_type)) {
                        $v['pay_type_way']=$pay_type;
                        if (in_array($pay_type, [2, 22])) {
                            $v['pay_type'] = $this->pay_type[$pay_type] . '-' . $offline_pay_type;
                        } elseif (in_array($pay_type, [1, 3])) {
                            $v['pay_type'] = $this->pay_type[$pay_type];
                        } elseif ($pay_type == 4) {
                            if (empty($online_pay_type)) {
                                $online_pay_type1 = '余额支付';
                            } else {
                                $online_pay_type1 = $this->pay_type_arr[$online_pay_type];
                            }
                            $v['pay_type'] = $this->pay_type[$pay_type] . '-' . $online_pay_type1;
                            if (isset($order_no)&&!empty($order_no)){
                                $v['order_no']=$order_no;
                            }
                        }
                    }
                    //   print_r($v['pay_type']);
                    if (isset($v['update_time']) && $v['update_time'] > 1) {
                        $v['updateTime'] = date('Y-m-d H:i:s', $v['update_time']);
                    }
                    if (isset($pay_time) && $pay_time > 1) {
                        $v['pay_time'] = date('Y-m-d H:i:s', $pay_time);
                    } else {
                        if (isset($v['pay_time']) && $v['pay_time'] > 1) {
                            $v['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                        }
                    }
                    //   print_r($v['pay_time']);
                    $project_info = $db_house_new_charge_project->getOne(['id'=>$v['project_id']]);
                    if($project_info && !$project_info->isEmpty()){
                        $project_info = $project_info->toArray();
                        if (!empty($project_info)&&$project_info['type']==1){
                            $v['service_start_time']=1;
                            $v['service_end_time']=1;
                        }
                    }

                    if (isset($v['service_start_time']) && $v['service_start_time'] > 1) {
                        $v['service_start_time'] = date('Y-m-d', $v['service_start_time']);
                    }else{
                        $v['service_start_time'] = '--';
                    }
                    if (isset($v['service_end_time']) && $v['service_end_time'] > 1) {
                        $v['service_end_time'] = date('Y-m-d', $v['service_end_time']);
                    }else{
                        $v['service_end_time'] = '--';
                    }

                    $record = $db_house_village_detail_record->getOne(['order_id' => $v['order_id'],'order_type'=>2]);
                    /*if (isset($record_status) && $record_status == 1) {
                        if (empty($record)) {
                            unset($list[$k]);
                            continue;
                        } else {
                            $v['record_status'] = '已开票';
                        }
                    } elseif (isset($record_status) && $record_status == 2) {
                        if (empty($record)) {
                            $v['record_status'] = '未开票';
                        } else {
                            unset($list[$k]);
                            continue;
                        }
                    } else {
                        if (empty($record)) {
                            $v['record_status'] = '未开票';
                        } else {
                            $v['record_status'] = '已开票';
                        }
                    }*/

                    if (empty($record)) {
                        $v['record_status'] = '未开票';
                    } else {
                        $v['record_status'] = '已开票';
                    }

                    if ($v['is_refund'] == 1) {
                        $v['order_status'] = '正常';
                    } else {
                        $refund_money_tmp=round($v['refund_money'],2);
                        $pay_money_tmp=round($v['pay_money'],2);
                        if ($refund_money_tmp == $pay_money_tmp) {
                            $v['order_status'] = '已退款';
                        } else {
                            $v['order_status'] = '部分退款';
                        }
                        $v['refund_money'] =formatNumber($v['refund_money'],2,1);
                    }
                    if (empty($v['pay_bind_name'])){
                        $v['pay_bind_name']='无';
                    }
                    if (empty($v['pay_bind_phone'])){
                        $v['pay_bind_phone']='无';
                    }

                    $number = '';
                    /*if ($room_flag==1&&!empty($v['position_id'])){
                        unset($list[$k]);
                        continue;
                    }*/
                    if (!empty($v['position_id'])) {
                        $position_num = $db_house_village_parking_position->getLists(['position_id' => $v['position_id']], 'pp.position_num,pg.garage_num', 0);
                        if (!empty($position_num)) {
                            $position_num = $position_num->toArray();
                            if (!empty($position_num)) {
                                $position_num1 = $position_num[0];
                                if (empty($position_num1['garage_num'])) {
                                    $position_num1['garage_num'] = '临时车库';
                                }
                                $number = $position_num1['garage_num'] . $position_num1['position_num'];
                            }
                        }
                    } elseif (!empty($v['room_id'])) {
                        $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
                        if (!empty($room)) {
                            $room = $room->toArray();
                            if (!empty($room)) {
                                $room1 = $room[0];
                                $house_village_service=new HouseVillageService();
                                $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$v['village_id']);
                                // $number = $room1['single_name'] . '栋' . $room1['floor_name'] . '单元' . $room1['layer_name'] . '层' . $room1['room'];
                            }
                        }
                    }
                    $v['numbers'] = $number;
                    $v['add_time'] = date('Y-m-d', $v['add_time']);
                    $v['my_check_status'] = 0;  //我看到审核状态 1审核中 2审核
                    $v['order_apply_info'] = '';
                    $v['check_status_str'] = '';
                    if (isset($v['check_status']) && $v['check_status'] == 2) {
                        $v['check_status_str'] = '审核中';
                    }
                    //处理审核状态
                    if (isset($v['check_status']) && $v['check_status'] == 2 && !empty($check_level_info)) {
                        $v['my_check_status'] = 1;
                        $houseVillageCheckauthDetailService = new HouseVillageCheckauthDetailService();
                        if (!empty($check_level_info['wid'])) {
                            $checkauthApplyWhere = array('order_id' => $v['order_id'], 'village_id' => $v['village_id'], 'wid' => $check_level_info['wid'], 'apply_id' => $v['check_apply_id']);
                            $checkDetail = $houseVillageCheckauthDetailService->getOneData($checkauthApplyWhere);
                            if (!empty($checkDetail) && $checkDetail['status'] == 0) {
                                $v['my_check_status'] = 2;
                                $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
                                $order_apply = $houseVillageCheckauthApplyService->getOneData(['id' => $checkDetail['apply_id'], 'order_id' => $v['order_id']]);
                                if ($order_apply && !empty($order_apply['extra_data'])) {
                                    $order_apply_info = json_decode($order_apply['extra_data'], 1);
                                    if ($order_apply_info) {
                                        $order_apply_info['opt_time_str'] = $order_apply_info['opt_time'] > 0 ? date('Y-m-d H:i:s', $order_apply_info['opt_time']) : '';
                                    }
                                    $v['order_apply_info'] = $order_apply_info;
                                }
                            } elseif (!empty($checkDetail) && $checkDetail['status'] == 1) {
                                $v['my_check_status'] = 3;
                            }
                        }

                    } elseif (isset($v['check_status']) && $v['check_status'] == 2) {
                        $v['my_check_status'] = 1;
                    }
                }
            }

        } else {
            $list = [];
        }
        /*  print_r($list);exit;*/
        $count = $db_house_new_pay_order->getCancelOrderCount($where, $where1);

        $data['list'] = array_values($list);
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        $data['sumMoney'] =  $sumMoney-$sumRefundMoney;
        $data['sumMoney']=formatNumber($data['sumMoney'],2,1);
        return $data;
    }



    /**
     *获取作废订单
     * @author: liukezhu
     * @date : 2021/6/17
     */
    public function getCancelOrderUser($where = [], $where1 = '', $field = true, $page = 1, $limit = 10,$order='')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village_detail_record = new HouseVillageDetailRecord();
        $village_id = 0;
        $is_online = 0;
        $room_flag=0;
        $where_summary = [];
        if (!empty($where)) {
            foreach ($where as $k => &$va) {
                if ($va[0] == 'record_status') {
                    $record_status = $va[2];
                    unset($where[$k]);
                }
                if ($va[0] == 'o.village_id') {
                    $village_id = $va[2];
                }
                if ($va[0] == 'o.room_id') {
                    $room_flag=1;
                }
                if ($va[0] == 'o.pay_type') {
                    $pay_type_arr =explode('-',$va[2]);
                    if ($pay_type_arr[0]==1){
                        $va[2]=1;
                        $where_summary[]=['pay_type','=',1];
                        unset($where[$k]);
                    }elseif($pay_type_arr[0]==2){
                        $where[]=['o.offline_pay_type','find in set',$pay_type_arr[1]];
                        $va[1]='in';
                        $va[2]=array(2,22);
                    }elseif($pay_type_arr[0]==4){
                        $va[2]=4;
                        $where_summary[]=['online_pay_type','=',$this->pay_type_arr1[$pay_type_arr[1]]];
                        $where_summary[]=['pay_type','=',4];
                        $is_online = 1;
                        unset($where[$k]);
                    }
                }
                if ($va[0] == 'o.is_online' && $va[2] == 1) {
                    $is_online = 1;
                }
            }
            $where = array_values($where);
        }
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $where_summary[] = ['is_paid', '=', 1];
        $where_summary[] = ['village_id', '=', $village_id];
        $summary_list = $db_house_new_pay_order_summary->getLists($where_summary, '*');
        $sumMoney=$db_house_new_pay_order_summary->sumMoney(['is_paid'=>1,'village_id'=>$village_id,'pay_type'=>4]);
        if (!empty($summary_list)) {
            $summary_list = $summary_list->toArray();
            if (!empty($summary_list)) {
                foreach ($summary_list as $val) {
                    $summary_arr[]=$val['summary_id'];
                }
            }
        }
        $db_house_new_offline_pay = new HouseNewOfflinePay();
        $where_pay = [];
        $pay_list = $db_house_new_offline_pay->getList($where_pay, '*');
        $payList = [];
        if (!empty($pay_list)) {
            $pay_list = $pay_list->toArray();
            if (!empty($pay_list)) {
                foreach ($pay_list as $vv) {
                    $payList[$vv['id']] = $vv['name'];
                }
            }
        }
        if (isset($summary_arr)&&!empty($summary_arr)){
            $where[]=['o.summary_id','in',$summary_arr];
        }
        if ($is_online == 1&&(!isset($summary_arr)||empty($summary_arr))){
            $data['list'] = [];
            $data['total_limit'] = $limit;
            $data['count'] = 0;
            $data['sumMoney'] = $sumMoney;
            return $data;
        }

        // print_r(1111);exit;
        $list = $db_house_new_pay_order->getCancelOrder($where, $where1, $field, $page, $limit,$order);
        if($list){
            $list = $list->toArray();
        }
        if ($list) {
		$digit_info=array();

            if(empty($digit_info)){
                $sumMoney = formatNumber($sumMoney,2,1);
            }else{
                $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                $sumMoney = formatNumber($sumMoney,$digit_info['other_digit'],$digit_info['type']);
            }
            foreach ($list as $k => &$v) {
                if (empty($v['project_name'])&&$v['car_type']=='stored_type'){
                    $v['project_name']='储值车充值（'.$v['car_number'].'）';
                }
                if (empty($v['project_name'])&&$v['car_type']=='temporary_type'){
                    $v['project_name']='临时车缴费（'.$v['car_number'].'）';
                }
                if(empty($digit_info)){
                    $v['pay_money'] = formatNumber($v['pay_money'],2,1);
                }else{
                    if($v['order_type'] == 'water' || $v['order_type'] == 'electric' || $v['order_type'] == 'gas'){
                        $digit_info['meter_digit'] = $digit_info['meter_digit']>2 || empty($digit_info['meter_digit'])?2:$digit_info['meter_digit'];
                        $v['pay_money'] = formatNumber($v['pay_money'],$digit_info['meter_digit'],$digit_info['type']);
                    }else{
                        $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                        $v['pay_money'] = formatNumber($v['pay_money'],$digit_info['other_digit'],$digit_info['type']);
                    }
                }
                // print_r($v);
                if (!empty($summary_list) && !empty($v['summary_id'])) {
                    foreach ($summary_list as $val) {
                        //    print_r($val);
                        $is_online_show = 0;
                        if ($val['summary_id'] == $v['summary_id']) {
                            $pay_time = $val['pay_time'];
                            $pay_type = $val['pay_type'];
                            $offline_pay_type = '';
                            if (!empty($payList) && !empty($val['offline_pay_type'])) {
                                if(strpos($val['offline_pay_type'],',')>0){
                                    $offline_pay_type_arr=explode(',',$val['offline_pay_type']);
                                    foreach ($offline_pay_type_arr as $opay){
                                        if(isset($payList[$opay])){
                                            $offline_pay_type.=empty($offline_pay_type) ? $payList[$opay]:'、'.$payList[$opay];
                                        }
                                    }
                                }else{
                                    $offline_pay_type = isset($payList[$val['offline_pay_type']]) ? $payList[$val['offline_pay_type']]:'';
                                }

                            }
                            $online_pay_type = $val['online_pay_type'];
                            if ($val['pay_type'] == 4 && $is_online == 1) {
                                if (empty($val['online_pay_type'])) {
                                    $is_online_show = 1;
                                }
                            }
                            $order_no=$val['paid_orderid'];
                            break;
                        }

                    }
                }
                /* if (!empty($is_online_show) && isset($is_online_show)) {
                     unset($list[$k]);
                     continue;
                 }*/
                //  print_r($pay_type);exit;
                if (isset($pay_type) && !empty($pay_type)) {
                    if (in_array($pay_type, [2, 22])) {
                        $v['pay_type'] = $this->pay_type[$pay_type] . '-' . $offline_pay_type;
                    } elseif (in_array($pay_type, [1, 3])) {
                        $v['pay_type'] = $this->pay_type[$pay_type];
                    } elseif ($pay_type == 4) {
                        if (empty($online_pay_type)) {
                            $online_pay_type1 = '余额支付';
                        } else {
                            $online_pay_type1 = $this->pay_type_arr[$online_pay_type];
                        }
                        $v['pay_type'] = $this->pay_type[$pay_type] . '-' . $online_pay_type1;
                        if (isset($order_no)&&!empty($order_no)){
                            $v['order_no']=$order_no;
                        }
                    }
                }
                //   print_r($v['pay_type']);
                if (isset($v['update_time']) && $v['update_time'] > 1) {
                    $v['updateTime'] = date('Y-m-d H:i:s', $v['update_time']);
                }
                if (isset($pay_time) && $pay_time > 1) {
                    $v['pay_time'] = date('Y-m-d H:i:s', $pay_time);
                } else {
                    if (isset($v['pay_time']) && $v['pay_time'] > 1) {
                        $v['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                    }
                }
                //   print_r($v['pay_time']);
                if (isset($v['service_start_time']) && $v['service_start_time'] > 1) {
                    $v['service_start_time'] = date('Y-m-d', $v['service_start_time']);
                }else{
                    $v['service_start_time'] = '--';
                }
                if (isset($v['service_end_time']) && $v['service_end_time'] > 1) {
                    $v['service_end_time'] = date('Y-m-d', $v['service_end_time']);
                }else{
                    $v['service_end_time'] = '--';
                }

                $record = $db_house_village_detail_record->getOne(['order_id' => $v['order_id'],'order_type'=>2]);
                if (isset($record_status) && $record_status == 1) {
                    if (empty($record)) {
                        unset($list[$k]);
                        continue;
                    } else {
                        $v['record_status'] = '已开票';
                    }
                } elseif (isset($record_status) && $record_status == 2) {
                    if (empty($record)) {
                        $v['record_status'] = '未开票';
                    } else {
                        unset($list[$k]);
                        continue;
                    }
                } else {
                    if (empty($record)) {
                        $v['record_status'] = '未开票';
                    } else {
                        $v['record_status'] = '已开票';
                    }
                }
                if ($v['is_refund'] == 1) {
                    $v['order_status'] = '正常';
                } else {
                    if ($v['refund_money'] == $v['pay_money']) {
                        $v['order_status'] = '已退款';
                    } else {
                        $v['order_status'] = '部分退款';
                    }
                }
                if (empty($v['pay_bind_name'])){
                    $v['pay_bind_name']='无';
                }
                if (empty($v['pay_bind_phone'])){
                    $v['pay_bind_phone']='无';
                }

                $number = '';
                if ($room_flag==1&&!empty($v['position_id'])){
                    unset($list[$k]);
                    continue;
                }
                if (!empty($v['position_id'])) {
                    $position_num = $db_house_village_parking_position->getLists(['position_id' => $v['position_id']], 'pp.position_num,pg.garage_num', 0);
                    if (!empty($position_num)) {
                        $position_num = $position_num->toArray();
                        if (!empty($position_num)) {
                            $position_num1 = $position_num[0];
                            if (empty($position_num1['garage_num'])) {
                                $position_num1['garage_num'] = '临时车库';
                            }
                            $number = $position_num1['garage_num'] . $position_num1['position_num'];
                        }
                    }
                } elseif (!empty($v['room_id'])) {
                    $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
                    if (!empty($room)) {
                        $room = $room->toArray();
                        if (!empty($room)) {
                            $room1 = $room[0];
                            $house_village_service=new HouseVillageService();
                            $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$v['village_id']);
                            // $number = $room1['single_name'] . '栋' . $room1['floor_name'] . '单元' . $room1['layer_name'] . '层' . $room1['room'];
                        }
                    }
                }
                $v['numbers'] = $number;
                /* print_r($v);
                  print_r($list[$k]);*/
            }
        } else {
            $list = [];
        }
        /*  print_r($list);exit;*/
        $count = $db_house_new_pay_order->getCancelOrderCount($where, $where1);

        $data['list'] = array_values($list);
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        $data['sumMoney'] = $sumMoney;
        return $data;
    }
    /**
     *获取作废订单
     * @author: liukezhu
     * @date : 2021/6/17
     */
    public function getCancelOrder1($where = [], $where1 = '', $field = true, $page = 1, $limit = 10,$order='')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village_detail_record = new HouseVillageDetailRecord();
        $room_flag=0;
        if (!empty($where)) {
            foreach ($where as $k => &$va) {
                if ($va[0] == 'o.room_id') {
                    $room_flag=1;
                }
            }
        }

        $list = $db_house_new_pay_order->getCancelOrder($where, $where1, $field, $page, $limit,$order);
        if ($list) {
            $list = $list->toArray();
            if ($list){

		$digit_info=array();
                foreach ($list as $k => &$v) {

                    if(empty($digit_info)){
                        $v['total_money']= formatNumber($v['total_money'],2,1);
                    }else{
                        if($v['order_type'] == 'water' || $v['order_type'] == 'electric' || $v['order_type'] == 'gas'){
                            $v['total_money']= formatNumber($v['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                        }else{
                            $v['total_money'] = formatNumber($v['total_money'],$digit_info['other_digit'],$digit_info['type']);
                        }
                    }

                    if (isset($v['update_time']) && $v['update_time'] > 1) {
                        $v['updateTime'] = date('Y-m-d H:i:s', $v['update_time']);
                    }
                    if (isset($pay_time) && $pay_time > 1) {
                        $v['pay_time'] = date('Y-m-d H:i:s', $pay_time);
                    } else {
                        if (isset($v['pay_time']) && $v['pay_time'] > 1) {
                            $v['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                        }
                    }
                    if (isset($v['service_start_time']) && $v['service_start_time'] > 1) {
                        $v['service_start_time'] = date('Y-m-d', $v['service_start_time']);
                    }else{
                        $v['service_start_time'] = '--';
                    }
                    if (isset($v['service_end_time']) && $v['service_end_time'] > 1) {
                        $v['service_end_time'] = date('Y-m-d', $v['service_end_time']);
                    }else{
                        $v['service_end_time'] = '--';
                    }
                    if ($room_flag==1&&!empty($v['position_id'])){
                        unset($list[$k]);
                        continue;
                    }
                    $number = '';
                    if (!empty($v['room_id'])) {
                        $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
                        if (!empty($room)) {
                            $room = $room->toArray();
                            if (!empty($room)) {
                                $room1 = $room[0];
                                $house_village_service=new HouseVillageService();
                                $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$v['village_id']);
                               //  $number = $room1['single_name'] . '栋' . $room1['floor_name'] . '单元' . $room1['layer_name'] . '层' . $room1['room'];
                            }
                        }
                    } elseif (!empty($v['position_id'])) {
                        $position_num = $db_house_village_parking_position->getLists(['position_id' => $v['position_id']], 'pp.position_num,pg.garage_num', 0);
                        if (!empty($position_num)) {
                            $position_num = $position_num->toArray();
                            if (!empty($position_num)) {
                                $position_num1 = $position_num[0];
                                if (empty($position_num1['garage_num'])) {
                                    $position_num1['garage_num'] = '临时车库';
                                }
                                $number = $position_num1['garage_num'] . $position_num1['position_num'];
                            }
                        }
                    }
                    $v['numbers'] = $number;

                }
            }

        } else {
            $list = [];
        }
        $count = $db_house_new_pay_order->getCancelOrderCount($where, $where1);

        $data['list'] = array_values($list);
        $data['total_limit'] = $limit;
        $data['count'] = $count;

        return $data;
    }


    /**
     * 根据分组获取账单列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param string $group
     * @return mixed
     * @author lijie
     * @date_time 2021/06/25
     */
    public function getOrderListByGroup($where = [], $field = true, $page = 0, $limit = 15, $order = 'o.order_id DESC', $group = 'o.room_id,o.position_id')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $service_house_village_user_bind_service = new HouseVillageUserBindService();
        $data = $db_house_new_pay_order->getListByGroup($where, $field, $page, $limit, $order, $group);
        $total_money = 0.00;
        if (!empty($data)) {
            $data = $data->toArray();
            if (!empty($data)) {
                $db_house_property_digit_service = new HousePropertyDigitService();
                $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$data[0]['property_id']]);

                foreach ($data as &$val) {
                    $number = '';
                    if (!empty($val['position_id'])){
                        $position_num = $db_house_village_parking_position->getLists(['position_id' => $val['position_id']], 'pp.position_num,pg.garage_num', 0);
                        //   print_r($position_num);exit;
                        if (!empty($position_num)) {
                            $position_num = $position_num->toArray();
                            if (!empty($position_num)) {
                                $position_num1 = $position_num[0];
                                if (empty($position_num1['garage_num'])) {
                                    $position_num1['garage_num'] = '临时车库';
                                }
                                $number = $position_num1['garage_num'] . '-' . $position_num1['position_num'];
                                $val['position_num'] = $number;
                            }
                        }
                        $service_house_village_parking_service = new HouseVillageParkingService();
                        $bind_position  = $service_house_village_parking_service->getBindPosition(['position_id'=>$val['position_id']]);
                        if($bind_position){
                            $bind_info = $service_house_village_user_bind_service->getBindInfo(['pigcms_id'=>$bind_position['user_id']],'name,phone');
                            $val['name'] = isset($bind_info['name'])?$bind_info['name']:'无';
                            $val['phone'] = isset($bind_info['phone'])?$bind_info['phone']:'无';
                        }
                    }
                   elseif (!empty($val['room_id'])) {
                       $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $val['room_id']]);
                       if (!empty($room)) {
                           $room = $room->toArray();
                           if (!empty($room)) {
                               $room1 = $room[0];
                               $house_village_service=new HouseVillageService();
                               $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$data[0]['property_id']['village_id']);
                             //  $number = $room1['single_name'] . '栋' . $room1['floor_name'] . '单元' . $room1['layer_name'] . '层' . $room1['room'];
                               $val['room'] = $number;
                           }
                       }
                       $bind_info = $service_house_village_user_bind_service->getBindInfo([['vacancy_id','=',$val['room_id']],['status','=',1],['type','in','0,3']],'name,phone');
                       $val['name'] = isset($bind_info['name'])?$bind_info['name']:'无';
                       $val['phone'] = isset($bind_info['phone'])?$bind_info['phone']:'无';
                   }
                    if(empty($digit_info)){
                        $val['total_money'] = formatNumber($val['total_money'],2,1);
                    }else{
                        $val['total_money'] = formatNumber($val['total_money'],$digit_info['other_digit'],$digit_info['type']);
                    }
                    $val['number'] = $number;
                    $tot_money = $val['total_money'];
                    $val['total_money'] =$val['total_money'].'元';
                    $total_money += $tot_money;
                }
            }
        }

        return ['list'=>$data,'total_money'=>$total_money];
    }


    /**
     * 查询订单详情
     * @author:zhubaodi
     * @date_time: 2021/6/25 14:45
     */
    public function getPayOrderInfo($order_id)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village_detail_record = new HouseVillageDetailRecord();
        $db_house_new_charge_number = new HouseNewChargeNumber();
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $db_house_new_pay_order_refund = new HouseNewPayOrderRefund();
        $db_admin = new HouseAdmin();
        $where[] = ['order_id', '=', $order_id];
        $data = $db_house_new_pay_order->getOne($where, 'o.*,p.name as project_name,p.subject_id,r.bill_create_set,p.type')->toArray();
       // print_r($data);exit;
        if (!empty($data)) {

	    $digit_info =[];
            if(isset($data['check_apply_id']) && ($data['check_apply_id']>0) && ($data['check_status']==4) && $data['pay_time']>100){
                $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
                $order_refund_apply = $houseVillageCheckauthApplyService->getOneData(['id' => $data['check_apply_id'],'order_id'=>$data['order_id'],'xtype'=>'order_refund','village_id'=>$data['village_id']]);
                if(empty($order_refund_apply)){
                    $data['check_apply_id']=0;
                }
            }
            if (empty($data['refund_type']) ) {
                $data['refund_type'] ='无';
            }elseif($data['refund_type']==1){
                $data['refund_type'] ='仅退款，不还原账单';
            }elseif($data['refund_type']==2){
                $data['refund_type'] ='退款且还原账单';
            }
            if ($data['pay_time'] > 1) {
                $data['pay_time'] = date('Y-m-d H:i:s', $data['pay_time']);
            }
            if ($data['service_start_time'] > 1) {
                $data['service_start_time'] = date('Y-m-d', $data['service_start_time']);
            }else{
                $data['service_start_time']='无';
            }
            if ($data['service_end_time'] > 1) {
                $data['service_end_time'] = date('Y-m-d', $data['service_end_time']);
            }else{
                $data['service_end_time'] ='无';
            }
            $data['subject_name'] = '';
            $subjectinfo = $db_house_new_charge_number->get_one(['id' => $data['subject_id']]);
            if (!empty($subjectinfo)) {
                $data['subject_name'] = $subjectinfo['charge_number_name'];
            }
            if (!empty($data['summary_id'])){
                $summary_info= $db_house_new_pay_order_summary->getOne(['summary_id' => $data['summary_id']]);
                if (!empty($summary_info)){
                    $data['order_serial']=$summary_info['paid_orderid'];
                    $data['order_no']=$summary_info['order_no'];
                    $data['remark']=$summary_info['remark'];
                }
            }
            if (isset($summary_info['pay_type']) && !empty($summary_info['pay_type'])) {
                if ($data['pay_type'] == 2 || $data['pay_type'] == 22) {
                    $db_house_new_offline_pay = new HouseNewOfflinePay();
                    $offline_pay_type='';
                    if(strpos($data['offline_pay_type'],',')>0){
                        $offline_pay_type_arr=explode(',',$data['offline_pay_type']);
                        $where_pay_arr=array();
                        $where_pay_arr[]=['id','in',$offline_pay_type_arr];
                        $pay_list = $db_house_new_offline_pay->getList($where_pay_arr, '*');
                        $payList = [];
                        if (!empty($pay_list)) {
                            $pay_list = $pay_list->toArray();
                            if (!empty($pay_list)) {
                                foreach ($pay_list as $vv) {
                                    $payList[$vv['id']] = $vv['name'];
                                }
                            }
                        }
                        foreach ($offline_pay_type_arr as $opay){
                            if(isset($payList[$opay])){
                                $offline_pay_type.=empty($offline_pay_type) ? $payList[$opay]:'、'.$payList[$opay];
                            }
                        }

                    }else{
                        $offline_pay = $db_house_new_offline_pay->get_one(['id'=>$data['offline_pay_type']]);
                        if (!empty($offline_pay) && !$offline_pay->isEmpty()){
                            $offline_pay_type=$offline_pay['name'];
                        }
                    }

                    $data['pay_type'] = $this->pay_type[$data['pay_type']] . '-' . $offline_pay_type;
                } elseif (in_array($summary_info['pay_type'], [1, 3])) {
                    $data['pay_type'] = $this->pay_type[$summary_info['pay_type']];
                } elseif ($summary_info['pay_type'] == 4) {
                    if (empty($summary_info['online_pay_type'])) {
                        $online_pay_type1 = '余额支付';
                    } else {
                        $online_pay_type1 = $this->pay_type_arr[$summary_info['online_pay_type']];
                    }
                    $data['pay_type'] = $this->pay_type[$summary_info['pay_type']] . '-' . $online_pay_type1;
                }
            }

            $data['ammeter'] = $data['now_ammeter'] - $data['last_ammeter'];
            $admininfo = $db_admin->getOne(['id' => $data['role_id']]);
            $data['role_name'] = '';
            if (!empty($admininfo)) {
                if (!empty($admininfo['realname'])){
                    $data['role_name'] = $admininfo['realname'];
                }else{
                    $data['role_name'] = $admininfo['account'];
                }
            }
            $record = $db_house_village_detail_record->getOne(['order_id' => $data['order_id']]);
            if (empty($record)) {
                $data['record_status'] = '未开票';
            } else {
                $data['record_status'] = '已开票';
            }
            if ($data['is_refund'] == 1) {
                $data['refund_status'] = '正常';
            } else {
                if ($data['refund_money'] == $data['pay_money']) {
                    $data['refund_status'] = '已退款';
                } else {
                    $data['refund_status'] = '部分退款';
                }
            }
            if ($data['is_prepare']==2){
                $data['diy_type'] ='无';
            }else{
                if (empty($data['diy_type'])){
                    $data['diy_type'] ='无';
                }else{
                    $data['diy_type'] = $this->diy_type[$data['diy_type']];
                }
            }
            $number = '';
            if (!empty($data['position_id'])) {
                $position_num = $db_house_village_parking_position->getLists(['position_id' => $data['position_id']], 'pp.position_num,pg.garage_num', 0);
                if (!empty($position_num)) {
                    $position_num = $position_num->toArray();
                    if (!empty($position_num)) {
                        $position_num1 = $position_num[0];
                        if (empty($position_num1['garage_num'])) {
                            $position_num1['garage_num'] = '临时车库';
                        }
                        $number = $position_num1['garage_num'] . $position_num1['position_num'];
                    }
                }
            } elseif (!empty($data['room_id'])) {
                 $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $data['room_id']]);
                 if (!empty($room)) {
                     $room = $room->toArray();
                     if (!empty($room)) {
                         $room1 = $room[0];
                      //   $number = $room1['single_name'] . '栋' . $room1['floor_name'] . '单元' . $room1['layer_name'] . '层' . $room1['room'];
                         $house_village_service=new HouseVillageService();
                         $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$data['village_id']);

                     }
                 }
             }
            $data['numbers'] = $number;
            if(1||empty($digit_info)){
                $data['total_money']= formatNumber($data['total_money'],2,1).'元';
                if (empty($data['modify_reason'])){
                    $data['modify_money']='无';
                    $data['modify_reason']='无';
                }else{
                    $data['modify_money']=formatNumber($data['modify_money'],2,1).'元';
                }

                $data['pay_amount_points']=formatNumber($data['pay_amount_points']/100,2,1).'元';
                $data['system_balance']=formatNumber($data['system_balance'],2,1).'元';
                $data['score_deducte']=formatNumber($data['score_deducte'],2,1).'元';
                $data['score_used_count']=formatNumber($data['score_used_count'],2,1);


                $data['pay_money']=formatNumber($data['pay_money'],2,1).'元';
                $data['late_payment_money']=formatNumber($data['late_payment_money'],2,1).'元';
                $data['prepare_pay_money']=formatNumber($data['prepare_pay_money'],2,1).'元';
                $data['refund_money']=formatNumber($data['refund_money'],2,1).'元';

            }else{
                //没有意义 这里处理的
                if($data['order_type'] == 'water' || $data['order_type'] == 'electric' || $data['order_type'] == 'gas'){
                    $data['total_money']= formatNumber($data['total_money'],$digit_info['meter_digit'],$digit_info['type']).'元';
                    if (empty($data['modify_reason'])){
                        $data['modify_money']='无';
                        $data['modify_reason']='无';
                    }else{
                        $data['modify_money']=formatNumber($data['modify_money'],$digit_info['meter_digit'],$digit_info['type']).'元';
                    }

                    $data['pay_amount_points']=formatNumber($data['pay_amount_points']/100,$digit_info['meter_digit'],$digit_info['type']).'元';
                    $data['system_balance']=formatNumber($data['system_balance'],$digit_info['meter_digit'],$digit_info['type']).'元';
                    $data['score_deducte']=formatNumber($data['score_deducte'],$digit_info['meter_digit'],$digit_info['type']).'元';
                    $data['score_used_count']=formatNumber($data['score_used_count'],$digit_info['meter_digit'],$digit_info['type']);


                    $data['pay_money']=formatNumber($data['pay_money'],$digit_info['meter_digit'],$digit_info['type']).'元';
                    $data['late_payment_money']=formatNumber($data['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']).'元';
                    $data['prepare_pay_money']=formatNumber($data['prepare_pay_money'],$digit_info['meter_digit'],$digit_info['type']).'元';
                    $data['refund_money']=formatNumber($data['refund_money'],$digit_info['meter_digit'],$digit_info['type']).'元';

                }else{
                    $data['total_money']= formatNumber($data['total_money'],$digit_info['other_digit'],$digit_info['type']).'元';
                    if (empty($data['modify_reason'])){
                        $data['modify_money']='无';
                        $data['modify_reason']='无';
                    }else{
                        $data['modify_money']=formatNumber($data['modify_money'],$digit_info['other_digit'],$digit_info['type']).'元';
                    }
                    $data['pay_amount_points']=formatNumber($data['pay_amount_points']/100,$digit_info['other_digit'],$digit_info['type']).'元';
                    $data['system_balance']=formatNumber($data['system_balance'],$digit_info['other_digit'],$digit_info['type']).'元';
                    $data['score_deducte']=formatNumber($data['score_deducte'],$digit_info['other_digit'],$digit_info['type']).'元';
                    $data['score_used_count']=formatNumber($data['score_used_count'],$digit_info['other_digit'],$digit_info['type']);

                    $data['pay_money']=formatNumber($data['pay_money'],$digit_info['other_digit'],$digit_info['type']).'元';
                    $data['late_payment_money']=formatNumber($data['late_payment_money'],$digit_info['other_digit'],$digit_info['type']).'元';
                    $data['prepare_pay_money']=formatNumber($data['prepare_pay_money'],$digit_info['other_digit'],$digit_info['type']).'元';
                    $data['refund_money']=formatNumber($data['refund_money'],$digit_info['other_digit'],$digit_info['type']).'元';
                }
            }
           // $data['total_money']=$data['total_money'].'元';
            if($data['type'] == 2){
                if(empty($data['service_month_num'])){
                    $data['service_month_num'] = 1;
                }
                if ($data['bill_create_set']==1){
                    $data['service_month_num']=$data['service_month_num'].'天';
                }elseif ($data['bill_create_set']==2){
                    $data['service_month_num']=$data['service_month_num'].'个月';
                }elseif ($data['bill_create_set']==3){
                    $data['service_month_num']=$data['service_month_num'].'年';
                }else{
                    $data['service_month_num']=$data['service_month_num'].'';
                }
                if($data['is_prepare']==1){
                    $data['prepare_month_num'] = $data['service_month_num'];
                }else{
                    $data['prepare_month_num'] = 0;
                }
            }
            if (empty($data['diy_content'])){
                $data['diy_content']='无优惠';
            }

        }
        return $data;
    }


    /**
     * 查询退款纪录
     * @author:zhubaodi
     * @date_time: 2021/6/25 19:12
     */
    public function getRefundList($order_id, $page, $limit)
    {
        $db_house_new_pay_order = new HouseNewPayOrderRefund();
        $db_house_new_pay_order1 = new HouseNewPayOrder();
         $db_admin = new HouseAdmin();
        $where[] = ['order_id', '=', $order_id];
        $page = empty($page) ? 1 : $page;
        $orderInfo=$db_house_new_pay_order1->get_one($where);
        $count = $db_house_new_pay_order->getCount($where);
        $list = $db_house_new_pay_order->getList($where, true, $page, $limit);
        if (!empty($list)) {
            $list = $list->toArray();
            if (!empty($list)) {

		$digit_info=[];
                // 退款总金额
                $refund_money_total = 0;
                foreach ($list as &$v) {
                    $v['add_time'] = date('Y-m-d', $v['add_time']);
                    $admininfo = $db_admin->getOne(['id' => $v['role_id']]);
                    $v['role_name'] = '';
                    if (!empty($admininfo)) {
                        if (!empty($admininfo['realname'])){
                             $v['role_name']= $admininfo['realname'];
                        }else{
                             $v['role_name'] = $admininfo['account'];
                        }
                    }
                    $v['refund_score_count']= formatNumber($v['refund_score_count'],2,1);
                    if(1||empty($digit_info)){
                        $v['refund_money']= formatNumber($v['refund_money'],2,1);
                        $v['refund_online_money']= formatNumber($v['refund_online_money'],2,1);
                        $v['refund_balance_money']= formatNumber($v['refund_balance_money'],2,1);
                        $v['refund_score_money']= formatNumber($v['refund_score_money'],2,1);
                    }else{
                        if($orderInfo['order_type'] == 'water' || $orderInfo['order_type'] == 'electric' || $orderInfo['order_type'] == 'gas'){
                            $v['refund_online_money']= formatNumber($v['refund_online_money'],$digit_info['meter_digit'],$digit_info['type']);
                            $v['refund_balance_money']= formatNumber($v['refund_balance_money'],$digit_info['meter_digit'],$digit_info['type']);
                            $v['refund_score_money']= formatNumber($v['refund_score_money'],$digit_info['meter_digit'],$digit_info['type']);
                            $v['refund_money']= formatNumber($v['refund_money'],$digit_info['meter_digit'],$digit_info['type']);
                        }else{
                            $v['refund_online_money']= formatNumber($v['refund_online_money'],$digit_info['other_digit'],$digit_info['type']);
                            $v['refund_balance_money']= formatNumber($v['refund_balance_money'],$digit_info['other_digit'],$digit_info['type']);
                            $v['refund_score_money']= formatNumber($v['refund_score_money'],$digit_info['other_digit'],$digit_info['type']);
                            $v['refund_money']= formatNumber($v['refund_money'],$digit_info['other_digit'],$digit_info['type']);
                        }
                    }
                    $refund_money_total = round((float)$refund_money_total + (float)$v['refund_money'],4);
                    $v['remaining_amount'] = round((float)$orderInfo['pay_money'] - (float)$refund_money_total,4);
                }
            }
        }

        $data = [];
        $data['list'] = $list;
        $data['count'] = $count;
        $data['total_limit'] = $limit;
        return $data;

    }

    /**
     * 添加退款纪录
     * @author:zhubaodi
     * @date_time: 2021/6/25 19:12
     * *$extra 额外数据 审核时用
     */
    public function addRefundInfo($role_id, $order_id, $refund_type, $refund_money, $refund_reason,$extra=array())
    {
        $db_house_new_pay_order_refund = new HouseNewPayOrderRefund();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $db_house_new_charge = new HouseNewCharge();
        $db_house_new_order_log = new HouseNewOrderLog();
        $db_plat_order = new PlatOrder();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $db_charge_project = new HouseNewChargeProject();
        $db_house_new_charge_standard_bind = new HouseNewChargeStandardBind();
        $orderInfo = $db_house_new_pay_order->get_one(['order_id' => $order_id], '*');
        if (empty($orderInfo)) {
            throw new \think\Exception("订单信息不存在！");
        }
        $meter_type=1;
        $other_digit=2;
        $meter_digit=2;
        $db_house_property_digit_service = new HousePropertyDigitService();
        $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$orderInfo['property_id']]);
        if (!empty($digit_info)){
            $meter_type=$digit_info['type'];
            $other_digit=$digit_info['other_digit'];
            $meter_digit=$digit_info['meter_digit'];
        }
        /*if ($digit_info && !$digit_info->isEmpty()){
            $meter_type=$digit_info['type'];
            $other_digit=$digit_info['other_digit'];
            $meter_digit=$digit_info['meter_digit'];
        }*/
        $ruleInfo=$db_house_new_charge_rule->getOne(['id'=>$orderInfo['rule_id']]);
        if(isset($ruleInfo['rule_digit']) && $ruleInfo['rule_digit']>-1 && $ruleInfo['rule_digit']<5){
            $other_digit=$ruleInfo['rule_digit'];
            $meter_digit=$ruleInfo['rule_digit'];
        }
        // 查询收费标准是否是一次性费用
        $refund_period = $db_charge_project->getOne([['id','=',$ruleInfo['charge_project_id']],['type','=',1],['status','=','1']],'refund_period,name');
        $refund_status = 1;
        $chargeInfo = $db_house_new_charge->get_one(['village_id' => $orderInfo['village_id']], '*');
        if (!empty($extra) && isset($extra['opt_type']) && ($extra['opt_type'] == 'check_pass_refund')) {
            //审核完成通过后退款 疲敝掉判断

        } else{
            if (!empty($chargeInfo)) {

                if ($chargeInfo['refund_term'] <= 0) {
                    throw new \think\Exception("未设置退款期限，订单不能进行退款！");
                }

                if ($refund_period && !$refund_period->isEmpty()) {
                    $refund_period = $refund_period->toArray();
                    if ($refund_period['refund_period']<$chargeInfo['refund_term']){
                        $refund_period['refund_period']=$chargeInfo['refund_term'];
                    }
                    // 一次性费用，则使用一次性费用退款期限
                    $time = intval($refund_period['refund_period']) * 86400;
                    if ($time <= 0) {
                        throw new \think\Exception("该订单不能进行退款，请前往【收费项目管理】【" . $refund_period['name'] . "】收费项目名称，编辑（一次性费用退款期限）");
                    }
                } else {
                    $time = intval($chargeInfo['refund_term']) * 86400;
                    if ($time <= 0) {
                        throw new \think\Exception("该订单不能进行退款，请前往【收费设置】，编辑（已缴账单退款期限）");
                    }
                }

                $time1 = time() - $orderInfo['pay_time'];
                if ($time1 > $time) {
                    throw new \think\Exception("订单已超过退款期限！");
                }
            } else {
                throw new \think\Exception("订单未设置退款权限！");
            }

            fdump_api(['退款信息' . __LINE__, $refund_money, $orderInfo['pay_money']], 'new_village_order_refund_zbd', 1);
            if ($refund_money > formatNumber($orderInfo['pay_money'], 2)) {
                throw new \think\Exception("退款金额不能大于实付金额");
            }
            if ($refund_money > formatNumber(($orderInfo['pay_money'] - $orderInfo['refund_money']), 2)) {
                throw new \think\Exception("退款总金额不能大于实付金额");
            }
        }

        $summaryInfo = $db_house_new_pay_order_summary->getOne(['summary_id' => $orderInfo['summary_id']], '*');
        if ($refund_type == 2) {
            //todo判断是不是按照自然月来生成订单，true默认开启自然月配置，配置项后面加
            if (!empty($ruleInfo)&&cfg('open_natural_month') == 1){
                $where[] = ['is_del', '=', 1];
                $where[] = ['project_id', '=', $orderInfo['project_id']];
                $where[] = ['vacancy_id', '=', $orderInfo['room_id']];
                $where[] = ['position_id', '=', $orderInfo['position_id']];
                $where[] = ['charge_valid_time', '<=', time()];
                $list = $db_house_new_charge_standard_bind->getLists1($where, 'rule_id', 'charge_valid_time desc');
                $id = 0;
                if (!empty($list)){
                    $list=$list->toArray();
                    if (isset($list[0]['rule_id']) && !empty($list[0]['rule_id'])) {
                        $id = $list[0]['rule_id'];
                    }
                }
             /*   $service_rule=new HouseNewChargeRuleService();
                $rule_id=$service_rule->getValidChargeRule($orderInfo['project_id']);*/
                $ruleInfo1=$db_house_new_charge_rule->getOne(['id'=>$id]);
              //  print_r($ruleInfo1->toArray());exit;
                if (!empty($ruleInfo1)&&$ruleInfo1['charge_valid_type']!=$ruleInfo['charge_valid_type'] && (empty($extra) || !isset($extra['opt_type']) && ($extra['opt_type'] != 'check_pass_refund'))){
                    throw new \think\Exception("收费标准更替,无法退款！");
                }
            }
            $refund_money = $orderInfo['pay_money'];
            $refund_status = 1;
        }
        $refund_money=round($refund_money,2);
        if (!empty($extra) && isset($extra['opt_type']) && ($extra['opt_type'] == 'check_pass_refund')) {
            //审核完成通过后退款 不在走 else里的业务 改变一下 订单里的状态
            $orderUpdateArr = array('check_status' => 3);
            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
        } else {
            $houseVillageCheckauthSetService = new HouseVillageCheckauthSetService();
            $orderRefundCheckWhere = array('village_id' => $orderInfo['village_id'], 'xtype' => 'order_refund_check');
            $checkauthSet = $houseVillageCheckauthSetService->getOneCheckauthSet($orderRefundCheckWhere);
            if (!empty($checkauthSet) && $checkauthSet['is_open']>0 && !empty($checkauthSet['check_level'])) {
                $orderRefundCheckArr = array('property_id' => $orderInfo['property_id'], 'village_id' => $orderInfo['village_id']);
                $orderRefundCheckArr['xtype'] = 'order_refund';
                $orderRefundCheckArr['order_id'] = $orderInfo['order_id'];
                $orderRefundCheckArr['other_relation_id'] = $summaryInfo['order_no'];
                $orderRefundCheckArr['money'] = $refund_money;
                $orderRefundCheckArr['status'] = 1;  //0未审核 1审核中 2审核通过
                $orderRefundCheckArr['apply_login_role'] = isset($extra['login_role']) ? $extra['login_role'] : 0;
                $orderRefundCheckArr['apply_name'] = isset($extra['apply_name']) ? $extra['apply_name'] : '';
                $orderRefundCheckArr['apply_phone'] = isset($extra['apply_phone']) ? $extra['apply_phone'] : '';
                $orderRefundCheckArr['apply_uid'] = isset($extra['apply_uid']) ? $extra['apply_uid'] : 0;
                $extra_data = array('order_id' => $order_id, 'refund_type' => $refund_type, 'refund_money' => $refund_money, 'refund_reason' => $refund_reason, 'role_id' => $role_id, 'opt_time' => time());
                $orderRefundCheckArr['extra_data'] = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
                $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();

                $extra['checkauth_set'] = $checkauthSet;
                $insert_id = $houseVillageCheckauthApplyService->addApply($orderRefundCheckArr, $extra);
                if ($insert_id > 0) {
                    $order_apply = $houseVillageCheckauthApplyService->getOneData(['id' => $insert_id]);
                    if ($order_apply['status'] == 2) {
                        //自动全额通过
                        $orderUpdateArr = array('check_status' => 3, 'check_apply_id' => $insert_id);
                        $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                    } else {
                        $orderUpdateArr = array('check_status' => 2, 'check_apply_id' => $insert_id);
                        $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                        //需要审核
                        return array('xtype' => 'check_opt', 'check_status' => 2, 'check_apply_id' => $insert_id);
                    }
                }
            }
        }
        /*
        if(in_array($orderInfo['order_type'],['water','electric','gas'])){
            $refund_money = formatNumber($refund_money,$meter_digit,$meter_type);
        }else{
            $refund_money = formatNumber($refund_money,$other_digit,$meter_type);
        }
        */

        $pay_money_tmp=round($orderInfo['pay_money'],2);
        if ($refund_type == 1 && $refund_money < $pay_money_tmp) {
            $refund_status = 2;
        } elseif ($refund_type == 1 && $refund_money == $pay_money_tmp) {
            $refund_status = 1;
        }
        $pay_type=0;
        if (in_array($orderInfo['pay_type'],[1,4])){
            $pay_type=1;
        }

        $refund_money= (double)$refund_money;
        $payInfo=$db_plat_order->get_one(['business_id' => $orderInfo['summary_id'],'business_type'=>'village_new_pay','paid'=>1]);
        $online_money=0;
        $system_balance_money=0;
        $score_deducte_money=0;
        $score_used_count=0;
        $refund_money3=$refund_money2=$refund_money1=$refund_money;
       if (!empty($payInfo) && $orderInfo['pay_type']!=2 && $orderInfo['pay_type']!=22){
            if(in_array($payInfo['pay_type'],['hqpay_wx','hqpay_al'])){
                if($refund_money != $summaryInfo['pay_money']){
                    throw new \think\Exception("【环球汇通聚合支付】不支持分批退款，请输入".$summaryInfo['pay_money'].'元');
                }
            }
            $orderInfo['pay_amount_points']=$orderInfo['pay_amount_points']/100;
            //1.线上可退款金额
            if($orderInfo['pay_amount_points']>0){
               $refund_online_money=$db_house_new_pay_order_refund->sumFieldv(['order_id'=>$orderInfo['order_id']],'refund_online_money');
               $t_money= (double)($orderInfo['pay_amount_points']-$refund_online_money);
               if ($t_money>$refund_money) {
                   $online_money = $refund_money;
                   $refund_money1 = 0;
               } elseif ($t_money<=0) {
                   $online_money = 0;

                   // $refund_money 不变
               } else {
                   $online_money = $t_money;
                   $refund_money1 =(double) ($refund_money-$t_money);
               }
            }
            //2.余额可退金额-当前只有线上和余额支付，有增加其他支付类型的需继续判断
            if ($refund_money1>0&&$orderInfo['system_balance']>0){
               //  $system_balance_money=$refund_money1;
                $refund_balance_money=$db_house_new_pay_order_refund->sumFieldv(['order_id'=>$orderInfo['order_id']],'refund_balance_money');
                $t_money= (double)($orderInfo['system_balance']-$refund_balance_money);
                if ($t_money>$refund_money1) {
                    $system_balance_money = $refund_money1;
                    $refund_money2 = 0;
                } elseif ($t_money<=0) {
                    $system_balance_money = 0;
                    // $refund_money 不变
                } else {
                    $system_balance_money = $t_money;
                    $refund_money2 =(double) ($refund_money1-$t_money);
                }

            }
            //3.积分可退金额-当前只有线上和余额支付、积分抵扣，有增加其他支付类型的需继续判断
            if ($refund_money2>0&&$orderInfo['score_deducte']>0){
                //  $system_balance_money=$refund_money1;
                $refund_score_money=$db_house_new_pay_order_refund->sumFieldv(['order_id'=>$orderInfo['order_id']],'refund_score_money');
                $t_money= (double)($orderInfo['score_deducte']-$refund_score_money);
                if ($t_money>$refund_money2) {
                    $score_deducte_money = $refund_money2;
                    $refund_money3 = 0;
                } elseif ($t_money<=0) {
                    $score_deducte_money = 0;
                    // $refund_money 不变
                } else {
                    $score_deducte_money = $t_money;
                    $refund_money3 =(double) ($refund_money2-$t_money);
                }

            }
         //   print_r([$orderInfo->toArray(),$online_money,$system_balance_money,$score_deducte_money,$score_used_count,$refund_money3,$refund_money2,$refund_money1,$refund_money]);exit;

           //线上退款
            if ($online_money>0){
                $param = [
                    'param' => array('business_type' => 'village_new_pay', 'business_id' => $orderInfo['summary_id']),
                    'operation_info' => '',
                    'refund_money' => round_number($online_money,2),
                    'pay_type'=>$pay_type
                ];
                $payService=new PayService();
                $db_pay_order_info = new PayOrderInfo();
                $pay_order_info = $db_pay_order_info->getByOrderNo($payInfo['orderid']);
                if (!empty($pay_order_info)){
                    if ($online_money>$pay_order_info['chinaums_merchant_already_get']&&$pay_order_info['channel']=='chinaums'){
                        $online_money=$pay_order_info['chinaums_merchant_already_get'];
                    }
                }
                fdump_api([$payInfo['orderid'],$online_money],'PayService_0211',1);
                $refund = $payService->refund($payInfo['orderid'],$online_money);
                if (isset($refund['refund_no'])&&!empty($refund['refund_no'])){
                    $business_order_table=new HouseVillagePayOrder();
                    $tOrder=$business_order_table->get_one(['order_id' => $payInfo['business_id']]);
                    $tOrder['order_type'] = 'new_village_refund';
                    $tOrder['desc'] = $payInfo['order_name'].'退款';
                    $tOrder['refund_money'] = $online_money;

                    if($payInfo['orderid']){
                        $pay_order_info=new PayOrderInfo();
                        $pay_info=$pay_order_info->getByOrderNo($payInfo['orderid']);
                        // if($pay_info['channel'] == 'scrcu' || $pay_info['channel'] == 'scrcuCash'){
                            if($pay_info['is_own'] > 0){
                                $tOrder['refund_money']=0;
                            }
                      //   }
                    }
                    $param_bill=[];
                    $param_bill=[
                        'village_id'=>$orderInfo['village_id'],
                        'money'=>$tOrder['refund_money'],
                        'type'=>'new_village_refund',
                        'desc'=>$payInfo['order_name'].'退款',
                        'order_id'=>$payInfo['order_id'],
                    ];
                    fdump_api(['退款写入物业余额'.__LINE__,$param_bill,$tOrder['refund_money'],$online_money],'new_village_order_refund_zbd',1);
                    $res =invoke_cms_model('Village_money_list/use_money', $param_bill);
                    fdump_api(['退款写入物业余额'.__LINE__,$param_bill,$tOrder['refund_money'],$res],'new_village_order_refund_zbd',1);
                    $data_plat_order['is_refund'] = 1;
                    $data_shop_order['order_id'] = $payInfo['order_id'];
                    $data_shop_order['refund_detail'] = serialize($param);
                    $db_plat_order->save_one(['order_id'=>$payInfo['order_id']],$data_plat_order);
                }

            }
            //余额退款
            if ($system_balance_money>0){
                $param = [
                    'param' => array('business_type' => 'village_new_pay','paid' => 1, 'business_id' => $orderInfo['summary_id']),
                    'operation_info' => '',
                    'refund_money' => round_number($system_balance_money,2),
                    'pay_type'=>$pay_type
                ];
                $refund = invoke_cms_model('Plat_order/new_village_order_refund', $param);
            }

            //积分退款
            if ($score_deducte_money>0){
                if($score_deducte_money==$orderInfo['score_deducte']){
                    $score_used_count=$orderInfo['score_used_count'];
                }else{
                    $score_used_count=round_number($orderInfo['score_used_count']*($score_deducte_money/$orderInfo['score_deducte']),2);
                }
                $refund = (new UserService())->addScore($summaryInfo['pay_uid'], $score_used_count, L_("账单退款 ，增加X1 ，订单编号X2", array( "X1" => $score_used_count, "X2" => $orderInfo['order_id'])));
                if (empty($refund['error_code'])){
                    $param_bill=[];
                    $param_bill=[
                        'village_id'=>$orderInfo['village_id'],
                        'money'=>$score_deducte_money,
                        'type'=>'new_village_refund',
                        'desc'=>$payInfo['order_name'].'退款',
                        'order_id'=>$payInfo['order_id'],
                    ];
                    fdump_api(['退款写入物业余额'.__LINE__,$param_bill,$score_deducte_money,$score_used_count,$refund],'new_village_order_refund_zbd',1);
                    $res =invoke_cms_model('Village_money_list/use_money', $param_bill);
                    fdump_api(['退款写入物业余额'.__LINE__,$param_bill,$score_deducte_money,$res],'new_village_order_refund_zbd',1);
                    $data_plat_order['is_refund'] = 1;
                    $data_shop_order['order_id'] = $payInfo['order_id'];
                    $data_shop_order['refund_detail'] = serialize($param_bill);
                    $db_plat_order->save_one(['order_id'=>$payInfo['order_id']],$data_plat_order);
                }
            }
           /* fdump_api(['退款信息'.__LINE__,$param],'new_village_order_refund_zbd',1);
            if ($payInfo['pay_money']>0){
                $payService=new PayService();
                $db_pay_order_info = new PayOrderInfo();
                $pay_order_info = $db_pay_order_info->getByOrderNo($payInfo['orderid']);
                if (!empty($pay_order_info)){
                    if ($refund_money>$pay_order_info['chinaums_merchant_already_get']&&$pay_order_info['channel']=='chinaums'){
                        $refund_money=$pay_order_info['chinaums_merchant_already_get'];
                    }
                }
                fdump_api([$payInfo['orderid'],$refund_money],'PayService_0211',1);
                $refund = $payService->refund($payInfo['orderid'],$refund_money);
                if (isset($refund['refund_no'])&&!empty($refund['refund_no'])){
                    $business_order_table=new HouseVillagePayOrder();
                    $tOrder=$business_order_table->get_one(['order_id' => $payInfo['business_id']]);
                    $tOrder['order_type'] = 'new_village_refund';
                    $tOrder['desc'] = $payInfo['order_name'].'退款';
                    $tOrder['refund_money'] = $refund_money;

                    if($payInfo['orderid']){
                        $pay_order_info=new PayOrderInfo();
                        $pay_info=$pay_order_info->getByOrderNo($payInfo['orderid']);
                        if($pay_info['channel'] == 'scrcu' || $pay_info['channel'] == 'scrcuCash'){
                            if($pay_info['is_own'] > 0){
                                $tOrder['refund_money']=0;
                            }
                        }
                    }
                    $param_bill=[];
                    $param_bill=[
                        'village_id'=>$orderInfo['village_id'],
                        'money'=>$tOrder['refund_money'],
                        'type'=>'new_village_refund',
                        'desc'=>$payInfo['order_name'].'退款',
                        'order_id'=>$payInfo['order_id'],
                    ];
                    fdump_api(['退款写入物业余额'.__LINE__,$param_bill,$tOrder['refund_money'],$refund_money],'new_village_order_refund_zbd',1);
                    $res =invoke_cms_model('Village_money_list/use_money', $param_bill);
                    fdump_api(['退款写入物业余额'.__LINE__,$param_bill,$tOrder['refund_money'],$res],'new_village_order_refund_zbd',1);
                    $data_plat_order['is_refund'] = 1;
                    $data_shop_order['order_id'] = $payInfo['order_id'];
                    $data_shop_order['refund_detail'] = serialize($param);
                    $db_plat_order->save_one(['order_id'=>$payInfo['order_id']],$data_plat_order);
                }
            }
            if ($payInfo['system_balance']>0){
                $refund = invoke_cms_model('Plat_order/new_village_order_refund', $param);
            }*/


        }elseif (!empty($payInfo)&&($orderInfo['pay_type']==2 ||$orderInfo['pay_type']==22)){
            //线下支付的
            $param = [
                'param' => array('business_type' => 'village_new_pay','paid' => 1, 'business_id' => $orderInfo['summary_id']),
                'operation_info' => '',
                'refund_money' => $refund_money,
                'pay_type'=>$pay_type
            ];
            $data_plat_order['is_refund'] = 1;
            $data_shop_order['order_id'] = $payInfo['order_id'];
            $data_shop_order['refund_detail'] = serialize($param);
            $db_plat_order->save_one(['order_id'=>$payInfo['order_id']],$data_plat_order);
        }
        $data = [
            'order_id' => $order_id,
            'refund_type' => $refund_type,
            'refund_money' => $refund_money,
            'refund_reason' => $refund_reason,
            'refund_status' => $refund_status,
            'refund_online_money'=>$online_money,
            'refund_balance_money'=>$system_balance_money,
            'refund_score_money'=>$score_deducte_money,
            'refund_score_count'=>$score_used_count,
            'role_id' => $role_id,
            'add_time' => time(),
            'update_time' => time(),

        ];
        $id = $db_house_new_pay_order_refund->addOne($data);
        if ($id > 0) {
            $data_order = [
                'is_refund' => 2,
                'refund_money' => $refund_money + $orderInfo['refund_money'],
                'refund_reason' => $refund_reason,
                'update_time' => time(),
                'refund_type' => $refund_type,
            ];
            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $data_order);
            //$summaryInfo = $db_house_new_pay_order_summary->getOne(['summary_id' => $orderInfo['summary_id']], '*');
            $data_order1 = [
                'is_refund' => 2,
                'refund_money' => $refund_money + $summaryInfo['refund_money'],
                'refund_reason' => $refund_reason,
            ];
            $db_house_new_pay_order_summary->saveOne(['summary_id' => $orderInfo['summary_id']], $data_order1);

            if ($refund_type==1){
                $new_order_log = [
                    'order_id' => $orderInfo['order_id'],
                    'order_type' => $orderInfo['order_type'],
                    'order_name' => $orderInfo['order_name'],
                    'room_id' => $orderInfo['room_id'],
                    'position_id' => $orderInfo['position_id'],
                    'property_id' => $orderInfo['property_id'],
                    'village_id' => $orderInfo['village_id'],
                    'service_start_time' => $orderInfo['service_start_time'],
                    'service_end_time' => $orderInfo['service_end_time'],
                    'project_id' => $orderInfo['project_id'],
                    'desc'=>'退款不还原账单',
                    'add_time' => time(),
                ];
                $db_house_new_order_log->addOne($new_order_log);
            }
            if ($refund_type == 2) {

                $logInfo = $db_house_new_order_log->getOne([['room_id' ,'=', $orderInfo['room_id']],['position_id' ,'=', $orderInfo['position_id']],['order_type','=', $orderInfo['order_type']]], '*','id DESC');
              // print_r($logInfo->toArray());
                if (empty($logInfo) || $logInfo['service_end_time']<100){
                    $orderInfo1 = $db_house_new_pay_order->get_one([['room_id' ,'=', $orderInfo['room_id']],['position_id' ,'=', $orderInfo['position_id']],['order_type','=', $orderInfo['order_type']],['is_paid','=',1],['refund_type','<>',2]], '*','service_end_time DESC');
                    if (!empty($orderInfo1)){
                        $service_end_time=$orderInfo1['service_end_time'];
                    }else{
                        $service_end_time=$orderInfo['service_start_time'];
                    }
                }else{
                    if (cfg('open_natural_month') == 1){
                        //todo判断是不是按照自然月来生成订单，true默认开启自然月配置，配置项后面加
                        if ($orderInfo['is_prepare']==1){
                            if ($ruleInfo['charge_valid_type']==1){
                                $service_end_time=$logInfo['service_end_time']-86400*($orderInfo['prepaid_cycle']+$orderInfo['service_give_month_num']);
                            }elseif($ruleInfo['charge_valid_type']==2){
                                $mouth=$orderInfo['service_month_num']+$orderInfo['service_give_month_num'];
                                $aa=date('Y-m-d H:i:s',strtotime('-'.$mouth.' month',$logInfo['service_end_time']));
                                $service_end_time=strtotime($aa);
                            }elseif($ruleInfo['charge_valid_type']==3){
                                $year=date('Y',$logInfo['service_end_time'])-1*($orderInfo['prepaid_cycle']+$orderInfo['service_give_month_num']);
                                $service_end_time=strtotime(date($year.'-m-d H:i:s',$logInfo['service_end_time']));
                            }
                        }else{
                            if (empty($orderInfo['service_month_num'])){
                                $orderInfo['service_month_num']=1;
                            }
                            if ($ruleInfo['charge_valid_type']==1){
                                $service_end_time=$logInfo['service_end_time']-86400*$orderInfo['service_month_num'];
                            }elseif($ruleInfo['charge_valid_type']==2){
                                $aa=date('Y-m-d H:i:s',strtotime('-'.$orderInfo['service_month_num'].' month',$logInfo['service_end_time']));
                                $service_end_time=strtotime($aa);
                            }elseif($ruleInfo['charge_valid_type']==3){
                                $year=date('Y',$logInfo['service_end_time'])-1*$orderInfo['service_month_num'];
                                $service_end_time=strtotime(date($year.'-m-d H:i:s',$logInfo['service_end_time']));
                            }
                        }
                    }else{
                        $service_end_time=$logInfo['service_end_time']-($orderInfo['service_end_time']-$orderInfo['service_start_time']);
                    }
                }
                $new_order = [
                    'summary_id' => $orderInfo['summary_id'],
                    'uid' => $orderInfo['uid'],
                    'pigcms_id' => $orderInfo['pigcms_id'],
                    'name' => $orderInfo['name'],
                    'phone' => $orderInfo['phone'],
                    'order_type' => $orderInfo['order_type'],
                    'order_name' => $orderInfo['order_name'],
                    'room_id' => $orderInfo['room_id'],
                    'position_id' => $orderInfo['position_id'],
                    'property_id' => $orderInfo['property_id'],
                    'village_id' => $orderInfo['village_id'],
                    'total_money' => $orderInfo['total_money'],
                    'modify_money' => $orderInfo['total_money'],
                    'is_paid' => 2,
                    'is_prepare' => $orderInfo['is_prepare'],
                    'prepare_pay_money' => $orderInfo['prepare_pay_money'],
                    'service_month_num' => $orderInfo['service_month_num'],
                    'service_give_month_num' => $orderInfo['service_give_month_num'],
                    'service_start_time' => $orderInfo['service_start_time'],
                    'service_end_time' => $orderInfo['service_end_time'],
                    'rate' => $orderInfo['rate'],
                    'diy_type' => $orderInfo['diy_type'],
                    'diy_content' => $orderInfo['diy_content'],
                    'rule_id' => $orderInfo['rule_id'],
                    'project_id' => $orderInfo['project_id'],
                    'order_no' => '',
                    'unit_price' => $orderInfo['unit_price'],
                    'from' => $orderInfo['from'],
                    'remark' => $orderInfo['remark'],
                    'add_time' => time(),
                    'update_time' => time(),
                    'role_id' => $orderInfo['role_id'],
                    'last_ammeter' => $orderInfo['last_ammeter'],
                    'now_ammeter' => $orderInfo['now_ammeter'],
                    'meter_reading_id'=>$orderInfo['meter_reading_id'],
                ];
                $db_house_new_pay_order->addOne($new_order);

                $new_order_log = [
                    'order_id' => $orderInfo['order_id'],
                    'order_type' => $orderInfo['order_type'],
                    'order_name' => $orderInfo['order_name'],
                    'room_id' => $orderInfo['room_id'],
                    'position_id' => $orderInfo['position_id'],
                    'property_id' => $orderInfo['property_id'],
                    'village_id' => $orderInfo['village_id'],
                    'service_start_time' => $orderInfo['service_start_time'],
                    'service_end_time' => $service_end_time,
                    'project_id' => $orderInfo['project_id'],
                    'desc'=>'退款且还原账单',
                    'add_time' => time(),
                ];
                $db_house_new_order_log->addOne($new_order_log);
                if($orderInfo['order_type'] == 'park' || $orderInfo['order_type'] == 'parking_management'){
                    $service_house_village_parking = new HouseVillageParkingService();
                    if($orderInfo['position_id']>0){
                        $service_house_village_parking->editParkingPosition(['position_id'=>$orderInfo['position_id']],['end_time'=>$service_end_time]);
                        $service_house_village_parking->editParkingCar(['car_position_id'=>$orderInfo['position_id']],['end_time'=>$service_end_time]);
                    }
                }
            }
        } else {
            throw new \think\Exception("退款失败");
        }
        return $id;
    }

    public function getRefundtype($order_id,$sum_refund_money=false)
    {
        $db_house_new_pay_order_refund = new HouseNewPayOrderRefund();
        $refundType = $db_house_new_pay_order_refund->getOne(['order_id' => $order_id]);
        if($sum_refund_money && !empty($refundType)){
            $refund_money_sum = $db_house_new_pay_order_refund->sumFieldv(['order_id' => $order_id],'refund_money');
            $refundType['refund_money']=$refund_money_sum;
        }
        return $refundType;
    }


    /**
     * 导出文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 14:44
     */
    public function saveExcel($title, $data, $fileName,$exporttype='',$exportPattern = 2)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(24);
        $sheet->getDefaultRowDimension()->setRowHeight(22);//设置行高
        $sheet->getRowDimension('1')->setRowHeight(25);//设置行高

        if($exporttype=='exportRefund'){
            $sheet->getStyle('A1:Ak1')->getFont()->setBold(true)->setSize(14);
            $sheet->mergeCells('A1:Ak1'); //合并单元格
            $sheet->getColumnDimension('A')->setWidth(32);
            $sheet->setCellValue('A1', '退款账单明细');
            $sheet->setCellValue('A2', '总合计：');
            $sheet->setCellValue('K2', $data['total_money']);
            $sheet->setCellValue('L2', $data['modify_money']);
            $sheet->setCellValue('M2', $data['pay_money']);
            $sheet->setCellValue('W2', $data['late_payment_money']);
            $sheet->setCellValue('Y2', $data['prepare_pay_money']);
            $sheet->setCellValue('Z2', $data['refund_money']);

            //设置单元格内容
            $titCol = 'A';
            foreach ($title as $key => $value) {
                //单元格内容写入
                $sheet->setCellValue($titCol . '3', $value);
                $sheet->getStyle('A3:Ak3')->getFont()->setBold(true);
                $titCol++;
            }
            //设置单元格内容
            $row = 4;
            foreach ($data['list'] as $k => $item) {
                $dataCol = 'A';
                $order_id=$item['order_id'];
                unset($item['order_id']);
               //  print_r($item);exit;
                foreach ($item as $value) {
                    //单元格内容写入
                    $sheet->setCellValue($dataCol . $row, $value);
                    $dataCol++;
                }
                if($exportPattern != 1){
                    $row1 = $row - 1;
                    if ($k > 0 && ($data['list'][$k]['numbers'] == $data['list'][$k - 1]['numbers'])) {
                        $sheet->mergeCells('A' . $row1 . ':' . 'A' . $row); //合并单元格
                        $sheet->mergeCells('B' . $row1 . ':' . 'B' . $row); //合并单元格
                        $sheet->mergeCells('C' . $row1 . ':' . 'C' . $row); //合并单元格
                        $sheet->mergeCells('D' . $row1 . ':' . 'D' . $row); //合并单元格
                        $sheet->mergeCells('E' . $row1 . ':' . 'E' . $row); //合并单元格
                        $sheet->mergeCells('F' . $row1 . ':' . 'F' . $row); //合并单元格
                    }
                    if ($k > 0 && ($data['list'][$k]['numbers'] == $data['list'][$k - 1]['numbers']) && ($data['list'][$k]['charge_number_name'] == $data['list'][$k - 1]['charge_number_name'])) {
                        $sheet->mergeCells('G' . $row1 . ':' . 'G' . $row); //合并单元格
                    }
                    if ($k > 0&&($order_id == $data['list'][$k - 1]['order_id'])){
                        $sheet->mergeCells('H' . $row1 . ':' . 'H' . $row); //合并单元格
                        $sheet->mergeCells('I' . $row1 . ':' . 'I' . $row); //合并单元格
                        $sheet->mergeCells('J' . $row1 . ':' . 'J' . $row); //合并单元格
                        $sheet->mergeCells('K' . $row1 . ':' . 'K' . $row); //合并单元格
                        $sheet->mergeCells('L' . $row1 . ':' . 'L' . $row); //合并单元格
                        $sheet->mergeCells('M' . $row1 . ':' . 'M' . $row); //合并单元格
                        $sheet->mergeCells('N' . $row1 . ':' . 'N' . $row); //合并单元格
                        $sheet->mergeCells('O' . $row1 . ':' . 'O' . $row); //合并单元格
                        $sheet->mergeCells('P' . $row1 . ':' . 'P' . $row); //合并单元格
                        $sheet->mergeCells('Q' . $row1 . ':' . 'Q' . $row); //合并单元格
                        $sheet->mergeCells('R' . $row1 . ':' . 'R' . $row); //合并单元格
                        $sheet->mergeCells('S' . $row1 . ':' . 'S' . $row); //合并单元格
                        $sheet->mergeCells('T' . $row1 . ':' . 'T' . $row); //合并单元格
                        $sheet->mergeCells('U' . $row1 . ':' . 'U' . $row); //合并单元格
                        $sheet->mergeCells('V' . $row1 . ':' . 'V' . $row); //合并单元格
                        $sheet->mergeCells('W' . $row1 . ':' . 'W' . $row); //合并单元格
                        $sheet->mergeCells('X' . $row1 . ':' . 'X' . $row); //合并单元格
                        $sheet->mergeCells('Y' . $row1 . ':' . 'Y' . $row); //合并单元格
                        $sheet->mergeCells('Z' . $row1 . ':' . 'Z' . $row); //合并单元格
                        $sheet->mergeCells('AA' . $row1 . ':' . 'AA' . $row); //合并单元格
                        $sheet->mergeCells('AB' . $row1 . ':' . 'AB' . $row); //合并单元格
                        $sheet->mergeCells('AC' . $row1 . ':' . 'AC' . $row); //合并单元格
                        $sheet->mergeCells('AD' . $row1 . ':' . 'AD' . $row); //合并单元格
                    }
                }
                $row++;
            }
        }
        else{
            $sheet->getStyle('A1:AG1')->getFont()->setBold(true)->setSize(12);
            $sheet->mergeCells('A1:AG1'); //合并单元格
            $sheet->setCellValue('A1', '已缴账单明细表');
            $sheet->setCellValue('A2', '总合计：');
            $sheet->setCellValue('K2', $data['total_money']);
            $sheet->setCellValue('L2', $data['modify_money']);
            $sheet->setCellValue('M2', $data['pay_money']);
            $sheet->setCellValue('W2', $data['late_payment_money']);
            $sheet->setCellValue('Y2', $data['prepare_pay_money']);
            $sheet->setCellValue('Z2', $data['refund_money']);
            //设置单元格内容
            $titCol = 'A';
            foreach ($title as $key => $value) {
                //单元格内容写入
                $sheet->setCellValue($titCol . '3', $value);
                $titCol++;
            }
            //设置单元格内容
            $row = 4;
            foreach ($data['list'] as $k => $item) {

                $dataCol = 'A';
                foreach ($item as $value) {
                    //单元格内容写入
                    $sheet->setCellValue($dataCol . $row, $value);
                    $dataCol++;
                }
                if($exportPattern != 1){
                    $row1 = $row - 1;
                    if ($k > 0 && ($data['list'][$k]['numbers'] == $data['list'][$k - 1]['numbers'])) {
                        $sheet->mergeCells('A' . $row1 . ':' . 'A' . $row); //合并单元格
                        $sheet->mergeCells('B' . $row1 . ':' . 'B' . $row); //合并单元格
                        $sheet->mergeCells('C' . $row1 . ':' . 'C' . $row); //合并单元格
                        $sheet->mergeCells('D' . $row1 . ':' . 'D' . $row); //合并单元格
                        $sheet->mergeCells('E' . $row1 . ':' . 'E' . $row); //合并单元格
                        $sheet->mergeCells('F' . $row1 . ':' . 'F' . $row); //合并单元格
                    }
                    if ($k > 0 && ($data['list'][$k]['numbers'] == $data['list'][$k - 1]['numbers']) && ($data['list'][$k]['charge_number_name'] == $data['list'][$k - 1]['charge_number_name'])) {
                        $sheet->mergeCells('G' . $row1 . ':' . 'G' . $row); //合并单元格
                    }
                }
                $row++;
            }
        }
        //保存

        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中

        if($exporttype=='exportRefund'){
            $sheet->getStyle('A1:Ak' . $total_rows)->applyFromArray($styleArrayBody);
         //   $sheet->getStyle('A2:A'. $total_rows)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        }else{
            $sheet->getStyle('A1:AG' . $total_rows)->applyFromArray($styleArrayBody);
        }
        //下载
        $filename = $fileName . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $this->downloadExportFile($filename);
    }

    /**
     * 下载表格
     */
    public function downloadExportFile($param)
    {
        $returnArr = [];
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/v20/runtime/' . $param)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }
        $filename = $param;

        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }

        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $param;
        $returnArr['error'] = 0;

        return $returnArr;
    }


    /**
     *导出账单
     * @author: zhubaodi
     * @date : 2021/6/26
     */
    public function printPayOrder($where = [], $where1 = '', $field = true, $order = 'o.order_id DESC',$exporttype='',$exportPattern = 2)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village_detail_record = new HouseVillageDetailRecord();
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $db_house_new_offline_pay = new HouseNewOfflinePay();
        $print_number = new HouseVillagePrintTemplateNumber();
        $db_admin = new HouseAdmin();

        $where_summary = [];
        $record_status = 0;
        $village_id = 0;
        if (!empty($where)) {
            foreach ($where as $k => &$va) {
                if ($va[0] == 'record_status') {
                    $record_status = $va[2];
                    unset($where[$k]);
                }
                if ($va[0] == 'o.pay_type') {
                    $pay_type_arr =explode('-',$va[2]);
                    if ($pay_type_arr[0]==1){
                        $va[2]=1;
                        $where_summary[]=['pay_type','=',1];
                        unset($where[$k]);
                    }elseif($pay_type_arr[0]==2){
                        $where[]=['o.offline_pay_type','find in set',$pay_type_arr[1]];
                        $va[1]='in';
                        $va[2]=array(2,22);
                    }elseif($pay_type_arr[0]==4){
                        $va[2]=4;
                        $where_summary[]=['online_pay_type','=',$this->pay_type_arr1[$pay_type_arr[1]]];
                        $where_summary[]=['pay_type','=',4];
                        $is_online = 1;
                        unset($where[$k]);
                    }
                }
                if ($va[0] == 'o.village_id') {
                    $village_id = $va[2];
                }
            }
            $where = array_values($where);
        }
        $where_summary[] = ['is_paid', '=', 1];
        $where_summary[] = ['village_id', '=', $village_id];
        $summary_list = $db_house_new_pay_order_summary->getLists($where_summary, '*');
        if (!empty($summary_list)) {
            $summary_list = $summary_list->toArray();
            if (!empty($summary_list)) {
                foreach ($summary_list as $val) {
                    $summary_arr[]=$val['summary_id'];
                }
            }
        }
        if (isset($summary_arr)&&!empty($summary_arr)){
            $where[]=['o.summary_id','in',$summary_arr];
        }

        // 开票状态筛选
        $record = $db_house_village_detail_record->getList([['order_type','=',2]],'order_id');
        $record_order = [0];
        if(!empty($record)){
            $record_order = array_column($record,'order_id');
            // 开票
            if ($record_status == 1) {
                $where[]=['o.order_id','in',$record_order];
            } elseif ($record_status == 2) {
                // 未开票
                $where[]=['o.order_id','not in',$record_order];
            }
        }else{
            if (isset($record_status) && $record_status == 1) {
                $where[]=['o.order_id','in',$record_order];
            } elseif (isset($record_status) && $record_status == 2) {
                $where[]=['o.order_id','not in',$record_order];
            }
        }

        $list = $db_house_new_pay_order->getPayOrder($where, $where1, $field, $order);

        $count = [];
        $count['total_money'] = 0;
        $count['modify_money'] = 0;
        $count['pay_money'] = 0;
        $count['late_payment_money'] = 0;
        $count['prepare_pay_money'] = 0;
        $count['refund_money'] = 0;
        $filename='已缴账单明细表';
        if($exporttype=='exportRefund'){
            $filename='退款账单明细';
        }
        $digit_info=array();
        if ($list) {
            $list= $list->toArray();
            if(empty($list)){
                throw new \think\Exception("暂无数据导出");
            }
            $data_list = [];

            $where_pay = [];
            $pay_list = $db_house_new_offline_pay->getList($where_pay, '*');
            $payList = [];
            if (!empty($pay_list)) {
                $pay_list = $pay_list->toArray();
                if (!empty($pay_list)) {
                    foreach ($pay_list as $vv) {
                        $payList[$vv['id']] = $vv['name'];
                    }
                }
            }
            foreach ($list as $k => $v) {
                $count['total_money'] = $count['total_money'] + (round($v['total_money'],2));
                $count['modify_money'] = $count['modify_money'] + (round($v['modify_money'],2));
                $count['pay_money'] = $count['pay_money'] + (round($v['pay_money'],2));
                $count['late_payment_money'] = $count['late_payment_money'] + $v['late_payment_money'];
                $count['prepare_pay_money'] = $count['prepare_pay_money'] + $v['prepare_pay_money'];
                $count['refund_money'] = $count['refund_money'] + $v['refund_money'];
                if (isset($v['update_time']) && $v['update_time'] > 1) {
                    $v['updateTime'] = date('Y-m-d', $v['update_time']);
                }
                if (isset($v['pay_time']) && $v['pay_time'] > 1) {
                    $v['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                }
                if (isset($v['service_start_time']) && $v['service_start_time'] > 1) {
                    $v['service_start_time'] = date('Y-m-d', $v['service_start_time']);
                }else{
                    $v['service_start_time'] ='无';
                }
                if (isset($v['service_end_time']) && $v['service_end_time'] > 1) {
                    $v['service_end_time'] = date('Y-m-d', $v['service_end_time']);
                }else{
                    $v['service_end_time']='无';
                }

                $record = $db_house_village_detail_record->getOne(['order_id' => $v['order_id']]);
                if (empty($record)) {
                    $v['record_status'] = '未开票';
                    $v['print_number'] = '';
                } else {
                    $v['record_status'] = '已开票';
                }
                $no = $print_number->getList([['order_ids','find in set',$v['order_id']]],'print_number');
                $print_num = [];
                foreach ($no as $k1 => $v1){
                    if(!empty($v1['print_number'])){
                        $print_num[] = sprintf('%07d',$v1['print_number']);
                    }
                }
                $v['print_number'] = (!empty($print_num)) ? implode(',',$print_num) : '';
                if ($v['is_refund'] == 1) {
                    $v['order_status'] = '未退款';
                }elseif($v['refund_money'] >= $v['pay_money']){
                    $v['order_status'] = '已退款';
                } else {
                    $v['order_status'] = '部分退款';
                }
                if (empty($v['refund_type']) || ($v['refund_type']<1)) {
                    $v['refund_type'] ='无';
                }elseif($v['refund_type']==1){
                    $v['refund_type'] ='仅退款，不还原账单';
                }elseif($v['refund_type']==2){
                    $v['refund_type'] ='退款且还原账单';
                }
                $number = '';
                $number_name = '';
                if (!empty($v['position_id'])) {
                    $position_num = $db_house_village_parking_position->getLists(['position_id' => $v['position_id']], 'pp.position_num,pg.garage_num', 0);
                    if (!empty($position_num)) {
                        $position_num = $position_num->toArray();
                        if (!empty($position_num)) {
                            $position_num1 = $position_num[0];
                            if (empty($position_num1['garage_num'])) {
                                $position_num1['garage_num'] = '临时车库';
                            }
                            $room1=[];
                            $number = $position_num1['position_num'];
                            $number_name = $position_num1['garage_num'];
                        }
                    }
                }elseif (!empty($v['room_id'])) {
                    $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
                    if (!empty($room)) {
                        $room = $room->toArray();
                        if (!empty($room)) {
                            $room1 = $room[0];
                            $number = $room1['room'];
                            $number_name = $room1['single_name'] . '(栋)';
                        }
                    }
                }
                if (!empty($v['summary_id'])){
                    $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
                    $summary_info= $db_house_new_pay_order_summary->getOne(['summary_id' => $v['summary_id']]);
                    if (!empty($summary_info)){
                        $v['order_serial']=$summary_info['paid_orderid'];
                        $v['order_no']=$summary_info['order_no'];
                    }
                }
                $admininfo = $db_admin->getOne(['id' => $v['role_id']]);
                $v['role_name'] = '';
                if ($admininfo && !$admininfo->isEmpty()) {
                    $admininfo = $admininfo->toArray();
                    if (!empty($admininfo['realname'])){
                        $v['role_name'] = $admininfo['realname'];
                    }else{
                        $v['role_name'] = $admininfo['account'];
                    }
                }

                $v['numbers'] = $number;
                if($exporttype=='exportRefund'){
                    $floor_name = isset($room1['floor_name']) ? $room1['floor_name'] . '(单元)' : '';
                    $layer_name = isset($room1['layer_name']) ? $room1['layer_name'] . '(层)' : '';
                    $data_list[$k]['full_number_name'] = $number_name.$floor_name.$layer_name.$v['numbers'];
                    $data_list[$k]['name'] = $v['name'];
                    $data_list[$k]['phone'] = $v['phone'];
                    $data_list[$k]['charge_number_name'] = $v['charge_number_name'];
                    $data_list[$k]['project_name'] = $v['project_name'];
                    $data_list[$k]['order_no'] = '';
                    //$data_list[$k]['order_serial'] = $v['order_serial'];
                    if (!empty($v['summary_id'])) {
                        $summary_info = $db_house_new_pay_order_summary->getOne(['summary_id' => $v['summary_id']]);
                        if (!empty($summary_info)) {
                            $data_list[$k]['order_no'] = $summary_info['order_no'];
                            //$data_list[$k]['order_serial'] = $summary_info['paid_orderid'];
                        }
                    }
                    $data_list[$k]['total_money'] = $v['total_money'];
                    $data_list[$k]['pay_money'] = $v['pay_money'];
                    $data_list[$k]['refund_money'] = $v['refund_money'];
                    if (!empty($v['pay_type'])) {
                        $data_list[$k]['pay_type'] = $this->pay_type[$v['pay_type']];
                    } else {
                        $data_list[$k]['pay_type'] = '无';
                    }
                    if (isset($summary_info) && isset($summary_info['pay_type']) && !empty($summary_info['pay_type'])) {
                        if ($summary_info['pay_type'] == 2 || $summary_info['pay_type'] == 22) {
                            $offline_pay_type = '';
                            if (!empty($payList) && !empty($summary_info['offline_pay_type'])) {
                                if(strpos($summary_info['offline_pay_type'],',')>0){
                                    $offline_pay_type_arr=explode(',',$summary_info['offline_pay_type']);
                                    foreach ($offline_pay_type_arr as $opay){
                                        if(isset($payList[$opay])){
                                            $offline_pay_type.=empty($offline_pay_type) ? $payList[$opay]:'、'.$payList[$opay];
                                        }
                                    }
                                }else{
                                    $offline_pay_type = isset($payList[$summary_info['offline_pay_type']]) ? $payList[$summary_info['offline_pay_type']]:'';
                                }

                            }

                            $data_list[$k]['pay_type'] = $this->pay_type[$summary_info['pay_type']] . '-' . $offline_pay_type;
                        } elseif (in_array($summary_info['pay_type'], [1, 3])) {
                            $data_list[$k]['pay_type'] = $this->pay_type[$summary_info['pay_type']];
                        } elseif ($summary_info['pay_type'] == 4) {
                            if (empty($summary_info['online_pay_type'])) {
                                $online_pay_type1 = '余额支付';
                            } else {
                                $online_pay_type1 = $this->pay_type_arr[$summary_info['online_pay_type']];
                            }
                            $data_list[$k]['pay_type'] = $this->pay_type[$summary_info['pay_type']] . '-' . $online_pay_type1;
                        }
                        unset($summary_info);
                    }
                    $data_list[$k]['pay_time'] = $v['pay_time'];
                    $data_list[$k]['update_time'] = $v['update_time']>1 ? date('Y-m-d H:i:s',$v['update_time']):'';
                    $data_list[$k]['record_status'] =$v['record_status'];
                    $data_list[$k]['order_status'] =$v['order_status'];
                    $data_list[$k]['refund_type'] = $v['refund_type'];
                    $data_list[$k]['number_name'] = $number_name;
                    $data_list[$k]['floor_name'] = isset($room1['floor_name']) ? $room1['floor_name'] . '(单元)' : '';
                    $data_list[$k]['layer_name'] = isset($room1['layer_name']) ? $room1['layer_name'] . '(层)' : '';
                    $data_list[$k]['numbers'] = $v['numbers'];
                    $data_list[$k]['modify_money'] = $v['modify_money'];
                    if (!empty($v['diy_type'])) {
                        $data_list[$k]['diy_type'] = $this->diy_type[$v['diy_type']];
                    } else {
                        $data_list[$k]['diy_type'] = '无';
                    }
                    $data_list[$k]['score_used_count'] = $v['score_used_count'];
                    $data_list[$k]['service_start_time'] = $v['service_start_time'];
                    $data_list[$k]['service_end_time'] = $v['service_end_time'];
                    $data_list[$k]['ammeter'] = $v['now_ammeter'] - $v['last_ammeter'];
                    $data_list[$k]['role_id'] = $v['role_name'];
                    $data_list[$k]['late_payment_day'] = $v['late_payment_day'];
                    $data_list[$k]['late_payment_money'] = $v['late_payment_money'];
                    $data_list[$k]['service_month_num'] = $v['service_month_num'];
                    $data_list[$k]['prepare_pay_money'] = $v['prepare_pay_money'];
                }else {
                    $data_list[$k]['number_name'] = $number_name;
                    $data_list[$k]['floor_name'] = isset($room1['floor_name']) ? $room1['floor_name'] . '(单元)' : '';
                    $data_list[$k]['layer_name'] = isset($room1['layer_name']) ? $room1['layer_name'] . '(层)' : '';
                    $data_list[$k]['numbers'] = $v['numbers'];
                    $data_list[$k]['name'] = $v['name'];
                    $data_list[$k]['phone'] = $v['phone'];
                    $data_list[$k]['charge_number_name'] = $v['charge_number_name'];
                    $data_list[$k]['project_name'] = $v['project_name'];
                    $data_list[$k]['order_no'] = '';
                    $data_list[$k]['order_serial'] = $v['order_serial'];
                    $remark = '';
                    if (!empty($v['summary_id'])) {
                        $summary_info = $db_house_new_pay_order_summary->getOne(['summary_id' => $v['summary_id']]);
                        if (!empty($summary_info)) {
                            $data_list[$k]['order_no'] = $summary_info['order_no'];
                            $data_list[$k]['order_serial'] = $summary_info['paid_orderid'];
                            $remark = $summary_info['remark'];
                        }
                    }
                    $data_list[$k]['total_money'] = $v['total_money'];
                    $data_list[$k]['modify_money'] = $v['modify_money'];
                    $data_list[$k]['pay_money'] = $v['pay_money'];
                    if (!empty($v['diy_type'])) {
                        $data_list[$k]['diy_type'] = $this->diy_type[$v['diy_type']];
                    } else {
                        $data_list[$k]['diy_type'] = '无';
                    }

                    $data_list[$k]['score_used_count'] = $v['score_used_count'];
                    $data_list[$k]['pay_time'] = $v['pay_time'];
                    if (!empty($v['pay_type'])) {
                        $data_list[$k]['pay_type'] = $this->pay_type[$v['pay_type']];
                    } else {
                        $data_list[$k]['pay_type'] = '无';
                    }
                    if (isset($summary_info['pay_type']) && !empty($summary_info['pay_type'])) {
                        if ($summary_info['pay_type'] == 2  || $summary_info['pay_type'] == 22) {
                            $offline_pay_type = '';
                            if (!empty($payList) && !empty($summary_info['offline_pay_type'])) {
                                if(strpos($summary_info['offline_pay_type'],',')>0){
                                    $offline_pay_type_arr=explode(',',$summary_info['offline_pay_type']);
                                    foreach ($offline_pay_type_arr as $opay){
                                        if(isset($payList[$opay])){
                                            $offline_pay_type.=empty($offline_pay_type) ? $payList[$opay]:'、'.$payList[$opay];
                                        }
                                    }
                                }else{
                                    $offline_pay_type = isset($payList[$summary_info['offline_pay_type']]) ? $payList[$summary_info['offline_pay_type']]:'';
                                }

                            }

                            $data_list[$k]['pay_type'] = $this->pay_type[$summary_info['pay_type']] . '-' . $offline_pay_type;
                        } elseif (in_array($summary_info['pay_type'], [1, 3])) {
                            $data_list[$k]['pay_type'] = $this->pay_type[$summary_info['pay_type']];
                        } elseif ($summary_info['pay_type'] == 4) {
                            if (empty($summary_info['online_pay_type'])) {
                                $online_pay_type1 = '余额支付';
                            } else {
                                $online_pay_type1 = $this->pay_type_arr[$summary_info['online_pay_type']];
                            }
                            $data_list[$k]['pay_type'] = $this->pay_type[$summary_info['pay_type']] . '-' . $online_pay_type1;
                        }
                        unset($summary_info);
                    }

                    $data_list[$k]['service_start_time'] = $v['service_start_time'];
                    $data_list[$k]['service_end_time'] = $v['service_end_time'];
                    $data_list[$k]['ammeter'] = $v['now_ammeter'] - $v['last_ammeter'];
                    $data_list[$k]['role_id'] = $v['role_name'];
                    $data_list[$k]['late_payment_day'] = $v['late_payment_day'];
                    $data_list[$k]['late_payment_money'] = $v['late_payment_money'];
                    $data_list[$k]['service_month_num'] = $v['service_month_num'];
                    $data_list[$k]['prepare_pay_money'] = $v['prepare_pay_money'];
                    $data_list[$k]['refund_money'] = $v['refund_money'];
                    $data_list[$k]['remark'] = (!empty($remark)) ? $remark : $v['remark'];
                    $data_list[$k]['record_status'] =$v['record_status'];
                    $data_list[$k]['print_number'] =$v['print_number'];
                }
                if(empty($digit_info)){
                    $data_list[$k]['late_payment_money']= formatNumber($data_list[$k]['late_payment_money'],2,1);
                    $data_list[$k]['prepare_pay_money']= formatNumber($data_list[$k]['prepare_pay_money'],2,1);
                    $data_list[$k]['refund_money']= formatNumber($data_list[$k]['refund_money'],2,1);
                    $data_list[$k]['total_money']= formatNumber($data_list[$k]['total_money'],2,1);
                    $data_list[$k]['modify_money']= formatNumber($data_list[$k]['modify_money'],2,1);
                    $data_list[$k]['pay_money']= formatNumber($data_list[$k]['pay_money'],2,1);

                    $data_list[$k]['pay_amount_points']=formatNumber($v['pay_amount_points']/100,2,1);
                    $data_list[$k]['system_balance']=formatNumber($v['system_balance'],2,1);
                    $data_list[$k]['score_deducte']=formatNumber($v['score_deducte'],2,1);


                }else{
                    if($v['order_type'] == 'water' || $v['order_type'] == 'electric' || $v['order_type'] == 'gas'){
                        $data_list[$k]['late_payment_money']= formatNumber($data_list[$k]['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['prepare_pay_money']= formatNumber($data_list[$k]['prepare_pay_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['refund_money']= formatNumber($data_list[$k]['refund_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['total_money']= formatNumber($data_list[$k]['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['modify_money']= formatNumber($data_list[$k]['modify_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['pay_money']= formatNumber($data_list[$k]['pay_money'],$digit_info['meter_digit'],$digit_info['type']);

                        $data_list[$k]['pay_amount_points']=formatNumber($v['pay_amount_points']/100,$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['system_balance']=formatNumber($v['system_balance'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['score_deducte']=formatNumber($v['score_deducte'],$digit_info['meter_digit'],$digit_info['type']);

                    }else{
                        $data_list[$k]['late_payment_money']= formatNumber($data_list[$k]['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['prepare_pay_money']= formatNumber($data_list[$k]['prepare_pay_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['refund_money']= formatNumber($data_list[$k]['refund_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['total_money']= formatNumber($data_list[$k]['total_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['modify_money']= formatNumber($data_list[$k]['modify_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['pay_money']= formatNumber($data_list[$k]['pay_money'],$digit_info['other_digit'],$digit_info['type']);

                        $data_list[$k]['pay_amount_points']=formatNumber($v['pay_amount_points']/100,$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['system_balance']=formatNumber($v['system_balance'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['score_deducte']=formatNumber($v['score_deducte'],$digit_info['other_digit'],$digit_info['type']);
                    }
                }
            }
        }
        $data = $count;
        if(empty($digit_info)){
            $count['total_money']= formatNumber($count['total_money'],2,1);
            $count['modify_money']= formatNumber($count['modify_money'],2,1);
            $count['pay_money']= formatNumber($count['pay_money'],2,1);
            $count['late_payment_money']= formatNumber($count['late_payment_money'],2,1);
            $count['prepare_pay_money']= formatNumber($count['prepare_pay_money'],2,1);
            $count['refund_money']= formatNumber($count['refund_money'],2,1);
        }else{
            $count['total_money']= formatNumber($count['total_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['modify_money']= formatNumber($count['modify_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['pay_money']= formatNumber($count['pay_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['late_payment_money']= formatNumber($count['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['prepare_pay_money']= formatNumber($count['prepare_pay_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['refund_money']= formatNumber($count['refund_money'],$digit_info['other_digit'],$digit_info['type']);
       }

        $data['total_money'] = $count['total_money'] . '元';
        $data['modify_money'] = $count['modify_money'] . '元';
        $data['pay_money'] = $count['pay_money'] . '元';
        $data['late_payment_money'] = $count['late_payment_money'] . '元';
        $data['prepare_pay_money'] = $count['prepare_pay_money'] . '元';
        $data['refund_money'] = $count['refund_money'] . '元';
        $data['list'] = $data_list;
        if($exporttype=='exportRefund'){
            $title = ['房间号/车位号', '缴费人', '电话', '所属收费科目', '收费项目名称', '订单编号', '应收费用（元）', '实际缴费金额', '退款金额', '支付方式', '支付时间', '退款时间','开票状态','账单状态','账单模式', '楼栋/车库', '单元', '楼层','房间/车位','修改后费用', '优惠方式', '积分使用数量', '计费开始时间', '计费结束时间', '使用电量', '收款人', '滞纳天数', '滞纳金费用（元）', '预缴周期', '预缴费用（元）','线上支付金额','余额支付金额','积分抵扣金额'];
        }else{
            $title = ['楼栋/车库', '单元', '楼层', '房间号/车位号', '业主名', '电话', '所属收费科目', '收费项目名称', '订单编号', '支付单号', '应收费用（元）', '修改后费用', '实际缴费金额', '优惠方式', '积分使用数量', '支付时间', '支付方式', '计费开始时间', '计费结束时间', '使用电量', '收款人', '滞纳天数', '滞纳金费用（元）', '预缴周期', '预缴费用（元）', '退款总金额', '备注','开票状态','打印编号','线上支付金额','余额支付金额','积分抵扣金额'];
        }
        $res = $this->saveExcel($title, $data, $filename . time(),$exporttype,$exportPattern);
        return $res;
    }

    /**
     *导出退款账单
     * @author: zhubaodi
     * @date : 2021/6/26
     */
    public function exportRefundOrders($where = [], $where1 = '', $field = true, $order = 'o.order_id DESC',$exporttype='',$exportPattern = 2)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village_detail_record = new HouseVillageDetailRecord();
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $db_house_new_offline_pay = new HouseNewOfflinePay();
        $print_number = new HouseVillagePrintTemplateNumber();
        $db_admin = new HouseAdmin();
        $db_house_new_pay_order_refund= new HouseNewPayOrderRefund();

        $where_summary = [];
        $record_status = 0;
        $village_id = 0;
        if (!empty($where)) {
            foreach ($where as $k => &$va) {
                if ($va[0] == 'record_status') {
                    $record_status = $va[2];
                    unset($where[$k]);
                }
                if ($va[0] == 'o.pay_type') {
                    $pay_type_arr =explode('-',$va[2]);
                    if ($pay_type_arr[0]==1){
                        $va[2]=1;
                        $where_summary[]=['pay_type','=',1];
                        unset($where[$k]);
                    }elseif($pay_type_arr[0]==2){
                        $where[]=['o.offline_pay_type','find in set',$pay_type_arr[1]];
                        $va[1]='in';
                        $va[2]=array(2,22);
                    }elseif($pay_type_arr[0]==4){
                        $va[2]=4;
                        $where_summary[]=['online_pay_type','=',$this->pay_type_arr1[$pay_type_arr[1]]];
                        $where_summary[]=['pay_type','=',4];
                        $is_online = 1;
                        unset($where[$k]);
                    }
                }
                if ($va[0] == 'o.village_id') {
                    $village_id = $va[2];
                }
            }
            $where = array_values($where);
        }
        $where_summary[] = ['is_paid', '=', 1];
        $where_summary[] = ['village_id', '=', $village_id];
        $summary_list = $db_house_new_pay_order_summary->getLists($where_summary, '*');
        if (!empty($summary_list)) {
            $summary_list = $summary_list->toArray();
            if (!empty($summary_list)) {
                foreach ($summary_list as $val) {
                    $summary_arr[]=$val['summary_id'];
                }
            }
        }
        if (isset($summary_arr)&&!empty($summary_arr)){
            $where[]=['o.summary_id','in',$summary_arr];
        }

        // 开票状态筛选
        $record = $db_house_village_detail_record->getList([['order_type','=',2]],'order_id');
        $record_order = [0];
        if(!empty($record)){
            $record_order = array_column($record,'order_id');
            // 开票
            if ($record_status == 1) {
                $where[]=['o.order_id','in',$record_order];
            } elseif ($record_status == 2) {
                // 未开票
                $where[]=['o.order_id','not in',$record_order];
            }
        }else{
            if (isset($record_status) && $record_status == 1) {
                $where[]=['o.order_id','in',$record_order];
            } elseif (isset($record_status) && $record_status == 2) {
                $where[]=['o.order_id','not in',$record_order];
            }
        }

        $list = $db_house_new_pay_order->getPayOrder($where, $where1, $field, $order);

        $count = [];
        $count['total_money'] = 0;
        $count['modify_money'] = 0;
        $count['pay_money'] = 0;
        $count['late_payment_money'] = 0;
        $count['prepare_pay_money'] = 0;
        $count['refund_money'] = 0;
        $filename='退款账单明细';

        if ($list) {
            $list= $list->toArray();
            if(empty($list)){
                throw new \think\Exception("暂无数据导出");
            }
            $where_pay = [];
            $pay_list = $db_house_new_offline_pay->getList($where_pay, '*');
            $payList = [];
            if (!empty($pay_list)) {
                $pay_list = $pay_list->toArray();
                if (!empty($pay_list)) {
                    foreach ($pay_list as $vv) {
                        $payList[$vv['id']] = $vv['name'];
                    }
                }
            }
            $data_list = [];
            $data_refund_list = [];

	    $digit_info=array();
            $kr=0;
            foreach ($list as $k => $v) {
                $count['total_money'] = $count['total_money'] + $v['total_money'];
                $count['modify_money'] = $count['modify_money'] + $v['modify_money'];
                $count['pay_money'] = $count['pay_money'] + $v['pay_money'];
                $count['late_payment_money'] = $count['late_payment_money'] + $v['late_payment_money'];
                $count['prepare_pay_money'] = $count['prepare_pay_money'] + $v['prepare_pay_money'];
                $count['refund_money'] = $count['refund_money'] + $v['refund_money'];
                if (isset($v['update_time']) && $v['update_time'] > 1) {
                    $v['updateTime'] = date('Y-m-d', $v['update_time']);
                }
                if (isset($v['pay_time']) && $v['pay_time'] > 1) {
                    $v['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                }
                if (isset($v['service_start_time']) && $v['service_start_time'] > 1) {
                    $v['service_start_time'] = date('Y-m-d', $v['service_start_time']);
                }else{
                    $v['service_start_time'] ='无';
                }
                if (isset($v['service_end_time']) && $v['service_end_time'] > 1) {
                    $v['service_end_time'] = date('Y-m-d', $v['service_end_time']);
                }else{
                    $v['service_end_time']='无';
                }
                if ($v['is_refund'] == 1) {
                    $v['order_status'] = '未退款';
                }elseif($v['refund_money'] >= $v['pay_money']){
                    $v['order_status'] = '已退款';
                } else {
                    $v['order_status'] = '部分退款';
                }
                if (empty($v['refund_type']) || ($v['refund_type']<1)) {
                    $v['refund_type'] ='无';
                }elseif($v['refund_type']==1){
                    $v['refund_type'] ='仅退款，不还原账单';
                }elseif($v['refund_type']==2){
                    $v['refund_type'] ='退款且还原账单';
                }
                $number = '';
                $number_name = '';
                if (!empty($v['position_id'])) {
                    $position_num = $db_house_village_parking_position->getLists(['position_id' => $v['position_id']], 'pp.position_num,pg.garage_num', 0);
                    if (!empty($position_num)) {
                        $position_num = $position_num->toArray();
                        if (!empty($position_num)) {
                            $position_num1 = $position_num[0];
                            if (empty($position_num1['garage_num'])) {
                                $position_num1['garage_num'] = '临时车库';
                            }
                            $room1=[];
                            $number = $position_num1['position_num'];
                            $number_name = $position_num1['garage_num'];
                        }
                    }
                }elseif (!empty($v['room_id'])) {
                    $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
                    if (!empty($room)) {
                        $room = $room->toArray();
                        if (!empty($room)) {
                            $room1 = $room[0];
                            $number = $room1['room'];
                            $number_name = $room1['single_name'] . '栋';
                        }
                    }
                }
                if (!empty($v['summary_id'])){
                    $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
                    $summary_info= $db_house_new_pay_order_summary->getOne(['summary_id' => $v['summary_id']]);
                    if (!empty($summary_info)){
                        $v['order_serial']=$summary_info['paid_orderid'];
                        $v['order_no']=$summary_info['order_no'];
                    }
                }
                $admininfo = $db_admin->getOne(['id' => $v['role_id']]);
                $v['role_name'] = '';
                if ($admininfo && !$admininfo->isEmpty()) {
                    $admininfo = $admininfo->toArray();
                    if (!empty($admininfo['realname'])){
                        $v['role_name'] = $admininfo['realname'];
                    }else{
                        $v['role_name'] = $admininfo['account'];
                    }
                }
                   $v['numbers'] = $number;

                    $data_list[$k]['number_name'] = $number_name;
                    $data_list[$k]['floor_name'] = isset($room1['floor_name']) ? $room1['floor_name'] . '单元' : '';
                    $data_list[$k]['layer_name'] = isset($room1['layer_name']) ? $room1['layer_name'] . '层' : '';
                    $data_list[$k]['numbers'] = $v['numbers'];
                    $data_list[$k]['name'] = $v['name'];
                    $data_list[$k]['phone'] = $v['phone'];
                    $data_list[$k]['charge_number_name'] = $v['charge_number_name'];
                    $data_list[$k]['project_name'] = $v['project_name'];
                    $data_list[$k]['order_no'] = '';
                    $data_list[$k]['order_serial'] = $v['order_serial'];
                    $remark = '';
                    if (!empty($v['summary_id'])) {
                        $summary_info = $db_house_new_pay_order_summary->getOne(['summary_id' => $v['summary_id']]);
                        if (!empty($summary_info)) {
                            $data_list[$k]['order_no'] = $summary_info['order_no'];
                            $data_list[$k]['order_serial'] = $summary_info['paid_orderid'];
                            $remark = $summary_info['remark'];
                        }
                    }
                    $data_list[$k]['total_money'] = $v['total_money'];
                    $data_list[$k]['modify_money'] = $v['modify_money'];
                    $data_list[$k]['pay_money'] = $v['pay_money'];
                    $data_list[$k]['pay_amount_points']=$v['pay_amount_points']/100;
                    $data_list[$k]['system_balance']=$v['system_balance'];
                    $data_list[$k]['score_deducte']=$v['score_deducte'];
                    $data_list[$k]['score_used_count'] = $v['score_used_count'];
                if (!empty($v['diy_type'])) {
                        $data_list[$k]['diy_type'] = $this->diy_type[$v['diy_type']];
                    } else {
                        $data_list[$k]['diy_type'] = '无';
                    }
                    $data_list[$k]['pay_time'] = $v['pay_time'];
                    if (!empty($v['pay_type'])) {
                        $data_list[$k]['pay_type'] = $this->pay_type[$v['pay_type']];
                    } else {
                        $data_list[$k]['pay_type'] = '无';
                    }
                    if (isset($summary_info['pay_type']) && !empty($summary_info['pay_type'])) {
                        if ($summary_info['pay_type'] == 2 || $summary_info['pay_type'] == 22) {
                            $offline_pay_type = '';
                            if (!empty($payList) && !empty($summary_info['offline_pay_type'])) {
                                if(strpos($summary_info['offline_pay_type'],',')>0){
                                    $offline_pay_type_arr=explode(',',$summary_info['offline_pay_type']);
                                    foreach ($offline_pay_type_arr as $opay){
                                        if(isset($payList[$opay])){
                                            $offline_pay_type.=empty($offline_pay_type) ? $payList[$opay]:'、'.$payList[$opay];
                                        }
                                    }
                                }else{
                                    $offline_pay_type = isset($payList[$summary_info['offline_pay_type']]) ? $payList[$summary_info['offline_pay_type']]:'';
                                }

                            }
                            $data_list[$k]['pay_type'] = $this->pay_type[$summary_info['pay_type']] . '-' . $offline_pay_type;
                        } elseif (in_array($summary_info['pay_type'], [1, 3])) {
                            $data_list[$k]['pay_type'] = $this->pay_type[$summary_info['pay_type']];
                        } elseif ($summary_info['pay_type'] == 4) {
                            if (empty($summary_info['online_pay_type'])) {
                                $online_pay_type1 = '余额支付';
                            } else {
                                $online_pay_type1 = $this->pay_type_arr[$summary_info['online_pay_type']];
                            }
                            $data_list[$k]['pay_type'] = $this->pay_type[$summary_info['pay_type']] . '-' . $online_pay_type1;
                        }
                        unset($summary_info);
                    }

                    $data_list[$k]['service_start_time'] = $v['service_start_time'];
                    $data_list[$k]['service_end_time'] = $v['service_end_time'];
                    $data_list[$k]['ammeter'] = $v['now_ammeter'] - $v['last_ammeter'];
                    $data_list[$k]['role_id'] = $v['role_name'];
                    $data_list[$k]['late_payment_day'] = $v['late_payment_day'];
                    $data_list[$k]['late_payment_money'] = $v['late_payment_money'];
                    $data_list[$k]['service_month_num'] = $v['service_month_num'];
                    $data_list[$k]['prepare_pay_money'] = $v['prepare_pay_money'];
                    $data_list[$k]['remark'] = (!empty($remark)) ? $remark : $v['remark'];
                    $data_list[$k]['refund_money'] = $v['refund_money'];
                if(empty($digit_info)){
                    $data_list[$k]['late_payment_money']= formatNumber($data_list[$k]['late_payment_money'],2,1);
                    $data_list[$k]['prepare_pay_money']= formatNumber($data_list[$k]['prepare_pay_money'],2,1);
                    $data_list[$k]['refund_money']= formatNumber($data_list[$k]['refund_money'],2,1);
                    $data_list[$k]['total_money']= formatNumber($data_list[$k]['total_money'],2,1);
                    $data_list[$k]['modify_money']= formatNumber($data_list[$k]['modify_money'],2,1);
                    $data_list[$k]['pay_money']= formatNumber($data_list[$k]['pay_money'],2,1);

                    $data_list[$k]['pay_amount_points']=formatNumber($data_list[$k]['pay_amount_points']/100,2,1);
                    $data_list[$k]['system_balance']=formatNumber($data_list[$k]['system_balance'],2,1);
                    $data_list[$k]['score_deducte']=formatNumber($data_list[$k]['score_deducte'],2,1);
                }else{
                    if($v['order_type'] == 'water' || $v['order_type'] == 'electric' || $v['order_type'] == 'gas'){
                        $data_list[$k]['late_payment_money']= formatNumber($data_list[$k]['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['prepare_pay_money']= formatNumber($data_list[$k]['prepare_pay_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['refund_money']= formatNumber($data_list[$k]['refund_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['total_money']= formatNumber($data_list[$k]['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['modify_money']= formatNumber($data_list[$k]['modify_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['pay_money']= formatNumber($data_list[$k]['pay_money'],$digit_info['meter_digit'],$digit_info['type']);

                        $data_list[$k]['pay_amount_points']=formatNumber($data_list[$k]['pay_amount_points']/100,$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['system_balance']=formatNumber($data_list[$k]['system_balance'],$digit_info['meter_digit'],$digit_info['type']);
                        $data_list[$k]['score_deducte']=formatNumber($data_list[$k]['score_deducte'],$digit_info['meter_digit'],$digit_info['type']);

                    }else{
                        $data_list[$k]['late_payment_money']= formatNumber($data_list[$k]['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['prepare_pay_money']= formatNumber($data_list[$k]['prepare_pay_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['refund_money']= formatNumber($data_list[$k]['refund_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['total_money']= formatNumber($data_list[$k]['total_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['modify_money']= formatNumber($data_list[$k]['modify_money'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['pay_money']= formatNumber($data_list[$k]['pay_money'],$digit_info['other_digit'],$digit_info['type']);

                        $data_list[$k]['pay_amount_points']=formatNumber($data_list[$k]['pay_amount_points']/100,$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['system_balance']=formatNumber($data_list[$k]['system_balance'],$digit_info['other_digit'],$digit_info['type']);
                        $data_list[$k]['score_deducte']=formatNumber($data_list[$k]['score_deducte'],$digit_info['other_digit'],$digit_info['type']);
                    }
                }
                $refund_list=$db_house_new_pay_order_refund->getList(['order_id'=>$v['order_id']]);
                if(!empty($refund_list)){
                    $refund_list=$refund_list->toArray();
                    if(!empty($refund_list)){
                        foreach ($refund_list as $vr){
                            $data_refund_list[$kr]['order_id']=$v['order_id'];
                            $data_refund_list[$kr]['number_name']=$data_list[$k]['number_name'];
                            $data_refund_list[$kr]['floor_name']=$data_list[$k]['floor_name'];
                            $data_refund_list[$kr]['layer_name']=$data_list[$k]['layer_name'];
                            $data_refund_list[$kr]['numbers']=$data_list[$k]['numbers'];
                            $data_refund_list[$kr]['name']=$data_list[$k]['name'];
                            $data_refund_list[$kr]['phone']=$data_list[$k]['phone'];
                            $data_refund_list[$kr]['charge_number_name']=$data_list[$k]['charge_number_name'];
                            $data_refund_list[$kr]['project_name']=$data_list[$k]['project_name'];
                            $data_refund_list[$kr]['order_no']=$data_list[$k]['order_no'];
                            $data_refund_list[$kr]['order_serial']=$data_list[$k]['order_serial'];
                            $data_refund_list[$kr]['total_money']=$data_list[$k]['total_money'];
                            $data_refund_list[$kr]['modify_money']=$data_list[$k]['modify_money'];
                            $data_refund_list[$kr]['pay_money']=$data_list[$k]['pay_money'];
                            $data_refund_list[$kr]['pay_amount_points']=$data_list[$k]['pay_amount_points'];
                            $data_refund_list[$kr]['system_balance']=$data_list[$k]['system_balance'];
                            $data_refund_list[$kr]['score_deducte']=$data_list[$k]['score_deducte'];
                            $data_refund_list[$kr]['score_used_count']=$data_list[$k]['score_used_count'];
                            $data_refund_list[$kr]['diy_type']=$data_list[$k]['diy_type'];
                            $data_refund_list[$kr]['pay_time']=$data_list[$k]['pay_time'];
                            $data_refund_list[$kr]['pay_type']=$data_list[$k]['pay_type'];
                            $data_refund_list[$kr]['service_start_time']=$data_list[$k]['service_start_time'];
                            $data_refund_list[$kr]['service_end_time']=$data_list[$k]['service_end_time'];
                            $data_refund_list[$kr]['ammeter']=$data_list[$k]['ammeter'];
                            $data_refund_list[$kr]['role_id']=$data_list[$k]['role_id'];
                            $data_refund_list[$kr]['late_payment_day']=$data_list[$k]['late_payment_day'];
                            $data_refund_list[$kr]['late_payment_money']=$data_list[$k]['late_payment_money'];
                            $data_refund_list[$kr]['service_month_num']=$data_list[$k]['service_month_num'];
                            $data_refund_list[$kr]['prepare_pay_money']=$data_list[$k]['prepare_pay_money'];
                            $data_refund_list[$kr]['remark']=$data_list[$k]['remark'];
                            $data_refund_list[$kr]['refund_type']=$v['refund_type'];
                            $data_refund_list[$kr]['refund_money']=$data_list[$k]['refund_money'];

                            if(empty($digit_info)){
                                $data_refund_list[$kr]['refund_online_money']=formatNumber($vr['refund_online_money'],2,1);
                                $data_refund_list[$kr]['refund_balance_money']=formatNumber($vr['refund_balance_money'],2,1);
                                $data_refund_list[$kr]['refund_score_money']=formatNumber($vr['refund_score_money'],2,1);
                            }else{
                                if($v['order_type'] == 'water' || $v['order_type'] == 'electric' || $v['order_type'] == 'gas'){
                                    $data_refund_list[$kr]['refund_online_money']=formatNumber($vr['refund_online_money'],$digit_info['meter_digit'],$digit_info['type']);
                                    $data_refund_list[$kr]['refund_balance_money']=formatNumber($vr['refund_balance_money'],$digit_info['meter_digit'],$digit_info['type']);
                                    $data_refund_list[$kr]['refund_score_money']=formatNumber($vr['refund_score_money'],$digit_info['meter_digit'],$digit_info['type']);

                                }else{
                                    $data_refund_list[$kr]['refund_online_money']=formatNumber($vr['refund_online_money'],$digit_info['other_digit'],$digit_info['type']);
                                    $data_refund_list[$kr]['refund_balance_money']=formatNumber($vr['refund_balance_money'],$digit_info['other_digit'],$digit_info['type']);
                                    $data_refund_list[$kr]['refund_score_money']=formatNumber($vr['refund_score_money'],$digit_info['other_digit'],$digit_info['type']);
                                }
                            }
                            $data_refund_list[$kr]['refund_score_count']=formatNumber($vr['refund_score_count'],2,1);
                            $data_refund_list[$kr]['refund_reason']=$vr['refund_reason'];
                            $data_refund_list[$kr]['add_time']=date('Y-m-d H:i:s',$vr['add_time']);
                            $kr++;
                        }
                    }
                }


            }
        }
      //  print_r($data_refund_list);exit;
        $data = $count;
        if(empty($digit_info)){
            $count['total_money']= formatNumber($count['total_money'],2,1);
            $count['modify_money']= formatNumber($count['modify_money'],2,1);
            $count['pay_money']= formatNumber($count['pay_money'],2,1);
            $count['late_payment_money']= formatNumber($count['late_payment_money'],2,1);
            $count['prepare_pay_money']= formatNumber($count['prepare_pay_money'],2,1);
            $count['refund_money']= formatNumber($count['refund_money'],2,1);
        }else{
            $count['total_money']= formatNumber($count['total_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['modify_money']= formatNumber($count['modify_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['pay_money']= formatNumber($count['pay_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['late_payment_money']= formatNumber($count['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['prepare_pay_money']= formatNumber($count['prepare_pay_money'],$digit_info['other_digit'],$digit_info['type']);
            $count['refund_money']= formatNumber($count['refund_money'],$digit_info['other_digit'],$digit_info['type']);
        }

        $data['total_money'] = $count['total_money'] . '元';
        $data['modify_money'] = $count['modify_money'] . '元';
        $data['pay_money'] = $count['pay_money'] . '元';
        $data['late_payment_money'] = $count['late_payment_money'] . '元';
        $data['prepare_pay_money'] = $count['prepare_pay_money'] . '元';
        $data['refund_money'] = $count['refund_money'] . '元';
        $data['list'] = $data_refund_list;
        $title = ['楼栋/车库', '单元', '楼层', '房间号/车位号', '业主名', '电话', '所属收费科目', '收费项目名称', '订单编号', '支付单号', '应收费用（元）', '修改后费用', '实际缴费金额','线上支付金额','余额支付金额','积分抵扣金额','积分使用数量','优惠方式', '支付时间', '支付方式', '计费开始时间', '计费结束时间', '用量', '收款人', '滞纳天数', '滞纳金费用（元）', '预缴周期', '预缴费用（元）','备注','退款模式','退款总金额','线上支付退款金额','余额支付退款金额','积分抵扣退款金额','退还积分数量','退款原因','退款时间'];
        $res = $this->saveExcel($title, $data, $filename . time(),$exporttype,$exportPattern);
        return $res;
    }


    /**
     * 发送公众号缴费通知
     * @param int $user_id
     * @param string $href
     * @param string $address
     * @param string $property_name
     * @param float $total_money
     * @author lijie
     * @date_time 2021/06/26
     */
    public function sendCashierMessage($user_id = 0, $href = '', $address = '', $property_name = '', $total_money = 0.00)
    {
        $templateNewsService = new TemplateNewsService();
        $db_user = new User();
        $user_info = $db_user->getOne(['uid' => $user_id]);
        if (!empty($user_info)) {
            $datamsg = [
                'tempKey' => 'TM01008',
                'dataArr' => [
                    'href' => $href,
                    'wecha_id' => $user_info['openid'],
                    'first' => ' 尊敬的业主，您有新的账单！',
                    'keynote2' => $address,
                    'keynote1' => $property_name,
                    'remark' => '您的待缴总额为：[' . $total_money . ']，点击缴费！'
                ]
            ];
            //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
        }
    }

    /**
     * 导出文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 14:44
     */
    public function saveOrderExcel($title, $data, $fileName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(20);
        $sheet->getRowDimension('1')->setRowHeight(25);//设置行高
        $sheet->getStyle('A1:N1')->getFont()->setBold(true)->setSize(12);
        $sheet->mergeCells('A1:N1'); //合并单元格
        $sheet->mergeCells('A2:N2'); //合并单元格
        $sheet->setCellValue('A1', '应收账单明细表');
        $sheet->setCellValue('A2', $data['total_money']);

        //设置单元格内容
        $titCol = 'A';
        foreach ($title as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '3', $value);
            $titCol++;
        }

        //设置单元格内容
        $row = 4;
        foreach ($data['list'] as $k => $item) {

            $dataCol = 'A';
            foreach ($item as $value) {
                //单元格内容写入
                $sheet->setCellValue($dataCol . $row, $value);
                $dataCol++;
            }
            $row1 = $row - 1;
            if ($k > 0 && ($data['list'][$k]['numbers'] == $data['list'][$k - 1]['numbers'])) {
                $sheet->mergeCells('A' . $row1 . ':' . 'A' . $row); //合并单元格
                $sheet->mergeCells('B' . $row1 . ':' . 'B' . $row); //合并单元格
                $sheet->mergeCells('C' . $row1 . ':' . 'C' . $row); //合并单元格
                $sheet->mergeCells('D' . $row1 . ':' . 'D' . $row); //合并单元格
                $sheet->mergeCells('E' . $row1 . ':' . 'E' . $row); //合并单元格
                $sheet->mergeCells('F' . $row1 . ':' . 'F' . $row); //合并单元格
            }
            if ($k > 0 && ($data['list'][$k]['numbers'] == $data['list'][$k - 1]['numbers']) && ($data['list'][$k]['charge_number_name'] == $data['list'][$k - 1]['charge_number_name'])) {
                $sheet->mergeCells('G' . $row1 . ':' . 'G' . $row); //合并单元格
            }
            if ($k > 0 && ($data['list'][$k]['numbers'] == $data['list'][$k - 1]['numbers']) && ($data['list'][$k]['charge_number_name'] == $data['list'][$k - 1]['charge_number_name']) && ($data['list'][$k]['project_name'] == $data['list'][$k - 1]['project_name'])) {
                $sheet->mergeCells('H' . $row1 . ':' . 'H' . $row); //合并单元格
            }
            $row++;
        }
        //保存

        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle('A1:N1' . $total_rows)->applyFromArray($styleArrayBody);
        $sheet->getStyle('A3:N' . $total_rows)->applyFromArray($styleArrayBody);

        $styleArrayBody1 = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        //添加所有边框/居中
        $sheet->getStyle('A2:N2' . $total_rows)->applyFromArray($styleArrayBody1);
        //下载
        $filename = $fileName . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $this->downloadExportFile($filename);
    }

    /**
     * 导出应收账单
     * @author: zhubaodi
     * @date : 2021/6/26
     */
    public function printOrder($where = [], $field = true, $order = 'o.order_id DESC')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $list = $db_house_new_pay_order->getPayOrder($where, '', $field, $order);
        $count = [];
        $count['total_money'] = 0;
        if ($list) {
            $data_list = [];
            foreach ($list as $k => $v) {
                $count['total_money'] = $count['total_money'] + $v['total_money'];
                if (isset($v['service_start_time'])) {
                    if ($v['service_start_time']>1)
                        $v['service_start_time_txt'] = date('Y-m-d H:i:s', $v['service_start_time']);
                    else
                        $v['service_start_time_txt'] = '--';
                }
                if (isset($v['service_end_time'])) {
                    if ($v['service_end_time']>1)
                        $v['service_end_time_txt'] = date('Y-m-d H:i:s', $v['service_end_time']);
                    else
                        $v['service_end_time_txt'] = '--';
                }
                if (isset($v['last_ammeter'])) {
                    if (!$v['last_ammeter'])
                        $v['last_ammeter'] = '--';
                }
                if (isset($v['now_ammeter'])) {
                    if (!$v['now_ammeter'])
                        $v['now_ammeter'] = '--';
                }
                $number = '';
                $number_name = '';
                if (!empty($v['room_id'])) {
                    $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
                    if (!empty($room)) {
                        $room = $room->toArray();
                        if (!empty($room)) {
                            $room1 = $room[0];
                            $number = $room1['room'];
                            $number_name = $room1['single_name'] . '(栋)';
                        }
                    }
                } elseif (!empty($v['position_id'])) {
                    $position_num = $db_house_village_parking_position->getLists(['position_id' => $v['position_id']], 'pp.position_num,pg.garage_num', 0);
                    if (!empty($position_num)) {
                        $position_num = $position_num->toArray();
                        if (!empty($position_num)) {
                            $position_num1 = $position_num[0];
                            if (empty($position_num1['garage_num'])) {
                                $position_num1['garage_num'] = '临时车库';
                            }
                            $number = $position_num1['position_num'];
                            $number_name = $position_num1['garage_num'];
                        }
                    }
                }
                $v['numbers'] = $number;

                $data_list[$k]['number_name'] = $number_name;
                $data_list[$k]['floor_name'] = isset($room1['floor_name']) ? $room1['floor_name'] . '(单元)' : '';
                $data_list[$k]['layer_name'] = isset($room1['layer_name']) ? $room1['layer_name'] . '(层)' : '';
                $data_list[$k]['numbers'] = $v['numbers'];
                $data_list[$k]['name'] = $v['name'];
                $data_list[$k]['phone'] = $v['phone'];
                $data_list[$k]['charge_number_name'] = $v['charge_number_name'];
                $data_list[$k]['project_name'] = $v['project_name'];
                $data_list[$k]['charge_name'] = $v['charge_name'];
                $data_list[$k]['total_money'] = $v['total_money'];
                $data_list[$k]['service_start_time'] = $v['service_start_time_txt'];
                $data_list[$k]['service_end_time'] = $v['service_end_time_txt'];
                $data_list[$k]['last_ammeter'] = $v['last_ammeter'];
                $data_list[$k]['now_ammeter'] = $v['now_ammeter'];
            }
        }
        //   print_r($data_list);exit;
        $data['total_money'] = '应收总费用：' . $count['total_money'] . '元';
        $data['list'] = $data_list;
        //楼栋/车库	单元	楼层	房间号/车位号	业主名	电话	所属收费科目	收费项目名称	收费标准名称	应收费用（元）	计费开始时间	计费结束时间	上次度数	本次度数
        $title = ['楼栋/车库', '单元', '楼层', '房间号/车位号', '业主名', '电话', '所属收费科目', '收费项目名称', '收费标准名称', '应收费用（元）', '计费开始时间', '计费结束时间', '上次度数', '本次度数'];
        $res = $this->saveOrderExcel($title, $data, '应收账单明细表' . time());
        return $res;
    }

    /**
     * 导出应收账单明细
     * @author lijie
     * @date_time 2021/12/28
     * @param array $data
     * @param string $title
     * @param string $fileName
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\Exception
     */
    public function receivableOrderImport($data=[],$title='',$fileName='')
    {
        if(empty($data) || empty($title) || empty($fileName)){
            throw new \think\Exception("无数据！");
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(20);
        $sheet->getRowDimension('1')->setRowHeight(25);//设置行高
        $sheet->getStyle('A1:K1')->getFont()->setBold(true)->setSize(12);
        $sheet->mergeCells('A1:K1'); //合并单元格
        $sheet->setCellValue('A1', '应收账单明细表');

        //设置单元格内容
        $titCol = 'A';
        foreach ($title as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '2', $value);
            $titCol++;
        }
        //设置单元格内容
        $row = 3;
        foreach ($data as $k => $item) {
            //单元格内容写入
            $sheet->setCellValue('A' . $row, $item['number']);
            $sheet->setCellValue('B' . $row, $item['name']);
            $sheet->setCellValue('C' . $row, $item['phone']);
            $sheet->setCellValue('D' . $row, $item['charge_name']);
            $sheet->setCellValue('E' . $row, $item['project_name']);
            $sheet->setCellValue('F' . $row, $item['charge_number_name']);
            $sheet->setCellValue('G' . $row, $item['total_money']);
            $sheet->setCellValue('H' . $row, $item['service_start_time_txt']);
            $sheet->setCellValue('I' . $row, $item['service_end_time_txt']);
            $sheet->setCellValue('J' . $row, $item['last_ammeter']);
            $sheet->setCellValue('K' . $row, $item['now_ammeter']);
            $row++;
        }
        //保存
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle('A1:K1' . $total_rows)->applyFromArray($styleArrayBody);

        $styleArrayBody1 = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        //下载
        $filename = $fileName . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $this->downloadExportFile($filename);
    }


    /**
     * 查询打印模板
     * @author:zhubaodi
     * @date_time: 2021/6/28 9:58
     */
    public function getPrintTemplate($village_id)
    {
        if (empty($village_id)) {
            throw new \think\Exception("小区id不能为空！");
        }
        $db_template = new HouseVillagePrintTemplate();
        $list = $db_template->getList(['village_id' => $village_id]);
        $village_info=(new HouseVillageInfo())->getOne(['village_id'=>$village_id],'print_template_id');
        $template_id=0;
        if($village_info && $village_info['print_template_id']){
            $template_info=$db_template->get_one(['template_id' => $village_info['print_template_id']],'template_id');
            if($template_info && !$template_info->isEmpty()){
                $template_id=$template_info['template_id'];
            }
        }
        return ['list'=>$list,'template_id'=>$template_id];

    }

    public function getPrintInfo($village_id, $order_id, $template_id,$pigcms_id=0,$choice_ids=[])
    {
        if (empty($village_id)) {
            throw new \think\Exception("小区id不能为空！");
        }
        if (empty($order_id) && empty($choice_ids)) {
            throw new \think\Exception("订单id不能为空！");
        }
        if (empty($template_id)) {
            throw new \think\Exception("打印模板id不能为空！");
        }
        $db_printCustom = new HouseVillagePrintCustom();
        $db_printCustomConfig = new HouseVillagePrintCustomConfigure();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_new_charge_project = new HouseNewChargeProject();
        $db_charge_rule = new HouseNewChargeRule();
        $db_house_admin = new HouseAdmin();
        $db_template = new HouseVillagePrintTemplate();
        $db_house_village_user_bind = new HouseVillageUserBind();
        $db_house_village = new HouseVillage();
        $db_house_new_offline_pay = new HouseNewOfflinePay();
        $db_house_property_digit_service = new HousePropertyDigitService();
        $ser_print_template = new PrintTemplateService();
        $db_standard_bind = new HouseNewChargeStandardBind();
        $db_admin = new HouseAdmin();
        $templateInfo = $db_template->get_one(['village_id' => $village_id, 'template_id' => $template_id]);
        if (empty($templateInfo)) {
            throw new \think\Exception("打印模板信息不存在！");
        }

        $printCustomList = [];
        if(!empty($templateInfo['custom_field'])){
            // 模板字段
            $template_field = json_decode($templateInfo['custom_field'],true);
            foreach ($template_field as $v){
                if(is_numeric($v['id'])){
                    $printCustomList[] = $db_printCustomConfig->getOne(['configure_id' => $v['id']],'configure_id,field_name,title,type');
                }else{
                    $printCustomList[] = [
                        'configure_id' => 0,
                        'field_name' => '',
                        'title' => $v['title'],
                        'type' => $v['print_type']
                    ];
                }
            }
        }else{
            // 兼容老的打印模板 custom_field没有数据的情况
            $printCustomList = $db_printCustom->getLists(['c.template_id' => $template_id, 'c.village_id' => $village_id], 'c.*,b.*',0,0,'b.weight DESC,c.id ASC');
            if($printCustomList && !$printCustomList->isEmpty()){
                $printCustomList = $printCustomList->toArray();
            }else{
                $printCustomList = [];
            }
        }

        if (empty($templateInfo)) {
            throw new \think\Exception("打印模板详情不存在！");
        }

        //小区信息
        $village_info = $db_house_village->getOne($village_id, 'village_name,property_id');
        $role_name = '';
        $property_info=(new HousePropertyService())->getFind(['id'=>$village_info['property_id']],'property_name');
        if($property_info){
            $role_name=$property_info['property_name'];
        }
        $user_info=array();
        if(!empty($choice_ids)){
            $arr2 = array_unique(array_column($choice_ids, 'pigcms_id'));
            $orderid2 = array_unique(array_column($choice_ids, 'orderid'));
            $roomIdArr = array_unique(array_column($choice_ids, 'room_id'));
            if(empty($arr2) ){
                throw new \think\Exception("查询数据不存在！");
            }
            if(count($arr2) > 1 ){
                throw new \think\Exception("当前仅支持同一个缴费人进行批量打印已缴账单！");
            }
            if(empty($arr2[0]) && count($roomIdArr) > 1 ){
                throw new \think\Exception("不同房间不支持批量打印已缴账单！");
            }
            $pigcms_id_tmp=$arr2[0];
            if($pigcms_id_tmp>0){
                $user_info_obj = $db_house_village_user_bind->getOne(['pigcms_id' =>$pigcms_id_tmp]);
                if($user_info_obj && !$user_info_obj->isEmpty()){
                    $user_info = $user_info_obj->toArray();
                }
            }
            $where[] = ['order_id','in',$orderid2];
            $order_ids = $orderid2;
        }
        else{
            $where[] = ['order_id', '=', $order_id];

            if($pigcms_id>0){
                $user_info_obj = $db_house_village_user_bind->getOne(['pigcms_id' => $pigcms_id]);
                if($user_info_obj && !$user_info_obj->isEmpty()){
                    $user_info = $user_info_obj->toArray();
                }
            }
            $order_ids = [$order_id];
        }

        // 是否允许打印
        $res = $this->isAllowPrint($village_id,$template_id,$order_ids);
        if($res == 1){
            throw new Exception('有账单已经打印，不能再单条打印');
        }elseif ($res == 2){
            throw new Exception('有账单已经打印，不能再合并打印');
        }

        $field='o.score_used_count,o.score_deducte,o.order_id,o.pigcms_id,o.project_id,o.village_id,o.order_type,o.total_money,o.unit_price,o.room_id,o.position_id,o.pay_type,o.service_month_num,o.property_id,o.pay_money,o.offline_pay_type,o.refund_money,o.pay_time,o.pay_bind_name,s.remark,o.service_start_time,o.service_end_time,o.diy_content,p.name as order_name,p.type,r.bill_create_set,r.measure_unit,s.online_pay_type,o.last_ammeter,o.now_ammeter,o.meter_reading_id,o.late_payment_money,o.from,o.role_id,o.rule_id,r.charge_name';
        $orderList = $db_house_new_pay_order->getOrderList($where,$field);
        if($orderList){
            $orderList=$orderList->toArray();
        }
        if(empty($orderList)){
            throw new \think\Exception("订单不存在！");
        }
        $time=time();
        $realMoney=[];//实付金额
        $order_body=[];//表格区
        $pay_type=[];//收款方式
        $pay_time=[];//收款日期
        $remark=[];//备注
        $money = []; // 应收金额
        $late_payment_money = []; // 滞纳金
        $role_id = []; // 操作人ID 新版打印 收款人名称取操作人名称
        $is_water_electric_gas=0;
        $room_id = 0;
        $payee_name='';
        $max_print_num=0;
        $db_house_village_detail_record = new HouseVillageDetailRecord();
        $property_service_end_time=0;
        foreach ($orderList as $k=>$v){
            $print_numArr=$db_house_village_detail_record->getOneOrder([['order_id','=',$v['order_id']]],'print_num');
            if($print_numArr && ($print_numArr['print_num']>$max_print_num)){
                $max_print_num=$print_numArr['print_num'];
            }
            $room_num = '';
            $number ='无';
            $last_ammeter='';
            $now_ammeter='';
            if(empty($user_info) && $v['pigcms_id']>0){
                $user_info_obj = $db_house_village_user_bind->getOne(['pigcms_id' => $v['pigcms_id']]);
                if($user_info_obj && !$user_info_obj->isEmpty()){
                    $user_info = $user_info_obj->toArray();
                }
            }
            if (in_array($v['order_type'], ['water', 'electric', 'gas'])) {
                $number = round($v["total_money"] / $v["unit_price"], 2);
                if ($v['order_type'] == 'electric') {
                    $last_ammeter='0 度';
                    $now_ammeter='0 度';
                    if ($v['last_ammeter'] > 0) {
                        $last_ammeter = $v['last_ammeter'] . ' 度';
                    }
                    if ($v['now_ammeter'] > 0) {
                        $now_ammeter = $v['now_ammeter'] . ' 度';
                    }
                } elseif (in_array($v['order_type'], ['water', 'gas'])) {
                    $last_ammeter='0 m³';
                    $now_ammeter='0 m³';
                    if ($v['last_ammeter'] > 0) {
                        $last_ammeter = $v['last_ammeter'] . ' m³';
                    }
                    if ($v['now_ammeter'] > 0) {
                        $now_ammeter = $v['now_ammeter'] . ' m³';
                    }
                }
                $is_water_electric_gas=1;
            }

            // 收费规则 数量
            $rule_info = $db_charge_rule->getOne([['id','=',$v['rule_id']]],'fees_type');
            if($rule_info && !$rule_info->isEmpty()){
                $rule_info = $rule_info->toArray();
                if($rule_info['fees_type'] == 2){
                    $where_standard = [];
                    $where_standard[] = ['rule_id','=',$v['rule_id']];
                    if($v['room_id']){
                        $where_standard[] = ['vacancy_id','=',$v['room_id']];
                        $where_standard[] = ['bind_type','=',1];
                        $standard_info = $db_standard_bind->getOne($where_standard,'custom_value');
                        if($standard_info && !$standard_info->isEmpty()){
                            $standard_info = $standard_info->toArray();
                            $number = $standard_info['custom_value'];
                        }
                    }
                    if($v['position_id']){
                        $where_standard = [];
                        $where_standard[] = ['rule_id','=',$v['rule_id']];
                        $where_standard[] = ['position_id','=',$v['position_id']];
                        $where_standard[] = ['bind_type','=',1];
                        $standard_info = $db_standard_bind->getOne($where_standard,'custom_value');
                        if($standard_info && !$standard_info->isEmpty()){
                            $standard_info = $standard_info->toArray();
                            $number = $standard_info['custom_value'];
                        }
                    }
                }
            }

            if (!empty($v['room_id'])) {
                $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
                if (!empty($room)) {
                    $room = $room->toArray();
                    if (!empty($room)) {
                        if (!empty($room)) {
                            $room1 = $room[0];
                            $room_num=(new HouseVillageService())->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$v['village_id']);
                            /*if ($v['order_type']=='property'&&!empty($room1['housesize'])){
                                $number=$room1['housesize'].' ㎡';
                            }*/
                        }
                    }
                }
            }
            elseif (!empty($v['position_id'])) {
                $position_num = $db_house_village_parking_position->getLists(['position_id' => $v['position_id']], 'pp.position_num,pg.garage_num', 0);
                if (!empty($position_num)) {
                    $position_num = $position_num->toArray();
                    if (!empty($position_num)) {
                        $position_num1 = $position_num[0];
                        if (empty($position_num1['garage_num'])) {
                            $position_num1['garage_num'] = '临时车库';
                        }
                        $room_num = $position_num1['garage_num'] . $position_num1['position_num'];
                    }
                }
            }

            if (!empty($v['pay_type'])) {
                if (in_array($v['pay_type'], [2, 22])) {
                    $offline_pay_type='';
                    if(strpos($v['offline_pay_type'],',')>0){
                        $offline_pay_type_arr=explode(',',$v['offline_pay_type']);
                        $where_pay_arr=array();
                        $where_pay_arr[]=['id','in',$offline_pay_type_arr];
                        $pay_list = $db_house_new_offline_pay->getList($where_pay_arr, '*');
                        $payList = [];
                        if (!empty($pay_list)) {
                            $pay_list = $pay_list->toArray();
                            if (!empty($pay_list)) {
                                foreach ($pay_list as $vv) {
                                    $payList[$vv['id']] = $vv['name'];
                                }
                            }
                        }
                        foreach ($offline_pay_type_arr as $opay){
                            if(isset($payList[$opay])){
                                $offline_pay_type.=empty($offline_pay_type) ? $payList[$opay]:'、'.$payList[$opay];
                            }
                        }

                    }else{
                        $offline_pay = $db_house_new_offline_pay->get_one(['id'=>$v['offline_pay_type']]);
                        if (!empty($offline_pay) && !$offline_pay->isEmpty()){
                            $offline_pay_type=$offline_pay['name'];
                        }
                    }
                    $pay_type[] = $this->pay_type[$v['pay_type']] . '-' . $offline_pay_type;
                }
                elseif (in_array($v['pay_type'], [1, 3])) {
                    $pay_type[] = $this->pay_type[$v['pay_type']];
                }
                elseif ($v['pay_type'] == 4) {
                    if (empty($v['online_pay_type'])) {
                        $online_pay_type1 = '余额支付';
                    } else {
                        $online_pay_type1 = $this->pay_type_arr[$v['online_pay_type']];
                    }
                    $pay_type[] = $this->pay_type[$v['pay_type']] . '-' . $online_pay_type1;
                }
            }

            if (!empty($v['service_month_num'])){
                if ($v['bill_create_set']==1){
                    $v['service_month_num']=$v['service_month_num'].'天';
                }elseif ($v['bill_create_set']==2){
                    $v['service_month_num']=$v['service_month_num'].'个月';
                }elseif ($v['bill_create_set']==3){
                    $v['service_month_num']=$v['service_month_num'].'年';
                }else{
                    $v['service_month_num']=$v['service_month_num'].'';
                }
            }


	    $digit_info=array();
            $v['score_used_count']=formatNumber($v['score_used_count'],2,1);
            if(1||empty($digit_info)){
                $real_money=formatNumber($v['pay_money']-$v['refund_money'],2,1);
                $v['total_money']=formatNumber($v['total_money'],2,1);
                $v['pay_money']=formatNumber($v['pay_money'],2,1);
                $v['score_deducte']=formatNumber($v['score_deducte'],2,1);

            }
            else{
                if($v['order_type'] == 'water' || $v['order_type'] == 'electric' || $v['order_type'] == 'gas'){
                    $real_money=formatNumber($v['pay_money']-$v['refund_money'],$digit_info['meter_digit'],$digit_info['type']);
                    $v['total_money']=formatNumber($v['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                    $v['pay_money']=formatNumber($v['pay_money'],$digit_info['meter_digit'],$digit_info['type']);

                    $v['score_deducte']=formatNumber($v['score_deducte'],$digit_info['meter_digit'],$digit_info['type']);

                }else{
                    $real_money=formatNumber($v['pay_money']-$v['refund_money'],$digit_info['other_digit'],$digit_info['type']);
                    $v['total_money']=formatNumber($v['total_money'],$digit_info['other_digit'],$digit_info['type']);
                    $v['pay_money']=formatNumber($v['pay_money'],$digit_info['other_digit'],$digit_info['type']);

                    $v['score_deducte']=formatNumber($v['score_deducte'],$digit_info['other_digit'],$digit_info['type']);

                }
            }

            $project_info=$db_house_new_charge_project->getOne(['id'=>$v['project_id']]);
            if (!empty($project_info)&&$project_info['type']==1){
                $v['service_start_time']=1;
                $v['service_end_time']=1;
            }
            //表格区
            if($v['type'] == 2){
                if($v['bill_create_set'] == 1){
                    $unit_price = $v['unit_price'].'元/日';
                }elseif($v['bill_create_set'] == 2){
                    $unit_price = $v['unit_price'].'元/月';
                }elseif($v['bill_create_set'] == 3){
                    $unit_price = $v['unit_price'].'元/年';
                }else{
                    $unit_price = $v['unit_price'].'元';
                }
            }else{
                if($v['order_type'] == 'water' || $v['order_type'] == 'electric' || $v['order_type'] == 'gas'){
                    $measure_unit = $v['measure_unit']?$v['measure_unit']:'元/度';
                    $unit_price = $v['unit_price'].$measure_unit;
                }else{
                    $unit_price = $v['unit_price'].'元';
                }
            }
            if(in_array($v['order_type'],['water','electric','gas'])){
                $start_time=$end_time='-';
            }else{
                $start_time=intval($v['service_start_time']) > 1 ? date('Y-m-d', $v['service_start_time']) : '-';
                $end_time=intval($v['service_end_time']) > 1 ? date('Y-m-d', $v['service_end_time']) : '-';
            }
            if ($v['order_type']=='property' && $v['service_end_time']>100 && ($v['service_end_time']>$property_service_end_time)) {
                $property_service_end_time=$v['service_end_time'];
            }
            $order_body[]=[
                'room_num'=>$room_num,  //房号
                'rule_name'=>$v['charge_name'], //收费标准名称
                'order_name'=>$v['order_name'], //收费项目
                'service_cycle'=>$v['service_month_num'], //缴费周期
                'number'=>$number, //数量
                'price'=>round($unit_price,4),//单价
                'money'=>round($v["total_money"],4), //应收金额
                'discount'=>$v['diy_content'],//优惠
                'score_deducte'=>$v["score_deducte"], //积分抵扣金额
                'score_used_count'=>$v['score_used_count'],//积分使用数量
                'real_money'=>round($real_money,4), //实收金额
                'remarks'=>$v['remark'], //备注
                'start_time'=>$start_time,//起始时间
                'end_time'=>$end_time, //终止日期
                'pay_time'=>date('Y-m-d', $v['pay_time']),//缴费日期
                'last_ammeter'=>$last_ammeter,
                'now_ammeter'=>$now_ammeter,
            ];
            fdump_api([$v,$order_body],'0106111',1);
            $realMoney[]=$real_money;
            $pay_time[]=$v['pay_time'];
            if(!empty($v['remark'])){
                $remark[]=$v['remark'];
            }
            $money[] = $v["total_money"];
            $late_payment_money[] = $v["late_payment_money"];

            // 新版打印 收款人名称取操作人名称
            $role_id[] = ($v['from'] == 1) ? (empty($v['role_id']) ? '1-0' : $v['role_id']) : 0;
            // 房间ID
            $room_id = $v['room_id'];

            $admininfo = $db_admin->getOne(['id' => $v['role_id']],'realname,account');
            if (!empty($admininfo)) {
                if (!empty($admininfo['realname'])){
                    $payee_name = $admininfo['realname'];
                }else{
                    $payee_name = $admininfo['account'];
                }
            }
        }
        $realMoney=empty($realMoney) ? '0' : array_sum($realMoney);
        $money = empty($money) ? '0' : array_sum($money);
        $late_payment_money = empty($late_payment_money) ? '0' : array_sum($late_payment_money);
        if(!empty($pay_time)){
            if(count($pay_time) > 1){
                $max = array_search(max($pay_time), $pay_time);
                $min = array_search(min($pay_time), $pay_time);
                $pay_time=date('Y/m/d',$pay_time[$min]).'-'.date('Y/m/d',$pay_time[$max]);
            } else{
                $pay_time=date('Y-m-d H:i:s',$pay_time[0]);
            }
        }
        else{
            $pay_time='';
        }
        // 新版打印 延华 收款人
        $payeeType = '自动缴费';
        if(!in_array(0,$role_id)){
            if(in_array('1-0',$role_id)){
                $payeeType = '平台缴费';
            }else{
                $admininfo = $db_house_admin->getOne([['id','in', $role_id]]);
                if($admininfo && !$admininfo->isEmpty()){
                    $admininfo = $admininfo->toArray();
                    $payeeType = !empty($admininfo['realname']) ? $admininfo['realname'] : '自动缴费';
                }
            }
        }

        //页眉区/页脚区 非表格区
        $realMoney=round($realMoney,4);
        $order_field=[
            'title'=> $templateInfo['top_title'],
            'usernum'=>'',//编号
            'housesize'=>0,//房屋面积
            'village_name'=>$village_info['village_name'],//小区
            'room_num'=>'',//房号
            'username'=> !empty($user_info) ? $user_info['name'] : '',//住户姓名
            'phone'=>!empty($user_info) ? $user_info['phone'] : '',//住户手机号
            'totalMoney'=>'￥' . $realMoney . '（人民币大写：' . cny($realMoney) . '）',//合计
            'real_money_type' => ['value1' => cny($realMoney),'value2' => '￥' . $realMoney], // 实收金额 新版打印模板使用
            'totalMoneyType' => round_number($realMoney,2), // 金额合计 新版打印模板使用 无大写数字
            'money' => round($money,4), // 应收金额 新版打印模板使用 无大写数字
            'late_payment_money' => $late_payment_money, // 滞纳金 新版打印模板使用 无大写数字
            'payeeType' => $payeeType, // 收款员 新版打印模板使用 无大写数字
            'print_time'=>date('Y-m-d H:i:s', $time),//打印日期
            'pay_time'=>$pay_time,//收款日期
            'payee'=>$role_name,//收款方
            'payee_namess'=>$payee_name,//收款人
            'desc'=>$templateInfo['desc'],//说明
            'payer'=>!empty($user_info) ? $user_info['name'] : '',//付款人
            'pay_type_name'=>!empty($pay_type) ? (implode('，',array_unique($pay_type))): '',//收款方式
            'remarks'=>empty($remark) ? '' : ((count($remark) < 2) ? $remark[0] :count($remark).'个'),//收款备注
            'case'=>cny($realMoney),//合计(大写)
            'printNumber'=>$ser_print_template->printTemplateNumber($templateInfo['template_id'],$order_ids,$village_id), // 新版打印模板编号 NO 编号7位数
            'fact_total_money'=> ['value1' => cny($realMoney),'value2' => '￥' . $realMoney],
            'need_total_money'=> ['value1' => cny($money),'value2' => '￥' . $money],
        ];
        if($property_service_end_time>1000){
            $property_service_end_time=$property_service_end_time;
            $order_field['property_fee_expire_time']=date('Y-m-d',$property_service_end_time);
        }
        if((!empty($user_info) && !empty($user_info['vacancy_id']))){
            $room_id = $user_info['vacancy_id'];
        }
        if(!empty($room_id)){
            $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $room_id]);
            if (!empty($room)) {
                $room = $room->toArray();
                if (!empty($room)) {
                    if (!empty($room)) {
                        $room1 = $room[0];
                        $order_field['room_num']=(new HouseVillageService())->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$room1['village_id']);
                        $order_field['housesize'] = $room1['housesize'].' ㎡';
                    }
                }
            }
        }
        if (!empty($user_info) && empty($user_info['bind_number'])){
            $order_field['usernum'] = $user_info['usernum'];
        }
        else{
            $order_field['usernum'] = !empty($user_info) ? $user_info['bind_number'] : '';
        }
        /*$where[] = ['order_id', '=', $order_id];
        $orderInfo = $db_house_new_pay_order->get_one($where);
        if (empty($orderInfo)) {
            throw new \think\Exception("订单信息不存在！");
        }
        $data_order = [];
        if (!empty($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
            if (!empty($orderInfo)) {
                //小区信息
                $village_info = $db_house_village->getOne($village_id, 'village_name,property_id');
                $user_info = $db_house_village_user_bind->getOne(['pigcms_id' => $orderInfo['pigcms_id']]);

                $orderInfo['role_name'] = '';
                $property_info=(new HousePropertyService())->getFind(['id'=>$village_info['property_id']],'property_name');
                if($property_info){
                    $orderInfo['role_name']=$property_info['property_name'];
                }

                $projectinfo=$db_house_new_charge_project->getOne(['id' => $orderInfo['project_id']]);

                if (!empty($projectinfo)) {
                    $orderInfo['order_name'] = $projectinfo['name'];
                }
                $number = '';
                $orderInfo['number'] ='无';
                if (in_array($orderInfo['order_type'],['water','electric','gas'])){
                    $orderInfo['number'] = round($orderInfo["total_money"] / $orderInfo["unit_price"], 2);
                }
                if (!empty($orderInfo['room_id'])) {
                    $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $orderInfo['room_id']]);
                    if (!empty($room)) {
                        $room = $room->toArray();
                        if (!empty($room)) {
                            $room1 = $room[0];
                            $number = $room1['single_name'] . '栋' . $room1['floor_name'] . '单元' . $room1['layer_name'] . '层' . $room1['room'];
                            if ($orderInfo['order_type']=='property'&&!empty($room1['housesize'])){
                                $orderInfo['number']=$room1['housesize'].'(房屋面积)';
                            }
                        }
                    }
                } elseif (!empty($orderInfo['position_id'])) {
                    $position_num = $db_house_village_parking_position->getLists(['position_id' => $orderInfo['position_id']], 'pp.position_num,pg.garage_num', 0);
                    if (!empty($position_num)) {
                        $position_num = $position_num->toArray();
                        if (!empty($position_num)) {
                            $position_num1 = $position_num[0];
                            if (empty($position_num1['garage_num'])) {
                                $position_num1['garage_num'] = '临时车库';
                            }
                            $number = $position_num1['garage_num'] . $position_num1['position_num'];
                        }
                    }
                }

                if (isset($orderInfo['pay_type']) && !empty($orderInfo['pay_type'])) {
                    if ($orderInfo['pay_type'] == 2) {
                        $db_house_new_offline_pay = new HouseNewOfflinePay();
                        $offline_pay = $db_house_new_offline_pay->get_one(['id'=>$orderInfo['offline_pay_type']]);
                        if (empty($offline_pay)){
                            $offline_pay_type='';
                        }else{
                            $offline_pay_type=$offline_pay['name'];
                        }
                        $orderInfo['pay_type'] = $this->pay_type[$orderInfo['pay_type']] . '-' . $offline_pay_type;
                    } elseif (in_array($orderInfo['pay_type'], [1, 3])) {
                        $orderInfo['pay_type'] = $this->pay_type[$orderInfo['pay_type']];
                    } elseif ($orderInfo['pay_type'] == 4) {
                        if (empty($orderInfo['online_pay_type'])) {
                            $online_pay_type1 = '余额支付';
                        } else {
                            $online_pay_type1 = $this->pay_type_arr[$orderInfo['online_pay_type']];
                        }
                        $orderInfo['pay_type'] = $this->pay_type[$orderInfo['pay_type']] . '-' . $online_pay_type1;
                    }
                }

                if (!empty($orderInfo['service_month_num'])){
                    $db_house_new_charge_rule = new HouseNewChargeRule();
                    $ruleInfo = $db_house_new_charge_rule->getOne(['id'=>$orderInfo['rule_id']]);
                    if ($ruleInfo['bill_create_set']==1){
                        $orderInfo['service_month_num']=$orderInfo['service_month_num'].'天';
                    }elseif ($ruleInfo['bill_create_set']==2){
                        $orderInfo['service_month_num']=$orderInfo['service_month_num'].'个月';
                    }elseif ($ruleInfo['bill_create_set']==3){
                        $orderInfo['service_month_num']=$orderInfo['service_month_num'].'年';
                    }else{
                        $orderInfo['service_month_num']=$orderInfo['service_month_num'].'';
                    }

                }

 
		$digit_info=[];
                if(empty($digit_info)){
                    $real_money=formatNumber($orderInfo['pay_money']-$orderInfo['refund_money'],2,1);
                    $orderInfo['total_money']=formatNumber($orderInfo['total_money'],2,1);
                    $orderInfo['pay_money']=formatNumber($orderInfo['pay_money'],2,1);
                }else{
                    if($orderInfo['order_type'] == 'water' || $orderInfo['order_type'] == 'electric' || $orderInfo['order_type'] == 'gas'){
                        $real_money=formatNumber($orderInfo['pay_money']-$orderInfo['refund_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $orderInfo['total_money']=formatNumber($orderInfo['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                        $orderInfo['pay_money']=formatNumber($orderInfo['pay_money'],$digit_info['meter_digit'],$digit_info['type']);

                    }else{
                        $real_money=formatNumber($orderInfo['pay_money']-$orderInfo['refund_money'],$digit_info['other_digit'],$digit_info['type']);
                        $orderInfo['total_money']=formatNumber($orderInfo['total_money'],$digit_info['other_digit'],$digit_info['type']);
                        $orderInfo['pay_money']=formatNumber($orderInfo['pay_money'],$digit_info['other_digit'],$digit_info['type']);

                    }
                }

                $data_order['title'] = $templateInfo['title'];
                if (empty($user_info['bind_number'])){
                    $data_order['usernum'] = $user_info['usernum'];
                }else{
                    $data_order['usernum'] = $user_info['bind_number'];
                }

                $data_order['print_time'] = date('Y-m-d H:i:s', time());;
                $data_order['village_name'] = $village_info['village_name'];
                $data_order['room_num'] = $number;
                $data_order['username'] = $user_info['name'];
                $data_order['phone'] = $user_info['phone'];
                $data_order['totalMoney'] = '￥' . $orderInfo['total_money'] . '（人民币大写：' . cny($orderInfo['total_money']) . '）';;
                $data_order['pay_time'] = date('Y-m-d H:i:s', $orderInfo['pay_time']);
                $data_order['payee'] = $orderInfo['role_name'];
                $data_order['payer'] = $orderInfo['pay_bind_name'];
                $data_order['order_name'] = $orderInfo['order_name'];
                $data_order['price'] = $orderInfo['unit_price'];
                $data_order['service_cycle'] = $orderInfo['service_month_num'];
                $data_order['number'] = $orderInfo['number'];
                $data_order['money'] = $orderInfo["total_money"];
                $data_order['discount'] = $orderInfo['diy_content'];
                $data_order['real_money'] = $real_money;
                $data_order['remarks'] = $orderInfo['remark'];
                $data_order['pay_type_name'] = $orderInfo['pay_type'];
                $data_order['desc'] = $templateInfo['desc'];
                $data_order['start_time'] = date('Y-m-d', $orderInfo['service_start_time']);
                $data_order['end_time'] = date('Y-m-d', $orderInfo['service_end_time']);
                $data_order['case'] = cny($orderInfo['pay_money']);

            }

        }

        $printList1 = [];
        $printList2 = [];
        $printList3 = [];
        if (!empty($printCustomList)) {
            $printCustomList = $printCustomList->toArray();
            if (!empty($printCustomList)) {
                foreach ($printCustomList as $val) {
                    $val['value'] = $data_order[$val['field_name']];
                    if ($val['field_name'] == 'title') {
                        unset($val);
                        continue;
                    }
                    if ($val['type'] == 1) {
                        $printList1[] = $val;
                    } elseif ($val['type'] == 2) {
                        $printList2[] = $val;
                    } else {
                        $printList3[] = $val;
                    }
                }
            }
        }*/

        $printList1 = [];
        $printList2 = [];
        $printList3 = [];
        $printList4 = [];
        $printList5 = [];
        $printList6 = [];
        $printList7 = [];
        $is_title = false;
        if (!empty($printCustomList)) {
            foreach ($printCustomList as $val) {
                if(!isset($val['type'])){
                    continue;
                }
                $value='';
                if(in_array($val['type'],[1,3,4,5,6,7])){
                    if($val['field_name'] == 'payee' && $val['type']==1){
                        $value=$order_field['payee_namess'];
                    }else{
                        if (isset($order_field[$val['field_name']])){
                            $value=$order_field[$val['field_name']];
                        }
                    }

                }
                $val['value'] = $value;
                if ($val['field_name'] == 'title') {
                    $is_title = true;
                    unset($val);
                    continue;
                }
                switch ($val['type']){
                    case 2:
                        if($is_water_electric_gas<1 && in_array($val['field_name'],array('now_ammeter','last_ammeter'))){
                            break;
                        }
                        $printList2[] = $val;
                        break;
                    case 3:
                        $printList3[] = $val;
                        break;
                    case 4:
                        $printList4[] = $val;
                        break;
                    case 5:
                        $printList5[] = $val;
                        break;
                    case 6:
                        $printList6[] = $val;
                        break;
                    case 7:
                        $printList7[] = $val;
                        break;
                    default:
                        $printList1[] = $val;
                        break;
                }
            }
        }

        // 新版打印模板处理表格器数据
        $tab_list = [];
        if(!empty($order_body) && !empty($printList2)){
            foreach ($order_body as $value){
                $temp = [];
                foreach ($printList2 as $v){
                    $temp[] = isset($value[$v['field_name']]) ? $value[$v['field_name']] : '';
                }
                $tab_list[] = $temp;
            }
        }

        // 模板一强制新增区域为空数组  模板二修改成模板一的情况
        if($templateInfo['type'] == 1){
            $printList4 = [];
            $printList5 = [];
            $printList6 = [];
        }
        if($templateInfo['type'] == 3){
            //加一条备注
            $printList6[]=['configure_id'=>0,'field_name'=>'bak_remarks','title'=>'备注','type'=>6,'value'=>'押金类收据凭票退款，请妥善保管'];
        }
        $font_set=[];
        if(isset($templateInfo['font_set']) && !empty($templateInfo['font_set'])){
            $font_set=json_decode($templateInfo['font_set'],1);
        }
        return [
            'print_title' => $order_field['title'],
            'type' => $templateInfo['type'],
            'is_title' => $is_title,
            'printList1' => $printList1,
            'printList2' => $printList2,
            'printList3' => $printList3,
            'printList4' => $printList4,
            'printList5' => $printList5,
            'printList6' => $printList6,
            'printList7' => $printList7,
            'data_order' => $order_body,
            'tab_list' => $tab_list,
            'col' => $templateInfo['col_num'],
            'font_set'=>$font_set,
            'print_number'=>$order_field['printNumber'],
            'prints_num'=>$max_print_num,
        ];
    }


    /**
     * 查询支付方式列表
     * @author:zhubaodi
     * @date_time: 2021/9/16 14:50
     */
    public function getPayTypeList($property_id,$type){
        $db_house_new_offline_pay = new HouseNewOfflinePay();

        //1扫码支付  2线下支付 3收款码支付 4线上支付
        // $pay_type_arr = ['wechat' => '微信', 'alipay' => '支付宝', 'unionpay' => '银联'];
        $data=[
            ['id'=>'4-0', 'name'=>'线上支付-余额支付'],
            ['id'=>'4-1', 'name'=>'线上支付-微信'],
            ['id'=>'4-2', 'name'=>'线上支付-支付宝'],
            ['id'=>'4-3', 'name'=>'线上支付-银联'],
            ['id'=>'5-0', 'name'=>'线上支付-环球汇通'],
        ];
        if (empty($type)){
            $data[]=['id'=>'1', 'name'=>'扫码支付'];
            $offline_pay = $db_house_new_offline_pay->getList(['property_id'=>$property_id,'status'=>1]);
            if (!empty($offline_pay)){
                $offline_pay= $offline_pay->toArray();
                if (!empty($offline_pay)) {
                    foreach ($offline_pay as $v) {
                        $vv=[];
                        $vv['id']='2-'.$v['id'];
                        $vv['name']='线下支付-'.$v['name'];
                        $data[]=$vv;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 查询历史缴费账单
     * @author:zhubaodi
     * @date_time: 2021/6/29 17:36
     */
    public function getHistoryOrderList($village_id, $page, $limit)
    {
        if (empty($village_id)) {
            throw new \think\Exception("小区id不能为空！");
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $where = [];
        $where[] = ['is_paid', '=', 1];
        $where[] = ['village_id', '=', $village_id];
        $where1 = '`refund_money`<`pay_money`';
        $list = $db_house_new_pay_order_summary->getList($where, $where1, '*', $page, $limit);
        if (!empty($list)) {
            $list = $list->toArray();
            if (!empty($list)) {
                $where_pay = [];
                $db_house_new_offline_pay = new HouseNewOfflinePay();
                $pay_list = $db_house_new_offline_pay->getList($where_pay, '*');
                $payList = [];
                if (!empty($pay_list)) {
                    $pay_list = $pay_list->toArray();
                    if (!empty($pay_list)) {
                        foreach ($pay_list as $vv) {
                            $payList[$vv['id']] = $vv['name'];
                        }
                    }
                }
                $db_house_property_digit_service = new HousePropertyDigitService();
                $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$list[0]['property_id']]);

                foreach ($list as $key => &$value) {
                    $where_order = [];
                    $where_order[] = ['o.summary_id', '=', $value['summary_id']];
                    $where_order[] = ['o.is_paid', '=', 1];
                    $where_order[] = ['o.order_type', '<>', 'non_motor_vehicle'];
                    $where_order[] = ['o.village_id', '=', $village_id];
                    $where11 = '`o`.`refund_money`<`o`.`pay_money`';
                    $whereRefund=[];
                    $whereRefund[]=['refund_type', '<>', 2];
                    $whereRefund[] = ['summary_id', '=', $value['summary_id']];
                    $whereRefund[] = ['is_paid', '=', 1];
                    $whereRefund[] = ['order_type', '<>', 'non_motor_vehicle'];
                    $whereRefund[] = ['village_id', '=', $village_id];
                    $countRefund=$db_house_new_pay_order->getSum($whereRefund,'pay_money');
                    $value['pay_money']=$countRefund;
                    $pay_money=0;
                    $order_list = $db_house_new_pay_order->getHistoryOrder($where_order, $where11, 'cp.img,o.summary_id,o.order_id,o.order_name,o.pay_money,o.pay_time,o.pay_type ,o.offline_pay_type,o.is_paid', 'o.order_id DESC');
                       if (!empty($order_list)) {
                            $order_list = $order_list->toArray();
                            if (!empty($order_list)){
                                foreach ($order_list as $vv){
                                    $pay_money=$pay_money+$vv['pay_money'];
                                }
                            }
                            $value['pay_money']=$pay_money;
                            if(empty($digit_info)){
                                $value['pay_money'] = formatNumber($value['pay_money'],2,1);
                            }else{
                                $value['pay_money']= formatNumber($value['pay_money'],$digit_info['other_digit'],$digit_info['type']);
                            }
                            if (!empty($order_list)) {
                                if (count($order_list) > 1) {
                                    $value['type'] = 2;
                                    $value['order_name'] = '合计';
                                    $value['img'] = cfg('site_url') . '/static/images/house/total.png';
                                } else {
                                    $value['type'] = 1;
                                    $value['order_name'] = $order_list[0]['order_name'];
                                    $value['img'] = replace_file_domain($order_list[0]['img']);
                                }
                                if ($value['pay_type'] == 2||$value['pay_type'] == 22) {
                                    $offline_pay_type_str=$order_list[0]['offline_pay_type'];
                                    $offline_pay_type = '';
                                    if (!empty($payList) && !empty($offline_pay_type_str)) {
                                        if(strpos($offline_pay_type_str,',')>0){
                                            $offline_pay_type_arr=explode(',',$offline_pay_type_str);
                                            foreach ($offline_pay_type_arr as $opay){
                                                if(isset($payList[$opay])){
                                                    $offline_pay_type.=empty($offline_pay_type) ? $payList[$opay]:'、'.$payList[$opay];
                                                }
                                            }
                                        }else{
                                            $offline_pay_type = isset($payList[$offline_pay_type_str]) ? $payList[$offline_pay_type_str]:'';
                                        }
                                    }
                                    $value['pay_type'] = $this->pay_type[$value['pay_type']] . '-' . $offline_pay_type;
                                } elseif (in_array($value['pay_type'], [1, 3])) {
                                    $value['pay_type'] = $this->pay_type[$value['pay_type']];
                                } elseif ($value['pay_type'] == 4) {
                                    if (empty($value['online_pay_type'])) {
                                        $online_pay_type = '余额支付';
                                    } else {
                                        $online_pay_type = $this->pay_type_arr[$value['online_pay_type']];
                                    }
                                    $value['pay_type'] = $this->pay_type[$value['pay_type']] . '-' . $online_pay_type;
                                }
                                if ($value['pay_time'] > 1) {
                                    $value['pay_time'] = date('Y-m-d H:i:s', $value['pay_time']);
                                }
                                if (!empty($value['refund_money']) && $value['refund_money'] < $value['pay_money']) {
                                    $value['refund_status'] = '部分退款';
                                } else {
                                    $value['refund_status'] = '';
                                }

                                //  print_r($value);exit;

                            }
                        }else{
                            unset($list[$key]);
                        }
                    }
                $list = array_values($list);
            }
        }

        return $list;
    }

    /**
     * 查询历史缴费账单详情
     * @author:zhubaodi
     * @date_time: 2021/6/29 19:47
     */
    public function getHistoryOrderInfo($village_id, $summary_id)
    {
        if (empty($village_id)) {
            throw new \think\Exception("小区id不能为空！");
        }
        if (empty($summary_id)) {
            throw new \think\Exception("账单id不能为空！");
        }
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village_detail_record = new HouseVillageDetailRecord();
        $db_house_new_charge_project = new HouseNewChargeProject();
        $db_house_new_charge_number = new HouseNewChargeNumber();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $db_admin = new Admin();
        $summary_info = $db_house_new_pay_order_summary->getOne(['summary_id' => $summary_id]);
        if (!empty($summary_info)){
            if ($summary_info['pay_type'] == 2 || $summary_info['pay_type'] == 22) {
                $db_house_new_offline_pay = new HouseNewOfflinePay();
                $offline_pay_type='';
                if(strpos($summary_info['offline_pay_type'],',')>0){
                    $offline_pay_type_arr=explode(',',$summary_info['offline_pay_type']);
                    $where_pay_arr=array();
                    $where_pay_arr[]=['id','in',$offline_pay_type_arr];
                    $pay_list = $db_house_new_offline_pay->getList($where_pay_arr, '*');
                    $payList = [];
                    if (!empty($pay_list)) {
                        $pay_list = $pay_list->toArray();
                        if (!empty($pay_list)) {
                            foreach ($pay_list as $vv) {
                                $payList[$vv['id']] = $vv['name'];
                            }
                        }
                    }
                    foreach ($offline_pay_type_arr as $opay){
                        if(isset($payList[$opay])){
                            $offline_pay_type.=empty($offline_pay_type) ? $payList[$opay]:'、'.$payList[$opay];
                        }
                    }

                }else{
                    $offline_pay = $db_house_new_offline_pay->get_one(['id'=>$summary_info['offline_pay_type']]);
                    if (!empty($offline_pay) && !$offline_pay->isEmpty()){
                        $offline_pay_type=$offline_pay['name'];
                    }
                }

                $data['pay_type'] = $this->pay_type[$summary_info['pay_type']] . '-' . $offline_pay_type;
            } elseif (in_array($summary_info['pay_type'], [1, 3])) {
                $data['pay_type'] = $this->pay_type[$summary_info['pay_type']];
            } elseif ($summary_info['pay_type'] == 4) {
                if (empty($summary_info['online_pay_type'])) {
                    $online_pay_type1 = '余额支付';
                } else {
                    $online_pay_type1 = $this->pay_type_arr[$summary_info['online_pay_type']];
                }
                $data['pay_type'] = $this->pay_type[$summary_info['pay_type']] . '-' . $online_pay_type1;
            }
        }
        $field = 'o.*,cp.img,cp.type';
        $where1 = '`refund_money`<`pay_money`';
        $list = $db_house_new_pay_order->getHistoryOrder([['o.summary_id' ,'=',$summary_id],['o.is_paid','=',1],['o.refund_type','<>',2]], $where1, $field);
        //  print_r($list);exit;
        $order_data = [];
        if (!empty($list)) {
            $list = $list->toArray();
            if (!empty($list)) {

                $db_house_property_digit_service = new HousePropertyDigitService();
                $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$list[0]['property_id']]);

                if (count($list) == 1) {
                    $number = '';
                    if (!empty($list[0]['position_id'])) {
                        $position_num = $db_house_village_parking_position->getLists(['position_id' => $list[0]['position_id']], 'pp.position_num,pg.garage_num', 0);
                        if (!empty($position_num)) {
                            $position_num = $position_num->toArray();
                            if (!empty($position_num)) {
                                $position_num1 = $position_num[0];
                                if (empty($position_num1['garage_num'])) {
                                    $position_num1['garage_num'] = '临时车库';
                                }
                                $number = $position_num1['garage_num'] . $position_num1['position_num'];
                            }
                        }
                    } elseif (!empty($list[0]['room_id'])) {
                        $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $list[0]['room_id']]);
                        if (!empty($room)) {
                            $room = $room->toArray();
                            if (!empty($room)) {
                                $room1 = $room[0];
                                $number = $room1['single_name'] . '(栋)' . $room1['floor_name'] . '(单元)' . $room1['layer_name'] . '(层)' . $room1['room'];
                            }
                        }
                    }
                    $project_name = '';
                    if (!empty($list[0]['project_id'])) {
                        $project_info = $db_house_new_charge_project->getOne(['id' => $list[0]['project_id']], 'id,name,subject_id');
                        $project_name = $project_info['name'];
                    }
                    if (!empty($list[0]['rule_id'])) {
                        $rule_info = $db_house_new_charge_rule->getOne(['id' => $list[0]['rule_id']], '*');
                    }
                    $subject_name = '';
                    $subject_type='';
                    if (!empty($project_info['subject_id'])) {
                        $subjectinfo = $db_house_new_charge_number->get_one(['id' => $project_info['subject_id']]);
                        if (!empty($subjectinfo)) {
                            $subject_name = $subjectinfo['charge_number_name'];
                            $subject_type = $subjectinfo['charge_type'];
                        }
                    }
                    $admininfo = $db_admin->getOne(['id' => $list[0]['role_id']]);
                    $list[0]['role_name'] = '';
                    if (!empty($admininfo)) {
                        $list[0]['role_name'] = $admininfo['account'];
                    }
                    $record = $db_house_village_detail_record->getOne(['order_id' => $list[0]['order_id']]);
                    if (empty($record)) {
                        $list[0]['record_status'] = '未开票';
                    } else {
                        $list[0]['record_status'] = '已开票';
                    }
                    if ($list[0]['is_refund'] == 1) {
                        $list[0]['refund_status'] = '正常';
                    } elseif ($list[0]['refund_money'] < $list[0]['pay_money']) {
                        $list[0]['refund_status'] = '部分退款';
                    }
                    $pay_type=$data['pay_type'];

                    if(empty($digit_info)){
                        $list[0]['pay_money']= formatNumber($list[0]['pay_money'],2,1);
                        $list[0]['total_money']= formatNumber($list[0]['total_money'],2,1);
                        $list[0]['modify_money']= formatNumber($list[0]['modify_money'],2,1);
                        $list[0]['late_payment_money']= formatNumber($list[0]['late_payment_money'],2,1);
                        $list[0]['prepare_pay_money']= formatNumber($list[0]['prepare_pay_money'],2,1);
                        $list[0]['refund_money']= formatNumber($list[0]['refund_money'],2,1);

                        $list[0]['pay_amount_points']=formatNumber($list[0]['pay_amount_points']/100,2,1);
                        $list[0]['system_balance']=formatNumber($list[0]['system_balance'],2,1);
                        $list[0]['score_deducte']=formatNumber($list[0]['score_deducte'],2,1);
                        $list[0]['score_used_count']=formatNumber($list[0]['score_used_count'],2,1);


                    }else{
                        if($list[0]['order_type'] == 'water' || $list[0]['order_type'] == 'electric' || $list[0]['order_type'] == 'gas'){
                            $list[0]['pay_money']= formatNumber( $list[0]['pay_money'],$digit_info['meter_digit'],$digit_info['type']);
                            $list[0]['total_money']= formatNumber( $list[0]['total_money'],$digit_info['meter_digit'],$digit_info['type']);
                            $list[0]['modify_money']= formatNumber( $list[0]['modify_money'],$digit_info['meter_digit'],$digit_info['type']);
                            $list[0]['late_payment_money']= formatNumber( $list[0]['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']);
                            $list[0]['prepare_pay_money']= formatNumber( $list[0]['prepare_pay_money'],$digit_info['meter_digit'],$digit_info['type']);
                            $list[0]['refund_money']= formatNumber( $list[0]['refund_money'],$digit_info['meter_digit'],$digit_info['type']);

                            $list[0]['pay_amount_points']=formatNumber($list[0]['pay_amount_points']/100,$digit_info['meter_digit'],$digit_info['type']);
                            $list[0]['system_balance']=formatNumber($list[0]['system_balance'],$digit_info['meter_digit'],$digit_info['type']);
                            $list[0]['score_deducte']=formatNumber($list[0]['score_deducte'],$digit_info['meter_digit'],$digit_info['type']);
                            $list[0]['score_used_count']=formatNumber($list[0]['score_used_count'],$digit_info['meter_digit'],$digit_info['type']);

                        }else{
                            $list[0]['pay_money']= formatNumber( $list[0]['pay_money'],$digit_info['other_digit'],$digit_info['type']);
                            $list[0]['total_money']= formatNumber( $list[0]['total_money'],$digit_info['other_digit'],$digit_info['type']);
                            $list[0]['modify_money']= formatNumber( $list[0]['modify_money'],$digit_info['other_digit'],$digit_info['type']);
                            $list[0]['late_payment_money']= formatNumber( $list[0]['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
                            $list[0]['prepare_pay_money']= formatNumber( $list[0]['prepare_pay_money'],$digit_info['other_digit'],$digit_info['type']);
                            $list[0]['refund_money']= formatNumber( $list[0]['refund_money'],$digit_info['other_digit'],$digit_info['type']);

                            $list[0]['pay_amount_points']=formatNumber($list[0]['pay_amount_points']/100,$digit_info['other_digit'],$digit_info['type']);
                            $list[0]['system_balance']=formatNumber($list[0]['system_balance'],$digit_info['other_digit'],$digit_info['type']);
                            $list[0]['score_deducte']=formatNumber($list[0]['score_deducte'],$digit_info['other_digit'],$digit_info['type']);
                            $list[0]['score_used_count']=formatNumber($list[0]['score_used_count'],$digit_info['other_digit'],$digit_info['type']);

                        }
                    }
                    $data = [];
                    $data['money'] = '￥' . $list[0]['pay_money'];
                    $data['order_no'] = $list[0]['order_no'];
                    $data['order_serial'] = $list[0]['order_serial'];
                    $data['number'] = $number;
                    $data['pay_bind_name'] = $list[0]['pay_bind_name'];
                    $data['pay_bind_phone'] = $list[0]['pay_bind_phone'];
                    $data['project_name'] = $project_name;
                    $data['subject_name'] = $subject_name;
                    $data['total_money'] = $list[0]['total_money'];
                    $data['modify_money'] = '￥' . $list[0]['modify_money'];
                    $data['modify_reason'] = $list[0]['modify_reason'];
                    $data['pay_money'] = '￥' . $list[0]['pay_money'];
                    $data['pay_money1'] = $list[0]['pay_money'];
                    if (empty($list[0]['diy_type'])){
                        $data['diy_type'] ='无';
                    }else {
                        $data['diy_type'] = $this->diy_type[$list[0]['diy_type']];
                    }
                    $data['score_used_count'] = $list[0]['score_used_count'];
                    $data['pay_time'] = date('Y-m-d H:i:s', $list[0]['pay_time']);
                    $data['pay_type'] = $pay_type;
                    if ($list[0]['service_start_time'] > 1) {
                        $data['service_start_time'] = date('Y-m-d H:i:s', $list[0]['service_start_time']);
                        $data['service_end_time'] = date('Y-m-d H:i:s', $list[0]['service_end_time']);
                    }
                    $data['ammeter'] = $list[0]['now_ammeter'] - $list[0]['last_ammeter'];
                    $data['role_name'] = $list[0]['role_name'];
                    $data['record_status'] = $list[0]['record_status'];
                    $data['refund_status'] = $list[0]['refund_status'];

                    $data['remark'] = $list[0]['remark'];
                    $data['late_payment_day'] = $list[0]['late_payment_day'];
                    $data['late_payment_money'] = '￥' . $list[0]['late_payment_money'];

                    $data['pay_amount_points'] = '￥' . $list[0]['pay_amount_points'];
                    $data['system_balance'] = '￥' . $list[0]['system_balance'];
                    $data['score_deducte'] = '￥' . $list[0]['score_deducte'];
                    $is_prepare = $list[0]['is_prepare'];
                    if ($list[0]['type'] == 2) {
                        $data['service_month_num'] = $list[0]['service_month_num']?$list[0]['service_month_num']:1;
                        if (!empty($data['service_month_num'])){
                            if ($rule_info['bill_create_set']==1){
                                $data['service_month_num']=$data['service_month_num'].'天';
                            }elseif ($rule_info['bill_create_set']==2){
                                $data['service_month_num']=$data['service_month_num'].'个月';
                            }elseif ($rule_info['bill_create_set']==3){
                                $data['service_month_num']=$data['service_month_num'].'年';
                            }else{
                                $data['service_month_num']=$data['service_month_num'].'';
                            }

                        }
                    } else {
                        $data['service_month_num'] = 0;
                    }
                    $data['diy_content'] = $list[0]['diy_content'];
                    $data['prepare_pay_money'] = '￥' . $list[0]['prepare_pay_money'];
                    $data['refund_money'] = '￥' . $list[0]['refund_money'];

                    $ammeter_name='用量';

                    $list = [];
                    $list[]=array(
                        'title'=>'订单编号',
                        'val'=>$data['order_no']
                    );
                    $list[]=array(
                        'title'=>'支付单号',
                        'val'=>$data['order_serial']
                    );
                    $list[]=array(
                        'title'=>'房间号/车位号',
                        'val'=>$data['number']
                    );
                    $list[]=array(
                        'title'=>'缴费人',
                        'val'=>$data['pay_bind_name']
                    );
                    $list[]=array(
                        'title'=>'电话',
                        'val'=>$data['pay_bind_phone']
                    );
                    $list[]=array(
                        'title'=>'收费项目名称',
                        'val'=>$data['project_name']
                    );
                    $list[]=array(
                        'title'=>'所属收费科目',
                        'val'=>$data['subject_name']
                    );
                    $list[]=array(
                        'title'=>'应收费用',
                        'val'=>'￥' . $data['total_money']
                    );
                    $list[]=array(
                        'title'=>'修改后费用',
                        'val'=>$data['modify_money']
                    );
                    $list[]=array(
                        'title'=>'修改原因',
                        'val'=>$data['modify_reason']
                    );
                    $list[]=array(
                        'title'=>'实际缴费金额',
                        'val'=>$data['pay_money']
                    );
                    $list[]=array(
                        'title'=>'线上支付金额',
                        'val'=>$data['pay_amount_points']
                    );
                    $list[]=array(
                        'title'=>'余额支付金额',
                        'val'=>$data['system_balance']
                    );
                    $list[]=array(
                        'title'=>'积分抵扣金额',
                        'val'=>$data['score_deducte']
                    );
                    $list[]=array(
                        'title'=>'优惠方式',
                        'val'=>$data['diy_type']
                    );
                    $list[]=array(
                        'title'=>'积分使用数量',
                        'val'=>$data['score_used_count']
                    );
                    $list[]=array(
                        'title'=>'支付时间',
                        'val'=>$data['pay_time']
                    );
                    $list[]=array(
                        'title'=>'支付方式',
                        'val'=>$data['pay_type']
                    );
                    $list[]=array(
                        'title'=>$ammeter_name,
                        'val'=>$data['ammeter']
                    );

                    $list[]=array(
                        'title'=>'收款人',
                        'val'=>$data['role_name']
                    );
                    $list[]=array(
                        'title'=>'开票状态',
                        'val'=>$data['record_status']
                    );
                    $list[]=array(
                        'title'=>'账单状态',
                        'val'=>$data['refund_status']
                    );
                    $list[]=array(
                        'title'=>'备注',
                        'val'=>$data['remark']
                    );
                    $list[]=array(
                        'title'=>'滞纳总天数',
                        'val'=>$data['late_payment_day']
                    );
                    $list[]=array(
                        'title'=>'滞纳金总费用',
                        'val'=>$data['late_payment_money']
                    );
                    if($is_prepare == 1){
                        $list[]=array(
                            'title'=>'收费周期',
                            'val'=>'无'
                        );
                        $list[]=array(
                            'title'=>'预缴周期',
                            'val'=>$data['service_month_num']
                        );

                    }else{
                        $list[]=array(
                            'title'=>'收费周期',
                            'val'=>$data['service_month_num']
                        );
                        $list[]=array(
                            'title'=>'预缴周期',
                            'val'=>'无'
                        );
                    }
                    $list[]=array(
                        'title'=>'预缴优惠',
                        'val'=>$data['diy_content']
                    );
                    $list[]=array(
                        'title'=>'预缴费用',
                        'val'=>$data['prepare_pay_money']
                    );
                    $list[]=array(
                        'title'=>'退款总金额',
                        'val'=>$data['refund_money']
                    );
                    if (isset($data['service_start_time'])) {
                        $list[]=array(
                            'title'=>'计费开始时间',
                            'val'=>$data['service_start_time']
                        );
                        $list[]=array(
                            'title'=>'计费结束时间',
                            'val'=>$data['service_end_time']
                        );
                    }

                    $order_data['type'] = 1;
                    $order_data['total_money'] = $data['pay_money1'];
                    $order_data['data'] = $list;
                } else {
                    $order_data['type'] = 2;
                    $pay_type=$data['pay_type'];
                    $data = [];
                    if (!empty($summary_info)) {
                        $number = '';
                        if (!empty($summary_info['room_id'])) {
                            $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $summary_info['room_id']]);
                            if (!empty($room)) {
                                $room = $room->toArray();
                                if (!empty($room)) {
                                    $room1 = $room[0];
                                    $number = $room1['single_name'] . '(栋)' . $room1['floor_name'] . '(单元)' . $room1['layer_name'] . '(层)' . $room1['room'];
                                }
                            }
                        } elseif (!empty($summary_info['position_id'])) {
                            $position_num = $db_house_village_parking_position->getLists(['position_id' => $summary_info['position_id']], 'pp.position_num,pg.garage_num', 0);
                            if (!empty($position_num)) {
                                $position_num = $position_num->toArray();
                                if (!empty($position_num)) {
                                    $position_num1 = $position_num[0];
                                    if (empty($position_num1['garage_num'])) {
                                        $position_num1['garage_num'] = '临时车库';
                                    }
                                    $number = $position_num1['garage_num'] . $position_num1['position_num'];
                                }
                            }
                        }
                        $admininfo = $db_admin->getOne(['id' => $list[0]['role_id']]);
                        $list[0]['role_name'] = '';
                        if (!empty($admininfo)) {
                            $list[0]['role_name'] = $admininfo['account'];
                        }
                        $data['pay_bind_name'] = $list[0]['pay_bind_name'];
                        $data['pay_bind_phone'] = $list[0]['pay_bind_phone'];
                        $data['order_serial'] = $list[0]['order_no'];
                        $data['number'] = $number;
                        $data['pay_type'] = $pay_type;
                        /*if ($list[0]['pay_type'] == 2) {
                            $data['pay_type'] = $this->pay_type[$list[0]['pay_type']] . $list[0]['pay_type_name'];
                        } elseif (in_array($list[0]['pay_type'], [1, 3])) {
                            $data['pay_type'] = $this->pay_type[$list[0]['pay_type']];
                        }*/
                        $data['pay_time'] = date('Y-m-d H:i:s', $list[0]['pay_time']);
                        $data['role_name'] = $list[0]['role_name'];
                        $data['diy_content'] = $list[0]['diy_content'];
                        $data['score_used_count'] = $summary_info['score_used_count'];
                        $data['system_balance'] = $summary_info['system_balance'];
                        $data['pay_amount_points'] = round_number($summary_info['pay_amount_points']/100,2);
                        $data['score_deducte'] = $summary_info['score_deducte'];
                        $data['remarks'] = $summary_info['remarks'];

                        $list1 = [];
                        $list1[]=array(
                            'title'=>'缴费人',
                            'val'=>$data['pay_bind_name']
                        );
                        $list1[]=array(
                            'title'=>'支付单号',
                            'val'=>$data['order_serial']
                        );
                        $list1[]=array(
                            'title'=>'电话',
                            'val'=>$data['pay_bind_phone']
                        );
                        $list1[]=array(
                            'title'=>'地址',
                            'val'=>$data['number']
                        );
                        $list1[]=array(
                            'title'=>'支付方式',
                            'val'=>$data['pay_type']
                        );
                        $list1[]=array(
                            'title'=>'支付时间',
                            'val'=>$data['pay_time']
                        );
                        $list1[]=array(
                            'title'=>'收款人',
                            'val'=>$data['role_name']
                        );
                        $list1[]=array(
                            'title'=>'优惠方式',
                            'val'=>$data['diy_content']
                        );
                        $list1[]=array(
                            'title'=>'线上支付金额',
                            'val'=>$data['pay_amount_points']
                        );
                        $list1[]=array(
                            'title'=>'余额支付金额',
                            'val'=>$data['system_balance']
                        );
                        $list1[]=array(
                            'title'=>'积分抵扣金额',
                            'val'=>$data['score_deducte']
                        );
                        $list1[]=array(
                            'title'=>'积分使用数量',
                            'val'=>$data['score_used_count']
                        );
                        $list1[]=array(
                            'title'=>'备注',
                            'val'=>$data['remarks']
                        );
                        $order_list = [];
                        $sum_money = 0;
                        foreach ($list as $k => $val) {
                            $sum_money = $sum_money + $val['pay_money'];
                            $order_list[$k]['order_name'] = $val['order_name'];
                            $order_list[$k]['pay_money'] = $val['pay_money'];
                            $order_list[$k]['order_id'] = $val['order_id'];
                            $order_list[$k]['summary_id'] = $val['summary_id'];
                            if ($val['is_refund'] == 1) {
                                $order_list[$k]['refund_status'] = '';
                            } elseif ($val['refund_money'] < $val['pay_money']) {
                                $order_list[$k]['refund_status'] = '部分退款';
                            }
                            $order_list[$k]['img'] = replace_file_domain($val['img']);
                            if(empty($digit_info)){
                                $order_list[$k]['pay_money']= formatNumber($order_list[$k]['pay_money'],2,1);
                            }else{
                                if($val['order_type'] == 'water' || $val['order_type'] == 'electric' || $val['order_type'] == 'gas'){
                                    $order_list[$k]['pay_money']= formatNumber($order_list[$k]['pay_money'],$digit_info['meter_digit'],$digit_info['type']);
                               }else{
                                    $order_list[$k]['pay_money']= formatNumber($order_list[$k]['pay_money'],$digit_info['other_digit'],$digit_info['type']);
                                }
                            }
                        }
                        if(empty($digit_info)){
                            $sum_money= formatNumber($sum_money,2,1);
                        }else{
                            $sum_money= formatNumber($sum_money,$digit_info['other_digit'],$digit_info['type']);
                        }
                        $order_data['sum_money'] = $sum_money;
                        $order_data['list'] = $order_list;
                        $order_data['data'] = $list1;
                    }
                }
            }
        }
        return $order_data;
    }

    /**
     * 查询多个账单里面的单个账单详情
     * @author:zhubaodi
     * @date_time: 2021/6/29 19:47
     */
    public function getHistoryInfo($village_id, $order_id)
    {
        if (empty($village_id)) {
            throw new \think\Exception("小区id不能为空！");
        }
        if (empty($order_id)) {
            throw new \think\Exception("账单id不能为空！");
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_detail_record = new HouseVillageDetailRecord();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $order_info = $db_house_new_pay_order->get_one(['order_id' => $order_id, 'village_id' => $village_id]);
        $data = [];
        $list = [];
        if (!empty($order_info)) {
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$order_info['property_id']]);

            $record = $db_house_village_detail_record->getOne(['order_id' => $order_info['order_id']]);
            if (empty($record)) {
                $order_info['record_status'] = '未开票';
            } else {
                $order_info['record_status'] = '已开票';
            }
            if ($order_info['is_refund'] == 1) {
                $order_info['refund_status'] = '正常';
            } elseif ($order_info['refund_money'] < $order_info['pay_money']) {
                $order_info['refund_status'] = '部分退款';
            }

            if(empty($digit_info)){
                $order_info['pay_money']= formatNumber($order_info['pay_money'],2,1);
                $order_info['modify_money']= formatNumber($order_info['modify_money'],2,1);
                $order_info['late_payment_money']= formatNumber($order_info['late_payment_money'],2,1);
                $order_info['refund_money']= formatNumber($order_info['refund_money'],2,1);

                $order_info['pay_amount_points']=formatNumber($order_info['pay_amount_points']/100,2,1);
                $order_info['system_balance']=formatNumber($order_info['system_balance'],2,1);
                $order_info['score_deducte']=formatNumber($order_info['score_deducte'],2,1);
                $order_info['score_used_count']=formatNumber($order_info['score_used_count'],2,1);

            }else{
                if($order_info['order_type'] == 'water' || $order_info['order_type'] == 'electric' || $order_info['order_type'] == 'gas'){
                    $order_info['pay_money']= formatNumber($order_info['pay_money'],$digit_info['meter_digit'],$digit_info['type']);
                    $order_info['modify_money']= formatNumber($order_info['modify_money'],$digit_info['meter_digit'],$digit_info['type']);
                    $order_info['late_payment_money']= formatNumber($order_info['late_payment_money'],$digit_info['meter_digit'],$digit_info['type']);
                    $order_info['refund_money']= formatNumber($order_info['refund_money'],$digit_info['meter_digit'],$digit_info['type']);

                    $order_info['pay_amount_points']=formatNumber($order_info['pay_amount_points']/100,$digit_info['meter_digit'],$digit_info['type']);
                    $order_info['system_balance']=formatNumber($order_info['system_balance'],$digit_info['meter_digit'],$digit_info['type']);
                    $order_info['score_deducte']=formatNumber($order_info['score_deducte'],$digit_info['meter_digit'],$digit_info['type']);
                    $order_info['score_used_count']=formatNumber($order_info['score_used_count'],$digit_info['meter_digit'],$digit_info['type']);
                }else{
                    $order_info['pay_money']= formatNumber($order_info['pay_money'],$digit_info['other_digit'],$digit_info['type']);
                    $order_info['modify_money']= formatNumber($order_info['modify_money'],$digit_info['other_digit'],$digit_info['type']);
                    $order_info['late_payment_money']= formatNumber($order_info['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
                    $order_info['refund_money']= formatNumber($order_info['refund_money'],$digit_info['other_digit'],$digit_info['type']);

                    $order_info['pay_amount_points']=formatNumber($order_info['pay_amount_points']/100,$digit_info['other_digit'],$digit_info['type']);
                    $order_info['system_balance']=formatNumber($order_info['system_balance'],$digit_info['other_digit'],$digit_info['type']);
                    $order_info['score_deducte']=formatNumber($order_info['score_deducte'],$digit_info['other_digit'],$digit_info['type']);
                    $order_info['score_used_count']=formatNumber($order_info['score_used_count'],$digit_info['other_digit'],$digit_info['type']);
                }
            }
            $data['total_money'] = $order_info['pay_money'];
            $data['order_no'] = $order_info['order_no'];
            $data['order_name'] = $order_info['order_name'];
            $data['pay_money'] = '￥' . $order_info['modify_money'];
            $data['service_start_time'] = date('Y-m-d H:i:s', $order_info['service_start_time']);
            $data['service_end_time'] = date('Y-m-d H:i:s', $order_info['service_end_time']);
            $data['ammeter'] = $order_info['now_ammeter'] - $order_info['last_ammeter'];
            $data['record_status'] = $order_info['record_status'];
            $data['refund_status'] = $order_info['refund_status'];
            $data['late_payment_day'] = $order_info['late_payment_day'];
            $data['late_payment_money'] = '￥' . $order_info['late_payment_money'];
            $rule_info = $db_house_new_charge_rule->getOne(['id' => $order_info['rule_id']], '*');
            $db_house_new_charge_project = new HouseNewChargeProject();
            $project_info = $db_house_new_charge_project->getOne(['id'=>$order_info['project_id']],'type');
            $data['service_month_num'] = $order_info['service_month_num'];
            if($project_info['type'] == 2){
                $data['service_month_num'] = $data['service_month_num']?$data['service_month_num']:1;
                if (!empty($data['service_month_num'])){
                    if ($rule_info['bill_create_set']==1){
                        $data['service_month_num']=$data['service_month_num'].'天';
                    }elseif ($rule_info['bill_create_set']==2){
                        $data['service_month_num']=$data['service_month_num'].'个月';
                    }elseif ($rule_info['bill_create_set']==3){
                        $data['service_month_num']=$data['service_month_num'].'年';
                    }else{
                        $data['service_month_num']=$data['service_month_num'].'';
                    }
                }
            }
            $data['diy_content'] = $order_info['diy_content'];
            $data['prepare_pay_money'] = '￥' . $order_info['pay_money'];
            $data['refund_money'] = '￥' . $order_info['refund_money'];

            $data['pay_amount_points'] = '￥' . $order_info['pay_amount_points'];
            $data['system_balance'] = '￥' . $order_info['system_balance'];
            $data['score_deducte'] = '￥' . $order_info['score_deducte'];
            $data['score_used_count'] =  $order_info['score_used_count'];

           /* $list[0]['title'] = '应收';
            $list[0]['val'] = '￥' . $data['total_money'];*/
            $list = [];
            $list[]=array(
                'title'=>'订单编号',
                'val'=>$data['order_no']
            );
            $list[]=array(
                'title'=>'缴费项目',
                'val'=>$data['order_name']
            );
            $list[]=array(
                'title'=>'应收费用',
                'val'=>$data['pay_money']
            );
            $list[]=array(
                'title'=>'用量',
                'val'=>$data['ammeter']
            );

            $list[]=array(
                'title'=>'开票状态',
                'val'=>$data['record_status']
            );
            $list[]=array(
                'title'=>'账单状态',
                'val'=>$data['refund_status']
            );
            $list[]=array(
                'title'=>'滞纳天数',
                'val'=>$data['late_payment_day']
            );
            $list[]=array(
                'title'=>'滞纳金费用',
                'val'=>$data['late_payment_money']
            );
            if($order_info['is_prepare'] == 1){
                $list[]=array(
                    'title'=>'收费周期',
                    'val'=>'无'
                );
                $list[]=array(
                    'title'=>'预缴周期',
                    'val'=>$data['service_month_num']
                );

            }else{
                $list[]=array(
                    'title'=>'收费周期',
                    'val'=>$data['service_month_num']
                );
                $list[]=array(
                    'title'=>'预缴周期',
                    'val'=>'无'
                );
            }

        }


        $res = [];
        if (!empty($list)) {
            $res['list'] = $list;
            $res['total_money'] = $data['total_money'];
        } else {
            $res['list'] = [];
            $res['total_money'] = 0;
        }

        return $res;
    }


    /**
     * 未缴账单合计页
     * @param array $where
     * @param bool $field
     * @param int $pigcms_id
     * @param int $village_id
     * @param int $room_id
     * @param bool $static
     * @param string $year_1
     * @param bool $is_all_select
     * @param int $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/07/01
     */
    public function getYear($where = [], $field = true, $pigcms_id = 0, $village_id = 0, $room_id = 0, $static = true, $year_1 = '', $is_all_select = true, $type = 0)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($village_id,'property_id');
        $property_id = $village_info['property_id'];
        if($property_id){
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$property_id]);
        }else{
            $digit_info = [];
        }
        $data = $db_house_new_pay_order->getList($where, $field, 0);
        fdump_api($data,'0108',1);
        $year_arr = array();
        $list = array();
        if ($data) {
            foreach ($data as $v) {
                $year = date('Y', $v['add_time']);
                if (!in_array($year, $year_arr)) {
                    $year_arr[] = $year;
                }
            }
        }
        if ($year_arr) {
            foreach ($year_arr as $key => $value) {
                $where = [];
                $time = $this->getStartAndEndUnixTimestamp($value);
                $where[] = ['add_time', 'between', array($time['start'], $time['end'])];
                $where[] = ['is_discard', '=', 1];
                $where[] = ['is_paid', '=', 2];
                $where[] = ['order_type', '<>', 'non_motor_vehicle'];
                $where[] = ['room_id', '=', $room_id];
                $where[] = ['village_id', '=', $village_id];
                $where[]=  ['check_status','<>',1];
                $no_pay = $db_house_new_pay_order->getOrderByGroup($where, 'order_id', 'project_id');
                $no_pay_sum1 = $db_house_new_pay_order->getSum($where, 'modify_money');
                $no_pay_sum2 = $db_house_new_pay_order->getSum($where, 'late_payment_money');
                $no_pay_sum = $no_pay_sum2+$no_pay_sum1;
                $where[] = ['is_paid', '=', 2];
                $list[$key]['year'] = $value;
                $list[$key]['no_pay_count'] = count($no_pay);
                if(empty($digit_info)){
                    $no_pay_sum = formatNumber($no_pay_sum,2,1);
                }else{
                    $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                    $no_pay_sum = formatNumber($no_pay_sum,$digit_info['other_digit'],$digit_info['type']);
                }
                $list[$key]['no_pay_sum'] = $no_pay_sum;
            }
        }
        if ($list) {
            $db_house_new_select_project_log = new HouseNewSelectProjectLog();
            $db_house_new_select_project_record = new HouseNewSelectProjectRecord();
            $log_info = $db_house_new_select_project_log->getOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id]);
            if (empty($log_info) || $log_info['action_name'] == 'layOutCycle') {
                $db_house_new_select_project_log->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'action_name' => 'firstIn', 'add_time' => time()]);
                $total_money = 0.00;
                foreach ($list as $k => $v) {
                    $total_money += $v['no_pay_sum'];
                    $list[$k]['selected_pay_count'] = $v['no_pay_count'];
                    $list[$k]['selected_pay_sum'] = $v['no_pay_sum'];
                    $list[$k]['static'] = true;
                    $no_pay_list = $this->getNoPayDetail($v['year'], $room_id, 1, 0, 0, $village_id, true, true, 0);
                    if ($no_pay_list['list']) {
                        foreach ($no_pay_list['list'] as $v1) {
                            $record_info = $db_house_new_select_project_record->getList(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $v1['project_id'], 'year' => $v['year']]);
                            if($record_info){
                                $record_info = $record_info->toArray();
                            }
                            if (empty($record_info)) {
                                $db_house_new_select_project_record->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $v1['project_id'], 'year' => $v['year'], 'add_time' => time()]);
                            }
                        }
                    }
                }
                $is_all_select = true;
            } elseif ($year_1 && !$static) {    //取消选中单个年份
                $total_money = 0.00;
                $db_house_new_select_project_log->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'action_name' => 'unSelected', 'add_time' => time()]);
                $db_house_new_select_project_record->del(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'year' => $year_1]);
                foreach ($list as $k => $v) {
                    $no_pay_count = 0;
                    $no_pay_sum = 0.00;
                    if ($v['year'] != $year_1) {
                        $time = $this->getStartAndEndUnixTimestamp($v['year']);
                        $record_list = $db_house_new_select_project_record->getList(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'year' => $v['year']])->toArray();
                        if ($record_list) {
                            $list[$k]['static'] = true;
                            foreach ($record_list as $v1) {
                                $project_ids[] = $v1['project_id'];
                                $no_pay_count += 1;
                            }
                            $sum = $db_house_new_pay_order->getSum([['add_time', 'between', array($time['start'], $time['end'])], ['is_discard', '=', 1], ['is_paid', '=', 2],['order_type', '<>', 'non_motor_vehicle'], ['room_id', '=', $room_id], ['project_id', 'in', $project_ids]], 'modify_money');
                            $total_money += $sum;
                            $no_pay_sum += $sum;
                        } else {
                            $list[$k]['static'] = false;
                        }
                    } else {
                        $list[$k]['static'] = false;
                    }
                    if(empty($digit_info)){
                        $no_pay_sum = formatNumber($no_pay_sum,2,1);
                        $total_money = formatNumber($total_money,2,1);
                    }else{
                        $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                        $no_pay_sum = formatNumber($no_pay_sum,$digit_info['other_digit'],$digit_info['type']);
                        $total_money = formatNumber($total_money,$digit_info['other_digit'],$digit_info['type']);
                    }
                    $list[$k]['selected_pay_count'] = $no_pay_count;
                    $list[$k]['selected_pay_sum'] = $no_pay_sum;
                }
            } elseif ($year_1 && $static) {           //选中单个年份
                $count = 0;
                $total_money = 0.00;
                $db_house_new_select_project_log->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'action_name' => 'Selected', 'add_time' => time()]);
                foreach ($list as $k2 => $v2) {
                    $no_pay_count = 0;
                    $no_pay_sum = 0.00;
                    if ($v2['year'] == $year_1) {
                        $no_pay_list = $this->getNoPayDetail($v2['year'], $room_id);
                        if ($no_pay_list['list']) {
                            foreach ($no_pay_list['list'] as $v1) {
                                $record_info = $db_house_new_select_project_record->getList(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $v1['project_id'], 'year' => $v2['year']])->toArray();
                                if (empty($record_info)) {
                                    $db_house_new_select_project_record->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $v1['project_id'], 'year' => $v2['year'], 'add_time' => time()]);
                                }
                            }
                        }
                    }
                    $time = $this->getStartAndEndUnixTimestamp($v2['year']);
                    $record_list = $db_house_new_select_project_record->getList(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'year' => $v2['year']])->toArray();
                    if ($record_list) {
                        $list[$k2]['static'] = true;
                        $count += 1;
                        foreach ($record_list as $v1) {
                            $project_ids[] = $v1['project_id'];
                            $no_pay_count += 1;
                        }
                        $sum = $db_house_new_pay_order->getSum([['add_time', 'between', array($time['start'], $time['end'])], ['is_discard', '=', 1], ['is_paid', '=', 2],['order_type', '<>', 'non_motor_vehicle'], ['room_id', '=', $room_id], ['project_id', 'in', $project_ids]], 'modify_money');
                        $late_payment_money = $db_house_new_pay_order->getSum([['add_time', 'between', array($time['start'], $time['end'])], ['is_discard', '=', 1], ['is_paid', '=', 2], ['order_type', '<>', 'non_motor_vehicle'], ['room_id', '=', $room_id], ['project_id', 'in', $project_ids]], 'late_payment_money');
                        $total_money += $sum+$late_payment_money;
                        $no_pay_sum += $sum+$late_payment_money;
                        if(empty($digit_info)){
                            $no_pay_sum = formatNumber($no_pay_sum,2,1);
                            $total_money = formatNumber($total_money,2,1);
                        }else{
                            $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                            $no_pay_sum = formatNumber($no_pay_sum,$digit_info['other_digit'],$digit_info['type']);
                            $total_money = formatNumber($total_money,$digit_info['other_digit'],$digit_info['type']);
                        }
                    } else {
                        $list[$k2]['static'] = false;
                    }
                    if (count($list) == $count)
                        $is_all_select = true;
                    else
                        $is_all_select = false;
                    $list[$k2]['selected_pay_count'] = $no_pay_count;
                    $list[$k2]['selected_pay_sum'] = $no_pay_sum;
                }
            } elseif ($is_all_select && $type) {             //全选
                $no_pay_count = 0;
                $no_pay_sum = 0.00;
                $db_house_new_select_project_log->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'action_name' => 'selectedAll', 'add_time' => time()]);
                $total_money = 0.00;
                foreach ($list as $k3 => $v3) {
                    $total_money += $v3['no_pay_sum'];
                    $list[$k3]['selected_pay_count'] = $v3['no_pay_count'];
                    $list[$k3]['selected_pay_sum'] = $v3['no_pay_sum'];
                    $list[$k3]['static'] = true;
                    $no_pay_list = $this->getNoPayDetail($v3['year'], $room_id);
                    if ($no_pay_list['list']) {
                        foreach ($no_pay_list['list'] as $v1) {
                            $record_info = $db_house_new_select_project_record->getList(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $v1['project_id'], 'year' => $v3['year']])->toArray();
                            if (empty($record_info)) {
                                $db_house_new_select_project_record->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $v1['project_id'], 'year' => $v3['year'], 'add_time' => time()]);
                            }
                        }
                    }
                }
                $is_all_select = true;
            } elseif (!$is_all_select && $type) {             //取消全选
                $db_house_new_select_project_log->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'action_name' => 'unSelectedAll', 'add_time' => time()]);
                $total_money = 0.00;
                foreach ($list as $k4 => $v4) {
                    //$total_money += $v4['no_pay_sum'];
                    $list[$k4]['selected_pay_count'] = 0;
                    $list[$k4]['selected_pay_sum'] = 0.00;
                    $list[$k4]['static'] = false;
                    $db_house_new_select_project_record->del(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'year' => $v4['year']]);
                }
                $is_all_select = false;
            } else {
                $count = 0;
                $db_house_new_select_project_log->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'action_name' => 'returnBack', 'add_time' => time()]);
                $total_money = 0.00;
                foreach ($list as $k5 => $v5) {
                    $no_pay_count = 0;
                    $no_pay_sum = 0.00;
                    $time = $this->getStartAndEndUnixTimestamp($v5['year']);
                    $record_list = $db_house_new_select_project_record->getList(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'year' => $v5['year']])->toArray();
                    if ($record_list) {
                        $list[$k5]['static'] = true;
                        $count += 1;
                        $project_ids = [];
                        foreach ($record_list as $v1) {
                            $project_ids[] = $v1['project_id'];
                            $no_pay_count += 1;
                        }
                        $sum1 = $db_house_new_pay_order->getSum([['add_time', 'between', array($time['start'], $time['end'])], ['is_discard', '=', 1], ['is_paid', '=', 2], ['order_type', '<>', 'non_motor_vehicle'], ['room_id', '=', $room_id], ['project_id', 'in', $project_ids]], 'modify_money');
                        $sum2 = $db_house_new_pay_order->getSum([['add_time', 'between', array($time['start'], $time['end'])], ['is_discard', '=', 1], ['is_paid', '=', 2], ['order_type', '<>', 'non_motor_vehicle'], ['room_id', '=', $room_id], ['project_id', 'in', $project_ids]], 'late_payment_money');
                        $sum = $sum1+$sum2;
                        $total_money += $sum;
                        $no_pay_sum += $sum;
                    } else {
                        $list[$k5]['static'] = false;
                    }
                    if (count($list) == $count)
                        $is_all_select = true;
                    else
                        $is_all_select = false;
                    if(empty($digit_info)){
                        $no_pay_sum = formatNumber($no_pay_sum,2,1);
                        $total_money = formatNumber($total_money,2,1);
                    }else{
                        $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                        $no_pay_sum = formatNumber($no_pay_sum,$digit_info['other_digit'],$digit_info['type']);
                        $total_money = formatNumber($total_money,$digit_info['other_digit'],$digit_info['type']);
                    }
                    $list[$k5]['selected_pay_count'] = $no_pay_count;
                    $list[$k5]['selected_pay_sum'] = $no_pay_sum;
                }
            }
            $selected_count = 0;
            foreach ($list as &$v) {
                $selected_count += $v['selected_pay_count'];
                $v['selected_pay_sum'] = $v['selected_pay_sum'];
            }
        } else {
            $is_all_select = false;
            $total_money = 0.00;
            $selected_count = 0;
        }
        $res['list'] = $list;
        $res['is_all_select'] = $is_all_select;
        $res['total_money'] = $total_money;
        $res['selected_count'] = $selected_count;
        return $res;
    }

    /**
     * 未缴账单明细
     * @author lijie
     * @date_time 2021/07/07
     * @param string $year
     * @param int $room_id
     * @param int $page
     * @param int $project_id
     * @param int $pigcms_id
     * @param int $village_id
     * @param bool $is_all_select
     * @param bool $static
     * @param int $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNoPayDetail($year = '', $room_id = 0, $page = 1, $project_id = 0, $pigcms_id = 0, $village_id = 0, $is_all_select = true, $static = true, $type = 0)
    {
        if (empty($year) || !$room_id)
            return false;
        $time = $this->getStartAndEndUnixTimestamp($year);
        $where[] = ['o.add_time', 'between', array($time['start'], $time['end'])];
        $where[] = ['o.is_discard', '=', 1];
        $where[] = ['o.is_paid', '=', 2];
        $where[] = ['o.order_type','<>','non_motor_vehicle'];
        $where[] = ['o.room_id', '=', $room_id];
        $where[] = ['o.check_status','<>',1];
        if($village_id){
            $where[] = ['o.village_id', '=', $village_id];
        }
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($village_id,'property_id');
        $property_id = $village_info['property_id'];
        if($property_id){
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$property_id]);
        }else{
            $digit_info = [];
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_new_select_project_record = new HouseNewSelectProjectRecord();
        $data = $db_house_new_pay_order->getListByGroup($where, 'o.project_id,c.name', 0, 0, 'o.order_id DESC', 'o.project_id', $page);
        $list = [];
        $total_money = 0.00;
        $count = 0;
        if ($data) {
            foreach ($data as $k => $v) {
                $all_money = 0.00;
                $list[$k]['project_name'] = $v['name'];
                $list[$k]['project_id'] = $v['project_id'];
                if ($project_id && !$static) {
                    if ($v['project_id'] == $project_id) {
                        $db_house_new_select_project_record->del(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'year' => $year, 'project_id' => $project_id]);
                    }
                } elseif ($project_id && $static) {
                    if ($project_id == $v['project_id']) {
                        $record_info = $db_house_new_select_project_record->getList(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $project_id, 'year' => $year])->toArray();
                        if (empty($record_info)) {
                            $db_house_new_select_project_record->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $project_id, 'year' => $year, 'add_time' => time()]);
                        }
                    }
                } elseif ($is_all_select && $type) {
                    $record_info = $db_house_new_select_project_record->getList(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $v['project_id'], 'year' => $year])->toArray();
                    if (empty($record_info)) {
                        $db_house_new_select_project_record->addOne(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $v['project_id'], 'year' => $year, 'add_time' => time()]);
                    }
                } elseif (!$is_all_select && $type) {
                    $db_house_new_select_project_record->del(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'year' => $year]);
                }
                $record_info = $db_house_new_select_project_record->getList(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'project_id' => $v['project_id'], 'year' => $year])->toArray();
                if ($record_info) {
                    $list[$k]['static'] = true;
                    $count += 1;
                } else {
                    $list[$k]['static'] = false;
                }
                $where = [];
                $where[] = ['o.add_time', 'between', array($time['start'], $time['end'])];
                $where[] = ['o.is_discard', '=', 1];
                $where[] = ['o.is_paid', '=', 2];
                $where[] = ['o.room_id', '=', $room_id];
                $where[] = ['o.project_id', '=', $v['project_id']];
                $where[] = ['o.check_status','<>',1];
                $list[$k]['list'] = $db_house_new_pay_order->getList($where, 'o.add_time,o.modify_money as money,o.order_id,o.room_id,o.position_id,o.total_money,o.late_payment_money,o.modify_money,o.late_payment_day,o.project_id,o.order_name,o.uid,o.order_type')->toArray();
                foreach ($list[$k]['list'] as &$v2) {
                    if(empty($digit_info)){
                        $all_money += $v2['modify_money']+$v2['late_payment_money'];
                        $v2['money'] = formatNumber($v2['modify_money']+$v2['late_payment_money'],2,1);
                        if ($record_info) {
                            $total_money += $v2['modify_money']+$v2['late_payment_money'];
                        }
                    }else{
                        $all_money += $v2['modify_money']+$v2['late_payment_money'];
                        $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                        $v2['money'] = formatNumber($v2['modify_money']+$v2['late_payment_money'],$digit_info['other_digit'],$digit_info['type']);
                        if ($record_info) {
                            $total_money += $v2['modify_money']+$v2['late_payment_money'];
                        }
                    }
                }
                if(empty($digit_info)){
                    $all_money = formatNumber($all_money,2,1);
                    $total_money = formatNumber($total_money,2,1);
                }else{
                    $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                    $all_money = formatNumber($all_money,$digit_info['other_digit'],$digit_info['type']);
                    $total_money = formatNumber($total_money,$digit_info['other_digit'],$digit_info['type']);
                }
                $list[$k]['all_money'] = $all_money;
            }
            foreach ($list as $k => $value) {
                foreach ($value['list'] as $k1 => $v1) {
                    $list[$k]['list'][$k1]['add_time_txt'] = date('Y-m', $v1['add_time']);
                }
            }
        }
        if ($count == count($data) && !empty($data)) {
            $is_all_select = true;
        } else {
            $is_all_select = false;
        }
        $res['list'] = $list;
        $res['is_all_select'] = $is_all_select;
        $res['total_money'] = $total_money;
        $res['selected_count'] = $count;
        return $res;
    }

    /**
     * @param array $data
     * @return int|string
     */
    public function addLog($data = [])
    {
        $db_house_new_select_project_log = new HouseNewSelectProjectLog();
        $res = $db_house_new_select_project_log->addOne($data);
        return $res;
    }

    /**
     * 获取指定年月日的开始时间戳和结束时间戳(本地时间戳非GMT时间戳)
     * [1] 指定年：获取指定年份第一天第一秒的时间戳和下一年第一天第一秒的时间戳
     * [2] 指定年月：获取指定年月第一天第一秒的时间戳和下一月第一天第一秒时间戳
     * [3] 指定年月日：获取指定年月日第一天第一秒的时间戳
     * @param integer $year [年份]
     * @param integer $month [月份]
     * @param integer $day [日期]
     * @return array('start' => '', 'end' => '')
     */
    public function getStartAndEndUnixTimestamp($year = 0, $month = 0, $day = 0)
    {
        if (empty($year)) {
            $year = date("Y");
        }

        $start_year = $year;
        $start_year_formated = str_pad(intval($start_year), 4, "0", STR_PAD_RIGHT);
        $end_year = $start_year + 1;
        $end_year_formated = str_pad(intval($end_year), 4, "0", STR_PAD_RIGHT);

        if (empty($month)) {
            //只设置了年份
            $start_month_formated = '01';
            $end_month_formated = '01';
            $start_day_formated = '01';
            $end_day_formated = '01';
        } else {

            $month > 12 || $month < 1 ? $month = 1 : $month = $month;
            $start_month = $month;
            $start_month_formated = sprintf("%02d", intval($start_month));

            if (empty($day)) {
                //只设置了年份和月份
                $end_month = $start_month + 1;

                if ($end_month > 12) {
                    $end_month = 1;
                } else {
                    $end_year_formated = $start_year_formated;
                }
                $end_month_formated = sprintf("%02d", intval($end_month));
                $start_day_formated = '01';
                $end_day_formated = '01';
            } else {
                //设置了年份月份和日期
                $startTimestamp = strtotime($start_year_formated . '-' . $start_month_formated . '-' . sprintf("%02d", intval($day)) . " 00:00:00");
                $endTimestamp = $startTimestamp + 24 * 3600 - 1;
                return array('start' => $startTimestamp, 'end' => $endTimestamp);
            }
        }

        $startTimestamp = strtotime($start_year_formated . '-' . $start_month_formated . '-' . $start_day_formated . " 00:00:00");
        $endTimestamp = strtotime($end_year_formated . '-' . $end_month_formated . '-' . $end_day_formated . " 00:00:00") - 1;
        return array('start' => $startTimestamp, 'end' => $endTimestamp);
    }

    /**
     * 欠费发送模板通知
     * @author:zhubaodi
     * @date_time: 2021/7/5 14:37
     */
    public function getArrearsOrderList()
    {
        $db_house_new_charge = new HouseNewCharge();
        $db_house_new_charge_time = new HouseNewChargeTime();
        $db_house_village_user_bind = new HouseVillageUserBind();
        $service_house_village = new HouseVillageService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();

        $href = '';
        $where['o.is_paid'] = 2;
        $where['o.is_discard'] = 1;
        $data = $this->getOrderListByGroup($where, 'sum(o.total_money) as total_money,o.name,o.phone,v.room,p.position_num,o.room_id,o.position_id,o.uid', 0, 0, 'o.order_id DESC', 'o.room_id,o.position_id');
        foreach ($data as $v) {

            $charge_info = $db_house_new_charge->get_one(['village_id' => $v['village_id']]);
            if (empty($charge_info)) {
                continue;
            }
            $day = intval(substr(date('Y-m-d'), 8));
            if ($charge_info['call_date'] != $day) {
                continue;
            }
            $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $v['village_id']], 'property_id,property_name');
            if (empty($village_info)) {
                continue;
            }
            $charge_time = $db_house_new_charge_time->get_one(['property_id' => $village_info['property_id']]);
            if (empty($charge_time) || $charge_time['take_effect_time'] > time()) {
                continue;
            }

            if ($v['room_id']) {
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $v['room_id']], 'single_id,floor_id,layer_id,village_id');
                if ($vacancy_info) {
                    $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $v['room_id'], $vacancy_info['village_id']);
                } else {
                    $address = '';
                }
                if ($charge_info['call_type'] == 1) {
                    $user_list = $db_house_village_user_bind->getList(['vacancy_id' => $v['room_id'], 'status' => 1, 'type' => [0, 3]]);
                } elseif ($charge_info['call_type'] == 2) {
                    $user_list = $db_house_village_user_bind->getList(['vacancy_id' => $v['room_id'], 'status' => 1, 'type' => [0, 1, 3]]);
                } elseif ($charge_info['call_type'] == 3) {
                    $user_list = $db_house_village_user_bind->getList(['vacancy_id' => $v['room_id'], 'status' => 1, 'type' => [0, 1, 2, 3]]);
                }
                if (empty($user_list)) {
                    continue;
                } else {
                    $user_list = $user_list->toArray();
                    if (empty($user_list)) {
                        continue;
                    }
                    foreach ($user_list as $vv) {
                        $this->sendCashierMessage($vv['uid'], $href, $address, $village_info['property_name'], $v['total_money']);
                    }
                }
            } else {
                continue;
            }


        }
        return true;
    }

    /**
     * 生成欠费账单给业主发送模板通知
     * @author:zhubaodi
     * @date_time: 2021/7/5 14:37
     */
    public function getArrearsList()
    {
        $db_house_new_charge_time = new HouseNewChargeTime();
        $service_house_village = new HouseVillageService();
        $service_house_parking = new HouseVillageParkingService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();

        $href = '';
        $where['o.is_paid'] = 2;
        $where['o.is_discard'] = 1;
        $data = $this->getOrderListByGroup($where, 'sum(o.total_money) as total_money,o.name,o.phone,v.room,p.position_num,o.room_id,o.position_id,o.uid', 0, 0, 'o.order_id DESC', 'o.room_id,o.position_id');
        foreach ($data as $v) {

            $start = strtotime(date('Y-m-d 00:00:00', $v['add_time']));
            $end = strtotime(date('Y-m-d 23:59:59', $v['add_time']));
            if ($v['add_time'] < $start || $end < $v['add_time']) {
                continue;
            }
            $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $v['village_id']], 'property_id,property_name');
            if (empty($village_info)) {
                continue;
            }
            $charge_time = $db_house_new_charge_time->get_one(['property_id' => $village_info['property_id']]);
            if (empty($charge_time) || $charge_time['take_effect_time'] > time()) {
                continue;
            }

            if ($v['room_id']) {
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $v['room_id']], 'single_id,floor_id,layer_id,village_id');
                if ($vacancy_info) {
                    $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $v['room_id'], $vacancy_info['village']);
                } else {
                    $address = '';
                }
            } else {
                $garage_info = $service_house_parking->getParkingPositionDetail(['pp.position_id' => $v['position_id']], 'pp.position_num,pg.garage_num');
                if ($garage_info) {
                    $address = $garage_info['detail']['garage_num'] . '--' . $garage_info['detail']['position_num'];
                } else {
                    $address = '';
                }
            }
            $this->sendCashierMessage($v['uid'], $href, $address, $village_info['property_name'], $v['total_money']);
        }
        return true;
    }

    /**
     * 获取消费标准绑定列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @return mixed
     * @author lijie
     * @date_time 2021/07/06
     */
    public function getBindList($where = [], $field = true, $page = 0)
    {
        $db_house_new_charge_standard_bind = new HouseNewChargeStandardBind();
        $data = $db_house_new_charge_standard_bind->getList($where, $field, $page);
        return $data;
    }

    /**
     * 业主车辆列表
     * @param $bind_id
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     * @author lijie
     * @date_time 2021/07/08
     */
    public function getUserCarList($bind_id, $field = true, $page = 0, $limit = 15, $order = 'a.id DESC')
    {
        $db_house_village_bind_car = new HouseVillageBindCar();
        $data = $db_house_village_bind_car->user_bind_car_list($bind_id, [], $field, $page, $limit);
        if ($data) {
            foreach ($data as $k => $v) {
                if($v['end_time']>1)
                    $data[$k]['end_time_txt'] = date('Y-m-d', $v['end_time']);
                else
                    $data[$k]['end_time_txt'] = date('Y-m-d', time());
            }
        }
        return $data;
    }


    /**
     * 查询新版收费 统计两个收入最多的收费项目
     * @param $type 1:物业  2：小区
     * @param $property_id
     * @param $village_id
     * @return array
     * @author: liukezhu
     * @date : 2021/7/17
     */
    public function getChargeProjectType($type, $property_id, $village_id = 0, $num = 2)
    {
        $db_charge_time = new HouseNewChargeTime();
        $db_house_new_charge_service = new HouseNewChargeService();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $new_charge_time = $db_charge_time->get_one(['property_id' => $property_id]);
        $nowtime = time();
        //旧版收费标准
        $new_charge = 0;
        $data_list = $data_type = [];
        $where=[];
        $where[] = ['o.property_id', '=', $property_id];
        $where[] = ['o.is_paid', '=', 1];
        $where[] = ['o.is_discard','=',1];
        if ($type == 2) {
            $where[] = ['o.village_id', '=', $village_id];
        }
        $where['string']='o.refund_money < o.pay_money';
        if (!empty($new_charge_time) && $new_charge_time['take_effect_time'] < $nowtime && $new_charge_time['take_effect_time'] > 1) {
            //新版收费标准
            $new_charge = 1;

            if($type == 1){
                //物业  物业的用收费科目来统计数据
                $data_list = $db_house_new_pay_order->getMostChargeProject($where, 'o.project_id,sum( o.pay_money - o.refund_money ) num,n.charge_type', 'num desc,n.id desc', 'n.charge_type', $num);
            }else{
                //小区 小区的用收费项目来统计数据
                $data_list = $db_house_new_pay_order->getMostChargeProject($where, 'o.project_id,sum( o.pay_money - o.refund_money ) num,n.charge_type,p.name as project_name', 'num desc,n.id desc', 'o.project_id', $num);
            }
            if ($data_list) {
                $data_list = $data_list->toarray();
                foreach ($data_list as $k => &$v) {
                    $value = '';
                    foreach ($db_house_new_charge_service->charge_type_arr as $v2) {
                        if ($v2['key'] == $v['charge_type']) {
                            $value = $v2['value'];
                            continue;
                        }
                    }
                    $v['charge_name'] = $value;
                    $v['charge_param'] = $v['charge_type'];
                    $data_type[] = array('name' => ($type == 1 ? $value : $v['project_name']), 'color' => '');
                    unset($v['num']);
                }
                unset($v);
                $data_list[] = array(
                    'project_id' => array_column($data_list, 'project_id'),
                    'charge_type' => array_column($data_list, 'charge_type'),
                    'charge_name' => '其他收入',
                    'charge_param' => 'other',
                    'charge_param_type' => 1
                );
            }

        }
        $data_type[] = array('name' => '其他收入', 'color' => '');
        return ['status' => $new_charge, 'charge_data' => $data_list, 'charge_type' => $data_type];
    }

    /**查询订单金额
     * @param $where
     * @return mixed
     * @author: liukezhu
     * @date : 2021/7/17
     */
    public function getChargeProjectMoney($where)
    {
        $dbHouseNewPayOrder = new HouseNewPayOrder();
        $where[]=['is_discard','=',1];
        $where['string']='refund_money < pay_money';
        $field1='sum(pay_money) as pay_money,sum(refund_money) as refund_money';
        $order_info1=$dbHouseNewPayOrder->get_one($where,$field1);
        return $order_info1 ?  get_number_format($order_info1['pay_money'] - $order_info1['refund_money']) : 0;
//        return $dbHouseNewPayOrder->sumMoney($where);
    }

    /**
     * 自动生成账单
     * @author lijie
     * @date_time 2021/07/06
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function automaticCall()
    {
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        $village_id_arr = $serviceHouseNewPorperty->getTakeJudgeVillages();
        // 获取已经生效的小区ID集合
        $condition = [];
        if (!empty($village_id_arr)) {
            $condition[] = ['b.village_id','in',$village_id_arr];
        } else {
            return [];
        }
        $condition[] = ['b.is_del','=',1];
        $condition[] = ['r.bill_type','=',2];
        $condition[] = ['b.order_add_time','<=',time()];
        $data = $this->getBindList($condition,'p.type,b.*,r.bill_type,p.name,r.unit_price,r.rate,r.not_house_rate,r.unit_gage,r.fees_type,n.charge_type',0);
        if($data){
            $data = $data->toArray();
        }else{
            $data = [];
        }
        return $data;
    }

    /**
     * 单个房间自动生成账单
     * @author lijie
     * @date_time 2022/01/20
     * @param $v
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function call($v)
    {
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village = new HouseVillageService();
        $db_house_new_pay_order = new HouseNewPayOrder();
        if($v['vacancy_id']){
            $type = 1;
            $id = $v['vacancy_id'];
        }else{
            $type = 2;
            $id = $v['position_id'];
        }
        if($type == 2 && $v['fees_type'] == 2 && empty($v['unit_gage']))
            return false;
        if($v['charge_type'] == 'water' || $v['charge_type'] == 'electric' || $v['charge_type'] == 'gas')
            return false;
        $is_allow = $service_house_new_charge_rule->checkChargeValid($v['project_id'], $v['rule_id'],$id,$type);
        if (!$is_allow || $v['bill_type'] == 1 || ($v['order_add_time'] > (strtotime((date('Y-m-d',time()).' 23:59:59'))))){
            return false;
        }
        $rule_info = $service_house_new_charge_rule->getRuleInfo($v['rule_id']);
        if($v['order_add_type'] == 1){
            $beginTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
        }
        elseif($v['order_add_type'] == 2){
            $beginTime = mktime(0,0,0,date('m'),1,date('Y'));
            if($rule_info['bill_arrears_set'] == 2){
                if(date("t") != date("j"))
                    return false;
            }
        } else{
            if($rule_info['bill_arrears_set'] == 2){
                if(date('m-d',time()) != '12-31')
                    return false;
            }
            $beginTime = strtotime(date('Y').'-01-01');
        }
        $where = [];
        $where[] = ['service_end_time|add_time','>=',$beginTime];
        if($v['vacancy_id'])
            $where[] = ['room_id','=',$v['vacancy_id']];
        if($v['position_id'])
            $where[] = ['position_id','=',$v['position_id']];
        $where[] = ['project_id','=',$v['project_id']];
        $where[] = ['is_discard','=',1];
        $order_info = $db_house_new_pay_order->get_one($where, true);
        if($order_info){
            $order_info = $order_info->toArray();
        }
        if(!empty($order_info))
            return false;
        $cycle = isset($v['cycle'])?$v['cycle']:1;
        if($rule_info['cyclicity_set'] > 0){
            if($type == 1){
                $order_list = $db_house_new_pay_order->getList(['o.is_discard'=>1,'o.room_id'=>$v['vacancy_id'],'o.project_id'=>$v['project_id'],'o.position_id'=>0,'o.rule_id'=>$v['rule_id']],'o.service_month_num,o.service_give_month_num');
            } else{
                $order_list = $db_house_new_pay_order->getList(['o.is_discard'=>1,'o.position_id'=>$v['position_id'],'o.project_id'=>$v['project_id'],'o.rule_id'=>$v['rule_id']],'o.service_month_num,o.service_give_month_num');
            }
            if($order_list){
                $order_list = $order_list->toArray();
                if(count($order_list) >= $rule_info['cyclicity_set'])
                    return false;
                $order_count = 0;
                foreach ($order_list as $item){
                    if($item['service_month_num'] == 0)
                        $order_count += 1;
                    else
                        $order_count = $order_count+$item['service_month_num']+$item['service_give_month_num'];
                }
                if($order_count >= $rule_info['cyclicity_set'])
                    return false;
            }
        }
        $orderData = [];
        if($v['vacancy_id']){
            $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$v['vacancy_id']],['type','in',[0,3]],['status','=',1]],'pigcms_id,name,phone,uid');
            $orderData['uid'] = isset($user_info['uid'])?$user_info['uid']:0;
            $orderData['name'] = isset($user_info['name'])?$user_info['name']:'';
            $orderData['phone'] = isset($user_info['phone'])?$user_info['phone']:'';
            $orderData['room_id'] = isset($v['vacancy_id'])?$v['vacancy_id']:0;
        }
        if($v['position_id']){
            $orderData['position_id'] = $v['position_id'];
            $service_house_village_parking = new HouseVillageParkingService();
            $bind_position = $service_house_village_parking->getBindPosition(['position_id'=>$v['position_id']]);
            $user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$bind_position['user_id']],'pigcms_id,name,phone,vacancy_id');
            if($user_info){
                $postData['room_id'] = $user_info['vacancy_id']?$user_info['vacancy_id']:0;
            }
        }
        $orderData['village_id'] = $v['village_id'];
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$v['village_id']],'property_id');
        $db_house_property_digit_service = new HousePropertyDigitService();
        $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$village_info['property_id']]);
        $rule_digit=-1;
        if(isset($rule_info['rule_digit']) && $rule_info['rule_digit']>-1 && $rule_info['rule_digit']<5){
            $rule_digit=$rule_info['rule_digit'];
            if(!empty($digit_info)){
                $digit_info['meter_digit']=$rule_digit;
                $digit_info['other_digit']=$rule_digit;
            }else{
                $digit_info=array('type'=>1);
                $digit_info['meter_digit']=$rule_digit;
                $digit_info['other_digit']=$rule_digit;
            }
        }
        $orderData['property_id'] = isset($village_info['property_id'])?$village_info['property_id']:0;
        $orderData['order_name'] = $v['name'];
        $orderData['order_type'] = $v['charge_type'];
        $orderData['project_id'] = $v['project_id'];
        $orderData['rule_id'] = $v['rule_id'];
        $orderData['is_auto'] = 1;
        if($v['vacancy_id']){
            /*
            $condition1 = [];
            $condition1[] = ['vacancy_id','=',$v['vacancy_id']];
            $condition1[] = ['status','=',1];
            $condition1[] = ['type','in',[0,3,1,2]];
            $bind_list = $service_house_village_user_bind->getList($condition1,true);
            */
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $whereArrTmp=array();
            $whereArrTmp[]=array('pigcms_id','=',$v['vacancy_id']);
            $whereArrTmp[]=array('user_status','=',2);  // 2未入住
            $whereArrTmp[]=array('status','in',[1,2,3]);
            $whereArrTmp[]=array('is_del','=',0);
            $room_vacancy=$service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp);
            $not_house_rate = 100;
            if($room_vacancy && !$room_vacancy->isEmpty()){
                $room_vacancy = $room_vacancy->toArray();
                if(!empty($room_vacancy)){
                    $not_house_rate = $v['not_house_rate'];
                }
            }
        }else{
            $service_house_village_parking = new HouseVillageParkingService();
            $carInfo = $service_house_village_parking->getCar(['car_position_id'=>$v['position_id']]);
            if($carInfo){
                $carInfo = $carInfo->toArray();
            }
            if(empty($carInfo)){
                $not_house_rate = $v['not_house_rate'];
            } else{
                $not_house_rate = 100;
            }

        }
        if($not_house_rate<=0 || $not_house_rate>100){
            $not_house_rate=100;
        }
        if($v['fees_type'] == 1){
            $orderData['total_money'] = $v['unit_price'] * $v['rate'] * ($not_house_rate/100)*$cycle;
        }else{
            if(empty($v['custom_value'])){
                $custom_value = 1;
            } else{
                $custom_value = $v['custom_value'];
                $custom_number=$custom_value;
            }
            if(empty($custom_value)){
                $custom_value=1;
            }
            $orderData['total_money'] = $v['unit_price'] * $v['rate'] * ($not_house_rate/100) * $custom_value*$cycle;
        }
        if (!empty($digit_info)) {
            if ($orderData['order_type'] == 'water' || $orderData['order_type'] == 'electric' || $orderData['order_type'] == 'gas') {
                $orderData['total_money'] = formatNumber($orderData['total_money'], $digit_info['meter_digit'], $digit_info['type']);
            } else {
                $orderData['total_money'] = formatNumber($orderData['total_money'], $digit_info['other_digit'], $digit_info['type']);

            }
        }
        $orderData['total_money']=formatNumber($orderData['total_money'], 2, 1);
        $orderData['modify_money'] = $orderData['total_money'];
        $orderData['is_paid'] = 2;
        $orderData['is_prepare'] = 2;
        //$orderData['service_month_num'] = 1;
        $orderData['unit_price'] = $v['unit_price'];
        $orderData['add_time'] = time();
        $con = [];
        if($v['vacancy_id']){
            $con[] = ['room_id','=',$v['vacancy_id']];
            $con[] = ['position_id','=',0];
        }else{
            $con[] = ['position_id','=',$v['position_id']];
        }
        $con[] = ['project_id','=',$v['project_id']];
        $projectInfo = $this->getProjectInfo(['id'=>$v['project_id']],'subject_id');
        $numberInfo = $this->getNumberInfo(['id'=>$projectInfo['subject_id']],'charge_type');
        $con[] = ['order_type','=',$numberInfo['charge_type']];
        $order_info = $this->getOrderLog($con, 'service_end_time','id DESC');
        if($v['type'] == 2){
            $orderData['service_month_num'] = $cycle;
            if($numberInfo['charge_type'] == 'property'){
                if($type != 1){
                    $where22=[
                        ['position_id','=',$v['position_id']],
                        ['order_type','=',$numberInfo['charge_type']],
                    ];
                }else{
                    $where22=[
                        ['room_id','=',$v['vacancy_id']],
                        ['order_type','=',$numberInfo['charge_type']],
                        ['position_id','=',0]
                    ];
                }
                $new_order_log = $this->getOrderLog($where22,'*','id DESC');
                if(!empty($new_order_log)){
                    $order_info=$new_order_log;
                }
            }
            if($order_info && $order_info['service_end_time']>100){
                $orderData['service_start_time'] = $order_info['service_end_time']+1;
                $orderData['service_start_time'] = strtotime(date('Y-m-d',$orderData['service_start_time']));
            }elseif($v['order_add_time']){
                $orderData['service_start_time'] = strtotime(date('Y-m-d',$v['order_add_time']));
            }else{
                $orderData['service_start_time'] = strtotime(date('Y-m-d',$v['charge_valid_time']));
            }
            if($v['order_add_type'] == 1){
                $orderData['service_end_time'] = strtotime("+$cycle day",$orderData['service_start_time'])-1;
            } elseif($v['order_add_type'] == 2){
                //todo 判断是不是按照自然月来生成订单
                if(cfg('open_natural_month') == 1){
                    $orderData['service_end_time'] = strtotime("+$cycle month",$orderData['service_start_time'])-1;
                }else{
                    $cycle = $cycle*30;
                    $orderData['service_end_time'] = strtotime("+$cycle day",$orderData['service_start_time'])-1;
                }
            } else{
                $orderData['service_end_time'] = strtotime("+$cycle year",$orderData['service_start_time'])-1;
            }
        }else{
            $orderData['service_start_time'] = time();
            $orderData['service_end_time'] = time();
        }
        $contract_info = $service_house_new_charge_rule->getHouseVillageInfo(['village_id'=>$v['village_id']],'contract_time_start,contract_time_end');
        if(isset($contract_info['contract_time_start']) && $contract_info['contract_time_start'] > 1){
            if($orderData['service_end_time'] < $contract_info['contract_time_start'] || $orderData['service_end_time'] > $contract_info['contract_time_end']){
                return false;
            }
            if($orderData['service_start_time'] < $contract_info['contract_time_start'] || $orderData['service_start_time'] > $contract_info['contract_time_end']){
                return false;
            }
        }
        if($not_house_rate>0 && $not_house_rate<100){
            $orderData['not_house_rate'] = $not_house_rate;
        }
        if(isset($custom_number)){
            $orderData['number'] = $custom_number;
        }
        $res = $this->addOrder($orderData);
        if($res && $orderData['order_type'] == 'property' && intval(cfg('cockpit')) && isset($orderData['pigcms_id']) && !empty($orderData['pigcms_id'])){
            $remarks='自动生成账单，物业费自动扣除余额';
            (new StorageService())->userBalanceChange($orderData['pigcms_id'],2,$orderData['modify_money'],$remarks,$remarks,$res);
        }
        fdump_api([$orderData,$res,__LINE__],'0915-6',true);
        return true;
    }

    public function delRecord($where=[])
    {
        $db_house_new_select_project_record = new HouseNewSelectProjectRecord();
        $res = $db_house_new_select_project_record->del($where);
        return $res;
    }

    /**
     * 收取收费项目服务到期时间
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderLog($where=[],$field=true,$order='id DESC')
    {
        $db_house_new_order_log = new HouseNewOrderLog();
        $data = $db_house_new_order_log->getOne($where,$field,$order);
        return $data;
    }

    public function queryScanPay($order_no='',$pay_type='',$summary_id=0)
    {
        if(empty($order_no) || empty($pay_type) || !$summary_id)
            return api_output_error(1001,'缺少必传参数');
        $service_pay = new PayService();
        $res = $service_pay->queryScanPay($order_no,$pay_type);
        if($res['status'] == 1){
            $service_new_pay = new NewPayService();
            $service_plat_order = new PlatOrderService();
            $plat_order['pay_type'] = $res['pay_type'];
            $plat_order['orderid'] = $res['order_no'];
            $plat_order['third_id'] = $res['transaction_no'];
            $plat_order['pay_time'] = time();
            $plat_order['paid'] = 1;
            $plat_id = $service_plat_order->savePlatOrder(['business_id'=>$summary_id,'business_type '=>'village_new_pay'],$plat_order);
            $res = $service_new_pay->offlineAfterPay($summary_id);
            return ['status'=>1];
        }elseif ($res['status'] == 2){
            $this->queryScanPay($order_no,$pay_type,$summary_id);
        }else{
            return ['status'=>0];
        }
    }

    /**
     * 补足残月剩余天数的账单
     * @author lijie
     * @date_time 2021/11/09
     * @param string $month
     * @param string $year
     * @param string $day
     * @param string $time
     * @param array $postData
     * @param array $charge_standard_bind_info
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addSingleDayOrder($month='',$year='',$day='',$time='',$postData=[],$charge_standard_bind_info=[])
    {
        if(empty($month) || empty($year) || empty($day) || empty($time) || empty($charge_standard_bind_info) || empty($postData))
            return false;
        $days = getMonthLastDay($month,$year);//获取所属月总共的天数
        if(substr($day,0,1) == 0){
            $day = ltrim($day,0);
        }
        $dateArr = [];  //残月需要缴费的单日列表
        for($i=0;$i<=$days-$day;$i++){
            if($i == 0){
                $dateArr[$i] = strtotime($time);
            }else{
                $dateArr[$i] = $dateArr[$i-1]+24*60*60;
            }
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $where = [];
        if($charge_standard_bind_info['vacancy_id']){
            $where[] = ['o.room_id','=',$charge_standard_bind_info['vacancy_id']];
            $where1[] = ['o.room_id','=',$charge_standard_bind_info['vacancy_id']];
        }else{
            $where[] = ['o.position_id','=',$charge_standard_bind_info['position_id']];
            $where1[] = ['o.position_id','=',$charge_standard_bind_info['position_id']];
        }
        $where[] = ['o.project_id','=',$charge_standard_bind_info['project_id']];
        $where[] = ['o.is_discard','=',1];
        $where[] = ['o.is_paid','=',2];
        $where[] = ['o.is_auto','=',2];
        $count = $db_house_new_pay_order->getCount($where);
        $where1[] = ['o.project_id','=',$charge_standard_bind_info['project_id']];
        $where1[] = ['o.is_discard','=',1];
        $where1[] = ['o.is_paid','=',2];
        $count1 = $db_house_new_pay_order->getCount($where1);
        $count = $count+$count1;
        if($count == count($dateArr)){
            $dateArr = [];
        }elseif (count($dateArr) < $count){
            $dateArr = [];
        }else{
            array_splice($dateArr, 0, $count);
        }
        if(!empty($dateArr)){
            $service_house_new_charge_rule = new HouseNewChargeRuleService();
            $service_house_village_user_bind = new HouseVillageUserBindService();
            $rule_list = $service_house_new_charge_rule->getRuleListOfDay([['village_id','=',$postData['village_id']],['charge_project_id','=',$charge_standard_bind_info['project_id']],['status','=',1],['bill_create_set','=',1]],true,'charge_valid_time DESC');
            if($rule_list){
                foreach ($rule_list as $val){
                    foreach ($dateArr as $k=>$v){
                        if($val['charge_valid_time'] <= $v){
                            if($charge_standard_bind_info['vacancy_id']){
                                /*
                                $condition1 = [];
                                $condition1[] = ['vacancy_id','=',$charge_standard_bind_info['vacancy_id']];
                                $condition1[] = ['status','=',1];
                                $condition1[] = ['type','in',[0,3,1,2]];
                                $bind_list = $service_house_village_user_bind->getList($condition1,true);
                                */
                                $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
                                $whereArrTmp=array();
                                $whereArrTmp[]=array('pigcms_id','=',$charge_standard_bind_info['vacancy_id']);
                                $whereArrTmp[]=array('user_status','=',2);  // 2未入住
                                $whereArrTmp[]=array('status','in',[1,2,3]);
                                $whereArrTmp[]=array('is_del','=',0);
                                $room_vacancy=$service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp);
                                $not_house_rate = 100;
                                if($room_vacancy && !$room_vacancy->isEmpty()){
                                    $room_vacancy = $room_vacancy->toArray();
                                    if(!empty($room_vacancy)){
                                        $not_house_rate = $val['not_house_rate'];
                                    }
                                }
                                $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$val['charge_project_id'],'rule_id'=>$val['id'],'vacancy_id'=>$charge_standard_bind_info['vacancy_id']]);
                            }else{
                                $service_house_village_parking = new HouseVillageParkingService();
                                $carInfo = $service_house_village_parking->getCar(['car_position_id'=>$charge_standard_bind_info['position_id']]);
                                if($carInfo){
                                    $carInfo = $carInfo->toArray();
                                }
                                if(empty($carInfo))
                                    $not_house_rate = $val['not_house_rate'];
                                else
                                    $not_house_rate = 100;
                            }
                            if($charge_standard_bind_info['position_id']){
                                $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$val['charge_project_id'],'rule_id'=>$val['id'],'position_id'=>$charge_standard_bind_info['position_id']]);
                            }
                            if(isset($projectBindInfo) && !empty($projectBindInfo)){
                                if($projectBindInfo['custom_value']){
                                    $custom_value = $projectBindInfo['custom_value'];
                                    $custom_number = $custom_value;
                                }else{
                                    $custom_value = 1;
                                }
                            }else{
                                $custom_value = 1;
                            }
                            if($val['fees_type'] == 1){
                                $postData['total_money'] = $val['charge_price']*$not_house_rate/100*$val['rate'];
                                $postData['modify_money'] = $postData['total_money'];
                            }else{
                                $postData['total_money'] = $val['charge_price']*$not_house_rate/100*$custom_value*$val['rate'];
                                $postData['modify_money'] = $postData['total_money'];
                            }
                            $postData['service_start_time'] = $v;
                            $postData['service_end_time'] = $postData['service_start_time']+24*60*60-1;
                            $postData['unit_price'] = $val['charge_price'];
                            if($charge_standard_bind_info['vacancy_id']){
                                $postData['room_id'] = $charge_standard_bind_info['vacancy_id'];
                                $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$charge_standard_bind_info['vacancy_id']],['type','in','0,3'],['status','=',1]],'pigcms_id,name,phone');
                                if($user_info){
                                    $postData['pigcms_id'] = $user_info['pigcms_id']?$user_info['pigcms_id']:0;
                                    $postData['name'] = $user_info['name']?$user_info['name']:'';
                                    $postData['phone'] = $user_info['phone']?$user_info['phone']:'';
                                }
                            }
                            if($charge_standard_bind_info['position_id']){
                                $service_house_village_parking = new HouseVillageParkingService();
                                $postData['position_id'] = $charge_standard_bind_info['position_id'];
                                $bind_position = $service_house_village_parking->getBindPosition(['position_id'=>$postData['position_id']]);
                                $user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$bind_position['user_id']],'pigcms_id,name,phone,vacancy_id');
                                if($user_info){
                                    $postData['pigcms_id'] = $user_info['pigcms_id']?$user_info['pigcms_id']:0;
                                    $postData['name'] = $user_info['name']?$user_info['name']:'';
                                    $postData['phone'] = $user_info['phone']?$user_info['phone']:'';
                                    $postData['room_id'] = $user_info['vacancy_id']?$user_info['vacancy_id']:0;
                                }
                            }
                            $postData['rule_id'] = $val['id'];
                            $postData['prepaid_cycle'] = 0;
                            $postData['service_month_num'] = 0;
                            $postData['service_give_month_num'] = 0;
                            $postData['diy_content'] = '';
                            $postData['diy_type'] = 0;
                            $postData['rate'] = 100;
                            $postData['is_auto'] = 2;
                            if($not_house_rate>0 && $not_house_rate<100){
                                $postData['not_house_rate'] = $not_house_rate;
                            }
                            if(isset($custom_number)){
                                $postData['number'] = $custom_number;
                            }
                            $res = $this->addOrder($postData);
                            if($res){
                                unset($dateArr[$k]);
                                $dateArr = array_values($dateArr);
                            }

                        }
                    }
                }
            }
        }
    }

    /**
     * 获取应收明细年月应收金额统计
     * User: zhanghan
     * Date: 2022/1/11
     * Time: 9:32
     * @param $param
     * @return array
     */
    public function getOrderStatisticsByYears($param){
        $db_house_new_pay_order = new HouseNewPayOrder();

        $where = [];
        // 房间
        if($param['type'] == 'room'){
            $where[] = ['room_id','=',$param['key_id']];
            $where[] = ['position_id','=',0];
        } else{
            // 车位
            $where[] = ['position_id','=',$param['key_id']];
        }

        $where[] = ['is_paid','=',2];
        $where[] = ['is_discard','=',1];
        $where[] = ['order_type','<>','non_motor_vehicle'];
        $where[] = ['village_id','=',$param['village_id']];
        $data = [
            'list' => [],
            'count' => 0,
            'source' => 1
        ];
        // 获取第一笔应收明细的时间作为起始年
        $first_order = $db_house_new_pay_order->get_one($where,'add_time,property_id','add_time asc');

        if($first_order && !$first_order->isEmpty()){
            $first_order = $first_order->toArray();
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$first_order['property_id']]);

            $first_year = date('Y',$first_order['add_time']);
            $first_month = date('n',$first_order['add_time']);
            // 当前年
            $cur_year = date('Y');

            $list = [];
            $count = 0;
            // 查询每年
            if(empty($param['year'])){
                for($i = $cur_year;$i >= $first_year; $i--){
                    $count++;
                    // 起始年
                    $start_time = strtotime($i.'-01-01');
                    $end_time = strtotime($i.'-12-31 23:59:59');

                    $where_year = $where;
                    $where_year[] = ['add_time','>=',$start_time];
                    $where_year[] = ['add_time','<=',$end_time];
                    // 统计每年金额
                    $modify_money = $db_house_new_pay_order->getSum($where_year,'total_money');
                    if(empty($modify_money)){
                        continue;
                    }

                    if(empty($digit_info)){
                        $modify_money = formatNumber($modify_money,2,1);
                    }else{
                        $modify_money = formatNumber($modify_money,$digit_info['other_digit'],$digit_info['type']);
                    }

                    $list[] = [
                        'title' => $i.'年账单',
                        'number' => (int)$i,
                        'money' => $modify_money
                    ];
                    // 最多循环次数
                    if($count > 20){
                        break;
                    }
                }
            }else{
                // 查询每月
                $end_month = 12; // 默认年的最后一月
                $start_month = 1; // 默认年的第一天
                if($first_year == $param['year']){
                    // 查询的是第一年的每月数据 起始月为第一笔订单的月份
                    $start_month = $first_month;
                }
                if ($param['year'] == $cur_year){
                    // 查询的是当前年的每月数据 最后一个月为当前月
                    $end_month = date('n',time());
                }
                for($i = $end_month;$i >= $start_month; $i--){
                    $count++;
                    // 起始月
                    $start_time = strtotime($param['year'].'-'.$i.'-01');
                    $end_time = strtotime("+1 month",$start_time)-1;

                    $where_month = $where;
                    $where_month[] = ['add_time','>=',$start_time];
                    $where_month[] = ['add_time','<=',$end_time];
                    // 统计每月金额
                    $modify_money = $db_house_new_pay_order->getSum($where_month,'total_money');
                    if(empty($modify_money)){
                        continue;
                    }

                    if(empty($digit_info)){
                        $modify_money = formatNumber($modify_money,2,1);
                    }else{
                        $modify_money = formatNumber($modify_money,$digit_info['other_digit'],$digit_info['type']);
                    }

                    $list[] = [
                        'title' => $param['year'].'年'.$i.'月账单详情',
                        'number' => (int)$i,
                        'month' => $param['year'].'-'.$i,
                        'money' => $modify_money
                    ];
                    // 最多循环次数
                    if($count > 12){
                        break;
                    }
                }
            }
            $data = [
                'list' => $list,
                'count' => $count,
                'source' => 1
            ];
        }
        return $data;
    }


    /**
     * 新老版收费更替时同步物业服务时间
     * @author:zhubaodi
     * @date_time: 2022/2/9 16:11
     */
    public function autoAddOrderLog($data)
    {
        $db_house_village_user_bind = new HouseVillageUserBind();
        $db_house_village = new HouseVillage();
        $db_house_new_order_log = new HouseNewOrderLog();
        $village_id_arr = $db_house_village->getList(['property_id' => $data['property_id'], 'status' => 1], 'village_id');
        if (!empty($village_id_arr)) {
            $village_id_arr = $village_id_arr->toArray();
            $arr_village_id = [];
            foreach ($village_id_arr as $vv) {
                $arr_village_id[] = $vv['village_id'];
            }
            $condition1 = [];
            $condition1[] = ['village_id', 'in', $arr_village_id];
            $condition1[] = ['status', '=', 1];
            $condition1[] = ['type', 'in', [0, 3]];
            $condition1[] = ['property_endtime', '>', 0];
            $field = 'pigcms_id,village_id,uid,type,vacancy_id,status,property_endtime,property_starttime';
            $bind_list = $db_house_village_user_bind->getList($condition1, $field);
            if (!empty($bind_list)) {
                $bind_list = $bind_list->toArray();
                if (!empty($bind_list)) {
                    foreach ($bind_list as $v) {
                        $where = [];
                        $where[] = ['village_id', '=', $v['village_id']];
                        $where[] = ['room_id', '=', $v['vacancy_id']];
                        $where[] = ['order_type', '=', 'property'];
                        $log_info = $db_house_new_order_log->getOne($where, true);
                        if (empty($log_info)) {
                            $arr = [];
                            $arr['order_type'] = 'property';
                            $arr['order_name'] = '物业费';
                            $arr['room_id'] = $v['vacancy_id'];
                            $arr['property_id'] = $data['property_id'];
                            $arr['village_id'] = $v['village_id'];
                            $arr['service_start_time'] = $v['property_starttime'];
                            $arr['service_end_time'] = $v['property_endtime'];
                            $arr['desc'] = '新老版收费更替同步物业服务时间';
                            $arr['add_time'] = time();
                            $db_house_new_order_log->addOne($arr);
                        }
                    }
                }
            }
        }
    }


    /**
     * 物业费预缴 触发未缴物业费扣款
     * @author: liukezhu
     * @date : 2022/2/8
     * @param $village_id
     * @param $pigcms_id
     * @return bool
     */
    public function userUnpaidOrder($village_id,$pigcms_id){
        if(!intval(cfg('cockpit'))){
            return false;
        }
        $StorageService=new StorageService();
        $where[] = ['is_paid','=',2];
        $where[] = ['order_type','=','property'];
        $where[] = ['is_discard','=',1];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['pigcms_id','=',$pigcms_id];
        $field='order_id,pigcms_id,modify_money';
        $order='modify_money asc,order_id asc';
        $list=(new HouseNewPayOrder())->getOrder($where,$field,$order);
        if(!empty($list)){
            $list=$list->toArray();
        }
        if(empty($list)){
            return false;
        }
        $remarks='物业预缴，触发自动扣除余额';
        foreach ($list as $v){
            $StorageService->userBalanceChange($v['pigcms_id'],2,$v['modify_money'],$remarks,$remarks,$v['order_id']);
        }
        return true;
    }

    public function getNewProperty(){
        $db_house_new_charge_time=new HouseNewChargeTime();
        $where=[];
        $where[]=['take_effect_time','<=',time()];
        $property_list=$db_house_new_charge_time->getList($where,'property_id');
        if (!empty($property_list)){
            $property_list=$property_list->toArray();
            if (!empty($property_list)){
                foreach ($property_list as $v){
                    $this->autoAddOrderLog($v);
                }
            }
        }

    }

    /**
     * 是否允许打印
     * User: zhanghan
     * Date: 2022/2/17
     * Time: 14:03
     * @param $village_id
     * @param $template_id
     * @param $order_ids
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isAllowPrint($village_id,$template_id,$order_ids){
        $db_village_info = new HouseVillageInfo();
        $db_print_number = new HouseVillagePrintTemplateNumber();
        $print_number_times = 0;
        // 获取打印配置
        $village_info_config = $db_village_info->getOne([['village_id','=',$village_id]],'print_number_times');
        if($village_info_config && !$village_info_config->isEmpty()){
            $village_info_config = $village_info_config->toArray();
            $print_number_times = $village_info_config['print_number_times'];
        }
        // 不允许
        if($print_number_times < 1){
            // 查询当前订单及模板，是否已有记录
            $where = [];
            $where[] = ['print_template_id','=',$template_id];
            $where[] = ['order_ids','=',implode(',',$order_ids)];
            $res = $db_print_number->getOne($where);
            if(!empty($res)){
                return 0;
            }else{
                // 判断订单中是否存在订单有过打印
                $isExistence = false;
                foreach ($order_ids as $valu){
                    $where = [];
                    $where[] = ['order_ids','find in set',$valu];
                    $res = $db_print_number->getOne($where);
                    if(!empty($res)){
                        $isExistence = true;
                        break;
                    }
                }
                if($isExistence){
                    return (count($order_ids) > 1) ? 2 : 1;
                }else{
                    return 0;
                }
            }
        }
        return 0;
    }
}