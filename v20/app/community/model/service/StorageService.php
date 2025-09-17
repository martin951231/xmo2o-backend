<?php
/**
 * @author : liukezhu
 * @date : 2021/11/17
 */
namespace app\community\model\service;

use app\common\model\db\PaidOrderRecord;
use app\common\model\db\UserMoneyList;
use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewOfflinePay;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\db\HouseVillageConfig;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\PlatOrder;
use app\community\model\db\User;
use app\community\model\db\UserRechargeOrder;
use app\community\model\db\UserSet;
use app\community\model\db\VillageUserMoneyList;
use app\community\model\db\VillageUserMoneyLog;
use app\community\model\service\HouseVillageService;

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;
use think\facade\Request;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\CustomizeMeterWaterReadingService;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
class StorageService{

    use CommonLogTraits;
    use AdminLoginTraits;
    public $charge_type;
    public $usr_set;
    public $charge_name_arr;
    public $HouseNewChargeService;
    public $UserRechargeOrder;
    public $HouseVillageUserBind;
    public $HouseVillageUserService;
    public $HouseNewPayOrder;
    public $UserMoneyList;
    public $UserService;
    public $UserSet;
    public $User;
    public $HouseVillageUserVacancy;
    public $HouseVillageService;
    public $HouseNewCashierService;
    public $HouseVillage;
    public $HouseVillageParkingPosition;
    public $HouseNewChargeProject;
    public $HouseNewPayOrderSummary;
    public $Dompdf;
    public $HousePropertyService;
    public $PlatOrder;
    public $customized_meter_reading=0;  // 定制的

    public function __construct()
    {
        $this->charge_type=['electric','water','gas'];
        $this->usr_set=[
            'water'=>'is_water_automat',
            'electric'=>'is_electric_automat',
            'gas'=>'is_gas_automat',
        ];
        $this->HouseNewChargeService =  new HouseNewChargeService();
        $this->charge_name_arr=$this->HouseNewChargeService->charge_type;
        $customized_meter_reading=cfg('customized_meter_reading');
        $customized_meter_reading=$customized_meter_reading ? intval($customized_meter_reading):0;
        if($customized_meter_reading>0){
            $this->customized_meter_reading=1;
            $this->charge_type=['water','hotwater','electric','villagebalance','gas'];
            $this->charge_name_arr['water']='冷水费';
            $this->charge_name_arr['hotwater']='热水费';
            $this->charge_name_arr['villagebalance']='燃气费';
            $this->usr_set=[
                'water'=>'is_water_automat',
                'electric'=>'is_electric_automat',
                'gas'=>'is_gas_automat',
                'hotwater'=>'is_water_hot_automat',
                'villagebalance'=>'is_village_balance_automat',
            ];
        }
        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if($jz_wsxdsj_customized_open){
            $this->charge_type[] = 'qunuanfei';
            $this->usr_set['qunuanfei'] = 'is_qunuanfei_automat';
            $this->charge_name_arr['qunuanfei'] = '取暖费';
        }
        
        $this->UserRechargeOrder =  new UserRechargeOrder();
        $this->HouseVillageUserBind =  new HouseVillageUserBind();
        $this->HouseVillageUserService =  new HouseVillageUserService();
        $this->UserMoneyList =  new UserMoneyList();
        $this->UserService =  new UserService();
        $this->HouseNewPayOrder = new HouseNewPayOrder();
        $this->UserSet =  new UserSet();
        $this->User =  new User();
        $this->HouseVillageUserVacancy = new HouseVillageUserVacancy();
        $this->HouseVillageService=new HouseVillageService();
        $this->HouseNewCashierService = new HouseNewCashierService();
        $this->HouseVillage = new HouseVillage();
        $this->HouseVillageParkingPosition = new HouseVillageParkingPosition();
        $this->HouseNewChargeProject = new HouseNewChargeProject();
        $this->HouseNewPayOrderSummary = new HouseNewPayOrderSummary();
        $this->Dompdf = new Dompdf();
        $this->HousePropertyService = new HousePropertyService();
        $this->PlatOrder = new PlatOrder();
    }

    /**
     * 获取userbind信息
     * @author: liukezhu
     * @date : 2021/11/17
     * @param $pigcms_id
     * @param bool $field
     * @return array
     */
    public function getUserBind($pigcms_id,$field=true){
        $where[] = ['pigcms_id','=',$pigcms_id];
        $data=$this->HouseVillageUserBind->getOne($where,$field);
        return $data ? $data->toArray() : [];
    }
    public function getHouseVillageConfig($village_id=0,$field='*'){
        $db_house_village_config=new HouseVillageConfig();
        $house_village_config=$db_house_village_config->getOne(['village_id'=>$village_id],$field);
        $village_config=array();
        if($house_village_config && !$house_village_config->isEmpty()){
            $village_config=$house_village_config->toArray();
        }
        return $village_config;
    }
    /**
     *获取预存类型
     * @author: liukezhu
     * @date : 2021/11/17
     * @return array
     */
    public function getType($village_id=0){
        $db_house_village_config=new HouseVillageConfig();
        $can_use_village_balance=false;
        $use_village_balance=$db_house_village_config->getOne(['village_id'=>$village_id],'open_village_balance');
        if ($use_village_balance['open_village_balance']==1){
            $can_use_village_balance=true;
        }
        if($this->customized_meter_reading && $can_use_village_balance){
            $charge_img=[
                'water' => '/static/images/storage/shufei.png',
                'electric' => '/static/images/storage/dianfei.png',
                'gas' => '/static/images/storage/rangqifei.png',
            ];
            $type=[];
            $type[]=array('type'=> "water", 'value'=> "冷水",'check_payment_status'=>1, 'img'=>cfg('site_url'). "/static/images/storage/shufei.png");
            $type[]=array('type'=> "hotwater", 'value'=> "热水",'check_payment_status'=>1, 'img'=> cfg('site_url')."/static/community/house_new/shuihotfei.png");
            $type[]=array('type'=> "electric", 'value'=> "电费",'check_payment_status'=>1, 'img'=> cfg('site_url')."/static/images/storage/dianfei.png");
            //$type[]=array('type'=> "villagebalance", 'value'=> "燃气费",'check_payment_status'=>1, 'img'=> cfg('site_url')."/static/images/storage/rangqifei.png");
            return $type;
        }else{
            $charge_type=$this->HouseNewChargeService->charge_type;
            $charge_img=[
                'water' => '/static/images/storage/shufei.png',
                'electric' => '/static/images/storage/dianfei.png',
                'gas' => '/static/images/storage/rangqifei.png',
            ];
            $type=[];
            $charge_type_arr=['electric','water','gas'];
            $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            if($jz_wsxdsj_customized_open){
                $charge_img['qunuanfei'] = '/static/images/storage/qunuanfei.png';
                $charge_type_arr[] = 'qunuanfei';
            }
            foreach ($charge_type as $k=>$v){
                if(in_array($k,$charge_type_arr)){
                    $v = $k=='qunuanfei' ? '取暖费' : $v;
                    $type[]=[
                        'type'=>$k,
                        'value'=>$v,
                        'check_payment_status'=>0,
                        'img'=>cfg('site_url').$charge_img[$k]
                    ];
                }
            }
            return $type; 
        }

    }

    /**
     * 预存记录
     * @author: liukezhu
     * @date : 2021/11/17
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     */
    public function getRecord($where,$field=true,$page=0,$limit=10,$order='order_id DESC'){
        $list = $this->UserRechargeOrder->getList($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                if(isset($v['pay_time']) && !empty($v['pay_time'])){
                    $v['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
                }
                if(isset($v['label']) && !empty($v['label'])){
                    $label=explode('_',$v['label']);
                    $v['label']=$this->charge_name_arr[$label[1]];
                }
                if(isset($v['money']) && !empty($v['money'])){
                    $v['money']=$v['money'].'元';
                }
            }
            unset($v);
        }
        $count = $this->UserRechargeOrder->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 获取预存
     * @author: liukezhu
     * @date : 2021/11/17
     * @param $pigcms_id
     * @param $uid
     * @param $type
     * @param int $village_id
     * @return array
     * @throws \think\Exception
     */
    public function getStorage($pigcms_id,$uid,$type,$village_id=0,$is_new=0){
        if(empty($type) || !in_array($type,$this->charge_type)){
            throw new \think\Exception("预存类型不存在");
        }
        $where=[];
        $where[] = ['uid','=',$uid];
        $user=$this->UserService->getUserOne($where,'uid,now_money');
        if($user){
            $user=$user->toArray();
        }
        if(empty($user)){
            throw new \think\Exception("【平台】该用户不存在");
        }
        $user_bind = $this->getUserBind($pigcms_id,'name');
        if(empty($user_bind)){
            throw new \think\Exception("【社区】该用户未入住");
        }
        $is_automat_pay=0;
        $is_show_automat_pay=1;
        $villageUser=$this->getVillageUser($uid,$village_id);
        $can_use_village_balance=$villageUser['can_use_village_balance'];
        if($this->customized_meter_reading && $can_use_village_balance){
            $is_automat_pay=1;
            $is_show_automat_pay=0;
        }else{
            $where=[];
            $where[] = ['uid','=',$uid];
            $user_set_filed=$this->getUserSetFiled($type);
            $user_set=$this->UserSet->getOne($where,$user_set_filed);
            if($user_set && intval($user_set[$user_set_filed]) == 1){
                $is_automat_pay=1;
            }
        }
        $now_money=$user['now_money'];
        $url=cfg('site_url').'/wap.php?g=Wap&c=My&a=recharge&label=village_'.$type.'_'.$village_id.'_'.$pigcms_id;
        $houseVillageService=new HouseVillageService();
        if($is_new==1){
            $base_url=$houseVillageService->base_url;
            $url=cfg('site_url').$base_url.'pages/houseMeter/preStorage/preStoragePay?label=village_'.$type.'_'.$village_id.'_'.$pigcms_id;
        }
        $storage_type=$this->charge_name_arr[$type];
        if($can_use_village_balance){
            $now_money=$villageUser['current_money'];
            if($this->customized_meter_reading && $type=='water'){
                $now_money=$villageUser['cold_water_balance'];
            }
            if($this->customized_meter_reading && $type=='hotwater'){
                $now_money=$villageUser['hot_water_balance'];
            }
            if($this->customized_meter_reading && $type=='electric'){
                $now_money=$villageUser['electric_balance'];
            }
        }
        $now_money=round($now_money,2);
        
        $data=[
            'user_money'=>$now_money,
            'user_name'=>$user_bind['name'],
            'storage_type'=>$storage_type,
            'type'=>$type,
            'is_automat_pay'=> $is_automat_pay,
            'is_show_automat_pay'=>$is_show_automat_pay,
            'url'=>$url
        ];
        return $data;
    }


    /**
     * 获取字段
     * @author: liukezhu
     * @date : 2021/11/25
     * @param $type
     * @param int $s
     * @return mixed|string
     * @throws \think\Exception
     */
    public function getUserSetFiled($type,$s=0){
        if(!isset($this->usr_set[$type]) || empty($this->usr_set[$type])){
            if($s == 1){
                return '';
            }else{
                throw new \think\Exception("参数不合法");
            }

        }
        return $this->usr_set[$type];
    }


    /**
     * 预存详情
     * @author: liukezhu
     * @date : 2021/11/17
     * @param $order_id
     * @param $village_id
     * @param $pigcms_id
     * @param $uid
     * @return mixed
     * @throws \think\Exception
     */
    public function getStorageDetails($order_id,$village_id,$pigcms_id,$uid){
        $where[] = ['order_id','=',$order_id];
        $where[] = ['uid','=',$uid];
        $where[] = ['business_type','=',2];
        $where[] = ['business_id','=',$village_id];
        $field='order_id,orderid as order_no,money,pay_time,label,paid';
        $data = $this->UserRechargeOrder->getOne($where,$field);
        if(empty($data)){
            throw new \think\Exception("订单不存在");
        }
        $user_bind = $this->getUserBind($pigcms_id,'name');
        if(empty($user_bind)){
            throw new \think\Exception("【社区】该用户未入住");
        }
        $data['pay_time']=date('Y-m-d H:i:s',$data['pay_time']);
        $data['user_name']=$user_bind['name'];
        $storage_type='';
        if(!empty($data['label'])){
            $label=explode('_',$data['label']);
            if(is_array($label)){
                $storage_type=$this->charge_name_arr[$label[1]];
            }
        }
        $data['storage_type']=$storage_type;
        if(intval($data['paid']) == 1){
            $data['paid']=1;
        }else{
            $data['paid']=0;
        }
        unset($data['label']);
        return $data;
    }


    /**
     *  取消、开通自动扣款
     * @author: liukezhu
     * @date : 2021/11/25
     * @param $uid
     * @param $pigcms_id
     * @param $type
     * @param $storage_type
     * @return array
     * @throws \think\Exception
     */
    public function userSet($uid,$pigcms_id,$type,$storage_type){
        $time=time();
        $user_bind = $this->getUserBind($pigcms_id,'phone');
        if(empty($user_bind)){
            throw new \think\Exception("【社区】该用户未入住");
        }
        $where[] = ['uid','=',$uid];
        $user_set_filed=$this->getUserSetFiled($storage_type);
        $user_set=$this->UserSet->getOne($where,'set_id,'.$user_set_filed);
        if(!in_array($type,[0,1])){
            return ['error'=>false,'msg'=>'非法参数'];
        }
        if(!$user_set){
            $data=[
                'uid'=>$uid,
                'phone'=>$user_bind['phone'],
                $user_set_filed=>$type,
                'set_time'=>$time
            ];
            $rr=$this->UserSet->addOne($data);
        }
        else{
            if($type == 1){
                if(intval($user_set[$user_set_filed]) == 1){
                    return ['error'=>false,'msg'=>'您已开通'.$this->charge_name_arr[$storage_type].'自动扣款，无须重复操作'];
                }
            }
            else{
                if(intval($user_set[$user_set_filed]) == 0){
                    return ['error'=>false,'msg'=>'您已取消'.$this->charge_name_arr[$storage_type].'自动扣款，无须重复操作'];
                }
            }
            $data=[
                $user_set_filed=>$type,
                'phone'=>$user_bind['phone'],
                'set_time'=>$time
            ];
            $where=[];
            $where[] = ['set_id','=',$user_set['set_id']];
            $where[] = ['uid','=',$uid];
            $rr=$this->UserSet->editFind($where,$data);
        }
        if(!$rr){
            return ['error'=>false,'msg'=>'操作失败','is_automat_pay'=>$type];
        }else{
            return ['error'=>true,'msg'=>'操作成功','is_automat_pay'=>$type];
        }

    }


    function subText($text, $length)
    {
        if (mb_strlen($text, 'utf8') > $length) {
            return mb_substr($text, 0, $length, 'utf8') . '......';
        } else {
            return $text;
        }
    }

    /**
     * 缴费账单打印
     * @author: liukezhu
     * @date : 2021/11/24
     * @param $pigcms_id
     * @param $order_id
     * @return array
     * @throws \think\Exception
     */
    public function printPayOrder($pigcms_id,$order_id){
        $time=time();
        $number = '';
        $orderInfo['number'] ='无';
        $where[] = ['order_id', '=', $order_id];
        $where[] = ['pigcms_id', '=', $pigcms_id];
        $orderInfo = $this->HouseNewPayOrder->get_one($where);
        if (empty($orderInfo)) {
            throw new \think\Exception("订单信息不存在！");
        }
        if(intval($orderInfo['is_paid']) != 1){
            throw new \think\Exception("该订单暂不支持打印！");
        }
        $projectinfo=$this->HouseNewChargeProject->getOne(['id' => $orderInfo['project_id']],'name');
        $user_info = $this->HouseVillageUserBind->getOne(['pigcms_id' => $pigcms_id],'village_id,name,phone,usernum,bind_number');
        if(!$user_info){
            throw new \think\Exception("该用户未入住");
        }
        $file_url='/static/pay_order/';

        $dirName=rtrim($_SERVER['DOCUMENT_ROOT'],'/').$file_url;
        if(!file_exists($dirName)){
            mkdir($dirName, 0777, true);
        }
        $file_name=$user_info['village_id'].'_'.$order_id.'.pdf';
        $db_file = $dirName.$file_name;
        if(file_exists($db_file)){
            return ['error'=>true,'url'=>cfg('site_url').$file_url.$file_name];
        }
        $village_info = $this->HouseVillage->getOne($user_info['village_id'], 'village_name');
        if(!$village_info){
            throw new \think\Exception("小区不存在");
        }
        if (in_array($orderInfo['order_type'],['water','electric','gas'])){
            $orderInfo['number'] = round($orderInfo["total_money"] / $orderInfo["unit_price"], 2);
        }
        if (!empty($orderInfo['room_id'])) {
            $room = $this->HouseVillageUserVacancy->getLists(['pigcms_id' => $orderInfo['room_id']]);
            if (!empty($room)) {
                $room = $room->toArray();
                if (!empty($room)) {
                    $room1 = $room[0];
                    $number=$this->HouseVillageService->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$user_info['village_id']);
                    if ($orderInfo['order_type']=='property'&&!empty($room1['housesize'])){
                        $orderInfo['number']=$room1['housesize'].'(房屋面积)';
                    }
                }
            }
        }
        elseif (!empty($orderInfo['position_id'])) {
            $position_num = $this->HouseVillageParkingPosition->getLists(['position_id' => $orderInfo['position_id']], 'pp.position_num,pg.garage_num', 0);
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
        if (!empty($projectinfo)) {
            $orderInfo['order_name'] = $projectinfo['name'];
        }
        $order_data=[
            'title'=>'已缴账单',//模板标题
            'print_time'=>date('Y-m-d H:i:s',$time), //打印日期
            'village_name'=>$village_info['village_name'], //小区名称
            'room_num'=>$number,//房号
            'username'=> $user_info['name'], //编号
            'phone'=>$user_info['phone'], //住户手机号
            'totalMoney'=>'￥' . $orderInfo['total_money'] . '（人民币大写：' . cny($orderInfo['total_money']) . '）', //合计
            'pay_time'=>date('Y-m-d H:i:s', $orderInfo['pay_time']),//	收款日期
            'payee'=>$orderInfo['role_name'], //收款人
            'payer'=>$orderInfo['pay_bind_name'],//住户姓名
            'order_name'=>$orderInfo['order_name'], //收费项目
            'price'=>$orderInfo['unit_price'], //单价
            'service_cycle'=>$orderInfo['service_month_num'],//缴费周期
            'number'=>$orderInfo['number'],//数量
            'money'=>$orderInfo["total_money"], //应收金额
            'discount'=>$orderInfo['diy_content'],//优惠
            'real_money'=>$orderInfo['pay_money']-$orderInfo['refund_money'],//实收金额
            'remarks'=>$this->subText($orderInfo['remark'],30),//备注
            'pay_type_name'=>$orderInfo['pay_type'],//收款方式
            'start_time'=>date('Y-m-d', $orderInfo['service_start_time']),//起始日期
            'end_time'=>date('Y-m-d', $orderInfo['service_end_time']),//终止日期
            'case'=>cny($orderInfo['pay_money']),
            'last_ammeter'=>$orderInfo['last_ammeter'],
            'now_ammeter'=>$orderInfo['now_ammeter'],
        ];
        if (empty($user_info['bind_number'])){
            $order_data['usernum'] = $user_info['usernum'];
        }
        else{
            $order_data['usernum'] = $user_info['bind_number'];
        }
        $field_head=[
            'username'=>'编号',
            'village_name'=>'小区',
            'room_num'=>'房号',
            'payer'=>'住户姓名',
            'phone'=>'住户手机号',
            'totalMoney'=>'合计'
        ];
        $field_body=[
            'room_num'=>'房号',
            'order_name'=>'收费项目',
            'service_cycle'=>'缴费周期',
            'number'=>'数量',
            'price'=>'单价',
            'money'=>'应收金额',
            'discount'=>'优惠',
            'real_money'=>'实收金额',
//            'start_time'=>'起始日期',
//            'end_time'=>'终止日期',
            'last_ammeter'=>'起度',
            'now_ammeter'=>'止度',
            'remarks'=>'备注',
            'pay_time'=>'缴费日期'
        ];
        $field_foot=[
            'print_time'=>'打印日期',
            'pay_time'=>'收款日期',
            'payee'=>'收款方'
        ];

        $html='<html lang="zh-cn"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style>body{font-family:simsun} .div_{display: inline-block;margin: 20px 0;width: 100%} .head{display: inline-block;width: 30%;padding: 5px}  .th_1{min-width: 68px;} .th_2{display: inline-block;} .foot{display: inline-block;width: 30%;padding: 5px}  table,table tr th, table tr td { border:1px solid #e8e8e8; }  .font_1{padding: 5px;}  .font_s{
font-size: 13px
} .div_head{display: block;width: 200px;margin: auto;} .tl_center{display: block;text-align: center;} .td_word{word-wrap:break-word;display: table-cell;} .td_room_num{max-width:150px;} .td_order_name{max-width:80px;} .td_remarks{max-width:80px;} .span_w{max-width: 150px;word-wrap:break-word;}</style></head>';

        $table='<div class="div_head"><h3>'.$order_data['title'].'</h3></div>';
        //todo 头部
        $table_head='';
        foreach ($field_head as $k=>$v){
            $class='';
            if(in_array($k,['room_num'])){
                $class=' span_w ';
            }
            $table_head.='<span class="head"><span>'.$v.'：</span><span class="font_s '.$class.'">'.$order_data[$k].'</span></span>';
        }
        $table.='<div class="div_">'.$table_head.'</div>';

        //todo body
        $table_str=$table_sth='';
        foreach ($field_body as $k=>$v){
            $class='';
            if(in_array($k,['room_num','order_name','remarks'])){
                $class='class="td_word td_'.$k.'"';
            }
            $table_str.='<th class="th_1 font_1">'.$v.'</th>';
            $table_sth.='<td '.$class.'><span class="th_2 font_1 font_s tl_center">'.$order_data[$k].'</span></td>';
        }
        $table.='<table border="1" cellspacing="0" cellpadding="0"><tbody><tr>'.$table_str.'</tr><tr>'.$table_sth.'</tr></tbody></table>';

        //todo 底部
        $table_foot='';
        foreach ($field_foot as $k=>$v){
            $table_foot.='<span class="foot"><span>'.$v.'：</span><span class="font_s">'.$order_data[$k].'</span></span>';
        }
        $table.='<div class="div_">'.$table_foot.'</div>';
        $str=$html.$table.'</html>';
        $this->Dompdf->loadHtml($str);
        $this->Dompdf->setPaper('A4', 'landscape');
        $this->Dompdf->render();
        if(!file_exists($db_file)){
             file_put_contents($db_file, $this->Dompdf->output());
        }
        return ['error'=>true,'url'=>cfg('site_url').$file_url.$file_name];
    }

