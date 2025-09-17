<?php
class Recruit_resumeModel extends Model
{
    public function get_limit_list_page($condition = array(),$pageSize = 20,$is_wap=0)
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND r.uid in (' . $condition['uid'] . ')';
        }

        if($condition['work_years']){
            $condition_where .= ' AND r.work_years in (' . $condition['work_years'] . ')';
        }

        if($condition['position_cat_id']){
            $condition_where .= ' AND r.position_cat_id in (' . $condition['position_cat_id'] . ')';
        }
        
        if($condition['name']){
            $condition_where .= ' AND r.name like "%' . $condition['name'] . '%"';
        }

        if ($condition['province']) {
            $condition_where .= ' AND r.province =' . intval($condition['province']);
        }
        if ($condition['city']) {
            $condition_where .= ' AND r.city =' . intval($condition['city']);
        }
        if ($condition['area']) {
            $condition_where .= ' AND r.area =' . intval($condition['area']);
        }
        if ($condition['sex']) {
            $condition_where .= ' AND r.sex =' . intval($condition['sex']);
        }
        if ($condition['has_work']) {
            $condition_where .= ' AND r.has_work =' . intval($condition['has_work']);
        }
        if ($condition['has_education']) {
            $condition_where .= ' AND r.has_education =' . intval($condition['has_education']);
        }
        if ($condition['other']) {
            $condition_where .= ' AND '.$condition['other'];
        }
        if ($condition['order_by']) {
            // $order = ' `r`.`'.$condition['order_by'].'` DESC';
        }else{
            $order = '`r`.`update_time` DESC,`r`.`resume_id` DESC';
            // $order = '`r`.`refresh_time` DESC,`r`.`resume_id` DESC';
        }
        
        $field = 'r.*,u.nickname,c.name as position_name,a.area_name';
        $join = 'as r LEFT JOIN '.C('DB_PREFIX').'user as u on `r`.`uid` = `u`.`uid` LEFT JOIN '.C('DB_PREFIX').'recruit_position_category as c on `c`.`id` = `r`.`position_cat_id` LEFT JOIN '.C('DB_PREFIX').'area as a on `a`.`area_id` = `r`.`area`';

        $count = $this->join($join)->where($condition_where)->count();
        if ($is_wap==1) {
            $_GET['page'] = intval($_GET['page']) ? intval($_GET['page']) : 1;
            $list = $this->field($field)->join($join)->where($condition_where)->order($order)->limit(($_GET['page']-1)*$pageSize.','.$pageSize)->select();
        }else{
            import('@.ORG.system_page');
            $p = new Page($count,$pageSize,'page');
            $list = $this->field($field)->join($join)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
            $return['pagebar'] = $p->show();
        }
        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['page'] = $_GET['page'];
        $return['list'] = $list ? $list : array();
        return $return;
    }

    public function get_one($resume_id)
    {       
        $condition_where = 'r.resume_id='.intval($resume_id); 
        $field = 'r.*,u.nickname';
        $join = 'as r LEFT JOIN '.C('DB_PREFIX').'user as u on `r`.`uid` = `u`.`uid`';
        $resume = $this->field($field)->join($join)->where($condition_where)->find();
        $resume_detail = M('Recruit_resume_detail')->where(array('resume_id'=>$resume_id))->select();
        foreach ($resume_detail as $key => $value) {
            if ( $value['type'] == 1 ) {
                // 工作经历
                $resume['work_experience'][] = $value;
            }elseif ($value['type'] == 2 ) {
                // 教育经历
                $resume['education_experience'][] = $value;
            }
        }
        return $resume;
    }
}