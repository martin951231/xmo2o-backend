<?php


namespace app\common\model\service\diypage;

use app\common\model\db\Area;
use app\common\model\db\Diypage;
use app\common\model\db\DiypageFeedCategory;
use app\common\model\db\DiypageModel;
use app\common\model\db\DiypageSearchHot;
use app\common\model\db\MerchantCategory;
use app\common\model\service\AreaService as AreaService;
use app\foodshop\model\db\MerchantStoreFoodshop;
use app\group\model\db\Group;
use app\group\model\db\GroupStore;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\service\MerchantStoreOpenTimeService;
use map\longLat;
use think\facade\Config;

class DiypageModelService
{
    public $diypageModel = null;
    public $diypage = null;
    public $diypageSearchHot = null;
    public $merchantCategory = null;
    public $diypageFeedCategory = null;

    public function __construct()
    {
        $this->diypageModel = new DiypageModel();
        $this->diypage = new Diypage();
        $this->diypageSearchHot = new DiypageSearchHot();
        $this->merchantCategory = new MerchantCategory();
        $this->diypageFeedCategory = new DiypageFeedCategory();
    }

    /**
     * @param $source
     * @param $source_id
     * @return mixed
     * 获得装修组件
     */
    public function getDiypageModel($source, $source_id)
    {
        if ($source == "category") {
            $where = [['is_public', '=', 1], ['is_del', '=', 0]];
            $order = "id asc";
            if($source_id){
                $where1=[['cat_id','=',$source_id],['cat_fid','>',0]];
                $is_exit=(new MerchantCategory())->getOne($where1);
                if(!empty($is_exit)){
                    array_push($where,['type','in',['swiperNav','swiperPic','magicSquare']]);
                }
            }
            $assign['list'] = $this->diypageModel->getSome($where, "type,title as label,icon,fixed,can_del", $order)->toArray();
            return $assign;
        } else {
            $assign['list'] = [];
            return $assign;
        }
    }

