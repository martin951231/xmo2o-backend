<?php

class House_village_user_bindModel extends Model
{

    public $relatives_type_info = array(
        0 => array(
            'relatives_type' => 1,
            'name' => '配偶'
        ),
        1 => array(
            'relatives_type' => 2,
            'name' => '父母'
        ),
        2 => array(
            'relatives_type' => 3,
            'name' => '子女'
        ),
        3 => array(
            'relatives_type' => 4,
            'name' => '亲朋好友'
        ),
    );

    public $user_type = [
        0 => [
            'type' => 0,
            'name' => '业主',
        ],
        1 => [
            'type' => 1,
            'name' => '家属',
        ],
        2 => [
            'type' => 2,
            'name' => '租客',
        ],
        3 => [
            'type' => 3,
            'name' => '更新业主',
        ],
        4 => [
            'type' => 4,
            'name' => '工作人员',
        ],
        5 => [
            'type' => 5,
            'name' => '访客',
        ],
        6 => [
            'type' => 6,
            'name' => '出入证',
        ],
    ];

    // 1住宅 2商铺 3办公
    public $house_type_arr = [1=>'住宅',2=>'商铺',3=>'办公'];

    /*通过手机号自动绑定业主*/
    public function bind($uid, $phone)
    {
		if(empty($uid) || empty($phone)){
			return false;
		}
        $this->data(array('uid' => $uid))->where(array('phone' => $phone))->save();
        D('House_village_user_vacancy')->data(array('uid' => $uid))->where(array('phone' => $phone))->save();
    }

    public function get_user_bind_list($uid, $village_id)
    {
        // 暂时只支持 业主 家属 租客 和  工作人员
        $where = array(
            'uid' => $uid,
            'village_id' => $village_id,
            'status'=>1,
            'type'=>['in', [0,1,2,3,4]]
        );
	    $bind_list = $this->field(true)->where($where)->order('`pigcms_id` DESC')->select();

        if($bind_list){
            foreach($bind_list as $k=>&$v){
//                $single = M('House_village_single')->field('single_name')->where(array('id'=>$v['single_id']))->find();
//                $floor = M('House_village_floor')->field('floor_name')->where(array('floor_id'=>$v['floor_id']))->find();
//                $vacancy = M('House_village_user_vacancy')->field('room')->where(array('pigcms_id'=>$v['vacancy_id']))->find();
//
//                $v['address'] = D('House_village')->word_replce_msg(array('single_name'=>$single['single_name'],'floor_name'=>$floor['floor_name'],'layer'=>$v['layer_num'],'room'=>$vacancy['room']));
                $v['address'] = D('House_village')->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
            }
        }

        return $bind_list;
    }


