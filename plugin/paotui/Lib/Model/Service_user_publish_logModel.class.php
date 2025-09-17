<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * Date: 2019/6/5
 * Time: 13:49
 */

class Service_user_publish_logModel extends Model
{

    public function add_log($param)
    {
        if (empty($param['publish_id'])){
            return false;
        } elseif ($publish_info = D('Service_user_publish')->field(true)->where(array('publish_id' => $param['publish_id']))->find()) {
            // status 状态(操作状态 0 用户下单 1 用户支付订单待服务 2 配送员已经接单，待取货  3.配送员取货，配送中  4.配送员送达，订单完成
            // 5.用户申请退款 6.退款成功订单关闭 7.用户已取消 8.已服务待确认 9.服务过期 10.服务已完成评价成功 11.过期退款 12.退款失败 13.配送员取消配送扔回)
            $data = array();
            $data['add_time'] = time();
            $data['publish_id'] = intval($param['publish_id']);
            $data['catgory_type'] = intval($publish_info['catgory_type']);
            $data['admin_id'] = isset($param['admin_id']) ? intval($param['admin_id']) : 0;
            $data['uid'] = isset($param['uid']) ? intval($param['uid']) : 0;
            $data['status'] = isset($param['status']) ? intval($param['status']) : 0;
            $data['phone'] = isset($param['phone']) ? $param['phone'] : '';
            $data['name'] = isset($param['name']) ? $param['name'] : '';
            $data['remark'] = isset($param['remark']) ? $param['remark'] : '';
            $data['type'] = isset($param['type']) ? intval($param['type']) : 0;
            return $this->add($data);
        }
    }
    public function notify_url($params, $notifyurl){
        // status 状态(操作状态 0 用户下单 1 用户支付订单待服务 2 配送员已经接单，待取货  3.配送员取货，配送中  4.配送员送达，订单完成
        // 5.用户申请退款 6.退款成功订单关闭 7.用户已取消 8.已服务待确认 9.服务过期 10.服务已完成评价成功 11.过期退款 12.退款失败 13.配送员取消配送扔回)
        fdump('==========$notifyurl================'.__LINE__, '$notify_info', true);
        fdump($notifyurl, '$notify_info', true);
        fdump('----------$params------------', '$notify_info', true);
        fdump($params, '$notify_info', true);
        for ($i=0 ; $i < 3; $i++) {
            $notify_info = httpRequest($notifyurl,'POST',$params);
            fdump('---------$notify_info1-------------', '$notify_info', true);
            fdump($notify_info, '$notify_info', true);
            $notify_info = json_decode($notify_info[1], true);
            fdump('----------$notify_info2------------', '$notify_info', true);
            fdump($notify_info, '$notify_info', true);
            if ($notify_info['err_code']==0 && $notify_info['err_msg']=='success') {
                break;
            } else {
                sleep(10);
            }
        };
    }
}