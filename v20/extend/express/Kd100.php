<?php
/*
 * 快递100电子面单
 * Created by vscode.
 * Author: 钱大双
 * Date Time: 2020年10月13日14:59:48
 */

namespace express;
class Kd100
{
    public $appkey;        //客户授权key
    public $appsecret;    //授权secret
    public $customer;    //公司编号
    public $state;      //运单签收状态服务说明

    public function __construct()
    {
        $this->state = [
            0 => '快件处于运输过程中',
            1 => '快件已由快递公司揽收',
            2 => '快递100无法解析的状态，或者是需要人工介入的状态， 比方说收件人电话错误。',
            3 => '正常签收',
            4 => '货物退回发货人并签收',
            5 => '货物正在进行派件',
            6 => '货物正处于返回发货人的途中',
            7 => '货物转给其他快递公司邮寄',
            10 => '货物等待清关',
            11 => '货物正在清关流程中',
            12 => '货物已完成清关流程',
            13 => '货物在清关过程中出现异常',
            14 => '收件人明确拒收',
        ];
    }

    //电子面单打印
    public function print_task($order_data)
    {
        $this->appkey = cfg('kd100_appkey');
        if (empty($this->appkey)) {
            return ['status' => 1, 'msg' => '快递100appkey没有配置'];
        }
        $this->appsecret = cfg('kd100_appsecret');
        if (empty($this->appsecret)) {
            return ['status' => 1, 'msg' => '快递100appsecret没有配置'];
        }
        list($msec, $sec) = explode(' ', microtime());
        $t = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);    //当前时间戳
        $site_url = cfg('site_url');

        $param = array(
            'type' => '10',                    //业务类型，默认为10
            'partnerId' => $order_data['partner_id'],        //电子面单客户账户或月结账号
            'kuaidicom' => $order_data['kuaidicom'],                 //快递公司的编码
            'recMan' => array(
                'name' => $order_data['rec_name'],                //收件人姓名
                'mobile' => $order_data['rec_mobile'],              //收件人手机
                'printAddr' => $order_data['rec_address'],             //收件人地址
            ),
            'sendMan' => array(
                'name' => $order_data['send_name'],                //寄件人姓名
                'mobile' => $order_data['send_mobile'],                //寄件人手机
                'printAddr' => $order_data['send_address'],             //寄件人地址
            ),
            'cargo' => $order_data['cargo'],                     //物品名称
            'count' => $order_data['count'],                     //物品总数量
            'payType' => 'SHIPPER',            //支付方式： SHIPPER：寄方付（默认） CONSIGNEE：到付 MONTHLY：月结 THIRDPARTY：第三方支付 （详细请参考参数字典）
            'expType' => '标准快递',           //快递类型: 标准快递（默认）、顺丰特惠、EMS经济
            'remark' => $order_data['remark'],                    //备注
            'tempid' => $order_data['tempid'],                    //电子面单模板编码
            'siid' => $order_data['siid'],                       //设备编码
            'callBackUrl' => $site_url . '/v20/public/index.php/mall/api.home/singleFaceCallBack'//打印结果回调

        );

        $post_data = array();
        $post_data["param"] = json_encode($param, JSON_UNESCAPED_UNICODE);
        $post_data["key"] = $this->appkey;
        $post_data["t"] = $t;
        $sign = md5($post_data["param"] . $t . $this->appkey . $this->appsecret);
        $post_data["sign"] = strtoupper($sign);

        $url = 'http://poll.kuaidi100.com/printapi/printtask.do?method=eOrder';    //电子面单打印请求地址
        $response = http_request($url, 'post', $post_data);
        if ($response[0] == 200) {
            $return = array_merge(json_decode($response[1], true), ['status' => 0]);
        } else {
            $return = ['status' => 1, 'msg' => '快递100接口请求失败'];
        }
        return $return;
    }

    //电子面单复打
    public function repeat_task($taskId)
    {
        $this->appkey = cfg('kd100_appkey');
        if (empty($this->appkey)) {
            return ['status' => 1, 'msg' => '快递100appkey没有配置'];
        }
        $this->appsecret = cfg('kd100_appsecret');
        if (empty($this->appsecret)) {
            return ['status' => 1, 'msg' => '快递100appsecret没有配置'];
        }
        list($msec, $sec) = explode(' ', microtime());
        $t = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);    //当前时间戳

        $param = array(
            'taskId' => $taskId,        //任务ID
        );

        $post_data = array();
        $post_data["param"] = json_encode($param, JSON_UNESCAPED_UNICODE);
        $post_data["key"] = $this->appkey;
        $post_data["t"] = $t;
        $sign = md5($post_data["param"] . $t . $this->appkey . $this->appsecret);
        $post_data["sign"] = strtoupper($sign);

        $url = 'http://poll.kuaidi100.com/printapi/printtask.do?method=printOld';    //电子面单复打请求地址
        $response = http_request($url, 'post', $post_data);
        if ($response[0] == 200) {
            $return = array_merge(json_decode($response[1], true), ['status' => 0]);
        } else {
            $return = ['status' => 1, 'msg' => '快递100接口请求失败'];
        }
        return $return;
    }

    //物流轨迹
    public function logistics_query($data)
    {
        $this->appkey = cfg('kd100_appkey');
        if (empty($this->appkey)) {
            return ['status' => 1, 'msg' => '快递100appkey没有配置'];
        }
        $this->customer = cfg('kd100_appcustomer');
        if (empty($this->customer)) {
            return ['status' => 1, 'msg' => '快递100appcustomer没有配置'];
        }

        $param = array(
            'com' => $data['express_name'],     //快递公司编码
            'num' => $data['express_num'],     //快递单号
            'phone' => $data['phone'],         //收、寄件人的电话号码（手机和固定电话均可，只能填写一个，顺丰单号必填，其他快递公司选填。）
        );

        //请求参数
        $post_data = array();
        $post_data["customer"] = $this->customer;
        $post_data["param"] = json_encode($param);
        $sign = md5($post_data["param"] . $this->appkey . $post_data["customer"]);
        $post_data["sign"] = strtoupper($sign);

        $url = 'http://poll.kuaidi100.com/poll/query.do';    //实时查询请求地址

        $params = "";
        foreach ($post_data as $k => $v) {
            $params .= "$k=" . urlencode($v) . "&";              //默认UTF-8编码格式
        }
        $post_data = substr($params, 0, -1);
        $response = http_request($url, 'post', $post_data);
        fdump_api([$url, $post_data, $response], 'kd100', 1);
        if ($response[0] == 200) {
            $response_data = json_decode($response[1], true);
            if (isset($response_data['result']) && empty($response_data['result'])) {
                $message = $response_data['message'];//错误返回说明
                $data = [
                    'state' => '',
                    'stateMessage' => $message,
                    'data' => [],//物流信息
                ];
            } else {
                $data = [
                    'state' => isset($response_data['state']) ? $response_data['state'] : '',//快递单当前状态值
                    'stateMessage' => (isset($response_data['state']) && isset($this->state[$response_data['state']])) ? $this->state[$response_data['state']] : '',//快递单当前状态说明
                    'data' => isset($response_data['data']) ? $response_data['data'] : [],//物流信息
                ];
            }
            $return = ['status' => 0, 'data' => $data];
        } else {
            //屏蔽报错优化用户体验，查询不到返回空就行
            $data = [
                'state' => '',
                'stateMessage' => '',
                'data' => [], //物流信息
            ];
            $return = ['status' => 0, 'data' => $data];
        }
        return $return;
    }
}