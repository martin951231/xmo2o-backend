<?php
/**
 * 打印service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/16 14:45
 */

namespace app\merchant\model\service\print_order;
use app\merchant\model\db\Orderprinter as OrderprinterModel;
class PrintHaddleService {
    public $orderprinterModel = null;
    public function __construct()
    {
        $this->orderprinterModel = new OrderprinterModel();
    }
    
    /**
     * 获取店铺的打印机列表 (如果店铺没有主打印机的话，将最先添加的打印机当做主打印机)
     * @param int $store_id
     * @return number|unknown[]
     */
    public function getPrintListByStoreId($storeId)
    {   
        $where = [
            'store_id' => $storeId
        ];
        $prints = $this->getList($where);

        $result = [];
        $isMain = 0;
        $firstId = 0;
        foreach ($prints as $print) {
            if ($print['is_main']) {
                $isMain = $print['pigcms_id'];
            } elseif (empty($firstId)) {
                $firstId = $print['pigcms_id'];
            }
            $result[$print['pigcms_id']] = $print;
        }
        if ($isMain == 0 && $firstId != 0) {
            $result[$firstId]['is_main'] = 1;
        }
        return $result;
    }
    
    /*
     * 打印数据
     * @param array|string formatArr 打印数组或字符串
     * @param array print  打印机
     * @return bool 返回
     */
    public function toPrint($formatArr,$print){
        fdump_api(func_get_args(),'toPrint',true);
        if(is_array($formatArr)){
            if (($print['print_type'] == 6 && $print['version'] >= 1450) || ($print['print_type'] == 2 && $print['version'] >= 50)) {
                // 商米打印机 有线打印机
                $formatStr['print_data'] = $formatArr;
                $formatStr = json_encode($formatStr, JSON_UNESCAPED_UNICODE);
            }else{ 
                $formatStr = $this->printArrayToString($formatArr);
            }
        }else{
            $formatStr = $formatArr;
        }
        fdump($formatStr, 'print',1);
        // 打印机调用
        (new PrintService())->toPrint($print, $formatStr);
        return true;
    }


    /*
     * 格式化打印内容，构造array
     * content  打印内容
     * size     字体大小 small middle big
     * type     text文本 image图片
     * bold     是否加粗 true false
     * align    显示位置 center left right
    */
    public function formatToArray($content, $size = 'small', $align = 'left', $bold = false, $type='text'){
        if (!$content) {
            return false;
        }
        $return = array(
                    "size" => $size,
                    "bold" => $bold,
                    "align" => $align,
                    "type" => $type,
                );
        if ($type=='text') {
           $return["text"] = $content;
        }else{
           $return["image"] = $content;
        }
        return $return;
    }   
    
    /*
     * 格式化打印内容，array转化成str格式
     * content  打印内容
     * size     字体大小 small middle big
     * type     text文本 image图片
     * bold     是否加粗 true false
     * align    显示位置 center left right
    */
    public function printArrayToString($content){
        if (!$content) {
            return '';
        }
        $temp_str = '';
        foreach ($content as $key => $value) {
            if ($value['type']=='text') {
                if ($value['align']=='center') {
                    // 文本居中
                    $value['text'] = "<center>".$value['text']."</center>";
                }elseif($value['align']=='right'){
                    // 文本居右
                    $value['text'] = "<right>".$value['text']."</right>";
                }
                switch ($value['size']) {
                    case 'middle': 
                        $value['text'] = "<FH><FW>".$value['text']."</FW></FH>";
                        break;
                    case 'big':
                        $value['text'] = "<FH2><FW2>".$value['text']."</FW2></FH2>";
                        break;
                }
                if ($value['bold']) {
                    // 字体加粗
                    $value['text'] = "<FB>".$value['text']."</FB>";
                }
                
                $temp_str .="\n" . $value['text'];
                // $temp_str .= chr(10) . $value['text'];                
            }
        }
        return $temp_str;
    }
    
