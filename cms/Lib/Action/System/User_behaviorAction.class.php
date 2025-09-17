<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/12/2.
 * Time: 9:24.
 */

/**
 * Class User_behaviorAction 用户行为跟踪
 */
class User_behaviorAction extends BaseAction{

    //购买商品行为跟踪
    public function index(){

        $model = D('User_behavior_goods');
        $where = array();

        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'goods') {
                $where['b.name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'nickname') {
                $where['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $where['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            if ($where['_string']) {
                $where['_string'] .= " AND (b.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }else{
                $where['_string'] = " (b.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }
        }

        if(isset($_GET['business_type_config']) && $_GET['business_type_config']>=0){
            $where['b.business_type'] = intval($_GET['business_type_config']);
        }

        if(isset($_GET['from_type_config'])&& $_GET['from_type_config']>=0){
            $where['b.from_type'] = intval($_GET['from_type_config']);
        }

        $field = 'b.*,u.nickname,u.phone';
        $count  = $model->alias('b')->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')->WHERE($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $list = $model->alias('b')
                ->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')
                ->field($field)
                ->order('id desc')
                ->WHERE($where)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->select();
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $list = $this->deal_list($list,$model);
        $this->assign('list', $list);

        $this->assign('from_type_config', $model->from_type_config);
        $this->assign('business_type_config', $model->business_type_config);

        $this->display();
    }

    //发布评论行为记录
    public function reply(){
        $model = D('User_behavior_reply');

        // $business_type_val = -1时，union连表时的搜索条件
        $condition = '';
        // $business_type_val = 3时，搜索pigcms_mall_good_reply表的条件
        $where_mall = [];
        // $business_type_val 其它值时，搜索pigcms_user_behavior_reply表的条件
        $where = array();

        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'content') {
                $where['b.content'] = $where_mall['b.comment'] = array('like', '%' . $_GET['keyword'] . '%');
                $condition = 'b.content like "%' . $_GET['keyword'] . '%" ';
            } else if ($_GET['searchtype'] == 'nickname') {
                $where['u.nickname'] = $where_mall['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
                $condition = 'u.nickname like "%' . $_GET['keyword'] . '%" ';
            } else if ($_GET['searchtype'] == 'phone') {
                $where['u.phone'] = $where_mall['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
                $condition = 'u.phone like "%' . $_GET['keyword'] . '%" ';
            }
        }

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            if ($where['_string']) {
                $where['_string'] .= " AND (b.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }else{
                $where['_string'] = " (b.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }
            if ($where_mall['_string']) {
                $where_mall['_string'] .= " AND (b.create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            } else {
                $where_mall['_string'] = " (b.create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }

            if ($condition) {
                $condition .= 'AND ';
            }
            $condition .= '(b.add_time BETWEEN ' . $period[0] . ' AND ' . $period[1] . ') ';
        }

        // 购买端口
        $from_type_val = I('from_type_config', -1);
        if($from_type_val >= 0){
            $where['b.from_type'] = $from_type_val;
            if ($condition) {
                $condition .= 'AND ';
            }
            $condition .= 'b.from_type=' . $from_type_val;
        }
        // 所属业务
        $business_type_val = I('business_type_config', -1);
        if ($business_type_val >= 0) {
            $where['b.business_type'] = $business_type_val;
        }

        // pigcms_user_behavior_reply表与pigcms_mall_good_reply表union查询
        $field = 'b.*,u.nickname,u.phone';
        if ($business_type_val == -1) {
            $query_sql = "SELECT $field ";
            $count_sql = "SELECT count(1) as count ";
            $query = "FROM 
                             (SELECT `id`,`uid`,`content`,`add_time`,`from_type`,`business_type`,`add_ip` FROM pigcms_user_behavior_reply
                                union select `rpl_id`,`uid`,`comment`,`create_time`,5,3,0 from `pigcms_mall_good_reply`) 
                             as b 
                             LEFT JOIN `pigcms_user` u ON b.uid=u.uid";
            if ($condition) {
                $query .= ' where ' . $condition;
            }
            $count = D()->query($count_sql . $query);
            import('@.ORG.system_page');
            $p = new Page($count[0]['count'], 30);
            $list = D()->query($query_sql . $query . " order by add_time desc limit {$p->firstRow},{$p->listRows}");
            $list = $this->deal_list($list,$model);
        } else if ($business_type_val == 3) {
            if($from_type_val != 5 && $from_type_val != -1){
                $where_mall['b.id'] =['lt', 0];
            }
            $count = M('Mall_good_reply')->alias('b')->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')->WHERE($where_mall)->count();
            import('@.ORG.system_page');
            $p = new Page($count, 30);
            $list = M('Mall_good_reply')->alias('b')
                ->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')
                ->field('b.rpl_id as id,b.uid,b.comment as content,b.create_time as add_time,u.nickname,u.phone')
                ->order('id desc')
                ->WHERE($where_mall)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->select();
            $list = $this->deal_list1($list,$model);
        } else {
            $count  = $model->alias('b')->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')->WHERE($where)->count();
            import('@.ORG.system_page');
            $p = new Page($count, 30);
            $list = $model->alias('b')
                ->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')
                ->field($field)
                ->order('id desc')
                ->WHERE($where)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->select();
            $list = $this->deal_list($list,$model);
        }

        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list', $list);

        $this->assign('from_type_config', $model->from_type_config);
        $this->assign('from_type_val', $from_type_val);
        $this->assign('business_type_config', $model->business_type_config);
        $this->assign('business_type_val', $business_type_val);

        $this->display();
    }

    //发布评论行为记录
    public function appoint(){
        $model = D('User_behavior_appoint');
        $where = array();

        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'name') {
                $where['b.content'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'nickname') {
                $where['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $where['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            if ($where['_string']) {
                $where['_string'] .= " AND (b.appoint_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }else{
                $where['_string'] = " (b.appoint_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }
        }

        if(isset($_GET['from_type_config'])&& $_GET['from_type_config']>=0){
            $where['b.from_type'] = intval($_GET['from_type_config']);
        }

        $field = 'b.*,u.nickname,u.phone';
        $count  = $model->alias('b')->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $list = $model->alias('b')
            ->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')
            ->field($field)
            ->order('id desc')
            ->WHERE($where)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $list = $this->deal_list($list,$model);
        $this->assign('list', $list);

        $this->assign('from_type_config', $model->from_type_config);

        $this->display();
    }

    //分类信息行为记录
    public function classify($is_export=false){
        $model = D('User_behavior_classify');
        $where = array();

        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'content') {
                $where['b.description'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'nickname') {
                $where['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $where['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            if ($where['_string']) {
                $where['_string'] .= " AND (b.classify_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }else{
                $where['_string'] = " (b.classify_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }
        }

        if(isset($_GET['from_type_config'])&& $_GET['from_type_config']>=0){
            $where['b.from_type'] = intval($_GET['from_type_config']);
        }

        $field = 'b.*,u.nickname,u.phone';
        if($is_export){
            $list = $model->alias('b')
                ->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')
                ->field($field)
                ->order('id desc')
                ->WHERE($where)
                ->select();
            $list = $this->deal_list($list,$model);
            return $list;
        }else{
            $count  = $model->alias('b')->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')->count();
            import('@.ORG.system_page');
            $p = new Page($count, 30);
            $list = $model->alias('b')
                ->JOIN('left join '.C("DB_PREFIX").'user as u on u.uid =b.uid')
                ->field($field)
                ->order('id desc')
                ->WHERE($where)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->select();
            $pagebar = $p->show();
            $this->assign('pagebar', $pagebar);
            $list = $this->deal_list($list,$model);
            $this->assign('list', $list);

            $this->assign('from_type_config', $model->from_type_config);

            $this->display();
        }
    }

    public function exportClassify(){
        $orders = $this->classify(true);
        foreach ($orders as $item){
            $item['description'] = $item['description']?:'未知';
            $item['classify_time'] = $item['classify_time']?date('Y-m-d H:i:s'):'';
        }
        $config = [
            'header' => [
                ['title' => L_('ID'), 'value_key' => 'id'],
                ['title' => L_('昵称'), 'value_key' => 'nickname'],
                ['title' => L_('手机号'), 'value_key' => 'phone'],
                ['title' => L_('发布内容'), 'value_key' => 'description'],
                ['title' => L_('发布时间'), 'value_key' => 'classify_time'],
                ['title' => L_('来源'), 'value_key' => 'from_type_text'],
                ['title' => L_('IP地址'), 'value_key' => 'ip'],
            ],
            'body' => $orders,
            'file_name' => time(),
            'sheet_title' => "分类信息记录"
        ];
        R('System/Shop/download_store_list', ['config' => $config]);
    }

    private function deal_list($list,$model){
        $from_type_config = $model->from_type_config;
        $business_type_config = $model->business_type_config;
        if($list){
            foreach($list as $key => $val){
                $list[$key]['from_type_text']    = $from_type_config[$val['from_type']];
                if(isset($val['business_type'])){
                    $list[$key]['business_type_text'] = $business_type_config[$val['business_type']];
                }
                $list[$key]['ip'] = long2ip($val['add_ip']);
            }
        }
        return $list;
    }

    private function deal_list1($list,$model){
        $from_type_config = $model->from_type_config;
        $business_type_config = $model->business_type_config;
        if($list){
            foreach($list as $key => $val){
                $val['from_type'] = 5;
                $list[$key]['from_type_text']    = $from_type_config[$val['from_type']];
                $val['business_type'] = 3;
                $val['add_ip'] = 0;
                $list[$key]['business_type_text'] = $business_type_config[$val['business_type']];
                $list[$key]['ip'] = long2ip($val['add_ip']);
            }
        }
        return $list;
    }

}

?>