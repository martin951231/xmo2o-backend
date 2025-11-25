<?php
/**
 * 抖音支付
 */

class Douyin
{
    const CREATE_ORDER = 'https://developer.toutiao.com/api/apps/ecpay/v1/create_order';

    const CREATE_REFUND = 'https://developer.toutiao.com/api/apps/ecpay/v1/create_refund';

    const QUERY_ORDER = 'https://developer.toutiao.com/api/apps/ecpay/v1/query_order';

    private $config = [];//配置参数

    public function __construct($order_info, $pay_money, $pay_type, $pay_config, $user_info=[], $is_mobile = 0)
    {
        $this->config = [
            'pay_slat' => C('config.pay_slat'),
            'pay_miniprogram_appid' => C('config.pay_miniprogram_appid'),
        ];
        $this->order_info = $order_info;
        $this->pay_money = $pay_money;
        $this->pay_type = $pay_type;
        $this->is_mobile = $is_mobile;
        $this->pay_config = $pay_config;
        $this->user_info = $user_info;
    }

    public function sign($map)
    {
        $rList = array();
        $salt = $this->config['pay_slat'];
        foreach ($map as $k => $v) {
            if ($k == "other_settle_params" || $k == "app_id" || $k == "sign" || $k == "thirdparty_id")
                continue;
            $value = trim(strval($v));
            $len = strlen($value);
            if ($len > 1 && substr($value, 0, 1) == "\"" && substr($value, $len, $len - 1) == "\"")
                $value = substr($value, 1, $len - 1);
            $value = trim($value);
            if ($value == "" || $value == "null")
                continue;
            array_push($rList, $value);
        }
        array_push($rList, $salt);
        sort($rList, 2);
        return md5(implode('&', $rList));
    }

    //小程序微信支付
    //微信小程序支付(还没通哦,最好对照着上面的方法重写一下)
    public function miniPay($order_no, $money, $extra_cfg)
    {
        $url = self::CREATE_ORDER;
        $req = [
            'app_id' => $this->config['pay_miniprogram_appid'],
            'out_order_no' => $order_no,
            'total_amount' => intval(getFormatNumber($money * 100)),
            'subject' => $extra_cfg['subject'],
            'body' => $extra_cfg['body'],
            'valid_time' => 600,
            'notify_url' => C('config.site_url') . '/source/douyin_notify.php',
        ];
        $req['sign'] = $this->sign($req);
        $result = httpRequest($url, 'post', json_encode($req))[1];
        $res = json_decode($result, true);
        if ($res['err_no'] != 0) {
            throw new \think\Exception($res['err_tips']);
        }
        return [
            'error' => '0',
            'type' => 'douyin_mini',
            'info' => $res['data']
        ];
    }

    public function refund()
    {
        $url = self::CREATE_REFUND;
        $refundNo = "sdkphp" . date("YmdHis") . rand(1000, 9999);
        $req = [
            'app_id' => $this->config['pay_miniprogram_appid'],
            'out_order_no' => ($this->order_info['order_type'] ? $this->order_info['order_type'] . '_' : '') . $this->order_info['orderid'],
            'out_refund_no' => $refundNo,
            'reason' => '售后服务',
            'refund_amount' => intval($this->pay_money * 100),
            'notify_url' => C('config.site_url') . '/source/douyin_refund_notify.php',
        ];
        $req['sign'] = $this->sign($req);
        $result = httpRequest($url, 'post', json_encode($req))[1];
        $res = json_decode($result, true);
        if ($res['err_no'] != 0) {
            throw new Exception($res['err_tips']);
        }
        return ['error' => 0, 'type' => 'ok', 'msg' => L_('退款申请成功！。'), 'refund_param' => $res];
    }

    public function notice_url()
    {
        fdump_sql([file_get_contents("php://input"), $_REQUEST], 'douyinNotify');
        $data = file_get_contents("php://input");
        //$data = '{"msg":"{\\"appid\\":\\"tt0ed6786b91efc35201\\",\\"cp_orderno\\":\\"22051217305631123456814\\",\\"cp_extra\\":\\"\\",\\"way\\":\\"2\\",\\"channel_no\\":\\"2022051222001425901406575468\\",\\"channel_gateway_no\\":\\"\\",\\"payment_order_no\\":\\"PCP2022051216012009070392452410\\",\\"out_channel_order_no\\":\\"2022051222001425901406575468\\",\\"total_amount\\":1,\\"status\\":\\"SUCCESS\\",\\"seller_uid\\":\\"70295871058272606800\\",\\"extra\\":\\"\\",\\"item_id\\":\\"\\",\\"paid_at\\":1652342491,\\"message\\":\\"\\",\\"order_id\\":\\"7096756467956713765\\"}","msg_signature":"d447e44e75e964b09832397d88f4d97ad787b10d","nonce":"8320","timestamp":"1652342492","type":"payment"}';
        $data = json_decode($data, true);
        $notifyData = json_decode($data['msg'], true);
        $cpOrderNo = $notifyData['cp_orderno'] ?? '';
        $arr = explode('_', $cpOrderNo);
        $payOrderid = count($arr) > 1 ? $arr[1] : $arr[0];

        $orderInfo = M('Payment_money_order')->where(['orderid' => $payOrderid])->find();
        if (empty($orderInfo)) {
            return false;
        }
        $payMoney = getFormatNumber($notifyData['total_amount'] / 100);
        M('Payment_money_order')->where(['orderid' => $payOrderid])->save(['paid_money' => $payMoney]);
        $res = [
            'order_id' => $payOrderid,
            'pay_type' => $orderInfo['pay_type'],
            'is_mobile' => $orderInfo['is_mobile_pay'],
            'order_from' => 1,
            'order_type' => $orderInfo['order_type'] ?: 'shop',
            'is_own' => 0,
            'third_id' => $notifyData['payment_order_no'],
            'pay_money' => $payMoney,
            'env' => $notifyData['way']
        ];
        if ($res) {
            D('Pay')->after_pay($res['order_type'], ['order_param' => $res], false);
            echo json_encode(['err_no' => 0, 'err_tips' => 'success']);
            exit;
        }
        return true;
    }
}