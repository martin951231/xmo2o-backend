<?php

namespace express;
class Dada
{
    public $url = 'http://up.pigcms.cn/server.php?m=server&c=deliver';

    public $defaultData = array();

//     public function __construct($is_test = 1)
    public function __construct($is_test = 0)
    {
        $domain = $this->getTopDomain();
//         $domain = 'pigcms.com';
        $this->url .= '&domain=' . $domain . '&is_test=' . $is_test;
        $this->defaultData = array('app_key' => cfg('dada_app_key'), 'app_secret' => cfg('dada_app_secret'), 'mer_id' => cfg('dada_mer_id'));
    }

    public function cityList($data = array())
    {
        $data = array_merge($data, $this->defaultData);
        $this->url .= '&a=citylist';
        $rt = $this->curlPost($this->url, $data);
        return $rt;
    }

    public function addShop($data = array())
    {
        $data = array_merge($data, $this->defaultData);
        $this->url .= '&a=addshop';
        $rt = $this->curlPost($this->url, $data);
        return $rt;
    }

    public function addOrder($data = array())
    {
        //达达新增必填参数。不然报错
        $data['cargo_weight'] = 1;
        $data = array_merge($data, $this->defaultData);
        $data['callback'] = cfg('site_url') . '/index.php?g=Index&c=Dada&a=index';
        $this->url .= '&a=addorder';
        $rt = $this->curlPost($this->url, $data);
        return $rt;
    }

    public function reAddOrder($data = array())
    {
        $data = array_merge($data, $this->defaultData);
        $data['callback'] = cfg('site_url') . '/index.php?g=Index&c=Dada&a=index';
        $this->url .= '&a=reAddOrder';
        $rt = $this->curlPost($this->url, $data);
        return $rt;
    }

    public function formalCancel($data = array())
    {
        $data = array_merge($data, $this->defaultData);
        $this->url .= '&a=formalCancel';
        $rt = $this->curlPost($this->url, $data);
        return $rt;
    }

    public function cancleReasons($data = array())
    {
        $data = array_merge($data, $this->defaultData);
        $this->url .= '&a=cancelReasons';
        $rt = $this->curlPost($this->url, $data);
        return $rt;
    }

    public function orderHandle($data = array())
    {
        $data = array_merge($data, $this->defaultData);
        $this->url .= '&a=orderhandle';
        $rt = $this->curlPost($this->url, $data);
        return $rt;
    }

    private function curlPost($url, $data, $timeout = 15)
    {
        $ch = curl_init();
        $headers[] = "Accept-Charset: utf-8";//"Content-Type: multipart/form-data; boundary=" .  uniqid('------------------');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $result = curl_exec($ch);

        //关闭curl
        curl_close($ch);
        $result = json_decode($result, true);
        fdump_sql([$data, $url, $result], 'dada_curl');
        return $result;
    }

    private function getTopDomain()
    {
        $host = $_SERVER['HTTP_HOST'];
        $host = strtolower($host);
        if (strpos($host, '/') !== false) {
            $parse = @parse_url($host);
            $host = $parse['host'];
        }
        $topleveldomaindb = array('com', 'edu', 'gov', 'int', 'mil', 'net', 'org', 'biz', 'info', 'pro', 'name', 'museum', 'coop', 'aero', 'xxx', 'idv', 'mobi', 'cc', 'me');
        $str = '';
        foreach ($topleveldomaindb as $v) {
            $str .= ($str ? '|' : '') . $v;
        }
        $matchstr = "[^\.]+\.(?:(" . $str . ")|\w{2}|((" . $str . ")\.\w{2}))$";
        if (preg_match("/" . $matchstr . "/ies", $host, $matchs)) {
            $domain = $matchs['0'];
        } else {
            $domain = $host;
        }
        return $domain;
    }
}