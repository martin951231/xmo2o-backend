<?php
/**
 * MallEInvoiceController.php
 * 商家电子发票配置-controller
 * Create on 2020/10/13 14:16
 * Created by zhumengqun
 */

namespace app\mall\controller\merchant;

use app\BaseController;
use app\mall\model\service\MallEInvoiceService;
use app\merchant\controller\merchant\AuthBaseController;

class MallEInvoiceController extends AuthBaseController
{
    /**
     * @根据pigcms_id 获取所有的电子发票开具记录
     * @return \json
     * @author lijie
     * @date_time 2020/07/07
     */
    public function getERecord()
    {
        $param['mer_id'] = $this->merId;
        $param['search_type']=$this->request->param('search_type',1,'trim');
        $param['keyword']=$this->request->param('keyword','','trim');
        $param['start_time']=$this->request->param('start_time','','trim');
        $param['end_time']=$this->request->param('end_time','','trim');
        $param['page']=$this->request->param('page',1,'trim');
        $param['pageSize']=$this->request->param('pageSize',10,'trim');
        $mallEInvoiceService = new MallEInvoiceService();
        try {
            $arr = $mallEInvoiceService->getERecord($param);
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
        $mallEInvoiceService = new MallEInvoiceService();
        try {
            $res = $mallEInvoiceService->getInvoice($fpqqlsh);
            return api_output(0, $res['list'][0]['c_url'], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}