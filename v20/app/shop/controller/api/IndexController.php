<?php
/**
 * 外卖首页控制器
 * Author: hengtingmei
 * Date Time: 2021/01/12 14:46
 */

namespace app\shop\controller\api;
use app\common\model\service\AreaService;
use app\common\model\service\user\UserLongLatService;
use app\common\model\service\viewpage\ShopSeckillCategoryGoodsService;
use app\common\model\service\viewpage\ShopSeckillCategoryService;
use app\shop\model\service\store\MerchantStoreShopService;

class IndexController extends ApiBaseController
{


     /**
     * 首页限时秒杀列表页-基本信息
     */
    public function shopSeckillIndex()
    {
        
        $param = $this->request->param();

        $return = (new ShopSeckillCategoryService())->getIndexInfo($param);

        return api_output(0, $return);
    }

     /**
     * 首页限时秒杀列表页-头部商品列表
     */
    public function shopSeckillTopGoodsList()
    {

        $param = $this->request->param();

        // 获得商品列表
        $return = (new ShopSeckillCategoryGoodsService())->shopSeckillTopGoodsList($param);

        return api_output(0, $return);
    }

    /**
     * 首页限时秒杀列表页-每个分类下的商品列表
     */
    public function shopSeckillCategoryGoodsList()
    {

        $param = $this->request->param();
        $lat = $param['lat'];
        $long = $param['lng'];
        //可能会没有携带经纬度，在用户登录情况下尽量优化一下
        if( !$lat && !$long && $this->_uid){
            if($this->userInfo['openid']){
                $user_long_lat = (new UserLongLatService())->getLocation($this->userInfo['openid'], 0);
                if($user_long_lat){
                    $long = $user_long_lat['long'];
                    $lat = $user_long_lat['lat'];
                }
            }
        }


        //如果没有传递lat和long，则取当前城市的经纬度
        if ($lat === 0 && $long === 0) {
            $cityId = cfg('now_shop_city') ? cfg('now_shop_city') : cfg('now_city');
            $where = [
                'area_id' => $cityId,
                'is_open' => 1
            ];
            $nowCity = (new AreaService())->getOne($where);
            if ($nowCity) {
                $lat = $nowCity['area_lat'];
                $long = $nowCity['area_lng'];
            }
        }

        request()->lat = $lat;
        request()->lng = $long;
        // 获得商品列表
        $return = (new ShopSeckillCategoryGoodsService())->shopSeckillCategoryGoodsList($param);

        return api_output(0, $return);
    }

    /**
     * 店铺分享海报
     */
    public function shopSharePoster()
    {
        $storeId = intval(input('store_id'));
        
        empty($storeId) && throw_exception('店铺ID不能为空！');
        
        $posterUrl = (new MerchantStoreShopService())->shopSharePoster($storeId, input());

        return api_output(0, ['poster_url' => $posterUrl]);
    }
}
