<?php
/**
 * 农业银行对接
 * 【www.aihorse.net 小红马 农行支付】https://www.tapd.cn/47145533/prong/stories/view/1147145533001015703
 */

namespace app\pay\model\service\channel;

use app\common\model\db\PlatOrder;
use app\community\model\db\HouseAbchinaOrderList;
use app\community\model\db\HouseAbchinaSet;
use app\community\model\db\HouseNewPayOrder;
use think\Exception;

class AbChinaService
{
    //演练环境
//	private $serviceurl='https://enjoy.abchina.com/aladdinInner';
    //生产环境
	private $serviceurl='https://enjoy.abchina.com';
    private $merchant_id = '';//配置参数
    private $merchant_pfx = '';//配置参数
    private $trust_pay_cer = '';//配置参数
    private $prikey = '';//配置参数
    private $propertyId = 0;//物业id

    public function __construct($propertyId=0,$merchantId=0){
        if($propertyId){
            $info = (new HouseAbchinaSet())->where('property_id',$propertyId)->find();
            $this->merchant_id = $info['merchant_id'];
            $this->merchant_pfx = $info['merchant_pfx'] ? $_SERVER['DOCUMENT_ROOT'].$info['merchant_pfx'] : '';
            $this->trust_pay_cer = $info['trust_pay_cer'] ? $_SERVER['DOCUMENT_ROOT'].$info['trust_pay_cer'] : '';
            $this->prikey = $info['abchina_prikey'];
            $this->propertyId = $propertyId;
        }elseif($merchantId){
            $info = (new HouseAbchinaSet())->where('merchant_id',$merchantId)->find();
            $this->merchant_id = $merchantId;
            $this->merchant_pfx = $info['merchant_pfx'] ? $_SERVER['DOCUMENT_ROOT'].$info['merchant_pfx'] : '';
            $this->trust_pay_cer = $info['trust_pay_cer'] ? $_SERVER['DOCUMENT_ROOT'].$info['trust_pay_cer'] : '';
            $this->prikey = $info['abchina_prikey'];
            $this->propertyId = $info['property_id'];
        }
    }
    
    public function getPropertyId()
    {
        return $this->propertyId;
    }

    /**
     * 错误返回
     */
    public function error($msg)
    {
        return [
            'error'=>'1',
            'msg'=>$msg
        ];
    }

    /**
     * 加签名
     */
    public function sign_with_sha1_with_rsa($contentForSign)
    {
        $data = base64_encode($contentForSign);
        $certs=array();
        $filePath = $this->merchant_pfx;
        $keyPass = $this->prikey;//'11111111';
        $pkcs12 = file_get_contents($filePath);
        if(openssl_pkcs12_read($pkcs12,$certs,$keyPass)){
            $privateKey = $certs['pkey'];
//            $publicKey =$certs['cert'];
            $signedMsg = '';
            if(openssl_sign($data,$signedMsg,$privateKey)){
                $signedMsg = base64_encode(($signedMsg));
                return $signedMsg;
            }
            return '';
        }
        return '';
    }

    /**
     * 验签
     * $contentBody 返回报文中加密json字符串
     * $signature 加签字符串
     * 返回值 1：验签成功
     */
    public function read_cer_and_verify_sign($contentBody, $signature)
    {
        $filePath = $this->trust_pay_cer;
//        $filePath = 'D:/nidan/work/pigo2o/upload/abchina/file/TrustPay.cer';
//        $filePath = 'static/resources/certificate/TrustPay.cer';
//        $filePath = 'static/resources/certificate/TrustPayTest.cer';
        $certificateCAcerContent = file_get_contents($filePath);

        $certificateCApemContent = '-----BEGIN CERTIFICATE-----'.PHP_EOL
            .chunk_split(base64_encode($certificateCAcerContent),64,PHP_EOL)
            .'-----END CERTIFICATE-----'.PHP_EOL;
        $key = openssl_get_publickey($certificateCApemContent);

        $ok = (bool)openssl_verify($contentBody,base64_decode($signature),$key);
        openssl_free_key($key);
        return $ok;
    }

