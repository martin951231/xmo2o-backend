<?php
/**
 * 门票订单service
 * @date 2021-12-16 
 */

namespace app\life_tools\model\service;

use app\common\model\db\CardNew;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\merchant_card\MerchantCardService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\user\UserNoticeService;
use app\common\model\service\UserService;
use app\common\model\db\ProcessSubPlan;
use app\common\model\db\SystemOrder;
use app\common\model\service\weixin\TemplateNewsService;
use app\group\model\db\TempOrderData;
use app\life_tools\model\db\LifeScenicActivityDetail;
use app\life_tools\model\db\LifeScenicLimitedSku;
use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsCardOrder;
use app\life_tools\model\db\LifeToolsGroupOrderTourists;
use app\life_tools\model\db\LifeToolsCarParkTools;
use app\life_tools\model\db\LifeToolsMember;
use app\life_tools\model\db\LifeToolsMessage;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketDetail;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketSku;
use app\life_tools\model\db\LifeToolsTicket;
use app\life_tools\model\db\LifeToolsTicketSaleDay;
use app\life_tools\model\db\LifeToolsTicketSku;
use app\merchant\model\service\card\CardNewService;
use app\merchant\model\service\MerchantMoneyListService;
use app\life_tools\model\service\LifeToolsSportsActivityService;
use app\life_tools\model\db\LifeToolsOrderBindSportsActivity;
use app\life_tools\model\db\LifeToolsStockCheck;
use app\common\model\service\export\ExportService as BaseExportService;
use app\life_tools\model\db\LifeToolsGroupOrder;
use app\life_tools\model\db\User;
use app\life_tools\model\db\LifeToolsDistributionUserBindMerchant;
use app\life_tools\model\db\LifeToolsDistributionUser;
use app\life_tools\model\db\LifeToolsTicketDistribution;
use app\life_tools\model\db\LifeToolsDistributionSetting;
use app\life_tools\model\db\LifeToolsDistributionOrder;
use app\life_tools\model\db\LifeToolsDistributionLog;
use app\life_tools\model\service\group\LifeToolsGroupOrderService;
use app\life_tools\model\service\group\LifeToolsGroupOrderTouristsService;
use app\life_tools\model\service\group\LifeToolsGroupSettingService;
use app\life_tools\model\service\group\LifeToolsGroupTicketService;
use app\life_tools\model\service\group\LifeToolsGroupTravelAgencyService;
use app\storestaff\model\service\ScanService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;
use think\facade\Db;
use think\Model;
use app\merchant\model\service\MerchantStoreService;
use app\life_tools\model\db\LifeToolsTicketSpec;
use app\life_tools\model\db\LifeToolsTicketSpecVal;

class LifeToolsOrderService
{
    public $lifeTools              = null;
    public $lifeToolsOrderModel    = null;
    public $LifeToolsOrderDetail   = null;
    public $LifeToolsTicket        = null;
    public $LifeToolsMessage       = null;
    public $LifeToolsMember        = null;
    public $LifeToolsTicketSaleDay = null;
    public $LifeToolsOrderBindSportsActivity = null;
    public $lifeToolsGroupOrderModel = null;
    public $lifeToolsDistributionUserBindMerchantModel = null;
    public $lifeToolsDistributionUserModel = null;
    public $lifeToolsTicketDistributionModel = null;
    public $lifeToolsDistributionSettingModel = null;
    public $lifeToolsDistributionOrderModel = null;
    public $lifeToolsDistributionLogModel = null;
    public $lifeToolsGroupOrderTouristsModel = null;
    public $LifeToolsTicketSku = null;
    public $order_status = [ //10未支付不显示 20未消费已付款，30已消费未评价,40已消费已评价 45申请退款中 50用户取消已退款  60未付款已过期 70已付款已过期 80已全部转赠
        '10' => '待支付', //待付款
        '20' => '待核销', //待核销
        '30' => '已核销未评价', //已核销
        '40' => '已核销已评价', //已核销
        '45' => '售后中', //售后中
        '50' => '已退款', //已退款
        '60' => '已取消', //已取消
        '70' => '已过期自动核销', //已过期
        '80' => '已全部转赠', //全部转赠
    ];
    public function __construct()
    {
        $this->lifeTools              = new LifeTools();
        $this->LifeToolsTicket        = new LifeToolsTicket();
        $this->LifeToolsMember        = new LifeToolsMember();
        $this->LifeToolsMessage       = new LifeToolsMessage();
        $this->lifeToolsOrderModel    = new LifeToolsOrder();
        $this->LifeToolsOrderDetail   = new LifeToolsOrderDetail();
        $this->LifeToolsTicketSaleDay = new LifeToolsTicketSaleDay();
        $this->LifeToolsOrderBindSportsActivity = new LifeToolsOrderBindSportsActivity();
        $this->lifeToolsGroupOrderModel = new LifeToolsGroupOrder();
        $this->lifeToolsDistributionUserBindMerchantModel = new LifeToolsDistributionUserBindMerchant();
        $this->lifeToolsDistributionUserModel = new LifeToolsDistributionUser();
        $this->lifeToolsTicketDistributionModel = new LifeToolsTicketDistribution();
        $this->lifeToolsDistributionSettingModel = new LifeToolsDistributionSetting();
        $this->lifeToolsDistributionOrderModel = new LifeToolsDistributionOrder();
        $this->lifeToolsDistributionLogModel = new LifeToolsDistributionLog();
        $this->lifeToolsGroupOrderTouristsModel = new LifeToolsGroupOrderTourists();
        $this->LifeToolsCardOrderModel = new LifeToolsCardOrder();
        $this->LifeToolsTicketSku = new LifeToolsTicketSku();
    }

    /**
     *批量插入数据
     * @param $data array
     * @return int|bool
     */
    public function addAll($data)
    {
        if (empty($data)) {
            return false;
        }

        $id = $this->lifeToolsOrderModel->addAll($data);
        if (!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取一条条数据
     * @param array $where
     * @return array
     */
    public function getOne($where)
    {
        $result = $this->lifeToolsOrderModel->getOne($where);
        if (empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $result = $this->lifeToolsOrderModel->getSome($where, $field, $order, $page, $limit);
        if (empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取订单列表
     * @param $param array
     * @param $ftype 1=导出
     * @return array
     */
    public function getList($param, $ftype = 0)
    {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $ftype == 1 && $limit = 0;
        $where = [
            ['o.tools_id', '>', 0]
        ];
        if (!empty($param['mer_id'])) {
            $where[] = ['o.mer_id', '=', $param['mer_id']];
        }
        if (!empty($param['keyword'])) {
            switch ($param['search_type']) {
                case 1:
                    $search_type = 'o.order_id|o.real_orderid|o.orderid';
                    break;
                case 2:
                    $search_type = 'o.nickname';
                    break;
                case 3:
                    $search_type = 'o.phone';
                    break;
                case 4:
                    $search_type = 'a.title';
                    break;
                case 5:
                    $search_type = 'o.ticket_title';
                    break;
            }
            $where[] = [$search_type, 'like', '%' . $param['keyword'] . '%'];
        }
        if ($param['type'] == 'pc') {
            $param['type'] = $param['etype'] ?? '';
        }
        if (!empty($param['type'])) {
            switch ($param['type']) {
                case 1:
                    $type = 'stadium';
                    break;
                case 2:
                    $type = 'course';
                    break;
                case 3:
                    $type = 'scenic';
                    break;
                case 4:
                    $type = 'stadium';
                    $where[] = ['o.activity_type', '=', 'sports_activity'];
                    break;
            }
            // $type = $param['type'] == 2 ? 'course' :'stadium';
            $where[] = ['a.type', '=', $type];
        } else {
            $where[] = ['a.type', 'exp', Db::raw('= "course" or a.type = "stadium"')];
        }
        if ($param['status'] != -1) {
            switch ($param['status']) {
                case 30:
                    $where[] = ['o.order_status', 'in', [30, 40]];
                    break;
                default:
                    $where[] = ['o.order_status', '=', $param['status']];
                    break;
            }
        }
        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            switch ($param['time_type']) {
                case 1:
                    $where[] = ['o.add_time', '>=', strtotime($param['begin_time'])];
                    $where[] = ['o.add_time', '<', strtotime($param['end_time']) + 86400];
                    break;
                default:
                    $where[] = ['o.verify_time', '>=', strtotime($param['begin_time'])];
                    $where[] = ['o.verify_time', '<', strtotime($param['end_time']) + 86400];
                    break;
            }
        }

        if(!empty($param['order_type'])){
            if($param['order_type'] == 2){
                $where[] = ['o.order_source', '=', 'pft'];
            }else{
                $where[] = ['o.order_source', '<>', 'pft'];
            }
        }

        // 支付方式
        if (isset($param['pay_type']) && $param['pay_type']) {
            switch ($param['pay_type']) {
                case 'balance':
                    //$where[] = ['o.order_status', '>', 20];
                    $where[] = ['o.merchant_balance_pay|o.system_balance|o.merchant_balance_give', '>', 0];
                    break;
                default:
                    $where[] = ['o.pay_type', '=', $param['pay_type']];
                    break;
            }
        }

        // 店员查询 通过绑定店员过滤
        if (isset($param['staff_id']) && $param['staff_id']) {
            $where[] = ['', 'exp', Db::raw('(t.staff_ids like "%,' . $param['staff_id'] . ',%" OR t.staff_ids = "")')];
        }

        $result = $this->lifeToolsOrderModel->getList($where, $limit);

        require_once '../extend/phpqrcode/phpqrcode.php';
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['type']        = $this->lifeTools->typeMap[$v['type']] ?? '';
                if($v['activity_type'] == "sports_activity"){
                    $result['data'][$k]['type'] = '约战';
                }
                if($v['type'] == 'scenic'){
                    if($v['order_source'] == 'pft'){
                        $result['data'][$k]['type'] = '票付通';
                    }else{
                        $result['data'][$k]['type'] = '普通';
                    }
                }
                
                $result['data'][$k]['add_time']    = !empty($v['add_time']) ? date('Y-m-d H:i:s', $v['add_time']) : '无';
                $result['data'][$k]['verify_time'] = !empty($v['verify_time']) ? date('Y-m-d H:i:s', $v['verify_time']) : '无';
                $result['data'][$k]['order_status_val'] = $this->order_status[$v['order_status']] ?? '未知状态';

                //返回二维码
                $orderDetail = $this->LifeToolsOrderDetail->where('order_id', $v['order_id'])->select();
                $printData = [];
                $num = 0;
                foreach ($orderDetail as $val) {
                    $tmp = [];
                    $tmp['code'] = $val->code;
                    $tmp['pay_code_img']  = $this->getQrCode($val->code);
                    $tmp['start_time'] = $v['start_time'];
                    $tmp['status'] = $val->status;
                    $printData[] = $tmp;
                    $num ++;
                }
                $result['data'][$k]['print_data'] = $printData;

                if ($v['order_status'] == 50) {
                    $result['data'][$k]['order_status_val'] = $v['reply_refund_reason'] == '超时未核销订单自动退款' ? '超时自动退款' : '已退款';
                }
                if($v['is_group'] == 1 && $v['order_status'] == 70){
                    $result['data'][$k]['order_status_val'] = '已过期';
                }

                $result['data'][$k]['pay_type_txt'] = '--';
                if ($v['pay_type'] == 'offline') {
                    $result['data'][$k]['pay_type_txt'] = L_('现金支付');
                } elseif ($v['orderid']) {
                    $payInfo = (new PayService())->getPayOrderData([$v['orderid']]);
                    $payInfo = $payInfo[$v['orderid']] ?? [];
                    $payInfo['pay_type_chanel'] = '';
                    if (isset($payInfo['pay_type'])) {
                        $result['data'][$k]['pay_type_txt'] =  ($payInfo['pay_type_txt'] ? $payInfo['pay_type_txt'] : '--') . ($payInfo['channel'] ? '(' . $payInfo['channel_txt'] . ')' : '');
                    }
                }
                $result['data'][$k]['num'] = $num;
            }
        }
        $result['all_num']   = $this->lifeToolsOrderModel->getListSum($where, 'num') ?? 0;
        $result['all_price'] = $this->lifeToolsOrderModel->getListSum($where, 'total_price') ?? 0;
        return $result;
    }

    /**
     *获取订单详情
     * @param $order_id array
     * @return array
     */
    public function getDetail($order_id)
    {
        $result = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $order_id]);
        if($result){
            if($result['order_status'] == 45){
                $result['refund_money'] = $this->LifeToolsOrderDetail->where([['order_id', '=', $order_id],['refund_status','=',1]])->sum('price');
            }else{
                $result['refund_money'] = $this->LifeToolsOrderDetail->where([['order_id', '=', $order_id],['refund_status','=',2]])->sum('price');
            }
        }
        $result['type_name']   =  $this->lifeTools->typeMap[$result['type']] ?? '';
        $result['add_time']    = !empty($result['add_time']) ? date('Y-m-d H:i:s', $result['add_time']) : '无';
        $result['verify_time'] = !empty($result['verify_time']) ? date('Y-m-d H:i:s', $result['verify_time']) : '无';
        $result['refund_time'] = !empty($result['refund_time']) ? date('Y-m-d H:i:s', $result['refund_time']) : '无';
        $result['order_status_val'] = $this->order_status[$result['order_status']] ?? '未知状态';
        if ($result['order_status'] == 50) {
            $result['order_status_val'] = $result['reply_refund_reason'] == '超时未核销订单自动退款' ? '超时自动退款' : '已退款';
        }
        if($result['is_group'] == 1 && $result['order_status'] == 70){
            $result['order_status_val'] = '已过期';
        }
        if ($result['member_id']) {
            $memberData = $this->LifeToolsMember->getOne(['member_id' => $result['member_id']])->toArray();
            $result['member_name']  = $memberData['name'];
            $result['member_phone'] = $memberData['phone'];
            $result['member_idcard'] = $memberData['idcard'];
        }
        $result['detail'] = $this->LifeToolsOrderDetail->getVerifyList(['d.order_id' => $order_id], 0, 'd.*,o.mer_id,o.uid,o.real_orderid,a.type,a.title,s.name as store_name', 'd.status ASC')['data'];
        foreach ($result['detail'] as $k => $v) {
            $result['detail'][$k]['last_time'] = !empty($v['last_time']) ? date('Y-m-d H:i:s', $v['last_time']) : '无';
            empty($v['staff_name']) && $result['detail'][$k]['staff_name']   = '无';
            empty($v['refund_name']) && $result['detail'][$k]['refund_name'] = '无';
            switch ($v['status']) {
                case 1:
                    $result['detail'][$k]['status_val'] = '未核销';
                    $result['detail'][$k]['time_val']   = '下单时间';
                    break;
                case 2:
                    $result['detail'][$k]['status_val'] = '已核销';
                    $result['detail'][$k]['time_val']   = '消费时间';
                    break;
                case 3:
                    if($result['paid'] == 2){
                        $result['detail'][$k]['status_val'] = '已退款';
                        $result['detail'][$k]['time_val']   = '退款时间';
                    }else{
                        $result['detail'][$k]['status_val'] = '已取消';
                        $result['detail'][$k]['time_val']   = '取消时间';
                    }
                    break;
                case 4:
                    $result['detail'][$k]['status_val'] = '已转赠';
                    $result['detail'][$k]['time_val']   = '转赠时间';
                    break;
                default:
                    $result['detail'][$k]['status_val'] = '未核销';
                    $result['detail'][$k]['time_val']   = '下单时间';
                    break;
            }

            $result['detail'][$k]['canRefund'] = 0;
            if ((($result['order_status'] >= 20 && $result['order_status'] <= 40) || $result['order_status'] == 70) && $v['status'] <= 2) {
                $result['detail'][$k]['canRefund'] = 1;
            }
            $result['detail'][$k]['sku_str'] = '';
            //场馆分布图
            if($result['sku_type'] == 2 && $v['sku_id']){
                $result['detail'][$k]['sku_str'] = $this->LifeToolsTicketSku->where('sku_id', $v['sku_id'])->value('sku_str');
            }
        }
        $result['tools_title_val'] = $result['title'] ?? '';

        // 获取表单自定义信息
        $customForm = (new LifeToolsOrderCustomFormService())->getSome(['order_id' => $order_id]);
        $customForm = (new LifeToolsOrderCustomFormService())->formatData($customForm);
        $result['custom_form'] = $customForm ?: [];

        //团体票
        if ($result['is_group'] == 1) {
            $tour_guide_custom_form = $this->lifeToolsGroupOrderModel->where('order_id', $result['order_id'])->value('tour_guide_custom_form');
            $result['tour_guide_custom_form'] = json_decode($tour_guide_custom_form, true)[0] ?? [];
        }

        return $result;
    }

