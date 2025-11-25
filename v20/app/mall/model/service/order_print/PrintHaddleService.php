<?php
/**
 * 打印service
 * Author: hengtingmei
 * Date Time: 2021/10/08 14:45
 */

namespace app\mall\model\service\order_print;
use app\merchant\model\service\print_order\PrintHaddleService as PrintHaddleBase;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\MerchantService;
use app\mall\model\service\MallOrderService;
use app\merchant\model\service\print_order\OrderprintService;
class PrintHaddleService {
    public $printBase = null;
    public function __construct()
    {
        $this->printBase = new PrintHaddleBase();
    }

    /**
     * 打印订单
     */
    public function printOrder($param, $status = -1)
    {

        $staff =  request()->staffUser;
        $orderId = $param['order_id'] ?? 0;
        isset($staff['name']) && $param['staff_name'] = $staff['name'];//店员名称
        $param['staff_name'] = $param['staff_name'] ?? '';
        $param['repair_print'] = $param['repair_print'] ?? '0';//是否补打

        if (empty($orderId)){
            return false;
        }

        // 订单信息
        $mallOrderService = new MallOrderService();
        $order = $mallOrderService->getOrderDetailsCopy($orderId);
        if (empty($order)){
            return false;
        }

        if($order['refund_money']<=0&&$order['pay_time']>0&&$order['money_refund']){
            $order['refund_money'] = $order['money_refund'];
        }

        // 商家信息
        $merchantInfo = (new MerchantService())->getMerchantByMerId($order['mer_id']);
        if (!empty($merchantInfo['merchant_default_lang'])) {
            cfg('tmp_system_lang',$merchantInfo['merchant_default_lang']);
        }

        // 打印机列表
        $printParam = [
            'store_id' => $order['store_id']
        ];
        $printList = (new OrderprintService())->getPrintList($printParam);
        if (empty($printList)) {// 无可用打印机
            return false;
        }

        $store = (new MerchantStoreService)->getStoreByStoreId($order['store_id']);
        $param['store'] = $store;
        $param['merchant'] = $merchantInfo;

        // 结账单            
        $this->frontPrint($order, $status, $printList['list'], $param);
        
        cfg('tmp_system_lang','');

        return true;
    }

    /**
     * 前台小票打印
     *  @param $order array 订单信息
     *  @param $status int 打印订单状态 5-接单后
     *  @param $printList array 打印机列表
     *  @param $param array 其他参数
     *  @return bool
     */
    public function frontPrint($order, $status, $printList , $param=[])
    {
        if(isset($param['goods_list']) && $param['goods_list']){
            $order['children'] = $param['goods_list'];
        }
        if(empty($order['children'])){// 商品信息
            return true;
        }

        foreach ($printList as $_print) {
            // 打印方式 1-接单后 2-支付后 3-退款后
            $statutArr = explode(',',$_print['mall_print']);

            if ($status != -1 && !in_array($status,$statutArr)) {
                // 不打印前台小票 跳过
                continue;
            }

            // 获得宽度
            $smallArr = array('is_big'=>0,'paper'=>$_print['paper']);
            $formatData = $this->printBase->getThreeData($smallArr);
            $widthSmall = $formatData['width'];

            $bigArr = array('is_big'=>2,'paper'=>$_print['paper']);
            $formatDataBig = $this->printBase->getThreeData($bigArr);
            $widthBig = $formatDataBig['width'];

            $middleArr = array('is_big'=>1,'paper'=>$_print['paper']);
            $formatDataMiddle = $this->printBase->getThreeData($bigArr);
            $widthMiddle = $formatDataMiddle['width'];

            // 只有最大号 和 大小区分 两种
            if ($_print['is_big']==2) {
                $param['size'] = 'big';
                $param['width'] = $widthBig;
            }elseif($_print['is_big']==1){
                $param['size'] = 'middle';
                $param['width'] = $widthMiddle;
            }else{
                $param['size'] = 'small';
                $param['width'] = $widthSmall;
            }

            // 打印内容头部信息
            $formatArr = $this->foodshopHeader($order, $param);

            // 打印内容商品信息
            $bodyArr = $this->foodshopBody($order['children'], $_print, $param);
            $formatArr = array_merge($formatArr, $bodyArr);

            // 打印内容底部信息
            $footerArr = $this->foodshopFooter($order, $_print, $param);
            $formatArr = array_merge($formatArr, $footerArr);

            fdump($_print, 'print',1);
            $this->printBase->toPrint($formatArr, $_print);            
        }
    }

