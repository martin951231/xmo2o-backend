<?php

namespace app\social_activity\model\service;

use app\common\model\db\UserScoreList;
use app\community\model\db\HouseVillage;
use app\social_activity\model\db\SocialActivityComment;
use app\social_activity\model\db\SocialActivityOrder;
use app\social_activity\model\db\SocialActivityPointRule;

class SocialActivityPointDetailService
{
    /**
     * 获取积分明细列表
     * @param $param
     * @return array
     */
    public function getPointDetailList($param)
    {
        $field = 's.id,s.uid,s.type,s.score,s.time, s.desc,s.score_type,s.order_id,s.order_type,s.house_type,
        s.village_id,u.nickname,u.phone';
        
        $where = [];
        $where[] = ['s.order_type', 'IN', ['social_activity_order','social_activity_comment']];
        
        // 姓名搜索
        if (!empty($param['name'])) {
            $where[] = ['u.nickname', 'like', '%' . $param['name'] . '%'];
        }
        
        // 手机号搜索
        if (!empty($param['phone'])) {
            $where[] = ['u.phone', 'like', '%' . $param['phone'] . '%'];
        }
        
        // 活动名称搜索
        if (!empty($param['activity_name'])) {
            $where[] = ['s.desc', 'like', '%' . $param['activity_name'] . '%'];
        }
        
        // 获得时间范围搜索
        if (!empty($param['start_time'])) {
            $where[] = ['s.time', '>=', strtotime($param['start_time'])];
        }
        if (!empty($param['end_time'])) {
            $where[] = ['s.time', '<=', strtotime($param['end_time'] . ' 23:59:59')];
        }
        
        $paginate = UserScoreList::alias('s')
            ->join('user u', 's.uid = u.uid')
            ->where($where)
             ->field($field)
            ->order('s.id DESC')
            ->paginate([
                'list_rows' => $param['pageSize'],
                'page' => $param['page'],
            ]);

        $list = $paginate ? $paginate->items() : [];
        
        foreach ($list as &$item) {
			switch ($item['order_type']) {
				case 'social_activity_order':
					$order = SocialActivityOrder::where('id', $item['order_id'])->find();
					$item['activity_name'] = $order['activity_name'];
					$item['session_name'] = $order['session_name'];
					$item['rule_name'] = SocialActivityPointRule::where('id', $order['rule_id'])->value('name');
					break;
				case 'social_activity_comment':
					$comment = SocialActivityComment::alias('c')
					->join('social_activity a', 'c.activity_id = a.id')
					->join('social_activity_session se', 'c.session_id = se.id')
					->field('a.name as activity_name,se.name as session_name,a.rule_id')
					->where('c.id', $item['order_id'])
					->find();
					$item['activity_name'] = $comment['activity_name'] ?? '';
					$item['session_name'] = $comment['session_name'] ?? '';
					$item['rule_name'] = SocialActivityPointRule::where('id', $comment['rule_id'])->value('name') ?? '';
					break;
			}
            $item['score_text'] = $item['type'] == 1 ? '+' . $item['score'] : '-' . $item['score'];
            $item['score_color'] = $item['type'] == 1 ? 'green' : 'red';
            $item['create_time'] = !empty($item['time']) ? date('Y-m-d H:i:s', $item['time']) : '';
			$item['village_name'] = '无小区';
			if($item['village_id']) {
				$item['village_name'] = HouseVillage::where('village_id', $item['village_id'])->value('village_name');
			}
        }

        return $paginate;
    }

    /**
     * 获取积分类型文本
     * @param $scoreType
     * @return string
     */
    public function getScoreTypeText($scoreType)
    {
        $typeMap = [
            1 => '绑定手机号',
            2 => '上传身份证实名认证',
            3 => '填写地址',
            20 => '小区加积分',
            21 => '小区减积分',
            30 => '签到增加积分',
            31 => '管理员后台操作',
            32 => '收银订单获得',
            33 => '购买万嘉汇卡赠送',
            34 => '购物消费获得',
        ];
        return $typeMap[$scoreType] ?? '其他';
    }

    /**
     * 获取用户积分列表（H5端）
     * @param $params
     * @return array
     */
    public function getUserPointList($params)
    {
        $uid = $params['uid'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];

        if (!$uid) {
            throw new \Exception('请先登录');
        }

        $field = 's.id,s.uid,s.type,s.score,s.time,s.desc,s.score_type,s.order_id,s.order_type,s.house_type,
        s.village_id,o.activity_id,o.session_id,a.name as activity_name,se.name as session_name';

        $where = [];
        $where[] = ['s.uid', '=', $uid];
        $where[] = ['s.order_type', 'IN', ['social_activity_order','social_activity_comment']];
        $paginate = UserScoreList::alias('s')
            ->join('social_activity_order o', 's.order_id = o.id')
            ->join('social_activity a', 'o.activity_id = a.id')
            ->join('social_activity_session se', 'o.session_id = se.id')
            ->where($where)
            ->field($field)
            ->order('s.id DESC')
            ->paginate([
                'list_rows' => $pageSize,
                'page' => $page,
            ]);

        $list = $paginate ? $paginate->items() : [];

        foreach ($list as &$item) {
            $item['type_text'] = $item['type'] == 1 ? '收入' : '支出';
            $item['score_color'] = $item['type'] == 1 ? 'green' : 'red';
            $item['score_text'] = $item['type'] == 1 ? '+' . $item['score'] : '-' . $item['score'];
            $item['time'] = !empty($item['time']) ? date('Y-m-d H:i:s', $item['time']) : '';
            $item['activity_name'] = $item['activity_name'] ?? '';
            $item['session_name'] = $item['session_name'] ?? '';
        }

        return $paginate;
    }
}