    /**
     * @param $where
     * @return bool
     * 是否装修
     */
    public function getDiyPage($where){
        $is_exit=(new Diypage())->getOne($where);
        if(empty($is_exit)){
            return false;
        }else{
            return true;
        }
    }
    /**
     * @param $source
     * @param $source_id
     * @return mixed
     *获得装修详情
     */
    public function getDiypageDetail($source, $source_id, $now_city)
    {
        $where = [['s.source', '=', $source], ['s.source_id', '=', $source_id], ['s.is_del', '=', 0]];

        $msg = $this->diypage->getDiypageDetail($source, $where, "s.source_id,s.source,s.content,m.cat_name,m.cat_fid");

        if (!empty($msg)) {
            $list = unserialize($msg['content']);
            if (!empty($list)) {
                foreach ($list as $key => $val) {
                    $list[$key]['share_image_h5'] = isset($val['share_image_h5']) ? replace_file_domain($val['share_image_h5']) : '';
                    $list[$key]['share_image_wechat'] = isset($val['share_image_wechat']) ? replace_file_domain($val['share_image_wechat']) : '';
                    if($val['type']=='feedModule'){//判断该分类下没有绑定门店则不显示该分类
                        if(isset($val['content']['list'])){
                            $array=[];
                            foreach ($val['content']['list'] as $k=>$v){
                                if(!$v['is_del']){
                                    if(!empty($v['ids'])){
                                        //unset($list[$key]['content']['list'][$k]);
                                        $ay=explode(',',$v['ids']);
                                        $wheres=[['cat_id','in',$ay]];
                                        $count=(new MerchantStore())->getCount($wheres);
                                        if($count==0){
                                            unset($list[$key]['content']['list'][$k]);
                                        }
                                    }
                                    $array[]=$v;
                                }
                            }

                            if(!empty($val['content']['list'])){
                                $list[$key]['content']['list']=$array;
                            }
                        }
                    }

                    if($val['type']=='swiperNav'){
                        if(isset($val['content']['list'])){
                            foreach ($val['content']['list'] as $k=>$v){
                                $list[$key]['content']['list'][$k]['image']=replace_file_domain($v['image']);
                                $list[$key]['content']['list'][$k]['badge_val']= L_($v['badge_val']);
                                $list[$key]['content']['list'][$k]['title']= L_($v['title']);
                            }
                        }
                    }

                    if($val['type']=='porcelainArea'){
                        if(isset($val['content']['list'])){
                            foreach ($val['content']['list'] as $k=>$v){
                                $list[$key]['content']['list'][$k]['image']=replace_file_domain($v['image']);
                                $list[$key]['content']['list'][$k]['title']= L_($v['title']);
                                $list[$key]['content']['list'][$k]['badge_val']= L_($v['badge_val']);
                                $list[$key]['content']['list'][$k]['sub_title']= L_($v['sub_title']);
                            }
                        }
                    }

                    if($val['type'] == 'categoryHeader'){
                        $list[$key]['content']['share_image_h5'] = replace_file_domain($list[$key]['content']['share_image_h5']);
                        $list[$key]['content']['share_image_wechat'] = replace_file_domain($list[$key]['content']['share_image_wechat']);

                        $list[$key]['content']['page_title'] = L_($list[$key]['content']['page_title']);
                        $list[$key]['content']['share_desc'] = L_($list[$key]['content']['share_desc']);
                        $list[$key]['content']['share_title'] = L_($list[$key]['content']['share_title']);
                    }
                }
            }
            $msg['custom'] = $list;
        }

        $msg['source_id']=$source_id;
        $msg['source']=$source;
        $msg['cat_name']="";
        $msg['cat_fid']='0';
        if($source=="category"){
            $wheres=[['cat_id','=',$source_id],['cat_status','=',1]];
            $msg1=(new MerchantCategory())->getOne($wheres);
            if(!empty($msg1)){
                $msg1=$msg1->toArray();
                $msg['cat_fid']=$msg1['cat_fid'];
                $msg['cat_name']=$msg1['cat_name'];
            }
        }
        $where2=[['cat_fid', '=', 0],['cat_status','=',1]];
        //分类
        $ret['index']="cat_id";
        $ret['level']=2;
        $ret['name']=L_('分类');
        $list1=(new MerchantCategory())->getSome($where2, "cat_id,cat_fid,cat_name,cat_url",'cat_id asc')->toArray();
        $arrs=array();
        $child['id']='0';
        $child['name']=L_("全部分类");
        $childs[]=$child;
        $arr['child']=$childs;
        $arr['id']='0';
        $arr['name']=L_("全部");
        $arrs[]=$arr;
        foreach ($list1 as $key=>$val){
            $arr['child']=[];
                $where=[['cat_fid','=',$val['cat_id']],['cat_status','=',1]];
                $ms=$this->getChildCategoryList($where,$val['cat_id']);
                if(!empty($ms)){
                    $arr['child']=$ms;
                    $arr['id']=strval($val['cat_id']);
                    $arr['name']=$val['cat_name'];
                }
            $arrs[]=$arr;
        }
        $ret['list']=$arrs;
        $msg['swiper_list'][]=$ret;

        //附近
        $ret['index']="area_id";
        $ret['level']=2;
        $ret['name']=L_("附近");
        $ret['list']=(new AreaService())->getAreaListByAreaPid1($now_city);
        $msg['swiper_list'][]=$ret;

        $ret['index']="sort";
        $ret['level']=1;
        $ret['name']=L_("排序");
        $item['name']=L_("智能排序");
        $item['id']="defaults";
        $a[]=$item;
        $item['name']=L_("评价排序");
        $item['id']="score";
        $a[]=$item;
        $item['name']=L_("离我最近排序");
        $item['id']="juli";
        $a[]=$item;
        $item['name']=L_("人气排序");
        $item['id']="popularity";
        $a[]=$item;
        $ret['list']=$a;
        $msg['swiper_list'][]=$ret;
        return $msg;
    }

