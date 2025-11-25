<?php

class LibraryAction extends BaseAction
{
    public function __construct(){
        parent::__construct();
        if($_GET['pigcms_id']){
            $bind_village= D('House_village_user_bind')->get_one_by_bindId($_GET['pigcms_id']);           
            $_SESSION['now_village_bind'] = $bind_village;
        }
    }

    //取快递页面 自己取 帮别人取
    public function receive()
    {
        $this->display();
    }

    //输入取货吗页面
    public function fetch_code()
    {
        $database_express = D('Express');
        $express_list = $database_express->get_express_list();
        $this->assign('express_list', $express_list);
        $this->display();
    }

    public function collection_list()
    {
        if (!$this->user_session) {
            $this->error_tips('请先进行登录', U('Login/index'));
        }

        if (!$_POST['fetch_code']) {
            $this->error_tips('请输入取件码！');
        }
        $database_house_village_express = D('House_village_express');
        $database_express = D('Express');
        $info = $database_house_village_express->where(array('fetch_code' => $_POST['fetch_code'], 'status' => 0))->find();
        if (is_array($info)) {
            $info['express_name'] = $database_express->where(array('id' => $info['express_type']))->getField('name');
            $list = $database_house_village_express->where(array('village_id' => $info['village_id'], 'phone' => $info['phone'], 'status' => 0, 'id' => array('neq', $info['id'])))->select();

        } else {
            $this->error_tips('没有查询到您要取的物品！', U('fetch_code'));
        }

        foreach ($list as $key => $value) {
            $list[$key]['express_name'] = $database_express->where(array('id' => $value['express_type']))->getField('name');
        }
        $this->assign('info', $info);
        $this->assign('list', $list);

        // $list = array();
        // foreach ($_POST['fetch_code'] as $key => $value) {
        //      $info = $database_house_village_express->where(array('fetch_code'=>$value))->find();
        //      $info['express_name'] = $database_express->where(array('id'=>$info['express_type']))->getField('name');
        //      $list[] = $info;
        // }
        // $this->assign('list',$list);
        $this->display();
    }

    //确认收货
    public function confirm_receipt()
    {
        $database_house_village_express = D('House_village_express');
        $res = $database_house_village_express->where(array('id' => $_POST['id']))->data(array('status' => 1, 'delivery_time' => time(), 'take_uid' => $this->user_session['uid'], 'take_nickname' => $this->user_session['nickname']))->save();
        if ($res) {
            exit(json_encode(array('error' => 1, 'msg' => '修改成功！')));
        } else {
            exit(json_encode(array('error' => 2, 'msg' => '修改失败，请重试！')));
        }
    }

    //确认收货--全部取货
    public function confirm_receipt_all()
    {
        $database_house_village_express = D('House_village_express');
        $res = $database_house_village_express->where(array('village_id' => $_POST['village_id'], 'phone' => $_POST['phone']))->data(array('status' => 1, 'delivery_time' => time(), 'take_uid' => $this->user_session['uid'], 'take_nickname' => $this->user_session['nickname']))->save();
        if ($res) {
            exit(json_encode(array('error' => 1, 'msg' => '修改成功！')));
        } else {
            exit(json_encode(array('error' => 2, 'msg' => '修改失败，请重试！')));
        }
    }


    public function express_service_list()
    {
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        if (isset($_GET['property_id']) && $_GET['property_id']>0 && !$_GET['village_id']) {
            $param = [
                'pageName' => 'villagelist',
                'isAll' => true,
                'urlParam' => 'property_id='.$_GET['property_id'].'&referer_type=express_service_list',
            ];
            $villagelistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            redirect($villagelistUrl);
        }
        if (!$this->user_session) {
            $this->error_tips('请先进行登录', U('Login/index'));
        }
        $param = [
            'pageName' => 'expressManagement',
            'isAll' => true,
            'urlParam' => 'village_id='.$_GET['village_id'],
        ];
        $expressManagementUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        redirect($expressManagementUrl);

        $village_id = $_GET['village_id'] + 0;

        $this->get_village($village_id);
        $database_house_village_express = D('House_village_express');

        $has_express_service = $this->getHasConfig($village_id, 'has_express_service');
        if ($has_express_service) {
            $where['village_id'] = $village_id;
            $where['uid'] = $this->user_session['uid'];
            $list = $database_house_village_express->express_service_list($where);
            $express_config = M('House_village_express_config')->where(array('village_id' => $village_id))->find();
            $express_config['status'] = $express_config['status'];
            $this->assign('list', $list['list']);
            $this->assign('express_config', $express_config);
            $this->display();
        } else {
            $this->error_tips(C('config.house_name').'未开通相关服务！');
        }
    }

