<?php

namespace app\social_activity\model\service;

use app\common\model\service\UserService;
use app\community\model\db\HouseVenueActivity;
use app\social_activity\model\db\SocialActivity;
use app\social_activity\model\db\SocialActivityComment;
use app\social_activity\model\db\SocialActivityCommentLike;
use app\social_activity\model\db\SocialActivityCommentSetting;
use app\social_activity\model\db\SocialActivityPointRule;
use app\social_activity\model\db\SocialActivitySensitiveWord;
use app\social_activity\model\db\SocialActivitySession;

class SocialActivityCommentService
{
    /**
     * 获取评论设置
     * @return array
     */
    public function getSettings()
    {
        $settings = (new SocialActivityCommentSetting())->getSettings();
        return $settings;
    }

    /**
     * 保存评论设置
     * @param $param
     * @return bool
     */
    public function saveSettings($param)
    {
        $data = [
            'comment_alias' => $param['comment_alias'],
            'share_audit' => intval($param['share_audit']),
        ];

        $result = (new SocialActivityCommentSetting())->saveSettings($data);
        if ($result === false) {
            throw_exception('保存失败！');
        }

        return true;
    }

    /**
     * 审核评论
     * @param $param
     * @return bool
     */
    public function auditComment($param)
    {
        if (!$param['id']) {
            throw_exception('评论ID不能为空！');
        }
        
        $info = (new SocialActivityComment())->getCommentById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('评论不存在！');
        }
        
        $status = intval($param['audit_status']) == 1 ? SocialActivityComment::STATUS_10 : SocialActivityComment::STATUS_20;
        
        $data = [
            'audit_status' => $status,
        ];
        
        if (!empty($param['audit_remark'])) {
            $data['audit_remark'] = $param['audit_remark'];
        }
        
        $result = (new SocialActivityComment())->where(['id' => $param['id']])->save($data);
        
        if ($result === false) {
            throw_exception('审核失败！');
        }

