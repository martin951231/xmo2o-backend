<?php
/**
 * @author : liukezhu
 * @date : 2021/7/5
 */
class ForumAction extends BaseAction{


    public function _initialize()
    {
        parent::_initialize();
        /**
         * todo 论坛帖子 开关状态
         */
        $switch= D('config')->where(['name'=>'house_forum_switch'])->getField('value');
        if (intval($switch) < 1){
            die('论坛关闭');
        }
    }

    /**
     * 论坛列表
     * @author: liukezhu
     * @date : 2021/7/5
     */
    public function forum_list(){
        $db_house_forum=M('house_forum');
        $where = array();
        if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }else{
                $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
                $where['push_time'] = [
                    ['EGT', $period[0]],
                    ['ELT', $period[1]]
                ];
            }
        }
        $where['status']=1;
        import('@.ORG.system_page');
        $count =$db_house_forum->where($where)->count();
        $p = new Page($count,20);
        $list = $db_house_forum->where($where)->order('add_time DESC')->field('id,title,content,push_time')->limit($p->firstRow,$p->listRows)->select();
        if ($list) {
            foreach ($list as &$val) {
                if(!empty($val['push_time'])){
                    $val['push_time']=date('Y-m-d H:i:s',$val['push_time']);
                }else{
                    $val['push_time']='未推送';
                }
            }
            unset($val);
        }
        $pagebar = $count>0?$p->show():'';
        $this->assign('adver_list',$list);
        $this->assign('pagebar',$pagebar);
        $this->display();
    }

    /**
     *添加帖子
     * @author: liukezhu
     * @date : 2021/7/6
     */
    public function forum_add(){
        if(IS_POST){
            if(empty($_POST['title'])){
                $this->error('请输入标题');
            }
            if(empty($_POST['url'])){
                $this->error('请输入链接');
            }
            $params=array(
                'title'=>trim($_POST['title']),
                'url'=>htmlspecialchars_decode($_POST['url']),
                'add_time'=>time()
            );
            if(!empty($_POST['content'])){
                $params['content']=trim($_POST['content']);
            }
            if(!empty($_POST['img'])){
                $params['img']=trim($_POST['img']);
            }
            $rel = D('House_forum')->data($params)->add();
            if($rel){
                $this->frame_submit_tips(1,'添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->display();
        }
    }


    /**
     *编辑帖子
     * @author: liukezhu
     * @date : 2021/7/6
     */
    public function forum_edit(){
        $db_House_forum=D('House_forum');
        if(IS_POST){
            if(empty($_POST['id'])){
                $this->error('缺少必要参数');
            }
            if(empty($_POST['title'])){
                $this->error('请输入标题');
            }
            if(empty($_POST['url'])){
                $this->error('请输入链接');
            }
            $params=array(
                'title'=>trim($_POST['title']),
                'url'=>htmlspecialchars_decode($_POST['url']),
                'update_time'=>time()
            );
            if(!empty($_POST['content'])){
                $params['content']=trim($_POST['content']);
            }
            if(!empty($_POST['img'])){
                $params['img']=trim($_POST['img']);
            }
            $db_House_forum->where(['id'=>$_POST['id']])->save($params);
            $this->frame_submit_tips(1,'编辑成功');
        }else{
            $data  =$db_House_forum->where(array('id'=>$_GET['id']))->find();
            $this->assign('rel',$data);
            $this->display();
        }
    }

    /**
     *删除
     * @author: liukezhu
     * @date : 2021/7/6
     */
    public function forum_del(){
        if(IS_POST){
            if(empty($_POST['id'])){
                $this->error('缺少必要参数');
            }
            $db_House_forum=D('House_forum');
            $list = $db_House_forum->where(['id'=>$_POST['id'],'status'=>1])->getField('id');
            if(!$list){
                $this->frame_submit_tips(0,'数据不存在');
            }
            $db_House_forum->where(array('id'=>$list))->save(['status'=>4,'update_time'=>time()]);
            $this->success('删除成功');
        }
    }

    /**
     *一键推送
     * @author: liukezhu
     * @date : 2021/7/6
     */
    public function forum_push(){
        if(IS_POST){
            if(empty($_POST['id'])){
                $this->error('缺少必要参数');
            }
            set_time_limit (0);
            $db_House_forum=D('House_forum');
            $list = $db_House_forum->where(['id'=>$_POST['id'],'status'=>1])->find();
            if(!$list){
                $this->error('数据不存在');
            }
            $where=array(
                'status'=>1
            );
            $time=time();
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $users = D('user')
                ->where($where)
                ->field('uid,openid,nickname')
                ->select();
            if(empty($users)){
                $this->error('暂无用户数据');
            }

            $db_House_forum->where(['id'=>$list['id']])->save(['push_time'=>$time,'update_time'=>$time]);
            $param = [
                'plan_send' =>1
            ];
            $msg=empty($list['content']) ? '论坛帖子' : $list['content'];
            import('@.ORG.Apppush');
            foreach ($users as $v){
                //todo app 极光推送
                $now_order['uid'] = $v['uid'];
                $now_order['bind_id'] = $v['uid'];
                $now_order['url'] = $list['url'];
                $now_order['title'] = $list['title'];
                $now_order['msg'] =$msg;
                $apppush = new Apppush();
                $rel=$apppush->send($now_order, 'house_notice');
                fdump_api(['line:'.__LINE__,$v,$rel],'forum/forum_push_log',1);
                if(!empty($v['openid'])){
                    //todo 模板消息发送
                    $model->sendTempMsg('OPENTM405462911', array('href' => $list['url'], 'wecha_id' => $v['openid'], 'first' => $list['title'], 'keyword1' => $msg, 'keyword2' =>'已发送','keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 'keyword4' => $v['nickname'], 'remark' => '\n请点击查看详细信息！'),0,0,2,$param);
                }
            }
            $this->success('推送成功');

        }
    }


}