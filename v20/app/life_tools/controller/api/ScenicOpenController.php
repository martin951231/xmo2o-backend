<?php
/**
 * 景区对外开放控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\db\LifeToolsMember;
use app\life_tools\model\service\HomeDecorateService;
use app\life_tools\model\service\LifeToolsOrderService;
use app\life_tools\model\service\LifeToolsService;

class ScenicOpenController extends ApiBaseOpenController
{
    public $methodMap = [
        'Server.CheckAlive'     =>  'CheckAlive', //存活接口
        'Order.Create'          =>  'CreateOrder', //请求下单
    ];


    /**
     * 票付通入口
     * @param string post:method 执行的方法 
     */
    public function pft()
    {
        $params = $this->request->param();
        
        try {
            if(empty($params) || empty($params['method'])){
                throw new \think\Exception('缺少method参数！');
            }
            if(!isset($this->methodMap[$params['method']])){
                throw new \think\Exception('无效的method参数！');
            }
            $action = $this->methodMap[$params['method']];
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        
        return $this->$action($params);
        
    }


    public function CheckAlive($params)
    {
        return $this->success('', 'success');
    }

 
}