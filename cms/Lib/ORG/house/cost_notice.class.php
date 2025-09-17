<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/11/4 11:02
 */

class cost_notice {
    public function propertyFeeNotice($param) {
        if (!$param['bind_id']) {
            fdump('缺少参数>>'.__LINE__,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            fdump($param,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            return false;
        }
        $where_bind = [
            'pigcms_id' => intval($param['bind_id'])
        ];
        $db_house_village_user_bind = D('House_village_user_bind');
        $bind_user = D('House_village_user_bind')->where($where_bind)->find();
        if (!$bind_user) {
            fdump('查询不到对应对应>>'.__LINE__,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            fdump($param,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            fdump(M('House_village_user_bind')->_sql(),'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            fdump(M('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            fdump($bind_user,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            return false;
        } elseif (!$bind_user['uid']) {
            fdump('未关联用户>>'.__LINE__,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            fdump($param,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            fdump(M('House_village_user_bind')->_sql(),'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            fdump(M('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            fdump($bind_user,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
            return false;
        }

        // 计算欠缴物业费
        $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$bind_user['vacancy_id'],'is_del'=>1])->field('standard_id')->find();
        if(!$standard_id['standard_id']){
            $property_money = 0;
        }elseif ($bind_user['property_endtime'] && $bind_user['property_endtime'] < time()){
            $dateMonth = array();
            $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
            $num = $db_house_village_user_bind->getMonthNum($bind_user['property_endtime'] + 1, time());
            if(date('d',time()) > date('d',$bind_user['property_endtime'] + 1)) {
                $num+=1;
            }
            $start_time = strtotime(date('Y-m',$bind_user['property_endtime']));
            $dateMonth[] = $start_time;
            for ($i=1;$i<$num;$i++){
                $dateMonth[] = strtotime("+ $i month",$start_time);
            }
            $property_money = $db_house_village_user_bind->property_price($dateMonth,unserialize($date['date_month']),$bind_user['housesize']);
        }else{
            $property_money = 0;
        }

        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pageName' => 'payMentlist',
            'isAll' => true,
            'urlParam' => '',
        ];
        $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);

        $getIsAihorseCustomized = D('Config_customization')->getIsAihorseCustomized();
        $third_pay_url = '';
        if($getIsAihorseCustomized){
            $village_info_arr= M('House_village_info')->where(['village_id'=>$this->village_id])->field('third_pay_url,third_ccb_pay_url')->find();
            $third_pay_url = isset($village_info_arr['third_pay_url']) && $village_info_arr['third_pay_url'] ? $village_info_arr['third_pay_url'] : $third_pay_url;
            $third_pay_url = isset($village_info_arr['third_ccb_pay_url']) && $village_info_arr['third_ccb_pay_url'] ? $village_info_arr['third_ccb_pay_url'] : $third_pay_url;
        }
        
        if ($property_money>0 && $bind_user['uid']) {
            $now_user = M('User')->where(['uid'=>$bind_user['uid']])->field('openid, is_follow, phone')->find();
            $href = $payMentlistUrl.'?village_id=' . $bind_user['village_id'].'&pigcms_id='.$bind_user['pigcms_id'];
            $href = $third_pay_url ?: $href;
            fdump('欠费发送>>'.__LINE__,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
            fdump($href,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
            $property_money = round_number($property_money, 2);
            if ($now_user['openid'] && $now_user['is_follow']) {
                $address = D('House_village')->get_single_floor_room($bind_user['single_id'],$bind_user['floor_id'],$bind_user['layer_id'],$bind_user['vacancy_id'],$bind_user['village_id']);
                if (!$address) {
                    $address = $bind_user['address'];
                }
                $property_name_info = D('House_village')->where(['village_id' => $bind_user['village_id']])->field('village_name,property_name')->find();
                $property_name = $property_name_info['property_name'] ? $property_name_info['property_name'] : $property_name_info['village_name'];
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => ' 尊敬的业主，当前您物业费已欠费！', 'keynote2' => $address, 'keynote1' => $property_name, 'remark' => '欠缴物业费为：['.C('config.Currency_symbol').$property_money.']，点击缴费！'));
            } elseif ($now_user['phone'] || $bind_user['phone']) {
                $phone = $bind_user['phone'] ? $bind_user['phone'] : $now_user['phone'];
                $data = array();
                $data['village_id'] = $bind_user['village_id'];
                $data['send_type'] = 2;
                $data['template_id'] = 0;
                $data['add_time'] = time();
                $data['status'] = 0;
                $data['send_phone_txt'] = $phone;
                $data['send_num'] = 1;
                $data['real_send_num'] = 1;
                $add_id = M('House_sms_group_send')->data($data)->add();
                $sms_data = array('mer_id' => 0, 'store_id' => 0, 'type' => 'house_group_send');
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $phone;
                $sms_data['sendto'] = 'user';
                $sms_data['village_id'] = $bind_user['village_id'];
                $sms_data['village_send_id'] = $add_id;
                $sms_data['content'] = "您已欠物业费，请及时缴费：{$href}";
                fdump('短信发送>>'.__LINE__,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                fdump($bind_user,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                fdump($now_user,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                fdump($sms_data,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                Sms::sendSms($sms_data);
            }
            import('@.ORG.Apppush');
            $now_order['bind_id'] = $bind_user['village_id'] . '_' . $bind_user['pigcms_id'];
            $now_order['url'] = $href;
            $now_order['title'] = '物业费通知';
            $now_order['msg'] = "尊敬的业主，当前您物业费已欠费[".C('config.Currency_symbol')."{$property_money}],点击前往缴费";
            $apppush = new Apppush();
            $apppush->send($now_order, 'house_notice');
        } elseif ($bind_user['property_endtime']) {
            $advance_payment_days = M('House_village_about_config')->where(['village_id'=>$bind_user['village_id']])->getField('advance_payment_days');
            if ($advance_payment_days==='' || $advance_payment_days===NULL) {
                fdump('设置为不通知>>'.__LINE__,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
                fdump($param,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
                fdump($bind_user,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
                fdump($advance_payment_days,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
                return false;
            }
            if (!$advance_payment_days) {
                $advance_payment_days = 15;
            }
            if ($advance_payment_days<=0) {
                fdump('设置为不通知>>'.__LINE__,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
                fdump($param,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
                fdump($bind_user,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
                fdump($advance_payment_days,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeErrLog',true);
                return false;
            }
            $now_time_start = strtotime(date('Y-m-d 00:00:00'));
            $notice_time_start = strtotime(" +{$advance_payment_days} day", $now_time_start);
            $property_endtime = strtotime(date('Y-m-d 00:00:00',$bind_user['property_endtime']));
            if ($property_endtime == $notice_time_start) {
                $msg = "您的物业费还有{$advance_payment_days}天到期，请及时缴费";
                $now_user = M('User')->where(['uid'=>$bind_user['uid']])->field('openid, is_follow, phone')->find();
                $href = $payMentlistUrl . '?village_id=' . $bind_user['village_id'].'&pigcms_id='.$bind_user['pigcms_id'];
                $href = $third_pay_url ?: $href;

                fdump('提前发送>>'.__LINE__,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                fdump($href,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                $property_money = round_number($property_money, 2);
                if ($now_user['openid'] && $now_user['is_follow']) {
                    $address = D('House_village')->get_single_floor_room($bind_user['single_id'],$bind_user['floor_id'],$bind_user['layer_id'],$bind_user['vacancy_id'],$bind_user['village_id']);
                    if (!$address) {
                        $address = $bind_user['address'];
                    }
                    $property_name_info = D('House_village')->where(['village_id' => $bind_user['village_id']])->field('village_name,property_name')->find();
                    $property_name = $property_name_info['property_name'] ? $property_name_info['property_name'] : $property_name_info['village_name'];
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => ' 物业费通知！', 'keynote2' => $address, 'keynote1' => $property_name, 'remark' => $msg));
                } elseif ($now_user['phone'] || $bind_user['phone']) {
                    $phone = $bind_user['phone'] ? $bind_user['phone'] : $now_user['phone'];
                    $data = array();
                    $data['village_id'] = $bind_user['village_id'];
                    $data['send_type'] = 2;
                    $data['template_id'] = 0;
                    $data['add_time'] = time();
                    $data['status'] = 0;
                    $data['send_phone_txt'] = $phone;
                    $data['send_num'] = 1;
                    $data['real_send_num'] = 1;
                    $add_id = M('House_sms_group_send')->data($data)->add();
                    $sms_data = array('mer_id' => 0, 'store_id' => 0, 'type' => 'house_group_send');
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $phone;
                    $sms_data['sendto'] = 'user';
                    $sms_data['village_id'] = $bind_user['village_id'];
                    $sms_data['village_send_id'] = $add_id;
                    $sms_data['content'] = "{$msg}：{$href}";
                    fdump('短信发送>>'.__LINE__,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                    fdump($bind_user,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                    fdump($now_user,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                    fdump($sms_data,'api/log/'.date('Ymd').'/cost_notice/propertyFeeNoticeLog',true);
                    Sms::sendSms($sms_data);
                }
                import('@.ORG.Apppush');
                $now_order['bind_id'] = $bind_user['village_id'] . '_' . $bind_user['pigcms_id'];
                $now_order['url'] = $href;
                $now_order['title'] = '物业费通知';
                $now_order['msg'] = $msg;
                $apppush = new Apppush();
                $apppush->send($now_order, 'house_notice');
            }
        }

        return $property_money;
    }
}