<?php


namespace app\social_activity\controller\socialactivity;


use app\social_activity\model\service\SocialActivityService;
use app\social_activity\validate\SocialActivityValidate;

class SocialActivityController extends AuthBaseController
{
    /**
     * 获取活动列表
     */
    public function getList()
    {
        $params = [
            'page' => input('page', 1, 'trim,intval'),
            'pageSize' => input('pageSize', 10, 'trim,intval'),
            'cat_id' => input('cat_id', 0, 'trim,intval'),
            'name' => input('name', '', 'trim,string'),
            'start_time' => input('start_time', '', 'trim,string'),
            'end_time' => input('end_time', '', 'trim,string'),
            'activity_start_time' => input('activity_start_time', '', 'trim,string'),
            'activity_end_time' => input('activity_end_time', '', 'trim,string'),
            'status' => input('status', -1, 'trim,intval'),
            'activity_status' => input('activity_status', 0, 'trim,intval'),
            'creator_uid' => input('creator_uid', 0, 'trim,intval'),
            'uid' => $this->_uid,
        ];
        try {
            $res = (new SocialActivityService())->getList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取相关信息
     */
    public function getEditInfo()
    {
        $params = [
            'uid' => $this->_uid,
        ];
        try {
            $res = (new SocialActivityService())->getEditInfo($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 保存活动（新增/编辑）
     */
    public function saveActivity()
    {
        $params = $this->request->param();

        $id = $params['id'] ?? 0;
        $scene = $id > 0 ? 'edit' : 'add';

        // 使用验证器验证参数
        $validate = new SocialActivityValidate();
        if (!$validate->scene($scene)->check($params)) {
            return api_output_error(1001, $validate->getError());
        }
        $params['creator_user'] = $this->socialUser;
        try {
            $res = (new SocialActivityService())->saveActivity($params, $id);
            $msg = $id > 0 ? '编辑成功' : '新增成功';
            return api_output(0, ['id' => $res], $msg);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 获取活动详情
     */
    public function getDetail()
    {
        $params = [
            'id' => $this->request->param('id', 0, 'trim,intval'),
            'is_view' => $this->request->param('is_view', 0, 'trim,intval'),
        ];
        try {
            $res = (new SocialActivityService())->getDetail($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 审核活动
     */
    public function auditActivity()
    {
        $params = [
            'id' => $this->request->param('id', 0, 'trim,intval'),
            'status' => $this->request->param('status', 0, 'trim,intval'),
            'remark' => $this->request->param('remark', '', 'trim,string'),
            'editUser' => $this->socialUser,
        ];
        try {
            (new SocialActivityValidate())->scene('audit')->check($params);
            (new SocialActivityService())->auditActivity($params);
            return api_output(0, [], '审核成功');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 推荐/撤回活动
     */
    public function recommendActivity()
    {
        $params = [
            'id' => $this->request->param('id', 0, 'trim,intval'),
            'recommend' => $this->request->param('recommend', 0, 'trim,intval'),
            'editUser' => $this->socialUser,
        ];
        try {
            (new SocialActivityValidate())->scene('recommend')->check($params);
            (new SocialActivityService())->recommendActivity($params);
            $msg = $params['recommend'] == 1 ? '推荐成功' : '撤回成功';
            return api_output(0, [], $msg);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 取消活动
     */
    public function cancelActivity()
    {
        $params = [
            'id' => $this->request->param('id', 0, 'trim,intval'),
            'reason' => $this->request->param('reason', '', 'trim,string'),
            'editUser' => $this->socialUser,
        ];
        try {
            (new SocialActivityValidate())->scene('detail')->check($params);
            (new SocialActivityService())->cancelActivity($params);
            return api_output(0, [], '取消成功');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取场次列表
     */
    public function getSessionList()
    {
        $params = [
            'page' => input('page', 1, 'trim,intval'),
            'pageSize' => input('pageSize', 10, 'trim,intval'),
            'name' => input('name', '', 'trim,string'),
            'activity_id' => input('activity_id', 0, 'trim,intval'),
        ];
        try {
            $res = (new SocialActivityService())->getSessionList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取报名列表
     */
    public function getSignUpList()
    {
        $params = [
            'page' => input('page', 1, 'trim,intval'),
            'pageSize' => input('pageSize', 10, 'trim,intval'),
            'session_id' => input('session_id', 0, 'trim,intval'),
            'start_date' => input('start_date', '', 'trim,string'),
            'end_date' => input('end_date', '', 'trim,string'),
            'check_in_status' => input('check_in_status', -1, 'trim,intval'),
            'is_export' => input('is_export', 0, 'trim,intval'),
        ];
        try {
            $res = (new SocialActivityService())->getSignUpList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 签到接口
     */
    public function signOrder()
    {
        $params = [
            'order_id' => input('order_id', 0, 'trim,intval'),
        ];
        try {
            $res = (new SocialActivityService())->signOrder($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取订单报名表单
     */
    public function getOrderSurveyForm()
    {
        $params = [
            'id' => $this->request->param('id', 0, 'trim,intval'),
        ];
        try {
            $res = (new SocialActivityService())->getOrderSurveyForm($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