	    if($status == SocialActivityComment::STATUS_10){
		    $this->addCommentScore($info['activity_id'], $info['uid'], $info['id']);
	    }
        return true;
    }

    /**
     * 批量审核评论
     * @param $params
     * @return bool
     */
    public function batchAuditComment($params)
    {
        if (empty($params['ids']) || !is_array($params['ids'])) {   
            throw_exception('请选择要审核的评论！');
        }
        
        $status = intval($params['audit_status']) == 1 ? SocialActivityComment::STATUS_10 : SocialActivityComment::STATUS_20;
        
        $data = ['audit_status' => $status];
        
        if (!empty($params['audit_remark'])) {
            $data['audit_remark'] = $params['audit_remark'];
        }
        
        $result = (new SocialActivityComment())
            ->where('id', 'in', $params['ids'])
            ->save($data);
        
        if ($result === false) {
            throw_exception('批量审核失败！');
        }
	    if($status == SocialActivityComment::STATUS_10){
			foreach($params['ids'] as $id){
				$info = (new SocialActivityComment())->getCommentById($id);
				if (!$info) {
					continue;
				}
				$this->addCommentScore($info['activity_id'], $info['uid'], $info['id']);
			}
	    }
        return true;
    }

    /**
     * 精选评论
     * @param $param
     * @return bool
     */
    public function selectComment($param)
    {
        if (!$param['id']) {
            throw_exception('评论ID不能为空！');
        }
        
        $info = (new SocialActivityComment())->where(['id' => $param['id']])->find();
        if (!$info || $info->isEmpty()) {
            throw_exception('评论不存在！');
        }
        
        $result = (new SocialActivityComment())->where(['id' => $param['id']])->save([
            'is_hot' => 1,
        ]);
        
        if ($result === false) {
            throw_exception('操作失败！');
        }
        
        return true;
    }

    /**
     * 取消精选评论
     * @param $param
     * @return bool
     */
    public function cancelSelectComment($param)
    {
        if (!$param['id']) {
            throw_exception('评论ID不能为空！');
        }
        
        $info = (new SocialActivityComment())->where(['id' => $param['id']])->find();
        if (!$info || $info->isEmpty()) {
            throw_exception('评论不存在！');
        }
        
        $result = (new SocialActivityComment())->where(['id' => $param['id']])->save([
            'is_hot' => 0,
        ]);
        
        if ($result === false) {
            throw_exception('操作失败！');
        }
        
        return true;
    }

    /**
     * 获取状态文本
     * @param $status
     * @return string
     */
    private function getStatusText($status)
    {
        $statusMap = [
            SocialActivityComment::STATUS_0 => '待审核',
            SocialActivityComment::STATUS_10 => '已通过',
            SocialActivityComment::STATUS_20 => '已拒绝',
        ];
        return $statusMap[$status] ?? '未知';
    }

    /**
     * 获取评论列表（支持按活动或用户筛选）
     * @param $params
     * @return array
     */
    public function getCommentList($params)
    {
        $activity_id = $params['activity_id'] ?? 0;
        $uid = $params['uid'] ?? 0;
        $myUid = $params['my_uid'] ?? 0;
        $page = $params['page'] ?: 1;
        $pageSize = $params['pageSize'] ?: 10;

        $where = [];

	    // 活动名称搜索
	    if (!empty($params['activity_name'])) {
		    $where[] = ['a.name', 'like', '%' . $params['activity_name'] . '%'];
	    }

	    // 活动场次搜索
	    if (!empty($params['session'])) {
		    $where[] = ['s.name', 'like', '%' . $params['session'] . '%'];
	    }

	    // 审核状态筛选
	    if (isset($params['audit_status']) && $params['audit_status'] > -1) {
		    $where[] = ['c.audit_status', '=', intval($params['audit_status'])];
	    }

	    // 评论时间范围搜索
	    if (!empty($params['start_time'])) {
		    $where[] = ['c.create_time', '>=', $params['start_time']];
	    }
	    if (!empty($params['end_time'])) {
		    $where[] = ['c.create_time', '<=', $params['end_time'] . ' 23:59:59'];
	    }
        if ($activity_id) {
            $where[] = ['c.activity_id', '=', $activity_id];
        }
        if (isset($params['from']) && $params['from'] == 'system') {
            $where[] = ['c.parent_id', '=', 0];
        }

        if ($uid) {
            $where[] = ['c.uid', '=', $uid];
        }
        
	    if(isset($params['user'])) {//用户端
		    $where[] = ['c.audit_status', '=', SocialActivityComment::STATUS_10];
			$where[] = ['c.parent_id', '=', 0];
		}

        $field = 'c.id,c.activity_id,c.session_id,c.uid,c.parent_id,c.content,c.images,c.audit_status,
        c.is_hot,c.like_count,c.create_time,u.nickname,u.avatar,a.name as activity_name,a.poster as activity_poster,
        s.name as session_name,s.start_time as session_start_time,s.end_time as session_end_time, s.location,
         s.location_id, a.start_time as activity_start_time, a.end_time as activity_end_time';
        $list = (new SocialActivityComment())
            ->alias('c')
            ->join('user u', 'c.uid = u.uid')
            ->join('social_activity a', 'c.activity_id = a.id')
            ->join('social_activity_session s', 'c.session_id = s.id')
            ->where($where)
            ->field($field)
            ->order('c.create_time DESC')
            ->paginate([
                'list_rows' => $pageSize,
                'page' => $page,
            ]);

        $data = $list ? $list->items() : [];

        $sensitiveWords = (new SocialActivitySensitiveWord())
            ->where(['status' => 1])
            ->column('word');

        $commentIds = array_column($data, 'id');
        $likedCommentIds = [];
        if (!empty($commentIds) && $myUid) {
            $likedCommentIds = (new SocialActivityCommentLike())
                ->where(['comment_id' => $commentIds, 'uid' => $myUid, 'status' => SocialActivityCommentLike::STATUS_1])
                ->column('comment_id');
        }

        foreach ($data as &$item) {
	        if(!empty($item['location_id'])) {
		        $item['session_location'] = HouseVenueActivity::where(['id' => $item['location_id']])->value('title');
	        }
	        $item['audit_status_text'] = $this->getStatusText($item['audit_status']);
	        $item['is_hot_text'] = $item['is_hot'] == 1 ? '已精选' : '未精选';
	        $item['activity_time'] = $item['activity_start_time'] . ' ~ ' . $item['activity_end_time'];
	        $item['comment_time'] = $item['create_time'];
	        if(isset($params['user'])) {
		        $item['content'] = $this->filterSensitiveWords($item['content'], $sensitiveWords);
		        $item['replies'] = $this->getCommentReplies($item['id'], $sensitiveWords);
	        }
            $item['images'] = !empty($item['images']) ? json_decode($item['images'], true) : [];
			if(!empty($item['images'])){
				$item['images'] = array_map(function($v) {
					return replace_file_domain($v);
				}, $item['images']);
			}
            $item['avatar'] = $item['avatar'] ? replace_file_domain($item['avatar']) : '';
            $item['activity_poster'] = $item['activity_poster'] ? replace_file_domain($item['activity_poster']) : '';
            $item['liked'] = in_array($item['id'], $likedCommentIds);
            $item['reply_count'] = (new SocialActivityComment())->where(['parent_id' => $item['id'], 'audit_status' => SocialActivityComment::STATUS_10])->count();
          
            if (!empty($item['location_id'])) {
                $item['location'] = $this->getLocationText($item['location_id']);
            }
        }

        return $list;
    }

    /**
     * 获取场地文本
     * @param int $location_id
     * @return string
     */
    public function getLocationText(int $location_id)
    {
        $location = (new HouseVenueActivity())->where(['status' => 1, 'is_del' => 0, 'id' => $location_id])->value('title');
        return $location ?? '';
    }

    /**
     * 过滤敏感词
     * @param string $content
     * @param array $sensitiveWords
     * @return string
     */
    private function filterSensitiveWords($content, $sensitiveWords)
    {
        if (empty($content) || empty($sensitiveWords)) {
            return $content;
        }

        foreach ($sensitiveWords as $word) {
            $replaceStr = str_repeat('*', mb_strlen($word, 'UTF-8'));
            $content = str_replace($word, $replaceStr, $content);
        }

        return $content;
    }
    /**
     * 获取评论回复列表
     * @param $parentId
     * @return array
     */
    private function getCommentReplies($parentId, $sensitiveWords = [])
    {
        $field = 'c.id,c.activity_id,c.session_id,c.uid,c.parent_id,c.content,c.images,c.audit_status,c.like_count,c.create_time,u.nickname,u.avatar';
        $replies = (new SocialActivityComment())
            ->alias('c')
            ->join('user u', 'c.uid = u.uid', 'LEFT')
            ->where([
                ['c.parent_id', '=', $parentId],
                ['c.audit_status', '=', SocialActivityComment::STATUS_10],
            ])
            ->field($field)
            ->order('c.create_time ASC')
            ->select()
            ->toArray();

        foreach ($replies as &$reply) {
            $reply['images'] = !empty($reply['images']) ? json_decode($reply['images'], true) : [];
            $reply['avatar'] = $reply['avatar'] ? replace_file_domain($reply['avatar']) : '';
            $reply['content'] = $this->filterSensitiveWords($reply['content'], $sensitiveWords);
        }

        return $replies;
    }

    /**
     * 用户提交活动评论
     * @param $params
     * @return array
     */
    public function submitActivityComment($params)
    {
        $uid = $params['uid'];
        $activity_id = $params['activity_id'];
        $session_id = $params['session_id'];
        $parent_id = $params['parent_id'] ?? 0;
        $content = $params['content'];
        $images = $params['images'];

        if (!$uid) {
            throw new \Exception('请先登录');
        }
        if (!$activity_id) {
            throw new \Exception('活动ID不能为空');
        }
        if (!$session_id) {
            throw new \Exception('场次ID不能为空');
        }
        if (empty($content)) {
            throw new \Exception('评论内容不能为空');
        }

        $activity = (new SocialActivity())->where(['id' => $activity_id])->findOrEmpty();
        if ($activity->isEmpty()) {
            throw new \Exception('活动不存在');
        }

        $session = (new SocialActivitySession())->where(['id' => $session_id, 'activity_id' => $activity_id])->findOrEmpty();
        if ($session->isEmpty()) {
            throw new \Exception('场次不存在');
        }

        if ($parent_id > 0) {
            $parentComment = (new SocialActivityComment())->where(['id' => $parent_id])->findOrEmpty();
            if ($parentComment->isEmpty()) {
                throw new \Exception('父评论不存在');
            }
        }

        $imagesData = [];
        if (!empty($images)) {
            $imagesData = is_string($images) ? json_decode($images, true) : $images;
            if (!is_array($imagesData)) {
                $imagesData = [$images];
            }
        }

        $commentSetting = (new SocialActivityCommentSetting())->getSettings();
        $audit_status = !empty($commentSetting['share_audit']) ? SocialActivityComment::STATUS_0 : SocialActivityComment::STATUS_10;
        if ($parent_id > 0) {
            $audit_status = SocialActivityComment::STATUS_10;
        }
        $data = [
            'activity_id' => $activity_id,
            'session_id' => $session_id,
            'uid' => $uid,
            'parent_id' => $parent_id,
            'content' => $content,
            'images' => !empty($imagesData) ? json_encode($imagesData, JSON_UNESCAPED_UNICODE) : '',
            'audit_status' => $audit_status,
            'like_count' => 0,
            'is_hot' => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
            'village_id' => $params['village_id'] ?? 0,
        ];

        $result = (new SocialActivityComment())->create($data);
        if (!$result->id) {
            throw new \Exception('评论失败');
        }
        if($audit_status == SocialActivityComment::STATUS_10){
            $this->addCommentScore($activity_id, $uid, $result->id);
        }

        return [
            'comment' => $result,
            'audit_status' => $audit_status,
            'audit_status_text' => $audit_status == SocialActivityComment::STATUS_10 ? '发布成功' : '评论已提交，等待审核',
        ];
    }
	
	public function addCommentScore($activity_id, $uid, $comment_id)
	{
		$activity = SocialActivity::where(['id' => $activity_id])->find();
		if(!$activity){
			return false;
		}
		$internalScore = SocialActivityPointRule::where('id', $activity['rule_id'])->value('share_point');
		if(!$internalScore){
			return false;
		}
		$comment = SocialActivityComment::where(['id' => $comment_id])->find();
		if(!$comment){
			return false;
		}
		$comment->save(['rule_id' => $activity['rule_id']]);
		$desc = "【{$activity['name']}】用户评论获得积分{$internalScore}";
		return (new UserService())->addScore($uid, $internalScore, $desc, 0, [
			'order_type' => 'social_activity_comment',
			'order_id' => $comment_id,
			'village_id' => $comment['village_id'],
		]);
	}

    /**
     * 评论点赞/取消点赞
     * @param $params
     * @return array
     */
    public function likeComment($params)
    {
        $uid = $params['uid'];
        $comment_id = $params['comment_id'];

        if (!$uid) {
            throw new \Exception('请先登录');
        }
        if (!$comment_id) {
            throw new \Exception('评论ID不能为空');
        }

        $comment = (new SocialActivityComment())->where(['id' => $comment_id])->findOrEmpty();
        if ($comment->isEmpty()) {
            throw new \Exception('评论不存在');
        }

        $likeRecord = (new SocialActivityCommentLike())
            ->where(['comment_id' => $comment_id, 'uid' => $uid])
            ->findOrEmpty();

        if ($likeRecord->isEmpty()) {
            $data = [
                'comment_id' => $comment_id,
                'uid' => $uid,
                'status' => SocialActivityCommentLike::STATUS_1,
                'create_time' => date('Y-m-d H:i:s'),
            ];
            (new SocialActivityCommentLike())->insert($data);

            (new SocialActivityComment())->where(['id' => $comment_id])->inc('like_count')->update();

            return [
                'is_liked' => true,
                'like_count' => $comment['like_count'] + 1,
            ];
        } else {
            if ($likeRecord['status'] == SocialActivityCommentLike::STATUS_1) {
                $likeRecord->status = SocialActivityCommentLike::STATUS_2;
                $likeRecord->save();

                (new SocialActivityComment())->where(['id' => $comment_id])->dec('like_count')->update();

                return [
                    'is_liked' => false,
                    'like_count' => max(0, $comment['like_count'] - 1),
                ];
            } else {
                $likeRecord->status = SocialActivityCommentLike::STATUS_1;
                $likeRecord->save();

                (new SocialActivityComment())->where(['id' => $comment_id])->inc('like_count')->update();

                return [
                    'is_liked' => true,
                    'like_count' => $comment['like_count'] + 1,
                ];
            }
        }
    }
}
