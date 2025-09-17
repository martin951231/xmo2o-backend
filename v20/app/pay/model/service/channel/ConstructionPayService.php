<?php
/**
 * 建行支付V2.2
 */

namespace app\pay\model\service\channel;

use app\common\model\service\CacheSqlService;
use app\common\model\service\ConfigService;
use app\community\model\db\ConstructionOrderList;
use app\pay\model\service\PayService;
use net\Http;
use sm4\SM4;

class ConstructionPayService
{
    private $agent = '';
//    private $commonUrl = 'http://124.127.94.57:8006/Adapter/lcs/';
//    private $payUrl = 'http://124.127.94.57:8003/order/orderPay_branch_H5.jhtml';
//    private $paySdkUrl = 'http://124.127.94.57:8003/order/orderPay_branch_sdk.jhtml';
//    private $outLinkPayUrl = 'http://124.127.94.57:8003/e_report/outlink/index.html';
    private $commonUrl = 'https://interface.cloud.ccb.com/Adapter/lcs/';
    private $payUrl = 'https://cloud.life.ccb.com/order/orderPay_branch_H5.jhtml';
    private $paySdkUrl = 'https://cloud.life.ccb.com/order/orderPay_branch_sdk.jhtml';
    private $outLinkPayUrl = 'https://cloud.life.ccb.com/e_report/outlink/index.html';
    private $config = [];//配置参数
    private $baseData = [];//通用报文数据
    private $desKey = '';//desKey
    private $desMd5Key='';
    private $md5Key = '';//md5Key
    private $sm4Key = '';//sm4Key
    private $sm4KeyError='';
    private $desKeyError='';
    private $linkId = 'e6cedbc98eb04a078d39ef31c2310793';//app支付

    public function __construct($agent = '', $config = [])
    {
        $this->sm4Key = $this->md5Key = '';
        $this->desMd5Key='';
        $this->desKey='';
        $this->agent = $agent;
        $this->config = $config;
        $this->baseData = [
            'thirdSysID' => $config['third_sys_id'] ?? '',
            'txCode' => '',
            'data' => [],
            'auth' => '',
            'secID' => '000021',//data字段sm4加密传输，且验签  000011是des加密
            'transID' => '',
        ];
        $nowSm4Key = cfg('construction_sm4_key');
        $nowMd5Key = cfg('construction_md5_key');
        $nowDesKey = cfg('construction_des_key');
       // $nowDesmd5Key = cfg('construction_desmd5_key');
        if (!$nowSm4Key || !$nowMd5Key) {//首次使用，需要获取key
            $this->sm4Key = $nowSm4Key ?: $config['pay_construction_sm4_key'];//先使用初始key
            $this->md5Key = $nowMd5Key ?: $config['pay_construction_md5_key'];//先使用初始key
            $nowKeyAry = $this->getKey('LCS00002');
            if($nowKeyAry && $nowKeyAry['error']){
                /*
                return [
                    'error'=>1,
                    'msg'=>$nowKeyAry['msg']
                ];
                */
                fdump_api($nowKeyAry,'00getKey',1);
                $this->sm4KeyError=$nowKeyAry['msg'];
            }
            $this->sm4Key = $nowKeyAry && isset($nowKeyAry['newSm4Key']) ? $nowKeyAry['newSm4Key']:'';
            $this->md5Key = $nowKeyAry && isset($nowKeyAry['newMd5Key']) ? $nowKeyAry['newMd5Key']:'';
            if(!$this->sm4Key || !$this->md5Key){
                /*
                return [
                    'error'=>1,
                    'msg'=>'密钥获取失败！'
                ];
                */
                $this->sm4KeyError='密钥获取失败！';
            }
        }else{
            $this->sm4Key = $nowSm4Key;
            $this->md5Key = $nowMd5Key;
        }

        if (!$nowDesKey || !$nowMd5Key) { //首次使用，需要获取key
            $this->desKey = $nowDesKey ?: $config['pay_construction_des_key'];//先使用初始key
            $this->md5Key = $nowMd5Key ?: $config['pay_construction_md5_key'];//先使用初始key
            $nowKeyAry = $this->getKey('LCS00001');
            if($nowKeyAry && $nowKeyAry['error']){
                /*
                return [
                    'error'=>1,
                    'msg'=>$nowKeyAry['msg']
                ];
                */
                fdump_api($nowKeyAry,'00getKey',1);
                $this->desKeyError=$nowKeyAry['msg'];
            }
            $this->desKey = $nowKeyAry && isset($nowKeyAry['newDesKey']) ? $nowDesKey:'';
            $this->md5Key = $nowKeyAry && isset($nowKeyAry['newDesMd5Key']) ? $nowMd5Key:'';
            if(!$this->desKey || !$this->md5Key){
                /*
                return [
                    'error'=>1,
                    'msg'=>'密钥获取失败！'
                ];
                */
                $this->desKeyError='密钥获取失败！';
            }
        }else{
            $this->desKey = $nowDesKey;
            $this->md5Key = $nowMd5Key;
           
        }
        $this->desMd5Key = $this->md5Key;  //这两md5 取最新的 不分加密方式
    }

