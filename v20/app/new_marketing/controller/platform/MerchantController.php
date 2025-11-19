<?php
/**
 * liuruofei
 * 2021/08/30
 * 商家管理
 */
namespace app\new_marketing\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\merchant\model\service\MerchantStoreService;
use app\new_marketing\model\service\MarketingPersonMerService;
use app\new_marketing\model\db\MerchantCategory;

class MerchantController extends AuthBaseController
{

    /**
     * 商家店铺详情
     */
    public function getMerchantStoreList(){ 
        $merId = $this->request->param('merId', 0, 'intval');
        if (!$merId) {
            return api_output_error(1003, '商家ID不存在');
        }
        $where = [
            ['mer_id', '=', $merId],
            ['status', '=', 1]
        ];
        $field = 'store_id,mer_id,cat_id,cat_fid,name,phone,adress as address,last_time as add_time,end_time as effect_time';
        try {
            $list = (new MerchantStoreService())->getMerStoreList($where, $field);
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    

    /**
     * 获得商家列表
     */
    public function getMerchantList(){
        $param['area'] = $this->request->param('area');
        $param['area_uid'] = $this->request->param('area_uid', 0, 'intval');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['merchant_name'] = $this->request->param('merchant_name', '', 'trim');
        $param['user_name'] = $this->request->param('user_name', '', 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['type'] =0;
        $list = (new MarketingPersonMerService())->getMerchantList($param);
        return api_output(0, $list, 'success');
        
    }

}