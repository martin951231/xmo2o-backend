<?php

class House_village_parking_carModel extends Model
{
    /**
     * 月租车A、B、C、D
     * 临时车A、B、C、D、E、F、G、H
     * 储值车A、B、C、D
     * 免费车A、B
     * 不可改动，只可以后续添加
     * @var array
     */
    public $parking_car_type_arr  = [
        1 => '月租车A',
        2 => '月租车B',
        3 => '月租车C',
        4 => '月租车D',
        5 => '临时车A',
        6 => '临时车B',
        7 => '临时车C',
        8 => '临时车D',
        9 => '临时车E',
        10 => '临时车F',
        11 => '临时车G',
        12 => '临时车H',
        13 => '储值车A',
        14 => '储值车B',
        15 => '储值车C',
        16 => '储值车D',
        17 => '免费车A',
        18 => '免费车B',
    ];
    public $parking_car_type_value  = [
        '月租车A'=>1,
        '月租车B'=>2,
        '月租车C'=>3,
        '月租车D'=>4,
        '临时车A'=>5,
        '临时车B'=>6,
        '临时车C'=>7,
        '临时车D'=>8,
        '临时车E'=>9,
        '临时车F'=>10,
        '临时车G'=>11,
        '临时车H'=>12,
        '储值车A'=>13,
        '储值车B'=>14,
        '储值车C'=>15,
        '储值车D'=>16,
        '免费车A'=>17,
        '免费车B'=>18,
    ];
    public $car_type_arr = [
        [
            'value' => 0,
            'name' => '汽车',
        ],
        [
            'value' => 1,
            'name' => '新能源',
        ],
    ];
    public $car_type_name = [
        '汽车' =>0,
        '新能源' => 1,
    ];
    public $car_type_value = [
        0=>'汽车',
        1=>'新能源',
    ];
    public $parking_d1_car_type_arr  = [
        1 => '月租车A',
        2 => '月租车B',
        3 => '月租车C',
        4 => '月租车D',
    ];
    public $parking_d3_car_type_arr  = [
        1 => '月租车',
        2 => '临时车',
    ];
    //monthCar 月租车
    public $monthCarArr = [1,2,3,4];
    //temporaryCar 临时车（有牌车）
    public $temporaryCarArr = [5,6,7,8,9,10,11,12];
    //storedCar 储值车
    public $storedCarArr = [13,14,15,16];
    //freeCar 免费车
    public $freeCarArr = [17,18];


    //A11停车卡类
    const  Month_A = 1;
    const  Month_B = 2;
    const  Month_C = 3;
    const  Month_D = 4;
    const  Month_E = 5;
    const  Month_F = 6;
    const  Month_G = 7;
    const  Month_H = 8;
    const  Temp_A = 9;
    const  Temp_B = 10;
    const  Temp_C = 11;
    const  Temp_D = 12;
    const  Temp_E = 13;
    const  Temp_F = 14;
    const  Temp_G = 15;
    const  Temp_H = 16;
    const  Stored_A = 17;
    const  Free_A = 18;

    public $parking_a11_car_type_arr  = [
        self::Month_A => '月租车A',
        self::Month_B => '月租车B',
        self::Month_C => '月租车C',
        self::Month_D => '月租车D',
        self::Month_E => '月租车E',
        self::Month_F => '月租车F',
        self::Month_G => '月租车G',
        self::Month_H => '月租车H',
        self::Temp_A => '临时车A',
        self::Temp_B => '临时车B',
        self::Temp_C => '临时车C',
        self::Temp_D => '临时车D',
        self::Temp_E => '临时车E',
        self::Temp_F => '临时车F',
        self::Temp_G => '临时车G',
        self::Temp_H => '临时车H',
        self::Stored_A => '储值车A',
        self::Free_A => '免费车A',
    ];
    //A11月租车卡类
    public $A11_Month=[self::Month_A,self::Month_B,self::Month_C,self::Month_D,self::Month_E,self::Month_F,self::Month_G,self::Month_H];
    //A11临时车卡类
    public $A11_Temp=[self::Temp_A,self::Temp_B,self::Temp_C,self::Temp_D,self::Temp_E,self::Temp_F,self::Temp_G,self::Temp_H];



  /**
   * [get_parking_car_list 获取绑定车辆数量]
   * @return [type] [description]
   */
	public function get_parking_car_list($field='',$where=array(),$join='',$order='',$pageSize=20){
      $count = D('House_village_parking_car')->alias('a')->where($where)->join($join)->count();
      $p = new Page($count,$pageSize,'page');
      $info_list['info_list'] = D('House_village_parking_car')
                              ->alias('a')
                              ->field($field)
                              ->where($where)
                              ->join($join)
                              ->order($order)
                              ->limit($p->firstRow.','.$p->listRows)
                              ->select();
      $info_list['pagebar'] = $p->show();
      if($info_list){
        return $info_list;
      }else{
        return false;
      }
   }

  /**
   * [get_parking_car_one 获取单个车辆信息]
   * @return [type] [description]
   */
  public function get_parking_car_one($where=array()){
     $result = D('House_village_parking_car')->where($where)->find();
     if($result){
      return $result;
     }else{
      return false;
     }
  }

  /**
   * [parking_car_add 添加单个信息]
   * @return [type] [description]
   */
  public function parking_car_add($data=array()){
      $result = D('House_village_parking_car')->data($data)->add();

      return $result;
  }

  /**
   * [parking_car_save 修改车辆信息]
   * @return [type] [description]
   */
  public function parking_car_save($data){
      fdump_api(['编辑车辆信息' => $data, 'data' => $data],'park_temp/editHouseVillageParkingCar_log',1);
      $result = D('House_village_parking_car')->save($data);
      if($result !== false){
          return $result;
      }else{
          return false;
      }
  }

  /**
   * [get_parking_car_info 联表查询]
   * @return [type] [description]
   */
  public function get_parking_car_info($field='',$where=array(),$join=''){
      $info_list = D('House_village_parking_car')->alias('a')->field($field)->where($where)->join($join)->find();
      if($info_list !== false){
        return $info_list;
      }else{
        return false;
      }
  }

  /**
   * [parking_car_del 删除车辆信息]
   * @return [type] [description]
   */
  public function parking_car_del($where, $village_id=0){
    $carInfo = D('House_village_parking_car')->where($where)->find();
    $result = D('House_village_parking_car')->where($where)->delete();
    if($result !== false){
        // 车辆进行了删除 调取删除
        if ($village_id) {
            $house_village_park_config = M('House_village_park_config')->where(array('village_id'=>$village_id))->find();
            if ($house_village_park_config['park_versions']==2 && 'D1'==$house_village_park_config['park_sys_type']) {
                $car_id = $carInfo['car_id'];
                $param = [
                    'is_del' => 1,
                    'car_number' => $carInfo['province'].$carInfo['car_number'],
                ];
                D('ParkService')->handleMonthCard($village_id, $car_id, $param, $carInfo);
            } elseif ($house_village_park_config['park_versions']==2 && $carInfo['card_id']) {
                $park_system = M('Park_system')->where(array('park_id'=>$village_id))->find();
                $service_name = 'month_member_sync';
                $card_id = $carInfo['card_id'];
                $park_data = [];
                $park_data['card_id'] = $card_id;
                $park_data['car_number'] = $carInfo['province'].$carInfo['car_number'];
                $park_data['park_id'] = $village_id;
                $park_data['operate_type'] = 3;
                $json_data = [];
                $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                $json_data['data'] = $park_data;
                $json_data['service_name'] = $service_name;
                $post_data['unserialize_desc'] = serialize($json_data);
                $post_data['status'] = 2;
                $post_data['card_id'] = $card_id;
                $post_data['msg_id'] = $json_data['msg_id'];
                $post_data['village_id'] = $village_id;
                $post_data['park_id'] = $park_system['park_id'];
                $post_data['token'] = $park_system['token'];
                $post_data['car_number'] = $park_data['car_number']?$park_data['car_number']:'';
                $post_data['service_name'] = $service_name;
                $post_data['create_time'] = time();
                M('Park_total_record')->data($post_data)->add();// 车辆删除
            }
        }

        return $result;
    }else{
        return false;
    }
  }

