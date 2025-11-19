<?php
/**
 * Class WxCircleIntegralAction
 * 微信商圈积分对接
 */

class WxCircleIntegralAction extends BaseAction
{
    public $APIv3_key = '';
    public $serial_no = '';
    public $private_file = '';

    /**
     * 是否引导注册组件
     */
    public function getWinStatus()
    {
        $uid = $this->user_session['uid'];//用户的id
        $data['win_open'] = 0;//默认不弹窗
        $data['wxapp_openid'] = 0;//小程序openid
        $data['mch_id'] = 0;//商户号
        if ($uid) {
            $w_user = M('Weixin_bind_user')->where(['uid' => $uid])->find();
            if (!empty($w_user)) {
                if (!empty($w_user['wxapp_openid'])) {
                    $w_app = M('Weixin_app_bind')->where(['bind_type' => 0, 'other_id' => $w_user['mer_id']])->find();
                    if ($w_app['circle_status']) {
                        $data['mch_id'] = $w_app['wxpay_merid'];
                        $data['wxapp_openid'] = $w_user['wxapp_openid'];
                        import('ORG.Net.Http');
                        $http = new Http();
                        $url = 'https://api.mch.weixin.qq.com/v3/businesscircle/user-authorizations/' . $w_user['wxapp_openid'] . '?appid=' . $w_app['appid'];
                        $serial_no = $w_app['serial_no'];//商户API证书序列号
                        $type="GET";
                        $signature = $this->sign_encode('', $url, $data['mch_id'], $serial_no, $w_app['private_key'],$type);
                        $headers = [
                            'Accept: application/json',
                            'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
                            'Authorization: WECHATPAY2-SHA256-RSA2048 ' . $signature
                        ];
                        $return = $http->curlGet($url, 15, $headers);
                        $jsonrt = json_decode($return, true);
                        if (isset($jsonrt['authorize_state']) && ($jsonrt['authorize_state'] == 'UNAUTHORIZED' || $jsonrt['authorize_state'] == 'DEAUTHORIZED')) {//未授权
                            $data['win_open'] = 1;
                        }
                    }
                }
            }
        }
        echo json_encode($data);
    }

