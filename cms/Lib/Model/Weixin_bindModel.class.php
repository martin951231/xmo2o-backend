<?php
class Weixin_bindModel extends Model
{

    public function get_tmp_qrcode($qrcode_id,$village_id)
    {
        $property_id = D('House_village')->where(['village_id'=>$village_id])->getField('property_id');
        $access_token = $this->get_access_token($property_id,2);
        $qrcode_url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token['access_token'];
        $post_data['expire_seconds'] = 2592000;
        $post_data['action_name'] = 'QR_SCENE';
        $post_data['action_info']['scene']['scene_id'] = $qrcode_id;
		
		import('ORG.Net.Http');
        $http = new Http();
        $json = $http->curlPost($qrcode_url,json_encode($post_data));
        if (!$json['errcode']){
            return(array('error_code'=>false,'id'=>$qrcode_id,'ticket'=>'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($json['ticket'])));
        }else{
            return(array('error_code'=>true,'msg'=>L_('发生错误：错误代码 ').$json['errcode'].L_('，微信返回错误信息：').$json['errmsg']));
        }
    }

	public function get_access_token($mer_id, $type = 0){
		$weixin_bind = $this->field(true)->where(array('mer_id' => $mer_id, 'type' => $type))->find();
		if (empty($weixin_bind)) return array('errcode' => 1, 'errmsg' => L_('公众号授权异常，请重新授权'));
		if ($weixin_bind['authorizer_access_token'] && $weixin_bind['authorizer_access_token_expires'] > time()) {
			$authorizer_access_token = $weixin_bind['authorizer_access_token'];
		} else {
			import('ORG.Net.Http');

			//获取component_access_token
			$url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
			
			if ($type == 0) {
				$data = array('component_appid' => C('config.wx_appid'), 'component_appsecret' => C('config.wx_appsecret'), 'component_verify_ticket' => C('config.wx_componentverifyticket'));
			} elseif ($type == 1) {
                $data = array('component_appid' => C('config.wx_house_appid'), 'component_appsecret' => C('config.wx_house_appsecret'), 'component_verify_ticket' => C('config.wx_house_componentverifyticket'));
            } elseif ($type == 2) {
                $data = array('component_appid' => C('config.wx_house_appid'), 'component_appsecret' => C('config.wx_house_appsecret'), 'component_verify_ticket' => C('config.wx_house_componentverifyticket'));
            }
			
			
			$result = Http::curlPost($url, json_encode($data));
			if ($result['errcode']) {
				return $result;
			} else {
				$component_access_token = $result['component_access_token'];
			}
			
			//利用刷新token 获取 authorizer_access_token
			$url = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=' . $component_access_token;
			$data = array();
			if ($type == 0) {
				$data['component_appid'] = C('config.wx_appid');
			} elseif ($type == 1) {
                $data['component_appid'] = C('config.wx_house_appid');
            } elseif ($type == 2) {
                $data['component_appid'] = C('config.wx_house_appid');
            }
			$data['authorizer_appid'] = $weixin_bind['authorizer_appid'];
			$data['authorizer_refresh_token'] = $weixin_bind['authorizer_refresh_token'];
			$access_data = Http::curlPost($url, json_encode($data));
			if ($access_data['errcode']) {
				return $access_data;
			} else {
				$authorizer_access_token = $access_data['authorizer_access_token'];
				$this->where(array('mer_id' => $mer_id, 'type' => $type))->data(array('authorizer_access_token'=>$access_data['authorizer_access_token'],'authorizer_access_token_expires'=>$access_data['expires_in'] + time()))->save();
			}
		}
		return array('errcode' => 0, 'access_token' => $authorizer_access_token);
	}
	public function get_component_access_token($type = 0){
		import('ORG.Net.Http');

		//获取component_access_token
		$url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
		if ($type == 0) {
			$data = array('component_appid' => C('config.wx_appid'), 'component_appsecret' => C('config.wx_appsecret'), 'component_verify_ticket' => C('config.wx_componentverifyticket'));
		} elseif ($type == 1) {
            $data = array('component_appid' => C('config.wx_house_appid'), 'component_appsecret' => C('config.wx_house_appsecret'), 'component_verify_ticket' => C('config.wx_house_componentverifyticket'));
        } elseif ($type == 2) {
            $data = array('component_appid' => C('config.wx_house_appid'), 'component_appsecret' => C('config.wx_house_appsecret'), 'component_verify_ticket' => C('config.wx_house_componentverifyticket'));
        }
		
		$result = Http::curlPost($url, json_encode($data));
		fdump_html($result,'merchant_get_component_access_token');
		if ($result['errcode']) {
			return '';
		} else {
			$component_access_token = $result['component_access_token'];
		}
		return $component_access_token;
	}
	
	public function get_account_type($mer_id, $type = 0)
	{
		// code  0：未认证的订阅号,1:认证的订阅号，2：未认证服务号，3认证服务号
		if ($weixin_bind = D('Weixin_bind')->where(array('mer_id' => $mer_id, 'type' => $type))->find()) {
			if ($weixin_bind['service_type_info'] == 0 || $weixin_bind['service_type_info'] == 1) {
				if ($weixin_bind['verify_type_info'] == -1) {
					return array('errcode' => 0, 'errmsg' => L_('未认证的订阅号'), 'code' => 0);
				}
				return array('errcode' => 0, 'errmsg' => L_('认证的订阅号'), 'code' => 1);
			} else {
				if ($weixin_bind['verify_type_info'] == -1) {
					return array('errcode' => 0, 'errmsg' => L_('未认证服务号'), 'code' => 2);
				}
				return array('errcode' => 0, 'errmsg' => L_('认证服务号'), 'code' => 3);
			}
		} else {
			return array('errcode' => 1, 'errmsg' => L_('公众号授权异常，请重新授权'));
		}
	}

    /**
     * 判断物业是否有公众号，如果有并且没有授权则返回用户授权登录页面
     * @param $property_id
     * @param $uid
     * @return array
     * @return array
     */
    public function is_property_weixin($property_id,$uid)
    {
        $res = $this->where(['mer_id'=>$property_id,'type'=>2])->find();
        if(!$res){
            return array('error'=>0);
        }else{
            $weixin_bind_user = M('Weixin_bind_user')->where(['uid'=>$uid,'property_id'=>$property_id,'type'=>2])->find();
            if(empty($weixin_bind_user) || empty($weixin_bind_user['openid']))
                return array('error'=>1001,'redirect_uri'=>'/wap.php?c=Packapp&a=property_weixin&property_id='.$property_id);
            else
                return array('error'=>0);
        }
    }
}
?>