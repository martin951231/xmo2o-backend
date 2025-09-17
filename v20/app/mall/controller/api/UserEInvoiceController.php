<?php
/**
 * UserEInvoiceController.php
 * 商城用户申请发票
 * Create on 2020/10/13 15:13
 * Created by zhumengqun
 */

namespace app\mall\controller\api;

use app\BaseController;
use app\mall\model\service\MallEInvoiceService;

class UserEInvoiceController extends BaseController
{
    /**
     * 用户申请开发票
     * @return \json
     */
    public function addInvoice()
    {
        $post_params['uid'] = request()->log_uid ? request()->log_uid : 0;
        $post_params['head_up_type'] = $this->request->param('head_up_type', 1, 'intval');
        $post_params['invoice_title'] = $this->request->param('invoice_title', '', 'trim');
        $post_params['tax_num'] = $this->request->param('tax_num', '', 'trim');
        $post_params['deposit_bank'] = $this->request->param('deposit_bank', '', 'trim');
        $post_params['account_number'] = $this->request->param('account_number', '', 'trim');
        $post_params['user_address'] = $this->request->param('user_address', '', 'trim');
        $post_params['user_phone'] = $this->request->param('user_phone', '', 'trim');
        $post_params['order_id'] = $this->request->param('order_id', '', 'intval');
        $post_params['mer_id'] = $this->request->param('mer_id', '', 'intval');
        $post_params['store_id'] = $this->request->param('store_id', '', 'intval');
        $post_params['detail'] = $this->request->param('detail', '');
        $post_params['is_default'] = $this->request->param('is_default', 0,'intval');
        $post_params['create_time'] = time();
        try {
            $e_incoice = new MallEInvoiceService();
            $res = $e_incoice->addInvoice($post_params);
            return api_output(0, $res, '提交成功，请注意短信查收');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取电子发票展示信息
     */
    public function showInvoice()
    {
        $post_params['uid'] = request()->log_uid ? request()->log_uid : 0;
        $post_params['order_id'] = $this->request->param('order_id', '', 'intval');
        try {
            $e_incoice = new MallEInvoiceService();
            $arr = $e_incoice->showInvoice($post_params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * 根据流水号获取电子发票pdf
     * @return \json
     * @author lijie
     * @date_time 2020/07/07
     */
    public function getInvoice()
    {
        $fpqqlsh = $this->request->param('fpqqlsh', '', 'trim');
        $e_incoice = new MallEInvoiceService();
        try {
            $res = $e_incoice->getInvoice($fpqqlsh);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 设置默认的发票信息
     */
    public function getDefaultInfo(){
        $uid = request()->log_uid ? request()->log_uid : 0;
        try {
            $e_incoice = new MallEInvoiceService();
            $arr = $e_incoice->getDefaultInfo($uid);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}