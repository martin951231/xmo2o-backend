<?php
/*
@create_time 2019-01-01
@desc 平台大数据接口 
*/
class SystemDataAction extends BaseAction{
	/*数据开始统计时间*/
	private $start_time='';
	public function __construct(){
		parent::__construct();
		$start_date=I('start_date','','trim');
		if(!empty($start_date)){
			$this->start_time=strtotime($start_date);
		}
	}

	/*1、获取平台商家信息*/
	public function get_merchant_list(){
		$where=array();
		$where['isverify|issign']=1;
		$list=M('Merchant')->alias('me')->join('inner join '.C('DB_PREFIX').'merchant_store st on me.mer_id=st.mer_id')->where($where)->field('me.status,me.issign,me.isverify,st.long,st.lat')->select();
		$model=M('Merchant_store');
		$data=array();
		foreach ($list as $k => $v) {
			$arr=array();
			$arr['lng']=$v['long'];
			$arr['lat']=$v['lat'];
			$arr['value']=1;
			if($v['isverify']==1 && $v['issign']==1){
				$arr['type']=3;
			}elseif($v['isverify']==1){
				$arr['type']=1;
			}elseif($v['issign']==1){
				$arr['type']=2;
			}
			$data[]=$arr;
		}
		echo json_encode($data);
	}

	//2、按照平台类别销售额排行
	public function get_merchant_type_sales_rank(){
		$where=array();
		$type=I('type',1,'intval');
		$limit=I('get.limit',10,'intval');
		if($type==1){
			$where['paid']=1;
			if(!empty($this->start_time)){
				$where['create_time']=array('egt',$this->start_time);
			}
			$list=M('System_order')->where($where)->group('type')->order('sum(total_price) desc,mer_id asc')->limit($limit)->getfield('type,sum(total_price) sum_total_money',true);
		}else{
			$where['income']=1;
			$list=M('Merchant_money_list')->where($where)->group('type')->order('sum(total_money) desc,mer_id asc')->limit($limit)->getfield('type,sum(total_money) sum_total_money',true);
		}
		$data=array();
		$type_arr=$this->get_alias_c_name();
		foreach ($list as $k => $v) {
			$arr=array();
			$arr['x']=$type_arr[$k];
			$arr['y']=$v;
			$data[]=$arr;
		}
		echo json_encode($data);
	}

	protected function get_alias_c_name(){
        $c_name = array(
            'all'          => L_('选择分类'),
            'group'        => $this->config['group_alias_name'],
            'shop'         => $this->config['shop_alias_name'],
            'mall'         => $this->config['mall_alias_name'],
            'shop_offline' => $this->config['shop_alias_name'] . L_('线下零售'),
            'meal'         => $this->config['meal_alias_name'],
            'appoint'      => $this->config['appoint_alias_name'],
            'waimai'       => L_('外卖'),
            'store'        => L_('优惠买单'),
            'cash'         => L_('到店支付'),
            'weidian'      => L_('微店'),
            'wxapp'        => L_('营销'),
            'withdraw'     => L_('提现'),
            'coupon'       => L_('优惠券'),
            'activity'     => L_('平台活动'),
            'spread'       => L_('商家推广'),
            'sub_card'=>L_('免单套餐'),
            'market'=>L_('批发'),
            'score_pay_back'=>L_('积分返现'),
        );
        return $c_name;
    }

    /*3、商城商品类目销量量排行*/
    public function get_mall_menu_sales_rank(){
    	$limit=I('get.limit',11,'intval');
    	$where=array();
    	$where['cat_fid']=array('gt',0);
    	$list=M('Shop_goods')->where($where)->order("sum(sell_count) desc,goods_id asc")->group('cat_fid')->limit($limit)->getfield('cat_fid,sum(sell_count) sales_count',true);
    	$where2=array();
    	$where2['fid']=0;
    	$menu_arr=M('Goods_category')->where($where2)->order('id asc')->getfield('id,name',true);
    	$num1=count($list);
    	$num2=count($menu_arr);
    	$data=array();
    	foreach ($list as $k => $v) {
    		$arr=array();
    		$arr['x']=$menu_arr[$k];
    		$arr['y']=$v;
    		$arr['s']='';
    		$data[]=$arr;
    		unset($menu_arr[$k]);
    	}
		$is_show=I('get.is_show',0,'intval');
		if($is_show==1 && $num1<$limit && $num1<$num2){
			$data2=array_slice($menu_arr, 0,$limit-$num1);
			foreach ($data2 as $k2 => $v2) {
				$arr=array();
	    		$arr['x']=$v2;
	    		$arr['y']=0;
	    		$arr['s']='';
	    		$data[]=$arr;
			}
		}
    	echo json_encode($data);
    }

	/*4、商城商品销售量排行*/
	public function get_mall_goods(){
		$limit=I('get.limit',5,'intval');
		$where=array();
		$where['cat_fid']=array('gt',0);
		$order_str='sell_count,goods_id desc';
		$list=M('Shop_goods')->where($where)->order($order_str)->field('name')->limit($limit)->select();
		echo json_encode($list);
	}

	//

	/*
	*平台项目包括团购；快店、商城；美食；预约；到店；微店；营销
	*/

