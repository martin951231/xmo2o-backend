<?php
namespace app\social_activity\controller\api;

use app\common\controller\api\ApiBaseController;
use app\social_activity\model\service\SocialActivityCommentService;
use app\social_activity\model\service\SocialActivityOrderService;
use app\social_activity\model\service\SocialActivityPointDetailService;
use app\social_activity\model\service\SocialActivityService;

class ActivityController extends ApiBaseController
{
    /**
     * 获取H5底部导航栏菜单
     */
    public function getFooterMenuList()
    {
        $activityService = new SocialActivityService();
        try {
            $data = $activityService->getFooterMenuList();
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }

    /**
     * 获取活动列表
     */
    public function getActivityList()
    {
        $params = [
            'page' => input('page', 1, 'trim,intval') ?: 1,
            'pageSize' => input('pageSize', 10, 'trim,intval') ?: 10,
            'category_id' => input('category_id', 0, 'trim,intval'),
            'name' => input('name', '', 'trim'),
            'activity_status' => input('activity_status', 0, 'trim,intval'),
            'recommend' => input('recommend', '', 'trim'),
            'date' => input('date', '', 'trim'),
        ];

        $activityService = new SocialActivityService();
        try {
            $data = $activityService->getActivityList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }

    /**
     * 获取活动详情
     */
    public function getActivityDetail()
    {
        $params = [
            'id' => input('id', 0, 'trim,intval'),
            'is_sign' => input('is_sign', 0, 'trim,intval'),
            'sign_session_id' => input('sign_session_id', 0, 'trim,intval'),
            'uid' => $this->uid,
	        'session_date' => input('session_date', '', 'trim'),
	        'order_id' => input('order_id', 0, 'trim,intval'),
        ];

        $activityService = new SocialActivityService();
        try {
            $data = $activityService->getActivityDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }

    /**
     * 获取首页数据
     */
    public function getHomeData()
    {
        $activityService = new SocialActivityService();
        try {
            $data = $activityService->getHomeData();
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }

    /**
     * 获取活动评论列表
     */
    public function getActivityCommentList()
    {
        $params = [
            'activity_id' => input('activity_id', 0, 'trim,intval'),
            'page' => input('page', 1, 'trim,intval'),
            'pageSize' => input('pageSize', 10, 'trim,intval'),
            'user' => true,
        ];
        $params['my_uid'] = $this->uid;

        $commentService = new SocialActivityCommentService();
        try {
            $data = $commentService->getCommentList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }

    /**
     * 用户提交活动评论
     */
    public function submitActivityComment()
    {
        $params = [
            'activity_id' => input('activity_id', 0, 'trim,intval'),
            'session_id' => input('session_id', 0, 'trim,intval'),
            'parent_id' => input('parent_id', 0, 'trim,intval'),
            'village_id' => input('village_id', 0, 'trim,intval'),
            'content' => input('content', '', 'trim'),
            'images' => input('images', '', 'trim'),
        ];
        $params['uid'] = $this->uid;

        $commentService = new SocialActivityCommentService();
        try {
            $data = $commentService->submitActivityComment($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, '评论成功');
    }

    /**
     * 获取用户积分列表
     */
    public function getUserPointList()
    {
        $params = [
            'page' => input('page', 1, 'trim,intval'),
            'pageSize' => input('pageSize', 10, 'trim,intval'),
        ];
        $params['uid'] = $this->uid;

        $pointService = new SocialActivityPointDetailService();
        try {
            $data = $pointService->getUserPointList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }

    /**
     * 获取我的订单列表
     */
    public function getMyOrderList()
    {
        $params = [
            'page' => input('page', 1, 'trim,intval'),
            'pageSize' => input('pageSize', 10, 'trim,intval'),
            'filter_type' => input('filter_type', 'all', 'trim'),
        ];
        $params['uid'] = $this->uid;

        $orderService = new SocialActivityOrderService();
        try {
            $data = $orderService->getMyOrderList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }

    /**
     * 获取个人中心数据
     */
    public function getMyCenter()
    {
        $uid = $this->uid;
        if (!$uid) {
            return api_output_error(1003, '请先登录');
        }

        $activityService = new SocialActivityService();
        try {
            $data = $activityService->getMyCenter($uid);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }

    /**
     * 获取我的评论列表
     */
    public function getMyCommentList()
    {
        $params = [
            'page' => input('page', 1, 'trim,intval'),
            'pageSize' => input('pageSize', 10, 'trim,intval'),
        ];
        $params['uid'] = $this->uid;

        $commentService = new SocialActivityCommentService();
        try {
            $data = $commentService->getCommentList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }

    /**
     * 评论点赞/取消点赞
     */
    public function likeComment()
    {
        $params = [
            'comment_id' => input('comment_id', 0, 'trim,intval'),
        ];
        $params['uid'] = $this->uid;

        $commentService = new SocialActivityCommentService();
        try {
            $data = $commentService->likeComment($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $data, 'success');
    }


}
