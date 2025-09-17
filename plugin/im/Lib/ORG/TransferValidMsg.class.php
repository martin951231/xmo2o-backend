<?php

/**
 * 转发微信公众号非法信息到平台客服
 */
class TransferValidMsg
{
    public function handle(array $params)
    {
        if (empty($params)) {
            return;
        }
        $type = isset($params['type']) ? $params['type'] : '';
        if ($type == 'store') {
            //TODO 转发给对应店铺客服
            return;
        } else if ($type == 'plat') {
            //转发给平台客服
            $openid = $params['openid'];
            $msgType = $params['msg_type'];
            $body = $params['body'];
            $tm = time();
            $wechatUser = M('Merchant_wechat_user')->where(['openid' => $openid])->find();
            if (empty($wechatUser) || $tm - strtotime($wechatUser['update_time']) > 86400 * 7) {
                import('@.ORG.WechatHelper');
                $accesstoken = PD('Access_token_expires')->get_access_token();
                if (empty($accesstoken) && $accesstoken['errcode'] != 0) {
                    return;
                }
                $user = new WechatUser(['access_token' => $accesstoken['access_token']]);
                $userinfo = $user->getUserInfo($openid);
                if (empty($userinfo)) {
                    return;
                }
                $updateUser = [
                    'openid' => $openid,
                    'nickname' => $userinfo['nickname'],
                    'avatar' => $userinfo['headimgurl'],
                    'update_time' => date('Y-m-d H:i:s', $tm)
                ];
                if ($wechatUser) {
                    M('Merchant_wechat_user')->where(['id' => $wechatUser['id']])->save($updateUser);
                } else {
                    $wechatUser['id'] = M('Merchant_wechat_user')->add($updateUser);
                }
                $wechatUser = array_merge($wechatUser,$updateUser);
            }
            //转发
            $msg = [
                'from_user' => 'wechat_' . $wechatUser['id'],
                'to_user' => 'plat_0',
                'custom' => [
                    'type' => $msgType,
                    'body' => $body
                ],
                'skip_ticket' => 1
            ];
            $sendMessageUrl = C('config.site_url') . '/plugin.php?plugin=im&c=JMessage&a=sendMessage';
            $rs = httpRequest($sendMessageUrl, 'POST', $msg);
            if (isset($rs[0]) && $rs[0] == '200') {
                return json_decode($rs[1], true);
            }else{
                return false;
            }
        }
    }
}