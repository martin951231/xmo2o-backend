<?php
/**
 * 员工卡充值订单service
 * @date 2021-12-21
 */

namespace app\employee\model\service;

use app\common\model\service\order\SystemOrderService;
use app\employee\model\db\EmployeeCardLable;
use app\employee\model\db\EmployeeCardOrder;
use app\employee\model\db\EmployeeCardUser;
use app\merchant\model\service\MerchantMoneyListService;
use app\pay\model\db\PayType;
use app\common\model\service\export\ExportService as BaseExportService;
use app\pay\model\service\PayService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;
use think\Model;

class EmployeeCardOrderService
{
    public $employeeCardOrderModel = null;
    public $order_status = [ //订单状态 10未支付不显示 20已支付，30已取消，40已退款
        '10' => '待支付', //待付款
        '20' => '已充值', //已支付
        '30' => '已取消', //已取消
        '40' => '已退款', //已退款
        '100'=> '后台充值余额',
        '110'=> '后台扣除余额'
    ];
    public function __construct()
    {
        $this->employeeCardOrderModel = new EmployeeCardOrder();
    }

    /**
     * 提交充值订单
     * @param $param
     * @return array
     */
    public function goPay($param = [])
    {
        $data['user_id'] = $param['user_id'] ?? '0';// 员工卡会员ID
        $data['uid'] = $param['uid'] ?? 0;// 用户id
        $data['order_name'] = '员工卡充值';// 排序值
        $data['real_orderid'] = build_real_orderid($data['uid']);// 订单长id
        $data['total_price'] = $param['money'] ?? '0';// 总价
        $data['price'] = $param['money'] ?? '0';// 优惠后的价格        
        $data['nickname'] = $param['nickname'] ?? '';// 用户昵称
        $data['phone'] = $param['phone'] ?? '';// 用户手机号

        
        if(empty($data['user_id'])){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }       
        
        if(empty($data['uid'])){
            throw new \think\Exception(L_('用户不存在'), 1002);
        }

        if(empty($data['total_price'])){
            throw new \think\Exception(L_('充值金额必须大于0'), 1003);
        }

        // 查询会员信息
        $cardUser = (new EmployeeCardUserService)->getOne(['user_id' => $data['user_id']]);
        if(empty($cardUser)){
            throw new \think\Exception(L_('会员不存在'), 1003);
        }

        if($cardUser['status'] == 0){
            throw new \think\Exception(L_('会员已禁用，不能充值'), 1003);
        }

        // 查询会员卡
        $card = (new EmployeeCardService)->getOne(['card_id'=>$cardUser['card_id']]); 
        if(empty($card)){
            throw new \think\Exception(L_('员工卡不存在'), 1003);
        }
        
        if($card['status'] == 0){
            throw new \think\Exception(L_('员工卡未开启'), 1003);
        }
        
        $data['mer_id'] = $card['mer_id'];// 商家id
        $data['card_id'] = $card['card_id'];// 员工卡id
        $data['last_time'] = time();
        $data['add_time'] = time();
        $data['order_status'] = 10;

        $orderId = $this->add($data);
        if(empty($orderId)){
            throw new \think\Exception(L_('订单提交失败'), 1001);
        }

        $returnArr['order_id'] = $orderId;
        $returnArr['order_type'] = 'employee_card';
        return $returnArr;
    }
    
