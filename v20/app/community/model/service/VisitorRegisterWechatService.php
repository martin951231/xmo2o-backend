<?php
namespace app\community\model\service;

use think\facade\Cache;

class VisitorRegisterWechatService
{
    private function key()
    {
        $key = base64_decode((string)config('visitor_register.encryption_key'), true);
        if ($key === false || strlen($key) !== 32) { throw new \InvalidArgumentException('请配置服务端 VISITOR_REGISTER.ENCRYPTION_KEY（32字节随机密钥的Base64值）'); }
        return $key;
    }

    public function encrypt($secret)
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($secret, 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) { throw new \RuntimeException('凭证加密失败'); }
        return base64_encode($iv . $tag . $cipher);
    }

    public function decrypt($encrypted)
    {
        $raw = base64_decode($encrypted, true);
        if ($raw === false || strlen($raw) < 29) { throw new \InvalidArgumentException('小程序凭证无效，请重新保存配置'); }
        $secret = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        if ($secret === false) { throw new \InvalidArgumentException('小程序凭证解密失败，请检查服务端密钥'); }
        return $secret;
    }

    private function cacheKey($config)
    {
        return 'visitor-register:token:' . $config['village_id'] . ':' . $config['version'];
    }

    private function cacheStore()
    {
        try { $store = Cache::store('redis'); $store->get('visitor-register:probe'); return $store; }
        catch (\Throwable $e) { return Cache::store('file'); }
    }

    public function clear($config)
    {
        foreach (['redis', 'file'] as $name) {
            try { Cache::store($name)->delete($this->cacheKey($config)); } catch (\Throwable $e) { /* Old version keys cannot be reused. */ }
        }
    }

    public function token($config, $force = false)
    {
        $store = $this->cacheStore();
        $key = $this->cacheKey($config);
        if (!$force && ($token = $store->get($key))) { return $token; }
        $raw = $this->post('https://api.weixin.qq.com/cgi-bin/stable_token', [
            'grant_type' => 'client_credential', 'appid' => $config['appid'],
            'secret' => $this->decrypt($config['secret_encrypted']), 'force_refresh' => false,
        ]);
        $data = json_decode($raw, true);
        $this->checkError($data);
        if (empty($data['access_token']) || empty($data['expires_in'])) { throw new \InvalidArgumentException('微信返回的令牌数据无效'); }
        $store->set($key, $data['access_token'], max(1, intval($data['expires_in']) - 300));
        return $data['access_token'];
    }

    public function qrcode($config, $page, $scene)
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $token = $this->token($config, $attempt > 0);
            $raw = $this->post('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . rawurlencode($token), [
                'page' => $page, 'scene' => $scene, 'width' => 430, 'env_version' => 'release', 'check_path' => true,
            ]);
            $data = json_decode($raw, true);
            if (is_array($data)) {
                if ($attempt === 0 && in_array(intval($data['errcode'] ?? 0), [40001, 40014, 42001], true)) { $this->clear($config); continue; }
                $this->checkError($data);
                throw new \InvalidArgumentException('微信未返回二维码图片');
            }
            $info = @getimagesizefromstring($raw);
            if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg'], true)) { throw new \InvalidArgumentException('微信二维码图片无效'); }
            return ['bytes' => $raw, 'extension' => $info['mime'] === 'image/png' ? 'png' : 'jpg'];
        }
        throw new \InvalidArgumentException('微信令牌失效，请重试');
    }

    public function openid($config, $code)
    {
        $data = json_decode($this->get('https://api.weixin.qq.com/sns/jscode2session?' . http_build_query([
            'appid' => $config['appid'], 'secret' => $this->decrypt($config['secret_encrypted']),
            'js_code' => $code, 'grant_type' => 'authorization_code',
        ])), true);
        $this->checkError($data);
        if (empty($data['openid']) || !is_string($data['openid'])) { throw new \InvalidArgumentException('微信登录失败，请重新进入登记页面'); }
        return $data['openid'];
    }

    public function phone($config, $code)
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $data = json_decode($this->post('https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=' . rawurlencode($this->token($config, $attempt > 0)), ['code' => $code]), true);
            if ($attempt === 0 && in_array(intval($data['errcode'] ?? 0), [40001, 40014, 42001], true)) { $this->clear($config); continue; }
            $this->checkError($data);
            $info = $data['phone_info'] ?? [];
            if (($info['watermark']['appid'] ?? '') !== $config['appid'] || !preg_match('/^1[3-9][0-9]{9}$/D', $info['purePhoneNumber'] ?? '')) {
                throw new \InvalidArgumentException('请授权有效的中国大陆手机号');
            }
            return $info['purePhoneNumber'];
        }
        throw new \InvalidArgumentException('手机号授权失效，请重试');
    }

    protected function get($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $status !== 200) { throw new \InvalidArgumentException('微信连接失败，请检查网络后重试'); }
        return $raw;
    }

    private function checkError($data)
    {
        if (!is_array($data)) { throw new \InvalidArgumentException('微信响应格式无效'); }
        if (!empty($data['errcode'])) {
            $code = intval($data['errcode']);
            $messages = [40013 => 'AppID无效', 40125 => 'AppSecret无效', 40164 => '请将服务器IP加入微信白名单', 41030 => '请先发布包含登记页面的小程序正式版', 45009 => '微信调用次数超限'];
            throw new \InvalidArgumentException('微信接口错误(' . $code . ')：' . ($messages[$code] ?? '请检查微信配置后重试'));
        }
    }

    protected function post($url, $body)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // Never surface transport errors containing credential-bearing URLs.
        if ($raw === false || $status !== 200) { throw new \InvalidArgumentException('微信连接失败，请检查网络后重试'); }
        return $raw;
    }
}
