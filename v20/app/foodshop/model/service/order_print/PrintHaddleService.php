<?php
/**
 * 打印service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/16 14:45
 */

namespace app\foodshop\model\service\order_print;
use app\foodshop\model\service\order\DiningOrderDetailService;
use app\foodshop\model\service\order\DiningOrderPayService;
use app\merchant\model\service\print_order\PrintHaddleService as PrintHaddleBase;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\MerchantService;
use app\foodshop\model\service\order\DiningOrderService;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\merchant\model\service\print_order\PrintService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use think\facade\Db;
class PrintHaddleService {
    public $printBase = null;
    public function __construct()
    {
        $this->printBase = new PrintHaddleBase();
    }

    /**
     * 打印标签订单
     */
    public function printOrderLabel($order)
    {
        $orderId = $order['order_id'] ?? 0;
        $store_id = $order['store_id'] ?? 0;

        // 所有未支付商品
        $where = [
            ['order_id', '=', $orderId],
            ['status', 'in', '0,1,2'],
            ['num', 'exp', Db::raw(' > refundNum')],
            ['host_id', '=', '0'],
        ];
        $orderDetail = (new DiningOrderDetailService())->getOrderDetailByCondition($where);

        if (empty($orderDetail)) {
            return false;
        }

        $storeFoodshop = (new MerchantStoreFoodshopService())->getStoreByStoreId($store_id);
        if(!empty($storeFoodshop) && $storeFoodshop['settle_accounts_type'] == 1) {// 堂食先吃后付，提交订单后自动触发标签打印机
            return false;
        }

        $this->printLabel($orderDetail, $order);
    }


    public function printLabel($orderDetail, $order)
    {
        $store_id = $order['store_id'] ?? 0;
        // 组合附属商品
        $list = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);


        // 打印机列表
        $where = [
            ['r.store_id', '=', $store_id],
            ['r.reciept_type', '=', 2],// 小票类型：1-普通小票，2-标签小票
        ];
        $printList = (new DiningPrintRuleService())->getRuleAndPrintLabel($where);
        if (empty($printList)) {
            return false;
            throw new \think\Exception(L_('无可用打印机'), 1003);
        }

        $shop_notice_plat_name = cfg('shop_notice_plat_name');
        $plat_name_length = (strlen($shop_notice_plat_name) + mb_strlen($shop_notice_plat_name,'UTF8')) / 2;
        if($plat_name_length > 10){
            $shop_notice_plat_name = mb_strimwidth($shop_notice_plat_name,0, 10,'',"utf-8");
            $plat_name_length = 10;
        }
        $store = (new MerchantStoreService())->getStoreByStoreId($store_id);
        foreach ($printList as $_print) {
            // 档口选项：0-同一档口，1-指定分类，2-指定商品，单选
            $dangkouSelect = $_print['dangkou_select'];
            $sortList = [];
            $goodsList = [];
            switch ($dangkouSelect){
                case '1':
                    //打印绑定的分类下的商品
                    $where = [
                        'rule_id' => $_print['id']
                    ];
                    $sortList = array_column((new DiningPrintRuleGoodsService())->getSome($where),'bind_id');
                    break;
                case '2':
                    //打印绑定的商品
                    $where = [
                        'rule_id' => $_print['id']
                    ];
                    $goodsList = array_column((new DiningPrintRuleGoodsService())->getSome($where),'bind_id');
                    break;
            }

            if (!empty($sortList)) {
                foreach ($list as $s_key => $sort) {
                    if (!in_array($sort['sort_id'], $sortList)) {
                        unset($list[$s_key]);
                    }
                }
            }

            if (!empty($goodsList)) {
                foreach ($list as $g_key => $goods) {
                    if (!in_array($goods['goods_id'], $goodsList)) {
                        unset($list[$g_key]);
                    }
                }
            }

            $goodsCount = 0;//商品总数
            foreach ($list as $row) {
                $goodsCount += $row['num'];
            }

            $index = 0;
            foreach ($list as $_goods) {
                // 每个商品单独打印
                $i = $_goods['num'];
                while($i>0){
                    $index++;//当前第几个
                    $i--;
                    $format_str = '';

                    // 头部
                    $title = '#' . $order['fetch_number'];

                    $tmp_length = strlen($title);

                    if($shop_notice_plat_name){
                        $title = str_repeat(' ', 2).$title.str_repeat(' ', 2).$shop_notice_plat_name;
                    }else{
                        $title = str_repeat(' ', 12).$title;
                    }

                    $title .= str_repeat(' ', 2).$index.'/'.$goodsCount;

                    $format_str .= '<TEXT x="10" y="20" font="12" w="1" h="1" r="0">'.$title.'</TEXT>';

                    // 商品名称
                    $format_str .= '<TEXT x="10" y="50" font="12" w="1" h="2" r="0">'.$_goods['name'].'</TEXT>';
                    if($_goods['spec']){
                        $spec =  str_replace(',', '/', $_goods['spec']);
                        $spec =  str_replace('、', '/', $spec);
                        $format_str .= '<TEXT x="10" y="105" font="12" w="1" h="1" r="0">'.$spec.'</TEXT>';
                    }
                    $format_str .= '<TEXT x="10" y="160" font="12" w="1" h="1" r="0">'.date('Y-m-d H:i'). str_repeat(' ', 3).cfg('Currency_symbol') . get_format_number($_goods['price']).'</TEXT>';
                    $format_str .= '<TEXT x="10" y="190" font="12" w="1" h="1" r="0">'. $store['adress'].'</TEXT>';
                    fdump($format_str,'shopOrderLabel',1);
                    (new PrintService())->LabelPrint($_print, $format_str);
                }
            }
        }

