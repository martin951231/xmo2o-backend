<?php

namespace app\pay\model\service\channel;

use app\pay\model\service\libs\icbc\DefaultIcbcClient;
use app\pay\model\service\libs\icbc\IcbcConstants;
use app\pay\model\service\libs\icbc\IcbcEncrypt;
use app\pay\model\service\libs\icbc\UiIcbcClient;
use app\pay\model\service\PayService;
use Exception;
use think\facade\Cache;

class IcbcService
{
    private $agent = '';
    private $config = []; //配置参数

    public function __construct($agent = '', $config = [])
    {
        $this->agent = $agent;
        $this->config = $config;

        //测试配置
        // $this->config = [
        //     'pay_icbc_gateway' => 'https://apipcs3.dccnet.com.cn/',
        //     'pay_icbc_app_id' => '10000000000000197550',
        //     'pay_icbc_private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCF7eCVTURFK5+gpeaQios8lw6uTcltPalYM6/VNfNDAirGnpFP5RQNI7duf5nr+3QLe1FNMbaR6Cl1BeWcaFzYqba4UTjNrttGf9E2LBnVa7sR0sVWX/1o71nIou21+Wh044zqcSl1T6FEBpSP70fz5ahytU41hVy6lyJymDhyWzZepr4Yqnrs69GB6V/v2DDuX8t4tpZAK1cxsOSrBJHOYxUoTomLb9dX8dFI/4AIShjKv4hmwryviHJRlZSiyV8j72FgOUs7BmMz+KCaKGVGo1G+YrppnX5IruNvaXDB59bcu3lsjPODUkfMKsMr+Vrg4ipDEboCCtKuaPrZ2xMNAgMBAAECggEAY3qQcXWl+xbvB52Sx9QQnh25yuB+eETvz1L9DQp4uVIXwdYwKz8FiMj5y/q9stnglVKwzfzaFkBy4rhRl76xEEHnNbsmzShPOWthU7KoMa1Gk3OSHplIGjSG5Q+YL62k2AXZOp55Y+iU6XlVyh+6uz/fwexHvltjyDjQXnwQmWdvUHM+cy7yTxlfz8FGecZOyRY2jRUJZ7w0WfUx0p1R0vWRRpjRddMDyu7sjtkTQ54KfmqE5HQWKrQVDio2hSw7EbaylAo6HSZO8i2ZSW3neKenvV2YCUVk6AbhfJj/SfTzqzEePduOv5bUq/vaTRCWAImcHnohLhzQQS5zn3jDpQKBgQDTncyUAAOL275viu9Xbk2IBsv/tyFaNji/G8BdE0jB++7x7J3EtLRJIBOJE16vvWUJfD7CXvezqArZWLADt7lholdPuUKlEOJtXvqlYvg7zKDce+wA7HAK0a2sMSvU51nC0q4LLaI+NlGxZZcPYcoj6PYLXw/BHaqGAHm5Y+RMhwKBgQCiBNz3zYP4RnfBTazWBRm3P7jybcteFMh5inmOyw9mfpphIUpFlw9lRbt7J2+sauzlIKZ2d9MOoZIurAZ/zbgIf2msTEoyczzO6yUpyeKinl6HjizMKggLEponU7nk2nQsuVbf7wYoHPwwjT+a5k9R+PMrYVO0B74Z8PIQpHd8ywKBgQCD//qMlzWfGANCMLf7IaLbJuI5MFJto0TicL0dUdogprX10lLXUKDkvE1zQ9HcbZeIwyeitvpr5nZd/SROLVxFuq4b3MPlkxKqhoRyhbmyE4KEBgjAyrtZBFIBBusAcw9ap3BJKL37cIak0WnY/nbIz5gqn7GEFK05yTIkolrWIQKBgH2U42m4BKi2hjBw8pzZSzt8yvAuJkoANfvvV5VU09UQIL1Uvmr+UdKfoFgvEyJDpudazWxaKF/Y4KQIduktHY6Io/IHGCPOqr2ACkg/0clWk9LiYtrFYYKjopUFErvOj+nypuHgNZd2EAvaEAkSpzzUiDeBsw2CStl0p0w7DtrNAoGAIsqskDGGewcgBWQmXvSqa7o0do3coiH3ToMAQm9vlGgHAAw5xlgfVQpGwe8GR4ilysh+5EuiuLnINDt0tXpv9K7dAQgjKAmszFrOjmyBMnSDebfysynABFj3c8GglO+l9J+ljFMqgqPVzsLAgIItciyq80Xmv8TjpbwFM2v34lI=',
        //     'pay_icbc_gateway_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwFgHD4kzEVPdOj03ctKM7KV+16bWZ5BMNgvEeuEQwfQYkRVwI9HFOGkwNTMn5hiJXHnlXYCX+zp5r6R52MY0O7BsTCLT7aHaxsANsvI9ABGx3OaTVlPB59M6GPbJh0uXvio0m1r/lTW3Z60RU6Q3oid/rNhP3CiNgg0W6O3AGqwIDAQAB'
        // ];
    }

