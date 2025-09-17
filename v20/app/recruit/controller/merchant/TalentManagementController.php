<?php
/**
 * 人才管理
 */

namespace app\recruit\controller\merchant;


use app\recruit\model\service\CompanyService;
use app\recruit\model\service\TalentManagementService;

class TalentManagementController extends ApiBaseController
{
    /**
     * @return \json
     * 人才管理列表
     */
    public function getList()
    {
        try {


            $merId = $this->request->log_uid;
            $info = (new CompanyService())->getInfoByMerId($merId);
            if (!$info) {
                return api_output(1003, [], L_('商家信息不存在'));
            } else {
                $params['page'] = $this->request->param('page', '1', 'intval');
                $params['pageSize'] = $this->request->param('pageSize', '10', 'intval');

                $params['education'] = $this->request->param('education');
                $params['user_name'] = $this->request->param('user_name');
                $params['job_age'] = $this->request->param('job_age');
                $params['sex'] = $this->request->param('sex');
                $params['job_id'] = $this->request->param('job_id');
                $params['status'] = $this->request->param('status');
                $where = [['m.mer_id', '=', $merId]];
                if (isset($params['education']) && $params['education'] != 0) {
                    array_push($where, ['j.education', '=', $params['education']]);
                }
                if (isset($params['user_name']) && $params['user_name'] != "") {
                    array_push($where, ['r.name', 'like', '%' . $params['user_name'] . '%']);
                }
                if (isset($params['job_age']) && $params['job_age'] != 0) {
                    array_push($where, ['j.job_age', '=', $params['job_age']]);
                }
                if (isset($params['sex']) && $params['sex'] != 0) {
                    array_push($where, ['r.sex', '=', $params['sex']]);
                }
                if (isset($params['job_id']) && $params['job_id'] != 0) {
                    array_push($where, ['j.job_id', '=', $params['job_id']]);
                }
                if (isset($params['status']) && $params['status'] != "") {
                    array_push($where, ['s.inter_status', '=', $params['status']]);
                }
                $list = (new TalentManagementService())->getList($where, $merId, $params['page'], $params['pageSize']);
                return api_output(1000, $list);
            }
        } catch (\Exception $exception) {
        dd($exception);
        }
    }

    /**
     * 简历详情
     */
    public function getResumeMsg()
    {
        $merId = $this->request->log_uid;
        $info = (new CompanyService())->getInfoByMerId($merId);
        if (!$info) {
            return api_output(1003, [], L_('商家信息不存在'));
        } else {
            $params['id'] = $this->request->param('id', '', 'intval');
            $list = (new TalentManagementService())->getResumeMsg($params['id']);
            return api_output(1000, $list);
        }
    }

    /**
     * 历史记录
     */
    public function getLibMsgLIst()
    {
        $merId = $this->request->log_uid;
        $info = (new CompanyService())->getInfoByMerId($merId);
        if (!$info) {
            return api_output(1003, [], L_('商家信息不存在'));
        } else {
            $params['resume_id'] = $this->request->param('resume_id', '', 'intval');
            $params['deliver_id'] = $this->request->param('deliver_id', '', 'intval');
            $list=(new TalentManagementService())->getLibMsg($params['resume_id'],$params['deliver_id']);
            return api_output(1000, $list);
        }
    }
}