	//5,6、商家销售额
	public function get_merchant_sales(){
		$isverify=I('get.isverify',0,'intval');
		$issign=I('get.issign',0,'intval');
		$where=array();
		if($isverify==1){
			$where['isverify']=1;
		}
		if($issign==1){
			$where['issign']=1;
		}
		$mer_arr=M('Merchant')->where($where)->order('mer_id asc')->getfield('mer_id,name',true);
		$limit=I('limit',10,'intval');
		$where2=array();
		$arr=array_keys($mer_arr);
		$where2['mer_id']=array('in',$arr);

		$type=I('type',1,'intval');
		if($type==1){
			$where2['paid']=1;
			if(!empty($this->start_time)){
				$where2['create_time']=array('egt',$this->start_time);
			}
			$list=M('System_order')->where($where2)->group('mer_id')->order('sum(total_price) desc,mer_id asc')->limit($limit)->getfield('mer_id,sum(total_price) sum_total_money',true);
		}else{
			$where2['income']=1;
			$list=M('Merchant_money_list')->where($where2)->group('mer_id')->order('sum(total_money) desc,mer_id asc')->limit($limit)->getfield('mer_id,sum(total_money) sum_total_money',true);
		}
		$data=array();
		$num1=count($list);
		$num2=count($mer_arr);

		foreach ($list as $k => $v) {
			$info=array();
			$info['name']=$mer_arr[$k];
			$info['total']=$list[$k];
			$data[]=$info;
			unset($mer_arr[$k]);
		}
		/*无销量的商家是否展示*/
		$is_show=I('get.is_show',0,'intval');
		if($is_show==1 && $num1<$limit && $num1<$num2){
			$data2=array_slice($mer_arr,0,$limit-$num1);
			foreach ($data2 as $k2 => $v2) {
				$info=array();
				$info['name']=$v2;
				$info['total']=0;
				$data[]=$info;
			}
		}
		echo json_encode($data);
	}

	/*7，11、按照区域等级获取销售额排行*/
	public function get_city_store_sales(){
		$where=array();
		$type=I('type',1,'intval');
		if($type==1){
			$where['paid']=1;
			if(!empty($this->start_time)){
				$where['create_time']=array('egt',$this->start_time);
			}
			$info=M('System_order')->where($where)->order("sum(total_price) desc,mer_id asc,store_id asc")->group('store_id')->getfield('store_id,sum(total_price) as sum_total_money',true);
		}else{
			$where['income']=1;
			$info=M('Merchant_money_list')->where($where)->order('sum(total_money) desc,mer_id asc,store_id asc')->group('store_id')->getfield('store_id,sum(total_money) as sum_total_money',true);
		}
		$limit=I('limit',5,'intval');
		$type=I('type',1,'intval');
		$where2=array();
		$where2['ae.area_type']=$type;

		$field='';
		if($type==1){
			$field='province_id';
		}elseif($type==2){
			$field='city_id';
		}elseif($type==3){
			$field='area_id';
		}elseif($type==4){
			$field='circle_id';
		}
		$area_list=M('Merchant_store')->alias('st')->join('inner join '.C('DB_PREFIX').'area ae on st.'.$field.'=ae.area_id')->limit($limit)->group($field)->getfield($field.',group_concat(store_id) store_arr,area_name',true);
		$data=array();
		$sort_arr=array();
		foreach ($area_list as $k => $v) {
			$store_arr=explode(',', $v['store_arr']);
			$arr=array();
			$arr['name']=$v['area_name'];
			foreach ($store_arr as $k2 => $v2) {
				$arr['total']+=$info[$v2];
			}
			$sort_arr[]=$arr['total'];
			$data[]=$arr;
		}
		array_multisort($sort_arr,SORT_DESC,$data);
		echo json_encode($data);
	}

	//8,9、本月订单数
	public function get_order_info(){
		$type=I('type',0,'intval');
		if($type==0){
			$date=date('Y-m-d 00:00:00');
			$stime=strtotime($date);
			$ftime=strtotime($date.' +1 days');
		}elseif($type==1){
			$date=date('Y-m-01 00:00:00');
			$stime=strtotime($date);
			$ftime=strtotime($date.' +1 months');
		}
		/*$where=array();
		$where['income']=1;
		$where['order_id']=array('neq','');
		$where['use_time']=array('between',array($stime,$ftime));
		$count=M('Merchant_money_list')->where($where)->count(1);*/

		$where=array();
		$where['paid']=1;
		$where['pay_time']=array('between',array($stime,$ftime));
		if(!empty($this->start_time)){
			$where['create_time']=array('egt',$this->start_time);
		}
		$count=M('System_order')->where($where)->count(1);
		$data=array();
		if($type==0){
			$data['name']=L_('平台本日订单数：');
		}elseif($type==1){
			$data['name']=L_(L_('平台本月订单数：'));
		}
		$data['value']=$count;
		$data['prefix']='';
		$data['suffix']='';
		echo json_encode(array($data));
	}

	//10、销售额
	public function get_system_sales(){
		$where=array();
		$where['paid']=1;
		if(!empty($this->start_time)){
			$where['create_time']=array('egt',$this->start_time);
		}
		$sum_total_money=M('System_order')->where($where)->sum('total_price');

		/*$where=array();
		$where['income']=1;
		$sum_total_money=M('Merchant_money_list')->where($where)->sum('total_money');*/

		$sum_total_money=floor($sum_total_money);
		$data=array();
		$data['name']='';
		$data['value']=$sum_total_money;
		$data['prefix']='';
		$data['suffix']='';
		echo json_encode(array($data));
	}
} 
?>