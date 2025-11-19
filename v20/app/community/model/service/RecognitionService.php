<?php


namespace app\community\model\service;

use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use error_msg\GetErrorMsg;
use net\Http as Http;
require_once '../extend/phpqrcode/phpqrcode.php';
class RecognitionService
{
    /**
     * 获取临时二维码
     * @author lijie
     * @date_time 2021/06/28
     * @param $qrcodeId
     * @return array
     * @throws \think\Exception
     */
    public function getTmpQrcode($qrcodeId){
        $appid     = cfg('wechat_appid');
        $appsecret = cfg('wechat_appsecret');

        if(empty($appid) || empty($appsecret)){
            throw new \think\Exception("请联系管理员配置【AppId】【 AppSecret】");
        }
        $httpObj = new Http();
        //微信授权获得access_token
        $accessTokenArray = (new \app\common\model\service\weixin\AccessTokenExpiresService)->getAccessToken();
        if ($accessTokenArray['errcode']) {
            throw new \think\Exception('获取access_token发生错误：错误代码' . $accessTokenArray['errcode'] .',微信返回错误信息：' . $accessTokenArray['errmsg']);
        }
        $accessToken = $accessTokenArray['access_token'];

        $qrcode_url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$accessToken;
        $post_data['expire_seconds'] = 2592000;
        $post_data['action_name'] = 'QR_SCENE';
        $post_data['action_info']['scene']['scene_id'] = $qrcodeId;

        $json = $httpObj->curlPost($qrcode_url,json_encode($post_data));
        if (!$json['errcode']){
            return(array('error_code'=>true,'id'=>$qrcodeId,'ticket'=>'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($json['ticket'])));
        }else{
            return(array('error_code'=>false,'msg'=>L_('发生错误：错误代码 ').$json['errcode'].L_('，微信返回错误信息：').$json['errmsg']));
        }
    }

    public function getWxTmpQrcode($summary_id){
        $appid     = cfg('pay_wxapp_appid');
        $appsecret = cfg('pay_wxapp_appsecret');

        if(empty($appid) || empty($appsecret)){
            throw new \think\Exception("请联系管理员配置【AppId】【 AppSecret】");
        }
        $httpObj = new Http();
        //微信授权获得access_token
        $accessTokenArray = (new \app\common\model\service\weixin\AccessTokenWxappExpiresService)->getAccessToken();
        if ($accessTokenArray['errcode']) {
            throw new \think\Exception('获取access_token发生错误：错误代码' . $accessTokenArray['errcode'] .',微信返回错误信息：' . $accessTokenArray['errmsg']);
        }
        $accessToken = $accessTokenArray['access_token'];
        $now_order = (new HouseNewPayOrderSummary())->getOne(['summary_id'=>$summary_id]);
        $qrcode_url='https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$accessToken;
        $post_data['width'] = 150;
        $post_data['path'] = 'pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id='.$now_order['village_id'].'&pigcms_id='.$now_order['pay_bind_id'];
        $json = $this->curlPost($qrcode_url,json_encode($post_data));
        if (!$json['errcode']){
            $json_url ="data:image/jpeg;base64,".base64_encode($json['path']);
            return(array('error_code'=>true,'id'=>$summary_id,'ticket'=>$json_url));
        }else{
            return(array('error_code'=>false,'msg'=>L_('发生错误：错误代码 ').$json['errcode'].L_('，微信返回错误信息：').$json['errmsg']));
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
        $result = curl_exec($ch);

        //关闭curl
        curl_close($ch);
      //   echo $result;exit;
        if (empty($result)) {
            return array('errcode' => 1, 'errmsg' => '请求失败');
        }elseif (isset($result['errcode'])) {
            $errmsg = GetErrorMsg::wx_error_msg($result['errcode']);
            return array('errcode' => $result['errcode'], 'errmsg' => $errmsg,'real_errmsg'=>$result['errmsg']);
        } else {
            return array('errcode' => 0, 'path' => $result);
        }
    }

    public function createQrcodeFlow($txt='',$filename='')
    {
        $qrCon = $txt;
        if(empty($filename)){
            $filename='qrcode_flow'.time();
        }
        $dir = '../../runtime/qrcode/village';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $fullpath=$dir . '/' . $filename.'.png';
        $qrcode = new \QRcode();
        $imgflow=$qrcode->png($qrCon,$fullpath, 'M', '9');
        return '/runtime/qrcode/village/' . $filename.'.png';
    }
    
    /**
     * 获取二维码
     */
    public function getAbChinaQrcode($summary_id,$url)
    {
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $urlMd5 = md5($url);
        $QR = 'runtime/qrcode/house/'. $urlMd5.'.png';      //已经生成的原始二维码图片文件
        if(@fopen($http_type.$_SERVER["HTTP_HOST"].'/'.$QR,'r')){
            return(array('error_code'=>true,'id'=>$summary_id,'ticket'=>$http_type.$_SERVER["HTTP_HOST"].'/'.$QR));
        }
        require_once '../extend/phpqrcode/phpqrcode.php';
        $time = date('Hi');
        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "9";
        $dir = '../../runtime/qrcode/house';
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/house/' . $urlMd5.'.png';
        $qrcode->png($url, $filename_url, $errorLevel, $size);
        return(array('error_code'=>true,'id'=>$summary_id,'ticket'=>$http_type.$_SERVER["HTTP_HOST"].'/'.$QR));
    }
}