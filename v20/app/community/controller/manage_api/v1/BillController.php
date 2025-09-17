<?php


namespace app\community\controller\manage_api\v1;

use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\service\HouseVillagePayCashierOrderService;
use app\community\model\service\HouseVillageOrderService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillagePaymentService;
use app\community\model\service\HouseVillageService;

class BillController extends CommunityBaseController
{
    /**
     * 收银台账单列表
     * @author lijie
     * @date_time 2020/08/15 9:44
     * @return \json
     */
    public function cashierBillLists()
    {
        $village_id = $this->request->post('village_id',0);
        $con = $this->request->post('con','');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        if(!$village_id)
            return api_output_error(1001,'必传参数缺失');
        $house_village_pay_cashier_order = new HouseVillagePayCashierOrderService();
        $where[] = ['a.village_id','=',$village_id];
        $where[] = ['a.paid','=',1];
        $where[]  = ['b.name|b.phone','like',"%$con%"];
        $field = 'b.name,b.address,b.phone,a.cashier_id,a.order_status,a.money,a.pay_type,a.pay_time,a.remarks,p.name as pay_name,b.single_id,b.floor_id,b.layer_id,b.vacancy_id,b.village_id';
        $data = $house_village_pay_cashier_order->get_limit_list($where,$field,$page,'a.cashier_id DESC',$limit);
        return api_output(0,$data, '获取成功');
    }

    /**
     * 收银台账单详情
     * @author lijie
     * @date_time 2020/08/15 10:06
     * @return \json
     */
    public function cashierBillDetail()
    {
        $cashier_id = $this->request->post('cashier_id',0);
        if(!$cashier_id)
            return api_output_error(1001,'必传参数缺失');
        $house_village_pay_order = new HouseVillageOrderService();
        $where['cashier_id'] = $cashier_id;
        $sum_money = $house_village_pay_order->getSumMoney($where,array(),'money');
        $pay_order_lists = $house_village_pay_order->houseVillagePayOrderLists($where,'order_name,money,order_id');
        $house_village_pay_cashier_order = new HouseVillagePayCashierOrderService();
        $data = $house_village_pay_cashier_order->get_house_village_pay_cashier_order($where,'cashier_id,money');
        $data['sum_money'] = $sum_money;
        $data['pay_order'] = $pay_order_lists;
        return api_output(0,$data, '获取成功');
    }

