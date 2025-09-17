<?php
/*
 *   标签打印机
 * @date 20201103
 *
 */
class Label_printerModel extends Model{
    public $orderPrint = null;
    public function _initialize()
    {
        $this->orderPrint = new orderPrint(C('config.print_server_key'), C('config.print_server_topdomain'));
    }

    /**
     * 快店的标签打印
     * @param array $order 订单详情
     * @param array $prints 打印机数组
     * @param int $status  当前状态
     * @param number $printType 打印类型， 0：按菜品归属打印机进行分组打印，1：按商品分类所属打印机进行分组打印
     */
    public function shopOrderLabel($order)
    {
        // 打印机列表
        $prints = $this->where(['store_id'=>$order['store_id']])->select();
        if(empty($prints)){
            // 没有打印机
            return false;
        }

        if (empty($order['info'])) {
            // 没有商品
            return false;
        }

        // 商品列表
        $list = D('Shop_goods')->combination_data($order['info'],'spec');

        $store = M(_view('Merchant_store'))->field(true)->where(array('store_id' => $order['store_id']))->find();

        $goods_ids = array();
        $newList = array();

        $goodsCount = 0;//商品总数
        foreach ($list as $row) {
            $goodsCount += $row['num'];
        }

        $shop_notice_plat_name = C('config.shop_notice_plat_name');
        $plat_name_length = (strlen($shop_notice_plat_name) + mb_strlen($shop_notice_plat_name,'UTF8')) / 2;
        if($plat_name_length > 10){
            $shop_notice_plat_name = mb_strimwidth($shop_notice_plat_name,0, 10,'',"utf-8");
            $plat_name_length = 10;
        }

        foreach ($prints as $_print) {
            // 打印机绑定的商品
            $printGoods = array_column(D('Label_printer_bind_goods')->where(['print_id'=>$_print['pigcms_id']])->select(), 'goods_id');
            $format_arr = [];

            $index = 0;
            foreach ($list as $_goods) {
               
                if(!in_array($_goods['goods_id'], $printGoods)){//未绑定该商品不打印
                    continue;
                }

                // 每个商品单独打印
                $i = $_goods['num'];
                while($i>0){ 
                    $index++;//当前第几个
                    $i--;
                    $format_str = '';

                    // 头部,有取单号就打印出来
                    if($order['fetch_number']){
                        $title = '#' . $order['fetch_number'];
                    }else{
                        $title = '';
                    }
                    if($shop_notice_plat_name){
                        $title = str_repeat(' ', 2).$title.str_repeat(' ', 2).$shop_notice_plat_name;
                    }else{
                        $title = str_repeat(' ', 12).$title;
                    }
                   
                    $title .= str_repeat(' ', 2).$index.'/'.$goodsCount;

                    $format_str .= '<TEXT x="10" y="20" font="12" w="1" h="1" r="0">'.$title.'</TEXT>';

                    // 商品名称
                    $format_str .= '<TEXT x="10" y="50" font="12" w="1" h="2" r="0">'.$_goods['name'].'</TEXT>';
                    $height = 120;
                    if($_goods['spec']){
                        $spec =  str_replace(',', '/', $_goods['spec']);
                        $spec =  str_replace('、', '/', $spec);
                        $length = mb_strlen($spec, 'UTF-8');
                        $lineCount = ceil($length / 14);
                        for ($j = 0; $j < $lineCount; $j++) {
                            $height = $height + $j * 30;
                            $substring = mb_substr($spec, $j * 14, 14, 'UTF-8');
                            $format_str .= '<TEXT x="10" y="'.$height.'" font="12" w="1" h="1" r="0">'.$substring.'</TEXT>';
                        }
                    }
                    $format_str .= '<TEXT x="10" y="'.($height+30).'" font="12" w="1" h="1" r="0">'.date('Y-m-d H:i'). str_repeat(' ', 3).C('config.Currency_symbol') . getFormatNumber($_goods['price']).'</TEXT>';
                    $format_str .= '<TEXT x="10" y="'.($height+60).'" font="12" w="1" h="1" r="0">'. $store['adress'].'</TEXT>';
                    fdump_api($format_str,'printer/shopOrderLabel',1);
                    $this->orderPrint->LabelPrint($_print, $format_str);
                }
            }
        }
    }
}
?>
