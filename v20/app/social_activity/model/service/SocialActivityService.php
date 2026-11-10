<?php


namespace app\social_activity\model\service;

use app\community\model\db\HouseVenueActivity;
use app\community\model\db\HouseVillage;
use app\social_activity\model\db\SocialActivity;
use app\social_activity\model\db\SocialActivityAdmin;
use app\social_activity\model\db\SocialActivityAdver;
use app\social_activity\model\db\SocialActivityAdverCategory;
use app\social_activity\model\db\SocialActivityAuditRecord;
use app\social_activity\model\db\SocialActivityCategory;
use app\social_activity\model\db\SocialActivityComment;
use app\social_activity\model\db\SocialActivityOrder;
use app\social_activity\model\db\SocialActivityPointRule;
use app\social_activity\model\db\SocialActivitySession;
use app\social_activity\model\db\SocialActivitySessionTicket;
use app\social_activity\model\db\SocialActivityViews;
use app\survey\model\SurveyForm;
use app\survey\model\SurveySubmit;
use Carbon\Traits\Timestamp;
use think\facade\Db;

class SocialActivityService
{
    public function getList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $cat_id = $params['cat_id'];
        $name = $params['name'];
        $start_time = $params['start_time'];
        $end_time = $params['end_time'];
        $activity_start_time = $params['activity_start_time'];
        $activity_end_time = $params['activity_end_time'];
        $status = $params['status'];
        $activity_status = $params['activity_status'];
        $creator_uid = $params['creator_uid'];
        $uid = $params['uid'] ?? 0;

