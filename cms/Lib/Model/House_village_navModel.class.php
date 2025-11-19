<?php
class House_village_navModel extends Model{
    public function house_village_nav_add($data,$block_type_id=''){
        if(!$data){
            return false;
        }

        if(empty($data['name'])){
            return array('status'=>0,'msg'=>'名称不能为空！');
        }

        if($block_type_id!='business_classify_info' && $block_type_id!='business_store_info'  && empty($data['url'])){
            return array('status'=>0,'msg'=>'url不能为空！');
        }

        if($block_type_id!='business_classify_info' && empty($data['img'])){
            return array('status'=>0,'msg'=>'图片不能为空！');
        }

        if($this->data($data)->add()){
            return array('status'=>1,'msg'=>'添加成功！');
        }else{
            return array('status'=>0,'msg'=>'添加失败！');
        }
    }


    public function house_village_nav_page_list($where , $fields = true ,$order = 'id desc' , $pageSize = 20,$group='',$page_type=0){
        if(!$where){
            return false;
        }

        if($page_type == 0){
            import('@.ORG.merchant_page');
        }else{
            import('@.ORG.system_page');
        }

        if (!empty($group)){
            $count = $this->where($where)->group($group)->count();
        }else{
            $count = $this->where($where)->count();
        }

        $p = new Page($count,$pageSize,'page');

        if (!empty($group)){
            $house_village_nav_list = $this->where($where)->field($fields)->group($group)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        }else{
            $house_village_nav_list = $this->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        }
        $shopGoodsList=array();
        foreach($house_village_nav_list as $k=>$v){
            $house_village_nav_list[$k]['url'] = wapLbsTranform($v['url'],array('title'=>$v['name']));
            if(isset($v['start_time']) && $v['start_time']>100){
                $house_village_nav_list[$k]['start_time_str']=date('Y-m-d',$v['start_time']);
            }
            if(isset($v['end_time']) && $v['end_time']>100){
                $house_village_nav_list[$k]['end_time_str']=date('Y-m-d',$v['end_time']);
            }
            if(isset($v['data_extra']) && $v['data_extra']){
                $data_extra= json_decode($v['data_extra'],true);
                if($v['type']==21 && $data_extra && isset($data_extra['price'])){
                    $house_village_nav_list[$k]['price']=$data_extra['price'];
                    $house_village_nav_list[$k]['discount_price']=$data_extra['discount_price'];
                }
            }
            if($v['type']==21 && $v['goods_id'] && $v['shop_goods_type']){
                $shopGoodsList[]=array('nav_idd'=>$v['id'],'goods_id'=>$v['goods_id'],'goods_type'=>$v['shop_goods_type']);
            }
        }

        $result['list'] = $house_village_nav_list;
        $result['pagebar'] = $p->show();

        if($house_village_nav_list){
            return array('status'=>1,'result'=>$result,'shopGoodsList'=>$shopGoodsList);
        }else{
            return array('status'=>0,'result'=>$result,'shopGoodsList'=>$shopGoodsList);
        }
    }

    public function house_village_nav_page_list1($where , $fields = true ,$order = 'id desc' , $pageSize = 0,$group='',$page_type=0){
        if(!$where){
            return false;
        }

        if($page_type == 0){
            import('@.ORG.merchant_page');
        }else{
            import('@.ORG.system_page');
        }

        if (!empty($group)){
            $count = $this->where($where)->group($group)->count();
        }else{
            $count = $this->where($where)->count();
        }
        if ($pageSize>0){
            $p = new Page($count,$pageSize,'page');

            if (!empty($group)){
                $house_village_nav_list = $this->where($where)->field($fields)->group($group)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

            }else{
                $house_village_nav_list = $this->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

            }
        }else{
            if (!empty($group)){
                $house_village_nav_list = $this->where($where)->field($fields)->group($group)->order($order)->select();

            }else{
                $house_village_nav_list = $this->where($where)->field($fields)->order($order)->select();

            }
        }

        foreach($house_village_nav_list as $k=>$v){
            $house_village_nav_list[$k]['url'] = wapLbsTranform($v['url'],array('title'=>$v['name']));
        }

        $result['list'] = $house_village_nav_list;
        if ($pageSize>0) {
            $result['pagebar'] = $p->show();
        }
        if($house_village_nav_list){
            return array('status'=>1,'result'=>$result);
        }else{
            return array('status'=>0,'result'=>$result);
        }
    }


