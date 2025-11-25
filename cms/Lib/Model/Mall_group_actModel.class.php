<?php
//商城拼团活动模型表
class Mall_group_actModel extends Model{
	//获取拼团商品信息
	public function get_group_info($goods_id=null,$type=1){
		$where=array();
		$where['good_id']=$goods_id;
		if($type=1){
			$time=time();
			$where['stime']=array('elt',$time);
			$where['ftime']=array('egt',$time);
		}
		$info=$this->where($where)->find();
		if($info){
			$info['join_price_arr']=explode('/',$info['join_price']);
			$info['team_price_arr']=explode('/',$info['team_price']);
		}
		return (array)$info;
	}

	//根据拼团活动获取拼团信息
	public function get_group_by_id($group_id,$goods_id=null,$goods_info=null){
		$info=$this->where(array('id'=>$group_id))->find();
		$team_price_arr=explode('/',$info['team_price']);
		$info['team_price']=$team_price_arr;
		$join_price_arr=explode('/',$info['join_price']);
		$info['join_price']=$join_price_arr;
		sort($join_price_arr);
		$info['group_price']=$join_price_arr['0'];
		sort($join_price_arr);
		$info['join_group_price']=$join_price_arr['0'];
		$time=time();
		if($info['status']==0 || $info['is_del']==1 || $info['ftime']<$time){
			$info['act_status']=2;
			$info['time_clock']=0;
			$info['has_stock']=1;
		}elseif($info['stime']>$time){
			$info['act_status']=0;
			$info['time_clock']=$info['stime']-$time;
			$info['has_stock']=1;
		}else{
			$info['act_status']=1;
			$info['time_clock']=$info['ftime']-$time;
			$info['has_stock']=$this->check_goods_stock($goods_info['store_id'],$goods_info['sell_day'],$goods_info['original_stock'],$goods_info['spec_value'],$goods_info['today_sell_spec'],$goods_info['stock_num'],$goods_info['sell_count']);
		}
		return (array)$info;
	}

	//获取当前用户参团购买的商品数量（去除取消和退款的）
	public function get_group_goods_num($group_id,$user_id,$goods_id){
		$where=array();
		$where['ord.uid']=$user_id;
		$where['ord.group_id']=$group_id;
		$where['ord.status']=array('not in',array(4,5));
		$where['de.goods_id']=$goods_id;
		$count=M('shop_order')->alias('ord')->join(C('DB_PREFIX').'shop_order_detail de on ord.order_id=de.order_id')->where($where)->sum('de.num');
		return $count;
	}

	//校验活动是否有效
	public function check_group_info($goods_id=null,$group_id='',$team_id=null){
		$where=array();
		if($group_id>0){
			$where['id']=$group_id;
		}else{
			$where['good_id']=$goods_id;
			$where['status']=1;
		}

		if($team_id<=0){
			$where['is_del']=0;
		}
        $info=$this->where($where)->order('id desc')->find();
		$result_arr=array();
		if(empty($info)){
			$result_arr['error']=1;
			$result_arr['msg']=L_('拼团不存在或已失效！');
			return $result_arr;
		}
		if($team_id<=0 && $info['status']==0){
			$result_arr['error']=1;
			$result_arr['msg']=L_('抱歉，该商品暂不支持发起新团！');
			return $result_arr;
		}
		$time=time();
		if($info['stime']>$time){
			$result_arr['error']=1;
			$result_arr['msg']=L_('活动尚未开始！');
			return $result_arr;
		}
		if($team_id<=0 && $info['ftime']<$time){
			$result_arr['error']=1;
			$result_arr['msg']=L_('活动已结束');
			return $result_arr;
		}
		$info['join_price']=explode('/',$info['join_price']);
		$info['team_price']=explode('/',$info['team_price']);
		$result_arr['error']=0;
		$result_arr['data']=$info;
		return $result_arr;
	}

	//判断商品是否还存在库存
	public function check_goods_stock($store_id,$sell_day,$original_stock,$spec_value=null,$today_sell_spec=null,$stock_num=null,$sell_count=0){
		/*判断店铺库存类型*/
    	$stock_type=M('Merchant_store_shop')->where(array('store_id'=>$store_id))->getfield('stock_type');
    	$today=date('Ymd');
    	$sell_day=$stock_type?$today:$sell_day;

		$has_stock=1;
		if($sell_day!=$today){	//每日更新的或今日未购买
			return $has_stock;
		}
		if(!empty($spec_value)){
			$arr=explode('#',$spec_value);
			$sale_arr=json_decode($today_sell_spec,true);
			if(!is_array($sale_arr)){
				return $has_stock;
			}
			$no_stock_num=0;
			foreach ($arr as $k => $v) {
				$arr2=explode('|',$v);
				$key=str_replace(':','_',$arr2['0']);
				$reg_arr=explode(':',$arr2['1']);
				$stock_num=$reg_arr['3'];
				if($stock_num==-1){
					break;	//存在规格不限制数量
				}
				if($sale_arr[$key]<$stock_num){
					break;	//存在规格有库存
				}
				$no_stock_num++;
			}
			if($no_stock_num>=count($arr)){
				$has_stock=0;
			}
		}else{
			if($stock_num!=-1 && $stock_num<=0){
				$has_stock=0;
			}
		}
		return $has_stock;
	}

