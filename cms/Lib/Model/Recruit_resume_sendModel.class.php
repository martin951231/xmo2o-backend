<?php
class Recruit_resume_sendModel extends Model
{
    public function get_limit_list_page($condition = array(),$pageSize = 20,$is_wap=0)
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND s.uid in (' . $condition['uid'] . ')';
        }

        // 姓名
        if($condition['name']){
            $condition_where .= ' AND r.name like "%' . $condition['name'] . '%"';
        }

        if ($condition['company_id']) {
            $condition_where .= ' AND s.company_id =' . intval($condition['company_id']);
        }

        if ($condition['other']) {
            $condition_where .= ' AND '.$condition['other'];
        }

        $field = 's.*,u.nickname,c.company_name,p.position_cat_id,p.work_years,r.resume_name,r.phone,r.qualifications,r.name as truename,r.work_years as user_work_years,r.sex,r.birthday,r.salary,r.avatar,r.uid as r_uid';
        $order = ' `s`.`id` DESC';
        $join = 'as s LEFT JOIN '.C('DB_PREFIX').'user as u on `s`.`uid` = `u`.`uid` INNER JOIN '.C('DB_PREFIX').'recruit_company as c on `c`.`company_id` = `s`.`company_id`  INNER JOIN '.C('DB_PREFIX').'recruit_position as p on `p`.`position_id` = `s`.`position_id` INNER JOIN '.C('DB_PREFIX').'recruit_resume as r on `r`.`resume_id` = `s`.`resume_id`';
        $count = $this->join($join)->where($condition_where)->count();
        // var_dump($this->_sql());

        if ($is_wap==1) {
            $_GET['page'] = intval($_GET['page']) ? intval($_GET['page']) : 1;
            $list = $this->field($field)->join($join)->where($condition_where)->order($order)->limit(($_GET['page']-1)*$pageSize.','.$pageSize)->select();
        }else{
            import('@.ORG.system_page');
            $p = new Page($count,$pageSize,'page');
            $list = $this->field($field)->join($join)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
            $return['pagebar'] = $p->show();
        }

        foreach ($list as &$value) {
            if ($value['position_cat_id']) {
                $cat = M('Recruit_position_category')->where(array('id'=>$value['position_cat_id']))->find();
                $value['position_name'] = $cat['name'];
            }
            $value['qualifications'] = D('Recruit_position')->qualifications_arr[$value['qualifications']];
            $value['work_years'] = D('Recruit_position')->work_years_arr[$value['work_years']];
            $value['user_work_years'] = D('Recruit_position')->work_years_arr[$value['user_work_years']];
            $value['salary'] = D('Recruit_position')->salary_arr[$value['salary']];
            $value['age'] = $value['birthday'] ? date('Y',time()) - date('Y',strtotime($value['birthday'])) : '';
            $value['avatar_old'] = $value['avatar'] ? $value['avatar'] : '';
            $value['avatar'] = file_domain().$value['avatar'] ;
            $value['add_time_format'] = date('Y-m-d H:i:s',$value['add_time']);
        }

        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['page'] = $_GET['page'];
        $return['list'] = $list ? $list : array();
        return $return;
    }

    public function get_one($id)
    {

        $field = 's.*,u.nickname,c.company_name,p.position_cat_id,p.work_years,r.resume_name,r.name as truename,r.work_years as user_work_years,r.sex,r.birthday,r.salary,r.avatar';
        $order = ' `s`.`id` DESC';
        $join = 'as s LEFT JOIN '.C('DB_PREFIX').'user as u on `s`.`uid` = `u`.`uid` LEFT JOIN '.C('DB_PREFIX').'recruit_company as c on `c`.`company_id` = `s`.`company_id`  LEFT JOIN '.C('DB_PREFIX').'recruit_position as p on `p`.`position_id` = `s`.`position_id` LEFT JOIN '.C('DB_PREFIX').'recruit_resume as r on `r`.`resume_id` = `s`.`resume_id`';
        $condition_where = 's.id='.$id;
      
        $now_resume = $this->field($field)->join($join)->order($order)->find();
       
   
        if ($now_resume['position_cat_id']) {
            $cat = M('Recruit_position_category')->where(array('id'=>$now_resume['position_cat_id']))->find();
            $now_resume['position_name'] = $cat['name'];
        }
        $now_resume['work_years'] = D('Recruit_position')->work_years_arr[$now_resume['work_years']];
        $now_resume['user_work_years'] = D('Recruit_position')->work_years_arr[$now_resume['user_work_years']];
        $now_resume['salary'] = D('Recruit_position')->salary_arr[$now_resume['salary']];
        $now_resume['age'] = $now_resume['birthday'] ? date('Y',time()) - date('Y',strtotime($now_resume['birthday'])) : '';
        $now_resume['avatar'] = file_domain().$now_resume['avatar'] ;
        $now_resume['add_time_format'] = date('Y-m-d H:i:s',$now_resume['add_time']);
    
        return $now_resume;
    }
}