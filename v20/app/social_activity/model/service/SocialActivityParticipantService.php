<?php
namespace app\social_activity\model\service;

use think\facade\Db;

/** Room membership is always resolved server-side; uid on the order remains the payer. */
class SocialActivityParticipantService
{
    public function roomMembers($uid, $villageId, $bindId, $required = true)
    {
        $room = Db::name('house_village_user_bind')->where([
            'pigcms_id' => (int)$bindId, 'uid' => (int)$uid,
            'village_id' => (int)$villageId, 'status' => 1,
        ])->find();
        if (!$uid || !$room || empty($room['vacancy_id'])) {
            if (!$required) { return []; }
            throw new \RuntimeException('请先选择当前已认证的房间');
        }
        return Db::name('house_village_user_bind')->where([
            'village_id' => (int)$villageId, 'vacancy_id' => $room['vacancy_id'], 'status' => 1,
        ])->order('type,pigcms_id')->select()->toArray();
    }

    public function role($type)
    {
        return [0 => '业主', 3 => '业主', 1 => '家属', 2 => '租客', 4 => '工作人员', 5 => '访客', 6 => '出入证人员'][(int)$type] ?? '其他';
    }

    public function duplicate($activityId, array $person, $lock = false)
    {
        $uid = (int)($person['uid'] ?? 0);
        $idCard = strtoupper(trim($person['id_card'] ?? ''));
        $bindId = (int)$person['pigcms_id'];
        $ids = Db::name('social_activity_participant')->where(function ($q) use ($uid, $idCard, $bindId) {
            $q->where('bind_id', $bindId ?: -1);
            if ($uid) { $q->whereOr('uid', $uid); }
            if ($idCard !== '') { $q->whereOr('id_number', $idCard); }
        })->column('order_id');
        // Also recognise records from before participant tracking existed.
        $legacyIds = Db::name('social_activity_participant')->field('order_id')->buildSql();
        $query = Db::name('social_activity_order')->where('activity_id', (int)$activityId)
            ->whereIn('status', [0, 10])->where(function ($q) use ($ids, $legacyIds, $uid, $idCard) {
                $q->whereIn('id', $ids ?: [-1]);
                if ($uid || $idCard !== '') {
                    $q->whereOr(function ($legacy) use ($legacyIds, $uid, $idCard) {
                        $legacy->whereNotIn('id', $legacyIds)->where(function ($identity) use ($uid, $idCard) {
                            $identity->where('uid', $uid ?: -1);
                            if ($idCard !== '') { $identity->whereOr('id_number', $idCard); }
                        });
                    });
                }
            });
        if ($lock) { $query->lock(true); }
        return $query->find();
    }

    public function options($params)
    {
        if (!Db::name('social_activity')->where(['id' => (int)$params['activity_id'], 'status' => 10])->count()) {
            throw new \RuntimeException('活动不存在或不可报名');
        }
        $allowExternal = Db::name('social_activity_session')->where('activity_id', (int)$params['activity_id'])->whereIn('identity', [1, 3])->count() > 0;
        $members = !empty($params['pigcms_id']) ? $this->roomMembers($params['uid'], $params['village_id'], $params['pigcms_id'], !$allowExternal) : [];
        if (!$members && !$allowExternal) { throw new \RuntimeException('请先选择当前已认证的房间'); }
        $groups = [];
        $seen = [];
        foreach ($members as $person) {
            $key = $this->identityKey($person);
            if (isset($seen[$key])) { continue; }
            $seen[$key] = true;
            $role = $this->role($person['type']);
            $order = $this->duplicate($params['activity_id'], $person);
            $groups[$role][] = [
                'bind_id' => (int)$person['pigcms_id'], 'name' => $person['name'] ?: '未填写姓名',
                'role' => $role, 'is_self' => (int)$person['uid'] === (int)$params['uid'],
                'disabled' => (bool)$order,
                'status_text' => $order ? ((int)$order['paid'] ? '已报名' : '待支付') : '',
            ];
        }
        $result = [];
        foreach ($groups as $role => $people) { $result[] = ['name' => $role, 'people' => $people]; }
        return ['groups' => $result, 'allow_external' => $allowExternal, 'has_bound_room' => !empty($members)];
    }

    private function identityKey($person)
    {
        if (!empty($person['uid'])) { return 'uid:' . (int)$person['uid']; }
        if (!empty($person['id_card'])) { return 'id:' . strtoupper(trim($person['id_card'])); }
        return 'bind:' . (int)$person['pigcms_id'];
    }