    public function get_family_user_bind_list($uid, $village_id)
    {
        $bind_list = $this->where(array('uid' => $uid, 'village_id' => $village_id , 'status'=>1, 'parent_id' => array('neq',0)))->order('`pigcms_id` DESC')->select();

        foreach($bind_list as &$val){
            //$val['address'] = $this->where(array('pigcms_id'=>$val['parent_id']))->getField('address');


            $val['address'] = D('House_village')->get_single_floor_room($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
        }

        return $bind_list;
    }

    /*得到小区下所有的业主列表*/
    public function get_limit_list_page($village_id, $pageSize = 20, $column = array(),$type='')
    {
        if (!$village_id) {
            return null;
        }
        if (isset($column['getChild']) && !$column['getChild']) {
            $getChild = false;
        } else {
            $getChild = true;
        }
        if (isset($column['getChild'])) {
            unset($column['getChild']);
        }
        $orderby='ub.pigcms_id DESC';
        if (isset($column['orderby'])) {
            if($column['orderby']){
                $orderby=$column['orderby'].', ub.pigcms_id DESC';
            }
            unset($column['orderby']);
        }
        
        $return = array();
        $condition_where = 'ub.village_id ='.$village_id;
        if (isset($column['_string'])) {
            $condition_where .= " AND ({$column['_string']})";
            unset($column['_string']);
        }
        if(!$type){
            $condition_where .= " AND ub.type in (0,3) AND ub.vacancy_id>0";
            // $condition_where['parent_id'] = 0;
            // $condition_where['type'] = array('neq',4);
        }else{
            // $condition_where['status'] = 1;
            $condition_where .= " AND ub.type in (0,1,2)";
            // $condition_where['type'] = array('in',array(0,1,2));
        }
        if (!isset($column['status'])) {
            // 去除禁止和删除的
            $condition_where .= ' AND ub.status NOT IN (0,3,4)';
        }
        /**是否绑定微信*is_bind_weixin 1是 2否****/
        $is_join_user=0;
        if(isset($column['is_bind_weixin'])){
            $is_join_user=1;
            if($column['is_bind_weixin']==1){
                $condition_where .= ' AND ((us.openid IS NOT NULL AND us.openid!="") OR (us.wxapp_openid IS NOT NULL AND us.wxapp_openid!=""))';
            }elseif ($column['is_bind_weixin']==2){
                $condition_where .= ' AND (ub.uid<1 OR ((us.openid IS NULL OR us.openid="") AND (us.wxapp_openid IS NULL OR us.wxapp_openid="")))';
            }
            unset($column['is_bind_weixin']);
        }
        if(isset($column['source_comefrom'])){
            /**是否绑定微信*source_comefrom 1公众号 2小程序3小区自动注册4其他 5 H5****/
            $is_join_user=1;
            if($column['source_comefrom']==1){
                $condition_where .= ' AND (ub.uid>0 AND us.source = "weixin")';
            }elseif ($column['source_comefrom']==2){
                $condition_where .= ' AND (ub.uid>0 AND (us.source = "wxapp" OR us.source = "wxcapp"))';
            }elseif ($column['source_comefrom']==3){
                $condition_where .= ' AND (ub.uid>0 AND us.source = "houseautoreg" )';
            }elseif($column['source_comefrom']==5){
                $condition_where .= ' AND (ub.uid>0 AND us.source = "wap" )';
            }elseif ($column['source_comefrom']==4){
                $condition_where .= ' AND (ub.uid<1 OR (us.source not in ("weixin","wxapp","wxcapp","houseautoreg","wap")))';
            }
            unset($column['source_comefrom']);
        }
        foreach ($column as $key => $value) {
            if ( $key == 'name' ) {
                $condition_where .= " AND REPLACE(ub.name,' ','') ";
            }else{
                $condition_where .= " AND ub.".$key." ";
            }
            if (is_array($value)) {
                switch ($value[0]) {
                    case 'eq':
                        $condition_where .= '="'.$value[1].'"';
                        break;
                    case 'neq':
                        $condition_where .= '<>"'.$value[1].'"';
                        break;
                    case 'like':
                        $condition_where .= 'like "'.$value[1].'"';
                        break;
                    case 'between':
                        $condition_where .= " between '".$value[1][0]."' and '".$value[1][1]."'";
                        break;
                    case 'egt':
                        $condition_where .= " >= '".$value[1]."'";
                        break;
                    case 'elt':
                        $condition_where .= " <= '".$value[1]."'";
                        break;
                    case 'gt':
                        $condition_where .= " > '".$value[1]."'";
                        break;
                    case 'lt':
                        $condition_where .= " < '".$value[1]."'";
                        break;
                    case 'in':
                        $condition_where .= " in (".implode(',',$value[1]).')';
                        break;
                }
            }else{
                $condition_where .= " = '".$value."'";
            }
        }
        
        if($is_join_user==1){
            $left_join = ' left join '.C('DB_PREFIX').'user as us on ub.uid=us.uid';
            $count_user = $this->alias('ub')->join($left_join)->where($condition_where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_user, $pageSize, 'page');
            $user_list = $this->alias('ub')->join($left_join)->field('ub.*')->where($condition_where)->order($orderby)->limit($p->firstRow . ',' . $p->listRows)->select();
            //echo $this->getLastSql();
        }else{
            $count_user = $this->alias('ub')->where($condition_where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_user, $pageSize, 'page');
            $user_list = $this->alias('ub')->field('ub.*')->where($condition_where)->order($orderby)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $database_house_village_pay_order = D('House_village_pay_order');
        
        $sing_arr = M('House_village_single')->where(array('village_id' => $village_id))->getField('id,single_name');
        $db_house_village_floor = M('House_village_floor');
        $db_house_village_user_vacancy = M('House_village_user_vacancy');
        $roomIdArrs =[];
        $layerArrs =[];
        $floorArrs =[];
        foreach($user_list as $Key=>$user){
            if(empty($user['bind_number']) && $user['vacancy_id']){
                $user_vacancy=$db_house_village_user_vacancy->where(['pigcms_id' => $user['vacancy_id']])->field('property_number')->find();
                $this->where(array('pigcms_id'=>$user['pigcms_id']))->data(array('bind_number'=>$user_vacancy['property_number']))->save();
                $user_list[$Key]['bind_number']=$user_vacancy['property_number'];
            }
            if (isset($user['vacancy_id']) && $user['vacancy_id']) {
                $roomIdArrs[] = $user['vacancy_id'];
            }
            if($user['layer_id']) {
                $layer_id = $user['layer_id'];
            } elseif ($user['vacancy_id']) {
                $layer_id = $db_house_village_user_vacancy->where(['pigcms_id' => $user['vacancy_id']])->getField('layer_id');
            }
            if (isset($layerArrs[$layer_id]) && $layerArrs[$layer_id]) {
                $layer_num = $layerArrs[$layer_id];
            } elseif ($layer_id) {
                $layer_num = M('House_village_layer')->where(['id' => $layer_id])->getField('layer_name');
                $layerArrs[$layer_id] = $layer_num;
            } else {
                $layer_num = $user['layer_num'];
            }
            if ($user['floor_id'] && isset($floorArrs[$user['floor_id']]) && $floorArrs[$user['floor_id']]) {
                $floor_info = $floorArrs[$user['floor_id']];
            } elseif($user['floor_id']) {
                $floor_info = $db_house_village_floor->where(['floor_id' => $user['floor_id']])->field('floor_id,single_id,floor_name,floor_layer,door_control')->find();
                $floorArrs[$user['floor_id']] = $floor_info;
            } else {
                $floor_info = [];
            }
            if (empty($floor_info['door_control'])) {
                $door_control_str = $user['door_control'];
            }elseif ($user['door_control'] == null) {
                $door_control_str = $floor_info['door_control'];
            }else{
                $door_control_str = $user['door_control'].','.$floor_info['door_control'];
            }
            $user_list[$Key]['door_control_str'] = $door_control_str;

            $word_data = array();
            $single_id = $user['single_id'] ? $user['single_id'] : ($floor_info['single_id']?$floor_info['single_id']:0);
            if ($sing_arr[$single_id] && $single_id>0) {
                $sing_name = $sing_arr[$single_id];
            } else {
                $sing_name = '';
            }
            if(is_numeric($layer_num)){
                $layer_num=$layer_num.'层';
            }
            $word_data['layer'] = $layer_num;
            $word_data['room'] = $user['room_addrss'];
            if (!empty($floor_info)) {
                $word_data['floor_name'] = $floor_info['floor_name'];
                if(is_numeric($word_data['floor_name'])){
                    $word_data['floor_name']=$word_data['floor_name'].(D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元');
                }
                if (!$sing_name) {
                    $sing_name = $floor_info['floor_layer'];
                }
            }
            if(is_numeric($sing_name)){
                $sing_name=$sing_name.(D('House_village')->getHouseAlias('single_name',$village_id)?:'栋');
            }else{
                $lastchar=substr($sing_name,-1,1);
                if(is_numeric($lastchar)){
                    $sing_name=$sing_name.(D('House_village')->getHouseAlias('single_name',$village_id)?:'栋');
                }
            }

            $word_data['single_name'] = $sing_name;
            if($user['vacancy_id']) {
                $word_data['room'] = D("House_village_user_vacancy")->where(['pigcms_id' => $user['vacancy_id'],'status'=>array('neq',0)])->getField('room');
            }
            $room_diy_name = D('House_village')->word_replce_msg($word_data,$village_id);
            $user_list[$Key]['address'] = $room_diy_name;
            if ($getChild) {
                $condition = [];
                $condition['parent_id'] = $user['pigcms_id'];
                $bind_list = $this->where($condition)->select();
                if($bind_list){
                    $user_list[$Key]['bind_list'] = $bind_list;
                }
            }
        }

        if ($user_list) {
            $return['totalPage'] = ceil($count_user / $pageSize);
            $return['user_count'] = count($user_list);
            $return['count_user'] = $count_user;
            $return['pagebar'] = $p->show();
            $return['user_list'] = $user_list;
            $return['roomIdArrs'] = $roomIdArrs;
        }

        return $return;
    }


    /*得到小区下所有的业主列表-业主导出时候查询使用*/
    public function get_limit_list_page_new($village_id, $pageSize = 20, $column = array(),$type='', $getChild=true)
    {
        if (!$village_id) {
            return null;
        }
        $return = array();
        $condition_where = 'ub.village_id ='.$village_id;
        if (isset($column['_string'])) {
            $condition_where .= " AND ({$column['_string']})";
            unset($column['_string']);
        }
        if(!$type){
            $condition_where .= " AND ub.parent_id=0 AND ub.type in (0,3)";
            // $condition_where['parent_id'] = 0;
            // $condition_where['type'] = array('neq',4);
        }else{
            // $condition_where['status'] = 1;
            $condition_where .= " AND ub.type in (0,1,2)";
            // $condition_where['type'] = array('in',array(0,1,2));
        }
        if (!isset($column['status'])) {
            $condition_where .= ' AND ub.status not in (0,3,4)';
        }
        /**是否绑定微信*is_bind_weixin 1是 2否****/
        $is_join_user=0;
        if(isset($column['is_bind_weixin'])){
            $is_join_user=1;
            if($column['is_bind_weixin']==1){
                $condition_where .= ' AND ((us.openid IS NOT NULL AND us.openid!="") OR (us.wxapp_openid IS NOT NULL AND us.wxapp_openid!=""))';
            }elseif ($column['is_bind_weixin']==2){
                $condition_where .= ' AND (ub.uid<1 OR ((us.openid IS NULL OR us.openid="") AND (us.wxapp_openid IS NULL OR us.wxapp_openid="")))';
            }
            unset($column['is_bind_weixin']);
        }
        if(isset($column['source_comefrom'])){
            /**是否绑定微信*source_comefrom 1公众号 2小程序3小区自动注册4其他 5 H5****/
            $is_join_user=1;
            if($column['source_comefrom']==1){
                $condition_where .= ' AND (ub.uid>0 AND us.source = "weixin")';
            }elseif ($column['source_comefrom']==2){
                $condition_where .= ' AND (ub.uid>0 AND (us.source = "wxapp" OR us.source = "wxcapp"))';
            }elseif ($column['source_comefrom']==3){
                $condition_where .= ' AND (ub.uid>0 AND us.source = "houseautoreg" )';
            }elseif($column['source_comefrom']==5){
                $condition_where .= ' AND (ub.uid>0 AND us.source = "wap" )';
            }elseif ($column['source_comefrom']==4){
                $condition_where .= ' AND (ub.uid<1 OR (us.source not in ("weixin","wxapp","wxcapp","houseautoreg","wap")))';
            }
            unset($column['source_comefrom']);
        }
        foreach ($column as $key => $value) {
            if ( $key == 'name' ) {
                $condition_where .= " AND REPLACE(ub.name,' ','') ";
            }else{
                $condition_where .= " AND ub.".$key." ";
            }
            if (is_array($value)) {
                switch ($value[0]) {
                    case 'eq':
                        $condition_where .= '="'.$value[1].'"';
                        break;
                    case 'neq':
                        $condition_where .= '<>"'.$value[1].'"';
                        break;
                    case 'like':
                        $condition_where .= 'like "'.$value[1].'"';
                        break;
                    case 'between':
                        $condition_where .= " between '".$value[1][0]."' and '".$value[1][1]."'";
                        break;
                    case 'egt':
                        $condition_where .= " >= '".$value[1]."'";
                        break;
                    case 'elt':
                        $condition_where .= " <= '".$value[1]."'";
                        break;
                    case 'gt':
                        $condition_where .= " > '".$value[1]."'";
                        break;
                    case 'lt':
                        $condition_where .= " < '".$value[1]."'";
                        break;
                    case 'in':
                        $condition_where .= " in (".implode(',',$value[1]).')';
                        break;
                }
            }else{
                $condition_where .= " = '".$value."'";
            }
        }

        if($is_join_user==1){
            $left_join = ' left join '.C('DB_PREFIX').'user as us on ub.uid=us.uid';
            $count_user = $this->alias('ub')->join($left_join)->where($condition_where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_user, $pageSize, 'page');
            $user_list = $this->alias('ub')->join($left_join)->field('ub.*,us.source as us_source,us.openid as us_openid,us.wxapp_openid as us_wxapp_openid')->where($condition_where)->order('ub.pigcms_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            //echo $this->getLastSql();
        }else{
            $count_user = $this->alias('ub')->where($condition_where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_user, $pageSize, 'page');
            $user_list = $this->alias('ub')->field('ub.*')->where($condition_where)->order('ub.pigcms_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $database_house_village_pay_order = D('House_village_pay_order');
        // 业主属性
        $attribute_list = D('House_village_user_attribute')->get_attribute($village_id,true);

        $sing_arr = M('House_village_single')->where(array('village_id' => $village_id))->getField('id,single_name');
        $cunstom_arr = [];
        $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
        $db_house_village_user_bind = D('House_village_user_bind');
        $db_house_village_user_vacancy = M('House_village_user_vacancy');
        $userDb=D('User');
        foreach($user_list as $Key=>$user){
            $vacancy_room_info = $db_house_village_user_vacancy->where(['pigcms_id' => $user['vacancy_id']])->field('house_type,layer_id,room')->find();
            if (!$vacancy_room_info['house_type']) {
                $house_type=1;
            } else {
                $house_type = $vacancy_room_info['house_type'];
            }
            $user_list[$Key]['is_bind_weixin']='否';
            $user_list[$Key]['source_comefrom']='其他';
            if(isset($user['us_source'])){
                if(!empty($user['us_openid']) || !empty($user['us_wxapp_openid'])){
                    $user_list[$Key]['is_bind_weixin']='是';
                }
                if(!empty($user['us_source']) && ($user['us_source']=='weixin')){
                    $user_list[$Key]['source_comefrom']='公众号';
                }else if(!empty($user['us_source']) && ($user['us_source']=='wxapp' || $user['us_source']=='wxcapp')){
                    $user_list[$Key]['source_comefrom']='小程序';
                }elseif(!empty($user['us_source']) && ($user['us_source']=='houseautoreg')){
                    $user_list[$Key]['source_comefrom']='小区自动注册';
                }elseif(!empty($user['us_source']) && ($user['us_source']=='wap')){
                    $user_list[$Key]['source_comefrom']='H5';
                }
            }else if($user['uid']>0){
                $userTmp=$userDb->where(array('uid'=>$user['uid']))->field('source as us_source,openid as us_openid,wxapp_openid as us_wxapp_openid')->find();
                    if($userTmp && (!empty($userTmp['us_openid']) || !empty($userTmp['us_wxapp_openid']))){
                        $user_list[$Key]['is_bind_weixin']='是';
                    }
                if($userTmp && !empty($userTmp['us_source']) && ($userTmp['us_source']=='weixin')){
                    $user_list[$Key]['source_comefrom']='公众号';
                }else if($userTmp && !empty($userTmp['us_source']) && ($userTmp['us_source']=='wxapp' || $userTmp['us_source']=='wxcapp')){
                    $user_list[$Key]['source_comefrom']='小程序';
                }elseif($userTmp && !empty($userTmp['us_source']) && ($userTmp['us_source']=='houseautoreg')){
                    $user_list[$Key]['source_comefrom']='小区自动注册';
                }elseif($userTmp && !empty($userTmp['us_source']) && ($userTmp['us_source']=='wap')){
                    $user_list[$Key]['source_comefrom']='H5';
                }
            }
            $user_list[$Key]['house_type'] = $house_type;
            $user_list[$Key]['floor_type_name'] = $this->house_type_arr[$house_type];

            if($user['floor_id']){
                $floor_info = D('House_village_floor')->where(array('floor_id'=>$user['floor_id']))->find();
                $floor_type_name= D('House_village_floor_type')->where(array('id'=>$floor_info['floor_type']))->getField('name');
                $house_type= D('House_village_user_vacancy')->where(['pigcms_id' => $user['vacancy_id']])->getField('house_type');
               if (empty($floor_type_name)){
                   if ($house_type==1){
                       $user_list[$Key]['floor_type_name']='住宅';
                   }elseif($house_type==2){
                       $user_list[$Key]['floor_type_name']='商铺';
                   }else{
                       $user_list[$Key]['floor_type_name']='办公';
                   }
               }else{
                   $user_list[$Key]['floor_type_name']=$floor_type_name;
               }
                 $user_list[$Key]['address'] = $floor_info['floor_layer'] . $floor_info['floor_name'] . $user['layer_num']  . '#' . $user['room_addrss'];
            }
            if($user['layer_id']) {
                $layer_id = $user['layer_id'];
            } elseif ($user['vacancy_id']) {
                $layer_id = $vacancy_room_info['layer_id'];
            }
            if ($layer_id) {
                $layer_num = M('House_village_layer')->where(['id' => $layer_id])->getField('layer_name');
            } else {
                $layer_num = $user['layer_num'];
            }

            $word_data = array();
            $single_id = $user['single_id'] ? $user['single_id'] : ($floor_info['single_id']?$floor_info['single_id']:0);
            if ($sing_arr[$single_id] && $single_id>0) {
                $sing_name = $sing_arr[$single_id];
            } else {
                $sing_name = '';
            }
            $word_data['layer'] = $layer_num;
            $word_data['room'] = $user['room_addrss'];
            if (!empty($floor_info)) {
                $word_data['floor_name'] = $floor_info['floor_name'];
                if (!$sing_name) {
                    $sing_name = $floor_info['floor_layer'];
                }
            }
            $word_data['single_name'] = $sing_name;
            $word_data['room'] = $vacancy_room_info['room'];
            $room_diy_name = D('House_village')->word_replce_msg($word_data,$village_id);
            $user_list[$Key]['address'] = $room_diy_name;


            $user_list[$Key]['attribute_name'] = $attribute_list[$user['attribute']] ? $attribute_list[$user['attribute']] : '' ;

            $property_month_time_arr = $database_house_village_pay_order->where(array('village_id'=>$village_id,'paid'=>1,'uid'=>$user['uid']))->field('(property_month_num+presented_property_month_num) as property_month_time')->select();
            $user_list[$Key]['openid'] = D('User')->where(array('uid'=>$user['uid']))->getField('openid');
            $property_month_time = 0;
            foreach($property_month_time_arr as $row){
                $property_month_time += $row['property_month_time'];
            }
            $user_list[$Key]['property_month_time'] = $user['add_time'] + $property_month_time * 30 * 24 * 3600;

            if ($getChild) {
                $condition['parent_id'] = $user['pigcms_id'];
                $bind_list = $this->where($condition)->select();
                if($bind_list){
                    $user_list[$Key]['bind_list'] = $bind_list;
                }
            }
            // 查询处理自定义缴费

            //自定义项欠费
            $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
                C('DB_PREFIX').'house_village_payment_standard'=>'ps',
                C('DB_PREFIX').'house_village_payment'=>'p'))
                ->where("psb.pigcms_id= '".$user['pigcms_id']."'AND psb.village_id =".$village_id." AND p.payment_id = psb.payment_id AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND ps.standard_id = psb.standard_id AND `psb`.`start_time` <".time())->select();
            $payment_list = $payment_list ? $payment_list : array();

            // 车位缴费
            $position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$user['pigcms_id']));
            $payment_list = array_merge($payment_list, $position_payment_list);
            $user_list[$Key]['cunstom_money'] = 0;
            if ($payment_list) {
                $cunstom_pay_arr = [];
                foreach ($payment_list as $key => $item) {
                    if ($item['cycle_sum'] - $item['paid_cycle'] <= 0 ) {
                        // 签订周期已缴完
                        $cunstom_list[$key]['payment_name'] = $item['payment_name'];
                        $cunstom_list[$key]['payment_money'] = 0;
                        $cunstom_arr[$item['payment_id']] = [
                            'payment_id' => $item['payment_id'],
                            'payment_name' => $item['payment_name']
                        ];
                        if (!$cunstom_pay_arr[$item['payment_id']] || !$cunstom_pay_arr[$item['payment_id']]['total_money']) {
                            $cunstom_pay_arr[$item['payment_id']]['total_money'] = 0;
                        }
                        $cunstom_pay_arr[$item['payment_id']] = [
                            'payment_id' => $item['payment_id'],
                            'payment_name' => $item['payment_name'],
                            'total_money' => $cunstom_pay_arr[$item['payment_id']]['total_money'] + $item['payment_money']
                        ];
                        continue;
                    }
                    // 已缴到期时间
                    switch ($value['cycle_type']) {
                        case 'Y':
                            $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*365;
                            break;
                        case 'M':
                            $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*30;
                            break;
                        case 'D':
                            $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400;
                            break;
                    }
//                    $end_time = strtotime(date('Y-m-d',$item['start_time']).'+'.$item['pay_cycle']*$item['paid_cycle'].$cycle_type[$item['cycle_type']]);
                    if ($end_time<time()) {
                        $num = $db_house_village_user_bind->getTimeNum($end_time,time(),$item['cycle_type']);
                        // 除以周期值 向上取整
                        $num = ceil($num/$item['pay_cycle']);
                        // 除以周期 取计算出来的周期和剩余周期取最小值
                        $num = min($num,$item['cycle_sum']);
                        if ($item['pay_type']==1) {
                            $item['payment_money'] = round($item['pay_money'] * $num,2);
                        }else{
                            $item['payment_money'] = round($item['pay_money'] * $num * $item['metering_mode_val'],2);
                        }
                        $user_list[$Key]['cunstom_money'] += $item['payment_money'];
                        $cunstom_arr[$item['payment_id']] = [
                            'payment_id' => $item['payment_id'],
                            'payment_name' => $item['payment_name']
                        ];
                        if (!$cunstom_pay_arr[$item['payment_id']] || !$cunstom_pay_arr[$item['payment_id']]['total_money']) {
                            $cunstom_pay_arr[$item['payment_id']]['total_money'] = 0;
                        }
                        $cunstom_pay_arr[$item['payment_id']] = [
                            'payment_id' => $item['payment_id'],
                            'payment_name' => $item['payment_name'],
                            'total_money' => $cunstom_pay_arr[$item['payment_id']]['total_money'] + $item['payment_money']
                        ];
                    }
                }
                $user_list[$Key]['cunstom_pay_arr'] = $cunstom_pay_arr;
                $user_list[$Key]['cunstom_money'] = round( $user_list[$Key]['cunstom_money'],2);
            }
        }

        if ($user_list) {
            $return['totalPage'] = ceil($count_user / $pageSize);
            $return['user_count'] = count($user_list);
            $return['count_user'] = $count_user;
            $return['pagebar'] = $p->show();
            $return['user_list'] = $user_list;
            $return['cunstom_arr'] = $cunstom_arr;
        }

        return $return;
    }

     /*得到小区下所有的业主列表*/
    public function get_limit_room_list_page($village_id, $pageSize = 20, $condition_where = array(),$type='')
    {
        if (!$village_id) {
            return null;
        }
        $return = array();
        $condition = '`f`.`village_id` = `b`.`village_id`  AND `f`.`floor_id` = `b`.`floor_id` AND b.village_id ='.$village_id;
        $condition_table  = array(C('DB_PREFIX').'house_village_floor'=>'f',C('DB_PREFIX').'house_village_user_bind'=>'b');

        // $condition_where['village_id'] = $village_id;
        if(!$type){//业主列表
            // $condition .= ' AND b.parent_id =0 AND b.type <> 4 AND b.status =1 ';
            $condition .= ' AND b.parent_id =0 AND b.type <> 4';
        }else{//所有住户列表
            // $condition .= ' AND b.status =1 AND b.type in (0,1,2)';
            $condition .= ' AND b.type in (0,1,2)';
        }
        if ($condition_where['other']) {
            $condition .= ' AND '.$condition_where['other'];
        }
        $condition .= ' AND status not in (3,4)';

        $field = 'b.*,f.floor_name,f.floor_layer';
        $order = ' `b`.`pigcms_id` DESC';

        $count_user = D('')->table($condition_table)->where($condition)->count();
        $p = new Page($count_user,$pageSize,'page');
        $user_list = D('')->field($field)->table($condition_table)->where($condition)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        foreach($user_list as &$user){
            $user['address'] = $user['floor_layer'] . $user['floor_name'] . $user['layer_num'] . '#' . $user['room_addrss'];
        }

        if ($user_list) {
            $return['totalPage'] = ceil($count_user / $pageSize);
            $return['user_count'] = count($user_list);
            $return['pagebar'] = $p->show();
            $return['user_list'] = $user_list;
        }

        return $return;
    }

    /*得到单个业主信息*/
    public function get_one($village_id, $value, $field = 'uid', $bind_uid = 0)
    {
        $condition_user['village_id'] = $village_id;
        $condition_user[$field] = $value;
        $now_user = $this->field(true)->where($condition_user)->find();
        if (!empty($now_user)) {
            $now_user['water_price'] = floatval($now_user['water_price']);
            $now_user['electric_price'] = floatval($now_user['electric_price']);
            $now_user['gas_price'] = floatval($now_user['gas_price']);
            $now_user['park_price'] = floatval($now_user['park_price']);
            $now_user['property_price'] = floatval($now_user['property_price']);
            if ($bind_uid) {
                $this->where(array('pigcms_id' => $now_user['pigcms_id']))->data(array('uid' => $bind_uid))->save();
            }
        }
        return $now_user;
    }

    /*得到单个业主信息*/
    public function get_one_by_bindId($pigcms_id)
    {
        $condition_user['pigcms_id'] = $pigcms_id;
        $now_user = $this->field(true)->where($condition_user)->find();
        $old_address  = $now_user['address'];
        if (!empty($now_user)) {
            $now_user['water_price'] = floatval($now_user['water_price']);
			$now_user['pigcms_id_find'] = $now_user['pigcms_id'];
            $now_user['electric_price'] = floatval($now_user['electric_price']);
            $now_user['gas_price'] = floatval($now_user['gas_price']);
            $now_user['park_price'] = floatval($now_user['park_price']);
            $now_user['property_price'] = floatval($now_user['property_price']);
            //算物业费
            $bind_info = M("House_village_user_vacancy")->where(['pigcms_id'=>$now_user['vacancy_id']])->find();
            $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$bind_info['pigcms_id'],'is_del'=>1])->field('standard_id')->find();
            if(!$standard_id['standard_id']){
                $price = 0;
            }else{
                $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                $price = $this->get_property_fee(unserialize($date['date_month']),strtotime(date('Y-m',time())));
            }
            $now_user['property_fee'] = $price;
            $now_user['house_type'] = M("House_village_user_vacancy")->where(['pigcms_id'=>$now_user['vacancy_id']])->getField('house_type');

            if($now_user['parent_id']){
                //重新修补一下用户身份 2020/1/8
				if($now_user['floor_id']){
					$floor_info = D('House_village_floor')->where(array('floor_id'=>$now_user['floor_id']))->find();
					$now_user['address'] = $floor_info['floor_layer'] . $floor_info['floor_name'] . $now_user['layer_num'] . '#' . $now_user['room_addrss'];
					if($old_address != $now_user['address']){
						$this->where($condition_user)->data(['address'=>$now_user['address']])->save();
					}
				}
				
				// 未来修复完成后，可以直接用家属表的地址就行
				// $address = $this->where(array('pigcms_id'=>$now_user['parent_id']))->getField('address');
                // $now_user['address'] = $address;
            }else{
				$old_address = $now_user['address'];
				if($now_user['floor_id']){
					$floor_info = D('House_village_floor')->where(array('floor_id'=>$now_user['floor_id']))->find();
					$now_user['address'] = $floor_info['floor_layer'] . $floor_info['floor_name'] . $now_user['layer_num'] . '#' . $now_user['room_addrss'];
					if($old_address != $now_user['address']){
						$this->where($condition_user)->data(['address'=>$now_user['address']])->save();
					}
				}
			}

        }
        $now_user['old_address'] = $old_address;
        if (!empty($now_user)) {
            $now_user['address'] = D('House_village')->get_single_floor_room($now_user['single_id'],$now_user['floor_id'],$now_user['layer_id'],$now_user['vacancy_id'],$now_user['village_id']);
        }
        return $now_user;
    }

