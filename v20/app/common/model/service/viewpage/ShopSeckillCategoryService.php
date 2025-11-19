<?php
/**
 * 系统后台可视化页面 外卖首页-限时秒杀功能 基础配置
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/12/21
 */

namespace app\common\model\service\viewpage;
use app\common\model\db\ShopSeckillCategory;
use app\common\model\service\AreaService;
use think\facade\Db;
class ShopSeckillCategoryService {
    public $shopSeckillCategory = null;
    public function __construct()
    {
        $this->shopSeckillCategory = new ShopSeckillCategory();
    }

    /**
     *获取一条条数据
     * @param $where array
     * @return array
     */
    public function getDefaultInfo(){
        $where = [
            'cat_id' => 1
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            // 自动创建一条默认数据
            $detail = [
                'cat_id' => 1,
                'is_all' => 1,
                'sort' => 999999999,
                'name' => '默认分类',
            ];
            $id = $this->add($detail);
        }

        // 商品总数
        $where = [
                   [ 'cat_id', '>', 0]
                ];
        $detail['goods_count'] = (new ShopSeckillCategoryGoodsService())->getCount($where);
        $detail['share_image'] = isset($detail['share_image']) ? replace_file_domain($detail['share_image']) : '';

        // 绑定城市
        $detail['city_list'] = (new ShopSeckillCategoryCityService())->getCityList($detail);

        return $detail;
    }

    /**
     *获取分类详情
     * @param $where array
     * @return array
     */
    public function getCategoryDetail($param){
        $catId = $param['cat_id'] ?? 0;
        if(empty($catId)){
            throw  new \think\Exception('缺少参数',1001);
        }
        $where = [
            'cat_id' => $catId
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw  new \think\Exception('分类不存在',1003);
        }

        // 绑定城市
        $detail['city_list'] = (new ShopSeckillCategoryCityService())->getCityList($detail);
        return $detail;
    }

    /**
     * 新增编辑分类
     * @param $where array
     * @return array
     */
    public function editCategory($param,$systemUser)
    {
        $catId = $param['cat_id'] ?? 0;
        $name = $param['name'] ?? '';
        $cityList = $param['city_list'] ?? [];
        $sort = $param['sort'] ?? '';
        $status = $param['status'] ?? '';

        if (empty($cityList) && $catId != 1) {
            throw  new \think\Exception('请选择覆盖城市', 1003);
        }

        $saveData = [
            'status' => $status,
            'update_time' => time(),
        ];

        if(isset($param['title'])){
            $saveData['title'] = $param['title'];
        }
        if(isset($param['share_title'])){
            $saveData['share_title'] = $param['share_title'];
        }
        if(isset($param['share_desc'])){
            $saveData['share_desc'] = $param['share_desc'];
        }
        if(isset($param['share_image'])){
            $saveData['share_image'] = $param['share_image'];
        }

        if ($catId) {

            if ($catId!=1) {
                $saveData['name'] = $name;
                $saveData['sort'] = $sort;
            }
            $where = [
                'cat_id' => $catId
            ];
            $res = $this->updateThis($where, $saveData);
        } else {
            $saveData['name'] = $name;
            $saveData['sort'] = $sort;
            $catId = $res = $this->add($saveData);
        }
        if ($res === false) {
            throw  new \think\Exception('保存失败，请稍后重试', 1003);
        }

        // 查看当前管理员的权限
        if ($systemUser['area_id'] > 0) {
            $nowArea = $this->getAreaByAreaId($systemUser['area_id']);
            if ($nowArea['area_type'] == 3) {//区域
                $nowArea = $this->getAreaByAreaId($nowArea['area_pid']);
                $provinceId = $nowArea['area_pid'];
            } elseif ($nowArea['area_type'] == 2) {//城市
                $provinceId = $nowArea['area_pid'];
            } elseif ($nowArea['area_type'] == 1) {// 省份
                $provinceId = $systemUser['area_id'];
            }
        } else {
            $provinceId = 0;
        }

        $where = [
            'cat_id' => $catId
        ];
        (new ShopSeckillCategoryCityService())->del($where);
        $saveData = [];
        $isAll = 0;
        foreach ($cityList as $_city) {

            if ($_city['value'] == 'all') {
                $isAll = 1;
                break;
            }
            $provinceId = explode('-', $_city['value'])[0];
            $cityId = explode('-', $_city['value'])[1];


            $saveData[] = [
                'province_id' => $provinceId,
                'city_id' => $cityId,
                'cat_id' => $catId,
            ];
        }
        if ($isAll || $catId==1) {// 城市通用
            $saveData = [
                'is_all' => 1
            ];
            $this->updateThis($where, $saveData);
        } else {
            (new ShopSeckillCategoryCityService())->addAll($saveData);
            $saveData = [
                'is_all' => 0
            ];
            $this->updateThis($where, $saveData);
        }
        return true;
    }

