<?php

namespace app\internal_card\controller\storestaff;

use app\internal_card\model\service\InternalCardService;
use app\storestaff\controller\storestaff\AuthBaseController;
use think\response\Json;

class InternalCardController extends AuthBaseController
{
    public function orderList(): Json
    {
        $params = input('post.');
        $params['store_id'] = $this->staffUser['store_id'];
        $data = (new InternalCardService())->orderList($this->merId, $params);

        return api_output(0, $data);
    }

    public function orderPayList(): Json
    {
        $params = input('post.');
        $params['store_id'] = $this->staffUser['store_id'];
        $data = (new InternalCardService())->orderPayList($this->merId, $params);

        return api_output(0, $data);
    }
}