<?php

/**
 * 平台会员卡
 */
class MembershipCardAction extends BaseAction
{
    /**
     * 配置页面
     * @author: 张涛
     * @date: 2020/12/18
     */
    public function config()
    {
        $mod = D('Membership_card');
        $config = $mod->find();
        $adminUser = $this->system_session['account'];
        if (IS_POST) {
            if(!$_POST['buy_after_over']){
                if(!is_numeric($_POST['early_renewal'])||$_POST['early_renewal']<0){
                    $this->error('提前续费天数格式错误！');
                }
            }else{
                $_POST['early_renewal'] = 0;
            }
            if ($config) {
                $id = $config['id'];
                D('Membership_card')->where(['id' => $id])->save($_POST);
                (new Membership_CardModel())->wirte_log(1, $config, $_POST, $adminUser);
            } else {
                $id = D('Membership_card')->add($_POST);
                (new Membership_CardModel())->wirte_log(0, [], $_POST, $adminUser);
            }
            //判断优惠信息是否变更，如果变更，更新平台会员卡关联的平台优惠券ID
            if ($_POST['discount_type'] & 1) {
                $tm = time();
                //默认优惠券截止日期2038年
                $endTime = strtotime('2038-01-01 00:00:01');
                $isEmpty = $config ? false : true;
                $isChangeCouponConfig = ($_POST['card_name'] != $config['card_name']) || ($_POST['days'] != $config['days']) || ($_POST['coupon_price'] != $config['coupon_price']) || ($_POST['coupon_order_price'] != $config['coupon_order_price']) || ($_POST['coupon_cate'] != $config['coupon_cate']);
                if ($isEmpty || $isChangeCouponConfig) {
                    $data = [
                        'name' => $_POST['card_name'],
                        'platform' => serialize(array('wap', 'app', 'weixin','wxapp')),
                        'num' => 10000,
                        'limit' => 1,
                        'status' => 1,
                        'cate_name' => $_POST['coupon_cate'],
                        'add_time' => $tm,
                        'start_time' => $tm,
                        'is_partition' => 1,  //临时也用瓜分券字段，这样其他地方都不用判断了，如果后续有问题再调整
                        'end_time' => $endTime,
                        'get_start_time' => $tm,
                        'get_end_time' => $endTime,
                        'valid_time' => $_POST['days'],
                        'discount' => $_POST['coupon_price'],
                        'order_money' => $_POST['coupon_order_price']
                    ];
                    $couponId = (new System_couponModel())->add($data);
                    D('Membership_card')->where(['id' => $id])->save(['coupon_id' => $couponId]);
                }
            }
            $this->success('配置成功');
        } else {
            $this->assign('config', $config);
            $return = D('System_coupon')->cate_platform();
            $this->assign("category", $return['category']);
            $this->display();
        }
    }

    /**
     * 效果数据
     * @author: 张涛
     * @date: 2020/12/21
     */
    public function report()
    {
        $config = D('Membership_card')->find();
        $this->assign('config', $config);
        $this->display();
    }

    /**
     * 效果数据
     * @author: 张涛
     * @date: 2020/12/21
     */
    public function form()
    {
        $days = I('get.days', 0, 'intval');
        $begin_time = strtotime(date('Y-m-d', strtotime("-$days days")));
        $end_time = time();
        $where['create_time'] = [
            ['EGT', $begin_time],
            ['ELT', $end_time]
        ];
        $records = M('Membership_card_user_open_record')->where($where)->field("count(id) AS sale_num, SUM(price) AS sale_price, FROM_UNIXTIME(create_time, '%Y-%m-%d') AS datetime")->group('datetime')->order('datetime DESC')->select();
        if ($records) {
            $date_index_records = array_combine(array_column($records, 'datetime'), $records);
        } else {
            $date_index_records = [];
        }
        $date_lists = [];
        while ($end_time >= $begin_time) {
            $date = date('Y-m-d', $end_time);
            if (isset($date_index_records[$date])) {
                $date_lists[] = $date_index_records[$date];
            } else {
                $date_lists[] = [
                    'sale_num' => 0,
                    'sale_price' => 0,
                    'datetime' => $date
                ];
            }
            $end_time -= 86400;
        }
        $this->assign('date_lists', $date_lists);
        $this->display();
    }

    /**
     * 销售统计
     * @author: 张涛
     * @date: 2021/02/19
     */
    public function saleData()
    {
        $mod = M('Membership_card_user_open_record');
        $days = I('get.days', 0, 'intval');
        $begin_time = strtotime(date('Y-m-d', strtotime("-$days days")));
        $end_time = time();
        $where['create_time'] = [
            ['EGT', $begin_time],
            ['ELT', $end_time]
        ];
        //总订单数
        $totalOrderCount = $mod->count();
        $orderCount = $mod->where($where)->count();

        //实付金额
        $totalOrderPrice = $mod->sum('price');
        $orderPrice = $mod->where($where)->sum('price');

        //购卡人数
        $totalUserCount = $mod->count('DISTINCT uid');
        $userCount = $mod->where($where)->count('DISTINCT uid');

        $rs = [
            'currency_symbol' => C('config.Currency_symbol'),
            'order_count' => $orderCount,
            'total_order_count' => $totalOrderCount,
            'order_price' => getFormatNumber($orderPrice),
            'total_order_price' => getFormatNumber($totalOrderPrice),
            'user_count' => $userCount,
            'total_user_count' => $totalUserCount,
            'per_price' => $orderCount > 0 ? getFormatNumber($orderPrice / $orderCount) : 0,
            'total_per_price' => $totalOrderCount > 0 ? getFormatNumber($totalOrderPrice / $totalOrderCount) : 0
        ];
        $this->ajaxReturn($rs);
    }

