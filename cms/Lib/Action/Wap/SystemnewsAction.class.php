<?php
	class SystemnewsAction extends BaseAction{
		public function index(){
			$news = M('System_news');
			$category = D('System_news_category')->where(array('status'=> 1))->order('sort DESC')->select();
			$condition_news['category_id'] = $category[0]['id'];
			$condition_news['status'] = 1;
			$condition_news['city_id'] = array(array('eq',$this->config['now_city']),array('eq',0), 'or');

			$page = $_GET['page'] ? intval($_GET['page']) : 1;
			$news = M('System_news');
			$return['count'] = $news->where(array('category_id'=> $category[0]['id'],'status'=>'1'))->count();
			if($return['count'] > ($page-1)*20){
				$return['news_list'] = $news->field('`id`,`title`,FROM_UNIXTIME(`add_time`, \'%Y-%m-%d %H:%i:%S\') AS add_date')->where($condition_news)->order('sort DESC,add_time DESC')->limit((($page-1)*10).',10')->select();
			}else{
				$return['news_list'] = array();
			}
			$return['totalPage'] = ceil($return['count']/10);
			$key = array_search($condition_news['category_id'],$category);
			$this->assign('now_cat',$category[$key]['name']);
			$this->assign("category",$category);
			$this->assign("now_cat_id",$category[0]['id']);
			$this->assign("count",$return['count']);
			$this->assign("news_list",$return['news_list']);
			$this->display();
		}

		public function ajaxList(){
			$this->header_json();
			$totalRows=20;
			$page = $_GET['page'] ? intval($_GET['page']) : 1;
			$news = M('System_news');
			
			$condition_news['category_id'] = $_GET['cat_id'];
			$condition_news['status'] = 1;
			$condition_news['city_id'] = array(array('eq',$this->config['now_city']),array('eq',0), 'or');
			
			$return['count'] = $news->where(array('category_id'=>$_GET['cat_id'],'status'=>'1'))->count();
			if($return['count'] > ($page-1)*$totalRows){
				$return['news_list'] = $news->field('`id`,`title`,FROM_UNIXTIME(`add_time`, \'%Y年%m月%d日\') AS add_date')->where($condition_news)->order('sort DESC,add_time DESC')->limit((($page-1)*$totalRows),$totalRows)->select();
			}else{
				$return['news_list'] = array();
			}
			$return['totalPage'] = ceil($return['count']/10);
			echo json_encode($return);
		}

		public function news(){
			$news = D('System_news')->where(array('id'=>$_GET['id']))->find();
			if(!empty($news) && !empty($news['content'])){
				$news['content'] = replace_file_domain($news['content']);

				$pattern_src = '/<img[\s\S]*?src\s*=\s*[\"|\'](.*?)[\"|\'][\s\S]*?>/';
				$num = preg_match_all($pattern_src, $news['content'], $match_src);
				if($num>0){
					$arr_src = $match_src['1'];
					$share_img = replace_file_domain($arr_src['0']);
				}
			}
			if(!isset($share_img)){
				if(C('config.wechat_share_img')){
					$share_img=C('config.wechat_share_img');
				}else{
					$share_img=C('config.site_logo');
				}
			}
			$news['content'] = replace_file_domain_content($news['content']);
			$this->assign('news',$news);
			$this->assign('share_img',$share_img);
			$this->display();
		}

	}