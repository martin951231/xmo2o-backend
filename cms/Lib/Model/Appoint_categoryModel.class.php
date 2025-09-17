<?php
class Appoint_categoryModel extends Model{
    /*得到分类*/
	public function get_category($get_all=true,$cat_fid=0,$limit_num=0,$category_limit=1){
		if(empty($limit_num)){
			$limit_num = '';
		}
		if($get_all){
			$condition_group_category['cat_status'] = '1';
			$tmp_group_category = $this->field('pc_content,wap_content',true)->where($condition_group_category)->order('`cat_sort` DESC')->limit($limit_num)->select();
			$group_category = array();
			$tmp_category = array();
			foreach($tmp_group_category as $key=>$value){
				if(empty($value['cat_fid'])){
					$tmp_category[$value['cat_id']] = $key;

					$value['cat_count'] = 0;
					$value['url'] = $this->get_f_category_url($value);
					if(!empty($value['cat_pic'])){
                        if(strpos($value['cat_pic'],'upload/system/') !== false) {
                            $value['cat_pic'] = replace_file_domain($value['cat_pic']);
                        } else {
                            $value['cat_pic'] = file_domain().'/upload/system/'.$value['cat_pic'];
                        }
					}
					$group_category[$key] = $value;
					unset($tmp_group_category[$key]);
				}
			}
			foreach($tmp_group_category as $key1=>$value1){
				
				if(!empty($value1['cat_pic'])){
                    if(strpos($value1['cat_pic'],'upload/system/') !== false) {
                        $value1['cat_pic'] = replace_file_domain($value1['cat_pic']);
                    } else {
                        $value1['cat_pic'] = file_domain().'/upload/system/'.$value1['cat_pic'];
                    }
				}
				if(!empty($value1['cat_big_pic'])){
                    if(strpos($value1['cat_big_pic'],'upload/system/') !== false) {
                        $value1['cat_big_pic'] = replace_file_domain($value1['cat_big_pic']);
                    } else {
                        $value1['cat_big_pic'] = file_domain().'/upload/system/'.$value1['cat_big_pic'];
                    }
				}
                $value1['url'] = $this->get_category_url($value1);
				if (isset($group_category[$tmp_category[$value1['cat_fid']]])) {
				    $group_category[$tmp_category[$value1['cat_fid']]]['cat_count'] += 1;
				    $group_category[$tmp_category[$value1['cat_fid']]]['category_list'][$key1] = $value1;
				}
			}
			$web_category_show_limit = C('config.web_category_show_limit');
			$web_category_show_limit = 0;
			if($web_category_show_limit && $category_limit){
				$new_group_category = array();
				$i = 1;
				foreach($group_category as $key=>$value2){
					if($i > $web_category_show_limit){
						break;
					}else{
						$new_group_category[] = $value2;
					}
					$i++;
				}
				$group_category = $new_group_category;
			}			
			return $group_category;
		}else{
			$condition_group_category['cat_status'] = '1';
			$condition_group_category['cat_fid'] = $cat_fid;
			$tmp_group_category = $this->field(true)->where($condition_group_category)->order('`cat_sort` DESC')->limit($limit_num)->select();
			foreach($tmp_group_category as &$value3){
					$value3['url'] = $this->get_category_url($value3);
			}
			return $tmp_group_category;
		}
	}