    /**
     * 课程/场馆-提交订单页数据
     * @param $param array
     */
    public function confirm($param, $uid)
    {
        if (empty($param['ticket_id']) || empty($param['num'])) {
            throw new \think\Exception('参数错误');
        }
        $data = [
            'base_info' => [],
            'saleday'   => [],
            'coupon'    => [
                "sys_hadpull_id" => -1,  //选中的平台优惠券领取ID
                "sys_title"      => "不可使用优惠券", //选中的平台优惠券标题
                "sys_price"      => 0,  //选中的平台优惠券价格
                "mer_hadpull_id" => -1,  //选中的商家优惠券领取ID
                "mer_title"      => "不可使用优惠券", //选中的商家优惠券标题
                "mer_price"      => 0   //选中的商家优惠券价格
            ],
            'activity_info' => [], //约战信息
            'seat_map' => [//场馆座位分布图
                "skuList"   => [],
                "specList"  => []
            ], 
            'sku_ids'   =>  $param['sku_ids']
        ];
        $data['base_info'] = $this->LifeToolsTicket->getDetail($param['ticket_id']);
        if($data['base_info']['status'] != 1){
            throw new \think\Exception('门票已下架！');
        }
        if($data['base_info']['is_close']==1){
            throw new \think\Exception('已暂停营业无法预定！');
        }
        $data['base_info']['num']          = $param['num'];
        $data['base_info']['total_price']  = 0;
        $data['base_info']['coupon_price'] = 0;
        $data['base_info']['pay_price']    = 0;
        $data['base_info']['select_id']    = '';
        $data['base_info']['select_date']  = '';
        $data['base_info']['member_id']    = 0;
        $data['base_info']['member_name']  = '';
        $data['base_info']['member_phone'] = '';
        $data['base_info']['member_idcard'] = '';
        $memWhere = ['uid' => $uid, 'is_del' => 0];
        if (!empty($param['member_id'])) {
            $memWhere['member_id'] = $param['member_id'];
            $memberData = $this->LifeToolsMember->where($memWhere)->find();
            if (empty($memberData)) {
                throw new \think\Exception('联系人不存在');
            }
            $memberData = $memberData->toArray();
        } else {
            $memberData = $this->LifeToolsMember->where($memWhere)->limit(1)->find();
            if (!empty($memberData)) {
                $memberData = $memberData->toArray();
            } else {
                $memberData = [];
            }
        }
        if (!empty($memberData)) {
            $data['base_info']['member_id']    = $param['member_id'];
            $data['base_info']['member_name']  = $memberData['name'];
            $data['base_info']['member_phone'] = $memberData['phone'];
            $data['base_info']['member_idcard'] = $memberData['idcard'];
        }
        if ($data['base_info']['type'] == 'stadium' && $param['sku_id']) {
            $data['saleday'] = (new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $param['ticket_id'], 'sku_id' => $param['sku_id']], true, 'day asc');
            if (empty($data['saleday'])) {
                $data['saleday'] = (new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $param['ticket_id']], true, 'day asc');
            }
        } elseif ($data['base_info']['type'] == 'course') {
            $data['saleday'] = [];
        } else if($data['base_info']['type'] == 'stadium' &&  $data['base_info']['is_sku'] == 2) {
            //场馆多选座位分布图
            $data['saleday'] = [];//(new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $param['ticket_id']], true, 'day asc');
        }else{
            $data['saleday'] = (new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $param['ticket_id']], true, 'day asc');
        }
        if ($data['saleday']) {
            foreach ($data['saleday'] as $k => $v) {
                if (strtotime($v['day']) + 86399 <= time()) {
                    $data['saleday'][$k]['is_sale'] = 0;
                } else {
                    $today = date('Y-m-d');
                    if ($v['day'] == $today && ($data['base_info']['can_book_today'] == 0 || time() >= strtotime($today . ' ' . $data['base_info']['book_today_time']))) {
                        $data['saleday'][$k]['is_sale'] = 0;
                    }
                    if (!empty($param['select_id'])) {
                        if ($v['pigcms_id'] == $param['select_id']) {
                            $data['base_info']['price']       = $v['is_sale'] == 1 ? $v['price'] : 0;
                            $data['base_info']['select_id']   = $v['pigcms_id'];
                            $data['base_info']['select_date'] = $v['day'];
                        }
                    } else if ($data['base_info']['select_id'] == 0 && $data['saleday'][$k]['is_sale'] == 1) {
                        $data['base_info']['price']       = $v['price'];
                        $data['base_info']['select_id']   = $v['pigcms_id'];
                        $data['base_info']['select_date'] = $v['day'];
                    }
                }
            }
        }
        //景区选择预约票才有价格日历
        if($data['base_info']['type'] == 'scenic' && $data['base_info']['scenic_ticket_type'] == 1 && empty($data['base_info']['select_id'])){
            throw new \think\Exception('价格日历有误');
        }

        //期票过期不能购买
        if($data['base_info']['type'] == 'scenic' && $data['base_info']['scenic_ticket_type'] == 0 && $data['base_info']['date_ticket_end'] < date('Y-m-d')){
            throw new \think\Exception('当前门票使用时间已过期');
        }
        
        // 计算库存兼容部分退款start---------------
        $sumWhere = [
            ['ticket_id', '=', $param['ticket_id']],
            //['ticket_time', '=', $data['base_info']['select_date']],
            // ['order_status', 'not in', [50, 60]],
            ['is_give', '=', 0]
        ];
        if($data['base_info']['scenic_ticket_type']==1){
            $sumWhere[] = ['ticket_time', '=', $data['base_info']['select_date']];
        }
        $orderIds = $this->lifeToolsOrderModel->where($sumWhere)->column('order_id');

        $condition = [];
        $condition[] = ['order_id', 'in', $orderIds];
        $condition[] = ['status', '<>', 3];
        $saleNum = $this->LifeToolsOrderDetail->where($condition)->count();

        if($data['base_info']['stock_type'] == 1 ||$data['base_info']['type'] == 'course'){
            $data['base_info']['limit_num'] = $data['base_info']['stock_num'] - $data['base_info']['sale_count'];
        }else{
            $data['base_info']['limit_num'] = $data['base_info']['stock_num'] -  $saleNum;
        }
        // 计算库存兼容部分退款end---------------


        $data['base_info']['limit_num']  <= 0 && $data['base_info']['limit_num'] = 0;
        $data['base_info']['act_type']="normal";
        $data['base_info']['act_id']=0;
        $data['base_info']['sku_id']=0;
        // 活动类型
        $activityType = isset($param['activity_type']) && $param['activity_type'] ? $param['activity_type'] : '';
        //stock_num作为剩余库存
        $data['base_info']['stock_num'] = $data['base_info']['limit_num'];
        if($activityType == 'group'){// 团体票
            $res = (new LifeToolsGroupTicketService())->getOne(['ticket_id' =>$data['base_info']['ticket_id'],'tools_id'=>$data['base_info']['tools_id'], 'is_del'=>0]);
            if($res){
                $data['base_info']['act_type'] = "group";
                $data['base_info']['act_id'] = $res['id'];
                $data['base_info']['limit_num'] = min($res['max_num'], $data['base_info']['limit_num']);
                $data['base_info']['price'] = $res['group_price'];

                // 价格日历
                if ($data['saleday']) {
                    foreach ($data['saleday'] as $k1 => $v1) {
                        $data['saleday'][$k1]['price']=$res['group_price'];
                    }
                }

                // 获得导游自定义表单信息
                $setting = (new LifeToolsGroupSettingService())->getDataDetail(['mer_id' =>$res['mer_id']]);
                if($setting['tour_guide_custom_form']){
                    foreach($setting['tour_guide_custom_form'] as $key => $value){
                        if($value['status'] == 0){
                            unset($setting['tour_guide_custom_form'][$key]);
                        }
                    }
                }
                $data['group']['tour_guide_custom_form'] = array_values($setting['tour_guide_custom_form']);
                $data['group']['expiration_time'] = $setting['expiration_time'];// 订单过期时间
                $data['group']['buy_audit'] = $setting['buy_audit'];// 购票审核1-自动审核0-需要审核

                // 查询旅行社是否审核通过了
                $travel = (new LifeToolsGroupTravelAgencyService())->getTravelByUid($uid, $res['mer_id']);
                if(empty($travel)){
                    throw new \think\Exception('请先申请旅行社认证！');
                }
                if($travel['status'] != 1){
                    throw new \think\Exception('请先通过旅行社认证！');
                }

                $data['group']['travel_agency_id'] = $travel['id'];

            }
 
        }elseif($data['base_info']['type']=='scenic'){
            $where_act = [
                ['lt.tools_id', '=', $data['base_info']['tools_id']], ['lt.status', '=', 1], ['lt.is_del', '=', 0],
                ['la.start_time', '<', time()], ['la.end_time', '>', time()], ['la.type', '=', 'limited'], ['la.is_del', '=', 0], ['sku.ticket_id', '=', $param['ticket_id']]
            ];
            $ret = (new LifeScenicActivityDetail())->getActDetail($where_act, 'la.start_time,la.end_time,act.*,sku.act_stock_num,sku.act_price,l.tools_id,sku.ticket_id');
        } elseif ($data['base_info']['type'] == 'stadium' || $data['base_info']['type'] == 'course') {
            $where_act = [
                ['lt.tools_id', '=', $data['base_info']['tools_id']], ['lt.status', '=', 1], ['lt.is_del', '=', 0], ['sku.ticket_id', '=', $param['ticket_id']],
                ['la.start_time', '<', time()], ['la.end_time', '>', time()], ['la.type', '=', 'limited'], ['la.is_del', '=', 0]
            ];
            $ret = (new LifeToolsSportsSecondsKillTicketDetail())->getActDetail($where_act, 'la.start_time,la.end_time,act.*,sku.act_stock_num,sku.act_price,l.tools_id,sku.ticket_id,sku.day_stock_num');
        }

        if (!empty($ret)) {
            if ($ret['limited_status'] == 1 && $param['activity_type'] == 'limited') {
                $data['base_info']['price'] = $ret['act_price'];
                $data['base_info']['act_type'] = "limited";
                $data['base_info']['act_id'] = $ret['id'];
                $data['base_info']['act_start_time'] = $ret['start_time'];
                $data['base_info']['act_end_time'] = $ret['end_time'];
                $data['base_info']['buy_limit'] = $ret['buy_limit'];

                if ($data['base_info']['type'] == 'stadium' || $data['base_info']['type'] == 'course' || $data['base_info']['type'] == 'scenic') {
                    $data['base_info']['act_stock_num'] = $ret['act_stock_num'] ?? '';
                    $data['base_info']['stock_type'] = $ret['stock_type'] ?? '';
                    $data['base_info']['day_stock_num'] = $ret['day_stock_num'] ?? '';
                } 
                $data['base_info']['limit_num'] = min($data['base_info']['limit_num'], $ret['act_stock_num']);
                $data['base_info']['stock_num'] = $data['base_info']['limit_num'];
                if ($ret['is_discount_share'] == 2) { //是否优惠同享 2否
 
                    $param['mer_hadpull_id'] = -1;
                    $param['sys_hadpull_id']=-1;
                }
                if ($data['saleday']) {
                    foreach ($data['saleday'] as $k1 => $v1) {
                        $data['saleday'][$k1]['price']=$ret['act_price'];
                    }
                }
            }
            
        }
        
        //多规格
        if (($data['base_info']['type'] == 'stadium' || $data['base_info']['type'] == 'course') && $param['sku_id']) {
 
            $sku=(new LifeToolsTicketSku())->getOne(['sku_id'=>$param['sku_id'],'ticket_id'=>$param['ticket_id'],'is_del'=>0]);
            if(empty($sku)){
                throw new \think\Exception('数据繁忙,请重新选择门票下单！');
            }else{
                $sku=$sku->toArray();
                $data['base_info']['sku_id']=$sku['sku_id'];
                $data['base_info']['spec_txt']=$sku['sku_str'];
                $sumWhere1 = [
                    ['ticket_id', '=', $param['ticket_id']],
                    ['ticket_time', '=', $data['base_info']['select_date']],
                    ['sku_id', '=', $data['base_info']['sku_id']],
                    ['order_status', 'not in', [50, 60]],
                    ['is_give', '=', 0]
                ];
                $sumWhere0 = [
                    ['ticket_id', '=', $param['ticket_id']],
                    ['sku_id', '=', $data['base_info']['sku_id']],
                    ['order_status', 'not in', [50, 60]],
                    ['is_give', '=', 0]
                ];
                $data['base_info']['limit_num']=$sku['stock_num'];// -
                //     ($data['base_info']['type'] == 'course' ? $this->lifeToolsOrderModel->where($sumWhere0)->sum('num') : $this->lifeToolsOrderModel->where($sumWhere1)->sum('num'));
                if($data['base_info']['limit_num']<0){
                    $data['base_info']['limit_num']=0;
                }
                $sale_date_price=(new LifeToolsTicketSaleDay())->getDetail(['ticket_id'=>$param['ticket_id'],'sku_id'=>$data['base_info']['sku_id'],'day'=>$data['base_info']['select_date']],'price');
                if(empty($sale_date_price)){
                   $data['base_info']['price']=$sku['sale_price'];
                }else{
                   $data['base_info']['price']=$sale_date_price['price'];
                }
                $sale_date_price=(new LifeToolsTicketSaleDay())->getDetail(['ticket_id'=>$param['ticket_id'],'sku_id'=>$data['base_info']['sku_id'],'day'=>$data['base_info']['select_date']],'price');
               if(empty($sale_date_price)){
                   $data['base_info']['price']=$sku['sale_price'];
               }else{
                   $data['base_info']['price']=$sale_date_price['price'];
               }

            }
            $data['base_info']['stock_num'] = $data['base_info']['limit_num'];
        }
        //        if ($data['base_info']['limit_num'] < $data['base_info']['num']) {
        //            throw new \think\Exception('库存不足');
        //        }
        $data['base_info']['total_price'] = $data['base_info']['price'] * $data['base_info']['num'];
        if (!empty($param['activity_id'])) {
            if ($param['num'] != 1) {
                throw new \think\Exception('约战订单数量必须等于1');
            }
            $param['price'] = $data['base_info']['total_price'];
            $data['activity_info'] = (new LifeToolsSportsActivityService())->getSportsActivityDetail($param);
            $data['base_info']['total_price'] = $data['activity_info']['price'];
        }
        if ($param['mer_hadpull_id'] != -1) { //优先计算商家优惠券
            //if ($param['mer_hadpull_id'] == 0) {
            $merchant_card = false;
            //                if ((new CardNew())->getOne(['mer_id' => $data['base_info']['mer_id'], 'status' => 1])) {
            //                    $merchant_card = true;
            //                }
            $merCouponList = (new MerchantCouponService())->getAvailableCoupon($uid, $data['base_info']['mer_id'], ['can_coupon_money' => $data['base_info']['total_price'], 'business' => $data['base_info']['type'], 'merchant_card' => $merchant_card], true);
            if (!empty($merCouponList[0])) {
                if($param['mer_hadpull_id']>0){
                    foreach ($merCouponList as $coupon_item){
                        if($param['mer_hadpull_id']==$coupon_item['id']){
                            $data['coupon']['mer_hadpull_id'] = $coupon_item['id'];
                            $data['coupon']['mer_title'] = $coupon_item['name'];
                            $data['coupon']['mer_price'] = $coupon_item['is_discount'] ? get_format_number(($data['base_info']['total_price'] - $data['base_info']['coupon_price']) * (1 - $coupon_item['discount'] / 10)) : $coupon_item['discount'];
                            $data['base_info']['coupon_price'] += $data['coupon']['mer_price'];
                        }
                    }
                }
                if(!isset($data['coupon'])||!isset($data['coupon']['mer_hadpull_id'])||$data['coupon']['mer_hadpull_id']<1){
                    $merCouponData = $merCouponList[0]; //(new MerchantCouponService())->formatDiscount([$merCouponList[0]])[0];
                    $data['coupon']['mer_hadpull_id'] = $merCouponData['id'];
                    $data['coupon']['mer_title'] = $merCouponData['name'];
                    $data['coupon']['mer_price'] = $merCouponData['is_discount'] ? get_format_number(($data['base_info']['total_price'] - $data['base_info']['coupon_price']) * (1 - $merCouponData['discount'] / 10)) : $merCouponData['discount'];
                    $data['base_info']['coupon_price'] += $data['coupon']['mer_price'];
                }
            }
            if(!isset($data['coupon'])||!isset($data['coupon']['mer_hadpull_id'])||$data['coupon']['mer_hadpull_id']<1){
                $data['coupon']['mer_hadpull_id'] = 0;
                $data['coupon']['mer_title'] = '无可用优惠券';
            }
            /*} else {
                $merCouponData = (new MerchantCouponService())->getCouponByHadpullId($param['mer_hadpull_id']);
                $data['coupon']['mer_hadpull_id'] = $param['mer_hadpull_id'];
                $data['coupon']['mer_title'] = $merCouponData['name'];
                $data['coupon']['mer_price'] = $merCouponData['is_discount'] ? get_format_number(($data['base_info']['total_price'] - $data['base_info']['coupon_price']) * (1 - $merCouponData['discount'] / 10)) : $merCouponData['discount'];
                $data['base_info']['coupon_price'] += $data['coupon']['mer_price'];
            }*/
        }
        if ($param['sys_hadpull_id'] != -1) {
            if ($param['sys_hadpull_id'] == 0 || $param['sys_hadpull_id'] == '') {
                $sysCouponList = (new SystemCouponService())->getAvailableCoupon($uid, ['can_coupon_money' => $data['base_info']['total_price'], 'business' => $data['base_info']['type']], true);
                if (!empty($sysCouponList[0])) {
                    $sysCouponData = $sysCouponList[0]; //(new SystemCouponService())->formatDiscount([$sysCouponList[0]])[0];
                    $data['coupon']['sys_hadpull_id'] = $sysCouponData['id'];
                    $data['coupon']['sys_title'] = $sysCouponData['name'];
                    $data['coupon']['sys_price'] = $sysCouponData['is_discount'] ? get_format_number(($data['base_info']['total_price'] - $data['base_info']['coupon_price']) * (1 - $sysCouponData['discount'] / 10)) : $sysCouponData['discount'];
                    $data['base_info']['coupon_price'] += $data['coupon']['sys_price'];
                } else {
                    $data['coupon']['sys_hadpull_id'] = 0;
                    $data['coupon']['sys_title'] = '无可用优惠券';
                }
            } else {
                $sysCouponData = (new SystemCouponService())->getCouponByHadpullId($param['sys_hadpull_id']);
                $data['coupon']['sys_hadpull_id'] = $param['sys_hadpull_id'];
                $data['coupon']['sys_title'] = $sysCouponData['name'];
                $data['coupon']['sys_price'] = $sysCouponData['is_discount'] ? get_format_number(($data['base_info']['total_price'] - $data['base_info']['coupon_price']) * (1 - $sysCouponData['discount'] / 10)) : $sysCouponData['discount'];
                $data['base_info']['coupon_price'] += $data['coupon']['sys_price'];
            }
        }

        //场馆座位分布
        if($data['base_info']['type'] == 'stadium' && $data['base_info']['is_sku'] == 2){
            //价格日历
            $data['saleday'] = $this->LifeToolsTicketSaleDay->field('*,1 as is_sale')->group('day')->where('ticket_id', $param['ticket_id'])->select()->toArray();

            $saleday = (new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $param['ticket_id']], true, 'day asc');
            $saleDays = [];
            foreach($saleday as $key => $val){
                $saleDays[$val['sku_id']][$val['day']] = $val;
            }
            $select_date = $data['base_info']['select_date'] ?: date('Y-m-d');
   
            $newDate = date('Y-m-d');
            $time = time();
            $can_not_sale = [];
            foreach($data['saleday'] as $key => $val){
                $data['base_info']['limit_num'] = 1;
                if($val['day'] < $newDate){
                    $data['saleday'][$key]['is_sale'] = 0;
                }
                if ($val['day'] == $newDate && ($data['base_info']['can_book_today'] == 0 || $time >= strtotime($newDate . ' ' . $data['base_info']['book_today_time']))) {
                    $data['saleday'][$key]['is_sale'] = 0;
                }

                if(empty($param['select_id'])){
                    if($val['day'] == $select_date){
                        $data['base_info']['select_id'] = $val['pigcms_id'];
                        $data['base_info']['select_date'] = $select_date;
                    }
                }else{
                    if($val['pigcms_id'] == $param['select_id']){
                        $data['base_info']['select_date'] = $val['day'];
                        $data['base_info']['select_id']   = $val['pigcms_id'];
                    }
                }
                if(!in_array($val['day'], $can_not_sale) &&  $data['saleday'][$key]['is_sale'] == 0){
                    $can_not_sale[] = $val['day'];
                }
            }
            $select_date = $data['base_info']['select_date'];
 
            $spec = $sku = $skuList = [];
            $specList = (object)[];
            $specList->tree = [];
            $specList->list = [];
            $spec = (new LifeToolsTicketSpec())
                ->field(['name','spec_id'])
                ->with(['values'=>function($query){
                    $query->field(['id', 'name', 'spec_id']);
                }])
                ->where('ticket_id', $param['ticket_id'])
                ->where('is_del', 0)
                ->select();

            $sku = (new LifeToolsTicketSku())
                ->field(['sku_id','stock_num','price', 'sale_price','sku_info','sku_str'])
                ->where('ticket_id', $param['ticket_id'])
                ->where('is_del', 0)
                ->select();

            foreach ($spec as $k => $val) {
                $val->k_id = 's' . ($k+1);
                unset($val->spec_id);
                $specList->tree[] = $val;
            }

            $condition = [];
            $condition[] = ['ticket_id', '=', $param['ticket_id']];
            $condition[] = ['ticket_time', '=', $select_date];
            $condition[] = ['status', 'in', [1, 2]];
            // $condition[] = ['paid', '=', 2];
            $orderSkuIds = $this->LifeToolsOrderDetail->field('sku_id,count(*) AS num')->group('sku_id')->where($condition)->select()->toArray();
            if($orderSkuIds){
                $orderSkuIds = array_column($orderSkuIds, 'num','sku_id');
            }
            foreach ($sku as $val)
            {
                $val->id = $val->sku_id;
                $val->sku_info = trim($val->sku_info, '|');
                $val->sku_str = trim($val->sku_str, ',');
                $skuList[$val->sku_info] = $val;
                $skuInfos = explode('|', $val->sku_info);
                foreach ($skuInfos as $k => $v) {
                    list($sid, $vid) = explode(':', $v);
                    $sname = 's' . ($k + 1);
                    $val->$sname = $vid;
                }
                $val->limit_num = $val->stock_num;
                if(isset($orderSkuIds[$val->sku_id])){
                    $val->limit_num = $val->stock_num - $orderSkuIds[$val->sku_id];
                }
                unset($val->sku_info);
                $val->is_sale = 1;
                if(isset($saleDays[$val->sku_id][$select_date]['is_sale'])){
                    $val->is_sale = $saleDays[$val->sku_id][$select_date]['is_sale'];
                }
                if($val->limit_num <= 0){
                    $val->is_sale = 0;
                }
                if(in_array($select_date, $can_not_sale)){
                    $val->is_sale = 0;
                }
                $val->price = $saleDays[$val->sku_id][$select_date]['price'] ?? $val->price;
                $specList->list[] = $val;
            }
            $data['seat_map']['specList'] = $specList;
            $data['seat_map']['skuList'] = $skuList;


            // 多选座位
            if(!empty($param['sku_ids']) && count($param['sku_ids'])){
                $data['base_info']['total_price'] = 0;
                foreach($sku as $key => $val){
                    foreach($param['sku_ids'] as $k => $v){
                        if($val->sku_id == $v){
                            //读取日历价格
                            $data['base_info']['total_price'] += $saleDays[$v][$select_date]['price'] ?? $val->sale_price;
                        }
                    }

                }
                $data['base_info']['price'] = $data['base_info']['total_price'];
            }else{
                $data['base_info']['price'] = $data['base_info']['total_price'] = 0;
            }

        }else{
            $data['seat_map']['specList'] = [];
            $data['seat_map']['skuList'] = [];
        }
        if ($data['base_info']['type'] == 'stadium' && empty($data['base_info']['select_id'])) { //课程不需要选择日期
            throw new \think\Exception('价格日历有误');
        }

        $data['base_info']['pay_price'] = ($data['base_info']['total_price'] - $data['base_info']['coupon_price']) < 0 ? 0 : get_format_number($data['base_info']['total_price'] - $data['base_info']['coupon_price']);

        // 自定义表单数据
        if($data['base_info']['open_custom_form']){
            $data['base_info']['mulit_custom_form'] = 0;
            $data['custom_form'] = (new LifeToolsTicketCustomFormService())->getUserCustomFormDetailByTicketId($data['base_info']['ticket_id']);
        }
        $data['base_info']['temp_order_id'] = (new TempOrderData())->insertGetId([
            'uid'          => $uid,
            'mer_id'       => $data['base_info']['mer_id'],
            'params'       => json_encode($param, true),
            'pay_infact'   => $data['base_info']['pay_price'],
            'order_data'   => json_encode($data['base_info'], true),
            'update_time'  => time(),
            'card_id'      => $data['coupon']['mer_hadpull_id'],
            'card_price'   => $data['coupon']['mer_price'],
            'coupon_id'    => $data['coupon']['sys_hadpull_id'],
            'coupon_price' => $data['coupon']['sys_price'],
        ]);
        //获取当前景区是否有停车场
        $car_park = (new LifeToolsCarParkTools())->get_car_park_num(['tools_id'=>$data['base_info']['tools_id']]);
        $data['base_info']['car_park'] = $car_park;

        //期票
        if($data['base_info']['scenic_ticket_type'] == 0){
            $data['base_info']['date_ticket_time'] = str_replace('-', '.', $data['base_info']['date_ticket_start']) . '-' . str_replace('-', '.', $data['base_info']['date_ticket_end']);
        }

        return $data;
    }

    /*
     *获取用户端订单详情
     * @param $order_id array
     * @return array
     */
    public function getUserDetail($param) {
        $orderId = $param['order_id'] ?? 0;
        $uid = $param['uid'] ?? 0;
        if(empty($orderId) || empty($uid)){
            throw new \think\Exception('缺少参数', 1001);
        }

        $result = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $orderId],'o.*,a.tools_id,a.type,a.cover_image,a.address,a.long,a.lat,a.title,a.time_txt,a.phone as tools_phone');
