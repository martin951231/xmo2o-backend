<?php

/**
 * 收费标准管理
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/10 11:47
 */

namespace app\community\controller\house_meter;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterService;

class MeterUserPayorderController extends CommunityBaseController{


    /**
     * 获取账单列表
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:49
     * @param 传参
     * array (
     *  'phone'=> '联系电话',
     *  'username'=> '用户名',
     *  'payment_num'=> '缴费项目',
     *  'payment_type'=> '缴费类型',
     *  'pay_type'=> '支付类型',
     *  'begin_time'=> '开始时间',
     *  'end_time'=> '结束时间',
     *  'page'=> '页码',
     * )
     */
    public function payorderList() {
        // 获取登录信息
        $data['admin_id']=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']){
            return api_output(1002,[],'请先登录！');
        }
        $data['key_val'] = $this->request->param('key_val','','trim');
        $data['value'] = $this->request->param('value','','trim');
        $data[$data['key_val']] = $data['value'];
        $data['payment_type'] = $this->request->param('payment_type','','intval');
        $data['pay_type'] = $this->request->param('pay_type','','trim');
        $data['date'] = $this->request->param('date','','trim');

        $data['province_id'] = $this->request->param('province','','intval');
        $data['city_id'] = $this->request->param('city','','intval');
        $data['area_id'] = $this->request->param('area','','intval');
        $data['street_id'] = $this->request->param('street','','intval');
        $data['community_id'] = $this->request->param('community','','intval');
        $data['village_id'] = $this->request->param('village','','intval');
        $data['single_id'] = $this->request->param('single','','intval');
        $data['floor_id'] = $this->request->param('floor','','intval');
        $data['layer_id'] = $this->request->param('layer','','intval');
        $data['vacancy_id'] = $this->request->param('vacancy','','intval');


        if (isset($data['date'])){
            if (isset($data['date'][0])){
                $data['begin_time'] = $data['date'][0];

            }
            if (isset($data['date'][1])){
                $data['end_time'] = $data['date'][1];
            }

        }
        $data['page'] = $this->request->param('page',0,'intval');
        $data['limit'] = 20;
        fdump_api($data,'payorderList',1);
        $serviceHouseMeter = new HouseMeterService();
        $order_list = $serviceHouseMeter->getPayorderList($data);
        $order_list['total_limit'] =  $data['limit'];
        return api_output(0,$order_list);
    }

    public function payorderPrint(){
        // 获取登录信息
        $data['admin_id']=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']){
            return api_output(1002,[],'请先登录！');
        }
        $data['key_val'] = $this->request->param('key_val','','trim');
        $data['value'] = $this->request->param('value','','trim');
        $data[$data['key_val']] = $data['value'];
        $data['payment_type'] = $this->request->param('payment_type','','intval');
        $data['pay_type'] = $this->request->param('pay_type','','trim');
        $data['date'] = $this->request->param('date','','trim');
        $data['province_id'] = $this->request->param('province','','intval');
        $data['city_id'] = $this->request->param('city','','intval');
        $data['area_id'] = $this->request->param('area','','intval');
        $data['street_id'] = $this->request->param('street','','intval');
        $data['community_id'] = $this->request->param('community','','intval');
        $data['village_id'] = $this->request->param('village','','intval');
        $data['single_id'] = $this->request->param('single','','intval');
        $data['floor_id'] = $this->request->param('floor','','intval');
        $data['layer_id'] = $this->request->param('layer','','intval');
        $data['vacancy_id'] = $this->request->param('vacancy','','intval');
        if (isset($data['date'])){
            if (isset($data['date'][0])){
                $data['begin_time'] = $data['date'][0];
            }
            if (isset($data['date'][1])){
                $data['end_time'] = $data['date'][1];
            }

        }
        $serviceHouseMeter = new HouseMeterService();
        $order_list = $serviceHouseMeter->getPayorderPrint($data);

        return api_output(0,$order_list);
    }
}