    /**
     * 获取sm4Key以及MD5Key
     */
    public function getKey($txCode='LCS00002')
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];
        $url = $this->commonUrl.$txCode;
        $data = $this->baseData;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(10,99).'001';

        $data['transID'] = $transID;
        $data['txCode'] = $txCode;
        $dataAry = [
            'thirdSysID'=>$this->baseData['thirdSysID'],
            'transID'=>$transID,
            'txCode'=>$txCode
        ];
        $keyType='';
        $md5keyStr='';
        if($txCode=='LCS00002'){
            $data['secID']='000021';
            $keyType='sm4';
            $md5keyStr=$this->md5Key;
            $data['data'] = $this->sm4Encrypt($dataAry);
        }else if($txCode=='LCS00001'){
            $keyType='des';
            $data['secID']='000011';
            $md5keyStr=$this->desMd5Key;
            $data['data'] = $this->desEncrypt($dataAry);
        }
        if(!$data['data']){
            $return['error'] = 1;
            $return['msg'] = $keyType.'加密失败';
            return $return;
        }
        
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$md5keyStr.$transID);
        $result = $this->curlPost($url,$data);
        if($result['error']){
            $return['error'] = 1;
            $return['msg'] = $result['msg'];
            return $return;
        }
        $ret_data='';
        if($txCode=='LCS00002') {
            $ret_data = $this->sm4Decrypt($result['data']);//解密获取信息
        }elseif ($txCode=='LCS00001'){
            $ret_data=$this->desDecrypt($result['data']);
        }
        fdump_api($ret_data,'00getKey',1);
        if(!$ret_data){
            $return['error'] = 1;
            $return['msg'] = $keyType.'解密失败';
            return $return;
        }

        $dataAry = json_decode($ret_data,true);
        if(isset($dataAry['status']) && $dataAry['status'] == 1){
            $newKey = $dataAry['keyInfo'];
            $newKeyAry = explode('|',$newKey);
            fdump_api(['txCode'=>$txCode,'newKeyAry'=>$newKeyAry],'00getKey',1);
            if($txCode=='LCS00002'){
                $newSm4Key = $newKeyAry[0];
                $newMd5Key = $newKeyAry[1];
                $return['newSm4Key'] = $newSm4Key;
                $return['newMd5Key'] = $newMd5Key;
                (new ConfigService())->saveConfigData(['name'=>'construction_sm4_key','value'=>$newSm4Key]);
                (new ConfigService())->saveConfigData(['name'=>'construction_md5_key','value'=>$newMd5Key]);
                cfg('construction_sm4_key',$newSm4Key);
                cfg('construction_md5_key',$newMd5Key);
            }else if($txCode=='LCS00001'){
                $newDesKey = $newKeyAry[0];
                $newDesMd5Key = $newKeyAry[1];
                $return['newDesKey'] = $newDesKey;
                $return['newDesMd5Key'] = $newDesMd5Key;
                (new ConfigService())->saveConfigData(['name'=>'construction_des_key','value'=>$newDesKey]);
                (new ConfigService())->saveConfigData(['name'=>'construction_md5_key','value'=>$newDesMd5Key]);
                cfg('construction_des_key',$newDesKey);
                cfg('construction_md5_key',$newDesMd5Key);
                //cfg('construction_desmd5_key',$newDesMd5Key);
            }

            (new CacheSqlService())->clearCache();
            return $return;
        }else{
            $errorCode = $dataAry['errorCode'] ?? '';
            $errMsg = $dataAry['failReason'] ?? '请求支付失败。';
            $return['error'] = 1;
            $return['msg'] = '【'.$errorCode.'】'.$errMsg;
            return $return;
        }
        
    }
    /**
     * 加密
     */
    public function desEncrypt($data)
    {
        if(is_array($data)){
            $str = json_encode($data,JSON_UNESCAPED_UNICODE);
        }else{
            $str = $data;
        }
        $encrypted = openssl_encrypt($str, 'DES-ECB', $this->desKey, OPENSSL_RAW_DATA);
        $encrypted = strtoupper(bin2hex($encrypted));
        return $encrypted;
    }
    /**
     * 解密
     */
    public function desDecrypt($str){
        $encrypted = hex2bin($str);
        $decrypted = openssl_decrypt($encrypted, 'DES-ECB', $this->desKey, OPENSSL_RAW_DATA);
        return $decrypted;
    }
	public function pay($order_no, $money, $extra_cfg){
		if(empty($order_no) || empty($money)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'money' => $money,'msg' => '订单ID或支付金额必填'],'errPay/WechatServicePayLog',1);
			throw new \think\Exception("订单ID或支付金额必填！");
		}
