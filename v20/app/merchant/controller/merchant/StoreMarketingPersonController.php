<?php


namespace app\merchant\controller\merchant;


use app\merchant\model\service\new_marketing\StoreMarketingPersonService;

class StoreMarketingPersonController extends AuthBaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->merId = $this->merchantUser['mer_id'] ?? 0;
    }

    /**
     * 验证手机号
     */
    public function regPhone()
    {
        try {
        $param['phone'] = $this->request->param('phone', 0, 'trim');
        $param['store_id'] = $this->request->param('store_id', 0, 'trim');
        $ret = (new StoreMarketingPersonService())->regPhone($param);
        return api_output(0, $ret, 'success');
        } catch (\Exception $e) {
            dd($e);
            // return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 分销员列表
     */
    public function getPersonList()
    {
        $storeMarketingPersonService = new StoreMarketingPersonService();
        try {
            $param['status'] = $this->request->param('status', 0, 'intval');
            $param['page'] = $this->request->param('page', 1, 'intval');
            $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
            $param['name'] = $this->request->param('name', '', 'trim');
            $param['start_time'] = $this->request->param('start_time', '', 'trim');
            $param['end_time'] = $this->request->param('end_time', '', 'trim');
            $param['store_id'] = $this->request->param('store_id',0, 'intval');
            if (empty($param['store_id'])) {
                return api_output_error(1003, "缺少店铺信息");
            }
            $arr = $storeMarketingPersonService->getPersonList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            dd($e);
           // return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 编辑营销人员
     */
    public function editPerson()
    {
        $storeMarketingPersonService = new StoreMarketingPersonService();
        try {
            $param['id'] = $this->request->param('id', 0, 'intval');
            $param['store_id'] = $this->request->param('store_id', 0, 'intval');
            $msg = $storeMarketingPersonService->editPerson($param);
            if (empty($msg)) {
                return api_output_error(1003, "人员信息id不正确");
            } else {
                return api_output(0, $msg, 'success');
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *添加保存分销员
     */
    public function addPerson()
    {
        $storeMarketingPersonService = new StoreMarketingPersonService();
        try {
            $param['uid'] = $this->request->param('uid', 0, 'intval');
            if (empty($param['uid'])) {
                return api_output_error(1003, "缺少人员信息");
            }
            $param['phone'] = $this->request->param('phone', '', 'trim');
            if (empty($param['phone'])) {
                return api_output_error(1003, "缺少联系方式");
            }
            $param['name'] = $this->request->param('name', '', 'trim');

            $param['store_id'] = $this->request->param('store_id', '', 'intval');
            if (empty($param['store_id'])) {
                return api_output_error(1003, "缺少店铺信息");
            }
            $param['mer_id'] = $this->merId;
            $param['ratio_type'] = $this->request->param('ratio_type', '0', 'intval');
            if (empty($param['ratio_type'])) {
                return api_output_error(1003, "缺少抽成设置");
            }
            $param['ratio'] = $this->request->param('ratio', '0', 'trim');
            $param['create_time'] =time();
            $ret=$storeMarketingPersonService->addPerson($param);
            return api_output(0, $ret, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @param $param
     * @return bool
     * 修改保存数据
     */
    public function savePerson(){
        $storeMarketingPersonService = new StoreMarketingPersonService();
        try {
            $param['id'] = $this->request->param('id', 0, 'intval');
            if (empty($param['id'])) {
                return api_output_error(1003, "缺少保存的id信息");
            }
            $param['uid'] = $this->request->param('uid', 0, 'intval');
            if (empty($param['uid'])) {
                return api_output_error(1003, "缺少人员信息");
            }
            $param['phone'] = $this->request->param('phone', '', 'trim');
            if (empty($param['phone'])) {
                return api_output_error(1003, "缺少联系方式");
            }
            $param['name'] = $this->request->param('name', '', 'trim');

            $param['store_id'] = $this->request->param('store_id', '', 'intval');
            if (empty($param['store_id'])) {
                return api_output_error(1003, "缺少店铺信息");
            }
            $param['mer_id'] = $this->merId;
            $param['ratio_type'] = $this->request->param('ratio_type', '0', 'intval');
            if (empty($param['ratio_type'])) {
                return api_output_error(1003, "缺少抽成设置");
            }
            $param['ratio'] = $this->request->param('ratio', '0', 'trim');
            $ret=$storeMarketingPersonService->savePerson($param);
            if($ret){
                return api_output(0, $ret, 'success');
            }else{
                return api_output_error(1003, "修改失败");
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @param $param
     * @return bool
     * 删除数据
     */
    public function delPerson()
    {
        $storeMarketingPersonService = new StoreMarketingPersonService();
        try {
            $param['id'] = $this->request->param('id', 0, 'intval');
            $param['store_id'] = $this->request->param('store_id', 0, 'intval');
            if (empty($param['id'])) {
                return api_output_error(1003, "缺少id信息");
            }
            $ret=$storeMarketingPersonService->delPerson($param);
            if($ret){
                return api_output(0, $ret, 'success');
            }else{
                return api_output_error(1003, "修改失败");
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}