    /**
     * 后厨小票打印
     *  @param $order array 订单信息
     *  @param $printList array 打印机列表
     *  @param $param array 其他参数
     *  @return bool
     */
    public function menuPrint($order, $printList , $param=[])
    {
        $param['print_type'] = 'menu';

        // 打印指定商品
        if(isset($param['children']) && $param['children']){
            $order['children'] = $param['children'];
        }

        foreach ($printList as $_print) {

            // 获得宽度
            $smallArr = array('is_big'=>0,'paper'=>$_print['paper']);
            $formatData = $this->printBase->getThreeData($smallArr);
            $widthSmall = $formatData['width'];

            $bigArr = array('is_big'=>2,'paper'=>$_print['paper']);
            $formatDataBig = $this->printBase->getThreeData($bigArr);
            $widthBig = $formatDataBig['width'];

            // 只有最大号 和 大小区分 两种
            if ($_print['is_big']==2) {
                $param['smallSize'] = 'big';
                $param['middleSize'] = 'big';
                $param['bigSize'] = 'big';
                $param['width'] = $widthBig;
            }else{
                $param['smallSize'] = 'small';
                $param['middleSize'] = 'middle';
                $param['bigSize'] = 'big';
                $param['width'] = $widthSmall;
            }
                  
            $printBindGoods = $order['children'];
            if(empty($printBindGoods)){
                continue;
            }

       
            // 整单打印
            // 打印内容头部信息
            $formatArr = $this->foodshopHeader($order, $param);

            // 打印内容商品信息
            $bodyArr = $this->foodshopBody($printBindGoods, $_print, 2);
            $formatArr = array_merge($formatArr, $bodyArr);

            // 打印内容底部信息
            $footerArr = $this->foodshopFooter($order, $param);
            $formatArr = array_merge($formatArr, $footerArr);

            //打印张数
            $printNumber = $_print['count'];
            while($printNumber>0){
                // 去打印
                fdump('整单', 'print',1);
                fdump($_print, 'print',1);
                $this->printBase->toPrint($formatArr, $_print);
                $printNumber--;
            }
        }
    }

    /**
     *  打印头部数据（菜品以上打印内容）
     *  @param $order array 订单详情
     *  @param $staffName string 店员名字
     *  @param $store array 店铺信息
     *  return $formatArr array 返回打印数组
     */
    public function foodshopHeader($order, $param=[]){
        $staffName = $param['staff_name'] ?? ''; // 店员名字
        $store = $param['store'] ?? [];// 店铺信息
        $printType = $param['print_type'] ?? ''; // 打印类型
        // 打印内容尺寸信息
        $size = $param['size'] ?? 'small';
        $width = $param['width'] ?? '16';

        // 打印内容数组
        $formatArr = [];

        // 打印标题
        $title = '';
        switch ($printType){
            case 'bill_account' ://结账单
                // $title = "结账单";
                break;
            case 'refund' ://退款单
                $title = "退款单";
                break;
        }
        // 标题
        $titleName = cfg('site_name').($title ? '-'.$title : '');
        if(isset($order['fetch_number']) && $order['fetch_number']){
            $titleName = '#'.$order['fetch_number'].$titleName;
        }
        $formatArr[] = $this->printBase->formatToArray($titleName,'big','center', true);

        // 分割线
        $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$size,'center');


