<?php
class Pre_auth_code_expires_expiresModel extends Model
{
    public function get_access_token($suiteid='')
    {
        $where = array();
        if (!$suiteid) {
            $suiteid = $this->config['enterprise_wx_provider_suiteid'];
        }
        $where['suiteid'] = $suiteid;
        $access_obj = $this->where($where)->field(true)->find();
        $now = intval(time());
        if ($access_obj && intval($access_obj['expires_in']) > ($now + 1200)) {
            return array('errcode' => 0, 'pre_auth_code' => $access_obj['pre_auth_code']);
        }
        $suite_access_token_msg = D('Suite_access_token_expires')->get_access_token($suiteid);
        if (!empty($suite_access_token_msg['errcode'])) {
            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/pre_auth_code_expires_expires_err_log',true);
            fdump($suiteid,'api/log/'.date('Ymd').'/pre_auth_code_expires_expires_err_log',true);
            fdump($suite_access_token_msg,'api/log/'.date('Ymd').'/pre_auth_code_expires_expires_err_log',true);
            return array('errcode' => $suite_access_token_msg['errcode'], 'errmsg' => $suite_access_token_msg['errmsg']);
        }
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_pre_auth_code?suite_access_token='.$suite_access_token_msg['suite_access_token'];
        $json = json_decode($this->curlGet($url));
        if (isset($json->pre_auth_code) && $json->pre_auth_code) {
            if ($access_obj) {
                $this->where(array('id' => $access_obj['id']))->save(array('suiteid' => $suiteid, 'pre_auth_code' => $json->pre_auth_code, 'expires_in' => intval($json->expires_in + $now)));
            } else {
                $this->add(array('suiteid' => $suiteid, 'pre_auth_code' => $json->pre_auth_code, 'expires_in' => intval($json->expires_in + $now)));
            }
            return array('errcode' => 0, 'pre_auth_code' => $json->pre_auth_code);
        } else {
            return array('errcode' => $json->errcode, 'errmsg' => $json->errmsg);
        }
    }

    function curlGet($url){
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }

}
?>