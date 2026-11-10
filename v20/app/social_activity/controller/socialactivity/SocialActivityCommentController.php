<?php

namespace app\social_activity\controller\socialactivity;

use app\common\model\service\UserService;
use app\social_activity\controller\socialactivity\AuthBaseController as BaseController;
use app\social_activity\model\db\SocialActivity;
use app\social_activity\model\db\SocialActivitySession;
use app\social_activity\model\service\SocialActivityCommentService;
use app\social_activity\validate\SocialActivityCommentValidate;

class SocialActivityCommentController extends BaseController
{
    protected $validateClass = SocialActivityCommentValidate::class;

    /**
     * 管理端发布活动分享
     */
    public function shareActivity()
    {
        $activityId = $this->request->param('activity_id', 0, 'intval');
        $content = $this->request->param('content', '', 'trim');
        $images = $this->request->param('images', []);

        if (!$activityId) {
            return api_output_error(1001, '活动ID不能为空');
        }

        if (is_string($images)) {
            $decodedImages = json_decode($images, true);
            $images = is_array($decodedImages) ? $decodedImages : [];
        }
        $images = array_values(array_filter(array_map('trim', (array)$images)));

        if ($content === '' && empty($images)) {
            return api_output_error(1001, '请输入分享内容或上传图片');
        }
        if (mb_strlen($content, 'UTF-8') > 500) {
            return api_output_error(1001, '分享内容不能超过500个字');
        }
        if (count($images) > 9) {
            return api_output_error(1001, '单次最多上传9张图片');
        }

        $activity = (new SocialActivity())->where(['id' => $activityId])->findOrEmpty();
        if ($activity->isEmpty()) {
            return api_output_error(1003, '活动不存在');
        }
        if (intval($activity['status']) !== SocialActivity::STATUS_10) {
            return api_output_error(1003, '活动审核通过后才可以分享');
        }

        $session = (new SocialActivitySession())
            ->where(['activity_id' => $activityId])
            ->field('id,village_id')
            ->order('id ASC')
            ->findOrEmpty();
        if ($session->isEmpty()) {
            return api_output_error(1003, '活动场次不存在');
        }

        try {
            $user = (new UserService())->getUserInfoByPhone($this->socialUser['phone']);
            $villageIds = array_filter(explode(',', (string)$session['village_id']));
            $data = (new SocialActivityCommentService())->submitActivityComment([
                'uid' => $user['uid'],
                'activity_id' => $activityId,
                'session_id' => $session['id'],
                'parent_id' => 0,
                'content' => $content,
                'images' => $images,
                'village_id' => intval(reset($villageIds)),
            ]);
            return api_output(0, $data, $data['audit_status_text']);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

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
