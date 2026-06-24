<?php

namespace app\social_activity\model\service;

use app\common\model\db\User;
use app\common\model\db\UserScoreList;
use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseVenueActivity;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\service\HouseVillageService;
use app\pay\model\service\PayService;
use app\social_activity\model\db\SocialActivity;
use app\social_activity\model\db\SocialActivityCategory;
use app\social_activity\model\db\SocialActivityOrder;
use app\social_activity\model\db\SocialActivityPointRule;
use app\social_activity\model\db\SocialActivitySession;
use app\social_activity\model\db\SocialActivitySessionTicket;
use app\social_activity\model\db\SocialActivityWithdraw;
use app\survey\service\FormService;
use app\survey\service\SubmitService;
use app\survey\validate\SubmitValidate;
use think\facade\Db;

class SocialActivityOrderService
{
    /**
     * 获取订单列表
     * @param array $params
     * @return array
     */
    public function getOrderList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 10;
        $isExport = $params['is_export'] ?? 0;

        $where = [];
        if (!empty($params['cat_id']) && $params['cat_id'] != 0) {
            $where[] = ['o.cat_id', '=', $params['cat_id']];
        }
        if (!empty($params['keywords'])) {
            $where[] = ['o.activity_name', 'like', "%{$params['keywords']}%"];
        }
        if (!empty($params['session_name'])) {
            $where[] = ['o.session_name', 'like', "%{$params['session_name']}%"];
        }
        if (!empty($params['nickname'])) {
            $where[] = ['u.nickname', 'like', "%{$params['nickname']}%"];
        }
        if (!empty($params['start_time'])) {
            $where[] = ['o.pay_time', '>=', $params['start_time']];
        }
        if (!empty($params['end_time'])) {
            $where[] = ['o.pay_time', '<=', $params['end_time']];
        }
        if(!empty($params['order_sn'])){
            $where[] = ['o.order_sn', '=', $params['order_sn']];
        }
        if(isset($params['order_status']) && $params['order_status'] >= 0){
            $where[] = ['o.status', '=', $params['order_status']];
        }

        $field = 'o.*, u.nickname, c.name as now_category_name';

