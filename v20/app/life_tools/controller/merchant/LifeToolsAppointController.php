<?php


namespace app\life_tools\controller\merchant;

use app\life_tools\model\service\appoint\LifeToolsAppointJoinOrderService;
use app\merchant\controller\merchant\AuthBaseController;
use app\life_tools\model\service\appoint\LifeToolsAppointService;

class LifeToolsAppointController extends AuthBaseController
{
    /**
     * 预约列表
     */
    public function getList()
    {
        $param['title'] = $this->request->param('title', '', 'trim'); //活动标题
        $param['start_time'] = $this->request->param('start_time', '', 'trim'); //活动开始时间
        $param['end_time'] = $this->request->param('end_time', '', 'trim'); //活动结束时间
        $param['page'] = $this->request->param('page', 1, 'intval'); //页码
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval'); //每页展示数量
        $param['mer_id'] = $this->merId;
        try {
            $list = (new LifeToolsAppointService())->getList($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 关闭预约
     */
    public function closeAppoint()
    {
        $param['status'] = $this->request->param('status', 0, 'intval'); //状态
        $param['appoint_id'] = $this->request->param('appoint_id', 0, 'intval'); //id
        $param['mer_id'] = $this->merId;
        try {
            if (empty($param['appoint_id'])) {
                return api_output_error(1003, "缺少必要参数");
            }
            $list = (new LifeToolsAppointService())->closeAppoint($param);
            if ($list) {
                return api_output(0, []);
            } else {
                return api_output_error(1003, "状态改变失败");
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 删除预约
     */
    public function delAppoint()
    {
        $param['appoint_id'] = $this->request->param('appoint_id', 0, 'intval'); //id
        $param['mer_id'] = $this->merId;
        try {
            if (empty($param['appoint_id'])) {
                return api_output_error(1003, "缺少必要参数");
            }
            $list = (new LifeToolsAppointService())->delSport($param);
            if ($list) {
                return api_output(0, []);
            } else {
                return api_output_error(1003, "删除失败");
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * 用户报名信息
     */
    public function lookAppointUser()
    {
        $param['appoint_id'] = $this->request->param('appoint_id', 0, 'intval'); //id
        $param['date_type'] = $this->request->post('date_type', 0, 'intval'); //0=报名日期，1=核销日期
        $param['date_start'] = $this->request->post('date_start', '', 'trim');
        $param['date_end'] = $this->request->post('date_end', '', 'trim');
        $param['page_size'] = $this->request->post('page_size', 10, 'intval');
        $param['status'] = $this->request->post('status', 0, 'intval'); //状态：0=全部，1-未核销 3-已核销 5-已退款
        $param['keywords'] = $this->request->post('keywords', '', 'trim'); //关键词
        $param['mer_id'] = $this->merId;
        try {
            if (empty($param['appoint_id'])) {
                return api_output_error(1003, "缺少必要参数");
            }
            $list = (new LifeToolsAppointService())->lookAppointUser($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 自定义暂停信息
     */
    public function suspend()
    {
        $params['appoint_id'] = $this->request->post('appoint_id', 0, 'intval'); 
        $params['is_suspend'] = $this->request->post('is_suspend', 0, 'intval'); 
        $params['suspend_msg'] = $this->request->post('suspend_msg', '', 'trim'); 
        try {
            if (empty($params['appoint_id']) || ($params['is_suspend'] == 1 && empty($params['suspend_msg']))) {
                return api_output_error(1003, "缺少必要参数");
            }
            $data = (new LifeToolsAppointService())->suspend($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 用户报名信息详情
     */
    public function getAppointOrderDetail()
    {
        $order_id = $this->request->post('order_id', 0, 'intval'); //id
        try {
            if (empty($order_id)) {
                return api_output_error(1003, "缺少必要参数");
            }
            $data = (new LifeToolsAppointService())->getAppointOrderDetail($order_id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 导出预约报名信息
     * @return \json
     */
    public function exportUserOrder()
    {
        $param['type'] = 'pc';
        $param['appoint_id'] = $this->request->param('appoint_id', 0, 'intval'); //id
        $param['re_type'] = 'platform';
        $param['use_type'] = 2; //1=列表使用  2= 导出使用
        $orderService = new LifeToolsAppointService();
        try {
            $arr = $orderService->addOrderExport($param, [], $this->merchantUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            dd($e);
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 获取预约信息
     */
    public function getToolAppointMsg()
    {
        $param['appoint_id'] = $this->request->param('appoint_id', 0, 'intval'); //id
        $param['mer_id'] = $this->merId;
        $orderService = new LifeToolsAppointService();
        try {
            $arr = $orderService->getToolAppointMsg($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            dd($e);
            //return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 审核退款
     */
    public function auditRefund()
    {
        $param['pigcms_id']      = $this->request->param('order_id', 0, 'intval');
        $param['type']      = $this->request->param('type', 0, 'intval');

        $ret = (new LifeToolsAppointJoinOrderService())->auditRefund($param);
        return api_output(0, $ret, 'success');
    }

    /**
     * 保存预约活动
     */
    public function saveToolAppoint()
    {
        $param['appoint_id'] = $this->request->param('appoint_id', 0, 'intval'); //id
        $param['title'] = $this->request->param('title', '', 'trim');
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['label'] = $this->request->param('label', '', 'trim');
        $param['phone'] = $this->request->param('phone', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['price'] = $this->request->param('price', 0, 'trim');
        $param['send_notice_days'] = $this->request->param('send_notice_days', 0, 'intval');
        $param['address'] = $this->request->param('address', '', 'trim');
        $param['long'] = $this->request->param('long', '', 'trim');
        $param['lat'] = $this->request->param('lat', '', 'trim');
        $param['province_id'] = $this->request->param('province_id', 0, 'intval');
        $param['city_id'] = $this->request->param('city_id', 0, 'intval');
        $param['area_id'] = $this->request->param('area_id', 0, 'intval');
        $param['image_big'] = $this->request->param('image_big', '', 'trim');
        $param['image_small'] = $this->request->param('image_small', '', 'trim');
        $param['limit_type'] = $this->request->param('limit_type', 0, 'intval');
        $param['limit_num'] = $this->request->param('limit_num', 0, 'intval');
        $param['need_verify'] = $this->request->param('need_verify', 0, 'intval');
        $param['people_type'] = $this->request->param('people_type', 0, 'intval');
        $param['can_refund'] = $this->request->param('can_refund', 0, 'intval');
        $param['refund_hours'] = $this->request->param('refund_hours', 0, 'intval');
        $param['desc'] = $this->request->param('desc', '', 'trim');
        $param['mer_id'] = $this->merId;

        $param['is_custom_form'] = $this->request->post('is_custom_form', 0, 'intval');
        $param['custom_form'] = $this->request->post('custom_form', []);
        $param['is_select_seat'] = $this->request->post('is_select_seat', 0, 'intval');
        $param['is_multi_code'] = $this->request->post('is_multi_code', 1, 'intval');
        $param['seat_row'] = $this->request->post('seat_row', 0, 'intval');
        $param['seat_col'] = $this->request->post('seat_col', 0, 'intval');
        $param['limit'] = $this->request->post('limit', 0, 'intval');
        $param['seat_data'] = $this->request->post('seat_data', []);

        //多规格
        $param['is_sku'] = $this->request->post('is_sku', '0', 'intval'); //是否多规格 1是
        $param['spec_list'] = $this->request->post('spec_list') ?? [];
        $param['sku_list'] = $this->request->post('sku_list') ?? [];
        $param['appoint_start_time'] = $this->request->post('appoint_start_time') ?? '';
        $param['appoint_end_time'] = $this->request->post('appoint_end_time') ?? '';
        $param['appoint_btn_txt'] = $this->request->post('appoint_btn_txt') ?? '';

        $orderService = new LifeToolsAppointService();
        try {
            $arr = $orderService->saveToolAppoint($param);
            if (empty($arr)) {
                return api_output_error('1003', "编辑失败");
            } else {
                return api_output(0, $arr, 'success');
            }
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 座位分布列表
     */
    public function getSeatMap()
    {
        $params = [];
        $params['appoint_id'] = $this->request->post('appoint_id', 0, 'intval');
        $params['row'] = $this->request->post('row', 0, 'intval');
        $params['col'] = $this->request->post('col', 0, 'intval');
        $params['seat_title'] = $this->request->post('seat_title', '', 'trim');
        $params['is_buy'] = $this->request->post('is_buy', null, 'intval');
        $params['seat_price'] = $this->request->post('seat_price', null, 'intval');
        $params['seat_num'] = $this->request->post('seat_num', []);
        try {
            $arr = (new LifeToolsAppointService())->getSeatMap($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage() . $e->getLine());
        }
    }
}