//        $this->agent = 'iosapp';
        if(in_array($this->agent,['h5','wechat_h5','alipay','iosapp','androidapp','wechat_mini'])){
            //先导入账单，后支付
            $uploadOrder = $this->uploadOrder($order_no,$extra_cfg);
            if($uploadOrder['error']){
                throw new \think\Exception($uploadOrder['msg']);
            }
            $extra_cfg['bill_id'] = $uploadOrder['billId'] ?? '';
            $extra_cfg['upload_order_info']=isset($uploadOrder['order_ret']) ? $uploadOrder['order_ret']:'';
            if(!$extra_cfg['bill_id']){
                throw new \think\Exception('未获取到缴费单ID');
            }
            $constructionOrderListDb=new ConstructionOrderList();
            //新建建行订单记录
            $ret=$constructionOrderListDb->addOne([
                'business'=>$extra_cfg['business_type'],
                'orderid'=>$order_no,
                'create_time'=>time(),
                'update_time'=>time()
            ]);
            fdump_api(['addOne','ret'=>$ret],'construction/pay',1);
        }
        if(isset($extra_cfg['pay_type']) && $extra_cfg['pay_type']=='out_link'){
            $pay = $this->outLinkPay($extra_cfg);
            return $pay;
        }
		switch ($this->agent) {
            case 'h5':
			case 'wechat_h5'://微信端
            case 'alipay':
                $pay = $this->h5Pay($extra_cfg);
				fdump_api(['h5Pay','pay'=>$pay,'extra_cfg'=>$extra_cfg],'construction/pay',1);
				break;
			case 'iosapp':
			case 'androidapp':
				$pay = $this->appPay($extra_cfg);
				fdump_api(['app','pay'=>$pay,'extra_cfg'=>$extra_cfg],'construction/pay',1);
                if($pay['error']){
                    throw new \think\Exception($pay['msg']);
                }
				break;
			case 'wechat_mini'://微信小程序
                $pay = $this->miniPay($extra_cfg);
				fdump_api(['wechat_mini','pay'=>$pay,'extra_cfg'=>$extra_cfg],'construction/pay',1);
                if($pay['error']){
                    throw new \think\Exception($pay['msg']);
                }
				break;
			
			default:
                fdump_api(['param' => $_POST,'agent' => $this->agent,'order_no' => $order_no,'money' => $money,'msg' => '当前端口未对接，不能使用'],'errPay/ConstructionPayLog',1);
				throw new \think\Exception("当前端口未对接，不能使用");
				break;
		}
        if($pay['error']){
            throw new \think\Exception($pay['msg']);
        }
        unset($pay['error'],$pay['msg']);
		return $pay;
	}

    /**
     * 建行app支付
     */
    public function outLinkPay($param)
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $platformType = '';
        if (preg_match('/iphone|ipod|ipad/i', $user_agent)) {
            $platformType = '2';
        }else if (preg_match('/android/i', $user_agent)) {
            $platformType = '1';
        }
        if(!$platformType){
            $return['error'] = 1;
            $return['msg'] = '仅支持移动端ios或者安卓系统h5支付';
        }

        $url = $this->outLinkPayUrl;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(10,99).'002';
        $ccbLCSParam = [
            'BILL_ID'=>$param['bill_id'],//订单编号
            'TRANS_ID'=>$transID,
            'VERSION'=>'1',
            'PLATFORM'=>$platformType
        ];
        $data = [
            'link_id'=>$this->linkId,
            'THIRD_SYS_ID'=>$this->config['third_sys_id'],
            'ccbLCSParam'=>$this->sm4Encrypt($ccbLCSParam)
        ];
