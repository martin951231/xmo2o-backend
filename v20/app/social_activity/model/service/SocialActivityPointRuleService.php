<?php

namespace app\social_activity\model\service;

use app\social_activity\model\db\SocialActivityPointRule;

class SocialActivityPointRuleService
{
    /**
     * 获取积分规则列表
     * @param $param
     * @return array
     */
    public function getPointRuleList($param)
    {
        $field = 'id,name,sign_point,participate_point,share_point,pay_ratio,status,create_time,update_time';
        $paginate = (new SocialActivityPointRule())
            ->withSearch(['name', 'status', 'start_time', 'end_time'],$param)
	        ->append(['status_text'])
            ->where('is_del', 0)
            ->field($field)
            ->order('id DESC')
            ->paginate([
                'list_rows' => $param['pageSize'],
                'page' => $param['page'],
            ]);

        return $paginate;
    }

    /**
     * 获取积分规则详情
     * @param $param
     * @return array
     */
    public function getPointRuleDetail($param)
    {
        if (!$param['id']) {
            throw_exception('查看对象不能为空！');
        }
        $info = (new SocialActivityPointRule())->getRuleById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('查看对象不存在或已被删除！');
        }
        return $info->toArray();
    }

    /**
     * 保存积分规则（新增/编辑）
     * @param $param
     * @return bool
     */
    public function savePointRule($param)
    {
        $id = $param['id'] ?? 0;
        if ($id > 0) {
            // 编辑模式
            $info = (new SocialActivityPointRule())->getRuleById($id);
            if (!$info || $info->isEmpty()) {
                throw_exception('编辑对象不存在！');
            }

            // 检查积分规则名称是否重复
            if (isset($param['name']) && $param['name'] != $info['name']) {
                $existRule = (new SocialActivityPointRule())->where(['name' => $param['name']])->find();
                if ($existRule && !$existRule->isEmpty()) {
                    throw_exception('积分规则名称已存在！');
                }
            }

            $data = [
                'name' => $param['name'] ?? $info['name'],
                'sign_point' => isset($param['sign_point']) ? intval($param['sign_point']) : $info['sign_point'],
                'participate_point' => isset($param['participate_point']) ? intval($param['participate_point']) : $info['participate_point'],
                'share_point' => isset($param['share_point']) ? intval($param['share_point']) : $info['share_point'],
                'pay_ratio' => isset($param['pay_ratio']) ? floatval($param['pay_ratio']) : $info['pay_ratio'],
                'status' => isset($param['status']) ? intval($param['status']) : $info['status'],
            ];

            $result = (new SocialActivityPointRule())->where(['id' => $id])->save($data);
            if ($result === false) {
                throw_exception('编辑失败！');
            }
        } else {
            // 新增模式
            $existRule = (new SocialActivityPointRule())->where(['name' => $param['name']])->find();
            if ($existRule && !$existRule->isEmpty()) {
                throw_exception('积分规则名称已存在！');
            }

            $data = [
                'name' => $param['name'],
                'sign_point' => intval($param['sign_point']),
                'participate_point' => intval($param['participate_point']),
                'share_point' => intval($param['share_point']),
                'pay_ratio' => floatval($param['pay_ratio']),
                'status' => intval($param['status']),
            ];

            $result = (new SocialActivityPointRule())->save($data);
            if (!$result) {
                throw_exception('新增失败！');
            }
        }

        return true;
    }

    /**
     * 删除积分规则
     * @param $param
     * @return bool
     */
    public function deletePointRule($param)
    {
        if (!$param['id']) {
            throw_exception('删除对象不能为空！');
        }
        $info = (new SocialActivityPointRule())->getRuleById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('删除对象不存在！');
        }

        $result = (new SocialActivityPointRule())->where(['id' => $param['id']])->update(['is_del' => 1]);
        if (!$result) {
            throw_exception('删除失败！');
        }

        return true;
    }

    /**
     * 修改积分规则状态
     * @param $param
     * @return bool
     */
    public function changeStatus($param)
    {
        if (!$param['id']) {
            throw_exception('修改对象不能为空！');
        }
        $info = (new SocialActivityPointRule())->getRuleById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('修改对象不存在！');
        }

        $result = (new SocialActivityPointRule())->where(['id' => $param['id']])->save([
            'status' => intval($param['status']),
        ]);
        if ($result === false) {
            throw_exception('操作失败！');
        }

        return true;
    }
}