    public function getDataAndDetail($url, $body)
    {
        $requestjson = json_encode($body, JSON_UNESCAPED_UNICODE);
        fdump_api(date('Y-m-d H:i:s',time()).$url . "向后台发送的报文加密前为：" . $requestjson,'abchina/getDataAndDetail',1);
//        echo base64_encode($requestjson);
        //       加密，加签名
        $data = $this->sign_with_sha1_with_rsa($requestjson) . "||" . base64_encode($requestjson);
        fdump_api(date('Y-m-d H:i:s',time()).$url . "向后台发送的报文加密后为：" . $data,'abchina/getDataAndDetail',1);
//        echo $data."<br>";

        try {
            $result = $this->doPostStr($url, $data);

            fdump_api(date('Y-m-d H:i:s',time()).$url . "后台获取的报文解密前为：" . $result,'abchina/getDataAndDetail',1);
//      处理响应报文返回结果
            $resultArr = explode('||', $result);

            $signature = $resultArr[0];
            $contentBody = $resultArr[1];
        } catch (Exception $ex) {
            fdump_api(date('Y-m-d H:i:s',time()).$url . "向服务器获取请求报文失败！",'abchina/getDataAndDetail',1);
            return $this->error("向服务器获取请求报文失败！");
        }
//        echo $requestjson;
        $yk = $this->read_cer_and_verify_sign($contentBody, $signature);
        fdump_api(date('Y-m-d H:i:s',time())."验签结果".$yk,'abchina/getDataAndDetail',1);
        if ('1' !== $yk && !$yk) {
            fdump_api(date('Y-m-d H:i:s',time()).$url."获取到的报文验签失败！",'abchina/getDataAndDetail',1);
            return $this->error("验签失败！");
        } else{
            fdump_api(date('Y-m-d H:i:s',time()).$url."获取到的报文验签成功！",'abchina/getDataAndDetail',1);
        }
        $resultJson = base64_decode($contentBody);
        $result = json_decode($resultJson);
        fdump_api(date('Y-m-d H:i:s',time()).$url . "后台获取的报文解密后为：" . $resultJson,'abchina/getDataAndDetail',1);
        return $result;
    }

