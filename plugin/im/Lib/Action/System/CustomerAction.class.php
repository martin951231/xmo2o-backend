<?php

class CustomerAction extends BaseAction
{
    /**
     * 获取粉丝，平台登录获取所有用户，商户登录获取商户粉丝
     */
    public function selectfans()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        $pageSize = isset($_GET['limit']) && $_GET['limit'] > 0 ? intval($_GET['limit']) : $this->pageSize;
        $tag = isset($_GET['tag']) ? trim($_GET['tag']) : 'merchant';
        if ($page > 0) {
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            $cond = array('u.status' => 1);
            if ($keyword) {
                $cond['u.nickname|u.phone'] = array('like', '%' . $keyword . '%'); 
            }
            if ($tag == 'system') {
                $count = M('User')->alias('u')->where($cond)->count();
                $fans = M('User')->alias('u')->where($cond)->field('u.uid,u.nickname,u.avatar,u.phone')->order('u.uid asc')->limit(($page - 1) * $pageSize . ',' . $pageSize)->select();
            } else {
                $merId = intval($this->merchant('mer_id'));
                $cond['m.mer_id'] = $merId;
                $cond['u.openid'] = ['NEQ', ''];
                $join = C('DB_PREFIX') . 'merchant_user_relation AS m ON u.openid = m.openid';
                $count = M('User')->alias('u')->join($join)->where($cond)->count("distinct(m.`openid`)");
                $fans = M('User')->alias('u')->join($join)->where($cond)->field('u.uid,u.nickname,u.avatar,u.openid,u.phone,m.openid as aa')->order('u.uid asc')->group('m.openid')->limit(($page - 1) * $pageSize . ',' . $pageSize)->select();
            }
            foreach ($fans as &$f) {
                if (empty($f['avatar'])) {
                    $f['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
                } else {
                    $f['avatar'] = replace_file_domain($f['avatar']);
                }
            }
            layerOk($count, $fans);
        } else {
            $from = isset($_GET['from']) ? $_GET['from'] : '';
            $tag = isset($_GET['tag']) ? $_GET['tag'] : '';
            $this->assign('from', $from);
            $this->assign('tag', $tag);
            $this->display();
        }
    }

    /**
     * 小区工作人员
     */
    public function villageWorker()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        $pageSize = isset($_GET['limit']) && $_GET['limit'] > 0 ? intval($_GET['limit']) : $this->pageSize;
        $villageId = isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        if ($page > 0) {
            $where = ['village_id' => $villageId];
            $where['status'] = 1;
            $where['is_del'] = 0;
            if (!empty(trim($_GET['keyword']))) {
                $where['name|phone'] = ['LIKE', '%' . trim($_GET['keyword']) . '%'];
            }
            $count = D('House_worker')->where($where)->count();
            $fans = D('House_worker')->where($where)->limit(($page - 1) * $pageSize . ',' . $pageSize)->select();
            foreach ($fans as &$kefu1) {
                if ($kefu1['avatar']) {
                    $kefu1['avatar'] = replace_file_domain($kefu1['avatar']);
                } else {
                    $kefu1['avatar'] = $this->config['site_url'] . '/static/images/user_avatar.jpg';
                }
            }
            layerOk($count, $fans);
        } else {
            $this->assign('village_id', $villageId);
            $this->display();
        }
    }

    /**
     * 小区业主
     */
    public function villageUserBind()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        $pageSize = isset($_GET['limit']) && $_GET['limit'] > 0 ? intval($_GET['limit']) : $this->pageSize;
        $villageId = isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $bind_uid = isset($_GET['bind_uid']) ? intval($_GET['bind_uid']) : 0;
        if ($page > 0) {
            $single_id = [];
            if($bind_uid > 0){
                $house_keeper_info = M('House_services_housekeeper')->where(['village_id'=>$villageId,'is_kefu'=>1])->select();
                if($house_keeper_info){
                    foreach ($house_keeper_info as $v){
                        if($v['work_arr'] && $v['single_id']){
                            if(in_array($bind_uid,explode(',',$v['work_arr']))){
                                $single_id[] = $v['single_id'];
                            }
                        }
                    }
                }
            }
            $where = ['b.village_id' => $villageId, 'b.uid' => ['gt', 0]];
            if (!empty(trim($_GET['keyword']))) {
                $where['b.name|b.phone'] = ['LIKE', '%' . trim($_GET['keyword']) . '%'];
            }
            if(!empty($single_id)){
                $where['b.single_id'] = array('in',$single_id);
            }
            $where['b.type'] = array('in','0,1,2,3');
            $where['b.status'] = 1;
            $count = D('House_village_user_bind')->alias('b')->where($where)->count('distinct b.uid');
            $fans = D('House_village_user_bind')->alias('b')
                ->join(C('DB_PREFIX') . 'user AS u ON u.uid = b.uid')
                ->where($where)->group('b.uid')->limit(($page - 1) * $pageSize . ',' . $pageSize)->select();
            foreach ($fans as &$kefu1) {
                if ($kefu1['avatar']) {
                    $kefu1['avatar'] = replace_file_domain($kefu1['avatar']);
                } else {
                    $kefu1['avatar'] = $this->config['site_url'] . '/static/images/user_avatar.jpg';
                }
            }
            layerOk($count, $fans);
        } else {
            $this->assign('bind_uid',$bind_uid);
            $this->assign('village_id', $villageId);
            $this->display();
        }
    }
}