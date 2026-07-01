<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\controller\socialactivity\AuthBaseController as BaseController;
use app\social_activity\model\service\SocialActivityCommentService;
use app\social_activity\validate\SocialActivityCommentValidate;

class SocialActivityCommentController extends BaseController
{
    protected $validateClass = SocialActivityCommentValidate::class;

    /**
     * 获取评论设置
     */
    public function getSettings()
    {
        $commentService = new SocialActivityCommentService();
        try {
            $data = $commentService->getSettings();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 保存评论设置
     */
    public function saveSettings()
    {
        $param['comment_alias'] = $this->request->param('comment_alias', '', 'trim');
        $param['share_audit'] = $this->request->param('share_audit', 1, 'int');
        $param = $this->validateParameter($param, 'saveSettings');
        
        $commentService = new SocialActivityCommentService();
        try {
            $commentService->saveSettings($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "保存成功");
    }

    /**
     * 获取活动评论列表
     */
    public function getCommentList()
    {
        $param['page'] = $this->request->param('page', 1, 'int');
        $param['pageSize'] = $this->request->param('pageSize', 20, 'int');
        $param['activity_name'] = $this->request->param('activity_name', '', 'trim');
        $param['session'] = $this->request->param('session', '', 'trim');
        $param['audit_status'] = $this->request->param('status', 0, 'int');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['from'] = 'system';
        $commentService = new SocialActivityCommentService();
        try {
            $data = $commentService->getCommentList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
		return json([
			'status' => 1000,
			'msg' 	 => 'success',
			'data' 	 => $data
		]);
    }

    /**
     * 审核评论
     */
    public function auditComment()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        $param['audit_status'] = $this->request->param('audit_status', 1, 'int');
        $param = $this->validateParameter($param, 'audit');
        
        $commentService = new SocialActivityCommentService();
        try {
            $commentService->auditComment($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "审核成功");
    }

    /**
     * 批量审核评论
     */
    public function batchAuditComment()
    {
        $param['ids'] = $this->request->param('ids', []);
        $param['audit_status'] = $this->request->param('audit_status', 1, 'int');
        $param = $this->validateParameter($param, 'batchAudit');
        
        $commentService = new SocialActivityCommentService();
        try {
            $commentService->batchAuditComment($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "批量审核成功");
    }

    /**
     * 精选评论
     */
    public function selectComment()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        $param = $this->validateParameter($param, 'select');
        
        $commentService = new SocialActivityCommentService();
        try {
            $commentService->selectComment($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "精选成功");
    }

    /**
     * 取消精选评论
     */
    public function cancelSelectComment()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        $param = $this->validateParameter($param, 'cancelSelect');
        
        $commentService = new SocialActivityCommentService();
        try {
            $commentService->cancelSelectComment($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "操作成功");
    }
}
