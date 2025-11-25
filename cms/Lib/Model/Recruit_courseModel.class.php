<?php
class Recruit_courseModel extends Model
{
   public function get_limit_list_page($condition = array(),$pageSize = 10,$is_wap=0,$long_lat=array())
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND c.uid in (' . $condition['uid'] . ')';
        }  
        if($condition['title']){
            $condition_where .= ' AND c.title like "%' . $condition['title'] . '%"';
        }
        if($condition['status']){
            $condition_where .= ' AND c.status in (' . $condition['status'] . ')';
        }
        if($condition['name']){
            $condition_where .= ' AND u.nickname like "%' . $condition['name'] . '%"';
        }
        if($condition['cat_id']){
            $condition_where .= ' AND c.cat_id = ' . $condition['cat_id'];
        }

        $field = 'c.*,u.nickname,u.phone,cat.name as cat_name ';
        $join = 'as c left join '.C('DB_PREFIX').'user as u on `c`.`uid` = `u`.`uid` left join '.C('DB_PREFIX').'recruit_course_category as cat on `cat`.`id` = `c`.`cat_id`';
        $order = ' `c`.`course_id` DESC';

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
        $return['page'] = $_GET['page'];
        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['list'] = $list ? $list : array();
        return $return;
    }


    public function get_recommend_limit_list_page($condition = array(),$pageSize = 10,$is_wap=0,$long_lat=array())
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND c.uid in (' . $condition['uid'] . ')';
        }
        if($condition['title']){
            $condition_where .= ' AND c.title like "%' . $condition['title'] . '%"';
        }
        if($condition['status']){
            $condition_where .= ' AND c.status in (' . $condition['status'] . ')';
        }
        if($condition['name']){
            $condition_where .= ' AND u.nickname like "%' . $condition['name'] . '%"';
        }

        $field = 'c.*,u.nickname,u.phone,cat.name as cat_name ';
        $join = 'as c left join '.C('DB_PREFIX').'user as u on `c`.`uid` = `u`.`uid` left join '.C('DB_PREFIX').'recruit_course_category as cat on `cat`.`id` = `c`.`cat_id`';
        $order = ' `c`.`add_time` DESC';

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
        $return['page'] = $_GET['page'];
        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['list'] = $list ? $list : array();
        return $return;
    }

    //精品课程列表
    public function get_boutique_limit_list_page($condition = array(),$pageSize = 10,$is_wap=0,$long_lat=array())
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND c.uid in (' . $condition['uid'] . ')';
        }
        if($condition['title']){
            $condition_where .= ' AND c.title like "%' . $condition['title'] . '%"';
        }
        if($condition['status']){
            $condition_where .= ' AND c.status in (' . $condition['status'] . ')';
        }
        if($condition['name']){
            $condition_where .= ' AND u.nickname like "%' . $condition['name'] . '%"';
        }

        $field = 'c.*,u.nickname,u.phone,cat.name as cat_name ';
        $join = 'as c left join '.C('DB_PREFIX').'user as u on `c`.`uid` = `u`.`uid` left join '.C('DB_PREFIX').'recruit_course_category as cat on `cat`.`id` = `c`.`cat_id`';
        $order = ' `c`.`add_time` DESC';

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
        $return['page'] = $_GET['page'];
        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['list'] = $list ? $list : array();
        return $return;
    }

    //免费专区更多
    public function get_recommend_free_detail_limit_list_page($condition = array(),$pageSize = 10,$is_wap=0,$long_lat=array())
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND c.uid in (' . $condition['uid'] . ')';
        }
        if($condition['title']){
            $condition_where .= ' AND c.title like "%' . $condition['title'] . '%"';
        }
        if($condition['status']){
            $condition_where .= ' AND c.status in (' . $condition['status'] . ')';
        }
        if($condition['name']){
            $condition_where .= ' AND u.nickname like "%' . $condition['name'] . '%"';
        }
        $condition_where .= ' AND (c.score = 0.00 or c.score = 0)';
        $field = 'c.*,u.nickname,u.phone,cat.name as cat_name ';
        $join = 'as c left join '.C('DB_PREFIX').'user as u on `c`.`uid` = `u`.`uid` left join '.C('DB_PREFIX').'recruit_course_category as cat on `cat`.`id` = `c`.`cat_id`';
        $order = ' `c`.`course_id` DESC';

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
        $return['page'] = $_GET['page'];
        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['list'] = $list ? $list : array();
        return $return;
    }

    public function get_recommend_free_limit_list_page($condition = array(),$pageSize = 10,$is_wap=0,$long_lat=array())
    {
        $return = array();
        $condition_where = '1';

//        if($condition['uid']){
//            $condition_where .= ' AND c.uid in (' . $condition['uid'] . ')';
//        }
//        if($condition['title']){
//            $condition_where .= ' AND c.title like "%' . $condition['title'] . '%"';
//        }
       if($condition['status']){
           $condition_where .= ' AND c.status in (' . $condition['status'] . ')';
       }
//        if($condition['name']){
//            $condition_where .= ' AND u.nickname like "%' . $condition['name'] . '%"';
//        }
        $condition_where .= ' AND (c.score = 0.00 or c.score = 0)';
        $field = 'c.*,u.nickname,u.phone,cat.name as cat_name';
        $join = 'as c left join '.C('DB_PREFIX').'user as u on `c`.`uid` = `u`.`uid` left join '.C('DB_PREFIX').'recruit_course_category as cat on `cat`.`id` = `c`.`cat_id`';
//        left join '.C('DB_PREFIX').'video_comment as v on `c`.`course_id` = `v`.`cl_VideoId`
        $order = ' `c`.`add_time` DESC';

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

        $return['page'] = $_GET['page'];
        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['list'] = $list ? $list : array();
        return $return;
    }
}