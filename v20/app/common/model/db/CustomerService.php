<?php

namespace app\common\model\db;

use think\Model;

class CustomerService extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getUrl($im_appid, $im_appkey, $im_url, $openid, $mer_id){
    	if(empty($im_appid) || empty($im_appkey) || empty($openid) || empty($mer_id)) return '';
    	$where = [
    		['mer_id', '=', $mer_id]
    	];
    	$data = $this->getOne($where);
    	if(empty($data)) return '';
    	
    	$key = $this->get_encrypt_key(array('app_id'=>$im_appid,'openid' => $openid), $im_appkey);
        $kf_url = ($im_url ? $im_url : 'http://im-link.weihubao.com').'/?app_id=' . $im_appid . '&openid=' . $openid . '&key=' . $key . '#serviceList_' . $mer_id;
        return $kf_url;
    }

    private function get_encrypt_key($array, $app_key)
    {
        $new_arr = array();
        ksort($array);
        foreach ($array as $key => $value) {
            $new_arr[] = $key . '=' . $value;
        }
        $new_arr[] = 'app_key=' . $app_key;

        $string = implode('&', $new_arr);
        return md5($string);
    }
}