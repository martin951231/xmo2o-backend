<?php

namespace douyin;

use think\Exception;
use think\facade\Cache;

class Douyin
{
    private $clientKey = '';
    private $clientSecret = '';

    public function __construct()
    {
        $this->clientKey = cfg('douyin_h5_client_key');
        $this->clientSecret = cfg('douyin_h5_client_secret');
    }

    public function getUserInfoByCode($code)
    {
        //获取
        $url = 'https://open.douyin.com/oauth/access_token/';
        $data = [
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_key' => $this->clientKey
        ];
        $res = http_request($url, 'POST', $data)[1];
        $result = json_decode($res, true);
        if ($result['message'] != 'success') {
            throw new Exception($result['data']['description'] ?? '获取用户信息失败，请重试');
        }

        $url = 'https://open.douyin.com/oauth/userinfo/';
        $data = [
            'access_token' => $result['data']['access_token'],
            'open_id' => $result['data']['open_id']
        ];
        $header = [
            'Content-Type:application/json'
        ];
        $response = http_request($url, 'POST', json_encode($data), $header)[1];
        $response = json_decode($response, true);
        if ($response['message'] != 'success' || empty($response['data']['union_id'])) {
            throw new Exception($result['data']['description'] ?? '获取用户信息失败，请重试');
        }
        return $response;
    }

    public function getShareId()
    {
        $clientTokenRs = $this->getClinetToken();
        $url = 'https://open.douyin.com/share-id/?need_callback=true';
        $header = [
            'Content-Type: application/json',
            'access-token:' . $clientTokenRs['data']['access_token']
        ];
        $res = http_request($url, 'GET', [], $header)[1];
        $result = json_decode($res, true);
        if ($result['data']['error_code'] != 0) {
            throw new Exception($result['data']['description'] ?? '接口请求失败');
        }
        return ['share_id' => $result['data']['share_id']];
    }

    private function getClinetToken()
    {
        $url = 'https://open.douyin.com/oauth/client_token/';
        $data = [
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credential',
            'client_key' => $this->clientKey
        ];
        $res = http_request($url, 'POST', $data)[1];
        $result = json_decode($res, true);
        if ($result['message'] != 'success') {
            throw new Exception($result['data']['description'] ?? '获取用户信息失败，请重试');
        }
        return $result;
    }

    public function getShareParams()
    {
        $clientToken = $this->getClinetToken();
        $url = 'https://open.douyin.com/open/getticket/';
        $header = [
            'Content-Type: application/json',
            'access-token:' . $clientToken['data']['access_token']
        ];
        $res = http_request($url, 'GET', [], $header)[1];
        $result = json_decode($res, true);
        $ticket = $result['data']['ticket'];
        $nonce_str = 'Wm3WZYTPz0wzccnW';
        $timestamp = time();

        $sign = md5('nonce_str=' . $nonce_str . '&ticket=' . $ticket . '&timestamp=' . $timestamp);
        $rs = [
            'nonce_str' => $nonce_str,
            'timestamp' => $timestamp,
            'ticket' => $ticket,
            'signature' => $sign,
            'client_key' => $this->clientKey
        ];
        return $rs;
    }

    /**
     * 获取jsapi ticket
     *
     * @return void
     * @date: 2023/07/31
     */
    public function getJsTicket()
    {
        $cacheKey = md5($this->clientKey . $this->clientSecret);
        $cacheTicket = Cache::get($cacheKey);
        if ($cacheTicket) {
            return $cacheTicket;
        } else {
            $clientToken = $this->getClinetToken();
            $url = 'https://open.douyin.com/js/getticket/';
            $header = [
                'Content-Type: application/json',
                'access-token:' . $clientToken['data']['access_token']
            ];
            $res = http_request($url, 'GET', [], $header)[1];
            $result = json_decode($res, true);
            $ticket = $result['data']['ticket'] ?? '';
            $expiresIn = $result['data']['expires_in'] ?? 0;

            if ($ticket) {
                Cache::set($cacheKey, $ticket, $expiresIn - 3600);
                return $ticket;
            } else {
                throw new Exception($result['data']['description'] ?? '获取js-ticket失败');
            }
        }
    }
}