    /**
     * 充值账单打印
     * @author: liukezhu
     * @date : 2021/11/24
     * @param $uid
     * @param $order_id
     * @return array
     * @throws \think\Exception
     */
    public function printRechargeOrder($uid,$order_id){
        $time=time();
        $number = '';
        $orderInfo['number'] ='无';
        $where[] = ['order_id', '=', $order_id];
        $where[] = ['uid', '=', $uid];
        $orderInfo = $this->UserRechargeOrder->getOne($where);
        if (empty($orderInfo)) {
            throw new \think\Exception("订单信息不存在！");
        }
        if(intval($orderInfo['paid']) != 1){
            throw new \think\Exception("该订单暂不支持打印！");
        }
        $user_info = $this->HouseVillageUserBind->getOne(['pigcms_id' => $orderInfo['bind_id']],'village_id,name,phone,usernum,bind_number,vacancy_id');
        if(!$user_info){
            throw new \think\Exception("该用户未入住");
        }
        $file_url='/static/recharge_order/';
        $dirName=rtrim($_SERVER['DOCUMENT_ROOT'],'/').$file_url;
        if(!file_exists($dirName)){
            mkdir($dirName, 0777, true);
        }
        $file_name=$user_info['village_id'].'_'.$order_id.'.pdf';
        $db_file = $dirName.$file_name;
        if(file_exists($db_file)){
            return ['error'=>true,'url'=>cfg('site_url').$file_url.$file_name];
        }
        $village_info = $this->HouseVillage->getOne($user_info['village_id'], 'village_name,property_id');
        if(!$village_info){
            throw new \think\Exception("小区不存在");
        }
        $payee='';
        $property_info=$this->HousePropertyService->getFind(['id'=>$village_info['property_id']],'property_name');
        if($property_info){
            $payee=$property_info['property_name'];
        }
        if (!empty($user_info['vacancy_id'])) {
            $room = $this->HouseVillageUserVacancy->getLists(['pigcms_id' => $user_info['vacancy_id']]);
            if (!empty($room)) {
                $room = $room->toArray();
                if (!empty($room)) {
                    $room1 = $room[0];
                    $number=$this->HouseVillageService->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$user_info['village_id']);
                }
            }
        }
        $label=explode('_',$orderInfo['label']);
        $order_data=[
            'title'=>'预存账单',//模板标题
            'username'=> $orderInfo['orderid'], //订单号
            'village_name'=>$village_info['village_name'], //小区名称
            'room_num'=>$number,//房号
            'payer'=>$user_info['name'],//住户姓名
            'phone'=>$user_info['phone'], //住户手机号
            'totalMoney'=>'￥' . $orderInfo['money'] . '（人民币大写：' . cny($orderInfo['money']) . '）', //合计
            'real_money'=> $orderInfo['money'],
            'storage_type'=>$this->charge_name_arr[$label[1]],
            'pay_time'=>date('Y-m-d H:i:s', $orderInfo['pay_time']),//	预存时间
            'print_time'=>date('Y-m-d H:i:s',$time), //打印日期
            'payee'=>$payee, //收款方
        ];
        if (empty($user_info['bind_number'])){
            $order_data['usernum'] = $user_info['usernum'];
        }
        else{
            $order_data['usernum'] = $user_info['bind_number'];
        }
        $field_head=[
            'username'=>'订单号',
            'village_name'=>'小区',
            'room_num'=>'房号',
            'payer'=>'住户姓名',
            'phone'=>'住户手机号',
            'totalMoney'=>'合计'
        ];
        $field_body=[
            'room_num'=>'房号',
            'storage_type'=>'预存类型',
            'real_money'=>'缴费金额',
            'pay_time'=>'预存时间',
        ];
        $field_foot=[
            'print_time'=>'打印日期',
            'pay_time'=>'收款日期',
            'payee'=>'收款方'
        ];

