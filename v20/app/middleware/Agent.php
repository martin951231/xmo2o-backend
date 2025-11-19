<?php
declare (strict_types = 1);

namespace app\middleware;

class Agent
{
    /**
     * 判断当前环境  H5浏览器|微信H5|微信小程序|支付宝H5|支付宝小程序|安卓APP|IOS APP|...
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        // $request->agent = 'h5';
        // $agent = $request->header('user-agent');//由于微信中没办法改agent， 不准备用agent判断了
        $agent = $request->param('app_type');
        $agent = $agent ? : 'pc';
        switch ($agent) {
            case 'pc'://pc电脑端
                $request->agent = 'pc';
                break;
            case 'packapp'://普通H5网页
                $request->agent = 'h5';
                $user_agent = $request->header('user-agent');
                if(strpos($user_agent,'MicroMessenger') !== false){
                    if (strpos($user_agent, 'miniProgram') !== false) {
                        $request->agent = 'wechat_mini';
                    } else {
                        $request->agent = 'wechat_h5';
                    }
                }
                elseif(strpos($user_agent,'AlipayClient') !== false){
                    fdump('xxx', '$all_pay_channel', 1);
                    $request->agent = 'alipay';
                }
                break;
            case 'wxapp'://微信小程序
                $request->agent = 'wechat_mini';
                break;
            case 'ios'://IOS app
            case '1'://IOS app
                $request->agent = 'iosapp';
                break;
            case '2'://安卓 APP
            case 'android'://IOS app
                $request->agent = 'androidapp';
                break;
            case 'alipayapp'://支付宝网页
                $request->agent = 'alipay';
                break;
                //后续的环境继续加上...
            case 'dyapp':
                $request->agent = 'douyin_mini';
                break;
            default:
                $request->agent = 'pc';
                break;
        }
        return $next($request);
    }
}