    //签名生成
    public function sign_encode($body = '', $url, $mchid, $serial_no, $private_key,$type)
    {
        // Authorization: <schema> <token>
        $nostr = time(); //随机字符串
        $timestamp = time(); //时间戳
        $http_method = $type; //提交方式
        $url_parts = parse_url($url);
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : "")); //这个是官方样例里面的
        $mch_private_key = file_get_contents(replace_file_domain($private_key)); //商户的私钥
        $message = $http_method . "\n" . $canonical_url . "\n" . $timestamp . "\n" . $nostr . "\n" . $body . "\n"; //拼接加密字符串
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption'); //进行加密
        $sign = base64_encode($raw_sign); //加密
        //$schema = 'WECHATPAY2-SHA256-RSA2048'; //官方写的 完全不知道干啥用就注释了
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"', $mchid, $nostr, $timestamp, $serial_no, $sign); //得出结果 然后传递给Authorization这个头文件
        return $token;
    }

    //小程序回调接口
    public function callBack()
    {
        $merId = $_GET['mer_id'] ? intval($_GET['mer_id']) : 1;
        $sourceData = file_get_contents('php://input');
        fdump_sql([$sourceData, $merId], 'WxCircleIntegralCallback');
        $data = json_decode($sourceData, true);

        $uniqueKey = md5($data['id'] . 'WxCircleIntegralCallBack');
        $lockRs = setLock($uniqueKey, 10);
        if ($lockRs['error_no']) {
            exit(json_encode(['code' => 'SUCCESS', 'message' => '成功']));
        }

        $record = [
            'mer_id' => $merId,
            'raw_data' => $sourceData,
            'add_time' => time(),
            'status' => 0
        ];
        $merWxappSetting = M('Weixin_app_bind')->where(['bind_type' => 0, 'other_id' => $merId])->find();
        $this->APIv3_key = $merWxappSetting['apiv3_key'];
        $this->serial_no = $merWxappSetting['serial_no'];
        $this->private_file = $merWxappSetting['private_key'];

        if ($data['resource_type'] == 'encrypt-resource') {//支付退款回调通知
            $resource = $data['resource'];
            $nonceStr = $resource['nonce'];
            $associatedData = $resource['associated_data'];
            $ciphertext = $resource['ciphertext'];
            $ciphertext = base64_decode($ciphertext);
            $eventType = $data['event_type'];
        
            $check_sodium_mod = extension_loaded('sodium');
            $check_aes256gcm = sodium_crypto_aead_aes256gcm_is_available();

            if ($check_sodium_mod !== false && $check_aes256gcm !== false) {
                $pem = sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->APIv3_key);
                $pem = json_decode($pem, true);

                if (isset($pem['openid'])) {
                    $record['openid'] = $pem['openid'];//用户小程序openid
                    $record['transaction_id'] = $pem['transaction_id'];//微信订单id
                    $record['appid'] = $pem['appid'];//小程序的appid
                    $record['mchid'] = $pem['mchid'];
                    $record['shop_name'] = $pem['shop_name'];

                    $store = M('Merchant_store')->where(['name'=>$record['shop_name'],'mer_id'=>$merId])->find();
                    if($store){
                        $record['store_id'] = $store['store_id'];
                        $record['rate'] = $store['rmb_score_rate'];
                    }
                    
                    $wxapp_openid = $pem['openid'];//用户小程序openid
                    $w_user = M('Weixin_bind_user')->alias('bu')->join(C('DB_PREFIX') . 'user u ON bu.uid = u.uid')
                        ->where(['bu.wxapp_openid' => $wxapp_openid, 'u.status' => ['NEQ', 4]])
                        ->field('bu.*,u.uid,u.phone')
                        ->find();
                    if($w_user){
                        $card_user = M('Card_userlist')->where(['mer_id'=>$merId, 'uid'=>$w_user['uid']])->find();
                        $record['card_id'] = $card_user['id'];
                    }
                    $record['uid'] = $w_user['uid'] ?? 0;
                    $record['phone'] = $w_user['phone'] ?? '';
                    //支付
                    if ($eventType == 'MALL_TRANSACTION.SUCCESS' && intval($pem['amount']) >= 1) {
                        $record['amount'] = $pem['amount'] / 100;
                        $record['event_type'] = 1;
                        if($store){
                            $record['score'] = $store['rmb_score_rate'] * $record['amount'];
                        }
                    }
                    //退款
                    else if($eventType == 'MALL_REFUND.SUCCESS' && intval($pem['refund_amount']) >= 1){
                        $record['event_type'] = 2;
                        $record['refund_amount'] = $pem['refund_amount'] / 100;
                        $record['refund_id'] = $pem['refund_id']??'';
                        if($store){
                            $record['score'] = $store['rmb_score_rate'] * $record['refund_amount'] * -1;
                        }
                    }
                }

            }
        }
        
        M('Wx_sync_score_log')->add($record);
        echo json_encode(['code' => 'SUCCESS', 'message' => '成功']);
    }

    //写入微信积分同步任务
    public function syncPointTask()
    {
        //记录缓存放置重复同步
        $lockKey = 'syncPointTask';
        if(S($lockKey)){
            return false;
        }
        S($lockKey, 1, 50);
        $taskMod = M('Wx_sync_score_log');
        $tasks = $taskMod->where(['status' => 0])->order('id ASC')->limit(10)->select();
        foreach ($tasks as $v) {
            $merId = $v['mer_id'];
            $merWxappSetting = M('Weixin_app_bind')->where(['bind_type' => 0, 'other_id' => $merId])->find();
            $this->APIv3_key = $merWxappSetting['apiv3_key'];
            $this->serial_no = $merWxappSetting['serial_no'];
            $this->private_file = $merWxappSetting['private_key'];
            $data = json_decode($v['raw_data'], true);

            if ($data['resource_type'] == 'encrypt-resource') {//支付退款回调通知
                $resource = $data['resource'];
                $nonceStr = $resource['nonce'];
                $associatedData = $resource['associated_data'];
                $ciphertext = $resource['ciphertext'];
                $ciphertext = base64_decode($ciphertext);
                $eventType = $data['event_type'];

                $check_sodium_mod = extension_loaded('sodium');
                if ($check_sodium_mod === false) {
                    fdump('没有安装sodium模块', "wxapp_circle_pay_after");
                }
                $check_aes256gcm = sodium_crypto_aead_aes256gcm_is_available();
                if ($check_aes256gcm === false) {
                    fdump('当前不支持aes256gcm', "wxapp_circle_pay_after");
                }
                $pem = sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->APIv3_key);
                $pem = json_decode($pem, true);

                if (isset($pem['openid'])) {
                    $wxapp_openid = $pem['openid'];//用户小程序openid
                    $w_user = M('Weixin_bind_user')->alias('bu')->join(C('DB_PREFIX') . 'user u ON bu.uid = u.uid')
                        ->where(['bu.wxapp_openid' => $wxapp_openid, 'u.status' => ['NEQ', 4]])
                        ->field('bu.*')
                        ->find();
                    if (!empty($merWxappSetting) && $merWxappSetting['circle_status']) {//开启小程序商圈积分
                        $data['openid'] = $pem['openid'];//用户小程序openid
                        $data['transaction_id'] = $pem['transaction_id'];//微信订单id
                        $data['appid'] = $pem['appid'];//小程序的appid
                        $data['points_update_timedate'] = date('c', time());
                        $data['earn_points'] = true;//获得积分
                        $data['mchid'] = $pem['mchid'];
                        if ($eventType == 'MALL_TRANSACTION.SUCCESS' && intval($pem['amount']) >= 1) {//支付
                            $amount = $pem['amount'];//购买金额
                            $data['money'] = $pem['amount'];//购买金额
                            $data['type'] = 1;//增加积分
                            $data['increased_points'] = intval($v['score']);//增加积分
                            $data['no_points_remarks'] = "【{$pem['shop_name']}】微信商圈支付积分";
                            
                            $this->auto_get($w_user['uid'], $w_user['mer_id'], '', true, $v['score'], $pem['shop_name']);
                        }
                        if ($eventType == 'MALL_REFUND.SUCCESS' && intval($pem['refund_amount']) >= 1) {//退款
                            $amount = $pem['refund_amount'];//退款金额
                            $data['money'] = $pem['refund_amount'];//退款金额
                            $data['type'] = 0;//减少积分
                            $data['increased_points'] = intval($pem['refund_amount']) * -1;//增加负积分
                            $data['no_points_remarks'] = "【{$pem['shop_name']}】微信商圈退款积分";
                            $user_card = M('Card_userlist')->where(array('uid' => $w_user['uid'], 'mer_id' => $w_user['mer_id']))->find();
                            $card = M(_view('Card_new'))->where(array('mer_id' => $w_user['mer_id']))->find();
                            $date_row['card_id'] = $user_card['id'];
                            $date_row['type'] = 2;
                            // $date_row['score_use'] = $amount * $card['support_score'];
                            $date_row['score_use'] = $v['score'] * -1;
                            $date_row['desc'] = "【{$pem['shop_name']}】微信商圈退款积分";
                            $date_row['order_type'] = 20;
                            $res = D('Card_new')->add_row($date_row);
                            if($res){
                                M('Card_userlist')->where(['id'=>$user_card['id']])->save([
                                    'card_score'    => $user_card['card_score'] + $v['score']
                                ]);
                            }
                        }
                        $res = $this->toget_wx($data);//积分同步
                    }
                }
            }

            $update = [
                'status' => 1,
                'response_data' => json_encode($res ?? [], JSON_UNESCAPED_UNICODE),
                'sync_time' => time(),
                'sync_type' =>  1
            ];
            $taskMod->where(['id' => $v['id']])->save($update);
        }
        S($lockKey, null);
    }


    //积分同步
    protected function toget_wx($data){
        $w_user = M('Weixin_bind_user')->where(['wxapp_openid' => $data['openid']])->find();
        if (!empty($w_user)) {
            if (!empty($w_user['wxapp_openid'])) {
                $w_app = M('Weixin_app_bind')->where(['bind_type' => 0, 'other_id' => $w_user['mer_id']])->find();
                if ($w_app['circle_status']) {
                    import('ORG.Net.Http');
                    $url = 'https://api.mch.weixin.qq.com/v3/businesscircle/points/notify';
                    $syncData = [
                        'transaction_id' => $data['transaction_id'],
                        'appid' => $data['appid'],
                        'openid' => $data['openid'],
                        'earn_points' => $data['earn_points'],
                        'increased_points' => $data['increased_points'],
                        'points_update_time' => $data['points_update_timedate'],
                    ];
                    $signature = $this->sign_encode(json_encode($syncData), $url, $data['mchid'], $this->serial_no, $this->private_file, 'POST');
                    $headers = [
                        'Content-Type:application/json',
                        'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
                        'Authorization: WECHATPAY2-SHA256-RSA2048 ' . $signature
                    ];
                    $return = Http::curlPostOwnWithHeader($url, json_encode($syncData), $headers, 15);
                    fdump([$url, $syncData, $headers, $return], "wxapp_circle_pay_after");
                    $jsonrt = json_decode($return, true);
                    $jsonrt['req_data'] = ['req'=>$syncData,'header'=>$headers];
                    return $jsonrt;
                }
            }
        }
    }

