<?php
/*
 * 活动界面
 *
 * 采用空方法处理，可以自定义页面。
 * 
 */
class TopicAction extends BaseAction{
	public function _empty(){
		if(!$this->config['single_system']){
			//热门搜索词
			$search_hot_list = D('Search_hot')->get_list(12);
			$this->assign('search_hot_list',$search_hot_list);
			
		}
		
		if($this->config['group_alias_name']){
			//所有分类 包含2级分类
			$all_category_list = D('Group_category')->get_category();
			$this->assign('all_category_list',$all_category_list);
		}
		
		if(strpos(ACTION_NAME,'app_wap') !== false){
			$app_config = D('Appapi_app_config')->get();
            if(preg_match('/(iphone|ipad|ipod)/i', strtolower($_SERVER['HTTP_USER_AGENT']))){
                $app_config['isIos'] = true;
            }else{
                $app_config['isIos'] = false;
            }
			$this->assign('app_config',$app_config);

			$data = D('Appapi_pack_config')->select();
			foreach ($data as $val) {
				$app_pack_config[$val['var']] = $val['value'];
			}
			$this->assign('app_pack_config',$app_pack_config);
			
			$share = new WechatShare($this->config,$_SESSION['openid']);
			$this->shareScript = $share->getSgin();
			$this->assign('shareScript', $this->shareScript);
			$this->hideScript = $share->gethideOptionMenu();
			$this->assign('hideScript', $this->hideScript);
			
		}
	//	dump($app_config);
		
		$this->display(ACTION_NAME);
	}
}