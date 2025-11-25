<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\model\service\building\MerchantService;

//商家管理
class MerchantController extends BaseController
{
    //商场列表
    public function mallLists()
    {
        $result = (new MerchantService())->getMallLists();
        return $this->apiOutput(1000, ['result'=>$result], '');
    }

    //切换商场积分上下线状态
    public function changeOnlineStatus(){
        $mallId = $this->request->param('mall_id',0,'intval');
        try{
            $result = (new MerchantService())->changeOnlineStatus($mallId);
            return $this->apiOutput(1000, ['result'=>$result], '');
        }catch(\Exception $e){
             return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

    //保存积分配置
    public function saveScoreSetting(){
        $params = $this->request->param();
        try{
            $result = (new MerchantService())->saveScoreSetting($params);
            return $this->apiOutput(1000, ['result'=>$result], '');
        }catch(\Exception $e){
             return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

}
