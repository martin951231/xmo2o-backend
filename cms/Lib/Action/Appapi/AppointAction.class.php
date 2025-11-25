<?php
/**
 * 
 * 预约服务
 */
class AppointAction extends BaseAction{
    
    public function index(){
        //判断分类信息
        $cat_url =   I('cat_url','all');
        $_GET['page']   =   I('page');
        //判断地区信息
        $area_url =   I('area_url','all');
        $circle_id = 0;
        if($area_url != 'all'){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->returnCode('20045008');
            }
            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->returnCode('20045008');
                }
                $circle_url = $now_circle['area_url'];
                $circle_id = $now_circle['area_id'];
                $area_url = $now_area['area_url'];
            }
            $area_id = $now_area['area_id'];
        }else{
            $area_id = 0;
        }
        //判断排序信息   默认排序就是按照手动设置项排序
        $sort_id =   I('sort_id','juli');
        $long_lat   =   $this->user_long_lat;
        if (empty($long_lat['long']) || empty($long_lat['lat'])) {
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
            $sort_array = array(
                    array('sort_id'=>'defaults','sort_value'=>L_('默认排序')),
                    array('sort_id'=>'appointNum','sort_value'=>L_('按预约数')),
                    array('sort_id'=>'start','sort_value'=>L_('最新发布')),
                    array('sort_id'=>'price','sort_value'=>L_('价格最低')),
                    array('sort_id'=>'priceDesc','sort_value'=>L_('价格最高')),
            );
        } else {
            $sort_array = array(
                    array('sort_id'=>'juli','sort_value'=>L_('离我最近')),
                    array('sort_id'=>'appointNum','sort_value'=>L_('按预约数')),
                    array('sort_id'=>'start','sort_value'=>L_('最新发布')),
                    array('sort_id'=>'price','sort_value'=>L_('价格最低')),
                    array('sort_id'=>'priceDesc','sort_value'=>L_('价格最高')),
            );
        }
        foreach($sort_array as $key=>$value){
            if($sort_id == $value['sort_id']){
                $now_sort_array = $value;
                break;
            }
        }
        //所有分类 包含2级分类
        $all_category_list = D('Appoint_category')->get_all_category();
        //根据分类信息获取分类
        if($cat_url != 'all'){
            $now_category = D('Appoint_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->returnCode('20045009');
            }
            if(!empty($now_category['cat_fid'])){
                $f_category = D('Appoint_category')->get_category_by_id($now_category['cat_fid']);
                $all_category_url = $f_category['cat_url'];
                $category_cat_field = $f_category['cat_field'];
                $get_grouplist_catfid = 0;
                $get_grouplist_catid = $now_category['cat_id'];
            }else{
                $all_category_url = $now_category['cat_url'];
                $category_cat_field = $now_category['cat_field'];
                $get_grouplist_catfid = $now_category['cat_id'];
                $get_grouplist_catid = 0;
            }
        }
        $all_area_list = D('Area')->get_all_area_list();
        C('config.appoint_page_row','10');
        $arr['merchant_store']  =   D('Appoint')->wap_get_appoint_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id);
        if(empty($arr['merchant_store']['group_list'])){
            $arr['merchant_store']['group_list']    =   array();
        }else{
            foreach($arr['merchant_store']['group_list'] as $k => &$v){
                $v['url']   =   $this->config['site_url'].$v['url'];
                $v['payment_money']   =   rtrim(rtrim(number_format($v['payment_money'],2,'.',''),'0'),'.');
                if($v['range'] == 'm'){
                    $v['range'] =   null;
                }
            }
        }
        $this->returnCode(0,$arr);
    }
    
    public  function indexList(){
        $area_url =   I('area_url','all');
        $cat_url =   I('cat_url','all');
        $all_area_lists = D('Area')->get_all_area_list();
        foreach($all_area_lists as $k => $v){
            $all_area_list[]    =   $v;
        }
        $arr['all_area_list']  =   isset($all_area_list)?$all_area_list:array();
        $arr['area_url']    =   isset($area_url)?$area_url:'all';
        $arr['cat_url']    =   isset($cat_url)?$cat_url:'all';
        if($area_url != 'all'){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->returnCode('20045008');
            }
            
            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->returnCode('20045008');
                }
                $circle_url = $now_circle['area_url'];
                $circle_id = $now_circle['area_id'];
                $area_url = $now_area['area_url'];
            }
            $area_id = $now_area['area_id'];
        }else{
            $area_id = 0;
        }
        $arr['now_area']      =   isset($tmp_area)?$tmp_area:null;
        $arr['now_circle']    =   isset($now_circle)?$now_circle:null;
        $sort_id =   I('sort_id','juli');
        $long_lat   =   $this->user_long_lat;
        if (empty($long_lat['long']) || empty($long_lat['lat'])) {
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
            $sort_array = array(
                    array('sort_id'=>'defaults','sort_value'=>L_('默认排序')),
                    array('sort_id'=>'appointNum','sort_value'=>L_('按预约数')),
                    array('sort_id'=>'start','sort_value'=>L_('最新发布')),
                    array('sort_id'=>'price','sort_value'=>L_('价格最低')),
                    array('sort_id'=>'priceDesc','sort_value'=>L_('价格最高')),
            );
        } else {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $location2 = $longlat_class->gpsToBaidu($long_lat['lat'], $long_lat['long']);//转换腾讯坐标到百度坐标
            $long_lat['lat'] = $location2['lat'];
            $long_lat['long'] = $location2['lng'];
            $sort_array = array(
                    array('sort_id'=>'juli','sort_value'=>L_('离我最近')),
                    array('sort_id'=>'appointNum','sort_value'=>L_('按预约数')),
                    array('sort_id'=>'start','sort_value'=>L_('最新发布')),
                    array('sort_id'=>'price','sort_value'=>L_('价格最低')),
                    array('sort_id'=>'priceDesc','sort_value'=>L_('价格最高')),
            );
        }
        foreach($sort_array as $key=>$value){
            if($sort_id == $value['sort_id']){
                $now_sort_array = $value;
                break;
            }
        }
        $arr['sort_array']    =   isset($sort_array)?$sort_array:array();
        $arr['now_sort_array']    =   isset($now_sort_array)?$now_sort_array:null;
        //根据分类信息获取分类
        if($cat_url != 'all'){
            $now_category = D('Appoint_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->returnCode('20045009');
            }
            if(!empty($now_category['cat_fid'])){
                $f_category = D('Appoint_category')->get_category_by_id($now_category['cat_fid']);
                $all_category_url = $f_category['cat_url'];
                $category_cat_field = $f_category['cat_field'];
                $get_grouplist_catfid = 0;
                $get_grouplist_catid = $now_category['cat_id'];
            }else{
                $all_category_url = $now_category['cat_url'];
                $category_cat_field = $now_category['cat_field'];
                $get_grouplist_catfid = $now_category['cat_id'];
                $get_grouplist_catid = 0;
            }
        }
        $arr['now_category']    =    isset($now_category)?$now_category:null;
        //所有分类 包含2级分类
        $category_list = D('Appoint_category')->get_all_category();
        foreach($category_list as $k=>$v){
            foreach($v['category_list'] as $kk => $vv){
                $v['one_category_list'][] =   $vv;
            }
            unset($v['category_list']);
            $all_category_list[]    =   $v;
        }
        $arr['all_category_list']   =    isset($all_category_list)?$all_category_list:array();
        $this->returnCode(0,$arr);
    }
}
?>