    public function house_village_nav_detail($where ,$fields = '*'){
        if(!$where){
            return false;
        }

        $detail = $this->where($where)->field($fields)->find();

        if($detail){
            return array('status'=>1,'detail'=>$detail);
        }else{
            return array('status'=>0,'detail'=>$detail);
        }
    }
    public function house_village_nav_save($data,$village_id=0,$where=array()){
        if(empty($data)){
            return false;
        }
        $idd=0;
        if($where){
            $info = $this->where($where)->find();
            $idd=$info ? $info['id']:0;
        }
        $data['village_id']=$village_id;
        if($idd>0){
            $insert_id = $this->where(array('id'=>$idd))->data($data)->save();
        }else{
            $data['add_time']=time();
            $insert_id=$this->data($data)->add();
        }
        return array('status'=>1,'msg'=>'保存成功！','idd'=>$insert_id);
    }

    public function house_village_nav_edit($where , $data,$village_id=0){
        if(!$where || !$data){
            return false;
        }
        $info = $this->where($where)->find();
        if(!$info){
            return array('status'=>0,'msg'=>'信息不存在！');
        }
        $data['url']=htmlspecialchars_decode($data['url']);
        //todo 小区复制平台广告 生成属于小区的广告
        $rel=D('House_link')->get_filter_village_id($village_id,$info);
        if(!empty($rel)){
            $insert_data=$info;
            unset($insert_data['id'],$insert_data['filter_village_ids']);
            $insert_data['village_id']=$village_id;
            $insert_data['name']=empty($data['name']) ? $info['name'] : $data['name'];
            $insert_data['img']=empty($data['img']) ? $info['img'] : $data['img'];
            $insert_data['add_time']=time();
            $insert_data['sort']=empty($data['sort']) ? $info['sort'] : $data['sort'];
            $insert_data['source_type']=0;
            $insert_data['url']=D('House_link')->replace_village_parameter((empty($data['url']) ? $info['url'] : $data['url']),$village_id);
            // 原广告数据写入关联village_ids
            $this->where(['id'=>$info['id']])->data(['filter_village_ids'=>implode(',',$rel)])->save();
            if(isset($data['mark_name'])){
                $insert_data['mark_name']=$data['mark_name'];
            }
            if(isset($data['mark_bg'])){
                $insert_data['mark_bg']=$data['mark_bg'];
            }
            if(isset($data['wxapp_id'])){
                $insert_data['wxapp_id']=trim($data['wxapp_id']);
                $insert_data['wxapp_page']=trim($data['wxapp_page']);
            }
            $insert_id=$this->data($insert_data)->add();
        }else{
            $insert_id = $this->where($where)->data($data)->save();
        }
        return array('status'=>1,'msg'=>'修改成功！');
    }


    public function house_village_nav_del($where){
        if(!$where){
            return false;
        }

        $insert_id = $this->where($where)->delete();

        if($insert_id){
            return array('status'=>1,'msg'=>'删除成功！');
        }else{
            return array('status'=>0,'msg'=>'删除失败！');
        }
    }


