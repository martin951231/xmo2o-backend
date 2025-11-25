<?php
/**
 * 建行app支付
 */

namespace app\pay\model\service\channel;

use app\common\model\service\CacheSqlService;
use app\common\model\service\ConfigService;
use app\community\model\db\ConstructionOrderList;
use app\pay\model\service\PayService;
use net\Http;
use sm4\SM4;

class ConstructionAppService
{
    private $agent = '';
//    private $commonUrl = 'http://124.127.94.57:8006/Adapter/lcs/';
//    private $payUrl = 'http://124.127.94.57:8003/order/orderPay_branch_H5.jhtml';
//    private $paySdkUrl = 'http://124.127.94.57:8003/order/orderPay_branch_sdk.jhtml';
//    private $outLinkPayUrl = 'http://124.127.94.57:8003/e_report/outlink/index.html';
    private $commonUrl = 'https://interface.cloud.ccb.com/Adapter/lcs/';
    private $payUrl = 'https://cloud.life.ccb.com/order/orderPay_branch_H5.jhtml';
    private $paySdkUrl = 'https://cloud.life.ccb.com/order/orderPay_branch_sdk.jhtml';
    private $config = [];//配置参数
    private $baseData = [];//通用报文数据
    private $key = '';//desKey
    private $md5Key = '';//md5Key
    private $sm4Key = '';//md5Key
    private $linkId = 'e6cedbc98eb04a078d39ef31c2310793';//app支付

    public function __construct($agent = '', $config = [])
    {
        $this->sm4Key = $this->md5Key = '';
        $this->agent = $agent;
        $this->config = $config;
        $this->baseData = [
            'thirdSysID' => $config['third_sys_id'] ?? '',
            'txCode' => '',
            'data' => [],
            'auth' => '',
            'secID' => '000021',//data字段sm4加密传输，且验签
            'transID' => '',
        ];
        $nowSm4Key = cfg('construction_sm4_key');
        $nowMd5Key = cfg('construction_md5_key');
        $nowKeyAry = [];
        if (!$nowSm4Key || !$nowMd5Key) {//首次使用，需要获取key
            $this->sm4Key = $nowSm4Key ?: $config['pay_construction_sm4_key'];//先使用初始key
            $this->md5Key = $nowMd5Key ?: $config['pay_construction_md5_key'];//先使用初始key
            $nowKeyAry = $this->getKey();
        }
        if($nowKeyAry && $nowKeyAry['error']){
            return [
                'error'=>1,
                'msg'=>$nowKeyAry['msg']
            ];
        }
        $this->sm4Key = $nowKeyAry['newSm4Key'] ?? $nowSm4Key;
        $this->md5Key = $nowKeyAry['newMd5Key'] ?? $nowMd5Key;
        if(!$this->sm4Key || !$this->md5Key){
            return [
                'error'=>1,
                'msg'=>'密钥获取失败！'
            ];
        }
    }