    public function getChildCategoryList($where1,$id){
        $list1=(new MerchantCategory())->getSome($where1, "cat_id,cat_fid,cat_name,cat_url",'cat_id asc')->toArray();
        $arrs=array();
        $put['id']=strval($id);
        $put['name']="全部";
        $arrs[]=$put;
        foreach ($list1 as $key=>$val){
                $arr['id']=strval($val['cat_id']);
                $arr['name']=$val['cat_name'];
                $arrs[]=$arr;
        }
        return $arrs;
    }
    /**
     * @param $data
     * @return array|bool
     * 保存装修数据
     */
    public function saveDiypage($data)
    {
        if ($data['custom'] == "") {//必填项
            return false;
        }
        if ($data['source'] == 'category') {
            $where = [['source', '=', $data['source']], ['source_id', '=', $data['source_id']]];
            if($data['custom']){
                foreach ($data['custom'] as &$v){
                    if(isset($v['content']['list'])){
                        foreach ($v['content']['list'] as &$vv){
                            if(isset($vv['image'])){
                                $vv['image'] = replace_file_domain($vv['image']);
                            }
                        }
                    }
                }
            }
            $data['content'] = serialize($data['custom']);
            unset($data['custom']);
            $exict = $this->diypage->getOne($where);//判断是否存在，是就修改，否就新增
            if (empty($exict)) {//新增
                $data['create_time'] = time();
                return $this->diypage->add($data);
            } else {
                $data['update_time'] = time();
                return $this->diypage->updateThis($where, $data);
            }
        } else {
            return [];
        }
    }

    /**
     * @param $source
     * @param $source_id
     * @return mixed
     * 获得热搜词列表
     */
    public function getSearchHotList($source, $source_id)
    {
        if ($source == 'category') {
            $where = [['source', '=', $source], ['source_id', '=', $source_id]];
        } else {
            $where = [['source_id', '=', $source_id]];
        }
        $assign['list'] = $this->diypageSearchHot->getSome($where, "name")->toArray();
        return $assign;
    }

    /**
     * @param $cat_id
     * @return mixed
     * 获得店铺子分类列表
     */
    public function getMerchantCategoryChildList($cat_id)
    {
        $where = [['cat_fid', '=', $cat_id],['cat_status', '=', 1]];
        $assign['list'] = $this->merchantCategory->getSome($where, "cat_id,cat_fid,cat_name,cat_url")->toArray();
        if(!empty($assign['list'])){
             foreach ($assign['list'] as $k=>$v){
                 $assign['list'][$k]['cat_url']=cfg('site_url') ."/packapp/platn/pages/store/v1/classifySecondary/index?source_id=".$v['cat_id'];
             }
        }
        return $assign;
    }

