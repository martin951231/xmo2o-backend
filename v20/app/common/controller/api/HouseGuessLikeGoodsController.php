<?php


namespace app\common\controller\api;


use app\common\model\service\HouseGuessLikeGoodsService;

class HouseGuessLikeGoodsController extends ApiBaseController
{
    /**
     * 社区首页猜你喜欢-商品列表
     * @return \think\response\Json
     */
    public function getGoodsList(){
        $params['lng'] = $this->request->param('lng','','trim,string');
        $params['lat'] = $this->request->param('lat','','trim,string');
        $params['set_num'] = $this->request->param('set_num',0,'trim,intval');
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',3,'trim,intval');
        try {
            $res = (new HouseGuessLikeGoodsService())->getGoodsList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}