    public function resolve($params, $lock = false)
    {
        $selected = $params['participants'] ?? [];
        if (!is_array($selected) || !$selected || count($selected) > 50) {
            throw new \RuntimeException('请选择参加人（最多50人）');
        }
        $requiresRoom = count(array_filter($selected, function ($p) { return empty($p['is_external']); })) > 0;
        $members = $requiresRoom ? array_column($this->roomMembers($params['uid'], $params['village_id'], $params['pigcms_id']), null, 'pigcms_id') : [];
        $session = Db::name('social_activity_session')->where(['id' => (int)$params['session_id'], 'activity_id' => (int)$params['activity_id']])->find();
        $people = []; $seen = [];
        foreach ($selected as $input) {
            $bindId = (int)($input['bind_id'] ?? 0);
            if (!empty($input['is_external'])) {
                if (!$session || !in_array((int)$session['identity'], [1, 3], true)) { throw new \RuntimeException('仅限业主的场次不能添加外部用户'); }
                $name = trim($input['user_name'] ?? '');
                $phone = trim($input['phone'] ?? '');
                $card = strtoupper(trim($input['id_number'] ?? ''));
                if ($name === '' || $phone === '' || $card === '') { throw new \RuntimeException('外部用户的姓名、电话、身份证号均不能为空'); }
                if (mb_strlen($name) > 100 || !preg_match('/^1[3-9]\d{9}$/', $phone) || !preg_match('/^\d{17}[\dX]$/', $card)) { throw new \RuntimeException('外部用户姓名、手机号或身份证号格式不正确'); }
                $linkedUid = (int)Db::name('user')->where('phone', $phone)->order('uid')->value('uid');
                $person = ['pigcms_id' => 0, 'uid' => $linkedUid, 'name' => $name, 'phone' => $phone, 'id_card' => $card,
                    'type' => -1, 'is_external' => 1, 'single_id' => 0, 'floor_id' => 0, 'layer_id' => 0, 'vacancy_id' => 0, 'address' => '外部用户'];
            } else {
                if (!isset($members[$bindId])) { throw new \RuntimeException('参加人已不在当前房间，请重新选择'); }
                $person = $members[$bindId];
                $person['is_external'] = 0;
            }
            $person['id_card'] = strtoupper(trim($person['id_card'] ?: ($input['id_number'] ?? '')));
            $person['name'] = trim($person['name'] ?: ($input['user_name'] ?? ''));
            $key = $this->identityKey($person);
            $cardKey = 'id:' . $person['id_card'];
            if (isset($seen[$key]) || ($person['id_card'] && isset($seen[$cardKey]))) {
                throw new \RuntimeException('同一个参加人不能重复选择');
            }
            $seen[$key] = true;
            if ($person['id_card']) { $seen[$cardKey] = true; }
            if ($this->duplicate($params['activity_id'], $person, $lock)) {
                throw new \RuntimeException(($person['name'] ?: '该参加人') . '已报名或有待支付订单，请勿重复报名');
            }
            $people[] = $person;
        }
        return $people;
    }

    public function memberQuery($uid)
    {
        if ((int)$uid <= 0) { return Db::name('social_activity_participant')->where('id', -1); }
        $bindIds = Db::name('house_village_user_bind')->where(['uid' => (int)$uid, 'status' => 1])->column('pigcms_id');
        $phone = trim(Db::name('user')->where('uid', (int)$uid)->value('phone') ?: '');
        return Db::name('social_activity_participant')->where(function ($q) use ($uid, $bindIds, $phone) {
            $q->where('uid', (int)$uid ?: -1)->whereOr('bind_id', 'in', $bindIds ?: [-1]);
            if ($phone !== '') {
                $q->whereOr(function ($external) use ($phone) { $external->where(['uid' => 0, 'bind_id' => 0, 'phone' => $phone]); });
            }
        });
    }

    public function scope($query, $uid, $alias = '', $payer = true)
    {
        $prefix = $alias ? $alias . '.' : '';
        $ids = $this->memberQuery($uid)->column('order_id');
        $legacy = Db::name('social_activity_participant')->field('order_id')->buildSql();
        return $query->where(function ($q) use ($uid, $prefix, $ids, $payer, $legacy) {
            $q->whereIn($prefix . 'id', $ids ?: [-1]);
            $q->whereOr(function ($own) use ($uid, $prefix, $payer, $legacy) {
                $own->where($prefix . 'uid', (int)$uid ?: -1);
                if (!$payer) { $own->whereNotIn($prefix . 'id', $legacy); }
            });
        });
    }

    public function display($orderId, $uid, $payer)
    {
        if (!$payer && !$this->scope(Db::name('social_activity_order'), $uid)->where('id', (int)$orderId)->count()) {
            throw new \RuntimeException('订单不存在或无权查看');
        }
        $query = Db::name('social_activity_participant');
        $rows = $query->where('order_id', (int)$orderId)
            ->field('id as participant_id,bind_id,user_name,role,is_sign,sign_time')->select()->toArray();
        foreach ($rows as &$row) { $row['is_sign'] = (int)$row['is_sign']; }
        return $rows;
    }

    public function sign($activityId, $sessionId, $uid)
    {
        $orders = $this->scope(Db::name('social_activity_order'), $uid, '', false)
            ->where(['activity_id' => $activityId, 'session_id' => $sessionId, 'paid' => 1, 'status' => 10, 'refund_status' => 0])
            ->lock(true)->select()->toArray();
        $changed = 0; $now = date('Y-m-d H:i:s');
        foreach ($orders as $order) {
            $hasPeople = Db::name('social_activity_participant')->where('order_id', $order['id'])->count();
            if ($hasPeople) {
                $changed += $this->memberQuery($uid)->where(['order_id' => $order['id'], 'is_sign' => 0])
                    ->update(['is_sign' => 1, 'sign_time' => $now]);
                // The order flag means that at least one seat has been used, so whole-order refunds are blocked.
                $used = Db::name('social_activity_participant')->where(['order_id' => $order['id'], 'is_sign' => 1])->count();
                if ($used) { Db::name('social_activity_order')->where('id', $order['id'])->update(['is_sign' => 1, 'sign_time' => $now]); }
            } else {
                $changed += Db::name('social_activity_order')->where(['id' => $order['id'], 'is_sign' => 0])->update(['is_sign' => 1, 'sign_time' => $now]);
            }
        }
        return $changed;
    }

    public function signedCount($orderQuery)
    {
        $ids = $orderQuery->column('id');
        if (!$ids) { return 0; }
        $modern = Db::name('social_activity_participant')->whereIn('order_id', $ids)->column('order_id');
        $signed = Db::name('social_activity_participant')->whereIn('order_id', $ids)->where('is_sign', 1)->count();
        $legacyIds = array_values(array_diff($ids, $modern));
        return $signed + ($legacyIds ? Db::name('social_activity_order')->whereIn('id', $legacyIds)->where('is_sign', 1)->sum('num') : 0);
    }
}