        $html='<html lang="zh-cn"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style>body{font-family:simsun} .div_{display: inline-block;margin: 20px 0;width: 100%} .head{display: inline-block;margin-right: 80px}  .th_1{width: 30%} .th_2{display: inline-block;} .foot{display: inline-block;width: 29%;padding: 5px}  table,table tr th, table tr td { border:1px solid #e8e8e8;}  .font_1{padding: 5px;}  .font_s{
font-size: 13px
} .div_head{display: block;width: 200px;margin: auto;} .tl_center{display: block;text-align: center;} table { width: 100%; table-layout: fixed; }</style></head>';

        $table='<div class="div_head"><h3>'.$order_data['title'].'</h3></div>';
        //todo 头部
        $table_head='';
        foreach ($field_head as $k=>$v){
            $table_head.='<span class="head"><span>'.$v.'：</span><span>'.$order_data[$k].'</span></span>';
        }
        $table.='<div class="div_">'.$table_head.'</div>';

        //todo body
        $table_str=$table_sth='';
        foreach ($field_body as $k=>$v){
            $table_str.='<th class="th_1 font_1">'.$v.'</th>';
            $table_sth.='<td align="center"><span style="" class="th_2 font_1 font_s tl_center">'.$order_data[$k].'</span></td>';
        }
        $table.='<table border="1" cellspacing="0" cellpadding="0"><tbody><tr>'.$table_str.'</tr><tr>'.$table_sth.'</tr></tbody></table>';

        //todo 底部
        $table_foot='';
        foreach ($field_foot as $k=>$v){
            $table_foot.='<span class="foot"><span>'.$v.'：</span><span class="font_s">'.$order_data[$k].'</span></span>';
        }
        $table.='<div class="div_">'.$table_foot.'</div>';
        $str=$html.$table.'</html>';
        $this->Dompdf->loadHtml($str);
        $this->Dompdf->setPaper('A4', 'landscape');
        $this->Dompdf->render();
        if(!file_exists($db_file)){
            file_put_contents($db_file, $this->Dompdf->output());
        }
        return ['error'=>true,'url'=>cfg('site_url').$file_url.$file_name];
    }


    /**
     *获取用户列表
     * @author: zhubaodi
     * @date : 2022/06/07
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function getUserList($data,$hidephone=false){
        $db_village_user_money_list=new VillageUserMoneyList();
        $db_house_village_user_bind=new HouseVillageUserBind();
        $db_house_village_bind_position=new HouseVillageBindPosition();
        $where[]=['v.business_id','=',$data['village_id']];
        $where[]=['v.uid','>',0];
        $where[]=['v.business_type', '=', 1];
        if(!empty($data['name'])){
            $where[] = ['u.nickname', 'like', '%'.$data['name'].'%'];
        }
        if(!empty($data['phone'])){
            $where[] = ['u.phone', 'like', '%'.$data['phone'].'%'];
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        
        if(isset($data['vacancy_arr']) && $data['vacancy_arr'] && is_array($data['vacancy_arr'])){
            $whereTmpArr=array(['village_id','=',$data['village_id']]);
            $whereTmpArr[]=array('single_id','=',$data['vacancy_arr']['0']);
            if(isset($data['vacancy_arr']['1']) && $data['vacancy_arr']['1']){
                $whereTmpArr[]=array('floor_id','=',$data['vacancy_arr']['1']);
            }
            if(isset($data['vacancy_arr']['2']) && $data['vacancy_arr']['2']){
                $whereTmpArr[]=array('layer_id','=',$data['vacancy_arr']['2']);
            }
            if(isset($data['vacancy_arr']['3']) && $data['vacancy_arr']['3']){
                $whereTmpArr[]=array('vacancy_id','=',$data['vacancy_arr']['3']);
            }
            $whereTmpArr[]=array('uid','>',0);
            $whereTmpArr[]=array('type','in',[0,1,2,3]);
            $whereTmpArr[]=array('status','=',1);
            $uid_arr=$db_house_village_user_bind->getUserColumn($whereTmpArr,'uid');
            if($uid_arr){
                if(isset($data['uid']) && !empty($data['uid'])){
                    $uid_arr=array_merge($uid_arr,$data['uid']);
                }
                $uid_arr=array_unique($uid_arr);
                $data['uid']=$uid_arr;
            }
        }
        if(!empty($data['uid'])){
            $where[]=['v.uid','in',$data['uid']];
        }
        
        if(isset($data['bind_pigcms_id']) && ($data['bind_pigcms_id']>0) && $data['uid']<1 ){
            $data['is_no_uid']=0;
            $count_tmp=0;
            $listTmp=array();
            $whereArr=array(['village_id','=',$data['village_id']]);
            $whereArr[]=array('status','=',1);
            $whereArr[]=array('pigcms_id','=',$data['bind_pigcms_id']);
            $user_bind_obj=$db_house_village_user_bind->getOne($whereArr);
            if($user_bind_obj && !$user_bind_obj->isEmpty()){
                $user_bind= $user_bind_obj->toArray();
                if($user_bind && $user_bind['uid']>0){
                    $count_tmp=1;
                    if($hidephone && isset($user_bind['phone']) && !empty($user_bind['phone'])){
                        $user_bind['phone']=$houseVillageDb->phone_desensitization_v($user_bind['phone'],$data['village_id'],$village_info);
                    }
                    $listTmp[]=array('id'=>0,'name'=>$user_bind['name'],'bind_pigcms_id'=>$data['bind_pigcms_id'],'phone'=>$user_bind['phone'],'now_money'=>0,'uid'=>$user_bind['uid']);
                }elseif($user_bind && $user_bind['uid']<1){
                    $data['is_no_uid']=1;
                }
            }

            $data['list'] = $listTmp;
            $data['total_limit'] = $data['limit'];
            $data['count'] = $count_tmp;
            return $data;
        }
        
        $field='v.*,u.nickname as name,u.phone';
        $order='v.id desc';
        $list=$db_village_user_money_list->getUserList($where,$field,$order,$data['page'],$data['limit']);
        if (!empty($list)){
            $list=$list->toArray();
        }
        $houseVillageService=new HouseVillageService();
        if (!empty($list)){
            foreach ($list as &$v){
                $v['now_money']=$v['current_money'];
                $user_bind_where=[
                    'village_id'=>$data['village_id'],
                    'uid'=>$v['uid'],
                    'type'=>[0,1,2,3],
                    'status'=>1
                ];
                $v['user_bind_name']='---';
                $v['room_address']='---';
                $v['room_num']=$db_house_village_user_bind->getVillageUserNum($user_bind_where);
                if($v['room_num']==1){
                    $user_bind_obj=$db_house_village_user_bind->getOne($user_bind_where,'*');
                    $v['user_bind_name']=$user_bind_obj['name'];
                    $address = $houseVillageService->getSingleFloorRoom($user_bind_obj['single_id'],$user_bind_obj['floor_id'],$user_bind_obj['layer_id'],$user_bind_obj['vacancy_id'],$user_bind_obj['village_id']);
                    $v['room_address']=$address;
                }
                $pigcms_id_arr=$db_house_village_user_bind->getUserColumn($user_bind_where,'pigcms_id');
                $position_where=[
                    'village_id'=>$data['village_id'],
                    'user_id'=>$pigcms_id_arr,
                ];
                $v['position_num']=$db_house_village_bind_position->getCount($position_where);
                if($hidephone && isset($v['phone']) && !empty($v['phone'])){
                    $v['phone']=$houseVillageDb->phone_desensitization_v($v['phone'],$data['village_id'],$village_info);
                }
            }
        }
        $count=$db_village_user_money_list->getUserCount($where);
        if(isset($data['bind_pigcms_id']) && ($data['bind_pigcms_id']>0) && empty($list) ){
            $data['is_no_uid']=0;
            $whereArr=array(['village_id','=',$data['village_id']]);
            $whereArr[]=array('status','=',1);
            $whereArr[]=array('pigcms_id','=',$data['bind_pigcms_id']);
            $user_bind_obj=$db_house_village_user_bind->getOne($whereArr);
            if($user_bind_obj && !$user_bind_obj->isEmpty()){
                $user_bind= $user_bind_obj->toArray();
                if($user_bind && $user_bind['uid']>0){
                    $count=1;
                    if($hidephone && isset($user_bind['phone']) && !empty($user_bind['phone'])){
                        $user_bind['phone']=$houseVillageDb->phone_desensitization_v($user_bind['phone'],$data['village_id'],$village_info);
                    }
                    $list[]=array('id'=>0,'name'=>$user_bind['name'],'bind_pigcms_id'=>$data['bind_pigcms_id'],'phone'=>$user_bind['phone'],'now_money'=>0,'uid'=>$user_bind['uid']);
                }elseif($user_bind && $user_bind['uid']<1){
                    $data['is_no_uid']=1;
                }
            }
        }
        $data['list'] = $list;
        $data['total_limit'] = $data['limit'];
        $data['count'] = $count;
        return $data;
    }
    /**
     *获取用户列表
     * @author: liukezhu
     * @date : 2021/11/18
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function getUserList_old($where,$field,$order,$page,$limit){
        $group='ub.uid';
        $list=$this->HouseVillageUserBind->getGroupUserList($where,$field,$group,$order,$page,$limit);
        if(!empty($list)){
            $list=$list->toArray();
        }
        $count=$this->HouseVillageUserBind->getGroupUserCount($where,$group);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }


    /**
     * 获取用户数据
     * @author: liukezhu
     * @date : 2021/11/23
     * @param $where
     * @param $field
     * @param int $type
     * @return array
     * @throws \think\Exception
     */
    public function getUser($where,$field,$type=0){
        $data= $this->HouseVillageUserBind->getUserBindInfo($where,$field);
        if(!$data){
            if($type == 1){
                return [];
            }else{
                throw new \think\Exception("用户不存在");
            }
        }else{
            return $data;
        }
    }


    /**
     * 余额变动
     * @author: liukezhu
     * @date : 2021/11/23
     * @param $pigcms_id user_bind 主键id
     * @param $status    1:增加 2:减少
     * @param string $price
     * @param $system_remarks  系统备注
     * @param $user_remarks  用户备注
     * @param int $order_id 订单id
     * @return array
     */
    public function userBalanceChange1($pigcms_id,$status,$price='0',$system_remarks,$user_remarks,$order_id=0){
        $time=time();
        $result=false;
        $is_automat_pay=false;
        $order=[];
        Db::startTrans();
        try {
            $where[]=['hvb.pigcms_id', '=',$pigcms_id];
            $where[]=['hvb.status', '=', 1];
            $field='hvb.pigcms_id,hvb.name,hvb.phone,hvb.village_id,u.now_money,u.uid';
            if(!is_numeric($price) || empty($price)){
                return ['error'=>false,'msg'=>'请选择余额'];
            }
            $user = (new StorageService())->getUser($where,$field,1);
            if(empty($user)){
                return ['error'=>false,'msg'=>'用户不存在'];
            }
            if(intval($order_id) > 0){
                $where=[];
                $where[]=['order_id', '=', $order_id];
                $order=$this->HouseNewPayOrder->get_one($where);
                if(!empty($order)){
                    $order=$order->toArray();
                }
                if(empty($order)){
                    fdump_api(['订单不存在'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$order],'storage/error_log',1);
                    return ['error'=>false,'msg'=>'订单不存在'];
                }

                if($order['order_type'] == 'property'){
                    fdump_api(['属于物业费缴费'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$order],'storage/error_log',1);
                    $is_automat_pay=true;
                }
                else{
                    $where=[];
                    $where[] = ['uid','=',$user['uid']];
                    $user_set_filed=$this->getUserSetFiled($order['order_type'],1);
                    if(empty($user_set_filed)){
                        fdump_api(['自动扣款类型不存在'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$order,$user_set_filed],'storage/error_log',1);
                        return ['error'=>false,'msg'=>'自动扣款类型不存在'];
                    }
                    $user_set=$this->UserSet->getOne($where,$user_set_filed);
                    if($user_set && (intval($user_set[$user_set_filed]) == 1)){
                        $is_automat_pay=true;
                    }
                    else{
                        fdump_api(['用户'.$this->charge_name_arr[$order['order_type']].'暂未开启自动扣款'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$user_set],'storage/error_log',1);
                        return ['error'=>false,'msg'=>'用户'.$this->charge_name_arr[$order['order_type']].'暂未开启自动扣款'];
                    }
                }
            }
            $data=[
                'order_no'=>date('YmdHis',$time).createRandomStr(8,true),
                'village_id'=>$user['village_id'],
                'uid'=>$user['uid'],
                'time'=>$time,
                'system_remarks'=>$system_remarks,
                'ip'=>Request::ip(),
                'desc'=>$user_remarks,
                'ask'=>20,
                'current_money'=>$user['now_money'],
                'type'=>$status,
                'withdraw_id'=>0,
                'ask_id'=>0
            ];
            if($status == 1){
                $user_price=$user['now_money'] + $price;
                $data['money']=$price;
            }
            elseif ($status == 2){
                $user_price=$user['now_money'] - $price;
                if($user_price < 0){
                    fdump_api(['用户余额不足'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$user,$user_price],'storage/error_log',1);
                    return ['error'=>false,'msg'=>'该用户余额不足'];
                }
                $data['money']='-'.$price;
            }
            else{
                fdump_api(['参数不存在'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id],'storage/error_log',1);
                return ['error'=>false,'msg'=>'参数不存在'];
            }
            $data['after_price']=$user_price;
            $where=[];
            $where[]=['uid', '=', $user['uid']];
            $user_data=[
                'now_money'=>$user_price
            ];
            $this->UserMoneyList->addData($data);
            $this->User->editFind($where,$user_data);
            if($is_automat_pay){
                $PayOrder=[
                    'is_paid'=>1,
                    'pay_time'=>$time,
                    'pay_type'=>4,
                    'pay_money'=>$price,
                    'system_balance'=>$price,
                    'pay_bind_id'=>$user['pigcms_id'],
                    'pay_bind_name'=>$user['name'],
                    'pay_bind_phone'=>$user['phone'],
                ];
                if(intval($order_id) > 0 && !empty($order) && $order['order_type'] == 'property'){
                    $PayOrder['is_service_time']=1;
                }else{
                    $PayOrder['is_service_time']=0;
                }
                $result=self::orderSummary($order,$PayOrder);
            }
            else{
                $result=true;
            }
            Db::commit();
        } catch (\Exception $e) {
            fdump_api(['error-----'.__LINE__,$pigcms_id,$status,$price='0',$system_remarks,$user_remarks,$order_id,$e->getMessage()],'storage/error_log',1);
            Db::rollback();
            return ['error'=>false,'msg'=>$e->getMessage()];
        }
        fdump_api(['用户操作成功'.__LINE__,$pigcms_id,$user,$status,$price,$system_remarks,$user_remarks,$order_id,$result,$is_automat_pay],'storage/succ_log',1);
        if($result && $is_automat_pay){
            return ['error'=>true,'msg'=>'该账单生成后，自动扣款成功'];
        }else{
            return ['error'=>true,'msg'=>'操作成功'];
        }

    }
    public function handle_room_water_order_balance($order_id,$village_id,$uid=0){
        /**客户不要这个定制功能了 先注释掉****/
        $water_order_handle=false;
        return $water_order_handle;
        $where=[];
        $where[]=['order_id', '=', $order_id];
        $orderObj=$this->HouseNewPayOrder->get_one($where,'*');
        if($orderObj && !$orderObj->isEmpty() && $orderObj['order_type']=='water' && $orderObj['is_discard']==1){
            $orderArr=$orderObj->toArray();
            if($orderArr['is_paid']!=2 || $orderArr['room_id']<1){
                return $water_order_handle;
            }
            $whereArr=array('pigcms_id'=>$orderArr['room_id'],'village_id'=>$village_id);
            $userVacancy=$this->HouseVillageUserVacancy->getOne($whereArr,'water_balance');
            if(empty($userVacancy) || !isset($userVacancy['water_balance']) || $userVacancy['water_balance']<$orderArr['total_money']){
                return $water_order_handle;
             }
            $PayOrder=[
                'is_paid'=>1,
                'pay_time'=>time(),
                'pay_type'=>4,
                'pay_money'=>$orderArr['total_money'],
                'village_balance'=>$orderArr['total_money'],
                'system_balance'=>0,
                'pay_bind_id'=>0,
                'pay_bind_name'=>'',
                'pay_bind_phone'=>'',
            ];
            $PayOrder['order_type_flag']='room_water_balance';
            $PayOrder['is_service_time']=0;
            $result=self::orderSummary($orderArr,$PayOrder);
            if($result){
                $water_balance=$userVacancy['water_balance']-$orderArr['total_money'];
                $saveTmpArr = [
                    'water_balance' => $water_balance
                ];
                $houseVillageSingleService= new HouseVillageSingleService();
                $whereArr=array('pigcms_id'=>$orderArr['room_id'],'village_id'=>$village_id);
                $this->HouseVillageUserVacancy->saveOne($whereArr,$saveTmpArr);
                $logBalanceRoomArr=array('room_id'=>$orderArr['room_id'],'village_id'=>$village_id,'current_v'=>$water_balance);
                $logBalanceRoomArr['xtype']='room_water_balance';
                $logBalanceRoomArr['change_v']=$orderArr['total_money'];
                $logBalanceRoomArr['old_v']=$userVacancy['water_balance'];
                $logBalanceRoomArr['cfrom']='order_auto_takeoff';
                $logBalanceRoomArr['is_need_order']=0;
                $logBalanceRoomArr['add_time']=time();
                $logBalanceRoomArr['bak']='水费订单【'.$order_id.'】自动扣除余额'.date('Y-m-d H:i:s');
                $houseVillageSingleService->addRoomWaterBalanceLog($logBalanceRoomArr);
                $water_order_handle=true;
                return $water_order_handle;
            }
        }
        return $water_order_handle;
    }
    /**
     * 余额变动
     * @author: liukezhu
     * @date : 2021/11/23
     * @param $pigcms_id user_bind 主键id
     * @param $status    1:增加 2:减少
     * @param string $price
     * @param $system_remarks  系统备注
     * @param $user_remarks  用户备注
     * @param int $order_id 订单id
     * $off_pay_type 线下支付方式
     * $opt_money_type 住户余额操作类型
     * @return array
     */
    public function userBalanceChange($pigcms_id,$status,$price='0',$system_remarks,$user_remarks,$order_id=0,$village_id=0,$opt_money_type='',$extra_data=array()){
        $time=time();
        $result=false;
        $is_automat_pay=false;
        $order=[];
        $pigcms_id=$pigcms_id ?$pigcms_id:0;
        try {
            if (!empty($village_id)){
                $where[]=['hvb.village_id', '=',$village_id];
            }
            $configCustomizationService= new ConfigCustomizationService();
            $isAihorseCustomized=$configCustomizationService->getAihorseCustomizedJudge();
            /***
            if($isAihorseCustomized && $status==2 && $order_id>0){
                $is_water_handle=$this->handle_room_water_order_balance($order_id,$village_id,$pigcms_id);
                if($is_water_handle){
                    return ['error'=>true,'msg'=>'该账单生成后，水费自动扣款成功'];
                }
            }
            **/
            if (empty($pigcms_id)){
                fdump_api(['用户id不能为空'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$village_id],'storage/error_log',1);
                return ['error'=>false,'msg'=>'用户id不能为空'];
            }
            $uid=$pigcms_id;
            $where[]=['hvb.uid', '=',$pigcms_id];
            $where[]=['hvb.status', '=', 1];
            $where[]=['hvb.type', 'in', [0,1,2,3]];
            $field='hvb.pigcms_id,hvb.name,hvb.phone,hvb.village_id,u.now_money,u.uid';
            if(!is_numeric($price) || empty($price)){
                return ['error'=>false,'msg'=>'请选择余额'];
            }
            $user = (new StorageService())->getUser($where,$field,1);
            if($village_id<1 && (empty($user) || !isset($user['pigcms_id']))){
                return ['error'=>false,'msg'=>'用户不存在！'];
            }elseif($user && isset($user['village_id'])){
                $village_id=$user['village_id'] ? $user['village_id']:$village_id;
            }
            if($village_id<1){
                return ['error'=>false,'msg'=>'小区信息错误！'];
            }
            if(intval($order_id) > 0){
                $where=[];
                $where[]=['order_id', '=', $order_id];
                $order=$this->HouseNewPayOrder->get_one($where);
                if(!empty($order)){
                    $order=$order->toArray();
                }
                if(empty($order)){
                    fdump_api(['订单不存在'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$order],'storage/error_log',1);
                    return ['error'=>false,'msg'=>'订单不存在'];
                }

                //$jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
                if($order['order_type'] == 'property' && !in_array($opt_money_type,['qunuanfei'])){
                    fdump_api(['属于物业费缴费'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$order],'storage/error_log',1);
                    $is_automat_pay=true;
                }elseif($this->customized_meter_reading || in_array($opt_money_type,array('cold_water_balance','hot_water_balance','electric_balance'))){
                    $is_automat_pay=true;
                } else{
                    $order['order_type'] = in_array($opt_money_type,['qunuanfei']) ? $opt_money_type : $order['order_type'];
                    $where=[];
                    $where[] = ['uid','=',$uid];
                    $user_set_filed=$this->getUserSetFiled($order['order_type'],1);
                    if(empty($user_set_filed)){
                        fdump_api(['自动扣款类型不存在'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$order,$user_set_filed],'storage/error_log',1);
                        return ['error'=>false,'msg'=>'自动扣款类型不存在'];
                    }
                    $user_set=$this->UserSet->getOne($where,$user_set_filed);
                    if($user_set && (intval($user_set[$user_set_filed]) == 1)){
                        $is_automat_pay=true;
                    }
                    if($isAihorseCustomized && $status==2 && !$is_automat_pay){
                        $houseVillage=new HouseVillage();
                        $tmpVillageObj=$houseVillage->getInfo(array('village_id'=>$village_id),'*');
                        if($tmpVillageObj && !$tmpVillageObj->isEmpty() && isset($tmpVillageObj['balance_automat_pay'])){
                            $is_automat_pay=$tmpVillageObj['balance_automat_pay']>0 ? true:false;
                        }
                    }
                    if(!$is_automat_pay){
                        fdump_api(['用户'.$this->charge_name_arr[$order['order_type']].'暂未开启自动扣款'.__LINE__,$pigcms_id,$status,$price,$system_remarks,$user_remarks,$order_id,$user_set],'storage/error_log',1);
                        return ['error'=>false,'msg'=>'用户'.$this->charge_name_arr[$order['order_type']].'暂未开启自动扣款'];
                    }
                }

                $village_money_data=[
                    'uid'=>$uid,
                    'village_id'=>$village_id,
                    'type'=>$status,
                    'current_village_balance'=>$price,
                    'role_id'=>$order['role_id'],
                    'desc'=>$user_remarks,
                    'order_id'=>$order_id,
                    'order_type'=>1,
                    'opt_money_type'=>$opt_money_type,
                ];
            }else{
                $village_money_data=[
                    'uid'=>$uid,
                    'village_id'=>$village_id,
                    'type'=>$status,
                    'current_village_balance'=>$price,
                    'desc'=>$user_remarks,
                    'opt_money_type'=>$opt_money_type,
                ];
            }
           
            $village_money_data['extra_data']=$extra_data;
           
            $add_res=$this->addVillageUserMoney($village_money_data);
            if ($add_res['error_code']){
                fdump_api(['error-----'.__LINE__,$pigcms_id,$status,$price='0',$system_remarks,$user_remarks,$order_id,$add_res['msg']],'storage/error_log',1);
                return ['error'=>false,'msg'=>$add_res['msg']];
            }
            $log_id=$add_res['log_id'];
            if($is_automat_pay){
                $PayOrder=[
                    'is_paid'=>1,
                    'pay_time'=>$time,
                    'pay_type'=>4,
                    'pay_money'=>$price,
                    'village_balance'=>$price,
                    'system_balance'=>0,
                    'pay_bind_id'=>$user ? $user['pigcms_id']:0,
                    'pay_bind_name'=>$user? $user['name']:'',
                    'pay_bind_phone'=>$user? $user['phone']:'',
                ];
                if(!empty($opt_money_type)){
                    $PayOrder['village_balance']=$price;
                    $PayOrder['system_balance']=0;
                    if($opt_money_type!='village_balance'){
                        $PayOrder['order_type_flag']=$opt_money_type;
                    }
                }
                if(intval($order_id) > 0 && !empty($order) && $order['order_type'] == 'property'){
                    $PayOrder['is_service_time']=1;
                }else{
                    $PayOrder['is_service_time']=0;
                }
                $result=self::orderSummary($order,$PayOrder);
            }
            else{
                $result=true;
            }
        } catch (\Exception $e) {
            fdump_api(['error-----'.__LINE__,$pigcms_id,$status,$price='0',$system_remarks,$user_remarks,$order_id,$e->getMessage()],'storage/error_log',1);
            return ['error'=>false,'log_id'=>$log_id,'msg'=>$e->getMessage()];
        }
        fdump_api(['用户操作成功'.__LINE__,$pigcms_id,$user,$status,$price,$system_remarks,$user_remarks,$order_id,$result,$is_automat_pay],'storage/succ_log',1);
        if($result && $is_automat_pay){
            return ['error'=>true,'log_id'=>$log_id,'msg'=>'该账单生成后，自动扣款成功'];
        }else{
            return ['error'=>true,'log_id'=>$log_id,'msg'=>'操作成功'];
        }

    }


    //todo 自动扣款成功 生成支付账单
    public function orderSummary($order,$PayOrder,$order_no=0){
        $is_service_time=$PayOrder['is_service_time'];
        //住户余额
        $village_balance=isset($PayOrder['village_balance']) ? $PayOrder['village_balance']:0;
        $pay_type_from='system_balance';
        if($village_balance>0){
            $pay_type_from='village_balance';
        }
        if(isset($PayOrder['order_type_flag']) && $PayOrder['order_type_flag']=='room_water_balance'){
            $pay_type_from='room_water_balance';
        }
        unset($PayOrder['is_service_time']);
        $order_summary=[
            'uid'=>$order['uid'],
            'pay_uid'=>$order['uid'],
            'property_id'=>$order['property_id'],
            'pigcms_id'=>$order['pigcms_id'],
            'pay_bind_id'=>$PayOrder['pay_bind_id'],
            'room_id'=>$order['room_id'],
            'village_id'=>$order['village_id'],
            'total_money'=>$order['total_money'],
            'pay_money'=>$PayOrder['pay_money'],
            'is_paid'=>$PayOrder['is_paid'],
            'pay_time'=>$PayOrder['pay_time'],
            'is_online'=>1,
            'pay_type'=>$PayOrder['pay_type'],
            'order_no'=>$order_no ? $order_no : build_real_orderid($order['uid']),
            'system_balance'=>isset($PayOrder['system_balance']) ? $PayOrder['system_balance']:0,
            'village_balance'=>$village_balance,
        ];
        if(isset($PayOrder['village_balance']) && ($PayOrder['village_balance']>0)){
            $order_summary['village_balance']=$PayOrder['village_balance'];
        }
        $order_summary['add_time'] = time();
        $summary_id=$this->HouseNewPayOrderSummary->addOne($order_summary);
        if(!$summary_id){
            return false;
        }
        $plat_order=[
            'orderid'=>'',
            'business_type'=>'village_new_pay',
            'business_id'=>$summary_id,
            'order_name'=>$this->charge_name_arr[$order['order_type']],
            'uid'=>$order['uid'],
            'total_money'=>$PayOrder['pay_money'],
            'system_balance'=>isset($PayOrder['system_balance']) ? $PayOrder['system_balance']:0,
            'village_balance'=>$village_balance,
            'pay_time'=>$PayOrder['pay_time'],
            'pay_type'=>'',
            'paid'=>1
        ];
        if(isset($PayOrder['village_balance'])&& ($PayOrder['village_balance']>0)){
            $plat_order['village_balance']=$PayOrder['village_balance'];
            $plat_order['system_balance']=0;
        }
        $plat_order_id=$this->PlatOrder->add_order($plat_order);
        $PayOrder['summary_id']=$summary_id;
        $where=[];
        $where[]=['order_id', '=', $order['order_id']];
        $where[]=['is_paid', '=', 2];
        $rr= $this->HouseNewPayOrder->saveOne($where,$PayOrder);
        if($is_service_time == 1){
            $tt=(new NewPayService())->offlineAfterPay($summary_id,0);
            fdump_api(['is_service_time---'.__LINE__,$tt],'storage/2error_log',1);
        }else{
            $save_paid_order_record=array('source_from'=>1);
            $save_paid_order_record['house_type']=$order['order_type'];
            $save_paid_order_record['business_type']='village_new_pay';
            $save_paid_order_record['business_name']=$plat_order['order_name'];
            $save_paid_order_record['uid']=$order['uid'];
            $save_paid_order_record['order_id']=$summary_id;
            $save_paid_order_record['order_no']=$order_summary['order_no'];
            if(isset($order['phone']) && $order['phone']){
                $save_paid_order_record['u_phone']=$order['phone'];
            }
            if(isset($order['pay_bind_phone']) && $order['pay_bind_phone']){
                $save_paid_order_record['u_phone']=$order['pay_bind_phone'];
            }
            if(isset($order['name']) && $order['name']){
                $save_paid_order_record['u_name']=$order['name'];
            }
            if(isset($order['pay_bind_name']) && $order['pay_bind_name']){
                $save_paid_order_record['u_name']=$order['pay_bind_name'];
            }
            $save_paid_order_record['table_name']='house_new_pay_order_summary';
            $save_paid_order_record['sub_order_ids']=$order['order_id'];
            $save_paid_order_record['order_money']=$order_summary['pay_money'];
            $save_paid_order_record['is_own']=1;
            $save_paid_order_record['balance_money']=$PayOrder['pay_money'];
            $save_paid_order_record['pay_type']='offline_balance';
            $save_paid_order_record['pay_type_from']=$pay_type_from;
            $save_paid_order_record['pay_time']=$PayOrder['pay_time'];
            $save_paid_order_record['room_id']=$order['room_id'];
            $save_paid_order_record['bind_user_id']=$order['pigcms_id'] ? $order['pigcms_id']:$order['pay_bind_id'];
            $save_paid_order_record['property_id']=$order['property_id'];
            $save_paid_order_record['village_id']=$order['village_id'];
            $paidOrderRecordDb = new PaidOrderRecord();
            $paidOrderRecordDb->addOneData($save_paid_order_record);
        }
        if(isset($PayOrder['order_type_flag']) && $this->customized_meter_reading && in_array($PayOrder['order_type_flag'],array('cold_water_balance','hot_water_balance','electric_balance'))){
            $customizeMeterWaterReadingService=new CustomizeMeterWaterReadingService();
            $customizeMeterWaterReadingService->openUserWaterElectricUse($order['village_id'],$order['room_id'],$PayOrder['order_type_flag']);
        }
        $tmp_order = [];
        $tmp_order['plat_order_id'] = $plat_order_id;
        $tmp_order['score_used_count'] = 0;
        $tmp_order['score_can_get'] =  0;
        $tmp_order['order_id'] = $summary_id;
        $tmp_order['is_own'] = 0;
        $tmp_order['payment_money'] = 0;
        $tmp_order['score_deducte'] = 0;
        $tmp_order['balance_pay'] = isset($PayOrder['system_balance']) ? $PayOrder['system_balance']:0;
        $tmp_order['desc'] = '社区缴费';
        $order_info = array_merge($tmp_order,$order);
        $order_info['money'] = $PayOrder['pay_money'];
        $order_info['order_type'] = 'village_new_pay';
        $order_info['village_balance']=$village_balance;
        fdump_api(['is_service_time---'.__LINE__,$order_info,$tmp_order,$order],'storage/3error_log',1);
        if($pay_type_from=='system_balance'){
            invoke_cms_model('SystemBill/bill_method',['type'=>5,'order_info'=>$order_info,'is_fenzhang'=>0,'is_new_charge'=>1]);
        }
        return $rr;
    }

    /**
     *余额记录
     * @author: liukezhu
     * @date : 2021/11/22
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getUerBalanceRecord_old($where,$field=true,$page=0,$limit=10,$order='pigcms_id DESC'){
        $list = $this->UserMoneyList->getListRelateUser($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                if(isset($v['add_time']) && !empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
                if(isset($v['money']) && $v['money'] > 0){
                    $v['money']='+'.$v['money'];
                }
            }
            unset($v);
        }
        $count = $this->UserMoneyList->getCountRelateUser($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    public function getUerBalanceRecord($village_id,$where,$field='*',$page=0,$limit=10,$order='id DESC'){
        $db_village_user_money_log=new VillageUserMoneyLog();
        $list = $db_village_user_money_log->getLogList($where,$field,$order,$page,$limit);
        $configCustomizationService= new ConfigCustomizationService();
        $isAihorseCustomized=$configCustomizationService->getAihorseCustomizedJudge();
        if($list){
            $houseVillageDb = new HouseVillage();
            $village_info = $houseVillageDb->getInfo(array('village_id' => $village_id),'*');
            $db_house_new_offline = new HouseNewOfflinePay();
            $whereOffline=array();
            $whereOffline['property_id'] = $village_info['property_id'];
            $whereOffline['status'] = 1;
            $offlineObj = $db_house_new_offline->getList($whereOffline,'id,name');
            $off_pay_type_arr=array();
            if($offlineObj && !$offlineObj->isEmpty()){
                foreach ($offlineObj as $ovv){
                    $off_pay_type_arr[$ovv['id']]=$ovv['name'];
                }
            }
            $db_house_village_user_bind=new HouseVillageUserBind();
            $user_room_num=array();
            foreach ($list as &$v){
                if(isset($v['add_time']) && !empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
                $v['is_print']=0;
                if($isAihorseCustomized && $v['type']==1 && in_array($v['order_type'],array('pc_plus','user_recharge'))){
                    $v['is_print']=1;
                }
                $v['off_pay_type_str']='';
                if(isset($v['off_pay_type']) && $v['off_pay_type']){
                    $v['off_pay_type_str']=isset($off_pay_type_arr[$v['off_pay_type']]) ? $off_pay_type_arr[$v['off_pay_type']]:'未知线下支付';
                }
                if(isset($v['type']) && $v['type'] == 1){
                    $v['money']='+'.$v['money'];
                }elseif(isset($v['type']) && $v['type'] == 2){
                    $v['money']='-'.$v['money'];
                }
                if (!empty($v['order_id'])){
                    $v['order_no']=$v['order_id'];
                }else{
                    $v['order_no']='';
                }
                if(isset($user_room_num[$v['uid']])){
                    $v['room_num']=$user_room_num[$v['uid']];
                }else{
                    $user_bind_where=[
                        'village_id'=>$v['business_id'],
                        'uid'=>$v['uid'],
                        'type'=>[0,1,2,3],
                        'status'=>1
                    ];
                    $v['room_num']=$db_house_village_user_bind->getVillageUserNum($user_bind_where);
                    $user_room_num[$v['uid']]=$v['room_num'];
                }
                $v['money_type_str']='物业费';
                if(isset($v['money_type']) && $v['money_type']==1){
                    $v['money_type_str']='电费';
                }else if(isset($v['money_type']) && $v['money_type']==2){
                    $v['money_type_str']='热水费';
                }else if(isset($v['money_type']) && $v['money_type']==3){
                    $v['money_type_str']='冷水费';
                }
                if(isset($v['phone']) && !empty($v['phone'])){
                    $v['phone']=$houseVillageDb->phone_desensitization_v($v['phone'],$village_id,$village_info);
                }
            }
            unset($v);
        }
        $count = $db_village_user_money_log->getLogCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 消费记录
     * @author: liukezhu
     * @date : 2022/2/9
     * @param $village_id
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getOrderRecord_old($village_id,$uid,$page=0,$limit=10){
        $charge_type=$this->charge_name_arr;
        $where[] = ['o.is_discard', '=', 1];
        $where[] = ['o.is_refund', '=', 1];
        $where[] = ['o.village_id', '=', $village_id];
        $where[] = ['o.uid', '=', $uid];
        $where[] = ['o.order_type', 'in',['water','electric','gas']];
        $field='o.order_id,o.pigcms_id,p.name as project_name,o.order_type,o.add_time,r.unit_price,r.rate,r.start_ammeter,r.last_ammeter,u.now_money,o.total_money,o.pay_money,r.note,r.work_name as realname,o.is_paid,o.pay_time';
        $list = $this->HouseNewPayOrder->getLists($where, $field,$page,$limit);
        $count=0;
        if($list){
            foreach ($list as &$v){
                $v['order_type']=$charge_type[$v['order_type']];
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            }
            unset($v);
            $count = $this->HouseNewPayOrder->getCounts($where);
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    public function getOrderRecord($village_id,$uid,$page=0,$limit=10){
        $charge_type=$this->charge_name_arr;
        $where[] = ['o.is_discard', '=', 1];
        $where[] = ['o.is_refund', '=', 1];
        $where[] = ['o.village_id', '=', $village_id];
        $where[] = ['o.uid', '=', $uid];
        $where[] = ['o.village_balance', '>',0];
        $field='o.order_id,o.rule_id,o.pigcms_id,p.name as project_name,o.order_type,o.add_time,r.unit_price,r.rate,r.start_ammeter,r.last_ammeter,o.village_balance as now_money,o.total_money,o.pay_money,r.note,r.work_name as realname,o.is_paid,o.pay_time';
        $list = $this->HouseNewPayOrder->getLists($where, $field,$page,$limit);
        $count=0;
        if (!empty($list)){
            $list=$list->toArray();
        }
        $db_house_new_charge_rule=new HouseNewChargeRule();
        if($list){
            foreach ($list as &$v){
                if (empty($v['unit_price'])&&!empty($v['rule_id'])&&in_array($v['order_type'],['water','electric','gas'])){
                    $rule_info=$db_house_new_charge_rule->getOne(['id'=>$v['rule_id']],'unit_price,rate');
                    if (!empty($rule_info)){
                        $v['unit_price'] =$rule_info['unit_price'];
                        $v['rate'] =$rule_info['rate'];
                    }
                }
                $v['order_type']=$charge_type[$v['order_type']];
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            }
            unset($v);
            $count = $this->HouseNewPayOrder->getCounts($where);
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }
    /**
     * 一键催缴
     * @author: liukezhu
     * @date : 2021/11/22
     * @param $village_id
     * @param $order_id
     * @return bool
     */
    public function sendMessage($village_id,$order_id,$property_id=0){
        $where[] = ['o.is_discard', '=', 1];
        $where[] = ['o.order_id', '=', $order_id];
        $where[] = ['o.village_id', '=', $village_id];
        $field='o.*,r.layer_num';
        $list = $this->HouseNewPayOrder->get_ones($where,$field)->toArray();
        if($list['pay_money'] < 0){
            return false;
        }
        $room = $this->HouseVillageUserVacancy->getLists(['pigcms_id' => $list['room_id']]);
        $href = get_base_url('pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id=' . $village_id . '&pigcms_id='.$list['pigcms_id']);
        $address='--';
        if($room){
            $room=$room->toArray();
            if($room){
                $room=$room[0];
            }
            $address =$this->HouseVillageService->word_replce_msg(array('single_name'=>$room['single_name'],'floor_name'=>$room['floor_name'],'layer'=>$room['layer_name'],'room'=>$room['room']),$village_id);
        }
        $village_name= $this->HouseVillage->getOne($village_id,'village_name');
        if($village_name){
            $village_name=$village_name['village_name'];
        }else{
            $village_name='--';
        }
        $name = (new HouseVillageUserBind())->where(['uid'=>$list['uid'],'village_id'=>$village_id])->value('name');
        $this->HouseNewCashierService->sendCashierMessage($list['uid'],$href,$address,$village_name,$list['total_money'],$property_id,$list['order_type'],$list['rule_id'],$name,$village_id);
        return true;
    }


    //todo 组装当前操作账号数据
    public function getRoleData($uid,$login_role,$adminUser){
        $system_remarks='角色ID：'.$login_role.' ';
        $account='';
        //4:物业普通管理员
        //            [id] => 9
        //            [property_id] => 13
        //            [account] => 13083199169
        //            [realname] => 栗子
        //            [phone] => 13083199169
        //5:小区工作人员
        //            [id] => 37
        //            [village_id] => 50
        //            [account] => 13083199169
        //            [realname] => 梅梅
        //            [phone] => 15695519169
        //6:系统后台登录的小区超级管理员账号
        //7:物业后台登录的小区超级管理员账号
        $adminLogService = new AdminLoginService();
        if(in_array($login_role,$adminLogService->villageRoleArr)){
            //系统后台或物业后台登录小区记录小区账号
            $system_remarks.='小区ID：'.$adminUser['village_id'].'名称：'.$adminUser['village_name'];
            $account=$adminUser['village_name'];
        }else{
            //人员账号登录小区
            $system_remarks.='用户ID：'.$uid;
            if(isset($adminUser['account'])){
                $system_remarks.=' account：'.$adminUser['account'];
            }
            if(isset($adminUser['realname'])){
                $system_remarks.=' realname：'.$adminUser['realname'];
            }
            if(isset($adminUser['phone'])){
                $system_remarks.=' phone：'.$adminUser['phone'];
            }
            if(isset($adminUser['realname']) && !empty($adminUser['realname'])){
                $account=$adminUser['realname'];
            }elseif (isset($adminUser['phone']) && !empty($adminUser['phone'])){
                $account=$adminUser['phone'];
            }elseif (isset($adminUser['account']) && !empty($adminUser['account'])){
                $account=$adminUser['account'];
            }
        }
        return ['remarks'=>$system_remarks,'account'=>$account];
    }


    /**
     * 查询用户在当前小区的住户余额
     * @author:zhubaodi
     * @date_time: 2022/6/6 15:56
     */
    public function getVillageUser($uid,$village_id,$charge_type=''){
        $db_house_village_config=new HouseVillageConfig();
        $db_village_user_money_list=new VillageUserMoneyList();
        $res=array();
        $can_use_village_balance=$db_house_village_config->getOne(['village_id'=>$village_id],'open_village_balance');
        if ($can_use_village_balance['open_village_balance']==1){
            $res['can_use_village_balance']=true;
        }else{
            $res['can_use_village_balance']=false;
        }
        $res['current_money']=0;
        $res['hot_water_balance']=0;
        $res['cold_water_balance']=0;
        $res['electric_balance']=0;
        $user_money=$db_village_user_money_list->getOne(['uid'=>$uid,'business_id'=>$village_id,'business_type'=>1],'uid,current_money,hot_water_balance,cold_water_balance,electric_balance');
        if (!empty($user_money)){
            $res['current_money']= round($user_money['current_money'],2);
            $res['hot_water_balance']=round($user_money['hot_water_balance'],2);
            $res['cold_water_balance']=round($user_money['cold_water_balance'],2);
            $res['electric_balance']=round($user_money['electric_balance'],2);
        }
        $res['nickname']='';
        $res['phone']='';
        $res['current_balance_desc']='住户余额：'.$res['current_money'].'元';
        if($this->customized_meter_reading && $res['can_use_village_balance'] && $charge_type){
            if($charge_type=='village_balance'){
                $res['current_balance_desc']='物业余额：'.$res['current_money'].'元';
            }else if($charge_type=='cold_water_balance'){
                $res['current_balance_desc']='冷水余额：'.$res['cold_water_balance'].'元';
            }else if($charge_type=='hot_water_balance'){
                $res['current_balance_desc']='热水余额：'.$res['hot_water_balance'].'元';
            }else if($charge_type=='electric_balance'){
                $res['current_balance_desc']='电费余额：'.$res['electric_balance'].'元';
            }
        }
        $userObj=$this->User->getOne(['uid'=>$uid],'nickname,phone');
        if($userObj && !$userObj->isEmpty()){
            $res['nickname']=$userObj['nickname'];
            $res['phone']=$userObj['phone'];
        }
        $res['village_id']=$village_id;
        $res['uid']=$uid;
        $res['village_name']='';
        $village_info = $this->HouseVillage->getOne($village_id, 'village_name');
        if($village_info && !$village_info->isEmpty()){
            $res['village_name']=$village_info['village_name'];
        }
        return $res;

    }

    /**
     * 添加小区住户余额变更记录
     * @author:zhubaodi
     * @date_time: 2022/6/6 15:56
     */
    public function addVillageUserMoney($data){
        
        $db_village_user_money_list=new VillageUserMoneyList();
        $db_village_user_money_log=new VillageUserMoneyLog();
        $user_money=$db_village_user_money_list->getOne(['uid'=>$data['uid'],'business_id'=>$data['village_id'],'business_type'=>1],'*');
        $opt_money_type='';
        if(isset($data['opt_money_type']) && !empty($data['opt_money_type']) && $data['opt_money_type']!='village_balance'){
            $opt_money_type=$data['opt_money_type'];
        }
        $is_have_recode=false;
        if($user_money && !$user_money->isEmpty()){
            $user_money=$user_money->toArray();
            $is_have_recode=true;
        }else{
            $user_money=array();
            $user_money['current_money']=0;
            $user_money['cold_water_balance']=0;
            $user_money['hot_water_balance']=0;
            $user_money['electric_balance']=0;
        }
        $msg_balance='住户';
        $arr_add=[
            'uid'=>$data['uid'],
            'business_id'=>$data['village_id'],
            'business_type'=>1,
            'add_time'=>time(),
            'update_time'=>time(),
            'remarks'=>isset($data['desc'])?$data['desc']:'',
        ];
        $saveArr=array();
        if($opt_money_type=='current_money'){
            $msg_balance='物业';
        }else if($opt_money_type=='cold_water_balance'){
            $msg_balance='冷水';
        }else if($opt_money_type=='hot_water_balance'){
            $msg_balance='热水';
        }else if($opt_money_type=='electric_balance'){
            $msg_balance='电费';
        }

        if ($data['current_village_balance']<0){
            return array('error_code' => true, 'msg' =>  L_($msg_balance.'余额数据有误'));
        }
        if ($data['type']==1){
            if($opt_money_type=='cold_water_balance'){
                $saveArr['cold_water_balance']=$user_money['cold_water_balance']+$data['current_village_balance'];
            }elseif($opt_money_type=='hot_water_balance'){
                $saveArr['hot_water_balance']=$user_money['hot_water_balance']+$data['current_village_balance'];
            }elseif($opt_money_type=='electric_balance'){
                $saveArr['electric_balance']=$user_money['electric_balance']+$data['current_village_balance'];
            }else{
                $saveArr['current_money']=$user_money['current_money']+$data['current_village_balance'];
            }
        }elseif($data['type']==2){

            if($opt_money_type=='cold_water_balance'){
                if($user_money['cold_water_balance'] < $data['current_village_balance']){
                    return array('error_code' => true, 'msg' =>  L_($msg_balance.'余额扣除失败！请联系管理员协助解决。'));
                }
                $saveArr['cold_water_balance']=$user_money['cold_water_balance']-$data['current_village_balance'];
            }elseif($opt_money_type=='hot_water_balance'){
                if($user_money['hot_water_balance'] < $data['current_village_balance']){
                    return array('error_code' => true, 'msg' =>  L_($msg_balance.'余额扣除失败！请联系管理员协助解决。'));
                }
                $saveArr['hot_water_balance']=$user_money['hot_water_balance']-$data['current_village_balance'];
            }elseif($opt_money_type=='electric_balance'){
                if($user_money['electric_balance'] < $data['current_village_balance']){
                    return array('error_code' => true, 'msg' =>  L_($msg_balance.'余额扣除失败！请联系管理员协助解决。'));
                }
                $saveArr['electric_balance']=$user_money['electric_balance']-$data['current_village_balance'];
            }else{
                if($user_money['current_money'] < $data['current_village_balance']){
                    return array('error_code' => true, 'msg' =>  L_($msg_balance.'余额扣除失败！请联系管理员协助解决。'));
                }
                $saveArr['current_money']=$user_money['current_money']-$data['current_village_balance'];
            }
        }

        if (!$is_have_recode && $data['type']==1){
            if(!empty($saveArr)){
                $arr_add =array_merge($arr_add,$saveArr);
            }
            $update_rets=$db_village_user_money_list->addOne($arr_add);
        }else{
            $update_rets=$db_village_user_money_list->saveOne(['uid'=>$data['uid'],'business_id'=>$data['village_id'],'business_type'=>1],$saveArr);
        }
        if(isset($data['come_from']) && $data['come_from']=='house_new_pay_order_refund' && isset($data['summary_id']) && $data['summary_id']>0){
            $paidOrderRecordDb = new PaidOrderRecord();
            $whereArr=array();
            $whereArr[]=array('village_id','=',$data['village_id']);
            $whereArr[]=array('source_from','=',1);
            $whereArr[]=array('order_id','=',$data['summary_id']);
            $whereArr[]=array('table_name','=','house_new_pay_order_summary');
            $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereArr,'*');
            if($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()){
                $refund_money=$data['current_village_balance']+$paidOrderRecordInfo['refund_money'];
                $refund_money=round($refund_money,2);
                $all_pay_money=$paidOrderRecordInfo['balance_money']+$paidOrderRecordInfo['pay_money'];
                $refund_status=1;
                if($refund_money>=$all_pay_money){
                    $refund_status=2;
                }
                $save_record_data=array('refund_status'=>$refund_status,'refund_money'=>$refund_money);
                $save_record_data['last_refund_time']=time();
                $paidOrderRecordDb->paidOrderRefundHandle($paidOrderRecordInfo['id'],$paidOrderRecordInfo['pay_order_info_id'],'',$save_record_data);
            }
        }
        $log_id=0;
       if (!empty($update_rets)){
            $log_data=[
                'uid'=>$data['uid'],
                'business_type'=>1,
                'business_id'=>$data['village_id'],
                'type'=>$data['type'],
                'current_money'=>0,
                'money'=>$data['current_village_balance'],
                'after_price'=>0,
                'add_time'=>time(),
                'role_id'=>isset($data['role_id'])?$data['role_id']:0,
                'ip'=>get_client_ip(),
                'desc'=>$data['desc'],
                'order_id'=>isset($data['order_id'])?$data['order_id']:0,
                'order_type'=>isset($data['order_type'])?$data['order_type']:1,
            ];
            if(isset($data['extra_data']) && $data['extra_data']){
                $log_data['extra_data']=json_encode($data['extra_data'],JSON_UNESCAPED_UNICODE);
                if(isset($data['extra_data']['off_pay_type']) && $data['extra_data']['off_pay_type']){
                    $log_data['off_pay_type']=$data['extra_data']['off_pay_type'];
                }
                if(isset($data['extra_data']['order_type']) && $data['extra_data']['order_type']){
                    $log_data['order_type']=$data['extra_data']['order_type'];
                }
            }
           if($opt_money_type=='cold_water_balance'){
               $log_data['after_price']=$saveArr['cold_water_balance'];
               $log_data['current_money']=$user_money['cold_water_balance'];
               $log_data['money_type']=3;
           }elseif($opt_money_type=='hot_water_balance'){
               $log_data['after_price']=$saveArr['hot_water_balance'];
               $log_data['current_money']=$user_money['hot_water_balance'];
               $log_data['money_type']=2;
           }elseif($opt_money_type=='electric_balance'){
               $log_data['after_price']=$saveArr['electric_balance'];
               $log_data['current_money']=$user_money['electric_balance'];
               $log_data['money_type']=1;
           }else{
               $log_data['after_price']=$saveArr['current_money'];
               $log_data['current_money']=$user_money['current_money'];
           }
           $log_id=$db_village_user_money_log->addOne($log_data);

       }else{
           return array('error_code' => true,'log_id'=>$log_id, 'msg' =>  L_($msg_balance.'余额扣除失败！请联系管理员协助解决。'));
       }
        return array('error_code' => false,'log_id'=>$log_id, 'msg' =>  L_('ok'));

    }

    /**
     * 查询用户在当前小区的关联房间列表
     * @author:zhubaodi
     * @date_time: 2022/6/8 17:40
     */
    public function getUserRoomList($uid,$village_id,$page,$limit){
        $db_house_village_user_bind=new HouseVillageUserBind();
        $where=[
            'h.uid'=>$uid,
            'h.village_id'=>$village_id,
            'h.type'=>[0,1,2,3],
            'h.status'=>1,
        ];
        $field='a.room,b.single_name,c.floor_name,l.layer_name,h.pigcms_id as bind_id, h.name as bind_name,h.phone as bind_phone';
        $list=$db_house_village_user_bind->getuserList($where,$field,$page,$limit);
        $count=$db_house_village_user_bind->getuserCount($where);
        $data1=[];
        $data1['list']=$list;
        $data1['total_limit']=$limit;
        $data1['count']=$count;

        return $data1;
    }

    /**
     * 查询用户在当前小区的关联车位列表
     * @author:zhubaodi
     * @date_time: 2022/6/8 17:40
     */
    public function getUserPositionList($uid,$village_id,$page,$limit){
        $db_house_village_bind_position=new HouseVillageBindPosition();
        $db_house_village_user_bind=new HouseVillageUserBind();
        $user_bind_where=[
            'village_id'=>$village_id,
            'uid'=>$uid,
            'type'=>[0,1,2,3],
            'status'=>1
        ];
        $pigcms_id_arr=$db_house_village_user_bind->getUserColumn($user_bind_where,'pigcms_id');
        $where=[
            'b.user_id'=>$pigcms_id_arr,
            'b.village_id'=>$village_id,
        ];
        $field='p.position_id,p.garage_id,p.position_num,g.garage_num';
        $list=$db_house_village_bind_position->getUserPositionList($where,$field,$page,$limit);
        $count=$db_house_village_bind_position->getUserPositionCount($where);
        $data1=[];
        $data1['list']=$list;
        $data1['total_limit']=$limit;
        $data1['count']=$count;
        return $data1;
    }


    /**
     * 批量变更小区住户余额
     * @author:zhubaodi
     * @date_time: 2022/6/6 15:56
     */
    public function addAllVillageUserMoney($data){
        $db_village_user_money_list=new VillageUserMoneyList();
        $db_village_user_money_log=new VillageUserMoneyLog();
        $db_user=new User();
        $whereArr=array(['business_type','=',1]);
        $whereArr[]=array('business_id','=',$data['village_id']);
        if(is_array($data['uid'])){
            $whereArr[]=array('uid','in',$data['uid']);
        }else{
            $whereArr[]=array('uid','=',$data['uid']);
        }
        $user_money_list=$db_village_user_money_list->getList($whereArr);
        if (!empty($user_money_list)){
            $user_money_list=$user_money_list->toArray();
        }
        $opt_money_type='';
        if(isset($data['opt_money_type']) && !empty($data['opt_money_type']) && $data['opt_money_type']!='village_balance'){
            $opt_money_type=$data['opt_money_type'];
        }
        if ($data['current_village_balance']<0){
            return array('error_code' => true, 'msg' =>  L_('住户余额数据有误'));
        }
        $msg_balance='住户';
        if($opt_money_type=='current_money'){
            $msg_balance='物业';
        }else if($opt_money_type=='cold_water_balance'){
            $msg_balance='冷水';
        }else if($opt_money_type=='hot_water_balance'){
            $msg_balance='热水';
        }else if($opt_money_type=='electric_balance'){
            $msg_balance='电费';
        }
        $error=[];
        if (!empty($user_money_list)){
            foreach ($user_money_list as $v){
                $user_info=$db_user->getOne(['uid'=>$v['uid']],'nickname');
                $saveArr=array();
                if ($data['type']==1){
                    if($opt_money_type=='cold_water_balance'){
                        $saveArr['cold_water_balance']=$v['cold_water_balance']+$data['current_village_balance'];
                    }elseif($opt_money_type=='hot_water_balance'){
                        $saveArr['hot_water_balance']=$v['hot_water_balance']+$data['current_village_balance'];
                    }elseif($opt_money_type=='electric_balance'){
                        $saveArr['electric_balance']=$v['electric_balance']+$data['current_village_balance'];
                    }else{
                        $saveArr['current_money']=$v['current_money']+$data['current_village_balance'];
                    }
                }elseif($data['type']==2){
                    if($opt_money_type=='cold_water_balance'){
                        if($v['cold_water_balance'] < $data['current_village_balance']){
                            $error['msg']='余额不足，扣除失败';
                            $error['uid'][]=$v['uid'];
                            $error['name'][]=$user_info['nickname'];
                            continue;
                        }
                        $saveArr['cold_water_balance']=$v['cold_water_balance']-$data['current_village_balance'];
                    }elseif($opt_money_type=='hot_water_balance'){
                            if($v['hot_water_balance'] < $data['current_village_balance']){
                                $error['msg']='余额不足，扣除失败';
                                $error['uid'][]=$v['uid'];
                                $error['name'][]=$user_info['nickname'];
                                continue;
                            }
                        $saveArr['hot_water_balance']=$v['hot_water_balance']-$data['current_village_balance'];
                    }elseif($opt_money_type=='electric_balance'){
                        if($v['electric_balance'] < $data['current_village_balance']){
                            $error['msg']='余额不足，扣除失败';
                            $error['uid'][]=$v['uid'];
                            $error['name'][]=$user_info['nickname'];
                            continue;
                        }
                        $saveArr['electric_balance']=$v['electric_balance']-$data['current_village_balance'];
                    }else{
                        if($v['current_money'] < $data['current_village_balance']){
                            $error['msg']='余额不足，扣除失败';
                            $error['uid'][]=$v['uid'];
                            $error['name'][]=$user_info['nickname'];
                            continue;
                        }
                        $saveArr['current_money']=$v['current_money']-$data['current_village_balance'];
                    }
                }
                if (empty($saveArr)){
                    $error['msg']='余额不足，扣除失败';
                    $error['uid'][]=$v['uid'];
                    $error['name'][]=$user_info['nickname'];
                    continue;
                }
                $log_id=0;
                $saveArr['remarks']= $data['desc'];
                $update_rets=$db_village_user_money_list->saveOne(['id'=>$v['id'],'uid'=>$v['uid'],'business_id'=>$data['village_id']],$saveArr);
                if (!empty($update_rets)){
                    $log_data=[
                        'uid'=>$v['uid'],
                        'business_type'=>1,
                        'business_id'=>$data['village_id'],
                        'type'=>$data['type'],
                        'current_money'=>0,
                        'money'=>$data['current_village_balance'],
                        'after_price'=>0,
                        'add_time'=>time(),
                        'role_id'=>isset($data['role_id'])?$data['role_id']:0,
                        'ip'=>get_client_ip(),
                        'desc'=>$data['desc'],
                        'order_id'=>isset($data['order_id'])?$data['order_id']:0,
                        'order_type'=>isset($data['order_type'])?$data['order_type']:1,
                    ];
                    if(isset($data['extra_data']) && $data['extra_data']){
                        $log_data['extra_data']=json_encode($data['extra_data'],JSON_UNESCAPED_UNICODE);
                        if(isset($data['extra_data']['off_pay_type']) && $data['extra_data']['off_pay_type']){
                            $log_data['off_pay_type']=$data['extra_data']['off_pay_type'];
                        }
                        if(isset($data['extra_data']['order_type']) && $data['extra_data']['order_type']){
                            $log_data['order_type']=$data['extra_data']['order_type'];
                        }
                    }
                    if($opt_money_type=='cold_water_balance'){
                        $log_data['after_price']=$saveArr['cold_water_balance'];
                        $log_data['current_money']=$v['cold_water_balance'];
                        $log_data['money_type']=3;
                    }elseif($opt_money_type=='hot_water_balance'){
                        $log_data['after_price']=$saveArr['hot_water_balance'];
                        $log_data['current_money']=$v['hot_water_balance'];
                        $log_data['money_type']=2;
                    }elseif($opt_money_type=='electric_balance'){
                        $log_data['after_price']=$saveArr['electric_balance'];
                        $log_data['current_money']=$v['electric_balance'];
                        $log_data['money_type']=1;
                    }else{
                        $log_data['after_price']=$saveArr['current_money'];
                        $log_data['current_money']=$v['current_money'];
                    }
                    $log_id=$db_village_user_money_log->addOne($log_data);

                }else{
                    $error['msg']='操作错误';
                    $error['uid'][]=$v['uid'];
                    $error['name'][]=$user_info['nickname'];
                }
            }
        }else if(isset($data['bind_pigcms_id']) && $data['bind_pigcms_id']>0){
            $addNewData=array('uid'=>$data['uid']['0'],'business_id'=>$data['village_id'],'business_type'=>1);
            if ($data['type']==1){
                $log_data=[
                    'uid'=>$data['uid']['0'],
                    'business_type'=>1,
                    'business_id'=>$data['village_id'],
                    'type'=>$data['type'],
                    'current_money'=>0,
                    'money'=>$data['current_village_balance'],
                    'after_price'=>$data['current_village_balance'],
                    'add_time'=>time(),
                    'role_id'=>isset($data['role_id'])?$data['role_id']:0,
                    'ip'=>get_client_ip(),
                    'desc'=>$data['desc'],
                    'order_id'=>isset($data['order_id'])?$data['order_id']:0,
                    'order_type'=>isset($data['order_type'])?$data['order_type']:1,
                ];
                if($opt_money_type=='cold_water_balance'){
                    $addNewData['cold_water_balance']=$data['current_village_balance'];
                    $log_data['money_type']=3;
                }elseif($opt_money_type=='hot_water_balance'){
                    $addNewData['hot_water_balance']=$data['current_village_balance'];
                    $log_data['money_type']=2;
                }elseif($opt_money_type=='electric_balance'){
                    $addNewData['electric_balance']=$data['current_village_balance'];
                    $log_data['money_type']=1;
                }else{
                    $addNewData['current_money']=$data['current_village_balance'];
                }
                $addNewData['remarks']=$data['desc'];
                $addNewData['add_time']=time();
                $addNewData['update_time']=time();

                $insert_id=$db_village_user_money_list->addOne($addNewData);
                if(isset($data['extra_data']) && $data['extra_data']){
                    $log_data['extra_data']=json_encode($data['extra_data'],JSON_UNESCAPED_UNICODE);
                    if(isset($data['extra_data']['off_pay_type']) && $data['extra_data']['off_pay_type']){
                        $log_data['off_pay_type']=$data['extra_data']['off_pay_type'];
                    }
                    if(isset($data['extra_data']['order_type']) && $data['extra_data']['order_type']){
                        $log_data['order_type']=$data['extra_data']['order_type'];
                    }
                }
                $log_id=$db_village_user_money_log->addOne($log_data);
        
            }else if($data['type']==2){
                $user_info=$db_user->getOne(['uid'=>$data['uid']['0']],'nickname');
                $error['msg']='操作错误';
                $error['uid'][]=$data['uid']['0'];
                $error['name'][]=$user_info['nickname'];
            }
        }
        if (!empty($error['uid'])){
            $name=implode(',',$error['name']);
            $msg='用户:'.$name.';'.$msg_balance.'余额扣除失败！请联系管理员协助解决。';
            return array('error_code' => true,'log_id'=>$log_id, 'msg' => $msg);
        }
        return array('error_code' => false,'log_id'=>$log_id, 'msg' =>  L_('ok'));
    }

    /**
     * 导入抄表
     * @author lijie
     * @date_time 2021/10/22
     * @param $file
     * @param $charge_name
     * @param $village_id
     * @param $uid
     * @return array|string
     * @throws \think\Exception
     */
    public function upload($file, $village_id, $uid)
    {
        $savepath = $file;
        $filed = [
            'A' => 'single_name',
            'B' => 'floor_name',
            'C' => 'layer_name',
            'D' => 'room',
            'E' => 'name',
            'F' => 'phone',
            'G' => 'price',
        ];
        $customized_meter_reading = cfg('customized_meter_reading');
        if (!empty($customized_meter_reading)) {
            $filed['H'] = 'electric_balance';
            $filed['I'] = 'cold_water_balance';
            $filed['J'] = 'hot_water_balance';
        }
        $file_arr = explode('/', $file);
        $count = count($file_arr);
        if ($count > 1) {
            $file_data = $file_arr[$count - 1];
            $file_arr = explode('.', $file_data);
            if (count($file_arr) == 2) {
                $file_type = $file_arr[1];
            }
        }
        if (!isset($file_type)) {
            return ['error' => false, 'msg' => '文件上传错误，导入失败'];
        }
        if ($file_type == 'xlsx') {
            $data = (new HouseNewMeterService())->readFile($_SERVER['DOCUMENT_ROOT'] . $savepath, $filed, 'Xlsx');
        } else {
            $data = (new HouseNewMeterService())->readFile($_SERVER['DOCUMENT_ROOT'] . $savepath, $filed, 'Xls');

        }
        $data = array_values($data);
        $house_village = new HouseVillage();
        $village_single = new HouseVillageSingle();
        $village_floor = new HouseVillageFloor();
        $village_layer = new HouseVillageLayer();
        $vacancy = new HouseVillageUserVacancy();
        $user_bind = new HouseVillageUserBind();
        $db_user = new User();
        $res = '';
        if (!empty($data)) {
            $data_print = [];
            foreach ($data as $value) {
                if (empty($value['single_name']) || empty($value['floor_name']) || empty($value['layer_name']) || empty($value['room']) || empty($value['name']) || empty($value['phone'])) {
                    $value['failReason'] = '参数填写不全';
                    $data_print[] = $value;
                    continue;
                }
                $value['price'] = floatval($value['price']);
                $value['price'] = round($value['price'], 2);
                $electric_balance = 0;
                if (isset($value['electric_balance']) && !empty($value['electric_balance'])) {
                    $electric_balance = floatval($value['electric_balance']);
                    $electric_balance = round($electric_balance, 2);
                }
                $cold_water_balance = 0;
                if (isset($value['cold_water_balance']) && !empty($value['cold_water_balance'])) {
                    $cold_water_balance = floatval($value['cold_water_balance']);
                    $cold_water_balance = round($cold_water_balance, 2);
                }
                $hot_water_balance = 0;
                if (isset($value['hot_water_balance']) && !empty($value['hot_water_balance'])) {
                    $hot_water_balance = floatval($value['hot_water_balance']);
                    $hot_water_balance = round($hot_water_balance, 2);
                }
                if ($customized_meter_reading) {
                    if ($value['price'] <= 0 && $electric_balance <= 0 && $cold_water_balance <= 0 && $hot_water_balance <= 0) {
                        $value['failReason'] = '物业余额，电费余额，冷水余额，热水余额，请至少正确填写一项！';
                        $data_print[] = $value;
                        continue;
                    }
                } else if ($value['price'] <= 0) {
                    $value['failReason'] = '缴费金额需大于0';
                    $data_print[] = $value;
                    continue;
                }
                $village_info = $house_village->getInfo(['village_id' => $village_id]);
                if (empty($village_info)) {
                    $value['failReason'] = '所属小区不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $single_info = $village_single->getOne(['single_name' => trim($value['single_name']), 'status' => 1, 'village_id' => $village_id]);
                if (empty($single_info)) {
                    $value['failReason'] = '所属楼栋不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $floor_info = $village_floor->getOne(['floor_name' => trim($value['floor_name']), 'status' => 1, 'single_id' => $single_info['id']]);
                if (empty($floor_info)) {
                    $value['failReason'] = '所属单元不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $layer_info = $village_layer->getOne(['layer_name' => trim($value['layer_name']), 'status' => 1, 'floor_id' => $floor_info['floor_id']]);
                if (empty($layer_info)) {
                    $value['failReason'] = '所属楼层不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $vacancy_info = $vacancy->getOne(['room' => trim($value['room']), 'status' => [1, 2, 3], 'layer_id' => $layer_info['id']]);
                if (empty($vacancy_info)) {
                    $value['failReason'] = '所属房间号不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $bind_where = [
                    'village_id' => $village_id,
                    'name' => trim($value['name']),
                    'phone' => trim($value['phone']),
                    'type' => [0, 1, 2, 3],
                    'status' => 1,
                    'vacancy_id' => $vacancy_info['pigcms_id']
                ];
                $user_info = $user_bind->getOne($bind_where, 'uid,pigcms_id');
                if (empty($user_info)) {
                    $value['failReason'] = '请确保住户姓名或者手机号是否正确';
                    $data_print[] = $value;
                    continue;
                }
                if (empty($user_info['uid'])) {
                    $value['failReason'] = '请确保住户已经登陆过平台并绑定了对应手机号';
                    $data_print[] = $value;
                    continue;
                }
                $user = $db_user->getOne(['uid'=>$user_info['uid']],'phone');
                $user['phone'] = $user['phone'] ?? '';
                if($user['phone'] != trim($value['phone'])){
                    $value['failReason'] = '请确保住户已经登陆过平台并绑定了对应手机号';
                    $data_print[] = $value;
                    continue;
                }

                if (!empty($customized_meter_reading)) {
                    $log_data = [
                        'uid' => $user_info['uid'],
                        'village_id' => $village_id,
                        'type' => 1,
                        'current_village_balance' => $value['price'],
                        'role_id' => isset($uid) ? $uid : 0,
                        'desc' => '导入物业余额',
                        'opt_money_type' => '',
                    ];
                    if ($value['price'] > 0) {
                        $res = $this->addVillageUserMoney($log_data);
                    }
                    if ($electric_balance > 0) {
                        $log_data['desc'] = '导入电费余额';
                        $log_data['opt_money_type'] = 'electric_balance';
                        $log_data['current_village_balance'] = $electric_balance;
                        $res = $this->addVillageUserMoney($log_data);
                    }
                    if ($cold_water_balance > 0) {
                        $log_data['desc'] = '导入冷水余额';
                        $log_data['opt_money_type'] = 'cold_water_balance';
                        $log_data['current_village_balance'] = $cold_water_balance;
                        $res = $this->addVillageUserMoney($log_data);
                    }
                    if ($hot_water_balance > 0) {
                        $log_data['desc'] = '导入热水余额';
                        $log_data['opt_money_type'] = 'hot_water_balance';
                        $log_data['current_village_balance'] = $hot_water_balance;
                        $res = $this->addVillageUserMoney($log_data);
                    }
                } else {
                    $log_data = [
                        'uid' => $user_info['uid'],
                        'village_id' => $village_id,
                        'type' => 1,
                        'current_village_balance' => $value['price'],
                        'role_id' => isset($uid) ? $uid : 0,
                        'desc' => '导入住户余额'
                    ];
                    $res = $this->addVillageUserMoney($log_data);
                }

                if ($res['error_code']) {
                    $value['failReason'] = '导入失败';
                    $data_print[] = $value;
                }
            }
            if (!empty($data_print)) {
                $title = ['楼号', '单元名称', '层号', '房间号', '住户姓名', '住户手机号', '缴费金额（元）', '失败原因'];
                $res = (new HouseNewMeterService())->exportExcel($title, $data_print, '导入住户余额失败列表' . time());
                return ['error' => false, 'msg' => '导入失败', 'data' => $res['url']];
            }

        }
        return ['error' => true, 'msg' => '导入成功', 'data' => []];

    }

    public function excelExportBalanceRecord($where, $uid = 0,$village_id=0)
    {
        $db_village_user_money_log = new VillageUserMoneyLog();
        $db_house_village_user_bind = new HouseVillageUserBind();
        /*
        $nickname = '';
        $phone = '';
        if ($uid > 0) {
            $userObj = $this->User->getOne(['uid' => $uid], 'nickname,phone');
            if ($userObj && !$userObj->isEmpty()) {
                $nickname = $userObj['nickname'];
                $phone = $userObj['phone'];
            }
        }
        */
        //$list = $db_village_user_money_log->getList($where, true, 'id DESC', 0, 0);
        $field='ml.*,u.nickname as name,u.phone';
        $list = $db_village_user_money_log->getLogList($where,$field,'id DESC',0,0);
        $datas = array();
        if ($list && !$list->isEmpty()) {
            $list = $list->toArray();
        }
        if (empty($list)) {
            throw new \think\Exception("暂无数据导出");
        }
        $serviceHouseVillage = new HouseVillage();
        $village_info = $serviceHouseVillage->getInfo(array('village_id' => $village_id),'property_id');
        $db_house_new_offline = new HouseNewOfflinePay();
        $whereOffline=array();
        $whereOffline['property_id'] = $village_info['property_id'];
        $whereOffline['status'] = 1;
        $offlineObj = $db_house_new_offline->getList($whereOffline,'id,name');
        $off_pay_type_arr=array();
        if($offlineObj && !$offlineObj->isEmpty()){
            foreach ($offlineObj as $ovv){
                $off_pay_type_arr[$ovv['id']]=$ovv['name'];
            }
        }
        $configCustomizationService= new ConfigCustomizationService();
        $isAihorseCustomized=$configCustomizationService->getAihorseCustomizedJudge();
        $user_room_list=array();
        foreach ($list as $v) {
            $tmpArr = array();
            $tmpArr['name']=$v['name'];
            $tmpArr['phone']=$v['phone'];
            if($uid==0){
                $tmpArr['room_num']='';
                if(isset($user_room_list[$v['uid']])){
                    $tmpArr['room_num']=$user_room_list[$v['uid']];
                }else{
                    $where=[
                        'h.uid'=>$v['uid'],
                        'h.village_id'=>$v['business_id'],
                        'h.type'=>[0,1,2,3],
                        'h.status'=>1,
                    ];
                    $field='a.room,b.single_name,c.floor_name,l.layer_name,h.pigcms_id as bind_id, h.name as bind_name,h.phone as bind_phone';
                    $homelist=$db_house_village_user_bind->getuserList($where,$field,0,100);
                    $homeRoom=array();
                    if($homelist && !$homelist->isEmpty()){
                        $homelist=$homelist->toArray();
                        foreach ($homelist as $vv){
                            if(is_numeric($vv['single_name'])){
                                $vv['single_name']=$vv['single_name'].'栋';
                            }
                            if(is_numeric($vv['floor_name'])){
                                $vv['floor_name']=$vv['floor_name'].'单元';
                            }
                            if(is_numeric($vv['layer_name'])){
                                $vv['layer_name']=$vv['layer_name'].'层';
                            }
                            $address=$this->HouseVillageService->word_replce_msg(array('single_name'=>$vv['single_name'],'floor_name'=>$vv['floor_name'],'layer'=>$vv['layer_name'],'room'=>$vv['room']),$v['business_id']);
                            $homeRoom[]=$address;
                        }
                    }
                    if(!empty($homeRoom)){
                        $tmpArr['room_num']=implode('；',$homeRoom);
                    }
                    $user_room_list[$v['uid']]=$tmpArr['room_num'];
                }
            }
            $tmpArr['order_no'] = !empty($v['order_id']) ? $v['order_id'] : '';
            $tmpArr['money_type_str'] = '物业费';
            if (isset($v['money_type']) && $v['money_type'] == 1) {
                $tmpArr['money_type_str'] = '电费';
            } else if (isset($v['money_type']) && $v['money_type'] == 2) {
                $tmpArr['money_type_str'] = '热水费';
            } else if (isset($v['money_type']) && $v['money_type'] == 3) {
                $tmpArr['money_type_str'] = '冷水费';
            }
            $tmpArr['add_time'] = '';
            if (isset($v['add_time']) && !empty($v['add_time'])) {
                $tmpArr['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            }
            $tmpArr['current_money'] = $v['current_money'] > 0 ? strval($v['current_money']) : '0';
            if (isset($v['type']) && $v['type'] == 1) {
                $tmpArr['money'] = '+' . $v['money'];
            } elseif (isset($v['type']) && $v['type'] == 2) {
                $tmpArr['money'] = '-' . $v['money'];
            }
            $tmpArr['after_price'] = $v['after_price'] > 0 ? strval($v['after_price']) : '0';
            if($isAihorseCustomized){
                $tmpArr['off_pay_type_str']='';
                if($v['off_pay_type']>0 && isset($off_pay_type_arr[$v['off_pay_type']])){
                    $tmpArr['off_pay_type_str']=$off_pay_type_arr[$v['off_pay_type']];
                }
            }
            $tmpArr['desc'] = $v['desc'] ? $v['desc'] : '';
            $datas[] = $tmpArr;
        }
        unset($list);
        if($uid==0){
            $titleArr = ['姓名','手机号','关联房间','订单编号', '预存类型', '预存时间', '金额变更前（元）', '缴费金额（元）', '金额变更后（元）', '备注'];
        }else{
            $titleArr = ['姓名','手机号','订单编号', '预存类型', '预存时间', '金额变更前（元）', '缴费金额（元）', '金额变更后（元）', '备注'];
        }
        if($isAihorseCustomized){
            if($uid==0){
                $titleArr = ['姓名','手机号','关联房间','订单编号', '预存类型', '预存时间', '金额变更前（元）', '缴费金额（元）', '金额变更后（元）', '线下支付方式','备注'];
            }else{
                $titleArr = ['姓名','手机号','订单编号', '预存类型', '预存时间', '金额变更前（元）', '缴费金额（元）', '金额变更后（元）', '线下支付方式', '备注'];
            }
        }
        $xtype = 'excelExportBalanceRecord';
        $filename = '余额记录导出' . date('YmdHis');
        $cellTips = '';
        $exportDatas = array('list' => $datas);
        return $this->saveExportExcel($titleArr, $exportDatas, $xtype, $filename, $cellTips);
    }

    public function exportUserBalanceRecord($data = array())
    {
        if (empty($data) || !isset($data['village_id']) || $data['village_id'] < 1) {
            throw new \think\Exception("暂无数据导出");
        }
        $db_village_user_money_list = new VillageUserMoneyList();
        $db_house_village_user_bind = new HouseVillageUserBind();
        $db_house_village_bind_position = new HouseVillageBindPosition();
        $where = array();
        $where[] = ['v.business_id', '=', $data['village_id']];
        $where[] = ['v.uid', '>', 0];
        $where[] = ['v.business_type', '=', 1];
        if (!empty($data['name'])) {
            $where[] = ['u.nickname', 'like', '%' . $data['name'] . '%'];
        }
        if (!empty($data['phone'])) {
            $where[] = ['u.phone', 'like', '%' . $data['phone'] . '%'];
        }
        if (!empty($data['uid'])) {
            $where[] = ['v.uid', 'in', $data['uid']];
        }
        $field = 'v.*,u.nickname,u.phone';
        $order = 'v.id desc';
        $list = $db_village_user_money_list->getUserList($where, $field, $order, 0, 0);
        if (!empty($list)) {
            $list = $list->toArray();
        }
        if (empty($list)) {
            throw new \think\Exception("暂无数据导出！");
        }
        $dataTmp = array();
        $customized_meter_reading=cfg('customized_meter_reading');
        foreach ($list as $v) {
            $tmpArr = array();
            $tmpArr['nickname'] = !empty($v['nickname']) ? $v['nickname'] : '';
            $tmpArr['phone'] = !empty($v['phone']) ? $v['phone'] : '';
            if($customized_meter_reading){
                $tmpArr['cold_water_balance'] = !empty($v['cold_water_balance']) ? strval($v['cold_water_balance']) : '';
                $tmpArr['hot_water_balance'] = !empty($v['hot_water_balance']) ? strval($v['hot_water_balance']) : '';
                $tmpArr['electric_balance'] = !empty($v['electric_balance']) ? strval($v['electric_balance']) : '';
            }
            $tmpArr['current_money'] = !empty($v['current_money']) ? strval($v['current_money']) : '';
            $user_bind_where = [
                'village_id' => $data['village_id'],
                'uid' => $v['uid'],
                'type' => [0, 1, 2, 3],
                'status' => 1
            ];
            
            $where=[
                'h.uid'=>$v['uid'],
                'h.village_id'=>$data['village_id'],
                'h.type'=>[0,1,2,3],
                'h.status'=>1,
            ];
            $field='a.room,b.single_name,c.floor_name,l.layer_name,h.pigcms_id as bind_id, h.name as bind_name,h.phone as bind_phone';
            $homelist=$db_house_village_user_bind->getuserList($where,$field,0,100);
            $tmpArr['room_num']='';
            $homeRoom=array();
            if($homelist && !$homelist->isEmpty()){
                $homelist=$homelist->toArray();
                foreach ($homelist as $vv){
                    if(is_numeric($vv['single_name'])){
                        $vv['single_name']=$vv['single_name'].(cfg('single_name')?:'栋');
                    }
                    if(is_numeric($vv['floor_name'])){
                        $vv['floor_name']=$vv['floor_name'].(cfg('floor_name')?:'单元');
                    }
                    if(is_numeric($vv['layer_name'])){
                        $vv['layer_name']=$vv['layer_name'].'层';
                    }
                    $address=$this->HouseVillageService->word_replce_msg(array('single_name'=>$vv['single_name'],'floor_name'=>$vv['floor_name'],'layer'=>$vv['layer_name'],'room'=>$vv['room']),$data['village_id']);
                    $homeRoom[]=$address;
                }
            }
            if(!empty($homeRoom)){
                $tmpArr['room_num']=implode('；',$homeRoom);
            }
            $tmpArr['position_num']='';
            $pigcms_id_arr = $db_house_village_user_bind->getUserColumn($user_bind_where, 'pigcms_id');
            if(!empty($pigcms_id_arr)){
                $where=[
                    'b.user_id'=>$pigcms_id_arr,
                    'b.village_id'=>$data['village_id'],
                ];
                $field='p.position_id,p.garage_id,p.position_num,g.garage_num';
                $positionlist=$db_house_village_bind_position->getUserPositionList($where,$field,0,100);
                $positionArr=array();
                if($positionlist && !$positionlist->isEmpty()){
                    $positionlist=$positionlist->toArray();
                    foreach ($positionlist as $pvv){
                        $paddress=$pvv['garage_num'].' '.$pvv['position_num'];
                        $positionArr[]=$paddress;
                    }
                }
                if(!empty($positionArr)){
                    $tmpArr['position_num']=implode('；',$positionArr);
                }
            }
            $dataTmp[] = $tmpArr;
        }

        unset($list);
        if($customized_meter_reading) {
            $titleArr = ['姓名', '手机号', '冷水余额', '热水余额', '电费余额', '物业余额', '关联房间', '关联车位'];
        }else{
            $titleArr = ['姓名', '手机号', '物业余额', '关联房间', '关联车位'];
        }
        $xtype = 'exportUserBalanceRecord';
        $filename = '用户余额导出' . date('YmdHis');
        $cellTips = '用户余额明细';
        $exportDatas = array('list' => $dataTmp);
        return $this->saveExportExcel($titleArr, $exportDatas, $xtype, $filename, $cellTips);
    }

    /**
     * 导出文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 14:44
     */
    public function saveExportExcel($titleArr, $exportDatas, $xtype = '', $fileName = '', $cellTips = '')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(24);
        $sheet->getDefaultRowDimension()->setRowHeight(24);//设置行高
        $sheet->getRowDimension('1')->setRowHeight(26);//设置行高
        $startRow = 1;
        if ($xtype == 'excelExportBalanceRecord' && !empty($cellTips)) {
            $startRow = 2;
            $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(12);
            $sheet->mergeCells('A1:G1'); //合并单元格
            $sheet->setCellValue('A1', $cellTips);
        }
        if($xtype == 'excelExportBalanceRecord'){
            $sheet->getColumnDimension('C')->setWidth(60);
        }
        if ($xtype == 'exportUserBalanceRecord' && !empty($cellTips)) {
            $startRow = 2;
        }
        //设置单元格内容
        $titCol = 'A';
        $lastCol='';
        foreach ($titleArr as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . $startRow, $value);
            $lastCol=$titCol;
            $titCol++;
        }
        if($xtype == 'excelExportBalanceRecord' && $lastCol){
            $sheet->getColumnDimension($lastCol)->setWidth(100);
        }
        if ($xtype == 'exportUserBalanceRecord' && !empty($cellTips)) {
            $sheet->getColumnDimension($lastCol)->setWidth(60);
            $sheet->getStyle('A1:'.$lastCol.'1')->getFont()->setBold(true)->setSize(12);
            $sheet->mergeCells('A1:'.$lastCol.'1'); //合并单元格
            $sheet->setCellValue('A1', $cellTips);
            $lastColNum=ord($lastCol);
            $lastColNum=$lastColNum-1;
            $lastCol= chr ($lastColNum);
            $sheet->getColumnDimension($lastCol)->setWidth(90);
        }
        //设置单元格内容
        $row = $startRow + 1;
        foreach ($exportDatas['list'] as $k => $item) {

            $dataCol = 'A';
            foreach ($item as $value) {
                //单元格内容写入
                $sheet->setCellValue($dataCol . $row, $value);
                $dataCol++;
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
        if ($xtype == 'excelExportBalanceRecord') {
            $sheet->getStyle('A1:AG' . $total_rows)->applyFromArray($styleArrayBody);
            //   $sheet->getStyle('A2:A'. $total_rows)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        } else if ($xtype == 'exportUserBalanceRecord') {
            $sheet->getStyle('A1:AH' . $total_rows)->applyFromArray($styleArrayBody);
            /*
            $styleArray2Body = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
            ];
            $sheet->getStyle('G1:GH' . $total_rows)->applyFromArray($styleArray2Body);
            */
        } else {
            $sheet->getStyle('A1:AM' . $total_rows)->applyFromArray($styleArrayBody);
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
    public function setMeterExtendedData($data=array(),$village_id=0,$xtype='balance'){
        $whereArr=array('village_id'=>$village_id);
        $villageInfoExtend=$this->HouseVillageService->getHouseVillageInfoExtend($whereArr);
        $meter_extended_data=array();
        $meter_extended_data_tmp=array();
        if($villageInfoExtend && !empty($villageInfoExtend['meter_extended_data'])){
            $meter_extended_data_tmp=json_decode($villageInfoExtend['meter_extended_data'],true);
            if($meter_extended_data_tmp && is_array($meter_extended_data_tmp)){
                $meter_extended_data=$meter_extended_data_tmp;
            }
        }
        if(isset($data['cold_water_balance']) && $xtype=='balance'){
            $meter_extended_data['cold_water_balance']=$data['cold_water_balance'];
        }
        if(isset($data['hot_water_balance']) && $xtype=='balance'){
            $meter_extended_data['hot_water_balance']=$data['hot_water_balance'];
        }
        if(isset($data['electric_balance']) && $xtype=='balance'){
            $meter_extended_data['electric_balance']=$data['electric_balance'];
        }
        if(isset($data['cold_water_prestore']) && $xtype=='prestore'){
            $meter_extended_data['cold_water_prestore']=$data['cold_water_prestore'];
        }
        if(isset($data['electric_prestore'])&&  $xtype=='prestore'){
            $meter_extended_data['electric_prestore']=$data['electric_prestore'];
        }
        if(isset($data['gas_prestore'])&&  $xtype=='prestore'){
            $meter_extended_data['gas_prestore']=$data['gas_prestore'];
        }
        if(isset($data['hot_water_prestore'])&&  $xtype=='prestore'){
            $meter_extended_data['hot_water_prestore']=$data['hot_water_prestore'];
        }
        if(isset($data['property_fee_pay_first'])&&  $xtype=='prestore'){
            $meter_extended_data['property_fee_pay_first']=$data['property_fee_pay_first'];
        }
        if($villageInfoExtend && $this->customized_meter_reading>0 && !empty($meter_extended_data)){
            $saveArr=array();
            $saveArr['meter_extended_data']=json_encode($meter_extended_data,JSON_UNESCAPED_UNICODE);
            $rets=$this->HouseVillageService->saveHouseVillageInfoExtend($whereArr,$saveArr);
            $tbname='住户余额-预存设置';
            if($xtype=='balance'){
                $tbname='住户余额-住户余额最低限额';
            }
            $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
            $queuData = [
                'logData' => [
                    'tbname' => $tbname,
                    'table'  => 'house_village_info:meter_extended_data',
                    'client' => '小区后台',
                    'trigger_path' => '财务管理->住户余额',
                    'trigger_type' => $this->getUpdateNmae(),
                    'addtime'      => time(),
                    'village_id'   => $village_id,
                    'property_id' =>0,
                    'op_id'   => $parseInfo['op_id'],
                    'op_type'   => $parseInfo['op_type'],
                    'op_name'   => $parseInfo['op_name'],
                ],
                'newData' => $meter_extended_data,
                'oldData' => $meter_extended_data_tmp
            ];
            $this->laterLogInQueue($queuData);
            
            return array('ret'=>$rets);
        }
        return array('ret'=>0);
    }
    public function getMeterExtendedData($village_id=0){
        $whereArr=array('village_id'=>$village_id);
        $villageInfoExtend=$this->HouseVillageService->getHouseVillageInfoExtend($whereArr,'meter_extended_data');
        $meterExtendedData=array('cold_water_balance'=>array('is_open'=>0,'balance'=>''),'hot_water_balance'=>array('is_open'=>0,'balance'=>''),'electric_balance'=>array('is_open'=>0,'balance'=>''));
        $meterExtendedData[]=array('cold_water_prestore'=>array('is_open'=>0,'prestore_set'=>array()));
        $meterExtendedData[]=array('hot_water_prestore'=>array('is_open'=>0,'prestore_set'=>array()));
        $meterExtendedData[]=array('electric_prestore'=>array('is_open'=>0,'prestore_set'=>array()));
        $meterExtendedData[]=array('gas_prestore'=>array('is_open'=>0,'prestore_set'=>array()));
        $meterExtendedData[]=array('property_fee_pay_first'=>array('is_open'=>0));
        if($villageInfoExtend && !empty($villageInfoExtend['meter_extended_data'])){
            $meterExtendedData=json_decode($villageInfoExtend['meter_extended_data'],true);
            if(!isset($meterExtendedData['cold_water_balance']) || empty($meterExtendedData['cold_water_balance'])){
                $meterExtendedData['cold_water_balance']=array('is_open'=>0,'balance'=>'');
            }
            if(!isset($meterExtendedData['hot_water_balance']) || empty($meterExtendedData['hot_water_balance'])){
                $meterExtendedData['hot_water_balance']=array('is_open'=>0,'balance'=>'');
            }
            if(!isset($meterExtendedData['electric_balance']) || empty($meterExtendedData['electric_balance'])){
                $meterExtendedData['electric_balance']=array('is_open'=>0,'balance'=>'');
            }
            if(!isset($meterExtendedData['cold_water_prestore']) || empty($meterExtendedData['cold_water_prestore'])){
                $meterExtendedData['cold_water_prestore']=array('is_open'=>0,'prestore_set'=>array());
            }
            if(!isset($meterExtendedData['hot_water_prestore']) || empty($meterExtendedData['hot_water_prestore'])){
                $meterExtendedData['hot_water_prestore']=array('is_open'=>0,'prestore_set'=>array());
            }
            if(!isset($meterExtendedData['electric_prestore']) || empty($meterExtendedData['electric_prestore'])){
                $meterExtendedData['electric_prestore']=array('is_open'=>0,'prestore_set'=>array());
            }
            if(!isset($meterExtendedData['gas_prestore']) || empty($meterExtendedData['gas_prestore'])){
                $meterExtendedData['gas_prestore']=array('is_open'=>0,'prestore_set'=>array());
            }
            if(!isset($meterExtendedData['property_fee_pay_first']) || empty($meterExtendedData['property_fee_pay_first'])){
                $meterExtendedData['property_fee_pay_first']=array('is_open'=>0);
            }
        }
        return $meterExtendedData;
    }

    public function checkUserOrderPaymentStatus($village_id=0,$parameter=array()){
        $returnArr=array('is_need_popup_tips'=>0,'msg_1tips'=>'由于您未缴物业费，手机终端充值缴费暂不能使用，','msg_2tips'=>'请及时联系物业工作人员','phone'=>'');
        if($village_id<1 || !isset($parameter['type']) || !isset($parameter['pigcms_id'])){
            return $returnArr;
        }
        $type=trim($parameter['type']);
        $pigcms_id=trim($parameter['pigcms_id']);
        $pigcms_id=$pigcms_id ? intval($pigcms_id):0;
        if($pigcms_id<1){
            return $returnArr;
        }
        $whereArr=array('village_id'=>$village_id);
        $villageInfoExtend=$this->HouseVillageService->getHouseVillageInfoExtend($whereArr,'meter_extended_data');
        if($villageInfoExtend && !empty($villageInfoExtend['meter_extended_data'])){
            $meterExtendedData=json_decode($villageInfoExtend['meter_extended_data'],1);
            if(empty($meterExtendedData)){
                return $returnArr;
            }
            $tmp_prestore=array();
            if($type=='water' && isset($meterExtendedData['cold_water_prestore']) && !empty($meterExtendedData['cold_water_prestore'])){
                $tmp_prestore=$meterExtendedData['cold_water_prestore'];
            }else if($type=='hotwater' && isset($meterExtendedData['hot_water_prestore']) && !empty($meterExtendedData['hot_water_prestore'])){
                $tmp_prestore=$meterExtendedData['hot_water_prestore'];
            }else if($type=='electric' && isset($meterExtendedData['electric_prestore']) && !empty($meterExtendedData['electric_prestore'])){
                $tmp_prestore=$meterExtendedData['electric_prestore'];
            }else if($type=='villagebalance' && isset($meterExtendedData['gas_prestore']) && !empty($meterExtendedData['gas_prestore'])){
                $tmp_prestore=$meterExtendedData['gas_prestore'];
            }
            if(empty($tmp_prestore) || !isset($tmp_prestore['is_open']) || $tmp_prestore['is_open']<1 ||  !isset($tmp_prestore['prestore_set'])  || empty($tmp_prestore['prestore_set'])){
                return $returnArr;
            }
            $vacancy_id=0;
            if($pigcms_id>1){
                $whereArr=array('pigcms_id'=>$pigcms_id,'village_id'=>$village_id);
                $bind_info = $this->HouseVillageUserBind->getOne($whereArr,'vacancy_id,village_id,uid');
                if (!empty($bind_info) && !$bind_info->isEmpty()){
                    $vacancy_id=$bind_info['vacancy_id'];
                }
            }
            if($vacancy_id<1){
                return $returnArr;
            }
            $whereOrder=array();
            $whereOrder[] = ['village_id', '=', $village_id];
            $whereOrder[] = ['room_id', '=', $vacancy_id];
            $whereOrder[] = ['order_type', 'in', $tmp_prestore['prestore_set']];
            $whereOrder[] = ['is_discard', '=', 1];
            $whereOrder[] = ['is_paid', '=', 2];
            $orderInfo = $this->HouseNewPayOrder->get_one($whereOrder);
            if($orderInfo && !$orderInfo->isEmpty()){
                $returnArr['is_need_popup_tips']=1;
                $whereArr=array('village_id'=>$village_id);
                $villageInfo=$this->HouseVillageService->getHouseVillageInfo($whereArr,'property_phone');
                if($villageInfo && !$villageInfo->isEmpty()){
                    $returnArr['phone']=$villageInfo['property_phone'];
                    $returnArr['phone']=$returnArr['phone'] ? trim($returnArr['phone']):'';
                }
            }
            return $returnArr;
        }
        return $returnArr;
    }
    
    /**
     * 统计小区的小区住户余额金额
     */
    public function getVillageUserMoney($where,$type=0)
    {
        if($type){//统计小区住户余额总额
            $db_village_user_money_list = new VillageUserMoneyList();
            $where['business_type'] = 1;
            $money = $db_village_user_money_list->getOne($where,'SUM(current_money) as now_money');
            $money = isset($money['now_money']) && $money['now_money'] ? $money['now_money'] : 0;
        }else{
            //统计小区住户预存金额或者抵扣金额
            $db_village_user_money_log=new VillageUserMoneyLog();
            $money = $db_village_user_money_log->getValues($where,'SUM(money)');
        }
        return $money;
    }

    public function getPrintInfo($village_id = 0, $idd = 0, $uid = 0)
    {
        $db_village_user_money_log = new VillageUserMoneyLog();
        $whereArr = array(['id', '=', $idd]);
        if ($village_id) {
            $whereArr[] = array('business_id', '=', $village_id);
        }
        if ($uid) {
            $whereArr[] = array('uid', '=', $uid);
        }
        $userMoneyLogObj = $db_village_user_money_log->getOne($whereArr, '*');
        $userMoneyLog = array();
        if ($userMoneyLogObj && !$userMoneyLogObj->isEmpty()) {
            $userMoneyLog = $userMoneyLogObj->toArray();
        }
        $pay_type_arr = array('weixin' => '微信支付', 'alipay' => '支付宝支付');
        if ($userMoneyLog) {
            $houseVillageDb = new HouseVillage();
            $houseVillageObj = $houseVillageDb->getInfo(array('village_id' => $village_id), 'village_id,village_name,property_id,village_code,seal_img');
            $userMoneyLog['village_name'] = '';
            $userMoneyLog['seal_img']='';
            $village_code = '';
            $off_pay_type_arr = array();
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $userMoneyLog['village_name'] = $houseVillageObj['village_name'];
                $userMoneyLog['seal_img']= $houseVillageObj['seal_img'] ? replace_file_domain( $houseVillageObj['seal_img']):'';
                $village_code = $houseVillageObj['village_code'];
                $db_house_new_offline = new HouseNewOfflinePay();
                $whereOffline = array();
                $whereOffline['property_id'] = $houseVillageObj['property_id'];
                $whereOffline['status'] = 1;
                $offlineObj = $db_house_new_offline->getList($whereOffline, 'id,name');
                if ($offlineObj && !$offlineObj->isEmpty()) {
                    foreach ($offlineObj as $ovv) {
                        $off_pay_type_arr[$ovv['id']] = $ovv['name'];
                    }
                }
            }
            $userMoneyLog['print_time_str'] = date('Y-m-d H:i:s');
            $userMoneyLog['add_time_str'] = date('Y-m-d H:i:s', $userMoneyLog['add_time']);
            $userMoneyLog['pay_type_str'] = '';
            $userMoneyLog['opt_name'] = '';
            if ($userMoneyLog['pay_type']) {
                $userMoneyLog['pay_type_str'] = isset($pay_type_arr[$userMoneyLog['pay_type']]) ? $pay_type_arr[$userMoneyLog['pay_type']] : $userMoneyLog['pay_type'];
                $userMoneyLog['opt_name'] = '用户自主支付';
            } else if ($userMoneyLog['off_pay_type'] && isset($off_pay_type_arr[$userMoneyLog['off_pay_type']])) {
                $userMoneyLog['pay_type_str'] = $off_pay_type_arr[$userMoneyLog['off_pay_type']];
            }

            $user_bind_where = [
                'village_id' => $village_id,
                'uid' => $userMoneyLog['uid'],
                'type' => [0, 1, 2, 3],
                'status' => 1
            ];
            $userMoneyLog['room_address'] = '';
            $userMoneyLog['user_name'] = '';
            $userMoneyLog['phone'] = '';
            $userObj = $this->User->getOne(['uid' => $userMoneyLog['uid']], 'nickname,phone');
            if ($userObj && !$userObj->isEmpty()) {
                $userMoneyLog['user_name'] = $userObj['nickname'];
                $userMoneyLog['phone'] = $userObj['phone'];
            }
            $db_house_village_user_bind = new HouseVillageUserBind();
            $houseVillageService = new HouseVillageService();
            $user_bind_obj = $db_house_village_user_bind->getOne($user_bind_where, '*');
            if ($user_bind_obj && !$user_bind_obj->isEmpty()) {
                $userMoneyLog['user_name'] = $user_bind_obj['name'];
                $address = $houseVillageService->getSingleFloorRoom($user_bind_obj['single_id'], $user_bind_obj['floor_id'], $user_bind_obj['layer_id'], $user_bind_obj['vacancy_id'], $user_bind_obj['village_id']);
                $userMoneyLog['room_address'] = $address;
            }
            $extra_data = $userMoneyLog['extra_data'] ? json_decode($userMoneyLog['extra_data'], true) : array();
            unset($userMoneyLog['extra_data']);
            if ($extra_data && $extra_data['opt_name']) {
                $userMoneyLog['opt_name'] = $extra_data['opt_name'];
            }
            $userMoneyLog['print_num'] = $village_code . date('Ymd') .'00'. $idd;
            $configCustomizationService = new ConfigCustomizationService();
            $is_aihorse_customized = $configCustomizationService->getAihorseCustomizedJudge();
            if ($is_aihorse_customized) {
                $userMoneyLog['print_num'] = $village_code . date('Ymd').'00'. $idd;  //定制的
            }
            return $userMoneyLog;
        }
        return array();
    }
}