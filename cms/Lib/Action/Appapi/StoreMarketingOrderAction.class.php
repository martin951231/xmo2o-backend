<?php


class StoreMarketingOrderAction extends BaseAction
{

    protected $merid;
    protected $merchant;
    protected function _initialize(){
        parent::_initialize();
        $ticket = I('ticket', false);
        if($ticket){
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if($info){
                $condition_merchant['mer_id'] = $info['uid'];
            }else{
                $this->returnCode('20140001');
            }
            $database_merchant = D('Merchant');
            $this->merchant = $database_merchant->field(true)->where($condition_merchant)->find();
            $this->mer_id = $this->merchant['mer_id'];
        }
    }

    public $payType = [
        'wechat' => '微信支付',
        'alipay' => '支付宝支付',//尾款待支付
        'wechat_mini' => '微信小程序支付',
        'balance' => '商家余额支付',
        'offline' => '线下支付',
        'platform_balance'=>'平台余额支付'
    ];

    /**
     * @param $param
     * 订单列表
     */
    public function getOrderList()
    {
        $datas['mer_id'] = $this->merchant['mer_id'];
        $datas['page'] = I("page", 1);
        $datas['pageSize'] = I("pageSize", 10);
        $out['list']=[];
        if(empty($datas['mer_id'])){
            $this->returnCode('73000008');
        }else{
            $where = ['mer_id' => $datas['mer_id'], 'paid' => 1];
            $sql =M('New_marketing_order')->field(true)->where($where)->order('add_time desc');
            if($datas['pageSize'])
            {
                $sql->limit(($datas['page'] - 1) * $datas['pageSize'],$datas['pageSize']);
            }
            $list = $sql->select();
            $arr = [];
            if (!empty($list)) {
                foreach ($list as $k => $v) {
                    $data['orderid'] = $v['orderid'];
                    $data['order_type'] = $v['order_type'];
                    $data['order_id'] = $v['order_id'];
                    $data['store_id'] = $v['store_id'];
                    $data['store_name'] =empty($v['store_id'])?$v['pack_name']:M('Merchant_store')->where(['store_id'=>$v['store_id']])->column('name');
                    $data['buy_num'] = $v['buy_num'];
                    $data['add_time'] = date("Y-m-d H:i:s",$v['add_time']);
                    $data['total_price'] = $this->get_format_number($v['total_price']);
                    $arr[]=$data;
                }
            }
            $out['list']=$arr;
        }
        $this->returnCode(0,$out);
    }


    /**
     * 订单详情
     */
    public function getOrderDetail(){
        $data['order_id'] = I("order_id", 197);
        $data['order_type'] = I("order_type", 0);//0新订单 1续费订单
        $out=[];
        if($data['order_id']){
            $where['order_id']=$data['order_id'];
            $msg =M('New_marketing_order')->field(true)->where($where)->find();
            if(!empty($msg)){
                $out['detail']['order_type']=$msg['order_type'];
                $out['detail']['orderid']=$msg['orderid'];
                if($msg['pack_region_id']){
                    $store_detail=$this->getPageName($msg['pack_region_id']);
                    if(!empty($store_detail)){
                        $out['detail']['buy_detail']=$this->formatData($store_detail);
                    }else{
                        $out['detail']['buy_detail']=[];
                    }
                }
                $out['detail']['store_name']=empty($msg['store_id'])?$msg['pack_name']:M('Merchant_store')->where(['store_id'=>$msg['store_id']])->column('name');
                $out['detail']['buy_num']=$msg['buy_num'];
                $out['detail']['pay_years']=$msg['pay_years']."年";
                $out['detail']['add_time']=date("Y-m-d H:i:s",$msg['add_time']);
                $out['detail']['pay_type']=$this->payType[$msg['pay_type']];
                $out['detail']['total_price']=$this->get_format_number($msg['total_price']);
            }
            $this->returnCode(0,$out);
        }else{
            $this->returnCode('10070006');
        }
    }

    // 返回前端所需格式
    public function formatData($store_detail)
    {
        $store_detail = $store_detail ? json_decode($store_detail, true) : [];
        $arr=[];
        if ($store_detail) {// 店铺分类信息
            foreach ($store_detail as $sk => $sv) {
                $type_name0 = $this->getOneData(['cat_id' => $sv['type'][0]])['cat_name'] ?? '';// 父分类
                $type_name1 = !empty($sv['type'][1]) ? $this->getOneData(['cat_id' => $sv['type'][1]])['cat_name'] : '';// 子分类
                $type_name = $type_name1 ? $type_name0 . '-' . $type_name1 : $type_name0;
                $data['msg']= '个'.$type_name;
                $data['num']=$sv['num'];
                $arr[]=$data;
            }
        }
        return $arr;
    }

    /**
     * @param $pack_region_id
     * @return string
     * 获取套餐的店铺详情
     */
    public function getPageName($pack_region_id){
        $sql = "SELECT p.store_detail FROM " . C('DB_PREFIX') . "new_marketing_package_region AS r LEFT JOIN " . C('DB_PREFIX') . "new_marketing_package AS p ON `r`.`package_id`=`p`.`id`
         WHERE `r`.`id`='$pack_region_id'";
        $mode = new Model();
        $list = $mode->query($sql);
        if(!empty($list)){
            $name=$list[0]['store_detail'];
        }else{
            $name="";
        }
        return $name;
    }

    /**
     * @param $where
     * @return mixed
     * 查询数据
     */
    public function getOneData($where) {
        $data = M('Merchant_category')->where($where)->find();
        return $data;
    }

    /**
     * 格式化数字
     * @param  [type] $number 数字
     * @param  [type] $retain 小数点后的位数
     * @return string         格式化后的数字
     */
    function get_format_number($number = 0, $retain = 2){
        $number = number_format($number,$retain);
        if(strpos($number,'.') !== false){
            $number = rtrim($number,'0');
            $number = rtrim($number,'0');
            $number = rtrim($number,'.');
        }
        $number = str_replace(',','',$number);

        return $number;
    }
}