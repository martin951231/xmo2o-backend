<?php

/**
 * 商家营销中心
 */
class MarketingAction extends BaseAction
{
    /**
     * 菜单
     *
     * @author 张涛
     * @date 2020/04/07
     */
    public function index()
    {
        $menu = [
            [
                'title' => L_('限时优惠'),
                'name' => 'time_limited_discount',
                'describe' => L_('花式提高客单价'),
                'pic' => $this->config['site_url'] . '/plugin/villagegroup/market/yx_icon1.png'
            ],
            [
                'title' => L_('店铺优惠'),
                'name' => 'shop_discount',
                'describe' => L_('有效活跃整场活动气氛，吸引顾客下单购买'),
                'pic' => $this->config['site_url'] . '/plugin/villagegroup/market/yx_icon2.png'
            ],
            [
                'title' => L_('满赠活动'),
                'name' => 'full_gift_activity',
                'describe' => L_('吸引顾客提高成交几率'),
                'pic' => $this->config['site_url'] . '/plugin/villagegroup/market/yx_icon3.png'
            ],
        ];
        $this->returnCode(0, $menu);
    }

    /**
     * 限时优惠列表
     *
     * @author 张涛
     * @date 2020/04/07
     */
    public function getTimeLimitedDiscount()
    {
        //1：待生效  2：进行中   3：已结束  4:已撤销
        $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 0;
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $_GET['page'] = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $search = [
            'keyword' => $keyword,
            'status' => $status,
            'store_id' => $storeId
        ];
        $discountMod = D('Time_limited_discount_goods');
        $lists = $discountMod->get_list($search);
        $rs = [];
        $today = date('Y-m-d');
        foreach ($lists['lists'] as $v) {
            $rs[] = [
                'id' => $v['id'],
                'goods_name' => $v['goods_name'],
                'goods_image' => $v['image'],
                'start_date' => $v['start_date'],
                'end_date' => $v['end_date'],
                'start_time' => $v['start_time'],
                'end_time' => $v['end_time'],
                'week_str' => $v['week_str'],
                'is_spec' => $v['is_spec'],
                'limit_price' => getFormatNumber($v['limit_price']),
                'old_price' => getFormatNumber($v['price']),
                'today_sale' => $v['today_sale'],
                'spec_list' => $v['spec_list'] ?: [],
                'reason_for_not_sale' => $v['reason_for_not_sale'] ?? '',
                'btns' => [
                    'lose' => $v['status'] != 3 && $v['is_del'] != 1,
                    'edit' => $v['status'] != 3 && $v['is_del'] != 1,
                    'detail' => true
                ]
            ];
        }
        $statics = $discountMod->stats($storeId);
        $this->returnCode(0, ['count' => $statics, 'lists' => $rs]);
    }

