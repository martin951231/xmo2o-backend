<?php

class Weixinh5
{

    protected $order_info;

    protected $pay_money;

    protected $pay_type;

    protected $is_mobile;

    protected $user_info;

    protected $pay_config;

    protected $notify_url;

    protected $scene_info;

    protected $sign;

    protected $pay_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    public function __construct($order_info=array(),$pay_money=0,$pay_type='weixinh5',$pay_config=array(),$user_info=array(),$is_mobile=0)
    {
        $this->order_info = $order_info;
        $this->pay_money  = $pay_money;
        $this->pay_type   = $pay_type;
        $this->is_mobile   = $is_mobile;
        $this->pay_config = $pay_config;
        $this->user_info  = $user_info;
        // $this->notify_url = C('config.site_url').'/source/web_weixinh5_notice.php';
        $this->notify_url = C('config.site_url').'/source/wap_weixin_notice.php';
        $this->scene_info = '{"h5_info":{"type":"Wap","wap_name":"H5支付"}}';
        $this->order_info = $order_info;
    }

    public function pay($ticket=null, $deviceId=null){
        if(empty($this->pay_config['pay_weixinh5_appid']) || empty($this->pay_config['pay_weixinh5_mchid']) || empty($this->pay_config['pay_weixinh5_key']) || empty($this->pay_config['pay_weixinh5_appsecret'])){
            return array('error'=>1,'msg'=>'微信支付缺少配置信息！请联系管理员处理或选择其他支付方式。');
        }
        return $this->mobile_pay($ticket, $deviceId);
    }


    /**
     * 微信H5下单付款
     *   @order 付款信息
     *   @bodys 付款内容
     * */
    function mobile_pay(){
        $url = $this->pay_url;//微信传参地址
        //1.获取调用统一下单接口所需必备参数
        $appid = $this->pay_config['pay_weixinh5_appid'];//微信公众号appid
        $mch_id = $this->pay_config['pay_weixinh5_mchid'];//微信支付商户号
        $key = $this->pay_config['pay_weixinh5_key'];//自己设置的微信商家key
        $out_trade_no = $this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ? '_1' : '');//平台内部订单号
        $nonce_str= MD5($out_trade_no);//随机字符串
        $body = $this->user_info['name'] ? : '订单内容';//付款内容

        $order_amount = $this->pay_money > 0 ? $this->pay_money : 0;//商品的总价 + 商品总运费 请传值前先行处理

        if($this->is_mobile == '2'){
            $this->notify_url =  C('config.site_url').'/source/web_weixinh5_notice.php';
        }

        $total_fee = $order_amount*100;//付款金额，单位为分
        $spbill_create_ip = get_client_ip(); //获得用户设备IP
        $attach = 'weixinh5';//附加数据（自定义，在支付通知中原样返回）
        $notify_url = $this->notify_url; //异步回调地址，需外网可以直接访问
        $trade_type = 'MWEB';//交易类型，微信H5支付时固定为MWEB
        $scene_info = $this->scene_info;//场景信息
        //2.将参数按照key=value的格式，并按照参数名ASCII字典序排序生成字符串
        //3.拼接字符串
        $param = [
            'appid' => $appid,
            'mch_id' => $mch_id,
            'nonce_str' => $nonce_str,
            'body' => $body,
            'out_trade_no' => $out_trade_no,
            'total_fee' => $total_fee,
            'spbill_create_ip' => $spbill_create_ip,
            'notify_url' => $notify_url,
            'trade_type' => $trade_type,
            'scene_info' => $scene_info
        ];
        $param['sign'] = $this->getSign($param);

        $xml = $this->toXml($param);
        import('ORG.Net.Http');
        $http = new Http();
        $return = Http::curlPostXml($url, $xml);
        //6.以POST方式向微信传参，并取得微信返回的支付参数
        fdump($return, "lumin0120", 1);
        if($return['return_code'] == 'SUCCESS' && $return['result_code'] == 'SUCCESS'){
            return ['error' => 0,'url' => $return['mweb_url']];
        }
        else{
            return ['error' => 1, 'msg'=>$return['return_msg'].$return['err_code_des']];
        }
    }

    private function toXml($arr){
        $xml = '<xml>';
        forEach($arr as $k=>$v){
            $xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
        }
        $xml.='</xml>';
        
        return $xml;
    }

    private function getSign($arr){
        $signPars = "";
        ksort($arr);
        foreach($arr as $k => $v) {
            if("" != $v && "sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $this->pay_config['pay_weixinh5_key'];
    
        return strtoupper(md5($signPars));
    }


    public function return_url(){
        @libxml_disable_entity_loader(true);
        if(empty($this->pay_config['pay_weixinh5_appid']) || empty($this->pay_config['pay_weixinh5_mchid']) || empty($this->pay_config['pay_weixinh5_key']) || empty($this->pay_config['pay_weixinh5_appsecret'])){
            return array('error'=>1,'msg'=>'微信支付缺少配置信息！请联系管理员处理或选择其他支付方式。');
        }
        $xml = file_get_contents("php://input");
        $objectxml = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA); //将微信返回的XML转换成数组
        fdump($objectxml,'weixinh5_notify',true);
        $order_id_arr = explode('_',$objectxml['out_trade_no']);
        $order_param['pay_type'] = 'weixin';
        $order_param['is_mobile'] = $this->is_mobile;
        $order_param['order_type'] = $order_id_arr[0];
        $order_param['order_id'] = $order_id_arr[1];
        $order_param['is_own'] = intval($order_id_arr[2]);
        $order_param['third_id'] = $objectxml['transaction_id'];
        $order_param['pay_money'] = $objectxml['total_fee']/100;
        $order_param['sub_mch_id'] = $objectxml['sub_mch_id'];
        return array('error'=>0,'order_param'=>$order_param);
    }

}
?>