    /*
     *  获得打印机间隔 一行三个
     *  param usePrinter array 打印机
     *  return formatArr array 返回打印数组
     */
    public function getThreeData($usePrinter)
    {
        if ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 0) {
            $width = 16;
            $firstSpace = 8;
            $secondSpace = 3;
            $thirdSpace = 4;
            $spaceWidth = 16;//数量前的所占的字符数
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 0) {
            $width = 12;
            $firstSpace = 3;
            $secondSpace = 4;
            $thirdSpace = 3;
            $spaceWidth = 10;
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 0) {
            $width = 8;
            $firstSpace = 1;
            $secondSpace = 1;
            $thirdSpace = 1;
            $spaceWidth = 4;
        } elseif ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 1) {
            $width = 24;
            $firstSpace = 7;
            $secondSpace = 14;
            $thirdSpace = 12;
            $spaceWidth = 22;
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 1) {
            $width = 18;
            $firstSpace = 4;
            $secondSpace = 11;
            $thirdSpace = 8;
            $spaceWidth = 18;
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 1) {
            $width = 12;
            $firstSpace = 3;
            $secondSpace = 4;
            $thirdSpace = 3;
            $spaceWidth = 10;
        }
        return array('width' => $width, 'one' => $firstSpace, 'two' => $secondSpace, 'three' => $thirdSpace, 'spaceWidth' => $spaceWidth);
    }

    /*
     *  获得打印机间隔 一行两个
     *  param usePrinter array 打印机
     *  return formatArr array 返回打印数组
     */
    public function getTwoData($mainPrint)
    {
        if ($mainPrint['is_big'] == 0 && $mainPrint['paper'] == 0) {
            $width = 16;
            $firstSpace = 4;
            $secondSpace = 16;
        } elseif ($mainPrint['is_big'] == 1 && $mainPrint['paper'] == 0) {
            $width = 12;
            $firstSpace = 4;
            $secondSpace = 8;
        } elseif ($mainPrint['is_big'] == 2 && $mainPrint['paper'] == 0) {
            $width = 8;
            $firstSpace = 2;
            $secondSpace = 2;
        } elseif ($mainPrint['is_big'] == 0 && $mainPrint['paper'] == 1) {
            $width = 24;
            $firstSpace = 4;
            $secondSpace = 32;
        } elseif ($mainPrint['is_big'] == 1 && $mainPrint['paper'] == 1) {
            $width = 18;
            $firstSpace = 4;
            $secondSpace = 20;
        } elseif ($mainPrint['is_big'] == 2 && $mainPrint['paper'] == 1) {
            $width = 12;
            $firstSpace = 4;
            $secondSpace = 8;
        }
        return array('width' => $width, 'one' => $firstSpace, 'two' => $secondSpace);
    }


    /*
     *  获得打印机间隔 一行三个
     *  param usePrinter array 打印机
     *  return formatArr array 返回打印数组
     */
    public function getTableThreeColumnLength($usePrinter)
    {
        if ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 0) {//16
            $width = 16;
            $firstWidth = 9;//第一列宽度
            $secondWidth = 2;//第二列宽度
            $thirdWidth = 4;//第三列宽度
            $spaceWidth = 1;//每列空格
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 0) {
            $width = 12;
            $firstWidth = 4;//第一列宽度
            $secondWidth = 3;//第二列宽度
            $thirdWidth = 3;//第三列宽度
            $spaceWidth = 1;//每列空格
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 0) {
            $width = 8;
            $firstWidth = 2;
            $secondWidth = 2;
            $thirdWidth = 2;
            $spaceWidth = 1;
        } elseif ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 1) {//24
            $width = 24;
            $firstWidth = 8;//第一列宽度
            $secondWidth = 3;//第二列宽度
            $thirdWidth = 3;//第三列宽度
            $spaceWidth = 2;//每列空格
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 1) {
            $width = 18;
            $firstWidth = 10;//第一列宽度
            $secondWidth = 2;//第二列宽度
            $thirdWidth = 3;//第三列宽度
            $spaceWidth = 1;//每列空格
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 1) {
            $width = 12;
            $firstWidth = 5;//第一列宽度
            $secondWidth = 3;//第二列宽度
            $thirdWidth = 2;//第三列宽度
            $spaceWidth = 1;//每列空格
        }
        return array('width' => $width, 'one' => $firstWidth, 'two' => $secondWidth, 'three' => $thirdWidth, 'spaceWidth' => $spaceWidth);
    }

    /**
     *  获得打印机间隔 一行两个
     *  param usePrinter array 打印机
     *  return formatArr array 返回打印数组
     */
    public function getTableTwoColumnLength($usePrinter)
    {
        if ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 0) {
            // 没有小号字体
            $width = 16;
            $firstWidth = 8;//第一列宽度
            $secondWidth = 3;//第二列宽度
            $spaceWidth = 1;//每列空格
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 0) {
            $width = 12;
            $firstWidth = 8;//第一列宽度
            $secondWidth = 3;//第二列宽度
            $spaceWidth = 1;//每列空格
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 0) {
            $width = 8;
            $firstWidth = 4;//第一列宽度
            $secondWidth = 3;//第二列宽度
            $spaceWidth = 1;//每列空格
        } elseif ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 1) {
            $width = 24;
            $firstWidth = 12;//第一列宽度
            $secondWidth = 4;//第二列宽度
            $spaceWidth = 2;//每列空格
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 1) {
            $width = 18;
            $firstWidth = 12;//第一列宽度
            $secondWidth = 4;//第二列宽度
            $spaceWidth = 2;//每列空格
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 1) {
            $width = 12;
            $firstWidth = 7;//第一列宽度
            $secondWidth = 3;//第二列宽度
            $spaceWidth = 2;//每列空格
        }
        return array('width' => $width, 'one' => $firstWidth, 'two' => $secondWidth, 'spaceWidth' => $spaceWidth);
    }

    /**
     *  餐饮打印商品数据
     *  @param $goods array 商品
     *  @param $print array 打印机
     *  @param $column int 打印列数
     *  @param $isBack int 是否退菜
     *  return formatArr array 返回打印数组
     */
    public function createBodyByTable($list, $header, $print){
        if(empty($list) || empty($header) || empty($print)){
            return [];
        }

        $formatArr = [];
        $bigArr = array('is_big'=>2,'paper'=>$print['paper']);
        $formatDataBig = $this->getThreeData($bigArr);
        $widthBig = $formatDataBig['width'];

        $column = count($header);
//        $bold = $column==2 ? true : false;
        $bold =  true;

        $first = 0;//第一列宽度
        $second = 0;//第二列宽度
        $third = 0;//第三列宽度
        $spaceWidth = 0;//每列空格
        switch ($column){
            case '3':
                $tableData = $this->getTableThreeColumnLength($print);
                $width = $tableData['width'];
                $first = $tableData['one'];
                $second = $tableData['two'];
                $third = $tableData['three'];
                $spaceWidth = $tableData['spaceWidth'];
                break;
            case '2':
                $tableData = $this->getTableTwoColumnLength($print);
                $width = $tableData['width'];
                $first = $tableData['one'];
                $second = $tableData['two'];
                $spaceWidth = $tableData['spaceWidth'];
                break;
        }

        if ($print['is_big']==2) {
            $small = 'big';
            $middle = 'big';
        }else{
            $small = 'small';
            $middle = 'middle';
        }

        $spaceStr = str_repeat(' ', $spaceWidth * 2);
        $head = '';
        foreach ($header as $_head){
            $length = self::dstrlen($_head['title']);
            $param = $_head['index'];
            if($length<$$param*2){
                $head .= $_head['title'].str_repeat(' ', ($$param*2-$length)).$spaceStr;
            }else{
                $head .= $_head['title'].$spaceStr;
            }
        }
        // 表头
        $formatArr[] = $this->formatToArray(trim($head), $middle);

        //内容
        foreach ($list as $goods){
            //计算每一列需要的行数，并取最大的
            $len1 = self::dstrlen($goods['name']);
            fdump($goods['name'],'ll',1);
            fdump($len1,'ll',1);
            $goodsNum = isset($goods['unit']) ? get_format_number($goods['num']).'/'.$goods['unit'] : get_format_number($goods['num']);
            $goodsTotalPrice = $goods['total_price'] ?? 0;
            $len2 = self::dstrlen($goodsNum);
            $len3 = self::dstrlen(get_format_number($goodsTotalPrice));
            $row1 = ceil($len1/($first*2));//每一列需要的行数
            fdump('$first','ll',1);
            fdump($first,'ll',1);
            fdump('$row1','ll',1);
            fdump($row1,'ll',1);
            $row2 = ceil($len2/($second*2));
            $row3 = 0;
            $third && $row3 = ceil($len3/($third*2));

            $row = max($row1,$row2);
            $row = max($row,$row3);

            $i = 0;
            while($row>0){
//                $name = mb_substr($goods['name'],$i*$first, $first,"utf-8");
                fdump('$name','ll',1);

                $nameSub = mb_strimwidth($goods['name'],0, $i*$first*2,'',"utf-8");
                $start = mb_strlen($nameSub);
                $name = mb_strimwidth($goods['name'],$start, $first*2,'',"utf-8");
                fdump($name,'ll',1);
                if($first*2 > self::dstrlen($name)){
                    $name = $name . str_repeat(' ', ($first*2 - self::dstrlen($name)));
                }

                $goodsNumber = '';
                if($i<$row2){
//                    $goodsNumber = mb_substr($goodsNum, $i * $second, $second, "utf-8");
                    $goodsNumber = mb_strimwidth($goodsNum,$i*$second*2, $second*2,'',"utf-8");
                    if ($second*2 > self::dstrlen($goodsNumber)) {
                        $goodsNumber = $goodsNumber . str_repeat(' ', ($second * 2 - self::dstrlen($goodsNumber)));
                    }

                }

                $totalPrice = '';
                if($i<$row3){
//                    $totalPrice = mb_substr($goodsTotalPrice, $i * $third, $third, "utf-8");
                    $totalPrice = mb_strimwidth($goodsTotalPrice,$i*$third, $third*2,'',"utf-8");
                    if ($third*2 > self::dstrlen($totalPrice)) {
                        $totalPrice = $totalPrice . str_repeat(' ', ($third * 2 - self::dstrlen($totalPrice)));
                    }
                }

                $rowContent = $name.$spaceStr.$goodsNumber.($third ? ($spaceStr.$totalPrice) : '');
                $formatArr[] = $this->formatToArray($rowContent, $middle,'', $bold);
                $row--;
                $i++;
            }
            // 规格
            if($goods['spec']){
                // $formatArr[] = $this->formatToArray(L_('规格').'：', $middle);
                $goods['spec'] = L_('规格').'：'.$goods['spec'];
                //计算每一列需要的行数
                $specLen = self::dstrlen($goods['spec']);
                $specRow = ceil($specLen/($first*2));//每一列需要的行数

                $i = 0;
				$specNew = '';// 已经截取的字符串
                while($specRow>0){
                    $start = mb_strlen($specNew);// 已经截取的字符串个数
                    $spec = mb_strimwidth($goods['spec'],$start, $first*2,'',"utf-8");
					$specNew .= $spec;
                    if($first*2 > self::dstrlen($spec)){
                        $spec = $spec . str_repeat(' ', ($first*2 - self::dstrlen($spec)));
                    }

                    $rowContent = $spec;
                    $formatArr[] = $this->formatToArray($rowContent, $middle);
                    $specRow--;
                    $i++;
                }
            }

            //附属菜
            if(isset($goods['sub_list']) && $goods['sub_list']){
                if (isset($goods['is_package_goods']) && $goods['is_package_goods'] != true) {
                    $formatArr[] = $this->formatToArray(L_('附属菜').'：', $middle);
                }

                foreach ($goods['sub_list'] as $subGoods){
                    //计算每一列需要的行数，并取最大的
                    $len1 = self::dstrlen($subGoods['name']);
                    $goodsNum = isset($subGoods['unit']) ? get_format_number($subGoods['num']).'/'.$subGoods['unit'] : get_format_number($subGoods['num']);
                    $goodsTotalPrice = $subGoods['total_price'] ?? 0;
                    $len2 = self::dstrlen($goodsNum);
                    $len3 = self::dstrlen(get_format_number($goodsTotalPrice));
                    $row1 = ceil($len1/($first*2));//每一列需要的行数
                    $row2 = ceil($len2/($second*2));
                    $row3 = 0;
                    $third && $row3 = ceil($len3/($third*2));

                    $row = max($row1,$row2);
                    $row = max($row,$row3);

                    $spaceStr = str_repeat(' ', $spaceWidth * 2);
                    $i = 0;
                    while($row>0){
                        $rowContent = '';
//                        $name = mb_substr($subGoods['name'],$i*$first, $first,"utf-8");
                        $nameSub = mb_strimwidth($subGoods['name'],0, $i*$first*2,'',"utf-8");
                        $start = mb_strlen($nameSub);
                        $name = mb_strimwidth($subGoods['name'],$start, $first*2,'',"utf-8");

                        if($first*2 > self::dstrlen($name)){
                            $name = $name . str_repeat(' ', ($first*2 - self::dstrlen($name)));
                        }

                        $goodsNumber = '';
                        if($i<$row2) {
//                            $goodsNumber = mb_substr($goodsNum, $i * $second, $second, "utf-8");
                            $goodsNumber = mb_strimwidth($goodsNum,$i*$second*2, $second*2,'',"utf-8");
                            if ($second * 2 > self::dstrlen($goodsNumber)) {
                                $goodsNumber = $goodsNumber . str_repeat(' ', ($second * 2 - self::dstrlen($goodsNumber)));
                            }
                        }

                        $totalPrice = '';
                        if($i<$row3) {
//                            $totalPrice = mb_substr($goodsTotalPrice, $i * $third, $third, "utf-8");
                            $totalPrice = mb_strimwidth($goodsTotalPrice,$i*$third, $third*2,'',"utf-8");
                            if ($third * 2 > self::dstrlen($totalPrice)) {
                                $totalPrice = $totalPrice . str_repeat(' ', ($third * 2 - self::dstrlen($totalPrice)));
                            }
                        }

                        $rowContent = $name.$spaceStr.$goodsNumber.($third ? ($spaceStr.$totalPrice) : '');
                        $formatArr[] = $this->formatToArray($rowContent, $middle,'');
                        $row--;
                        $i++;
                    }

                    // 规格
                    if($subGoods['spec']){
                        // $formatArr[] = $this->formatToArray(L_('规格').'：', $middle);
                        $subGoods['spec'] = L_('规格').'：'.$subGoods['spec'];
                        
                        //计算每一列需要的行数
                        $specLen = self::dstrlen($subGoods['spec']);
                        $specRow = ceil($specLen/($first*2));//每一列需要的行数

                        $i = 0;
                        while($specRow>0){
//                            $spec = mb_substr($subGoods['spec'],$i*$first, $first,"utf-8");
                            $spec = mb_strimwidth($subGoods['spec'],$i*$first, $first*2,'',"utf-8");
                            if($first*2 > self::dstrlen($spec)){
                                $spec = $spec . str_repeat(' ', ($first*2 - self::dstrlen($spec)));
                            }

                            $rowContent = $spec;
                            $formatArr[] = $this->formatToArray($rowContent, $middle);
                            $specRow--;
                            $i++;
                        }
                    }
                }
            }
        }
//        die;
        return $formatArr;
    }
    /**
     * 获得列表
     * @param array $where
     * @param array $order
     * @return array
     */
    public function getList($where,$order=[]) {
        if(empty($where)){
            return [];
        }

        if(empty($order)){
            $order['is_main'] = 'DESC';
            $order['pigcms_id'] = 'ASC';
        }

        $list = $this->merchantModel->getMerchantByMerId($where,$order);
        if(!$list) {
            return [];
        }
        
        return $list->toArray(); 
    }


    public static function dstrlen($string)
    {
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n ++;
                $noc ++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n ++;
            }
        }
        return $noc;
    }
}