    public function get_property_fee($chargingStandard,$month)
    {
        $price = 0;
        foreach ($chargingStandard as $value){
            if(strtotime($value['date_month']) <= $month){
                $price = $value['price'];
            }
        }
        return $price;
    }

    /*得到小区下所有的业主列表(绑定微信的)*/
    public function get_limit_list_open($village_id, $pageSize = 5,$where = array())
    {
        if (!$village_id) {
            return null;
        }

        $return = array();
        $condition_table = array(C('DB_PREFIX') . 'house_village_user_bind' => 'b', C('DB_PREFIX') . 'user' => 'u');
        $condition_where = "b.status=1 AND b.phone = u.phone AND u.openid !='' AND b.phone>0 AND b.village_id=" . $village_id;
        $condition_field = ' distinct(u.openid), b.uid,u.openid,u.nickname,b.type';
        // if($bigId !== 0){
        // $condition_where .= " AND b.pigcms_id<=".$bigId." AND b.pigcms_id>=".$smallId;
        // }
        if ($where['floor_id']) {
            $condition_where .= ' AND b.floor_id in ('.$where['floor_id'].')';
        }
        if ($where['send_user_type']) {
            // 小区下所有的业主
            switch ($where['send_user_type']) {
                case '1': // 仅业主
                    $condition_where .= ' AND b.type in (0,3)';
                    break;
                case '2': // 业主和工作人员
                    // 新增模板消息（消息发送状态提醒）
                    $condition_where .= ' AND b.type in (0,3,4)';
                    break;
                case '3': // 仅工作人员
                    $condition_where .= ' AND b.type = 4';
                    break;
            }
        }
        $count_user = D('')->table($condition_table)->where($condition_where)->count('distinct(u.openid)');

        import('@.ORG.merchant_page');
        $p = new Page($count_user, $pageSize, 'page');
        $user_list = D('')->table($condition_table)->field($condition_field)->where($condition_where)->group('u.openid,b.type')->order('`b`.`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        if ($user_list) {
            $return['totalPage'] = ceil($count_user / $pageSize);
            $return['user_count'] = count($user_list);
            $return['pagebar'] = $p->show();
            $return['user_list'] = $user_list;
        }

        return $return;
    }

    /*得到小区下所有欠费业主列表(绑定微信的)*/
    public function get_pay_list_open($village_id, $pageSize = 20, $bigId = 0, $smallId = 0)
    {
        if (!$village_id) {
            return null;
        }
        $return = array();

        $condition_table = array(C('DB_PREFIX') . 'house_village_user_paylist' => 'b', C('DB_PREFIX') . 'user' => 'u');
        $condition_where = " b.uid = u.uid 	AND  u.openid !='' AND b.uid>0 AND b.village_id=" . $village_id;
        $condition_field = ' u.openid,b.uid,b.address,b.usernum,b.bind_id';
        if ($bigId !== 0) {
            $condition_where .= " AND b.pigcms_id<=" . $bigId . " AND b.pigcms_id>" . $smallId;
        }
        $count_user_list = D('')->table($condition_table)->group('u.openid')->where($condition_where)->field('u.openid')->select();
        $count_user = count($count_user_list);

        import('@.ORG.merchant_page');
        $p = new Page($count_user, $pageSize, 'page');
        $user_list = D('')->table($condition_table)->group('u.openid')->field($condition_field)->where($condition_where)->order('`b`.`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        if ($user_list) {
            $d_house_village = D('House_village');
            foreach ($user_list as &$val) {
                $bind_list = $this->field(true)->where(array('pigcms_id' => $val['bind_id']))->field('single_id, floor_id, layer_id, vacancy_id, village_id,address')->find();
                $room_info = M('House_village_user_vacancy')->where(array('pigcms_id'=>$bind_list['vacancy_id']))->field('single_id, floor_id, layer_id, village_id,pigcms_id')->find();
                $val['address'] = $d_house_village->get_single_floor_room($room_info['single_id'],$room_info['floor_id'],$room_info['layer_id'],$room_info['pigcms_id'],$room_info['village_id']);
                if (!$val['address']) {
                    $val['address'] = '';
                }
            }
            $return['totalPage'] = ceil($count_user / $pageSize);
            $return['user_count'] = count($user_list);
            $return['pagebar'] = $p->show();
            $return['user_list'] = $user_list;
        }

        return $return;
    }

    /*绑定家属*/
    public function house_village_my_bind_family_add($data)
    {
        if (!$data) {
            return array('status' => 0, 'msg' => '传递参数有误！');
        }

        $where['uid'] = $data['uid'];
        $where['phone'] = $data['phone'];
        $where['parent_id'] = $data['parent_id'];
        $count = $this->where($where)->count();
        if ($count > 0) {
            return array('status' => 0, 'msg' => '该手机号已经绑定');
        }

        $insert_id = $this->data($data)->add();
        if ($insert_id) {
            $sms_data['uid'] = $data['uid'];
            $sms_data['mobile'] = $data['phone'];
            $sms_data['sendto'] = 'user';
            $sms_data['type'] = 'bind_family';
            $sms_data['content'] = '手机号：' . $_SESSION['user']['phone'] . '已成功将您绑定为其'.(C('config.open_house_user_name')?'亲友':'家属').'！';
            //Sms::sendSms($sms_data);

            return array('status' => 1, 'msg' => '绑定成功');
        } else {
            return array('status' => 0, 'msg' => '绑定失败');
        }
    }

    /*家属列表*/
    public function house_village_my_bind_family_list($where)
    {
        if (!$where) {
            return false;
        }
        if(!isset($where['status'])) {
            $where['status'] = ['not in',[3,4]];
        }
        $field = array('pigcms_id', 'name', 'phone');
        $list = $this->where($where)->field($field)->select();
        if ($list) {
            return $list;
        } else {
            return false;
        }
    }


    //获取被绑定家属信息
    public function get_village_family_list($where)
    {
        if (!$where) {
            return false;
        }
        if(!isset($where['status'])) {
            $where['status'] = ['not in',[3,4]];
        }
        $info = $this->where($where)->select();
        print_r($info);
        exit;
    }


    //获取单个业主信息
    public function house_village_user_bind_detail($where, $field = true)
    {
        if (!$where) {
            return false;
        }

        $info = $this->field($field)->where($where)->find();
        if ($info['floor_id']>0) {
            $floor_info = M('House_village_floor')->where(array('floor_id' => $info['floor_id']))->field('single_id,floor_name,floor_layer')->find();
        }
        $single_id = $info['single_id'];
        if ($floor_info['single_id']>0 && !$single_id) {
            $single_id = $floor_info['single_id'];
        }
        $single_name = M('House_village_single')->where(array('id' =>$single_id))->getField('single_name');
        $word_data['layer'] = $info['layer_num'];
        $word_data['room'] = $info['room_addrss'];
        $word_data['floor_name'] = $floor_info['floor_name'];
        if (!$single_name) {
            $single_name = $floor_info['floor_layer'];
        }
        $word_data['single_name'] = $single_name;
        if (!empty($word_data['single_name']) || !empty($word_data['floor_name']) || !empty($word_data['room']) || !empty($word_data['layer'])) {
            $room_diy_name = D('House_village')->word_replce_msg($word_data,$where['village_id']);
            $info['address'] = $room_diy_name;
        }

        if (!$info) {
            return array('status' => 0, 'info' => '没有查到相关业主');
        } else {
            return array('status' => 1, 'info' =>$info);
        }
    }
	
	//查询用户申请小区房间亲属/租客信息 - wangdong
	public function get_my_room_not_master($uid , $type , $room_str=""){
		
		$where = "`uid`=".$uid;
		if(!empty($room_str)) $where .= " AND vacancy_id not in ($room_str)";
		$where .= " AND type in ($type)";
        $where .= " AND status not in (3,4)";
		
		$lists = $this->field(true)->where($where)->order('village_id DESC,floor_id ASC,parent_id ASC')->select();
		
		return $lists;
	}
	
	//业主下面的亲戚/租客列表 - wangdong
	public function get_my_room_user($pigcms_id){
	
		$condition['vacancy_id'] = $pigcms_id;
		$condition['status'] = array('in' , "1,2");
		$condition['type'] = array('in' , '1,2');
		$lists = $this->field(true)->where($condition)->select();
		return $lists;
		
	}
	
	//小程序判断用户是否绑定当前小区 - wandong
	public function wxapp_getSelect($where){
        if(!isset($where['status'])) {
            $where['status'] = ['not in',[3,4]];
        }
		$list = $this->field(true)->where($where)->select();
		return $list;
			
	}

