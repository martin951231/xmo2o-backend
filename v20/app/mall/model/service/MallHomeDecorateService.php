<?php
/**
 * MallHomeDecorateService.php
 * 新版商城首页装修service
 * Create on 2020/10/20 15:07
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\common\model\service\AreaService;
use app\common\model\service\live\LiveGoodService;
use app\mall\model\db\LiveGoods;
use app\mall\model\db\MallAdver;
use app\mall\model\db\MallAdverCategory;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoodsArea;
use app\mall\model\db\MallGoodsActivityBanner;
use app\mall\model\db\MallGoodsCategoryBanner;
use app\mall\model\db\MallNewBargainAct as MallNewBargainActModel;
use app\mall\model\db\MallNewGroupAct;
use app\mall\model\db\MallRecommend;
use app\mall\model\db\MallRecommendGoods;
use app\mall\model\db\MallSixAdver;
use app\mall\model\db\MallSixAdverGoods;
use app\mall\model\service\activity\MallLimitedActService;
use app\mall\model\service\activity\MallNewGroupActService;
use app\mall\model\service\WxappOtherService;
use think\facade\Db;
use app\common\model\service\percent_rate\PercentRateService;

class MallHomeDecorateService
{
    public function __construct()
    {
        $this->mallAdverModel = new MallAdver();
        $this->mallAdverCategory = new MallAdverCategory();
        $this->areaService = new AreaService();
        $this->wxappOtherService = new WxappOtherService();
        $this->appOtherService = new AppOtherService();
        $this->mallSixAdverModel = new MallSixAdver();
        $this->mallSixAdverGoodsModel = new MallSixAdverGoods();
        $this->mallRecommendModel = new MallRecommend();
        $this->mallRecommendGoodsModel = new MallRecommendGoods();
    }
    //轮播图、导航列表、单图广告

    /**
     * 获取列表
     * @param $cat_key
     * @param $systemUser
     * @return array
     * @throws \think\Exception
     */
    public function getList($cat_key, $systemUser)
    {
        if (empty($cat_key)) {
            throw new \think\Exception('缺少cat_key参数');
        }
        $where_cat = ['cat_key' => $cat_key];
        $now_category = $this->mallAdverCategory->getById(true, $where_cat);
        $arr['now_category'] = $now_category;
        $many_city = cfg('many_city');
        $where = [['cat_id', '=', $now_category['cat_id']]];
        if ($systemUser['area_id']) {
            $area_id = $systemUser['area_id'];
            if ($systemUser['level'] == 1) {
                $temp = $this->areaService->getOne(['area_id' => $systemUser['area_id']]);
                if ($temp['area_type'] == 1) {
                    $city_list = $this->areaService->getAreaListByCondition(['area_pid' => $temp['area_id']]);
                    $area_id = array();
                    foreach ($city_list as $value) {
                        $area_id[] = $value['area_id'];
                    }
                } else if ($temp['area_type'] == 2) {
                    $area_id = $temp['area_id'];
                } else {
                    $area_id = $temp['area_pid'];
                }
            }
            if (is_array($area_id)) {
                array_push($where, ['city_id', 'in', $area_id]);
            } else {
                array_push($where, ['city_id', '=', $area_id]);
            }
        }
        $order = ['sort' => 'DESC', 'id' => 'DESC'];
        $adver_list = $this->mallAdverModel->getByCondition(true, $where, $order);
        if (!empty($adver_list)) {
            if ($many_city == 1 && !empty($adver_list)) {
                foreach ($adver_list as $key => $v) {
                    $city = $this->areaService->getOne(['area_id' => $v['city_id']]);
                    if (empty($city)) {
                        $adver_list[$key]['area_name'] = '通用';
                    } else {
                        $adver_list[$key]['area_name'] = $city['area_name'];
                    }
                }
            }
            //处理图片
            foreach ($adver_list as $key => $v) {
                $adver_list[$key]['pic'] = $v['pic'] ? replace_file_domain($v['pic']) : '';
                $adver_list[$key]['last_time'] = date('Y-m-d H:i:s', $adver_list[$key]['last_time']);
            }
            $arr['adver_list'] = $adver_list;
            $arr['many_city'] = $many_city;
        }
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 编辑或新增
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function addOrEdit($param)
    {
        if (empty($param['name'])) {
            throw new \think\Exception('缺少name参数');
        }
        if (empty($param['url'])) {
            throw new \think\Exception('缺少url参数');
        }
        if (empty($param['cat_key'])) {
            throw new \think\Exception('缺少cat_key参数');
        }
        if ($param['currency'] == 1) {
            $param['province_id'] = 0;
            $param['city_id'] = 0;
        } else {
            if (!empty($param['areaList'])) {
                $param['province_id'] = $param['areaList'][0];
                $param['city_id'] = $param['areaList'][1];
            } else {
                $param['currency'] == 1;
                $param['province_id'] = 0;
                $param['city_id'] = 0;
            }
        }
        //没图片使用默认图片地址
        if (empty($param['pic'])) {
            $param['pic'] = '/v20/public/static/mall/mall_platform_default_decorate.png';
        }
        unset($param['areaList']);
        $where_cat = ['cat_key' => $param['cat_key']];
        $now_category = $this->mallAdverCategory->getById(true, $where_cat);
        $param['cat_id'] = $now_category['cat_id'];
        if (empty($param['cat_id'])) {
            throw new \think\Exception('缺少cat_id参数');
        }
        unset($param['cat_key']);
        // app打开其他小程序需要获取原始id
        if ($param['app_wxapp_id']) {
            $wxapp = $this->wxappOtherService->getById(true, ['appid' => $param['app_wxapp_id']]);
            $param['app_wxapp_username'] = $wxapp['username'];
        }

        $param['last_time'] = time();
        $param['url'] = htmlspecialchars_decode($param['url']);

        if (empty($param['id'])) {
            //添加
            $res = $this->mallAdverModel->addOne($param);
        } else {
            //编辑
            if (stripos($param['pic'], 'http') !== false) {
                $param['pic'] = '/upload/' . explode('/upload/', $param['pic'])[1];
            }
            $res = $this->mallAdverModel->editOne(['id' => $param['id']], $param);
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * 编辑或参看时的一些参数
     * @param $id
     * @return array
     * @throws \think\Exception
     */
    public function getEdit($id)
    {
        if (!empty($id)) {
            $where = ['id' => $id];
            $now_adver = $this->mallAdverModel->getById(true, $where);
            if (!empty($now_adver)) {
                $now_adver['pic'] = $now_adver['pic'] ? replace_file_domain($now_adver['pic']) : '';
            }
            $arr['now_adver'] = $now_adver;
            if (empty($now_adver)) {
                throw new \think\Exception('该广告不存在');
            }
            $where_cat = ['cat_id' => $now_adver['cat_id']];
            $now_category = $this->mallAdverCategory->getById(true, $where_cat);
            $arr['now_category'] = $now_category;
        }
        $many_city = cfg('many_city');
        $arr['many_city'] = $many_city;
        //小程序列表
        $wxapp_list = $this->wxappOtherService->getAll();
        $arr['wxapp_list'] = $wxapp_list;
        //ios列表
        $app_list = $this->appOtherService->getAll();
        $arr['app_list'] = $app_list;
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 添加时的一些参数
     * @param $cat_id
     * @return array
     * @throws \think\Exception
     */
    public function getAdd($cat_id)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('缺少cat_id参数');
        }
        $many_city = cfg('many_city');
        $arr['many_city'] = $many_city;
        $where_cat = ['cat_id' => $cat_id];
        $now_category = $this->mallAdverCategory->getById(true, $where_cat);
        $arr['now_category'] = $now_category;
        //小程序列表
        $wxapp_list = $this->wxappOtherService->getAll();
        $arr['wxapp_list'] = $wxapp_list;
        //ios列表
        $app_list = $this->appOtherService->getAll();
        $arr['app_list'] = $app_list;
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     *删除
     * @param $id
     * @return bool
     * @throws \think\Exception
     */
    public function getDel($id)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $where = ['id' => $id];
        $res = $this->mallAdverModel->getDel($where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    //六宫格

    /**
     * 获取列表
     * @return array
     */
    public function getSixList($limit, $type,$village_id = 0,$grid = 6)
    {
        $order = ['sort' => 'DESC', 'id' => 'DESC'];
        if ($type == 1) {
            $arr = $this->mallSixAdverModel->getByCondition($order, true, [['start_time', '<', time()], ['end_time', '>', time()],['village_id','=',$village_id],['grid_num','=',$grid]], $limit);
        } elseif ($type == 2) {
            $arr = $this->mallSixAdverModel->getByCondition($order, true, [['village_id','=',$village_id],['grid_num','=',$grid]], $limit);
        }
        if (!empty($arr)) {
            foreach ($arr as $key => $val) {
                switch ($val['type']) {
                    case 'rec':
                        $arr[$key]['type_txt'] = '商品热推活动';
                        break;
                    case 'video':
                        $arr[$key]['type_txt'] = '短视频活动';
                        break;
                    case 'live':
                        $arr[$key]['type_txt'] = '直播活动';
                        break;
                    case 'limited':
                        $arr[$key]['type_txt'] = '秒杀活动';
                        break;
                    case 'bargain':
                        $arr[$key]['type_txt'] = '砍价活动';
                        break;
                    case 'group':
                        $arr[$key]['type_txt'] = '拼团活动';
                        break;
                }
                $arr[$key]['start_time'] = date('Y-m-d H:i', $val['start_time']);
                $arr[$key]['end_time'] = date('Y-m-d H:i', $val['end_time']);
                if (intval($limit) != 0) {
                    if ($val['type'] == 'limited') {
                        $goods = (new MallLimitedActService())->activityList(0, 1, 0, 4,"k.act_id");
                    } elseif ($val['type'] == 'bargain') {
                        $goods = (new MallNewBargainActModel())->getListDec();
                    } elseif ($val['type'] == 'group') {
                        $goods = (new MallNewGroupAct())->getRecGroupListDec();
                    } else {
                        $goods = $this->mallSixAdverGoodsModel->getDetail(4, [['s.adver_id', '=', $val['id']]], $val['type'], 's.sort DESC');
                    }
                    if (count($goods) < 4) {
                        unset($arr[$key]);
                    }
                } else {
                    $goods = $this->mallSixAdverGoodsModel->getDetail(0, [['s.adver_id', '=', $val['id']]], $val['type'], 's.sort DESC');
                }
                if (!empty($arr[$key])) {
                    if (!empty($goods)) {
                        foreach ($goods as $k => $v) {
                            $goods[$k]['image'] = isset($goods[$k]['goods_image']) ? $goods[$k]['goods_image'] : $goods[$k]['image'];
                            $goods[$k]['image'] = $goods[$k]['image'] ? replace_file_domain($goods[$k]['image']) : '';
                        }
                    }
                    $arr[$key]['related_goods'] = $goods;
                }
                // 处理参数
                if($val['grid_num'] == 3){
                    $arr[$key]['bg_image'] = replace_file_domain($val['bg_image']);
                }
                if(in_array($val['grid_num'],[3,4])){
                    $arr[$key]['icon_image'] = replace_file_domain($val['icon_image']);
                }
            }
        }
        if (!empty($arr)) {
            return array_values($arr);
        } else {
            return [];
        }
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取活动商品
     */
    public function getActGoods($param)
    {
        if (empty($param['record_id'])) {
            throw new \think\Exception('参数缺失');
        }
        $param['type'] = '';
        $param['start_time'] = 0;
        $param['end_time'] = 0;
        if ($param['source'] == 'platform_six') {
            //获取记录信息
            $sixInfo = $this->mallSixAdverModel->getOne(['id' => $param['record_id']]);
            if (empty($sixInfo)) {
                throw new \think\Exception('未查到记录信息');
            }
            $param['type'] = $sixInfo['type'];
            $param['start_time'] = $sixInfo['start_time'];
            $param['end_time'] = $sixInfo['end_time'];
        } elseif ($param['source'] == 'platform_rec') {
            $recInfo = $this->mallRecommendModel->getOne(['id' => $param['record_id']]);
            if (empty($recInfo)) {
                throw new \think\Exception('未查到记录信息');
            }
        }
        if (in_array($param['type'], ['group', 'bargain', 'limited'])) {
            $res = (new MallGoodsService())->getDecorateActGoods($param);
        } elseif (in_array($param['type'], ['live', 'video'])) {
            $res = $this->getLiveGoods($param);
        } else { //全部商品  目前是热推和猜你喜欢
            $res = (new MallGoodsService())->getPlatformGoodsList($param['keyword'], '', '', $param['page'], $param['pageSize'], $param['cat_id']);
        }
        return $res;
    }

    /**
     * @author zhumengqun
     * 商城店铺首页装修-获取所有的直播和短视频商品
     */
    public function getLiveGoods($param)
    {
        $where = [['a.status', '=', 1], ['b.status', '=', 1], ['b.is_del', '=', 0], ['a.business', '=', 'mall3'], ['b.cat_id', '=', $param['cat_id']]];
        if (!$param['keyword'] !== '') {
            array_push($where, ['b.name', 'like', '%' . $param['keyword'] . '%']);
        }
        $field = "a.*, b.goods_id,b.image,b.store_id,b.mer_id,b.name,b.min_price,b.max_price,b.price,b.stock_num,b.goods_type";
        $arr = (new LiveGoods())->getLiveGoods($where, $field, $param['page'], $param['pageSize']);
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                $arr[$k]['image'] = $arr[$k]['image'] ? replace_file_domain($v['image']) : '';
            }
        }
        $count = (new LiveGoods())->getLiveGoodsCount($where);
        $list['list'] = $arr;
        $list['count'] = $count;
        return $list;
    }

    /**
     * 编辑或添加六宫格
     * @param $param
     * @throws \think\Exception
     */
    public function addOrEditSixAdver($param)
    {
        if (empty($param['type'])) {
            throw new \think\Exception('缺少type参数');
        }
        //判断六宫格活动是否重复新建
        if (empty($param['id'])) {
            $types = (new MallSixAdver())->getByCondition('id DESC', 'type', [['village_id','=',0]], 0);
            if (!empty($types)) {
                if (in_array($param['type'], array_column($types, 'type'))) {
                    return 100; //标识该类型的活动已新建
                }
            }
        }
        if (empty($param['name'])) {
            throw new \think\Exception('缺少name参数');
        }
        if (empty($param['subname'])) {
            throw new \think\Exception('缺少subname参数');
        }
        if (empty($param['start_time'])) {
            throw new \think\Exception('缺少start_time参数');
        }
        if (empty($param['end_time'])) {
            throw new \think\Exception('缺少end_time参数');
        }
        switch ($param['type']) {
            case 'group':
                $param['bg_color'] = ' #FF8329';
                break;
            case 'limited':
                $param['bg_color'] = '#EA4333';
                break;
            case 'bargain':
                $param['bg_color'] = ' #00DA84';
                break;
            case 'rec':
                $param['bg_color'] = '#FF5F85';
                break;
            case 'live':
                $param['bg_color'] = '#47BCFF';
                break;
            case 'video':
                $param['bg_color'] = '#FFCB35';
                break;
        }
        $param['start_time'] = strtotime($param['start_time']);
        $param['end_time'] = strtotime($param['end_time']);
        if (empty($param['id'])) {
            //添加
            $res = $this->mallSixAdverModel->addOne($param);
            if ($res === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        } else {
            //编辑
            $where = ['id' => $param['id']];
            unset($param['id']);
            $res = $this->mallSixAdverModel->updateOne($param, $where);
            if ($res === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        }
        return true;
    }

    /**
     * 添加关联商品
     */
    public function addRelatedGoods($param)
    {
        if ($param['source'] == 'platform_six') {
            //删除该活动下的商品再新增
            $where = ['adver_id' => $param['id']];
            $res_del = $this->mallSixAdverGoodsModel->delSome($where);
            if ($res_del === false) {
                throw new \think\Exception('操作失败，请重试');
            }
            if (!empty($param['goods_ids'])) {
                foreach ($param['goods_ids'] as $val) {
                    $data['adver_id'] = $param['id'];
                    $data['goods_id'] = $val;
                    $res_add = $this->mallSixAdverGoodsModel->addOne($data);
                    if ($res_add === false) {
                        throw new \think\Exception('操作失败，请重试');
                    }
                }
            }
        } elseif ($param['source'] == 'platform_rec') {
            //删除该活动下的商品再新增
            $where = ['recommend_id' => $param['id']];
            // $res_del = $this->mallRecommendGoodsModel->delSome($where);
//            if ($res_del === false) {
//                throw new \think\Exception('操作失败，请重试');
//            }
            if (!empty($param['goods_ids'])) {
                foreach ($param['goods_ids'] as $val) {
                    $data['recommend_id'] = $param['id'];
                    $data['goods_id'] = $val;
                    $count = $this->mallRecommendGoodsModel->getGoodsCount($data);
                    if($count == 0){
                        $res_add = $this->mallRecommendGoodsModel->addOne($data);
                        if ($res_add === false) {
                            throw new \think\Exception('操作失败，请重试');
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 六宫格编辑时的数据
     * @param $id
     * @throws \think\Exception
     */
    public function getSixEdit($id)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $where = ['id' => $id];
        $arr = $this->mallSixAdverModel->getOne($where);
        if (!empty($arr)) {
            $goods = $this->mallSixAdverGoodsModel->getDetail(0, [['s.adver_id', '=', $arr['id']]], $arr['type'], 's.sort DESC');
            if (!empty($goods)) {
                foreach ($goods as $k => $v) {
                    $goods[$k]['image'] = cfg('url') . '/' . $v['image'];
                }
            }
            $arr['start_time'] = date('Y-m-d H:i:s', $arr['start_time']);
            $arr['end_time'] = date('Y-m-d H:i:s', $arr['end_time']);
            $arr['related_goods'] = $goods;
        }
        return $arr;
    }

    /**
     * @param $id
     * @return bool
     * @throws \think\Exception
     * 删除六宫格
     */
    public function delSixAdver($id)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $where = ['id' => $id];
        $res = $this->mallSixAdverModel->delSixAdver($where);
        $where = ['adver_id' => $id];
        $res_del = $this->mallSixAdverGoodsModel->delSome($where);
        if ($res === false || $res_del === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    //猜你喜欢装修（推荐）

    /**
     * @return array
     * 获取推荐模块和关联商品列表
     */
    public function getRecList($where, $page, $pageSize)
    {
        $arr = [];
//        if (!empty($where)) {
//            $arr = [['id' => 0, 'name' => '精选', 'subname' => '为你推荐', 'is_display' => 1, 'sort' => 10000, 'status' => 1, 'create_time' => time()]];
//        }
        $decorateColumn = $this->mallRecommendModel->getByCondition(true, $where);

        if (!empty($arr)) {
            $arr = array_merge($arr, $decorateColumn);
            return $arr;
        } else {
            return $decorateColumn;
        }
    }

    public function getRelatedGoods($id, $page, $pageSize, $uid = 0,$arr1=[],$store_ids=[])
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $where = [
            ['r.recommend_id','=',$id],
            ['g.is_del','=',0],
            ['g.status','=',1],
            ['s.status','=',1],
            ['s.have_mall','=',1],
        ];
        $whereOr = [];
        if(cfg('open_mall_order_area')==1){
            $area_id=(new MallGoodsAreaService())->getAreaId($arr1);
            if($area_id){
                $whereOr[]=['ma.area_id', 'in', $area_id];
            }
        }elseif(cfg('open_mall_order_area')==2){
            $where[] = ['s.store_id','in',$store_ids];
        }
        
        $goods = $this->mallRecommendGoodsModel->getDetail($where, $page, $pageSize, 'r.sort DESC',$whereOr);
        $goodsCount = $this->mallRecommendGoodsModel->getCount($where);
        if (!empty($goods)) {
            foreach ($goods as $k => &$v) {
                $goods[$k]['image'] = $goods[$k]['image'] ? replace_file_domain($v['image']) : '';
                $goods[$k]['price'] = get_format_number($v['price']);
                $goods[$k]['old_age_pension'] = cfg('open_yanglao_and_sande') == 1 ? '消费100可得养老金' . cfg('Currency_symbol') . (new PercentRateService)->getOldAgePension($v['mer_id'], 'mall', $goods[$k]['price']) : '';
                $images = explode(',', $goods[$k]['images']);
                if(!empty($v['like_image'])){
                    $v['like_image'] = replace_file_domain($v);
                }
                $result = (new MallGoods())->globelLabel($v['store_id'], $v['goods_id'], $v['mer_id'], $uid);
                //获取标签
                $goods[$k]['good_label'] = $result;
                //获取活动类型
                $act_arr=(new \app\mall\model\service\activity\MallActivityService())->getActivity($v['goods_id']);
                $goods[$k]['activity_type'] = $act_arr['style'];
                isset($act_arr['price']) && $goods[$k]['act_price'] = get_format_number($act_arr['price']);
                if (!empty($images)) {
                    foreach ($images as $vv) {
                        $pics[] = replace_file_domain($vv);
                    }
                    $goods[$k]['images'] = $pics;
                    $pics = [];
                }
            }
        }
        $list['list'] = $goods;
        $list['total_count'] = $goodsCount;
        return $list;
    }

    /**
     * @param $param
     * @return bool
     * @throws \think\Exception
     * 编辑或添加推荐模块
     */
    public function addOrEditRec($param)
    {
        if (empty($param['name'])) {
            throw new \think\Exception('缺少name参数');
        }
        if (empty($param['subname'])) {
            throw new \think\Exception('缺少subname参数');
        }
        if (empty($param['id'])) {
            //添加
            $res = $this->mallRecommendModel->addOne($param);
        } else {
            //编辑
            $where = ['id' => $param['id']];
            if($param['id']==999999){//默认推荐分组不可关闭
                $param['status']=1;
            }
            unset($param['id']);
            $res = $this->mallRecommendModel->updateOne($param, $where);
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return true;
    }

    /**
     * @param $id
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\Exception
     * 获取推荐的编辑信息
     */
    public function getRecEdit($id, $page, $pageSize)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $where = ['id' => $id];
        $arr = $this->mallRecommendModel->getOne($where);
        if (!empty($arr)) {
            $goods = $this->mallRecommendGoodsModel->getDetail(['recommend_id' => $arr['id']], $page, $pageSize);
            if (!empty($goods)) {
                foreach ($goods as $k => $v) {
                    $goods[$k]['image'] = cfg('url') . '/' . $v['image'];
                }
            }
            $arr['related_goods'] = $goods;
        }
        return $arr;
    }

    /**
     * @param $id
     * @return bool
     * @throws \think\Exception
     * 删除推荐模块
     */
    public function delRecAdver($id)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $where = ['id' => $id];
        $res = $this->mallRecommendModel->delRecAdver($where);
        $where = ['recommend_id' => $id];
        $res_del = $this->mallRecommendGoodsModel->delSome($where);
        if ($res === false || $res_del === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * @param $isDisplay
     * @return bool
     * @throws \think\Exception
     * 修改猜你喜欢（推荐）是否展示
     */
    public function recDisplay($isDisplay)
    {
        $res = $this->mallRecommendModel->updateOne(['is_display' => $isDisplay], [['name', 'like', '%%'],['id','<>',999999]]);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return true;
    }

    /**
     * 获取装修渲染的信息
     */
    public function getUrlAndRecSwitch()
    {
//        $isDisplay = $this->mallRecommendModel->getOne(['status' => 1]) ? $this->mallRecommendModel->getOne(['status' => 1])['is_display'] : 0;
        $mallRecommend = $this->mallRecommendModel->getOne([['status' ,'=', 1],['id','<>',999999]]);
        $isDisplay = $mallRecommend ? $mallRecommend['is_display'] : 0;
        $url = get_base_url('pages/shopmall_third/shopmall_index');
        return ['is_display' => $isDisplay, 'url' => $url];
    }
    //用户端数据返回

    /**
     * 用户端首页各种广告数据返回
     * @param $catKey
     * @param int $limit
     * @param bool $needFormart
     * @return array|string|string[]
     */
    public function getAdverByCatKey($catKey, $limit = 3, $needFormart = false)
    {
        // 当前城市
        $nowCity = cfg('now_city');
        // 获取缓存
        //$cache = cache();
        //$adverList = $cache->get('adverList_' . $catKey . '_' . $nowCity . '_' . $limit);
        $adverList = [];
        if (!empty($adverList)) {
            $adverList = replace_domain($adverList);
            return $adverList;
        }
        // 广告分类信息
        $nowAdverCategory = $this->mallAdverCategory->getAdverCategoryByCatKey($catKey);
        if (!$nowAdverCategory) {
            return [];
        }
        // 搜索条件
        $where = [['cat_id', '=', $nowAdverCategory['cat_id']], ['status', '=', 1]];
        // 开启多城市
        if (cfg('many_city')) {
            array_push($where, ['city_id', 'exp', Db::raw('=' . $nowCity . ' or currency = 1')]);
        }
        // 排序
        $order = ['complete' => 'DESC', 'sort' => 'DESC', 'id' => 'DESC',];
        // 广告列表
        $adverList = $this->mallAdverModel->getAdverListByCondition($where, $order, $limit);
        $imgCount = count($adverList);
//        if (cfg('many_city')) {
//            // 开启多城市
//            $enough = $limit - $imgCount;
//            // 排序
//            $order = ['sort' => 'DESC', 'id' => 'DESC',];
//            if (empty($adverList)) {
//                // 查询不绑定城市的
//                $where['city_id'] = 0;
//                // 通用广告列表
//                $adverList = $this->mallAdverModel->getAdverListByCondition($where, $order, $limit);
//            } elseif ($enough > 0 && $adverList[0]['complete'] == 1) {
//                $where['city_id'] = 0;
//                // 补齐广告列表
//                $complete = $this->mallAdverModel->getAdverListByCondition($where, $order, $enough);
//                if ($complete) {
//                    if ($adverList) {
//                        $adverList = array_merge_recursive($adverList, $complete);
//                    } else {
//                        $adverList = $complete;
//                    }
//                }
//            }
//        }
        // 替换图片路径
        foreach ($adverList as $key => $value) {
            if ($value['pic'] == '/v20/public/static/mall/mall_platform_default_decorate.png') {
                $adverList[$key]['pic'] = cfg('site_url') . '/v20/public/static/mall/mall_platform_default_decorate.png';
            } else {
                $adverList[$key]['pic'] = $value['pic'] ? thumb($value['pic'], 640, 240) : cfg('site_url') . '/v20/public/static/mall/mall_platform_default_decorate.png';
            }
            $adverList[$key]['img_count'] = $imgCount;
        }
//        // web版导航多城市
//        if (cfg('many_city')) {
//            foreach ($adverList as $key => $value) {
//                if (substr($value['url'], -6) == 'nocity') {
//                    $adverList[$key]['url'] = substr($value['url'], 0, strlen($value['url']) - 6);
//                } else if (strpos($value['url'], '/wap.php') >= 0) {
//                    $adverList[$key]['url'] = $value['url'];
//                } else {
//                    $adverList[$key]['url'] = str_replace(cfg('site_url'), cfg('now_site_url'), $value['url']);
//                }
//            }
//        }
        if (!empty($adverList) && $needFormart) {
            $adverList = $this->formatAdver($adverList);
        }
        // 存入缓存
        //$cache->set('adverList_' . $catKey . '_' . $nowCity . '_' . $limit, $adverList, 3600);
        $adverList = replace_domain($adverList);
        return $adverList;
    }


    /**
     * 点击广告 
     * @param int $id;
     */
    public function clickAdver($id)
    {
        $adv = $this->mallAdverModel->clickNumberInc($id);
        if(!$adv){
            throw new \Exception('');
        }
        return $adv;
    }

    /**
     * 点击分类轮播图 
     * @param int $id;
     */
    public function clickCategoryBanner($id)
    {
        $banner = (new MallGoodsCategoryBanner())->clickNumberInc($id);
        if(!$banner){
            throw new \Exception('');
        }
        return $banner;
    }

    /**
     * 点击活动轮播图 
     * @param int $id;
     */
    public function clickActivityBanner($id)
    {
        $banner = (new MallGoodsActivityBanner())->clickNumberInc($id);
        if(!$banner){
            throw new \Exception('');
        }
        return $banner;
    }

    /**
     * 点击六宫格广告
     * @param int $id;
     */
    public function clickSixAdver($id)
    {
        $banner = (new MallSixAdver())->clickNumberInc($id);
        if(!$banner){
            throw new \Exception('');
        }
        return $banner;
    }

    /**
     * 去掉不要的字段
     * @param $array
     * @return array
     */
    public function formatAdver($array)
    {
        foreach ($array as &$adver_value) {
            unset($adver_value['id']);
            unset($adver_value['bg_color']);
            unset($adver_value['cat_id']);
            unset($adver_value['status']);
            unset($adver_value['last_time']);
            unset($adver_value['sort']);
            unset($adver_value['province_id']);
            unset($adver_value['city_id']);
            unset($adver_value['complete']);
            unset($adver_value['img_count']);
        }
        return $array;
    }

    /**
     * @param $type
     * @param $dec_id
     * 获取关联商品列表
     */
    public function getRelatedList($type, $dec_id, $page, $pageSize)
    {
        if (empty($type) || empty($dec_id)) {
            throw new \think\Exception('缺少参数');
        }
        if ($type == 1) {
            $where = ['s.adver_id' => $dec_id];
            $res = (new MallSixAdverGoods())->getDetail2($where, $page, $pageSize, 's.sort DESC');
            $count = (new MallSixAdverGoods())->getCount($where);
        } elseif ($type == 2) {
            $where = ['r.recommend_id' => $dec_id,'g.is_del'=>0,'g.status'=>1];
            $res = (new MallRecommendGoods())->getDetail($where, $page, $pageSize, 'r.sort DESC');
            $count = (new MallRecommendGoods())->getGoodsRelateCount($where);
        }
        if (!empty($res)) {
            foreach ($res as &$val) {
                $val['image'] = replace_file_domain($val['image']);
            }
        }
        $list['list'] = $res;
        $list['total'] = $count;
        return $list;
    }

    /**
     * @param $type
     * @param $dec_id
     * @param $sort
     * 保存排序
     */
    public function saveRelatedSort($type, $id, $sort)
    {
        if (empty($type) || empty($id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['id' => $id];
        $data = ['sort' => $sort];
        if ($type == 1) {
            $res = (new MallSixAdverGoods())->updateOne($where, $data);
        } elseif ($type == 2) {
            $res = (new MallRecommendGoods())->updateOne($where, $data);
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $id
     * @param $type
     * @throws \think\Exception
     * 删除关联商品
     */
    public function delOne($id, $type)
    {
        if (empty($type) || empty($id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['id' => $id];
        if ($type == 1) {
            $res = (new MallSixAdverGoods())->delSome($where);
        } elseif ($type == 2) {
            $res = (new MallRecommendGoods())->delSome($where);
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

}