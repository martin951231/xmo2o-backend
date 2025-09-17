<?php
/*
 * 积分
 *
 */
class PointAction extends BaseAction {
    public function index(){
		
		//导航条
    	$web_index_slider = D('Slider')->get_slider_by_key('web_slider');
    	$this->assign('web_index_slider',$web_index_slider);
    	
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
		
		//余额记录列表
		$this->assign(D('User_score_list')->get_list($this->now_user['uid']));
		
		$this->display();
    }
}