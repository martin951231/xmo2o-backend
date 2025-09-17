<?php
/**
 * App信息缓存
 */
class Appapi_app_configModel extends Model{
	public function get($field = '',$pack_id=0){
	    if ($pack_id && $pack_id>0) {
            $app_config = S('app_config_'.$pack_id);
        } else {
            $app_config = S('app_config');
        }
		if(empty($app_config)){
            if ($pack_id && $pack_id>0) {
                $appapi_app_config = $this->where(['pack_id' => $pack_id])->select();
            } else {
                $appapi_app_config = $this->select();
            }
			foreach($appapi_app_config as $value){
				$app_config[$value['var']] = replace_file_domain($value['value']);
			}
            if ($pack_id && $pack_id>0) {
                S('app_config_'.$pack_id,$app_config);
            } else {
                S('app_config',$app_config);
            }
		}
		if($field == ''){
			return $app_config;
		}else{
			return $app_config[$field];
		}
	}
	public function remove($pack_id=0){
        if ($pack_id && $pack_id>0) {
            S('app_config_'.$pack_id,null);
        } else {
            S('app_config',null);
        }
	}
}