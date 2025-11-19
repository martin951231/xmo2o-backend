<?php


namespace app\community\model\service;


class WxService
{
    public function getTmpQrcode($appid,$appsecret,$qrcode_id){

        if(empty($appid) || empty($appsecret)){
            return false;
        }


        //微信授权获得access_token
        $access_token_array = D('Access_token_expires')->get_access_token();
        if ($access_token_array['errcode']) {
            return(array('error_code'=>true,'msg'=>'获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']));
        }
        $access_token = $access_token_array['access_token'];

        $qrcode_url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
        $post_data['expire_seconds'] = 2592000;
        $post_data['action_name'] = 'QR_SCENE';
        $post_data['action_info']['scene']['scene_id'] = $qrcode_id;

        $json = $http->curlPost($qrcode_url,json_encode($post_data));
        if (!$json['errcode']){
            return(array('error_code'=>false,'id'=>$qrcode_id,'ticket'=>'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($json['ticket'])));
        }else{
            return(array('error_code'=>true,'msg'=>L_('发生错误：错误代码 ').$json['errcode'].L_('，微信返回错误信息：').$json['errmsg']));
        }
    }
}