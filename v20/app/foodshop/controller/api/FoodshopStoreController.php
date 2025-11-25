<?php
/**
 * 餐饮商品控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:46
 */

namespace app\foodshop\controller\api;
use app\foodshop\controller\api\ApiBaseController;
use app\common\model\service\AdverService as AdverService;
use app\common\model\service\SliderService as SliderService;
class FoodshopStoreController extends ApiBaseController
{

    /**
     * 获得首页广告导航等信息
     */
    public function index()
    {
        $adverService = new AdverService();
        $SliderService = new SliderService();
        $returnArr = [];
        // 头部轮播广告
        $returnArr['adver_top'] = $adverService->getAdverByCatKey('wap_foodshop_index_top', 5, 1);
        
        // 餐饮首页中部4图广告
        $returnArr['adver_middle_four'] = $adverService->getAdverByCatKey('wap_foodshop_middle_four', 4, 1);
        
        // 中部导航
        $returnArr['slider'] = $SliderService->getSliderByCatKey('wap_foodshop_slider',1000, 1);

        // 搜索名称
        $returnArr['index_search_name'] = L_('请输入店铺名');
        
        // 分享信息
        $returnArr['share_info']['title'] = cfg('foodshop_share_title') ? cfg('foodshop_share_title') : cfg('site_name').'|'.cfg('meal_alias_name');
        $returnArr['share_info']['image'] = cfg('foodshop_share_image') ? replace_file_domain(cfg('foodshop_share_image')) : '';
        $returnArr['share_info']['desc'] = L_('点击立即查看更多店铺与优惠');
        return api_output(0, $returnArr);
    }

    /**
     * 筛选信息
     */
    public function screenList()
    {
        $now_city = $this->request->param("now_city", "0", "intval");

        // 筛选信息
        $returnArr = (new \app\foodshop\model\service\store\MerchantStoreFoodshopService())->getScreenList($now_city);

        return api_output(0, $returnArr);
    }


    /**
    * 搜索发现
    */
    public function searchFind()
    {
       $returnArr = [];

       // 筛选信息
       $returnArr = (new \app\foodshop\model\service\store\SearchHotFoodshopService())->getWapSearchList();

       return api_output(0, $returnArr);
   }

   /**
   * 搜索商圈
   */
   public function searchCircle()
   {
        $returnArr = [];
        
        // 商圈搜索
        $areaKeyword = $this->request->param("areaKeyword", "", "trim");

        // 筛选信息
        $returnArr = (new \app\common\model\service\AreaService())->getAreaListByKeyword($areaKeyword);

        return api_output(0, $returnArr);
  }

    
    /**
     * 获得店铺详情
     */
    public function storeList()
    {
        // 区域id
        $areaId = $this->request->param("area_id", "", "intval");
        // 美食分类ID
        $catID = $this->request->param("cat_id", "", "intval");
        // 排序值
        $sort = $this->request->param("sort", "", "trim");
        // 筛选
        $recommend = $this->request->param("recommend", "", "trim");
        // 关键字
        $keyword = $this->request->param("keyword", "", "trim");
        // 经度
        $long = $this->request->param("user_long", "", "trim");
        // 纬度
        $lat = $this->request->param("user_lat", "", "trim");
        // 当前页数
        $page = $this->request->param("page", "1", "intval");
        // 商圈搜索
        $areaKeyword = $this->request->param("areaKeyword", "", "trim");
        // 是否否地图
        $isMap = $this->request->param("is_map", "", "trim");
        
        $param['area_id'] = $areaId;
        $param['cat_id'] = $catID;
        $param['sort'] = $sort;
        $param['recommend'] = $recommend;
        $param['keyword'] = htmlspecialchars($keyword);
        $param['long'] = $long;
        $param['lat'] = $lat;
        $param['page'] = $page;
        $param['isMap'] = $isMap;
        $param['areaKeyword'] = $areaKeyword;
        $param['mer_id'] = $this->request->param("mer_id", "", "intval");
        $param['merchant_wxapp'] = $this->request->param("merchant_wxapp", "", "intval");
        $param['now_city'] = $this->request->param("now_city", "", "intval");
        $storeList = (new \app\foodshop\model\service\store\MerchantStoreFoodshopService())->getStoreList($param, $this->userInfo);

        try {
            // 获得店铺列表
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $storeList);
    }
}
