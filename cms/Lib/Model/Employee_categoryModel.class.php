<?php
class Employee_categoryModel extends Model
{
    public $write_group_info = array(
        1 =>
            array (
                'gid' => '1',
                'gname' => '基本信息',
                'gsort' => '4',
            ),
        2 =>
            array (
                'gid' => '2',
                'gname' => '单位信息',
                'gsort' => '3',
            ),
        3 =>
            array (
                'gid' => '3',
                'gname' => '个人资历',
                'gsort' => '2',
            ),
        4 =>
            array (
                'gid' => '4',
                'gname' => '风采展示',
                'gsort' => '1',
            )
    );

    /**
     * 从业者id获取是否可以评论
     * @param $employee_id
     * @return bool
     */
    public function employee_is_comment($employee_id)
    {
        $m_employee_list = M('Employee_list');
        $info = $m_employee_list->where(array('id' => $employee_id))->field('cat_id, cat_fid, cat_pfcid')->find();
        $is_comment = $this->where(array('cat_id' => $info['cat_pfcid'], 'cat_fid' => 0, 'cat_pfcid' => 0))->getField('is_comment');
        if ($is_comment == 2) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * 分类id获取是否可以评论
     * @param $category_id
     * @return bool
     */
    public function category_is_comment($category_id)
    {
        $is_comment = $this->where(array('cat_id' => $category_id, 'cat_fid' => 0, 'cat_pfcid' => 0))->getField('is_comment');
        if ($is_comment == 2) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * 根据cat_url得到分类
     * @param $cat_url
     * @return mixed
     */
    public function get_category_by_catUrl($cat_url){
        $condition_group_category['cat_url'] = $cat_url;
        $condition_group_category['cat_status'] = '1';
        $now_category = $this->field(true)->where($condition_group_category)->find();
        if(!empty($now_category)){
            $now_category['url'] = $this->get_category_url($now_category);
        }
        return $now_category;
    }


    /**
     * 根据cat_id得到分类
     * @param $cat_id
     * @return mixed
     */
    public function get_category_by_id($cat_id){
        $condition_group_category['cat_id'] = $cat_id;
        $condition_group_category['cat_status'] = '1';
        $now_category = $this->field(true)->where($condition_group_category)->find();
        if(!empty($now_category)){
            $now_category['url'] = $this->get_category_url($now_category);
        }
        return $now_category;
    }


    /**
     * 得到列表所有分类 包含所有一级二级三级分类
     * @return array
     */
    public function get_all_category(){
        $condition_employee_category['cat_status'] = '1';
        $tmp_employee_category = $this->field(true)->where($condition_employee_category)->order('`cat_sort` DESC')->limit()->select();
        $employee_category = array();
        $second_category = array();
        $third_category = array();
        foreach($tmp_employee_category as $k1=>$val1){
            if(empty($val1['cat_fid']) && empty($val1['cat_pfcid'])){
                $second_category[$val1['cat_id']] = $k1;

                $val1['cat_count'] = 0;
                $val1['url'] = $this->get_category_url($val1);
                $employee_category[$k1] = $val1;

                unset($tmp_employee_category[$k1]);
            }
        }
        foreach($tmp_employee_category as $k2=>$val2){
            if(!empty($val2['cat_fid']) && empty($val2['cat_pfcid'])){
                if(isset($second_category[$val2['cat_fid']])){
                    $third_category[$second_category[$val2['cat_fid']]][$val2['cat_id']] = $k2;

                    $val2['url'] = $this->get_category_url($val2);
                    $employee_category[$second_category[$val2['cat_fid']]]['cat_count'] += 1;
                    $employee_category[$second_category[$val2['cat_fid']]]['category_list'][$k2] = $val2;

                    unset($tmp_employee_category[$k2]);
                }
            }
        }
        foreach($tmp_employee_category as $k3=>$val3){
            if(!empty($val3['cat_fid']) && !empty($val3['cat_pfcid'])){
                if(isset($third_category[$second_category[$val3['cat_pfcid']]][$val3['cat_fid']])){
                    $third_key = $third_category[$second_category[$val3['cat_pfcid']]][$val3['cat_fid']];
                    $second_key = $second_category[$val3['cat_pfcid']];
                    $val3['url'] = $this->get_category_url($val3);
                    $employee_category[$second_key]['category_list'][$third_key]['cat_count'] += 1;
                    $employee_category[$second_key]['category_list'][$third_key]['category_list'][$k3] = $val3;
                }
            }
        }
        foreach($employee_category as $k4=>$val4){
            if(empty($val4['cat_id'])){
                unset($employee_category[$k4]);
            }
        }
        return $employee_category;

    }



    /**
     * 得到分类的URL
     * @param $category
     * @return string
     */
    protected function get_category_url($category){
        return C('config.site_url').'/employee_category/'.$category['cat_url'];
    }
}
?>