//自动领卡
    public function auto_get($uid, $mer_id, $wxcardcode = '', $is_auto = false, $amount,$shop_name = '')
    {
        if ($card = M(_view('Card_new'))->where(array('mer_id' => $mer_id))->find()) {
            if (!$card['auto_get'] && !$card['auto_get_buy'] && !$is_auto) {
                fdump_sql('该商家' . $mer_id . '会员卡不能自动领卡', "wxapp_circle_pay_after");
                return array('status' => false);
            }
            if (!$card['status']) {
                fdump_sql('该商家' . $mer_id . '会员卡没有启用', "wxapp_circle_pay_after");
                return array('status' => false);
            }
            if($amount <= 0){
                fdump_sql(['err'=>"兑换积分为0！",$uid, $mer_id, $wxcardcode, $is_auto, $amount,$shop_name], "wxapp_circle_pay_after");
                return array('status' => false);
            }
            $user_card = M('Card_userlist')->where(array('uid' => $uid, 'mer_id' => $mer_id))->find();
            if (($uid && $user_card)) {
                $date_row['card_id'] = $user_card['id'];
                $date_row['type'] = 1;
                $date_row['score_add'] = $amount;
                $date_row['desc'] = "【{$shop_name}】微信商圈支付积分";
                $date_row['order_type'] = 20;
                D('Card_new')->add_row($date_row);

                $data_userlist['card_score'] = $user_card['card_score'] + $date_row['score_add'];
                M('Card_userlist')->where(array('id'=>$user_card['id']))->data($data_userlist)->save();
         
                return array('status' => true, 'card_id' => $user_card['card_id']);
            }
            $data['card_id'] = $card['card_id'];
            $data['mer_id'] = $mer_id;
            $data['uid'] = $uid;
            $data['card_money_give'] = $card['begin_money'];
            $data['card_score'] = $card['begin_score'];
            $data['add_time'] = time();
            $data['status'] = 1;
            $data['wx_card_code'] = $wxcardcode;
            if ($card_id = M('Card_userlist')->add($data)) {
                if ($card['begin_money'] > 0 || $card['begin_score'] > 0) {
                    $date_row['card_id'] = $card_id;
                    $date_row['type'] = 1;
                    $date_row['money_add'] = $card['begin_money'];
                    $date_row['score_add'] = $card['begin_score'];
                    $date_row['order_type'] = 20;
                    $date_row['desc'] = "【{$shop_name}】微信商圈支付积分";
                    D('Card_new')->add_row($date_row);
                }

                if ($amount > 0) {
                    $date_row['card_id'] = $card_id;
                    $date_row['type'] = 1;
                    $date_row['score_add'] = $amount;
                    $date_row['desc'] = "【{$shop_name}】微信商圈支付积分";
                    $date_row['order_type'] = 20;
                    D('Card_new')->add_row($date_row);
                }

                if ($now_user = M('User')->where(array('uid' => $uid))->find()) {
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->find();
                    $model->sendTempMsg('OPENTM200964573', array('href' => C('config.site_url') . '/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id=' . $mer_id, 'wecha_id' => $now_user['openid'], 'first' => L_('您成功领取了商家【X1】的会员卡', $now_merchant['name']), 'keyword1' => $card_id, 'keyword2' => $now_user['nickname'], 'keyword3' => $now_user['phone'], 'keyword4' => date("Y年m月d日 H:i"), 'remark' => L_('感谢您的关注！')), $now_merchant['mer_id']);
                }
                return array('status' => true, 'card_id' => $card_id);
            } else {
                return array('status' => false);
            }
        } else {
            fdump_sql('未查询到商家' . $mer_id . '的会员卡', "wxapp_circle_pay_after");
            return array('status' => false);
        }
    }

}