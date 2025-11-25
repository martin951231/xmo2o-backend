<?php
/**
 * Created by PhpStorm.
 * User: 郭杰
 * Date: 2019/12/11
 * Time: 15:52
 */
class HouseExpressAction extends BaseAction {

    // 实名认证
    public function authentication() {


        if($_GET['status'] == 2){
            $where['authentication_status'] =array('in','2,3');
            $this->assign('status',2);
        }elseif($_GET['status'] == 4){
            $where['authentication_status'] =4;
            $this->assign('status',4);
        }else{
            $where['authentication_status'] =1;
            $this->assign('status',1);
        }

        $count = D('Express_authentication')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $authentication_list = D('Express_authentication')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('authentication_list', $authentication_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function authentication_check(){
        if(IS_POST){

            $data['examine_time'] = time();
            if($_POST['authentication_status'] == 3){
                $data['note'] = $_POST['note'];
            }
            $data['authentication_status'] = $_POST['authentication_status'];
            $info = D('Express_authentication')->where(array('authentication_id'=>$_POST['authentication_id']))->find();

            $res = D('Express_authentication')->where(array('authentication_id'=>$_POST['authentication_id']))->data($data)->save();
            if($res){

                if($_POST['authentication_status'] == 2){
                    $user_info = D("User")->where(array('uid'=>$info['uid']))->field('uid,openid,nickname')->find();
                    if ($user_info['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=authentication';
                        $model->sendTempMsg('OPENTM405462911',
                            array(
                                'href' => $href,
                                'wecha_id' => $user_info['openid'],
                                'first' => '您提交的服务认证，已经通过系统审核啦。\n',
                                'keyword1' => '认证审核通过',
                                'keyword2' =>'已发送',
                                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                'keyword4' => $user_info['nickname'],
                                'remark' => '\n请点击查看详细信息！'
                            )
                        );
                    }
                }elseif ($_POST['authentication_status'] == 3) {
                    $user_info = D("User")->where(array('uid'=>$info['uid']))->field('uid,openid,nickname')->find();
                    if ($user_info['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=authentication';
                        $model->sendTempMsg('OPENTM405462911',
                            array(
                                'href' => $href,
                                'wecha_id' => $user_info['openid'],
                                'first' => '您提交的服务认证，系统审核失败请重新编辑提交。\n',
                                'keyword1' => '认证审核失败',
                                'keyword2' =>'已发送',
                                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                'keyword4' => $user_info['nickname'],
                                'remark' => '\n请点击查看详细信息！'
                            )
                        );
                    }
                }

                $this->success('审核成功！');
            }else{
                $this->error('审核失败！请重试！');
            }
        }else{
            if($_GET['status'] == 1){
                $this->assign('status',$_GET['status']);
            }
            $authenticationInfo = D('Express_authentication')->where(array('authentication_id'=>$_GET['id']))->find();
            $authenticationInfo['authentication_field'] = unserialize($authenticationInfo['authentication_field']);
            $this->assign('authenticationInfo',$authenticationInfo);
            $this->display();
        }

    }

    // 黑名单处理
    public function authentication_del() {
        if($_GET['status'] == 4){
            D('Express_authentication')->where(array('authentication_id'=>$_POST['id']))->save(array('authentication_status'=>4));
            $this->success('加入黑名单成功！');
        }elseif($_GET['status'] == 2){
            D('Express_authentication')->where(array('authentication_id'=>$_POST['id']))->save(array('authentication_status'=>2));
            $this->success('移除黑名单成功！');
        }

    }

    // 实名认证
    public function authentication_config() {
        $authentication_config_list = D('Express_authentication_config')->select();
        $this->assign('authentication_config_list',$authentication_config_list);
        $this->display();
    }
    // 实名认证
    public function authentication_config_add() {
        if(IS_POST){
            $data = $_POST;
            $data['add_time'] = time();
            $info = D('Express_authentication_config')->where(array('key'=>$_POST['key']))->find();

            if($info){
                $this->error('添加失败！唯一标识已存在！');
                die;
            }
            $res = D('Express_authentication_config')->data($data)->add();
            if($res){
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！请重试！');
            }
        }else{
            $this->display();
        }

    }
    // 实名认证
    public function authentication_config_edit() {
        if(IS_POST){

            $info = D('Express_authentication_config')->where(array('acid'=>array('neq',$_POST['acid']),'key'=>trim($_POST['key'])))->find();
            if($info){
                $this->error('修改失败！唯一标识已存在！');
                die;
            }
            $data = $_POST;
            $res = D('Express_authentication_config')->where(array('acid'=>$_POST['acid']))->data($data)->save();
            if($res){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试！');
            }
        }else{
            $info = D('Express_authentication_config')->where(array('acid'=>$_GET['acid']))->find();
            $this->assign('info',$info);
            $this->display();
        }

    }
    // 实名认证
    public function authentication_config_del() {
        D('Express_authentication_config')->where(array('acid'=>$_POST['acid']))->delete();
        $this->success('删除成功！');
    }
}