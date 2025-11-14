<?php

class Village_group_activityModel extends Model
{
    //获取置顶的每日必买活动
    public function getTopActivity($leader_id = 0, $activity_id = 0, $hasgoods = true){
        if(!$activity_id){
        	$where = [
        		'is_top' => 1,
        		'type' => 1,//每日必买
        		'status' => 1,
        		'start_time' => ['elt', time()],
        		'end_time' => ['gt', time()]
        	];
        }
        else{
            $where['activity_id'] = $activity_id;
        }
    	$data = [];
    	$data = $this->where($where)->find();

    	if($data){
	    	$data['img'] = $data['img'] ? replace_file_domain($data['img']) : '';
            $data['share_img'] = $data['share_img'] ? replace_file_domain($data['share_img']) : '';
            if($hasgoods){
    	    	$goodsModel = D("Village_group_goods");
    	    	$data['goods_list'] = $goodsModel->get_goods($leader_id, 0, $data['activity_id'], 1, 3, ['goods.is_new_user' => 0]);
            }
	    }
	    else{
	    	$data = [];
	    }
    	return $data;
    }

    //获取所有限时抢购活动
    public function getLimitBuyActivity($leader_id = 0, $now_activity_id = 0, $hasgoods = true){
        $where = [
            'type' => 2,//限时抢购
            'status' => 1
        ];
        $where['_string'] = "start_time >= ".strtotime(date('Y-m-d 00:00:00'))." and start_time <= ".strtotime(date('Y-m-d 23:59:59'));//只取今天的活动
        $activity_list = $this->where($where)->order('start_time asc')->field("activity_id,activity_name,start_time,end_time")->select();
        $current_time = time();
        $point = "";
        $tips = "";
        $count_down = 0;
        foreach ($activity_list as $key => $value) {
            $activity_list[$key]['title'] = date('H:i', $value['start_time']);
            if($value['end_time'] < $current_time){
                $activity_list[$key]['status'] = 1;//已开抢
            }
            elseif($value['start_time'] > $current_time){
                $activity_list[$key]['status'] = 2;//即将开抢
            }
            else{
                $activity_list[$key]['status'] = 3;//正在开抢   
                if(empty($now_activity_id)){
                    $now_activity_id = $value['activity_id'];
                }
                $count_down = $value['end_time'] - $current_time;
                $point = date('H:i', $value['end_time']);
                $tips = "抢购即将结束";
            }
        }
        if($now_activity_id == 0){
            //如果没有正在开抢的，那就显示已开抢的最后一个
            foreach ($activity_list as $key => $value) {
                if($value['status'] == 1){
                    $now_activity_id = $value['activity_id'];
                }
            }
        }
        if($now_activity_id == 0){
            //如果还是没有，那就显示未开抢的第一个
            foreach ($activity_list as $key => $value) {
                if($value['status'] == 2){
                    $count_down = $value['start_time'] - $current_time;
                    $now_activity_id = $value['activity_id'];
                    $point = date('H:i', $value['start_time']);
                    $tips = "抢购即将开始";
                    break;
                }
            }
        }
        $return = [
            'activity_list' => $activity_list,
            'count_down' => $count_down,//倒计时
            'point' => $point,
            'tips' => $tips,
            'default_activity_id' => $now_activity_id,
            'goods_list' => []
        ];
        if($hasgoods){
            //得到默认活动的商品list
            $goodsModel = D("Village_group_goods");
            $return['goods_list'] = $goodsModel->get_goods($leader_id, 0, $now_activity_id, 1, 4, ['goods.is_new_user' => 0]);
            foreach ($return['goods_list'] as $key => $value){
                $goods_activity_info = D("Village_group_activity_goods")->getActivityInfo($value['goods_id'], 2);
                if($goods_activity_info['activity_id'] == $now_activity_id){
                    if($goods_activity_info && $goods_activity_info['buy_price'] < $value['price']){
                        $return['goods_list'][$key]['old_price'] = $return['goods_list'][$key]['price'];
                        $return['goods_list'][$key]['price'] = $goods_activity_info['buy_price'];
                    }
                }
            }
        }
        return $return;
    }

    //获取当前正在进行的活动
    public function getNowActivity($type = 2){
        $where = [
            'start_time' => ['elt', time()],
            'end_time' => ['gt', time()],
            'status' => 1,
            'type' => $type,
        ];
        $info = $this->where($where)->find();
        return $info;
    }
    
}