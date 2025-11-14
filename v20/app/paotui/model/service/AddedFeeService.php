<?php

namespace app\paotui\model\service;

use app\paotui\model\db\PaotuiAddedFee;

/**
 * 跑腿增值费用
 *
 * @author: zt
 * @date: 2025/07/07
 */
class AddedFeeService
{
    public $addedFeeMod;

    public function __construct()
    {
        $this->addedFeeMod = new PaotuiAddedFee();
    }

    public function saveAddedFee($params)
    {
        $rule = [
            'name' => 'require',
            'money' => 'require|egt:0',
            'begin_time' => 'require',
            'end_time' => 'require',
            'status' => 'require|in:0,1',
            'bind_business' => 'require|in:1,2,3',
        ];
        $message = [
            'name' => '请输入费用名称',
            'money' => '请输入费用金额',
            'begin_time' => '请输入生效时间',
            'end_time' => '请输入失效时间',
            'status' => '请选择状态',
            'bind_business' => '请选择绑定业务类型',
        ];

        if (validate($rule, $message)->check($params)) {
            if (strtotime($params['begin_time']) > strtotime($params['end_time'])) {
                throw new \think\Exception('生效时间设置有误');
            }

            $data = [
                'name' => $params['name'],
                'money' => $params['money'],
                'begin_time' => $params['begin_time'],
                'end_time' => $params['end_time'],
                'status' => $params['status'],
                'bind_business' => $params['bind_business'],
            ];

            $id = $params['id'] ?? 0;
            if ($id > 0) {
                //编辑
                $old = $this->addedFeeMod->getOne(['id' => $id]);
                if (empty($old)) {
                    throw new \think\Exception('增值费用不存在');
                } else {
                    return $this->addedFeeMod->updateThis(['id' => $id], $data);
                }
            } else {
                //新增
                return $this->addedFeeMod->add($data);
            }
        }
        return false;
    }

    public function getLists($params)
    {
        $where = [];
        $fields = '*';
        $lists = $this->addedFeeMod
            ->where($where)
            ->field($fields)
            ->append(['status_text', 'bind_business_text'])
            ->paginate(['list_rows' => $params['page_size'], 'page' => $params['page']])
            ->toArray();
        return $lists;
    }
}