    /*得到分类-新接口*/
    public function get_category_new($get_all=true,$cat_fid=0,$limit_num=0,$two_category=4, $is_autotrophic=[0,1,2],$field='cat_id, cat_fid, cat_name, cat_pic, cat_big_pic, cat_url, bg_color, desc,is_autotrophic'){
        if(empty($limit_num)){
            $limit_num = '';
        }
        if($get_all){
            $condition_group_category['cat_status'] = '1';
            $tmp_group_category = $this->field($field)->where($condition_group_category)->order('`cat_sort` DESC')->limit($limit_num)->select();
            $group_category = array();
            $tmp_category = array();
            foreach($tmp_group_category as $key=>$value){
                if(empty($value['cat_fid'])){
                    $tmp_category[$value['cat_id']] = $key;

                    $value['cat_count'] = 0;
                    $value['url'] = $this->get_f_category_new_url($value);
                    if(!empty($value['cat_pic'])){
                        if(strpos($value['cat_pic'],'upload/system/') !== false) {
                            $value['cat_pic'] = replace_file_domain($value['cat_pic']);
                        } else {
                            $value['cat_pic'] = file_domain().'/upload/system/'.$value['cat_pic'];
                        }
                    }
                    if (!$value['bg_color'] || $value['bg_color']=='') {
                        $value['bg_color'] = C('config.mobile_head_color');
                    }
                    $group_category[$key] = $value;
                    unset($tmp_group_category[$key]);
                }
            }
            foreach($tmp_group_category as $key1=>$value1){
                if(!empty($value1['cat_pic'])){
                    if(strpos($value1['cat_pic'],'upload/system/') !== false) {
                        $value1['cat_pic'] = replace_file_domain($value1['cat_pic']);
                    } else {
                        $value1['cat_pic'] = file_domain().'/upload/system/'.$value1['cat_pic'];
                    }
                }
                if(!empty($value1['cat_big_pic'])){
                    if(strpos($value1['cat_big_pic'],'upload/system/') !== false) {
                        $value1['cat_big_pic'] = replace_file_domain($value1['cat_big_pic']);
                    } else {
                        $value1['cat_big_pic'] = file_domain().'/upload/system/'.$value1['cat_big_pic'];
                    }
                }
                $value1['url'] = $this->get_category_new_url($value1);
                if (isset($group_category[$tmp_category[$value1['cat_fid']]])) {
                    $group_category[$tmp_category[$value1['cat_fid']]]['cat_count'] += 1;
                    if ($two_category && count($group_category[$tmp_category[$value1['cat_fid']]]['category_list'])<$two_category) {
                        $group_category[$tmp_category[$value1['cat_fid']]]['category_list'][$key1] = $value1;
                    } elseif (!$two_category || !$group_category[$tmp_category[$value1['cat_fid']]]['category_list']) {
                        $group_category[$tmp_category[$value1['cat_fid']]]['category_list'][$key1] = $value1;
                    }
                }
            }
			
			foreach($group_category as $gc_key => $gc_value){
				$group_category[$gc_key]['category_list'] = array_values($gc_value['category_list']);
			}
			$group_category = array_values($group_category);
			
            return $group_category;
        }else{
            $condition_group_category['cat_status'] = '1';
            $condition_group_category['cat_fid'] = $cat_fid;
			$condition_group_category['is_autotrophic'] = array('in', $is_autotrophic);
            $tmp_group_category = $this->field($field)->where($condition_group_category)->order('`cat_sort` DESC')->limit($limit_num)->select();
            foreach($tmp_group_category as &$value1){
                $value1['url'] = $this->get_category_new_url($value1);
                if(!empty($value1['cat_pic'])){
                    if(strpos($value1['cat_pic'],'upload/system/') !== false) {
                        $value1['cat_pic'] = replace_file_domain($value1['cat_pic']);
                    } else {
                        $value1['cat_pic'] = file_domain().'/upload/system/'.$value1['cat_pic'];
                    }
                }
                if(!empty($value1['cat_big_pic'])){
                    if(strpos($value1['cat_big_pic'],'upload/system/') !== false) {
                        $value1['cat_big_pic'] = replace_file_domain($value1['cat_big_pic']);
                    } else {
                        $value1['cat_big_pic'] = file_domain().'/upload/system/'.$value1['cat_big_pic'];
                    }
                }
            }
            return $tmp_group_category;
        }
    }

    /* 得到主分类的URL */
    protected function get_f_category_new_url($category){
        $appoint_base_url = D('Appoint')->appoint_base_url;
//        return C('config.site_url').'/appoint/category/#'.$category['cat_url'];
        $param = '';
        if ($_GET['now_city']) {
            $param .= "&now_city={$_GET['now_city']}";
        } else {
            $param .= "&now_city=".C('config.now_city');
        }
        if ($_GET['wxappp_version']) {
            $param .= "&wxappp_version={$_GET['wxappp_version']}";
        }
        return C('config.site_url').$appoint_base_url.'pages/appoint/category?cat_url='.$category['cat_url'].$param;
    }
    /* 得到分类的URL */
    protected function get_category_new_url($category){
        $appoint_base_url = D('Appoint')->appoint_base_url;
		if (2==$category['is_autotrophic']) {
			return C('config.site_url').$appoint_base_url.'pages/appoint/goods/goodsDetail?cat_url='.$category['cat_url'];
		}
        $param = '';
        if ($_GET['now_city']) {
            $param .= "&now_city={$_GET['now_city']}";
        } else {
            $param .= "&now_city=".C('config.now_city');
        }
        if ($_GET['wxappp_version']) {
            $param .= "&wxappp_version={$_GET['wxappp_version']}";
        }
        return C('config.site_url').$appoint_base_url.'pages/appoint/goods/goodsList?cat_url='.$category['cat_url'].$param;
    }


