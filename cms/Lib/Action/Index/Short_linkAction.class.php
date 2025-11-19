<?php
/*
 * 短链接
 *
 */
class Short_linkAction extends BaseAction{
    public function link(){
		if ($_GET['spread_uid'] > 0 && C('customization.user_spread_force_login') && empty($this->user_session)) {
			$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
			redirect(U('Wap/Login/index', $location_param));
			return false;
		}


		$link = M('Short_link')->where(['link_id' => $_GET['link_id']])->find();
		if($link){
			redirect($link['link_url']);
		}else{
			redirect($this->config['site_url']);
		}
    }
}