//        $return['txt'] = $this->buildRequestForm($url, $data);
        $return['go_url'] = $url;
        $return['data'] = $data;
        return $return;
    }
	/**
     * h5支付
     */
	public function h5Pay($param)
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];
        $url = $this->payUrl;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(100,999).'003';
        $data = [
            'TRANS_ID'=>$transID,
            'THIRD_SYS_ID'=>$this->config['third_sys_id'],
            'BILL_ID'=>$this->sm4Encrypt($param['bill_id']),//订单编号
            'EXTPARAM'=>'299E485D06CE6A64993F2DBB4463A987BE5561293D27A233',//网站扩展字段
            'CLIENT_TYPE'=>'1',//终端标识 0-PC端；1-手机网页版；其它默认为PC端
//            'MODIFY_AMT'=>'',//缴费单明细可变金额
//            'CHANNEL_TYPE'=>'002',//渠道类型
//            'LCSPARAMS'=>'',//智慧缴费扩展字段
            'LOGIN_INFO_LIST'=>$this->sm4Encrypt('订单编号='.$param['summary_order_no']),//智慧缴费扩展字段
            'secID'=>'000020',//加解密方式000020:SM4加解密,为空则使用原来的加解密方案
//            'LCSExtra'=>'',//智慧缴费扩展字段2
        ];
        //$go_h5_pay_url = $this->payUrl.'?'.http_build_query($data);
        $return['go_url'] = $url;
        $return['go_h5_pay_url'] = '';
        $return['data'] = $data;
        return $return;
    }
	/**
     * app支付
     */
	public function appPay($param)
    {
        $return = [
            'error'=>0,
            'type'=>'sdk',
            'msg'=>'',
        ];
        $url = $this->paySdkUrl;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(100,999).'004';
        $systemType = $this->agent == 'iosapp' ? '01' : '02';
        $data = [
            'TRANS_ID'=>$transID,
            'THIRD_SYS_ID'=>$this->config['third_sys_id'],
            'BILL_ID'=>$this->sm4Encrypt($param['bill_id']),//订单编号
            'SYSTEM_TYPE'=>$systemType,//操作系统标志 01:安卓 02:苹果IOS
//            'CLIENT_IP'=>'',//手机IP
//            'THIRD_APP_INFO'=>'',//客户端标识 todo 这个要问问
            'TIMEOUT'=>'10',//订单超时时间 单位:分钟
            'UNIONPAY_FLAG'=>'2',//银联跨行标识 0-非银联(建行支付)，1-银联(跨行)，2-微信支付，3支付宝支付 ；空或其它默认为使用非银联(建行支付)。
            'secID'=>'000020',//加解密方式000020:SM4加解密,为空则使用原来的加解密方案
        ];
        $url = $url.'?TRANS_ID='.$transID.'&THIRD_SYS_ID='.$this->config['third_sys_id'].'&BILL_ID='.$this->sm4Encrypt($param['bill_id']).'&SYSTEM_TYPE='.$systemType.'&TIMEOUT=10&UNIONPAY_FLAG=2&secID=000020';
        $result = $this->curlPost($url,[]);
        if($result['error']){
            $return['error'] = 1;
            $return['msg'] = $result['msg'];
            return $return;
        }
//        $dataInfo = $this->sm4Decrypt($result['data']);//解密获取信息
//        if(!$dataInfo){
//            $return['error'] = 1;
//            $return['msg'] = 'sm4解密失败';
//            return $return;
//        }
//        $dataAry = json_decode($dataInfo,true);
        $dataAry = $result['data'];
        if(isset($dataAry['RET_CODE']) && $dataAry['RET_CODE'] == 'SUCCESS'){
            $payParams = $dataAry['PAY_PARAMS'] ? $this->sm4Decrypt($dataAry['PAY_PARAMS']) : '';
            $return['payInfo'] = [
                'pay_flow'=>$dataAry['PAY_FLOW'],
                'pay_params'=>$payParams
            ];
            return $return;
        }else{
            $errorCode = $dataAry['RET_CODE'] ?? '';
            $errMsg = $dataAry['RET_MSG'] ?? '请求支付失败！！';
            $return['error'] = 1;
            $return['msg'] = '【'.$errorCode.'】'.$errMsg;
            return $return;
        }
    }
	/**
     * 微信小程序支付
     */
	public function miniPay($param)
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];
        $txCode = 'PAY00001';
        $url = $this->commonUrl.$txCode;
        $data = $this->baseData;
        $data['secID']='000011';
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(100,999).'005';
        
        $data['transID'] = $transID;
        $data['txCode'] = $txCode;
        $wxapp_openid='';
        if(isset($param['openid']) && $param['openid']){
            $wxapp_openid=$param['openid'];
        }
        if(isset($param['wxapp_openid']) && $param['wxapp_openid']){
            $wxapp_openid=$param['wxapp_openid'];
        }
        $DETAIL_AMT=$this->config['pay_construction_money_id'].','.strval($param['money']);
        if(isset($param['upload_order_info']) && !empty($param['upload_order_info']) && isset($param['upload_order_info']['billIDMap'])){
            $billDetailIDs=$param['upload_order_info']['billIDMap']['0']['billDetailIDs'];
            $tmpArr=explode('|',$billDetailIDs['0']);
            $DETAIL_AMT=$tmpArr['0'].','.strval($param['money']);
        }
        $dataAry = [
            'transID'=>$transID,
            'txCode'=>$txCode,
            'thirdSysID'=>$this->baseData['thirdSysID'],
            'project_id'=>$this->config['pay_construction_project_id'],
            'billId'=>$param['bill_id'],
            'PAYMENT'=>strval($param['money']),
            'DETAIL_AMT'=>$DETAIL_AMT,
//            'TIMEOUT'=>5,
            'SUB_APPID'=>cfg('pay_wxapp_appid'),
            'SUB_OPENID'=>$wxapp_openid,
        ];
       /*
        $data['data'] = $this->sm4Encrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->md5Key.$transID);
       */
        
        $data['data'] = $this->desEncrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->desMd5Key .$transID);
        
        $result = $this->curlPost($url,$data);
        if($result['error']){
            $return['error'] = 1;
            $return['msg'] = $result['msg'];
            return $return;
        }
        //$dataInfo = $this->sm4Decrypt($result['data']);//解密获取信息
        $dataInfo = $this->desDecrypt($result['data']);//解密获取信息
        if(!$dataInfo){
            $return['error'] = 1;
            $return['msg'] = '解密失败';
            return $return;
        }
 fdump_api(['param' => $dataAry,'dataInfo' => $dataInfo],'construction/miniPay',1);
        $dataAry = json_decode($dataInfo,true);
        if(isset($dataAry['status']) && $dataAry['status'] == 1){
            $nowTime=time();
            $jsApiParameters = [
                'appId'=>$dataAry['appId'],
                'nonceStr'=>$dataAry['nonceStr'],
                'package'=>$dataAry['pack'],
                'signType'=>$dataAry['signType'],
                'paySign'=>$dataAry['paySign'],
                'partnerid'=>$dataAry['partnerid'],
                'prepayid'=>$dataAry['prepayid'],
                'mweb_url'=>$dataAry['mweb_url'],
                //'package'=>"prepay_id=".$dataAry['partnerid'],
                'timeStamp'=>strval($nowTime),
            ];
            $return['type'] = 'jssdk';
            $return['info'] = json_encode($jsApiParameters);
            return $return;
        }else{
            $errorCode = $dataAry['errorCode'] ?? '';
            $errMsg = $dataAry['failReason'] ?? '请求支付失败！';
            $return['error'] = 1;
            $return['msg'] = '【'.$errorCode.'】'.$errMsg;
            return $return;
        }
    }

    /**
     * 导入需要支付的订单
     */
    public function uploadOrder($order_no,$param)
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];
        $txCode = 'IPSPT10003';
        $url = $this->commonUrl.$txCode;
        $data = $this->baseData;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(1000,9999).'006';

        $orderAry = [];
        foreach ($param['order_ary'] as $v){
            $billDetail = [
                [
                    'id'=>$this->config['pay_construction_money_id'],//缴费明细项ID
                    'duepay_amt'=>strval($v['money']),//缴费明细项应缴金额
                    'pay_amt'=>'0',//缴费明细项实缴金额，初始上送0
                    'free_amt'=>'0',//缴费明细项优惠金额，若没有优惠，则填写0
                ]
            ];
            //查找项或者展示项
            $customDetail = [];
            if($this->config['pay_construction_usernum_id']){
                //查找项 缴费单查找项编号(订单编号)
                $customDetail[] = [
                    'id'=>$this->config['pay_construction_usernum_id'],
                    'value'=>$v['summary_order_no']??''
                ];
            }
            $orderAry[] = [
                'billId_thirdSys'=>$order_no,
                'project_id'=>$this->config['pay_construction_project_id'],//收费项目ID
                'is_use'=>'01',
                'bill_date'=>date('YmdHis').'-'.createRandomStr(5,false,false,true),//缴费期
                'duepay_amt'=>strval($v['money']),//应缴金额
                'pay_amt'=>'0',
                'free_amt'=>'0',//优惠金额
//                'invalid_time'=>'',//缴费单有效期
//                'remark'=>'',
                'merchant_org_id'=>'0',//收费单位机构ID
                'operator'=>$this->config['third_sys_id'],//操作员,如果无法填入操作员实际值，请填入能标识行业应用的字段
                'rule_id'=>$param['rule_id'],//清算规则编号
//                'vcode_send'=>'0',//前端查询订单时是否发送手机验证码
//                'certType'=>'',//证件类型
//                'certID'=>'',//证件id
//                'name'=>'',
//                'mobile'=>'',
//                'msgRemark'=>'',
                'billDetail'=>$billDetail,
                'customDetail'=>$customDetail,
            ];
        }

        $data['transID'] = $transID;
        $data['txCode'] = $txCode;
        $dataAry = [
            'transID'=>$transID,
            'txCode'=>$txCode,
//            'batchNo'=>'',
            'billSet'=>$orderAry
        ];
        switch ($this->agent) {
            case 'h5':
            case 'wechat_h5'://微信端
            case 'iosapp':
            case 'alipay':
            case 'androidapp':
            /*
            $data['data'] = $this->sm4Encrypt($dataAry);
            $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->md5Key.$transID);
            */
            $data['secID']='000011';
            $data['data'] = $this->desEncrypt($dataAry);
            $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->desMd5Key .$transID);
                break;
            case 'wechat_mini'://微信小程序
                
                $data['secID']='000011';
                $data['data'] = $this->desEncrypt($dataAry);
                $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->desMd5Key .$transID);
                
                /*
                $data['data'] = $this->sm4Encrypt($dataAry);
                $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->md5Key.$transID);
                */
                break;
            default:
                $data['data'] = $this->sm4Encrypt($dataAry);
                $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->md5Key.$transID);
                break;
        }

        $result = $this->curlPost($url,$data);
        if($result['error']){
            $return['error'] = 1;
            $return['msg'] = $result['msg'];
            return $return;
        }
        //        $dataInfo = '{"successTip":"本次新生成1条缴费单，总金额50元；","billIDMap":[{"billId_thirdSys":"village_new_pay-79165","billId":"90090515267","billDetailIDs":["90121076603|0000021555"]}],"transID":"016212023101209273600001","txCode":"IPSPT10003","status":"1"}';
        switch ($this->agent) {
            case 'h5':
            case 'wechat_h5'://微信端
            case 'alipay':
            case 'iosapp':
            case 'androidapp':
            //$dataInfo = $this->sm4Decrypt($result['data']);//解密获取信息
            $dataInfo = $this->desDecrypt($result['data']);//解密获取信息
                break;
            case 'wechat_mini'://微信小程序
                //$dataInfo = $this->sm4Decrypt($result['data']);//解密获取信息
                $dataInfo = $this->desDecrypt($result['data']);//解密获取信息
                break;
            default:
                $dataInfo = $this->sm4Decrypt($result['data']);//解密获取信息
                break;
        }
        fdump_api(['dataAry'=>$dataAry,'data'=>$data,'ret'=>$dataInfo],'construction/uploadOrder',1);
        if(!$dataInfo){
            $return['error'] = 1;
            $return['msg'] = '解密失败';
            return $return;
        }

        $dataAry = json_decode($dataInfo,true);
        if(isset($dataAry['status']) && $dataAry['status'] == 1){
            $return['billId'] = $dataAry['billIDMap'][0]['billId'];
            $return['order_ret'] = $dataAry;
            return $return;
        }else{
            $errorCode = $dataAry['errorCode'] ?? '';
            $errMsg = $dataAry['failReason'] ?? '请求支付失败了！';
            $return['error'] = 1;
            $return['msg'] = '【'.$errorCode.'】'.$errMsg;
            return $return;
        }
    }
    
    /**
     * 查询订单状态
     */
    public function queryOrder($param)
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];
        $txCode = 'IPSPT10011';
        $url = $this->commonUrl.$txCode;
        $data = $this->baseData;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(100,999).'007';

        $data['transID'] = $transID;
        $data['txCode'] = $txCode;
