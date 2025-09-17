<?php
namespace app\recruit\controller\api;

use app\recruit\model\service\ResumeLogService;

class ResumeLogController extends ApiBaseController
{
    /**
     * 操作记录列表
     */
    public function recruitResumeLogList()
    {
        $id = $this->request->param("id", 0, "trim");
        $send_id = $this->request->param("send_id", 0, "trim");
        $param['uid'] = $this->_uid;
        try {
            if (!$param['uid']) {
                return api_output_error(1002, L_('请登录'));
            } else {
                if($id < 1){
                    return api_output_error(1003, L_('缺少参数'));
                }
                if($send_id < 1){
                    return api_output_error(1003, L_('缺少参数'));
                }
                $where = [['resume_id', '=', $id], ['send_id', '=', $send_id], ['status','=',0]];
                $fields = '*';
                $list = (new ResumeLogService())->recruitResumeLogList($where, $fields);
                return api_output(0, $list);
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}