    //获取所有家属绑定信息
    public function get_all_child_list($where, $fields = true ,$order = 'ub.pigcms_id desc' , $pageSize = 20){
        if(!$where){
            return false;
        }
        if (!isset($where['ub.status'])) {
            $where['ub.status'] = ['not in', [3,4]];
        }

        /**是否绑定微信*is_bind_weixin 1是 2否****/
        $is_join_user=0;
        $_string=' ub.pigcms_id >0 ';
        if(isset($where['is_bind_weixin'])){
            $is_join_user=1;
            if($where['is_bind_weixin']==1){
                $_string .=' AND ((us.openid IS NOT NULL AND us.openid!="") OR (us.wxapp_openid IS NOT NULL AND us.wxapp_openid!=""))';
            }elseif ($where['is_bind_weixin']==2){
                $_string .=  ' AND (ub.uid<1 OR ((us.openid IS NULL OR us.openid="") AND (us.wxapp_openid IS NULL OR us.wxapp_openid="")))';
            }
            unset($where['is_bind_weixin']);
        }
        if(isset($where['source_comefrom'])){
            /**是否绑定微信*source_comefrom 1公众号 2小程序3小区自动注册4其他 5 H5****/
            $is_join_user=1;
            if($where['source_comefrom']==1){
                $_string .= ' AND (ub.uid>0 AND us.source = "weixin")';
            }elseif ($where['source_comefrom']==2){
                $_string .= ' AND (ub.uid>0 AND (us.source = "wxapp" OR us.source = "wxcapp"))';
            }elseif ($where['source_comefrom']==3){
                $_string .= ' AND (ub.uid>0 AND us.source = "houseautoreg" )';
            }elseif($where['source_comefrom']==5){
                $_string .= ' AND (ub.uid>0 AND us.source = "wap" )';
            }elseif ($where['source_comefrom']==4){
                $_string .= ' AND (ub.uid<1 OR (us.source not in ("weixin","wxapp","wxcapp","houseautoreg","wap")))';
            }
            unset($where['source_comefrom']);
        }
        if($is_join_user==1){
            $where['_string']=$_string;
        }
        if ($pageSize>0) {
            import('@.ORG.merchant_page');
            $count = $this->alias('ub')->where($where)->count();
            $p = new Page($count,$pageSize,'page');
        }
        $village_id=0;
        if(isset($where['village_id'])){
            $village_id=$where['village_id'];
        }elseif(isset($where['ub.village_id'])){
            $village_id=$where['ub.village_id'];
        }
        // 业主属性
        $attribute_list = D('House_village_user_attribute')->get_attribute($village_id,true);

        if ($pageSize>0) {
            if($is_join_user==1){
                $left_join = ' left join '.C('DB_PREFIX').'user as us on ub.uid=us.uid';
                $child_list = $this->alias('ub')->join($left_join)->field('ub.*,us.source as us_source,us.openid as us_openid,us.wxapp_openid as us_wxapp_openid')->where($where)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
                
            }else{
                $child_list = $this->alias('ub')->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
            }
        } else {
            if($is_join_user==1){
                $left_join = ' left join '.C('DB_PREFIX').'user as us on ub.uid=us.uid';
                $child_list = $this->alias('ub')->join($left_join)->field('ub.*,us.source as us_source,us.openid as us_openid,us.wxapp_openid as us_wxapp_openid')->where($where)->order($order)->select();
            }else{
                $child_list = $this->alias('ub')->where($where)->field($fields)->order($order)->select();
            }
            
        }
        $userDb=D('User');
        $sing_arr = M('House_village_single')->where(array('village_id' => $village_id))->getField('id,single_name');
        $roomIdArrs =[];
        $layerArrs =[];
        $floorArrs =[];
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        foreach($child_list as $Key=>$row){
            $row['add_time'] = date('Y-m-d H:i:s',$row['add_time']);
            //查询业主信息
            $parent_where = array('vacancy_id' => $row['vacancy_id'], 'type' => array('in', array(0, 3)), 'status' => ['not in', [3, 4]]);
            if ($row['parent_id'] > 0) {
                $parent_where['pigcms_id'] = $row['parent_id'];
            }
            $parent_info = $this->where($parent_where)->field('name,phone,floor_id,layer_num,room_addrss,single_id,property_starttime,property_endtime')->order('type ASC,pigcms_id desc')->find();
            if ($parent_info) {

                $row['parent_name'] = $parent_info && $parent_info['name'] ? $parent_info['name'] : '';
                $row['parent_phone'] = $parent_info && $parent_info['phone'] ? $parent_info['phone'] : '';
                $row['parent_starttime'] = $parent_info && $parent_info['property_starttime'] ? $parent_info['property_starttime'] : '';
                $row['parent_endtime'] = $parent_info && $parent_info['property_endtime'] ? $parent_info['property_endtime'] : '';
            } else {
                $parent_info = array();
            }

            $row['is_bind_weixin'] = '否';
            $row['source_comefrom'] = '其他';
            if(isset($row['us_source'])){
                if(!empty($row['us_openid']) || !empty($row['us_wxapp_openid'])){
                    $row['is_bind_weixin']='是';
                }
                if(!empty($row['us_source']) && ($row['us_source']=='weixin')){
                    $row['source_comefrom']='公众号';
                }else if(!empty($row['us_source']) && ($row['us_source']=='wxapp' || $row['us_source']=='wxcapp')){
                    $row['source_comefrom']='小程序';
                }elseif(!empty($row['us_source']) && ($row['us_source']=='houseautoreg')){
                    $row['source_comefrom']='小区自动注册';
                }elseif(!empty($row['us_source']) && ($row['us_source']=='wap')){
                    $row['source_comefrom']='H5';
                }
            }else if($row['uid']>0){
                $userTmp=$userDb->where(array('uid'=>$row['uid']))->field('source as us_source,openid as us_openid,wxapp_openid as us_wxapp_openid')->find();
                if($userTmp && (!empty($userTmp['us_openid']) || !empty($userTmp['us_wxapp_openid']))){
                    $row['is_bind_weixin']='是';
                }
                if($userTmp && !empty($userTmp['us_source']) && ($userTmp['us_source']=='weixin')){
                    $row['source_comefrom']='公众号';
                }else if($userTmp && !empty($userTmp['us_source']) && ($userTmp['us_source']=='wxapp' || $userTmp['us_source']=='wxcapp')){
                    $row['source_comefrom']='小程序';
                }elseif($userTmp && !empty($userTmp['us_source']) && ($userTmp['us_source']=='houseautoreg')){
                    $row['source_comefrom']='小区自动注册';
                }elseif($userTmp && !empty($userTmp['us_source']) && ($userTmp['us_source']=='wap')){
                    $row['source_comefrom']='H5';
                }
            }
            if ((empty($parent_info) || empty($parent_info['name']) || empty($parent_info['phone'])) && $row['vacancy_id']) {
                $room_info = M('House_village_user_vacancy')->where(array('pigcms_id'=>$row['vacancy_id'],'is_del'=>0))->field('name,phone')->order('status desc')->find();
                if ($room_info) {
                    $row['parent_name'] = $room_info && $room_info['name'] ? $room_info['name'] : '';
                    $row['parent_phone'] = $room_info && $room_info['phone'] ? $room_info['phone'] : '';
                }
            }
            if($parent_info['floor_id']){
                $floor_info = D('House_village_floor')->where(array('floor_id'=>$parent_info['floor_id']))->find();
            } else {
                $floor_info = D('House_village_floor')->where(array('floor_id'=>$row['floor_id']))->find();
            }
            $word_data = array();
            if ($sing_arr[$parent_info['single_id']] && $parent_info['single_id']>0) {
                $sing_name = $sing_arr[$parent_info['single_id']];
            } else {
                $village_floor = D('House_village_floor')->field('single_id')->where(array('floor_id'=>$row['floor_id']))->find();

                $signle = M('House_village_single')->field('single_name')->where(array('id' => $village_floor['single_id']))->find();
                $sing_name = $signle['single_name'];
            }
            if (!empty($floor_info)) {
                $word_data['floor_name'] = $floor_info['floor_name'];
                if($row['vacancy_id']){
                    $user_vacancy = D('house_village_user_vacancy')->field('layer_id')->where(array('pigcms_id'=>$row['vacancy_id']))->find();
                    if($user_vacancy['layer_id']){
                        $house_village_layer = M('House_village_layer')->field('layer_name')->where(array('id'=>$user_vacancy['layer_id']))->find();
                        $word_data['layer'] = $house_village_layer['layer_name'];
                    }
                }else{
                    $word_data['layer'] = $parent_info['layer_num'];
                }
                $word_data['room'] = $parent_info['room_addrss'];
                if (!$sing_name) {
                    $sing_name = $floor_info['floor_layer'];
                }
            } else {
                if($row['vacancy_id']>0){
                    $user_vacancy = D('house_village_user_vacancy')->where(array('pigcms_id'=>$row['vacancy_id']))->find();
                    if($user_vacancy['layer_id']){
                        $house_village_layer = M('House_village_layer')->where(array('id'=>$user_vacancy['layer_id']))->find();
                        $word_data['layer'] = $house_village_layer['layer_name'];
                    }
                }else{
                    $word_data['layer'] = $row['layer_num'];
                }
                $word_data['room'] = $row['room_addrss'];
            }
            if(is_numeric($word_data['layer'])){
                $word_data['layer']=$word_data['layer'].'层';
            }
            if(is_numeric($sing_name)){
                $sing_name=$sing_name.(D('House_village')->getHouseAlias('single_name',$village_id)?:'栋');
            }else{
                $lastchar=substr($sing_name,-1,1);
                if(is_numeric($lastchar)){
                    $sing_name=$sing_name.(D('House_village')->getHouseAlias('single_name',$village_id)?:'栋');
                }
            }
            if(is_numeric($word_data['floor_name'])){
                $word_data['floor_name']=$word_data['floor_name'].(D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元');
            }
            $word_data['single_name'] = $sing_name;
            $word_data['room'] = D("House_village_user_vacancy")->where(['pigcms_id'=>$row['vacancy_id']])->getField('room');
            $row['house_type'] = D("House_village_user_vacancy")->where(['pigcms_id'=>$row['vacancy_id']])->getField('house_type');
            $room_diy_name = D('House_village')->word_replce_msg($word_data,$village_id);
            $hasAddress = true;
            if (!$room_diy_name || trim($room_diy_name) == '无' && isset($row['third_type']) && trim($row['third_type']) == 'ezc_chinapopin' && $row['address']) {
                $hasAddress = false;
            }
            $hasAddress && $row['address'] = $room_diy_name;

            $row['attribute_name'] = $attribute_list[$row['attribute']] ? $attribute_list[$row['attribute']] : '' ;
            $child_list[$Key] = $row;
        }
        if ($pageSize>0) {
            $list['pageCount'] = ceil($count / $pageSize);
            $list['pagebar'] = $p->show();
        }
        $list['list'] = $child_list;
        if($list){
            return array('status'=>1,'result'=>$list);
        }else{
            return array('status'=>0,'result'=>$list);
        }
    }
	
    /**
     * [get_user_bind_info 获取绑定车位业主的信息]
     * @return [type] [description]
     */
	public function get_user_bind_info($field='',$join,$where){
        if(!isset($where['a.status'])) {
            $where['a.status'] = ['not in',[3,4]];
        }
        $data_list = D('House_village_user_bind')->alias('a')->field($field)->join($join)->where($where)->find();
        
        if($data_list){
            return $data_list;
        }else{
            return false;
        }
    }
	
    /**
     * [get_user_bind_select 查询绑定的业主信息]
     * @return [type] [description]
     */
    public function get_user_bind_select($field=true){
        $result = D('House_village_user_bind')->field($field)->select();
        if($result !== false){
            return $result;
        }else{
            return false;
        }
    }
	
    /**
     * [user_bind_save 修改绑定信息]
     * @return [type] [description]
     */
    public function user_bind_save($arr){
        $result = D('House_village_user_bind')->save($arr);
        if($result !== false){
            return $result;
        }else{
            return false;
        }
    }

    /**
     * [get_user_bind_count 获取数量]
     * @param  [type] $where [description]
     * @param  string $field [description]
     * @return [type]        [description]
     */
    public function get_user_bind_count($where,$field=''){
        if (isset($where['status'])) {
            $where['status'] = ['not in', [3,4]];
        }
        $count = D('House_village_user_bind')->field($field)->where($where)->count();
        return $count;
    }

    /**
     * [get_user_bind_sum 获取总和]
     * @param  string $field [description]
     * @return [type]        [description]
     */
    public function get_user_bind_sum($field='',$where=array()){
        if (isset($where['status'])) {
            $where['status'] = ['not in', [3,4]];
        }
        $pay_count = $this->where($where)->sum($field);
        $pay_count = $pay_count ? $pay_count : 0;
        return $pay_count;
    }

    /*得到未缴列表*/
    public function get_cashier_unpaid_list($column,$page=0,$pageSize=20){
        if(!$column['village_id']){
            return null;
        }
        // 水电燃气停车费 物业费
        $condition = '(water_price>0 OR electric_price>0 OR gas_price>0 OR park_price>0 OR ((property_endtime <> "" OR property_endtime <> null) AND property_endtime<"'.time().'")) AND parent_id=0 AND type in (0,3) AND village_id='.$column['village_id'];
        if ($column['pigcms_id']) {
            $condition .= ' AND pigcms_id in('.$column['pigcms_id'].')';
        } else {
            $condition .= ' AND status not in (3,4)';
        }

        if ($column['name']) {
            //$condition .= " AND `name` like '%".$column['name']."%'";
            $condition .= " AND REPLACE(`name`,' ','') like '%".str_replace(' ','',$column['name'])."%'";
        }   

        if ($column['phone']) {
            $condition .= " AND `phone` like '%".$column['phone']."%'";
        }  
        if ($column['usernum']) {
            $condition .= " AND `usernum` like '%".$column['usernum']."%'";
        }       

        if ($column['search']) {
            $condition .= ' AND (name like "%'.$column['search'].'%" OR phone like "%'.$column['search'].'%")';
        }
        $list = $this->field('pigcms_id')->where($condition)->select();
        $ids = array();
        foreach ($list as $key => $value) {
            $ids[] = $value['pigcms_id'];
        }
        if (empty($ids) && $column['pigcms_id']) {
            $ids[] = $column['pigcms_id'];
        }
        // $ids = array_values($list);
        // var_dump($this->_sql());

        // 自定义缴费
        $condition = '`psb`.`pigcms_id` = `b`.`pigcms_id`  AND `psb`.`standard_id` = `ps`.`standard_id` AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND `psb`.`start_time` <"'.time().'" AND psb.village_id ='.$column['village_id'];
        $condition_table  = array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',C('DB_PREFIX').'house_village_user_bind'=>'b',C('DB_PREFIX').'house_village_payment_standard'=>'ps');
        $field = '`psb`.*,`ps`.`cycle_type`,`ps`.`pay_cycle`,`b`.`pigcms_id`';

        if ($column['pigcms_id']) {
            $condition .= ' AND `b`.`pigcms_id` in('.$column['pigcms_id'].')';
        } else {
            $condition .= ' AND status not in (3,4)';
        }
        
        if ($column['name']) {
            //$condition .= " AND `b`.`name` like '%".$column['name']."%'";
            $condition .= " AND REPLACE(`b`.`name`,' ','') like '%".str_replace(' ','',$column['name'])."%'";
        }  

        if ($column['phone']) {
            $condition .= " AND `b`.`phone` like '%".$column['phone']."%'";
        }  
        if ($column['usernum']) {
            $condition .= " AND `b`.`usernum` like '%".$column['usernum']."%'";
        } 

        if ($column['search']) {
            $condition .= ' AND (b.name like "%'.$column['search'].'%" OR b.phone like "%'.$column['search'].'%")';
        }

        $list1 = D('')->field($field)->table($condition_table)->where($condition)->select();
        // fdump(D('')->getLastSql(),'htm',true);
        
        $list1 = $list1 ? $list1 : array();

        // 车位自定义缴费
        $condition = '`bp`.`user_id` = `b`.`pigcms_id`  AND `bp`.`position_id` = `psb`.`position_id`  AND `psb`.`standard_id` = `ps`.`standard_id` AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND `psb`.`start_time` <"'.time().'" AND psb.village_id ='.$column['village_id'];
        $condition_table  = array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',C('DB_PREFIX').'house_village_user_bind'=>'b',C('DB_PREFIX').'house_village_payment_standard'=>'ps',C('DB_PREFIX').'house_village_bind_position'=>'bp');
        $field = '`psb`.*,`ps`.`cycle_type`,`ps`.`pay_cycle`,`b`.`pigcms_id`';

        if ($column['pigcms_id']) {
            $condition .= ' AND `b`.`pigcms_id` in('.$column['pigcms_id'].')';
        } else {
            $condition .= ' AND status not in (3,4)';
        }

        if ($column['name']) {
            //$condition .= " AND `b`.`name` like '%".$column['name']."%'";
            $condition .= " AND REPLACE(`b`.`name`,' ','') like '%".str_replace(' ','',$column['name'])."%'";
        }   

        if ($column['phone']) {
            $condition .= " AND `b`.`phone` like '%".$column['phone']."%'";
        }  
        if ($column['usernum']) {
            $condition .= " AND `b`.`usernum` like '%".$column['usernum']."%'";
        } 

        if ($column['search']) {
            $condition .= ' AND (b.name like "%'.$column['search'].'%" OR b.phone like "%'.$column['search'].'%")';
        }

        $list2 = D('')->field($field)->table($condition_table)->where($condition)->select();
        $list2 = $list2 ? $list2 : array();

        $custom_list = array_merge($list1,$list2);

        $custom_ids = array();
        if ($custom_list) {
            foreach ($custom_list as $key => $value) {
                switch ($value['cycle_type']) {
                    case 'Y': // 年
                        // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                        $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*365;
                        break;
                    case 'M': //月
                        $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*30;
                        break;
                    case 'D': // 日
                        $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400;
                        break;
                }
                if ($end_time<time()) {
                    $custom_ids[] = $value['pigcms_id'];
                }
            }
        }

        //欠费业主id集
        $ids = array_merge($ids,$custom_ids);
        $condition = 'f.floor_id=b.floor_id AND b.pigcms_id in('.implode(',',$ids).') AND b.village_id='.$column['village_id'];

        if($column['is_bind_weixin'] == 1){
            $condition .= ' AND u.openid <> "" AND u.openid is not null';
        }elseif ($column['is_bind_weixin'] == 2) {
            $condition .= ' AND (u.openid = "" OR u.openid is null)';
        }
         
        $condition_table  = array(C('DB_PREFIX').'house_village_floor'=>'f',C('DB_PREFIX').'house_village_user_bind'=>'b');
        $field = 'b.*,f.floor_name,f.floor_layer,f.property_fee,f.single_id as floor_single_id,u.openid';
        $order = ' `b`.`pigcms_id` DESC';

        $left_join = ' left join '.C('DB_PREFIX').'user as u on u.uid=b.uid';

        if (!$pageSize) {
            $list = D('')->field($field)->table($condition_table)->join($left_join)->where($condition)->group('b.pigcms_id')->order($order)->select();
        }else{
            $count_info = D('')->table($condition_table)->where($condition)->group('b.pigcms_id')->field('b.pigcms_id')->select();
            $count_user = $count_info ? count($count_info) : 0;
            if ($page) {
                $list = D('')->field($field)->table($condition_table)->join($left_join)->where($condition)->group('b.pigcms_id')->order($order)->page($page,$pageSize)->select();
            }else{
				import('@.ORG.merchant_page');
                $p = new Page($count_user,$pageSize,'page');
                $list = D('')->field($field)->table($condition_table)->join($left_join)->where($condition)->group('b.pigcms_id')->order($order)->limit($p->firstRow.','.$p->listRows)->select();
            }
        }
        $water_money = 0;
        $electric_money = 0;
        $gas_money = 0;
        $park_money = 0;
        $property_money1 = 0;
        $cunstom_money = 0;
        if($list){
            $sing_arr = M('House_village_single')->where(array('village_id' => $column['village_id']))->getField('id,single_name');
            foreach ($list as $k=>$v){
                $list[$k]['address'] = D('House_village')->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
                $water_money += $v['water_price'];
                $electric_money += $v['electric_price'];
                $gas_money += $v['gas_price'];
                $park_money += $v['park_price'];
                // 计算欠缴物业费
                $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$v['vacancy_id'],'is_del'=>1])->field('standard_id')->find();
                if(!$standard_id['standard_id']){
                    $property_money = 0;
                }elseif ($v['property_endtime'] && $v['property_endtime'] < time()){
                    $dateMonth = array();
                    $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                    $num = $this->getMonthNum($v['property_endtime'] + 1, time());
                    if(date('d',time()) > date('d',$v['property_endtime'] + 1))
                        $num+=1;
                    $start_time = strtotime(date('Y-m',$v['property_endtime']));
                    $dateMonth[] = $start_time;
                    for ($i=1;$i<$num;$i++){
                        $dateMonth[] = strtotime("+ $i month",$start_time);
                    }
                    $property_money = $this->property_price($dateMonth,unserialize($date['date_month']),$v['housesize']);
                }else{
                    $property_money = 0;
                }
                $property_money1 += $property_money;
                $list[$k]['property_price'] = round(floatval($property_money),2);
                //自定义项欠费
                $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
                C('DB_PREFIX').'house_village_payment_standard'=>'ps',
                C('DB_PREFIX').'house_village_payment'=>'p'))
                ->where("psb.pigcms_id= '".$v['pigcms_id']."'AND psb.village_id =".$column['village_id']." AND p.payment_id = psb.payment_id AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND ps.standard_id = psb.standard_id AND `psb`.`start_time` <".time())->select();
                $payment_list = $payment_list ? $payment_list : array();

