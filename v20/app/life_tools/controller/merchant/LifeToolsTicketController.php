<?php
/**
 * 门票控制器
 */

namespace app\life_tools\controller\merchant;

use app\life_tools\model\service\LifeToolsTicketService;
use app\mall\model\service\MallGoodsService;
use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;

class LifeToolsTicketController extends AuthBaseController
{

    /**
     * 获取列表
     * @return \json
     */
    public function getList()
    {
        $param['tools_id'] = $this->request->param('tools_id', 0, 'intval');
        $param['page'] = $this->request->param('page', 0, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['audit_status'] = $this->request->param('audit_status', '');
        if($param['audit_status'] === ''){
            $param['audit_status'] = null;
        }
        $param['mer_id'] = $this->merId;
        $service = new LifeToolsTicketService();

        $arr = $service->getList($param);
        return api_output(0, $arr, 'success');
    }

    /**
     * @author zhumengqun
     * 获取商家分类
     */
    public function getMerchantSort()
    {
        $mer_id = $this->merId;
        $merchantSortService = new LifeToolsTicketService();
        try {
            $param['source'] = $this->request->param('source', 'limited', 'trim');
            $arr = $merchantSortService->getSortList($mer_id, $param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 门票列表
     */
    public function getLifeToolsTicket(){
        $param['tools_id'] = $this->request->param('tools_id', '', 'intval');
        $param['keyword'] = $this->request->param('search_keyword', '', 'trim') ?: $this->request->param('keyword', '', 'trim');
        $param['source'] = $this->request->param('source', 'limited', 'trim');
        $param['mer_id'] = $this->merId;
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['group_type'] = $this->request->param('group_type', []);
        $param['targeTage'] = $this->request->param('targeTage',[]);
        $param['start_time'] = $this->request->param('start_time','');
        $mallGoodsService = new LifeToolsTicketService();
        try {
            $arr = $mallGoodsService->getTickectSelect($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 新增或编辑
     * @return \json
     */
    public function addOrEdit()
    {
        $param['mer_id']             = $this->merId;// 商家ID
        $param['ticket_id']          = $this->request->param('ticket_id', '', 'intval');// 门票ID
        $param['tools_id']           = $this->request->param('tools_id', '', 'intval');
        $param['sort']               = $this->request->param('sort', '', 'intval');
        $param['title']              = $this->request->param('title', '', 'trim');
        $param['description']        = $this->request->param('description', '', 'trim');// 预定须知
        $param['old_price']          = $this->request->param('old_price', '', 'trim');// 老价格
        $param['price']              = $this->request->param('price', '', 'trim');//现价
        $param['start_time']         = $this->request->param('start_time', '', 'trim');
        $param['end_time']           = $this->request->param('end_time', '', 'trim');
        $param['stock_type']         = $this->request->param('stock_type', '2', 'trim');// 库存类型1-永久库存2-每日库存
        $param['stock_num']          = $this->request->param('stock_num', '0', 'trim');// 库存总数量
        $param['status']             = $this->request->param('status', '1', 'intval');
        $param['is_refund']          = $this->request->param('is_refund', '0', 'intval');
        $param['is_appoint']         = $this->request->param('is_appoint', '0', 'trim');// 是否需要预约
        $param['price_calendar']     = $this->request->param('price_calendar', []);
        $param['can_book_today']     = $this->request->param('can_book_today', '0', 'intval');// 是否可预订当天的门票
        $param['book_today_time']    = $this->request->param('book_today_time', '', 'trim');// 预定当天门票截止时间
        $param['course_end_time']    = $this->request->param('course_end_time', '', 'trim');// 课程结束时间
        $param['open_custom_form']   = $this->request->param('open_custom_form', '', 'trim');// 是否开启自定义表单填写 1开启 0关闭
        $param['label']              = $this->request->param('label', []);
        $param['custom_form']        = $this->request->param('custom_form', []);
        $param['scenic_ticket_type'] = $this->request->param('scenic_ticket_type', 0);
        
        $param['is_sku'] = $this->request->param('is_sku', '0', 'intval');//是否多规格 1是
        $param['spec_list']         = $this->request->param('spec_list') ?? [];
        $param['list']              = $this->request->param('sku_list') ?? [];
        $param['staff_ids']         = $this->request->param('staff_ids', []);

        $param['scenic_ticket_type'] = $this->request->post('scenic_ticket_type', '', 'trim');
        $param['date_ticket_start'] = $this->request->post('date_ticket_start', '', 'trim');
        $param['date_ticket_end'] = $this->request->post('date_ticket_end', '', 'trim');
        $service = new LifeToolsTicketService();
       
        $res = $service->addOrEdit($param);
        return api_output(0, $res, 'success');
    }

    /**
     * 获得门票详情
     * @return \json
     */
    public function getDetail()
    {
        $param['ticket_id'] = $this->request->param('ticket_id', '', 'intval');
        $param['mer_id'] = $this->merId;// 商家ID
        $service = new LifeToolsTicketService();
        $arr = $service->getDetail($param);
        return api_output(0, $arr, 'success');
    }

    /**
    * 获得编辑添加所需参数
    * @return \json
    */
   public function getEditInfo()
   {
       $param['tools_id'] = $this->request->param('tools_id', '', 'intval');
       $param['mer_id'] = $this->merId;// 商家ID
       $service = new LifeToolsTicketService();
       $arr = $service->getEditInfo($param);
       return api_output(0, $arr, 'success');
   }

    /**
     * 删除门票
     * @return \json
     */
    public function del()
    {
        $param['ticket_id'] = $this->request->param('ticket_id', '', 'intval');
        $param['mer_id'] = $this->merId;// 商家ID
        $service = new LifeToolsTicketService();
        $res = $service->del($param);
        return api_output(0, $res, 'success');
    }


    /**
     * 店员管理
     */
    public function getStaffList()
    {
        //获取店员列表
        $condition = [['token', '=',  $this->merId]];
        $field = "id,name";
        $order = "id desc";
        $list = (new MerchantStoreStaffService())->getStaffListByCondition($condition, $field, $order);
        $list['list'] = $list;
        return api_output(1000, $list, 'success');
    }

}