        return true;
    }

    /**
     * 打印订单
     */
    public function printOrder($param, $staff = [])
    {
        fdump_api(func_get_args(),'diningOrderPrint',true);
//        return true;;
        $orderId = $param['order_id'] ?? 0;
        $type = $param['type'] ?? 0;//打印类型
        $goodsList = $param['goods_list'] ?? [];//后厨单打印内容
        $param['is_back'] =  $param['is_back'] ?? 0;//是否否退菜标识
        $param['old_status'] =  $param['old_status'] ?? 0;//订单变更前状态
        isset($staff['name']) && $param['staff_name'] = $staff['name'];//店员名称
        $param['staff_name'] = $param['staff_name'] ?? '';
        $param['repair_print'] = $param['repair_print'] ?? '0';//是否补打
        $param['pay_order_id'] = $param['pay_order_id'] ?? '0';//结账单打印 支付订单id
        $param['order_num'] = $param['order_num'] ?? '0';//打印单次提交的商品

        fdump($type,'goodsList',1);
        $diningOrderService = new DiningOrderService();
//        $type = ['menu'];;
        if (empty($orderId) || empty($type)){
            return false;
            throw new \think\Exception(L_('参数错误'), 1003);
        }

        $orderInfo = (new DiningOrderService())->getOrderByOrderId($orderId);

        $merchantInfo = (new MerchantService())->getMerchantByMerId($orderInfo['mer_id']);
        if (!empty($merchantInfo['merchant_default_lang'])) {
            cfg('tmp_system_lang',$merchantInfo['merchant_default_lang']);
        }

        // 订单详情
        $order = $diningOrderService->getPrintOrderDetail($orderId, $param['pay_order_id'], $param['order_num']);

        $param['table_info'] = $order['table_info'];
        $param['store'] = $order['store'];
        $order = $order['order'];

        if (empty($order)){
            throw new \think\Exception(L_('订单不存在'), 1003);
        }

        // 打印机列表
        $where = [
            ['r.store_id', '=', $order['store_id']],
            ['r.reciept_type', '=', 1],// 小票类型：1-普通小票，2-标签小票
        ];
        $printList = (new DiningPrintRuleService())->getRuleAndPrint($where);
        fdump_api($printList,'diningOrderPrint',true);
        if (empty($printList)) {
            return false;
            throw new \think\Exception(L_('无可用打印机'), 1003);
        }

        foreach ($type as $_type){
            switch ($_type){
                case 'customer_account' ://客看单
                    $param['print_type'] = 'customer_account';
                    $this->frontPrint($order, $printList, $param);
                    break;
                case 'pre_account' ://预结单
                    $param['print_type'] = 'pre_account';
                    // 只打印未支付的商品
                    $goodsList = (new DiningOrderDetailService())->getGoPayGoods($orderId,[],1, 0);
                    $orderPre = $order;
                    $orderPre['goods_num'] = $goodsList['goods_count']; // 已点菜品总数
                    $orderPre['order_num'] = 0; // 下单次数
                    $orderPre['go_pay_money'] = $goodsList['go_pay_money']; // 商品待支付总价
                    $orderPre['goods_total_price'] = $goodsList['goods_total_price']; // 商品总价
                    $orderPre['total_price'] = $goodsList['goods_total_price']; // 商品总价
                    $orderPre['goods_detail'] =  $goodsList['goods_list'];
                    if($orderPre['print_type'] == 1){// 分单打印
                        $orderPre['goods_list'] = (new DiningOrderDetailService())->getFormartGoodsDetailPrint($order,$orderPre['goods_detail'])['goods_list'];
                    }

                    $this->frontPrint($orderPre, $printList,$param);
                    break;
                case 'bill_account' ://结账单
                    $param['print_type'] = 'bill_account';
                    $this->frontPrint($order, $printList, $param);
                    break;
                case 'menu' ://后厨单
                    $param['print_type'] = 'menu';
                    $this->menuPrint($order, $printList, $param);
                    break;
            }
        }
        cfg('tmp_system_lang','');

        return true;
    }

    /**
     * 餐饮前台小票打印
     *  @param $order array 订单信息
     *  @param $printList array 打印机列表
     *  @param $param array 其他参数
     *  @return bool
     */
    public function frontPrint($order, $printList , $param=[])
    {
        // 打印类型 customer_account pre_account bill_account menu
        $printType = $param['print_type'];

        $isSinglePrint = $order['print_type'] ?? 0;// 分单打印

        if($isSinglePrint== 1){// 分单打印
            $order['goods_detail'] = $order['goods_list'];
        }
        $column = 2;//打印列数
        $frontPrint = 0;//前台打印类型
        switch ($printType){
            case 'customer_account' ://客看单
                $frontPrint = 1;
                break;
            case 'pre_account' ://预结单
                $frontPrint = 2;
                $column = 3;
                break;
            case 'bill_account' ://结账单
                $frontPrint = 3;
                $column = 3;
                break;
        }

        if(empty($order['goods_detail'])){
            return true;
        }

        foreach ($printList as $_print) {
            // 打印类型 1-前台小票 2-后厨小票
            $rulePrintType = explode(',',$_print['rule_print_type']);
            if (!in_array(1,$rulePrintType)) {
                // 不打印前台小票 跳过
                continue;
            }

            // 前台小票打印内容：1-客看单，2-预结账单，3-结账单，多个,号分割
            $frontPrintArr = explode(',',$_print['front_print']);
            if (!in_array($frontPrint,$frontPrintArr)) {
                // 不打印对应类型 跳过
                continue;
            }


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


            // 打印内容头部信息
            $formatArr = $this->foodshopHeader($order, $_print, $param);

            // 打印内容商品信息
            $bodyArr = $this->foodshopBody($order['goods_detail'], $_print, $column, $param, $isSinglePrint);
            $formatArr = array_merge($formatArr, $bodyArr);

            // 打印内容底部信息
            $footerArr = $this->foodshopFooter($order, $_print, $param, $isSinglePrint);
            $formatArr = array_merge($formatArr, $footerArr);

            //打印张数
            // $printNumber = $_print['number'];
            // while($printNumber>0){
                // 去打印
                fdump($printType, 'print',1);
                // fdump($_print, 'print',1);
                $this->printBase->toPrint($formatArr, $_print);
            //     $printNumber--;
            // }
        }
    }

    /**
     * 餐饮后厨小票打印
     *  @param $order array 订单信息
     *  @param $printList array 打印机列表
     *  @param $param array 其他参数
     *  @return bool
     */
    public function menuPrint($order, $printList , $param=[])
    {
        if($order['is_self_take']){
            // 自取单不打印后厨单
            return true;
        }
        // 是否退菜 前台和后厨都打印
        $isBack = $param['is_back'] ?? 0;

        $isSinglePrint = $order['print_type'] ?? 0;// 分单打印

        $column = 2;//打印列数
        // 打印指定商品
        if(isset($param['goods_list']) && $param['goods_list']){
            $order['goods_detail'] = $param['goods_list'];
        }
        foreach ($order['goods_detail'] as $key => $_goods){
            if(isset($_goods['is_must']) && $_goods['is_must']){
                unset($order['goods_detail'][$key]);
            }

            $thisSortArr = [$_goods['sort_id']];
            //套餐
            if (isset($_goods['package_id']) && $_goods['package_id'] > 0) {
                $thisSortArr = array_unique(array_column($_goods['sub_list'], 'sort_id'));
            }
            $order['goods_detail'][$key]['sort_id_arr'] = $thisSortArr; 
        }

        foreach ($printList as $_print) {
            // 打印类型 1-前台小票 2-后厨小票
            $rulePrintType = explode(',',$_print['rule_print_type']);
            if (!in_array(2,$rulePrintType) && !$isBack) {
                // 不打印后厨小票 跳过
                continue;
            }

            // 后厨小票打印内容：后厨小票打印内容：1-一菜一单，2-整单打印，多个,号分割
            $backPrintArr = explode(',',$_print['back_print']);

            // 档口选项：0-同一档口，1-指定分类，2-指定商品，单选
            $dangkouSelect = $_print['dangkou_select'];


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

            $printBindGoods = [];//每个打印机对应打印的商品
            switch ($dangkouSelect){
                case '0':
                    // 打印所有商品
                    $printBindGoods = $order['goods_detail'];
                    break;
                case '1':
                    //打印绑定的分类下的商品
                    $where = [
                        'rule_id' => $_print['id']
                    ];
                    $sortList = array_column((new DiningPrintRuleGoodsService())->getSome($where),'bind_id');
//                    var_dump($sortList);
//                    var_dump($order['goods_detail']);
                    foreach ($order['goods_detail'] as $goods){
                        if(in_array($goods['sort_id'], $sortList)){
                            $printBindGoods[] = $goods;
                        }else if(isset($goods['sort_id_arr']) && array_intersect($goods['sort_id_arr'],$sortList)){
                            $printBindGoods[] = $goods;
                        }
                    }
                    break;
                case '2':
                    //打印绑定的商品
                    $where = [
                        'rule_id' => $_print['id']
                    ];
                    $goodsList = array_column((new DiningPrintRuleGoodsService())->getSome($where),'bind_id');
                    foreach ($order['goods_detail'] as $goods){
                        if($goods['sub_list']){//有附属菜，附属菜也只展示绑定的商品
                            $isShgow = false;
                            foreach ($goods['sub_list'] as $kSub=>$subGoods){
                                if(!in_array($subGoods['goods_id'],$goodsList)){
                                    unset($goods['sub_list'][$kSub]);
                                }else{
                                    $isShgow = true;
                                }
                            }
                            if($isShgow){
                                $printBindGoods[] = $goods;
                            }
                        }elseif(in_array($goods['goods_id'], $goodsList)){
                            $printBindGoods[] = $goods;
                        }
                    }
                    break;
            }

            if(empty($printBindGoods)){
                continue;
            }

            foreach ($backPrintArr as $backPrint){
                switch ($backPrint){
                    case '1':
                        // 一菜一单
                        foreach ($printBindGoods as $goods){
                            // 打印内容头部信息
                            $formatArr = $this->foodshopHeader($order, $_print, $param);

                            // 打印内容商品信息
                            $bodyArr = $this->foodshopBody([$goods], $_print, $column,$param);
                            $formatArr = array_merge($formatArr, $bodyArr);

                            // 打印内容底部信息
                            $footerArr = $this->foodshopFooter($order, $_print, $param,$param);
                            $formatArr = array_merge($formatArr, $footerArr);

                            //打印张数
                            $printNumber = $_print['number'];
                            while($printNumber>0){
                                // 去打印
                                fdump('一菜一单', 'print',1);
                                // fdump($_print, 'print',1);
                                $this->printBase->toPrint($formatArr, $_print);
                                $printNumber--;
                            }
                        }
                        break;
                    case '2':
                        // 整单打印
                        $tempGoodsList = [];
                        if($isSinglePrint == 1){// 分单打印
                            $tempGoodsList = (new DiningOrderDetailService())->getFormartGoodsDetailPrint($order,$printBindGoods)['goods_list'];
                        }
                        // 打印内容头部信息
                        $formatArr = $this->foodshopHeader($order, $_print, $param);
                        // 打印内容商品信息
                        $bodyArr = $this->foodshopBody($tempGoodsList ?: $printBindGoods, $_print, $column,[],$isSinglePrint);
                        $formatArr = array_merge($formatArr, $bodyArr);

                        // 打印内容底部信息
                        $footerArr = $this->foodshopFooter($order, $_print, $param, $isSinglePrint);
                        $formatArr = array_merge($formatArr, $footerArr);

                        //打印张数
                        $printNumber = $_print['number'];
                        while($printNumber>0){
                            // 去打印
                            fdump('整单', 'print',1);
                            // fdump($_print, 'print',1);
                            $this->printBase->toPrint($formatArr, $_print);
                            $printNumber--;
                        }
                        break;
                }
            }
        }
    }

    /*
     *  定制餐饮整单打印不打印未勾选打印机的商品 
     *  param order array 订单详情
     *  param printGoodsArr array 商品数组
     *  param printList array 打印机列表
     *  param staffName string 店员名字
     *  param isBack int 是否退菜
    */
    public function printBelongGoods($order,$printGoodsArr,$printList,$isBack='0', $staffName = ''){
        //分单打印
        foreach ($printGoodsArr as $pintid => $goodsList) {
            $mainPrint = isset($printList[$pintid]) ? $printList[$pintid] : null;
            if (empty($mainPrint)) {
                continue;
            }

            // 打印方式
            if ($mainPrint['is_main'] == 0 && $mainPrint['print_way'] == 1) {// 副打印机 一菜一单不打印整单
                continue;
            }
           
            // 打印内容头部信息
            $formatArr = $this->foodshopHeader($order,$mainPrint,$staffName,1,[],$isBack);
               
            // 获得数量和价格的最大长度（和下面名字组合判断是否换行）
            $numPriceLength = 0;
            foreach ($goodsList as $val) {
                $num_str = '×'.$val['num'] .'  '.floatval($val['price']);
                $tmp_length = (strlen($num_str) + mb_strlen($num_str,'UTF8')) / 2;
                if ($tmp_length>$numPriceLength) {
                    $numPriceLength = $tmp_length;
                }
            }

            foreach ($goodsList as $val) {
                // 打印内容商品信息
                $returnArr = $this->foodshopBody($val,$mainPrint,$numPriceLength,$isBack);
                $formatArr = array_merge($formatArr,$returnArr);
            }
            // 打印内容底部信息
            $footerArr = $this->foodshopFooter($order,$mainPrint,$staffName,1,[]);
            $formatArr = array_merge($formatArr,$footerArr);

            // 去打印
            $this->printHaddleBase->toPrint($formatArr, $mainPrint);
        }
        return true;
    }

    /**
     *  餐饮打印头部数据（菜品以上打印内容）
     *  @param $order array 订单详情
     *  @param $print array 打印机
     *  @param $staffName string 店员名字
     *  @param $printType int 打印类型（非结账单预结单）0-结账单预结单 1-一菜一单 2-后厨补打
     *  @param $store array 店铺信息
     *  @param $isBack int 是否退菜
     *  @param $repairPrint int 是否补打
     *  return $formatArr array 返回打印数组
     */
    public function foodshopHeader($order, $print, $param=[]){
        $staffName = $param['staff_name'] ?? ''; // 店员名字
        $printType = $param['print_type'] ?? ''; // 打印类型（非结账单预结单）0-结账单预结单 1-一菜一单 2-后厨补打
        $store = $param['store'] ?? [];// 店铺信息
        $isBack = $param['is_back'] ?? 0;// 是否退菜
        $repairPrint = $param['repair_print'] ?? 0;//是否补打
        $orderNum = $param['order_num'] ?? 0;//第几次下单

        // 打印内容尺寸信息
        $smallSize = $param['smallSize'] ?? 'small';
        $middleSize = $param['middleSize'] ?? 'middle';
        $bigSize = $param['bigSize'] ?? 'big';
        $width = $param['width'] ?? '16';

        // 打印内容数组
        $formatArr = [];

        // 打印标题
        switch ($printType){
            case 'customer_account' ://客看单
                // 店铺名
                $formatArr[] = $this->printBase->formatToArray($store['name'],'big','center');
                $title = "客看单";
                break;
            case 'pre_account' ://预结单
                $formatArr[] = $this->printBase->formatToArray($store['name'],'big','center');
                $title = "预结单";

                break;
            case 'bill_account' ://结账单
                $formatArr[] = $this->printBase->formatToArray($store['name'],'big','center');
                $title = "结账单";

                break;
            case 'menu' ://后厨单
                if ($isBack == 1) {
                    $title = "退菜单";
                }else{
                    $title = "后厨单";
                }
                break;
        }

        // 先付后吃加菜次数
        $count = 0;
        if($order['settle_accounts_type'] == 2){//先付后吃
            $where = [
                'order_id' => $order['order_id'],
                'order_type' => 1,
                'paid' => 1,
            ];
            $count = (new DiningOrderPayService())->getCount($where);

        }

        if($repairPrint && $printType != 'pre_account'){
            // 补打标识
            $title .= "补打";
        }elseif(!$isBack){
            //加菜标识
            if(in_array($printType,['menu', 'bill_account'])){
                if ($orderNum > 1 || $count > 1) {
                    $title .= "--加菜";
                }


            }elseif($printType == 'customer_account'){
                if ($orderNum > 1) {
                    $title .= "--加菜(".($orderNum-1).')';
                }elseif($count > 1){
                    $title .= "--加菜(".($count-1).')';
                }

            }
        }

        // 标题
        $formatArr[] = $this->printBase->formatToArray($title,'big','center', true);

        // 分割线
        $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$smallSize,'center');

        // 取餐号
