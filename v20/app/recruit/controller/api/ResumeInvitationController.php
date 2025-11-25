<?php
namespace app\recruit\controller\api;

use app\recruit\model\service\ResumeInvitationService;
use app\recruit\model\service\ResumeSendService;
use app\recruit\model\service\HrService;
use app\recruit\model\service\RecruitResumeService;

class ResumeInvitationController extends ApiBaseController
{
    /**
     * 面试邀请详情
     */
    public function recruitResumeInvitationDetails(){
        $id = $this->request->param("id", 0, "trim");
        if ($id < 1) {
            return api_output_error(1003, L_('缺少参数'));
        } else {
            $list = (new ResumeInvitationService())->recruitResumeInvitationDetails($id);
            if(empty($list)){
                return api_output_error(1003, L_('邀请记录不存在'));
            }
            return api_output(0, $list);
        }
    }

    /**
     * 面试邀请保存
     */
    public function recruitResumeInvitationList(){
        $params['uid'] = $this->_uid;
        $params['company_id'] = $this->request->param("company_id", 0, "trim");
        if($params['company_id']==0){
            $companyId = (new HrService())->getInfoByUid($params['uid']);
            $params['company_id'] = $companyId['mer_id'];
        }
        $params['send_id'] = $this->request->param("send_id", 0, "trim");
        $params['position_id'] = $this->request->param("position_id", 0, "trim");
        $params['resume_id'] = $this->request->param("resume_id", 0, "trim");
        $params['to_uid'] = $this->request->param("to_uid", 0, "trim");
        $params['address'] = $this->request->param("address", '', "trim");
        $params['name'] = $this->request->param("name", '', "trim");
        $params['phone'] = $this->request->param("phone", 0, "trim");
        $params['position_name'] = $this->request->param("position_name", '', "trim");
        $params['remark'] = $this->request->param("remark", '', "trim");
        $params['invitation_time'] = $this->request->param('invitation_time', 0, 'strtotime');
        $params['add_time'] = time();
        if($params['resume_id'] < 1){
            $resumeId = (new RecruitResumeService())->recruiTresumeId(['uid'=>$params['to_uid']]);
            if($resumeId){
                $params['resume_id'] = $resumeId['id'];
            }
        }
        if ($params['uid'] < 1) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $data = array(
                'company_id'=>$params['company_id'],
                'position_id'=>$params['position_id'],
                'send_id'=>$params['send_id'],
                'resume_id'=>$params['resume_id'],
                'to_uid'=>$params['to_uid'],
                'uid'=>$params['uid']
            );
            if($params['send_id'] == 0){
                $datas = array(
                    'company_id' => $params['company_id'],
                    'position_id' => $params['position_id'],
                    'resume_id' => $params['resume_id'],
                    'uid' => $params['to_uid'],
                    'position_name' => $params['position_name'],
                    'status' => 2,
                    'inter_status' => 1,
                    'add_time' => time(),
                );
                $sendId = (new ResumeSendService())->recruitInvitationSend($datas);
                $params['send_id'] = $sendId;
            }
            $return_find = (new ResumeInvitationService())->recruitResumeInvitations($data);
            if(empty($return_find)){
                $id = 0;
            }else{
                return api_output_error(1001, L_('不能重复邀请'));
                $id = $return_find['id'];
            }
            $list = (new ResumeInvitationService())->recruitResumeInvitationList($id, $params);
            return api_output(0, $list);
        }
    }

    /**
     * 面试邀操作
     */
    public function recruitResumeInvitationOperation(){
        $params['uid'] = $this->_uid;
        // $params['uid'] = 112358755;
        if ($params['uid'] < 1) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $params['filed_status'] = $this->request->param("filed_status", 0, "trim");
            $params['filed_remarks'] = $this->request->param("filed_remarks", '', "trim");
            $params['send_id'] = $this->request->param("send_id", '', "trim");
            $params['resume_id'] = $this->request->param("resume_id", '', "trim");
            $params['position_id'] = $this->request->param("position_id", '', "trim");
            $params['company_id'] = $this->request->param("company_id", '', "trim");
            if(!$params['filed_status'] || !$params['resume_id'] || !$params['position_id']){
                return api_output_error(1003, L_('缺少参数'));
            }
            $list = (new ResumeSendService())->recruitResumeInvitationOperation($params);
            return api_output(0, $list);
        }
    }

    /**
     * 面试拒绝、接受操作
     */
    public function recruitResumeInvitationRefuse(){
        $params['uid'] = $this->_uid;
        // $params['uid'] = 112358755;
        if ($params['uid'] < 1) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $params['status'] = $this->request->param("status", 0, "trim");
            $params['resume_id'] = $this->request->param("resume_id", 0, "trim");
            $params['send_id'] = $this->request->param("send_id", 0, "trim");
            $params['position_id'] = $this->request->param("position_id", 0, "trim");
            $params['hr_uid'] = $this->request->param("hr_uid", 0, "trim");
            if(!$params['status'] || !$params['resume_id'] || !$params['position_id']){
                return api_output_error(1003, L_('缺少参数'));
            }
            $list = (new ResumeSendService())->recruitResumeInvitationRefuse($params);
            return api_output(0, $list);
        }
    }

    //  1k 转 1000
    function expand_k($str) {
        if ($str[strlen($str) - 1] !== "k") {
            return $str;
        }
        $no_k = str_replace("k", "", $str);
        $dotted = str_replace("," , ".", $no_k);
        return $dotted * 1000;
    }  
}