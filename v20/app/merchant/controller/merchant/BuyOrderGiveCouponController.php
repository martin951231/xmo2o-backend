<?php


namespace app\merchant\controller\merchant;


use app\merchant\model\service\BuyOrderGiveCouponService;

class BuyOrderGiveCouponController extends AuthBaseController
{
    /**
     * 优惠买单返券记录
     * @return \think\response\Json
     */
    public function getList(){
        $param['page'] = $this->request->param('page',1,'trim,intval');
        $param['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $param['search_type'] = $this->request->param('search_type',0,'trim,intval');
        $param['keywords'] = $this->request->param('keywords','','trim,string');
        $param['date_type'] = $this->request->param('date_type',0,'trim,intval');
        $param['start_date'] = $this->request->param('start_date','','trim,string');
        $param['end_date'] = $this->request->param('end_date','','trim,string');
        $param['mer_id'] = $this->merId;
        try {
            $res = (new BuyOrderGiveCouponService())->getList($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 导出
     * @return \think\response\Json
     */
    public function export(){
        $param['search_type'] = $this->request->param('search_type',0,'trim,intval');
        $param['keywords'] = $this->request->param('keywords','','trim,string');
        $param['date_type'] = $this->request->param('date_type',0,'trim,intval');
        $param['start_date'] = $this->request->param('start_date','','trim,string');
        $param['end_date'] = $this->request->param('end_date','','trim,string');
        $param['mer_id'] = $this->merId;
        $param['rand_number'] = time();
        try {
            $res = (new BuyOrderGiveCouponService())->export($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage().$e->getLine().$e->getFile());
        }
    }

}