        // 店铺名
        $formatArr[] = $this->printBase->formatToArray(L_('店铺名：') .$store['name'],$size);

        // 订单编号
        $formatArr[] = $this->printBase->formatToArray(L_('订单编号：') . $order['order_no'], $size);

        // 下单时间
        $order['create_time'] && $formatArr[] = $this->printBase->formatToArray(L_('下单时间：') . $order['create_time'],$size);

        if($order['express_style'] == '1'){
            // 期望送达时间
            $formatArr[] = $this->printBase->formatToArray(L_('期望送达时间：') . $order['express_send_time'],$size);
        }
        if(isset($order['paid_time']) && $order['paid_time']){
            // 支付时间
            $formatArr[] = $this->printBase->formatToArray(L_('支付时间：') . $order['paid_time'],$size);
            // 支付方式
            $formatArr[] = $this->printBase->formatToArray(L_('支付方式：') . $order['pay_type_txt'],$size);
            // 支付金额
            $formatArr[] = $this->printBase->formatToArray(L_('支付金额：') . get_format_number($order['money_real']),$size);
            if($printType=='refund'){
                // 退款金额
                $formatArr[] = $this->printBase->formatToArray(L_('退款金额：') . get_format_number($order['refund_money']),$size);
            }
        }


        // 打印时间
        // $formatArr[] = $this->printBase->formatToArray(L_('打印时间：') . date('Y-m-d H:i:s'), $smallSize);
        return $formatArr;
    }

    /**
     *  餐饮打印商品数据
     *  @param $goods array 商品
     *  @param $print array 打印机
     *  @param $column int 打印列数
     *  @param $param array 其他参数
     *  return formatArr array 返回打印数组
     */
    public function foodshopBody($goods,$print, $param=[]){
        // 打印内容尺寸信息
        $smallSize = $param['size'] ?? 'small';
        $width = $param['width'] ?? '16';

        $formatArr = [];
        if(empty($goods)){
            return [];
        }

        $goodList = [];
        foreach($goods as $_goods){
            $goodList[] = [
                'name' => $_goods['goods_name'].((!empty($param['print_type'])&&$param['print_type']=='refund'&&!empty($_goods['goods_status'])&&$_goods['goods_status']==70)?' 【退款】':''),
                'num' => $_goods['num'],
                'total_price' => $_goods['price'],
                'spec' => $_goods['sku_info'] ?? '',
                'sub_list' => $_goods['sub_list'] ?? [],
            ];
        }
        // 分割线
        $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$smallSize,'center');

        $header = [
            [
                'title' => L_('商品'),
                'dataIndex' => 'name',
                'index' => 'first',
            ],
            [
                'title' => L_('数量'),
                'dataIndex' => 'num',
                'index' => 'second',
            ],
            [
                'title' => L_('价格'),
                'dataIndex' => 'total_price',
                'index' => 'third',
            ],
        ];
        

        $bodyArr = $this->printBase->createBodyByTable($goodList, $header, $print);

        $formatArr = array_merge($formatArr,$bodyArr);
        return $formatArr;
    }

    /**
     *  餐饮打印底部数据（菜品以下打印内容）
     **  @param $order array 订单
     *  @param $print array 打印机
     *  @param $param array 其他参数
     *  return formatArr array 返回打印数组
     */
    public function foodshopFooter($order,$param=[]){
        $staffName = $param['staff_name'] ?? ''; // 店员名字
        $printType = $param['print_type'] ?? ''; // 打印类型（非结账单预结单）0-结账单预结单 1-一菜一单 2-后厨补打
        $formatArr = [];


        // 打印内容尺寸信息
        $size = $param['size'] ?? 'small';
        $width = $param['width'] ?? '16';


        switch ($printType){
            case 'bill_account' ://结账单
                break;
        }

        // 分割线
        $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$size,'center');
        
        // 姓名
        $formatArr[] = $this->printBase->formatToArray(L_('姓名：') . $order['username'], $size);
        // 电话
        $formatArr[] = $this->printBase->formatToArray(L_('电话：') . $order['sh_phone'], 'middle');
        // 收货人地址
        if($order['express_style']==3){
            $formatArr[] = $this->printBase->formatToArray(L_('自提地址：') . $order['take_address'], 'middle');
        }else{
            $formatArr[] = $this->printBase->formatToArray(L_('收货人地址：') . $order['address'], 'middle');
        }

        //备注
        if (isset($order['remark']) && $order['remark']) {
            $formatArr[] = $this->printBase->formatToArray(L_('备注：') . $order['remark'], 'big');
        }

        $formatArr[] = $this->printBase->formatToArray(str_repeat(' ', $width * 2),$size,'center');

        return $formatArr;
    }


    public function printInternalMembershipCardTicket($data)
    {
        $printList = (new OrderprintService())->getPrintList(['store_id'=>$data['store_id']]);

        foreach ($printList['list'] as $_print) {
            if (isset($data['mkey']) && $data['mkey'] && $_print['mkey'] != $data['mkey']) {
                continue;
            }
            // 获得宽度
            $smallArr = array('is_big' => 0, 'paper' => $_print['paper']);
            $formatData = $this->printBase->getThreeData($smallArr);
            $widthSmall = $formatData['width'];

            $bigArr = array('is_big' => 2, 'paper' => $_print['paper']);
            $formatDataBig = $this->printBase->getThreeData($bigArr);
            $widthBig = $formatDataBig['width'];

            $middleArr = array('is_big' => 1, 'paper' => $_print['paper']);
            $formatDataMiddle = $this->printBase->getThreeData($bigArr);
            $widthMiddle = $formatDataMiddle['width'];

            // 只有最大号 和 大小区分 两种
            if ($_print['is_big'] == 2) {
                $size = 'big';
                $width = $widthBig;
            } elseif ($_print['is_big'] == 1) {
                $size = 'middle';
                $width = $widthMiddle;
            } else {
                $size = 'small';
                $width = $widthSmall;
            }


            $formatArr[] = $this->printBase->formatToArray('日结报表', 'big', 'center', true);

            // 店铺名
            $formatArr[] = $this->printBase->formatToArray(L_('店铺名称：') . $data['store_name'], $size);
            $formatArr[] = $this->printBase->formatToArray(L_('日期：') . $data['date'], $size);

            $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2), $size, 'center');

            $formatArr[] = $this->printBase->formatToArray(L_('总笔数：') . $data['pay_order_count'], $size);
            $formatArr[] = $this->printBase->formatToArray(L_('一卡通实收金额：') . $data['internal_card_pay_money'], $size);
            $formatArr[] = $this->printBase->formatToArray(L_('余额支付金额：') . $data['balance_pay_money'], $size);
            $formatArr[] = $this->printBase->formatToArray(L_('支付宝支付金额：') . $data['alipay_pay_money'], $size);
            $formatArr[] = $this->printBase->formatToArray(L_('微信支付金额：') . $data['wechat_pay_money'], $size);
            $formatArr[] = $this->printBase->formatToArray(L_('线下支付金额：') . $data['offline_pay_money'], $size);
            $formatArr[] = $this->printBase->formatToArray(L_('退款金额：') . $data['refund_money'], $size);
            $formatArr[] = $this->printBase->formatToArray(L_('收款总金额：') . $data['pay_money'], $size);

            $formatArr[] = $this->printBase->formatToArray(L_('打印时间：') . date('Y-m-d H:i:s'), $size);
            $formatArr[] = $this->printBase->formatToArray(L_('收银员：') . $data['staff_name'], $size);

            $this->printBase->toPrint($formatArr, $_print);
        }
    }
}