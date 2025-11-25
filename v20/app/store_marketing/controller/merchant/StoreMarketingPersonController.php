<?php
namespace app\store_marketing\controller\merchant;
use app\merchant\controller\merchant\AuthBaseController;
use app\store_marketing\model\service\StoreMarketingPersonService;
use app\store_marketing\model\service\StoreMarketingRecordService;

class StoreMarketingPersonController extends AuthBaseController
{
    /**
     * @return \json
     * 佣金记录
     */
    public function storeMarketingRecord(){

        $data['store_id'] = $this->request->param("store_id", 0, "intval");
        $data['page'] = $this->request->param("page", 0, "intval");
        $data['pageSize'] = $this->request->param("pageSize", 0, "intval");
        if(empty($data['store_id'])){
            return api_output_error(1003, "店铺信息获取失败,请退出再重新打开");
        }
        $data['person_id'] = $this->request->param("person_id", 0, "intval");
        $data['goods_type'] = $this->request->param("goods_type", 0, "intval");
        $data['goods_name'] = $this->request->param("goods_name", '', "trim");
        $data['start_time'] = $this->request->param("start_time", '', "trim");
        $data['end_time'] = $this->request->param("end_time", '', "trim");
        (new StoreMarketingRecordService())->delRecord();
        $ret=(new StoreMarketingPersonService())->merchantStoreMarketingRecord($data,$data['page'],$data['pageSize']);
        return api_output(0, $ret, '获取成功');
    }
}