        $where = [];
        if ($cat_id) {
            $where[] = ['a.category_id', '=', $cat_id];
        }
        if ($name) {
            $where[] = ['a.name', 'like', "%{$name}%"];
        }
        if ($start_time) {
            $where[] = ['a.create_time', '>=', $start_time];
        }
        if ($end_time) {
            $where[] = ['a.create_time', '<=', $end_time . ' 23:59:59'];
        }
        if ($activity_start_time) {
            $where[] = ['a.start_time', '>=', $activity_start_time];
        }
        if ($activity_end_time) {
            $where[] = ['a.end_time', '<=', $activity_end_time . ' 23:59:59'];
        }
        if ($status > -1) {
            $where[] = ['a.status', '=', $status];
        }
        if ($activity_status) {
            $date = date('Y-m-d H:i:s');
            if ($activity_status == 1) { // 未开始
                $where[] = ['a.start_time', '>', $date];
                $where[] = ['a.status', '<>', SocialActivity::STATUS_30];
            } elseif ($activity_status == 2) { // 进行中
                $where[] = ['a.start_time', '<=', $date];
                $where[] = ['a.end_time', '>=', $date];
                $where[] = ['a.status', '<>', SocialActivity::STATUS_30];
            } elseif ($activity_status == 3) { // 已结束
	            $date = date('Y-m-d 23:59:59', strtotime('-1 day'));
                $where[] = ['a.end_time', '<', $date];
	            $where[] = ['a.status', '<>', SocialActivity::STATUS_30];
                $where[] = ['a.is_audit', '=', SocialActivity::STATUS_10];
            } elseif ($activity_status == 4) { // 已取消
                $where[] = ['a.status', '=', SocialActivity::STATUS_30];
            }
        }
        if ($creator_uid) {
            $where[] = ['u.id', '=', $creator_uid];
        }
        $field = 'a.*,c.name as category_name,u.realname as creator';
        $list = (new SocialActivity())->getList($field, $where, $page, $pageSize, $uid);
        $activity_ids = array_column($list['data'], 'id');
        $signup_num = [];
        if ($activity_ids) {
            $signup_list = (new SocialActivityOrder())
                ->field('activity_id,SUM(num) as total')
                ->where([
                    ['activity_id', 'in', $activity_ids],
                    ['status', 'in', [SocialActivityOrder::STATUS_0, SocialActivityOrder::STATUS_10]],
                ])
                ->group('activity_id')
                ->select()
                ->toArray();
            $signup_num = array_column($signup_list, 'total', 'activity_id');
        }
        foreach ($list['data'] as &$activity_item) {
            $activity_item['sign_up_num'] = (int)($signup_num[$activity_item['id']] ?? 0);
        }
        unset($activity_item);
        //活动分类
        $catList = (new SocialActivityCategory())->field('id,name')->where(['status' => 1])->select()->toArray();
        $list['cat_list'] = $catList ?: [];
        //活动状态
        $list['activity_status'] = [
            [
                'id' => 1,
                'name' => '待开始',
            ],
            [
                'id' => 2,
                'name' => '进行中',
            ],
            [
                'id' => 3,
                'name' => '已结束',
            ],
            [
                'id' => 4,
                'name' => '已取消',
            ],
        ];
        //审核状态
        $list['status'] = [
            [
                'id' => SocialActivity::STATUS_0,
                'name' => SocialActivity::STATUS_MAP[SocialActivity::STATUS_0],
            ],
            [
                'id' => SocialActivity::STATUS_10,
                'name' => SocialActivity::STATUS_MAP[SocialActivity::STATUS_10],
            ],
            [
                'id' => SocialActivity::STATUS_20,
                'name' => SocialActivity::STATUS_MAP[SocialActivity::STATUS_20],
            ],
        ];
        //创建人
        $creatorList = (new SocialActivityAdmin())->field('id,realname as name')->select()->toArray();
        $list['creator_list'] = $creatorList ?: [];
        return $list;
    }

    public function getEditInfo($params)
    {
        $uid = $params['uid'];
        $village_where = [['status', '=', 1]];
	    $role = SocialActivityAdmin::where('id', $uid)->value('role_id');
        if ($role != 1) {
            $village_ids = (new SocialActivityAdmin())->where(['id' => $uid])->value('village_id');
            $village_ids = $village_ids ? explode(',', $village_ids) : [];
            $village_where[] = ['village_id', 'in', $village_ids];
        }
        //获取小区列表
        $communityList = (new HouseVillage())->field('village_id as id,village_name as name')->where($village_where)->select()->toArray();
        //获取场馆列表
        $venueList = (new HouseVenueActivity())->field('id,title as name')->where(['status' => 1, 'is_del' => 0])->select()->toArray();
        //报名表单
        $formList = (new SurveyForm())->field('id,title as name')->where(['type' => 'signup', 'status' => 1, 'is_del' => 0])->select()->toArray();
        //积分规则
        $pointList = (new SocialActivityPointRule())->field('id,name')->where(['status' => 1])->select()->toArray();
        return [
            'communityList' => $communityList,
            'venueList' => $venueList,
            'formList' => $formList,
            'pointList' => $pointList,
        ];
    }

    /**
     * 保存活动
     * @param $data
     * @param $id
     * @return int
     */
    public function saveActivity($data, $id = 0)
    {
        $session_list = $data['sessions'] ?? [];
        $hostVillageId = intval($data['host_village_id'] ?? 0);
        $hostVillage = (new HouseVillage())->where([
            'village_id' => $hostVillageId,
            'status' => 1,
        ])->find();
        if (!$hostVillage) {
            throw new \Exception('活动主办社区不存在或未启用');
        }
        $creator = $data['creator_user'] ?? [];
        if (intval($creator['role_id'] ?? 0) !== 1) {
            $allowedVillageIds = array_filter(array_map('intval', explode(',', (string)($creator['village_id'] ?? ''))));
            if (!in_array($hostVillageId, $allowedVillageIds, true)) {
                throw new \Exception('无权选择当前活动主办社区');
            }
        }
        (new SocialActivityPayConfigService())->getEnabledConfig($hostVillageId);
        if (empty($data['position_poster'])) {
            throw new \Exception('社群活动位海报不能为空');
        }
        // 验证时间逻辑
        if (strtotime($data['start_time']) >= strtotime($data['end_time'].' 23:59:59')) {
            throw new \Exception('活动开始时间必须早于结束时间');
        }

        //查询分类是否存在
        $cat = (new SocialActivityCategory())->where(['id' => $data['category_id'], 'status' => 1])->find();
        if (!$cat) {
            throw new \Exception('活动分类不存在/状态未开启');
        }

        //查询报名表单是否存在
        $form = (new SurveyForm())->where(['id' => $data['form_id'], 'type' => 'signup', 'status' => 1, 'is_del' => 0])->find();
        if (!$form) {
            throw new \Exception('报名表单不存在/状态未开启');
        }
        //查询积分规则是否存在
        $point = (new SocialActivityPointRule())->where(['id' => $data['rule_id'], 'status' => 1])->find();
        if (!$point) {
            throw new \Exception('积分规则不存在/状态未开启');
        }
        //查询视频是否存在
        $video = $data['video'] ?? [];
        if ($video) {
            $video = $video[0];
        }

        $date = date('Y-m-d H:i:s');
        $save_session = []; //场次信息
        $edit_session_ids = []; //要编辑的场次ID
        foreach ($session_list as $key => $session) {
            if (!$session['name']) {
                throw new \Exception('场次' . ($key + 1) . '名称不能为空');
            }
            if (!in_array($session['identity'], [1, 2, 3])) {
                throw new \Exception('场次' . ($key + 1) . '参与身份不正确');
            }
            if (!in_array($session['gender'], [1, 2, 3])) {
                throw new \Exception('场次' . ($key + 1) . '性别不正确');
            }
            if ($session['start_time'] >= $session['end_time']) {
                throw new \Exception('场次' . ($key + 1) . '开始时间必须早于结束时间');
            }
            if(strtotime($data['start_time']) > strtotime($session['start_time'])){
                throw new \Exception('场次' . ($key + 1) . '开始时间必须晚于活动开始时间');
            }
            if(strtotime($data['end_time'].' 23:59:59') < strtotime($session['end_time'])){
                throw new \Exception('场次' . ($key + 1) . '结束时间必须晚于活动结束时间');
            }
            if ($session['age_range'] == 1) {
                if (!$session['age_max']) {
                    throw new \Exception('场次' . ($key + 1) . '最大年龄不能为空');
                }
                if ($session['age_min'] >= $session['age_max']) {
                    throw new \Exception('场次' . ($key + 1) . '最小年龄必须小于最大年龄');
                }
            } else {
                $session['age_min'] = 0;
                $session['age_max'] = 0;
            }
            if ($session['location_type'] == 1) {
                if (!$session['location_id']) {
                    throw new \Exception('场次' . ($key + 1) . '请选择场馆');
                }
                $session['location'] = '';
                //查询场馆是否存在
                $venue = (new HouseVenueActivity())->where(['id' => $session['location_id'], 'status' => 1, 'is_del' => 0])->find();
                if (!$venue) {
                    throw new \Exception('场次' . ($key + 1) . '选择场馆不存在/已被删除');
                }
            } else {
                if (!$session['location']) {
                    throw new \Exception('场次' . ($key + 1) . '自定义地点不能为空');
                }
                $session['location_id'] = '';
            }
            if (!$session['tickets']) {
                throw new \Exception('场次' . ($key + 1) . '门票种类不能为空');
            }
            foreach ($session['tickets'] as $ticket_k => $ticket) {
                if (!$ticket['name']) {
                    throw new \Exception('场次' . ($key + 1) . '门票名称不能为空');
                }
                if ($ticket['price'] < 0) {
                    throw new \Exception('场次' . ($key + 1) . '门票价格不能小于0');
                }
                if (!$ticket['num'] || $ticket['num'] <= 0) {
                    throw new \Exception('场次' . ($key + 1) . '门票数量必须大于0');
                }
                $session['tickets'][$ticket_k]['point'] = $ticket['point'] ? 1 : 0;
            }
            if (empty($session['ticket_start_time']) && empty($session['ticket_end_time'])) {
                throw new \Exception('场次' . ($key + 1) . '请选择购票时间');
            }
            if (strtotime($session['ticket_start_time']) >= strtotime($session['ticket_end_time'])) {
                throw new \Exception('场次' . ($key + 1) . '购票开始时间必须早于结束时间');
            }
            if (strtotime($session['ticket_end_time']) > strtotime($session['end_time'])) {
                throw new \Exception('场次' . ($key + 1) . '购票结束时间不能晚于场次结束时间');
            }
            $save_session[] = [
                'id' => $session['id'] ?? 0,
                'name' => $session['name'],
                'identity' => $session['identity'],
                'gender' => $session['gender'],
                'start_time' => $session['start_time'],
                'end_time' => $session['end_time'],
                'age_min' => $session['age_min'],
                'age_max' => $session['age_max'],
                'location_id' => $session['location_id'],
                'location' => $session['location'],
                'tickets' => $session['tickets'],
                'ticket_start_time' => $session['ticket_start_time'],
                'ticket_end_time' => $session['ticket_end_time'],
                'village_id' => $session['village_id'] ? implode(',', $session['village_id']) : '',
                'update_time' => $date,
                'video' => $video,
            ];
            if (!empty($session['id']) && $session['id'] > 0) {
                $edit_session_ids[] = $session['id'];
            }
        }
        $saveData = [
            'name' => $data['name'],
            'host_village_id' => $hostVillageId,
            'category_id' => $data['category_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'form_id' => $data['form_id'] ?? 0,
            'rule_id' => $data['rule_id'] ?? 0,
            'content' => $data['content'] ?? '',
            'notice' => $data['notice'] ?? '',
            'poster' => $data['poster'] ?? '',
            'position_poster' => $data['position_poster'] ?? '',
            'status' => SocialActivity::STATUS_0,
            'video' => $video,
            'is_audit' => SocialActivity::STATUS_0,
        ];
        Db::startTrans();
        try {
            if ($id > 0) {
                $info = (new SocialActivity())->where(['id' => $id])->lock(true)->findOrEmpty()->toArray();
                if (!$info) {
                    throw new \Exception('活动不存在/已被删除');
                }
                if ($info['status'] != SocialActivity::STATUS_0) {
                    throw new \Exception('审核后的活动不允许编辑，请先消审');
                }
                //查询活动是否已有人报名
                $order_info = (new SocialActivityOrder())->where(['activity_id' => $id])->findOrEmpty()->toArray();
                if ($order_info) {
                    throw new \Exception('活动已有人报名，无法修改');
                }
                $check_name = (new SocialActivity())->where([['name', '=', $data['name']], ['id', '<>', $id]])->findOrEmpty()->toArray();
                if ($check_name) {
                    throw new \Exception('活动名称已存在');
                }
                //去除需要编辑之外的场次
                if ($edit_session_ids) {
                    (new SocialActivitySession())->where([['id', 'not in', $edit_session_ids], ['activity_id', '=', $id]])->delete();
                }
                $saveData['modifier_uid'] = $data['creator_user']['id'];
                $saveData['modifier'] = $data['creator_user']['realname'];
                $saveData['update_time'] = $date;
                // 编辑
                (new SocialActivity())->where(['id' => $id])->update($saveData);
            } else {
                $check_name = (new SocialActivity())->where([['name', '=', $data['name']]])->findOrEmpty()->toArray();
                if ($check_name) {
                    throw new \Exception('活动名称已存在');
                }
                // 新增
                $saveData['status'] = SocialActivity::STATUS_0;
                $saveData['create_time'] = $date;
                $saveData['creator_uid'] = $data['creator_user']['id'];
                $saveData['creator'] = $data['creator_user']['realname'];
                $id = (new SocialActivity())->insertGetId($saveData);
            }
            $session_days = strtotime($saveData['end_time']) - strtotime($saveData['start_time']);
            $session_days = ceil($session_days / (24 * 60 * 60));
            //添加场次
            (new SocialActivitySessionTicket())->where(['activity_id' => $id])->delete();
            foreach ($save_session as $session_item) {
                $tickets = $session_item['tickets'] ?? [];
                $session_id = 0;
                if ($session_item['id'] > 0) {
                    $session_info = (new SocialActivitySession())->where(['id' => $session_item['id'], 'activity_id' => $id])->findOrEmpty()->toArray();
                    if ($session_info) {
                        $session_id = $session_info['id'];
                        $session_item['update_time'] = $date;
                    }
                } else {
                    $session_item['create_time'] = $date;
                }
                unset($session_item['id']);
                unset($session_item['tickets']);
                unset($session_item['video']);
                $session_item['activity_id'] = $id;
                if ($session_id) {
                    (new SocialActivitySession())->where(['id' => $session_id])->save($session_item);
                } else {
                    $session_id = (new SocialActivitySession())->insertGetId($session_item);
                }
                //添加场次门票
                if ($tickets) {
                    foreach ($tickets as &$ticket) {
                        if (isset($ticket['id'])) {
                            unset($ticket['id']);
                        }
                        $ticket['session_id'] = $session_id;
                        $ticket['activity_id'] = $id;
                    }
                    (new SocialActivitySessionTicket())->saveAll($tickets);
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 获取活动详情
     */
    public function getDetail($params)
    {
        $id = $params['id'] ?? 0;
        $is_view = $params['is_view'] ?? 0;
        if ($id <= 0) {
            throw new \Exception('活动ID必须大于0');
        }
        $res = (new SocialActivity())->where(['id' => $id])->append(['status_text'])->findOrEmpty()->toArray();
        if (!$res) {
            throw new \Exception('活动不存在');
        }
        $res['poster'] = $res['poster'] ? replace_file_domain($res['poster']) : '';
        $res['position_poster'] = $res['position_poster'] ? replace_file_domain($res['position_poster']) : '';
        $sessions = (new SocialActivitySession())->where(['activity_id' => $id])->select()->toArray();
        $sessions_ids = array_column($sessions, 'id');
        $tickets = (new SocialActivitySessionTicket())->where([['session_id', 'in', $sessions_ids]])->select()->toArray();
        $session_tickets = [];
        foreach ($tickets as $ticket) {
            $ticket['point'] = $ticket['point'] ? true : false;
            $session_tickets[$ticket['session_id']] = $session_tickets[$ticket['session_id']] ?? [];
            $session_tickets[$ticket['session_id']][] = $ticket;
        }
        foreach ($sessions as &$session) {
            $session['age_range'] = 0;
            if ($session['age_min'] > 0 && $session['age_max'] > 0) {
                $session['age_range'] = 1;
            }
            $session['location_type'] = 2;
            $session['location_name'] = $session['location'] ?? '';
            if ($session['location_id']) {
                $session['location_type'] = 1;
                $session['location_name'] = (new HouseVenueActivity())->where(['status' => 1, 'is_del' => 0, 'id' => $session['location_id']])->value('title');
            }
            $session['ticket_time_range'] = [$session['ticket_start_time'], $session['ticket_end_time']];
            $session['start_time'] = date('Y-m-d H:i', strtotime($session['start_time']));
            $session['end_time'] = date('Y-m-d H:i', strtotime($session['end_time']));
            $session['time_range'] = [$session['start_time'], $session['end_time']];
            $session['location_id'] = (int)$session['location_id'];
            $session['village_id'] = $session['village_id'] ? array_map('intval', array_filter(explode(',', $session['village_id']), 'is_numeric')) : [];
            $session['tickets'] = $session_tickets[$session['id']] ?? [];
            $session['village_name'] = '';
            if ($is_view) {
                $village_name = (new HouseVillage())->where([['village_id', 'in', $session['village_id']], ['status', '=', 1]])->column('village_name');
                $session['village_name'] = $village_name;
            }
        }
        $res['sessions'] = $sessions;
        $res['auditRecords'] = (new SocialActivityAuditRecord())->field('id,creator,create_time,status,note')
            ->where(['activity_id' => $id])
            ->order('id DESC')
            ->select()->each(function (&$item) use ($res) {
                $status_text = [
                    SocialActivity::STATUS_0 => '已消审',
                    SocialActivity::STATUS_10 => '通过',
                    SocialActivity::STATUS_20 => '驳回',
                ];
                $item['status_text'] = $status_text[$item['status']] ?? '';
                $item['activity_creator'] = $res['creator'];
                $item['activity_create_time'] = $res['create_time'];
            })
            ->toArray();
        //表单
        $res['form_name'] = (new SurveyForm())->where(['id' => $res['form_id'], 'status' => 1, 'is_del' => 0])->value('title');
        //规则
        $res['rule_name'] = (new SocialActivityPointRule())->where(['id' => $res['rule_id'], 'status' => 1])->value('name');
        $res['video'] = $res['video'] ? [replace_file_domain($res['video'])] : [];
        $res['content'] = $res['content'] ? replace_file_domain_content($res['content']) : '';
        return $res;
    }

    /**
     * 审核活动
     * @param array $params
     * @return void
     * @throws \Exception
     */
    public function auditActivity($params)
    {
        $id = $params['id'];
        $status = $params['status'];
        $remark = $params['remark'] ?? '';
        $editUser = $params['editUser'];

        // 获取活动信息
        $activity = (new SocialActivity())->where(['id' => $id])->findOrEmpty()->toArray();
        if (!$activity) {
            throw new \Exception('活动不存在');
        }

        // 检查活动状态（只有待审核状态才能审核）
        if ($activity['status'] != SocialActivity::STATUS_0) {
            throw new \Exception('该活动状态不允许审核');
        }

        if (!in_array($status, [SocialActivity::STATUS_10, SocialActivity::STATUS_20])) {
            throw new \Exception('审核状态值不正确');
        }

        if ($status == SocialActivity::STATUS_20 && !$remark) {
            throw new \Exception('请输入拒绝原因');
        }

        $date = date('Y-m-d H:i:s');
        // 更新活动状态
        $updateData = [
            'status' => $status, // 1=通过(10), 2=拒绝(20)
            'check_time' => $date,
            'remark' => $remark,
            'update_time' => $date,
            'modifier_uid' => $editUser['id'],
            'modifier' => $editUser['realname'],
            'is_audit' => $status,
        ];

        Db::startTrans();
        try {
            $result = (new SocialActivity())->where('id', $id)->update($updateData);
            (new SocialActivityAuditRecord())->save([
                'activity_id' => $id,
                'status' => $status,
                'note' => $remark,
                'create_time' => $date,
                'create_uid' => $editUser['id'],
                'creator' => $editUser['realname'],
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 消审活动
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    public function cancelAuditActivity($params)
    {
        $id = $params['id'];
        $editUser = $params['editUser'];
        $date = date('Y-m-d H:i:s');

        Db::startTrans();
        try {
            $activity = (new SocialActivity())
                ->where(['id' => $id])
                ->lock(true)
                ->findOrEmpty()
                ->toArray();
            if (!$activity) {
                throw new \Exception('活动不存在');
            }
            if (!in_array($activity['status'], [SocialActivity::STATUS_10, SocialActivity::STATUS_20])) {
                throw new \Exception('当前活动状态不允许消审');
            }

            // 未支付订单也视为已经报名，避免消审后订单继续完成支付。
            $signup_orders = (new SocialActivityOrder())
                ->field('id')
                ->where([
                    ['activity_id', '=', $id],
                    ['status', 'in', [SocialActivityOrder::STATUS_0, SocialActivityOrder::STATUS_10]],
                ])
                ->lock(true)
                ->select()
                ->toArray();
            if ($signup_orders) {
                throw new \Exception('活动已有报名，不允许消审');
            }

            (new SocialActivity())->where(['id' => $id])->update([
                'status' => SocialActivity::STATUS_0,
                'is_audit' => SocialActivity::STATUS_0,
                'remark' => '',
                'recommend' => 0,
                'update_time' => $date,
                'modifier_uid' => $editUser['id'],
                'modifier' => $editUser['realname'],
            ]);
            (new SocialActivityAuditRecord())->save([
                'activity_id' => $id,
                'status' => SocialActivity::STATUS_0,
                'note' => '消审',
                'create_time' => $date,
                'create_uid' => $editUser['id'],
                'creator' => $editUser['realname'],
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return true;
    }

    /**
     * 推荐/撤回活动
     * @param array $params
     * @return void
     * @throws \Exception
     */
    public function recommendActivity($params)
    {
        $id = $params['id'];
        $recommend = $params['recommend'];
        $editUser = $params['editUser'];

        // 获取活动信息
        $activity = (new SocialActivity())->where(['id' => $id])->findOrEmpty()->toArray();
        if (!$activity) {
            throw new \Exception('活动不存在');
        }

        // 检查活动状态（只有已审核状态才能推荐）
        if ($activity['status'] != SocialActivity::STATUS_10) {
            throw new \Exception('该活动状态不允许推荐/撤回');
        }

        // 检查推荐状态是否合法
        if (!in_array($recommend, [0, 1])) {
            throw new \Exception('推荐状态值不正确');
        }

        $date = date('Y-m-d H:i:s');
        // 更新活动推荐状态
        $updateData = [
            'recommend' => $recommend,
            'update_time' => $date,
            'modifier_uid' => $editUser['id'],
            'modifier' => $editUser['realname'],
        ];

        try {
            $result = (new SocialActivity())->where('id', $id)->update($updateData);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    /**
     * 取消活动
     * @param array $params
     * @return void
     * @throws \Exception
     */
    public function cancelActivity($params)
    {
        if (empty($params['reason'])) { throw new \RuntimeException('取消原因不能为空'); }
        return Db::transaction(function () use ($params) {
            $activity = (new SocialActivity())->where('id', $params['id'])->lock(true)->findOrEmpty();
            if ($activity->isEmpty()) { throw new \RuntimeException('活动不存在'); }
            if ((int)$activity['status'] === SocialActivity::STATUS_30) { throw new \RuntimeException('该活动已取消'); }
            if (strtotime($activity['start_time']) <= time()) { throw new \RuntimeException('该活动已开始，无法取消'); }
            // 与报名下单使用同一活动行锁；有报名、待支付或退款中订单时均不取消。
            $orders = (new SocialActivityOrder())->where('activity_id', $params['id'])->whereIn('status', [0, 10])->count();
            if ($orders > 0) { throw new \RuntimeException('该活动已有报名订单，不能取消（含待支付及退款中的订单）'); }
            $activity->save(['status' => SocialActivity::STATUS_30, 'cancel_reason' => $params['reason'],
                'modifier_uid' => $params['editUser']['id'], 'modifier' => $params['editUser']['realname'], 'update_time' => date('Y-m-d H:i:s')]);
            return true;
        });
    }

    /**
     * 获取场次列表
     * @param array $params
     * @return array
     */
    public function getSessionList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $name = $params['name'];
        $activity_id = $params['activity_id'];
        if (!$activity_id) {
            throw new \Exception('活动ID不能为空');
        }

        $where = [
            ['s.activity_id', '=', $activity_id],
        ];
        if ($name) {
            $where[] = ['s.name', 'like', "%{$name}%"];
        }

        $field = 's.*';

        $list = (new socialActivitySession())->alias('s')
            ->field($field)
            ->where($where)
            ->order('s.id asc')
            ->paginate($pageSize)->toArray();

        $session_ids = array_column($list['data'], 'id');

        //统计总数
        $ticket_num = [];
        if ($session_ids) {
            $tickets = (new SocialActivitySessionTicket())->field('session_id, SUM(num) as total')->where([['session_id', 'in', $session_ids]])->group('session_id')->select()->toArray();
            $ticket_num = array_column($tickets, 'total', 'session_id');
        }

        $order_status = [
            ['session_id', 'in', $session_ids],
            ['status', '=', SocialActivityOrder::STATUS_10],
            ['paid', '=', 1],
        ];
        //统计报名人数
        $order_num = [];
        if ($session_ids) {
            $orders = (new SocialActivityOrder())->field('SUM(num) as total, session_id')->where($order_status)->group('session_id')->select()->toArray();
            $order_num = array_column($orders, 'total', 'session_id');
        }

        foreach ($list['data'] as &$item) {
            $item['total_num'] = $ticket_num[$item['id']] ?? 0;
            $item['sign_up_num'] = $order_num[$item['id']] ?? 0;
            $item['check_in_num'] = (new SocialActivityParticipantService())->signedCount((new SocialActivityOrder())->where(['session_id' => $item['id'], 'status' => 10, 'paid' => 1]));
            $item['time_range'] = date('Y-m-d H:i', strtotime($item['start_time'])) . ' ~ ' . date('Y-m-d H:i', strtotime($item['end_time']));

            $now = date('Y-m-d H:i:s');
            if ($item['start_time'] > $now) {
                $item['status'] = 0;
                $item['status_text'] = '待开始';
            } elseif ($item['end_time'] < $now) {
                $item['status'] = 2;
                $item['status_text'] = '已结束';
            } else {
                $item['status'] = 1;
                $item['status_text'] = '进行中';
            }
        }

        return $list;
    }

    /**
     * 获取报名列表
     * @param array $params
     * @return array
     */
    public function getSignUpList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $session_id = $params['session_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $check_in_status = $params['check_in_status'];
        $is_export = $params['is_export'] ?? 0;
        if (!$session_id) {
            throw new \Exception('场次ID不能为空');
        }
        $session_info = (new SocialActivitySession())->where(['id' => $session_id])->find();
        if (!$session_info) {
            throw new \Exception('场次不存在');
        }
        $where = [
            ['o.session_id', '=', $session_id],
            ['o.status', '<>', SocialActivityOrder::STATUS_20],
            ['o.paid', '=', 1],
        ];

        if ($start_date) {
            $where[] = ['o.create_time', '>=', $start_date . ' 00:00:00'];
        }
        if ($end_date) {
            $where[] = ['o.create_time', '<=', $end_date . ' 23:59:59'];
        }
        if ($check_in_status >= 0) {
            $matchingOrders = \think\facade\Db::name('social_activity_participant')->where('session_id', $session_id)->where('is_sign', $check_in_status)->column('order_id');
            $modernOrders = \think\facade\Db::name('social_activity_participant')->where('session_id', $session_id)->column('order_id');
            $legacyOrders = (new SocialActivityOrder())->where('session_id', $session_id)->whereNotIn('id', $modernOrders ?: [-1])->where('is_sign', $check_in_status)->column('id');
            $where[] = ['o.id', 'in', array_merge($matchingOrders, $legacyOrders) ?: [-1]];
        }

        $query = (new SocialActivityOrder())->alias('o')
            ->field('o.*, u.nickname, u.avatar, u.phone, u.phone as signup_phone')
            ->join('user u', 'o.uid = u.uid')
            ->where($where)
            ->append(['source_text'])
            ->order('o.create_time desc')
            ->group('o.id,o.buy_village_id');
        //统计
        $signUpNum = 0;
        $checkInNum = 0;
        if ($is_export != 1) {
            $list = $query->paginate($pageSize)->toArray();
            $signUpNum = (new SocialActivityOrder())->alias('o')->where($where)->sum('num');
            $checkInNum = (new SocialActivityParticipantService())->signedCount((new SocialActivityOrder())->alias('o')->where($where));
            $list['signUpNum'] = $signUpNum;
            $list['checkInNum'] = $checkInNum;
            $data = $list['data'];
        } else {
            $data = $query->select()->toArray();
        }
        $surveyFormMap = [];
        if ($is_export == 1) {
            $submitIds = array_values(array_unique(array_filter(array_column($data, 'survey_submit_id'))));
            if ($submitIds) {
                $surveyAnswers = (new SurveySubmit())->alias('s')
                    ->field('s.id as submit_id,sq.type,sq.title,ssa.answer')
                    ->join('survey_submit_answer ssa', 's.id = ssa.submit_id')
                    ->join('survey_question sq', 'ssa.question_id = sq.id')
                    ->whereIn('s.id', $submitIds)
                    ->order('sq.sort asc,sq.id asc')
                    ->select()
                    ->toArray();
                foreach ($surveyAnswers as $surveyAnswer) {
                    $answer = $surveyAnswer['answer'] ?? '';
                    if ($answer && in_array($surveyAnswer['type'], ['image', 'file'])) {
                        $answer = implode('、', array_map(function ($url) {
                            return replace_file_domain($url);
                        }, array_filter(explode(',', $answer))));
                    }
                    $submitId = $surveyAnswer['submit_id'];
                    $surveyFormMap[$submitId][] = $surveyAnswer['title'] . '：' . ($answer !== '' ? $answer : '未填写');
                }
                foreach ($surveyFormMap as &$surveyForm) {
                    $surveyForm = implode('；', $surveyForm);
                }
                unset($surveyForm);
            }
        }
        $sourceMap = [
            'androidapp' => '安卓APP',
            'iosapp' => '苹果APP',
            'wechat_mini' => '微信小程序',
            'wechat_h5' => '微信公众号',
            'h5' => '移动网页',
        ];
        foreach ($data as &$item) {
            $participants = (new SocialActivityParticipantService())->display($item['id'], 0, true);
            $item['participants'] = $participants;
            $item['sign_text'] = $item['is_sign'] == 1 ? '已签到' : '未签到';
            if ($participants) {
                $signedCount = count(array_filter($participants, function ($person) { return $person['is_sign']; }));
                $item['is_sign'] = (int)($signedCount === count($participants));
                $item['sign_text'] = $signedCount . '/' . count($participants) . '人已签到';
                $item['user_name'] = implode('、', array_map(function ($person) { return $person['user_name'] . '（' . $person['role'] . '）'; }, $participants));
            }
			$item['avatar'] = $item['avatar'] ? replace_file_domain($item['avatar']) : '';
            $item['export_user_name'] = $item['user_name'] ?? '';
            $item['export_phone'] = $item['signup_phone'] ?? '';
            $item['export_source'] = !empty($item['user_source']) ? $item['user_source'] : ($sourceMap[$item['source'] ?? ''] ?? '');
            $item['export_amount'] = number_format((float)($item['amount'] ?? 0), 2, '.', '');
            $item['export_survey_form'] = $surveyFormMap[$item['survey_submit_id'] ?? 0] ?? '';
        }
        unset($item);

        if ($is_export == 1) {
            $title = '报名情况导出列表';
            $xlsCell = [
                ['nickname', '用户昵称'],
                ['export_user_name', '姓名'],
                ['export_phone', '手机号'],
                ['export_survey_form', '报名表单'],
                ['ticket_name', '票种'],
                ['export_amount', '金额'],
                ['create_time', '报名时间'],
                ['export_source', '来源'],
                ['sign_text', '签到状态'],
            ];
            $fileName = '报名列表_' . date('YmdHis');
            $exportResult = export_excel($title, $xlsCell, $data, $fileName);
            return ['file_url' => $exportResult['url']];
        } else {
            $list['data'] = $data;
            $list['qrcode_url'] = get_base_url('pages/socialActivity/detail?is_sign=1&id=' . $session_info['activity_id'] . '&sign_session_id=' . $session_id);
        }
        return $list;
    }

    /**
     * 判断场次是否允许签到
     * @param mixed $session
     * @return array
     */
    private function getSessionCheckInStatus($session)
    {
        if (empty($session) || (is_object($session) && method_exists($session, 'isEmpty') && $session->isEmpty())) {
            return [0, '场次不存在'];
        }

        $startTime = $session['start_time'] ?? '';
        if ($startTime && strtotime($startTime) > time()) {
            return [0, '活动未开始，暂不允许签到'];
        }

        return [1, ''];
    }
    /**
     * 获取订单报名表单
     * @param array $params
     * @return array
     */
    public function getOrderSurveyForm($params)
    {
        $id = $params['id'];
        if (!$id) {
            throw new \Exception('订单ID不能为空');
        }
        $order = (new SocialActivityOrder())->where(['id' => $id])->findOrEmpty()->toArray();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        $form = (new SurveySubmit())->alias('s')
            ->field('sq.type,sq.title,ssa.answer')
            ->join('survey_submit_answer ssa', 's.id = ssa.submit_id')
            ->join('survey_question sq', 'ssa.question_id = sq.id')
            ->where(['s.id' => $order['survey_submit_id']])
            ->select()->each(function (&$item) {
                if (in_array($item['type'], ['image', 'file'])) {
                    $item['answer'] = $item['answer'] ? explode(',', $item['answer']) : [];
                }
            })
            ->toArray();
        return ['list' => $form];
    }

    /**
     * 签到接口
     * @param $params
     * @return array
     */
    public function signOrder($params)
    {
        $orderId = $params['order_id'] ?? 0;
        if (!$orderId) {
            throw new \Exception('订单ID不能为空');
        }
        return \think\facade\Db::transaction(function () use ($orderId) {
            $order = (new SocialActivityOrder())->where(['id' => $orderId])->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \Exception('订单不存在');
            }
            if (!empty($order['refund_status'])) { throw new \RuntimeException('订单退款中或已退款，不能核销'); }
            if ($order['status'] != SocialActivityOrder::STATUS_10) {
                throw new \Exception('订单未支付，无法签到');
            }
            if( time() > strtotime($order->ticket_end_time) ){
                throw new \Exception('该场次活动已经结束, 无法签到');
            }
            $remaining = \think\facade\Db::name('social_activity_participant')->where(['order_id' => $orderId, 'is_sign' => 0])->count();
            if ($order['is_sign'] == 1 && !$remaining) {
                throw new \Exception('订单已签到，请勿重复签到');
            }

            $session = (new SocialActivitySession())->where(['id' => $order['session_id']])->findOrEmpty();

            [$canCheckIn, $checkInMsg] = $this->getSessionCheckInStatus($session);

            if (!$canCheckIn) {

                throw new \Exception($checkInMsg);

            }
            $result = (new SocialActivityOrder())->where(['id' => $orderId, 'status' => 10, 'refund_status' => 0])->save([
                'is_sign' => 1,
                'sign_time' => date('Y-m-d H:i:s'),
            ]);
            if (!$result && !$remaining) {
                throw new \Exception('订单状态已改变，签到失败');
            }
            \think\facade\Db::name('social_activity_participant')->where(['order_id' => $orderId, 'is_sign' => 0])->update(['is_sign' => 1, 'sign_time' => date('Y-m-d H:i:s')]);
            return ['msg' => '签到成功'];
        });

    }

    /**
     * 获取活动列表
     * @param $params
     * @return array
     */
    public function getActivityList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $category_id = $params['category_id'];
        $name = $params['name'];
        $activity_status = $params['activity_status'];
        $recommend = $params['recommend'];
        $date = $params['date'];
        $start_date = $params['start_date'] ?? '';
        $end_date = $params['end_date'] ?? '';

        $where = [];
        $where[] = ['a.status', '=', SocialActivity::STATUS_10];

        if ($category_id) {
            $where[] = ['a.category_id', '=', $category_id];
        }
        if ($name) {
            $where[] = ['a.name', 'like', "%{$name}%"];
        }
        if ($recommend !== '') {
            $where[] = ['a.recommend', '=', $recommend];
        }
        if ($start_date && $end_date) {
            $where[] = ['a.start_time', '<=', $end_date . ' 23:59:59'];
            $where[] = ['a.end_time', '>=', $start_date];
        } elseif ($date) {
            $where[] = ['a.start_time', '<=', $date];
            $where[] = ['a.end_time', '>=', $date];
        }

        if ($activity_status) {
            $currentDate = date('Y-m-d');
            if ($activity_status == 1) {
                $where[] = ['a.start_time', '>', $currentDate];
            } elseif ($activity_status == 2) {
                $where[] = ['a.start_time', '<=', $currentDate];
                $where[] = ['a.end_time', '>=', $currentDate];
            } elseif ($activity_status == 3) {
                $where[] = ['a.end_time', '<', $currentDate];
            }
        }
        $field = 'a.id,a.category_id,a.name,a.poster,a.position_poster,a.start_time,a.end_time,a.content,a.recommend,
        a.create_time,c.name as category_name, a.status';
        $list = (new SocialActivity())
            ->alias('a')
            ->join('social_activity_category c', 'a.category_id = c.id')
            ->where($where)
            ->field($field)
            ->order('a.recommend DESC, a.update_time DESC, a.id DESC')
            ->paginate([
                'list_rows' => $pageSize,
                'page' => $page,
            ]);
        $data = $list ? $list->items() : [];

        foreach ($data as &$item) {
            $item['poster'] = $item['poster'] ? replace_file_domain($item['poster']) : '';
            $item['position_poster'] = $item['position_poster'] ? replace_file_domain($item['position_poster']) : '';
            if (!$item['poster'] && $item['position_poster']) {
                $item['poster'] = $item['position_poster'];
            }
            $item['activity_status'] = $this->getActivityStatus($item['start_time'], $item['end_time']);
            $item['activity_status_text'] = $this->getActivityStatusText($item['activity_status']);
            $ticketPrices = (new SocialActivitySessionTicket())
                ->where(['activity_id' => $item['id']])
                ->column('price');
            $item['min_ticket_price'] = $ticketPrices ? min($ticketPrices) : 0;
            $item['signup_count'] = $this->activitySignupCount($item['id']);
            $this->setActivityDisplayTime($item);
        }

        return $list;
    }

    /**
     * 获取活动详情
     * @param $params
     * @return array
     */
    public function getActivityDetail($params)
    {
        $id = $params['id'];
        $uid = $params['uid'] ?? 0;
        $is_sign = $params['is_sign'] ?? 0;
        $sign_session_id = $params['sign_session_id'] ?? 0;
		$session_date = $params['session_date'];
		$orderId = $params['order_id'] ?? 0;
        if (!$id) {
            throw new \Exception('活动ID不能为空');
        }

        $where = [
            'id' => $id
        ];
        if((int)$orderId == 0){
            $where['status'] = SocialActivity::STATUS_10;
        }
        $activity = (new SocialActivity())
            ->where($where)
            ->findOrEmpty();

        if ($activity->isEmpty()) {
            throw new \Exception('活动不存在');
        }

        $res = $activity->toArray();
        $res['poster'] = $res['poster'] ? replace_file_domain($res['poster']) : '';
        $res['position_poster'] = $res['position_poster'] ? replace_file_domain($res['position_poster']) : '';
        $res['content'] = $res['content'] ? replace_file_domain_content($res['content']) : '';
        if (!$res['poster'] && $res['position_poster']) {
            $res['poster'] = $res['position_poster'];
        }
        $res['activity_status'] = $this->getActivityStatus($res['start_time'], $res['end_time']);
        if($res['status'] == SocialActivity::STATUS_30){
            $res['activity_status'] = 4;
        }
        $res['activity_status_text'] = $this->getActivityStatusText($res['activity_status']);

	    $ticketPrices = (new SocialActivitySessionTicket())
		    ->where(['activity_id' => $id])
		    ->column('price');
	    $res['min_ticket_price'] = $ticketPrices ? min($ticketPrices) : 0;
        $res['signup_count'] = $this->activitySignupCount($id);
        $sessions = (new SocialActivitySession())->where(['activity_id' => $id])->select()->toArray();
        $sessionIds = array_column($sessions, 'id');

        $tickets = [];
        if (!empty($sessionIds)) {
            $tickets = (new SocialActivitySessionTicket())
                ->where([['session_id', 'in', $sessionIds]])
                ->select()
                ->toArray();
        }

        $sessionTickets = [];
        foreach ($tickets as $ticket) {
            $ticket['point'] = (bool)$ticket['point'];
            $ticket['order_count'] = SocialActivityOrder::alias('o')
				->where([
					'o.session_id' => $ticket['session_id'],
                    'o.ticket_id' => $ticket['id'],
                ])->whereIn('o.status', [0, 10])
                ->sum('o.num');
	        $sessionTickets[$ticket['session_id']] = $sessionTickets[$ticket['session_id']] ?? [];
	        $sessionTickets[$ticket['session_id']][] = $ticket;
        }

        foreach ($sessions as &$session) {
            $session['age_range'] = 0;
            if ($session['age_min'] > 0 && $session['age_max'] > 0) {
                $session['age_range'] = 1;
            }
            $session['location_type'] = 2;
            $session['location_name'] = $session['location'] ?? '';
            if ($session['location_id']) {
                $session['location_type'] = 1;
                $session['location_name'] = (new HouseVenueActivity())->where(['status' => 1, 'is_del' => 0, 'id' => $session['location_id']])->value('title');
            }
            $session['location_id'] = (int)$session['location_id'];
            $session['village_id'] = $session['village_id'] ? array_map('intval', array_filter(explode(',', $session['village_id']), 'is_numeric')) : [];
            $session['tickets'] = $sessionTickets[$session['id']] ?? [];
	        $session['show'] = 0;
	        $now = date('Y-m-d H:i:s');
	        if ($now >= $session['ticket_start_time'] && $now <= $session['ticket_end_time']) {
		        $session['show'] = 1;
	        }
            $villageNames = (new HouseVillage())->where([['village_id', 'in', $session['village_id']], ['status', '=', 1]])->column('village_name');
            $session['village_name'] = $villageNames;
	        $session['start_time'] = date('Y-m-d H:i',strtotime($session['start_time']));
	        $session['end_time'] = date('Y-m-d H:i',strtotime($session['end_time']));
	        $session['ticket_start_time'] = date('Y-m-d H:i', strtotime($session['ticket_start_time']));
	        $session['ticket_end_time'] = date('Y-m-d H:i', strtotime($session['ticket_end_time']));
        }

        $res['sessions'] = $sessions;
        $this->setActivityDisplayTime($res, $sessions);
        $res['order_info'] = [];
        $res['can_refund'] = 0; //0-不能取消订单，1-可以取消订单
        if ($orderId > 0) {
            $res['order_info'] = (new SocialActivityParticipantService())->scope(new SocialActivityOrder(), $uid)->where(['id' => $orderId, 'activity_id' => $id])->findOrEmpty();
            if ($res['order_info']->isEmpty()) { throw new \RuntimeException('订单不存在或无权查看'); }
            $res['order_info']['is_payer'] = (int)$res['order_info']['uid'] === (int)$uid;
            $res['order_info']['participants'] = (new SocialActivityParticipantService())->display($orderId, $uid, $res['order_info']['is_payer']);
	        if (
	            !$res['order_info']->isEmpty()
	            && floatval($res['order_info']['amount']) <= 0
	            && intval($res['order_info']['paid']) === 0
	            && intval($res['order_info']['status']) === SocialActivityOrder::STATUS_0
	        ) {
	            $freeOrderTime = date('Y-m-d H:i:s');
	            $res['order_info']->save([
	                'paid' => 1,
	                'status' => SocialActivityOrder::STATUS_10,
	                'pay_time' => $freeOrderTime,
	                'update_time' => $freeOrderTime,
	            ]);
	            $res['order_info']['paid'] = 1;
	            $res['order_info']['status'] = SocialActivityOrder::STATUS_10;
	            $res['order_info']['pay_time'] = $freeOrderTime;
	        }
	        if (intval($res['order_info']['status']) === SocialActivityOrder::STATUS_20) {
	            $res['order_info']['status_text'] = '已退款';
	        } elseif (
	            floatval($res['order_info']['amount']) <= 0
	            && intval($res['order_info']['status']) === SocialActivityOrder::STATUS_10
	        ) {
	            $res['order_info']['status_text'] = '已报名';
	        }
	        $res['order_info']['can_use_time'] = date('Y-m-d H:i', strtotime($res['order_info']['ticket_start_time'])).' ~ '.date('Y-m-d H:i', strtotime($res['order_info']['ticket_end_time']));
	        $res['order_info']['order_type'] = 'social_activity';
	        if ($res['order_info']['session_id']) {
				$orderSession = SocialActivitySession::where(['id' => $res['order_info']['session_id']])->findOrEmpty();
				if($orderSession->location_id) {
					$orderSession['location'] = (new HouseVenueActivity())->where(['status' => 1, 'is_del' => 0, 'id' => $orderSession->location_id])->value('title');
				}
		        $res['order_info']['location'] = $orderSession['location'];
		    }
	        if (!empty($res['order_info']['refund_status'])) {
                $refundDisplay = (new SocialActivityRefundService())->userStatus($res['order_info']['id'], $res['order_info']['uid']);
                $res['order_info']['refund_status'] = $refundDisplay['refund_status'];
                $res['order_info']['status_text'] = $refundDisplay['status_text'];
            }
            try {
                SocialActivityRefundPolicy::validate($res['order_info']->toArray(), $uid, time());
                $res['can_refund'] = 1;
            } catch (\RuntimeException $e) {
                $res['refund_disabled_reason'] = $e->getMessage();
            }
            // Preserve the persisted order usage flag for refund checks above, then expose personal sign state.
            $mine = (new SocialActivityParticipantService())->memberQuery($uid)->where('order_id', $orderId)->select()->toArray();
            if ($mine) {
                $res['order_info']['is_sign'] = (int)(count(array_filter($mine, function ($p) { return !$p['is_sign']; })) === 0);
            } elseif (!empty($res['order_info']['participants'])) {
                $res['order_info']['is_sign'] = 0;
            }
            if (!$res['order_info']['is_payer']) {
                // A participant must not receive the primary attendee's ID card or the payer's gateway data.
                foreach (['id_number', 'user_name', 'user_source', 'paid_extra', 'pay_orderno'] as $privateField) {
                    unset($res['order_info'][$privateField]);
                }
            }
        }

        $res['is_success_sign'] = 0;
        $res['success_sign_msg'] = '';
        if($uid && $is_sign && $sign_session_id){
            $signSession = (new SocialActivitySession())->where(['id' => $sign_session_id, 'activity_id' => $id])->findOrEmpty();
            [$canCheckIn, $checkInMsg] = $this->getSessionCheckInStatus($signSession);
            if (!$canCheckIn) {
                $res['is_success_sign'] = 1;
                $res['success_sign_msg'] = $checkInMsg;
            } else {
                $signed = \think\facade\Db::transaction(function () use ($id, $sign_session_id, $uid) {
                    return (new SocialActivityParticipantService())->sign($id, $sign_session_id, $uid);
                });
                $res['is_success_sign'] = $signed ? 1 : 0;
                $res['success_sign_msg'] = $signed ? '恭喜签到成功' : '您没有可签到的报名或已经签到';
            }
        }
		$this->addViews($id, $uid);
        return $res;
    }

	public function addViews($id, $uid)
    {
        $where = [
            ['activity_id', '=', $id],
            ['uid', '=', $uid],
        ];
        $view = (new SocialActivityViews())->where($where)->find();
        if (!$view) {
            $view = (new SocialActivityViews())->create([
                'activity_id' => $id,
                'uid' => $uid,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        }
		return $view;
    }
    /**
     * 设置活动列表展示时间：同一天单场活动展示场次起止时间，多场或跨天保留原活动时间逻辑。
     * @param array $activity
     * @param array|null $sessions
     * @return void
     */
    private function setActivityDisplayTime(&$activity, $sessions = null)
    {
        $activity['activity_time'] = $activity['activity_time'] ?? '';
        $activity['single_session_start_time'] = $activity['single_session_start_time'] ?? '';

        if (empty($activity['id'])) {
            return;
        }

        if ($sessions === null) {
            $sessions = (new SocialActivitySession())
                ->where(['activity_id' => $activity['id']])
                ->field('id,start_time,end_time')
                ->order('id ASC')
                ->select()
                ->toArray();
        }

        if (count($sessions) == 1 && !empty($sessions[0]['start_time']) && !empty($sessions[0]['end_time'])) {
            $startTimestamp = strtotime($sessions[0]['start_time']);
            $endTimestamp = strtotime($sessions[0]['end_time']);
            if (date('Y-m-d', $startTimestamp) == date('Y-m-d', $endTimestamp)) {
                $activity['single_session_start_time'] = date('Y-m-d H:i', $startTimestamp);
                $activity['single_session_end_time'] = date('Y-m-d H:i', $endTimestamp);
                $activity['activity_time'] = date('Y-m-d H:i', $startTimestamp) . ' - ' . date('H:i', $endTimestamp);
            }
        }
    }
    /**
     * 获取活动状态
     * @param $startTime
     * @param $endTime
     * @return int
     */
    private function getActivityStatus($startTime, $endTime)
    {
        $now = date('Y-m-d H:i:s');
        if ($startTime > $now) {
            return 1;
        } elseif ($endTime . ' 23:59:59' >= $now) {
            return 2;
        } else {
            return 3;
        }
    }

    /**
     * 获取活动状态文本
     * @param $status
     * @return string
     */
    private function getActivityStatusText($status)
    {
        $statusMap = [
            1 => '待开始',
            2 => '进行中',
            3 => '已结束',
            4 => '已取消',
        ];
        return $statusMap[$status] ?? '未知';
    }

    /**
     * 获取首页数据
     * @return array
     */
    public function getHomeData()
    {
        return [
            'top_bg_pic' => (new DecorationService())->getTopBgPic(),
			'social_activity_slider_row' => cfg('social_activity_slider_row'),
			'social_activity_slider_num' => cfg('social_activity_slider_num'),
			'social_activity_main_color' => cfg('social_activity_main_color'),
            'nav_list' => $this->getHomeNavList(),
            'hot_activities' => cfg('social_activity_hot_open') ? $this->getHotActivities() : [],
            'comments' => cfg('social_activity_show_previous') ? $this->getHomeComments() : [],
        ];
    }

    /**
     * 获取底部导航菜单
     * @return array
     */
    public function getFooterMenuList()
    {
        $category = (new SocialActivityAdverCategory())->where(['cat_key' => 'footer_menu_list'])->find();
        if (!$category || $category->isEmpty()) {
            return [];
        }

        $list = (new SocialActivityAdver())
            ->where(['cat_id' => $category['cat_id'], 'status' => 1])
            ->field('id,name,pic,url,sort,select_pic')
            ->order('sort DESC')
            ->select()
            ->toArray();
        return [
            'list' => $list,
            'social_activity_footer_font_color' => cfg('social_activity_footer_font_color') ?: '',
            'social_activity_footer_select_font_color' => cfg('social_activity_footer_select_font_color') ?: '',
            'social_activity_footer_bg_color' => cfg('social_activity_footer_bg_color') ?: '',
        ];
    }

    /**
     * 获取首页导航列表
     * @return array
     */
    private function getHomeNavList()
    {
        $category = (new SocialActivityAdverCategory())->where(['cat_key' => 'banner_list'])->find();
        if (!$category || $category->isEmpty()) {
            return [];
        }

        $navList = (new SocialActivityAdver())
            ->where(['cat_id' => $category['cat_id'], 'status' => 1])
            ->field('id,name,pic,url')
            ->order('sort DESC')
            ->select()
            ->toArray();

        return $navList;
    }

    /**
     * 获取热门推荐活动
     * @return array
     */
    private function getHotActivities()
    {
        $field = 'a.id,a.category_id,a.name,a.poster,a.position_poster,a.start_time,a.end_time,a.recommend,c.name as category_name';
        $list = (new SocialActivity())
            ->alias('a')
            ->join('social_activity_category c', 'a.category_id = c.id', 'LEFT')
            ->where([
                ['a.status', '=', SocialActivity::STATUS_10],
                ['a.recommend', '=', 1],
            ])
            ->field($field)
            ->order('a.update_time desc, a.id DESC')
            ->limit(4)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['poster'] = $item['poster'] ? replace_file_domain($item['poster']) : '';
            $item['position_poster'] = $item['position_poster'] ? replace_file_domain($item['position_poster']) : '';
            if (!$item['poster'] && $item['position_poster']) {
                $item['poster'] = $item['position_poster'];
            }
            $item['activity_status'] = $this->getActivityStatus($item['start_time'], $item['end_time']);
            $item['activity_status_text'] = $this->getActivityStatusText($item['activity_status']);
            $item['signup_count'] = $this->activitySignupCount($item['id']);
            $this->setActivityDisplayTime($item);
        }

        return $list;
    }

    public function activitySignupCount($activityId)
    {
        return SocialActivityOrder::where(['activity_id' => $activityId, 'status' => SocialActivityOrder::STATUS_10])->sum('num');
    }
    /**
     * 获取首页评论列表
     * @return array
     */
    private function getHomeComments()
    {
        $field = 'c.id,c.content,c.images,c.create_time,c.like_count,a.name as activity_name,s.name as session_name,u.nickname,u.avatar,
        s.start_time,s.end_time,s.location,s.location_id,a.poster, c.activity_id';
        $list = (new SocialActivityComment())
            ->alias('c')
            ->join('social_activity a', 'c.activity_id = a.id')
            ->join('social_activity_session s', 'c.session_id = s.id')
            ->join('user u', 'c.uid = u.uid')
            ->where([['c.audit_status', '=', SocialActivityComment::STATUS_10]])
	    ->where(['c.is_hot' => 1])
            ->field($field)
            ->order('c.id DESC')
            ->limit(4)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['images'] = !empty($item['images']) ? json_decode($item['images'], true) : [];
            $item['avatar'] = $item['avatar'] ? replace_file_domain($item['avatar']) : '';
            $item['poster'] = $item['poster'] ? replace_file_domain($item['poster']) : '';
            if($item['location_id']) {
                $item['location'] = (new HouseVenueActivity())->where(['status' => 1, 'is_del' => 0, 'id' => $item['location_id']])->value('title');
            }
        }

        return $list;
    }

    /**
     * 获取个人中心数据
     * @param $uid
     * @return array
     */
    public function getMyCenter($uid)
    {
        $user = (new \app\common\model\db\User())->where(['uid' => $uid])->findOrEmpty();
        if ($user->isEmpty()) {
            throw new \Exception('用户不存在');
        }

        $user = $user->toArray();

        $myActivityCount = (new SocialActivityParticipantService())->scope(new SocialActivityOrder(), $uid)
            ->where(['status' => SocialActivityOrder::STATUS_10])
            ->count();

        $myCommentCount = (new SocialActivityComment())
            ->where(['uid' => $uid, 'audit_status' => SocialActivityComment::STATUS_10])
            ->count();

        $incomeScore = (new \app\common\model\db\UserScoreList())
            ->where(['uid' => $uid, 'type' => 1])
	        ->where('order_type', 'IN', ['social_activity_order','social_activity_comment'])
            ->sum('score');

        $expenseScore = (new \app\common\model\db\UserScoreList())
            ->where(['uid' => $uid, 'type' => 2])
	        ->where('order_type', 'IN', ['social_activity_order','social_activity_comment'])
            ->sum('score');

        $socialActivityScore = ($incomeScore ?? 0) - ($expenseScore ?? 0);
	    $commentName = cfg('social_comment_alias_name') ?: '评论';
        $menuList = [
            [
                'name' => '我的活动',
                'icon' => 'calendar',
                'count' => $myActivityCount,
                'url' => '/pages/socialActivity/myActivity',
            ],
            [
                'name' => "我的{$commentName}内容",
                'icon' => 'edit-pen',
                'count' => $myCommentCount,
                'url' => '/pages/socialActivity/pastInteractions?from=myComment',
            ],
        ];

        return [
            'uid' => $uid,
            'phone' => $user['phone'] ?? '',
            'nickname' => $user['nickname'] ?? '',
            'avatar' => $user['avatar'] ? replace_file_domain($user['avatar']) : '',
            'balance' => $user['now_money'] ?? 0,
            'score' => $socialActivityScore ?? 0,
            'vip_level' => $user['level'] ? 'VIP' . $user['level'] : 'VIP0',
            'my_activity_count' => $myActivityCount,
            'my_comment_count' => $myCommentCount,
            'menu_list' => $menuList,
        ];
    }
}