    /**
     * @param $data
     * @return array
     * 用户端主分类装修获得店铺列表
     */
    public function getFeedStoreList($data)
    {
        $assign = array();//返回值
        $cat_ids = array();//收集所有分类id
        $arrs = array();
        $where = [['category_id', '=', $data['category_id']]];
        $msg = $this->diypageFeedCategory->getOne($where);
        if (!empty($msg)) {
            $msg = $msg->toArray();
            if (!empty($msg['ids'])) {
                $cat_ids = explode(",", $msg['ids']);
                $where = [['cat_fid', 'in', $msg['ids']]];
                $category = (new MerchantCategory())->getSome($where);//找出全部店铺二级分类
                foreach ($category as $k => $v) {
                    $cat_ids[] = $v['cat_id'];
                }

            } else {
                // $where = [['cat_fid', '=', $msg['cat_id']]];
                $where = [['cat_fid', '>', 0]];// 全部子分类
                $category = (new MerchantCategory())->getSome($where);//找出全部店铺二级分类
                foreach ($category as $k => $v) {
                    $cat_ids[] = $v['cat_id'];
                }
            }

            $long = $data['user_long'];//经度
            $lat = $data['user_lat'];//纬度
            $assign['show_type'] = $show_type = $msg['show_type'];///内容展示样式
            $now_city = $data['now_city'] > 0 ? $data['now_city'] : cfg('now_city');
            $city_where = ['s.city_id', '=', $now_city];

            //修复一下这里now_city前端传过来的可能是区县，所以可能需要转一下
            $thisArea = (new AreaService())->getOne(['area_id' => $now_city]);
            if ($thisArea && $thisArea['area_type'] == 3) {
                $now_city = $thisArea['area_id'];
                $city_where = ['s.area_id', '=', $thisArea['area_id']];
            }

            $where1 = [['s.cat_id', 'in', $cat_ids], $city_where, ['s.status', '=', 1], ['m.status', '=', 1]];
            $fields = 's.*,c.cat_name';
            $order = 's.add_time';
            if($long > 0 && $lat >0 && $msg['show_sort_type'] == 2){
                $fields .=",(6371 * ACOS(COS(RADIANS($lat)) * COS(RADIANS(s.lat)) * COS(RADIANS(s.long) - RADIANS($long)) + SIN(RADIANS($lat)) * SIN(RADIANS(s.lat)))) AS distance";
            }
            switch ($msg['show_sort_type']){
                case DiypageFeedCategory::SORE_TYPE_SALES:
                    $order = 's.fifteen_day_sale_num DESC';
                    break;
                case DiypageFeedCategory::SORE_TYPE_DISTANCE:
                    $long > 0 && $lat >0 && $order = 'distance ASC';
                    break;
                case DiypageFeedCategory::SORE_TYPE_SCORE:
                    $order = 's.score DESC';
                    break;
                case DiypageFeedCategory::SORE_TYPE_ADD_TIME:
                    $order = 's.add_time ASC';
                    break;
            }
            $store_list = (new MerchantStore())
                ->alias('s')
                ->where($where1)
                ->field($fields)
                ->join('merchant_category c', 's.cat_id = c.cat_id')
                ->join('merchant m', 'm.mer_id = s.mer_id')
                ->order($order)
                ->page($data['page'],10)
                ->select()
                ->toArray();
            $assign['page_size'] = 10;
            foreach ($store_list as $key => $value) {
                $arr['area_name'] = '';
                if (!empty($value['circle_id'])) {
                    $arr['area_name'] = (new Area())->where(['area_id' => $value['circle_id']])->value('area_name') ?? '';
                }
                $arr['store_id'] = $value['store_id'];
                $arr['name'] = $value['name'];
                $arr['image'] = $value['logo'] != "" ? replace_file_domain($value['logo']) : "";
                if(!empty($value['pic_info'])){
                   $ex=explode(';',$value['pic_info']);
                    $arr['image'] =replace_file_domain($ex[0]);
                }
                if($value['score']*1){
                    $arr['score'] = strval($value['score']);
                }else{
                    $arr['score'] = strval(5.0);
                }
                if ($value['long'] > 0 && $value['lat'] > 0 && $long > 0 && $lat > 0) {
                    $arr['range'] = $this->getDistance($value['lat'], $value['long'], $lat, $long);
                } else {
                    $arr['range'] = '';
                }
                $arr['address'] = $value['adress'];
                $arr['url'] = get_base_url('pages/store/v1/home/index?store_id=' . $value['store_id'], true);//跳转连接
                $arr['cat_name'] = $value['cat_name'];
                $arr['area_name'] = $value['area_name']??'';
                if ($value['foodshop_on_sale'] == 1) {//外卖餐饮
                    $where=[['store_id','=',$value['store_id']]];
                    $foods=(new MerchantStoreFoodshop())->getOne($where,'is_queue,is_book');
					if(!empty($foods)){
						$foods = $foods->toArray();
						$arr['bus_label'] = $this->bus_label($foods);
					}else{
						$arr['bus_label'] = [];
					}
                } else {
                    $arr['bus_label'] = [];
                }
                if ($show_type == 1) {//样式1
                    $arr['discount_list'] = [];//样式2使用
                    $group_list = $this->groupGoods($value['store_id']);
                    if (!empty($group_list['list'])) {
                        $arr['group_goods']['count'] = $group_list['count'];
                        $items=array();
                        foreach ($group_list['list'] as $ke => $va) {
                            $item['group_id'] = $va['group_id'];
                            $item['name'] = $va['name'];
                            $item['price'] = $va['price'];
                            $item['sale_count'] = $va['sale_count'];
                            $item['group_cate'] = $va['group_cate'];
                            $item['old_price'] =$va['old_price'];
                            if ($va['group_cate'] == "normal") {
                                $item['tag'] = "惠";
                            } elseif ($va['group_cate'] == "booking_appoint") {
                                $item['tag'] = "订";
                            } else {
                                $item['tag'] = "券";
                            }
                            if(!empty($item['group_cate']) && !empty($item['name'])){//去掉空白的部分
                                $items[] = $item;
                            }
                        }
                        $arr['group_goods']['list'] = $items;
                    } else {
                        $arr['group_goods']['count'] = 0;
                        $arr['group_goods']['list'] = [];
                    }

                } else if ($show_type == 2) {//样式2//样式1使用
                    $arr['group_goods']['count'] = 0;
                    $arr['group_goods']['list'] = [];
                    $my_get_arr=$this->discountList($value);
                    $arr['discount_list'] = $my_get_arr;
                } else {
                    $arr['group_goods']['count'] = 0;
                    $arr['group_goods']['list'] = [];//样式1使用
                    $arr['discount_list'] = [];//样式2使用
                }

                $arrs[] = $arr;
            }
            $assign['list'] = $arrs;
        }
        return $assign;
    }