    /**
     * 获取未缴账单
     * @author lijie
     * @date_time 2020/08/17 10:06
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function noPayBill()
    {
        //未缴账单
        $village_id = $this->request->post('village_id',0);
        $con = $this->request->post('con','');
        $page = $this->request->post('page',1);
        if(!$village_id)
            return api_output_error(1001,'必传参数缺失');
        $house_village_user_bind = new HouseVillageUserBind();
        $house_village_user_bind_service = new HouseVillageUserBindService();
        $house_village_payment_service = new HouseVillagePaymentService();
        $house_village_order = new HouseVillageOrderService();
        $where1[] = ['village_id','=',$village_id];
        $where1[] = ['type','in',[0,3]];
        $where1[] = ['water_price|electric_price|gas_price|park_price|property_price','>',0];
        if($con){
            $where1[]  = ['name|phone','like',"%$con%"];
        }
        $pigcms1 = $house_village_user_bind_service->getHouseUserBindList($where1,'pigcms_id',0,0); //欠水电燃等用户
        $pigcms_id1 = array();
        if($pigcms1){
            foreach ($pigcms1 as $k=>$v){
                $pigcms_id1[] = $v['pigcms_id'];
            }
        }
        $where2[] = ['village_id','=',$village_id];
        $where2[] = ['type','in',[0,3]];
        $where2[] = ['property_endtime','<>',0];
        $where2[] = ['property_endtime','<',time()];
        if($con){
            $where2[]  = ['name|phone','like',"%$con%"];
        }
        $pigcms2 = $house_village_user_bind_service->getHouseUserBindList($where2,'pigcms_id',0,0); //欠物业费用户
        $pigcms_id2 = array();
        if($pigcms2){
            foreach ($pigcms2 as $k=>$v){
                $pigcms_id2[] = $v['pigcms_id'];
            }
        }
        $where3[] = ['psb.village_id','=',$village_id];
        $where3[] = ['psb.start_time','<',time()];
        $where3[] = ['psb.cycle_sum' ,'>','psb.paid_cycle'];
        $where3[] = ['hvb.type' ,'in',[0,3]];
        $where3[] = ['hvb.vacancy_id' ,'<>',0];
        $where3[] = ['hvb.village_id' ,'=',$village_id];
        if($con){
            $where3[]  = ['hvb.name|hvb.phone','like',"%$con%"];
        }
        $pigcms3 = $house_village_payment_service->getUserPaymentLists($where3,'p.payment_name,ps.cycle_type,ps.pay_type,ps.pay_money,ps.metering_mode,ps.metering_mode_type,psb.cycle_sum,psb.start_time,psb.end_time,psb.paid_cycle,ps.pay_cycle,psb.metering_mode_val,psb.bind_id,psb.pigcms_id');
        $pigcms_id3 = array();
        if($pigcms3){
            foreach ($pigcms3 as $k=>$v){
                $pigcms_id3[] = $v['pigcms_id'];
            }
        }
        $pigcms_id = array_merge($pigcms_id1,$pigcms_id2,$pigcms_id3);
        $pigcms_id = array_values(array_unique($pigcms_id));
        $where[] = ['pigcms_id','in',$pigcms_id];
        $field='sum(water_price) as water_price,sum(electric_price) as electric_price,sum(gas_price) as gas_price,sum(park_price) as park_price,sum(property_price) as property_price';
        $user_bind_list = $house_village_user_bind_service->getHouseUserBindList($where,'pigcms_id,address,phone,type,name,vacancy_id,property_endtime,housesize,status,single_id,floor_id,layer_id,village_id',$page,20)->toArray();
        $service_house_village = new HouseVillageService();
        foreach ($user_bind_list as $k=>$v){
            $user_bind_list[$k]['address'] = $service_house_village->getSingleFloorRoom($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
            $user_sum = $house_village_user_bind->getSumBill(['pigcms_id'=>$v['pigcms_id']],$field);
            if($v['property_endtime'] != 0 && $v['property_endtime']<time()){
                $price = $house_village_order->getRoomPropertyFee($v['vacancy_id'],$v['property_endtime'],$v['housesize']);
            }else{
                $price= 0;
            }
            $user_sum['property_price'] = $price;
            $user_where = [];
            $user_where[] = ['psb.pigcms_id','=',$v['pigcms_id']];
            $user_where[] = ['psb.village_id','=',$village_id];
            $user_where[] = ['psb.start_time','<',time()];
            $user_where[] = ['psb.cycle_sum' ,'>','psb.paid_cycle'];
            $user_where[] = ['hvb.village_id' ,'=',$village_id];
            $info = $house_village_payment_service->getUserPaymentLists($user_where,'p.payment_name,ps.cycle_type,ps.pay_type,ps.pay_money,ps.metering_mode,ps.metering_mode_type,psb.cycle_sum,psb.start_time,psb.end_time,psb.paid_cycle,ps.pay_cycle,psb.metering_mode_val,psb.bind_id');
            $user_sum['payment_price'] = 0;
            if($info){
                foreach ($info as $key=>$val){
                    $user_sum['payment_price'] += $val['pay_money'];
                }
            }
            $user_sum['payment_price'] = round($user_sum['payment_price'],2);
            $money = $user_sum['water_price'] + $user_sum['gas_price'] +$user_sum['electric_price'] +$user_sum['park_price']+$user_sum['property_price'] + $user_sum['payment_price'];
            $user_bind_list[$k]['money'] = round($money,2);
        }
        $data = $user_bind_list;
        //水费，电费，燃气费，停车费，物业费，自定义缴费
        $where = array();
        $where[] = ['village_id','=',$village_id];
        $where[] = ['type' ,'in',[0,3]];
        $where[] = ['status','=',1];
        if($con){
            $where[]  = ['name|phone','like',"%$con%"];
        }
        $sum = $house_village_user_bind->getSumBill($where,$field);
        $where[] = ['property_endtime','<',time()];
        $where[] = ['property_endtime','<>',0];
        $sum['property_price'] = 0;
        $user_list = $house_village_user_bind_service->getList($where,'pigcms_id,vacancy_id,property_endtime,housesize');
        foreach ($user_list as $k_1=>$v_1){
            $price = $house_village_order->getRoomPropertyFee($v_1['vacancy_id'],$v_1['property_endtime'],$v_1['housesize']);
            $sum['property_price']+=$price;
        }
        if($con){
            $where_con[] = ['hvb.name|hvb.phone','like',"%$con%"];
        }
        $where_con[] = ['psb.village_id','=',$village_id];
        $where_con[] = ['psb.start_time','<',time()];
        $where_con[] = ['psb.cycle_sum' ,'>','psb.paid_cycle'];
        $where_con[] = ['hvb.type' ,'in',[0,3]];
        $where_con[] = ['hvb.vacancy_id' ,'<>',0];
        $where_con[] = ['hvb.village_id' ,'=',$village_id];
        $info = $house_village_payment_service->getUserPaymentLists($where_con,'p.payment_name,ps.cycle_type,ps.pay_type,ps.pay_money,ps.metering_mode,ps.metering_mode_type,psb.cycle_sum,psb.start_time,psb.end_time,psb.paid_cycle,ps.pay_cycle,psb.metering_mode_val,psb.bind_id');
        $sum['payment_price'] = 0;
        if($info){
            foreach ($info as $key1=>$val1){
                $sum['payment_price'] += $val1['pay_money'];
            }
        }
        $sum['payment_price'] = getFormatNumber($sum['payment_price']);
        $total_price = 0;
        foreach ($sum as $k1=>$v1){
            if($v1==null){
                $sum[$k1] = 0.00;
            }
            $total_price += $v1;
        }
        $sum['total_price'] =  round($total_price,2);
        $res['list'] = $data;
        $res['sum'] = $sum;
        return api_output(0,$res, '获取成功');
    }

    /**
     * 获取未缴账单详情
     * @author lijie
     * @date_time 2020/08/17 11:18
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function noPayBillDetail()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        $village_id = $this->request->post('village_id',0);
        if(!$village_id || !$pigcms_id)
            return api_output_error(1001,'必传参数缺失');
        $field='sum(water_price) as water_price,sum(electric_price) as electric_price,sum(gas_price) as gas_price,sum(park_price) as park_price,sum(property_price) as property_price';
        $house_village_user_bind = new HouseVillageUserBind();
        $house_village_payment_service = new HouseVillagePaymentService();
        $house_village_order = new HouseVillageOrderService();
        $house_village_user_bind_service = new HouseVillageUserBindService();
        $user_info = $house_village_user_bind_service->getBindInfo(['pigcms_id'=>$pigcms_id],'property_endtime,housesize,vacancy_id');
        $where['pigcms_id'] = $pigcms_id;
        $sum = $house_village_user_bind->getSumBill($where,$field);
        if($user_info['property_endtime'] != 0 && $user_info['property_endtime'] < time()){
            $price = $house_village_order->getRoomPropertyFee($user_info['vacancy_id'],$user_info['property_endtime'],$user_info['housesize']);
        }else{
            $price = 0;
        }
        $sum['property_price'] = $price;
        $where_con[] = ['psb.pigcms_id','=',$pigcms_id];
        $where_con[] = ['psb.start_time','<',time()];
        $where_con[] = ['psb.cycle_sum' ,'>','psb.paid_cycle'];
        $where_con[] = ['psb.village_id','=',$village_id];
        $info = $house_village_payment_service->getUserPaymentLists($where_con,'p.payment_name,ps.cycle_type,ps.pay_type,ps.pay_money,ps.metering_mode,ps.metering_mode_type,psb.cycle_sum,psb.start_time,psb.end_time,psb.paid_cycle,ps.pay_cycle,psb.metering_mode_val,psb.bind_id');
        $sum['payment_price'] = 0;
        if($info){
            foreach ($info as $key=>$val){
                $sum['payment_price'] += $val['pay_money'];
            }
        }
        $data = array();
        for ($i=0;$i<6;$i++){
            switch ($i){
                case 0:
                    $data[$i]['title'] = '水费';
                    $data[$i]['name'] = getFormatNumber($sum['water_price']);
                    break;
                case 1:
                    $data[$i]['title'] = '电费';
                    $data[$i]['name'] = getFormatNumber($sum['electric_price']);
                    break;
                case 2:
                    $data[$i]['title'] = '燃气费';
                    $data[$i]['name'] = getFormatNumber($sum['gas_price']);
                    break;
                case 3:
                    $data[$i]['title'] = '停车费';
                    $data[$i]['name'] = getFormatNumber($sum['park_price']);
                    break;
                case 4:
                    $data[$i]['title'] = '物业费';
                    $data[$i]['name'] = getFormatNumber($sum['property_price']);
                    break;
                case 5:
                    $data[$i]['title'] = '自定义缴费';
                    $data[$i]['name'] = getFormatNumber($sum['payment_price']);
                    break;
            }
        }
        return api_output(0,$data, '获取成功');
    }
}