    public function get_village_nav($village_id,$modules=[],$uid=-1,$type = 0,$now_village=[])
    {
        $replace_arr=[];
        if(isset($modules['is_new_charge'])){
            $replace_arr['is_new_charge']=$modules['is_new_charge'];
        }
        $where1='(status =1 and type = '. $type .' and village_id = '.$village_id.')';
        $limit_num=20;
        if($type==2){
            $limit_num=10;
        }
        $list = $this->where($where1)->order('status DESC,sort desc,id desc')->limit($limit_num)->select();
        // 2020/8/25 start
        $list = D('PrivilegePackage')->getApplication($list,$village_id);
        //2020/8/25 end
        //系统导航
        if (empty($now_village)) {
            $now_village = D('House_village')->where(array('village_id'=>$village_id))->find();
        }
        $village_nav_set = D('House_village_info')->where(array('village_id'=>$village_id))->find();
        $where_field = "type=".$type." and ((village_id=0";
        if($now_village){
            if($now_village['province_id']){
                $where_field .= " and  province_id =".$now_village['province_id'];
            }
            if($now_village['city_id']){

                $where_field .= " AND (city_id =".$now_village['city_id'].' OR city_id = 0)';
            }
            if($now_village['area_id']){

                $where_field .= " AND (area_id =".$now_village['area_id'].' OR area_id = 0)';
            }
            if($now_village['street_id']){

                $where_field .= " AND (street_id =".$now_village['street_id'].' OR street_id = 0)';
            }
            if($now_village['community_id']){

                $where_field .= " AND (community_id =".$now_village['community_id'].' OR community_id = 0)';
            }

        }

        if(isset($modules['switch']) && intval($modules['switch']) > 0){
            if(!empty($modules['modules'])){
                $where_str_modules = "'".implode("','", $modules['modules'])."'";
                $where_field .= ' AND status=1 and source_type = 0 ) OR (village_id=0 AND status=1 and ( source_type in ('.$where_str_modules.') or source_type = "0") and (filter_village_ids is null OR  !FIND_IN_SET('.$village_id.', filter_village_ids )) ))';
            }else{
                $where_field .= ' AND status=1 and source_type = 0 ))';
            }
        }else{
            $adver_village_list=M('House_adver_village_count')->where(['village_id'=>$village_id])->getField('nav_id',true);
            $adver_village_list=array_filter($adver_village_list);
            $where_field.=" AND status=1";
            if (!empty($adver_village_list)){
                $adver_village_list=implode(',',$adver_village_list);
                $where_field .= "  OR (id IN ({$adver_village_list}) AND status=1)";
            }
            $where_field .= ") OR ( province_id =0 AND city_id = 0 AND area_id=0 AND street_id=0 AND community_id=0  AND village_id=0 AND status=1 ))";
        }
        $house_village_count = $this->where($where1)->count();
        if (!empty($village_nav_set) && (($type != 1 && $village_nav_set['village_nav']==1) || ($type == 1 && $village_nav_set['village_nav_single']==1))) {
            if ($house_village_count < $limit_num) {
                $limit = $limit_num - $house_village_count;
                $system_nav_list = $this->where($where_field)->order('status DESC,sort desc,id desc')->limit($limit)->select();
            if($system_nav_list){
                foreach($list as $k=>&$v){
                    $property_id = $now_village['property_id'];
                    $is_new_charge=D('House_village')->checkNewCharge($property_id);
                    if($is_new_charge){
                        if(strpos($v['url'],'village/my/payMentlist?') !== false){
                            $v['url'] = str_replace('village/my/payMentlist?','houseMeter/NewCollectMoney/newLiveExpenses?',$v['url']);
                        }
                    }
                    $url = html_entity_decode($v['url']);
                    $v['url']=D('House_link')->replace_village_parameter($url,$village_id,$uid,$now_village,$replace_arr);
                }
                foreach($system_nav_list as $kk=>$vv){
                    $data_list['id'] = $vv['id'];
                    $data_list['name'] = $vv['name'];
                    $data_list['village_id'] = $vv['village_id'];
                    $data_list['img'] = $vv['img'];
//                    $data_list['url'] = html_entity_decode($vv['url']);
                    $property_id = $now_village['property_id'];
                    $is_new_charge=D('House_village')->checkNewCharge($property_id);
                    if($is_new_charge){
                        if(strpos($vv['url'],'village/my/payMentlist?') !== false){
                            $vv['url'] = str_replace('village/my/payMentlist?','houseMeter/NewCollectMoney/newLiveExpenses?',$vv['url']);
                        }
                    }
                    $url = html_entity_decode($vv['url']);
                    $data_list['url']=D('House_link')->replace_village_parameter($url,$village_id,$uid,$now_village,$replace_arr);


                    $data_list['status'] = $vv['status'];
                    $data_list['add_time'] = $vv['add_time'];
                    $data_list['sort'] = $vv['sort'];
                    $data_list['province_id'] = $vv['province_id'];
                    $data_list['city_id'] = $vv['city_id'];
                    $data_list['area_id'] = $vv['area_id'];
                    $data_list['street_id'] = $vv['street_id'];
                    $data_list['community_id'] = $vv['community_id'];
                    $data_list['wxapp_id'] = $vv['wxapp_id'];
                    $data_list['wxapp_page'] = $vv['wxapp_page'];
                    $list[] = $data_list;


                }
            } else {
                if(empty($list)){
                    //需要加默认导航的数据==>未完成
                    $this->house_nav_test($village_id,$type);
                    $list = $this->where($where1)->order('status DESC,sort desc,id desc')->limit($limit_num)->select();
                }
                foreach($list as $k=>&$v){
                    $property_id = $now_village['property_id'];
                    $is_new_charge=D('House_village')->checkNewCharge($property_id);
                    if($is_new_charge){
                        if(strpos($v['url'],'village/my/payMentlist?') !== false){
                            $v['url'] = str_replace('village/my/payMentlist?','houseMeter/NewCollectMoney/newLiveExpenses?',$v['url']);
                        }
                    }
                    $url = html_entity_decode($v['url']);
                    $v['url']=D('House_link')->replace_village_parameter($url,$village_id,$uid,$now_village,$replace_arr);

                }
            }
        } else {
            if(empty($list)){
                //需要加默认导航的数据==>未完成
                $this->house_nav_test($village_id,$type);
                $list = $this->where($where1)->order('status DESC,sort desc,id desc')->limit($limit_num)->select();
            }
            foreach($list as $k=>&$v){
                $property_id = $now_village['property_id'];
                $is_new_charge=D('House_village')->checkNewCharge($property_id);
                if($is_new_charge){
                    if(strpos($v['url'],'village/my/payMentlist?') !== false){
                        $v['url'] = str_replace('village/my/payMentlist?','houseMeter/NewCollectMoney/newLiveExpenses?',$v['url']);
                    }
                }
                $url = html_entity_decode($v['url']);
                $v['url']=D('House_link')->replace_village_parameter($url,$village_id,$uid,$now_village,$replace_arr);
                }
            }
        }else{
            if ($house_village_count < $limit_num && ($type != 1 && $village_nav_set['village_nav']==1)) {
                $limit = $limit_num - $house_village_count;
                $system_nav_list = $this->where($where_field)->order('status DESC,sort desc,id desc')->limit($limit)->select();
                fdump_api(['查询系统导航'.__LINE__,$system_nav_list,$this->_sql()],'system_nav_list',1);
                if ($system_nav_list) {
                    foreach ($list as $k => &$v) {
                        $url = html_entity_decode($v['url']);
                        $v['url'] = D('House_link')->replace_village_parameter($url, $village_id,$uid,$now_village,$replace_arr);
                    }
                    fdump_api(['查询系统导航'.__LINE__,$list],'system_nav_list',1);
                    foreach ($system_nav_list as $kk => $vv) {
                        $data_list['id'] = $vv['id'];
                        $data_list['name'] = $vv['name'];
                        $data_list['village_id'] = $vv['village_id'];
                        $data_list['img'] = $vv['img'];
                        $url = html_entity_decode($vv['url']);
                        $data_list['url'] = D('House_link')->replace_village_parameter($url, $village_id,$uid,$now_village,$replace_arr);
                        $data_list['status'] = $vv['status'];
                        $data_list['add_time'] = $vv['add_time'];
                        $data_list['sort'] = $vv['sort'];
                        $data_list['province_id'] = $vv['province_id'];
                        $data_list['city_id'] = $vv['city_id'];
                        $data_list['area_id'] = $vv['area_id'];
                        $data_list['street_id'] = $vv['street_id'];
                        $data_list['community_id'] = $vv['community_id'];
                        $data_list['wxapp_id'] = $vv['wxapp_id'];
                        $data_list['wxapp_page'] = $vv['wxapp_page'];
                        $list[] = $data_list;
                    }
                    fdump_api(['查询系统导航'.__LINE__,$list],'system_nav_list',1);
                }
            }
        }
        $list=$this->handleImg($list,0,$now_village);
        if($list){
            $sortArr=array_column($list,'sort');
            array_multisort($sortArr,SORT_DESC,SORT_NUMERIC,$list);
        }
        return $list ? array_values($list) : [];

    }