//        if($printType == 'bill_account' && in_array($order['order_from'],[4,5])){
            $order['fetch_number'] && $formatArr[] = $this->printBase->formatToArray(L_('取餐号：') . $order['fetch_number'],$bigSize);
//        }
        if($order['is_self_take']){
            $order['self_take_time'] && $formatArr[] = $this->printBase->formatToArray(L_('取餐时间：') . $order['self_take_time'],$bigSize);
        }

        // 桌台信息
        if(isset($param['table_info']) && $param['table_info']){
//            (isset($param['table_info']['table_type_name']) && $param['table_info']['table_type_name']) && $formatArr[] = $this->printBase->formatToArray('桌台类型：' . $param['table_info']['table_type_name'],$bigSize);
            (isset($param['table_info']['table_name']) && $param['table_info']['table_name']) && $formatArr[] = $this->printBase->formatToArray("桌号：" . $param['table_info']['table_name'],$bigSize);

        }

        // 就餐人数
        $order['book_num'] && $formatArr[] = $this->printBase->formatToArray(L_('人数：') . $order['book_num'], $smallSize);

        if(in_array($printType,['customer_account', 'bill_account', 'pre_account'])) {
            // 订单编号
            $formatArr[] = $this->printBase->formatToArray(L_('订单编号：') . $order['real_orderid'], $smallSize);

            // 操作人
            $staffName && $formatArr[] = $this->printBase->formatToArray(L_('操作人：') . $staffName, $smallSize);

            // 下单时间
            $formatArr[] = $this->printBase->formatToArray(L_('下单时间：') . $order['create_time_str'], $smallSize);
        }

        if(in_array($printType,['pre_account'])) {
            // 打印时间
            $formatArr[] = $this->printBase->formatToArray(L_('打印时间：') . date('Y-m-d H:i:s'), $smallSize);
        }

        if(in_array($printType,['bill_account'])) {
            // 订单类型
            $formatArr[] = $this->printBase->formatToArray(L_("订单类型：") . ($order['is_self_take'] ? L_('自取') : L_('堂食')),$smallSize);

            // 结账时间
            (isset($order['pay_time']) && $order['pay_time']) && $formatArr[] = $this->printBase->formatToArray('结账时间：' .  $order['pay_time'], $smallSize);
        }
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
    public function foodshopBody($goods,$print,$column,$param=[], $isSinglePrint = 0){
        // 打印内容尺寸信息
        $smallSize = $param['smallSize'] ?? 'small';
        $width = $param['width'] ?? '16';
        $isBack = $param['is_back'] ?? '0';//是否退菜

        $formatArr = [];
        if(empty($goods)){
            return [];
        }

        // 退菜
        if ($isBack==1) {
//            $goods['name'] = '【退】'. $goods['name'];
        }

        // 分割线
        $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$smallSize,'center');

        if($column==2){//一行两列
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
            ];
        }elseif($column==3){//一行四列
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
        }

        if($isSinglePrint){
            $bodyArr = [];
            foreach($goods as $_goods){
                if(isset($_goods['goods_combine']) && $_goods['goods_combine']){
                    foreach($_goods['goods_combine']as $_goods_combine){
                        $_tempPrintArr = [];

                        if(isset($_goods_combine['goods']) && $_goods_combine['goods']){// 存在商品
                            // 头部
                            $_tempPrintArr[] = $this->printBase->formatToArray($_goods_combine['number_str'], $smallSize, 'center');

                            // 商品
                            $_tempBodyArr = $this->printBase->createBodyByTable($_goods_combine['goods'], $header, $print);
                            $_tempPrintArr = array_merge($_tempPrintArr, $_tempBodyArr);

                            // 备注
                            $_goods_combine['goods_note'] && $_tempPrintArr[] = $this->printBase->formatToArray(L_('备注：') . $_goods_combine['goods_note'], 'middle');

                            $bodyArr = array_merge($bodyArr, $_tempPrintArr);
                        }
                    }
                }
            }
        }else{
            $bodyArr = $this->printBase->createBodyByTable($goods, $header, $print);
        }
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
    public function foodshopFooter($order,$print,$param=[], $isSinglePrint = 0){
        $staffName = $param['staff_name'] ?? ''; // 店员名字
        $printType = $param['print_type'] ?? ''; // 打印类型（非结账单预结单）0-结账单预结单 1-一菜一单 2-后厨补打
        $store = $param['store'] ?? [];// 店铺信息
        $orderNum = $param['order_num'] ?? 0;// 下单次数
        $isBack = $param['is_back'] ?? 0;// 退菜
        $formatArr = [];


        // 打印内容尺寸信息
        $smallSize = $param['smallSize'] ?? 'small';
        $middleSize = $param['middleSize'] ?? 'middle';
        $bigSize = $param['bigSize'] ?? 'big';
        $width = $param['width'] ?? '16';


        // 每次下单的备注
        if($orderNum && !$isSinglePrint){
            $goodsNote = $order['goods_note'] ? unserialize($order['goods_note']) : '';
            $note = $goodsNote ? $goodsNote[$orderNum-1] : '';
            $note && $formatArr[] = $this->printBase->formatToArray(L_('备注：') . $note,$middleSize);
        }

        if ($isBack == 1) {
//            $formatArr[] = $this->printBase->formatToArray(L_('退菜原因：') . $order['cancel_reason'],$middleSize);
        }

        // 打印标题
        switch ($printType){
            case 'customer_account' ://客看单
                break;
            case 'pre_account' ://预结单
                if (!empty($order['note']) && $order['note'] != '无') {
                    // 分割线
                    $formatArr[] = $this->printBase->formatToArray(L_('备注：') . $order['note'],$middleSize);
                    $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$smallSize,'center');
                }
                // 分割线
                $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$smallSize,'center');

                $formatArr[] = $this->printBase->formatToArray(L_('原价合计：') . floatval($order['total_price']),$middleSize);
                $formatArr[] = $this->printBase->formatToArray(L_('应付金额：') . floatval($order['total_price']),$middleSize);
                // 分割线
                $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$smallSize,'center');

                // 店铺地址
                $formatArr[] = $this->printBase->formatToArray($store['adress'],$middleSize,'center');
                $formatArr[] = $this->printBase->formatToArray($store['phone'],$middleSize,'center');
                $formatArr[] = $this->printBase->formatToArray(L_('欢迎下次光临'),$middleSize,'center');

                break;
            case 'bill_account' ://结账单
                if (!empty($order['note']) && $order['note'] != '无') {
                    $formatArr[] = $this->printBase->formatToArray(L_('备注：') . $order['note'],$middleSize);
                }

                // 分割线
                $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$smallSize,'center');

                $formatArr[] = $this->printBase->formatToArray(L_('原价合计：') . floatval($order['total_price']),$middleSize);
                //$order['discount_price'] && $formatArr[] = $this->printBase->formatToArray(L_('优惠合计：') .'-'. floatval($order['discount_price']),$middleSize);
                ($order['total_price'] > $order['pay_price']) && $formatArr[] = $this->printBase->formatToArray(L_('优惠合计：') . '-' . bcsub((string)$order['total_price'], (string)$order['pay_price'], 2), $middleSize);
                $formatArr[] = $this->printBase->formatToArray(L_('实付金额：') . floatval($order['pay_price']),$middleSize);

                $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$smallSize,'center');

                // 店铺地址
                $formatArr[] = $this->printBase->formatToArray($store['adress'],$middleSize,'center');
                $formatArr[] = $this->printBase->formatToArray($store['phone'],$middleSize,'center');
                $formatArr[] = $this->printBase->formatToArray(L_('欢迎下次光临'),$middleSize,'center');

                break;
            case 'menu' ://后厨单
                // 分割线
                $formatArr[] = $this->printBase->formatToArray(str_repeat('-', $width * 2),$smallSize,'center');
                // 店铺地址
                $formatArr[] = $this->printBase->formatToArray(L_('单号：').$order['real_orderid'],$smallSize);
                $staffName && $formatArr[] = $this->printBase->formatToArray(L_('操作人：').$staffName,$smallSize);
                $formatArr[] = $this->printBase->formatToArray(L_('打印时间：').date('Y-m-d H:i:s'),$smallSize);
                break;
        }