    public function pay($order_no, $money)
    {
        $request = array(
            "serviceUrl" => $this->config['pay_icbc_gateway'] . 'ui/jft/ui/pay/h5/V3',
            "method" => 'POST',
            "isNeedEncrypt" => false,
            "extraParams" => array( //其他参数，用array
            ),
            "biz_content" => array(
                "appId" => $this->config['pay_icbc_app_id'],
                "outOrderId" => $order_no,
                "outVendorId" => (string)$this->config['sub_merid'],
                "outUserId" => (string)request()->log_uid,
                "payAmount" => $money,
                "payType" => "01",
                "notifyUrl" => cfg('site_url') . "/v20/public/index.php/pay/Notify/icbc_pay_notify",
                "jumpUrl" => cfg('site_url') . "/v20/public/index.php/pay/Notify/icbc_pay_notify",
                "goodsName" => $this->config['title'],
                "trxIp" => request()->ip(),
                "trxChannel" => "03"
            )
        );
        $client = new UiIcbcClient(
            $this->config['pay_icbc_app_id'],
            $this->config['pay_icbc_private_key'],
            IcbcConstants::$SIGN_TYPE_RSA2,
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        );

        $url = $client->buildPostForm($request, 'msgId', '', false);
        //工行是表单提交触发支付，所以这里缓存下，通过中转页面读取
        Cache::set($order_no, $url, 60);
        return ['url' => cfg('site_url') . '/v20/public/index.php/pay/Pay/icbc?order_no=' . $order_no, 'type' => 'redirect', 'env' => $this->agent];
    }

    public function notice()
    {
        return [
            'paid_money' => request()->icbc_notify_data['payAmount'],
            'paid_time' => strtotime(request()->icbc_notify_data['payCompleteDate'] . ' ' . request()->icbc_notify_data['payCompleteTime']),
            'transaction_no' => request()->icbc_notify_data['jOrderId']
        ];
    }

    public function query($order_no)
    {

        $request = array(
            "serviceUrl" => $this->config['pay_icbc_gateway'] . 'api/jft/api/pay/query/order/V1',
            "method" => 'POST',
            "isNeedEncrypt" => false,
            "extraParams" => array( //其他参数，用array
            ),
            "biz_content" => array(
                "appId" => $this->config['pay_icbc_app_id'],
                "outOrderId" => $order_no,
                "outVendorId" => (string)$this->config['sub_merid'],
            )
        );
        $client = new DefaultIcbcClient(
            $this->config['pay_icbc_app_id'],
            $this->config['pay_icbc_private_key'],
            IcbcConstants::$SIGN_TYPE_RSA2,
            '',
            '',
            $this->config['pay_icbc_gateway_public_key'],
            '',
            '',
            '',
            ''
        );
        $resp = $client->execute($request, 'msgId', '', false);
        $respObj = json_decode($resp, true);
        if ($respObj["return_code"] == 0 && $respObj['orderStatus'] == '02') { //支付成功
            dd([
                'status' => 1,
                'paid_money' => $respObj['payAmount'],
                'paid_time' => strtotime($respObj['payCompleteDate'] . ' ' . $respObj['payCompleteTime']),
                'transaction_no' => $respObj['jOrderId']
            ]);
            return [
                'status' => 1,
                'paid_money' => $respObj['payAmount'],
                'paid_time' => strtotime($respObj['payCompleteDate'] . ' ' . $respObj['payCompleteTime']),
                'transaction_no' => $respObj['jOrderId']
            ];
        } else { //失败
            throw new Exception($respObj["return_msg"]);
        }
    }

    public function refund($order_no, $total_fee, $refund_money, $transaction_no = '')
    {
        $businessOrder = (new PayService())->getBusinessOrderInfoByPayOrderid($order_no);

        $request = array(
            "serviceUrl" => $this->config['pay_icbc_gateway'] . 'api/jft/api/pay/refund/accept/V1',
            "method" => 'POST',
            "isNeedEncrypt" => false,
            "extraParams" => array( //其他参数，用array
            ),
            "biz_content" => array(
                "appId" => $this->config['pay_icbc_app_id'],
                "vendorId" => (string)$this->config['sub_merid'],
                "userId" => (string)$businessOrder['uid'],
                "payType" => "01",
                "orderId" => $order_no,
                "refundId" => "R" . $order_no,
                "refundAmount" => (string)$refund_money,
                "notifyUrl" => cfg('site_url') . "/v20/public/index.php/pay/Notify/icbc_refund_notify",

            )
        );
        $client = new DefaultIcbcClient(
            $this->config['pay_icbc_app_id'],
            $this->config['pay_icbc_private_key'],
            IcbcConstants::$SIGN_TYPE_RSA2,
            '',
            '',
            $this->config['pay_icbc_gateway_public_key'],
            '',
            '',
            '',
            ''
        );
        $resp = $client->execute($request, 'msgId', '', false);
        $respObj = json_decode($resp, true);
        if ($respObj["return_code"] == 0) { //退款成功
            return [
                'refund_no' => $request['biz_content']['refundId']
            ];
        } else { //失败
            throw new Exception($respObj["return_msg"]);
        }
    }
}
