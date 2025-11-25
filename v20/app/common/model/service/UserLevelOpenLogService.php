<?php
/**
 * 用户等级相关操作记录
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 19:26
 */

namespace app\common\model\service;

use app\common\model\db\UserLevelOpenLog;

class UserLevelOpenLogService
{
    public $userLevelOpenLogObj = null;
    public function __construct()
    {
        $this->userLevelOpenLogObj = new UserLevelOpenLog();
    }

    public function addReward($uid, $level_info, $log_uid, $type = 0, $log_type = 2, $open_time = 0, $open_type = 0, $log_info = '', $score_used_count = 0, $balance_pay = 0, $no_is_log = false, $payment_money = 0)
    {
        fdump(date('Y-m-d H:i:s'), 'api/log/' . date('Ymd') . '/add_reward_log', true);
        $level_id = 0;
        $level = 0;
        $end_time = 0;
        $get_score = 0;
        // 用户信息
        $now_user = D('User')->field(true)->where(array('uid' => $uid))->find();
        if ($level_info) {
            $level_id = $level_info['id'];
            $level = $level_info['level'];
            if ($level_info['open_give_score'] > 0 && $uid > 0 && $log_type == 2 && $open_type > 0) {
                if (!empty($now_user)) {
                    // 如果存在 开通获得积分，赠送对应积分
                    D('User')->add_score($uid, round($level_info['open_give_score']), "用户开通会员等级 {$level_info['lname']} 获得" . C('config.score_name'));
                    D('Scroll_msg')->add_msg('level', $uid, '用户' . str_replace_name($now_user['nickname']) . '于' . date('Y-m-d H:i', $_SERVER['REQUEST_TIME']) . '开通会员等级 ' . $level_info['lname'] . ' 获得' . C('config.score_name'));
                    $get_score = round($level_info['open_give_score']);
                }
            }
            if ($open_time > 0 && $level_info['validity'] > 0) {
                // 计算到期时间
                $end_time = $open_time + (86400 * $level_info['validity']);
            }
        }

        // 如果使用了余额，进行三级分佣处理
        if ($balance_pay > 0) {
            fdump('------$balance_pay---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
            fdump($balance_pay, 'api/log/' . date('Ymd') . '/add_reward_log', true);
            $where['uid'] = array('eq', $now_user['uid']);
            fdump($where, 'api/log/' . date('Ymd') . '/add_reward_log', true);
            $userSpread = D('User_spread')->where($where)->find();//用户搜一级
            fdump($userSpread, 'api/log/' . date('Ymd') . '/add_reward_log', true);
            if (!empty($userSpread)) {
                $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                //存在一级
                $userSpreadOne = D('User_spread')->where(array('uid' => $userSpread['spread_uid']))->find();
                if (!empty($userSpreadOne)) {
                    //一级
                    $userOne = D('User')->field(true)->where(array('uid' => $userSpreadOne['uid']))->find();
                    $level_spread_rate = C('config.level_spread_rate');
                    $first_open_money = $balance_pay * $level_spread_rate * 0.01;
                    if (!empty($userOne) && $level_spread_rate > 0) {

                        $spread_money = $first_open_money;
                        $spread_data = array('status' => 1, 'uid' => $userOne['uid'], 'spread_uid' => 0, 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                        if ($userOne['spread_change_uid'] != 0) {
                            $spread_data['change_uid'] = $userOne['spread_change_uid'];
                        }
                        $ad = D('User_spread_list')->data($spread_data)->add();
                        if (!$ad) {
                            fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                            fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                            fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                        }
                        D('User')->add_money($userOne['uid'], $first_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');

                        $model->sendTempMsg('OPENTM201812627',
                            array(
                                'href' => $href,
                                'wecha_id' => $userOne['openid'],
                                'wxapp_openid' => $userOne['wxapp_openid'],
                                'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                'keyword1' => $spread_money,
                                'keyword2' => date("Y年m月d日 H:i"),
                                'remark' => L_('点击查看详情！')
                            ), 0);
                    } elseif ($level_spread_rate > 0) {//理论上讲，这个elseif应该是走不到的 (因为现在改用uid来查询用户了，查不到就GG了)
                        $userOneO = D('User')->field(true)->where(array('openid' => $userSpreadOne['openid']))->find();
                        if (!empty($userOneO)) {

                            $spread_money = $first_open_money;
                            $spread_data = array('status' => 1, 'uid' => $userOne['uid'], 'spread_uid' => 0, 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                            if ($userOne['spread_change_uid'] != 0) {
                                $spread_data['change_uid'] = $userOne['spread_change_uid'];
                            }
                            $ad = D('User_spread_list')->data($spread_data)->add();
                            if (!$ad) {
                                fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                            }
                            D('User')->add_money($userOneO['uid'], $first_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                            $model->sendTempMsg('OPENTM201812627',
                                array(
                                    'href' => $href,
                                    'wecha_id' => $userOne['openid'],
                                    'wxapp_openid' => $userOne['wxapp_openid'],
                                    'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                    'keyword1' => $spread_money,
                                    'keyword2' => date("Y年m月d日 H:i"),
                                    'remark' => L_('点击查看详情！')
                                ), 0);
                        }
                    }
                    //二级
                    $userSpreadTwo = D('User_spread')->where(array('uid' => $userSpreadOne['spread_uid']))->find();
                    if (!empty($userSpreadTwo)) {
                        $userTwo = D('User')->field(true)->where(array('uid' => $userSpreadTwo['uid']))->find();
                        $level_first_spread_rate = C('config.level_first_spread_rate');
                        $second_open_money = $balance_pay * $level_first_spread_rate * 0.01;
                        if (!empty($userTwo) && $level_first_spread_rate > 0) {

                            $spread_money = $second_open_money;
                            $spread_data = array('status' => 1, 'uid' => $userTwo['uid'], 'spread_uid' => $userOne['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                            if ($userOne['spread_change_uid'] != 0) {
                                $spread_data['change_uid'] = $userOne['spread_change_uid'];
                            }
                            $ad = D('User_spread_list')->data($spread_data)->add();
                            if (!$ad) {
                                fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                            }
                            D('User')->add_money($userTwo['uid'], $second_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                            $model->sendTempMsg('OPENTM201812627',
                                array(
                                    'href' => $href,
                                    'wecha_id' => $userTwo['openid'],
                                    'wxapp_openid' => $userTwo['wxapp_openid'],
                                    'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                    'keyword1' => $spread_money,
                                    'keyword2' => date("Y年m月d日 H:i"),
                                    'remark' => L_('点击查看详情！')
                                ), 0);
                        } elseif ($level_first_spread_rate > 0) {//理论上讲，这个elseif应该是走不到的 (因为现在改用uid来查询用户了，查不到就GG了)
                            $userTwoO = D('User')->field(true)->where(array('openid' => $userSpreadTwo['openid']))->find();
                            if (!empty($userTwoO)) {

                                $spread_money = $second_open_money;
                                $spread_data = array('status' => 1, 'uid' => $userTwo['uid'], 'spread_uid' => $userOne['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                                if ($userOne['spread_change_uid'] != 0) {
                                    $spread_data['change_uid'] = $userOne['spread_change_uid'];
                                }
                                $ad = D('User_spread_list')->data($spread_data)->add();
                                if (!$ad) {
                                    fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                    fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                    fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                }
                                D('User')->add_money($userTwoO['uid'], $second_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                                $model->sendTempMsg('OPENTM201812627',
                                    array(
                                        'href' => $href,
                                        'wecha_id' => $userTwo['openid'],
                                        'wxapp_openid' => $userTwo['wxapp_openid'],
                                        'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                        'keyword1' => $spread_money,
                                        'keyword2' => date("Y年m月d日 H:i"),
                                        'remark' => L_('点击查看详情！')
                                    ), 0);
                            }
                        }
                        //三级
                        $userSpreadThree = D('User_spread')->where(array('uid' => $userSpreadTwo['spread_uid']))->find();
                        if (!empty($userSpreadThree)) {
                            $userThree = D('User')->field(true)->where(array('uid' => $userSpreadThree['uid']))->find();
                            $level_second_spread_rate = C('config.level_second_spread_rate');
                            $third_open_money = $balance_pay * $level_second_spread_rate * 0.01;
                            if (!empty($userThree) && $level_second_spread_rate > 0) {

                                $spread_money = $second_open_money;
                                $spread_data = array('status' => 1, 'uid' => $userThree['uid'], 'spread_uid' => $userTwoO['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                                if ($userOne['spread_change_uid'] != 0) {
                                    $spread_data['change_uid'] = $userOne['spread_change_uid'];
                                }
                                D('User_spread_list')->data($spread_data)->add();
                                D('User')->add_money($userThree['uid'], $third_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                                $model->sendTempMsg('OPENTM201812627',
                                    array(
                                        'href' => $href,
                                        'wecha_id' => $userThree['openid'],
                                        'wxapp_openid' => $userThree['wxapp_openid'],
                                        'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                        'keyword1' => $spread_money,
                                        'keyword2' => date("Y年m月d日 H:i"),
                                        'remark' => L_('点击查看详情！')
                                    ), 0);
                            } elseif ($level_second_spread_rate > 0) {//理论上讲，这个elseif应该是走不到的 (因为现在改用uid来查询用户了，查不到就GG了)
                                $userThreeO = D('User')->field('uid,now_money')->where(array('openid' => $userSpreadThree['openid']))->find();
                                if (!empty($userThreeO)) {

                                    $spread_money = $second_open_money;
                                    $spread_data = array('status' => 1, 'uid' => $userThree['uid'], 'spread_uid' => $userTwoO['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                                    if ($userOne['spread_change_uid'] != 0) {
                                        $spread_data['change_uid'] = $userOne['spread_change_uid'];
                                    }
                                    $ad = D('User_spread_list')->data($spread_data)->add();
                                    if (!$ad) {
                                        fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                        fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                        fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                    }
                                    D('User')->add_money($userThreeO['uid'], $third_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                                    $model->sendTempMsg('OPENTM201812627',
                                        array(
                                            'href' => $href,
                                            'wecha_id' => $userThree['openid'],
                                            'wxapp_openid' => $userThree['wxapp_openid'],
                                            'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                            'keyword1' => $spread_money,
                                            'keyword2' => date("Y年m月d日 H:i"),
                                            'remark' => L_('点击查看详情！')
                                        ), 0);
                                }
                            }
                        } else {
                            //没有三级结束
                            $userThree = D('User')->field('uid,now_money')->where(array('uid' => $userSpreadTwo['spread_uid']))->find();
                            $level_second_spread_rate = C('config.level_second_spread_rate');
                            $third_open_money = $balance_pay * $level_second_spread_rate * 0.01;
                            if (!empty($userThree) && $level_second_spread_rate > 0) {

                                $spread_money = $second_open_money;
                                $spread_data = array('status' => 1, 'uid' => $userThree['uid'], 'spread_uid' => $userTwoO['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                                if ($userOne['spread_change_uid'] != 0) {
                                    $spread_data['change_uid'] = $userOne['spread_change_uid'];
                                }
                                $ad = D('User_spread_list')->data($spread_data)->add();
                                if (!$ad) {
                                    fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                    fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                    fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                }
                                D('User')->add_money($userThree['uid'], $third_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                                $model->sendTempMsg('OPENTM201812627',
                                    array(
                                        'href' => $href,
                                        'wecha_id' => $userThree['openid'],
                                        'wxapp_openid' => $userThree['wxapp_openid'],
                                        'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                        'keyword1' => $spread_money,
                                        'keyword2' => date("Y年m月d日 H:i"),
                                        'remark' => L_('点击查看详情！')
                                    ), 0);
                            } elseif ($level_second_spread_rate > 0) {//理论上讲，这个elseif应该是走不到的 (因为现在改用uid来查询用户了，查不到就GG了)
                                $userThreeO = D('User')->field('uid,now_money')->where(array('openid' => $userSpreadTwo['spread_openid']))->find();
                                if (!empty($userThreeO)) {

                                    $spread_money = $second_open_money;
                                    $spread_data = array('status' => 1, 'uid' => $userThree['uid'], 'spread_uid' => $userTwoO['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                                    if ($userOne['spread_change_uid'] != 0) {
                                        $spread_data['change_uid'] = $userOne['spread_change_uid'];
                                    }
                                    $ad = D('User_spread_list')->data($spread_data)->add();
                                    if (!$ad) {
                                        fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                        fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                        fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                    }
                                    D('User')->add_money($userThreeO['uid'], $third_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                                    $model->sendTempMsg('OPENTM201812627',
                                        array(
                                            'href' => $href,
                                            'wecha_id' => $userThreeO['openid'],
                                            'wxapp_openid' => $userThreeO['wxapp_openid'],
                                            'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                            'keyword1' => $spread_money,
                                            'keyword2' => date("Y年m月d日 H:i"),
                                            'remark' => L_('点击查看详情！')
                                        ), 0);
                                }
                            }
                        }
                    } else {
                        //二级
                        $userTwo = D('User')->field('uid,now_money')->where(array('uid' => $userSpreadOne['spread_uid']))->find();
                        $level_first_spread_rate = C('config.level_first_spread_rate');
                        $second_open_money = $balance_pay * $level_first_spread_rate * 0.01;
                        if (!empty($userTwo) && $level_first_spread_rate > 0) {

                            $spread_money = $second_open_money;
                            $spread_data = array('status' => 1, 'uid' => $userTwo['uid'], 'spread_uid' => $userOne['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                            if ($userOne['spread_change_uid'] != 0) {
                                $spread_data['change_uid'] = $userOne['spread_change_uid'];
                            }
                            $ad = D('User_spread_list')->data($spread_data)->add();
                            if (!$ad) {
                                fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                            }
                            D('User')->add_money($userTwo['uid'], $second_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                            $model->sendTempMsg('OPENTM201812627',
                                array(
                                    'href' => $href,
                                    'wecha_id' => $userTwo['openid'],
                                    'wxapp_openid' => $userTwo['wxapp_openid'],
                                    'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                    'keyword1' => $spread_money,
                                    'keyword2' => date("Y年m月d日 H:i"),
                                    'remark' => L_('点击查看详情！')
                                ), 0);
                        } elseif ($level_first_spread_rate > 0) {//理论上讲，这个elseif应该是走不到的 (因为现在改用uid来查询用户了，查不到就GG了)
                            $userTwoO = D('User')->field(true)->where(array('openid' => $userSpreadOne['spread_openid']))->find();
                            if (!empty($userTwoO)) {

                                $spread_money = $second_open_money;
                                $spread_data = array('status' => 1, 'uid' => $userTwo['uid'], 'spread_uid' => $userOne['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                                if ($userOne['spread_change_uid'] != 0) {
                                    $spread_data['change_uid'] = $userOne['spread_change_uid'];
                                }
                                $ad = D('User_spread_list')->data($spread_data)->add();
                                if (!$ad) {
                                    fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                    fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                    fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                }
                                D('User')->add_money($userTwoO['uid'], $second_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                                $model->sendTempMsg('OPENTM201812627',
                                    array(
                                        'href' => $href,
                                        'wecha_id' => $userTwoO['openid'],
                                        'wxapp_openid' => $userTwoO['wxapp_openid'],
                                        'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                        'keyword1' => $spread_money,
                                        'keyword2' => date("Y年m月d日 H:i"),
                                        'remark' => L_('点击查看详情！')
                                    ), 0);
                            }
                        }
                    }
                } else {
                    //一级
                    $userOne = D('User')->field('uid,now_money')->where(array('uid' => $userSpread['spread_uid']))->find();
                    $level_spread_rate = C('config.level_spread_rate');
                    $first_open_money = $balance_pay * $level_spread_rate * 0.01;
                    if (!empty($userOne) && $level_spread_rate > 0) {

                        $spread_money = $first_open_money;
                        $spread_data = array('status' => 1, 'uid' => $userOne['uid'], 'spread_uid' => 0, 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => $_SERVER['REQUEST_TIME']);
                        if ($userOne['spread_change_uid'] != 0) {
                            $spread_data['change_uid'] = $userOne['spread_change_uid'];
                        }
                        D('User_spread_list')->data($spread_data)->add();
                        D('User')->add_money($userOne['uid'], $first_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                        $model->sendTempMsg('OPENTM201812627',
                            array(
                                'href' => $href,
                                'wecha_id' => $userOne['openid'],
                                'wxapp_openid' => $userOne['wxapp_openid'],
                                'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                'keyword1' => $spread_money,
                                'keyword2' => date("Y年m月d日 H:i"),
                                'remark' => L_('点击查看详情！')
                            ), 0);
                    } elseif ($level_spread_rate > 0) {//理论上讲，这个elseif应该是走不到的 (因为现在改用uid来查询用户了，查不到就GG了)
                        $userOneO = D('User')->field(true)->where(array('openid' => $userSpread['spread_openid']))->find();
                        if (!empty($userOneO)) {

                            $spread_money = $first_open_money;
                            $spread_data = array('status' => 1, 'uid' => $userOne['uid'], 'spread_uid' => 0, 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'user_level', 'order_id' => $level_info['id'], 'third_id' => $level_info['level'], 'add_time' => time());
                            if ($userOne['spread_change_uid'] != 0) {
                                $spread_data['change_uid'] = $userOne['spread_change_uid'];
                            }
                            $ad = D('User_spread_list')->data($spread_data)->add();
                            if (!$ad) {
                                fdump('------User_spread_list---------' . __LINE__, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                fdump(D('User_spread_list')->getDbError(), 'api/log/' . date('Ymd') . '/add_reward_log', true);
                                fdump($spread_data, 'api/log/' . date('Ymd') . '/add_reward_log', true);
                            }
                            D('User')->add_money($userOneO['uid'], $first_open_money, '用户' . str_replace_name($now_user['nickname']) . '开通会员等级，增加分佣');
                            $model->sendTempMsg('OPENTM201812627',
                                array(
                                    'href' => $href,
                                    'wecha_id' => $userOneO['openid'],
                                    'wxapp_openid' => $userOneO['wxapp_openid'],
                                    'first' => L_("用户X1通开通会员等级，增加分佣。", array("X1" => str_replace_name($now_user['nickname']))),
                                    'keyword1' => $spread_money,
                                    'keyword2' => date("Y年m月d日 H:i"),
                                    'remark' => L_('点击查看详情！')
                                ), 0);
                        }
                    }

                }
            }
        }
        if ($no_is_log) {
            return true;
        }

        return $this->add_row($uid, $log_uid, $type, $level_id, $level, $log_type, $open_time, $end_time, $open_type, $log_info, $score_used_count, $balance_pay, $get_score, $payment_money);
    }
}