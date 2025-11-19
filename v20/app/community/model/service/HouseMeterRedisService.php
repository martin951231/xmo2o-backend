<?php
/**
 * Created by PhpStorm.
 * User: zhubaodi
 * Date Time: 2021/4/8 13:33
 */

namespace app\community\model\service;


use app\common\model\service\send_message\SmsService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseMeterAdminElectric;
use app\community\model\db\HouseMeterAdminVillage;
use app\community\model\db\HouseMeterAdminUser;
use app\community\model\db\HouseMeterUserPayorder;
use app\community\model\db\HouseMeterElectricGroup;
use app\community\model\db\HouseMeterElectricPrice;
use app\community\model\db\HouseMeterElectricRealtime;
use app\community\model\db\HouseMeterReadingSys;
use app\community\model\db\HouseMeterWarn;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\Area;
use app\community\model\db\AreaStreet;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\User;
use app\foodshop\model\service\message\SmsSendService;
use app\Redis;
use token\Token;
use think\facade\Request;

class HouseMeterRedisService
{

    public function __construct()
    {
        //初始化连接redis
        /*$data['host'] = '127.0.0.1';
        $data['port'] = 6379;
        $data['password'] = 'Vu8kwNIe2DSFtQzk';
        $this->redis = new Redis($data);*/

        $data['host'] = 'r-2zekp5rs03nxj2goba.redis.rds.aliyuncs.com';
        $data['port'] = 6379;
        $data['password'] = 'Wwby870168';
        $this->redis = new Redis($data);
    }

    /**
     * 下发开关闸指令
     * @author:zhubaodi
     * @date_time: 2021/4/20 11:59
     */
    public function download_switch($electric_id, $switch_type)
    {

        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_info = $house_meter_electric->getInfo(['id' => $electric_id]);
        if (!$electric_info) {
            throw new \think\Exception("该电表不存在！");
        }
        if ($electric_info['status'] == 1) {
            throw new \think\Exception("该电表状态异常不可以操作！");
        }
        if ($electric_info['disabled'] == 'true') {
            throw new \think\Exception("该电表当前不允许开关闸操作，请稍后再试！");
        }
        $house_meter_group = new HouseMeterElectricGroup();
        $group_info = $house_meter_group->getInfo(['id' => $electric_info['group_id']]);
        if (!$group_info) {
            throw new \think\Exception("该电表分组不存在,请先绑定分组！");
        }
        if ($group_info['status'] != 1) {
            throw new \think\Exception("该电表分组状态异常不可以操作！");
        }

        $data = [
            'cmd' => 'switch',
            'logical_address' => $group_info['tcp_address'],
            'type' => $switch_type,
            'energy_meter_address' => $electric_info['electric_address'],
            'measuring_point' => $electric_info['measure_id'],
            'electric_type' => $electric_info['electric_type'],
        ];
        $key = 'download_' . $group_info['tcp_address'];
        $value = json_encode($data);
        $res = $this->redis->rPush($key, $value);
        if (is_numeric($res)) {
            $house_meter_electric->saveOne(['id' => $electric_id], ['disabled' => 'true']);
        }
        return $res;
    }


    /**
     * 下发绑定指令
     * @author:zhubaodi
     * @date_time: 2021/4/20 11:59
     */
    public function download_bang($electric_id)
    {
        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_info = $house_meter_electric->getInfo(['id' => $electric_id]);
        if (!$electric_info) {
            throw new \think\Exception("该电表不存在！");
        }
        $house_meter_group = new HouseMeterElectricGroup();
        $group_info = $house_meter_group->getInfo(['id' => $electric_info['group_id']]);
        if (!$group_info) {
            throw new \think\Exception("该电表分组不存在,请先绑定分组！");
        }
        if ($group_info['status'] != 1) {
            throw new \think\Exception("该电表分组状态异常不可以操作！");
        }
        $data = [
            'cmd' => 'bang',
            'logical_address' => $group_info['tcp_address'],
            'energy_meter_address' => $electric_info['electric_address'],
            'measuring_point' => $electric_info['measure_id'],
        ];
        $key = 'download_' . $group_info['tcp_address'];
        $value = json_encode($data);
        return $this->redis->rPush($key, $value);

    }

