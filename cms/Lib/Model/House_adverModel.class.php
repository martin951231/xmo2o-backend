<?php
/**
 * Created by PhpStorm.
 * User: guoxiansen
 * Date: 2019/9/24
 * Time: 19:29
 */
class House_adverModel extends Model{


    public function adver_list($cat_key,$limit,$village_id,$param=[],$is_platform=0,$modules=[]){

        $now_category= M('House_adver_category')->field('cat_id,size_info')->where(array('cat_key'=>$cat_key))->find();
        if(!$now_category){
            return [];
        }

        $house_advert_targeted_delivery = C('config.house_advert_targeted_delivery') ? C('config.house_advert_targeted_delivery') : 0;
        if (in_array($cat_key,['house_app_index_top', 'house_app_index_center', 'house_open_advert','house_center_adver']) && $house_advert_targeted_delivery==1 && (!isset($modules['switch']) || intval($modules['switch']) == 0)) {
            //系统轮播图
            $now_village = D('House_village')->where(array('village_id'=>$village_id))->find();
            $where_field = "(";
            if($now_village){
                $first = true;
                if($now_village['province_id']){
                    $where_field .= "(province_id =".$now_village['province_id'];
                    $first = false;
                }
                if($now_village['city_id'] && !$first){
                    $where_field .= " AND (city_id =".$now_village['city_id']." OR city_id=0)";
                } elseif($now_village['city_id']){
                    $where_field .= "((city_id =".$now_village['city_id']." OR city_id=0)";
                    $first = false;
                }
                if($now_village['area_id'] && !$first){
                    $where_field .= " AND (area_id =".$now_village['area_id']." OR area_id=0)";
                } elseif($now_village['area_id']){
                    $where_field .= "((area_id =".$now_village['area_id']." OR area_id=0)";
                    $first = false;
                }
                if($now_village['street_id'] && !$first){
                    $where_field .= " AND (street_id =".$now_village['street_id']." OR street_id=0)";
                }elseif($now_village['street_id']){
                    $where_field .= "((street_id =".$now_village['street_id']." OR street_id=0)";
                    $first = false;
                }
                if($now_village['community_id'] && !$first){
                    $where_field .= " AND (community_id =".$now_village['community_id']." OR community_id=0)";
                } else if($now_village['community_id']){
                    $where_field .= " AND (community_id =".$now_village['community_id']." OR community_id=0)";
                    $first = false;
                }
                if (!$first) {
                    $where_field .= " AND cat_id={$now_category['cat_id']} AND status=1) OR (province_id=0 AND city_id=0 AND area_id=0 AND street_id=0 AND community_id=0  AND cat_id ={$now_category['cat_id']} AND status=1))";
                } else {
                    $where_field = "((cat_id={$now_category['cat_id']} AND status=1) OR (province_id=0 AND city_id=0 AND area_id=0 AND street_id=0 AND community_id=0  AND cat_id ={$now_category['cat_id']} AND status=1))";
                }
            } else {
                $where_field = "((cat_id={$now_category['cat_id']} AND status=1) OR (province_id=0 AND city_id=0 AND area_id=0 AND street_id=0 AND community_id=0  AND cat_id ={$now_category['cat_id']} AND status=1))";
            }
            $now_time = time();
            $str_time = date('Hi', $now_time);
            $str_time = intval($str_time);
            $where_field .= " AND ((effective_start_time=0 AND effective_end_time=0) OR (effective_start_time<={$now_time} AND effective_end_time>={$now_time}))";
            $where_field .= " AND ((play_start_time=0 AND play_end_time=0) OR  (play_start_time<={$str_time} AND play_end_time>={$str_time}))";
            if ($param['id_card']) {
                if (is_idcard($param['id_card'])) {
                    $sex_judge = substr($param['id_card'],-2,1);
                    if ($sex_judge%2==0){
                        // 偶数是女
                        $sex = 2;
                    }else{
                        // 奇数是男
                        $sex = 1;
                    }
                    $age = getAgeByIdcard($param['id_card']);
                }
            }
            if ((!$age || !$sex) && $param['authentication_field']) {
                $authentication_field = $param['authentication_field'];
                $authentication_field = unserialize($authentication_field);
                if (!$sex && $authentication_field && $authentication_field['sex'] && $authentication_field['sex']['value']) {
                    if ('女'==$authentication_field['sex']['value']) {
                        $sex = 2;
                    } elseif ('男'==$authentication_field['sex']['value']) {
                        $sex = 1;
                    }
                }
                if (!$age && $authentication_field && $authentication_field['birthday'] && $authentication_field['birthday']['value']) {
                    $date_time = $authentication_field['birthday']['value'];
                    $date = strtotime($date_time);
                    if ($date) {
                        //获得今日的时间戳
                        $today = strtotime('today');
                        //得到两个日期相差的大体年数
                        $diff = floor(($today-$date)/86400/365);
                        //strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
                        $age = strtotime($date_time.' +'.$diff.'years')>$today?($diff+1):$diff;
                        $age = intval($age);
                    }
                }
            }
            if ($sex) {
                $where_field .= " AND (lm_sex={$sex} OR lm_sex=0)";
            } else {
                $where_field .= " AND lm_sex=0";
            }
            if ($age) {
                $where_field .= " AND ((lm_age_min=0 AND lm_age_max=0) OR  (lm_age_min<={$age} AND lm_age_max>={$age}))";
            } else {
                $where_field .= " AND lm_age_min=0 AND lm_age_max=0";
            }

            $adver_village_list=M('House_adver_village_count')->where(['village_id'=>$village_id])->getField('adver_id',true);
//             print_r($adver_village_list);exit;
            if (empty($adver_village_list)){
                $where_field .= " AND (village_id=0 OR (add_type=1 AND village_id={$village_id}))";
            }else{
                foreach ($adver_village_list as $k=>$aa){
                    $adver_cat=$this->where(['id'=>$aa])->field('cat_id,status,effective_start_time,effective_end_time')->find();
                  //   fdump_api([$adver_village_list,$aa,$adver_cat,$now_category,$now_category['cat_id']],'adver_village_list_0117',1);
                    if (empty($adver_cat)||$now_category['cat_id']!=$adver_cat['cat_id']||$adver_cat['status']!=1 || (($adver_cat['effective_start_time']>$now_time) || ($adver_cat['effective_end_time']>0 && $adver_cat['effective_end_time']<$now_time))){
                        unset($adver_village_list[$k]);
                    }
                }
                if (isset($adver_village_list)&&!empty($adver_village_list)){
                    $adver_village_list=implode(',',$adver_village_list);
                    $where_field .= " AND (village_id=0 OR (add_type=1 AND village_id={$village_id})) OR (id IN ({$adver_village_list}))";
                }else{
                    $where_field .= " AND (village_id=0 OR (add_type=1 AND village_id={$village_id}))";
                }
            }

//            $where_field .= " AND village_id=0";
            $result_list=$this->where($where_field)->order('`sort` DESC,`id` DESC')->limit($limit)->select();
            $dataList = [];
            if($result_list){
                foreach($result_list as $k=>$value){
                    if (strpos($value['pic'], '/upload') !== false) {
                        $value['pic'] = replace_file_domain($value['pic']);
                    } elseif($value['pic']) {
                        $value['pic'] = replace_file_domain('/upload/adver/'.$value['pic']);
                    }
                    if (preg_match("/^rgb/", $value['bg_color']) ) {
                        $bg_color_str = RGBToHex($value['bg_color']);
                    } elseif($value['bg_color']) {
                        $bg_color_str = $value['bg_color'];
                    } else {
                        $bg_color_str = '';
                    }
                    $msg = array(
                        'id'=>$value['id'],
                        'bg_color'=>$value['bg_color']?$value['bg_color']:'',
                        'bg_color_str'=>$bg_color_str,
                        'name'=>$value['name'],
                        'url'=>urldecode($value['url']),
                        'sub_name'=>$value['sub_name']?$value['sub_name']:'',
                        'cat_id'=>$value['cat_id'],
                        'pic'=>empty($value['pic'])?'':$value['pic'],
                    );
                    $msg['wxapp_id']='';
                    $msg['wxapp_page']='';
                    if(isset($value['wxapp_id']) && !empty($value['wxapp_id'])){
                        $msg['wxapp_id']=$value['wxapp_id'];
                        $msg['wxapp_page']=$value['wxapp_page'];
                    }
                    if ($house_advert_targeted_delivery==1) {
                        $msg['click_count'] = true;
                        $param_advert = [
                            'village_id' => $value['village_id'] ? $value['village_id'] : 0,
                            'lm_day_look_max' => $value['lm_day_look_max'] ? $value['lm_day_look_max'] : 0,
                            'lm_day_click_max' => $value['lm_day_click_max'] ? $value['lm_day_click_max'] : 0
                        ];
                        $this->add_house_adver_record($value['id'],1,$param_advert);
                        $where_advert_village=[];
                        $where_advert_village = [
                            'adver_id' => $value['id'],
                            'village_id' => $village_id,
                        ];
                        $house_adver_village = M('House_adver_village_count')->where($where_advert_village)->find();
                        if (!empty($house_adver_village)){
                            $data_adver=[];
                            $data_adver['look_num']=$house_adver_village['look_num']+1;
                            $data_adver['last_time']=time();
                            M('House_adver_village_count')->where($where_advert_village)->data($data_adver)->save();
                        }else{
                            $data_count = [];
                            $data_count['village_id'] = $village_id;
                            $data_count['look_num']=1;
                            $data_count['adver_id'] = $value['id'];
                            $data_count['last_time'] = time();
                            $data_count['add_time'] = time();
                            M('House_adver_village_count')->data($data_count)->add();
                        }

                    } else {
                        $msg['click_count'] = false;
                    }
                    $size = isset($now_category['size_info']) && trim($now_category['size_info']) ? $now_category['size_info'] : '';
                    $sizeArr = explode("*", $size);
                    $msg['size_width'] = isset($sizeArr[0]) && intval($sizeArr[0]) ? $sizeArr[0] : '';
                    $msg['size_height'] = isset($sizeArr[0]) && intval($sizeArr[0]) ? $sizeArr[0] : '';
                    $dataList[] = $msg;
                }
            }
            return $dataList;
        }

//        $result_list=$this->where(array('cat_id'=>$now_category['cat_id'],'village_id'=>$village_id,'status'=>1))->order('`sort` DESC,`id` DESC')->limit($limit)->select();
        $where_str='(cat_id ='.$now_category['cat_id'].' and  village_id = '.$village_id.' and status = 1)';
        if(isset($modules['switch']) && intval($modules['switch']) > 0){
            $w1=' status = 1 and village_id = 0 and (filter_village_ids is null OR  !FIND_IN_SET('.$village_id.', filter_village_ids )) ';
            if($is_platform > 0){
                $w1.=' and is_platform = '.$is_platform;
            }
            if($is_platform!=3 && !empty($modules['modules'])){
                $where_str_modules = "'".implode("','", $modules['modules'])."'";
                $w1.=' and (source_type in( '.$where_str_modules.') or source_type = "0")';
            }
            $where_str.=' or ('.$w1.')';
        }

        //todo is_platform = 1 兼容平台发布的数据
        $result_list=$this->where($where_str)->order('is_platform desc,`sort` DESC,`id` DESC')->limit($limit)->select();
        $dataList = [];
        if($result_list){
            foreach($result_list as $k=>$value){
                if (strpos($value['pic'], '/upload') !== false) {
                    $value['pic'] = replace_file_domain($value['pic']);
                } elseif($value['pic']) {
                    $value['pic'] = replace_file_domain('/upload/adver/'.$value['pic']);
                }
                if (preg_match("/^rgb/", $value['bg_color']) ) {
                    $bg_color_str = RGBToHex($value['bg_color']);
                } elseif($value['bg_color']) {
                    $bg_color_str = $value['bg_color'];
                } else {
                    $bg_color_str = '';
                }
                $tmp_data = array(
                    'id'=>$value['id'],
                    'bg_color'=>$value['bg_color']?$value['bg_color']:'',
                    'bg_color_str'=>$bg_color_str,
                    'name'=>$value['name'],
                    'url'=>urldecode($value['url']),
                    'sub_name'=>$value['sub_name']?$value['sub_name']:'',
                    'cat_id'=>$value['cat_id'],
                    'pic'=>empty($value['pic'])?'':$value['pic'],
                    'click_count' => false
                );

                $tmp_data['wxapp_id']='';
                $tmp_data['wxapp_page']='';
                if(isset($value['wxapp_id']) && !empty($value['wxapp_id'])){
                    $tmp_data['wxapp_id']=$value['wxapp_id'];
                    $tmp_data['wxapp_page']=$value['wxapp_page'];
                }
                $dataList[]=$tmp_data;
            }
        }

        //系统轮播图
        $now_village = D('House_village')->where(array('village_id'=>$village_id))->find();



        $where_field = "(";
        $is_where=false;
        if($now_village){
            if($now_village['province_id']){
                $where_field .= " province_id =".$now_village['province_id'];
                $is_where=true;
            }
            if($now_village['city_id']){
                $where_field .= " AND (city_id =".$now_village['city_id']." OR city_id=0)";
                $is_where=true;
            }
            if($now_village['area_id']){
                $where_field .= " AND (area_id =".$now_village['area_id']." OR area_id=0)";
                $is_where=true;
            }
            if($now_village['street_id']){
                $where_field .= " AND (street_id =".$now_village['street_id']." OR street_id=0)";
                $is_where=true;
            }
            if($now_village['community_id']){
                $where_field .= " AND (community_id =".$now_village['community_id']." OR community_id=0)";
                $is_where=true;
            }
        }


        if('house_app_index_top' == $cat_key){
            $platform_where="(province_id=0 AND city_id=0 AND area_id=0 AND street_id=0 AND community_id=0 AND cat_id={$now_category['cat_id']} AND village_id=0 AND status=1)";
            if(isset($modules['switch']) && intval($modules['switch']) > 0){
                if(!empty($modules['modules'])){
                    $platform_modules = "'".implode("','", $modules['modules'])."'";
                    $platform_where='(cat_id='.$now_category['cat_id'].' AND village_id=0 AND status=1 and (source_type in( '.$platform_modules.') or source_type = "0") and (filter_village_ids is null OR  !FIND_IN_SET('.$village_id.', filter_village_ids )))';
                }
            }
            //todo source_type 不等于0 表示关联了小区功能库 有购买套餐才可显示 等于0是无条件显示
            $platform_where.=' or (cat_id='.$now_category['cat_id'].' AND village_id='.$village_id.' AND status=1 )';
           /* if ($is_where) {
                $where_field .= " AND cat_id={$now_category['cat_id']} AND status=1) OR ".$platform_where;
            } else {
                $where_field = "(cat_id={$now_category['cat_id']} AND status=1) OR ".$platform_where;
            }*/
            if ($is_where) {
                $where_field .= " ) OR ".$platform_where;
            } else {
                $where_field = $platform_where;
            }
            $system_adver_list=$this->where($where_field)->order('`sort` DESC,`id` DESC')->select();
            if($system_adver_list){
                foreach($system_adver_list as $kk=>$vv){
                    if (strpos($vv['pic'], '/upload') !== false) {
                        $vv['pic'] = replace_file_domain($vv['pic']);
                    } elseif($vv['pic']) {
                        $vv['pic'] = replace_file_domain('/upload/adver/'.$vv['pic']);
                    }
                    if (preg_match("/^rgb/", $vv['bg_color']) ) {
                        $bg_color_str = RGBToHex($vv['bg_color']);
                    } elseif($vv['bg_color']) {
                        $bg_color_str = $vv['bg_color'];
                    } else {
                        $bg_color_str = '';
                    }
                    $msg = array(
                        'id'=>$vv['id'],
                        'bg_color'=>$vv['bg_color']?$vv['bg_color']:'',
                        'bg_color_str'=>$bg_color_str,
                        'name'=>$vv['name'],
                        'url'=>urldecode($vv['url']),
                        'sub_name'=>$vv['sub_name']?$vv['sub_name']:'',
                        'cat_id'=>$vv['cat_id'],
                        'pic'=>empty($vv['pic'])?'':$vv['pic'],
                        'click_count' => false
                    );
                    $msg['wxapp_id']='';
                    $msg['wxapp_page']='';
                    if(isset($vv['wxapp_id']) && !empty($vv['wxapp_id'])){
                        $msg['wxapp_id']=$vv['wxapp_id'];
                        $msg['wxapp_page']=$vv['wxapp_page'];
                    }
                    $dataList[] = $msg;
                }
            }
            $dataList = $this->multi_array_unqie($dataList);
        }

        if(!empty($dataList)){
            $uid = isset($param['uid'])&&$param['uid']?$param['uid']:-1;
            foreach ($dataList as &$v){
                $size = isset($now_category['size_info']) && trim($now_category['size_info']) ? $now_category['size_info'] : '';
                $sizeArr = explode("*", $size);
                $v['size_width'] = isset($sizeArr[0]) && intval($sizeArr[0]) ? $sizeArr[0] : '';
                $v['size_height'] = isset($sizeArr[1]) && intval($sizeArr[1]) ? $sizeArr[1] : '';
                $v['url']=D('House_link')->replace_village_parameter($v['url'],$village_id,$uid);
            }
            unset($v);
        }
        return $dataList;
    }