    /**
     * 删除分类
     * @param $where array
     * @return array
     */
    public function delCategory($param){
        $catId = $param['cat_id'] ?? 0;
        if(empty($catId)){
            throw  new \think\Exception('缺少参数',1001);
        }
        $where = [
            'cat_id' => $catId
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw  new \think\Exception('分类不存在',1003);
        }

        // 查询商品
        $goods = (new ShopSeckillCategoryGoodsService())->getCount($where);
        if($goods){
            throw  new \think\Exception('请先删除商品再来删除分类',1003);
        }

        // 删除
        $res = $this->shopSeckillCategory->where($where)->delete();
        if($res === false){
            throw  new \think\Exception('保存失败，请稍后重试',1003);
        }

        // 删除绑定的城市
        (new ShopSeckillCategoryCityService())->del($where);
        return true;
    }

    /**
     * 获得分类列表
     * @param $param array
     * @param $systemUser array
     * @return array
     */
    public function getCategoryList($param,$systemUser){
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;

        $page = ($page-1)*$pageSize;

        $where = [
            ['cat_id','>',0]
        ];
        $order= [
            'sort' => 'DESC',
            'cat_id' => 'ASC'
        ];

        $cityId = 0;
        $provinceId = 0;
        if ($systemUser && $systemUser['area_id']>0) {
            // 城市管理员所属城市
            $nowArea = (new AreaService())->getAreaByAreaId($systemUser['area_id']);
            if ($nowArea['area_type'] == 3) {//区域
                $cityId = $nowArea['area_pid'];
            } elseif ($nowArea['area_type'] == 2) {//城市
                $cityId = $nowArea['area_id'];
            } elseif ($nowArea['area_type'] == 1) {// 省份
                $provinceId = $systemUser['area_id'];
            }
        }

        $catIdArr = [];
        if($cityId || $provinceId){
            $whereArea = [];
            if($cityId){
                $whereArea[] = ['city_id', '=', $cityId];
            }
            if($provinceId){
                $whereArea[] = ['province_id', '=', $provinceId];
            }
            $catIdList = (new ShopSeckillCategoryCityService())->getSome($whereArea);
            $catIdArr = array_unique(array_column($catIdList,'cat_id'));
            if($catIdArr){
                $where[] = [
                    'cat_id','exp', Db::raw(' in ('.implode(',',$catIdArr).') OR is_all = 1')
                ];
            }
        }

        $list = $this->getSome($where,true,$order,$page,$pageSize);
        $count = $this->getCount($where);
        foreach ($list as &$category){
            // 是否可编辑
            $category['edit'] = true;
            // 绑定城市信息
            $category['city_name'] = '';
            if($category['is_all']){
                $category['city_name'] = '全国';
                if($systemUser['area_id']){
                    $category['edit'] = false;
                }

            }else{
                $cityList = (new ShopSeckillCategoryCityService())->getCityNameList($category);

                if($systemUser['area_id'] && (count($cityList)>1 || $nowArea['area_type'] == 3)){
                    $category['edit'] = false;
                }
                $category['city_name'] = '';
                if(count($cityList) <=2 ){
                    foreach ($cityList as $city){
                        $category['city_name'] .= $city['area_name'].'、';
                    }
                    $category['city_name'] = trim($category['city_name'],'、');
                }else{
                    $category['city_name'] = $cityList[0]['area_name'].'、'.$cityList[1]['area_name'].'等'.count($cityList).'个城市';
                }
            }


            // 绑定商品数量
            $where = [
                'cat_id' => $category['cat_id']
            ];
            $category['goods_count'] = (new ShopSeckillCategoryGoodsService())->getCount($where);
            $category['update_time'] = date('Y-m-d H:i:s',$category['update_time']);
        }
        $returnArr['list'] = $list;
        $returnArr['total'] = $count;
        return $returnArr;
    }