        $query = (new SocialActivityOrder())->alias('o')
            ->field($field)
            ->join('user u', 'o.uid = u.uid')
            ->join('social_activity_category c', 'o.activity_id = c.id', 'left')
            ->where($where)
            ->append(['status_text'])
            ->order('o.create_time desc');
        if ($isExport == 1) {
            $data = $query->select()->toArray();
        } else {
            $list = $query->paginate($pageSize)->toArray();
            $data = $list['data'] ?? [];
        }
        foreach ($data as &$item) {
            $item['category_name'] = $item['now_category_name'] ?: $item['category_name'];
            if (strtotime($item['ticket_start_time']) > time()) {
                $item['activity_status_text'] = '待开始';
            } elseif (strtotime($item['ticket_end_time']) <= time()) {
                $item['activity_status_text'] = '已结束';
            } else {
                $item['activity_status_text'] = '进行中';
            }
            if($item['pay_time'] == '0000-00-00 00:00:00'){
                $item['pay_time'] = '';
            }
        }
        if ($isExport == 1) {
            $title = '活动订单导出列表';
            $xlsCell = [
                ['order_no', '订单编号'],
                ['category_name', '活动分类'],
                ['activity_name', '活动名称'],
                ['session_name', '活动场次'],
                ['nickname', '用户昵称'],
                ['ticket_name', '票种'],
                ['amount', '金额'],
                ['pay_time', '支付时间'],
                ['activity_status_text', '状态'],
                ['activity_status_text', '订单状态'],
            ];
            $fileName = '活动订单列表_' . date('YmdHis');
            $exportResult = export_excel($title, $xlsCell, $data, $fileName);
            return ['file_url' => $exportResult['url']];
        } else {
            $list['data'] = $data;
        }
        $category = (new SocialActivityCategory())
            ->field('id,name')
            ->where(['status' => 1])->order('sort desc,id asc')->select()->toArray();
        $list['category'] = $category ?: [];
        //订单状态
        $status_text = SocialActivityOrder::STATUS_MAP;
        $list['order_status_arr'] = [];
        //活动状态
        foreach ($status_text as $key=>$val){
            $list['order_status_arr'][] = [
                'id' => $key,
                'name' => $val,
            ];
        }
        // 计算可提现金额
        $withdrawableAmount = $this->getWithdrawableAmount();
        $list['withdrawable_amount'] = $withdrawableAmount;
        return $list;
    }

    /**
     * 获取可提现金额
     * @return array
     */
    public function getWithdrawableAmount()
    {
        $amount = (new SocialActivityOrder())
            ->where([
                ['paid', '=', 1],
                ['status', '=', 10],
                ['ticket_end_time', '<', date('Y-m-d H:i:s')],
            ])
            ->sum('amount');

        $withdrawnAmount = (new SocialActivityWithdraw())
            ->where([
                ['audit_status', 'in', [0, 10]],
            ])
            ->sum('amount');

        $withdrawableAmount = max(0, ($amount ?? 0) - ($withdrawnAmount ?? 0));

        return $withdrawableAmount;
    }

    /**
     * 获取提现记录列表
     * @param array $params
     * @return array
     */
    public function getWithdrawRecordList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 10;

        $where = [];
        if (isset($params['status']) && $params['status'] != -1) {
            $where[] = ['status', '=', $params['status']];
        }
        if (!empty($params['start_time'])) {
            $where[] = ['create_time', '>=', $params['start_time']];
        }
        if (!empty($params['end_time'])) {
            $where[] = ['create_time', '<=', $params['end_time']];
        }

        $total = Db::name('social_activity_withdraw_record')
            ->where($where)
            ->count();

        $list = Db::name('social_activity_withdraw_record')
            ->where($where)
            ->order('create_time DESC')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        return ['code' => 0, 'msg' => 'success', 'data' => ['list' => $list, 'total' => $total]];
    }

    /**
     * 下单页数据
     * @param $params
     */
    public function saveOrder($params)
    {
        $is_save = $params['is_save'] ?? 0;
        $uid = $params['uid'] ?? 0;
        $form_question = $params['form_question'] ?? [];
        $spend_time = $params['spend_time'] ?? 0;
        try {
            $res = $this->checkOrder($params);
        }catch (\Exception $e){
            throw new \think\Exception($e->getMessage(),$is_save ? 1003 : 1008);
        }
        $activity = $res['activity'];
        $session = $res['session'];
        $ticket = $res['ticket'];
        $buy_ticket_count = $res['buy_ticket_count'];
        $buy_num = $res['buy_num'];
        $user_info = $res['user_info'];
        if ($is_save != 1) {
            //查询场馆信息
            if ($session['location_id']) {
                $location = (new HouseVenueActivity())->where(['status' => 1, 'is_del' => 0, 'id' => $session['location_id']])->value('title');
            } else {
                $location = $session['location'];
            }
            //获取表单信息
            $form_question = null;
            if ($activity['form_id']) {
                $form_question = (new FormService())->getFormDetail($activity['form_id']);
            }
            return [
                'activity_id' => $activity['id'],
                'session_id' => $session['id'],
                'ticket_id' => $ticket['id'],
                'village_id' => $params['village_id'] ?? 0,
                'poster' => $activity['poster'],
                'price' => $ticket['price'],
                'total_price' => $ticket['price'] * $buy_num,
                'num' => $ticket['num'] - $buy_ticket_count,
                'name' => $activity['name'],
                'session_name' => $session['name'],
                'ticket_name' => $ticket['name'],
                'date_text' => date('Y-m-d H:i', strtotime($session['start_time'])) . ' ~ ' . date('Y-m-d H:i', strtotime($session['end_time'])),
                'location' => $location,
                'notice' => $activity['notice'],
                'buy_num' => $buy_num,
                'form_question' => $form_question,
                'user_info' => $user_info,
            ];
        } else {
            $category = (new SocialActivityCategory())->where(['id' => $activity['category_id']])->findOrEmpty()->toArray();
            if (!$category) {
                throw new \Exception('活动分类不存在');
            }
            //验证表单
            $submitId = 0;
            if ($activity['form_id'] && !$form_question) {
                throw new \Exception('请填写活动报名表单');
            }
            if ($form_question) {
                validate(SubmitValidate::class)->scene('add')->check($form_question);
                $submitId = (new SubmitService())->submitForm($uid, $activity['form_id'], $form_question['answers'], ['spend_time' => $spend_time]);
            }
            //保存订单
            Db::startTrans();
            try {
                $order_id = (new SocialActivityOrder())->insertGetId([
                    'category_id' => $category['id'],
                    'category_name' => $category['name'],
                    'order_sn' => (new PayService())->createOrderNo(),
                    'uid' => $uid,
                    'activity_id' => $activity['id'],
                    'activity_name' => $activity['name'],
                    'session_id' => $session['id'],
                    'session_name' => $session['name'],
                    'ticket_id' => $ticket['id'],
                    'ticket_name' => $ticket['name'],
                    'buy_village_id' => $params['village_id'] ?? 0,
                    'num' => $buy_num,
                    'price' => $ticket['price'],
                    'amount' => $ticket['price'] * $buy_num,
                    'ticket_start_time' => $session['start_time'],
                    'ticket_end_time' => $session['end_time'],
                    'rule_id' => $activity['rule_id'],
                    'survey_submit_id' => is_array($submitId) ? $submitId['submit_id'] : $submitId,
                    'source' => request()->agent,
                    'paid' => 0,
                    'status' => SocialActivityOrder::STATUS_0,
                    'is_sign' => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                    'user_name' => $user_info['user_name'],
                    'id_number' => $user_info['id_number'],
                    'user_source' => $user_info['user_source'],
                ]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw new \Exception($e->getMessage());
            }
            return ['order_id' => $order_id, 'order_type' => 'social_activity'];
        }
    }

    public function checkOrder($params)
    {
        $activity_id = $params['activity_id'] ?? 0;
        $session_id = $params['session_id'] ?? 0;
        $uid = $params['uid'] ?? 0;
        $ticket_id = $params['ticket_id'] ?? 0;
        $village_id = $params['village_id'] ?? 0; //小区id
        $buy_num = $params['buy_num'] ?? 1;
        $user_info = $params['user_info'] ?? [];
        $is_save = $params['is_save'] ?? 0;
        $pigcms_id = $params['pigcms_id'] ?? 0; //用户绑定小区关联表id
        if (!$village_id) {
            throw new \Exception('小区参数错误');
        }
        $house_village = (new HouseVillage())->where([['status', '=', 1], ['village_id', '=', $village_id]])->findOrEmpty()->toArray();
        if(!$house_village){
            throw new \Exception('小区不存在');
        }
        $user = (new User())->where(['uid' => $uid])->findOrEmpty()->toArray();
        if (!$user) {
            throw new \Exception('用户不存在');
        }
        //查询活动是否存在
        $activity = (new SocialActivity())->where(['id' => $activity_id, 'status' => 10])->findOrEmpty()->toArray();

        if (!$activity) {
            throw new \Exception('活动不存在');
        }
        if (strtotime($activity['end_time'] . ' 23:59:59') < time()) {
            throw new \Exception('活动已结束');
        }
        //查询场次是否存在
        $session = (new SocialActivitySession())->where(['id' => $session_id, 'activity_id' => $activity_id])->findOrEmpty()->toArray();
        if (!$session) {
            throw new \Exception('场次不存在/已被删除');
        }
        if (strtotime($session['ticket_start_time']) > time()) {
            throw new \Exception('购票时间未开始');
        }
        if (strtotime($session['ticket_end_time']) < time()) {
            throw new \Exception('购票时间已结束');
        }
        $village_ids = $session['village_id'] ? explode(',', $session['village_id']) : [];
        if (!in_array($village_id, $village_ids)) {
            throw new \Exception('当前小区不在活动范围');
        }
        $user_info['phone'] = $user['phone'];
        $user_info['nickname'] = $user['nickname'];
        if($is_save){
            if(empty($user_info['user_name'])){
                throw new \Exception('报名人姓名必填');
            }
            if(empty($user_info['id_number'])){
                throw new \Exception('报名人身份证号必填');
            }
            $id_number = trim($user_info['id_number']);
            if (!preg_match('/^\d{17}[\dXx]$/', $id_number)) {
                throw new \Exception('报名人身份证号格式不正确');
            }
            if(empty($user_info['user_source'])){
                throw new \Exception('报名人来源必填');
            }
            $id_sex = ((int)substr($id_number, 16, 1) % 2) ? 1 : 2;
            if ($session['gender'] != 1) {
                if ($session['gender'] == 2 && $id_sex != 1) {
                    throw new \Exception('仅限男生');
                }
                if ($session['gender'] == 3 && $id_sex != 2) {
                    throw new \Exception('仅限女生');
                }
            }
        }else{
            $user_info['user_name'] = '';
            $user_info['id_number'] = '';
            $user_info['user_source'] = '非业主';
            //查询是否为小区业主
            $village_house_where = [
                'uid' => $uid,
                'village_id' => $village_id,
                'status' => 1,
            ];
            if($pigcms_id){
                $village_house_where['pigcms_id'] = $pigcms_id;
            }
            $user_bind = (new HouseVillageUserBind())->where($village_house_where)->findOrEmpty()->toArray();
            if($user_bind){
                $user_info['user_name'] = $user_bind['name'] ?: '';
                $user_info['id_number'] = $user_bind['id_card'] ?: '';
                $address = (new HouseVillageService())->getSingleFloorRoom($user_bind['single_id'],$user_bind['floor_id'],$user_bind['layer_id'],$user_bind['vacancy_id'],$village_id);
                $user_info['user_source'] = $house_village['village_name'] . ' ' . (!empty($address) ? $address : $user_bind['address']);
            }
        }
        

        //查询场次关联的票种是否存在
        $ticket = (new SocialActivitySessionTicket())->where(['id' => $ticket_id, 'session_id' => $session_id, 'activity_id' => $activity_id])->findOrEmpty()->toArray();
        if (!$ticket) {
            throw new \Exception('票种不存在/已被删除');
        }
        if($buy_num < 1){
            throw new \Exception('购买数量不能少于1个');
        }
        if ($buy_num > 1) {
            throw new \Exception('一个场次一个票种只能购买一张');
        }
        $user_order = (new SocialActivityOrder())->where([
            'uid' => $uid,
            'activity_id' => $activity_id,
            'session_id' => $session_id,
            'ticket_id' => $ticket_id,
        ])->where('status', 'in', [SocialActivityOrder::STATUS_0, SocialActivityOrder::STATUS_10])
            ->order('id desc')
            ->findOrEmpty()
            ->toArray();
        if ($user_order) {
            if ($user_order['paid'] == 1) {
                throw new \Exception('您已购买过该场次');
            } elseif ($user_order['paid'] == 0) {
                throw new \Exception('您有该场次订单待支付，请先完成支付');
            }
        }
        //查询场次关联日期是否存在
        if ($buy_num > $ticket['num']) {
            throw new \Exception('购买数量不能超过库存数量');
        }
        if(strtotime($session['end_time']) <= time()){
            throw new \Exception('当前场次已结束，无法购买');
        }
        if ($session['identity'] != 1) {
            if ($session['identity'] == 2 && !$house_village) {
                throw new \Exception('仅限业主用户');
            }
            if ($session['identity'] == 3 && $house_village) {
                throw new \Exception('仅限非业主用户');
            }
        }
        if ($session['age_max'] > 0) {
            if (!$user['birthday'] || $user['birthday'] == '0000-00-00') {
                throw new \Exception('请先设置生日');
            }
            $user_age = (time() - strtotime($user['birthday'])) / (3600 * 24 * 365);
            if ($user_age < $session['age_min'] || $user_age > $session['age_max']) {
                throw new \Exception('该场次适用年龄为' . $session['age_min'] . ' ~ ' . $session['age_max'] . '岁');
            }
        }
        //查询已购票数量
        $buy_ticket_count = (new SocialActivityOrder())->where([
            'session_id' => $session_id,
            'activity_id' => $activity_id,
            'ticket_id' => $ticket_id,
            'paid' => 1,
            'status' => SocialActivityOrder::STATUS_10,
        ])->count();
        if ($buy_ticket_count >= $ticket['num']) {
            throw new \Exception('该场次票种已售完');
        }
        if (($buy_ticket_count + $buy_num) > $ticket['num']) {
            throw new \Exception('购买数量不能超过库存数量');
        }
        return [
            'activity' => $activity,
            'session' => $session,
            'ticket' => $ticket,
            'buy_ticket_count' => $buy_ticket_count,
            'buy_num' => $buy_num,
            'user_info' => $user_info,
        ];
    }

    /**
     * 获取订单支付信息
     */
    public function getOrderPayInfo($order_id)
    {
        $order = (new SocialActivityOrder())->where(['id' => $order_id])->findOrEmpty()->toArray();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        $activity = (new SocialActivity())->where(['id' => $order['activity_id']])->findOrEmpty()->toArray();
        if (!$activity) {
            throw new \Exception('活动不存在');
        }
        $time_remaining = 1200 - (time() - strtotime($order['create_time'])); //20分钟未支付订单取消
        if ($time_remaining < 0 && $order['status'] == SocialActivityOrder::STATUS_0) {
            (new SocialActivityOrder())->where(['id' => $order_id])->update(['status' => SocialActivityOrder::STATUS_20, 'update_time' => date('Y-m-d H:i:s', time()), 'cancel_remark' => '20分钟未支付订单取消']);
        }
        //查询已购票数量
        $buy_ticket_count = (new SocialActivityOrder())->where([
            'session_id' => $order['session_id'],
            'activity_id' => $order['activity_id'],
            'ticket_id' => $order['ticket_id'],
            'paid' => 1,
            'status' => SocialActivityOrder::STATUS_10,
        ])->count();
        $ticket = (new SocialActivitySessionTicket())->where(['id' => $order['ticket_id']])->findOrEmpty()->toArray();
        if (!$ticket) {
            throw new \Exception('票种不存在/已被删除');
        }
        if ($buy_ticket_count >= $ticket['num']) {
            (new SocialActivityOrder())->where(['id' => $order_id])->update(['status' => SocialActivityOrder::STATUS_20, 'update_time' => date('Y-m-d H:i:s', time()), 'cancel_remark' => '该场次票种已售完,取消订单']);
            throw new \Exception('该场次票种已售完,订单已取消');
        }
        if (($buy_ticket_count + $order['num']) > $ticket['num']) {
            (new SocialActivityOrder())->where(['id' => $order_id])->update(['status' => SocialActivityOrder::STATUS_20, 'update_time' => date('Y-m-d H:i:s', time()), 'cancel_remark' => '购买数量超过剩余库存数量,取消订单']);
            throw new \Exception('购买数量超过剩余库存数量，请重新购买');
        }
        $return = [
            'paid' => $order['paid'],
            'is_cancel' => $order['status'] == SocialActivityOrder::STATUS_20 ? 1 : 0,
            'time_remaining' => $time_remaining < 0 ? 0 : $time_remaining,
            'mer_id' => 0,
            'city_id' => 0,
            'store_id' => 0,
            'order_money' => $order['amount'], //这个金额需要后续计算出来
            'uid' => $order['uid'],
            'order_no' => $order['order_sn'],
            'title' => '社群活动订单',
            'merchant_balance_open' => false,
            'business_order_sn' => '',
            'open_user_score' => false,
            'use_user_score' => 0,
            'order' => $order,
            'goods_status' => $activity['status'] == 10 ? 1 : 0, //活动开启才可支付
        ];
        return $return;
    }

    /**
     * 获取支付结果页地址
     * @param  integer $is_cancel 1=已取消  0=未取消
     * @return array             返回跳转链接
     */

    public function getPayResultUrl($order_id, $is_cancel = 0)
    {
        $order = (new SocialActivityOrder())->where(['id' => $order_id])->findOrEmpty()->toArray();
        if ($order) {
            return ['redirect_url' => get_base_url('pages/socialActivity/detail?&id=' . $order['activity_id'] . '&order_id=' . $order_id), 'direct' => 0];
        } else {
            return ['redirect_url' => get_base_url('pages/socialActivity/index'), 'direct' => 0];
        }
    }

    /**
     * 支付后处理
     */
    public function afterPay($order_id, $pay_info = [])
    {
        fdump_api([$order_id, $pay_info], 'pay/social_activity_after_pay', 1);
        $order_info = (new SocialActivityOrder())->where(['id' => $order_id])->findOrEmpty()->toArray();
        if (!$order_info) {
            throw new \Exception('订单不存在');
        }
        if ($order_info['status'] != SocialActivityOrder::STATUS_0 || $order_info['paid'] != 0) {
            throw new \Exception('订单状态已支付/已取消');
        }
        //查询订单是否超卖，超卖退钱
        $order_ticket_count = (new SocialActivityOrder())->where([
            'session_id' => $order_info['session_id'],
            'activity_id' => $order_info['activity_id'],
            'ticket_id' => $order_info['ticket_id'],
            'paid' => 1,
            'status' => SocialActivityOrder::STATUS_10,
        ])->count();
        //查询票数量
        $ticket  = (new SocialActivitySessionTicket())->where(['id' => $order_info['ticket_id']])->findOrEmpty()->toArray();
        if ($ticket && ($order_ticket_count + $order_info['num']) > $ticket['num']) {
            //票卖完了，退钱
            $this->refundOrder(['order_id' => $order_id, 'refund_msg' => '该场次票种已售完/购买数量超过库存数量']);
            throw new \Exception('该场次票种已售完/购买数量超过库存数量');
        }
        $now_user = (new UserService())->getUser($order_info['uid']);
        //扣除使用平台余额
        $current_system_balance = 0;
        if ($pay_info['current_system_balance'] > 0) {
            if ($now_user['now_money'] < $pay_info['current_system_balance']) {
                throw new \think\Exception("用户余额不足！");
            }
            $desc = "【{$order_info['activity_name']}】订购社群活动票 ，扣除余额 ，订单编号" . $order_info['order_sn'];
            $use_result = (new UserService())->userMoney($now_user['uid'], $pay_info['current_system_balance'], $desc);
            fdump_api([$now_user['uid'], $pay_info['current_system_balance'], $desc, $use_result], 'pay/social_activity_after_pay', 1);
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['error_code']);
            }
            $current_system_balance = $pay_info['current_system_balance'];
        }

        //在线支付金额
        $money_online_pay = 0;
        if ($pay_info['paid_money'] > 0) {
            $money_online_pay = round($pay_info['paid_money'] * 100) / 100;
        }

        $status = SocialActivityOrder::STATUS_0;
        if ($pay_info['paid']) {
            $status = SocialActivityOrder::STATUS_10;
        }
        $pay_date = date('Y-m-d H:i:s', time());
        $save_data = [
            'money_system_balance' => $current_system_balance,
            'money_online_pay' => $money_online_pay,
            'pay_orderno' => $pay_info['paid_orderid'],
            'online_pay_type' => $pay_info['paid_type'],
            'pay_time' => $pay_date,
            'paid' => $pay_info['paid'],
            'paid_extra' => $pay_info['paid_extra'] ?? '',
            'status' => $status,
            'update_time' => $pay_date,
        ];
        try {
            (new SocialActivityOrder())->where(['id' => $order_id])->save($save_data);
            $score_total = 0;
            if ($order_info['rule_id']) {
                //如果有积分需要加积分
                $rule = (new SocialActivityPointRule())->where(['id' => $order_info['rule_id'], 'is_del' => 0])->findOrEmpty()->toArray();
                fdump_api([$order_info['id'], $rule, (new SocialActivityPointRule())->getLastSql(), '积分规则'], 'pay/social_activity_after_pay', 1);
                if ($rule && $rule['status'] == 1 && ($rule['sign_point'] || $rule['pay_ratio'])) {
                    $scoreParam = [
                        'order_id' => $order_info['id'],
                        'order_type' => 'social_activity_order',
                        'score_type' => 35,
                    ];
                    if ($rule['sign_point'] > 0) {
                        $score_total += $rule['sign_point'];
                        $desc = "【{$order_info['activity_name']}】订购社群活动票 ，奖励报名积分 ，订单编号" . $order_info['order_sn'];
                        $use_result = (new UserService())->addScore($now_user['uid'], $rule['sign_point'], $desc, 0, $scoreParam);
                        fdump_api([$now_user['uid'], $rule['sign_point'], $desc, $use_result, '得积分'], 'pay/social_activity_after_pay', 1);
                    }
                    $score = get_format_number(($order_info['amount'] * $rule['pay_ratio'] / 100), 2);
                    if ($ticket && $ticket['point'] == 1 && $score > 0) {
                        $score_total += $score;
                        $desc = "【{$order_info['activity_name']}】订购社群活动票 ，奖励按支付金额" . $rule['pay_ratio'] . "%获得积分 ，订单编号" . $order_info['order_sn'];
                        $use_result = (new UserService())->addScore($now_user['uid'], $score, $desc, 0, $scoreParam);
                        fdump_api([$now_user['uid'], $score, $desc, $use_result, '得积分'], 'pay/social_activity_after_pay', 1);
                    }
                }
            }
            //微信通知用户
            if (!empty($now_user) && $now_user['openid']) {
                //报名成功通知
                $model = new TemplateNewsService();
                $href = get_base_url('pages/socialActivity/detail?&id=' . $order_info['activity_id'] . '&order_id=' . $order_info['id']);
                $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => L_('社群活动提醒'), 'keyword1' => '您已成功报名本次活动，记得准时参与，不要错过精彩内容哦', 'keyword2' => $order_info['order_sn'], 'keyword3' => getFormatNumber($order_info['amount']), 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_("您已成功报名本次活动，记得准时参与，不要错过精彩内容哦。您的订单号：X1", array("X1" => $order_info['order_sn']))));
                if ($score_total > 0) { //获得积分通知
                    $href = get_base_url('pages/socialActivity/detail?&id=' . $order_info['activity_id'] . '&order_id=' . $order_info['id']);
                    $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => L_('社群活动提醒'), 'keyword1' => '您已成功报名活动，获得活动积分', 'keyword2' => $order_info['order_sn'], 'keyword3' => getFormatNumber($score_total), 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_("您已成功报名活动，获得活动积分。您的订单号：X1", array("X1" => $order_info['order_sn']))));
                }
            }
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 自动取消订单
     */
    public function autoCancelOrder()
    {
        $timeout = time() - 20 * 60; //20分钟未支付订单取消
        $where = [
            ['status', '=', SocialActivityOrder::STATUS_0],
            ['paid', '=', 0],
            ['create_time', '>', $timeout],
        ];
        try {
            (new SocialActivityOrder())->where($where)->saveOrder(['status' => SocialActivityOrder::STATUS_20, 'update_time' => date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
    }

    /**
     * 活动开始前30分钟提醒
     */
    public function autoAdvanceNotice()
    {
        $timeout = time() - 30 * 60; //20分钟未支付订单取消
        $where = [
            ['o.status', '=', SocialActivityOrder::STATUS_0],
            ['o.paid', '=', 0],
            ['o.is_sign', '=', 1],
            ['o.ticket_start_time', '>', date('Y-m-d H:i:s', $timeout)],
            ['o.ticket_end_time', '<', date('Y-m-d H:i:s')],
            ['o.is_notice', '=', 0],
            ['u.openid', '<>', ''],
        ];

        $list = (new SocialActivityOrder())->alias('o')->field('o.*,u.openid')
            ->join('user u', 'o.uid = u.uid')
            ->where($where)->limit(100)->select();
        foreach ($list as $item) {
            try {
                (new SocialActivityOrder())->where(['order_id' => $item['order']])->save(['is_notice' => 1, 'update_time' => date('Y-m-d H:i:s')]);
                $href = get_base_url('pages/socialActivity/detail?&id=' . $item['activity_id'] . '&order_id=' . $item['id']);
                $send = (new TemplateNewsService())->sendTempMsg(
                    'OPENTM400166399',
                    array(
                        'href' => $href,
                        'wecha_id' => $item['openid'],
                        'first' => '社群活动开始通知',
                        'keyword1' => '距离活动开始仅剩30 分钟',
                        'keyword2' => '社群活动开始通知',
                        'keyword3' => date('Y-m-d H:i:s'),
                        'keyword4' => '距离活动开始仅剩30 分钟',
                        'remark' => '距离活动开始仅剩30 分钟',
                    )
                );
            } catch (\Exception $e) {
                fdump_api([$e->getMessage(), $item], 'task/autoAdvanceNotice', 1);
            }
        }
    }

    /**
     * 订单退款
     */
    public function refundOrder($params)
    {
        $order_id = $params['order_id'] ?? '';
        $is_cancel_activity = $params['is_cancel_activity'] ?? 0; //1-取消活动自动取消订单
        $refund_msg = $params['refund_msg'] ?? '';
        $uid = $params['uid'] ?? '';
        if (!$order_id) {
            throw new \think\Exception('订单ID参数错误');
        }
        $order = (new SocialActivityOrder())->where(['id' => $order_id])->find();
        if (!$order) {
            throw new \think\Exception('订单不存在');
        }
        if ($order['paid'] != 1 || $order['status'] != SocialActivityOrder::STATUS_10) {
            throw new \think\Exception('订单已退款/未支付');
        }
        $user = (new User())->where(['uid' => $order['uid']])->findOrEmpty()->toArray();
        if (!$user) {
            throw new \think\Exception('用户不存在');
        }
        if($uid){ //用户主动取消订单
            if($order['uid'] != $uid){
                throw new \think\Exception('无法取消他人订单');
            }
            if($order['ticket_start_time'] < date('Y-m-d H:i:s')){
                throw new \think\Exception('活动已开始，无法取消订单');
            }
        }
        (new SocialActivityOrder())->where(['id' => $order['id']])->save(['status' => SocialActivityOrder::STATUS_20, 'update_time' => date('Y-m-d H:i:s'), 'cancel_remark' => $refund_msg]);
        //退平台余额
        if ($order['money_system_balance'] > 0) {
            $result = (new UserService())->addMoney($order['uid'], $order['money_system_balance'], '社群活动订单退款,增加余额,订单编号' . $order['order_sn']);
            if ($result['error_code']) {
                throw new \think\Exception($result['msg']);
            }
        }
        //扣除赠送积分
        $score_count = (new UserScoreList())->where(['uid' => $order['uid'], 'order_id' => $order_id, 'order_type' => 'social_activity_order', 'type' => 1, 'score_type' => 35])->sum('score');
        if ($score_count > 0) {
            //扣除送积分
            (new UserService())->userScore($order['uid'], $score_count, '社群活动取消订单,扣除赠送积分,订单编号' . $order['order_sn'], 2, ['order_id' => $order_id, 'order_type' => 'social_activity_order', 'score_type' => 35]);
        }
        //退在线支付金额
        if ($order['money_online_pay'] > 0) {
            $PayService = new PayService;
            $end_order = $PayService->getPayOrderInfo($order['pay_orderno']);
            if ($end_order && $end_order['paid'] == '1' && $end_order['paid_money'] > 0) {
                $end_refund = (new PayService)->refund($order['pay_orderno'], $order['money_online_pay']);
                fdump_api($end_refund, 'social_activity/order_refund', true);
            }
        }
        fdump_api(['order_id' => $order['id'], 'refund_system_balance' => $order['money_system_balance'], 'refund_online_pay' => $order['money_online_pay'], 'order' => $order], "social_activity/order_refund", 1);
        if ($is_cancel_activity && $user['openid']) {
            //取消活动微信通知
            $href = get_base_url('pages/socialActivity/detail?&id=' . $order['activity_id'] . '&order_id=' . $order['id']);
            (new TemplateNewsService())->sendTempMsg(
                'TM00017',
                array(
                    'href' => $href,
                    'wecha_id' => $user['openid'],
                    'first' => cfg('group_alias_name') . L_('提醒'),
                    'OrderSn' => $order['order_sn'],
                    'OrderStatus' => L_('很抱歉通知您，您报名的活动因运营调整已取消，给你带来不便深感歉意'),
                    'remark' => "很抱歉通知您，您报名的活动因运营调整已取消，给你带来不便深感歉意"
                )
            );
        }
        return true;
    }

    /**
     * 获取我的订单列表（H5端）
     * @param $params
     */
    public function getMyOrderList($params)
    {
        $uid = $params['uid'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $filter_type = $params['filter_type'] ?? 'all';

        if (!$uid) {
            throw new \Exception('请先登录');
        }

        $where = [];
        $where[] = ['o.uid', '=', $uid];

        if ($filter_type == 'pending_start') {
            $where[] = ['o.status', '=', SocialActivityOrder::STATUS_10];
            $where[] = ['o.ticket_start_time', '>', date('Y-m-d H:i:s')];
        } elseif ($filter_type == 'in_progress') {
            $where[] = ['o.status', '=', SocialActivityOrder::STATUS_10];
            $where[] = ['o.ticket_start_time', '<=', date('Y-m-d H:i:s')];
            $where[] = ['o.ticket_end_time', '>=', date('Y-m-d H:i:s')];
        } elseif ($filter_type == 'ended') {
            $where[] = ['o.status', '=', SocialActivityOrder::STATUS_10];
            $where[] = ['o.ticket_end_time', '<', date('Y-m-d H:i:s')];
        } elseif ($filter_type == 'cancelled') {
            $where[] = ['o.status', '=', SocialActivityOrder::STATUS_20];
        }

        $field = 'o.id,o.order_sn,o.activity_id,o.activity_name,o.session_id,o.session_name,o.ticket_name,o.price,
        o.amount,o.paid,o.status,o.is_sign,o.sign_time,o.ticket_start_time,o.ticket_end_time,o.create_time,o.num, 
        s.location_id, s.location, a.poster';
        $list = (new SocialActivityOrder())
            ->alias('o')
            ->join('social_activity a', 'o.activity_id = a.id')
            ->join('social_activity_session s', 'o.session_id = s.id')
            ->where($where)
            ->field($field)
            ->order('o.create_time DESC')
            ->paginate([
                'list_rows' => $pageSize,
                'page' => $page,
            ]);

        $data = $list ? $list->items() : [];

        foreach ($data as &$item) {
            $item['status_text'] = $this->getOrderStatusText($item);
			$item['poster'] = replace_file_domain($item['poster']);
	        if ($item['location_id']) {
		        $item['location'] = (new HouseVenueActivity())->where(['status' => 1, 'is_del' => 0, 'id' => $item['location_id']])->value('title');
	        }
	        $item['can_use_time'] = date('m-d H:i', strtotime($item['ticket_start_time'])).' ~ '.date('m-d H:i', strtotime($item['ticket_end_time']));
        }

        return $list;
    }

    /**
     * 获取订单状态文本
     * @param $item
     * @return string
     */
	private function getOrderStatusText($item)
	{
		if ($item['status'] == SocialActivityOrder::STATUS_20) {
			return '已取消';
		}
		if ($item['status'] == SocialActivityOrder::STATUS_0) {
			return '待支付';
		}
		if ($item['status'] == SocialActivityOrder::STATUS_10) {
			$now = time();
			$ticket_start_time = strtotime($item['ticket_start_time']);
			$ticket_end_time = strtotime($item['ticket_end_time']);

			if ($now < $ticket_start_time) {
				return '待开始';
			} elseif ($now <= $ticket_end_time) {
				return '进行中';
			} else {
				return '已结束';
			}
		}
		return '未知';
	}
}