    /**
     * 获取sm4Key以及MD5Key
     */
    public function getKey()
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];


        $txCode = 'LCS00002';
        $url = $this->commonUrl.$txCode;
        $data = $this->baseData;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').'00001';

        $data['transID'] = $transID;
        $data['txCode'] = $txCode;
        $dataAry = [
            'thirdSysID'=>$this->baseData['thirdSysID'],
            'transID'=>$transID,
            'txCode'=>$txCode
        ];
        $data['data'] = $this->sm4Encrypt($dataAry);
        if(!$data['data']){
            $return['error'] = 1;
            $return['msg'] = 'sm4加密失败';
            return $return;
        }

        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->md5Key.$transID);
        $result = $this->curlPost($url,$data);
        if($result['error']){
            $return['error'] = 1;
            $return['msg'] = $result['msg'];
            return $return;
        }
        $data = $this->sm4Decrypt($result['data']);//解密获取信息
        fdump_api($data,'post',1);
        if(!$data){
            $return['error'] = 1;
            $return['msg'] = 'sm4解密失败';
            return $return;
        }

        $dataAry = json_decode($data,true);
        if(isset($dataAry['status']) && $dataAry['status'] == 1){
            $newKey = $dataAry['keyInfo'];
            $newKeyAry = explode('|',$newKey);
            $newSm4Key = $newKeyAry[0];
            $newMd5Key = $newKeyAry[1];
            $return['newSm4Key'] = $newSm4Key;
            $return['newMd5Key'] = $newMd5Key;
            (new ConfigService())->saveConfigData(['name'=>'construction_sm4_key','value'=>$newSm4Key]);
            (new ConfigService())->saveConfigData(['name'=>'construction_md5_key','value'=>$newMd5Key]);
            cfg('construction_sm4_key',$newSm4Key);
            cfg('construction_md5_key',$newMd5Key);
            (new CacheSqlService())->clearCache();
            return $return;
        }else{
            $errorCode = $dataAry['errorCode'] ?? '';
            $errMsg = $dataAry['failReason'] ?? '请求支付失败！';
            $return['error'] = 1;
            $return['msg'] = '【'.$errorCode.'】'.$errMsg;
            return $return;
        }

    }


	public function pay($order_no, $money, $extra_cfg){
        if(empty($order_no) || empty($money)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'money' => $money,'msg' => '订单ID或支付金额必填'],'errPay/WechatServicePayLog',1);
            throw new \think\Exception("订单ID或支付金额必填！");
        }
        $extra_cfg['pay_type'] = 'out_link';
        $pay = (new ConstructionPayService($this->agent,$this->config))->pay($order_no, $money, $extra_cfg);
		return $pay;
		return $pay;
	}
    
    /**
     * 查询订单状态
     */
    public function queryOrder($param)
    {
        $return = (new ConstructionPayService($this->agent,$this->config))->queryOrder($param);
        return $return;
    }
    /**
     * 退款
     */
    public function refund($order_no, $total_fee, $refund_money, $transaction_no)
    {
        $return = (new ConstructionPayService($this->agent,$this->config))->refund($order_no, $total_fee, $refund_money, $transaction_no);
        return $return;
    }

    /**
     * 退款结果查询接口
     */
    public function getRefundStatus($param)
    {
        $return = (new ConstructionPayService($this->agent,$this->config))->getRefundStatus($param);
        return $return;
    }

    static public function curlPost($url,$data,$timeout=15, $type = null){
        $ch = curl_init();
        $headers[] = "Accept-Charset: utf-8";//"Content-Type: multipart/form-data; boundary=" .  uniqid('------------------');
        if($type == 'json'){
            $headers[] = "Content-type: application/json;charset=utf-8";
        }
// 		$header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
        $result = curl_exec($ch);

        //关闭curl
        curl_close($ch);
        // echo $result;exit;
        $resultAry = json_decode($result, true);
        fdump_api([$url,$data,$result],'construction/post',1);
        if (isset($resultAry['data']) || isset($resultAry['RET_CODE'])) {
            $returnData = isset($resultAry['data']) ? $resultAry['data'] : $resultAry;
            return array('error' => 0, 'data' => $returnData);
        } else {
//            fdump_api([$url,$data,$result],'construction/post',1);
            return array('error' => 1, 'msg' => '请求失败');
        }
    }

    /**
     * sm4加密
     */
    public function sm4Encrypt($data)
    {
        $key = $this->sm4Key;
// 必须转换为字符串
        if(is_array($data)){
            $content = json_encode($data,JSON_HEX_QUOT);
        }else{
            $content = $data;
        }
        $sm4 = new SM4($key);
        $encryptContent = $sm4->encrypt($content);
        $encryptContent = strtoupper($encryptContent);
        return $encryptContent;
    }

    /**
     * sm4解密
     */
    public function sm4Decrypt($encryptContent)
    {
        $key = $this->sm4Key;
        $sm4 = new SM4($key);
        $decryptedJsonContent = $sm4->decrypt($encryptContent);
        $decryptedJsonContent = preg_replace("/[\r\n\f]/","",$decryptedJsonContent);
        return $decryptedJsonContent;
    }

}