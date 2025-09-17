<?php
/**
 * 用户访问到期时间
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/10/22
 */

namespace app\common\model\service\weixin;
use app\common\model\db\AccessTokenWxappExpires as AccessTokenWxappExpiresModel;
use net\Http as Http;
class AccessTokenWxappExpiresService{
    public $accessTokenWxappExpiresModel = null;
    public function __construct()
    {
        $this->accessTokenWxappExpiresModel = new AccessTokenWxappExpiresModel();
    }

    public function getAccessToken()
	{
        $access_obj = $this->getOne();
        $now = time();
        if ($access_obj && intval($access_obj['expires_in']) > ($now + 1200)) {
            $url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=' . $access_obj['access_token'];
            $json = json_decode($this->curlGet($url));
            if (isset($json->ip_list)) {
                return array('errcode' => 0, 'access_token' => $access_obj['access_token']);
            }
        }

        $appid = cfg('pay_wxapp_appid');
        $appsecret = cfg('pay_wxapp_appsecret');
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.trim($appid).'&secret='.trim($appsecret);
        $json = json_decode($this->curlGet($url));
        if (isset($json->access_token) && $json->access_token) {
            if ($access_obj) {
                $saveData =[
                    'access_token' => $json->access_token,
                    'expires_in' => intval($json->expires_in + $now)
                ];
                $res = $this->updateById($access_obj['id'],$saveData);
            } else {

                $saveData =[
                    'access_token' => $json->access_token,
                    'expires_in' => intval($json->expires_in + $now)
                ];
                $this->save($saveData);
            }
            return array('errcode' => 0, 'access_token' => $json->access_token);
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

    /**
     * 获取一条数据
     * @return array
     */
	public function getOne(){
		$accessTokenExpires = $this->accessTokenWxappExpiresModel->getOne();
		if(!$accessTokenExpires) {
            return [];
        }
		return $accessTokenExpires->toArray();
	}

	
    /**
     * 更新数据
     * @param $id
     * @param $data
     * @return bool
     */
	public function updateById($id,$data){
        if (!$id || !$data) {
            return false;
		}
		
		try {
			$result = $this->accessTokenWxappExpiresModel->updateById($id,$data);
        }catch (\Exception $e) {
			return false;
        }
		
		return $result;
	}

	
	
    /**
     * 新增数据
     * @param $data
     * @return bool|intval
     */
	public function save($data){
        if (!$data) {
            return false;
		}
		
		try {
			$result = $this->accessTokenWxappExpiresModel->add($data);
        }catch (\Exception $e) {
			return false;
        }
		
		return $this->accessTokenExpiresModel->id;
	}

}