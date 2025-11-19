<?php
/**
 * 抖音支付对接（https://microapp.bytedance.com/docs/zh-CN/mini-app/develop/server/ecpay/introduction/）
 */

namespace app\pay\model\service\channel;

use net\Http;

class DouyinService
{
    const CREATE_ORDER = 'https://developer.toutiao.com/api/apps/ecpay/v1/create_order';

    const CREATE_REFUND = 'https://developer.toutiao.com/api/apps/ecpay/v1/create_refund';

    const QUERY_ORDER = 'https://developer.toutiao.com/api/apps/ecpay/v1/query_order';

    private $agent = '';
    private $config = [];//配置参数

    public function __construct($agent = '', $config = [])
    {
        $agent && $this->agent = $agent;
        $config && $this->config = array_merge($this->config, $config);
    }

    public function pay($order_no, $money, $extra_cfg)
    {

        if (empty($order_no) || empty($money)) {
            throw new \think\Exception("订单ID或支付金额必填！");
        }
        switch ($this->agent) {
            case 'douyin_mini'://微信端
                return $this->miniPay($order_no, $money, $extra_cfg);
                break;
            default:
                throw new \think\Exception("当前端口未对接，不能使用");
                break;
        }
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
            'total_amount' => intval(get_format_number($money * 100)),
            'subject' => $extra_cfg['title'],
            'body' => $extra_cfg['title'],
            'valid_time' => 600,
            'notify_url' => cfg('site_url') . '/v20/public/index.php/pay/douyin_notify',
        ];
        $req['sign'] = $this->sign($req);
        $result = Http::curlPostOwn($url, json_encode($req));
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

    public function refund($order_no, $total_fee, $refund_money, $transaction_no = '')
    {
        $url = self::CREATE_REFUND;
        $refundNo = "sdkphp" . date("YmdHis") . rand(1000, 9999);
        $req = [
            'app_id' => $this->config['pay_miniprogram_appid'],
            'out_order_no' => $order_no,
            'out_refund_no' => $refundNo,
            'reason' => '售后服务',
            'refund_amount' => intval($refund_money),
            'notify_url' => cfg('site_url') . '/v20/public/index.php/pay/douyin_refund_notify',
        ];
        $req['sign'] = $this->sign($req);
        $result = Http::curlPostOwn($url, json_encode($req));
        $res = json_decode($result, true);
        if ($res['err_no'] != 0) {
            throw new \think\Exception($res['err_tips']);
        }
        return ['refund_no' => $refundNo, 'refund_param' => $res];
    }

    public function query($order_no)
    {
        $url = self::QUERY_ORDER;
        $refundNo = "sdkphp" . date("YmdHis") . rand(1000, 9999);
        $req = [
            'app_id' => $this->config['pay_miniprogram_appid'],
            'out_order_no' => $order_no
        ];
        $req['sign'] = $this->sign($req);
        $result = Http::curlPostOwn($url, json_encode($req));
        $res = json_decode($result, true);
        if ($res['err_no'] != 0) {
            throw new \think\Exception($res['err_tips']);
        }
        if ($res['payment_info']['order_status'] == 'SUCCESS') {
            //支付成功
            return [
                'paid_money' => $res['payment_info']['total_fee'],
                'paid_time' => strtotime($res['payment_info']['pay_time']),
                'transaction_no' => $res['payment_info']['channel_gateway_no'],
            ];
        }
    }

    public function notice()
    {
        return [
            'paid_money' => request()->douyin_notify_data['total_amount'],
            'paid_time' => request()->douyin_notify_data['paid_at'],
            'transaction_no' => request()->douyin_notify_data['payment_order_no'],
        ];
    }
}