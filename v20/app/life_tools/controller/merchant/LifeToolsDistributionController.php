<?php
namespace app\life_tools\controller\merchant;

use app\life_tools\model\service\distribution\LifeToolsDistributionOrderService;
use app\life_tools\model\service\distribution\LifeToolsDistributionOrderStatementService;
use app\life_tools\model\service\distribution\LifeToolsDistributionSettingService;
use app\life_tools\model\service\distribution\LifeToolsDistributionUserService;
use app\life_tools\model\service\distribution\LifeToolsTicketDistributionService;
use app\merchant\controller\merchant\AuthBaseController;

/**
 * 景区分销
 */
class LifeToolsDistributionController extends AuthBaseController
{
    /**
     * 获取配置详情
     * @author nidan
     * @date 2022/4/6
     */
    public function getSettingDataDetail()
    {
        $params = [];
        $params['mer_id'] = $this->merId;

        try {
            $data = (new LifeToolsDistributionSettingService())->getDataDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 配置景区分销信息
     * @author nidan
     * @date 2022/4/6
     */
    public function editSetting()
    {
        $params = [];
        $params['status_distribution'] = $this->request->post('status_distribution', '', 'trim');
        $params['status_award'] = $this->request->post('status_award', '', 'trim');
        $params['distributor_audit'] = $this->request->post('distributor_audit', '', 'trim');
        $params['share_logo'] = $this->request->post('share_logo', '', 'trim');
        $params['update_status_time'] = $this->request->post('update_status_time', '', 'trim');
        $params['personal_custom_form'] = $this->request->post('personal_custom_form', '', 'trim');
        $params['business_custom_form'] = $this->request->post('business_custom_form', '', 'trim');
        $params['description'] = $this->request->post('description', '', 'trim');
        $params['share_type'] = $this->request->post('share_type', 1, 'trim');
        $params['status_show_avatar'] = $this->request->post('status_show_avatar', '', 'trim');
        $params['status_show_price'] = $this->request->post('status_show_price', '', 'trim');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsDistributionSettingService())->editData($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }


    /**
     * 配置门票分销佣金
     * @author nidan
     * @date 2022/4/6
     */
    public function editDistributionPrice()
    {
        $params = [];
        $params['ticket_id'] = $this->request->post('ticket_id', '', 'trim');
        $params['secondary_commission'] = $this->request->post('secondary_commission', 0, 'trim');
        $params['third_commission'] = $this->request->post('third_commission', 0, 'trim');
        try {
            $data = (new LifeToolsTicketDistributionService())->editDistributionPrice($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 获取分销员列表（包含导出功能）
     * @author nidan
     * @date 2022/4/7
     */
    public function getDistributorList()
    {
        $params = [];
        $params['function_type'] = $this->request->post('function_type', 0,'trim');//方法类型（0-查询，1-导出）
        $params['user_ids'] = $this->request->post('user_ids', '','trim');//需要导出的数据（为空导出全部）
        $params['nickname'] = $this->request->post('nickname', '','trim');
        $params['phone'] = $this->request->post('phone', '','trim');
        $params['status'] = $this->request->post('status', 'all','trim');
        $params['type'] = $this->request->post('type', 0,'trim');
        $params['page'] = $this->request->post('page', 1, 'trim,intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsDistributionUserService())->getDistributorList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 获取分销员列表统计数据
     * @author nidan
     * @date 2022/4/7
     */
    public function getAtatisticsInfo()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsDistributionUserService())->getAtatisticsInfo($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 获取下级用户（包含导出功能）
     * @author nidan
     * @date 2022/4/7
     */
    public function getLowerLevel()
    {
        $params = [];
        $params['function_type'] = $this->request->post('function_type', 0,'trim');//方法类型（0：查询，1：导出数据）
        $params['user_id'] = $this->request->post('user_id', '','trim');
//        $params['nickname'] = $this->request->post('nickname', '','trim');
//        $params['phone'] = $this->request->post('phone', '','trim');
        $params['select_type'] = $this->request->post('select_type', '','trim');//1-昵称，2-手机号，3-上级昵称，4-上级手机号
        $params['content'] = $this->request->post('content', '','trim');
        $params['level'] = $this->request->post('level', 1,'trim');//查询等级用户
        $params['page'] = $this->request->post('page', 1, 'trim,intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsDistributionUserService())->getLowerLevel($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 分销员审核
     * @author nidan
     * @date 2022/4/7
     */
    public function audit()
    {
        $params = [];
        $params['user_id'] = $this->request->post('user_id', '','trim');//分销员id
        $params['audit_status'] = $this->request->post('audit_status', '','trim');//审核状态
        $params['audit_msg'] = $this->request->post('audit_msg', '','trim');//审核理由
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsDistributionUserService())->audit($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 查询指定用户的分销清单
     * @author nidan
     * @date 2022/4/7
     */
    public function getDistributionOrderList()
    {
        $params = [];
        $params['user_id'] = $this->request->post('user_id', 0,'trim');
        $params['status'] = $this->request->post('status', 0,'trim');//结算状态
        $params['page'] = $this->request->post('page', 1, 'trim,intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'trim');
        $params['start_time'] = $this->request->post('start_time', '', 'trim');
        $params['end_time'] = $this->request->post('end_time', '', 'trim');
        $params['get_all'] = $this->request->post('get_all', 0, 'trim');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsDistributionOrderService())->getList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 修改订单备注
     * @author nidan
     * @date 2022/4/7
     */
    public function editDistributionOrderNote()
    {
        $params = [];
        $params['distribution_order_id'] = $this->request->post('distribution_order_id', 0,'trim');
        $params['note'] = $this->request->post('note', '','trim');//备注
        try {
            $data = (new LifeToolsDistributionOrderService())->updateOrderNote($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 生成结算单
     * @author nidan
     * @date 2022/4/7
     */
    public function addStatement()
    {
        $params = [];
        $params['user_id'] = $this->request->post('user_id', '','trim');
        $params['name'] = $this->request->post('name', '','trim');
        $params['company'] = $this->request->post('company', '','trim');
        $params['reject_money'] = $this->request->post('reject_money', 0,'trim');
        $params['order_ids'] = $this->request->post('order_ids', '','trim');//订单id
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsDistributionOrderStatementService())->addStatement($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 删除分销员
     * @author nidan
     * @date 2022/4/8
     */
    public function delDistributor()
    {
        $params = [];
        $params['user_id'] = $this->request->post('user_id', '','trim');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsDistributionUserService())->delDistributor($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 获取结算单列表（包含导出）
     * @author Nd
     * @date 2022/4/13
     */
    public function getStatement()
    {
        $params = [];
        $params['function_type'] = $this->request->post('function_type', 0,'trim');//0-查询数据，1-导出数据，默认为0
        $params['search_type'] = $this->request->post('search_type', '','trim');//查询类型：1-昵称 ，2-手机号
        $params['search_content'] = $this->request->post('search_content', '','trim');
        $params['status'] = $this->request->post('status', '2','trim');//结算单状态（0：待确定，1：已确定，2：全部）
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsDistributionOrderStatementService())->getStatement($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
    /**
     * 获取结算单详情订单列表（包含导出）
     * @author Nd
     * @date 2022/4/13
     */
    public function getStatementDetail()
    {
        $params = [];
        $params['function_type'] = $this->request->post('function_type', 0,'trim');//0-查询数据，1-导出数据，默认为0
        $params['start_time'] = $this->request->post('start_time', '','trim');
        $params['end_time'] = $this->request->post('end_time', '','trim');
        $params['search_type'] = $this->request->post('search_type', '','trim');//查询类型：1-订单号 ，2-门票名称 ，3-游客， 4-手机号
        $params['search_content'] = $this->request->post('search_content', '','trim');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        try {
            $data = (new LifeToolsDistributionOrderStatementService())->getDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
}