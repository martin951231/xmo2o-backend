<?php
/**
 * 打印service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/16 14:45
 */

namespace app\foodshop\model\service\store;
use app\merchant\model\service\print_order\PrintHaddleService as PrintHaddleBase;
use app\merchant\model\service\MerchantStoreService as MerchantStoreService;
use app\foodshop\model\service\order\DiningOrderService as DiningOrderService;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService as FoodshopGoodsLibraryService;
class PrintHaddleService {
    public $printBase = null;
    public function __construct()
    {
        $this->printBase = new PrintHaddleBase();
    }
    
    /*
     *  餐饮打印模式
     *  param orderId int 订单id
     *  param status int 触发打印机制6-自动打印预结账单（用户或店员下单成功后）7-自动打印结账单（用户支付成功后，含店员收款成功后） -1 不限制（手动打印）
     *  param $isPrint 是否不打印后厨菜单 0-后厨补打 
     *  param staffName string 店员名字
    */
    public function foodshopOrder($orderId, $status = 0, $isPrint = 0, $staffName = '')
    {
        return true;
        $diningOrderService = new DiningOrderService();

        // 订单详情
        $order = $diningOrderService->getPrintOrderDetail($orderId);
        $order = $order['order'];

        if($order['status'] == 2){

        }

        // 打印机列表
        $printList = $this->printBase->getPrintListByStoreId($order['store_id']);
        if (empty($printList)) return false;
        
        // 店铺详情
        $store = (new MerchantStoreService())->getStoreByStoreId($order['store_id']);

        if ($isPrint == 0 ) { // 后厨补打
            $this->foodshopOrderEvery($order, $printList, $status, 0, $staffName);
            return true;
        }
        
        foreach ($printList as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }

            // 打印内容头部信息
            $formatArr = $this->foodshopHeader($order,$usePrinter,$staffName,0,$store,0);

            // 获得数量和价格的最大长度（和下面名字组合判断是否换行）
            $numPriceLength = 0;
            foreach ($order['goods_detail'] as $val) {
                $num_str = '×'.$val['num'] .'  '.floatval($val['price']);
                $tmp_length = (strlen($num_str) + mb_strlen($num_str,'UTF8')) / 2;
                if ($tmp_length>$numPriceLength) {
                    $numPriceLength = $tmp_length;
                }
            }

            if ($order['status'] == 2) { // 就餐中
                $order['total_price'] = $order['goods_total_price'];
            }

            foreach ($order['goods_detail'] as $val) {
                // 打印内容商品信息
                $bodyArr = $this->foodshopBody($val, $usePrinter, $numPriceLength);
                $formatArr = array_merge($formatArr, $bodyArr);
            }

            // 打印内容底部信息
            $footerArr = $this->foodshopFooter($order, $usePrinter, $staffName, 0, $store);
            $formatArr = array_merge($formatArr, $footerArr);
        
            // 去打印
            $this->toPrint($formatArr, $usePrinter);
        }
    } 

     /*
     *  打印商品:一菜一单或整单菜品 
     *  param orderId int 订单id
     *  param menus array 本次修改商品数组
     *  param status int 订单状态
     *  param $printType 0-整单打印 1-一菜一单
     *  param staffName string 店员名字
     *  param isBack int 是否退菜
     *  param oldStatus int 订单修改前状态
    */
    public function foodshopMenu($orderId, $menus, $printType = 1, $isBack = 0, $staffName='', $oldStatus = 0)
    {
        return true;
        $diningOrderService = new DiningOrderService();

        // 订单详情
        $order = $diningOrderService->getPrintOrderDetail($orderId);
        $order = $order['order'];

        // 打印机列表
        $printList = $this->printBase->getPrintListByStoreId($order['store_id']);
        if (empty($printList)) return false;

        // 餐饮店铺
        $foodshopStore = (new MerchantStoreFoodshop())->getStoreByStoreId($order['store_id']);
        

        // 排除主打印机设置不打印后厨的打印机
        foreach ($printList as $key=>$l) {
            if ($l['is_main']&&$l['is_print_menu']==0) {
                unset($printList[$key]);
                continue;
            }
        }
        if (empty($printList)) return false;

        $list = $order['goods_detail'];
        if ($isBack == 1) {
            $list = $menus;
        }
        $goods_ids = array();
        $newList = array();
        foreach ($list as $row) {
            $goods_ids[] = $row['id'];
            $goods_gids[] = $row['goods_id'];
            $newList[$row['id']] = $row;
        }
        if ($goods_ids) {
            // 获取商品列表
            $where['goods_ids'] =  implode(',', $goods_gids);
            $where['store_id'] =  $order['store_id'];
            $where['select_sort'] = 1;
            $resultList = (new FoodshopGoodsLibraryService())->getGoodsList($where);
            $goodsList = $resultList['list'];

            // 获取打印机配置
            foreach($newList as  &$nlist){
                foreach($goodsList as $good){
                    if($nlist['goods_id']==$good['goods_id']){
                        // 如果设置分类打印机，使用分类打印机设置
                        $nlist['print_id'] = ($good['is_open_print'] && $good['sort_print_id']) ? $good['sort_print_id'] : $good['print_id'];
                    }
                }
            }

            // 生成打印机关联菜品数组
            $printGoodsArr = array();
            foreach ($menus as &$_menus) {
                foreach ($newList as $m) {
                    if ($_menus['goods_id']==$m['goods_id']) {
                        $_menus['print_id'] = explode(',', $m['print_id']);
                    }
                }
                if ($_menus['print_id']) {
                    foreach ($_menus['print_id'] as $key => $value) {
                        if (isset($printGoodsArr[$value])) {
                            $printGoodsArr[$value][] = $_menus;
                        } else {
                            $printGoodsArr[$value] = array($_menus);
                        }
                    }
                }
                
            }

            //分单打印（每个打印机分别打印）
            foreach ($printGoodsArr as $pintId => $goodsList) {
                $mainPrint = isset($printList[$pintId]) ? $printList[$pintId] : null;
                if (empty($mainPrint)) {
                    continue;
                }

                // 打印方式
                if ($mainPrint['print_way'] == 2) {// 整单打印不打印一菜一单
                    continue;
                }

                // 整单菜品
                if ($printType == 0) {
                    // 打印内容头部信息
                    $formatArr = $this->foodshopHeader($order,$mainPrint,$staffName,1,[],$isBack);
                }

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
                    // 一菜一单
                    if ($printType == 1) {
                        // 打印内容头部信息
                        $formatArr = $this->foodshopHeader($order,$mainPrint,$staffName,1,[],$isBack);
                    }

                    // 打印内容商品信息
                    $returnArr = $this->foodshopBody($val,$mainPrint,$numPriceLength,$isBack);
                    $formatArr = array_merge($formatArr,$returnArr);
                    
                    if ($printType == 1) {
                        // 打印内容底部信息
                        $footerArr = $this->foodshopFooter($order,$mainPrint,$staffName,1,[]);
                        $formatArr = array_merge($formatArr,$footerArr);

                        // 去打印
                        $this->printHaddleBase->toPrint($formatArr, $mainPrint);
                    }
                }
                if ($printType == 0) {
                    // 打印内容底部信息
                    $footerArr = $this->foodshopFooter($order,$mainPrint,$staffName,1,[]);
                    $formatArr = array_merge($formatArr,$footerArr);

                    // 去打印
                    $this->printHaddleBase->toPrint($formatArr, $mainPrint);
                }
            }

            // 定制餐饮整单打印不打印未勾选打印机的商品
            if (cfg('print_belong_goods')==1) {
                $this->printBelongGoods($order,$printGoodsArr,$printList, $isBack, $staffName);
            }else{
                 //打印全部(主打印机)
                foreach ($printList as $mainPrint) {

                    // 打印方式
                    if ($mainPrint['is_main'] == 0 && $mainPrint['print_way'] == 1) {// 副打印机 一菜一单不打印整单
                        continue;
                    }                
                    $printAllStr = $this->foodshopHeader($order,$mainPrint,$staffName,1,[],$isBack);

                    $hasGoods = false;
                    foreach ($menus as $l) {
                        if ($foodshopStore['print_belong_goods']==1 && !in_array($mainPrint['pigcms_id'], $l['print_id'])) {
                            continue;
                        }
                        $hasGoods = true;

                        // 打印内容商品信息
                        $returnArr = $this->foodshopBody($l,$mainPrint,$numPriceLength,$isBack);
                        $printAllStr = array_merge($printAllStr,$returnArr);
                    }
                    if ($printAllStr&&$hasGoods) {
                        // 打印内容底部信息
                        $footerArr = $this->foodshopFooter($order,$mainPrint,$staffName,1,[]);
                        $printAllStr = array_merge($printAllStr,$footerArr);

                        // 去打印
                        $this->printHaddleBase->toPrint($printAllStr, $mainPrint);
                    }
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

    /*
     *  param order array 订单详情
     *  param printList array 打印机列表
     *  param status int 订单状态
     *  param printType 1-一菜一单，0-打印整单
     *  param staffName string 店员名字
     *  param isBack int 是否退菜
    */
    public function foodshopOrderEvery($order, $printList, $status, $printType = 1, $staffName = '')
    {
        // 排除主打印机设置不打印后厨的打印机
        foreach ($printList as $key=>$l) {
            if ($l['is_main']&&$l['is_print_menu']==0) {
                unset($printList[$key]);
                continue;
            }
        }   
        if (empty($printList)) return false;

        if ($order['goods_detail']) {
            $list = $order['goods_detail'];
            $goods_ids = array();
            $newList = array();
            foreach ($list as $row) {
                $goods_ids[] = $row['id'];
                $goods_gids[] = $row['goods_id'];
                $newList[$row['id']] = $row;
            }
            if ($goods_ids) {
                // 获取商品列表
                $where['goods_ids'] =  implode(',', $goods_gids);
                $where['store_id'] =  $order['store_id'];
                $where['select_sort'] = 1;
                $resultList = (new FoodshopGoodsLibraryService())->getGoodsList($where);
                $goodsList = $resultList['list'];

                // 获取打印机配置
                foreach($newList as  &$nlist){
                    foreach($goodsList as $good){
                        if($nlist['goods_id']==$good['goods_id']){
                            // 如果设置分类打印机，使用分类打印机设置20180917
                            $nlist['print_id'] = ($good['is_open_print'] && $good['sort_print_id']) ? $good['sort_print_id'] : $good['print_id'];
                        }
                    }
                }

                $printGoodsArr = array();
                foreach ($newList as $m) {
                    $m['print_id'] = explode(',', $m['print_id']);
                    if ($m['print_id']) {
                        foreach ($m['print_id'] as $key => $value) {
                            if (isset($printGoodsArr[$value])) {
                                $printGoodsArr[$value][] = $newList[$m['id']];
                            } else {
                                $printGoodsArr[$value] = array($newList[$m['id']]);
                            }
                        }
                    }
                }

                 foreach ($newList as $m) {
                    if ($_menus['goods_id']==$m['goods_id']) {
                        $_menus['print_id'] = explode(',', $m['print_id']);
                    }
                }
                
                foreach ($printGoodsArr as $pintId => $goodsList) {
                    $mainPrint = isset($printList[$pintId]) ? $printList[$pintId] : null;
                    if (empty($mainPrint)) {
                        continue;
                    }
                    
                    if ($status != -1) {
                        $statusArr = explode(',', $mainPrint['paid']);
                        if (!in_array($status, $statusArr)) {
                            continue;
                        }
                    }
                    
                    $formatData = $this->printHaddleBase->getTwoData($mainPrint);
                    $width = $formatData['width'];
                    $firstSpace = $formatData['one'];
                    $secondSpace = $formatData['two']+2;
                    
                    if ($printType == 0) {//整单
                        // 打印内容头部信息
                        $formatArr = $this->foodshopHeader($order,$mainPrint,$staffName,1,[],0);
                    }
                    foreach ($goodsList as $l) {
                        if ($printType == 1) {//一菜一单
                            // 打印内容头部信息
                            $formatArr = $this->foodshopHeader($order,$mainPrint,$staffName,1,[],0);
                        }

                        // 打印内容商品信息
                        $returnArr = $this->foodshopBody($val,$mainPrint,$numPriceLength,$isBack);
                        $formatArr = array_merge($formatArr,$returnArr);

                        if ($printType == 1) {
                            // 打印内容底部信息
                            $footerArr = $this->foodshopFooter($order,$mainPrint,$staffName,1,[]);
                            $formatArr = array_merge($formatArr,$footerArr);

                            // 去打印
                            $this->printHaddleBase->toPrint($formatArr, $mainPrint);
                        }
                    }
                    if ($printType == 0) {
                        // 打印内容底部信息
                        $footerArr = $this->foodshopFooter($order,$mainPrint,$staffName,1,[]);
                        $formatArr = array_merge($formatArr,$footerArr);
                        
                        // 去打印
                        $this->printHaddleBase->toPrint($formatArr, $mainPrint);
                    }
                }
            }
        }
    } 

    /*
     *  餐饮打印头部数据（菜品以上打印内容）
     *  param order array 订单详情
     *  param print array 打印机
     *  param staffName string 店员名字
     *  param printType int 打印类型（非结账单预结单）0-结账单预结单 1-一菜一单 2-后厨补打
     *  param store array 店铺信息
     *  param isBack int 是否退菜
     *  return formatArr array 返回打印数组
     */
    public function foodshopHeader($order,$print,$staffName='',$printType=0,$store=[],$isBack=0){
        $formatArr = [];
        $smallArr = array('is_big'=>0,'paper'=>$print['paper']);
        $formatData = $this->printBase->getThreeData($smallArr);
        $widthSmall = $formatData['width'];

        $middleArr = array('is_big'=>1,'paper'=>$print['paper']);
        $formatDataMiddle = $this->printBase->getThreeData($middleArr);
        $widthMid = $formatDataMiddle['width'];

        $bigArr = array('is_big'=>2,'paper'=>$print['paper']);
        $formatDataBig = $this->printBase->getThreeData($bigArr);
        $widthBig = $formatDataBig['width'];

        // 只有最大号 和 大小区分 两种
        if ($mainPrint['is_big']==2) {
            $smallSize = 'big';
            $middleSize = 'big';
            $width = $widthBig;
        }else{
            $smallSize = 'small';
            $middleSize = 'middle';
            $width = $widthSmall;
        }

        if ($printType==1) {
            // 标题
            if ($isBack == 1) {
                $title = "#".$order['fetch_number']."退菜单";
            }else{
                if ($oldStatus<2) {
                    $title = "#".$order['fetch_number'].'点菜单';
                }else{
                    $title = "#".$order['fetch_number'].'加菜单';
                }
            }
        }elseif ($printType==2) {
            $title = "#".$order['fetch_number']."后厨-补打";
        }else{
            // 店铺名
            $formatArr[] = $this->printBase->formatToArray($store['name'],'big','center');
            
            // 头部标题
            if ($order['status'] <= 2) { // 预结单
                $title = '预结单';
            }else{
                $title ='结账单';
            }
        }
        $content =   '**'. $title.'**';
        $formatArr[] = $this->printBase->formatToArray($content,'big','center');

        // 分割线
        $formatArr[] = $this->printBase->formatToArray(str_repeat('*', $width * 2),$smallSize,'center'); 

        $formatArr[] = $this->printBase->formatToArray('订单编号:' . $order['real_orderid'],$smallSize);
        if ($printType==0) {
            $formatArr[] = $this->printBase->formatToArray("流水号：" . $order['fetch_number'],$smallSize);
            $order['name'] && $formatArr[] = $this->printBase->formatToArray('客户姓名：' . $order['name'],$smallSize);
            $order['phone'] && $formatArr[] = $this->printBase->formatToArray('客户手机：' . $order['phone'],$smallSize);
        }

        $formatArr[] = $this->printBase->formatToArray('桌台类型：' . $order['table_type_name'],$middleSize);
        $formatArr[] = $this->printBase->formatToArray("桌台名称：" . $order['table_name'],$middleSize);

        if ($printType==0) { //结账单预结单
            $formatArr[] = $this->printBase->formatToArray("订单来源：" . $this->order_from_arr[$order['order_from']],$smallSize);
            $formatArr[] = $this->printBase->formatToArray("操作员：" . $staffName,$smallSize);
            floatval($order['book_price']) && $formatArr[] = $this->printBase->formatToArray('预定金额：' . floatval($order['book_price']),$smallSize);
        }

        if ($printType==1 || $printType==2) {//1-一菜一单 2-后厨补打
            $formatArr[] = $this->printBase->formatToArray("打印时间：" . date('Y-m-d H:i'),$smallSize);
        }

        // 分割线
        $formatArr[] = $this->printBase->formatToArray(str_repeat('*', $width * 2),$smallSize,'center'); 
        return $formatArr;
    }

    /*
     *  餐饮打印商品数据
     *  param goods array 商品
     *  param print array 打印机
     *  param numPriceLength float 商品价格与数量最大长度
     *  param isBack int 是否退菜
     *  return formatArr array 返回打印数组
     */
    public function foodshopBody($goods,$print,$numPriceLength,$isBack=0){
        $formatArr = [];
        $bigArr = array('is_big'=>2,'paper'=>$print['paper']);
        $formatDataBig = $this->printBase->getThreeData($bigArr);
        $widthBig = $formatDataBig['width'];
        // 退菜
        if ($isBack==1) {
            $goods['name'] = '【退】'. $goods['name'];
        }

        if ($goods['spec'] || $goods['spec_sub']) {
            $goods['name'] .= '[' . trim($goods['spec'].'（'.$goods['spec_sub'].'）') . ']';
        }

        $len = self::dstrlen($goods['name']);
        $num_price_str = '';//数量与价格拼接
        if ($len+$numPriceLength+2 > $widthBig * 2) { 
            $formatArr[] = $this->printBase->formatToArray($goods['name'],'big');
            // 拼接数量
            $num_price_str .= str_repeat(' ',($widthBig * 2-$numPriceLength)).'×'.$goods['num'];
           
        }else{
            $num_price_str .= $goods['name'].'  ';
            $name_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;
            // 拼接数量
            $num_price_str .= str_repeat(' ',($widthBig * 2-$numPriceLength - $name_length)).'×'.$goods['num'];
        }

        // 数量长度
        $num_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;

        // 价格长度
        $price_length = strlen(getFormatNumber($goods['price']*$goods['num']));

        // 拼接价格，价格居右显示
        $num_price_str .= str_repeat(' ',($widthBig * 2 - $price_length - $num_length)).getFormatNumber($goods['price']*$goods['num']);
        $formatArr[] = $this->printBase->formatToArray($num_price_str,'big');

        if ($goods['note']) {
            $formatArr[] = $this->printBase->formatToArray('  ['.L_('备注') . '：' . $goods['note'] . ']');
        }
        return $formatArr;
    }

    /*
     *  餐饮打印底部数据（菜品以下打印内容）
     *  param order array 订单详情
     *  param print array 打印机
     *  param staffName string 店员名字
     *  param numPriceLength float 商品价格与数量最大长度
     *  param isBack int 是否退菜
     *  param printType int 打印类型（非结账单预结单）0-结账单预结单 1-一菜一单 2-后厨补打
     *  param store array 店铺信息
     *  return formatArr array 返回打印数组
     */
    public function foodshopFooter($order,$print,$staffName='',$printType=1,$store=[]){
        $formatArr = [];

        $smallArr = array('is_big'=>0,'paper'=>$print['paper']);
        $formatData = $this->printBase->getThreeData($smallArr);
        $widthSmall = $formatData['width'];

        $middleArr = array('is_big'=>1,'paper'=>$print['paper']);
        $formatDataMiddle = $this->printBase->getThreeData($middleArr);
        $widthMid = $formatDataMiddle['width'];

        $bigArr = array('is_big'=>2,'paper'=>$print['paper']);
        $formatDataBig = $this->printBase->getThreeData($bigArr);
        $widthBig = $formatDataBig['width'];

        if ($mainPrint['is_big']==2) {
            $smallSize = 'big';
            $middleSize = 'big';
            $width = $widthBig;
        }else{
            $smallSize = 'small';
            $middleSize = 'middle';
            $width = $widthSmall;
        }
      
        if ($printType) { 
            if (!empty($order['note']) && $order['note'] != '无') {
                // 分割线
                $formatArr[] = $this->printBase->formatToArray(str_repeat('*', $width * 2),$smallSize,'center');
                $formatArr[] = $this->printBase->formatToArray('整单留言:' . $order['note'],$middleSize);
                $formatArr[] = $this->printBase->formatToArray(str_repeat(' ', $width * 2),$smallSize,'center');
            }
        }else{
            // 桌台服务名称
            // $table_service_name = $this->get_table_service_alias($order['store_id']);

            // 分割线
            $formatArr[] = $this->printBase->formatToArray(str_repeat('*', $width * 2),$smallSize,'center');
            
            if ($order['book_time'] != '') {
                $formatArr[] = $this->printBase->formatToArray('预定时间：' . $order['book_time_str'],$smallSize);
            }
            
            if (isset($order['book_pay_type'])&&$order['book_pay_type']) {
                $formatArr[] = $this->printBase->formatToArray('预定支付方式：' . $order['book_pay_type'],$smallSize);
            }
            if (isset($order['book_pay_time']) && $order['book_pay_time']) {
                $formatArr[] = $this->printBase->formatToArray('预定支付时间：' . date('Y-m-d H:i', $order['book_pay_time']),$smallSize);
            }
            
            if (isset($order['pay_type'])&&$order['pay_type']) {
                $formatArr[] = $this->printBase->formatToArray('买单支付方式：' . $order['pay_type'],$smallSize);
            }
            if (isset($order['pay_time']) && $order['pay_time']) {
                $formatArr[] = $this->printBase->formatToArray('买单支付时间：' . $order['pay_time'],$smallSize);
            }
            //桌台服务费
            // if (isset($order['table_service_payable']) && $order['table_service_payable']>0) {
            //     $formatArr[] = $this->printBase->formatToArray('应付'.$table_service_name.':' . $order['table_service_payable'],$smallSize);
            // }
            // if (isset($order['table_service_payment']) && $order['table_service_payment']>0) {
            //     $formatArr[] = $this->printBase->formatToArray('实付'.$table_service_name.':' . $order['table_service_payment'],$smallSize);
            // }
            
            if ($order['status'] > 1) {
                if ($order['goods_num']) {
                    if ($order['status']<=2) {
                        $formatArr[] = $this->printBase->formatToArray('共' . $order['goods_num'] . '件，应付金额：' . floatval($order['total_price']),$middleSize);
                    }else{
                        $formatArr[] = $this->printBase->formatToArray('共' . $order['goods_num'] . '件，订单总价：' . floatval($order['total_price']),$middleSize);
                    }
                }else{
                    if ($order['status']<=2) {
                        $formatArr[] = $this->printBase->formatToArray('应付金额：' . floatval($order['total_price']),$middleSize);
                    }else{
                        $formatArr[] = $this->printBase->formatToArray('订单总价：' . floatval($order['total_price']),$middleSize);
                    } 
                }

                if($order['order_from']==2||$order['from_plat']==2){
                    // $store_order = M('Store_order')->where(array('business_type'=>'foodshop','business_id'=>$order['order_id'],'paid'=>1))->find();
                    // $store_order['discount_price']>0 && $formatArr[] = $this->printBase->formatToArray('优惠金额：' . floatval($store_order['discount_price']),$middleSize);
                    // $store_order['price']>0 && $formatArr[] = $this->printBase->formatToArray('实付金额：' . floatval($store_order['price']),$middleSize);
                }else{
                    if($order['table_discount_money']>0 && $order['table_or_vipcard_discount']==0){
                        $order['mer_table_discount']>0 && $formatArr[] = $this->printBase->formatToArray('桌台折扣：' . floatval($order['mer_table_discount']/10),$middleSize);
                        $order['table_discount_money']>0 && $formatArr[] = $this->printBase->formatToArray('桌台优惠金额：' . floatval($order['table_discount_money']),$middleSize);
                    }
                    $order['price']>0 && $formatArr[] = $this->printBase->formatToArray('实付金额：' . floatval($order['price']),$middleSize);
                }
            }
            if (!empty($order['note']) && $order['note'] != '无') {
                // 分割线
                $formatArr[] = $this->printBase->formatToArray(str_repeat('*', $width * 2),$smallSize,'center');
                $formatArr[] = $this->printBase->formatToArray('整单留言:' . $order['note'],$middleSize);
            }
            // $formatStr .= chr(10) . str_repeat('※', $width);
            $formatArr[] = $this->printBase->formatToArray(str_repeat('*', $width * 2),$smallSize,'center');
            $formatArr[] = $this->printBase->formatToArray('店铺电话：' . $store['phone'],$smallSize);
            $formatArr[] = $this->printBase->formatToArray('店铺地址：' . $store['adress'],$smallSize);
            $formatArr[] = $this->printBase->formatToArray('打印时间：' . date('Y-m-d H:i:s'),$smallSize);
            $formatArr[] = $this->printBase->formatToArray('店铺电话：' . $store['phone'],$smallSize);
            $formatArr[] = $this->printBase->formatToArray('谢谢惠顾，欢迎再次光临！',$smallSize,'center');
        }
        return $formatArr;
    }


}