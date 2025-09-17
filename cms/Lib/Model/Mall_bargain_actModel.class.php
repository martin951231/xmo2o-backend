<?php
//商城砍价活动模型类
class Mall_bargain_actModel extends Model{
	//发起砍价活动
	public function get_bar_act_info($bar_id,$team_id=0){
		$where=array();
		$where['id']=$bar_id;
		if($team_id==0){
			$where['is_del']=0;	
		}		
		$bar_info=M('mall_bargain_act')->where($where)->find();
		$result_arr=array();
		if(empty($bar_info)){
			$result_arr['error']=1;
			$result_arr['msg']=L_('该活动不存在！');
			return $result_arr;
		}
		if($bar_info['status']==0 && $team_id==0){
			$result_arr['error']=1;
			$result_arr['msg']=L_('该活动已失效');
			return $result_arr;
		}
		$time=time();
		if($bar_info['stime']>$time){
			$result_arr['error']=1;
			$result_arr['msg']=L_('该活动未开始！');
			return $result_arr;
		}
		if($bar_info['ftime']<$time && $team_id==0){
			$result_arr['error']=1;
			$result_arr['msg']=L_('该活动已结束！');
			return $result_arr;	
		}
		$result_arr['error']=0;
		$result_arr['msg']=$bar_info;
		return $result_arr;
	}