//        $formatArr[] = $this->printBase->formatToArray(str_repeat(' ', $width * 2),$smallSize,'center');
        return $formatArr;
    }

    /**
     * 排号小票打印
     * @param $queue array 排号信息
     * @param $param array 其他参数
     * @return bool
     */
    public function frontPrintQueue($queue, $param = [])
    {
        // 店铺信息
        $store = (new \app\merchant\model\service\MerchantStoreService())->getStoreByStoreId($param['store_id']);

        // 店铺综合二维码
        $returnArr = (new \app\merchant\model\service\MerchantStoreService())->seeQrcode($param['store_id']);
        $qrcode = '';
        if (isset($returnArr['qrcode']) && !empty($returnArr['qrcode'])) {
            $qrcode = $returnArr['qrcode'];
        }

        // 排号小票打印机列表
        $condition_where = [];
        $condition_where[] = ['r.store_id', '=', $param['store_id']];
        $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET('3',r.print_type)")];
        $diningPrintRuleService = new DiningPrintRuleService();
        $printList = $diningPrintRuleService->getRuleAndPrint($condition_where);

        foreach ($printList as $_print) {
            // 打印类型 1-前台小票 2-后厨小票 3-排号小票
            $rulePrintType = explode(',', $_print['rule_print_type']);
            if (!in_array(1, $rulePrintType)) {
                // 不打印前台小票 跳过
                continue;
            }

            // 获得宽度
            $smallArr = array('is_big' => 0, 'paper' => $_print['paper']);
            $formatData = $this->printBase->getThreeData($smallArr);
            $widthSmall = $formatData['width'];

            $bigArr = array('is_big' => 2, 'paper' => $_print['paper']);
            $formatDataBig = $this->printBase->getThreeData($bigArr);
            $widthBig = $formatDataBig['width'];

            // 只有最大号 和 大小区分 两种
            if ($_print['is_big'] == 2) {
                $param['smallSize'] = 'big';
                $param['middleSize'] = 'big';
                $param['bigSize'] = 'big';
                $param['width'] = $widthBig;
            } else {
                $param['smallSize'] = 'small';
                $param['middleSize'] = 'middle';
                $param['bigSize'] = 'big';
                $param['width'] = $widthSmall;
            }

            // 打印内容头部信息
            $formatArr = [];
            $formatArr[] = $this->printBase->formatToArray(L_('排队小票'), 'big', 'center');

            // 打印内容信息
            $formatArr[] = $this->printBase->formatToArray(L_('您的排队号码：'), 'middle');
            $formatArr[] = $this->printBase->formatToArray($queue['number'], 'big', 'left', true);
            $formatArr[] = $this->printBase->formatToArray(date('Y-m-d H:i:s', $queue['create_time']), 'small');
            $formatArr[] = $this->printBase->formatToArray(L_('商家名称：') . $store['name'], 'small');
            $formatArr[] = $this->printBase->formatToArray(L_('您的前面还有 ') . $param['count'] . L_(' 位在等待，请扫码关注您的排队进程。'), 'small');
            if (!empty($qrcode)) {
                $formatArr[] = $this->printBase->formatToArray($qrcode, 'small', 'center', false, 'image');
            }

            // 店铺地址
            $formatArr[] = $this->printBase->formatToArray(L_('电话：') . $store['phone'], 'small');
            $formatArr[] = $this->printBase->formatToArray(L_('地址：') . $store['adress'], 'small');

            $formatArr[] = $this->printBase->formatToArray(L_('号码仅限当日有效；过号请重新取号，谢谢配合！'), 'small');

            //打印张数
            $printNumber = $_print['number'];
            while ($printNumber > 0) {
                // 去打印
                $this->printBase->toPrint($formatArr, $_print);
                $printNumber--;
            }
        }
    }
}