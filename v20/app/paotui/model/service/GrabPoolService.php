<?php

namespace app\paotui\model\service;

use app\paotui\model\db\PaotuiGrabPool;
use think\facade\Db;

/**
 * 跑腿增值费用
 *
 * @author: zt
 * @date: 2025/07/07
 */
class GrabPoolService
{
    public $grabPoolMod;

    public function __construct()
    {
        $this->grabPoolMod = new PaotuiGrabPool();
    }

    public function saveGrabPool($params)
    {
        $rule = [
            'name' => 'require',
            'status' => 'require|in:0,1',
        ];
        $message = [
            'name' => '抢单池名称不能为空',
            'status' => '请选择状态',
        ];

        if (validate($rule, $message)->check($params)) {
            $data = [
                'name' => $params['name'],
                'status' => $params['status'],
            ];

            $id = $params['id'] ?? 0;
            if ($id > 0) {
                //编辑
                $old = $this->grabPoolMod->getOne(['id' => $id]);
                if (empty($old)) {
                    throw new \think\Exception('抢单池不存在');
                } else {
                    return $this->grabPoolMod->updateThis(['id' => $id], $data);
                }
            } else {
                //新增
                return $this->grabPoolMod->add($data);
            }
        }
        return false;
    }

    //删除抢单池
    public function deleteGrabPool($id)
    {
        $old = $this->grabPoolMod->getOne(['id' => $id]);
        if (empty($old)) {
            throw new \think\Exception('抢单池不存在');
        } else {
            $this->grabPoolMod->where('id', $id)->delete();
            Db::name('paotui_grab_pool_deliver')->where('pool_id', $id)->delete();
            Db::name('paotui_grab_pool_merchant')->where('pool_id', $id)->delete();
            return true;
        }
    }

    public function getLists($params)
    {
        $where = [];
        $fields = '*';
        $lists = $this->grabPoolMod
            ->where($where)
            ->field($fields)
            ->append(['status_text'])
            ->paginate(['list_rows' => $params['page_size'], 'page' => $params['page']])
            ->each(function ($item) {
                $item['bind_deliver_user_count'] = Db::name('paotui_grab_pool_deliver')->where('pool_id', $item['id'])->count();
                $item['bind_merchant_count'] = Db::name('paotui_grab_pool_merchant')->where('pool_id', $item['id'])->count();
                return $item;
            })
            ->toArray();
        return $lists;
    }

    //骑手列表
    public function deliverUser($params)
    {
        $where = [
            ['u.group', '=', 1],
            ['u.status', '<>', 4]
        ];
        if (!empty($params['name'])) {
            $where[] = ['u.name', 'like', '%' . $params['name'] . '%'];
        }
        if (!empty($params['phone'])) {
            $where[] = ['u.phone', '=', $params['phone']];
        }
        if (isset($params['register_type']) && in_array($params['register_type'], [0, 1])) {
            $where[] = ['u.register_type', '=', $params['register_type']];
        }

        $lists = Db::name('deliver_user')->alias('u')
            ->leftJoin('paotui_grab_pool_deliver d', 'u.uid = d.deliver_uid AND d.pool_id =' . $params['pool_id'])
            ->where($where)
            ->field('uid,name,phone,site,register_type,status,IF(d.id >0,true,false) AS is_bind')
            ->order('d.id desc,u.uid ASC')
            ->paginate(['list_rows' => $params['page_size'], 'page' => $params['page']])
            ->toArray();
        return $lists;
    }

    //变更骑手绑定状态
    public function changeBindDeliverUserStatus($poolId, $deliverUid)
    {
        $where = [
            ['pool_id', '=', $poolId],
            ['deliver_uid', '=', $deliverUid]
        ];

        $old = Db::name('paotui_grab_pool_deliver')->where($where)->find();
        if ($old) {
            //解绑删除
            Db::name('paotui_grab_pool_deliver')->where('id', $old['id'])->delete();
        } else {
            //绑定
            $data = [
                'pool_id' => $poolId,
                'deliver_uid' => $deliverUid,
            ];
            Db::name('paotui_grab_pool_deliver')->insert($data);
        }
        return true;
    }

    //商家列表
    public function merchant($params)
    {
        if(empty($params['pool_id'])){
            return [];
        }

        $where = [
            ['m.status', '<>', 4]
        ];
        if (!empty($params['name'])) {
            $where[] = ['m.name', 'like', '%' . $params['name'] . '%'];
        }
        if (!empty($params['phone'])) {
            $where[] = ['m.phone', '=', $params['phone']];
        }
        if (isset($params['status']) && $params['status'] > -1) {
            $where[] = ['m.status', '=', $params['status']];
        }
        $lists = Db::name('merchant')->alias('m')
            ->leftJoin('paotui_grab_pool_merchant pm', 'pm.mer_id = m.mer_id AND pm.pool_id =' . $params['pool_id'])
            ->where($where)
            ->field('m.mer_id,m.name,m.phone,m.status,IF(pm.id >0,true,false) AS is_bind')
            ->order('pm.id desc,m.mer_id ASC')
            ->paginate(['list_rows' => $params['page_size'], 'page' => $params['page']])
            ->toArray();
        return $lists;
    }

    public function changeBindMerchantStatus($poolId, $merId)
    {
        $where = [
            ['pool_id', '=', $poolId],
            ['mer_id', '=', $merId]
        ];

        $old = Db::name('paotui_grab_pool_merchant')->where($where)->find();
        if ($old) {
            //解绑删除
            Db::name('paotui_grab_pool_merchant')->where('id', $old['id'])->delete();
        } else {
            //绑定
            $data = [
                'pool_id' => $poolId,
                'mer_id' => $merId,
            ];
            Db::name('paotui_grab_pool_merchant')->insert($data);
        }
        return true;
    }
}