    /*
	 * 获得秒杀活动基本信息
     */
    public function getIndexInfo($param)
    {
        $cityId = cfg('now_shop_city') ?: (cfg('now_city') ?: 0);
        $returnArr = [];
        $returnArr['shop_seckill_show'] = 0;
        $returnArr['shop_seckill_cat_list'] = [];


        $where = ['cat_id'=>1];
        $info = $this->getOne($where);

        // 获得秒杀结束时间
        $info['last_time'] = (new ShopSeckillCategoryGoodsService())->getLastTime();

        $returnArr['shop_seckill_show'] = $info['status'];
        $returnArr['shop_seckill_title'] = $info['name'];
        $returnArr['shop_seckill_end_time'] = $info['last_time']>time() ? $info['last_time']-time() : 0;
        $returnArr['shop_seckill_title'] = $info['title'];

        $returnArr['share_title'] = $info['share_title'];
        $returnArr['share_desc'] = $info['share_desc'];
        $returnArr['share_image'] = thumb_img(replace_file_domain($info['share_image']),500,400);

        $returnArr['share_url'] = get_base_url('pages/shop_new/limitPage/limitPage?city_id='.$cityId);

        $returnArr['share_wx'] = (cfg('pay_wxapp_important') && cfg('pay_wxapp_username')) ? 'wxapp' : 'h5';
        if($returnArr['share_wx'] == 'wxapp'){
            $returnArr['share_wx_info'] = [
                'userName' 	=> cfg('pay_wxapp_username'),
                'path' 		=> '/pages/plat_menu/index?redirect=webview&webview_url='.urlencode($returnArr['share_url']).'&webview_title='.urlencode($info['name']),
                'image' 	=> thumb_img(replace_file_domain($info['share_image']),500,400),
                'title' 	=> $info['share_title'],
            ];
        }

        if(empty($cityId)){
            return $returnArr;
        }

        //获得显示的分类列表
        $where = [
            [ 'g.status' , '=', 1],
            [ 'g.cat_id' , '<>', 1],
            [ 'c.city_id','exp', Db::raw('='.$cityId.' OR c.city_id is null')]
        ];
        $prefix = config('database.connections.mysql.prefix');
        $order = [
            'g.sort' => 'DESC',
            'g.cat_id' => 'DESC',
        ];
        $categoryList = $this->shopSeckillCategory->alias('g')
            ->leftjoin([$prefix . 'shop_seckill_category_city' => 'c'], 'c.cat_id=g.cat_id')
            ->field('g.*,c.city_id')
            ->where($where)
            ->order($order)
            ->select();
        if($info){
            $returnArr['shop_seckill_cat_list'] = $categoryList->toArray();
        }
        return $returnArr;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $data['create_time'] = time();
        $data['update_time'] = time();

        $id = $this->shopSeckillCategory->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取一条条数据
     * @param $where array
     * @return array
     */
    public function getOne($where){
        $result = $this->shopSeckillCategory->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->shopSeckillCategory->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->shopSeckillCategory->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->shopSeckillCategory->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}