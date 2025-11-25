<?php


namespace app\merchant\controller\merchant;


use app\merchant\model\service\MerchantPhysicalCardService;

class PhysicalCardController extends AuthBaseController
{
    /**
     * 实体卡列表
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
            $res = (new MerchantPhysicalCardService())->getList($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 添加实体卡
     */
    public function addCard(){
        $param['card_prefix'] = $this->request->param('card_prefix','','trim,string');
        $param['start_no'] = $this->request->param('start_no',0,'trim');
        $param['end_no'] = $this->request->param('end_no',0,'trim');
        $param['money'] = $this->request->param('money','','trim,string');
        $param['total'] = $this->request->param('total',0,'trim,intval');
        $param['mer_id'] = $this->merId;
        try {
            $res = (new MerchantPhysicalCardService())->addCard($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 实体卡-消费记录
     */
    public function getCardOrderList(){
        $param['page'] = $this->request->param('page',1,'trim,intval');
        $param['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['mer_id'] = $this->merId;
        try {
            $res = (new MerchantPhysicalCardService())->getCardOrderList($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 实体卡-删除
     */
    public function delCard(){
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['mer_id'] = $this->merId;
        try {
            $res = (new MerchantPhysicalCardService())->delCard($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 实体卡-导出
     */
    public function exportCard(){
        $param['page'] = 0;
        $param['pageSize'] = 10;
        $param['search_type'] = $this->request->param('search_type',0,'trim,intval');
        $param['keywords'] = $this->request->param('keywords','','trim,string');
        $param['date_type'] = $this->request->param('date_type',0,'trim,intval');
        $param['start_date'] = $this->request->param('start_date','','trim,string');
        $param['end_date'] = $this->request->param('end_date','','trim,string');
        $param['mer_id'] = $this->merId;
        $param['rand_number'] = time();
        try {
            $res = (new MerchantPhysicalCardService())->exportCard($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}