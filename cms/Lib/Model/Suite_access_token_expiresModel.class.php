<?php
class Suite_access_token_expiresModel extends Model
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
            $access_obj = $this->get_suite_token($this->config['enterprise_wx_suite_ticket'],$suiteid,$access_obj);
            if ($access_obj && $access_obj['suite_access_token']) {
                return array('errcode' => 0, 'suite_access_token' => $access_obj['suite_access_token']);
            } else {
                return array('errcode' => $access_obj['errcode'], 'errmsg' => $access_obj['errmsg']);
            }
        } else {
            return array('errcode' => 0, 'suite_access_token' => $access_obj['suite_access_token']);
        }
    }


    private function get_suite_token($suite_ticket,$suiteid='',$access_obj=array())
    {
        import('ORG.Net.Http');
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_suite_token';
        if (!$suiteid) {
            $suiteid = $this->config['enterprise_wx_provider_suiteid'];
        }
        $data = array('suite_id' => $suiteid, 'suite_secret' => $this->config['enterprise_wx_provider_secret'], 'suite_ticket' => $suite_ticket);
        $result = Http::curlPost($url, json_encode($data));
        if ($result['errcode']) {
            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/enterprise_get_access_token_err_log',true);
            fdump($data,'api/log/'.date('Ymd').'/enterprise_get_access_token_err_log',true);
            fdump($result,'api/log/'.date('Ymd').'/enterprise_get_access_token_err_log',true);
            return $result;
        } elseif ($result['suite_access_token']) {
            $now = intval(time());
            $data_add = array(
                'suiteid' => $suiteid,
                'suite_access_token' => $result['suite_access_token'],
                'expires_in' => intval($result['expires_in'] + $now)
            );
            if (!$access_obj) {
                $access_obj = M('Suite_access_token_expires')->where(array('suiteid' => $suiteid))->find();
            }
            if ($access_obj) {
                M('Suite_access_token_expires')->where(array('id' => $access_obj['id']))->save($data_add);
            } else {
                M('Suite_access_token_expires')->add($data_add);
            }
        }
        return $result;
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