    public function multi_array_unqie($arr){
        $data = array();
        foreach ($arr as $val){
            if(!array_key_exists($val['id'],$data)){
                $data[$val['id']] = $val;
            }
         }
        $data=array_values($data);
        return $data;
    }

    public function add_house_adver_record($adver_id,$add_type=1,$param=[], $business_type='house_adver') {
        if (intval($param['lm_day_look_max'])>0) {
            $lm_day_look_max = intval($param['lm_day_look_max']);
        } elseif (1==$add_type) {
            $lm_day_look_max = M('House_adver')->where(['id' => $adver_id])->getField('lm_day_look_max');
        }
        if (intval($param['lm_day_click_max'])>0) {
            $lm_day_click_max = intval($param['lm_day_click_max']);
        } elseif (2==$add_type) {
            $lm_day_click_max = M('House_adver')->where(['id' => $adver_id])->getField('lm_day_click_max');
        }
        if (isset($param['village_id'])) {
            $village_id = intval($param['$village_id']);
        } else {
            $village_id = 0;
        }
        $now_time = time();
        // 当前月
        $month_time = date('Ym', $now_time);
        // 当前日
        $day_time = date('Ymd', $now_time);
        // 查询下单日记录 单月记录  总记录
        $where_advert_day = [
            'village_id' => $village_id,
            'adver_id' => $adver_id,
            'business_type' => $business_type,
            'type' => 3,
            'day_time' => $day_time,
        ];
        $record_day = M('House_adver_record')->where($where_advert_day)->find();
        if ($record_day) {
            if (1==$add_type) {
                if ($lm_day_look_max && intval($record_day['look_num'])>=intval($lm_day_look_max)) {
                    return false;
                }
                // 增加浏览量
                $set_look = [
                    'look_num' => intval($record_day['look_num']) + 1,
                    'last_time' => $now_time
                ];
                M('House_adver_record')->where(['record_id' => $record_day['record_id']])->save($set_look);
            } elseif (2==$add_type) {
                if ($lm_day_click_max && intval($record_day['click_num'])>=intval($lm_day_click_max)) {
                    return false;
                }
                // 增加点击量
                $set_click = [
                    'click_num' => intval($record_day['click_num']) + 1,
                    'last_time' => $now_time
                ];
                M('House_adver_record')->where(['record_id' => $record_day['record_id']])->save($set_click);
            } else {
                return true;
            }
        }
        else {
            if (1==$add_type) {
                // 增加浏览量
                $data_look = [
                    'adver_id' => $adver_id,
                    'business_type' => $business_type,
                    'village_id' => $village_id,
                    'type' => 3,
                    'look_num' => 1,
                    'click_num' => 0,
                    'day_time' => $day_time,
                    'month_time' => $month_time,
                    'add_time' => $now_time
                ];
                M('House_adver_record')->add($data_look);
            } elseif (2==$add_type) {
                // 增加点击量
                $data_look = [
                    'adver_id' => $adver_id,
                    'business_type' => $business_type,
                    'village_id' => $village_id,
                    'type' => 3,
                    'look_num' => 0,
                    'click_num' => 1,
                    'day_time' => $day_time,
                    'month_time' => $month_time,
                    'add_time' => $now_time
                ];
                M('House_adver_record')->add($data_look);
            } else {
                return true;
            }
        }
        $where_advert_month = [
            'village_id' => $village_id,
            'adver_id' => $adver_id,
            'business_type' => $business_type,
            'type' => 2,
            'month_time' => $month_time,
        ];
        $record_month = M('House_adver_record')->where($where_advert_month)->find();
        if ($record_month) {
            if (1==$add_type) {
                // 增加浏览量
                $set_look = [
                    'look_num' => intval($record_month['look_num']) + 1,
                    'last_time' => $now_time
                ];
                M('House_adver_record')->where(['record_id' => $record_month['record_id']])->save($set_look);
            } elseif (2==$add_type) {
                // 增加点击量
                $set_click = [
                    'click_num' => intval($record_month['click_num']) + 1,
                    'last_time' => $now_time
                ];
                M('House_adver_record')->where(['record_id' => $record_month['record_id']])->save($set_click);
            } else {
                return true;
            }
        } else {
            if (1==$add_type) {
                // 增加浏览量
                $data_look = [
                    'adver_id' => $adver_id,
                    'business_type' => $business_type,
                    'village_id' => $village_id,
                    'type' => 2,
                    'look_num' => 1,
                    'click_num' => 0,
                    'day_time' => $day_time,
                    'month_time' => $month_time,
                    'add_time' => $now_time
                ];
                M('House_adver_record')->add($data_look);
            } elseif (2==$add_type) {
                // 增加点击量
                $data_look = [
                    'adver_id' => $adver_id,
                    'business_type' => $business_type,
                    'village_id' => $village_id,
                    'type' => 2,
                    'look_num' => 0,
                    'click_num' => 1,
                    'day_time' => $day_time,
                    'month_time' => $month_time,
                    'add_time' => $now_time
                ];
                M('House_adver_record')->add($data_look);
            } else {
                return true;
            }
        }

        $where_advert_total = [
            'village_id' => $village_id,
            'adver_id' => $adver_id,
            'business_type' => $business_type,
            'type' => 1,
        ];
        $record_info = M('House_adver_record')->where($where_advert_total)->find();
        if ($record_info) {
            if (1==$add_type) {
                // 增加浏览量
                $set_look_t = [
                    'look_num' => intval($record_info['look_num']) + 1,
                    'last_time' => $now_time
                ];
                M('House_adver_record')->where(['record_id' => $record_info['record_id']])->save($set_look_t);
            } elseif (2==$add_type) {
                // 增加点击量
                $set_click_t = [
                    'click_num' => intval($record_info['click_num']) + 1,
                    'last_time' => $now_time
                ];
                M('House_adver_record')->where(['record_id' => $record_info['record_id']])->save($set_click_t);
            } else {
                return true;
            }
        } else {
            if (1==$add_type) {
                // 增加浏览量
                $data_look = [
                    'adver_id' => $adver_id,
                    'business_type' => $business_type,
                    'village_id' => $village_id,
                    'type' => 1,
                    'look_num' => 1,
                    'click_num' => 0,
                    'day_time' => $day_time,
                    'month_time' => $month_time,
                    'add_time' => $now_time
                ];
                M('House_adver_record')->add($data_look);
            } elseif (2==$add_type) {
                // 增加点击量
                $data_look = [
                    'adver_id' => $adver_id,
                    'business_type' => $business_type,
                    'village_id' => $village_id,
                    'type' => 1,
                    'look_num' => 0,
                    'click_num' => 1,
                    'day_time' => $day_time,
                    'month_time' => $month_time,
                    'add_time' => $now_time
                ];
                M('House_adver_record')->add($data_look);
            } else {
                return true;
            }
        }
    }

}