                 // 车位缴费
                $position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$v['pigcms_id']));
                $payment_list = array_merge($payment_list, $position_payment_list);
                $v['cunstom_money'] = 0;
                $cunstom_list = array();
                if ($payment_list) {
                    $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
                    foreach ($payment_list as $key => $value) {
                        if ($value['cycle_sum'] - $value['paid_cycle'] <= 0 ) {
                            // 签订周期已缴完
                            $cunstom_list[$key]['payment_name'] = $value['payment_name'];
                            $cunstom_list[$key]['payment_money'] = 0;
                            continue;
                        }

                        // 已缴到期时间
                        switch ($value['cycle_type']) {
                            case 'Y':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*365;
                                break;
                            case 'M':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*30;
                                break;
                            case 'D':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400;
                                break;
                        }
//                        $end_time = strtotime(date('Y-m-d',$value['start_time']).'+'.$value['pay_cycle']*$value['paid_cycle'].$cycle_type[$value['cycle_type']]);
                        if ($end_time<time()) {
                            $num = $this->getTimeNum($end_time,time(),$value['cycle_type']);
                            // 除以周期 向上取整
                            $num = ceil($num/$value['pay_cycle']);
                            // 除以周期 取计算出来的周期和剩余周期取最小值
                            $num = min($num,$value['cycle_sum']);
                            if ($value['pay_type']==1) {
                                $value['payment_money'] = round($value['pay_money'] * $num,2);
                            }else{
                                $value['payment_money'] = round($value['pay_money'] * $num * $value['metering_mode_val'],2);
                            }
                        }

                        $cunstom_list[$key]['payment_name'] = $value['payment_name'];
                        $cunstom_list[$key]['payment_money'] = $value['payment_money'];
                        $list[$k]['cunstom_money'] += $value['payment_money'];
                    }
                    $list[$k]['cunstom_money'] = round( $list[$k]['cunstom_money'],2);
                    $cunstom_money += $list[$k]['cunstom_money'];
                }
                $v['cunstom_list'] = $cunstom_list;

                $word_data = array();
                $single_id = $v['single_id'] ? $v['single_id'] : ($v['floor_single_id']?$v['floor_single_id']:0);
                if ($sing_arr[$single_id] && $single_id>0) {
                    $sing_name = $sing_arr[$single_id];
                } else {
                    $sing_name = '';
                }
                $word_data['layer'] = $v['layer_num'];
                $word_data['room'] = $v['room_addrss'];
                $word_data['floor_name'] = $v['floor_name'];
                if (!$sing_name) {
                    $sing_name = $v['floor_layer'];
                }
                $word_data['single_name'] = $sing_name;
                $room_diy_name = D('House_village')->word_replce_msg($word_data,$column['village_id']);
                $v['address'] = $room_diy_name;

                $list[$k]['total'] = round(floatval($v['water_price'])+floatval($v['electric_price'])+floatval($v['gas_price'])+floatval($v['park_price']) + $property_money + $list[$k]['cunstom_money'],2);
            }
        }
        $total_money = round(floatval($water_money)+floatval($electric_money)+floatval($gas_money)+floatval($park_money) + $property_money1 + $cunstom_money,2);
        $return['pagebar'] = $pageSize&&$p ? $p->show() : '';
        $return['list'] = $list;
        $return['total_money'] =  round(floatval($total_money),2);
        $return['water_money'] = round(floatval($water_money),2);
        $return['electric_money'] = round(floatval($electric_money),2);
        $return['gas_money'] = round(floatval($gas_money),2);
        $return['park_money'] = round(floatval($park_money),2);
        $return['cunstom_money'] = round(floatval($cunstom_money),2);
        $return['property_money'] = round(floatval($property_money1),2);
        $return['totalPage'] = ceil($count_user/$pageSize);
        $return['page'] = intval($page);
        $return['list_count'] = count($list);
        return $return;
    }

    //获得相差时间 $date2>$date1
    function getTimeNum($date1,$date2,$type){
        // var_dump(date('Y-m-d',$date1),date('Y-m-d',$date2));
        switch ($type) {
            case 'Y': //相差年份
                $date_1['y'] = date('Y',$date1);
                $date_2['y'] = date('Y',$date2);
                $num = $date_2['y']-$date_1['y'];
                if (strtotime(date('m-d',$date2))-strtotime(date('m-d',$date1))>=0) {
                    $num += 1;
                }
                # code...
                break;
            case 'M': //相差月份
                list($date_1['y'],$date_1['m'],$date_1['d']) = explode("-",date('Y-m-d',$date1));
                list($date_2['y'],$date_2['m'],$date_2['d']) = explode("-",date('Y-m-d',$date2));
                $num = ($date_2['y']-$date_1['y'])*12 +$date_2['m']-$date_1['m'];
                if ($date_2['d']- $date_1['d'] >= 0) { //多相差1天则加一个月
                    $num += 1;
                }
                break;
            case 'D': //相差天数
                $num = abs( ceil(($date2-$date1)/86400));
                break;
        }
        return $num;    
    }

    //发送缴费通知
    function send_weixin($href,$openid,$param,$property_id=0){
        if (floatval($param['total'])<=0) {
            return true;
        }
        $param['total'] = round_number($param['total'], 2);
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        if(!$property_id)
            $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $openid, 'first' => ' 尊敬的业主，您有新的账单！', 'keynote2' => $param['address'], 'keynote1' => $param['property_name'], 'remark' => '您的待缴总额为：['.C('config.Currency_symbol').$param['total'].']，点击缴费！'));
        else
            $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $openid, 'first' => ' 尊敬的业主，您有新的账单！', 'keynote2' => $param['address'], 'keynote1' => $param['property_name'], 'remark' => '您的待缴总额为：['.C('config.Currency_symbol').$param['total'].']，点击缴费！'),0,$property_id,2);
        //todo app 极光推送
        $uid=D('user')->where(['openid'=>$openid])->getField('uid');
        if($uid){
            D('House_village_repair_log')->send_app(['uid'=>$uid],' 尊敬的业主，您有新的账单！','您的待缴总额为：['.C('config.Currency_symbol').$param['total'].']，点击缴费！',$href);
        }

    }

    //发送缴费通知
    function send_weixin_pay($village_id,$user_list,$send_user_type=0){

        $now_village = D('House_village')->where(array('village_id'=>$village_id))->find();
        $now_property = D('House_property')->where(array('id'=>$now_village['property_id']))->find();
        $send_user_type = $send_user_type ? $send_user_type : $now_village['send_user_type']; // 发送类型

        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pageName' => 'payMentlist',
            'isAll' => true,
            'urlParam' => 'village_id='.$village_id,
        ];
        $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);

        $href_base = $payMentlistUrl;
        $property_name = $now_property['property_name'];
        if ($now_village['village_name']) {
            $property_name .= "【{$now_village['village_name']}】";
        }
        foreach ($user_list as $key => $value) {
            $param = array(
                'address' => $value['address'],
                'total' => $value['total'],
                'property_name' => $property_name,
            );
            if ($value['pigcms_id']) {
                $href = $href_base . '&pigcms_id='.$value['pigcms_id'];
            } else {
                $href = $href_base;
            }
            fdump_api($href,'href_send_weixin_pay',true);
            
            switch ($send_user_type) {
                case '1': // 只发送业主
                    if ($value['openid']) {
                        $this->send_weixin($href,$value['openid'],$param,$now_village['property_id']);
                    }
                    break;
                case '2': // 业主和家属
                    if ($value['openid']) {
                        $this->send_weixin($href,$value['openid'],$param,$now_village['property_id']);
                    }
                    // 查询家属
                    $condition = '`u`.`uid` = `b`.`uid` AND `b`.`status`=1 AND `b`.`type` in(1,2) AND b.vacancy_id ='.$value['vacancy_id'];
                    $condition_table  = array(C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'house_village_user_bind'=>'b');
                    $field = '`u`.`openid`';
                    $bind_list = D()->field($field)->table($condition_table)->where($condition)->select();
                    if ($bind_list) {
                        foreach ($bind_list as $bind) {
                            if(!empty($bind['openid'])){
                                $this->send_weixin($href,$bind['openid'],$param,$now_village['property_id']);
                            }
                        }
                    }
                    break;
                case '3':// 只发送家属
                    $condition = '`u`.`uid` = `b`.`uid` AND `b`.`status`=1 AND `b`.`type` in(1,2) AND b.vacancy_id ='.$value['vacancy_id'];
                    $condition_table  = array(C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'house_village_user_bind'=>'b');
                    $field = '`u`.`openid`';
                    $bind_list = D()->field($field)->table($condition_table)->where($condition)->select();
                    if ($bind_list) {
                        foreach ($bind_list as $bind) {
                            if(!empty($bind['openid'])){
                                $this->send_weixin($href,$bind['openid'],$param,$now_village['property_id']);
                            }
                        }
                    }
                    break;
            }
        }
    }

    //计算业主物业欠缴月份
    function owe_property_mouth($pigcms_id){
        $bind_info = $this->get_one_by_bindId($pigcms_id);
        $mouth = 0;
        // 物业时间没有设置
        if ( !$bind_info['property_starttime'] || !$bind_info['property_endtime']) {
            return $mouth;
        }

        // 结束时间大于当前时间
        if ($bind_info['property_endtime'] >= strtotime(date("Y-m-d"))) {
            return $mouth;
        }

        // 计算相差月份
        $mouth = $this->getTimeNum($bind_info['property_endtime'],time(),'M');
        return $mouth;
    }


    public function add_user_info($villlage_id = 0,$pigcms_id = 0,$uid = 0,$arr = [],$type = 1)
    {

           if($uid){
               $user_bind_info = M('House_village_user_bind_info')->where(array('village_id'=>$villlage_id,'uid'=>$uid,'pigcms_id'=>$pigcms_id))->find();
           }else{
               $user_bind_info = M('House_village_user_bind_info')->where(array('village_id'=>$villlage_id,'pigcms_id'=>$pigcms_id))->find();
           }
           if($user_bind_info){
               if($uid){
                   M('House_village_user_bind_info')->where(array('village_id'=>$villlage_id,'uid'=>$uid,'pigcms_id'=>$pigcms_id))->delete();
               }else{
                   M('House_village_user_bind_info')->where(array('village_id'=>$villlage_id,'pigcms_id'=>$pigcms_id))->delete();
               }

           }
           if($type == 1){//接口操作
               foreach($arr as $k=>$v){
                   $data = [];
                   $data['village_id'] = $villlage_id;
                   $data['uid'] = $uid?$uid:0;
                   $data['pigcms_id'] = $pigcms_id;

                   $data['title'] = $v['title'];
                   $data['key'] = $v['key'];
                   $data['type'] = $v['type'];
                   if($v['key'] == 'native_place'){
                       $city = explode("#",$v['value']);
                       $data['province_id'] = $city[0];
                       $data['city_id'] = $city[1];
                   }else{
                       $data['value'] = $v['value'];
                   }
                   M('House_village_user_bind_info')->data($data)->add();
               }
           }elseif($type == 2){
               //管理端操作

           }


    }

    /*得到未缴列表*/
    public function get_cashier_unpaid_list_new($column,$page=0,$pageSize=20)
    {
        //时区设置成中国
        date_default_timezone_set('PRC');
        if (!$column['village_id']) {
            return null;
        }
        // 水电燃气停车费 物业费
        $condition = '(water_price>0 OR electric_price>0 OR gas_price>0 OR park_price>0 OR ((property_endtime <> "" OR property_endtime <> null) AND property_endtime<"' . time() . '")) AND parent_id=0 AND type in (0,3) AND status=1 AND village_id=' . $column['village_id'];
        if ($column['pigcms_id']) {
            $condition .= ' AND pigcms_id in(' . $column['pigcms_id'] . ')';
        }
        if($column['single_id']){
            $condition .= ' AND `single_id`=' . $column['single_id'] ;
        }
        if($column['floor_id']){
            $condition .= ' AND `floor_id`=' . $column['floor_id'] ;
        }
        if($column['layer_id']){
            $condition .= ' AND `layer_id`=' . $column['layer_id'] ;
        }
        if($column['vacancy_id']){
            $condition .= ' AND `vacancy_id`=' . $column['vacancy_id'] ;
        }

        if ($column['name']) {
            //$condition .= " AND `name` like '%".$column['name']."%'";
            $condition .= " AND REPLACE(`name`,' ','') like '%" . str_replace(' ', '', $column['name']) . "%'";
        }

        if ($column['phone']) {
            $condition .= " AND `phone` like '%" . $column['phone'] . "%'";
        }
        if ($column['usernum']) {
            $condition .= " AND (`usernum` like '%" . $column['usernum'] . "%' OR `bind_number` like '%" . $column['usernum'] . "%')";
        }

        if ($column['search']) {
            $condition .= ' AND (name like "%' . $column['search'] . '%" OR phone like "%' . $column['search'] . '%")';
        }

        if(isset($column['status']) && $column['status']){
            $condition .= ' AND `status`=' . $column['status'] ;
        }
        $list = $this->field('pigcms_id')->where($condition)->select();
        $ids = array();
        foreach ($list as $key => $value) {
            $ids[] = $value['pigcms_id'];
        }
        if (empty($ids) && $column['pigcms_id']) {
            $ids[] = $column['pigcms_id'];
        }
        // $ids = array_values($list);
        // var_dump($this->_sql());

        // 自定义缴费
        $condition = '`psb`.`pigcms_id` = `b`.`pigcms_id`  AND `psb`.`standard_id` = `ps`.`standard_id` AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND `psb`.`start_time` <"' . time() . '" AND psb.village_id =' . $column['village_id'];
        $condition_table = array(C('DB_PREFIX') . 'house_village_payment_standard_bind' => 'psb', C('DB_PREFIX') . 'house_village_user_bind' => 'b', C('DB_PREFIX') . 'house_village_payment_standard' => 'ps');
        $field = '`psb`.*,`ps`.`cycle_type`,`ps`.`pay_cycle`,`b`.`pigcms_id`';

        if($column['single_id']){
            $condition .= ' AND `b`.`single_id`=' . $column['single_id'] ;
        }
        if($column['floor_id']){
            $condition .= ' AND `b`.`floor_id`=' . $column['floor_id'] ;
        }
        if($column['layer_id']){
            $condition .= ' AND `b`.`layer_id`=' . $column['layer_id'] ;
        }
        if($column['vacancy_id']){
            $condition .= ' AND `b`.`vacancy_id`=' . $column['vacancy_id'] ;
        }

        if ($column['pigcms_id']) {
            $condition .= ' AND `b`.`pigcms_id` in(' . $column['pigcms_id'] . ')';
        }

        if ($column['name']) {
            //$condition .= " AND `b`.`name` like '%".$column['name']."%'";
            $condition .= " AND REPLACE(`b`.`name`,' ','') like '%" . str_replace(' ', '', $column['name']) . "%'";
        }

        if ($column['phone']) {
            $condition .= " AND `b`.`phone` like '%" . $column['phone'] . "%'";
        }
        if ($column['usernum']) {
            $condition .= " AND (`b`.`usernum` like '%" . $column['usernum'] . "%' OR `b`.`bind_number` like '%" . $column['usernum'] . "%')";
        }

        if ($column['search']) {
            $condition .= ' AND (b.name like "%' . $column['search'] . '%" OR b.phone like "%' . $column['search'] . '%")';
        }

        if(isset($column['status']) && $column['status']){
            $condition .= ' AND `b`.`status`=' . $column['status'] ;
        }

        $list1 = D('')->field($field)->table($condition_table)->where($condition)->select();
        // fdump(D('')->getLastSql(),'htm',true);

        $list1 = $list1 ? $list1 : array();

        // 车位自定义缴费
        $condition = '`bp`.`user_id` = `b`.`pigcms_id`  AND `bp`.`position_id` = `psb`.`position_id`  AND `psb`.`standard_id` = `ps`.`standard_id` AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND `psb`.`start_time` <"' . time() . '" AND psb.village_id =' . $column['village_id'];
        $condition_table = array(C('DB_PREFIX') . 'house_village_payment_standard_bind' => 'psb', C('DB_PREFIX') . 'house_village_user_bind' => 'b', C('DB_PREFIX') . 'house_village_payment_standard' => 'ps', C('DB_PREFIX') . 'house_village_bind_position' => 'bp');
        $field = '`psb`.*,`ps`.`cycle_type`,`ps`.`pay_cycle`,`b`.`pigcms_id`';

        if ($column['pigcms_id']) {
            $condition .= ' AND `b`.`pigcms_id` in(' . $column['pigcms_id'] . ')';
        }

        if($column['single_id']){
            $condition .= ' AND `b`.`single_id`=' . $column['single_id'] ;
        }
        if($column['floor_id']){
            $condition .= ' AND `b`.`floor_id`=' . $column['floor_id'] ;
        }
        if($column['layer_id']){
            $condition .= ' AND `b`.`layer_id`=' . $column['layer_id'] ;
        }
        if($column['vacancy_id']){
            $condition .= ' AND `b`.`vacancy_id`=' . $column['vacancy_id'] ;
        }

        if ($column['name']) {
            //$condition .= " AND `b`.`name` like '%".$column['name']."%'";
            $condition .= " AND REPLACE(`b`.`name`,' ','') like '%" . str_replace(' ', '', $column['name']) . "%'";
        }

        if ($column['phone']) {
            $condition .= " AND `b`.`phone` like '%" . $column['phone'] . "%'";
        }
        if ($column['usernum']) {
            $condition .= " AND (`b`.`usernum` like '%" . $column['usernum'] . "%' OR `b`.`bind_number` like '%" . $column['usernum'] . "%')";
        }

        if ($column['search']) {
            $condition .= ' AND (b.name like "%' . $column['search'] . '%" OR b.phone like "%' . $column['search'] . '%")';
        }

        $list2 = D('')->field($field)->table($condition_table)->where($condition)->select();
        $list2 = $list2 ? $list2 : array();

        //$custom_list = array_merge($list1, $list2);
        $custom_list = $list1;

        $custom_ids = array();
        if ($custom_list) {
            foreach ($custom_list as $key => $value) {
                switch ($value['cycle_type']) {
                    case 'Y': // 年
                        // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                        $end_time = $value['start_time'] + $value['pay_cycle'] * $value['paid_cycle'] * 86400 * 365;
                        break;
                    case 'M': //月
                        $end_time = $value['start_time'] + $value['pay_cycle'] * $value['paid_cycle'] * 86400 * 30;
                        break;
                    case 'D': // 日
                        $end_time = $value['start_time'] + $value['pay_cycle'] * $value['paid_cycle'] * 86400;
                        break;
                }
                if ($end_time < time()) {
                    $custom_ids[] = $value['pigcms_id'];;
                }
            }
        }

        //欠费业主id集
        $ids = array_merge($ids, $custom_ids);
        $condition = 'f.floor_id=b.floor_id AND b.pigcms_id in(' . implode(',', $ids) . ') AND b.village_id=' . $column['village_id'];

        if ($column['is_bind_weixin'] == 1) {
            $condition .= ' AND u.openid <> "" AND u.openid is not null';
        } elseif ($column['is_bind_weixin'] == 2) {
            $condition .= ' AND (u.openid = "" OR u.openid is null)';
        }

        $condition_table = array(C('DB_PREFIX') . 'house_village_floor' => 'f', C('DB_PREFIX') . 'house_village_user_bind' => 'b');
        $field = 'b.*,f.floor_name,f.floor_layer,f.property_fee,f.single_id as floor_single_id,u.openid';
        $order = ' `b`.`pigcms_id` DESC';

        $left_join = ' left join ' . C('DB_PREFIX') . 'user as u on u.uid=b.uid';

        if (!$pageSize) {
            $list = D('')->field($field)->table($condition_table)->join($left_join)->where($condition)->order($order)->select();
        } else {
            $count_user = D('')->table($condition_table)->where($condition)->count();
            if ($page) {
                $list = D('')->field($field)->table($condition_table)->join($left_join)->where($condition)->order($order)->page($page, $pageSize)->select();
            } else {
                import('@.ORG.merchant_page');
                $p = new Page($count_user, $pageSize, 'page');
                $list = D('')->field($field)->table($condition_table)->join($left_join)->where($condition)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
            }
        }
        $total_money = 0;
        $all_money = 0;
        $water_money = 0;
        $electric_money = 0;
        $gas_money = 0;
        $park_money = 0;
        $property_money = 0;
        $cunstom_money = 0;
        $property_money_total = 0;
        if ($list) {
//            $now_village = D('House_village')->get_one($column['village_id']);
            $sing_arr = M('House_village_single')->where(array('village_id' => $column['village_id']))->getField('id,single_name');
            $cunstom_arr = [];
            $cycle_type = array('Y' => 'year', 'M' => 'month', 'D' => 'day');
            foreach ($list as $k=>&$v) {
                $water_money += $v['water_price'];
                $electric_money += $v['electric_price'];
                $gas_money += $v['gas_price'];
                $park_money += $v['park_price'];
                // 计算欠缴物业费
                $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$v['vacancy_id'],'is_del'=>1])->field('standard_id')->find();
                if(!$standard_id['standard_id']){
                    $property_money = 0;
                }elseif ($v['property_endtime'] && $v['property_endtime'] < time()){
                    $dateMonth = array();
                    $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                    $num = $this->getMonthNum($v['property_endtime'] + 1, time());
                    if(date('d',time()) > date('d',$v['property_endtime'] + 1))
                        $num+=1;
                    $start_time = strtotime(date('Y-m',$v['property_endtime']));
                    $dateMonth[] = $start_time;
                    for ($i=1;$i<$num;$i++){
                        $dateMonth[] = strtotime("+ $i month",$start_time);
                    }
                    $property_money = $this->property_price($dateMonth,unserialize($date['date_month']),$v['housesize']);
                }else{
                    $property_money = 0;
                }
                $property_money_total += $property_money;
                $v['property_price'] = round(floatval($property_money),2);
                //自定义项欠费
                $payment_list = D('')->table(array(C('DB_PREFIX') . 'house_village_payment_standard_bind' => 'psb',
                    C('DB_PREFIX') . 'house_village_payment_standard' => 'ps',
                    C('DB_PREFIX') . 'house_village_payment' => 'p'))
                    ->where("psb.pigcms_id= '" . $v['pigcms_id'] . "'AND psb.village_id =" . $column['village_id'] . " AND p.payment_id = psb.payment_id AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND ps.standard_id = psb.standard_id AND `psb`.`start_time` <" . time())->select();
                $payment_list = $payment_list ? $payment_list : array();

                // 车位缴费
                //$position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id' => $v['pigcms_id']));
                //$payment_list = array_merge($payment_list, $position_payment_list);
                $v['cunstom_money'] = 0;
                $cunstom_list = array();
                $cunstom_pay_arr = [];
                if ($payment_list) {
                    foreach ($payment_list as $key => $value) {
                        if ($value['cycle_sum'] - $value['paid_cycle'] <= 0) {
                            // 签订周期已缴完 金额均为0
                            $payment_money = 0;
                            $cunstom_list[$key]['payment_name'] = $value['payment_name'];
                            $cunstom_list[$key]['payment_money'] = $payment_money;
                            if (isset($cunstom_arr[$value['payment_id']]) && isset($cunstom_arr[$value['payment_id']]['total_money']) && $cunstom_arr[$value['payment_id']]['total_money']) {
                                $total_money = floatval($cunstom_arr[$value['payment_id']]['total_money']);
                            } else {
                                $total_money = 0;
                            }
                            $cunstom_arr[$value['payment_id']] = [
                                'payment_id' => $value['payment_id'],
                                'payment_name' => $value['payment_name'],
                                'total_money' => $total_money + $payment_money
                            ];
                            if (isset($cunstom_pay_arr[$value['payment_id']]) && isset($cunstom_pay_arr[$value['payment_id']]['total_money']) && $cunstom_pay_arr[$value['payment_id']]['total_money']) {
                                $pay_total_money = floatval($cunstom_pay_arr[$value['payment_id']]['total_money']);
                            } else {
                                $pay_total_money = 0;
                            }
                            $cunstom_pay_arr[$value['payment_id']] = [
                                'payment_id' => $value['payment_id'],
                                'payment_name' => $value['payment_name'],
                                'total_money' => $pay_total_money + $payment_money
                            ];
                            continue;
                        }

                        // 已缴到期时间
                        switch ($value['cycle_type']) {
                            case 'Y':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*365;
                                break;
                            case 'M':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*30;
                                break;
                            case 'D':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400;
                                break;
                        }
                        if ($end_time < time()) {
                            $num = $this->getTimeNum($end_time, time(), $value['cycle_type']);
                            // 除以周期 向上取整
                            $num = ceil($num / $value['pay_cycle']);
                            // 除以周期 取计算出来的周期和剩余周期取最小值
                            $num = min($num, $value['cycle_sum']);
                            if ($value['pay_type'] == 1) {
                                $value['payment_money'] = round($value['pay_money'] * $num, 2);
                            } else {
                                $value['payment_money'] = round($value['pay_money'] * $num * $value['metering_mode_val'], 2);
                            }
                        }

                        $cunstom_list[$key]['payment_name'] = $value['payment_name'];
                        $cunstom_list[$key]['payment_money'] = $value['payment_money'];
                        $v['cunstom_money'] += $value['payment_money'];
                        if (isset($cunstom_arr[$value['payment_id']]) && isset($cunstom_arr[$value['payment_id']]['total_money']) && $cunstom_arr[$value['payment_id']]['total_money']) {
                            $total_money = floatval($cunstom_arr[$value['payment_id']]['total_money']);
                        } else {
                            $total_money = 0;
                        }
                        $cunstom_arr[$value['payment_id']] = [
                            'payment_id' => $value['payment_id'],
                            'payment_name' => $value['payment_name'],
                            'total_money' => $total_money + $value['payment_money']
                        ];
                        if (isset($cunstom_pay_arr[$value['payment_id']]) && isset($cunstom_pay_arr[$value['payment_id']]['total_money']) && $cunstom_pay_arr[$value['payment_id']]['total_money']) {
                            $pay_total_money = floatval($cunstom_pay_arr[$value['payment_id']]['total_money']);
                        } else {
                            $pay_total_money = 0;
                        }
                        $cunstom_pay_arr[$value['payment_id']] = [
                            'payment_id' => $value['payment_id'],
                            'payment_name' => $value['payment_name'],
                            'total_money' => $pay_total_money + $value['payment_money']
                        ];
                    }
                    $v['cunstom_money'] = round($v['cunstom_money'], 2);
                    $cunstom_money += $v['cunstom_money'];
                }
                if (!empty($cunstom_pay_arr)){
                    foreach ($cunstom_pay_arr as &$cc){
                        $cc['total_money']=round_number($cc['total_money']);
                    }
                }
                $v['cunstom_list'] = $cunstom_list;
                $v['cunstom_pay_arr'] = $cunstom_pay_arr;



                $v['total'] = round(floatval($v['water_price']) + floatval($v['electric_price']) + floatval($v['gas_price']) + floatval($v['park_price']) + $v['property_price'] + $v['cunstom_money'], 2);
                $all_money += $v['total'];
                if($v['total'] <= 0){
                   // unset($list[$k]);
                }

                $word_data = array();
                $single_id = $v['single_id'] ? $v['single_id'] : ($v['floor_single_id'] ? $v['floor_single_id'] : 0);
                if ($sing_arr[$single_id] && $single_id > 0) {
                    $sing_name = $sing_arr[$single_id];
                } else {
                    $sing_name = '';
                }
                $word_data['layer'] = $v['layer_num'];
                $word_data['room'] = $v['room_addrss'];
                $word_data['floor_name'] = $v['floor_name'];
                if (!$sing_name) {
                    $sing_name = $v['floor_layer'];
                }
                $word_data['single_name'] = $sing_name;
                $room_diy_name = D('House_village')->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
                $v['address'] = $room_diy_name;


//                $v['address'] = $v['floor_name'].$v['floor_layer'].$v['layer_num'].$v['room_addrss'];
            }
        }
        $return['pagebar'] = $pageSize && $p ? $p->show() : '';
        $return['list'] = $list;
        $return['total_money'] = round(floatval($all_money), 2);
        $return['water_money'] = round(floatval($water_money), 2);
        $return['electric_money'] = round(floatval($electric_money), 2);
        $return['gas_money'] = round(floatval($gas_money), 2);
        $return['park_money'] = round(floatval($park_money), 2);
        $return['cunstom_money'] = round(floatval($cunstom_money), 2);
        $return['property_money'] = round(floatval($property_money_total), 2);
        $return['totalPage'] = ceil($count_user / $pageSize);
        $return['page'] = intval($page);
        $return['list_count'] = count($list);
        $return['cunstom_arr'] = $cunstom_arr;
        return $return;
    }
    /**
     * 获取两个日期相差几个月
     * @author lijie
     * @date_time 2020/07/27
     * @param $date1
     * @param $date2
     * @return float|int
     */
    function getMonthNum($date1,$date2){
        list($date_1['y'],$date_1['m'])=explode("-",date('Y-m',$date1));
        list($date_2['y'],$date_2['m'])=explode("-",date('Y-m',$date2));
        return abs($date_1['y']-$date_2['y'])*12 +$date_2['m']-$date_1['m'];
    }

    /**
     * 计算物业费
     * @author lijie
     * @date_time 2020/07/20 15:48
     * @param $dateMonths
     * @param $chargingStandard
     * @param $housesize
     * @return float|int
     */
    public function property_price($dateMonths,$chargingStandard,$housesize)
    {
        $property_price = 0;
        foreach ($dateMonths as $value){
            foreach ($chargingStandard as $v){
                if($value >= strtotime($v['date_month'])){
                    $price = $v['price'];
                }
            }
            if(isset($price)){
                $property_price += $price*$housesize;
            }
            unset($price);
        }
        return $property_price;
    }


    // 查询下业主和家属身份
    public function get_my_village_lists($word_val,$field='',$word='uid',$is_use=0) {
        $condition[$word] = $word_val;
        $condition['type'] = ['in',[0,3]];
        if($is_use==1){
            $condition['status'] = 1;
        }else{
            $condition['status'] = array('in',[0,1,2]);
        }
        if(empty($field)) {
            $field = true;
        }
        $lists = $this->field($field)->where($condition)->order('village_id DESC,floor_id ASC,pigcms_id ASC')->select();
        $owner_room_id_arr = $this->field($field)->where($condition)->getField('vacancy_id',true);
        // 查询到的业主-去除这些业主  再次查询用户在其他房间的家属身份
        $where_family = [];
        $where_family[$word] = $word_val;
        $where_family['type'] = 1;
        if($is_use==1) {
            $where_family['status'] = 1;
        }else{
            $where_family['status'] = array('in',[0,1,2]);
        }
        if ($owner_room_id_arr) {
            $where_family['vacancy_id'] = array('not in',$owner_room_id_arr);
        }
        if(empty($field)) {
            $field = true;
        }
        $family_lists = $this->field($field)->where($where_family)->order('village_id DESC,floor_id ASC,pigcms_id ASC')->select();
        $lists = array_merge($lists, $family_lists);
        return $lists;
    }


    //业主或者家属下面的亲戚/租客列表
    public function get_my_room_user_new($word_val, $field=true,$word='pigcms_id'){
        $where[$word] = $word_val;
        $info = $this->field($field)->where($where)->find();
        if (!$info) {
            return [];
        }
        $condition['parent_id'] = $info['pigcms_id'];
//        $condition['vacancy_id'] = $info['vacancy_id'];
        $condition['status'] = array('in' , "0,1,2");
        if (1==$info['type']) {
            $condition['type'] = 2;
        } else {
            $condition['type'] = array('in' , '1,2');
        }
        $lists = $this->field(true)->where($condition)->select();
        return $lists;

    }

    public function get_spread_list($where,$field='*',$pageSize=20)
    {
        if (!isset($where['status'])) {
            $where['status'] = ['not in', [3,4]];
        }
        $count_user = $this->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, $pageSize, 'page');
        $user_list = $this->field($field)->where($where)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if(!empty($user_list)){
            $d_house_village=D('House_village');
            $village_info = $d_house_village->field('*')->where(array('village_id'=>$user_list['0']['village_id']))->find();
            foreach($user_list as $Key=>$user){
                $count = M('User_spread')->where(['spread_uid'=>$user['uid']])->count();
                $user_list[$Key]['spread_count'] = $count;
                if(isset($user['phone']) && !empty($user['phone'])){
                    $user_list[$Key]['phone']=$d_house_village->phone_desensitization_v($user['phone'],$user['village_id'],$village_info);
                }
            }
        }else{
            $user_list=array();
        }

        $return['totalPage'] = ceil($count_user / $pageSize);
        $return['user_count'] = count($user_list);
        $return['count_user'] = $count_user;
        $return['pagebar'] = $p->show();
        $return['user_list'] = $user_list;
        return $return;
    }


    /**
     * Notes: 通过用户小区绑定id获取地址拼接
     * @param $pigcms_id
     * @return string
     * @author: wanzy
     * @date_time: 2021/1/26 17:39
     */
    public function getUserBindAddress($pigcms_id,$bind_info=[]) {
        if (!$pigcms_id) {
            return '';
        }
        if (empty($bind_info)) {
            $bind_info = $this->where(['pigcms_id'=>$pigcms_id])->field('village_id,single_id,floor_id,layer_id,vacancy_id')->find();
        }
        if ($bind_info['single_id'] && $bind_info['floor_id'] && $bind_info['layer_id'] && $bind_info['vacancy_id'] && $bind_info['village_id']) {
            $address = D('House_village')->get_single_floor_room($bind_info['single_id'], $bind_info['floor_id'], $bind_info['layer_id'],$bind_info['vacancy_id'], $bind_info['village_id']);
            if ($address) {
                return $address;
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * 通过数组pigcms_id 查询最新数据
     * @author: liukezhu
     * @date : 2022/1/20
     * @param array $pigcms_id
     * @return array
     */
    public function getUserBindData($pigcms_id=[]){
        if(empty($pigcms_id)){
            return ['error'=>false,'data'=>[]];
        }
        $id=end($pigcms_id);
        $user=$this->where(['pigcms_id'=>$id])->field('pigcms_id,name,phone')->find();
        if(!$user){
            return ['error'=>false,'data'=>[]];
        }
        return ['error'=>true,'data'=>$user];

    }

    public function getUserEndTime($pigcms_id, $isDelay=true, $param=[],$parentInfo=[],$parentField='pigcms_id,uid,phone,status,property_starttime,property_endtime')
    {
        $arr = [];
        if (!$param['pigcms_id'] || !$param['village_id'] || !$param['type'] || !$param['property_id'] || !$param['vacancy_id'] || !isset($param['property_endtime']) || !isset($param['owe_property_open_door']) || !isset($param['owe_property_open_door_day'])) {
            // 缺少关键字段的 重新查询一下
            $field = array(
                'a.pigcms_id','a.parent_id','a.type','a.village_id', 'a.vacancy_id', 'a.property_starttime','a.property_endtime',
                'a.phone','a.open_door_day', 'b.owe_property_open_door', 'b.owe_property_open_door_day','b.property_id'
            );
            $bindUser = $this->alias('a')->join('LEFT JOIN pigcms_house_village as b on b.village_id=a.village_id')
                ->group('a.pigcms_id')->field($field)->where(['a.pigcms_id' => $pigcms_id])->order('a.type DESC')
                ->find();
            if (empty($bindUser)) {
                // 如果查询不到用户直接返回空
                return $arr;
            }
            if(isset($bindUser['open_door_day']) && $bindUser['open_door_day']>0){
                $bindUser['owe_property_open_door_day']=$bindUser['open_door_day'];
            }
            $param['property_id'] = $bindUser['property_id'];
            $param['village_id'] = $bindUser['village_id'];
            $param['vacancy_id'] = $bindUser['vacancy_id'];
            $param['type'] = $bindUser['type'];
            $param['parent_id'] = $bindUser['parent_id'];
            $param['startTime'] = $bindUser['property_starttime'];
            $param['endTime'] = $bindUser['property_endtime'];
            $param['owe_property_open_door'] = $bindUser['owe_property_open_door'];
            $param['owe_property_open_door_day'] = $bindUser['owe_property_open_door_day'];
            $arr['bindUser'] = $bindUser;
        } else {
            $arr['bindUser'] = $param;
        }
        if (isset($param['type']) && 4==$param['type']) {
            // 工作人员无时间限制
            $arr['propertyEndTime'] = 0;
            $arr['propertyStartTime'] = 0;
            $arr['parentInfo'] = [];
            return $arr;
        }
        if (isset($param['type']) && isset($param['parent_id']) && $param['parent_id'] && in_array($param['type'],[1,2,5]) && empty($parentInfo)) {
            $parentInfo = $this->where(array('pigcms_id' => $param['parent_id']))->field($parentField)->find();
        }
        if (isset($param['property_id'])&&$param['property_id']) {
            // 存在物业id，查询下物业新版收费生效与否 确定物业服务时间截止
            $is_new_charge=D('House_village')->checkNewCharge($param['property_id']);
            if ($is_new_charge) {

                // 新版生效了  查询下
                $whereNewOrder = array('room_id'=>$param['vacancy_id'], 'order_type'=>'property');
                //查当前房间的服务结束时间
                $order_log = D('House_new_order_log')->where($whereNewOrder)->order('id DESC')->find();
                //查当前房间的服务开始时间
                $order_log_start = D('House_new_order_log')->where($whereNewOrder)->order('id ASC')->find();
                $contract_time=D('House_village')->getContractTime($param['vacancy_id']);
                if ($contract_time['contract_time_start']==0&&empty($order_log)){
                    $arr['propertyStartTime'] = 0;
                    $arr['propertyEndTime'] = 0;
                }elseif($contract_time['contract_time_start']==0&&!empty($order_log)){
                    $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                    $arr['propertyEndTime'] = $order_log['service_end_time'];
                }elseif($contract_time['contract_time_start']!=0&&empty($order_log)){
                    $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                    $arr['propertyEndTime'] = $contract_time['contract_time_end'];
                }elseif($contract_time['contract_time_start']!=0&&!empty($order_log)){
                    if ($contract_time['contract_time_start']<$order_log_start['service_start_time']){
                        $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                    }else{
                        $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                    }
                    $arr['propertyEndTime'] = $order_log['service_end_time'];
                }
                if($arr['propertyEndTime']<$arr['propertyStartTime']){
                    $arr['propertyEndTime']=$arr['propertyStartTime'];
                }
                if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && $param['owe_property_open_door_day'] && $param['owe_property_open_door']&& $arr['propertyEndTime']>0) {
                    $arr['propertyEndTime'] +=  intval($param['owe_property_open_door_day']) * 86400;
                } else if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && !$param['owe_property_open_door_day'] && $param['owe_property_open_door']&& $arr['propertyEndTime']>0) {
                    // 允许延期开门 且为0 默认不限制 2035
                    $arr['propertyEndTime'] =  2082729599;
                }
                /*
                // 新版生效了  查询下
                $whereNewOrder = array('room_id'=>$param['vacancy_id'], 'order_type'=>'property');
                //查当前房间的服务结束时间
                $order_log = D('House_new_order_log')->where($whereNewOrder)->order('id DESC')->find();
                //查当前房间的服务开始时间
                $order_log_start = D('House_new_order_log')->where($whereNewOrder)->order('id ASC')->find();
                $contract_time=D('House_village')->getContractTime($param['vacancy_id']);
                fdump_api([$order_log,$order_log_start,$contract_time],'getUserEndTime_0623',1);
                $arr['propertyStartTime']=0;
                $arr['propertyEndTime']=0;
                 if($contract_time && isset($contract_time['contract_time_start'])){
                    $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                    $arr['propertyEndTime'] = $contract_time['contract_time_end'];
                 }
              
                if($contract_time['contract_time_start']>0&&!empty($order_log)){
                    $arr['propertyEndTime'] = $order_log['service_end_time'];
                    if ($contract_time['contract_time_start']<$order_log_start['service_start_time']){
                        $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                        if($order_log['service_end_time']<$arr['propertyStartTime']){
                            $arr['propertyEndTime'] = $order_log_start['service_end_time'];
                        }
                    }else{
                        $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                        if($order_log['service_end_time']<$arr['propertyStartTime']){
                            $arr['propertyEndTime'] = $contract_time['contract_time_end'];
                        }
                    }
                }else if($order_log_start && isset($order_log_start['service_start_time']) && ($order_log_start['service_start_time']>100)){
                    $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                    $arr['propertyEndTime'] = $order_log_start['service_end_time'];
                    if($order_log && isset($order_log['service_end_time']) && ($order_log['service_end_time']>$arr['propertyEndTime']) && ($order_log['service_end_time']>$arr['propertyEndTime'])){
                        $arr['propertyEndTime'] = $order_log['service_end_time'];
                    }
                }
                if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && $param['owe_property_open_door_day'] && $param['owe_property_open_door']&& $arr['propertyEndTime']>0) {
                    $arr['propertyEndTime'] +=  intval($param['owe_property_open_door_day']) * 86400;
                } else if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && !$param['owe_property_open_door_day'] && $param['owe_property_open_door']) {
                    // 允许延期开门 且为0 默认不限制 2035
                    $arr['propertyEndTime'] =  2082729599;
                }
                
                $arr['parentInfo'] = $parentInfo?$parentInfo:[];
                fdump_api([$arr],'getUserEndTime_0623',1);
                */
                return $arr;
            }
        }

        if (isset($param['type']) && isset($param['parent_id']) && $param['parent_id'] && in_array($param['type'],[1,2,5])) {
            if (!$parentInfo) $parentInfo = [];
            $arr['parentInfo'] = $parentInfo;
            if ($parentInfo) {
                // 0标识 无限制
                $arr['propertyEndTime'] = isset($parentInfo['property_endtime'])&&$parentInfo['property_endtime']?$parentInfo['property_endtime']:0;
                $arr['propertyStartTime'] = isset($parentInfo['property_starttime'])&&$parentInfo['property_starttime']?$parentInfo['property_starttime']:0;
            }
            $bind_id = $param['parent_id'];
        } else {
            $bind_id = $pigcms_id;
            if (isset($param['startTime'])) {
                $arr['propertyStartTime'] = isset($param['startTime'])&&$param['startTime']?$param['startTime']:0;
            }
            if (isset($param['endTime'])) {
                $arr['propertyEndTime'] = isset($param['endTime'])&&$param['endTime']?$param['endTime']:0;
            }
        }
        // 计算 当前时间到物业截止时间的天数
        if (isset($arr['propertyEndTime']) && $arr['propertyEndTime']>0) {
            $end_time = D('House_village_property_paylist')->where(array('bind_id'=>$bind_id))->order('add_time desc')->getField('end_time');
            if ($end_time>$arr['propertyEndTime']) {
                $arr['propertyEndTime'] = $end_time;
            }
        }
        if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && $param['owe_property_open_door_day'] && $param['owe_property_open_door']&& $arr['propertyEndTime']>0) {
            $arr['propertyEndTime'] +=  intval($param['owe_property_open_door_day']) * 86400;
        } else if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && !$param['owe_property_open_door_day'] && $param['owe_property_open_door']&& $arr['propertyEndTime']>0) {
            // 允许延期开门 且为0 默认不限制 2035
            $arr['propertyEndTime'] =  2082729599;
        }
        if (!isset($arr['parentInfo']) || !$arr['parentInfo']) {
            $arr['parentInfo'] = [];
        }
        if (!isset($arr['propertyEndTime'])) {
            $arr['propertyEndTime'] = 0;
        }
        if (!isset($arr['propertyStartTime'])) {
            $arr['propertyStartTime'] = 0;
        }
        return $arr;
    }
    public function getPropertyTime($pigcms_id, $param=[])
    {
        $arr = [];
        if (!$param['pigcms_id'] || !$param['village_id'] || !$param['type'] || !$param['property_id'] || !$param['vacancy_id'] || !isset($param['property_endtime']) || !isset($param['owe_property_open_door']) || !isset($param['owe_property_open_door_day'])) {
            // 缺少关键字段的 重新查询一下
            $field = array(
                'a.pigcms_id','a.parent_id','a.type','a.village_id', 'a.vacancy_id', 'a.property_starttime','a.property_endtime',
                'a.phone','a.open_door_day', 'b.owe_property_open_door', 'b.owe_property_open_door_day','b.property_id'
            );
            $bindUser = $this->alias('a')->join('LEFT JOIN pigcms_house_village as b on b.village_id=a.village_id')
                ->group('a.pigcms_id')->field($field)->where(['a.pigcms_id' => $pigcms_id])->order('a.type DESC')
                ->find();
            if (empty($bindUser)) {
                // 如果查询不到用户直接返回空
                return $arr;
            }
            if(isset($bindUser['open_door_day']) && $bindUser['open_door_day']>0){
                $bindUser['owe_property_open_door_day']=$bindUser['open_door_day'];
            }
            $param['property_id'] = $bindUser['property_id'];
            $param['village_id'] = $bindUser['village_id'];
            $param['vacancy_id'] = $bindUser['vacancy_id'];
            $param['type'] = $bindUser['type'];
            $param['parent_id'] = $bindUser['parent_id'];
            $param['startTime'] = $bindUser['property_starttime'];
            $param['endTime'] = $bindUser['property_endtime'];
            $param['owe_property_open_door'] = $bindUser['owe_property_open_door'];
            $param['owe_property_open_door_day'] = $bindUser['owe_property_open_door_day'];
            $arr['bindUser'] = $bindUser;
        } else {
            $arr['bindUser'] = $param;
        }
        if (isset($param['type']) && 4==$param['type']) {
            // 工作人员无时间限制
            $arr['propertyEndTime'] = 0;
            $arr['propertyStartTime'] = 0;
            $arr['parentInfo'] = [];
            return $arr;
        }
        if (isset($param['property_id'])&&$param['property_id']) {
            $contract_time_start = $contract_time_end = 0;
            // 小区合同时间 -> 楼栋合同时间 -> 房间合同时间 ，都有合同起始时间才展示缴费时间，否则按照展示合同时间（房间>楼栋>小区）.如果有账单操作服务时间，以账单操作服务时间为准
            //查房间的合同时间
            $vacancy_time=D('House_village_user_vacancy')->field('village_id,single_id,contract_time_start,contract_time_end')->where(array('pigcms_id' => $param['vacancy_id']))->find();
            //楼栋的合同时间
            $single_time=D('House_village_single')->field('contract_time_start,contract_time_end')->where(array('id' => $vacancy_time['single_id']))->find();
            //小区的合同时间
            $village_time=D('House_village_info')->field('contract_time_start,contract_time_end')->where(array('village_id' => $vacancy_time['village_id']))->find();
            
            $whereNewOrder = array('room_id'=>$param['vacancy_id'], 'order_type'=>'property');
            //查当前房间的服务结束时间
            $order_log = D('House_new_order_log')->where($whereNewOrder)->order('id DESC')->find();
            //查当前房间的服务开始时间
            $order_log_start = D('House_new_order_log')->where($whereNewOrder)->order('id ASC')->find();
            
            if ($vacancy_time && $single_time && $village_time && $vacancy_time['contract_time_start']>1 && $single_time['contract_time_start']>1 && $village_time['contract_time_start']>1){
                $contract_time_start= isset($param['property_starttime']) ? $param['property_starttime']:0;
                $contract_time_end=isset($param['property_endtime'])? $param['property_endtime']:0;
                if(($contract_time_start < $vacancy_time['contract_time_start']) || ($contract_time_end > $vacancy_time['contract_time_end'])){
                    $contract_time_start = $vacancy_time['contract_time_start'];
                    $contract_time_end = $vacancy_time['contract_time_end'];
                }
            }else{
                if (!empty($vacancy_time) && $vacancy_time['contract_time_start']>1){
                    $contract_time_start=$vacancy_time['contract_time_start'];
                    $contract_time_end=$vacancy_time['contract_time_end'];
                }elseif (!empty($single_time) && $single_time['contract_time_start']>1){
                    $contract_time_start=$single_time['contract_time_start'];
                    $contract_time_end=$single_time['contract_time_end'];
                }elseif (!empty($village_time) && $village_time['contract_time_start']>1){
                    $contract_time_start=$village_time['contract_time_start'];
                    $contract_time_end=$village_time['contract_time_end'];
                }
            }
            if ($contract_time_start==0&&empty($order_log)){
                $arr['propertyStartTime'] = 0;
                $arr['propertyEndTime'] = 0;
            }elseif($contract_time_start==0&&!empty($order_log)){
                $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                $arr['propertyEndTime'] = $order_log['service_end_time'];
            }elseif($contract_time_start!=0&&empty($order_log)){
                $arr['propertyStartTime'] = $contract_time_start;
                $arr['propertyEndTime'] = $contract_time_end;
            }elseif($contract_time_start!=0&&!empty($order_log)){
                if ($contract_time_start<$order_log_start['service_start_time']){
                    $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                }else{
                    $arr['propertyStartTime'] = $contract_time_start;
                }
                $arr['propertyEndTime'] = $order_log['service_end_time'];
            }
            if($arr['propertyEndTime']<$arr['propertyStartTime']){
                $arr['propertyStartTime']=$arr['propertyEndTime'];
            }
            return $arr;
        }
    }

    //todo 校验性别、出生年月日是否有数据
    public function check_id_card($info){
        $is_edit_attribute=false;
        if((isset($info['authentication_field']['sex']['value']) && !empty($info['authentication_field']['sex']['value'])) && (isset($info['authentication_field']['birthday']['value']) && !empty($info['authentication_field']['birthday']['value']))){
            $is_edit_attribute=true;
        }
        return $is_edit_attribute;
    }

    //todo 身份证号解析返数据
    public function id_card_data($id_card,$dataList){
        if(empty($dataList)){
            return ['error'=>false,'data'=>[]];
        }
        $idCard=idCardAnalysis($id_card);
        if($idCard == false){
            return ['error'=>false,'data'=>[]];
        }
        $data=[];
        foreach ($dataList as $v){
            $value=$v['value'];
            if($v['key'] == 'birthday'){
                $value=$idCard['birthday'];
            }elseif ($v['key'] == 'sex'){
                $value=$idCard['sex'];
            }
            $data[$v['key']]=[
                'type'=>$v['type'],
                'is_must'=>$v['is_must'],
                'key'=>$v['key'],
                'title'=>$v['title'],
                'value'=>$value
            ];
        }
        return ['error'=>true,'data'=>serialize($data)];
    }


    //删除业主方法 迁移 从控制器到model
    public function user_bind_del($pigcms_id){
        $database_house_village_user_bind = D('House_village_user_bind');
        $bind_condition['pigcms_id'] = $pigcms_id;
        $bind_condition['type'] = ['in', [0,3]];
        $now_bind_info = $database_house_village_user_bind->where($bind_condition)->find();
        if(!$now_bind_info){
            return ['error'=>false,'msg'=>'该信息不存在！'];
        }
        fdump_api(['删除业主'.__LINE__,$pigcms_id,$now_bind_info],'house_user_bind/user_bind_delete_log',1);
        /**
         * todo 软删除 修改类型 usernum拼上时间戳
         */
        $insert_id = $database_house_village_user_bind->where($bind_condition)->data(['usernum'=>$now_bind_info['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();
        $m_house_face_device = M('House_face_device');
        $condition_face_device['village_id'] = $now_bind_info['village_id'];
        $condition_face_device['is_del'] = 0;
        $aDoor_count = $m_house_face_device->field(true)->where($condition_face_device)->count();
        fdump_api(['删除时查询设备==='.__LINE__,$aDoor_count],'house_user_bind/user_bind_delete_log',1);
        $third_robot_recognition_open = C('config.third_robot_recognition_open');
        if (intval($aDoor_count) > 0 || $third_robot_recognition_open) {
            // 删除  同步进行删除
            try {
                D('House_village_face_door')->del_a3_user_bind($pigcms_id, $now_bind_info['village_id'],$now_bind_info);
            }catch (Exception $e) {
                fdump_api(['删除时同步设备出错==='.__LINE__,$pigcms_id,$now_bind_info,$e->getMessage()],'house_user_bind/del_a3_user_bind_log_err',1);
            }
            $where = array('village_id' => $now_bind_info['village_id'],'status'=>1, 'vacancy_id' => $now_bind_info['vacancy_id'], 'type' => array('in', '0, 3'));
            // 查询一下是否存在其他状态的业主信息，全部删除
            $other = $database_house_village_user_bind->where($where)->select();
            if (!empty($other)) {
                foreach ($other as $val) {

                    /**
                     * todo 软删除 修改类型 usernum拼上时间戳
                     */
                    $database_house_village_user_bind->where(array('pigcms_id' => $val['pigcms_id']))->data(['usernum'=>$val['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();
                    fdump_api(['查询一下是否存在其他状态的业主信息，全部删除=='.__LINE__,$val,$database_house_village_user_bind->_sql(),$database_house_village_user_bind->getDbError()],'house_user_bind/user_bind_delete_log',1);
                    try {
                        D('House_village_face_door')->del_a3_user_bind($val['pigcms_id'], $val['village_id'], $val);
                    }catch (Exception $e) {
                        fdump_api(['删除时同步设备出错=='.__LINE__,$val,$e->getMessage()],'house_user_bind/user_bind_delete_log_err',1);
                    }
                }
            }
        }
        if($insert_id){
            $hik_info_vision_i_community_app_key=C('config.hik_info_vision_i_community_app_key');
            $hik_info_vision_i_community_app_secret=C('config.hik_info_vision_i_community_app_secret');
            if($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret){
                $hikRet=invoke_v20_service('\app\community\model\service\Device\HikInfoVisionICommunityService/deleteBindUserOfHikCommunity', ['village_id'=>$now_bind_info['village_id'],'pigcms_id'=>$now_bind_info['pigcms_id']]);
            }
            $family_condition['village_id'] = $now_bind_info['village_id'];
            $family_condition['parent_id'] = $now_bind_info['pigcms_id'];
            $family_condition['status'] = ['neq', 4];
            $user_del =  $database_house_village_user_bind->where($family_condition)->select();
            if (!empty($user_del)) {
                fdump_api(['删除家属=='.__LINE__,$user_del],'house_user_bind/user_bind_delete_log',1);
                /**
                 * todo 软删除 修改类型 usernum拼上时间戳
                 */
                $database_house_village_user_bind->where($family_condition)->data(['usernum'=>$now_bind_info['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();
                foreach ($user_del as $val1) {
                    try {
                        if($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret){
                            $hikRet=invoke_v20_service('\app\community\model\service\Device\HikInfoVisionICommunityService/deleteBindUserOfHikCommunity', ['village_id'=>$now_bind_info['village_id'],'pigcms_id'=>$val1['pigcms_id']]);
                        }
                        D('House_village_face_door')->del_a3_user_bind($val1['pigcms_id'], $val1['village_id'], $val1);
                    }catch (Exception $e) {
                        fdump_api(['删除时同步设备出错=='.__LINE__,$val1,$e->getMessage()],'house_user_bind/user_bind_delete_log_err',1);
                    }
                }
            }

            // 同步通过 解绑申请
            $database_house_village_user_unbind = D('House_village_user_unbind');
            $info_where = [];
            $info_where['bind_id'] = $pigcms_id;
            $info_where['status'] = ['neq', 3];
            $info_where['type'] = ['in', [0,3]];
            $info_where['village_id'] = $this->village_id;
            $set_data = [];
            $set_data['status'] = 3;
            $set_data['edittime'] = time();
            $set_data['reason'] = '对应业主删除自动同步对应解绑';
            $database_house_village_user_unbind->where($info_where)->save($set_data);

            //清空房间
            $database_house_village_user_vacancy = D('House_village_user_vacancy');
            $data['uid'] = 0;
            $data['status'] = 1;
            $data['name'] = "";
            $data['phone'] = "";
            $data['type'] = 0;
            $data['park_flag'] = 0;
            $database_house_village_user_vacancy->where(array('pigcms_id'=>$now_bind_info['vacancy_id'],'village_id'=>$now_bind_info['village_id']))->data($data)->save();
            fdump_api(['删除家属=='.__LINE__,$now_bind_info],'house_user_bind/shequ_user_delete_log',1);
            return ['error'=>true,'msg'=>'删除成功！'];
        }else{
            return ['error'=>false,'msg'=>'删除失败！'];
        }
    }

    /**
     * 审核通过后走下这个方法，自行匹配下企微。
     */
    public function checkUserPass($pigcms_id, $uid = 0, $village_id = 0, $property_id = 0) {
        $params = [
            'pigcms_id' => $pigcms_id, 'uid' => $uid, 'village_id' => $village_id, 'property_id' => $property_id,
        ];
        $result = invoke_v20_service('\app\community\model\service\workweixin\WorkWeiXinNewService/checkUserPassJob', $params);
        $resultVal = isset($result['data']['retval']) && $result['data']['retval'] ? $result['data']['retval'] : [];
        return $resultVal;
    }

    public function get_house_village_user_vacancys($where, $fields = '*' ,$order = 'pigcms_id desc' , $pageSize = 20){
        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count,$pageSize,'page');

        $bind_list = $this->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->order($order)->select();
        $database_house_village_floor = D('House_village_floor');
        $database_house_village_floor_type = D('House_village_floor_type');

        $village_id = $where['village_id'];
        $floor_list = $database_house_village_floor->where(array('village_id'=>$village_id,'status'=>1))->getField('floor_id,floor_name,floor_layer,floor_type');
        $floor_type_list = $database_house_village_floor_type->where(array('village_id'=>$village_id,'status'=>1))->getField('id,name');
        $layer = M('House_village_layer')->where(array('village_id'=>$village_id))->getField('id,layer_name');
        foreach($bind_list as $Key=>$row){
            $row['floor_name'] = $floor_list[$row['floor_id']]['floor_name'];
            $row['floor_layer'] = $layer[$row['id']]['layer_name'];
            $row['floor_type_name'] = $floor_type_list[$floor_list[$row['floor_id']]['floor_type']];
            $row['room_addrss'] = $row['room_addrss'];
            $row['address'] = D('House_village')->get_single_floor_room($row['single_id'],$row['floor_id'],$row['layer_id'],$row['vacancy_id'],$row['village_id']);
            $bind_list[$Key] = $row;
        }

        $return['totalPage'] = ceil($count / $pageSize);
        $return['user_list'] = $bind_list;
        $return['pagebar'] = $p->show();

        return $return;
    }
}