    /**
     * 获得支付订单信息（供支付调用）
     * @param $param 
     * @param $user array 用户
     * @return array 
     */
    public function getOrderPayInfo($orderId)
    {
        if(!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        $where = [
            'order_id' => $orderId
        ];
        $order = $this->getOne($where);
        if(!$order) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        $returnArr['order_money'] = $order['price'];
        $returnArr['paid'] = $order['paid'];
        $returnArr['order_no'] = $order['real_orderid'];
        $returnArr['mer_id'] = $order['mer_id'];
        $returnArr['is_cancel'] = $order['order_status'] >= 30 ? 1 : 0;
        $returnArr['time_remaining'] = 900 - (time() - $order['add_time']);//秒
        $returnArr['city_id'] = 0;
        $returnArr['store_id'] = 0;

        $returnArr['use_merchant_balance'] = false;
        $returnArr['use_score'] = false;
        $returnArr['use_platform_balance'] = false;
        $returnArr['business_order_sn'] = $order['real_orderid'];
        

        $isCancel = 0;
        if($returnArr['time_remaining']<=0){
            $isCancel = 1;
        }

        $returnArr['uid'] = $order['uid'];
        $returnArr['title'] = $order['order_name'];

        if($returnArr['time_remaining']<=0 && $isCancel){
            //取消订单
            $data['order_status'] = 30;
            $where = [
                'order_id' => $orderId
            ];
            $this->updateThis($where, $data);
        }
        return $returnArr;        
    }

    /**
     * 获得支付订单信息（供支付调用）
     * @param $param 
     * @param $user array 用户
     * @return array 
     */
    public function afterPay($orderId, $payParam)
    {
        if(!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }
        
        $nowOrder = $this->getOne(['order_id' =>$orderId]);
        if(!$nowOrder) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }

        $paidTime = isset($payParam['pay_time']) ? $payParam['pay_time'] : '';
        $paidMoney = isset($payParam['paid_money']) ? $payParam['paid_money'] : '';
        $paidType = isset($payParam['paid_type']) ? $payParam['paid_type'] : '';
        $paidOrderid = isset($payParam['paid_orderid']) ? $payParam['paid_orderid'] : '';
        $isOwn = isset($payParam['is_own']) ? $payParam['is_own'] : '0';
        $uid = isset($payParam['uid']) ? $payParam['uid'] : '0';

        // 验证订单状态
        if ($nowOrder['order_status'] != 10) {
            // 该订单已付款
            return false;
        }
        
        // 商家id
        $payParam['mer_id'] = $diningOrder['mer_id'] ?? 0;

        // 保存支付订单信息
        $saveData = [];
        $saveData['pay_time'] = $paidTime ? $paidTime : time();
        $saveData['pay_money'] = $paidMoney;//在线支付的钱
        $saveData['pay_type'] = $paidType;
        $saveData['orderid'] = $paidOrderid;
        $saveData['paid'] = 1;
        $saveData['order_status'] = 20;
        $saveData['is_own'] = $isOwn;//是否自有支付
        if($uid){
            $saveData['uid'] = $uid;
        }
        $where = [
            'order_id' => $orderId,
        ];
        if($this->updateThis($where ,$saveData)){
            $orderInfo = [];
            $orderInfo['order_id'] = $nowOrder['order_id'];
            $orderInfo['real_orderid'] = $nowOrder['real_orderid'];
            $orderInfo['mer_id'] = $nowOrder['mer_id'];
            $orderInfo['store_id'] = 0; //当前门店ID
            $orderInfo['uid'] = $nowOrder['uid']; //用户id
            $orderInfo['order_type'] = 'employee_card'; //业务代号
            $orderInfo['num'] = 1;
            
    
            $orderInfo['payment_money'] = '0'; //在线支付金额（不包含自有支付）
            $orderInfo['balance_pay'] = '0'; //平台余额支付金额
            $orderInfo['score_deducte'] = '0'; //平台积分抵扣金额
            $orderInfo['merchant_balance'] = '0'; //商家会员卡支付金额
            $orderInfo['card_give_money'] = '0'; //商家会员卡赠送支付金额
            $orderInfo['card_give_money'] = '0'; //商家会员卡赠送支付金额
            $orderInfo['system_coupon_plat_money'] = '0'; //平台优惠券平台抵扣金额
            $orderInfo['system_coupon_merchant_money'] = '0'; //平台优惠券商家抵扣金额
            $orderInfo['score_used_count'] = '0'; //积分使用数量
    
            $orderInfo['is_own'] = $isOwn; //自有支付类型
            $orderInfo['own_pay_money'] = '0'; //自有支付在线支付金额
            $orderInfo['pay_order_id'] = [$paidOrderid]; //支付单号id
            $orderInfo['pay_type'] = $paidType; //支付方式
            $orderInfo['desc'] = '员工卡充值余额'; 
            $orderInfo['total_money'] = $nowOrder['total_price'];
            
            if(!$isOwn){
                $orderInfo['payment_money'] = $paidMoney;
                $orderInfo['bill_money'] = $paidMoney;
            }else{
                $orderInfo['own_pay_money'] = $paidMoney;
                $orderInfo['bill_money'] = 0;
            }

            (new MerchantMoneyListService())->addMoney($orderInfo);

            // 增加余额
            (new EmployeeCardUserService())->addMoney($nowOrder['user_id'], $nowOrder['total_price'], '用户充值余额'.cfg('Currency_symbol').$nowOrder['total_price'],'您在'.date('Y-m-d H:i:s').'充值了余额'.cfg('Currency_symbol').$nowOrder['total_price'],'user');
        }else{
            return false;
        }
        return true;       
    }
    /**
     * 获得支付后跳转链接供支付调用）
     * @param $param 
     * @param $payParam array 支付后的支付数据
     * @return array 
    */
    public function getPayResultUrl($orderId,$cancel = 0)
    {
        if(!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }
        
        $nowOrder = $this->getOne(['order_id' =>$orderId]);
        if(!$nowOrder) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }
        