    /**
     * @param $data
     * @return array
     * 子分类装修获得店铺列表
     */
    public function getCategoryStoreList($data,$pageSIze)
    {
        $assign = array();//返回值
        $arrs = array();
        $items = array();
        $cat_ids = array();//二级分类id
        $long = $data['user_long'];//经度
        $lat = $data['user_lat'];//纬度
        $order = array();//排序
        $now_city = $data['now_city'] > 0 ? $data['now_city'] : cfg('now_city');
        $sort = isset($data['sort']) ? $data['sort'] : '';
        $keywords = $data['keywords']??'';
        // 搜索字段
        $condition_field = 's.*';
        //搜索条件
        $condition_where = 's.status=1 AND (s.area_id='.$now_city.' OR s.city_id='. $now_city.') AND mm.status<4';
        if ($data['cat_id'] == 0) {//全部二级分类
            $condition_where .= " AND s.cat_fid>0";
        }

        if (!empty($data['area_id'])) {//选好区域
            $condition_where .= " AND s.circle_id=" . $data['area_id'];
        }

        if (!empty($data['cat_id']) && empty($data['cat_fid'])) {
            $condition_where .= ' AND (s.cat_id IN (' . $data['cat_id'] . ') OR s.cat_fid IN ('.$data['cat_id'].'))';
        }elseif (!empty($data['cat_id']) && !empty($data['cat_fid'])){
            $condition_where .= ' AND s.cat_id IN (' . $data['cat_id'] . ')';
        }

        if (!empty($data['cat_fid'])) {// 分类父id
            $condition_where .= ' AND s.cat_fid IN (' . $data['cat_fid'] . ')';
        }
        // 经纬度
        if ($long > 0 && $lat > 0) {
            if (in_array($sort, ['juli', 'defaults', 'score', 'popularity'])) {
                $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
            }
        } else {
            $sort = $sort == 'juli' ? '' : $sort;
        }
        
        //搜索关键字
        if($keywords){
            $condition_where .= ' AND mm.name like "%'.$keywords.'%"';
        }

        //排序
        switch ($sort) {
            case 'score'://店铺评分
                $order['s.score'] = 'DESC';
                if ($lat > 0 && $long > 0) {
                    $order['juli'] = 'ASC';
                }
                $order['s.last_time'] = 'DESC';
                break;
            case 'juli'://距离
                $order['juli'] = 'ASC';
                $order['s.last_time'] = 'DESC';
                break;
            case 'popularity'://人气排序
                $order['s.relation_num'] = 'DESC';
                $order['s.fifteen_day_sale_num'] = 'DESC';
                $order['s.fifteen_day_reply_num'] = 'DESC';
                break;
            default://智能排序
                $order['s.sort'] = 'DESC';
                if ($lat > 0 && $long > 0) {
                    $order['juli'] = 'ASC';
                }
                $order['s.last_time'] = 'DESC';
        }
        $store_list = (new MerchantStore())->getStoreByCategoryList($condition_where, $order, $condition_field, $data['page'],$pageSIze=10);//店铺基本信息
	$assign['page_size'] = $pageSIze;
        // $assign['total_page'] = count($store_list) > 0 ? (ceil(count($store_list) / cfg(Config::get('api.page_size')))) : 1;

        foreach ($store_list as $key => $value) {
            $arr['status'] =$value['status'];
            $arr['store_id'] = $value['store_id'];
            $arr['name'] = $value['name'];
            $pics = array_values(array_filter(explode(';', $value['pic_info'])));
            $arr['image'] = $pics ? replace_file_domain($pics[0]) : "";
            $arr['score'] = strval($value['score'])=='0.0'?'5':strval($value['score']);
            if ($value['long'] > 0 && $value['lat'] > 0 && $long > 0 && $lat > 0) {
                $arr['range'] = $this->getDistance($value['long'], $value['lat'], $long, $lat);
            } else {
                $arr['range'] = '';
            }
            $arr['address'] = $value['adress'];
            $arr['url'] = get_base_url('pages/store/v1/home/index?store_id=' . $value['store_id'], true);	//跳转连接
            $arr['cat_name'] = (new MerchantCategory())->getVal([['cat_id','=',$value['cat_id']]], 'cat_name');
            $arr['area_name'] = $value['area_name'];
            if ($value['foodshop_on_sale'] == 1) {//外卖餐饮
                $arr['bus_label'] = $this->bus_label($value);
            } else {
                $arr['bus_label'] = [];
            }
            $arr['group_goods'] = [
                'list'=>[],
                'count'=>0
            ];//样式1使用
            $arr['discount_list'] = [];//样式2使用
            $group_list = $this->groupGoods($value['store_id']);
            if (!empty($group_list['list'])) {
                $items = $item = [];
                $arr['group_goods']['count'] = $group_list['count'];
                foreach ($group_list['list'] as $ke => $va) {
                    $item['group_id'] = $va['group_id'];
                    $item['name'] = $va['name'];
                    $item['price'] = $va['price'];
                    $item['old_price'] = $va['old_price'];
                    $item['sale_count'] = $va['sale_count'];
                    $item['group_cate'] = $va['group_cate'];
                    if ($va['group_cate'] == "normal") {
                        $item['tag'] = "团";
                    } elseif ($va['group_cate'] == "booking_appoint") {
                        $item['tag'] = "订";
                    } else {
                        $item['tag'] = "券";
                    }
                    $items[] = $item;
                }
                $arr['group_goods']['list'] = $items;
            }
            $arrs[] = $arr;
        }
        $assign['list'] = $arrs;
        return $assign;
    }