	/*得到列表所有分类*/
	public function get_all_category(){
		$condition_group_category['cat_status'] = '1';
		$tmp_group_category = $this->field(true)->where($condition_group_category)->order('`cat_sort` DESC')->limit()->select();
		$group_category = array();
		$tmp_category = array();
		foreach($tmp_group_category as $key=>$value){
			if(empty($value['cat_fid'])){
				$tmp_category[$value['cat_id']] = $key;
				
				$value['cat_count'] = 0;
				$value['url'] = $this->get_category_url($value);
				
				$group_category[$key] = $value;
				unset($tmp_group_category[$key]);
			}
		}
		foreach($tmp_group_category as $key=>$value){
			$value['url'] = $this->get_category_url($value);
			
			$group_category[$tmp_category[$value['cat_fid']]]['cat_count'] += 1;
			$group_category[$tmp_category[$value['cat_fid']]]['category_list'][$key] = $value;
		}
		foreach($group_category as $key=>$value){
			if(empty($value['cat_id'])){
				unset($group_category[$key]);
			}
		}
		return $group_category;

	}
	
	/*根据cat_url得到分类*/
	public function get_category_by_catUrl($cat_url){
		$condition_group_category['cat_url'] = $cat_url;
		$condition_group_category['cat_status'] = '1';
		$now_category = $this->field(true)->where($condition_group_category)->find();
		if(!empty($now_category)){
			$now_category['url'] = $this->get_category_url($now_category);
		}
		return $now_category;
	}
	
	/*根据cat_id得到分类*/
	public function get_category_by_id($cat_id){
		$condition_group_category['cat_id'] = $cat_id;
		$condition_group_category['cat_status'] = '1';
		$now_category = $this->field(true)->where($condition_group_category)->find();
		if(!empty($now_category)){
			$now_category['url'] = $this->get_category_url($now_category);
		}
		return $now_category;
	}
	
	/*根据顶级ID或子分类ID 得到子分类或子分类的同级分类*/
	public function get_son_category_list_byid($cat_fid,$cat_id){
		if(!empty($cat_fid)){
			$son_category_list = $this->get_son_category_list_byfid($cat_fid);
		}else{
			$son_category_list = $this->get_son_category_list_bycid($cat_id);
		}
		
		foreach($son_category_list as $key=>$value){
			$son_category_list[$key]['url'] = $this->get_category_url($value);
		}
		return $son_category_list;
	}
	/*根据顶级ID获得子分类列表*/
	public function get_son_category_list_byfid($cat_fid){
		$condition_group_category['cat_fid'] = $cat_fid;
		$condition_group_category['cat_status'] = '1';
		return $this->field(true)->where($condition_group_category)->order('`cat_sort` DESC')->select();
	}
	/*根据子分类ID获得同级分类列表*/
	public function get_son_category_list_bycid($cat_id){
		$condition_group_category['cat_fid'] = $cat_id;
		$condition_group_category['cat_status'] = '1';
		return $this->field(true)->where($condition_group_category)->order('`cat_sort` DESC')->select();
	}
	/* 得到主分类的URL */
	protected function get_f_category_url($category){
		return C('config.site_url').'/appoint/category/#'.$category['cat_url'];
	}
	/* 得到分类的URL */
	protected function get_category_url($category){
		return C('config.site_url').'/appoint/category/'.$category['cat_url'];
	}
        
        
        public function appoint_category_edit($where,$data){
            if(!$where || !$data){
                return false;
            }

            $insert_id = $this->where($where)->data($data)->save();
            if($insert_id){
                return array('status'=>1,'msg'=>L_('修改成功！'));
            }else{
                return array('status'=>0,'msg'=>L_('修改失败！'));
            }
        }
        
        
        public function appoint_category_detail($where,$field = true){
            if(!$where){
                return false;
            }
            
            $detail = $this->where($where)->field($field)->find();
            if($detail['pc_title']){
                $detail['pc_title'] = unserialize($detail['pc_title']);
            }
            
            if($detail['wap_title']){
                $detail['wap_title'] = unserialize($detail['wap_title']);
            }
            
            if($detail['pc_content']){
                $detail['pc_content'] = unserialize($detail['pc_content']);
            }
            
            if($detail['wap_content']){
                $detail['wap_content'] = unserialize($detail['wap_content']);
            }

            if($detail){
                return array('status'=>1,'detail'=>$detail);
            }else{
                return array('status'=>0,'detail'=>$detail);
            }
        }
        
        // 二维码
	public function get_qrcode($cat_id){
		$where['cat_id'] = $cat_id;
		$now_group = $this->field('`cat_id`,`qrcode_id`')->where($where)->find();
		if(empty($now_group)){
			return false;
		}
		return $now_group;
	}
	public function save_qrcode($cat_id,$qrcode_id){
		$where['cat_id'] = $cat_id;
		$data['qrcode_id'] = $qrcode_id;
		if($this->where($where)->data($data)->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_('保存二维码至预约失败！请重试。')));
		}
	}
}

?>