<?php
/**
 * 用户访问到期时间
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/18 11:05
 */

namespace app\common\model\service\weixin;
use app\common\model\db\AccessTokenExpires as AccessTokenExpiresModel;
use net\Http as Http;
class AccessTokenExpiresService{
    public $accessTokenExpiresModel = null;
    public function __construct()
    {
        $this->accessTokenExpiresModel = new AccessTokenExpiresModel();
    }

    public function getAccessToken()
	{
		if(cfg('open_youshanzhu_login') == 1){
			$url = cfg('youshanzhu_login_url').'/plugins/login_wx_client/index/get_access_token';

			$return = http_request($url,'GET','',array('Token:eb0ed7aaee01c4b922d8'));
			$return = json_decode($return[1]);

			return ['errcode' => 0, 'access_token' =>$return['access_token']];
		}

		$access_obj = $this->getOne();
		
		$httpObj = new Http();
		
		$now = intval(time());
		if ($access_obj && intval($access_obj['expires_in']) > ($now + 1200)) {
			$url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=' . $access_obj['access_token'];
			$json = json_decode($httpObj->curlGet($url));
			if (isset($json->ip_list)) {
				return array('errcode' => 0, 'access_token' => $access_obj['access_token']);
			}
		}
		
		$appid = cfg('wechat_appid');
		$appsecret = cfg('wechat_appsecret');
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.trim($appid).'&secret='.trim($appsecret);
		$json = json_decode($httpObj->curlGet($url));
		if (isset($json->access_token) && $json->access_token) {
			$saveData = [
				'access_token' => $json->access_token,
				'expires_in' => intval($json->expires_in + $now)
			];
			if ($access_obj) {
				$res = $this->updateById($access_obj['id'],$saveData);
			} else {
				$res = $this->save($saveData);
			}
			return array('errcode' => 0, 'access_token' => $json->access_token);
		} else {
			return array('errcode' => $json->errcode, 'errmsg' => $json->errmsg);
		}
	}
	

    /**
     * 获取一条数据
     * @return array
     */
	public function getOne(){
		$accessTokenExpires = $this->accessTokenExpiresModel->getOne();
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
			$result = $this->accessTokenExpiresModel->updateById($id,$data);
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
			$result = $this->accessTokenExpiresModel->add($data);
        }catch (\Exception $e) {
			return false;
        }
		
		return $this->accessTokenExpiresModel->id;
	}

}