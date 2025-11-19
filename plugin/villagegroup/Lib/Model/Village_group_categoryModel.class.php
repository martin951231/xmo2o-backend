<?php

class Village_group_categoryModel extends Model
{
    //获取活动商品
    public function getHotRecomCategory($leader_id = 0, $limit = 10){
    	$where = [
    		'hot_recom' => 1,
    		'status' => 1,
    	];
    	$category_list = $this->where($where)->field("cat_id,cat_name,bg_img,bg_color,sub_title,show_what")->order("sort desc")->select();

        $goodsModel = D("Village_group_goods");
        foreach ($category_list as $key => $value) {
            if($value['show_what'] == 1){
                $category_list[$key]['bg_img'] = '';
            }
            else{
                $category_list[$key]['bg_img'] = replace_file_domain($value['bg_img']);
            }
        	$category_list[$key]['goods_list'] = $goodsModel->get_goods($leader_id, $value['cat_id'], 0, 1, 10, ['goods.is_new_user' => 0]);
            //如果没有商品也不展示该分类
            if(empty($category_list[$key]['goods_list'])){
                unset($category_list[$key]);
            }
        }
        
        return $category_list;
    }

    //获取带有商品的分类列表
    public function getHasGoodsCategory($leader_id = 0){
        $goodsModel = D("Village_group_goods");
        $all_goods = $goodsModel->get_goods($leader_id, 0, 0, -1);

        $cids = array_unique(array_column($all_goods, 'cat_fid'));
        $where = [
            'status' => 1,
            'cat_id' => ['in', $cids],
            'fid'    => 0,//必须是一级分类
        ];
        $category_list = $this->where($where)->field("cat_id,cat_name,hot_recom,bg_img,bg_color,sub_title,show_what")->order("sort desc")->select();
        //echo M()->getLastSql();exit;
        return $this->deal_list($category_list);
    }

    private function deal_list($list){
        if($list){
            foreach ($list as $key => $value) {
                $son_cate = $this->where(['fid'=>$value['cat_id'],'status' => 1])->field("cat_id,cat_name,icon")->order("sort desc")->select();
                if($son_cate){
                    foreach ($son_cate as $k => $v) {
                        $son_cate[$k]['icon'] = replace_file_domain($v['icon']);
                    }
                }
                $list[$key]['have_son_cate'] = $son_cate?true:false;
                $list[$key]['son_cate_list'] = $son_cate;
                $list[$key]['bg_image'] = replace_file_domain($value['bg_img']);
            }
        }
        return $list;
    }
    
}