    /**
     * 下发日抄表指令
     * @author:zhubaodi
     * @date_time: 2021/4/20 11:59
     */
    public function download_meter_reading($id, $tcp_address)
    {
        if (strlen($tcp_address) == 10) {
            $readingInfo = $this->redis->Hgetall($tcp_address);
            if (!empty($readingInfo)) {
                $time = time() - 600;
                if ($readingInfo['last_heart_time'] > $time) {

                    $house_meter_electric = new HouseMeterAdminElectric();
                    $where = [
                        'group_id' => $id,
                        'status' => 0
                    ];
                    $electric_list = $house_meter_electric->getListAll($where);

                    $sdata = [];
                    if (!empty($electric_list)) {
                        $electric_list = $electric_list->toArray();
                        foreach ($electric_list as $value) {
                            if ($value['group_id'] == $id) {

                                $data = [
                                    'logical_address' => $tcp_address,
                                    'energy_meter_address' => $value['electric_address'],
                                    'measuring_point' => $value['measure_id'],
                                    'type' => 'day_reading',
                                    'date' => $date = ['Y' => date("y"), 'M' => date("m"), 'D' => date("d", strtotime("-1 day"))],
                                ];
                                $sdata[] = $data;
                            }

                        }
                    }

                    $reading = '';

                    if (!empty($sdata)) {
                        $key = 'download_' . $tcp_address;
                        $valuearr = [
                            'cmd' => 'meter_reading',
                            'data' => $sdata,
                        ];
                        $value = json_encode($valuearr);
                        fdump_api(['下发抄表指令'.__LINE__,$valuearr],'houseMeter/downloadlog',1);
                        $reading = $this->redis->rPush($key, $value);
                    }

                }
            }
        }
        return $reading;
    }

    /**
     * 下发多次日抄表指令
     * @author:zhubaodi
     * @date_time: 2021/4/20 11:59
     */
    public function download_meter_reading_repeat($id, $tcp_address)
    {
        if (strlen($tcp_address) == 10) {
            $readingInfo = $this->redis->Hgetall($tcp_address);
            if (!empty($readingInfo)) {
                $time = time() - 600;
                if ($readingInfo['last_heart_time'] > $time) {

                    $house_meter_electric = new HouseMeterAdminElectric();
                    $house_electric_readind = new HouseMeterElectricRealtime();
                   /* $where = [
                        'group_id' => $id,
                        'status' => 0
                    ];*/
                    $where=[];
                    $where[]=['a.group_id','=',$id];
                    $where[]=['a.status','=',0];
                    $where[]=['b.add_time','>',strtotime(date('Y-m-d 00:00:00'))];
                    $electric_list = $house_meter_electric->getListReal($where,'a.*,b.add_time as add_realtime');
                   //  $electric_list = $house_meter_electric->getListAll($where);
                    $sdata = [];
                    if (!empty($electric_list)) {
                        $electric_list = $electric_list->toArray();
                        foreach ($electric_list as $value) {
                           /* $where_real=[];
                            $where_real[]=['electric_id','=',$value['id']];
                            $where_real[]=['add_time','>',strtotime(date('Y-m-d 00:00:00'))];
                            $realInfo=$house_electric_readind->getInfo($where_real);*/
                           // if (empty($realInfo)&&$value['group_id'] == $id) {
                            if (empty($value['add_realtime'])&&$value['group_id'] == $id) {
                                $data = [
                                    'logical_address' => $tcp_address,
                                    'energy_meter_address' => $value['electric_address'],
                                    'measuring_point' => $value['measure_id'],
                                    'type' => 'day_reading',
                                    'date' => $date = ['Y' => date("y"), 'M' => date("m"), 'D' => date("d", strtotime("-1 day"))],
                                ];
                                $sdata[] = $data;
                            }

                        }
                    }

                    $reading = '';

                    if (!empty($sdata)) {
                        $key = 'download_' . $tcp_address;
                        $valuearr = [
                            'cmd' => 'meter_reading',
                            'data' => $sdata,
                        ];
                        $value = json_encode($valuearr);
                        fdump_api(['下发抄表指令'.__LINE__,$valuearr],'houseMeter/downloadlog',1);
                        $reading = $this->redis->rPush($key, $value);
                    }

                }
            }
        }
        return $reading;
    }