//        $result = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $orderId,'o.uid'=>$uid],'o.*,a.tools_id,a.type,a.cover_image,a.address,a.long,a.lat,a.title,a.time_txt,a.phone as tools_phone');
        if(empty($result)){
            throw new \think\Exception('订单不存在', 1001);
        }

        //获取此次退款金额
        $refundMoney = $this->LifeToolsOrderDetail->where([['order_id', '=', $orderId],['refund_status','=',1]])->sum('price');
        $result['refund_money'] = $refundMoney;
        if($result['is_group'] == 0 && $result['uid'] != $uid){ //兼容团体票其他游客获取订单信息
            throw new \think\Exception('订单不存在', 1001);
        }
        
        // 门票信息
        $ticketInfo = (new LifeToolsTicketService())->getOne(['ticket_id'=>$result['ticket_id']]);

        $result['type_name']   = $result['type'] == 'stadium' ? '场馆' : '课程';
        $result['add_time']    = !empty($result['add_time']) ? date('Y-m-d H:i:s', $result['add_time']) : '无';
        $result['verify_time'] = !empty($result['verify_time']) ? date('Y-m-d H:i:s', $result['verify_time']) : '无';
        $result['refund_time'] = !empty($result['refund_time']) ? date('Y-m-d H:i:s', $result['refund_time']) : '无';
        $result['order_status_val'] = $this->order_status[$result['order_status']] ?? '未知状态';
        $result['order_type']   = 'lifetools';

        // 平台优惠券信息
        if($result['coupon_id']){
            $couponName = (new SystemCouponService())->getCouponByHadpullId($result['coupon_id'], false);
            $result['coupon_title'] = $couponName['name'] ?? '';
        }

        // 商家优惠券信息
        if($result['card_id']){
            $couponName = (new MerchantCouponService())->getCouponByHadpullId($result['card_id'], false);
            $result['card_title'] = $couponName['name'] ?? '';
        }

        $showCode = true;
        if($result['is_group']){// 团体票
            $groupOrder = (new LifeToolsGroupOrderService())->getOne(['order_id'=>$orderId]);
            if($groupOrder){
                $resultArr['group']['tour_guide_custom_form'] = $groupOrder['tour_guide_custom_form'] ? json_decode($groupOrder['tour_guide_custom_form'], true) : [];
                $resultArr['group']['group_status'] = $groupOrder['group_status'];// 团体票状态0待提交审核10-已提交审核20审核通过30审核不通过40已过期
                if($groupOrder['group_status'] != 20){
                    $showCode = false;
                    // throw new \think\Exception(L_('订单未审核'), 1003);
                }
            }else{
                throw new \think\Exception(L_('订单信息错误'), 1003);
            }

            // 游客信息
            $touristList = (new LifeToolsGroupOrderTouristsService())->getTouristsList(['order_id'=>$orderId,'page_size'=>10000,'uid'=>$uid,'group_tourist_search'=>$param['group_tourist_search']]);
            
            if($touristList){// 团体票
                $touristList = $touristList->toArray();
                $touristList = array_column($touristList['data'], 'tourists_custom_form' ,'id');
            }
            
            $result['refund_num'] = 0;
            //退款数量
            if($result['order_status'] == 50){
                $refundCondition = [];
                $refundCondition[] = ['order_id', '=', $result['order_id']];
                $refundCondition[] = ['status', '=', 3];
                $result['refund_num'] = $this->LifeToolsOrderDetail->where($refundCondition)->count();
            }
        }

        // 查询核销码         
        $codeArr = (new LifeToolsOrderDetailService)->getSome(['order_id'=>$orderId], true, ['detail_id'=>'ASC']);
        $toolsOldPrice = $codeArr[0]['old_price']??0;
        $toolsPrice = $codeArr[0]['price']??0;
        if($codeArr){
            require_once '../extend/phpqrcode/phpqrcode.php';
            //场馆座位分布
            if($result['type'] == 'stadium' && $ticketInfo['is_sku'] == 2){
                // $result['ticket_id']
                $sku = (new LifeToolsTicketSku())
                ->where('ticket_id', $result['ticket_id'])
                ->where('is_del', 0)
                ->column('sku_info', 'sku_id');

                $LifeToolsTicketSpec = new LifeToolsTicketSpec();
                $LifeToolsTicketSpecVal = new LifeToolsTicketSpecVal();
                $specInfo = $LifeToolsTicketSpec->where('ticket_id', $result['ticket_id'])->column('name', 'spec_id');
                $specValInfo = $LifeToolsTicketSpecVal->where('spec_id', 'in', array_keys($specInfo))->column('name', 'id');
                // dd($specInfo, $specValInfo);
                $toolsPrice = $result['price'];
            }
            
            foreach($codeArr as $k => &$_code){

                if($result['is_group']){// 团体票
                
                    if(isset($touristList[$_code['tourists_id']])){
                        $_code['code_img']  = $this->getQrCode($_code['code']);
                        $_code['code_img1']  = $this->getBarCode($_code['code']);
                        
                        // 团体票游客信息
                        $_code['tourists_custom_form'] = $touristList[$_code['tourists_id']] ?? [];
                    }else{
                        unset($codeArr[$k]);
                    } 
                
                }else{
                    $_code['code_img']  = $this->getQrCode($_code['code']);
                    $_code['code_img1']  = $this->getBarCode($_code['code']);
                    
                    // 团体票游客信息
                    $_code['tourists_custom_form'] = $touristList[$_code['tourists_id']] ?? [];
                    if($_code['sku_id'] && isset($sku) && isset($sku[$_code['sku_id']])){
                        $skuList = [];
                        $skuInfo = trim($sku[$_code['sku_id']], '|');
                        $skuInfos = explode('|', $skuInfo);
                        foreach ($skuInfos as $key => $val) {
                            list($sid, $vid) = explode(':', $val);
                            $skuList[$key]['s_name'] = $specInfo[$sid] ?? '';
                            $skuList[$key]['v_name'] = $specValInfo[$vid] ?? '';
                        }
                        $codeArr[$k]['skuList'] = $skuList;
                    }
                }

                //退款中
                if($_code['refund_status'] == 1){
                    $_code['status'] = 5;
                }


            }
            $codeArr = array_values($codeArr);
        }
        

         // 获得支付信息
         $payInfo = [];
         if($result['orderid']){
             $payInfo = (new PayService())->getPayOrderData([$result['orderid']]);
             $payInfo = $payInfo[$result['orderid']] ?? [];
             $payInfo['pay_type_chanel'] = '';
             if(isset($payInfo['pay_type'])){
                 $payInfo['pay_type_chanel'] =  ($payInfo['pay_type'] ? $payInfo['pay_type_txt'] : '').($payInfo['channel'] ? '('.$payInfo['channel_txt'].')' : '');
             }
         }
         $result['pay_info'] = $payInfo;
            if($result['sku_id']){
                $sku_txt=(new LifeToolsTicketSku())->getVal(['sku_id'=>$result['sku_id']],'sku_str');
                $result['spec_txt']= $sku_txt;
            }else{
                $result['spec_txt']="";
            }

        $result['code'] = $showCode ? $codeArr : [];
        //期票
        if($result['scenic_ticket_type'] == 0){
            $result['date_ticket_time'] = str_replace('-', '.', $result['date_ticket_start']) . '-' . str_replace('-', '.', $result['date_ticket_end']);
        }

        //退款列表
        $field = 'SUM(CASE WHEN refund_status IN (0,3) THEN 1 ELSE 0 END) AS refund_num,
                  SUM(CASE WHEN refund_status = 1 THEN 1 ELSE 0 END) AS refund_audit_num,
                  order_id,detail_id,sku_id,refund_status';
        $refundList = $this->LifeToolsOrderDetail->field($field)->where('order_id', $orderId)->group('sku_id')->select()->toArray();
        
        //退款中列表
        $refund_audit_list = [];

        if($refundList && count($refundList)){
            $sku_ids = array_column($refundList, 'sku_id');
            $sku_strs = (new LifeToolsTicketSku())->where('sku_id', 'in', $sku_ids)->column('sku_str', 'sku_id');
            foreach($refundList as $key => $val){
                $refundList[$key]['sku_str'] = $sku_strs[$val['sku_id']] ?? '';
                if($val['refund_status'] == 1){
                    $refund_audit_list[] = [
                        'sku_str'   =>  $refundList[$key]['sku_str'],
                        'num'       =>  $val['refund_audit_num']
                    ];
                }
                unset($refundList[$key]['refund_audit_num'], $refundList[$key]['refund_status']);
            }
        }
        $result['refund_list'] = $refundList;
        $result['refund_audit_list'] = $refund_audit_list;

        //场馆分布图订单数量
        $result['num'] = $this->LifeToolsOrderDetail->where('order_id', $orderId)->count();

        //退款申请中
        if($result['order_status'] == 45){
            $result['num'] = $this->LifeToolsOrderDetail->where('order_id', $orderId)->where('refund_status', 1)->count();
        }
        $resultArr['order'] = $result;

        // 订单详情展示的操作按钮
        $resultArr['button'] = [
            'pay_btn' => 0,//是否显示去支付按钮,0=否1=是
            'reply_btn' => 0,// 是否显示评价按钮,0=否1=是
            'refund_btn' => 0,// 是否显示申请退款按钮,0=否1=是
            'revoke_btn' => 0,// 是否显示撤销申请按钮,0=否1=是
            'again_btn' => 0,//是否显示再来一单按钮,0=否1=是
            'cancel_btn' =>  0,//取消按钮
        ];
        switch($result['order_status']){
            case 10;// 未支付            
                
                // 计算自动取消时间               
                $resultArr['order']['countdown'] = max(0,(cfg('life_tools_sports_order_auto_cancel') * 60) - (time()-strtotime($result['add_time'])));  
                if($resultArr['order']['countdown']){
                    $resultArr['button']['pay_btn'] = 1;    
                    $resultArr['button']['cancel_btn'] = 1;    
                }else{
                    $this->changeOrderStatus($result['order_id'], 60, '超时自动取消');
                }
                break;
            case 20;// 未消费已付款
                if($ticketInfo['is_refund'] && $result['price'] > 0 && !$result['is_group']){
                    $resultArr['button']['refund_btn'] = 1;
                }
                break;
            case 30;// 已消费未评价
                $resultArr['button']['reply_btn'] = 1;
                $resultArr['button']['again_btn'] = 1;
                break;
            case 40;//已消费已评价
                $resultArr['button']['again_btn'] = 1;
                break;
            case 45;//申请退款中
                $resultArr['button']['revoke_btn'] = 1;

                // 计算退款未处理自动同意时间
                if(cfg('life_tools_sports_order_refund_time')){
                    $resultArr['order']['countdown'] = max(0,cfg('life_tools_sports_order_refund_time')*86400 - (time()-$result['reply_refund_time']));
                }
                break;
        }

        //运动约战
        $sportActivity = $this->LifeToolsOrderBindSportsActivity->where('order_id', $result['order_id'])->find();
        if($sportActivity){
            if($sportActivity['group_status'] == 20){
                $resultArr['button']['refund_btn'] = 0;
            }
        }

        // 获取表单自定义信息
        $customForm = (new LifeToolsOrderCustomFormService())->getSome(['order_id'=>$orderId]);
        $customForm = (new LifeToolsOrderCustomFormService())->formatData($customForm);
        $resultArr['custom_form'] = $customForm ?: [];
        //获取当前景区是否有停车场
        $car_park = (new LifeToolsCarParkTools())->get_car_park_num(['tools_id'=>$result['tools_id']]);
        // 购买商品信息
        $resultArr['tools'] = [
            'tools_id' => $result['tools_id'],
            'type' => $result['type'],
            'ticket_id' => $result['ticket_id'],
            'mer_id' => $result['mer_id'],
            'time_txt' => $result['time_txt'],
            'long' => $result['long'],
            'lat' => $result['lat'],
            'address' => $result['address'],
            'image' => replace_file_domain($result['cover_image']),
            'phone' => explode(' ', $result['tools_phone']),
            'title' => $result['title'],// 服务名称
            'sub_title' => $result['ticket_title'],// 门票名称
            'old_price' => $toolsOldPrice,
            'price' => $toolsPrice,
            'car_park' => $car_park,
        ];
        return $resultArr;
    }

    /**
     * 课程/场馆-提交订单
     * @param $arr array
     */
    public function saveOrder($confirm, $userInfo) {
        $PayService = new PayService();
        $orderNo    = $PayService->createOrderNo();
        $now_time   = time();
        $order      = [
            'sku_id' => $confirm['base_info']['sku_id'],
            'real_orderid'  => $orderNo,
            'orderid'       => $orderNo,
            'mer_id'        => $confirm['base_info']['mer_id'],
            'tools_id'      => $confirm['base_info']['tools_id'],
            'ticket_id'     => $confirm['base_info']['ticket_id'],
            'ticket_title'  => $confirm['base_info']['title'],
            'num'           => $confirm['base_info']['num'],
            'uid'           => $userInfo['uid'],
            'nickname'      => $userInfo['nickname'],
            'phone'         => $userInfo['phone'],
            'ticket_time'   => $confirm['base_info']['type'] != 'course' ? $confirm['base_info']['select_date'] : '',
            'member_id'     => $confirm['base_info']['member_id'],
            'total_price'   => $confirm['base_info']['total_price'],
            'price'         => $confirm['base_info']['pay_price'],
            'coupon_id'     => $confirm['coupon']['sys_hadpull_id'] <= 0 ? 0 : $confirm['coupon']['sys_hadpull_id'],
            'coupon_price'  => $confirm['coupon']['sys_price'],
            'card_id'       => $confirm['coupon']['mer_hadpull_id'] <= 0 ? 0 : $confirm['coupon']['mer_hadpull_id'],
            'card_price'    => $confirm['coupon']['mer_price'],
            'order_status'  => 10,
            'last_time'     => $now_time,
            'add_time'      => $now_time,
            'act_id'        => $confirm['base_info']['act_id'],
            'is_group'      => $confirm['base_info']['act_type'] == 'group' ? 1 : 0,// 团体票
            'activity_type' => !empty($confirm['base_info']['activity_id']) ? 'sports_activity' : '',
            'sku_type'      => $confirm['base_info']['is_sku'],
            'order_source'  => $confirm['order_source'] ?? '', //订单来源，pft=票付通
            'source_extra'  => $confirm['source_extra'] ?? '', //订单来源其他信息
            'third_order_no'  => $confirm['third_order_no'] ?? '', //第三方订单号
            'third_client_id'  => $confirm['third_client_id'] ?? '', //三方系统客户标识
        ];
        $order['activity_type'] = isset($confirm['activity_type']) && $confirm['activity_type']=='limited' ? 'limited' : $order['activity_type'];

        Db::startTrans();
        try {
            if($confirm['base_info']['type']=='stadium' || $confirm['base_info']['type']=='course'){
                $list=(new LifeToolsTicketSku())->getSome(['ticket_id'=>$confirm['base_info']['ticket_id'],'is_del'=>0])->toArray();
                if(!empty($list) && (empty($confirm['base_info']['sku_id']) && empty($confirm['sku_ids']))){
                    throw new \think\Exception('此门票为多规格,请选择规格购买');
                }

                if(!empty($list) && !empty($confirm['base_info']['sku_id'])) {
                    $sku=(new LifeToolsTicketSku())->getOne(['sku_id'=>$confirm['base_info']['sku_id'],'ticket_id'=>$confirm['base_info']['ticket_id'],'is_del'=>0]);
                    $sumWhere1 = [
                        ['ticket_id', '=', $confirm['base_info']['ticket_id']],
                        ['ticket_time', '=', $confirm['base_info']['select_date']],
                        ['sku_id', '=', $confirm['base_info']['sku_id']],
                        ['order_status', 'not in', [50, 60]],
                        ['is_give', '=', 0]
                    ];
                    $sumWhere0 = [
                        ['ticket_id', '=', $confirm['base_info']['ticket_id']],
                        ['sku_id', '=', $confirm['base_info']['sku_id']],
                        ['order_status', 'not in', [50, 60]],
                        ['is_give', '=', 0]
                    ];
                    $stock_nums=$sku['stock_num'];// -
                        // ($confirm['base_info']['type'] == 'course' ? (new LifeToolsOrder())->where($sumWhere0)->sum('num') : (new LifeToolsOrder())->where($sumWhere1)->sum('num'));
                    if($stock_nums<=0){
                        throw new \think\Exception('此门票已售完');
                    }else{
                        if($confirm['base_info']['type']=='course' || $confirm['base_info']['type']=='stadium'){
                            (new LifeToolsTicketSku())->setDec(['sku_id'=>$confirm['base_info']['sku_id'],'ticket_id'=>$confirm['base_info']['ticket_id']],'stock_num',$confirm['base_info']['num']);
                        }
                    }
                }
            }
            //期票
            if(isset($confirm['base_info']['scenic_ticket_type']) && $confirm['base_info']['scenic_ticket_type'] == 0){
                $order['scenic_ticket_type'] = $confirm['base_info']['scenic_ticket_type'];
                $order['date_ticket_start'] = $confirm['base_info']['date_ticket_start'];
                $order['date_ticket_end'] = $confirm['base_info']['date_ticket_end'];
                $confirm['base_info']['select_date'] = $confirm['base_info']['date_ticket_end'];
                $order['ticket_time'] = $confirm['base_info']['date_ticket_end'];
            }
            
            $order_id  = $this->lifeToolsOrderModel->add($order);
            $detailArr = [];
            $price     = get_format_number($order['price'] / $order['num']); //单个价格
            $price1    = 0;
            if($confirm['base_info']['type']=='stadium' && $confirm['base_info']['is_sku'] == 2){

                $sku = (new LifeToolsTicketSku())
                // ->field(['sku_id','stock_num','price', 'sale_price','sku_info','sku_str'])
                ->where('ticket_id', $confirm['base_info']['ticket_id'])
                ->where('is_del', 0)
                ->column('sale_price', 'sku_id');

                if(!empty($confirm['sku_ids']) && count($confirm['sku_ids'])){
                    foreach($confirm['sku_ids'] as $key => $val){
                        $detailArr[$key] = [
                            'order_id'  => $order_id,
                            'old_price' => $confirm['base_info']['old_price'],
                            'price'     => $sku[$val] ?? $price,
                            'code'      => createRandomStr(12),
                            'status'    => 1,
                            'last_time' => $now_time,
                            'ticket_id'  => $confirm['base_info']['ticket_id'],
                            'ticket_time' => $confirm['base_info']['select_date'],
                            'sku_id'    =>  $val,
                        ];
                    }
                }else{
                    throw new \think\Exception('请选择座位！');
                }
            }else{
                for ($i = 0; $i < $order['num']; $i++) {
                    if ($i == $order['num'] - 1) { //最后一个核销码价格取差值
                        $price = $order['price'] - $price1;
                    } else {
                        $price1 += $price;
                    }
                    $detailArr[$i] = [
                        'order_id'  => $order_id,
                        'old_price' => $confirm['base_info']['old_price'],
                        'price'     => $price,
                        'code'      => createRandomStr(12),
                        'status'    => 1,
                        'last_time' => $now_time,
                        'ticket_id'  => $confirm['base_info']['ticket_id'],
                        'ticket_time' => $confirm['base_info']['select_date'],
                    ];
                }
            }
            
            $this->LifeToolsOrderDetail->addAll($detailArr);

            if ($order['activity_type'] == 'sports_activity' || $order['activity_type'] == 'engagement') { //运动约战订单
                $order['activity_type'] = 'sports_activity';
                $this->LifeToolsOrderBindSportsActivity->add([
                    'mer_id'       => $order['mer_id'],
                    'activity_id'  => $confirm['base_info']['activity_id'],
                    'order_id'     => $order_id,
                    'title'        => $confirm['base_info']['activity_title'],
                    'num'          => $confirm['base_info']['people_num'],
                    'group_num'    => 0, //支付时+1
                    'group_type'   => $confirm['base_info']['group_type'],
                    'is_public'    => $confirm['base_info']['is_public'],
                    'group_status' => 0,
                    'group_price'  => $confirm['base_info']['group_type'] == 1 ? 0 : get_format_number(($confirm['base_info']['price'] - $confirm['base_info']['total_price']) / ($confirm['base_info']['people_num'] - 1)), //参团价
                ]);
            }


            if($confirm['base_info']['act_type']=="limited" && ($confirm['base_info']['type'] == 'scenic' || $confirm['base_info']['type'] == 'stadium' || $confirm['base_info']['type'] == 'course')){
//            if($confirm['base_info']['act_type']=="limited" && $confirm['base_info']['type']=='scenic'){
                $order_num=(new LifeToolsOrder())->getSum([['add_time','>',$confirm['base_info']['act_start_time']],['add_time','<',$confirm['base_info']['act_end_time']],['uid','=',$userInfo['uid']],['order_status','>=',10],['order_status','<',50],['ticket_id','=',$confirm['base_info']['ticket_id']],['order_id','<>',$order_id],['activity_type','=','limited']],'num');

                if($confirm['base_info']['buy_limit']!=0){
                    if($confirm['base_info']['buy_limit']<=$order_num || $confirm['base_info']['buy_limit']<($order_num+$confirm['base_info']['num'])){
                        throw new \think\Exception('此门票限购'.$confirm['base_info']['buy_limit'].'张,您已达到购买上限，无法下单！');
                    }
                }
                // 验证秒杀库存
                $skuGoods = [
                    'act_id' => $confirm['base_info']['act_id'],
                    'ticket_id' => $confirm['base_info']['ticket_id'],
                    'date' => $order['ticket_time'],
                    'num' => $order['num'],
                    'act_stock_num' =>$confirm['base_info']['limit_num']
                ];
                $check_db = new LifeToolsStockCheck();
                $check_data = [];
                $check_data['goods'] = json_encode($skuGoods);
                $check_id = $check_db->add($check_data);
                
                if(!(new ProcessSubPlan())->getOne(['unique_id'=>'life_tools_stock_check'])){
                    $arr= array();
                    $arr['param'] = serialize(array());
                    $arr['plan_time'] = -1 * time();
                    $arr['space_time'] = 1;
                    $arr['add_time'] = time();
                    $arr['file'] = 'life_tools_stock_check';
                    $arr['time_type'] = 0;
                    $arr['unique_id'] = 'life_tools_stock_check';
                    $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
                    (new ProcessSubPlan())->add($arr);
                }

                $process = true;
                $i=0;
//                while($process){
//                    $check_info = $check_db->where(array('id'=>$check_id))->find();
//                    if($check_info['status']==1){//成功
//                        $process = false;
//                        $check_return = true;
//                    }elseif($check_info['status']==2){//失败
//                        $process = false;
//                        throw new \think\Exception('库存不足');
//                    }
//                    if($i>=50){
//                        $process = false;
//                        throw new \think\Exception('抱歉，系统繁忙，请稍后再试！');
//                    }
//                    $i++;
//                    usleep(300000);
//                }

                if($confirm['base_info']['limit_num']<=0 || ($confirm['base_info']['limit_num']<$confirm['base_info']['num'])){
                    throw new \think\Exception('此门票库存不足,无法下单！');
                }

                if($confirm['base_info']['act_type']=="limited" && $confirm['base_info']['limit_num']>0 && $confirm['base_info']['limit_num']>=$confirm['base_info']['num'] && $confirm['base_info']['type']=='scenic'){
                    try{
                        (new LifeScenicLimitedSku())->setDec(['act_id'=>$confirm['base_info']['act_id'],'ticket_id'=>$confirm['base_info']['ticket_id']],'act_stock_num',$confirm['base_info']['num']);
                    } catch (\Exception $e) {
                        throw new \think\Exception('此门票库存不足,无法下单！');
                    }
                }
                
                if($confirm['base_info']['act_type']=="limited" && ($confirm['base_info']['type']=='stadium' || $confirm['base_info']['type']=='course')){
                    try{
                        if($confirm['base_info']['stock_type']==1 && $confirm['base_info']['act_stock_num']>0){//总库存
                            (new LifeToolsSportsSecondsKillTicketSku())->setDec(['act_id'=>$confirm['base_info']['act_id'],'ticket_id'=>$confirm['base_info']['ticket_id']],'act_stock_num',$confirm['base_info']['num']);
                        }elseif($confirm['base_info']['stock_type']==2 && $confirm['base_info']['day_stock_num']>0){//每日库存
                            (new LifeToolsSportsSecondsKillTicketSku())->setDec(['act_id'=>$confirm['base_info']['act_id'],'ticket_id'=>$confirm['base_info']['ticket_id']],'day_stock_num',$confirm['base_info']['num']);
                        }else{
                            throw new \think\Exception('此门票库存不足,无法下单！');
                        }
                    } catch (\Exception $e) {
                        throw new \think\Exception('此门票库存不足,无法下单！');
                    }
                }
            }
            
            if($confirm['base_info']['act_type']=="group"){// 团体票
                $groupOrder = [
                    'order_id' => $order_id,
                    'travel_agency_id' => $confirm['group']['travel_agency_id'],
                    'tour_guide_custom_form' => $confirm['tour_guide_custom_form'] ? json_encode($confirm['tour_guide_custom_form']) : '',
                    'group_status' => 0 ,
                ];

                $res = (new LifeToolsGroupOrderService())->add($groupOrder);
                if(!$res){
                    throw new \think\Exception('订单保存失败，请稍后重试！');
                }
            }

            $this->changeOrderStatus($order_id, 10, '订单下单成功');

            if(isset($confirm['custom_form']) && $confirm['custom_form']){// 保存自定义表单
                (new LifeToolsOrderCustomFormService())->saveUserCustomFormDetailByTicketId($confirm['base_info']['ticket_id'],$order_id, $confirm['custom_form']);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return [
            'order_type' => 'lifetools',
            'order_id'   => $order_id
        ];
    }

    /**
     * 景区订单转赠
     */
    public function giveOrder($code, $userInfo) {
        $orderDetail = $this->LifeToolsOrderDetail->getOne(['code' => $code, 'status' => 1]);
        if (empty($orderDetail)) {
            throw new \think\Exception('兑换码有误！');
        }
        if ($orderDetail['uid'] == $userInfo['uid']) {
            throw new \think\Exception('不能转赠给自己!');
        }
        $orderDetail = $orderDetail->toArray();
        $orderData   = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $orderDetail['order_id']]);
        $orderNo     = (new PayService())->createOrderNo();
        $now_time    = time();
        $order       = [
            'real_orderid' => $orderNo,
            'orderid'      => $orderNo,
            'mer_id'       => $orderData['mer_id'],
            'tools_id'     => $orderData['tools_id'],
            'ticket_id'    => $orderData['ticket_id'],
            'ticket_title' => $orderData['ticket_title'],
            'num'          => 1,
            'uid'          => $userInfo['uid'],
            'nickname'     => $userInfo['nickname'],
            'phone'        => $userInfo['phone'],
            'ticket_time'  => $orderData['ticket_time'],
            'member_id'    => 0,
            'total_price'  => $orderDetail['price'],
            'price'        => $orderDetail['price'],
            'pay_time'     => $orderData['pay_time'],
            'pay_type'     => $orderData['pay_type'],
            'paid'         => $orderData['paid'],
            'order_status' => $orderData['order_status'],
            'is_give'      => 1,
            'last_time'    => $now_time,
            'add_time'     => $now_time
        ];
        Db::startTrans();
        try {
            $order_id  = $this->lifeToolsOrderModel->add($order);
            $detailArr = [
                'order_id'  => $order_id,
                'old_price' => $orderDetail['old_price'],
                'price'     => $orderDetail['price'],
                'code'      => $orderDetail['code'],
                'status'    => $orderDetail['status'],
                'last_time' => $now_time
            ];
            $this->LifeToolsOrderDetail->add($detailArr);
            $saveData = [
                'mer_id'      => $order['mer_id'],
                'price'       => $order['price'],
                'total_price' => $order['total_price'],
                'keywords'    => $order['ticket_title']
            ];
            (new SystemOrderService())->saveOrder('lifetools', $order_id, $order['uid'], $saveData);
            $this->LifeToolsMessage->add([ //获取赠送消息
                'tools_id' => $orderData['tools_id'],
                'type'     => $orderData['type'],
                'uid'      => $userInfo['uid'],
                'order_id' => $order_id,
                'title'    => '兑换成功',
                'content'  => '恭喜您在' . date('Y-m-d H:i:s') . '成功兑换' . $orderData['ticket_time'] . '的' . $orderData['title'] . '-' . $orderData['ticket_title'],
                'add_time' => $now_time
            ]);
            $this->LifeToolsOrderDetail->updateThis(['detail_id' => $orderDetail['detail_id']], ['status' => 4, 'last_time' => $now_time]);
            $this->LifeToolsMessage->add([ //转增成功消息
                'tools_id' => $orderData['tools_id'],
                'type'     => $orderData['type'],
                'uid'      => $orderData['uid'],
                'order_id' => $order_id,
                'title'    => '赠品成功',
                'content'  => '您在' . $orderData['ticket_time'] . '的' . $orderData['title'] . '-' . $orderData['ticket_title'] . '赠送成功',
                'add_time' => $now_time
            ]);
            if (empty($this->LifeToolsOrderDetail->getOne(['order_id' => $orderDetail['order_id'], 'status' => 1]))) { //全部转赠
                $status = 80;
                if (!empty($this->LifeToolsOrderDetail->getOne(['order_id' => $orderDetail['order_id'], 'status' => 2]))) { //部分核销
                    $status = 30;
                }
                $this->changeOrderStatus($orderDetail['order_id'], $status, '全部转赠');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 课程/场馆-申请退款
     * @param $param array
     */
    public function supplyRefund($param, $note = '用户申请退款', $is_user = 1) {
        if ((empty($param['order_id']) && empty($param['detail_ids'])) || empty($param['reason'])) {
            throw new \think\Exception('参数缺失');
        }
        if(!empty($param['detail_ids']) && count($param['detail_ids'])){
            $param['order_id'] = $param['detail_ids'][0]['order_id'] ?? 0;
        }
        $orderDetail = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $param['order_id']]);

        if (empty($orderDetail) || $orderDetail['order_status'] != 20) {
            throw new \think\Exception('该订单状态不支持申请退款');
        }
        if($orderDetail['is_group'] == 1 && $is_user == 1){
            throw new \think\Exception('团体票不支持退款');
        }
        $refund_where = [
            'order_id' => $param['order_id'],
            'status'   => 1
        ];
        $refund_num = $this->LifeToolsOrderDetail->getCount($refund_where) ?? 0;
        if ($refund_num <= 0) throw new \think\Exception('没有未核销的订单，请检查');

        //选退
        if(!empty($param['detail_ids']) && count($param['detail_ids'])){
            //场馆分布图
            if($orderDetail['sku_type'] == 2){
                foreach($param['detail_ids'] as $key => $val){
                    if($val['num'] > 0){
                        $detailIds[] = $val['detail_id'];
                    }
                }
                if(count($detailIds) == 0){
                    throw new \think\Exception('数量不能为0');
                }
                $condition = [];
                $condition[] = ['status', '=', 1];
                $condition[] = ['detail_id', 'in', $detailIds];
                $refund_money = $this->LifeToolsOrderDetail->where($condition)->sum('price');
                $param['order_id'] = $this->LifeToolsOrderDetail->where($condition)->value('order_id');
                $this->LifeToolsOrderDetail->where($condition)->update([
                    'refund_status' =>  1
                ]);
            }else{
                $num = $param['detail_ids'][0]['num'] ?? 0;
                if($num == 0){
                    throw new \think\Exception('数量不能为0');
                }
                $condition = [];
                $condition[] = ['status', '=', 1];
                $condition[] = ['order_id', '=', $param['order_id']];
                $detailList = $this->LifeToolsOrderDetail->where($condition)->limit($num)->select();
                if(!$detailList){
                    throw new \think\Exception('未找到可退的订单！');
                }
                $refund_money = 0;
                $detailIds = [];
                foreach($detailList as $key => $val){
                    $refund_money += $val['price'];
                    $detailIds[] = $val['detail_id'];
                }
                if($refund_money > $orderDetail['price']){
                    $refund_money = $orderDetail['price'];
                }
                $condition[] = ['detail_id', 'in', $detailIds];
                $this->LifeToolsOrderDetail->where($condition)->update([
                    'refund_status' =>  1
                ]);
            }
            
        }else{
            //按订单退
            $refund_money = $this->LifeToolsOrderDetail->getSum($refund_where, 'price') ?? 0;
            $this->LifeToolsOrderDetail->where($refund_where)->update([
                'refund_status' =>  1
            ]);
        }

        $this->changeOrderStatus($param['order_id'], 45, $note);
        $refund = [
            'reply_refund_reason' => $param['reason'],
            'reply_refund_time'   => time(),
            'refund_money'        => $refund_money + $orderDetail['refund_money']
        ];
        $this->lifeToolsOrderModel->updateThis(['order_id' => $param['order_id']], $refund);
        
        return true;
    }

    /**
     * 课程/场馆-撤销申请
     * @param $param array
     */
    public function revokeRefund($order_id) {
        if (empty($order_id)) {
            throw new \think\Exception('参数缺失');
        }
        $orderDetail = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $order_id]);
        if (empty($orderDetail) || $orderDetail['order_status'] != 45) {
            throw new \think\Exception('该订单状态不支持撤销申请');
        }
        $this->changeOrderStatus($order_id, 20, '用户撤销申请');
        $refund = [
            'reply_refund_reason' => '',
            'reply_refund_time'   => 0,
            'refund_money'        => 0
        ];
        $this->LifeToolsOrderDetail->where('order_id', $order_id)->where('refund_status', 1)->update([
            'refund_status' =>  0
        ]);
        $this->lifeToolsOrderModel->updateThis(['order_id' => $order_id], $refund);
        return true;
    }

    /**
     * 同意退款
     * @param $order_ids array
     */
    public function agreeRefund($order_ids, $note = '商家同意退款') {
        foreach ($order_ids as $order_id) {
            $this->changeOrderStatus($order_id, 50, $note);
        } 
        return true;
    }

    /**
     * 退款退回三级分销佣金
     */
    private function refundDistributionCommission($order_ids)
    {
        $condition = [];
        $condition[] = ['order_id', 'in', $order_ids];
        $order = $this->lifeToolsDistributionOrderModel->where($condition)->select();
        foreach ($order as $item) {
            $item->status = -1;
            $item->save();
            
            $condition = [];
            $condition[] = ['user_id', '=', $item->user_id];
            $condition[] = ['is_del', '=', 0];
            $user = $this->lifeToolsDistributionUserModel->where($condition)->find();
            $user->commission_level_1 = $this->lifeToolsDistributionOrderModel->getCommission($user->user_id, 0, 1);
            $user->commission_level_2 = $this->lifeToolsDistributionOrderModel->getCommission($user->user_id, 0, 2);
            $user->save();

            $condition = [];
            $condition[] = ['user_id', '=', $item->user_id];
            $condition[] = ['mer_id', '=', $item->mer_id];
            $condition[] = ['is_del', '=', 0];
            $userMer = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->find();
            $userMer->commission = $this->lifeToolsDistributionOrderModel->getCommission($user->user_id, $item->mer_id, 1);
            $userMer->invit_money = $this->lifeToolsDistributionOrderModel->getCommission($user->user_id, $item->mer_id, 2);
            $userMer->save();

        }
        $condition = [];
        $condition[] = ['order_id', 'in', $order_ids];
        $log = $this->lifeToolsDistributionLogModel->where($condition)->select();
        foreach ($log as $item) {
            $item->status = -1;
            $item->save();
        }
    }

    /**
     * 过期未核销商家退款
     * @param $order_ids array
     */
    public function agreeOutRefund($detail_id, $note = '过期未核销商家退款', $merUser = []) {
        $detailData  = $this->LifeToolsOrderDetail->getOne(['detail_id' => $detail_id])->toArray();
        $orderDetail = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $detailData['order_id']]);
        if (empty($orderDetail)) {
            throw new \think\Exception('该订单不存在');
        }
        Db::startTrans();
        try {
            $this->LifeToolsOrderDetail->updateThis(['detail_id' => $detail_id], [
                'status' => 3,
                'refund_by'     => $merUser['mer_id'],
                'refund_name'   => $merUser['name'],
                'refund_reason' => $note,
                'last_time'     => time()
            ]);
            // $this->lifeTools->setDec(['tools_id' => $orderDetail['tools_id']], 'sale_count', 1); //减少销量
            $this->lifeTools->where('tools_id', $orderDetail['tools_id'])->update([
                'sale_count'    =>  $this->LifeToolsOrderDetail->getSaleCount($orderDetail['tools_id'])
            ]);
            
            // $this->LifeToolsTicket->setDec(['ticket_id' => $orderDetail['ticket_id']], 'sale_count', 1); //减少销量
            $this->LifeToolsTicket->where('ticket_id', $orderDetail['ticket_id'])->update([
                'sale_count'    =>  $this->LifeToolsOrderDetail->getTicketSaleCount($orderDetail['ticket_id'])
            ]);

            $refundMoney = get_format_number($orderDetail['price'] / $orderDetail['num']);
            $refund = [
                'reply_refund_reason' => $note,
                'reply_refund_time'   => time(),
                'refund_money'        => $refundMoney + $orderDetail['refund_money']
            ];
            $is_all = 0;
            if (empty($this->LifeToolsOrderDetail->getOne([['order_id', '=', $detailData['order_id']], ['status', '<', 2]])) || $refund['refund_money'] >= $orderDetail['price']) { //全退
                $refundMoney = $orderDetail['price'] - $orderDetail['refund_money'];
                $refund['refund_money'] = $orderDetail['price'];
                $refund['order_status'] = 50;
                (new SystemOrderService)->refundOrder('lifetools', $detailData['order_id']);
                $is_all = 1;
            }
            $this->lifeToolsOrderModel->updateThis(['order_id' => $detailData['order_id']], $refund);
            $msg = '您购买的订单号' . $orderDetail['real_orderid'] . '的' . $orderDetail['title'] . $orderDetail['ticket_title'] . '已退款成功，退款原因：' . $note;
            $this->LifeToolsMessage->add([
                'tools_id' => $orderDetail['tools_id'],
                'type'     => $orderDetail['type'],
                'uid'      => $orderDetail['uid'],
                'order_id' => $detailData['order_id'],
                'title'    => '商家取消订单',
                'content'  => $msg,
                'add_time' => time()
            ]);
            $this->refund($detailData['order_id'], $refundMoney);

            $moneyListMod = new MerchantMoneyListService();
            $records  = $moneyListMod->getAll(['order_id' => $orderDetail['order_id'], 'type' => 'lifetools']);
            $totalGet = $totalRefund = 0;
            foreach ($records as $v) {
                if ($v['income'] == 1) {
                    $totalGet += $v['money'];
                } else {
                    $totalRefund += $v['money'];
                }
            }
            if ($is_all == 1) {
                $reduceMoney = get_format_number($totalGet - $totalRefund);
            } else {
                $reduceMoney = get_format_number($totalGet / $orderDetail['num']);
            }
            $reduceMoney > 0 && $moneyListMod->useMoney($orderDetail['mer_id'], $reduceMoney, 'lifetools', '体育健身订单部分退款,减少余额,订单编号' . $orderDetail['orderid'], $orderDetail['order_id'], 0, 0, [], 0);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    function refundOne($detail_id, $reason = '平台退款', $admin = [])
    {
        $detailData  = $this->LifeToolsOrderDetail->getOne(['detail_id' => $detail_id])->toArray();
        $orderDetail = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $detailData['order_id']]);
 
        if (empty($orderDetail)) {
            throw new \think\Exception('该订单不存在');
        }
        if(!in_array($orderDetail['order_status'], [20, 30, 40, 45, 70, 80])){
            throw new \think\Exception('该订单状态不支持退款');
        }
        if($detailData['status'] == '3'){
            throw new \think\Exception('请勿重复退款！');
        }


        
        Db::startTrans();
        try {

            $refundMoney = $detailData['price'];

            $this->LifeToolsOrderDetail->updateThis(['detail_id' => $detail_id], [
                'status' => 3,
                'refund_by'     => $admin['id'] ?? 0,
                'refund_name'   => $admin['name'] ?? '',
                'refund_reason' => $reason,
                'last_time'     => time()
            ]);

            $refund = [
                'reply_refund_reason' => $reason,
                'reply_refund_time'   => time(),
                'refund_time'         => time(),
                'refund_money'        => $refundMoney + $orderDetail['refund_money']
            ];
            

            //全退 
            if($this->LifeToolsOrderDetail->where('order_id', $orderDetail['order_id'])->where('status', 3)->count() == $orderDetail['num']){
                $refundMoney = $orderDetail['price'] - $orderDetail['refund_money'];
                $refund['refund_money'] = $orderDetail['price'];
                $refund['order_status'] = 50;
                (new SystemOrderService)->refundOrder('lifetools', $detailData['order_id']);
                //增加消息通知
                (new UserNoticeService())->addNotice([
                    'type'=>0,
                    'business'=>'scenic',
                    'order_id'=>$orderDetail['order_id'],
                    'tools_id'=>$orderDetail['tools_id'],
                    'uid'=>$orderDetail['uid'],
                    'title'=>'商家手动取消订单',
                    'content'=>'商家手动取消订单，钱已退回，查看订单'
                ]);
            }
            

            $this->lifeToolsOrderModel->updateThis(['order_id' => $detailData['order_id']], $refund);



            $this->refund($detailData['order_id'], $refundMoney);

 
            //根据核销码查询订单信息
            $orderInfo = $this->LifeToolsOrderDetail->getInfoByCode(['a.detail_id'=>$detail_id],['b.is_group','c.openid','a.order_id','b.real_orderid','b.ticket_title','d.title as tools_title','b.uid','b.tools_id','d.type']);
            if(!$orderInfo){
                throw new \think\Exception(L_("未查询到订单信息"));
            }
            $openid = $orderInfo['openid']; 
            //发送微信公众号
            $msg = '您购买的订单号' . $orderDetail['real_orderid'] . '的' . $orderDetail['title'] . $orderDetail['ticket_title'] . '已退款成功，退款原因：' . $reason;
            if ($openid) {
                $model = new TemplateNewsService();
                $model->sendTempMsg('TM00017', array(
                    'wecha_id' => $openid,
                    'first' => '平台于' . date('Y-m-d H:i:s') . '时，取消了【'.$orderInfo['tools_title'].'】-'.$orderInfo['ticket_title'],
                    'OrderSn' => $orderInfo['real_orderid'],
                    'OrderStatus' => '退款成功',
                    'remark' => $msg
                ));
            } 

        

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 拒绝退款
     * @param $order_ids array
     */
    public function refuseRefund($order_ids, $reason = '') {
        foreach ($order_ids as $order_id) {
            $where  = ['order_id' => $order_id];
            $update = [
                'refund_refuse_reason' => $reason,
                'refund_refuse_time' => time(),
                'refund_money' => 0,
                'refund_time' => 0
            ];
            $this->lifeToolsOrderModel->updateThis($where, $update);
            $this->changeOrderStatus($order_id, 20, '商家拒绝退款');
        }
        return true;
    }

    /**
     * 统一的修改订单状态（所有修改订单状态的地方必须调用这个方法）
     * 方便统一处理
     */
    public function changeOrderStatus($order_id, $status, $note)
    {
        $orderDetail = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $order_id]);
        if (empty($orderDetail)) return false;
        Db::startTrans();
        try {
            $where  = ['order_id' => $order_id];
            $update = [
                'order_status' => $status,
                'last_time'    => time(),
                'old_status'   =>  $orderDetail['order_status']
            ];
            if ($status == 30) {
                $update['verify_time'] = time();
            }
            $res = $this->lifeToolsOrderModel->updateThis($where, $update);
            if ($res !== false) {
                $res = true;
                //逻辑业务处理
                switch ($status) {
                    case 10://下单
                        $saveData = [
                            'mer_id'      => $orderDetail['mer_id'],
                            'price'       => $orderDetail['price'],
                            'total_price' => $orderDetail['total_price'],
                            'keywords'    => $orderDetail['ticket_title']
                        ];
                        (new SystemOrderService)->saveOrder('lifetools', $order_id, $orderDetail['uid'], $saveData);
                        // $this->lifeTools->setInc(['tools_id' => $orderDetail['tools_id']], 'sale_count', $orderDetail['num']); //增加销量
                        $this->lifeTools->where('tools_id', $orderDetail['tools_id'])->update([
                            'sale_count'    =>  $this->LifeToolsOrderDetail->getSaleCount($orderDetail['tools_id'])
                        ]);
                        // $this->LifeToolsTicket->setInc(['ticket_id' => $orderDetail['ticket_id']], 'sale_count', $orderDetail['num']); //增加销量
                        $this->LifeToolsTicket->where('ticket_id', $orderDetail['ticket_id'])->update([
                            'sale_count'    =>  $this->LifeToolsOrderDetail->getTicketSaleCount($orderDetail['ticket_id'])
                        ]);

                        break;
                    case 20://付款
                        (new SystemOrderService)->paidOrder('lifetools', $order_id);
                        if ($orderDetail['activity_type'] == 'sports_activity') {
                            $activityData = $this->LifeToolsOrderBindSportsActivity->getOne(['order_id' => $order_id]);
                            if (!empty($activityData)) {
                                $actData  = ['group_num' => $activityData['group_num'] + 1];
                                $actWhere = [];
                                if ($activityData['leader_order_id'] > 0) { //参团
                                    $actData['group_num'] = $this->LifeToolsOrderBindSportsActivity->where(['order_id' => $activityData['leader_order_id']])->value('group_num') + 1;
                                    $actWhere[] = ['leader_order_id|order_id', '=', $activityData['leader_order_id']];
                                    $actWhere[] = ['group_status', '<', 60];
                                    $actWhere[] = ['group_status', '<>', 40];
                                } else {
                                    $actWhere[] = ['order_id', '=', $activityData['order_id']];
                                }
                                if ($activityData['num'] - $actData['group_num'] <= 0) { //成团
                                    $actData['group_status'] = 20;
                                } else {
                                    $actData['group_status'] = 10;
                                }
                                $this->LifeToolsOrderBindSportsActivity->updateThis($actWhere, $actData);
                            }
                        }
                        $msg   = '';
                        $title = '预约成功';
                        if ($note == '商家拒绝退款') {
                            $msg   = '您申请的' . $orderDetail['ticket_title'] . '的退款已拒绝';
                            $title = '退款失败';
                            
                            $where['status'] = 1;
                            $where['refund_status'] = 1;
                            $this->LifeToolsOrderDetail->updateThis($where, ['last_time' => time(), 'refund_status' => 3]); //更新详情表
                        } else {
                            switch ($orderDetail['type']) { //scenic-景区，stadium-场馆，course-课程
                                case 'scenic':
                                case 'stadium':
                                    $msg = '恭喜您在' . date('Y-m-d H:i:s') . '成功预定了' . $orderDetail['ticket_time'] . '的' . $orderDetail['title'] . '-' . $orderDetail['ticket_title'];
                                    break;
                                case 'course':
                                    $msg = '恭喜您在' . date('Y-m-d H:i:s') . '购买了' . $orderDetail['title'] . '-' . $orderDetail['ticket_title'];
                                    break;
                            }
                        }
                        $this->LifeToolsMessage->add([
                            'tools_id' => $orderDetail['tools_id'],
                            'type'     => $orderDetail['type'],
                            'uid'      => $orderDetail['uid'],
                            'order_id' => $order_id,
                            'title'    => $title,
                            'content'  => $msg,
                            'add_time' => time()
                        ]);
                        break;
                    case 30: //消费
                    case 70: //已付款已过期
                    case 80: //已全部转赠
                        (new SystemOrderService)->completeOrder('lifetools', $order_id);
                        if ($status == 70) {
                            switch ($orderDetail['type']) {
                                case 'stadium':
                                    $title = '场馆已过期';
                                    break;
                                case 'course':
                                    $title = '课程已过期';
                                    break;
                                default:
                                    $title = '门票已过期';
                                    break;
                            }
                            $msg = $orderDetail['ticket_title'] . '在于' . date('Y-m-d H:i:s') . '过期';
                            $this->LifeToolsMessage->add([
                                'tools_id' => $orderDetail['tools_id'],
                                'type'     => $orderDetail['type'],
                                'uid'      => $orderDetail['uid'],
                                'order_id' => $order_id,
                                'title'    => $title,
                                'content'  => $msg,
                                'add_time' => time()
                            ]);
                        }
                        $this->merchantAddMoney($order_id);
                        if($status==30){
                            //增加消息通知
                            (new UserNoticeService())->addNotice([
                                'type'=>0,
                                'business'=>'scenic',
                                'order_id'=>$order_id,
                                'tools_id'=>$orderDetail['tools_id'],
                                'uid'=>$orderDetail['uid'],
                                'title'=>'核销成功,写评价说感受',
                                'content'=>'您的景区门票已经核销，请查看订单'
                            ]);
                        }
                        break;
                    case 40://评价
                        (new SystemOrderService)->commentOrder('lifetools', $order_id);
                        break;
                    case 45://申请退款
                        (new SystemOrderService)->refundingOrder('lifetools', $order_id);
                        if ($note == '用户申请退款') {
                            $msg = '您已经申请' . $orderDetail['ticket_title'] . '的退款，请耐心等候';
                            $this->LifeToolsMessage->add([
                                'tools_id' => $orderDetail['tools_id'],
                                'type'     => $orderDetail['type'],
                                'uid'      => $orderDetail['uid'],
                                'order_id' => $order_id,
                                'title'    => '申请退款',
                                'content'  => $msg,
                                'add_time' => time()
                            ]);
                        }
                        break;
                    case 50://用户取消已退款
                    case 60://未付款已过期
                        if ($orderDetail['order_status'] <= 10) {
                            (new SystemOrderService)->cancelOrder('lifetools', $order_id);
                            //运动约战
                            $activityData = $this->LifeToolsOrderBindSportsActivity->getOne(['order_id' => $order_id]);
                            if (!empty($activityData)) {
                                $this->LifeToolsOrderBindSportsActivity->updateThis(['pigcms_id' => $activityData['pigcms_id']], ['group_status' => 30]);
                            }
                            $refundTicketNum = $orderDetail['num'];
                            $refundMoney = 0;

                            if($status==60){
                                //增加消息通知
                                (new UserNoticeService())->addNotice([
                                    'type'=>0,
                                    'business'=>'scenic',
                                    'order_id'=>$order_id,
                                    'tools_id'=>$orderDetail['tools_id'],
                                    'uid'=>$orderDetail['uid'],
                                    'title'=>'订单取消提醒',
                                    'content'=>'支付超时，订单取消'
                                ]);
                            }
                        } else {
                            if ($orderDetail['refund_money'] < 0) throw new \think\Exception('退款金额错误，请检查');
                            if ($note == '商家同意退款') {
                                $msg = $orderDetail['ticket_title'] . '已被' . $orderDetail['title'] . '退款成功';
                                $this->LifeToolsMessage->add([
                                    'tools_id' => $orderDetail['tools_id'],
                                    'type'     => $orderDetail['type'],
                                    'uid'      => $orderDetail['uid'],
                                    'order_id' => $order_id,
                                    'title'    => '商家退款',
                                    'content'  => $msg,
                                    'add_time' => time()
                                ]);
                                //增加消息通知
                                (new UserNoticeService())->addNotice([
                                    'type'=>0,
                                    'business'=>'scenic',
                                    'order_id'=>$order_id,
                                    'tools_id'=>$orderDetail['tools_id'],
                                    'uid'=>$orderDetail['uid'],
                                    'title'=>'商家已经同意退款',
                                    'content'=>'您申请的退款商家已经同意，请查看订单详情'
                                ]);
                            } else if ($note == '超时未核销订单自动退款') {
                                $msg = $orderDetail['ticket_title'] . '退款成功';
                                $this->LifeToolsMessage->add([
                                    'tools_id' => $orderDetail['tools_id'],
                                    'type'     => $orderDetail['type'],
                                    'uid'      => $orderDetail['uid'],
                                    'order_id' => $order_id,
                                    'title'    => '过期已退款',
                                    'content'  => $msg,
                                    'add_time' => time()
                                ]);
                                //增加消息通知
                                (new UserNoticeService())->addNotice([
                                    'type'=>0,
                                    'business'=>'scenic',
                                    'order_id'=>$order_id,
                                    'tools_id'=>$orderDetail['tools_id'],
                                    'uid'=>$orderDetail['uid'],
                                    'title'=>'订单取消提醒',
                                    'content'=>'超时未核销订单自动退款'
                                ]);
                            }
                            $condition = [];
                            $condition[] = ['order_id', '=', $order_id];
                            $condition[] = ['refund_status', 'in', [1, 2]];
                            $num = $this->LifeToolsOrderDetail->where($condition)->count();
                            $refundTicketNum = $this->LifeToolsOrderDetail->where([['order_id', '=', $order_id],['refund_status','=',1]])->count();
                            $refundMoney = $this->LifeToolsOrderDetail->where([['order_id', '=', $order_id],['refund_status','=',1]])->sum('price');
                            //全部数量
                            $allNum = $this->LifeToolsOrderDetail->where('order_id', $order_id)->count();
                            if($num != $allNum){//不全退 
                                $this->lifeToolsOrderModel->where('order_id', $order_id)->update([
                                    'order_status'  =>  $orderDetail['old_status'],
                                    'refund_time'   =>  time()
                                ]);
                                $system_status = 0;
                                switch($orderDetail['old_status']){
                                    case 20:
                                        $system_status = 0;
                                        break;
                                    case 30:
                                    case 70:
                                    case 80:
                                        $system_status = 2;
                                        break;
                                    case 40:
                                        $system_status = 3;
                                        break;
                                }
                                (new SystemOrder())->where('order_id', $order_id)->update([
                                    'system_status' =>  $system_status
                                ]);
                            }else{
                                $this->lifeToolsOrderModel->where('order_id', $order_id)->update([
                                    'refund_time'   =>  time()
                                ]);
                                (new SystemOrderService)->refundOrder('lifetools', $order_id);
                            }
                        }

                    //体育秒杀
                    if($orderDetail['type']=='course' || $orderDetail['type']=='stadium'){
                        $where_act = [
                            ['lt.tools_id', '=', $orderDetail['tools_id']], ['lt.status', '=', 1], ['lt.is_del', '=', 0], ['sku.ticket_id', '=', $orderDetail['ticket_id']],
                            ['la.start_time', '<', time()], ['la.end_time', '>', time()], ['la.type', '=', 'limited'], ['la.is_del', '=', 0]
                        ];
                        $ret = (new LifeToolsSportsSecondsKillTicketDetail())->getActDetail($where_act, 'la.start_time,la.end_time,act.*,sku.act_stock_num,sku.act_price,l.tools_id,sku.ticket_id,sku.day_stock_num');
                        if($ret && isset($ret['stock_type'])){
                            if($ret['stock_type']==1){//总库存
                                (new LifeToolsSportsSecondsKillTicketSku())->setInc(['act_id'=>$ret['id'],'ticket_id'=>$orderDetail['ticket_id']],'act_stock_num',$refundTicketNum);
                            }elseif($ret['stock_type']==2){//每日库存
                                (new LifeToolsSportsSecondsKillTicketSku())->setInc(['act_id'=>$ret['id'],'ticket_id'=>$orderDetail['ticket_id']],'day_stock_num',$refundTicketNum);
                            }
                        }
                    }
                    //景区秒杀
                    if($orderDetail['type']=='scenic'){
                        $where_act = [
                            ['lt.tools_id', '=', $orderDetail['tools_id']], ['lt.status', '=', 1], ['lt.is_del', '=', 0],
                            ['la.start_time', '<', time()], ['la.end_time', '>', time()], ['la.type', '=', 'limited'], ['la.is_del', '=', 0], ['sku.ticket_id', '=', $orderDetail['ticket_id']],['act.id','=',$orderDetail['act_id']]
                        ];
                        $ret = (new LifeScenicActivityDetail())->getActDetail($where_act, 'la.start_time,la.end_time,act.*,sku.act_stock_num,sku.act_price,l.tools_id,sku.ticket_id');
                        if($ret && $ret['id']){
                            (new LifeScenicLimitedSku())->setInc(['act_id'=>$ret['id'],'ticket_id'=>$orderDetail['ticket_id']],'act_stock_num',$refundTicketNum);
                        }
                    }
                        
                        //场馆分布图退款回滚库存
                        if($orderDetail['sku_type'] == 2){
                            $sku_ids = $this->LifeToolsOrderDetail->where('order_id', $order_id)->where('refund_status', 1)->column('sku_id');
                            (new LifeToolsTicketSku())->where('sku_id', 'in', $sku_ids)->update([
                                'stock_num' =>  1
                            ]);
                        }
                        
                        $where['status'] = 1;
                        if($orderDetail['order_status'] == 45){
                            $where['refund_status'] = 1;
                        }
                        $refund_num = $this->LifeToolsOrderDetail->getCount($where);
                        if ($refund_num <= 0) throw new \think\Exception('没有未核销的订单，请检查');
                        $canceledNum = $this->LifeToolsOrderDetail->where('order_id', $order_id)->where('status', 3)->count();
                        $refundNum = $this->LifeToolsOrderDetail->updateThis($where, ['status' => 3, 'last_time' => time(), 'refund_status' => 2]); //更新详情表
                        $this->lifeTools->setDec([['tools_id', '=', $orderDetail['tools_id']], ['sale_count', '>=', $refund_num]], 'sale_count', $refund_num); //减少销量
                        $this->LifeToolsTicket->setDec([['ticket_id', '=', $orderDetail['ticket_id']], ['sale_count', '>=', $refund_num]], 'sale_count', $refund_num); //减少销量
                        //多规格库存回滚
                        if($orderDetail['sku_id']){
                            (new LifeToolsTicketSku)->setInc(['sku_id'=>$orderDetail['sku_id']], 'stock_num', $refund_num);
                        }
                        $this->refund($order_id,$refundMoney);
                        break;
                    default:
                        # code...
                        break;
                }

                //票付通申请退款
                if($orderDetail['order_source'] == 'pft' 
                    && $orderDetail['third_order_no'] 
                    && $orderDetail['third_client_id'] 
                    && $orderDetail['third_refund_order_sn'] 
                    && $orderDetail['order_status'] == 45){
                    //同意
                    $pftService = new ScenicOpenPftService();
                    if($status == 50){
                        $pftService->pftOrderCancel($orderDetail, true, $refundNum ?? 0, $canceledNum ?? 0);
                    }else if($status == 20){
                        $pftService->pftOrderCancel($orderDetail, false, 0, 0);
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return $res;
    }

    /**
     * 退款
     * @param  [type] $order_id     订单ID
     * @return [type]               [description]
     */
    public function refund($order_id, $refundMoney = 0) {
        $order = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $order_id]);
        if($order['paid'] != 2) { //未支付
            return true;
        }
        if ($refundMoney > 0) { //已核销退款
            $refund_money = $refundMoney;
            $refund_all   = false;
            if ($refund_money >= $order['price']) {
                $refund_money = $order['price'];
                $refund_all   = true;
            }
        } else {
            $refund_money = $order['price'];
            $refund_all   = true;
            if ($order['refund_money'] < $order['price']) { //部分退款
                $refund_money = $order['refund_money'];
                $refund_all   = false;
            }
        }
        $alias_name = $order['type'] == 'scenic' ? '景区' : '体育健身';
        try {
            //退款顺序  商家优惠券-商家会员卡余额-商家会员卡赠送余额-平台优惠券-平台积分-平台余额-在线支付
            if($refund_all && $order['card_id'] > 0) {//全部退款 退商家优惠券
                (new MerchantCouponService())->refundReturnCoupon($order['card_id']);
            }
            if($refund_all && $order['coupon_id'] > 0) {//全部退款 退平台优惠券
                (new SystemCouponService())->refundReturnCoupon($order['coupon_id']);
            }
            //退商家会员卡余额
            $refund_merchant_card = $order['merchant_balance_pay'];
            $_money1 = 0;
            if ($refund_merchant_card > 0) {
                $_money1 = $refund_money >= $refund_merchant_card ? $refund_merchant_card : $refund_money;
                $result  = (new CardNewService())->addUserMoney($order['mer_id'], $order['uid'],$_money1, 0, 0, '', $alias_name . '订单退款,增加余额,订单编号' . $order['real_orderid']);
                if ($result['error_code']) {
                    throw new \think\Exception($result['msg']);
                }
                $refund_money -= $_money1;
            }
            if($refund_money <= 0) return true;

            //商家会员卡赠送余额
            $refund_merchant_card_give = ($order['merchant_balance_give'] + $order['merchant_balance_pay']) - $_money1;
            $_money2 = 0;
            if ($refund_merchant_card_give > 0) {
                $_money2 = $refund_money >= $refund_merchant_card_give ? $refund_merchant_card_give : $refund_money;
                $result  = (new CardNewService())->addUserMoney($order['mer_id'], $order['uid'], 0, $_money2, 0, '', $alias_name . '订单退款,增加余额,订单编号' . $order['real_orderid']);
                if ($result['error_code']) {
                    throw new \think\Exception($result['msg']);
                }
                $refund_money -= $_money2;
            }
            if($refund_money <= 0) return true;

            //退积分
            $refund_score = ($order['system_score_money'] + $order['merchant_balance_give'] + $order['merchant_balance_pay']) - ($_money1 + $_money2);
            $_money3 = 0;
            if ($refund_score > 0) {
                $score_refund = $order['system_score'];
                $_money3 = $refund_money >= $refund_score ? $refund_score : $refund_money;
                $result  = (new UserService())->addScore($order['uid'], $score_refund, $alias_name . '订单退款,增加积分,订单编号' . $order['real_orderid']);
                if ($result['error_code']) {
                    throw new \think\Exception($result['msg']);
                }
                $refund_money -= $_money3;
            }
            if($refund_money <= 0) return true;

            //平台余额
            $refund_system_balance = ($order['system_balance'] + $order['system_score_money'] + $order['merchant_balance_give'] + $order['merchant_balance_pay']) - ($_money1 + $_money2 + $_money3);
            $_money4 = 0;
            if ($refund_system_balance > 0) {
                $_money4 = $refund_money >= $refund_system_balance ? $refund_system_balance : $refund_money;
                $result  = (new UserService())->addMoney($order['uid'], $_money4, $alias_name . '订单退款,增加余额,订单编号' . $order['real_orderid']);
                if ($result['error_code']) {
                    throw new \think\Exception($result['msg']);
                }
                $refund_money -= $_money4;
            }
            if($refund_money <= 0) return true;

            //在线支付退款
            $refund_online_pay = ($order['pay_money'] + $order['system_balance'] + $order['system_score_money'] + $order['merchant_balance_give'] + $order['merchant_balance_pay']) - ($_money1 + $_money2 + $_money3 + $_money4);
            if ($refund_online_pay > 0) {
                $PayService = new PayService();
                $_money5 = $refund_money >= $refund_online_pay ? $refund_online_pay : $refund_money;
                if ($order['orderid']) {
                    $end_order = $PayService->getPayOrderInfo($order['orderid']);
                    if ($end_order['paid'] == '1' && $end_order['paid_money'] > 0) {
                        $end_money = ($end_order['paid_money'] - $end_order['refund_money']) / 100;
                        if ($end_money > 0) {
                            $end_refund_money = $end_money >= $_money5 ? $_money5 : $end_money;
                            $PayService->refund($order['orderid'], $end_refund_money);
                        }
                    }
                }
                $refund_money -= $_money5;
            }

            //线下支付退款
            $refund_offline_pay = ($order['offline_pay_money'] + $order['system_balance'] + $order['system_score_money'] + $order['merchant_balance_give'] + $order['merchant_balance_pay']) - ($_money1 + $_money2 + $_money3 + $_money4);
            if ($refund_offline_pay > 0) {
                $_money6 = $refund_money >= $refund_offline_pay ? $refund_offline_pay : $refund_money;
                $refund_money -= $_money6;
            }
            if ($refund_money <= 0) {
                return true;
            }
            //票付通推送订单，不参与结算
            if($order['order_source'] == 'pft' && $order['third_order_no'] && $order['third_client_id']){
                return true;
            }
            throw new \think\Exception('退款金额计算溢出了，请检查');
        } catch(\Exception $e) {
            throw new \think\Exception($e->getMessage(), 1005);
        }
    }

    /**
     * 订单完成增加商家余额
     * @param $orderId
     */
    public function merchantAddMoney($orderId)
    {
        try {
            $nowOrder = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $orderId]);
            if (!$nowOrder) {
                throw new \Exception('订单不存在');
            }
            //订单详情信息
            $order_detail = $this->LifeToolsOrderDetail->getSome(['order_id' => $orderId, 'status' => 2]);
            if (empty($order_detail)) {
                return true;
            }
            $order_detail = $order_detail->toArray();
            $money_real = $nowOrder['price'];
            //当前商家应该入账的金额 = 实际金额（除去优惠的金额） - 积分的钱 - 商家会员卡赠送余额付了多少钱 - 商家会员卡余额付了多少钱 - 运费 - 已成功退款金额
            $bill_money = $money_real - $nowOrder['system_score_money'] - $nowOrder['merchant_balance_pay'] - $nowOrder['merchant_balance_give'] - $nowOrder['refund_money'];

            $money_system_take = $bill_money;//平台抽成基数
            if ($nowOrder['coupon_id'] > 0) {//使用平台优惠券商家承担的金额
                $systemCouponService = new SystemCouponService();
                $coupon_info = $systemCouponService->getMoney($nowOrder['coupon_id'], $nowOrder['coupon_price']);
                if (!empty($coupon_info) && isset($coupon_info['merchant_money'])) {
                    $bill_money -= $coupon_info['merchant_money'];
                }
            }
            $orderInfo = [];
            $orderInfo['pay_order_id'] = [$nowOrder['orderid']];//支付单号
            $orderInfo['total_money'] = $nowOrder['total_price'];//当前订单总金额
            $orderInfo['bill_money'] = $bill_money;
            $orderInfo['balance_pay'] = $nowOrder['system_balance'];//平台余额支付金额
            $orderInfo['merchant_balance'] = $nowOrder['merchant_balance_pay'];//商家会员卡支付金额
            $orderInfo['card_give_money'] = $nowOrder['merchant_balance_give'];//商家会员卡赠送支付金额
            $orderInfo['payment_money'] = $nowOrder['pay_money'];//在线支付金额（不包含自有支付）
            $orderInfo['score_deducte'] = $nowOrder['system_score_money'];//积分支付金额
            $orderInfo['order_from'] = 1;
            $orderInfo['order_type'] = 'lifetools';
            $orderInfo['num'] = count($order_detail);//数量
            $orderInfo['store_id'] = 0;
            $orderInfo['mer_id'] = $nowOrder['mer_id'];
            $orderInfo['order_id'] = $nowOrder['real_orderid'];
            $orderInfo['group_id'] = '0';
            $orderInfo['real_orderid'] = $nowOrder['real_orderid'];//订单编号
            $orderInfo['union_mer_id'] = '0';//商家联盟id
            $orderInfo['uid'] = $nowOrder['uid'];//用户ID
            $orderInfo['desc'] = '用户在 ' . $nowOrder['ticket_title'] . ' 中消费' . $nowOrder['total_price'] . '元记入收入';
            $orderInfo['is_own'] = (new PayOrderInfo())->where([
                    'business' => 'lifetools',
                    'business_order_id' => $orderId,
                    'orderid' => $nowOrder['orderid']
                ])->value('is_own') ?? 0;//自有支付类型
            $orderInfo['own_pay_money'] = '0';//自有支付在线支付金额
            $orderInfo['pay_for_system'] = 0;
            $orderInfo['pay_for_store'] = '0';
            $orderInfo['score_used_count'] = '';
            $orderInfo['score_discount_type'] = '';
            $orderInfo['money_system_take'] = $money_system_take;
            $orderInfo['discount_detail'] = '';
            $orderInfo['system_coupon_plat_money'] = '';
            $orderInfo['system_coupon_merchant_money'] = '';
            $merchantMoneyListService = new MerchantMoneyListService();
            $merchantMoneyListService->addMoney($orderInfo);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return true;
    }


    /**
     * 获取条形码
     */
    private function getBarCode($code)
    {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();

        $base64Image = base64_encode($generator->getBarcode($code, $generator::TYPE_CODE_128, 1, 1));

        $path = request()->server('DOCUMENT_ROOT').'/runtime/life_tools/Barcode/';
        
        if(!is_dir($path)){
            mkdir($path, 0777, true);
        }
        base64_to_img($path, $code, $base64Image, 'png');
        return cfg('site_url').'/runtime/life_tools/Barcode/'.$code.'.png';
    }

    /**
     * 获取二维码
     */
    private function getQrCode($code)
    {
        $date = date('Y-m-d');
        $time = date('Hi');
        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "9";
        $dir = '../../runtime/qrcode/employee/'.$date. '/' .$time;
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';
        $qrcode->png($code, $filename_url, $errorLevel, $size);
        $QR = 'runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';      //已经生成的原始二维码图片文件
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;
    }

    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function exportToolsOrder($param, $systemUser = [], $merchantUser = [])
    {
        $title = $param['type'] == 3 ? '景区订单' : '体育健身订单';
        $param['etype'] = $param['type'];
        $param['type'] = 'pc';
        $param['service_path'] = '\app\life_tools\model\service\LifeToolsOrderService';
        $param['service_name'] = 'toolsOrderExportPhpSpreadsheet';
        $param['rand_number']  = time();
        $param['system_user']['area_id']  = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        return $this->toolsOrderExportPhpSpreadsheet($param);
        // $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        // return $result;
    }

    /**
     * 导出(Spreadsheet方法)
     * @param $param
     */
    public function toolsOrderExportPhpSpreadsheet($param)
    {
        $orderList   = $this->getList($param, 1)['data'];
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '订单号');
        $worksheet->setCellValueByColumnAndRow(2, 1, '订单类型');
        $worksheet->setCellValueByColumnAndRow(3, 1, '订单名称');
        $worksheet->setCellValueByColumnAndRow(4, 1, '用户昵称');
        $worksheet->setCellValueByColumnAndRow(5, 1, '用户手机号');
        $worksheet->setCellValueByColumnAndRow(6, 1, '数量');
        $worksheet->setCellValueByColumnAndRow(7, 1, '总价');
        $worksheet->setCellValueByColumnAndRow(8, 1, '订单状态');
        $worksheet->setCellValueByColumnAndRow(9, 1, '下单时间');
        $worksheet->setCellValueByColumnAndRow(10, 1, '核销时间');
        $worksheet->setCellValueByColumnAndRow(11, 1, '报名信息');
        //设置单元格样式
        $worksheet->getStyle('A1:K1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:K')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(42);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(50);
        $len = count($orderList);
        $j   = 0;
        $row = 0;
        $i   = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['real_orderid']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $orderList[$key]['type']);
                $worksheet->setCellValueByColumnAndRow(3, $j, $orderList[$key]['title']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $orderList[$key]['nickname']);
                $worksheet->setCellValueByColumnAndRow(5, $j, $orderList[$key]['phone']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['num']);
                $worksheet->setCellValueByColumnAndRow(7, $j, '¥' . $orderList[$key]['total_price']);
                $worksheet->setCellValueByColumnAndRow(8, $j, $orderList[$key]['order_status_val']);
                $worksheet->setCellValueByColumnAndRow(9, $j, $orderList[$key]['add_time']);
                $worksheet->setCellValueByColumnAndRow(10, $j, $orderList[$key]['verify_time']);

                // 自定义报名信息
                $customForm = (new LifeToolsOrderCustomFormService())->getSome(['order_id'=>$val['order_id']]);
                $customForm = (new LifeToolsOrderCustomFormService())->formatData($customForm);
                $customFormStr = '';
                foreach($customForm as $_custom){
                    foreach($_custom['content'] as $_customContent){
                        if($_customContent['type']=='image'){
                            $_customContent['show_value'] = $_customContent['show_value']?implode(';',$_customContent['show_value']):'';
                        }
                        $customFormStr .= $_customContent['title']."：".(isset($_customContent['show_value'])&& $_customContent['show_value'] ? $_customContent['show_value'] : '-').'，';
                    }
                    $customFormStr = trim($customFormStr, '，');
                    $customFormStr .= '；';
                }
                $worksheet->setCellValueByColumnAndRow(11, $j, $customFormStr);
                $i++;
            }
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_JUSTIFY
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:K' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];

    }

    /**************************************以下为对接支付中心的方法*********************************/

    /**
     * 获取订单信息
     * $order_id  订单表的主键ID
     */
    public function getOrderPayInfo($order_id) {
        $orderInfo = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $order_id]);
        if (empty($orderInfo)) throw new \think\Exception("没有找到订单");
        $orderInfo['timeout']      = ($orderInfo['add_time'] + 30 * 60) > time() ? 0 : 1; //1=已过期,0=未过期
        $orderInfo['time_surplus'] = $orderInfo['timeout'] == 0 ? ($orderInfo['add_time'] + 30 * 60) - time() : 0; //有效时间
        if ($orderInfo['paid'] != 2 && $orderInfo['timeout'] == 1) { //超时未支付
            $this->changeOrderStatus($order_id, 60, '超时未支付（支付中心）');
        }
        $return = [
            'paid'        => $orderInfo['paid'] == 2 ? 1 : 0,
            'is_cancel'   => $orderInfo['timeout'],
            'mer_id'      => $orderInfo['mer_id'],
            'city_id'     => $orderInfo['city_id'],
            'store_id'    => 0,
            'uid'         => $orderInfo['uid'],
            'order_no'    => $orderInfo['orderid'],
            'title'       => $orderInfo['type'] == 'scenic' ? '景区订单' : '体育健身订单',
            'order_money' => get_format_number($orderInfo['price'] - $orderInfo['system_balance'] - $orderInfo['merchant_balance_pay'] - $orderInfo['merchant_balance_give'] - $orderInfo['system_score_money']),
            'time_remaining'        => $orderInfo['time_surplus'],
            'merchant_balance_open' => true,
            'business_order_sn' => $orderInfo['real_orderid'],
        ];
        return $return;
    }

    /**
     * 获取支付结果页地址
     * @param  [type]  $order_id  [description]
     * @param  integer $is_cancel 1=已取消  0=未取消
     */
    public function getPayResultUrl($order_id, $is_cancel = 0)
    {
        $orderInfo = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $order_id]);
        $redirect_url = get_base_url('pages/lifeTools/order/orderDetail?order_id=' . $order_id . '&type=' . $orderInfo['type']);
        $redirect_home_url = get_base_url('pages/plat_menu/index');
        if ((string)$orderInfo['activity_type'] == 'sports_activity') {
            $leader_order_id = $this->LifeToolsOrderBindSportsActivity->where(['order_id' => $order_id])->value('leader_order_id') ?? 0;
            $redirect_url = get_base_url('pages/lifeTools/sports/activity/detail?order_id=' . ($leader_order_id > 0 ? $leader_order_id : $order_id));
            $redirect_home_url = get_base_url('pages/lifeTools/sports/activity/list');
        }

        if ($orderInfo['is_group'] == '1') { // 团体票
            $redirect_url = get_base_url('pages/lifeTools/scenic/groupTicket/order/paySuccess?order_id=' . $order_id);
        }
        return ['redirect_url' => $redirect_url, 'direct' => 0, 'redirect_home_url'=>$redirect_home_url]; //前端提供跳转地址
    }
    
    /**
     * 支付成功逻辑
     * $order_id  订单表的主键ID
     */
    public function afterPay($order_id, $pay_info = []) {
        $orderInfo = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $order_id]);
        if(empty($orderInfo)) throw new \think\Exception("没有找到订单");
        $alias_name = $orderInfo['type'] == 'scenic' ? '景区' : '体育健身';
        $order_record = [
            'current_score_use'             => 0,
            'current_score_deducte'         => 0,
            'current_system_balance'        => 0,
            'current_merchant_balance'      => 0,
            'current_merchant_give_balance' => 0,
            'current_qiye_balance'          => 0,
            'money_online_pay'              => 0
        ];
        $UserService = new UserService();
        if ($orderInfo['uid']) {
            $now_user = $UserService->getUser($orderInfo['uid']);
        }
        $MerchantCardService = new MerchantCardService();
        $CardNewService      = new CardNewService();
        if ($orderInfo['coupon_id']) { //平台优惠券
            (new SystemCouponService())->useCoupon($orderInfo['coupon_id'], $orderInfo['order_id'], 'lifetools', $orderInfo['mer_id'], $orderInfo['uid']);
        }
        if ($orderInfo['card_id']) { //商家优惠券
            (new MerchantCouponService())->useCoupon($orderInfo['card_id'], $orderInfo['order_id'], 'lifetools', $orderInfo['mer_id'], $orderInfo['uid']);
        }
        $orderInfo['orderid'] = $pay_info['paid_orderid'];
        //扣除使用商家会员卡余额
        if ($pay_info['current_merchant_balance'] > 0) {
            $current_card = $MerchantCardService->getUserCard($orderInfo['uid'], $orderInfo['mer_id']);
            if ($current_card && $current_card['card_money'] < $pay_info['current_merchant_balance']) {
                throw new \think\Exception("商家会员卡余额不足！");
            }
            $desc = "购买 " . $alias_name . "商品 扣除会员卡余额，订单编号" . $orderInfo['orderid'];
            $use_result = $CardNewService->useMoney($orderInfo['mer_id'], $orderInfo['uid'], $pay_info['current_merchant_balance'], $desc);
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['error_code']);
            }
            $order_record['current_merchant_balance'] = $pay_info['current_merchant_balance'];
        }
        //扣除使用商家会员卡赠送余额
        if ($pay_info['current_merchant_give_balance'] > 0) {
            $current_card = $MerchantCardService->getUserCard($orderInfo['uid'], $orderInfo['mer_id']);
            if ($current_card && $current_card['card_money_give'] < $pay_info['current_merchant_give_balance']) {
                throw new \think\Exception("商家会员卡赠送余额不足！");
            }
            $desc = "购买 " . $alias_name . "商品 扣除会员卡赠送余额，订单编号" . $orderInfo['orderid'];
            $use_result = $CardNewService->useGiveMoney($orderInfo['mer_id'], $orderInfo['uid'], $pay_info['current_merchant_give_balance'], $desc);
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['error_code']);
            }
            $order_record['current_merchant_give_balance'] = $pay_info['current_merchant_give_balance'];
        }
        //扣除使用积分
        if ($pay_info['current_score_use'] > 0) {
            if ($now_user['score_count'] < $pay_info['current_score_use']) {
                throw new \think\Exception("用户积分不足！");
            }
            $desc = "购买" . $alias_name . "商品 ，扣除" . cfg('score_name') . " ，订单编号" . $orderInfo['orderid'];
            $use_result = $UserService->userScore($now_user['uid'], $pay_info['current_score_use'], $desc);
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['error_code']);
            }
            $order_record['current_score_use']     = $pay_info['current_score_use'];
            $order_record['current_score_deducte'] = $pay_info['current_score_deducte'];
        }
        //扣除使用平台余额
        if ($pay_info['current_system_balance'] > 0) {
            if ($now_user['now_money'] < $pay_info['current_system_balance']) {
                throw new \think\Exception("用户余额不足！");
            }
            $desc = "购买" . $alias_name . "商品 ，扣除余额 ，订单编号" . $orderInfo['orderid'];
            $use_result = $UserService->userMoney($now_user['uid'], $pay_info['current_system_balance'], $desc);
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['error_code']);
            }
            $order_record['current_system_balance'] = $pay_info['current_system_balance'];
        }
        //在线支付金额
        if ($pay_info['paid_money'] > 0) {
            $order_record['money_online_pay'] = $pay_info['paid_money'];
        }
        $order_status = 20;
        $status_note  = '订单支付成功';
        $source = $pay_info['source'] ?? request()->agent;
        $source_extra = $pay_info['source_extra'] ?? [];
        $source_extra = json_encode($source_extra);
        //现金支付
        if(!$pay_info['paid_type'] && ($order_record['current_merchant_balance'] > 0 || $order_record['current_system_balance'] > 0 || $order_record['current_merchant_give_balance'] > 0)){
            $pay_info['paid_type'] = 'balance';
        }
        $save_data    = [
            'system_score'          => $order_record['current_score_use'],
            'system_score_money'    => $order_record['current_score_deducte'],
            'system_balance'        => $order_record['current_system_balance'],
            'merchant_balance_pay'  => $order_record['current_merchant_balance'],
            'merchant_balance_give' => $order_record['current_merchant_give_balance'],
            'pay_type'     => $pay_info['paid_type'],
            'pay_money'    => $order_record['money_online_pay'],
            'offline_pay_money'    => $pay_info['offline_pay_money'] ?? 0,
            'orderid'      => $pay_info['paid_orderid'],
            'real_orderid' => $pay_info['paid_orderid'],
            'paid'         => 2,
            'pay_time'     => time(),
            'last_time'    => time(),
            'source'       =>   $source,
            'source_extra' =>   $source_extra,
        ];
        $this->lifeToolsOrderModel->updateThis(['order_id' => $orderInfo['order_id']], $save_data);

        if($orderInfo['is_group']){// 团体票
         
            // 获得团体票配置信息
            $setting = (new LifeToolsGroupSettingService())->getDataDetail(['mer_id' =>$orderInfo['mer_id']]);
            if($setting['expiration_time'] > 0){
                // 更新提交审核截止时间
                (new LifeToolsGroupOrderService())->updateThis(['order_id' =>$orderInfo['order_id']],['submit_audit_time'=>time()+$setting['expiration_time']*60]);
            }
        }


         //三级分销
         if($orderInfo['type'] == 'scenic'){
            $setting = $this->lifeToolsDistributionSettingModel->where('mer_id', $orderInfo['mer_id'])->find();
            $userDistribution = $this->lifeToolsDistributionUserModel->where('uid', $now_user['uid'])->where('is_del', 0)->find();
            $commission_level_1 = $commission_level_2 = 0;
            $time = time();
            //开启分销 && 审核通过 && 有上级用户
            if($setting && $setting->status_distribution == 1 && $userDistribution && $userDistribution->pid != 0){
                //上一级
                $userLevel1 = $this->lifeToolsDistributionUserModel->where('user_id', $userDistribution->pid)->where('is_del', 0)->find();
                $ticketDistribution = $this->lifeToolsTicketDistributionModel->where('ticket_id', $orderInfo['ticket_id'])->find();
                if($userLevel1){
                    $condition = [];
                    $condition[] = ['uid', '=', $userLevel1->uid];
                    $condition[] = ['audit_status', '=', 1];
                    $condition[] = ['mer_id', '=', $orderInfo['mer_id']];
                    $condition[] = ['is_del', '=', 0];
                    $userBindMer1 = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->find();
                }
                //存在上级用户 && 商家审核通过 && 配置门票佣金金额
                if($userLevel1 && $ticketDistribution && !empty($userBindMer1) && $ticketDistribution->secondary_commission){
                    $num = $orderInfo['num'];
                    $commission_level_1 = formatNumber($ticketDistribution->secondary_commission * $num);
                    //分销清单
                    $lifeToolsDistributionOrder = $this->lifeToolsDistributionOrderModel;
                    $distributionOrderLevel1 = [];
                    $distributionOrderLevel1['mer_id'] = $orderInfo['mer_id'];
                    $distributionOrderLevel1['order_id'] = $order_id;
                    $distributionOrderLevel1['create_time'] = $time;
                    $distributionOrderLevel1['user_id'] = $userLevel1->user_id;
                    $distributionOrderLevel1['from_user_id'] = $userDistribution->user_id;
                    $distributionOrderLevel1['commission_level_1'] = $commission_level_1;
                    $distributionOrderLevel1['commission_type'] = 1;
                    $distributionOrderLevel1['pid'] = $userLevel1->user_id;
                    $distributionOrderLevel1['is_cert'] = $userDistribution->is_cert;
                    $lifeToolsDistributionOrder->insert($distributionOrderLevel1);

                    //分销员绑定商家
                    // $userBindMer1->commission += $commission_level_1;
                    $userBindMer1->commission = $lifeToolsDistributionOrder->getCommission($userLevel1->user_id, $orderInfo['mer_id'], 1);
                    $userBindMer1->update_time = $time;
                    $userBindMer1->save();
                    
                    //上级分销员
                    // $userLevel1->commission_level_1 += $commission_level_1;
                    $userLevel1->commission_level_1 = $lifeToolsDistributionOrder->getCommission($userLevel1->user_id, 0, 1);
                    $userLevel1->update_time = $time;
                    $userLevel1->save();

                    //上两级
                    $userLevel2 = $this->lifeToolsDistributionUserModel->where('user_id', $userLevel1->pid)->where('is_del', 0)->find();
                    if($userLevel2){
                        $condition = [];
                        $condition[] = ['uid', '=', $userLevel2->uid];
                        $condition[] = ['audit_status', '=', 1];
                        $condition[] = ['mer_id', '=', $orderInfo['mer_id']];
                        $condition[] = ['is_del', '=', 0];
                        $userBindMer2 = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->find();
                    }
                    //开启奖励 && 存在上两级用户 && 设置门票佣金金额
                    if($setting->status_award == 1 && !empty($userLevel2) && !empty($userBindMer2) && $ticketDistribution->third_commission){
                        $commission_level_2 = formatNumber($ticketDistribution->third_commission * $num);
                        //分销清单
                        $distributionOrderLevel2['mer_id'] = $orderInfo['mer_id'];
                        $distributionOrderLevel2['order_id'] = $order_id;
                        $distributionOrderLevel2['create_time'] = $time;
                        $distributionOrderLevel2['commission_level_2'] = $commission_level_2;
                        $distributionOrderLevel2['user_id'] = $userLevel2->user_id;
                        $distributionOrderLevel2['from_user_id'] = $userDistribution->user_id;
                        $distributionOrderLevel2['commission_type'] = 2;
                        $distributionOrderLevel2['pid'] = $userLevel1->user_id;
                        $distributionOrderLevel2['is_cert'] = $userDistribution->is_cert;
                        $lifeToolsDistributionOrder->insert($distributionOrderLevel2);

                        //分销员绑定商家
                        // $userBindMer2->invit_money += $commission_level_2;
                        $userBindMer2->invit_money = $lifeToolsDistributionOrder->getCommission($userLevel2->user_id, $orderInfo['mer_id'], 2);
                        $userBindMer2->update_time = $time;
                        $userBindMer2->save();
                        
                        //上级分销员
                        // $userLevel2->commission_level_2 += $commission_level_2;
                        $userLevel2->commission_level_2 = $lifeToolsDistributionOrder->getCommission($userLevel2->user_id, 0, 2);
                        $userLevel2->update_time = $time;
                        $userLevel2->save();

                    }
                }

            }

            //业务员下单记录
            if($userDistribution){
                $distributionLog = $this->lifeToolsDistributionLogModel;
                $distributionLog->mer_id = $orderInfo['mer_id'];
                $distributionLog->user_id = $userDistribution->user_id;
                $distributionLog->uid = $now_user['uid'];
                $distributionLog->order_id = $order_id;
                $distributionLog->commission_level_1 = $commission_level_1;
                $distributionLog->commission_level_2 = $commission_level_2;
                $distributionLog->create_time = $time;
                $distributionLog->save();
            }

            
        }

        $this->changeOrderStatus($orderInfo['order_id'], $order_status, $status_note);
         //增加消息通知
        (new UserNoticeService())->addNotice([
            'type'=>0,
            'business'=>'scenic',
            'order_id'=>$order_id,
            'tools_id'=>$orderInfo['tools_id'],
            'uid'=>$orderInfo['uid'],
            'title'=>'商品下单成功',
            'content'=>'下单成功，查看订单详情'
        ]);
    }


    /**
     * 核销列表
     */
    public function verifyList($params)
    {
        $condition = [];
        $condition[] = ['o.order_status', 'between', [30, 40]];
        if(!empty($params['mer_id'])){
            $condition[] = ['o.mer_id', '=', $params['mer_id']];
        }
        if(!empty($params['staff_id'])){
            $condition[] = ['od.staff_id', '=', $params['staff_id']];
        }
        if(!empty($params['order_type'])){
            if($params['order_type'] == 'all'){
                $condition[] = ['a.type', 'in', ['stadium', 'course']];
            }else if($params['order_type'] == 'sports_activity'){
                $condition[] = ['o.activity_type', '=', 'sports_activity'];
            }else{
                $condition[] = ['a.type', '=', $params['order_type']];
                $condition[] = ['o.activity_type', '<>', 'sports_activity'];
            }
        }
        if(!empty($params['keywords'])){
            switch ($params['search_by']) {
                case 1: //订单号
                    $condition[] = ['o.orderid', 'like', "%{$params['keywords']}%"];
                    break;
                case 2: //活动名称
                    $condition[] = ['t.title', 'like', "%{$params['keywords']}%"];
                    break;
                case 3: //手机号
                    $condition[] = ['o.phone', 'like', "%{$params['keywords']}%"];
                    break;
                case 4: //景区名称
                    $condition[] = ['a.title', 'like', "%{$params['keywords']}%"];
                    break; 
            } 
        }

        if(!empty($params['start_date']) && !empty($params['end_date'])){
            switch ($params['date_by']) {
                case 1: //下单日期
                    $condition[] = ['o.add_time', 'BETWEEN', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
                    break;
                case 2: //核销日期
                    $condition[] = ['o.verify_time', 'BETWEEN', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
                    break;
            }
        }

        $field = 'o.*,a.type,t.title';
        return $this->lifeToolsOrderModel->getVerifyList($condition, $params['page_size'], $field);
    }


    /**
     * 店员端自主购票-订单提交数据
     */
    public function staffConfirm($params)
    {
        if(empty($params['ticket_id'])){
            throw new \think\Exception("请设置价格日历！");
        }
        if(empty($params['num'])){
            throw new \think\Exception("num参数不能为空！");
        }
        if(empty($params['select_id'])){
            throw new \think\Exception("请设置价格日历！");
        }

        //游玩日期
        $saleDay = (new LifeToolsTicketSaleDay())->getDetail(['pigcms_id'=>$params['select_id']]);
        $select_date = $saleDay['day'] ?? date('Y-m-d');
         
        $base_info = $this->LifeToolsTicket->getDetail($params['ticket_id']);
        $date_time = date('H:i:s', time());
        if($base_info['start_time'] > $date_time){
            // throw new \think\Exception('当前时间未开园');
        }
        if($base_info['end_time'] < $date_time && $select_date == date('Y-m-d')){
            throw new \think\Exception('当前时间已闭园');
        }

        $base_info['date'] = $select_date;
        $sumWhere = [
            ['ticket_id', '=', $params['ticket_id']],
            ['ticket_time', '=', $select_date],
            ['order_status', 'not in', [50, 60]],
            ['is_give', '=', 0]
        ];
        $sale_count = ($base_info['stock_type'] == 1) ? $base_info['sale_count'] : $this->lifeToolsOrderModel->where($sumWhere)->sum('num');
        $base_info['limit_num'] = $base_info['stock_num'] - $sale_count;
        $base_info['limit_num']  <= 0 && $base_info['limit_num'] = 0;

        if($params['num'] > $base_info['limit_num']){
            throw new \think\Exception("库存不足，剩余{$base_info['limit_num']}件");
        }
        $base_info['num'] = max(1, $params['num']);
        $base_info['num'] = min($base_info['limit_num'], $params['num']);

        $base_info['price'] = $saleDay['price'] ?? $base_info['price'];
        $base_info['total_price'] = formatNumber($params['num'] * $base_info['price']);

        return $base_info;
    }

    /**
     * 店员端自主购票-订单确认
     */
    public function staffSaveOrder($params)
    {
        if(empty($params['name']) || empty($params['phone'])){
            throw new \think\Exception("购票人信息不能为空！");
        }
        if(empty($params['ticket_id'])){
            throw new \think\Exception("当前时间未开园！");
        }
        if(empty($params['num'])){
            throw new \think\Exception("num参数不能为空！");
        }
        if(empty($params['select_id'])){
            throw new \think\Exception("select_id参数不能为空！");
        }
 
        $time = time();
        $condition = [];
        $condition[] = ['phone', '=', $params['phone']];
        $userModel = new User();
        $user = $userModel->where($condition)->find();
        if(!$user){
            $user = $userModel;
            $user->phone = $params['phone'];
            $user->nickname = $params['name'];
            $user->truename = $params['name'];
            $user->status = 1;
            $user->add_time = $time;
            $user->save();
        }

        $params['sys_hadpull_id'] = -1;
        $params['mer_hadpull_id'] = -1;
        $params['member_id'] = 0;

        $confirm = $this->confirm($params, $user->uid);
 
        if ($params['pay_price'] != $confirm['base_info']['pay_price']) {
            throw new \think\Exception('支付价格有误');
        }
        if ($confirm['base_info']['limit_num'] < $params['num']) {
            throw new \think\Exception('库存不足');
        }
       
        $res = $this->saveOrder($confirm, $user->toArray());


        return $res;
    }

    /**
     * 核销时验证门票绑定的店员
     */
    public function checkOrderStaff($ticketId, $staffId)
    {
        $ticket = (new LifeToolsTicketService())->getOne(['ticket_id'=>$ticketId]);
        if(empty($ticket)){
            throw new \think\Exception(L_("门票信息错误"), 1003);
        }

        if($ticket['staff_ids'] == ''){// 不绑定店员
            return true;
        }

        // 门票绑定的店员
        $staffIds = explode(',', trim($ticket['staff_ids'], ','));

        if(!in_array($staffId,$staffIds)){
            throw new \think\Exception(L_("核销失败，无权限核销此门票"));
        }

        return true;
    }


    public function goPay($params)
    {
        $order = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $params['order_id']]);
        if(!$order){
            throw new \think\Exception('订单不存在！');
        }

        if($order['paid'] == 2){
            throw new \think\Exception('订单已支付！');
        }

        if($order['order_status'] == 60){
            throw new \think\Exception('订单已过期！');
        }

        if($order['order_status'] != 10){
            throw new \think\Exception('订单状态不支持支付！');
        }

        $ticket = $this->LifeToolsTicket->getDetail($order['ticket_id']);
        $date_time = date('H:i:s', time());
        if($ticket['start_time'] > $date_time){
            throw new \think\Exception('当前时间未开园');
        }
        if($ticket['end_time'] < $date_time){
            throw new \think\Exception('当前时间已闭园');
        }

        $payPrice = $order['total_price'];
        
        
        if($params['getPrice'] < $payPrice){
            // throw new \think\Exception('付款金额有误！');
        }

        $store_id = $params['staffUser']['store_id'];
        // 店铺信息
        $store = (new MerchantStoreService())->getStoreByStoreId($store_id);

        if(!in_array($params['pay_type'], ['offline', 'online'])){
            throw new \think\Exception('支付类型有误！');
        }

        //在线支付
        if($params['pay_type'] == 'online'){

            if(empty($params['pay_code'])){
                throw new \think\Exception('付款码不能为空！');
            }

            // 调用在线支付
            $payService = new PayService($store['city_id'], $store['mer_id'], $store['store_id']);
            try {
                $payInfo = $payService->scanPay($params['pay_code'], $payPrice);
                $payInfo['paid_type'] = $payInfo['pay_type'];
                $payInfo['paid_money'] = $payPrice;
                $payInfo['paid_orderid'] = $payInfo['order_no'];
                $this->afterPay($params['order_id'], $payInfo);
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage(), $e->getCode());
            }

        }else{
            
            $payInfo['paid_type'] = $params['pay_type'];
            $payInfo['current_merchant_balance'] = 0;
            $payInfo['current_merchant_give_balance'] = 0;
            $payInfo['current_score_use'] = 0;
            $payInfo['current_system_balance'] = 0;
            $payInfo['paid_money'] = 0;
            $payInfo['offline_pay_money'] = $payPrice;
            $payInfo['paid_orderid'] = $order['orderid'];
 

        }
        $payInfo['source'] = 'window';
        $payInfo['source_extra'] = $params;
        $this->afterPay($params['order_id'], $payInfo);
        fdump_sql($params, 'life_tools_staff_buy_ticket');
       
        //返回二维码
        $orderDetail = $this->LifeToolsOrderDetail->where('order_id', $order['order_id'])->select();
        $printData = [];
        require_once '../extend/phpqrcode/phpqrcode.php';
        foreach($orderDetail as $key => $val){
            $ticket = $this->LifeToolsTicket->getDetail($order['ticket_id']);
            $tmp = [];
            $tmp['code'] = $val['code'];
            $tmp['pay_code_img']  = $this->getQrCode($val['code']);
            $tmp['start_time'] = $ticket['start_time'];
            $printData[] = $tmp;
        }
        return $printData;
    }

    public function getAtatisticsInfo($params)
    {
        if(empty($params['type']) || !in_array($params['type'], ['scenic','stadium','course','sports'])){
            throw new \think\Exception('type参数不正确！');
        }
        $data = [
            'count_order'=>0,//订单总数
            'price_order'=>0,//订单总金额
            'price_back_order'=>0,//退款总金额
            'cash_order'=>0,//现金总金额
            'pft_order_price'=>0,//票付通订单总金额
            'today_count_order'=>0,//今日订单总数
            'today_price_order'=>0,//今日订单总金额
            'today_price_back_order'=>0,//今日退款总金额
            'today_cash_order'=>0,//今日现金总金额
            'today_pft_order_price'=>0,//今日票付通订单总金额
        ];
        $where = [];
        if($params['type'] == 'sports'){
            $where[] = ['a.type', 'in', ['stadium', 'course']];
        }else{
            $where[] = ['a.type', '=', $params['type']];
        }
        $result = $this->lifeToolsOrderModel->getList($where,[],'o.price,o.refund_money,o.order_status,o.pay_type,o.add_time,o.is_group,o.pay_time,o.total_price');
        $result = $result['data'];
        $allCount = count($result);
        $backCount = 0;
        $todayTime = strtotime(date('Y-m-d').' 00:00:00');
        foreach ($result as $v){
            if($v['order_status'] == 50 || $v['order_status'] == 10 || $v['order_status'] == 60){//退款订单和待付款订单、已取消订单
                $backCount += 1;
            }else{
                if($v['pay_time'] >= $todayTime){
                    $data['today_count_order'] += 1;
                }
            }
//            if(in_array($v['order_status'],[20, 30, 40, 45, 50, 70, 80])){
//                $data['price_order'] += $v['price']-$v['refund_money'];
//                $data['price_back_order'] += $v['refund_money'];
//                $data['cash_order'] = $v['pay_type'] == 'offline' ? $data['cash_order'] + $v['price']-$v['refund_money'] : $data['cash_order'];
//                if($v['pay_time'] >= $todayTime){
//                    $data['today_price_order'] += $v['price']-$v['refund_money'];
//                    $data['today_price_back_order'] += $v['refund_money'];
//                    $data['today_cash_order'] = $v['pay_type'] == 'offline' ? $data['cash_order'] + $v['price']-$v['refund_money'] : $data['cash_order'];
//                }
//            }
        }
        $dataList = $this->lifeToolsOrderModel->getListDetail($where,[],'t.price,o.total_price,t.status,o.pay_type,o.add_time,o.is_group,o.pay_time,o.order_status');
        foreach ($dataList['data'] as $val){
            if($val['status'] == 3){//退款订单核销码
                $data['price_back_order'] += $val['price'];
                if($val['pay_time'] >= $todayTime){
                    $data['today_price_back_order'] += $val['price'];
                }
            }elseif($val['order_status'] != 10 && $val['order_status'] != 60){//非待支付订单
                $data['price_order'] += $val['price'];
                if($val['pay_type'] == 'offline'){//现金支付
                    $data['cash_order'] += $val['price'];
                }
                if($val['pay_time'] >= $todayTime){
                    $data['today_price_order'] += $val['price'];
                }
                if($val['pay_time'] >= $todayTime && $val['pay_type'] == 'offline'){//现金支付
                    $data['today_cash_order'] += $val['price'];
                }

            }
        }
        $data['count_order'] = $allCount - $backCount;

        //查询景区次卡订单信息
        $cardOrderInfo = $this->LifeToolsCardOrderModel->getExportData(3, $params['type']);//全部
        if(isset($cardOrderInfo[0])){
            $data['count_order'] += $cardOrderInfo[0]['total_order'];
            $data['price_order'] += $cardOrderInfo[0]['total_money'];
            $data['price_back_order'] += $cardOrderInfo[0]['refund_money'];
        }
        $cardOrderInfoDate = $this->LifeToolsCardOrderModel->getExportData(4, $params['type']);//今天
        if(isset($cardOrderInfoDate[0])){
            $data['today_count_order'] += $cardOrderInfoDate[0]['total_order'];
            $data['today_price_order'] += $cardOrderInfoDate[0]['total_money'];
            $data['today_price_back_order'] += $cardOrderInfoDate[0]['refund_money'];
        }
        //票付通总金额
        $data['pft_order_price'] = $this->lifeToolsOrderModel->where([
            ['order_status', 'in', [20, 30, 40, 45, 70]],
            ['order_source', '=', 'pft'],
            ['paid', '=', 2]
        ])->sum('total_price');
        //票付通今日总金额
        $todayStart = strtotime(date('Ymd'));
        $data['today_pft_order_price'] = $this->lifeToolsOrderModel->where([
            ['order_status', 'in', [20, 30, 40, 45, 70]],
            ['order_source', '=', 'pft'],
            ['paid', '=', 2],
            ['add_time', 'between', [$todayStart, $todayStart + 86400]]
        ])->sum('total_price');
        return $data;
    }

    public function getOrderDetail($param)
    {
        $order_id = $param['order_id'] ?? 0;
        $orderInfo = $this->lifeToolsOrderModel->getDetail(['o.order_id' => $order_id]);
        if (empty($orderInfo)) throw new \think\Exception("没有找到订单");
        $baseInfo = [
            'real_orderid' => $orderInfo['real_orderid'],   //订单编号
            'type' => $orderInfo['type'],                   //订单类型
            'tools_title' => $orderInfo['title'],           //景区名称
            'ticket_title' => $orderInfo['ticket_title'],   //门票名称
            'is_group' => $orderInfo['is_group'],           //门票类型
            'order_status' => $orderInfo['order_status'],   //订单状态
            'order_status_text' => $this->order_status[$orderInfo['order_status']] ?? '未知状态',   //订单状态
            'source' => $this->lifeToolsOrderModel->sourceMap[$orderInfo['source']] ?? '',         //购买方式
            'num' => $orderInfo['num'],                     //下单数量
            'pay_type' => $orderInfo['pay_type'],           //支付方式
            'pay_time' => $orderInfo['pay_time'] ? date('Y-m-d H:i:s',$orderInfo['pay_time']) : '',           //下单时间
            'use_time' => $orderInfo['verify_time'] ? date('Y-m-d',$orderInfo['verify_time']) : '',        //使用日期
            'verify_time' => $orderInfo['verify_time'] ? date('Y-m-d H:i:s',$orderInfo['verify_time']) : '',     //核销时间
        ];
        if($baseInfo['is_group']){
            //团体票
            $userInfo = $this->lifeToolsGroupOrderTouristsModel->getUserInfo([
                'order_id' => $order_id,
                'is_del' => 0
            ],['a.tourists_custom_form','b.nickname','b.phone']);
        }else{
            //普通票
            $userInfo = $this->lifeToolsOrderModel->getUserInfo([
                'order_id' => $order_id
            ],['a.nickname as tourists_nickname','a.phone as tourists_phone','b.nickname','b.phone']);
        }
        $guideInfo = [];
        if($baseInfo['is_group']){
            $customInfo = $this->lifeToolsGroupOrderModel->getUserInfo([
                'order_id' => $order_id
            ],['tour_guide_custom_form']);
            if(isset($customInfo['tour_guide_custom_form']) && is_array($customInfo['tour_guide_custom_form'])){
                $guideInfo = $customInfo['tour_guide_custom_form'];
            }
            if(isset($customInfo['tour_guide_custom_form']) && !is_array($customInfo['tour_guide_custom_form'])){
                $guideInfo = json_decode($customInfo['tour_guide_custom_form'],true);
            }
        }
        $priceInfo = [
            'total_price' => $orderInfo['total_price'],                     //订单总价格
            'price' => $orderInfo['price'],                                 //优惠后价格
            'pay_money' => $orderInfo['pay_money'],                         //在线支付金额
            'system_balance' => $orderInfo['system_balance'],               //平台余额支付金额
            'system_score_money' => $orderInfo['system_score_money'],       //积分抵扣金额
            'system_score' => $orderInfo['system_score'],                   //积分抵扣数
            'merchant_balance_pay' => $orderInfo['merchant_balance_pay'],   //商家余额支付金额
            'merchant_balance_give' => $orderInfo['merchant_balance_give'], //商家赠送余额支付金额
        ];
        $couponInfo = [
            'coupon_price'  =>  $orderInfo['coupon_price'],                 //平台优惠券的金额
            'card_price'  =>  $orderInfo['card_price'],                     //商家优惠券的金额
        ];
        $backInfo = [];
        if($orderInfo['refund_money'] > 0){
            $backInfo = [
                'refund_money' => $orderInfo['refund_money'],               //退款金额
                'refund_time' => $orderInfo['refund_time'] ? date('Y-m-d H:i:s',$orderInfo['refund_time']) : '',                 //退款时间
                'reply_refund_reason' => $orderInfo['reply_refund_reason'], //退款原因
            ];
        }
        $verifyInfo = $this->LifeToolsOrderDetail->getSome(['order_id' => $order_id],['detail_id','code','status','last_time','staff_name as refund_name','verify_type'],'detail_id desc');
        foreach ($verifyInfo as &$v){
            $v['status_text'] = $this->LifeToolsOrderDetail->getStatusText($v['status']);
            $v['verify_type_text'] = $v['status'] == 2 ? $this->LifeToolsOrderDetail->getVerifyTypeText($v['verify_type']) : '';
            $v['last_time'] = $v['status'] == 3 ? ($v['last_time'] ? date('Y-m-d H:i:s',$v['last_time']) : '无') : '无';
            $v['refund_name'] = $v['status'] == 3 ? '' : $v['refund_name'];
        }
        $return = [
            'order_id' => $orderInfo['order_id'],           //订单id
            'mer_id' => $orderInfo['mer_id'],               //商家id
            'base_info' => $baseInfo,                       //订单基本信息
            'user_info' => $userInfo,                       //用户信息
            'guide_info' => $guideInfo,                     //导游信息
            'price_info' => $priceInfo,                     //价格信息
            'coupon_info' => $couponInfo,                   //优惠券信息
            'back_info' => $backInfo,                       //退款信息
            'verify_info' => $verifyInfo,                   //核销信息
        ];
        return $return;
    }

    /**
     * 票务系统订单列表
     */
    public function getTicketSystemOrderList($params)
    {
        if(empty($params['type']) || !in_array($params['type'], ['scenic','stadium','course','sports'])){
            throw new \think\Exception('type参数不正确！');
        }

        $condiiton = [];
        if($params['type'] == 'sports'){
            $condiiton[] = ['a.type', 'in', ['stadium', 'course']];
        }else{
            $condiiton[] = ['a.type', '=', $params['type']];
        }

        //-1：全部，10：待付款，20待核销，30：已核销，50：已退款，70：已过期， 60：已取消，45:售后中
        if ($params['status'] != -1) {
            switch ($params['status']) {
                case 30:
                    $condiiton[] = ['o.order_status', 'in', [30, 40]];
                    break;
                default:
                    $condiiton[] = ['o.order_status', '=', $params['status']];
                    break;
            }
        }

        //wechat：微信，alipay：支付宝，offline：现金支付，balance：余额支付
        if(!empty($params['pay_type']) && $params['pay_type'] != 'all'){
            $condiiton[] = ['o.pay_type', '=', $params['pay_type']];
        }

        //类型:0=全部,1=团体票，2=普通门票
        if(!empty($params['ticket_type'])){
            if($params['ticket_type'] == 3){
                $condiiton[] = ['o.order_source', '=', 'pft'];
            }else{
                $condiiton[] = ['o.is_group', '=', $params['ticket_type'] == 1 ? 1 : 0];
            }
        }

        //0：全部，1：订单号，2：景区|场馆|课程名，3：门票名称，4：手机号，5：用户呢称
        $search_type = '';
        if (!empty($params['keywords']) && !empty($params['search_type'])) {
            switch ($params['search_type']) {
                case 1:
                    $search_type = 'o.order_id|o.real_orderid|o.orderid';
                    break;
                case 2:
                    $search_type = 'a.title';
                    break;
                case 3:
                    $search_type = 't.title';
                    break;
                case 4:
                    $search_type = 'o.phone';
                    break;
                case 5:
                    $search_type = 'o.nickname';
                    break;
            }
        }
        if(!empty($search_type)){
            $condiiton[] = [$search_type, 'like', '%' . $params['keywords'] . '%'];
        }

        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $condiiton[] = ['o.add_time', 'between', [strtotime($params['start_date']), strtotime($params['end_date']) + 86400]];
        }

        $data = $this->lifeToolsOrderModel->getList($condiiton, $params['page_size'] ?? 0);

        foreach($data['data'] as $key => $item)
        {
            $data['data'][$key]['type_text'] = $this->lifeTools->typeMap[$item['type']] ?? '';
            $data['data'][$key]['buy_type'] = $this->lifeToolsOrderModel->sourceMap[$item['source']] ?? '';
            $data['data'][$key]['order_status_text'] = $this->order_status[$item['order_status']] ?? '未知状态';
            if ($item['order_status'] == 50) {
                $data['data'][$key]['order_status_text'] = $item['reply_refund_reason'] == '超时未核销订单自动退款' ? '超时自动退款' : '已退款';
            }

            $data['data'][$key]['pay_type_text'] = '';
            if($item['pay_type'] == 'offline'){
                $data['data'][$key]['pay_type_text'] = L_('现金支付');
            }elseif($item['orderid']){
                $payInfo = (new PayService())->getPayOrderData([$item['orderid']]);
                $payInfo = $payInfo[$item['orderid']] ?? [];
                $payInfo['pay_type_chanel'] = '';
                if(isset($payInfo['pay_type'])){
                    $data['data'][$key]['pay_type_text'] =  ($payInfo['pay_type_txt'] ? $payInfo['pay_type_txt'] : '--').($payInfo['channel'] ? '('.$payInfo['channel_txt'].')' : '');
                }
            }

        }
        return $data;
    }

    /**
     * 平台操作景区、体育订单核销
     * @author Nd
     * @date 2022/4/25
     */
    public function verification($param,$admin)
    {
        if(!$param['code'] || !$param['mer_id']){
            throw new \think\Exception(L_("必要参数缺失"));
        }
        $staffUser = [
            'mer_id'=>$param['mer_id'],
            'id'=>$admin['mer_id'],
            'name'=>$admin['name'],
            'store_id'=>'',
        ];
        $param['verify_type'] = 2;
        //根据核销码查询订单信息
        $orderInfo = $this->LifeToolsOrderDetail->getInfoByCode(['a.code'=>$param['code']],['b.is_group','c.openid','a.order_id','b.real_orderid','b.ticket_title','d.title as tools_title','b.uid','b.tools_id','d.type','b.order_status']);
        if(!$orderInfo){
            throw new \think\Exception(L_("未查询到订单信息"));
        }
        if($orderInfo['order_status'] == 10){
            throw new \think\Exception(L_("待支付订单不能核销"));
        }
        $openid = $orderInfo['openid'];
        $uid = $orderInfo['uid'];
        //核销
        $data = (new ScanService())->indexScan($param,$staffUser);
        if($data['operate_type'] == 'success'){
        //发送微信公众号
        if ($openid) {
            $model = new TemplateNewsService();
            $model->sendTempMsg('TM00017', array(
                'wecha_id' => $openid,
                'first' => '平台于' . date('Y-m-d H:i:s',time()) . '时，核验了【'.$orderInfo['tools_title'].'】-'.$orderInfo['ticket_title'],
                'OrderSn' => $orderInfo['real_orderid'],
                'OrderStatus' => L_("平台已于X1核销【核销码：".$param['code']."】成功", date('Y-m-d H:i:s', time())),
                'remark' => date('Y-m-d H:i:s')
            ));
        }
        //发送至小程序消息
        $title = '订单核验成功';
        $msg = '平台在' . date('Y-m-d H:i:s') . '成功核验了【'.$orderInfo['tools_title'].'】-'.$orderInfo['ticket_title'].'(核销码：'.$param['code'].')';
        $this->LifeToolsMessage->add([
            'tools_id' => 0,
            'type'     => 'scenic',
            'uid'      => $uid,
            'order_id' => $orderInfo['order_id'],
            'title'    => $title,
            'content'  => $msg,
            'add_time' => time()
        ]);
        }
        return $data;
    }

    /**
     * 订单导出
     */
    public function orderExport($params)
    {  
        $tools_type = $params['type'] == 'scenic' ? '景区名称' : '场馆/课程名称';
        $csvHead = array(
            L_('订单号'),
            L_($tools_type),
            L_('门票名称'),
            L_('数量'),
            L_('总价'),
            L_('类型'),
            L_('用户呢称'),
            L_('手机号'),
            L_('购买平台'),
            L_('下单时间'),
            L_('支付方式'),
            L_('状态'),
        );

        
        $data = $this->getTicketSystemOrderList($params);
        
        $csvData = [];
 
        if (!empty($data['data'])) { 
            foreach ($data['data'] as $key => $value) {
                $column_type = $value['type'] == 'scenic' ? $value['ticket_type'] : $value['type_text'];
                $csvData[$key] = [
                    $value['orderid'] . "\t",
                    $value['title'],
                    $value['ticket_type'],
                    $value['num'],
                    $value['total_price'],
                    $column_type,
                    $value['nickname'],
                    $value['phone'] . "\t",
                    $value['buy_type'],
                    $value['add_time_text'] . "\t",
                    $value['pay_type_text'],
                    $value['order_status_text'],
                ]; 
            }
        } 
        $filename = date("Y-m-d", time()) . '-' . $params['rand_number'] . '.csv'; 
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead); 

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

    public function dataExport($params)
    {
        $tools_type = $params['type'] == 'scenic' ? 'scenic' : 'sports';
        $csvHead = array(
            L_('日期'),
            L_('订单数量'),
            L_('订单金额'),
            L_('退款金额')
        );
        if($tools_type == 'scenic'){
            $csvHead[] = L_('现金金额');
        }

        $data = $this->lifeToolsOrderModel->getExportData($params['export_type'], $tools_type);
        // return $data;
        $csvData = [];
        $total = [];
        $total['dates'] = '本页总计';
        $total['total_order'] = 0;
        $total['total_money'] = 0;
        $total['refund_money'] = 0;
        $total['offline_pay_money'] = 0;
        if (!empty($data)) { 
            foreach ($data as $key => $value) {
                $csvData[$key] = [
                    ' ' . $value['dates'] . ' ',
                    $value['total_order'],
                    $value['total_money'],
                    $value['refund_money'],
                    $value['offline_pay_money']
                ];
                $total['total_order'] += $value['total_order'];
                $total['total_money'] += $value['total_money'];
                $total['refund_money'] += $value['refund_money'];
                $total['offline_pay_money'] += $value['offline_pay_money'];
            }
        } 
        $csvData[] = $total;
        $filename = date("Y-m-d", time()) . '-' . $params['rand_number'] . '.csv'; 
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead); 

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }
}