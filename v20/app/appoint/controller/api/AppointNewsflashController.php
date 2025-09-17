<?php

declare (strict_types=1);

namespace app\appoint\controller\api;

use app\appoint\model\db\AppointNewsflash;
use app\appoint\model\service\AppointNewsflashService;
use app\appoint\validate\AppointNewsflash as AppointNewsflashValidate;

class AppointNewsflashController extends ApiBaseController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常
    protected $validateClass = AppointNewsflashValidate::class;
    
    //endregion

    //region 资讯

    /**
     * 资讯列表
     * @return \think\response\Json
     */
    public function newsList()
    {
        $params = $this->validateParameter();
        
        $data = app(AppointNewsflashService::class)->newsList($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 资讯详情
     * @return \think\response\Json
     */
    public function newsDetail()
    {
        $params = $this->validateParameter();
        $data = app(AppointNewsflashService::class)->newsDetail($params['newsflash_id']);
        AppointNewsflash::where('id', $params['newsflash_id'])->inc('views')->update();

        return api_output(self::STATUS_OK, $data);
    }

    //endregion


}