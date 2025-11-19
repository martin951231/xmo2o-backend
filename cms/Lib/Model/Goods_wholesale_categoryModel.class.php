<?php
class Goods_wholesale_categoryModel extends Model
{
	public function get_list($get_banner=0, $where = array())
	{
	    if (!$where) {
            $where=array();
            $where['_string'] = '(fid>0 AND status=1) OR fid=0';
        }
		$items = $this->field(true)->where($where)->order('`sort` DESC, `id` ASC')->select();
		$tmpMap = array();
		foreach ($items as $item) {
            if(!empty($item['image'])){
                $item['image_path'] = file_domain()."/upload/goodscategory/".$item['image'];
            }else{
				$item['image_path'] = C('config.site_url')."/static/images/nopic.jpg";
			}
			
            if(!empty($item['icon'])){
                $item['icon_path'] = file_domain()."/upload/goodscategory/".$item['icon'];
            }
			
            //一级分类
            if($get_banner=1 && empty($item['fid'])){
                $where=array();
                $where['cat_id']=$item['id'];
                $where['type']=2;
                $banner_info=M('Goods_category_banner')->where($where)->field('image,url')->find();
                if(isset($banner_info)){
                    $banner_info['image'] = file_domain()."/upload/goodscategory/".$banner_info['image'];
                }
                $item['banner_info']=$banner_info;
            }
			$tmpMap[$item['id']] = $item;
		}
		$list = array();
// 		if ($is_add_all) {
// 			$list[] = array('id' => '0', 'name' => '全部分类');
// 		}

		$tlist = array();
		foreach ($items as $item) {
			if (isset($tmpMap[$item['fid']])) {
				$tmpMap[$item['fid']]['son_list'][] = &$tmpMap[$item['id']];
			} else {
				$tlist[] = &$tmpMap[$item['id']];
			}
		}
		foreach ($tlist as $tl) {
			if ($tl['status'] == 1) {
				$list[] = $tl;
			}
		}
		unset($tmpMap);
		foreach ($list as $key=>$va) {
			if(empty($va['son_list'])){
				unset($list[$key]);
			}

		}
		return $list;
	}


    //获取指定一级分类的二级分类信息
    public function get_menu_list($fid){
        $where=array();
        $where['fid']=$fid;
        $where['status']=1;
        $list=$this->where($where)->order('`sort` DESC, `id` ASC')->select();
        foreach ($list as $k => $v) {
            if(!empty($v['icon'])){
                $v['icon_path']=file_domain()."/upload/goodscategory/".$v['icon'];
            }
            $v['url']=U('Mall/goods_list',array('cat_fid'=>$fid,'cat_id'=>$v['id']));
            $list[$k]=$v;
        }
        return (array)$list;
    }
}
?>