    public function doPostStr($url, $data)
    {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:text/plain;charset="utf-8"',
                'content' => $data
            )
        );
        fdump_api(date('Y-m-d H:i:s',time()).$url."发送的请求信息为：".$data,'abchina/doPostStr',1);
        //发送请求获取响应结果，一般方法可以在result中直接获取报文
        $content = stream_context_create($options);
        $result = null;
        fdump_api(date('Y-m-d H:i:s',time())." 开始发送请求：".$content,'abchina/doPostStr',1);
        try {

            $result = file((string)$url, FILE_USE_INCLUDE_PATH, $content);
        } catch (Exception $e) {
            return $this->error($url." 请求失败！".$e);
        }
        $output = $result[0];
        fdump_api(date('Y-m-d H:i:s',time()) . $url."响应获取的result：".$output,'abchina/doPostStr',1);
        return $output;
    }

    /**
     * 接收报文获取requestBody和使用base64解析后的requestBody以及缴费中心传送的签名
     *
     */
    public function requestBodyOfBase64($requestContent)
    {
        try {
//            $requestContent = \request()->getInput();
//            $requestContent = "k6lECy5TcFx9SNniGM8xg94ZeocFBOIp8xF1wJg817gcBYuN6UHssjwr0/U5W2D1XZIRXJHQkgfluQ2qzZhDl5eiOyHpNgbxR0I/QYxUokaZy3XnSAjCi+uv6O6gti5MCnFs3ZP1l4cKdJrKMPaZowoQKR0aeUUFc3zWTH3LTcg=||eyJmb3JtYXQiOiJqc29uIiwibWVzc2FnZSI6eyJoZWFkIjp7ImJyYW5jaENvZGUiOiIyMTEwIiwiY2hhbm5lbCI6Ik1CTksiLCJ0aW1lU3RhbXAiOiIyMDE4MDkyMTE1MTg0Nzg2NyIsInRyYW5zQ29kZSI6InF1ZXJ5QmlsbCIsInRyYW5zRmxhZyI6IjAxIiwidHJhbnNTZXFOdW0iOiJCUDE4MDkyMTE1MTg1NzM5MDAwOSJ9LCJpbmZvIjp7ImVwYXlDb2RlIjoiSkYtRVBBWTIwMTgwODAyNjU2MDIiLCJpbnB1dDEiOiIxMjM0NTYiLCJtZXJjaGFudElkIjoiMTAzODgxMTA0NDEwMDAxIiwidHJhY2VObyI6IkpGMTgwOTIxMTUxODU3ODQ4NTkyIiwidXNlcklkIjoiMTYzNzUwNDYwMjk5NDM1NiJ9fX0=";
            fdump_api(date('Y-m-d H:i:s',time())."后台获取的报文解密前为：" . $requestContent,'abchina/requestBodyOfBase64',1);
            $resultArr = explode('||', $requestContent);

            $signatureString = $resultArr[0];
            $requestBody = $resultArr[1];
            $requestBodyOfDecoded = base64_decode($requestBody);

            fdump_api(date('Y-m-d H:i:s',time())."后台获取的报文解密后为：" . $requestBodyOfDecoded,'abchina/requestBodyOfBase64',1);

            //使用base64解析完成后的requestBody
            $requestMap['requestBodyOfDecoded'] = json_decode($requestBodyOfDecoded);
            //解析前的requestBody
            $requestMap['requestBody'] = $requestBody;
            //获取缴费中心传送过来的签名
            $requestMap['signatureString'] = $signatureString;
            return $requestMap;

        } catch (Exception $ex) {
            return $this->error("向服务器获取请求报文失败！");
        }

    }
    
    /**
     * 查询单笔流水账单
     */
    public function confirmTrace($data)
    {
        $url = $this->serviceurl.'/jf-openapiweb/bridgeTrans/confirmTrace';
        $merchantId = $data['merchantId'];
        $traceNo = $data['traceNo'];
        $timestamp = $data['timeStamp'];
        $format = "json";
        $info = [
            'merchantId'=>$merchantId,
            'traceNo'=>$traceNo,
        ];
        $head = [
            'timeStamp'=>$timestamp,//时间戳,格式 yyyyMMddHHmmssSSS，
            'transCode'=>'confirmTrace',
            'transFlag'=>'01',
            'transSeqNum'=>"BRIDGE$timestamp$merchantId",
        ];

        $responseBody['format'] = $format;
        $responseBody['message'] = [
            'info'=>(array)$info,
            'head'=>(array)$head
        ];
        //      将数据发送到后端进行处理，返回响应结果
        $result = $this->getDataAndDetail($url,$responseBody);
        if(is_array($result) && isset($result['error']) && $result['error']){
            return $result;
        }

        $result = get_object_vars($result);
        return $this->object_to_array($result);
    }

    /**
     * 退款
     */
    public function refund($order_no, $total_fee, $refund_money, $transaction_no = '')
    {
        //获取农行缴费订单信息
        $platOrder = (new PlatOrder())->where('orderid',$order_no)->field('business_type,business_id')->find();
        if(!$platOrder || $platOrder['business_type']!=='village_new_pay'){
            return false;
        }
        $houseNewPayOrder = (new HouseNewPayOrder())->where('summary_id',$platOrder['business_id'])->field('order_type,order_id,property_id')->find();
        if(!$houseNewPayOrder || !$houseNewPayOrder['order_type'] || !$houseNewPayOrder['order_id'] || !$houseNewPayOrder['property_id']){
            return false;
        }
        $abChinaOrder = (new HouseAbchinaOrderList())->where(['pay_order_type'=>$houseNewPayOrder['order_type'],'pay_order_id'=>$houseNewPayOrder['order_id']])->field('query_content,notify_content,auto_query_content,trace_no')->find();
        if(!$abChinaOrder || (!$abChinaOrder['notify_content'] && !$abChinaOrder['auto_query_content']) || !$abChinaOrder['trace_no'] || !$abChinaOrder['query_content']){
            return false;
        }
        
        //获取物业配置信息
        $info = (new HouseAbchinaSet())->where('property_id',$houseNewPayOrder['property_id'])->find();
        $this->merchant_id = $info['merchant_id'];
        $this->merchant_pfx = $info['merchant_pfx'] ? $_SERVER['DOCUMENT_ROOT'].$info['merchant_pfx'] : '';
        $this->trust_pay_cer = $info['trust_pay_cer'] ? $_SERVER['DOCUMENT_ROOT'].$info['trust_pay_cer'] : '';
        $this->prikey = $info['abchina_prikey'];
        $this->propertyId = $houseNewPayOrder['property_id'];
        
        $queryContent = $abChinaOrder['query_content'] ? json_decode($abChinaOrder['query_content'],true) : [];
        $notifyContent = $abChinaOrder['notify_content'] ? json_decode($abChinaOrder['notify_content'],true) : [];
        $autoQueryContent = $abChinaOrder['auto_query_content'] ? json_decode($abChinaOrder['auto_query_content'],true) : [];
        if($notifyContent){
            $merchantId = $notifyContent['message']['info']['merchantId'];
//            $amtRefund = $notifyContent['message']['info']['payBillAmt'];
        }else{
            $merchantId = $autoQueryContent['message']['info']['merchantId'];
//            $amtRefund = $notifyContent['message']['info']['amtEpay'];
        }
        $branchCode = $queryContent['message']['head']['branchCode'];
        
        $result = $this->refundTrace([
            'merchantId'=>$merchantId,
            'traceNo'=>$abChinaOrder['trace_no'],
            'timeStamp'=>(new \app\community\model\service\AbChinaService())->getTimeStamp('YmdHisu'),
            'amtRefund'=>$refund_money,
            'branchCode'=>$branchCode,
        ]);
        if(isset($result['message']['info']['refundNo']) && $result['message']['info']['refundNo']){
            return [
                'refund_no'=>$result['message']['info']['refundNo'],
            ];
        }
        return $result;
    }
    /**
     * 请求农行单笔流水退款
     */
    public function refundTrace($data)
    {
        $url = $this->serviceurl.'/jf-openapiweb/bridgeTrans/refundTrace';
        $merchantId = $data['merchantId'];
        $traceNo = $data['traceNo'];
        $timestamp = $data['timeStamp'];
        $amtRefund = $data['amtRefund'];
        $branchCode = $data['branchCode'];
        $format = "json";
        $info = [
            'amtRefund'=>$amtRefund,
            'merchantId'=>$merchantId,
            'traceNo'=>$traceNo,
        ];
        $head = [
            'branchCode'=>$branchCode,
            'timeStamp'=>$timestamp,//时间戳,格式 yyyyMMddHHmmssSSS，
            'transCode'=>'refundTrace',
            'transFlag'=>'01',
            'transSeqNum'=>"BRIDGE$timestamp$merchantId",
        ];

        $responseBody['format'] = $format;
        $responseBody['message'] = [
            'info'=>(array)$info,
            'head'=>(array)$head
        ];
        //      将数据发送到后端进行处理，返回响应结果
        $result = $this->getDataAndDetail($url,$responseBody);
        if(is_array($result) && isset($result['error']) && $result['error']){
            return $result;
        }

        $result = get_object_vars($result);
        return $this->object_to_array($result);
    }
    
    public function object_to_array($obj){
        $obj = (array)$obj;
        foreach ($obj as $k => $v) {
            if (gettype($v) == 'resourse'){
                return;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array'){
                $obj[$k] = (array)self::object_to_array($v);
            }
        }

        return $obj;
    }
}