    /**
     * 撤销
     *
     * @author 张涛
     * @date 2020/04/07
     */
    public function setLose()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $detail = D('Time_limited_discount_goods')->where(['id' => $id, 'is_del' => 0])->find();
        if (!$detail) {
            $this->returnCodeError(L_('活动不存在或已撤销'));
        }
        if (D('Time_limited_discount_goods')->where(['id' => $id])->setField('is_del', 1)) {
            D('Time_limited_discount_goods')->wirte_action_log(2, [], [], $id);
            $this->returnCodeOk([]);
        } else {
            $this->returnCodeError(L_('撤销失败'));
        }
    }

    /**
     * 操作记录
     *
     * @author 张涛
     * @date 2020/04/07
     */
    public function logs()
    {
        $limit_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $logs = M('Time_limited_discount_log')->where(['limit_id' => $limit_id])->order('id DESC')->select();
        $parse_logs = [];
        foreach ($logs as $l) {
            $data = ['time' => date('Y-m-d H:i:s', $l['log_time'])];
            if ($l['action'] == 0) {
                $data['action'] = L_('创建活动');
                $data['remark'] = L_('创建了限时优惠');
            } else if ($l['action'] == 1) {
                $data['action'] = L_('修改活动规则');
                $data['remark'] = L_('编辑了限时优惠');
            } else if ($l['action'] == 2) {
                $data['action'] = L_('修改活动规则');
                $data['remark'] = L_('撤销了限时优惠');
            }
            $parse_logs[] = $data;
        }
        $this->returnCode(0, $parse_logs);
    }

    /**
     * 限时优惠选择商品，非附属菜 + 非称重商品
     *
     * @author 张涛
     * @date 2020/04/09
     */
    public function goodsList()
    {
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) :0;
        $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
        $_GET['page'] = $_POST['page'] > 0 ? intval($_POST['page']) : 1;
        $condition = [
            'keyword' => $keyword,
            'store_id' => $store_id
        ];
        $data = D('Time_limited_discount_goods')->select_goods($condition);
        $lists = $data['goods'] ?: [];
        foreach ($lists as $k => $v) {
            unset($lists[$k]['spec_json_str']);
            unset($lists[$k]['spec_value']);
        }
        $this->returnCode(0, ['total_page' => $data['total_page'], 'page' => $_GET['page'], 'lists' => $lists]);
    }


    /**
     * 创建限时优惠
     *
     * @author 张涛
     * @date 2020/04/07
     */
    public function addTimeLimitedDiscount()
    {
        try {
            D('Time_limited_discount_goods')->add_record($_POST);
            $this->returnCode();
        } catch (Exception $e) {
            $this->returnCodeError($e->getMessage());
        }
    }

    /**
     * 编辑限时优惠
     *
     * @author 张涛
     * @date 2020/04/07
     */
    public function editTimeLimitedDiscount()
    {
        try {
            D('Time_limited_discount_goods')->edit_record($_POST);
            $this->returnCode();
        } catch (Exception $e) {
            $this->returnCodeError($e->getMessage());
        }
    }

    /**
     * 限时优惠详情
     *
     * @author 张涛
     * @date 2020/04/07
     */
    public function timeLimitedDiscountDetail()
    {
        $limit_id = isset($_POST['limit_id']) ? intval($_POST['limit_id']) : 0;
        $limit_spec_id = isset($_POST['limit_spec_id']) ? intval($_POST['limit_spec_id']) : 0;
        $days = isset($_POST['days']) ? intval($_POST['days']) : 1;
        $report = D('Time_limited_discount_goods')->get_sale_statistics($limit_id, $limit_spec_id, $days);
        $detail = D('Time_limited_discount_goods')->get_detail($limit_id, $limit_spec_id);
        $detail['create_time'] = date('Y-m-d H:i:s', $detail['create_time']);
        $rs = [
            'report' => $report,
            'detail' => $detail
        ];
        $this->returnCode(0, $rs);
    }

    /**
     * 销售统计
     * @author 张涛
     * @date 2020/04/14
     */
    public function getSaleRecord()
    {
        $limit_id = isset($_POST['limit_id']) ? intval($_POST['limit_id']) : 0;
        $limit_spec_id = isset($_POST['limit_spec_id']) ? intval($_POST['limit_spec_id']) : 0;
        $days = isset($_POST['days']) ? intval($_POST['days']) : 1;
        $mod = M('Time_limited_discount_sale_record');
        $where = [];
        $limit_id && $where['limit_id'] = $limit_id;
        $limit_spec_id && $where['limit_spec_id'] = $limit_spec_id;
        $begin_time = strtotime(date('Y-m-d', strtotime("-$days days")));
        $end_time = time();
        $where['create_time'] = [
            ['EGT', $begin_time],
            ['ELT', $end_time]
        ];
        $records = $mod->where($where)->field("SUM(num) AS sale_num, SUM(price) AS sale_price, FROM_UNIXTIME(create_time, '%Y-%m-%d') AS datetime")->group('datetime')->order('datetime DESC')->select();
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
        $this->returnCode(0, $date_lists);
    }

    /**
     * 查看详情
     * @author 张涛
     * @date 2020/04/14
     */
    public function detail()
    {
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 429;
        $limit_id = isset($_POST['limit_id']) ? intval($_POST['limit_id']) : 47;
        $where = [
            'store_id' => $store_id,
            'id' => $limit_id
        ];
        $lists = D('Time_limited_discount_goods')->get_list($where);
        $info = isset($lists['lists'][0]) ? $lists['lists'][0] : [];
        $this->returnCode(0, $info);
    }
}