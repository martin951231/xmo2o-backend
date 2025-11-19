<?php
class House_village_nav_moduleModel extends Model{

    public $block_type_arr=array('village_life_service'=>'小区生活服务','business_express_delivery'=>'','village_life_top'=>'','village_life_middle'=>'','village_life_bottom'=>'','village_life_assistant'=>'家庭助理','village_life_assistant'=>'家庭助理','business_classify_info'=>'邻里猜你喜欢','village_express_delivery'=>'小区快送','business_store_info'=>'猜你喜欢商家','left_bottom_rowgrid'=>'底部左侧二宫格','right_bottom_rowgrid'=>'底部右侧二宫格');
    public $block_visualize_assign=array('business_express_delivery'=>30,'village_life_service'=>31,'village_life_assistant'=>32,'business_live_broadcast'=>33,'business_classify_info'=>34,'business_store_info'=>35,'left_bottom_rowgrid'=>36,'right_bottom_rowgrid'=>37,'business_small_shop'=>30,'business_new_mall'=>30,'business_community_group'=>30,'business_service_reservation'=>30,'village_life_top'=>30,'village_life_middle'=>30,'village_street_community'=>30);
    public function getOneInfo($where,$field = '*'){
        if(!isset($where['del_time'])){
            $where['del_time']=array('lt',1);
        }
        return $this->field($field)->where($where)->find();
    }
    public function get_block_type($block_type=''){
        if($block_type){
            $block_type_str='';
            if($block_type && isset($this->block_type_arr[$block_type])){
                $block_type_str=$this->block_type_arr[$block_type];
            }
            return $block_type_str;
        }
        return $this->block_type_arr;
    }

    public function saveData($whereArr='',$saveArr=array()){

        $ret=$this->where($whereArr)->save($saveArr);
        return $ret;
    }
    public function getDataList($whereArr='',$fields='*',$order='id ASC'){
        $ret=$this->where($whereArr)->field($fields)->order($order)->select();
        return $ret;
    }
    
    public function addData($data=array()){
        $insert_id=$this->data($data)->add();
        return $insert_id;
    }
    public function house_visualize_assign($village_id=0,$block_type_id=''){
        $is_assign=false;
        $database_house_visualize_assign  = D('House_visualize_assign');
        $where=[
            'village_id'=>$village_id
        ];
        $switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        $switch=intval($switch);
        if($switch<1){
            $is_assign=false;
            return $is_assign;
        }
        if(!empty($block_type_id) && isset($this->block_visualize_assign[$block_type_id])){
            $where['type']=$this->block_visualize_assign[$block_type_id];
            $house_visualize_assign=$database_house_visualize_assign->where($where)->field('village_id,type')->find();
            if($house_visualize_assign && isset($house_visualize_assign['type']) && $house_visualize_assign['type']){
                $is_assign=true;
            }
        }
        return $is_assign;
    }
    