    /**
     * 获取upload数据
     * @author:zhubaodi
     * @date_time: 2021/4/20 11:59
     */
    public function upload_data()
    {
        $house_meter_electric = new HouseMeterAdminElectric();
        $house_meter_warn = new HouseMeterWarn();
        $house_meter_group = new HouseMeterElectricGroup();
        $electric_realtime = new HouseMeterElectricRealtime();
        $electric_sys = new HouseMeterReadingSys();
        $service_vacancy = new HouseVillageUserVacancy();
        $service_village = new HouseVillage();
        $service_price = new HouseMeterElectricPrice();
        $service_order = new HouseMeterUserPayorder();
        $templateNewsService = new TemplateNewsService();

        $key = 'upload';
        $reading_arr = $this->redis->lRange($key, 0, -1);

        if (!empty($reading_arr)) {
            $len=count($reading_arr);
            for ($i = 0; $i < $len; $i++) {
                fdump_api(['下发抄表指令'.__LINE__,$reading_arr[$i]],'houseMeter/uploadlog',1);
                $reading = json_decode($reading_arr[$i], true);
                if ($reading['cmd'] == 'switch') {
                    $electric_info = $house_meter_electric->getInfo(['electric_address' => $reading['energy_meter_address']]);
                    if ($reading['status'] == true || $reading['status'] == 1) {
                        if ($electric_info['swicth'] == 'open') {
                            $res = $house_meter_electric->saveOne(['electric_address' => $reading['energy_meter_address']], ['swicth' => 'close', 'disabled' => 'false']);
                        } else {
                            $res = $house_meter_electric->saveOne(['electric_address' => $reading['energy_meter_address']], ['swicth' => 'open', 'disabled' => 'false']);
                        }
                    } else {
                        $res = $house_meter_electric->saveOne(['electric_address' => $reading['energy_meter_address']], ['disabled' => 'false']);
                    }
                    if ($res) {
                        $this->redis->LREM($key, 1, $reading_arr[$i]);
                    }
                }
                if ($reading['cmd'] == 'meter_reading') {
                    $group = $house_meter_group->getInfo(['tcp_address' => $reading['logical_address']]);

                    if (!empty($group)) {
                        $electric = $house_meter_electric->getInfo(['group_id' => $group['id'], 'measure_id' => $reading['measuring_point']]);

                        if (!empty($electric)) {
                            $uid = $service_vacancy->getOne(['pigcms_id' => $electric['vacancy_id']]);
                            $date11 = '20' . $reading['date']['Y'] . '-' . $reading['date']['M'] . '-' . $reading['date']['D'] . '  ' . $reading['date']['H'] . ':' . $reading['date']['I'];
                            $date = strtotime($date11);
                           // $res = $house_meter_electric->saveOne(['id' => $electric['id']], ['reading_time' => $date]);
                            $realtime_last_info = $electric_realtime->where(['electric_id' => $electric['id']])->order('id DESC')->find();
                            if (empty($realtime_last_info)) {
                                $realtime_last_info['end_num'] = 0;
                            }
                            if (!empty($realtime_last_info)&&$realtime_last_info['add_time']>strtotime(date('Y-m-d 00:00:00'))){
                                $this->redis->LREM($key, 1, $reading_arr[$i]);
                            }else{
                                $data1 = [
                                    'uid' => $uid['uid'],
                                    'province_id' => $electric['province_id'],
                                    'city_id' => $electric['city_id'],
                                    'area_id' => $electric['area_id'],
                                    'street_id' => $electric['street_id'],
                                    'community_id' => $electric['community_id'],
                                    'village_id' => $electric['village_id'],
                                    'single_id' => $electric['single_id'],
                                    'floor_id' => $electric['floor_id'],
                                    'layer_id' => $electric['layer_id'],
                                    'vacancy_id' => $electric['vacancy_id'],
                                    'meter_reading_num' => 0,
                                    'begin_num' => $realtime_last_info['end_num'],
                                    'end_num' => $reading['degrees'],
                                    'status' => 0,
                                    'add_time' => time(),
                                    'electric_id' => $electric['id'],
                                    'reading_time' => $date
                                ];
                                $realtime = $electric_realtime->insertOne($data1);
                                if ($realtime) {
                                    $this->redis->LREM($key, 1, $reading_arr[$i]);
                                }
                            }

                        }
                    }

                }
                if ($reading['cmd'] == 'except') {
                    $group = $house_meter_group->getInfo(['tcp_address' => $reading['logical_address']]);
                    if ($group) {
                        $date11 = '20' . $reading['date']['Y'] . '-' . $reading['date']['M'] . '-' . $reading['date']['D'] . '  ' . $reading['date']['H'] . ':' . $reading['date']['I'];
                        $date = strtotime($date11);
                        $house_meter_group->saveOne(['tcp_address' => $reading['logical_address']],['status1'=>1,'last_notice_time'=>$date]);
                        $electric_info = $house_meter_electric->getListAll(['group_id' => $group['id']]);
                        if (!empty($electric_info)) {
                            $electric_info = $electric_info->toArray();
                            if (!empty($electric_info)) {
                                foreach ($electric_info as $value) {
                                    $uid = $service_vacancy->getOne(['pigcms_id' => $value['vacancy_id']]);
                                    $village_info = $service_village->getOne($value['village_id']);
                                    $data_warn=[
                                        'electric_id'=>$value['id'],
                                        'group_id'=>$group['id'],
                                        'electric_name'=>$value['electric_name'],
                                        'village_id'=>$value['village_id'],
                                        'city_id'=>$value['city_id'],
                                        'village_name'=>$village_info['village_name'],
                                        'warn_reason'=>'所属集中器已离线',
                                        'warn_time'=>time(),
                                    ];
                                    $house_meter_warn->insertOne($data_warn);
                                    if ($uid['uid']) {
                                        $user = new User();
                                        $openid = $user->getOne(['uid' => $uid['uid']]);
                                        if (!empty($openid)) {
                                            $datamsg = [
                                                'tempKey' => 'OPENTM400166399',
                                                'dataArr' => [
                                                    'wecha_id' => $openid['openid'],
                                                    'first' => '您好，您所在的' . $electric['village_address'] . '电表处于断网状态，请及时查看！',
                                                    'keyword1' => '电表状态提醒',
                                                    'keyword2' => '已发送',
                                                    'keyword3' => date('H:i'),
                                                ]
                                            ];
                                            //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                        }
                                    }
                                }
                            }
                        }

                    }

                    $this->redis->LREM($key, 1, $reading_arr[$i]);

                }
            }

        }
        $this->meter_reading();
        return true;

    }



    /**
     * 获取抄表数据
     * @author:zhubaodi
     * @date_time: 2021/4/21 13:50
     */
    public function meter_reading()
    {
        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_sys = new HouseMeterReadingSys();
        $electric_realtime = new HouseMeterElectricRealtime();
        $service_vacancy = new HouseVillageUserVacancy();
        $service_price = new HouseMeterElectricPrice();
        $service_order = new HouseMeterUserPayorder();
        $templateNewsService = new TemplateNewsService();

        $time = strtotime(date('Y-m-d 00:00:00'));
        $where = [];
        $where[] = ['status', '=', '0'];
        $where[] = ['reading_time', '<', $time];
        $where[] = ['nowtime', '<', time()-3000];

        $electric_list = $house_meter_electric->getListAll($where, true, 1, 100);
        if (!empty($electric_list)) {
            $electric_list = $electric_list->toArray();
            $sys = $electric_sys->getInfo(['id' => 1]);
            foreach ($electric_list as $value) {
                $uid = $service_vacancy->getOne(['pigcms_id' => $value['vacancy_id']]);
                if ($sys) {
                    if ($sys) {
                        if (empty($value['electric_price_id'])&&empty($value['unit_price'])) {
                            $electric_price = $service_price->getInfo(['city_id' => $value['city_id'], 'house_type' => $uid['house_type']]);
                        } elseif(!empty($value['electric_price_id'])) {
                            $electric_price = $service_price->getInfo(['id' => $value['electric_price_id']]);
                        }else{
                            $electric_price['unit_price']=$value['unit_price'];
                            $electric_price['rate']=$value['rate'];
                        }

                        $date1=date('Y-m',$sys['meter_reading_date']);
                        $time1=date('Y-m-d H:i',time());
                        $time2=substr_replace($time1,$date1,0,7);
                        $dateMonth=strtotime($time2);
                        if ($sys['meter_reading_type'] == 1 && $dateMonth >= $sys['meter_reading_date']) {
                            $house_meter_electric->saveOne(['id' => $value['id']], ['nowtime' => time()]);

                            $begin_time = date('Y-m-01', time());
                            $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                            $end_time = date('Y-m-d', strtotime("$begin_time +1 month -1 day"));
                            $where1[] = ['reading_time', '<=', strtotime($end_time)];
                            $where1[] = ['electric_id', '=', $value['id']];
                            $reading_list = $electric_realtime->getList($where1);
                            $reading_list = $reading_list->toArray();
                            $len = count($reading_list);
                            if (!empty($reading_list) && $len > 0) {
                                $reading_num = $reading_list[0]['end_num'] - $reading_list[$len - 1]['begin_num'];
                                $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                                $order_arr = [
                                    'uid' => $uid['uid'],
                                    'phone' => $uid['phone'],
                                    'province_id' => $value['province_id'],
                                    'city_id' => $value['city_id'],
                                    'area_id' => $value['area_id'],
                                    'street_id' => $value['street_id'],
                                    'community_id' => $value['community_id'],
                                    'village_id' => $value['village_id'],
                                    'single_id' => $value['single_id'],
                                    'floor_id' => $value['floor_id'],
                                    'layer_id' => $value['layer_id'],
                                    'vacancy_id' => $value['vacancy_id'],
                                    'meter_reading_type' => $sys['meter_reading_type'],
                                    'payment_num' => 0,
                                    'payment_type' => 1,
                                    'begin_num' => $reading_list[$len - 1]['begin_num'],
                                    'end_num' => $reading_list[0]['end_num'],
                                    'unit_price' => $electric_price['unit_price'],
                                    'rate' => $electric_price['rate'],
                                    'add_time' => time(),
                                    'pay_time' => time(),
                                    'electric_id' => $value['id'],
                                    'order_no' =>build_real_orderid($uid['uid']) ,
                                    'charge_num' => $reading_num,
                                    'pay_type' => 'meterPay',
                                    'charge_price' => $charge_price,
                                    'status' => 2,
                                ];
                                $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                               $service_order->insertOne($order_arr);
                                $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => time()]);
                                $user = new User();
                                $openid = $user->getOne(['uid' => $uid['uid']]);

                                if (!empty($openid)) {
                                    $href1 = get_base_url('pages/houseMeter/index/billList?electric_id=' . $value['id']);
                                    $datamsg1 = [
                                        'tempKey' => 'TM01008',
                                        'dataArr' => [
                                            'href' => $href1,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '电表抄表扣费成功',
                                            'keyword1' => '电费',
                                            'keyword2' => $value['village_address'],
                                            'remark' => '缴费时间:' . date('Y-m-d H:i') . '\n' . '缴费金额:￥' . $charge_price,

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $msg=$templateNewsService->sendTempMsg($datamsg1['tempKey'], $datamsg1['dataArr']);
                                    fdump_api(['获取抄表数据-》月扣费发送模板消息'.__LINE__,$msg],'houseMeter/planLog',1);

                                }
                                if ($remaining_capacity < $sys['electric_set'] || $remaining_capacity < $sys['price_electric_set']) {
                                    if ($value['disabled'] != true) {
                                       $switch= $this->download_switch($value['id'], 'close');
                                        fdump_api(['获取抄表数据-》月扣费扣费关闸'.__LINE__,$switch],'houseMeter/planLog',1);

                                    }
                                }
                                //剩余电量小于断闸可手动开闸且大于断闸需交费
                                if ($remaining_capacity < $sys['electric_set'] && $remaining_capacity > $sys['price_electric_set']) {
                                    if (!empty($openid)) {
                                        $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                        $templateNewsService = new TemplateNewsService();
                                        $datamsg = [
                                            'tempKey' => 'OPENTM400166399',
                                            'dataArr' => [
                                                'href' => $href,
                                                'wecha_id' => $openid['openid'],
                                                'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['electric_set'] . '，电表已断闸，可重新开闸，请及时缴费！',
                                                'keyword1' => '电表状态提醒',
                                                'keyword2' => '已发送',
                                                'keyword3' => date('H:i'),
                                                'remark' => '请点击查看详细信息！',

                                            ]
                                        ];
                                        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                        $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                        fdump_api(['获取抄表数据-》月扣费剩余电量小于断闸可手动开闸且大于断闸需交费模板消息提醒'.__LINE__,$restem],'houseMeter/planLog',1);
                                    }
                                    $sms_data = array('type' => 'meter');
                                    $sms_data['uid'] = $uid['uid'];
                                    $sms_data['mobile'] = $openid['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，可重新开闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['electric_set']));
                                   $sms= (new SmsService())->sendSms($sms_data);
                                    fdump_api(['获取抄表数据-》月扣费剩余电量小于断闸可手动开闸且大于断闸需交费短信消息提醒'.__LINE__,$sms],'houseMeter/planLog',1);

                                } //剩余电量小于断闸需交费
                                if ($remaining_capacity <= $sys['price_electric_set']) {
                                    if (!empty($openid)) {
                                        $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                        $templateNewsService = new TemplateNewsService();
                                        $datamsg = [
                                            'tempKey' => 'OPENTM400166399',
                                            'dataArr' => [
                                                'href' => $href,
                                                'wecha_id' => $openid['openid'],
                                                'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['price_electric_set'] . '，电表已断闸，请及时缴费！',
                                                'keyword1' => '电表状态提醒',
                                                'keyword2' => '已发送',
                                                'keyword3' => date('H:i'),
                                                'remark' => '请点击查看详细信息！',

                                            ]
                                        ];
                                        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                        $tempMsg=$templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                        fdump_api(['获取抄表数据-》月扣费剩余电量小于断闸需交费模板消息提醒'.__LINE__,$tempMsg],'houseMeter/planLog',1);

                                        $sms_data = array('type' => 'meter');
                                        $sms_data['uid'] = $uid['uid'];
                                        $sms_data['mobile'] = $openid['phone'];
                                        $sms_data['sendto'] = 'user';
                                        $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['price_electric_set']));
                                        $sendSms= (new SmsService())->sendSms($sms_data);
                                        fdump_api(['获取抄表数据-》月扣费剩余电量小于断闸需交费短信消息提醒'.__LINE__,$sendSms],'houseMeter/planLog',1);

                                    }

                                }
                            }


                            
                        }

                        $date1=date('Y-m-d',$sys['meter_reading_date']);
                        $time1=date('Y-m-d H:i',time());
                        $time2=substr_replace($time1,$date1,0,10);
                        $dateDay=strtotime($time2);
                        if ($sys['meter_reading_type'] == 2 && $dateDay >= $sys['meter_reading_date']) {
                            $house_meter_electric->saveOne(['id' => $value['id']], ['nowtime' => time()]);
                            $begin_time = date('Y-m-d 00:00:00', time());
                            $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                            $end_time = date('Y-m-d 23:59:59', time());
                            $where1[] = ['reading_time', '<=', strtotime($end_time)];
                            $where1[] = ['electric_id', '=', $value['id']];
                            $reading_list = $electric_realtime->getList($where1);
                            $reading_list = $reading_list->toArray();
                            $len = count($reading_list);
                            if($len==1){
                                $realtime_last_info=$reading_list[0];
                            }elseif($len>1){
                                $realtime_last_info=['end_num'=>$reading_list[0]['end_num'],'begin_num'=>$reading_list[$len-1]['begin_num']];
                            }else{
                                $realtime_last_info=[];
                            }

                            if (!empty($realtime_last_info)) {
                                $reading_num = $realtime_last_info['end_num'] - $realtime_last_info['begin_num'];
                                $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                                $order_arr = [
                                    'uid' => $uid['uid'],
                                    'phone' => $uid['phone'],
                                    'province_id' => $value['province_id'],
                                    'city_id' => $value['city_id'],
                                    'area_id' => $value['area_id'],
                                    'street_id' => $value['street_id'],
                                    'community_id' => $value['village_id'],
                                    'single_id' => $value['single_id'],
                                    'floor_id' => $value['floor_id'],
                                    'layer_id' => $value['layer_id'],
                                    'vacancy_id' => $value['vacancy_id'],
                                    'meter_reading_type' => $sys['meter_reading_type'],
                                    'payment_num' => 0,
                                    'payment_type' => 1,
                                    'begin_num' => $realtime_last_info['begin_num'],
                                    'end_num' => $realtime_last_info['end_num'],
                                    'unit_price' => $electric_price['unit_price'],
                                    'rate' => $electric_price['rate'],
                                    'add_time' => time(),
                                    'pay_time' => time(),
                                    'electric_id' => $value['id'],
                                    'order_no' => rand(1000, 9999) . time(),
                                    'charge_num' => $reading_num,
                                    'pay_type' => 'meterPay',
                                    'charge_price' => $charge_price,
                                    'status' => 2,
                                ];
                                $remaining_capacity = $value['remaining_capacity'] - $reading_num;

                                $service_order->insertOne($order_arr);
                                $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => time()]);
                                $user = new User();
                                $openid = $user->getOne(['uid' => $uid['uid']]);

                                if (!empty($openid)) {
                                    $href1 = get_base_url('pages/houseMeter/index/billList?electric_id=' . $value['id']);
                                    $datamsg1 = [
                                        'tempKey' => 'TM01008',
                                        'dataArr' => [
                                            'href' => $href1,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '电表抄表扣费成功',
                                            'keyword1' => '电费',
                                            'keyword2' => $value['village_address'],
                                            'remark' => '缴费时间:' . date('Y-m-d H:i') . '\n' . '缴费金额:￥' . $charge_price,

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $resqq = $templateNewsService->sendTempMsg($datamsg1['tempKey'], $datamsg1['dataArr']);
                                    fdump_api(['获取抄表数据-》日扣费模板消息'.__LINE__,$resqq],'houseMeter/planLog',1);
                                }
                                if (($remaining_capacity < $sys['electric_set']) || ($remaining_capacity < $sys['price_electric_set'])) {
                                    if ($value['disabled'] != true) {
                                        $dayswitch=$this->download_switch($value['id'], 'close');
                                        fdump_api(['获取抄表数据-》日扣费关闸'.__LINE__,$dayswitch],'houseMeter/planLog',1);
                                    }

                                }

                                if ($remaining_capacity < $sys['electric_set'] && $remaining_capacity > $sys['price_electric_set']) {
                                    if (!empty($openid)) {
                                        $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                        $templateNewsService = new TemplateNewsService();
                                        $datamsg = [
                                            'tempKey' => 'OPENTM400166399',
                                            'dataArr' => [
                                                'href' => $href,
                                                'wecha_id' => $openid['openid'],
                                                'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['electric_set'] . '，电表已断闸，可重新开闸，请及时缴费！',
                                                'keyword1' => '电表状态提醒',
                                                'keyword2' => '已发送',
                                                'keyword3' => date('H:i'),
                                                'remark' => '请点击查看详细信息！',

                                            ]
                                        ];
                                        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                        $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                        fdump_api(['获取抄表数据-》日扣费关闸模板提醒'.__LINE__,$restem],'houseMeter/planLog',1);
                                        $sms_data = array('type' => 'meter');
                                        $sms_data['uid'] = $uid['uid'];
                                        $sms_data['mobile'] = $openid['phone'];
                                        $sms_data['sendto'] = 'user';
                                        $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，可重新开闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['electric_set']));
                                        $sendSms= (new SmsService())->sendSms($sms_data);
                                        fdump_api(['获取抄表数据-》日扣费关闸短信提醒'.__LINE__,$sendSms],'houseMeter/planLog',1);

                                    }

                                }
                                if ($remaining_capacity <= $sys['price_electric_set']) {
                                    if (!empty($openid)) {
                                        $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                        $templateNewsService = new TemplateNewsService();
                                        $datamsg = [
                                            'tempKey' => 'OPENTM400166399',
                                            'dataArr' => [
                                                'href' => $href,
                                                'wecha_id' => $openid['openid'],
                                                'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['price_electric_set'] . '，电表已断闸，请及时缴费！',
                                                'keyword1' => '电表状态提醒',
                                                'keyword2' => '已发送',
                                                'keyword3' => date('H:i'),
                                                'remark' => '请点击查看详细信息！',

                                            ]
                                        ];
                                        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                        $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                        fdump_api(['获取抄表数据-》日扣费关闸需缴费模板提醒'.__LINE__,$restem],'houseMeter/planLog',1);
                                        $sms_data = array('type' => 'meter');
                                        $sms_data['uid'] = $uid['uid'];
                                        $sms_data['mobile'] = $openid['phone'];
                                        $sms_data['sendto'] = 'user';
                                        $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['price_electric_set']));
                                        $send_Sms= (new SmsService())->sendSms($sms_data);
                                        fdump_api(['获取抄表数据-》日扣费关闸需缴费短信提醒'.__LINE__,$send_Sms],'houseMeter/planLog',1);
                                    }


                                }

                            }
                        }
                    }
                }
            }
        }

        return true;

    }



    /**
     * 单个电表下发日抄表指令
     * @author:zhubaodi
     * @date_time: 2021/4/20 11:59
     */


    public function download_meter_reading_electric($param)
    {
        $house_meter_electric = new HouseMeterAdminElectric();


        $house_meter_group = new HouseMeterElectricGroup();
        $where = [
            'id' => $param['id'],
            'status' => 0
        ];
        $electric_list = $house_meter_electric->getInfo($where);

        $reading = '';
        $sdata = [];
        if (!empty($electric_list)) {
            $group = $house_meter_group->getInfo(['id' => $electric_list['group_id'], 'status' => 1]);
            if (!empty($group)) {
                if (strlen($group['tcp_address']) == 10) {
                    $readingInfo = $this->redis->Hgetall($group['tcp_address']);
                    if (!empty($readingInfo)) {
                        $time = time() - 600;
                        if ($readingInfo['last_heart_time'] > $time) {
                            $data = [
                                'logical_address' => $group['tcp_address'],
                                'energy_meter_address' => $electric_list['electric_address'],
                                'measuring_point' => $electric_list['measure_id'],
                                'type' => 'day_reading',
                                //  'date' => ['Y' => date("y"), 'M' => date("m"), 'D' => $d],
                                'date' => $date = ['Y' => date("y"), 'M' => date("m"), 'D' => date("d", strtotime("-1 day"))],
                            ];
                            $sdata[] = $data;
                            if (!empty($sdata)) {
                                $key = 'download_' . $group['tcp_address'];
                                $valuearr = [
                                    'cmd' => 'meter_reading',
                                    'data' => $sdata,
                                ];
                                $value = json_encode($valuearr);
                                fdump_api($value, 'redismeterreading', 1);
                                $reading = $this->redis->rPush($key, $value);
                            }
                        }
                    }
                }
            }

        }


        return $reading;
    }


}