    private function house_nav_test($village_id,$type=0)
    {
        if($type > 0){
            return true;
        }
        //处理我的应用
        $application_arr = D('Application_list')->get_application_all_auth($village_id);

        $data = $this->where(array('village_id' => $village_id,'status' => 1,'name' => array('in',['绑定小区','快递管理','公告通知','投诉建议','物业报修','访客通行','生活缴费','快递管理'])))->getField('name',true);

        $base_url = D('House_village')->base_url;

        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pageName' => 'payMentlist',
            'isAll' => true,
            'urlParam' => 'village_id='.$village_id,
        ];
        $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);

        $service_son_category['name'] = '生活缴费';
        $service_son_category['village_id'] = $village_id;
        $service_son_category['img'] = '/static/images/house/shjf_index.png';
        $service_son_category['status'] = '1';
        $service_son_category['add_time'] = time();
        $service_son_category['sort'] = 0;
        //$service_son_category['url'] = C('config.site_url').'/wap.php?g=Wap&c=House&a=village_my_pay&village_id='.$village_id;
        $service_son_category['url'] = $payMentlistUrl;
        if(!$data || !in_array('生活缴费',$data)){
            $this->data($service_son_category)->add();
        }

        $service_son_category['name'] = '访客通行';
        $service_son_category['village_id'] = $village_id;
        $service_son_category['img'] = '/static/images/house/fktx_index.png';
        $service_son_category['status'] = '1';
        $service_son_category['add_time'] = time();
        $service_son_category['sort'] = 0;
        $service_son_category['url'] = C('config.site_url').$base_url.'pages/village/index/visitorPass';
        if(!$data || !in_array('访客通行',$data)){
            $this->data($service_son_category)->add();
        }


        if(in_array('16',$application_arr)){
            $service_son_category['name'] = '物业报修';
            $service_son_category['village_id'] = $village_id;
            $service_son_category['img'] = '/static/images/house/wybx_index.png';
            $service_son_category['status'] = '1';
            $service_son_category['add_time'] = time();
            $service_son_category['sort'] = 0;
            $param = [
                'pageName' => 'waterReportlist',
                'isAll' => true,
                'urlParam' => 'type=1&title='.urlencode('物业报修').'&village_id='.$village_id,
            ];
            $waterReportlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            $service_son_category['url'] = $waterReportlistUrl;
            if(!$data || !in_array('物业报修',$data)){
                $this->data($service_son_category)->add();
            }
        }

        if(in_array('19',$application_arr)){

            $service_son_category['name'] = '投诉建议';
            $service_son_category['village_id'] = $village_id;
            $service_son_category['img'] = '/static/images/house/txjy_index.png';
            $service_son_category['status'] = '1';
            $service_son_category['add_time'] = time();
            $service_son_category['sort'] = 0;
            $param = [
                'pageName' => 'waterReportlist',
                'isAll' => true,
                'urlParam' => 'type=3&title='.urlencode('投诉建议').'&village_id='.$village_id,
            ];
            $waterReportlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            $service_son_category['url'] = $waterReportlistUrl;
            if(!$data || !in_array('投诉建议',$data)){
                $this->data($service_son_category)->add();
            }
        }


        $service_son_category['name'] = '公告通知';
        $service_son_category['village_id'] = $village_id;
        $service_son_category['img'] = '/static/images/house/ggtz_index.png';
        $service_son_category['status'] = '1';
        $service_son_category['add_time'] = time();
        $service_son_category['sort'] = 0;
        //$service_son_category['url'] = C('config.site_url').'/wap.php?g=Wap&c=House&a=village_newslist&village_id='.$village_id;
        $service_son_category['url'] = C('config.site_url'). $base_url.'pages/village/index/notice?village_id='.$village_id;
        if(!$data || !in_array('公告通知',$data)){
            $this->data($service_son_category)->add();
        }

        if(in_array('14',$application_arr)){
            $service_son_category['name'] = '快递管理';
            $service_son_category['village_id'] = $village_id;
            $service_son_category['img'] = '/static/images/house/ems_index.png';
            $service_son_category['status'] = '1';
            $service_son_category['add_time'] = time();
            $service_son_category['sort'] = 0;
            $param = [
                'pageName' => 'expressManagement',
                'isAll' => true,
                'urlParam' => 'village_id=' . $village_id,
            ];
            $expressManagementUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            $service_son_category['url'] = $expressManagementUrl;

            if(!$data || !in_array('快递管理',$data)){
                $this->data($service_son_category)->add();
            }
        }


        $service_son_category['name'] = '绑定小区';
        $service_son_category['village_id'] = $village_id;
        $service_son_category['img'] = '/static/images/house/village.png';
        $service_son_category['status'] = '1';
        $service_son_category['add_time'] = time();
        $service_son_category['sort'] = 0;
        $param = [
            'pageName' => 'selectRooms',
            'isAll' => true,
            'urlParam' => '',
        ];
        $selectRoomsUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);

        $service_son_category['url'] = $selectRoomsUrl;

        if(!$data || !in_array('绑定小区',$data)){
            $this->data($service_son_category)->add();
        }


    }


    //todo 集中处理图片路径
    public function handleImg($list,$status=0,$now_village=array()){
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pageName' => 'waterReportlist',
            'isAll' => true,
            'urlParam' => '',
        ];
        $waterReportlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $arr=[
            $waterReportlistUrl.'?type=1',
            $waterReportlistUrl.'?type=2',
            $waterReportlistUrl.'?type=3',
        ];
        if(!empty($list)){
            foreach ($list as $k => &$v) {
                $list[$k]['nav_xtype']='';
                if(isset($v['img']) && !empty($v['img'])){
                    if (strpos($v['img'],'/upload/') !== false) {
                        $v['img'] = replace_file_domain($v['img']);
                    }
                    elseif (substr($v['img'], 1, 6) == 'static') {
                        $v['img'] = C('config.site_url') . $v['img'];
                    }
                    elseif (substr($v['img'], '0', '4') == '000/') {
                        $v['img'] = file_domain() . '/upload/adver/' . $v['img'];
                    }
                    else {
                        $v['img'] = file_domain() . '/upload/service/' . $v['img'];
                    }
                }
                if(isset($v['wxapp_id']) || array_key_exists('wxapp_id',$v)) {
                    $list[$k]['wxapp_id'] = empty($v['wxapp_id']) ? '' : $v['wxapp_id'];
                    $list[$k]['wxapp_page'] = empty($v['wxapp_page']) ? '' : $v['wxapp_page'];
                }
                $url=!empty($v['url']) ?  trim($v['url']):'';
                if(strpos($v['url'],'area_street_id=') && strpos($v['url'],'source=Street') && !empty($now_village) && !empty($now_village['street_id'])){
                    $v['url']=$v['url'].'&area_street_id='.$now_village['street_id'];
                }elseif(strpos($v['url'],'area_street_id=') && strpos($v['url'],'source=Community') && !empty($now_village) && !empty($now_village['community_id'])){
                    $v['url']=$v['url'].'&area_street_id='.$now_village['community_id'];
                }
                if($url=='on_deving'){
                    $list[$k]['nav_xtype']='on_deving';
                    $list[$k]['url']='';
                }
                if($status > 0){
                    foreach ($arr as $v2){
                        if(strpos($v['url'], $v2) !== false){
                            unset($list[$k]);break;
                        }
                    }
                }
            }
            unset($v);
        }
        return $list;
    }

}
?>