	//获取进行中的砍价商品列表
	public function get_cut_goods_list($store_id,$page=1,$limit=3,$get_count=0,$user=[]){
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
		/*$where['act.stime']=array('elt',$time);
		$where['act.ftime']=array('egt',$time);*/
		$where['act.status']=1;
		$where['act.is_del']=0;
		$where['_string']="pg.type=3 and pg.is_promote=1 and pg.promote_end_time>".$time;
		$first=($page-1)*$limit;
		//排序
		$order="pg.sort desc,gd.sell_count desc,gd.price asc";
		//字段
		$field_str='distinct(gd.goods_id),gd.store_id,gd.sell_day,gd.use_area,gd.original_stock,gd.name,gd.describe,gd.image,gd.price,gd.old_price,gd.stock_num,gd.sell_count,gd.spec_value,gd.today_sell_spec,act.id bar_id,act.spec_id,act.stime,act.ftime,act.floor_price,act.spec_price,act.bar_total_num';

		$list=$this->alias('act')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->join("inner join ".C('DB_PREFIX').'merchant_store st on st.store_id=gd.store_id')->join('inner join '.C('DB_PREFIX').'mall_promote_goods pg on concat("type",3,"_",act.id)=concat("type",pg.type,"_",pg.act_id)')->where($where)->field($field_str)->limit($first,$limit)->order($order)->select();

		$area_id=D('Shop_goods')->getAreaId($user);
        /*if(!empty($area_id)){*/
            foreach ($list as $k => $v){
                if($v['use_area']==1){
                    $goods_list = D('Shop_goods_area')->where(array('did'=>$v['goods_id'],'store_id'=>array('exp','IS NOT NULL')))->getField('aid',true);
                    if(empty($goods_list)){
                        continue;
                    }
                    //取交集,有则过滤商品
                    $arrs=array_intersect($area_id, $goods_list);
                    if(empty($arrs)){
                        unset($list[$k]);
                        continue;
                    }
                }
            }
            $list=array_values($list);
       /* }*/

		$goods_image=new goods_image();
		$today=date('Ymd');
		foreach ($list as $k => $v) {
			/*判断店铺库存类型*/
	    	$stock_type=M('Merchant_store_shop')->where(array('store_id'=>$v['store_id']))->getfield('stock_type');
	    	$sell_day=$v['stock_type']?$today:$v['sell_day'];

			$img_arr=D('Shop_goods')->reg_good_img($v['image'],$goods_image);
			$v['image'] = thumb_img($img_arr['0']['url'],'170');
			if(!empty($v['spec_id'])){
				$where2=array();
				$where2['id']=array('in',explode('_',$v['spec_id']));
				$spec_arr=M('Shop_goods_spec_value')->where($where2)->order('id asc')->getfield('name',true);
				$spec_str=implode(',',$spec_arr);
				$v['spec_str']=$spec_str;
			}
			if($v['stime']>$time){	//未开始
				$v['act_status']=0;
			}elseif($v['ftime']<$time){	//已结束
				$v['act_status']=2;
			}else{
				$v['act_status']=1;	//进行中
				$v['has_stock']=1;	//是否还存在库存
				if($sell_day==$today){	//校验库存
					if(!empty($v['spec_value'])){
						$arr=explode('#',$v['spec_value']);
						foreach ($arr as $k2 => $v2) {
							$arr2=explode('|',$v2);
							$key=str_replace(':','_',$arr2['0']);
							if($key==$v['spec_id']){	//当前商品规格
								$reg_arr=explode(':',$arr2['1']);
								$stock_num=$reg_arr['3'];
								if($stock_num!=-1){	//设置规格库存，且小于销售量
									$sale_arr=json_decode($v['today_sell_spec'],true);
									if($sale_arr[$key]>=$stock_num){
										$v['has_stock']=0;
									}
								}
							}
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
			$num=$this->alias('act')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->join("inner join ".C('DB_PREFIX').'merchant_store st on st.store_id=gd.store_id')->join('inner join '.C('DB_PREFIX').'mall_promote_goods pg on concat(3,"_",act.id)=concat(pg.type,"_",pg.act_id)')->where($where)->count(1);
			$result=array();
			$result['num']=$num;
			$result['list']=$list;
			return $result;
		}else{
			return $list;	
		}
		
	}

	//获取商品砍价信息
	public function get_cut_goods_info($goods_id,$bar_id=null,$team_id=0){
		$where=array();
		$where['act.goods_id']=$goods_id;
		if($bar_id>0){
			$where['act.id']=$bar_id;
		}
		$time=time();
		/*$where['act.stime']=array('elt',$time);
		$where['act.ftime']=array('egt',$time);
		$where['act.status']=1;*/
		if($team_id==0){
			$where['is_del']=0;
		}
		$bar_info=$this->alias('act')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->field('act.*')->where($where)->order('act.id desc')->find();
		if(!empty($bar_info['spec_id'])){
			$where2=array();
			$where2['id']=array('in',explode('_',$bar_info['spec_id']));
			$spec_arr=M('Shop_goods_spec_value')->where($where2)->order('id asc')->getfield('name',true);
			$spec_str=implode(',',$spec_arr);
			$bar_info['spec_str']=$spec_str;
		}
		$arr=D('Mall_bargain_team')->get_good_price($goods_id,$bar_info['spec_id'],1);
		$bar_info['tprice']=$arr['price'];
		$bar_info['stock_num']=$arr['stock_num'];
		if($team_id>0){
			$bar_info['act_status']=1;
		}else{
			if($bar_info['status']==0 || $bar_info['is_del']==1 || $bar_info['ftime']<$time){
				$bar_info['act_status']=2;
				$bar_info['time_clock']=0;
			}elseif($bar_info['stime']>$time){
				$bar_info['act_status']=0;
				$bar_info['time_clock']=$bar_info['stime']-$time;
			}else{
				$bar_info['act_status']=1;
				$bar_info['time_clock']=$bar_info['ftime']-$time;
			}
		}			
		return (array)$bar_info;
	}

	//获取最新砍价用户信息
	public function get_cut_user_list($sort=0,$page=1,$limit=10){
		$sort='bar_time desc';
		$model=M('mall_bargain_user')->alias('bu')->join('inner join '.C('DB_PREFIX').'user us on bu.user_id=us.uid')->order($sort)->field('bu.*,us.nickname,us.avatar');
		if($limit>0){
			$first=($page-1)*$limit;
			$list=$model->limit($first,$limit)->select();
		}else{
			$list=$model->select();
		}
		$goods_name_arr=$this->alias('act')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->join('inner join '.C('DB_PREFIX').'mall_bargain_team te on act.id=te.bar_id')->getfield('te.id,gd.name',true);
		foreach ($list as $k => $v) {
			if(preg_match('/\d{3}\*{4}\d{4}/',$v['nickname'],$m)|| preg_match('/\d{11}/',$v['nickname'],$m)){
                $v['nickname'] = L_('游客');
            }else{
            	$arr=preg_split('/(?<!^)(?!$)/u',$v['nickname']);
            	if(count($arr)>2){
            		$v['nickname']=$arr['0'].'***'.array_pop($arr);
            	}elseif($count==2){
            		$v['nickname']=$arr['0'].'*';
            	}else{
            		$v['nickname']='*';
            	}
            }
            $v['goods_name']=$goods_name_arr[$v['tid']];
			$list[$k]=$v;
		}
		return (array)$list;

	}

	//获取最新砍价的用户
	public function get_act_user($goods_id=null,$limit=10,$get_count=0){
		$where=array();
		if($goods_id>0){
			$where['act.goods_id']=$goods_id;
		}
		$default_avatar=C('config.site_url').'/tpl/Wap/pure/static/images/new_my/pic-default.png';
		if($get_count==1){
			$data=array();
			$count=$this->alias('act')->join('inner join '.C('DB_PREFIX').'mall_bargain_team team on act.id=team.bar_id')->join('inner join '.C('DB_PREFIX').'user us on team.user_id=us.uid')->count(1);
			$data['count']=$count;
			$list=$this->alias('act')->join('inner join '.C('DB_PREFIX').'mall_bargain_team team on act.id=team.bar_id')->join('inner join '.C('DB_PREFIX').'user us on team.user_id=us.uid')->order('team.id desc')->group('team.user_id')->field('us.nickname,us.avatar')->limit($limit)->select();
			foreach ($list as $k => $v) {
				$v['nickname']=str_replace_name($v['nickname']);
				$v['avatar']=$v['avatar']?$v['avatar']:$default_avatar;
				$list[$k]=$v;	
			}
			$data['head_list']=$list;
			return $data;
		}else{
			$list=$this->alias('act')->join('inner join '.C('DB_PREFIX').'mall_bargain_team team on act.id=team.bar_id')->join('inner join '.C('DB_PREFIX').'user us on team.user_id=us.uid')->order('team.id desc')->field('team.id,us.nickname,us.avatar')->limit($limit)->select();
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