	//获取店铺设置的正在进行中的拼团商品信息
	public function get_group_goods_list($store_id='',$sort_type=0,$page=1,$limit=3,$get_count=0,$get_promote=1,$user=[]){
		$where=array();
		//店铺条件
    	$where['st.status']=1;
    	//$where['st.have_shop']=1;
    	//商品条件
    	$where['gd.status']=1;
    	//活动条件
		if($store_id>0){
			$where['act.store_id']=$store_id;
		}
		$time=time();
		//$where['act.stime']=array('elt',$time);
		//$where['act.ftime']=array('egt',$time);
		$where['act.status']=1;
		$where['act.is_del']=0;
		//排序
		if($get_promote==1){	//获取推荐的团购商品
			$order='pg.sort desc,';
			$where['_string']="pg.type=2 and pg.is_promote=1 and pg.promote_end_time>".$time;
		}else{
			$order='';
		}		
		if($sort_type==1){
			$order.="act.sales_num desc,gd.sell_count desc,gd.price asc";	
		}else{
			$order.="gd.sell_count desc,gd.price asc";	
		}		
		$first=($page-1)*$limit;		
		$model=$this->alias('act')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->join("inner join ".C('DB_PREFIX').'merchant_store st on st.store_id=gd.store_id');
		if($get_promote==1){
			$model=$model->join('left join '.C('DB_PREFIX').'mall_promote_goods pg on concat("type",2,"_",act.id)=concat("type",pg.type,"_",pg.act_id)');
		}
		$field_str='gd.goods_id,gd.use_area,gd.store_id,gd.sell_day,gd.original_stock,gd.name,gd.describe,gd.image,gd.price,gd.stock_num,gd.sell_count,gd.spec_value,gd.today_sell_spec,act.id group_id,act.complete_num,act.stime,act.ftime,act.team_price,act.join_price,act.sales_num';
		$model=$model->where($where)->field($field_str)->order($order);
		if($limit>0){
			$list=$model->limit($first,$limit)->select();
		}else{
			$list=$model->select();
		}
       //过滤区域展示
        $area_id=D('Shop_goods')->getAreaId($user);
      /*  if(!empty($area_id)){*/
            foreach ($list as $k => $v){
                if($v['use_area']==1){
                    $goods_list = D('Shop_goods_area')->where(array('did'=>$v['goods_id'],'store_id'=>array('exp','IS NOT NULL')))->getField('aid',true);
                    if(empty($goods_list)){
                        continue;
                    }                    //取交集,有则过滤商品
                    $arrs=array_intersect($area_id, $goods_list);
                    if(empty($arrs)){
                        unset($list[$k]);
                        continue;
                    }
                }
            }
            $list=array_values($list);
        /*}*/

		$goods_image=new goods_image();
		$today=date('Ymd');
		foreach ($list as $k => $v) {
			/*判断店铺库存类型*/
	    	$stock_type=M('Merchant_store_shop')->where(array('store_id'=>$v['store_id']))->getfield('stock_type');
	    	$sell_day=$v['stock_type']?$today:$v['sell_day'];

			$img_arr=D('Shop_goods')->reg_good_img($v['image'],$goods_image);
			//$v['image']=$img_arr['0']['url']['s_image'];
			$v['image']=$img_arr['0']['url'];
			$arr=explode('/',$v['join_price']);
			sort($arr);
			//最低价
			$v['group_price']=array_shift($arr);
			if($v['stime']>$time){	//未开始
				$v['act_status']=0;
			}elseif($v['ftime']<$time){	//已结束
				$v['act_status']=2;
			}else{
				$v['act_status']=1;
				$v['has_stock']=1;
				if($sell_day==$today){
					if(!empty($v['spec_value'])){
						$arr=explode('#',$v['spec_value']);
						$sale_arr=json_decode($v['today_sell_spec'],true);
						$no_stock_num=0;
						foreach ($arr as $k2 => $v2) {
							$arr2=explode('|',$v2);
							$key=str_replace(':','_',$arr2['0']);
							$reg_arr=explode(':',$arr2['1']);
							$stock_num=$reg_arr['3'];
							if($stock_num==-1){
								break;	//存在规格不限制数量
							}
							if($sale_arr[$key]<$stock_num){
								break;	//存在规格有库存
							}
							$no_stock_num++;
						}
						if($no_stock_num>=count($arr)){
							$v['has_stock']=0;
						}
					}else{
						if($v['stock_num']!=-1 && $v['stock_num']<=0){
							$v['has_stock']=0;
						}
					}
				}
			}
			$list[$k]=$v;
		}
		$list=(array)$list;
		if($get_count==1){
			$num=$this->alias('act')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->join("inner join ".C('DB_PREFIX').'merchant_store st on st.store_id=gd.store_id')->join('left join '.C('DB_PREFIX').'mall_promote_goods pg on concat(2,"_",act.id)=concat(pg.type,"_",pg.act_id)')->where($where)->count(1);
			$result=array();
			$result['num']=$num;
			$result['list']=$list;
			return $result;
		}else{
			return $list;	
		}		
	}