    /**
     * 平台会员卡变动日志
     * @author: 张涛
     * @date: 2020/12/21
     */
    public function logs()
    {
        $logs = M('Membership_card_log')->order('id DESC')->select();
        $parse_logs = [];
        foreach ($logs as $l) {
            $data = [
                'time' => date('Y-m-d H:i:s', $l['log_time']),
                'admin_user' => $l['admin_user']
            ];
            if ($l['action'] == 0) {
                $data['action'] = L_('创建活动');
                $data['remark'] = L_('创建了平台会员卡');
            } else if ($l['action'] == 1) {
                $data['action'] = L_('修改活动规则');
                $data['remark'] = L_('编辑了平台会员卡');
            }
            $parse_logs[] = $data;
        }
        $this->assign('parse_logs', $parse_logs);
        $this->display();
    }

    public function orders($isExport = false)
    {
        $where = [];
        $keyword = I('get.keyword', '', 'trim');
        $searchtype = I('get.searchtype', '', 'trim');
        $beginTime = I('get.begin_time', '', 'trim');
        $endTime = I('get.end_time', '', 'trim');
        $_GET['paid'] = $paid = I('get.paid', '-1');
        $payType = I('get.pay_type', '');
        if ($keyword) {
            switch ($searchtype) {
                case 'phone':
                    $where['u.phone'] = $keyword;
                    break;
                case 'nickname':
                    $where['u.nickname'] = ['LIKE', '%' . $keyword . '%'];
                    break;
            }
        }

        if ($beginTime) {
            $where['o.create_time'][] = ['gt', strtotime($beginTime)];
        }
        if ($endTime) {
            $where['o.create_time'][] = ['lt', strtotime($endTime) + 86400];
        }
        if ($paid != -1) {
            $where['o.paid'] = $paid;
        }
        if ($payType) {
            if ($payType == 'balance') {
                $where['o.pay_type'] = '';
            } else {
                $where['o.pay_type'] = $payType;
            }
        }

        //直接购买的
        $subSql1 = D('Membership_card_order')->alias('o')
            ->join(C('DB_PREFIX') . 'user u ON o.uid = u.uid')
            ->field('o.order_sn AS order_id,u.nickname,u.phone,o.order_name,o.price,o.create_time,o.pay_time,o.expire_time,pay_type,"个人中心" AS source')
            ->where($where)
            ->buildSql();

        //通过外卖购买的
        $subSql2 = D('Shop_order')->alias('o')
            ->join(C('DB_PREFIX') . 'user u ON o.uid = u.uid')
            ->join(C('DB_PREFIX') . 'membership_card_user_open_record r ON r.business_order_id = o.order_id AND r.business_type = "shop"')
            ->field('o.real_orderid AS order_id,u.nickname,u.phone,o.membership_card_name AS order_name,o.membership_card_price AS price,o.create_time,o.pay_time,r.expire_time,pay_type,"外卖" AS source')
            ->where(array_merge($where, array('is_open_membership_card' => 1)))
            ->buildSql();

        if ($isExport) {
            $lists = D()->query("SELECT *  FROM ($subSql1 UNION ALL $subSql2) tmp ORDER BY create_time DESC");
            foreach ($lists as &$l) {
                $l['create_date'] = date('Y-m-d H:i:s', $l['create_time']);
                $l['pay_date'] = $l['pay_time'] > 0 ? date('Y-m-d H:i:s', $l['pay_time']) : "--";
                $l['expire_date'] = $l['expire_time'] > 0 ? date('Y-m-d H:i:s', $l['expire_time']) : "--";
            }
            return $lists;
        }

        $count = D()->query("SELECT count(1) as c FROM ($subSql1 UNION ALL $subSql2) tmp");
        $count = $count[0]['c'] ?? 0;

        $totalPrice = D()->query("SELECT sum(`price`) as totalPrice FROM ($subSql1 UNION ALL $subSql2) tmp");
        $totalPrice = $totalPrice[0]['totalPrice'] ?? 0;
        $this->assign('total_price', $totalPrice);
        $this->assign('count', $count);

        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $lists = D()->query("SELECT *  FROM ($subSql1 UNION ALL $subSql2) tmp ORDER BY create_time DESC limit " . $p->firstRow . ',' . $p->listRows);

        $paidLists = ['-1' => L_('全部'), '1' => L_('已支付'), '0' => L_('未支付')];
        $payMethods = ['weixin' => '微信支付', 'alipay' => '支付宝', 'balance' => '余额支付'];
        $this->assign('paidLists', $paidLists);
        $this->assign('payMethods', $payMethods);
        $this->assign('pagebar', $p->show());
        $this->assign('page', ceil($count / 20));
        $this->assign('lists', $lists);
        $this->display();
    }

    public function exportOrders()
    {
        $orders = $this->orders(true);
        $config = [
            'header' => [
                ['title' => L_('订单编号'), 'value_key' => 'order_id'],
                ['title' => L_('下单人'), 'value_key' => 'nickname'],
                ['title' => L_('手机号'), 'value_key' => 'phone'],
                ['title' => L_('会员卡名称'), 'value_key' => 'order_name'],
                ['title' => L_('价格'), 'value_key' => 'price'],
                ['title' => L_('下单时间'), 'value_key' => 'create_date'],
                ['title' => L_('支付时间'), 'value_key' => 'pay_date'],
                ['title' => L_('过期时间'), 'value_key' => 'expire_date'],
                ['title' => L_('下单来源'), 'value_key' => 'source'],
            ],
            'body' => $orders,
            'file_name' => time(),
            'sheet_title' => "会员卡导出记录"
        ];
        R('System/Shop/download_store_list', ['config' => $config]);
    }
}