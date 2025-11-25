<?php
/**
 * 物业列表
 * 衡婷妹
 * 2021/09/08
 */
namespace app\new_marketing\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\community\model\service\HousePropertyService;
use app\merchant\model\service\MerchantStoreService;
use app\new_marketing\model\service\MarketingPersonMerService;
use app\new_marketing\model\db\MerchantCategory;

class HousePropertyController extends AuthBaseController
{

    /**
     * 物业详情
     */
    public function getHousePropertyDetail(){ 
        $id = $this->request->param('id', 0, 'intval');
        if (!$id) {
            return api_output_error(1003, '物业ID不存在');
        }
       
        try {
            $list = (new HousePropertyService())->getHousePropertyDetail($id);
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    

    /**
     * 获得物业列表
     */
    public function getHousePropertyList(){
        $param['area'] = $this->request->param('area');
        $param['area_uid'] = $this->request->param('area_uid', 0, 'intval');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['property_name'] = $this->request->param('property_name', '', 'trim');
        $param['user_name'] = $this->request->param('user_name', '', 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['type'] =0;
        $list = (new MarketingPersonMerService())->getHousePropertyList($param);
        return api_output(0, $list, 'success');
        
    }

}