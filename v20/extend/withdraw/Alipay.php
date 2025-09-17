<?php
/*
 * 支付宝提现到支付宝余额
 * author 衡婷妹
 * date 20210413
 * */


namespace withdraw;
final class Alipay
{
    // 商户id
    public $privateKey;
    public $appId;
    public $publicKey;
    public $baseUrl;
    public $signType;

    public function __construct()
    {
        $this->privateKey = cfg('withdraw_alipay_merchant_private_key'); // 商户私钥
        $this->appId = cfg('withdraw_alipay_appid'); // 商户账号appid
        $this->publicKey = cfg('withdraw_alipay_public_key'); // 支付宝公钥
        $this->baseUrl = 'https://openapi.alipay.com';
        $this->signType =  cfg('withdraw_alipay_sign_type');
    }

    /*
    * 提现到微信余额
    * */
    public function withdraw($data){
        require_once 'alipay_sdk/aop/AopCertClient.php';
        require_once 'alipay_sdk/aop/request/AlipayFundTransUniTransferRequest.php';

        if(empty($this->appId) || empty($this->privateKey) || empty($this->publicKey)){
            $result['result']['err_code_des'] = '暂未配置支付宝提现支付信息，请至系统设置--付款管理--支付宝提现tab页中配置';
            fdump_api(['msg' => '暂未配置微信提现支付信息，请至系统设置--付款管理--支付宝提现tab页中配置','$data' => $data],'withdraw/errAlipayWithdrawLog',1);
            return $result;
        }

        // 项目根目录
        $DOCUMENT_ROOT = request()->server('DOCUMENT_ROOT');

        $aop = new \AopCertClient();
        $appCertPath = $DOCUMENT_ROOT.str_replace(file_domain(),'',cfg('withdraw_alipay_app_cert_public_key'));// "应用证书路径（要确保证书文件可读），例如：/home/admin/cert/appCertPublicKey.crt";
        $alipayCertPath = $DOCUMENT_ROOT.str_replace(file_domain(),'',cfg('withdraw_alipay_cert_public_key'));// "支付宝公钥证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayCertPublicKey_RSA2.crt";
        $rootCertPath = $DOCUMENT_ROOT.str_replace(file_domain(),'',cfg('withdraw_alipay_root_cert'));// "支付宝根证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayRootCert.crt";

        $aop->gatewayUrl = $this->baseUrl. "/gateway.do";
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->privateKey;
        $aop->format = "json";
        $aop->apiVersion = '1.0';
        $aop->charset= "utf-8";
        $aop->signType= $this->signType;
        //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $aop->isCheckAlipayPublicCert = true;
        try{
            //调用getPublicKey从支付宝公钥证书中提取公钥
            $aop->alipayrsaPublicKey = $aop->getPublicKey($alipayCertPath);
            //调用getCertSN获取证书序列号
            $aop->appCertSN = $aop->getCertSN($appCertPath);
            //调用getRootCertSN获取支付宝根证书序列号
            $aop->alipayRootCertSN = $aop->getRootCertSN($rootCertPath);

        } catch (\Exception $e) {
            $return['error'] = true;
            $return['msg'] = L_('证书错误');
            return $return;
        }

        //请求参数
        $param['out_biz_no'] = $data['partner_trade_no'];//商家侧唯一订单号
        $param['trans_amount'] = $data['money'];//订单总金额，单位为元，精确到小数点后两位
        $param['product_code'] = 'TRANS_ACCOUNT_NO_PWD';//业务产品码 单笔无密转账到支付宝账户固定为:TRANS_ACCOUNT_NO_PWD
        $param['biz_scene'] = 'DIRECT_TRANSFER';//描述特定的业务场景:DIRECT_TRANSFER单笔无密转账到支付宝
        $param['order_title'] = '支付宝提现'.$data['money'].'元';//转账业务的标题，用于在支付宝用户的账单里显示
        $param['payee_info'] = [
            'identity' => $data['account'],//参与方的唯一标识
            'identity_type' => 'ALIPAY_LOGON_ID',//参与方的标识类型，目前支持如下类型1、ALIPAY_USER_ID 支付宝的会员ID2、ALIPAY_LOGON_ID：支付宝登录号，支持邮箱和手机号格式
            'name' => $data['truename'],//参与方真实姓名，如果非空，将校验收款支付宝账号姓名一致性。当identity_type=ALIPAY_LOGON_ID时，本字段必填。
        ];//收款方信息
        $param['remark'] = '单笔转账';//业务备注
        $request = new \AlipayFundTransUniTransferRequest ();
        $request->setBizContent(json_encode($param));
        
        $result = $aop->execute ( $request);
       
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
		
        $return['error'] = false;
        if(!empty($resultCode)&&$resultCode == 10000){
            $return['order_id'] = $result->$responseNode->order_id;//支付宝转账订单号
            $return['out_biz_no'] = $result->$responseNode->out_biz_no;//商户订单号
            $return['pay_fund_order_id'] = $result->$responseNode->pay_fund_order_id ?? '';//支付宝支付资金流水号(本地保存的订单号)
        } else {
            $return['error'] = true;
            $return['msg'] = $result->$responseNode->sub_msg;
        }
        fdump_api(['msg' => '支付宝提现结果','$data' => $data,'$param' => $param,'$result' => $result],'withdraw/alipayWithdrawLog',1);
        return $return;
    }
}
?>