        if($cancel== 1){
            // 订单详情
            $url = get_base_url().'pages/lifeTools/pocket/message?card_id='.$nowOrder['card_id'];
            return $url;
        }else{
            // 支付成功页
            $url = get_base_url().'pages/lifeTools/pocket/message?card_id='.$nowOrder['card_id'];
            return ['redirect_url' => $url, 'direct' => 1];
        }
    }

    /**
     * 写入平台总订单
     * @param $tableId int 桌台id
     * @return array
     */
    public function addSystemOrder($orderId = 0,$step = 1){
    	if(!$orderId) return false;
        $where = [
            'order_id' => $orderId
        ];
        $nowOrder = $this->getOne($where);
       
        $business = 'employee_card'; 
        $businessOrderId = $orderId; 

        // 如果系统订单不存在该订单则添加
        $systemOrderService = new SystemOrderService();
        $systemWhere= [
            'type' => $business,
            'order_id' => $businessOrderId,
        ];
        $systemOrder = $systemOrderService->getCount($systemWhere);
        $step = empty($systemOrder) ? 0 : 1;

        $data['price'] = $nowOrder['price'];
        $data['discount_price'] = $nowOrder['price'];
        $data['total_price'] = $nowOrder['total_price'];
        $data['paid'] = $nowOrder['pay_time'] > 0 ? 1 : 0;
        $data['pay_time'] = $nowOrder['pay_time'];
        if($step == 0){
            $data['store_id'] = $nowOrder['store_id'] ?? 0;
            $data['mer_id'] = $nowOrder['mer_id'];
            $data['real_orderid'] = $nowOrder['real_orderid'];
            $data['system_status'] = 0;
            $data['last_time'] = time();
            $systemOrderService->saveOrder($business, $businessOrderId, $nowOrder['uid'], $data);
        }else{
            if($nowOrder['order_status'] == 10) {
                // 待支付
                $data['system_status'] = 0;
                $systemOrderService->editOrder($business, $businessOrderId, $data);
            } elseif ($nowOrder['order_status'] == 20) {
                //完成订单
                $systemOrderService->commentOrder($business, $businessOrderId, $data);
            }  elseif ($nowOrder['order_status'] == 30) {
                // 取消订单
                $systemOrderService->cancelOrder($business, $businessOrderId);
            }
        }
        return true;
    }
    
    /**
     *插入一条数据
     * @param array $data 
     * @return int|bool
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $id = $this->employeeCardOrderModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        $this->addSystemOrder($id,0);

        return $id;
    }

    /**
     *获取一条条数据
     * @param array $where 
     * @return array
     */
    public function getOne($where){
        $result = $this->employeeCardOrderModel->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param array $where 
     * @return array
     */
    public function getCount($where = []){
        $result = $this->employeeCardOrderModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     * 更新数据
     * @param array $where 
     * @param array $data 
     * @return bool
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $data['last_time'] = time();

        $result = $this->employeeCardOrderModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        if(isset($where['order_id'])){
            $this->addSystemOrder($where['order_id'],1);
        }
        return $result;
    }

    /**
     *员工卡-充值记录-列表
     * @param $param array
     * @param $type 1=导出
     * @return array
     */
    public function getEmployeeOrderList($param, $type = 0) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $type == 1 && $limit = 0;
        $where = [
            ['o.paid', '=', 1],
            ['o.order_status', 'in', '20,40,100']
        ];
        if (!empty($param['mer_id'])) {
            $where[] = ['o.mer_id', '=', $param['mer_id']];
        }
        if (!empty($param['keyword'])) {
            $search_type = '';
            switch ($param['search_type']) { //1订单号，2卡号，3姓名，4手机号
                case 1:
                    $search_type = 'o.real_orderid';
                    break;
                case 2:
                    $search_type = 'a.card_number';
                    break;
                case 3:
                    $search_type = 'a.name';
                    break;
                case 4:
                    $search_type = 'u.phone';
                    break;
                case 5:
                    $search_type = 'a.identity';
                    break;
                case 6:
                    $search_type = 'a.department';
                    break;
                case 7: //标签搜索，单独处理
                    $lableIds = (new EmployeeCardLable())->where([
                        ['is_del', '=', 0],
                        ['mer_id', '=', $param['mer_id']],
                        ['name', 'like', '%' . $param['keyword'] . '%']
                    ])->column('id');
                    if (!empty($lableIds)) {
                        $lableIds = implode(',', $lableIds);
                    } else {
                        $lableIds = '0';
                    }
                    $where[] = ['','exp',Db::raw("concat(',',a.lable_ids,',') regexp concat(',(',replace(" . $lableIds . ",',','|'),'),')")];
                    break;
            }
            !empty($search_type) && $where[] = [$search_type, 'like', '%' . $param['keyword'] . '%'];
        }
        if (!empty($param['status'])) {
            $where[] = ['o.order_status', '=', $param['status']];
        }
        if (!empty($param['pay_type']) && $param['pay_type'] != 'all') {
            if ($param['pay_type'] == 'balance') {
                $param['pay_type'] = '';
            }
            $where[] = ['o.pay_type', '=', $param['pay_type']];
        }
        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            $where[] = ['o.pay_time', '>=', strtotime($param['start_time'])];
            $where[] = ['o.pay_time', '<', strtotime($param['end_time']) + 86400];
        }
        $result = $this->employeeCardOrderModel->getList($where, $limit);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['nickname'] = $v['card_name'];
                $result['data'][$k]['pay_time'] = !empty($v['pay_time']) ? date('Y-m-d H:i:s', $v['pay_time']) : '无';
                $result['data'][$k]['order_status_val'] = $this->order_status[$v['order_status']] ?? '未知状态';
                $result['data'][$k]['pay_type_val'] = $this->getPayType()[$v['pay_type']] ?? '余额支付';
                $result['data'][$k]['id_de'] = $v['identity'] . '-' . $v['department'];
                $result['data'][$k]['refund_btn'] = 0;
                if ($v['order_status'] == 20 && $v['card_money'] >= $v['total_price']) {
                    $result['data'][$k]['refund_btn'] = 1;
                }
            }
        }
        return $result;
    }

    /**
     * 员工卡-充值记录-获取支付方式
     * @return array
     */
    public function getPayType() {
        $arr = [
            'all' => '全部',
            'balance' => '余额支付',
            'merchant'  =>  '后台充值'
        ];
        $list = (new PayType())->getList(['switch' => 1]);
        if (!empty($list)) {
            $arr = array_merge($arr, array_column($list->toArray(), 'text', 'code'));
        }
        return $arr;
    }

    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function getEmployeeOrderExport($param, $systemUser = [], $merchantUser = [])
    {
        $title = '员工卡-充值记录';
        $param['type'] = 'pc';
        $param['service_path'] = '\app\employee\model\service\EmployeeCardOrderService';
        $param['service_name'] = 'employeeOrderExportPhpSpreadsheet';
        $param['rand_number']  = time();
        $param['system_user']['area_id']  = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        return $result;
    }

    /**
     * 导出(Spreadsheet方法)
     * @param $param
     */
    public function employeeOrderExportPhpSpreadsheet($param)
    {
        $orderList   = $this->getEmployeeOrderList($param, 1)['data'];
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '订单号');
        $worksheet->setCellValueByColumnAndRow(2, 1, '卡号');
        $worksheet->setCellValueByColumnAndRow(3, 1, '姓名');
        $worksheet->setCellValueByColumnAndRow(4, 1, '手机');
        $worksheet->setCellValueByColumnAndRow(5, 1, '充值金额');
        $worksheet->setCellValueByColumnAndRow(6, 1, '账户剩余金额');
        $worksheet->setCellValueByColumnAndRow(7, 1, '充值方式');
        $worksheet->setCellValueByColumnAndRow(8, 1, '充值时间');
        $worksheet->setCellValueByColumnAndRow(9, 1, '状态');
        //设置单元格样式
        $worksheet->getStyle('A1:G1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:G')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
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
        $len = count($orderList);
        $j   = 0;
        $row = 0;
        $i   = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['real_orderid']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $orderList[$key]['card_number']);
                $worksheet->setCellValueByColumnAndRow(3, $j, $orderList[$key]['nickname']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $orderList[$key]['phone']);
                $worksheet->setCellValueByColumnAndRow(5, $j, $orderList[$key]['total_price']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['card_money']);
                $worksheet->setCellValueByColumnAndRow(7, $j, $orderList[$key]['pay_type_val']);
                $worksheet->setCellValueByColumnAndRow(8, $j, $orderList[$key]['pay_time']);
                $worksheet->setCellValueByColumnAndRow(9, $j, $orderList[$key]['order_status_val']);
                $i++;
            }
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:G' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);

    }

    /**
     *员工卡-充值记录-统计数据
     * @param $param array
     * @param $type 1=导出
     * @return array
     */
    public function getEmployeeOrderStatistics($param, $type = 0) {
        $result = [
            'all_count'    => 0,
            'today_count'  => 0,
            'all_money'    => 0,
            'today_money'  => 0,
            'all_user'     => 0,
            'today_user'   => 0,
            'all_refund'   => 0,
            'today_refund' => 0
        ];
        $where = [
            ['paid', '=', 1]
        ];
        if (!empty($param['mer_id'])) {
            $where[] = ['mer_id', '=', $param['mer_id']];
        }
        $where1 = $where;
        $where2 = $where;
        $where1[] = ['pay_time', '>=', strtotime(date('Y-m-d'))];
        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            $where2[] = ['pay_time', '>=', strtotime($param['start_time'])];
            $where2[] = ['pay_time', '<', strtotime($param['end_time']) + 86400];
        }
        $result['all_count']    = $this->employeeCardOrderModel->getCount(array_merge($where2, [['order_status', '=', '20']]));
        $result['today_count']  = $this->employeeCardOrderModel->getCount(array_merge($where1, [['order_status', '=', '20']]));
        $result['all_money']    = $this->employeeCardOrderModel->getSum(array_merge($where2, [['order_status', '=', '20']]), 'total_price');
        $result['today_money']  = $this->employeeCardOrderModel->getSum(array_merge($where1, [['order_status', '=', '20']]), 'total_price');
        $result['all_user']     = $this->employeeCardOrderModel->where(array_merge($where2, [['order_status', '=', '20']]))->group('uid')->count();
        $result['today_user']   = $this->employeeCardOrderModel->where(array_merge($where1, [['order_status', '=', '20']]))->group('uid')->count();
        $result['all_refund']   = $this->employeeCardOrderModel->getSum(array_merge($where2, [['order_status', '=', '40']]), 'total_price');
        $result['today_refund'] = $this->employeeCardOrderModel->getSum(array_merge($where1, [['order_status', '=', '40']]), 'total_price');
        if ($type == 1) {
            empty($param['export_type']) && $param['export_type'] = 'day';
            $arr  = [];
            $list = $this->employeeCardOrderModel->where(array_merge($where, [['order_status', 'in', '20,40']]))->select();
            if (!empty($list)) {
                $list = $list->toArray();
                $arr1 = [];
                $uids = [];
                foreach ($list as $v) {
                    $uids[] = $v['uid'];
                    switch ($param['export_type']) {
                        case 'month':
                            $date = date('Ym', $v['pay_time']);
                            break;
                        case 'year':
                            $date = date('Y', $v['pay_time']);
                            break;
                        default:
                            $date = date('Ymd', $v['pay_time']);
                            break;
                    }
                    $arr1[$date][] = $v;
                }
                foreach ($arr1 as $k1 => $v1) {
                    $refundArr = [];
                    foreach ($v1 as $v2) {
                        $v2['order_status'] == 40 && $refundArr[] = $v2['total_price'];
                    }
                    $arr[] = [
                        'date'         => $k1,
                        'user_count'   => count(array_unique(array_column($v1, 'uid'))),
                        'order_count'  => count($v1),
                        'order_money'  => array_sum(array_column($v1, 'total_price')),
                        'refund_money' => array_sum($refundArr)
                    ];
                }
                $arr[] = [
                    'date'         => '总计',
                    'user_count'   => count(array_unique($uids)),
                    'order_count'  => count(array_column($arr, 'order_count')),
                    'order_money'  => array_sum(array_column($arr, 'order_money')),
                    'refund_money' => array_sum(array_column($arr, 'refund_money'))
                ];
            }
            $result['data'] = $arr;
        }
        return $result;
    }

    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function getEmployeeOrderStatisticsExport($param, $systemUser = [], $merchantUser = [])
    {
        $title = '员工卡-充值记录';
        $param['type'] = 'pc';
        $param['service_path'] = '\app\employee\model\service\EmployeeCardOrderService';
        $param['service_name'] = 'employeeOrderStatisticsExportPhpSpreadsheet';
        $param['rand_number']  = time();
        $param['system_user']['area_id']  = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        return $result;
    }

    /**
     * 导出(Spreadsheet方法)
     * @param $param
     */
    public function employeeOrderStatisticsExportPhpSpreadsheet($param)
    {
        $orderList   = $this->getEmployeeOrderStatistics($param, 1)['data'];
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '日期');
        $worksheet->setCellValueByColumnAndRow(2, 1, '充值人数');
        $worksheet->setCellValueByColumnAndRow(3, 1, '充值笔数');
        $worksheet->setCellValueByColumnAndRow(4, 1, '充值金额');
        $worksheet->setCellValueByColumnAndRow(5, 1, '退款金额');
        $worksheet->setCellValueByColumnAndRow(6, 1, '入账金额');
        $worksheet->setCellValueByColumnAndRow(7, 1, '（元）');
        //设置单元格样式
        $worksheet->getStyle('A1:G1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:G')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(42);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        $len = count($orderList);
        $j   = 0;
        $row = 0;
        $i   = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['date']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $orderList[$key]['user_count']);
                $worksheet->setCellValueByColumnAndRow(3, $j, $orderList[$key]['order_count']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $orderList[$key]['order_money']);
                $worksheet->setCellValueByColumnAndRow(5, $j, $orderList[$key]['refund_money']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['order_money'] - $orderList[$key]['refund_money']);
                $i++;
            }
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:G' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);

    }

    /**
     * 员工卡-充值记录-退款
     * @param $param array
     */
    public function employeeOrderRefund($param, $merchantUser = []) {
        $orderDetail = $this->employeeCardOrderModel->getDetail(['o.order_id' => $param['order_id'], 'o.mer_id' => $param['mer_id'], 'order_status' => 20]);
        if (empty($orderDetail)) {
            throw new \think\Exception(L_('订单不存在'), 1003);
        }
        if ($orderDetail['card_money'] < $orderDetail['total_price']) {
            throw new \think\Exception(L_('用户余额不足'), 1003);
        }
        Db::startTrans();
        try {
            $res = $this->employeeCardOrderModel->updateThis(['order_id' => $orderDetail['order_id']], ['order_status' => 40, 'last_time' => time()]);
            if ($res !== false) {
                (new SystemOrderService())->refundOrder('employee_card', $orderDetail['order_id']);
                $this->refund($orderDetail);
                (new EmployeeCardUser())->setDec(['user_id' => $orderDetail['user_id']], 'card_money', $orderDetail['total_price']);
                // 添加日志
                $logData = [
                    'card_id'      => $orderDetail['card_id'],
                    'user_id'      => $orderDetail['user_id'],
                    'mer_id'       => $orderDetail['mer_id'],
                    'uid'          => $orderDetail['uid'],
                    'num'          => $orderDetail['total_price'],
                    'type'         => 'money',
                    'change_type'  => 'decrease',
                    'operate_id'   => $merchantUser['mer_id'],
                    'operate_type' => 'merchant',
                    'operate_name' => $merchantUser['name'],
                    'description'  => '商家后台操作：充值记录列表退款，减少余额：' . cfg('Currency_symbol') . $orderDetail['total_price'],
                    'user_desc'    => '商家后台操作：充值记录列表退款，减少余额：' . cfg('Currency_symbol') . $orderDetail['total_price'],
                ];
                (new EmployeeCardLogService())->add($logData);

                $moneyListMod = new MerchantMoneyListService();
                $records  = $moneyListMod->getAll(['order_id' => $orderDetail['order_id'], 'type' => 'employee_card']);
                $totalGet = $totalRefund = 0;
                foreach ($records as $v) {
                    if ($v['income'] == 1) {
                        $totalGet += $v['money'];
                    } else {
                        $totalRefund += $v['money'];
                    }
                }
                $reduceMoney = get_format_number($totalGet - $totalRefund);
                $reduceMoney > 0 && $moneyListMod->useMoney($orderDetail['mer_id'], $reduceMoney, 'employee_card', '员工卡充值余额退款,减少余额,订单编号' . $orderDetail['orderid'], $orderDetail['order_id'], 0, 0, [], 0);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 退款
     * @param  [type] $order_id     订单ID
     * @return [type]               [description]
     */
    public function refund($order) {
        if($order['paid'] != 1) { //未支付
            return true;
        }
        if($order['order_status'] != 20) {
            throw new \think\Exception('当前订单状态不支持退款');
        }
        $refund_money = $order['pay_money'];
        $alias_name = '员工卡充值余额';
        try {
            //在线支付退款
            if ($refund_money > 0) {
                $PayService = new PayService();
                if ($order['orderid']) {
                    $end_order = $PayService->getPayOrderInfo($order['orderid']);
                    if ($end_order['paid'] == '1' && $end_order['paid_money'] > 0) {
                        $end_money = ($end_order['paid_money'] - $end_order['refund_money']) / 100;
                        if ($end_money > 0) {
                            $PayService->refund($order['orderid'], $end_money);
                        }
                    }
                }
            }
            return true;
        } catch(\Exception $e) {
            throw new \think\Exception($e->getMessage(), 1005);
        }
    }

}