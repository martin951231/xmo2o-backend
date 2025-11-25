<?php
/*
 * @Descripttion: 页面装修控制器
 * @Author: wangchen
 * @Date: 2021-02-24 09:25:10
 * @LastEditors: wangchen
 * @LastEditTime: 2021-03-03 10:07:35
 */

namespace app\foodshop\controller\platform;

use app\foodshop\controller\platform\AuthBaseController;
use app\foodshop\model\service\renovation\RenovationService;
use think\Exception;

class RenovationController extends AuthBaseController
{
    
    /**
     * 获得装修内容
     */
    public function renovation_list()
    {
        $renovationService = new RenovationService();

        // 分类id
        $cat_id = $this->request->param("cat_id", "0", "intval");

        if(empty($cat_id)){
            throw new Exception(L_("参数错误"), 1001);
        }

        // 获取背景颜色
        $shop_decoration_bgcolor = $renovationService->getBgColor($cat_id,1);

        // 获取文本与图标颜色
        $shop_decoration_text_icon = $renovationService->getBgColor($cat_id,2);

        // 获取热门搜索词列表
        $shop_decoration_hot_mall = $renovationService->getHotMall($cat_id);

        // 获取轮播图列表
        $shop_decoration_carousel = $renovationService->getCarousel($cat_id);

        // 获取导航管理列表
        $shop_decoration_slider = $renovationService->getSliderList($cat_id)->toArray();

        // 获取爆款推荐/店铺列表展示热销商品
        $shop_decoration_hot_goods = $renovationService->getHotGoods($cat_id);

        $data = array(
            'shop_decoration_bgcolor' => $shop_decoration_bgcolor,
            'shop_decoration_text_icon' => $shop_decoration_text_icon,
            'shop_decoration_hot_mall' =>$shop_decoration_hot_mall,
            'shop_decoration_carousel' =>$shop_decoration_carousel,
            'shop_decoration_slider' =>$shop_decoration_slider,
            'shop_decoration_hot_goods' =>$shop_decoration_hot_goods['recommend_good_lists'],
            'shop_decoration_hot_goods_shop' =>$shop_decoration_hot_goods['recommend_good_lists_shop'],
        );
        return api_output(0, $data);
    }
}
