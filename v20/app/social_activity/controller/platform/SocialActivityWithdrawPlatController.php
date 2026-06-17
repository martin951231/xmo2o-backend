<?php


namespace app\social_activity\controller\platform;

use app\social_activity\model\service\SocialActivityWithdrawService;


class SocialActivityWithdrawPlatController extends \app\common\controller\platform\AuthBaseController
{
    /**
     * 获取提现审核列表
     * @return \think\response\Json
     */
    public function getWithdrawList()
    {
        $param = [
            'start_time' => $this->request->param('start_time', '', 'trim,string'),
            'end_time' => $this->request->param('end_time', '', 'trim,string'),
            'status' => $this->request->param('status', '', 'trim,intval'),
            'page' => $this->request->param('page', 1, 'trim,intval'),
            'page_size' => $this->request->param('page_size', 10, 'trim,intval')
        ];
        
        try {
            $service = new SocialActivityWithdrawService();
            $result = $service->getWithdrawList($param);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 提现审核
     * @return \think\response\Json
     */
    public function auditWithdraw()
    {
        $param = [
            'id' => $this->request->param('id', '', 'trim,intval'),
            'audit_status' => $this->request->param('audit_status', '', 'trim,intval'),
            'audit_remark' => $this->request->param('audit_remark', '', 'trim,string'),
            'systemUser' => $this->systemUser,
        ];
        
        try {
            $service = new SocialActivityWithdrawService();
            $result = $service->auditWithdraw($param);
            return api_output(0, $result, '审核成功');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}