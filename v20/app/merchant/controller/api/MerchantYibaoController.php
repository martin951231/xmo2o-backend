<?php


namespace app\merchant\controller\api;


use app\merchant\model\db\MerchantYibaoInfo;
use app\merchant\model\service\yibao\MerchantYibaoService;
use mallbook\Mallbook;

class MerchantYibaoController
{
    /**
     * 商家入网审核回调
     */
    public function notify()
    {
        $params_1 = file_get_contents('php://input');
        $params_2 = request()->param();
        $code = request()->param('code');
        $msg = request()->param('msg');
        $date = request()->param('date');
        $result = request()->param('result');
        $sign = request()->param('sign');
        $params = [
            'code' => $code,
            'msg' => $msg,
            'date' => $date,
            'result' => $result,
            'sign' => $sign
        ];
        fdump_api([$params_1,$params_2,$params],'yibao/notify',1);
        try {
            $res = (new MerchantYibaoService())->notify($params);
            fdump_api([$res,'成功'],'yibao/notify',1);
        }catch (\Exception $e){
            fdump_api($e->getMessage(),'yibao/notify',1);
        }
    }
}