	//获取店铺拼团商品数量
	public function get_store_group_num($store_id){
		$where=array();
		$where['act.store_id']=$store_id;
		$time=time();
		$where['act.stime']=array('elt',$time);
		$where['act.ftime']=array('egt',$time);
		$where['act.status']=1;
		$where['act.is_del']=0;
		$count=$this->alias('act')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->where($where)->count(1);
		return (int)$count;
	}


	//获取拼团商品信息
	public function get_group_goods_info($group_id=null,$get_reg=0){

	}

	//获取用户购买当前商品的数量
	public function get_user_buy_count($group_id=null,$user_id=null){
		if(empty($user_id)){
			return 0;
		}
		$where=array();
		$where['group_id']=$group_id;
		$where['uid']=$user_id;
		$buy_count=M('shop_order')->where($where)->sum('num');
		return $buy_count;
	}

	//获取店铺成功团队，团场的订单id
	public function get_success_team($mer_id,$store_id,$team_id=null){
		$where=array();
		$where['act.mer_id']=$mer_id;
		$where['act.store_id']=$store_id;
		$where['team.status']=1;
		$model=$this->alias('act')->join('inner join '.C('DB_PREFIX').'mall_group_team team on act.id=team.group_id')->join('inner join '.C('DB_PREFIX').'mall_group_user us on team.id=us.tid');
		if($team_id>0){
			$where['team.id']=$team_id;
			$model=$model->where($where);
		}else{
			$model=$model->where($where);
		}
		$order_arr=$model->field('us.order_id')->select();
		return (array)$order_arr;
	}

	//获取最新发起拼团/参团用户
	public function get_act_user($goods_id=null,$limit=10,$get_count=0){
		$where=array();
		if($goods_id>0){
			$where['act.good_id']=$goods_id;
		}
		$default_avatar=C('config.site_url').'/tpl/Wap/pure/static/images/new_my/pic-default.png';
		if($get_count==1){
			$data=array();
			//获取购买总数
			$count=$this->alias('act')->join('inner join '.C('DB_PREFIX').'mall_group_team team on act.id=team.group_id')->join('inner join '.C('DB_PREFIX').'mall_group_user gu on team.id=gu.tid')->join('inner join '.C('DB_PREFIX').'user us on gu.user_id=us.uid')->where($where)->count(1);
			$data['count']=$count;
			//最新购买的用户信息
			$list=$this->alias('act')->join('inner join '.C('DB_PREFIX').'mall_group_team team on act.id=team.group_id')->join('inner join '.C('DB_PREFIX').'mall_group_user gu on team.id=gu.tid')->join('inner join '.C('DB_PREFIX').'user us on gu.user_id=us.uid')->order('order_id desc')->group('gu.user_id')->field('us.nickname,us.avatar')->where($where)->limit($limit)->select();
			foreach ($list as $k => $v) {
				$v['nickname']=str_replace_name($v['nickname']);
				$v['avatar']=$v['avatar']?$v['avatar']:$default_avatar;
				$list[$k]=$v;	
			}
			$data['head_list']=$list;
			return $data;
		}else{
			$list=$this->alias('act')->join('inner join '.C('DB_PREFIX').'mall_group_team team on act.id=team.group_id')->join('inner join '.C('DB_PREFIX').'mall_group_user gu on team.id=gu.tid')->join('inner join '.C('DB_PREFIX').'user us on gu.user_id=us.uid')->order('order_id desc')->field('order_id,us.nickname,us.avatar')->where($where)->limit($limit)->select();
			foreach ($list as $k => $v) {
				$v['nickname']=str_replace_name($v['nickname']);
				$v['avatar']=$v['avatar']?$v['avatar']:$default_avatar;
				$list[$k]=$v;	
			}
			return $list;
		}
	}
}
?>