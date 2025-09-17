<?php


namespace app\appoint\controller\api;


use app\appoint\model\service\AppointService;
use app\appoint\validate\AppointDecorate as AppointDecorateValidate;

class AppointController extends ApiBaseController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常
    protected $validateClass = AppointDecorateValidate::class;
    
    public function appointCategoryList()
    {
        $data = app(AppointService::class)->appointCategoryList();

        return api_output(0, $data);
    }
    
    public function recommendAppointList()
    {
        $params = $this->validateParameter();

        $data = app(AppointService::class)->recommendAppointList($params);
        
        return api_output(0, $data);
        
    }

    public function appointStoreList()
    {
        $params = $this->validateParameter();

        $data = app(AppointService::class)->appointStoreList($params);

        return api_output(0, $data);
    }

    /**
     * 活动关联的预约列表
     * @return \think\response\Json
     */
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

    //获取预约关联商品
    public function getRelatedGoods()
    {
        $params = [
            'appoint_id' => input('appoint_id', 0, 'trim,intval')
        ];
        try {
            $result = (new AppointService())->getRelatedGoods($params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}