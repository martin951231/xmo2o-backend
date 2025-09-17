<?php
class Recruit_companyModel extends Model
{
   public function get_limit_list_page($condition = array(),$pageSize = 10,$is_wap=0,$long_lat=array())
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND c.uid in (' . $condition['uid'] . ')';
        }  
        if($condition['name']){
            $condition_where .= ' AND u.nickname like "%' . $condition['name'] . '%"';
        }  
        if($condition['company_name']){
            $condition_where .= ' AND c.company_name like "%' . $condition['company_name'] . '%"';
        }
        if ($condition['province']) {
            $condition_where .= ' AND c.province =' . intval($condition['province']);
        }
        if ($condition['city']) {
            $condition_where .= ' AND c.city =' . intval($condition['city']);
        }
        if ($condition['area']) {
            $condition_where .= ' AND c.area =' . intval($condition['area']);
        }
        if ($condition['other']) {
            $condition_where .= ' AND '.$condition['other'];
        }

        $field = 'c.*,u.nickname,u.phone';
        $join = 'as c left join '.C('DB_PREFIX').'user as u on `c`.`uid` = `u`.`uid`';
        $order = ' `c`.`company_id` DESC';

        $count = $this->join($join)->where($condition_where)->count();
   
        import('@.ORG.system_page');
        $p = new Page($count,$pageSize,'page');
        $list = $this->field($field)->join($join)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        $return['pagebar'] = $p->show();
        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['list'] = $list ? $list : array();
        return $return;
    }

    public function get_one($company_id,$field='company_id')
    {       
        $condition_where = 'c.'.$field.'='.intval($company_id);
        $field = 'c.*,u.nickname,u.phone as user_phone';
        $company = $this->join(' as c left join '.C('DB_PREFIX').'user as u on `c`.`uid` = `u`.`uid`')->field($field)->table($condition_table)->where($condition_where)->find();
        if ($company) {
            $company['license_pic'] = explode(';',json_decode($company['license_pic']));
            $company['company_pic'] = explode(';',json_decode($company['company_pic']));
        }
        return $company;
    }

    // 获取首页推荐
    
   public function get_wap_list_page($condition = array(),$pageSize = 10,$long_lat=array(),$is_wap=0)
    {
        $return = array();
        $condition_where = 'c.status = 1';

        if($condition['uid']){
            $condition_where .= ' AND c.uid in (' . $condition['uid'] . ')';
        }  
        if($condition['name']){
            $condition_where .= ' AND u.nickname like "%' . $condition['name'] . '%"';
        }  
        if($condition['company_name']){
            $condition_where .= ' AND c.company_name in (' . $condition['company_name'] . '%"';
        }
        if(isset($condition['is_hide'])){
            $condition_where .= ' AND c.is_hide =' . $condition['is_hide'];
        }

        if ($condition['other']) {
            $condition_where .= ' AND '.$condition['other'];
        }

        $field = 'c.*,u.nickname,u.phone';
        $join = 'as c left join '.C('DB_PREFIX').'user as u on `c`.`uid` = `u`.`uid`';
         if ($is_wap==1&&$condition['type']) {
            $field .= ',`b`.`ranking_sort`';
            // 排序
            switch ($condition['type']) {
                case 'index':
                    if ($condition['now_area']) {
                        $join .= ' left join (select MAX(b.ranking_sort) as ranking_sort,`b`.`company_id` ,`b`.`start_time`,`b`.`end_time`,`b`.`city`,`b`.`province` from pigcms_recruit_ranking_bind_area as b where `b`.`end_time` >= '.time() .' AND `b`.`area`='.$condition['now_area'].' LIMIT 1) as b on `b`.`company_id` = `c`.`company_id`';
                        $condition_where .=' AND c.area =' . $condition['now_area'];
                    }
                    break;
                case 'city':
                    $join .= ' left join (select MAX(b.ranking_sort) as ranking_sort,`b`.`company_id` ,`b`.`start_time`,`b`.`end_time`,`b`.`city`,`b`.`province` from pigcms_recruit_ranking_bind_area as b where `b`.`end_time` >= '.time() .' AND `b`.`city`='.C('config.now_city').' LIMIT 1) as b on `b`.`company_id` = `c`.`company_id`';

                    $condition_where .=' AND c.city =' . C('config.now_city');
                    break;
                case 'province':
                    // 获得当前省份
                    $province = D('Area')->get_parents_area_by_areaid(C('config.now_city'));
                    $province_id = $province[1];
                    $join .= ' left join (select MAX(b.ranking_sort) as ranking_sort,`b`.`company_id` ,`b`.`start_time`,`b`.`end_time`,`b`.`city`,`b`.`province` from pigcms_recruit_ranking_bind_area as b where `b`.`end_time` >= '.time() .' AND `b`.`province`='.$province_id.' LIMIT 1) as b on `b`.`company_id` = `c`.`company_id`';
                    $condition_where .=' AND c.province =' . $province_id;
                    break;
            }
            if ($long_lat['lat']&&$long_lat['long']) {
                $order = "`b`.`ranking_sort` DESC, `b`.`start_time` ASC, ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']} * PI() / 180- `c`.`lat` * PI()/180)/2),2)+COS({$long_lat['lat']} *PI()/180)*COS(`c`.`lat`*PI()/180)*POW(SIN(({$long_lat['long']} *PI()/180- `c`.`lng`*PI()/180)/2),2)))*1000) ASC,`c`.`company_id` DESC";
            } else {
                // 没有经纬度以排名排序
                $order = "`b`.`ranking_sort` DESC, `b`.`start_time` ASC,`c`.`company_id` DESC ";
            }
        }else{
            $order = ' `c`.`company_id` DESC';
        }

        $count = $this->join($join)->where($condition_where)->count();
        $_GET['page'] = intval($_GET['page']) ? intval($_GET['page']) : 1;
        $list = $this->field($field)->join($join)->where($condition_where)->order($order)->limit(($_GET['page']-1)*$pageSize.','.$pageSize)->select();

        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['page'] = $_POST['page'];
        $return['list'] = $list ? $list : array();
        return $return;
    }

}