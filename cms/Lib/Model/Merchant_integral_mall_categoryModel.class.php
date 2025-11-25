<?php
class Merchant_integral_mall_categoryModel extends Model{
    public function merchant_integral_mall_category_add($data){
        if(!$data){
            return false;
        }
 
        $data['add_time'] = time();
        $insert_id = $this->data($data)->add();
        if($insert_id){
            return array('status'=>1,'msg'=>L_('添加成功！'));
        }else{
            return array('status'=>0,'msg'=>L_('添加失败！'));
        }
    }
    
    
    public function merchant_integral_mall_category_page_list($where , $field = true , $order = 'cat_id desc',$pageSize = 20){
        if(!$where){
            return false;
        }
        import('@.ORG.system_page');
        $count = $this->where($where)->count();
        $page = new Page($count,$pageSize,'page');
        $list['list'] = $this->field($field)->where($where)->order($order)->limit($page->firstRow. ',' .$page->listRows)->select();
        $list['pagebar'] = $page->show();
        if(!$list['list']){
            $list['list'] = array();
        }
        return array('status'=>1,'list'=>$list);
    }
    
    public function merchant_integral_mall_category_del($where){
        if(!$where){
            return false;
        }
        
        $cat_id = $this->where($where)->getField('cat_id');
        if(!empty($cat_id) && ($cat_id != 0)){
            $Map['cat_fid'] = $cat_id;
            $Map['cat_status'] = array('neq', 3);
            $cat_count = $this->where($Map)->count();
            if($cat_count){
                return array('status'=>0,'msg'=>L_('请先删除底下子分类！'));
            }
        }
        
        $data['del_time'] = time();
        $data['cat_status'] = 3;
        $insert_id = $this->where($where)->data($data)->save();
        if($insert_id){
            return array('status'=>1,'msg'=>L_('删除成功！'));
        }else{
            return array('status'=>0,'msg'=>L_('删除失败！'));
        }
    }
    
    public function merchant_integral_mall_category_detail($where,$field = true){
        if(!$where){
            return false;
        }
        $detail = $this->where($where)->field($field)->find();
        if(!$detail){
            return array('status'=>0,'detail'=>array());
        }else{
            return array('status'=>1,'detail'=>$detail);
        }
    }
    
    public function merchant_integral_mall_category_edit($where,$data){
        if(!$where || !$data){
            return false;
        }
        
        $insert_id = $this->where($where)->data($data)->save();
        if($insert_id !== false){
            return array('status'=>1,'msg'=>L_('修改成功！'));
        }else{
            return array('status'=>0,'msg'=>L_('修改失败！'));
        }
    }


    public function get_son_category($cat_fid){
        if(empty($cat_fid)){
            return false;
        }

        $where['cat_fid'] = $cat_fid;
        $where['is_del'] = 0;
        $where['cat_status'] = 1;
        $son_category_list = $this->where($where)->select();

        $database_gift = D('Gift');

        $merchant_integral_mall_category_list = array();
        $count = 0;
        foreach($son_category_list as $Key=>$Son_category){
            $Son_category_condition['cat_id'] = $Son_category['cat_id'];
            $Son_category_condition['is_del'] = 0;
            $Son_category_condition['status'] = 1;

            $Son_category['gift_num'] = $database_gift->where($Son_category_condition)->count();
            array_push($merchant_integral_mall_category_list , $Son_category);
            $count += $Son_category['gift_num'];
        }

        return array('merchant_integral_mall_category_list'=>$merchant_integral_mall_category_list , 'count'=>$count);
    }

    /**
     * @desc 获取商家积分分类
     * @param $mer_id
     * @param bool|true $field
     * @return array
     */
    public function get_category_list($mer_id,$field=true){
        if(!$mer_id) return array();
        $where = array();
        $where['cat_status'] = 1;
        $where['cat_fid'] = 0;
        $where['mer_id'] = $mer_id;
        $order = 'cat_sort DESC,cat_id DESC';
        $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }
}
?>