    /**
     * 获取车辆信息
     * @author lijie
     * @date_time 2020/08/06 10:32
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function get_car_lists($where,$field = true)
    {
        $data = $this->where($where)->field($field)->select();
        return $data;
    }

    /**
     * 车辆月租卡类型
     * @param bool $field
     * @param $join
     * @param $where
     * @return bool
     */
    public function get_month_type($field=true,$join,$where)
    {
        $data_list = D('House_village_parking_car')->field($field)->join($join)->where($where)->find();
        if($data_list){
            return $data_list;
        }else{
            return false;
        }
    }
    //多车多位，绑定群组
    public function bind_parking_position($card_id,$car_id=0)
    {
        if ($car_id) {
            $carInfo = D('House_village_parking_car')->where(['car_id'=>$car_id])->find();
            $car_position_id_path = isset($carInfo['car_position_id_path'])?$carInfo['car_position_id_path']:'';
        } elseif ($card_id) {
            $carInfo = D('House_village_parking_car')->where(['card_id'=>$card_id])->find();
            $car_position_id_path = isset($carInfo['car_position_id_path'])?$carInfo['car_position_id_path']:'';
        }
        if($car_position_id_path||$carInfo['car_position_id']){
            $car_position_id = explode(',',$car_position_id_path);
            if (!$car_position_id[0]&&$carInfo['car_position_id']) {
                $car_position_id = [];
                $car_position_id[] = $carInfo['car_position_id'];
            }
            if (count($car_position_id)<=1) {
                // 单个或者没有车位的不进行多车多位绑定
                // 二次确认是否存在 一位多车
                if ($car_position_id[0]) {
                    $whereCar = array(
                        '_string' => '(FIND_IN_SET(' . $car_position_id[0] . ',`car_position_id_path`) OR car_position_id='.$car_position_id[0].')'
                    );
                    $carCount = D('House_village_parking_car')->where($whereCar)->count();
                    if (intval($carCount)<=1) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
            asort($car_position_id);
            $positionIds = D('House_village_parking_position')->where(['position_id'=>['in',$car_position_id]])->order('position_id asc')->getField('position_id',true);
            $position_id_min = array_values($positionIds)[0];
            $data['car_group'] = $position_id_min;
            $data['p_lot_number'] = count($positionIds);
            return $data;
        }else{
            return false;
        }
    }


    public function carToDevice($car_id, $car_info=[],$village_id=0,$park_versions=0,$park_system=[],$parking_position=[],$house_village_park_config=[]) {
        if (!$car_info || !$car_info['province'] || !$car_info['car_number']) {
            $where_car = [
                'car_id' => $car_id,
            ];
            $field_car = 'car_id,village_id,car_position_id,car_type,province,car_number,end_time';
            $car_info = $this->where($where_car)->field($field_car)->find();
            if (empty($car_info)) {
                fdump_api(['车辆信息不存在'.__LINE__,$car_id, $car_info,$village_id],'car/carToDeviceErrLog',1);
                return false;
            }
        }
        if (!$village_id) {
            $village_id = $car_info['village_id'];
            if (empty($village_id)) {
                fdump_api(['小区id不能为空'.__LINE__,$car_id, $car_info,$village_id],'car/carToDeviceErrLog',1);
                return false;
            }
        }
        $db_house_village_parking_position = M('House_village_parking_position');
        $park_system = M('Park_system')->where(array('park_id'=>$village_id))->find();
        $house_village_park_config = M('House_village_park_config')->where(array('village_id'=>$village_id))->find();
        if (empty($house_village_park_config)) {
            $whereGarageConfig = [
                'village_id' => $village_id,
                'garage_id' => $car_info['garage_id'],
            ];
            $house_village_park_config = M('House_village_park_garage_config')->where($whereGarageConfig)->find();
        }
        $parking_position = $db_house_village_parking_position->where(array('position_id'=>$car_info['car_position_id']))->find();
        if (!$park_versions && $house_village_park_config) {
            $park_versions = $house_village_park_config['park_versions'];
        }
        if (empty($park_versions) || $park_versions!=2) {
            fdump_api(['版本号不存在或不是新版',$car_id, $car_info,$village_id,$park_versions,$park_system,$parking_position],'car/carToDeviceErrLog',1);
            return false;
        }
        if (empty($parking_position)) {
            fdump_api(['缺少停车场信息，对应 车辆需要关联停车场'.__LINE__,$car_id, $car_info,$village_id,$park_versions,$park_system,$parking_position],'car/carToDeviceErrLog',1);
            return false;
        }
        // 判断智慧停车系统类别
        if ($house_village_park_config['park_sys_type'] && 'D1'==$house_village_park_config['park_sys_type']) {
            return $this->carToD2Device($car_info,$village_id,$parking_position,$house_village_park_config);
        } elseif ($house_village_park_config['park_sys_type'] && 'A1'!=$house_village_park_config['park_sys_type']) {
            fdump_api(['其他类型智慧停车暂无同步'.__LINE__,$car_id, $car_info,$village_id,$park_versions,$park_system,$house_village_park_config],'car/carToDeviceErrLog',1);
            return true;
        }
        
        if (empty($park_system)) {
            fdump_api(['缺少停车系统信息，检查停车系统是否设置正确'.__LINE__,$car_id, $car_info,$village_id,$park_versions,$park_system,$parking_position],'car/carToDeviceErrLog',1);
            return false;
        }
        $house_village = D('House_village')->where(array('village_id'=>$village_id))->field('village_name')->find();
        $now_time = time();
        $end_time = $car_info['end_time']>100 ? $car_info['end_time']:($now_time-3600);
        $park_position_set = [];
        
        $nowtime = date("mdHis");
        if (2!=$parking_position['position_pattern']) {
            $car_arr = $this->where(array('car_position_id'=>$parking_position['position_id']))->select();
            // 固定车位-共同使用一个月卡会员编号
            if ($parking_position['card_id']) {
                $now_car_id = $parking_position['card_id'];
            } else {
                $now_car_id = '1'.sprintf("%09d",$parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                $park_position_set['card_id'] = $now_car_id;
            }
            $car_group = '3'.sprintf("%06d",$parking_position['position_id']);
            $address = $house_village['village_name'] .'车位：'.$parking_position['position_num'];
            if ($parking_position['end_time'] && intval($parking_position['end_time'])>intval($end_time)) {
                // 绑定真实停车位以停车位时间为底线
                $end_time = $parking_position['end_time'];
            }
        } else {
            $car_arr = D('House_village_parking_car')->where(array('car_id'=>$car_id))->select();
            $car_group = '';
            $address = $house_village['village_name'];
        }
        if(isset($car_info['del_this_car']) && $car_info['del_this_car']==1){
            //只删这一条
            $car_arr=array();
            $car_arr[]=$car_info;
        }
        $trade_no = $nowtime . sprintf("%04d", $now_car_id);
        // 如果是虚拟停车位 对应每一个车生成唯一收费编号
        $p_lot_number = 1;
        $parking_car_type_arr = $this->parking_car_type_arr;
        if ($house_village_park_config['is_park_month_type']!=1) {
            // 不支持月租关闭月租
            unset($parking_car_type_arr[1],$parking_car_type_arr[2],$parking_car_type_arr[3],$parking_car_type_arr[4]);
        }
        if ($house_village_park_config['is_temporary_park_type']!=1) {
            // 不支持储值关闭储值类型
            unset($parking_car_type_arr[13],$parking_car_type_arr[14],$parking_car_type_arr[15],$parking_car_type_arr[16]);
        }
        fdump_api(['车辆同步信息'.__LINE__,$car_id, $car_info,$village_id,$park_versions,$park_system,$parking_position,$house_village_park_config,$car_arr,$parking_car_type_arr],'car/carToDeviceLog',1);
        $db_park_total_record = M('Park_total_record');
        $now_time_tmp=$now_time-3600*24;
        foreach($car_arr as $k=>$v){
            if (in_array($v['parking_car_type'],[13,14,15,16])) {
                fdump_api(['车辆储值类型不符合 不进行同步'.__LINE__,$v],'car/carToDeviceErrLog',1);
                continue;
            }
            $park_set = [];
            $park_data = [];
            $park_data['begin_time'] = $v['start_time']>100 ?  $v['start_time'] : $now_time_tmp;
            $park_data['end_time'] = $end_time;
            if ($park_data['end_time'] > $now_time) {
                $park_data['begin_time'] = $now_time_tmp;
            }
            $park_set['start_time'] = $park_data['begin_time'];
            $park_set['end_time'] = $park_data['end_time'];
            $park_data['park_id'] = $v['village_id'];
            $park_data['car_number'] = $v['province'].$v['car_number'];
            if ($now_car_id) {
                $park_position_set['start_time'] = $park_data['begin_time'];
                $park_position_set['end_time'] = $park_data['end_time'];
            }
            if ($parking_car_type_arr && $v['parking_car_type'] && $parking_car_type_arr[$v['parking_car_type']]) {
                $car_type_id = $parking_car_type_arr[$v['parking_car_type']];
            } else if($v['temporary_car_type']) {
                $car_type_id = $v['temporary_car_type'];
            } else {
                $car_type_id = '月租车A';
            }
            // 3.7月卡会员同步（停车云）
            $park_data['remark'] = '后台同步';
            $service_name = 'month_member_sync';// 添加会员卡操作
            if ($now_car_id) {
                $card_id = $now_car_id;
            } else {
                $card_id = '2'.sprintf("%09d",$v['car_id']);//月卡会员编号（收费系统唯一编号  是
            }
            $park_set['card_id'] = $card_id;
            $park_data['operate_type'] = $v['card_id'] ? '2' : '1';	//1 添加，2 编辑
            /*
            if(isset($v['is_del']) && $v['is_del']===0){
                $park_data['operate_type']=3;  //删除
            }
            */
            if(isset($v['del_this_car']) && $v['del_this_car']==1){
                $park_data['operate_type']=3;  //删除
            }
            $park_data['create_time'] = $now_time;
            $park_data['update_time'] = $now_time;
            $park_data['price'] = 0;//实收金额
            $park_data['name'] = $v['car_user_name'];
            $park_data['car_type_id'] = $car_type_id;
            $park_data['amount_receivable'] = 0;//应收金额
//                                $park_data['channel_id'] = '';//月卡允许通行的通道编号
            $park_data['tel'] = $v['car_user_phone'] ? $v['car_user_phone'] : '';
            $park_data['address'] = $address;
            $park_data['p_lot'] = strval($parking_position['position_num']);

            $park_data['card_id'] = $card_id;
            $infos = $this->bind_parking_position($card_id,$v['car_id']);
            if($infos){
                $park_data['car_group'] = intval($infos['car_group']);
                $park_data['p_lot_number'] = intval($infos['p_lot_number']);
            }
            //2021/2/1 多车多位  修改 end
            $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
            $json_data['data'] = $park_data;
            $json_data['service_name'] = $service_name;
            $post_data['unserialize_desc'] = serialize($json_data);
            $post_data['status'] = 2;
            $post_data['card_id'] = $card_id;
            $post_data['msg_id'] = $json_data['msg_id'];
            $post_data['village_id'] = $v['village_id'];
            $post_data['park_id'] = $park_system['park_id'];
            $post_data['token'] = $park_system['token'];
            $post_data['car_number'] = $park_data['car_number']?$park_data['car_number']:'';
            $post_data['service_name'] = $service_name;
            $post_data['create_time'] = $now_time;
            $post_data['car_id'] = $v['car_id'];
            $post_data['car_id'] = $car_id;
            $post_data['create_time'] = time();
            $res = $db_park_total_record->data($post_data)->add();//月卡会员下发
            if($res){
                fdump_api(['编辑车辆信息' => $v['car_id'],$post_data['car_number'],'post_data'=>$post_data, 'data' => $park_set],'car/carToDevice',1);
                $this->where(array('car_id'=>$v['car_id']))->data($park_set)->save();
                if ($park_position_set) {
                    $db_house_village_parking_position->where(array('position_id'=>$parking_position['position_id']))->data($park_position_set)->save();
                }
            } else {
                fdump_api(['记录同步失败'.__LINE__,$post_data,$park_set,$res,$db_park_total_record->_sql()],'car/carToDeviceErrLog',1);
            }
        }
        return true;
    }

    public function carDelToDevice($car_id, $car_info=[],$village_id=0,$park_versions=0,$park_system=[],$parking_position=[],$house_village_park_config=[]) {
        if (!$car_info) {
            $where_car = [
                'car_id' => $car_id,
            ];
            $field_car = 'car_id,province,car_number,village_id,car_position_id,car_type,province,car_number,end_time,parking_car_type';
            $car_info = $this->where($where_car)->field($field_car)->find();
            if (empty($car_info)) {
                fdump_api(['车辆信息不存在',$car_id, $car_info,$village_id,$park_versions,$park_system,$parking_position],'car/carDelToDeviceErrLog',1);
                return false;
            }
        }
        if (!$village_id) {
            $village_id = $car_info['village_id'];
            if (empty($village_id)) {
                fdump_api(['小区id不能为空',$car_id, $car_info,$village_id,$park_versions,$park_system,$parking_position],'car/carDelToDeviceErrLog',1);
                return false;
            }
        }
        if (empty($house_village_park_config)) {
            $house_village_park_config = M('House_village_park_config')->where(array('village_id'=>$village_id))->find();
        }
        if (!$park_versions && $house_village_park_config) {
            $park_versions = $house_village_park_config['park_versions'];
        }
        if (empty($park_versions) || $park_versions!=2) {
            fdump_api(['版本号不存在或不是新版',$car_id, $car_info,$village_id,$park_versions,$park_system,$parking_position],'car/carDelToDeviceErrLog',1);
            return false;
        }
        if (empty($park_system)) {
            $park_system = M('Park_system')->where(array('park_id'=>$village_id))->find();
            if (empty($park_system)) {
                fdump_api(['缺少停车系统信息，检查停车系统是否设置正确',$car_id, $car_info,$village_id,$park_versions,$park_system,$parking_position],'car/carDelToDeviceErrLog',1);
                return false;
            }
        }
        $db_house_village_parking_position = M('House_village_parking_position');
        if (empty($parking_position)) {
            $parking_position = $db_house_village_parking_position->where(array('position_id'=>$car_info['car_position_id']))->find();
            if (empty($parking_position)) {
                fdump_api(['缺少停车场信息，对应 车辆需要关联停车场',$car_id, $car_info,$village_id,$park_versions,$park_system,$parking_position],'car/carDelToDeviceErrLog',1);
                return false;
            }
        }
        $now_time = time();
        if (in_array($car_info['parking_car_type'],[13,14,15,16])) {
            fdump_api(['车辆类型不符合 不进行同步',$car_info],'car/carDelToDeviceErrLog',1);
            return false;
        }
        $park_data = [];
        $park_data['park_id'] = $village_id;
        $park_data['car_number'] = $car_info['province'].$car_info['car_number'];
        // 3.7月卡会员同步（停车云）
        $park_data['remark'] = '后台同步删除';
        $service_name = 'month_member_sync';// 添加会员卡操作
        $park_data['operate_type'] = 3;	//3删除
        //2021/2/1 多车多位  修改 end
        $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
        $json_data['data'] = $park_data;
        $json_data['service_name'] = $service_name;
        $post_data['unserialize_desc'] = serialize($json_data);
        $post_data['status'] = 2;
        $post_data['msg_id'] = $json_data['msg_id'];
        $post_data['village_id'] = $village_id;
        $post_data['park_id'] = $park_system['park_id'];
        $post_data['token'] = $park_system['token'];
        $post_data['car_number'] = $park_data['car_number']?$park_data['car_number']:'';
        $post_data['service_name'] = $service_name;
        $post_data['create_time'] = $now_time;
        $post_data['car_id'] = $car_id;
        $res = M('Park_total_record')->data($post_data)->add();//月卡会员删除下发
        if (!$res) {
            fdump_api(['记录同步失败',$post_data,$res,M('Park_total_record')->_sql()],'car/carDelToDeviceErrLog',1);
        }
        return true;
    }

    // base64图片转本地图片
    public function base64ToLocalImg1($base_img, $ImgPathType='car_log') {
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = "./upload/{$ImgPathType}/".$rand_num."/";
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        if ($base_img) {
            $type = 'jpg';
            $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
            if(file_put_contents($new_file, base64_decode($base_img))){
                //判断是否需要上传至云存储
                $file_handle = new file_handle();
                $file_handle->upload($new_file);
                $img_path = trim($new_file,'.');
                return $img_path;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }

    // 车型类型对应表 licensePlateType
    // 01大型汽车  02小型汽车 03使馆汽车 04领馆汽车 05境外汽车 06外籍汽车 07普通摩托车 08轻便摩托车 09使馆摩托车 10领馆摩托车
    // 11境外摩托车 12外籍摩托车 13低速车 14拖拉机 15挂车 16教练汽车 17教练摩托车 18试验汽车 19试验摩托车 20临时入境汽车
    // 21临时入境摩托车 22临时行驶车 23警用汽车 24警用摩托 25原农机号牌 26香港入出境车  27澳门入出境车
    // 51大型新能源汽车  52小型新能源汽车
    public  $licensePlateTypeArr = [
        1 => '大型汽车', 2 => '小型汽车', 3 => '使馆汽车', 4 => '领馆汽车', 5 => '境外汽车',6 => '外籍汽车', 7 => '普通摩托车', 8 => '轻便摩托车', 9 => '使馆摩托车', 10 => '领馆摩托车',
        11 => '境外摩托车', 12 => '外籍摩托车', 13 => '低速车', 14 => '拖拉机', 15 => '挂车',16 => '教练汽车', 17 => '教练摩托车', 18 => '试验汽车', 19 => '试验摩托车', 20 => '临时入境汽车',
        21 => '临时入境摩托车', 22 => '临时行驶车', 23 => '警用汽车', 24 => '警用摩托', 25 => '原农机号牌',26 => '香港入出境车', 27 => '澳门入出境车',
        51 => '大型新能源汽车', 52 => '小型新能源汽车',
    ];
    // 支付通道 0微信 1支付宝 2余额 3银联 4建行 5银盛 6云闪付 10工行 17农业银行 45贵州银行 50中行个人金融部 61黔农云
    public $payChannelArr = [
        0=>'微信', 1=>'支付宝', 2=> '余额', 3=>'银联', 4=>'建行', 5=> '银盛', 6=> '云闪付',
        10=>'工行', 17=>'农业银行', 45=> '贵州银行', 50=> '中行个人金融部',61=> '黔农云',
    ];

    public $db_in_park;
    public $db_out_park;
    public $db_house_village;
    public $m_house_village_parking_car;
    public function carDataToAccessRecord($park_id=0,$limit=100) {
        $this->db_in_park = D('In_park');
        $this->db_out_park = D('Out_park');
        $this->db_house_village = D('House_village');
        $where = [];
        $park_name = '';
        if ($park_id) {
            $where['park_id'] = $park_id;
            $park_name = $this->db_house_village->where(['village_id'=> $park_id])->getField('village_name');
        }
        $inParkList = $this->db_in_park->where($where)->limit($limit)->select();
        $outParkList = [];
        if (empty($inParkList)) {
            $outParkList = $this->db_out_park->where($where)->limit($limit)->select();
            if (empty($outParkList)) {
                return false;
            }
        }
        $noInParkList = [];
        $signMovedPark = [
            'moved' => time(),
        ];
        if ($inParkList) {
            $signParkType = 'in_park';
            foreach ($inParkList as $item) {
                $item['optcode'] = isset($item['uid'])&&$item['uid']?$item['uid']:0;
                unset($item['uid']);
                $item['from_id'] = isset($item['id'])&&$item['id']?$item['id']:0;
                $item['accessType'] = 1;
                $item['accessTime'] = $item['in_time'];
                $addInParkLists = $this->addSingleToAccessRecord($park_id, $park_name,[], $item);
                if (!empty($addInParkLists['noInParkLists'])) {
                    $noInParkList[] = $addInParkLists['noInParkLists'];
                }
                if (isset($addInParkLists['record_id'])&&$addInParkLists['record_id']) {
                    $this->m_house_village_parking_car->signMovedPark($item['id'],$signParkType,$signMovedPark);
                }
            }
        }
        if ($outParkList) {
            $signParkType = 'out_park';
            foreach ($outParkList as $out) {
                $out['optcode'] = isset($out['uid'])&&$out['uid']?$out['uid']:0;
                unset($out['uid']);
                $out['from_id'] = isset($out['id'])&&$out['id']?$out['id']:0;
                $out['accessType'] = 2;
                $item['accessTime'] = $out['out_time'];
                $addInParkLists = $this->addSingleToAccessRecord($park_id, $park_name,[], $out);
                if (!empty($addInParkLists['noInParkLists'])) {
                    $noInParkList[] = $addInParkLists['noInParkLists'];
                }
                if (isset($addInParkLists['record_id'])&&$addInParkLists['record_id']) {
                    $this->m_house_village_parking_car->signMovedPark($out['id'],$signParkType,$signMovedPark);
                }
            }
        }
        fdump(['title'=>'重复记录','line'=>__LINE__,'inParkList' => $noInParkList],'api/log/carDataToAccessRecordLog',true);
    }

    public function addSingleToAccessRecord($park_id=0,$park_name='',$access_record=[],$param=[]) {
        if (empty($access_record)) {
            $access_record = $this->carSingleToAccessRecord($param,$park_id,$park_name);
        }
        $db_house_village_car_access_record = D('House_village_car_access_record');
        // 去除重复
        $whereReplace = [
            'business_type' => $access_record['business_type'],
            'business_id' => $access_record['business_id'],
            'car_number' => $access_record['car_number'],
            'accessType' => $access_record['accessType'],
            'accessTime' => $access_record['accessTime'],
//            'accessMode' => $access_record['accessMode'],
            'park_sys_type' => $access_record['park_sys_type'],
        ];
        $replaceId = $db_house_village_car_access_record->where($whereReplace)->getField('record_id');
        if (!$replaceId) {
            // 不重复的进行添加
            $access_record['update_time'] = time();
            $record_id = $db_house_village_car_access_record->add($access_record);
            $noInParkLists = [];
        } else {
            $noInParkLists = ['replaceId' => $replaceId,'item' => $param, 'access_record' => $access_record];
            $record_id = $replaceId;
        }
        return [
            'record_id' => $record_id,
            'noInParkLists' => $noInParkLists,
        ];
    }

    public function carSingleToAccessRecord($item,$park_id=0,$park_name='') {
        $db_house_village = D('House_village');
        if (!$park_name && $item['park_id']) {
            $item['park_name'] = $db_house_village->where(['village_id'=> $item['park_id']])->getField('village_name');
        }
        $db_house_village_parking_car = D('House_village_parking_car');
        $car_number = trim($item['car_number']);
        $access_record = [
            'business_type' => 0,
            'business_id' => $item['park_id']?$item['park_id']:$park_id,
            'car_number' => $car_number,
            'accessType' => $item['accessType']?$item['accessType']:1,
            'accessTime' => $item['accessTime']?$item['accessTime']:time(),
            'channel_number' => !is_null($item['in_channel_id'])?$item['in_channel_id']:'',
            'accessMode' => $item['c_type']?$item['c_type']:3,
            'park_sys_type' => $item['park_sys_type']?$item['park_sys_type']:'A1',
            'park_id' => $item['park_id']?$item['park_id']:$park_id,
            'park_name' => $item['park_name']?$item['park_name']:$park_name,
            'empty_plot' => $item['empty_plot']?$item['empty_plot']:0,
            'token' => $item['token']?$item['token']:'',
            'sign' => $item['sign']?$item['sign']:'',
            'pay_order_id' => $item['pay_order_id']?$item['pay_order_id']:0,
            'is_paid' => isset($item['is_paid'])?intval($item['is_paid']):0,
            'optcode' => isset($item['optcode'])?intval($item['optcode']):0,
            'optname' => isset($item['optname'])?intval($item['optname']):'',
        ];
        $whereCar = [];
        $whereCar['_string'] = "concat(`province`,'',`car_number`)='{$car_number}'";
        $car_info = $db_house_village_parking_car->where($whereCar)->field('car_id,car_user_name,car_user_phone,parking_car_type')->find();
        // ↑获取对应绑定车辆信息
        if (isset($item['content_path']) && $item['content_path'] && stripos($item['content_path'], 'upload/') !== false) {
            // ↑车辆通过的时候抓拍图片 如果是图片路径存储直接复制
            $accessImage = trim($item['content_path']);
        }
        elseif (isset($item['content_path']) && $item['content_path']) {
            // ↑车辆通过的时候抓拍图片 如果是图片base64存储进行转换路径存储
            $item['content_path'] = str_replace(['data:image/png;base64,','data:image/jpg;base64,'], '', $item['content_path']);
            $accessImage = $this->base64ToLocalImg($item['content_path']);
        }
        elseif (isset($item['in_image_small']) && $item['in_image_small'] && stripos($item['in_image_small'], 'upload/') !== false) {
            // ↑车辆通过的时候抓拍小图 如果是图片路径存储直接复制
            $accessImage = trim($item['in_image_small']);
        }
        elseif (isset($item['in_image_small']) && $item['in_image_small'] && stripos($item['in_image_small'], 'upload/') !== false) {
            // ↑车辆通过的时候抓拍小图 如果是图片base64存储进行转换路径存储
            $item['in_image_small'] = str_replace(['data:image/png;base64,','data:image/jpg;base64,'], '', $item['in_image_small']);
            $accessImage = $this->base64ToLocalImg($item['in_image_small']);
        }
        else {
            $accessImage = '';
        }
        if ($accessImage) {
            $access_record['accessImage'] = $accessImage;
        }
        if (isset($item['in_image_big']) && $item['in_image_big'] && stripos($item['in_image_big'], 'upload/') !== false) {
            // ↑车辆通过的时候抓拍大图 如果是图片路径存储直接复制
            $accessBigImage = trim($item['in_image_big']);
        } elseif (isset($item['in_image_big']) && $item['in_image_big']) {
            $item['in_image_big'] = str_replace(['data:image/png;base64,','data:image/jpg;base64,'], '', $item['in_image_big']);
            // ↑车辆通过的时候抓拍大图 如果是图片base64存储进行转换路径存储
            $accessBigImage = $this->base64ToLocalImg($item['in_image_big']);
        } else {
            $accessBigImage = '';
        }
        if ($accessBigImage) {
            $access_record['accessBigImage'] = $accessBigImage;
        }
        if ($car_info && isset($car_info['car_id']) && $car_info['car_id']) {
            // ↑获取到了小区绑定车辆信息对应记录id
            $access_record['car_id'] = $car_info['car_id'];
            $db_house_village_bind_car = D('House_village_bind_car');
            $bindCar = $db_house_village_bind_car->where(['car_id' => $car_info['car_id']])->find();
            if (!isset($item['bind_id'])&&$bindCar['user_id']) {
                $access_record['bind_id'] = $bindCar['user_id'];
            }
            if (!isset($item['uid'])&&$bindCar['uid']) {
                $access_record['uid'] = $bindCar['uid'];
            }
            if (!isset($item['user_name'])&&$car_info['car_user_name']) {
                $access_record['user_name'] = $car_info['car_user_name'];
            }
            if (!isset($item['user_phone'])&&$car_info['car_user_phone']) {
                $access_record['user_phone'] = $car_info['car_user_phone'];
            }
        }
        if (isset($item['order_id'])&&$item['order_id']) {
            $access_record['order_id'] = $item['order_id'];
        }
        if ($access_record['park_sys_type']!='A1'&&isset($item['ordersn'])&&$item['ordersn']&&!$access_record['order_id']) {
            // ↑如果不是A1（杂淘停车系统）
            $access_record['order_id'] = $item['ordersn'];
        } elseif ($access_record['park_sys_type']!='A1'&&isset($item['ordersn'])&&$item['ordersn']&&$access_record['order_id']) {
            $access_record['trade_no'] = $item['ordersn'];
        }
        if (isset($item['totalMoney'])&&$item['totalMoney']) {
            // ↑ 订单总金额 保留2位小数
            $access_record['totalMoney'] = $item['totalMoney'];
        }
        if (isset($item['total'])&&$item['total']) {
            // ↑ 实时订单金额（不包含已预付、已减免的部分）
            $access_record['total'] = $item['total'];
        }
        if (isset($item['prepayTotal'])&&$item['prepayTotal']) {
            // 预付总金额  每次预付累加
            $access_record['prepayTotal'] = $item['prepayTotal'];
        }
        if (isset($item['deductionTotal'])&&$item['deductionTotal']) {
            // 减免总金额  减免金额记录  可以累加也可以用于去重
            $access_record['deductionTotal'] = $item['deductionTotal'];
        }
        if (isset($item['coupon_id'])&&$item['coupon_id']) {
            // 使用优惠券coupon_id  当前仅支持使用一张
            $access_record['coupon_id'] = $item['coupon_id'];
        }
        if (isset($item['pay_type'])&&$item['pay_type']) {
            // ↑ 支付类型  cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
            $access_record['pay_type'] = $item['pay_type'];
        }
        if (isset($item['pay_time'])&&$item['pay_time']) {
            // ↑ 支付时间
            $access_record['pay_time'] = $item['pay_time'];
        } elseif (isset($access_record['pay_type'])&&$access_record['pay_type']) {
            $access_record['pay_time'] = time();// 没有时间记录当前时间
        }
        if (isset($item['auth_code'])&&$item['auth_code']) {
            // ↑ 授权码，支付类型是sweepcode时记录
            $access_record['auth_code'] = $item['auth_code'];
        }
        if (isset($item['park_time'])&&$item['park_time']) {
            // ↑ 出场的时候记录 停留时间 记录单位秒
            $access_record['park_time'] = $item['park_time'];
        }
        if (isset($item['service_name'])&&$item['service_name']) {
            // ↑ 额外指令标识
            $access_record['service_name'] = $item['service_name'];
        }
        if (isset($item['from_id'])&&$item['from_id']) {
            // ↑ 对应来源
            $access_record['from_id'] = $item['from_id'];
        }
        if (isset($item['carType'])&&intval($item['carType'])>=0) {
            // ↑ 车辆类型 0普通车辆  1无牌车（会自动生成临时牌照）
            $access_record['carType'] = intval($item['carType']);
        }
        if (isset($item['licensePlateType'])&&intval($item['licensePlateType'])>0) {
            // ↑ 车牌类型 对应参考House_village_parking_carModel中licensePlateTypeArr
            $access_record['licensePlateType'] = intval($item['licensePlateType']);
        } else {
            $access_record['licensePlateType'] = 2;
        }
        if (isset($item['pay_channel'])&&intval($item['licensePlateType'])>=0) {
            // ↑ 支付通道 对应参考House_village_parking_carModel中licensePlateTypeArr
            $access_record['pay_channel'] = intval($item['pay_channel']);
        } else {
            $access_record['pay_channel'] = 0;
        }
        if (isset($item['third_id'])&&trim($item['third_id'])) {
            // ↑ 第三方返回id
            $access_record['third_id'] = trim($item['third_id']);
        }
        if (isset($item['car_type'])&&trim($item['car_type'])) {
            // ↑ temporaryuUnlicensed  临时无牌车（一般会自动生成一个车牌）temporaryCar 临时车（有牌车） storedCar 储值车   monthCar 月租车 freeCar 免费车
            $access_record['car_type'] = trim($item['car_type']);
        }
        // ↑判断下是否是无牌车入场车辆
        $db_nolicence_in_park = D('Nolicence_in_park');
        $whereNolicenceInParkInfo = [
            'car_number' => $car_number,
        ];
        $nolicenceInParkInfo = $db_nolicence_in_park->where($whereNolicenceInParkInfo)->field('id,uid,car_number,park_sys_type')->find();
        if (!empty($nolicenceInParkInfo)&&!$access_record['uid']) {
            $access_record['uid'] = isset($nolicenceInParkInfo['uid'])&&$nolicenceInParkInfo['uid']?$nolicenceInParkInfo['uid']:0;
            $access_record['car_type'] = 'temporaryuUnlicensed';
        }
        if ((!isset($access_record['car_type']) || !$access_record['car_type'])&&$car_info['parking_car_type']) {
            if (in_array($car_info['parking_car_type'],$this->monthCarArr)) {
                // 月租车
                $access_record['car_type'] = 'monthCar';
            } elseif (in_array($car_info['parking_car_type'],$this->temporaryCarArr)) {
                // 临时车（有牌车）
                $access_record['car_type'] = 'temporaryCar';
            } elseif (in_array($car_info['parking_car_type'],$this->storedCarArr)) {
                // 储值车
                $access_record['car_type'] = 'storedCar';
            } elseif (in_array($car_info['parking_car_type'],$this->freeCarArr)) {
                // 免费车
                $access_record['car_type'] = 'freeCar';
            }
        }
        return $access_record;
    }

    public function signMovedPark($id,$signParkType='in_park',$data=[]) {
        if (empty($data)) {
            $data = [
                'moved' => time(),
            ];
        }
        if ('in_park'==$signParkType) {
            if (!$this->db_in_park) {
                $this->db_in_park = D('In_park');
            }
            $this->db_in_park->where(['id'=> $id])->save($data);
        } elseif ('out_park'==$signParkType) {
            if (!$this->db_out_park) {
                $this->db_out_park = D('Out_park');
            }
            $this->db_out_park->where(['id' => $id])->save($data);
        } elseif ('out_park'==$signParkType) {
            if (!$this->db_out_park) {
                $this->db_out_park = D('Out_park');
            }
            $this->db_out_park->where(['id' => $id])->save($data);
        }
    }
    /**
     * 车辆查询对应车位信息
     * @author: liukezhu
     * @date : 2022/1/14
     * @param $where
     * @param $field
     * @return mixed
     */
    public function get_parking_position($where,$field){
        $info = $this->alias('a')
            ->JOIN('left join '. C('DB_PREFIX') . 'house_village_parking_position as b on a.car_position_id=b.position_id')
            ->where($where)
            ->field($field)
            ->find();
        return $info;
    }

    //同步记录到log表
    public function bindOrderLog($param){
        //$param['car_id']  pigcms_house_village_parking_car 表car_id
        fdump_api(['line:'.__LINE__,$param],'/parking_car/bindOrderLog',1);
        if(empty($param['end_time']) || empty($param['room_id'])){
            return false;
        }
        if(isset($param['is_new_charge']) && intval($param['is_new_charge']) == 1){
            $time=time();
            $data=[
                'order_type'        => 'park',
                'order_name'        => '停车费',
                'room_id'           => $param['room_id'],
                'position_id'       => $param['position_id'],
                'property_id'       => $param['property_id'],
                'village_id'        => $param['village_id'],
                'desc'              => '添加车辆，同步写入该表数据',
                'service_start_time'=> $time,
                'service_end_time'  => $param['end_time'],
                'add_time'          => $time
            ];
            D('House_new_order_log')->add($data);
            //todo D5智慧停车 触发下发到设备
            invoke_v20_service('\app\community\model\service\Park\D5Service/D5AddCar',[
                'village_id'=>$param['village_id'],
                'car_id'=>$param['car_id'],
                'start_time'=>$data['service_start_time'],
                'end_time'=>$data['service_end_time']
            ]);
        }
        return true;
    }
    // 同步D2智慧停车
    public function carToD2Device($car_info,$village_id,$parking_position,$house_village_park_config) {
        $end_time = $car_info['end_time']?$car_info['end_time']:0;
        // 进行同步
        $house_village = D('House_village')->where(array('village_id'=>$village_id))->field('village_name')->find();
        if (2!=$parking_position['position_pattern']) {
            $parking_car = D('House_village_parking_car')->where(array('car_position_id'=>$parking_position['position_id']))->select();
            // 固定车位-共同使用一个月卡会员编号
            if ($parking_position['card_id']) {
                $now_car_id = $parking_position['card_id'];
            } else {
                $now_car_id = '1'.sprintf("%09d",$parking_position['position_id']);//月卡会员编号（收费系统唯一编号，必传）
                $park_position_set['card_id'] = $now_car_id;
            }
            $address = $house_village['village_name'] .'车位：'.$parking_position['position_num'];
            if ($parking_position['end_time'] && intval($parking_position['end_time'])>intval($end_time)) {
                // 绑定真实停车位以停车位时间为底线
                $end_time = $parking_position['end_time'];
            }
        } else {
            // 虚拟停车位 每个车都是独立的
            $parking_car = D('House_village_parking_car')->where(array('car_id'=>$_POST['car_id']))->select();
            $address = $house_village['village_name'];
        }
        $parking_car_type_arr = $this->parking_car_type_arr;
        if ($house_village_park_config['is_park_month_type']!=1) {
            // 不支持月租关闭月租
            unset($parking_car_type_arr[1],$parking_car_type_arr[2],$parking_car_type_arr[3],$parking_car_type_arr[4]);
        }
        if ($house_village_park_config['is_temporary_park_type']!=1) {
            // 不支持储值关闭储值类型
            unset($parking_car_type_arr[13],$parking_car_type_arr[14],$parking_car_type_arr[15],$parking_car_type_arr[16]);
        }
        $now_time = time();
        foreach($parking_car as $k1=>$v1){
            if (in_array($v1['parking_car_type'],[13,14,15,16])) {
                fdump_api(['车辆储值类型不符合 不进行同步'.__LINE__,$v1],'car/carToD2DeviceErrLog',1);
                continue;
            }
            $park_set = [];
            $park_data = [];
            $park_data['begin_time'] = $v1['start_time'] ?  $v1['start_time'] : $now_time;
            $park_data['end_time'] = $end_time;
            if (intval($park_data['end_time']) > intval($now_time)) {
                $park_data['begin_time'] = $now_time;
            }
            $park_set['start_time'] = $park_data['begin_time'];
            $park_set['end_time'] = $park_data['end_time'];
            $village_id = $v1['village_id'];
            $park_data['car_number'] = $v1['province'].$v1['car_number'];
            if ($now_car_id) {
                $park_position_set['start_time'] = $park_data['begin_time'];
                $park_position_set['end_time'] = $park_data['end_time'];
            }
            if ($parking_car_type_arr && $v1['parking_car_type'] && $parking_car_type_arr[$v1['parking_car_type']]) {
                $car_type_id = $parking_car_type_arr[$v1['parking_car_type']];
            } else if($v1['temporary_car_type']) {
                $car_type_id = $v1['temporary_car_type'];
            } else {
                $car_type_id = '月租车A';
            }
            $park_data['remark'] = '后台同步';
            if ($now_car_id) {
                $card_id = $now_car_id;
            } else {
                $card_id = '2'.sprintf("%09d",$v1['car_id']);//月卡会员编号（收费系统唯一编号）
            }
            $park_set['card_id'] = $card_id;
            $getCar = D('ParkService')->getMonthCar($village_id, $park_data['car_number']);
            if ($getCar['error']) {
                if ($car_info['car_id']==$v1['car_id']) {
                    // 本次添加编辑车辆直接报错
                    throw new \think\Exception($getCar['msg']);
                } else {
                    // 不是本次编辑车辆直接跳过
                    continue;
                }
            } elseif ($getCar['car_info'] && $getCar['car_info']['car_number']==$park_data['car_number']) {
                if ($getCar['car_info']['card_id']) {
                    $card_id = $getCar['car_info']['card_id'];
                }
                $park_data['is_edit'] = 1;
            }
            $park_data['name'] = $v1['car_user_name'];
            $park_data['car_type_id'] = $car_type_id;
            $park_data['amount_receivable'] = 0;//应收金额
//                                $park_data['channel_id'] = '';//月卡允许通行的通道编号
            $park_data['tel'] = $v1['car_user_phone'] ? $v1['car_user_phone'] : '';
            $park_data['address'] = $address;
            $park_data['p_lot'] = strval($parking_position['position_num']);
            $park_data['card_id'] = $card_id;
            if ($park_data['end_time']) {
                $res = D('ParkService')->handleMonthCard($village_id, $v1['car_id'], $park_data, $v1);
                if($res && !$res['error']){
                    fdump_api(['编辑车辆信息' => $v1['car_id'], 'data' => $park_set],'park_temp/editHouseVillageParkingCar_log',1);
                    D('House_village_parking_car')->where(array('car_id'=>$v1['car_id']))->data($park_set)->save();
                    if ($park_position_set) {
                        D('House_village_parking_position')->where(array('position_id'=>$parking_position['position_id']))->data($park_position_set)->save();
                    }
                }
            }
        }
        return true;
    }

    
    // base64图片转本地图片
    public function base64ToLocalImg($base_img, $ImgPathType='car_log') {
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = "./upload/{$ImgPathType}/".$rand_num."/";
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        if ($base_img) {
            $type = 'jpg';
            $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
            if(file_put_contents($new_file, base64_decode($base_img))){
                //判断是否需要上传至云存储
                $file_handle = new file_handle();
                $file_handle->upload($new_file);
                $img_path = trim($new_file,'.');
                return $img_path;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     * Notes: 智慧停车场批量同步
     * @author: wanzy
     * @date_time: 2021/10/27 13:24
     */
    public function sysParkCarDevice($village_id)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $village_id))->find();
        $park_versions = $house_village_park_config['park_versions'];
        $hasCars = 0;
        if ($park_versions && 2 == $park_versions) {
            $park_system = M('Park_system')->where(array('park_id' => $village_id))->find();
            $where_car = [
                'village_id' => $village_id,
            ];
            $cars = M('House_village_parking_car')->where($where_car)->field('car_id,province,car_number,village_id,car_position_id,end_time')->select();
            if (!empty($cars)) {
                $hasCars = 1;
                foreach ($cars as $item) {
                    try {
                        $this->carToDevice($item['car_id'], $item, $village_id, $park_versions, $park_system, [], $house_village_park_config);
                    } catch (\Exception $e) {
                        return ['code'=>0,'msg'=>$e->getMessage()];
                    }
                }
                return ['code' => 1, 'msg' => '已经同步!'];
            }
        } else {
            $whereGarageConfig = [
                'village_id' => $village_id,
                'park_versions' => 2,
            ];
            $park_garage = M('House_village_park_garage_config')->where($whereGarageConfig)->field('garage_id')->select();
            $configGarageIds=array();
            foreach ($park_garage as $gvv){
                $configGarageIds[]=$gvv['garage_id'];
            }
            $park_system = M('Park_system')->where(array('park_id' => $village_id))->find();
            $where_car = [
                'village_id' => $village_id,
                'garage_id' => ['in', $configGarageIds],
            ];
            $cars = M('House_village_parking_car')->where($where_car)->field('car_id,province,car_number,village_id,car_position_id,end_time')->select();
            if (!empty($cars)) {
                $hasCars = 1;
                foreach ($cars as $item) {
                    try {
                        $this->carToDevice($item['car_id'], $item, $village_id, 2, $park_system, [], []);
                    } catch (\Exception $e) {
                        return ['code'=>0,'msg'=>$e->getMessage()];
                    }
                }
                return ['code' => 1, 'msg' => '已经同步!'];
            }
        }

        if ($hasCars != 1 && !empty($cars)) {
            return ['code' => 0, 'msg' => '没有可同步车辆!'];
        } elseif ($hasCars != 1) {
            return ['code' => 0, 'msg' => '智慧停车场已经关闭!'];
        }
    }


    //针对A11查询车辆收费标准
    public function getParkingCarChargingRule($car_info){
        $house_village_park_charge_cartype=M('House_village_park_charge_cartype');
        $house_new_charge_rule=M('House_new_charge_rule');
        if(!isset($car_info['parking_car_type']) || empty($car_info)){
            return ['error'=>false,'msg'=>'该车辆数据不存在，请刷新页面','data'=>[]];
        }
        if(!isset($this->parking_a11_car_type_arr[$car_info['parking_car_type']])){
            return ['error'=>false,'msg'=>'该车辆[停车卡类]绑定无效，请联系管理员重新绑定','data'=>[]];
        }
        if($car_info['parking_car_type'] == self::Free_A){
            return ['error'=>false,'msg'=>'该车辆属于免费车，不支持储值或续费','data'=>[]];
        }
        $where=[
            'status'=>1,
            'park_sys_type'=>'A11',
            'village_id'=>$car_info['village_id']
        ];
        //储值车或临时车转月租车 默认选月租车A卡类收费标准
        if($car_info['parking_car_type'] == self::Stored_A || in_array($car_info['parking_car_type'],$this->A11_Temp)){
            $where['car_type']=self::Month_A;
        }else{
            //月租车续费
            $where['car_type']=$car_info['parking_car_type'];
        }
        if($car_info['parking_car_type'] == self::Stored_A && floatval($car_info['stored_balance']) > 0){
            return ['error'=>false,'msg'=>'该车所属[储值车]且余额为'.$car_info['stored_balance'].'元，不支持[月租车缴费]','data'=>[]];
        }
        $ids=$house_village_park_charge_cartype->where($where)->getField('rule_id',true);
        if(!$ids){
            return ['error'=>false,'msg'=>'暂无绑定收费标准,请联系管理员','data'=>[]];
        }
        $where =[
            'id' =>['in', $ids],
            'status'=>1
        ];
        $rule_id=$house_new_charge_rule->where($where)->getField('id',true);
        if(!$rule_id){
            return ['error'=>false,'msg'=>'暂无收费标准,请联系管理员','data'=>[]];
        }
        return ['error'=>true,'msg'=>'查询成功','data'=>$rule_id];
    }

    //针对A11免费车不支持储值
    public function checkParkingCarRecharge($car_info){
        if(!isset($car_info['parking_car_type']) || empty($car_info)){
            return ['error'=>false,'msg'=>'该车辆数据不存在，请刷新页面','data'=>[]];
        }
        if(!isset($this->parking_a11_car_type_arr[$car_info['parking_car_type']])){
            return ['error'=>false,'msg'=>'该车辆[停车卡类]绑定无效，请联系管理员重新绑定','data'=>[]];
        }
        if($car_info['parking_car_type'] == self::Free_A){
            return ['error'=>false,'msg'=>'该车辆属于免费车，不支持储值','data'=>[]];
        }
        return ['error'=>true,'msg'=>'查询成功','data'=>[]];

    }


    private $village_id, $park_id, $park_sys_type, $car_number, $position_num, $car_user_name, $car_user_phone;
    public function parkPassList($param, $page = 1, $pageSize = 20, $order = '') {
        fdump_api([
            'time'            => microtime(true),
            '$data'           => $param,
        ], '$in_park_data');
        $isInPark  = isset($param['isInPark'])  && $param['isInPark']  ? $param['isInPark']  : 0;// 目前整体方法是以入场为主 所以搜索类型是入场的时候 直接使用这个方法
        $isOutPark = isset($param['isOutPark']) && $param['isOutPark'] ? $param['isOutPark'] : 0;// 这个传参后就会以出场为主进行查询 分页也是以出场为主，不包含二次入场更改的出场记录
        $this->village_id     = isset($param['village_id'])     && $param['village_id']     ? $param['village_id']     : 0;
        $this->park_id        = isset($param['park_id'])        && $param['park_id']        ? $param['park_id']        : $this->village_id;
        $this->park_sys_type  = isset($param['park_sys_type'])  && $param['park_sys_type']  ? $param['park_sys_type']  : 'A1';
        $this->car_number     = isset($param['car_number'])     && $param['car_number']     ? $param['car_number']     : '';
        $this->position_num   = isset($param['position_num'])   && $param['position_num']   ? $param['position_num']   : '';
        $this->car_user_name  = isset($param['car_user_name'])  && $param['car_user_name']  ? $param['car_user_name']  : '';
        $this->car_user_phone = isset($param['car_user_phone']) && $param['car_user_phone'] ? $param['car_user_phone'] : '';
        if ($isOutPark) {
            return $this->outParkList($param, $page, $pageSize, $order);
        }
        $in_time_start        = isset($param['in_time_start']) && $param['in_time_start'] ? $param['in_time_start'] : '';
        $in_time_end          = isset($param['in_time_end'])   && $param['in_time_end']   ? $param['in_time_end']   : '';
        $isReturnList         = isset($param['isReturnList'])  && $param['isReturnList']  ? $param['isReturnList']  : '';
        $getCount             = isset($param['getCount'])      && $param['getCount']      ? $param['getCount']      : '';
        // 先查询下入场
        $whereInPark = [
            'park_id'       => $this->park_id,
            'park_sys_type' => $this->park_sys_type,
        ];
        $whereCar = [];
        $whereCarPosition = [];
        if ($this->position_num) {
            $whereCarPosition['position_num'] = $this->position_num;
        }
        if ($this->car_number) {
            $whereInPark['car_number'] = $this->car_number;
        }
        if ($this->car_user_name) {
            $whereCar['car_user_name'] = ['like', '%' . trim($this->car_user_name) . '%'];
        }
        if ($this->car_user_phone) {
            $whereCar['car_user_phone'] = ['like', '%' . trim($this->car_user_phone) . '%'];
        }
        $db_house_village_parking_car      = M('House_village_parking_car');
        $db_house_village_parking_position = M('House_village_parking_position');
        $db_in_park                        = M('in_park');
        $db_out_park                       = M('out_park');
        if (!empty($whereCar)) {
            $car_number_arr = $db_house_village_parking_car->where($whereCar)->getField("concat(`province`,'',`car_number`)", true);
            $whereInPark['car_number'] = ['in', $car_number_arr];
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);
        if (!empty($whereCarPosition)) {
            $car_position_arr = $db_house_village_parking_position->where($whereCarPosition)->getField("position_id", true);
            $whereCar['car_position_id'] = ['in', $car_position_arr];
            $car_number_arr = $db_house_village_parking_car->where($whereCar)->getField("concat(`province`,'',`car_number`)", true);
            $whereInPark['car_number'] = ['in', $car_number_arr];
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);
        if (!isset($whereInPark['car_number'])) {
            $whereInPark['car_number'] = ['neq', ''];
        }
        if ($in_time_start && $in_time_end) {
            $whereInPark['in_time'] = [];
            $whereInPark['in_time'][] = ['gt', $in_time_start];
            $whereInPark['in_time'][] = ['lt', $in_time_end];
        } elseif ($in_time_start > 0) {
            $whereInPark['in_time'] = ['gt', $in_time_start];
        } elseif ($in_time_end > 0) {
            $whereInPark['in_time'] = ['lt', $in_time_end];
        }

        if ($getCount) {
            // 只是获取数量 直接反馈
            fdump_api($whereInPark, '$getCount');
            return $db_in_park->where($whereInPark)->count();
        }
        if ($page && $page > 0) {
            $count = $db_in_park->where($whereInPark)->count();
        }
        fdump_api([
            'time'            => microtime(true),
            '$whereInPark'            => $whereInPark,
        ], '$in_park_data',1);
        if ($page && $page > 0) {
            $_GET['page'] = $page > 1 ? $page : $_GET['page'];
            import('@.ORG.merchant_page');
            $p = new Page($count, $pageSize, 'page');
        }
        if ($page && $page > 0 && ($count <= 0 || !$count)) {
            // 没有信息直接返回
            $data = $this->toDayCarStatistics();
            $data['list']       = [];
            $data['pagebar']    = $p->show();
            return $data;
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);
        if (!$order) {
            $order = 'in_time DESC, id DESC';
        }
        if ($page && $page > 0) {
            $in_park_data = $db_in_park->where($whereInPark)->limit($p->firstRow . ',' . $p->listRows)->order($order)->select();
        } else {
            $in_park_data = $db_in_park->where($whereInPark)->order($order)->select();
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);
        $car_number_arr = [];
        $order_id_arr   = [];
        $out_park_time_arr   = [];
        foreach ($in_park_data as $val) {
            $car_number_arr[] = '"'.$val['car_number'].'"';
            if ($val['order_id']) {
                $order_id_arr[]   = $val['order_id'];
            }
            if ($val['in_time']) {
                $out_park_time_arr[]   = '"'.$val['car_number'].'_'.$val['in_time'].'"';
            }
        }
        $car_number_arr = array_unique($car_number_arr);
        $car_number_arr_string = implode(',', $car_number_arr);
        $whereInfoCar = [
            '_string' => "concat(`province`,'',`car_number`) in (".$car_number_arr_string.")"
        ];
        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);
        $car_info_arr = $db_house_village_parking_car->where($whereInfoCar)->getField("concat(`province`,'',`car_number`) as car_number, car_position_id, car_user_name, car_user_phone, car_stop_num", true);

        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);
        $carPositionInfoArr = [];
        foreach ($car_info_arr as $car_num_info) {
            $carPositionInfoArr[] = $car_num_info['car_position_id'];
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);
        $carPositionInfoArr = array_unique($carPositionInfoArr);
        $whereCarPositionInfo = [
            'position_id' => ['in', $carPositionInfoArr]
        ];
        $car_position_info_arr = $db_house_village_parking_position->where($whereCarPositionInfo)->getField("position_id, position_num", true);

        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);
        fdump_api([
            '$out_park_time_arr'            => $out_park_time_arr,
        ], '$in_park_data',1);
        if (!empty($out_park_time_arr)) {
            $out_park_time_arr = array_unique($out_park_time_arr);
            $out_park_time_arr_string = implode(',', $out_park_time_arr);
            $whereOutTime = [
                '_string' => "concat(`car_number`,'_',`in_time`) in (".$out_park_time_arr_string.")"
            ];
            $out_park_time_info_arr = $db_out_park->where($whereOutTime)->getField("concat(`car_number`,'_',`in_time`) as car_number_time, order_id, out_time, total, order_id, in_time", true);

            fdump_api([
                '$out_park_time_info_arr'            => $out_park_time_info_arr,
            ], '$in_park_data',1);
        }
        if (!isset($out_park_time_info_arr) || !$out_park_time_info_arr) {
            $out_park_time_info_arr = [];
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);

        if (!empty($order_id_arr)) {
            $order_id_arr = array_unique($order_id_arr);
            $whereOut = [
                'park_id'  => $this->park_id,
                'order_id' => ['in', $order_id_arr],
            ];
            $out_park_info_arr = $db_out_park->where($whereOut)->getField('order_id, out_time, total, order_id, in_time',true);
        }
        if (!isset($out_park_info_arr) || !$out_park_info_arr) {
            $out_park_info_arr = [];
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$in_park_data',1);
        
        fdump_api([
            '$whereInPark'           => $whereInPark,
            '$car_number_arr'        => $car_number_arr,
            '$whereInfoCar'          => $whereInfoCar,
            '$car_info_arr'          => $car_info_arr,
            '$whereCarPositionInfo'  => $whereCarPositionInfo,
            '$car_position_info_arr' => $car_position_info_arr,
            '$out_park_info_arr'     => $out_park_info_arr,
            ], '$parkPassList',1);
        // 时间判断有效界限  
        $topLimitTime   = 2177423999; // 上限  暂定2038-12-31 23:59:59
        $floorLimitTime = 1514736000;// 下限 暂定2018-01-01 00:00:00
        $noOutInParkIdArr = [];
        foreach ($in_park_data as &$inPark) {
            $order_id     = isset($inPark['order_id'])   && $inPark['order_id']   ? $inPark['order_id']   : '';
            $car_number   = isset($inPark['car_number']) && $inPark['car_number'] ? $inPark['car_number'] : '';
            $outPark      = $order_id    && isset($out_park_info_arr[$order_id])        && $out_park_info_arr[$order_id]        ? $out_park_info_arr[$order_id]        : [];
            if (empty($outPark)) {
                $car_number_time = $car_number . '_' . $inPark['in_time'];
                $outPark  = isset($out_park_time_info_arr[$car_number_time]) && $out_park_time_info_arr[$car_number_time] ? $out_park_time_info_arr[$car_number_time] : [];
            }
            $carInfo      = $car_number  && isset($car_info_arr[$car_number])           && $car_info_arr[$car_number]           ? $car_info_arr[$car_number]    : [];
            $position_id  = $carInfo     && isset($carInfo['car_position_id'])          && $carInfo['car_position_id']          ? $carInfo['car_position_id']          : '';
            $position_num = $position_id && isset($car_position_info_arr[$position_id]) && $car_position_info_arr[$position_id] ? $car_position_info_arr[$position_id] : '';
            $inPark['car_position_id']  = $position_id;
            $inPark['car_user_name']    = isset($carInfo['car_user_name'])  ? $carInfo['car_user_name']  : '';
            $inPark['car_user_phone']   = isset($carInfo['car_user_phone']) ? $carInfo['car_user_phone'] : '';
            $inPark['car_stop_num']     = isset($carInfo['car_stop_num'])   ? $carInfo['car_stop_num']   : '';
            $inPark['position_num']     = $position_num;
            $standing_time_txt = '--';
            if (!empty($outPark) && $inPark['id'] && $inPark['is_out'] != 1) {
                $noOutInParkIdArr[] = $inPark['id'];
            }
            $hasOut   = false;
            if (!empty($outPark)) {
                // 存在出场记录
                $out_time = isset($outPark['out_time']) && $outPark['out_time'] ? $outPark['out_time'] : $inPark['out_time'];
                $in_time  = isset($inPark['in_time']) && $inPark['in_time'] ? $inPark['in_time'] : $outPark['in_time'];
                $hasOut   = true;
            } elseif ($inPark['is_out'] == 1) {
                $out_time = isset($inPark['out_time']) && $inPark['out_time'] ? $inPark['out_time'] :0;
                $in_time  = isset($inPark['in_time']) && $inPark['in_time'] ? $inPark['in_time'] : 0;
                $hasOut   = true;
            } else {
                $in_time  = isset($inPark['in_time']) && $inPark['in_time'] ? $inPark['in_time'] : 0;
                $out_time = isset($inPark['out_time']) && $inPark['out_time'] ? $inPark['out_time'] :0;
            }
            if (!$out_time || intval($out_time) < $floorLimitTime || intval($out_time) > $topLimitTime) {
                $out_time = 0;
            }
            if (!$in_time || intval($in_time) < $floorLimitTime || intval($in_time) > $topLimitTime) {
                $in_time = 0;
            }
            
            if ($out_time && $in_time) {
                $hasOut = true;
                $standing_time_val = intval($out_time - $in_time);
                $standing_time_txt = $this->secondToTimeTxt($standing_time_val);
                if (!$standing_time_txt) {
                    $standing_time_txt = '无';
                }
            } elseif($hasOut) {
                $standing_time_txt = '无';
            }
            $inPark['standing_time'] = $standing_time_txt;
            if ($out_time) {
                $inPark['out_time'] = date('Y-m-d H:i:s', $out_time);
            } else {
                $inPark['out_time'] = '--';
            }
            if ($in_time) {
                $inPark['in_time'] = date('Y-m-d H:i:s', $in_time);
            } else {
                $inPark['in_time'] = '--';
            }
            if ($hasOut && $in_time && $out_time) {
                $inPark['is_out_text'] = '进场-出场';
            } elseif ($hasOut && $in_time && !$out_time) {
                $inPark['is_out_text'] = '进场-出场';
                $inPark['out_time']    = "二次进场记录更替";
            } elseif ($hasOut && !$in_time && $out_time) {
                $inPark['is_out_text'] = '出场';
                $inPark['in_time']    = "无入场记录";
            } elseif ($hasOut) {
                $inPark['is_out_text'] = '出场';
            } else{
                $inPark['is_out_text'] = '进场';
            }
        }
        if ($isReturnList) {
            return $in_park_data;
        }
        $data = $this->toDayCarStatistics();
        $data['list']       = $in_park_data;
        if ($page && $page > 0) {
            $data['pagebar'] = $p->show();
        }
        if (!empty($noOutInParkIdArr)) {
            $whereInPark = [
                'id' => ['in', $noOutInParkIdArr]
            ];
            $db_in_park->where($whereInPark)->save(['is_out' => 1]); // 实际有对应出场记录的但是入场记录未变更出场状态的集中变更
        }
        fdump_api([
            'time'                    => microtime(true),
            '$noOutInParkIdArr'           => $noOutInParkIdArr,
            '$data'           => $data,
        ], '$in_park_data',1);
        return $data;
    }
    
    public function outParkList($param, $page = 1, $pageSize = 20, $order = '') {
        $out_time_start = isset($param['out_time_start']) && $param['out_time_start'] ? $param['out_time_start'] : '';
        $out_time_end   = isset($param['out_time_end'])   && $param['out_time_end']   ? $param['out_time_end']   : '';
        $isReturnList   = isset($param['isReturnList'])   && $param['isReturnList']   ? $param['isReturnList']   : '';
        $getCount       = isset($param['getCount'])       && $param['getCount']       ? $param['getCount']       : '';
        // 先查询下出场
        $whereOutPark = [
            'park_id'       => $this->park_id,
//            'park_sys_type' => $this->park_sys_type,
        ];
        $whereCar = [];
        $whereCarPosition = [];
        if ($this->position_num) {
            $whereCarPosition['position_num'] = $this->position_num;
        }
        if ($this->car_number) {
            $whereOutPark['car_number'] = $this->car_number;
        }
        if ($this->car_user_name) {
            $whereCar['car_user_name'] = ['like', '%' . trim($this->car_user_name) . '%'];
        }
        if ($this->car_user_phone) {
            $whereCar['car_user_phone'] = ['like', '%' . trim($this->car_user_phone) . '%'];
        }
        $db_house_village_parking_car      = M('House_village_parking_car');
        $db_house_village_parking_position = M('House_village_parking_position');
        $db_in_park                        = M('in_park');
        $db_out_park                       = M('out_park');
        if (!empty($whereCar)) {
            $car_number_arr = $db_house_village_parking_car->where($whereCar)->getField("concat(`province`,'',`car_number`)", true);
            $whereOutPark['car_number'] = ['in', $car_number_arr];
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$outParkList',1);
        if (!empty($whereCarPosition)) {
            $car_position_arr = $db_house_village_parking_position->where($whereCarPosition)->getField("position_id", true);
            $whereCar['car_position_id'] = ['in', $car_position_arr];
            $car_number_arr = $db_house_village_parking_car->where($whereCar)->getField("concat(`province`,'',`car_number`)", true);
            $whereOutPark['car_number'] = ['in', $car_number_arr];
        }
        fdump_api([
            'time'            => microtime(true),
            '$param'            => $param,
            '$out_time_start'            => $out_time_start,
            '$out_time_end'            => $out_time_end,
        ], '$outParkList',1);
        if (!isset($whereOutPark['car_number'])) {
            $whereOutPark['car_number'] = ['neq', ''];
        }
        if ($out_time_start && $whereOutPark) {
            $whereOutPark['out_time'] = [];
            $whereOutPark['out_time'][] = ['gt', $out_time_start];
            $whereOutPark['out_time'][] = ['lt', $out_time_end];
        } elseif ($out_time_start > 0) {
            $whereOutPark['out_time'] = ['gt', $out_time_start];
        } elseif ($out_time_end > 0) {
            $whereOutPark['out_time'] = ['lt', $out_time_end];
        }
        if ($getCount) {
            // 只是获取数量 直接反馈
            return $db_out_park->where($whereOutPark)->count();
        }
        if ($page && $page > 0) {
            $count = $db_out_park->where($whereOutPark)->count();
        }
        fdump_api([
            'time'            => microtime(true),
            '$whereOutPark'            => $whereOutPark,
            '$count'            => $count,
        ], '$outParkList',1);
        if ($page && $page > 0) {
            $_GET['page'] = $page > 1 ? $page : $_GET['page'];
            import('@.ORG.merchant_page');
            $p = new Page($count, $pageSize, 'page');
        }
        if ($page && $page > 0 && ($count <= 0 || !$count)) {
            // 没有信息直接返回
            $data = $this->toDayCarStatistics();
            $data['list']       = [];
            $data['pagebar']    = $p->show();
            return $data;
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$outParkList',1);
        if (!$order) {
            $order = 'out_time DESC, id DESC';
        }
        if ($page && $page > 0) {
            $outParkList = $db_out_park->where($whereOutPark)->limit($p->firstRow . ',' . $p->listRows)->order($order)->select();
        } else {
            $outParkList = $db_out_park->where($whereOutPark)->order($order)->select();
        }
        fdump_api([
            'time'            => microtime(true),
            '$outParkList'    => $outParkList,
        ], '$outParkList',1);
        $car_number_arr = [];
        $order_id_arr   = [];
        $in_park_time_arr   = [];
        foreach ($outParkList as $val) {
            $car_number_arr[] = '"'.$val['car_number'].'"';
            if ($val['order_id']) {
                $order_id_arr[]   = $val['order_id'];
            }
            if ($val['in_time']) {
                $in_park_time_arr[]   = '"'.$val['car_number'].'_'.$val['in_time'].'"';
            }
        }
        $car_number_arr = array_unique($car_number_arr);
        $car_number_arr_string = implode(',', $car_number_arr);
        $whereInfoCar = [
            '_string' => "concat(`province`,'',`car_number`) in (".$car_number_arr_string.")"
        ];
        fdump_api([
            'time'            => microtime(true),
        ], '$outParkList',1);
        $car_info_arr = $db_house_village_parking_car->where($whereInfoCar)->getField("concat(`province`,'',`car_number`) as car_number, car_position_id, car_user_name, car_user_phone, car_stop_num", true);

        fdump_api([
            'time'            => microtime(true),
        ], '$outParkList',1);
        $carPositionInfoArr = [];
        foreach ($car_info_arr as $car_num_info) {
            $carPositionInfoArr[] = $car_num_info['car_position_id'];
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$outParkList',1);
        $carPositionInfoArr = array_unique($carPositionInfoArr);
        $whereCarPositionInfo = [
            'position_id' => ['in', $carPositionInfoArr]
        ];
        $car_position_info_arr = $db_house_village_parking_position->where($whereCarPositionInfo)->getField("position_id, position_num", true);

        fdump_api([
            'time'            => microtime(true),
        ], '$outParkList',1);
        fdump_api([
            '$in_park_time_arr'            => $in_park_time_arr,
        ], '$outParkList',1);
        if (!empty($in_park_time_arr)) {
            $in_park_time_arr = array_unique($in_park_time_arr);
            $in_park_time_arr_string = implode(',', $in_park_time_arr);
            $whereInTime = [
                '_string' => "concat(`car_number`,'_',`in_time`) in (".$in_park_time_arr_string.")"
            ];
            $in_park_time_info_arr = $db_in_park->where($whereInTime)->getField("concat(`car_number`,'_',`in_time`) as car_number_time, order_id, out_time, is_out, car_number, in_time, id", true);
        }
        if (!isset($in_park_time_info_arr) || !$in_park_time_info_arr) {
            $in_park_time_info_arr = [];
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$outParkList',1);

        if (!empty($order_id_arr)) {
            $order_id_arr = array_unique($order_id_arr);
            $whereIn = [
                'park_id'  => $this->park_id,
                'order_id' => ['in', $order_id_arr],
            ];
            $in_park_info_arr = $db_in_park->where($whereIn)->getField('order_id, out_time, is_out, order_id, car_number, in_time, id',true);
        }
        if (!isset($in_park_info_arr) || !$in_park_info_arr) {
            $in_park_info_arr = [];
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$outParkList',1);

        fdump_api([
            '$whereOutPark'           => $whereOutPark,
            '$car_number_arr'        => $car_number_arr,
            '$whereInfoCar'          => $whereInfoCar,
            '$car_info_arr'          => $car_info_arr,
            '$whereCarPositionInfo'  => $whereCarPositionInfo,
            '$car_position_info_arr' => $car_position_info_arr,
            '$in_park_time_info_arr'      => $in_park_time_info_arr,
            '$in_park_info_arr'      => $in_park_info_arr,
        ], '$parkPassOutList',1);
        // 时间判断有效界限  
        $topLimitTime   = 2177423999; // 上限  暂定2038-12-31 23:59:59
        $floorLimitTime = 1514736000;// 下限 暂定2018-01-01 00:00:00
        $noOutInParkIdArr = [];
        foreach ($outParkList as &$outPark) {
            $order_id     = isset($outPark['order_id'])   && $outPark['order_id']   ? $outPark['order_id']   : '';
            $car_number   = isset($outPark['car_number']) && $outPark['car_number'] ? $outPark['car_number'] : '';
            $inPark       = $order_id    && isset($in_park_info_arr[$order_id])        && $in_park_info_arr[$order_id]        ? $in_park_info_arr[$order_id]        : [];
            if (empty($inPark)) {
                $car_number_time = $car_number . '_' . $outPark['in_time'];
                $inPark  = isset($in_park_time_info_arr[$car_number_time]) && $in_park_time_info_arr[$car_number_time] ? $in_park_time_info_arr[$car_number_time] : [];
            }
            $carInfo      = $car_number  && isset($car_info_arr[$car_number])           && $car_info_arr[$car_number]           ? $car_info_arr[$car_number]    : [];
            $position_id  = $carInfo     && isset($carInfo['car_position_id'])          && $carInfo['car_position_id']          ? $carInfo['car_position_id']          : '';
            $position_num = $position_id && isset($car_position_info_arr[$position_id]) && $car_position_info_arr[$position_id] ? $car_position_info_arr[$position_id] : '';
            $outPark['car_position_id']  = $position_id;
            $outPark['car_user_name']    = isset($carInfo['car_user_name'])  ? $carInfo['car_user_name']  : '';
            $outPark['car_user_phone']   = isset($carInfo['car_user_phone']) ? $carInfo['car_user_phone'] : '';
            $outPark['car_stop_num']     = isset($carInfo['car_stop_num'])   ? $carInfo['car_stop_num']   : '';
            $outPark['position_num']     = $position_num;
            if (!$order_id && isset($inPark['order_id']) && $inPark['order_id']) {
                $outPark['order_id'] = $inPark['order_id'];
            }
            if (!empty($inPark) && $inPark['is_out'] != 1) {
                $noOutInParkIdArr[] = $inPark['id'];
            }
            $standing_time_txt = '--';
            $hasOut   = false;
            if (!empty($outPark)) {
                // 存在出场记录
                $out_time = isset($outPark['out_time']) && $outPark['out_time'] ? $outPark['out_time'] : $inPark['out_time'];
                $in_time  = isset($inPark['in_time']) && $inPark['in_time'] ? $inPark['in_time'] : $outPark['in_time'];
                $hasOut   = true;
            } elseif ($inPark['is_out'] == 1) {
                $out_time = isset($inPark['out_time']) && $inPark['out_time'] ? $inPark['out_time'] :0;
                $in_time  = isset($inPark['in_time']) && $inPark['in_time'] ? $inPark['in_time'] : 0;
                $hasOut   = true;
            } else {
                $in_time  = isset($inPark['in_time']) && $inPark['in_time'] ? $inPark['in_time'] : 0;
                $out_time = isset($inPark['out_time']) && $inPark['out_time'] ? $inPark['out_time'] :0;
            }
            if (!$out_time || intval($out_time) < $floorLimitTime || intval($out_time) > $topLimitTime) {
                $out_time = 0;
            }
            if (!$in_time || intval($in_time) < $floorLimitTime || intval($in_time) > $topLimitTime) {
                $in_time = 0;
            }

            if ($out_time && $in_time) {
                $hasOut = true;
                $standing_time_val = intval($out_time - $in_time);
                $standing_time_txt = $this->secondToTimeTxt($standing_time_val);
                if (!$standing_time_txt) {
                    $standing_time_txt = '无';
                }
            } elseif($hasOut) {
                $standing_time_txt = '无';
            }
            $outPark['standing_time'] = $standing_time_txt;
            if ($out_time) {
                $outPark['out_time'] = date('Y-m-d H:i:s', $out_time);
            } else {
                $outPark['out_time'] = '--';
            }
            if ($in_time) {
                $outPark['in_time'] = date('Y-m-d H:i:s', $in_time);
            } else {
                $outPark['in_time'] = '--';
            }
            if ($hasOut && $in_time && $out_time) {
                $outPark['is_out_text'] = '进场-出场';
            } elseif ($hasOut && $in_time && !$out_time) {
                $outPark['is_out_text'] = '进场-出场';
                $outPark['out_time']    = "二次进场记录更替";
            } elseif ($hasOut && !$in_time && $out_time) {
                $outPark['is_out_text'] = '出场';
                $outPark['in_time']     = "无入场记录";
            } elseif ($hasOut) {
                $outPark['is_out_text'] = '出场';
            } else{
                $outPark['is_out_text'] = '进场';
            }
        }

        if ($isReturnList) {
            return $outParkList;
        }
        fdump_api([
            'time'            => microtime(true),
        ], '$outParkList',1);

        $data = $this->toDayCarStatistics();
        $data['list']       = $outParkList;
        if ($page && $page > 0) {
            $data['pagebar'] = $p->show();
        }
        if (!empty($noOutInParkIdArr)) {
            $whereInPark = [
                'id' => ['in', $noOutInParkIdArr]
            ];
            $db_in_park->where($whereInPark)->save(['is_out' => 1]); // 实际有对应出场记录的但是入场记录未变更出场状态的集中变更
        }
        fdump_api([
            'time'                    => microtime(true),
            '$noOutInParkIdArr'           => $noOutInParkIdArr,
            '$data'           => $data,
        ], '$outParkList',1);
        return $data;
    }
    
    public function secondToTimeTxt($time) {
        $day    = 0;
        $hour   = 0;
        $minute = 0;
        $second = 0;
        if ($time > 86400) {
            $day = floor($time / 86400);
            $time = $time - $day * 86400;
        }
        if ($time > 3600) {
            $hour = floor($time / 3600);
            $time = $time - $hour * 3600;
        }
        if ($time > 60) {
            $minute = floor($time / 60);
            $time = $time - $minute * 60;
        }
        if ($time > 0) {
            $second = $time;
        }
        $time_txt = '';
        if ($day) {
            $time_txt .= $day . '天 ';
        }
        if ($hour) {
            $time_txt .= $hour . '小时';
        }
        if ($minute) {
            $time_txt .= $minute . '分钟';
        }
        if ($second) {
            $time_txt .= $second . '秒';
        }
        return $time_txt;
    }
    
    public function toDayCarStatistics() {
        $db_in_park                        = M('in_park');
        $db_out_park                       = M('out_park');
        $s_time = strtotime(date('Y-m-d', time()));
        $e_time = strtotime(date('Y-m-d 23:59:59', time()));
        $in_where = [];
        $in_where['in_time'] = [['gt', $s_time], ['lt', $e_time]];
        $in_where['park_id'] = $this->park_id;
        $in_count = $db_in_park->where($in_where)->count();//今日进场

        $out_where1 = [];
        $out_where1['out_time'] = [['gt', $s_time], ['lt', $e_time]];
        $out_where1['park_id'] = $this->park_id;
        $out_where1['is_out'] = 1;
        $out_count1 = $db_in_park->where($out_where1)->count();//今日出场1
        $out_where2 = [];
        if ($out_count1) {
            $out_order_id_arr = $db_in_park->where($out_where1)->getField('order_id', true);
        }
        if (isset($out_order_id_arr) && !empty($out_order_id_arr)) {
            $out_where2['out_time'] = ['not in', $out_order_id_arr];
        }
        $out_where2['out_time'] = [['gt', $s_time], ['lt', $e_time]];
        $out_where2['park_id'] = $this->park_id;
        $out_count2 = $db_out_park->where($out_where2)->count();//今日出场2
        $out_count = intval($out_count1) + intval($out_count2);

        $in_car_where = [];
        $in_car_where['is_out']  = 0;
        $in_car_where['park_id'] = $this->park_id;
        $in_car_num = $db_in_park->where($in_car_where)->count();//今日在场
        return [
            'in_count'   => $in_count   ? $in_count   : 0,
            'out_count'  => $out_count  ? $out_count  : 0,
            'in_car_num' => $in_car_num ? $in_car_num : 0,
        ];
    }
    
    public function passCarExport($param) {
        $paramCount = $param;
        $paramCount['getCount'] = 1;
        $in_park_data_count = $this->parkPassList($paramCount);
        fdump_api([$paramCount, $in_park_data_count], '$getCount', 1);
        if(intval($in_park_data_count) <= 0){
            throw new Exception("暂无数据导出!");
        }
        $village_name = isset($param['village_name'])   && $param['village_name']   ? $param['village_name']   : '';
        $village_id   = $this->village_id;
        
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title =  $village_name. '车辆进出统计表格';
        
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil($in_park_data_count/1000);

        $pCoordinate_arr = [
            'A','B','C','D','E','F','G','H','I'
        ];
        $pValue_arr = [
            '车辆号码',
            '车位号',
            '车主姓名',
            '车主手机号',
            '进出场动作',
            '进场时间',
            '出场时间',
            '所在场内时间',
            '当前订单',
        ];
        $key_length = count($pValue_arr) - 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . $in_park_data_count . '条数据');
            $objActSheet = $objExcel->getActiveSheet();
            foreach ($pValue_arr as $k1=>$v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
            }
            $page = $i + 1;
            $param['isReturnList'] = 1;
            $in_park_data = $this->parkPassList($param, $page, 1000);
            if (!empty($in_park_data)) {
                $index = 2;
                $cell_list = range($pCoordinate_arr[0],$pCoordinate_arr[$key_length]);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(25);
                }
                foreach ($in_park_data as $value) {
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, $value['car_number']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, $value['position_num']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, $value['car_user_name']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, $value['car_user_phone']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, $value['is_out_text']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, $value['in_time']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, $value['out_time']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, $value['standing_time']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[8] . $index, $value['order_id']);
                    $index++;
                }
            }
        }
        sleep(2);
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }
}

?>