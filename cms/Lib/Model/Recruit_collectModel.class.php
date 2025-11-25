<?php
class Recruit_collectModel extends Model
{
    public function get_limit_list_page($condition = array(),$pageSize = 20,$is_wap=0)
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND rc.uid in (' . $condition['uid'] . ')';
        }

        // 类型
        if($condition['type']){
            $condition_where .= ' AND rc.type = "' . $condition['type'] . '"';
        } 

        // 职位名
        // if($condition['position_name']){
        //     $condition_where .= ' AND r.position_name in (' . $condition['position_name'] . '%"';
        // }

        if ($condition['other']) {
            $condition_where .= ' AND '.$condition['other'];
        }
        $join = 'as rc LEFT JOIN '.C('DB_PREFIX').'user as u on `rc`.`uid` = `u`.`uid`';
        $field = 'rc.*,u.nickname';
        switch ($condition['type']) {
            case 'position':
                $field .= ',c.company_name,p.*';
                $join .= ' LEFT JOIN '.C('DB_PREFIX').'recruit_position as p on `p`.`position_id` = `rc`.`cid` LEFT JOIN '.C('DB_PREFIX').'recruit_company as c on `p`.`company_id` = `c`.`company_id` ';
                break;
            case 'resume':
                
                // $join .= '';
                break;
            case 'course':
                
                // $join .= ' LEFT JOIN '.C('DB_PREFIX').'recruit_company as c on `c`.`company_id` = `s`.`company_id`  LEFT JOIN '.C('DB_PREFIX').'recruit_position as p on `p`.`cid` = `s`.`position_id`';
                break;
            
            default:
                # code...
                break;
        }

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