    /**
     * @param $cat_id
     * @return mixed
     * 获得feed流导航分类列表
     */
    public function getFeedCategoryList($cat_id)
    {
        $where = [['cat_id', '=', $cat_id]];
        $msg['list'] = $this->diypageFeedCategory->getSome($where,true,'sort desc')->toArray();
        return $msg;
    }

    //右侧标识
    public function bus_label($store)
    {
        $returnArr = [];
        // 排号
        if (isset($store['is_queue']) && $store['is_queue'] == 1) {
            $returnArr[] = [
                'type' => 'queue',
                'name' => '排',
            ];
        }

        // 外卖
        // 外卖优惠
        /*if ($store['shop_on_sale'] == 1 && $store['have_shop'] == 1) {
            $returnArr[] = [
                'type' => 'shop',
                'name' => '外',
            ];
        }*/

        // 预订
        if (isset($store['is_book']) && $store['is_book'] == 1) {
            $returnArr[] = [
                'type' => 'book',
                'name' => '订',
            ];
        }

        // 快速买单

        /*if (cfg('pay_in_store') && $store['discount_txt']) {
            $returnArr[] = [
                'type' => 'check',
                'name' => '买',
            ];
        }*/

        return $returnArr;
    }

    /**
     * @param $s_lat
     * @param $s_long
     * @param $lat
     * @param $long
     * 距离
     */
    public function getDistance($s_lat, $s_long, $lat, $long)
    {
        $location2 = (new longLat())->gpsToBaidu($s_lat, $s_long);//转换腾讯坐标到百度坐标
        $jl = get_distance($location2['lat'], $location2['lng'], $lat, $long);
        //$returnArr['range'] = get_range($jl);
        return  get_range($jl,false);
    }