    public function getNewVillageNavDatas($village_id=0,$uid=0,$modules=array(),$now_village=array()){
        $whereNavArr=array('village_id'=>$village_id,'is_community_platform'=>0,'is_show'=>1,'del_time'=>array('lt',1));
        $fieldStr='*';
        $newNavArr=array();
        $is_assign= $this->house_visualize_assign($village_id,'business_express_delivery');
        if($is_assign){
            $whereNavArr['village_id']=0;
            $whereNavArr['is_community_platform']=1;
        }
        $business_express_delivery=array('block_type_id'=>'business_express_delivery','block_top_color'=>'','block_bottom_color'=>'','fillet_radius'=>0,'list'=>array());
        $lng= '';
        $lat='';
        if(isset($now_village['long']) && $now_village['long']>0){
            $lng=$now_village['long'];
        }
        if(isset($now_village['lat']) && $now_village['lat']>0){
            $lat=$now_village['lat'];
        }
        $whereNavArr['block_type_id']='business_small_shop';
        $ret=$this->where($whereNavArr)->field($fieldStr)->find();
        if ($ret) {
            $data_extra = $ret['data_extra'] ? json_decode($ret['data_extra'],true) : array();
            unset($ret['data_extra'], $ret['add_time'], $ret['del_time']);
            if($ret['block_top_color']){
                $business_express_delivery['block_top_color']=$ret['block_top_color'];
            }
            if($ret['block_bottom_color']){
                $business_express_delivery['block_bottom_color']=$ret['block_bottom_color'];
            }
            if($ret['fillet_radius']){
                $business_express_delivery['fillet_radius']=$ret['fillet_radius']>0 ? intval($ret['fillet_radius']):0;
            }
            $ret['product_info']=array();
            /*
            if($data_extra && isset($data_extra['product_info']) && !empty($data_extra['product_info'])){
                $product_id_arr=array();
                foreach ($data_extra['product_info'] as $vv){
                    $product_id_arr[]=$vv['product_id'];
                }
                $list= D('Common')->businessProductList('shop',$product_id_arr);
                $datalist=$list['list'];
                $ret['product_info']=$datalist;
            }
            */
            $distance=isset($ret['distance']) ? intval($ret['distance']):0;
            $list= D('Common')->businessProductList('shop',$lng,$lat,$distance);
            $datalist=$list['list'];
            $ret['product_info']=$datalist;
            $business_express_delivery['list'][]=$ret;
        }

        $whereNavArr['block_type_id']='business_new_mall';
        $ret=$this->where($whereNavArr)->field($fieldStr)->find();
        if ($ret) {
            $data_extra = $ret['data_extra'] ? json_decode($ret['data_extra'],true) : array();
            unset($ret['data_extra'], $ret['add_time'], $ret['del_time']);
            if($ret['block_top_color']){
                $business_express_delivery['block_top_color']=$ret['block_top_color'];
            }
            if($ret['block_bottom_color']){
                $business_express_delivery['block_bottom_color']=$ret['block_bottom_color'];
            }
            if($ret['fillet_radius']){
                $business_express_delivery['fillet_radius']=$ret['fillet_radius']>0 ? intval($ret['fillet_radius']):0;
            }
            $ret['product_info']=array();
            /*
            if($data_extra && isset($data_extra['product_info']) && !empty($data_extra['product_info'])){
                $product_id_arr=array();
                foreach ($data_extra['product_info'] as $vv){
                    $product_id_arr[]=$vv['product_id'];
                }
                $list= D('Common')->businessProductList('mall',$product_id_arr);
                $datalist=$list['list'];
                $ret['product_info']=$datalist;
            }
            */
            $distance=isset($ret['distance']) ? intval($ret['distance']):0;
            $list= D('Common')->businessProductList('mall',$lng,$lat,$distance);
            $datalist=$list['list'];
            $ret['product_info']=$datalist;
            $business_express_delivery['list'][]=$ret;
        }

        $whereNavArr['block_type_id']='business_community_group';
        $ret=$this->where($whereNavArr)->field($fieldStr)->find();
        if ($ret) {
            $data_extra = $ret['data_extra'] ? json_decode($ret['data_extra'],true) : array();
            unset($ret['data_extra'], $ret['add_time'], $ret['del_time']);
            if($ret['block_top_color']){
                $business_express_delivery['block_top_color']=$ret['block_top_color'];
            }
            if($ret['block_bottom_color']){
                $business_express_delivery['block_bottom_color']=$ret['block_bottom_color'];
            }
            if($ret['fillet_radius']){
                $business_express_delivery['fillet_radius']=$ret['fillet_radius']>0 ? intval($ret['fillet_radius']):0;
            }
            $ret['product_info']=array();
            /*
            if($data_extra && isset($data_extra['product_info']) && !empty($data_extra['product_info'])){
                $product_id_arr=array();
                foreach ($data_extra['product_info'] as $vv){
                    $product_id_arr[]=$vv['product_id'];
                }
                $list= D('Common')->businessProductList('group',$product_id_arr);
                $datalist=$list['list'];
                $ret['product_info']=$datalist;
            }
            */
            $distance=isset($ret['distance']) ? intval($ret['distance']):0;
            $list= D('Common')->businessProductList('group',$lng,$lat,$distance);
            $datalist=$list['list'];
            $ret['product_info']=$datalist;
            $business_express_delivery['list'][]=$ret;
        }
        
        $whereNavArr['block_type_id']='business_service_reservation';
        $ret=$this->where($whereNavArr)->field($fieldStr)->find();
        if ($ret) {
            $data_extra = $ret['data_extra'] ? json_decode($ret['data_extra'],true) : array();
            unset($ret['data_extra'], $ret['add_time'], $ret['del_time']);
            if($ret['block_top_color']){
                $business_express_delivery['block_top_color']=$ret['block_top_color'];
            }
            if($ret['block_bottom_color']){
                $business_express_delivery['block_bottom_color']=$ret['block_bottom_color'];
            }
            if($ret['fillet_radius']){
                $business_express_delivery['fillet_radius']=$ret['fillet_radius']>0 ? intval($ret['fillet_radius']):0;
            }
            $ret['product_info']=array();
            /*
            if($data_extra && isset($data_extra['product_info']) && !empty($data_extra['product_info'])){
                $product_id_arr=array();
                foreach ($data_extra['product_info'] as $vv){
                    $product_id_arr[]=$vv['product_id'];
                }
                $list= D('Common')->businessProductList('appoint',$product_id_arr);
                $datalist=$list['list'];
                $ret['product_info']=$datalist;
            }
            */
            $distance=isset($ret['distance']) ? intval($ret['distance']):0;
            $list= D('Common')->businessProductList('appoint',$lng,$lat,$distance);
            $datalist=$list['list'];
            $ret['product_info']=$datalist;
            $business_express_delivery['list'][]=$ret;
        }
        if($business_express_delivery['list']){
            $tmpexpresslist=$business_express_delivery['list']['0'];
            foreach ($business_express_delivery['list'] as $kkk=>$vvv){
                if(empty($vvv['product_info'])){
                    unset($business_express_delivery['list'][$kkk]);
                }
            }
            if(empty($business_express_delivery['list'])){
                $business_express_delivery['list'][]=$tmpexpresslist;
            }
            $business_express_delivery['list']=array_values($business_express_delivery['list']);
        }
        $xsortArr=array_column($business_express_delivery['list'],'xsort');
        array_multisort($xsortArr,SORT_DESC,SORT_NUMERIC,$business_express_delivery['list']);
        
        $newNavArr['business_express_delivery']=$business_express_delivery;

        $village_life_top=$this->getItemVillageNav('village_life_top',$village_id,$uid,$modules,$now_village);
        $newNavArr['village_life_top']=$village_life_top;
        
        $village_life_middle=$this->getItemVillageNav('village_life_middle',$village_id,$uid,$modules,$now_village);
        $newNavArr['village_life_middle']=$village_life_middle;
        
        $village_street_community=$this->getItemVillageNav('village_street_community',$village_id,$uid,$modules,$now_village);
        if($village_street_community['nav_list'] && $village_street_community['nav_list']['0']){
            $nav_tmp = $village_street_community['nav_list']['0'];
            $village_street_community['nav_list']=array();
            $village_street_community['url']=$nav_tmp['url'];
            $village_street_community['img']=$nav_tmp['img'];
            $village_street_community['wxapp_id']=$nav_tmp['wxapp_id'];
            $village_street_community['wxapp_page']=$nav_tmp['wxapp_page'];
        }
        $newNavArr['village_street_community']=$village_street_community;
        
        $village_life_service=$this->getItemVillageNav('village_life_service',$village_id,$uid,$modules,$now_village);
        $newNavArr['village_life_service']=$village_life_service;
        
        $village_life_assistant=$this->getItemVillageNav('village_life_assistant',$village_id,$uid,$modules,$now_village);
        $newNavArr['village_life_assistant']=$village_life_assistant;

        $newNavArr['business_live_broadcast']=array('is_show'=>0);
        $whereNavArr=array('village_id'=>$village_id,'is_community_platform'=>0,'is_show'=>1,'del_time'=>array('lt',1));
        $fieldStr='*';
        $is_assign= $this->house_visualize_assign($village_id,'business_express_delivery');
        if($is_assign){
            $whereNavArr['village_id']=0;
            $whereNavArr['is_community_platform']=1;
        }
        $whereNavArr['block_type_id']='business_live_broadcast';
        $ret_live=$this->where($whereNavArr)->field($fieldStr)->find();
        if($ret_live){
            $newNavArr['business_live_broadcast']['is_show']=intval($ret_live['is_show']);
        }
        $business_classify_info=$this->getItemVillageNav('business_classify_info',$village_id,$uid,$modules,$now_village);
        $business_classify_info['classify_url']=C('config.site_url').'/wap.php?g=Wap&c=Classifynew&a=index';
        $newNavArr['business_classify_info']=$business_classify_info;
        $business_store_info=$this->getItemVillageNav('business_store_info',$village_id,$uid,$modules,$now_village);
        $newNavArr['business_store_info']=$business_store_info;

        $left_bottom_rowgrid=$this->getItemVillageNav('left_bottom_rowgrid',$village_id,$uid,$modules,$now_village);
        $newNavArr['left_bottom_rowgrid']=$left_bottom_rowgrid;

        $right_bottom_rowgrid=$this->getItemVillageNav('right_bottom_rowgrid',$village_id,$uid,$modules,$now_village);
        $newNavArr['right_bottom_rowgrid']=$right_bottom_rowgrid;
        $newNavArr['right_top_set_info']=array(['title'=>'设置','url'=>C('config.site_url').'/packapp/village/pages/my/myinfo?url_type=village','icon_src'=>C('config.site_url').'/static/setting.png']);
        if(isset($now_village['has_kefu']) && $now_village['has_kefu'] && isset($now_village['bind_pigcms_id']) && $now_village['bind_pigcms_id']){
            $application_arr = D('Application_list')->get_application_all_auth($village_id);
            if(1||in_array(1,$application_arr)){
                $customer_info = M('Merchant_store_kefu_customer')->where(['uid'=>$now_village['bind_pigcms_id']])->find();
                if($customer_info){
                    $kf = M('Merchant_store_kefu')->where(array('id' => $customer_info['kefu_id']))->find();
                }else{
                    $user_bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$now_village['bind_pigcms_id']])->field('single_id')->find();
                    if($user_bind_info['single_id']){
                        $house_keeper_info = M('House_services_housekeeper')->where(['single_id'=>$user_bind_info['single_id']])->find();
                        if($house_keeper_info['work_arr']){
                            $con=array();
                            $con['bind_uid'] = array('in',$house_keeper_info['work_arr']);
                            $kf = M('Merchant_store_kefu')->where($con)->find();
                            if(empty($kf)){
                                $kf = M('Merchant_store_kefu')->where(array('belong' => 'village', 'store_id' => $village_id))->find();
                            }
                        }else{
                            $kf = M('Merchant_store_kefu')->where(array('belong' => 'village', 'store_id' => $village_id))->find();
                        }
                    }else{
                        $kf = M('Merchant_store_kefu')->where(array('belong' => 'village', 'store_id' => $village_id))->find();
                    }

                }
                if(1||(isset($kf) && $kf)){
                    $kfUrl = build_im_chat_url('villageBind_' . $now_village['bind_pigcms_id'], $kf['username'], 'villageBind2village');
                    $newNavArr['right_top_set_info'][]=['title'=>'在线客服','url'=>$kfUrl,'icon_src'=>C('config.site_url').'/static/online_kf.png'];
                }
            }

        }
        return $newNavArr;
    }

    public function  getItemVillageNav($block_type_id='',$village_id=0,$uid=0,$modules=array(),$now_village=array()){
        $database_house_village_nav = D('House_village_nav');
        $village_block_nav=array('is_show'=>0);
        if(empty($block_type_id)){
            return $village_block_nav;
        }
        $replace_arr=[];
        if(isset($modules['is_new_charge'])){
            $replace_arr['is_new_charge']=$modules['is_new_charge'];
        }
        $whereNavArr=array('village_id'=>$village_id,'is_community_platform'=>0,'del_time'=>array('lt',1),'is_show'=>1);
        
        $is_assign= $this->house_visualize_assign($village_id,$block_type_id);
        if($is_assign){
            $whereNavArr['village_id']=0;
            $whereNavArr['is_community_platform']=1;
        }
        $whereNavArr['block_type_id']=$block_type_id;
        $fieldStr='*';
        $block_nav=$this->where($whereNavArr)->field($fieldStr)->find();
        $navNumArr=array('village_life_top'=>12,'village_life_service'=>12,'village_life_assistant'=>9,'village_street_community'=>1,'village_life_middle'=>9,'business_classify_info'=>20,'business_store_info'=>20,'left_bottom_rowgrid'=>2,'right_bottom_rowgrid'=>2);
        if($block_nav && is_array($block_nav)){
            if ($block_nav['label_img']) {
                if (strpos($block_nav['label_img'], '/upload/') !== false) {
                    $block_nav['label_img'] = replace_file_domain($block_nav['label_img']);
                } elseif (substr($block_nav['label_img'], 1, 6) == 'static') {
                    $block_nav['label_img'] = C('config.site_url') . $block_nav['label_img'];
                } elseif (substr($block_nav['label_img'], '0', '4') == '000/') {
                    $block_nav['label_img'] = file_domain() . '/upload/adver/' . $block_nav['label_img'];
                } else {
                    $block_nav['label_img'] = file_domain() . '/upload/service/' . $block_nav['label_img'];
                }

            }
            unset($block_nav['data_extra'], $block_nav['add_time'], $block_nav['del_time']);
            $whereTmpArr=array('block_type_id'=>$block_type_id,'type'=>50,'status'=>1);
            if($is_assign){
                if($block_type_id=='village_street_community'){
                    $whereTmpArr['village_id'] =0;
                    $whereTmpArr['is_community_platform']=1;
                }else{
                    $whereTmpArr['_string'] = '(village_id='.$village_id.' or (village_id =0 AND is_community_platform=1))';
                }

            }else{
                $whereTmpArr['village_id']=$village_id;
            }
            $block_nav['is_assign']=$is_assign;
            $block_nav['nav_list']=array();
            $alllimit=20;
            if(isset($navNumArr[$block_type_id]) && $navNumArr[$block_type_id]>0){
                $alllimit=$navNumArr[$block_type_id];
            }
            $house_village_nav=$database_house_village_nav->where($whereTmpArr)->field('*')->order('sort desc,id desc')->limit('0,'.$alllimit)->select();
            if($house_village_nav){
                $list = D('PrivilegePackage')->getApplication($house_village_nav,$village_id);
                $nav_num=0;
                if($list){
                    $nav_num=count($list);
                }
                if($block_nav['show_community_platform']>0 && !$is_assign && $nav_num<$alllimit && $alllimit>1){
                    $limit = $alllimit - $nav_num;
                    $where_sys=array('block_type_id'=>$block_type_id,'type'=>50,'status'=>1,'village_id' =>'0','is_community_platform'=>1);
                    $system_nav_list = $database_house_village_nav->where($where_sys)->order('sort desc,id desc')->limit('0,'.$limit)->select();
                    if($system_nav_list){
                        foreach ($system_nav_list as $svv){
                            $list[]=$svv;
                        }
                    }
                }
                if($block_type_id=='business_classify_info'){
                    $database_classify_category = D('Classify_category');
                    $new_list=array();
                    foreach ($list as &$nvv) {
                        $tmp_item=array();
                        $tmp_item['id']=$nvv['id'];
                        $tmp_item['sub_title']=$nvv['name'];
                        $tmp_item['sort']=$nvv['sort'];
                        $tmp_item['block_type_id']=$nvv['block_type_id'];
                        $tmp_item['business_cat_id']=$nvv['business_cat_id'];
                        $tmp_item['business_num']=$nvv['business_num'];
                        $tmp_item['title']='';
                        $tmp_item['cat_url']='';
                        if($nvv['business_cat_id']>0){
                            $condition = array();
                            $condition['cid'] = $nvv['business_cat_id'];
                            $classify_category = $database_classify_category->field('cid,cat_name,cat_url,cat_pic')->where($condition)->find();
                            if ($classify_category) {
                                $tmp_item['title'] = $classify_category['cat_name'];
                                $tmp_item['cat_url'] = $classify_category['cat_url'];
                            }
                        }
                        $new_list[]=$tmp_item;
                    }
                    $list=$new_list;
                    $sortArr=array_column($list,'id');
                    array_multisort($sortArr,SORT_DESC,SORT_NUMERIC,$list);
                }elseif($block_type_id=='business_store_info'){
                    $database_shop_category = D('Shop_category');
                    $new_list=array();
                    $list=$database_house_village_nav->handleImg($list,0,$now_village);
                    foreach ($list as &$nvv) {
                        $tmp_item=array();
                        $tmp_item['id']=$nvv['id'];
                        $tmp_item['sub_title']=$nvv['name'];
                        $tmp_item['sort']=$nvv['sort'];
                        $tmp_item['block_type_id']=$nvv['block_type_id'];
                        $tmp_item['business_cat_id']=$nvv['business_cat_id'];
                        $tmp_item['business_num']=$nvv['business_num'];
                        $tmp_item['img']=$nvv['img'];
                        $tmp_item['title']='';
                        $tmp_item['cat_url']='';
                        if($nvv['business_cat_id']>0){
                            $condition = array();
                            $condition['cat_id'] = $nvv['business_cat_id'];
                            $classify_category = $database_shop_category->field('cat_id as cid,cat_name,cat_url')->where($condition)->find();
                            if ($classify_category) {
                                $tmp_item['title'] = $classify_category['cat_name'];
                                $tmp_item['cat_url'] = $classify_category['cat_url'];
                            }
                        }elseif($nvv['business_cat_id']==0){
                            $tmp_item['title']='全部分类';
                            $tmp_item['cat_url']='all';
                        }
                        $new_list[]=$tmp_item;
                    }
                    $list=$new_list;
                    $sortArr=array_column($list,'id');
                    array_multisort($sortArr,SORT_DESC,SORT_NUMERIC,$list);
                    
                }else{
                    foreach ($list as &$nvv){
                        if(strpos($nvv['url'],'village/my/payMentlist?') !== false){
                            $nvv['url'] = str_replace('village/my/payMentlist?','houseMeter/NewCollectMoney/newLiveExpenses?',$nvv['url']);
                        }
                        $url = html_entity_decode($nvv['url']);
                        $nvv['url']=D('House_link')->replace_village_parameter($url,$village_id,$uid,$now_village,$replace_arr);
                    }
                    $list=$database_house_village_nav->handleImg($list,0,$now_village);
                }
                if($list){
                    $sortArr=array_column($list,'sort');
                    array_multisort($sortArr,SORT_DESC,SORT_NUMERIC,$list);
                }
                $nav_list= $list ? array_values($list) : [];
                $block_nav['nav_list']=$nav_list;
            }
            $village_block_nav=$block_nav;
        }
        return $village_block_nav;
    }

    public function get_guess_you_like_goods_set($village_id=0){
        $guess_you_like_goods_set=array('is_show'=>0,'set_from'=>'','set_num'=>4,'lng'=>'','lat'=>'','village_id'=>$village_id);
        if($village_id>0){
            $villageInfo=M('House_village')->where(array('village_id'=>$village_id))->field('village_name,`long`,lat')->find();
            if($villageInfo && $villageInfo['lat']>0){
                $guess_you_like_goods_set['lat']=strval($villageInfo['lat']);
                $guess_you_like_goods_set['lng']=strval($villageInfo['long']);
            }
            $assign_type=D('House_visualize_assign')->house_visualize_assign($village_id,40);
            $house_visualize_assign=$assign_type['type_40'];
            $house_village_nav_moduleDb = D('House_village_nav_module');
            if($house_visualize_assign){
                //小区
                $whereArr=array('block_type_id'=>'guess_you_like_goods','village_id'=>$village_id);
                $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
                $guess_you_like_goods_set['set_from']='village';
                if($nav_set && isset($nav_set['is_show']) && $nav_set['is_show']){
                    $guess_you_like_goods_set['is_show']=intval($nav_set['is_show']);
                    $guess_you_like_goods_set['set_num']=$nav_set['set_num'];
                }
            }else{
                //平台
                $whereArr=array('block_type_id'=>'guess_you_like_goods','village_id'=>'0','is_community_platform'=>'1');
                $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
                $guess_you_like_goods_set['set_from']='community_platform';
                if($nav_set && isset($nav_set['is_show']) && $nav_set['is_show']){
                    $guess_you_like_goods_set['is_show']=intval($nav_set['is_show']);
                    $guess_you_like_goods_set['set_num']=$nav_set['set_num'];
                }
            }
            
        }
        return $guess_you_like_goods_set;
    }
}
?>