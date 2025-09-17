<?php
/**
 * Created by PigCMS.
 * User: jjc.
 * Date: 2019/4/11.
 * Time: 14:17.
 */

/**
 * Class Merchant_store_open_timeModel 店铺营业时间类
 */
class Merchant_store_open_timeModel extends Model{

    public $sort = array(1,2,3);//3个时间段

    /**
     * @desc 获取店铺营业时间
     * @param $mer_id
     * @param $store_id
     * @param $api：接口暂时不支持显示到秒
     * @param $cut：如果不是开启的状态不显示当天的字段【根据status字段判断】
     * @return array
     */
    public function get_store_open_time($mer_id,$store_id,$api=0,$cut=false){
        if(!$mer_id || !$store_id) return array();
        $where = array();
        $where['mer_id'] = $mer_id;
        $where['store_id'] = $store_id;
        $order = 'week ASC,time_sort ASC';
        $data = $this->where($where)->order($order)->select();
        return $data?$this->deal_week_data($data,$api,$cut):$this->get_empty_data($api,$cut);
    }

    /**
     * @desc 以“周几”格式化数组->输出页面
     * @param $data
     * @param $api：接口暂时不支持显示到秒
     * @param $cut：如果不是开启的状态不显示当天的字段【根据status字段判断】
     * @return mixed
     */
    protected function deal_week_data($data,$api=false,$cut=false){
        if(!$data) return $this->get_empty_data($api,$cut);
        $data = $this->fill_data($data);//数据为空的时间段数据库不保存，这里需要填充下
        $week_show = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));
        $return = array();
        foreach($data as $key => $val){
            //客户要求显示到秒，所以不需要截取
            /*if($api){
                $val['open_time']  = substr($val['open_time'],0,strlen($val['open_time'])-3);
                $val['close_time'] = substr($val['close_time'],0,strlen($val['close_time'])-3);
            }*/
            if((!$cut||$val['status']==1) && $week_show[$val['week']]){
                $return[$val['week']]['week_show'] = $week_show[$val['week']];
                $return[$val['week']]['week_status'] = $val['status'];//这里的状态是针对周几的，所以每天的三个时间段状态必须一致，这里可以任取其一
                $return[$val['week']]['time_list'][] = $val;
                $return[$val['week']]['week'] = $val['week'];
            }

        }
        return $return;
    }

    /**
     * @desc 填充数据
     * @param $data
     * @return array
     */
    protected function fill_data($data){
        for($i=0;$i<=6;$i++){
            for($j=1;$j<=count($this->sort);$j++){
                $is = false;//是否存在，不存在需要填充
                $status = 0;
                foreach($data as $key => $val){
                    $mer_id   = $val['mer_id'];
                    $store_id = $val['store_id'];
                    if($val['week']==$i){
                        $status = $val['status'];
                    }
                    if($val['week']==$i && $val['time_sort']==$j){
                        $is = true;
                    }
                }
                if(!$is){
                    $temp = array();
                    $temp['mer_id'] = $mer_id;
                    $temp['store_id'] = $store_id;
                    $temp['week'] = $i;
                    $temp['open_time'] = '00:00:00';
                    $temp['close_time'] = '00:00:00';
                    $temp['time_sort'] = $j;
                    $temp['status'] = $status;
                    $temp['type'] = 0;
                    $data[] = $temp;
                }

            }
        }
        return $data;
    }

    /**
     * @desc 返回一个格式化好的空数组
     * @param $api：接口暂时不支持显示到秒
     * @param $cut：如果不是开启的状态不显示当天的字段【根据status字段判断】
     * @return array
     */
    protected function get_empty_data($api=false,$cut=false){
        if($cut) return array();
        $return = array();
        $week_show = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));
        $sort = $this->sort;
        foreach($week_show as $key => $val){
            $temp = array();
            $temp['week_show']  = $val;
            $temp['week_status'] = 0;
            $temp['week'] = $key;
            $list = array();
            foreach($sort as $k => $v){
                $l = array();
                $l['open_time'] = $api?'00:00':'00:00:00';
                $l['close_time'] = $api?'00:00':'00:00:00';
                $l['time_sort'] = $v;
                $list[] = $l;
            }
            $temp['time_list'] = $list;
            $return[] = $temp;
        }
        return $return;
    }

    /**
     * @desc 新增&编辑店铺营业时间
     * @param $mer_id
     * @param $store_id
     * @param array $week_status
     * @param array $open_time
     * @param array $close_time
     * @param bool $add 是->新增，否->编辑
     * @return bool
     */
    public function add_edit_data($mer_id,$store_id,$week_status=array(),$open_time=array(),$close_time=array(),$add=false){
        if(!$mer_id || !$store_id) return false;
        $save_data = $this->format_data($week_status,$open_time,$close_time);
        if(!$add){//修改 需要删除以前数据，重新添加
            $del_where = array();
            //$del_where['mer_id'] = $mer_id;
            $del_where['store_id'] = $store_id;
            $this->where($del_where)->delete();
        }
        foreach($save_data as $key => $val){
            if($val['time_sort']==1 || ($val['open_time']<>'00:00:00' || $val['close_time']<>'00:00:00')){
                $data = array();
                $data['mer_id']      =  $mer_id;
                $data['store_id']    =  $store_id;
                $data['week']        =  $val['week'];
                $data['open_time']   =  $val['open_time'];
                $data['close_time']  =  $val['close_time'];
                $data['time_sort']   =  $val['time_sort'];
                $data['status']      =  $val['status'];
                $this->data($data)->add();
            }
        }
        $this->checkBusinessTime($store_id);
        return true;
    }

    /**
     * @desc api端:新增&编辑营业时间
     * @param $mer_id
     * @param $store_id
     * @param $data
     * @param $add
     * @return bool
     */
    public function api_add_edit_data($mer_id,$store_id,$data,$add=false){
        if(!$mer_id || !$store_id) return false;
        $save_data = $this->format_api_data($data);
        if(!$add){//修改：删除以前数据，新数据重新添加
            $del_where = array();
            $del_where['mer_id'] = $mer_id;
            $del_where['store_id'] = $store_id;
            $this->where($del_where)->delete();
        }
        foreach($save_data as $key => $val){
            if($val['time_sort']==1 || ($val['open_time']<>'00:00' || $val['close_time']<>'00:00')){
                $data = array();
                $data['mer_id']      =  $mer_id;
                $data['store_id']    =  $store_id;
                $data['week']        =  $val['week'];
                $data['open_time']   =  $val['open_time'];
                $data['close_time']  =  $val['close_time'];
                $data['time_sort']   =  $val['time_sort'];
                $data['status']      =  $val['status'];
                $this->data($data)->add();
            }
        }

//        else{//修改
//            foreach($save_data as $key => $val){
//                $where = array();
//                $where['mer_id']     =  $mer_id;
//                $where['store_id']   =  $store_id;
//                $where['week']       =  $val['week'];
//                $where['time_sort']  =  $val['time_sort'];
//
//                $data  = array();
//                $data['open_time']   =  $val['open_time'];
//                $data['close_time']  =  $val['close_time'];
//                $data['status']      =  $val['status'];
//                $this->where($where)->data($data)->save();
//                if($this->where($where)->find()){//如果找到了就修改
//                    if($val['time_sort']==1 || $val['open_time']<>'00:00' || $val['close_time']<>'00:00'){//如果不是00-00就修改
//                        $this->where($where)->data($data)->save();
//                    }else{
//                        $this->where($where)->delete();
//                    }
//                }else{//如果没找到就新增
//                    if($val['open_time']<>'00:00' || $val['close_time']<>'00:00'){
//                        $add_data = array();
//                        $add_data['mer_id'] = $mer_id;
//                        $add_data['store_id'] = $store_id;
//                        $add_data['week'] = $val['week'];
//                        $add_data['time_sort'] = $val['time_sort'];
//                        $add_data['open_time'] = $val['open_time'];
//                        $add_data['close_time'] = $val['close_time'];
//                        $add_data['status'] = $val['status'];
//                    }
//                }
//            }
//        }
        $this->checkBusinessTime($store_id);
        return true;
    }

    /**
     * @desc 格式化api端传过来的数据
     * @param $data
     * @return array
     */
    protected function format_api_data($data){
        $return = array();
        if($data){
            foreach($data as $key => $val){
                foreach($val['time_list'] as $k => $v){
                    $temp = array();
                    $temp['week'] = $key;
                    $temp['time_sort']  = $k+1;
                    $temp['open_time']  = $v['openTime'];
                    $temp['close_time'] = $v['closeTime'];
                    $temp['status'] = $val['week_status'];
                    $return[] = $temp;
                }
            }
        }
        //dump($return);die;
        return $return;
    }

    /**
     * @desc 格式化页面post过来的数组
     * @param $week_status
     * @param $open_time
     * @param $close_time
     * @return array
     */
    protected function format_data($week_status,$open_time,$close_time){
        $week = array(0,1,2,3,4,5,6);
        $sort = $this->sort;
        $return = array();
        foreach($week as $key =>$val){
            foreach($sort as $k=>$v){
                $temp = array();
                $temp['week'] = $val;
                $temp['time_sort'] = $v;
                $temp['open_time']  = $open_time[$val][$v]?$open_time[$val][$v]:'00:00:00';
                $temp['close_time'] = $close_time[$val][$v]?$close_time[$val][$v]:'00:00:00';
                $temp['status']     = $week_status[$val]==1?$week_status[$val]:0;
                $return[] = $temp;
            }
        }
        return $return;
    }

    /**
     * @desc 删除数据
     * @param $mer_id
     * @param $store_id
     * @return bool
     */
    public function del_data($mer_id,$store_id){
        if(!$mer_id ||!$store_id) return false;
        $where = array();
        $where['mer_id']      =  $mer_id;
        $where['store_id']    =  $store_id;
        $result = $this->where($where)->delete();
        return $result===false?false:true;
    }

    /**
     * @desc 获取多个店铺营业时间
     * @param array $store_id
     * @return array
     */
    public function get_all_time_by_store($store_id=array()){
        if (empty($store_id)) return array();
        $where = array();
        $where['store_id'] = array('in',$store_id);
        $order = 'week ASC,time_sort ASC';
        $data = $this->where($where)->order($order)->select();
        $return = array();
        if($data){
            $week_show = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));
            foreach($data as $key =>$val){
                $return[$val['store_id']][$val['week']]['week'] = $val['week'];
                $return[$val['store_id']][$val['week']]['current'] = $val['week']==date("w")?true:false;
                $return[$val['store_id']][$val['week']]['week_show'] = $week_show[$val['week']];
                //开关，判断是否继续循环
                $return[$val['store_id']][$val['week']]['close'] = $return[$val['store_id']][$val['week']]['close']?$return[$val['store_id']][$val['week']]['close']:0;

                if($val['status']==1){//营业时间开启状态
                    if($val['time_sort']==1 && $val['open_time']=='00:00:00' && $val['close_time']=='00:00:00'){
                        $return[$val['store_id']][$val['week']]['time_show'] = '24小时营业';
                        $return[$val['store_id']][$val['week']]['close'] = 1;
                    }elseif(!$return[$val['store_id']][$val['week']]['close']){
                        if ( $val['open_time'] != '00:00:00' || $val['close_time'] != '00:00:00') {
                            $return[$val['store_id']][$val['week']]['time_show'] .= ',' . substr($val['open_time'], 0, -3) . '~' . substr($val['close_time'], 0, -3);
                        }
                        $return[$val['store_id']][$val['week']]['time_show'] = trim($return[$val['store_id']][$val['week']]['time_show'],',');
                    }
                }else{//关闭状态 显示“不营业”；
                    $return[$val['store_id']][$val['week']]['time_show'] = '不营业';
                }
            }
        }
        return $return;
    }

    /**
     * @desc 获取单个店铺营业时间
     * @param string $store_id
     * @return array
     */
    public function get_one_time_by_store($store_id=''){
        if(!$store_id) return array();
        $where = array();
        $where['store_id'] = $store_id;
        $order = 'week ASC,time_sort ASC';
        $data = $this->where($where)->order($order)->select();
        $return = array();
        if($data){
            $week_show = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));
            $ext = [];//跨天的营业时间放在这里
            foreach($data as $key =>$val){
                $return[$val['week']]['week'] = $val['week'];
                $return[$val['week']]['current']  = $val['week']==date("w")?true:false;
                $return[$val['week']]['week_show'] = $week_show[$val['week']];
                //开关，判断是否继续循环
                $return[$val['week']]['close'] = $return[$val['week']]['close']?$return[$val['week']]['close']:0;

                if($val['status']==1){//营业时间开启状态
                    if($val['time_sort']==1 && $val['open_time']=='00:00:00' && $val['close_time']=='00:00:00'){
                        $return[$val['week']]['time_show'] = L_('24小时营业');
                        $return[$val['week']]['close'] = 1;
                    }elseif(!$return[$val['store_id']][$val['week']]['close']){
                        if($val['open_time']=='00:00:00'){
                            $ext[$val['week']]['open_time'] = $val['open_time'];
                            $ext[$val['week']]['close_time'] = $val['close_time'];
                            continue;
                        }elseif($val['close_time']=='23:59:59'||$val['close_time']=='23:59:00'){
                            $ext[$val['week']]['open_time'] = $val['open_time'];
                            continue;
                        }elseif ( $val['open_time'] != '00:00:00' || $val['close_time'] != '00:00:00') {
                            $open_timestamp  = strtotime($val['open_time']);
                            $close_timestamp = strtotime($val['close_time']);
                            if($close_timestamp<$open_timestamp){//如果结束时间小于开始时间就是到次日
                                if($val['open_time']=='00:00:00'){
                                    $return[$val['week']]['time_show'] .= ',' . substr($val['open_time'], 0, -3) . '~'.L_('凌晨') . substr($val['close_time'], 0, -3);
                                }else{
                                    $return[$val['week']]['time_show'] .= ',' . substr($val['open_time'], 0, -3) . '~'.L_('次日') . substr($val['close_time'], 0, -3);
                                }

                            }else{
                                $return[$val['week']]['time_show'] .= ',' . substr($val['open_time'], 0, -3) . '~' . substr($val['close_time'], 0, -3);
                            }

                        }
                        $return[$val['week']]['time_show'] = trim($return[$val['week']]['time_show'],',');
                    }
                    $return[$val['week']]['week_status'] = 1;
                }else{//关闭状态 显示“不营业”；
                    $return[$val['week']]['time_show'] = L_('不营业');
                    $return[$val['week']]['week_status'] = 0;
                }
                if($val['time_sort']=='3'){
                    unset($return[$val['week']]['close']);
                }
            }
        }
        if(!empty($ext)){
            foreach ($return as $key => $value) {
                if($ext[$value['week']]){
                    if($return[$key]['time_show']){
                        if($ext[$value['week']]['open_time']=='00:00:00'){
                            $return[$key]['time_show'] .= ','.substr($ext[$value['week']]['open_time'], 0, -3) . '~' .substr($ext[$value['week']]['close_time'], 0, -3);
                        }else{
                            $return[$key]['time_show'] .= ','.substr($ext[$value['week']]['open_time'], 0, -3) . '~' .L_('次日') . substr($ext[$value['week']]['close_time'], 0, -3);
                        }

                    }else{
                        if($ext[$value['week']]['open_time']=='00:00:00'){
                            $return[$key]['time_show'] =  substr($ext[$value['week']]['open_time'], 0, -3) . '~' . substr($ext[$value['week']]['close_time'], 0, -3);
                        }else{
                            $return[$key]['time_show'] =  substr($ext[$value['week']]['open_time'], 0, -3) . '~' .L_('次日') . substr($ext[$value['week']]['close_time'], 0, -3);
                        }

                    }
                }
            }
        }
        return $return;
    }

    /**
     * @desc 获取店铺营业中的时间，
     * @param $store_id
     * @return array 返回周几的数组
     */
    public function get_store_week_time($store_id){
        if(empty($store_id)) return array();
        $where = array();
        $where['store_id'] = $store_id;
        $where['status']   = 1;//营业中的时间
        $order = 'week ASC,time_sort ASC';
        $data = $this->where($where)->order($order)->select();
        $return = array_column($data, 'week');
        $return = array_unique($return);
        $week = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));
        $weeK_arr = array();
        foreach($return as $val){
            if($week[$val]){
                $weeK_arr[] = $week[$val];
            }
        }
        return implode(',',$weeK_arr);
    }

    //处理老数据脚本，上线后跑一遍就行
    public function deal_old_data(){
        $storeModel = M('merchant_store');
        $field = 'store_id,mer_id,open_1,close_1,open_2,close_2,open_3,close_3';
        $data = $storeModel->field($field)->select();
        $week = array(0,1,2,3,4,5,6);
        $sort = array(1,2,3);
        foreach($data as $key =>$val){
            $add_data = array();
            $add_data['store_id'] = $val['store_id'];
            $add_data['mer_id'] = $val['mer_id'];
			if($this->where($add_data)->find()){
				continue;
			}
            foreach($week as $wk => $wv){
                $add_data['week'] = $wv;
                foreach($sort as $sv){
                    $add_data['time_sort'] = $sv;
                    $add_data['open_time'] = $val['open_'.$sv];
                    $add_data['close_time'] = $val['close_'.$sv];
                    $add_data['status'] = 1;
                    $r = $this->add($add_data);
                    if($r){
                        echo '添加成功：'.json_encode($add_data).'</br>';
                    }else{
                        echo '添加失败：'.json_encode($add_data).'</br>';
                    }
                }
            }
        }
    }

    /**此方法已废弃
     * @desc 计划任务（每分钟更新）：获取店铺当天的营业时间并更新到主表merchant_store中去。
     */
    public function run_time_task(){
        //查找店铺列表
        $where = array();
        $where['status'] = 1;
        $r = M('Merchant_store')->field('`store_id`,`city_id`')->group('city_id')->where($where)->select();
        echo M()->getLastSql();
        dump($r);exit;
        $where = array();
        $where['week'] = date("w");
        $order  = 'store_id ASC,time_sort ASC';
        $field  = 'store_id,mer_id,week,open_time,close_time,time_sort,status';

        $time_data = $this->where($where)->field($field)->order($order)->select();
        $save_data = array();
        foreach($time_data as $key =>$val){
            $open_key = 'open_'.$val['time_sort'];
            $close_key = 'close_'.$val['time_sort'];
            $save_data[$val['store_id']]['store_id'] = $val['store_id'];
            $save_data[$val['store_id']]['mer_id']   = $val['mer_id'];
            $save_data[$val['store_id']]['status']   = $val['status'];
            $save_data[$val['store_id']][$open_key]  = $val['open_time'];
            $save_data[$val['store_id']][$close_key] = $val['close_time'];
        }
        if(!empty($save_data)){
            $storeModel = M('Merchant_store');//店铺主表：保存店铺营业时间字段
            $shopModel  = M('Merchant_store_shop');//快店表：关店状态字段
            foreach($save_data as $key => $val){
                $time_where =array();
                $time_where['mer_id'] = $val['mer_id'];
                $time_where['store_id'] = $val['store_id'];

                $time_data = array();
                $time_data['open_1']  = $val['open_1'];
                $time_data['close_1'] = $val['close_1'];
                $time_data['open_2']  = $val['open_2'];
                $time_data['close_2'] = $val['close_2'];
                $time_data['open_3']  = $val['open_3'];
                $time_data['close_3'] = $val['close_3'];
                if($storeModel->where($time_where)->data($time_data)->save()){
                    echo '主表-更新成功：'.json_encode($time_where).json_encode($time_data).'</br>';
                }else{
                    echo '主表-更新失败.'.json_encode($time_where).json_encode($time_data).'</br>';
                }
                $status = $val['status']==1?0:1;//店铺营业时间开启，即正常营业，所以紧急关店状态应该是0
                if($shopModel->where(array('store_id'=>$val['store_id']))->data(array('is_close'=>$status))->save()){
                    echo '快店表-更新成功：'.$val['store_id'].'-'.$status.'</br>';
                }else{
                    echo '快店表-更新失败：'.$val['store_id'].'-'.$status.'</br>';
                }
            }
        }
    }

    /**
     * @desc 更新主表merchant_store店铺营业时间&状态【计划任务调用此方法】
     * @param $store_id：必填
     * @return bool
     */
    public function checkBusinessTime($store_id=0){

        $where = array();
        $where['store_id'] = $store_id;
        $where['week'] = date("w");
        $order = 'time_sort asc';//必须按此排序，否则影响后面业务逻辑
        $data = M('Merchant_store_open_time')->field(true)->order($order)->where($where)->select();
        if(empty($data)){
			// 不能强行改变店铺营业时间，等待手动补新数据
			/* $save_data['open_1'] = '00:00:00';
			$save_data['close_1'] = '00:00:00';
			$save_data['open_2'] = '00:00:00';
			$save_data['close_2'] = '00:00:00';
			$save_data['open_3'] = '00:00:00';
			$save_data['close_3'] = '00:00:00'; */
			$save_data['is_business_open'] = '0';
			M('Merchant_store')->where(array('store_id'=>$store_id))->data($save_data)->save();
            return true;
        }
		
		$this->store_time_init($store_id);//初始化设置店铺营业时间为0且不营业
        $this->set_store_timezone($store_id);//设置店铺时区
        $is_business = 0;//是否在营业时间内

        $now_time = time();
        $save_data = array();

        $ext_num = 0;
        foreach($data as $key => $val){

            $val['time_sort'] += $ext_num;
            $open_key  = 'open_'.$val['time_sort'];
            $close_key = 'close_'.$val['time_sort'];

            $open_time  = strtotime($val['open_time']);//改为时间戳，以便后续比较
            $close_time = strtotime($val['close_time']);
            if($open_time<=$close_time||$val['close_time']=='00:00:00'){//如果结束时间是0点也可以直接写上去
                $save_data[$open_key]  = $val['open_time'];
                $save_data[$close_key] = $val['close_time'];
            }else{//如果结束时间小于开始时间就是次日
                $save_data[$open_key]  = $val['open_time'];
                $save_data[$close_key] = '23:59:59';

                $next_sort =$val['time_sort']+1;
                $next_open_key  = 'open_'.$next_sort;
                $next_close_key = 'close_'.$next_sort;
                $save_data[$next_open_key]  = '00:00:00';
                $save_data[$next_close_key] = $val['close_time'];
                $ext_num += 1;
                $close_time += 60*60*24;
            }

            if($val['status']==1){
                if($now_time>=$open_time && $now_time<=$close_time){//在两个时间段之间是营业中
                    $is_business = 1;
                }elseif($val['open_time']<>'00:00:00' && $now_time>=$open_time && $val['close_time']=='00:00:00'){
                    $is_business = 1;
                }elseif($key == 0 && $val['open_time']=='00:00:00' && $val['close_time']=='00:00:00'){
                    $is_business = 1;
                }
            }
        }

        $save_data['is_business_open'] = $is_business;//店铺营业状态：1营业中，0关店中
        M('Merchant_store')->where(array('store_id'=>$store_id))->data($save_data)->save();
        return true;
    }
	
	//初始化设置主表店铺营业时间为00:00且不营业
	private function store_time_init($store_id){
		$where = array();
		$where['store_id'] = $store_id;
		$data = array();
		$data['open_1'] = '00:00:00';
		$data['close_1'] = '00:00:00';
		$data['open_2'] = '00:00:00';
		$data['close_2'] = '00:00:00';
		$data['open_3'] = '00:00:00';
		$data['close_3'] = '00:00:00';
		$data['is_business_open'] = '0';
		M('Merchant_store')->where($where)->data($data)->save();
	}

    //给店铺设置时区
    public function set_store_timezone($store_id){
        $store_info = M('Merchant_store')->where(array('store_id'=>$store_id))->field('city_id')->find();
        $area_db = M("Area");
        $city_info = $area_db->where(array('area_id'=>$store_info['city_id']))->field('timezone')->find();
        if(!empty($city_info['timezone'])){
            date_default_timezone_set($city_info['timezone']);
        }
    }

    /**
     * @desc 获取店铺下一阶段营业时间
     * @param $store_id
     * @return array
     */
    public function get_next_time($store_id){
        if(empty($store_id)) return array();
        $this->set_store_timezone($store_id);

        $week = array(0,1,2,3,4,5,6);
        $now_week = date("w");
        $pre_week = array_slice($week,0,$now_week);
        $next_week = array_slice($week,$now_week);
        $order_week = implode(',',array_merge($next_week,$pre_week));
        $order = "FIELD (week,{$order_week})";//必须按此排序，否则影响后面逻辑

        $where = array();
        $where['store_id'] = $store_id;
        $where['status'] = 1;
        $data = $this->where($where)->order($order)->select();
		foreach($data as $key => $val){//去除无效数据
			if($val['time_sort']<>1 &&$val['open_time']=='00:00:00' && $val['close_time']=='00:00:00'){
				unset($data[$key]);
			}
		}
        $next_day =$this->get_sort_day($data);
        $return = array();
        $return['date'] = '';//
        $return['time'] = '';
        $return['type'] = '';//1日期+时间，2只有日期（如2019.10.21），3一直不营业
        $advance_day = M('Merchant_store_shop')->where(['store_id'=>$store_id])->getField('advance_day');
        if(empty($advance_day)){
            $return['type'] = 3;
            return $return;
        }
        //dump($advance_day);exit;
        if(empty($next_day)){
            $return['type'] = 3;
        }else{
            if($next_day['week']==$now_week){//当天
                $return['date'] = '';
                $return['time'] = date('H:i',$next_day['time']);
                $return['type'] = 1;
            }elseif(($now_week+1==$next_day['week'] || $now_week+6==$next_day['week'])&&$advance_day>0){
                $return['date'] = L_('明日');
                $return['time'] = date('H:i',$next_day['time']+86400);
                $return['type'] = 1;
            }elseif(($now_week+2==$next_day['week'] || $now_week-5==$next_day['week'])&&$advance_day>1){
                $return['date'] = L_('后天');
                $return['time'] = date('H:i',$next_day['time']+86400);
                $return['type'] = 1;
            }else{
                if($next_day['week']>$now_week){//还在本周
                    $num = $next_day['week']-$now_week;//间隔天数
                }else{//下一周
                    $num = $next_day['week']+7-$now_week;//间隔天数
                }
                if($advance_day>=$num){
                    $return['date'] = date('m月d日 H:i',$next_day['time']+86400*$num);
                    $return['time'] = '';
                    $return['type'] = 2;
                }else{
                    $return['type'] = 3;
                }
                
            }
        }
        return $return;

    }

    /**
     * @desc 获取下一次最近的营业时间，只有最近的那个时段及周几的数据
     * @param $data
     * @return array
     */
    protected function get_sort_day($data){
        if(empty($data)) return array();
        $now_week = date("w");
        $now_time = time();
        $return = array();
        if(!empty($data)){
            foreach($data as $key =>$val){
                if($val['week']==$now_week){//如果是当天，下一段的营业时间必须是当前时间之后
                    $open_time=strtotime($val['open_time']);
                    if($now_time<$open_time){
                        $sort_day[$val['week']][] = strtotime($val['open_time']);
                    }
                }else{
                    $sort_day[$val['week']][] = strtotime($val['open_time']);
                }
            }
            if(!empty($sort_day)){
                foreach($sort_day as $k => $v){
                    $return['week'] = $k;
                    $return['time'] = min($v);
                    break;//只需要获取第一个
                }
            }
        }
        return $return;
    }

    //获取店铺一周中的营业时间
    public function get_time_by_week($store_id){
        if(empty($store_id)) return array();
        $where = array();
        $where['store_id'] = $store_id;
        $where['status'] = 1;
        $data = $this->where($where)->order('week asc,time_sort asc')->select();
        $return = array();
        if($data){
            foreach($data as $key => $val){
                $return[$val['week']][] = $val;
            }
        }
        return $return;
    }


}
