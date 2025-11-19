<?php
/*
 * @Descripttion: 店铺分类装修
 * @Author: wangchen
 * @Date: 2021-03-02 10:29:09
 * @LastEditors: wangchen
 * @LastEditTime: 2021-03-03 10:03:04
 */

namespace app\foodshop\model\service\renovation;

use app\foodshop\model\db\ShopDecorationColor;
use app\foodshop\model\db\ShopDecorationHotMall;
use app\common\model\db\Adver;
use app\common\model\db\Slider;
use app\shop\model\db\ShopGoods;
use app\common\model\db\Config;

class RenovationService {
    public function __construct(){
        $this->bgColor = new ShopDecorationColor();
        $this->hotMall = new ShopDecorationHotMall();
        $this->carouselAdver = new Adver();
        $this->sliderList = new Slider();
        $this->hotGoods = new ShopGoods();
        $this->cfgGoods = new Config();
    }
    /**
     * 获取装修颜色
     */
    public function getBgColor($cat_id, $type_id) {
        $where = array(
            'cat_id' => $cat_id,
            'type_id' => $type_id,
            'status' => 1
        );
        $result = $this->bgColor->getOneColor($where);
        return $result;
    }

    /**
     * 获取热门搜索词列表
     */
    public function getHotMall($shop_id) {
        $where = array(
            'shop_id' => $shop_id,
        );
        $result = $this->hotMall->HotMall($where);
        return $result;
    }

    /**
     * 获取轮播图列表
     */
    public function getCarousel($shop_id) {
        $where = array(
            'shop_id' => $shop_id,
        );
        $result = $this->carouselAdver->where($where)->select()->toArray();
        return $result;
    }

    /**
     * 获取导航列表
     */
    public function getSliderList($shop_id) {
        $where = array(
            'shop_id' => $shop_id,
        );
        $result = $this->sliderList->getSliderListByCondition($where);
        return $result;
    }

    /**
     * 获取爆款推荐/店铺列表展示热销商品
     */
    public function getHotGoods($shop_id) {
        $result = $this->bgColor->hotGoodsList($shop_id);
        $config = $this->cfgGoods->where(array('name'=>'shop_decoration_hot_goods'))->find()->toArray();
        $recommend_good_lists_shop = $config['value'];
        $dataresult['recommend_good_lists_shop'] = $recommend_good_lists_shop;
        if(!empty($config['value'])){
            foreach($result as $v){
                $goodslist = array();
                $good_ids_list = explode(',',$v['recommend_good_ids']);
                foreach($good_ids_list as $k){
                    $goodslist[] = $this->hotGoods->where(array('goods_id'=>$k))->find()->toArray();
                }
                $v['recommend_good_lists'] = $goodslist;
                $content_list[] = $v;
            }
            $dataresult['recommend_good_lists'] = $content_list;
            return $dataresult;
        }else{
            $dataresult['recommend_good_lists'] = $result;
            return $dataresult;
        }
    }
}