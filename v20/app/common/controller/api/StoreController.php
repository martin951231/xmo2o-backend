<?php

namespace app\common\controller\api;

use app\common\model\service\AskService;
use app\group\model\service\appoint\GroupAppointService;
use app\group\model\service\GroupService;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\MerchantUserRelationService;
use app\common\model\db\UserViewsStore;
use weather\Weather;

/**
 * 店铺控制器
 * @package app\common\controller\api
 */
class StoreController extends ApiBaseController
{
    /**
     * 店铺综合主页
     * @author: 张涛
     * @date: 2021/05/10
     */
    public function index()
    {
        try {
            $storeId = $this->request->param('store_id', 0, 'intval');
            $lng = $this->request->param('lng', 0);
            $lat = $this->request->param('lat', 0);
            $uid = $this->request->log_uid;

            $baseInfo = (new MerchantStoreService())->getIndexBaseInfo($storeId, $uid, $lat, $lng);
            if(!empty($baseInfo['mer_id'])){
                $mer=(new MerchantService())->getOne([['mer_id','=',$baseInfo['mer_id']],['status','<>',4]]);
                if(empty($mer)){
                    return api_output(1003, [],"此店铺所属商家已被删除");
                }
            }

            return api_output(0, $baseInfo);
        } catch (\Exception $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 浏览足迹上报
     */
    public function viewReport(){
        $uid = $this->request->log_uid;
        // $uid = 112358776;
        $store_id = $this->request->param('store_id', 0, 'intval');
        if(empty($uid) || empty($store_id)){
            return api_output(0, []);
        }
        $UserViewsStore = new UserViewsStore();
        $where = [
            'store_id'=>$store_id,
            'uid' => $uid
        ];
        //先删除以前的
        $UserViewsStore->updateThis($where, ['is_del'=>1]);
        //新增
        $data = [
            'store_id' => $store_id,
            'uid' => $uid,
            'add_time' => time(),
            'is_del' => 0
        ];
        $UserViewsStore->add($data);
        return api_output(0, []);
    }

    /**
     * 用户浏览足迹
     */
    public function viewStores(){
        $this->checkLogin();
        $uid = $this->request->log_uid;

        // $uid = 112358755;
        $page = $this->request->param('page', 1, 'intval');
        $lat = $this->request->param('lat');
        $lng = $this->request->param('lng');
        $pageSize = 10;
        $UserViewsStore = new UserViewsStore();
        $stores = $UserViewsStore->alias('o')
                ->field('s.*')
                ->leftJoin($UserViewsStore->dbPrefix().'merchant_store s','s.store_id=o.store_id')
                ->where([['o.uid','=',$uid],['o.is_del','=','0'],['s.have_mall','=','1']])
                ->order('o.add_time desc')
                ->limit(($page-1)*$pageSize,$pageSize)
                ->select()->toArray();
        if(empty($stores)){
            return api_output(0, []);
        }
        
        $stores = (new MerchantStoreService)->formatData($stores, $lng, $lat);
        return api_output(0, $stores);
    }

    public function viewStoresDel(){
        $this->checkLogin();
        $uid = $this->request->log_uid;
        $store_id = $this->request->param('store_id', 0, 'intval');
        if(empty($uid) || empty($store_id)){
            return api_output(0, []);
        }
        $UserViewsStore = new UserViewsStore();
        $where = [
            'store_id'=>$store_id,
            'uid' => $uid
        ];
        $UserViewsStore->updateThis($where, ['is_del'=>1]);
        return api_output(0, []);
    }

    /**
     * 问大家
     * @return void
     * @author: 张涛
     * @date: 2021/05/17
     */
    public function askLabels()
    {
        $storeId = $this->request->param('store_id', 0, 'intval');
        $label = (new AskService)->getLabels($storeId);
        return api_output(0, $label);
    }

    /**
     * 问答列表
     * @author: 张涛
     * @date: 2021/05/17
     */
    public function askList()
    {
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['label_id'] = $this->request->param('label_id', 0, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['page_size'] = $this->request->param('page_size', 20, 'intval');
        $param['is_del'] = 0;
        $label = (new AskService)->getAskList($param, ['a.index_show' => 'desc', 'a.reply_count' => 'desc']);
        return api_output(0, $label);
    }

    /**
     * 我要提问/回复
     * @author: 张涛
     * @date: 2021/05/17
     */
    public function saveAsk()
    {
        $this->checkLogin();
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['uid'] = $this->request->log_uid;
        $param['fid'] = $this->request->param('fid', 0, 'intval');
        $param['image'] = $this->request->param('image', []);
        $label = (new AskService)->saveAsk($param);
        return api_output(0, $label);
    }

    /**
     * 根据标签获取团购商品+课程
     * @author: 张涛
     * @date: 2021/05/24
     *
     */
    public function getStoreGroupGoodsByLabel()
    {
        $fid = $this->request->param('fid', 0, 'intval');
        $id = $this->request->param('id', 0, 'intval');
        $storeId = $this->request->param('store_id', 0, 'intval');

        $goods = (new GroupService())->getStoreGoupGoodsByLabel($storeId, $fid, $id);
        return api_output(0, $goods);
    }

    public function getSceneByDate()
    {
        try {
            $date = $this->request->param('date', '', 'trim');
            $storeId = $this->request->param('store_id', 0, 'intval');
            $scene = (new GroupService())->getSceneByDate($storeId, $date);
            return api_output(0, ['lists' => $scene]);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 更多精品团购列表
     * @author: 张涛
     * @date: 2021/05/28
     */
    public function getGroupGoods()
    {
        $storeId = $this->request->param('store_id', 0, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('page_size', 20, 'intval');
        $lists = (new GroupService())->getGoodsByStoreId($storeId, 'normal', true, 'g.pin_num = 0', $pageSize, $page);
        return api_output(0, $lists);
    }

    /**
     * 预约礼提交
     * @author: 张涛
     * @date: 2021/05/28
     */
    public function appointSubmit()
    {
        try {
            $this->checkLogin();
            $storeId = $this->request->param('store_id', 0, 'intval');
            $phone = $this->request->param('phone', '', 'trim');
            $uid = $this->uid;
            (new GroupAppointService())->appointSubmit($uid, $storeId, $phone);
            return api_output(0, []);
        } catch (\Exception $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 经营资质
     * @author: 张涛
     * @date: 2021/06/01
     */
    public function getLicense()
    {
        $storeId = $this->request->param('store_id', 0, 'intval');
        $license = (new MerchantStoreService())->storeLicense($storeId);
        return api_output(0, $license);
    }

    /**
     * 店铺信息纠错上报
     * @date: 2021/06/01
     */
    public function saveReportError()
    {
        try {
            $this->checkLogin();
            $storeId = $this->request->param('store_id', 0, 'intval');
            $pics = $this->request->param('pics', []);
            $content = $this->request->param('content', '', 'trim');
            $reportId = (new MerchantStoreService())->saveReportError($this->request->log_uid, $storeId, $pics, $content);
            return api_output(0, ['id' => $reportId]);
        } catch (\Exception $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }
    
    public function selectCategory()
    {
        $category = [
            [
                'id'   => 1, 
                'name' => cfg('group_alias_name'),
                "type" => 'group'
            ],
            [
                'id'   => 2,
                'name' => cfg('shop_alias_name'),
                "type" => 'shop'
            ],
            [
                'id'   => 3,
                'name' => cfg('meal_alias_name'),
                "type" => 'meal'
            ],
            [
                'id'   => 4,
                'name' => cfg('mall_alias_name'),
                "type" => 'mall'
            ],
            [
                'id'   => 5,
                'name' => cfg('appoint_alias_name'),
                "type" => 'appoint'
            ],
            [
                'id'   => 6,
                'name' => cfg('life_tools_scenic_alias_name'),
                "type" => 'scenic'
            ],
            [
                'id'   => 7,
                'name' => cfg('life_tools_sports_alias_name'),
                "type" => 'sports'
            ],
        ];

        return api_output(0, $category);
    }
    //天气
    public function weather()
    {
        $this->checkLogin();

        $areaName = input('area_name');
        $lng      = input('lng');
        $lat      = input('lat');
        
        if(empty($areaName) || empty($lng) || empty($lat)){
            return api_output(1003, [], '参数错误！');
        }
        $location = "$lng,$lat";
        $weather = (new Weather())->getWeatherInfo($areaName,$location);
        
        $weatherIconDir = cfg('site_url') . '/static/images/weather/';
        $weatherAnimationImage = '';
        $weatherType = 0;
        $weatherImage = $weatherIconDir.'hotday.png';
        if(100 <=$weather['icon'] && $weather['icon'] <=153){
            if(7 <= date('H') && date('H') <= 19){
                $weatherImage = $weatherIconDir.'hotday.png';
            }else{
                $weatherImage = $weatherIconDir.'sunnyday.png';
            }
        }else if(300 <=$weather['icon'] && $weather['icon'] <=399){
            $weatherImage = $weatherIconDir.'rainyday.png';
            $weatherAnimationImage = $weatherIconDir.'rain.gif';
            $weatherType = 3;
        }else if(400 <=$weather['icon'] && $weather['icon'] <=499){
            $weatherImage = $weatherIconDir.'snowyday.png';
            $weatherAnimationImage = $weatherIconDir.'snow.gif';
            $weatherType = 2;
        }else if(500 <=$weather['icon'] && $weather['icon'] <=515){
            $weatherImage = $weatherIconDir.'fog.png';
            $weatherAnimationImage = $weatherIconDir.'fog.png';
            $weatherType = 4;
        }else if($weather['icon'] == 900){
            $weatherImage = $weatherIconDir.'hotday.png';
            $weatherAnimationImage = $weatherIconDir.'hotday.png';
        }else if($weather['icon'] == 901){
            $weatherImage = $weatherIconDir.'fog.png';
            $weatherAnimationImage = $weatherIconDir.'fog.png';
            $weatherType = 4;
        }

        $icon = app()->getRootPath() . '../static/images/weather/icons/' . $weather['icon'] . '.png';
        if (file_exists($icon)) {
            $weatherImage = cfg('site_url') . '/static/images/weather/icons/' . $weather['icon'] . '.png';
        }
        
        if($weather['temp'] >=35){
            $weatherAnimationImage = $weatherIconDir.'highTemperature.gif';
            $weatherType = 1;
        }
        $weather = [
            'weather_type'            => $weatherType, //1高温 2下雪 3 下雨 4 起雾
            'weather_text'            => $weather['text'],
            'weather_image'           => $weatherImage,
            'temperature'             => "{$weather['temp']}℃",
            'wind'                    => "{$weather['windDir']}{$weather['windScale']}级",
            'wind_image'              => cfg('site_url') . '/static/images/weather/wind.png',
            'humidity'                => "湿度{$weather['humidity']}%",
            'humidity_image'          => cfg('site_url') . '/static/images/weather/humidity.png',
            "weather_animation_image" => $weatherAnimationImage,
            "image_type" => 0,
        ];
        if(in_array($weatherType,[2, 3])){
            $weather['image_type'] = 1;//使用gif动态图
        }
       
        return api_output(0, $weather);
    }
}