    public function ajax_express_appoint()
    {
        if (IS_AJAX) {

            if (empty($this->user_session)) {
                exit(json_encode(array('status' => 0, 'info' => '未获取到您的帐号信息，请重试')));
            }


            $now_user = D('User')->get_user($this->user_session['uid']);
            if (empty($now_user)) {
                exit(json_encode(array('status' => 0, 'info' => '未获取到您的帐号信息，请重试')));
            }

            $use_money = $_POST['express_collection_price'] + 0;


            $database_house_village_express_order = D('House_village_express_order');

            $order_where['express_id'] = $_POST['express_id'] + 0;
            $order_where['paid'] = 0;

            $now_order = $database_house_village_express_order->house_village_express_order_detail($order_where);
            $now_order = $now_order['detail'];
            $express_config = M('House_village_express_config')->where(array('village_id' => $_POST['village_id']))->find();
            $time = strtotime($_POST['send_time']);
            $start_time = strtotime(date('G:i:s', $express_config['start_time']));
            $end_time = strtotime(date('G:i:s', $express_config['end_time']));

            // 配送时间段
            $e_time = strtotime(date('G:i:s', strtotime($_POST['send_time'])));

            if (empty($_POST['send_time'])) {
                exit(json_encode(array('status' => 0, 'info' => '预约时间不能为空！')));
            }

            // if ($time < $start_time) {
            //     exit(json_encode(array('status' => 0, 'info' => '预约时间不能比当前时间小')));
            // }

            // if ($time > $end_time) {
            //     exit(json_encode(array('status' => 0, 'info' => '该时间段物业不送货上门！')));
            // }
            //20180706
            if ($time < time()) {
                exit(json_encode(array('status' => 0, 'info' => '预约时间不能比当前时间小')));
            }
            if ($e_time<$start_time || $e_time>$end_time) {
                exit(json_encode(array('status' => 0, 'info' => '该时间段物业不送货上门！')));
            }

            if ($now_order) {
                $now_user = D('User')->get_user($this->user_session['uid']);
                if (empty($now_user)) {
                    $this->error_tips('未获取到您的帐号信息，请重试');
                }

                $use_money = $now_order['express_collection_price'];

                $pay_order_param = array(
                    'business_type' => 'house_village_express',
                    'business_id' => $now_order['order_id'],
                    'order_name' => '快递代送',
                    'uid' => $now_user['uid'],
                    'total_money' => $use_money,
                    'wx_cheap' => 0,
                );

                $plat_order_result = D('Plat_order')->add_order($pay_order_param);

                if ($plat_order_result['error_code']) {
                    $this->error($plat_order_result['error_msg']);
                } else {
                    $this->success('', U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));

                    // redirect(U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));
                }
                exit;
                if ($use_money != 0) {
                    if ($now_user['now_money'] < $use_money) {
                        exit(json_encode(array('order_id' => $now_order['order_id'], 'status' => -4, 'info' => '您的帐户余额为 <span>' . $now_user['now_money'] . '</span> '.C('config.Currency_txt').'，请先充值帐户余额', 'recharge' => $use_money - $now_user['now_money'])));
                    }
                    $save_result = D('User')->user_money($now_user['uid'], $use_money, '快递送达。');
                    if ($save_result['error_code']) {
                        exit(json_encode(array('status' => 0, 'info' => $save_result['error_code'])));
                    }

                    $data['paid'] = 1;
                    $data['pay_time'] = time();

                    $edit_where['order_id'] = $now_order['order_id'];
                    $result = $database_house_village_express_order->house_village_express_order_edit($edit_where, $data);

                    if (!$result) {
                        exit(json_encode(array('status' => 0, 'info' => '数据处理有误！')));
                    } else {
                        if ($result['status']) {
                            exit(json_encode(array('status' => 1, 'info' => '付费成功！')));
                        } else {
                            exit(json_encode(array('status' => 0, 'info' => '付费失败！')));
                        }
                    }
                } else {
                    $data['paid'] = 1;
                    $data['pay_time'] = time();

                    $edit_where['order_id'] = $now_order['order_id'];
                    $result = $database_house_village_express_order->house_village_express_order_edit($edit_where, $data);
                    exit(json_encode(array('status' => 1, 'info' => '提交成功！')));
                }
            }

            $result = $database_house_village_express_order->house_village_express_order_add($_POST);

            if ($result['status']==0) {
                exit(json_encode(array('status' => 0, 'info' => '数据处理有误！')));
            } else {
                $now_user = D('User')->get_user($this->user_session['uid']);
                if (empty($now_user)) {
                    $this->error_tips('未获取到您的帐号信息，请重试');
                }
                $order_where['express_id'] = $result['order_id'] + 0;
                $order_where['paid'] = 0;
                $now_order = $database_house_village_express_order->house_village_express_order_detail($order_where);
                $now_order = $now_order['detail'];
                $use_money = $now_order['express_collection_price'];

                $pay_order_param = array(
                    'business_type' => 'house_village_express',
                    'business_id' => $now_order['order_id'],
                    'order_name' => '快递代送',
                    'uid' => $now_user['uid'],
                    'total_money' => $use_money,
                    'wx_cheap' => 0,
                );

                $plat_order_result = D('Plat_order')->add_order($pay_order_param);
                if ($plat_order_result['error_code']) {
                    $this->error($plat_order_result['error_msg']);
                } else {
                    $this->success('', U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));

                    // redirect(U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));
                }

                exit;
                if ($result['status']) {
                    if ($use_money != 0) {
                        if ($now_user['now_money'] < $use_money) {
                            exit(json_encode(array('order_id' => $database_house_village_express_order->getLastInsID(), 'status' => -4, 'info' => '您的帐户余额为 <span>' . $now_user['now_money'] . '</span> '.C('config.Currency_txt').'，请先充值帐户余额', 'recharge' => $use_money - $now_user['now_money'])));
                        }
                        $save_result = D('User')->user_money($now_user['uid'], $use_money, '快递送达。');

                        if ($save_result['error_code']) {
                            exit(json_encode(array('status' => 0, 'info' => $save_result['error_code'])));
                        }

                        $data['paid'] = 1;
                        $data['pay_time'] = time();

                        $edit_where['order_id'] = $result['order_id'];
                        $result = $database_house_village_express_order->house_village_express_order_edit($edit_where, $data);

                        if (!$result) {
                            exit(json_encode(array('status' => 0, 'info' => '数据处理有误！')));
                        } else {
                            if ($result['status']) {
                                $this->send_to_village($_POST['village_id'], $_POST['express_id']);
                                exit(json_encode(array('status' => 1, 'info' => '付费成功！')));
                            } else {
                                exit(json_encode(array('status' => 1, 'info' => '付费失败！')));
                            }
                        }
                        exit(json_encode(array('status' => 1, 'info' => $result['msg'])));
                    } else {
                        $data['paid'] = 1;
                        $data['pay_time'] = time();

                        $edit_where['order_id'] = $result['order_id'];
                        $result = $database_house_village_express_order->house_village_express_order_edit($edit_where, $data);
                        $this->send_to_village($_POST['village_id'], $_POST['express_id']);
                        exit(json_encode(array('status' => 1, 'info' => '提交成功！')));
                    }

                } else {
                    exit(json_encode(array('status' => 0, 'info' => $result['msg'])));
                }
            }
        } else {
            $this->error_tips('访问页面有误！');
        }
    }

    public function send_to_village($village_id, $id)
    {
        $express_config = M('House_village_express_config')->where(array('village_id' => $village_id))->find();
        $order_info = M('House_village_express')->field('e.id,o.send_time,u.name,ex.name as express_name , e.express_no,v.village_name')->join('as e LEFT JOIN ' . C('DB_PREFIX') . 'house_village_express_order  o ON e.id = o.express_id left join ' . C('DB_PREFIX') . 'express ex ON e.express_type = ex.id left join ' . C('DB_PREFIX') . 'house_village v ON e.village_id = v.village_id left join  ' . C('DB_PREFIX') . 'house_village_user_bind u ON e.uid = u.uid')->where(array('e.village_id' => $_POST['village_id'], 'e.id' => $id))->find();
        if ($this->config['village_sms'] && $order_info['send_time'] > 0) {
            $sms_data = array('mer_id' => $express_config['village_id'], 'store_id' => 0, 'type' => 'village_express');
            $sms_data['uid'] = 0;
            $sms_data['mobile'] = $express_config['notice_phone'];
            $sms_data['sendto'] = 'village';
            $sms_data['content'] = '【提醒】您好，' . $order_info['village_name'] . $order_info['name'] . '业主，已预约快递代送服务，代送时间:' . date('Y-m-d H:i', $order_info['send_time']) . '，' . $order_info['express_name'] . '，单号：' . $order_info['express_no'] . '。[' . $this->config['site_name'] . ']';
            Sms::sendSms($sms_data);
        }
    }

    public function express_submit()
    {
        $order_id = $_GET['order_id'];
        $database_house_village_express_order = D('House_village_express_order');
        $where['order_id'] = $order_id;
        $order_info = $database_house_village_express_order->house_village_express_order_detail($where);
        $order_info = $order_info['detail'];

        if ($order_info['paid'] > 0) {
            $this->error_tips('该订单已支付。');
        }

        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录', U('Login/index'));
        }


        if ($now_user['now_money'] < $use_money) {
            $this->error_tips('您的帐户余额为 <span>' . $now_user['now_money'] . '</span> '.C('config.Currency_txt').'，请先充值帐户余额');
        }
        $save_result = D('User')->user_money($now_user['uid'], $use_money, '快递送达。');
        if ($save_result['error_code']) {
            $this->error_tips($save_result['error_code']);
        }

        $data['paid'] = 1;
        $data['pay_time'] = time();

        $edit_where['order_id'] = $order_id;
        $result = $database_house_village_express_order->house_village_express_order_edit($edit_where, $data);
        if (!$result) {
            $this->error_tips('数据处理有误！');
        } else {
            if ($result['status']) {
                $village_user['uid'] = $now_user['uid'];
                $village_user['village_id'] = $order_info['village_id'];

                $bind_user = D('House_village_user_bind')->house_village_user_bind_detail($village_user);

                $data_village_order['order_name'] = '快递代送';
                $data_village_order['order_type'] = 'express';
                $data_village_order['uid'] = $now_user['uid'];
                $data_village_order['village_id'] = $order_info['village_id'];
                $data_village_order['money'] = $order_info['express_collection_price'];
                $data_village_order['time'] = $data['pay_time'];
                $data_village_order['pay_time'] = $data['pay_time'];
                $data_village_order['paid'] = 1;
                $data_village_order['third_id'] = $order_id;
                $data_village_order['check_time'] = $data['pay_time'];
                $data_village_order['bind_id'] = $bind_user['info']['pigcms_id'];
                $data_village_order['pay_type'] = 2; //在线支付
                M('House_village_pay_order')->add($data_village_order);
                $this->success_tips('付费成功！', U('Library/express_service_list', array('village_id' => $village_user['village_id'])));
            } else {
                $this->error_tips('付费失败！');
            }
        }


    }

    public function express_appoint()
    {
        $village_id = $_GET['village_id'] + 0;
        $this->get_village($village_id);

        $datatabase_house_village_express_order = D('House_village_express_order');

        $where['express_id'] = $_GET['id'] + 0;
        $now_order = $datatabase_house_village_express_order->house_village_express_order_detail($where);

        $now_order = $now_order['detail'];

        $now_express = M('House_village_express')->where(array('id' => $_GET['id']))->find();
        if ($now_order) {
            if ($now_order['paid'] > 0) {
                $this->error_tips('订单已支付。请您耐心等待。');
            }
        }
        $express_config = M('House_village_express_config')->where(array('village_id' => $village_id))->find();
        $this->assign('express_config', $express_config);
        $this->assign('now_express', $now_express);
        $this->display();
    }

    public function submit()
    {
        if (empty($this->user_session)) {
            exit(json_encode(array('status' => 0, 'info' => '未获取到您的帐号信息，请重试')));
        }
        $now_user = D('User')->get_user($this->user_session['uid']);
        if (empty($now_user)) {
            exit(json_encode(array('status' => 0, 'info' => '未获取到您的帐号信息，请重试')));
        }

        $use_money = $_POST['express_collection_price'] + 0;

        if ($use_money != 0) {
            if ($now_user['now_money'] < $use_money) {
                exit(json_encode(array('status' => -4, 'info' => '您的帐户余额为 <span>' . $now_user['now_money'] . '</span> '.C('config.Currency_txt').'，请先充值帐户余额', 'recharge' => $use_money - $now_user['now_money'])));
            }
            $save_result = D('User')->user_money($now_user['uid'], $use_money, '快递代送');
            if ($save_result['error_code']) {
                exit(json_encode(array('status' => 0, 'info' => $save_result['error_code'])));
            }
        }
    }

    public function visitor_history_list()
    {
        if (!$this->user_session) {
            $this->error_tips('请先进行登录', U('Login/index'));
        }


        $database_house_village_visitor = D('House_village_visitor');

        $village_id = $_GET['village_id'] + 0;
        $this->get_village($village_id);
        $has_visitor = $this->getHasConfig($village_id, 'has_visitor');
        if ($has_visitor) {
            $where['village_id'] = $village_id;
            $where['owner_uid'] = $this->user_session['uid'];
            $where['status'] = array('neq',0);
            $list = $database_house_village_visitor->house_village_visitor_list($where);
            if (!$list) {
                $this->error('数据处理有误！');
            } else {
                $this->assign('list', $list['list']);
                $this->assign('visitor_type', $database_house_village_visitor->visitor_type);
            }
            $this->display();
        } else {
            $this->error_tips(C('config.house_name').'未开通相关服务！');
        }

    }

    public function visitor_list()
    {
        if (isset($_GET['property_id']) && $_GET['property_id']>0 && !$_GET['village_id']) {
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'villagelist',
                'isAll' => true,
                'urlParam' => 'property_id='.$_GET['property_id'].'&referer_type=visitor_list',
            ];
            $villagelistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            redirect($villagelistUrl);
        }
        if (!$this->user_session) {
            $this->error_tips('请先进行登录', U('Login/index'));
        }


        $database_house_village_visitor = D('House_village_visitor');

        $village_id = $_GET['village_id'] + 0;
        $this->get_village($village_id);
        $has_visitor = $this->getHasConfig($village_id, 'has_visitor');
        if ($has_visitor) {
            $where['village_id'] = $village_id;
            $where['owner_uid'] = $this->user_session['uid'];
            $where['status'] = 0;
            $list = $database_house_village_visitor->house_village_visitor_list($where);
            if (!$list) {
                $this->error('数据处理有误！');
            } else {
                $this->assign('list', $list['list']);
                $this->assign('visitor_type', $database_house_village_visitor->visitor_type);
            }
            $this->display();
        } else {
            $this->error_tips(C('config.house_name').'未开通相关服务！');
        }

    }


    // 访客开门
    public function visitor_list_open()
    {
        if (isset($_GET['property_id']) && $_GET['property_id']>0 && !$_GET['village_id']) {
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'villagelist',
                'isAll' => true,
                'urlParam' => 'property_id='.$_GET['property_id'].'&referer_type=visitor_list_open',
            ];
            $villagelistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            redirect($villagelistUrl);
        }
        // 优化部分
        $now_village_bind = $_SESSION['now_village_bind'];
        if ($now_village_bind['pigcms_id'] && $_GET['village_id']) {
            $base_url = D('House_village')->base_url;
            $referer_url = $base_url . 'pages/village/index/visitorPass?village_id='.$_GET['village_id'].'&pigcms_id='.$now_village_bind['pigcms_id'];
            $url = $this->config['site_url'].$referer_url;
            redirect($url);
        }
        if (empty($this->user_session)) {
            if (IS_POST) {
                $this->check_ajax_error_tips('请先进行登录', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
            } else {
                $this->error_tips('请先进行登录', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
            }
        }
        $uid = $this->user_session['uid'];
        $village_id = $_GET['village_id'] + 0;
        if (empty($village_id)) {
            $this->error_tips('未获取到您的社区信息，请重试', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
        }


        if (empty($this->user_session)) {
            if (IS_POST) {
                $this->check_ajax_error_tips('请先进行登录', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
            } else {
                $this->error_tips('请先进行登录', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
            }
        }
        $village_id = $_GET['village_id'] + 0;
        if (empty($village_id)) {
            $this->error_tips('未获取到您的社区信息，请登录', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
        }
        $village_info = M('House_village')->where(array('village_id' => $now_village_bind['village_id']))->field('owe_property_open_door, owe_property_open_door_day, temporary_visitor_switch,temporary_visitor_time')->find();
        $owe_property_days = 0;


        // 处理一下可选择的时间
        if ($this->config['house_village_temporary_visitor'] != 1) {
            $this->error_tips('平台未开启该功能！请联系管理员');
        } elseif ($village_info['temporary_visitor_switch'] != 1) {
            $this->error_tips('社区未开启该功能！请联系社区管理人员');
        }
        $temporary_visitor_time_limit = 168; // 默认168小时 即 7天
        if ($this->config['house_village_temporary_visitor_time'] > 0) {
            $temporary_visitor_time_limit = floatval($this->config['house_village_temporary_visitor_time']);
        }
        if ($village_info['temporary_visitor_time'] > 0) {
            if ($this->config['house_village_temporary_visitor_time'] > 0 && $village_info['temporary_visitor_time'] < $this->config['house_village_temporary_visitor_time']) {
                $temporary_visitor_time_limit = floatval($village_info['temporary_visitor_time']);
            }
        }
        $this->assign('time_limit', $temporary_visitor_time_limit);


        $info = array(
            'tip' => 0,
            'tip_info' => ''
        );
        if(isset($now_village_bind['open_door_day']) && $now_village_bind['open_door_day']>0){
            $village_info['owe_property_open_door_day']=$now_village_bind['open_door_day'];
        }
        if ($now_village_bind['type'] == 4) {
            // 考虑到安全问题，工作人员门禁权限不可分享
            $info['tip'] = 3;
            $info['tip_info'] = '工作人员不能分享门禁';
            // 类型为工作人员
//            $phone = $this->user_session['phone'];
//            $work_info = M('House_worker')->where((array('phone'=>$phone,'village_id' => $now_village_bind['village_id'])))->find();
//            if ($work_info['status'] == 4) {
//                $info['tip'] = 2;
//                $info['tip_info'] = '工作人员账号已被禁用，不可开门';
//            } elseif ($work_info['open_door'] == 0) {
//                $info['tip'] = 2;
//                $info['tip_info'] = '工作人员无开门权限';
//            }
        } else {
            if ($now_village_bind['type'] == 1 || $now_village_bind['type'] == 2) {
                // 家属和租客 物业费信息以业主为准
                $m_house_village_user_bind = M('House_village_user_bind');
                $info = $m_house_village_user_bind->where(array('pigcms_id' => $now_village_bind['parent_id']))->field('property_price, property_endtime')->find();
                $now_village_bind['property_price'] = $info['property_price'];
                $now_village_bind['property_endtime'] = $info['property_endtime'];
            }

            if ($now_village_bind['property_endtime'] > 0) {
                // 计算 当前时间到物业截止时间的天数
                $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $now_village_bind['property_endtime']))) / 86400;
            }
            if (!$village_info['owe_property_open_door'] && $now_village_bind['property_endtime'] > 0 && (time() > $now_village_bind['property_endtime'])) {
                // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                $info['tip'] = 1;
                $info['tip_info'] = '您已欠物业费不能使用访客开门';
            } else if ($village_info['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $village_info['owe_property_open_door_day']) {
                // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                $info['tip'] = 1;
                $info['tip_info'] = '您已欠物业费不能使用访客开门';
            }
        }

        $this->assign('info', $info);

        // 处理下分享图片
        if ($this->config['house_village_visitor_open']) {
            $visitor_open_img = $this->config['house_village_visitor_open'];
        } else {
            $visitor_open_img = $this->config['site_url'] . '/static/wxapp/visitor_open.png';
        }
        $this->assign('visitor_open_img', $visitor_open_img);


        // 获取所有该用户在小区的开门权限（工作人员除外）
        $m_house_village_door_user = M('House_village_door_user');
        $m_house_village_floor = M('House_village_floor');
        $m_house_village_door = M('House_village_door');
        $m_house_village_user_bind = M('House_village_user_bind');

        $where['a.uid'] = $uid;
        $where['a.status'] = 1;
        $where['b.status'] = 1;
        $aUserSelect = $m_house_village_user_bind->alias('a')
            ->join('LEFT JOIN pigcms_house_village as b on b.village_id=a.village_id')
            ->distinct(true)
            ->field(array('a.pigcms_id','a.parent_id','a.type','a.name','a.village_id', 'a.floor_id', 'a.property_price', 'a.property_endtime', 'a.phone', 'a.open_door_day', 'b.village_name', 'b.village_address', 'b.owe_property_open_door', 'b.owe_property_open_door_day'))
            ->where($where)
            ->order('a.type DESC')
            ->select();

        // 处理数据-层次为  小区-门禁-具体信息
        $open_list_info = array();
        // 查询出来的门禁信息存储
        $door_list_info = array();
        // 查询出来的单元信息存储
        $floor_list_info = array();
        // 查询出来的可以开门的信息存储
        $aDoor_is_open = array();
        // 存储信息
        $bind_info = array();

        $aDoor = array();
        if (!empty($aUserSelect)) {
            foreach ($aUserSelect as $k => $v) {
                if(isset($v['open_door_day']) && $v['open_door_day']>0){
                    $v['owe_property_open_door_day']=$v['open_door_day'];
                }
                if ($v['type'] == 1 || $v['type'] == 2) {
                    // 家属和租客 物业费信息以业主为准
                    if ($bind_info[$v['parent_id']]) {
                        $v['property_price'] = $bind_info[$v['parent_id']]['property_price'];
                        $v['property_endtime'] = $bind_info[$v['parent_id']]['property_endtime'];
                    } else {
                        $info = $m_house_village_user_bind->where(array('pigcms_id' => $v['parent_id']))->field('property_price, property_endtime')->find();
                        $v['property_price'] = $info['property_price'];
                        $v['property_endtime'] = $info['property_endtime'];
                    }
                }
                $bind_info[$v['pigcms_id']] = $v;
                if (!$open_list_info[$v['village_id']]) {
                    // 处理小区层信息
                    $open_list_info[$v['village_id']] = array(
                        'village_name' => $v['village_name'],
                        'village_address' => $v['village_address']
                    );
                    // 放置门禁信息数组
                    $open_list_info[$v['village_id']]['door_list'] = array();
                }
                // 避免重复查询门禁信息
                if ($door_list_info && $door_list_info[$v['village_id']]) {
                    $aDoorList = $door_list_info[$v['village_id']];
                } else {
                    $condition_door['door_status'] = 1;
                    $condition_door['village_id'] = $v['village_id'];
                    $aDoorList = $m_house_village_door->distinct(true)->field(true)->where($condition_door)->select();
                    $door_list_info[$v['village_id']] = $aDoorList;
                }
                // 默认存在单元门禁
                if (empty($aDoorList)) {
                    unset($open_list_info[$v['village_id']]);
                    continue;
                } else {
                    $open_list_info[$v['village_id']]['have_village_door'] = true;
                    $open_list_info[$v['village_id']]['tip_info'] = '';
                }

                if ($v['type'] == 4) {
                    // 工作人员信息 无法分享
                    continue;
                } else {
                    // 绑定的单元-即拥有该小区的大门和改单元门禁权限
                    $village_floor_id = $v['floor_id'];
                    // 开门到期时间
                    $property_endtime = 0;

                    // 允许开门情况下继续判断
                    if (!$v['property_endtime']) {
                        $ban_open_status = false;   // 是否禁止开门  false 可以开门  true 禁止开门
                    } else {
                        $ban_open_status = false; // 默认可以开门
                        $owe_property_days = 0;
                        if ( $v['property_endtime'] > 0) {
                            // 计算 当前时间到物业截止时间的天数
                            $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $v['property_endtime']))) / 86400;
                        }
                        if (!$v['owe_property_open_door'] && $v['property_endtime']>0 && (time() > $v['property_endtime'])) {
                            // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                            $ban_open_status = true;
                        } else if ($v['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $v['owe_property_open_door_day']) {
                            // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                            $ban_open_status = true;
                        }
                        // 处理下开门到期时间
                        if ($v['property_endtime'] > 0) {
                            $property_endtime = $v['property_endtime'];
                            if ($v['owe_property_open_door'] && $v['owe_property_open_door_day'] > 0) {
                                $property_endtime +=  $v['owe_property_open_door_day'] * 86400;
                            }
                        }
                    }
                    foreach ($aDoorList as $kk => &$vv) {
                        $userWhere = array(
                            'user_id' => $uid,
                            'door_fid' => $vv['door_id'],
                        );
                        if (!empty($aDoor_is_open) && !empty($aDoor_is_open[$vv['door_id']])) {

                            // 判断下哪一个有效期更长
                            if ($aDoor_is_open[$vv['door_id']]['door_end_time'] == 0 || ($property_endtime > 0 && $aDoor_is_open[$vv['door_id']]['door_end_time'] && $aDoor_is_open[$vv['door_id']]['door_end_time'] > $property_endtime)) {
                                // 如果已经存在的门禁权限更久 将跳过下面的步骤
                                continue;
                            }
                            // 门禁信息已经查询过，直接赋予新的状态
                            $vv = $aDoor_is_open[$vv['door_id']];
                        } elseif ($vv['floor_id'] != "-1") {
                            // 避免重复查询单元信息
                            if ($floor_list_info && $floor_list_info[$vv['floor_id']]) {
                                $aFloor = $floor_list_info[$vv['floor_id']];
                            } else {
                                $aFloor = $m_house_village_floor->field(array('floor_name', 'floor_layer'))->where(array('floor_id' => $vv['floor_id']))->find();
                                $floor_list_info[$vv['floor_id']] = $aFloor;
                            }
                            $vv['floor_name'] = strval($aFloor['floor_name']);
                            $vv['floor_layer'] = strval($aFloor['floor_layer']);
                            $vv['door_type'] = 1; // 小区单元门
                        } else {
                            $vv['floor_name'] = C('config.house_name');
                            $vv['floor_layer'] = '大门';
                            $vv['door_type'] = 2; // 小区大门
                        }
                        if ($v['type'] == 1 || $v['type'] == 2) {
                            $vv['door_user_type'] = 4; // 家属
                        } else {
                            $vv['door_user_type'] = 1; // 业主
                        }

                        $vv['door_end_time'] = $property_endtime>0?date('Y-m-d H:i', $property_endtime):0;
                        $aDoorFind = $m_house_village_door_user->field(true)->where($userWhere)->find();
                        if (!$ban_open_status && empty($aDoorFind) && $vv['floor_id'] == -1) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$vv['door_id']])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = '可以打开';
                                $aDoor[] = $vv;
                                $aDoor_is_open[$vv['door_id']] = $vv;
                            }
                            $aDoor_is_open[$vv['door_id']]['door_user_type'] = 1;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif (!$ban_open_status && empty($aDoorFind) && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$vv['door_id']])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = '可以打开';
                                $aDoor[] = $vv;
                                $aDoor_is_open[$vv['door_id']] = $vv;
                            }
                            $aDoor_is_open[$vv['door_id']]['door_user_type'] = 1;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif (!$ban_open_status && $aDoorFind['status'] == 1 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            if ($aDoorFind == 0 || time() > $aDoorFind['end_time']) {
                                if (empty($aDoor_is_open) || empty($aDoor_is_open[$vv['door_id']])) {
                                    $vv['open_status'] = 1;
                                    $vv['open_status_txt'] = '可以打开';
                                    $aDoor[] = $vv;
                                    $aDoor_is_open[$vv['door_id']] = $vv;
                                }
                                $aDoor_is_open[$vv['door_id']]['door_user_type'] = 1;
                                $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                            } else {
                                $vv['open_status']	=	2;	//时间过期
                                $vv['open_status_txt']	=	'已过期，请联系物业';
                            }
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif (!$ban_open_status && $aDoorFind['status'] == 0 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            $vv['open_status']	=	0;		//禁止使用
                            $vv['open_status_txt']	=	'您没有权限开门，请联系物业';		//禁止使用
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif ($ban_open_status && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	'您已欠物业费不能开门';
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif ($ban_open_status && $vv['floor_id'] == -1) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	'您已欠物业费不能开门';
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        }
                    }
                    if (empty($open_list_info[$v['village_id']]['door_list']) && $open_list_info[$v['village_id']]['have_village_door']) {
                        $open_list_info[$v['village_id']]['have_floor_door'] = false;
                        $open_list_info[$v['village_id']]['tip_info'] = '您入住的单元暂未添加门禁';
                    } else {
                        $open_list_info[$v['village_id']]['have_floor_door'] = true;
                    }
                }
            }
        }

        $village_floor_list = [
            'list' => $aDoor
        ];

        $share = new WechatShare($this->config,$_SESSION['openid']);
        $this->sign_data = $share->get_wx_config();
        $this->assign('sign_data',json_encode($this->sign_data));

        $this->assign('village_floor_list', $village_floor_list);
        $this->display();
    }

    // 访客开门密码
    public function visitor_list_open_time()
    {
        $this->display();
    }    

    // 人脸门禁
    public function visitor_open_door()
    {
        if (empty($this->user_session)) {
            if (IS_POST) {
                $this->check_ajax_error_tips('请先进行登录', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
            } else {
                $this->error_tips('请先进行登录', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
            }
        }
        $uid = $this->user_session['uid'];
        $village_id = $_GET['village_id'] + 0;
        if (empty($village_id)) {
            $this->error_tips('未获取到您的社区信息，请重试', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
        }

        // 优化部分
        $now_village_bind = $_SESSION['now_village_bind'];
        if (!$now_village_bind) {
            $this->error_tips('未获取到住户信息', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
        }

        if ($this->config['house_door_docking_open'] != 1) {
            $this->error_tips('平台未开启该功能！请联系管理员');
        }
       
        $this->display();
    }

    public function ajax_visitor_open_door_pwd()
    {
       
        $uid = $this->user_session['uid'];
        $village_id = $_GET['village_id'] + 0;

        // 生成临时密码
        $info = array(
            'tip' => 0,
            'tip_info' => ''
        );
        $now_village_bind = $_SESSION['now_village_bind'];
        if ($now_village_bind['type'] == 4) {
            // 考虑到安全问题，工作人员门禁权限不可分享
            $info['tip'] = 3;
            $info['tip_info'] = '工作人员不能分享门禁';
        } else {
            if ($now_village_bind['type'] == 1 || $now_village_bind['type'] == 2) {
                // 家属和租客 物业费信息以业主为准
                $m_house_village_user_bind = M('House_village_user_bind');
                $info = $m_house_village_user_bind->where(array('pigcms_id' => $now_village_bind['parent_id']))->field('property_price, property_endtime')->find();
                $now_village_bind['property_price'] = $info['property_price'];
                $now_village_bind['property_endtime'] = $info['property_endtime'];
            }
            $village_info = M('House_village')->where(array('village_id' => $now_village_bind['village_id']))->field('owe_property_open_door, owe_property_open_door_day, temporary_visitor_switch,temporary_visitor_time')->find();
            if(isset($now_village_bind['open_door_day']) && $now_village_bind['open_door_day']>0){
                $village_info['owe_property_open_door_day']=$now_village_bind['open_door_day'];
            }
            if ($now_village_bind['property_endtime'] > 0) {
                // 计算 当前时间到物业截止时间的天数
                $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $now_village_bind['property_endtime']))) / 86400;
            }
            if (!$village_info['owe_property_open_door'] && $now_village_bind['property_endtime'] > 0 && (time() > $now_village_bind['property_endtime'])) {
                // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                $info['tip'] = 1;
                $info['tip_info'] = '您已欠物业费不能使用访客开门';
            } else if ($village_info['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $village_info['owe_property_open_door_day']) {
                // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                $info['tip'] = 1;
                $info['tip_info'] = '您已欠物业费不能使用访客开门';
            }
        }

        if ($info['tip'] == 0) {
            // 可以获得开门密码
            $token_info = D('House_village_door_code')->get_token($now_village_bind['village_id']);
            if (!$token_info['error']) {

                $house = D('House_village_user_vacancy')->where(array('pigcms_id'=>$now_village_bind['vacancy_id']))->find();
                if ($house['houseId']) {
                    $url = 'http://nlife.uicp.net:21423/openapi/v1/doorkeeper/getTempNumDKKey';
                    $params = array();
                    $params['houseId'] = $house['houseId'];
                    $params['userName'] = $now_village_bind['name'];
                    $params['userPhone'] = $now_village_bind['phone'];
                    $params['authUserPhone'] = $house['phone'];
                    $params['remark'] = '';
                    // $info['data'] = array('passwd'=>rand(100000,999999));
                    // echo json_encode($info);die;
                    $result = D('House_village_door_code')->curl_post($url,$params,$token_info['access_token']);
                    if($result['code'] == 1){
                        $info['data'] = $result['data'];
                    }else{

                      
                        $info['tip'] = 1;
                        $info['tip_info'] = '获取失败，请重试';
                    }
                }else{

                    $info['tip'] = 1;
                    $info['tip_info'] = '获取失败，请重试';
                }
            }else{
                $info['tip'] = 1;
                $info['tip_info'] = '获取失败，请重试';
            }
        }
        echo json_encode($info);
        
    }

    // 社区添加门禁分享信息
    public function house_village_door_share_add()
    {
        if (empty($this->user_session)) {
            if (IS_POST) {
                $this->check_ajax_error_tips('请先进行登录', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
            } else {
                $this->error_tips('请先进行登录', U('Login/index', array('referer' => urlencode(U('House/village_my', array('village_id' => $_GET['village_id']))))));
            }
        }
        // 获取社区id
        $village_id = $_POST['village_id'] + 0;
        if (empty($village_id)) {
            $this->returnCode('30000001');
        }
        // 获取小区信息
        $village_info = M('House_village')->where(array('village_id' => $village_id))->field('temporary_visitor_time, village_name')->find();
        $village_name = $village_info['village_name'];
        $now_village_bind = $_SESSION['now_village_bind'];
        $address = $now_village_bind['address'];
        if ($address) {
            if(strpos($address,$village_name) !== false){
                $address_info = $address;
            } else {
                $address_info = $village_name . $address;
            }
        } else {
            $address_info = $village_name;
        }

        // 获取单元信息

        // 选中单元信息处理
        $data = array();
        $data['share_info'] = serialize($_POST['share_info']);
        $data['share_address'] = $address_info;
        $data['village_id'] = $village_id;
        $data['share_uid'] = $this->user_session['uid'];
        $data['open_uid'] = 0;
        $now_time = time();
        $data['share_time'] = $now_time;
        // 有时间类型的取时间类型，没有默认为0 即分钟 分享时间计时方式（0: 分钟，1:小时，2:天）
        $share_time_type = !empty($_POST['share_time_type']) ? intval($_POST['share_time_type']) : 0;
        $data['share_time_type'] = $share_time_type;
        $share_time_length = !empty($_POST['share_time_length']) ? $_POST['share_time_length'] : 0;
        if ($share_time_length <= 0) {
            $this->error_tips('分享时间不能为零');
        }
        $data['share_time_length'] = $share_time_length;
        // 计算截止时间 是否上传正确

        $temporary_visitor_time_limit = 168; // 默认168小时  即7天
        if ($this->config['house_village_temporary_visitor_time'] > 0) {
            $temporary_visitor_time_limit = floatval($this->config['house_village_temporary_visitor_time']);
        }
        if ($village_info['temporary_visitor_time'] > 0) {
            if ($this->config['house_village_temporary_visitor_time'] > 0 && $village_info['temporary_visitor_time'] < $this->config['house_village_temporary_visitor_time']) {
                $temporary_visitor_time_limit = floatval($village_info['temporary_visitor_time']);
            }
        }
        // 判断时间是否传值正确
//        if (empty($_POST['share_end_time'])) {
//            $this->error_tips('请上传分享有效时间');
//        } elseif ($temporary_visitor_time_limit == -1) {
//            $data['share_end_time'] = strtotime($_POST['share_end_time']);
//        } else {
//            $share_end_time = strtotime($_POST['share_end_time']);
//            $diff_time = intval($share_end_time) - intval($now_time);
//            if ($diff_time > ($temporary_visitor_time_limit*3600)) {
//                $share_end_time = date('Y-m-d H:i', intval($now_time)+(intval($temporary_visitor_time_limit)*3600));
//                $this->error_tips('分享时间不得超过'.$share_end_time);
//            } else {
//                $data['share_end_time'] = strtotime($_POST['share_end_time']);
//                $data['share_time_length'] = intval($diff_time)/3600;
//                $data['share_time_type'] = 1;
//            }
//        }
        if (!empty($share_time_type)) {
            if (0 == $share_time_type) {
                if ($temporary_visitor_time_limit > 0 && $share_time_length > $temporary_visitor_time_limit) {
                    $this->error_tips('分享时间不得超过'.($temporary_visitor_time_limit * 60).'分钟');
                }
                $data['share_end_time'] = strtotime("+" . $share_time_length . " minute");
            } elseif (1 == $share_time_type) {
                if ($temporary_visitor_time_limit > 0 && $share_time_length > $temporary_visitor_time_limit) {
                    $this->error_tips('分享时间不得超过'.$temporary_visitor_time_limit.'小时');
                }
                $data['share_end_time'] = strtotime("+" . $share_time_length . " hour");
            } elseif (2 == $share_time_type) {
                if ($temporary_visitor_time_limit > 0 && $share_time_length > $temporary_visitor_time_limit) {
                    $this->error_tips('分享时间不得超过'.($temporary_visitor_time_limit/24).'天');
                }
                $data['share_end_time'] = strtotime("+" . $share_time_length . " day");
            } else {
                if ($temporary_visitor_time_limit > 0 && $share_time_length > $temporary_visitor_time_limit) {
                    $this->error_tips('分享时间不得超过'.($temporary_visitor_time_limit * 60).'分钟');
                }
                $data['share_end_time'] = strtotime("+" . $share_time_length . " minute");
            }
        } else {
            if ($temporary_visitor_time_limit > 0 && $share_time_length > $temporary_visitor_time_limit) {
                $this->error_tips('分享时间不得超过'.($temporary_visitor_time_limit * 60).'分钟');
            }
            $data['share_end_time'] = strtotime("+" . $share_time_length . " minute");
        }
        $data['share_use'] = 0;
        $data['share_status'] = 0;
        $addInfo = M('House_village_door_share')->add($data);
        if (!$addInfo) {
            $this->error_tips('分享失败');
        } else {
            $return = array();
            $return['door_share_id'] = $addInfo;
            $return['share_address'] = $address_info;
            $return['share_url'] = C('config.site_url') . '/wap.php?g=Wap&c=Library&a=visitor_list_open_time&door_share_id=' . $addInfo;
            $return['error_code'] = 0;
            exit(json_encode($return));
        }
    }


    public function express_edit()
    {
        $database_house_village_express = D('House_village_express');
        if (IS_POST) {
            $id = $_POST['id'] + 0;
            $status = $_POST['status'] + 0;
            if (!$id || !$status) {
                $this->error('传递参数有误！');
            }

            $where['id'] = $order_where['express_id'] = $id;
            $data['status'] = $status;

            $database_house_village_express_order = D('House_village_express_order');
            $database_house_village_express_order->house_village_express_order_edit($order_where, $data);
            $data['delivery_time'] = time();
            $result = $database_house_village_express->house_village_express_edit($where, $data);
            if (!$result) {
                exit(json_encode(array('status' => 0, 'msg' => '数据处理有误！')));
            } else {
                if ($status == 1) {
                    $express_config = M('House_village_express_config')->where(array('village_id' => $_POST['village_id']))->find();
                    $order_info = M('House_village_express')->field('e.id,o.send_time,u.name,ex.name as express_name , e.express_no,v.village_name')->join('as e LEFT JOIN ' . C('DB_PREFIX') . 'house_village_express_order  o ON e.id = o.express_id left join ' . C('DB_PREFIX') . 'express ex ON e.express_type = ex.id left join ' . C('DB_PREFIX') . 'house_village v ON e.village_id = v.village_id left join  ' . C('DB_PREFIX') . 'house_village_user_bind u ON e.uid = u.uid')->where(array('e.village_id' => $_POST['village_id'], 'e.id' => $id))->find();
                    if ($this->config['village_sms'] && $order_info['send_time'] > 0) {
                        $sms_data = array('mer_id' => $express_config['village_id'], 'store_id' => 0, 'type' => 'village_express');
                        $sms_data['uid'] = 0;
                        $sms_data['mobile'] = $express_config['notice_phone'];
                        $sms_data['sendto'] = 'village';
                        $sms_data['content'] = '【提醒】您好，' . $order_info['village_name'] . $order_info['name'] . '业主，已确认收货，收货时间:' . date('Y-m-d H:i', $order_info['send_time']) . '，' . $order_info['express_name'] . '，单号：' . $order_info['express_no'] . '。[' . $this->config['site_name'] . ']';
                        Sms::sendSms($sms_data);
                    }
                }
                exit(json_encode(array('status' => $result['status'], 'msg' => $result['msg'])));
            }
        }
    }


    public function chk_visitor_info()
    {
        $id = $_POST['id'] + 0;
        $status = $_POST['status'] + 0;
        if (!$id) {
            $this->error('传递参数有误！');
        }
        $database_house_village_visitor = D('House_village_visitor');
        $where['id'] = $id;
        $data['status'] = $status;
        $result = $database_house_village_visitor->house_village_visitor_edit($where, $data);
        if (!$result) {
            exit(json_encode(array('status' => 0, 'msg' => '数据处理有误！')));
        } else {
            exit(json_encode(array('status' => $result['status'], 'msg' => $result['msg'])));
        }
    }


    private function getHasConfig($village_id, $field)
    {
        $database_house_village = D('House_village');
        $house_village_info = $database_house_village->get_one($village_id, $field);
        $config_info = $house_village_info[$field];
        return $config_info;
    }

    private function get_village($village_id)
    {
        $now_village = D('House_village')->get_one($village_id);
        if (empty($now_village)) {
            $this->error_tips('当前访问的'.C('config.house_name').'不存在或未开放');
        }
        $this->assign('now_village', $now_village);
        return $now_village;
    }


    public function express_send_list()
    {
        if (!$this->user_session) {
            $this->error_tips('请先进行登录', U('Login/index'));
        }

        $village_id = $_GET['village_id'] + 0;

        $this->get_village($village_id);
        $database_house_village_express_send = D('House_village_express_send');

        $express_tmp = M('Express')->where(array('status' => 1))->select();

        foreach ($express_tmp as $key => $value) {
            $express_list[$value['code']] = $value['name'];
        }
        $this->assign('express_list', $express_list);
        $has_express_send = $this->getHasConfig($village_id, 'has_express_send');
        if ($has_express_send) {
            $where['village_id'] = $village_id;
            $where['uid'] = $this->user_session['uid'];

            $send_list = M('House_village_express_send')->where($where)->select();
            // echo M('House_village_express_send')->getlastsql();
            $this->assign('send_list', $send_list);

            $this->display();
        } else {
            $this->error_tips(C('config.house_name').'未开通相关服务！');
        }

    }

    public function express_send_add()
    {
        if (IS_POST) {
            $userInfo = D('User')->where(array('uid' => $this->user_session['uid']))->field('now_money,nickname,phone')->find();
            if ($userInfo['now_money'] < $_POST['send_price']) {
                exit(json_encode(array('error' => 3, 'msg' => '您的余额不足请前去充值')));
            }
            $data = $_POST;
            $data['uid'] = $this->user_session['uid'];
            $data['add_time'] = time();
            $res = D("House_village_express_send")->data($data)->add();
            if ($res) {

                D('User')->where(array('uid' => $this->user_session['uid']))->setDec('now_money', $_POST['send_price']);
                
                $save_paid_order_record=array('source_from'=>1);
                $save_paid_order_record['house_type']='house_express_send';
                $save_paid_order_record['business_type']='house_express_send';
                $save_paid_order_record['business_name']='快递代发';
                $save_paid_order_record['uid']=$this->user_session['uid'];
                $save_paid_order_record['order_id']=$res ? $res:0;
                $save_paid_order_record['order_no']=$res ? $res:'';
                $save_paid_order_record['u_name']=$userInfo['nickname'] ? $userInfo['nickname']:'';
                $save_paid_order_record['u_phone']=$userInfo['phone'] ? $userInfo['phone']:'';
                $save_paid_order_record['table_name']='house_village_express_send';
                $save_paid_order_record['order_money']=$_POST['send_price'];
                $save_paid_order_record['is_own']=1;
                $save_paid_order_record['pay_type']='balance';
                $save_paid_order_record['balance_money']=$_POST['send_price'];
                $save_paid_order_record['pay_type_from']='system_balance';
                $save_paid_order_record['is_online']=1;
                $save_paid_order_record['pay_time']=time();
                $save_paid_order_record['village_id']=$_POST['village_id'];
                D('Paid_order_record')->data($save_paid_order_record)->add();
                
                D('User_money_list')->add_row($this->user_session['uid'], 2, $_POST['send_price'], "支付待寄快递服务费 " . $_POST['send_price'] . C('config.Currency_txt')."", true, 5, $res);

                $order_param['village_id'] = $_POST['village_id'];
                $order_param['desc'] ='快递代发费用';
                $order_param['balance_pay'] = $_POST['send_price'];
                $order_param['payment_money'] = 0;
                $order_param['is_own'] = 4;
                $order_param['order_type'] = 'express';
                $order_param['order_id'] = 0;
                fdump($order_param,'ss');
                $return  = D('SystemBill')->bill_method(4,$order_param);


                $_SESSION['express'] = '';
                exit(json_encode(array('error' => 1, 'msg' => '添加成功！')));
            } else {
                exit(json_encode(array('error' => 2, 'msg' => '添加失败，请重试！')));
            }
        } else {

            $goods_type = array('1' => '文件', '2' => '数码产品', '3' => '生活用品', '4' => '服饰', '5' => '食品', '6' => '其他');
            $this->assign('goods_type', $goods_type);

            $this->assign('send_adress', $_SESSION['express']['express_adress_send']);
            $this->assign('collect_adress', $_SESSION['express']['express_adress_collect']);
            $this->assign('send_info', $_SESSION['express']['express_send_info']);
            $village_id = $_GET['village_id'] + 0;
            $express_send_price = $this->getHasConfig($village_id, 'express_send_price');
            $this->assign('express_send_price', $express_send_price);
            $express_list = M('Express')->where(array('status' => 1))->select();
            $this->assign('express_list', $express_list);
            $this->display();
        }
    }

    public function express_send_adress()
    {
        if ($_GET['type'] == 1) {
            $this->assign('adress_info', $_SESSION['express']['express_adress_send']);
        } else {
            $this->assign('adress_info', $_SESSION['express']['express_adress_collect']);
        }
        $this->display();
    }

    public function send_adress_ajax()
    {
        if ($_POST['type'] == 1) {
            $_SESSION['express']['express_adress_send'] = $_POST;
        } elseif ($_POST['type'] == 2) {
            $_SESSION['express']['express_adress_collect'] = $_POST;
        } else {
            $_SESSION['express']['express_send_info'] = $_POST;
        }
        exit(json_encode(array('error' => 1, 'msg' => '保存成功')));
    }
}

?>