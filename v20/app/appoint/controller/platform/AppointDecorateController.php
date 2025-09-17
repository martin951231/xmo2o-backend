<?php

declare (strict_types=1);

namespace app\appoint\controller\platform;

use app\appoint\model\service\AdvertisementService;
use app\appoint\model\service\AppointService;
use app\appoint\model\service\SliderService;
use app\appoint\validate\AppointDecorate as AppointDecorateValidate;
use app\common\controller\platform\AuthBaseController;
use app\common\model\db\Config;
use app\http\exceptions\ParametersException;
use think\helper\Str;

class AppointDecorateController extends AuthBaseController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常

    /**
     * 参数验证
     * @param $scenario
     * @param array $param
     * @param string $method
     * @return array|mixed
     */
    private function validateParameter($method = 'post', array $params = [], string $scenario = ''): array
    {
        empty($params) && $params = input($method . '.');
        $validate = validate(AppointDecorateValidate::class);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw new ParametersException(L_($validate->getError()));
        }

        return $params;
    }

    //endregion

    //region 预约首页设置
    
    /**
     * 预约首页设置
     * @return \think\response\Json
     */
    public function appointDecorateIndex()
    {
        $config = [
            'appoint_index_activity',
            'appoint_show_tab',
            'appoint_style',
            'appoint_bottom_style',
        ];
        $appointStyle = Config::where('name','IN', $config)
            ->field('name, value')
            ->select()
            ->column('value', 'name');
        
        $appointStyle['url'] = cfg('config_site_url').'/packapp/plat/pages/appoint/index';
        
        return api_output(0, $appointStyle);

    }
    
    /**
     * 预约首页设置
     * @return \think\response\Json
     */
    public function appointDecorateIndexSet()
    {
        $params = $this->validateParameter();
        
        $appointSet = Config::where('name','=', $params['config_name'])->find();
        $appointSet->value = $params['config_value'];
        $result = $appointSet->save();
        
        return api_output(0, $result);

    }
    //endregion
    
    //region 轮播图广告

    /**
     * 预约广告列表
     * @return \think\response\Json
     */
    public function appointAdvertisementList()
    {
        $advertisementList = app(AdvertisementService::class)->advertisementList();
        
        return api_output(0, $advertisementList);
    }

    /**
     * 预约广告详情
     * @return \think\response\Json
     */
    public function appointAdvertisementDetail()
    {
        $params = $this->validateParameter();
        
        $advertisementDetail = app(AdvertisementService::class)->appointAdvertisementDetail($params);

        return api_output(0, $advertisementDetail);
    }
    
    /**
     * 预约广告添加或者编辑
     * @return \think\response\Json
     */
    public function appointAdvertisementAddOrEdit()
    {
        $params = $this->validateParameter();
        
        $data = app(AdvertisementService::class)->advertisementAddOrEdit($params);

        return api_output(0, $data);
    }

    /**
     * 预约广告删除
     * @return \think\response\Json
     */
    public function appointAdvertisementDel()
    {
        $params = $this->validateParameter();

        $data = app(AdvertisementService::class)->advertisementDel($params);

        return api_output(0, $data);
    }
    
    //endregion

    //region 导航栏列表
    
    /**
     * 预约导航栏列表
     * @return \think\response\Json
     */
    public function appointSliderList()
    {
        $sliderList = app(SliderService::class)->appointSliderList();

        return api_output(0, $sliderList);
    }
    
    /**
     * 预约导航详情
     * @return \think\response\Json
     */
    public function appointSliderDetail()
    {
        $params = $this->validateParameter();

        $sliderDetail = app(SliderService::class)->appointSliderDetail($params);

        return api_output(0, $sliderDetail);
    }
    
    /**
     * 预约导航添加或者编辑
     * @return \think\response\Json
     */
    public function appointSliderAddOrEdit()
    {
        $params = $this->validateParameter();

        $data = app(SliderService::class)->appointSliderAddOrEdit($params);

        return api_output(0, $data);
    }

    /**
     * 预约导航删除
     * @return \think\response\Json
     */
    public function appointSliderDel()
    {
        $params = $this->validateParameter();

        $data = app(SliderService::class)->appointSliderDel($params);

        return api_output(0, $data);
    }
    
    //endregion

    //region 精选推荐
    
    /**
     * 预约商品推荐列表
     * @return \think\response\Json
     */
    public function appointList()
    {
        $data = app(AppointService::class)->appointList(input());

        return api_output(0, $data);
    }
    
    /**
     * 预约商品推荐列表
     * @return \think\response\Json
     */
    public function appointRecommendList()
    {
        $data = app(AppointService::class)->appointRecommendList();

        return api_output(0, $data);
    }


    /**
     * 预约商品推荐添加
     * @return \think\response\Json
     */
    public function appointRecommendAdd()
    {
        $params = $this->validateParameter();
        
        $data = app(AppointService::class)->appointRecommendAdd($params);

        return api_output(0, $data);
    }
    
    /**
     * 预约商品推荐添加
     * @return \think\response\Json
     */
    public function appointRecommendEdit()
    {
        $params = $this->validateParameter();

        $data = app(AppointService::class)->appointRecommendEdit($params);

        return api_output(0, $data);
    }
    
    /**
     * 预约商品推荐添加
     * @return \think\response\Json
     */
    public function appointRecommendDel()
    {
        $params = $this->validateParameter();

        $data = app(AppointService::class)->appointRecommendDel($params);

        return api_output(0, $data);
    }

    /**
     * 预约精选推荐编辑
     * @return \think\response\Json
     */
    public function appointCategoryEdit()
    {
        $params = $this->validateParameter();

        $result = app(AppointService::class)->appointCategoryEdit($params);

        return api_output(0, $result);
    }

    /**
     * 首页预约分类列表
     * @return \think\response\Json
     */
    public function appointIndexCategory()
    {
        $result = app(AppointService::class)->appointIndexCategory();

        return api_output(0, $result);
    }
    
    //endregion

    //region 预约活动页
    /**
     * 活动列表
     * @return \think\response\Json
     */
    public function activityList()
    {
        $params = input('post.');

        $result = app(AppointService::class)->activityList($params);

        return api_output(0, $result);
    }

    /**
     * 保存活动
     * @return \think\response\Json
     */
    public function saveActivityList()
    {
        $params = $this->validateParameter();

        $result = app(AppointService::class)->saveActivityList($params);

        return api_output(0, $result);
    }

    /**
     * 删除活动
     * @return \think\response\Json
     */
    public function delActivity()
    {
        $params = $this->validateParameter();

        $result = app(AppointService::class)->delActivity($params['ids']);

        return api_output(0, $result);
    }

    public function activityAppointList()
    {
        $params = input('post.');

        $result = app(AppointService::class)->activityAppointList($params);

        return api_output(0, $result);
    }

    /**
     * 预约分类
     * @return \think\response\Json
     */
    public function categoryList()
    {
        $params = input('post.');

        $result = app(AppointService::class)->categoryList($params);

        return api_output(0, $result);
    }
    
    //endregion
    
    
    
    
    
}