    /**
     * @param $store_id
     * @return mixed
     * 取相应分组的团购商品
     */
    public function groupGoods($store_id)
    {
        $where = [['a.store_id', '=', $store_id], ['g.group_cate', '<>', 'course_appoint'], ['g.status', '=',1]];
        $list = (new GroupStore())->getGroupTypeMsg($where);
        return $list;
    }

    /**
     * @param $store
     * @return array
     * 导航分类样式2
     */
    public function discountList($store)
    {
        $arr = array();
        //外卖优惠
        if ($store['shop_on_sale'] == 1 && $store['have_shop'] == 1) {
            $returnArr=array();
            $all_get=array();
            $shop_discount = (new \app\merchant\model\service\ShopDiscountService())->getDiscounts($store['mer_id'], $store['store_id']);
            if ($shop_discount) {
                $returnArr['type'] = 'shop';
                $shop_discount = (new \app\merchant\model\service\ShopDiscountService())->formartDiscount($shop_discount, $store['store_id']);
                $shop_discount = (new \app\merchant\model\service\ShopDiscountService())->simpleParseCoupon($shop_discount['coupon_list']);
                if(empty($shop_discount)){
                    $returnArr['list'] = [];
                }else{
                    foreach ($shop_discount as $key=>$value){
                        $arr1['name']=$value['text'];
                        $all_get[]=$arr1;
                    }
                }
                $returnArr['list'] = $all_get;
            }
            if(!empty($returnArr)){
                $arr[] = $returnArr;
            }
        }

        //快速买单优惠
        if (cfg('pay_in_store') && $store['discount_txt']) {
            $returnArr['type'] = 'store';
            // 快速买单优惠
            $store_pay_discount = (new \app\merchant\model\service\MerchantStoreService())->getStoreQuickDiscount(unserialize($store['discount_txt']));
            //$returnArr['list'][] = $store_pay_discount ? ['name' => $store_pay_discount['name']] : [];
            if(empty($store_pay_discount)){
                $returnArr['list'] = [];
            }else{
                $arr1['name']=$store_pay_discount['name'];
                $all_get1[]=$arr1;
                $returnArr['list']=$all_get1;
            }
            $arr[] = $returnArr;
        }

        if(!empty($arr)){
            foreach ($arr as $k=>$v){
                if(empty($v['list'])){
                    unset($arr[$k]);
                }
            }
        }
        return $arr;
    }
}