<?php

namespace app\common\model\service\weixin;

use Exception;
use net\Http;
use think\facade\Db;

class UGCService
{
    //文字内容检查
    public static function msgSecCheck($openid, $content, $scene = 3)
    {
        //获取access_token
        $result = [
            'pass' => 1,
            'message' => '通过'
        ];
        try {
            if (empty($content) || empty($openid)) {
                throw new Exception('参数有误');
            }

            $accessTokenArr = (new AccessTokenWxappExpiresService())->getAccessToken();
            if ($accessTokenArr['errcode']) {
                throw new Exception($accessTokenArr['errmsg']);
            }
            $accessToken = $accessTokenArr['access_token'];
            $url = "https://api.weixin.qq.com/wxa/msg_sec_check?access_token=" . $accessToken;
            $data = [
                'content' => $content,
                'version' => 2,
                'scene' => $scene,
                'openid' => $openid
            ];
            $wxRS = Http::curlPostOwn($url, json_encode($data, JSON_UNESCAPED_UNICODE), 15, 'json');
            $wxRS = json_decode($wxRS, true);
            if ($wxRS['errcode'] != 0) {
                //请求失败
                throw new Exception($wxRS['errcode'] . ':' . $wxRS['errmsg']);
            }

            $suggest = $wxRS['result']['suggest'] ?? '';
            if ($suggest != 'pass') {
                throw new Exception("当前提交的内容含违规信息，请修改后重新提交");
            }
        } catch (Exception $e) {
            $result = [
                'pass' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $result;
    }

    //图片检查(异步的)
    public static function imgSecCheckAsync($openid, $imageUrl, $scene = 3)
    {
        //获取 access_token
        $result = [
            'pass' => 1,
            'message' => '通过'
        ];
        try {
            if (empty($imageUrl) || empty($openid)) {
                throw new Exception('参数有误');
            }

            $accessTokenArr = (new AccessTokenWxappExpiresService())->getAccessToken();
            if ($accessTokenArr['errcode']) {
                throw new Exception($accessTokenArr['errmsg']);
            }
            $accessToken = $accessTokenArr['access_token'];
            $url = "https://api.weixin.qq.com/wxa/media_check_async?access_token=" . $accessToken;
            $data = [
                'media_url' => $imageUrl,
                'media_type' => 2,
                'version' => 2,
                'scene' => $scene,
                'openid' => $openid
            ];
            $wxRS = Http::curlPostOwn($url, json_encode($data, JSON_UNESCAPED_UNICODE), 15, 'json');
            $wxRS = json_decode($wxRS, true);
            if ($wxRS['errcode'] != 0) {
                //请求失败
                throw new Exception($wxRS['errcode'] . ':' . $wxRS['errmsg']);
            }

            $traceId = $wxRS['trace_id'] ?? '';
            //根据traceId查询结果
            $leftSec = 5;
            while ($leftSec > 0) {
                $result = Db::name('wxapp_event_logs')->where('trace_id', $traceId)->value('result');
                $result = $result ? json_decode($result, true) : [];
                if ($result) {
                    if ($result['suggest'] != 'pass') {
                        throw new Exception("图片中含违规信息，请修改后重新上传");
                    }
                    break;
                }
                $leftSec--;
                sleep(1);
            }
        } catch (Exception $e) {
            $result = [
                'pass' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $result;
    }
}
