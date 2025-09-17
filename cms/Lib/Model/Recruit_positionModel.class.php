<?php
class Recruit_positionModel extends Model
{
    // 工作年限
    public $work_years_arr = array(
        1 => '经验不限',
        2 => '1年以下',
        3 => '1-2年',
        4 => '3-5年',
        5 => '6-7年',
        6 => '8-10年',
        7 => '10年以上',
    );

    // 学历
    public $qualifications_arr = array(
        1 => '不限',
        2 => '高中',
        3 => '技校',
        4 => '中专',
        5 => '大专',
        6 => '本科',
        7 => '硕士',
        8 => '博士',
    );

    // 薪资
    public $salary_arr = array(
        1 => '薪资面议',
        2 => '1000以下',
        3 => '1000-2000元',
        4 => '2000-3000元',
        5 => '3000-5000元',
        6 => '5000-8000元',
        7 => '8000-12000元',
        8 => '12000-20000元',
        9 => '20000-22000元',
        10 => '22000元以上',
    );

    // 福利待遇
    public $welfare_arr = array(
        1 => '五险一金',
        2 => '包吃',
        3 => '包住',
        4 => '周末双休',
        5 => '年底双薪',
        6 => '房补',
        7 => '话补',
        8 => '交通补助',
        9 => '饭补',
        10 => '加班补助',
        11 => '免费培训',
        12 => '环境好',
        13 => '班车接送',
    );

    public function get_limit_list_page($condition = array(),$pageSize = 20,$is_wap=0)
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND p.uid in (' . $condition['uid'] . ')';
        }   
        if($condition['status']){
            $condition_where .= ' AND p.status in (' . $condition['status'] . ')';
        }  
        if($condition['name']){
            $condition_where .= ' AND u.nickname like "%' . $condition['name'] . '%"';
        }
        if($condition['position_name']){
            $condition_where .= ' AND p.position_name in (' . $condition['position_name'] . '%"';
        }
        if($condition['company_name']){
            $condition_where .= ' AND c.company_name in (' . $condition['company_name'] . '%"';
        }
        if ($condition['province']) {
            $condition_where .= ' AND p.province =' . intval($condition['province']);
        }
        if ($condition['city']) {
            $condition_where .= ' AND p.city =' . intval($condition['city']);
        }
        if ($condition['area']) {
            $condition_where .= ' AND p.area =' . intval($condition['area']);
        }
        if ($condition['other']) {
            $condition_where .= ' AND '.$condition['other'];
        }


        $field = 'p.*,u.avatar,u.nickname,u.phone,c.company_name,cat.name as position_name';
        $order = ' `p`.`position_id` DESC';
        $join = 'as p LEFT JOIN '.C('DB_PREFIX').'user as u on `p`.`uid` = `u`.`uid` LEFT JOIN '.C('DB_PREFIX').'recruit_company as c on `p`.`company_id` = `c`.`company_id` LEFT JOIN '.C('DB_PREFIX').'recruit_position_category as cat on `cat`.`id` = `p`.`position_cat_id`';
        $count = D('Recruit_position')->join($join)->where($condition_where)->count();

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

    public function get_one($position_id)
    {       
        $condition_where = 'p.position_id='.intval($position_id); 
        $field = 'p.*,u.avatar,u.nickname,u.phone,c.company_name,c.address as company_address,cat.name as position_name';
        $join = 'as p LEFT JOIN '.C('DB_PREFIX').'user as u on `p`.`uid` = `u`.`uid` LEFT JOIN '.C('DB_PREFIX').'recruit_company as c on `p`.`company_id` = `c`.`company_id` LEFT JOIN '.C('DB_PREFIX').'recruit_position_category as cat on `cat`.`id` = `p`.`position_cat_id`';
        $pisition = D('Recruit_position')->field($field)->join($join)->where($condition_where)->find();
        return $pisition;
    }

    // 推荐职位
    public function recommend_position($long_lat, $condition = array(), $limit = 4)
    {
       
        if ($long_lat['lat']&&$long_lat['long']) {
            $order = "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']} * PI() / 180- `lat` * PI()/180)/2),2)+COS({$long_lat['lat']} *PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']} *PI()/180- `lng`*PI()/180)/2),2)))*1000) ASC";
        } else {
            $condition['city_id'] = C('config.now_city');
            $order = "`position_id` DESC";
        }

        $list = $this->field(true)->where($condition)->order($order)->limit($limit)->select();
        foreach ($list as &$value) {
            if($long_lat){
                $value['range'] = getRange(getDistance($value['lat'],$value['lng'],$long_lat['lat'],$long_lat['long']));
            }
            $value['salary_text'] = $this->salary_arr[$value['salary']];
            
            $position_name = M('Recruit_position_category')->where(array('id'=>$value['position_cat_id']))->find();
            $value['position_name'] = $position_name['name'];
        }
        return $list;
    }
}