<?php
/**
 * 店铺营业时间service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/23 17:24
 */

namespace app\merchant\model\service;
use app\common\model\db\Area;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\db\MerchantStoreOpenTime as MerchantStoreOpenTimeModel;
use app\merchant\model\db\MerchantStoreOpenTime;
use app\shop\model\service\store\MerchantStoreShopService;

class MerchantStoreOpenTimeService {
    public $merchantStoreOpenTimeModel = null;
    public $weekShow = null;
    public $sort = array(1,2,3);//3个时间段
    public function __construct()
    {
        $this->merchantStoreOpenTimeModel = new MerchantStoreOpenTimeModel();
        $this->weekShow = [L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六')];
    }


    /**
     * 获得今日营业时间
     * @param $storeId int 店铺id
     * @author 衡婷妹
     * @return array
     */
    public function getTodayOpenTime($storeId)
    {
        $where = array();
        $where['store_id'] = $storeId;
        $where['week'] = date("w");
        $order = [
            'time_sort' => 'ASC'
        ];
        $returnArr = [];
        $data = (new MerchantStoreOpenTimeService())->getList($where, $order);
        foreach ($data as $key => $_time){
            if($_time['status']==0){
                continue;
            }

            if($key == 0){
                if($_time['open_time'] == '00:00:00' && ($_time['close_time'] == '00:00:00' || $_time['close_time'] == '23:59:59')){
                    $returnArr[] = [
                        'time_str' => L_('全天营业')
                    ];
                    break;
                }else{
                    $returnArr[] = [
                        'time_str' => $_time['open_time'].'-'.$_time['close_time']
                    ];
                }
            }else{
                $returnArr[] = [
                    'time_str' => $_time['open_time'].'-'.$_time['close_time']
                ];
            }
        }
        return $returnArr;
    }

    /**
     * @desc 获取单个店铺营业时间
     * @param string $storeId
     * @return array
     */
    public function getShowTimeByStore($storeId){
        if(!$storeId) return [];
        
        // 获得营业时间
        $where['store_id'] = $storeId;
        $order = [
            'week' => 'ASC',
            'time_sort' => 'ASC',
        ];
        $data = $this->getList($where, $order);
        
        $return = array();
        if($data){
            $ext = [];//跨天的营业时间放在这里
            foreach($data as $key =>$val){
                $return[$val['week']]['week'] = $val['week'];
                $return[$val['week']]['current']  = $val['week']==date("w") ? true : false;
                $return[$val['week']]['week_show'] = $this->weekShow[$val['week']];

                //开关，判断是否继续循环
                $return[$val['week']]['close'] = isset($return[$val['week']]['close']) && $return[$val['week']]['close'] ? $return[$val['week']]['close'] : 0;
                if($val['status']==1){//营业时间开启状态
                    if($val['time_sort']==1 && $val['open_time']=='00:00:00' && $val['close_time']=='00:00:00'){
                        $return[$val['week']]['time_show'] = '24小时营业';
                        $return[$val['week']]['close'] = 1;
                    }elseif(!isset($return[$val['week']]['close']) || !$return[$val['week']]['close']){
                        if($val['open_time']=='00:00:00' && $val['time_sort'] != 1){
                            $ext[$val['week']]['close_time'] = $val['close_time'];
                            continue;
                        }elseif(($val['close_time']=='23:59:59'||$val['close_time']=='23:59:00') && $val['time_sort'] != 1){
                            $ext[$val['week']]['open_time'] = $val['open_time'];
                            continue;
                        }elseif ( $val['open_time'] != '00:00:00' || $val['close_time'] != '00:00:00') {
                            $open_timestamp  = strtotime($val['open_time']);
                            $close_timestamp = strtotime($val['close_time']);
                            if($close_timestamp<$open_timestamp){//如果结束时间小于开始时间就是到次日
                                if($val['open_time']=='00:00:00'){
                                    $return[$val['week']]['time_show'] = isset($return[$val['week']]['time_show']) ?  $return[$val['week']]['time_show']. ',' . substr($val['open_time'], 0, -3) . '~'.L_('凌晨') . substr($val['close_time'], 0, -3) : substr($val['open_time'], 0, -3) . '~'.L_('凌晨') . substr($val['close_time'], 0, -3);
                                }else{
                                    $return[$val['week']]['time_show'] = isset($return[$val['week']]['time_show']) ? $return[$val['week']]['time_show'] . ',' . substr($val['open_time'], 0, -3) . '~'.L_('次日') . substr($val['close_time'], 0, -3) :  substr($val['open_time'], 0, -3) . '~'.L_('次日') . substr($val['close_time'], 0, -3);
                                }

                            }else{
                                $return[$val['week']]['time_show'] = $return[$val['week']]['time_show'] ?? '';
                                $return[$val['week']]['time_show'] .= ',' . substr($val['open_time'], 0, -3) . '~' . substr($val['close_time'], 0, -3);
                            }

                        }
                        $return[$val['week']]['time_show'] = trim($return[$val['week']]['time_show'] ?? '',',');
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
                if(isset($ext[$value['week']]) && $ext[$value['week']]){
                    if(isset($return[$value['week']]['time_show']) && $return[$value['week']]['time_show']){

                        $openTime = isset($ext[$value['week']]['open_time']) ?  substr($ext[$value['week']]['open_time'], 0, -3) : '';
                        $closeTime = isset($ext[$value['week']]['close_time']) ?  substr($ext[$value['week']]['close_time'], 0, -3) : '';

                        $return[$key]['time_show'] .= ','.$openTime. '~' . L_('次日') . $closeTime;
                    }else{
                        $openTime = isset($ext[$value['week']]['open_time']) ?  substr($ext[$value['week']]['open_time'], 0, -3) : '';
                        $closeTime = isset($ext[$value['week']]['close_time']) ?  substr($ext[$value['week']]['close_time'], 0, -3) : '';
                        $return[$key]['time_show'] =  $openTime. '~' . L_('次日') . $closeTime;
                    }
                }
            }
        }
        $returnArr = [];
        $flag = 1;
        $timeShow = '';
        $return = array_values($return);
        foreach ($return as $key => $value){
           if($key!= 0 && $value['time_show'] != $timeShow){
               $flag = 0;
           };
            $timeShow = $value['time_show'];
        }
        if($flag && $return){
            $return =[$return[0]];
            $return[0]['week_show'] = L_('周一至周日');
        }
        return array_values($return);
    }
    
    /**
     * @desc 获取店铺下一阶段营业时间
     * @param $storeId int
     * @param $isShop int 是否否外卖店铺 1-是0-否
     * @return array
     */
    public function getNextTime($storeId,$isShop = 0){
        if(empty($storeId)) return array();

        // 设置时区
        $this->setStoreTimezone($storeId);

        $week = array(0,1,2,3,4,5,6);
        $nowWeek = date("w");
        $pre_week = array_slice($week,0,$nowWeek);
        $next_week = array_slice($week,$nowWeek);
        $order_week = implode(',',array_merge($next_week,$pre_week));
        $order = "FIELD (week,{$order_week})";//必须按此排序，否则影响后面逻辑

        $where = array();
        $where['store_id'] = $storeId;
        $where['status'] = 1;
        $data = $this->getList($where, $order);

        //去除无效数据
        foreach($data as $key => $val){
            if($val['time_sort']<>1 &&$val['open_time']=='00:00:00' && $val['close_time']=='00:00:00'){
                unset($data[$key]);
            }
        }
        $nextDay = $this->getSortDay($data);

        $return = array();
        $return['date'] = '';//
        $return['time'] = '';
        
        // 外卖可提前预定时间
        $advanceDay = 0;
        if($isShop){
            $return['type'] = '';//1日期+时间，2只有日期（如2019.10.21），3一直不营业
            $shopStore = (new MerchantStoreShopService())->getStoreByStoreId($storeId);
            $advanceDay = $shopStore['advance_day'];
            if(empty($advanceDay)){
                $return['type'] = 3;//无营业时间
                return $return;
            }
        }
        if(empty($nextDay)){
            $return['type'] = 3;//无营业时间
        }else{
            if($nextDay['week']==$nowWeek){//当天
                $return['date'] = '';
                $return['time'] = date('H:i',$nextDay['time']).'-'.date('H:i',$nextDay['close_time']);
                $return['type'] = 1;//date + time
            }elseif(($nowWeek+1==$nextDay['week'] || $nowWeek+6==$nextDay['week'])&&(!$isShop || $advanceDay>0)){
                $return['date'] = L_('明日');
                $return['time'] = date('H:i',$nextDay['time']+86400).'-'.date('H:i',$nextDay['close_time']);
                $return['type'] = 1;
            }elseif(($nowWeek+2==$nextDay['week'] || $nowWeek-5==$nextDay['week'])&&(!$isShop || $advanceDay>1)){
                $return['date'] = L_('后天');
                $return['time'] = date('H:i',$nextDay['time']+86400).'-'.date('H:i',$nextDay['close_time']);
                $return['type'] = 1;
            }else{
                if($nextDay['week']>$nowWeek){//还在本周
                    $num = $nextDay['week']-$nowWeek;//间隔天数
                }else{//下一周
                    $num = $nextDay['week']+7-$nowWeek;//间隔天数
                }
                if(!$isShop || $advanceDay>=$num){
                    $return['date'] = date('m月d日 H:i',$nextDay['time']+86400*$num).'-'.date('H:i',$nextDay['close_time']);
                    $return['time'] = '';
                    $return['type'] = 2;//只有日期
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
    protected function getSortDay($data){
        if(empty($data)) return array();
        $nowWeek = date("w");
        $now_time = time();
        $return = array();
        if(!empty($data)){
            foreach($data as $key =>$val){
                if($val['week']==$nowWeek){//如果是当天，下一段的营业时间必须是当前时间之后
                    $open_time=strtotime($val['open_time']);
                    if($now_time<$open_time){
                        $sort_day[$val['week']][] = [strtotime($val['open_time']),strtotime($val['close_time'])];
                    }
                }else{
                    $sort_day[$val['week']][] = [strtotime($val['open_time']),strtotime($val['close_time'])];
                }
            }
            if(!empty($sort_day)){
                foreach($sort_day as $k => $v){
                    $return['week'] = $k;
                    $return['time'] = $v[0][0];
                    $return['close_time'] = $v[0][1];
                    break;//只需要获取第一个
                }
            }
        }
        return $return;
    }
    
    /**
     * 获取店铺一周中的营业时间
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
     public function getTimeByWeek($storeId){
        if(empty($storeId)) return [];
        $where = [];
        $where['store_id'] = $storeId;
        $where['status'] = 1;
        $order['week'] = 'ASC';
        $order['time_sort'] = 'ASC';
        $data = $this->getList($where,$order);
        $return = [];
        if($data){
            foreach($data as $key => $val){
                $return[$val['week']][] = $val;
            }
        }
        return $return;
    }



    //给店铺设置时区
    public function setStoreTimezone($storeId){
        $store = (new MerchantStoreService())->getStoreByStoreId($storeId);
        $city = (new \app\common\model\service\AreaService())->getAreaByAreaId($store['city_id']);
        if(!empty($city['timezone'])){
            date_default_timezone_set($city['timezone']);
        }
    }

    /**
     * 更新数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function getList($where, $order = [], $field = true) {
        if(empty($where)){
           return [];
        }
        
        $result = $this->merchantStoreOpenTimeModel->getList($where, $order, $field);
        if(!$result){
            return [];
        }
        
        return $result->toArray(); 
    }

    /**
     * 更新数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || !$data){
           return false;
        }
        
        $result = $this->merchantStoreOpenTimeModel->updateThis($where, $data);
        if($result === false){
            return false;
        }
        
        return $result; 
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
            $del_where = [['store_id','=',$store_id]];
            (new MerchantStoreOpenTime())->where($del_where)->delete();
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
                (new MerchantStoreOpenTime())->add($data);
            }
        }
        $this->checkBusinessTime($store_id);
        return true;
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
     * @desc 更新主表merchant_store店铺营业时间&状态【计划任务调用此方法】
     * @param $store_id：必填
     * @return bool
     */
    public function checkBusinessTime($store_id=0){

        $where = [['store_id','=',$store_id],['week','=',date("w")]];
        $order = 'time_sort asc';//必须按此排序，否则影响后面业务逻辑
        $data = (new MerchantStoreOpenTime())->field(true)->order($order)->where($where)->select()->toArray();
        if(empty($data)){
            $save_data['is_business_open'] = '0';
            //M('Merchant_store')->where(array('store_id'=>$store_id))->data($save_data)->save();
            (new MerchantStore())->updateThis(['store_id'=>$store_id],$save_data);
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
            if($val['time_sort']<=3) {
                $open_key = 'open_' . $val['time_sort'];
                $close_key = 'close_' . $val['time_sort'];
            }
            $open_time  = strtotime($val['open_time']);//改为时间戳，以便后续比较
            $close_time = strtotime($val['close_time']);
            if($open_time<=$close_time||$val['close_time']=='00:00:00'){//如果结束时间是0点也可以直接写上去
                $save_data[$open_key]  = $val['open_time'];
                $save_data[$close_key] = $val['close_time'];
            }else{//如果结束时间小于开始时间就是次日
                if(count($data) < 3){
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
        (new MerchantStore())->updateThis(['store_id'=>$store_id],$save_data);
        return true;
    }

    //初始化设置主表店铺营业时间为00:00且不营业
    private function store_time_init($store_id){
        $data = array();
        $data['open_1'] = '00:00:00';
        $data['close_1'] = '00:00:00';
        $data['open_2'] = '00:00:00';
        $data['close_2'] = '00:00:00';
        $data['open_3'] = '00:00:00';
        $data['close_3'] = '00:00:00';
        $data['is_business_open'] = '0';
        (new MerchantStore())->updateThis(['store_id'=>$store_id],$data);
    }

    //给店铺设置时区
    public function set_store_timezone($store_id){
        $store_info = (new MerchantStore())->getOne(['store_id'=>$store_id],'city_id');
        if(!empty($store_info)){
            $store_info=$store_info->toArray();
            $city_info = (new Area())->getOne(['area_id'=>$store_info['city_id']],'timezone');
            if(!empty($city_info) && !empty($city_info['timezone'])){
                $city_info=$city_info->toArray();
                date_default_timezone_set($city_info['timezone']);
            }
        }
    }
}