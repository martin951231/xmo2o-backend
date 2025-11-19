<?php
class Search_hotModel extends Model{
	/*得到热门搜索词列表*/
	public function get_list($limit=0,$type=-1,$is_wap=false){
		//手机版不显示带URL的关键词
		if($is_wap){
			$condition_hot_list['url'] = '';
		}
		if($type != -1){
			$condition_hot_list['type'] = $type;
		}
		$condition_hot_list['city_id'] = array('in', array(0, C("config.now_city")));
		$search_hot_list = $this->field(true)->order('`sort` DESC,`id` ASC')->where($condition_hot_list)->limit($limit)->select();
		foreach($search_hot_list as $key=>$value){
			if($category = M('Group_category')->where(array('cat_name'=>$value['name']))->find()){
				$search_hot_list[$key]['is_group_category'] = 1;
				$search_hot_list[$key]['cat_url'] = $category['cat_url'];
				$search_hot_list[$key]['go_url'] = C('config.site_url') . D('House_village')->base_url . 'pages/group/index/catPage?cat_fid='.$category['cat_id'] . '&cat_name='.$category['cat_name'];
			}else{
				$search_hot_list[$key]['is_group_category'] = 0;
				$search_hot_list[$key]['cat_url'] = '';
				$search_hot_list[$key]['go_url'] = '';
			}
			if(empty($value['url'])){
				if($is_wap){
					if($value['type'] == 0){
						$search_type = 'group';
					}else if($value['type'] == 1){
						$search_type = 'meal';
					}else if($value['type'] == 2){
						$search_type = 'shop';
					}else if($value['type'] == 3){
						$search_type = 'appoint';
					}
					$search_hot_list[$key]['url'] = U('Search/'.($search_type),array('w'=>urlencode($value['name'])));
				}else{
					if($value['type'] == 0){
						$search_hot_list[$key]['url'] = U(ucfirst('group').'/Search/index',array('w'=>urlencode($value['name'])));
					}else{
						$search_hot_list[$key]['url'] = U(ucfirst('meal').'/Search/index',array('w'=>urlencode($value['name'])));
					}
				}
			}
		}
		return $search_hot_list;
	}
}

?>