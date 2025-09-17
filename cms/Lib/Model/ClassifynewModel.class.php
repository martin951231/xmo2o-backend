<?php
class ClassifynewModel extends Model{
    public function add_spread_money($money,$uid)
    {
        if ($money<=0) {
            return array('error'=>0,'msg'=>'金额不能小于0');
        }
        $spread_total_money = $money;
        $now_user = D('User')->get_user($uid);
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        if(!empty($now_user['openid'])&&C('config.open_user_spread') && (!C('config.open_spread_appoint_user') || ($now_user['can_spread']==1 && !C('config.open_spread_only_once'))) && (C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money) && !C('config.open_spread_rand')){
            //上级分享佣金
            $spread_rate = D('Percent_rate')->get_user_spread_rate(0,'classifynew',0,['uid'=>$uid]);
            $spread_type = 'classifynew';

            $spread_users[]=$now_user['uid'];
                $spread_where['_string'] = "uid = '{$now_user['uid']}'";
            $now_user_spread = D('User_spread')->where($spread_where)->find();

            $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
            if(!empty($now_user_spread)){
                $spread_user = D('User')->get_user($now_user_spread['spread_uid']);

                $user_spread_rate = $spread_rate['first_rate'];
                if($spread_user && $user_spread_rate && !in_array($spread_user['uid'],$spread_users)){
                    $spread_money = round(($spread_total_money) * $user_spread_rate / 100, 2);
                    $spread_data = array(
                        'uid'=>$spread_user['uid'],
                        'spread_uid'=>0,
                        'get_uid'=>$now_user['uid'],
                        'money'=>$spread_money,
                        'order_type'=>$spread_type,
                        'order_id'=>0,
                        'third_id'=>0,
                        'status'=>1,
                        'add_time'=>$_SERVER['REQUEST_TIME']
                    );
                    if($spread_user['spread_change_uid']!=0){
                        $spread_data['change_uid'] = $spread_user['spread_change_uid'];
                    }
                    D('User_spread_list')->data($spread_data)->add();
                    $buy_user = D('User')->get_user($now_user_spread['uid'],'uid');
                    if($spread_money>0){
                        $money_name = L_('佣金');
                        $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' =>L_('x1通过您的分享在x2消费，您将获得x3。',array('x1'=>$buy_user['nickname'],'x2'=>C('config.classify_title'),'x3'=>$money_name)), 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), 0);
                        $res = D('User')->add_money($spread_user['uid'],$spread_money,L_('推广用户X1消费获得的佣金',$buy_user['nickname']));
                        if($res['error_code']){
                            fdump_sql($res['msg'],'classifynew_spread_1');
                        }
                    }
                    $spread_users[]=$spread_user['uid'];
                }

                //第二级分享佣金
                $spread_where['_string'] = "uid = '{$spread_user['uid']}' ";
                
                $second_user_spread = D('User_spread')->where($spread_where)->find();

                if(!empty($second_user_spread) ) {
                    $second_user = D('User')->get_user($second_user_spread['spread_uid']);
                    $sub_user_spread_rate = $spread_rate['second_rate'];
                    if ($second_user && $sub_user_spread_rate&&!in_array($second_user['uid'],$spread_users)) {
                        $spread_money = round(($spread_total_money) * $sub_user_spread_rate / 100, 2);
                        $spread_sec_data =array(
                            'uid' => $second_user['uid'],
                            'spread_uid' => $spread_user['uid'],
                            'get_uid' => $now_user['uid'],
                            'money' => $spread_money,
                            'order_type' => $spread_type,
                            'order_id' => 0,
                            'third_id' => 0,
                            'status'=>1,
                            'add_time' => $_SERVER['REQUEST_TIME']
                        );
                        if($second_user['spread_change_uid']!=0){
                            $spread_sec_data['change_uid'] = $second_user['spread_change_uid'];
                        }
                        D('User_spread_list')->data($spread_sec_data)->add();
                        $sec_where['_string'] = "uid = '{$second_user_spread['uid']}'";
                        $sec_user = M('User')->where($sec_where)->find();
                    
                        if($spread_money>0) {
                            $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_('x1的子用户x2通过您的分享在x3消费，您将获得佣金。',array('x1'=>$sec_user['nickname'],'x2'=>$buy_user['nickname'],'x3'=>C('config.classify_title'))), 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')),  0);
                            $res = D('User')->add_money($second_user['uid'],$spread_money,L_('子用户推广用户X1消费获得的佣金',$buy_user['nickname']));
                            if($res['error_code']){
                                fdump_sql($res['msg'],'classifynew_spread_2');
                            }
                        }
                        $spread_users[]=$second_user['uid'];
                    }

                    //顶级分享佣金
                    $spread_where['_string'] = "uid = '{$second_user['uid']}' ";
                    $first_user_spread = D('User_spread')->where($spread_where)->find();

                    if (!empty($first_user_spread) && C('config.user_third_level_spread')) {
                        $first_spread_user = D('User')->get_user($first_user_spread['spread_uid']);
                        $sub_user_spread_rate = $spread_rate['third_rate'];
                        if ($first_spread_user && $sub_user_spread_rate&&!in_array($first_spread_user['uid'],$spread_users)) {
                            $spread_money = round(($spread_total_money) * $sub_user_spread_rate / 100, 2);
                            $spread_thd_data=array(
                                'uid' => $first_spread_user['uid'],
                                'spread_uid' => $second_user['uid'],
                                'get_uid' => $now_user['uid'],
                                'money' => $spread_money,
                                'order_type' => $spread_type,
                                'order_id' => 0,
                                'third_id' => 0,
                                'status'=>1,
                                'add_time' => $_SERVER['REQUEST_TIME']
                            );
                            if($first_spread_user['spread_change_uid']!=0){
                                $spread_thd_data['change_uid'] = $first_spread_user['spread_change_uid'];
                            }
                            D('User_spread_list')->data($spread_thd_data)->add();

                            $fir_where['_string'] = "uid = '{$first_user_spread['uid']}'";
                            $fir_user = M('User')->where($fir_where)->find();
                            if($spread_money>0) {
                                $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>L_('x1的子用户的子用户x2通过您的分享在x3消费，您将获得佣金。',array('x1'=>$fir_user['nickname'],'x2'=>$buy_user['nickname'],'x3'=>C('config.classify_title'))), 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), 0);
                                $res = D('User')->add_money($first_spread_user['uid'],$spread_money,L_('子用户的子用户推广用户X1消费获得的佣金',$buy_user['nickname']));
                                if($res['error_code']){
                                    fdump_sql($res['msg'],'classifynew_spread_3');
                                }
                            }
                        }
                    }
                }
            }
        }elseif(!empty($now_user['openid'])&&C('config.open_spread_rand')){
            /* 开启三级推广随机一级推广奖励功能，唯一次奖励 */
            //一级推广用户
            $spread_res = D('User_spread')->spread_rand_only_once('classifynew',$now_user);
        }elseif(!empty($now_user['openid'])&&C('config.open_spread_appoint_user') && $now_user['can_spread']==1 && C('config.open_spread_only_once')&&(C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)){
            /* 开启指定用户享受三级推广功能且开启唯一次奖励 */
            //一级推广用户
            $spread_res = D('User_spread')->spread_user_only_once('classifynew',$spread_total_money,$now_user);
        }
    }
}
?>