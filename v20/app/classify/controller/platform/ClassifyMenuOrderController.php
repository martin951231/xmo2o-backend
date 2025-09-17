<?php

namespace app\classify\controller\platform;

use app\classify\validate\ClassifyMenuOrder as ClassifyMenuOrderValidate;
use app\classify\model\service\ClassifyMenuOrder as ClassifyMenuOrderService;

class ClassifyMenuOrderController extends ClassifyBaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->classifyValidate = ClassifyMenuOrderValidate::class;
    }

    public function orderList()
    {
        $params = input('post.');

        $data = app(ClassifyMenuOrderService::class)->orderList($params);
        if(!empty($params['export'])){
            $xlsName = '分类套餐购买记录--'.date('YmdHis');
            $xlsCell = [
                ['user_nickname', '用户昵称'],
                ['user_phone', '用户手机号'],
                ['name', '套餐名称'],
                ['effective_days', '套餐时间'],
                ['price', '套餐金额（元）'],
                ['expire_time', '过期时间'],
                ['create_time', '购买时间'],
            ];

            return api_output(self::STATUS_OK, export_excel($xlsName, $xlsCell, $data));
        }
        return api_output(self::STATUS_OK, $data);
    }

    public function changeExpire()
    {
        $params = $this->validateParameter();

        $data = app(ClassifyMenuOrderService::class)->changeExpire($params);

        return api_output(self::STATUS_OK, $data);
    }
}