//        $param['order_no'] = 'village_new_pay-79165';
        $dataAry = [
            'transID'=>$transID,
            'txCode'=>$txCode,
            'billId_thirdSys'=>$param['order_no'],
            'project_id'=>$this->config['pay_construction_project_id'],
//            'statusMap'=>''
        ];
        $data['secID']='000011';
        $data['data'] = $this->desEncrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->desMd5Key .$transID);
        /*
        $data['data'] = $this->sm4Encrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->md5Key.$transID);
        */
        $result = $this->curlPost($url,$data);
        fdump_api(['dataAry'=>$dataAry,'data'=>$data,'result'=>$result],'construction/queryOrder',1);
        if($result['error']){
            $return['error'] = 1;
            $return['msg'] = $result['msg'];
            return $return;
        }
        //$dataInfo = $this->sm4Decrypt($result['data']);//解密获取信息
        $dataInfo = $this->desDecrypt($result['data']);//解密获取信息
        fdump_api(['dataAry'=>$dataAry,'decrypt_result'=>$dataInfo],'construction/queryOrder',1);
        if(!$dataInfo){
            $return['error'] = 1;
            $return['msg'] = 'sm4解密失败';
            return $return;
        }

        $dataAry = json_decode($dataInfo,true);
        if(isset($param['all_result']) && $param['all_result']==1){
            $return['query_result']=$dataAry ? $dataAry:'';
            return $return;
        }
        $return['detail_paid_extra'] = '';
        $return['payMoney'] = 0;
        $return['payment_flow_no']='';
        $return['billId_thirdSys']='';
        $return['pay_status']='';
        $return['paid_extra']='';
        if(isset($dataAry['status']) && $dataAry['status'] == 1 && $dataAry['paymentDetailItemFlow']){
            $return['pay_status'] = 1;//交易成功
            $paymentDetailItemFlow = explode('@',$dataAry['paymentDetailItemFlow']);
            foreach ($paymentDetailItemFlow as $v){
                $v = $v ? explode("|",$v) : [];
                if($v && $v[3] == $param['order_no'] && in_array($v[6],['01','04'])){
                    $return['paid_extra'] = $v[1]; //明细流水号
                    $return['payment_flow_no']=$v[1];  //缴费流水号
                    $return['detail_paid_extra'] = $v[0];
                    $return['payMoney'] = $v[5];
                    $return['billId_thirdSys']=$v[3];
                    $return['pay_status']=$v[6];
                }
            }
            return $return;
        }else{
            $return['pay_status'] = 2;//交易失败
            return $return;
        }
    }
    /**
     * 退款
     */
    public function refund($order_no, $total_fee, $refund_money, $transaction_no = '')
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];
        $payService = new PayService();
        $payOrderInfo = $payService->getPayOrderInfo($order_no);
        if (empty($payOrderInfo)) {
            $return['error'] = 1;
            $return['msg'] = "订单查询失败！没有找到支付信息！";
            return $return;
        }
        fdump_api(['refund_money'=>$refund_money,'payOrderInfo'=>$payOrderInfo],'construction/refund',1);
        if($payOrderInfo['paid_time'] >= strtotime(date('Y-m-d'))){
            if($refund_money < $payOrderInfo['paid_money']){
                $return['error'] = 1;
                $return['msg'] = "建行支付当天支付的订单不支持部分退款！";
                return $return;
            }
            $refund_money = getFormatNumber(bcdiv($refund_money,100,3));
            $return = $this->refundToday($payOrderInfo['paid_extra'],$order_no);
        }else{
            $refund_money = getFormatNumber(bcdiv($refund_money,100,3));
            $paidInfo = (new ConstructionOrderList())->getOne(['orderid'=>$order_no]);
            $return = $this->refundNotToday($payOrderInfo['paid_extra'],$paidInfo,$refund_money);
        }
        fdump_api(['refund_money'=>$refund_money,'payOrderInfo'=>$payOrderInfo,'return'=>$return],'construction/refund',1);
        return $return;
    }
    /**
     * 退款(当天支付的订单)
     */
    public function refundToday($paidExtra,$order_no)
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];
        $txCode = 'IPSPT20010';
        $url = $this->commonUrl.$txCode;
        $data = $this->baseData;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(100,999).'008';

        $data['transID'] = $transID;
        $data['txCode'] = $txCode;
        $dataAry = [
            'transID'=>$transID,
            'txCode'=>$txCode,
            'projectId'=>$this->config['pay_construction_project_id'],
            'paymentFlowNo'=>$paidExtra//缴费流水号
        ];
        $data['secID']='000011';
        $data['data'] = $this->desEncrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->desMd5Key .$transID);
        /*
        $data['data'] = $this->sm4Encrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->md5Key.$transID);
        */
        $result = $this->curlPost($url,$data);
        if($result['error']){
            $return['error'] = 1;
            $return['msg'] = $result['msg'];
            return $return;
        }
        //$dataInfo = $this->sm4Decrypt($result['data']);//解密获取信息
        $dataInfo = $this->desDecrypt($result['data']);//解密获取信息
        if(!$dataInfo){
            $return['error'] = 1;
            $return['msg'] = 'sm4解密失败';
            return $return;
        }

        $dataAry = json_decode($dataInfo,true);
		fdump_api(['refundToday','paidExtra'=>$paidExtra,'order_no'=>$order_no,'dataAry'=>$dataAry],'construction/refund',1);
        if(isset($dataAry['status']) && in_array($dataAry['status'],['1','3'])){
            $return['refund_status'] = 1;//退款成功
            $return['refund_no'] = $dataAry['transID'];
            (new ConstructionOrderList())->updateThis(['orderid'=>$order_no,'is_refund'=>0],['is_refund'=>1,'update_time'=>time()]);
            return $return;
        }else{
            $return['refund_status'] = 2;//退款失败
            $return['refund_error_msg'] =$dataAry['failReason'] ?? '';//退款失败
            return $return;
        }
    }
    /**
     * 退款(非当天，半年内<155天内>支付的订单可退)
     */
    public function refundNotToday($paidExtra,$paidInfo,$refund_money)
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];
        $txCode = 'IPSPT10019';
        $url = $this->commonUrl.$txCode;
        $data = $this->baseData;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(100,999).'009';

        $data['transID'] = $transID;
        $data['txCode'] = $txCode;
        $dataAry = [
            'transID'=>$transID,
            'txCode'=>$txCode,
            'projectId'=>$this->config['pay_construction_project_id'],
            'paymentFlowNo'=>$paidExtra,//缴费流水号
            'refundAmout'=>$refund_money,//实际退款金额
            'refundList'=>[
                [
                    'detailItemFlowNo'=>$paidInfo['detail_paid_extra'],//支付明细流水号  (请先使用4.4中的ZC00002接口查询相关流水对应的支付明细流水)
                    'refundAmt'=>$refund_money//退款金额
                ]
            ],//退款明细流水列表
        ];
        $data['secID']='000011';
        $data['data'] = $this->desEncrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->desMd5Key .$transID);
        /*
        $data['data'] = $this->sm4Encrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->md5Key.$transID);
        */
        $result = $this->curlPost($url,$data);
        if($result['error']){
            $return['error'] = 1;
            $return['msg'] = $result['msg'];
            return $return;
        }
        //$dataInfo = $this->sm4Decrypt($result['data']);//解密获取信息
        $dataInfo = $this->desDecrypt($result['data']);//解密获取信息
        if(!$dataInfo){
            $return['error'] = 1;
            $return['msg'] = 'sm4解密失败';
            return $return;
        }

        $dataRefundRet = json_decode($dataInfo,true);
		fdump_api(['refundNotToday','paidExtra'=>$paidExtra,'order_no'=>$order_no,'dataAry'=>$dataAry,'dataRefundRet'=>$dataRefundRet,'refund_money'=>$refund_money],'construction/refund',1);
        $is_refund = 0;
        $query_refund_time = 0;
        if(isset($dataRefundRet['refundStatus']) && $dataRefundRet['refundStatus'] == '01'){
            $return['refund_status'] = 1;//退款成功
            $return['refund_no'] = $dataRefundRet['paybackFlowNo'];
            $is_refund = 1;
        }elseif(isset($dataRefundRet['refundStatus']) && $dataRefundRet['refundStatus'] == '03'){
            $getRefundStatus = $this->getRefundStatus([
                'paymentFlowNo'=>$paidExtra,
                'paybackFlowNo'=>$dataRefundRet['paybackFlowNo'],
            ]);
            if($return['error'] == 0 && $getRefundStatus['refund_status'] == '01'){
                $return['refund_status'] = 1;//退款成功
                $return['refund_no'] = $dataRefundRet['paybackFlowNo'];
                $is_refund = 1;
            }elseif($return['error'] == 0 && $getRefundStatus['refund_status'] == '02'){
                $return['refund_status'] = 2;//退款失败
                $return['failReason'] = $getRefundStatus['failReason'];//退款失败
            }else{//退款状态不明确 todo 修改订单状态，计划任务执行
                $is_refund = 2;
                $query_refund_time = time() + 1800;//30分钟后再次查询
            }
        }else{
			if(isset($dataRefundRet['failReason']) && $dataRefundRet['failReason']){
				$return['msg']=$dataRefundRet['failReason'];
			}
            $return['refund_status'] = 2;//退款失败
        }
        $checkauthApplyData = $this->config['refund_ary'] ? json_encode($this->config['refund_ary']) : '';
        if($is_refund > 0){
            (new ConstructionOrderList())->addOne([
                'business'=>$paidInfo['business'],
                'orderid'=>$paidInfo['orderid'],
                'paid_extra'=>$paidInfo['paid_extra'],
                'detail_paid_extra'=>$paidInfo['detail_paid_extra'],
                'pay_back_flow_no'=>$dataRefundRet['paybackFlowNo']??'',
                'is_notify'=>$paidInfo['is_notify'],
                'is_refund'=>$is_refund,
                'create_time'=>time(),
                'update_time'=>time(),
                'query_refund_time'=>$query_refund_time,
                'checkauth_apply_data'=>$checkauthApplyData
            ]);
        }
        return $return;
    }

    /**
     * 退款结果查询接口
     */
    public function getRefundStatus($param)
    {
        $return = [
            'error'=>0,
            'msg'=>'',
            'refund_status'=>0//退款状态，01：成功，02：失败，03：未知
        ];
        $txCode = 'IPSPT10020';
        $url = $this->commonUrl.$txCode;
        $data = $this->baseData;
        $transID = $this->baseData['thirdSysID'].date('YmdHis').rand(100,999).'010';

        $data['transID'] = $transID;
        $data['txCode'] = $txCode;
        $dataAry = [
            'transID'=>$transID,
            'txCode'=>$txCode,
            'projectId'=>$this->config['pay_construction_project_id'],
            'paymentFlowNo'=>$param['paymentFlowNo'],//缴费流水号
            'paybackFlowNo'=>$param['paybackFlowNo'],//退款流水号
        ];
        $data['secID']='000011';
        $data['data'] = $this->desEncrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->desMd5Key .$transID);
        /*
        $data['data'] = $this->sm4Encrypt($dataAry);
        $data['auth'] = md5($this->baseData['thirdSysID'].$txCode.$data['data'].$this->md5Key.$transID);
        */
        $result = $this->curlPost($url,$data);
        if($result['error']){
            $return['error'] = 1;
            $return['msg'] = $result['msg'];
            return $return;
        }
        //$dataInfo = $this->sm4Decrypt($result['data']);//解密获取信息
        $dataInfo = $this->desDecrypt($result['data']);//解密获取信息
        if(!$dataInfo){
            $return['error'] = 1;
            $return['msg'] = 'sm4解密失败';
            return $return;
        }

        $dataAry = json_decode($dataInfo,true);
        if(isset($dataAry['refundStatus'])){
            $return['refund_status'] = $dataAry['refundStatus'];
            $return['refundAmt'] = $dataAry['refundList'][0]['refundAmt'] ?? 0;
            $return['failReason'] = $dataAry['failReason'] ?? '';
            if($return['refund_status'] == '02'){
                //记录一下退款失败的原因
                fdump_api($dataAry,'construction/refundFail',1);
            }
            return $return;
        }else{
            $return['error'] = 1;//查询失败
            $return['msg'] = $dataAry['failReason'] ?? '';
            return $return;
        }
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
            return array('error' => 1, 'msg' => '请求失败了！');
        }
    }
    
    /**
     * 模拟form表单提交
     */
