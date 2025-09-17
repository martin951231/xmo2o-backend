<?php

namespace app\merchant\controller\api;

use app\common\model\service\ResourceService;
use app\http\exceptions\ParametersException;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\validate\AddressSetting as AddressSettingValidate;
use app\merchant\validate\MerchantMenu;
use think\helper\Str;

class MerchantController extends ApiBaseController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常
    
    public $validateClass = AddressSettingValidate::class;

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
        $validate = validate($this->validateClass);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw new ParametersException(L_($validate->getError()));
        }

        return $params;
    }

    //endregion
    
    public function store()
    {
        $merId = $this->request->param('mer_id', 0, 'intval');
        $store_id = $this->request->param('store_id', 0, 'intval');
        $lng = $this->request->param('lng');
        $lat = $this->request->param('lat');
        if ($merId < 1) {
            return api_output(1001, [], L_('参数错误'));
        }

        $stores = (new MerchantStoreService())->getStoreListByMerId($merId, 'store_id,province_id,city_id,area_id,adress,phone,name,score,long,lat',[['status','=',1],['store_id','<>',$store_id]]);
        $return = ResourceService::storeListsModel($stores,$lat,$lng);
        return api_output(0, ['lists' => $return]);
    }

    /**
     * 添加订单地址变更记录
     * @return \think\response\Json
     */
    public function addressChangeAddRecord()
    {
        $params = $this->validateParameter();

        $data = app(MerchantService::class)->addressChangeAddRecord($params);

        return api_output(0, $data);
    }

    //region 商家个人中心(手机端)
    
    /**
     * 商家手机端菜单列表
     */
    public function customMenuList()
    {
        $this->checkLogin();
        $params['merchant_id'] = $this->_uid;

        $data = app(MerchantService::class)->merchantMenuList($params);

        return api_output(0, $data);
    }

    /**
     * 自定义菜单
     * @return \think\response\Json
     */
    public function customMenuEdit()
    {
        $this->checkLogin();
        $this->validateClass = MerchantMenu::class;
        
        $params['menu_ids'] = is_string(input('menu_ids')) ? explode(',',input('menu_ids')) : input('menu_ids');
        $params = $this->validateParameter('post', $params);
        $params['merchant_id'] = $this->_uid;

        $data = app(MerchantService::class)->merchantMenuEdit($params);

        return api_output(0, $data);
    }

    /**
     * 商家手机端自定义菜单列表
     */
    public function customMenuIndexList()
    {
        $this->checkLogin();
        $params['merchant_id'] = $this->_uid;

        $data = app(MerchantService::class)->customMenuIndexList($params);

        return api_output(0, $data);
    }
    //endregion
}