//    public function buildRequestForm($url, $data, $method = 'post')
//    {
//        $html = "<form id='requestForm' name='requestForm' action='".$url."' method='".$method."'>";
//        while (list ($key, $val) = each ($data))
//        {
//            $html.= "<input type='hidden' name='".$key."' value='".$val."' />";
//        }
//        $html.= "<input type='submit' value='确定' style='display:none;'></form>";
//        $html.= "<script>document.forms['requestForm'].submit();</script>";
//        return $html;
//    }
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
        $decryptedJsonContent = preg_replace("/[\r\n\f\v\x0F\x0E]/","",$decryptedJsonContent);
        return $decryptedJsonContent;
    }
    
    /**
     * 获取auth
     */
    public function getAuth($thirdSysID,$txCode,$data,$transID)
    {
        $auth = md5($thirdSysID.$txCode.$data.$this->md5Key.$transID);
        return $auth;
    }
    

    /**
     * 回调处理
     */
    public function notice()
    {
        $data = file_get_contents("php://input");
        $param = $data ? json_decode($data, true) : [];
        $data = $this->sm4Decrypt($param['data']);
        $dataAry = json_decode($data,true);
        
        if(!isset($dataAry['billSet'][0]['amount']) || !isset($dataAry['paymentFlowNo'])){
            return [];
        }
        $amount = $dataAry['billSet'][0]['amount'] ?: 0;
        return [
            'paid_money' => $amount * 100,
            'paid_time' => time(),
            'transaction_no' => $dataAry['paymentFlowNo'] ?: ''
        ];
    }
}